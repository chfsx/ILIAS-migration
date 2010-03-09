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

include_once 'Services/Tracking/classes/class.ilLPStatus.php';
include_once 'Services/Tracking/classes/class.ilLPObjSettings.php';
include_once 'Services/Tracking/classes/class.ilLearningProgress.php';

class ilLPStatusVisits extends ilLPStatus
{

	function ilLPStatusVisits($a_obj_id)
	{
		global $ilDB;

		parent::ilLPStatus($a_obj_id);
		$this->db =& $ilDB;
	}

	function _getInProgress($a_obj_id)
	{
		global $ilDB;

		$status_info = ilLPStatusWrapper::_getStatusInfo($a_obj_id);
		$required_visits = $status_info['visits'];
			
		include_once './Services/Tracking/classes/class.ilChangeEvent.php';
		$all = ilChangeEvent::_lookupReadEvents($a_obj_id);
		foreach($all as $event)
		{
			if($event['read_count'] < $required_visits)
			{
				$user_ids[] = $event['usr_id'];
			}
		}
		return $user_ids ? $user_ids : array();
	}		

	function _getCompleted($a_obj_id)
	{
		global $ilDB;

		$status_info = ilLPStatusWrapper::_getStatusInfo($a_obj_id);
		$required_visits = $status_info['visits'];

		include_once './Services/Tracking/classes/class.ilChangeEvent.php';
		$all = ilChangeEvent::_lookupReadEvents($a_obj_id);
		foreach($all as $event)
		{
			if($event['read_count'] >= $required_visits)
			{
				$user_ids[] = $event['usr_id'];
			}
		}
		return $user_ids ? $user_ids : array();
	}

	function _getStatusInfo($a_obj_id)
	{
		include_once 'Services/Tracking/classes/class.ilLPObjSettings.php';
		$status_info['visits'] = ilLPObjSettings::_lookupVisits($a_obj_id);

		return $status_info;
	}

		
		

}	
?>