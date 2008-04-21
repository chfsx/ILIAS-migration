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
* class ilSessionAppointment
*
* @author Stefan Meyer <smeyer.ilias@gmx.de> 
* @version $Id$
* 
* @ingroup ModulesSession
*/

include_once('./Services/Calendar/interfaces/interface.ilDatePeriod.php');

class ilSessionAppointment implements ilDatePeriod
{
	var $ilErr;
	var $ilDB;
	var $tree;
	var $lng;

	var $starting_time = null;
	var $ending_time = null;

	function ilSessionAppointment($a_appointment_id = null)
	{
		global $ilErr,$ilDB,$lng,$tree;

		$this->ilErr =& $ilErr;
		$this->db  =& $ilDB;
		$this->lng =& $lng;

		$this->appointment_id = $a_appointment_id;
		$this->__read();
	}
	
	/**
	 * lookup appointment
	 *
	 * @access public
	 * @param int obj_id
	 * @static
	 */
	public static function _lookupAppointment($a_obj_id)
	{
		global $ilDB;
		
		$query = "SELECT * FROM event_appointment ".
			"WHERE event_id = ".$ilDB->quote($a_obj_id)." ";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$info['start'] = $row->starting_time;
			$info['end'] = $row->ending_time;
			$info['fullday'] = $row->fulltime;
			return $info;
		}
		return array();
	}
	
	// Interface methods
	/**
	 * is fullday
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function isFullday()
	{
		return $this->enabledFullTime();
	}
	
	/**
	 * get start
	 *
	 * @access public
	 * @param
	 * @return ilDateTime
	 */
	public function getStart()
	{
		include_once('./Services/Calendar/classes/class.ilDate.php');
		if($this->isFullday())
		{
			return new ilDate($this->getStartingTime(),IL_CAL_UNIX);
		}
		else
		{
			return new ilDateTime($this->getStartingTime(),IL_CAL_UNIX);
		}
	}
	
	/**
	 * get end
	 *
	 * @access public
	 * @return ilDateTime
	 */
	public function getEnd()
	{
		include_once('./Services/Calendar/classes/class.ilDate.php');
		if($this->isFullday())
		{
			return new ilDate($this->getEndingTime(),IL_CAL_UNIX);
		}
		else
		{
			return new ilDateTime($this->getEndingTime(),IL_CAL_UNIX);			
		}
	}

	function setAppointmentId($a_appointment_id)
	{
		$this->appointment_id = $a_appointment_id;
	}
	function getAppointmentId()
	{
		return $this->appointment_id;
	}

	function setSessionId($a_session_id)
	{
		$this->session_id = $a_session_id;
	}
	function getSessionId()
	{
		return $this->session_id;
	}

	function setStartingTime($a_starting_time)
	{
		$this->starting_time = $a_starting_time;
	}
	function getStartingTime()
	{
		return isset($this->starting_time) ? $this->starting_time : mktime(8,0,0,date('n',time()),date('d',time()),date('Y',time()));
	}
	
	function setEndingTime($a_ending_time)
	{
		$this->ending_time = $a_ending_time;
	}
	function getEndingTime()
	{
		return isset($this->ending_time) ? $this->ending_time : mktime(16,0,0,date('n',time()),date('d',time()),date('Y',time()));
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
		return ilSessionAppointment::_timeToString($this->getStartingTime(),$this->getEndingTime());
	}

	function _timeToString($start,$end)
	{
		$start = date($this->lng->txt('lang_timeformat_no_sec'),$start);
		$end = date($this->lng->txt('lang_timeformat_no_sec'),$end);
	
		return $start.' - '. $end;
	}

	function _appointmentToString($start,$end,$fulltime)
	{
		global $lng;

		$start_day = date('j',$start);
		$start_month = date('n',$start);
		$start_year = date('Y',$start);

		$end_day = date('j',$end);
		$end_month = date('n',$end);
		$end_year = date('Y',$end);

		if($start_day == $end_day and
		   $start_month == $end_month and
		   $start_year == $end_year)
		{
			$date_time = ilFormat::formatUnixTime($start,false);
			if(!$fulltime)
			{
				$date_time .= (' '.ilSessionAppointment::_timeToString($start,$end));
			}
			else
			{
				$date_time .= (' ('.$lng->txt('event_full_time_info').')');
			}
		}
		else
		{
			if(!$fulltime)
			{
				$date_time = ilFormat::formatUnixTime($start,true) . ' - '.
					ilFormat::formatUnixTime($end,true);
			}
			else
			{
				$date_time = ilFormat::formatUnixTime($start,false) . ' - '.
					ilFormat::formatUnixTime($end,false);
				$date_time .= (' ('.$lng->txt('event_full_time_info').')');
			}
		}
		return $date_time;
	}
	function appointmentToString()
	{
		return ilSessionAppointment::_appointmentToString($this->getStartingTime(),$this->getEndingTime(),$this->enabledFullTime());
	}
	
	/**
	 * clone appointment
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function cloneObject($new_id)
	{
		$new_app = new ilSessionAppointment();
		$new_app->setSessionId($new_id);
		$new_app->setStartingTime($this->getStartingTime());
		$new_app->setEndingTime($this->getEndingTime());
		$new_app->toggleFullTime($this->isFullday());
		$new_app->create();
	}

	function create()
	{
		global $ilDB;
		
		if(!$this->getSessionId())
		{
			return false;
		}
		$query = "INSERT INTO event_appointment ".
			"SET event_id = ".$ilDB->quote($this->getSessionId()).", ".
			"starting_time = ".$ilDB->quote($this->getStartingTime()).", ".
			"ending_time = ".$ilDB->quote($this->getEndingTime()).", ".
			"fulltime = ".$ilDB->quote($this->enabledFullTime())." ";
		
		$this->appointment_id = $ilDB->getLastInsertId();

		$this->db->query($query);
		
		
		return true;
	}

	function update()
	{
		global $ilDB;
		
		if(!$this->getSessionId())
		{
			return false;
		}
		$query = "UPDATE event_appointment ".
			"SET event_id = ".$ilDB->quote($this->getSessionId()).", ".
			"starting_time = ".$ilDB->quote($this->getStartingTime()).", ".
			"ending_time = ".$ilDB->quote($this->getEndingTime()).", ".
			"fulltime = ".$ilDB->quote($this->enabledFullTime())." ".
			"WHERE appointment_id = ".$ilDB->quote($this->getAppointmentId())." ";

		$this->db->query($query);
		return true;
	}

	function delete()
	{
		return ilSessionAppointment::_delete($this->getAppointmentId());
	}

	function _delete($a_appointment_id)
	{
		global $ilDB;

		$query = "DELETE FROM event_appointment ".
			"WHERE appointment_id = ".$ilDB->quote($a_appointment_id)." ";
		$this->db->query($query);

		return true;
	}

	function _deleteBySession($a_event_id)
	{
		global $ilDB;

		$query = "DELETE FROM event_appointment ".
			"WHERE event_id = ".$ilDB->quote($a_event_id)." ";
		$ilDB->query($query);

		return true;
	}

	function _readAppointmentsBySession($a_event_id)
	{
		global $ilDB;

		$query = "SELECT * FROM event_appointment ".
			"WHERE event_id = ".$ilDB->quote($a_event_id)." ".
			"ORDER BY starting_time";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$appointments[] =& new ilSessionAppointment($row->appointment_id);
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
		global $ilDB;
		
		if(!$this->getAppointmentId())
		{
			return null;
		}

		$query = "SELECT * FROM event_appointment ".
			"WHERE appointment_id = ".$ilDB->quote($this->getAppointmentId())." ";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->setSessionId($row->event_id);
			$this->setStartingTime($row->starting_time);
			$this->setEndingTime($row->ending_time);
			$this->toggleFullTime($row->fulltime);
		}
		return true;
	}

}
?>