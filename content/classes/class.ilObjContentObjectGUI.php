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
require_once ("classes/class.ilObjStyleSheetGUI.php");
require_once ("content/classes/class.ilLMPageObjectGUI.php");
require_once ("content/classes/class.ilStructureObjectGUI.php");

class ilObjContentObjectGUI extends ilObjectGUI
{
	var $ctrl;

	/**
	* Constructor
	*
	* @access	public
	*/
	function ilObjContentObjectGUI($a_data,$a_id = 0,$a_call_by_reference = true, $a_prepare_output = true)
	{
		global $lng, $ilCtrl;

		$this->ctrl =& $ilCtrl;
		$lng->loadLanguageModule("content");
		parent::ilObjectGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);
		$this->actions = $this->objDefinition->getActions("lm");

	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		// get next class that processes or forwards current command
		$next_class = $this->ctrl->getNextClass($this);

		// get current command
		$cmd = $this->ctrl->getCmd();


		switch($next_class)
		{
			case "ilobjstylesheetgui":
				$this->ctrl->setReturn($this, "properties");
				$style_gui =& new ilObjStyleSheetGUI("", $this->object->getStyleSheetId(), false);
				$ret =& $style_gui->executeCommand();

				if ($cmd == "save")
				{
					$style_id = $ret;
					$this->object->setStyleSheetId($style_id);
					$this->object->update();
					$this->ctrl->redirect($this, "properties");
				}
				break;

			case "illmpageobjectgui":
				$this->ctrl->setReturn($this, "properties");
//echo "!";
				//$this->lm_obj =& $this->ilias->obj_factory->getInstanceByRefId($this->ref_id);

				$pg_gui =& new ilLMPageObjectGUI($this->object);
				if ($_GET["obj_id"] != "")
				{
					$obj =& ilLMObjectFactory::getInstance($this->object, $_GET["obj_id"]);
					$pg_gui->setLMPageObject($obj);
				}
				$ret =& $pg_gui->executeCommand();
				if ($cmd == "save" || $cmd == "cancel")
				{
					$this->ctrl->redirect($this, "pages");
				}
				break;

			case "ilstructureobjectgui":
				$this->ctrl->setReturn($this, "properties");
				$st_gui =& new ilStructureObjectGUI($this->object, $this->object->lm_tree);
				if ($_GET["obj_id"] != "")
				{
					$obj =& ilLMObjectFactory::getInstance($this->object, $_GET["obj_id"]);
					$st_gui->setStructureObject($obj);
				}
				$ret =& $st_gui->executeCommand();
				if ($cmd == "save" || $cmd == "cancel")
				{
					if ($_GET["obj_id"] == "")
					{
						$this->ctrl->redirect($this, "chapters");
					}
					else
					{
						$this->ctrl->setCmd("subchap");
						$this->executeCommand();
					}
				}
				break;

			default:
				if ($cmd == "create")
				{
					switch ($_POST["new_type"])
					{
						case "pg":
							$this->setTabs();
							$this->ctrl->addTransit(get_class($this));
							$this->ctrl->setCmdClass("ilLMPageObjectGUI");
							$ret =& $this->executeCommand();
							break;

						case "st":
							$this->setTabs();
							$this->ctrl->addTransit(get_class($this));
							$this->ctrl->setCmdClass("ilStructureObjectGUI");
							$ret =& $this->executeCommand();
							break;
					}
				}
				else
				{
					$ret =& $this->$cmd();
				}
				break;
		}

		return $ret;
	}

	function _forwards()
	{
		return array("ilLMPageObjectGUI", "ilStructureObjectGUI","ilObjStyleSheetGUI");
	}

	/**
	* edit properties form
	*/
	function properties()
	{
		$this->setTabs();

		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// view button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK","lm_presentation.php?ref_id=".$this->object->getRefID());
		$this->tpl->setVariable("BTN_TARGET"," target=\"ilContObj".$this->object->getID()."\" ");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("view"));
		$this->tpl->parseCurrentBlock();

		// test purpose: create stylesheet
		if ($this->object->getStyleSheetId() == 0)
		{
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK",
				$this->ctrl->getLinkTargetByClass("ilObjStyleSheetGUI", "create",
					array(get_class($this))));
			//$this->tpl->setVariable("BTN_TARGET"," target=\"_top\" ");
			$this->tpl->setVariable("BTN_TXT",$this->lng->txt("create_stylesheet"));
			$this->tpl->parseCurrentBlock();
		}
		else // test purpose: edit stylesheet
		{
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK",
				$this->ctrl->getLinkTargetByClass("ilObjStyleSheetGUI", "edit",
					array(get_class($this))));
			//$this->tpl->setVariable("BTN_TARGET"," target=\"_top\" ");
			$this->tpl->setVariable("BTN_TXT",$this->lng->txt("edit_stylesheet"));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "exportList"));
		//$this->tpl->setVariable("BTN_TARGET"," target=\"_top\" ");
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("export"));
		$this->tpl->parseCurrentBlock();

		// lm properties
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_properties.html", true);
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_PROPERTIES", $this->lng->txt("cont_lm_properties"));

		// online
		$this->tpl->setVariable("TXT_ONLINE", $this->lng->txt("cont_online"));
		$this->tpl->setVariable("CBOX_ONLINE", "cobj_online");
		$this->tpl->setVariable("VAL_ONLINE", "y");
		if ($this->object->getOnline())
		{
			$this->tpl->setVariable("CHK_ONLINE", "checked");
		}

		// layout
		$this->tpl->setVariable("TXT_LAYOUT", $this->lng->txt("cont_def_layout"));
		$layouts = ilObjLearningModule::getAvailableLayouts();
		$select_layout = ilUtil::formSelect ($this->object->getLayout(), "lm_layout",
			$layouts, false, true);
		$this->tpl->setVariable("SELECT_LAYOUT", $select_layout);

		// page header
		$this->tpl->setVariable("TXT_PAGE_HEADER", $this->lng->txt("cont_page_header"));
		$pg_header = array ("st_title" => $this->lng->txt("cont_st_title"),
			"pg_title" => $this->lng->txt("cont_pg_title"),
			"none" => $this->lng->txt("cont_none"));
		$select_pg_head = ilUtil::formSelect ($this->object->getPageHeader(), "lm_pg_header",
			$pg_header, false, true);
		$this->tpl->setVariable("SELECT_PAGE_HEADER", $select_pg_head);

		// toc mode
		$this->tpl->setVariable("TXT_TOC_MODE", $this->lng->txt("cont_toc_mode"));
		$arr_toc_mode = array ("chapters" => $this->lng->txt("cont_chapters_only"),
			"pages" => $this->lng->txt("cont_chapters_and_pages"));
		$select_toc_mode = ilUtil::formSelect ($this->object->getTOCMode(), "toc_mode",
			$arr_toc_mode, false, true);
		$this->tpl->setVariable("SELECT_TOC_MODE", $select_toc_mode);

		// clean frames
		$this->tpl->setVariable("TXT_CLEAN_FRAMES", $this->lng->txt("cont_clean_frames"));
		$this->tpl->setVariable("CBOX_CLEAN_FRAMES", "cobj_clean_frames");
		$this->tpl->setVariable("VAL_CLEAN_FRAMES", "y");
		if ($this->object->cleanFrames())
		{
			$this->tpl->setVariable("CHK_CLEAN_FRAMES", "checked");
		}

		// lm menu
		$this->tpl->setVariable("TXT_LM_MENU", $this->lng->txt("cont_lm_menu"));
		$this->tpl->setVariable("TXT_ACT_MENU", $this->lng->txt("cont_active"));
		$this->tpl->setVariable("CBOX_LM_MENU", "cobj_act_lm_menu");
		$this->tpl->setVariable("VAL_LM_MENU", "y");
		if ($this->object->isActiveLMMenu())
		{
			$this->tpl->setVariable("CHK_LM_MENU", "checked");
		}

		// toc
		$this->tpl->setVariable("TXT_TOC", $this->lng->txt("cont_toc"));
		$this->tpl->setVariable("CBOX_TOC", "cobj_act_toc");
		$this->tpl->setVariable("VAL_TOC", "y");
		if ($this->object->isActiveTOC())
		{
			$this->tpl->setVariable("CHK_TOC", "checked");
		}

		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "saveProperties");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* save properties
	*/
	function saveProperties()
	{
		$this->object->setLayout($_POST["lm_layout"]);
		$this->object->setPageHeader($_POST["lm_pg_header"]);
		$this->object->setTOCMode($_POST["toc_mode"]);
		$this->object->setOnline(ilUtil::yn2tf($_POST["cobj_online"]));
		$this->object->setActiveLMMenu(ilUtil::yn2tf($_POST["cobj_act_lm_menu"]));
		$this->object->setActiveTOC(ilUtil::yn2tf($_POST["cobj_act_toc"]));
		$this->object->setCleanFrames(ilUtil::yn2tf($_POST["cobj_clean_frames"]));
		$this->object->updateProperties();
		sendInfo($this->lng->txt("msg_obj_modified"), true);
		$this->ctrl->redirect($this, "properties");
	}

	/**
	* form for new content object creation
	*/
	function createObject()
	{

		parent::createObject();
		return;

		// TEMPORALIY DISABLED
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
			$newObj->setTitle($_POST["Fobject"]["title"]);#"content object ".$newObj->getId());		// set by meta_gui->save
			$newObj->setDescription($_POST["Fobject"]["desc"]);	// set by meta_gui->save
			$newObj->create();
			$newObj->createReference();
			$newObj->putInTree($_GET["ref_id"]);
			$newObj->setPermissions($_GET["ref_id"]);
			$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());

			// setup rolefolder & default local roles (moderator)
			//$roles = $newObj->initDefaultRoles();
			// assign author role to creator of forum object
			//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "n");
			//ilObjUser::updateActiveRoles($newObj->getOwner());
			// create content object tree
			$newObj->createLMTree();
			unset($newObj);

			// always send a message
			sendInfo($this->lng->txt("lm_added"), true);
			ilUtil::redirect($this->getReturnLocation("save","adm_object.php?".$this->link_params));
		}
	}

	// called by administration
	function chooseMetaSectionObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->edit("ADM_CONTENT", "adm_content",
			$a_target, $_REQUEST["meta_section"]);
	}

	// called by editor
	function chooseMetaSection()
	{
		$this->setTabs();
//echo "<br>target:".$this->ctrl->getLinkTarget($this).":";
		$this->chooseMetaSectionObject($this->ctrl->getLinkTarget($this));
	}

	function addMetaObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_name = $_POST["meta_name"] ? $_POST["meta_name"] : $_GET["meta_name"];
		$meta_index = $_POST["meta_index"] ? $_POST["meta_index"] : $_GET["meta_index"];
		if ($meta_index == "")
			$meta_index = 0;
		$meta_path = $_POST["meta_path"] ? $_POST["meta_path"] : $_GET["meta_path"];
		$meta_section = $_POST["meta_section"] ? $_POST["meta_section"] : $_GET["meta_section"];
		if ($meta_name != "")
		{
			$meta_gui->meta_obj->add($meta_name, $meta_path, $meta_index);
		}
		else
		{
			sendInfo($this->lng->txt("meta_choose_element"), true);
		}
		$meta_gui->edit("ADM_CONTENT", "adm_content", $a_target, $meta_section);
	}

	function addMeta()
	{
		$this->setTabs();
		$this->addMetaObject($this->ctrl->getLinkTarget($this));
	}

	/**
	* add bib item (admin call)
	*/
	function addBibItemObject($a_target = "")
	{
		include_once "content/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemName = $_POST["bibItemName"] ? $_POST["bibItemName"] : $_GET["bibItemName"];
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		if ($bibItemIndex == "")
			$bibItemIndex = 0;
		$bibItemPath = $_POST["bibItemPath"] ? $_POST["bibItemPath"] : $_GET["bibItemPath"];
		if ($bibItemName != "")
		{
			$bib_gui->bib_obj->add($bibItemName, $bibItemPath, $bibItemIndex);
			$data = $bib_gui->bib_obj->getElement("BibItem");
			$bibItemIndex = (count($data) - 1);
		}
		else
		{
			sendInfo($this->lng->txt("bibitem_choose_element"), true);
		}
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* add bib item (module call)
	*/
	function addBibItem()
	{
		$this->setTabs();
		$this->addBibItemObject($this->ctrl->getLinkTarget($this));
	}

	function deleteMetaObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_index = $_POST["meta_index"] ? $_POST["meta_index"] : $_GET["meta_index"];
		$meta_gui->meta_obj->delete($_GET["meta_name"], $_GET["meta_path"], $meta_index);
		$meta_gui->edit("ADM_CONTENT", "adm_content", $a_target, $_GET["meta_section"]);
	}

	function deleteMeta()
	{
		$this->setTabs();
		$this->deleteMetaObject($this->ctrl->getLinkTarget($this));
	}

	/**
	* delete bib item (admin call)
	*/
	function deleteBibItemObject($a_target = "")
	{
		include_once "content/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		$bib_gui->bib_obj->delete($_GET["bibItemName"], $_GET["bibItemPath"], $bibItemIndex);
		if (strpos($bibItemIndex, ",") > 0)
		{
			$bibItemIndex = substr($bibItemIndex, 0, strpos($bibItemIndex, ","));
		}
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* delete bib item (module call)
	*/
	function deleteBibItem()
	{
		$this->setTabs();
		$this->deleteBibItemObject($this->ctrl->getLinkTarget($this));
	}

	function editMetaObject($a_target = "")
	{

		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
//echo "target:$a_target:";
		$meta_gui->edit("ADM_CONTENT", "adm_content", $a_target, $_GET["meta_section"]);
	}

	function editMeta()
	{
		$this->setTabs();
		$this->editMetaObject($this->ctrl->getLinkTarget($this));
	}

	/**
	* edit bib items (admin call)
	*/
	function editBibItemObject($a_target = "")
	{
		include_once "content/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		$bibItemIndex *= 1;
		if ($bibItemIndex < 0)
		{
			$bibItemIndex = 0;
		}
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* edit bib items (module call)
	*/
	function editBibItem()
	{
		$this->setTabs();
		$this->editBibItemObject($this->ctrl->getLinkTarget($this));
	}

	function saveMetaObject($a_target = "adm_object.php")
	{
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
//echo "title_value:".htmlentities($_POST["meta"]["Title"]["Value"]); exit;
		$meta_gui->save($_POST["meta_section"]);
		ilUtil::redirect($a_target . "?cmd=editMeta&ref_id=" . $this->object->getRefId() . "&meta_section=" . $_POST["meta_section"]);
	}

	function saveMeta()
	{
		$this->saveMetaObject("lm_edit.php");
	}

	/**
	* save bib item (admin call)
	*/
	function saveBibItemObject($a_target = "")
	{
		include_once "content/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		$bibItemIndex *= 1;
		if ($bibItemIndex < 0)
		{
			$bibItemIndex = 0;
		}
		$bibItemIndex = $bib_gui->save($bibItemIndex);

		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* save bib item (module call)
	*/
	function saveBibItem()
	{
		$this->setTabs();
		$this->saveBibItemObject($this->ctrl->getLinkTarget($this));
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
		include_once("content/classes/class.ilObjContentObject.php");
		$newObj = new ilObjContentObject();
		$newObj->setType($_GET["new_type"]);
		$newObj->setTitle("dummy");
		$newObj->setDescription("dummy");
		$newObj->create(true);
		$newObj->createReference();
		$newObj->putInTree($_GET["ref_id"]);
		$newObj->setPermissions($_GET["ref_id"]);
		$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());

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

		/* update title and description in object data */
		if (is_object($newObj->meta_data))
		{
			$newObj->meta_data->read();
			$newObj->setTitle($newObj->meta_data->getTitle());
			$newObj->setDescription($newObj->meta_data->getDescription());
			$q = "UPDATE object_data SET title = '" . $newObj->getTitle() . "', description = '" . $newObj->getDescription() . "' WHERE obj_id = '" . $newObj->getID() . "'";
			$this->ilias->db->query($q);
		}

		ilUtil::redirect("adm_object.php?".$this->link_params);

	}

	/**
	* show chapters
	*/
	function chapters()
	{
		global $tree;

		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.structure_edit.html", true);
		$num = 0;

		$this->ctrl->setParameter($this, "backcmd", "chapters");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
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

			// link
			$this->ctrl->setParameter($this, "backcmd", "");
			$this->ctrl->setParameterByClass("ilStructureObjectGUI", "obj_id", $child["obj_id"]);
			$this->tpl->setVariable("LINK_TARGET",
				$this->ctrl->getLinkTargetByClass("ilStructureObjectGUI", "view", array(get_class($this))));

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
			$acts = array("delete" => "delete", "move" => "moveChapter");
			if (ilEditClipboard::getContentObjectType() == "st")
			{
				if ($this->lm_tree->isInTree(ilEditClipboard::getContentObjectId()))
				{
					$acts["pasteChapter"] =  "pasteChapter";
				}
			}
			$this->setActions($acts);
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

		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.all_pages.html", true);
		$num = 0;

		$this->tpl->setCurrentBlock("form");
		$this->ctrl->setParameter($this, "backcmd", "pages");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
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

			// link
			$this->ctrl->setParameter($this, "backcmd", "");
			$this->ctrl->setParameterByClass("ilLMPageObjectGUI", "obj_id", $page["obj_id"]);
//echo "<br>:".$this->ctrl->getLinkTargetByClass("ilLMPageObjectGUI", "view").":";
			$this->tpl->setVariable("LINK_TARGET",
				$this->ctrl->getLinkTargetByClass("ilLMPageObjectGUI", "view", array(get_class($this))));

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
			$this->tpl->setVariable("NUM_COLS", 4);
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$acts = array("delete" => "delete", "movePage" => "movePage");
			/*
			if (ilEditClipboard::getContentObjectType() == "st")
			{
				$acts["pasteChapter"] =  "pasteChapter";
			}*/
			$this->setActions($acts);
			$this->tpl->setVariable("NUM_COLS", 4);
			$this->showActions();

			// SHOW VALID ACTIONS
			/*
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME", "delete");
			$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("delete"));
			$this->tpl->parseCurrentBlock();*/

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
	* confirm deletion screen for page object and structure object deletion
	*
	* @param	int		$a_parent_subobj_id		id of parent object (structure object)
	*											of the objects, that should be deleted
	*											(or no parent object id for top level)
	*/
	function delete($a_parent_subobj_id = 0)
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		if(count($_POST["id"]) == 1 && $_POST["id"][0] == IL_FIRST_NODE)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_item"), $this->ilias->error_obj->MESSAGE);
		}

		if ($a_parent_subobj_id == 0)
		{
			$this->setTabs();
		}

		// SAVE POST VALUES
		$_SESSION["saved_post"] = $_POST["id"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm_deletion.html", true);

		sendInfo($this->lng->txt("info_delete_sure"));

		if ($a_parent_subobj_id != 0)
		{
			$this->ctrl->setParameterByClass("ilStructureObjectGUI", "backcmd", $_GET["backcmd"]);
			$this->ctrl->setParameterByClass("ilStructureObjectGUI", "obj_id", $a_parent_subobj_id);
			$this->tpl->setVariable("FORMACTION",
				$this->ctrl->getFormActionByClass("ilStructureObjectGUI"));
		}
		else
		{
			$this->ctrl->setParameter($this, "backcmd", $_GET["backcmd"]);
			$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		}
		// BEGIN TABLE HEADER
		$this->tpl->setCurrentBlock("table_header");
		$this->tpl->setVariable("TEXT",$this->lng->txt("objects"));
		$this->tpl->parseCurrentBlock();

		// END TABLE HEADER

		// BEGIN TABLE DATA
		$counter = 0;
		foreach($_POST["id"] as $id)
		{
			if ($id != IL_FIRST_NODE)
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

	/**
	* cancel delete
	*/
	function cancelDelete()
	{
		session_unregister("saved_post");

		$this->ctrl->redirect($this, $_GET["backcmd"]);

	}

	/**
	* delete page object or structure objects
	*
	* @param	int		$a_parent_subobj_id		id of parent object (structure object)
	*											of the objects, that should be deleted
	*											(or no parent object id for top level)
	*/
	function confirmedDelete($a_parent_subobj_id = 0)
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
			if ($id != IL_FIRST_NODE)
			{
				$obj =& ilLMObjectFactory::getInstance($this->object, $id, false);
				$node_data = $tree->getNodeData($id);
				if (is_object($obj))
				{
					$obj->setLMId($this->object->getId());
					$obj->delete();
				}
				if($tree->isInTree($id))
				{
					$tree->deleteTree($node_data);
				}
			}
		}

		// check the tree
		$this->object->checkTree();

		// feedback
		sendInfo($this->lng->txt("info_deleted"),true);

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, $_GET["backcmd"]);
		}
	}



	/**
	* get context path in content object tree
	*
	* @param	int		$a_endnode_id		id of endnode
	* @param	int		$a_startnode_id		id of startnode
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

		$operations = array();

		$d = $this->actions;

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

	/**
	* edit permissions
	*/
	function perm()
	{
		$this->setTabs();

		$this->setFormAction("addRole", $this->ctrl->getLinkTarget($this, "addRole"));
		$this->setFormAction("permSave", $this->ctrl->getLinkTarget($this, "permSave"));
		$this->permObject();
	}


	/**
	* save permissions
	*/
	function permSave()
	{
		$this->setReturnLocation("permSave", $this->ctrl->getLinkTarget($this, "perm"));
		$this->permSaveObject();
	}


	/**
	* add local role
	*/
	function addRole()
	{
		$this->setReturnLocation("addRole", $this->ctrl->getLinkTarget($this, "perm"));
		$this->addRoleObject();
	}


	/**
	* show owner of content object
	*/
	function owner()
	{
		$this->setTabs();
		$this->ownerObject();
	}


	/**
	* view content object
	*/
	function view()
	{
		$this->viewObject();
	}


	/**
	* move a single chapter  (selection)
	*/
	function moveChapter($a_parent_subobj_id = 0)
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
//echo "Hallo::"; exit;
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		if(count($_POST["id"]) == 1 && $_POST["id"][0] == IL_FIRST_NODE)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_item"), $this->ilias->error_obj->MESSAGE);
		}

		// SAVE POST VALUES
		ilEditClipboard::storeContentObject("st", $_POST["id"][0]);

		sendInfo($this->lng->txt("cont_chap_select_target_now"), true);

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}


	/**
	* paste chapter
	*/
	function pasteChapter($a_parent_subobj_id = 0)
	{
		if (ilEditClipboard::getContentObjectType() != "st")
		{
			$this->ilias->raiseError($this->lng->txt("no_chapter_in_clipboard"),$this->ilias->error_obj->MESSAGE);
		}

		$tree = new ilTree($this->object->getId());
		$tree->setTableNames('lm_tree','lm_data');
		$tree->setTreeTablePK("lm_id");

		// cut selected object
		$id = ilEditClipboard::getContentObjectId();

		$node = $tree->getNodeData($id);

		$subnodes = $tree->getSubtree($node);

		// check, if target is within subtree
		foreach ($subnodes as $subnode)
		{
			if($subnode["obj_id"] == $a_parent_subobj_id)
			{
				$this->ilias->raiseError($this->lng->txt("cont_target_within_source"),$this->ilias->error_obj->MESSAGE);
			}
		}
		if($_POST["id"][0] == $id)
		{
			ilEditClipboard::clear();
			$this->ctrl->redirect($this, "chapters");
		}


		//echo ":".$id.":";
		// delete old tree entries
		$tree->deleteTree($node);
		if(!isset($_POST["id"]))
		{
			$target = IL_LAST_NODE;
		}
		else
		{
			$target = $_POST["id"][0];
		}

		// determin parent
		$parent = ($a_parent_subobj_id == 0)
			? $tree->getRootId()
			: $a_parent_subobj_id;

		// do not move a chapter in front of a page
		if($target == IL_FIRST_NODE)
		{
			$childs =& $tree->getChildsByType($parent, "pg");
			if (count($childs) != 0)
			{
				$target = $childs[count($childs) - 1]["obj_id"];
			}
		}


		if (!$tree->isInTree($id))
		{
			$tree->insertNode($id, $parent, $target);

			foreach ($subnodes as $node)
			{
				//$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);
				//$obj_data->putInTree($node["parent"]);
				if($node["obj_id"] != $id)
				{
					$tree->insertNode($node["obj_id"], $node["parent"]);
				}
			}
		}

		ilEditClipboard::clear();

		// check the tree
		$this->object->checkTree();

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}

	/**
	* move page
	*/
	function movePage()
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		// SAVE POST VALUES
		ilEditClipboard::storeContentObject("pg", $_POST["id"][0]);

		sendInfo($this->lng->txt("cont_page_select_target_now"), true);
		$this->ctrl->redirect($this, "pages");
	}

	/**
	* cancel action
	*/
	function cancel()
	{
		if ($_GET["new_type"] == "pg")
		{
			$this->ctrl->redirect($this, "pages");
		}
		else
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}


	/**
	* export content object
	*/
	function export()
	{
		require_once("content/classes/class.ilContObjectExport.php");
		$cont_exp = new ilContObjectExport($this->object);
		$cont_exp->buildExportFile();
		$this->exportList();
	}

	/*
	* list all export files
	*/
	function exportList()
	{
		global $tree;

		$this->setTabs();

		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// create export file button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "export"));
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_create_export_file"));
		$this->tpl->parseCurrentBlock();

		// view last export log button
		if (is_file($this->object->getExportDirectory()."/export.log"))
		{
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "viewExportLog"));
			$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_view_last_export_log"));
			$this->tpl->parseCurrentBlock();
		}


		$export_dir = $this->object->getExportDirectory();

		$export_files = $this->object->getExportFiles($export_dir);

		// create table
		require_once("classes/class.ilTableGUI.php");
		$tbl = new ilTableGUI();

		// load files templates
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");

		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.export_file_row.html", true);

		$num = 0;

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$tbl->setTitle($this->lng->txt("cont_export_files"));

		$tbl->setHeaderNames(array("", $this->lng->txt("cont_file"),
			$this->lng->txt("cont_size"), $this->lng->txt("date") ));

		$cols = array("", "file", "size", "date");
		$header_params = array("ref_id" => $_GET["ref_id"],
			"cmd" => "exportList", "cmdClass" => get_class($this));
		$tbl->setHeaderVars($cols, $header_params);
		$tbl->setColumnWidth(array("1%", "49%", "25%", "25%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);		// ???


		$this->tpl->setVariable("COLUMN_COUNTS", 4);

		// delete button
		$this->tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "confirmDeleteExportFile");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("delete"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "downloadExportFile");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("download"));
		$this->tpl->parseCurrentBlock();

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		//$tbl->disable("footer");

		$tbl->setMaxCount(count($export_files));
		$export_files = array_slice($export_files, $_GET["offset"], $_GET["limit"]);

		$tbl->render();
		if(count($export_files) > 0)
		{
			$i=0;
			foreach($export_files as $exp_file)
			{
				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("TXT_FILENAME", $exp_file);

				$css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
				$this->tpl->setVariable("CSS_ROW", $css_row);

				$this->tpl->setVariable("TXT_SIZE", filesize($export_dir."/".$exp_file));
				$this->tpl->setVariable("CHECKBOX_ID", $exp_file);

				$file_arr = explode("__", $exp_file);
				$this->tpl->setVariable("TXT_DATE", date("Y-m-d H:i:s",$file_arr[0]));

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
	}

	/*
	* view last export log
	*/
	function viewExportLog()
	{
		global $tree;

		$this->setTabs();

		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// create export file button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "exportList"));
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_export_files"));
		$this->tpl->parseCurrentBlock();

		// load files templates
		$this->tpl->setVariable("ADM_CONTENT",
			nl2br(file_get_contents($this->object->getExportDirectory()."/export.log")));

		$this->tpl->parseCurrentBlock();
	}

	/**
	* download export file
	*/
	function downloadExportFile()
	{
		if(!isset($_POST["file"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		if (count($_POST["file"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}


		$export_dir = $this->object->getExportDirectory();
		ilUtil::deliverFile($export_dir."/".$_POST["file"][0],
			$_POST["file"][0]);
	}

	/**
	* download export file
	*/
	function downloadPDFFile()
	{
		if(!isset($_POST["file"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		if (count($_POST["file"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}


		$export_dir = $this->object->getOfflineDirectory();
		ilUtil::deliverFile($export_dir."/".$_POST["file"][0],
			$_POST["file"][0]);
	}

	/**
	* confirmation screen for export file deletion
	*/
	function confirmDeleteExportFile()
	{
		if(!isset($_POST["file"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		$this->setTabs();

		// SAVE POST VALUES
		$_SESSION["ilExportFiles"] = $_POST["file"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm_deletion.html", true);

		sendInfo($this->lng->txt("info_delete_sure"));

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		// BEGIN TABLE HEADER
		$this->tpl->setCurrentBlock("table_header");
		$this->tpl->setVariable("TEXT",$this->lng->txt("objects"));
		$this->tpl->parseCurrentBlock();

		// BEGIN TABLE DATA
		$counter = 0;
		foreach($_POST["file"] as $file)
		{
				$this->tpl->setCurrentBlock("table_row");
				$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
				$this->tpl->setVariable("TEXT_CONTENT", $file);
				$this->tpl->parseCurrentBlock();
		}

		// cancel/confirm button
		$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$buttons = array( "cancelDeleteExportFile"  => $this->lng->txt("cancel"),
			"deleteExportFile"  => $this->lng->txt("confirm"));
		foreach ($buttons as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
	}


	/**
	* cancel deletion of export files
	*/
	function cancelDeleteExportFile()
	{
		session_unregister("ilExportFiles");
		$this->ctrl->redirect($this, "exportList");
	}


	/**
	* delete export files
	*/
	function deleteExportFile()
	{
		$export_dir = $this->object->getExportDirectory();
		foreach($_SESSION["ilExportFiles"] as $file)
		{
			$exp_file = $export_dir."/".$file;
			$exp_dir = $export_dir."/".substr($file, 0, strlen($file) - 4);
			if (@is_file($exp_file))
			{
				unlink($exp_file);
			}
			if (@is_dir($exp_dir))
			{
				ilUtil::delDir($exp_dir);
			}
		}
		$this->ctrl->redirect($this, "exportList");
	}

	/*
	* list all offline files
	*/
	function offlineList()
	{
		global $tree;

		$this->setTabs();

		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// create pdf file button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "createPDF"));
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_create_pdf_file"));
		$this->tpl->parseCurrentBlock();

		// view last export log button
		/*
		if (is_file($this->object->getExportDirectory()."/export.log"))
		{
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "viewExportLog"));
			$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_view_last_export_log"));
			$this->tpl->parseCurrentBlock();
		}*/

		$offline_dir = $this->object->getOfflineDirectory();

		$offline_files = $this->object->getOfflineFiles($offline_dir);

		// create table
		require_once("classes/class.ilTableGUI.php");
		$tbl = new ilTableGUI();

		// load files templates
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");

		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.offline_file_row.html", true);

		$num = 0;

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$tbl->setTitle($this->lng->txt("cont_offline_files"));

		$tbl->setHeaderNames(array("", $this->lng->txt("cont_file"),
			$this->lng->txt("cont_size"), $this->lng->txt("date") ));

		$cols = array("", "file", "size", "date");
		$header_params = array("ref_id" => $_GET["ref_id"],
			"cmd" => "offlineList", "cmdClass" => get_class($this));
		$tbl->setHeaderVars($cols, $header_params);
		$tbl->setColumnWidth(array("1%", "49%", "25%", "25%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);		// ???


		$this->tpl->setVariable("COLUMN_COUNTS", 4);

		// delete button
		$this->tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "confirmDeleteOfflineFile");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("delete"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "downloadPDFFile");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("download"));
		$this->tpl->parseCurrentBlock();

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		//$tbl->disable("footer");

		$tbl->setMaxCount(count($offline_files));
		$offline_files = array_slice($offline_files, $_GET["offset"], $_GET["limit"]);

		$tbl->render();
		if(count($offline_files) > 0)
		{
			$i=0;
			foreach($offline_files as $off_file)
			{
				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("TXT_FILENAME", $off_file);

				$css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
				$this->tpl->setVariable("CSS_ROW", $css_row);

				$this->tpl->setVariable("TXT_SIZE", filesize($offline_dir."/".$off_file));
				$this->tpl->setVariable("CHECKBOX_ID", $off_file);

				$file_arr = explode("__", $off_file);
				$this->tpl->setVariable("TXT_DATE", date("Y-m-d H:i:s",$file_arr[0]));

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
	}

	/**
	* export content object
	*/
	function createPDF()
	{
		require_once("content/classes/class.ilContObjectExport.php");
		$cont_exp = new ilContObjectExport($this->object, "pdf");
		$cont_exp->buildExportFile();
		$this->offlineList();
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
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
	}

	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		// back to upper context
		$tabs_gui->getTargetsByObjectType($this, $this->object->getType());
	}

} // END class.ilObjContentObjectGUI
?>
