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

require_once("classes/class.ilObjectGUI.php");
require_once("classes/class.ilMetaDataGUI.php");
require_once("content/classes/class.ilObjGlossary.php");
require_once("content/classes/class.ilGlossaryTermGUI.php");
require_once("content/classes/class.ilGlossaryDefinition.php");
require_once("content/classes/class.ilTermDefinitionEditorGUI.php");
require_once("content/classes/Pages/class.ilPCParagraph.php");

/**
* Class ilGlossaryGUI
*
* GUI class for ilGlossary
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilObjGlossaryGUI: ilGlossaryTermGUI
*
* @package content
*/
class ilObjGlossaryGUI extends ilObjectGUI
{
	var $admin_tabs;
	var $mode;
	var $term;

	/**
	* Constructor
	* @access	public
	*/
	function ilObjGlossaryGUI($a_data,$a_id = 0,$a_call_by_reference = true, $a_prepare_output = true)
	{
		global $ilCtrl;

		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this, array("ref_id", "offset"));

		$this->type = "glo";
		parent::ilObjectGUI($a_data, $a_id, $a_call_by_reference, false);
		if (defined("ILIAS_MODULE"))
		{
			$this->setTabTargetScript("glossary_edit.php");
		}
		if ($a_prepare_output)
		{
			$this->prepareOutput();
		}
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass($this);

		switch ($next_class)
		{
			case "ilglossarytermgui":
				$this->ctrl->setReturn($this, "listTerms");
				$term_gui =& new ilGlossaryTermGUI($_GET["term_id"]);
				$term_gui->setGlossary($this->object);
				//$ret =& $term_gui->executeCommand();
				$ret =& $this->ctrl->forwardCommand($term_gui);
				break;

			default:
				$cmd = $this->ctrl->getCmd("ListTerms");

				if (($cmd == "create") && ($_POST["new_type"] == "term"))
				{
					$this->ctrl->setCmd("create");
					$this->ctrl->setCmdClass("ilGlossaryTermGUI");
					$ret =& $this->executeCommand();
					return;
				}
				else
				{
					$this->getTemplate();
					$this->setTabs();
					$this->setLocator();
					$ret =& $this->$cmd();
				}
				break;
		}

		$this->tpl->show();
	}

	function assignObject()
	{
		include_once("content/classes/class.ilObjGlossary.php");

		$this->object =& new ilObjGlossary($this->id, true);
	}


	/**
	* form for new content object creation
	*/
	function createObject()
	{
		global $rbacsystem;

		$new_type = $_POST["new_type"] ? $_POST["new_type"] : $_GET["new_type"];

		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $new_type))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			// fill in saved values in case of error
			$data = array();
			$data["fields"] = array();
			$data["fields"]["title"] = ilUtil::prepareFormOutput($_SESSION["error_post_vars"]["Fobject"]["title"],true);
			$data["fields"]["desc"] = ilUtil::stripSlashes($_SESSION["error_post_vars"]["Fobject"]["desc"]);

			$this->getTemplateFile("create", $new_type);

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);

				if ($this->prepare_output)
				{
					$this->tpl->parseCurrentBlock();
				}
			}

			$this->tpl->setVariable("FORMACTION", $this->getFormAction("save","adm_object.php?cmd=gateway&ref_id=".
																	   $_GET["ref_id"]."&new_type=".$new_type));
			$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($new_type."_new"));
			$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt($new_type."_add"));
			$this->tpl->setVariable("CMD_SUBMIT", "save");
			$this->tpl->setVariable("TARGET", $this->getTargetFrame("save"));
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));

			$this->tpl->setVariable("TXT_IMPORT_GLO", $this->lng->txt("import_glossary"));
			$this->tpl->setVariable("TXT_GLO_FILE", $this->lng->txt("glo_upload_file"));
			$this->tpl->setVariable("TXT_IMPORT", $this->lng->txt("import"));
		}
	}

	function importObject()
	{
		$this->createObject();
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
			include_once("content/classes/class.ilObjGlossary.php");
			$newObj = new ilObjGlossary();
			$newObj->setType($this->type);
			$newObj->setTitle($_POST["Fobject"]["title"]);
			$newObj->setDescription($_POST["Fobject"]["desc"]);
			$newObj->create();
			$newObj->createReference();
			$newObj->putInTree($_GET["ref_id"]);
			$newObj->setPermissions($_GET["ref_id"]);
			$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());

			//$roles = $newObj->initDefaultRoles();

			// assign author role to creator of forum object
			//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "n");
			//ilObjUser::updateActiveRoles($newObj->getOwner());

			unset($newObj);

			// always send a message
			sendInfo($this->lng->txt("glo_added"),true);
			ilUtil::redirect($this->getReturnLocation("save","adm_object.php?".$this->link_params));
		}
	}

	/**
	* display status information or report errors messages
	* in case of error
	*
	* @access	public
	*/
	function importFileObject()
	{
		global $HTTP_POST_FILES, $rbacsystem;

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
		include_once("content/classes/class.ilObjGlossary.php");
		$newObj = new ilObjGlossary();
		$newObj->setType($_GET["new_type"]);
		$newObj->setTitle("dummy");
		//$newObj->setDescription("dummy");
		$newObj->create(true);
		$newObj->createReference();
		$newObj->putInTree($_GET["ref_id"]);
		$newObj->setPermissions($_GET["ref_id"]);
		$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());

		// create import directory
		$newObj->createImportDirectory();

		// copy uploaded file to import directory
		$file = pathinfo($_FILES["xmldoc"]["name"]);
		$full_path = $newObj->getImportDirectory()."/".$_FILES["xmldoc"]["name"];
		
		ilUtil::moveUploadedFile($_FILES["xmldoc"]["tmp_name"],
			$_FILES["xmldoc"]["name"], $full_path);
		
		//move_uploaded_file($_FILES["xmldoc"]["tmp_name"], $full_path);

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
		// not allowed here: move to glossary class!
		if (is_object($newObj->meta_data))
		{
			//$newObj->meta_data->read();
			//$newObj->setTitle($newObj->meta_data->getTitle());
			//$newObj->setDescription($newObj->meta_data->getDescription());
			ilObject::_writeTitle($newObj->getID(), $newObj->getTitle());
			ilObject::_writeDescription($newObj->getID(), $newObj->getDescription());
			//$q = "UPDATE object_data SET title = '" . $newObj->getTitle() . "', description = '" . $newObj->getDescription() . "' WHERE obj_id = '" . $newObj->getID() . "'";
			//$this->ilias->db->query($q);
		}

		sendInfo($this->lng->txt("glo_added"),true);
		ilUtil::redirect($this->getReturnLocation("save","adm_object.php?".$this->link_params));
	}


	/**
	* choose meta data section
	* (called by administration)
	*/
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

	/**
	* choose meta data section
	* (called by module)
	*/
	function chooseMetaSection()
	{
		//$this->prepareOutput();
		$this->chooseMetaSectionObject($this->ctrl->getLinkTarget($this));
	}

	/**
	* add meta data object
	* (called by administration)
	*/
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

	/**
	* add meta data object
	* (called by module)
	*/
	function addMeta()
	{
		//$this->prepareOutput();
		$this->addMetaObject($this->ctrl->getLinkTarget($this));
	}


	/**
	* delete meta data object
	* (called by administration)
	*/
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

	/**
	* delete meta data object
	* (called by module)
	*/
	function deleteMeta()
	{
		//$this->prepareOutput();
		$this->deleteMetaObject($this->ctrl->getLinkTarget($this));
	}

	/**
	* edit meta data
	* (called by administration)
	*/
	function editMetaObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->edit("ADM_CONTENT", "adm_content", $a_target, $_GET["meta_section"]);
	}

	/**
	* edit meta data
	* (called by module)
	*/
	function editMeta()
	{
		//$this->prepareOutput();
		$this->editMetaObject($this->ctrl->getLinkTarget($this));
	}

	/**
	* save meta data
	* (called by administration)
	*/
	function saveMetaObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?cmd=editMeta&ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->save($_POST["meta_section"]);
		ilUtil::redirect(ilUtil::appendUrlParameterString($a_target,
			"meta_section=" . $_POST["meta_section"]));
	}

	/**
	* save meta data
	* (called by module)
	*/
	function saveMeta()
	{
		$this->saveMetaObject($this->ctrl->getLinkTarget($this, "editMeta"));
	}


	function viewObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		// edit button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		if (!defined("ILIAS_MODULE"))
		{
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK","content/glossary_edit.php?cmd=listTerms&ref_id=".$this->object->getRefID());
			$this->tpl->setVariable("BTN_TARGET"," target=\"bottom\" ");
			$this->tpl->setVariable("BTN_TXT",$this->lng->txt("edit"));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK","content/glossary_presentation.php?cmd=listTerms&ref_id=".$this->object->getRefID());
		$this->tpl->setVariable("BTN_TARGET"," target=\"bottom\" ");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("view"));
		$this->tpl->parseCurrentBlock();

		//parent::viewObject();
	}

	/**
	* edit properties of object (admin form)
	*
	* @access	public
	*/
	function properties()
	{
		global $rbacsystem, $tree, $tpl;

		// glossary properties
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.glossary_properties.html", true);
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_PROPERTIES", $this->lng->txt("cont_glo_properties"));

		// online
		$this->tpl->setVariable("TXT_ONLINE", $this->lng->txt("cont_online"));
		$this->tpl->setVariable("CBOX_ONLINE", "cobj_online");
		$this->tpl->setVariable("VAL_ONLINE", "y");
		if ($this->object->getOnline())
		{
			$this->tpl->setVariable("CHK_ONLINE", "checked");
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
		$this->object->setOnline(ilUtil::yn2tf($_POST["cobj_online"]));
		$this->object->update();
		sendInfo($this->lng->txt("msg_obj_modified"), true);
		$this->ctrl->redirect($this, "properties");
	}



	/**
	* list terms
	*/
	function listTerms()
	{
		global $ilUser;

		//$this->getTemplate();
		//$this->setTabs();
		//$this->setLocator();
		$this->lng->loadLanguageModule("meta");
		include_once "./classes/class.ilTableGUI.php";


		// view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", "glossary_presentation.php?cmd=listTerms&ref_id=".$this->object->getRefID());
		$this->tpl->setVariable("BTN_TARGET"," target=\"bottom\" ");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("view"));
		$this->tpl->parseCurrentBlock();

		// export button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "exportList"));
		//$this->tpl->setVariable("BTN_TARGET"," target=\"_top\" ");
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("export"));
		$this->tpl->parseCurrentBlock();

		// glossary term list template
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.glossary_term_list.html", true);
		$this->tpl->setVariable("FORMACTION1", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_TERM", $this->lng->txt("cont_term"));
		$this->tpl->setVariable("TXT_ADD2", $this->lng->txt("add"));
		$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		$lang = ilMetaData::getLanguages();

		if ($_SESSION["il_text_lang_".$_GET["ref_id"]] != "")
		{
			$s_lang = $_SESSION["il_text_lang_".$_GET["ref_id"]];
		}
		else
		{
			$s_lang = $ilUser->getLanguage();
		}

		$select_language = ilUtil::formSelect ($s_lang, "term_language",$lang,false,true);
		$this->tpl->setVariable("SELECT_LANGUAGE", $select_language);


		// load template for table
		$this->tpl->addBlockfile("TERM_TABLE", "term_table", "tpl.table.html");

		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.term_tbl_row.html", true);

		$num = 0;

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		// create table
		$tbl = new ilTableGUI();

		// title & header columns
		$tbl->setTitle($this->lng->txt("cont_terms"));
		//$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));

		$tbl->setHeaderNames(array("", $this->lng->txt("cont_term"),
			 $this->lng->txt("language"), $this->lng->txt("cont_definitions")));

		$cols = array("", "term", "language", "definitions", "id");
		$header_params = array("ref_id" => $this->ref_id, "cmd" => "listTerms");
		$tbl->setHeaderVars($cols, $header_params);
		$tbl->setColumnWidth(array("1%","24%","15%","60%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);

		$this->tpl->setVariable("COLUMN_COUNTS", 4);
		$this->setActions(array("confirmTermDeletion" => "delete", "addDefinition" => "cont_add_definition"));
		//$this->setSubObjects(array("term" => array()));
		$this->showActions(true);

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		//$tbl->disable("footer");

		$term_list = $this->object->getTermList();
		$tbl->setMaxCount(count($term_list));

		// sorting array
		//$term_list = ilUtil::sortArray($term_list, $_GET["sort_by"], $_GET["sort_order"]);
		$term_list = array_slice($term_list, $_GET["offset"], $_GET["limit"]);

		// render table
		$tbl->render();

		if (count($term_list) > 0)
		{
			$i=1;
			foreach($term_list as $key => $term)
			{
				$css_row = ilUtil::switchColor($i++,"tblrow1","tblrow2");
				$defs = ilGlossaryDefinition::getDefinitionList($term["id"]);
				for($j=0; $j<count($defs); $j++)
				{
					$def = $defs[$j];

					// up
					if ($j > 0)
					{
						$this->tpl->setCurrentBlock("move_up");
						$this->tpl->setVariable("TXT_UP", $this->lng->txt("up"));
						$this->ctrl->setParameter($this, "term_id", $term["id"]);
						$this->ctrl->setParameter($this, "def", $def["id"]);
						$this->tpl->setVariable("LINK_UP",
							$this->ctrl->getLinkTarget($this, "moveDefinitionUp"));
						$this->tpl->parseCurrentBlock();
					}

					// down
					if ($j+1 < count($defs))
					{
						$this->tpl->setCurrentBlock("move_down");
						$this->tpl->setVariable("TXT_DOWN", $this->lng->txt("down"));
						$this->ctrl->setParameter($this, "term_id", $term["id"]);
						$this->ctrl->setParameter($this, "def", $def["id"]);
						$this->tpl->setVariable("LINK_DOWN",
							$this->ctrl->getLinkTarget($this, "moveDefinitionDown"));
						$this->tpl->parseCurrentBlock();
					}

					// delete
					$this->tpl->setCurrentBlock("delete");
					$this->ctrl->setParameter($this, "term_id", $term["id"]);
					$this->ctrl->setParameter($this, "def", $def["id"]);
					$this->tpl->setVariable("LINK_DELETE",
						$this->ctrl->getLinkTarget($this, "confirmDefinitionDeletion"));
					$this->tpl->setVariable("TXT_DELETE", $this->lng->txt("delete"));
					$this->tpl->parseCurrentBlock();

					// edit
					$this->tpl->setCurrentBlock("edit");
					$this->ctrl->setParameterByClass("ilpageobjectgui", "term_id", $term["id"]);
					$this->ctrl->setParameterByClass("ilpageobjectgui", "def", $def["id"]);
					$this->tpl->setVariable("LINK_EDIT",
						$this->ctrl->getLinkTargetByClass(array("ilglossarytermgui",
						"iltermdefinitioneditorgui",
						"ilpageobjectgui"), "view"));
					$this->tpl->setVariable("TXT_EDIT", $this->lng->txt("edit"));
					$this->tpl->parseCurrentBlock();

					// text
					$this->tpl->setCurrentBlock("definition");
					$short_str = strip_tags(ilPCParagraph::xml2output($def["short_text"]));
					$short_str = str_replace("<", "&lt;", $short_str);
					$short_str = str_replace(">", "&gt;", $short_str);
					$this->tpl->setVariable("DEF_SHORT", $short_str);
					$this->tpl->parseCurrentBlock();

					$this->tpl->setCurrentBlock("definition_row");
					$this->tpl->parseCurrentBlock();
				}

				$this->tpl->setCurrentBlock("check_col");
				$this->tpl->setVariable("CHECKBOX_ID", $term["id"]);
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->parseCurrentBlock();

				// edit term link
				$this->tpl->setCurrentBlock("edit_term");
				$this->tpl->setVariable("TEXT_TERM", $term["term"]);
				$this->ctrl->setParameter($this, "term_id", $term["id"]);
				$this->tpl->setVariable("LINK_EDIT_TERM",
					$this->ctrl->getLinkTarget($this, "editTerm"));
				$this->tpl->setVariable("TXT_EDIT_TERM", $this->lng->txt("edit"));
				$this->tpl->parseCurrentBlock();

				$this->tpl->setCurrentBlock("tbl_content");

				// output term and language
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->setVariable("TEXT_LANGUAGE", $this->lng->txt("meta_l_".$term["language"]));
				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->parseCurrentBlock();
			}
		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", $num);
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* add term
	*/
	function addTerm()
	{
		// add term
		include_once ("content/classes/class.ilGlossaryTerm.php");
		$term =& new ilGlossaryTerm();
		$term->setGlossary($this->object);
		$term->setTerm(ilUtil::stripSlashes($_POST["new_term"]));
		$term->setLanguage($_POST["term_language"]);
		$_SESSION["il_text_lang_".$_GET["ref_id"]] = $_POST["term_language"];
		$term->create();

		// add first definition
		$def =& new ilGlossaryDefinition();
		$def->setTermId($term->getId());
		$def->setTitle(ilUtil::stripSlashes($_POST["new_term"]));
		$def->create();

		$this->ctrl->setParameterByClass("ilpageobjectgui", "term_id", $term->getId());
		$this->ctrl->setParameterByClass("ilpageobjectgui", "def", $def->getId());
		$this->ctrl->redirectByClass(array("ilglossarytermgui",
			"iltermdefinitioneditorgui", "ilpageobjectgui"), "view");
	}

	/**
	* move a definiton up
	*/
	function moveDefinitionUp()
	{
		include_once("content/classes/class.ilGlossaryDefinition.php");

		$definition =& new ilGlossaryDefinition($_GET["def"]);
		$definition->moveUp();

		$this->ctrl->redirect($this, "listTerms");
	}

	/**
	* move a definiton down
	*/
	function moveDefinitionDown()
	{
		include_once("content/classes/class.ilGlossaryDefinition.php");

		$definition =& new ilGlossaryDefinition($_GET["def"]);
		$definition->moveDown();

		$this->ctrl->redirect($this, "listTerms");
	}

	/**
	* deletion confirmation screen
	*/
	function confirmDefinitionDeletion()
	{
		//$this->getTemplate();
		//$this->displayLocator();
		//$this->setTabs();

		$term = new ilGlossaryTerm($_GET["term_id"]);

		// content style
		$this->tpl->setCurrentBlock("ContentStyle");
		$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
			ilObjStyleSheet::getContentStylePath(0));
		$this->tpl->parseCurrentBlock();

		// syntax style
		$this->tpl->setCurrentBlock("SyntaxStyle");
		$this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
			ilObjStyleSheet::getSyntaxStylePath());
		$this->tpl->parseCurrentBlock();


		//$this->tpl->setVariable("HEADER",
		//	$this->lng->txt("cont_term").": ".$term->getTerm());

		$this->tpl->addBlockfile("ADM_CONTENT", "def_list", "tpl.glossary_definition_delete.html", true);
		sendInfo($this->lng->txt("info_delete_sure"));

		$this->tpl->setVariable("TXT_TERM", $term->getTerm());

		$definition =& new ilGlossaryDefinition($_GET["def"]);
		$page =& new ilPageObject("gdf", $definition->getId());
		$page_gui =& new ilPageObjectGUI($page);
		$page_gui->setTemplateOutput(false);
		$page_gui->setSourcecodeDownloadScript("glossary_presentation.php?ref_id=".$_GET["ref_id"]);
		$output = $page_gui->preview();

		$this->tpl->setCurrentBlock("definition");
		$this->tpl->setVariable("PAGE_CONTENT", $output);
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("LINK_CANCEL",
			$this->ctrl->getLinkTarget($this, "cancelDefinitionDeletion"));
		$this->tpl->setVariable("TXT_CONFIRM", $this->lng->txt("confirm"));
		$this->ctrl->setParameter($this, "def", $definition->getId());
		$this->tpl->setVariable("LINK_CONFIRM",
			$this->ctrl->getLinkTarget($this, "deleteDefinition"));
		$this->tpl->parseCurrentBlock();
	}

	function cancelDefinitionDeletion()
	{
		$this->ctrl->redirect($this, "listTerms");
	}


	function deleteDefinition()
	{
		$definition =& new ilGlossaryDefinition($_GET["def"]);
		$definition->delete();
		$this->ctrl->redirect($this, "listTerms");
	}

	/**
	* edit term
	*/
	function editTerm()
	{
		$term = new ilGlossaryTerm($_GET["term_id"]);
		//$this->tpl->setVariable("HEADER", $this->lng->txt("cont_term").": ".$term->getTerm());

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.glossary_term_edit.html", true);
		$this->ctrl->setParameter($this, "term_id", $_GET["term_id"]);
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_edit_term"));
		$this->tpl->setVariable("TXT_TERM", $this->lng->txt("cont_term"));
		$this->tpl->setVariable("INPUT_TERM", "term");
		$this->tpl->setVariable("VALUE_TERM", htmlspecialchars($term->getTerm()));
		$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		$lang = ilMetaData::getLanguages();
		$select_language = ilUtil::formSelect ($term->getLanguage(),"term_language",$lang,false,true);
		$this->tpl->setVariable("SELECT_LANGUAGE", $select_language);
		$this->tpl->setVariable("BTN_NAME", "updateTerm");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
	}


	/**
	* update term
	*/
	function updateTerm()
	{
		$term = new ilGlossaryTerm($_GET["term_id"]);

		$term->setTerm(ilUtil::stripSlashes($_POST["term"]));
		$term->setLanguage($_POST["term_language"]);
		$term->update();
		sendinfo($this->lng->txt("msg_obj_modified"),true);
		$this->ctrl->redirect($this, "listTerms");
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
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.glo_export_file_row.html", true);

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


	/**
	* export content object
	*/
	function export()
	{
		require_once("content/classes/class.ilGlossaryExport.php");
		$glo_exp = new ilGlossaryExport($this->object);
		$glo_exp->buildExportFile();
		$this->exportList();
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

	/*
	* list all export files
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

	/**
	* confirm term deletion
	*/
	function confirmTermDeletion()
	{
		//$this->prepareOutput();
		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// save values to
		$_SESSION["term_delete"] = $_POST["id"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.obj_confirm.html");

		sendInfo($this->lng->txt("info_delete_sure"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		// output table header
		$cols = array("cont_term");
		foreach ($cols as $key)
		{
			$this->tpl->setCurrentBlock("table_header");
			$this->tpl->setVariable("TEXT",$this->lng->txt($key));
			$this->tpl->parseCurrentBlock();
		}

		foreach($_POST["id"] as $id)
		{
			$term = new ilGlossaryTerm($id);

			// output title
			$this->tpl->setCurrentBlock("table_cell");
			$this->tpl->setVariable("TEXT_CONTENT", $term->getTerm());
			$this->tpl->parseCurrentBlock();

			// output table row
			$this->tpl->setCurrentBlock("table_row");
			$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
			$this->tpl->parseCurrentBlock();
		}

		// cancel and confirm button
		$buttons = array( "cancelTermDeletion"  => $this->lng->txt("cancel"),
			"deleteTerms"  => $this->lng->txt("confirm"));
		$this->tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		foreach($buttons as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}

	}

	/**
	* cancel deletion of object
	*
	* @access	public
	*/
	function cancelTermDeletion()
	{
		session_unregister("term_delete");
		sendInfo($this->lng->txt("msg_cancel"),true);
		$this->ctrl->redirect($this, "listTerms");
	}

	/**
	* delete selected terms
	*/
	function deleteTerms()
	{
		foreach($_SESSION["term_delete"] as $id)
		{
			$term = new ilGlossaryTerm($id);
			$term->delete();
		}
		session_unregister("term_delete");
		$this->ctrl->redirect($this, "listTerms");
	}

	/**
	* set Locator
	*
	* @param	object	tree object
	* @param	integer	reference id
	* @access	public
	*/
	function setLocator($a_tree = "", $a_id = "")
	{
		global $ilias_locator;

		if(!defined("ILIAS_MODULE"))
		{
			parent::setLocator($a_tree, $a_id);
		}
		else
		{
			if(is_object($this->object))
			{
				require_once("content/classes/class.ilGlossaryLocatorGUI.php");
				$gloss_loc =& new ilGlossaryLocatorGUI();
				if (is_object($this->term))
				{
					$gloss_loc->setTerm($this->term);
				}
				$gloss_loc->setGlossary($this->object);
				//$gloss_loc->setDefinition($this->definition);
				$gloss_loc->display();
				return;



				// ### AA 03.11.10 added new locator GUI class ###
				$i = 1;

				$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");

				if (!empty($_GET["term_id"]))
				{
					$this->tpl->touchBlock("locator_separator");
				}

				$this->tpl->setCurrentBlock("locator_item");
				$this->tpl->setVariable("ITEM", $this->object->getTitle());
				// TODO: SCRIPT NAME HAS TO BE VARIABLE!!!
				$this->tpl->setVariable("LINK_ITEM", "glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=listTerms");
				$this->tpl->parseCurrentBlock();

				// ### AA 03.11.10 added new locator GUI class ###
				// navigate locator
				$ilias_locator->navigate($i++,$this->object->getTitle(),"glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=listTerms","bottom");

				if (!empty($_GET["term_id"]))
				{
					$term =& new ilGlossaryTerm($_GET["term_id"]);
					$this->tpl->setCurrentBlock("locator_item");
					$this->tpl->setVariable("ITEM", $term->getTerm());
					$this->tpl->setVariable("LINK_ITEM", "glossary_edit.php?ref_id=".$_GET["ref_id"].
						"&cmd=listDefinitions&term_id=".$term->getId());
					$this->tpl->parseCurrentBlock();

					// ### AA 03.11.10 added new locator GUI class ###
					// navigate locator
					$ilias_locator->navigate($i++,$term->getTerm(),"glossary_edit.php?ref_id=".$_GET["ref_id"].
						"&cmd=listDefinitions&term_id=".$term->getId(),"bottom");
				}

				//$this->tpl->touchBlock("locator_separator");

				$this->tpl->setCurrentBlock("locator");
				$this->tpl->setVariable("TXT_LOCATOR",$debug.$this->lng->txt("locator"));
				$this->tpl->parseCurrentBlock();
			}
		}

	}

	/**
	* edit permissions
	*/
	function perm()
	{
		//$this->prepareOutput();
		$this->setFormAction("addRole", "glossary_edit.php?ref_id=".$this->object->getRefId()."&cmd=addRole");
		$this->setFormAction("permSave", "glossary_edit.php?ref_id=".$this->object->getRefId()."&cmd=permSave");
		$this->permObject();
	}

	/**
	* save permissions
	*/
	function permSave()
	{
		$this->setReturnLocation("permSave", "glossary_edit.php?ref_id=".$this->object->getRefId()."&cmd=perm");
		$this->permSaveObject();
	}

	/**
	* add a local role
	*/
	function addRole()
	{
		$this->setReturnLocation("addRole", "glossary_edit.php?ref_id=".$this->object->getRefId()."&cmd=perm");
		$this->addRoleObject();
	}

	/**
	* show owner
	*/
	function owner()
	{
		//$this->prepareOutput();
		$this->ownerObject();
	}

	/**
	* view content
	*/
	function view()
	{
		//$this->prepareOutput();
		$this->viewObject();
	}

	/**
	* create new (subobject) in glossary
	*/
	function create()
	{
		switch($_POST["new_type"])
		{
			case "term":
				$term_gui =& new ilGlossaryTermGUI();
				$term_gui->create();
				break;
		}
	}

	function saveTerm()
	{
		$term_gui =& new ilGlossaryTermGUI();
		$term_gui->setGlossary($this->object);
		$term_gui->save();

		sendinfo($this->lng->txt("cont_added_term"),true);

		ilUtil::redirect("glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=listTerms");
	}


	/**
	* add definition
	*/
	function addDefinition()
	{
		if (count($_POST["id"]) < 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_term"),$this->ilias->error_obj->MESSAGE);
		}

		if (count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_term"),$this->ilias->error_obj->MESSAGE);
		}

		// add term
		include_once ("content/classes/class.ilGlossaryTerm.php");
		$term =& new ilGlossaryTerm($_POST["id"][0]);

		// add first definition
		$def =& new ilGlossaryDefinition();
		$def->setTermId($term->getId());
		$def->setTitle(ilUtil::stripSlashes($term->getTerm()));
		$def->create();

		$this->ctrl->setParameterByClass("ilpageobjectgui", "term_id", $term->getId());
		$this->ctrl->setParameterByClass("ilpageobjectgui", "def", $def->getId());
		$this->ctrl->redirectByClass(array("ilglossarytermgui",
			"iltermdefinitioneditorgui", "ilpageobjectgui"), "view");
		
	}

	function getTemplate()
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");

		$title = $this->object->getTitle();

		// catch feedback message
		sendInfo();

		$this->tpl->setVariable("HEADER", $this->lng->txt("glo").": ".$title);

		//$this->setAdminTabs($_POST["new_type"]);
		//$this->setLocator();

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

	}

	/**
	* get tabs
	*/
	function getTabs(&$tabs_gui)
	{

		// list definitions
		$tabs_gui->addTarget("cont_terms",
			$this->ctrl->getLinkTarget($this, "listTerms"), "listTerms",
			get_class($this));

		// properties
		$tabs_gui->addTarget("properties",
			$this->ctrl->getLinkTarget($this, "properties"), "properties",
			get_class($this));

		// meta data
		$tabs_gui->addTarget("meta_data",
			$this->ctrl->getLinkTarget($this, "editMeta"), "editMeta",
			get_class($this));

		// permissions
		$tabs_gui->addTarget("permission_settings",
			$this->ctrl->getLinkTarget($this, "perm"), "perm",
			get_class($this));

		// owner
		$tabs_gui->addTarget("owner",
			$this->ctrl->getLinkTarget($this, "owner"), "owner",
			get_class($this));

	}

}

?>
