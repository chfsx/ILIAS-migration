<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2008 ILIAS open source, University of Cologne            |
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

/** @defgroup ServicesRegistration Services/Registration
 */

/**
* Class ilAccountRegistrationGUI
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilAccountRegistrationGUI:
*
* @ingroup ServicesRegistration
*/

require_once './Services/Registration/classes/class.ilRegistrationSettings.php';
require_once "./Services/User/classes/class.ilUserAgreement.php";

class ilAccountRegistrationGUI
{
	var $ctrl;
	var $tpl;
	
	private $display_pdata = false;
	private $display_cdata = false;
	private $display_odata = false;

	public function __construct()
	{
		global $ilCtrl,$tpl,$lng;

		$this->tpl =& $tpl;

		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this,'lang');

		$this->lng =& $lng;
		$this->lng->loadLanguageModule('registration');

		$this->registration_settings = new ilRegistrationSettings();
	}

	function executeCommand()
	{
		global $ilErr, $tpl;

		if($this->registration_settings->getRegistrationType() == IL_REG_DISABLED)
		{
			$ilErr->raiseError($this->lng->txt('reg_disabled'),$ilErr->FATAL);
		}

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				if($cmd)
				{
					$this->$cmd();
				}
				else
				{
					$this->displayForm();
				}
				break;
		}
		$tpl->show();
		return true;
	}

	function login()
	{
		global $ilias,$lng,$ilLog;

		$ilLog->write("Entered login");

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.usr_registered.html");

		$this->tpl->setVariable("IMG_USER",
			ilUtil::getImagePath("icon_usr_b.gif"));
		$this->tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("registration"));
		$this->tpl->setVariable("TXT_WELCOME", $lng->txt("welcome").", ".$this->userObj->getTitle()."!");

		if ($this->registration_settings->getRegistrationType() == IL_REG_DIRECT and
			!$this->registration_settings->passwordGenerationEnabled())
		{
			$this->tpl->setCurrentBlock("activation");
			$this->tpl->setVariable("TXT_REGISTERED", $lng->txt("txt_registered"));
			$this->tpl->setVariable("FORMACTION", "login.php?cmd=post&target=".$_GET["target"]);
			$this->tpl->setVariable("TARGET","target=\"_parent\"");
			$this->tpl->setVariable("TXT_LOGIN", $lng->txt("login_to_ilias"));
			$this->tpl->setVariable("USERNAME",$this->userObj->getLogin());
			$this->tpl->setVariable("PASSWORD",$_POST["user"]['passwd']);
			$this->tpl->parseCurrentBlock();
		}
		else if ($this->registration_settings->getRegistrationType() == IL_REG_APPROVE)
		{
			$this->tpl->setVariable("TXT_REGISTERED", $lng->txt("txt_submitted"));
		}
		else if($this->registration_settings->getRegistrationType() == IL_REG_ACTIVATION)
		{
			$this->tpl->setVariable("TXT_REGISTERED", sprintf($lng->txt("reg_confirmation_link_successful"), './login.php'));
			$this->tpl->setVariable("REDIRECT_URL", './login.php');
		}
		else
		{
			$this->tpl->setVariable("TXT_REGISTERED", $lng->txt("txt_registered_passw_gen"));
		}
	}
	
	private function getRegistrationFieldsArray()
	{
		$data = array();
		$data["fields"] = array();
		$data["fields"]["login"] = "";

		if (!$this->registration_settings->passwordGenerationEnabled())
		{
			$data["fields"]["passwd"] = "";
			$data["fields"]["passwd2"] = "";
		}

		$data["fields"]["title"] = "";
		$data["fields"]["gender"] = "";
		$data["fields"]["firstname"] = "";
		$data["fields"]["lastname"] = "";
		$data["fields"]["institution"] = "";
		$data["fields"]["department"] = "";
		$data["fields"]["street"] = "";
		$data["fields"]["city"] = "";
		$data["fields"]["zipcode"] = "";
		$data["fields"]["country"] = "";
		$data["fields"]["phone_office"] = "";
		$data["fields"]["phone_home"] = "";
		$data["fields"]["phone_mobile"] = "";
		$data["fields"]["fax"] = "";
		$data["fields"]["email"] = "";
		$data["fields"]["hobby"] = "";
		$data["fields"]["referral_comment"] = "";
		$data["fields"]["matriculation"] = "";
		$data["fields"]["delicious"] = "";
		
		return $data;
	}

	function displayForm()
	{
		global $ilias,$lng,$ObjDefinition,$ilSetting;

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.usr_registration.html");
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");

		//load ILIAS settings
		$settings = $ilias->getAllSettings();

		$this->__showRoleSelection();

		$data = $this->getRegistrationFieldsArray();

		// fill presets
		foreach((array)$data["fields"] as $key => $val)
		{
			if(!in_array($key, array('login')))
			{
				// dont show fields, which are not enabled for registration
				if( isset($settings['require_' . $key]) && (int)$settings['require_' . $key] ||
					(int)$ilSetting->get('usr_settings_visib_reg_' . $key, '1') ||
					in_array($key, array('passwd', 'passwd2')) ||
					$key == 'email' && (
						$this->registration_settings->passwordGenerationEnabled() ||
						$this->registration_settings->getRegistrationType() == IL_REG_ACTIVATION ||
						$this->registration_settings->getRegistrationType() == IL_REG_APPROVE
					)
				){
					$this->tpl->setCurrentBlock($key."_section");
				}
				else
				{
					continue;
				}
			}

			$str = $lng->txt($key);
			if ($key == 'title')
			{
				$str = $lng->txt('person_title');
			}
			
			// check to see if dynamically required
			if (((isset($settings['require_' . $key]) && 
			    (int)$settings['require_' . $key])) 
			    || in_array($key, array('login', 'passwd')) ||
			    ($key == 'email' && ($this->registration_settings->passwordGenerationEnabled() || 
				                     $this->registration_settings->getRegistrationType() == IL_REG_ACTIVATION ||
                                     	$this->registration_settings->getRegistrationType() == IL_REG_APPROVE )))
			{
				$str = $str . '<span class="asterisk">*</span>';
			}

			if($key == 'passwd2')
			{
				// text label for passwd2 is nonstandard
				$str = $lng->txt('retype_password');				
				$str = $str . '<span class="asterisk">*</span>';
			}
			
 			$this->tpl->setVariable("TXT_".strtoupper($key), $str);
			$this->tpl->setVariable(strtoupper($key), ilUtil::prepareFormOutput($_POST['user'][$key],true));
			
			if($key == 'matriculation' || $key == 'delicious')
			{
				$this->display_odata = true;
			}
			else if(in_array($key, array('title', 'gender', 'firstname', 'lastname')))
			{
				$this->display_pdata = true;
			}
			else if(in_array($key, array('institution', 'department', 'street', 'city',
										 'zipcode', 'country', 'phone_office', 'phone_home',
										 'phone_mobile', 'fax', 'email', 'hobby', 'referral_comment')))
			{
				$this->display_cdata = true;
			}

			if($key == 'gender')
			{
				$this->tpl->setVariable('TXT_GENDER_F', $lng->txt('gender_f'));
				$this->tpl->setVariable('TXT_GENDER_M', $lng->txt('gender_m'));
				
				// FILL SAVED VALUES IN CASE OF ERROR
				if(isset($_POST['user']))
				{
					// gender selection
					$gender = strtoupper($_POST['user']['gender']);		
					if(!empty($gender))
					{
						$this->tpl->setVariable('BTN_GENDER_'.$gender, 'checked="checked"');
					}
				}
			}
			
			if(!in_array($key, array('login')))
			{
				$this->tpl->parseCurrentBlock();
			}
		}		

		if($this->registration_settings->passwordGenerationEnabled())
		{
			$this->tpl->setCurrentBlock('select_password');
			$this->tpl->setVariable("TXT_PASSWD_SELECT", $lng->txt("passwd"));
			$this->tpl->setVariable("TXT_PASSWD_VIA_MAIL", $lng->txt("reg_passwd_via_mail"));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_SAVE", $lng->txt("register"));
		$this->tpl->setVariable("TXT_REQUIRED_FIELDS", $lng->txt("required_field"));
		$this->tpl->setVariable("TXT_SETTINGS", $lng->txt("settings"));
		$this->tpl->setVariable("TXT_LOGIN_DATA", $lng->txt("login_data"));
		$this->tpl->setVariable("TXT_LANGUAGE",$lng->txt("language"));
		$this->tpl->setVariable("TXT_OK",$lng->txt("ok"));
		$this->tpl->setVariable("TXT_CHOOSE_LANGUAGE", $lng->txt("choose_language"));
		$this->tpl->setVariable("REG_LANG_FORMACTION",
			$this->ctrl->getFormAction($this));

		// language selection
		$languages = $lng->getInstalledLanguages();

		$count = (int) round(count($languages) / 2);
		$num = 1;

		foreach ($languages as $lang_key)
		{
			/*
			 if ($num === $count)
			 {
			 $this->tpl->touchBlock("lng_new_row");
			 }
			*/

			$this->tpl->setCurrentBlock("languages");
			$this->tpl->setVariable("LINK_LANG",$this->ctrl->getLinkTarget($this,'displayForm'));
			$this->tpl->setVariable("LANG_NAME",
							  ilLanguage::_lookupEntry($lang_key, "meta", "meta_l_".$lang_key));
			$this->tpl->setVariable("LANG_ICON", $lang_key);
			$this->tpl->setVariable("BORDER", 0);
			$this->tpl->setVariable("VSPACE", 0);
			$this->tpl->parseCurrentBlock();

			$num++;
		}

		// preselect previous chosen language otherwise default language
		$selected_lang = (isset($_POST["user"]["language"])) ?
			$_POST["user"]["language"] : $lng->lang_key;
		
		foreach ($languages as $lang_key)
		{
			$this->tpl->setCurrentBlock("language_selection");
			$this->tpl->setVariable("LANG", $lng->txt("lang_".$lang_key));
			$this->tpl->setVariable("LANGSHORT", $lang_key);

			if ($selected_lang == $lang_key)
			{
				$this->tpl->setVariable("SELECTED_LANG", "selected=\"selected\"");
			}

			$this->tpl->parseCurrentBlock();
		} // END language selection


		$this->tpl->setVariable("IMG_USER",
			ilUtil::getImagePath("icon_usr_b.gif"));
		$this->tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("registration"));
		$this->tpl->setVariable("TXT_PAGETITLE", "ILIAS3 - ".$lng->txt("registration"));
		$this->tpl->setVariable("TXT_REGISTER_INFO", $lng->txt("register_info"));
		$this->tpl->setVariable("AGREEMENT", ilUserAgreement::_getText());
		$this->tpl->setVariable("ACCEPT_CHECKBOX", ilUtil::formCheckbox(0, "status", "accepted"));
		$this->tpl->setVariable("ACCEPT_AGREEMENT", $lng->txt("accept_usr_agreement") . '<span class="asterisk">*</span>');

		$this->showUserDefinedFields();
		
		// show personal data if at least one field appears
		if($this->display_pdata)
		{
			$this->tpl->setCurrentBlock('block_headline_personalinfo');
			$this->tpl->setVariable("TXT_PERSONAL_DATA", $lng->txt("personal_data"));
			$this->tpl->parseCurrentBlock();
		}
		// show contact data if at least one field appears
		if($this->display_cdata)
		{		
			$this->tpl->setCurrentBlock('block_headline_contactinfo');
			$this->tpl->setVariable("TXT_CONTACT_DATA", $lng->txt("contact_data"));
			$this->tpl->parseCurrentBlock();
		}
		// show other data if at least one field appears
		if($this->display_odata)
		{		
			$this->tpl->setCurrentBlock('block_headline_otherdata');
			$this->tpl->setVariable("TXT_OTHER", $lng->txt("user_profile_other"));
			$this->tpl->parseCurrentBlock();
		}
	}

	function showUserDefinedFields()
	{
		include_once './Services/User/classes/class.ilUserDefinedFields.php';
		$user_defined_fields =& ilUserDefinedFields::_getInstance();

		#$user_defined_data = $ilUser->getUserDefinedData();
		foreach($user_defined_fields->getRegistrationDefinitions() as $field_id => $definition)
		{
			$this->display_odata = true;
			
			if($definition['field_type'] == UDF_TYPE_TEXT)
			{
				$old = isset($_POST["udf"][$field_id]) ?
					$_POST["udf"][$field_id] : '';


				$this->tpl->setCurrentBlock("field_text");
				$this->tpl->setVariable("FIELD_NAME",'udf['.$definition['field_id'].']');
				$this->tpl->setVariable("FIELD_VALUE",ilUtil::prepareFormOutput($old));
				if(!$definition['changeable'])
				{
					$this->tpl->setVariable("DISABLED_FIELD",'disabled=\"disabled\"');
				}
				$this->tpl->parseCurrentBlock();
			}
			else if($definition['field_type'] == UDF_TYPE_WYSIWYG)
			{
				include_once "./Services/RTE/classes/class.ilRTE.php";
				$rtestring = ilRTE::_getRTEClassname();
				include_once "./Services/RTE/classes/class.$rtestring.php";
				$rte = new $rtestring();
				include_once "./Services/Form/classes/class.ilTextAreaInputGUI.php";
				$ta = new ilTextAreaInputGUI("","");
				$rte->addCustomRTESupport(0, "", $ta->getRteTags());

				$old = isset($_POST["udf"][$field_id]) ?
					$_POST["udf"][$field_id] : '';

				$this->tpl->setCurrentBlock("field_wysiwyg");
				$this->tpl->setVariable("FIELD_NAME",'udf['.$definition['field_id'].']');
				$this->tpl->setVariable("FIELD_VALUE",ilUtil::prepareFormOutput($old));
				if(!$definition['changeable'])
				{
					$this->tpl->setVariable("DISABLED_FIELD",'disabled=\"disabled\"');
				}
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->setCurrentBlock("field_select");
				$this->tpl->setVariable("SELECT_BOX",ilUtil::formSelect($_POST['udf']["$definition[field_id]"],
																  'udf['.$definition['field_id'].']',
																  $user_defined_fields->fieldValuesToSelectArray(
																	  $definition['field_values']),
																  false,
																  true));
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("user_defined");

			if($definition['required'])
			{
				$name = $definition['field_name']."<span class=\"asterisk\">*</span>";
			}
			else
			{
				$name = $definition['field_name'];
			}
			$this->tpl->setVariable("TXT_FIELD_NAME",$name);
			$this->tpl->parseCurrentBlock();
		}
		return true;
	}


	function checkUserDefinedRequiredFields()
	{
		include_once './Services/User/classes/class.ilUserDefinedFields.php';
		$user_defined_fields =& ilUserDefinedFields::_getInstance();

		foreach($user_defined_fields->getRegistrationDefinitions() as $field_id => $definition)
		{
			if($definition['required'] and !strlen($_POST['udf'][$field_id]))
			{
				return false;
			}
		}
		return true;
	}


	function saveForm()
	{
		global $ilias, $lng, $rbacadmin, $ilDB, $ilErr, $ilSetting;
		
		require_once 'Services/User/classes/class.ilObjUser.php';

		//load ILIAS settings
		$settings = $ilias->getAllSettings();

		//check, whether user-agreement has been accepted
		if ($_POST["status"] != "accepted")
		{
			ilUtil::sendInfo($lng->txt("force_accept_usr_agreement"),true);
			$this->displayForm();
			return false;
		}
		
		$data = $this->getRegistrationFieldsArray();
		$require_keys = array();
		$this->profile_incomplete = false;
		foreach($data['fields'] as $key => $val)
		{
			if(in_array($key, array('login', 'passwd', 'passwd2')))
			{
				$require_keys[] = $key;
				continue;
			}
			
			if((int)$settings['require_'.$key])
			{
				#if((int)$settings['usr_settings_visib_reg_'.$key])
				#{
					$require_keys[] = $key;
				#}
				#else
				#{
				#	$this->profile_incomplete = true;
				#}
			}
		}

		// email address is required if password generation is enabled or registration type = link confirmation
		if(($this->registration_settings->passwordGenerationEnabled() ||
		   $this->registration_settings->getRegistrationType() == IL_REG_ACTIVATION ||
           $this->registration_settings->getRegistrationType() == IL_REG_APPROVE ) &&
		   !in_array('email', $require_keys))
		{
			$require_keys[] = 'email';
		}

		foreach($require_keys as $key => $val)
		{
			if(empty($_POST['user'][$val]))
 			{
				ilUtil::sendFailure($lng->txt('fill_out_all_required_fields') . ': ' . $lng->txt($val), true);
				$this->displayForm();
				return false;
			}

			if($val == 'email')
			{
				// validate email
				if(!ilUtil::is_email($_POST['user']['email']))
 				{
					ilUtil::sendFailure($lng->txt('email_not_valid'), true);
					$this->displayForm();
					return false;
 				}
			}
		}

		if(!$this->checkUserDefinedRequiredFields())
		{
			ilUtil::sendFailure($lng->txt("fill_out_all_required_fields"),true);
			$this->displayForm();
			return false;
		}

		// validate username
		if (!ilUtil::isLogin($_POST["user"]["login"]))
		{
			ilUtil::sendFailure($lng->txt("login_invalid"),true);
			$this->displayForm();
			return false;
		}
	
		// check loginname
		if (ilObjUser::_loginExists($_POST["user"]["login"]))
		{
			ilUtil::sendFailure($lng->txt("login_exists"), true);
			$this->displayForm();
			return false;
		}

		//check if loginname exists in history		
		if((int)$ilSetting->get('allow_change_loginname') &&
		   (int)$ilSetting->get('prevent_reuse_of_loginnames') &&
		   ilObjUser::_doesLoginnameExistInHistory($_POST['user']['login']))
		{
			ilUtil::sendFailure($lng->txt('login_exists'), true);
			$this->displayForm();
			return false;	
		}

		if (!$this->registration_settings->passwordGenerationEnabled())
		{
			// check passwords
			if ($_POST["user"]["passwd"] != $_POST["user"]["passwd2"])
			{
				ilUtil::sendFailure($lng->txt("passwd_not_match"),true);
				$this->displayForm();
				return false;
			}

			// validate password
			if (!ilUtil::isPassword($_POST["user"]["passwd"],$custom_error))
			{
				if($custom_error != '') ilUtil::sendFailure($custom_error,true);
				else ilUtil::sendFailure($lng->txt("passwd_invalid"),true);

				$this->displayForm();
				return false;
			}
		}
		else
		{
			$passwd = ilUtil::generatePasswords(1);
			$_POST["user"]["passwd"] = $passwd[0];
		}
		// The password type is not passed in the post data. Therefore we
		// append it here manually.		
		$_POST["user"]["passwd_type"] = IL_PASSWD_PLAIN;

		// Do some Radius checks
		$this->__validateRole();

		// TODO: check if login or passwd already exists
		// TODO: check length of login and passwd

		// checks passed. save user

		$this->userObj = new ilObjUser();
		$this->userObj->assignData($_POST["user"]);
		$this->userObj->setTitle($this->userObj->getFullname());
		$this->userObj->setDescription($this->userObj->getEmail());
		
		if($this->profile_incomplete)
			$this->userObj->setProfileIncomplete(true);

		// Time limit
		$this->userObj->setTimeLimitOwner(7);

		if ($this->registration_settings->getAccessLimitation())
		{
			include_once 'Services/Registration/classes/class.ilRegistrationRoleAccessLimitations.php';

			$access_limitations_obj = new ilRegistrationRoleAccessLimitations();

			if ($this->registration_settings->roleSelectionEnabled())
			{
				$default_role = $_POST['user']['default_role'];
			}
			else
			{
				// Assign by email
				include_once 'Services/Registration/classes/class.ilRegistrationEmailRoleAssignments.php';

				$registration_role_assignments = new ilRegistrationRoleAssignments();
				$default_role = $registration_role_assignments->getRoleByEmail($this->userObj->getEmail());
			}

			$access_limit_mode = $access_limitations_obj->getMode($default_role);

			if ($access_limit_mode == 'absolute')
			{
				$access_limit = $access_limitations_obj->getAbsolute($default_role);
				$this->userObj->setTimeLimitUnlimited(0);
				$this->userObj->setTimeLimitUntil($access_limit);
			}
			elseif ($access_limit_mode == 'relative')
			{
				$rel_d = (int) $access_limitations_obj->getRelative($default_role,'d');
				$rel_m = (int) $access_limitations_obj->getRelative($default_role,'m');
				$rel_y = (int) $access_limitations_obj->getRelative($default_role,'y');

				$access_limit = $rel_d * 86400 + $rel_m * 2592000 + $rel_y * 31536000 + time();
				$this->userObj->setTimeLimitUnlimited(0);
				$this->userObj->setTimeLimitUntil($access_limit);
			}
			else
			{
				$this->userObj->setTimeLimitUnlimited(1);
				$this->userObj->setTimeLimitUntil(time());
			}
		}
		else
		{
			$this->userObj->setTimeLimitUnlimited(1);
			$this->userObj->setTimeLimitUntil(time());
		}

		$this->userObj->setTimeLimitFrom(time());

		$this->userObj->setUserDefinedData($_POST['udf']);
		$this->userObj->create();

		if($this->registration_settings->getRegistrationType() == IL_REG_DIRECT)
		{
			$this->userObj->setActive(1);
		}
		else if($this->registration_settings->getRegistrationType() == IL_REG_ACTIVATION)
		{
			$this->userObj->setActive(0,0);						 
		}
		else
		{
			$this->userObj->setActive(0,0);
		}

		$this->userObj->updateOwner();


		// set a timestamp for last_password_change
		// this ts is needed by the ACCOUNT_SECURITY_MODE_CUSTOMIZED
		// in ilSecuritySettings
		$this->userObj->setLastPasswordChangeTS( time() );		
		
		//insert user data in table user_data
		$this->userObj->saveAsNew();

		// store acceptance of user agreement
		$this->userObj->writeAccepted();

		// setup user preferences
		$this->userObj->setLanguage($_POST["user"]["language"]);
		$hits_per_page = $ilias->getSetting("hits_per_page");
		if ($hits_per_page < 10)
		{
			$hits_per_page = 10;
		}
		$this->userObj->setPref("hits_per_page", $ilias->getSetting("hits_per_page"));
		$show_online = $ilias->getSetting("show_users_online");
		if ($show_online == "")
		{
			$show_online = "y";
		}
		$this->userObj->setPref("show_users_online", $show_online);
		$this->userObj->writePrefs();

		// Assign role (depends on settings in administration)
		$this->__assignRole();

		// Distribute mails
		$this->__distributeMails();

		$this->login();
		return true;
	}


	function __validateRole()
	{
		global $ilDB,$ilias,$ilErr,$lng;

		// validate role
		include_once("./Services/AccessControl/classes/class.ilObjRole.php");
		if ($this->registration_settings->roleSelectionEnabled() and
			!ilObjRole::_lookupAllowRegister($_POST["user"]["default_role"]))
		{
			$ilias->raiseError("Invalid role selection in registration: ".
							   ilObject::_lookupTitle($_POST["user"]["default_role"])." [".$_POST["user"]["default_role"]."]".
							   ", IP: ".$_SERVER["REMOTE_ADDR"],$ilias->error_obj->FATAL);
		}
		return true;
	}

	function __assignRole()
	{
		global $rbacadmin;

		// Assign chosen role
		if($this->registration_settings->roleSelectionEnabled())
		{
			return $rbacadmin->assignUser((int) $_POST['user']['default_role'],
										  $this->userObj->getId(),true);
		}

		// Assign by email
		include_once 'Services/Registration/classes/class.ilRegistrationEmailRoleAssignments.php';

		$registration_role_assignments = new ilRegistrationRoleAssignments();

		return $rbacadmin->assignUser((int) $registration_role_assignments->getRoleByEmail($this->userObj->getEmail()),
									  $this->userObj->getId(),
									  true);
	}

	function __showRoleSelection()
	{
		if(!$this->registration_settings->roleSelectionEnabled())
		{
			return true;
		}

		// TODO put query in a function
		include_once("./Services/AccessControl/classes/class.ilObjRole.php");
		$reg_roles = ilObjRole::_lookupRegisterAllowed();

		$rol = array();
		foreach ($reg_roles as $role)
		{
			$rol[$role["id"]] = $role["title"];
		}

		$this->tpl->setCurrentBlock("role");
		$this->tpl->setVariable("TXT_DEFAULT_ROLE",$this->lng->txt('default_role'));
		$this->tpl->setVariable("DEFAULT_ROLE",ilUtil::formSelect($_POST["user"]["default_role"],
																  "user[default_role]",
																  $rol,false,true));
		$this->tpl->parseCurrentBlock();

		return true;
	}

	function __distributeMails()
	{
		global $ilias;

		include_once './Services/Language/classes/class.ilLanguage.php';
		include_once './Services/User/classes/class.ilObjUser.php';
        include_once "Services/Mail/classes/class.ilFormatMail.php";

		include_once './Services/Registration/classes/class.ilRegistrationMailNotification.php';

		$settings = $ilias->getAllSettings();

		// Always send mail to approvers
		
		if($this->registration_settings->getRegistrationType() == IL_REG_APPROVE)
		{
			$mail = new ilRegistrationMailNotification();
			$mail->setType(ilRegistrationMailNotification::TYPE_NOTIFICATION_CONFIRMATION);
			$mail->setRecipients($this->registration_settings->getApproveRecipients());
			$mail->setAdditionalInformation(array('usr' => $this->userObj));
			$mail->send();
		}
		else
		{
			$mail = new ilRegistrationMailNotification();
			$mail->setType(ilRegistrationMailNotification::TYPE_NOTIFICATION_APPROVERS);
			$mail->setRecipients($this->registration_settings->getApproveRecipients());
			$mail->setAdditionalInformation(array('usr' => $this->userObj));
			$mail->send();
			
		}		
		// Send mail to new user
		
		// Registration with confirmation link ist enabled		
		if($this->registration_settings->getRegistrationType() == IL_REG_ACTIVATION)
		{			
			include_once 'Services/Mail/classes/class.ilMail.php';
			$mail_obj = new ilMail(ANONYMOUS_USER_ID);			
		
			// mail subject
			$subject = $this->lng->txt("reg_mail_subject_confirmation");

			// mail body			
			$hashcode = ilObjUser::_generateRegistrationHash($this->userObj->getId());
			$body = $this->lng->txt("reg_mail_body_salutation")." ".$this->userObj->getFullname().",\n\n";
			$body .= $this->lng->txt('reg_mail_body_confirmation')."\n".
            	ILIAS_HTTP_PATH.'/confirmReg.php?client_id='.CLIENT_ID."&rh=".$hashcode."\n\n";
            	
            $body .= sprintf($this->lng->txt('reg_mail_body_2_confirmation'), 
            	ilFormat::_secondsToString($this->registration_settings->getRegistrationHashLifetime()))."\n\n";
	
			$body .= $this->lng->txt('reg_mail_body_3_confirmation');			
            
			$mail_obj->enableSoap(false);
			$mail_obj->appendInstallationSignature(true);
			$mail_obj->sendMail($this->userObj->getEmail(), '', '',
				$subject,
				$body,
				array(), array('normal'));
		}
		else
		{
			// try individual account mail in user administration
			include_once("Services/Mail/classes/class.ilAccountMail.php");
			include_once './Services/User/classes/class.ilObjUserFolder.php';
			$amail = ilObjUserFolder::_lookupNewAccountMail($GLOBALS["lng"]->getDefaultLanguage());
			if (trim($amail["body"]) != "" && trim($amail["subject"]) != "")
			{				
	            $acc_mail = new ilAccountMail();
	            $acc_mail->setUser($this->userObj);
	            if ($this->registration_settings->passwordGenerationEnabled())
	            {
	                $acc_mail->setUserPassword($_POST["user"]["passwd"]);
	            }
	            $acc_mail->send();
			}
			else	// do default mail
			{
				include_once "Services/Mail/classes/class.ilMimeMail.php";
	
				$mmail = new ilMimeMail();
				$mmail->autoCheck(false);
				$mmail->From($settings["admin_email"]);
				$mmail->To($this->userObj->getEmail());
	
				// mail subject
				$subject = $this->lng->txt("reg_mail_subject");
	
				// mail body
				$body = $this->lng->txt("reg_mail_body_salutation")." ".$this->userObj->getFullname().",\n\n".
					$this->lng->txt("reg_mail_body_text1")."\n\n".
					$this->lng->txt("reg_mail_body_text2")."\n".
					ILIAS_HTTP_PATH."/login.php?client_id=".$ilias->client_id."\n";			
				$body .= $this->lng->txt("login").": ".$this->userObj->getLogin()."\n";
	
				if ($this->registration_settings->passwordGenerationEnabled())
				{
					$body.= $this->lng->txt("passwd").": ".$_POST["user"]["passwd"]."\n";
				}
				$body.= "\n";
	
				// Info about necessary approvement
				if($this->registration_settings->getRegistrationType() == IL_REG_APPROVE)
				{
					$body .= ($this->lng->txt('reg_mail_body_pwd_generation')."\n\n");
				}			
				
				$body .= ($this->lng->txt("reg_mail_body_text3")."\n\r");
				$body .= $this->userObj->getProfileAsString($this->lng);
				$mmail->Subject($subject);
				$mmail->Body($body);
				$mmail->Send();
			}
		}
	}	
}
?>
