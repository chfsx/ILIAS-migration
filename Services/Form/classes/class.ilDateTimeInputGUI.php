<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2007 ILIAS open source, University of Cologne            |
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
* This class represents a date/time property in a property form.
*
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
* @ingroup	ServicesForm
*/
class ilDateTimeInputGUI extends ilFormPropertyGUI
{
	protected $date;
	protected $showdate = true;
	protected $time = "00:00:00";
	protected $showtime = false;
	protected $showseconds = false;
	
	/**
	* Constructor
	*
	* @param	string	$a_title	Title
	* @param	string	$a_postvar	Post Variable
	*/
	function __construct($a_title = "", $a_postvar = "")
	{
		parent::__construct($a_title, $a_postvar);
		$this->setType("datetime");
	}

	/**
	* Set Date, yyyy-mm-dd.
	*
	* @param	string	$a_date	Date, yyyy-mm-dd
	*/
	function setDate($a_date)
	{
		$this->date = $a_date;
	}

	/**
	* Get Date, yyyy-mm-dd.
	*
	* @return	string	Date, yyyy-mm-dd
	*/
	function getDate()
	{
		return $this->date;
	}

	/**
	* Set Show Date Information.
	*
	* @param	boolean	$a_showdate	Show Date Information
	*/
	function setShowDate($a_showdate)
	{
		$this->showdate = $a_showdate;
	}

	/**
	* Get Show Date Information.
	*
	* @return	boolean	Show Date Information
	*/
	function getShowDate()
	{
		return $this->showdate;
	}

	/**
	* Set Time, 00:00:00.
	*
	* @param	string	$a_time	Time, 00:00:00
	*/
	function setTime($a_time)
	{
		$this->time = $a_time;
	}

	/**
	* Get Time, 00:00:00.
	*
	* @return	string	Time, 00:00:00
	*/
	function getTime()
	{
		return $this->time;
	}

	/**
	* Set Show Time Information.
	*
	* @param	boolean	$a_showtime	Show Time Information
	*/
	function setShowTime($a_showtime)
	{
		$this->showtime = $a_showtime;
	}

	/**
	* Get Show Time Information.
	*
	* @return	boolean	Show Time Information
	*/
	function getShowTime()
	{
		return $this->showtime;
	}

	/**
	* Set Show Seconds.
	*
	* @param	boolean	$a_showseconds	Show Seconds
	*/
	function setShowSeconds($a_showseconds)
	{
		$this->showseconds = $a_showseconds;
	}

	/**
	* Get Show Seconds.
	*
	* @return	boolean	Show Seconds
	*/
	function getShowSeconds()
	{
		return $this->showseconds;
	}
	
	/**
	* Set value by array
	*
	* @param	array	$a_values	value array
	*/
	function setValueByArray($a_values)
	{
		$this->setDate($a_values[$this->getPostVar()]["date"]);
		$this->setTime($a_values[$this->getPostVar()]["time"]);
	}

	/**
	* Check input, strip slashes etc. set alert, if input is not ok.
	*
	* @return	boolean		Input ok, true/false
	*/	
	function checkInput()
	{
		global $lng;
		
		$_POST[$this->getPostVar()]["date"]["y"] = 
			ilUtil::stripSlashes($_POST[$this->getPostVar()]["date"]["y"]);
		$_POST[$this->getPostVar()]["date"]["m"] = 
			ilUtil::stripSlashes($_POST[$this->getPostVar()]["date"]["m"]);
		$_POST[$this->getPostVar()]["date"]["d"] = 
			ilUtil::stripSlashes($_POST[$this->getPostVar()]["date"]["d"]);
		$_POST[$this->getPostVar()]["time"]["h"] = 
			ilUtil::stripSlashes($_POST[$this->getPostVar()]["time"]["h"]);
		$_POST[$this->getPostVar()]["time"]["m"] = 
			ilUtil::stripSlashes($_POST[$this->getPostVar()]["time"]["m"]);
		$_POST[$this->getPostVar()]["time"]["s"] = 
			ilUtil::stripSlashes($_POST[$this->getPostVar()]["time"]["s"]);
			
		$_POST[$this->getPostVar()]["time"] =
			str_pad($_POST[$this->getPostVar()]["time"]["h"], 2 , "0", STR_PAD_LEFT).":".
			str_pad($_POST[$this->getPostVar()]["time"]["m"], 2 , "0", STR_PAD_LEFT).":".
			str_pad($_POST[$this->getPostVar()]["time"]["s"], 2 , "0", STR_PAD_LEFT);
			
		$_POST[$this->getPostVar()]["date"] =
			str_pad($_POST[$this->getPostVar()]["date"]["y"], 4 , "0", STR_PAD_LEFT)."-".
			str_pad($_POST[$this->getPostVar()]["date"]["m"], 2 , "0", STR_PAD_LEFT)."-".
			str_pad($_POST[$this->getPostVar()]["date"]["d"], 2 , "0", STR_PAD_LEFT);
			
		// todo: future checks, e.g. > other datetime property
			
		return true;
	}

	/**
	* Insert property html
	*
	*/
	function insert(&$a_tpl)
	{
		global $lng;
		
		$lng->loadLanguageModule("jscalendar");
		require_once("./Services/Calendar/classes/class.ilCalendarUtil.php");
		ilCalendarUtil::initJSCalendar();
		$a_tpl->setCurrentBlock("prop_file");
		if ($this->getShowDate())
		{
			$a_tpl->setVariable("IMG_DATE_CALENDAR", ilUtil::getImagePath("calendar.png"));
			$a_tpl->setVariable("TXT_DATE_CALENDAR", $lng->txt("open_calendar"));
			$a_tpl->setVariable("DATE_ID", $this->getPostVar());
			$a_tpl->setVariable("INPUT_FIELDS_DATE", $this->getPostVar()."[date]");
			$date = explode("-", $this->getDate());
			$a_tpl->setVariable("DATE_SELECT",
				ilUtil::makeDateSelect($this->getPostVar()."[date]", $date[0], $date[1], $date[2]));
		}
		if ($this->getShowTime())
		{
			$time = explode(":", $this->getTime());
			$a_tpl->setVariable("TIME_SELECT",
				ilUtil::makeTimeSelect($this->getPostVar()."[time]", !$this->getShowSeconds(),
				$time[0], $time[1], $time[2]));
			$a_tpl->setVariable("TXT_TIME", $this->getShowSeconds()
				? "(".$lng->txt("hh_mm_ss").")"
				: "(".$lng->txt("hh_mm").")");
		}
		if ($this->getShowTime() && $this->getShowDate())
		{
			$a_tpl->setVariable("DELIM", "<br />");
		}
	}

}

