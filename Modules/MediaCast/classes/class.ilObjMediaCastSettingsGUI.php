<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
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
* Media Cast Settings.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilObjMediaCastSettingsGUI: ilPermissionGUI
* @ilCtrl_IsCalledBy ilObjMediaCastSettingsGUI: ilAdministrationGUI
*
* @ingroup ModulesMediaCast
*/
class ilObjMediaCastSettingsGUI extends ilObjectGUI
{
    private static $ERROR_MESSAGE;
	/**
	 * Contructor
	 *
	 * @access public
	 */
	public function __construct($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
	{
		$this->type = 'mcts';
		parent::ilObjectGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

		$this->lng->loadLanguageModule('mcst');
		$this->initMediaCastSettings();
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
			case 'ilpermissiongui':
				$this->tabs_gui->setTabActive('perm_settings');
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			default:
				if(!$cmd || $cmd == 'view')
				{
					$cmd = "editSettings";
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
			$this->tabs_gui->addTarget("mcst_edit_settings",
				$this->ctrl->getLinkTarget($this, "editSettings"),
				array("editSettings", "view"));
		}

		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$this->tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass('ilpermissiongui',"perm"),
				array(),'ilpermissiongui');
		}
	}

	/**
	* Edit mediacast settings.
	*/
	public function editSettings()
	{
		$this->tabs_gui->setTabActive('mcst_edit_settings');		
		$this->initFormSettings();
		return true;
	}

	/**
	* Save mediacast settings
	*/
	public function saveSettings()
	{
		global $ilCtrl;
		foreach ($this->settings->getPurposeSuffixes() as $purpose => $filetypes) {
			$purposeSuffixes[$purpose] = explode(",", preg_replace("/[^\w,]/", "", strtolower($_POST[$purpose])));			
		}

		$this->settings->setPurposeSuffixes($purposeSuffixes);
		$this->settings->setDefaultAccess ($_POST["defaultaccess"]);

		$this->settings->save();
		
		ilUtil::sendSuccess($this->lng->txt("settings_saved"),true);
		
		$ilCtrl->redirect($this, "view");
	}

	/**
	* Save mediacast settings
	*/
	public function cancel()
	{
		global $ilCtrl;
		
		$ilCtrl->redirect($this, "view");
	}
	
	/**
	 * iniitialize settings storage for media cast
	 *
	 */
	protected function initMediaCastSettings()
	{
		include_once('Modules/MediaCast/classes/class.ilMediaCastSettings.php');
		$this->settings = ilMediaCastSettings::_getInstance();
	}
	
	/**
	 * Init settings property form
	 *
	 * @access protected
	 */
	protected function initFormSettings()
	{
	    global $lng;
		include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
		
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->lng->txt('mcst_file_extension_settings'));
		$form->addCommandButton('saveSettings',$this->lng->txt('save'));
		$form->addCommandButton('cancel',$this->lng->txt('cancel'));
		foreach ($this->settings->getPurposeSuffixes() as $purpose => $filetypes) 
		{
			$text = new ilTextInputGUI($lng->txt("mcst_".strtolower($purpose)."_settings_title"),$purpose);
			$text->setValue(implode(",",$filetypes));
			$text->setInfo($lng->txt("mcst_".strtolower($purpose)."_settings_info"));
			$form->addItem($text);
		}
		//Default Visibility
		$radio_group = new ilRadioGroupInputGUI($lng->txt("mcst_default_visibility"), "defaultaccess");
		$radio_option = new ilRadioOption($lng->txt("mcst_visibility_users"), "users");
		$radio_group->addOption($radio_option);					
		$radio_option = new ilRadioOption($lng->txt("mcst_visibility_public"), "public");
		$radio_group->addOption($radio_option);
		$radio_group->setInfo($lng->txt("mcst_news_item_visibility_info"));
		$radio_group->setRequired(false);			
		$radio_group->setValue($this->settings->getDefaultAccess());			
		#$ch->addSubItem($radio_group);
		$form->addItem($radio_group);
		$this->tpl->setContent($form->getHTML());
	}
}
?>