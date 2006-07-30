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

require_once "class.ilObject.php";

/**
* Class ilObjRole
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @ingroup	ServicesAccessControl
*/
class ilObjRole extends ilObject
{
	/**
	* reference id of parent object
	* this is _only_ neccessary for non RBAC protected objects
	* TODO: maybe move this to basic Object class
	* @var		integer
	* @access	private
	*/
	var $parent;
	
	var $allow_register;
	var $assign_users;

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjRole($a_id = 0,$a_call_by_reference = false)
	{
		$this->type = "role";
		$this->ilObject($a_id,$a_call_by_reference);
	}

	function toggleAssignUsersStatus($a_assign_users)
	{
		$this->assign_users = (int) $a_assign_users;
	}
	function getAssignUsersStatus()
	{
		return $this->assign_users;
	}
	// Same method (static)
	function _getAssignUsersStatus($a_role_id)
	{
		global $ilDB;

		$query = "SELECT assign_users FROM role_data WHERE role_id = '".$a_role_id."'";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->assign_users ? true : false;
		}
		return false;
	}

	/**
	* loads "role" from database
	* @access private
	*/
	function read ()
	{
		$q = "SELECT * FROM role_data WHERE role_id='".$this->id."'";
		$r = $this->ilias->db->query($q);

		if ($r->numRows() > 0)
		{
			$data = $r->fetchRow(DB_FETCHMODE_ASSOC);

			// fill member vars in one shot
			$this->assignData($data);
		}
		else
		{
			 $this->ilias->raiseError("<b>Error: There is no dataset with id ".$this->id."!</b><br />class: ".get_class($this)."<br />Script: ".__FILE__."<br />Line: ".__LINE__, $this->ilias->FATAL);
		}

		parent::read();
	}

	/**
	* loads a record "role" from array
	* @access	public
	* @param	array		roledata
	*/
	function assignData($a_data)
	{
		$this->setTitle(ilUtil::stripSlashes($a_data["title"]));
		$this->setDescription(ilUtil::stripslashes($a_data["desc"]));
		$this->setAllowRegister($a_data["allow_register"]);
		$this->toggleAssignUsersStatus($a_data['assign_users']);
	}

	/**
	* updates a record "role" and write it into database
	* @access	public
	*/
	function update ()
	{
		$q = "UPDATE role_data SET ".
			"allow_register='".$this->allow_register."', ".
			"assign_users = '".$this->getAssignUsersStatus()."' ".
			"WHERE role_id='".$this->id."'";

		$this->ilias->db->query($q);

		parent::update();

		$this->read();

		return true;
	}
	
	/**
	* create
	*
	*
	* @access	public
	* @return	integer		object id
	*/
	function create()
	{
		$this->id = parent::create();

		$q = "INSERT INTO role_data ".
			"(role_id,allow_register,assign_users) ".
			"VALUES ".
			"('".$this->id."','".$this->getAllowRegister()."','".$this->getAssignUsersStatus()."')";
		$this->ilias->db->query($q);

		return $this->id;
	}

	/**
	* set allow_register of role
	* 
	* @access	public
	* @param	integer
	*/
	function setAllowRegister($a_allow_register)
	{
		if (empty($a_allow_register))
		{
			$a_allow_register == 0;
		}
		
		$this->allow_register = (int) $a_allow_register;
	}
	
	/**
	* get allow_register
	* 
	* @access	public
	* @return	integer
	*/
	function getAllowRegister()
	{
		return $this->allow_register;
	}

	/**
	* get all roles that are activated in user registration
	*
	* @access	public
	* @return	array		array of int: role ids
	*/
	function _lookupRegisterAllowed()
	{
		global $ilDB;
		
		$q = "SELECT * FROM role_data ".
			"LEFT JOIN object_data ON object_data.obj_id = role_data.role_id ".
			"WHERE allow_register = 1";
			
		$r = $ilDB->query($q);
	
		$roles = array();
		while ($role = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$roles[] = array("id" => $role["obj_id"],
							 "title" => $role["title"],
							 "auth_mode" => $role['auth_mode']);
		}
		
		return $roles;
	}

	/**
	* check whether role is allowed in user registration or not
	*
	* @param	int			$a_role_id		role id
	* @return	boolean		true if role is allowed in user registration
	*/
	function _lookupAllowRegister($a_role_id)
	{
		global $ilDB;
		
		$q = "SELECT * FROM role_data ".
			" WHERE role_id =".$ilDB->quote($a_role_id);
			
		$role_set = $ilDB->query($q);
		
		if ($role_rec = $role_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if ($role_rec["allow_register"])
			{
				return true;
			}
		}
		return false;
	}

	/**
	* set reference id of parent object
	* this is neccessary for non RBAC protected objects!!!
	* 
	* @access	public
	* @param	integer	ref_id of parent object
	*/
	function setParent($a_parent_ref)
	{
		$this->parent = $a_parent_ref;
	}
	
	/**
	* get reference id of parent object
	* 
	* @access	public
	* @return	integer	ref_id of parent object
	*/
	function getParent()
	{
		return $this->parent;
	}

	/**
	* copy all properties and subobjects of a role.
	* DISABLED
	* @access	public
	* @return	integer	new ref id
	*/
	function ilClone($a_parent_ref)
	{		
		// DISABLED
		return false;

		global $rbacadmin;

		// always call parent ilClone function first!!
		$new_ref_id = parent::ilClone($a_parent_ref);
		
		// put here role specific stuff

		// ... and finally always return new reference ID!!
		return $new_ref_id;
	}

	/**
	* delete role and all related data
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{		
		global $rbacadmin, $rbacreview;
		
		$role_folders = $rbacreview->getFoldersAssignedToRole($this->getId());
		
		if ($rbacreview->isAssignable($this->getId(),$this->getParent()))
		{
			// do not delete role if this role is the last role a user is assigned to

			// first fetch all users assigned to role
//echo "<br>role id:".$this->getId().":";
			$user_ids = $rbacreview->assignedUsers($this->getId());

			$last_role_user_ids = array();

			foreach ($user_ids as $user_id)
			{
//echo "<br>user id:".$user_id.":";
				// get all roles each user has
				$role_ids = $rbacreview->assignedRoles($user_id);
				
				// is last role?
				if (count($role_ids) == 1)
				{
					$last_role_user_ids[] = $user_id;
				}			
			}
			
			// users with last role found?
			if (count($last_role_user_ids) > 0)
			{
				foreach ($last_role_user_ids as $user_id)
				{
//echo "<br>last role for user id:".$user_id.":";
					// GET OBJECT TITLE
					$tmp_obj = $this->ilias->obj_factory->getInstanceByObjId($user_id);
					$user_names[] = $tmp_obj->getFullname();
					unset($tmp_obj);
				}
				
				// TODO: This check must be done in rolefolder object because if multiple
				// roles were selected the other roles are still deleted and the system does not
				// give any feedback about this.
				$users = implode(', ',$user_names);
				$this->ilias->raiseError($this->lng->txt("msg_user_last_role1")." ".
									 $users."<br/>".$this->lng->txt("msg_user_last_role2"),$this->ilias->error_obj->WARNING);				
			}
			else
			{
				// IT'S A BASE ROLE
				$rbacadmin->deleteRole($this->getId(),$this->getParent());

				// delete object_data entry
				parent::delete();
					
				// delete role_data entry
				$q = "DELETE FROM role_data WHERE role_id = '".$this->getId()."'";
				$this->ilias->db->query($q);

				include_once './classes/class.ilRoleDesktopItem.php';
				
				$role_desk_item_obj =& new ilRoleDesktopItem($this->getId());
				$role_desk_item_obj->deleteAll();

			}
		}
		else
		{
			// linked local role: INHERITANCE WAS STOPPED, SO DELETE ONLY THIS LOCAL ROLE
			$rbacadmin->deleteLocalRole($this->getId(),$this->getParent());
		}

		//  purge empty rolefolders
		foreach ($role_folders as $rolf)
		{
			if (ilObject::_exists($rolf,true))
			{
				$rolfObj = $this->ilias->obj_factory->getInstanceByRefId($rolf);
				$rolfObj->purge();
				unset($roleObj);
			}
		}
		
		return true;
	}
	
	function getCountMembers()
	{
		global $rbacreview;
		
		return count($rbacreview->assignedUsers($this->getId()));
	}

	/**
	 * STATIC METHOD
	 * search for role data. This method is called from class.ilSearch
	 * This method used by class.ilSearchGUI.php to a link to the results
	 * @param	object object of search class
	 * @static
	 * @access	public
	 */
	function _search(&$a_search_obj)
	{
		global $ilBench;

		// NO CLASS VARIABLES IN STATIC METHODS

		$where_condition = $a_search_obj->getWhereCondition("like",array("title","description"));
		//$in = $a_search_obj->getInStatement("ore.ref_id");

		$query = "SELECT obj_id FROM object_data AS od ".
			$where_condition." ".
			"AND od.type = 'role' ";

		$ilBench->start("Search", "ilObjRole_search");
		$res = $a_search_obj->ilias->db->query($query);
		$ilBench->stop("Search", "ilObjRole_search");

		$counter = 0;

		while ($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$result_data[$counter++]["id"]				=  $row->obj_id;
		}

		return $result_data ? $result_data : array();
	}
	
	// updates role assignments in sessions of users that are online
    function _updateSessionRoles($a_selected_users)
	{
        global $ilDB, $rbacreview;
        
		$online_users_all = ilUtil::getUsersOnline();
		
        // users online to alter their role assignment
        $affected_users = array_intersect(array_keys($online_users_all),$a_selected_users);
        
	    foreach ($affected_users as $user)
		{
			$role_arr = $rbacreview->assignedRoles($user);

			// current user assigned himself?
            if ($user == $_SESSION["AccountId"])
			{
				$_SESSION["RoleId"] = $role_arr;
			}
			else
			{
				$roles = "RoleId|".serialize($role_arr);
				$modified_data = preg_replace("/RoleId.*?;\}/",$roles,$online_users_all[$user]["data"]);

				$q = "UPDATE usr_session SET data='".$modified_data."' WHERE user_id = '".$user."'";
				$ilDB->query($q);
			}
		}
	}
	
	function _getTranslation($a_role_title)
	{
		global $lng;
		
		$test_str = explode('_',$a_role_title);

		if ($test_str[0] == 'il') 
		{
			$test2 = (int) $test_str[3];
			if ($test2 > 0)
			{
				unset($test_str[3]);
			}

			return $lng->txt(implode('_',$test_str));
		}
		
		return $a_role_title;
	}
	
	function _updateAuthMode($a_roles)
	{
		global $ilDB;

		foreach ($a_roles as $role_id => $auth_mode)
		{
			$q = "UPDATE role_data SET ".
				 "auth_mode='".$auth_mode."' ".
				 "WHERE role_id='".$role_id."'";
			$ilDB->query($q);
		}
	}

	function _getAuthMode($a_role_id)
	{
		global $ilDB;

		$q = "SELECT auth_mode FROM role_data ".
			 "WHERE role_id='".$a_role_id."'";
		$r = $ilDB->query($q);
		$row = $r->fetchRow();
		
		return $row[0];
	}
	
	// returns array of operation/objecttype definitions
	// private
	function __getPermissionDefinitions()
	{
		global $ilDB, $lng, $objDefinition;		
		#$to_filter = $objDefinition->getSubobjectsToFilter();

		// build array with all rbac object types
		$q = "SELECT ta.typ_id,obj.title,ops.ops_id,ops.operation FROM rbac_ta AS ta ".
			 "LEFT JOIN object_data AS obj ON obj.obj_id=ta.typ_id ".
			 "LEFT JOIN rbac_operations AS ops ON ops.ops_id=ta.ops_id";
		$r = $ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			// FILTER SUBOJECTS OF adm OBJECT
			#if(in_array($row->title,$to_filter))
			#{
			#	continue;
			#}
			$rbac_objects[$row->typ_id] = array("obj_id"	=> $row->typ_id,
											    "type"		=> $row->title
												);

			$rbac_operations[$row->typ_id][$row->ops_id] = array(
									   							"ops_id"	=> $row->ops_id,
									  							"title"		=> $row->operation,
																"name"		=> $lng->txt($row->title."_".$row->operation)
															   );
		}
		
		return array($rbac_objects,$rbac_operations);
	}
} // END class.ilObjRole
?>
