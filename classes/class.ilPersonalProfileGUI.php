<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
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
* GUI class for personal profiel
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilPersonalProfileGUI:
*
* @package content
*/
class ilPersonalProfileGUI
{
    var $tpl;
    var $lng;
    var $ilias;
	var $ctrl;

	var $user_defined_fields = null;


	/**
	* constructor
	*/
    function ilPersonalProfileGUI()
    {
        global $ilias, $tpl, $lng, $rbacsystem, $ilCtrl;

		include_once './classes/class.ilUserDefinedFields.php';

		$this->user_defined_fields = new ilUserDefinedFields();

        $this->tpl =& $tpl;
        $this->lng =& $lng;
        $this->ilias =& $ilias;
		$this->ctrl =& $ilCtrl;
		$this->settings = $ilias->getAllSettings();
		$this->upload_error = "";
		$this->password_error = "";
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		$next_class = $this->ctrl->getNextClass();

		switch($next_class)
		{				
			default:
				//$this->setTabs();
				$cmd = $this->ctrl->getCmd("showProfile");
				$this->$cmd();
				break;
		}
		return true;
	}



	/**
	* Returns TRUE if working with the given
	* user setting is allowed, FALSE otherwise
	*/
	function workWithUserSetting($setting)
	{
		$result = TRUE;
		if ($this->settings["usr_settings_hide_".$setting] == 1)
		{
			$result = FALSE;
		}
		if ($this->settings["usr_settings_disable_".$setting] == 1)
		{
			$result = FALSE;
		}
		return $result;
	}

	/**
	* Returns TRUE if user setting is
	* visible, FALSE otherwise
	*/
	function userSettingVisible($setting)
	{
		$result = TRUE;
		if ($this->settings["usr_settings_hide_".$setting] == 1)
		{
			$result = FALSE;
		}
		return $result;
	}

	/**
	* Returns TRUE if user setting is
	* enabled, FALSE otherwise
	*/
	function userSettingEnabled($setting)
	{
		$result = TRUE;
		if ($this->settings["usr_settings_disable_".$setting] == 1)
		{
			$result = FALSE;
		}
		return $result;
	}

	/**
	* upload user image
	*
	* (original method by ratana ty)
	*/
	function uploadUserPicture()
	{
		global $ilUser;

		if ($this->workWithUserSetting("upload"))
		{

			if ($_FILES["userfile"]["size"] == 0)
			{
				$this->upload_error = $this->lng->txt("msg_no_file");
			}
			else
			{
				
				$webspace_dir = ilUtil::getWebspaceDir();
				$image_dir = $webspace_dir."/usr_images";
				$store_file = "usr_".$ilUser->getID()."."."jpg";
			
				// store filename
				$ilUser->setPref("profile_image", $store_file);
				$ilUser->update();
					
				// move uploaded file
				$uploaded_file = $image_dir."/upload_".$ilUser->getId();
				if (!ilUtil::moveUploadedFile($_FILES["userfile"]["tmp_name"], $_FILES["userfile"]["name"],
					$uploaded_file, false))
				{
					sendInfo($this->lng->txt("upload_error", true));
					$this->ctrl->redirect($this, "showProfile");
				}
				chmod($uploaded_file, 0770);
			
				// take quality 100 to avoid jpeg artefacts when uploading jpeg files
				// taking only frame [0] to avoid problems with animated gifs
				$show_file  = "$image_dir/usr_".$ilUser->getId().".jpg"; 
				$thumb_file = "$image_dir/usr_".$ilUser->getId()."_small.jpg";
				$xthumb_file = "$image_dir/usr_".$ilUser->getId()."_xsmall.jpg"; 
				$xxthumb_file = "$image_dir/usr_".$ilUser->getId()."_xxsmall.jpg";
				$uploaded_file = ilUtil::escapeShellArg($uploaded_file);
				$show_file = ilUtil::escapeShellArg($show_file);
				$thumb_file = ilUtil::escapeShellArg($thumb_file);
				$xthumb_file = ilUtil::escapeShellArg($xthumb_file);
				$xxthumb_file = ilUtil::escapeShellArg($xxthumb_file);
				
				system(ilUtil::getConvertCmd()." $uploaded_file" . "[0] -geometry 200x200 -quality 100 JPEG:$show_file");
				system(ilUtil::getConvertCmd()." $uploaded_file" . "[0] -geometry 100x100 -quality 100 JPEG:$thumb_file");
				system(ilUtil::getConvertCmd()." $uploaded_file" . "[0] -geometry 75x75 -quality 100 JPEG:$xthumb_file");
				system(ilUtil::getConvertCmd()." $uploaded_file" . "[0] -geometry 30x30 -quality 100 JPEG:$xxthumb_file");
			}
		}
		
		$this->saveProfile();
	}

	/**
	* remove user image
	*/
	function removeUserPicture()
	{
		global $ilUser;
	
		$webspace_dir = ilUtil::getWebspaceDir();
		$image_dir = $webspace_dir."/usr_images";
		$file = $image_dir."/usr_".$ilUser->getID()."."."jpg";
		$thumb_file = $image_dir."/usr_".$ilUser->getID()."_small.jpg";
		$xthumb_file = $image_dir."/usr_".$ilUser->getID()."_xsmall.jpg";
		$xxthumb_file = $image_dir."/usr_".$ilUser->getID()."_xxsmall.jpg";
		$upload_file = $image_dir."/upload_".$ilUser->getID();
	
		// remove user pref file name
		$ilUser->setPref("profile_image", "");
		$ilUser->update();
	
		if (@is_file($file))
		{
			unlink($file);
		}
		if (@is_file($thumb_file))
		{
			unlink($thumb_file);
		}
		if (@is_file($xthumb_file))
		{
			unlink($xthumb_file);
		}
		if (@is_file($xxthumb_file))
		{
			unlink($xxthumb_file);
		}
		if (@is_file($upload_file))
		{
			unlink($upload_file);
		}
	
		$this->saveProfile();
	}


	/**
	* change user password
	*/
	function changeUserPassword()
	{
		global $ilUser, $ilSetting;

		// do nothing if auth mode is not local database
		if ($ilUser->getAuthMode(true) != AUTH_LOCAL &&
			($ilUser->getAuthMode(true) != AUTH_CAS || !$ilSetting->get("cas_allow_local")) &&
			($ilUser->getAuthMode(true) != AUTH_SOAP || !$ilSetting->get("soap_auth_allow_local"))
			)
		{
			$this->password_error = $this->lng->txt("not_changeable_for_non_local_auth");
		}

		// select password from auto generated passwords
		if ($this->ilias->getSetting("passwd_auto_generate") == 1)
		{
			// check old password
			if (md5($_POST["current_password"]) != $ilUser->getPasswd())
			{
				$this->password_error = $this->lng->txt("passwd_wrong");
			}
			// validate transmitted password
			if (!ilUtil::isPassword($_POST["new_passwd"]))
			{
				$this->password_error = $this->lng->txt("passwd_not_selected");
			}

			if (empty($this->password_error))
			{
				sendInfo($this->lng->txt("saved_successfully"));
				$ilUser->updatePassword($_POST["current_password"], $_POST["new_passwd"], $_POST["new_passwd"]);
			}
		}
		else
		{
			// check old password
			if (md5($_POST["current_password"]) != $ilUser->getPasswd())
			{
				$this->password_error = $this->lng->txt("passwd_wrong");
			}
			// check new password
			else if ($_POST["desired_password"] != $_POST["retype_password"])
			{
				$this->password_error = $this->lng->txt("passwd_not_match");
			}
			// validate password
			else if (!ilUtil::isPassword($_POST["desired_password"]))
			{
				$this->password_error = $this->lng->txt("passwd_invalid");
			}
			else if ($_POST["current_password"] != "" and empty($this->password_error))
			{
				sendInfo($this->lng->txt("saved_successfully"));
				$ilUser->updatePassword($_POST["current_password"], $_POST["desired_password"], $_POST["retype_password"]);
			}
		}

		$this->saveProfile();
	}



	/**
	* save user profile data
	*/
	function saveProfile()
	{
		global $ilUser;
		
		//init checking var
		$form_valid = true;

		// testing by ratana ty:
		// if people check on check box it will
		// write some datata to table usr_pref
		// if check on Public Profile
		if (($_POST["chk_pub"])=="on")
		{
			$ilUser->setPref("public_profile","y");
		}
		else
		{
			$ilUser->setPref("public_profile","n");
		}

		// if check on Institute
		$val_array = array("institution", "department", "upload", "street",
			"zip", "city", "country", "phone_office", "phone_home", "phone_mobile",
			"fax", "email", "hobby", "matriculation");
	
		// set public profile preferences
		foreach($val_array as $key => $value)
		{
			if (($_POST["chk_".$value]) == "on")
			{
				$ilUser->setPref("public_".$value,"y");
			}
			else
			{
				$ilUser->setPref("public_".$value,"n");
			}
		}

		// check dynamically required fields
		foreach($this->settings as $key => $val)
		{
			if (substr($key,0,8) == "require_")
			{
				$require_keys[] = substr($key,8);
			}
		}

		foreach($require_keys as $key => $val)
		{
			// exclude required system and registration-only fields
			$system_fields = array("login", "default_role", "passwd", "passwd2");
			if (!in_array($val, $system_fields))
			{
				if ($this->workWithUserSetting($val))
				{
					if (isset($this->settings["require_" . $val]) && $this->settings["require_" . $val])
					{
						if (empty($_POST["usr_" . $val]))
						{
							sendInfo($this->lng->txt("fill_out_all_required_fields") . ": " . $this->lng->txt($val));
							$form_valid = false;
						}
					}
				}
			}
		}

		// Check user defined required fields
		if($form_valid and !$this->__checkUserDefinedRequiredFields())
		{
			sendInfo($this->lng->txt("fill_out_all_required_fields"));
			$form_valid = false;
		}

		// check email
		if ($this->workWithUserSetting("email"))
		{
			if (!ilUtil::is_email($_POST["usr_email"]) and !empty($_POST["usr_email"]) and $form_valid)
			{
				sendInfo($this->lng->txt("email_not_valid"));
				$form_valid = false;
			}
		}

		//update user data (not saving!)
		if ($this->workWithUserSetting("firstname"))
		{
			$ilUser->setFirstName(ilUtil::stripSlashes($_POST["usr_firstname"]));
		}
		if ($this->workWithUserSetting("lastname"))
		{
			$ilUser->setLastName(ilUtil::stripSlashes($_POST["usr_lastname"]));
		}
		if ($this->workWithUserSetting("gender"))
		{
			$ilUser->setGender($_POST["usr_gender"]);
		}
		if ($this->workWithUserSetting("title"))
		{
			$ilUser->setUTitle(ilUtil::stripSlashes($_POST["usr_title"]));
		}
		$ilUser->setFullname();
		if ($this->workWithUserSetting("institution"))
		{
			$ilUser->setInstitution(ilUtil::stripSlashes($_POST["usr_institution"]));
		}
		if ($this->workWithUserSetting("department"))
		{
			$ilUser->setDepartment(ilUtil::stripSlashes($_POST["usr_department"]));
		}
		if ($this->workWithUserSetting("street"))
		{
			$ilUser->setStreet(ilUtil::stripSlashes($_POST["usr_street"]));
		}
		if ($this->workWithUserSetting("zipcode"))
		{
			$ilUser->setZipcode(ilUtil::stripSlashes($_POST["usr_zipcode"]));
		}
		if ($this->workWithUserSetting("city"))
		{
			$ilUser->setCity(ilUtil::stripSlashes($_POST["usr_city"]));
		}
		if ($this->workWithUserSetting("country"))
		{
			$ilUser->setCountry(ilUtil::stripSlashes($_POST["usr_country"]));
		}
		if ($this->workWithUserSetting("phone_office"))
		{
			$ilUser->setPhoneOffice(ilUtil::stripSlashes($_POST["usr_phone_office"]));
		}
		if ($this->workWithUserSetting("phone_home"))
		{
			$ilUser->setPhoneHome(ilUtil::stripSlashes($_POST["usr_phone_home"]));
		}
		if ($this->workWithUserSetting("phone_mobile"))
		{
			$ilUser->setPhoneMobile(ilUtil::stripSlashes($_POST["usr_phone_mobile"]));
		}
		if ($this->workWithUserSetting("fax"))
		{
			$ilUser->setFax(ilUtil::stripSlashes($_POST["usr_fax"]));
		}
		if ($this->workWithUserSetting("email"))
		{
			$ilUser->setEmail(ilUtil::stripSlashes($_POST["usr_email"]));
		}
		if ($this->workWithUserSetting("hobby"))
		{
			$ilUser->setHobby(ilUtil::stripSlashes($_POST["usr_hobby"]));
		}
		if ($this->workWithUserSetting("referral_comment"))
		{
			$ilUser->setComment(ilUtil::stripSlashes($_POST["usr_referral_comment"]));
		}
		if ($this->workWithUserSetting("matriculation"))
		{
			$ilUser->setMatriculation(ilUtil::stripSlashes($_POST["usr_matriculation"]));
		}
		// Set user defined data
		$ilUser->setUserDefinedData($_POST['udf']);

		// everthing's ok. save form data
		if ($form_valid)
		{
			// init reload var. page should only be reloaded if skin or style were changed
			$reload = false;
	
			if ($this->workWithUserSetting("skin_style"))
			{
				//set user skin and style
				if ($_POST["usr_skin_style"] != "")
				{
					$sknst = explode(":", $_POST["usr_skin_style"]);
		
					if ($ilUser->getPref("style") != $sknst[1] ||
						$ilUser->getPref("skin") != $sknst[0])
					{
						$ilUser->setPref("skin", $sknst[0]);
						$ilUser->setPref("style", $sknst[1]);
						$reload = true;
					}
				}
			}
	
			if ($this->workWithUserSetting("language"))
			{	
				// reload page if language was changed
				//if ($_POST["usr_language"] != "" and $_POST["usr_language"] != $_SESSION['lang'])
				// (this didn't work as expected, alex)
				if ($_POST["usr_language"] != $ilUser->getLanguage())
				{
					$reload = true;
				}

				// set user language
				$ilUser->setLanguage($_POST["usr_language"]);

			}
			if ($this->workWithUserSetting("hits_per_page"))
			{
				// set user hits per page
				if ($_POST["hits_per_page"] != "")
				{
					$ilUser->setPref("hits_per_page",$_POST["hits_per_page"]);
				}
			}
	
			// set show users online
			if ($this->workWithUserSetting("show_users_online"))
			{
				$ilUser->setPref("show_users_online", $_POST["show_users_online"]);
			}
			
			// save notes setting
			if ($_POST["chk_notes"] != "")
			{
				$ilUser->setPref("show_notes","y");
			}
			else
			{
				$ilUser->setPref("show_notes","n");
			}

			// profile ok
			$ilUser->setProfileIncomplete(false);
			
			// save user data & object_data
			$ilUser->setTitle($ilUser->getFullname());
			$ilUser->setDescription($ilUser->getEmail());
			$ilUser->update();
	
			// reload page only if skin or style were changed
			// feedback
			if (!empty($this->password_error))
			{
				sendInfo($this->password_error,true);
			}
			elseif (!empty($this->upload_error))
			{
				sendInfo($this->upload_error,true);
			}
			else if ($reload)
			{
				// feedback
				sendInfo($this->lng->txt("saved_successfully"),true);
				$this->tpl->setVariable("RELOAD","<script language=\"Javascript\">\ntop.location.href = \"./start.php\";\n</script>\n");
			}
			else
			{
				sendInfo($this->lng->txt("saved_successfully"),true);
			}
		}
		
		$this->showProfile();
	}

	/**
	* show profile form
	*/
	function showProfile()
	{
		global $ilUser, $styleDefinition, $rbacreview, $ilias, $lng, $ilSetting;
		
		$settings = $ilias->getAllSettings();
		
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.usr_profile.html");
		
		// set locator
/*
		$this->tpl->setVariable("TXT_LOCATOR", $this->lng->txt("locator"));
		$this->tpl->touchBlock("locator_separator");
		$this->tpl->touchBlock("locator_item");
		//$this->tpl->setCurrentBlock("locator_item");
		//$this->tpl->setVariable("ITEM", $this->lng->txt("personal_desktop"));
		//$this->tpl->setVariable("LINK_ITEM",
		//	$this->ctrl->getLinkTargetByClass("ilpersonaldesktopgui"));
		//$this->tpl->parseCurrentBlock();
		
		$this->tpl->setCurrentBlock("locator_item");
		$this->tpl->setVariable("ITEM", $this->lng->txt("personal_profile"));
		$this->tpl->setVariable("LINK_ITEM",
			$this->ctrl->getLinkTargetByClass("ilpersonalprofilegui", "showProfile"));
		$this->tpl->parseCurrentBlock();
*/
		
		// catch feedback message
		if ($ilUser->getProfileIncomplete())
		{
			sendInfo($lng->txt("profile_incomplete"));
		}
		else
		{
			sendInfo();
		}
		
		
		// display infopanel if something happened
		infoPanel();

		if ($this->userSettingVisible("language"))
		{
			//get all languages
			$languages = $this->lng->getInstalledLanguages();
			
			// preselect previous chosen language otherwise saved language
			$selected_lang = (isset($_POST["usr_language"]))
				? $_POST["usr_language"]
				: $ilUser->getLanguage();
			
			//go through languages
			foreach($languages as $lang_key)
			{
				$this->tpl->setCurrentBlock("sel_lang");
				//$tpl->setVariable("LANG", $lng->txt("lang_".$lang_key));
				$this->tpl->setVariable("LANG", ilLanguage::_lookupEntry($lang_key,"meta", "meta_l_".$lang_key));
				$this->tpl->setVariable("LANGSHORT", $lang_key);
			
				if ($selected_lang == $lang_key)
				{
					$this->tpl->setVariable("SELECTED_LANG", "selected=\"selected\"");
				}
			
				$this->tpl->parseCurrentBlock();
			}
		}
		
		// get all templates
		include_once("classes/class.ilObjStyleSettings.php");
		$templates = $styleDefinition->getAllTemplates();
		
		if ($this->userSettingVisible("skin_style"))
		{
			foreach($templates as $template)
			{
				// get styles information of template
				$styleDef =& new ilStyleDefinition($template["id"]);
				$styleDef->startParsing();
				$styles = $styleDef->getStyles();
			
				foreach($styles as $style)
				{
					if (!ilObjStyleSettings::_lookupActivatedStyle($template["id"],$style["id"]))
					{
						continue;
					}
		
					$this->tpl->setCurrentBlock("selectskin");
			
					if ($ilUser->skin == $template["id"] &&
						$ilUser->prefs["style"] == $style["id"])
					{
						$this->tpl->setVariable("SKINSELECTED", "selected=\"selected\"");
					}
			
					$this->tpl->setVariable("SKINVALUE", $template["id"].":".$style["id"]);
					$this->tpl->setVariable("SKINOPTION", $styleDef->getTemplateName()." / ".$style["name"]);
					$this->tpl->parseCurrentBlock();
				}
			}
		}
		
		// hits per page
		if ($this->userSettingVisible("hits_per_page"))
		{
			$hits_options = array(2,10,15,20,30,40,50,100,9999);
		
			foreach($hits_options as $hits_option)
			{
				$this->tpl->setCurrentBlock("selecthits");
		
				if ($ilUser->prefs["hits_per_page"] == $hits_option)
				{
					$this->tpl->setVariable("HITSSELECTED", "selected=\"selected\"");
				}
		
				$this->tpl->setVariable("HITSVALUE", $hits_option);
		
				if ($hits_option == 9999)
				{
					$hits_option = $this->lng->txt("no_limit");
				}
		
				$this->tpl->setVariable("HITSOPTION", $hits_option);
				$this->tpl->parseCurrentBlock();
			}
		}
		
		// Users Online
		if ($this->userSettingVisible("show_users_online"))
		{
			$users_online_options = array("y","associated","n");
			$selected_option = $ilUser->prefs["show_users_online"];
			foreach($users_online_options as $an_option)
			{
				$this->tpl->setCurrentBlock("select_users_online");
		
				if ($selected_option == $an_option)
				{
					$this->tpl->setVariable("USERS_ONLINE_SELECTED", "selected=\"selected\"");
				}
		
				$this->tpl->setVariable("USERS_ONLINE_VALUE", $an_option);
		
				$this->tpl->setVariable("USERS_ONLINE_OPTION", $this->lng->txt("users_online_show_".$an_option));
				$this->tpl->parseCurrentBlock();
			}
		}
		
		// show notes
		if ($ilUser->prefs["show_notes"] != "n")
		{
			$this->tpl->setVariable("CHK_NOTES", "checked");
		}
		$this->tpl->setVariable("TXT_SHOW_NOTES", $this->lng->txt("show_notes_on_pd"));
		
		if (($ilUser->getAuthMode(true) == AUTH_LOCAL ||
			($ilUser->getAuthMode(true) == AUTH_CAS && $ilSetting->get("cas_allow_local")) ||
			($ilUser->getAuthMode(true) == AUTH_SOAP && $ilSetting->get("soap_auth_allow_local"))
			)
			&&
			$this->userSettingVisible('password'))
		{
			if($this->ilias->getSetting('usr_settings_disable_password'))
			{
				$this->tpl->setCurrentBlock("disabled_password");
				$this->tpl->setVariable("TXT_DISABLED_PASSWORD", $this->lng->txt("chg_password"));
				$this->tpl->setVariable("TXT_DISABLED_CURRENT_PASSWORD", $this->lng->txt("current_password"));
				$this->tpl->parseCurrentBlock();
			}
			elseif ($settings["passwd_auto_generate"] == 1)
			{
				$passwd_list = ilUtil::generatePasswords(5);
			 
				foreach ($passwd_list as $passwd)
				{
					$passwd_choice .= ilUtil::formRadioButton(0,"new_passwd",$passwd)." ".$passwd."<br/>";
				}
		
				$this->tpl->setCurrentBlock("select_password");
				$this->tpl->setVariable("TXT_CHANGE_PASSWORD", $this->lng->txt("chg_password"));
				$this->tpl->setVariable("TXT_CURRENT_PASSWORD", $this->lng->txt("current_password"));
				$this->tpl->setVariable("TXT_SELECT_PASSWORD", $this->lng->txt("select_password"));
				$this->tpl->setVariable("PASSWORD_CHOICE", $passwd_choice);
				$this->tpl->setVariable("TXT_NEW_LIST_PASSWORD", $this->lng->txt("new_list_password"));
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->setCurrentBlock("change_password");
				$this->tpl->setVariable("TXT_CHANGE_PASSWORD", $this->lng->txt("chg_password"));
				$this->tpl->setVariable("TXT_CURRENT_PW", $this->lng->txt("current_password"));
				$this->tpl->setVariable("TXT_DESIRED_PW", $this->lng->txt("desired_password"));
				$this->tpl->setVariable("TXT_RETYPE_PW", $this->lng->txt("retype_password"));
				$this->tpl->setVariable("CHANGE_PASSWORD", $this->lng->txt("chg_password"));
				$this->tpl->parseCurrentBlock();
			}
		}
		
		$this->tpl->setTitleIcon(ilUtil::getImagePath("icon_pd_b.gif"),
			$this->lng->txt("personal_desktop"));

		$this->tpl->setCurrentBlock("content");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$this->tpl->setVariable("HEADER", $this->lng->txt("personal_desktop"));
		$this->tpl->setVariable("TXT_OF",strtolower($this->lng->txt("of")));
		$this->tpl->setVariable("USR_FULLNAME",$ilUser->getFullname());
		
		$this->tpl->setVariable("TXT_USR_DATA", $this->lng->txt("userdata"));
		$this->tpl->setVariable("TXT_NICKNAME", $this->lng->txt("username"));
		$this->tpl->setVariable("TXT_PUBLIC_PROFILE", $this->lng->txt("public_profile"));
		
		$data = array();
		$data["fields"] = array();
		$data["fields"]["gender"] = "";
		$data["fields"]["firstname"] = "";
		$data["fields"]["lastname"] = "";
		$data["fields"]["title"] = "";
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
		$data["fields"]["create_date"] = "";
		$data["fields"]["approve_date"] = "";
		$data["fields"]["active"] = "";
		
		$data["fields"]["default_role"] = $role;
		// fill presets
		foreach($data["fields"] as $key => $val)
		{
			// note: general "title" is not as "title" for a person
			if ($key != "title")
			{
				$str = $this->lng->txt($key);
			}
			else
			{
				$str = $this->lng->txt("person_title");
			}
		
			// check to see if dynamically required
			if (isset($this->settings["require_" . $key]) && $this->settings["require_" . $key])
			{
						$str = $str . '<span class="asterisk">*</span>';
			}
		
			if ($this->userSettingVisible("$key"))
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $str);
			}
		}
		
		if ($this->userSettingVisible("gender"))
		{
			$this->tpl->setVariable("TXT_GENDER_F",$this->lng->txt("gender_f"));
			$this->tpl->setVariable("TXT_GENDER_M",$this->lng->txt("gender_m"));
		}
		
		
		if ($this->userSettingVisible("upload"))
		{
			$this->tpl->setVariable("TXT_UPLOAD",$this->lng->txt("personal_picture"));
			$webspace_dir = ilUtil::getWebspaceDir("output");
			$full_img = $ilUser->getPref("profile_image");
			$last_dot = strrpos($full_img, ".");
			$small_img = substr($full_img, 0, $last_dot).
					"_small".substr($full_img, $last_dot, strlen($full_img) - $last_dot);
			$image_file = $webspace_dir."/usr_images/".$small_img;
			
			if (@is_file($image_file))
			{
				$this->tpl->setCurrentBlock("pers_image");
				$this->tpl->setVariable("IMG_PERSONAL", $image_file."?dummy=".rand(1,99999));
				$this->tpl->setVariable("ALT_IMG_PERSONAL",$this->lng->txt("personal_picture"));
				$this->tpl->parseCurrentBlock();
				if ($this->userSettingEnabled("upload"))
				{
					$this->tpl->setCurrentBlock("remove_pic");
					$this->tpl->setVariable("TXT_REMOVE_PIC", $this->lng->txt("remove_personal_picture"));
				}
				$this->tpl->parseCurrentBlock();
				$this->tpl->setCurrentBlock("content");
			}
			
			if ($this->userSettingEnabled("upload"))
			{
				$this->tpl->setCurrentBlock("upload_pic");
				$this->tpl->setVariable("UPLOAD", $this->lng->txt("upload"));
			}
			$this->tpl->setVariable("TXT_FILE", $this->lng->txt("userfile"));
			$this->tpl->setVariable("USER_FILE", $this->lng->txt("user_file"));
		}
		
		// ilinc upload pic
		if ($this->userSettingVisible("upload") and $this->ilias->getSetting("ilinc_active"))
		{
			include_once ('ilinc/classes/class.ilObjiLincUser.php');
			$ilinc_user = new ilObjiLincUser($ilUser);
				
			if ($ilinc_user->id)
			{
				include_once ('ilinc/classes/class.ilnetucateXMLAPI.php');
				$ilincAPI = new ilnetucateXMLAPI();
				
				$ilincAPI->uploadPicture($ilinc_user);
				$response = $ilincAPI->sendRequest("uploadPicture");
			
				// return URL to user's personal page
				$url = trim($response->data['url']['cdata']);
		
				$this->tpl->setCurrentBlock("ilinc_upload_pic");
				$this->tpl->setVariable("TXT_ILINC_UPLOAD", $this->lng->txt("ilinc_upload_pic_text"));
				$this->tpl->setVariable("ILINC_UPLOAD_LINK", $url);
				$this->tpl->setVariable("ILINC_UPLOAD_LINKTXT", $this->lng->txt("ilinc_upload_pic_linktext"));
				$this->tpl->parseCurrentBlock();
			}
		}
		
		
		if ($this->userSettingVisible("language"))
		{
			$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		}
		if ($this->userSettingVisible("show_users_online"))
		{
			$this->tpl->setVariable("TXT_SHOW_USERS_ONLINE", $this->lng->txt("show_users_online"));
		}
		if ($this->userSettingVisible("skin_style"))
		{
			$this->tpl->setVariable("TXT_USR_SKIN_STYLE", $this->lng->txt("usr_skin_style"));
		}
		if ($this->userSettingVisible("hits_per_page"))
		{
			$this->tpl->setVariable("TXT_HITS_PER_PAGE", $this->lng->txt("usr_hits_per_page"));
		}
		if ($this->userSettingVisible("show_users_online"))
		{
			$this->tpl->setVariable("TXT_SHOW_USERS_ONLINE", $this->lng->txt("show_users_online"));
		}
		$this->tpl->setVariable("TXT_PERSONAL_DATA", $this->lng->txt("personal_data"));
		$this->tpl->setVariable("TXT_SYSTEM_INFO", $this->lng->txt("system_information"));
		$this->tpl->setVariable("TXT_CONTACT_DATA", $this->lng->txt("contact_data"));

		if($this->__showOtherInformations())
		{
			$this->tpl->setVariable("TXT_OTHER", $this->lng->txt("user_profile_other"));
		}
		$this->tpl->setVariable("TXT_SETTINGS", $this->lng->txt("settings"));
		
		//values
		$this->tpl->setVariable("NICKNAME", ilUtil::prepareFormOutput($ilUser->getLogin()));
		
		if ($this->userSettingVisible("firstname"))
		{
			$this->tpl->setVariable("FIRSTNAME", ilUtil::prepareFormOutput($ilUser->getFirstname()));
		}
		if ($this->userSettingVisible("lastname"))
		{
			$this->tpl->setVariable("LASTNAME", ilUtil::prepareFormOutput($ilUser->getLastname()));
		}
		
		if ($this->userSettingVisible("gender"))
		{
			// gender selection
			$gender = strtoupper($ilUser->getGender());
			
			if (!empty($gender))
			{
				$this->tpl->setVariable("BTN_GENDER_".$gender,"checked=\"checked\"");
			}
		}
		
		$this->tpl->setVariable("CREATE_DATE", $ilUser->getCreateDate());
		$this->tpl->setVariable("APPROVE_DATE", $ilUser->getApproveDate());
		
		if ($ilUser->getActive())
		{
			$this->tpl->setVariable("ACTIVE", "checked=\"checked\"");
		}
		
		if ($this->userSettingVisible("title"))
		{
			$this->tpl->setVariable("TITLE", ilUtil::prepareFormOutput($ilUser->getUTitle()));
		}
		if ($this->userSettingVisible("institution"))
		{
			$this->tpl->setVariable("INSTITUTION", ilUtil::prepareFormOutput($ilUser->getInstitution()));
		}
		if ($this->userSettingVisible("department"))
		{
			$this->tpl->setVariable("DEPARTMENT", ilUtil::prepareFormOutput($ilUser->getDepartment()));
		}
		if ($this->userSettingVisible("street"))
		{
			$this->tpl->setVariable("STREET", ilUtil::prepareFormOutput($ilUser->getStreet()));
		}
		if ($this->userSettingVisible("zipcode"))
		{
			$this->tpl->setVariable("ZIPCODE", ilUtil::prepareFormOutput($ilUser->getZipcode()));
		}
		if ($this->userSettingVisible("city"))
		{
			$this->tpl->setVariable("CITY", ilUtil::prepareFormOutput($ilUser->getCity()));
		}
		if ($this->userSettingVisible("country"))
		{
			$this->tpl->setVariable("COUNTRY", ilUtil::prepareFormOutput($ilUser->getCountry()));
		}
		if ($this->userSettingVisible("phone_office"))
		{
			$this->tpl->setVariable("PHONE_OFFICE", ilUtil::prepareFormOutput($ilUser->getPhoneOffice()));
		}
		if ($this->userSettingVisible("phone_home"))
		{
			$this->tpl->setVariable("PHONE_HOME", ilUtil::prepareFormOutput($ilUser->getPhoneHome()));
		}
		if ($this->userSettingVisible("phone_mobile"))
		{
			$this->tpl->setVariable("PHONE_MOBILE", ilUtil::prepareFormOutput($ilUser->getPhoneMobile()));
		}
		if ($this->userSettingVisible("fax"))
		{
			$this->tpl->setVariable("FAX", ilUtil::prepareFormOutput($ilUser->getFax()));
		}
		if ($this->userSettingVisible("email"))
		{
			$this->tpl->setVariable("EMAIL", ilUtil::prepareFormOutput($ilUser->getEmail()));
		}
		if ($this->userSettingVisible("hobby"))
		{
			$this->tpl->setVariable("HOBBY", ilUtil::prepareFormOutput($ilUser->getHobby()));		// here
		}
		if ($this->userSettingVisible("referral_comment"))
		{
			$this->tpl->setVariable("REFERRAL_COMMENT", ilUtil::prepareFormOutput($ilUser->getComment()));
		}
		if ($this->userSettingVisible("matriculation"))
		{
			$this->tpl->setVariable("MATRICULATION", ilUtil::prepareFormOutput($ilUser->getMatriculation()));
		}
		
		// show user defined visible fields
		$this->__showUserDefinedFields();

		// get assigned global roles (default roles)
		$global_roles = $rbacreview->getGlobalRoles();
		
		foreach($global_roles as $role_id)
		{
			if (in_array($role_id, $_SESSION["RoleId"]))
			{
				$roleObj = $this->ilias->obj_factory->getInstanceByObjId($role_id);
				$role_names .= $roleObj->getTitle().", ";
				unset($roleObj);
			}
		}
		
		$this->tpl->setVariable("TXT_DEFAULT_ROLES", $this->lng->txt("default_roles"));
		$this->tpl->setVariable("DEFAULT_ROLES", substr($role_names,0,-2));
		
		$this->tpl->setVariable("TXT_REQUIRED_FIELDS", $this->lng->txt("required_field"));
		
		//button
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		
		// addeding by ratana ty
		if ($this->userSettingEnabled("upload"))
		{
			$this->tpl->setVariable("UPLOAD", $this->lng->txt("upload"));
		}

		// 
		if ($ilUser->prefs["public_profile"] == "y")
		{
			$this->tpl->setVariable("CHK_PUB","checked");
		}
		$val_array = array("institution", "department", "upload", "street",
			"zip", "city", "country", "phone_office", "phone_home", "phone_mobile",
			"fax", "email", "hobby", "matriculation", "show_users_online");
		foreach($val_array as $key => $value)
		{
			if ($this->userSettingVisible("$value"))
			{
				if ($ilUser->prefs["public_".$value] == "y")
				{
					$this->tpl->setVariable("CHK_".strtoupper($value), "checked");
				}
			}
		}
		// End of showing
		// Testing by ratana ty
		
		
		$profile_fields = array(
			"gender",
			"firstname",
			"lastname",
			"title",
			"upload",
			"institution",
			"department",
			"street",
			"city",
			"zipcode",
			"country",
			"phone_office",
			"phone_home",
			"phone_mobile",
			"fax",
			"email",
			"hobby",
			"matriculation",
			"referral_comment",
			"language",
			"skin_style",
			"hits_per_page",
			"show_users_online"
		);
		foreach ($profile_fields as $field)
		{
			if (!$this->ilias->getSetting("usr_settings_hide_" . $field))
			{
				if ($this->ilias->getSetting("usr_settings_disable_" . $field))
				{
					$this->tpl->setVariable("DISABLED_" . strtoupper($field), " disabled=\"disabled\"");
				}
			}
		}
		
		$this->tpl->parseCurrentBlock();
		$this->tpl->show();
	}


	function __showOtherInformations()
	{
		if($this->userSettingVisible("matriculation") or count($this->user_defined_fields->getVisibleDefinitions()))
		{
			return true;
		}
		return false;
	}

	function __showUserDefinedFields()
	{
		global $ilUser;

		$user_defined_data = $ilUser->getUserDefinedData();
		foreach($this->user_defined_fields->getVisibleDefinitions() as $field_id => $definition)
		{
			if($definition['field_type'] == UDF_TYPE_TEXT)
			{
				$this->tpl->setCurrentBlock("field_text");
				$this->tpl->setVariable("FIELD_NAME",'udf['.$definition['field_id'].']');
				$this->tpl->setVariable("FIELD_VALUE",ilUtil::prepareFormOutput($user_defined_data[$field_id]));
				if(!$definition['changeable'])
				{
					$this->tpl->setVariable("DISABLED_FIELD",'disabled=\"disabled\"');
				}
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->setCurrentBlock("field_select");
				$this->tpl->setVariable("SELECT_BOX",ilUtil::formSelect($user_defined_data[$field_id],
																		'udf['.$definition['field_id'].']',
																		$this->user_defined_fields->fieldValuesToSelectArray(
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

	function __checkUserDefinedRequiredFields()
	{
		foreach($this->user_defined_fields->getVisibleDefinitions() as $definition)
		{
			$field_id = $definition['field_id'];
			if($definition['required'] and !strlen($_POST['udf'][$field_id]))
			{
				return false;
			}
		}
		return true;
	}
		
}
?>
