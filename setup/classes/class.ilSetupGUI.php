<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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
* Setup GUI class
*
* class to setup ILIAS first and maintain the ini-settings and the database
*
* @author	Sascha Hofmann <shofmann@databay.de>
* @version	$Id$
* @package	ilias-setup
*/

require_once "class.ilSetup.php";

class ilSetupGUI extends ilSetup
{
	var $tpl;
	var $lng;
	var $log;
	
	var $btn_prev_on = false;
	var	$btn_prev_cmd;
	var	$btn_prev_lng;

	var $btn_next_on = false;
	var	$btn_next_cmd;
	var	$btn_next_lng;

	var $revision;
	var $version;
	var $lang;

	var $cmd;						// command variable
	var $display_mode = "view";		// view mode (setup or details)

	// Construcotr
	function ilSetupGUI()
	{
		global $tpl, $lng;

		$this->tpl =& $tpl;
		$this->lng =& $lng;

		//CVS - REVISION - DO NOT MODIFY
		$this->revision = "$Revision$";
		$this->version = "2.".substr(substr($this->revision,2),0,-2);
		$this->lang = $this->lng->lang_key;

		// init setup
		$this->ilSetup($_SESSION["auth"],$_SESSION["access_mode"]);

		// init client object if exists
		$client_id = ($_GET["client_id"]) ? $_GET["client_id"] : $_SESSION["ClientId"];

		// for security
		if (!$this->isAdmin() and $client_id != $_SESSION["ClientId"])
		{
			$client_id = $_SESSION["ClientId"];
		}

		$this->ini_client_exists = $this->newClient($client_id);
		$this->client->status = $this->getStatus();

		// determine command
		if (($this->cmd = $_GET["cmd"]) == "gateway")
		{
			// surpress warning if POST is not set
			@$this->cmd = key($_POST["cmd"]);
		}

		// determine display mode here
		// TODO: depending on previous setting (session)
		// OR switch to 'setup'-mode if someone logs in as client and client's setup wasn't finished (-> entry in settings table doesn't exists)
		if ($this->isAuthenticated() and !$this->client->status["finish"]["status"] and $this->cmd != "clientlist" and $this->cmd != "")
		{
			$this->setDisplayMode("setup");
		}
		else
		{
			$this->setDisplayMode($_SESSION["display_mode"]);
		}
//////////////////////////////////////////////
// output starts here

		// display header
		$this->displayHeader();

		// main cmd handling
		if (!$this->isAuthenticated() or !$this->isInstalled())
		{
			// check for first time installation or migrate an old one first
			if (!$this->isInstalled() or !($this->ini->readVariable("clients","path")))
			{
				$this->cmdInstall();
			}
			else
			{
				$this->displayLogin();
			}
		}
		else
		{
			if ($this->isAdmin())
			{
				$this->cmdAdmin();
			}
			else
			{
				$this->cmdClient();
			}
		}

		if (DEBUG)
		{
			echo "cmd: ".$this->cmd." | access: ".$this->access_mode." | display: ".$this->display_mode;
			var_dump($this->client->status);
		}

		// display footer
		$this->displayFooter();

// end output
/////////////////////////////////////////////

	}  // end constructor

////////////////////////////////////////////
// cmd subsets

	// process fresh installation commands
	function cmdInstall()
	{
		switch ($this->cmd)
		{
			case NULL:
			case "preliminaries":
				$this->checkPreliminaries();
				$this->displayPreliminaries();
				break;

			case "install":
				$this->displayMasterSetup();
				break;

			default:
				$this->displayError($this->lng->txt("unknown_command"));
				break;
		}
	}
	
	// process admin commands
	function cmdAdmin()
	{
		switch ($this->cmd)
		{
			case NULL:
			case "clientlist":
				$this->setDisplayMode("view");
				$this->displayClientList();
				break;
			
			case "changepassword":
				$this->setDisplayMode("view");
				$this->changeMasterPassword();
				break;

			case "mastersettings":
				$this->setDisplayMode("view");
				$this->changeMasterSettings();
				break;

			case "changedefault":
				$this->changeDefaultClient();
				break;	

			case "newclient":
				$this->cmd = "ini";
				$this->setDisplayMode("setup");
				$this->ini_client_exists = $this->newClient();
				$this->displayIni();
				break;	

			case "startup":
				$this->setDisplayMode("setup");
				$this->ini_client_exists = $this->newClient();
				$this->displayStartup();
				break;

			case "delete":
				$this->setDisplayMode("view");
				$this->displayDeleteConfirmation();
				break;

			default:
				$this->cmdClient();
				break;
		}
	}
	
	// process setup/client commands
	function cmdClient()
	{
		switch ($this->cmd)
		{
			case NULL:
			case "view":
				if ($this->client->db_installed)
				{
					$this->setDisplayMode("view"); 
					$this->displayClientOverview();
				}
				else
				{
					$this->cmd = "db";
					$this->displayDatabase();
				}
				break;
				
			case "ini":
				// only allow access to ini if db not exists yet
				if ($this->client->db_installed)
				{
					$this->cmd = "db";
					$this->displayDatabase();
				}
				else
				{
					$this->displayIni();
				}
				break;

			case "db":
				$this->displayDatabase();
				break;
	
			case "lang":
				if (!$this->client->status["finish"]["status"])
				{
					$this->jumpToFirstUnfinishedSetupStep();
				}
				else
				{
					$this->displayLanguages();
				}
				break;

			case "contact":
				if (!$this->client->status["finish"]["status"])
				{
					$this->jumpToFirstUnfinishedSetupStep();
				}
				else
				{
					$this->displayContactData();
				}
				break;
	
			case "nic":
				if (!$this->client->status["finish"]["status"])
				{
					$this->jumpToFirstUnfinishedSetupStep();
				}
				else
				{
					$this->displayNIC();
				}
				break;
	
			case "finish":
				if (!$this->client->db_installed)
				{
					$this->cmd = "db";
					$this->displayDatabase();
				}
				else
				{
					$this->displayFinishSetup();
				}
				break;

			case "changeaccess":
				$this->changeAccessMode($_GET["back"]);
				break;

			case "logout":
				$this->displayLogout();
				break;

			case "login":
				session_destroy();
				header("Location: ".ILIAS_HTTP_PATH."/login.php?client_id=".$this->client->getId());
				exit();
				break;

			default:
				$this->displayError($this->lng->txt("unknown_command"));
				break;
		}
	}

// end cmd subsets 
////////////////////////////////////////////

	function displayClientOverview()
	{		
		$this->checkDisplayMode();
		/*$this->tpl->addBlockFile("CONTENT","content","tpl.clientview.html");
		// display tabs
		include "./include/inc.client_tabs.php";
		$this->tpl->setVariable("TXT_HEADER","Client details of \"".$this->client->getName()."\" (Id: ".$this->client->getId().")");	
		*/		
		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.client_overview.html");

		if ($this->client->db_installed)
		{
			$settings = $this->client->getAllSettings();
		}
		
		$txt_no_database = $this->lng->txt("no_database");

		$access_status = ($this->client->status["access"]["status"]) ? "online" : "disabled";
		$access_button = ($this->client->status["access"]["status"]) ? "disable" : "enable";
		$access_link = "&nbsp;&nbsp;[<a href=\"setup.php?cmd=changeaccess&client_id=".$this->client->getId()."&back=view\">".$this->lng->txt($access_button)."</a>]";
		
		// basic data
		$this->tpl->setVariable("TXT_BASIC_DATA", $this->lng->txt("client_info"));
		$this->tpl->setVariable("TXT_INST_NAME", $this->lng->txt("inst_name"));
		$this->tpl->setVariable("TXT_INST_ID", $this->lng->txt("ilias_nic_id"));
		$this->tpl->setVariable("TXT_CLIENT_ID2", $this->lng->txt("client_id"));
		$this->tpl->setVariable("TXT_DB_VERSION", $this->lng->txt("db_version"));
		$this->tpl->setVariable("TXT_ACCESS_STATUS", $this->lng->txt("access_status"));
		
		$this->tpl->setVariable("TXT_SERVER_DATA", $this->lng->txt("server_info"));
		$this->tpl->setVariable("TXT_ILIAS_VERSION", $this->lng->txt("ilias_version"));
		$this->tpl->setVariable("TXT_HOSTNAME", $this->lng->txt("host"));
		$this->tpl->setVariable("TXT_IP_ADDRESS", $this->lng->txt("ip_address"));
		$this->tpl->setVariable("TXT_SERVER_PORT", $this->lng->txt("port"));
		$this->tpl->setVariable("TXT_SERVER_SOFTWARE", $this->lng->txt("server_software"));
		$this->tpl->setVariable("TXT_HTTP_PATH", $this->lng->txt("http_path"));
		$this->tpl->setVariable("TXT_ABSOLUTE_PATH", $this->lng->txt("absolute_path"));
		$this->tpl->setVariable("TXT_DEFAULT_LANGUAGE", $this->lng->txt("default_language"));
		$this->tpl->setVariable("TXT_FEEDBACK_RECIPIENT", $this->lng->txt("feedback_recipient"));
		$this->tpl->setVariable("TXT_ERROR_RECIPIENT", $this->lng->txt("error_recipient"));

		// pathes
		$this->tpl->setVariable("TXT_SOFTWARE", $this->lng->txt("3rd_party_software"));
		$this->tpl->setVariable("TXT_CONVERT_PATH", $this->lng->txt("path_to_convert"));
		$this->tpl->setVariable("TXT_ZIP_PATH", $this->lng->txt("path_to_zip"));
		$this->tpl->setVariable("TXT_UNZIP_PATH", $this->lng->txt("path_to_unzip"));
		$this->tpl->setVariable("TXT_JAVA_PATH", $this->lng->txt("path_to_java"));
		$this->tpl->setVariable("TXT_HTMLDOC_PATH", $this->lng->txt("path_to_htmldoc"));

		///////////////////////////////////////////////////////////
		// display formula data

		// client data
		$this->tpl->setVariable("INST_ID",($this->client->db_installed) ? $settings["inst_id"] : $txt_no_database);
		$this->tpl->setVariable("CLIENT_ID2",$this->client->getId());
		$this->tpl->setVariable("INST_NAME",($this->client->getName()) ? $this->client->getName() : "&lt;".$this->lng->txt("no_client_name")."&gt;");
		$this->tpl->setVariable("INST_INFO",$this->client->getDescription());
		$this->tpl->setVariable("DB_VERSION",($this->client->db_installed) ? $settings["db_version"] : $txt_no_database);
		$this->tpl->setVariable("ACCESS_STATUS",$this->lng->txt($access_status).$access_link);

		// server data
		$this->tpl->setVariable("HTTP_PATH",ILIAS_HTTP_PATH);
		$this->tpl->setVariable("ABSOLUTE_PATH",ILIAS_ABSOLUTE_PATH);
		$this->tpl->setVariable("HOSTNAME", $_SERVER["SERVER_NAME"]);
		$this->tpl->setVariable("SERVER_PORT", $_SERVER["SERVER_PORT"]);
		$this->tpl->setVariable("SERVER_ADMIN", $_SERVER["SERVER_ADMIN"]);	// not used
		$this->tpl->setVariable("SERVER_SOFTWARE", $_SERVER["SERVER_SOFTWARE"]);
		$this->tpl->setVariable("IP_ADDRESS", $_SERVER["SERVER_ADDR"]);
		$this->tpl->setVariable("ILIAS_VERSION",($this->client->db_installled) ? $settings["ilias_version"] : $txt_no_database);

		$this->tpl->setVariable("FEEDBACK_RECIPIENT",($this->client->db_installed) ? $settings["feedback_recipient"] : $txt_no_database);
		$this->tpl->setVariable("ERROR_RECIPIENT",($this->client->db_installed) ? $settings["error_recipient"] : $txt_no_database);

		// pathes to tools
		$not_set = $this->lng->txt("path_not_set");
				
		$convert = $this->ini->readVariable("tools","convert");
		$zip = $this->ini->readVariable("tools","zip");
		$unzip = $this->ini->readVariable("tools","unzip");
		$java = $this->ini->readVariable("tools","java");
		$htmldoc = $this->ini->readVariable("tools","htmldoc");
		
		$this->tpl->setVariable("CONVERT_PATH",($conver) ? $convert : $not_set);
		$this->tpl->setVariable("ZIP_PATH",($zip) ? $zip : $not_set);
		$this->tpl->setVariable("UNZIP_PATH",($unzip) ? $unzip : $not_set);
		$this->tpl->setVariable("JAVA_PATH",($java) ? $java : $not_set);
		$this->tpl->setVariable("HTMLDOC_PATH",($htmldoc) ? $htmldoc : $not_set);

		$this->tpl->parseCurrentBlock();
		
		$this->displayStatusPanel();
	}


	function setDisplayMode($a_mode)
	{
		// security
		if ($a_mode != "view" and $a_mode != "setup")
		{
			return false;
		}

		$this->display_mode = $a_mode;
		$_SESSION["display_mode"] = $this->display_mode;
		
		return true;
	}
	
	function displayHeader()
	{
		$languages = $this->lng->getLanguages();
		
		foreach ($languages as $lang_key)
		{
			$this->tpl->setCurrentBlock("languages");
			$this->tpl->setVariable("LINK_LANG", "./setup.php?cmd=".$this->cmd."&amp;lang=".$lang_key);
			$this->tpl->setVariable("LANG_NAME", $this->lng->txt("lang_".$lang_key));
			$this->tpl->setVariable("LANG_ICON", $lang_key);
			$this->tpl->setVariable("BORDER", 0);
			$this->tpl->setVariable("VSPACE", 0);
			$this->tpl->parseCurrentBlock();
		}
		
		if ($this->cmd != "logout" and $this->isInstalled())
		{
			// add client link
			if ($this->isAdmin())
			{
				if ($this->display_mode != "setup")
				{
					$this->tpl->setCurrentBlock("add_client");
					$this->tpl->setVariable("TXT_ADD_CLIENT",ucfirst($this->lng->txt("new")));
					$this->tpl->parseCurrentBlock();
				}
	
				// client list link
				$this->tpl->setCurrentBlock("display_list");
				$this->tpl->setVariable("TXT_LIST",ucfirst($this->lng->txt("list")));
				$this->tpl->parseCurrentBlock();
			
				// edit pathes link
				$this->tpl->setCurrentBlock("edit_pathes");
				$this->tpl->setVariable("TXT_EDIT_PATHES",$this->lng->txt("pathes"));
				$this->tpl->parseCurrentBlock();

				// change password link
				$this->tpl->setCurrentBlock("change_password");
				$this->tpl->setVariable("TXT_CHANGE_PASSWORD",ucfirst($this->lng->txt("password")));
				$this->tpl->parseCurrentBlock();
			}

			// logout link
			if ($this->isAuthenticated())
			{
				$this->tpl->setCurrentBlock("logout");
				$this->tpl->setVariable("TXT_LOGOUT",$this->lng->txt("logout"));
				$this->tpl->parseCurrentBlock();
			}
		}

		$this->tpl->setVariable("PAGETITLE","Setup");
		$this->tpl->setVariable("LOCATION_STYLESHEET","./templates/blueshadow.css");
		$this->tpl->setVariable("LOCATION_JAVASCRIPT","../templates/default");
		$this->tpl->setVariable("TXT_SETUP", "ILIAS3 - ".$this->lng->txt("setup"));
		$this->tpl->setVariable("VERSION", $this->version);
		$this->tpl->setVariable("TXT_VERSION", $this->lng->txt("version"));
		$this->tpl->setVariable("LANG", $this->lang);
	}

	function displayFooter()
	{
		// footer (not really)
		
		if ($this->cmd != "logout")
		{
			if ($this->ini_ilias_exists and $this->display_mode == "setup" and isset($_SESSION["ClientId"]))
			{
				$this->tpl->setVariable("TXT_ACCESS_MODE","(".$this->lng->txt("client_id").": ".$this->client->getId().")");
			}
			elseif ($this->isAdmin())
			{
				$this->tpl->setVariable("TXT_ACCESS_MODE","(".$this->lng->txt("root_access").")");
			}
		
			$this->displayNavButtons();
		}
		
		$this->tpl->show();
	}

	function displayNavButtons()
	{
		if (!$this->btn_prev_on and !$this->btn_next_on)
		{
			return false;
		}
		
		$this->tpl->addBlockFile("NAVBUTTONS","navbuttons","tpl.navbuttons.html");

		$this->tpl->setVariable("FORMACTION_BUTTONS","setup.php?lang=".$this->lang."&cmd=gateway");

		if ($this->btn_prev_on)
		{
			$this->tpl->setCurrentBlock("btn_back");
			$this->tpl->setVariable("TXT_PREV", $this->btn_prev_lng);	
			$this->tpl->setVariable("CMD_PREV", $this->btn_prev_cmd);	
			$this->tpl->parseCurrentBlock();
		}
		
		if ($this->btn_next_on)
		{
			$this->tpl->setCurrentBlock("btn_forward");
			$this->tpl->setVariable("TXT_NEXT", $this->btn_next_lng);
			$this->tpl->setVariable("CMD_NEXT", $this->btn_next_cmd);	
			$this->tpl->parseCurrentBlock();
		}
		
		return true;
	}

	function SetButtonPrev($a_cmd = 0,$a_lng = 0)
	{
		$this->btn_prev_on = true;
		$this->btn_prev_cmd = ($a_cmd) ? $a_cmd : "gateway";
		$this->btn_prev_lng = ($a_lng) ? $this->lng->txt($a_lng) : "<< ".$this->lng->txt("prev");
	}

	function SetButtonNext($a_cmd,$a_lng = 0)
	{
		$this->btn_next_on = true;
		$this->btn_next_cmd = ($a_cmd) ? $a_cmd : "gateway";
		$this->btn_next_lng = ($a_lng) ? $this->lng->txt($a_lng) : $this->lng->txt("next")." >>";
	}

	function displayPreliminaries()
	{
		$OK = "<font color=\"green\"><strong>OK</strong></font>";
		$FAILED = "<strong><font color=\"red\">FAILED</font></strong>";
		
		$this->tpl->addBlockFile("CONTENT","content","tpl.preliminaries.html");
		
		$this->tpl->setVariable("TXT_SETUP_TITLE",$this->lng->txt("ilias_setup"));
		$this->tpl->setVariable("TXT_SETUP_WELCOME", $this->lng->txt("setup_welcome"));
		$this->tpl->setVariable("TXT_SETUP_INIFILE_DESC", $this->lng->txt("setup_inifile_desc"));
		$this->tpl->setVariable("TXT_SETUP_DATABASE_DESC", $this->lng->txt("setup_database_desc"));
		$this->tpl->setVariable("TXT_SETUP_LANGUAGES_DESC", $this->lng->txt("setup_languages_desc"));
		$this->tpl->setVariable("TXT_SETUP_PASSWORD_DESC", $this->lng->txt("setup_password_desc"));		
		$this->tpl->setVariable("TXT_SETUP_NIC_DESC", $this->lng->txt("setup_nic_desc"));	
	
		$server_os = php_uname();
		$server_web = $_SERVER["SERVER_SOFTWARE"];
		$environment = $this->lng->txt("env_using")." ".$server_os." <br/>".$this->lng->txt("with")." ".$server_web;
		
		if ((stristr($server_os,"linux") || stristr($server_os,"windows")) && stristr($server_web,"apache"))
		{
			$env_comment = $this->lng->txt("env_ok");		
		}
		else
		{
			$env_comment = "<font color=\"red\">".$this->lng->txt("env_warning")."</font>";
		}
			
		$this->tpl->setVariable("TXT_ENV_TITLE", $this->lng->txt("environment"));
		$this->tpl->setVariable("TXT_ENV_INTRO", $environment);
		$this->tpl->setVariable("TXT_ENV_COMMENT", $env_comment);	
		
		$this->tpl->setVariable("TXT_PRE_TITLE", $this->lng->txt("preliminaries"));
		$this->tpl->setVariable("TXT_PRE_INTRO", $this->lng->txt("pre_intro"));

		// display phpversion
		$this->tpl->setCurrentBlock("preliminary");
		$this->tpl->setVariable("TXT_PRE", $this->lng->txt("pre_php_version").": ".$arCheck["php"]["version"]);

		if ($this->preliminaries_result["php"]["status"] == true)
		{
			$this->tpl->setVariable("STATUS_PRE", $OK);
		}
		else
		{
			$this->tpl->setVariable("STATUS_PRE", $FAILED);
			$this->tpl->setVariable("COMMENT_PRE", $this->preliminaries_result["php"]["comment"]);
		}

		$this->tpl->parseCurrentBlock();
	
		// check if ilias3 folder is writable
		$this->tpl->setCurrentBlock("preliminary");
		$this->tpl->setVariable("TXT_PRE", $this->lng->txt("pre_folder_write"));

		if ($this->preliminaries_result["root"]["status"] == true)
		{
			$this->tpl->setVariable("STATUS_PRE", $OK);
		}
		else
		{
			$this->tpl->setVariable("STATUS_PRE", $FAILED);
			$this->tpl->setVariable("COMMENT_PRE", $this->preliminaries_result["root"]["comment"]);
		}
		$this->tpl->parseCurrentBlock();
		
		// check if ilias3 can create new folders
		$this->tpl->setCurrentBlock("preliminary");
		$this->tpl->setVariable("TXT_PRE", $this->lng->txt("pre_folder_create"));

		if ($this->preliminaries_result["create"]["status"] == true)
		{
			$this->tpl->setVariable("STATUS_PRE", $OK);
		}
		else
		{
			$this->tpl->setVariable("STATUS_PRE", $FAILED);
			$this->tpl->setVariable("COMMENT_PRE", $this->preliminaries_result["create"]["comment"]);
		}

		$this->tpl->parseCurrentBlock();

		// check cookies
		$this->tpl->setCurrentBlock("preliminary");
		$this->tpl->setVariable("TXT_PRE", $this->lng->txt("pre_cookies_enabled"));

		if ($this->preliminaries_result["cookies"]["status"] == true)
		{
			$this->tpl->setVariable("STATUS_PRE", $OK);
		}
		else
		{
			$this->tpl->setVariable("STATUS_PRE", $FAILED);
			$this->tpl->setVariable("COMMENT_PRE", $this->preliminaries_result["cookies"]["comment"]);
		}

		$this->tpl->parseCurrentBlock();

		// check javascript
		$this->tpl->setCurrentBlock("preliminary_js");
		$this->tpl->setVariable("TXT_PRE", $this->lng->txt("pre_javascript_enabled"));
		$this->tpl->setVariable("STATUS_PRE", $FAILED);
		$this->tpl->setVariable("COMMENT_PRE", "You may run ILIAS without Javascript enabled.");
		$this->tpl->parseCurrentBlock();

		// summary
		if ($this->preliminaries === true)
		{
			$cmd = "install";

			$btn_text = ($this->cmd == "preliminaries") ? "" : "installation";
			$this->setButtonNext($cmd,$btn_text);
		}
		else
		{
			$this->tpl->setCurrentBlock("premessage");
			$this->tpl->setVariable("TXT_PRE_ERR", $this->lng->txt("pre_error"));
			$this->tpl->parseCurrentBlock();
		}
	}
	
	function displayMasterSetup()
	{
		if ($_POST["form"])
		{
			if (!$this->checkDataDirSetup($_POST["form"]))
			{
				$this->raiseError($this->lng->txt($this->getError()),$this->error_obj->MESSAGE);
			}
	
			if (!$this->checkLogSetup($_POST["form"]))
			{
				$this->raiseError($this->lng->txt($this->getError()),$this->error_obj->MESSAGE);
			}

			if (!$this->checkToolsSetup($_POST["form"]))
			{
				$this->raiseError($this->lng->txt($this->getError()),$this->error_obj->MESSAGE);
			}
			
			if (!$this->checkPasswordSetup($_POST["form"]))
			{
				$this->raiseError($this->lng->txt($this->getError()),$this->error_obj->MESSAGE);
			}

			if (!$this->saveMasterSetup($_POST["form"]))
			{
				$this->raiseError($this->lng->txt($this->getError()),$this->error_obj->MESSAGE);
			}			
			
			sendInfo("settings_saved",true);
			
			header("Location: setup.php?cmd=startup");
			exit();
		}

		$this->tpl->addBlockFile("CONTENT","content","tpl.std_layout.html");

		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.form_mastersetup.html");

		$this->tpl->setVariable("FORMACTION", "setup.php?lang=".$this->lang."&cmd=gateway");
		
		// for checkboxes & radio buttons
		$checked = "checked=\"checked\"";

		// general
		$this->tpl->setVariable("TXT_HEADER", $this->lng->txt("setup_basic_settings"));
		$this->tpl->setVariable("SUBMIT_CMD", "install");
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_first_install")."<br/>".$this->lng->txt("info_text_pathes"));
		
		if ($this->safe_mode)
		{
			$this->tpl->setVariable("SAFE_MODE_STYLE", " class=\"message\" ");
			$this->tpl->setVariable("TXT_SAFE_MODE_INFO", $this->lng->txt("safe_mode_enabled"));
		}
		else
		{
			$this->tpl->setVariable("TXT_SAFE_MODE_INFO", "");
		}

		// datadir
		$this->tpl->setCurrentBlock("setup_datadir");
		$this->tpl->setVariable("TXT_DATADIR_TITLE", $this->lng->txt("main_datadir_outside_webspace"));
		$this->tpl->setVariable("TXT_DATADIR_PATH", $this->lng->txt("datadir_path"));
		$this->tpl->setVariable("TXT_DATADIR_COMMENT1", $this->lng->txt("datadir_path_comment1"));
		$this->tpl->setVariable("TXT_CREATE", $this->lng->txt("create"));
		// values
		$this->tpl->setVariable("DATADIR_PATH", $_SESSION["error_post_vars"]["form"]["datadir_path"]);
		//$chk_datadir_path = ($_SESSION["error_post_vars"]["form"]["chk_datadir_path"]) ? "CHK_DATADIR_PATH_TARGET" : "CHK_DATADIR_PATH_CREATE";
		$chk_datadir_path = ($_SESSION["error_post_vars"]["form"]["chk_datadir_path"]) ? $checked : "";
		$this->tpl->setVariable("CHK_DATADIR_PATH",$chk_datadir_path);
		$this->tpl->parseCurrentBlock();
		
		// logging
		$this->tpl->setCurrentBlock("setup_log");
		$this->tpl->setVariable("TXT_LOG_TITLE", $this->lng->txt("logging"));
		$this->tpl->setVariable("TXT_LOG_PATH", $this->lng->txt("log_path"));
		$this->tpl->setVariable("TXT_LOG_COMMENT", $this->lng->txt("log_path_comment"));
		$this->tpl->setVariable("TXT_DISABLE_LOGGING", $this->lng->txt("disable_logging"));
		// values
		$this->tpl->setVariable("LOG_PATH", $_SESSION["error_post_vars"]["form"]["log_path"]);
		$chk_log_path = ($_SESSION["error_post_vars"]["form"]["chk_log_status"]) ? $checked : "";
		$this->tpl->setVariable("CHK_LOG_STATUS",$chk_log_path);
		$this->tpl->parseCurrentBlock();

		// tools
		$this->tpl->setCurrentBlock("setup_tools");
		$this->tpl->setVariable("TXT_DISABLE_CHECK", $this->lng->txt("disable_check"));
		$this->tpl->setVariable("TXT_TOOLS_TITLE", $this->lng->txt("3rd_party_software"));
		$this->tpl->setVariable("TXT_CONVERT_PATH", $this->lng->txt("convert_path"));
		$this->tpl->setVariable("TXT_ZIP_PATH", $this->lng->txt("zip_path"));
		$this->tpl->setVariable("TXT_UNZIP_PATH", $this->lng->txt("unzip_path"));
		$this->tpl->setVariable("TXT_JAVA_PATH", $this->lng->txt("java_path"));
		$this->tpl->setVariable("TXT_HTMLDOC_PATH", $this->lng->txt("htmldoc_path"));

		$this->tpl->setVariable("TXT_CONVERT_COMMENT", $this->lng->txt("convert_path_comment"));
		$this->tpl->setVariable("TXT_ZIP_COMMENT", $this->lng->txt("zip_path_comment"));
		$this->tpl->setVariable("TXT_UNZIP_COMMENT", $this->lng->txt("unzip_path_comment"));
		$this->tpl->setVariable("TXT_JAVA_COMMENT", $this->lng->txt("java_path_comment"));
		$this->tpl->setVariable("TXT_HTMLDOC_COMMENT", $this->lng->txt("htmldoc_path_comment"));
		// values
		$this->tpl->setVariable("CONVERT_PATH", $_SESSION["error_post_vars"]["form"]["convert_path"]);
		$this->tpl->setVariable("ZIP_PATH", $_SESSION["error_post_vars"]["form"]["zip_path"]);
		$this->tpl->setVariable("UNZIP_PATH", $_SESSION["error_post_vars"]["form"]["unzip_path"]);
		$this->tpl->setVariable("JAVA_PATH", $_SESSION["error_post_vars"]["form"]["java_path"]);
		$this->tpl->setVariable("HTMLDOC_PATH", $_SESSION["error_post_vars"]["form"]["htmldoc_path"]);

		$chk_convert_path = ($_SESSION["error_post_vars"]["form"]["chk_convert_path"]) ? $checked : "";
		$chk_zip_path = ($_SESSION["error_post_vars"]["form"]["chk_zip_path"]) ? $checked : "";
		$chk_unzip_path = ($_SESSION["error_post_vars"]["form"]["chk_unzip_path"]) ? $checked : "";
		$chk_java_path = ($_SESSION["error_post_vars"]["form"]["chk_java_path"]) ? $checked : "";
		$chk_htmldoc_path = ($_SESSION["error_post_vars"]["form"]["chk_htmldoc_path"]) ? $checked : "";

		$this->tpl->setVariable("CHK_CONVERT_PATH", $chk_convert_path);
		$this->tpl->setVariable("CHK_ZIP_PATH", $chk_zip_path);
		$this->tpl->setVariable("CHK_UNZIP_PATH", $chk_unzip_path);
		$this->tpl->setVariable("CHK_JAVA_PATH", $chk_java_path);
		$this->tpl->setVariable("CHK_HTMLDOC_PATH", $chk_htmldoc_path);
		$this->tpl->parseCurrentBlock();
		
		// setup password
		$this->tpl->setCurrentBlock("setup_pass");
		$this->tpl->setVariable("TXT_SETUP_PASS_TITLE", $this->lng->txt("setup_pass_title"));
		$this->tpl->setVariable("TXT_SETUP_PASS_COMMENT", $this->lng->txt("password_info"));
		$this->tpl->setVariable("TXT_SETUP_PASS", $this->lng->txt("setup_pass"));
		$this->tpl->setVariable("TXT_SETUP_PASS2", $this->lng->txt("setup_pass2"));
		// values
		$this->tpl->setVariable("SETUP_PASS", $_SESSION["error_post_vars"]["form"]["setup_pass"]);
		$this->tpl->setVariable("SETUP_PASS2", $_SESSION["error_post_vars"]["form"]["setup_pass2"]);
		$this->tpl->parseCurrentBlock();
		
		$this->setButtonPrev("preliminaries");

		if ($this->isInstalled())
		{
			$this->setButtonNext("list");
		}
	}
	
	function loginClient()
	{
		session_destroy();
		
		header ("Location: ".ILIAS_HTTP_PATH."/login.php?client_id=".$this->client->getId());
		exit();
	}
	
	function displayLogin()
	{
		$this->tpl->addBlockFile("CONTENT","content","tpl.std_layout.html");

		if ($_POST["form"])
		{
			// first check client login
			if (empty($_POST["form"]["admin_password"]))
			{
				if (!$this->loginAsClient($_POST["form"]))
				{
					if ($error_msg = $this->getError())
					{
						$this->raiseError($this->lng->txt($error_msg),$this->error_obj->MESSAGE);
					}
				}
			}
			else
			{
				if (!$this->loginAsAdmin($_POST["form"]["admin_password"]))
				{
					$this->raiseError($this->lng->txt("login_invalid"),$this->error_obj->MESSAGE);
				}
			}

			header ("Location: setup.php");
			exit();
		}

		// output
		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.form_login.html");
		$this->tpl->setVariable("FORMACTION", "setup.php?lang=".$this->lang."&cmd=gateway");
		$this->tpl->setVariable("TXT_HEADER",$this->lng->txt("setup_login"));

		$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_login"));

		$this->tpl->setVariable("TXT_REQUIRED_FIELDS", $this->lng->txt("required_field"));
		$this->tpl->setVariable("TXT_CLIENT_LOGIN",$this->lng->txt("client_login"));
		$this->tpl->setVariable("TXT_CLIENT_ID",$this->lng->txt("client_id"));
		$this->tpl->setVariable("TXT_USERNAME",ucfirst($this->lng->txt("username")));
		$this->tpl->setVariable("TXT_PASSWORD",ucfirst($this->lng->txt("password")));
		$this->tpl->setVariable("TXT_OR",strtoupper($this->lng->txt("or")));
		$this->tpl->setVariable("TXT_ADMIN_LOGIN",$this->lng->txt("admin_login"));
		$this->tpl->setVariable("TXT_ADMIN_PASSWORD",ucfirst($this->lng->txt("password")));
		$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt("submit"));
	}

	function displayClientList()
	{
		$_SESSION["ClientId"] = "";

		$clientlist = new ilClientList();
		$list = $clientlist->getClients();

		if (count($list) == 0)
		{
			sendInfo($this->lng->txt("no_clients_available"),true);
		}
		
		//prepare clientlist
		$data = array();
		$data["data"] = array();
		$data["ctrl"] = array();
		$data["cols"] = array("","name","id","login","details","status","access");

		foreach ($list as $key => $client)
		{
			// check status
			// TODO: aufr�umen!!!	
			$status_arr = $this->getStatus($client);

			if (!$status_arr["db"]["status"])
			{
				$status = $status_arr["db"]["comment"];
			}
			elseif (!$status_arr["finish"]["status"])
			{
				$status = $this->lng->txt("setup_not_finished");
			}
			else
			{
				$status = "<font color=\"green\"><strong>OK</strong></font>";
			}
			
			if ($status_arr["access"]["status"])
			{
				$access = "online";
			}
			else
			{
				$access = "disabled";
			}
			
			if ($key == $this->default_client)
			{
				$default = " checked=\"checked\"";
			}
			else
			{
				$default = "";
			}
			
			if ($status_arr["finish"]["status"] and $status_arr["access"]["status"])
			{
				$login = "<a href=\"../login.php?client_id=".$key."\">Login</a>";
			}
			else
			{
				$login = "&nbsp;";
			}

			$access_html = "<a href=\"setup.php?cmd=changeaccess&client_id=".$key."&back=clientlist\">".$this->lng->txt($access)."</a>";
			
			$client_name = ($client->getName()) ? $client->getName() : "&lt;".$this->lng->txt("no_client_name")."&gt;";
			
			//visible data part
			$data["data"][] = array(
							"default"		=> "<input type=\"radio\" name=\"form[default]\" value=\"".$key."\"".$default."/>",
							"name"			=> $client_name."#separator#".$client->getDescription(),
							"id"			=> $key,
							"login"			=> $login,
							"details"		=> "<a href=\"setup.php?cmd=view&client_id=".$key."\">Details</a>",
							"status"		=> $status,
							"access_html"	=> $access_html
							);

		}

		$this->maxcount = count($data["data"]);

		// sorting array
		$data["data"] = ilUtil::sortArray($data["data"],$_GET["sort_by"],$_GET["sort_order"]);

		$this->tpl->addBlockFile("CONTENT","content","tpl.clientlist.html");
		
		$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_list"));
		
		sendInfo();

		// load template for table
		$this->tpl->addBlockfile("CLIENT_LIST", "client_list", "tpl.table.html");
		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.obj_tbl_rows.html");

		// common
		$this->tpl->setVariable("TXT_HEADER",$this->lng->txt("available_clients"));
		$this->tpl->setVariable("FORMACTION","setup.php?cmd=gateway&lang=".$this->lang);

		// build table
		include_once "../classes/class.ilTableGUI.php";
		$tbl = new ilTableGUI();

		$num = 0;

		// title & header columns
		$tbl->setTitle(ucfirst($this->lng->txt("select_client")));

		foreach ($data["cols"] as $val)
		{
			$header_names[] = $this->lng->txt($val);
		}
		
		$tbl->setHeaderNames($header_names);

		//$header_params = array("ref_id" => $this->ref_id,"obj_id" => $this->id,"cmd" => "edit");
		$tbl->setHeaderVars($data["cols"],$header_params);
		$tbl->setColumnWidth(array("5%","30%","10%","10%","10%","20%","15%"));
		
		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit(0);
		$tbl->setOffset(0);
		$tbl->setMaxCount($maxcount);
		
		// SHOW VALID ACTIONS
		$this->tpl->setVariable("COLUMN_COUNTS",count($data["cols"]));
		
		// footer
		//$tbl->setFooter("tbl_footer");
		
		$tbl->disable("footer");
		$tbl->disable("icon");
		$tbl->disable("numinfo");
		
		// render table
		$tbl->render();

		if (is_array($data["data"][0]))
		{
			//table cell
			for ($i=0; $i < count($data["data"]); $i++)
			{
				$data2 = $data["data"][$i];
				$ctrl = $data["ctrl"][$i];

				// color changing
				$css_row = ilUtil::switchColor($i+1,"tblrow1","tblrow2");

				$this->tpl->setCurrentBlock("table_cell");
				$this->tpl->setVariable("CELLSTYLE", "tblrow1");
				$this->tpl->parseCurrentBlock();

				foreach ($data2 as $key => $val)
				{
					$this->tpl->setCurrentBlock("text");
					
					if ($key == "name")
					{
						$name_field = explode("#separator#",$val);
						$val = $name_field[0]."<br/><span class=\"subtitle\">".$name_field[1]."</span>";
					}

					$this->tpl->setVariable("TEXT_CONTENT", $val);					
					$this->tpl->parseCurrentBlock();

					$this->tpl->setCurrentBlock("table_cell");
					$this->tpl->parseCurrentBlock();

				} //foreach

				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("tbl_action_btn");
			$this->tpl->setVariable("TPLPATH",TPLPATH);			
			$this->tpl->setVariable("BTN_NAME","changedefault");
			$this->tpl->setVariable("BTN_VALUE",$this->lng->txt("set_default_client"));
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("tbl_action_row");
			$this->tpl->setVariable("TPLPATH",TPLPATH);			
			$this->tpl->setVariable("COLUMN_COUNTS","7");
			$this->tpl->parseCurrentBlock();

		}
	}

	function changeMasterSettings()
	{
		if ($_POST["form"])
		{
			if (!$this->checkLogSetup($_POST["form"]))
			{
				$this->raiseError($this->lng->txt($this->getError()),$this->error_obj->MESSAGE);
			}

			if (!$this->checkToolsSetup($_POST["form"]))
			{
				$this->raiseError($this->lng->txt($this->getError()),$this->error_obj->MESSAGE);
			}

			if (!$this->updateMasterSettings($_POST["form"]))
			{
				$this->raiseError($this->lng->txt($this->getError()),$this->error_obj->MESSAGE);
			}			
			
			sendInfo("settings_saved",true);
			header("Location: setup.php");
			exit;
		}

		$this->tpl->addBlockFile("CONTENT","content","tpl.std_layout.html");

		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.form_mastersetup.html");

		$this->tpl->setVariable("FORMACTION", "setup.php?lang=".$this->lang."&cmd=gateway");
		
		// for checkboxes & radio buttons
		$checked = "checked=\"checked\"";

		// general
		$this->tpl->setVariable("TXT_HEADER", $this->lng->txt("change_basic_settings"));
		$this->tpl->setVariable("SUBMIT_CMD", "mastersettings");
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_pathes"));
		
		if ($this->safe_mode)
		{
			$this->tpl->setVariable("SAFE_MODE_STYLE", " class=\"message\" ");
			$this->tpl->setVariable("TXT_SAFE_MODE_INFO", $this->lng->txt("safe_mode_enabled"));
		}
		else
		{
			$this->tpl->setVariable("TXT_SAFE_MODE_INFO", "");
		}
		// datadir
		$this->tpl->setCurrentBlock("display_datadir");
		$this->tpl->setVariable("TXT_DATADIR_TITLE", $this->lng->txt("main_datadir_outside_webspace"));
		$this->tpl->setVariable("TXT_DATADIR_PATH", $this->lng->txt("datadir_path"));
		$this->tpl->setVariable("DATADIR_PATH", $this->ini->readVariable("clients","datadir"));
		$this->tpl->setVariable("TXT_DATADIR_COMMENT2", $this->lng->txt("datadir_path_comment2"));
		$this->tpl->parseCurrentBlock();

		// logging
		$this->tpl->setCurrentBlock("setup_log");
		$this->tpl->setVariable("TXT_LOG_TITLE", $this->lng->txt("logging"));
		$this->tpl->setVariable("TXT_LOG_PATH", $this->lng->txt("log_path"));
		$this->tpl->setVariable("TXT_LOG_COMMENT", $this->lng->txt("log_path_comment"));
		$this->tpl->setVariable("TXT_DISABLE_LOGGING", $this->lng->txt("disable_logging"));
		// values
		if ($_SESSION["error_post_vars"])
		{
			$this->tpl->setVariable("LOG_PATH", $_SESSION["error_post_vars"]["form"]["log_path"]);
			$chk_log_status = ($_SESSION["error_post_vars"]["form"]["chk_log_status"]) ? $checked : "";
		}
		else
		{
			$this->tpl->setVariable("LOG_PATH",$this->ini->readVariable("log","path")."/".$this->ini->readVariable("log","file"));
			$chk_log_status = ($this->ini->readVariable("log","enabled")) ? "" : $checked;

		}
		
		$this->tpl->setVariable("CHK_LOG_STATUS",$chk_log_status);
		$this->tpl->parseCurrentBlock();

		// tools
		$this->tpl->setCurrentBlock("setup_tools");
		$this->tpl->setVariable("TXT_DISABLE_CHECK", $this->lng->txt("disable_check"));
		$this->tpl->setVariable("TXT_TOOLS_TITLE", $this->lng->txt("3rd_party_software"));
		$this->tpl->setVariable("TXT_CONVERT_PATH", $this->lng->txt("convert_path"));
		$this->tpl->setVariable("TXT_ZIP_PATH", $this->lng->txt("zip_path"));
		$this->tpl->setVariable("TXT_UNZIP_PATH", $this->lng->txt("unzip_path"));
		$this->tpl->setVariable("TXT_JAVA_PATH", $this->lng->txt("java_path"));
		$this->tpl->setVariable("TXT_HTMLDOC_PATH", $this->lng->txt("htmldoc_path"));

		$this->tpl->setVariable("TXT_CONVERT_COMMENT", $this->lng->txt("convert_path_comment"));
		$this->tpl->setVariable("TXT_ZIP_COMMENT", $this->lng->txt("zip_path_comment"));
		$this->tpl->setVariable("TXT_UNZIP_COMMENT", $this->lng->txt("unzip_path_comment"));
		$this->tpl->setVariable("TXT_JAVA_COMMENT", $this->lng->txt("java_path_comment"));
		$this->tpl->setVariable("TXT_HTMLDOC_COMMENT", $this->lng->txt("htmldoc_path_comment"));
		// values
		if ($_SESSION["error_post_vars"])
		{
			$this->tpl->setVariable("CONVERT_PATH", $_SESSION["error_post_vars"]["form"]["convert_path"]);
			$this->tpl->setVariable("ZIP_PATH", $_SESSION["error_post_vars"]["form"]["zip_path"]);
			$this->tpl->setVariable("UNZIP_PATH", $_SESSION["error_post_vars"]["form"]["unzip_path"]);
			$this->tpl->setVariable("JAVA_PATH", $_SESSION["error_post_vars"]["form"]["java_path"]);
			$this->tpl->setVariable("HTMLDOC_PATH", $_SESSION["error_post_vars"]["form"]["htmldoc_path"]);
		}
		else
		{
			$this->tpl->setVariable("CONVERT_PATH", $this->ini->readVariable("tools","convert"));
			$this->tpl->setVariable("ZIP_PATH", $this->ini->readVariable("tools","zip"));
			$this->tpl->setVariable("UNZIP_PATH",$this->ini->readVariable("tools","unzip"));
			$this->tpl->setVariable("JAVA_PATH",$this->ini->readVariable("tools","java"));
			$this->tpl->setVariable("HTMLDOC_PATH",$this->ini->readVariable("tools","htmldoc"));
		}

		$chk_convert_path = ($_SESSION["error_post_vars"]["form"]["chk_convert_path"]) ? $checked : "";
		$chk_zip_path = ($_SESSION["error_post_vars"]["form"]["chk_zip_path"]) ? $checked : "";
		$chk_unzip_path = ($_SESSION["error_post_vars"]["form"]["chk_unzip_path"]) ? $checked : "";
		$chk_java_path = ($_SESSION["error_post_vars"]["form"]["chk_java_path"]) ? $checked : "";
		$chk_htmldoc_path = ($_SESSION["error_post_vars"]["form"]["chk_htmldoc_path"]) ? $checked : "";

		$this->tpl->setVariable("CHK_LOG_STATUS", $chk_log_stauts);
		$this->tpl->setVariable("CHK_CONVERT_PATH", $chk_convert_path);
		$this->tpl->setVariable("CHK_ZIP_PATH", $chk_zip_path);
		$this->tpl->setVariable("CHK_UNZIP_PATH", $chk_unzip_path);
		$this->tpl->setVariable("CHK_JAVA_PATH", $chk_java_path);
		$this->tpl->setVariable("CHK_HTMLDOC_PATH", $chk_htmldoc_path);
		$this->tpl->parseCurrentBlock();
	}

	function displayIni()
	{
		$this->checkDisplayMode("create_new_client");
		
		// checkings
		if ($_POST["form"])
		{
			// check client name
			if (!$_POST["form"]["client_id"])
			{
				$this->raiseError($this->lng->txt("ini_no_client_id"),$this->error_obj->MESSAGE);
			}

			if (strlen($_POST["form"]["client_id"]) != strlen(urlencode(($_POST["form"]["client_id"]))))
			{
				$this->raiseError($this->lng->txt("ini_client_id_invalid"),$this->error_obj->MESSAGE);
			}			

			if (strlen($_POST["form"]["client_id"]) < 4)
			{
				$this->raiseError($this->lng->txt("ini_client_id_too_short"),$this->error_obj->MESSAGE);
			}

			if (strlen($_POST["form"]["client_id"]) > 32)
			{
				$this->raiseError($this->lng->txt("ini_client_id_too_long"),$this->error_obj->MESSAGE);
			}

			// check database
			if (!$_POST["form"]["db_host"])
			{
				$this->raiseError($this->lng->txt("ini_no_db_host"),$this->error_obj->MESSAGE);
			}

			if (!$_POST["form"]["db_name"])
			{
				$this->raiseError($this->lng->txt("ini_no_db_name"),$this->error_obj->MESSAGE);
			}
			
			if (!$_POST["form"]["db_user"])
			{
				$this->raiseError($this->lng->txt("ini_no_db_user"),$this->error_obj->MESSAGE);
			}

			// create new client object if not exists !!
			if (!$this->ini_client_exists)
			{
				$client_id = $_POST["form"]["client_id"];
				
				// check for existing client dir (only for newly created clients not renaming)
				if (!$this->ini_client_exists and file_exists(ILIAS_ABSOLUTE_PATH."/".ILIAS_WEB_DIR."/".$client_id))
				{
					$this->raiseError($this->lng->txt("ini_client_id_exists"),$this->error_obj->MESSAGE);
				}

				$this->newClient($client_id);
			}

			// save some old values
			$old_db_name = $this->client->getDbName();
			$old_client_id = $this->client->getId();			
			// set client data 
			$this->client->setId($_POST["form"]["client_id"]);
			$this->client->setDbHost($_POST["form"]["db_host"]);
			$this->client->setDbName($_POST["form"]["db_name"]);
			$this->client->setDbUser($_POST["form"]["db_user"]);
			$this->client->setDbPass($_POST["form"]["db_pass"]);
			$this->client->setDSN();
			
			// try to connect to database
			if (!$this->client->checkDatabaseHost())
			{
				$this->raiseError($this->lng->txt($this->client->getError()),$this->error_obj->MESSAGE);
			}
			
			// check if db exists
			$db_installed = $this->client->checkDatabaseExists();

			if ($db_installed and (!$this->ini_ilias_exists or ($this->client->getDbName() != $old_db_name)))
			{
				$_POST["form"]["db_name"] = $old_db_name;
				$message = ucfirst($this->lng->txt("database"))." \"".$this->client->getDbName()."\" ".$this->lng->txt("ini_db_name_exists");
				$this->raiseError($message,$this->error_obj->MESSAGE);
			}
			
			if ($this->ini_client_exists and $old_client_id != $this->client->getId())
			{
				$message = $this->lng->txt("ini_client_id_no_change");
				$this->raiseError($message,$this->error_obj->MESSAGE);
			}

			// all ok. create client.ini and save posted data
			if (!$this->ini_client_exists)
			{
				if ($this->saveNewClient())
				{
					sendInfo($this->lng->txt("settings_saved"));
					$this->client->status["ini"]["status"] = true;
				}
				else
				{
					$err = $this->getError();
					sendInfo($this->lng->txt("save_error").": ".$err);
					$this->client->status["ini"]["status"] = false;
					$this->client->status["ini"]["comment"] = $err;
				}
			}
			else
			{
				if ($this->client->ini->write())
				{
					sendInfo($this->lng->txt("settings_changed"));
					$this->client->status["ini"]["status"] = true;
				}
				else
				{
					$err = $this->client->ini->getError();
					sendInfo($this->lng->txt("save_error").": ".$err);
					$this->client->status["ini"]["status"] = false;
					$this->client->status["ini"]["comment"] = $err;
				}
			}
		}

		// output
		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.clientsetup_ini.html");
		
		$this->tpl->setVariable("FORMACTION", "setup.php?lang=".$this->lang."&cmd=gateway");
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		
		$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_ini"));
		
		// display default values, loaded valus or saved error values
		if ($_SESSION["error_post_vars"]["form"])
		{
			$this->tpl->setVariable("CLIENT_ID", $_SESSION["error_post_vars"]["form"]["client_id"]);
			$this->tpl->setVariable("DB_HOST", $_SESSION["error_post_vars"]["form"]["db_host"]);	
			$this->tpl->setVariable("DB_NAME", $_SESSION["error_post_vars"]["form"]["db_name"]);		
			$this->tpl->setVariable("DB_USER", $_SESSION["error_post_vars"]["form"]["db_user"]);		
			$this->tpl->setVariable("DB_PASS", $_SESSION["error_post_vars"]["form"]["db_pass"]);
		}
		else
		{
			$this->tpl->setVariable("CLIENT_ID", $this->client->getId());
			$this->tpl->setVariable("DB_HOST", $this->client->getDbHost());	
			$this->tpl->setVariable("DB_NAME", $this->client->getDbName());		
			$this->tpl->setVariable("DB_USER", $this->client->getDbUser());		
			$this->tpl->setVariable("DB_PASS", $this->client->getDbPass());		
		}

		$this->tpl->setVariable("TXT_CLIENT_HEADER", $this->lng->txt("inst_identification"));
		$this->tpl->setVariable("TXT_CLIENT_ID", $this->lng->txt("client_id"));
		$this->tpl->setVariable("TXT_DB_HEADER", $this->lng->txt("db_conn"));
		$this->tpl->setVariable("TXT_DB_TYPE", $this->lng->txt("db_type"));
		$this->tpl->setVariable("TXT_DB_HOST", $this->lng->txt("db_host"));
		$this->tpl->setVariable("TXT_DB_NAME", $this->lng->txt("db_name"));	
		$this->tpl->setVariable("TXT_DB_USER", $this->lng->txt("db_user"));
		$this->tpl->setVariable("TXT_DB_PASS", $this->lng->txt("db_pass"));

		if ($this->client->status["ini"]["status"])
		{
			$this->setButtonNext("db");
		}
		
		$this->checkPanelMode();
	}
	
	function displayError($a_message)
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.error.html");
		
		$this->tpl->setCurrentBlock("content");
		$this->tpl->setVariable("FORMACTION", $_SESSION["referer"]);
		$this->tpl->setVariable("TXT_BACK", $this->lng->txt("back"));
		$this->tpl->setVariable("ERROR_MESSAGE",($a_message));
		$this->tpl->parseCurrentBlock();
		
		//session_unregister("referer");
		//unset($_SESSION["message"]);

		$this->tpl->show();
		exit();
	}

	function displayLogout()
	{
		$this->tpl->addBlockFile("CONTENT","content","tpl.logout.html");

		session_destroy();

		$this->logged_out = true;
		$this->tpl->setVariable("TXT_HEADER",$this->lng->txt("logged_out"));		
		$this->tpl->setCurrentBlock("home_link");
		$this->tpl->setVariable("TXT_INDEX",$this->lng->txt("ilias_homepage"));
		$this->tpl->setVariable("LNK_INDEX",ILIAS_HTTP_PATH."/index.php");
		$this->tpl->parseCurrentBlock();
	}

	function displayProcessPanel()
	{
		$OK = "<font color=\"green\"><strong>OK</strong></font>";
		$FAILED = "<strong><font color=\"red\">FAILED</font></strong>";
		
		$steps = array();
		$steps = $this->getStatus();
		
		// remove access step
		unset($steps["access"]);
		
		$steps["ini"]["text"]		= $this->lng->txt("setup_process_step_ini");
		$steps["db"]["text"]		= $this->lng->txt("setup_process_step_db");
		$steps["lang"]["text"]		= $this->lng->txt("setup_process_step_lang");
		$steps["contact"]["text"]	= $this->lng->txt("setup_process_step_contact");
		$steps["nic"]["text"]		= $this->lng->txt("setup_process_step_nic");
		$steps["finish"]["text"]	= $this->lng->txt("setup_process_step_finish");
		
		$this->tpl->addBlockFile("PROCESS_MENU","process_menu","tpl.process_panel.html");

		$this->tpl->setVariable("TXT_SETUP_PROCESS_STATUS",$this->lng->txt("setup_process_status"));

		$num = 1;

		foreach ($steps as $key => $val)
		{
			$this->tpl->setCurrentBlock("menu_row");
			$this->tpl->setVariable("TXT_STEP",$this->lng->txt("step")." ".$num.": &nbsp;");
			$this->tpl->setVariable("TXT_ACTION",$val["text"]);
			$this->tpl->setVariable("IMG_ARROW", "spacer.gif");
			
			$num++;

			if ($this->cmd == $key and isset($this->cmd))
			{
				$this->tpl->setVariable("HIGHLIGHT", " style=\"font-weight:bold;\"");
				$this->tpl->setVariable("IMG_ARROW", "arrow_right.png");
			}
			
			$status = ($val["status"]) ? $OK : "";			
			
			$this->tpl->setVariable("TXT_STATUS",$status);
			$this->tpl->parseCurrentBlock();
		}
	}

	function displayStatusPanel()
	{
		$OK = "<font color=\"green\"><strong>OK</strong></font>";

		$this->tpl->addBlockFile("STATUS_PANEL","status_panel","tpl.status_panel.html");

		$this->tpl->setVariable("TXT_OVERALL_STATUS",$this->lng->txt("overall_status"));
		// display status
		if ($this->client->status)
		{
			foreach ($this->client->status as $key => $val)
			{
				$status = ($val["status"]) ? $OK : "&nbsp;";
				$this->tpl->setCurrentBlock("status_row");
				$this->tpl->setVariable("TXT_STEP",$key.":&nbsp;");
				$this->tpl->setVariable("TXT_STATUS",$status);
				$this->tpl->setVariable("TXT_COMMENT",$val["comment"]);
				$this->tpl->parseCurrentBlock();
			}
		}
	}
	
	function checkDisplayMode($a_title = "")
	{

		switch ($this->display_mode)
		{
			case "view":
				$this->tpl->addBlockFile("CONTENT","content","tpl.clientview.html");
				// display tabs
				include "./include/inc.client_tabs.php";
				$client_name = ($this->client->getName()) ? $this->client->getName() : $this->lng->txt("no_client_name");
				$this->tpl->setVariable("TXT_HEADER",$client_name." (".$this->lng->txt("client_id").": ".$this->client->getId().")");		
				break;
			
			case "setup":
				$this->tpl->addBlockFile("CONTENT","content","tpl.clientsetup.html");
				$this->tpl->setVariable("TXT_HEADER",$this->lng->txt($a_title));		
				break;

			default:
				$this->displayError($this->lng->txt("unknown_display_mode"));
				exit();
				break;
		}
	}

	function checkPanelMode()
	{
		switch ($this->display_mode)
		{
			case "view":
				$this->displayStatusPanel();				
				break;
			
			case "setup":
				$this->displayProcessPanel();
				break;
		}
	}

	function displayStartup()
	{
		$this->tpl->addBlockFile("CONTENT","content","tpl.clientsetup.html");
		
		$this->tpl->setVariable("TXT_INFO",$this->lng->txt("info_text_first_client"));
		$this->tpl->setVariable("TXT_HEADER",$this->lng->txt("setup_first_client"));
		
		$this->displayProcessPanel();
		
		$this->setButtonNext("ini");
	}

	function displayDatabase()
	{
		$this->checkDisplayMode("setup_database");

		include_once "../classes/class.ilDBUpdate.php";

		// checkings
		if ($_POST["form"]["db_flag"] == 1)
		{
			$message = "";
			
			//echo "hier";exit;

			if (!$this->client->db_installed)
			{
				if (!$this->client->db_exists)
				{
					$this->createDatabase();
				}
				
				if (!$this->installDatabase())
				{
					$message = $this->error;
					$this->client->status["db"]["status"] = false;
					$this->client->status["db"]["comment"] = "install_error!";
				}
				else
				{
					$message = "Database installed";
				}
			}
			else
			{
				// referencing db handler in language class
				$this->lng->setDbHandler($this->client->db);
				
				// run dbupdate
				$dbupdate = new ilDBUpdate($this->client->db);
				$dbupdate->applyUpdate();
			
				if ($dbupdate->updateMsg == "no_changes")
				{
					$message = "No changes. ".$this->lng->txt("database_is_uptodate");
				}
				else
				{
					foreach ($dbupdate->updateMsg as $row)
					{
						$message .= $this->lng->txt($row["msg"]).": ".$row["nr"]."<br/>";
					}
				}
			}
		}

		sendInfo($message);

		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.clientsetup_db.html");
		
		$this->tpl->setVariable("FORMACTION", "setup.php?lang=".$this->lang."&cmd=gateway");
		$this->tpl->setVariable("DB_HOST", $this->client->getDbHost());
		$this->tpl->setVariable("DB_NAME", $this->client->getDbName());		
		$this->tpl->setVariable("DB_USER", $this->client->getDbUser());		
		$this->tpl->setVariable("DB_PASS", $this->client->getDbPass());

		if ($this->client->db_installed)
		{
			// referencing db handler in language class
			$this->lng->setDbHandler($this->client->db);

			$dbupdate = new ilDBUpdate($this->client->db);

			if (!$db_status = $dbupdate->getDBVersionStatus())
			{
				$remark = "<font color=\"red\">".$this->lng->txt("database_needs_update").
								  " (".$this->lng->txt("database_version").": ".$dbupdate->currentVersion.
								  " ; ".$this->lng->txt("file_version").": ".$dbupdate->fileVersion.")</font>";
				$this->tpl->setVariable("TXT_INFO", $remark);
				
				$this->tpl->setCurrentBlock("btn_submit");
				$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("database_update"));
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->setVariable("TXT_INFO",$this->lng->txt("database_is_uptodate"));
				$this->client->status["db"]["status"] = true;
				$this->client->status["db"]["comment"] = "version ".$dbupdate->getCurrentVersion();
			}
		}
		else
		{
			$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("database_create"));
			$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_db"));
		}
		
		$this->tpl->parseCurrentBlock();
		
		$this->tpl->setVariable("TXT_SETUP_TITLE", $this->lng->txt("setup_database"));
		$this->tpl->setVariable("TXT_DB_HEADER", $this->lng->txt("db_conn"));
		$this->tpl->setVariable("TXT_DB_TYPE", $this->lng->txt("db_type"));
		$this->tpl->setVariable("TXT_DB_HOST", $this->lng->txt("db_host"));
		$this->tpl->setVariable("TXT_DB_NAME", $this->lng->txt("db_name"));	
		$this->tpl->setVariable("TXT_DB_USER", $this->lng->txt("db_user"));
		$this->tpl->setVariable("TXT_DB_PASS", $this->lng->txt("db_pass"));
		
		// only allow to return to ini if db not exists yet
		if (!$this->client->db_installed)
		{
			$this->setButtonPrev("ini");
		}
		
		if ($this->client->db_installed and $db_status)
		{
			$this->setButtonNext("lang");
		}
		
		$this->checkPanelMode();
	}
	
	function displayLanguages()
	{
		$this->checkDisplayMode("setup_languages");

		if (!$this->client->db_installed)
		{
			// programm should never come to this place
			$message = "No database found! Please install database first.";
			sendInfo($message);
		}
	
		// checkings
		if ($_POST["form"])
		{
			if (empty($_POST["form"]["lang_id"]))
			{
				$message = $this->lng->txt("lang_min_one_language");
				$this->raiseError($message,$this->error_obj->MESSAGE);
			}
			
			if (!in_array($_POST["form"]["lang_default"],$_POST["form"]["lang_id"]))
			{
				$message = $this->lng->txt("lang_not_installed_default");
				$this->raiseError($message,$this->error_obj->MESSAGE);
			}
			
			$this->lng->installLanguages($_POST["form"]["lang_id"]);
			$this->client->setDefaultLanguage($_POST["form"]["lang_default"]);
			$message = $this->lng->txt("languages_installed");
			sendInfo($message);
		}

		// output
		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.clientsetup_lang.html");

		$languages = $this->lng->getInstallableLanguages();
		$installed_langs = $this->lng->getInstalledLanguages();
		$default_lang = $this->client->getDefaultLanguage();
		
		$lang_count = count($installed_langs);
		
		$this->tpl->setVariable("TXT_LANG_HEADER", ucfirst($this->lng->txt("available_languages")));
		$this->tpl->setVariable("TXT_LANGUAGE", ucfirst($this->lng->txt("languages")));
		$this->tpl->setVariable("TXT_INSTALLED", ucfirst($this->lng->txt("installed")));
		$this->tpl->setVariable("TXT_DEFAULT", ucfirst($this->lng->txt("default")));

		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		
		$this->tpl->setVariable("TXT_SETUP_TITLE",ucfirst(trim($this->lng->txt("setup_languages"))));
		$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_lang"));
		
		if ($lang_count > 0)
		{
			$this->client->status["lang"]["status"] = true;
			$this->client->status["lang"]["comment"] = $lang_count." ".$this->lng->txt("languages_installed");
		}
		else
		{
			$this->client->status["lang"]["status"] = false;
			$this->client->status["lang"]["comment"] = $this->lng->txt("lang_none_installed");
		}

		foreach ($languages as $lang_key)
		{
			$this->tpl->setCurrentBlock("language_row");
			$this->tpl->setVariable("LANG_KEY", $lang_key);
			$this->tpl->setVariable("TXT_LANG", $this->lng->txt("lang_".$lang_key));

			if (in_array($lang_key,$installed_langs))
			{
				$this->tpl->setVariable("CHECKED", ("checked=\"checked\""));		
			}

			if ($lang_key == $default_lang)
			{
				$this->tpl->setVariable("DEFAULT", ("checked=\"checked\""));		
			}

			$this->tpl->parseCurrentBlock();
		}
		
		$this->setButtonPrev("db");
		
		if ($lang_count > 0)
		{
			$this->setButtonNext("contact");
		}
		
		$this->checkPanelMode();
	}
	
	function displayContactData()
	{
		$this->checkDisplayMode("setup_contact_data");
	
		$settings = $this->client->getAllSettings();

		// formular sent
		if ($_POST["form"])
		{
			//init checking var
			$form_valid = true;

			// check required fields
			if (empty($_POST["form"]["admin_firstname"]) or empty($_POST["form"]["admin_lastname"])
				or empty($_POST["form"]["admin_street"]) or empty($_POST["form"]["admin_zipcode"])
				or empty($_POST["form"]["admin_country"]) or empty($_POST["form"]["admin_city"])
				or empty($_POST["form"]["admin_phone"]) or empty($_POST["form"]["admin_email"])
				or empty($_POST["form"]["inst_name"]))
			{
				$form_valid = false;
				$message = $this->lng->txt("fill_out_required_fields");
				//$this->raiseError($message,$this->error_obj->MESSAGE);
				sendInfo($message);
			}
			
			// admin email
			if (!ilUtil::is_email($_POST["form"]["admin_email"]) and $form_valid)
			{
				$form_valid = false;
				$message = $this->lng->txt("input_error").": '".$this->lng->txt("email")."'<br/>".$this->lng->txt("email_not_valid");
				sendInfo($message);
				//$this->raiseError($message,$this->error_obj->MESSAGE);
			}

			if (!$form_valid)	//required fields not satisfied. Set formular to already fill in values
			{
		////////////////////////////////////////////////////////////
		// load user modified settings again
				// contact
				$settings["admin_firstname"] = ilUtil::prepareFormOutput($_POST["form"]["admin_firstname"],true);
				$settings["admin_lastname"] = ilUtil::prepareFormOutput($_POST["form"]["admin_lastname"],true);
				$settings["admin_title"] = ilUtil::prepareFormOutput($_POST["form"]["admin_title"],true);
				$settings["admin_position"] = ilUtil::prepareFormOutput($_POST["form"]["admin_position"],true);
				$settings["admin_institution"] = ilUtil::prepareFormOutput($_POST["form"]["admin_institution"],true);
				$settings["admin_street"] = ilUtil::prepareFormOutput($_POST["form"]["admin_street"],true);
				$settings["admin_zipcode"] = ilUtil::prepareFormOutput($_POST["form"]["admin_zipcode"],true);
				$settings["admin_city"] = ilUtil::prepareFormOutput($_POST["form"]["admin_city"],true);
				$settings["admin_country"] = ilUtil::prepareFormOutput($_POST["form"]["admin_country"],true);
				$settings["admin_phone"] = ilUtil::prepareFormOutput($_POST["form"]["admin_phone"],true);
				$settings["admin_email"] = ilUtil::prepareFormOutput($_POST["form"]["admin_email"],true);

				// client
				$settings["inst_name"] = ilUtil::prepareFormOutput($_POST["form"]["inst_name"],true);
				$settings["inst_info"] = ilUtil::prepareFormOutput($_POST["form"]["inst_info"],true);
				$settings["inst_institution"] = ilUtil::prepareFormOutput($_POST["form"]["inst_institution"],true);
			}
			else // all required fields ok
			{

		////////////////////////////////////////////////////////////
		// write new settings

				// contact
				$this->client->setSetting("admin_firstname",ilUtil::stripSlashes($_POST["form"]["admin_firstname"]));
				$this->client->setSetting("admin_lastname",ilUtil::stripSlashes($_POST["form"]["admin_lastname"]));
				$this->client->setSetting("admin_title",ilUtil::stripSlashes($_POST["form"]["admin_title"]));
				$this->client->setSetting("admin_position",ilUtil::stripSlashes($_POST["form"]["admin_position"]));
				$this->client->setSetting("admin_institution",ilUtil::stripSlashes($_POST["form"]["admin_institution"]));
				$this->client->setSetting("admin_street",ilUtil::stripSlashes($_POST["form"]["admin_street"]));
				$this->client->setSetting("admin_zipcode",ilUtil::stripSlashes($_POST["form"]["admin_zipcode"]));
				$this->client->setSetting("admin_city",ilUtil::stripSlashes($_POST["form"]["admin_city"]));
				$this->client->setSetting("admin_country",ilUtil::stripSlashes($_POST["form"]["admin_country"]));
				$this->client->setSetting("admin_phone",ilUtil::stripSlashes($_POST["form"]["admin_phone"]));
				$this->client->setSetting("admin_email",ilUtil::stripSlashes($_POST["form"]["admin_email"]));
				$this->client->setSetting("inst_institution",ilUtil::stripSlashes($_POST["form"]["inst_institution"]));

				// update client.ini
				$this->client->setName(ilUtil::stripSlashes($_POST["form"]["inst_name"]));
				$this->client->setDescription(ilUtil::stripSlashes($_POST["form"]["inst_info"]));
				$this->client->ini->write();

				// reload settings
				$settings = $this->client->getAllSettings();
				// feedback
				sendInfo($this->lng->txt("saved_successfully"));
			}
		}

		// output
		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.clientsetup_contact.html");

		// client values
		$this->tpl->setVariable("INST_NAME",ilUtil::prepareFormOutput(($this->client->getName()) ? $this->client->getName() : $this->client->getId()));
		$this->tpl->setVariable("INST_INFO",ilUtil::prepareFormOutput($this->client->getDescription()));
		$this->tpl->setVariable("INST_INSTITUTION",ilUtil::prepareFormOutput($settings["inst_institution"]));

		// contact values
		$this->tpl->setVariable("ADMIN_FIRSTNAME",ilUtil::prepareFormOutput($settings["admin_firstname"]));
		$this->tpl->setVariable("ADMIN_LASTNAME",ilUtil::prepareFormOutput($settings["admin_lastname"]));
		$this->tpl->setVariable("ADMIN_TITLE",ilUtil::prepareFormOutput($settings["admin_title"]));
		$this->tpl->setVariable("ADMIN_POSITION",ilUtil::prepareFormOutput($settings["admin_position"]));
		$this->tpl->setVariable("ADMIN_INSTITUTION",ilUtil::prepareFormOutput($settings["admin_institution"]));
		$this->tpl->setVariable("ADMIN_STREET",ilUtil::prepareFormOutput($settings["admin_street"]));
		$this->tpl->setVariable("ADMIN_ZIPCODE",ilUtil::prepareFormOutput($settings["admin_zipcode"]));
		$this->tpl->setVariable("ADMIN_CITY",ilUtil::prepareFormOutput($settings["admin_city"]));
		$this->tpl->setVariable("ADMIN_COUNTRY",ilUtil::prepareFormOutput($settings["admin_country"]));
		$this->tpl->setVariable("ADMIN_PHONE",ilUtil::prepareFormOutput($settings["admin_phone"]));
		$this->tpl->setVariable("ADMIN_EMAIL",ilUtil::prepareFormOutput($settings["admin_email"]));
		
		// client text
		$this->tpl->setVariable("TXT_INST_DATA", $this->lng->txt("client_data"));
		$this->tpl->setVariable("TXT_INST_NAME", $this->lng->txt("client_name"));
		$this->tpl->setVariable("TXT_INST_INFO", $this->lng->txt("client_info"));
		$this->tpl->setVariable("TXT_INST_INSTITUTION", $this->lng->txt("client_institution"));

		// contact text
		$this->tpl->setVariable("TXT_CONTACT_DATA", $this->lng->txt("contact_data"));
		$this->tpl->setVariable("TXT_REQUIRED_FIELDS", $this->lng->txt("required_field"));
		$this->tpl->setVariable("TXT_ADMIN", $this->lng->txt("administrator"));
		$this->tpl->setVariable("TXT_FIRSTNAME", $this->lng->txt("firstname"));
		$this->tpl->setVariable("TXT_LASTNAME", $this->lng->txt("lastname"));
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TXT_POSITION", $this->lng->txt("position"));
		$this->tpl->setVariable("TXT_INSTITUTION", $this->lng->txt("institution"));
		$this->tpl->setVariable("TXT_STREET", $this->lng->txt("street"));
		$this->tpl->setVariable("TXT_ZIPCODE", $this->lng->txt("zipcode"));
		$this->tpl->setVariable("TXT_CITY", $this->lng->txt("city"));
		$this->tpl->setVariable("TXT_COUNTRY", $this->lng->txt("country"));
		$this->tpl->setVariable("TXT_PHONE", $this->lng->txt("phone"));
		$this->tpl->setVariable("TXT_EMAIL", $this->lng->txt("email"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		
		$this->tpl->setVariable("FORMACTION", "setup.php?lang=".$this->lang."&cmd=gateway");
		$this->tpl->setVariable("TXT_SETUP_TITLE","contact information & client data");
		$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_contact"));
		
		$this->setButtonPrev("lang");
		
		$check = $this->checkClientContact($this->client);

		$this->client->status["contact"]["status"] = $check["status"];
		$this->client->status["contact"]["comment"] = $check["comment"];

		if ($check["status"])
		{
			$this->setButtonNext("nic");
		}
		
		$this->checkPanelMode();
	}
	
	function displayNIC()
	{
		$this->checkDisplayMode("nic_registration");

		$settings = $this->client->getAllSettings();
		
		// formular sent
		if ($_POST["form"])
		{
			// check register option
			if ($_POST["form"]["register"] == 1)
			{
				// update nic
				$this->client->updateNIC($this->ilias_nic_server);
				
				// online registration failed
				if (empty($this->client->nic_status[2]))
				{
					$this->client->setSetting("nic_enabled","-1");
					$message = $this->lng->txt("nic_reg_failed");				
				}
				else
				{
					$this->client->setSetting("inst_id",$this->client->nic_status[2]);
					$this->client->setSetting("nic_enabled","1");
					$message = $this->lng->txt("nic_reg_enabled");		
				}
			}
			elseif ($_POST["form"]["register"] == 2)
			{
				$nic_by_email = (int) $_POST["form"]["nic_id"];
				
				if (!$nic_by_email)
				{
					$message = $this->lng->txt("nic_reg_enter_correct_id");		
				}
				else
				{
					$this->client->setSetting("inst_id",$nic_by_email);
					$this->client->setSetting("nic_enabled","1");
					$message = $this->lng->txt("nic_reg_enabled");		
				}
			}
			else
			{
				$this->client->setSetting("inst_id","0");
				$this->client->setSetting("nic_enabled","0");
				$message = $this->lng->txt("nic_reg_disabled");
			}

			sendInfo($message);
		}
		
		// reload settings
		$settings = $this->client->getAllSettings();
		
		if ($settings["nic_enabled"] == "1")
		{
			$this->tpl->setVariable("TXT_INFO",$this->lng->txt("info_text_nic3")." ".$settings["inst_id"].".");
		}
		else
		{
			// reload settings
			$settings = $this->client->getAllSettings();
			
			$email_subject = rawurlencode("NIC registration request");
			$email_body = base64_encode($this->client->getURLStringForNIC($this->ilias_nic_server));
			$email_link = "<a href=\"mailto:ilias-info@uni-koeln.de?subject=".$email_subject."&body=".$email_body."\">".$this->lng->txt("email")."</a>";
			
			$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_nic1")." ".$email_link." ".$this->lng->txt("info_text_nic2"));

			// output
			$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.clientsetup_nic.html");
	
			$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
	
			// register form
			$this->tpl->setVariable("TXT_NIC_ENTER_ID",$this->lng->txt("nic_reg_enter_id"));
			$this->tpl->setVariable("TXT_NIC_REGISTER",$this->lng->txt("nic_registration"));
			$this->tpl->setVariable("TXT_NIC_ENABLE",$this->lng->txt("nic_reg_online"));
			$this->tpl->setVariable("TXT_NIC_EMAIL",$this->lng->txt("nic_reg_email"));
			$this->tpl->setVariable("TXT_NIC_DISABLE",$this->lng->txt("nic_reg_disable")." <span class=\"subtitle\">".$this->lng->txt("nic_reg_disable_comment")."</span>");
	
			$checked = "checked=\"checked\"";
			
			if (!isset($settings["nic_enabled"]) or $settings["nic_enabled"] == "1")
			{
				$this->tpl->setVariable("ENABLED",$checked);
			}
			elseif ($settings["nic_enabled"] == "2")
			{
				$this->tpl->setVariable("EMAIL",$checked);
			}
			else
			{
				$this->tpl->setVariable("DISABLED",$checked);
			}
	
			if (isset($settings["nic_enabled"]))
			{
				$this->client->status["nic"]["status"] = true;
			}
		}

		$this->setButtonPrev("contact");
		
		if ($this->client->status["nic"]["status"])
		{
			$this->setButtonNext("finish","finish");
		}
		
		$this->checkPanelMode();
	}
	
	function changeMasterPassword()
	{
		$this->tpl->addBlockFile("CONTENT","content","tpl.std_layout.html");
		
		$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_password"));

		// formular sent
		if ($_POST["form"])
		{
			$pass_old = $this->getPassword();

			if (empty($_POST["form"]["pass_old"]))
			{
				$message = $this->lng->txt("password_enter_old");
				$this->raiseError($message,$this->error_obj->MESSAGE);
			}
				
			if (md5($_POST["form"]["pass_old"]) != $pass_old)
			{
				$message = $this->lng->txt("password_old_wrong");
				$this->raiseError($message,$this->error_obj->MESSAGE);
			}
			
			if (empty($_POST["form"]["pass"]))
			{
				$message = $this->lng->txt("password_empty");
				$this->raiseError($message,$this->error_obj->MESSAGE);
			}
			
			if ($_POST["form"]["pass"] != $_POST["form"]["pass2"])
			{
				$message = $this->lng->txt("password_not_match");
				$this->raiseError($message,$this->error_obj->MESSAGE);
			}
			
			if (md5($_POST["form"]["pass"]) == $pass_old)
			{
				$message = $this->lng->txt("password_same");
				$this->raiseError($message,$this->error_obj->MESSAGE);
			}
			
			if (!$this->setPassword($_POST["form"]["pass"]))
			{
				$message = $this->lng->txt("save_error");
				$this->raiseError($message,$this->error_obj->MESSAGE);
			}

			sendInfo($this->lng->txt("password_changed"),true);
			header("Location: setup.php");
			exit();
		}
		
		// output
		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.form_change_admin_password.html");

		$this->tpl->setVariable("TXT_HEADER",$this->lng->txt("password_new_master"));

		// pass form
		$this->tpl->setVariable("FORMACTION", "setup.php?lang=".$this->lang."&cmd=gateway");
		$this->tpl->setVariable("TXT_REQUIRED_FIELDS", $this->lng->txt("required_field"));
		$this->tpl->setVariable("TXT_PASS_TITLE",$this->lng->txt("change_password"));
		$this->tpl->setVariable("TXT_PASS_OLD",$this->lng->txt("set_oldpasswd"));
		$this->tpl->setVariable("TXT_PASS",$this->lng->txt("set_newpasswd"));
		$this->tpl->setVariable("TXT_PASS2",$this->lng->txt("password_retype"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
	}

	function displayFinishSetup()
	{
		$this->checkDisplayMode("finish_setup");

		if ($this->validateSetup())
		{
			$txt_info = $this->lng->txt("info_text_finish1");
			$this->setButtonNext("login","login");
			$this->client->setSetting("setup_ok",1);
			$this->client->status["finish"]["status"] = true;
		}
		else
		{
			$txt_info = $this->lng->txt("info_text_finish2");
		}
		
		// output
		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.clientsetup_finish.html");
		$this->tpl->setVariable("TXT_INFO",$txt_info);
		
		$this->setButtonPrev("nic");
		
		$this->checkPanelMode();
	}
	
	function displayDeleteConfirmation()
	{
		$this->checkDisplayMode();

		// formular sent
		if ($_POST["form"]["delete"])
		{
			$ini = true;
			$db = false;
			$files = false;
		
			/* disabled
			switch ($_POST["form"]["delete"])
			{
				case 1:
					$ini = true;
					break;
			
				case 2:
					$ini = true;
					$db = true;
					break;

				case 3:
					$ini = true;
					$db = true;
					$files = true;
					break;		
			}
			*/
			
			$msg = $this->client->delete($ini,$db,$files);

			sendInfo($this->lng->txt("client_deleted"),true);
			header("Location: setup.php");
			exit();
		}

		$this->tpl->setVariable("TXT_INFO", $this->lng->txt("info_text_delete"));
		
		// output
		$this->tpl->addBlockFile("SETUP_CONTENT","setup_content","tpl.form_delete_client.html");

		// delete panel
		$this->tpl->setVariable("FORMACTION", "setup.php?cmd=gateway");
		$this->tpl->setVariable("TXT_DELETE", $this->lng->txt("delete"));
		$this->tpl->setVariable("TXT_DELETE_CONFIRM", $this->lng->txt("delete_confirm"));
		$this->tpl->setVariable("TXT_DELETE_INFO", $this->lng->txt("delete_info"));

		$this->checkPanelMode();
	}
	
	function changeAccessMode($a_back)
	{
		if ($this->client->status["finish"]["status"])
		{
			$val = ($this->client->ini->readVariable("client","access")) ? "0" : true;
			$this->client->ini->setVariable("client","access",$val);
			$this->client->ini->write();
			$message = "client_access_mode_changed";
		}
		else
		{
			$message = "client_setup_not_finished";
		}
		
		sendInfo($this->lng->txt($message),true);
		
		header("Location: setup.php?cmd=".$a_back);
		exit();
	}
	
	function changeDefaultClient()
	{
		if ($_POST["form"])
		{
			$client = new ilClient($_POST["form"]["default"]);

			if (!$client->init())
			{
				$this->raiseError($this->lng->txt("no_valid_client_id"),$this->error_obj->MESSAGE);
			}
			
			$status = $this->getStatus($client);
		
			if ($status["finish"]["status"])
			{
				$this->ini->setVariable("clients","default",$client->getId());
				$this->ini->write();
				$message = "default_client_changed";
			}
			else
			{
				$message = "client_setup_not_finished";
			}
		}
		
		sendInfo($this->lng->txt($message),true);
		
		header("Location: setup.php");
		exit();
	}

	function validateSetup()
	{
		foreach ($this->client->status as $key => $val)
		{
			if ($key != "finish" and $key != "access")
			{
				if ($val["status"] != true)
				{
					return false;
				}
			}
		}
		
		$clientlist = new ilClientList();
		$list = $clientlist->getClients();
		
		if (count($list) == 1)
		{
			$this->ini->setVariable("clients","default",$this->client->getId());
			$this->ini->write();

			$this->client->ini->setVariable("client","access",1);
			$this->client->ini->write();
		}

		return true;
	}
	
	function jumpToFirstUnfinishedsetupStep()
	{
		if (!$this->client->status["db"]["status"])
		{
			$this->cmd = "db";
			sendInfo($this->lng->txt("finish_initial_setup_first"),true);
			$this->displayDatabase();
		}
		elseif (!$this->client->status["lang"]["status"])
		{
			$this->cmd = "lang";
			sendInfo($this->lng->txt("finish_initial_setup_first"),true);
			$this->displayLanguages();		
		}
		elseif (!$this->client->status["contact"]["status"])
		{
			$this->cmd = "contact";
			sendInfo($this->lng->txt("finish_initial_setup_first"),true);
			$this->displayContactData();		
		}
		elseif (!$this->client->status["nic"]["status"])
		{
			$this->cmd = "nic";
			sendInfo($this->lng->txt("finish_initial_setup_first"),true);
			$this->displayNIC();		
		}
		else
		{
			return false;
		}
	}
} // END class.ilSetupGUI
?>
