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

/** 
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
* 
* @ilCtrl_Calls 
* @ingroup ServicesWebServicesECS 
*/

include_once('./Services/EventHandling/interfaces/interface.ilAppEventListener.php');

class ilECSAppEventListener implements ilAppEventListener
{
	/**
	* Handle an event in a listener.
	*
	* @param	string	$a_component	component, e.g. "Modules/Forum" or "Services/User"
	* @param	string	$a_event		event e.g. "createUser", "updateUser", "deleteUser", ...
	* @param	array	$a_parameter	parameter array (assoc), array("name" => ..., "phone_office" => ...)
	*/
	static function handleEvent($a_component, $a_event, $a_parameter)
	{
		global $ilLog;
		
		$ilLog->write(__METHOD__.': Listening to event from: '.$a_component);
		
		switch($a_component)
		{
			case 'Services/User':
				switch($a_event)
				{
					case 'afterCreation':
						$user = $a_parameter['user_obj'];
						$this->handleMembership($user);
						break;
				}
				break;
				
			case 'Modules/Course':
				switch($a_event)
				{
					case 'addSubscriber':
					case 'addParticipant':
						
						if(ilObjUser::_lookupAuthMode($a_parameter['usr_id']) == 'ecs')
						{
							if(!$user = ilObjectFactory::getInstanceByObjId($a_parameter['usr_id']))
							{
								return true;
							}
							
							include_once './Services/WebServices/ECS/classes/class.ilECSImport.php';
							$server_id = ilECSImport::lookupServerId($a_parameter['usr_id']);

							include_once('Services/WebServices/ECS/classes/class.ilECSSetting.php');
							$settings = ilECSSetting::getInstanceByServerId($server_id);
							
							$end = new ilDateTime(time(),IL_CAL_UNIX);
							$end->increment(IL_CAL_MONTH,$settings->getDuration());
							
							if($user->getTimeLimitUntil() < $end->get(IL_CAL_UNIX))
							{
								$user->setTimeLimitUntil($end->get(IL_CAL_UNIX));
								$user->update();
								
								$start = $user->getTimeLimitFrom();
								$end = $user->getTimeLimitUntil();

								// send notification only for non session accounts
								if(($end - $start) > (60 * 60 * 24))
								{
									self::_sendNotification($settings,$user);
								}
							}
							unset($user);
						}
						break;
				}
				break;			
		}
	}
	
	/**
	 * send notification about new user accounts
	 *
	 * @access protected
	 */
	protected static function _sendNotification(ilECSSetting $server, ilObjUser $user_obj)
	{
		if(!count($server->getUserRecipients()))
		{
			return true;
		}
		// If sub id is set => mail was send
		include_once './Services/WebServices/ECS/classes/class.ilECSImport.php';
		$import = new ilECSImport($server->getServerId(),$user_obj->getId());
		if($import->getSubId())
		{
			return false;
		}

		include_once('./Services/Language/classes/class.ilLanguageFactory.php');
		$lang = ilLanguageFactory::_getLanguage();
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
		
		$mail->sendMail($server->getUserRecipientsAsString(),"","",$subject,$body,array(),array("normal"));
		
		// Store sub_id = 1 in ecs import which means mail is send
		$import->setSubId(1);
		$import->save();
		
		return true;
	}
	
	/**
	 * Assign mmissing course/groups to new user accounts
	 * @param ilObjUser $user
	 */
	protected function handleMembership(ilObjUser $user)
	{
		if($user->getAuthMode() != ilECSSetting::lookupAuthMode())
		{
			return true;
		}
		
	}
}
?>