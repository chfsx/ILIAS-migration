<?php

require_once("./classes/class.IniFile.php");
require_once("./classes/class.util.php");
require_once("DB.php");

/**
* Setup class
*
* class to setup ILIAS first and maintain the ini-settings and the database
*
* @author Peter Gabriel <pgabriel@databay.de>
* @package application
* @access public
* @version $Id$
*/
class Setup
{
	/**
	 * ini file
	 * @var string
	 * @access private
	 */
	var $INI_FILE = "./ilias.ini.php";

	/**
	 * ini file
	 * @var string
	 * @access private
	 */
	var $DEFAULT_INI_FILE = "./ilias.master.ini.php";
	
	/**
	 * sql-template-file
	 * @var string
	 * @access private
	 */
	var $SQL_FILE = "./sql/ilias3.sql";
	
    /**
	 *  database connector
	 *  @var string
	 *  @access public
	*/
    var $dsn = "";
	
    /**
	 *  database handle
	 *  @var object DB
	 *  @access private
	 */
    var $db;
	
    /**
	 *  ini-object
	 *  @var object IniFile
	 *  @access private
	 */
	var $ini;
	
	/**
	 * default array for ini-file
	 * @var array
     * @access private
	 */
	var $default;
	
	/**
    * constructor
    * @return boolean
    */

    function getDefaults()
    {
	//default values are in $DEFAULTINIFILE
	//NOTE: please don't use any brackets
	$this->default = parse_ini_file($this->DEFAULT_INI_FILE, true);
	
	//build list of databasetypes
		$this->dbTypes = array();
		$this->dbTypes["mysql"] = "MySQL";
		$this->dbTypes["pgsql"] = "PostgreSQL";
		$this->dbTypes["ibase"] = "InterBase";
		$this->dbTypes["msql"] = "Mini SQL";
		$this->dbTypes["mssql"] = "Microsoft SQL Server";
		$this->dbTypes["oci8"] = "Oracle 7/8/8i";
		$this->dbTypes["odbc"] = "ODBC (Open Database Connectivity)";
		$this->dbTypes["sybase"] = "SyBase";
		$this->dbTypes["ifx"] = "Informix";
		$this->dbTypes["fbsql"] = "FrontBase";
    }

    /**
	* constructor
	*/
	function Setup()
    {
		$this->ini = new IniFile($this->INI_FILE);
    }

	/**
	* try to read the ini file
	*/
    function readIniFile()
    {
		// get settings from ini file
		$this->ini = new IniFile($this->INI_FILE);
		$this->ini->read();
		//check for error
		if ($this->ini->ERROR != "")
		{
			$this->error = $this->ini->ERROR;
			return false;
		}
		
		//here only dbsetting are interesting
		$this->setDbType($this->ini->readVariable("db","type"));
		$this->setDbHost($this->ini->readVariable("db","host"));
		$this->setDbName($this->ini->readVariable("db","name"));
		$this->setDbUser($this->ini->readVariable("db","user"));
		$this->setDbPass($this->ini->readVariable("db","pass"));

		$this->setDSN();
		
		// set tplPath
		$this->tplPath = TUtil::setPathStr($this->ini->readVariable("server","tpl_path"));

		return true;
    }

	/**
	* write the ini file
	*/
    function writeIniFile()
    {		
		//write inifile
		//overwrite with defaults
		$this->getDefaults();
		$this->ini->GROUPS = $this->default;
		
		//no overwrite the defaults with submitted values
		$this->ini->setVariable("db", "host", $this->dbHost);
		$this->ini->setVariable("db", "name", $this->dbName);
		$this->ini->setVariable("db", "user", $this->dbUser);
		$this->ini->setVariable("db", "pass", $this->dbPass);
		
		//try to write the file
		if ($this->ini->write()==false)
		{
			$this->error_msg = "cannot_write";
			return false;
		}
		
		//everything went okay
		return true;
		
	} //function
	
	/**
	* set the dsns
	*/
	function setDSN()
	{
		$this->dsn_host = $this->dbType."://".$this->dbUser.":".$this->dbPass."@".$this->dbHost;
		$this->dsn = $this->dbType."://".$this->dbUser.":".$this->dbPass."@".$this->dbHost."/".$this->dbName;
	}
	
    /**
	 * connect
	 */
     function connect()
	 {
		 // build dsn of database connection and connect
		 $this->dsn = $this->dbtype.
			 "://".$this->dbuser.
			 ":".$this->dbpass.
			 "@".$this->dbhost.

		 $this->db = DB::connect($this->dsn,true);

		 if (DB::isError($this->db)) {
			 $this->error_msg = $this->db->getMessage();
			 $this->error = "not_connected_to_db";
			 return false;
		 }

		 return true;
	 }

	/**
	 * set the databasetype
	 * @param string
	 */
	function setDbType($str)
	{
		$this->dbType = $str;
		$this->setDSN();
	}
	
	/**
	 * set the host
	 * @param string
	 */
	function setDbHost($str)
	{
		$this->dbHost = $str;
		$this->setDSN();
	}

	/**
	 * set the name of database
	 * @param string
	 */
	function setDbName($str)
	{
		$this->dbName = $str;
		$this->setDSN();
	}

	/**
	 * set the user
	 * @param string
	 */
	function setDbUser($str)
	{
		$this->dbUser = $str;
		$this->setDSN();
	}

	/**
	 * set the password
	 * @param string
	 */
	function setDbPass($str)
	{
		$this->dbPass = $str;
		$this->setDSN();
	}

    /**
	 * execute a query
	 * @param string 
	 * @param string
	 * @return bool true
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

	/**
	 * set the database data
	*/
    function installDatabase()
	{
		//check parameters
		if ($this->dbType=="" || $this->dbHost=="" || $this->dbName=="" || $this->dbUser=="")
		{
			$this->error = "empty_fields";
			return false;
		}

		if ($this->checkDatabaseHost() == false)
		{
			$this->error = "no_connection_to_host";
			return false;		
		}

		$db_status = $this->checkDatabaseExists();
		if ($db_status["status"] == true)		
		{
			$this->error = "database_exists";
			return false;
		}

		//create database
		$db = DB::connect($this->dsn_host);
		if (DB::isError($db))
		{
			$this->error_msg = $db->getMessage();
			$this->error = "connection_failed";
			return false;
		}

		$sql = "CREATE DATABASE ".$this->dbName;
		$r = $db->query($sql);

		if (DB::isError($r))
		{
			$this->error = "create_database_failed";
			$this->error_msg = $r->getMessage();
			return false;
		}
		
		//database is created, now disconnect and reconnect
		$db->disconnect();
		$db = DB::connect($this->dsn);
		if (DB::isError($db))
		{
			$this->error = "creation_of_database_failed";
			$db->disconnect();
			return false;
		}
		
		//take sql dump an put it in
		$q = file($this->SQL_FILE);
		$q = implode("\n",$q);
		if ($this->execQuery($db,$q)==false)
		{
			$this->error_msg = "dump_error";
			return false;
		}
	    return true;
    }

	/**
	* check database connection
	* @return	boolean
	*/
	function checkDatabaseHost()
	{
        //connect to databasehost
		$db = DB::connect($this->dsn_host);

		if (DB::isError($db))
		{
			$this->error_msg = $db->getMessage();
			$this->error = "data_invalid";
			return false;
		}
		
		return true;
	}
	
	/**
	* check database connection
	* @return	array
	*/
	function checkDatabaseExists()
	{
		//try to connect to database
		$db = DB::connect($this->dsn);
		if (DB::isError($db))
		{
			$arr["status"] = false;
			$arr["comment"] = "Please check your database connection!";
		}
		else
		{
			$arr["status"] = true;
			$arr["comment"] = "";
		}

		return $arr;
	}
	
	/**
	* check if inifile exists
	* @return	boolean
	*/
    function checkIniFileExists()
    {
		$a = file_exists($this->INI_FILE);
		return $a;		
    }
    
	/**
	* check for writable directory
	* @param	string	directory
	* @return	array
	*/
    function checkWritable($a_dir = ".")
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
			$arr["comment"] = "Cannot write in folder! Please check your permission settings in your filesystem!";
		}

		return $arr;
	}

	/**
	* check for permission to create new folders in specified directory
	* @param	string	directory
	* @return	array
	*/
    function checkCreatable($a_dir = ".")
    {
		clearstatcache();
		if (mkdir($a_dir."/crtst", 0774))
		{
			$arr["status"] = true;
			$arr["comment"] = "";
			
			rmdir($a_dir."/crtst");
		}
		else
		{
			$arr["status"] = false;
			$arr["comment"] = "Cannot create subfolders! Please check your permission settings in your filesystem!";
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
				$arr["comment"] = "Your PHP version is much too old for using ILIAS 3! Please upgrade your PHP.";
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
					$arr["comment"] = "DOMXML and XSLT support won't work properly with this version!";	
				}
				else
				{
					$arr["status"] = false;
					$arr["comment"] = "PEAR classes won't work properly with this version! Please upgrade your PHP.";		
				}
				break;
				
			case 5:
				$arr["status"] = true;
				$arr["comment"] = "";	
				break;
				
			default:
				$arr["status"] = true;
				$arr["comment"] = "ILIAS setup don't know this version. Use with own risk!";
				break;
		}

		return $arr;
	}
	
	/**
	* preliminaries
	* 
	* check if different things are ok for setting up ilias
	* @return boolean
	*/
	function preliminaries()
	{
		$a = array();
		$a["php"] = $this->checkPHPVersion();
		$a["root"] = $this->checkWritable();
		$a["create"] = $this->checkCreatable();
		$a["db"] = $this->checkDatabaseExists();
		
		//return value
		return $a;
	}
	
	/**
	* get all setup languages in the system
	* 
	* the functions looks for setup*.lang-files in the languagedirectory
	* @access	public
	* @return	array	langs
	*/
	function getLanguages($a_lang_path)
	{
		$d = dir($a_lang_path);
		$tmpPath = getcwd();
		chdir ($a_lang_path);
	
		// get available lang-files
		while ($entry = $d->read())
		{
			if (is_file($entry) && (ereg ("(^setup_.{2}\.lang)", $entry)))
			{
				$lang_key = substr($entry,6,2);
				$languages[] = $lang_key;
			}
		}

		chdir($tmpPath);
		return $languages;
	}

    /**
    * destructor
	* 
    * @return boolean
    */
    function _Setup()
	{
		if ($this->readVariable("db","type") != "")
		{
			$this->db->disconnect();
        }
		return true;
    }
} // END class.Setup
?>