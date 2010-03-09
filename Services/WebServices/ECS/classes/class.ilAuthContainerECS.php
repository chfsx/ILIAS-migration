<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once('Auth/Container.php');

/** 
* Custom PEAR Auth Container for ECS auth checks
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
* @ingroup ServicesWebServicesECS 
*/
class ilAuthContainerECS extends Auth_Container
{
	protected $mid = null;
	protected $abreviation = null;
	
	protected $log;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function __construct($a_params = array())
	{
	 	parent::__construct($a_params);
		$this->initECSServices();
		
		$this->log = $GLOBALS['ilLog'];
	}
	
	/**
	 * get abbreviation
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function getAbreviation()
	{
	 	return $this->abreviation;
	}
	
	/**
	 * get mid
	 *
	 * @access public
	 */
	public function getMID()
	{
		return $this->mid;	 	
	}
	
	
	/**
	 * fetch data
	 *
	 * @access public
	 * @param string username
	 * @param string pass
	 * 
	 */
	public function fetchData($a_username,$a_pass)
	{
		global $ilLog;
		
		$ilLog->write(__METHOD__.': Starting ECS authentication.');
		
		if(!$this->settings->isEnabled())
		{
			$ilLog->write(__METHOD__.': ECS settings .');
			return false;
		}

	 	// Check if hash is valid ...
	 	include_once('./Services/WebServices/ECS/classes/class.ilECSConnector.php');
	 	
	 	try
	 	{
	 		$connector = new ilECSConnector();
	 		$res = $connector->getAuth($_GET['ecs_hash']);
			$auths = $res->getResult();
			$this->mid = $auths[0]->mid;
			$ilLog->write(__METHOD__.': Got mid: '.$this->mid);
			$this->abreviation = $auths[0]->abr;
			$ilLog->write(__METHOD__.': Got abr: '.$this->abreviation);
			/*			
			// Read abbreviation from mid
			$res = $connector->getMemberships($this->mid);
			$member = $res->getResult();
			$this->abbreviation = $member[0]->participants[0]->abr;
			*/
		 	return true;
	 	}
	 	catch(ilECSConnectorException $e)
	 	{
	 		$ilLog->write(__METHOD__.': Authentication failed with message: '.$e->getMessage());
	 		return false;
	 	}
	}
	
	/** 
	 * Called from base class after successful login
	 *
	 * @param string username
	 */
	public function loginObserver($a_username, $a_auth)
	{
		include_once('./Services/WebServices/ECS/classes/class.ilECSUser.php');
		
		$user = new ilECSUser($_GET);
		
		if(!$usr_id = ilObject::_lookupObjIdByImportId($user->getImportId()))
		{
			$username = $this->createUser($user);
		}
		else
		{
			$username = $this->updateUser($user,$usr_id);
		}
		
		$a_auth->setAuth($username);
		$this->log->write(__METHOD__.': Login succesesful');
		return true;
	}
	
	/** 
	 * Called from base class after failed login
	 *
	 * @param string username
	 */
	public function failedLoginObserver()
	{
		$this->log->write(__METHOD__.': Login failed');
	}
	
	

	/**
	 * create new user
	 *
	 * @access protected
	 */
	protected function createUser(ilECSUser $user)
	{
			global $ilClientIniFile,$ilSetting,$rbacadmin,$ilLog;
			
			$userObj = new ilObjUser();
			
			include_once('./Services/Authentication/classes/class.ilAuthUtils.php');
			$local_user = ilAuthUtils::_generateLogin($this->getAbreviation().'_'.$user->getLogin());
			
			$newUser["login"] = $local_user;
			$newUser["firstname"] = $user->getFirstname();
			$newUser["lastname"] = $user->getLastname();
			$newUser['email'] = $user->getEmail();
			$newUser['institution'] = $user->getInstitution();
			
			// set "plain md5" password (= no valid password)
			$newUser["passwd"] = ""; 
			$newUser["passwd_type"] = IL_PASSWD_MD5;
							
			$newUser["auth_mode"] = "ecs";
			$newUser["profile_incomplete"] = 0;
			
			// system data
			$userObj->assignData($newUser);
			$userObj->setTitle($userObj->getFullname());
			$userObj->setDescription($userObj->getEmail());
		
			// set user language to system language
			$userObj->setLanguage($ilSetting->get("language"));
			
			// Time limit
			$userObj->setTimeLimitOwner(7);
			$userObj->setTimeLimitUnlimited(0);
			$userObj->setTimeLimitFrom(time());
			$userObj->setTimeLimitUntil(time() + $ilClientIniFile->readVariable("session","expire"));
							
			// Create user in DB
			$userObj->setOwner(6);
			$userObj->create();
			$userObj->setActive(1);
			$userObj->updateOwner();
			$userObj->saveAsNew();
			$userObj->writePrefs();

			$this->initSettings();
			if($global_role = $this->settings->getGlobalRole())
			{
				$rbacadmin->assignUser($this->settings->getGlobalRole(),$userObj->getId(),true);
			}
			ilObject::_writeImportId($userObj->getId(),$user->getImportId());
			
			$ilLog->write(__METHOD__.': Created new remote user with usr_id: '.$user->getImportId());
			
			// Send Mail
			#$this->sendNotification($userObj);
			
			return $userObj->getLogin();
	}
	
	/**
	 * update existing user
	 *
	 * @access protected
	 */
	protected function updateUser(ilECSUser $user,$a_local_user_id)
	{
		global $ilClientIniFile,$ilLog,$rbacadmin;
		
		$user_obj = new ilObjUser($a_local_user_id);
		$user_obj->setFirstname($user->getFirstname());
		$user_obj->setLastname($user->getLastname());
		$user_obj->setEmail($user->getEmail());
		$user_obj->setInstitution($user->getInstitution());
		
		$until = $user_obj->getTimeLimitUntil();

		if($until < (time() + $ilClientIniFile->readVariable('session','expire')))
		{		
			$user_obj->setTimeLimitFrom(time());
			$user_obj->setTimeLimitUntil(time() + $ilClientIniFile->readVariable("session","expire"));
		}
		$user_obj->update();
		$user_obj->refreshLogin();
		
		$this->initSettings();
		if($global_role = $this->settings->getGlobalRole())
		{
			$rbacadmin->assignUser($this->settings->getGlobalRole(),$user_obj->getId(),true);
		}

		$ilLog->write(__METHOD__.': Finished update of remote user with usr_id: '.$user->getImportId());	
		return $user_obj->getLogin();
	}
	
	
	/**
	 * init ecs settings
	 *
	 * @access private
	 * 
	 */
	private function initSettings()
	{
	 	include_once('./Services/WebServices/ECS/classes/class.ilECSSettings.php');
	 	$this->settings = ilECSSettings::_getInstance();
	}
	

	/**
	 * Init ECS Services
	 * @access private
	 * @param
	 * 
	 */
	private function initECSServices()
	{
	 	include_once('./Services/WebServices/ECS/classes/class.ilECSSettings.php');
	 	$this->settings = ilECSSettings::_getInstance();
	}
	
	/**
	 * Send notification
	 *
	 * @access private
	 * @param
	 * 
	 */
	private function sendNotification($user_obj)
	{
		if(!count($this->settings->getUserRecipients()))
		{
			return true;
		}

		include_once('./Services/Language/classes/class.ilLanguageFactory.php');
		$lang = ilLanguageFactory::_getLanguage();
		$GLOBALS['lng'] = $lang;
		$GLOBALS['ilUser'] = $user_obj;
		$lang->loadLanguageModule('ecs');

		include_once('./Services/Mail/classes/class.ilMail.php');
		$mail = new ilMail(6);
		$mail->enableSoap(false);
		$subject = $lang->txt('ecs_new_user_subject');

				// build body
		$body = $lang->txt('ecs_new_user_body')."\n\n";
		$body .= $lang->txt('ecs_new_user_profile')."\n\n";
		$body .= $user_obj->getProfileAsString($lang)."\n\n";
		$body .= ilMail::_getAutoGeneratedMessageString($lang);
		
		$mail->sendMail($this->settings->getUserRecipientsAsString(),"","",$subject,$body,array(),array("normal"));
	}
}


?>