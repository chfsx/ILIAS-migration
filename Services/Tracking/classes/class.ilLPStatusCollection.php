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

	/**
	 * Get completed users
	 * New handling for optional grouped assignments.
	 * @global <type> $ilBench
	 * @global <type> $ilObjDataCache
	 * @global <type> $tree
	 * @param int $a_obj_id
	 * @return array users
	 */
	function _getCompleted($a_obj_id)
	{
		include_once './Services/Tracking/classes/class.ilLPCollectionCache.php';

		global $ilBench,$ilObjDataCache,$tree;
		$ilBench->start('LearningProgress','9173_LPStatusCollection_completed');

		// New handling for optional assignments
		$counter = 0;
		$users = array();
		foreach(ilLPCollectionCache::getGroupedItems($a_obj_id) as $grouping_id => $grouping)
		{
			$isGrouping = $grouping_id ? true : false;
			$grouping_completed = array();
			foreach((array) $grouping['items'] as $item)
			{
				$item_id = $ilObjDataCache->lookupObjId($item);
				$tmp_users = ilLPStatusWrapper::_getCompleted($item_id);
				if($isGrouping)
				{
					// Collect
					$grouping_completed = array_unique(array_merge($grouping_completed,$tmp_users));
				}
				else
				{
					if(!$counter++)
					{
						$users = $tmp_users;
					}
					else
					{
						$users = array_intersect($users,$tmp_users);
					}
				}
			}
			if($isGrouping)
			{
				// build intersection of users
				if(!$counter++)
				{
					$users = $grouping_completed;
				}
				else
				{
					$users = array_intersect($users,$grouping_completed);
				}
			}
		}



/*
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
*/
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
		foreach(ilLPCollectionCache::getGroupedItems($a_obj_id) as $grouping_id => $grouping)
		{
			$isGrouping = $grouping_id ? true : false;

			$gr_failed = array();
			$counter = 0;
			foreach((array) $grouping['items'] as $item)
			{
				$item_id = $ilObjDataCache->lookupObjId($item);
				$tmp_users = ilLPStatusWrapper::_getFailed($item_id);

				if($isGrouping)
				{
					// All items of grouping must be failed for grouping status failed
					$gr_failed = $counter ? array_intersect($gr_failed, $tmp_users) : $tmp_users;
				}
				else
				{
					// One item failed is sufficient for status failed.
					$gr_failed = array_merge($gr_failed,$tmp_users);
				}
				$counter++;
			}
			$users = array_merge($users,$gr_failed);
		}

		/*
		$users = array();
		foreach(ilLPCollectionCache::_getItems($a_obj_id) as $item_id)
		{
			$item_id = $ilObjDataCache->lookupObjId($item_id);
			$tmp_users = ilLPStatusWrapper::_getFailed($item_id);
			$users = array_merge($users,$tmp_users);
		}
		 */
		
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


		$status['completed'] = true;
		$status['failed'] = true;
		$status['in_progress'] = true;


		#$status = LP_STATUS_NOT_ATTEMPTED_NUM;
		switch ($ilObjDataCache->lookupType($a_obj_id))
		{
			case "crs":
			case "fold":
			case "grp":
				include_once "./Services/Tracking/classes/class.ilChangeEvent.php";
				if (ilChangeEvent::hasAccessed($a_obj_id, $a_user_id))
				{
					#$status = LP_STATUS_IN_PROGRESS_NUM;
					$status['in_progress'] = true;

				}
<<<<<<< .working

=======

				include_once './Services/Tracking/classes/class.ilLPCollectionCache.php';
				foreach(ilLPCollectionCache::getGroupedItems($a_obj_id) as $grouping_id => $grouping)
				{
					$isGrouping = $grouping_id ? true : false;
					$status = self::determineGroupingStatus($status,$grouping['items'],$a_user_id,$isGrouping);
				}

				if($status['completed'])
				{
					return LP_STATUS_COMPLETED_NUM;
				}
				if($status['failed'])
				{
					return LP_STATUS_FAILED_NUM;
				}
				if($status['in_progress'])
				{
					return LP_STATUS_IN_PROGRESS_NUM;
				}
				return LP_STATUS_NOT_ATTEMPTED_NUM;
		}

		/*
>>>>>>> .merge-rechts.r28161
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
						== LP_STATUS_COMPLETED_NUM)
					{
						$status = LP_STATUS_IN_PROGRESS_NUM;
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

				break;			
		}

		return $status;
		 */
	}

	public static function determineGroupingStatus($status,$items,$user_id,$is_grouping)
	{
		global $ilObjDataCache;

		include_once("./Services/Tracking/classes/class.ilLPCollectionCache.php");
		foreach($items as $item_id)
		{
			$item_id = $ilObjDataCache->lookupObjId($item_id);
			$gr_status = ilLPStatusWrapper::_determineStatus($item_id, $user_id);

			if($gr_stat == LP_STATUS_FAILED_NUM)
			{
				$status['in_progress'] = true;
				if($is_grouping)
				{

				}
				else
				{
					$status['failed'] = true;
					$status['completed'] = false;
					return $status;
				}
			}
			if($gr_status == LP_STATUS_COMPLETED_NUM)
			{
				$status['in_progress'] = true;
				if($is_grouping)
				{
					$status['failed'] = false;
					return $status;
				}
				else
				{
					
				}
			}
			if($gr_status == LP_STATUS_IN_PROGRESS_NUM)
			{
				$status['in_progress'] = true;
				if($is_grouping)
				{
					$status['failed'] = false;
				}
				else
				{
					$status['completed'] = false;
				}
			}
			if($gr_status == LP_STATUS_NOT_ATTEMPTED_NUM)
			{
				if($is_grouping)
				{
					
				}
				else
				{
					$status['completed'] = false;
				}
			}
		}
		if($is_grouping)
		{
			$status['completed'] = false;
		}
		return $status;
	}

}	
?>