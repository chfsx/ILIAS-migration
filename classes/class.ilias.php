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
* ILIAS base class
* perform basic setup: init database handler, load configuration file,
* init user authentification & error handler, load object type definitions
*
* @author Sascha Hofmann <shofmann@databay.de>
* @version $Id$
*
* @extends PEAR
* @package ilias-core
* @todo review the concept how the object type definition is loaded. We need a concept to
* edit the definitions via webfrontend in the admin console.
*/
class ILIAS extends PEAR
{
	/**
	* ini file
	* @var string
	*/
 	var $INI_FILE;

	/**
	* database connector
	* @var string
	* @access public
	*/
	var $dsn = "";

	/**
	* database handle
	* @var object database
	* @access private
	*/
	var $db;

	/**
	* template path
	* @var string
	* @access private
	*/
	var $tplPath = "./templates/";

	/**
	* user account
	* @var object user
	* @access public
	*/
	var $account;

	/**
	* auth parameters
	* @var array
	* @access private
	*/
	var $auth_params = array();

	/**
	* auth handler
	* @var object auth
	* @access public
	*/
	var $auth;

 	/**
	* system settings
	* @var array
	* @access public
	*/
	var $ini = array();

	/**
	* Error Handling
	* @var object Error
	* @access public
	*/
	var $error_obj;

	/**
	* object factory
	*
	* @var object factory
	* @access public
	*/
	var $obj_factory;

	/**
	* styles
	*
	* @var	array	list of stylesheets
	* @access	public
	*/
	var $styles;

	/**
	* skins (template sets)
	*
	* @var	array	list of skins
	* @access	public
	*/
	var $skins;
	
	/**
	* Constructor
	* setup ILIAS global object
	* @access	public
	*/
	function ILIAS()
	{
		$this->INI_FILE = "./ilias.ini.php";
		$this->PEAR();

		// prepare file access to work with safe mode
		umask(0117);

		// get settings from ini file
		$this->ini = new ilIniFile($this->INI_FILE);
		$this->ini->read();

		// if no ini-file found switch to setup routine
		if ($this->ini->ERROR != "")
		{
			header("Location: ./setup.php?error=".$this->ini->ERROR);
			exit();
		}
		
		// set constants
		define ("DEBUG",$this->ini->readVariable("system","DEBUG"));
		define ("ROOT_FOLDER_ID",$this->ini->readVariable('system','ROOT_FOLDER_ID'));
		define ("SYSTEM_FOLDER_ID",$this->ini->readVariable('system','SYSTEM_FOLDER_ID'));
		define ("ROLE_FOLDER_ID",$this->ini->readVariable('system','ROLE_FOLDER_ID'));

		define ("MAXLENGTH_OBJ_TITLE",$this->ini->readVariable('system','MAXLENGTH_OBJ_TITLE'));		
		define ("MAXLENGTH_OBJ_DESC",$this->ini->readVariable('system','MAXLENGTH_OBJ_DESC'));

		define ("ILIAS_HTTP_PATH",$this->ini->readVariable('server','http_path'));
		define ("ILIAS_ABSOLUTE_PATH",$this->ini->readVariable('server','absolute_path'));

		define ("ANONYMOUS_USER_ID",$this->ini->readVariable('system','ANONYMOUS_USER_ID'));

		// build dsn of database connection and connect
		$this->dsn = $this->ini->readVariable("db","type").
					 "://".$this->ini->readVariable("db", "user").
					 ":".$this->ini->readVariable("db", "pass").
					 "@".$this->ini->readVariable("db", "host").
					 "/".$this->ini->readVariable("db", "name");
		
		$this->db = new ilDBx($this->dsn);
			
		// set anonymous user id
		//define ("ANONYMOUS_USER_ID",$this->getSetting("anonymous_user_id"));

		// build option string for PEAR::Auth
		$this->auth_params = array(
									'dsn'		  => $this->dsn,
									'table'       => $this->ini->readVariable("auth", "table"),
									'usernamecol' => $this->ini->readVariable("auth", "usercol"),
									'passwordcol' => $this->ini->readVariable("auth", "passcol")
									);

		// We use MySQL as storage container
		$this->auth = new Auth("DB", $this->auth_params,"",false);

		// Error Handling
		$this->error_obj = new ilErrorHandling();
		$this->setErrorHandling(PEAR_ERROR_CALLBACK,array($this->error_obj,'errorHandler'));

		// create instance of object factory
		require_once("classes/class.ilObjectFactory.php");
		$this->obj_factory =& new ilObjectFactory();
	}

	/**
	* Destructor
	* @access	private
	* @return	boolean
	*/
	function _ILIAS()
	{
		if ($this->ini->readVariable("db", "type") != "")
		{
			$this->db->disconnect();
		}
		
		return true;
	}

	/**
	* read one value from settingstable
	* @access	public
	* @param	string	keyword
	* @return	string	value
	*/
	function getSetting($a_keyword)
	{
		$query = "SELECT value FROM settings WHERE keyword='".$a_keyword."'";
		$res = $this->db->query($query);

		if ($res->numRows() > 0)
		{
			$row = $res->fetchRow();
			return $row[0];
		}
		else
		{
			return false;
		}
	}

	/**
	* read all values from settingstable
	* @access	public
	* @return	array	keyword/value pairs
	*/
	function getAllSettings()
	{
		$query = "SELECT * FROM settings";
		$res = $this->db->query($query);

		while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$arr[$row["keyword"]] = $row["value"];
		}
		
		return $arr;
	}

	/**
	* write one value to db-table settings
	* @access	public
	* @param	string		keyword
	* @param	string		value
	* @return	boolean		true on success
	* 
	* TODO: change to replace-statement
	*/
	function setSetting($a_key, $a_val)
	{
		$sql = "DELETE FROM settings WHERE keyword='".$a_key."'";
		$r = $this->db->query($sql);

		$sql = "INSERT INTO settings (keyword, value) VALUES ('".$a_key."','".$a_val."')";
		$r = $this->db->query($sql);

		return true;
	}

	/**
	* skin system: get all available skins from template directory
	* and store them in $this->skins
	* @access	public
	* @return	boolean	false if no skin was found
	* @author	Peter Gabriel <pgabriel@databay.de>
	*/
	function getSkins()
	{
		$skins = array();

		//open directory for reading and search for subdirectories
		//$tplpath = $this->ini->readVariable("server", "tpl_path");
		//$tplpath = "./templates";

		if ($dp = @opendir($this->tplPath))
		{
			while (($file = readdir($dp)) != false)
			{
				//is the file a directory?
				if (is_dir($this->tplPath.$file) && $file != "." && $file != ".." && $file != "CVS")
				{
					$skins[] = array(
						"name" => $file
					);
				}
			} // while
		}
		else
		{
			return false;
		}

		$this->skins = $skins;

		return true;
	}

	/**
	* skin system: get all available styles from current templates
	* and store them in $this->styles
	* @access	public
	* @param	string	name of template set/directory name
	* @return	boolean	false if no style was found
	* @author	Peter Gabriel <pgabriel@databay.de>
	*/
	function getStyles($a_skin)
	{
		$styles = array();

		//open directory for reading and search for subdirectories
		//$tplpath = $this->ini->readVariable("server", "tpl_path")."/".$skin;
		//$tplpath = "./templates/".$a_skin;

		if ($dp = @opendir($this->tplPath.$a_skin))
		{
			while (($file = readdir($dp)) != false)
			{
				//is the file a stylesheet?
				if (strpos($file, ".css") > 0)
				{
					$styles[] = array(
										"name" => substr($file,0,-4)
									);
				}
			} // while
		}
		else
		{
			return false;
		}
		
		$this->styles = $styles;

		return true;
	}
	
	/**
	* get first available stylesheet from skindirectory
	* @param	string
	* @return	string	style name
	* @access	public
	*/
	function getFirstStyle($a_skin)
	{
		if (!is_array($this->styles))
		{
			$this->getStyles($a_skin);
		}

		return $this->styles[0]["name"];
	}
	
	/**
	* check if a template name exists on the server
	* @param	string	template name
	* @return	boolean	true if file exists
	* @access	public
	*/
	function checkTemplate($a_name)
	{
		return file_exists($this->tplPath.$a_name);
	}
} // END class.ilias
?>
