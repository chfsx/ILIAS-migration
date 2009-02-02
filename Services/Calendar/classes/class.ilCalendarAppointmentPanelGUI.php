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
* GUI class for YUI appointment panels
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ServicesCalendar
*/
class ilCalendarAppointmentPanelGUI
{
	protected static $counter = 0;
	protected static $instance = null;
	
	protected $tpl = null;
	protected $lng = null;
	protected $ctrl = null;

	/**
	 * Singleton
	 *
	 * @access public
	 * @param
	 * @return
	 */
	protected function __construct()
	{
		global $lng,$ilCtrl;
		
		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
	}
	
	/**
	 * get singleton instance
	 *
	 * @access public
	 * @param
	 * @return
	 * @static
	 */
	public static function _getInstance()
	{
		if(isset(self::$instance) and self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new ilCalendarAppointmentPanelGUI();
	}
	
	
	/**
	 * get HTML
	 *
	 * @access public
	 * @param 
	 * @return
	 */
	public function getHTML($a_app)
	{
		global $tree;
		
		self::$counter++;
		
		$this->tpl = new ilTemplate('tpl.appointment_panel.html',true,true,'Services/Calendar');
		
		// Panel variables
		$this->tpl->setVariable('PANEL_NUM',self::$counter);
		$this->tpl->setVariable('PANEL_TITLE',$a_app['event']->getPresentationTitle());
		$this->tpl->setVariable('PANEL_DETAILS',$this->lng->txt('cal_details'));
		$this->tpl->setVariable('PANEL_TXT_DATE',$this->lng->txt('date'));
		
		if($a_app['fullday'])
		{
			$this->tpl->setVariable('PANEL_DATE',ilDatePresentation::formatPeriod(
				new ilDate($a_app['dstart'],IL_CAL_UNIX),
				new ilDate($a_app['dend'],IL_CAL_UNIX)));
		}
		else
		{
			$this->tpl->setVariable('PANEL_DATE',ilDatePresentation::formatPeriod(
				new ilDateTime($a_app['dstart'],IL_CAL_UNIX),
				new ilDateTime($a_app['dend'],IL_CAL_UNIX)));
		}
		if($a_app['event']->getLocation())
		{
			$this->tpl->setVariable('PANEL_TXT_WHERE',$this->lng->txt('cal_where'));
			$this->tpl->setVariable('PANEL_WHERE',$a_app['event']->getLocation());
		}
		if($a_app['event']->getDescription())
		{
			$this->tpl->setVariable('PANEL_TXT_DESC',$this->lng->txt('description'));
			$this->tpl->setVariable('PANEL_DESC',nl2br($a_app['event']->getDescription()));
		}

		include_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');
		$cat_id = ilCalendarCategoryAssignments::_lookupCategory($a_app['event']->getEntryId());
		$cat_info = ilCalendarCategories::_getInstance()->getCategoryInfo($cat_id);
		
		$this->tpl->setVariable('PANEL_TXT_CAL_TYPE',$this->lng->txt('cal_cal_type'));
		switch($cat_info['type'])
		{
			case ilCalendarCategory::TYPE_GLOBAL:
				$this->tpl->setVariable('PANEL_CAL_TYPE',$this->lng->txt('cal_type_system'));
				break;
				
			case ilCalendarCategory::TYPE_USR:
				$this->tpl->setVariable('PANEL_CAL_TYPE',$this->lng->txt('cal_type_personal'));
				break;
			
			case ilCalendarCategory::TYPE_OBJ:
				$type = ilObject::_lookupType($cat_info['obj_id']);
				$this->tpl->setVariable('PANEL_CAL_TYPE',$this->lng->txt('cal_type_'.$type));
				break;				
		}

		$this->tpl->setVariable('PANEL_TXT_CAL_NAME',$this->lng->txt('cal_calendar_name'));
		$this->tpl->setVariable('PANEL_CAL_NAME',$cat_info['title']);
		

		if($cat_info['editable'] and !$a_app['event']->isAutoGenerated())
		{
			$this->tpl->setCurrentBlock('panel_edit_link');
			$this->tpl->setVariable('TXT_PANEL_EDIT',$this->lng->txt('edit'));
			
			$this->ctrl->clearParametersByClass('ilcalendarappointmentgui');
			$this->ctrl->setParameterByClass('ilcalendarappointmentgui','app_id',$a_app['event']->getEntryId());
			$this->tpl->setVariable('PANEL_EDIT_HREF',$this->ctrl->getLinkTargetByClass('ilcalendarappointmentgui','edit'));
			$this->tpl->parseCurrentBlock();
		}			
		include_once('./Services/Calendar/classes/class.ilCalendarCategory.php');
		if($cat_info['type'] == ilCalendarCategory::TYPE_OBJ)
		{
			$refs = ilObject::_getAllReferences($cat_info['obj_id']);
			
			include_once('classes/class.ilLink.php');
			$href = ilLink::_getStaticLink(current($refs),ilObject::_lookupType($cat_info['obj_id']),true);
			$parent = $tree->getParentId(current($refs));
			$parent_title = ilObject::_lookupTitle(ilObject::_lookupObjId($parent));
			$this->tpl->setVariable('PANEL_TXT_LINK',$this->lng->txt('ext_link'));
			$this->tpl->setVariable('PANEL_LINK_HREF',$href);
			$this->tpl->setVariable('PANEL_LINK_NAME',ilObject::_lookupTitle($cat_info['obj_id']));
			$this->tpl->setVariable('PANEL_PARENT',$parent_title);
		}
		
		return $this->tpl->get();
	}
}
?>