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
* @package rbac
*/
class ilRbacAdmin
{
	/**
	* ilias object
	* @var		object	ilias
	* @access	public
	*/
	var $ilias;

	/**
	* Constructor
	* @access	public
	*/
	function ilRbacAdmin()
	{
		global $ilias;

		$this->ilias =& $ilias;
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
		global $log;

		if (!isset($a_usr_id))
		{
			$message = get_class($this)."::removeUser(): No usr_id given!";
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$q = "DELETE FROM rbac_ua WHERE usr_id='".$a_usr_id."'";
		$this->ilias->db->query($q);
		
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
		if (!isset($a_rol_id) or !isset($a_ref_id))
		{
			$message = get_class($this)."::deleteRole(): Missing parameter! role_id: ".$a_rol_id." ref_id of role folder: ".$a_ref_id;
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		// TODO: check assigned users before deletion
		// This is done in ilObjRole. Should be better moved to this place?
		
		// delete user assignements
		$q = "DELETE FROM rbac_ua ".
			 "WHERE rol_id = '".$a_rol_id ."'";
		$this->ilias->db->query($q);
		
		// delete permission assignments
		$q = "DELETE FROM rbac_pa ".
			 "WHERE rol_id = '".$a_rol_id."'";
		$this->ilias->db->query($q);
		
		//delete rbac_templates and rbac_fa
		$this->deleteLocalRole($a_rol_id,$a_ref_id);
		
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
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$q = "DELETE FROM rbac_templates ".
			 "WHERE rol_id = '".$a_obj_id ."'";
		$this->ilias->db->query($q);

		$q = "DELETE FROM rbac_fa ".
			 "WHERE rol_id = '".$a_obj_id ."'";
		$this->ilias->db->query($q);

		return true;
	}

	/**
	* Deletes a local role and entries in rbac_fa and rbac_templates
	* @access	public
	* @param	integer	object_id of role
	* @param	integer	ref_id of role folder
	* @return	boolean true on success
	*/
	function deleteLocalRole($a_rol_id,$a_ref_id)
	{
		if (!isset($a_rol_id) or !isset($a_ref_id))
		{
			$message = get_class($this)."::deleteLocalRole(): Missing parameter! role_id: ".$a_rol_id." ref_id of role folder: ".$a_ref_id;
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}
		
		$q = "DELETE FROM rbac_fa ".
			 "WHERE rol_id = '".$a_rol_id."' ".
			 "AND parent = '".$a_ref_id."'";
		$this->ilias->db->query($q);

		$q = "DELETE FROM rbac_templates ".
			 "WHERE rol_id = '".$a_rol_id."' ".
			 "AND parent = '".$a_ref_id."'";
		$this->ilias->db->query($q);

		return true;
	}


	/**
	* Assigns an user to a role. Update of table rbac_ua
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
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}
		
		if ($a_default)
		{
			$a_default = "y";
		}
		else
		{
			$a_default = "n";
		}

		$q = "REPLACE INTO rbac_ua ".
			 "VALUES ('".$a_usr_id."','".$a_rol_id."','".$a_default."')";
		$res = $this->ilias->db->query($q);

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
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$q = "DELETE FROM rbac_ua ".
			 "WHERE usr_id='".$a_usr_id."' ".
			 "AND rol_id='".$a_rol_id."'";
		$this->ilias->db->query($q);
		
		return true;
	}

	/**
	* Update of the default role of a user
	* @access	public
	* @param	integer	object id of role
	* @param	integer	user id
	* @return	boolean true if role was changed
	*/
	function updateDefaultRole($a_rol_id,$a_usr_id)
	{
		global $log;

		if (!isset($a_rol_id) or !isset($a_usr_id))
		{
			$message = get_class($this)."::updateDefaultRole(): Missing parameter! role_id: ".$a_rol_id." usr_id: ".$a_usr_id;
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$current_default_role = $this->getDefaultRole($a_usr_id);
		
		if ($current_default_role != $a_rol_id)
		{
			$this->deassignUser($current_default_role,$a_usr_id);	
			return $this->assignUser($a_rol_id,$a_usr_id,true);
		}
		
		return false;
	}

	/**
	* get Default role
	* @access	public
	* @param	integer		user id
	* @return	boolean
	*/
	function getDefaultRole($a_usr_id)
	{
		global $log;

		if (!isset($a_usr_id))
		{
			$message = get_class($this)."::getDefaultRole(): No usr_id given!";
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$q = "SELECT * FROM rbac_ua ".
			 "WHERE usr_id = '".$a_usr_id."' ".
			 "AND default_role = 'y'";
		$row = $this->ilias->db->getRow($q);

		return $row->rol_id;
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
			$this->ilias->raiseError(get_class($this)."::grantPermission(): Missing parameter! ".
							"role_id: ".$a_rol_id." ref_id: ".$a_ref_id." operations: ",$this->ilias->error_obj->WARNING);
		}

		if (!is_array($a_ops))
		{
			$this->ilias->raiseError(get_class($this)."::grantPermission(): Wrong datatype for operations!",
									 $this->ilias->error_obj->WARNING);
		}

		// Serialization des ops_id Arrays
		$ops_ids = addslashes(serialize($a_ops));

		$q = "INSERT INTO rbac_pa (rol_id,ops_id,obj_id) ".
			 "VALUES ".
			 "('".$a_rol_id."','".$ops_ids."','".$a_ref_id."')";
		$this->ilias->db->query($q);

		return true;
	}

	/**
	* Revokes permissions of object. Update of table rbac_pa.
	*  Revokes all permission for all roles for that object (with this reference).
	*  When a role_id is given this applies only to that role
	* @access	public
	* @param	integer	reference id of object where permissions should be revoked
	* @param	integer	role_id (optional: if you want to revoke permissions of object only for a specific role)
	* @return	boolean
	*/
	function revokePermission($a_ref_id,$a_rol_id = 0)
	{
		if (!isset($a_ref_id))
		{
			$message = get_class($this)."::revokePermission(): Missing parameter! ref_id: ".$a_ref_id;
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
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
			 "WHERE obj_id = '".$a_ref_id."' ".
			 $and1;
		$this->ilias->db->query($q);

		return true;
	}


	/**
	* Copies template permissions of one role to another.
	*  It's also possible to copy template permissions from/to RoleTemplateObject
	* @access	public
	* @param	integer		role_id source
	* @param	integer		parent_id source
	* @param	integer		role_id destination
	* @param	integer		parent_id destination
	* @return	boolean 
	*/
	function copyRolePermission($a_source_id,$a_source_parent,$a_dest_parent,$a_dest_id)
	{
		if (!isset($a_source_id) or !isset($a_source_parent) or !isset($a_dest_id) or !isset($a_dest_parent))
		{
			$message = get_class($this)."::copyRolePermission(): Missing parameter! source_id: ".$a_source_id.
					   " source_parent_id: ".$a_source_parent.
					   " dest_id : ".$a_dest_id.
					   " dest_parent_id: ".$a_dest_parent;
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		//$a_dest_id = $a_dest_id ? $a_dest_id : $a_source_id;

		$q = "SELECT * FROM rbac_templates ".
			 "WHERE rol_id = '".$a_source_id."' ".
			 "AND parent = '".$a_source_parent."'";
		$r = $this->ilias->db->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$q = "INSERT INTO rbac_templates ".
				 "VALUES ".
				 "('".$a_dest_id."','".$row->type."','".$row->ops_id."','".$a_dest_parent."')";
			$this->ilias->db->query($q);
		}

		return true;
	}
	
	/**
	* Deletes a template. Update of table rbac_templates.
	* @access	public
	* @param	integer		object id of role
	* @param	integer		ref_id of role folder
	* @return	boolean
	*/
	function deleteRolePermission($a_rol_id,$a_ref_id)
	{
		if (!isset($a_rol_id) or !isset($a_ref_id))
		{
			$message = get_class($this)."::deleteRolePermission(): Missing parameter! role_id: ".$a_rol_id." ref_id: ".$a_ref_id;
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$q = "DELETE FROM rbac_templates ".
			 "WHERE rol_id = '".$a_rol_id."' ".
			 "AND parent = '".$a_ref_id."'";
		$this->ilias->db->query($q);

		return true;
	}
	
	/**
	* Inserts template permissions in rbac_templates. 
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
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		if (!is_string($a_type) or empty($a_type))
		{
			$message = get_class($this)."::setRolePermission(): a_type is no string or empty!";
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		if (!is_array($a_ops) or empty($a_ops))
		{
			$message = get_class($this)."::setRolePermission(): a_ops is no array or empty!";
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}
		
		foreach ($a_ops as $op)
		{
			$q = "INSERT INTO rbac_templates ".
				 "VALUES ".
				 "('".$a_rol_id."','".$a_type."','".$op."','".$a_ref_id."')";
			$this->ilias->db->query($q);
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
		global $log;

		if (!isset($a_rol_id) or !isset($a_parent) or func_num_args() != 3)
		{
			$message = get_class($this)."::assignRoleToFolder(): Missing Parameter!".
					   " role_id: ".$a_rol_id.
					   " parent_id: ".$a_parent.
					   " assign: ".$a_assign;
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}
		
		// if a wrong value is passed, always set assign to "n"
		if ($a_assign != "y")
		{
			$a_assign = "n";
		}

		$q = "INSERT INTO rbac_fa (rol_id,parent,assign) ".
			 "VALUES ('".$a_rol_id."','".$a_parent."','".$a_assign."')";
		$this->ilias->db->query($q);

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
		global $log;

		if (!isset($a_type_id) or !isset($a_ops_id))
		{
			$message = get_class($this)."::assignOperationToObject(): Missing parameter!".
					   "type_id: ".$a_type_id.
					   "ops_id: ".$a_ops_id;
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$q = "INSERT INTO rbac_ta ".
			 "VALUES('".$a_type_id."','".$a_ops_id."')";
		$this->ilias->db->query($q);

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
		global $log;

		if (!isset($a_type_id) or !isset($a_ops_id))
		{
			$message = get_class($this)."::deassignPermissionFromObject(): Missing parameter!".
					   "type_id: ".$a_type_id.
					   "ops_id: ".$a_ops_id;
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$q = "DELETE FROM rbac_ta ".
			 "WHERE typ_id = '".$a_type_id."' ".
			 "AND ops_id = '".$a_ops_id."'";
		$this->ilias->db->query($q);
	
		return true;
	}
} // END class.ilRbacAdmin
?>
