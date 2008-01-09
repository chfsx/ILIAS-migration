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

include_once('Services/Calendar/classes/class.ilTimeZone.php');

/** 
* Stores all calendar relevant settings.
* 
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* 
* @ingroup ServicesCalendar
*/
class ilCalendarSettings
{
	private static $instance = null;

	private $storage = null;
	private $timezone = null;
	private $week_start = 0;
	private $enabled = false;

	/**
	 * singleton contructor
	 *
	 * @access private
	 * 
	 */
	private function __construct()
	{
	 	$this->initStorage();
		$this->read();			 	
	}
	
	/**
	 * set enabled
	 *
	 * @access public
	 * 
	 */
	public function setEnabled($a_enabled)
	{
	 	$this->enabled = $a_enabled;
	}
	
	/**
	 * is calendar enabled
	 *
	 * @access public
	 * 
	 */
	public function isEnabled()
	{
	 	return (bool) $this->enabled;
	}
	
	/**
	 * set week start
	 *
	 * @access public
	 * 
	 */
	public function setDefaultWeekStart($a_start)
	{
	 	$this->week_start = $a_start;
	}
	
	/**
	 * get default week start
	 *
	 * @access public
	 * 
	 */
	public function getDefaultWeekStart()
	{
	 	return $this->week_start;
	}
	
	/**
	 * set default timezone
	 *
	 * @access public
	 */
	public function setDefaultTimeZone($a_zone)
	{
	 	$this->timezone = $a_zone;
	}
	
	/**
	 * get derfault time zone
	 *
	 * @access public
	 */
	public function getDefaultTimeZone()
	{
	 	return $this->timezone;
	}

	

	/**
	 * get singleton instance
	 *
	 * @access public
	 * @static
	 *
	 */
	public static function _getInstance()
	{
		if(self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new ilCalendarSettings();
	}
	
	/**
	 * save 
	 *
	 * @access public
	 */
	public function save()
	{
	 	$this->storage->set('enabled',(int) $this->isEnabled());
	 	$this->storage->set('default_timezone',$this->getDefaultTimeZone());
	 	$this->storage->set('default_week_start',$this->getDefaultWeekStart());
	}

	/**
	 * Read settings
	 *
	 * @access private
	 * @param
	 * 
	 */
	private function read()
	{
		$this->setEnabled($this->storage->get('enabled'));
		$this->setDefaultTimeZone($this->storage->get('default_timezone',ilTimeZone::_getDefaultTimeZone()));
		$this->setDefaultWeekStart($this->storage->get('default_week_start',self::WEEK_START_MONDAY));
	}
	
	/**
	 * Init storage class (ilSetting)
	 * @access private
	 * 
	 */
	private function initStorage()
	{
	 	include_once('./Services/Administration/classes/class.ilSetting.php');
	 	$this->storage = new ilSetting('calendar');
	}
}
?>