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
* Class ilObjRoleFolder
*
* @author Stefan Meyer <smeyer@databay.de> 
* $Id$
* 
* @extends ilObject
* @package ilias-core
*/

require_once "class.ilObject.php";

class ilObjRoleFolder extends ilObject
{
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjRoleFolder($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "rolf";
		$this->ilObject($a_id,$a_call_by_reference);
	}
	
	function read($a_force_db = false)
	{
		parent::read();
		
		if ($this->getId() != ROLE_FOLDER_ID)
		{
			$this->setDescription($this->lng->txt("obj_".$this->getType()."_local_desc").$this->getTitle().$this->getDescription());
			$this->setTitle($this->lng->txt("obj_".$this->getType()."_local"));

		}
	}

	/**
	* copy all properties and subobjects of an rolefolder.
	* DISABLED
	* @access	public
	* @return	integer	new ref id
	*/
	function clone($a_parent_ref)
	{		
		// DISABLED
		// DO NOTHING ROLE FOLDERS AREN'T COPIED
		//	$new_ref_id = parent::clone($a_parent_ref);
		return false;

		global $rbacadmin;

		// always call parent clone function first!!
		$new_ref_id = parent::clone($a_parent_ref);
		
		// put here rolefolder specific stuff

		// ... and finally always return new reference ID!!
		return $new_ref_id;
	}

	/**
	* delete rolefolder and all related data	
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{		
		// always call parent delete function first!!
		if (!parent::delete())
		{
			return false;
		}

		// put here rolefolder specific stuff
		global $rbacreview;
		
		$roles = $rbacreview->getRolesOfRoleFolder($this->getRefId());
		
		// FIRST DELETE ALL LOCAL/BASE ROLES OF FOLDER
		foreach ($roles as $role_id)
		{
			$roleObj =& $this->ilias->obj_factory->getInstanceByObjId($role_id);
			$roleObj->setParent($this->getRefId());
			$roleObj->delete();
			unset($roleObj);
		}
		
		// always call parent delete function at the end!!
		return true;
	}

	/**
	* creates a local role in current rolefolder (this object)
	* 
	* @access	public
	* @param	string	title
	* @param	string	description
	* @return	object	role object
	*/
	function createRole($a_title,$a_desc)
	{
		global $rbacadmin, $rbacreview;
		
		// check if role title is unique
		if ($rbacreview->roleExists($a_title))
		{
			$this->ilias->raiseError($this->lng->txt("msg_role_exists1")." '".ilUtil::stripSlashes($a_title)."' ".
									 $this->lng->txt("msg_role_exists2"),$this->ilias->error_obj->MESSAGE);
		}		

		include_once ("classes/class.ilObjRole.php");
		$roleObj = new ilObjRole();
		$roleObj->setTitle($a_title);
		$roleObj->setDescription($a_desc);
		$roleObj->create();
			
		// ...and put the role into local role folder...
		$rbacadmin->assignRoleToFolder($roleObj->getId(),$this->getRefId(),"y");

		return $roleObj;
	}
	
	/**
	* checks if rolefolder contains any roles. if not the rolefolder is deleted
	* @access	public
	* @return	boolean	true if rolefolder is deleted
	*/
	function purge()
	{
		global $rbacreview, $rbacadmin, $tree;

		$local_roles = $rbacreview->getRolesOfRoleFolder($this->getRefId());
			
		if (count($local_roles) == 0)
		{
			$rbacadmin->revokePermission($this->getRefId());
			
			if ($tree_id = $this->isDeleted())
			{
				$deleted_tree = new ilTree ($tree_id,- (int) $tree_id);
				$deleted_tree->deleteTree($deleted_tree->getNodeData($this->getRefId()));				
			}
			else
			{
				$tree->deleteTree($tree->getNodeData($this->getRefId()));
			}

			$this->delete();
			
			return true;
		}
		
		return false;
	}
	
	/**
	* checks if role folder is in trash
	* @access	private
	* @return	integer	return negative tree if in trash, otherwise false
	*/
	function isDeleted()
	{
		$q = "SELECT tree FROM tree WHERE child='".$this->getRefId()."'";
		$row = $this->ilias->db->getRow($q);
		
		if ($row->tree < 0)
		{
			return $row->tree;
		}
		
		return false;
	}
} // END class.ilObjRoleFolder
?>
