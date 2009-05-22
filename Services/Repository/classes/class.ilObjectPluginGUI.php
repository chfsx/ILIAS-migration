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

include_once("./Services/Object/classes/class.ilObject2GUI.php");
include_once("./Services/Component/classes/class.ilPlugin.php");

/*
* Object GUI class for plugins
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
* @ingroup ServicesRepository
*/
abstract class ilObjectPluginGUI extends ilObject2GUI
{
	/**
	* Constructor.
	*/
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id, true);
		$this->plugin =
			ilPlugin::getPluginObject(IL_COMP_SERVICE, "Repository", "robj",
				ilPlugin::lookupNameForId(IL_COMP_SERVICE, "Repository", "robj", $this->getType()));
		if (!is_object($this->plugin))
		{
			die("ilObjectPluginGUI: Could not instantiate plugin object for type ".$this->getType().".");
		}
	}
	
	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilCtrl, $tpl, $ilAccess, $lng, $ilNavigationHistory, $ilTabs;

		// get standard template (includes main menu and general layout)
		$tpl->getStandardTemplate();

		// set title
		if (!$this->getCreationMode())
		{
			$tpl->setTitle($this->object->getTitle());
			$tpl->setTitleIcon($this->plugin->getImagePath("icon_".$this->object->getType()."_b.gif"),
				$lng->txt("icon")." ".$this->txt("obj_".$this->object->getType()));
				
			// set tabs
			$this->setTabs();
			$this->setLocator();
			
			// add entry to navigation history
			if ($ilAccess->checkAccess("read", "", $_GET["ref_id"]))
			{
				$ilNavigationHistory->addItem($_GET["ref_id"],
					$ilCtrl->getLinkTarget($this, $this->getStandardCmd()), $this->getType());
			}

		}

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			case "ilinfoscreengui":
				$this->checkPermission("visible");
				$this->infoScreen();	// forwards command
				break;

			case 'ilpermissiongui':
				include_once("./classes/class.ilPermissionGUI.php");
				$perm_gui = new ilPermissionGUI($this);
				$ilTabs->setTabActive("perm_settings");
				$ret = $ilCtrl->forwardCommand($perm_gui);
			break;

			default:
				if(!$cmd)
				{
					$cmd = $this->getStandardCmd();
				}
				if ($cmd == "infoScreen")
				{
					$ilCtrl->setCmd("showSummary");
					$ilCtrl->setCmdClass("ilinfoscreengui");
					$this->infoScreen();
				}
				else
				{
					$this->performCommand($cmd);
				}
				break;
		}

		if (!$this->getCreationMode())
		{
			$tpl->show();
		}
	}

	/**
	* Add object to locator
	*/
	function addLocatorItems()
	{
		global $ilLocator;

		if (!$this->getCreationMode())
		{
			$ilLocator->addItem($this->object->getTitle(),
				$this->ctrl->getLinkTarget($this, $this->getStandardCmd()), "", $_GET["ref_id"]);
		}
	}

	/**
	* Get standard command
	*/
	function getStandardCmd()
	{
		return "";
	}

	/**
	* Get plugin object
	*
	* @return	object	plugin object
	*/
	final private function getPlugin()
	{
		return $this->plugin;
	}
	
	/**
	* Wrapper for txt function
	*/
	final protected function txt($a_var)
	{
		return $this->getPlugin()->txt($a_var);
	}
	
	/**
	* Init object creation form
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initEditForm($a_mode = "edit", $a_new_type = "")
	{
		global $lng, $ilCtrl;
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		$this->form->setTarget("_top");
	
		// title
		$ti = new ilTextInputGUI($lng->txt("title"), "title");
		$ti->setMaxLength(128);
		$ti->setSize(40);
		$ti->setRequired(true);
		$this->form->addItem($ti);
		
		// description
		$ta = new ilTextAreaInputGUI($lng->txt("description"), "desc");
		$ta->setCols(40);
		$ta->setRows(2);
		$this->form->addItem($ta);
	
		// save and cancel commands
		if ($a_mode == "create")
		{
			$this->form->addCommandButton("save", $this->txt($a_new_type."_add"));
			$this->form->addCommandButton("cancelCreation", $lng->txt("cancel"));
			$this->form->setTitle($this->txt($a_new_type."_new"));
		}
		else
		{
			$this->form->addCommandButton("update", $lng->txt("save"));
			$this->form->addCommandButton("cancelUpdate", $lng->txt("cancel"));
			$this->form->setTitle($lng->txt("edit"));
		}
	                
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	 
	}

	/**
	* After saving
	* @access	public
	*/
	function afterSave($newObj)
	{
		global $ilCtrl;
		// always send a message
		ilUtil::sendSuccess($this->lng->txt("object_added"),true);

		$ilCtrl->initBaseClass("ilObjPluginDispatchGUI");
		$ilCtrl->setTargetScript("ilias.php");
		$ilCtrl->getCallStructure(strtolower("ilObjPluginDispatchGUI"));
		
//var_dump($ilCtrl->call_node);
//var_dump($ilCtrl->forward);
//var_dump($ilCtrl->parent);
//var_dump($ilCtrl->root_class);

		$ilCtrl->setParameterByClass(get_class($this), "ref_id", $newObj->getRefId());
		$ilCtrl->redirectByClass(array("ilobjplugindispatchgui", get_class($this)), $this->getAfterCreationCmd());
	}
	
	/**
	* Cmd that will be redirected to after creation of a new object.
	*/
	abstract function getAfterCreationCmd();
	
	/**
	* Add info screen tab
	*/
	function addInfoTab()
	{
		global $ilAccess, $ilTabs;
		
		// info screen
		if ($ilAccess->checkAccess('visible', "", $this->object->getRefId()))
		{
			$ilTabs->addTarget("info_short",
				$this->ctrl->getLinkTargetByClass(
				"ilinfoscreengui", "showSummary"),
				"showSummary");
		}
	}

	/**
	* Add permission tab
	*/
	function addPermissionTab()
	{
		global $ilAccess, $ilTabs, $ilCtrl;
		
		// edit permissions
		if($ilAccess->checkAccess('edit_permission', "", $this->object->getRefId()))
		{
			$ilTabs->addTarget("perm_settings",
				$ilCtrl->getLinkTargetByClass("ilpermissiongui", "perm"),
				array("perm","info","owner"), 'ilpermissiongui');
		}
	}
	
	
	/**
	* show information screen
	*/
	function infoScreen()
	{
		global $ilAccess, $ilUser, $lng, $ilCtrl, $tpl, $ilTabs;
		
		$ilTabs->setTabActive("info_short");
		
		$this->checkPermission("visible");

		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);
		$info->enablePrivateNotes();

		// general information
		$lng->loadLanguageModule("meta");

		$this->addInfoItems($info);

		// forward the command
		$ret = $ilCtrl->forwardCommand($info);
		//$tpl->setContent($ret);
	}

	/**
	* Add items to info screen
	*/
	function addInfoItems($info)
	{
	}

}
