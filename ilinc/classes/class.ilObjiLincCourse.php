<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
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
* Class ilObjiLincCourse
* 
* @author Sascha Hofmann <saschahofmann@gmx.de> 
*
* @version $Id$
*
* @extends ilObject
* @package ilias-core
*/

require_once ('./classes/class.ilObject.php');
require_once ('class.ilnetucateXMLAPI.php');

class ilObjiLincCourse extends ilObject
{
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjiLincCourse($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "icrs";
		$this->ilObject($a_id,$a_call_by_reference);
		$this->setRegisterMode(false);
		$this->ilincAPI = new ilnetucateXMLAPI();
		
		$this->docent_ids = array();
		$this->student_ids = array();
	}
	
	/**
	* 
	* @access private
	*/
	function read()
	{
		parent::read();
		
		// TODO: fetching default role should be done in rbacadmin
		$q = "SELECT * FROM ilinc_data ".
			 "WHERE obj_id='".$this->id."'";
		$r = $this->ilias->db->query($q);

		if ($r->numRows() > 0)
		{
			$data = $r->fetchRow(DB_FETCHMODE_OBJECT);

			$this->ilinc_id = $data->course_id;
			$this->activated = ilUtil::yn2tf($data->activation_offline);
		}
		else
		{
			 $this->ilias->raiseError("<b>Error: There is no dataset with id ".$this->id."!</b><br />class: ".get_class($this)."<br />Script: ".__FILE__."<br />Line: ".__LINE__, $this->ilias->FATAL);
		}
	}
	
	function getiLincId()
	{
		return $this->ilinc_id;
	}
	
	function getErrorMsg()
	{
		$err_msg = $this->error_msg;
		$this->error_msg = "";

		return $err_msg;
	}

	/**
	* update object data
	*
	* @access	public
	* @return	boolean
	*/
	function update()
	{
		$this->ilincAPI->editCourse($this->getiLincId(),$_POST["Fobject"]);
		$response = $this->ilincAPI->sendRequest();
		
		if ($response->isError())
		{
			$this->error_msg = $response->getErrorMsg();
			return false;
		}


		if (!parent::update())
		{			
			$this->error_msg = "database_error";
			return false;
		}
		
		$q = "UPDATE ilinc_data SET activation_offline='".$this->activated."' WHERE obj_id=".$this->getId()."";
		$r = $this->ilias->db->query($q);
		
		return true;
	}
	
	/**
	* create course on iLinc server
	*
	* @access	public
	* @return	boolean
	*/
	function addCourse()
	{
		$this->ilincAPI->addCourse($_POST["Fobject"]);
		$response = $this->ilincAPI->sendRequest();
		
		if ($response->isError())
		{
			$this->error_msg = $response->getErrorMsg();
			return false;
		}
		
		$this->ilinc_id = $response->getFirstID();
		
		return true;
	}
	
	/**
	* copy all entries of your object.
	* 
	* @access	public
	* @param	integer	ref_id of parent object
	* @return	integer	new ref id
	*/
	function ilClone($a_parent_ref)
	{		
		global $rbacadmin;

		// always call parent clone function first!!
		$new_ref_id = parent::ilClone($a_parent_ref);
		
		// get object instance of cloned object
		//$newObj =& $this->ilias->obj_factory->getInstanceByRefId($new_ref_id);

		// create a local role folder & default roles
		//$roles = $newObj->initDefaultRoles();

		// ...finally assign role to creator of object
		//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "n");		

		// always destroy objects in clone method because clone() is recursive and creates instances for each object in subtree!
		//unset($newObj);

		// ... and finally always return new reference ID!!
		return $new_ref_id;
	}

	/**
	* delete object and all related data	
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
		
		//put here your module specific stuff
		$q = "DELETE FROM ilinc_data WHERE course_id='".$this->getiLincId()."'";
		$this->ilias->db->query($q);
		
		// TODO: delete data in ilinc_registration table
		
		// remove course from ilinc server
		$this->ilincAPI->removeCourse($this->getiLincId());
		$response = $this->ilincAPI->sendRequest();

		return true;
	}
	
	// store iLinc Id in ILIAS and set variable
	function storeiLincId($a_icrs_id)
	{
		$q = "INSERT INTO ilinc_data (obj_id,type,course_id,activation_offline) VALUES (".$this->id.",'icrs','".$a_icrs_id."','".$this->activated."')";
		$this->ilias->db->query($q);
		
		$this->ilinc_id = $a_icrs_id;
	}
	
	// saveActivationStatus()
	function saveActivationStatus($a_activated)
	{
		$q = "UPDATE ilinc_data SET activation_offline='".$a_activated."' WHERE obj_id=".$this->getId()."";
		$r = $this->ilias->db->query($q);
	}
	
	/**
	* init default roles settings
	* 
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
		$roleObj = $rfoldObj->createRole("il_icrs_admin_".$this->getRefId(),"LearnLinc admin of seminar obj_no.".$this->getId());
		$this->m_roleAdminId = $roleObj->getId();

		//set permission template of new local role
		$q = "SELECT obj_id FROM object_data WHERE type='rolt' AND title='il_icrs_admin'";
		$r = $this->ilias->db->getRow($q, DB_FETCHMODE_OBJECT);
		$rbacadmin->copyRolePermission($r->obj_id,ROLE_FOLDER_ID,$rfoldObj->getRefId(),$roleObj->getId());

		// set object permissions of icrs object
		$ops = $rbacreview->getOperationsOfRole($roleObj->getId(),"icrs",$rfoldObj->getRefId());
		$rbacadmin->grantPermission($roleObj->getId(),$ops,$this->getRefId());

		// set object permissions of role folder object
		//$ops = $rbacreview->getOperationsOfRole($roleObj->getId(),"rolf",$rfoldObj->getRefId());
		//$rbacadmin->grantPermission($roleObj->getId(),$ops,$rfoldObj->getRefId());

		// MEMBER ROLE
		// create role and assign role to rolefolder...
		$roleObj = $rfoldObj->createRole("il_icrs_member_".$this->getRefId(),"LearnLinc admin of seminar obj_no.".$this->getId());
		$this->m_roleMemberId = $roleObj->getId();

		//set permission template of new local role
		$q = "SELECT obj_id FROM object_data WHERE type='rolt' AND title='il_icrs_member'";
		$r = $this->ilias->db->getRow($q, DB_FETCHMODE_OBJECT);
		$rbacadmin->copyRolePermission($r->obj_id,ROLE_FOLDER_ID,$rfoldObj->getRefId(),$roleObj->getId());
		
		// set object permissions of icrs object
		$ops = $rbacreview->getOperationsOfRole($roleObj->getId(),"icrs",$rfoldObj->getRefId());
		$rbacadmin->grantPermission($roleObj->getId(),$ops,$this->getRefId());

		// set object permissions of role folder object
		//$ops = $rbacreview->getOperationsOfRole($roleObj->getId(),"rolf",$rfoldObj->getRefId());
		//$rbacadmin->grantPermission($roleObj->getId(),$ops,$rfoldObj->getRefId());

		unset($rfoldObj);
		unset($roleObj);

		$roles[] = $this->m_roleAdminId;
		$roles[] = $this->m_roleMemberId;
		
		// Break inheritance and initialize permission settings using intersection method with a non_member_template 
		// not implemented for ilinc. maybe never will...
		$this->__setCourseStatus();
		
		return $roles ? $roles : array();
	}

	/**
	* notifys an object about an event occured
	* Based on the event happend, each object may decide how it reacts.
	* 
	* If you are not required to handle any events related to your module, just delete this method.
	* (For an example how this method is used, look at ilObjGroup)
	* 
	* @access	public
	* @param	string	event
	* @param	integer	reference id of object where the event occured
	* @param	array	passes optional parameters if required
	* @return	boolean
	*/
	function notify($a_event,$a_ref_id,$a_parent_non_rbac_id,$a_node_id,$a_params = 0)
	{
		return true;
	}
	
	/**
	* add Member to iLic course
	* @access	public
	* @param	integer	user_id
	* @param	integer	member role_id of local group_role
	* @param	boolean	register member on iLinc server as student(false) or docent(true)
	*/
	function addMember(&$a_user_obj, $a_mem_role, $a_instructor = false)
	{
		global $rbacadmin;
//echo "0";
		if (!isset($a_user_obj) && !isset($a_mem_role))
		{
			$this->error_msg = get_class($this)."::addMember(): Missing parameters !";
			return false;
		}
//echo "1";
		// check if user is registered at iLinc server
		if (!$this->userExists($a_user_obj))
		{
			// if not, add user on iLinc server
			if ($this->addUser($a_user_obj) == false)
			{
				// error_msg already set
				return false;
			}
		}
//echo "2";
		// assign membership to icourse on iLinc server
		if (!$this->registerUser($a_user_obj,$a_instructor))
		{
			// error_msg already set
			return false;
		}
//echo "3";
		// finally assign user to member role in ILIAS
		$this->join($a_user_obj->getId(),$a_mem_role);
//echo "4";
		return true;
	}

	/**
	* join icrs, assigns user to role
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
	* get group member status
	* @access	public
	* @param	integer	user_id
	* @return	returns array of obj_ids of assigned local roles
	*/
	function getMemberRoles($a_user_id)
	{
		global $rbacadmin, $rbacreview;

		$arr_assignedRoles = array();

		$arr_assignedRoles = array_intersect($rbacreview->assignedRoles($a_user_id),$this->getLocalRoles());

		return $arr_assignedRoles;
	}
	
	/**
	* get all group Member ids regardless of role
	* @access	public
	* @return	return array of users (obj_ids) that are assigned to
	* the groupspecific roles (grp_member,grp_admin)
	*/
	function getMemberIds()
	{
		global $rbacadmin, $rbacreview;

		$usr_arr= array();

		$rol  = $this->getLocalRoles();

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
	function getMemberData($a_mem_ids, $active = 1)
	{
		global $rbacadmin, $rbacreview, $ilBench, $ilDB;

		$usr_arr= array();
		
		$q = "SELECT login,firstname,lastname,title,usr_id,ilinc_id ".
			 "FROM usr_data ".
			 "WHERE usr_id IN (".implode(',',$a_mem_ids).")";
			 
  		if (is_numeric($active) && $active > -1)
  			$q .= "AND active = '$active'";			 
		
  		$r = $ilDB->query($q);
		
		while($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$mem_arr[] = array("id" => $row->usr_id,
								"login" => $row->login,
								"firstname" => $row->firstname,
								"lastname" => $row->lastname,
								"ilinc_id" => $row->ilinc_id
								);
		}

		return $mem_arr ? $mem_arr : array();
	}
	
	/**
	* get ALL local roles of group, also those created and defined afterwards
	* only fetch data once from database. info is stored in object variable
	* @access	public
	* @return	return array [title|id] of roles...
	*/
	function getLocalRoles($a_translate = false)
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
	* get group member status
	* @access	public
	* @param	integer	user_id
	* @return	returns string of role titles
	*/
	function getMemberRolesTitle($a_user_id)
	{
		global $ilDB,$ilBench;
		
		include_once ('classes/class.ilObjRole.php');

		$str_member_roles ="";

		$q = "SELECT title ".
			 "FROM object_data ".
			 "LEFT JOIN rbac_ua ON object_data.obj_id=rbac_ua.rol_id ".
			 "WHERE object_data.type = 'role' ".
			 "AND rbac_ua.usr_id = ".$ilDB->quote($a_user_id)." ".
			 "AND rbac_ua.rol_id IN (".implode(',',$this->getLocalRoles()).")";

		$r = $ilDB->query($q);

		while($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			// display human readable role names for autogenerated roles
			$str_member_roles .= ilObjRole::_getTranslation($row["title"]).", ";
		}

		return substr($str_member_roles,0,-2);
	}
	
	/**
	* returns object id of created default member role
	* @access	public
	*/
	function getDefaultMemberRole()
	{
		$local_icrs_Roles = $this->getLocalRoles();

		return $local_icrs_Roles["il_icrs_member_".$this->getRefId()];
	}

	/**
	* returns object id of created default adminstrator role
	* @access	public
	*/
	function getDefaultAdminRole()
	{
		$local_icrs_Roles = $this->getLocalRoles();

		return $local_icrs_Roles["il_icrs_admin_".$this->getRefId()];
	}
	
	function getClassrooms()
	{
		global $ilErr;
		
		if (!$this->ilias->getSetting("ilinc_active"))
		{
			$this->error_msg = "ilinc_server_not_active";
			return false;
		}

		$this->ilincAPI->findCourseClasses($this->getiLincId());
		$response = $this->ilincAPI->sendRequest();

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_get_classrooms";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}

		if (!$response->data['classes'])
		{

			$this->error_msg = $response->data['result']['cdata'];
			return false;
		}

		//echo "3";
		foreach ($response->data['classes'] as $class_id => $data)
		{
			$this->ilincAPI->findClass($class_id);
			$response = $this->ilincAPI->sendRequest("findClass");

			if ($response->data['classes'])
			{
				$full_class_data[$class_id] = $response->data['classes'][$class_id];
			}
		}
		
		return $full_class_data;
	}
	
	// checks if user is already registered at iLinc server
	// TODO: check is only local in ILIAS not on iLinc server
	function userExists(&$a_user_obj)
	{
		$data = $a_user_obj->getiLincData();
		
		if (empty($data["id"]) and empty($data["login"]))
		{
			return false;
		}
		
		return true;
	}
	
	// create user account on iLinc server
	function addUser(&$a_user_obj)
	{
		// create login and passwd for iLinc account
		$login_data = $this->__createLoginData($a_user_obj->getId(),$a_user_obj->getLogin(),$this->ilias->getSetting($inst_id));
		
		$this->ilincAPI->addUser($login_data,$a_user_obj);
		$response = $this->ilincAPI->sendRequest();

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_add_user";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		$ilinc_user_id = $response->getFirstID();
		$a_user_obj->setiLincData($ilinc_user_id,$login_data["login"],$login_data["passwd"]);
		$a_user_obj->update();
		
		return true;
	}

	/**
	 * creates login and password for ilinc
	 * login format is: <first 3 letter of ilias login> _ <user_id> _ <inst_id> _ <timestamp>
	 * passwd format is a random md5 hash
	 * 
	 */
	function __createLoginData($a_user_id,$a_user_login,$a_inst_id)
	{
		if (!$a_inst_id)
		{
			$a_inst_id = "0";
		}

		$data["login"] = substr($a_user_login,0,3)."_".$a_user_id."_".$a_inst_id."_".time();
		$data["passwd"] = md5(microtime().$a_user_login.rand(10000, 32000));
		
		return $data;
	}
	
	function isMember($a_user_id = "")
	{
		if (strlen($a_user_id) == 0)
		{
			$a_user_id = $this->ilias->account->getId();
		}

		$arr_members = $this->getMemberIds();

		if (in_array($a_user_id, $arr_members))
		{
			return true;
		}
		
		return false;
	}
	
	function isDocent($a_user_obj = "")
	{
		if (!$a_user_obj)
		{
			$a_user_obj =& $this->ilias->account;
		}
		
		$docents = $this->getiLincMemberIds(true);
		
		$ilinc_data = $a_user_obj->getiLincData();
		
		if (in_array($ilinc_data['id'],$docents))
		{
			return true;
		}
		
		return false;
	}
	
	function registerUser(&$a_user_obj,$a_instructor = false)
	{
		if ($a_instructor === true)
		{
			$a_instructor = "True";
		}
		else
		{
			$a_instructor = "False";
		}

		$ilinc_data = $a_user_obj->getiLincData();
		$user[] = array('id' => $ilinc_data['id'], 'instructor' => $a_instructor);
		$this->ilincAPI->registerUser($this->getiLincId(),$user);
		$response = $this->ilincAPI->sendRequest("registerUser");
		
//var_dump($response->data);exit;

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_register_user";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		return true;
	}
	
	function registerUsers($a_user_arr)
	{
		foreach ($a_user_arr as $user_id => $instructorflag)
		{
			$flag = "False";
			
			if ($instructorflag == ILINC_MEMBER_DOCENT)
			{
				$flag = "True";
			}
			
			$ilinc_users[] = array('id' => $user_id,'instructor' => $flag);
		}
		
		$this->ilincAPI->registerUser($this->getiLincId(),$ilinc_users);
		$response = $this->ilincAPI->sendRequest("registerUser");

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_register_users";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		return true;
	}
	
	// unregister user from course on iLinc server
	function unregisterUser($a_user_obj)
	{
		$ilinc_data = $a_user_obj->getiLincData();
		
		// do not send request if user is not registered at iLinc server at all
		if ($ilinc_data['id'] == '0')
		{
			return true;
		}
		
		$this->ilincAPI->unregisterUser($this->getiLincId(),array($ilinc_data['id']));
		$response = $this->ilincAPI->sendRequest();

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_unregister_user";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		return true;
	}
	
	function unregisterUsers($a_ilinc_user_ids)
	{
		$this->ilincAPI->unregisterUser($this->getiLincId(),$a_ilinc_user_ids);
		$response = $this->ilincAPI->sendRequest();
		
		//var_dump($response->data);exit;
		
		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_unregister_users";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		return true;
	}
	
	function userLogin(&$a_user_obj)
	{
		$this->ilincAPI->userLogin($a_user_obj);
		$response = $this->ilincAPI->sendRequest("userLogin");

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_user_login";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		// return URL to join class room
		return $response->data['url']['cdata'];
	}
	
	// not used here
	function uploadPicture(&$a_user_obj,$a_lang)
	{
		$this->ilincAPI->uploadPicture($a_user_obj,$a_lang);
		$response = $this->ilincAPI->sendRequest("uploadPicture");

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_upload_picture";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		// return URL to user's personal page
		return $response->data['url']['cdata'];
	}
	
	/**
	* get Group Admin Id
	* @access	public
	* @param	integer	group id
	* @param	returns userids that are assigned to a group administrator! role
	*/
	function getAdminIds($a_grpId="")
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
		$roles = $this->getDefaultRoles($this->getRefId());

		foreach ($rbacreview->assignedUsers($this->getDefaultAdminRole()) as $member_id)
		{
			array_push($usr_arr,$member_id);
		}

		return $usr_arr;
	}
	
	/**
	* removes Member from group
	* @access	public
	*/
	function removeMember(&$a_user_obj)
	{
		if (!isset($a_user_obj))
		{
			$this->error_msg = get_class($this)."::removeMember(): Missing parameters !";
			return false;
		}
		
		if (!$this->isMember($a_user_obj->getId()))
		{
			return true;
		}

		if (count($this->getMemberIds()) > 1)
		{
			if ($this->isAdmin($a_user_obj->getId()) && count($this->getAdminIds()) < 2)
			{
				$this->error_msg = "ilinc_err_administrator_required";
				return false;
			}
		}
		
		// unregister from course on iLinc server
		if (!$this->unregisterUser($a_user_obj))
		{
			// error_msg already set
			return false;
		}

		$this->leave($a_user_obj->getId());

		return true;
	}

	/**
	* is Admin
	* @access	public
	* @param	integer	user_id
	* @param	boolean, true if user is group administrator
	*/
	function isAdmin($a_user_id)
	{
		global $rbacreview;

		$icrs_roles = $this->getDefaultRoles();

		if (in_array($a_user_id,$rbacreview->assignedUsers($icrs_roles["icrs_admin_role"])))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	* get default group roles, returns the defaultlike create roles il_grp_member, il_grp_admin
	* @access	public
	* @param 	returns the obj_ids of group specific roles(il_grp_member,il_grp_admin)
	*/
	function getDefaultRoles($a_grp_id="")
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

			$grp_Member ="il_icrs_member_".$grp_id;
			$grp_Admin  ="il_icrs_admin_".$grp_id;

			if (strcmp($role_Obj->getTitle(), $grp_Member) == 0 )
			{
				$arr_grpDefaultRoles["icrs_member_role"] = $role_Obj->getId();
			}

			if (strcmp($role_Obj->getTitle(), $grp_Admin) == 0)
			{
				$arr_grpDefaultRoles["icrs_admin_role"] = $role_Obj->getId();
			}
		}

		return $arr_grpDefaultRoles;
	}
	
	// returns ilinc_user_ids of course (students=false,docents=true)
	function getiLincMemberIds($a_instructorflag = false)
	{
		if ($a_instructorflag == true)
		{
			if ($this->docent_ids)
			{
				return $this->docent_ids;
			}
		}
		else
		{
			if ($this->student_ids)
			{
				return $this->student_ids;
			}
		}
		
		$this->ilincAPI->findRegisteredUsersByRole($this->getiLincId(),$a_instructorflag);
		$response = $this->ilincAPI->sendRequest();
			
		if (is_array($response->data['users']))
		{
			if ($a_instructorflag == true)
			{
				$this->docent_ids = array_keys($response->data['users']);
			}
			else
			{
				$this->student_ids = array_keys($response->data['users']);
			}

			return array_keys($response->data['users']);
		}
		
		return array();
	}
	
	function checkiLincMemberStatus($a_ilinc_user_id,$a_docent_ids,$a_student_ids)
	{
		if (in_array($a_ilinc_user_id,$a_docent_ids))
		{
			return ILINC_MEMBER_DOCENT;
		}
		
		if (in_array($a_ilinc_user_id,$a_student_ids))
		{
			return ILINC_MEMBER_STUDENT;
		}
			
		return ILINC_MEMBER_NOTSET;
	}
	
	function _isActivated($a_course_obj_id)
	{
		global $ilDB,$ilias;

		if (!$ilias->getSetting("ilinc_active"))
		{
			return false;
		}
		
		$q = "SELECT activation_offline FROM ilinc_data WHERE obj_id=".$ilDB->quote($a_course_obj_id);
		$r = $ilDB->query($q);
		
		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

		return ilUtil::yn2tf($row->activation_offline);
	}
	
	function _isMember($a_user_id,$a_ref_id)
	{
		global $rbacreview;
		
		$rolf = $rbacreview->getRoleFolderOfObject($a_ref_id);
		$local_roles = $rbacreview->getRolesOfRoleFolder($rolf["ref_id"],false);
		$user_roles = $rbacreview->assignedRoles($a_user_id);
		
		if (!array_intersect($local_roles,$user_roles))
		{
			return false;
		}
		
		return true;
	}
	
	function __setCourseStatus()
	{
		// empty
	}
} // END class.ilObjiLincCourse
?>
