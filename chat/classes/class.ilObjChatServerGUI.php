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
* Class ilObjChatServerGUI
*
* @author Stefan Meyer <smeyer@databay.de>
* $Id$
*
* @extends ilObjectGUI
* @package chat
*/

require_once "classes/class.ilObjectGUI.php";

class ilObjChatServerGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjChatServerGUI($a_data,$a_id,$a_call_by_reference = true, $a_prepare_output = true)
	{
		define("ILIAS_MODULE","chat");

		$this->type = "chac";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference, $a_prepare_output);

		$this->lng->loadLanguageModule("chat");
	}

	function editObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.chac_edit.html",true);

		$ip = $_SESSION["error_post_vars"]["chat_ip"] ? 
			$_SESSION["error_post_vars"]["chat_ip"] :
			$this->object->server_conf->getIp();

		$port = $_SESSION["error_post_vars"]["chat_port"] ? 
			$_SESSION["error_post_vars"]["chat_port"] :
			$this->object->server_conf->getPort();

		$logfile = $_SESSION["error_post_vars"]["chat_logfile"] ? 
			$_SESSION["error_post_vars"]["chat_logfile"] :
			$this->object->server_conf->getLogfile();

		$loglevel = $_SESSION["error_post_vars"]["chat_loglevel"] ? 
			$_SESSION["error_post_vars"]["chat_loglevel"] :
			$this->object->server_conf->getLogLevel();

		$allowed = $_SESSION["error_post_vars"]["chat_allowed"] ? 
			$_SESSION["error_post_vars"]["chat_ip"] :
			$this->object->server_conf->getAllowedHosts();

		$active = $_SESSION["error_post_vars"]["chat_active"] ?
			$_SESSION["error_post_vars"]["chat_active"] :
			$this->object->server_conf->getActiveStatus();

		if($this->object->server_conf->isAlive())
		{
			$this->tpl->setCurrentBlock("chat_active");
			$this->tpl->setVariable("TXT_ACT_CHAT",$this->lng->txt("chat_ilias"));
			$this->tpl->setVariable("TXT_ACT_STATUS",$this->lng->txt("chat_status"));
			$this->tpl->setVariable("TXT_ACT_SUBMIT",$this->lng->txt("change"));
			$this->tpl->setVariable("SELECT_ACT_STATUS",$this->__getStatusSelect($active));
		}
			


		// SET TEXT VARIABLES
		$this->tpl->setVariable("FORMACTION", 
								$this->getFormAction("gateway","adm_object.php?ref_id=".$this->ref_id."&cmd=gateway"));
		$this->tpl->setVariable("TXT_CHAT_SERVER_SETTINGS","!!Chat Server Einstellungen");
		$this->tpl->setVariable("TXT_CHAT_SERVER_IP","!!Chat server ip");
		$this->tpl->setVariable("TXT_CHAT_SERVER_PORT","!Chat server port");
		$this->tpl->setVariable("TXT_CHAT_SERVER_LOGFILE","!Chat server logfile");
		$this->tpl->setVariable("TXT_CHAT_SERVER_LEVEL","!Chat server log level");
		$this->tpl->setVariable("TXT_CHAT_SERVER_ALLOWED","!Chat server allowed");
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->setVariable("TXT_CANCEL",$this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_SUBMIT",$this->lng->txt("save"));

		// SET SETTING VARS
		$this->tpl->setVariable("CHAT_SERVER",$ip);
		$this->tpl->setVariable("CHAT_PORT",$port);
		$this->tpl->setVariable("CHAT_LOGFILE",$logfile);
		$this->tpl->setVariable("CHAT_ALLOWED",$allowed);
		$this->tpl->setVariable("SELECT_LEVEL",$this->__getLogLevelSelect($level));
		$this->tpl->parseCurrentBlock();
		
		return true;
	}

	function updateObject()
	{
		$this->object->server_conf->setIp($_POST["chat_ip"]);
		$this->object->server_conf->setPort($_POST["chat_port"]);
		$this->object->server_conf->setLogfile($_POST["chat_logfile"]);
		$this->object->server_conf->setLogLevel($_POST["chat_loglevel"]);
		$this->object->server_conf->setAllowedHosts($_POST["chat_allowed"]);

		if(!$this->object->server_conf->validate())
		{
			$this->ilias->raiseError($this->object->server_conf->getErrorMessage(),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			$this->object->server_conf->update();
		}
		sendInfo($this->lng->txt("chat_settings_saved"),true);
		header("location: ".$this->getReturnLocation("update","adm_object.php?ref_id=$_GET[ref_id]"));
		exit;
	}

	function activateObject()
	{
		$this->object->server_conf->setActiveStatus((bool) $_POST["chat_active"]);
		$this->object->server_conf->updateStatus();

		sendInfo($this->lng->txt("chat_status_saved"),true);
		header("location: ".$this->getReturnLocation("update","adm_object.php?ref_id=$_GET[ref_id]"));
		exit;
	}

	// PRIVATE
	function __getLogLevelSelect($a_level)
	{
		$levels = array(0 => $this->lng->txt("chat_level_no"),
						1 => $this->lng->txt("chat_level_fatal"),
						2 => $this->lng->txt("chat_level_error"),
						3 => $this->lng->txt("chat_level_info"),
						4 => $this->lng->txt("chat_level_debug"),
						5 => $this->lng->txt("chat_level_all"));

		return ilUtil::formSelect($a_level,"chat_loglevel",$levels,false,true);
	}
	function __getStatusSelect($a_status)
	{
		$stati = array(1 => $this->lng->txt("chat_active"),
					   0 => $this->lng->txt("chat_inactive"));

		return ilUtil::formSelect($a_status,"chat_active",$stati,false,true);
	}

} // END class.ilObjChatServerGUI

?>
