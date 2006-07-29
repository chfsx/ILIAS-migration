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
* Class ilRbacAdmin 
*  Core functions for role based access control.
*  Creation and maintenance of Relations.
*  The main relations of Rbac are user <-> role (UR) assignment relation and the permission <-> role (PR) assignment relation.
*  This class contains methods to 'create' and 'delete' instances of the (UR) relation e.g.: assignUser(), deassignUser()
*  Required methods for the PR relation are grantPermission(), revokePermission()
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
*
* @ingroup ServicesAccessControl
*/
class ilRbacAdmin
{
	/**
	* Constructor
	* @access	public
	*/
	function ilRbacAdmin()
	{
		global $ilDB,$ilErr,$ilias;

		// set db & error handler
		(isset($ilDB)) ? $this->ilDB =& $ilDB : $this->ilDB =& $ilias->db;
		
		if (!isset($ilErr))
		{
			$ilErr = new ilErrorHandling();
			$ilErr->setErrorHandling(PEAR_ERROR_CALLBACK,array($ilErr,'errorHandler'));
		}
		else
		{
			$this->ilErr =& $ilErr;
		}
	}

	/**
	* deletes a user from rbac_ua
	*  all user <-> role relations are deleted
	* @access	public
	* @param	integer	user_id
	* @return	boolean	true on success
	*/
	function removeUser($a_usr_id)
	{
		if (!isset($a_usr_id))
		{
			$message = get_class($this)."::removeUser(): No usr_id given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$q = "DELETE FROM rbac_ua WHERE usr_id='".$a_usr_id."'";
		$this->ilDB->query($q);
		
		return true;
	}

	/**
	* Deletes a role and deletes entries in object_data, rbac_pa, rbac_templates, rbac_ua, rbac_fa
	* @access	public
	* @param	integer		obj_id of role (role_id)
	* @param	integer		ref_id of role folder (ref_id)
	* @return	boolean     true on success
	*/
	function deleteRole($a_rol_id,$a_ref_id)
	{
		global $lng;

		if (!isset($a_rol_id) or !isset($a_ref_id))
		{
			$message = get_class($this)."::deleteRole(): Missing parameter! role_id: ".$a_rol_id." ref_id of role folder: ".$a_ref_id;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		// exclude system role from rbac
		if ($a_rol_id == SYSTEM_ROLE_ID)
		{
			$this->ilErr->raiseError($lng->txt("msg_sysrole_not_deletable"),$this->ilErr->MESSAGE);
		}

		// TODO: check assigned users before deletion
		// This is done in ilObjRole. Should be better moved to this place?
		
		// delete user assignements
		$q = "DELETE FROM rbac_ua ".
			 "WHERE rol_id = '".$a_rol_id ."'";
		$this->ilDB->query($q);
		
		// delete permission assignments
		$q = "DELETE FROM rbac_pa ".
			 "WHERE rol_id = '".$a_rol_id."'";
		$this->ilDB->query($q);
		
		//delete rbac_templates and rbac_fa
		$this->deleteLocalRole($a_rol_id);
		
		return true;
	}

	/**
	* Deletes a template from role folder and deletes all entries in rbac_templates, rbac_fa
 	* @access	public
	* @param	integer		object_id of role template
	* @return	boolean
	*/
	function deleteTemplate($a_obj_id)
	{
		if (!isset($a_obj_id))
		{
			$message = get_class($this)."::deleteTemplate(): No obj_id given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$q = "DELETE FROM rbac_templates ".
			 "WHERE rol_id = '".$a_obj_id ."'";
		$this->ilDB->query($q);

		$q = "DELETE FROM rbac_fa ".
			 "WHERE rol_id = '".$a_obj_id ."'";
		$this->ilDB->query($q);

		return true;
	}

	/**
	* Deletes a local role and entries in rbac_fa and rbac_templates
	* @access	public
	* @param	integer	object_id of role
	* @param	integer	ref_id of role folder (optional)
	* @return	boolean true on success
	*/
	function deleteLocalRole($a_rol_id,$a_ref_id = 0)
	{
		if (!isset($a_rol_id))
		{
			$message = get_class($this)."::deleteLocalRole(): Missing parameter! role_id: '".$a_rol_id."'";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		
		// exclude system role from rbac
		if ($a_rol_id == SYSTEM_ROLE_ID)
		{
			return true;
		}

		if ($a_ref_id != 0)
		{
			$clause = "AND parent = '".$a_ref_id."'";
		}
		
		$q = "DELETE FROM rbac_fa ".
			 "WHERE rol_id = '".$a_rol_id."' ".
			 $clause;

		$this->ilDB->query($q);

		$q = "DELETE FROM rbac_templates ".
			 "WHERE rol_id = '".$a_rol_id."' ".
			 $clause;
		$this->ilDB->query($q);

		return true;
	}


	/**
	* Assigns an user to a role. Update of table rbac_ua
	* TODO: remove deprecated 3rd parameter sometime
	* @access	public
	* @param	integer	object_id of role
	* @param	integer	object_id of user
	* @param	boolean	true means default role (optional
	* @return	boolean
	*/
	function assignUser($a_rol_id,$a_usr_id,$a_default = false)
	{
		if (!isset($a_rol_id) or !isset($a_usr_id))
		{
			$message = get_class($this)."::assignUser(): Missing parameter! role_id: ".$a_rol_id." usr_id: ".$a_usr_id;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		
		$q = "REPLACE INTO rbac_ua ".
			 "VALUES ('".$a_usr_id."','".$a_rol_id."')";
		$res = $this->ilDB->query($q);

		// Finally assign desktop items assigned to this role
		include_once './classes/class.ilRoleDesktopItem.php';

		$role_desk_item_obj =& new ilRoleDesktopItem($a_rol_id);
		
		if(is_object($tmp_user = ilObjectFactory::getInstanceByObjId($a_usr_id,false)))
		{
			foreach($role_desk_item_obj->getAll() as $item_data)
			{
				if(!$tmp_user->isDesktopItem($item_data['item_id'],$item_data['item_type']))
				{
					$tmp_user->addDesktopItem($item_data['item_id'],$item_data['item_type']);
				}
			}
		}
		return true;
	}

	/**
	* Deassigns a user from a role. Update of table rbac_ua
	* @access	public
	* @param	integer	object id of role
	* @param	integer	object id of user
	* @return	boolean	true on success
	*/
	function deassignUser($a_rol_id,$a_usr_id)
	{
		if (!isset($a_rol_id) or !isset($a_usr_id))
		{
			$message = get_class($this)."::deassignUser(): Missing parameter! role_id: ".$a_rol_id." usr_id: ".$a_usr_id;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$q = "DELETE FROM rbac_ua ".
			 "WHERE usr_id='".$a_usr_id."' ".
			 "AND rol_id='".$a_rol_id."'";
		$this->ilDB->query($q);
		
		return true;
	}

	/**
	* Grants a permission to an object and a specific role. Update of table rbac_pa
	* @access	public
	* @param	integer	object id of role
	* @param	array	array of operation ids
	* @param	integer	reference id of that object which is granted the permissions
	* @return	boolean
	*/
	function grantPermission($a_rol_id,$a_ops,$a_ref_id)
	{
		if (!isset($a_rol_id) or !isset($a_ops) or !isset($a_ref_id))
		{
			$this->ilErr->raiseError(get_class($this)."::grantPermission(): Missing parameter! ".
							"role_id: ".$a_rol_id." ref_id: ".$a_ref_id." operations: ",$this->ilErr->WARNING);
		}

		if (!is_array($a_ops))
		{
			$this->ilErr->raiseError(get_class($this)."::grantPermission(): Wrong datatype for operations!",
									 $this->ilErr->WARNING);
		}
		
		if (count($a_ops) == 0)
		{
			return false;
		}
		
		// exclude system role from rbac
		if ($a_rol_id == SYSTEM_ROLE_ID)
		{
			return true;
		}
		
		// convert all values to integer
		foreach ($a_ops as $key => $operation)
		{
			$a_ops[$key] = (int) $operation;
		}

		// Serialization des ops_id Arrays
		$ops_ids = addslashes(serialize($a_ops));

		$q = "REPLACE INTO rbac_pa (rol_id,ops_id,ref_id) ".
			 "VALUES ".
			 "('".$a_rol_id."','".$ops_ids."','".$a_ref_id."')";
		$this->ilDB->query($q);

		return true;
	}

	/**
	* Revokes permissions of an object of one role. Update of table rbac_pa.
	* Revokes all permission for all roles for that object (with this reference).
	* When a role_id is given this applies only to that role
	* @access	public
	* @param	integer	reference id of object where permissions should be revoked
	* @param	integer	role_id (optional: if you want to revoke permissions of object only for a specific role)
	* @return	boolean
	*/
	function revokePermission($a_ref_id,$a_rol_id = 0,$a_keep_protected = true)
	{
		global $rbacreview,$log;

		if (!isset($a_ref_id))
		{
			$message = get_class($this)."::revokePermission(): Missing parameter! ref_id: ".$a_ref_id;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
$log->write("ilRBACadmin::revokePermission(), 0");

		// bypass protected status of roles
		if ($a_keep_protected != true)
		{
			// exclude system role from rbac
			if ($a_rol_id == SYSTEM_ROLE_ID)
			{
				return true;
			}
	
			if ($a_rol_id)
			{
				$and1 = " AND rol_id = '".$a_rol_id."'";
			}
			else
			{
				$and1 = "";
			}
	
			// TODO: rename db_field from obj_id to ref_id and remove db-field set_id
			$q = "DELETE FROM rbac_pa ".
				 "WHERE ref_id = '".$a_ref_id."' ".
				 $and1;
			$this->ilDB->query($q);
	
			return true;
		}
		
		// consider protected status of roles
	
		// in any case, get all roles in scope first
		$roles_in_scope = $rbacreview->getParentRoleIds($a_ref_id);

		if (!$a_rol_id)
		{
$log->write("ilRBACadmin::revokePermission(), 1");

			$role_ids = array();
			
			foreach ($roles_in_scope as $role)
			{
				if ($role['protected'] == true)
				{
					continue;
				}
				
				$role_ids[] = $role['obj_id'];
			}
			
			// return if no role in array
			if (!$role_ids)
			{
				return true;
			}
			
			$q = "DELETE FROM rbac_pa ".
				 "WHERE rol_id IN (".implode(',',$role_ids).") ".
				 "AND ref_id ='".$a_ref_id."'";
			$this->ilDB->query($q);
		}
		else
		{
$log->write("ilRBACadmin::revokePermission(), 2");	
			// exclude system role from rbac
			if ($a_rol_id == SYSTEM_ROLE_ID)
			{
				return true;
			}
			
			// exclude protected permission settings from revoking
			if ($roles_in_scope[$a_rol_id]['protected'] == true)
			{
				return true;
			}

			$q = "DELETE FROM rbac_pa ".
				 "WHERE ref_id = '".$a_ref_id."' ".
				 "AND rol_id = '".$a_rol_id."'";
			$this->ilDB->query($q);
		}

		return true;
	}

	/**
	* Revokes permissions of a LIST of objects of ONE role. Update of table rbac_pa.
	* @access	public
	* @param	array	list of reference_ids to revoke permissions
	* @param	integer	role_id
	* @return	boolean
	*/
	function revokePermissionList($a_ref_ids,$a_rol_id)
	{
		if (!isset($a_ref_ids) or !is_array($a_ref_ids))
		{
			$message = get_class($this)."::revokePermissionList(): Missing parameter or parameter is not an array! object_list: ".$a_obj_ids;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		if (!isset($a_rol_id))
		{
			$message = get_class($this)."::revokePermissionList(): Missing parameter! rol_id: ".$a_rol_id;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		// exclude system role from rbac
		if ($a_rol_id == SYSTEM_ROLE_ID)
		{
			return true;
		}

		$ref_ids = implode(",",$a_ref_ids);

		// TODO: rename db_field from obj_id to ref_id and remove db-field set_id
		$q = "DELETE FROM rbac_pa ".
			 "WHERE ref_id IN (".$ref_ids.") ".
			 "AND rol_id = ".$a_rol_id;
		$this->ilDB->query($q);

		return true;
	}

	/**
	* Copies template permissions of one role to another.
	*  It's also possible to copy template permissions from/to RoleTemplateObject
	* @access	public
	* @param	integer		$a_source_id		role_id source
	* @param	integer		$a_source_parent	parent_id source
	* @param	integer		$a_dest_parent		parent_id destination
 	* @param	integer		$a_dest_id			role_id destination
	* @return	boolean 
	*/
	function copyRolePermission($a_source_id,$a_source_parent,$a_dest_parent,$a_dest_id,$a_consider_protected = true)
	{
		global $rbacreview;

		if (!isset($a_source_id) or !isset($a_source_parent) or !isset($a_dest_id) or !isset($a_dest_parent))
		{
			$message = get_class($this)."::copyRolePermission(): Missing parameter! source_id: ".$a_source_id.
					   " source_parent_id: ".$a_source_parent.
					   " dest_id : ".$a_dest_id.
					   " dest_parent_id: ".$a_dest_parent;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		
		// exclude system role from rbac
		if ($a_dest_id == SYSTEM_ROLE_ID)
		{
			return true;
		}

		$q = "SELECT * FROM rbac_templates ".
			 "WHERE rol_id = '".$a_source_id."' ".
			 "AND parent = '".$a_source_parent."'";
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$q = "INSERT INTO rbac_templates ".
				 "VALUES ".
				 "('".$a_dest_id."','".$row->type."','".$row->ops_id."','".$a_dest_parent."')";
			$this->ilDB->query($q);
		}
		
		// copy also protection status if applicable
		if ($a_consider_protected == true)
		{
			if ($rbacreview->isProtected($a_source_parent,$a_source_id))
			{
				$this->setProtected($a_dest_parent,$a_dest_id,'y');
			}
		}

		return true;
	}
	/**
	* Copies the intersection of the template permissions of two roles to a
        * third role.
        *
	* @access	public
	* @param	integer		$a_source1_id		role_id source
	* @param	integer		$a_source1_parent	parent_id source
	* @param	integer		$a_source2_id		role_id source
	* @param	integer		$a_source2_parent	parent_id source
 	* @param	integer		$a_dest_id			role_id destination
	* @param	integer		$a_dest_parent		parent_id destination
	* @return	boolean 
	*/
	function copyRolePermissionIntersection($a_source1_id,$a_source1_parent,$a_source2_id,$a_source2_parent,$a_dest_parent,$a_dest_id)
	{
		global $rbacreview;
		
		if (!isset($a_source1_id) or !isset($a_source1_parent) 
		or !isset($a_source2_id) or !isset($a_source2_parent) 
                or !isset($a_dest_id) or !isset($a_dest_parent))
		{
			$message = get_class($this)."::copyRolePermissionIntersection(): Missing parameter! source1_id: ".$a_source1_id.
					   " source1_parent: ".$a_source1_parent.
					   " source2_id: ".$a_source2_id.
					   " source2_parent: ".$a_source2_parent.
					   " dest_id: ".$a_dest_id.
					   " dest_parent_id: ".$a_dest_parent;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		
		// exclude system role from rbac
		if ($a_dest_id == SYSTEM_ROLE_ID)
		{
			return true;
		}
		
		if ($rbacreview->isProtected($a_source2_parent,$a_source2_id))
		{
			return true;
			//return $this->copyRolePermission($a_source2_id,$a_source2_parent,$a_dest_parent,$a_dest_id);
		}

		$q = "SELECT s1.type, s1.ops_id ".
                        "FROM rbac_templates AS s1, rbac_templates AS s2 ".
                        "WHERE s1.rol_id = '".$a_source1_id."' ".
                        "AND s1.parent = '".$a_source1_parent."' ".
                        "AND s2.rol_id = '".$a_source2_id."' ".
                        "AND s2.parent = '".$a_source2_parent."' ".
                        "AND s1.type = s2.type ".
                        "AND s1.ops_id = s2.ops_id";
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$q = "INSERT INTO rbac_templates ".
				 "VALUES ".
				 "('".$a_dest_id."','".$row->type."','".$row->ops_id."','".$a_dest_parent."')";
			$this->ilDB->query($q);
		}

		return true;
	}
	
	/**
	* Deletes all entries of a template. If an object type is given for third parameter only
	* the entries for that object type are deleted
	* Update of table rbac_templates.
	* @access	public
	* @param	integer		object id of role
	* @param	integer		ref_id of role folder
	* @param	string		object type (optional)
	* @return	boolean
	*/
	function deleteRolePermission($a_rol_id,$a_ref_id,$a_type = false)
	{
		if (!isset($a_rol_id) or !isset($a_ref_id))
		{
			$message = get_class($this)."::deleteRolePermission(): Missing parameter! role_id: ".$a_rol_id." ref_id: ".$a_ref_id;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		// exclude system role from rbac
		if ($a_rol_id == SYSTEM_ROLE_ID)
		{
			return true;
		}
		
		if ($a_type !== false)
		{
			$and_type = " AND type='".$a_type."'";
		}

		$q = "DELETE FROM rbac_templates ".
			 "WHERE rol_id = '".$a_rol_id."' ".
			 "AND parent = '".$a_ref_id."'".
			 $and_type;
		$this->ilDB->query($q);

		return true;
	}
	
	/**
	* Inserts template permissions in rbac_templates for an specific object type. 
	*  Update of table rbac_templates
	* @access	public
	* @param	integer		role_id
	* @param	string		object type
	* @param	array		operation_ids
	* @param	integer		ref_id of role folder object
	* @return	boolean
	*/
	function setRolePermission($a_rol_id,$a_type,$a_ops,$a_ref_id)
	{
		if (!isset($a_rol_id) or !isset($a_type) or !isset($a_ops) or !isset($a_ref_id))
		{
			$message = get_class($this)."::setRolePermission(): Missing parameter!".
					   " role_id: ".$a_rol_id.
					   " type: ".$a_type.
					   " operations: ".$a_ops.
					   " ref_id: ".$a_ref_id;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		if (!is_string($a_type) or empty($a_type))
		{
			$message = get_class($this)."::setRolePermission(): a_type is no string or empty!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		if (!is_array($a_ops) or empty($a_ops))
		{
			$message = get_class($this)."::setRolePermission(): a_ops is no array or empty!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		
		// exclude system role from rbac
		if ($a_rol_id == SYSTEM_ROLE_ID)
		{
			return true;
		}
		
		foreach ($a_ops as $op)
		{
			$q = "INSERT INTO rbac_templates ".
				 "VALUES ".
				 "('".$a_rol_id."','".$a_type."','".$op."','".$a_ref_id."')";
			$this->ilDB->query($q);
		}

		return true;
	}

	/**
	* Assigns a role to an role folder
	* A role folder is an object to store roles.
	* Every role is assigned to minimum one role folder
	* If the inheritance of a role is stopped, a new role template will created, and the role is assigned to
	* minimum two role folders. All roles with stopped inheritance need the flag '$a_assign = false'
	*
	* @access	public
	* @param	integer		object id of role
	* @param	integer		ref_id of role folder
	* @param	string		assignable('y','n'); default: 'y'
	* @return	boolean
	*/
	function assignRoleToFolder($a_rol_id,$a_parent,$a_assign = "y")
	{
		if (!isset($a_rol_id) or !isset($a_parent))
		{
			$message = get_class($this)."::assignRoleToFolder(): Missing Parameter!".
					   " role_id: ".$a_rol_id.
					   " parent_id: ".$a_parent.
					   " assign: ".$a_assign;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		
		// exclude system role from rbac
		if ($a_rol_id == SYSTEM_ROLE_ID)
		{
			return true;
		}
		
		// if a wrong value is passed, always set assign to "n"
		if ($a_assign != "y")
		{
			$a_assign = "n";
		}

		$q = "INSERT INTO rbac_fa (rol_id,parent,assign) ".
			 "VALUES ('".$a_rol_id."','".$a_parent."','".$a_assign."')";
		$this->ilDB->query($q);

		return true;
	}

	/**
	* Assign an existing operation to an object
	*  Update of rbac_ta.
	* @access	public
	* @param	integer	object type
	* @param	integer	operation_id
	* @return	boolean
	*/
	function assignOperationToObject($a_type_id,$a_ops_id)
	{
		if (!isset($a_type_id) or !isset($a_ops_id))
		{
			$message = get_class($this)."::assignOperationToObject(): Missing parameter!".
					   "type_id: ".$a_type_id.
					   "ops_id: ".$a_ops_id;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$q = "INSERT INTO rbac_ta ".
			 "VALUES('".$a_type_id."','".$a_ops_id."')";
		$this->ilDB->query($q);

		return true;
	}

	/**
	* Deassign an existing operation from an object 
	* Update of rbac_ta
	* @access	public
	* @param	integer	object type
	* @param	integer	operation_id
	* @return	boolean
	*/
	function deassignOperationFromObject($a_type_id,$a_ops_id)
	{
		if (!isset($a_type_id) or !isset($a_ops_id))
		{
			$message = get_class($this)."::deassignPermissionFromObject(): Missing parameter!".
					   "type_id: ".$a_type_id.
					   "ops_id: ".$a_ops_id;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$q = "DELETE FROM rbac_ta ".
			 "WHERE typ_id = '".$a_type_id."' ".
			 "AND ops_id = '".$a_ops_id."'";
		$this->ilDB->query($q);
	
		return true;
	}
	
	function setProtected($a_ref_id,$a_role_id,$a_value)
	{
		// ref_id not used yet. protected permission acts 'global' for each role, regardless of any broken inheritance before
		$q = "UPDATE rbac_fa ".
			 "SET protected = '".$a_value."' ".
			 //"WHERE parent = '".$a_ref_id."' ".
			 "WHERE rol_id = '".$a_role_id."'";
		$this->ilDB->query($q);
		
		return true;
	}
} // END class.ilRbacAdmin
?>
