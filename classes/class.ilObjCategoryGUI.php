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
* Class ilObjCategoryGUI
*
* @author Stefan Meyer <smeyer@databay.de>
* @author Sascha Hofmann <saschahofmann@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilObjCategoryGUI:
* 
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "class.ilContainerGUI.php";

class ilObjCategoryGUI extends ilContainerGUI
{
	var $ctrl;

	/**
	* Constructor
	* @access public
	*/
	function ilObjCategoryGUI($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
	{
		global $ilCtrl;

		// CONTROL OPTIONS
		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this,array("ref_id","cmdClass"));

		$this->type = "cat";
		$this->ilContainerGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output);
	}

	function &executeCommand()
	{
		global $rbacsystem;

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				if(!$cmd)
				{
					$cmd = "render";
				}
				$cmd .= "Object";
				$this->$cmd();

				break;
		}
		return true;
	}

	function getTabs(&$tabs_gui)
	{
		global $rbacsystem;


		$this->ctrl->setParameter($this,"ref_id",$this->ref_id);

		if ($rbacsystem->checkAccess('read',$this->ref_id))
		{
			$force_active = ($_GET["cmd"] == "" || $_GET["cmd"] == "render")
				? true
				: false;
			$tabs_gui->addTarget("view_content",
				$this->ctrl->getLinkTarget($this, ""),
				array("view", ""), "", "", $force_active);
		}
		
		if ($rbacsystem->checkAccess('write',$this->ref_id))
		{
			$tabs_gui->addTarget("edit_properties",
				$this->ctrl->getLinkTarget($this, "edit"), "edit", get_class($this));
		}

		if($rbacsystem->checkAccess('cat_administrate_users',$this->ref_id))
		{
			$tabs_gui->addTarget("administrate_users",
				$this->ctrl->getLinkTarget($this, "listUsers"), "listUsers", get_class($this));
		}

		// parent tabs (all container: edit_permission, clipboard, trash
		parent::getTabs($tabs_gui);

	}

	/**
	* create new category form
	*
	* @access	public
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
			// for lang selection include metadata class
			include_once "./classes/class.ilMetaData.php";

			//add template for buttons
			$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

			// only in administration
			if ($this->ctrl->getTargetScript() == "adm_object.php")
			{
				$this->tpl->setCurrentBlock("btn_cell");
				$this->tpl->setVariable("BTN_LINK",
				$this->ctrl->getLinkTarget($this, "importCategoriesForm"));
				$this->tpl->setVariable("BTN_TXT", $this->lng->txt("import_categories"));
				$this->tpl->parseCurrentBlock();
			}

			$this->getTemplateFile("edit",$new_type);

			$array_push = true;

			if ($_SESSION["error_post_vars"])
			{
				$_SESSION["translation_post"] = $_SESSION["error_post_vars"];
				$array_push = false;
			}

			// clear session data if a fresh category should be created
			if (($_GET["mode"] != "session"))
			{
				unset($_SESSION["translation_post"]);
			}	// remove a translation from session
			elseif ($_GET["entry"] != 0)
			{
				array_splice($_SESSION["translation_post"]["Fobject"],$_GET["entry"],1,array());

				if ($_GET["entry"] == $_SESSION["translation_post"]["default_language"])
				{
					$_SESSION["translation_post"]["default_language"] = "";
				}
			}

			// stripslashes in form output?
			$strip = isset($_SESSION["translation_post"]) ? true : false;

			$data = $_SESSION["translation_post"];

			if (!is_array($data["Fobject"]))
			{
				$data["Fobject"] = array();
			}

			// add additional translation form
			if (!$_GET["entry"] and $array_push)
			{
				$count = array_push($data["Fobject"],array("title" => "","desc" => ""));
			}
			else
			{
				$count = count($data["Fobject"]);
			}

			foreach ($data["Fobject"] as $key => $val)
			{
				// add translation button
				if ($key == $count -1)
				{
					$this->tpl->setCurrentBlock("addTranslation");
					$this->tpl->setVariable("TXT_ADD_TRANSLATION",$this->lng->txt("add_translation")." >>");
					$this->tpl->parseCurrentBlock();
				}

				// remove translation button
				if ($key != 0)
				{
					$this->tpl->setCurrentBlock("removeTranslation");
					$this->tpl->setVariable("TXT_REMOVE_TRANSLATION",$this->lng->txt("remove_translation"));
					$this->ctrl->setParameter($this, "entry", $key);
					$this->ctrl->setParameter($this, "new_type", $new_type);
					$this->ctrl->setParameter($this, "mode", "create");
					$this->tpl->setVariable("LINK_REMOVE_TRANSLATION", $this->ctrl->getLinkTarget($this, "removeTranslation"));

					//$this->tpl->setVariable("LINK_REMOVE_TRANSLATION", "adm_object.php?cmd=removeTranslation&entry=".$key."&mode=create&ref_id=".$_GET["ref_id"]."&new_type=".$new_type);
					$this->tpl->parseCurrentBlock();
				}

				// lang selection
				$this->tpl->addBlockFile("SEL_LANGUAGE", "sel_language", "tpl.lang_selection.html", false);
				$this->tpl->setVariable("SEL_NAME", "Fobject[".$key."][lang]");

				$languages = ilMetaData::getLanguages();

				foreach($languages as $code => $language)
				{
					$this->tpl->setCurrentBlock("lg_option");
					$this->tpl->setVariable("VAL_LG", $code);
					$this->tpl->setVariable("TXT_LG", $language);

					if ($count == 1 AND $code == $this->ilias->account->getPref("language") AND !isset($_SESSION["translation_post"]))
					{
						$this->tpl->setVariable("SELECTED", "selected=\"selected\"");
					}
					elseif ($code == $val["lang"])
					{
						$this->tpl->setVariable("SELECTED", "selected=\"selected\"");
					}

					$this->tpl->parseCurrentBlock();
				}

				// object data
				$this->tpl->setCurrentBlock("obj_form");

				if ($key == 0)
				{
					$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($new_type."_new"));
				}
				else
				{
					$this->tpl->setVariable("TXT_HEADER", $this->lng->txt("translation")." ".$key);
				}

				if ($key == $data["default_language"])
				{
					$this->tpl->setVariable("CHECKED", "checked=\"checked\"");
				}

				$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("title"));
				$this->tpl->setVariable("TXT_DESC", $this->lng->txt("desc"));
				$this->tpl->setVariable("TXT_DEFAULT", $this->lng->txt("default"));
				$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
				$this->tpl->setVariable("TITLE", ilUtil::prepareFormOutput($val["title"],$strip));
				$this->tpl->setVariable("DESC", ilUtil::stripSlashes($val["desc"]));
				$this->tpl->setVariable("NUM", $key);
				$this->tpl->parseCurrentBlock();
			}

			// global
			$this->ctrl->setParameter($this, "mode", "create");
			$this->ctrl->setParameter($this, "new_type", $new_type);
			$this->tpl->setVariable("FORMACTION",
				$this->ctrl->getFormAction($this));
			//$this->getFormAction("save","adm_object.php?cmd=gateway&mode=create&ref_id=".$_GET["ref_id"]."&new_type=".$new_type));
			$this->tpl->setVariable("TARGET", $this->getTargetFrame("save"));
			$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt($new_type."_add"));
			$this->tpl->setVariable("CMD_SUBMIT", "save");
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		}
	}

	/**
	* save category
	* @access	public
	*/
	function saveObject()
	{
		$data = $_POST;

		// default language set?
		if (!isset($data["default_language"]))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_default_language"),$this->ilias->error_obj->MESSAGE);
		}

		// prepare array fro further checks
		foreach ($data["Fobject"] as $key => $val)
		{
			$langs[$key] = $val["lang"];
		}

		$langs = array_count_values($langs);

		// all languages set?
		if (array_key_exists("",$langs))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_language_selected"),$this->ilias->error_obj->MESSAGE);
		}

		// no single language is selected more than once?
		if (array_sum($langs) > count($langs))
		{
			$this->ilias->raiseError($this->lng->txt("msg_multi_language_selected"),$this->ilias->error_obj->MESSAGE);
		}

		// copy default translation to variable for object data entry
		$_POST["Fobject"]["title"] = $_POST["Fobject"][$_POST["default_language"]]["title"];
		$_POST["Fobject"]["desc"] = $_POST["Fobject"][$_POST["default_language"]]["desc"];

		// always call parent method first to create an object_data entry & a reference
		$newObj = parent::saveObject();

		// setup rolefolder & default local roles if needed (see ilObjForum & ilObjForumGUI for an example)
		//$roles = $newObj->initDefaultRoles();

		// write translations to object_translation
		foreach ($data["Fobject"] as $key => $val)
		{
			if ($key == $data["default_language"])
			{
				$default = 1;
			}
			else
			{
				$default = 0;
			}

			$newObj->addTranslation(ilUtil::stripSlashes($val["title"]),ilUtil::stripSlashes($val["desc"]),$val["lang"],$default);
		}

		// always send a message
		sendInfo($this->lng->txt("cat_added"),true);
		ilUtil::redirect($this->getReturnLocation("save","adm_object.php?".$this->link_params));
	}

	/**
	* edit category
	*
	* @access	public
	*/
	function editObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("write", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->ctrl->setParameter($this,"mode","edit");

		// for lang selection include metadata class
		include_once "./classes/class.ilMetaData.php";

		$this->getTemplateFile("edit",$new_type);
		$array_push = true;

		if ($_SESSION["error_post_vars"])
		{
			$_SESSION["translation_post"] = $_SESSION["error_post_vars"];
			$_GET["mode"] = "session";
			$array_push = false;
		}

		// load from db if edit category is called the first time
		if (($_GET["mode"] != "session"))
		{
			$data = $this->object->getTranslations();
			$_SESSION["translation_post"] = $data;
			$array_push = false;
		}	// remove a translation from session
		elseif ($_GET["entry"] != 0)
		{
			array_splice($_SESSION["translation_post"]["Fobject"],$_GET["entry"],1,array());

			if ($_GET["entry"] == $_SESSION["translation_post"]["default_language"])
			{
				$_SESSION["translation_post"]["default_language"] = "";
			}
		}

		$data = $_SESSION["translation_post"];

		// add additional translation form
		if (!$_GET["entry"] and $array_push)
		{
			$count = array_push($data["Fobject"],array("title" => "","desc" => ""));
		}
		else
		{
			$count = count($data["Fobject"]);
		}

		// stripslashes in form?
		$strip = isset($_SESSION["translation_post"]) ? true : false;

		foreach ($data["Fobject"] as $key => $val)
		{
			// add translation button
			if ($key == $count -1)
			{
				$this->tpl->setCurrentBlock("addTranslation");
				$this->tpl->setVariable("TXT_ADD_TRANSLATION",$this->lng->txt("add_translation")." >>");
				$this->tpl->parseCurrentBlock();
			}

			// remove translation button
			if ($key != 0)
			{
				$this->tpl->setCurrentBlock("removeTranslation");
				$this->tpl->setVariable("TXT_REMOVE_TRANSLATION",$this->lng->txt("remove_translation"));
				$this->ctrl->setParameter($this, "entry", $key);
				$this->ctrl->setParameter($this, "mode", "edit");
				$this->tpl->setVariable("LINK_REMOVE_TRANSLATION", $this->ctrl->getLinkTarget($this, "removeTranslation"));
				$this->tpl->parseCurrentBlock();
			}

			// lang selection
			$this->tpl->addBlockFile("SEL_LANGUAGE", "sel_language", "tpl.lang_selection.html", false);
			$this->tpl->setVariable("SEL_NAME", "Fobject[".$key."][lang]");

			$languages = ilMetaData::getLanguages();

			foreach ($languages as $code => $language)
			{
				$this->tpl->setCurrentBlock("lg_option");
				$this->tpl->setVariable("VAL_LG", $code);
				$this->tpl->setVariable("TXT_LG", $language);

				if ($code == $val["lang"])
				{
					$this->tpl->setVariable("SELECTED", "selected=\"selected\"");
				}

				$this->tpl->parseCurrentBlock();
			}

			// object data
			$this->tpl->setCurrentBlock("obj_form");

			if ($key == 0)
			{
				$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($this->object->getType()."_edit"));
			}
			else
			{
				$this->tpl->setVariable("TXT_HEADER", $this->lng->txt("translation")." ".$key);
			}

			if ($key == $data["default_language"])
			{
				$this->tpl->setVariable("CHECKED", "checked=\"checked\"");
			}

			$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("title"));
			$this->tpl->setVariable("TXT_DESC", $this->lng->txt("desc"));
			$this->tpl->setVariable("TXT_DEFAULT", $this->lng->txt("default"));
			$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
			$this->tpl->setVariable("TITLE", ilUtil::prepareFormOutput($val["title"],$strip));
			$this->tpl->setVariable("DESC", ilUtil::stripSlashes($val["desc"]));
			$this->tpl->setVariable("NUM", $key);
			$this->tpl->parseCurrentBlock();
		}
		
		$this->showCustomIconsEditing();

		// global
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TARGET", $this->getTargetFrame("update"));
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt("save"));
		$this->tpl->setVariable("CMD_SUBMIT", "update");
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
	}

	/**
	* updates object entry in object_data
	*
	* @access	public
	*/
	function updateObject()
	{
		global $rbacsystem;
		if (!$rbacsystem->checkAccess("write", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			$data = $_POST;

			// default language set?
			if (!isset($data["default_language"]))
			{
				$this->ilias->raiseError($this->lng->txt("msg_no_default_language"),$this->ilias->error_obj->MESSAGE);
			}

			// prepare array fro further checks
			foreach ($data["Fobject"] as $key => $val)
			{
				$langs[$key] = $val["lang"];
			}

			$langs = array_count_values($langs);

			// all languages set?
			if (array_key_exists("",$langs))
			{
				$this->ilias->raiseError($this->lng->txt("msg_no_language_selected"),$this->ilias->error_obj->MESSAGE);
			}

			// no single language is selected more than once?
			if (array_sum($langs) > count($langs))
			{
				$this->ilias->raiseError($this->lng->txt("msg_multi_language_selected"),$this->ilias->error_obj->MESSAGE);
			}

			// copy default translation to variable for object data entry
			$_POST["Fobject"]["title"] = $_POST["Fobject"][$_POST["default_language"]]["title"];
			$_POST["Fobject"]["desc"] = $_POST["Fobject"][$_POST["default_language"]]["desc"];

			// first delete all translation entries...
			$this->object->removeTranslations();

			// ...and write new translations to object_translation
			foreach ($data["Fobject"] as $key => $val)
			{
				if ($key == $data["default_language"])
				{
					$default = 1;
				}
				else
				{
					$default = 0;
				}

				$this->object->addTranslation(ilUtil::stripSlashes($val["title"]),ilUtil::stripSlashes($val["desc"]),$val["lang"],$default);
			}

			// update object data entry with default translation
			$this->object->setTitle(ilUtil::stripSlashes($_POST["Fobject"]["title"]));
			$this->object->setDescription(ilUtil::stripSlashes($_POST["Fobject"]["desc"]));
			
			//save custom icons
			if ($this->ilias->getSetting("custom_icons"))
			{
				$this->object->saveIcons($_FILES["cont_big_icon"],
					$_FILES["cont_small_icon"]);
			}
			
			$this->update = $this->object->update();
		}

		sendInfo($this->lng->txt("msg_obj_modified"),true);
		ilUtil::redirect($this->getReturnLocation("update",$this->ctrl->getTargetScript()."?".$this->link_params));
	}

	/**
	* adds a translation form & save post vars to session
	*
	* @access	public
	*/
	function addTranslationObject()
	{
		if (!($_GET["mode"] != "create" or $_GET["mode"] != "edit"))
		{
			$message = get_class($this)."::addTranslationObject(): Missing or wrong parameter! mode: ".$_GET["mode"];
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$_SESSION["translation_post"] = $_POST;
		//ilUtil::redirect($this->getReturnLocation("addTranslation",
		//	"adm_object.php?cmd=".$_GET["mode"]."&entry=0&mode=session&ref_id=".$_GET["ref_id"]."&new_type=".$_GET["new_type"]));
		$this->ctrl->setParameter($this, "entry", 0);
		$this->ctrl->setParameter($this, "mode", "session");
		$this->ctrl->setParameter($this, "new_type", $_GET["new_type"]);
		ilUtil::redirect($this->ctrl->getLinkTarget($this, $_GET["mode"]));
	}

	/**
	* removes a translation form & save post vars to session
	*
	* @access	public
	*/
	function removeTranslationObject()
	{
		if (!($_GET["mode"] != "create" or $_GET["mode"] != "edit"))
		{
			$message = get_class($this)."::removeTranslationObject(): Missing or wrong parameter! mode: ".$_GET["mode"];
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$this->ctrl->setParameter($this, "entry", $_GET["entry"]);
		$this->ctrl->setParameter($this, "mode", "session");
		$this->ctrl->setParameter($this, "new_type", $_GET["new_type"]);
		//ilUtil::redirect("adm_object.php?cmd=".$_GET["mode"]."&entry=".$_GET["entry"]."&mode=session&ref_id=".$_GET["ref_id"]."&new_type=".$_GET["new_type"]);
		ilUtil::redirect($this->ctrl->getLinkTarget($this, $_GET["mode"]));

	}

	/**
	* remove big icon
	*
	* @access	public
	*/
	function removeBigIconObject()
	{

		$_SESSION["translation_post"] = $_POST;
		$this->object->removeBigIcon();
		//$this->ctrl->setParameter($this, "entry", 0);
		//$this->ctrl->setParameter($this, "mode", "session");
		//$this->ctrl->setParameter($this, "new_type", $_GET["new_type"]);
		ilUtil::redirect($this->ctrl->getLinkTarget($this, $_GET["mode"]));
	}
	
	/**
	* remove small icon
	*
	* @access	public
	*/
	function removeSmallIconObject()
	{

		$_SESSION["translation_post"] = $_POST;
		$this->object->removeSmallIcon();
		//$this->ctrl->setParameter($this, "entry", 0);
		//$this->ctrl->setParameter($this, "mode", "session");
		//$this->ctrl->setParameter($this, "new_type", $_GET["new_type"]);
		ilUtil::redirect($this->ctrl->getLinkTarget($this, $_GET["mode"]));
	}

	/**
	* display form for category import
	*/
	function importCategoriesFormObject ()
	{
		/*$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.cat_import_form.html");

		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$this->ref_id."&cmd=gateway");

		$this->tpl->setVariable("TXT_IMPORT_CATEGORIES", $this->lng->txt("import_categories"));
		$this->tpl->setVariable("TXT_IMPORT_FILE", $this->lng->txt("import_file"));

		$this->tpl->setVariable("BTN_IMPORT", $this->lng->txt("import"));
		$this->tpl->setVariable("BTN_CANCEL", $this->lng->txt("cancel"));*/
		ilObjCategoryGUI::_importCategoriesForm($this->ref_id, $this->tpl);
	}

	/**
	* display form for category import (static, also called by RootFolderGUI)
	*/
	function _importCategoriesForm ($a_ref_id, &$a_tpl)
	{
		global $lng, $rbacreview;

		$a_tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.cat_import_form.html");

		$a_tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$a_ref_id."&cmd=gateway");

		$a_tpl->setVariable("TXT_IMPORT_CATEGORIES", $lng->txt("import_categories"));
		$a_tpl->setVariable("TXT_HIERARCHY_OPTION", $lng->txt("import_cat_localrol"));
		$a_tpl->setVariable("TXT_IMPORT_FILE", $lng->txt("import_file"));
		$a_tpl->setVariable("TXT_IMPORT_TABLE", $lng->txt("import_cat_table"));

		$a_tpl->setVariable("BTN_IMPORT", $lng->txt("import"));
		$a_tpl->setVariable("BTN_CANCEL", $lng->txt("cancel"));

		// NEED TO FILL ADOPT_PERMISSIONS HTML FORM....
		$parent_role_ids = $rbacreview->getParentRoleIds($a_ref_id,true);
		
		// sort output for correct color changing
		ksort($parent_role_ids);
		
		foreach ($parent_role_ids as $key => $par)
		  {
		    if ($par["obj_id"] != SYSTEM_ROLE_ID)
		      {
			$check = ilUtil::formCheckbox(0,"adopt[]",$par["obj_id"],1);
			$output["adopt"][$key]["css_row_adopt"] = ilUtil::switchColor($key, "tblrow1", "tblrow2");
			$output["adopt"][$key]["check_adopt"] = $check;
			$output["adopt"][$key]["role_id"] = $par["obj_id"];
			$output["adopt"][$key]["type"] = ($par["type"] == 'role' ? 'Role' : 'Template');
			$output["adopt"][$key]["role_name"] = $par["title"];
		      }
		  }
		
		//var_dump($output);

		// BEGIN ADOPT PERMISSIONS
		foreach ($output["adopt"] as $key => $value)
		  {
		    $a_tpl->setCurrentBlock("ADOPT_PERM_ROW");
		    $a_tpl->setVariable("CSS_ROW_ADOPT",$value["css_row_adopt"]);
		    $a_tpl->setVariable("CHECK_ADOPT",$value["check_adopt"]);
		    $a_tpl->setVariable("LABEL_ID",$value["role_id"]);
		    $a_tpl->setVariable("TYPE",$value["type"]);
		    $a_tpl->setVariable("ROLE_NAME",$value["role_name"]);
		    $a_tpl->parseCurrentBlock();
		  }
	}


	/**
	* import cancelled
	*
	* @access private
	*/
	function importCancelledObject()
	{
		sendInfo($this->lng->txt("action_aborted"),true);
		ilUtil::redirect("adm_object.php?ref_id=".$_GET["ref_id"]);
	}

	/**
	* get user import directory name
	*/
	function _getImportDir()
	{
		return ilUtil::getDataDir()."/cat_import";
	}

	/**
	* import categories
	*/
	function importCategoriesObject()
	{
		ilObjCategoryGUI::_importCategories($_GET["ref_id"]);
		// call to importCategories with $withrol = 0
		ilObjCategoryGUI::_importCategories($_GET["ref_id"], 0);
	}
	
        /**
	 * import categories with local rol
	 */
	function importCategoriesWithRolObject()
	{
	
	  //echo "entra aqui";
	  // call to importCategories with $withrol = 1
	  ilObjCategoryGUI::_importCategories($_GET["ref_id"], 1);
	}

	/**
	* import categories (static, also called by RootFolderGUI)
	*/
	
	function _importCategories($a_ref_id, $withrol_tmp)	
{
		global $lng;

		require_once("classes/class.ilCategoryImportParser.php");

		$import_dir = ilObjCategoryGUI::_getImportDir();

		// create user import directory if necessary
		if (!@is_dir($import_dir))
		{
			ilUtil::createDirectory($import_dir);
		}

		// move uploaded file to user import directory
		$file_name = $_FILES["importFile"]["name"];
		$parts = pathinfo($file_name);
		$full_path = $import_dir."/".$file_name;
		//move_uploaded_file($_FILES["importFile"]["tmp_name"], $full_path);
		ilUtil::moveUploadedFile($_FILES["importFile"]["tmp_name"], $file_name, $full_path);

		// unzip file
		ilUtil::unzip($full_path);

		$subdir = basename($parts["basename"],".".$parts["extension"]);
		$xml_file = $import_dir."/".$subdir."/".$subdir.".xml";
		// CategoryImportParser
		//var_dump($_POST);
		$importParser = new ilCategoryImportParser($xml_file, $a_ref_id, $withrol_tmp);
		$importParser->startParsing();

		sendInfo($lng->txt("categories_imported"), true);
		ilUtil::redirect("adm_object.php?ref_id=".$a_ref_id);
	}

	// METHODS for local user administration
	function listUsersObject($show_delete = false)
	{
		include_once './classes/class.ilLocalUser.php';
		include_once './classes/class.ilObjUserGUI.php';

		global $rbacsystem,$rbacreview;

		if(!$rbacsystem->checkAccess("cat_administrate_users",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_admin_users"),$this->ilias->error_obj->MESSAGE);
		}

		$_SESSION['delete_users'] = $show_delete ? $_SESSION['delete_users'] : array();

		$_SESSION['filtered_users'] = isset($_POST['filter']) ? $_POST['filter'] : $_SESSION['filtered_users'];


		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		if(count($rbacreview->getGlobalAssignableRoles()) or in_array(SYSTEM_ROLE_ID,$_SESSION["RoleId"]))
		{
			// add user button
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK",$this->ctrl->getLinkTargetByClass('ilobjusergui','create'));
			$this->tpl->setVariable("BTN_TXT",$this->lng->txt('add_user'));
			$this->tpl->parseCurrentBlock();

			// import user button
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK",$this->ctrl->getLinkTargetByClass('ilobjuserfoldergui','importUserForm'));
			$this->tpl->setVariable("BTN_TXT",$this->lng->txt('import_users'));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			sendInfo($this->lng->txt('no_roles_user_can_be_assigned_to'));
			return true;
		}
		if(!count($users = ilLocalUser::_getAllUserIds($_SESSION['filtered_users'])))
		{
			sendInfo($this->lng->txt('no_local_users'));

			return true;
		}


		if($show_delete)
		{
			$this->tpl->setCurrentBlock("confirm_delete");
			$this->tpl->setVariable("CONFIRM_FORMACTION",$this->ctrl->getFormAction($this));
			$this->tpl->setVariable("TXT_CANCEL",$this->lng->txt('cancel'));
			$this->tpl->setVariable("CONFIRM_CMD",'performDeleteUsers');
			$this->tpl->setVariable("TXT_CONFIRM",$this->lng->txt('delete'));
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.cat_admin_users.html');

		$parent = ilLocalUser::_getFolderIds();
		if(count($parent) > 1)
		{
			$this->tpl->setCurrentBlock("filter");
			$this->tpl->setVariable("FILTER_TXT_FILTER",$this->lng->txt('filter'));
			$this->tpl->setVariable("SELECT_FILTER",$this->__buildFilterSelect($parent));
			$this->tpl->setVariable("FILTER_ACTION",$this->ctrl->getFormAction($this));
			$this->tpl->setVariable("FILTER_NAME",'listUsers');
			$this->tpl->setVariable("FILTER_VALUE",$this->lng->txt('apply_filter'));
			$this->tpl->parseCurrentBlock();
		}
		
		$counter = 0;
		$editable = false;
		foreach($users as $user_id)
		{
			$tmp_obj =& ilObjectFactory::getInstanceByObjId($user_id,false);

			if($tmp_obj->getTimeLimitOwner() == $this->object->getRefId())
			{
				$editable = true;
				$f_result[$counter][]	= ilUtil::formCheckbox(in_array($tmp_obj->getId(),$_SESSION['delete_users']) ? 1 : 0,
															   "user_ids[]",$tmp_obj->getId());

				$this->ctrl->setParameterByClass('ilobjusergui','obj_id',$user_id);
				$f_result[$counter][]	= '<a href="'.$this->ctrl->getLinkTargetByClass('ilobjusergui','edit').'">'.
					$tmp_obj->getLogin().'</a>';
			}
			else
			{
				$f_result[$counter][]	= '&nbsp;';
				$f_result[$counter][]	= $tmp_obj->getLogin();
			}

			$f_result[$counter][]	= $tmp_obj->getFirstname();
			$f_result[$counter][]	= $tmp_obj->getLastname();

			
			switch($tmp_obj->getTimeLimitOwner())
			{
				case ilLocalUser::_getUserFolderId():
					$f_result[$counter][]	= $this->lng->txt('global');
					break;

				default:
					$f_result[$counter][] = ilObject::_lookupTitle(ilObject::_lookupObjId($tmp_obj->getTimeLimitOwner()));
			}
			
			// role assignment
			$this->ctrl->setParameter($this,'obj_id',$user_id);
			$f_result[$counter][]	= '[<a href="'.$this->ctrl->getLinkTarget($this,'assignRoles').'">'.
				$this->lng->txt('edit').'</a>]';
			
			unset($tmp_obj);
			++$counter;
		}
		$this->__showUsersTable($f_result,"listUsersObject",$editable);
		
		return true;
	}

	function performDeleteUsersObject()
	{
		include_once './classes/class.ilLocalUser.php';

		foreach($_SESSION['delete_users'] as $user_id)
		{
			if(!in_array($user_id,ilLocalUser::_getAllUserIds($this->object->getRefId())))
			{
				die('user id not valid');
			}
			if(!$tmp_obj =& ilObjectFactory::getInstanceByObjId($user_id,false))
			{
				continue;
			}
			$tmp_obj->delete();
		}
		sendInfo($this->lng->txt('deleted_users'));
		$this->listUsersObject();

		return true;
	}
			
	function deleteUserObject()
	{
		if(!count($_POST['user_ids']))
		{
			sendInfo($this->lng->txt('no_users_selected'));
			$this->listUsersObject();
			
			return true;
		}
		$_SESSION['delete_users'] = $_POST['user_ids'];

		sendInfo('sure_delete_selected_users');
		$this->listUsersObject(true);
		return true;
	}

	function assignRolesObject()
	{
		global $rbacreview;

		include_once './classes/class.ilLocalUser.php';

		if(!isset($_GET['obj_id']))
		{
			sendInfo('no_user_selected');
			$this->listUsersObject();

			return true;
		}

		// check local user
		$tmp_obj =& ilObjectFactory::getInstanceByObjId($_GET['obj_id']);
		if($tmp_obj->getTimeLimitOwner() != $this->object->getRefId() and
		   !in_array(SYSTEM_ROLE_ID,$_SESSION['RoleId']))
		{
			$check_disable = true;
		}
		else
		{
			$check_disable = false;
		}
		if(!in_array(SYSTEM_ROLE_ID,$_SESSION["RoleId"]))
		{
			$global_roles = $rbacreview->getGlobalAssignableRoles();
		}
		else
		{
			$global_roles = $rbacreview->getGlobalRolesArray();
		}
		$roles = array_merge($global_roles,
							 $rbacreview->getAssignableChildRoles($this->object->getRefId()));

		if(!count($roles))
		{
			sendInfo($this->lng->txt('no_roles_user_can_be_assigned_to'));
			$this->listUsersObject();

			return true;
		}
		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.cat_role_assignment.html');
		$this->__showButton('listUsers',$this->lng->txt('back'));

		$ass_roles = $rbacreview->assignedRoles($_GET['obj_id']);

		$counter = 0;
		foreach($roles as $role)
		{
			$role_obj =& ilObjectFactory::getInstanceByObjId($role['obj_id']);
			
			if($check_disable)
			{
				$disabled = $role['role_type'] == 'global' ? true : false;
			}
			else
			{
				$disabled = false;
			}
			$f_result[$counter][] = ilUtil::formCheckbox(in_array($role['obj_id'],$ass_roles) ? 1 : 0,
														 'role_ids[]',
														 $role['obj_id'],
														 $disabled);
			$f_result[$counter][] = $role_obj->getTitle();
			$f_result[$counter][] = $role_obj->getDescription();
			$f_result[$counter][] = $role['role_type'] == 'local' ? 
				$this->lng->txt('local') :
				$this->lng->txt('global');
			
			unset($role_obj);
			++$counter;
		}
		$this->__showRolesTable($f_result,"assignRolesObject");
	}

	function assignSaveObject()
	{
		global $rbacreview,$rbacadmin;

		include_once './classes/class.ilLocalUser.php';
		
		// check hack
		if(!isset($_REQUEST['obj_id']) or !in_array($_REQUEST['obj_id'],ilLocalUser::_getAllUserIds()))
		{
			sendInfo('no_user_selected');
			$this->listUsersObject();

			return true;
		}
		// check minimum one global role
		if(!$this->__checkGlobalRoles($_POST['role_ids']))
		{
			sendInfo($this->lng->txt('no_global_role_left'));
			$this->assignRolesObject();

			return false;
		}

		// De-assign roles
		if(!in_array(SYSTEM_ROLE_ID,$_SESSION["RoleId"]))
		{
			$global_roles = $rbacreview->getGlobalAssignableRoles();
		}
		else
		{
			$global_roles = $rbacreview->getGlobalRolesArray();
		}
		$roles = array_merge($global_roles,
							 $rbacreview->getAssignableChildRoles($this->object->getRefId()));

		$new_role_ids = $_POST['role_ids'] ? $_POST['role_ids'] : array();
		$assigned_roles = $rbacreview->assignedRoles((int) $_REQUEST['obj_id']);
		foreach($roles as $role)
		{
			if(in_array($role['obj_id'],$new_role_ids) and !in_array($role['obj_id'],$assigned_roles))
			{
				$rbacadmin->assignUser($role['obj_id'],(int) $_REQUEST['obj_id']);
			}
			if(in_array($role['obj_id'],$assigned_roles) and !in_array($role['obj_id'],$new_role_ids))
			{
				$rbacadmin->deassignUser($role['obj_id'],(int) $_REQUEST['obj_id']);
			}
		}
		sendInfo($this->lng->txt('role_assignment_updated'));
		$this->assignRolesObject();
		
		return true;
	}

	// PRIVATE
	function __checkGlobalRoles($new_assigned)
	{
		global $rbacreview;

		// new assignment by form
		$new_assigned = $new_assigned ? $new_assigned : array();
		$assigned = $rbacreview->assignedRoles((int) $_GET['obj_id']);

		// all assignable globals
		if(!in_array(SYSTEM_ROLE_ID,$_SESSION["RoleId"]))
		{
			$ga = $rbacreview->getGlobalAssignableRoles();
		}
		else
		{
			$ga = $rbacreview->getGlobalRolesArray();
		}
		#$ga = array();
		#$ga = $rbacreview->getGlobalAssignableRoles();
		foreach($ga as $role)
		{
			$global_assignable[] = $role['obj_id'];
		}

		$new_visible_assigned_roles = array_intersect($new_assigned,$global_assignable);
		$all_assigned_roles = array_intersect($assigned,$rbacreview->getGlobalRoles());
		$main_assigned_roles = array_diff($all_assigned_roles,$global_assignable);

		if(!count($new_visible_assigned_roles) and !count($main_assigned_roles))
		{
			return false;
		}
		return true;
	}


	function __showRolesTable($a_result_set,$a_from = "")
	{
		$tbl =& $this->__initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		// SET FORMAACTION
		$tpl->setCurrentBlock("tbl_form_header");

		$this->ctrl->setParameter($this,'obj_id',$_GET['obj_id']);
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		// SET FOOTER BUTTONS
		$tpl->setVariable("COLUMN_COUNTS",4);
		$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		
		$tpl->setCurrentBlock("tbl_action_button");
		$tpl->setVariable("BTN_NAME","assignSave");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("change_assignment"));
		$tpl->parseCurrentBlock();
		
		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("TPLPATH",$this->tpl->tplPath);
		$tpl->parseCurrentBlock();

		$tmp_obj =& ilObjectFactory::getInstanceByObjId($_GET['obj_id']);
		$title = $this->lng->txt('role_assignment').' ('.$tmp_obj->getFullname().')';

		$tbl->setTitle($title,"icon_role_b.gif",$this->lng->txt("role_assignment"));
		$tbl->setHeaderNames(array('',
								   $this->lng->txt("title"),
								   $this->lng->txt('description'),
								   $this->lng->txt("type")));
		$tbl->setHeaderVars(array("",
								  "title",
								  "description",
								  "type"),
							array("ref_id" => $this->object->getRefId(),
								  "cmd" => "assignRoles",
								  "obj_id" => $_GET['obj_id'],
								  "cmdClass" => "ilobjcategorygui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("4%","35%","45%","16%"));

		$this->set_unlimited = true;
		$this->__setTableGUIBasicData($tbl,$a_result_set,$a_from,true);
		$tbl->render();

		$this->tpl->setVariable("ROLES_TABLE",$tbl->tpl->get());

		return true;
	}		

	function __showUsersTable($a_result_set,$a_from = "",$a_footer = true)
	{
		$tbl =& $this->__initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		// SET FORMAACTION
		$tpl->setCurrentBlock("tbl_form_header");

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();


		if($a_footer)
		{
			// SET FOOTER BUTTONS
			$tpl->setVariable("COLUMN_COUNTS",6);
			$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));

			$tpl->setCurrentBlock("tbl_action_button");
			$tpl->setVariable("BTN_NAME","deleteUser");
			$tpl->setVariable("BTN_VALUE",$this->lng->txt("delete"));
			$tpl->parseCurrentBlock();
			
			$tpl->setCurrentBlock("tbl_action_row");
			$tpl->setVariable("TPLPATH",$this->tpl->tplPath);
			$tpl->parseCurrentBlock();
		}

		$tbl->setTitle($this->lng->txt("users"),"icon_usr_b.gif",$this->lng->txt("users"));
		$tbl->setHeaderNames(array('',
								   $this->lng->txt("username"),
								   $this->lng->txt("firstname"),
								   $this->lng->txt("lastname"),
								   $this->lng->txt('context'),
								   $this->lng->txt('role_assignment')));
		$tbl->setHeaderVars(array("",
								  "login",
								  "firstname",
								  "lastname",
								  "context",
								  "role_assignment"),
							array("ref_id" => $this->object->getRefId(),
								  "cmd" => "listUsers",
								  "cmdClass" => "ilobjcategorygui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("4%","20%","20%","20%","20%","20%"));

		$this->__setTableGUIBasicData($tbl,$a_result_set,$a_from,true);
		$tbl->render();

		$this->tpl->setVariable("USERS_TABLE",$tbl->tpl->get());

		return true;
	}		

	function __setTableGUIBasicData(&$tbl,&$result_set,$a_from = "",$a_footer = true)
	{
		switch ($a_from)
		{
			case "listUsersObject":
			case "assignRolesObject":
				$offset = $_GET["offset"];
				// init sort_by (unfortunatly sort_by is preset with 'title'
				if ($_GET["sort_by"] == "title" or empty($_GET["sort_by"]))
				{
					$_GET["sort_by"] = "login";
				}
				$order = $_GET["sort_by"];
				$direction = $_GET["sort_order"];
				break;
			
			case "clipboardObject":
				$offset = $_GET["offset"];
				$order = $_GET["sort_by"];
				$direction = $_GET["sort_order"];
				$tbl->disable("footer");
				break;
				
			default:
				$offset = $_GET["offset"];
				$order = $_GET["sort_by"];
				$direction = $_GET["sort_order"];
				break;
		}

		$tbl->setOrderColumn($order);
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		if($this->set_unlimited)
		{
			$tbl->setLimit($_GET["limit"]*100);
		}
		else
		{
			$tbl->setLimit($_GET['limit']);
		}
		$tbl->setMaxCount(count($result_set));

		if($a_footer)
		{
			$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		}
		else
		{
			$tbl->disable('footer');
		}
		$tbl->setData($result_set);
	}

	function &__initTableGUI()
	{
		include_once "./classes/class.ilTableGUI.php";

		return new ilTableGUI(0,false);
	}

	function __buildFilterSelect($a_parent_ids)
	{
		$action[0] = $this->lng->txt('all_users');

		foreach($a_parent_ids as $parent)
		{
			switch($parent)
			{
				case ilLocalUser::_getUserFolderId():
					$action[ilLocalUser::_getUserFolderId()] = $this->lng->txt('global_user'); 
					
					break;

				default:
					$action[$parent] = $this->lng->txt('users').' ('.ilObject::_lookupTitle(ilObject::_lookupObjId($parent)).')';

					break;
			}
		}
		return ilUtil::formSelect($_SESSION['filtered_users'],"filter",$action,false,true);
	}
	

} // END class.ilObjCategoryGUI
?>
