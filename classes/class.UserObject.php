<?php
/**
* Class UserObject
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @extends Object
* @package ilias-core
*/
class UserObject extends Object
{
	/**
	* array of gender abbreviations
	* @var array
	* @access public
	*/
	var $gender;

	/**
	* Contructor
	* @access	public
	*/
	function UserObject()
	{
		global $lng;

		$this->Object();

		$this->gender = array(
							  'm'    => "salutation_m",
							  'f'    => "salutation_f"
							  );
	}

	/**
	* create user
	* @access	public
	*/
	function createObject()
	{
		global $tree,$tpl,$rbacsystem;

		$obj = getObject($_GET["obj_id"]);

		if ($rbacsystem->checkAccess('write',$_GET["obj_id"],$_GET["parent"]))
		{
			// gender selection
			$gender = TUtil::formSelect($Fuserdata["Gender"],"Fuserdata[Gender]",$this->gender);
			// role selection
			$rol = TUtil::getRoles();
			$role = TUtil::formSelectWoTranslation($Fuserdata["Role"],"Fuserdata[Role]",$rol);

			$data["fields"] = array();
			$data["fields"]["login"] = "";
			$data["fields"]["passwd"] = "";
			$data["fields"]["title"] = "";
			$data["fields"]["gender"] = $gender;
			$data["fields"]["firstname"] = "";
			$data["fields"]["lastname"] = "";
			$data["fields"]["email"] = "";
			$data["fields"]["default_role"] = $role;
			$data["title"] = $user->data["Title"];
			return $data;
		}
		else
		{
			$this->ilias->raiseError("No permission to write to user folder",$this->ilias->error_obj->WARNING);
		}
	}

	/**
	* save user data
	* @access	public
	*/
	function saveObject()
	{
		global $rbacsystem,$rbacadmin;
		
		$Fuserdata = $_POST["Fuserdata"];

		if ($rbacsystem->checkAccess('write',$_GET["obj_id"],$_GET["parent"]))
		{
			// create object
			$Fobject["title"] = User::buildFullName($Fuserdata["Title"],$Fuserdata["FirstName"],$Fuserdata["SurName"]);
			$Fobject["desc"] = $Fuserdata["Email"];
			$Fuserdata["Id"] = createNewObject("usr",$Fobject);

			// insert user data
			$rbacadmin->addUser($Fuserdata);
			$rbacadmin->assignUser($Fuserdata["Role"],$Fuserdata["Id"]);
		}
		else
		{
			$this->ilias->raiseError("No permission to write to user folder",$this->ilias->error_obj->WARNING);
		}
		return true;		
	}
	
	/**
	* delete user
	* @access	public
	*/
	function deleteObject()
	{
		global $rbacadmin,$rbacsystem;
		
		// CHECK ACCESS
		if ($rbacsystem->checkAccess('write',$_GET["obj_id"],$_GET["parent"]))
		{
			$rbacadmin->deleteUser($_POST["id"]);
		}
		else
		{
			$this->ilias->raiseError("No permission to delete user",$this->ilias->error_obj->WARNING);
		}
		
		header("Location: content_user.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]);
		exit;
	}
	
	/**
	* edit user data
	* @access	public
	*/
	function editObject()
	{
		global $tpl, $rbacsystem, $rbacreview, $lng;

		if ($rbacsystem->checkAccess('write',$_GET["parent"],$_GET["parent_parent"]) || $_GET["obj_id"] == $_SESSION["AccountId"])
		{
			// Userobjekt erzeugen
			$user = new User($_GET["obj_id"]);

			// gender selection
			$gender = TUtil::formSelect($Fuserdata["Gender"],"Fuserdata[Gender]",$this->gender);
			// role selection
			$rol = TUtil::getRoles();
			$role = TUtil::formSelectWoTranslation($Fuserdata["Role"],"Fuserdata[Role]",$rol);

			$data = array();

			$data["fields"] = array();
			$data["fields"]["login"] = $user->data["login"];
			$data["fields"]["passwd"] = "********";
			$data["fields"]["title"] = $user->data["title"];
			$data["fields"]["gender"] = $gender;
			$data["fields"]["firstname"] = $user->data["FirstName"];
			$data["fields"]["lastname"] = $user->data["SurName"];
			$data["fields"]["email"] = $user->data["Email"];
			$data["fields"]["default_role"] = $role;
			$data["title"] = $user->data["Title"];
			

			if ($_GET["obj_id"] == $_SESSION["AccountId"])
			{
				// BEGIN ACTIVE ROLE
				$assigned_roles = $rbacreview->assignedRoles($_GET["obj_id"]);
				
				foreach ($assigned_roles as $key => $role)
				{
					// BEGIN TABLE_ROLES
					$tpl->setCurrentBlock("TABLE_ROLES");
					$obj = getObject($role);
					$tpl->setVariable("CSS_ROW_ROLE",$key % 2 ? 'tblrow1' : 'tblrow2');
					$box = Tutil::formCheckBox(in_array($role,$_SESSION["RoleId"]),'active[]',$role);
					$tpl->setVariable("CHECK_ROLE",$box);
					$tpl->setVariable("ROLENAME",$obj["title"]);
					$tpl->parseCurrentBlock();
				}
				
				$tpl->setCurrentBlock("ACTIVE_ROLE");
				$tpl->setVariable("ACTIVE_ROLE_OBJ_ID",$_GET["obj_id"]);
				$tpl->setVariable("ACTIVE_ROLE_TPOS",$_GET["parent"]);
				$tpl->setVariable("ACTIVE_ROLE_PAR",$_GET["parent_parent"]);
				$tpl->parseCurrentBlock();
			}
			return $data;
		}
		else
		{
			$this->ilias->raiseError("No permission to edit user",$this->ilias->error_obj->WARNING);
		}
	}

	/**
	* update user data
	* TODO: The entry in object_data must be changed too!!
	* @access	public
	*/
	function updateObject()
	{
		global $rbacsystem,$rbacadmin;
		
		if ($rbacsystem->checkAccess('write',$_GET["parent"],$_GET["parent_parent"]) || $_GET["obj_id"] == $_SESSION["AccountId"])
		{
			$Fuserdata = $_POST["Fuserdata"];
			$rbacadmin->updateUser($Fuserdata);
			$rbacadmin->assignUser($Fuserdata["Role"],$_GET["obj_id"]);
			// TODO: Passwort muss gesondert abgefragt werden
		}
		else
		{
			$this->ilias->raiseError("No permission to delete user",$this->ilias->error_obj->WARNING);
		}
		
		header("Location: content_user.php?obj_id=".$_GET["parent"]."&parent=".SYSTEM_FOLDER_ID);
		exit;
	}
	
	/**
	* add active role in session
	* @access	public
	**/
	function activeRoleSaveObject()
	{
		if ($_GET["obj_id"] == $_SESSION["AccountId"])
		{
			if (!count($_POST["active"]))
			{
				$this->ilias->raiseError("You must leave one active role",$this->ilias->error_obj->WARNING);
			}

			$_SESSION["RoleId"] = $_POST["active"];
		}
		else
		{
			$this->ilias->raiseError("You can only change your own account",$this->ilias->error_obj->WARNING);
		}
		
		header("Location: object.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&parent_parent=".$_GET["parent_parent"]."&cmd=edit");
		exit;
	}
} //end class.UserObject
?>