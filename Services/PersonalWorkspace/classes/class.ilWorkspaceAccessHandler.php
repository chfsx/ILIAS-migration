<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Access handler for personal workspace
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id: class.ilPersonalDesktopGUI.php 26976 2010-12-16 13:24:38Z akill $
 */
class ilWorkspaceAccessHandler
{
	protected $tree; // [ilTree]

	public function __construct(ilTree $a_tree = null)
	{
		global $ilUser;
		
		if(!$a_tree)
		{
			include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";
			$a_tree = new ilWorkspaceTree($ilUser->getId());
		}
		$this->tree = $a_tree;
	}

	/**
	 * check access for an object
	 *
	 * @param	string		$a_permission
	 * @param	string		$a_cmd
	 * @param	int			$a_node_id
	 * @param	string		$a_type (optional)
	 * @return	bool
	 */
	public function checkAccess($a_permission, $a_cmd, $a_node_id, $a_type = "")
	{
		global $ilUser;

		return $this->checkAccessOfUser($this->tree, $ilUser->getId(),$a_permission, $a_cmd, $a_node_id, $a_type);
	}

	/**
	 * check access for an object
	 *
	 * @param	ilTree		$a_tree
	 * @param	integer		$a_user_id
	 * @param	string		$a_permission
	 * @param	string		$a_cmd
	 * @param	int			$a_node_id
	 * @param	string		$a_type (optional)
	 * @return	bool
	 */
	public function checkAccessOfUser(ilTree $a_tree, $a_user_id, $a_permission, $a_cmd, $a_node_id, $a_type = "")
	{
		// tree root is read-only
		if($a_permission == "write")
		{
			if($a_tree->readRootId() == $a_node_id)
			{
				return false;
			}
		}

		// workspace owner has all rights
		if($a_tree->getTreeId() == $a_user_id)
		{
			return true;
		}

		// get all objects with explicit permission
		$objects = $this->getPermissions($a_node_id);
		if($objects)
		{
			// check if given user is member of object or has role
			foreach($objects as $obj_id)
			{
				switch(ilObject::_lookupType($obj_id))
				{
					case "grp":
						// :TODO:
						break;

					case "crs":
						// :TODO:
						break;

					case "role":
						// :TODO:
						break;

					case "usr":
						// direct hit
						if($a_user_id == $obj_id)
						{
							return true;
						}
						break;
				}
			}
		}
		
		return false;
	}

	/**
	 * Set permissions after creating node/object
	 * 
	 * @param int $a_parent_node_id
	 * @param int $a_node_id
	 */
	public function setPermissions($a_parent_node_id, $a_node_id)
	{
		// nothing to do as owner has irrefutable rights to any workspace object
	}

	/**
	 * Add permission to node for object
	 *
	 * @param int $a_node_id
	 * @param int $a_object_id
	 */
	public function addPermission($a_node_id, $a_object_id)
	{
		global $ilDB, $ilUser;

		// tree owner must not be added
		if($this->tree->getTreeId() == $ilUser->getId() &&
			$a_object_id == $ilUser->getId())
		{
			return;
		}

		$ilDB->manipulate("INSERT INTO acl_ws (node_id, object_id)".
			" VALUES (".$ilDB->quote($a_node_id, "integer").", ".
			$ilDB->quote($a_object_id, "integer").")");
	}

	/**
	 * Remove permission[s] (for object) to node
	 *
	 * @param int $a_node_id
	 * @param int $a_object_id 
	 */
	public function removePermission($a_node_id, $a_object_id = null)
	{
		global $ilDB;
		
		$query = "DELETE FROM acl_ws".
			" WHERE node_id = ".$ilDB->quote($a_node_id, "integer");

		if($a_object_id)
		{
			$query .= " AND object_id = ".$ilDB->quote($a_object_id, "integer");
		}

		return $ilDB->manipulate($query);
	}

	/**
	 * Get all permissions to node
	 *
	 * @param int $a_node_id
	 * @return array
	 */
	public function getPermissions($a_node_id)
	{
		global $ilDB;

		$set = $ilDB->query("SELECT object_id FROM acl_ws".
			" WHERE node_id = ".$ilDB->quote($a_node_id, "integer"));
		$res = array();
		while($row = $ilDB->fetchAssoc($set))
		{
			$res[] = $row["object_id"];
		}
		return $res;
	}
}

?>