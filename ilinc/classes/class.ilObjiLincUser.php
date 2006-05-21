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
* Class ilObjiLincUser
* iLinc related user settings
*
* @author Sascha Hofmann <saschahofmann@gmx.de>
*
* @version $Id$
*
* @package iLinc
*/

class ilObjiLincUser
{
	/**
	* Constructor
	* @access	public
	* @param	object	ilias user 
	*/
	function ilObjiLincUser(&$a_user_obj)
	{
		global $ilias,$lng;

		$this->ilias =& $ilias;
		$this->lng =& $lng;
		$this->user =& $a_user_obj;
		
		$this->__init();
	}
	
	function __init()
	{
		global $ilErr;
		
		$q = "SELECT ilinc_id,ilinc_login,ilinc_passwd FROM usr_data ".
			 "WHERE usr_data.usr_id='".$this->user->getId()."'";
		$r = $this->ilias->db->query($q);
		
		if ($r->numRows() > 0)
		{
			$data = $r->fetchRow(DB_FETCHMODE_ASSOC);
			
			$this->id = $data['ilinc_id'];
			$this->login = $data['ilinc_login'];
			$this->passwd = $data['ilinc_passwd'];
		}
		else
		{
			$ilErr->raiseError("<b>Error: There is no dataset with id ".
							   $this->id."!</b><br />class: ".get_class($this)."<br />Script: ".__FILE__.
							   "<br />Line: ".__LINE__, $ilErr->FATAL);
		}
	}
	
	/**
	* updates ilinc data of a record "user" and write it into ILIAS database
	* @access	public
	*/
	function update()
	{
		$q = "UPDATE usr_data SET ".
            "last_update=now(), ".
            "ilinc_id='".ilUtil::prepareDBString($this->id)."', ".
            "ilinc_login='".ilUtil::prepareDBString($this->login)."', ".
            "ilinc_passwd='".ilUtil::prepareDBString($this->passwd)."' ".
            "WHERE usr_id='".$this->user->getId()."'";

		$this->ilias->db->query($q);
		
		return true;
	}
	
	function syncILIAS2iLinc()
	{
		// for future use
	}
	
	function synciLinc2ILIAS()
	{
		// for future use
	}
	
	function getErrorMsg()
	{
		$err_msg = $this->error_msg;
		$this->error_msg = "";

		return $err_msg;
	}
	
	/**
	 * creates login and password for ilinc
	 * login format is: <first 3 letter of ilias login> _ <user_id> _ <inst_id> _ <timestamp>
	 * passwd format is a random md5 hash
	 * 
	 */
	function __createLoginData($a_user_id,$a_user_login,$a_inst_id)
	{
		if (!$a_inst_id)
		{
			$a_inst_id = "0";
		}

		$data["login"] = substr($a_user_login,0,3)."_".$a_user_id."_".$a_inst_id."_".time();
		$data["passwd"] = md5(microtime().$a_user_login.rand(10000, 32000));
		
		return $data;
	}
	
	// create user account on iLinc server
	function add()
	{
		include_once ('class.ilnetucateXMLAPI.php');

		$this->ilincAPI = new ilnetucateXMLAPI();

		// create login and passwd for iLinc account
		$login_data = $this->__createLoginData($this->user->getId(),$this->user->getLogin(),$this->ilias->getSetting($inst_id));
		
		$this->ilincAPI->addUser($login_data,$this->user);
		$response = $this->ilincAPI->sendRequest();

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_add_user";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		$this->id = $response->getFirstID();
		$this->login = $login_data["login"];
		$this->passwd = $login_data["passwd"];

		$this->update();
		
		return true;
	}
} // END class.ilObjiLincUser
?>