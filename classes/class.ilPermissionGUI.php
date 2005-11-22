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
* Class ilPermissionGUI
* RBAC related output
*
* @author Sascha Hofmann <saschahofmann@gmx.de>
* @version $Id$
*
* @package ilias-core
*/
class ilPermissionGUI
{
	/**
	* Constructor
	* @access	public
	* @param	array	??
	* @param	integer	object id
	* @param	boolean	call be reference
	*/
	function ilPermissionGUI(&$a_gui_obj)
	{
		global $ilias, $objDefinition, $tpl, $tree, $ilCtrl, $ilErr, $lng;

		if (!isset($ilErr))
		{
			$ilErr = new ilErrorHandling();
			$ilErr->setErrorHandling(PEAR_ERROR_CALLBACK,array($ilErr,'errorHandler'));
		}
		else
		{
			$this->ilErr =& $ilErr;
		}

		$this->ilias =& $ilias;
		$this->objDefinition =& $objDefinition;
		$this->tree =& $tree;
		$this->tpl =& $tpl;
		$this->lng =& $lng;
		$this->ctrl =& $ilCtrl;

		$this->gui_obj =& $a_gui_obj;
		
		$this->roles = array();
	}
	

	function &executeCommand()
	{
		global $rbacsystem;

		// access to all functions in this class are only allowed if edit_permission is granted
		if (!$rbacsystem->checkAccess("edit_permission",$this->gui_obj->object->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt("permission_denied"),$ilErr->MESSAGE);
		}

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();
		$this->$cmd();

		return true;
	}

	/**
	* show permissions of current node
	*
	* @access	public
	*/
	function perm()
	{
		global $rbacsystem, $rbacreview;

		$this->getRolesData();

		//var_dump("<pre>",$this->roles,"</pre>");exit;		

		/////////////////////
		// START DATA OUTPUT
		/////////////////////
		$this->__initSubTabs("perm");

		$this->gui_obj->getTemplateFile("perm");

		// render filter form
	    $this->tpl->setCurrentBlock("filter");
	    $this->tpl->setVariable("FILTER_TXT_FILTER",$this->lng->txt('filter'));
	    $this->tpl->setVariable("SELECT_FILTER",$this->__buildRoleFilterSelect());
	    $this->tpl->setVariable("FILTER_ACTION",$this->ctrl->getFormAction($this)."&cmd=perm");
	    $this->tpl->setVariable("FILTER_NAME",'view');
	    $this->tpl->setVariable("FILTER_VALUE",$this->lng->txt('apply_filter'));
	    $this->tpl->parseCurrentBlock();

		$num_roles = count($this->roles);

		// don't display table if no role in list
		if ($num_roles < 1)
		{
			sendinfo($this->lng->txt("msg_no_roles_of_type"),false);
			return true;
		}
		
		$this->tpl->addBlockFile("PERM_PERMISSIONS", "permissions", "tpl.obj_perm_permissions.html");
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("permission_settings"));
		$this->tpl->setVariable("COLSPAN", $num_roles);
		$this->tpl->setVariable("FORMACTION",
			$this->gui_obj->getFormAction("permSave",$this->ctrl->getLinkTarget($this,"permSave")));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		//$this->tpl->setVariable("TXT_OPERATION", $this->lng->txt("operation"));
		//$this->tpl->setVariable("TXT_ROLES", $this->lng->txt("roles"));
		//$this->tpl->parseCurrentBlock();

		foreach ($this->roles as $role)
		{
			$tmp_role_folder = $rbacreview->getRoleFolderOfObject($this->gui_obj->object->getRefId());
			$tmp_local_roles = array();

			if ($tmp_role_folder)
			{
				$tmp_local_roles = $rbacreview->getRolesOfRoleFolder($tmp_role_folder["ref_id"]);
			}
				
			// Is it a real or linked lokal role
			if ($role['protected'] == false and in_array($role['obj_id'],$tmp_local_roles))
			{
				$role_folder_data = $rbacreview->getRoleFolderOfObject($_GET['ref_id']);
				$role_folder_id = $role_folder_data['ref_id'];


				$this->tpl->setCurrentBlock("rolelink_open");

				if ($this->ctrl->getTargetScript() != 'adm_object.php')
				{
					$up_path = defined('ILIAS_MODULE') ? "../" : "";
					$this->tpl->setVariable("LINK_ROLE_RULESET",$up_path.'role.php?cmd=perm&ref_id='.
											$role_folder_id.'&obj_id='.$role['obj_id']);

					#$this->ctrl->setParameterByClass('ilobjrolegui','obj_id',$role['obj_id']);
					#$this->tpl->setVariable("LINK_ROLE_RULESET",
					#						$this->ctrl->getLinkTargetByClass('ilobjrolegui','perm'));
				}
				else
				{
					$this->tpl->setVariable("LINK_ROLE_RULESET",'adm_object.php?cmd=perm&ref_id='.
											$role_folder_id.'&obj_id='.$role['obj_id']);
				}
				$this->tpl->setVariable("TXT_ROLE_RULESET",$this->lng->txt("edit_perm_ruleset"));
				$this->tpl->parseCurrentBlock();

				$this->tpl->touchBlock("rolelink_close");
			}

			$this->tpl->setCurrentBlock("role_infos");
			
			// display human readable role names for autogenerated roles
			include_once ('class.ilObjRole.php');
			$this->tpl->setVariable("ROLE_NAME",str_replace(" ","&nbsp;",ilObjRole::_getTranslation($role["title"])));
			
			// display role context
			if ($role["role_type"] == "global")
			{
				$this->tpl->setVariable("ROLE_CONTEXT_TYPE","global");
			}
			else
			{
				$rolf = $rbacreview->getFoldersAssignedToRole($role["obj_id"],true);
				$parent_node = $this->tree->getParentNodeData($rolf[0]);
				$this->tpl->setVariable("ROLE_CONTEXT_TYPE",$this->lng->txt("obj_".$parent_node["type"])."&nbsp;(#".$parent_node["obj_id"].")");
				$this->tpl->setVariable("ROLE_CONTEXT",$parent_node["title"]);
			}
			
			$this->tpl->parseCurrentBlock();
		}
		
		// create pointer to first role (need only the permission list -> TODO: change array structure)
		reset($this->roles);
		$first_role =& current($this->roles);
		
//var_dump("<pre>",$first_role,$perm_list,"</pre>");

// permission settings

		// general section
		$this->tpl->setCurrentBlock("perm_subtitle");
		$this->tpl->setVariable("TXT_PERM_CLASS",$this->lng->txt('perm_class_general'));
		$this->tpl->setVariable("TXT_PERM_CLASS_DESC",$this->lng->txt('perm_class_general_desc'));
		$this->tpl->setVariable("COLSPAN", $num_roles);
		$this->tpl->parseCurrentBlock();

		foreach ($this->roles as $role)
		{
			foreach ($role['permissions']['general'] as $perm)
			{
				$box = ilUtil::formCheckBox($perm['checked'],"perm[".$role["obj_id"]."][]",$perm["ops_id"],$role["protected"]);

				$this->tpl->setCurrentBlock("perm_item");
				$this->tpl->setVariable("PERM_CHECKBOX",$box);
				$this->tpl->setVariable("PERM_NAME",$perm['name']);
				$this->tpl->setVariable("PERM_LABEL",'perm_'.$role['obj_id'].'_'.$perm['ops_id']);
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("perm_table");
			$this->tpl->parseCurrentBlock();	
		}

		$this->tpl->setCurrentBlock("perm_settings");
		$this->tpl->parseCurrentBlock();		

		// object section
		if (count($first_role['permissions']['object'])) // check if object type has special operations
		{
			$this->tpl->setCurrentBlock("perm_subtitle");
			$this->tpl->setVariable("TXT_PERM_CLASS",$this->lng->txt('perm_class_object'));
			$this->tpl->setVariable("TXT_PERM_CLASS_DESC",$this->lng->txt('perm_class_object_desc'));
			$this->tpl->setVariable("COLSPAN", $num_roles);
			$this->tpl->parseCurrentBlock();
	
			foreach ($this->roles as $role)
			{
				foreach ($role['permissions']['object'] as $perm)
				{
					$box = ilUtil::formCheckBox($perm['checked'],"perm[".$role["obj_id"]."][]",$perm["ops_id"],$role["protected"]);
	
					$this->tpl->setCurrentBlock("perm_item");
					$this->tpl->setVariable("PERM_CHECKBOX",$box);
					$this->tpl->setVariable("PERM_NAME",$perm['name']);
					$this->tpl->setVariable("PERM_LABEL",'perm_'.$role['obj_id'].'_'.$perm['ops_id']);
					$this->tpl->parseCurrentBlock();
				}
	
				$this->tpl->setCurrentBlock("perm_table");
				$this->tpl->parseCurrentBlock();	
			}								
	
			$this->tpl->setCurrentBlock("perm_settings");
			$this->tpl->parseCurrentBlock();
		}

		// rbac section
		$this->tpl->setCurrentBlock("perm_subtitle");
		$this->tpl->setVariable("TXT_PERM_CLASS",$this->lng->txt('perm_class_rbac'));
		$this->tpl->setVariable("TXT_PERM_CLASS_DESC",$this->lng->txt('perm_class_rbac_desc'));
		$this->tpl->setVariable("COLSPAN", $num_roles);
		$this->tpl->parseCurrentBlock();

		foreach ($this->roles as $role)
		{
			foreach ($role['permissions']['rbac'] as $perm)
			{
				$box = ilUtil::formCheckBox($perm['checked'],"perm[".$role["obj_id"]."][]",$perm["ops_id"],$role["protected"]);

				$this->tpl->setCurrentBlock("perm_item");
				$this->tpl->setVariable("PERM_CHECKBOX",$box);
				$this->tpl->setVariable("PERM_NAME",$perm['name']);
				$this->tpl->setVariable("PERM_LABEL",'perm_'.$role['obj_id'].'_'.$perm['ops_id']);
				$this->tpl->parseCurrentBlock();
			}

			// use local policy flag
			if ($this->gui_obj->object->getType() != 'root')  // no local policy for root folder
			{
				if ($role['local_policy_allowed'])
				{
					$box = ilUtil::formCheckBox($role['local_policy_enabled'],'stop_inherit[]',$role['obj_id'],$role['protected']);
					$lang = "use local policy";
				}
				else
				{
					$box = '&nbsp;';
					$lang = "Local role";
				}
				
				$this->tpl->setCurrentBlock("perm_item");
				$this->tpl->setVariable("PERM_CHECKBOX",$box);
				$this->tpl->setVariable("PERM_NAME",$lang);
				$this->tpl->setVariable("PERM_LABEL",'stop_inherit_'.$role['obj_id']);
				$this->tpl->parseCurrentBlock();
			}
	
				$this->tpl->setCurrentBlock("perm_table");
				$this->tpl->parseCurrentBlock();	
		}

		$this->tpl->setCurrentBlock("perm_settings");
		$this->tpl->parseCurrentBlock();

		// create section
		if (count($first_role['permissions']['create'])) // check if object type has create operations
		{
			$this->tpl->setCurrentBlock("perm_subtitle");
			$this->tpl->setVariable("TXT_PERM_CLASS",$this->lng->txt('perm_class_create'));
			$this->tpl->setVariable("TXT_PERM_CLASS_DESC",$this->lng->txt('perm_class_create_desc'));
			$this->tpl->setVariable("COLSPAN", $num_roles);
			$this->tpl->parseCurrentBlock();
	
			foreach ($this->roles as $role)
			{
				foreach ($role['permissions']['create'] as $perm)
				{
					$box = ilUtil::formCheckBox($perm['checked'],"perm[".$role["obj_id"]."][]",$perm["ops_id"],$role["protected"]);
	
					$this->tpl->setCurrentBlock("perm_item");
					$this->tpl->setVariable("PERM_CHECKBOX",$box);
					$this->tpl->setVariable("PERM_NAME",$perm['name']);
					$this->tpl->setVariable("PERM_LABEL",'perm_'.$role['obj_id'].'_'.$perm['ops_id']);
					$this->tpl->parseCurrentBlock();
				}
	
				$this->tpl->setCurrentBlock("perm_table");
				$this->tpl->parseCurrentBlock();	
			}
	
			$this->tpl->setCurrentBlock("perm_settings");
			$this->tpl->parseCurrentBlock();
		}


		// ADD LOCAL ROLE
		
		// do not display this option for admin section and root node
		/*$object_types_exclude = array("adm","root","mail","objf","lngf","trac","taxf","auth", "assf",'seas');

		if (!in_array($this->gui_obj->object->getType(),$object_types_exclude) and $this->gui_obj->object->getRefId() != ROLE_FOLDER_ID)
		{
			$this->tpl->addBlockFile("PERM_ADD_ROLE", "add_local_roles", "tpl.obj_perm_add_role.html");

			// fill in saved values in case of error
			$data = array();
			$data["fields"] = array();
			$data["fields"]["title"] = $_SESSION["error_post_vars"]["Fobject"]["title"];
			$data["fields"]["desc"] = $_SESSION["error_post_vars"]["Fobject"]["desc"];

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);
			}

			$this->tpl->setVariable("FORMACTION_LR",$this->gui_obj->getFormAction("addRole", $this->ctrl->getLinkTarget($this, "addRole")));
			$this->tpl->setVariable("TXT_HEADER", $this->lng->txt("you_may_add_local_roles"));
			$this->tpl->setVariable("TXT_ADD_ROLE", $this->lng->txt("role_add_local"));
			$this->tpl->setVariable("TARGET", $this->gui_obj->getTargetFrame("addRole"));
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
			$this->tpl->parseCurrentBlock();
		}

		// PARSE BLOCKFILE
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORMACTION",
			$this->gui_obj->getFormAction("permSave",$this->ctrl->getLinkTarget($this,"permSave")));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("COL_ANZ",$colspan);
		$this->tpl->parseCurrentBlock();*/
		
		$this->tpl->setVariable("COLSPAN", $num_roles);
	}


	/**
	* save permissions
	*
	* @access	public
	*/
	function permSave()
	{
		global $rbacreview, $rbacadmin;

		// first save the new permission settings for all roles
		$rbacadmin->revokePermission($this->gui_obj->object->getRefId());

		if (is_array($_POST["perm"]))
		{
			foreach ($_POST["perm"] as $key => $new_role_perms) // $key enthaelt die aktuelle Role_Id
			{
				$rbacadmin->grantPermission($key,$new_role_perms,$this->gui_obj->object->getRefId());
			}
		}

		// update object data entry (to update last modification date)
		$this->gui_obj->object->update();

		// Wenn die Vererbung der Rollen Templates unterbrochen werden soll,
		// muss folgendes geschehen:
		// - existiert kein RoleFolder, wird er angelegt und die Rechte aus den Permission Templates ausgelesen
		// - existiert die Rolle im aktuellen RoleFolder werden die Permission Templates dieser Rolle angezeigt
		// - existiert die Rolle nicht im aktuellen RoleFolder wird sie dort angelegt
		//   und das Permission Template an den Wert des nihst hher gelegenen Permission Templates angepasst

		// get rolefolder data if a rolefolder already exists
		$rolf_data = $rbacreview->getRoleFolderOfObject($this->gui_obj->object->getRefId());
		$rolf_id = $rolf_data["child"];
		
		$stop_inherit_roles = $_POST["stop_inherit"] ? $_POST["stop_inherit"] : array();

		if ($stop_inherit_roles)
		{
			// rolefolder does not exist, so create one
			if (empty($rolf_id))
			{
				// create a local role folder
				$rfoldObj = $this->gui_obj->object->createRoleFolder();

				// set rolf_id again from new rolefolder object
				$rolf_id = $rfoldObj->getRefId();
			}

			$roles_of_folder = $rbacreview->getRolesOfRoleFolder($rolf_id);
			
			foreach ($stop_inherit_roles as $stop_inherit)
			{
				// create role entries for roles with stopped inheritance
				if (!in_array($stop_inherit,$roles_of_folder))
				{
					$parentRoles = $rbacreview->getParentRoleIds($rolf_id);
					$rbacadmin->copyRolePermission($stop_inherit,$parentRoles[$stop_inherit]["parent"],
												   $rolf_id,$stop_inherit);
					$rbacadmin->assignRoleToFolder($stop_inherit,$rolf_id,'n');
				}
			}// END FOREACH
		}// END STOP INHERIT
		
		if ($rolf_id)
		{
			// get roles where inheritance is stopped was cancelled
			$linked_roles = $rbacreview->getLinkedRolesOfRoleFolder($rolf_id);
			$linked_roles_to_remove = array_diff($linked_roles,$stop_inherit_roles);
				
			// remove roles where stopped inheritance is cancelled and purge rolefolder if empty
			foreach ($linked_roles_to_remove as $role_id)
			{
				$role_obj =& $this->ilias->obj_factory->getInstanceByObjId($role_id);
				$role_obj->setParent($rolf_id);
				$role_obj->delete();
				unset($role_obj);
			}
		}
		
		sendinfo($this->lng->txt("saved_successfully"),true);
		
		$this->ctrl->redirect($this,'perm');
	}



	/**
	* adds a local role
	* This method is only called when choose the option 'you may add local roles'. This option
	* is displayed in the permission settings dialogue for an object
	* TODO: this will be changed
	* @access	public
	*/
	function addRole()
	{
		global $rbacadmin, $rbacreview, $rbacsystem;

		// first check if role title is unique
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

		// if the current object is no role folder, create one
		if ($this->gui_obj->object->getType() != "rolf")
		{
			$rolf_data = $rbacreview->getRoleFolderOfObject($this->gui_obj->object->getRefId());

			// is there already a rolefolder?
			if (!($rolf_id = $rolf_data["child"]))
			{
				// can the current object contain a rolefolder?
				$subobjects = $this->objDefinition->getSubObjects($this->gui_obj->object->getType());

				if (!isset($subobjects["rolf"]))
				{
					$this->ilias->raiseError($this->lng->txt("msg_no_rolf_allowed1")." '".$this->gui_obj->object->getTitle()."' ".
											$this->lng->txt("msg_no_rolf_allowed2"),$this->ilias->error_obj->WARNING);
				}

				// create a rolefolder
				$rolfObj = $this->gui_obj->object->createRoleFolder();
				$rolf_id = $rolfObj->getRefId();
			}
		}
		else
		{
			// Current object is already a rolefolder. To create the role we take its reference id
			$rolf_id = $this->gui_obj->object->getRefId();
		}

		// create role
		if ($this->gui_obj->object->getType() == "rolf")
		{
			$roleObj = $this->gui_obj->object->createRole($_POST["Fobject"]["title"],$_POST["Fobject"]["desc"]);
		}
		else
		{
			$rfoldObj = $this->ilias->obj_factory->getInstanceByRefId($rolf_id);
			$roleObj = $rfoldObj->createRole($_POST["Fobject"]["title"],$_POST["Fobject"]["desc"]);
		}

		sendInfo($this->lng->txt("role_added"),true);
		
		// in administration jump to deault perm settings screen
		if ($this->ctrl->getTargetScript() != "repository.php")
		{
			$this->ctrl->setParameter($this,"obj_id",$roleObj->getId());
			$this->ctrl->setParameter($this,"ref_id",$rolf_id);
			$this->ctrl->redirect($this,'perm');
		}

		$this->ctrl->redirect($this,'perm');
	}

	function &__initTableGUI()
	{
		include_once "./classes/class.ilTableGUI.php";

		return new ilTableGUI(0,false);
	}
	
	/**
	 * standard implementation for tables
	 * use 'from' variable use different initial setting of table 
	 * 
	 */
	function __setTableGUIBasicData(&$tbl,&$result_set,$a_from = "")
	{
		switch ($a_from)
		{
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
		$tbl->setLimit($_GET["limit"]);
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		$tbl->setData($result_set);
	}
	

	function __buildRoleFilterSelect()
	{
		$action[1] = $this->lng->txt('all_roles');
		$action[2] = $this->lng->txt('all_global_roles');
		$action[3] = $this->lng->txt('all_local_roles');
		$action[4] = $this->lng->txt('linked_local_roles');
		$action[5] = $this->lng->txt('local_roles_this_object_only');
		
		return ilUtil::formSelect($_SESSION['perm_filtered_roles'],"filter",$action,false,true);
	}
	
	function __filterRoles($a_roles,$a_filter)
	{
		global $rbacreview;

		switch ($a_filter)
		{
			case 1:	// all roles
				return $a_roles;
				break;
			
			case 2:	// all global roles
				$arr_global_roles = $rbacreview->getGlobalRoles();
				$arr_remove_roles = array_diff(array_keys($a_roles),$arr_global_roles);

				foreach ($arr_remove_roles as $role_id)
				{
					unset($a_roles[$role_id]);
				}
				
				return $a_roles;
				break;			

			case 3:	// all local roles
				$arr_global_roles = $rbacreview->getGlobalRoles();

				foreach ($arr_global_roles as $role_id)
				{
					unset($a_roles[$role_id]);
				}
				
				return $a_roles;
				break;
				
			case 4:	// all roles
				return $a_roles;
				break;
				
			case 5:	// local role only at this position
				
				$role_folder = $rbacreview->getRoleFolderOfObject($this->gui_obj->object->getRefId());
		
				if (!$role_folder)
				{
					return array();
				}
				
				$arr_local_roles = $rbacreview->getRolesOfRoleFolder($role_folder["ref_id"]);
				$arr_remove_roles = array_diff(array_keys($a_roles),$arr_local_roles);

				foreach ($arr_remove_roles as $role_id)
				{
					unset($a_roles[$role_id]);
				}

				return $a_roles;
				break;
		}

		return $a_roles;
	}

	// show owner sub tab
	function owner()
	{
		global $ilObjDataCache,$ilUser;

		$this->__initSubTabs("owner");

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.obj_owner.html');

		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("USERNAME",ilObjUser::_lookupLogin($this->gui_obj->object->getOwner()));
		$this->tpl->setVariable("TBL_TITLE_IMG",ilUtil::getImagePath('icon_usr.gif'));
		$this->tpl->setVariable("TBL_TITLE_IMG_ALT",$this->lng->txt('owner'));
		$this->tpl->setVariable("TBL_TITLE",$this->lng->txt('info_owner_of_object'));
		$this->tpl->setVariable("BTN_CHOWN",$this->lng->txt('change_owner'));
		$this->tpl->setVariable("TXT_USERNAME",$this->lng->txt('username'));
		$this->tpl->setVariable("CHOWN_WARNING",$this->lng->txt('chown_warning'));
	}
	
	function changeOwner()
	{
		global $rbacsystem,$ilErr,$ilObjDataCache;

		if(!$user_id = ilObjUser::_lookupId($_POST['owner']))
		{
			sendInfo($this->lng->txt('user_not_known'));
			$this->owner();
			return true;
		}

		$this->gui_obj->object->setOwner($user_id);
		$this->gui_obj->object->updateOwner();
		$ilObjDataCache->deleteCachedEntry($this->gui_obj->object->getId());
		sendInfo($this->lng->txt('owner_updated'),true);

		if (!$rbacsystem->checkAccess("edit_permission",$this->gui_obj->object->getRefId()))
		{
			$this->ctrl->redirect($this);
			return true;
		}

		$this->ctrl->redirect($this,'owner');
		return true;

	}
	
	// init permission query feature
	function info()
	{
		$this->__initSubTabs("info");

		include_once('classes/class.ilObjectStatusGUI.php');
		
		$ilInfo = new ilObjectStatusGUI($this->gui_obj->object);
		
		$this->tpl->setVariable("ADM_CONTENT",$ilInfo->getHTML());
	}
	
	// init sub tabs
	function __initSubTabs($a_cmd)
	{
		$perm = ($a_cmd == 'perm') ? true : false;
		$info = ($a_cmd == 'info') ? true : false;
		$owner = ($a_cmd == 'owner') ? true : false;

		include_once('classes/class.ilTabsGUI.php');

		$sub_tab_gui = new ilTabsGUI();
		$sub_tab_gui->setSubTabs();
		$sub_tab_gui->addTarget("permission_settings", $this->ctrl->getLinkTarget($this, "perm"),
			"", "", "", $perm);
		$sub_tab_gui->addTarget("info_status_info", $this->ctrl->getLinkTarget($this, "info"),
			"", "", "", $info);
		$sub_tab_gui->addTarget("owner", $this->ctrl->getLinkTarget($this, "owner"),
			"", "", "", $owner);
		$this->tpl->setVariable("SUB_TABS", $sub_tab_gui->getHTML());		
	}
	
	function getRolesData()
	{
		global $rbacsystem, $rbacreview;

		// first get all roles in
		$roles = $rbacreview->getParentRoleIds($this->gui_obj->object->getRefId());

		// filter roles
		$_SESSION['perm_filtered_roles'] = isset($_POST['filter']) ? $_POST['filter'] : $_SESSION['perm_filtered_roles'];

		// set default filter (all roles) if no filter is set
		if ($_SESSION['perm_filtered_roles'] == 0)
        {
        	$_SESSION['perm_filtered_roles'] = 1;
        }
        
  		// remove filtered roles from array
      	$roles = $this->__filterRoles($roles,$_SESSION["perm_filtered_roles"]);


		// determine status of each role (local role, changed policy, protected)
		$role_folder = $rbacreview->getRoleFolderOfObject($this->gui_obj->object->getRefId());
		
		$local_roles = array();

		if (!empty($role_folder))
		{
			$local_roles = $rbacreview->getRolesOfRoleFolder($role_folder["ref_id"]);
		}

		foreach ($roles as $key => $role)
		{
			// exclude system admin role from list
			if ($role["obj_id"] == SYSTEM_ROLE_ID)
			{
				unset($roles[$key]);
				continue;
			}
			
			$this->roles[$role['obj_id']] = $role;

			if (!in_array($role["obj_id"],$local_roles))
			{
				$this->roles[$role['obj_id']]['keep_protected'] = $keep_protected = $rbacreview->isProtected($role['parent'],$role['obj_id']);
				$this->roles[$role['obj_id']]['local_policy_enabled'] = false;
				$this->roles[$role['obj_id']]['local_policy_allowed'] = true;
			}
			else
			{
				// no checkbox for local roles
				if ($rbacreview->isAssignable($role["obj_id"],$role_folder["ref_id"]))
				{
					$this->roles[$role['obj_id']]['local_policy_allowed'] = false;
				}
				else
				{
					$this->roles[$role['obj_id']]['local_policy_enabled'] = true;
					$this->roles[$role['obj_id']]['local_policy_allowed'] = true;
				}
			}

			// compute permission settings for each role
			$grouped_ops = groupOperationsByClass(getOperationList($this->gui_obj->object->getType()));

			foreach ($grouped_ops as $ops_group => $ops_data)
			{
				foreach ($ops_data as $key => $operation)
				{
					$grouped_ops[$ops_group][$key]['checked'] = $rbacsystem->checkPermission($this->gui_obj->object->getRefId(), $role['obj_id'], $operation['name']);
	
						// Es wird eine 2-dim Post Variable bergeben: perm[rol_id][ops_id]
						//$box = ilUtil::formCheckBox($checked,"perm[".$role["obj_id"]."][]",$ops_id,$role["protected"]);
						//$opdata["values"][] = $box;
				}
			}
			
			$this->roles[$role['obj_id']]['permissions'] = $grouped_ops;
			unset($grouped_ops);
		}
	}
} // END class.ilPermissionGUI
?>
