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

/** 
*
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
* 
* @ilCtrl_Calls ilObjCalendarSettingsGUI: ilPermissionGUI
* @ingroup ServicesCalendar
*/

include_once('./classes/class.ilObjectGUI.php');

class ilObjCalendarSettingsGUI extends ilObjectGUI
{

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
	{
		global $lng;
		
		$this->type = 'cals';
		parent::ilObjectGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

		$this->lng = $lng;
		$this->lng->loadLanguageModule('dateplaner');
		$this->lng->loadLanguageModule('jscalendar');
	}

	/**
	 * Execute command
	 *
	 * @access public
	 *
	 */
	public function executeCommand()
	{
		global $ilErr,$ilAccess;

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		$this->prepareOutput();

		if(!$ilAccess->checkAccess('read','',$this->object->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('no_permission'),$ilErr->WARNING);
		}

		switch($next_class)
		{
			case 'ilpermissiongui':
				$this->tabs_gui->setTabActive('perm_settings');
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			default:
				$this->tabs_gui->setTabActive('settings');
				$this->initCalendarSettings();
				if(!$cmd || $cmd == 'view')
				{
					$cmd = "settings";
				}

				$this->$cmd();
				break;
		}
		return true;
	}
	

	/**
	 * Get tabs
	 *
	 * @access public
	 *
	 */
	public function getAdminTabs()
	{
		global $rbacsystem, $ilAccess;

		if ($ilAccess->checkAccess("read",'',$this->object->getRefId()))
		{
			$this->tabs_gui->addTarget("settings",
				$this->ctrl->getLinkTarget($this, "settings"),
				array("settings", "view"));
		}

		if ($ilAccess->checkAccess('edit_permission','',$this->object->getRefId()))
		{
			$this->tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass('ilpermissiongui',"perm"),
				array(),'ilpermissiongui');
		}
	}

	/**
	* Edit settings.
	*/
	public function settings()
	{
		include_once('./Services/Calendar/classes/class.ilDateTime.php');
		
		include_once('./Services/Calendar/classes/iCal/class.ilICalParser.php');
		
		include_once('./Services/Calendar/classes/class.ilCalendarRecurrenceCalculator.php');
		include_once('./Services/Calendar/classes/class.ilCalendarRecurrence.php');
		include_once('./Services/Calendar/classes/class.ilCalendarEntry.php');
				
		#$parser = new ilICalParser('./extern/Feiertage.ics',ilICalParser::INPUT_FILE);
		#$parser->setCategoryId(6);
		#$parser->parse();
		/*
		$calc = new ilCalendarRecurrenceCalculator(
			new ilCalendarEntry(273),
			new ilCalendarRecurrence(43));
	
		$list = $calc->calculateDateList(
				new ilDateTime('2008-04-01',IL_CAL_DATE),
				new ilDateTime('2008-04-31',IL_CAL_DATE));
		*/		
		#echo "RESULT: ".$list;
		/*
		$zeit = microtime(true);
		
		for($i = 0;$i < 1;$i++)
		{
			$calc = new ilCalendarRecurrenceCalculator(
				new ilCalendarEntry(1061),
				new ilCalendarRecurrence(72));
	
			$list = $calc->calculateDateList(
					new ilDateTime('2008-03-01',IL_CAL_DATE),
					new ilDateTime('2008-03-31',IL_CAL_DATE));
		}		
		echo "NEEDS: ".(microtime(true) - $zeit).' seconds.<br>';
		foreach($list->get() as $event)
		{
			echo $event->get(IL_CAL_DATETIME,'',$this->settings->getDefaultTimeZone()).'<br />';
		}
		*/
		#$parser = new ilICalParser('./extern/fc.ics',ilICalParser::INPUT_FILE);
		#$parser->setCategoryId(11);
		#$parser = new ilICalParser('./Feiertage.ics',ilICalParser::INPUT_FILE);
		#$parser->parse();
		#$entry = new ilCalendarEntry(927);
		/*
		$timezone = "US/Alaska";
		echo $entry->getTitle().'<br>';
		echo $entry->getStart()->get(IL_CAL_DATE,'',$timezone).'<br>';
		echo $entry->getStart()->get(IL_CAL_DATETIME,'',$timezone).'<br>';		
		echo $entry->getEnd()->get(IL_CAL_DATE,'',$timezone).'<br>';
		echo $entry->getEnd()->get(IL_CAL_DATETIME,'',$timezone).'<br>';		

		$entry = new ilCalendarEntry(928);
		echo $entry->getTitle().'<br>';
		echo $entry->getStart()->get(IL_CAL_DATE,'',$timezone).'<br>';
		echo $entry->getStart()->get(IL_CAL_DATETIME,'',$timezone).'<br>';		
		echo $entry->getEnd()->get(IL_CAL_DATE,'',$timezone).'<br>';
		echo $entry->getEnd()->get(IL_CAL_DATETIME,'',$timezone).'<br>';		
		*/
		$this->tabs_gui->setTabActive('settings');
		$this->initFormSettings();
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.settings.html','Services/Calendar');
		$this->tpl->setVariable('CAL_SETTINGS',$this->form->getHTML());
		return true;
	}
	
	/**
	 * save settings
	 *
	 * @access protected
	 */
	protected function save()
	{
		$this->settings->setEnabled((int) $_POST['enable']);
		$this->settings->setDefaultWeekStart((int) $_POST['default_week_start']);
		$this->settings->setDefaultTimeZone(ilUtil::stripSlashes($_POST['default_timezone']));
		$this->settings->setDefaultTimeFormat((int) $_POST['default_time_format']);
		$this->settings->setEnableGroupMilestones((int) $_POST['enable_grp_milestones']);
		$this->settings->setDefaultDayStart((int) $_POST['dst']);
		$this->settings->setDefaultDayEnd((int) $_POST['den']);
		$this->settings->enableSynchronisationCache((bool) $_POST['sync_cache']);
		$this->settings->setSynchronisationCacheMinutes((int) $_POST['sync_cache_time']);
		$this->settings->setCacheMinutes((int) $_POST['cache_time']);
		$this->settings->useCache((bool) $_POST['cache']);	
		
		if(((int) $_POST['den']) < (int) $_POST['dst'])
		{
			ilUtil::sendFailure($this->lng->txt('cal_dstart_dend_warn'));
			$this->settings();
			return false;
		}
		
		$this->settings->save();
		
		ilUtil::sendSuccess($this->lng->txt('settings_saved'));
		$this->settings();
	}

	/**
	 * init calendar settings
	 *
	 * @access protected
	 */
	protected function initCalendarSettings()
	{
		include_once('Services/Calendar/classes/class.ilCalendarSettings.php');
		$this->settings = ilCalendarSettings::_getInstance();
	}
	
	/**
	 * Init settings property form
	 *
	 * @access protected
	 */
	protected function initFormSettings()
	{
		if(is_object($this->form))
		{
			return true;
		}
		include_once('Services/Calendar/classes/class.ilCalendarUtil.php');
		include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
		
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		$this->form->setTitle($this->lng->txt('cal_global_settings'));
		$this->form->addCommandButton('save',$this->lng->txt('save'));
		#$this->form->addCommandButton('cancel',$this->lng->txt('cancel'));
		
		$check = new ilCheckboxInputGUI($this->lng->txt('enable_calendar'),'enable');
		$check->setValue(1);
		$check->setChecked($this->settings->isEnabled() ? true : false);
		$this->form->addItem($check);
		
		$server_tz = new ilNonEditableValueGUI($this->lng->txt('cal_server_tz'));
		$server_tz->setValue(ilTimeZone::_getDefaultTimeZone());
		$this->form->addItem($server_tz);
		
		$select = new ilSelectInputGUI($this->lng->txt('cal_def_timezone'),'default_timezone');
		$select->setOptions(ilCalendarUtil::_getShortTimeZoneList());
		$select->setInfo($this->lng->txt('cal_def_timezone_info'));
		$select->setValue($this->settings->getDefaultTimeZone());
		$this->form->addItem($select);
		
		$select = new ilSelectInputGUI($this->lng->txt('cal_def_time_format'),'default_time_format');
		$select->setOptions(array(
			ilCalendarSettings::TIME_FORMAT_24 => '13:00',
			ilCalendarSettings::TIME_FORMAT_12 => '1:00pm'));
		$select->setInfo($this->lng->txt('cal_def_time_format_info'));
		$select->setValue($this->settings->getDefaultTimeFormat());
		$this->form->addItem($select);
		
		
		$radio = new ilRadioGroupInputGUI($this->lng->txt('cal_def_week_start'),'default_week_start');
		$radio->setValue($this->settings->getDefaultWeekStart());
	
		$option = new ilRadioOption($this->lng->txt('l_su'),0);
		$radio->addOption($option);
		$option = new ilRadioOption($this->lng->txt('l_mo'),1);
		$radio->addOption($option);
		
		// Calendar cache		
		$cache = new ilRadioGroupInputGUI($this->lng->txt('cal_cache'),'cache');
		$cache->setValue((int) $this->settings->isCacheUsed());
		$cache->setInfo($this->lng->txt('cal_cache_info'));
		$cache->setRequired(true);
		
		$sync_cache = new ilRadioOption($this->lng->txt('cal_cache_disabled'),0);
		$cache->addOption($sync_cache);
		
		$sync_cache = new ilRadioOption($this->lng->txt('cal_cache_enabled'),1);
		$cache->addOption($sync_cache);
		
		$cache_t = new ilNumberInputGUI('','cache_time');
		$cache_t->setValue($this->settings->getCacheMinutes());
		$cache_t->setMinValue(0);
		$cache_t->setSize(3);
		$cache_t->setMaxLength(3);
		$cache_t->setSuffix($this->lng->txt('form_minutes'));
		$sync_cache->addSubItem($cache_t);
		$this->form->addItem($cache);
		
		// enable milestone planning in groups
		$checkm = new ilCheckboxInputGUI($this->lng->txt('cal_enable_group_milestones'),'enable_grp_milestones');
		$checkm->setValue(1);
		$checkm->setChecked($this->settings->getEnableGroupMilestones() ? true : false);
		$checkm->setInfo($this->lng->txt('cal_enable_group_milestones_desc'));
		$this->form->addItem($checkm);
		
		$this->form->addItem($radio);
		
		// Day start
		$day_start = new ilSelectInputGUI($this->lng->txt('cal_day_start'),'dst');
		$day_start->setOptions(
			ilCalendarUtil::getHourSelection($this->settings->getDefaultTimeFormat())
		);
		$day_start->setValue($this->settings->getDefaultDayStart());
		$this->form->addItem($day_start);
		
		$day_end = new ilSelectInputGUI($this->lng->txt('cal_day_end'),'den');
		$day_end->setOptions(
			ilCalendarUtil::getHourSelection($this->settings->getDefaultTimeFormat())
		);
		$day_end->setValue($this->settings->getDefaultDayEnd());
		$this->form->addItem($day_end);
		
		// Synchronisation cache
		$sec = new ilFormSectionHeaderGUI();
		$sec->setTitle($this->lng->txt('cal_sync_header'));
		$this->form->addItem($sec);
		
		$cache = new ilRadioGroupInputGUI($this->lng->txt('cal_sync_cache'),'sync_cache');
		$cache->setValue((int) $this->settings->isSynchronisationCacheEnabled());
		$cache->setInfo($this->lng->txt('cal_sync_cache_info'));
		$cache->setRequired(true);
		
		$sync_cache = new ilRadioOption($this->lng->txt('cal_sync_disabled'),0);
		$cache->addOption($sync_cache);
		
		$sync_cache = new ilRadioOption($this->lng->txt('cal_sync_enabled'),1);
		$cache->addOption($sync_cache);
		
		$cache_t = new ilNumberInputGUI('','sync_cache_time');
		$cache_t->setValue($this->settings->getSynchronisationCacheMinutes());
		$cache_t->setMinValue(0);
		$cache_t->setSize(3);
		$cache_t->setMaxLength(3);
		$cache_t->setSuffix($this->lng->txt('form_minutes'));
		$sync_cache->addSubItem($cache_t);
		
		$this->form->addItem($cache);
		
	}
}
?>