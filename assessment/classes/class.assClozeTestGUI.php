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
require_once "./assessment/classes/class.assClozeTest.php";

/**
* Cloze test question GUI representation
*
* The ASS_ClozeTestGUI class encapsulates the GUI representation
* for cloze test questions.
*
* @author		Helmut Schottm�ller <hschottm@tzi.de>
* @version	$Id$
* @module   class.assClozeTestGUI.php
* @modulegroup   Assessment
*/
class ASS_ClozeTestGUI extends ASS_QuestionGUI
{
	/**
	* ASS_ClozeTestGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the ASS_ClozeTestGUI object.
	*
	* @param integer $id The database id of a image map question object
	* @access public
	*/
	function ASS_ClozeTestGUI(
			$id = -1
	)
	{
		$this->ASS_QuestionGUI();
//echo "<br>assClozeTestGUI_constructor";
		$this->object = new ASS_ClozeTest();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
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
		return "qt_cloze";
	}


	function getCommand($cmd)
	{
		if (substr($cmd, 0, 6) == "delete")
		{
			$cmd = "delete";
		}
		if (substr($cmd, 0, 10) == "addTextGap")
		{
			$cmd = "addTextGap";
		}
		if (substr($cmd, 0, 12) == "addSelectGap")
		{
			$cmd = "addSelectGap";
		}

		return $cmd;
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
		$this->getQuestionTemplate("qt_cloze");
		$this->tpl->addBlockFile("QUESTION_DATA", "question_data", "tpl.il_as_qpl_cloze_question.html", true);
		$this->tpl->addBlockFile("OTHER_QUESTION_DATA", "other_question_data", "tpl.il_as_qpl_other_question_data.html", true);
		for ($i = 0; $i < $this->object->get_gap_count(); $i++)
		{
			$gap = $this->object->get_gap($i);
			if ($gap[0]->get_cloze_type() == CLOZE_TEXT)
			{
				$this->tpl->setCurrentBlock("textgap_value");
				foreach ($gap	 as $key => $value)
				{
					$this->tpl->setVariable("VALUE_TEXT_GAP", $value->get_answertext());
					$this->tpl->setVariable("TEXT_VALUE", $this->lng->txt("value"));
					$this->tpl->setVariable("VALUE_GAP_COUNTER", "$i" . "_" . "$key");
					$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock("textgap");
				$answer_array = $this->object->get_gap($i);
				$answer_points = $answer_array[0]->get_points();
				$this->tpl->setVariable("VALUE_TEXT_GAP_POINTS", sprintf("%d", $answer_points));
				$this->tpl->setVariable("ADD_TEXT_GAP", $this->lng->txt("add_gap"));
				$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
				$this->tpl->setVariable("VALUE_GAP_COUNTER", $i + 1);
				$this->tpl->parseCurrentBlock();
			}
			elseif ($gap[0]->get_cloze_type() == CLOZE_SELECT)
			{
				$this->tpl->setCurrentBlock("selectgap_value");
				foreach ($gap as $key => $value)
				{
					$this->tpl->setVariable("TEXT_VALUE", $this->lng->txt("value"));
					$this->tpl->setVariable("VALUE_SELECT_GAP", $value->get_answertext());
					$this->tpl->setVariable("VALUE_GAP_COUNTER", "$i" . "_" . "$key");
					$this->tpl->setVariable("VALUE_GAP", $i);
					$this->tpl->setVariable("VALUE_INDEX", $key);
					$this->tpl->setVariable("TEXT_TRUE", $this->lng->txt("true"));
					if ($value->isStateSet())
					{
						$this->tpl->setVariable("STATUS_CHECKED", " checked=\"checked\"");
					}
					$this->tpl->setVariable("TEXT_WHEN", $this->lng->txt("when"));
					$this->tpl->setVariable("TEXT_SET", $this->lng->txt("radio_set"));
					$this->tpl->setVariable("VALUE_STATUS_COUNTER", $key);
					$this->tpl->setVariable("VALUE_GAP", $i);
					$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
					$this->tpl->setVariable("VALUE_SELECT_GAP_POINTS", sprintf("%d", $value->get_points()));
					$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock("selectgap");
				$this->tpl->setVariable("ADD_SELECT_GAP", $this->lng->txt("add_gap"));
				$this->tpl->setVariable("TEXT_SHUFFLE_ANSWERS", $this->lng->txt("shuffle_answers"));
				$this->tpl->setVariable("VALUE_GAP_COUNTER", "$i");
				if ($gap[0]->get_shuffle())
				{
					$this->tpl->setVariable("SELECTED_YES", " selected=\"selected\"");
				}
				else
				{
					$this->tpl->setVariable("SELECTED_NO", " selected=\"selected\"");
				}
				$this->tpl->setVariable("TXT_YES", $this->lng->txt("yes"));
				$this->tpl->setVariable("TXT_NO", $this->lng->txt("no"));
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("answer_row");
			$name = $gap[0]->get_name();
			if (!$name)
			{
				$name = $this->lng->txt("gap") . " " . ($i+1);
			}
			$this->tpl->setVariable("TEXT_GAP_NAME", $name);
			$this->tpl->setVariable("TEXT_TYPE", $this->lng->txt("type"));
			if ($gap[0]->get_cloze_type() == CLOZE_SELECT)
			{
				$this->tpl->setVariable("SELECTED_SELECT_GAP", " selected=\"selected\"");
			}
			else
			{
				$this->tpl->setVariable("SELECTED_TEXT_GAP", " selected=\"selected\"");
			}
			$this->tpl->setVariable("TEXT_TEXT_GAP", $this->lng->txt("text_gap"));
			$this->tpl->setVariable("TEXT_SELECT_GAP", $this->lng->txt("select_gap"));
			$this->tpl->setVariable("VALUE_GAP_COUNTER", $i);
			$this->tpl->parseCurrentBlock();
		}

		// call to other question data i.e. estimated working time block
		$this->outOtherQuestionData();

		$this->tpl->setCurrentBlock("question_data");
		$this->tpl->setVariable("VALUE_CLOZE_TITLE", $this->object->getTitle());
		$this->tpl->setVariable("VALUE_CLOZE_COMMENT", $this->object->getComment());
		$this->tpl->setVariable("VALUE_CLOZE_AUTHOR", $this->object->getAuthor());
		$this->tpl->setVariable("VALUE_CLOZE_TEXT", $this->object->get_cloze_text());
		$this->tpl->setVariable("TEXT_CREATE_GAPS", $this->lng->txt("create_gaps"));
		$this->tpl->setVariable("CLOZE_ID", $this->object->getId());

		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
		$this->tpl->setVariable("TEXT_COMMENT", $this->lng->txt("description"));
		$this->tpl->setVariable("TEXT_CLOZE_TEXT", $this->lng->txt("cloze_text"));
		$this->tpl->setVariable("TEXT_GAP_DEFINITION", $this->lng->txt("gap_definition"));
		$this->tpl->setVariable("SAVE",$this->lng->txt("save"));
		$this->tpl->setVariable("APPLY","Apply");
		$this->tpl->setVariable("CANCEL",$this->lng->txt("cancel"));
		$this->ctrl->setParameter($this, "sel_question_types", "qt_cloze");
		$this->tpl->setVariable("ACTION_CLOZE_TEST",
			$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->parseCurrentBlock();

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
		$colspan = " colspan=\"4\"";

		$this->tpl->setCurrentBlock("other_question_data");
		$est_working_time = $this->object->getEstimatedWorkingTime();
		$this->tpl->setVariable("TEXT_WORKING_TIME", $this->lng->txt("working_time"));
		$this->tpl->setVariable("TIME_FORMAT", $this->lng->txt("time_format"));
		$this->tpl->setVariable("VALUE_WORKING_TIME", ilUtil::makeTimeSelect("Estimated", false, $est_working_time[h], $est_working_time[m], $est_working_time[s]));
		$this->tpl->parseCurrentBlock();
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
		$saved = false;

		// Delete all existing gaps and create new gaps from the form data
		$this->object->flush_gaps();

		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["clozetext"]))
		{
			$result = 1;
		}

		if (($result) and ($_POST["cmd"]["add"]))
		{
			// You cannot create gaps before you enter the required data
			sendInfo($this->lng->txt("fill_out_all_required_fields_create_gaps"));
			$_POST["cmd"]["add"] = "";
		}

		$this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
		$this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
		$this->object->setComment(ilUtil::stripSlashes($_POST["comment"]));
		$this->object->set_cloze_text(ilUtil::stripSlashes($_POST["clozetext"]));
		// adding estimated working time
		$saved = $saved | $this->writeOtherPostData($result);

		if ($this->ctrl->getCmd() != "createGaps")
		{
			$this->setGapValues();
			$this->setGapPoints();
			$this->setShuffleState();

			foreach ($_POST as $key => $value)
			{
				// Set the cloze type of the gap
				if (preg_match("/clozetype_(\d+)/", $key, $matches))
				{
					$this->object->set_cloze_type($matches[1], $value);
				}
			}

			/*
			for ($i=0; $i<=$this->object->get_gap_count();$i++)
			{
				if (strlen($_POST["textgap_add_".$i]) > 0)
				{
					$j = $i-1;
					$this->object->set_answertext(
						ilUtil::stripSlashes($j),
						ilUtil::stripSlashes($this->object->get_gap_text_count($j)),
						"",
						1
					);
				}
				elseif (strlen($_POST["selectgap_add_".$i]) > 0)
				{
					$this->object->set_answertext(
						ilUtil::stripSlashes($i),
						ilUtil::stripSlashes($this->object->get_gap_text_count($i)),
						"",
						1
					);
				}
			}*/
		}

		$this->object->update_all_gap_params();
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
	* delete
	*/
	function delete()
	{
		$this->writePostData();
		foreach ($_POST["cmd"] as $key => $value)
		{
			// Check, if one of the gap values was deleted
			if (preg_match("/delete_(\d+)_(\d+)/", $key, $matches))
			{
				$selectgap = "selectgap_" . $matches[1] . "_" . $matches[2];
				$this->object->delete_answertext_by_index($matches[1], $matches[2]);
			}
		}
		$this->editQuestion();
	}

	function addSelectGap()
	{
		$this->writePostData();

		$len = strlen("addSelectGap_");
		$i = substr($this->ctrl->getCmd(), $len);
		$this->object->set_answertext(
			ilUtil::stripSlashes($i),
			ilUtil::stripSlashes($this->object->get_gap_text_count($i)),
			"",
			1
		);

		$this->editQuestion();
	}

	function addTextGap()
	{
		$this->writePostData();

		$len = strlen("addTextGap_");
		$i = substr($this->ctrl->getCmd(), $len);
		$j = $i-1;
		$this->object->set_answertext(
			ilUtil::stripSlashes($j),
			ilUtil::stripSlashes($this->object->get_gap_text_count($j)),
			"",
			1
		);

		$this->editQuestion();
	}

	function setGapValues($a_apply_text = true)
	{
//echo "<br>SETGapValues:$a_apply_text:";
		foreach ($_POST as $key => $value)
		{
			// Set gap values
			if ($a_apply_text)
			{
				if (preg_match("/textgap_(\d+)_(\d+)/", $key, $matches))
				{
					$answer_array = $this->object->get_gap($matches[1]);
					if (strlen($value) > 0)
					{
						// Only change gap values <> empty string
						if (array_key_exists($matches[2], $answer_array))
						{
							if (strcmp($value, $answer_array[$matches[2]]->get_answertext()) != 0)
							{
								$this->object->set_answertext(
									ilUtil::stripSlashes($matches[1]),
									ilUtil::stripSlashes($matches[2]),
									ilUtil::stripSlashes($value)
								);
							}
						}
					}
					else
					{
						// Display errormessage: You've tried to set an gap value to an empty string!
					}
				}
			}

			if (preg_match("/selectgap_(\d+)_(\d+)/", $key, $matches))
			{
				$answer_array = $this->object->get_gap($matches[1]);
				if (strlen($value) > 0)
				{
					// Only change gap values <> empty string
					if (array_key_exists($matches[2], $answer_array))
					{
						if (strcmp($value, $answer_array[$matches[2]]->get_answertext()) != 0)
						{
							if ($a_apply_text)
							{
								$this->object->set_answertext(
									ilUtil::stripSlashes($matches[1]),
									ilUtil::stripSlashes($matches[2]),
									ilUtil::stripSlashes($value)
								);
							}
						}
						$points = $_POST["points_$matches[1]_$matches[2]"] or 0.0;
						$this->object->set_single_answer_points($matches[1], $matches[2], $points);
						$state = 0;
						if ($_POST["status_$matches[1]"] == $matches[2])
						{
							$state = 1;
						}
						$this->object->set_single_answer_state($matches[1], $matches[2], $state);
					}
				}
				else
				{
					// Display errormessage: You've tried to set an gap value to an empty string!
				}
			}
		}
	}

	function setGapPoints()
	{
		foreach ($_POST as $key => $value)
		{
			// Set text gap points
			if (preg_match("/^points_(\d+)$/", $key, $matches))
			{
				$points = $value or 0.0;
				$this->object->set_gap_points($matches[1]-1, $value);
			}
		}
	}

	function setShuffleState()
	{
		foreach ($_POST as $key => $value)
		{
			// Set select gap shuffle state
			if (preg_match("/^shuffle_(\d+)$/", $key, $matches))
			{
				$this->object->set_gap_shuffle($matches[1], $value);
			}
		}
	}

	/**
	* create gaps
	*/
	function createGaps()
	{
		$this->writePostData();

		$this->setGapValues(false);
		$this->setGapPoints();
		$this->setShuffleState();

		$this->object->update_all_gap_params();
		$this->editQuestion();
	}


	/**
	* Creates the question output form for the learner
	*
	* Creates the question output form for the learner
	*
	* @access public
	*/
	function outWorkingForm($test_id = "", $is_postponed = false)
	{
		$output = $this->outQuestionPage("CLOZE_TEST", $is_postponed);

		// set solutions
		if ($test_id)
		{
			$solutions =& $this->object->getSolutionValues($test_id);
			foreach ($solutions as $idx => $solution_value)
			{
				$repl_str = "dummy=\"tgap_".$solution_value->value1."\"";
				$output = str_replace($repl_str, $repl_str." value=\"".$solution_value->value2."\"", $output);
				$repl_str = "dummy=\"sgap_".$solution_value->value1."_".$solution_value->value2."\"";
				$output = str_replace($repl_str, $repl_str." selected=\"1\"", $output);
//echo "<br>".$repl_str;
			}
		}

		$this->tpl->setVariable("CLOZE_TEST", $output);
		return;
	}

	/**
	* Creates a preview of the question
	*
	* Creates a preview of the question
	*
	* @access private
	*/
	function outPreviewForm()
	{
		$this->outWorkingForm();
	}

}
?>
