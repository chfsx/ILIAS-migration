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
*  Contains Review functions of core Rbac.
*  This class offers the possibility to view the contents of the user <-> role (UR) relation and
*  the permission <-> role (PR) relation.
*  For example, from the UA relation the administrator should have the facility to view all user assigned to a given role.
*  
* 
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @package rbac
*/
class ilRbacReview
{
	/**
	* Constructor
	* @access	public
	*/
	function ilRbacReview()
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
	* Checks if a role already exists. Role title should be unique
	* @access	public
	* @param	string	role title
	* @param	integer	obj_id of role to exclude in the check. Commonly this is the current role you want to edit
	* @return	boolean	true if exists
	*/
	function roleExists($a_title,$a_id = 0)
	{
		if (empty($a_title))
		{
			$message = get_class($this)."::roleExists(): No title given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		
		$clause = ($a_id) ? " AND obj_id != '".$a_id."'" : "";
		
		$q = "SELECT DISTINCT obj_id FROM object_data ".
			 "WHERE title ='".addslashes($a_title)."' ".
			 "AND type IN('role','rolt')".
			 $clause;
		$r = $this->ilDB->query($q);

		if ($r->numRows() == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Get parent roles in a path. If last parameter is set 'true'
	* it delivers also all templates in the path
	* @access	public
	* @param	array	array with path_ids
	* @param	boolean	true for role templates (default: false)
	* @return	array	array with all parent roles (obj_ids)
	*/
	function getParentRoles($a_path,$a_templates = false)
	{
		if (!isset($a_path) or !is_array($a_path))
		{
			$message = get_class($this)."::getParentRoles(): No path given or wrong datatype!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$parentRoles = array();

		$child = $this->getAllRoleFolderIds();
		
		// CREATE IN() STATEMENT
		$in = " IN('";
		$in .= implode("','",$child);
		$in .= "') ";
		
		foreach ($a_path as $path)
		{
			//TODO: move this to tree class !!!!
			$q = "SELECT * FROM tree ".
				 "WHERE child ".$in.
				 "AND parent = '".$path."'";
			$r = $this->ilDB->query($q);

			while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$roles = $this->getRoleListByObject($row->child,$a_templates);

				foreach ($roles as $role)
				{
					$id = $role["obj_id"];
					// TODO: need a parent here?
					$role["parent"] = $row->child;
					$parentRoles[$id] = $role;
				}
			}
		}

		return $parentRoles;
	}

	/**
	* get an array of parent role ids of all parent roles, if last parameter is set true
	* you get also all parent templates
	* @access	private
	* @param	integer		ref_id of an object which is end node
	* @param	boolean		true for role templates (default: false)
	* @return	array       array(role_ids => role_data)
	*/
	function getParentRoleIds($a_endnode_id,$a_templates = false)
	{
		global $tree;

		if (!isset($a_endnode_id))
		{
			$message = get_class($this)."::getParentRoleIds(): No node_id (ref_id) given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
	
		$pathIds  = $tree->getPathId($a_endnode_id);

		// add system folder since it may not in the path
		$pathIds[0] = SYSTEM_FOLDER_ID;

		return $this->getParentRoles($pathIds,$a_templates);
	}

	/**
	* Returns a list of roles in an container
	* @access	public
	* @param	integer	ref_id
	* @param	boolean	if true fetch template roles too
	* @return	array	set ids
	*/
	function getRoleListByObject($a_ref_id,$a_templates = false)
	{
		if (!isset($a_ref_id) or !isset($a_templates))
		{
			$message = get_class($this)."::getRoleListByObject(): Missing parameter!".
					   "ref_id: ".$a_ref_id.
					   "tpl_flag: ".$a_templates;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$role_list = array();

		$where = $this->setTemplateFilter($a_templates);
	
		$q = "SELECT * FROM object_data ".
			 "JOIN rbac_fa ".$where.
			 "AND object_data.obj_id = rbac_fa.rol_id ".
			 "AND rbac_fa.parent = '".$a_ref_id."'";
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$role_list[] = fetchObjectData($row);
		}
		
		$role_list = $this->setRoleType($role_list);
		
		return $role_list;
	}
	
	/**
	* Returns a list of all assignable roles
	* @access	public
	* @param	boolean	if true fetch template roles too
	* @return	array	set ids
	*/
	function getAssignableRoles($a_templates = false)
	{
		$role_list = array();

		$where = $this->setTemplateFilter($a_templates);

		$q = "SELECT DISTINCT * FROM object_data ".
			 "JOIN rbac_fa ".$where.
			 "AND object_data.obj_id = rbac_fa.rol_id ".
			 "AND rbac_fa.assign = 'y'";
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$role_list[] = fetchObjectData($row);
		}
		
		$role_list = $this->setRoleType($role_list);
		
		return $role_list;
	}
	
	/**
	* get roles and templates or only roles; returns string for where clause
	* @access	private
	* @param	boolean	true: with templates
	* @return	string	where clause
	*/
	function setTemplateFilter($a_templates)
	{
		if ($a_templates === true)
		{
			 $where = "WHERE object_data.type IN ('role','rolt') ";		
		}
		else
		{
			$where = "WHERE object_data.type = 'role' ";
		}
		
		return $where;
	}

	/**
	* computes role type in role list array:
	* global: roles in ROLE_FOLDER_ID
	* local: assignable roles in other role folders
	* linked: roles with stoppped inheritance
	* template: role templates
	* 
	* @access	private
	* @param	array	role list
	* @return	array	role list with additional entry for role_type
	*/
	function setRoleType($a_role_list)
	{
		foreach ($a_role_list as $key => $val)
		{
			// determine role type
			if ($val["type"] == "rolt")
			{
				$a_role_list[$key]["role_type"] = "template";
			}
			else
			{
				if ($val["assign"] == "y")
				{
					if ($val["parent"] == ROLE_FOLDER_ID)
					{
						$a_role_list[$key]["role_type"] = "global";
					}
					else
					{
						$a_role_list[$key]["role_type"] = "local";
					}
				}
				else
				{
					$a_role_list[$key]["role_type"] = "linked";
				}
			}
		}
		
		return $a_role_list;
	}
	
	/**
	* get all assigned users to a given role
	* @access	public
	* @param	integer	role_id
	* @return	array	all users (id) assigned to role
	*/
	function assignedUsers($a_rol_id)
	{
		if (!isset($a_rol_id))
		{
			$message = get_class($this)."::assignedUsers(): No role_id given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

	    $usr_arr = array();
	   
		$q = "SELECT usr_id FROM rbac_ua WHERE rol_id='".$a_rol_id."'";
		$r = $this->ilDB->query($q);

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
		if (!isset($a_usr_id))
		{
			$message = get_class($this)."::assignedRoles(): No user_id given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$role_arr = array();
		
		$q = "SELECT rol_id FROM rbac_ua WHERE usr_id = '".$a_usr_id."'";
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$role_arr[] = $row->rol_id;
		}

		if (!count($role_arr))
		{
			$message = get_class($this)."::assignedRoles(): No assigned roles found or user doesn't exists!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		return $role_arr;
	}

	/**
	* Check if its possible to assign users
	* @access	public
	* @param	integer	object id of role
	* @param	integer	ref_id of object in question
	* @return	boolean 
	*/
	function isAssignable($a_rol_id, $a_ref_id)
	{
		// exclude system role from rbac
		if ($a_rol_id == SYSTEM_ROLE_ID)
		{
			return true;
		}

		if (!isset($a_rol_id) or !isset($a_ref_id))
		{
			$message = get_class($this)."::isAssignable(): Missing parameter!".
					   " role_id: ".$a_rol_id." ,ref_id: ".$a_ref_id;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		
		$q = "SELECT * FROM rbac_fa ".
			 "WHERE rol_id = '".$a_rol_id."' ".
			 "AND parent = '".$a_ref_id."'";
		$row = $this->ilDB->getRow($q);

		return $row->assign == 'y' ? true : false;
	}

	/**
	* returns an array of role folder ids assigned to a role. A role with stopped inheritance
	* may be assigned to more than one rolefolder.
	* To get only the original location of a role, set the second parameter to true
	*
	* @access	public
	* @param	integer		role id
	* @param	boolean		get only rolefolders where role is assignable (true) 
	* @return	array		reference IDs of role folders
	*/
	function getFoldersAssignedToRole($a_rol_id, $a_assignable = false)
	{
		if (!isset($a_rol_id))
		{
			$message = get_class($this)."::getFoldersAssignedToRole(): No role_id given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		
		if ($a_assignable)
		{
			$where = " AND assign ='y'";
		}

		$q = "SELECT DISTINCT parent FROM rbac_fa ".
			 "WHERE rol_id = '".$a_rol_id."'".$where;
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$folders[] = $row->parent;
		}

		return $folders ? $folders : array();
	}

	/**
	* get all roles of a role folder including linked local roles that are created due to stopped inheritance
	* returns an array with role ids
	* @access	public
	* @param	integer		ref_id of object
	* @param	boolean		if false only get true local roles
	* @return	array		Array with rol_ids
	*/
	function getRolesOfRoleFolder($a_ref_id,$a_nonassignable = true)
	{
		if (!isset($a_ref_id))
		{
			$message = get_class($this)."::getRolesifRoleFolder(): No ref_id given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		
		if ($a_nonassignable === false)
		{
			$and = " AND assign='y'";
		}

		$q = "SELECT rol_id FROM rbac_fa ".
			 "WHERE parent = '".$a_ref_id."'".
			 $and;
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$rol_id[] = $row->rol_id;
		}

		return $rol_id ? $rol_id : array();
	}
	
	/**
	* get only 'global' roles (all assignalbe roles from main rolefolder with ROLE_FOLDER_ID)
	* @access	public
	* @return	array		Array with rol_ids
	*/
	function getGlobalRoles()
	{
		return $this->getRolesOfRoleFolder(ROLE_FOLDER_ID,false);
	}

	/**
	* get all role folder ids
	* @access	public
	* @return	array
	*/
	function getAllRoleFolderIds()
	{
		$parent = array();
		
		$q = "SELECT DISTINCT parent FROM rbac_fa";
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$parent[] = $row->parent;
		}

		return $parent;
	}

	/**
	* returns the data of a role folder assigned to an object
	* @access	public
	* @param	integer		ref_id of object with a rolefolder object under it
	* @return	array		empty array if rolefolder not found
	*/
	function getRoleFolderOfObject($a_ref_id)
	{
		global $tree;
		
		if (!isset($a_ref_id))
		{
			$message = get_class($this)."::getRoleFolderOfObject(): No ref_id given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$childs = $tree->getChildsByType($a_ref_id,"rolf");

		return $childs[0] ? $childs[0] : array();
	}

	/**
	* get all possible operations of a specific role
	* The ref_id of the role folder (parent object) is necessary to distinguish local roles
	* @access	public
	* @param	integer	role_id
	* @param	string	object type
	* @param	integer	role folder id
	* @return	array	array of operation_id
	*/
	function getOperationsOfRole($a_rol_id,$a_type,$a_parent = 0)
	{
		if (!isset($a_rol_id) or !isset($a_type))
		{
			$message = get_class($this)."::getOperationsOfRole(): Missing Parameter!".
					   "role_id: ".$a_rol_id.
					   "type: ".$a_type.
					   "parent_id: ".$a_parent;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$ops_arr = array();

		// if no rolefolder id is given, assume global role folder as target
		if ($a_parent == 0)
		{
			$a_parent = ROLE_FOLDER_ID;
		}
		
		$q = "SELECT ops_id FROM rbac_templates ".
			 "WHERE type ='".$a_type."' ".
			 "AND rol_id = '".$a_rol_id."' ".
			 "AND parent = '".$a_parent."'";
		$r  = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ops_arr[] = $row->ops_id;
		}

		return $ops_arr;
	}
	
	function getRoleOperationsOnObject($a_role_id,$a_ref_id)
	{
		$query = "SELECT * FROM rbac_pa ".
			"WHERE rol_id = '".$a_role_id."' ".
			"AND ref_id = '".$a_ref_id."'";

		$res = $this->ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ops = unserialize(stripslashes($row->ops_id));
		}

		return $ops ? $ops : array();
	}

	/**
	* all possible operations of a type
	* @access	public
	* @param	integer		object_ID of type
	* @return	array		valid operation_IDs
	*/
	function getOperationsOnType($a_typ_id)
	{
		if (!isset($a_typ_id))
		{
			$message = get_class($this)."::getOperationsOnType(): No type_id given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		$q = "SELECT * FROM rbac_ta WHERE typ_id = '".$a_typ_id."'";
		$r = $this->ilDB->query($q);

		while($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ops_id[] = $row->ops_id;
		}

		return $ops_id ? $ops_id : array();
	}

	/**
	* get all objects in which the inheritance of role with role_id was stopped
	* the function returns all reference ids of objects containing a role folder.
	* @access	public
	* @param	integer	role_id
	* @return	array	with ref_ids of objects
	*/
	function getObjectsWithStopedInheritance($a_rol_id)
	{
		$tree = new ilTree(ROOT_FOLDER_ID);

		if (!isset($a_rol_id))
		{
			$message = get_class($this)."::getObjectsWithStopedInheritance(): No role_id given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
			
		$all_rolf_ids = $this->getFoldersAssignedToRole($a_rol_id,false);

		foreach ($all_rolf_ids as $rolf_id)
		{
			$parent[] = $tree->getParentId($rolf_id);
		}

		return $parent ? $parent : array();
	}

	/**
	* checks if a rolefolder is set as deleted (negative tree_id)
	* @access	public
	* @param	integer	ref_id of rolefolder
	* @return	boolean	true if rolefolder is set as deleted
	*/
	function isDeleted($a_node_id)
	{
		$q = "SELECT tree FROM tree WHERE child ='".$a_node_id."'";
		$r = $this->ilDB->query($q);
		
		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);
		
		if (!$row)
		{
			$message = get_class($this)."::isDeleted(): Rolefolder with ref_id '".$a_node_id."' not found!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		// rolefolder is deleted
		if ($row->tree < 0)
		{
			return true;
		}
		
		return false;
	}
} // END class.ilRbacReview
?>
