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

require_once "./assessment/classes/class.assQuestionGUI.php";
require_once "./assessment/classes/class.assMultipleChoice.php";

/**
* Multiple choice question GUI representation
*
* The ASS_MultipleChoiceGUI class encapsulates the GUI representation
* for multiple choice questions.
*
* @author		Helmut Schottm�ller <hschottm@tzi.de>
* @version	$Id$
* @module   class.assMultipleChoiceGUI.php
* @modulegroup   Assessment
*/
class ASS_MultipleChoiceGUI extends ASS_QuestionGUI
{
	/**
	* ASS_MultipleChoiceGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the ASS_MultipleChoiceGUI object.
	*
	* @param integer $id The database id of a multiple choice question object
	* @access public
	*/
	function ASS_MultipleChoiceGUI(
			$id = -1
	)
	{
		$this->ASS_QuestionGUI();
		$this->object = new ASS_MultipleChoice();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	function getCommand($cmd)
	{
		if (substr($cmd, 0, 6) == "delete")
		{
			$cmd = "delete";
		}

		return $cmd;
	}


	/**
	* Returns the question type string
	*
	* Returns the question type string
	*
	* @result string The question type string
	* @access public
	*/
	function getQuestionType()
	{
		if ($this->object->get_response() == RESPONSE_SINGLE)
		{
			return "qt_multiple_choice_sr";
		}
		else
		{
			return "qt_multiple_choice_mr";
		}
	}

	/**
	* Creates an output of the edit form for the question
	*
	* Creates an output of the edit form for the question
	*
	* @access public
	*/
	function editQuestion()
	{
		// single response
		if ($this->object->get_response() == RESPONSE_SINGLE)
		{
			$this->getQuestionTemplate("qt_multiple_choice_sr");
			$this->tpl->addBlockFile("QUESTION_DATA", "question_data", "tpl.il_as_qpl_mc_sr.html", true);
			$this->tpl->addBlockFile("OTHER_QUESTION_DATA", "other_question_data", "tpl.il_as_qpl_other_question_data.html", true);
			// output of existing single response answers
			for ($i = 0; $i < $this->object->get_answer_count(); $i++)
			{
				$this->tpl->setCurrentBlock("deletebutton");
				$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
				$this->tpl->setVariable("ANSWER_ORDER", $i);
				$this->tpl->parseCurrentBlock();
				$this->tpl->setCurrentBlock("answers");
				$answer = $this->object->get_answer($i);
				$this->tpl->setVariable("VALUE_ANSWER_COUNTER", $answer->get_order() + 1);
				$this->tpl->setVariable("ANSWER_ORDER", $answer->get_order());
				$this->tpl->setVariable("VALUE_ANSWER", $answer->get_answertext());
				$this->tpl->setVariable("TEXT_WHEN", $this->lng->txt("when"));
				$this->tpl->setVariable("TEXT_SET", $this->lng->txt("radio_set"));
				$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
				$this->tpl->setVariable("TEXT_ANSWER_TEXT", $this->lng->txt("answer_text"));
				$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_POINTS", sprintf("%d", $answer->get_points()));
				$this->tpl->setVariable("VALUE_TRUE", $this->lng->txt("true"));
				if ($answer->isStateSet())
				{
					$this->tpl->setVariable("STATUS_CHECKED", " checked=\"checked\"");
				}
				$this->tpl->parseCurrentBlock();
			}
			/*
			if (strlen($_POST["cmd"]["add"]) > 0)
			{
				// Create template for a new answer
				$this->tpl->setCurrentBlock("answers");
				$this->tpl->setVariable("VALUE_ANSWER_COUNTER", $this->object->get_answer_count() + 1);
				$this->tpl->setVariable("ANSWER_ORDER", $this->object->get_answer_count());
				$this->tpl->setVariable("TEXT_ANSWER_TEXT", $this->lng->txt("answer_text"));
				$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
				$this->tpl->setVariable("VALUE_TRUE", $this->lng->txt("true"));
				$this->tpl->parseCurrentBlock();
			}*/
			// call to other question data i.e. estimated working time block
			$this->outOtherQuestionData();

			$this->tpl->setCurrentBlock("question_data");
			$this->tpl->setVariable("MULTIPLE_CHOICE_ID", $this->object->getId());
			$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_TITLE", $this->object->getTitle());
			$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_COMMENT", $this->object->getComment());
			$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_AUTHOR", $this->object->getAuthor());
			$this->tpl->setVariable("VALUE_QUESTION", $this->object->get_question());
			$this->tpl->setVariable("VALUE_ADD_ANSWER", $this->lng->txt("add_answer"));
			$this->tpl->setVariable("VALUE_ADD_ANSWER_YN", $this->lng->txt("add_answer_yn"));
			$this->tpl->setVariable("VALUE_ADD_ANSWER_TF", $this->lng->txt("add_answer_tf"));
			$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
			$this->tpl->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
			$this->tpl->setVariable("TEXT_COMMENT", $this->lng->txt("description"));
			$this->tpl->setVariable("TEXT_QUESTION", $this->lng->txt("question"));
			$this->tpl->setVariable("TEXT_SHUFFLE_ANSWERS", $this->lng->txt("shuffle_answers"));
			$this->tpl->setVariable("TXT_YES", $this->lng->txt("yes"));
			$this->tpl->setVariable("TXT_NO", $this->lng->txt("no"));
			if ($this->object->getShuffle())
			{
				$this->tpl->setVariable("SELECTED_YES", " selected=\"selected\"");
			}
			else
			{
				$this->tpl->setVariable("SELECTED_NO", " selected=\"selected\"");
			}
			$this->tpl->setVariable("SAVE",$this->lng->txt("save"));
			$this->tpl->setVariable("APPLY", $this->lng->txt("apply"));
			$this->tpl->setVariable("CANCEL",$this->lng->txt("cancel"));
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
			$this->ctrl->setParameter($this, "sel_question_types", "qt_multiple_choice_sr");
			$this->tpl->setVariable("ACTION_MULTIPLE_CHOICE_TEST",
				$this->ctrl->getFormAction($this));

			$this->tpl->parseCurrentBlock();
		}
		else	// multiple response
		{
			$this->getQuestionTemplate("qt_multiple_choice_mr");
			$this->tpl->addBlockFile("QUESTION_DATA", "question_data", "tpl.il_as_qpl_mc_mr.html", true);
			$this->tpl->addBlockFile("OTHER_QUESTION_DATA", "other_question_data", "tpl.il_as_qpl_other_question_data.html", true);

			// output of existing multiple response answers
			for ($i = 0; $i < $this->object->get_answer_count(); $i++)
			{
				$this->tpl->setCurrentBlock("deletebutton");
				$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
				$this->tpl->setVariable("ANSWER_ORDER", $i);
				$this->tpl->parseCurrentBlock();
				$this->tpl->setCurrentBlock("answers");
				$answer = $this->object->get_answer($i);
				$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
				$this->tpl->setVariable("VALUE_ANSWER_COUNTER", $answer->get_order() + 1);
				$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_POINTS", sprintf("%d", $answer->get_points()));
				$this->tpl->setVariable("TEXT_WHEN", $this->lng->txt("when"));
				$this->tpl->setVariable("TEXT_UNCHECKED", $this->lng->txt("checkbox_unchecked"));
				$this->tpl->setVariable("TEXT_CHECKED", $this->lng->txt("checkbox_checked"));
				$this->tpl->setVariable("ANSWER_ORDER", $answer->get_order());
				$this->tpl->setVariable("VALUE_ANSWER", $answer->get_answertext());
				$this->tpl->setVariable("TEXT_ANSWER_TEXT", $this->lng->txt("answer_text"));
				$this->tpl->setVariable("VALUE_TRUE", $this->lng->txt("true"));
				if ($answer->isStateChecked())
				{
					$this->tpl->setVariable("CHECKED_SELECTED", " selected=\"selected\"");
				}
				$this->tpl->parseCurrentBlock();
			}

			/*
			if (strlen($_POST["cmd"]["add"]) > 0)
			{
				// Create template for a new answer
				$this->tpl->setCurrentBlock("answers");
				$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
				$this->tpl->setVariable("VALUE_ANSWER_COUNTER", $this->object->get_answer_count() + 1);
				$this->tpl->setVariable("ANSWER_ORDER", $this->object->get_answer_count());
				$this->tpl->setVariable("TEXT_ANSWER_TEXT", $this->lng->txt("answer_text"));
				$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_POINTS", "0");
				$this->tpl->setVariable("VALUE_TRUE", $this->lng->txt("true"));
				$this->tpl->parseCurrentBlock();
			}*/

			// call to other question data i.e. estimated working time block
			$this->outOtherQuestionData();

			$this->tpl->setCurrentBlock("question_data");

			$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
			$this->tpl->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
			$this->tpl->setVariable("TEXT_COMMENT", $this->lng->txt("description"));
			$this->tpl->setVariable("TEXT_QUESTION", $this->lng->txt("question"));
			$this->tpl->setVariable("MULTIPLE_CHOICE_ID", $this->object->getId());
			$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_TITLE", $this->object->getTitle());
			$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_COMMENT", $this->object->getComment());
			$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_AUTHOR", $this->object->getAuthor());
			$this->tpl->setVariable("VALUE_QUESTION", $this->object->get_question());
			$this->tpl->setVariable("VALUE_ADD_ANSWER", $this->lng->txt("add_answer"));
			$this->tpl->setVariable("TEXT_SHUFFLE_ANSWERS", $this->lng->txt("shuffle_answers"));
			$this->tpl->setVariable("TXT_YES", $this->lng->txt("yes"));
			$this->tpl->setVariable("TXT_NO", $this->lng->txt("no"));
			if ($this->object->getShuffle())
			{
				$this->tpl->setVariable("SELECTED_YES", " selected=\"selected\"");
			}
			else
			{
				$this->tpl->setVariable("SELECTED_NO", " selected=\"selected\"");
			}
			$this->tpl->setVariable("SAVE",$this->lng->txt("save"));
			$this->tpl->setVariable("APPLY", $this->lng->txt("apply"));
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
			$this->tpl->setVariable("CANCEL", $this->lng->txt("cancel"));
			$this->ctrl->setParameter($this, "sel_question_types", "qt_multiple_choice_mr");
			$this->tpl->setVariable("ACTION_MULTIPLE_CHOICE_TEST",
				$this->ctrl->getFormAction($this));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->parseCurrentBlock();
	}

	/**
	* Sets the extra fields i.e. estimated working time of a question from a posted create/edit form
	*
	* Sets the extra fields i.e. estimated working time of a question from a posted create/edit form
	*
	* @access private
	*/
	function outOtherQuestionData()
	{
//echo "<br>ASS_MultipleChoiceGUI->outOtherQuestionData()";
		$this->tpl->setCurrentBlock("other_question_data");
		$est_working_time = $this->object->getEstimatedWorkingTime();
		$this->tpl->setVariable("TEXT_WORKING_TIME", $this->lng->txt("working_time"));
		$this->tpl->setVariable("TIME_FORMAT", $this->lng->txt("time_format"));
		$this->tpl->setVariable("VALUE_WORKING_TIME", ilUtil::makeTimeSelect("Estimated", false, $est_working_time[h], $est_working_time[m], $est_working_time[s]));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* add yes no answer
	*/
	function addYesNo()
	{
		$this->writePostData();
		//$this->setObjectData();

		if (!$this->checkInput())
		{
			sendInfo($this->lng->txt("fill_out_all_required_fields_add_answer"));
		}
		else
		{
			// add a yes/no answer template
			$this->object->add_answer(
				$this->lng->txt("yes"),
				0,
				0,
				count($this->object->answers)
			);
			$this->object->add_answer(
				$this->lng->txt("no"),
				0,
				0,
				count($this->object->answers)
			);
		}

		$this->editQuestion();
	}

	/**
	* add true/false answer
	*/
	function addTrueFalse()
	{
		//$this->setObjectData();
		$this->writePostData();

		if (!$this->checkInput())
		{
			sendInfo($this->lng->txt("fill_out_all_required_fields_add_answer"));
		}
		else
		{
			// add a true/false answer template
			$this->object->add_answer(
				$this->lng->txt("true"),
				0,
				0,
				count($this->object->answers)
			);
			$this->object->add_answer(
				$this->lng->txt("false"),
				0,
				0,
				count($this->object->answers)
			);
		}

		$this->editQuestion();
	}

	/**
	* add an answer
	*/
	function add()
	{
		//$this->setObjectData();
		$this->writePostData();

		if (!$this->checkInput())
		{
			sendInfo($this->lng->txt("fill_out_all_required_fields_add_answer"));
		}
		else
		{
			// add an answer template
			$this->object->add_answer(
				$this->lng->txt(""),
				0,
				0,
				count($this->object->answers)
			);
		}

		$this->editQuestion();
	}

	/**
	* delete an answer
	*/
	function delete()
	{
		//$this->setObjectData();
		$this->writePostData();

		foreach ($_POST["cmd"] as $key => $value)
		{
			// was one of the answers deleted
			if (preg_match("/delete_(\d+)/", $key, $matches))
			{
				$this->object->delete_answer($matches[1]);
			}
		}

		$this->editQuestion();
	}

	/**
	* check input fields
	*/
	function checkInput()
	{
		$cmd = $this->ctrl->getCmd();

		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]))
		{
//echo "<br>checkInput1:FALSE";
			return false;
		}
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/answer_(\d+)/", $key, $matches))
			{
				if (!$value)
				{
//echo "<br>checkInput2:FALSE";
					return false;
				}
			}
		}

		return true;
	}

	/**
	* set object data
	*/
	/*
	function setObjectData()
	{
		$this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
		$this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
		$this->object->setComment(ilUtil::stripSlashes($_POST["comment"]));
		$this->object->set_question(ilUtil::stripSlashes($_POST["question"]));
		$this->object->setShuffle($_POST["shuffle"]);

		// adding materials uris
		//$saved = $this->writeOtherPostData($result);
		$saved = $this->writeOtherPostData($result);

		// Delete all existing answers and create new answers from the form data
		$this->object->flush_answers();

		// Add all answers from the form into the object
		if ($this->object->get_response() == RESPONSE_SINGLE)
		{
			// ...for multiple choice with single response
			foreach ($_POST as $key => $value)
			{
				if (preg_match("/answer_(\d+)/", $key, $matches))
				{
					if ($_POST["radio"] == $matches[1])
					{
						$is_true = TRUE;
					}
					else
					{
						$is_true = FALSE;
					}
					$this->object->add_answer(
						ilUtil::stripSlashes($_POST["$key"]),
						ilUtil::stripSlashes($_POST["points_$matches[1]"]),
						ilUtil::stripSlashes($is_true),
						ilUtil::stripSlashes($matches[1])
						);
				}
			}
		}
		else
		{
			// ...for multiple choice with multiple response
			foreach ($_POST as $key => $value)
			{
				if (preg_match("/answer_(\d+)/", $key, $matches))
				{
					if ($_POST["checkbox_$matches[1]"] == $matches[1])
					{
						$is_true = TRUE;
					}
					else
					{
						$is_true = FALSE;
					}
					$this->object->add_answer(
						ilUtil::stripSlashes($_POST["$key"]),
						ilUtil::stripSlashes($_POST["points_$matches[1]"]),
						ilUtil::stripSlashes($is_true),
						ilUtil::stripSlashes($matches[1])
						);
				}
			}
		}
	}
	*/

	/**
	* Evaluates a posted edit form and writes the form data in the question object
	*
	* Evaluates a posted edit form and writes the form data in the question object
	*
	* @return integer A positive value, if one of the required fields wasn't set, else 0
	* @access private
	*/
	function writePostData()
	{
//echo "here!"; exit;
//echo "<br>ASS_MultipleChoiceGUI->writePostData()";
		$result = 0;
		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]))
		{
			$result = 1;
		}

		if (($result) and (($_POST["cmd"]["add"]) or ($_POST["cmd"]["add_tf"]) or ($_POST["cmd"]["add_yn"])))
		{
			// You cannot add answers before you enter the required data
			sendInfo($this->lng->txt("fill_out_all_required_fields_add_answer"));
			$_POST["cmd"]["add"] = "";
			$_POST["cmd"]["add_yn"] = "";
			$_POST["cmd"]["add_tf"] = "";
		}

		// Check the creation of new answer text fields
		if ($_POST["cmd"]["add"] or $_POST["cmd"]["add_yn"] or $_POST["cmd"]["add_tf"])
		{
			foreach ($_POST as $key => $value)
			{
				if (preg_match("/answer_(\d+)/", $key, $matches))
				{
					if (!$value)
					{
						$_POST["cmd"]["add"] = "";
						$_POST["cmd"]["add_yn"] = "";
						$_POST["cmd"]["add_tf"] = "";
						sendInfo($this->lng->txt("fill_out_all_answer_fields"));
					}
			 	}
			}
		}

		$this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
		$this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
		$this->object->setComment(ilUtil::stripSlashes($_POST["comment"]));
		$this->object->set_question(ilUtil::stripSlashes($_POST["question"]));
		$this->object->setShuffle($_POST["shuffle"]);

		$saved = $this->writeOtherPostData($result);

		// Delete all existing answers and create new answers from the form data
		$this->object->flush_answers();

		// Add all answers from the form into the object
		if ($this->object->get_response() == RESPONSE_SINGLE)
		{
			// ...for multiple choice with single response
			foreach ($_POST as $key => $value)
			{
				if (preg_match("/answer_(\d+)/", $key, $matches))
				{
					$state = 0;
					if ($_POST["status"] == $matches[1])
					{
						$state = 1;
					}
					$this->object->add_answer(
						ilUtil::stripSlashes($_POST["$key"]),
						ilUtil::stripSlashes($_POST["points_$matches[1]"]),
						$state,
						ilUtil::stripSlashes($matches[1])
						);
				}
			}
			/*
			if ($_POST["cmd"]["add_tf"])
			{
				// add a true/false answer template
				$this->object->add_answer(
					$this->lng->txt("true"),
					0,
					false,
					count($this->object->answers)
				);
				$this->object->add_answer(
					$this->lng->txt("false"),
					0,
					false,
					count($this->object->answers)
				);
			}*/
			/*
			if ($_POST["cmd"]["add_yn"])
			{
				// add a true/false answer template
				$this->object->add_answer(
					$this->lng->txt("yes"),
					0,
					false,
					count($this->object->answers)
				);
				$this->object->add_answer(
					$this->lng->txt("no"),
					0,
					false,
					count($this->object->answers)
				);
			}*/
		}
		else
		{
			// ...for multiple choice with multiple response
			foreach ($_POST as $key => $value)
			{
				if (preg_match("/answer_(\d+)/", $key, $matches))
				{
					$this->object->add_answer(
						ilUtil::stripSlashes($_POST["$key"]),
						ilUtil::stripSlashes($_POST["points_$matches[1]"]),
						ilUtil::stripSlashes($_POST["status_$matches[1]"]),
						ilUtil::stripSlashes($matches[1])
						);
				}
			}
		}

		// After adding all questions from the form we have to check if the learner pressed a delete button
		foreach ($_POST as $key => $value)
		{
			// was one of the answers deleted
			if (preg_match("/delete_(\d+)/", $key, $matches))
			{
				$this->object->delete_answer($matches[1]);
			}
		}

		// Set the question id from a hidden form parameter
		if ($_POST["multiple_choice_id"] > 0)
		{
			$this->object->setId($_POST["multiple_choice_id"]);
		}
		
		if ($saved)
		{
			// If the question was saved automatically before an upload, we have to make
			// sure, that the state after the upload is saved. Otherwise the user could be
			// irritated, if he presses cancel, because he only has the question state before
			// the upload process.
			$this->object->saveToDb();
			$_GET["q_id"] = $this->object->getId();
		}

		return $result;
	}

	/**
	* Creates the question output form for the learner
	*
	* Creates the question output form for the learner
	*
	* @access public
	*/
	function outWorkingForm($test_id = "", $is_postponed = false, $showsolution = 0)
	{
		global $ilUser;
		$output = $this->outQuestionPage("MULTIPLE_CHOICE_QUESTION", $is_postponed);
//		preg_match("/(<div[^<]*?ilc_Question.*?<\/div>)/is", $output, $matches);
//		$solutionoutput = $matches[1];
		$solutionoutput = preg_replace("/.*?(<div[^<]*?ilc_Question.*?<\/div>).*/", "\\1", $output);
		$solutionoutput = preg_replace("/\"mc/", "\"solution_mc", $solutionoutput);
		$solutionoutput = preg_replace("/multiple_choice_result/", "solution_multiple_choice_result", $solutionoutput);
		// set solutions
		if ($test_id)
		{
			$solutions =& $this->object->getSolutionValues($test_id);
			foreach ($solutions as $idx => $solution_value)
			{
				$repl_str = "dummy=\"mc".$solution_value->value1."\"";
//echo "<br>".htmlentities($repl_str);
				$output = str_replace($repl_str, $repl_str." checked=\"checked\"", $output);
			}
		}
		
		$points = 0;
		foreach ($this->object->answers as $idx => $answer)
		{
			$points += $answer->get_points();
			if ($answer->isStateChecked())
			{
				$repl_str = "dummy=\"solution_mc$idx\"";
				$solutionoutput = str_replace($repl_str, $repl_str." checked=\"checked\"", $solutionoutput);
			}
			$solutionoutput = preg_replace("/(<tr.*?dummy=\"solution_mc$idx.*?)<\/tr>/", "\\1<td>" . "<em>(" . $points . " " . $this->lng->txt("points") . ")</em>" . "</td></tr>", $solutionoutput);
		}

		$solutionoutput = "<p>" . $this->lng->txt("correct_solution_is") . ":</p><p>$solutionoutput</p>";
		if ($test_id) 
		{
			$received_points = "<p>" . sprintf($this->lng->txt("you_received_a_of_b_points"), $this->object->getReachedPoints($ilUser->id, $test_id), $this->object->getMaximumPoints()) . "</p>";
		}
		if (!$showsolution)
		{
			$solutionoutput = "";
			$received_points = "";
		}
		$this->tpl->setVariable("MULTIPLE_CHOICE_QUESTION", $output.$solutionoutput.$received_points);
	}

	/**
	* Creates a preview of the question
	*
	* @access private
	*/
	function outPreviewForm()
	{
		$this->outWorkingForm();
	}

	/**
	* Creates an output of the user's solution
	*
	* Creates an output of the user's solution
	*
	* @access public
	*/
	function outUserSolution($user_id, $test_id)
	{
		$results = $this->object->getReachedInformation($user_id, $test_id);
		foreach ($this->object->answers as $key => $answer)
		{
			$selected = 0;
			$this->tpl->setCurrentBlock("tablerow");
			if ($answer->isStateChecked())
			{
				$right = 0;
				foreach ($results as $reskey => $resvalue)
				{
					if ($resvalue["value"] == $key)
					{
						$right = 1;
						$selected = 1;
					}
				}
			}
			elseif ($answer->isStateUnchecked())
			{
				$right = 1;
				foreach ($results as $reskey => $resvalue)
				{
					if ($resvalue["value"] == $key)
					{
						$right = 0;
						$selected = 1;
					}
				}
			}
			if ($right)
			{
				$this->tpl->setVariable("ANSWER_IMAGE", ilUtil::getImagePath("right.png", true));
				$this->tpl->setVariable("ANSWER_IMAGE_TITLE", $this->lng->txt("answer_is_right"));
			}
			else
			{
				$this->tpl->setVariable("ANSWER_IMAGE", ilUtil::getImagePath("wrong.png", true));
				$this->tpl->setVariable("ANSWER_IMAGE_TITLE", $this->lng->txt("answer_is_wrong"));
			}
			if ($this->object->get_response() == RESPONSE_SINGLE)
			{
				$state = $this->lng->txt("unselected");
			}
			else
			{
				$state = $this->lng->txt("checkbox_unchecked");
			}
			if ($selected)
			{
				if ($this->object->get_response() == RESPONSE_SINGLE)
				{
					$state = $this->lng->txt("selected");
				}
				else
				{
					$state = $this->lng->txt("checkbox_checked");
				}
			}
			$this->tpl->setVariable("ANSWER_DESCRIPTION", "$state: " . "&quot;<em>" . $answer->get_answertext() . "</em>&quot;");
			$this->tpl->parseCurrentBlock();
		}
	}
}
?>
