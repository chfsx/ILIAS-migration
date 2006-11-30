<?php
 /*
   +----------------------------------------------------------------------------+
   | ILIAS open source                                                          |
   +----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne           |
   |                                                                            |
   | This program is free software; you can redistribute it and/or              |
   | modify it under the terms of the GNU General Public License                |
   | as published by the Free Software Foundation; either version 2             |
   | of the License, or (at your option) any later version.                     |
   |                                                                            |
   | This program is distributed in the hope that it will be useful,            |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of             |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              |
   | GNU General Public License for more details.                               |
   |                                                                            |
   | You should have received a copy of the GNU General Public License          |
   | along with this program; if not, write to the Free Software                |
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. |
   +----------------------------------------------------------------------------+
*/

include_once "./Modules/Survey/classes/inc.SurveyConstants.php";

/**
* Survey evaluation graphical output
*
* The ilSurveyEvaluationGUI class creates the evaluation output for the ilObjSurveyGUI
* class. This saves some heap space because the ilObjSurveyGUI class will be
* smaller.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @ingroup ModulesSurvey
*/
class ilSurveyEvaluationGUI
{
	var $object;
	var $lng;
	var $tpl;
	var $ctrl;
	
/**
* ilSurveyEvaluationGUI constructor
*
* The constructor takes possible arguments an creates an instance of the ilSurveyEvaluationGUI object.
*
* @param object $a_object Associated ilObjSurvey class
* @access public
*/
  function ilSurveyEvaluationGUI($a_object)
  {
		global $lng, $tpl, $ilCtrl;

    $this->lng =& $lng;
    $this->tpl =& $tpl;
		$this->ctrl =& $ilCtrl;
		$this->object =& $a_object;
	}
	
	/**
	* execute command
	*/
	function &executeCommand()
	{
		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass($this);

		$cmd = $this->getCommand($cmd);
		switch($next_class)
		{
			default:
				$ret =& $this->$cmd();
				break;
		}
		return $ret;
	}

	function getCommand($cmd)
	{
		return $cmd;
	}

	/**
	* Show the detailed evaluation
	*
	* Show the detailed evaluation
	*
	* @access private
	*/
	function checkAnonymizedEvaluationAccess()
	{
		global $rbacsystem;
		global $ilUser;
		
		if ($rbacsystem->checkAccess("write", $_GET["ref_id"]))
		{
			// people with write access always have access to the evaluation
			$_SESSION["anon_evaluation_access"] = 1;
			return $this->evaluation();
		}
		if ($this->object->getEvaluationAccess() == EVALUATION_ACCESS_ALL)
		{
			// if the evaluation access is open for all users, grant it
			$_SESSION["anon_evaluation_access"] = 1;
			return $this->evaluation();
		}
		$surveycode = $this->object->getUserAccessCode($ilUser->getId());
		if ($this->object->isAnonymizedParticipant($surveycode))
		{
			$_SESSION["anon_evaluation_access"] = 1;
			return $this->evaluation();
		}
		$this->tpl->setVariable("TABS", "");
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_evaluation_checkaccess.html", "Modules/Survey");
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("AUTHENTICATION_NEEDED", $this->lng->txt("svy_check_evaluation_authentication_needed"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("EVALUATION_CHECKACCESS_INTRODUCTION", $this->lng->txt("svy_check_evaluation_access_introduction"));
		$this->tpl->setVariable("VALUE_CHECK", $this->lng->txt("ok"));
		$this->tpl->setVariable("VALUE_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("TEXT_SURVEY_CODE", $this->lng->txt("survey_code"));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* Checks the evaluation access after entering the survey access code
	*
	* Checks the evaluation access after entering the survey access code
	*
	* @access private
	*/
	function checkEvaluationAccess()
	{
		$surveycode = $_POST["surveycode"];
		if ($this->object->isAnonymizedParticipant($surveycode))
		{
			$_SESSION["anon_evaluation_access"] = 1;
			$this->evaluation();
		}
		else
		{
			sendInfo($this->lng->txt("svy_check_evaluation_wrong_key", true));
			$this->cancelEvaluationAccess();
		}
	}
	
	/**
	* Cancels the input of the survey access code for evaluation access
	*
	* Cancels the input of the survey access code for evaluation access
	*
	* @access private
	*/
	function cancelEvaluationAccess()
	{
		include_once "./classes/class.ilUtil.php";
		global $tree;
		$path = $tree->getPathFull($this->object->getRefID());
		ilUtil::redirect("repository.php?cmd=frameset&ref_id=" . $path[count($path) - 2]["child"]);
	}
	
	/**
	* Show the detailed evaluation
	*
	* Show the detailed evaluation
	*
	* @access private
	*/
	function evaluationdetails()
	{
		$this->evaluation(1);
	}
	
	function exportCumulatedResults($details = 0)
	{
		$result = @include_once 'Spreadsheet/Excel/Writer.php';
		if (!$result)
		{
			include_once './classes/Spreadsheet/Excel/Writer.php';
		}
		$format_bold = "";
		$format_percent = "";
		$format_datetime = "";
		$format_title = "";
		$object_title = preg_replace("/[^a-zA-Z0-9\s]/", "", $this->object->getTitle());
		$surveyname = preg_replace("/\s/", "_", $object_title);

		switch ($_POST["export_format"])
		{
			case TYPE_XLS:
				// Creating a workbook
				$workbook = new Spreadsheet_Excel_Writer();

				// sending HTTP headers
				$workbook->send("$surveyname.xls");

				// Creating a worksheet
				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();
				$format_percent =& $workbook->addFormat();
				$format_percent->setNumFormat("0.00%");
				$format_datetime =& $workbook->addFormat();
				$format_datetime->setNumFormat("DD/MM/YYYY hh:mm:ss");
				$format_title =& $workbook->addFormat();
				$format_title->setBold();
				$format_title->setColor('black');
				$format_title->setPattern(1);
				$format_title->setFgColor('silver');
				// Creating a worksheet
				include_once ("./classes/class.ilExcelUtils.php");
				$mainworksheet =& $workbook->addWorksheet();
				include_once ("./classes/class.ilExcelUtils.php");
				$mainworksheet->writeString(0, 0, ilExcelUtils::_convert_text($this->lng->txt("title"), $_POST["export_format"]), $format_bold);
				$mainworksheet->writeString(0, 1, ilExcelUtils::_convert_text($this->lng->txt("question"), $_POST["export_format"]), $format_bold);
				$mainworksheet->writeString(0, 2, ilExcelUtils::_convert_text($this->lng->txt("question_type"), $_POST["export_format"]), $format_bold);
				$mainworksheet->writeString(0, 3, ilExcelUtils::_convert_text($this->lng->txt("users_answered"), $_POST["export_format"]), $format_bold);
				$mainworksheet->writeString(0, 4, ilExcelUtils::_convert_text($this->lng->txt("users_skipped"), $_POST["export_format"]), $format_bold);
				$mainworksheet->writeString(0, 5, ilExcelUtils::_convert_text($this->lng->txt("mode"), $_POST["export_format"]), $format_bold);
				$mainworksheet->writeString(0, 6, ilExcelUtils::_convert_text($this->lng->txt("mode_text"), $_POST["export_format"]), $format_bold);
				$mainworksheet->writeString(0, 7, ilExcelUtils::_convert_text($this->lng->txt("mode_nr_of_selections"), $_POST["export_format"]), $format_bold);
				$mainworksheet->writeString(0, 8, ilExcelUtils::_convert_text($this->lng->txt("median"), $_POST["export_format"]), $format_bold);
				$mainworksheet->writeString(0, 9, ilExcelUtils::_convert_text($this->lng->txt("arithmetic_mean"), $_POST["export_format"]), $format_bold);
				break;
			case (TYPE_SPSS):
				$csvfile = array();
				$csvrow = array();
				array_push($csvrow, $this->lng->txt("title"));
				array_push($csvrow, $this->lng->txt("question"));
				array_push($csvrow, $this->lng->txt("question_type"));
				array_push($csvrow, $this->lng->txt("users_answered"));
				array_push($csvrow, $this->lng->txt("users_skipped"));
				array_push($csvrow, $this->lng->txt("mode"));

				//array_push($csvrow, $this->lng->txt("mode_text"));


				array_push($csvrow, $this->lng->txt("mode_nr_of_selections"));
				array_push($csvrow, $this->lng->txt("median"));
				array_push($csvrow, $this->lng->txt("arithmetic_mean"));
				array_push($csvfile, $csvrow);
				break;
		}
		$questions =& $this->object->getSurveyQuestions();
		foreach ($questions as $data)
		{
			include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
			$question_type = SurveyQuestion::_getQuestionType($data["question_id"]);
			include_once "./Modules/SurveyQuestionPool/classes/class.$question_type.php";
			$question = new $question_type();
			$question->loadFromDb($data["question_id"]);

			$eval = $this->object->getCumulatedResults($question);
			switch ($_POST["export_format"])
			{
				case TYPE_XLS:
					include_once ("./classes/class.ilExcelUtils.php");
					$mainworksheet->writeString($counter+1, 0, ilExcelUtils::_convert_text($question->getTitle(), $_POST["export_format"]));
					$mainworksheet->writeString($counter+1, 1, ilExcelUtils::_convert_text($question->getQuestiontext(), $_POST["export_format"]));
					$mainworksheet->writeString($counter+1, 2, ilExcelUtils::_convert_text($this->lng->txt($eval["QUESTION_TYPE"]), $_POST["export_format"]));
					$mainworksheet->write($counter+1, 3, $eval["USERS_ANSWERED"]);
					$mainworksheet->write($counter+1, 4, $eval["USERS_SKIPPED"]);
					$mainworksheet->write($counter+1, 5, ilExcelUtils::_convert_text($eval["MODE_VALUE"], $_POST["export_format"]));
					$mainworksheet->write($counter+1, 6, ilExcelUtils::_convert_text($eval["MODE"], $_POST["export_format"]));
					$mainworksheet->write($counter+1, 7, $eval["MODE_NR_OF_SELECTIONS"]);
					$mainworksheet->write($counter+1, 8, ilExcelUtils::_convert_text(str_replace("<br />", " ", $eval["MEDIAN"]), $_POST["export_format"]));
					$mainworksheet->write($counter+1, 9, $eval["ARITHMETIC_MEAN"]);
					break;
				case (TYPE_SPSS):
					$csvrow = array();
					array_push($csvrow, $question->getTitle());
					array_push($csvrow, $question->getQuestiontext());
					array_push($csvrow, $this->lng->txt($eval["QUESTION_TYPE"]));
					array_push($csvrow, $eval["USERS_ANSWERED"]);
					array_push($csvrow, $eval["USERS_SKIPPED"]);
					array_push($csvrow, $eval["MODE"]);
					array_push($csvrow, $eval["MODE_NR_OF_SELECTIONS"]);
					array_push($csvrow, $eval["MEDIAN"]);
					array_push($csvrow, $eval["ARITHMETIC_MEAN"]);
					array_push($csvfile, $csvrow);
					break;
			}
			if ($details)
			{
				switch ($_POST["export_format"])
				{
					case TYPE_XLS:
						$question->setExportDetailsXLS($workbook, $format_title, $format_bold, $eval);
						break;
				}
			}
			$counter++;
		}

		switch ($_POST["export_format"])
		{
			case TYPE_XLS:
				// Let's send the file
				$workbook->close();
				exit();
				break;
			case TYPE_SPSS:
				$csv = "";
				$separator = ";";
				foreach ($csvfile as $csvrow)
				{
					$csvrow =& $this->object->processCSVRow($csvrow, TRUE, $separator);
					$csv .= join($csvrow, $separator) . "\n";
				}
				include_once "./classes/class.ilUtil.php";
				ilUtil::deliverData($csv, "$surveyname.csv");
				exit();
				break;
		}
	}
	
	function evaluation($details = 0)
	{
		global $ilUser;
		if (($this->object->getAnonymize() == 1) && ($_SESSION["anon_evaluation_access"] != 1))
		{
			$this->checkAnonymizedEvaluationAccess();
			return;
		}
		
		if (strlen($_POST["export_format"]))
		{
			$this->exportCumulatedResults($details);
			return;
		}

		sendInfo();
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_evaluation.html", "Modules/Survey");
		$counter = 0;
		$classes = array("tblrow1", "tblrow2");
		$questions =& $this->object->getSurveyQuestions();
		foreach ($questions as $data)
		{
			include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
			$question_type = SurveyQuestion::_getQuestionType($data["question_id"]);
			$question_type_gui = $question_type . "GUI";
			include_once "./Modules/SurveyQuestionPool/classes/class.$question_type". "GUI.php";
			$question_gui = new $question_type_gui($data["question_id"]);
			$question = $question_gui->object;
			//$question->loadFromDb($data["question_id"]);
			$row = $question_gui->getCumulatedResultRow($counter, $classes[$counter % 2], $this->object->getSurveyId());
			//$eval = $this->object->getCumulatedResults($question);
			
			$this->tpl->setCurrentBlock("row");
			$this->tpl->setVariable("ROW", $row);
			$this->tpl->parseCurrentBlock();
			if ($details)
			{
				$detail = $question_gui->getCumulatedResultsDetails($this->object->getSurveyId(), $counter+1);
				$this->tpl->setCurrentBlock("detail");
				$this->tpl->setVariable("DETAIL", $detail);
				$this->tpl->parseCurrentBlock();
			}
			$counter++;
		}

		$this->tpl->setCurrentBlock("generic_css");
		$this->tpl->setVariable("LOCATION_GENERIC_STYLESHEET", "./survey/templates/default/evaluation_print.css");
		$this->tpl->setVariable("MEDIA_GENERIC_STYLESHEET", "print");
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("QUESTION_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("QUESTION_TEXT", $this->lng->txt("question"));
		$this->tpl->setVariable("QUESTION_TYPE", $this->lng->txt("question_type"));
		$this->tpl->setVariable("USERS_ANSWERED", $this->lng->txt("users_answered"));
		$this->tpl->setVariable("USERS_SKIPPED", $this->lng->txt("users_skipped"));
		$this->tpl->setVariable("MODE", $this->lng->txt("mode"));
		$this->tpl->setVariable("MODE_NR_OF_SELECTIONS", $this->lng->txt("mode_nr_of_selections"));
		$this->tpl->setVariable("MEDIAN", $this->lng->txt("median"));
		$this->tpl->setVariable("ARITHMETIC_MEAN", $this->lng->txt("arithmetic_mean"));
		$this->tpl->setVariable("EXPORT_DATA", $this->lng->txt("export_data_as"));
		$this->tpl->setVariable("TEXT_EXCEL", $this->lng->txt("exp_type_excel"));
		$this->tpl->setVariable("TEXT_CSV", $this->lng->txt("exp_type_csv"));
		$this->tpl->setVariable("VALUE_DETAIL", $details);
		$this->tpl->setVariable("BTN_EXPORT", $this->lng->txt("export"));
		$this->tpl->setVariable("BTN_PRINT", $this->lng->txt("print"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("PRINT_ACTION", $this->ctrl->getFormAction($this));
		if ($details)
		{
			$this->tpl->setVariable("CMD_EXPORT", "evaluationdetails");
		}
		else
		{
			$this->tpl->setVariable("CMD_EXPORT", "evaluation");
		}
		$this->tpl->parseCurrentBlock();
	}
	
	/**
	* Export the user specific results for the survey
	*
	* Export the user specific results for the survey
	*
	* @access private
	*/
	function exportUserSpecificResults($export_format)
	{
		$result = @include_once 'Spreadsheet/Excel/Writer.php';
		if (!$result)
		{
			include_once './classes/Spreadsheet/Excel/Writer.php';
		}
		
		$object_title = preg_replace("/[^a-zA-Z0-9\s]/", "", $this->object->getTitle());
		$surveyname = preg_replace("/\s/", "_", $object_title);

		$csvfile = array();
		$csvrow = array();
		$questions = array();
		$eval =& $this->object->getEvaluationForAllUsers();
		$questions =& $this->object->getSurveyQuestions(true);
		array_push($csvrow, $this->lng->txt("username"));
		if ($this->object->getAnonymize() == ANONYMIZE_OFF)
		{
			array_push($csvrow, $this->lng->txt("gender"));
		}
		$cellcounter = 1;
		foreach ($questions as $question_id => $question_data)
		{
			include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
			$question_type = SurveyQuestion::_getQuestionType($question_data["question_id"]);
			include_once "./Modules/SurveyQuestionPool/classes/class.$question_type.php";
			$question = new $question_type();
			$question->loadFromDb($question_data["question_id"]);
			$question->addUserSpecificResultsExportTitles($csvrow);
			$questions[$question_data["question_id"]] = $question;
		}
		array_push($csvfile, $csvrow);

		foreach ($eval as $user_id => $resultset)
		{
			$csvrow = array();
			array_push($csvrow, $resultset["name"]);
			if ($this->object->getAnonymize() == ANONYMIZE_OFF)
			{
				array_push($csvrow, $resultset["gender"]);
			}
			foreach ($questions as $question_id => $question)
			{
				$question->addUserSpecificResultsData($csvrow, $resultset);
			}
			array_push($csvfile, $csvrow);
		}
		switch ($export_format)
		{
			case TYPE_XLS:
				// Let's send the file
				// Creating a workbook
				$workbook = new Spreadsheet_Excel_Writer();

				// sending HTTP headers
				$workbook->send("$surveyname.xls");

				// Creating a worksheet
				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();
				$format_percent =& $workbook->addFormat();
				$format_percent->setNumFormat("0.00%");
				$format_datetime =& $workbook->addFormat();
				$format_datetime->setNumFormat("DD/MM/YYYY hh:mm:ss");
				$format_title =& $workbook->addFormat();
				$format_title->setBold();
				$format_title->setColor('black');
				$format_title->setPattern(1);
				$format_title->setFgColor('silver');
				$format_title_plain =& $workbook->addFormat();
				$format_title_plain->setColor('black');
				$format_title_plain->setPattern(1);
				$format_title_plain->setFgColor('silver');
				// Creating a worksheet
				$mainworksheet =& $workbook->addWorksheet();
				$row = 0;
				include_once "./classes/class.ilExcelUtils.php";
				foreach ($csvfile as $csvrow)
				{
					$col = 0;
					if ($row == 0)
					{
						foreach ($csvrow as $text)
						{
							$mainworksheet->writeString($row, $col++, ilExcelUtils::_convert_text($text, $_POST["export_format"]), $format_title);
						}
					}
					else
					{
						foreach ($csvrow as $text)
						{
							if (is_numeric($text))
							{
								$mainworksheet->writeNumber($row, $col++, $text);
							}
							else
							{
								$mainworksheet->writeString($row, $col++, ilExcelUtils::_convert_text($text, $_POST["export_format"]));
							}
						}
					}
					$row++;
				}
				$workbook->close();
				exit();
				break;
			case TYPE_SPSS:
				$csv = "";
				$separator = ";";
				foreach ($csvfile as $csvrow)
				{
					$csvrow =& $this->object->processCSVRow($csvrow, TRUE, $separator);
					$csv .= join($csvrow, $separator) . "\n";
				}
				include_once "./classes/class.ilUtil.php";
				ilUtil::deliverData($csv, "$surveyname.csv");
				exit();
				break;
		}
	}
	
	/**
	* Print the survey evaluation for a selected user
	*
	* Print the survey evaluation for a selected user
	*
	* @access private
	*/
	function evaluationuser()
	{
		if (!is_array($_POST))
		{
			$_POST = array();
		}
		if (array_key_exists("export_format", $_POST))
		{
			return $this->exportUserSpecificResults($_POST["export_format"]);
		}

		$userResults =& $this->object->getUserSpecificResults();
		sendInfo();
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_evaluation_user.html", "Modules/Survey");
		$counter = 0;
		$classes = array("tblrow1top", "tblrow2top");
		$questions =& $this->object->getSurveyQuestions(true);
		$this->tpl->setCurrentBlock("headercell");
		$this->tpl->setVariable("TEXT_HEADER_CELL", $this->lng->txt("username"));
		$this->tpl->parseCurrentBlock();
		if ($this->object->getAnonymize() == ANONYMIZE_OFF)
		{
			$this->tpl->setCurrentBlock("headercell");
			$this->tpl->setVariable("TEXT_HEADER_CELL", $this->lng->txt("gender"));
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("headercell");
		$this->tpl->setVariable("TEXT_HEADER_CELL", $this->lng->txt("question"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("headercell");
		$this->tpl->setVariable("TEXT_HEADER_CELL", $this->lng->txt("results"));
		$this->tpl->parseCurrentBlock();

		$cellcounter = 1;
		$participants =& $this->object->getSurveyParticipants();
		
		foreach ($participants as $data)
		{
			$this->tpl->setCurrentBlock("bodycell");
			$this->tpl->setVariable("COLOR_CLASS", $classes[$counter % 2]);
			$this->tpl->setVariable("TEXT_BODY_CELL", $data["name"]);
			$this->tpl->parseCurrentBlock();
			if ($this->object->getAnonymize() == ANONYMIZE_OFF)
			{
				$this->tpl->setCurrentBlock("bodycell");
				$this->tpl->setVariable("COLOR_CLASS", $classes[$counter % 2]);
				$this->tpl->setVariable("TEXT_BODY_CELL", $this->lng->txt("gender_" . ilObjUser::_lookupGender($data["user_id"])));
				$this->tpl->parseCurrentBlock();
			}
			$intro = TRUE;
			$questioncounter = 1;
			foreach ($questions as $question_id => $question_data)
			{
				if ($intro)
				{
					$intro = FALSE;
				}
				else
				{
					if ($this->object->getAnonymize() == ANONYMIZE_OFF)
					{
						$this->tpl->setCurrentBlock("bodycell");
						$this->tpl->setVariable("COLOR_CLASS", $classes[$counter % 2]);
						$this->tpl->parseCurrentBlock();
					}
					$this->tpl->setCurrentBlock("bodycell");
					$this->tpl->setVariable("COLOR_CLASS", $classes[$counter % 2]);
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock("bodycell");
				$this->tpl->setVariable("COLOR_CLASS", $classes[$counter % 2]);
				$this->tpl->setVariable("TEXT_BODY_CELL", $questioncounter++ . ". " . $question_data["title"]);
				$this->tpl->parseCurrentBlock();
				
				if ($this->object->getAnonymize() == ANONYMIZE_OFF)
				{
					$found = $userResults[$question_id][$data["user_id"]];
				}
				else
				{
					$found = $userResults[$question_id][$data["anonymous_id"]];
				}
				$text = "";
				if (is_array($found))
				{
					$text = implode("<br />", $found);
				}
				else
				{
					$text = $found;
				}
				if (strlen($text) == 0) $text = $this->lng->txt("skipped");
				$this->tpl->setCurrentBlock("bodycell");
				$this->tpl->setVariable("COLOR_CLASS", $classes[$counter % 2]);
				$this->tpl->setVariable("TEXT_BODY_CELL", $text);
				$this->tpl->parseCurrentBlock();
				$this->tpl->setCurrentBlock("row");
				$this->tpl->parse("row");
			}
			$counter++;
		}
		$this->tpl->setCurrentBlock("generic_css");
		$this->tpl->setVariable("LOCATION_GENERIC_STYLESHEET", "./survey/templates/default/evaluation_print.css");
		$this->tpl->setVariable("MEDIA_GENERIC_STYLESHEET", "print");
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("EXPORT_DATA", $this->lng->txt("export_data_as"));
		$this->tpl->setVariable("TEXT_EXCEL", $this->lng->txt("exp_type_excel"));
		$this->tpl->setVariable("TEXT_CSV", $this->lng->txt("exp_type_csv"));
		$this->tpl->setVariable("BTN_EXPORT", $this->lng->txt("export"));
		$this->tpl->setVariable("BTN_PRINT", $this->lng->txt("print"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("PRINT_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("CMD_EXPORT", "evaluationuser");
		$this->tpl->parseCurrentBlock();
	}
	
	/**
	* Print the survey evaluation for a selected user
	*
	* Print the survey evaluation for a selected user
	*
	* @access private
	*/
	function evaluationuser_old()
	{
		if (!is_array($_POST))
		{
			$_POST = array();
		}
		if (array_key_exists("export_format", $_POST))
		{
			return $this->exportUserSpecificResults($_POST["export_format"]);
		}

		$userResults =& $this->object->getUserSpecificResults();
		sendInfo();
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_evaluation_user.html", "Modules/Survey");
		$counter = 0;
		$classes = array("tblrow1top", "tblrow2top");
		$questions =& $this->object->getSurveyQuestions(true);
		$this->tpl->setCurrentBlock("headercell");
		$this->tpl->setVariable("TEXT_HEADER_CELL", $this->lng->txt("username"));
		$this->tpl->parseCurrentBlock();
		if ($this->object->getAnonymize() == ANONYMIZE_OFF)
		{
			$this->tpl->setCurrentBlock("headercell");
			$this->tpl->setVariable("TEXT_HEADER_CELL", $this->lng->txt("gender"));
			$this->tpl->parseCurrentBlock();
		}
		$char = "A";
		$cellcounter = 1;
		$participants =& $this->object->getSurveyParticipants();
		
		foreach ($questions as $question_id => $question_data)
		{
			$this->tpl->setCurrentBlock("headercell");
			$this->tpl->setVariable("TEXT_HEADER_CELL", $char);
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("legendrow");
			$this->tpl->setVariable("TEXT_KEY", $char++);
			$this->tpl->setVariable("TEXT_VALUE", $question_data["title"]);
			$this->tpl->parseCurrentBlock();
		}

		foreach ($participants as $data)
		{
			$this->tpl->setCurrentBlock("bodycell");
			$this->tpl->setVariable("COLOR_CLASS", $classes[$counter % 2]);
			$this->tpl->setVariable("TEXT_BODY_CELL", $data["name"]);
			$this->tpl->parseCurrentBlock();
			if ($this->object->getAnonymize() == ANONYMIZE_OFF)
			{
				$this->tpl->setCurrentBlock("bodycell");
				$this->tpl->setVariable("COLOR_CLASS", $classes[$counter % 2]);
				$this->tpl->setVariable("TEXT_BODY_CELL", $this->lng->txt("gender_" . ilObjUser::_lookupGender($data["user_id"])));
				$this->tpl->parseCurrentBlock();
			}
			foreach ($questions as $question_id => $question_data)
			{
				if ($this->object->getAnonymize() == ANONYMIZE_OFF)
				{
					$found = $userResults[$question_id][$data["user_id"]];
				}
				else
				{
					$found = $userResults[$question_id][$data["anonymous_id"]];
				}
				$text = "";
				if (is_array($found))
				{
					$text = implode("<br />", $found);
				}
				else
				{
					$text = $found;
				}
				if (strlen($text) == 0) $text = $this->lng->txt("skipped");
				$this->tpl->setCurrentBlock("bodycell");
				$this->tpl->setVariable("COLOR_CLASS", $classes[$counter % 2]);
				$this->tpl->setVariable("TEXT_BODY_CELL", $text);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("row");
			$this->tpl->parse("row");
			$counter++;
		}
		$this->tpl->setCurrentBlock("generic_css");
		$this->tpl->setVariable("LOCATION_GENERIC_STYLESHEET", "./survey/templates/default/evaluation_print.css");
		$this->tpl->setVariable("MEDIA_GENERIC_STYLESHEET", "print");
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("EXPORT_DATA", $this->lng->txt("export_data_as"));
		$this->tpl->setVariable("TEXT_EXCEL", $this->lng->txt("exp_type_excel"));
		$this->tpl->setVariable("TEXT_CSV", $this->lng->txt("exp_type_csv"));
		$this->tpl->setVariable("BTN_EXPORT", $this->lng->txt("export"));
		$this->tpl->setVariable("BTN_PRINT", $this->lng->txt("print"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("PRINT_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TEXT_LEGEND", $this->lng->txt("legend"));
		$this->tpl->setVariable("TEXT_LEGEND_LINK", $this->lng->txt("eval_legend_link"));
		$this->tpl->setVariable("CMD_EXPORT", "evaluationuser");
		$this->tpl->parseCurrentBlock();
	}
}
?>
