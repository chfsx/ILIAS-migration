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
* Class ilObjSurveyGUI
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version  $Id$
*
* @ilCtrl_Calls ilObjSurveyGUI: ilSurveyEvaluationGUI
* @ilCtrl_Calls ilObjSurveyGUI: ilSurveyExecutionGUI
* @ilCtrl_Calls ilObjSurveyGUI: ilMDEditorGUI, ilPermissionGUI
*
* @extends ilObjectGUI
* @package ilias-core
* @package survey
*/

include_once "./classes/class.ilObjectGUI.php";
include_once "./survey/classes/inc.SurveyConstants.php";

class ilObjSurveyGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjSurveyGUI()
	{
    global $lng, $ilCtrl;

		$this->type = "svy";
		$lng->loadLanguageModule("survey");
		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this, "ref_id");

		$this->ilObjectGUI("",$_GET["ref_id"], true, false);
	}
	
	function backToRepositoryObject()
	{
		include_once "./classes/class.ilUtil.php";
		$path = $this->tree->getPathFull($this->object->getRefID());
		ilUtil::redirect($this->getReturnLocation("cancel","./repository.php?cmd=frameset&ref_id=" . $path[count($path) - 2]["child"]));
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		$cmd = $this->ctrl->getCmd("properties");
		$next_class = $this->ctrl->getNextClass($this);
		$this->ctrl->setReturn($this, "properties");
		$this->prepareOutput();

		//echo "<br>nextclass:$next_class:cmd:$cmd:qtype=$q_type";
		switch($next_class)
		{
			case 'ilmdeditorgui':
				include_once 'Services/MetaData/classes/class.ilMDEditorGUI.php';
				$md_gui =& new ilMDEditorGUI($this->object->getId(), 0, $this->object->getType());
				$md_gui->addObserver($this->object,'MDUpdateListener','General');

				$this->ctrl->forwardCommand($md_gui);
				break;
			
			case "ilsurveyevaluationgui":
				include_once("./survey/classes/class.ilSurveyEvaluationGUI.php");
				$eval_gui = new ilSurveyEvaluationGUI($this->object);
				$ret =& $this->ctrl->forwardCommand($eval_gui);
				break;

			case "ilsurveyexecutiongui":
				include_once("./survey/classes/class.ilSurveyExecutionGUI.php");
				$exec_gui = new ilSurveyExecutionGUI($this->object);
				$ret =& $this->ctrl->forwardCommand($exec_gui);
				break;
				
			case 'ilpermissiongui':
				include_once("./classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			default:
				$cmd.= "Object";
				$ret =& $this->$cmd();
				break;
		}
		if (strtolower($_GET["baseClass"]) != "iladministrationgui" &&
			$this->getCreationMode() != true)
		{
			$this->tpl->show();
		}
	}

	/**
	* save object
	* @access	public
	*/
	function saveObject()
	{
		global $rbacadmin;

		// create and insert forum in objecttree
		$newObj = parent::saveObject();
		// always send a message
		sendInfo($this->lng->txt("object_added"),true);
		
		ilUtil::redirect("ilias.php?ref_id=".$newObj->getRefId().
			"&baseClass=ilObjSurveyGUI");
	}
	
	/**
	* cancel action and go back to previous page
	* @access	public
	*
	*/
	function cancelObject($in_rep = false)
	{
		sendInfo($this->lng->txt("msg_cancel"),true);
		ilUtil::redirect("repository.php?cmd=frameset&ref_id=".$_GET["ref_id"]);
	}

	/**
	* Cancel actions in the properties form
	*
	* Cancel actions in the properties form
	*
	* @access private
	*/
	function cancelPropertiesObject()
	{
    sendInfo($this->lng->txt("msg_cancel"), true);
		$this->ctrl->redirect($this, "properties");
	}
	
	/**
	* Save the survey properties
	*
	* Save the survey properties
	*
	* @access private
	*/
	function savePropertiesObject()
	{
		include_once "./classes/class.ilUtil.php";
		$this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
		$this->object->setDescription(ilUtil::stripSlashes($_POST["description"]));
		$this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
		$result = $this->object->setStatus($_POST["status"]);
		if ($result)
		{
			sendInfo($result, true);
		}
		$this->object->setEvaluationAccess($_POST["evaluation_access"]);
		$this->object->setStartDate(sprintf("%04d-%02d-%02d", $_POST["start_date"]["y"], $_POST["start_date"]["m"], $_POST["start_date"]["d"]));
		$this->object->setStartDateEnabled($_POST["checked_start_date"]);
		$this->object->setEndDate(sprintf("%04d-%02d-%02d", $_POST["end_date"]["y"], $_POST["end_date"]["m"], $_POST["end_date"]["d"]));
		$this->object->setEndDateEnabled($_POST["checked_end_date"]);
		$this->object->setIntroduction(ilUtil::stripSlashes($_POST["introduction"]));
		$this->object->setAnonymize($_POST["anonymize"]);
		if ($_POST["showQuestionTitles"])
		{
			$this->object->showQuestionTitles();
		}
		else
		{
			$this->object->hideQuestionTitles();
		}
		$this->update = $this->object->update();
		$this->object->saveToDb();
		if (strcmp($_SESSION["info"], "") != 0)
		{
			sendInfo($_SESSION["info"] . "<br />" . $this->lng->txt("msg_obj_modified"), true);
		}
		else
		{
			sendInfo($this->lng->txt("msg_obj_modified"), true);
		}
		$this->ctrl->redirect($this, "properties");
	}

/**
* Creates the properties form for the survey object
*
* Creates the properties form for the survey object
*
* @access public
*/
  function propertiesObject()
  {
		global $rbacsystem;

		include_once "./classes/class.ilUtil.php";
		$this->lng->loadLanguageModule("jscalendar");
		$this->tpl->addBlockFile("CALENDAR_LANG_JAVASCRIPT", "calendar_javascript", "tpl.calendar.html");
		$this->tpl->setCurrentBlock("calendar_javascript");
		$this->tpl->setVariable("FULL_SUNDAY", $this->lng->txt("l_su"));
		$this->tpl->setVariable("FULL_MONDAY", $this->lng->txt("l_mo"));
		$this->tpl->setVariable("FULL_TUESDAY", $this->lng->txt("l_tu"));
		$this->tpl->setVariable("FULL_WEDNESDAY", $this->lng->txt("l_we"));
		$this->tpl->setVariable("FULL_THURSDAY", $this->lng->txt("l_th"));
		$this->tpl->setVariable("FULL_FRIDAY", $this->lng->txt("l_fr"));
		$this->tpl->setVariable("FULL_SATURDAY", $this->lng->txt("l_sa"));
		$this->tpl->setVariable("SHORT_SUNDAY", $this->lng->txt("s_su"));
		$this->tpl->setVariable("SHORT_MONDAY", $this->lng->txt("s_mo"));
		$this->tpl->setVariable("SHORT_TUESDAY", $this->lng->txt("s_tu"));
		$this->tpl->setVariable("SHORT_WEDNESDAY", $this->lng->txt("s_we"));
		$this->tpl->setVariable("SHORT_THURSDAY", $this->lng->txt("s_th"));
		$this->tpl->setVariable("SHORT_FRIDAY", $this->lng->txt("s_fr"));
		$this->tpl->setVariable("SHORT_SATURDAY", $this->lng->txt("s_sa"));
		$this->tpl->setVariable("FULL_JANUARY", $this->lng->txt("l_01"));
		$this->tpl->setVariable("FULL_FEBRUARY", $this->lng->txt("l_02"));
		$this->tpl->setVariable("FULL_MARCH", $this->lng->txt("l_03"));
		$this->tpl->setVariable("FULL_APRIL", $this->lng->txt("l_04"));
		$this->tpl->setVariable("FULL_MAY", $this->lng->txt("l_05"));
		$this->tpl->setVariable("FULL_JUNE", $this->lng->txt("l_06"));
		$this->tpl->setVariable("FULL_JULY", $this->lng->txt("l_07"));
		$this->tpl->setVariable("FULL_AUGUST", $this->lng->txt("l_08"));
		$this->tpl->setVariable("FULL_SEPTEMBER", $this->lng->txt("l_09"));
		$this->tpl->setVariable("FULL_OCTOBER", $this->lng->txt("l_10"));
		$this->tpl->setVariable("FULL_NOVEMBER", $this->lng->txt("l_11"));
		$this->tpl->setVariable("FULL_DECEMBER", $this->lng->txt("l_12"));
		$this->tpl->setVariable("SHORT_JANUARY", $this->lng->txt("s_01"));
		$this->tpl->setVariable("SHORT_FEBRUARY", $this->lng->txt("s_02"));
		$this->tpl->setVariable("SHORT_MARCH", $this->lng->txt("s_03"));
		$this->tpl->setVariable("SHORT_APRIL", $this->lng->txt("s_04"));
		$this->tpl->setVariable("SHORT_MAY", $this->lng->txt("s_05"));
		$this->tpl->setVariable("SHORT_JUNE", $this->lng->txt("s_06"));
		$this->tpl->setVariable("SHORT_JULY", $this->lng->txt("s_07"));
		$this->tpl->setVariable("SHORT_AUGUST", $this->lng->txt("s_08"));
		$this->tpl->setVariable("SHORT_SEPTEMBER", $this->lng->txt("s_09"));
		$this->tpl->setVariable("SHORT_OCTOBER", $this->lng->txt("s_10"));
		$this->tpl->setVariable("SHORT_NOVEMBER", $this->lng->txt("s_11"));
		$this->tpl->setVariable("SHORT_DECEMBER", $this->lng->txt("s_12"));
		$this->tpl->setVariable("ABOUT_CALENDAR", $this->lng->txt("about_calendar"));
		$this->tpl->setVariable("ABOUT_CALENDAR_LONG", $this->lng->txt("about_calendar_long"));
		$this->tpl->setVariable("ABOUT_TIME_LONG", $this->lng->txt("about_time"));
		$this->tpl->setVariable("PREV_YEAR", $this->lng->txt("prev_year"));
		$this->tpl->setVariable("PREV_MONTH", $this->lng->txt("prev_month"));
		$this->tpl->setVariable("GO_TODAY", $this->lng->txt("go_today"));
		$this->tpl->setVariable("NEXT_MONTH", $this->lng->txt("next_month"));
		$this->tpl->setVariable("NEXT_YEAR", $this->lng->txt("next_year"));
		$this->tpl->setVariable("SEL_DATE", $this->lng->txt("select_date"));
		$this->tpl->setVariable("DRAG_TO_MOVE", $this->lng->txt("drag_to_move"));
		$this->tpl->setVariable("PART_TODAY", $this->lng->txt("part_today"));
		$this->tpl->setVariable("DAY_FIRST", $this->lng->txt("day_first"));
		$this->tpl->setVariable("CLOSE", $this->lng->txt("close"));
		$this->tpl->setVariable("TODAY", $this->lng->txt("today"));
		$this->tpl->setVariable("TIME_PART", $this->lng->txt("time_part"));
		$this->tpl->setVariable("DEF_DATE_FORMAT", $this->lng->txt("def_date_format"));
		$this->tpl->setVariable("TT_DATE_FORMAT", $this->lng->txt("tt_date_format"));
		$this->tpl->setVariable("WK", $this->lng->txt("wk"));
		$this->tpl->setVariable("TIME", $this->lng->txt("time"));
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("CalendarJS");
		$this->tpl->setVariable("LOCATION_JAVASCRIPT_CALENDAR", "./survey/js/calendar/calendar.js");
		$this->tpl->setVariable("LOCATION_JAVASCRIPT_CALENDAR_SETUP", "./survey/js/calendar/calendar-setup.js");
		$this->tpl->setVariable("LOCATION_JAVASCRIPT_CALENDAR_STYLESHEET", "./survey/js/calendar/calendar.css");
		$this->tpl->parseCurrentBlock();

		if ((!$rbacsystem->checkAccess("read", $this->ref_id)) && (!$rbacsystem->checkAccess("write", $this->ref_id))) 
		{
			// allow only read and write access
			sendInfo($this->lng->txt("cannot_edit_survey"), true);
			$path = $this->tree->getPathFull($this->object->getRefID());
			ilUtil::redirect($this->getReturnLocation("cancel","./repository.php?cmd=frameset&ref_id=" . $path[count($path) - 2]["child"]));
			return;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_properties.html", true);
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("VALUE_TITLE", ilUtil::prepareFormOutput($this->object->getTitle()));
		$this->tpl->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
		$this->tpl->setVariable("VALUE_AUTHOR", ilUtil::prepareFormOutput($this->object->getAuthor()));
		$this->tpl->setVariable("TEXT_DESCRIPTION", $this->lng->txt("description"));
		$this->tpl->setVariable("VALUE_DESCRIPTION", ilUtil::prepareFormOutput($this->object->getDescription()));
		$this->tpl->setVariable("TEXT_INTRODUCTION", $this->lng->txt("introduction"));
		$this->tpl->setVariable("VALUE_INTRODUCTION", ilUtil::prepareFormOutput($this->object->getIntroduction()));
		$this->tpl->setVariable("TEXT_STATUS", $this->lng->txt("status"));
		$this->tpl->setVariable("TEXT_START_DATE", $this->lng->txt("start_date"));
		$this->tpl->setVariable("VALUE_START_DATE", ilUtil::makeDateSelect("start_date", $this->object->getStartYear(), $this->object->getStartMonth(), $this->object->getStartDay()));
		$this->tpl->setVariable("IMG_START_DATE_CALENDAR", ilUtil::getImagePath("calendar.png"));
		$this->tpl->setVariable("TXT_START_DATE_CALENDAR", $this->lng->txt("open_calendar"));
		$this->tpl->setVariable("TEXT_END_DATE", $this->lng->txt("end_date"));
		$this->tpl->setVariable("VALUE_END_DATE", ilUtil::makeDateSelect("end_date", $this->object->getEndYear(), $this->object->getEndMonth(), $this->object->getEndDay()));
		$this->tpl->setVariable("IMG_END_DATE_CALENDAR", ilUtil::getImagePath("calendar.png"));
		$this->tpl->setVariable("TXT_END_DATE_CALENDAR", $this->lng->txt("open_calendar"));
		$this->tpl->setVariable("TEXT_EVALUATION_ACCESS", $this->lng->txt("evaluation_access"));
		$this->tpl->setVariable("VALUE_OFFLINE", $this->lng->txt("offline"));
		$this->tpl->setVariable("VALUE_ONLINE", $this->lng->txt("online"));
		$this->tpl->setVariable("TEXT_ENABLED", $this->lng->txt("enabled"));
		$this->tpl->setVariable("VALUE_OFF", $this->lng->txt("off"));
		$this->tpl->setVariable("VALUE_ALL", $this->lng->txt("evaluation_access_all"));
		$this->tpl->setVariable("VALUE_PARTICIPANTS", $this->lng->txt("evaluation_access_participants"));
		$this->tpl->setVariable("TEXT_ANONYMIZATION", $this->lng->txt("anonymize_survey"));
		$this->tpl->setVariable("TEXT_ANONYMIZATION_EXPLANATION", $this->lng->txt("anonymize_survey_explanation"));
		$this->tpl->setVariable("ANON_VALUE_OFF", $this->lng->txt("off"));
		$this->tpl->setVariable("ANON_VALUE_ON", $this->lng->txt("on"));
		
		if ($this->object->getAnonymize())
		{
			$this->tpl->setVariable("ANON_SELECTED_ON", " selected=\"selected\"");
		}
		else
		{
			$this->tpl->setVariable("ANON_SELECTED_OFF", " selected=\"selected\"");
		}
		
		if ($this->object->getEndDateEnabled())
		{
			$this->tpl->setVariable("CHECKED_END_DATE", " checked=\"checked\"");
		}
		if ($this->object->getStartDateEnabled())
		{
			$this->tpl->setVariable("CHECKED_START_DATE", " checked=\"checked\"");
		}
		switch ($this->object->getEvaluationAccess())
		{
			case EVALUATION_ACCESS_OFF:
				$this->tpl->setVariable("SELECTED_OFF", " selected=\"selected\"");
				break;
			case EVALUATION_ACCESS_ALL:
				$this->tpl->setVariable("SELECTED_ALL", " selected=\"selected\"");
				break;
			case EVALUATION_ACCESS_PARTICIPANTS:
				$this->tpl->setVariable("SELECTED_PARTICIPANTS", " selected=\"selected\"");
				break;
		}
		if ($this->object->getStatus() == STATUS_ONLINE)
		{
			$this->tpl->setVariable("SELECTED_ONLINE", " selected=\"selected\"");
		}
		else
		{
			$this->tpl->setVariable("SELECTED_OFFLINE", " selected=\"selected\"");
		}
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
    if ($rbacsystem->checkAccess("write", $this->ref_id)) {
			$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
			$this->tpl->setVariable("CANCEL", $this->lng->txt("cancel"));
		}
		$this->tpl->setVariable("TEXT_SHOW_QUESTIONTITLES", $this->lng->txt("svy_show_questiontitles"));
		if ($this->object->getShowQuestionTitles())
		{
			$this->tpl->setVariable("QUESTIONTITLES_CHECKED", " checked=\"checked\"");
		}
    $this->tpl->parseCurrentBlock();
  }

	/**
	* Called when the filter in the question browser is activated
	*
	* Called when the filter in the question browser is activated
	*
	* @access private
	*/
	function filterQuestionsObject()
	{
		$this->browseForQuestionsObject($_POST["sel_questionpool"]);
	}
	
	/**
	* Called when the filter in the question browser has been resetted
	*
	* Called when the filter in the question browser has been resetted
	*
	* @access private
	*/
	function resetFilterQuestionsObject()
	{
		$this->browseForQuestionsObject("", true);
	}
	
	/**
	* Change the object type in the question browser
	*
	* Change the object type in the question browser
	*
	* @access private
	*/
	function changeDatatypeObject()
	{
		$this->browseForQuestionsObject("", true, $_POST["datatype"]);
	}
	
	/**
	* Insert questions into the survey
	*
	* Insert questions into the survey
	*
	* @access private
	*/
	function insertQuestionsObject()
	{
		// insert selected questions into test
		$inserted_objects = 0;
		foreach ($_POST as $key => $value) 
		{
			if (preg_match("/cb_(\d+)/", $key, $matches)) 
			{
				if ($_GET["browsetype"] == 1)
				{
					$this->object->insertQuestion($matches[1]);
				}
				else
				{
					$this->object->insertQuestionBlock($matches[1]);
				}
				$inserted_objects++;
			}
		}
		if ($inserted_objects)
		{
			$this->object->saveCompletionStatus();
			sendInfo($this->lng->txt("questions_inserted"), true);
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			if ($_GET["browsetype"] == 1)
			{
				sendInfo($this->lng->txt("insert_missing_question"));
			}
			else
			{
				sendInfo($this->lng->txt("insert_missing_questionblock"));
			}
			$this->browseForQuestionsObject("", false, $_GET["browsetype"]);
		}
	}
	
	/**
	* Remove questions from the survey
	*
	* Remove questions from the survey
	*
	* @access private
	*/
	function removeQuestionsObject()
	{
		$checked_questions = array();
		$checked_questionblocks = array();
		foreach ($_POST as $key => $value) 
		{
			if (preg_match("/cb_(\d+)/", $key, $matches)) 
			{
				array_push($checked_questions, $matches[1]);
			}
			if (preg_match("/cb_qb_(\d+)/", $key, $matches))
			{
				array_push($checked_questionblocks, $matches[1]);
			}
		}
		if (count($checked_questions) + count($checked_questionblocks) > 0) 
		{
			sendInfo($this->lng->txt("remove_questions"));
			$this->removeQuestionsForm($checked_questions, $checked_questionblocks);
			return;
		} 
		else 
		{
			sendInfo($this->lng->txt("no_question_selected_for_removal"), true);
			$this->ctrl->redirect($this, "questions");
		}
	}
	
/**
* Creates the questionbrowser to select questions from question pools
*
* Creates the questionbrowser to select questions from question pools
*
* @access public
*/
	function browseForQuestionsObject($filter_questionpool = "", $reset_filter = false, $browsequestions = 1) 
	{
    global $rbacsystem;

		if (strcmp($this->ctrl->getCmd(), "filterQuestions") != 0)
		{
			if (array_key_exists("sel_questionpool", $_GET)) $filter_questionpool = $_GET["sel_questionpool"];
		}
		if (strcmp($this->ctrl->getCmd(), "changeDatatype") != 0)
		{
			if (array_key_exists("browsetype", $_GET))	$browsequestions = $_GET["browsetype"];
		}
		if ($_POST["cmd"]["back"]) {
			$show_questionbrowser = false;
		}
		
    $add_parameter = "&insert_question=1";

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_questionbrowser.html", true);
    $this->tpl->addBlockFile("A_BUTTONS", "a_buttons", "tpl.il_svy_svy_action_buttons.html", true);
    $this->tpl->addBlockFile("FILTER_QUESTION_MANAGER", "filter_questions", "tpl.il_svy_svy_filter_questions.html", true);

		$questionpools =& $this->object->getQuestionpoolTitles();

		$filter_type = $_GET["sel_filter_type"];
		if (!$filter_type)
		{
			$filter_type = $_POST["sel_filter_type"];
		}
		if ($reset_filter)
		{
			$filter_type = "";
		}
		$add_parameter .= "&sel_filter_type=$filter_type";

		$filter_text = $_GET["filter_text"];
		if (!$filter_text)
		{
			$filter_text = $_POST["filter_text"];
		}
		if ($reset_filter)
		{
			$filter_text = "";
		}
		$add_parameter .= "&filter_text=$filter_text";

		$add_parameter .= "&browsetype=$browsequestions";
		$filter_fields = array(
			"title" => $this->lng->txt("title"),
			"comment" => $this->lng->txt("description"),
			"author" => $this->lng->txt("author"),
		);
		$this->tpl->setCurrentBlock("filterrow");
		foreach ($filter_fields as $key => $value) 
		{
			$this->tpl->setVariable("VALUE_FILTER_TYPE", "$key");
			$this->tpl->setVariable("NAME_FILTER_TYPE", "$value");
			if (!$reset_filter) 
			{
				if (strcmp($filter_type, $key) == 0) 
				{
					$this->tpl->setVariable("VALUE_FILTER_SELECTED", " selected=\"selected\"");
				}
			}
			$this->tpl->parseCurrentBlock();
		}

		$filter_question_type = $_POST["sel_question_type"];
		if (!$filter_question_type)
		{
			$filter_question_type = $_GET["sel_question_type"];
		}
		if ($reset_filter)
		{
			$filter_question_type = "";
		}
		$add_parameter .= "&sel_question_type=$filter_question_type";

		if ($browsequestions)
		{
			$questiontypes =& $this->object->_getQuestiontypes();
			foreach ($questiontypes as $key => $value)
			{
				$this->tpl->setCurrentBlock("questiontype_row");
				$this->tpl->setVariable("VALUE_QUESTION_TYPE", $value);
				$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt($value));
				if (strcmp($filter_question_type, $value) == 0)
				{
					$this->tpl->setVariable("SELECTED_QUESTION_TYPE", " selected=\"selected\"");
				}
				$this->tpl->parseCurrentBlock();
			}
		}
		
		if ($reset_filter)
		{
			$filter_questionpool = "";
		}
		$add_parameter .= "&sel_questionpool=$filter_questionpool";
		
		if ($browsequestions)
		{
			foreach ($questionpools as $key => $value)
			{
				$this->tpl->setCurrentBlock("questionpool_row");
				$this->tpl->setVariable("VALUE_QUESTIONPOOL", $key);
				$this->tpl->setVariable("TEXT_QUESTIONPOOL", $value);
				if (strcmp($filter_questionpool, $key) == 0)
				{
					$this->tpl->setVariable("SELECTED_QUESTIONPOOL", " selected=\"selected\"");
				}
				$this->tpl->parseCurrentBlock();
			}
		}

		if ($browsequestions)
		{
			$this->tpl->setCurrentBlock("question_filters");
			$this->tpl->setVariable("SHOW_QUESTION_TYPES", $this->lng->txt("filter_show_question_types"));
			$this->tpl->setVariable("TEXT_ALL_QUESTION_TYPES", $this->lng->txt("filter_all_question_types"));
			$this->tpl->setVariable("SHOW_QUESTIONPOOLS", $this->lng->txt("filter_show_questionpools"));
			$this->tpl->setVariable("TEXT_ALL_QUESTIONPOOLS", $this->lng->txt("filter_all_questionpools"));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("filter_questions");
    $this->tpl->setVariable("FILTER_TEXT", $this->lng->txt("filter"));
    $this->tpl->setVariable("TEXT_FILTER_BY", $this->lng->txt("by"));
    if (!$_POST["cmd"]["reset"]) {
      $this->tpl->setVariable("VALUE_FILTER_TEXT", $filter_text);
    }
    $this->tpl->setVariable("VALUE_SUBMIT_FILTER", $this->lng->txt("set_filter"));
    $this->tpl->setVariable("VALUE_RESET_FILTER", $this->lng->txt("reset_filter"));
    $this->tpl->setVariable("OPTION_QUESTIONS", $this->lng->txt("questions"));
    $this->tpl->setVariable("OPTION_QUESTIONBLOCKS", $this->lng->txt("questionblocks"));
		if ($browsequestions)
		{
	    $this->tpl->setVariable("SELECTED_QUESTIONS", " selected=\"selected\"");
		}
		else
		{
	    $this->tpl->setVariable("SELECTED_QUESTIONBLOCKS", " selected=\"selected\"");
		}
    $this->tpl->setVariable("TEXT_DATATYPE", $this->lng->txt("display_all_available"));
		$this->tpl->setVariable("BTN_CHANGE", $this->lng->txt("change"));
    $this->tpl->parseCurrentBlock();

		if ($_POST["cmd"]["reset"])
		{
			$_POST["filter_text"] = "";
		}
		$startrow = 0;
		if ($_GET["prevrow"])
		{
			$startrow = $_GET["prevrow"];
		}
		if ($_GET["nextrow"])
		{
			$startrow = $_GET["nextrow"];
		}
		if ($_GET["startrow"])
		{
			$startrow = $_GET["startrow"];
		}
		if (!$_GET["sort"])
		{
			// default sort order
			$_GET["sort"] = array("title" => "ASC");
		}
		if ($browsequestions)
		{
			$table = $this->object->getQuestionsTable($_GET["sort"], $filter_text, $filter_type, $startrow, 1, $filter_question_type, $filter_questionpool);
		}
		else
		{
			$table = $this->object->getQuestionblocksTable($_GET["sort"], $filter_text, $filter_type, $startrow);
		}
    $colors = array("tblrow1", "tblrow2");
    $counter = 0;
		$questionblock_id = 0;
		if ($browsequestions)
		{
			include_once "./classes/class.ilFormat.php";
			foreach ($table["rows"] as $data)
			{
				if ($rbacsystem->checkAccess("write", $data["ref_id"])) {
					$this->tpl->setCurrentBlock("QTab");
					if ($data["complete"]) {
						// make only complete questions selectable
						$this->tpl->setVariable("QUESTION_ID", $data["question_id"]);
					}
					$this->tpl->setVariable("QUESTION_TITLE", "<strong>" . $data["title"] . "</strong>");
					$this->tpl->setVariable("PREVIEW", "[<a href=\"" . "ilias.php?baseClass=ilObjSurveyQuestionPoolGUI&ref_id=" . $data["ref_id"] . "&cmd=preview&preview=" . $data["question_id"] . " \" target=\"_blank\">" . $this->lng->txt("preview") . "</a>]");
					$this->tpl->setVariable("QUESTION_COMMENT", $data["description"]);
					$this->tpl->setVariable("QUESTION_TYPE", $this->lng->txt($data["type_tag"]));
					$this->tpl->setVariable("QUESTION_AUTHOR", $data["author"]);
					$this->tpl->setVariable("QUESTION_CREATED", ilFormat::formatDate(ilFormat::ftimestamp2dateDB($data["created"]), "date"));
					$this->tpl->setVariable("QUESTION_UPDATED", ilFormat::formatDate(ilFormat::ftimestamp2dateDB($data["TIMESTAMP14"]), "date"));
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->setVariable("QUESTION_POOL", $questionpools[$data["obj_fi"]]);
					$this->tpl->parseCurrentBlock();
					$counter++;
				}
			}
			if ($table["rowcount"] > count($table["rows"]))
			{
				$nextstep = $table["nextrow"] + $table["step"];
				if ($nextstep > $table["rowcount"])
				{
					$nextstep = $table["rowcount"];
				}
				$sort = "";
				if (is_array($_GET["sort"]))
				{
					$key = key($_GET["sort"]);
					$sort = "&sort[$key]=" . $_GET["sort"]["$key"];
				}
				$counter = 1;
				for ($i = 0; $i < $table["rowcount"]; $i += $table["step"])
				{
					$this->tpl->setCurrentBlock("pages_questions");
					if ($table["startrow"] == $i)
					{
						$this->tpl->setVariable("PAGE_NUMBER", "<span class=\"inactivepage\">$counter</span>");
					}
					else
					{
						$this->tpl->setVariable("PAGE_NUMBER", "<a href=\"" . $this->ctrl->getLinkTarget($this, "browseForQuestions") . $add_parameter . "$sort&nextrow=$i" . "\">$counter</a>");
					}
					$this->tpl->parseCurrentBlock();
					$counter++;
				}
				$this->tpl->setCurrentBlock("questions_navigation_bottom");
				$this->tpl->setVariable("TEXT_ITEM", $this->lng->txt("item"));
				$this->tpl->setVariable("TEXT_ITEM_START", $table["startrow"] + 1);
				$end = $table["startrow"] + $table["step"];
				if ($end > $table["rowcount"])
				{
					$end = $table["rowcount"];
				}
				$this->tpl->setVariable("TEXT_ITEM_END", $end);
				$this->tpl->setVariable("TEXT_OF", strtolower($this->lng->txt("of")));
				$this->tpl->setVariable("TEXT_ITEM_COUNT", $table["rowcount"]);
				$this->tpl->setVariable("TEXT_PREVIOUS", $this->lng->txt("previous"));
				$this->tpl->setVariable("TEXT_NEXT", $this->lng->txt("next"));
				$this->tpl->setVariable("HREF_PREV_ROWS", $this->ctrl->getLinkTarget($this, "browseForQuestions") . $add_parameter . "$sort&prevrow=" . $table["prevrow"]);
				$this->tpl->setVariable("HREF_NEXT_ROWS", $this->ctrl->getLinkTarget($this, "browseForQuestions") . $add_parameter . "$sort&nextrow=" . $table["nextrow"]);
				$this->tpl->parseCurrentBlock();
			}
		}
		else
		{
			foreach ($table["rows"] as $data)
			{
				$this->tpl->setCurrentBlock("questionblock_row");
				$this->tpl->setVariable("QUESTIONBLOCK_ID", $data["questionblock_id"]);
				$this->tpl->setVariable("QUESTIONBLOCK_TITLE", "<strong>" . $data["title"] . "</strong>");
				$this->tpl->setVariable("SURVEY_TITLE", $data["surveytitle"]);
				$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
				$this->tpl->setVariable("QUESTIONS_TITLE", $data["questions"]);
				$this->tpl->parseCurrentBlock();
				$counter++;
			}
			if ($table["rowcount"] > count($table["rows"]))
			{
				$nextstep = $table["nextrow"] + $table["step"];
				if ($nextstep > $table["rowcount"])
				{
					$nextstep = $table["rowcount"];
				}
				$sort = "";
				if (is_array($_GET["sort"]))
				{
					$key = key($_GET["sort"]);
					$sort = "&sort[$key]=" . $_GET["sort"]["$key"];
				}
				$counter = 1;
				for ($i = 0; $i < $table["rowcount"]; $i += $table["step"])
				{
					$this->tpl->setCurrentBlock("pages_questionblocks");
					if ($table["startrow"] == $i)
					{
						$this->tpl->setVariable("PAGE_NUMBER", "<strong>$counter</strong>");
					}
					else
					{
						$this->tpl->setVariable("PAGE_NUMBER", "<a href=\"" . $this->ctrl->getLinkTarget($this, "browseForQuestions") . $add_parameter . "$sort&nextrow=$i" . "\">$counter</a>");
					}
					$this->tpl->parseCurrentBlock();
					$counter++;
				}
				$this->tpl->setCurrentBlock("questionblocks_navigation_bottom");
				$this->tpl->setVariable("TEXT_ITEM", $this->lng->txt("item"));
				$this->tpl->setVariable("TEXT_ITEM_START", $table["startrow"] + 1);
				$end = $table["startrow"] + $table["step"];
				if ($end > $table["rowcount"])
				{
					$end = $table["rowcount"];
				}
				$this->tpl->setVariable("TEXT_ITEM_END", $end);
				$this->tpl->setVariable("TEXT_OF", strtolower($this->lng->txt("of")));
				$this->tpl->setVariable("TEXT_ITEM_COUNT", $table["rowcount"]);
				$this->tpl->setVariable("TEXT_PREVIOUS", $this->lng->txt("previous"));
				$this->tpl->setVariable("TEXT_NEXT", $this->lng->txt("next"));
				$this->tpl->setVariable("HREF_PREV_ROWS", $this->ctrl->getLinkTarget($this, "browseForQuestions") . $add_parameter . "$sort&prevrow=" . $table["prevrow"]);
				$this->tpl->setVariable("HREF_NEXT_ROWS", $this->ctrl->getLinkTarget($this, "browseForQuestions") . $add_parameter . "$sort&nextrow=" . $table["nextrow"]);
				$this->tpl->parseCurrentBlock();
			}
		}

    // if there are no questions, display a message
    if ($counter == 0) 
		{
      $this->tpl->setCurrentBlock("Emptytable");
			if ($browsequestions)
			{
      	$this->tpl->setVariable("TEXT_EMPTYTABLE", $this->lng->txt("no_questions_available"));
			}
			else
			{
      	$this->tpl->setVariable("TEXT_EMPTYTABLE", $this->lng->txt("no_questionblocks_available"));
			}
      $this->tpl->parseCurrentBlock();
    }
		else
		{
			$this->tpl->setCurrentBlock("selectall");
			$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
			$counter++;
			$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
			$this->tpl->parseCurrentBlock();
			// create edit buttons & table footer
			$this->tpl->setCurrentBlock("selection");
			$this->tpl->setVariable("INSERT", $this->lng->txt("insert"));
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("Footer");
			include_once "./classes/class.ilUtil.php";
			$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
			$this->tpl->parseCurrentBlock();
		}
    // define the sort column parameters
    $sort = array(
      "title" => $_GET["sort"]["title"],
      "description" => $_GET["sort"]["description"],
      "type" => $_GET["sort"]["type"],
      "author" => $_GET["sort"]["author"],
      "created" => $_GET["sort"]["created"],
      "updated" => $_GET["sort"]["updated"],
			"qpl" => $_GET["sort"]["qpl"],
			"svy" => $_GET["sort"]["svy"]
    );
    foreach ($sort as $key => $value) {
      if (strcmp($value, "ASC") == 0) {
        $sort[$key] = "DESC";
      } else {
        $sort[$key] = "ASC";
      }
    }

		if ($browsequestions)
		{
			$this->tpl->setCurrentBlock("questions_header");
			$this->tpl->setVariable("QUESTION_TITLE", "<a href=\"" . $this->ctrl->getLinkTargetByClass(get_class($this), "browseForQuestions") . "$add_parameter&startrow=" . $table["startrow"] . "&sort[title]=" . $sort["title"] . "\">" . $this->lng->txt("title") . "</a>" . $table["images"]["title"]);
			$this->tpl->setVariable("QUESTION_COMMENT", "<a href=\"" . $this->ctrl->getLinkTargetByClass(get_class($this), "browseForQuestions") . "$add_parameter&startrow=" . $table["startrow"] . "&sort[description]=" . $sort["description"] . "\">" . $this->lng->txt("description") . "</a>". $table["images"]["description"]);
			$this->tpl->setVariable("QUESTION_TYPE", "<a href=\"" . $this->ctrl->getLinkTargetByClass(get_class($this), "browseForQuestions") . "$add_parameter&startrow=" . $table["startrow"] . "&sort[type]=" . $sort["type"] . "\">" . $this->lng->txt("question_type") . "</a>" . $table["images"]["type"]);
			$this->tpl->setVariable("QUESTION_AUTHOR", "<a href=\"" . $this->ctrl->getLinkTargetByClass(get_class($this), "browseForQuestions") . "$add_parameter&startrow=" . $table["startrow"] . "&sort[author]=" . $sort["author"] . "\">" . $this->lng->txt("author") . "</a>" . $table["images"]["author"]);
			$this->tpl->setVariable("QUESTION_CREATED", "<a href=\"" . $this->ctrl->getLinkTargetByClass(get_class($this), "browseForQuestions") . "$add_parameter&startrow=" . $table["startrow"] . "&sort[created]=" . $sort["created"] . "\">" . $this->lng->txt("create_date") . "</a>" . $table["images"]["created"]);
			$this->tpl->setVariable("QUESTION_UPDATED", "<a href=\"" . $this->ctrl->getLinkTargetByClass(get_class($this), "browseForQuestions") . "$add_parameter&startrow=" . $table["startrow"] . "&sort[updated]=" . $sort["updated"] . "\">" . $this->lng->txt("last_update") . "</a>" . $table["images"]["updated"]);
			$this->tpl->setVariable("QUESTION_POOL", "<a href=\"" . $this->ctrl->getLinkTargetByClass(get_class($this), "browseForQuestions") . "$add_parameter&startrow=" . $table["startrow"] . "&sort[qpl]=" . $sort["qpl"] . "\">" . $this->lng->txt("obj_spl") . "</a>" . $table["images"]["qpl"]);
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$this->tpl->setCurrentBlock("questionblocks_header");
			$this->tpl->setVariable("QUESTIONBLOCK_TITLE", "<a href=\"" . $this->ctrl->getLinkTargetByClass(get_class($this), "browseForQuestions") . "$add_parameter&startrow=" . $table["startrow"] . "&sort[title]=" . $sort["title"] . "\">" . $this->lng->txt("title") . "</a>" . $table["images"]["title"]);
			$this->tpl->setVariable("SURVEY_TITLE", "<a href=\"" . $this->ctrl->getLinkTargetByClass(get_class($this), "browseForQuestions") . "$add_parameter&startrow=" . $table["startrow"] . "&sort[svy]=" . $sort["svy"] . "\">" . $this->lng->txt("obj_svy") . "</a>" . $table["images"]["svy"]);
			$this->tpl->setVariable("QUESTIONS_TITLE", $this->lng->txt("contains"));
			$this->tpl->parseCurrentBlock();
		}
    $this->tpl->setCurrentBlock("adm_content");
    // create table header
    $this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this) . $add_parameter);
    $this->tpl->parseCurrentBlock();
	}
	
/**
* Execute a search for survey questions
*
* Execute a search for survey questions
*
* @access private
*/
	function searchQuestionsExecuteObject()
	{
		include_once "./survey/classes/class.SurveySearch.php";
		include_once "./classes/class.ilUtil.php";
		$search = new SurveySearch(ilUtil::stripSlashes($_POST["search_term"]), $_POST["concat"], $_POST["search_field"], $_POST["search_type"]);
		$search->search();
		$results =& $search->search_results;
		if (count($results))
		{
			$this->searchQuestionsObject($results);
		}
		else
		{
			sendInfo($this->lng->txt("no_search_results"));
			$this->searchQuestionsObject();
		}
	}
	
/**
* Creates a form to search questions for inserting
*
* Creates a form to search questions for inserting
*
* @param mixed $search_results Array containing search results of a search for survey questions
* @access public
*/
	function searchQuestionsObject($search_results = false)
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_search_questions.html", true);

		if (is_array($search_results))
		{
			$classes = array("tblrow1", "tblrow2");
			$counter = 0;
			$titles = $this->object->getQuestionpoolTitles();
			$forbidden_pools =& $this->object->getForbiddenQuestionpools();
			$existing_questions =& $this->object->getExistingQuestions();
			foreach ($search_results as $data)
			{
				if ((!in_array($data["question_id"], $existing_questions)) && (!in_array($data["obj_fi"], $forbidden_pools)))
				{
					$this->tpl->setCurrentBlock("result_row");
					$this->tpl->setVariable("COLOR_CLASS", $classes[$counter % 2]);
					$this->tpl->setVariable("QUESTION_ID", $data["question_id"]);
					$this->tpl->setVariable("QUESTION_TITLE", $data["title"]);
					$this->tpl->setVariable("QUESTION_DESCRIPTION", $data["description"]);
					$this->tpl->setVariable("QUESTION_TYPE", $this->lng->txt($data["type_tag"]));
					$this->tpl->setVariable("QUESTION_AUTHOR", $data["author"]);
					$this->tpl->setVariable("QUESTION_POOL", $titles[$data["obj_fi"]]);
					$this->tpl->parseCurrentBlock();
					$counter++;
				}
			}
			$this->tpl->setCurrentBlock("search_results");
			include_once "./classes/class.ilUtil.php";
			$this->tpl->setVariable("RESULT_IMAGE", ilUtil::getImagePath("icon_spl_b.gif"));
			$this->tpl->setVariable("ALT_IMAGE", $this->lng->txt("found_questions"));
			$this->tpl->setVariable("TEXT_QUESTION_TITLE", $this->lng->txt("title"));
			$this->tpl->setVariable("TEXT_QUESTION_DESCRIPTION", $this->lng->txt("description"));
			$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt("question_type"));
			$this->tpl->setVariable("TEXT_QUESTION_AUTHOR", $this->lng->txt("author"));
			$this->tpl->setVariable("TEXT_QUESTION_POOL", $this->lng->txt("obj_spl"));
			$this->tpl->setVariable("BTN_INSERT", $this->lng->txt("insert"));
			$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
			$this->tpl->setVariable("FOUND_QUESTIONS", $this->lng->txt("found_questions"));
			$this->tpl->parseCurrentBlock();
		}
		
		sendInfo();
		$questiontypes = &$this->object->getQuestiontypes();
		foreach ($questiontypes as $questiontype)
		{
			$this->tpl->setCurrentBlock("questiontypes");
			$this->tpl->setVariable("VALUE_QUESTION_TYPE", $questiontype);
			$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt($questiontype));
			if (strcmp($_POST["search_type"], $questiontype) == 0)
			{
				$this->tpl->setVariable("SELECTED_SEARCH_TYPE", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		switch ($_POST["search_field"])
		{
			case "title":
				$this->tpl->setVariable("CHECKED_TITLE", " selected=\"selected\"");
				break;
			case "description":
				$this->tpl->setVariable("CHECKED_DESCRIPTION", " selected=\"selected\"");
				break;
			case "author":
				$this->tpl->setVariable("CHECKED_AUTHOR", " selected=\"selected\"");
				break;
			case "questiontext":
				$this->tpl->setVariable("CHECKED_QUESTIONTEXT", " selected=\"selected\"");
				break;
			case "default":
				$this->tpl->setVariable("CHECKED_ALL", " selected=\"selected\"");
				break;
		}
		$this->tpl->setVariable("TEXT_SEARCH_TERM", $this->lng->txt("search_term"));
		$this->tpl->setVariable("VALUE_SEARCH_TERM", $_POST["search_term"]);
		$this->tpl->setVariable("TEXT_CONCATENATION", $this->lng->txt("concatenation"));
		$this->tpl->setVariable("TEXT_AND", $this->lng->txt("and"));
		$this->tpl->setVariable("TEXT_OR", $this->lng->txt("or"));
		if ($_POST["concat"] == 1)
		{
			$this->tpl->setVariable("CHECKED_OR", " checked=\"checked\"");
		}
		else
		{
			$this->tpl->setVariable("CHECKED_AND", " checked=\"checked\"");
		}
		$this->tpl->setVariable("TEXT_SEARCH_FOR", $this->lng->txt("search_for"));
		$this->tpl->setVariable("SEARCH_FIELD_ALL", $this->lng->txt("search_field_all"));
		$this->tpl->setVariable("SEARCH_FIELD_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("SEARCH_FIELD_DESCRIPTION", $this->lng->txt("description"));
		$this->tpl->setVariable("SEARCH_FIELD_AUTHOR", $this->lng->txt("author"));
		$this->tpl->setVariable("SEARCH_FIELD_QUESTIONTEXT", $this->lng->txt("question"));
		$this->tpl->setVariable("SEARCH_TYPE_ALL", $this->lng->txt("search_type_all"));
		$this->tpl->setVariable("BTN_SEARCH", $this->lng->txt("search"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this) . "&search_question=1&browsetype=1&insert_question=1");
		$this->tpl->parseCurrentBlock();
	}

/**
* Creates a confirmation form to remove questions from the survey
*
* Creates a confirmation form to remove questions from the survey
*
* @param array $checked_questions An array containing the id's of the questions to be removed
* @param array $checked_questionblocks An array containing the id's of the question blocks to be removed
* @access public
*/
	function removeQuestionsForm($checked_questions, $checked_questionblocks)
	{
		sendInfo();
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_remove_questions.html", true);
		$colors = array("tblrow1", "tblrow2");
		$counter = 0;
		$surveyquestions =& $this->object->getSurveyQuestions();
		foreach ($surveyquestions as $question_id => $data)
		{
			if (in_array($data["question_id"], $checked_questions) or (in_array($data["questionblock_id"], $checked_questionblocks)))
			{
				$this->tpl->setCurrentBlock("row");
				$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
				$this->tpl->setVariable("TEXT_TITLE", $data["title"]);
				$this->tpl->setVariable("TEXT_DESCRIPTION", $data["description"]);
				$this->tpl->setVariable("TEXT_TYPE", $this->lng->txt($data["type_tag"]));
				$this->tpl->setVariable("TEXT_QUESTIONBLOCK", $data["questionblock_title"]);
				$this->tpl->parseCurrentBlock();
				$counter++;
			}
		}
		foreach ($checked_questions as $id)
		{
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("HIDDEN_NAME", "id_$id");
			$this->tpl->setVariable("HIDDEN_VALUE", "$id");
			$this->tpl->parseCurrentBlock();
		}
		foreach ($checked_questionblocks as $id)
		{
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("HIDDEN_NAME", "id_qb_$id");
			$this->tpl->setVariable("HIDDEN_VALUE", "$id");
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TEXT_DESCRIPTION", $this->lng->txt("description"));
		$this->tpl->setVariable("TEXT_TYPE", $this->lng->txt("question_type"));
		$this->tpl->setVariable("TEXT_QUESTIONBLOCK", $this->lng->txt("questionblock"));
		$this->tpl->setVariable("BTN_CONFIRM", $this->lng->txt("confirm"));
		$this->tpl->setVariable("BTN_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->parseCurrentBlock();
	}


/**
* Displays the definition form for a question block
*
* Displays the definition form for a question block
*
* @param integer $questionblock_id The database id of the questionblock to edit an existing questionblock
* @access public
*/
	function defineQuestionblock($questionblock_id = "")
	{
		sendInfo();
		if ($questionblock_id)
		{
			$questionblock = $this->object->getQuestionblock($questionblock_id);
		}
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_define_questionblock.html", true);
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/cb_(\d+)/", $key, $matches))
			{
				$this->tpl->setCurrentBlock("hidden");
				$this->tpl->setVariable("HIDDEN_NAME", "cb_$matches[1]");
				$this->tpl->setVariable("HIDDEN_VALUE", $matches[1]);
				$this->tpl->parseCurrentBlock();
			}
		}
		if ($questionblock_id)
		{
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("HIDDEN_NAME", "questionblock_id");
			$this->tpl->setVariable("HIDDEN_VALUE", $questionblock_id);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("DEFINE_QUESTIONBLOCK_HEADING", $this->lng->txt("define_questionblock"));
		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		if ($questionblock_id)
		{
			$this->tpl->setVariable("VALUE_TITLE", $questionblock["title"]);
		}
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->parseCurrentBlock();
	}

/**
* Creates a form to select a survey question pool for storage
*
* Creates a form to select a survey question pool for storage
*
* @access public
*/
	function createQuestionObject()
	{
		global $ilUser;
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_qpl_select.html", true);
		$questionpools =& $this->object->getAvailableQuestionpools();
		foreach ($questionpools as $key => $value)
		{
			$this->tpl->setCurrentBlock("option");
			$this->tpl->setVariable("VALUE_OPTION", $key);
			$this->tpl->setVariable("TEXT_OPTION", $value);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("hidden");
		$this->tpl->setVariable("HIDDEN_NAME", "sel_question_types");
		$this->tpl->setVariable("HIDDEN_VALUE", $_POST["sel_question_types"]);
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_QPL_SELECT", $this->lng->txt("select_questionpool"));
		if (count($questionpools))
		{
			$this->tpl->setVariable("BTN_SUBMIT", $this->lng->txt("submit"));
		}
		else
		{
			sendInfo($this->lng->txt("create_questionpool_before_add_question"));
		}
		$this->tpl->setVariable("BTN_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->parseCurrentBlock();
	}

/**
* Cancel the creation of a new questions in a survey
*
* Cancel the creation of a new questions in a survey
*
* @access private
*/
	function cancelCreateQuestionObject()
	{
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Execute the creation of a new questions in a survey
*
* Execute the creation of a new questions in a survey
*
* @access private
*/
	function executeCreateQuestionObject()
	{
		include_once "./classes/class.ilUtil.php";
		ilUtil::redirect("ilias.php?baseClass=ilObjSurveyQuestionPoolGUI&ref_id=" . $_POST["sel_spl"] . "&cmd=createQuestionForSurvey&new_for_survey=".$_GET["ref_id"]."&sel_question_types=".$_POST["sel_question_types"]);
	}
	
/**
* Creates a form to add a heading to a survey
*
* Creates a form to add a heading to a survey
*
* @param integer $question_id The id of the question directly after the heading. If the id is given, an existing heading will be edited
* @access public
*/
	function addHeadingObject($question_id = "")
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_heading.html", true);
		$survey_questions =& $this->object->getSurveyQuestions();
		if ($question_id)
		{
			$_POST["insertbefore"] = $question_id;
			$_POST["heading"] = $survey_questions[$question_id]["heading"];
		}
		foreach ($survey_questions as $key => $value)
		{
			$this->tpl->setCurrentBlock("insertbefore_row");
			$this->tpl->setVariable("VALUE_OPTION", $key);
			$option = $this->lng->txt("before") . ": \"" . $value["title"] . "\"";
			if (strlen($option) > 80)
			{
				$option = preg_replace("/^(.{40}).*(.{40})$/", "\\1 [...] \\2", $option);
			}
			include_once "./classes/class.ilUtil.php";
			$this->tpl->setVariable("TEXT_OPTION", ilUtil::prepareFormOutput($option));
			if ($key == $_POST["insertbefore"])
			{
				$this->tpl->setVariable("SELECTED_OPTION", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock();
		}
		if ($question_id)
		{
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("INSERTBEFORE_ORIGINAL", $question_id);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		if ($question_id)
		{
			$this->tpl->setVariable("TEXT_ADD_HEADING", $this->lng->txt("edit_heading"));
			$this->tpl->setVariable("SELECT_DISABLED", " disabled=\"disabled\"");
		}
		else
		{
			$this->tpl->setVariable("TEXT_ADD_HEADING", $this->lng->txt("add_heading"));
		}
		$this->tpl->setVariable("TEXT_HEADING", $this->lng->txt("heading"));
		$this->tpl->setVariable("VALUE_HEADING", $_POST["heading"]);
		$this->tpl->setVariable("TEXT_INSERT", $this->lng->txt("insert"));
		$this->tpl->setVariable("CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();
	}

/**
* Insert questions or question blocks into the survey after confirmation
*
* Insert questions or question blocks into the survey after confirmation
*
* @access public
*/
	function confirmInsertQuestionObject()
	{
		// insert questions from test after confirmation
		foreach ($_POST as $key => $value) {
			if (preg_match("/id_(\d+)/", $key, $matches)) {
				if ($_GET["browsetype"] == 1)
				{
					$this->object->insertQuestion($matches[1]);
				}
				else
				{
					$this->object->insertQuestionBlock($matches[1]);
				}
			}
		}
		$this->object->saveCompletionStatus();
		sendInfo($this->lng->txt("questions_inserted"), true);
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Cancels insert questions or question blocks into the survey
*
* Cancels insert questions or question blocks into the survey
*
* @access public
*/
	function cancelInsertQuestionObject()
	{
		$this->ctrl->redirect($this, "questions");
	}

/**
* Saves an edited heading in the survey questions list
*
* Saves an edited heading in the survey questions list
*
* @access public
*/
	function saveHeadingObject()
	{
		if ($_POST["heading"])
		{
			$insertbefore = $_POST["insertbefore"];
			if (!$insertbefore)
			{
				$insertbefore = $_POST["insertbefore_original"];
			}
			$this->object->saveHeading($_POST["heading"], $insertbefore);
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			sendInfo($this->lng->txt("error_add_heading"));
			$this->addHeadingObject();
			return;
		}
	}
	
/**
* Cancels saving a heading in the survey questions list
*
* Cancels saving a heading in the survey questions list
*
* @access public
*/
	function cancelHeadingObject()
	{
		$this->ctrl->redirect($this, "questions");
	}

/**
* Remove a survey heading after confirmation
*
* Remove a survey heading after confirmation
*
* @access public
*/
	function confirmRemoveHeadingObject()
	{
		$this->object->saveHeading("", $_POST["removeheading"]);
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Cancels the removal of survey headings
*
* Cancels the removal of survey headings
*
* @access public
*/
	function cancelRemoveHeadingObject()
	{
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Displays a confirmation form to delete a survey heading
*
* Displays a confirmation form to delete a survey heading
*
* @access public
*/
	function confirmRemoveHeadingForm()
	{
		sendInfo($this->lng->txt("confirm_remove_heading"));
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_confirm_removeheading.html", true);
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("BTN_CONFIRM_REMOVE", $this->lng->txt("confirm"));
		$this->tpl->setVariable("BTN_CANCEL_REMOVE", $this->lng->txt("cancel"));
		$this->tpl->setVariable("REMOVE_HEADING", $_GET["removeheading"]);
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->parseCurrentBlock();
	}
	
/**
* Remove questions from survey after confirmation
*
* Remove questions from survey after confirmation
*
* @access private
*/
	function confirmRemoveQuestionsObject()
	{
		$checked_questions = array();
		$checked_questionblocks = array();
		foreach ($_POST as $key => $value) 
		{
			if (preg_match("/id_(\d+)/", $key, $matches)) 
			{
				array_push($checked_questions, $matches[1]);
			}
			if (preg_match("/id_qb_(\d+)/", $key, $matches)) 
			{
				array_push($checked_questionblocks, $matches[1]);
			}
		}
		$this->object->removeQuestions($checked_questions, $checked_questionblocks);
		$this->object->saveCompletionStatus();
		sendInfo($this->lng->txt("questions_removed"), true);
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Cancel remove questions from survey after confirmation
*
* Cancel remove questions from survey after confirmation
*
* @access private
*/
	function cancelRemoveQuestionsObject()
	{
		$this->ctrl->redirect($this, "questions");
	}

/**
* Cancel remove questions from survey after confirmation
*
* Cancel remove questions from survey after confirmation
*
* @access private
*/
	function defineQuestionblockObject()
	{
		$questionblock = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/cb_(\d+)/", $key, $matches))
			{
				array_push($questionblock, $value);
			}
		}
		if (count($questionblock) < 2)
		{
			sendInfo($this->lng->txt("qpl_define_questionblock_select_missing"), true);
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			$this->defineQuestionblock();
			return;
		}
	}
	
/**
* Confirm define a question block
*
* Confirm define a question block
*
* @access private
*/
	function saveDefineQuestionblockObject()
	{
		if ($_POST["title"])
		{
			if ($_POST["questionblock_id"])
			{
				include_once "./classes/class.ilUtil.php";
				$this->object->modifyQuestionblock($_POST["questionblock_id"], ilUtil::stripSlashes($_POST["title"]));
			}
			else
			{
				$questionblock = array();
				foreach ($_POST as $key => $value)
				{
					if (preg_match("/cb_(\d+)/", $key, $matches))
					{
						array_push($questionblock, $value);
					}
				}
				include_once "./classes/class.ilUtil.php";
				$this->object->createQuestionblock(ilUtil::stripSlashes($_POST["title"]), $questionblock);
			}
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			sendInfo($this->lng->txt("enter_questionblock_title"));
			$this->defineQuestionblockObject();
			return;
		}
	}

/**
* Unfold a question block
*
* Unfold a question block
*
* @access private
*/
	function unfoldQuestionblockObject()
	{
		$unfoldblocks = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/cb_qb_(\d+)/", $key, $matches))
			{
				array_push($unfoldblocks, $matches[1]);
			}
		}
		if (count($unfoldblocks))
		{
			$this->object->unfoldQuestionblocks($unfoldblocks);
		}
		else
		{
			sendInfo($this->lng->txt("qpl_unfold_select_none"), true);
		}
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Cancel define a question block
*
* Cancel define a question block
*
* @access private
*/
	function cancelDefineQuestionblockObject()
	{
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Move questions
*
* Move questions
*
* @access private
*/
	function moveQuestionsObject()
	{
		$move_questions = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/cb_(\d+)/", $key, $matches))
			{
				array_push($move_questions, $matches[1]);
			}
			if (preg_match("/cb_qb_(\d+)/", $key, $matches))
			{
				$ids = $this->object->getQuestionblockQuestionIds($matches[1]);
				foreach ($ids as $qkey => $qid)
				{
					array_push($move_questions, $qid);
				}
			}
		}
		if (count($move_questions) == 0)
		{
			sendInfo($this->lng->txt("no_question_selected_for_move"), true);
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			$_SESSION["move_questions"] = $move_questions;
			sendInfo($this->lng->txt("select_target_position_for_move_question"));
			$this->questionsObject();
		}
	}

/**
* Insert questions from move clipboard
*
* Insert questions from move clipboard
*
* @access private
*/
	function insertQuestions($insert_mode)
	{
		// get all questions to move
		$move_questions = $_SESSION["move_questions"];
		// get insert point
		$insert_id = -1;
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/^cb_(\d+)$/", $key, $matches))
			{
				if ($insert_id < 0)
				{
					$insert_id = $matches[1];
				}
			}
			if (preg_match("/^cb_qb_(\d+)$/", $key, $matches))
			{
				if ($insert_id < 0)
				{
					$ids =& $this->object->getQuestionblockQuestionIds($matches[1]);
					if (count($ids))
					{
						if ($insert_mode == 0)
						{
							$insert_id = $ids[0];
						}
						else if ($insert_mode == 1)
						{
							$insert_id = $ids[count($ids)-1];
						}
					}
				}
			}
		}
		if ($insert_id <= 0)
		{
			sendInfo($this->lng->txt("no_target_selected_for_move"), true);
		}
		else
		{
			$this->object->moveQuestions($move_questions, $insert_id, $insert_mode);
		}
		unset($_SESSION["move_questions"]);
		$this->ctrl->redirect($this, "questions");
	}

/**
* Insert questions before selection
*
* Insert questions before selection
*
* @access private
*/
	function insertQuestionsBeforeObject()
	{
		$this->insertQuestions(0);
	}
	
/**
* Insert questions after selection
*
* Insert questions after selection
*
* @access private
*/
	function insertQuestionsAfterObject()
	{
		$this->insertQuestions(1);
	}

/**
* Save obligatory states
*
* Save obligatory states
*
* @access private
*/
	function saveObligatoryObject()
	{
		$obligatory = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/obligatory_(\d+)/", $key, $matches))
			{
				$obligatory[$matches[1]] = 1;
			}
		}
		$this->object->setObligatoryStates($obligatory);
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Creates the questions form for the survey object
*
* Creates the questions form for the survey object
*
* @access public
*/
	function questionsObject() 
	{
		global $rbacsystem;

		include_once "./classes/class.ilUtil.php";
		if ((!$rbacsystem->checkAccess("read", $this->ref_id)) && (!$rbacsystem->checkAccess("write", $this->ref_id))) 
		{
			// allow only read and write access
			sendInfo($this->lng->txt("cannot_edit_survey"), true);
			$path = $this->tree->getPathFull($this->object->getRefID());
			ilUtil::redirect($this->getReturnLocation("cancel","./repository.php?cmd=frameset&ref_id=" . $path[count($path) - 2]["child"]));
			return;
		}
		
		if ($_GET["new_id"] > 0)
		{
			// add a question to the survey previous created in a questionpool
			$this->object->insertQuestion($_GET["new_id"]);
		}
		
		if ($_GET["eqid"] and $_GET["eqpl"])
		{
			ilUtil::redirect("ilias.php?baseClass=ilObjSurveyQuestionPoolGUI&ref_id=" . $_GET["eqpl"] . "&cmd=editQuestionForSurvey&calling_survey=".$_GET["ref_id"]."&q_id=" . $_GET["eqid"]);
		}


		$_SESSION["calling_survey"] = $this->object->getRefId();
		unset($_SESSION["survey_id"]);

		if ($_GET["editheading"])
		{
			$this->addHeadingObject($_GET["editheading"]);
			return;
		}
		
		if ($_GET["up"] > 0)
		{
			$this->object->moveUpQuestion($_GET["up"]);
		}
		if ($_GET["down"] > 0)
		{
			$this->object->moveDownQuestion($_GET["down"]);
		}
		if ($_GET["qbup"] > 0)
		{
			$this->object->moveUpQuestionblock($_GET["qbup"]);
		}
		if ($_GET["qbdown"] > 0)
		{
			$this->object->moveDownQuestionblock($_GET["qbdown"]);
		}
		
		if ($_GET["removeheading"])
		{
			$this->confirmRemoveHeadingForm();
			return;
		}
		
		if ($_GET["editblock"])
		{
			$this->defineQuestionblock($_GET["editblock"]);
			return;
		}

		if ($_GET["add"])
		{
			// called after a new question was created from a questionpool
			$selected_array = array();
			array_push($selected_array, $_GET["add"]);
			sendInfo($this->lng->txt("ask_insert_questions"));
			$this->insertQuestionsForm($selected_array);
			return;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_questions.html", true);

		$survey_questions =& $this->object->getSurveyQuestions();
		$questionblock_titles =& $this->object->getQuestionblockTitles();
		$questionpools =& $this->object->getQuestionpoolTitles();
		$colors = array("tblrow1", "tblrow2");
		$counter = 0;
		$title_counter = 0;
		$last_color_class = "";
		$obligatory = "<img src=\"" . ilUtil::getImagePath("obligatory.gif", true) . "\" alt=\"" . $this->lng->txt("question_obligatory") . "\" title=\"" . $this->lng->txt("question_obligatory") . "\" />";
		if (count($survey_questions) > 0)
		{
			foreach ($survey_questions as $question_id => $data)
			{
				$title_counter++;
				if (($last_questionblock_id > 0) && ($data["questionblock_id"] == 0))
				{
					$counter++;
				}
				if (($last_questionblock_id > 0) && ($data["questionblock_id"] > 0) && ($data["questionblock_id"] != $last_questionblock_id))
				{
					$counter++;
				}
				if (($data["questionblock_id"] > 0) and ($data["questionblock_id"] != $last_questionblock_id))
				{
					// add a separator line for the beginning of a question block
					$this->tpl->setCurrentBlock("separator");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("QTab");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();

					$this->tpl->setCurrentBlock("block");
					$this->tpl->setVariable("TYPE_ICON", "<img src=\"" . ilUtil::getImagePath("questionblock.gif", true) . "\" alt=\"".$this->lng->txt("questionblock_icon")."\" />");
					$this->tpl->setVariable("TEXT_QUESTIONBLOCK", $this->lng->txt("questionblock") . ": " . $data["questionblock_title"]);
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					if ($rbacsystem->checkAccess("write", $this->ref_id) and ($this->object->isOffline())) 
					{
						if ($data["question_id"] != $this->object->questions[0])
						{
							$this->tpl->setVariable("BUTTON_UP", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "$&qbup=" . $data["questionblock_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_up.gif") . "\" alt=\"" . $this->lng->txt("up") . "\" title=\"" . $this->lng->txt("up") . "\" border=\"0\" /></a>");
						}
						$akeys = array_keys($survey_questions);
						if ($data["questionblock_id"] != $survey_questions[$akeys[count($akeys)-1]]["questionblock_id"])
						{
							$this->tpl->setVariable("BUTTON_DOWN", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&qbdown=" . $data["questionblock_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_down.gif") . "\" alt=\"" . $this->lng->txt("down") . "\" title=\"" . $this->lng->txt("down") . "\" border=\"0\" /></a>");
						}
						$this->tpl->setVariable("TEXT_EDIT", $this->lng->txt("edit"));
						$this->tpl->setVariable("HREF_EDIT", $this->ctrl->getLinkTarget($this, "questions") . "&editblock=" . $data["questionblock_id"]);
					}
					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("QTab");
					$this->tpl->setVariable("QUESTION_ID", "qb_" . $data["questionblock_id"]);
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();
				}
				if (($last_questionblock_id > 0) && ($data["questionblock_id"] == 0))
				{
					// add a separator line for the end of a question block
					$this->tpl->setCurrentBlock("separator");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("QTab");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();
				}
				if ($data["heading"])
				{
					$this->tpl->setCurrentBlock("heading");
					$this->tpl->setVariable("TEXT_HEADING", $data["heading"]);
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					if ($rbacsystem->checkAccess("write", $this->ref_id) and ($this->object->isOffline())) 
					{
						$this->tpl->setVariable("TEXT_EDIT", $this->lng->txt("edit"));
						$this->tpl->setVariable("HREF_EDIT", $this->ctrl->getLinkTarget($this, "questions") . "&editheading=" . $data["question_id"]);
						$this->tpl->setVariable("TEXT_DELETE", $this->lng->txt("remove"));
						$this->tpl->setVariable("HREF_DELETE", $this->ctrl->getLinkTarget($this, "questions") . "&removeheading=" . $data["question_id"]);
					}
					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("QTab");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();
				}
				if (!$data["questionblock_id"])
				{
					$this->tpl->setCurrentBlock("checkable");
					$this->tpl->setVariable("QUESTION_ID", $data["question_id"]);
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock("QTab");
				include_once "./survey/classes/class.SurveyQuestion.php";
				$ref_id = SurveyQuestion::_getRefIdFromObjId($data["obj_fi"]);
				if ($rbacsystem->checkAccess("write", $this->ref_id) and ($this->object->isOffline())) 
				{
					$q_id = $data["question_id"];
					$qpl_ref_id = $this->object->_getRefIdFromObjId($data["obj_fi"]);
					$this->tpl->setVariable("QUESTION_TITLE", "$title_counter. <a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&eqid=$q_id&eqpl=$qpl_ref_id" . "\">" . $data["title"] . "</a>");
				}
				else
				{
					$this->tpl->setVariable("QUESTION_TITLE", "$title_counter. ". $data["title"]);
				}
				$this->tpl->setVariable("TYPE_ICON", "<img src=\"" . ilUtil::getImagePath("question.gif", true) . "\" alt=\"".$this->lng->txt("question_icon")."\" />");
				if ($rbacsystem->checkAccess("write", $this->ref_id) and ($this->object->isOffline())) 
				{
					$obligatory_checked = "";
					if ($data["obligatory"] == 1)
					{
						$obligatory_checked = " checked=\"checked\"";
					}
					$this->tpl->setVariable("QUESTION_OBLIGATORY", "<input type=\"checkbox\" name=\"obligatory_" . $data["question_id"] . "\" value=\"1\"$obligatory_checked />");
				}
				else
				{
					if ($data["obligatory"] == 1)
					{
						$this->tpl->setVariable("QUESTION_OBLIGATORY", $obligatory);
					}
				}
				$this->tpl->setVariable("QUESTION_COMMENT", $data["description"]);
				if ($rbacsystem->checkAccess("write", $this->ref_id) and ($this->object->isOffline())) {
					if (!$data["questionblock_id"])
					{
						// up/down buttons for non-questionblock questions
						if ($data["question_id"] != $this->object->questions[0])
						{
							$this->tpl->setVariable("BUTTON_UP", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&up=" . $data["question_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_up.gif") . "\" alt=\"".$this->lng->txt("up")."\" border=\"0\" /></a>");
						}
						if ($data["question_id"] != $this->object->questions[count($this->object->questions)-1])
						{
							$this->tpl->setVariable("BUTTON_DOWN", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&down=" . $data["question_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_down.gif") . "\" alt=\"".$this->lng->txt("down")."\" border=\"0\" /></a>");
						}
					}
					else
					{
						// up/down buttons for questionblock questions
						if ($data["questionblock_id"] == $last_questionblock_id)
						{
							$this->tpl->setVariable("BUTTON_UP", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&up=" . $data["question_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_up.gif") . "\" alt=\"".$this->lng->txt("up")."\" border=\"0\" /></a>");
						}
						$tmp_questions = array_keys($survey_questions);
						$blockkey = array_search($question_id, $tmp_questions);
						if (($blockkey !== FALSE) && ($blockkey < count($tmp_questions)-1))
						{
							if ($data["questionblock_id"] == $survey_questions[$tmp_questions[$blockkey+1]]["questionblock_id"])
							{
								$this->tpl->setVariable("BUTTON_DOWN", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&down=" . $data["question_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_down.gif") . "\" alt=\"".$this->lng->txt("down")."\" border=\"0\" /></a>");
							}
						}
					}
				}
				$this->tpl->setVariable("QUESTION_TYPE", $this->lng->txt($data["type_tag"]));
				$this->tpl->setVariable("QUESTION_AUTHOR", $data["author"]);
				$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
				$last_color_class = $colors[$counter % 2];
				if (!$data["questionblock_id"])
				{
					$counter++;
				}
				$this->tpl->parseCurrentBlock();
				$last_questionblock_id = $data["questionblock_id"];
			}

	    if ($rbacsystem->checkAccess("write", $this->ref_id) and (!$this->object->getStatus() == STATUS_ONLINE)) 
			{
				$this->tpl->setCurrentBlock("selectall");
				$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
				$this->tpl->setVariable("COLOR_CLASS", $last_color_class);
				$this->tpl->parseCurrentBlock();
				if (array_key_exists("move_questions", $_SESSION))
				{
					$this->tpl->setCurrentBlock("move_buttons");
					$this->tpl->setVariable("INSERT_BEFORE", $this->lng->txt("insert_before"));
					$this->tpl->setVariable("INSERT_AFTER", $this->lng->txt("insert_after"));
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock("QFooter");
				$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
				$this->tpl->setVariable("REMOVE", $this->lng->txt("remove_question"));
				$this->tpl->setVariable("MOVE", $this->lng->txt("move"));
				$this->tpl->setVariable("QUESTIONBLOCK", $this->lng->txt("define_questionblock"));
				$this->tpl->setVariable("UNFOLD", $this->lng->txt("unfold"));
				$this->tpl->parseCurrentBlock();
				$this->tpl->setCurrentBlock("actionbuttons");
				$this->tpl->setVariable("SAVE", $this->lng->txt("save_obligatory_state"));
				$this->tpl->setVariable("HEADING", $this->lng->txt("add_heading"));
				$this->tpl->parseCurrentBlock();
			}
		}
		else
		{
			$this->tpl->setCurrentBlock("Emptytable");
			$this->tpl->setVariable("TEXT_EMPTYTABLE", $this->lng->txt("no_questions_available"));
			$this->tpl->parseCurrentBlock();
		}
		if (($last_questionblock_id > 0))
		{
			// add a separator line for the end of a question block (if the last question is a questionblock question)
			$this->tpl->setCurrentBlock("separator");
			$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("QTab");
			$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
			$this->tpl->parseCurrentBlock();
		}

		if (array_key_exists("move_questions", $_SESSION))
		{
			$this->tpl->setCurrentBlock("move_buttons");
			$this->tpl->setVariable("INSERT_BEFORE", $this->lng->txt("insert_before"));
			$this->tpl->setVariable("INSERT_AFTER", $this->lng->txt("insert_after"));
			$this->tpl->parseCurrentBlock();
		}
		
    if ($rbacsystem->checkAccess("write", $this->ref_id) and (!$this->object->getStatus() == STATUS_ONLINE)) 
		{
			$this->tpl->setCurrentBlock("QTypes");
			$query = "SELECT * FROM survey_questiontype";
			$query_result = $this->ilias->db->query($query);
			while ($data = $query_result->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$this->tpl->setVariable("QUESTION_TYPE_ID", $data->type_tag);
				$this->tpl->setVariable("QUESTION_TYPE", $this->lng->txt($data->type_tag));
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("QUESTION_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("QUESTION_COMMENT", $this->lng->txt("description"));
		$this->tpl->setVariable("QUESTION_OBLIGATORY", $this->lng->txt("obligatory"));
		$this->tpl->setVariable("QUESTION_SEQUENCE", $this->lng->txt("sequence"));
		$this->tpl->setVariable("QUESTION_TYPE", $this->lng->txt("question_type"));
		$this->tpl->setVariable("QUESTION_AUTHOR", $this->lng->txt("author"));

    if ($rbacsystem->checkAccess("write", $this->ref_id) and (!$this->object->getStatus() == STATUS_ONLINE)) {
			$this->tpl->setVariable("BUTTON_INSERT_QUESTION", $this->lng->txt("browse_for_questions"));
			$this->tpl->setVariable("BUTTON_SEARCH_QUESTION", $this->lng->txt("search_questions"));
			$this->tpl->setVariable("TEXT_OR", " " . strtolower($this->lng->txt("or")));
			$this->tpl->setVariable("TEXT_CREATE_NEW", " " . strtolower($this->lng->txt("or")) . " " . $this->lng->txt("create_new"));
			$this->tpl->setVariable("BUTTON_CREATE_QUESTION", $this->lng->txt("create"));
		}
		if ($this->object->getStatus() == STATUS_ONLINE)
		{
			sendInfo($this->lng->txt("survey_online_warning"));
		}

		$this->tpl->parseCurrentBlock();
	}

	/**
	* Redirects the evaluation object call to the ilSurveyEvaluationGUI class
	*
	* Redirects the evaluation object call to the ilSurveyEvaluationGUI class
	*
	* @access	private
	*/
	function evaluationObject()
	{
		include_once("./survey/classes/class.ilSurveyEvaluationGUI.php");
		$eval_gui = new ilSurveyEvaluationGUI($this->object);
		$this->ctrl->setCmdClass(get_class($eval_gui));
		$this->ctrl->redirect($eval_gui, "evaluation");
	}
	
	/**
	* Redirects the evaluation object call to the ilSurveyEvaluationGUI class
	*
	* Redirects the evaluation object call to the ilSurveyEvaluationGUI class
	*
	* @access	private
	*/
	function runObject()
	{
		include_once("./survey/classes/class.ilSurveyExecutionGUI.php");
		$exec_gui = new ilSurveyExecutionGUI($this->object);
		$this->ctrl->setCmdClass(get_class($exec_gui));
		$this->ctrl->redirect($exec_gui, "run");
	}
	
	/**
	* Creates the search output for the user/group search form
	*
	* Creates the search output for the user/group search form
	*
	* @access	public
	*/
	function outUserGroupTable($a_type, $id_array, $block_result, $block_row, $title_text, $buttons)
	{
		global $rbacsystem;
		
		$rowclass = array("tblrow1", "tblrow2");
		switch($a_type)
		{
			case "usr":
				include_once "./classes/class.ilObjUser.php";
				$counter = 0;
				foreach ($id_array as $user_id)
				{
					$user = new ilObjUser($user_id);
					$this->tpl->setCurrentBlock($block_row);
					$this->tpl->setVariable("COLOR_CLASS", $rowclass[$counter % 2]);
					$this->tpl->setVariable("COUNTER", $user->getId());
					$this->tpl->setVariable("VALUE_LOGIN", $user->getLogin());
					$this->tpl->setVariable("VALUE_FIRSTNAME", $user->getFirstname());
					$this->tpl->setVariable("VALUE_LASTNAME", $user->getLastname());
					$counter++;
					$this->tpl->parseCurrentBlock();
				}
				if (count($id_array))
				{
					$this->tpl->setCurrentBlock("selectall_$block_result");
					$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
					$counter++;
					$this->tpl->setVariable("COLOR_CLASS", $rowclass[$counter % 2]);
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock($block_result);
				include_once "./classes/class.ilUtil.php";
				$this->tpl->setVariable("TEXT_USER_TITLE", "<img src=\"" . ilUtil::getImagePath("icon_usr.gif") . "\" alt=\"".$this->lng->txt("obj_usr")."\" /> " . $title_text);
				$this->tpl->setVariable("TEXT_LOGIN", $this->lng->txt("login"));
				$this->tpl->setVariable("TEXT_FIRSTNAME", $this->lng->txt("firstname"));
				$this->tpl->setVariable("TEXT_LASTNAME", $this->lng->txt("lastname"));
				if ($rbacsystem->checkAccess('invite', $this->object->getRefId()))
				{
					foreach ($buttons as $cat)
					{
						$this->tpl->setVariable("VALUE_" . strtoupper($cat), $this->lng->txt($cat));
					}
					$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
				}
				$this->tpl->parseCurrentBlock();
				break;
			case "grp":
				include_once "./classes/class.ilObjGroup.php";
				$counter = 0;
				foreach ($id_array as $group_id)
				{
					$group = new ilObjGroup($group_id);
					$this->tpl->setCurrentBlock($block_row);
					$this->tpl->setVariable("COLOR_CLASS", $rowclass[$counter % 2]);
					$this->tpl->setVariable("COUNTER", $group->getRefId());
					$this->tpl->setVariable("VALUE_TITLE", $group->getTitle());
					$this->tpl->setVariable("VALUE_DESCRIPTION", $group->getDescription());
					$counter++;
					$this->tpl->parseCurrentBlock();
				}
				if (count($id_array))
				{
					$this->tpl->setCurrentBlock("selectall_$block_result");
					$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
					$counter++;
					$this->tpl->setVariable("COLOR_CLASS", $rowclass[$counter % 2]);
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock($block_result);
				include_once "./classes/class.ilUtil.php";
				$this->tpl->setVariable("TEXT_GROUP_TITLE", "<img src=\"" . ilUtil::getImagePath("icon_grp.gif") . "\" alt=\"".$this->lng->txt("obj_grp")."\" /> " . $title_text);
				$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
				$this->tpl->setVariable("TEXT_DESCRIPTION", $this->lng->txt("description"));
				if ($rbacsystem->checkAccess('invite', $this->object->getRefId()))
				{
					foreach ($buttons as $cat)
					{
						$this->tpl->setVariable("VALUE_" . strtoupper($cat), $this->lng->txt($cat));
					}
					$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
				}
				$this->tpl->parseCurrentBlock();
				break;
			case "role":
				include_once "./classes/class.ilObjRole.php";
				$counter = 0;
				foreach ($id_array as $role_id)
				{
					$role = new ilObjRole($role_id);
					$this->tpl->setCurrentBlock($block_row);
					$this->tpl->setVariable("COLOR_CLASS", $rowclass[$counter % 2]);
					$this->tpl->setVariable("COUNTER", $role->getId());
					$this->tpl->setVariable("VALUE_TITLE", $role->getTitle());
					$this->tpl->setVariable("VALUE_DESCRIPTION", $role->getDescription());
					$counter++;
					$this->tpl->parseCurrentBlock();
				}
				if (count($id_array))
				{
					$this->tpl->setCurrentBlock("selectall_$block_result");
					$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
					$counter++;
					$this->tpl->setVariable("COLOR_CLASS", $rowclass[$counter % 2]);
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock($block_result);
				include_once "./classes/class.ilUtil.php";
				$this->tpl->setVariable("TEXT_ROLE_TITLE", "<img src=\"" . ilUtil::getImagePath("icon_role.gif") . "\" alt=\"".$this->lng->txt("obj_role")."\" /> " . $title_text);
				$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
				$this->tpl->setVariable("TEXT_DESCRIPTION", $this->lng->txt("description"));
				if ($rbacsystem->checkAccess('invite', $this->object->getRefId()))
				{
					foreach ($buttons as $cat)
					{
						$this->tpl->setVariable("VALUE_" . strtoupper($cat), $this->lng->txt($cat));
					}
					$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
				}
				$this->tpl->parseCurrentBlock();
				break;
		}
	}
	
	/**
	* Cancels an action on the invitation tab
	*
	* Cancels an action on the invitation tab
	*
	* @access private
	*/
	function cancelInvitationStatusObject()
	{
		$this->ctrl->redirect($this, "invite");
	}

	/**
	* Saves the status of the invitation tab
	*
	* Saves the status of the invitation tab
	*
	* @access private
	*/
	function saveInvitationStatusObject()
	{
		$this->object->setInvitationAndMode($_POST["invitation"], $_POST["mode"]);
		$this->object->saveToDb();
		$this->ctrl->redirect($this, "invite");
	}
	
	/**
	* Searches users for the invitation tab
	*
	* Searches users for the invitation tab
	*
	* @access private
	*/
	function searchInvitationObject()
	{
		$this->inviteObject();
	}

	/**
	* Disinvite users or groups from a survey
	*
	* Disinvite users or groups from a survey
	*
	* @access	private
	*/
	function disinviteUserGroupObject()
	{
		// disinvite users
		if (is_array($_POST["invited_users"]))
		{
			foreach ($_POST["invited_users"] as $user_id)
			{
				$this->object->disinviteUser($user_id);
			}
		}
		$this->ctrl->redirect($this, "invite");
	}
	
	/**
	* Invite users or groups to a survey
	*
	* Invite users or groups to a survey
	*
	* @access	private
	*/
	function inviteUserGroupObject()
	{
		// add users to invitation
		if (is_array($_POST["user_select"]))
		{
			foreach ($_POST["user_select"] as $user_id)
			{
				$this->object->inviteUser($user_id);
			}
		}
		// add groups to invitation
		if (is_array($_POST["group_select"]))
		{
			foreach ($_POST["group_select"] as $group_id)
			{
				$this->object->inviteGroup($group_id);
			}
		}
		// add roles to invitation
		if (is_array($_POST["role_select"]))
		{
			foreach ($_POST["role_select"] as $role_id)
			{
				$this->object->inviteRole($role_id);
			}
		}
		$this->ctrl->redirect($this, "invite");
	}

	
	/**
	* Creates the output for user/group invitation to a survey
	*
	* Creates the output for user/group invitation to a survey
	*
	* @access	public
	*/
	function inviteObject()
	{
		global $rbacsystem;

		if ((!$rbacsystem->checkAccess("read", $this->ref_id)) && (!$rbacsystem->checkAccess("write", $this->ref_id))) 
		{
			// allow only read and write access
			sendInfo($this->lng->txt("cannot_edit_survey"), true);
			$path = $this->tree->getPathFull($this->object->getRefID());
			include_once "./classes/class.ilUtil.php";
			ilUtil::redirect($this->getReturnLocation("cancel","./repository.php?cmd=frameset&ref_id=" . $path[count($path) - 2]["child"]));
			return;
		}
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_invite.html", true);

		if ($this->object->getStatus() == STATUS_OFFLINE)
		{
			$this->tpl->setCurrentBlock("survey_offline");
			$this->tpl->setVariable("SURVEY_OFFLINE_MESSAGE", $this->lng->txt("survey_offline_message"));
			$this->tpl->parseCurrentBlock();
			return;
		}

		if (strcmp($this->ctrl->getCmd(), "searchInvitation") == 0)
		{
			if (is_array($_POST["search_for"]))
			{
				if (in_array("usr", $_POST["search_for"]) or in_array("grp", $_POST["search_for"]) or in_array("role", $_POST["search_for"]))
				{
					include_once "./classes/class.ilSearch.php";
					$search =& new ilSearch($ilUser->id);
					$search->setSearchString($_POST["search_term"]);
					$search->setCombination($_POST["concatenation"]);
					$search->setSearchFor($_POST["search_for"]);
					$search->setSearchType("new");
					if($search->validate($message))
					{
						$search->performSearch();
					}
					if ($message)
					{
						sendInfo($message);
					}
					if(!$search->getNumberOfResults() && $search->getSearchFor())
					{
						sendInfo($this->lng->txt("search_no_match"));
					}
					$buttons = array("add");
					$invited_users = $this->object->getInvitedUsers();
					if ($searchresult = $search->getResultByType("usr"))
					{
						$users = array();
						foreach ($searchresult as $result_array)
						{
							if (!in_array($result_array["id"], $invited_users))
							{
								array_push($users, $result_array["id"]);
							}
						}
						$this->outUserGroupTable("usr", $users, "user_result", "user_row", $this->lng->txt("search_users"), $buttons);
					}
					$searchresult = array();
					if ($searchresult = $search->getResultByType("grp"))
					{
						$groups = array();
						foreach ($searchresult as $result_array)
						{
							array_push($groups, $result_array["id"]);
						}
						$this->outUserGroupTable("grp", $groups, "group_result", "group_row", $this->lng->txt("search_groups"), $buttons);
					}
					$searchresult = array();
					if ($searchresult = $search->getResultByType("role"))
					{
						$roles = array();
						foreach ($searchresult as $result_array)
						{
							array_push($roles, $result_array["id"]);
						}
						$this->outUserGroupTable("role", $roles, "role_result", "role_row", $this->lng->txt("search_roles"), $buttons);
					}
				}
			}
			else
			{
				sendInfo($this->lng->txt("no_user_or_group_selected"));
			}
		}

		if (($this->object->getInvitationMode() == MODE_PREDEFINED_USERS) and ($this->object->getInvitation() == INVITATION_ON))
		{
			if ($rbacsystem->checkAccess('invite', $this->ref_id))
			{
				$this->tpl->setCurrentBlock("invitation");
				$this->tpl->setVariable("SEARCH_INVITATION", $this->lng->txt("search_invitation"));
				$this->tpl->setVariable("SEARCH_TERM", $this->lng->txt("search_term"));
				$this->tpl->setVariable("SEARCH_FOR", $this->lng->txt("search_for"));
				$this->tpl->setVariable("SEARCH_USERS", $this->lng->txt("objs_usr"));
				$this->tpl->setVariable("SEARCH_GROUPS", $this->lng->txt("objs_grp"));
				$this->tpl->setVariable("SEARCH_ROLES", $this->lng->txt("objs_role"));
				$this->tpl->setVariable("TEXT_CONCATENATION", $this->lng->txt("concatenation"));
				$this->tpl->setVariable("TEXT_AND", $this->lng->txt("and"));
				$this->tpl->setVariable("TEXT_OR", $this->lng->txt("or"));
				$this->tpl->setVariable("VALUE_SEARCH_TERM", $_POST["search_term"]);
				if (is_array($_POST["search_for"]))
				{
					if (in_array("usr", $_POST["search_for"]))
					{
						$this->tpl->setVariable("CHECKED_USERS", " checked=\"checked\"");
					}
					if (in_array("grp", $_POST["search_for"]))
					{
						$this->tpl->setVariable("CHECKED_GROUPS", " checked=\"checked\"");
					}
					if (in_array("role", $_POST["search_for"]))
					{
						$this->tpl->setVariable("CHECKED_ROLES", " checked=\"checked\"");
					}
				}
				if (strcmp($_POST["concatenation"], "and") == 0)
				{
					$this->tpl->setVariable("CHECKED_AND", " checked=\"checked\"");
				}
				else if (strcmp($_POST["concatenation"], "or") == 0)
				{
					$this->tpl->setVariable("CHECKED_OR", " checked=\"checked\"");
				}
				$this->tpl->setVariable("SEARCH", $this->lng->txt("search"));
				$this->tpl->parseCurrentBlock();
			}
		}
		if ($this->object->getInvitationMode() == MODE_PREDEFINED_USERS)
		{
			$invited_users = $this->object->getInvitedUsers();
			$buttons = array("disinvite");
			if (count($invited_users))
			{
				$this->outUserGroupTable("usr", $invited_users, "invited_user_result", "invited_user_row", $this->lng->txt("invited_users"), $buttons);
			}
		}
		if ($this->object->getInvitation() == INVITATION_ON)
		{
			$this->tpl->setCurrentBlock("invitation_mode");
			$this->tpl->setVariable("TEXT_MODE", $this->lng->txt("invitation_mode"));
			$this->tpl->setVariable("VALUE_UNLIMITED", $this->lng->txt("unlimited_users"));
			$this->tpl->setVariable("VALUE_PREDEFINED", $this->lng->txt("predefined_users"));
			if ($this->object->getInvitationMode() == MODE_PREDEFINED_USERS)
			{
				$this->tpl->setVariable("SELECTED_PREDEFINED", " selected=\"selected\"");
			}
			else
			{
				$this->tpl->setVariable("SELECTED_UNLIMITED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TEXT_INVITATION", $this->lng->txt("invitation"));
		$this->tpl->setVariable("VALUE_ON", $this->lng->txt("on"));
		$this->tpl->setVariable("VALUE_OFF", $this->lng->txt("off"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		if ($this->object->getInvitation() == INVITATION_ON)
		{
			$this->tpl->setVariable("SELECTED_ON", " selected=\"selected\"");
		}
		else
		{
			$this->tpl->setVariable("SELECTED_OFF", " selected=\"selected\"");
		}
    if ($rbacsystem->checkAccess("write", $this->ref_id) or $rbacsystem->checkAccess('invite', $this->ref_id)) {
			$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
			$this->tpl->setVariable("CANCEL", $this->lng->txt("cancel"));
		}
		$this->tpl->parseCurrentBlock();
	}

	/**
	* Creates a confirmation form for delete all user data
	*
	* Creates a confirmation form for delete all user data
	*
	* @access	private
	*/
	function deleteAllUserDataObject()
	{
		sendInfo($this->lng->txt("confirm_delete_all_user_data"));
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_maintenance.html", true);
		$this->tpl->setCurrentBlock("confirm_delete");
		$this->tpl->setVariable("BTN_CONFIRM_DELETE_ALL", $this->lng->txt("confirm"));
		$this->tpl->setVariable("BTN_CANCEL_DELETE_ALL", $this->lng->txt("cancel"));
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->parseCurrentBlock();
	}
	
	/**
	* Deletes all user data of the survey after confirmation
	*
	* Deletes all user data of the survey after confirmation
	*
	* @access	private
	*/
	function confirmDeleteAllUserDataObject()
	{
		$this->object->deleteAllUserData();
		sendInfo($this->lng->txt("svy_all_user_data_deleted"), true);
		$this->ctrl->redirect($this, "maintenance");
	}
	
	/**
	* Cancels delete of all user data in maintenance
	*
	* Cancels delete of all user data in maintenance
	*
	* @access	private
	*/
	function cancelDeleteAllUserDataObject()
	{
		$this->ctrl->redirect($this, "maintenance");
	}
	
	/**
	* Creates the maintenance form for a survey
	*
	* Creates the maintenance form for a survey
	*
	* @access	public
	*/
	function maintenanceObject()
	{
		global $rbacsystem;

		if ((!$rbacsystem->checkAccess("read", $this->ref_id)) && (!$rbacsystem->checkAccess("write", $this->ref_id))) 
		{
			// allow only read and write access
			sendInfo($this->lng->txt("cannot_edit_survey"), true);
			$path = $this->tree->getPathFull($this->object->getRefID());
			include_once "./classes/class.ilUtil.php";
			ilUtil::redirect($this->getReturnLocation("cancel","./repository.php?cmd=frameset&ref_id=" . $path[count($path) - 2]["child"]));
			return;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_maintenance.html", true);

		if ($rbacsystem->checkAccess("write", $this->ref_id))
		{
			$this->tpl->setCurrentBlock("delete_button");
			$this->tpl->setVariable("BTN_DELETE_ALL", $this->lng->txt("svy_delete_all_user_data"));
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("adm_content");
			$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			sendInfo($this->lng->txt("cannot_maintain_survey"));
		}
	}	

	/**
	* Creates the status output for a test
	*
	* Creates the status output for a test
	*
	* @access	public
	*/
	function statusObject()
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_status.html", true);
		if (!$this->object->isComplete())
		{
			if (count($this->object->questions) == 0)
			{
				$this->tpl->setCurrentBlock("list_element");
				$this->tpl->setVariable("TEXT_ELEMENT", $this->lng->txt("svy_missing_questions"));
				$this->tpl->parseCurrentBlock();
			}
			if (strcmp($this->object->author, "") == 0)
			{
				$this->tpl->setCurrentBlock("list_element");
				$this->tpl->setVariable("TEXT_ELEMENT", $this->lng->txt("svy_missing_author"));
				$this->tpl->parseCurrentBlock();
			}
			if (strcmp($this->object->title, "") == 0)
			{
				$this->tpl->setCurrentBlock("list_element");
				$this->tpl->setVariable("TEXT_ELEMENT", $this->lng->txt("svy_missing_author"));
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("status_list");
			$this->tpl->setVariable("TEXT_MISSING_ELEMENTS", $this->lng->txt("svy_status_missing_elements"));
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		if ($this->object->isComplete())
		{
			$this->tpl->setVariable("TEXT_STATUS_MESSAGE", $this->lng->txt("svy_status_ok"));
			$this->tpl->setVariable("STATUS_CLASS", "bold");
		}
		else
		{
			$this->tpl->setVariable("TEXT_STATUS_MESSAGE", $this->lng->txt("svy_status_missing"));
			$this->tpl->setVariable("STATUS_CLASS", "warning");
		}
		$this->tpl->parseCurrentBlock();
	}	

	/*
	* list all export files
	*/
	function exportObject()
	{
		global $tree;
		global $rbacsystem;

		if ((!$rbacsystem->checkAccess("read", $this->ref_id)) && (!$rbacsystem->checkAccess("write", $this->ref_id))) 
		{
			// allow only read and write access
			sendInfo($this->lng->txt("cannot_edit_survey"), true);
			$path = $this->tree->getPathFull($this->object->getRefID());
			include_once "./classes/class.ilUtil.php";
			ilUtil::redirect($this->getReturnLocation("cancel","./repository.php?ref_id=" . $path[count($path) - 2]["child"]));
			return;
		}

		//$this->setTabs();

		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// create export file button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "createExportFile"));
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("svy_create_export_file"));
		$this->tpl->parseCurrentBlock();

		$export_dir = $this->object->getExportDirectory();
		$export_files = $this->object->getExportFiles($export_dir);

		// create table
		include_once("./classes/class.ilTableGUI.php");
		$tbl = new ilTableGUI();

		// load files templates
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");

		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.export_file_row.html", true);

		$num = 0;

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$tbl->setTitle($this->lng->txt("svy_export_files"));

		$tbl->setHeaderNames(array("", $this->lng->txt("svy_file"),
			$this->lng->txt("svy_size"), $this->lng->txt("date") ));

		$tbl->enabled["sort"] = false;
		$tbl->setColumnWidth(array("1%", "49%", "25%", "25%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);		// ???

		$this->tpl->setVariable("COLUMN_COUNTS", 4);

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		//$tbl->disable("footer");

		$tbl->setMaxCount(count($export_files));
		$export_files = array_slice($export_files, $_GET["offset"], $_GET["limit"]);

		$tbl->render();
		if(count($export_files) > 0)
		{
			$i=0;
			foreach($export_files as $exp_file)
			{
				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("TXT_FILENAME", $exp_file);

				$css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
				$this->tpl->setVariable("CSS_ROW", $css_row);

				$this->tpl->setVariable("TXT_SIZE", filesize($export_dir."/".$exp_file));
				$this->tpl->setVariable("CHECKBOX_ID", $exp_file);

				$file_arr = explode("__", $exp_file);
				$this->tpl->setVariable("TXT_DATE", date("Y-m-d H:i:s",$file_arr[0]));

				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("selectall");
			$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
			$this->tpl->setVariable("CSS_ROW", $css_row);
			$this->tpl->parseCurrentBlock();
			// delete button
			$this->tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
			$this->tpl->setCurrentBlock("tbl_action_btn");
			$this->tpl->setVariable("BTN_NAME", "confirmDeleteExportFile");
			$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("delete"));
			$this->tpl->parseCurrentBlock();
	
			$this->tpl->setCurrentBlock("tbl_action_btn");
			$this->tpl->setVariable("BTN_NAME", "downloadExportFile");
			$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("download"));
			$this->tpl->parseCurrentBlock();	
		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", 3);
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->parseCurrentBlock();
	}

	/**
	* create export file
	*/
	function createExportFileObject()
	{
		global $rbacsystem;
		
		if ($rbacsystem->checkAccess("write", $this->ref_id))
		{
			include_once("./survey/classes/class.ilSurveyExport.php");
			$survey_exp = new ilSurveyExport($this->object);
			$survey_exp->buildExportFile();
			$this->ctrl->redirect($this, "export");
		}
		else
		{
			sendInfo("cannot_export_survey");
		}
	}

	/**
	* display dialogue for importing tests
	*
	* @access	public
	*/
	function importObject()
	{
		$this->getTemplateFile("import", "svy");
		$this->tpl->setCurrentBlock("option_qpl");
		include_once("./survey/classes/class.ilObjSurvey.php");
		$svy = new ilObjSurvey();
		$questionpools =& $svy->getAvailableQuestionpools(true);
		if (count($questionpools) == 0)
		{
		}
		else
		{
			foreach ($questionpools as $key => $value)
			{
				$this->tpl->setCurrentBlock("option_spl");
				$this->tpl->setVariable("OPTION_VALUE", $key);
				$this->tpl->setVariable("TXT_OPTION", $value);
				$this->tpl->parseCurrentBlock();
			}
		}
		$this->tpl->setVariable("TXT_SELECT_QUESTIONPOOL", $this->lng->txt("select_questionpool"));
		$this->tpl->setVariable("OPTION_SELECT_QUESTIONPOOL", $this->lng->txt("select_questionpool_option"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
//		$this->tpl->setVariable("FORMACTION", "adm_object.php?&ref_id=".$_GET["ref_id"]."&cmd=gateway&new_type=".$this->type);
		$this->tpl->setVariable("BTN_NAME", "upload");
		$this->tpl->setVariable("TXT_UPLOAD", $this->lng->txt("upload"));
		$this->tpl->setVariable("TXT_IMPORT_TST", $this->lng->txt("import_tst"));
		$this->tpl->setVariable("TXT_SELECT_MODE", $this->lng->txt("select_mode"));
		$this->tpl->setVariable("TXT_SELECT_FILE", $this->lng->txt("select_file"));

	}

	/**
	* display status information or report errors messages
	* in case of error
	*
	* @access	public
	*/
	function uploadObject($redirect = true)
	{
		if ($_POST["spl"] < 1)
		{
			sendInfo($this->lng->txt("svy_select_questionpools"));
			$this->importObject();
			return;
		}
		if (strcmp($_FILES["xmldoc"]["tmp_name"], "") == 0)
		{
			sendInfo($this->lng->txt("svy_select_file_for_import"));
			$this->importObject();
			return;
		}
		include_once("./survey/classes/class.ilObjSurvey.php");
		$newObj = new ilObjSurvey();
		$newObj->setType($_GET["new_type"]);
		$newObj->setTitle("dummy");
		$newObj->setDescription("dummy");
		$newObj->create(true);
		$newObj->createReference();
		$newObj->putInTree($_GET["ref_id"]);
		$newObj->setPermissions($_GET["ref_id"]);
		$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());

		// copy uploaded file to import directory
		$newObj->importObject($_FILES["xmldoc"], $_POST["spl"]);

		$newObj->update();
		$newObj->saveToDb();
		if ($redirect)
		{
			include_once "./classes/class.ilUtil.php";
			ilUtil::redirect($this->getReturnLocation("upload",$this->ctrl->getTargetScript()."?".$this->link_params));
		}
		return $newObj->getRefId();
	}

	/**
	* form for new content object creation
	*/
	function createObject()
	{
		global $rbacsystem;
		$new_type = $_POST["new_type"] ? $_POST["new_type"] : $_GET["new_type"];
		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $new_type))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			$this->getTemplateFile("create", $new_type);

			include_once("./survey/classes/class.ilObjSurvey.php");
			$svy = new ilObjSurvey();
			
			$surveys =& ilObjSurvey::_getAvailableSurveys(true);
			if (count($surveys) > 0)
			{
				foreach ($surveys as $key => $value)
				{
					$this->tpl->setCurrentBlock("option_svy");
					$this->tpl->setVariable("OPTION_VALUE_SVY", $key);
					$this->tpl->setVariable("TXT_OPTION_SVY", $value);
					if ($_POST["svy"] == $key)
					{
						$this->tpl->setVariable("OPTION_SELECTED_SVY", " selected=\"selected\"");				
					}
					$this->tpl->parseCurrentBlock();
				}
			}
			
			$questionpools =& $svy->getAvailableQuestionpools($use_obj_id = true, $could_be_offline = true);
			if (count($questionpools) > 0)
			{
				foreach ($questionpools as $key => $value)
				{
					$this->tpl->setCurrentBlock("option_spl");
					$this->tpl->setVariable("OPTION_VALUE", $key);
					$this->tpl->setVariable("TXT_OPTION", $value);
					if ($_POST["spl"] == $key)
					{
						$this->tpl->setVariable("OPTION_SELECTED", " selected=\"selected\"");				
					}
					$this->tpl->parseCurrentBlock();
				}
			}
			// fill in saved values in case of error
			$data = array();
			$data["fields"] = array();
			include_once "./classes/class.ilUtil.php";
			$data["fields"]["title"] = ilUtil::prepareFormOutput($_SESSION["error_post_vars"]["Fobject"]["title"],true);
			$data["fields"]["desc"] = ilUtil::prepareFormOutput($_SESSION["error_post_vars"]["Fobject"]["desc"]);

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);

				if ($this->prepare_output)
				{
					$this->tpl->parseCurrentBlock();
				}
			}

			$this->ctrl->setParameter($this, "new_type", $this->type);
//			$this->tpl->setVariable("FORMACTION", $this->getFormAction("save","adm_object.php?cmd=gateway&ref_id=".
//																	   $_GET["ref_id"]."&new_type=".$new_type));
			$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
			$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($new_type."_new"));
			$this->tpl->setVariable("TXT_SELECT_QUESTIONPOOL", $this->lng->txt("select_questionpool_short"));
			$this->tpl->setVariable("OPTION_SELECT_QUESTIONPOOL", $this->lng->txt("select_questionpool_option"));
			$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt($new_type."_add"));
			$this->tpl->setVariable("CMD_SUBMIT", "save");
			$this->tpl->setVariable("TARGET", ' target="'.
				ilFrameTargetInfo::_getFrame("MainContent").'" ');
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));

			$this->tpl->setVariable("TXT_IMPORT_SVY", $this->lng->txt("import_svy"));
			$this->tpl->setVariable("TXT_SVY_FILE", $this->lng->txt("svy_upload_file"));
			$this->tpl->setVariable("TXT_IMPORT", $this->lng->txt("import"));

			$this->tpl->setVariable("TXT_DUPLICATE_SVY", $this->lng->txt("duplicate_svy"));
			$this->tpl->setVariable("TXT_SELECT_SVY", $this->lng->txt("obj_svy"));
			$this->tpl->setVariable("OPTION_SELECT_SVY", $this->lng->txt("select_svy_option"));
			$this->tpl->setVariable("TXT_DUPLICATE", $this->lng->txt("duplicate"));
		}
	}
	
	/**
	* form for new survey object duplication
	*/
	function cloneAllObject()
	{
		if ($_POST["svy"] < 1)
		{
			sendInfo($this->lng->txt("svy_select_surveys"));
			$this->createObject();
			return;
		}
		include_once "./survey/classes/class.ilObjSurvey.php";
		include_once "./classes/class.ilUtil.php";
		$ref_id = ilObjSurvey::_clone($_POST["svy"]);
		// always send a message
		sendInfo($this->lng->txt("object_duplicated"),true);

		ilUtil::redirect("ilias.php?ref_id=".$ref_id.
			"&baseClass=ilObjSurveyGUI");
	}
	
	/**
	* form for new survey object import
	*/
	function importFileObject()
	{
		if ($_POST["spl"] < 1)
		{
			sendInfo($this->lng->txt("svy_select_questionpools"));
			$this->createObject();
			return;
		}
		if (strcmp($_FILES["xmldoc"]["tmp_name"], "") == 0)
		{
			sendInfo($this->lng->txt("svy_select_file_for_import"));
			$this->createObject();
			return;
		}
		$this->ctrl->setParameter($this, "new_type", $this->type);
		$ref_id = $this->uploadObject(false);
		// always send a message
		sendInfo($this->lng->txt("object_imported"),true);

		ilUtil::redirect("ilias.php?ref_id=".$ref_id.
			"&baseClass=ilObjSurveyGUI");
//		$this->ctrl->redirect($this, "importFile");
	}

	/**
	* download export file
	*/
	function downloadExportFileObject()
	{
		if(!isset($_POST["file"]))
		{
			sendInfo($this->lng->txt("no_checkbox"), true);
			$this->ctrl->redirect($this, "export");
		}

		if (count($_POST["file"]) > 1)
		{
			sendInfo($this->lng->txt("select_max_one_item"), true);
			$this->ctrl->redirect($this, "export");
		}


		$export_dir = $this->object->getExportDirectory();
		include_once "./classes/class.ilUtil.php";
		ilUtil::deliverFile($export_dir."/".$_POST["file"][0],
			$_POST["file"][0]);
	}

	/**
	* confirmation screen for export file deletion
	*/
	function confirmDeleteExportFileObject()
	{
		if(!isset($_POST["file"]))
		{
			sendInfo($this->lng->txt("no_checkbox"), true);
			$this->ctrl->redirect($this, "export");
		}

		//$this->setTabs();

		// SAVE POST VALUES
		$_SESSION["ilExportFiles"] = $_POST["file"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm_deletion.html", true);

		sendInfo($this->lng->txt("info_delete_sure"));

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		// BEGIN TABLE HEADER
		$this->tpl->setCurrentBlock("table_header");
		$this->tpl->setVariable("TEXT",$this->lng->txt("objects"));
		$this->tpl->parseCurrentBlock();

		// BEGIN TABLE DATA
		$counter = 0;
		include_once "./classes/class.ilUtil.php";
		foreach($_POST["file"] as $file)
		{
				$this->tpl->setCurrentBlock("table_row");
				$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_file.gif"));
				$this->tpl->setVariable("TEXT_IMG_OBJ", $this->lng->txt("file_icon"));
				$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
				$this->tpl->setVariable("TEXT_CONTENT", $file);
				$this->tpl->parseCurrentBlock();
		}

		// cancel/confirm button
		$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$buttons = array( "cancelDeleteExportFile"  => $this->lng->txt("cancel"),
			"deleteExportFile"  => $this->lng->txt("confirm"));
		foreach ($buttons as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
	}


	/**
	* cancel deletion of export files
	*/
	function cancelDeleteExportFileObject()
	{
		session_unregister("ilExportFiles");
		$this->ctrl->redirect($this, "export");
	}


	/**
	* delete export files
	*/
	function deleteExportFileObject()
	{
		$export_dir = $this->object->getExportDirectory();
		foreach($_SESSION["ilExportFiles"] as $file)
		{
			$exp_file = $export_dir."/".$file;
			$exp_dir = $export_dir."/".substr($file, 0, strlen($file) - 4);
			if (@is_file($exp_file))
			{
				unlink($exp_file);
			}
			if (@is_dir($exp_dir))
			{
				include_once "./classes/class.ilUtil.php";
				ilUtil::delDir($exp_dir);
			}
		}
		$this->ctrl->redirect($this, "export");
	}

	/**
	* Display the survey access codes tab
	*
	* Display the survey access codes tab
	*
	* @access private
	*/
	function codesObject()
	{
		global $rbacsystem;

		if ((!$rbacsystem->checkAccess("read", $this->ref_id)) && (!$rbacsystem->checkAccess("write", $this->ref_id))) 
		{
			// allow only read and write access
			sendInfo($this->lng->txt("cannot_edit_survey"), true);
			$path = $this->tree->getPathFull($this->object->getRefID());
			include_once "./classes/class.ilUtil.php";
			ilUtil::redirect($this->getReturnLocation("cancel","./repository.php?ref_id=" . $path[count($path) - 2]["child"]));
			return;
		}
		
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_codes.html", true);
		if ($rbacsystem->checkAccess("write", $this->ref_id))
		{
			$color_class = array("tblrow1", "tblrow2");
			$survey_codes =& $this->object->getSurveyCodes();
			if (count($survey_codes) == 0)
			{
				$this->tpl->setCurrentBlock("emptyrow");
				$this->tpl->setVariable("COLOR_CLASS", "tblrow1");
				$this->tpl->setVariable("NO_CODES", $this->lng->txt("survey_code_no_codes"));
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$counter = 1;
				foreach ($survey_codes as $key => $row)
				{
					$this->tpl->setCurrentBlock("coderow");
					$this->tpl->setVariable("COLOR_CLASS", $color_class[$key % 2]);
					$this->tpl->setVariable("CODE_SEQUENCE", $counter);
					$this->tpl->setVariable("SURVEY_CODE", $row["survey_key"]);
					include_once "./classes/class.ilFormat.php";
					$this->tpl->setVariable("CODE_CREATED", ilFormat::formatDate(ilFormat::ftimestamp2dateDB($row["TIMESTAMP14"]), "date"));
					$state = "<span class=\"smallred\">" . $this->lng->txt("not_used") . "</span>";
					if ($this->object->isSurveyCodeUsed($row["survey_key"]))
					{
						$state = "<span class=\"smallgreen\">" . $this->lng->txt("used") . "</span>";
					}
					else
					{
						$this->tpl->setVariable("CODE_URL_NAME", $this->lng->txt("survey_code_url_name"));
						$this->tpl->setVariable("CODE_URL", ILIAS_HTTP_PATH."/goto.php?cmd=run&target=svy_".$this->object->getRefId() . "&client_id=" . CLIENT_ID . "&accesscode=".$row["survey_key"]);
					}
					$this->tpl->setVariable("CODE_USED", $state);
					$this->tpl->parseCurrentBlock();
					$counter++;
				}
			}
			$this->tpl->setCurrentBlock("adm_content");
			$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
			$this->tpl->setVariable("SURVEY_CODE", $this->lng->txt("survey_code"));
			$this->tpl->setVariable("CODE_CREATED", $this->lng->txt("create_date"));
			$this->tpl->setVariable("CODE_USED", $this->lng->txt("survey_code_used"));
			$this->tpl->setVariable("CODE_URL", $this->lng->txt("survey_code_url"));
			$this->tpl->setVariable("TEXT_CREATE", $this->lng->txt("create"));
			$this->tpl->setVariable("TEXT_SURVEY_CODES", $this->lng->txt("new_survey_codes"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			sendInfo($this->lng->txt("cannot_create_survey_codes"));
		}
	}
	
	/**
	* Create access codes for the survey
	*
	* Create access codes for the survey
	*
	* @access private
	*/
	function createSurveyCodesObject()
	{
		if (preg_match("/\d+/", $_POST["nrOfCodes"]))
		{
			$this->object->createSurveyCodes($_POST["nrOfCodes"]);
		}
		else
		{
			sendInfo($this->lng->txt("enter_valid_number_of_codes"), true);
		}
		$this->ctrl->redirect($this, "codes");
	}

	/**
	* Display the form to add preconditions for survey questions
	*
	* Display the form to add preconditions for survey questions
	*
	* @access private
	*/
	function addConstraintForm($step, &$survey_questions, $questions = false)
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_add_constraint.html", true);
		if (is_array($questions))
		{
			foreach ($questions as $question)
			{
				$this->tpl->setCurrentBlock("option_q");
				$this->tpl->setVariable("OPTION_VALUE", $question["question_id"]);
				$this->tpl->setVariable("OPTION_TEXT", $question["title"] . " (" . $this->lng->txt($question["type_tag"]) . ")");
				if ($question["question_id"] == $_POST["q"])
				{
					$this->tpl->setVariable("OPTION_CHECKED", " selected=\"selected\"");
				}
				$this->tpl->parseCurrentBlock();
			}
		}
		
		if ($step > 1)
		{
			$relations = $this->object->getAllRelations();
			switch ($survey_questions[$_POST["q"]]["type_tag"])
			{
				case "qt_nominal":
					foreach ($relations as $rel_id => $relation)
					{
						if ((strcmp($relation["short"], "=") == 0) or (strcmp($relation["short"], "<>") == 0))
						{
							$this->tpl->setCurrentBlock("option_r");
							$this->tpl->setVariable("OPTION_VALUE", $rel_id);
							$this->tpl->setVariable("OPTION_TEXT", $relation["short"]);
							if ($rel_id == $_POST["r"])
							{
								$this->tpl->setVariable("OPTION_CHECKED", " selected=\"selected\"");
							}
							$this->tpl->parseCurrentBlock();
						}
					}
					break;
				case "qt_ordinal":
				case "qt_metric":
					foreach ($relations as $rel_id => $relation)
					{
						$this->tpl->setCurrentBlock("option_r");
						$this->tpl->setVariable("OPTION_VALUE", $rel_id);
						$this->tpl->setVariable("OPTION_TEXT", $relation["short"]);
						if ($rel_id == $_POST["r"])
						{
							$this->tpl->setVariable("OPTION_CHECKED", " selected=\"selected\"");
						}
						$this->tpl->parseCurrentBlock();
					}
					break;
			}
			$this->tpl->setCurrentBlock("select_relation");
			$this->tpl->setVariable("SELECT_RELATION", $this->lng->txt("step") . " 2: " . $this->lng->txt("select_relation"));
			$this->tpl->parseCurrentBlock();
		}
		
		if ($step > 2)
		{
			$variables =& $this->object->getVariables($_POST["q"]);
			switch ($survey_questions[$_POST["q"]]["type_tag"])
			{
				case "qt_nominal":
				case "qt_ordinal":
					foreach ($variables as $sequence => $row)
					{
						$this->tpl->setCurrentBlock("option_v");
						$this->tpl->setVariable("OPTION_VALUE", $sequence);
						$this->tpl->setVariable("OPTION_TEXT", ($sequence+1) . " - " . $row->title);
						$this->tpl->parseCurrentBlock();
					}
					break;
				case "qt_metric":
						$this->tpl->setCurrentBlock("textfield");
						$this->tpl->setVariable("TEXTFIELD_VALUE", "");
						$this->tpl->parseCurrentBlock();
					break;
			}
			$this->tpl->setCurrentBlock("select_value");
			if (strcmp($survey_questions[$_POST["q"]]["type_tag"], "qt_metric") == 0)
			{
				$this->tpl->setVariable("SELECT_VALUE", $this->lng->txt("step") . " 3: " . $this->lng->txt("enter_value"));
			}
			else
			{
				$this->tpl->setVariable("SELECT_VALUE", $this->lng->txt("step") . " 3: " . $this->lng->txt("select_value"));
			}
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setCurrentBlock("buttons");
		$this->tpl->setVariable("BTN_CONTINUE", $this->lng->txt("continue"));
		switch ($step)
		{
			case 1:
				$this->tpl->setVariable("COMMAND", "constraintStep2");
				$this->tpl->setVariable("COMMAND_BACK", "constraints");
				break;
			case 2:
				$this->tpl->setVariable("COMMAND", "constraintStep3");
				$this->tpl->setVariable("COMMAND_BACK", "constraintStep1");
				break;
			case 3:
				$this->tpl->setVariable("COMMAND", "constraintsAdd");
				$this->tpl->setVariable("COMMAND_BACK", "constraintStep2");
				break;
		}
		$this->tpl->setVariable("BTN_BACK", $this->lng->txt("back"));
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("adm_content");
		$title = "";
		if ($survey_questions[$_SESSION["constraintstructure"][$_GET["start"]][0]]["questionblock_id"] > 0)
		{
			$title = $this->lng->txt("questionblock") . ": " . $survey_questions[$_SESSION["constraintstructure"][$_GET["start"]][0]]["questionblock_title"];
		}
		else
		{
			$title = $this->lng->txt($survey_questions[$_SESSION["constraintstructure"][$_GET["start"]][0]]["type_tag"]) . ": " . $survey_questions[$_SESSION["constraintstructure"][$_GET["start"]][0]]["title"];
		}
		$this->tpl->setVariable("CONSTRAINT_QUESTION_TEXT", $title);
		$this->tpl->setVariable("SELECT_PRIOR_QUESTION", $this->lng->txt("step") . " 1: " . $this->lng->txt("select_prior_question"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this) . "&start=" . $_GET["start"]);
		$this->tpl->parseCurrentBlock();
	}
	
	/**
	* Add a precondition for a survey question or question block
	*
	* Add a precondition for a survey question or question block
	*
	* @access private
	*/
	function constraintsAddObject()
	{
		$survey_questions =& $this->object->getSurveyQuestions();
		$structure =& $_SESSION["constraintstructure"];
		$include_elements = $_SESSION["includeElements"];
		foreach ($include_elements as $elementCounter)
		{
			if (is_array($structure[$elementCounter]))
			{
				foreach ($structure[$elementCounter] as $key => $question_id)
				{
					$this->object->addConstraint($question_id, $_POST["q"], $_POST["r"], $_POST["v"]);
				}
			}
		}
		unset($_SESSION["includeElements"]);
		unset($_SESSION["constraintstructure"]);
		$this->ctrl->redirect($this, "constraints");
	}

	/**
	* Handles the third step of the precondition add action
	*
	* Handles the third step of the precondition add action
	*
	* @access private
	*/
	function constraintStep3Object()
	{
		$survey_questions =& $this->object->getSurveyQuestions();
		$option_questions = array();
		array_push($option_questions, array("question_id" => $_POST["q"], "title" => $survey_questions[$_POST["q"]]["title"], "type_tag" => $survey_questions[$_POST["q"]]["type_tag"]));
		$this->addConstraintForm(3, $survey_questions, $option_questions);
	}
	
	/**
	* Handles the second step of the precondition add action
	*
	* Handles the second step of the precondition add action
	*
	* @access private
	*/
	function constraintStep2Object()
	{
		$survey_questions =& $this->object->getSurveyQuestions();
		$option_questions = array();
		array_push($option_questions, array("question_id" => $_POST["q"], "title" => $survey_questions[$_POST["q"]]["title"], "type_tag" => $survey_questions[$_POST["q"]]["type_tag"]));
		$this->addConstraintForm(2, $survey_questions, $option_questions);
	}
	
	/**
	* Handles the first step of the precondition add action
	*
	* Handles the first step of the precondition add action
	*
	* @access private
	*/
	function constraintStep1Object()
	{
		$survey_questions =& $this->object->getSurveyQuestions();
		$structure =& $_SESSION["constraintstructure"];
		$start = $_GET["start"];
		$option_questions = array();
		for ($i = 1; $i < $start; $i++)
		{
			if (is_array($structure[$i]))
			{
				foreach ($structure[$i] as $key => $question_id)
				{
					if (strcmp($survey_questions[$question_id]["type_tag"], "qt_text") != 0)
					{
						array_push($option_questions, array("question_id" => $survey_questions[$question_id]["question_id"], "title" => $survey_questions[$question_id]["title"], "type_tag" => $survey_questions[$question_id]["type_tag"]));
					}
				}
			}
		}
		if (count($option_questions) == 0)
		{
			unset($_SESSION["includeElements"]);
			unset($_SESSION["constraintstructure"]);
			sendInfo($this->lng->txt("constraints_no_nonessay_available"), true);
			$this->ctrl->redirect($this, "constraints");
		}
		$this->addConstraintForm(1, $survey_questions, $option_questions);
	}
	
	/**
	* Delete constraints of a survey
	*
	* Delete constraints of a survey
	*
	* @access private
	*/
	function deleteConstraintsObject()
	{
		$survey_questions =& $this->object->getSurveyQuestions();
		$structure =& $_SESSION["constraintstructure"];
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/^constraint_(\d+)_(\d+)/", $key, $matches)) 
			{
				foreach ($structure[$matches[1]] as $key => $question_id)
				{
					$this->object->deleteConstraint($matches[2], $question_id);
				}
			}
		}

		$this->ctrl->redirect($this, "constraints");
	}
	
	function createConstraintsObject()
	{
		$include_elements = $_POST["includeElements"];
		if ((!is_array($include_elements)) || (count($include_elements) == 0))
		{
			sendInfo($this->lng->txt("constraints_no_questions_or_questionblocks_selected"), true);
			$this->ctrl->redirect($this, "constraints");
		}
		else if (count($include_elements) >= 1)
		{
			$_SESSION["includeElements"] = $include_elements;
			sort($include_elements, SORT_NUMERIC);
			$_GET["start"] = $include_elements[0];
			$this->constraintStep1Object();
		}
	}
	
	/**
	* Administration page for survey constraints
	*
	* Administration page for survey constraints
	*
	* @access public
	*/
	function constraintsObject()
	{
		global $rbacsystem;
		
		$step = 0;
		if (array_key_exists("step", $_GET))	$step = $_GET["step"];
		switch ($step)
		{
			case 1:
				$this->constraintStep1Object();
				return;
				break;
			case 2:
				return;
				break;
			case 3:
				return;
				break;
		}
		
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_constraints_list.html", true);
		$survey_questions =& $this->object->getSurveyQuestions();
		$last_questionblock_title = "";
		$counter = 1;
		$structure = array();
		$colors = array("tblrow1", "tblrow2");
		foreach ($survey_questions as $question_id => $data)
		{
			$title = $data["title"];
			$show = true;
			if ($data["questionblock_id"] > 0)
			{
				$title = $data["questionblock_title"];
				$type = $this->lng->txt("questionblock");
				if (strcmp($title, $last_questionblock_title) != 0) 
				{
					$last_questionblock_title = $title;
					$structure[$counter] = array();
					array_push($structure[$counter], $data["question_id"]);
				}
				else
				{
					array_push($structure[$counter-1], $data["question_id"]);
					$show = false;
				}
			}
			else
			{
				$structure[$counter] = array($data["question_id"]);
				$type = $this->lng->txt("question");
			}
			if ($show)
			{
				if ($counter == 1)
				{
					$this->tpl->setCurrentBlock("description");
					$this->tpl->setVariable("DESCRIPTION", $this->lng->txt("constraints_first_question_description"));
					$this->tpl->parseCurrentBlock();
				}
				else
				{
					$constraints =& $this->object->getConstraints($data["question_id"]);
					$rowcount = 0;
					if (count($constraints))
					{
						foreach ($constraints as $constraint)
						{
							$value = "";
							$variables =& $this->object->getVariables($constraint["question"]);
							switch ($survey_questions[$constraint["question"]]["type_tag"])
							{
								case "qt_metric":
									$value = $constraint["value"];
									break;
								case "qt_nominal":
								case "qt_ordinal":
									$value = sprintf("%d", $constraint["value"]+1) . " - " . $variables[$constraint["value"]]->title;
									break;
							}
							$this->tpl->setCurrentBlock("constraint");
							$this->tpl->setVariable("CONSTRAINT_TEXT", $survey_questions[$constraint["question"]]["title"] . " " . $constraint["short"] . " $value");
							$this->tpl->setVariable("SEQUENCE_ID", $counter);
							$this->tpl->setVariable("CONSTRAINT_ID", $constraint["id"]);
							$this->tpl->setVariable("COLOR_CLASS", $colors[$rowcount % 2]);
							$rowcount++;
							$this->tpl->parseCurrentBlock();
						}
						if ($rbacsystem->checkAccess("write", $this->ref_id) && ($this->object->isOffline())) 
						{
							$this->tpl->setCurrentBlock("delete_button");
							$this->tpl->setVariable("BTN_DELETE", $this->lng->txt("delete"));
							include_once "./classes/class.ilUtil.php";
							$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
							$this->tpl->parseCurrentBlock();
						}
					}
					else
					{
						$this->tpl->setCurrentBlock("empty_row");
						$this->tpl->setVariable("EMPTY_TEXT", $this->lng->txt("no_available_constraints"));
						$this->tpl->setVariable("COLOR_CLASS", $colors[$rowcount % 2]);
						$this->tpl->parseCurrentBlock();
					}
					$this->tpl->setCurrentBlock("question");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->setVariable("DEFINED_PRECONDITIONS", $this->lng->txt("existing_constraints"));
					$this->tpl->parseCurrentBlock();
				}
				if ($counter != 1)
				{
					$this->tpl->setCurrentBlock("include_elements");
					$this->tpl->setVariable("QUESTION_NR", "$counter");
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock("constraint_section");
				$this->tpl->setVariable("QUESTION_NR", "$counter");
				$this->tpl->setVariable("TITLE", "$title");
				$icontype = "question.gif";
				if ($data["questionblock_id"] > 0)
				{
					$icontype = "questionblock.gif";
					$this->tpl->setVariable("TYPE", "$type: ");
				}
				include_once "./classes/class.ilUtil.php";
				$this->tpl->setVariable("ICON_HREF", ilUtil::getImagePath($icontype, true));
				$this->tpl->setVariable("ICON_ALT", $type);
				$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
				$this->tpl->parseCurrentBlock();
				$counter++;
			}
		}
		if ($rbacsystem->checkAccess("write", $this->ref_id) and ($this->object->isOffline())) 
		{
			$this->tpl->setCurrentBlock("selectall");
			$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
			$counter++;
			$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("buttons");
			$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
			$this->tpl->setVariable("BTN_CREATE_CONSTRAINTS", $this->lng->txt("constraint_add"));
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("CONSTRAINTS_INTRODUCTION", $this->lng->txt("constraints_introduction"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("CONSTRAINTS_HEADER", $this->lng->txt("constraints_list_of_entities"));
		$this->tpl->parseCurrentBlock();
		$_SESSION["constraintstructure"] = $structure;
	}

	function addLocatorItems()
	{
		global $ilLocator;
		switch ($this->ctrl->getCmd())
		{
			case "run":
				$ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTargetByClass("ilsurveyexecutiongui", "run"));
				break;
			case "evaluation":
			case "checkEvaluationAccess":
			case "evaluationdetails":
			case "evaluationuser":
				$ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTargetByClass("ilsurveyevaluationgui", "evaluation"));
				break;
			case "create":
			case "save":
			case "cancel":
			case "importFile":
			case "cloneAll":
				break;
		default:
				$ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTarget($this, ""));
				break;
		}
	}
	
	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		switch ($this->ctrl->getCmd())
		{
			case "run":
			case "start":
			case "resume":
			case "next":
			case "previous":
				return;
				break;
		}
		
		// properties
		$force_active = ($this->ctrl->getCmd() == "")
			? true
			: false;
		$tabs_gui->addTarget("properties",
			 $this->ctrl->getLinkTarget($this,'properties'),
			 array("properties", "save", "cancel"), "",
			 "", $force_active);

		// questions
		$force_active = ($_GET["up"] != "" || $_GET["down"] != "")
			? true
			: false;

		$tabs_gui->addTarget("survey_questions",
			 $this->ctrl->getLinkTarget($this,'questions'),
			 array("questions", "browseForQuestions", "searchQuestions", "createQuestion",
			 "searchQuestionsExecute",
			 "filterQuestions", "resetFilterQuestions", "changeDatatype", "insertQuestions",
			 "removeQuestions", "cancelRemoveQuestions", "confirmRemoveQuestions",
			 "defineQuestionblock", "saveDefineQuestionblock", "cancelDefineQuestionblock",
			 "unfoldQuestionblock", "moveQuestions",
			 "insertQuestionsBefore", "insertQuestionsAfter", "saveObligatory",
			 "addHeading", "saveHeading", "cancelHeading", "editHeading",
			 "confirmRemoveHeading", "cancelRemoveHeading"),
			 "", "", $force_active);
			 
		// constraints
		$tabs_gui->addTarget("constraints",
			 $this->ctrl->getLinkTarget($this, "constraints"),
			 array("constraints", "constraintStep1", "constraintStep2",
			 "constraintStep3", "constraintsAdd", "createConstraints"),
			 "");
			 
		// invite
		$tabs_gui->addTarget("invite_participants",
			 $this->ctrl->getLinkTarget($this, "invite"),
			 array("invite", "saveInvitationStatus",
			 "cancelInvitationStatus", "searchInvitation", "inviteUserGroup",
			 "disinviteUserGroup"),
			 "");

		// export
		$tabs_gui->addTarget("export",
			 $this->ctrl->getLinkTarget($this,'export'),
			 array("export", "createExportFile", "confirmDeleteExportFile",
			 "downloadExportFile"), 
			 ""
			);

		// maintenance
		$tabs_gui->addTarget("maintenance",
			 $this->ctrl->getLinkTarget($this,'maintenance'),
			 array("maintenance", "deleteAllUserData"),
			 "");

		// status
		$tabs_gui->addTarget("status",
			 $this->ctrl->getLinkTarget($this,'status'),
			 array("status"),
			 "");
			
		// code
		$tabs_gui->addTarget("codes",
			 $this->ctrl->getLinkTarget($this,'codes'),
			 array("codes", "createSurveyCodes"),
			 "");

		// permissions
		$tabs_gui->addTarget("perm_settings",
			$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), array("perm","info","owner"), 'ilpermissiongui');
			 
		// meta data
		$tabs_gui->addTarget("meta_data",
			 $this->ctrl->getLinkTargetByClass('ilmdeditorgui','listSection'),
			 "", "ilmdeditorgui");
	}
	
} // END class.ilObjSurveyGUI
?>
