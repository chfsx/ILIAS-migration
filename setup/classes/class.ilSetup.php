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
* Setup class
*
* class to setup ILIAS first and maintain the ini-settings and the database
*
* @author	Peter Gabriel <pgabriel@databay.de>
* @author Sascha Hofmann <shofmann@databay.de> 
* @version	$Id$
* @package	ilias-setup
*/

class ilSetup extends PEAR
{
	var $ini;			// ini file object
	var $ini_file_path;	// full path to setup.ini, containing the client list
	var $error = "";	// error text

	var $ini_ilias_exists = false;
	var $ini_client_exists = false;
	
	var $setup_defaults;
	var $ilias_nic_server = "http://homer.ilias.uni-koeln.de/ilias-nic/index.php";

	var $preliminaries_result = array();
	var $preliminaries = true;

	/**
	* sql-template-file
	* @var		string
	* @access	private
	*/
	var $SQL_FILE = "../sql/ilias3.sql";

	/**
	*  database connector
	*  @var		string
	*  @access	public
	*/
	var $dsn = "";

	/**
	*  database handler
	*  @var		object
	*  @access	public
	*/
	var $db;

	var $setup_password;		// master setup password
	var $default_client;
	
	var $safe_mode;
	var $safe_mode_exec_dir;
	
	var $auth;
	var $access_mode;			// if "admin", admin functions are enabled

	/**
	* constructor
	*/
	function ilSetup($a_auth,$a_auth_type)
	{
		global $log;

		$this->PEAR();
		
		define("ILIAS_MODULE","setup");
		
		$this->auth = ($a_auth) ? true : false;
		$this->access_mode = $a_auth_type;

		// safe mode status & exec_dir
		if ($this->safe_mode = ini_get("safe_mode"))
		{
			$this->safe_mode_exec_dir = ilFile::deleteTrailingSlash(ini_get("safe_mode_exec_dir"));
		}
		
		// Error Handling
		$this->error_obj = new ilErrorHandling();
		$this->setErrorHandling(PEAR_ERROR_CALLBACK,array($this->error_obj,'errorHandler'));

		// set path to ilias.ini
		$this->ini_file_path = ILIAS_ABSOLUTE_PATH."/ilias.ini.php";
		$this->setup_defaults = ILIAS_ABSOLUTE_PATH."/setup/ilias.master.ini.php";

		// init setup.ini
		$this->ini_ilias_exists = $this->init();

		if ($this->ini_ilias_exists)
		{
			if ($this->ini->readVariable("log","path") != "")
			{
				$log->path = $this->ini->readVariable("log","path");
			}

			if ($this->ini->readVariable("log","file") != "")
			{
				$log->filename = $this->ini->readVariable("log","file");
			}

			if ($this->ini->readVariable("log","enabled") != "")
			{
				$log->enabled = $this->ini->readVariable("log","enabled");
			}
		}
	}

	function init()
	{
		// load data from setup.ini file
		$this->ini = new ilIniFile($this->ini_file_path);
		
		if (!$this->ini->read())
		{
			$this->ini->GROUPS = parse_ini_file($this->setup_defaults,true);
			$this->error = get_class($this).": ".$this->ini->getError();
			return false;
		}
		
		$this->setup_password = $this->ini->readVariable("setup","pass");
		$this->default_client = $this->ini->readVariable("clients","default");

		define("ILIAS_DATA_DIR",$this->ini->readVariable("clients","datadir"));
		define("ILIAS_WEB_DIR",$this->ini->readVariable("clients","path"));

		return true;
	}
	
	/**
	* saves client.ini & updates client list in ilias.ini
	*
	*/
	function saveNewClient()
	{
		// save client id to session
		$_SESSION["ClientId"] = $this->client->getId();
		
		// create client
		if (!$this->client->create())
		{
			$this->error = $this->client->getError();
			return false;
		}

		//everything okay
		$this->ini_client_exists = true;

		return true;		
	}
	
	/**
	* update client.ini & move data dirs
	* does not work correctly at this time - DISABLED
	*
	*/
	function updateNewClient($a_old_client_id)
	{
		return true;
		var_dump("<pre>",$this->client,"</pre>");exit;

		if ($a_old_client_id != $this->client->getId())
		{
			// check for existing client dir
			if (file_exists(ILIAS_ABSOLUTE_PATH."/".ILIAS_WEB_DIR."/".$this->client->getId()))
			{
				$this->raiseError($this->lng->txt("client_id_already_exists"),$this->error_obj->MESSAGE);
			}
			
			if (!$this->saveNewClient())
			{
				$this->raiseError($this->lng->txt("save_error"),$this->error_obj->MESSAGE);
			}
			
			ilUtil::delDir(ILIAS_ABSOLUTE_PATH."/".ILIAS_WEB_DIR."/".$a_old_client_id);
			ilUtil::delDir(ILIAS_DATA_DIR."/".$a_old_client_id);	
		}
		
		//everything okay
		$this->ini_client_exists = true;

		return true;		
	}

	/**
	* execute a query
	* @param	string
	* @param	string
	* @return	boolean	true
	*/
	function execQuery($db,$str)
	{
		$sql = explode("\n",trim($str));
		for ($i=0; $i<count($sql); $i++)
		{
			$sql[$i] = trim($sql[$i]);
			if ($sql[$i] != "" && substr($sql[$i],0,1)!="#")
			{
				//take line per line, until last char is ";"
				if (substr($sql[$i],-1)==";")
				{
					//query is complete
					$q .= " ".substr($sql[$i],0,-1);
					$r = $db->query($q);
					if ($r == false)
						return false;
					unset($q);
				} //if
				else
				{
					$q .= " ".$sql[$i];
				} //else
			} //if
		} //for
		return true;
	}

	function createDatabase()
	{
		if ($this->client->checkDatabaseExists())
		{
			$this->error = $this->lng->txt("database_exists");
			return false;
		}
		
		//create database
		$db = DB::connect($this->client->dsn_host);
		if (DB::isError($db))
		{
			$this->error = "connection_failed";
			return false;
		}

		$sql = "CREATE DATABASE ".$this->client->getdbName();
		$r = $db->query($sql);

		if (DB::isError($r))
		{
			$this->error = "create_database_failed";
			return false;
		}

		//database is created, now disconnect and reconnect
		$db->disconnect();
		
		$this->client->db_exists = true;
		return true;
	}
	
	/**
	* set the database data
	*/
	function installDatabase()
	{
		if (!$this->client->checkDatabaseHost())
		{
			$this->error = "no_connection_to_host";
			return false;
		}

		if (!$this->client->connect())
		{
			return false;
		}

		//take sql dump an put it in
		$q = file($this->SQL_FILE);
		$q = implode("\n",$q);

		if ($this->execQuery($this->client->db,$q) === false)
		{
			$this->error = "dump_error";
			return false;
		}

		$this->client->db_installed = true;
		return true;
	}

	/**
	* check if inifile exists
	* @return	boolean
	*/
	function checkIniFileExists()
	{
		$a = @file_exists($this->INI_FILE);
		return $a;
	}

	/**
	* check for writable directory
	* @param	string	directory
	* @return	array
	*/
	function checkWritable($a_dir = "..")
	{
		clearstatcache();
		if (is_writable($a_dir))
		{
			$arr["status"] = true;
			$arr["comment"] = "";
		}
		else
		{
			$arr["status"] = false;
			$arr["comment"] = $this->lng->txt("pre_folder_write_error");
		}

		return $arr;
	}

	/**
	* check for permission to create new folders in specified directory
	* @param	string	directory
	* @return	array
	*/
	function checkCreatable($a_dir = "..")
	{
		clearstatcache();
		if (mkdir($a_dir."/crst879dldsk9d", 0774))
		{
			$arr["status"] = true;
			$arr["comment"] = "";

			rmdir($a_dir."/crst879dldsk9d");
		}
		else
		{
			$arr["status"] = false;
			$arr["comment"] = $this->lng->txt("pre_folder_create_error");
		}

		return $arr;
	}

	/**
	* check cookies enabled
	* @return	array
	*/
	function checkCookiesEnabled()
	{
		global $sess;

		if ($sess->usesCookies)
		{
			$arr["status"] = true;
			$arr["comment"] = "";
		}
		else
		{
			$arr["status"] = false;
			$arr["comment"] = $this->lng->txt("pre_cookies_disabled");
		}

		return $arr;
	}

	/**
	* check for PHP version
	* @return	array
	*/
	function checkPHPVersion()
	{
		$version =  phpversion();
		$arr["version"] = $version;
		$first = (integer) substr($version,0,1);

		switch ($first)
		{
			case 2:
			case 3:
				$arr["status"] = false;
				$arr["comment"] = $this->lng->txt("pre_php_version_3");
				break;

			case 4:
				$second = (integer) substr($version,2,1);
				if ($second >= 3)
				{
					$arr["status"] = true;
					$arr["comment"] = "";
				}
				elseif ($second == 2)
				{
					$arr["status"] = false;
					$arr["comment"] = $this->lng->txt("pre_php_version_4.2");
				}
				else
				{
					$arr["status"] = false;
					$arr["comment"] = $this->lng->txt("pre_php_version_4.1");
				}
				break;

			case 5:
				$arr["status"] = true;
				$arr["comment"] = $this->lng->txt("pre_php_version_5");
				break;

			default:
				$arr["status"] = true;
				$arr["comment"] = $this->lng->txt("pre_php_version_unknown");
				break;
		}

		return $arr;
	}

	function checkAuth()
	{
		if ($_SESSION["auth"] === true)
		{
			return true;
		}

		return false;
	}
	
	/**
	* preliminaries
	*
	* check if different things are ok for setting up ilias
	* @access	private
	* @return 	array
	*/
	function queryPreliminaries()
	{
		$a = array();
		$a["php"] = $this->checkPHPVersion();
		$a["root"] = $this->checkWritable();
		$a["create"] = $this->checkCreatable();
		$a["cookies"] = $this->checkCookiesEnabled();

		return $a;
	}

	function checkPreliminaries()
	{
		$this->preliminaries_result = $this->queryPreliminaries();

		foreach ($this->preliminaries_result as $val)
		{
			if ($val["status"] === false)
			{
				$this->preliminaries = false;
				return false;
			}
		}

		return true;
	}

	function getPassword ()
	{
		return $this->ini->readVariable("setup","pass");
	}

	function setPassword ($a_password)
	{
		$this->ini->setVariable("setup","pass",md5($a_password));

		if ($this->ini->write() == false)
		{
			$this->error = $this->ini->getError();
			return false;
		}
		
		return true;
	}
	
	function loginAsClient($a_auth_data)
	{
		if (empty($a_auth_data["client_id"]))
		{
			$this->error = "no_client_id";
			return false;
		}

		if (empty($a_auth_data["username"]))
		{
			$this->error = "no_username";
			return false;
		}

		if (empty($a_auth_data["password"]))
		{
			$this->error = "no_password";
			return false;
		}
		
		if (!$this->newClient($a_auth_data["client_id"]))
		{
			$this->error = "unknown_client_id";
			unset($this->client);
			return false;
		}
		
		if (!$this->client->db_exists)
		{
			$this->error = "no_db_connect_consult_admin";
			unset($this->client);
			return false;		
		}
		
		$q = "SELECT usr_data.usr_id FROM usr_data ".
			 "LEFT JOIN rbac_ua ON rbac_ua.usr_id=usr_data.usr_id ".
			 "LEFT JOIN settings ON settings.value = rbac_ua.rol_id ".
			 "WHERE settings.keyword='system_role_id' ".
			 "AND usr_data.login='".$a_auth_data["username"]."' ".
			 "AND usr_data.passwd='".md5($a_auth_data["password"])."'";
		$r = $this->client->db->query($q);
		
		if (!$r->numRows())
		{
			$this->error = "login_invalid";
			return false;
		}

		// all checks passed -> user valid
		$_SESSION["auth"] = true;
		$_SESSION["access_mode"] = "client";
		$_SESSION["ClientId"] = $this->client->getId();		
		return true;
	}

	function loginAsAdmin($a_password)
	{
		$a_password = md5($a_password);
		
		if ($this->ini->readVariable("setup","pass") == $a_password)
		{
			$_SESSION["auth"] = true;
			$_SESSION["access_mode"] = "admin";
			return true;
		}
		
		return false;
	}

	// creates a client object in $this->client from clientlist
	function newClient($a_client_id = 0)
	{
		if (!$this->isInstalled())
		{
			return false;
		}

		$this->client = new ilClient($a_client_id);

		if (!$this->client->init())
		{
			$this->error = get_class($this).": ".$this->client->getError();
			$_SESSION["ClientId"] = "";
			return false;
		}
		
		$_SESSION["ClientId"] = $a_client_id;
		
		return true;
	}
	
	function getStatus ($client = 0)
	{
		if (!is_object($client))
		{
			if ($this->ini_client_exists)
			{
				$client =& $this->client;
			}
			else
			{
				$client = new ilClient();
			}
		}
		
		$status = array();
		$status["ini"] = $this->checkClientIni($client);
		$status["db"] = $this->checkClientDatabase($client);
		
		if ($status["db"]["status"] === false and $status["db"]["update"] !== true)
		{
			$status["lang"]["status"] = false;
			$status["lang"]["comment"] = $status["db"]["comment"];
			$status["contact"]["status"] = false;
			$status["contact"]["comment"] = $status["db"]["comment"];
			$status["nic"]["status"] = false;
			$status["nic"]["comment"] = $status["db"]["comment"];
		}
		else
		{
			$status["lang"] = $this->checkClientLanguages($client);
			$status["contact"] = $this->checkClientContact($client);
			$status["nic"] = $this->checkClientNIC($client);
			$status["finish"] = $this->checkFinish($client);
			$status["access"] = $this->checkAccess($client);
		}

		//return value
		return $status;
	}
	
	function checkFinish(&$client)
	{
		if ($client->getSetting("setup_ok"))
		{
			$arr["status"] = true;
			$arr["comment"] = $this->lng->txt("setup_finished");
		}
		else
		{
			$arr["status"] = false;
			$arr["comment"] = $this->lng->txt("setup_not_finished");
		}
		
		return $arr;
	}
	
	function checkAccess(&$client)
	{
		if ($client->ini->readVariable("client","access") == "1")
		{
			$arr["status"] = true;
			$arr["comment"] = $this->lng->txt("online");
		}
		else
		{
			$arr["status"] = false;
			$arr["comment"] = $this->lng->txt("disabled");
		}
		
		return $arr;
	}

	function checkClientIni(&$client)
	{
		if (!$arr["status"] = $client->init())
		{
			$arr["comment"] = $client->getError();
		}
		else
		{
			$arr["comment"] = "dir: /".ILIAS_WEB_DIR."/".$client->getId();			
		}
		
		return $arr; 
	}
	
	function checkClientDatabase(&$client)
	{
//		if (!$arr["status"] = $client->checkDatabaseExists())
		if (!$arr["status"] = $client->db_exists)
		{
			$arr["comment"] = $this->lng->txt("no_database");
			return $arr;
		}
		
//		if ($arr["status"] = $client->connect())
		if (!$arr["status"] = $client->db_installed)
		{
			$arr["comment"] = $this->lng->txt("db_not_installed");
			return $arr;
		}
		
		// TODO: move this to client class!!
		$client->setup_ok = (bool) $client->getSetting("setup_ok");
			
		$this->lng->setDbHandler($client->db);
		include_once "../classes/class.ilDBUpdate.php";
		$dbupdate = new ilDBUpdate($client->db);
				
		if (!$arr["status"] = $dbupdate->getDBVersionStatus())
		{
			$arr["comment"] = $this->lng->txt("db_needs_update");
			$arr["update"] = true;
			return $arr;
		}

		$arr["comment"] = "version ".$dbupdate->getCurrentVersion();
		return $arr;
	}

	function checkClientLanguages(&$client)
	{
		$installed_langs = $this->lng->getInstalledLanguages();
		
		$count = count($installed_langs);
		
		if ($count < 1)
		{
			$arr["status"] = false;
			$arr["comment"] = $this->lng->txt("lang_none_installed");		
		}
		else
		{
			$arr["status"] = true;
			$arr["comment"] = $count." ".$this->lng->txt("languages_installed");
		}
		
		return $arr;
	}
	
	function checkClientContact(&$client)
	{
		$arr["status"] = true;
		$arr["comment"] = $this->lng->txt("filled_out");

		$settings = $client->getAllSettings();
		$client_name = $client->getName();

		// check required fields
		if (empty($settings["admin_firstname"]) or empty($settings["admin_lastname"])
			or empty($settings["admin_street"]) or empty($settings["admin_zipcode"])
			or empty($settings["admin_country"]) or empty($settings["admin_city"])
			or empty($settings["admin_phone"]) or empty($settings["admin_email"])
			or empty($client_name) or empty($settings["inst_institution"]))
		{
			$arr["status"] = false;
			$arr["comment"] = $this->lng->txt("missing_data");
		}
			
		// admin email
		if (!ilUtil::is_email($settings["admin_email"]) and $arr["status"] != false)
		{
			$arr["status"] = false;
			$arr["comment"] = $this->lng->txt("email_not_valid");
		}
		
		return $arr;
	}
	
	function checkClientNIC(&$client)
	{
		$settings = $client->getAllSettings();
		
		if (!isset($settings["nic_enabled"]))
		{
			$arr["status"] = false;
			$arr["comment"] = $this->lng->txt("nic_not_disabled");
			return $arr;
		}
		
		$arr["status"] = true;

		if ($settings["nic_enabled"] == "-1")
		{
			$arr["comment"] = $this->lng->txt("nic_reg_failed");
			return $arr;
		}

		if (!$settings["nic_enabled"])
		{
			$arr["comment"] = $this->lng->txt("nic_reg_disabled");
		}
		else
		{
			$arr["comment"] = $this->lng->txt("nic_reg_enabled");
		}

		return $arr;
	}
	
	function isInstalled()
	{
		return $this->ini_ilias_exists;
	}
	
	function isAuthenticated()
	{
		return $this->auth;
	}

	function isAdmin()
	{
		return ($this->access_mode == "admin") ? true : false;
	}
	
	// saves intial settings
	function saveMasterSetup($a_formdata)
	{
		if ($a_formdata["chk_datadir_path"] == 1)	// mode create dir 
		{
			$datadir_path = preg_replace("/\\\\/","/",ilFile::deleteTrailingSlash(ilUtil::stripSlashes($a_formdata["datadir_path"])));

			if (!ilUtil::makeDir($datadir_path))
			{
				$this->error = "create_datadir_failed";
				return false;
			}
		}

		// create data dir if not exists
		if (!@file_exists(ILIAS_ABSOLUTE_PATH."/".$this->ini->readVariable("clients","path")) and !@is_dir(ILIAS_ABSOLUTE_PATH."/".$this->ini->readVariable("clients","path")))
		{
			if (!ilUtil::makeDir(ILIAS_ABSOLUTE_PATH."/".$this->ini->readVariable("clients","path")))
			{
				$this->error = "create_webdir_failed";
				return false;
			}			
		}
		
		$form_log_path = preg_replace("/\\\\/","/",ilFile::deleteTrailingSlash(ilUtil::stripSlashes($a_formdata["log_path"])));
		$log_path = substr($form_log_path,0,strrpos($form_log_path,"/"));
		$log_file = substr($form_log_path,strlen($log_path)+1);
		
 		$this->ini->setVariable("server","http_path",ILIAS_HTTP_PATH);
		$this->ini->setVariable("server","absolute_path",ILIAS_ABSOLUTE_PATH);
		$this->ini->setVariable("clients", "datadir", $datadir_path);
		$this->ini->setVariable("tools", "convert", preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["convert_path"])));
		$this->ini->setVariable("tools", "zip", preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["zip_path"])));
		$this->ini->setVariable("tools", "unzip", preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["unzip_path"])));
		$this->ini->setVariable("tools", "java", preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["java_path"])));
		$this->ini->setVariable("tools", "htmldoc", preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["htmldoc_path"])));
		$this->ini->setVariable("setup", "pass", md5($a_formdata["setup_pass"]));
		$this->ini->setVariable("log", "path", $log_path);
		$this->ini->setVariable("log", "file", $log_file);
		$this->ini->setVariable("log", "enabled", (isset($a_formdata["chk_log_status"])) ? "0" : 1);

		if (!$this->ini->write())
		{
			$this->error = get_class($this).": ".$this->ini->getError();
			return false;
		}

		// everything is fine. so we authenticate the user and set access mode to 'admin'
		$_SESSION["auth"] = true;
		$_SESSION["access_mode"] = "admin";	

		return true;
	}
	
	// updates settings
	function updateMasterSettings($a_formdata)
	{
		$convert_path = preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["convert_path"]));
		$zip_path = preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["zip_path"]));
		$unzip_path = preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["unzip_path"]));
		$java_path = preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["java_path"]));
		$htmldoc_path = preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["htmldoc_path"]));

		$this->ini->setVariable("tools", "convert", $convert_path);
		$this->ini->setVariable("tools", "zip", $zip_path);
		$this->ini->setVariable("tools", "unzip", $unzip_path);
		$this->ini->setVariable("tools", "java", $java_path);
		$this->ini->setVariable("tools", "htmldoc", $htmldoc_path);

		$form_log_path = preg_replace("/\\\\/","/",ilFile::deleteTrailingSlash(ilUtil::stripSlashes($a_formdata["log_path"])));
		$log_path = substr($form_log_path,0,strrpos($form_log_path,"/"));
		$log_file = substr($form_log_path,strlen($log_path)+1);

		$this->ini->setVariable("log", "path", $log_path);
		$this->ini->setVariable("log", "file", $log_file);
		$this->ini->setVariable("log", "enabled", (isset($a_formdata["chk_log_status"])) ? "0" : 1);

		if (!$this->ini->write())
		{
			$this->error = get_class($this).": ".$this->ini->getError();
			return false;
		}

		return true;
	}
	
	function checkToolsSetup($a_formdata)
	{
		// convert path
		if (!isset($a_formdata["chk_convert_path"]))
		{
			// convert backslashes to forwardslashes
			$convert_path = preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["convert_path"]));

			if (empty($convert_path))
			{
				$this->error = "no_path_convert";
				return false;
			}
			
			if (!$this->testConvert($convert_path))
			{
				$this->error = "check_failed_convert";
				return false;
			}
		}
		
		// zip path
		if (!isset($a_formdata["chk_zip_path"]))
		{
			// convert backslashes to forwardslashes
			$zip_path = preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["zip_path"]));
			
			if (empty($zip_path))
			{
				$this->error = "no_path_zip";
				return false;
			}
		
			if (!$this->testZip($zip_path))
			{
				$this->error = "check_failed_zip";
				return false;
			}
		}

		// unzip path
		if (!isset($a_formdata["chk_unzip_path"]))
		{
			// convert backslashes to forwardslashes
			$unzip_path = preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["unzip_path"]));

			if (empty($unzip_path))
			{
				$this->error = "no_path_unzip";
				return false;
			}

			if (!$this->testUnzip($unzip_path))
			{
				$this->error = "check_failed_unzip";
				return false;
			}
		}
		
		// java path
		if (!isset($a_formdata["chk_java_path"]))
		{
			// convert backslashes to forwardslashes
			$java_path = preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["java_path"]));

			if (empty($java_path))
			{
				$this->error = "no_path_java";
				return false;
			}
		
			if (!$this->testJava($java_path))
			{
				$this->error = "check_failed_java";
				return false;
			}
		}
		
		// htmldoc path
		if (!isset($a_formdata["chk_htmldoc_path"]))
		{
			// convert backslashes to forwardslashes
			$htmldoc_path = preg_replace("/\\\\/","/",ilUtil::stripSlashes($a_formdata["htmldoc_path"]));

			if (empty($htmldoc_path))
			{
				$this->error = "no_path_htmldoc";
				return false;
			}
		
			if (!$this->testHtmldoc($htmldoc_path))
			{
				$this->error = "check_failed_htmldoc";
				return false;
			}
		}
		
		return true;
	}
		
	// datadir path
	function checkDataDirSetup($a_formdata)
	{
		// remove trailing slash & convert backslashes to forwardslashes
		$datadir_path = preg_replace("/\\\\/","/",ilFile::deleteTrailingSlash(ilUtil::stripSlashes($a_formdata["datadir_path"])));


		if (empty($datadir_path))
		{
			$this->error = "no_path_datadir";
			return false;
		}

		// create dir
		if ($a_formdata["chk_datadir_path"] == 1)
		{
			$dir_to_create = substr(strrchr($datadir_path, "/"), 1);
			$dir_to_check = substr($datadir_path,0,- strlen($dir_to_create)-1);

			if (is_writable($datadir_path))
			{
				$this->error = "dir_exists_create";
				return false;
			}

			if (!is_writable($dir_to_check))
			{
				$this->error = "cannot_create_datadir_no_write_access";
				return false;
			}
		}
		else	// check set target dir
		{
			if (!is_writable($datadir_path))
			{
				$this->error = "cannot_create_datadir_no_write_access";
				return false;
			}
		}

		return true;
	}
	
	function checkPasswordSetup($a_formdata)
	{
		if (!$a_formdata["setup_pass"])
		{
			$this->error = "no_setup_pass_given";
			return false;
		}

		if ($a_formdata["setup_pass"] != $a_formdata["setup_pass2"])
		{
			$this->error = "pass_does_not_match";
			return false;
		}
		
		return true;
	}
	
	function checkLogSetup($a_formdata)
	{
		// log path
		if (!isset($a_formdata["chk_log_status"]))
		{
			// remove trailing slash & convert backslashes to forwardslashes
			$log_path = preg_replace("/\\\\/","/",ilFile::deleteTrailingSlash(ilUtil::stripSlashes($a_formdata["log_path"])));

			if (empty($log_path))
			{
				$this->error = "no_path_log";
				return false;
			}

			if (!@touch($log_path))
			{
				$this->error = "could_not_create_logfile";
				return false;
			}
		}
		
		return true;
	}
	
	function getError()
	{
		if (empty($this->error))
		{
			return false;
		}
		
		$error = $this->error;
		$this->error = "";
		
		return $error;
	}

	/**
	* destructor
	*
	* @return boolean
	*/
	function _ilSetup()
	{
		//if ($this->readVariable("db","type") != "")
		//{
		//	$this->db->disconnect();
		//}
		return true;
	}

	/**
	* Check convert program
	*
	* @param	string		convert path
	* @return	boolean		true -> OK | false -> not OK	
	*/
	function testConvert ($a_convert_path)
	{
		// generate gif with convert
		if (file_exists(ILIAS_ABSOLUTE_PATH."/images/test.gif"))
		{
			unlink(ILIAS_ABSOLUTE_PATH."/images/test.gif");
		}
		system($a_convert_path." ".ILIAS_ABSOLUTE_PATH."/images/test.jpg GIF:".ILIAS_ABSOLUTE_PATH."/images/test.gif");
	
		// check wether convert generated file
		if (file_exists(ILIAS_ABSOLUTE_PATH."/images/test.gif"))
		{
			unlink(ILIAS_ABSOLUTE_PATH."/images/test.gif");
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Check JVM
	*
	* @param	string		java path
	* @return	boolean		true -> OK | false -> not OK	
	*/
	function testJava ($a_java_path)
	{
		exec($a_java_path, $out, $back);
	
		unset($out);
	
		if ($back != 1)
			return false;
		else
			return true;
	}

	/**
	* Check zip program
	*
	* @param	string		zip path
	* @return	boolean		true -> OK | false -> not OK	
	*/
	function testZip ($a_zip_path)
	{
		// create test file and run zip
		$fp = fopen(ILIAS_ABSOLUTE_PATH."/test.dat", "w");
			
		fwrite($fp, "test");
		fclose($fp);
					
		if (file_exists(ILIAS_ABSOLUTE_PATH."/test.dat"))
		{
			$curDir = getcwd();
			chdir(ILIAS_ABSOLUTE_PATH);
				
			$zipCmd = $a_zip_path." -m zip_test_file.zip test.dat";
				
			exec($zipCmd);
				
			chdir($curDir);
		}
	
		// check wether zip generated test file or not
		if (file_exists(ILIAS_ABSOLUTE_PATH."/zip_test_file.zip"))
		{
			unlink(ILIAS_ABSOLUTE_PATH."/zip_test_file.zip");
			return true;
		}
		else
		{
			unlink(ILIAS_ABSOLUTE_PATH."/test.dat");
			return false;
		}
	}
	
	
	/**
	* Check unzip program
	*
	* @param	string		unzip_path
	* @return	boolean		true -> OK | false -> not OK	
	*/
	function testUnzip ($a_unzip_path)
	{
		$curDir = getcwd();
				
		chdir(ILIAS_ABSOLUTE_PATH);
				
		if (file_exists(ILIAS_ABSOLUTE_PATH."/unzip_test_file.zip"))
		{
			$unzipCmd = $a_unzip_path." unzip_test_file.zip";
			exec($unzipCmd);
		}

		chdir($curDir);
	
		// check wether unzip extracted the test file or not
		if (file_exists(ILIAS_ABSOLUTE_PATH."/unzip_test_file.txt"))
		{
			unlink(ILIAS_ABSOLUTE_PATH."/unzip_test_file.txt");
		
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Check htmldoc program
	*
	* @param	string		htmldoc_path
	* @return	boolean		true -> OK | false -> not OK	
	*/
	function testHtmldoc($a_htmldoc_path)
	{
		$curDir = getcwd();
				
		chdir(ILIAS_ABSOLUTE_PATH);

		$html = "<html><head><title></title></head><body><p>test</p></body></html>";

		$html_file = "htmldoc_test_file.html";
        
        $fp = fopen( $html_file ,"wb");
        fwrite($fp, $html);
        fclose($fp);

        $htmldoc = $a_htmldoc_path." ";
        $htmldoc .= "--no-toc ";
        $htmldoc .= "--no-jpeg ";
        $htmldoc .= "--webpage ";
        $htmldoc .= "--outfile htmldoc_test_file.pdf ";
        $htmldoc .= "--bodyfont Arial ";
        $htmldoc .= "--charset iso-8859-15 ";
        $htmldoc .= "--color ";
        $htmldoc .= "--size A4  ";      // --landscape
        $htmldoc .= "--format pdf ";
        $htmldoc .= "--footer ... ";
        $htmldoc .= "--header ... ";
        $htmldoc .= "--left 60 ";
        // $htmldoc .= "--right 200 ";
        $htmldoc .= $html_file;
		exec($htmldoc);

		unlink(ILIAS_ABSOLUTE_PATH."/".$html_file);

		chdir($curDir);

		if (file_exists(ILIAS_ABSOLUTE_PATH."/htmldoc_test_file.pdf"))
		{
			unlink(ILIAS_ABSOLUTE_PATH."/htmldoc_test_file.pdf");
			return true;
		}
		else
		{
			return false;
		}
	}
} // END class.ilSetup
?>
