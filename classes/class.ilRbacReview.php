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
* class ilRbacReview
* 
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @package rbac
*/
class ilRbacReview
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
	function ilRbacReview()
	{
	    global $ilias;
		
		$this->ilias =& $ilias;
	}

	/**
	* get all assigned users to a given role
	* @access	public
	* @param	integer	role_id
	* @return	array	all users (id) assigned to role
	*/
	function assignedUsers($a_rol_id)
	{
		global $log;

		if (!isset($a_rol_id))
		{
			$message = get_class($this)."::assignedUsers(): No role_id given!";
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

	    $usr_arr = array();
	   
		$q = "SELECT usr_id FROM rbac_ua WHERE rol_id='".$a_rol_id."'";
		$r = $this->ilias->db->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			array_push($usr_arr,$row["usr_id"]);
		}
		
		return $usr_arr;
	}
	
	/**
	* get all assigned roles to a given user
	* @access	public
	* @param	integer		usr_id
	* @return	array		all roles (id) the user have
	*/
	function assignedRoles($a_usr_id)
	{
		global $log;

		if (!isset($a_usr_id))
		{
			$message = get_class($this)."::assignedRoles(): No user_id given!";
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$role_arr = array();
		
		$q = "SELECT rol_id FROM rbac_ua WHERE usr_id = '".$a_usr_id."'";
		$r = $this->ilias->db->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$role_arr[] = $row->rol_id;
		}

		if (!count($role_arr))
		{
			$this->ilias->raiseError("No assigned roles found or user doesn't exists!",$this->ilias->error_obj->WARNING);
		}

		return $role_arr;
	}

	/**
	* get all enabled operations of a specific role
	* @access	public
	* @param	integer	role_id
	* @param	string	object type
	* @param	integer	role folder id
	* @return	array	array of operation_id
	*/
	function getOperationsOfRole($a_rol_id,$a_type,$a_parent = 0)
	{
		global $log;

		if (!isset($a_rol_id) or !isset($a_type) or func_num_args() != 3)
		{
			$message = get_class($this)."::getOperationsOfRole(): Missing Parameter!".
					   "role_id: ".$a_rol_id.
					   "type: ".$a_type.
					   "parent_id: ".$a_parent;
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$ops_arr = array();

		// TODO: what happens if $a_parent is empty???????
		
		$q = "SELECT ops_id FROM rbac_templates ".
			 "WHERE type ='".$a_type."' ".
			 "AND rol_id = '".$a_rol_id."' ".
			 "AND parent = '".$a_parent."'";
		$r  = $this->ilias->db->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ops_arr[] = $row->ops_id;
		}

		return $ops_arr;
	}

	function userOperationsOnObject($a_usr_id,$a_obj_id)
	{

	}

	/**
	* Assign an existing permission to an object 
	* @access	public
	* @param	integer	object type
	* @param	integer	operation_id
	* @return	boolean
	*/
	function assignPermissionToObject($a_type_id,$a_ops_id)
	{
		global $log;

		if (!isset($a_type_id) or !isset($a_ops_id))
		{
			$message = get_class($this)."::assignPermissionToObject(): Missing parameter!".
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
	* Deassign an existing permission from an object 
	* @access	public
	* @param	integer	object type
	* @param	integer	operation_id
	* @return	boolean
	*/
	function deassignPermissionFromObject($a_type_id,$a_ops_id)
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
	
	/**
	* TODO: function should be renamed
	* get all objects in which the inheritance was stopped
	* @access	public
	* @param	integer	role_id
	* @return	array
	*/
	function getObjectsWithStopedInheritance($a_rol_id)
	{
		global $log;
		
		if (!isset($a_rol_id))
		{
			$message = get_class($this)."::getObjectsWithStopedInheritance(): No role_id given!";
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}
			
		$q = "SELECT DISTINCT parent_obj FROM rbac_fa ".
			 "WHERE rol_id = '".$a_rol_id."'";
		$r = $this->ilias->db->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if($row->parent_obj != SYSTEM_FOLDER_ID)
			{
				$parent_obj[] = $row->parent_obj;
			}
		}

		return $parent_obj ? $parent_obj : array();
	}

	function rolePermissons($a_rol_id,$a_obj_id = 0)
	{

	}
	
	function userPermissions($a_usr_id)
	{

	}

	function getErrorMessage()
	{
		return $this->Error;
	}
	
	function createSession()
	{

	}

	function deleteSession()
	{

	}
	
	function addActiveRole()
	{

	}

	function dropActiveRole()
	{

	}

	function sessionRoles()
	{

	}
	
	function sessionPermissions()
	{
	
	}
	
	function roleOperationsOnObject($a_rol_id,$a_obj_id)
	{

	}
} // END class.RbacReview
?>
