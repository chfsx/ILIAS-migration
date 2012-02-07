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
* Object statistics garbage collection
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id: class.ilObjectStatisticsCheck.php 29621 2011-06-22 16:51:14Z jluetzen $
*
* @package ilias
*/

class ilCronObjectStatisticsCheck
{
	function __construct()
	{
		global $ilLog,$ilDB;

		$this->log =& $ilLog;
		$this->db =& $ilDB;
		
		// all date related operations are based on this timestamp
		// should be midnight of yesterday (see gatherUserData()) to always have full day
		$this->date = strtotime("yesterday");
	}

	function check()
	{
		// $this->doGarbageCollection();
		$this->gatherCourseLPData();
		$this->gatherTypesData();
		$this->gatherUserData();
	}
	
	/*
	function doGarbageCollection() 
	{
		$first_day_of_month = date("Y-m-01", $this->date);
		
		// only execute on first day of month
		if(date("Y-m-d", $this->date) == $first_day_of_month)
		{
			$cut = strtotime("-3 months", $this->date);
			$cut = mktime(23, 59, 59, date("m", $cut), 0, date("Y", $cut));
			$cut = date("Y-m", $cut);
			
			$date_compare = array(array("yyyy", ""), 
				array($this->db->quote("-", "text"), ""),
				array("LPAD(mm, 2,".$this->db->quote("0", "text").")", ""));
			
			$sql = "DELETE FROM obj_stat".
				" WHERE ".$this->db->concat($date_compare)." <= ".
				$this->db->quote($cut, "text");			
			$this->db->manipulate($sql);
			
			$sql = "DELETE FROM obj_stat_history".
				" WHERE tstamp <= ".$this->db->quote($cut, "text");			
			$this->db->manipulate($sql);
			
			$sql = "DELETE FROM obj_lp_stat".
				" WHERE fulldate <= ".$this->db->quote($cut, "text");			
			$this->db->manipulate($sql);
			
			$sql = "DELETE FROM obj_type_stat".
				" WHERE fulldate <= ".$this->db->quote($cut, "text");			
			$this->db->manipulate($sql);
			
			$sql = "DELETE FROM obj_user_stat".
				" WHERE fulldate <= ".$this->db->quote($cut, "text");			
			$this->db->manipulate($sql);
		}
	
		return true;
	}
	*/
	
	function gatherCourseLPData()
	{
		global $tree, $ilDB;
				
		// gather objects in trash
		$trashed_objects = array();
		$tmp = $tree->getSavedNodeData(ROOT_FOLDER_ID);
		if($tmp)
		{
			foreach($tmp as $item)
			{
				$trashed_objects[] = $item["obj_id"];
			}
		}
						
		// process all courses
		$all_courses = ilObject::_getObjectsByType("crs");	
		if($all_courses)
		{
			include_once 'Services/Tracking/classes/class.ilLPObjSettings.php';
			include_once "Modules/Course/classes/class.ilCourseParticipants.php";
			include_once "Services/Tracking/classes/class.ilLPStatusWrapper.php";				
			foreach($all_courses as $crs_id => $item)
			{
				// only if LP is active
				$mode = ilLPObjSettings::_lookupMode($crs_id);
				if($mode == LP_MODE_DEACTIVATED || $mode == LP_MODE_UNDEFINED)
				{
					continue;
				}
				
				// trashed objects will not change
				if(!in_array($crs_id, $trashed_objects))
				{
					$participants = new ilCourseParticipants($crs_id);
					
					// only save once per day
					$ilDB->manipulate("DELETE FROM obj_lp_stat WHERE".
						" obj_id = ".$ilDB->quote($crs_id, "integer").
						" AND fulldate = ".$ilDB->quote(date("Ymd", $this->date), "integer"));
					
					$set = array(
						"type" => array("text", "crs"),
						"obj_id" => array("integer", $crs_id),
						"yyyy" => array("integer", date("Y", $this->date)),
						"mm" => array("integer", date("m", $this->date)),
						"dd" => array("integer", date("d", $this->date)),
						"fulldate" => array("integer", date("Ymd", $this->date)),
						"mem_cnt" => array("integer", $participants->getCountMembers()),
						"in_progress" => array("integer", ilLPStatusWrapper::_getCountInProgress($crs_id)),
						"completed" => array("integer", ilLPStatusWrapper::_getCountCompleted($crs_id)),
						"failed" => array("integer", ilLPStatusWrapper::_getCountFailed($crs_id)),
						"not_attempted" => array("integer", ilLPStatusWrapper::_getCountNotAttempted($crs_id))												
						);	
					
					$ilDB->insert("obj_lp_stat", $set);
				}						
			}
		}
	}
	
	function gatherTypesData()
	{
		global $ilDB;
		
		include_once "Services/Tracking/classes/class.ilTrQuery.php";
		$data = ilTrQuery::getObjectTypeStatistics();		
		foreach($data as $type => $item)
		{			
			// only save once per day
			$ilDB->manipulate("DELETE FROM obj_type_stat WHERE".
				" type = ".$ilDB->quote($type, "text").
				" AND fulldate = ".$ilDB->quote(date("Ymd", $this->date), "integer"));
			
			$set = array(
				"type" => array("text", $type),
				"yyyy" => array("integer", date("Y", $this->date)),
				"mm" => array("integer", date("m", $this->date)),
				"dd" => array("integer", date("d", $this->date)),
				"fulldate" => array("integer", date("Ymd", $this->date)),
				"cnt_references" => array("integer", (int)$item["references"]),
				"cnt_objects" => array("integer", (int)$item["objects"]),
				"cnt_deleted" => array("integer", (int)$item["deleted"])										
				);	

			$ilDB->insert("obj_type_stat", $set);
		}
	}
	
	function gatherUserData()
	{
		global $ilDB;
		
		$to = mktime(23, 59, 59, date("m", $this->date), date("d", $this->date), date("Y", $this->date));
					
		$sql = "SELECT COUNT(DISTINCT(usr_id)) counter,obj_id FROM read_event".
			" WHERE last_access >= ".$ilDB->quote($this->date, "integer").
			" AND last_access <= ".$ilDB->quote($to, "integer").
			" GROUP BY obj_id";
		$set = $ilDB->query($sql);
		while($row = $ilDB->fetchAssoc($set))
		{		
			// only save once per day
			$ilDB->manipulate("DELETE FROM obj_user_stat".
				" WHERE fulldate = ".$ilDB->quote(date("Ymd", $this->date), "integer").
				" AND obj_id = ".$ilDB->quote($row["obj_id"], "integer"));

			$iset = array(
				"obj_id" => array("integer", $row["obj_id"]),
				"yyyy" => array("integer", date("Y", $this->date)),
				"mm" => array("integer", date("m", $this->date)),
				"dd" => array("integer", date("d", $this->date)),
				"fulldate" => array("integer", date("Ymd", $this->date)),	
				"counter" => array("integer", $row["counter"])
				);	

			$ilDB->insert("obj_user_stat", $iset);	
		}
	}
}
?>
