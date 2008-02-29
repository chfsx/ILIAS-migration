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

include_once('Services/Calendar/classes/class.ilDate.php');
include_once('Services/Calendar/classes/class.ilCalendarHeaderNavigationGUI.php');


/** 
* 
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* 
* @ilCtrl_IsCalledBy ilCalendarMonthBlockGUI: ilColumnGUI
* @ingroup ServicesCalendar 
*/

include_once('Services/Block/classes/class.ilBlockGUI.php');

class ilCalendarMonthBlockGUI extends ilBlockGUI
{
	public static $block_type = 'cal';
	
	protected $lng;
	protected $ctrl;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function __construct()
	{
		global $ilCtrl, $lng, $ilUser;
		
		parent::ilBlockGUI();
		
		$this->seed = new ilDate($_GET['seed'] ? $_GET['seed'] : date('Y-m-d',time()),ilDateTime::FORMAT_DATE);

		$this->lng = $lng;
		$this->lng->loadLanguageModule('dateplaner');
		$this->ctrl = $ilCtrl;
		
		
		//$this->setImage(ilUtil::getImagePath("icon_bm_s.gif"));
		$this->setTitle($lng->txt('app_month'));
		$this->setEnableNumInfo(false);
		$this->setLimit(99999);
		$this->setColSpan(10);
		$this->setBigMode(true);
		$this->allow_moving = false;
	}
	
	/**
	 * get block type
	 *
	 * @access public
	 * @static
	 *
	 * @param
	 */
	public static function getBlockType()
	{
		return self::$block_type;
	}
	
	/**
	 * is repository object
	 *
	 * @access public
	 * @static
	 *
	 */
	public static function isRepositoryObject()
	{
		return false;
	}
	
	/**
	 * Execute command
	 *
	 * @access public
	 * 
	 */
	public function executeCommand()
	{
		global $ilCtrl;

		$next_class = $ilCtrl->getNextClass();
		$cmd = $ilCtrl->getCmd("getHTML");

		return $this->$cmd();
	}
	
	/**
	 * fill data section
	 *
	 * @access public
	 * 
	 */
	public function fillDataSection()
	{
		$tpl = new ilTemplate('tpl.month_view.html',true,true,'Services/Calendar');
		
		$navigation = new ilCalendarHeaderNavigationGUI($this,$this->seed,ilDateTime::MONTH);
		$tpl->setVariable('NAVIGATION',$navigation->getHTML());
		
		for($i = 1;$i < 8;$i++)
		{
			$tpl->setCurrentBlock('month_header_col');
			$tpl->setVariable('TXT_WEEKDAY',ilCalendarUtil::_numericDayToString($i,true));
			$tpl->parseCurrentBlock();
		}
		
		$counter = 0;
		foreach(ilCalendarUtil::_buildMonthDayList($this->seed->get(ilDateTime::FORMAT_FKT_DATE,'m'),$this->seed->get(ilDateTime::FORMAT_FKT_DATE,'Y'))->get() as $date)
		{
			$counter++;

			$tpl->setCurrentBlock('month_col');
			$day = $date->get(ilDateTime::FORMAT_FKT_DATE,'j');
			$month = $date->get(ilDateTime::FORMAT_FKT_DATE,'n');
			
			if($day == 1)
			{
				$month_day = '1 '.ilCalendarUtil::_numericMonthToString($month,false);
			}
			else
			{
				$month_day = $day;
			}
			
			$tpl->setVariable('MONTH_DAY',$month_day);
			$tpl->setVariable('NEW_SRC',ilUtil::getImagePath('new.gif','calendar'));
			$tpl->setVariable('OPEN_SRC',ilUtil::getImagePath('open.gif','calendar'));
			$tpl->parseCurrentBlock();
			
			
			if($counter and !($counter % 7))
			{
				$tpl->setCurrentBlock('month_row');
				$tpl->parseCurrentBlock();
			}
		}
		$this->setDataSection($tpl->get());
	}
	
}


?>