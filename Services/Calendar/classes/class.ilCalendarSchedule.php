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

include_once('Services/Calendar/classes/class.ilDateTime.php');
include_once('Services/Calendar/classes/class.ilCalendarRecurrenceCalculator.php');
include_once('Services/Calendar/classes/class.ilCalendarEntry.php');

/** 
* Represents a list of calendar appointments (including recurring events) for a specific user
* in a given time range.
* 
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
* 
* 
* @ingroup ServicesCalendar
*/

class ilCalendarSchedule
{
	const TYPE_DAY = 1;
	const TYPE_WEEK = 2;
	const TYPE_MONTH = 3;
	
	protected $schedule = array();
	protected $timezone;
	protected $type = 0;
	
	protected $start = null;
	protected $end = null;
	protected $user = null;
	protected $db = null;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param ilDate seed date
	 * @param int type of schedule (TYPE_DAY,TYPE_WEEK or TYPE_MONTH)
	 * @param int user_id
	 * 
	 */
	public function __construct(ilDate $seed,$a_type,$a_user_id = 0)
	{
	 	global $ilUser,$ilDB;
	 	
	 	$this->db = $ilDB;

		$this->type = $a_type;
		$this->initPeriod($seed);
	 	
	 	if(!$a_user_id)
	 	{
	 		$this->user = $ilUser;
	 	}
	 	
	 	$this->timezone = $ilUser->getUserTimeZone();
	}

	/**
	 * get byday
	 *
	 * @access public
	 * @param ilDate start
	 * 
	 */
	public function getByDay(ilDate $a_start,$a_timezone)
	{
		$start = new ilDateTime($a_start->get(IL_CAL_DATETIME),IL_CAL_DATETIME,$this->timezone);
		$fstart = new ilDate($a_start->get(IL_CAL_UNIX),IL_CAL_UNIX);
		$fend = clone $fstart;
		
		$f_unix_start = $fstart->get(IL_CAL_UNIX);
		$fend->increment(ilDateTime::DAY,1);
		$f_unix_end = $fend->get(IL_CAL_UNIX);
		
		$unix_start = $start->get(IL_CAL_UNIX);
		$start->increment(ilDateTime::DAY,1);
		$unix_end = $start->get(IL_CAL_UNIX);
		
		$counter = 0;
	 	foreach($this->schedule as $schedule)
	 	{
	 		if($schedule['fullday'])
	 		{
		 		if(($f_unix_start == $schedule['dstart']) or
		 			($f_unix_start > $schedule['dstart'] and $f_unix_end <= $schedule['dend']))
	 			{
		 			$tmp_schedule[] = $schedule;
	 			}
	 		}
	 		elseif((($unix_start <= $schedule['dstart']) and ($unix_end > $schedule['dstart'])) or
	 			(($unix_start <= $schedule['dend']) and ($unix_end > $schedule['dend'])) or
	 			($unix_start >= $schedule['dstart'] and $unix_end < $schedule['dend']))
	 		{
	 			$tmp_schedule[] = $schedule;
	 		}
	 	}
	 	return $tmp_schedule ? $tmp_schedule : array();
	}

	
	/**
	 * calculate 
	 *
	 * @access protected
	 */
	public function calculate()
	{
		$counter = 0;
		foreach($this->getEvents() as $event)
		{
			// Calculdate recurring events
			
			include_once('Services/Calendar/classes/class.ilCalendarRecurrences.php');
			if($recs = ilCalendarRecurrences::_getRecurrences($event->getEntryId()))
			{
				$duration = $event->getEnd()->get(IL_CAL_UNIX) - $event->getStart()->get(IL_CAL_UNIX);
				
				foreach($recs as $rec)
				{
					$calc = new ilCalendarRecurrenceCalculator($event,$rec);
					foreach($calc->calculateDateList($this->start,$this->end)->get() as $rec_date)
					{
						$this->schedule[$counter]['event'] = $event;
						$this->schedule[$counter]['dstart'] = $rec_date->get(IL_CAL_UNIX);
						$this->schedule[$counter]['dend'] = $this->schedule[$counter]['dstart'] + $duration; 
						$this->schedule[$counter]['fullday'] = $event->isFullday();
						$counter++;
					}
				}
			}
			else
			{
				$this->schedule[$counter]['event'] = $event;
				$this->schedule[$counter]['dstart'] = $event->getStart()->get(IL_CAL_UNIX);
				$this->schedule[$counter]['dend'] = $event->getEnd()->get(IL_CAL_UNIX);
				$this->schedule[$counter]['fullday'] = $event->isFullday();
				$counter++;
			}
		}
	}
	
	/**
	 * Read events (will be moved to another class, since only active and/or visible calendars are shown)
	 *
	 * @access protected
	 */
	protected function getEvents()
	{
		$query = "SELECT cal_id FROM cal_entries AS ce LEFT JOIN cal_recurrence_rules AS crr USING (cal_id) ".
			"WHERE (start <= ".$this->db->quote($this->end->get(IL_CAL_DATE))." ".
			"AND end >= ".$this->db->quote($this->start->get(IL_CAL_DATE)).") ".
			"OR (start <= ".$this->db->quote($this->end->get(IL_CAL_DATE))." ".
			"AND rule_id != 0) ".
			"ORDER BY start";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$events[] = new ilCalendarEntry($row->cal_id);
		}
		return $events ? $events : array();
	}
	
	/**
	 * init period of events
	 *
	 * @access protected
	 * @param ilDate seed
	 * @return
	 */
	protected function initPeriod(ilDate $seed)
	{
		switch($this->type)
		{
			case self::TYPE_DAY:
				break;
			
			case self::TYPE_WEEK:
				break;
			
			case self::TYPE_MONTH:
				$year_month = $seed->get(IL_CAL_FKT_DATE,'Y-m');
				list($year,$month) = explode('-',$year_month);
			
				$this->start = new ilDate($year_month.'-01',IL_CAL_DATE);
				$this->start->increment(IL_CAL_DAY,-6);
				
				$this->end = new ilDate($year_month.'-'.ilCalendarUtil::_getMaxDayOfMonth($year,$month),IL_CAL_DATE);
				$this->end->increment(IL_CAL_DAY,6);
				break;
		}
		
		return true;
	}
}

?>