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
* class ilTimingPlaned
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
*/


class ilTimingPlaned
{
	var $ilErr;
	var $ilDB;
	var $lng;

	function ilTimingPlaned($item_id,$a_usr_id)
	{
		global $ilErr,$ilDB,$lng,$tree;

		$this->ilErr =& $ilErr;
		$this->db  =& $ilDB;
		$this->lng =& $lng;

		$this->item_id = $item_id;
		$this->user_id = $a_usr_id;

		$this->__read();
	}
	
	function getUserId()
	{
		return $this->user_id;
	}
	function getItemId()
	{
		return $this->item_id;
	}

	function getPlanedStartingTime()
	{
		return $this->start;
	}
	function setPlanedStartingTime($a_time)
	{
		$this->start = $a_time;
	}
	function getPlanedEndingTime()
	{
		return $this->end;
	}
	function setPlanedEndingTime($a_end)
	{
		$this->end = $a_end;
	}

	function validate()
	{
		include_once 'course/classes/class.ilCourseItems.php';
		$item_data = ilCourseItems::_getItem($this->getItemId());

		if($this->getPlanedEndingTime() > $item_data['latest_end'])
		{
			return false;
		}
		return true;
	}

	function update()
	{
		ilTimingPlaned::_delete($this->getItemId(),$this->getUserId());
		$this->create();
		return true;
	}

	function create()
	{
		$query = "INSERT INTO crs_timings_planed ".
			"SET item_id = '".$this->getItemId()."', ".
			"usr_id = '".$this->getUserId()."', ".
			"planed_start = '".(int) $this->getPlanedStartingTime()."', ".
			"planed_end = '".(int) $this->getPlanedEndingTime()."'";
		$this->db->query($query);
	}

	function delete()
	{
		return ilTimingPlaned::_delete($this->getItemId(),$this->getUserId());
	}

	function _delete($a_item_id,$a_usr_id)
	{
		global $ilDB;

		$query = "DELETE FROM crs_timings_planed ".
			"WHERE item_id = '".$a_item_id."' ".
			"AND usr_id = '".$a_usr_id."'";
		$ilDB->query($query);
	}

	// Static
	function _getPlanedTimings($a_usr_id,$a_item_id)
	{
		global $ilDB;

		$query = "SELECT * FROM crs_timings_planed ".
			"WHERE item_id = '".$a_item_id."' ".
			"AND usr_id = '".$a_usr_id."'";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$data['planed_start'] = $row->planed_start;
			$data['planed_end'] = $row->planed_end;
		}
		return $data ? $data : array();
	}


	function _getPlanedTimingsByItem($a_item_id)
	{
		global $ilDB;

		$query = "SELECT * FROM crs_timings_planed ".
			"WHERE item_id = '".$a_item_id."'";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$data[$row->usr_id]['start'] = $row->planed_start;
			$data[$row->usr_id]['end']   = $row->planed_end;
		}
		return $data ? $data : array();
	}

	function _deleteByItem($a_item_id)
	{
		global $ilDB;

		$query = "DELETE FROM crs_timings_planed ".
			"WHERE item_id = '".$a_item_id."'";
		$ilDB->query($query);
	}

	function _deleteByUser($a_usr_id)
	{
		global $ilDB;

		$query = "DELETE FROM crs_timings_planed ".
			"WHERE usr_id = '".$a_usr_id."'";
		$ilDB->query($query);
	}

	function __read()
	{
		$query = "SELECT * FROM crs_timings_planed ".
			"WHERE item_id = '".$this->getItemId()."' ".
			"AND usr_id = '".$this->getUserId()."'";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->setPlanedStartingTime($row->planed_start);
			$this->setPlanedEndingTime($row->planed_end);
		}
		return true;
	}		
}
?>