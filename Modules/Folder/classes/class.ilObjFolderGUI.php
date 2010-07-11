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


/**
* Class ilObjFolderGUI
*
* @author Alex Killing <alex.killing@gmx.de>
* $Id$
*
* @ilCtrl_Calls ilObjFolderGUI: ilConditionHandlerInterface, ilPermissionGUI
* @ilCtrl_Calls ilObjFolderGUI: ilCourseContentGUI, ilLearningProgressGUI
* @ilCtrl_Calls ilObjFolderGUI: ilInfoScreenGUI, ilPageObjectGUI, ilColumnGUI
* @ilCtrl_Calls ilObjFolderGUI: ilCourseItemAdministrationGUI, ilObjectCopyGUI, ilObjStyleSheetGUI
* @ilCtrl_Calls ilObjFolderGUI: ilExportGUI
*
* @extends ilObjectGUI
*/

require_once "./Services/Container/classes/class.ilContainerGUI.php";

class ilObjFolderGUI extends ilContainerGUI
{
	var $folder_tree;		// folder tree

	/**
	* Constructor
	* @access	public
	*/
	function ilObjFolderGUI($a_data, $a_id = 0, $a_call_by_reference = true, $a_prepare_output = false)
	{
		$this->type = "fold";
		parent::__construct($a_data, $a_id, $a_call_by_reference, $a_prepare_output, false);
	}


	/**
	* View folder
	*/
	function viewObject()
	{
		global $tree;

		if (strtolower($_GET["baseClass"]) == "iladministrationgui")
		{
			parent::viewObject();
			return true;
		}
		$this->renderObject();
		$this->tabs_gui->setTabActive('view_content');
		return true;
	}
		
	/**
	* Render folder
	*/
	function renderObject()
	{
		global $ilTabs;
		
		$ilTabs->activateTab("view_content");
		$ret =  parent::renderObject();
		return $ret;
	}

	function &executeCommand()
	{
		global $ilUser,$ilCtrl;

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch ($next_class)
		{
			case "ilconditionhandlerinterface":
				$this->prepareOutput();
				include_once './classes/class.ilConditionHandlerInterface.php';

				if($_GET['item_id'])
				{
					$this->ctrl->saveParameter($this,'item_id',$_GET['item_id']);
					$this->__setSubTabs('activation');
					$this->tabs_gui->setTabActive('view_content');

					$new_gui =& new ilConditionHandlerInterface($this,(int) $_GET['item_id']);
					$this->ctrl->forwardCommand($new_gui);
				}
				else
				{
					$new_gui =& new ilConditionHandlerInterface($this);
					$this->ctrl->forwardCommand($new_gui);
				}
				break;
				
			case 'ilpermissiongui':
				$this->prepareOutput();
				$this->tabs_gui->setTabActive('perm_settings');
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			case 'ilcoursecontentgui':
				$this->prepareOutput();
				include_once './Modules/Course/classes/class.ilCourseContentGUI.php';
				$course_content_obj = new ilCourseContentGUI($this);
				$this->ctrl->forwardCommand($course_content_obj);
				break;
				
			case 'ilcourseitemadministrationgui':
				$this->prepareOutput();
				include_once 'Modules/Course/classes/class.ilCourseItemAdministrationGUI.php';
				$this->tabs_gui->clearSubTabs();
				$this->ctrl->setReturn($this,'view');
				$item_adm_gui = new ilCourseItemAdministrationGUI($this->object,(int) $_REQUEST['item_id']);
				$this->ctrl->forwardCommand($item_adm_gui);
				break;

			case "illearningprogressgui":
				$this->prepareOutput();
				include_once './Services/Tracking/classes/class.ilLearningProgressGUI.php';
				
				$new_gui =& new ilLearningProgressGUI(LP_MODE_REPOSITORY,
													  $this->object->getRefId(),
													  $_GET['user_id'] ? $_GET['user_id'] : $ilUser->getId());
				$this->ctrl->forwardCommand($new_gui);
				$this->tabs_gui->setTabActive('learning_progress');
				break;

			// container page editing
			case "ilpageobjectgui":
				$this->prepareOutput(false);
				$this->checkPermission("write");
				$ret = $this->forwardToPageObject();
				if ($ret != "")
				{
					$this->tpl->setContent($ret);
				}
				break;

			case 'ilinfoscreengui':
				$this->prepareOutput();
				$this->infoScreen();
				break;

			case 'ilobjectcopygui':
				$this->prepareOutput();

				include_once './Services/Object/classes/class.ilObjectCopyGUI.php';
				$cp = new ilObjectCopyGUI($this);
				$cp->setType('fold');
				$this->ctrl->forwardCommand($cp);
				break;

			case "ilobjstylesheetgui":
				$this->forwardToStyleSheet();
				break;
				
			case 'ilexportgui':
				$this->prepareOutput();
					
				$this->tabs_gui->setTabActive('export');
				include_once './Services/Export/classes/class.ilExportGUI.php';
				$exp = new ilExportGUI($this);
				$exp->addFormat('xml');
				$this->ctrl->forwardCommand($exp);
				break;

			default:

				$this->prepareOutput();
				// Dirty hack for course timings view
				if($this->forwardToTimingsView())
				{
					break;
				}

				if (empty($cmd))
				{
					$cmd = "view";
				}
				$cmd .= "Object";
				$this->$cmd();
				break;
		}
	}

	/**
	* set tree
	*/
	function setFolderTree($a_tree)
	{
		$this->folder_tree =& $a_tree;
	}
	
	/**
	 * Create object
	 * @return 
	 */
	public function createObject()
	{
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.fold_create.html','Modules/Folder');
		$this->ctrl->setParameter($this,'new_type',$this->type);
		
		$this->initFormCreate();
		
		$this->tpl->setVariable('NEW_FOLDER',$this->form->getHTML());
		$this->fillCloneTemplate('DUPLICATE', 'fold');
		
		$this->initImportForm("fold");
		$this->tpl->setVariable("IMPORT_FORM", $this->form->getHTML());
		
	}

	/**
	 * Import
	 *
	 * @access	public
	 */
	protected function importFileObject()
	{
		global $rbacsystem, $objDefinition, $tpl, $lng, $ilErr;

		$new_type = $_POST["new_type"] ? $_POST["new_type"] : $_GET["new_type"];

		// create permission is already checked in createObject. This check here is done to prevent hacking attempts
		if (!$rbacsystem->checkAccess("create", $_GET['ref_id'], $new_type))
		{
			$ilErr->raiseError($this->lng->txt('no_create_permission'),$ilErr->MESSAGE);
		}
		$this->ctrl->setParameter($this, "new_type", $new_type);
		$this->initImportForm($new_type);
		if ($this->form->checkInput())
		{
			include_once './Services/Export/classes/class.ilImport.php';
			$imp = new ilImport();
			$new_id = $imp->importObject(null, $_FILES["importfile"]["tmp_name"],$_FILES["importfile"]["name"], $new_type);

			// put new object id into tree
			if ($new_id > 0)
			{
				$newObj = ilObjectFactory::getInstanceByObjId($new_id);
				$newObj->createReference();
				$newObj->putInTree($_GET["ref_id"]);
				$newObj->setPermissions($_GET["ref_id"]);
				ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
				$this->ctrl->returnToParent($this);
			}
			return;
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}

	/**
	 * Init object import form
	 *
	 * @param        string        new type
	 */
	public function initImportForm($a_new_type = "")
	{
		global $lng, $ilCtrl;

		$lng->loadLanguageModule("fold");

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		$this->form->setTableWidth('60%');
		$this->form->setTarget("_top");

		// Import file
		include_once("./Services/Form/classes/class.ilFileInputGUI.php");
		$fi = new ilFileInputGUI($lng->txt("import_file"), "importfile");
		$fi->setSuffixes(array("zip"));
		$fi->setRequired(true);
		$this->form->addItem($fi);

		$this->form->addCommandButton("importFile", $lng->txt("import"));
		$this->form->addCommandButton("cancel", $lng->txt("cancel"));
		$this->form->setTitle($lng->txt($a_new_type."_import"));

		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}
	
	
	/**
	 * Init creation form
	 * @return 
	 */
	protected function initFormCreate()
	{
		if($this->form instanceof ilPropertyFormGUI)
		{
			return true;			
		}
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this,'save'));
		$this->form->setTableWidth('60%');
		$this->form->setTitle($this->lng->txt($this->type.'_new'));
		
		// Title
		$tit = new ilTextInputGUI($this->lng->txt('title'),'tit');
		$tit->setRequired(true);
		$tit->setMaxLength(128);
		$this->form->addItem($tit);
		
		// Description
		$des = new ilTextAreaInputGUI($this->lng->txt('description'),'des');
		$this->form->addItem($des);
		
		$this->form->addCommandButton('save', $this->lng->txt('save'));
		$this->form->addCommandButton('cancel', $this->lng->txt('cancel'));
	}
	
	/**
	 * Save object
	 * @return 
	 */
	public function saveObject()
	{
		global $ilUser;

		$this->initFormCreate();
		
		if($this->form->checkInput())
		{
			$fold = parent::saveObject();
			$fold->setTitle($this->form->getInput('tit'));
			$fold->setDescription($this->form->getInput('des'));
			
			$fold->update();
			
			include_once 'Services/Tracking/classes/class.ilChangeEvent.php';
			if (ilChangeEvent::_isActive())
			{
				ilChangeEvent::_recordWriteEvent($fold->getId(), $ilUser->getId(), 'create');
			}
			
			include_once './classes/class.ilLink.php';
			ilUtil::sendSuccess($this->lng->txt($this->type."_added"),true);
			ilUtil::redirect('repository.php?ref_id='.$fold->getRefId());
		}
		$this->form->setValuesByPost();
		$this->createObject();
	}
	
	
	/**
	 * Update object
	 * @return 
	 */
	public function editObject()
	{
		$this->tabs_gui->setTabActive('settings');
		$this->initFormEdit();
		
		include_once './Services/Container/classes/class.ilContainerSortingSettings.php';
		$this->form->getItemByPostVar('tit')->setValue($this->object->getTitle());
		$this->form->getItemByPostVar('des')->setValue($this->object->getDescription());
		$this->form->getItemByPostVar('sor')->setValue($this->object->getOrderType());
#			ilContainerSortingSettings::_readSortMode($this->object->getId())
#		);

		$this->tpl->setContent($this->form->getHTML());
		return true;
	}
	
	
	public function updateObject()
	{
		global $ilUser;

		$this->initFormEdit();
		if($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput('tit'));
			$this->object->setDescription($this->form->getInput('des'));
			$this->object->update();
			
			// Save sorting
			include_once './Services/Container/classes/class.ilContainerSortingSettings.php';
			$sort = new ilContainerSortingSettings($this->object->getId());
			$sort->setSortMode($this->form->getInput('sor'));
			$sort->update();
			
			include_once 'Services/Tracking/classes/class.ilChangeEvent.php';
			if (ilChangeEvent::_isActive())
			{
				ilChangeEvent::_recordWriteEvent($this->object->getId(), $ilUser->getId(), 'update');
				ilChangeEvent::_catchupWriteEvents($this->object->getId(), $ilUser->getId());
			}
			
			ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"),true);
			$this->ctrl->redirect($this,'edit');
		}
		$this->form->setValuesByPost();
		$this->tabs_gui->setTabActive('settings');
		$this->tpl->setContent($this->form->getHTML());
		return true;
	}
	
	/**
	 * Init edit form
	 * @return 
	 */
	protected function initFormEdit()
	{
		global $tree;
		
		if($this->form instanceof ilPropertyFormGUI)
		{
			return true;			
		}
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this,'update'));
		$this->form->setTitle($this->lng->txt($this->type.'_edit'));
		
		// Title
		$tit = new ilTextInputGUI($this->lng->txt('title'),'tit');
		$tit->setRequired(true);
		$tit->setMaxLength(128);
		$this->form->addItem($tit);
		
		// Description
		$des = new ilTextAreaInputGUI($this->lng->txt('description'),'des');
		$this->form->addItem($des);
		
		// Sorting
		$sog = new ilRadioGroupInputGUI($this->lng->txt('sorting_header'),'sor');
		$sog->setRequired(true);
		
		// implicit: there is always a group or course in the path
		$sde = new ilRadioOption();
		$sde->setValue(ilContainer::SORT_INHERIT);
		
		$title = $this->lng->txt('sort_inherit_prefix');
		$title .= ' ('.ilContainerSortingSettings::sortModeToString(ilContainerSortingSettings::lookupSortModeFromParentContainer($this->object->getId())).') ';
		$sde->setTitle($title);
		$sde->setInfo($this->lng->txt('sorting_info_inherit'));
		$sog->addOption($sde);
		
		$sma = new ilRadioOption();
		$sma->setValue(ilContainer::SORT_TITLE);
		$sma->setTitle($this->lng->txt('sorting_title_header'));
		$sma->setInfo($this->lng->txt('sorting_info_title'));
		$sog->addOption($sma);

		$sti = new ilRadioOption();
		$sti->setValue(ilContainer::SORT_MANUAL);
		$sti->setTitle($this->lng->txt('sorting_manual_header'));
		$sti->setInfo($this->lng->txt('sorting_info_manual'));
		$sog->addOption($sti);
		
		$this->form->addItem($sog);

		$this->form->addCommandButton('update', $this->lng->txt('save'));
		$this->form->addCommandButton('cancel', $this->lng->txt('cancel'));
	}
	


	
		/**
	* updates object entry in object_data
	*
	* @access	public
	*/
	function updateObject2($a_return_to_parent = false)
	{
		$this->object->setTitle(ilUtil::stripSlashes($_POST["Fobject"]["title"]));
		$this->object->setDescription(ilUtil::stripSlashes($_POST["Fobject"]["desc"]));
		$this->update = $this->object->update();

		// BEGIN ChangeEvent: Record write event.
		require_once('Services/Tracking/classes/class.ilChangeEvent.php');
		if (ilChangeEvent::_isActive())
		{
			global $ilUser;
			ilChangeEvent::_recordWriteEvent($this->object->getId(), $ilUser->getId(), 'update');
			ilChangeEvent::_catchupWriteEvents($this->object->getId(), $ilUser->getId());
		}
		// END ChangeEvent: Record write event.
		
		ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"),true);

		if ($a_return_to_parent)
		{
			$this->ctrl->returnToParent($this);
		}
		else
		{
			$this->ctrl->redirect($this);
		}
	}

	// BEGIN ChangeEvent show info screen on folder object
	/**
	* this one is called from the info button in the repository
	* not very nice to set cmdClass/Cmd manually, if everything
	* works through ilCtrl in the future this may be changed
	*/
	function showSummaryObject()
	{
		$this->ctrl->setCmd("showSummary");
		$this->ctrl->setCmdClass("ilinfoscreengui");
		$this->infoScreen();
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
	* show information screen
	*/
	function infoScreen()
	{
		global $ilAccess;

		if (!$ilAccess->checkAccess("visible", "", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}

		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);

		$info->enablePrivateNotes();
		
		if ($ilAccess->checkAccess("read", "", $_GET["ref_id"]))
		{
			$info->enableNews();
		}

		// no news editing for files, just notifications
		$info->enableNewsEditing(false);
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
			$news_set = new ilSetting("news");
			$enable_internal_rss = $news_set->get("enable_rss_for_internal");
			
			if ($enable_internal_rss)
			{
				$info->setBlockProperty("news", "settings", true);
				$info->setBlockProperty("news", "public_notifications_option", true);
			}
		}

		
		// standard meta data
		$info->addMetaDataSections($this->object->getId(),0, $this->object->getType());
		
		// forward the command
		$this->ctrl->forwardCommand($info);
	}
	// END ChangeEvent show info screen on folder object

	/**
	* Get tabs
	*/
	function getTabs(&$tabs_gui)
	{
		global $rbacsystem, $ilUser, $lng, $ilCtrl,$ilAccess;

		$this->ctrl->setParameter($this,"ref_id",$this->ref_id);

		$tabs_gui->setTabActive("");
		if ($rbacsystem->checkAccess('read',$this->ref_id))
		{
			$tabs_gui->addTab("view_content", $lng->txt("content"),
				$this->ctrl->getLinkTarget($this, ""));

			//BEGIN ChangeEvent add info tab to category object
			$force_active = ($this->ctrl->getNextClass() == "ilinfoscreengui"
				|| strtolower($_GET["cmdClass"]) == "ilnotegui")
				? true
				: false;
			$tabs_gui->addTarget("info_short",
				 $this->ctrl->getLinkTargetByClass(
				 array("ilobjfoldergui", "ilinfoscreengui"), "showSummary"),
				 array("showSummary","", "infoScreen"),
				 "", "", $force_active);
			//END ChangeEvent add info tab to category object
		}
		
		if ($rbacsystem->checkAccess('write',$this->ref_id))
		{
			$tabs_gui->addTarget("settings",
				$this->ctrl->getLinkTarget($this, "edit"), "edit", "", "", ($ilCtrl->getCmd() == "edit"));
		}

		// learning progress
		include_once './Services/Tracking/classes/class.ilLearningProgressAccess.php';
		if(ilLearningProgressAccess::checkAccess($this->object->getRefId()))
		{
			$tabs_gui->addTarget('learning_progress',
								 $this->ctrl->getLinkTargetByClass(array('ilobjfoldergui','illearningprogressgui'),''),
								 '',
								 array('illplistofobjectsgui','illplistofsettingsgui','illearningprogressgui','illplistofprogressgui'));
		}
		
		if($ilAccess->checkAccess('write','',$this->object->getRefId()))
		{
			$tabs_gui->addTarget(
				'export',
				$this->ctrl->getLinkTargetByClass('ilexportgui',''),
				'export',
				'ilexportgui'
			);
		}
		

		if ($rbacsystem->checkAccess('edit_permission',$this->ref_id))
		{
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), array("perm","info","owner"), 'ilpermissiongui');
		}

		// show clipboard in repository
		if ($this->ctrl->getTargetScript() == "repository.php" and !empty($_SESSION['il_rep_clipboard']))
		{
			$tabs_gui->addTarget("clipboard",
				 $this->ctrl->getLinkTarget($this, "clipboard"), "clipboard", get_class($this));
		}

	}

	// Methods for ConditionHandlerInterface
	function initConditionHandlerGUI($item_id)
	{
		include_once './classes/class.ilConditionHandlerInterface.php';

		if(!is_object($this->chi_obj))
		{
			if($_GET['item_id'])
			{
				$this->chi_obj =& new ilConditionHandlerInterface($this,$item_id);
				$this->ctrl->saveParameter($this,'item_id',$_GET['item_id']);
			}
			else
			{
				$this->chi_obj =& new ilConditionHandlerInterface($this);
			}
		}
		return true;
	}

	/**
	* set sub tabs
	*/
	function __setSubTabs($a_tab)
	{
		global $rbacsystem,$ilUser;
	
		switch ($a_tab)
		{
				
			case "activation":
				
				$this->tabs_gui->addSubTabTarget("activation",
												 $this->ctrl->getLinkTargetByClass('ilCourseItemAdministrationGUI','edit'),
												 "edit", get_class($this));
				$this->ctrl->setParameterByClass('ilconditionhandlerinterface','item_id',(int) $_GET['item_id']);
				$this->tabs_gui->addSubTabTarget("preconditions",
												 $this->ctrl->getLinkTargetByClass('ilConditionHandlerInterface','listConditions'),
												 "", "ilConditionHandlerInterface");
				break;
		}
	}

	/**
	* goto target group
	*/
	function _goto($a_target)
	{
		global $ilAccess, $ilErr, $lng;

		if ($ilAccess->checkAccess("read", "", $a_target))
		{
			$_GET["cmd"] = "frameset";
			$_GET["ref_id"] = $a_target;
			include("repository.php");
			exit;
		}
		/*
		else
		{
			// to do: force flat view
			
			// no info screen for folders
			if ($ilAccess->checkAccess("visible", "", $a_target))
			{
				$_GET["cmd"] = "infoScreen";
				$_GET["ref_id"] = $a_target;
				include("repository.php");
				exit;
			}
			else
			{
				// This part will never be reached
				if ($ilAccess->checkAccess("read", "", ROOT_FOLDER_ID))
				{
					$_GET["cmd"] = "frameset";
					$_GET["target"] = "";
					$_GET["ref_id"] = ROOT_FOLDER_ID;
					ilUtil::sendInfo(sprintf($lng->txt("msg_no_perm_read_item"),
						ilObject::_lookupTitle(ilObject::_lookupObjId($a_target))), true);
					include("repository.php");
					exit;
				}
			}
		}
		*/
		$ilErr->raiseError($lng->txt("msg_no_perm_read"), $ilErr->FATAL);
	}


	public function downloadFolderObject () {
		global $ilAccess, $ilErr, $lng;
			
		if (!$ilAccess->checkAccess("read", "", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		$filename = $this->object->downloadFolder();
		ilUtil::deliverFile($filename, ilUtil::getASCIIFilename($this->object->getTitle().".zip"));				
	}
	
	/**
	* Modify Item ListGUI for presentation in container
	*/
	function modifyItemGUI($a_item_list_gui, $a_item_data, $a_show_path)
	{
		global $tree;

		// if folder is in a course, modify item list gui according to course requirements
		if ($course_ref_id = $tree->checkForParentType($this->object->getRefId(),'crs'))
		{
			include_once("./Modules/Course/classes/class.ilObjCourse.php");
			include_once("./Modules/Course/classes/class.ilObjCourseGUI.php");
			$course_obj_id = ilObject::_lookupObjId($course_ref_id);
			ilObjCourseGUI::_modifyItemGUI($a_item_list_gui, 'ilcoursecontentgui', $a_item_data, $a_show_path,
				ilObjCourse::_lookupAboStatus($course_obj_id), $course_ref_id, $course_obj_id,
				$this->object->getRefId());
		}
	}
	
	protected function forwardToTimingsView()
	{
		global $tree;
		
		if(!$crs_ref = $tree->checkForParentType($this->ref_id, 'crs'))
		{
			return false;
		}
		include_once './Modules/Course/classes/class.ilObjCourse.php';
		if(!$this->ctrl->getCmd() and ilObjCourse::_lookupViewMode(ilObject::_lookupObjId($crs_ref)) == ilContainer::VIEW_TIMING)
		{
			if(!isset($_SESSION['crs_timings'])) {
				$_SESSION['crs_timings'] = true;
			}
			
			if($_SESSION['crs_timings'] == true) {
				include_once './Modules/Course/classes/class.ilCourseContentGUI.php';
				$course_content_obj = new ilCourseContentGUI($this);
				$this->ctrl->setCmdClass(get_class($course_content_obj));
				$this->ctrl->setCmd('editUserTimings');
				$this->ctrl->forwardCommand($course_content_obj);
				return true;
			}
		}
		$_SESSION['crs_timings'] = false;
		return false;
	}
	

} // END class.ilObjFolderGUI
?>
