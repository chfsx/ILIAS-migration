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
* Class ilObjGroup
*
* @author Stefan Meyer <smeyer@databay.de> 
* @author Sascha Hofmann <saschahofmann@gmx.de> 
* @version $Id$
* 
* @extends ilObject
* @package ilias-core
*/

//TODO: function getRoleId($groupRole) returns the object-id of grouprole

require_once "class.ilObject.php";

class ilObjGroup extends ilObject
{
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
		$appList = array();
		$q = "SELECT * FROM grp_registration WHERE grp_id=".$this->getId();
		$res = $this->ilias->db->query($q);

		while ($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			array_push($appList,$row);
		}

		return ($appList) ? $appList : false;
	}

	/**
	* deletes an Entry from application list
	* @access	public
	*/
	function deleteApplicationListEntry($a_userId)
	{
		$q = "DELETE FROM grp_registration WHERE user_id=".$a_userId." AND grp_id=".$this->getId();
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
	* get group Members
	* @access	public
	* @param	integer	group id
	* @param	return array of users (obj_ids) that are assigned to the groupspecific roles (grp_member,grp_admin)
	*/
	function getGroupMemberIds($a_grpId="")
	{
		global $rbacadmin, $rbacreview;

		if (!empty($a_grpId) )
		{
			$grp_id = $a_grpId;
		}
		else
		{
			$grp_id = $this->getRefId();
		}
		$usr_arr= array();

		$rol  = $this->getLocalGroupRoles($grp_id);

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
	* @access	public
	* @param	return array [title|id] of roles...
	*/
	function getLocalGroupRoles($a_grp_id="")
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

		$rolf 	   = $rbacreview->getRoleFolderOfObject($this->getRefId());
		$role_arr  = $rbacreview->getRolesOfRoleFolder($rolf["ref_id"]);

		foreach ($role_arr as $role_id)
		{
			if ($rbacreview->isAssignable($role_id,$rolf["ref_id"]) == true)
			{
				$role_Obj =& $this->ilias->obj_factory->getInstanceByObjId($role_id);
				$arr_grpDefaultRoles[$role_Obj->getTitle()] = $role_Obj->getId();
			}
		}

		return $arr_grpDefaultRoles;
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
		$q = "SELECT * FROM grp_data WHERE grp_id='".$this->getId()."'";
		$res = $this->ilias->db->query($q);

		if (!isset($a_regFlag))
		{
			$a_regFlag = 0;
		}

		if ($res->numRows() == 0)
		{
			$q = "INSERT INTO grp_data (grp_id, register) VALUES(".$this->getId().",".$a_regFlag.")";
			$res = $this->ilias->db->query($q);
		}
		else
		{
			$q = "UPDATE grp_data SET register=".$a_regFlag." WHERE grp_id=".$this->getId()."";
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
		$q = "SELECT * FROM grp_data WHERE grp_id='".$this->getId()."'";
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
		$q = "SELECT * FROM grp_data WHERE grp_id='".$this->getId()."'";
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
		$q = "SELECT * FROM grp_data WHERE grp_id='".$this->getId()."'";
		$res = $this->ilias->db->query($q);

		if ($res->numRows() == 0)
		{
			$q = "INSERT INTO grp_data (grp_id, password) VALUES(".$this->getId().",'".$a_password."')";
			$res = $this->ilias->db->query($q);
		}
		else
		{
			$q = "UPDATE grp_data SET password='".$a_password."' WHERE grp_id=".$this->getId()."";
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
		$q = "SELECT * FROM grp_data WHERE grp_id='".$this->getId()."'";
		$res = $this->ilias->db->query($q);
		$date = ilFormat::input2date($a_date);

		if ($res->numRows() == 0)
		{
			$q = "INSERT INTO grp_data (grp_id, expiration) VALUES(".$this->getId().",'".$date."')";
			$res = $this->ilias->db->query($q);
		}
		else
		{
			$q = "UPDATE grp_data SET expiration='".$date."' WHERE grp_id=".$this->getId()."";
			$res = $this->ilias->db->query($q);
		}
	}

	/**
	* get Expiration Date and Time
	* @access	public
	* @param	return array(0=>date, 1=>time)
	*/
	function getExpirationDateTime()
	{
		$q = "SELECT * FROM grp_data WHERE grp_id='".$this->getId()."'";
		$res = $this->ilias->db->query($q);
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		$datetime = $row["expiration"];
		$date = ilFormat::fdateDB2dateDE($datetime);
		$time = substr($row["expiration"], -8);
		$datetime = array(0=>$date, 1=>$time);

		return $datetime;
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
	* @access	public
	* @param	integer	group id (optional)
	* @param	integer group status (0=public|1=private|2=closed)
	*/
	function setGroupStatus($a_grpStatus)
	{
		global $rbacadmin, $rbacreview, $rbacsystem;

		//get Rolefolder of group
		$rolf_data = $rbacreview->getRoleFolderOfObject($this->getRefId());

		//define all relevant roles that rights are needed to be changed
		$arr_globalRoles = array_diff(array_keys($rbacreview->getParentRoleIds($this->getRefId())),$this->getDefaultGroupRoles());

		//group status opened/private
	  	if ($a_grpStatus == 0 )//|| $a_grpStatus == 1)
		{
			//get defined operations on object group depending on group status "CLOSED"->template 'il_grp_status_closed'
			$arr_ops = $rbacreview->getOperationsOfRole($this->getGrpStatusOpenTemplateId(), 'grp', ROLE_FOLDER_ID);

			foreach ($arr_globalRoles as $globalRole)
			{
				//initialize permissionarray
				$granted_permissions = array();
				//delete old rolepermissions in rbac_fa
				$rbacadmin->deleteLocalRole($globalRole,$rolf_data["child"]);
				//revoke all permission on current group object for global role
				$rbacadmin->revokePermission($this->getRefId(), $globalRole);
				//grant new permissions according to group status
				//$rbacadmin->grantPermission($globalRole,$arr_ops, $this->getRefId());
				$ops_of_role = $rbacreview->getOperationsOfRole($globalRole,"grp", ROLE_FOLDER_ID);

				if (in_array(2,$ops_of_role)) //VISIBLE permission is set for global role and group
				{
					array_push($granted_permissions,"2");
				}

				if (in_array(7,$ops_of_role)) //JOIN permission is set for global role and group
				{
					array_push($granted_permissions,"7");
				}

				if (!empty($granted_permissions))
				{
					$rbacadmin->grantPermission($globalRole, $granted_permissions, $this->getRefId());
				}

				//copy permissiondefinitions of openGroup_template
				$rbacadmin->copyRolePermission($this->getGrpStatusOpenTemplateId(),ROLE_FOLDER_ID,$rolf_data["child"],$globalRole);			//RollenTemplateId, Rollenfolder von Template (->8),RollenfolderRefId von Gruppe,Rolle die Rechte Ã¼bernehmen soll
				$rbacadmin->assignRoleToFolder($globalRole,$rolf_data["child"],"false");
			}//END foreach
		}

		//group status closed
	  	if ($a_grpStatus == 1)
		{
			//get defined operations on object group depending on group status "CLOSED"->template 'il_grp_status_closed'
			$arr_ops = $rbacreview->getOperationsOfRole($this->getGrpStatusClosedTemplateId(), 'grp', ROLE_FOLDER_ID);

			foreach ($arr_globalRoles as $globalRole)
			{
				//delete old rolepermissions in rbac_fa
				$rbacadmin->deleteLocalRole($globalRole,$rolf_data["child"]);
				//revoke all permission on current group object for all(!) global roles, may be a workaround
				$rbacadmin->revokePermission($this->getRefId(), $globalRole);//refid des grpobjektes,dass rechte aberkannt werden, opti.:roleid, wenn nur dieser rechte aberkannt...
				//set permissions of global role (admin,author,guest,learner) for group object
				$rbacadmin->grantPermission($globalRole,$arr_ops, $this->getRefId());//rollenid,operationen,refid des objektes auf das rechte gesetzt werden
				//copy permissiondefinitions of closedGroup_template
				$rbacadmin->copyRolePermission($this->getGrpStatusClosedTemplateId(),ROLE_FOLDER_ID,$rolf_data["child"],$globalRole);			//RollenTemplateId, Rollenfolder von Template (->8),RollenfolderRefId von Gruppe,Rolle die Rechte Ã¼bernehmen soll
				$rbacadmin->assignRoleToFolder($globalRole,$rolf_data["child"],"false");
			}//END foreach
		}
	}

	/**
	* get group status, redundant method because
	* @access	public
	* @param	return group status[0=public|2=closed]
	*/
	function getGroupStatus()
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
				return 0;
			}
		}

		return 1;
	}

	/**
	* get group member status
	* @access	public
	* @param	returns array of obj_ids of assigned local roles
	*/
	function getMemberRoles($a_user_id, $a_grp_id="")
	{
		global $rbacadmin, $rbacreview;

		$arr_assignedRoles = array();

		if (strlen($a_grp_id) > 0)
		{
			$grp_id = $a_grp_id;
		}
		else
		{
			$grp_id = $this->getRefId();
		}

		$roles = $this->getLocalGroupRoles($grp_id);

		foreach ($roles as $role)
		{
			if (in_array($a_user_id,$rbacreview->assignedUsers($role)))
			{
				array_push($arr_assignedRoles, $role);
			}
		}

		return $arr_assignedRoles;
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
	* copy all properties and subobjects of a group.
	* Does not copy the settings in the group's local role folder. Instead a new local role folder is created from
	* the template settings (same process as creating a new group manually)
	* 
	* @access	public
	* @return	integer	new ref id
	*/
	function clone($a_parent_ref)
	{
		global $rbacadmin;

		// always call parent clone function first!!
		$new_ref_id = parent::clone($a_parent_ref);
		
		// get object instance of cloned group
		$groupObj =& $this->ilias->obj_factory->getInstanceByRefId($new_ref_id);
		
		// find a free number
		for ($n = 1;$n < 99;$n++)
		{
			$groupname_copy = $groupObj->getTitle()."_(copy_".$n.")";

			if (!ilUtil::groupNameExists($groupname_copy))
			{
				$groupObj->setTitle($groupname_copy);
				$groupObj->update();
				break;
			}
		}

		// setup rolefolder & default local roles (admin & member)
		$roles = $groupObj->initDefaultRoles();

		// ...finally assign groupadmin role to creator of group object
		$rbacadmin->assignUser($roles[0], $groupObj->getOwner(), "n");
		ilObjUser::updateActiveRoles($groupObj->getOwner());

		// TODO: function getGroupStatus returns integer but setGroupStatus expects a string.
		// I disabled this function. Please investigate
		// shofmann@databay.de	4.7.03
		// copy group status
		// 0=public/visible for all,1=closed/invisible for all
		$groupObj->setGroupStatus($this->getGroupStatus());

		// always destroy objects in clone method because clone() is recursive and creates instances for each object in subtree!
		unset($groupObj);
		unset($rfoldObj);
		unset($roleObj);

		// ... and finally always return new reference ID!!
		return $new_ref_id;
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
		// always call parent delete function first!!
		if (!parent::delete())
		{
			return false;
		}
		
		$query = "DELETE FROM grp_data WHERE grp_id=".$this->getId();
		$this->ilias->db->query($query);

		return true;
	}

	/**
	* init default roles settings
	* @access	public
	* @return	array	object IDs of created local roles.
	*/
	function initDefaultRoles()
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
		$ops = $rbacreview->getOperationsOfRole($roleObj->getId(),"rolf",$rfoldObj->getRefId());
		$rbacadmin->grantPermission($roleObj->getId(),$ops,$rfoldObj->getRefId());

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
		$ops = $rbacreview->getOperationsOfRole($roleObj->getId(),"rolf",$rfoldObj->getRefId());
		$rbacadmin->grantPermission($roleObj->getId(),$ops,$rfoldObj->getRefId());

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
	
	function isUserRegistered($a_user_id = 0)
	{
		global $rbacsystem;
		
		// exclude system role from check
		if (in_array(SYSTEM_ROLE_ID,$_SESSION["RoleId"]))
		{
			return true;		
		}

		if (!$this->isMember() or !$rbacsystem->checkAccess("join", $this->ref_id))
		{
			return false;
		}
		
		return true;
	}
} //END class.ilObjGroup
?>
