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
require_once "./assessment/classes/class.assOrderingQuestion.php";

/**
* Ordering question GUI representation
*
* The ASS_OrderingQuestionGUI class encapsulates the GUI representation
* for ordering questions.
*
* @author		Helmut Schottmüller <hschottm@tzi.de>
* @version	$Id$
* @module   class.assOrderingQuestionGUI.php
* @modulegroup   Assessment
*/
class ASS_OrderingQuestionGUI extends ASS_QuestionGUI
{

	/**
	* ASS_OrderingQuestionGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the ASS_OrderingQuestionGUI object.
	*
	* @param integer $id The database id of a ordering question object
	* @access public
	*/
	function ASS_OrderingQuestionGUI(
			$id = -1
	)
	{
		$this->ASS_QuestionGUI();
		$this->object = new ASS_OrderingQuestion();
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
		return "qt_ordering";
	}

	function getCommand($cmd)
	{
		if (substr($cmd, 0, 6) == "delete")
		{
			$cmd = "delete";
		}
		if (substr($cmd, 0, 6) == "upload")
		{
			$cmd = "upload";
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
		$this->getQuestionTemplate("qt_ordering");
		$this->tpl->addBlockFile("QUESTION_DATA", "question_data", "tpl.il_as_qpl_ordering.html", true);
		$this->tpl->addBlockFile("OTHER_QUESTION_DATA", "other_question_data", "tpl.il_as_qpl_other_question_data.html", true);

		// Output of existing answers
		for ($i = 0; $i < $this->object->get_answer_count(); $i++)
		{
			$this->tpl->setCurrentBlock("deletebutton");
			$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
			$this->tpl->setVariable("ANSWER_ORDER", $i);
			$this->tpl->parseCurrentBlock();

			$thisanswer = $this->object->get_answer($i);
			if ($this->object->get_ordering_type() == OQ_PICTURES)
			{
				$this->tpl->setCurrentBlock("order_pictures");
				$this->tpl->setVariable("ANSWER_ORDER", $i);
				$this->tpl->setVariable("TEXT_ANSWER_PICTURE", $this->lng->txt("answer_picture"));

				$filename = $thisanswer->get_answertext();
				if ($filename)
				{
					$imagepath = $this->object->getImagePathWeb() . $thisanswer->get_answertext();
					$this->tpl->setVariable("UPLOADED_IMAGE", "<img src=\"$imagepath.thumb.jpg\" alt=\"" . $thisanswer->get_answertext() . "\" border=\"\" />");
					$this->tpl->setVariable("IMAGE_FILENAME", $thisanswer->get_answertext());
					$this->tpl->setVariable("VALUE_ANSWER", "");
					//$thisanswer->get_answertext()
				}
				$this->tpl->setVariable("UPLOAD", $this->lng->txt("upload"));
			}
			elseif ($this->object->get_ordering_type() == OQ_TERMS)
			{
				$this->tpl->setCurrentBlock("order_terms");
				$this->tpl->setVariable("TEXT_ANSWER_TEXT", $this->lng->txt("answer_text"));
				$this->tpl->setVariable("ANSWER_ORDER", $i);
				$this->tpl->setVariable("VALUE_ANSWER", $thisanswer->get_answertext());
			}
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("answers");
			$this->tpl->setVariable("VALUE_ANSWER_COUNTER", $thisanswer->get_order() + 1);
			$anchor = "#answer_" . ($thisanswer->get_order() + 1);
			$this->tpl->setVariable("ANSWER_ORDER", $thisanswer->get_order());
			$this->tpl->setVariable("TEXT_SOLUTION_ORDER", $this->lng->txt("solution_order"));
			$this->tpl->setVariable("TEXT_ANSWER", $this->lng->txt("answer"));
			$this->tpl->setVariable("VALUE_ORDER", $thisanswer->get_solution_order());
			$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
			$this->tpl->setVariable("VALUE_ORDERING_POINTS", sprintf("%d", $thisanswer->get_points()));
			$this->tpl->parseCurrentBlock();
		}

		if ($this->ctrl->getCmd() == "addItem")
		{
			if ($this->object->get_ordering_type() == OQ_PICTURES)
			{
				$this->tpl->setCurrentBlock("order_pictures");
				$this->tpl->setVariable("ANSWER_ORDER", $this->object->get_answer_count());
				$this->tpl->setVariable("VALUE_ANSWER", "");
				$this->tpl->setVariable("UPLOAD", $this->lng->txt("upload"));
				$this->tpl->setVariable("TEXT_ANSWER_PICTURE", $this->lng->txt("answer_picture"));
			}
			elseif ($this->object->get_ordering_type() == OQ_TERMS)
			{
				$this->tpl->setCurrentBlock("order_terms");
				$this->tpl->setVariable("TEXT_ANSWER_TEXT", $this->lng->txt("answer_text"));
				$this->tpl->setVariable("ANSWER_ORDER", $this->object->get_answer_count());
				$this->tpl->setVariable("VALUE_ASNWER", "");
			}
			$this->tpl->parseCurrentBlock();

			// Create an empty answer
			$this->tpl->setCurrentBlock("answers");
			//$this->tpl->setVariable("TEXT_ANSWER_TEXT", $this->lng->txt("answer_text"));
			$this->tpl->setVariable("TEXT_ANSWER", $this->lng->txt("answer"));
			$this->tpl->setVariable("VALUE_ANSWER_COUNTER", $this->object->get_answer_count() + 1);
			$anchor = "#answer_" . ($this->object->get_answer_count() + 1);
			$this->tpl->setVariable("ANSWER_ORDER", $this->object->get_answer_count());
			$this->tpl->setVariable("TEXT_SOLUTION_ORDER", $this->lng->txt("solution_order"));
			$this->tpl->setVariable("VALUE_ORDER", $this->object->get_max_solution_order() + 1);
			$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
			$this->tpl->setVariable("VALUE_ORDERING_POINTS", sprintf("%d", 0));
			$this->tpl->parseCurrentBlock();
		}
		// call to other question data i.e. material, estimated working time block
		$this->outOtherQuestionData();

		$this->tpl->setCurrentBlock("question_data");
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
		$this->tpl->setVariable("ORDERING_ID", $this->object->getId());
		$this->tpl->setVariable("VALUE_ORDERING_TITLE", $this->object->getTitle());
		$this->tpl->setVariable("VALUE_ORDERING_COMMENT", $this->object->getComment());
		$this->tpl->setVariable("VALUE_ORDERING_AUTHOR", $this->object->getAuthor());
		$this->tpl->setVariable("VALUE_QUESTION", $this->object->get_question());
		$this->tpl->setVariable("VALUE_ADD_ANSWER", $this->lng->txt("add_answer"));
		$this->tpl->setVariable("TEXT_TYPE", $this->lng->txt("type"));
		$this->tpl->setVariable("TEXT_TYPE_PICTURES", $this->lng->txt("order_pictures"));
		$this->tpl->setVariable("TEXT_TYPE_TERMS", $this->lng->txt("order_terms"));
		if ($this->object->get_ordering_type() == OQ_TERMS)
		{
			$this->tpl->setVariable("SELECTED_TERMS", " selected=\"selected\"");
		}
		elseif ($this->object->get_ordering_type() == OQ_PICTURES)
		{
			$this->tpl->setVariable("SELECTED_PICTURES", " selected=\"selected\"");
		}

		$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("APPLY", $this->lng->txt("apply"));
		$this->tpl->setVariable("CANCEL", $this->lng->txt("cancel"));
		$this->ctrl->setParameter($this, "sel_question_types", "qt_ordering");
		$this->tpl->setVariable("ACTION_ORDERING_QUESTION",
			$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->parseCurrentBlock();
	}

	/**
	* apply changes
	*/
	function apply()
	{
		$this->writePostData();
		$this->editQuestion();
	}

	/**
	* save question to db and return to question pool
	*/
	function save()
	{
		$this->writePostData();
		$this->object->saveToDb();
		$this->ctrl->returnToParent($this);
	}

	function addItem()
	{
		$ok = true;
		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]))
		{
			// You cannot add answers before you enter the required data
			sendInfo($this->lng->txt("fill_out_all_required_fields_add_answers"));
			$ok = false;
		}
		else
		{
			foreach ($_POST as $key => $value)
			{
				if (preg_match("/answer_(\d+)/", $key, $matches))
				{
					if (!$value)
					{
						$ok = false;
						sendInfo($this->lng->txt("fill_out_all_answer_fields"));
					}
			 	}
			}
		}

		$this->writePostData();
		$this->editQuestion();
	}

	/**
	* delete matching pair
	*/
	function delete()
	{
		$this->writePostData();

		// Delete an answer if the delete button was pressed
		foreach ($_POST[cmd] as $key => $value)
		{
			if (preg_match("/delete_(\d+)/", $key, $matches))
			{
				$this->object->delete_answer($matches[1]);
			}
		}
		$this->editQuestion();
	}

	/**
	* upload matching picture
	*/
	function upload()
	{
		$this->writePostData();
		$this->editQuestion();
	}

	/**
	* Sets the extra fields i.e. estimated working time and material of a question from a posted create/edit form
	*
	* Sets the extra fields i.e. estimated working time and material of a question from a posted create/edit form
	*
	* @access private
	*/
	function outOtherQuestionData()
	{
		$colspan = " colspan=\"3\"";

		if (!empty($this->object->materials))
		{
			$this->tpl->setCurrentBlock("select_block");
			foreach ($this->object->materials as $key => $value)
			{
				$this->tpl->setVariable("MATERIAL_VALUE", $key);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("materiallist_block");
			$i = 1;
			foreach ($this->object->materials as $key => $value)
			{
				$this->tpl->setVariable("MATERIAL_COUNTER", $i);
				$this->tpl->setVariable("MATERIAL_VALUE", $key);
				$this->tpl->setVariable("MATERIAL_FILE_VALUE", $value);
				$this->tpl->parseCurrentBlock();
				$i++;
			}
			$this->tpl->setVariable("UPLOADED_MATERIAL", $this->lng->txt("uploaded_material"));
			$this->tpl->setVariable("VALUE_MATERIAL_DELETE", $this->lng->txt("delete"));
			$this->tpl->setVariable("COLSPAN_MATERIAL", $colspan);
			$this->tpl->parse("mainselect_block");
		}

		$this->tpl->setCurrentBlock("other_question_data");
		$est_working_time = $this->object->getEstimatedWorkingTime();
		$this->tpl->setVariable("TEXT_WORKING_TIME", $this->lng->txt("working_time"));
		$this->tpl->setVariable("TIME_FORMAT", $this->lng->txt("time_format"));
		$this->tpl->setVariable("VALUE_WORKING_TIME", ilUtil::makeTimeSelect("Estimated", false, $est_working_time[h], $est_working_time[m], $est_working_time[s]));
		$this->tpl->setVariable("TEXT_MATERIAL", $this->lng->txt("material"));
		$this->tpl->setVariable("TEXT_MATERIAL_FILE", $this->lng->txt("material_file"));
		$this->tpl->setVariable("VALUE_MATERIAL_UPLOAD", $this->lng->txt("upload"));
		$this->tpl->setVariable("COLSPAN_MATERIAL", $colspan);
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

		// Delete all existing answers and create new answers from the form data
		$this->object->flush_answers();

		$this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
		$this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
		$this->object->setComment(ilUtil::stripSlashes($_POST["comment"]));
		$this->object->set_question(ilUtil::stripSlashes($_POST["question"]));
		$this->object->setShuffle($_POST["shuffle"]);

		// adding estimated working time and materials uris
		$saved = $saved | $this->writeOtherPostData($result);
		$this->object->set_ordering_type($_POST["ordering_type"]);

		// Add answers from the form
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/answer_(\d+)/", $key, $matches))
			{
				if ($this->object->get_ordering_type() == OQ_PICTURES)
				{
					if ($_FILES[$key]["tmp_name"])
					{
						// upload the ordering picture
						if ($this->object->getId() <= 0)
						{
							$this->object->saveToDb();
							$saved = true;
							sendInfo($this->lng->txt("question_saved_for_upload"));
						}
						$this->object->set_image_file($_FILES[$key]['name'], $_FILES[$key]['tmp_name']);
						$_POST[$key] = $_FILES[$key]['name'];
					}
				}
				$this->object->add_answer(
					ilUtil::stripSlashes($_POST["$key"]),
					ilUtil::stripSlashes($_POST["points_$matches[1]"]),
					ilUtil::stripSlashes($matches[1]),
					ilUtil::stripSlashes($_POST["order_$matches[1]"])
				);
			}
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
	function outWorkingForm($test_id = "", $is_postponed = false)
	{
		$output = $this->outQuestionPage("ORDERING_QUESTION", $is_postponed);

		// set solutions
		if ($test_id)
		{
			$solutions =& $this->object->getSolutionValues($test_id);
			foreach ($solutions as $idx => $solution_value)
			{
				$repl_str = "dummy=\"ord".$solution_value->value1."\"";
//echo "<br>".$repl_str;
				$output = str_replace($repl_str, $repl_str." value=\"".$solution_value->value2."\"", $output);
			}
		}

		$this->tpl->setVariable("ORDERING_QUESTION", $output);

		if (!empty($this->object->materials))
		{
			$i = 1;
			$this->tpl->setCurrentBlock("material_preview");
			foreach ($this->object->materials as $key => $value)
			{
				$this->tpl->setVariable("COUNTER", $i++);
				$this->tpl->setVariable("VALUE_MATERIAL_DOWNLOAD", $key);
				$this->tpl->setVariable("URL_MATERIAL_DOWNLOAD", $this->object->getMaterialsPathWeb().$value);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("material_download");
			$this->tpl->setVariable("TEXT_MATERIAL_DOWNLOAD", $this->lng->txt("material_download"));
			$this->tpl->parseCurrentBlock();
		}
		return;

		$this->tpl->addBlockFile("ORDERING_QUESTION", "ordering", "tpl.il_as_execute_ordering_question.html", true);
		$solutions = array();
		$postponed = "";
		if ($test_id)
		{
			$solutions =& $this->object->getSolutionValues($test_id);
		}
		if ($is_postponed)
		{
			$postponed = " (" . $this->lng->txt("postponed") . ")";
		}
		if (!empty($this->object->materials))
		{
			$i = 1;
			$this->tpl->setCurrentBlock("material_preview");
			foreach ($this->object->materials as $key => $value)
			{
				$this->tpl->setVariable("COUNTER", $i++);
				$this->tpl->setVariable("VALUE_MATERIAL_DOWNLOAD", $key);
				$this->tpl->setVariable("URL_MATERIAL_DOWNLOAD", $this->object->getMaterialsPathWeb().$value);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("material_download");
			$this->tpl->setVariable("TEXT_MATERIAL_DOWNLOAD", $this->lng->txt("material_download"));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("orderingQuestion");
		$keys = array_keys($this->object->answers);
		if ($this->object->shuffle)
		{
			$keys = $this->object->pcArrayShuffle($keys);
		}
		foreach ($keys as $key)
		{
			$value = $this->object->answers[$key];
			$this->tpl->setVariable("ORDERING_QUESTION_ANSWER_VALUE", $key);
			foreach ($solutions as $idx => $solution)
			{
				if ($solution->value1 == $key)
				{
					$this->tpl->setVariable("VALUE_ORDER", $solution->value2);
				}
			}
			if ($this->object->get_ordering_type() == OQ_PICTURES)
			{
				$imagepath = $this->object->getImagePathWeb() . $value->get_answertext();
				$this->tpl->setVariable("ORDERING_QUESTION_ANSWER_VALUE_IMAGE", $key);
				$this->tpl->setVariable("ORDERING_QUESTION_ANSWER_IMAGE", "<a href=\"$imagepath\" target=\"_blank\"><img src=\"$imagepath.thumb.jpg\" title=\"" . $this->lng->txt("qpl_display_fullsize_image") . "\" alt=\"" . $this->lng->txt("qpl_display_fullsize_image") . "\" border=\"\" /></a>");
			}
			else
			{
				$this->tpl->setVariable("ORDERING_QUESTION_ANSWER_VALUE_TEXT", $key);
				$this->tpl->setVariable("ORDERING_QUESTION_ANSWER_TEXT", $value->get_answertext());
			}
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("ordering");
		$this->tpl->setVariable("ORDERING_QUESTION_HEADLINE", $this->object->getTitle() . $postponed);
		$this->tpl->setVariable("ORDERING_QUESTION", $this->object->get_question());
		$this->tpl->parseCurrentBlock();
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
