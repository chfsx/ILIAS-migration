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
* Class ilCourseContentGUI
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
*
* @extends ilObjectGUI
*
* @ilCtrl_Calls ilCourseContentGUI: ilCourseArchivesGUI, ilCourseObjectivePresentationGUI, ilCourseItemAdministrationGUI
* @ilCtrl_Calls ilCourseContentGUI: ilEventAdministrationGUI, ilColumnGUI
*
*/

include_once './Modules/Course/classes/Event/class.ilEvent.php';

class ilCourseContentGUI
{
	var $container_gui;
	var $container_obj;
	var $course_obj;

	var $tpl;
	var $ctrl;
	var $lng;
	var $tabs_gui;

	/**
	* Constructor
	* @access public
	*/
	function ilCourseContentGUI(&$container_gui_obj)
	{
		global $tpl,$ilCtrl,$lng,$ilObjDataCache,$ilTabs;

		$this->tpl =& $tpl;
		$this->ctrl =& $ilCtrl;
		$this->lng =& $lng;
		$this->lng->loadLanguageModule('crs');
		$this->tabs_gui =& $ilTabs;

		$this->container_gui =& $container_gui_obj;
		$this->container_obj =& $this->container_gui->object;

		//
		$this->__initCourseObject();
	}

	function &executeCommand()
	{
		global $ilAccess, $ilErr, $ilTabs, $ilCtrl;

		if(!$ilAccess->checkAccess('read','',$this->container_obj->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('msg_no_perm_read'),$ilErr->WARNING);
		}

		$this->__setSubTabs();
		$this->tabs_gui->setTabActive('view_content');
		$cmd = $this->ctrl->getCmd();

		switch($this->ctrl->getNextClass($this))
		{
			case 'ilcourseitemadministrationgui':
				include_once 'Modules/Course/classes/class.ilCourseItemAdministrationGUI.php';
				$this->tabs_gui->clearSubTabs();
				$this->ctrl->setReturn($this,'');
				$item_adm_gui = new ilCourseItemAdministrationGUI($this->container_obj,(int) $_GET['item_id']);
				$this->ctrl->forwardCommand($item_adm_gui);
				break;

			case 'ilcoursearchivesgui':
				$this->__forwardToArchivesGUI();
				break;

			case 'ilcourseobjectivepresentationgui':
				$this->view();					// forwarding moved to getCenterColumnHTML()
				break;

			case 'ileventadministrationgui':
				include_once 'Modules/Course/classes/Event/class.ilEventAdministrationGUI.php';

				$this->ctrl->setReturn($this,'');
				$event_gui = new ilEventAdministrationGUI($this->container_gui,(int) $_GET['event_id']);
				$this->ctrl->forwardCommand($event_gui);
				$this->tabs_gui->clearSubTabs();
				$this->tabs_gui->clearTargets();
				break;

			case "ilcolumngui":
				$ilCtrl->saveParameterByClass("ilcolumngui", "col_return");
				$ilTabs->setSubTabActive("crs_content");
				$this->view();
				break;

			default:
				if(!$this->__checkStartObjects())
				{
					$this->showStartObjects();
					break;
				}

				// forward if archives enabled and not tutor
				if(!$this->is_tutor = $ilAccess->checkAccess('write','',$this->course_obj->getRefId()) and
				   $this->course_obj->isArchived())
				{
					$this->__forwardToArchivesGUI();
					break;
				}

				// forward to objective presentation
				if((!$this->is_tutor and
				   $this->container_obj->getType() == 'crs' and
				   $this->container_obj->enabledObjectiveView()) ||
				   $_GET["col_return"] == "objectives")
				{
					$this->use_objective_presentation = true;
					$this->view();
					//$this->__forwardToObjectivePresentation();
					break;
				}


				if(!$cmd)
				{
					$cmd = $this->__getDefaultCommand();
				}
				$this->$cmd();
				break;
		}
	}

	function __getDefaultCommand()
	{
		global $ilAccess;

		// edit timings if panel is on
		if($_SESSION['crs_timings_panel'][$this->course_obj->getId()])
		{
			return 'editTimings';
		}
		if($ilAccess->checkAccess('write','',$this->container_obj->getRefId()))
		{
			return 'view';
		}
		if($this->container_obj->getType() == 'crs' and
		   $this->course_obj->getViewMode() == IL_CRS_VIEW_TIMING)
		{
			return 'editUserTimings';
		}
		return 'view';
	}

	function __forwardToObjectivePresentation()
	{
		include_once 'Modules/Course/classes/class.ilCourseObjectivePresentationGUI.php';

		$this->ctrl->setReturn($this,'');
		$objectives_gui = new ilCourseObjectivePresentationGUI($this->container_gui);
		$this->ctrl->forwardCommand($objectives_gui);

		$this->tabs_gui->setTabActive('view_content');
		$this->tabs_gui->setSubTabActive('learners_view');

		return true;
	}

	function __forwardToArchivesGUI()
	{
		include_once 'Modules/Course/classes/class.ilCourseArchivesGUI.php';

		$this->ctrl->setReturn($this,'');
		$archives_gui = new ilCourseArchivesGUI($this->container_gui);
		$this->ctrl->forwardCommand($archives_gui);

		$this->tabs_gui->setTabActive('view_content');
		$this->tabs_gui->setSubTabActive('crs_archives');

		return true;
	}

	function __checkStartObjects()
	{
		include_once './Modules/Course/classes/class.ilCourseStart.php';

		global $ilAccess,$ilUser;

		if($ilAccess->checkAccess('write','',$this->course_obj->getRefId()))
		{
			return true;
		}
		$this->start_obj = new ilCourseStart($this->course_obj->getRefId(),$this->course_obj->getId());
		if(count($this->start_obj->getStartObjects()) and !$this->start_obj->allFullfilled($ilUser->getId()))
		{
			return false;
		}
		return true;
	}

	function showStartObjects()
	{
		include_once './Modules/Course/classes/class.ilCourseLMHistory.php';
		include_once './classes/class.ilRepositoryExplorer.php';
		include_once './classes/class.ilLink.php';

		global $rbacsystem,$ilias,$ilUser,$ilAccess,$ilObjDataCache;

		$this->tabs_gui->setSubTabActive('crs_content');

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_start_view.html",'Modules/Course');
		$this->tpl->setVariable("INFO_STRING",$this->lng->txt('crs_info_start'));
		$this->tpl->setVariable("TBL_TITLE_START",$this->lng->txt('crs_table_start_objects'));
		$this->tpl->setVariable("HEADER_NR",$this->lng->txt('crs_nr'));
		$this->tpl->setVariable("HEADER_DESC",$this->lng->txt('description'));
		$this->tpl->setVariable("HEADER_EDITED",$this->lng->txt('crs_objective_accomplished'));


		$lm_continue =& new ilCourseLMHistory($this->course_obj->getRefId(),$ilUser->getId());
		$continue_data = $lm_continue->getLMHistory();

		$counter = 0;
		foreach($this->start_obj->getStartObjects() as $start)
		{
			$obj_id = $ilObjDataCache->lookupObjId($start['item_ref_id']);
			$ref_id = $start['item_ref_id'];
			$type = $ilObjDataCache->lookupType($obj_id);

			$conditions_ok = ilConditionHandler::_checkAllConditionsOfTarget($obj_id);

			$obj_link = ilLink::_getLink($ref_id,$type);
			$obj_frame = ilRepositoryExplorer::buildFrameTarget($type,$ref_id,$obj_id);
			$obj_frame = $obj_frame ? $obj_frame : '';

			// Tmp fix for tests
			$obj_frame = $type == 'tst' ? '' : $obj_frame;

			$contentObj = false;

			if($ilAccess->checkAccess('read','',$ref_id))
			{
				$this->tpl->setCurrentBlock("start_read");
				$this->tpl->setVariable("READ_TITLE_START",$ilObjDataCache->lookupTitle($obj_id));
				$this->tpl->setVariable("READ_TARGET_START",$obj_frame);
				$this->tpl->setVariable("READ_LINK_START", $obj_link.'&crs_show_result='.$this->course_obj->getRefId());
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->setCurrentBlock("start_visible");
				$this->tpl->setVariable("VISIBLE_LINK_START",$ilObjDataCache->lookupTitle($obj_id));
				$this->tpl->parseCurrentBlock();
			}

			// CONTINUE LINK
			if(isset($continue_data[$ref_id]))
			{
				$this->tpl->setCurrentBlock("link");
				$this->tpl->setVariable("LINK_HREF",ilLink::_getLink($ref_id,'',array('obj_id',
																					  $continue_data[$ref_id]['lm_page_id'])));
				#$this->tpl->setVariable("CONTINUE_LINK_TARGET",$target);
				$this->tpl->setVariable("LINK_NAME",$this->lng->txt('continue_work'));
				$this->tpl->parseCurrentBlock();
			}

			// add to desktop link
			if(!$ilUser->isDesktopItem($ref_id,$type) and
			   $this->course_obj->getAboStatus())
			{
				if ($ilAccess->checkAccess('read','',$ref_id))
				{
					$this->tpl->setCurrentBlock("link");
					$this->ctrl->setParameterByClass(get_class($this->container_gui),'item_ref_id',$ref_id);
					$this->ctrl->setParameterByClass(get_class($this->container_gui),'item_id',$ref_id);
					$this->ctrl->setParameterByClass(get_class($this->container_gui),'type',$type);

					$this->tpl->setVariable("LINK_HREF",$this->ctrl->getLinkTarget($this->container_gui,'addToDesk'));
					$this->tpl->setVariable("LINK_NAME", $this->lng->txt("to_desktop"));
					$this->tpl->parseCurrentBlock();
				}
			}
			elseif($this->course_obj->getAboStatus())
			{
					$this->tpl->setCurrentBlock("link");
					$this->ctrl->setParameterByClass(get_class($this->container_gui),'item_ref_id',$ref_id);
					$this->ctrl->setParameterByClass(get_class($this->container_gui),'item_id',$ref_id);
					$this->ctrl->setParameterByClass(get_class($this->container_gui),'type',$type);

					$this->tpl->setVariable("LINK_HREF",$this->ctrl->getLinkTarget($this->container_gui,'removeFromDesk'));
					$this->tpl->setVariable("LINK_NAME", $this->lng->txt("unsubscribe"));
					$this->tpl->parseCurrentBlock();
			}


			// Description
			if(strlen($ilObjDataCache->lookupDescription($obj_id)))
			{
				$this->tpl->setCurrentBlock("start_description");
				$this->tpl->setVariable("DESCRIPTION_START",$ilObjDataCache->lookupDescription($obj_id));
				$this->tpl->parseCurrentBlock();
			}


			if($this->start_obj->isFullfilled($ilUser->getId(),$ref_id))
			{
				$accomplished = 'accomplished';
			}
			else
			{
				$accomplished = 'not_accomplished';
			}
			$this->tpl->setCurrentBlock("start_row");
			$this->tpl->setVariable("EDITED_IMG",ilUtil::getImagePath('crs_'.$accomplished.'.gif'));
			$this->tpl->setVariable("EDITED_ALT",$this->lng->txt('crs_objective_'.$accomplished));
			$this->tpl->setVariable("ROW_CLASS",'option_value');
			$this->tpl->setVariable("ROW_CLASS_CENTER",'option_value_center');
			$this->tpl->setVariable("OBJ_NR_START",++$counter.'.');
			$this->tpl->parseCurrentBlock();
		}
		return true;
	}

	/**
	* Output course content
	*/
	function view()
	{
		$this->getCenterColumnHTML();
		$this->tpl->setRightContent($this->getRightColumnHTML());
	}

	/**
	* Display right column
	*/
	function getRightColumnHTML()
	{
		global $ilUser, $lng, $ilCtrl, $ilAccess;
		
		if ($ilCtrl->getNextClass() == "ilcourseobjectivepresentationgui")
		{
			$ilCtrl->setParameterByClass("ilcolumngui", "col_return", "objectives");
		}
		$ilCtrl->saveParameterByClass("ilcolumngui", "col_return");
			
		$obj_id = ilObject::_lookupObjId($this->container_obj->getRefId());
		$obj_type = ilObject::_lookupType($obj_id);

		include_once("Services/Block/classes/class.ilColumnGUI.php");
		$column_gui = new ilColumnGUI($obj_type, IL_COL_RIGHT);
		$this->setColumnSettings($column_gui);
		
		if ($ilCtrl->getNextClass() == "ilcolumngui" &&
			$column_gui->getCmdSide() == IL_COL_RIGHT &&
			$column_gui->getScreenMode() == IL_SCREEN_SIDE)
		{
			$html = $ilCtrl->forwardCommand($column_gui);
		}
		else
		{
			if (!$ilCtrl->isAsynch())
			{
				$html = $ilCtrl->getHTML($column_gui);
			}
		}

		return $html;
	}

	/**
	* Output center column
	*/
	function getCenterColumnHTML()
	{
		global $ilCtrl, $tpl;
		
		$this->tabs_gui->setSubTabActive('crs_content');
		
		$ilCtrl->saveParameterByClass("ilcolumngui", "col_return");

		if ($this->use_objective_presentation)
		{
			$this->tabs_gui->setSubTabActive('learners_view');
			return $this->__forwardToObjectivePresentation();
		}
		
		switch ($ilCtrl->getNextClass())
		{	
			case "ilcolumngui":
				if ($_GET["col_return"] == "objectives")
				{
					$this->tabs_gui->setSubTabActive('learners_view');
					$ilCtrl->setParameter($this, "col_return", "objectives");
					$ilCtrl->setReturn($this, "view");
				}
				else
				{
					$this->tabs_gui->setSubTabActive('crs_content');
					$ilCtrl->setReturn($this, "view");
				}
				$tpl->setContent($this->__forwardToColumnGUI());
				return;
				
			case "ilcourseobjectivepresentationgui":
				$this->tabs_gui->setSubTabActive('learners_view');
				return $this->__forwardToObjectivePresentation();
		}
		
		if($_SESSION['crs_timings_panel'][$this->course_obj->getId()])
		{
			return $this->editTimings();
		}

		global $rbacsystem;

		include_once './classes/class.ilObjectListGUIFactory.php';
		include_once './Modules/Course/classes/Event/class.ilEvent.php';

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.container_page.html");

		$this->container_gui->showPossibleSubObjects();

		// Feedback
		$this->__showFeedBack();

		// Event
		$this->__showEvents();

		// course materials
		$this->__showMaterials();
	}
	
	function setColumnSettings($column_gui)
	{
		global $ilAccess;
		
		$column_gui->setRepositoryMode(true);
		$column_gui->setEnableEdit(false);
		
		$grouped_items = array();
		foreach($this->course_obj->items_obj->items as $item)
		{
			$grouped_items[$item["type"]][] = $item;
		}
		
		$column_gui->setRepositoryItems($grouped_items);
		if ($ilAccess->checkAccess("write", "", $this->container_obj->getRefId()))
		{
			$column_gui->setEnableEdit(true);
		}
		if ($this->container_gui->isActiveAdministrationPanel())
		{
			$column_gui->setBlockProperty("news", "settings", true);
			$column_gui->setBlockProperty("news", "public_notifications_option", true);
		}
	}

	
	/**
	* Get columngui output
	*/
	function __forwardToColumnGUI()
	{
		global $ilCtrl, $ilAccess;
		
		include_once("Services/Block/classes/class.ilColumnGUI.php");

		// this gets us the subitems we need in setColumnSettings()
		$this->course_obj->initCourseItemObject($this->container_obj->getRefId());
		
		$obj_id = ilObject::_lookupObjId($this->container_obj->getRefId());
		$obj_type = ilObject::_lookupType($obj_id);

		if (!$ilCtrl->isAsynch())
		{
			//if ($column_gui->getScreenMode() != IL_SCREEN_SIDE)
			if (ilColumnGUI::getScreenMode() != IL_SCREEN_SIDE)
			{
				// right column wants center
				if (ilColumnGUI::getCmdSide() == IL_COL_RIGHT)
				{
					$column_gui = new ilColumnGUI($obj_type, IL_COL_RIGHT);
					$this->setColumnSettings($column_gui);
					$html = $ilCtrl->forwardCommand($column_gui);
				}
				// left column wants center
				if (ilColumnGUI::getCmdSide() == IL_COL_LEFT)
				{
					$column_gui = new ilColumnGUI($obj_type, IL_COL_LEFT);
					$this->setColumnSettings($column_gui);
					$html = $ilCtrl->forwardCommand($column_gui);
				}
			}
		}
		
		return $html;
	}


	// PRIVATE
	function __showEvents()
	{
		global $ilUser;

		include_once 'Modules/Course/classes/Event/class.ilEventItems.php';
		include_once 'Modules/Course/classes/Event/class.ilEventParticipants.php';

		$this->course_obj->initCourseItemObject($this->container_obj->getRefId());

		if(!count($event_objs = ilEvent::_getEvents($this->container_obj->getId())))
		{
			return true;
		}

		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.event_row.html",'Modules/Course');

		$counter = 0;
		foreach($event_objs as $event_obj)
		{
			$appointment_obj =& $event_obj->getFirstAppointment();

			// Links
			if($event_obj->enabledRegistration() and ilEventParticipants::_isRegistered($ilUser->getId(),$event_obj->getEventId()))
			{
				$tpl->setCurrentBlock("event_commands");
				$this->ctrl->setParameterByClass('ileventadministrationgui','event_id',$event_obj->getEventId());
				$tpl->setVariable("EVENT_LINK",$this->ctrl->getLinkTargetByClass('ileventadministrationgui','unregister'));
				$tpl->setVariable("EVENT_LINK_TXT",$this->lng->txt('event_unregister'));
				$tpl->parseCurrentBlock();
			}
			elseif($event_obj->enabledRegistration())
			{
				$tpl->setCurrentBlock("event_commands");
				$this->ctrl->setParameterByClass('ileventadministrationgui','event_id',$event_obj->getEventId());
				$tpl->setVariable("EVENT_LINK",$this->ctrl->getLinkTargetByClass('ileventadministrationgui','register'));
				$tpl->setVariable("EVENT_LINK_TXT",$this->lng->txt('event_register'));
				$tpl->parseCurrentBlock();
			}
			if($this->is_tutor)
			{
				// Edit
				$tpl->setCurrentBlock("event_commands");
				$this->ctrl->setParameterByClass('ileventadministrationgui','event_id',$event_obj->getEventId());
				$tpl->setVariable("EVENT_LINK",$this->ctrl->getLinkTargetByClass('ileventadministrationgui','edit'));
				$tpl->setVariable("EVENT_LINK_TXT",$this->lng->txt('edit'));
				$tpl->parseCurrentBlock();

				// Edit Members
				$tpl->setCurrentBlock("event_commands");
				$tpl->setVariable("EVENT_LINK",$this->ctrl->getLinkTargetByClass('ileventadministrationgui','editMembers'));
				$tpl->setVariable("EVENT_LINK_TXT",$this->lng->txt('event_edit_members'));
				$tpl->parseCurrentBlock();

				// Edit assignments
				$tpl->setCurrentBlock("event_commands");
				$tpl->setVariable("EVENT_LINK",$this->ctrl->getLinkTargetByClass('ileventadministrationgui','materials'));
				$tpl->setVariable("EVENT_LINK_TXT",$this->lng->txt('event_assign_materials'));
				$tpl->parseCurrentBlock();

				// Delete
				$tpl->setCurrentBlock("event_commands");
				$tpl->setVariable("EVENT_LINK",$this->ctrl->getLinkTargetByClass('ileventadministrationgui','confirmDelete'));
				$tpl->setVariable("EVENT_LINK_TXT",$this->lng->txt('delete'));
				$tpl->parseCurrentBlock();
			}



			$event_items = $this->course_obj->items_obj->getItemsByEvent($event_obj->getEventId());
			foreach ($event_items as $cont_data)
			{
				if(strlen($html = $this->__getItemHTML($cont_data,true)))
				{
					 /* Disabled: no manual sort
					 foreach($this->__getOptions($cont_data,$num) as $key => $image)
					 {
						 $tpl->setCurrentBlock("img");
						 $tpl->setVariable("IMG_TYPE",$image["gif"]);
						 $tpl->setVariable("IMG_ALT",$image["lng"]);
						 $tpl->setVariable("IMG_LINK",$image["lnk"]);
						 $tpl->setVariable("IMG_TARGET",$image["tar"]);
						 $tpl->parseCurrentBlock();
					 }

					 $tpl->setCurrentBlock("options");
					 $tpl->setVariable("OPT_ROWCOL", ilUtil::switchColor($num,"tblrow1","tblrow2"));
					 $tpl->parseCurrentBlock();
					 */

					 if ($this->container_gui->isActiveAdministrationPanel())
					 {
						 $tpl->setCurrentBlock("block_row_check");
						 $tpl->setVariable("ITEM_ID", $cont_data['ref_id']);
						 $tpl->parseCurrentBlock();
					 }

					 // change row color
					 $tpl->setVariable("ITEM_HTML",$html);
					 $tpl->setVariable("MATERIAL_ROWCOL", ilUtil::switchColor($counter,"tblrow1","tblrow2"));
					 $tpl->setVariable("TYPE_IMG", ilUtil::getImagePath("icon_".$cont_data["type"].".gif"));
					 $tpl->setVariable("ALT_IMG", $this->lng->txt("obj_".$cont_data["type"]));
				 }

				 $tpl->setCurrentBlock("materials");
				 $tpl->setVariable("ITEM_HTML",$html);
				 $tpl->parseCurrentBlock();
			}

			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("EVENT_ROWCOL",ilUtil::switchColor($counter,'tblrow1','tblrow2'));
			$tpl->setVariable("EVENT_IMG",ilUtil::getImagePath('icon_event.gif'));
			$tpl->setVariable("EVENT_ALT",$this->lng->txt('events'));
			$tpl->setVariable("EVENT_TITLE",$event_obj->getTitle());

			$this->ctrl->setParameterByClass('ileventadministrationgui','event_id',$event_obj->getEventId());
			$tpl->setVariable("HREF_EVENT_TITLE",$this->ctrl->getLinkTargetByClass('ileventadministrationgui',
																				   'info'));
			if(strlen($desc = $event_obj->getDescription()))
			{
				$tpl->setVariable("EVENT_DESCRIPTION",$desc);
			}
			$tpl->setVariable("EVENT_TXT_DATE",$this->lng->txt('event_date'));
			$tpl->setVariable("EVENT_DATE",$appointment_obj->appointmentToString());
			$tpl->parseCurrentBlock();

			$counter++;
		}

		// create table
		include_once "./Services/Table/classes/class.ilTableGUI.php";
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','il_ContainerBlock');

		// title & header columns
		$tbl->setTitle($this->lng->txt("events"),"icon_crs.gif",$this->lng->txt("events"));

		if($this->is_tutor)
		{
			$tbl->setHeaderNames(array($this->lng->txt("type"),$this->lng->txt("title"),
									   ""));
			$tbl->setHeaderVars(array("type","title","options"),
								array("ref_id" => $this->course_obj->getRefId(),
									  "cmdClass" => "ilobjcoursecontentgui",
									  "cmdNode" => $_GET["cmdNode"]));
			$tbl->setColumnWidth(array("1px","100%","24px"));
			$tbl->disable("header");
		}
		else
		{
			$tbl->setHeaderNames(array($this->lng->txt("type"),$this->lng->txt("title")));
			$tbl->setHeaderVars(array("type","title"),
								array("ref_id" => $this->course_obj->getRefId(),
									  "cmdClass" => "ilobjcoursecontentgui",
									  "cmdNode" => $_GET["cmdNode"]));
			$tbl->setColumnWidth(array("1px",""));
			$tbl->disable("header");
		}

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');
		$tbl->disable("form");

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setCurrentBlock("cont_page_content");
		$this->tpl->setVariable("CONTAINER_PAGE_CONTENT",$tpl->get());
		$this->tpl->parseCurrentBlock();
	}

	function __showMaterials()
	{
		global $ilAccess;

		include_once 'Modules/Course/classes/Event/class.ilEventItems.php';

		$this->course_obj->initCourseItemObject($this->container_obj->getRefId());
		$this->cont_arr = $this->course_obj->items_obj->getFilteredItems($this->container_obj->getId());

		// NO ITEMS FOUND
		if(!count($this->cont_arr))
		{
			#ilUtil::sendInfo($this->lng->txt("crs_no_items_found"));
			$this->tpl->addBlockFile("CONTENT_TABLE", "content_tab", "tpl.container_page.html");
			$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
			$this->tpl->setVariable("CONTAINER_PAGE_CONTENT", "");
			$this->container_gui->showAdministrationPanel($this->tpl);
			return true;
		}

		// show course materials
		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_content_row.html",'Modules/Course');
		$cont_num = count($this->cont_arr);
		
		$this->container_gui->clearAdminCommandsDetermination();

		// render table content data
		// counter for rowcolor change

		$num = 0;
		foreach ($this->cont_arr as $cont_data)
		{
			// do not show blocks in material list (not nice, to do: clean up)
			if ($cont_data['type'] == "feed")
			{
				continue;
			}
			
			if(!$ilAccess->checkAccess('visible','',$cont_data['ref_id']))
			{
				continue;
			}
			
			if($html = $this->__getItemHTML($cont_data))
			{
				foreach($this->__getOptions($cont_data,$num) as $key => $image)
				{
					$tpl->setCurrentBlock("img");
					$tpl->setVariable("IMG_TYPE",$image["gif"]);
					$tpl->setVariable("IMG_ALT",$image["lng"]);
					$tpl->setVariable("IMG_LINK",$image["lnk"]);
					$tpl->setVariable("IMG_TARGET",$image["tar"]);
					$tpl->parseCurrentBlock();
				}

				$tpl->setCurrentBlock("options");
				$tpl->setVariable("OPT_ROWCOL", ilUtil::switchColor($num,"tblrow1","tblrow2"));
				$tpl->parseCurrentBlock();

				if ($this->container_gui->isActiveAdministrationPanel())
				{
					$tpl->setCurrentBlock("block_row_check");
					$tpl->setVariable("ITEM_ID", $cont_data['ref_id']);
					$tpl->parseCurrentBlock();
				}

				// change row color
				$tpl->setVariable("ITEM_HTML",$html);
				$tpl->setVariable("ROWCOL", ilUtil::switchColor($num,"tblrow1","tblrow2"));
				$tpl->setVariable("TYPE_IMG", ilUtil::getImagePath("icon_".$cont_data["type"].".gif"));
				$tpl->setVariable("ALT_IMG", $this->lng->txt("obj_".$cont_data["type"]));
				$tpl->setCurrentBlock("tbl_content");
				$tpl->parseCurrentBlock();
				// increment counter
			}
			$num++;
		}

		// create table
		include_once "./Services/Table/classes/class.ilTableGUI.php";
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','il_ContainerBlock');

		// title & header columns
		$tbl->setTitle($this->lng->txt("crs_content"),"icon_crs.gif",$this->lng->txt("courses"));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));

		if($this->is_tutor)
		{
			$tbl->setHeaderNames(array($this->lng->txt("type"),$this->lng->txt("title"),
									   ""));
			$tbl->setHeaderVars(array("type","title","options"),
								array("ref_id" => $this->course_obj->getRefId(),
									  "cmdClass" => "ilobjcoursecontentgui",
									  "cmdNode" => $_GET["cmdNode"]));
			$tbl->setColumnWidth(array("1px","100%","24px"));
			$tbl->disable("header");
		}
		else
		{
			$tbl->setHeaderNames(array($this->lng->txt("type"),$this->lng->txt("title"),''));
			$tbl->setHeaderVars(array("type","title",'options'),
								array("ref_id" => $this->course_obj->getRefId(),
									  "cmdClass" => "ilobjcoursecontentgui",
									  "cmdNode" => $_GET["cmdNode"]));
			$tbl->setColumnWidth(array("1px",""));
			$tbl->disable("header");
		}

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');
		$tbl->disable("form");

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setCurrentBlock("cont_page_content");
		$this->tpl->setVariable("CONTAINER_PAGE_CONTENT", $tpl->get());
		$this->tpl->parseCurrentBlock();
		$this->container_gui->showAdministrationPanel($this->tpl);

		return true;

	}

	function editTimings()
	{
		global $ilAccess,$ilErr;

		include_once 'Services/MetaData/classes/class.ilMDEducational.php';
		include_once 'classes/class.ilLink.php';

		$this->lng->loadLanguageModule('meta');

		if(!$ilAccess->checkAccess('write','',$this->container_obj->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('msg_no_perm_write'),$ilErr->WARNING);
		}
		$this->__showTimingsPanel();
		$this->tabs_gui->setSubTabActive('timings_timings');

		$this->course_obj->initCourseItemObject($this->container_obj->getRefId());
		$this->cont_arr = $this->course_obj->items_obj->getAllItems($this->container_obj->getId());

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.crs_edit_items.html','Modules/Course');
		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("HEADER_IMG",ilUtil::getImagePath('icon_crs.gif'));
		$this->tpl->setVariable("HEADER_ALT",$this->lng->txt('crs_materials'));
		$this->tpl->setVariable("BLOCK_HEADER_CONTENT",$this->lng->txt('edit_timings_list'));
		$this->tpl->setVariable("TXT_TITLE",$this->lng->txt('title'));


		$this->tpl->setVariable("TXT_DURATION",$this->lng->txt('crs_timings_time_frame'));
		$this->tpl->setVariable("TXT_INFO_DURATION",$this->lng->txt('crs_timings_in_days'));

		$this->tpl->setVariable("TXT_START_END",$this->lng->txt('crs_timings_short_start_end'));
		$this->tpl->setVariable("TXT_INFO_START_END",$this->lng->txt('crs_timings_start_end_info'));

		$this->tpl->setVariable("TXT_CHANGEABLE",$this->lng->txt('crs_timings_short_changeable'));

		$this->tpl->setVariable("TXT_INFO_LIMIT",$this->lng->txt('crs_timings_from_until'));
		$this->tpl->setVariable("TXT_LIMIT",$this->lng->txt('crs_timings_short_limit_start_end'));
		$this->tpl->setVariable("TXT_ACTIVE",$this->lng->txt('crs_timings_short_active'));
		$this->tpl->setVariable("TXT_INFO_ACTIVE",$this->lng->txt('crs_timings_info_active'));

		$counter = 0;
		foreach($this->cont_arr as $item)
		{
			$item = $this->__loadFromPost($item);
			$item_prefix = "item[$item[ref_id]]";
			$item_change_prefix = "item_change[$item[ref_id]]";
			$item_active_prefix = "item_active[$item[ref_id]]";

			if($item['type'] == 'grp' or
			   $item['type'] == 'fold')
			{
				$this->tpl->setVariable("TITLE_LINK",ilLink::_getLink($item['ref_id'],$item['type']));
				$this->tpl->setVariable("TITLE_FRAME",ilFrameTargetInfo::_getFrame('MainContent',$item['type']));
				$this->tpl->setVariable("TITLE_LINK_NAME",$item['title']);
			}
			else
			{
				$this->tpl->setVariable("TITLE",$item['title']);
			}

			if(strlen($item['description']))
			{
				$this->tpl->setCurrentBlock("item_description");
				$this->tpl->setVariable("DESC",$item['description']);
				$this->tpl->parseCurrentBlock();
			}

			if($tlt = ilMDEducational::_getTypicalLearningTimeSeconds($item['obj_id']))
			{
				$this->tpl->setCurrentBlock("tlt");
				$this->tpl->setVariable("TXT_TLT",$this->lng->txt('meta_typical_learning_time'));
				$this->tpl->setVariable("TLT_VAL",ilFormat::_secondsToString($tlt));
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("container_standard_row");

			// Suggested
			if(is_array($_POST['item']["$item[ref_id]"]['sug_start']))
			{
				$start = $this->__toUnix($_POST['item']["$item[ref_id]"]['sug_start']);
			}
			else
			{
				$start = $item['suggestion_start'];
			}
			$end = $item['suggestion_end'];
			$date = $this->__prepareDateSelect($start);
			$this->tpl->setVariable("SUG_START",
									ilUtil::makeDateSelect($item_prefix."[sug_start]",
														   $date['y'],$date['m'],$date['d'],date('Y',time()),false));

			$this->tpl->setVariable("NAME_DURATION_A",$item_prefix."[duration_a]");
			if(isset($_POST['item']["$item[ref_id]"]['duration_a']))
			{
				$this->tpl->setVariable("VAL_DURATION_A",abs($_POST['item']["$item[ref_id]"]['duration_a']));
			}
			else
			{
				$this->tpl->setVariable("VAL_DURATION_A",intval(($end-$start)/(60*60*24)));
			}

			$this->tpl->setVariable("SUG_END",ilFormat::formatUnixTime($item['suggestion_end']));

			// Limit
			if(is_array($_POST['item']["$item[ref_id]"]['lim_end']))
			{
				$end = $this->__toUnix($_POST['item']["$item[ref_id]"]['lim_end']);
			}
			else
			{
				$end = $item['latest_end'];
			}

			$date = $this->__prepareDateSelect($end);
			$this->tpl->setVariable("LIM_END",
									ilUtil::makeDateSelect($item_prefix."[lim_end]",
														   $date['y'],$date['m'],$date['d'],date('Y',time()),false));

			$this->tpl->setVariable("NAME_CHANGE",$item_change_prefix."[change]");
			$this->tpl->setVariable("NAME_ACTIVE",$item_active_prefix."[active]");

			if(isset($_POST['item']))
			{
				$change = $_POST['item_change']["$item[ref_id]"]['change'];
				$active = $_POST['item_active']["$item[ref_id]"]['active'];
			}
			else
			{
				$change = $item['changeable'];
				$active = ($item['timing_type'] == IL_CRS_TIMINGS_PRESETTING);
			}

			$this->tpl->setVariable("CHECKED_ACTIVE",$active ? 'checked="checked"' : '');
			$this->tpl->setVariable("CHECKED_CHANGE",$change ? 'checked="checked"' : '');

			if(isset($this->failed["$item[ref_id]"]))
			{
				$this->tpl->setVariable("ROWCLASS",'tblrowmarked');
			}
			else
			{
				$this->tpl->setVariable("ROWCLASS",ilUtil::switchColor($counter++,'tblrow1','tblrow2'));
			}
			$this->tpl->parseCurrentBlock();
		}

		// Select all
		$this->tpl->setVariable("CHECKCLASS",ilUtil::switchColor($counter++,'tblrow1','tblrow2'));
		$this->tpl->setVariable("SELECT_ALL",$this->lng->txt('select_all'));

		$this->tpl->setVariable("BTN_SAVE",$this->lng->txt('save'));
		$this->tpl->setVariable("BTN_CANCEL",$this->lng->txt('cancel'));

	}

	function __showUserAcceptanceTable()
	{
		global $ilUser;

		include_once 'Modules/Course/classes/Timings/class.ilTimingAccepted.php';
		$accept_obj = new ilTimingAccepted($this->course_obj->getId(),$ilUser->getId());

		$this->tpl->setVariable("REMARK",$accept_obj->getRemark());
		$this->tpl->setVariable("ACCEPT_CHECKED",$accept_obj->isAccepted() ? 'checked="checked"' : '');
		$this->tpl->setVariable("TUTOR_CHECKED",$accept_obj->isVisible() ? 'checked="checked"' : '');

		$this->tpl->setVariable("TIMING_ACCEPT",$this->lng->txt('timing_accept_table'));
		$this->tpl->setVariable("TXT_ACCEPT",$this->lng->txt('timing_user_accept'));
		$this->tpl->setVariable("TXT_REMARK",$this->lng->txt('timing_remark'));
		$this->tpl->setVariable("TXT_TUTOR",$this->lng->txt('timing_tutor_visible'));
		$this->tpl->setVariable("TXT_BTN_UPDATE",$this->lng->txt('save'));
	}
	function saveAcceptance()
	{
		global $ilUser;

		include_once 'Modules/Course/classes/Timings/class.ilTimingAccepted.php';
		$accept_obj = new ilTimingAccepted($this->course_obj->getId(),$ilUser->getId());

		$accept_obj->setRemark(ilUtil::stripSlashes($_POST['remark']));
		$accept_obj->accept($_POST['accepted']);
		$accept_obj->setVisible($_POST['tutor']);
		$accept_obj->update();
		ilUtil::sendInfo($this->lng->txt('settings_saved'));
		$this->editUserTimings();
	}

	function editUserTimings()
	{
		if($_SESSION['crs_timings_panel'][$this->course_obj->getId()])
		{
			return $this->editTimings();
		}
		global $ilAccess,$ilErr;

		if(!$ilAccess->checkAccess('read','',$this->container_obj->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('msg_no_perm_read'),$ilErr->WARNING);
		}
		$this->tabs_gui->setSubTabActive('timings_timings');

		$_SESSION['crs_timings_user_hidden'] = isset($_GET['show_details']) ? $_GET['show_details'] : $_SESSION['crs_timings_user_hidden'];

		include_once 'Modules/Course/classes/class.ilCourseItems.php';
		if(ilCourseItems::_hasChangeableTimings($this->course_obj->getRefId()))
		{
			$this->__editAdvancedUserTimings();
		}
		else
		{
			$this->__editUserTimings();
		}
	}

	function returnToMembers()
	{
		$this->ctrl->returnToParent($this);
	}

	function showUserTimings()
	{
		global $ilObjDataCache;

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.crs_user_timings.html','Modules/Course');
		$this->tabs_gui->clearSubTabs();
		$this->tabs_gui->setTabActive('members');

		if(!$_GET['member_id'])
		{
			ilUtil::sendInfo($this->lng->txt('no_checkbox'),true);
			$this->ctrl->returnToParent($this);
		}


		// Back button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK",$this->ctrl->getLinkTarget($this,'returnToMembers'));
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("back"));
		$this->tpl->parseCurrentBlock();

		include_once 'Modules/Course/classes/Timings/class.ilTimingAccepted.php';
		$usr_accepted = new ilTimingAccepted($this->course_obj->getId(),(int) $_GET['member_id']);

		if($usr_accepted->isAccepted())
		{
			$this->tpl->setVariable("ACC_IMG",ilUtil::getImagePath('icon_ok.gif'));
			$this->tpl->setVariable("ACC_ALT",$this->lng->txt('timing_accepted'));
		}
		else
		{
			$this->tpl->setVariable("ACC_IMG",ilUtil::getImagePath('icon_not_ok.gif'));
			$this->tpl->setVariable("ACC_ALT",$this->lng->txt('timing_not_accepted'));
		}
		if($usr_accepted->isVisible() and strlen($usr_accepted->getRemark()))
		{
			$this->tpl->setVariable("REMARK",nl2br($usr_accepted->getRemark()));
		}
		else
		{
			$this->tpl->setVariable("REMARK",$this->lng->txt('not_available'));
		}

		$this->tpl->setVariable("TIMING_ACCEPT",$this->lng->txt('timing_accept_table'));
		$this->tpl->setVariable("TXT_ACCEPTED",$this->lng->txt('timing_user_accepted'));
		$this->tpl->setVariable("TXT_REMARK",$this->lng->txt('timing_remark'));

		$this->tpl->setVariable("HEADER_IMG",ilUtil::getImagePath('icon_usr.gif'));
		$this->tpl->setVariable("HEADER_ALT",$this->lng->txt('obj_usr'));
		$this->tpl->setVariable("TABLE_HEADER",$this->lng->txt('timings_of'));
		$name = ilObjUser::_lookupName($_GET['member_id']);
		$this->tpl->setVariable("USER_NAME",$name['lastname'].', '.$name['firstname']);

		$this->tpl->setVariable("TXT_TITLE",$this->lng->txt('title'));
		$this->tpl->setVariable("TXT_START_END",$this->lng->txt('crs_timings_short_start_end'));
		$this->tpl->setVariable("TXT_INFO_START_END",$this->lng->txt('crs_timings_start_end_info'));
		$this->tpl->setVariable("TXT_CHANGED",$this->lng->txt('crs_timings_changed'));
		$this->tpl->setVariable("TXT_OWN_PRESETTING",$this->lng->txt('crs_timings_planed_start'));
		$this->tpl->setVariable("TXT_INFO_OWN_PRESETTING",$this->lng->txt('crs_timings_from_until'));

		$this->items_obj = new ilCourseItems($this->course_obj,$this->course_obj->getRefId());
		$items =& $this->items_obj->getItems();

		foreach($items as $item)
		{
			if(($item['timing_type'] == IL_CRS_TIMINGS_PRESETTING) or
			   ilCourseItems::_hasChangeableTimings($item['ref_id']))
			{
				$this->__renderUserItem($item,0);
			}
		}
	}

	function __renderUserItem($item,$level)
	{
		global $ilUser,$ilAccess;

		include_once 'Modules/Course/classes/Timings/class.ilTimingPlaned.php';
		include_once './Services/MetaData/classes/class.ilMDEducational.php';

		$this->lng->loadLanguageModule('meta');

		$usr_planed = new ilTimingPlaned($item['ref_id'],$_GET['member_id']);
		for($i = 0;$i < $level;$i++)
		{
			$this->tpl->touchBlock('start_indent');
			$this->tpl->touchBlock('end_indent');
		}
		if(strlen($item['description']))
		{
			$this->tpl->setCurrentBlock("item_description");
			$this->tpl->setVariable("DESC",$item['description']);
			$this->tpl->parseCurrentBlock();
		}
		if($tlt = ilMDEducational::_getTypicalLearningTimeSeconds($item['obj_id']))
		{
			$this->tpl->setCurrentBlock("tlt");
			$this->tpl->setVariable("TXT_TLT",$this->lng->txt('meta_typical_learning_time'));
			$this->tpl->setVariable("TLT_VAL",ilFormat::_secondsToString($tlt));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("title_plain");
		$this->tpl->setVariable("TITLE",$item['title']);
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("container_standard_row");

		$this->tpl->setVariable("ROWCLASS",ilUtil::switchColor($this->counter++,'tblrow1','tblrow2'));
		$this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_'.$item['type'].'.gif'));
		$this->tpl->setVariable("TYPE_ALT_IMG",$this->lng->txt('obj_'.$item['type']));


		if($item['timing_type'] == IL_CRS_TIMINGS_PRESETTING)
		{
			$this->tpl->setVariable("SUG_START",ilFormat::formatUnixTime($item['suggestion_start']));
			$this->tpl->setVariable("SUG_END",ilFormat::formatUnixTime($item['suggestion_end']));
		}

		if($item['changeable'] and $item['timing_type'] == IL_CRS_TIMINGS_PRESETTING)
		{
			if($usr_planed->getPlanedStartingTime())
			{
				$start = $usr_planed->getPlanedStartingTime();
			}
			else
			{
				$start = $item['suggestion_start'];
			}

			$this->tpl->setVariable("OWN_START",ilFormat::formatUnixTime($start));

			if($usr_planed->getPlanedEndingTime())
			{
				$end = $usr_planed->getPlanedEndingTime();
			}
			else
			{
				$end = $item['suggestion_end'];
			}
			if($start != $item['suggestion_start'] or $end != $item['suggestion_end'])
			{
				$this->tpl->setVariable("OK_IMG",ilUtil::getImagePath('icon_ok.gif'));
				$this->tpl->setVariable("OK_ALT",$this->lng->txt('crs_timings_changed'));
			}
			else
			{
				$this->tpl->setVariable("OK_IMG",ilUtil::getImagePath('icon_not_ok.gif'));
				$this->tpl->setVariable("OK_ALT",$this->lng->txt('crs_timings_not_changed'));
			}
			$this->tpl->setVariable("OWN_END",ilFormat::formatUnixTime($end));

		}

		$this->tpl->parseCurrentBlock();

		$sub_items_obj = new ilCourseItems($this->course_obj,$item['ref_id'],$_GET['member_id']);
		foreach($sub_items_obj->getItems() as $item_data)
		{
			if(($item_data['timing_type'] == IL_CRS_TIMINGS_PRESETTING) or
			   ilCourseItems::_hasChangeableTimings($item_data['ref_id']))
			{
				$this->__renderUserItem($item_data,$level+1);
			}
		}
	}



	function __editAdvancedUserTimings()
	{
		include_once 'Modules/Course/classes/Event/class.ilEvent.php';

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.crs_usr_edit_timings_adv.html','Modules/Course');
		$this->__showTimingsPanel();
		$this->__showUserAcceptanceTable();

		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("HEADER_IMG",ilUtil::getImagePath('icon_crs.gif'));
		$this->tpl->setVariable("HEADER_ALT",$this->lng->txt('obj_crs'));
		$this->tpl->setVariable("BLOCK_HEADER_CONTENT",$this->lng->txt('timings_usr_edit'));

		if(!$_SESSION['crs_timings_user_hidden'])
		{
			$this->tpl->setVariable("SHOW_HIDE_TEXT",$this->lng->txt('show_details'));
			$this->ctrl->setParameter($this,'show_details',1);
			$this->tpl->setVariable("SHOW_HIDE_LINK",$this->ctrl->getLinkTarget($this,'editUserTimings'));
		}
		else
		{
			$this->tpl->setVariable("SHOW_HIDE_TEXT",$this->lng->txt('hide_details'));
			$this->ctrl->setParameter($this,'show_details',0);
			$this->tpl->setVariable("SHOW_HIDE_LINK",$this->ctrl->getLinkTarget($this,'editUserTimings'));
		}
		$this->ctrl->clearParameters($this);
		$this->tpl->setVariable("TXT_TITLE",$this->lng->txt('title'));
		$this->tpl->setVariable("TXT_START_END",$this->lng->txt('crs_timings_short_start_end'));
		$this->tpl->setVariable("TXT_INFO_START_END",$this->lng->txt('crs_timings_start_end_info'));

		$this->tpl->setVariable("TXT_LIMIT",$this->lng->txt('crs_timings_short_limit_start_end'));
		$this->tpl->setVariable("TXT_INFO_LIMIT",$this->lng->txt('crs_timings_from_until'));

		$this->tpl->setVariable("TXT_OWN_PRESETTING",$this->lng->txt('crs_timings_planed_start'));
		$this->tpl->setVariable("TXT_INFO_OWN_PRESETTING",$this->lng->txt('crs_timings_start_end_info'));

		$this->tpl->setVariable("TXT_DURATION",$this->lng->txt('crs_timings_time_frame'));
		$this->tpl->setVariable("TXT_INFO_DURATION",$this->lng->txt('crs_timings_in_days'));

		$this->tpl->setVariable("TXT_BTN_UPDATE",$this->lng->txt('save'));
		$this->tpl->setVariable("TXT_CANCEL",$this->lng->txt('cancel'));

		$this->items_obj = new ilCourseItems($this->course_obj,$this->course_obj->getRefId());
		$items =& $this->items_obj->getItems();

		$all_items = array_merge($this->items_obj->getFilteredItems($this->course_obj->getId()),
								 ilEvent::_getEventsAsArray($this->course_obj->getId()));
		$sorted_items = $this->__sortByStart($all_items);

		$this->counter = 0;
		foreach($sorted_items as $item)
		{
			switch($item['type'])
			{
				case 'event':
					$this->__renderEvent($item);
					break;

				default:
					$this->__renderItem($item,0);
					break;
			}
		}
	}

	function __editUserTimings()
	{
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.crs_usr_edit_timings.html','Modules/Course');

		$this->__showTimingsPanel();
		$this->__showUserAcceptanceTable();

		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("HEADER_IMG",ilUtil::getImagePath('icon_crs.gif'));
		$this->tpl->setVariable("HEADER_ALT",$this->lng->txt('obj_crs'));

		if(!$_SESSION['crs_timings_user_hidden'])
		{
			$this->tpl->setVariable("SHOW_HIDE_TEXT",$this->lng->txt('show_details'));
			$this->ctrl->setParameter($this,'show_details',1);
			$this->tpl->setVariable("SHOW_HIDE_LINK",$this->ctrl->getLinkTarget($this,'editUserTimings'));
		}
		else
		{
			$this->tpl->setVariable("SHOW_HIDE_TEXT",$this->lng->txt('hide_details'));
			$this->ctrl->setParameter($this,'show_details',0);
			$this->tpl->setVariable("SHOW_HIDE_LINK",$this->ctrl->getLinkTarget($this,'editUserTimings'));
		}
		$this->ctrl->clearParameters($this);

		$this->tpl->setVariable("BLOCK_HEADER_CONTENT",$this->lng->txt('timings_timings'));
		$this->tpl->setVariable("TXT_TITLE",$this->lng->txt('title'));
		$this->tpl->setVariable("TXT_START",$this->lng->txt('crs_timings_sug_begin'));
		$this->tpl->setVariable("TXT_END",$this->lng->txt('crs_timings_sug_end'));


		$this->items_obj = new ilCourseItems($this->course_obj,$this->course_obj->getRefId());

		$all_items = array_merge($this->items_obj->getFilteredItems($this->course_obj->getId()),
								 ilEvent::_getEventsAsArray($this->course_obj->getId()));
		$sorted_items = $this->__sortByStart($all_items);

		$this->counter = 0;
		foreach($sorted_items as $item)
		{
			switch($item['type'])
			{
				case 'event':
					$this->__renderEvent($item);
					break;

				default:
					$this->__renderItem($item,0);
					break;
			}
		}
	}



	function __sortByStart($a_items)
	{
		foreach($a_items as $item)
		{
			if($item['timing_type'] == IL_CRS_TIMINGS_DEACTIVATED)
			{
				$inactive[] = $item;
			}
			else
			{
				$active[] = $item;
			}
		}
		$sorted_active = ilUtil::sortArray((array) $active,"start","asc");
		$sorted_inactive = ilUtil::sortArray((array) $inactive,'title','asc');

		return array_merge($sorted_active,$sorted_inactive);
	}

	function __renderEvent($item)
	{
		if(strlen($item['description']))
		{
			$this->tpl->setCurrentBlock("item_description");
			$this->tpl->setVariable("DESC",$item['description']);
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("title_as_link");
		$this->ctrl->setParameterByClass('ileventadministrationgui','event_id',$item['event_id']);
		$this->tpl->setVariable("TITLE_LINK",$this->ctrl->getLinkTargetByClass('ileventadministrationgui','info'));
		$this->tpl->setVariable("TITLE_NAME",$item['title']);
		$this->tpl->parseCurrentBlock();

		$this->tpl->setVariable("SUG_START",ilFormat::formatUnixTime($item['start']));
		$this->tpl->setVariable("SUG_END",ilFormat::formatUnixTime($item['end']));

		$this->tpl->setCurrentBlock("tlt");
		$this->tpl->setVariable("TXT_TLT",$this->lng->txt('event_date'));
		$this->tpl->setVariable("TLT_VAL",ilEventAppointment::_appointmentToString($item['start'],$item['end'],$item['fulltime']));
		$this->tpl->parseCurrentBlock();



		$this->tpl->setCurrentBlock("container_standard_row");
		$this->tpl->setVariable("ROWCLASS",ilUtil::switchColor($this->counter++,'tblrow1','tblrow2'));
		$this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_'.$item['type'].'.gif'));
		$this->tpl->setVariable("TYPE_ALT_IMG",$this->lng->txt('obj_'.$item['type']));
		$this->tpl->parseCurrentBlock();

		#if(!$_SESSION['crs_timings_user_hidden'])
		#{
		#	return true;
		#}
		foreach($this->items_obj->getItemsByEvent($item['event_id']) as $item)
		{
			$this->__renderItem($item,1);
		}
	}

	function __renderItem($item,$level)
	{
		global $ilUser,$ilAccess;

		include_once 'Modules/Course/classes/Timings/class.ilTimingPlaned.php';
		include_once './classes/class.ilLink.php';
		include_once './Services/MetaData/classes/class.ilMDEducational.php';

		$this->lng->loadLanguageModule('meta');

		$usr_planed = new ilTimingPlaned($item['ref_id'],$ilUser->getId());

		for($i = 0;$i < $level;$i++)
		{
			$this->tpl->touchBlock('start_indent');
			$this->tpl->touchBlock('end_indent');
		}
		if(strlen($item['description']))
		{
			$this->tpl->setCurrentBlock("item_description");
			$this->tpl->setVariable("DESC",$item['description']);
			$this->tpl->parseCurrentBlock();
		}
		if($tlt = ilMDEducational::_getTypicalLearningTimeSeconds($item['obj_id']))
		{
			$this->tpl->setCurrentBlock("tlt");
			$this->tpl->setVariable("TXT_TLT",$this->lng->txt('meta_typical_learning_time'));
			$this->tpl->setVariable("TLT_VAL",ilFormat::_secondsToString($tlt));
			$this->tpl->parseCurrentBlock();
		}

		if($ilAccess->checkAccess('read','',$item['ref_id']))
		{
			$this->tpl->setCurrentBlock("title_as_link");
			$this->tpl->setVariable("TITLE_LINK",ilLink::_getLink($item['ref_id'],$item['type']));
			$this->tpl->setVariable("TITLE_NAME",$item['title']);
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$this->tpl->setCurrentBlock("title_plain");
			$this->tpl->setVariable("TITLE",$item['title']);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("container_standard_row");

		if(isset($this->invalid["$item[ref_id]"]))
		{
			$this->tpl->setVariable("ROWCLASS",'tblrowmarked');
		}
		else
		{
			$this->tpl->setVariable("ROWCLASS",ilUtil::switchColor($this->counter++,'tblrow1','tblrow2'));
		}
		$this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_'.$item['type'].'.gif'));
		$this->tpl->setVariable("TYPE_ALT_IMG",$this->lng->txt('obj_'.$item['type']));


		if($item['timing_type'] == IL_CRS_TIMINGS_PRESETTING)
		{
			$this->tpl->setVariable("SUG_START",ilFormat::formatUnixTime($item['suggestion_start']));
			$this->tpl->setVariable("SUG_END",ilFormat::formatUnixTime($item['suggestion_end']));
		}

		if($item['changeable'])
		{
			$item_prefix = "item[".$item['ref_id'].']';

			if(is_array($_POST['item']["$item[ref_id]"]['own_start']))
			{
				#echo "Start post<br>";
				$start = $this->__toUnix($_POST['item']["$item[ref_id]"]['own_start']);
			}
			elseif($usr_planed->getPlanedStartingTime())
			{
				#echo "Own start<br>";
				$start = $usr_planed->getPlanedStartingTime();
			}
			else
			{
				#echo "Empfehlung start<br>";
				$start = $item['suggestion_start'];
			}

			$date = $this->__prepareDateSelect($start);
			$this->tpl->setVariable("OWN_START",
									ilUtil::makeDateSelect($item_prefix."[own_start]",
														   $date['y'],$date['m'],$date['d'],date('Y',time()),false));

			if($usr_planed->getPlanedEndingTime())
			{
				#echo "Own End<br>";
				$end = $usr_planed->getPlanedEndingTime();
			}
			else
			{
				#echo "Empfehlung end<br>";
				$end = $item['suggestion_end'];
			}
			$this->tpl->setVariable("OWN_END",ilFormat::formatUnixTime($end));
			$this->tpl->setVariable("NAME_DURATION",$item_prefix."[duration]");

			// Duration
			if(isset($_POST['item']["$item[ref_id]"]['duration']))
			{
				$this->tpl->setVariable("VAL_DURATION",$_POST['item']["$item[ref_id]"]['duration']);
			}
			else
			{
				$this->tpl->setVariable("VAL_DURATION",intval(($end - $start) / (60 * 60 * 24)));
			}
			$this->tpl->setVariable("LIM_START",ilFormat::formatUnixTime($item['earliest_start']));
			$this->tpl->setVariable("LIM_END",ilFormat::formatUnixTime($item['latest_end']));
		}

		$this->tpl->parseCurrentBlock();

		if(!$_SESSION['crs_timings_user_hidden'])
		{
			return true;
		}

		$sub_items_obj = new ilCourseItems($this->course_obj,$item['ref_id']);
		foreach($sub_items_obj->getItems() as $item_data)
		{
			$this->__renderItem($item_data,$level+1);
		}
	}

	function __showTimingsPanel()
	{
		global $ilAccess;

		if(!$ilAccess->checkAccess('write','',$this->container_obj->getRefId()))
		{
			return true;
		}

		if(!$_SESSION['crs_timings_panel'][$this->course_obj->getId()])
		{
			$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK",$this->ctrl->getLinkTarget($this,'timingsOn'));
			$this->tpl->setVariable("BTN_TXT",$this->lng->txt("timings_timings_on"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK",$this->ctrl->getLinkTarget($this,'timingsOff'));
			$this->tpl->setVariable("BTN_TXT",$this->lng->txt("timings_timings_off"));
			$this->tpl->parseCurrentBlock();
		}
	}

	function timingsOn()
	{
		global $ilTabs;
		$_SESSION['crs_timings_panel'][$this->course_obj->getId()] = 1;

		$ilTabs->clearSubTabs();
		$this->__setSubTabs();
		$this->editTimings();
	}

	function timingsOff()
	{
		global $ilTabs;
		$_SESSION['crs_timings_panel'][$this->course_obj->getId()] = 0;

		$ilTabs->clearSubTabs();
		$this->__setSubTabs();
		$this->editUserTimings();
	}


	function updateUserTimings()
	{
		global $ilUser,$ilObjDataCache;
		include_once 'Modules/Course/classes/Timings/class.ilTimingPlaned.php';

		// Validate
		$this->invalid = array();
		foreach($_POST['item'] as $ref_id => $data)
		{
			$tmp_planed = new ilTimingPlaned($ref_id,$ilUser->getId());

			$tmp_planed->setPlanedStartingTime($this->__toUnix($data['own_start']));
			if(isset($data['duration']))
			{
				$data['own_start']['d'] += $data['duration'];
				$tmp_planed->setPlanedEndingTime($this->__toUnix($data['own_start'],array('h' => 23,'m' => 55)));
			}
			else
			{
				$tmp_planed->setPlanedEndingTime($this->__toUnix($data['own_start']),array('h' => 23,'m' => 55));
			}
			if(!$tmp_planed->validate())
			{
				$this->invalid[$ref_id] = $ilObjDataCache->lookupTitle($ilObjDataCache->lookupObjId($ref_id));
			}
			$all_items[] = $tmp_planed;
		}
		if(count($this->invalid))
		{
			$message = $this->lng->txt('crs_timings_update_error');
			$message .= ("<br />".$this->lng->txt('crs_materials').': ');
			$message .= (implode(',',$this->invalid));
			ilUtil::sendInfo($message);
			$this->editUserTimings();
			return false;
		}
		foreach($all_items as $new_item_obj)
		{
			$new_item_obj->update();
		}
		ilUtil::sendInfo($this->lng->txt('settings_saved'));
		$this->editUserTimings();
		return true;

	}


	function &__loadFromPost(&$item)
	{
		$obj_id = $item['obj_id'];

		if(!isset($_POST['item'][$obj_id]))
		{
			return $item;
		}
		$item['suggestion_start'] = $this->__toUnix($_POST['item'][$obj_id]['sug_start']);
		$item['suggestion_end'] = $this->__toUnix($_POST['item'][$obj_id]['sug_end']);
		$item['earliest_start'] = $this->__toUnix($_POST['item'][$obj_id]['lim_start']);
		$item['latest_end'] = $this->__toUnix($_POST['item'][$obj_id]['lim_end']);
		$item['changeable'] = $_POST['item'][$obj_id]['change'];
		$item['timing_type'] = $_POST['item'][$obj_id]['active'] ? IL_CRS_TIMINGS_PRESETTING : $item['timing_type'];
		$item['duration_a'] = $_POST['item'][$obj_id]['duration_a'];
		$item['duration_b'] = $_POST['item'][$obj_id]['duration_b'];

		return $item;
	}

	function updateTimings()
	{
		include_once 'Modules/Course/classes/class.ilCourseItems.php';

		global $ilAccess,$ilErr;

		if(!$ilAccess->checkAccess('write','',$this->container_obj->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('msg_no_perm_write'),$ilErr->WARNING);
		}
		$this->failed = array();
		// Validate

		$_POST['item'] = is_array($_POST['item']) ? $_POST['item'] : array();
		$all_items = array();

		foreach($_POST['item'] as $ref_id => $data)
		{
			$item_obj =& new ilCourseItems($this->course_obj,$this->container_obj->getRefId());
			$old_data = $item_obj->getItem($ref_id);

			$item_obj->setTimingType($_POST['item_active'][$ref_id]['active'] ? IL_CRS_TIMINGS_PRESETTING : IL_CRS_TIMINGS_DEACTIVATED);
			$item_obj->setTimingStart($old_data['timing_start']);
			$item_obj->setTimingEnd($old_data['timing_end']);
			$item_obj->setSuggestionStart($this->__toUnix($data["sug_start"]));

			// add duration
			$data['sug_start']['d'] += abs($data['duration_a']);
			$item_obj->setSuggestionEnd($this->__toUnix($data['sug_start'],array('h' => 23,'m' => 55)));

			$item_obj->setEarliestStart(time());
			$item_obj->setLatestEnd($this->__toUnix($data['lim_end'],array('h' => 23,'m' => 55)));

			$item_obj->toggleVisible($old_data['visible']);
			$item_obj->toggleChangeable($_POST['item_change'][$ref_id]['change']);

			if(!$item_obj->validateActivation())
			{
				$this->failed[$ref_id] = $old_data['title'];
			}
			$all_items[$ref_id] =& $item_obj;
			unset($item_obj);
		}

		if(count($this->failed))
		{
			$message = $this->lng->txt('crs_timings_update_error');
			$message .= ("<br />".$this->lng->txt('crs_materials').': ');
			$message .= (implode(',',$this->failed));
			ilUtil::sendInfo($message);
			$this->editTimings();
			return false;
		}

		// No do update 
		foreach($all_items as $ref_id => $item_obj_new)
		{
			$item_obj_new->update($ref_id);
		}
		ilUtil::sendInfo($this->lng->txt('settings_saved'));
		$this->editTimings();
		return false;
	}



	function __getItemHTML($cont_data,$a_show_path = false)
	{
		include_once './classes/class.ilObjectListGUIFactory.php';

		// ACTIVATION
		$activation = '';
		if($cont_data['timing_type'] != IL_CRS_TIMINGS_DEACTIVATED)
		{
			$long = $cont_data['timing_type'] == IL_CRS_TIMINGS_ACTIVATION;

			$activation = ilFormat::formatUnixTime($cont_data['start'],$long).' - '.
				ilFormat::formatUnixTime($cont_data['end'],$long);
		}
		// get item list gui object
		if (!is_object ($this->list_gui[$cont_data["type"]]))
		{
			$item_list_gui =& ilObjectListGUIFactory::_getListGUIByType($cont_data["type"]);

			$item_list_gui->setContainerObject($this->container_gui);
			// Enable/disable subscription depending on course settings
			$item_list_gui->enableSubscribe($this->course_obj->getAboStatus());

			$this->list_gui[$cont_data["type"]] =& $item_list_gui;
		}
		else
		{
			$item_list_gui =& $this->list_gui[$cont_data["type"]];
		}

		// show administration command buttons (or not)
		if (!$this->container_gui->isActiveAdministrationPanel())
		{
			$item_list_gui->enableDelete(false);
			$item_list_gui->enableLink(false);
			$item_list_gui->enableCut(false);
		}

		// add activation custom property
		if ($activation != "")
		{
			$item_list_gui->addCustomProperty($this->lng->txt($cont_data['activation_info']), $activation,
											  false, true);
		}

		if($a_show_path and $this->is_tutor)
		{
			$item_list_gui->addCustomProperty($this->lng->txt('path'),
											  $this->__buildPath($cont_data['ref_id']),
											  false,
											  true);
		}


		if($this->is_tutor)
		{
			$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"ref_id",
											 $this->container_obj->getRefId());
			$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"item_id",
											 $cont_data['child']);

			$item_list_gui->addCustomCommand($this->ctrl->getLinkTargetByClass('ilCourseItemAdministrationGUI',
																			   'edit'),
											 'activation');
		}

		$html = $item_list_gui->getListItemHTML($cont_data['ref_id'],
												$cont_data['obj_id'], $cont_data['title'], $cont_data['description']);
	
		$this->container_gui->determineAdminCommands($cont_data['ref_id'],
													 $item_list_gui->adminCommandsIncluded());

		return $html;
	}

	function __getOptions($cont_data,$num)
	{
		if($this->is_tutor)
		{
			$images = array();
			if($this->course_obj->getOrderType() == $this->course_obj->SORT_MANUAL)
			{
				if($num != 0)
				{
					$tmp_array["gif"] = ilUtil::getImagePath("a_up.gif");
					$tmp_array["lng"] = $this->lng->txt("crs_move_up");

					$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"ref_id",
													 $this->container_obj->getRefId());
					$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"item_id",
													 $cont_data['child']);
					$tmp_array['lnk'] = $this->ctrl->getLinkTargetByClass('ilcourseitemadministrationgui','moveUp');
					$tmp_array["tar"] = "";

					$images[] = $tmp_array;
				}
				if($num != count($this->cont_arr) - 1)
				{
					$tmp_array["gif"] = ilUtil::getImagePath("a_down.gif");
					$tmp_array["lng"] = $this->lng->txt("crs_move_down");
					$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"ref_id",
													 $this->container_obj->getRefId());
					$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"item_id",
													 $cont_data['child']);
					$tmp_array['lnk'] = $this->ctrl->getLinkTargetByClass('ilcourseitemadministrationgui','moveDown');

					$images[] = $tmp_array;
				}
			}

		}
		return $images ? $images : array();
	}


	function __showMaterial(&$tpl,$cont_data,$num)
	{
		include_once './classes/class.ilObjectListGUIFactory.php';

		// ACTIVATION
		$activation = '';
		if($cont_data['timing_type'] == IL_CRS_TIMINGS_ACTIVATION)
		{
			$activation = ilFormat::formatUnixTime($cont_data['start'],true).' - '.
				ilFormat::formatUnixTime($cont_data['end'],true);
		}

		// get item list gui object
		if (!is_object ($this->list_gui[$cont_data["type"]]))
		{
			$item_list_gui =& ilObjectListGUIFactory::_getListGUIByType($cont_data["type"]);

			$item_list_gui->setContainerObject($this->container_gui);
			// Enable/disable subscription depending on course settings
			$item_list_gui->enableSubscribe($this->course_obj->getAboStatus());

			$this->list_gui[$cont_data["type"]] =& $item_list_gui;
		}
		else
		{
			$item_list_gui =& $this->list_gui[$cont_data["type"]];
		}

		// show administration command buttons (or not)
		if (!$this->container_gui->isActiveAdministrationPanel())
		{
			$item_list_gui->enableDelete(false);
			$item_list_gui->enableLink(false);
			$item_list_gui->enableCut(false);
		}

		// add activation custom property
		if ($activation != "")
		{
			$item_list_gui->addCustomProperty($this->lng->txt($cont_data['activation_info']), $activation,
											  false, true);
		}

		if($this->is_tutor)
		{
			$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"ref_id",
											 $this->container_obj->getRefId());
			$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"item_id",
											 $cont_data['child']);

			$item_list_gui->addCustomCommand($this->ctrl->getLinkTargetByClass('ilCourseItemAdministrationGUI',
																			   'edit'),
											 'activation');
		}

		$html = $item_list_gui->getListItemHTML($cont_data['ref_id'],
												$cont_data['obj_id'], $cont_data['title'], $cont_data['description']);

		$this->container_gui->determineAdminCommands($cont_data['ref_id'],
													 $item_list_gui->adminCommandsIncluded());

		if(strlen($html))
		{
			$tpl->setVariable("ITEM_HTML", $html);
		}

		// OPTIONS
		if($this->is_tutor)
		{
			$images = array();
			if($this->course_obj->getOrderType() == $this->course_obj->SORT_MANUAL)
			{
				if($num != 0)
				{
					$tmp_array["gif"] = ilUtil::getImagePath("a_up.gif");
					$tmp_array["lng"] = $this->lng->txt("crs_move_up");

					$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"ref_id",
													 $this->container_obj->getRefId());
					$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"item_id",
													 $cont_data['child']);
					$tmp_array['lnk'] = $this->ctrl->getLinkTargetByClass('ilcourseitemadministrationgui','moveUp');
					$tmp_array["tar"] = "";

					$images[] = $tmp_array;
				}
				if($num != count($this->cont_arr) - 1)
				{
					$tmp_array["gif"] = ilUtil::getImagePath("a_down.gif");
					$tmp_array["lng"] = $this->lng->txt("crs_move_down");
					$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"ref_id",
													 $this->container_obj->getRefId());
					$this->ctrl->setParameterByClass('ilcourseitemadministrationgui',"item_id",
													 $cont_data['child']);
					$tmp_array['lnk'] = $this->ctrl->getLinkTargetByClass('ilcourseitemadministrationgui','moveDown');

					$images[] = $tmp_array;
				}
			}

			foreach($images as $key => $image)
			{
				$tpl->setCurrentBlock("img");
				$tpl->setVariable("IMG_TYPE",$image["gif"]);
				$tpl->setVariable("IMG_ALT",$image["lng"]);
				$tpl->setVariable("IMG_LINK",$image["lnk"]);
				$tpl->setVariable("IMG_TARGET",$image["tar"]);
				$tpl->parseCurrentBlock();
			}
			unset($images);

			$tpl->setCurrentBlock("options");
			$tpl->setVariable("OPT_ROWCOL", ilUtil::switchColor($num,"tblrow1","tblrow2"));
			$tpl->parseCurrentBlock();
		} // END write perm

		if(strlen($html))
		{
			if ($this->container_gui->isActiveAdministrationPanel())
			{
				$tpl->setCurrentBlock("block_row_check");
				$tpl->setVariable("ITEM_ID", $cont_data['ref_id']);
				$tpl->parseCurrentBlock();
				//$nbsp = false;
			}

			// change row color
			$tpl->setVariable("ROWCOL", ilUtil::switchColor($num,"tblrow1","tblrow2"));
			$tpl->setVariable("TYPE_IMG", ilUtil::getImagePath("icon_".$cont_data["type"].".gif"));
			$tpl->setVariable("ALT_IMG", $this->lng->txt("obj_".$cont_data["type"]));
			$tpl->setCurrentBlock("tbl_content");
			$tpl->parseCurrentBlock();
			// increment counter
			return ++$num;
		}
		return $num;
	}

	function __showFeedback()
	{
		if(!$this->is_tutor && $this->container_obj->getType() == 'crs')
		{
			include_once('Services/Feedback/classes/class.ilFeedbackGUI.php');
			$feedbackGUI = new ilFeedbackGUI();
			$feedbackHTML = $feedbackGUI->getCRSFeedbackListHTML();

			if(strlen($feedbackHTML))
			{
				$this->tpl->setCurrentBlock("cont_page_content");
				$this->tpl->setVariable("CONTAINER_PAGE_CONTENT",$feedbackHTML);
				$this->tpl->parseCurrentBlock();
			}
		}
		return true;
	}

	function __setSubTabs()
	{
		global $ilAccess;

		if($this->container_obj->getType() != 'crs')
		{
			return true;
		}
		if(!$ilAccess->checkAccess('write','',
								   $this->course_obj->getRefId(),'crs',$this->course_obj->getId()))
		{
			$this->is_tutor = false;
			// No further tabs if objective view or archives
			if($this->course_obj->enabledObjectiveView())
			{
				return false;
			}
		}
		else
		{
			$this->is_tutor = true;
		}

		// These subtabs should also work, if the command is called directly in
		// ilObjCourseGUI, so please use ...ByClass methods.
		// (see ilObjCourseGUI->executeCommand: case "ilcolumngui")
		
		if($this->course_obj->enabledObjectiveView())
		{
			// Objective gui
			$this->tabs_gui->addSubTabTarget('learners_view',
				$this->ctrl->getLinkTargetByClass(array('ilcoursecontentgui', 'ilcourseobjectivepresentationgui'),'view'));
		}
		if(!$_SESSION['crs_timings_panel'][$this->course_obj->getId()])
		{
			$this->tabs_gui->addSubTabTarget('crs_content',
				$this->ctrl->getLinkTargetByClass("ilobjcoursegui",'view'));
		}
		include_once 'Modules/Course/classes/class.ilCourseItems.php';
		if(!$this->course_obj->enabledObjectiveView() and $this->course_obj->getViewMode() == IL_CRS_VIEW_TIMING)
		{
			$this->tabs_gui->addSubTabTarget('timings_timings',
				$this->ctrl->getLinkTargetByClass('ilcoursecontentgui','editUserTimings'));
		}

		if($this->is_tutor)
		{
			$this->tabs_gui->addSubTabTarget('crs_archives',
				$this->ctrl->getLinkTargetByClass(
					array('ilcoursecontentgui', 'ilcoursearchivesgui'),'view'));
		}

		return true;
	}

	function __initCourseObject()
	{
		global $tree;

		if($this->container_obj->getType() == 'crs')
		{
			// Container is course
			$this->course_obj =& $this->container_obj;
		}
		else
		{
			$course_ref_id = $tree->checkForParentType($this->container_obj->getRefId(),'crs');
			$this->course_obj =& ilObjectFactory::getInstanceByRefId($course_ref_id);
		}
		return true;
	}

	function __toUnix($date,$time = array())
	{
		return mktime($time['h'],$time['m'],0,$date['m'],$date['d'],$date['y']);
	}

	function __prepareDateSelect($a_unix_time)
	{
		return array('y' => date('Y',$a_unix_time),
					 'm' => date('m',$a_unix_time),
					 'd' => date('d',$a_unix_time));
	}

	function __prepareTimeSelect($a_unix_time)
	{
		return array('h' => date('G',$a_unix_time),
					 'm' => date('i',$a_unix_time),
					 's' => date('s',$a_unix_time));
	}


	function __buildPath($a_ref_id)
	{
		global $tree;

		$path_arr = $tree->getPathFull($a_ref_id,$this->course_obj->getRefId());
		$counter = 0;
		foreach($path_arr as $data)
		{
			if($counter++)
			{
				$path .= " -> ";
			}
			$path .= $data['title'];
		}

		return $path;
	}


} // END class.ilCourseContentGUI
?>
