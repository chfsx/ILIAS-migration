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
include_once("./classes/class.ilObjectGUI.php");


/**
* Meta Data Settings.
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
*
* @ilCtrl_Calls ilObjMDSettingsGUI: ilPermissionGUI, ilAdvancedMDSettingsGUI
*
* @ingroup ServicesMetaData
*/
class ilObjMDSettingsGUI extends ilObjectGUI
{

	/**
	 * Contructor
	 *
	 * @access public
	 */
	public function __construct($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
	{
		global $lng;
		
		$this->type = 'mds';
		parent::ilObjectGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

		$this->lng = $lng;
		$this->lng->loadLanguageModule("meta");
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

		if(!$ilAccess->checkAccess('read','',$this->object->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('no_permission'),$ilErr->WARNING);
		}

		switch($next_class)
		{
			case 'iladvancedmdsettingsgui':
				$this->tabs_gui->setTabActive('md_advanced');
				include_once('Services/AdvancedMetaData/classes/class.ilAdvancedMDSettingsGUI.php');
				$adv_md = new ilAdvancedMDSettingsGUI();
				$ret = $this->ctrl->forwardCommand($adv_md);
				break;
			
			case 'ilpermissiongui':
				$this->tabs_gui->setTabActive('perm_settings');
				include_once("./classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			default:
				$this->tabs_gui->setTabActive('settings');
				$this->initMDSettings();
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

		if ($rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->tabs_gui->addTarget("settings",
				$this->ctrl->getLinkTarget($this, "settings"),
				array("settings", "view"));

			$this->tabs_gui->addTarget("md_advanced",
				$this->ctrl->getLinkTargetByClass('iladvancedmdsettingsgui', ""),
				'',
				'iladvancedmdsettingsgui');
				
		}

		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$this->tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass('ilpermissiongui',"perm"),
				array(),'ilpermissiongui');
		}
	}

	/**
	* Edit news settings.
	*/
	public function settings()
	{
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.settings.html','Services/MetaData');
		
		$this->initSettingsForm();
		$this->tpl->setVariable('SETTINGS_TABLE',$this->form->getHTML());
		
		include_once("./Services/MetaData/classes/class.ilMDCopyrightTableGUI.php");
		$table_gui = new ilMDCopyrightTableGUI($this,'settings');
		$table_gui->setTitle($this->lng->txt("md_copyright_selection"));
		$table_gui->parseSelections();
		$table_gui->addCommandButton("updateCopyrightSelection", $this->lng->txt("save"));
		$table_gui->addCommandButton('addEntry',$this->lng->txt('add'));
		$table_gui->addMultiCommand("confirmDeleteEntries", $this->lng->txt("delete"));
		$table_gui->setSelectAllCheckbox("entry_id");
		$this->tpl->setVariable('COPYRIGHT_TABLE',$table_gui->getHTML());
		
		
	}

	/**
	* Save news and external webfeeds settings
	*/
	public function saveSettings()
	{
		$this->md_settings->activateCopyrightSelection((int) $_POST['active']);
		$this->md_settings->save();
		ilUtil::sendInfo($this->lng->txt('settings_saved'));
		$this->settings();
	}
	
	
	/**
	 * edit one selection
	 *
	 * @access public
	 * 
	 */
	public function editEntry()
	{
	 	$this->ctrl->saveParameter($this,'entry_id');
	 	$this->initCopyrightEditForm();
	 	$this->tpl->setContent($this->form->getHTML());
	}
	
	/**
	 * add new entry
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function addEntry()
	{
	 	$this->initCopyrightEditForm('add');
	 	$this->tpl->setContent($this->form->getHTML());
	}
	
	
	/**
	 * save new entry
	 *
	 * @access public
	 * 
	 */
	public function saveEntry()
	{
		global $ilErr;

		include_once('Services/MetaData/classes/class.ilMDCopyrightSelectionEntry.php');
		$this->entry = new ilMDCopyrightSelectionEntry(0);
	
		$this->entry->setTitle(ilUtil::stripSlashes($_POST['title']));
		$this->entry->setDescription(ilUtil::stripSlashes($_POST['description']));
		$this->entry->setCopyright($this->stripSlashes($_POST['copyright']));
		$this->entry->setLanguage('en');
		$this->entry->setCopyrightAndOtherRestrictions(true);
		$this->entry->setCosts(false);
		
		if(!$this->entry->validate())
		{
			ilUtil::sendInfo($this->lng->txt('fill_out_all_required_fields'));
			$this->addEntry();
			return false;
		}
		$this->entry->add();
		ilUtil::sendInfo($this->lng->txt('settings_saved'));
		$this->settings();
		return true;
	}
	
	/**
	 * confirm deletion of entries
	 *
	 * @access public
	 * 
	 */
	public function confirmDeleteEntries()
	{
	 	if(!is_array($_POST['entry_id']) or !count($_POST['entry_id']))
	 	{
	 		ilUtil::sendInfo($this->lng->txt('select_one'));
	 		$this->settings();
	 		return true;
	 	}
	 	
	 	include_once('Services/Utilities/classes/class.ilConfirmationGUI.php');
	 	$c_gui = new ilConfirmationGUI();
		
		// set confirm/cancel commands
		$c_gui->setFormAction($this->ctrl->getFormAction($this, "deleteEntries"));
		$c_gui->setHeaderText($this->lng->txt("md_delete_cp_sure"));
		$c_gui->setCancel($this->lng->txt("cancel"), "settings");
		$c_gui->setConfirm($this->lng->txt("confirm"), "deleteEntries");

		include_once('Services/MetaData/classes/class.ilMDCopyrightSelectionEntry.php');

		// add items to delete
		foreach($_POST["entry_id"] as $entry_id)
		{
			$entry = new ilMDCopyrightSelectionEntry($entry_id);
			$c_gui->addItem('entry_id[]',$entry_id,$entry->getTitle());
		}
		$this->tpl->setContent($c_gui->getHTML());
	}
	
	/**
	 * delete entries
	 *
	 * @access public
	 * 
	 */
	public function deleteEntries()
	{
	 	if(!is_array($_POST['entry_id']) or !count($_POST['entry_id']))
	 	{
	 		ilUtil::sendInfo($this->lng->txt('select_one'));
	 		$this->settings();
	 		return true;
	 	}

		include_once('Services/MetaData/classes/class.ilMDCopyrightSelectionEntry.php');
		foreach($_POST["entry_id"] as $entry_id)
		{
			$entry = new ilMDCopyrightSelectionEntry($entry_id);
			$entry->delete();
		}
		ilUtil::sendInfo($this->lng->txt('md_copyrights_deleted'));
		$this->settings();
		return true;
	}

	/**
	 * update one entry
	 *
	 * @access public
	 * 
	 */
	public function updateEntry()
	{
		global $ilErr;

		include_once('Services/MetaData/classes/class.ilMDCopyrightSelectionEntry.php');
		$this->entry = new ilMDCopyrightSelectionEntry((int) $_REQUEST['entry_id']);
	
		$this->entry->setTitle(ilUtil::stripSlashes($_POST['title']));
		$this->entry->setDescription(ilUtil::stripSlashes($_POST['description']));
		$this->entry->setCopyright($this->stripSlashes($_POST['copyright']));
		
		if(!$this->entry->validate())
		{
			ilUtil::sendInfo($this->lng->txt('fill_out_all_required_fields'));
			$this->editEntry();
			return false;
		}
		$this->entry->update();
		ilUtil::sendInfo($this->lng->txt('settings_saved'));
		$this->settings();
		return true;
	}
	
	
	/**
	 * 
	 *
	 * @access protected
	 */
	protected function initSettingsForm()
	{
		if(is_object($this->form))
		{
			return true;
		}
		include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		$this->form->setTitle($this->lng->txt('md_copyright_settings'));
		$this->form->addCommandButton('saveSettings',$this->lng->txt('save'));
		$this->form->addCommandButton('settings',$this->lng->txt('cancel'));
		
		$check = new ilCheckboxInputGUI($this->lng->txt('md_copyright_enabled'),'active');
		$check->setChecked($this->md_settings->isCopyrightSelectionActive());
		$check->setValue(1);
		$this->form->addItem($check);
	}
	
	/**
	 * 
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function initCopyrightEditForm($a_mode = 'edit')
	{
		if(is_object($this->form))
		{
			return true;
		}
		if(!is_object($this->entry))
		{
			include_once('Services/MetaData/classes/class.ilMDCopyrightSelectionEntry.php');
			$this->entry = new ilMDCopyrightSelectionEntry((int) $_REQUEST['entry_id']);
		}
		
		include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		
		$tit = new ilTextInputGUI($this->lng->txt('title'),'title');
		$tit->setValue($this->entry->getTitle());
		$tit->setRequired(true);
		$tit->setSize(40);
		$tit->setMaxLength(255);
		$this->form->addItem($tit);
		
		$des = new ilTextAreaInputGUI($this->lng->txt('description'),'description');
		$des->setValue($this->entry->getDescription());
		$des->setRows(3);
		$this->form->addItem($des);
		
		$cop = new ilTextAreaInputGUI($this->lng->txt('md_copyright_value'),'copyright');
		$cop->setValue($this->entry->getCopyright());
		$cop->setRows(5);
		$this->form->addItem($cop);
		
		switch($a_mode)
		{
			case 'edit':
				$this->form->setTitle($this->lng->txt('md_copyright_edit'));
				$this->form->addCommandButton('updateEntry',$this->lng->txt('save'));
				$this->form->addCommandButton('settings',$this->lng->txt('cancel'));
				break;
			
			case 'add':
				$this->form->setTitle($this->lng->txt('md_copyright_add'));
				$this->form->addCommandButton('saveEntry',$this->lng->txt('save'));
				$this->form->addCommandButton('settings',$this->lng->txt('cancel'));
				break;			
		}
		
	}
	
	/**
	 * init Md settings
	 *
	 * @access protected
	 */
	protected function initMDSettings()
	{
		include_once('Services/MetaData/classes/class.ilMDSettings.php');
		$this->md_settings = ilMDSettings::_getInstance();
	}
	
	/**
	 * Special function to strip slashes for copyright fields
	 *
	 * @access protected
	 */
	protected function stripSlashes($a_str)
	{
		if (ini_get("magic_quotes_gpc"))
		{
			$a_str = stripslashes($a_str);
		}
		return $a_str;		
	}
}
?>