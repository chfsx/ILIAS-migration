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
* Class ilObjRoleTemplateGUI
*
* @author Stefan Meyer <smeyer@databay.de>
* $Id$Id: class.ilObjRoleTemplateGUI.php,v 1.31 2004/01/21 16:56:38 shofmann Exp $
* 
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "class.ilObjectGUI.php";

class ilObjRoleTemplateGUI extends ilObjectGUI
{
	/**
	* ILIAS3 object type abbreviation
	* @var		string
	* @access	public
	*/
	var $type;

	/**
	* rolefolder ref_id where role is assigned to
	* @var		string
	* @access	public
	*/
	var $rolf_ref_id;
	/**
	* Constructor
	*
	* @access	public
	*/
	function ilObjRoleTemplateGUI($a_data,$a_id,$a_call_by_reference)
	{
		$this->type = "rolt";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference);
		$this->rolf_ref_id =& $this->ref_id;
	}
	
	/**
	* create new role definition template
	*
	* @access	public
	*/
	function createObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("create_rolt", $this->rolf_ref_id))
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

			$this->getTemplateFile("edit",$this->type);

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
																	   $this->rolf_ref_id."&new_type=".$this->type));
			$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($this->type."_new"));
			$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt($this->type."_add"));
			$this->tpl->setVariable("CMD_SUBMIT", "save");
			$this->tpl->setVariable("TARGET", $this->getTargetFrame("save"));
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		}
	}


	/**
	* save a new role template object
	*
	* @access	public
	*/
	function saveObject()
	{
		global $rbacsystem,$rbacadmin, $rbacreview;

		// CHECK ACCESS 'write' to role folder
		// TODO: check for create role permission should be better
		if (!$rbacsystem->checkAccess("create_rolt",$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_create_rolt"),$this->ilias->error_obj->WARNING);
		}

		// check required fields
		if (empty($_POST["Fobject"]["title"]))
		{
			$this->ilias->raiseError($this->lng->txt("fill_out_all_required_fields"),$this->ilias->error_obj->MESSAGE);
		}

		// check if rolt title is unique
		if ($rbacreview->roleExists($_POST["Fobject"]["title"]))
		{
			$this->ilias->raiseError($this->lng->txt("msg_role_exists1")." '".ilUtil::stripSlashes($_POST["Fobject"]["title"])."' ".
									 $this->lng->txt("msg_role_exists2"),$this->ilias->error_obj->MESSAGE);
		}

		// check if role title has il_ prefix
		if (substr($_POST["Fobject"]["title"],0,3) == "il_")
		{
			$this->ilias->raiseError($this->lng->txt("msg_role_reserved_prefix"),$this->ilias->error_obj->MESSAGE);
		}

		// create new rolt object
		include_once("./classes/class.ilObjRoleTemplate.php");
		$roltObj = new ilObjRoleTemplate();
		$roltObj->setTitle(ilUtil::stripSlashes($_POST["Fobject"]["title"]));
		$roltObj->setDescription(ilUtil::stripSlashes($_POST["Fobject"]["desc"]));
		$roltObj->create();
		$rbacadmin->assignRoleToFolder($roltObj->getId(), $this->rolf_ref_id,'n');
		
		sendInfo($this->lng->txt("rolt_added"),true);

		header("Location: adm_object.php?ref_id=".$this->rolf_ref_id);
		exit();
	}

	/**
	* display permissions
	* 
	* @access	public
	*/
	function permObject()
	{
		global $rbacadmin, $rbacreview, $rbacsystem,$objDefinition;

		if (!$rbacsystem->checkAccess('write',$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->WARNING);
			exit();
		}

		$to_filter = $objDefinition->getSubobjectsToFilter();

		// build array with all rbac object types
		$q = "SELECT ta.typ_id,obj.title,ops.ops_id,ops.operation FROM rbac_ta AS ta ".
			 "LEFT JOIN object_data AS obj ON obj.obj_id=ta.typ_id ".
			 "LEFT JOIN rbac_operations AS ops ON ops.ops_id=ta.ops_id";
		$r = $this->ilias->db->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			// FILTER SUBOJECTS OF adm OBJECT
			if(in_array($row->title,$to_filter))
			{
				continue;
			}
			$rbac_objects[$row->typ_id] = array("obj_id"	=> $row->typ_id,
											    "type"		=> $row->title
												);

			$rbac_operations[$row->typ_id][$row->ops_id] = array(
									   							"ops_id"	=> $row->ops_id,
									  							"title"		=> $row->operation,
																"name"		=> $this->lng->txt($row->title."_".$row->operation)
															   );
		}

		foreach ($rbac_objects as $key => $obj_data)
		{
			$rbac_objects[$key]["name"] = $this->lng->txt("obj_".$obj_data["type"]);
			$rbac_objects[$key]["ops"] = $rbac_operations[$key];
		}

		sort($rbac_objects);
			
		foreach ($rbac_objects as $key => $obj_data)
		{
			sort($rbac_objects[$key]["ops"]);
		}
		
		// sort by (translated) name of object type
		$rbac_objects = ilUtil::sortArray($rbac_objects,"name","asc");

		// BEGIN CHECK_PERM
		foreach ($rbac_objects as $key => $obj_data)
		{
			$arr_selected = $rbacreview->getOperationsOfRole($this->object->getId(), $obj_data["type"], $this->rolf_ref_id);
			$arr_checked = array_intersect($arr_selected,array_keys($rbac_operations[$obj_data["obj_id"]]));

			foreach ($rbac_operations[$obj_data["obj_id"]] as $operation)
			{
				$checked = in_array($operation["ops_id"],$arr_checked);
				$disabled = false;

				// Es wird eine 2-dim Post Variable �bergeben: perm[rol_id][ops_id]
				$box = ilUtil::formCheckBox($checked,"template_perm[".$obj_data["type"]."][]",$operation["ops_id"],$disabled);
				$output["perm"][$obj_data["obj_id"]][$operation["ops_id"]] = $box;
			}
		}
		// END CHECK_PERM

		$output["col_anz"] = count($rbac_objects);
		$output["txt_save"] = $this->lng->txt("save");

/************************************/
/*		adopt permissions form		*/
/************************************/

		$output["message_middle"] = $this->lng->txt("adopt_perm_from_template");

		// send message for system role
		if ($this->object->getId() == SYSTEM_ROLE_ID)
		{
			$output["adopt"] = array();
			sendinfo($this->lng->txt("msg_sysrole_not_editable"));
		}
		else
		{
			// BEGIN ADOPT_PERMISSIONS
			$parent_role_ids = $rbacreview->getParentRoleIds($this->rolf_ref_id,true);

			// sort output for correct color changing
			ksort($parent_role_ids);

			foreach ($parent_role_ids as $key => $par)
			{
				if ($par["obj_id"] != SYSTEM_ROLE_ID)
				{
					$radio = ilUtil::formRadioButton(0,"adopt",$par["obj_id"]);
					$output["adopt"][$key]["css_row_adopt"] = ilUtil::switchColor($key, "tblrow1", "tblrow2");
					$output["adopt"][$key]["check_adopt"] = $radio;
					$output["adopt"][$key]["type"] = ($par["type"] == 'role' ? 'Role' : 'Template');
					$output["adopt"][$key]["role_name"] = $par["title"];
				}
			}
	
			$output["formaction_adopt"] = "adm_object.php?cmd=adoptPermSave&ref_id=".$this->rolf_ref_id."&obj_id=".$this->object->getId();
			// END ADOPT_PERMISSIONS
		}

		$output["formaction"] = "adm_object.php?cmd=permSave&ref_id=".$this->rolf_ref_id."&obj_id=".$this->object->getId();

		$this->data = $output;


/************************************/
/*			generate output			*/
/************************************/

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.adm_perm_role.html");

		foreach ($rbac_objects as $obj_data)
		{
			// BEGIN object_operations
			$this->tpl->setCurrentBlock("object_operations");
	
			foreach ($obj_data["ops"] as $operation)
			{
				$css_row = ilUtil::switchColor($key, "tblrow1", "tblrow2");
				$this->tpl->setVariable("CSS_ROW",$css_row);
				$this->tpl->setVariable("PERMISSION",$operation["name"]);
				$this->tpl->setVariable("CHECK_PERMISSION",$this->data["perm"][$obj_data["obj_id"]][$operation["ops_id"]]);
				$this->tpl->parseCurrentBlock();
			} // END object_operations
			
			// BEGIN object_type
			$this->tpl->setCurrentBlock("object_type");
			$this->tpl->setVariable("TXT_OBJ_TYPE",$obj_data["name"]);
			$this->tpl->parseCurrentBlock();
			// END object_type
		}

		// BEGIN ADOPT PERMISSIONS
		foreach ($this->data["adopt"] as $key => $value)
		{
			$this->tpl->setCurrentBlock("ADOPT_PERM_ROW");
			$this->tpl->setVariable("CSS_ROW_ADOPT",$value["css_row_adopt"]);
			$this->tpl->setVariable("CHECK_ADOPT",$value["check_adopt"]);
			$this->tpl->setVariable("TYPE",$value["type"]);
			$this->tpl->setVariable("ROLE_NAME",$value["role_name"]);
			$this->tpl->parseCurrentBlock();
		}
			
		$this->tpl->setCurrentBlock("ADOPT_PERM_FORM");
		$this->tpl->setVariable("MESSAGE_MIDDLE",$this->data["message_middle"]);
		$this->tpl->setVariable("FORMACTION_ADOPT",$this->data["formaction_adopt"]);
		$this->tpl->parseCurrentBlock();
		// END ADOPT PERMISSIONS
	
		$this->tpl->setCurrentBlock("tblfooter_standard");
		$this->tpl->setVariable("COL_ANZ_PLUS",4);
		$this->tpl->setVariable("TXT_SAVE",$this->data["txt_save"]);
		$this->tpl->parseCurrentBlock();

		
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("TBL_TITLE_IMG",ilUtil::getImagePath("icon_".$this->object->getType()."_b.gif"));
		$this->tpl->setVariable("TBL_TITLE_IMG_ALT",$this->lng->txt($this->object->getType()));
		$this->tpl->setVariable("TBL_HELP_IMG",ilUtil::getImagePath("icon_help.gif"));
		$this->tpl->setVariable("TBL_HELP_LINK","tbl_help.php");
		$this->tpl->setVariable("TBL_HELP_IMG_ALT",$this->lng->txt("help"));
		$this->tpl->setVariable("TBL_TITLE",$this->object->getTitle());
			
		$this->tpl->setVariable("TXT_PERMISSION",$this->data["txt_permission"]);
		$this->tpl->setVariable("FORMACTION",$this->data["formaction"]);
		$this->tpl->parseCurrentBlock();



/*

		global $rbacadmin, $rbacreview, $rbacsystem;

		if (!$rbacsystem->checkAccess('write',$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->WARNING);
		}
		else
		{
			// get all object type definitions
			$obj_data = getObjectList("typ","title","ASC");

			// remove object types that are 'deactivated' (have no operation enabled)
			foreach ($obj_data as $key => $type)
			{
				$ops_arr = $rbacreview->getOperationsOnType($type["obj_id"]);

				if (empty($ops_arr))
				{
					unset($obj_data[$key]);				
				}
			}

			// BEGIN OBJECT_TYPES
			foreach ($obj_data as $data)
			{
				$output["obj_types"][] = $data["title"];
			}

			// END OBJECT TYPES
			$all_ops = getOperationList();

			// BEGIN TABLE_DATA_OUTER
			foreach ($all_ops as $key => $operations)
			{
				$operation_name = $operations["operation"];

				$num = 0;

				// BEGIN CHECK_PERM
				foreach ($obj_data as $data)
				{
					if (in_array($operations["ops_id"],$rbacreview->getOperationsOnType($data["obj_id"])))
					{
						$selected = $rbacreview->getOperationsOfRole($this->object->getId(), $data["title"], $this->rolf_ref_id);

						$checked = in_array($operations["ops_id"],$selected);
						// Es wird eine 2-dim Post Variable �bergeben: perm[rol_id][ops_id]
						$box = ilUtil::formCheckBox($checked,"template_perm[".$data["title"]."][]",$operations["ops_id"]);
						$output["perm"]["$operation_name"][] = $box;
					}
					else
					{
						$output["perm"]["$operation_name"][$num] = "";
					}

					$num++;
				}
				// END CHECK_PERM

				// color changing
				$css_row = ilUtil::switchColor($key, "tblrow1", "tblrow2");
				$output["perm"]["$operation_name"]["color"] = $css_row;
			}

			// END TABLE DATA OUTER
			$output["col_anz"] = count($obj_data);
			$output["txt_save"] = $this->lng->txt("save");
			$output["txt_permission"] = $this->lng->txt("permission");
			$output["txt_obj_type"] = $this->lng->txt("obj_type");
	
			// ADOPT PERMISSIONS
			$output["message_middle"] = $this->lng->txt("adopt_perm_from_template");

			// BEGIN ADOPT_PERMISSIONS
			$parent_role_ids = $rbacreview->getParentRoleIds($this->rolf_ref_id,true);

			// sort output for correct color changing
			ksort($parent_role_ids);

			foreach ($parent_role_ids as $key => $par)
			{
				$radio = ilUtil::formRadioButton(0,"adopt",$par["obj_id"]);
				$output["adopt"][$key]["css_row_adopt"] = ilUtil::switchColor($key, "tblrow1", "tblrow2");
				$output["adopt"][$key]["check_adopt"] = $radio;
				$output["adopt"][$key]["type"] = ($par["type"] == 'role' ? 'Role' : 'Template');
				$output["adopt"][$key]["role_name"] = $par["title"];
			}
			$output["formaction_adopt"] = "adm_object.php?cmd=adoptPermSave&obj_id="
				.$this->object->getId()."&ref_id=".$this->rolf_ref_id;
			// END ADOPT_PERMISSIONS

			$output["formaction"] = "adm_object.php?cmd=permSave&obj_id=".$this->object->getId()."&ref_id=".$this->rolf_ref_id;
			$output["message_top"] = "Permission Template of Role: ".$this->object->getTitle();
		}

		$this->data = $output;

		// generate output
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.adm_perm_role.html");

		// BEGIN BLOCK OBJECT TYPES
		$this->tpl->setCurrentBlock("OBJECT_TYPES");

		foreach ($this->data["obj_types"] as $type)
		{
			$this->tpl->setVariable("OBJ_TYPES",$type);
			$this->tpl->parseCurrentBlock();
		}
		// END BLOCK OBJECT TYPES

		// BEGIN TABLE DATA OUTER
		foreach ($this->data["perm"] as $name => $operations)
		{
			// BEGIN CHECK PERMISSION
			$this->tpl->setCurrentBlock("CHECK_PERM");

			for ($i = 0;$i < count($operations)-1;++$i)
			{
				$this->tpl->setVariable("CHECK_PERMISSION",$operations[$i]);
				$this->tpl->parseCurrentBlock();
			}
			// END CHECK PERMISSION

			$this->tpl->setCurrentBlock("TABLE_DATA_OUTER");
			$this->tpl->setVariable("CSS_ROW",$operations["color"]);
			$this->tpl->setVariable("PERMISSION",$name);
			$this->tpl->parseCurrentBlock();
		}
		// END TABLE DATA OUTER

		// BEGIN ADOPT PERMISSIONS
		foreach ($this->data["adopt"] as $key => $value)
		{
			$this->tpl->setCurrentBlock("ADOPT_PERMISSIONS");
			$this->tpl->setVariable("CSS_ROW_ADOPT",$value["css_row_adopt"]);
			$this->tpl->setVariable("CHECK_ADOPT",$value["check_adopt"]);
			$this->tpl->setVariable("TYPE",$value["type"]);
			$this->tpl->setVariable("ROLE_NAME",$value["role_name"]);
			$this->tpl->parseCurrentBlock();
		}
		// END ADOPT PERMISSIONS

		// PARSE BLOCKFILE
		$this->tpl->setCurrentBlock("adm_content");

		$this->tpl->setVariable("TBL_TITLE_IMG",ilUtil::getImagePath("icon_rolt_b.gif"));
		$this->tpl->setVariable("TBL_TITLE_IMG_ALT",$this->lng->txt($this->object->getType()));
		$this->tpl->setVariable("TBL_HELP_IMG",ilUtil::getImagePath("icon_help.gif"));
		$this->tpl->setVariable("TBL_HELP_LINK","tbl_help.php");
		$this->tpl->setVariable("TBL_HELP_IMG_ALT",$this->lng->txt("help"));
		$this->tpl->setVariable("TBL_TITLE",$this->object->getTitle());

		$this->tpl->setVariable("COL_ANZ",$this->data["col_anz"]);
		$this->tpl->setVariable("COL_ANZ_PLUS",$this->data["col_anz"]+1);
		$this->tpl->setVariable("TXT_SAVE",$this->data["txt_save"]);
		$this->tpl->setVariable("TXT_PERMISSION",$this->data["txt_permission"]);
		$this->tpl->setVariable("TXT_OBJ_TYPE",$this->data["txt_obj_type"]);
		$this->tpl->setVariable("MESSAGE_TABLE",$this->data["message_table"]);
		$this->tpl->setVariable("FORMACTION",$this->data["formaction"]);
		$this->tpl->setVariable("MESSAGE_MIDDLE",$this->data["message_middle"]);
		$this->tpl->setVariable("FORMACTION_ADOPT",$this->data["formaction_adopt"]);
		$this->tpl->parseCurrentBlock();
		* */
	}


	/**
	* save permission templates of role
	*
	* @access	public
	*/
	function permSaveObject()
	{
		global $rbacadmin, $rbacsystem, $rbacreview,$objDefinition;

		if (!$rbacsystem->checkAccess('write',$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->WARNING);
		}
		else
		{
			// Alle Template Eintraege loeschen
			$rbacadmin->deleteRolePermission($this->object->getId(), $this->rolf_ref_id);

			foreach ($_POST["template_perm"] as $key => $ops_array)
			{
				// Setzen der neuen template permissions
				$rbacadmin->setRolePermission($this->object->getId(), $key,$ops_array,$this->rolf_ref_id);
			}
		}
		
		// update object data entry (to update last modification date)
		$this->object->update();

		sendinfo($this->lng->txt("saved_successfully"),true);

		header("Location: adm_object.php?obj_id=".$this->object->getId()."&ref_id=".$this->rolf_ref_id."&cmd=perm");
		exit();
	}

	/**
	* adopting permission setting from other roles/role templates
	*
	* @access	public
	*/
	function adoptPermSaveObject()
	{
		global $rbacadmin, $rbacsystem, $rbacreview;

		if (!$rbacsystem->checkAccess('write',$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->WARNING);
		}
		elseif ($this->obj_id == $_POST["adopt"])
		{
			sendInfo($this->lng->txt("msg_perm_adopted_from_itself"),true);
		}
		else
		{
			$rbacadmin->deleteRolePermission($this->obj_id, $this->rolf_ref_id);
			$parentRoles = $rbacreview->getParentRoleIds($this->rolf_ref_id,true);
			$rbacadmin->copyRolePermission($_POST["adopt"],$parentRoles[$_POST["adopt"]]["parent"],
										   $this->rolf_ref_id,$this->obj_id);		
			// update object data entry (to update last modification date)
			$this->object->update();

			// send info
			$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($_POST["adopt"]);
			sendInfo($this->lng->txt("msg_perm_adopted_from1")." '".$obj_data->getTitle()."'.<br/>".$this->lng->txt("msg_perm_adopted_from2"),true);
		}

		header("Location: adm_object.php?ref_id=".$this->rolf_ref_id."&obj_id=".$this->obj_id."&cmd=perm");
		exit();
	}

	/**
	* update role template object
	*
	* @access	public
	*/
	function updateObject()
	{
		global $rbacsystem, $rbacadmin, $rbacreview;

		// check write access
		if (!$rbacsystem->checkAccess("write", $this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_modify_rolt"),$this->ilias->error_obj->WARNING);
		}
		
		// check required fields
		if (empty($_POST["Fobject"]["title"]))
		{
			$this->ilias->raiseError($this->lng->txt("fill_out_all_required_fields"),$this->ilias->error_obj->MESSAGE);
		}

		// check if role title is unique
		if ($rbacreview->roleExists($_POST["Fobject"]["title"],$this->object->getId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_role_exists1")." '".ilUtil::stripSlashes($_POST["Fobject"]["title"])."' ".
									 $this->lng->txt("msg_role_exists2"),$this->ilias->error_obj->MESSAGE);
		}

		// update
		$this->object->setTitle(ilUtil::stripSlashes($_POST["Fobject"]["title"]));
		$this->object->setDescription(ilUtil::stripSlashes($_POST["Fobject"]["desc"]));
		$this->object->update();
		
		sendInfo($this->lng->txt("saved_successfully"),true);

		header("Location: adm_object.php?ref_id=".$this->rolf_ref_id);
		exit();
	}
} // END class.ilObjRoleTemplateGUI
?>
