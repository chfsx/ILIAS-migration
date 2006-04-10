<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
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
* Class ilObjStyleSettingsGUI
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
* 
* @ilCtrl_Calls ilObjStyleSettingsGUI: ilPermissionGUI
* 
* @extends ilObjectGUI
* @package ilias-core
*/

include_once "class.ilObjectGUI.php";

class ilObjStyleSettingsGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjStyleSettingsGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output = true)
	{
		global $lng;
		
		$this->type = "stys";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);
		
		$lng->loadLanguageModule("style");
	}
	
	function &executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();
		$this->prepareOutput();

		switch($next_class)
		{
			case 'ilpermissiongui':
				include_once("./classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			default:
				if ($cmd == "" || $cmd == "view")
				{
					$cmd = "editBasicSettings";
				}
				$cmd .= "Object";
				$this->$cmd();

				break;
		}
		return true;
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
		
		ilUtil::redirect($this->getReturnLocation("save",$this->ctrl->getLinkTarget($this,"")));
	}
	
	/**
	* edit basic style settings
	*/
	function editBasicSettingsObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->tpl->addBlockfile("ADM_CONTENT", "style_basic_settings", "tpl.stys_basic_settings.html");
		$this->tpl->setCurrentBlock("style_settings");

		$settings = $this->ilias->getAllSettings();

		$this->tpl->setVariable("FORMACTION_STYLESETTINGS", $this->ctrl->getFormAction($this));		
		$this->tpl->setVariable("TXT_STYLE_SETTINGS", $this->lng->txt("basic_settings"));
		$this->tpl->setVariable("TXT_ICONS_IN_TYPED_LISTS", $this->lng->txt("icons_in_typed_lists"));
		$this->tpl->setVariable("TXT_ICONS_IN_HEADER", $this->lng->txt("icons_in_header"));
		$this->tpl->setVariable("TXT_ICONS_IN_ITEM_ROWS", $this->lng->txt("icons_in_item_rows"));
		$this->tpl->setVariable("TXT_ICONS_IN_TYPED_LISTS_INFO", $this->lng->txt("icons_in_typed_lists_info"));
		
		$this->tpl->setVariable("TXT_ENABLE_CUSTOM_ICONS", $this->lng->txt("enable_custom_icons"));
		$this->tpl->setVariable("TXT_ENABLE_CUSTOM_ICONS_INFO", $this->lng->txt("enable_custom_icons_info"));
		$this->tpl->setVariable("TXT_CUSTOM_ICON_SIZE_BIG", $this->lng->txt("custom_icon_size_big"));
		$this->tpl->setVariable("TXT_CUSTOM_ICON_SIZE_SMALL", $this->lng->txt("custom_icon_size_small"));
		$this->tpl->setVariable("TXT_WIDTH_X_HEIGHT", $this->lng->txt("width_x_height"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		
		// set current values
		if ($settings["custom_icons"])
		{
			$this->tpl->setVariable("CHK_CUSTOM_ICONS","checked=\"checked\"");
		}
		if ($settings["icon_position_in_lists"] == "item_rows")
		{
			$this->tpl->setVariable("SEL_ICON_POS_ITEM_ROWS","selected=\"selected\"");
		}
		else
		{
			$this->tpl->setVariable("SEL_ICON_POS_HEADER","selected=\"selected\"");
		}
		$this->tpl->setVariable("CUST_ICON_BIG_WIDTH", $settings["custom_icon_big_width"]);
		$this->tpl->setVariable("CUST_ICON_BIG_HEIGHT", $settings["custom_icon_big_height"]);
		$this->tpl->setVariable("CUST_ICON_SMALL_WIDTH", $settings["custom_icon_small_width"]);
		$this->tpl->setVariable("CUST_ICON_SMALL_HEIGHT", $settings["custom_icon_small_height"]);

		$this->tpl->parseCurrentBlock();
	}
	
	/**
	* save basic style settings
	*/
	function saveBasicStyleSettingsObject()
	{
		$this->ilias->setSetting("icon_position_in_lists", $_POST["icon_position_in_lists"]);
		$this->ilias->setSetting("custom_icons", $_POST["custom_icons"]);
		$this->ilias->setSetting("custom_icon_big_width", (int) $_POST["custom_icon_big_width"]);
		$this->ilias->setSetting("custom_icon_big_height", (int) $_POST["custom_icon_big_height"]);
		$this->ilias->setSetting("custom_icon_small_width", (int) $_POST["custom_icon_small_width"]);
		$this->ilias->setSetting("custom_icon_small_height", (int) $_POST["custom_icon_small_height"]);
		sendInfo($this->lng->txt("msg_obj_modified"), true);
		ilUtil::redirect($this->ctrl->getLinkTarget($this,"editBasicSettings"));		
	}
	
	/**
	* view list of styles
	*/
	function editContentStylesObject()
	{
		global $rbacsystem, $ilias;
		
		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		include_once "./classes/class.ilTableGUI.php";

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");
		
		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.styf_row.html");

		$num = 0;

		$this->tpl->setVariable("FORMACTION",
			$this->ctrl->getFormAction($this));
		// "adm_object.php?ref_id=".$this->ref_id."$obj_str&cmd=gateway");

		// create table
		$tbl = new ilTableGUI();

		// title & header columns
		$tbl->setTitle($this->lng->txt("content_styles"),"icon_sty.gif",
			$this->lng->txt("content_styles"));

		//$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));

		// title
		$header_names = array("", $this->lng->txt("title"),
			$this->lng->txt("purpose"), $this->lng->txt("sty_standard_style"));
		$tbl->setHeaderNames($header_names);

		$header_params = array("ref_id" => $this->ref_id);
		$tbl->setHeaderVars(array("", "title", "purpose", "standard"), $header_params);
		$tbl->setColumnWidth(array("0%", "60%", "20%", "20%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		
		// get style ids
		$style_entries = array();
		$styles = $this->object->getStyles();
		foreach($styles as $style)
		{
			$style_entries[$style["title"].":".$style["id"]]
				= $style;
		}
		ksort($style_entries);
		
		// todo
		$tbl->setMaxCount(count($style_entries));

		$this->tpl->setVariable("COLUMN_COUNTS", 4);

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));

		$this->showActions(true);

		include_once ("classes/class.ilObjStyleSheet.php");
		
		$fixed_style = $ilias->getSetting("fixed_content_style_id");
		$default_style = $ilias->getSetting("default_content_style_id");

		foreach ($style_entries as $style)
		{
			$this->tpl->setCurrentBlock("style_row");
		
			// color changing
			$css_row = ($css_row == "tblrow2")
				? "tblrow1"
				: "tblrow2";

			$this->tpl->setVariable("CHECKBOX_ID", $style["id"]);
			$this->tpl->setVariable("TXT_TITLE", $style["title"]);
			if (ilObjStyleSheet::_lookupStandard($style["id"]))
			{
				$this->tpl->setVariable("CHECKED_STY", 'checked="checked"');
			}
			$this->tpl->setVariable("TXT_DESC", ilObject::_lookupDescription($style["id"]));
			$this->ctrl->setParameterByClass("ilobjstylesheetgui", "obj_id", $style["id"]); 
			$this->tpl->setVariable("LINK_STYLE",
				$this->ctrl->getLinkTargetByClass("ilobjstylesheetgui"), "view");
			$this->tpl->setVariable("ROWCOL", $css_row);
			if ($style["id"] == $fixed_style)
			{
				$this->tpl->setVariable("TXT_PURPOSE", $this->lng->txt("global_fixed"));
			}
			if ($style["id"] == $default_style)
			{
				$this->tpl->setVariable("TXT_PURPOSE", $this->lng->txt("global_default"));
			}
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("tbl_content");
			$this->tpl->parseCurrentBlock();

		} //if is_array
		
		if (count($style_entries) == 0)
		{
            $tbl->disable("header");
			$tbl->disable("footer");
			
			$this->tpl->setCurrentBlock("text");
			$this->tpl->setVariable("TXT_CONTENT", $this->lng->txt("obj_not_found"));
			$this->tpl->parseCurrentBlock();
			
			$this->tpl->setCurrentBlock("tbl_content");
			$this->tpl->parseCurrentBlock();
		}

		// render table
		$tbl->render();
		
	}
	
	/**
	* edit system styles
	*/
	function editSystemStylesObject()
	{
		global $rbacsystem, $ilias, $styleDefinition;;
		
		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->tpl->addBlockfile("ADM_CONTENT", "style_settings", "tpl.stys_settings.html");
		$this->tpl->setCurrentBlock("style_settings");

		$settings = $this->ilias->getAllSettings();

		$this->tpl->setVariable("FORMACTION_STYLESETTINGS", $this->ctrl->getFormAction($this));		
		$this->tpl->setVariable("TXT_STYLE_SETTINGS", $this->lng->txt("system_style_settings"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("TXT_DEFAULT_SKIN_STYLE", $this->lng->txt("default_skin_style"));
		$this->tpl->setVariable("TXT_SKIN_STYLE_ACTIVATION", $this->lng->txt("style_activation"));
		$this->tpl->setVariable("TXT_NUMBER_OF_USERS", $this->lng->txt("num_users"));
		$this->tpl->setVariable("TXT_MOVE_USERS_TO_STYLE", $this->lng->txt("move_users_to_style"));
		
		// get all templates
		$templates = $styleDefinition->getAllTemplates();

		$all_styles = array();
		
		foreach ($templates as $template)
		{
			// get styles definition for template
			$styleDef =& new ilStyleDefinition($template["id"]);
			$styleDef->startParsing();
			$styles = $styleDef->getStyles();

			foreach ($styles as $style)
			{
				if ($this->ilias->ini->readVariable("layout","skin") == $template["id"] &&
					$this->ilias->ini->readVariable("layout","style") == $style["id"])
				{
					$this->tpl->setVariable("SKINSELECTED", "selected=\"selected\"");
				}

				// default selection list
				$this->tpl->setCurrentBlock("selectskin");
				$this->tpl->setVariable("SKINVALUE", $template["id"].":".$style["id"]);
				$this->tpl->setVariable("SKINOPTION", $styleDef->getTemplateName()." / ".$style["name"]);
				$this->tpl->parseCurrentBlock();
				
				// can be optimized
				foreach ($templates as $template2)
				{
					// get styles definition for template
					$styleDef2 =& new ilStyleDefinition($template2["id"]);
					$styleDef2->startParsing();
					$styles2 = $styleDef2->getStyles();
		
					foreach ($styles2 as $style2)
					{
						if (ilObjStyleSettings::_lookupActivatedStyle($template2["id"], $style2["id"]))
						{
							$this->tpl->setCurrentBlock("move_to_skin");
							$this->tpl->setVariable("TOSKINVALUE", $template2["id"].":".$style2["id"]);
							$this->tpl->setVariable("TOSKINOPTION", $styleDef2->getTemplateName()." / ".$style2["name"]);
							$this->tpl->parseCurrentBlock();
						}
					}
				}
				
				// activation checkbox
				$this->tpl->setCurrentBlock("activation_checkbox");
				$this->tpl->setVariable("VAL_SKIN_STYLE", $template["id"].":".$style["id"]);
				if (ilObjStyleSettings::_lookupActivatedStyle($template["id"], $style["id"]))
				{
					$this->tpl->setVariable("CHK_SKIN_STYLE", " checked=\"1\" ");
				}
				$this->tpl->parseCurrentBlock();
				
				// activation row
				$this->tpl->setCurrentBlock("style_activation");
				$this->tpl->setVariable("VAL_MOVE_SKIN_STYLE", $template["id"].":".$style["id"]);
				$this->tpl->setVariable("TXT_SKIN_STYLE_TITLE", 
					$styleDef->getTemplateName()." / ".$style["name"]);
				$num_users = ilObjUser::_getNumberOfUsersForStyle($template["id"], $style["id"]);
				$this->tpl->setVariable("VAL_NUM_USERS", $num_users);
				$this->tpl->parseCurrentBlock();
				
				$all_styles[] = $template["id"].":".$style["id"];
			}
		}
		
		// get all user assigned styles
		$all_user_styles = ilObjUser::_getAllUserAssignedStyles();
		
		// output "other" row for all users, that are not assigned to
		// any existing style
		$users_missing_styles = 0;
		foreach($all_user_styles as $style)
		{
			if (!in_array($style, $all_styles))
			{
				$style_arr = explode(":", $style);
				$users_missing_styles += ilObjUser::_getNumberOfUsersForStyle($style_arr[0], $style_arr[1]);
			}
		}

		if ($users_missing_styles > 0)
		{			
			// can be optimized
			foreach ($templates as $template2)
			{
				// get styles definition for template
				$styleDef2 =& new ilStyleDefinition($template2["id"]);
				$styleDef2->startParsing();
				$styles2 = $styleDef2->getStyles();
	
				foreach ($styles2 as $style2)
				{
					if (ilObjStyleSettings::_lookupActivatedStyle($template2["id"], $style2["id"]))
					{
						$this->tpl->setCurrentBlock("move_to_skin");
						$this->tpl->setVariable("TOSKINVALUE", $template2["id"].":".$style2["id"]);
						$this->tpl->setVariable("TOSKINOPTION", $styleDef2->getTemplateName()." / ".$style2["name"]);
						$this->tpl->parseCurrentBlock();
					}
				}
			}
			
			$this->tpl->setCurrentBlock("style_activation");
			$this->tpl->setVariable("TXT_SKIN_STYLE_TITLE",
				$this->lng->txt("other"));
			$this->tpl->setVariable("VAL_NUM_USERS",
				$users_missing_styles);
			$this->tpl->setVariable("VAL_MOVE_SKIN_STYLE", "other");
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->parseCurrentBlock();
	}
	

	/**
	* save skin and style settings
	*/
	function saveStyleSettingsObject()
	{
		global $styleDefinition;
		
		// check if one style is activated
		if (count($_POST["st_act"]) < 1)
		{
			$this->ilias->raiseError($this->lng->txt("at_least_one_style"), $this->ilias->error_obj->MESSAGE);
		}
		
		// check if a style should be deactivated, that still has
		// a user assigned to
		$templates = $styleDefinition->getAllTemplates();
		$all_styles = array();
		foreach ($templates as $template)
		{
			// get styles definition for template
			$styleDef =& new ilStyleDefinition($template["id"]);
			$styleDef->startParsing();
			$styles = $styleDef->getStyles();
			foreach ($styles as $style)
			{
				if (!isset($_POST["st_act"][$template["id"].":".$style["id"]]))
				{
					if (ilObjUser::_getNumberOfUsersForStyle($template["id"], $style["id"]) > 1)
					{
						$this->ilias->raiseError($this->lng->txt("cant_deactivate_if_users_assigned"), $this->ilias->error_obj->MESSAGE);
					}
					else
					{
						ilObjStyleSettings::_deactivateStyle($template["id"], $style["id"]);
					}
				}
				else
				{
					ilObjStyleSettings::_activateStyle($template["id"], $style["id"]);
				}
				$all_styles[] = $template["id"].":".$style["id"];
			}
		}
		
		// move users to other skin
		foreach($_POST["move_users"] as $key => $value)
		{
			if ($value != "")
			{
				$to = explode(":", $value);
				
				if ($key != "other")
				{
					$from = explode(":", $key);
					ilObjUser::_moveUsersToStyle($from[0],$from[1],$to[0],$to[1]);
				}
				else
				{
					// get all user assigned styles
					$all_user_styles = ilObjUser::_getAllUserAssignedStyles();
					
					// move users that are not assigned to
					// currently existing style
					foreach($all_user_styles as $style)
					{
						if (!in_array($style, $all_styles))
						{
							$style_arr = explode(":", $style);
							ilObjUser::_moveUsersToStyle($style_arr[0],$style_arr[1],$to[0],$to[1]);
						}
					}
				}
			}
		}
		
		//set default skin and style
		if ($_POST["default_skin_style"] != "")
		{
			$sknst = explode(":", $_POST["default_skin_style"]);

			if ($this->ilias->ini->readVariable("layout","style") != $sknst[1] ||
				$this->ilias->ini->readVariable("layout","skin") != $sknst[0])
			{
				$this->ilias->ini->setVariable("layout","skin", $sknst[0]);
				$this->ilias->ini->setVariable("layout","style",$sknst[1]);
			}
		}
		$this->ilias->ini->write();
//echo "redirect-".$this->ctrl->getLinkTarget($this,"editSystemStyles")."-";
		sendInfo($this->lng->txt("msg_obj_modified"), true);
		ilUtil::redirect($this->ctrl->getLinkTarget($this,"editSystemStyles"));
	}
	
	/**
	* display deletion confirmation screen
	*
	* @access	public
 	*/
	function deleteStyleObject($a_error = false)
	{
		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// SAVE POST VALUES
		$_SESSION["saved_post"] = $_POST["id"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm_deletion.html");

		if(!$a_error)
		{
			sendInfo($this->lng->txt("info_delete_sure"));
		}

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		// BEGIN TABLE HEADER
		$this->tpl->setCurrentBlock("table_header");
		$this->tpl->setVariable("TEXT", $this->lng->txt("objects"));
		$this->tpl->parseCurrentBlock();
		
		// END TABLE HEADER

		// BEGIN TABLE DATA
		$counter = 0;

		foreach ($_POST["id"] as $id)
		{
			$this->tpl->setCurrentBlock("table_row");
			$this->tpl->setVariable("IMG_OBJ",ilUtil::getImagePath("icon_sty.gif"));
			$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
			$this->tpl->setVariable("TEXT_CONTENT",ilObject::_lookupTitle($id));
			$this->tpl->parseCurrentBlock();
		}
		
		// END TABLE DATA

		// BEGIN OPERATION_BTN
		$buttons = array("confirmedDelete"  => $this->lng->txt("confirm"),
			"cancelDelete"  => $this->lng->txt("cancel"));
		foreach ($buttons as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
	}
	
	
	/**
	* delete selected style objects
	*/
	function confirmedDeleteObject()
	{
		global $ilias;
		
		foreach($_SESSION["saved_post"] as $id)
		{
			$this->object->removeStyle($id);
			$style_obj =& $ilias->obj_factory->getInstanceByObjId($id);
			$style_obj->delete();
		}
		$this->object->update();
		
		ilUtil::redirect($this->getReturnLocation("delete",
			$this->ctrl->getLinkTarget($this,"editContentStyles")));
	}
	
	
	/**
	* toggle global default style
	*
	* @access	public
 	*/
	function toggleGlobalDefaultObject()
	{
		global $ilias;
		
		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		$ilias->deleteSetting("fixed_content_style_id");
		$def_style = $ilias->getSetting("default_content_style_id");
		
		if ($def_style != $_POST["id"][0])
		{
			$ilias->setSetting("default_content_style_id", $_POST["id"][0]);
		}
		else
		{
			$ilias->deleteSetting("default_content_style_id");
		}
		
		ilUtil::redirect($this->ctrl->getLinkTarget($this, "editContentStyles"));
	}

	/**
	* toggle global fixed style
	*
	* @access	public
 	*/
	function toggleGlobalFixedObject()
	{
		global $ilias;
		
		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		$ilias->deleteSetting("default_content_style_id");
		$fixed_style = $ilias->getSetting("fixed_content_style_id");
		if ($fixed_style == $_POST["id"][0])
		{
			$ilias->deleteSetting("fixed_content_style_id");
		}
		else
		{
			$ilias->setSetting("fixed_content_style_id", $_POST["id"][0]);
		}
		ilUtil::redirect($this->ctrl->getLinkTarget($this, "editContentStyles"));
	}
	
	
	/**
	* save standard styles
	*/
	function saveStandardStylesObject()
	{
		include_once("classes/class.ilObjStyleSheet.php");
		$styles = $this->object->getStyles();
		foreach($styles as $style)
		{
			if ($_POST["std_".$style["id"]] == 1)
			{
				ilObjStyleSheet::_writeStandard($style["id"], 1);
			}
			else
			{
				ilObjStyleSheet::_writeStandard($style["id"], 0);
			}
		}
		ilUtil::redirect($this->ctrl->getLinkTarget($this, "editContentStyles"));
	}
	
	/**
	* show possible action (form buttons)
	*
	* @param	boolean
	* @access	public
 	*/
	function showActions($with_subobjects = false)
	{

		// delete
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "deleteStyle");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("delete"));
		$this->tpl->parseCurrentBlock();

		// set global default
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "toggleGlobalDefault");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("toggleGlobalDefault"));
		$this->tpl->parseCurrentBlock();
		
		// set global default
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "toggleGlobalFixed");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("toggleGlobalFixed"));
		$this->tpl->parseCurrentBlock();

		// save standard files
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "saveStandardStyles");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("sty_save_standard_styles"));
		$this->tpl->parseCurrentBlock();

		if ($with_subobjects === true)
		{
			$this->showPossibleSubObjects();
		}
		
		$this->tpl->setCurrentBlock("tbl_action_row");
		$this->tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* cancel deletion of object
	*
	* @access	public
	*/
	function cancelDeleteObject()
	{
		session_unregister("saved_post");

		sendInfo($this->lng->txt("msg_cancel"),true);
		$this->ctrl->redirect($this, "editContentStyles");
		//ilUtil::redirect($this->getReturnLocation("cancelDelete",
		//	"adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=editContentStyles"));

	}

	
	function setTabs()
	{
		echo "settings_setTabs";
	}
	
	function getAdminTabs(&$tabs_gui)
	{
		$this->getTabs($tabs_gui);
	}
		
	/**
	* get tabs
	* @access	public
	* @param	object	tabs gui object
	*/
	function getTabs(&$tabs_gui)
	{
		global $rbacsystem;

		if ($rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$tabs_gui->addTarget("basic_settings",
				$this->ctrl->getLinkTarget($this, "editBasicSettings"), array("editBasicSettings","", "view"), "", "");

			$tabs_gui->addTarget("system_styles",
				$this->ctrl->getLinkTarget($this, "editSystemStyles"), "editSystemStyles", "", "");
				
			$tabs_gui->addTarget("content_styles",
				$this->ctrl->getLinkTarget($this, "editContentStyles"), "editContentStyles", "", "");
		}

		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), array("perm","info","owner"), 'ilpermissiongui');
		}
	}
} // END class.ilObjStyleSettingsGUI
?>
