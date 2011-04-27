<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * GUI class for personal profile
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 * @ilCtrl_Calls ilPersonalProfileGUI: ilPublicUserProfileGUI, ilPortfolioGUI
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

		include_once './Services/User/classes/class.ilUserDefinedFields.php';
		$this->user_defined_fields =& ilUserDefinedFields::_getInstance();

        $this->tpl =& $tpl;
        $this->lng =& $lng;
        $this->ilias =& $ilias;
		$this->ctrl =& $ilCtrl;
		$this->settings = $ilias->getAllSettings();
		$lng->loadLanguageModule("jsmath");
		$this->upload_error = "";
		$this->password_error = "";
		$lng->loadLanguageModule("user");
		// $ilCtrl->saveParameter($this, "user_page");
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilUser, $ilCtrl, $tpl, $ilTabs, $lng;
		
		$next_class = $this->ctrl->getNextClass();

		switch($next_class)
		{
			case "ilpublicuserprofilegui":
				include_once("./Services/User/classes/class.ilPublicUserProfileGUI.php");
				$_GET["user_id"] = $ilUser->getId();
				$pub_profile_gui = new ilPublicUserProfileGUI($_GET["user_id"]);
				$pub_profile_gui->setBackUrl($ilCtrl->getLinkTarget($this, "showPersonalData"));
				$ilCtrl->forwardCommand($pub_profile_gui);
				$tpl->show();
				break;

			case "ilportfoliogui":
				$this->__initSubTabs("portfolios");
				$ilTabs->activateTab("portfolios");
				$tpl->setTitle($lng->txt("personal_profile"));
				include_once("./Services/Portfolio/classes/class.ilPortfolioGUI.php");
				$portfolio_gui = new ilPortfolioGUI($ilUser->getId());
				$ilCtrl->forwardCommand($portfolio_gui);
				$tpl->show();
				break;

			default:
				//$this->setTabs();
				
				$cmd = $this->ctrl->getCmd("showPersonalData");
				
				// check whether password of user have to be changed
				// due to first login or password of user is expired
				if( $ilUser->isPasswordChangeDemanded() && $cmd != 'savePassword' )
				{
					$cmd = 'showPassword';

					ilUtil::sendInfo(
						$this->lng->txt('password_change_on_first_login_demand'), true
					);
				}
				elseif( $ilUser->isPasswordExpired() && $cmd != 'savePassword' )
				{
					$cmd = 'showPassword';

					$msg = $this->lng->txt('password_expired');
					$password_age = $ilUser->getPasswordAge();

					ilUtil::sendInfo( sprintf($msg,$password_age), true );
				}

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
		if (isset($this->settings["usr_settings_hide_".$setting]) &&
			$this->settings["usr_settings_hide_".$setting] == 1)
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
	* Upload user image
	*/
	function uploadUserPicture()
	{
		global $ilUser;

		if ($this->workWithUserSetting("upload"))
		{
			$userfile_input = $this->form->getItemByPostVar("userfile");

			if ($_FILES["userfile"]["tmp_name"] == "")
			{
				if ($userfile_input->getDeletionFlag())
				{
					$ilUser->removeUserPicture();
				}
				return;
			}

			if ($_FILES["userfile"]["size"] != 0)
			{
				$webspace_dir = ilUtil::getWebspaceDir();
				$image_dir = $webspace_dir."/usr_images";
				$store_file = "usr_".$ilUser->getID()."."."jpg";

				// store filename
				$ilUser->setPref("profile_image", $store_file);
				$ilUser->update();

				// move uploaded file
				$uploaded_file = $image_dir."/upload_".$ilUser->getId()."pic";

				if (!ilUtil::moveUploadedFile($_FILES["userfile"]["tmp_name"], $_FILES["userfile"]["name"],
					$uploaded_file, false))
				{
					ilUtil::sendFailure($this->lng->txt("upload_error", true));
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
				ilUtil::execConvert($uploaded_file . "[0] -geometry 200x200 -quality 100 JPEG:".$show_file);
				ilUtil::execConvert($uploaded_file . "[0] -geometry 100x100 -quality 100 JPEG:".$thumb_file);
				ilUtil::execConvert($uploaded_file . "[0] -geometry 75x75 -quality 100 JPEG:".$xthumb_file);
				ilUtil::execConvert($uploaded_file . "[0] -geometry 30x30 -quality 100 JPEG:".$xxthumb_file);
			}
		}

//		$this->saveProfile();
	}

	/**
	* remove user image
	*/
	function removeUserPicture()
	{
		global $ilUser;

		$ilUser->removeUserPicture();

		$this->saveProfile();
	}



	/**
	* save user profile data
	*/
	function saveProfile()
	{
		global $ilUser ,$ilSetting, $ilAuth;

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

		$d_set = new ilSetting("delicious");
		if ($d_set->get("user_profile"))
		{
			if (($_POST["chk_delicious"]) == "on")
			{
				$ilUser->setPref("public_delicious","y");
			}
			else
			{
				$ilUser->setPref("public_delicious","n");
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
							ilUtil::sendFailure($this->lng->txt("fill_out_all_required_fields") . ": " . $this->lng->txt($val));
							$form_valid = false;
						}
					}
				}
			}
		}

		// Check user defined required fields
		if($form_valid and !$this->__checkUserDefinedRequiredFields())
		{
			ilUtil::sendFailure($this->lng->txt("fill_out_all_required_fields"));
			$form_valid = false;
		}

		// check email
		if ($this->workWithUserSetting("email"))
		{
			if (!ilUtil::is_email($_POST["usr_email"]) and !empty($_POST["usr_email"]) and $form_valid)
			{
				ilUtil::sendFailure($this->lng->txt("email_not_valid"));
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

		// delicious
		$d_set = new ilSetting("delicious");
		if ($d_set->get("user_profile"))
		{
			$ilUser->setDelicious(ilUtil::stripSlashes($_POST["usr_delicious"]));
		}

		// set instant messengers
		if ($this->workWithUserSetting("instant_messengers"))
		{
			$ilUser->setInstantMessengerId('icq',ilUtil::stripSlashes($_POST["usr_im_icq"]));
			$ilUser->setInstantMessengerId('yahoo',ilUtil::stripSlashes($_POST["usr_im_yahoo"]));
			$ilUser->setInstantMessengerId('msn',ilUtil::stripSlashes($_POST["usr_im_msn"]));
			$ilUser->setInstantMessengerId('aim',ilUtil::stripSlashes($_POST["usr_im_aim"]));
			$ilUser->setInstantMessengerId('skype',ilUtil::stripSlashes($_POST["usr_im_skype"]));
			$ilUser->setInstantMessengerId('jabber',ilUtil::stripSlashes($_POST["usr_im_jabber"]));
			$ilUser->setInstantMessengerId('voip',ilUtil::stripSlashes($_POST["usr_im_voip"]));
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

			// set hide own online_status
			if ($this->workWithUserSetting("hide_own_online_status"))
			{
				if ($_POST["chk_hide_own_online_status"] != "")
				{
					$ilUser->setPref("hide_own_online_status","y");
				}
				else
				{
					$ilUser->setPref("hide_own_online_status","n");
				}
			}

			// personal desktop items in news block
/* Subscription Concept is abandonded for now, we show all news of pd items (Alex)
			if ($_POST["pd_items_news"] != "")
			{
				$ilUser->setPref("pd_items_news","y");
			}
			else
			{
				$ilUser->setPref("pd_items_news","n");
			}
*/

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
				ilUtil::sendFailure($this->password_error,true);
			}
			elseif (!empty($this->upload_error))
			{
				ilUtil::sendFailure($this->upload_error,true);
			}
			else if ($reload)
			{
				// feedback
				ilUtil::sendSuccess($this->lng->txt("saved_successfully"),true);
				$this->ctrl->redirect($this, "");
				//$this->tpl->setVariable("RELOAD","<script language=\"Javascript\">\ntop.location.href = \"./start.php\";\n</script>\n");
			}
			else
			{
				ilUtil::sendSuccess($this->lng->txt("saved_successfully"),true);
			}
		}

		$this->showProfile();
	}

	/**
	* show profile form
	*
	* /OLD IMPLEMENTATION DEPRECATED
	*/
	function showProfile()
	{
		$this->showPersonalData();
	}
	
	/**
	 * Add location fields to form if activated
	 * 
	 * @param ilPropertyFormGUI $a_form
	 * @param ilObjUser $a_user
	 */
	function addLocationToForm(ilPropertyFormGUI $a_form, ilObjUser $a_user)
	{
		global $ilCtrl;

		// check google map activation
		include_once("./Services/GoogleMaps/classes/class.ilGoogleMapUtil.php");
		if (!ilGoogleMapUtil::isActivated())
		{
			return;
		}
		
		$this->lng->loadLanguageModule("gmaps");

		// Get user settings
		$latitude = $a_user->getLatitude();
		$longitude = $a_user->getLongitude();
		$zoom = $a_user->getLocationZoom();
		
		// Get Default settings, when nothing is set
		if ($latitude == 0 && $longitude == 0 && $zoom == 0)
		{
			$def = ilGoogleMapUtil::getDefaultSettings();
			$latitude = $def["latitude"];
			$longitude = $def["longitude"];
			$zoom =  $def["zoom"];
		}
		
		$street = $a_user->getStreet();
		if (!$street)
		{
			$street = $this->lng->txt("street");
		}
		$city = $a_user->getCity();
		if (!$city)
		{
			$city = $this->lng->txt("city");
		}
		$country = $a_user->getCountry();
		if (!$country)
		{
			$country = $this->lng->txt("country");
		}
		
		// location property
		$loc_prop = new ilLocationInputGUI($this->lng->txt("location"),
			"location");
		$loc_prop->setLatitude($latitude);
		$loc_prop->setLongitude($longitude);
		$loc_prop->setZoom($zoom);
		$loc_prop->setAddress($street.",".$city.",".$country);
		
		$a_form->addItem($loc_prop);
	}

	// init sub tabs
	function __initSubTabs($a_cmd)
	{
		global $ilTabs, $ilSetting, $ilUser;

		// profile
		$ilTabs->addTab("profile", 
			$this->lng->txt("profile"),
			$this->ctrl->getLinkTarget($this, "showPersonalData"));
		
		if($a_cmd == "showPersonalData")
		{
			// personal data
			$ilTabs->addSubTab("personal_data", 
				$this->lng->txt("personal_data"),
				$this->ctrl->getLinkTarget($this, "showPersonalData"));

			// public profile
			$ilTabs->addSubTab("public_profile",
				$this->lng->txt("public_profile"),
				$this->ctrl->getLinkTarget($this, "showPublicProfile"));
			
			if($ilUser->getPref("public_profile") != "n")
			{			
				// profile preview
				$ilTabs->addSubTab("profile_preview",
					$this->lng->txt("user_profile_preview"),
					$this->ctrl->getLinkTargetByClass("ilpublicuserprofilegui", "view"));
			}
		}
		
		// :TODO: admin setting
		if(true)
		{
			$ilTabs->addTab("portfolios", 
				$this->lng->txt("portfolios"),
				$this->ctrl->getLinkTargetByClass("ilportfoliogui", "show"));
		}
	}


	function __showOtherInformations()
	{
		$d_set = new ilSetting("delicous");
		if($this->userSettingVisible("matriculation") or count($this->user_defined_fields->getVisibleDefinitions())
			or $d_set->get("user_profile") == "1")
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
				$this->tpl->setVariable("FIELD_VALUE",ilUtil::prepareFormOutput($user_defined_data[$field_id]));
				if(!$definition['changeable'])
				{
					$this->tpl->setVariable("DISABLED_FIELD",'disabled=\"disabled\"');
					$this->tpl->setVariable("FIELD_NAME",'udf['.$definition['field_id'].']');
				}
				else
				{
					$this->tpl->setVariable("FIELD_NAME",'udf['.$definition['field_id'].']');
				}
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				if($definition['changeable'])
				{
					$name = 'udf['.$definition['field_id'].']';
					$disabled = false;
				}
				else
				{
					$name = '';
					$disabled = true;
				}
				$this->tpl->setCurrentBlock("field_select");
				$this->tpl->setVariable("SELECT_BOX",ilUtil::formSelect($user_defined_data[$field_id],
																		$name,
																		$this->user_defined_fields->fieldValuesToSelectArray(
																			$definition['field_values']),
																		false,
																		true,0,'','',$disabled));
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

	/**
	 * Set header
	 */
	function setHeader()
	{
//		$this->tpl->setTitleIcon(ilUtil::getImagePath("icon_pd_b.gif"), "");
		$this->tpl->setVariable('HEADER', $this->lng->txt('personal_profile'));
	}

	//
	//
	//	PERSONAL DATA FORM
	//
	//
	
	/**
	* Personal data form.
	*/
	function showPersonalData($a_no_init = false)
	{
		global $ilUser, $styleDefinition, $rbacreview, $ilias, $lng, $ilSetting, $ilTabs;
		
		$this->__initSubTabs("showPersonalData");
		$ilTabs->activateTab("profile");
		$ilTabs->activateSubTab("personal_data");

		$settings = $ilias->getAllSettings();

		$this->setHeader();

		if (!$a_no_init)
		{
			$this->initPersonalDataForm();
			// catch feedback message
			if ($ilUser->getProfileIncomplete())
			{
				ilUtil::sendInfo($lng->txt("profile_incomplete"));
			}
		}
		$this->tpl->setContent($this->form->getHTML());

		$this->tpl->show();
	}

	/**
	* Init personal form
	*/
	function initPersonalDataForm()
	{
		global $ilSetting, $lng, $ilUser, $styleDefinition, $rbacreview;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		
		// user defined fields
		$user_defined_data = $ilUser->getUserDefinedData();

		foreach($this->user_defined_fields->getVisibleDefinitions() as $field_id => $definition)
		{
			if($definition['field_type'] == UDF_TYPE_TEXT)
			{
				$this->input["udf_".$definition['field_id']] =
					new ilTextInputGUI($definition['field_name'], "udf_".$definition['field_id']);
				$this->input["udf_".$definition['field_id']]->setValue($user_defined_data["f_".$field_id]);
				$this->input["udf_".$definition['field_id']]->setMaxLength(255);
				$this->input["udf_".$definition['field_id']]->setSize(40);
			}
			else if($definition['field_type'] == UDF_TYPE_WYSIWYG)
			{
				$this->input["udf_".$definition['field_id']] =
					new ilTextAreaInputGUI($definition['field_name'], "udf_".$definition['field_id']);
				$this->input["udf_".$definition['field_id']]->setValue($user_defined_data["f_".$field_id]);
				$this->input["udf_".$definition['field_id']]->setUseRte(true);
			}
			else
			{
				$this->input["udf_".$definition['field_id']] =
					new ilSelectInputGUI($definition['field_name'], "udf_".$definition['field_id']);
				$this->input["udf_".$definition['field_id']]->setValue($user_defined_data["f_".$field_id]);
				$this->input["udf_".$definition['field_id']]->setOptions(
					$this->user_defined_fields->fieldValuesToSelectArray($definition['field_values']));
			}
			if(!$definition['changeable'])
			{
				$this->input["udf_".$definition['field_id']]->setDisabled(true);
			}
			if($definition['required'])
			{
				$this->input["udf_".$definition['field_id']]->setRequired(true);
			}
		}
		
		// standard fields
		include_once("./Services/User/classes/class.ilUserProfile.php");
		$up = new ilUserProfile();
		$up->skipField("password");
		$up->skipGroup("settings");
		$up->skipGroup("preferences");
		
		// standard fields
		$up->addStandardFieldsToForm($this->form, $ilUser, $this->input);
		
		$this->addLocationToForm($this->form, $ilUser);

		$this->form->addCommandButton("savePersonalData", $lng->txt("save"));

	}

	/**
	* Save personal data form
	*
	*/
	public function savePersonalData()
	{
		global $tpl, $lng, $ilCtrl, $ilUser, $ilSetting, $ilAuth;
	
		$this->initPersonalDataForm();
		if ($this->form->checkInput())
		{
			$form_valid = true;
			
			if ($this->workWithUserSetting("firstname"))
			{
				$ilUser->setFirstName($_POST["usr_firstname"]);
			}
			if ($this->workWithUserSetting("lastname"))
			{
				$ilUser->setLastName($_POST["usr_lastname"]);
			}
			if ($this->workWithUserSetting("gender"))
			{
				$ilUser->setGender($_POST["usr_gender"]);
			}
			if ($this->workWithUserSetting("title"))
			{
				$ilUser->setUTitle($_POST["usr_title"]);
			}
			if ($this->workWithUserSetting("birthday"))
			{
				if (is_array($_POST['usr_birthday']))
				{
					if (is_array($_POST['usr_birthday']['date']))
					{
						if (($_POST['usr_birthday']['d'] > 0) && ($_POST['usr_birthday']['m'] > 0) && ($_POST['usr_birthday']['y'] > 0))
						{
							$ilUser->setBirthday(sprintf("%04d-%02d-%02d", $_POST['user_birthday']['y'], $_POST['user_birthday']['m'], $_POST['user_birthday']['d']));
						}
						else
						{
							$ilUser->setBirthday("");
						}
					}
					else
					{
						$ilUser->setBirthday($_POST['usr_birthday']['date']);
					}
				}
			}
			$ilUser->setFullname();
			if ($this->workWithUserSetting("institution"))
			{
				$ilUser->setInstitution($_POST["usr_institution"]);
			}
			if ($this->workWithUserSetting("department"))
			{
				$ilUser->setDepartment($_POST["usr_department"]);
			}
			if ($this->workWithUserSetting("street"))
			{
				$ilUser->setStreet($_POST["usr_street"]);
			}
			if ($this->workWithUserSetting("zipcode"))
			{
				$ilUser->setZipcode($_POST["usr_zipcode"]);
			}
			if ($this->workWithUserSetting("city"))
			{
				$ilUser->setCity($_POST["usr_city"]);
			}
			if ($this->workWithUserSetting("country"))
			{
				$ilUser->setCountry($_POST["usr_country"]);
			}
			if ($this->workWithUserSetting("sel_country"))
			{
				$ilUser->setSelectedCountry($_POST["usr_sel_country"]);
			}
			if ($this->workWithUserSetting("phone_office"))
			{
				$ilUser->setPhoneOffice($_POST["usr_phone_office"]);
			}
			if ($this->workWithUserSetting("phone_home"))
			{
				$ilUser->setPhoneHome($_POST["usr_phone_home"]);
			}
			if ($this->workWithUserSetting("phone_mobile"))
			{
				$ilUser->setPhoneMobile($_POST["usr_phone_mobile"]);
			}
			if ($this->workWithUserSetting("fax"))
			{
				$ilUser->setFax($_POST["usr_fax"]);
			}
			if ($this->workWithUserSetting("email"))
			{
				$ilUser->setEmail($_POST["usr_email"]);
			}
			if ($this->workWithUserSetting("hobby"))
			{
				$ilUser->setHobby($_POST["usr_hobby"]);
			}
			if ($this->workWithUserSetting("referral_comment"))
			{
				$ilUser->setComment($_POST["usr_referral_comment"]);
			}
			if ($this->workWithUserSetting("matriculation"))
			{
				$ilUser->setMatriculation($_POST["usr_matriculation"]);
			}
			if ($this->workWithUserSetting("delicious"))
			{
				$ilUser->setDelicious($_POST["usr_delicious"]);
			}

			// delicious
			$d_set = new ilSetting("delicious");
			if ($d_set->get("user_profile"))
			{
				$ilUser->setDelicious($_POST["usr_delicious"]);
			}

			// set instant messengers
			if ($this->workWithUserSetting("instant_messengers"))
			{
				$ilUser->setInstantMessengerId('icq',$_POST["usr_im_icq"]);
				$ilUser->setInstantMessengerId('yahoo',$_POST["usr_im_yahoo"]);
				$ilUser->setInstantMessengerId('msn',$_POST["usr_im_msn"]);
				$ilUser->setInstantMessengerId('aim',$_POST["usr_im_aim"]);
				$ilUser->setInstantMessengerId('skype',$_POST["usr_im_skype"]);
				$ilUser->setInstantMessengerId('jabber',$_POST["usr_im_jabber"]);
				$ilUser->setInstantMessengerId('voip',$_POST["usr_im_voip"]);
			}
		
			// check google map activation
			include_once("./Services/GoogleMaps/classes/class.ilGoogleMapUtil.php");
			if (ilGoogleMapUtil::isActivated())
			{
				$ilUser->setLatitude(ilUtil::stripSlashes($_POST["location"]["latitude"]));
				$ilUser->setLongitude(ilUtil::stripSlashes($_POST["location"]["longitude"]));
				$ilUser->setLocationZoom(ilUtil::stripSlashes($_POST["location"]["zoom"]));
			}				

			// Set user defined data
			$defs = $this->user_defined_fields->getVisibleDefinitions();
			$udf = array();
			foreach ($_POST as $k => $v)
			{
				if (substr($k, 0, 4) == "udf_")
				{
					$f = substr($k, 4);
					if ($defs[$f]["changeable"] && $defs[$f]["visible"])
					{
						$udf[$f] = $v;
					}
				}
			}

			$ilUser->setUserDefinedData($udf);
		
			// if loginname is changeable -> validate
			$un = $this->form->getInput('username');
			if((int)$ilSetting->get('allow_change_loginname') && 
			   $un != $ilUser->getLogin())
			{				
				if(!strlen($un) || !ilUtil::isLogin($un))
				{
					ilUtil::sendFailure($lng->txt('form_input_not_valid'));
					$this->form->getItemByPostVar('username')->setAlert($this->lng->txt('login_invalid'));
					$form_valid = false;	
				}
				else if(ilObjUser::_loginExists($un, $ilUser->getId()))
				{
					ilUtil::sendFailure($lng->txt('form_input_not_valid'));
					$this->form->getItemByPostVar('username')->setAlert($this->lng->txt('loginname_already_exists'));
					$form_valid = false;
				}	
				else
				{
					$ilUser->setLogin($un);
					
					try 
					{
						$ilUser->updateLogin($ilUser->getLogin());
						$ilAuth->setAuth($ilUser->getLogin());
						$ilAuth->start();
					}
					catch (ilUserException $e)
					{
						ilUtil::sendFailure($lng->txt('form_input_not_valid'));
						$this->form->getItemByPostVar('username')->setAlert($e->getMessage());
						$form_valid = false;							
					}
				}
			}

			// everthing's ok. save form data
			if ($form_valid)
			{
				$this->uploadUserPicture();
				
				// profile ok
				$ilUser->setProfileIncomplete(false);
	
				// save user data & object_data
				$ilUser->setTitle($ilUser->getFullname());
				$ilUser->setDescription($ilUser->getEmail());
	
				$ilUser->update();
				
                                ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
                                if ($redirect = $_SESSION['profile_complete_redirect']) {
					unset($_SESSION['profile_complete_redirect']);
					ilUtil::redirect($redirect);
				}
				else
                                    $ilCtrl->redirect($this, "showPersonalData");
			}
		}
		
		$this->form->setValuesByPost();
		$this->showPersonalData(true);
	}
	
	//
	//
	//	PUBLIC PROFILE FORM
	//
	//
	
	/**
	* Public profile form
	*/
	function showPublicProfile($a_no_init = false)
	{
		global $ilUser, $lng, $ilSetting, $ilTabs;
		
		$this->__initSubTabs("showPersonalData");
		$ilTabs->activateTab("profile");
		$ilTabs->activateSubTab("public_profile");

		$this->setHeader();

		if (!$a_no_init)
		{
			$this->initPublicProfileForm();
		}
		
		$ptpl = new ilTemplate("tpl.edit_personal_profile.html", true, true, "Services/User");
		$ptpl->setVariable("FORM", $this->form->getHTML());
		include_once("./Services/User/classes/class.ilPublicUserProfileGUI.php");
		$pub_profile = new ilPublicUserProfileGUI($ilUser->getId());
		$ptpl->setVariable("PREVIEW", $pub_profile->getEmbeddable());
		$this->tpl->setContent($ptpl->get());
		$this->tpl->show();
	}

	/**
	* Init public profile form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initPublicProfileForm()
	{
		global $lng, $ilUser, $ilSetting;
		
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
	
		// Activate public profile
		$radg = new ilRadioGroupInputGUI($lng->txt("user_activate_public_profile"), "public_profile");
		$radg->setInfo($this->lng->txt("user_activate_public_profile_info"));
		$pub_prof = in_array($ilUser->prefs["public_profile"], array("y", "n", "g"))
			? $ilUser->prefs["public_profile"]
			: "n";
		if (!$ilSetting->get('enable_global_profiles') && $pub_prof == "g")
		{
			$pub_prof = "y";
		}
		$radg->setValue($pub_prof);
			$op1 = new ilRadioOption($lng->txt("usr_public_profile_disabled"), "n",$lng->txt("usr_public_profile_disabled_info"));
			$radg->addOption($op1);
			$op2 = new ilRadioOption($lng->txt("usr_public_profile_logged_in"), "y",$lng->txt("usr_public_profile_logged_in_info"));
			$radg->addOption($op2);
		if ($ilSetting->get('enable_global_profiles'))
		{
			$op3 = new ilRadioOption($lng->txt("usr_public_profile_global"), "g",$lng->txt("usr_public_profile_global_info"));
			$radg->addOption($op3);
		}
		$this->form->addItem($radg);
		
		$this->showPublicProfileFields($this->form, $ilUser->prefs);
		
		// save and cancel commands
		$this->form->addCommandButton("savePublicProfile", $lng->txt("save"));
	                
		$this->form->setTitle($lng->txt("public_profile"));
		$this->form->setDescription($lng->txt("user_public_profile_info"));
		$this->form->setFormAction($this->ctrl->getFormAction($this));
	}

	/**
	 * Add fields to form
	 *
	 * @param ilPropertyformGUI $form
	 * @param array $prefs
	 * @param object $parent
	 */
	public function showPublicProfileFields(ilPropertyformGUI $form, array $prefs, $parent = null)
	{
		global $ilUser;
		
		$birthday = $ilUser->getBirthday();
		if($birthday)
		{
			$birthday = ilDatePresentation::formatDate(new ilDate($birthday, IL_CAL_DATE));
		}
		$gender = $ilUser->getGender();
		if($gender)
		{
			$gender = $this->lng->txt("gender_".$gender);
		}

		if ($ilUser->getSelectedCountry() != "")
		{
			$this->lng->loadLanguageModule("meta");
			$txt_sel_country = $this->lng->txt("meta_c_".$ilUser->getSelectedCountry());
		}

		// personal data
		$val_array = array(
			"title" => $ilUser->getUTitle(),
			"birthday" => $birthday,
			"gender" => $gender,
			"institution" => $ilUser->getInstitution(),
			"department" => $ilUser->getDepartment(),
			"upload" => "",
			"street" => $ilUser->getStreet(),
			"zipcode" => $ilUser->getZipcode(),
			"city" => $ilUser->getCity(),
			"country" => $ilUser->getCountry(),
			"sel_country" => $txt_sel_country,
			"phone_office" => $ilUser->getPhoneOffice(),
			"phone_home" => $ilUser->getPhoneHome(),
			"phone_mobile" => $ilUser->getPhoneMobile(),
			"fax" => $ilUser->getFax(),
			"email" => $ilUser->getEmail(),
			"hobby" => $ilUser->getHobby(),
			"matriculation" => $ilUser->getMatriculation(),
			"delicious" => $ilUser->getDelicious()
			);
		
		// location
		include_once("./Services/GoogleMaps/classes/class.ilGoogleMapUtil.php");
		if (ilGoogleMapUtil::isActivated())
		{
			$val_array["location"] = "";
		}		
		
		foreach($val_array as $key => $value)
		{
			if ($this->userSettingVisible($key))
			{
				// public setting
				if ($key == "upload")
				{
					$cb = new ilCheckboxInputGUI($this->lng->txt("personal_picture"), "chk_".$key);
				}
				else
				{
					$cb = new ilCheckboxInputGUI($this->lng->txt($key), "chk_".$key);
				}
				if ($prefs["public_".$key] == "y")
				{
					$cb->setChecked(true);
				}
				//$cb->setInfo($value);
				$cb->setOptionTitle($value);

				if(!$parent)
				{
					$form->addItem($cb);
				}
				else
				{
					$parent->addSubItem($cb);
				}
			}
		}

		$im_arr = array("icq","yahoo","msn","aim","skype","jabber","voip");
		if ($this->userSettingVisible("instant_messengers"))
		{
			foreach ($im_arr as $im)
			{
				// public setting
				$cb = new ilCheckboxInputGUI($this->lng->txt("im_".$im), "chk_im_".$im);
				//$cb->setInfo($ilUser->getInstantMessengerId($im));
				$cb->setOptionTitle($ilUser->getInstantMessengerId($im));
				if ($prefs["public_im_".$im] != "n")
				{
					$cb->setChecked(true);
				}
				
				if(!$parent)
				{
					$form->addItem($cb);
				}
				else
				{
					$parent->addSubItem($cb);
				}
			}
		}

		// additional defined user data fields
		$user_defined_data = $ilUser->getUserDefinedData();
		foreach($this->user_defined_fields->getVisibleDefinitions() as $field_id => $definition)
		{
			// public setting
			$cb = new ilCheckboxInputGUI($definition["field_name"], "chk_udf_".$definition["field_id"]);
			$cb->setOptionTitle($user_defined_data["f_".$definition["field_id"]]);
			if ($prefs["public_udf_".$definition["field_id"]] == "y")
			{
				$cb->setChecked(true);
			}

			if(!$parent)
			{
				$form->addItem($cb);
			}
			else
			{
				$parent->addSubItem($cb);
			}
		}
	}
	
	/**
	* Save public profile form
	*
	*/
	public function savePublicProfile()
	{
		global $tpl, $lng, $ilCtrl, $ilUser;
	
		$this->initPublicProfileForm();
		if ($this->form->checkInput())
		{
			/*if (($_POST["public_profile"]))
			{
				$ilUser->setPref("public_profile","y");
			}
			else
			{
				$ilUser->setPref("public_profile","n");
			}*/
			$ilUser->setPref("public_profile", $_POST["public_profile"]);

			// if check on Institute
			$val_array = array("title", "birthday", "gender", "institution", "department", "upload", "street",
				"zipcode", "city", "country", "sel_country", "phone_office", "phone_home", "phone_mobile",
				"fax", "email", "hobby", "matriculation", "location");
	
			// set public profile preferences
			foreach($val_array as $key => $value)
			{
				if (($_POST["chk_".$value]))
				{
					$ilUser->setPref("public_".$value,"y");
				}
				else
				{
					$ilUser->setPref("public_".$value,"n");
				}
			}
	
			$im_arr = array("icq","yahoo","msn","aim","skype","jabber","voip");
			if ($this->userSettingVisible("instant_messengers"))
			{
				foreach ($im_arr as $im)
				{
					if (($_POST["chk_im_".$im]))
					{
						$ilUser->setPref("public_im_".$im,"y");
					}
					else
					{
						$ilUser->setPref("public_im_".$im,"n");
					}
				}
			}

//			$d_set = new ilSetting("delicious");
//			if ($d_set->get("user_profile"))
//			{
				if (($_POST["chk_delicious"]))
				{
					$ilUser->setPref("public_delicious","y");
				}
				else
				{
					$ilUser->setPref("public_delicious","n");
				}
//			}
			
			// additional defined user data fields
			foreach($this->user_defined_fields->getVisibleDefinitions() as $field_id => $definition)
			{
				if (($_POST["chk_udf_".$definition["field_id"]]))
				{
					$ilUser->setPref("public_udf_".$definition["field_id"], "y");
				}
				else
				{
					$ilUser->setPref("public_udf_".$definition["field_id"], "n");
				}
			}

			$ilUser->update();
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "showPublicProfile");
		}
		$this->form->setValuesByPost();
		$tpl->showPublicProfile(true);
	}
}

?>