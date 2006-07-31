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
* class ilEventAppointment
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @extends Object
* @package ilias-core
*/


class ilEventAppointment
{
	var $ilErr;
	var $ilDB;
	var $tree;
	var $lng;

	var $starting_time = null;
	var $ending_time = null;

	function ilEventAppointment($a_appointment_id = null)
	{
		global $ilErr,$ilDB,$lng,$tree;

		$this->ilErr =& $ilErr;
		$this->db  =& $ilDB;
		$this->lng =& $lng;

		$this->appointment_id = $a_appointment_id;
		$this->__read();
	}

	function setAppointmentId($a_appointment_id)
	{
		$this->appointment_id = $a_appointment_id;
	}
	function getAppointmentId()
	{
		return $this->appointment_id;
	}

	function setEventId($a_event_id)
	{
		$this->event_id = $a_event_id;
	}
	function getEventId()
	{
		return $this->event_id;
	}

	function setStartingTime($a_starting_time)
	{
		$this->starting_time = $a_starting_time;
	}
	function getStartingTime()
	{
		return isset($this->starting_time) ? $this->starting_time : time();
	}
	
	function setEndingTime($a_ending_time)
	{
		$this->ending_time = $a_ending_time;
	}
	function getEndingTime()
	{
		return isset($this->ending_time) ? $this->ending_time : time();
	}

	function toggleFullTime($a_status)
	{
		$this->fulltime = $a_status;
	}
	function enabledFullTime()
	{
		return $this->fulltime;
	}

	function formatTime()
	{
		return ilEventAppointment::_timeToString($this->getStartingTime(),$this->getEndingTime());
	}

	function _timeToString($start,$end)
	{
		$start = date($this->lng->txt('lang_timeformat_no_sec'),$start);
		$end = date($this->lng->txt('lang_timeformat_no_sec'),$end);
	
		return $start.' - '. $end;
	}

	function create()
	{
		if(!$this->getEventId())
		{
			return false;
		}
		$query = "INSERT INTO event_appointment ".
			"SET event_id = '".$this->getEventId()."', ".
			"starting_time = '".$this->getStartingTime()."', ".
			"ending_time = '".$this->getEndingTime()."', ".
			"fulltime = '".(bool) $this->enabledFullTime()."'";

		$this->db->query($query);
		return true;
	}

	function update()
	{
		if(!$this->getEventId())
		{
			return false;
		}
		$query = "UPDATE event_appointment ".
			"SET event_id = '".$this->getEventId()."', ".
			"starting_time = '".$this->getStartingTime()."', ".
			"ending_time = '".$this->getEndingTime()."', ".
			"fulltime = '".$this->enabledFullTime()."' ".
			"WHERE appointment_id = '".$this->getAppointmentId()."'";

		$this->db->query($query);
		return true;
	}

	function delete()
	{
		return ilEventAppointment::_delete($this->getAppointmentId());
	}

	function _delete($a_appointment_id)
	{
		global $ilDB;

		$query = "DELETE FROM event_appointment ".
			"WHERE appointment_id = '".$a_appointment_id."'";
		$this->db->query($query);

		return true;
	}

	function _deleteByEvent($a_event_id)
	{
		global $ilDB;

		$query = "DELETE FROM event_appointment ".
			"WHERE event_id = '".$a_event_id."'";
		$ilDB->query($query);

		return true;
	}

	function &_readAppointmentsByEvent($a_event_id)
	{
		global $ilDB;

		$query = "SELECT * FROM event_appointment ".
			"WHERE event_id = '".$a_event_id."' ".
			"ORDER BY starting_time";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$appointments[] =& new ilEventAppointment($row->appointment_id);
		}
		return is_array($appointments) ? $appointments : array();
	}
			
	function validate()
	{
		if($this->starting_time > $this->ending_time)
		{
			$this->ilErr->appendMessage($this->lng->txt('event_etime_smaller_stime'));
			return false;
		}
		return true;
	}

	// PRIVATE
	function __read()
	{
		if(!$this->getAppointmentId())
		{
			return null;
		}

		$query = "SELECT * FROM event_appointment ".
			"WHERE appointment_id = '".$this->getAppointmentId()."'";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->setEventId($row->event_id);
			$this->setStartingTime($row->starting_time);
			$this->setEndingTime($row->ending_time);
			$this->toggleFullTime($row->fulltime);
		}
		return true;
	}

}
?>