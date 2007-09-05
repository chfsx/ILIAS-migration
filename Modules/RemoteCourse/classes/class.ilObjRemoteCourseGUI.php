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

include_once('classes/class.ilObjectGUI.php');

/** 
* 
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* 
* @ilCtrl_Calls ilObjRemoteCourseGUI: ilPermissionGUI, ilInfoScreenGUI
* @ingroup ModulesRemoteCourse 
*/

class ilObjRemoteCourseGUI extends ilObjectGUI
{
	/**
	 * Contructor
	 *
	 * @access public
	 */
	public function __construct($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
	{
		$this->type = 'rcrs';
		parent::ilObjectGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

		$this->lng->loadLanguageModule('rcrs');
		$this->lng->loadLanguageModule('crs');
	}

	/**
	 * Execute command
	 *
	 * @access public
	 *
	 */
	public function executeCommand()
	{
		global $rbacsystem,$ilErr,$ilAccess;

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		$this->prepareOutput();

		switch($next_class)
		{
			case 'ilinfoscreengui':
				$this->infoScreen();	// forwards command
				break;
		
			case 'ilpermissiongui':
				$this->tabs_gui->setTabActive('perm_settings');
				include_once("./classes/class.ilPermissionGUI.php");
				$this->ctrl->forwardCommand(new ilPermissionGUI($this));
				break;

			default:
				if(!$cmd || $cmd == 'view')
				{
					$cmd = "editSettings";
				}
				$cmd .= "Object";
				$this->$cmd();
				break;
		}
		return true;
	}
	
	/**
	 * get tabs
	 *
	 * @access public
     * @param	object	tabs gui object
	 */
	public function getTabs($tabs_gui)
	{
		global $ilAccess;

		if($ilAccess->checkAccess('visible','',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("info_short",
				$this->ctrl->getLinkTarget($this, "infoScreen"));
		}

		if($ilAccess->checkAccess('write','',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("edit",
				$this->ctrl->getLinkTarget($this, "edit"),
				array(),
				"");
		}
		if ($ilAccess->checkAccess('edit_permission','',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), 
				array("perm","info","owner"), 'ilpermissiongui');
		}
	}
	
	/**
	* this one is called from the info button in the repository
	* not very nice to set cmdClass/Cmd manually, if everything
	* works through ilCtrl in the future this may be changed
	*/
	function infoScreenObject()
	{
		$this->ctrl->setCmd("showSummary");
		$this->ctrl->setCmdClass("ilinfoscreengui");
		$this->infoScreen();
	}
	
	
	/**
	 * show info screen
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function infoScreen()
	{
		global $ilErr,$ilAccess;

		if(!$ilAccess->checkAccess('visible','',$this->object->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('msg_no_perm_read'),$ilErr->MESSAGE);
		}

		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);
		
		$info->addSection($this->lng->txt('crs_general_info'));
		$info->addProperty($this->lng->txt('title'),$this->object->getTitle());
		if(strlen($this->object->getDescription()))
		{
			$info->addProperty($this->lng->txt('description'),$this->object->getDescription());
		}
		if(strlen($loc = $this->object->getLocalInformation()))
		{
			$info->addProperty($this->lng->txt('local_information'),$this->object->getLocalInformation());
		}
		
		// Access
		$info->addProperty($this->lng->txt('crs_visibility'),$this->availabilityToString());
		
		
		include_once('Services/AdvancedMetaData/classes/class.ilAdvancedMDRecordGUI.php');
		$record_gui = new ilAdvancedMDRecordGUI(ilAdvancedMDRecordGUI::MODE_INFO,
			'rcrs',$this->object->getId());
		$record_gui->setInfoObject($info);
		$record_gui->parse();
		
		$this->ctrl->forwardCommand($info);
	}
	
	/**
	 * Edit object
	 *
	 * @access protected
	 */
	public function editObject()
	{
		global $ilErr,$ilAccess;

		if(!$ilAccess->checkAccess('write','',$this->object->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('msg_no_perm_read'),$ilErr->MESSAGE);
		}
		$this->tabs_gui->setTabActive('edit');
	 	
	 	$this->initEditTable();
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.edit.html','Modules/RemoteCourse');
		$this->tpl->setVariable('EDIT_TABLE',$this->form->getHTML());
	}
	
	/**
	 * update object
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function updateObject()
	{
		global $ilErr,$ilAccess;

		if(!$ilAccess->checkAccess('write','',$this->object->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('msg_no_perm_read'),$ilErr->MESSAGE);
		}
		
		$this->object->setLocalInformation(ilUtil::stripSlashes($_POST['local_info']));
		#$this->object->setAvailabilityType($_POST['activation_type']);
		#$this->object->setStartingTime($_POST['start']);
		#$this->object->setEndingTime($_POST['end']);
		$this->object->update();
		
		// Save advanced meta data
		include_once('Services/AdvancedMetaData/classes/class.ilAdvancedMDRecordGUI.php');
		$record_gui = new ilAdvancedMDRecordGUI(ilAdvancedMDRecordGUI::MODE_EDITOR,
			'rcrs',$this->object->getId());
		$record_gui->loadFromPost();
		$record_gui->saveValues();

		ilUtil::sendInfo($this->lng->txt("settings_saved"));
		$this->editObject();
		return true;
	}

	/**
	 * Init edit settings table
	 *
	 * @access protected
	 */
	protected function initEditTable()
	{
		if(is_object($this->form))
		{
			return true;
		}
		
		$this->lng->loadLanguageModule('crs');
	
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		$this->form->setTableWidth('75%');
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		$this->form->setTitle($this->lng->txt('rcrs_general_info'));
		$this->form->addCommandButton('update',$this->lng->txt('save'));
		$this->form->addCommandButton('edit',$this->lng->txt('cancel'));
		
		$text = new ilTextInputGUI($this->lng->txt('title'),'title');
		$text->setValue($this->object->getTitle());
		$text->setSize(40);
		$text->setMaxLength(128);
		$text->setDisabled(true);
		$this->form->addItem($text);
		
		
		$area = new ilTextAreaInputGUI($this->lng->txt('description'),'description');
		$area->setValue($this->object->getDescription());
		$area->setRows(3);
		$area->setCols(80);
		$area->setDisabled(true);
		$this->form->addItem($area);

		$area = new ilTextAreaInputGUI($this->lng->txt('local_info'),'local_info');
		$area->setValue($this->object->getLocalInformation());
		$area->setRows(3);
		$area->setCols(80);
		$this->form->addItem($area);
		
		$radio_grp = new ilRadioGroupInputGUI($this->lng->txt('crs_visibility'),'activation_type');
		$radio_grp->setValue($this->object->getAvailabilityType());
		$radio_grp->setDisabled(true);

		$radio_opt = new ilRadioOption($this->lng->txt('crs_visibility_unvisible'),ilObjRemoteCourse::ACTIVATION_OFFLINE);
		$radio_grp->addOption($radio_opt);

		$radio_opt = new ilRadioOption($this->lng->txt('crs_visibility_limitless'),ilObjRemoteCourse::ACTIVATION_UNLIMITED);
		$radio_grp->addOption($radio_opt);	

		$radio_opt = new ilRadioOption($this->lng->txt('crs_visibility_until'),ilObjRemoteCourse::ACTIVATION_LIMITED);
		
		$start = new ilDateTimeInputGUI($this->lng->txt('crs_start'),'start');
		$start->setUnixTime(time());
		$start->setDisabled(true);
		$start->setShowTime(true);
		$radio_opt->addSubItem($start);
		$end = new ilDateTimeInputGUI($this->lng->txt('crs_end'),'end');
		$end->setUnixTime(time());
		$end->setDisabled(true);
		$end->setShowTime(true);
		$radio_opt->addSubItem($end);
		
		$radio_grp->addOption($radio_opt);
		$this->form->addItem($radio_grp);	

		include_once('Services/AdvancedMetaData/classes/class.ilAdvancedMDRecordGUI.php');
		$record_gui = new ilAdvancedMDRecordGUI(ilAdvancedMDRecordGUI::MODE_EDITOR,'rcrs',$this->object->getId());
		$record_gui->setPropertyForm($this->form);
		$record_gui->parse();
	}
	
	/**
	 * 
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function availabilityToString()
	{
	 	switch($this->object->getAvailabilityType())
	 	{
	 		case ilObjRemoteCourse::ACTIVATION_OFFLINE:
	 			return $this->lng->txt('offline');
	 		
	 		case ilObjRemoteCourse::ACTIVATION_UNLIMITED:
	 			return $this->lng->txt('crs_unlimited');
	 		
	 		case ilObjRemoteCourse::ACTIVATION_LIMITED:
	 			return $this->lng->txt('crs_from').' '.ilFormat::formatUnixTime($this->object->getStartingTime(),true).
	 				'<br /> '.$this->lng->txt('crs_to').' '.ilFormat::formatUnixTime($this->object->getEndingTime(),true);
	 	}
	 	return '';
	}
}
?>