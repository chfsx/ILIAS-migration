<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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
* Class ilObjSearchSettingsGUI
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* @ilCtrl_Calls ilObjSearchSettingsGUI: ilPermissionGUI
* 
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "classes/class.ilObjectGUI.php";

class ilObjSearchSettingsGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjSearchSettingsGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output = true)
	{
		$this->type = "seas";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);

		$this->lng->loadLanguageModule('search');
	}

	function &executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();
		$this->prepareOutput();

		switch($next_class)
		{
			case 'ilpermissiongui':
				include_once("./classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			default:
				if($cmd == "" || $cmd == "view")
				{
					$cmd = "settings";
				}
				$cmd .= "Object";
				$this->$cmd();

				break;
		}
		return true;
	}
	
	function cancelObject()
	{
		ilUtil::sendInfo($this->lng->txt("msg_cancel"),true);
		$this->ctrl->redirect($this, "settings");
	}

	/**
	* Show settings
	* @access	public
	*/
	function settingsObject()
	{
		global $rbacsystem;

		include_once 'Services/WebServices/RPC/classes/class.ilRPCServerSettings.php';

		$rpc_settings =& new ilRPCServerSettings();

		if(!$rbacsystem->checkAccess('read',$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		$this->object->initSettingsObject();

		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.seas_settings.html','Services/Search');

		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_SEAS_TITLE",$this->lng->txt('seas_settings'));

		// Max hits
		$this->tpl->setVariable("TXT_MAX_HITS",$this->lng->txt('seas_max_hits'));
		$this->tpl->setVariable("TXT_MAX_HITS_INFO",$this->lng->txt('seas_max_hits_info'));
		for($i = 10; $i <= 100; $i += 10)
		{
			$max_hits[$i] = $i;
		}
		$this->tpl->setVariable('SELECT_MAX_HITS',ilUtil::formSelect($this->object->settings_obj->getMaxHits(),
																	 'max_hits',
																	 $max_hits,false,true));

		$this->tpl->setVariable("TXT_DIRECT",$this->lng->txt('search_direct'));
		$this->tpl->setVariable("TXT_INDEX",$this->lng->txt('search_index'));

		$this->tpl->setVariable("TXT_TYPE",$this->lng->txt('search_type'));
		$this->tpl->setVariable("TXT_LIKE_INFO",$this->lng->txt('search_like_info'));
		$this->tpl->setVariable("TXT_FULL_INFO",$this->lng->txt('search_full_info'));

		$this->tpl->setVariable("RADIO_TYPE_LIKE",ilUtil::formRadioButton($this->object->settings_obj->enabledIndex() ? 0 : 1,
																		  'search_index',0));

		$this->tpl->setVariable("RADIO_TYPE_FULL",ilUtil::formRadioButton($this->object->settings_obj->enabledIndex() ? 1 : 0,
																		  'search_index',1));

		// Lucene
		$this->tpl->setVariable("TXT_LUCENE",$this->lng->txt('search_lucene'));
		$this->tpl->setVariable("TXT_LUCENE_INFO",$this->lng->txt('search_lucene_info'));
		
		$this->tpl->setVariable("CHECK_TYPE_LUCENE",ilUtil::formCheckBox($this->object->settings_obj->enabledLucene() ? 1 : 0,
																		 'search_lucene',1));
		$this->tpl->setVariable("LUCENE_HOST",ilUtil::prepareFormOutput($rpc_settings->getHost()));
		$this->tpl->setVariable("LUCENE_PORT",ilUtil::prepareFormOutput($rpc_settings->getPort()));
									

		$this->tpl->setVariable("CMD_SUBMIT",'saveSettings');
		$this->tpl->setVariable("TXT_SUBMIT",$this->lng->txt('save'));
		$this->tpl->setVariable("TXT_CANCEL",$this->lng->txt('cancel'));

		return true;
	}

	/**
	* Save settings
	* @access	public
	*/
	function saveSettingsObject()
	{
		include_once 'Services/WebServices/RPC/classes/class.ilRPCServerSettings.php';

		global $rbacsystem;

		if(!$rbacsystem->checkAccess('write',$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		$this->object->initSettingsObject();
		$this->object->settings_obj->setMaxHits((int) $_POST['max_hits']);
		$this->object->settings_obj->enableIndex($_POST['search_index']);
		$this->object->settings_obj->enableLucene($_POST['search_lucene']);

		$rpc_settings =& new ilRPCServerSettings();
		if($this->object->settings_obj->enabledLucene() and !$rpc_settings->pingServer())
		{
			ilUtil::sendInfo($this->lng->txt('search_no_connection_lucene'),true);
			$this->ctrl->redirect($this,'settings');

			return false;
		}

		$this->object->settings_obj->update();

		ilUtil::sendInfo($this->lng->txt('settings_saved'),true);
		$this->ctrl->redirect($this,'settings');

		return true;
	}
	
	function getAdminTabs(&$tabs_gui)
	{
		$this->getTabs($tabs_gui);
	}

	/**
	* get tabs
	* @access	public
	* @param	object	tabs gui object
	*/
	function getTabs(&$tabs_gui)
	{
		global $rbacsystem;

		if ($rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$tabs_gui->addTarget("settings",
				$this->ctrl->getLinkTarget($this, "settings"), array("settings","", "view"), "", "");
		}

		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), array("perm","info","owner"), 'ilpermissiongui');
		}
	}
} // END class.ilObjSearchSettingsGUI
?>
