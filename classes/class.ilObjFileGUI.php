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
* Class ilObjFileGUI
*
* @author Sascha Hofmann <shofmann@databay.de> 
* @version $Id$
*
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "class.ilObjectGUI.php";

class ilObjFileGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access	public
	*/
	function ilObjFileGUI($a_data,$a_id,$a_call_by_reference)
	{
		$this->type = "file";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference,false);

		$this->setReturnLocation("cut","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("clear","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("copy","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("link","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("paste","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("cancelDelete","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("cancel","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("confirmedDelete","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("removeFromSystem","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("undelete","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
	}

	/**
	* create new object form
	*
	* @access	public
	*/
	function createObject()
	{
		global $rbacsystem;
		
		if (!$rbacsystem->checkAccess("create_file", $_GET["ref_id"]))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		// fill in saved values in case of error
		$data = array();
		$data["fields"] = array();
		$data["fields"]["title"] = ilUtil::prepareFormOutput($_SESSION["error_post_vars"]["Fobject"]["title"],true);
		$data["fields"]["desc"] = ilUtil::stripSlashes($_SESSION["error_post_vars"]["Fobject"]["desc"]);
		$data["fields"]["file"] = $_SESSION["error_post_vars"]["Fobject"]["file"];

		$this->getTemplateFile("new",$this->type);

		foreach ($data["fields"] as $key => $val)
		{
			$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
			$this->tpl->setVariable(strtoupper($key), $val);
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setVariable("FORMACTION", $this->getFormAction("save","adm_object.php?cmd=gateway&ref_id=".$_GET["ref_id"]."&new_type=".$this->type));
		$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($this->type."_new"));
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt($this->type."_add"));
		$this->tpl->setVariable("CMD_SUBMIT", "save");
		$this->tpl->setVariable("TARGET", $this->getTargetFrame("save"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
	}

	/**
	* save object
	*
	* @access	public
	*/
	function saveObject()
	{
		global $rbacsystem, $objDefinition;

		$data = $_POST["Fobject"];

		// delete trailing '/' in filename
		if (substr($data["file"],-1) == '/')
		{
			$data["file"] = substr($data["file"],0,-1);
		}

		if (empty($data["file"]))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_file"),$this->ilias->error_obj->MESSAGE);
		}

		if (empty($data["title"]))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_title"),$this->ilias->error_obj->MESSAGE);
		}

		if (empty($_FILES["Fobject"]["name"]["file"]))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_file_invalid"),$this->ilias->error_obj->MESSAGE);
		}

		// create and insert file in grp_tree
		include_once("classes/class.ilObjFile.php");
		$fileObj = new ilObjFile();
		$fileObj->setType($this->type);
		$fileObj->setTitle(ilUtil::stripSlashes($_POST["Fobject"]["title"]));
		$fileObj->setDescription(ilUtil::stripSlashes($_POST["Fobject"]["desc"]));
		$fileObj->setFileName($_FILES["Fobject"]["name"]["file"]);
		$fileObj->setFileType($_FILES["Fobject"]["type"]["file"]);
		$fileObj->create();
		$fileObj->createReference();
		//insert file in grp_tree
		$fileObj->putInTree($_GET["ref_id"]);
		// upload file to filesystem
		$fileObj->createDirectory();
		$fileObj->getUploadFile($_FILES["Fobject"]["tmp_name"]["file"],
			$_FILES["Fobject"]["name"]["file"]);

		sendInfo($this->lng->txt("file_added"),true);
		header("Location: group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		exit();
	}
} // END class.ilObjFileGUI
?>
