<?php
include_once("classes/class.Object.php");

/**
* Class RoleTemplateObject
* @extends class.Object.php
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$ 
* @package ilias-core
* 
*/
class RoleTemplateObject extends Object
{
	/**
	* Constructor
	* @access public
	*/
	function RoleTemplateObject()
	{
		$this->Object();
	}

	//
	// Overwritten methods:
	//

	/**
	* create a role template object 
	* @access public
	*/
	function createObject()
	{
		// Creates a child object
		global $tplContent, $rbacsystem;

		if ($rbacsystem->checkAccess("write",$_GET["obj_id"],$_GET["parent"]))
		{
			$tplContent = new Template("object_form.html",true,true);
			$tplContent->setVariable($this->ilias->ini["layout"]);

			// Zur Ausgabe des 'Path' wird die Private-Methode createPath() aufgerufen 
			$tplContent->setVariable("TREEPATH",$this->getPath());
			$tplContent->setVariable("CMD","save");
			$tplContent->setVariable("OBJ_ID",$_GET["obj_id"]);
			$tplContent->setVariable("TPOS",$_GET["parent"]);
			$tplContent->setVariable("TYPE",$_POST["type"]);
		}
		else
		{
			$this->ilias->raiseError("No permission to write to role folder",$this->ilias->error_class->WARNING);
		}
	}

	/**
	* save new role
	* @access public
	**/
	function saveObject()
	{
		global $rbacadmin, $rbacsystem; 

		// CHECK ACCESS 'write' to role folder
		if ($rbacsystem->checkAccess('write',$_GET["obj_id"],$_GET["parent"]))
		{
			if ($rbacadmin->roleExists($_POST["Fobject"]["title"]))
			{
				$this->ilias->raiseError("Role Exists",$this->ilias->error_obj->WARNING);
			}
			$new_obj_id = createNewObject($_POST["type"],$_POST["Fobject"]);
			$rbacadmin->assignRoleToFolder($new_obj_id,$_GET["obj_id"],'n');
		}
		else
		{
			$this->ilias->raiseError("No permission to write to role folder",$this->ilias->error_obj->WARNING);
		}
		header("Location: content.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]);
		exit;
	}

	/**
	* delete template 
	* @access public
	**/
	function deleteObject()
	{
		global $rbacsystem, $rbacadmin;

		// check access write in role folder
		if ($rbacsystem->checkAccess('write',$_GET["obj_id"],$_GET["parent"]))
		{
			// is there any id to delete
			if ($_POST["id"])
			{
				foreach ($_POST["id"] as $id)
				{
					$rbacadmin->deleteTemplate($id);
				}
			}
			else
			{
				$this->ilias->raiseError("No check box checked, nothing happened ;-).",$this->ilias->error_obj->MESSAGE);
			}
		}
		else
		{
			$this->ilias->raiseError("No permission to write to role folder",$this->ilias->error_obj->MESSAGE);
		}
		header("Location: content_role.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]);
		exit;
	}

	/**
	* edit object
	* @access public
	* 
	**/
	function editObject()
	{
		global $tplContent, $rbacsystem;

		if ($rbacsystem->checkAccess('write',$_GET["parent"],$_GET["parent_parent"]))
		{
			$tplContent = new Template("object_form.html",true,true);
			$tplContent->setVariable($this->ilias->ini["layout"]);
			$tplContent->setVariable("TREEPATH",$this->getPath($_GET["parent"],$_GET["parent_parent"]));
			$tplContent->setVariable("CMD","update");
			$tplContent->setVariable("TPOS",$_GET["parent"]);

			$obj = getObject($_GET["obj_id"]);
			$tplContent->setVariable("TYPE",$obj["type"]);

			$tplContent->setVariable("OBJ_ID",$obj["obj_id"]);
			$tplContent->setVariable("OBJ_TITLE",$obj["title"]);
			$tplContent->setVariable("OBJ_DESC",$obj["desc"]);
		}
		else
		{
			$this->ilias->raiseError("No permission to edit the object",$this->ilias->error_obj->WARNING);
		}			
	}

	/**
	* update an object
	* @access public
	**/
	function updateObject()
	{
		global $rbacsystem;

		if ($rbacsystem->checkAccess('write',$_GET["parent"],$_GET["parent_parent"]))
		{
			updateObject($_GET["obj_id"],$_GET["type"],$_POST["Fobject"]);

			header("Location: content.php?obj_id=".$_GET["parent"]."&parent=".$_GET["parent_parent"]);
			exit;
		}
		else
		{
			$this->ilias->raiseError("No permission to edit the object",$this->ilias->error_obj->WARNING);
		}
	}

	/**
	* show permission templates of role
	* @access public
	**/
	function permObject() 
	{
		global $tree, $tplContent, $rbacssystem, $rbacadmin, $rbacreview, $rbacsystem;

		if ($rbacsystem->checkAccess('write',$_GET["parent"],$_GET["parent_parent"]))
		{
			$tplContent = new Template("role_perm.html",true,true);
			$tplContent->setVariable("TPOS",$_GET["parent"]);
			$tplContent->setVariable("OBJ_ID",$_GET["obj_id"]);
			$tplContent->setVariable("PAR",$_GET["parent_parent"]);
			$tplContent->setVariable($this->ilias->ini["layout"]);
			$tplContent->setVariable("TREEPATH",$this->getPath($_GET["parent"],$_GET["parent_parent"]));

			$role_data = getObject($_GET["obj_id"]);
			$tplContent->setVariable("MESSAGE_TOP","Permissions of template: ".$role_data["title"]);

			$obj_data = getTypeList();
			// BEGIN OBJECT_TYPES
			$tplContent->setCurrentBlock("OBJECT_TYPES");

			foreach ($obj_data as $data)
			{
				$tplContent->setVariable("OBJ_TYPES",$data["title"]);
				$tplContent->parseCurrentBlock();
			}
			// END OBJECT TYPES
			$all_ops = getOperationList();
			// BEGIN TABLE_DATA_OUTER
			foreach ($all_ops as $key => $operations)
			{
				// BEGIN CHECK_PERM
				$tplContent->setCurrentBlock("CHECK_PERM");

				foreach ($obj_data as $data)
				{
					if (in_array($operations["ops_id"],$rbacadmin->getOperationsOnType($data["obj_id"])))
					{
						$selected = $rbacadmin->getRolePermission($_GET["obj_id"],$data["title"],$_GET["parent"]);
						$checked = in_array($operations["ops_id"],$selected);
						// Es wird eine 2-dim Post Variable �bergeben: perm[rol_id][ops_id]
						$box = TUtil::formCheckBox($checked,"template_perm[".$data["title"]."][]",$operations["ops_id"]);
						$tplContent->setVariable("CHECK_PERMISSION",$box);
					}
					else
					{
						$tplContent->setVariable("CHECK_PERMISSION","");
					}
					$tplContent->parseCurrentBlock();
				}
				// END CHECK_PERM
				$tplContent->setCurrentBlock("TABLE_DATA_OUTER");
				$css_row = TUtil::switchColor($key,"row_high","row_low");
				$tplContent->setVariable("CSS_ROW",$css_row);
				$tplContent->setVariable("PERMISSION",$operations["operation"]);
				$tplContent->parseCurrentBlock();
			}

			$tplContent->setVariable("COL_ANZ",count($obj_data));
			$tplContent->setVariable("MESSAGE_TABLE","Change permissions");		
			// ADOPT PERMISSIONS
			$tplContent->setVariable("MESSAGE_MIDDLE","Adopt Permissions from Role Template");
			
			// BEGIN ADOPT_PERMISSIONS
			$tplContent->setCurrentBlock("ADOPT_PERMISSIONS");
			$parent_role_ids = $this->getParentRoleTemplateIds($_GET["parent"],$_GET["parent_parent"]);

			foreach ($parent_role_ids as $key => $par)
			{
				$radio = TUtil::formRadioButton(0,"adopt",$par["obj_id"]);
				$tplContent->setVariable("CSS_ROW_ADOPT",TUtil::switchColor($key,"row_high","row_low"));
				$tplContent->setVariable("CHECK_ADOPT",$radio);
				$tplContent->setVariable("TYPE",$par["type"] == 'role' ? 'Role' : 'Template');
				$tplContent->setVariable("ROLE_NAME",$par["title"]);
				$tplContent->parseCurrentBlock();
			}
			// END ADOPT_PERMISSIONS
		}
		else
		{
			$this->ilias->raiseError("No permission to write to role folder",$this->ilias->error_obj->WARNING);
		}
	}

	/**
	* save permission templates of role 
	* @access public
	**/
	function permSaveObject()
	{
		global $tree, $rbacadmin, $rbacsystem;

		$parent_obj_id = $this->getParentObjectId();

		if ($rbacsystem->checkAccess('edit permission',$_GET["parent"],$parent_obj_id))
		{
			// Alle Template Eintraege loeschen
			$rbacadmin->deleteRolePermission($_GET["obj_id"],$_GET["parent"]);

			foreach ($_POST["template_perm"] as $key => $ops_array)
			{
				// Setzen der neuen template permissions
				$rbacadmin->setRolePermission($_GET["obj_id"],$key,$ops_array,$_GET["parent"]);
			}
		}
		else
		{
			$this->ilias->raiseError("No permission to edit permissions",$this->ilias->error_obj->WARNING);
		}

		header("location:object.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&cmd=perm");
		exit;
	}

	/**
	* copy permissions from role or template
	* @access public
	**/
	function adoptPermSaveObject()
	{
		global $rbacadmin, $rbacsystem;

		$parent_obj_id = $this->getParentObjectId();

		if ($rbacsystem->checkAccess('edit permission',$_GET["parent"],$parent_obj_id))
		{
			$rbacadmin->deleteRolePermission($_GET["obj_id"],$_GET["parent"]);
			$parentRoles = $rbacadmin->getParentRoleIds($_GET["parent"]);
			$rbacadmin->copyRolePermission($_POST["adopt"],$parentRoles["$_POST[adopt]"]["parent"],$_GET["parent"],$_GET["obj_id"]);
		}
		else
		{
			$this->ilias->raiseError("No Permission to edit permissions",$this->ilias->error_obj->WARNING);
		}

		header("Location: object.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&cmd=perm");
		exit;
	}
} // END class.RoleTemplateObject
?>