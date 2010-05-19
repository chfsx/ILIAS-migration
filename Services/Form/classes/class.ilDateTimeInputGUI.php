<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* This class represents a date/time property in a property form.
*
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
* @ingroup	ServicesForm
*/
include_once("./Services/Table/interfaces/interface.ilTableFilterItem.php");
class ilDateTimeInputGUI extends ilSubEnabledFormPropertyGUI implements ilTableFilterItem
{
	protected $mode = null;
	protected $date_obj = null;
	protected $date;
	protected $showdate = true;
	protected $time = "00:00:00";
	protected $showtime = false;
	protected $showseconds = false;
	protected $minute_step_size = 1;
	protected $show_empty = false;
	protected $startyear = '';
	
	protected $activation_title = '';
	protected $activation_post_var = '';

	const MODE_SELECT = 1;
	const MODE_INPUT = 2;
	
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
		$this->setMode(self::MODE_SELECT);
	}

	/**
	 * Set Display Mode.
	 *
	 * @param	int $mode Display Mode
	 */
	function setMode($mode)
	{
		if(in_array($mode, array(self::MODE_INPUT, self::MODE_SELECT)))
		{
			$this->mode = $mode;
		}
	}

	/**
	 * Get Display Mode
	 *
	 * @return int
	 */
	function getMode()
	{
		return $this->mode;
	}
	
	/**
	 * Enable date activation.
	 * If chosen a checkbox will be shown that gives the possibility to en/disable the date selection.
	 *
	 * @access public
	 * @param string text displayed after the checkbox
	 * @param string name of postvar
	 * @param bool checkbox checked
	 * 
	 */
	public function enableDateActivation($a_title,$a_postvar,$a_checked = true)
	{
	 	$this->activation_title = $a_title;
	 	$this->activation_post_var = $a_postvar;
	 	$this->activation_checked = $a_checked;
	}
	
	/**
	 * Get activation post var
	 *
	 * @access public
	 * 
	 */
	public function getActivationPostVar()
	{
	 	return $this->activation_post_var;
	}

	/**
	* set date
	* E.g	$dt_form->setDate(new ilDateTime(time(),IL_CAL_UTC));
	* or 	$dt_form->setDate(new ilDateTime('2008-06-12 08:00:00',IL_CAL_DATETIME));
	* 
	* For fullday (no timezone conversion) events use:
	* 
	* 		$dt_form->setDate(new ilDate('2008-08-01',IL_CAL_DATE));
	*		
	* @param	object	$a_date	ilDate or ilDateTime  object
	*/
	function setDate(ilDateTime $a_date)
	{
		$this->date = $a_date;
	}

	/**
	* Get Date, yyyy-mm-dd.
	*
	* @return	object	Date, yyyy-mm-dd
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
	* Set Show Empty Information.
	*
	* @param	boolean	Show Empty Information
	*/
	function setShowEmpty($a_empty)
	{
		$this->show_empty = $a_empty;
	}

	/**
	* Get Show Empty Information.
	*
	* @return	boolean	Show Empty Information
	*/
	function getShowEmpty()
	{
		return $this->show_empty;
	}
	
	/**
	* Set start year
	*
	* @param	integer	Start year
	*/
	function setStartYear($a_year)
	{
		$this->startyear = $a_year;
	}
	
	/**
	* Get start year
	*
	* @return	integer	Start year
	*/
	function getStartYear()
	{
		return $this->startyear;
	}
	
	/**
	 * Set minute step size
	 * E.g 5 => The selection will only show 00,05,10... minutes 
	 *
	 * @access public
	 * @param int minute step_size 1,5,10,15,20...
	 * 
	 */
	public function setMinuteStepSize($a_step_size)
	{
	 	$this->minute_step_size = $a_step_size;
	}
	
	/**
	 * Get minute step size
	 *
	 * @access public
	 * 
	 */
	public function getMinuteStepSize()
	{
	 	return $this->minute_step_size;
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

	/*
	 *
	 * 
	 */
	protected function convertIncomingValues($post)
	{
		global $ilUser;
		
		if($this->getMode() == self::MODE_SELECT)
		{
			$post["date"]["y"] = ilUtil::stripSlashes($post["date"]["y"]);
			$post["date"]["m"] = ilUtil::stripSlashes($post["date"]["m"]);
			$post["date"]["d"] = ilUtil::stripSlashes($post["date"]["d"]);
			$dt['year'] = (int) $post['date']['y'];
			$dt['mon'] = (int) $post['date']['m'];
			$dt['mday'] = (int) $post['date']['d'];

			if($this->getShowTime())
			{
				$post["time"]["h"] = ilUtil::stripSlashes($post["time"]["h"]);
				$post["time"]["m"] = ilUtil::stripSlashes($post["time"]["m"]);
				$post["time"]["s"] = ilUtil::stripSlashes($post["time"]["s"]);
				$dt['hours'] = (int) $post['time']['h'];
				$dt['minutes'] = (int) $post['time']['m'];
				$dt['seconds'] = (int) $post['time']['s'];
			}
		}
		else
		{
			$post["date"] = ilUtil::stripSlashes($post["date"]);
			$post["time"] = ilUtil::stripSlashes($post["time"]);

			if($post["date"])
			{
				switch($ilUser->getDateFormat())
				{
					case ilCalendarSettings::DATE_FORMAT_DMY:
						$date = explode(".", $post["date"]);
						$dt['mday'] = (int)$date[0];
						$dt['mon'] = (int)$date[1];
						$dt['year'] = (int)$date[2];
						break;

					case ilCalendarSettings::DATE_FORMAT_YMD:
						$date = explode("-", $post["date"]);
						$dt['mday'] = (int)$date[2];
						$dt['mon'] = (int)$date[1];
						$dt['year'] = (int)$date[0];
						break;

					case ilCalendarSettings::DATE_FORMAT_MDY:
						$date = explode("/", $post["date"]);
						$dt['mday'] = (int)$date[1];
						$dt['mon'] = (int)$date[0];
						$dt['year'] = (int)$date[2];
						break;
				}

				if($this->getShowTime())
				{
					if($ilUser->getTimeFormat() == ilCalendarSettings::TIME_FORMAT_12)
					{
						$seconds = "";
						if($this->getShowSeconds())
						{
							$seconds = ":\s*([0-9]{1,2})\s*";
						}
						if(preg_match("/([0-9]{1,2})\s*:\s*([0-9]{1,2})\s*".$seconds."(am|pm)/", trim(strtolower($post["time"])), $matches))
						{
							$dt['hours'] = (int)$matches[1];
							$dt['minutes'] = (int)$matches[2];
							if($seconds)
							{
								$dt['seconds'] = (int)$time[2];
								$ampm = $matches[4];
							}
							else
							{
								$dt['seconds'] = 0;
								$ampm = $matches[3];
							}
							if($dt['hours'] == 12)
							{
								if($ampm == "am")
								{
									$dt['hours'] = 0;
								}
							}
							else if($ampm == "pm")
							{
								$dt['hours'] += 12;
							}
						}
					}
					else
					{
						$time = explode(":", $post["time"]);
						$dt['hours'] = (int)$time[0];
						$dt['minutes'] = (int)$time[1];
						$dt['seconds'] = (int)$time[2];
					}
				}
			}
		}

		if($dt)
		{
			$date = new ilDateTime(NULL, 0, $ilUser->getTimeZone());
			if($date->setDate($dt, IL_CAL_FKT_GETDATE))
			{
				return $date;
			}
		}
	}
	
	/**
	* Set value by array
	*
	* @param	array	$a_values	value array
	*/
	function setValueByArray($a_values)
	{
		global $ilUser;

		$date = $this->convertIncomingValues($a_values[$this->getPostVar()]);
		if($date)
		{
			$this->setDate($date);
		}

		foreach($this->getSubItems() as $item)
		{
			$item->setValueByArray($a_values);
		}
	}

	/**
	* Check input, strip slashes etc. set alert, if input is not ok.
	*
	* @return	boolean		Input ok, true/false
	*/	
	function checkInput()
	{
		if ($this->getDisabled())
		{
			return true;
		}

		$date = $this->convertIncomingValues($_POST[$this->getPostVar()]);
		if($date)
		{
			$this->setDate($date);
			return true;
		}
		return false;
	}

	/**
	* Insert property html
	*
	*/
	function render()
	{
		global $lng,$ilUser;
		
		$tpl = new ilTemplate("tpl.prop_datetime.html", true, true, "Services/Form");

		$lng->loadLanguageModule("jscalendar");
		require_once("./Services/Calendar/classes/class.ilCalendarUtil.php");
		ilCalendarUtil::initJSCalendar();
		
		if(strlen($this->getActivationPostVar()))
		{
			$tpl->setCurrentBlock('prop_date_activation');
			$tpl->setVariable('CHECK_ENABLED_DATE',$this->getActivationPostVar());
			$tpl->setVariable('TXT_DATE_ENABLED',$this->activation_title);
			$tpl->setVariable('CHECKED_ENABLED',$this->activation_checked ? 'checked="checked"' : '');
			$tpl->setVariable('CHECKED_DISABLED',$this->getDisabled() ? 'disabled="disabled" ' : '');
			$tpl->parseCurrentBlock();
		}

		if($this->getMode() == self::MODE_SELECT)
		{
			if(is_a($this->getDate(),'ilDate'))
			{
				$date_info = $this->getDate()->get(IL_CAL_FKT_GETDATE,'','UTC');
			}
			elseif(is_a($this->getDate(),'ilDateTime'))
			{
				$date_info = $this->getDate()->get(IL_CAL_FKT_GETDATE,'',$ilUser->getTimeZone());
			}
			else
			{
				$this->setDate(new ilDateTime(time(), IL_CAL_UNIX));
				$date_info = $this->getDate()->get(IL_CAL_FKT_GETDATE,'',$ilUser->getTimeZone());
			}
		}

		if ($this->getShowDate())
		{
			if($this->getMode() == self::MODE_SELECT)
			{
				$tpl->setCurrentBlock("prop_date_input_select_setup");
				$tpl->setVariable("INPUT_FIELDS_DATE", $this->getPostVar()."[date]");
				$tpl->parseCurrentBlock();

				$tpl->setCurrentBlock("prop_date");
				$tpl->setVariable("DATE_SELECT",
					ilUtil::makeDateSelect($this->getPostVar()."[date]", $date_info['year'], $date_info['mon'], $date_info['mday'],
						$this->startyear,true,array('disabled' => $this->getDisabled()), $this->getShowEmpty()));		
			}
			else
			{
				$value = $this->getDate();
				if($value)
				{
					$value = substr($this->getDate()->get(IL_CAL_DATETIME), 0, 10);
					$day = substr($value, 8, 2);
					$month = substr($value, 6, 2);
					$year = substr($value, 0, 4);
				}

				switch($ilUser->getDateFormat())
				{
					case ilCalendarSettings::DATE_FORMAT_DMY:
						if($value)
						{
							$value = date("d.m.Y", mktime(0, 0, 0, $month, $day, $year));
						}
						$format = "%d.%m.%Y";
						break;

					case ilCalendarSettings::DATE_FORMAT_YMD:
						if($value)
						{
							$value = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
						}
						$format = "%Y-%m-%d";
						break;

					case ilCalendarSettings::DATE_FORMAT_MDY:
						if($value)
						{
							$value = date("m/d/Y", mktime(0, 0, 0, $month, $day, $year));
						}
						$format = "%m/%d/%Y";
						break;
				}
				
				$tpl->setCurrentBlock("prop_date_input_field");
				$tpl->setVariable("DATE_ID", $this->getPostVar());
				$tpl->setVariable("DATE_VALUE", $value); 
				$tpl->setVariable("DISABLED", $this->getDisabled() ? " disabled=\"disabled\"" : "");
				$tpl->parseCurrentBlock();

				$tpl->setCurrentBlock("prop_date_input_field_setup");
				$tpl->setVariable("DATE_ID", $this->getPostVar());
				$tpl->setVariable("DATE_FIELD_FORMAT", $format);
				$tpl->parseCurrentBlock();
			}

			$tpl->setCurrentBlock("prop_date");
			$tpl->setVariable("IMG_DATE_CALENDAR", ilUtil::getImagePath("calendar.png"));
			$tpl->setVariable("TXT_DATE_CALENDAR", $lng->txt("open_calendar"));
			$tpl->setVariable("DATE_ID", $this->getPostVar());

			include_once './Services/Calendar/classes/class.ilCalendarUserSettings.php';
			$tpl->setVariable('DATE_FIRST_DAY',ilCalendarUserSettings::_getInstance()->getWeekStart());

			$tpl->parseCurrentBlock();
		}
		if($this->getShowTime())
		{
			if($this->getMode() == self::MODE_INPUT)
			{
				$value = $this->getDate();
				if($value)
				{
					if(!$this->getShowSeconds())
					{
						$value = substr($value->get(IL_CAL_DATETIME), 11, 5);
						if($ilUser->getTimeFormat() == ilCalendarSettings::TIME_FORMAT_12)
						{
							$value = date("g:ia", mktime(substr($value, 0, 2), substr($value, 3, 2)));
						}
					}
					else
					{
						$value = substr($value->get(IL_CAL_DATETIME), 11, 8);
						if($ilUser->getTimeFormat() == ilCalendarSettings::TIME_FORMAT_12)
						{
							$value = date("g:i:sa", mktime(substr($value, 0, 2), substr($value, 3, 2), substr($value, 6, 2)));
						}
					}
				}
				
				$tpl->setCurrentBlock("prop_time_input_field");
                $tpl->setVariable("DATE_ID", $this->getPostVar());
				$tpl->setVariable("TIME_VALUE", $value); 
				$tpl->setVariable("DISABLED", $this->getDisabled() ? " disabled=\"disabled\"" : "");
				$tpl->parseCurrentBlock();
			}
			
			$tpl->setCurrentBlock("prop_time");

			if($this->getMode() == self::MODE_SELECT)
			{
				$tpl->setVariable("TIME_SELECT",
					ilUtil::makeTimeSelect($this->getPostVar()."[time]", !$this->getShowSeconds(),
					$date_info['hours'], $date_info['minutes'], $date_info['seconds'],
					true,array('minute_steps' => $this->getMinuteStepSize(),
								'disabled' => $this->getDisabled())));
			}
			
			$tpl->setVariable("TXT_TIME", $this->getShowSeconds()
				? "(".$lng->txt("hh_mm_ss").")"
				: "(".$lng->txt("hh_mm").")");
			
			$tpl->parseCurrentBlock();
		}
		
		if ($this->getShowTime() && $this->getShowDate())
		{
			$tpl->setVariable("DELIM", "<br />");
		}

		return $tpl->get();
	}

	/**
	* Insert property html
	*
	* @return	int	Size
	*/
	function insert(&$a_tpl)
	{
		$html = $this->render();

		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $html);
		$a_tpl->parseCurrentBlock();
	}

	/**
	* Get HTML for table filter
	*/
	function getTableFilterHTML()
	{
		$html = $this->render();
		return $html;
	}

	/**
	* serialize data
	*/
	function serializeData()
	{
		return serialize($this->getDate());
	}
	
	/**
	* unserialize data
	*/
	function unserializeData($a_data)
	{
		$data = unserialize($a_data);

		if (is_object($data))
		{
			$this->setDate($data);
		}
	}

}

?>