<?php
/**
* ILIAS base class
*
* class to setup ILIAS
*
* @author Sascha Hofmann <shofmann@databay.de>
* @package ILIAS
* @access public
* @version $Id$
*/
include_once("./classes/class.IniFile.php");
class ILIAS extends PEAR
{
	/**
	 * ini file
	 * @const INI_FILE
	 */
 	var $INI_FILE = "./ilias.ini";

    /**
    *  database connector
    *  @var string
    *  @access public
	*/
    var $dsn = "";

    /**
    *  database handle
    *  @var object
    *  @access private
    */
    var $db = "";
	
    /**
    *  template path
    *  @var string
    *  @access private
    */
    var $tplPath = "";

    /**
    *  user account
    *  @var object
    *  @access public
	*/
    var $account = "";

    /**
    *  auth parameters
    *  @var array
    *  @access private
    */
    var $auth_params = array();
		
    /**
    *  auth handler
    *  @var object
    *  @access public
    */
    var $auth = "";
	
    /**
    *  operation list
    *  @var array
    *  @access private
    */
    var $operations = "";
	
    /**
    *  type definition
    *  @var array
    *  @access private
    */	
	var $typedefinition = array(
								"grp"  => "'grp','frm','le'",
								"cat"  => "'cat','frm','le','grp'",
								"frm"  => "",
								"le"   => "",
								"crs"  => "'le','frm','grp'",
								"adm"  => "'usrf','rolf','objf'",
								"usrf" => "'user'",
								"rolf" => "'role'",
								"objf" => "'type'"
							);
    /**
    *  system settings
    *  @var array
    *  @access public
    */
	var $ini = array();

	/**
    * constructor
	* 
	* setup ILIAS global object
	* 
	* @param void
    * @return boolean
    */
    function ILIAS()
    {
		// get settings from ini file
		$this->ini = new IniFile($this->INI_FILE);

		//check for error
		if ($this->ini->ERROR != "")
		{
			header("Location: ./setup.php?error=".$this->ini->ERROR);
		}
	
        // build dsn of database connection and connect
		$this->dsn = $this->ini->readVariable("db","type").
					 "://".$this->ini->readVariable("db", "user").
					 ":".$this->ini->readVariable("db", "pass").
					 "@".$this->ini->readVariable("db", "host").
					 "/".$this->ini->readVariable("db", "name");
		
		$this->db = DB::connect($this->dsn,true);
            
		if (DB::isError($this->db)) {
			die($this->db->getMessage());
		}
			
		// build option string for PEAR::Auth
		$this->auth_params = array(
									'dsn'		  => $this->dsn,
									'table'       => $this->ini->readVariable("auth", "table"),
									'usernamecol' => $this->ini->readVariable("auth", "usercol"),
									'passwordcol' => $this->ini->readVariable("auth", "passcol")
									);
		// set tplPath
		$this->tplPath = TUtil::setPathStr($this->ini->readVariable("server", "tpl_path"));
		
		// We use MySQL as storage container
		$this->auth = new Auth("DB",$this->auth_params,"",false);
		
		return true;
	}

    /**
    * destructor
	* 
	* @param void
    * @return boolean
    */
    function _ILIAS()
	{
		if ($this->ini->readVariable("db", "type") != "")
		{
			$this->db->disconnect();
        }
		
		return true;
    }
}
?>