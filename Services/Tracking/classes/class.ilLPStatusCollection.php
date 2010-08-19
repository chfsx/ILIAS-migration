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
* @author Stefan Meyer <meyer@leifos.com>
*
* @version $Id$
*
* @package ilias-tracking
*
*/

include_once './Services/Tracking/classes/class.ilLPStatus.php';
include_once './Services/Tracking/classes/class.ilLPStatusWrapper.php';

class ilLPStatusCollection extends ilLPStatus
{

	function ilLPStatusCollection($a_obj_id)
	{
		global $ilDB;

		parent::ilLPStatus($a_obj_id);
		$this->db =& $ilDB;
	}

	function _getNotAttempted($a_obj_id)
	{
		global $ilObjDataCache,$tree;

		switch($ilObjDataCache->lookupType($a_obj_id))
		{
			case 'crs':
				include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
				$member_obj = ilCourseParticipants::_getInstanceByObjId($a_obj_id);
				$members = $member_obj->getMembers();
				
				// diff in progress and completed (use stored result in LPStatusWrapper)
				$users = array_diff((array) $members, ilLPStatusWrapper::_getInProgress($a_obj_id));
				$users = array_diff((array) $users, ilLPStatusWrapper::_getCompleted($a_obj_id));
				$users = array_diff((array) $users, ilLPStatusWrapper::_getFailed($a_obj_id));
				return $users;

			case 'grp':
				include_once './Modules/Group/classes/class.ilObjGroup.php';
				$members = ilObjGroup::_getMembers($a_obj_id);

				// diff in progress and completed (use stored result in LPStatusWrapper)
				$users = array_diff((array) $members, ilLPStatusWrapper::_getInProgress($a_obj_id));
				$users = array_diff((array) $users, ilLPStatusWrapper::_getCompleted($a_obj_id));
				$users = array_diff((array) $users, ilLPStatusWrapper::_getFailed($a_obj_id));
				return $users;
				
			case 'fold':
				$folder_ref_ids = ilObject::_getAllReferences($a_obj_id);
				$folder_ref_id = current($folder_ref_ids);
				if($crs_id = $tree->checkForParentType($folder_ref_id,'crs'))
				{
					include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
					$member_obj = ilCourseParticipants::_getInstanceByObjId(ilObject::_lookupObjId($crs_id));
					$members = $member_obj->getMembers();
				
					// diff in progress and completed (use stored result in LPStatusWrapper)
					$users = array_diff((array) $members,ilLPStatusWrapper::_getInProgress($a_obj_id));
					$users = array_diff((array) $users, ilLPStatusWrapper::_getCompleted($a_obj_id));
					$users = array_diff((array) $users, ilLPStatusWrapper::_getFailed($a_obj_id));
					return $users;
				}
				break;
				
			default:
				return array();
		}
	}

	function _getInProgress($a_obj_id)
	{
		global $tree;
		
		include_once './Services/Tracking/classes/class.ilLPCollectionCache.php';

		global $ilBench,$ilObjDataCache;
		$ilBench->start('LearningProgress','9172_LPStatusCollection_inProgress');

		$in_progress = 0;
		foreach(ilLPCollectionCache::_getItems($a_obj_id) as $item_id)
		{
			$item_id = $ilObjDataCache->lookupObjId($item_id);

			// merge arrays of users with status 'in progress'
			$users = array_unique(array_merge((array) $users,ilLPStatusWrapper::_getInProgress($item_id)));
			$users = array_unique(array_merge((array) $users,ilLPStatusWrapper::_getCompleted($item_id)));
		}

		// Exclude all users with status completed.
		$users = array_diff((array) $users,ilLPStatusWrapper::_getCompleted($a_obj_id));
		// Exclude all users with status failed.
		$users = array_diff((array) $users,ilLPStatusWrapper::_getFailed($a_obj_id));

		switch($ilObjDataCache->lookupType($a_obj_id))
		{
			case 'crs':
				// Exclude all non members
				include_once './Modules/Course/classes/class.ilCourseParticipants.php';
				$members_obj = ilCourseParticipants::_getInstanceByObjId($a_obj_id);
				$members = $members_obj->getMembers();
				$users = array_intersect($members,(array) $users);
				break;
				
			case 'grp':
				include_once './Modules/Group/classes/class.ilObjGroup.php';
				$members = ilObjGroup::_getMembers($a_obj_id);
				$users = array_intersect($members,(array) $users);
				break;
				
			case 'fold':
				$folder_ref_ids = ilObject::_getAllReferences($a_obj_id);
				$folder_ref_id = current($folder_ref_ids);
				if($crs_id = $tree->checkForParentType($folder_ref_id,'crs'))
				{
					include_once './Modules/Course/classes/class.ilCourseParticipants.php';
					$members_obj = ilCourseParticipants::_getInstanceByObjId(ilObject::_lookupObjId($crs_id));
					$members = $members_obj->getMembers();
					$users = array_intersect($members,(array) $users);
				}
				break;
		}

		$ilBench->stop('LearningProgress','9172_LPStatusCollection_inProgress');
		return $users;
	}

	function _getCompleted($a_obj_id)
	{
		include_once './Services/Tracking/classes/class.ilLPCollectionCache.php';

		global $ilBench,$ilObjDataCache,$tree;
		$ilBench->start('LearningProgress','9173_LPStatusCollection_completed');

		$counter = 0;
		$users = array();
		foreach(ilLPCollectionCache::_getItems($a_obj_id) as $item_id)
		{
			$item_id = $ilObjDataCache->lookupObjId($item_id);

			$tmp_users = ilLPStatusWrapper::_getCompleted($item_id);
			if(!$counter++)
			{
				$users = $tmp_users;
			}
			else
			{
				$users = array_intersect($users,$tmp_users);
			}

		}

		switch($ilObjDataCache->lookupType($a_obj_id))
		{
			case 'crs':
				// Exclude all non members
				include_once './Modules/Course/classes/class.ilCourseParticipants.php';
				$member_obj = ilCourseParticipants::_getInstanceByObjId($a_obj_id);
				$users = array_intersect($member_obj->getMembers(),(array) $users);
				break;

			case 'grp':
				include_once './Modules/Group/classes/class.ilObjGroup.php';
				$members = ilObjGroup::_getMembers($a_obj_id);
				$users = array_intersect($members,(array) $users);
				break;
				
			case 'fold':
				$folder_ref_ids = ilObject::_getAllReferences($a_obj_id);
				$folder_ref_id = current($folder_ref_ids);
				if($crs_id = $tree->checkForParentType($folder_ref_id,'crs'))
				{
					include_once './Modules/Course/classes/class.ilCourseParticipants.php';
					$members_obj = ilCourseParticipants::_getInstanceByObjId(ilObject::_lookupObjId($crs_id));
					$users = array_intersect($members_obj->getMembers(),(array) $users);
				}
				break;
				
		}
		$users = array_diff($users,ilLPStatusWrapper::_getFailed($a_obj_id));
		$ilBench->stop('LearningProgress','9173_LPStatusCollection_completed');
		return (array) $users;
	}

	function _getFailed($a_obj_id)
	{
		global $ilObjDataCache,$tree;

		include_once './Services/Tracking/classes/class.ilLPCollectionCache.php';

		$users = array();
		foreach(ilLPCollectionCache::_getItems($a_obj_id) as $item_id)
		{
			$item_id = $ilObjDataCache->lookupObjId($item_id);
			$tmp_users = ilLPStatusWrapper::_getFailed($item_id);
			$users = array_merge($users,$tmp_users);
		}
		
		switch($ilObjDataCache->lookupType($a_obj_id))
		{
			case 'crs':
				// Exclude all non members
				include_once './Modules/Course/classes/class.ilCourseParticipants.php';
				$members_obj = ilCourseParticipants::_getInstanceByObjId($a_obj_id);
				$members = $members_obj->getMembers();
		
				$users = array_intersect($members,(array) $users);
				break;
				
			case 'grp':
				include_once './Modules/Group/classes/class.ilObjGroup.php';
				$members = ilObjGroup::_getMembers($a_obj_id);
				$users = array_intersect($members,(array) $users);
				break;

			case 'fold':
				$folder_ref_ids = ilObject::_getAllReferences($a_obj_id);
				$folder_ref_id = current($folder_ref_ids);
				if($crs_id = $tree->checkForParentType($folder_ref_id,'crs'))
				{
					$members_obj = ilCourseParticipants::_getInstanceByObjId(ilObject::_lookupObjId($crs_id));
					$members = $members_obj->getMembers();
					$users = array_intersect($members,(array) $users);
				}
				break;
		}
		
		return array_unique($users);
	}
		

	function _getStatusInfo($a_obj_id)
	{
		include_once './Services/Tracking/classes/class.ilLPCollectionCache.php';

		$status_info['collections'] = ilLPCollectionCache::_getItems($a_obj_id);
		$status_info['num_collections'] = count($status_info['collections']);
		return $status_info;
	}

	function _getTypicalLearningTime($a_obj_id)
	{
		global $ilObjDataCache;

		if($ilObjDataCache->lookupType($a_obj_id) == 'sahs')
		{
			return parent::_getTypicalLearningTime($a_obj_id);
		}

		$status_info = ilLPStatusWrapper::_getStatusInfo($a_obj_id);
		foreach($status_info['collections'] as $item)
		{
			$tlt += ilLPStatusWrapper::_getTypicalLearningTime($ilObjDataCache->lookupObjId($item));
		}
		return $tlt;
	}

	/**
	 * Determine status
	 *
	 * @param	integer		object id
	 * @param	integer		user id
	 * @param	object		object (optional depends on object type)
	 * @return	integer		status
	 */
	function determineStatus($a_obj_id, $a_user_id, $a_obj = null)
	{
		global $ilObjDataCache, $ilDB;

		$status = LP_STATUS_NOT_ATTEMPTED_NUM;
		switch ($ilObjDataCache->lookupType($a_obj_id))
		{
			case "crs":
				include_once "./Services/Tracking/classes/class.ilChangeEvent.php";
				if (ilChangeEvent::hasAccessed($a_obj_id, $a_user_id))
				{
					$status = LP_STATUS_IN_PROGRESS_NUM;
					
					$completed = true;
					$failed = false;
					include_once("./Services/Tracking/classes/class.ilLPCollectionCache.php");
					foreach(ilLPCollectionCache::_getItems($a_obj_id) as $item_id)
					{
						$item_id = $ilObjDataCache->lookupObjId($item_id);
						if (ilLPStatusWrapper::_determineStatus($item_id, $a_user_id)
							!= LP_STATUS_COMPLETED_NUM)
						{
							$completed = false;
						}
						if (ilLPStatusWrapper::_determineStatus($item_id, $a_user_id)
							== LP_STATUS_FAILED_NUM)
						{
							$failed = true;
						}
					}
					if ($failed)
					{
						$status = LP_STATUS_FAILED_NUM;
					}
					else if ($completed)
					{
						$status = LP_STATUS_COMPLETED_NUM;
					}
				}
				break;			
		}

		return $status;
	}

}	
?>