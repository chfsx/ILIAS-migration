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
* class ilobjcourseobjectivesgui
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @extends Object
*/

class ilCourseObjectivesGUI
{
	var $ctrl;
	var $ilias;
	var $ilErr;
	var $lng;
	var $tpl;

	var $course_obj;
	var $course_id;
	
	function ilCourseObjectivesGUI($a_course_id)
	{
		include_once './Modules/Course/classes/class.ilCourseObjective.php';

		global $ilCtrl,$lng,$ilErr,$ilias,$tpl,$tree,$ilTabs;

		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this,array("ref_id"));

		$this->ilErr =& $ilErr;
		$this->lng =& $lng;
		$this->tpl =& $tpl;
		$this->tree =& $tree;
		$this->tabs_gui =& $ilTabs;

		$this->course_id = $a_course_id;
		$this->__initCourseObject();
	}

	/**
	 * execute command
	 */
	function &executeCommand()
	{
		global $ilTabs;

		$ilTabs->setTabActive('crs_objectives');
		
		$cmd = $this->ctrl->getCmd();


		if (!$cmd = $this->ctrl->getCmd())
		{
			$cmd = "list";
		}
		
		$this->setSubTabs();
		$this->$cmd();
	}

	function listAssignedLM()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}

		$this->ctrl->setParameter($this,'objective_id',(int) $_GET['objective_id']);
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_objectives_list_lm.html",'Modules/Course');

		if(!count($this->__getAllLMs()))
		{
			$this->__showButton('listObjectives',$this->lng->txt('crs_objective_overview_objectives'));
			ilUtil::sendInfo($this->lng->txt('crs_no_lms_inside_course'));
			
			return true;
		}

		$this->__initLMObject((int) $_GET['objective_id']);
		if(!count($lms = $this->objectives_lm_obj->getLMs()))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_lms_assigned'));
			#$this->__showButton('listObjectives',$this->lng->txt('crs_objective_overview_objectives'));
			$this->__showButton('assignLMSelect',$this->lng->txt('crs_objective_assign_lm'));

			return true;
		}

		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_objectives_lm_list_row.html",'Modules/Course');

		#$this->__showButton('listObjectives',$this->lng->txt('crs_objective_overview_objectives'));

		$counter = 0;
		foreach($lms as $item)
		{
			++$counter;

			$tmp_lm =& ilObjectFactory::getInstanceByRefId($item['ref_id']);

			$title = $tmp_lm->getTitle();
			if($item['type'] == 'st')
			{
				include_once './content/classes/class.ilLMObjectFactory.php';

				$st_obj = ilLMObjectFactory::getInstance($tmp_lm,$item['obj_id']);
				$title .= (" -> ".$st_obj->getTitle());
			}
				
			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL",ilUtil::switchColor($counter,"tblrow2","tblrow1"));
			$tpl->setVariable("CHECK_OBJECTIVE",ilUtil::formCheckbox(0,'lm[]',$item['lm_ass_id']));

			$tpl->setVariable("IMG_TYPE",ilUtil::getImagePath('icon_'.$tmp_lm->getType().'.gif'));
			$tpl->setVariable("IMG_ALT",$this->lng->txt('obj_'.$tmp_lm->getType()));
			$tpl->setVariable("TITLE",$title);
			$tpl->setVariable("DESCRIPTION",$tmp_lm->getDescription());
			$tpl->parseCurrentBlock();

			unset($tmp_lm);
		}

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));

		// Show action row
		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'askDeleteLM');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('crs_objective_delete_lm_assignment'));
		$tpl->parseCurrentBlock();

		// Show add button
		$tpl->setCurrentBlock("plain_button");
		$tpl->setVariable("PBTN_NAME",'assignLMSelect');
		$tpl->setVariable("PBTN_VALUE",$this->lng->txt('crs_objective_assign_lm'));
		$tpl->parseCurrentBlock();


		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",3);
		$tpl->setVariable("WIDTH","width=\"50%\"");
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->parseCurrentBlock();


		// create table
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','std');

		// title & header columns
		$objective_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);

		$header_title = $this->lng->txt("crs_objectives_assigned_lms")." (".$objective_obj->getTitle().")";

		$tbl->setTitle($header_title,"icon_crs.gif",$this->lng->txt("crs_objectives"));

		$tbl->setHeaderNames(array('',$this->lng->txt('type'),$this->lng->txt("title")));
		$tbl->setHeaderVars(array("","type","title"), 
							array("ref_id" => $this->course_obj->getRefId(),
								  "objective_id" => (int) $_GET['objective_id'],
								  "cmdClass" => "ilcourseobjectivesgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("1%","1%",'98%'));

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($objectives));

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("OBJECTIVES_TABLE", $tpl->get());

		return true;


	}

	function askDeleteLM()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!count($_POST['lm']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_lm_no_assignments_selected'));
			$this->listAssignedLM();

			return false;
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_objectives_delete_lm.html",'Modules/Course');
		$this->ctrl->setParameter($this,'objective_id',(int) $_GET['objective_id']);

		ilUtil::sendInfo($this->lng->txt('crs_deassign_lm_sure'));

		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_objectives_delete_lm_row.html",'Modules/Course');

		$this->__initLMObject((int) $_GET['objective_id']);

		$counter = 0;
		foreach($_POST['lm'] as $lm_ass_id)
		{
			$lm_ass_data = $this->objectives_lm_obj->getLM($lm_ass_id);

			$tmp_lm =& ilObjectFactory::getInstanceByRefId($lm_ass_data['ref_id']);
			$title = $tmp_lm->getTitle();
			if($lm_ass_data['type'] == 'st')
			{
				include_once './content/classes/class.ilLMObjectFactory.php';

				$st_obj = ilLMObjectFactory::getInstance($tmp_lm,$lm_ass_data['obj_id']);
				$title .= (" -> ".$st_obj->getTitle());
			}

			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL",ilUtil::switchColor(++$counter,"tblrow2","tblrow1"));
			$tpl->setVariable("TITLE",$title);
			$tpl->setVariable("DESCRIPTION",$tmp_lm->getDescription());
			$tpl->parseCurrentBlock();
		}

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));

		// Show action row
		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'listAssignedLM');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('cancel'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'deleteLM');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('crs_lm_deassign'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",1);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->parseCurrentBlock();

		$tpl->setVariable("WIDTH","width=\"50%\"");

		// create table
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','std');
		
			
		// title & header columns
		$objective_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);
		$header_title = $this->lng->txt("crs_objective")." (".$objective_obj->getTitle().')';

		$tbl->setTitle($header_title,"icon_crs.gif",$this->lng->txt("crs_objectives"));

		$tbl->setHeaderNames(array($this->lng->txt("title")));
		$tbl->setHeaderVars(array("title"), 
							array("ref_id" => $this->course_obj->getRefId(),
								  "cmdClass" => "ilcourseobjectivesgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("100%"));

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($objectives));


		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("OBJECTIVES_TABLE", $tpl->get());

		// Save marked objectives
		$_SESSION['crs_delete_lm'] = $_POST['lm'];

		return true;


	}

	function deleteLM()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}
		if(!count($_SESSION['crs_delete_lm']))
		{
			ilUtil::sendInfo('No lm selected');
			$this->listAssignedLM();

			return false;
		}

		$this->__initLMObject((int) $_GET['objective_id']);

		foreach($_SESSION['crs_delete_lm'] as $lm_ass_id)
		{
			$this->objectives_lm_obj->delete($lm_ass_id);
		}
		ilUtil::sendInfo($this->lng->txt('crs_lm_assignment_deleted'));
		$this->listAssignedLM();

		return true;
	}



	function assignLMSelect()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}
		if(!count($all_lms = $this->__getAllLMs()))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_lms_found'));
			$this->listAssignedLM();

			return false;
		}
		$this->ctrl->setParameter($this,'objective_id',(int) $_GET['objective_id']);
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_objectives_lm_select.html",'Modules/Course');
		$this->__showButton('listAssignedLM',$this->lng->txt('back'));


		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_objectives_lm_select_row.html",'Modules/Course');

		$counter = 0;
		foreach($all_lms as $item)
		{
			++$counter;

			$tmp_lm =& ilObjectFactory::getInstanceByRefId($item);

			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL",ilUtil::switchColor($counter,"tblrow2","tblrow1"));
			$tpl->setVariable("CHECK_OBJECTIVE",ilUtil::formCheckbox(0,'lm[]',$item));

			$tpl->setVariable("IMG_TYPE",ilUtil::getImagePath('icon_'.$tmp_lm->getType().'.gif'));
			$tpl->setVariable("IMG_ALT",$this->lng->txt('obj_'.$tmp_lm->getType()));
			$tpl->setVariable("TITLE",$tmp_lm->getTitle());
			$tpl->setVariable("DESCRIPTION",$tmp_lm->getDescription());
			$tpl->parseCurrentBlock();

			unset($tmp_lm);
		}

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));

		// Show action row
		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'assignLM');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('crs_objective_assign_lm'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'assignChapterSelect');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('crs_objective_assign_chapter'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",3);
		$tpl->setVariable("WIDTH","width=\"50%\"");
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->parseCurrentBlock();


		// create table
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','std');

		// title & header columns
		$objectives_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);
		$header_title = $this->lng->txt("crs_objectives_lm_assignment").' ('.$objectives_obj->getTitle().')';

		$tbl->setTitle($header_title,"icon_crs.gif",$this->lng->txt("crs_objectives"));

		$tbl->setHeaderNames(array('',$this->lng->txt('type'),$this->lng->txt("title")));
		$tbl->setHeaderVars(array("","type","title"), 
							array("ref_id" => $this->course_obj->getRefId(),
								  "objective_id" => (int) $_GET['objective_id'],
								  "cmdClass" => "ilcourseobjectivesgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("1%","1%",'98%'));

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($objectives));

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("OBJECTIVES_TABLE", $tpl->get());

		return true;
	}

	function assignChapterSelect()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(count($_POST['lm']) !== 1)
		{
			ilUtil::sendInfo($this->lng->txt('crs_select_exactly_one_lm'));
			$this->assignLMSelect();

			return false;
		}
		foreach($_POST['lm'] as $lm_id)
		{
			$tmp_lm =& ilObjectFactory::getInstanceByRefId($lm_id);
			if($tmp_lm->getType() != 'lm')
			{
				ilUtil::sendInfo($this->lng->txt('crs_select_native_lm'));
				$this->assignLMSelect();
				
				return false;
			}
		}
		$lm_id = (int) $_POST['lm'][0];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_objectives_chapter_select.html",'Modules/Course');
		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_objectives_chapter_select_row.html",'Modules/Course');

		$this->ctrl->setParameter($this,'objective_id',(int) $_GET['objective_id']);
		$this->ctrl->setParameter($this,'lm_id',(int) $lm_id);
		$this->__showButton('assignLMSelect',$this->lng->txt('back'));
		
		$lm_obj =& ilObjectFactory::getInstanceByRefId($lm_id);

		$counter = 0;
		foreach($this->__getAllChapters($lm_id) as $chapter)
		{
			++$counter;
			include_once './content/classes/class.ilLMObjectFactory.php';

			$st_obj = ilLMObjectFactory::getInstance($lm_obj,$chapter);
			
			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL",ilUtil::switchColor($counter,"tblrow2","tblrow1"));
			$tpl->setVariable("TITLE",$st_obj->getTitle());
			$tpl->setVariable("CHECK_OBJECTIVE",ilUtil::formCheckbox(0,'chapter[]',$st_obj->getId()));
			$tpl->parseCurrentBlock();
		}


		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));

		// Show action row
		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'assignLMChapter');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('crs_objectives_assign_chapter'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("WIDTH","width=\"50%\"");
		$tpl->setVariable("COLUMN_COUNTS",2);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->parseCurrentBlock();


		// create table
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','std');
		

		// title & header columns
		$objectives_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);
		$header_title = $this->lng->txt("crs_objectives_chapter_assignment").' ('.$objectives_obj->getTitle().')';

		$tbl->setTitle($header_title,"icon_crs.gif",$this->lng->txt("crs_objectives"));

		$tbl->setHeaderNames(array('',$this->lng->txt("title")));
		$tbl->setHeaderVars(array("type","title"), 
							array("ref_id" => $this->course_obj->getRefId(),
								  "cmdClass" => "ilcourseobjectivesgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("1%","99%"));

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($objectives));

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("OBJECTIVES_TABLE", $tpl->get());
		
		return true;
	}

	function assignLMChapter()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}
		if(!count($_POST['chapter']))
		{
			$_POST['lm'] = array((int) $_GET['lm_id']);
			ilUtil::sendInfo($this->lng->txt('crs_no_chapter_selected'));
			$this->assignChapterSelect();

			return false;
		}

		$this->__initLMObject((int) $_GET['objective_id']);

		$counter = 0;
		foreach($_POST['chapter'] as $chapter_id)
		{
			$tmp_lm =& ilObjectFactory::getInstanceByRefId((int) $_GET['lm_id']);

			$this->objectives_lm_obj->setType('st');
			$this->objectives_lm_obj->setLMRefId($tmp_lm->getRefId());
			$this->objectives_lm_obj->setLMObjId($chapter_id);
			
			if($this->objectives_lm_obj->checkExists())
			{
				continue;
			}
			
			$this->objectives_lm_obj->add();
			++$counter;
		}

		if($counter)
		{
			ilUtil::sendInfo($this->lng->txt('crs_objectives_assigned_lm'));
			$this->listAssignedLM();
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt('crs_chapter_already_assigned'));
			$this->assignLMSelect();
		}

		return true;
	}

	function assignLM()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}
		if(!count($_POST['lm']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_lm_selected'));
			$this->assignLMSelect();

			return false;
		}

		$this->__initLMObject((int) $_GET['objective_id']);

		$counter = 0;
		foreach($_POST['lm'] as $lm_id)
		{
			$tmp_lm =& ilObjectFactory::getInstanceByRefId($lm_id);

			$this->objectives_lm_obj->setType($tmp_lm->getType());
			$this->objectives_lm_obj->setLMRefId($tmp_lm->getRefId());
			$this->objectives_lm_obj->setLMObjId($tmp_lm->getId());
			
			if($this->objectives_lm_obj->checkExists())
			{
				continue;
			}
			
			$this->objectives_lm_obj->add();
			++$counter;
		}

		if($counter)
		{
			ilUtil::sendInfo($this->lng->txt('crs_objectives_assigned_lm'));
			$this->listAssignedLM();
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt('crs_lms_already_assigned'));
			$this->assignLMSelect();
		}

		return true;
	}

	function listObjectives()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_objectives.html",'Modules/Course');
		if(!count($objectives = ilCourseObjective::_getObjectiveIds($this->course_obj->getId())))
		{
			$this->__showButton('addObjective',$this->lng->txt('crs_add_objective'));
			ilUtil::sendInfo($this->lng->txt('crs_no_objectives_created'));
			
			return true;
		}
		#else
		#{
		#	$this->__showButton('editQuestionAssignment',$this->lng->txt('crs_objective_overview_question_assignment'));
		#}

		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_objectives_row.html",'Modules/Course');

		$counter = 0;
		foreach($objectives as $objective)
		{
			++$counter;
			$objective_obj =& $this->__initObjectivesObject($objective);

			// Up down links
			if(count($objectives) > 1)
			{
				if($counter == 1)
				{
					$tpl->setVariable("NO_IMG_PRE_TYPE",ilUtil::getImagePath('empty.gif'));
				}					
				if($counter > 1) 
				{
					$tpl->setCurrentBlock("img");
					$this->ctrl->setParameter($this,'objective_id',$objective_obj->getObjectiveId());
					$tpl->setVariable("IMG_LINK",$this->ctrl->getLinkTarget($this,'moveObjectiveUp'));
					$tpl->setVariable("IMG_TYPE",ilUtil::getImagePath('a_up.gif'));
					$tpl->setVariable("IMG_ALT",$this->lng->txt('crs_move_up'));
					$tpl->parseCurrentBlock();
				}
				if($counter < count($objectives))
				{
					$tpl->setCurrentBlock("img");
					$this->ctrl->setParameter($this,'objective_id',$objective_obj->getObjectiveId());
					$tpl->setVariable("IMG_LINK",$this->ctrl->getLinkTarget($this,'moveObjectiveDown'));
					$tpl->setVariable("IMG_TYPE",ilUtil::getImagePath('a_down.gif'));
					$tpl->setVariable("IMG_ALT",$this->lng->txt('crs_move_down'));
					$tpl->parseCurrentBlock();
				}
				if($counter == count($objectives))
				{
					$tpl->setCurrentBlock("no_img_post");
					$tpl->setVariable("NO_IMG_POST_TYPE",ilUtil::getImagePath('empty.gif'));
					$tpl->parseCurrentBlock();
				}					

			}
			// Edit link
			$tpl->setCurrentBlock("edit_img");
			$this->ctrl->setParameter($this,'objective_id',$objective_obj->getObjectiveId());
			$tpl->setVariable("EDIT_IMG_LINK",$this->ctrl->getLinkTarget($this,'editObjective'));
			$tpl->setVariable("EDIT_IMG_TYPE",ilUtil::getImagePath('edit.gif'));
			$tpl->setVariable("EDIT_IMG_ALT",$this->lng->txt('edit'));
			$tpl->parseCurrentBlock();
			
			// Assign lm
			$tpl->setCurrentBlock("edit_img");
			$this->ctrl->setParameter($this,'objective_id',$objective_obj->getObjectiveId());
			$tpl->setVariable("EDIT_IMG_LINK",$this->ctrl->getLinkTarget($this,'listAssignedLM'));
			$tpl->setVariable("EDIT_IMG_TYPE",ilUtil::getImagePath('icon_lm.gif'));
			$tpl->setVariable("EDIT_IMG_ALT",$this->lng->txt('crs_lm_assignment'));
			$tpl->parseCurrentBlock();

			// Assign questions
			$tpl->setCurrentBlock("edit_img");
			$this->ctrl->setParameter($this,'objective_id',$objective_obj->getObjectiveId());
			$tpl->setVariable("EDIT_IMG_LINK",$this->ctrl->getLinkTarget($this,'listAssignedQuestions'));
			$tpl->setVariable("EDIT_IMG_TYPE",ilUtil::getImagePath('icon_tst.gif'));
			$tpl->setVariable("EDIT_IMG_ALT",$this->lng->txt('crs_question_assignment'));
			$tpl->parseCurrentBlock();

			$tpl->setCurrentBlock("options");
			$tpl->setVariable("OPT_ROWCOL",ilUtil::switchColor($counter,"tblrow2","tblrow1"));


			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL",ilUtil::switchColor($counter,"tblrow2","tblrow1"));
			$tpl->setVariable("CHECK_OBJECTIVE",ilUtil::formCheckbox(0,'objective[]',$objective_obj->getObjectiveId()));
			$tpl->setVariable("TITLE",$objective_obj->getTitle());
			$tpl->setVariable("DESCRIPTION",$objective_obj->getDescription());
			$tpl->parseCurrentBlock();
		}

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));

		// Show action row
		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'askDeleteObjective');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('delete'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("plain_button");
		$tpl->setVariable("PBTN_NAME",'addObjective');
		$tpl->setVariable("PBTN_VALUE",$this->lng->txt('crs_add_objective'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",3);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->parseCurrentBlock();


		// create table
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','std');

		// title & header columns
		$tbl->setTitle($this->lng->txt("crs_objectives"),"icon_crs.gif",$this->lng->txt("crs_objectives"));

		$tbl->setHeaderNames(array('',$this->lng->txt("title"),$this->lng->txt('options')));
		$tbl->setHeaderVars(array("type","title",'options'), 
							array("ref_id" => $this->course_obj->getRefId(),
								  "cmdClass" => "ilcourseobjectivesgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("1%","80%",'20%'));

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($objectives));

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("OBJECTIVES_TABLE", $tpl->get());
		

		return true;
	}
	function moveObjectiveUp()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!$_GET['objective_id'])
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();
			
			return true;
		}
		$objective_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);

		$objective_obj->moveUp((int) $_GET['objective_id']);
		ilUtil::sendInfo($this->lng->txt('crs_moved_objective'));

		$this->listObjectives();

		return true;
	}
	function moveObjectiveDown()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!$_GET['objective_id'])
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();
			
			return true;
		}
		$objective_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);

		$objective_obj->moveDown((int) $_GET['objective_id']);
		ilUtil::sendInfo($this->lng->txt('crs_moved_objective'));

		$this->listObjectives();

		return true;
	}


	function addObjective()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		$this->tpl->addBlockFile("ADM_CONTENT","adm_content","tpl.crs_add_objective.html",'Modules/Course');

		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_HEADER",$this->lng->txt('crs_add_objective'));
		$this->tpl->setVariable("TXT_TITLE",$this->lng->txt('title'));
		$this->tpl->setVariable("TXT_DESC",$this->lng->txt('description'));
		$this->tpl->setVariable("TXT_REQUIRED_FLD",$this->lng->txt('required'));
		$this->tpl->setVariable("CMD_SUBMIT",'saveObjective');
		$this->tpl->setVariable("TXT_SUBMIT",$this->lng->txt('add'));
		$this->tpl->setVariable("TXT_CANCEL",$this->lng->txt('cancel'));

		return true;
	}

	function editObjective()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}

		$this->tpl->addBlockFile("ADM_CONTENT","adm_content","tpl.crs_add_objective.html",'Modules/Course');

		$this->ctrl->setParameter($this,'objective_id',(int) $_GET['objective_id']);
		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_HEADER",$this->lng->txt('crs_update_objective'));
		$this->tpl->setVariable("TXT_TITLE",$this->lng->txt('title'));
		$this->tpl->setVariable("TXT_DESC",$this->lng->txt('description'));
		$this->tpl->setVariable("TXT_REQUIRED_FLD",$this->lng->txt('required'));
		$this->tpl->setVariable("CMD_SUBMIT",'updateObjective');
		$this->tpl->setVariable("TXT_SUBMIT",$this->lng->txt('save'));
		$this->tpl->setVariable("TXT_CANCEL",$this->lng->txt('cancel'));

		$objective_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);

		$this->tpl->setVariable("TITLE",$objective_obj->getTitle());
		$this->tpl->setVariable("DESC",$objective_obj->getDescription());

		return true;
	}

	function updateObjective()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{		
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}
		if(!$_POST['objective']['title'])
		{		
			ilUtil::sendInfo($this->lng->txt('crs_objective_no_title_given'));
			$this->editObjective();
			
			return false;
		}


		$objective_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);

		$objective_obj->setObjectiveId((int) $_GET['objective_id']);
		$objective_obj->setTitle(ilUtil::stripSlashes($_POST['objective']['title']));
		$objective_obj->setDescription(ilUtil::stripSlashes($_POST['objective']['description']));

		$objective_obj->update();
		
		ilUtil::sendInfo($this->lng->txt('crs_objective_modified'));
		$this->listObjectives();

		return true;
	}


	function askDeleteObjective()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!count($_POST['objective']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();
			
			return true;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_objectives.html",'Modules/Course');

		ilUtil::sendInfo($this->lng->txt('crs_delete_objectve_sure'));

		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_objectives_delete_row.html",'Modules/Course');

		$counter = 0;
		foreach($_POST['objective'] as $objective_id)
		{
			$objective_obj =& $this->__initObjectivesObject($objective_id);

			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL",ilUtil::switchColor(++$counter,"tblrow2","tblrow1"));
			$tpl->setVariable("TITLE",$objective_obj->getTitle());
			$tpl->setVariable("DESCRIPTION",$objective_obj->getDescription());
			$tpl->parseCurrentBlock();
		}

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));

		// Show action row
		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'listObjectives');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('cancel'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'deleteObjectives');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('delete'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",1);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->parseCurrentBlock();


		// create table
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','std');

		// title & header columns
		$tbl->setTitle($this->lng->txt("crs_objectives"),"icon_crs.gif",$this->lng->txt("crs_objectives"));

		$tbl->setHeaderNames(array($this->lng->txt("title")));
		$tbl->setHeaderVars(array("title"), 
							array("ref_id" => $this->course_obj->getRefId(),
								  "cmdClass" => "ilcourseobjectivesgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("50%"));

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($objectives));

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("OBJECTIVES_TABLE", $tpl->get());
		

		// Save marked objectives
		$_SESSION['crs_delete_objectives'] = $_POST['objective'];

		return true;
	}

	function deleteObjectives()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!count($_SESSION['crs_delete_objectives']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();
			
			return true;
		}

		foreach($_SESSION['crs_delete_objectives'] as $objective_id)
		{
			$objective_obj =& $this->__initObjectivesObject($objective_id);
			$objective_obj->delete();
		}

		ilUtil::sendInfo($this->lng->txt('crs_objectives_deleted'));
		$this->listObjectives();

		return true;
	}


	function saveObjective()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!$_POST['objective']['title'])
		{
			ilUtil::sendInfo('crs_no_title_given',true);

			$this->addObjective();
			return false;
		}

		$objective_obj =& $this->__initObjectivesObject();

		$objective_obj->setTitle(ilUtil::stripSlashes($_POST['objective']['title']));
		$objective_obj->setDescription(ilUtil::stripSlashes($_POST['objective']['description']));
		$objective_obj->add();
		
		ilUtil::sendInfo($this->lng->txt('crs_added_objective'));
		$this->listObjectives();

		return true;
	}

	// Question assignment
	function listAssignedQuestions()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}

		$this->ctrl->setParameter($this,'objective_id',(int) $_GET['objective_id']);
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_objectives_list_qst.html",'Modules/Course');

		if(!count($this->__getAllTests()))
		{
			#$this->__showButton('listObjectives',$this->lng->txt('crs_objective_overview_objectives'));
			ilUtil::sendInfo($this->lng->txt('crs_no_tests_inside_crs'));
			
			return true;
		}

		$this->__initQuestionObject((int) $_GET['objective_id']);
		if(!count($questions = $this->objectives_qst_obj->getQuestions()))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_questions_assigned'));
			#$this->__showButton('listObjectives',$this->lng->txt('crs_objective_overview_objectives'));
			$this->__showButton('assignTestSelect',$this->lng->txt('crs_objective_assign_question'));

			return true;
		}

		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_objectives_list_qst_row.html",'Modules/Course');

		#$this->__showButton('listObjectives',$this->lng->txt('crs_objective_overview_objectives'));

		$counter = 0;
		foreach($this->__sortQuestions($questions) as $question)
		{
			++$counter;

			include_once './Modules/Test/classes/class.ilObjTest.php';

			$tmp_question =& ilObjTest::_instanciateQuestion($question['question_id']);

			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL",ilUtil::switchColor($counter,"tblrow2","tblrow1"));
			$tpl->setVariable("CHECK_OBJECTIVE",ilUtil::formCheckbox(0,'question[]',$question['qst_ass_id']));
			$tpl->setVariable("TITLE",$tmp_question->getTitle());
			$tpl->setVariable("DESCRIPTION",$tmp_question->getComment());
			$tpl->parseCurrentBlock();

			unset($tmp_question);
		}

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		// Show action row

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'askDeassignQuestion');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('crs_objective_deassign_question'));
		$tpl->parseCurrentBlock();

		// Show add button
		$tpl->setCurrentBlock("plain_button");
		$tpl->setVariable("PBTN_NAME",'assignTestSelect');
		$tpl->setVariable("PBTN_VALUE",$this->lng->txt('crs_objective_assign_question'));
		$tpl->parseCurrentBlock();


		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",2);
		$tpl->setVariable("WIDTH","width=\"50%\"");
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->parseCurrentBlock();

		// create table
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','std');

		// title & header columns
		$objectives_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);
		$header_title = $this->lng->txt("crs_objectives_assigned_questions").' ('.$objectives_obj->getTitle().')';

		$tbl->setTitle($header_title,"icon_crs.gif",$this->lng->txt("crs_objectives"));

		$tbl->setHeaderNames(array('',$this->lng->txt("title")));
		$tbl->setHeaderVars(array("","title"), 
							array("ref_id" => $this->course_obj->getRefId(),
								  "objective_id" => (int) $_GET['objective_id'],
								  "cmdClass" => "ilcourseobjectivesgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("1%","99%"));

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($objectives));

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("OBJECTIVES_TABLE", $tpl->get());

		return true;
	}

	function askDeassignQuestion()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}
		if(!count($_POST['question']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_objectives_no_question_selected'));
			$this->listAssignedQuestions();

			return false;
		}

		$this->ctrl->setParameter($this,'objective_id',(int) $_GET['objective_id']);
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_objectives_deassign_qst.html",'Modules/Course');


		$this->__initQuestionObject((int) $_GET['objective_id']);

		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_objectives_deassign_qst_row.html",'Modules/Course');

		// Send info
		ilUtil::sendInfo($this->lng->txt('crs_objectives_deassign_question_sure'));

		$counter = 0;
		foreach($_POST['question'] as $qid)
		{
			++$counter;

			include_once './Modules/Test/classes/class.ilObjTest.php';

			$question = $this->objectives_qst_obj->getQuestion($qid);
			$tmp_question =& ilObjTest::_instanciateQuestion($question['question_id']);

			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL",ilUtil::switchColor($counter,"tblrow2","tblrow1"));
			$tpl->setVariable("TITLE",$tmp_question->getTitle());
			$tpl->setVariable("DESCRIPTION",$tmp_question->getComment());
			$tpl->parseCurrentBlock();

			unset($tmp_question);
		}

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));

		// Show add button
		$tpl->setCurrentBlock("plain_button");
		$tpl->setVariable("PBTN_NAME",'listAssignedQuestions');
		$tpl->setVariable("PBTN_VALUE",$this->lng->txt('cancel'));
		$tpl->parseCurrentBlock();

		// Show add button
		$tpl->setCurrentBlock("plain_button");
		$tpl->setVariable("PBTN_NAME",'deassignQuestion');
		$tpl->setVariable("PBTN_VALUE",$this->lng->txt('crs_objective_deassign_question'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",1);
		$tpl->setVariable("WIDTH","width=\"50%\"");
		$tpl->parseCurrentBlock();

		// create table
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','std');

		// title & header columns
		$objectives_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);
		$header_title = $this->lng->txt("crs_objectives_assigned_questions").' ('.$objectives_obj->getTitle().')';

		$tbl->setTitle($header_title,"icon_crs.gif",$this->lng->txt("crs_objectives"));

		$tbl->setHeaderNames(array($this->lng->txt("title")));
		$tbl->setHeaderVars(array("title"), 
							array("ref_id" => $this->course_obj->getRefId(),
								  "objective_id" => (int) $_GET['objective_id'],
								  "cmdClass" => "ilcourseobjectivesgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("100%"));

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($objectives));

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("OBJECTIVES_TABLE", $tpl->get());

		$_SESSION['crs_objectives_qst'] = $_POST['question'];

		return true;
	}

	function deassignQuestion()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}
		if(!count($_SESSION['crs_objectives_qst']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_objectives_no_question_selected'));
			$this->listAssignedQuestions();

			return false;
		}
		
		$this->__initQuestionObject((int) $_GET['objective_id']);

		foreach($_SESSION['crs_objectives_qst'] as $qid)
		{
			$this->objectives_qst_obj->delete($qid);
		}
		unset($_SESSION['crs_objectives_qst']);

		ilUtil::sendInfo($this->lng->txt('crs_objectives_qst_deassigned'));
		$this->listAssignedQuestions();

		return true;
	}
		


	function assignTestSelect()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}
		if(!count($all_tests = $this->__getAllTests()))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_tests_found'));
			$this->listAssignedQuestions();

			return false;
		}
		$this->ctrl->setParameter($this,'objective_id',(int) $_GET['objective_id']);
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_objectives_tst_select.html",'Modules/Course');
		$this->__showButton('listAssignedQuestions',$this->lng->txt('back'));


		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_objectives_tst_select_row.html",'Modules/Course');

		$counter = 0;
		foreach($all_tests as $item)
		{
			++$counter;

			$tmp_tst =& ilObjectFactory::getInstanceByRefId($item);

			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL",ilUtil::switchColor($counter,"tblrow2","tblrow1"));

			$tpl->setVariable("IMG_TYPE",ilUtil::getImagePath('icon_'.$tmp_tst->getType().'.gif'));
			$tpl->setVariable("IMG_ALT",$this->lng->txt('obj_'.$tmp_tst->getType()));
			$tpl->setVariable("TITLE",$tmp_tst->getTitle());
			$tpl->setVariable("DESCRIPTION",$tmp_tst->getDescription());

			// Get status info
			if($tmp_tst->isRandomTest())
			{
				$tpl->setVariable("STATUS",$this->lng->txt('crs_test_status_random'));
				$tpl->setVariable("CHECK_OBJECTIVE",'&nbsp;');
			}
			elseif(!$tmp_tst->isComplete())
			{
				$tpl->setVariable("STATUS",$this->lng->txt('crs_test_status_not_complete'));
				$tpl->setVariable("CHECK_OBJECTIVE",'&nbsp;');
			}
			else
			{
				$tpl->setVariable("STATUS",$this->lng->txt('crs_test_status_complete'));
				$tpl->setVariable("CHECK_OBJECTIVE",ilUtil::formRadioButton(0,'test_id',$item));
			}

			$tpl->setVariable("COUNT_QUESTIONS",count($tmp_tst->getExistingQuestions()));
			$tpl->parseCurrentBlock();

			unset($tmp_tst);
		}

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));

		// Show action row
		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",'assignQuestionSelect');
		$tpl->setVariable("BTN_VALUE",$this->lng->txt('crs_objective_assign_question'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",4);
		$tpl->setVariable("WIDTH","width=\"75%\"");
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->parseCurrentBlock();


		// create table
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','std');

		// title & header columns
		$objectives_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);
		$header_title = $this->lng->txt("crs_objective_question_assignment").' ('.$objectives_obj->getTitle().')';

		$tbl->setTitle($header_title,"icon_crs.gif",$this->lng->txt("crs_objectives"));

		$tbl->setHeaderNames(array('',$this->lng->txt("title"),$this->lng->txt('status'),$this->lng->txt('crs_count_questions')));
		$tbl->setHeaderVars(array("","title",'status','nr_questions'), 
							array("ref_id" => $this->course_obj->getRefId(),
								  "objective_id" => (int) $_GET['objective_id'],
								  "cmdClass" => "ilcourseobjectivesgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("1%","50%",'30%','20%'));

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($objectives));

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("OBJECTIVES_TABLE", $tpl->get());

		return true;
	}

	function assignQuestionSelect()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}
		if(!$_POST['test_id'])
		{
			ilUtil::sendInfo($this->lng->txt('crs_select_exactly_one_tst'));
			$this->assignTestSelect();

			return false;
		}

		
		$this->ctrl->setParameter($this,'objective_id',(int) $_GET['objective_id']);
		$this->ctrl->setParameter($this,'test_id',(int) $_POST['test_id']);
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_objectives_question_select.html",'Modules/Course');
		$this->__showButton('assignTestSelect',$this->lng->txt('back'));


		$tpl =& new ilTemplate("tpl.table.html", true, true);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_objectives_question_select_row.html",'Modules/Course');

		if(!$tmp_tst =& ilObjectFactory::getInstanceByRefId((int) $_POST['test_id'],false))
		{
			ilUtil::sendInfo('Error: Test does not exist');
			$this->assignTestSelect();

			return false;
		}		

		$this->__initQuestionObject((int) $_GET['objective_id']);

		$counter = 0;
		$assignable = false;
		
		foreach($this->__sortQuestions($tmp_tst->getAllQuestions()) as $question_data)
		{
			++$counter;

			$tmp_question =& ilObjTest::_instanciateQuestion($question_data['question_id']);

			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL",ilUtil::switchColor($counter,"tblrow2","tblrow1"));
			$tpl->setVariable("CHECK_OBJECTIVE",ilUtil::formCheckbox(0,'question[]',$question_data['question_id']));
			$tpl->setVariable("TITLE",$tmp_question->getTitle());
			$tpl->setVariable("DESCRIPTION",$tmp_question->getComment());

			if(!$objective_id = ilCourseObjectiveQuestion::_isAssigned((int) $_GET['objective_id'],
																	   $tmp_tst->getRefId(),$question_data['question_id']))
			{
				$tpl->setVariable("CHECK_OBJECTIVE",ilUtil::formCheckbox(0,'question[]',$question_data['question_id']));
				$tpl->setVariable("ASSIGNED",$this->lng->txt('no'));
				
				$assignable = true;
			}
			else
			{
				$tmp_objective_obj =& $this->__initObjectivesObject($objective_id);
				
				#$assigned = $this->lng->txt('yes').' ('.$tmp_objective_obj->getTitle().')';
				$assigned = $this->lng->txt('yes');
				$tpl->setVariable("ASSIGNED",$assigned);
				$tpl->setVariable("CHECK_OBJECTIVE",'&nbsp;');
			}
			$tpl->parseCurrentBlock();

			unset($tmp_question);
		}

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->setVariable("WIDTH","width=\"50%\"");

		// Show action row

		if($assignable)
		{
			$tpl->setCurrentBlock("tbl_action_btn");
			$tpl->setVariable("BTN_NAME",'assignQuestion');
			$tpl->setVariable("BTN_VALUE",$this->lng->txt('crs_objective_assign_question'));
			$tpl->parseCurrentBlock();

			$tpl->setCurrentBlock("tbl_action_row");
			$tpl->setVariable("COLUMN_COUNTS",3);
			$tpl->setVariable("WIDTH","width=\"50%\"");
			$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath('arrow_downright.gif'));
			$tpl->parseCurrentBlock();
		}

		// create table
		$tbl = new ilTableGUI();
		$tbl->setStyle('table','std');

		// title & header columns
		$objectives_obj =& $this->__initObjectivesObject((int) $_GET['objective_id']);
		$header_title = $this->lng->txt("crs_objective_question_assignment").' ('.$objectives_obj->getTitle().')';

		$tbl->setTitle($header_title,"icon_crs.gif",$this->lng->txt("crs_objectives"));

		$tbl->setHeaderNames(array('',$this->lng->txt("title"),$this->lng->txt('assigned')));
		$tbl->setHeaderVars(array("","title",'assigned'), 
							array("ref_id" => $this->course_obj->getRefId(),
								  "objective_id" => (int) $_GET['objective_id'],
								  "cmdClass" => "ilcourseobjectivesgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("1%","60%",'50%'));

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($objectives));

		// footer
		$tbl->disable("footer");
		$tbl->disable('sort');

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("OBJECTIVES_TABLE", $tpl->get());

		return true;

	}

	function __sortQuestions($a_qst_ids)
	{
		return ilUtil::sortArray($a_qst_ids,'title','asc');
	}

	function assignQuestion()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!isset($_GET['objective_id']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_objective_selected'));
			$this->listObjectives();

			return false;
		}
		if(!$_GET['test_id'])
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_test_selected'));
			$this->assignTestSelect();

			return false;
		}
		if(!count($_POST['question']))
		{
			$_POST['test_id'] = $_GET['test_id'];
			ilUtil::sendInfo($this->lng->txt('crs_no_question_selected'));
			$this->assignQuestionSelect();

			return false;
		}

		$this->__initQuestionObject((int) $_GET['objective_id']);

		$tmp_test =& ilObjectFactory::getInstanceByRefId((int) $_GET['test_id'],false);

		$this->objectives_qst_obj->setTestRefId($tmp_test->getRefId());
		$this->objectives_qst_obj->setTestObjId($tmp_test->getId());

		$added = 0;
		foreach($_POST['question'] as $qid)
		{
			if((int) $_GET['objective_id'] == ilCourseObjectiveQuestion::_isAssigned((int) $_GET['objective_id'],
																					 $tmp_test->getRefId(),
																					 $qid))
			{
				continue;
			}
			$this->objectives_qst_obj->setQuestionId($qid);
			$this->objectives_qst_obj->add();

			++$added;
		}

		if($added)
		{
			ilUtil::sendInfo($this->lng->txt('crs_objectives_assigned_new_questions'));
			$this->listAssignedQuestions();

			return true;
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt('crs_objectives_questions_already_assigned'));
			$this->assignQuestionSelect();

			return false;
		}
		return false;
	}

	function editQuestionAssignment()
	{
		global $rbacsystem;

		$this->tabs_gui->setSubTabActive('crs_objective_overview_question_assignment');

		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.crs_objectives_edit_question_assignments.html','Modules/Course');

		#$this->__showButton('listObjectives',$this->lng->txt('crs_objective_overview_objectives'));

		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("CSS_TABLE",'fullwidth');
		$this->tpl->setVariable("WIDTH",'80%');
		$this->tpl->setVariable("COLUMN_COUNT",5);
		$this->tpl->setVariable("TBL_TITLE_IMG",ilUtil::getImagePath('icon_crs.gif'));
		$this->tpl->setVariable("TBL_TITLE_IMG_ALT",$this->lng->txt('obj_crs'));
		$this->tpl->setVariable("TBL_TITLE",$this->lng->txt('crs_objectives_edit_question_assignments'));
		
		$head_titles = array(array($this->lng->txt('title'),"35%"),
							 array($this->lng->txt('crs_objectives_nr_questions'),"10%"),
							 array($this->lng->txt('crs_objectives_max_points'),"10%"),
							 array($this->lng->txt('options'),"35%"));

		$counter = 0;
		foreach($head_titles as $title)
		{
			$this->tpl->setCurrentBlock("tbl_header_no_link");

			if(!$counter)
			{
				$this->tpl->setVariable("TBL_HEADER_COLSPAN",' colspan="2"');
				++$counter;
			}
			$this->tpl->setVariable("TBL_HEADER_CELL_NO_LINK",$title[0]);
			$this->tpl->setVariable("TBL_COLUMN_WIDTH_NO_LINK",$title[1]);
			$this->tpl->parseCurrentBlock();
		}

		foreach(ilCourseObjective::_getObjectiveIds($this->course_obj->getId()) as $objective_id)
		{
			$tmp_objective_obj =& $this->__initObjectivesObject($objective_id);
			
			$this->__initQuestionObject($objective_id);

			$counter = 1;
			foreach($this->objectives_qst_obj->getTests() as $test_data)
			{
				$show_buttons = true;

				$tmp_test =& ilObjectFactory::getInstanceByRefId($test_data['ref_id']);

				$this->tpl->setCurrentBlock("test_row");
				$this->tpl->setVariable("TEST_TITLE",$tmp_test->getTitle());
				$this->tpl->setVariable("TEST_QST",$this->objectives_qst_obj->getNumberOfQuestionsByTest($test_data['ref_id']));
				$this->tpl->setVariable("TEST_POINTS",$this->objectives_qst_obj->getMaxPointsByTest($test_data['ref_id']));

				// Options
				$this->tpl->setVariable("TXT_CHANGE_STATUS",$this->lng->txt('crs_change_status'));
				$this->tpl->setVariable("CHECK_CHANGE_STATUS",ilUtil::formCheckbox((int) $test_data['tst_status'],
																				   'test['.$test_data['test_objective_id'].'][status]'
																				   ,1));
				$this->tpl->setVariable("TXT_SUGGEST",$this->lng->txt('crs_suggest_lm'));
				$this->tpl->setVariable("SUGGEST_NAME",'test['.$test_data['test_objective_id'].'][limit]');
				$this->tpl->setVariable("SUGGEST_VALUE",(int) $test_data['tst_limit']);

				$this->tpl->parseCurrentBlock();



				++$counter;
			}
			$this->tpl->setCurrentBlock("objective_row");
			$this->tpl->setVariable("OBJ_TITLE",$tmp_objective_obj->getTitle());
			$this->tpl->setVariable("OBJ_DESCRIPTION",$tmp_objective_obj->getDescription());
			$this->tpl->setVariable("OBJ_QST",count($this->objectives_qst_obj->getQuestions()));
			$this->tpl->setVariable("OBJ_POINTS",$this->objectives_qst_obj->getMaxPointsByObjective());
			$this->tpl->setVariable("ROWSPAN",$counter);
			$this->tpl->parseCurrentBlock();
			
			// Options
			unset($tmp_objective_obj);
		}
		// Buttons
		if($show_buttons)
		{
			$this->tpl->setCurrentBlock("edit_footer");
			$this->tpl->setVariable("TXT_RESET",$this->lng->txt('reset'));
			$this->tpl->setVariable("TXT_UPDATE",$this->lng->txt('save'));
			$this->tpl->setVariable("CMD_UPDATE",'updateQuestionAssignment');
			$this->tpl->parseCurrentBlock();
		}
	}

	function updateQuestionAssignment()
	{
		global $rbacsystem;

		$this->tabs_gui->setSubTabActive('crs_objective_overview_question_assignment');


		// MINIMUM ACCESS LEVEL = 'write'
		if(!$rbacsystem->checkAccess("write", $this->course_obj->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilErr->MESSAGE);
		}
		if(!is_array($_POST['test']))
		{
			ilUtil::sendInfo('Internal error: CRSM learning objectives');
			$this->editQuestionAssignment();

			return false;
		}
		// Validate
		foreach($_POST['test'] as $test_obj_id => $data)
		{
			if(!preg_match('/1?[0-9][0-9]?/',$data['limit']) or 
			   $data['limit'] < 0 or 
			   $data['limit'] > 100)
			{
				ilUtil::sendInfo($this->lng->txt('crs_objective_insert_percent'));
				$this->editQuestionAssignment();

				return false;
			}
		}
		
		foreach($_POST['test'] as $test_obj_id => $data)
		{
			include_once './Modules/Course/classes/class.ilCourseObjectiveQuestion.php';

			$test_data = ilCourseObjectiveQuestion::_getTest($test_obj_id);

			$this->__initQuestionObject($test_data['objective_id']);
			$this->objectives_qst_obj->setTestStatus($data['status'] ? 1 : 0);
			$this->objectives_qst_obj->setTestSuggestedLimit($data['limit']);
			$this->objectives_qst_obj->updateTest($test_obj_id);
		}
		ilUtil::sendInfo($this->lng->txt('crs_objective_updated_test'));
		$this->editQuestionAssignment();

		return true;
	}
		

	// PRIVATE
	function __initCourseObject()
	{
		if(!$this->course_obj =& ilObjectFactory::getInstanceByRefId($this->course_id,false))
		{
			$this->ilErr->raiseError("ilCourseObjectivesGUI: cannot create course object",$this->ilErr->MESSAGE);
			exit;
		}
		// do i need members?
		$this->course_obj->initCourseMemberObject();

		return true;
	}

	function &__initObjectivesObject($a_id = 0)
	{
		return new ilCourseObjective($this->course_obj,$a_id);
	}

	function __initLMObject($a_objective_id = 0)
	{
		include_once './Modules/Course/classes/class.ilCourseObjectiveLM.php';

		$this->objectives_lm_obj =& new ilCourseObjectiveLM($a_objective_id);

		return true;
	}

	function __initQuestionObject($a_objective_id = 0)
	{
		include_once './Modules/Course/classes/class.ilCourseObjectiveQuestion.php';

		$this->objectives_qst_obj =& new ilCourseObjectiveQuestion($a_objective_id);

		return true;
	}

	function __showButton($a_cmd,$a_text,$a_target = '')
	{
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");
		
		// display button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK",$this->ctrl->getLinkTarget($this,$a_cmd));
		$this->tpl->setVariable("BTN_TXT",$a_text);

		if($a_target)
		{
			$this->tpl->setVariable("BTN_TARGET",$a_target);
		}

		$this->tpl->parseCurrentBlock();
	}

	function __getAllLMs()
	{
		global $tree;
		
		#foreach($tree->getSubTree($tree->getNodeData($this->course_obj->getRefId())) as $node)
		foreach($tree->getChilds($this->course_obj->getRefId()) as $node)
		{
			switch($node['type'])
			{
				case 'lm':
				case 'htlm':
				case 'sahs':
					$all_lms[] = $node['ref_id'];
					break;
			}
		}
		return $all_lms ? $all_lms : array();
	}

	function __getAllTests()
	{
		global $tree;
		
		#foreach($tree->getSubTree($tree->getNodeData($this->course_obj->getRefId())) as $node)
		foreach($tree->getChilds($this->course_obj->getRefId()) as $node)
		{
			switch($node['type'])
			{
				case 'tst':
					$all_tst[] = $node['ref_id'];
					break;
			}
		}
		return $all_tst ? $all_tst : array();
	}

	function __getAllChapters($a_ref_id)
	{
		$tmp_lm =& ilObjectFactory::getInstanceByRefId($a_ref_id);

		$tree = new ilTree($tmp_lm->getId());
		$tree->setTableNames('lm_tree','lm_data');
		$tree->setTreeTablePK("lm_id");

		foreach($tree->getSubTree($tree->getNodeData($tree->getRootId())) as $node)
		{
			if($node['type'] == 'st')
			{
				$chapter[] = $node['child'];
			}
		}

		return $chapter ? $chapter : array();
	}

	/**
	* set sub tabs
	*/
	function setSubTabs()
	{
		global $ilTabs;

		$ilTabs->addSubTabTarget("crs_objective_overview_objectives",
								 $this->ctrl->getLinkTarget($this, "listObjectives"),
								 array("listObjectives", "moveObjectiveUp", "moveObjectiveDown", "listAssignedLM"),
								 array(),
								 '',
								 true);
			
		$ilTabs->addSubTabTarget("crs_objective_overview_question_assignment",
								 $this->ctrl->getLinkTarget($this, "editQuestionAssignment"),
								 "editQuestionAssignment",
								 array(),
								 '',
								 false);

	}
}
?>