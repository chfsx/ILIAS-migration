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

include_once "./assessment/classes/class.assQuestionGUI.php";
include_once "./assessment/classes/inc.AssessmentConstants.php";

/**
* Single choice question GUI representation
*
* The assSingleChoiceGUI class encapsulates the GUI representation
* for single choice questions.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @module   class.assSingleChoiceGUI.php
* @modulegroup   Assessment
*/
class assSingleChoiceGUI extends assQuestionGUI
{
	/**
	* assSingleChoiceGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the assSingleChoiceGUI object.
	*
	* @param integer $id The database id of a single choice question object
	* @access public
	*/
	function assSingleChoiceGUI(
			$id = -1
	)
	{
		$this->assQuestionGUI();
		include_once "./assessment/classes/class.assSingleChoice.php";
		$this->object = new assSingleChoice();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	function getCommand($cmd)
	{
		if (substr($cmd, 0, 6) == "upload")
		{
			$cmd = "upload";
		}
		if (substr($cmd, 0, 11) == "deleteImage")
		{
			$cmd = "deleteImage";
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
		return "assSingleChoice";
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
		$javascript = "<script type=\"text/javascript\">function initialSelect() {\n%s\n}</script>";
		$graphical_answer_setting = $this->object->getGraphicalAnswerSetting();
		$multiline_answers = $this->object->getMultilineAnswerSetting();
		if ($graphical_answer_setting == 0)
		{
			for ($i = 0; $i < $this->object->getAnswerCount(); $i++)
			{
				$answer = $this->object->getAnswer($i);
				if (strlen($answer->getImage())) $graphical_answer_setting = 1;
			}
		}
		$this->object->setGraphicalAnswerSetting($graphical_answer_setting);
		$this->getQuestionTemplate("qt_multiple_choice_sr");
		$this->tpl->addBlockFile("QUESTION_DATA", "question_data", "tpl.il_as_qpl_mc_sr.html", true);
		// output of existing single response answers
		if ($this->object->getAnswerCount() > 0)
		{
			$this->tpl->setCurrentBlock("answersheading");
			$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
			$this->tpl->setVariable("TEXT_ANSWER_TEXT", $this->lng->txt("answer_text"));
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("selectall");
			$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("existinganswers");
			$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
			$this->tpl->setVariable("MOVE", $this->lng->txt("move"));
			$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
			$this->tpl->parseCurrentBlock();
		}
		for ($i = 0; $i < $this->object->getAnswerCount(); $i++)
		{
			$answer = $this->object->getAnswer($i);
			if ($graphical_answer_setting == 1)
			{
				$imagefilename = $this->object->getImagePath() . $answer->getImage();
				if (!@file_exists($imagefilename))
				{
					$answer->setImage("");
				}
				if (strlen($answer->getImage()))
				{
					$imagepath = $this->object->getImagePathWeb() . $answer->getImage();
					$this->tpl->setCurrentBlock("graphical_answer_image");
					$this->tpl->setVariable("IMAGE_FILE", $imagepath);
					if (strlen($answer->getAnswertext()))
					{
						$this->tpl->setVariable("IMAGE_ALT", ilUtil::prepareFormOutput($answer->getAnswertext()));
					}
					else
					{
						$this->tpl->setVariable("IMAGE_ALT", $this->lng->txt("image"));
					}
					$this->tpl->setVariable("ANSWER_ORDER", $answer->getOrder());
					$this->tpl->setVariable("DELETE_IMAGE", $this->lng->txt("delete_image"));
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock("graphical_answer");
				$this->tpl->setVariable("ANSWER_ORDER", $answer->getOrder());
				$this->tpl->setVariable("UPLOAD_IMAGE", $this->lng->txt("upload_image"));
				$this->tpl->setVariable("VALUE_IMAGE", $answer->getImage());
				$this->tpl->parseCurrentBlock();
			}
			if ($multiline_answers)
			{
				$this->tpl->setCurrentBlock("show_textarea");
				$this->tpl->setVariable("ANSWER_ANSWER_ORDER", $answer->getOrder());
				$this->tpl->setVariable("VALUE_ANSWER", ilUtil::prepareFormOutput($answer->getAnswertext()));
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->setCurrentBlock("show_textinput");
				$this->tpl->setVariable("ANSWER_ANSWER_ORDER", $answer->getOrder());
				$this->tpl->setVariable("VALUE_ANSWER", ilUtil::prepareFormOutput($answer->getAnswertext()));
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("answers");
			$this->tpl->setVariable("ANSWER_ORDER", $answer->getOrder());
			$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_POINTS", sprintf("%d", $answer->getPoints()));
			$this->tpl->setVariable("VALUE_TRUE", $this->lng->txt("true"));
			$this->tpl->parseCurrentBlock();
		}
		// call to other question data i.e. estimated working time block
		$this->outOtherQuestionData();

		$internallinks = array(
			"lm" => $this->lng->txt("obj_lm"),
			"st" => $this->lng->txt("obj_st"),
			"pg" => $this->lng->txt("obj_pg"),
			"glo" => $this->lng->txt("glossary_term")
		);
		foreach ($internallinks as $key => $value)
		{
			$this->tpl->setCurrentBlock("internallink");
			$this->tpl->setVariable("TYPE_INTERNAL_LINK", $key);
			$this->tpl->setVariable("TEXT_INTERNAL_LINK", $value);
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setCurrentBlock("HeadContent");
		if ($this->object->getAnswerCount() == 0)
		{
			$this->tpl->setVariable("CONTENT_BLOCK", sprintf($javascript, "document.frm_multiple_choice.title.focus();"));
		}
		else
		{
			switch ($this->ctrl->getCmd())
			{
				case "add":
					$nrOfAnswers = $_POST["nrOfAnswers"];
					if ((strcmp($nrOfAnswers, "yn") == 0) || (strcmp($nrOfAnswers, "tf") == 0)) $nrOfAnswers = 2;
					$this->tpl->setVariable("CONTENT_BLOCK", sprintf($javascript, "document.frm_multiple_choice.answer_".($this->object->getAnswerCount() - $nrOfAnswers).".focus(); document.getElementById('answer_".($this->object->getAnswerCount() - $nrOfAnswers)."').scrollIntoView(\"true\");"));
					break;
				case "deleteAnswer":
					if ($this->object->getAnswerCount() == 0)
					{
						$this->tpl->setVariable("CONTENT_BLOCK", sprintf($javascript, "document.frm_multiple_choice.title.focus();"));
					}
					else
					{
						$this->tpl->setVariable("CONTENT_BLOCK", sprintf($javascript, "document.frm_multiple_choice.answer_".($this->object->getAnswerCount() - 1).".focus(); document.getElementById('answer_".($this->object->getAnswerCount() - 1)."').scrollIntoView(\"true\");"));
					}
					break;
				default:
					$this->tpl->setVariable("CONTENT_BLOCK", sprintf($javascript, "document.frm_multiple_choice.title.focus();"));
					break;
			}
		}
		$this->tpl->parseCurrentBlock();

		for ($i = 1; $i < 10; $i++)
		{
			$this->tpl->setCurrentBlock("numbers");
			$this->tpl->setVariable("VALUE_NUMBER", $i);
			if ($i == 1)
			{
				$this->tpl->setVariable("TEXT_NUMBER", $i . " " . $this->lng->txt("answer"));
			}
			else
			{
				$this->tpl->setVariable("TEXT_NUMBER", $i . " " . $this->lng->txt("answers"));
			}
			$this->tpl->parseCurrentBlock();
		}
		// add yes/no answers
		$this->tpl->setCurrentBlock("numbers");
		$this->tpl->setVariable("VALUE_NUMBER", "yn");
		$this->tpl->setVariable("TEXT_NUMBER", $this->lng->txt("add_answer_yn"));
		$this->tpl->parseCurrentBlock();
		// add true/false answers
		$this->tpl->setCurrentBlock("numbers");
		$this->tpl->setVariable("VALUE_NUMBER", "tf");
		$this->tpl->setVariable("TEXT_NUMBER", $this->lng->txt("add_answer_tf"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("question_data");
		$this->tpl->setVariable("MULTIPLE_CHOICE_ID", $this->object->getId());
		$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_TITLE", ilUtil::prepareFormOutput($this->object->getTitle()));
		$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_COMMENT", ilUtil::prepareFormOutput($this->object->getComment()));
		$this->tpl->setVariable("VALUE_MULTIPLE_CHOICE_AUTHOR", ilUtil::prepareFormOutput($this->object->getAuthor()));
		$questiontext = $this->object->getQuestion();
		//$questiontext = preg_replace("/<br \/>/", "\n", $questiontext);
		$this->tpl->setVariable("VALUE_QUESTION", ilUtil::prepareFormOutput($questiontext));
		$this->tpl->setVariable("VALUE_ADD_ANSWER", $this->lng->txt("add"));
		$this->tpl->setVariable("TEXT_GRAPHICAL_ANSWERS", $this->lng->txt("graphical_answers"));
		$this->tpl->setVariable("TEXT_HIDE_GRAPHICAL_ANSWER_SUPPORT", $this->lng->txt("graphical_answers_hide"));
		$this->tpl->setVariable("TEXT_SHOW_GRAPHICAL_ANSWER_SUPPORT", $this->lng->txt("graphical_answers_show"));
		if ($this->object->getGraphicalAnswerSetting() == 1)
		{
			$this->tpl->setVariable("SELECTED_SHOW_GRAPHICAL_ANSWER_SUPPORT", " selected=\"selected\"");
		}
		if ($multiline_answers)
		{
			$this->tpl->setVariable("SELECTED_SHOW_MULTILINE_ANSWERS", " selected=\"selected\"");
		}
		$this->tpl->setVariable("TEXT_HIDE_MULTILINE_ANSWERS", $this->lng->txt("multiline_answers_hide"));
		$this->tpl->setVariable("TEXT_SHOW_MULTILINE_ANSWERS", $this->lng->txt("multiline_answers_show"));
		$this->tpl->setVariable("SET_EDIT_MODE", $this->lng->txt("set_edit_mode"));
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
		$this->tpl->setVariable("TEXT_SOLUTION_HINT", $this->lng->txt("solution_hint"));
		if (count($this->object->suggested_solutions))
		{
			$solution_array = $this->object->getSuggestedSolution(0);
			include_once "./assessment/classes/class.assQuestion.php";
			$href = assQuestion::_getInternalLinkHref($solution_array["internal_link"]);
			$this->tpl->setVariable("TEXT_VALUE_SOLUTION_HINT", " <a href=\"$href\" target=\"content\">" . $this->lng->txt("solution_hint"). "</a> ");
			$this->tpl->setVariable("BUTTON_REMOVE_SOLUTION", $this->lng->txt("remove"));
			$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("change"));
			$this->tpl->setVariable("VALUE_SOLUTION_HINT", $solution_array["internal_link"]);
		}
		else
		{
			$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("add"));
		}
		$this->tpl->setVariable("SAVE",$this->lng->txt("save"));
		$this->tpl->setVariable("SAVE_EDIT", $this->lng->txt("save_edit"));
		$this->tpl->setVariable("CANCEL",$this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->ctrl->setParameter($this, "sel_question_types", "assSingleChoice");
		$this->tpl->setVariable("ACTION_MULTIPLE_CHOICE_TEST", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt("assSingleChoice"));

		$this->tpl->parseCurrentBlock();
		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->addPlugin("latex");
		include_once "./classes/class.ilObject.php";
		$obj_id = $_GET["q_id"];
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, $obj_type, "assessment");

		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("BODY_ATTRIBUTES", " onload=\"initialSelect();\""); 
		$this->tpl->parseCurrentBlock();
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
			$nrOfAnswers = $_POST["nrOfAnswers"];
			switch ($nrOfAnswers)
			{
				case "tf":
					// add a true/false answer template
					$this->object->addAnswer(
						$this->lng->txt("true"),
						0,
						0,
						count($this->object->answers),
						""
					);
					$this->object->addAnswer(
						$this->lng->txt("false"),
						0,
						0,
						count($this->object->answers),
						""
					);
					break;
				case "yn":
					// add a yes/no answer template
					$this->object->addAnswer(
						$this->lng->txt("yes"),
						0,
						0,
						count($this->object->answers),
						""
					);
					$this->object->addAnswer(
						$this->lng->txt("no"),
						0,
						0,
						count($this->object->answers),
						""
					);
					break;
				default:
					for ($i = 0; $i < $nrOfAnswers; $i++)
					{
						$this->object->addAnswer(
							$this->lng->txt(""),
							0,
							0,
							count($this->object->answers),
							""
						);
					}
					break;
			}
		}

		$this->editQuestion();
	}

	/**
	* delete checked answers
	*/
	function deleteAnswer()
	{
		$this->writePostData();
		$answers = $_POST["chb_answers"];
		if (is_array($answers))
		{
			arsort($answers);
			foreach ($answers as $answer)
			{
				$this->object->deleteAnswer($answer);
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
				if (strlen($value) == 0)
				{
					if (strlen($_POST["uploaded_image_".$matches[1]]) == 0)
					{
						return false;
					}
				}
			}
		}

		return true;
	}

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
		include_once "./classes/class.ilObjAdvancedEditing.php";
		$questiontext = ilUtil::stripSlashes($_POST["question"], true, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment"));
		$questiontext = preg_replace("/[\n\r]+/", "<br />", $questiontext);
		$this->object->setQuestion($questiontext);
		$this->object->setSuggestedSolution($_POST["solution_hint"], 0);
		$this->object->setShuffle($_POST["shuffle"]);
		$this->object->setMultilineAnswerSetting($_POST["multilineAnswers"]);
		$this->object->setGraphicalAnswerSetting($_POST["graphicalAnswerSupport"]);

		$saved = $this->writeOtherPostData($result);

		// Delete all existing answers and create new answers from the form data
		$this->object->flushAnswers();
		$graphical_answer_setting = $this->object->getGraphicalAnswerSetting();
		// Add all answers from the form into the object
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/answer_(\d+)/", $key, $matches))
			{
				$answer_image = $_POST["uploaded_image_".$matches[1]];
				if ($graphical_answer_setting == 1)
				{
					foreach ($_FILES as $key2 => $value2)
					{
						if (preg_match("/image_(\d+)/", $key2, $matches2))
						{
							if ($matches[1] == $matches2[1])
							{
								if ($value2["tmp_name"])
								{
									// upload the image
									if ($this->object->getId() <= 0)
									{
										$this->object->saveToDb();
										$saved = true;
										$this->error .= $this->lng->txt("question_saved_for_upload") . "<br />";
									}
									$upload_result = $this->object->setImageFile($value2['name'], $value2['tmp_name']);
									switch ($upload_result)
									{
										case 0:
											$_POST["image_".$matches2[1]] = $value2['name'];
											$answer_image = $value2['name'];
											break;
										case 1:
											$this->error .= $this->lng->txt("error_image_upload_wrong_format") . "<br />";
											break;
										case 2:
											$this->error .= $this->lng->txt("error_image_upload_copy_file") . "<br />";
											break;
									}
								}
							}
						}
					}
				}
				$points = $_POST["points_$matches[1]"];
				if (!preg_match("/\d+/", $points))
				{
					$points = 0.0;
				}
				$answertext = ilUtil::stripSlashes($_POST["$key"], true, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment"));
				$answertext = preg_replace("/[\n\r]+/", "<br />", $answertext);
				$this->object->addAnswer(
					$answertext,
					ilUtil::stripSlashes($points),
					0,
					ilUtil::stripSlashes($matches[1]),
					$answer_image
					);
			}
		}

		if ($this->object->getMaximumPoints() < 0)
		{
			$result = 1;
			$this->setErrorMessage($this->lng->txt("enter_enough_positive_points"));
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

	function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		$test_output = $this->getTestOutput($active_id, $pass, $is_postponed, $use_post_solutions); 
		$this->tpl->setVariable("QUESTION_OUTPUT", $test_output);
		$this->tpl->setVariable("FORMACTION", $formaction);
	}

	function getSolutionOutput($active_id, $pass = NULL, $graphicalOutput = FALSE)
	{
		// get page object output
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id);

		// shuffle output
		$keys = array_keys($this->object->answers);

		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = "";
		if ($active_id)
		{
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
			foreach ($solutions as $idx => $solution_value)
			{
				$user_solution = $solution_value["value1"];
			}
		}
		else
		{
			$found_index = -1;
			$max_points = 0;
			foreach ($this->object->answers as $index => $answer)
			{
				if ($answer->getPoints() > $max_points)
				{
					$max_points = $answer->getPoints();
					$found_index = $index;
				}
			}
			$user_solution = $found_index;
		}
		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_mc_sr_output_solution.html", TRUE, TRUE, TRUE);
		foreach ($keys as $answer_id)
		{
			$answer = $this->object->answers[$answer_id];
			if ($active_id)
			{
				if ($graphicalOutput)
				{
					// output of ok/not ok icons for user entered solutions
					$ok = FALSE;
					if (strcmp($user_solution, $answer_id) == 0)
					{
						if ($answer->getPoints() == $this->object->getMaximumPoints())
						{
							$ok = TRUE;
						}
						else
						{
							$ok = FALSE;
						}
						if ($ok)
						{
							$template->setCurrentBlock("icon_ok");
							$template->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.gif"));
							$template->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
							$template->parseCurrentBlock();
						}
						else
						{
							$template->setCurrentBlock("icon_ok");
							$template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.gif"));
							$template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
							$template->parseCurrentBlock();
						}
					}
				}
			}
			if (strlen($answer->getImage()))
			{
				$template->setCurrentBlock("answer_image");
				$template->setVariable("ANSWER_IMAGE_URL", $this->object->getImagePathWeb() . $answer->getImage());
				$alt = $answer->getImage();
				if (strlen($answer->getAnswertext()))
				{
					$alt = $answer->getAnswertext();
				}
				$template->setVariable("ANSWER_IMAGE_ALT", $alt);
				$template->setVariable("ANSWER_IMAGE_TITLE", $alt);
				$template->parseCurrentBlock();
			}
			$template->setCurrentBlock("answer_row");
			$answertext = ilUtil::insertLatexImages($answer->getAnswertext(), "\<span class\=\"latex\">", "\<\/span>", URL_TO_LATEX);
			$template->setVariable("ANSWER_TEXT", $answertext);
			if (strcmp($user_solution, $answer_id) == 0)
			{
				$template->setVariable("SOLUTION_IMAGE", ilUtil::getImagePath("radiobutton_checked.gif"));
				$template->setVariable("SOLUTION_ALT", $this->lng->txt("checked"));
			}
			else
			{
				$template->setVariable("SOLUTION_IMAGE", ilUtil::getImagePath("radiobutton_unchecked.gif"));
				$template->setVariable("SOLUTION_ALT", $this->lng->txt("unchecked"));
			}
			$template->parseCurrentBlock();
		}
		$questiontext = $this->object->getQuestion();
		$questiontext = ilUtil::insertLatexImages($questiontext, "\<span class\=\"latex\">", "\<\/span>", URL_TO_LATEX);
		$template->setVariable("QUESTIONTEXT", $questiontext);
		$questionoutput = $template->get();
		$questionoutput = str_replace("<div xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" class=\"ilc_Question\"></div>", $questionoutput, $pageoutput);
		$questionoutput = preg_replace("/<div class\=\"ilc_PageTitle\"\>.*?\<\/div\>/", "", $questionoutput);
		return $questionoutput;
	}
	
	function getPreview()
	{
		// shuffle output
		$keys = array_keys($this->object->answers);
		if ($this->object->getShuffle())
		{
			$keys = $this->object->pcArrayShuffle($keys);
		}

		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_mc_sr_output.html", TRUE, TRUE, TRUE);
		foreach ($keys as $answer_id)
		{
			$answer = $this->object->answers[$answer_id];
			if (strlen($answer->getImage()))
			{
				$template->setCurrentBlock("answer_image");
				$template->setVariable("ANSWER_IMAGE_URL", $this->object->getImagePathWeb() . $answer->getImage());
				$alt = $answer->getImage();
				if (strlen($answer->getAnswertext()))
				{
					$alt = $answer->getAnswertext();
				}
				$template->setVariable("ANSWER_IMAGE_ALT", $alt);
				$template->setVariable("ANSWER_IMAGE_TITLE", $alt);
				$template->parseCurrentBlock();
			}
			$template->setCurrentBlock("answer_row");
			$template->setVariable("ANSWER_ID", $answer_id);
			$answertext = ilUtil::insertLatexImages($answer->getAnswertext(), "\<span class\=\"latex\">", "\<\/span>", URL_TO_LATEX);
			$template->setVariable("ANSWER_TEXT", $answertext);
			$template->parseCurrentBlock();
		}
		$questiontext = $this->object->getQuestion();
		$questiontext = ilUtil::insertLatexImages($questiontext, "\<span class\=\"latex\">", "\<\/span>", URL_TO_LATEX);
		$template->setVariable("QUESTIONTEXT", $questiontext);
		$questionoutput = $template->get();
		$questionoutput = preg_replace("/\<div[^>]*?>(.*)\<\/div>/is", "\\1", $questionoutput);
		return $questionoutput;
	}

	function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		// get page object output
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id);

		// shuffle output
		$keys = array_keys($this->object->answers);
		if ($this->object->getShuffle())
		{
			$keys = $this->object->pcArrayShuffle($keys);
		}

		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = "";
		if ($active_id)
		{
			$solutions = NULL;
			include_once "./assessment/classes/class.ilObjTest.php";
			if (ilObjTest::_getHidePreviousResults($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
			foreach ($solutions as $idx => $solution_value)
			{
				$user_solution = $solution_value["value1"];
			}
		}
		
		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_mc_sr_output.html", TRUE, TRUE, TRUE);
		foreach ($keys as $answer_id)
		{
			$answer = $this->object->answers[$answer_id];
			if (strlen($answer->getImage()))
			{
				$template->setCurrentBlock("answer_image");
				$template->setVariable("ANSWER_IMAGE_URL", $this->object->getImagePathWeb() . $answer->getImage());
				$alt = $answer->getImage();
				if (strlen($answer->getAnswertext()))
				{
					$alt = $answer->getAnswertext();
				}
				$template->setVariable("ANSWER_IMAGE_ALT", $alt);
				$template->setVariable("ANSWER_IMAGE_TITLE", $alt);
				$template->parseCurrentBlock();
			}
			$template->setCurrentBlock("answer_row");
			$template->setVariable("ANSWER_ID", $answer_id);
			$answertext = ilUtil::insertLatexImages($answer->getAnswertext(), "\<span class\=\"latex\">", "\<\/span>", URL_TO_LATEX);
			$template->setVariable("ANSWER_TEXT", $answertext);
			if (strcmp($user_solution, $answer_id) == 0)
			{
				$template->setVariable("CHECKED_ANSWER", " checked=\"checked\"");
			}
			$template->parseCurrentBlock();
		}
		$questiontext = $this->object->getQuestion();
		$questiontext = ilUtil::insertLatexImages($questiontext, "\<span class\=\"latex\">", "\<\/span>", URL_TO_LATEX);
		$template->setVariable("QUESTIONTEXT", $questiontext);
		$questionoutput = $template->get();
		$questionoutput = str_replace("<div xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" class=\"ilc_Question\"></div>", $questionoutput, $pageoutput);
		return $questionoutput;
	}

	function addSuggestedSolution()
	{
		$_SESSION["subquestion_index"] = 0;
		if ($_POST["cmd"]["addSuggestedSolution"])
		{
			if ($this->writePostData())
			{
				sendInfo($this->getErrorMessage());
				$this->editQuestion();
				return;
			}
			if (!$this->checkInput())
			{
				sendInfo($this->lng->txt("fill_out_all_required_fields_add_answer"));
				$this->editQuestion();
				return;
			}
		}
		$this->object->saveToDb();
		$_GET["q_id"] = $this->object->getId();
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
		$this->getQuestionTemplate("qt_multiple_choice_sr");
		parent::addSuggestedSolution();
	}

	/**
	* upload an image
	*/
	function upload()
	{
		$this->writePostData();
		$this->editQuestion();
	}
	
	function deleteImage()
	{
		if ($this->writePostData())
		{
			sendInfo($this->getErrorMessage());
			$this->editQuestion();
			return;
		}
		$imageorder = "";
		foreach ($_POST["cmd"] as $key => $value)
		{
			if (preg_match("/deleteImage_(\d+)/", $key, $matches))
			{
				$imageorder = $matches[1];
			}
		}
		for ($i = 0; $i < $this->object->getAnswerCount(); $i++)
		{
			$answer = $this->object->getAnswer($i);
			if ($answer->getOrder() == $imageorder)
			{
				$this->object->deleteImage($answer->getImage());
				$this->object->answers[$i]->setImage("");
			}
		}
		$this->editQuestion();
	}

	function editMode()
	{
		global $ilUser;
		
		$this->object->setMultilineAnswerSetting($_POST["multilineAnswers"]);
		$this->object->setGraphicalAnswerSetting($_POST["graphicalAnswerSupport"]);
		$this->writePostData();
		$this->editQuestion();
	}
}
?>
