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
* StartUp GUI class. Handles Login and Registration.
*
* @author	Alex Killing <alex.killing@gmx.de>
* @version	$Id$
* @ilCtrl_Calls ilStartUpGUI: ilRegistrationGUI, ilPasswordAssistanceGUI
*
* @ingroup Services/Init
*/
class ilStartUpGUI
{
	
	/**
	* constructor
	*/
	function ilStartUpGUI()
	{
		global $ilCtrl;
				
		$this->ctrl =& $ilCtrl;
		
		$ilCtrl->saveParameter($this, array("rep_ref_id", "lang", "target"));
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		$cmd = $this->ctrl->getCmd("processIndexPHP");
		$next_class = $this->ctrl->getNextClass($this);

		switch($next_class)
		{
			case "ilregistrationgui":
				require_once("Services/Registration/classes/class.ilRegistrationGUI.php");
				return $this->ctrl->forwardCommand(new ilRegistrationGUI());
				break;

			case "ilpasswordassistancegui":
				require_once("Services/Init/classes/class.ilPasswordAssistanceGUI.php");
				return $this->ctrl->forwardCommand(new ilPasswordAssistanceGUI());
				break;

			default:
				return $this->$cmd();
				break;
		}
	}
	
	/**
	* jump to registration gui
	*/
	function jumpToRegistration()
	{
		$this->ctrl->setCmdClass("ilregistrationgui");
		$this->ctrl->setCmd("");
		$this->executeCommand();
	}

	/**
	* jump to password assistance
	*/
	function jumpToPasswordAssistance()
	{
		$this->ctrl->setCmdClass("ilpasswordassistancegui");
		$this->ctrl->setCmd("");
		$this->executeCommand();
	}

	/**
	* show login
	*/
	function showLogin()
	{
		global $ilSetting, $ilAuth, $ilUser, $tpl, $ilIliasIniFile, $ilias;

		// login language selection is post type
		if ($_POST["lang"] != "")
		{
			$_GET["lang"] = $_POST["lang"];
		}
		
		// check for session cookies enabled
		if (!isset($_COOKIE['iltest']))
		{
			if (empty($_GET['cookies']))
			{
				setcookie("iltest","cookie");
				header('Location: '.$_SERVER['PHP_SELF']."?cookies=nocookies&lang=".$_GET['lang']);
			}
			else
			{
				$_COOKIE['iltest'] = "";
			}
		}
		else
		{
			unset($_GET['cookies']);
		}
				
		// check correct setup
		if (!$ilSetting->get("setup_ok"))
		{
			die("Setup is not completed. Please run setup routine again. (Login)");
		}
		
		if ($ilSetting->get("shib_active") && $ilSetting->get("shib_hos_type"))
		{
			require_once "classes/class.ilShibbolethWAYF.php";
			// Check if we user selects Home Organization
			$WAYF = new ShibWAYF();
		}
		
		if (isset($WAYF) && $WAYF->isSelection())
		{
			if ($WAYF->isValidSelection())
			{
				// Set cookie
				$WAYF->setSAMLCookie();
				
				// Redirect
				$WAYF->redirect();
			}
		} 
		elseif ($ilAuth->getAuth())
		{
			// Or we do authentication here
			// To do: check whether some $ilInit method could be used here.
			
			if(!$ilUser->checkTimeLimit())
			{
				$ilAuth->logout();
				session_destroy();
				
				// to do: get rid of this
				ilUtil::redirect('login.php?time_limit=true');
			}
		
			include_once './Services/Tracking/classes/class.ilOnlineTracking.php';
			ilOnlineTracking::_addUser($ilUser->getId());
		
			// handle chat kicking
			if ($ilSetting->get("chat_active"))
			{
				include_once "./chat/classes/class.ilChatServerCommunicator.php";
				include_once "./chat/classes/class.ilChatRoom.php";
		
				ilChatServerCommunicator::_login();
				ilChatRoom::_unkick($ilUser->getId());
			}
		
			// update last forum visit
			include_once './classes/class.ilObjForum.php';
			ilObjForum::_updateOldAccess($ilUser->getId());
		
			if ($_GET["rep_ref_id"] != "")
			{
				$_GET["ref_id"] = $_GET["rep_ref_id"];
			}
			$this->processStartingPage();
			exit;
		}
		
		// Instantiate login template
		// Use Shibboleth-only authentication if auth_mode is set to Shibboleth
		$tpl->addBlockFile("CONTENT", "content", "tpl.login.html");
		
		//language handling
		if ($_GET["lang"] == "")
		{
			$_GET["lang"] = $ilIliasIniFile->readVariable("language","default");
		}
		
		//instantiate language
		$lng = new ilLanguage($_GET["lang"]);		
		
		$tpl->setVariable("TXT_OK", $lng->txt("ok"));
		
		$languages = $lng->getInstalledLanguages();
		
		foreach ($languages as $lang_key)
		{
			$tpl->setCurrentBlock("languages");
			$tpl->setVariable("LANG_KEY", $lang_key);
			$tpl->setVariable("LANG_NAME",
				ilLanguage::_lookupEntry($lang_key, "meta", "meta_l_".$lang_key));
			$tpl->setVariable("BORDER", 0);
			$tpl->setVariable("VSPACE", 0);
			$tpl->parseCurrentBlock();
		}
		
		// allow new registrations? 
		include_once 'Services/Registration/classes/class.ilRegistrationSettings.php';
		if (ilRegistrationSettings::_lookupRegistrationType() != IL_REG_DISABLED)
		{
			$tpl->setCurrentBlock("new_registration");
			$tpl->setVariable("REGISTER", $lng->txt("registration")); 
			$tpl->setVariable("CMD_REGISTER",
				$this->ctrl->getLinkTargetByClass("ilregistrationgui", ""));
			$tpl->parseCurrentBlock();
		}
		// allow password assistance? Surpress option if Authmode is not local database
		if ($ilSetting->get("password_assistance") and AUTH_DEFAULT == AUTH_LOCAL)
		{
			$tpl->setCurrentBlock("password_assistance");
			$tpl->setVariable("FORGOT_PASSWORD", $lng->txt("forgot_password"));
			$tpl->setVariable("FORGOT_USERNAME", $lng->txt("forgot_username"));
			$tpl->setVariable("CMD_FORGOT_PASSWORD",
				$this->ctrl->getLinkTargetByClass("ilpasswordassistancegui", ""));
			$tpl->setVariable("CMD_FORGOT_USERNAME",
				$this->ctrl->getLinkTargetByClass("ilpasswordassistancegui", "showUsernameAssistanceForm"));
			$tpl->setVariable("LANG_ID", $_GET["lang"]);
			$tpl->parseCurrentBlock();
		}
		
		if ($ilSetting->get("pub_section"))
		{
			$tpl->setCurrentBlock("homelink");
			$tpl->setVariable("CLIENT_ID","?client_id=".$_COOKIE["ilClientId"]."&lang=".$_GET["lang"]);
			$tpl->setVariable("TXT_HOME",$lng->txt("home"));
			$tpl->parseCurrentBlock();
		}
		
		if ($ilIliasIniFile->readVariable("clients","list"))
		{
			$tpl->setCurrentBlock("client_list");
			$tpl->setVariable("TXT_CLIENT_LIST", $lng->txt("to_client_list"));
			$tpl->setVariable("CMD_CLIENT_LIST",
				$this->ctrl->getLinkTarget($this, "showClientList"));
			$tpl->parseCurrentBlock();	
		}
		
		// shibboleth login link
		if ($ilSetting->get("shib_active"))
		{
			if($ilSetting->get("shib_hos_type") != 'external_wayf'){
				$tpl->setCurrentBlock("shibboleth_wayf_login");
				$tpl->setVariable("TXT_SHIB_LOGIN", $lng->txt("login_to_ilias_via_shibboleth"));
				$tpl->setVariable("TXT_SHIB_FEDERATION_NAME", $ilSetting->get("shib_federation_name"));
				$tpl->setVariable("TXT_SELECT_HOME_ORGANIZATION", sprintf($lng->txt("shib_select_home_organization"), $ilSetting->get("shib_federation_name")));
				$tpl->setVariable("TXT_CONTINUE", $lng->txt("btn_next"));
				$tpl->setVariable("TXT_SHIB_HOME_ORGANIZATION", $lng->txt("shib_home_organization"));
				$tpl->setVariable("TXT_SHIB_LOGIN_INSTRUCTIONS", $lng->txt("shib_general_wayf_login_instructions").' <a href="mailto:'.$ilias->getSetting("admin_email").'">ILIAS '. $lng->txt("administrator").'</a>.');
				$tpl->setVariable("TXT_SHIB_CUSTOM_LOGIN_INSTRUCTIONS", $ilSetting->get("shib_login_instructions"));
				$tpl->setVariable("TXT_SHIB_INVALID_SELECTION", $WAYF->showNotice());
				$tpl->setVariable("SHIB_IDP_LIST", $WAYF->generateSelection());
				
				$tpl->parseCurrentBlock();
			} else {
				$tpl->setCurrentBlock("shibboleth_login");
				$tpl->setVariable("TXT_SHIB_LOGIN", $lng->txt("login_to_ilias_via_shibboleth"));
				$tpl->setVariable("TXT_SHIB_FEDERATION_NAME", $ilSetting->get("shib_federation_name"));
				$tpl->setVariable("TXT_SHIB_LOGIN_BUTTON", $ilSetting->get("shib_login_button"));
					$tpl->setVariable("TXT_SHIB_LOGIN_INSTRUCTIONS", sprintf($lng->txt("shib_general_login_instructions"),$ilSetting->get("shib_federation_name")).' <a href="mailto:'.$ilias->getSetting("admin_email").'">ILIAS '. $lng->txt("administrator").'</a>.');
				$tpl->setVariable("TXT_SHIB_CUSTOM_LOGIN_INSTRUCTIONS", $ilSetting->get("shib_login_instructions"));
				$tpl->parseCurrentBlock();
			}
		}
		
		// cas login link
		if ($ilSetting->get("cas_active"))
		{
			$tpl->setCurrentBlock("cas_login");
			$tpl->setVariable("TXT_CAS_LOGIN", $lng->txt("login_to_ilias_via_cas"));
			$tpl->setVariable("TXT_CAS_LOGIN_BUTTON", ilUtil::getImagePath("cas_login_button.gif"));
			$tpl->setVariable("TXT_CAS_LOGIN_INSTRUCTIONS", $ilSetting->get("cas_login_instructions"));
			$this->ctrl->setParameter($this, "forceCASLogin", "1");
			$tpl->setVariable("TARGET_CAS_LOGIN",
				$this->ctrl->getLinkTarget($this, "showLogin"));
			$this->ctrl->setParameter($this, "forceCASLogin", "");
			$tpl->parseCurrentBlock();
		}
		
		// login via ILIAS (this also includes radius and ldap)
		if ($ilSetting->get("auth_mode") != AUTH_SHIBBOLETH &&
			$ilSetting->get("auth_mode") != AUTH_CAS)
		{
			$tpl->setCurrentBlock("ilias_login");
			$tpl->setVariable("TXT_ILIAS_LOGIN", $lng->txt("login_to_ilias"));
			$tpl->setVariable("TXT_USERNAME", $lng->txt("username"));
			$tpl->setVariable("TXT_PASSWORD", $lng->txt("password"));
			$tpl->setVariable("USERNAME", $_POST["username"]);
			$tpl->setVariable("TXT_SUBMIT", $lng->txt("submit"));
			$tpl->parseCurrentBlock();
		}

		$tpl->setVariable("ILIAS_RELEASE", $ilSetting->get("ilias_version"));
		
		
		$tpl->setVariable("FORMACTION",
			$this->ctrl->getFormAction($this));
//echo "-".htmlentities($this->ctrl->getFormAction($this, "showLogin"))."-";
		$tpl->setVariable("LANG_FORM_ACTION", 
			$this->ctrl->getFormAction($this));
		$tpl->setVariable("TXT_CHOOSE_LANGUAGE", $lng->txt("choose_language"));
		$tpl->setVariable("LANG_ID", $_GET["lang"]);
		
		if ($_GET["inactive"])
		{
			$tpl->setVariable(TXT_MSG_LOGIN_FAILED, $lng->txt("err_inactive"));
		}
		elseif ($_GET["expired"])
		{
			$tpl->setVariable(TXT_MSG_LOGIN_FAILED, $lng->txt("err_session_expired"));
		}
		
		// TODO: Move this to header.inc since an expired session could not detected in login script 
		$status = $ilAuth->getStatus();
		if ($status == "")
		{
			$status = $_GET["auth_stat"];
		}
		$auth_error = $ilias->getAuthError();

		if (!empty($status))
		{
			switch ($status)
			{
				case AUTH_EXPIRED:
					$tpl->setVariable(TXT_MSG_LOGIN_FAILED, $lng->txt("err_session_expired"));
					break;
				case AUTH_IDLED:
					// lang variable err_idled not existing
					//$tpl->setVariable(TXT_MSG_LOGIN_FAILED, $lng->txt("err_idled"));
					break;
					
				case AUTH_CAS_NO_ILIAS_USER:
					$tpl->setVariable(TXT_MSG_LOGIN_FAILED,
						$lng->txt("err_auth_cas_no_ilias_user"));
					break;

				case AUTH_SOAP_NO_ILIAS_USER:
					$tpl->setVariable(TXT_MSG_LOGIN_FAILED,
						$lng->txt("err_auth_soap_no_ilias_user"));
					break;
					
				case AUTH_WRONG_LOGIN:
				default:
					$add = "";
					if (is_object($auth_error))
					{
						$add = "<br>".$auth_error->getMessage();
					}
					$tpl->setVariable(TXT_MSG_LOGIN_FAILED, $lng->txt("err_wrong_login").$add);			
					break;
			}
		}
		
		
		if ($_GET['time_limit'])
		{
			$tpl->setVariable("TXT_MSG_LOGIN_FAILED", $lng->txt('time_limit_reached'));
		}
		
		// output wrong IP message
		if($_GET['wrong_ip'])
		{
			$tpl->setVariable("TXT_MSG_LOGIN_FAILED", $lng->txt('wrong_ip_detected')." (".$_SERVER["REMOTE_ADDR"].")");
		}
		
		$tpl->setVariable("PHP_SELF", $_SERVER['PHP_SELF']);
		$tpl->setVariable("USER_AGREEMENT", $lng->txt("usr_agreement"));
		$tpl->setVariable("LINK_USER_AGREEMENT",
			$this->ctrl->getLinkTarget($this, "showUserAgreement"));
		
		// browser does not accept cookies
		if ($_GET['cookies'] == 'nocookies')
		{
			$tpl->setVariable(TXT_MSG_LOGIN_FAILED, $lng->txt("err_no_cookies"));
			$tpl->setVariable("COOKIES_HOWTO", $lng->txt("cookies_howto"));
			$tpl->setVariable("LINK_NO_COOKIES",
				$this->ctrl->getLinkTarget($this, "showNoCookiesScreen"));
		}
		
		$tpl->show("DEFAULT", false);
	}

	/**
	* show logout screen
	*/
	function showLogout()
	{
		global $tpl, $ilSetting, $ilAuth, $lng, $ilIliasIniFile;
		
		// LOGOUT CHAT USER
		if($ilSetting->get("chat_active"))
		{
			include_once "./chat/classes/class.ilChatServerCommunicator.php";
			ilChatServerCommunicator::_logout();
		}

		$ilAuth->logout();
		session_destroy();

		// reset cookie
		$client_id = $_COOKIE["ilClientId"];
		setcookie("ilClientId","");
		$_COOKIE["ilClientId"] = "";
		
		//instantiate logout template
		$tpl->addBlockFile("CONTENT", "content", "tpl.logout.html");
		
		if ($ilSetting->get("pub_section"))
		{
			$tpl->setCurrentBlock("homelink");
			$tpl->setVariable("CLIENT_ID","?client_id=".$client_id."&lang=".$_GET['lang']);
			$tpl->setVariable("TXT_HOME",$lng->txt("home"));
			$tpl->parseCurrentBlock();
		}
		
		if ($ilIliasIniFile->readVariable("clients","list"))
		{
			$tpl->setCurrentBlock("client_list");
			$tpl->setVariable("TXT_CLIENT_LIST", $lng->txt("to_client_list"));
			$tpl->setVariable("CMD_CLIENT_LIST",
				$this->ctrl->getLinkTarget($this, "showClientList"));
			$tpl->parseCurrentBlock();	
		}
		
		$tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("logout"));
		$tpl->setVariable("TXT_LOGOUT_TEXT", $lng->txt("logout_text"));
		$tpl->setVariable("TXT_LOGIN", $lng->txt("login_to_ilias"));
		$tpl->setVariable("CLIENT_ID","?client_id=".$client_id."&lang=".$_GET['lang']);
			
		$tpl->show();
	}
	
	/**
	* show client list
	*/
	function showClientList()
	{
		global $tpl, $ilIliasIniFile, $ilCtrl;
//echo "1";
		if (!$ilIliasIniFile->readVariable("clients","list"))
		{
			$this->processIndexPHP();
			return;
		}
//echo "2";
		$tpl = new ilTemplate("tpl.main.html", true, true);

		// to do: get standard style
		$tpl->setVariable("PAGETITLE","Client List");
		$tpl->setVariable("LOCATION_STYLESHEET","./templates/default/delos.css");
		$tpl->setVariable("LOCATION_JAVASCRIPT","./templates/default");
		
		// load client list template
		$tpl->addBlockfile("CONTENT", "content", "tpl.client_list.html");
		
		// load template for table
		$tpl->addBlockfile("CLIENT_LIST", "client_list", "tpl.table.html");
		
		// load template for table content data
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.obj_tbl_rows.html");
		
		// load table content data
		require_once("setup/classes/class.ilClientList.php");
		require_once("setup/classes/class.ilClient.php");
		require_once("classes/class.ilTableGUI.php");
		$clientlist = new ilClientList();
		$list = $clientlist->getClients();
		
		if (count($list) == 0)
		{
			header("Location: ./setup/setup.php");
			exit();
		}
		
		foreach ($list as $key => $client)
		{
			if ($client->checkDatabaseExists() and $client->ini->readVariable("client","access") and $client->getSetting("setup_ok"))
			{
				$this->ctrl->setParameter($this, "client_id", $key);
				$data[] = array(
								$client->getName(),
								"<a href=\"".$ilCtrl->getLinkTarget($this, "processIndexPHP")."\">Start page</a>",
								"<a href=\"".$ilCtrl->getLinkTarget($this, "showLogin")."\">Login page</a>"
								);
			}
		}
		$this->ctrl->setParameter($this, "client_id", "");
		
		// create table
		$tbl = new ilTableGUI();
		
		// title & header columns
		$tbl->setTitle("Available Clients");
		$tbl->setHeaderNames(array("Installation Name","Public Access","Login"));
		$tbl->setHeaderVars(array("name","index","login"));
		$tbl->setColumnWidth(array("50%","25%","25%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"],"name");
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		
		// content
		$tbl->setData($data);
		
		// footer
		$tbl->setFooter("tblfooter");
		
		// styles
		$tbl->setStyle("table","std");
		
		$tbl->disable("icon");
		$tbl->disable("numinfo");
		
		// render table
		$tbl->render();
		$tpl->show();
	}
	
	/**
	* show help screen, if cookies are disabled
	*
	* to do: link to online help here
	*/
	function showNoCookiesScreen()
	{
		global $tpl;
		
		$str = "<p style=\"margin:15px;\">
			You need to enable Session Cookies in your Browser to use ILIAS.
			<br/>
			<br/><b>Firefox</b>
			<br/>Tools -> Options -> Privacy -> Cookies
			<br/>Enable 'Allow sites to set cookies' and activate option 'Keep
			<br/>cookies' auf 'until I close Firefox'
			<br/>
			<br/><b>Mozilla/Netscape</b>
			<br/>Edit -> Preferences -> Privacy&Security -> Cookies
			<br/>Go to 'Cookie Lifetime Policy' and check option 'Accept for current
			<br/>session only'.
			<br/>
			<br/><b>Internet Explorer</b>
			<br/>Tools -> Internet Options -> Privacy -> Advanced
			<br/>- Check 'Override automatic cookie handling'
			<br/>- Check 'Always allow session cookies'
			</p>";
		$tpl->setVariable("CONTENT", $str);
		$tpl->show();
	}
	
	/**
	* get user agreement acceptance
	*/
	function getAcceptance()
	{
		$this->showUserAgreement();
	}
	
	/**
	* show user agreement
	*/
	function showUserAgreement()
	{
		global $lng, $tpl, $ilUser;
		
		require_once "classes/class.ilUserAgreement.php";
		
		$tpl->addBlockFile("CONTENT", "content", "tpl.view_usr_agreement.html");
		$tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		
		sendInfo();
		// display infopanel if something happened
		infoPanel();
		
		$tpl->setVariable("TXT_CHOOSE_LANGUAGE", $lng->txt("choose_language"));
		$tpl->setVariable("TXT_OK", $lng->txt("ok"));
		
		// language selection
		$languages = $lng->getInstalledLanguages();
	
		$count = (int) round(count($languages) / 2);
		$num = 1;
		
		foreach ($languages as $lang_key)
		{
			$tpl->setCurrentBlock("languages");
			$tpl->setVariable("LANG_VAL_CMD", $this->ctrl->getCmd());
			$tpl->setVariable("AGR_LANG_ACTION",
				$this->ctrl->getFormAction($this));
			$tpl->setVariable("LANG_NAME",
				ilLanguage::_lookupEntry($lang_key, "meta", "meta_l_".$lang_key));
			$tpl->setVariable("LANG_ICON", $lang_key);
			$tpl->setVariable("LANG_KEY", $lang_key);
			$tpl->setVariable("BORDER", 0);
			$tpl->setVariable("VSPACE", 0);
			$tpl->parseCurrentBlock();

			$num++;
		}
		$tpl->setCurrentBlock("content");
		
		// display tabs
		$tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("usr_agreement"));
		$tpl->setVariable("TXT_PAGETITLE", "ILIAS3 - ".$lng->txt("usr_agreement"));
		$tpl->setVariable("TXT_USR_AGREEMENT", ilUserAgreement::_getText());
		
		if ($this->ctrl->getCmd() == "getAcceptance")
		{
			if ($_POST["status"]=="accepted")
			{
				$ilUser->writeAccepted();
				ilUtil::redirect("index.php");
			}
			$tpl->setVariable("VAL_CMD", "getAcceptance");
			$tpl->setVariable("AGR_LANG_ACTION",
				$this->ctrl->getFormAction($this));
			$tpl->setCurrentBlock("get_acceptance");
			$tpl->setVariable("FORM_ACTION",
				$this->ctrl->getFormAction($this));
			$tpl->setVariable("ACCEPT_CHECKBOX", ilUtil::formCheckbox(0, "status", "accepted"));
			$tpl->setVariable("ACCEPT_AGREEMENT", $lng->txt("accept_usr_agreement"));
			$tpl->setVariable("TXT_SUBMIT", $lng->txt("submit"));
			$tpl->parseCurrentBlock();
		}
		else
		{
			$tpl->setCurrentBlock("back");
			$tpl->setVariable("BACK", $lng->txt("back"));
			$tpl->setVariable("LINK_BACK",
				$this->ctrl->getLinkTargetByClass("ilstartupgui", "showLogin"));
			$tpl->parseCurrentBlock();
		}
		
		$tpl->show();


	}
	
	/**
	* process index.php
	*/
	function processIndexPHP()
	{
		global $ilIliasIniFile, $ilAuth, $ilSetting, $ilInit;

		// display client selection list if enabled
		if (!isset($_GET["client_id"]) &&
			!isset($_GET["cmd"]) &&
			$ilIliasIniFile->readVariable("clients","list"))
		{
			$this->showClientList();
			//include_once "./include/inc.client_list.php";
			exit();
		}
		
		/*
		if ($_GET["cmd"] == "login")
		{
			$rep_ref_id = $_SESSION["il_rep_ref_id"];
		
			$ilAuth->logout();
			session_destroy();
		
			// reset cookie
			$client_id = $_COOKIE["ilClientId"];
			setcookie("ilClientId","");
			$_COOKIE["ilClientId"] = "";
		
			$_GET["client_id"] = $client_id;
			$_GET["rep_ref_id"] = $rep_ref_id;
			
			
			ilUtil::redirect("login.php?client_id=".$client_id."&lang=".$_GET['lang'].
				"&rep_ref_id=".$rep_ref_id);
		}*/
		
		
		// if no start page was given, ILIAS defaults to the standard login page
		if ($start == "")
		{
			$start = "login.php";
		}
		
		
		//
		// index.php is called and public section is enabled
		//
		if ($ilSetting->get("pub_section") && $_POST["sendLogin"] != "1")
		{
			//
			// TO DO: THE FOLLOWING BLOCK IS COPY&PASTED FROM HEADER.INC
				
			$_POST["username"] = "anonymous";
			$_POST["password"] = "anonymous";
			$ilAuth->start();
			if (ANONYMOUS_USER_ID == "")
			{
				die ("Public Section enabled, but no Anonymous user found.");
			}
			if (!$ilAuth->getAuth())
			{
				die("ANONYMOUS user with the object_id ".ANONYMOUS_USER_ID." not found!");
			}
		
			// get user id
			$ilInit->initUserAccount();
			$this->processStartingPage();
			exit;
		}
		else
		{
			//
			// index.php is called and public section is disabled
			$this->showLogin();
		}
	}
	
	
	/**
	* open start page (personal desktop or repository)
	*
	* precondition: authentication (maybe anonymous) successfull
	*/
	function processStartingPage()
	{
		global $ilBench, $ilCtrl, $ilAccess, $lng;
//echo "here";
		if ($_SESSION["AccountId"] == ANONYMOUS_USER_ID || !empty($_GET["ref_id"]))
		{
//echo "A";
			// if anonymous and a target given...
			if ($_SESSION["AccountId"] == ANONYMOUS_USER_ID && $_GET["target"] != "")
			{
				// target is accessible -> goto target
				if	($this->_checkGoto($_GET["target"]))
				{
//echo "B";
					ilUtil::redirect(ILIAS_HTTP_PATH.
						"/goto.php?target=".$_GET["target"]);
				}
				else	// target is not accessible -> login
				{
//echo "C";
					$this->showLogin();
				}
			}
			
			// just go to public section
			if (empty($_GET["ref_id"]))
			{
				$_GET["ref_id"] = ROOT_FOLDER_ID;
			}
			$ilCtrl->initBaseClass("");
			$ilCtrl->setCmd("frameset");
			$start_script = "repository.php";
		}
		else
		{
			if	(!$this->_checkGoto($_GET["target"]))
			{
				// message if target given but not accessible
				if ($_GET["target"] != "")
				{
					$tarr = explode("_", $_GET["target"]);
					if ($tarr[0] != "pg" && $tarr[0] != "st" && $tarr[1] > 0)
					{
						sendInfo(sprintf($lng->txt("msg_no_perm_read_item"),
							ilObject::_lookupTitle(ilObject::_lookupObjId($tarr[1]))), true);
					}
				}

				// show personal desktop
				$ilCtrl->initBaseClass("ilPersonalDesktopGUI");
				$start_script = "ilias.php";
			}
			else
			{
//echo "3";
				ilUtil::redirect(ILIAS_HTTP_PATH.
					"/goto.php?target=".$_GET["target"]);
			}
		}
		
		include($start_script);
	}
	
	function _checkGoto($a_target)
	{
		global $objDefinition;
		
		if ($a_target == "")
		{
			return false;
		}

		$t_arr = explode("_", $_GET["target"]);
		$type = $t_arr[0];
		
		if ($type == "git")
		{
			$type = "glo";
		}
		
		if ($type == "pg" | $type == "st")
		{
			$type = "lm";
		}

		$class = $objDefinition->getClassName($type);
		if ($class == "")
		{
			return false;
		}
		$location = $objDefinition->getLocation($type);
		$full_class = "ilObj".$class."Access";
		include_once($location."/class.".$full_class.".php");
		
		return call_user_func(array($full_class, "_checkGoto"),
			$a_target);
	}

}
?>