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
* User Interface class for media pool objects
*
* @author Alex Killing <alex.killing@gmx.de>
*
* $Id$
*
* @extends ilObjectGUI
* @package content
*/

require_once("classes/class.ilObjectGUI.php");
require_once("content/classes/class.ilObjMediaPool.php");
require_once("classes/class.ilTableGUI.php");
require_once("classes/class.ilObjFolderGUI.php");
require_once("content/classes/Media/class.ilObjMediaObjectGUI.php");
require_once ("content/classes/class.ilEditClipboardGUI.php");

class ilObjMediaPoolGUI extends ilObjectGUI
{
	var $output_prepared;

	/**
	* Constructor
	*
	* @access	public
	*/
	function ilObjMediaPoolGUI($a_data,$a_id = 0,$a_call_by_reference = true, $a_prepare_output = true)
	{
		global $lng, $ilCtrl;

		$this->ctrl =& $ilCtrl;

		$this->ctrl->saveParameter($this, array("ref_id", "obj_id"));

		$this->type = "mep";
		$lng->loadLanguageModule("content");

		parent::ilObjectGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output);
		$this->output_prepared = $a_prepare_output;

		if (defined("ILIAS_MODULE"))
		{
			$this->setTabTargetScript("mep_edit.php");
		}
	}

	function _forwards()
	{
		return array("ilObjMediaObjectGUI", "ilObjFolderGUI", "ilEditClipboardGUI");
	}

	/**
	* execute command
	*/
	function executeCommand()
	{
		$tree =& $this->object->getTree();
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			case "ilobjmediaobjectgui":

				//$cmd.="Object";
				$ilObjMediaObjectGUI =& new ilObjMediaObjectGUI("", $_GET["obj_id"], false, false);
				if ($cmd == "create")
				{
					$ret_obj = $_GET["obj_id"];
				}
				else
				{
					$ret_obj = $tree->getParentId($_GET["obj_id"]);
				}
				if ($this->ctrl->getCmdClass() == "ilinternallinkgui")
				{
					$this->ctrl->setReturn($this, "explorer");
				}
				else
				{
					$this->ctrl->setParameter($this, "obj_id", $ret_obj);
					$this->ctrl->setReturn($this, "listMedia");
					$this->ctrl->setParameter($this, "obj_id", $_GET["obj_id"]);
				}
				$this->getTemplate();
				$ilObjMediaObjectGUI->setAdminTabs();
				$this->setLocator();

//echo ":".$tree->getParentId($_GET["obj_id"]).":";
				$ret =& $ilObjMediaObjectGUI->executeCommand();
//echo "<br>ilObjMediaPoolGUI:afterexecute:<br>"; exit;
				switch($cmd)
				{
					case "save":
						$parent = ($_GET["obj_id"] == "")
							? $tree->getRootId()
							: $_GET["obj_id"];
						$tree->insertNode($ret->getId(), $parent);
						ilUtil::redirect("mep_edit.php?cmd=listMedia&ref_id=".
							$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
						break;

					default:
						$this->tpl->show();
						break;
				}
				break;

			case "ilobjfoldergui":
				$folder_gui = new ilObjFolderGUI("", 0, false, false);
				$cmd.="Object";
				switch($cmd)
				{
					case "createObject":
						$this->prepareOutput();
						$folder_gui =& new ilObjFolderGUI("", 0, false, false);
						$folder_gui->setFormAction("save", "mep_edit.php?cmd=post&cmdClass=ilObjFolderGUI&ref_id=".
							$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
						$folder_gui->createObject();
						break;

					case "saveObject":
						$folder_gui->setReturnLocation("save", $this->ctrl->getLinkTarget($this, "listMedia"));
						$parent = ($_GET["obj_id"] == "")
							? $tree->getRootId()
							: $_GET["obj_id"];
						$folder_gui->setFolderTree($tree);
						$folder_gui->saveObject($parent);
						break;

					case "editObject":
						$this->prepareOutput();
						$folder_gui =& new ilObjFolderGUI("", $_GET["obj_id"], false, false);
						$folder_gui->setFormAction("update", "mep_edit.php?cmd=post&cmdClass=ilObjFolderGUI&ref_id=".
							$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
						$folder_gui->editObject();
						$this->tpl->show();
						break;

					case "updateObject":
						$folder_gui =& new ilObjFolderGUI("", $_GET["obj_id"], false, false);
						$folder_gui->setReturnLocation("update", $this->ctrl->getLinkTarget($this, "listMedia"));
						$folder_gui->updateObject();
						break;

					case "cancelObject":
						sendInfo($this->lng->txt("action_aborted"), true);
						$this->ctrl->redirect($this, "listMedia");
						break;
				}
				break;

			case "ileditclipboardgui":
				$this->prepareOutput();
				$this->ctrl->setReturn($this, "listMedia");
				$clip_gui = new ilEditClipboardGUI();
				$ret =& $clip_gui->executeCommand();
				$this->tpl->show();
				break;

			default:
				$cmd = $this->ctrl->getCmd("frameset");
				$this->$cmd();
				break;
		}
	}

	/**
	* save object
	* @access	public
	*/
	function saveObject()
	{
		global $rbacadmin;

		// create and insert forum in objecttree
		$newObj = parent::saveObject();

		// setup rolefolder & default local roles
		//$roles = $newObj->initDefaultRoles();

		// ...finally assign role to creator of object
		//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "y");

		// put here object specific stuff

		// always send a message
		sendInfo($this->lng->txt("object_added"),true);

		ilUtil::redirect($this->getReturnLocation("save","adm_object.php?".$this->link_params));
	}

	/**
	* edit properties of object (admin form)
	*
	* @access	public
	*/
	function editObject()
	{
		global $rbacsystem, $tree, $tpl;

		if (!$rbacsystem->checkAccess("visible,write",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		// edit button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		if (!defined("ILIAS_MODULE"))
		{
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK","content/mep_edit.php?ref_id=".$this->object->getRefID());
			$this->tpl->setVariable("BTN_TARGET"," target=\"bottom\" ");
			$this->tpl->setVariable("BTN_TXT",$this->lng->txt("edit"));
			$this->tpl->parseCurrentBlock();
		}

		parent::editObject();
	}

	/**
	* edit properties of object (module form)
	*/
	function edit()
	{
		$this->prepareOutput();
		$this->setFormAction("update", "mep_edit.php?cmd=post&ref_id=".$_GET["ref_id"].
			"&obj_id=".$_GET["obj_id"]);
		$this->editObject();
		$this->tpl->show();
	}

	/**
	* cancel editing
	*/
	function cancel()
	{
		$this->setReturnLocation("cancel","mep_edit.php?cmd=listMedia&ref_id=".$_GET["ref_id"].
			"&obj_id=".$_GET["obj_id"]);
		$this->cancelObject();
	}

	/**
	* update properties
	*/
	function update()
	{
		$this->setReturnLocation("update", "mep_edit.php?cmd=listMedia&ref_id=".$_GET["ref_id"].
			"&obj_id=".$_GET["obj_id"]);
		$this->updateObject();
	}

	/**
	* permission form
	*/
	function perm()
	{
		$this->prepareOutput();
		$this->setFormAction("permSave", "mep_edit.php?cmd=permSave&ref_id=".$_GET["ref_id"].
			"&obj_id=".$_GET["obj_id"]);
		$this->setFormAction("addRole", "mep_edit.php?ref_id=".$_GET["ref_id"].
			"&obj_id=".$_GET["obj_id"]."&cmd=addRole");
		$this->permObject();
		$this->tpl->show();
	}

	/**
	* save permissions
	*/
	function permSave()
	{
		$this->setReturnLocation("permSave",
			"mep_edit.php?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]."&cmd=perm");
		$this->permSaveObject();
	}

	/**
	* add role
	*/
	function addRole()
	{
		$this->setReturnLocation("addRole",
			"mep_edit.php?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]."&cmd=perm");
		$this->addRoleObject();
	}

	/**
	* show owner of media pool
	*/
	function owner()
	{
		$this->prepareOutput();
		$this->ownerObject();
		$this->tpl->show();
	}

	/**
	* list media objects
	*/
	function listMedia()
	{
		global $tree;

		if (!$this->output_prepared)
		{
			$this->prepareOutput();
		}

		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// create folder form button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK","mep_edit.php?ref_id=".$_GET["ref_id"].
			"&obj_id=".$_GET["obj_id"]."&cmd=create&cmdClass=ilObjFolderGUI");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("cont_create_folder"));
		$this->tpl->parseCurrentBlock();

		// create mob form button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK","mep_edit.php?ref_id=".$_GET["ref_id"].
			"&obj_id=".$_GET["obj_id"]."&cmd=create&cmdClass=ilObjMediaObjectGUI");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("cont_create_mob"));
		$this->tpl->parseCurrentBlock();

		$obj_id = ($_GET["obj_id"] == "")
			? $obj_id = $this->object->tree->getRootId()
			: $_GET["obj_id"];

		// create table
		require_once("classes/class.ilTableGUI.php");
		$tbl = new ilTableGUI();

		// load files templates
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");

		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.mep_list_row.html", true);

		$num = 0;

		$this->tpl->setVariable("FORMACTION", "mep_edit.php?ref_id=".$_GET["ref_id"].
			"&obj_id=".$_GET["obj_id"]."&cmd=post");

		$tbl->setTitle($this->lng->txt("cont_content"));

		$tbl->setHeaderNames(array("", $this->lng->txt("type"), $this->lng->txt("title")));

		$cols = array("", "type", "title");
		$header_params = array("ref_id" => $_GET["ref_id"],
			"obj_id" => $_GET["obj_id"], "cmd" => "listMedia");
		$tbl->setHeaderVars($cols, $header_params);
		$tbl->setColumnWidth(array("1%", "1%", "98%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);		// ???

		$this->tpl->setVariable("COLUMN_COUNTS", 3);

		// remove button
		$this->tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "confirmRemove");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("remove"));
		$this->tpl->parseCurrentBlock();

		// copy to clipboard
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "copyToClipboard");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("cont_copy_to_clipboard"));
		$this->tpl->parseCurrentBlock();

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		//$tbl->disable("footer");

		$objs = $this->object->getChilds($_GET["obj_id"]);

		$tbl->setMaxCount(count($objs));
		$objs = array_slice($objs, $_GET["offset"], $_GET["limit"]);

		$tbl->render();
		if(count($objs) > 0)
		{
			$i=0;
			foreach($objs as $obj)
			{
				$this->tpl->setCurrentBlock("link");
				$this->tpl->setVariable("TXT_TITLE", $obj["title"]);
				switch($obj["type"])
				{
					case "fold":
						$this->tpl->setVariable("LINK_VIEW",
							"mep_edit.php?cmd=ListMedia&ref_id=".$_GET["ref_id"].
							"&obj_id=".$obj["obj_id"]);
						break;

					case "mob":
						$this->tpl->setVariable("LINK_VIEW",
							"mep_edit.php?cmdClass=ilObjMediaObjectGUI&cmd=edit&ref_id=".$_GET["ref_id"].
							"&obj_id=".$obj["obj_id"]);
						break;
				}
				$this->tpl->parseCurrentBlock();

				// edit folder Link
				if ($obj["type"] == "fold")
				{
					$this->tpl->setCurrentBlock("link");
					$this->tpl->setVariable("TXT_TITLE", "[".$this->lng->txt("edit")."]");
					$this->tpl->setVariable("LINK_VIEW",
						"mep_edit.php?cmdClass=ilObjFolderGUI&cmd=edit&ref_id=".$_GET["ref_id"].
						"&obj_id=".$obj["obj_id"]);
					$this->tpl->parseCurrentBlock();
				}

				$this->tpl->setCurrentBlock("tbl_content");
				$css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
				$this->tpl->setVariable("CHECKBOX_ID", $obj["obj_id"]);
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_".$obj["type"].".gif"));

				$this->tpl->parseCurrentBlock();
			}
		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", 3);
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->parseCurrentBlock();
		$this->tpl->show();
	}

	function getTemplate()
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
	}

	/**
	* output main frameset of media pool
	* left frame: explorer tree of folders
	* right frame: media pool content
	*/
	function frameset()
	{
		$this->tpl = new ilTemplate("tpl.mep_edit_frameset.html", false, false, "content");
		$this->tpl->setVariable("REF_ID",$this->ref_id);
		$this->tpl->show();
	}

	/**
	* output explorer tree
	*/
	function explorer()
	{
		$this->tpl = new ilTemplate("tpl.main.html", true, true);

		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.explorer.html");

		require_once ("content/classes/class.ilMediaPoolExplorer.php");
		$exp = new ilMediaPoolExplorer("mep_edit.php?cmd=listMedia&ref_id=".$this->object->getRefId(), $this->object);
		$exp->setTargetGet("obj_id");
		$exp->setExpandTarget("mep_edit.php?cmd=explorer&ref_id=".$this->object->getRefId());

		$exp->addFilter("root");
		$exp->addFilter("fold");
		$exp->setFiltered(true);
		$exp->setFilterMode(IL_FM_POSITIVE);


		if ($_GET["mepexpand"] == "")
		{
			$mep_tree =& $this->object->getTree();
			$expanded = $mep_tree->readRootId();
		}
		else
		{
			$expanded = $_GET["mepexpand"];
		}

		$exp->setExpand($expanded);

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();

		$this->tpl->setCurrentBlock("content");
		$this->tpl->setVariable("TXT_EXPLORER_HEADER", $this->lng->txt("cont_mep_structure"));
		$this->tpl->setVariable("EXPLORER",$output);
		$this->tpl->setVariable("ACTION", "mep_edit.php?cmd=explorer&ref_id=".$this->ref_id."&mepexpand=".$_GET["mepexpand"]);
		$this->tpl->parseCurrentBlock();
		$this->tpl->show(false);

	}


	/**
	* confirm remove of mobs
	*/
	function confirmRemove()
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		$this->prepareOutput();

		// SAVE POST VALUES
		$_SESSION["ilMepRemove"] = $_POST["id"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm_deletion.html", true);

		sendInfo($this->lng->txt("info_delete_sure"));

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		// BEGIN TABLE HEADER
		$this->tpl->setCurrentBlock("table_header");
		$this->tpl->setVariable("TEXT",$this->lng->txt("objects"));
		$this->tpl->parseCurrentBlock();

		// BEGIN TABLE DATA
		$counter = 0;
		foreach($_POST["id"] as $obj_id)
		{
			$type = ilObject::_lookupType($obj_id);
			$title = ilObject::_lookupTitle($obj_id);
			$this->tpl->setCurrentBlock("table_row");
			$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
			$this->tpl->setVariable("TEXT_CONTENT", $title);
			$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_".$type.".gif"));
			$this->tpl->parseCurrentBlock();
		}

		// cancel/confirm button
		$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$buttons = array( "cancelRemove"  => $this->lng->txt("cancel"),
			"remove"  => $this->lng->txt("confirm"));
		foreach ($buttons as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->show();
	}

	/**
	* cancel deletion of media objects/folders
	*/
	function cancelRemove()
	{
		session_unregister("ilMepRemove");
		$this->ctrl->redirect($this, "listMedia");
	}

	/**
	* confirm deletion of
	*/
	function remove()
	{
		foreach($_SESSION["ilMepRemove"] as $obj_id)
		{
			$this->object->deleteChild($obj_id);
		}

		sendInfo($this->lng->txt("cont_obj_removed"),true);
		session_unregister("ilMepRemove");
		$this->ctrl->redirect($this, "listMedia");
	}


	/**
	* copy media objects to clipboard
	*/
	function copyToClipboard()
	{
		global $ilUser;

		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		foreach ($_POST["id"] as $obj_id)
		{
			$type = ilObject::_lookupType($obj_id);
			if ($type == "fold")
			{
				$this->ilias->raiseError($this->lng->txt("cont_cant_copy_folders"), $this->ilias->error_obj->MESSAGE);
			}
		}

		foreach ($_POST["id"] as $obj_id)
		{
			$ilUser->addObjectToClipboard($obj_id, "mob", "");
		}

		sendInfo($this->lng->txt("copied_to_clipboard"),true);
		$this->ctrl->redirect($this, "listMedia");
	}

	/**
	* set locator
	*/
	function setLocator($a_tree = "", $a_id = "", $scriptname="adm_object.php")
	{
		global $ilias_locator;
		if (!defined("ILIAS_MODULE"))
		{
			parent::setLocator();
		}
		else
		{
			$tree =& $this->object->getTree();
			$obj_id = ($_GET["obj_id"] == "")
				? $tree->getRootId()
				: $_GET["obj_id"];
			parent::setLocator($tree, $obj_id, "mep_edit.php?cmd=listMedia&ref_id=".$_GET["ref_id"],
				"obj_id", false, $this->object->getTitle());
		}
		return;

		if (!is_object($a_tree))
		{
			$a_tree =& $this->tree;
		}

		if (!($a_id))
		{
			$a_id = $_GET["ref_id"];
		}

		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");

		$path = $a_tree->getPathFull($a_id);

        //check if object isn't in tree, this is the case if parent_parent is set
		// TODO: parent_parent no longer exist. need another marker
		if ($a_parent_parent)
		{
			//$subObj = getObject($a_ref_id);
			$subObj =& $this->ilias->obj_factory->getInstanceByRefId($a_ref_id);

			$path[] = array(
				"id"	 => $a_ref_id,
				"title"  => $this->lng->txt($subObj->getTitle())
				);
		}

		// this is a stupid workaround for a bug in PEAR:IT
		$modifier = 1;

		if (isset($_GET["obj_id"]))
		{
			$modifier = 0;
		}

		// ### AA 03.11.10 added new locator GUI class ###
		$i = 1;

		foreach ($path as $key => $row)
		{
			if ($key < count($path)-$modifier)
			{
				$this->tpl->touchBlock("locator_separator");
			}

			$this->tpl->setCurrentBlock("locator_item");
			$this->tpl->setVariable("ITEM", $row["title"]);

			$this->tpl->setVariable("LINK_ITEM", $scriptname."?ref_id=".$row["child"]);
			$this->tpl->parseCurrentBlock();

			// ### AA 03.11.10 added new locator GUI class ###
			// navigate locator
			$ilias_locator->navigate($i++,$row["title"],$scriptname."?ref_id=".$row["child"],"bottom");
		}

		if (isset($_GET["obj_id"]))
		{
			$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($_GET["obj_id"]);

			$this->tpl->setCurrentBlock("locator_item");
			$this->tpl->setVariable("ITEM", $obj_data->getTitle());

			$this->tpl->setVariable("LINK_ITEM", $scriptname."?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
			$this->tpl->parseCurrentBlock();

			// ### AA 03.11.10 added new locator GUI class ###
			// navigate locator
			$ilias_locator->navigate($i++,$obj_data->getTitle(),$scriptname."?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"],"bottom");
		}

		$this->tpl->setCurrentBlock("locator");

		if (DEBUG)
		{
			$debug = "DEBUG: <font color=\"red\">".$this->type."::".$this->id."::".$_GET["cmd"]."</font><br/>";
		}

		$prop_name = $this->objDefinition->getPropertyName($_GET["cmd"],$this->type);

		if ($_GET["cmd"] == "confirmDeleteAdm")
		{
			$prop_name = "delete_object";
		}

		$this->tpl->setVariable("TXT_LOCATOR",$debug.$this->lng->txt("locator"));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* create folder form
	*/
	function createFolderForm()
	{
		$this->prepareOutput();

		$folder_gui =& new ilObjFolderGUI("", 0, false, false);
		$folder_gui->setFormAction("save", "mep_edit.php?cmd=post&cmdClass=ilObjFolderGUI&ref_id=".
			$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
		$folder_gui->createObject();
		//$this->tpl->show();
	}

	/**
	* prepare output
	*/
	function prepareOutput()
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");

		$title = $this->object->getTitle();

		// catch feedback message
		sendInfo();

		if (!empty($title))
		{
			$this->tpl->setVariable("HEADER", $title);
		}

		$this->setTabs();
		$this->setLocator();
	}

	/**
	* output tabs
	*/
	function setTabs()
	{
		// catch feedback message
		include_once("classes/class.ilTabsGUI.php");
		$tabs_gui =& new ilTabsGUI();
		$this->getTabs($tabs_gui);
		$this->tpl->setVariable("TABS", $tabs_gui->getHTML());
		//$this->tpl->setVariable("HEADER", $this->object->getTitle());
	}

	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		$tabs_gui->addTarget("view_content", $this->ctrl->getLinkTarget($this, "listMedia"),
			get_class($this), "listMedia");

		$tabs_gui->addTarget("edit_properties", $this->ctrl->getLinkTarget($this, "edit"),
			get_class($this), "edit");

		$tabs_gui->addTarget("permission_settings", $this->ctrl->getLinkTarget($this, "perm"),
			get_class($this), "perm");

		$tabs_gui->addTarget("show_owner", $this->ctrl->getLinkTarget($this, "owner"),
			get_class($this), "owner");

		$tabs_gui->addTarget("clipboard", $this->ctrl->getLinkTargetByClass("ilEditClipboardGUI", "view"),
			"ileditclipboardgui", "view");

	}



}
?>
