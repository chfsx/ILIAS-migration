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
* Class ilObjContentObjectGUI
*
* @author Stefan Meyer <smeyer@databay.de>
* @author Sascha Hofmann <shofmann@databay.de>
* @author Alex Killing <alex.killing@gmx.de>
* $Id$
*
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "classes/class.ilObjectGUI.php";
require_once "content/classes/class.ilObjContentObject.php";

class ilObjContentObjectGUI extends ilObjectGUI
{
	/**
	* Constructor
	*
	* @access	public
	*/
	function ilObjContentObjectGUI($a_data,$a_id = 0,$a_call_by_reference = true, $a_prepare_output = true)
	{
		global $lng;

		$lng->loadLanguageModule("content");
		parent::ilObjectGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);

	}

	/**
	* form for new content object creation
	*/
	function createObject()
	{
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		//$meta_gui->setObject($this->object);

		$meta_gui->setTargetFrame("save",$this->getTargetFrame("save"));

		$new_type = $_POST["new_type"] ? $_POST["new_type"] : $_GET["new_type"];

		$meta_gui->edit("ADM_CONTENT", "adm_content",
			$this->getFormAction("save","adm_object.php?ref_id=".$_GET["ref_id"]."&new_type=".$new_type."&cmd=save"));
	}

	/**
	* save new content object to db
	*/
	function saveObject()
	{
		global $rbacadmin, $rbacsystem;

		// always call parent method first to create an object_data entry & a reference
		//$newObj = parent::saveObject();
		// TODO: fix MetaDataGUI implementation to make it compatible to use parent call
		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $_GET["new_type"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_create_permission"), $this->ilias->error_obj->MESSAGE);
		}
		else
		{
			// create and insert object in objecttree
			include_once("content/classes/class.ilObjContentObject.php");
			$newObj = new ilObjContentObject();
			$newObj->setType($this->type);
			$newObj->setTitle("content object ".$newObj->getId());		// set by meta_gui->save
			$newObj->setDescription("");	// set by meta_gui->save
			$newObj->create();
			$newObj->createReference();
			$newObj->putInTree($_GET["ref_id"]);
			$newObj->setPermissions($_GET["ref_id"]);
			$newObj->notify("new",$_GET["ref_id"],$_GET["ref_id"]);
			
			// setup rolefolder & default local roles (moderator)
			$roles = $newObj->initDefaultRoles();

			// assign author role to creator of forum object
			//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "n");
			//ilObjUser::updateActiveRoles($newObj->getOwner());

			// save meta data
			include_once "classes/class.ilMetaDataGUI.php";
			$meta_gui =& new ilMetaDataGUI();
			$meta_gui->setObject($newObj);
			$meta_gui->save();

			// create content object tree
			$newObj->createLMTree();

			unset($newObj);

			// always send a message
			sendInfo($this->lng->txt("lm_added"),true);
			header("Location:".$this->getReturnLocation("save","adm_object.php?".$this->link_params));
			exit();
		}
	}

	function chooseMetaSectionObject()
	{
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->edit("ADM_CONTENT", "adm_content",
			"adm_object.php?ref_id=".$_GET["ref_id"], $_POST["meta_section"], $_POST["meta_language"]);
	}

	function editMetaObject()
	{
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->edit("ADM_CONTENT", "adm_content",
			"adm_object.php?ref_id=".$_GET["ref_id"]);
	}

	function saveMetaObject()
	{
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->save();
		header("Location: adm_object.php?ref_id=".$_GET["ref_id"]);
		exit;
	}



	/**
	* view object
	*
	* @access	public
	*/
	function viewObject()
	{
		global $rbacsystem, $tree, $tpl;


		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		// edit button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		if (!defined("ILIAS_MODULE"))
		{
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK","content/lm_edit.php?ref_id=".$this->object->getRefID());
			$this->tpl->setVariable("BTN_TARGET"," target=\"bottom\" ");
			$this->tpl->setVariable("BTN_TXT",$this->lng->txt("edit"));
			$this->tpl->parseCurrentBlock();
		}

		// view button
		$this->tpl->setCurrentBlock("btn_cell");
		if (!defined("ILIAS_MODULE"))
		{
			$this->tpl->setVariable("BTN_LINK","content/lm_presentation.php?ref_id=".$this->object->getRefID());
		}
		else
		{
			$this->tpl->setVariable("BTN_LINK","lm_presentation.php?ref_id=".$this->object->getRefID());
		}
		$this->tpl->setVariable("BTN_TARGET"," target=\"_top\" ");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("view"));
		$this->tpl->parseCurrentBlock();


		parent::viewObject();

		/*
		if ($this->object->getStyleSheetId() == 0)
		{
		}


		$lotree = new ilTree($_GET["ref_id"],ROOT_FOLDER_ID);

		//prepare objectlist
		$this->data = array();
		$this->data["data"] = array();
		$this->data["ctrl"] = array();

		$this->data["cols"] = array("", "view", "title", "description", "last_change");

		$lo_childs = $lotree->getChilds($_GET["ref_id"], $_GET["order"], $_GET["direction"]);

		foreach ($lo_childs as $key => $val)
		{
			// visible
			//if (!$rbacsystem->checkAccess("visible",$val["id"]))
			//{
			//	continue;
			//}
			//visible data part
			$this->data["data"][] = array(
					"type" => "<img src=\"".$this->tpl->tplPath."/images/enlarge.gif\" border=\"0\">",
					"title" => $val["title"],
					"description" => $val["desc"],
					"last_change" => $val["last_update"]
				);

			//control information
			$this->data["ctrl"][] = array(
					"type" => $val["type"],
					"ref_id" => $_GET["ref_id"],
					"lm_id" => $_GET["obj_id"],
					"lo_id" => $val["child"]
				);
	    } //foreach*/

		//parent::displayList();
	}

	/**
	* export object
	*
	* @access	public
	*/
	function exportObject()
	{
		return;
	}

	/**
	* display dialogue for importing XML-LeaningObjects
	*
	* @access	public
	*/
	function importObject()
	{
		$this->getTemplateFile("import", "lm");
		$this->tpl->setVariable("FORMACTION", "adm_object.php?&ref_id=".$_GET["ref_id"]."&cmd=gateway&new_type=".$this->type);
		$this->tpl->setVariable("BTN_NAME", "upload");
		$this->tpl->setVariable("TXT_UPLOAD", $this->lng->txt("upload"));
		$this->tpl->setVariable("TXT_IMPORT_LM", $this->lng->txt("import_lm"));
		/*
		$this->tpl->setVariable("TXT_PARSE", $this->lng->txt("parse"));
		$this->tpl->setVariable("TXT_VALIDATE", $this->lng->txt("validate"));
		$this->tpl->setVariable("TXT_PARSE2", $this->lng->txt("parse2"));*/
		$this->tpl->setVariable("TXT_SELECT_MODE", $this->lng->txt("select_mode"));
		$this->tpl->setVariable("TXT_SELECT_FILE", $this->lng->txt("select_file"));

	}


	/**
	* display status information or report errors messages
	* in case of error
	*
	* @access	public
	*/
	function uploadObject()
	{
		global $HTTP_POST_FILES, $rbacsystem;

		include_once "content/classes/class.ilObjLearningModule.php";

		// check if file was uploaded
		$source = $HTTP_POST_FILES["xmldoc"]["tmp_name"];
		if (($source == 'none') || (!$source))
		{
			$this->ilias->raiseError("No file selected!",$this->ilias->error_obj->MESSAGE);
		}
		// check create permission
		/*
		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $_GET["new_type"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_create_permission"), $this->ilias->error_obj->WARNING);
		}*/

		// check correct file type
		if ($HTTP_POST_FILES["xmldoc"]["type"] != "application/zip" && $HTTP_POST_FILES["xmldoc"]["type"] != "application/x-zip-compressed")
		{
			$this->ilias->raiseError("Wrong file type!",$this->ilias->error_obj->MESSAGE);
		}

		// create and insert object in objecttree
		$newObj = new ilObjLearningModule();
		$newObj->setType($_GET["new_type"]);
		$newObj->setTitle("dummy");			// set by meta_gui->save
		$newObj->setDescription("dummy");	// set by meta_gui->save
		$newObj->create();
		$newObj->createReference();
		$newObj->putInTree($_GET["ref_id"]);
		$newObj->setPermissions($_GET["ref_id"]);

		// create learning module tree
		$newObj->createLMTree();

		// create import directory
		$newObj->createImportDirectory();

		// copy uploaded file to import directory
		$file = pathinfo($_FILES["xmldoc"]["name"]);
		$full_path = $newObj->getImportDirectory()."/".$_FILES["xmldoc"]["name"];
		move_uploaded_file($_FILES["xmldoc"]["tmp_name"], $full_path);

		// unzip file
		ilUtil::unzip($full_path);

		// determine filename of xml file
		$subdir = basename($file["basename"],".".$file["extension"]);
		$xml_file = $newObj->getImportDirectory()."/".$subdir."/".$subdir.".xml";
//echo "xmlfile:".$xml_file;

		include_once ("content/classes/class.ilContObjParser.php");
		$contParser = new ilContObjParser($newObj, $xml_file, $subdir);
		$contParser->startParsing();

		header("Location: adm_object.php?".$this->link_params);
		exit();

	}

	/**
	* show chapters
	*/
	function chapters()
	{
		global $tree;

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.structure_edit.html", true);
		$num = 0;

		$this->tpl->setVariable("FORMACTION", "lm_edit.php?ref_id=".
			$this->object->getRefId()."&cmd=post&backcmd=chapters");
		$this->tpl->setVariable("HEADER_TEXT", $this->lng->txt("cont_chapters"));
		$this->tpl->setVariable("CHECKBOX_TOP", IL_FIRST_NODE);


		$cnt = 0;
		$childs = $this->lm_tree->getChilds($this->lm_tree->getRootId());
		foreach ($childs as $child)
		{
			if($child["type"] != "st")
			{
				continue;
			}

			$this->tpl->setCurrentBlock("table_row");
			// color changing
			$css_row = ilUtil::switchColor($cnt++,"tblrow1","tblrow2");

			// checkbox
			$this->tpl->setVariable("CHECKBOX_ID", $child["obj_id"]);
			$this->tpl->setVariable("CSS_ROW", $css_row);
			$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_cat.gif"));

			// type
			$link = "lm_edit.php?cmd=view&ref_id=".$this->object->getRefId()."&obj_id=".
				$child["obj_id"];
			$this->tpl->setVariable("LINK_TARGET", $link);

			// title
			$this->tpl->setVariable("TEXT_CONTENT", $child["title"]);

			$this->tpl->parseCurrentBlock();
		}
		if($cnt == 0)
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("NUM_COLS", 3);
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			// SHOW VALID ACTIONS
			$this->tpl->setVariable("NUM_COLS", 3);
			$this->showActions();
		}

		// SHOW POSSIBLE SUB OBJECTS
		$this->tpl->setVariable("NUM_COLS", 3);
		$subobj = array("st");
		$opts = ilUtil::formSelect(12,"new_type",$subobj);
		$this->tpl->setCurrentBlock("add_object");
		$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
		$this->tpl->setVariable("BTN_NAME", "create");
		$this->tpl->setVariable("TXT_ADD", $this->lng->txt("insert"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("form");
		$this->tpl->parseCurrentBlock();

	}


	/*
	* list all pages of learning module
	*/
	function pages()
	{
		global $tree;

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.all_pages.html", true);
		$num = 0;

		$this->tpl->setCurrentBlock("form");
		$this->tpl->setVariable("FORMACTION", "lm_edit.php?ref_id=".
			$this->object->getRefId()."&backcmd=pages&cmd=post");
		$this->tpl->setVariable("HEADER_TEXT", $this->lng->txt("cont_pages"));
		$this->tpl->setVariable("CONTEXT", $this->lng->txt("context"));
		$this->tpl->setVariable("CHECKBOX_TOP", IL_FIRST_NODE);

		$cnt = 0;
		$pages = ilLMPageObject::getPageList($this->object->getId());
		foreach ($pages as $page)
		{
			$this->tpl->setCurrentBlock("table_row");
			// color changing
			$css_row = ilUtil::switchColor($cnt++,"tblrow1","tblrow2");

			// checkbox
			$this->tpl->setVariable("CHECKBOX_ID", $page["obj_id"]);
			$this->tpl->setVariable("CSS_ROW", $css_row);
			$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_le.gif"));

			// type
			$link = "lm_edit.php?cmd=view&ref_id=".$this->object->getRefId()."&obj_id=".
				$page["obj_id"];
			$this->tpl->setVariable("LINK_TARGET", $link);

			// title
			$this->tpl->setVariable("TEXT_CONTENT", $page["title"]);

			// context
			if ($this->lm_tree->isInTree($page["obj_id"]))
			{
				$path_str = $this->getContextPath($page["obj_id"]);
			}
			else
			{
				$path_str = "---";
			}
			$this->tpl->setVariable("TEXT_CONTEXT", $path_str);

			$this->tpl->parseCurrentBlock();
		}
		if($cnt == 0)
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("NUM_COLS", 3);
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			// SHOW VALID ACTIONS
			$this->tpl->setVariable("NUM_COLS", 4);
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME", "delete");
			$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("delete"));
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("operation");
			$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
			$this->tpl->parseCurrentBlock();
		}

		// SHOW POSSIBLE SUB OBJECTS
		$this->tpl->setVariable("NUM_COLS", 4);
		//$this->showPossibleSubObjects("st");
		$subobj = array("pg");
		$opts = ilUtil::formSelect(12,"new_type",$subobj);
		$this->tpl->setCurrentBlock("add_object");
		$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
		$this->tpl->setVariable("BTN_NAME", "create");
		$this->tpl->setVariable("TXT_ADD", $this->lng->txt("create"));
		$this->tpl->parseCurrentBlock();


		$this->tpl->setCurrentBlock("form");
		$this->tpl->parseCurrentBlock();

	}

	/**
	* confirm deletion screen
	*/
	function delete()
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		// SAVE POST VALUES
		$_SESSION["saved_post"] = $_POST["id"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm_deletion.html", true);

		sendInfo($this->lng->txt("info_delete_sure"));
		$this->tpl->setVariable("FORMACTION", "lm_edit.php?ref_id=".
			$this->object->getRefId()."&backcmd=".$_GET["backcmd"]."&cmd=post");
		// BEGIN TABLE HEADER
		$this->tpl->setCurrentBlock("table_header");
		$this->tpl->setVariable("TEXT",$this->lng->txt("objects"));
		$this->tpl->parseCurrentBlock();

		// END TABLE HEADER

		// BEGIN TABLE DATA
		$counter = 0;
		foreach($_POST["id"] as $id)
		{
			$obj =& new ilLMObject($this->object, $id);
			switch($obj->getType())		// ok that's not so nice, could be done better
			{
				case "pg":
					$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_le.gif"));
					break;
				case "st":
					$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_cat.gif"));
					break;
			}
			$this->tpl->setCurrentBlock("table_row");
			$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
			$this->tpl->setVariable("TEXT_CONTENT", $obj->getTitle());
			$this->tpl->parseCurrentBlock();
		}

		// cancel/confirm button
		$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$buttons = array( "cancelDelete"  => $this->lng->txt("cancel"),
								  "confirmedDelete"  => $this->lng->txt("confirm"));
		foreach ($buttons as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
	}

	function cancelDelete()
	{
		session_unregister("saved_post");

		header("location: lm_edit.php?cmd=".$_GET["backcmd"]."&ref_id=".$this->object->getRefId());
		exit();

	}

	function confirmedDelete()
	{
		$tree = new ilTree($this->object->getId());
		$tree->setTableNames('lm_tree','lm_data');
		$tree->setTreeTablePK("lm_id");

		// check number of objects
		if (!isset($_SESSION["saved_post"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// delete all selected objects
		foreach ($_SESSION["saved_post"] as $id)
		{
			$obj =& ilLMObjectFactory::getInstance($this->object, $id);
			$obj->setLMId($this->object->getId());
			$node_data = $tree->getNodeData($id);
			$obj->delete();
			if($tree->isInTree($id))
			{
				$tree->deleteTree($node_data);
			}
		}

		// feedback
		sendInfo($this->lng->txt("info_deleted"),true);

		header("location: lm_edit.php?cmd=".$_GET["backcmd"]."&ref_id=".$this->object->getRefId());
		exit();
	}



	/**
	*
	*/
	function getContextPath($a_endnode_id, $a_startnode_id = 1)
	{
		$path = "";

		$tmpPath = $this->lm_tree->getPathFull($a_endnode_id, $a_startnode_id);

		// count -1, to exclude the learning module itself
		for ($i = 1; $i < (count($tmpPath) - 1); $i++)
		{
			if ($path != "")
			{
				$path .= " > ";
			}

			$path .= $tmpPath[$i]["title"];
		}

		return $path;
	}



	/**
	* show possible action (form buttons)
	*
	* @access	public
	*/
	function showActions()
	{
		$notoperations = array();
		// NO PASTE AND CLEAR IF CLIPBOARD IS EMPTY
		if (empty($_SESSION["clipboard"]))
		{
			$notoperations[] = "paste";
			$notoperations[] = "clear";
		}
		// CUT COPY PASTE LINK DELETE IS NOT POSSIBLE IF CLIPBOARD IS FILLED
		if ($_SESSION["clipboard"])
		{
			$notoperations[] = "cut";
			$notoperations[] = "copy";
			$notoperations[] = "link";
		}

		$operations = array();

		$d = $this->objDefinition->getActions("lm");

		foreach ($d as $row)
		{
			if (!in_array($row["name"], $notoperations))
			{
				$operations[] = $row;
			}
		}

		if (count($operations)>0)
		{
			foreach ($operations as $val)
			{
				$this->tpl->setCurrentBlock("operation_btn");
				$this->tpl->setVariable("BTN_NAME", $val["lng"]);
				$this->tpl->setVariable("BTN_VALUE", $this->lng->txt($val["lng"]));
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("operation");
			$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
			$this->tpl->parseCurrentBlock();
		}
	}

	function editMeta()
	{
		include_once("classes/class.ilMetaDataGUI.php");
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->edit("ADM_CONTENT", "adm_content", "lm_edit.php?ref_id=".
			$this->object->getRefId()."&cmd=saveMeta");
	}

	function saveMeta()
	{
		include_once("classes/class.ilMetaDataGUI.php");
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->save();
		header("location: lm_edit.php?cmd=view&ref_id=".$this->object->getRefId());
	}

	function perm()
	{
		$this->setFormAction("addRole", "lm_edit.php?ref_id=".$this->object->getRefId()."&cmd=addRole");
		$this->setFormAction("permSave", "lm_edit.php?ref_id=".$this->object->getRefId()."&cmd=permSave");
		$this->permObject();
	}

	function permSave()
	{
		$this->setReturnLocation("permSave", "lm_edit.php?ref_id=".$this->object->getRefId()."&cmd=perm");
		$this->permSaveObject();
	}

	function addRole()
	{
		$this->setReturnLocation("addRole", "lm_edit.php?ref_id=".$this->object->getRefId()."&cmd=perm");
		$this->addRoleObject();
	}

	function owner()
	{
		$this->ownerObject();
	}

	function view()
	{
		$this->viewObject();
	}

} // END class.ilObjContentObjectGUI
?>
