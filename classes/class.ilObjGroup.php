<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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
* Class ilObjGroup
*
* @author Stefan Meyer <smeyer@databay.de> 
* @author Sascha Hofmann <saschahofmann@gmx.de> 
*
* @version $Id$
* 
* @extends ilObject
*/

//TODO: function getRoleId($groupRole) returns the object-id of grouprole

require_once "class.ilContainer.php";

define('GRP_REGISTRATION_DIRECT',0);
define('GRP_REGISTRATION_REQUEST',1);
define('GRP_REGISTRATION_PASSWORD',2);


class ilObjGroup extends ilContainer
{
	/**
	* Group file object for handling of export files
	*/
	var $file_obj = null;

	var $m_grpStatus;

	var $m_roleMemberId;

	var $m_roleAdminId;

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjGroup($a_id = 0,$a_call_by_reference = true)
	{
		global $tree;

		$this->tree =& $tree;

		$this->type = "grp";
		$this->ilObject($a_id,$a_call_by_reference);
		$this->setRegisterMode(true);
	}
	
	/**
	 * Clone group (no member data)
	 *
	 * @access public
	 * @param int target ref_id
	 * @param int copy id
	 * 
	 */
	public function cloneObject($a_target_id,$a_copy_id = 0)
	{
		global $ilDB;
		
	 	$new_obj = parent::cloneObject($a_target_id,$a_copy_id);
	 	$new_obj->setGroupStatus($this->readGroupStatus());
	 	$new_obj->initGroupStatus();
	 	
		// find a free number
		for ($n = 1;$n < 999;$n++)
		{
			$groupname_copy = $this->getTitle()."_(copy_".$n.")";

			if (!ilUtil::groupNameExists($groupname_copy))
			{
				$new_obj->setTitle($groupname_copy);
				$new_obj->update();
				break;
			}
		}
	 	
		
		// Copy settings
		$new_obj->setRegistrationFlag($this->getRegistrationFlag());
		$new_obj->setPassword($this->getPassword());
		$new_obj->updateExpiration($this->getExpiration());
		
				
		// Copy learning progress settings
		include_once('Services/Tracking/classes/class.ilLPObjSettings.php');
		$obj_settings = new ilLPObjSettings($this->getId());
		$obj_settings->cloneSettings($new_obj->getId());
		unset($obj_settings);
		
		return $new_obj;
	}
	
	/**
	 * Clone object dependencies (crs items, preconditions)
	 *
	 * @access public
	 * @param int target ref id of new course
	 * @param int copy id
	 * 
	 */
	public function cloneDependencies($a_target_id,$a_copy_id)
	{
		global $tree;
		
		if($course_ref_id = $tree->checkForParentType($this->getRefId(),'crs') and
			$new_course_ref_id = $tree->checkForParentType($a_target_id,'crs'))
		{
			include_once('Modules/Course/classes/class.ilCourseItems.php');
			$course_obj =& ilObjectFactory::getInstanceByRefId($course_ref_id,false);
			$course_items = new ilCourseItems($course_obj,$this->getRefId());
			$course_items->cloneDependencies($a_target_id,$a_copy_id);			
		}
		
		include_once('Services/Tracking/classes/class.ilLPCollections.php');
		$lp_collection = new ilLPCollections($this->getId());
		$lp_collection->cloneCollections($a_target_id,$a_copy_id);		
		
	 	return true;
	}
	
	

	/**
	* join Group, assigns user to role
	* @access	private
	* @param	integer	member status = obj_id of local_group_role
	*/
	function join($a_user_id, $a_mem_role="")
	{
		global $rbacadmin;

		if (is_array($a_mem_role))
		{
			foreach ($a_mem_role as $role)
			{
				$rbacadmin->assignUser($role,$a_user_id, false);
			}
		}
		else
		{
			$rbacadmin->assignUser($a_mem_role,$a_user_id, false);
		}

		ilObjUser::updateActiveRoles($a_user_id);
		return true;
	}

	/**
	* returns object id of created default member role
	* @access	public
	*/
	function getDefaultMemberRole()
	{
		$local_group_Roles = $this->getLocalGroupRoles();

		return $local_group_Roles["il_grp_member_".$this->getRefId()];
	}

	/**
	* returns object id of created default adminstrator role
	* @access	public
	*/
	function getDefaultAdminRole()
	{
		$local_group_Roles = $this->getLocalGroupRoles();

		return $local_group_Roles["il_grp_admin_".$this->getRefId()];
	}

	/**
	* add Member to Group
	* @access	public
	* @param	integer	user_id
	* @param	integer	member role_id of local group_role
	*/
	function addMember($a_user_id, $a_mem_role)
	{
		global $rbacadmin;

		if (isset($a_user_id) && isset($a_mem_role) )
		{
			$this->join($a_user_id,$a_mem_role);
			return true;
		}
		else
		{
			$this->ilias->raiseError(get_class($this)."::addMember(): Missing parameters !",$this->ilias->error_obj->WARNING);
			return false;
		}
	}

	/**
	* displays list of applicants
	* @access	public
	*/
	function getNewRegistrations()
	{
		global $ilDB;
		
		$appList = array();
		$q = "SELECT * FROM grp_registration WHERE grp_id=".
			$ilDB->quote($this->getId());
		$res = $this->ilias->db->query($q);
		while ($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if (ilObject::_exists($row->user_id) &&
				ilObject::_lookupType($row->user_id) == "usr")
			{
				array_push($appList,$row);
			}
		}

		return ($appList) ? $appList : false;
	}

	/**
	* deletes an Entry from application list
	* @access	public
	*/
	function deleteApplicationListEntry($a_userId)
	{
		global $ilDB;
		
		$q = "DELETE FROM grp_registration WHERE user_id=".
			$ilDB->quote($a_userId)." AND grp_id=".$ilDB->quote($this->getId());
		$res = $this->ilias->db->query($q);
	}

	/**
	* is called when a member decides to leave group
	* @access	public
	* @param	integer	user-Id
	* @param	integer group-Id
	*/
	function leaveGroup()
	{
		global $rbacadmin, $rbacreview;

		$member_ids = $this->getGroupMemberIds();

		if (count($member_ids) <= 1 || !in_array($this->ilias->account->getId(), $member_ids))
		{
			return 2;
		}
		else
		{
			if (!$this->isAdmin($this->ilias->account->getId()))
			{
				$this->leave($this->ilias->account->getId());
				$member = new ilObjUser($this->ilias->account->getId());
				$member->dropDesktopItem($this->getRefId(), "grp");

				return 0;
			}
			else if (count($this->getGroupAdminIds()) == 1)
			{
				return 1;
			}
		}
	}

	/**
	* deassign member from group role
	* @access	private
	*/
	function leave($a_user_id)
	{
		global $rbacadmin;

		$arr_groupRoles = $this->getMemberRoles($a_user_id);

		if (is_array($arr_groupRoles))
		{
			foreach ($arr_groupRoles as $groupRole)
			{
				$rbacadmin->deassignUser($groupRole, $a_user_id);
			}
		}
		else
		{
			$rbacadmin->deassignUser($arr_groupRoles, $a_user_id);
		}

		ilObjUser::updateActiveRoles($a_user_id);

		return true;
	}

	/**
	* removes Member from group
	* @access	public
	*/
	function removeMember($a_user_id, $a_grp_id="")
	{
		if (isset($a_user_id) && isset($a_grp_id) && $this->isMember($a_user_id))
		{
			if (count($this->getGroupMemberIds()) > 1)
			{
				if ($this->isAdmin($a_user_id) && count($this->getGroupAdminIds()) < 2)
				{
					return "grp_err_administrator_required";
				}
				else
				{
					$this->leave($a_user_id);
					$member = new ilObjUser($a_user_id);
					$member->dropDesktopItem($this->getRefId(), "grp");

					return "";
				}
			}
			else
			{
				return "grp_err_last_member";
			}
		}
		else
		{
			$this->ilias->raiseError(get_class($this)."::removeMember(): Missing parameters !",$this->ilias->error_obj->WARNING);
		}
	}

	/**
	* get all group Member ids regardless of role
	* @access	public
	* @return	return array of users (obj_ids) that are assigned to
	* the groupspecific roles (grp_member,grp_admin)
	*/
	function getGroupMemberIds()
	{
		global $rbacadmin, $rbacreview;

		$usr_arr= array();

		$rol  = $this->getLocalGroupRoles();

		foreach ($rol as $value)
		{
			foreach ($rbacreview->assignedUsers($value) as $member_id)
			{
				array_push($usr_arr,$member_id);
			}
		}

		$mem_arr = array_unique($usr_arr);
		
		return $mem_arr ? $mem_arr : array();
	}
	
	/**
	* get all group Members regardless of group role.
	* fetch all users data in one shot to improve performance
	* @access	public
	* @param	array	of user ids
	* @return	return array of userdata
	*/
	function getGroupMemberData($a_mem_ids, $active = 1)
	{
		global $rbacadmin, $rbacreview, $ilBench, $ilDB;

		$usr_arr= array();
		
		$q = "SELECT login,firstname,lastname,title,usr_id,last_login ".
			 "FROM usr_data ".
			 "WHERE usr_id IN (".implode(',',ilUtil::quoteArray($a_mem_ids)).")";
			 
  		if (is_numeric($active) && $active > -1)
  			$q .= "AND active = '$active'";			 
		
  		$r = $ilDB->query($q);
		
		while($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$mem_arr[] = array("id" => $row->usr_id,
								"login" => $row->login,
								"firstname" => $row->firstname,
								"lastname" => $row->lastname,
								"last_login" => $row->last_login
								);
		}

		return $mem_arr ? $mem_arr : array();
	}

	function getCountMembers()
	{
		return count($this->getGroupMemberIds());
	}

	/**
	* get Group Admin Id
	* @access	public
	* @param	integer	group id
	* @param	returns userids that are assigned to a group administrator! role
	*/
	function getGroupAdminIds($a_grpId="")
	{
		global $rbacreview;

		if (!empty($a_grpId))
		{
			$grp_id = $a_grpId;
		}
		else
		{
			$grp_id = $this->getRefId();
		}

		$usr_arr = array();
		$roles = $this->getDefaultGroupRoles($this->getRefId());

		foreach ($rbacreview->assignedUsers($this->getDefaultAdminRole()) as $member_id)
		{
			array_push($usr_arr,$member_id);
		}

		return $usr_arr;
	}

	/**
	* get default group roles, returns the defaultlike create roles il_grp_member, il_grp_admin
	* @access	public
	* @param 	returns the obj_ids of group specific roles(il_grp_member,il_grp_admin)
	*/
	function getDefaultGroupRoles($a_grp_id="")
	{
		global $rbacadmin, $rbacreview;

		if (strlen($a_grp_id) > 0)
		{
			$grp_id = $a_grp_id;
		}
		else
		{
			$grp_id = $this->getRefId();
		}

		$rolf 	   = $rbacreview->getRoleFolderOfObject($grp_id);
		$role_arr  = $rbacreview->getRolesOfRoleFolder($rolf["ref_id"]);

		foreach ($role_arr as $role_id)
		{
			$role_Obj =& $this->ilias->obj_factory->getInstanceByObjId($role_id);

			$grp_Member ="il_grp_member_".$grp_id;
			$grp_Admin  ="il_grp_admin_".$grp_id;

			if (strcmp($role_Obj->getTitle(), $grp_Member) == 0 )
			{
				$arr_grpDefaultRoles["grp_member_role"] = $role_Obj->getId();
			}

			if (strcmp($role_Obj->getTitle(), $grp_Admin) == 0)
			{
				$arr_grpDefaultRoles["grp_admin_role"] = $role_Obj->getId();
			}
		}

		return $arr_grpDefaultRoles;
	}

	/**
	* get ALL local roles of group, also those created and defined afterwards
	* only fetch data once from database. info is stored in object variable
	* @access	public
	* @return	return array [title|id] of roles...
	*/
	function getLocalGroupRoles($a_translate = false)
	{
		global $rbacadmin,$rbacreview;
		
		if (empty($this->local_roles))
		{
			$this->local_roles = array();
			$rolf 	   = $rbacreview->getRoleFolderOfObject($this->getRefId());
			$role_arr  = $rbacreview->getRolesOfRoleFolder($rolf["ref_id"]);

			foreach ($role_arr as $role_id)
			{
				if ($rbacreview->isAssignable($role_id,$rolf["ref_id"]) == true)
				{
					$role_Obj =& $this->ilias->obj_factory->getInstanceByObjId($role_id);
					
					if ($a_translate)
					{
						$role_name = ilObjRole::_getTranslation($role_Obj->getTitle());
					}
					else
					{
						$role_name = $role_Obj->getTitle();
					}
					
					$this->local_roles[$role_name] = $role_Obj->getId();
				}
			}
		}
		
		return $this->local_roles;
	}

	/**
	* get group status closed template
	* @access	public
	* @param	return obj_id of roletemplate containing permissionsettings for a closed group
	*/
	function getGrpStatusClosedTemplateId()
	{
		$q = "SELECT obj_id FROM object_data WHERE type='rolt' AND title='il_grp_status_closed'";
		$res = $this->ilias->db->query($q);
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);

		return $row["obj_id"];
	}

	/**
	* get group status open template
	* @access	public
	* @param	return obj_id of roletemplate containing permissionsettings for an open group
	*/
	function getGrpStatusOpenTemplateId()
	{
		$q = "SELECT obj_id FROM object_data WHERE type='rolt' AND title='il_grp_status_open'";
		$res = $this->ilias->db->query($q);
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);

		return $row["obj_id"];
	}
	
	/**
	* set Registration Flag 
	* @access	public
	* @param	integer [ 0 = no registration| 1 = registration]
	*/
	function setRegistrationFlag($a_regFlag="")
	{
		global $ilDB;
		
		$q = "SELECT * FROM grp_data WHERE grp_id= ".
			$ilDB->quote($this->getId());
		$res = $this->ilias->db->query($q);

		if (!isset($a_regFlag))
		{
			$a_regFlag = 0;
		}

		if ($res->numRows() == 0)
		{
			$q = "INSERT INTO grp_data (grp_id, register) VALUES(".
				$ilDB->quote($this->getId()).",".
				$ilDB->quote($a_regFlag).")";
			$res = $this->ilias->db->query($q);
		}
		else
		{
			$q = "UPDATE grp_data SET register=".
				$ilDB->quote($a_regFlag)." WHERE grp_id=".
				$ilDB->quote($this->getId());
			$res = $this->ilias->db->query($q);
		}
	}

	/**
	* get Registration Flag
	* @access	public
	* @param	return flag => [ 0 = no registration| 1 = registration]
	*/
	function getRegistrationFlag()
	{
		global $ilDB;
		
		$q = "SELECT * FROM grp_data WHERE grp_id= ".
			$ilDB->quote($this->getId());
		$res = $this->ilias->db->query($q);
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);

		return $row["register"];
	}

	/**
	* get Password
	* @access	public
	* @param	return password
	*/
	function getPassword()
	{
		global $ilDB;
		
		$q = "SELECT * FROM grp_data WHERE grp_id= ".
			$ilDB->quote($this->getId());
		$res = $this->ilias->db->query($q);
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);

		return $row["password"];
	}
	
	/**
	* set Password
	* @access	public
	* @param	password
	*/
	function setPassword($a_password="")
	{
		global $ilDB;
		
		$q = "SELECT * FROM grp_data WHERE grp_id= ".
			$ilDB->quote($this->getId());
			
		$res = $this->ilias->db->query($q);

		if ($res->numRows() == 0)
		{
			$q = "INSERT INTO grp_data (grp_id, password) VALUES(".
				$ilDB->quote($this->getId()).",".$ilDB->quote($a_password).")";
			$res = $this->ilias->db->query($q);
		}
		else
		{
			$q = "UPDATE grp_data SET password=".$ilDB->quote($a_password)." WHERE grp_id=".
				$ilDB->quote($this->getId());
			$res = $this->ilias->db->query($q);
		}
	}
	
	/**
	 * Set expiration
	 *
	 * @access public
	 * @param string expiration
	 * 
	 */
	public function updateExpiration($a_date)
	{
		global $ilDB;
		
		$q = "SELECT * FROM grp_data WHERE grp_id= ".
			$ilDB->quote($this->getId());
		$res = $this->ilias->db->query($q);
		$date = $a_date;

		if ($res->numRows() == 0)
		{
			$q = "INSERT INTO grp_data (grp_id, expiration) VALUES(".
				$ilDB->quote($this->getId()).",".$ilDB->quote($date).")";
			$res = $this->ilias->db->query($q);
		}
		else
		{
			$q = "UPDATE grp_data SET expiration=".
				$ilDB->quote($date)." WHERE grp_id=".$ilDB->quote($this->getId());
			$res = $this->ilias->db->query($q);
		}
	 	
	}

	/**
	* set Expiration Date and Time
	* @access	public
	* @param	date
	*/
	function setExpirationDateTime($a_date)
	{
		global $ilDB;
		
		$q = "SELECT * FROM grp_data WHERE grp_id= ".
			$ilDB->quote($this->getId());
		$res = $this->ilias->db->query($q);
		$date = ilFormat::input2date($a_date);

		if ($res->numRows() == 0)
		{
			$q = "INSERT INTO grp_data (grp_id, expiration) VALUES(".
				$ilDB->quote($this->getId()).",".$ilDB->quote($date).")";
			$res = $this->ilias->db->query($q);
		}
		else
		{
			$q = "UPDATE grp_data SET expiration=".
				$ilDB->quote($date)." WHERE grp_id=".$ilDB->quote($this->getId());
			$res = $this->ilias->db->query($q);
		}
	}
	
	/**
	 * Get expiration
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function getExpiration()
	{
		global $ilDB;
		
		$q = "SELECT * FROM grp_data WHERE grp_id= ".
			$ilDB->quote($this->getId());
		$res = $this->ilias->db->query($q);
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		return $datetime = $row["expiration"];
	}

	/**
	* get Expiration Date and Time
	* @access	public
	* @param	return array(0=>date, 1=>time)
	*/
	function getExpirationDateTime()
	{
		global $ilDB;
		
		$q = "SELECT * FROM grp_data WHERE grp_id= ".
			$ilDB->quote($this->getId());
		$res = $this->ilias->db->query($q);
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		$datetime = $row["expiration"];
		$date = ilFormat::fdateDB2dateDE($datetime);
		$time = substr($row["expiration"], -8);
		$datetime = array(0=>$date, 1=>$time);

		return $datetime;
	}

	function getExpirationTimestamp()
	{
		global $ilDB;
		
		$query = "SELECT * FROM grp_data WHERE grp_id = ".
			$ilDB->quote($this->getId());

		$res = $this->ilias->db->query($query);
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		$datetime = $row["expiration"];

		return ($timest = ilFormat::datetime2unixTS($datetime)) ? $timest : 0;
	}
		

	function registrationPossible()
	{
		$datetime = $this->getExpirationDateTime();
		$today_date = ilFormat::getDateDE();
		$today_time = date("H:i:s");
       
		$ts_exp_date = ilFormat::dateDE2timestamp($datetime[0]);
		$ts_today_date = ilFormat::dateDE2timestamp($today_date);
	
		$ts_exp_time = substr($datetime[1], 0, 2).
						substr($datetime[1], 3, 2).
						substr($datetime[1], 6, 2);
		
		$ts_today_time = substr($today_time, 0, 2).
						substr($today_time, 3, 2).
						substr($today_time, 6, 2);
						
		// no timelimit given -> unlimited
		if ($ts_exp_date == 0)
		{
			return true;
		}
		
		if ($ts_today_date < $ts_exp_date) 
		{
			return true;
		}
		elseif (($ts_today_date == $ts_exp_date) and (strcmp($ts_exp_time,$ts_today_time) >= 0)) 
		{
			return true;
		}
		else 
		{
			return false;
		}
	}

	/**
	* set group status
	*
	* Grants permissions on the group object for all parent roles.  
	* Each permission is granted by computing the intersection of the role 
	* template il_grp_status_open/_closed and the permission template of 
	* the parent role.
	*
	* Creates linked roles in the local role folder object for all 
	* parent roles and initializes their permission templates.
	* Each permission template is initialized by computing the intersection 
	* of the role template il_grp_status_open/_closed and the permission
	* template of the parent role.
	*
	* @access	public
	* @param	integer	group id (optional)
	* @param	integer group status (0=public|1=private|2=closed)
	*/
	function initGroupStatus($a_grpStatus)
	{
		global $rbacadmin, $rbacreview, $rbacsystem;

		//get Rolefolder of group
		$rolf_data = $rbacreview->getRoleFolderOfObject($this->getRefId());

		//define all relevant roles that rights are needed to be changed
		$arr_parentRoles = $rbacreview->getParentRoleIds($this->getRefId());
		$arr_relevantParentRoleIds = array_diff(array_keys($arr_parentRoles),$this->getDefaultGroupRoles());

		//group status open (aka public) or group status closed
		if ($a_grpStatus == 0 || $a_grpStatus == 1)
		{
			if ($a_grpStatus == 0)
			{
				$template_id = $this->getGrpStatusOpenTemplateId();
			} else {
				$template_id = $this->getGrpStatusClosedTemplateId();
			}
			//get defined operations from template
			$template_ops = $rbacreview->getOperationsOfRole($template_id, 'grp', ROLE_FOLDER_ID);

			foreach ($arr_relevantParentRoleIds as $parentRole)
			{
				if ($rbacreview->isProtected($arr_parentRoles[$parentRole]['parent'],$parentRole))
				{
					continue;
				}
				
				$granted_permissions = array();

				// Delete the linked role for the parent role
				// (just in case if it already exists).
				$rbacadmin->deleteLocalRole($parentRole,$rolf_data["child"]);

				// Grant permissions on the group object for 
				// the parent role. In the foreach loop we
				// compute the intersection of the role     
				// template il_grp_status_open/_closed and the 
				// permission template of the parent role.
				$current_ops = $rbacreview->getRoleOperationsOnObject($parentRole, $this->getRefId());
				$rbacadmin->revokePermission($this->getRefId(), $parentRole);
				foreach ($template_ops as $template_op) 
				{
					if (in_array($template_op,$current_ops)) 
					{
						array_push($granted_permissions,$template_op);
					}
				}
				if (!empty($granted_permissions))
				{
					$rbacadmin->grantPermission($parentRole, $granted_permissions, $this->getRefId());
				}

				// Create a linked role for the parent role and
				// initialize it with the intersection of 
				// il_grp_status_open/_closed and the permission
				// template of the parent role
				$rbacadmin->copyRolePermissionIntersection(
					$template_id, ROLE_FOLDER_ID, 
					$parentRole, $arr_parentRoles[$parentRole]['parent'], 
					$rolf_data["child"], $parentRole
				);	
				$rbacadmin->assignRoleToFolder($parentRole,$rolf_data["child"],"false");
			}//END foreach
		}
	}
	
	/**
	 * Set group status
	 *
	 * @access public
	 * @param int group status[0=public|2=closed]
	 * 
	 */
	public function setGroupStatus($a_status)
	{
		$this->group_status = $a_status;
	}
	
	/**
	 * get group status
	 *
	 * @access public
	 * @param int group status
	 * 
	 */
	public function getGroupStatus()
	{
	 	return $this->group_status;
	}

	/**
	* get group status, redundant method because
	* @access	public
	* @param	return group status[0=public|2=closed]
	*/
	function readGroupStatus()
	{
		global $rbacsystem,$rbacreview;

		$role_folder = $rbacreview->getRoleFolderOfObject($this->getRefId());
		$local_roles = $rbacreview->getRolesOfRoleFolder($role_folder["ref_id"]);

		//get Rolefolder of group
		$rolf_data = $rbacreview->getRoleFolderOfObject($this->getRefId());
		//get all relevant roles
		$arr_globalRoles = array_diff($local_roles, $this->getDefaultGroupRoles());

		//if one global role has no permission to join the group is officially closed !
		foreach ($arr_globalRoles as $globalRole)
		{
			$ops_of_role = $rbacreview->getOperationsOfRole($globalRole,"grp", ROLE_FOLDER_ID);

			if ($rbacsystem->checkPermission($this->getRefId(), $globalRole ,"join"))
			{
				return $this->group_status = 0;
			}
		}

		return $this->group_status = 2;
	}

	/**
	* get group member status
	* @access	public
	* @param	integer	user_id
	* @return	returns array of obj_ids of assigned local roles
	*/
	function getMemberRoles($a_user_id)
	{
		global $rbacadmin, $rbacreview,$ilBench;

		$ilBench->start("Group", "getMemberRoles");

		$arr_assignedRoles = array();

		$arr_assignedRoles = array_intersect($rbacreview->assignedRoles($a_user_id),$this->getLocalGroupRoles());

		$ilBench->stop("Group", "getMemberRoles");

		return $arr_assignedRoles;
	}
	
	/**
	* get group member status
	* @access	public
	* @param	integer	user_id
	* @return	returns string of role titles
	*/
	function getMemberRolesTitle($a_user_id)
	{
		global $ilDB,$ilBench;
		
		include_once ('class.ilObjRole.php');

		$ilBench->start("Group", "getMemberRolesTitle");
		
		$str_member_roles ="";

		$q = "SELECT title ".
			 "FROM object_data ".
			 "LEFT JOIN rbac_ua ON object_data.obj_id=rbac_ua.rol_id ".
			 "WHERE object_data.type = 'role' ".
			 "AND rbac_ua.usr_id = ".$ilDB->quote($a_user_id)." ".
			 "AND rbac_ua.rol_id IN (".implode(',',ilUtil::quoteArray($this->getLocalGroupRoles())).")";

		$r = $ilDB->query($q);

		while($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			// display human readable role names for autogenerated roles
			$str_member_roles .= ilObjRole::_getTranslation($row["title"]).", ";
		}

		$ilBench->stop("Group", "getMemberRolesTitle");
				
		return substr($str_member_roles,0,-2);
	}
	
	/**
	* set member status
	* @access	public
	* @param	integer	user id
	* @param	integer member role id
	*/
	function setMemberStatus($a_user_id, $a_member_role)
	{
		if (isset($a_user_id) && isset($a_member_role))
		{
			$this->removeMember($a_user_id);
			$this->addMember($a_user_id, $a_member_role);
		}
	}

	/**
	* is Member
	* @access	public
	* @param	integer	user_id
	* @param	return true if user is member
	*/
	function isMember($a_userId = "")
	{
		if (strlen($a_userId) == 0)
		{
			$user_id = $this->ilias->account->getId();
		}
		else 
		{
			$user_id = $a_userId;
		}
		
		if ($this->getType() == "grp")
		{
			$arr_members = $this->getGroupMemberIds();

			if (in_array($user_id, $arr_members))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	* is Admin
	* @access	public
	* @param	integer	user_id
	* @param	boolean, true if user is group administrator
	*/
	function isAdmin($a_userId)
	{
		global $rbacreview;

		$grp_Roles = $this->getDefaultGroupRoles();

		if (in_array($a_userId,$rbacreview->assignedUsers($grp_Roles["grp_admin_role"])))
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	* delete group and all related data
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	* TODO: Grouplinking is not longer permitted -> no other referneces possible
	* TODO: If entire group is deleted entries of object in group that are lying in trash (-> negative tree ID) are not removed!
	*/
	function delete()
	{
		global $ilDB;
		
		// always call parent delete function first!!
		if (!parent::delete())
		{
			return false;
		}
		
		$query = "DELETE FROM grp_data WHERE grp_id = ".
			$ilDB->quote($this->getId());
		$this->ilias->db->query($query);

		return true;
	}

	/**
	* init default roles settings
	* @access	public
	* @return	array	object IDs of created local roles.
	*/
	function initDefaultRoles($a_group_status = 0)
	{
		global $rbacadmin, $rbacreview;

		// create a local role folder
		$rfoldObj =& $this->createRoleFolder();

		// ADMIN ROLE
		// create role and assign role to rolefolder...
		$roleObj = $rfoldObj->createRole("il_grp_admin_".$this->getRefId(),"Groupadmin of group obj_no.".$this->getId());
		$this->m_roleAdminId = $roleObj->getId();

		//set permission template of new local role
		$q = "SELECT obj_id FROM object_data WHERE type='rolt' AND title='il_grp_admin'";
		$r = $this->ilias->db->getRow($q, DB_FETCHMODE_OBJECT);
		$rbacadmin->copyRolePermission($r->obj_id,ROLE_FOLDER_ID,$rfoldObj->getRefId(),$roleObj->getId());

		// set object permissions of group object
		$ops = $rbacreview->getOperationsOfRole($roleObj->getId(),"grp",$rfoldObj->getRefId());
		$rbacadmin->grantPermission($roleObj->getId(),$ops,$this->getRefId());

		// set object permissions of role folder object
		//$ops = $rbacreview->getOperationsOfRole($roleObj->getId(),"rolf",$rfoldObj->getRefId());
		//$rbacadmin->grantPermission($roleObj->getId(),$ops,$rfoldObj->getRefId());

		// MEMBER ROLE
		// create role and assign role to rolefolder...
		$roleObj = $rfoldObj->createRole("il_grp_member_".$this->getRefId(),"Groupmember of group obj_no.".$this->getId());
		$this->m_roleMemberId = $roleObj->getId();

		//set permission template of new local role
		$q = "SELECT obj_id FROM object_data WHERE type='rolt' AND title='il_grp_member'";
		$r = $this->ilias->db->getRow($q, DB_FETCHMODE_OBJECT);
		$rbacadmin->copyRolePermission($r->obj_id,ROLE_FOLDER_ID,$rfoldObj->getRefId(),$roleObj->getId());
		
		// set object permissions of group object
		$ops = $rbacreview->getOperationsOfRole($roleObj->getId(),"grp",$rfoldObj->getRefId());
		$rbacadmin->grantPermission($roleObj->getId(),$ops,$this->getRefId());

		// set object permissions of role folder object
		//$ops = $rbacreview->getOperationsOfRole($roleObj->getId(),"rolf",$rfoldObj->getRefId());
		//$rbacadmin->grantPermission($roleObj->getId(),$ops,$rfoldObj->getRefId());

		unset($rfoldObj);
		unset($roleObj);

		$roles[] = $this->m_roleAdminId;
		$roles[] = $this->m_roleMemberId;
		
		return $roles ? $roles : array();
	}

	/**
	* notifys an object about an event occured
	* Based on the event happend, each object may decide how it reacts.
	* 
	* @access	public
	* @param	string	event
	* @param	integer	reference id of object where the event occured
	* @param	array	passes optional parameters if required
	* @return	boolean
	*/
	function notify($a_event,$a_ref_id,$a_parent_non_rbac_id,$a_node_id,$a_params = 0)
	{
		global $tree;
		
		$parent_id = (int) $tree->getParentId($a_node_id);
		
		if ($parent_id != 0)
		{
			$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($a_node_id);
			$obj_data->notify($a_event,$a_ref_id,$a_parent_non_rbac_id,$parent_id,$a_params);
		}
				
		return true;
	}


	function exportXML()
	{
		include_once 'classes/class.ilGroupXMLWriter.php';

		$xml_writer = new ilGroupXMLWriter($this);
		$xml_writer->start();
		
		$xml = $xml_writer->getXML();

		$name = time().'__'.$this->ilias->getSetting('inst_id').'__grp_'.$this->getId();

		$this->__initFileObject();
		
		$this->file_obj->addGroupDirectory();
		$this->file_obj->addDirectory($name);
		$this->file_obj->writeToFile($xml,$name.'/'.$name.'.xml');
		$this->file_obj->zipFile($name,$name.'.zip');
		$this->file_obj->deleteDirectory($name);

		return true;
	}

	function deleteExportFiles($a_files)
	{
		$this->__initFileObject();
		
		foreach($a_files as $file)
		{
			$this->file_obj->deleteFile($file);
		}
		return true;
	}

	function downloadExportFile($file)
	{
		$this->__initFileObject();

		if($abs_name = $this->file_obj->getExportFile($file))
		{
			ilUtil::deliverFile($abs_name,$file);
			// Not reached
		}
		return false;
	}

	/**
	 * Static used for importing a group from xml string
	 *
	 * @param	xml string
	 * @static
	 * @access	public
	 */

	function _importFromXMLString($xml,$parent_id)
	{
		include_once 'classes/class.ilGroupImportParser.php';

		$import_parser = new ilGroupImportParser($xml,$parent_id);

		return $import_parser->startParsing();
	}

	/**
	 * Static used for importing an group from xml zip file
	 *
	 * @param	xml file array structure like $_FILE from upload
	 * @static
	 * @access	public
	 */
	function _importFromFile($file,$parent_id)
	{
		global $lng;

		include_once 'classes/class.ilFileDataGroup.php';
		
		$file_obj = new ilFileDataGroup(null);
		$file_obj->addImportDirectory();
		$file_obj->createImportFile($_FILES["xmldoc"]["tmp_name"],$_FILES['xmldoc']['name']);
		$file_obj->unpackImportFile();

		if(!$file_obj->validateImportFile())
		{
			return false;
		}
		return ilObjGroup::_importFromXMLString(file_get_contents($file_obj->getImportFile()),$parent_id);
	}

	/**
	 * STATIC METHOD
	 * search for group data. This method is called from class.ilSearch
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
		$in = $a_search_obj->getInStatement("ore.ref_id");

		$query = "SELECT ore.ref_id AS ref_id FROM object_data AS od, object_reference AS ore ".
			$where_condition." ".
			$in." ".
			"AND od.obj_id = ore.obj_id ".
			"AND od.type = 'grp' ";

		$ilBench->start("Search", "ilObjGroup_search");
		$res = $a_search_obj->ilias->db->query($query);
		$ilBench->stop("Search", "ilObjGroup_search");

		$counter = 0;

		while ($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$result_data[$counter++]["id"]				=  $row->ref_id;
			#$result_data[$counter]["link"]				=  "group.php?cmd=view&ref_id=".$row->ref_id;
			#$result_data[$counter++]["target"]			=  "";
		}

		return $result_data ? $result_data : array();
	}

	/**
	 * STATIC METHOD
	 * create a link to the object
	 * @param	int uniq id
	 * @return array array('link','target')
	 * @static
	 * @access	public
	 */
	function _getLinkToObject($a_id)
	{
		return array("repository.php?ref_id=".$a_id."&set_mode=flat&cmdClass=ilobjgroupgui","");
	}

	function _lookupIdByTitle($a_title)
	{
		global $ilDB;

		$query = "SELECT * FROM object_data WHERE title = ".
			$ilDB->quote($a_title)." AND type = 'grp'";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->obj_id;
		}
		return 0;
	}

	
	function isUserRegistered($a_user_id = 0)
	{
		global $rbacsystem;
		
		// exclude system role from check
		/*if (in_array(SYSTEM_ROLE_ID,$_SESSION["RoleId"]))
		{
			return true;		
		}*/

		if (!$this->isMember() or !$rbacsystem->checkAccess("join", $this->ref_id))
		{
			return false;
		}
		
		return true;
	}
	
	function _isMember($a_user_id,$a_ref_id,$a_field = '')
	{
		global $rbacreview,$ilObjDataCache,$ilDB;
		
		$rolf = $rbacreview->getRoleFolderOfObject($a_ref_id);
		$local_roles = $rbacreview->getRolesOfRoleFolder($rolf["ref_id"],false);
		$user_roles = $rbacreview->assignedRoles($a_user_id);

		// Used for membership limitations -> check membership by given field
		if($a_field)
		{
			include_once 'classes/class.ilObjUser.php';

			$tmp_user =& ilObjectFactory::getInstanceByObjId($a_user_id);
			switch($a_field)
			{
				case 'login':
					$and = "AND login = '".$tmp_user->getLogin()."' ";
					break;
				case 'email':
					$and = "AND email = '".$tmp_user->getEmail()."' ";
					break;
				case 'matriculation':
					$and = "AND matriculation = '".$tmp_user->getMatriculation()."' ";
					break;
					
				default:
					$and = "AND usr_id = '".$a_usr_id."'";
					break;
			}
			if(!$members = ilObjGroup::_getMembers($ilObjDataCache->lookupObjId($a_ref_id)))
			{
				return false;
			}
			$query = "SELECT * FROM usr_data as ud ".
				"WHERE usr_id IN (".implode(",",ilUtil::quoteArray($members)).") ".
				$and;
			$res = $ilDB->query($query);
			
			return $res->numRows() ? true : false;
		}

			
			
	
		
		if (!array_intersect($local_roles,$user_roles))
		{
			return false;
		}
		
		return true;
	}

	function _getMembers($a_obj_id)
	{
		global $rbacreview;

		// get reference
		$ref_ids = ilObject::_getAllReferences($a_obj_id);
		$ref_id = current($ref_ids);
		
		$rolf = $rbacreview->getRoleFolderOfObject($ref_id);
		$local_roles = $rbacreview->getRolesOfRoleFolder($rolf['ref_id'],false);
		
		$users = array();
		foreach($local_roles as $role_id)
		{
			$users = array_merge($users,$rbacreview->assignedUsers($role_id));
		}
		
		return array_unique($users);
	}



	// Private / Protected
	function __initFileObject()
	{
		if($this->file_obj)
		{
			return $this->file_obj;
		}
		else
		{
			include_once 'classes/class.ilFileDataGroup.php';

			return $this->file_obj = new ilFileDataGroup($this);
		}
	}

	function getMessage()
	{
		return $this->message;
	}
	function setMessage($a_message)
	{
		$this->message = $a_message;
	}
	function appendMessage($a_message)
	{
		if($this->getMessage())
		{
			$this->message .= "<br /> ";
		}
		$this->message .= $a_message;
	}


} //END class.ilObjGroup
?>
