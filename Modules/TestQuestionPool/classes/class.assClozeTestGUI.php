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

include_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";

/**
* Cloze test question GUI representation
*
* The assClozeTestGUI class encapsulates the GUI representation
* for cloze test questions.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @ingroup ModulesTestQuestionPool
*/
class assClozeTestGUI extends assQuestionGUI
{
	/**
	* A temporary variable to store gap indexes of ilCtrl commands in the getCommand method
	*
	*/
	var $gapIndex;

	/**
	* A temporary variable to store answer indexes of ilCtrl commands in the getCommand method
	*
	*/
	var $answerIndex;
	
	/**
	* assClozeTestGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the assClozeTestGUI object.
	*
	* @param integer $id The database id of a image map question object
	* @access public
	*/
	function assClozeTestGUI(
			$id = -1
	)
	{
		$this->assQuestionGUI();
		include_once "./Modules/TestQuestionPool/classes/class.assClozeTest.php";
		$this->object = new assClozeTest();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	function getCommand($cmd)
	{
		if (preg_match("/^(addGapText|addSelectGapText|addSuggestedSolution|removeSuggestedSolution)_(\d+)$/", $cmd, $matches))
		{
			$cmd = $matches[1];
			$this->gapIndex = $matches[2];
		}
		else if (preg_match("/^(delete)_(\d+)_(\d+)$/", $cmd, $matches))
		{
			$cmd = $matches[1];
			$this->gapIndex = $matches[2];
			$this->answerIndex = $matches[3];
		}
		return $cmd;
	}

	/**
	* Create editable gaps from the question text
	*/
	function createGaps()
	{
		$this->writePostData();
		$this->editQuestion();
	}

	/**
	* Change the type of a gap
	*/
	function changeGapType()
	{
		$this->writePostData();
		$this->editQuestion();
	}

	/**
	* Checks the obligatory fields from a POST in the edit form
	*/
	function checkInput()
	{
		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["clozetext"]))
		{
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	* Sets the gap types from the editing form
	*
	* Sets the gap types from the editing form
	*
	* @access private
	*/
	function setGapTypes()
	{
		foreach ($_POST as $key => $value)
		{
			// Set the cloze type of the gap
			if (preg_match("/clozetype_(\d+)/", $key, $matches))
			{
				$this->object->setGapType($matches[1], $value);
			}
		}
	}

	/**
	* Sets the shuffle state of gaps from the editing form
	*
	* Sets the shuffle state of gaps from the editing form
	*
	* @access private
	*/
	function setShuffleState()
	{
		foreach ($_POST as $key => $value)
		{
			// Set select gap shuffle state
			if (preg_match("/^shuffle_(\d+)$/", $key, $matches))
			{
				$this->object->setGapShuffle($matches[1], $value);
			}
		}
	}

	/**
	* Sets the answers for the gaps from the editing form
	*
	* Sets the answers for the gaps from the editing form
	*
	* @access private
	*/
	function setGapAnswers()
	{
		$this->object->clearGapAnswers();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/^(textgap|selectgap|numericgap)_(\d+)_(\d+)$/", $key, $matches))
			{
				// text gap answer
				$gap = $matches[2];
				$order = $matches[3];
				$this->object->addGapAnswer($gap, $order, $value);
			}
		}
	}

	/**
	* Sets the points for the gaps from the editing form
	*
	* Sets the points for the gaps from the editing form
	*
	* @access private
	*/
	function setGapPoints()
	{
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/points_(\d+)_(\d+)/", $key, $matches))
			{
				$gap = $matches[1];
				$order = $matches[2];
				$this->object->setGapAnswerPoints($gap, $order, $value);
			}
		}
	}

	/**
	* Sets the bounds for the gaps from the editing form
	*
	* Sets the bounds for the gaps from the editing form
	*
	* @access private
	*/
	function setGapBounds()
	{
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/numericgap_(\d+)_(\d+)_lower/", $key, $matches))
			{
				$gap = $matches[1];
				$order = $matches[2];
				$this->object->setGapAnswerLowerBound($gap, $order, $value);
			}
			if (preg_match("/numericgap_(\d+)_(\d+)_upper/", $key, $matches))
			{
				$gap = $matches[1];
				$order = $matches[2];
				$this->object->setGapAnswerUpperBound($gap, $order, $value);
			}
		}
	}

	/**
	* Adds a new answer text value to a text gap
	*
	* Adds a new answer text value to a text gap
	*
	* @access public
	*/
	function addGapText()
	{
		$this->writePostData();
		$this->object->addGapText($this->gapIndex);
		$this->editQuestion();
	}

	/**
	* Adds a new answer text value to a select gap
	*
	* Adds a new answer text value to a select gap
	*
	* @access public
	*/
	function addSelectGapText()
	{
		$this->writePostData();
		$this->object->addGapText($this->gapIndex);
		$this->editQuestion();
	}

	/**
	* Deletes answer text from a gap
	*
	* Deletes answer text from a gap
	*
	* @access public
	*/
	function delete()
	{
		$this->writePostData();
		$this->object->deleteAnswerText($this->gapIndex, $this->answerIndex);
		$this->editQuestion();
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
		$this->object->flushGaps();

		if (!$this->checkInput())
		{
			$result = 1;
		}

/*		if (($result) and ($_POST["cmd"]["add"]))
		{
			// You cannot create gaps before you enter the required data
			sendInfo($this->lng->txt("fill_out_all_required_fields_create_gaps"));
			$_POST["cmd"]["add"] = "";
		}
*/
		$this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
		$this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
		$this->object->setComment(ilUtil::stripSlashes($_POST["comment"]));
		$this->object->setTextgapRating($_POST["textgap_rating"]);
		$this->object->setFixedTextLength($_POST["fixedTextLength"]);
		include_once "./classes/class.ilObjAdvancedEditing.php";
		$cloze_text = ilUtil::stripSlashes($_POST["clozetext"], false, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment"));
		$this->object->setClozeText($cloze_text);
		// adding estimated working time
		$saved = $saved | $this->writeOtherPostData($result);
		$this->object->suggested_solutions = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/^solution_hint_(\d+)/", $key, $matches))
			{
				if ($value)
				{
					$this->object->setSuggestedSolution($value, $matches[1]);
				}
			}
		}

		if (strcmp($this->ctrl->getCmd(), "createGaps") == 0)
		{
			// on createGaps the gaps are created from the entered cloze text
			// but synchronized with existing gap form values if an answer
			// already exists for a gap
			$this->setGapTypes();
			$this->setShuffleState();
			$this->setGapPoints();
			$this->setGapBounds();
		}
		else
		{
			$this->setGapTypes();
			$this->setShuffleState();
			$this->setGapAnswers();
			$this->setGapPoints();
			$this->setGapBounds();
			$this->object->updateClozeTextFromGaps();
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
	* Creates an output of the edit form for the question
	*
	* Creates an output of the edit form for the question
	*
	* @access public
	*/
	function editQuestion()
	{
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$internallinks = array(
			"lm" => $this->lng->txt("obj_lm"),
			"st" => $this->lng->txt("obj_st"),
			"pg" => $this->lng->txt("obj_pg"),
			"glo" => $this->lng->txt("glossary_term")
		);
		//$this->tpl->setVariable("HEADER", $this->object->getTitle());
		$this->getQuestionTemplate();
		$this->tpl->addBlockFile("QUESTION_DATA", "question_data", "tpl.il_as_qpl_cloze_question.html", "Modules/TestQuestionPool");
		for ($i = 0; $i < $this->object->getGapCount(); $i++)
		{
			$gap = $this->object->getGap($i);
			if ($gap->getType() == CLOZE_TEXT)
			{
				$this->tpl->setCurrentBlock("textgap_value");
				foreach ($gap->getItemsRaw() as $item)
				{
					$this->tpl->setVariable("TEXT_VALUE", $this->lng->txt("value"));
					$this->tpl->setVariable("VALUE_TEXT_GAP", ilUtil::prepareFormOutput($item->getAnswertext()));
					$this->tpl->setVariable("VALUE_GAP_COUNTER", "$i" . "_" . $item->getOrder());
					$this->tpl->setVariable("VALUE_GAP", $i);
					$this->tpl->setVariable("VALUE_INDEX", $item->getOrder());
					$this->tpl->setVariable("VALUE_STATUS_COUNTER", $item->getOrder());
					$this->tpl->setVariable("VALUE_GAP", $i);
					$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
					$this->tpl->setVariable("VALUE_TEXT_GAP_POINTS", $item->getPoints());
					$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
					$this->tpl->parseCurrentBlock();
				}

				foreach ($internallinks as $key => $value)
				{
					$this->tpl->setCurrentBlock("textgap_internallink");
					$this->tpl->setVariable("TYPE_INTERNAL_LINK", $key);
					$this->tpl->setVariable("TEXT_INTERNAL_LINK", $value);
					$this->tpl->parseCurrentBlock();
				}

				$this->tpl->setCurrentBlock("textgap_suggested_solution");
				$this->tpl->setVariable("TEXT_SOLUTION_HINT", $this->lng->txt("solution_hint"));
				if (array_key_exists($i, $this->object->suggested_solutions))
				{
					$solution_array = $this->object->getSuggestedSolution($i);
					$href = assQuestion::_getInternalLinkHref($solution_array["internal_link"]);
					$this->tpl->setVariable("TEXT_VALUE_SOLUTION_HINT", " <a href=\"$href\" target=\"content\">" . $this->lng->txt("solution_hint"). "</a> ");
					$this->tpl->setVariable("BUTTON_REMOVE_SOLUTION", $this->lng->txt("remove"));
					$this->tpl->setVariable("VALUE_GAP_COUNTER_REMOVE", $i);
					$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("change"));
					$this->tpl->setVariable("VALUE_SOLUTION_HINT", $solution_array["internal_link"]);
				}
				else
				{
					$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("add"));
				}
				$this->tpl->setVariable("VALUE_GAP_COUNTER", $i);
				$this->tpl->parseCurrentBlock();
				$this->tpl->setCurrentBlock("textgap");
				$this->tpl->setVariable("ADD_TEXT_GAP", $this->lng->txt("add_gap"));
				$this->tpl->setVariable("VALUE_GAP_COUNTER", "$i");
				$this->tpl->parseCurrentBlock();
			}
			
			else if ($gap->getType() == CLOZE_NUMERIC)
			{
				$this->tpl->setCurrentBlock("numericgap_value");
				foreach ($gap->getItemsRaw() as $item)
				{
					$this->tpl->setVariable("TEXT_VALUE", $this->lng->txt("value"));
					$this->tpl->setVariable("TEXT_LOWER_LIMIT", $this->lng->txt("range_lower_limit"));
					$this->tpl->setVariable("TEXT_UPPER_LIMIT", $this->lng->txt("range_upper_limit"));
					$this->tpl->setVariable("VALUE_NUMERIC_GAP", ilUtil::prepareFormOutput($item->getAnswertext()));
					$this->tpl->setVariable("VALUE_GAP_COUNTER", "$i" . "_" . $item->getOrder());
					$this->tpl->setVariable("VALUE_GAP", $i);
					$this->tpl->setVariable("VALUE_INDEX", $item->getOrder());
					$this->tpl->setVariable("VALUE_STATUS_COUNTER", $item->getOrder());
					$this->tpl->setVariable("VALUE_GAP", $i);
					$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
					$this->tpl->setVariable("VALUE_NUMERIC_GAP_POINTS", $item->getPoints());
					$this->tpl->setVariable("VALUE_LOWER_LIMIT", $item->getLowerBound());
					$this->tpl->setVariable("VALUE_UPPER_LIMIT", $item->getUpperBound());
					$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
					$this->tpl->parseCurrentBlock();
				}

				foreach ($internallinks as $key => $value)
				{
					$this->tpl->setCurrentBlock("numericgap_internallink");
					$this->tpl->setVariable("TYPE_INTERNAL_LINK", $key);
					$this->tpl->setVariable("TEXT_INTERNAL_LINK", $value);
					$this->tpl->parseCurrentBlock();
				}

				$this->tpl->setCurrentBlock("numericgap_suggested_solution");
				$this->tpl->setVariable("TEXT_SOLUTION_HINT", $this->lng->txt("solution_hint"));
				if (array_key_exists($i, $this->object->suggested_solutions))
				{
					$solution_array = $this->object->getSuggestedSolution($i);
					$href = assQuestion::_getInternalLinkHref($solution_array["internal_link"]);
					$this->tpl->setVariable("TEXT_VALUE_SOLUTION_HINT", " <a href=\"$href\" target=\"content\">" . $this->lng->txt("solution_hint"). "</a> ");
					$this->tpl->setVariable("BUTTON_REMOVE_SOLUTION", $this->lng->txt("remove"));
					$this->tpl->setVariable("VALUE_GAP_COUNTER_REMOVE", $i);
					$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("change"));
					$this->tpl->setVariable("VALUE_SOLUTION_HINT", $solution_array["internal_link"]);
				}
				else
				{
					$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("add"));
				}
				$this->tpl->setVariable("VALUE_GAP_COUNTER", $i);
				$this->tpl->parseCurrentBlock();
				$this->tpl->setCurrentBlock("numericgap");
				$this->tpl->parseCurrentBlock();
			}
			
			else if ($gap->getType() == CLOZE_SELECT)
			{
				$this->tpl->setCurrentBlock("selectgap_value");
				foreach ($gap->getItemsRaw() as $item)
				{
					$this->tpl->setVariable("TEXT_VALUE", $this->lng->txt("value"));
					$this->tpl->setVariable("VALUE_SELECT_GAP", ilUtil::prepareFormOutput($item->getAnswertext()));
					$this->tpl->setVariable("VALUE_GAP_COUNTER", "$i" . "_" . $item->getOrder());
					$this->tpl->setVariable("VALUE_GAP", $i);
					$this->tpl->setVariable("VALUE_INDEX", $item->getOrder());
					$this->tpl->setVariable("VALUE_STATUS_COUNTER", $item->getOrder());
					$this->tpl->setVariable("VALUE_GAP", $i);
					$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
					$this->tpl->setVariable("VALUE_SELECT_GAP_POINTS", $item->getPoints());
					$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
					$this->tpl->parseCurrentBlock();
				}

				foreach ($internallinks as $key => $value)
				{
					$this->tpl->setCurrentBlock("selectgap_internallink");
					$this->tpl->setVariable("TYPE_INTERNAL_LINK", $key);
					$this->tpl->setVariable("TEXT_INTERNAL_LINK", $value);
					$this->tpl->parseCurrentBlock();
				}

				$this->tpl->setCurrentBlock("selectgap_suggested_solution");
				$this->tpl->setVariable("TEXT_SOLUTION_HINT", $this->lng->txt("solution_hint"));
				if (array_key_exists($i, $this->object->suggested_solutions))
				{
					$solution_array = $this->object->getSuggestedSolution($i);
					$href = assQuestion::_getInternalLinkHref($solution_array["internal_link"]);
					$this->tpl->setVariable("TEXT_VALUE_SOLUTION_HINT", " <a href=\"$href\" target=\"content\">" . $this->lng->txt("solution_hint"). "</a> ");
					$this->tpl->setVariable("BUTTON_REMOVE_SOLUTION", $this->lng->txt("remove"));
					$this->tpl->setVariable("VALUE_GAP_COUNTER_REMOVE", $i);
					$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("change"));
					$this->tpl->setVariable("VALUE_SOLUTION_HINT", $solution_array["internal_link"]);
				}
				else
				{
					$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("add"));
				}
				$this->tpl->setVariable("VALUE_GAP_COUNTER", $i);
				$this->tpl->parseCurrentBlock();
				$this->tpl->setCurrentBlock("selectgap");
				$this->tpl->setVariable("ADD_SELECT_GAP", $this->lng->txt("add_gap"));
				$this->tpl->setVariable("TEXT_SHUFFLE_ANSWERS", $this->lng->txt("shuffle_answers"));
				$this->tpl->setVariable("VALUE_GAP_COUNTER", "$i");
				if ($gap->getShuffle())
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
			$name = $this->lng->txt("gap") . " " . ($i+1);
			$this->tpl->setVariable("TEXT_GAP_NAME", $name);
			$this->tpl->setVariable("TEXT_TYPE", $this->lng->txt("type"));
			$this->tpl->setVariable("TEXT_CHANGE", $this->lng->txt("change"));
			switch ($gap->getType())
			{
				case CLOZE_TEXT:
					$this->tpl->setVariable("SELECTED_TEXT_GAP", " selected=\"selected\"");
					break;
				case CLOZE_SELECT:
					$this->tpl->setVariable("SELECTED_SELECT_GAP", " selected=\"selected\"");
					break;
				case CLOZE_NUMERIC:
					$this->tpl->setVariable("SELECTED_NUMERIC_GAP", " selected=\"selected\"");
					break;
			}
			$this->tpl->setVariable("TEXT_TEXT_GAP", $this->lng->txt("text_gap"));
			$this->tpl->setVariable("TEXT_SELECT_GAP", $this->lng->txt("select_gap"));
			$this->tpl->setVariable("TEXT_NUMERIC_GAP", $this->lng->txt("numeric_gap"));
			$this->tpl->setVariable("VALUE_GAP_COUNTER", $i);
			$this->tpl->parseCurrentBlock();
		}

		// call to other question data i.e. estimated working time block
		$this->outOtherQuestionData();

		// out automatical selection of the best text input field (javascript)
		$this->tpl->setCurrentBlock("HeadContent");
		$javascript = "<script type=\"text/javascript\">function initialSelect() {\n%s\n}</script>";
		if (preg_match("/addGapText_(\d+)/", $this->ctrl->getCmd(), $matches))
		{
			$this->tpl->setVariable("CONTENT_BLOCK", sprintf($javascript, "document.frm_cloze_test.textgap_" . $matches[1] . "_" .(is_object($this->object->getGap($matches[1])) ? $this->object->getGap($matches[1])->getItemCount() - 1 : 0) .".focus(); document.frm_cloze_test.textgap_" . $matches[1] . "_" . (is_object($this->object->getGap($matches[1])) ? $this->object->getGap($matches[1])->getItemCount() - 1 : 0) .".scrollIntoView(\"true\");"));
		}
		else if (preg_match("/addSelectGapText_(\d+)/", $this->ctrl->getCmd(), $matches))
		{
			$this->tpl->setVariable("CONTENT_BLOCK", sprintf($javascript, "document.frm_cloze_test.selectgap_" . $matches[1] . "_" .(is_object($this->object->getGap($matches[1])) ? $this->object->getGap($matches[1])->getItemCount() - 1 : 0) .".focus(); document.frm_cloze_test.selectgap_" . $matches[1] . "_" . (is_object($this->object->getGap($matches[1])) ? $this->object->getGap($matches[1])->getItemCount() - 1 : 0) .".scrollIntoView(\"true\");"));
		}
		else
		{
			$this->tpl->setVariable("CONTENT_BLOCK", sprintf($javascript, "document.frm_cloze_test.title.focus();"));
		}
		$this->tpl->parseCurrentBlock();
		
		// Add textgap rating options
		$textgap_options = array(
			array("ci", $this->lng->txt("cloze_textgap_case_insensitive")),
			array("cs", $this->lng->txt("cloze_textgap_case_sensitive")),
			array("l1", sprintf($this->lng->txt("cloze_textgap_levenshtein_of"), "1")),
			array("l2", sprintf($this->lng->txt("cloze_textgap_levenshtein_of"), "2")),
			array("l3", sprintf($this->lng->txt("cloze_textgap_levenshtein_of"), "3")),
			array("l4", sprintf($this->lng->txt("cloze_textgap_levenshtein_of"), "4")),
			array("l5", sprintf($this->lng->txt("cloze_textgap_levenshtein_of"), "5"))
		);
		$textgap_rating = $this->object->getTextgapRating();
		foreach ($textgap_options as $textgap_option)
		{
			$this->tpl->setCurrentBlock("textgap_rating");
			$this->tpl->setVariable("TEXTGAP_VALUE", $textgap_option[0]);
			$this->tpl->setVariable("TEXTGAP_TEXT", $textgap_option[1]);
			if (strcmp($textgap_rating, $textgap_option[0]) == 0)
			{
				$this->tpl->setVariable("SELECTED_TEXTGAP_VALUE", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setCurrentBlock("question_data");
		$this->tpl->setVariable("FIXED_TEXTLENGTH", $this->lng->txt("cloze_fixed_textlength"));
		$this->tpl->setVariable("FIXED_TEXTLENGTH_DESCRIPTION", $this->lng->txt("cloze_fixed_textlength_description"));
		if ($this->object->getFixedTextLength())
		{
			$this->tpl->setVariable("VALUE_FIXED_TEXTLENGTH", " value=\"" . ilUtil::prepareFormOutput($this->object->getFixedTextLength()) . "\"");
		}
		$this->tpl->setVariable("VALUE_CLOZE_TITLE", ilUtil::prepareFormOutput($this->object->getTitle()));
		$this->tpl->setVariable("VALUE_CLOZE_COMMENT", ilUtil::prepareFormOutput($this->object->getComment()));
		$this->tpl->setVariable("VALUE_CLOZE_AUTHOR", ilUtil::prepareFormOutput($this->object->getAuthor()));
		$cloze_text = $this->object->getClozeText();
		$this->tpl->setVariable("VALUE_CLOZE_TEXT", $this->object->prepareTextareaOutput($cloze_text));
		$this->tpl->setVariable("TEXT_CREATE_GAPS", $this->lng->txt("create_gaps"));

		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
		$this->tpl->setVariable("TEXT_COMMENT", $this->lng->txt("description"));
		$this->tpl->setVariable("TEXT_CLOZE_TEXT", $this->lng->txt("cloze_text"));
		$this->tpl->setVariable("TEXT_CLOSE_HINT", ilUtil::prepareFormOutput($this->lng->txt("close_text_hint")));
		$this->tpl->setVariable("TEXTGAP_RATING", $this->lng->txt("cloze_textgap_rating"));
		$this->tpl->setVariable("TEXT_GAP_DEFINITION", $this->lng->txt("gap_definition"));
		$this->tpl->setVariable("SAVE",$this->lng->txt("save"));
		$this->tpl->setVariable("SAVE_EDIT", $this->lng->txt("save_edit"));
		$this->tpl->setVariable("CANCEL",$this->lng->txt("cancel"));
		$this->ctrl->setParameter($this, "sel_question_types", $this->object->getQuestionType());
		$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt($this->object->getQuestionType()));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->parseCurrentBlock();

		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->addPlugin("latex");
		$rte->addButton("latex");
		include_once "./classes/class.ilObject.php";
		$obj_id = $_GET["q_id"];
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, $obj_type, "assessment");

		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("BODY_ATTRIBUTES", " onload=\"initialSelect();\""); 
		$this->tpl->parseCurrentBlock();
	}

	/**
	* Creates an output of the question for a test
	*
	* Creates an output of the question for a test
	*
	* @param string $formaction The form action for the test output
	* @param integer $active_id The active id of the current user from the tst_active database table
	* @param integer $pass The test pass of the current user
	* @param boolean $is_postponed The information if the question is a postponed question or not
	* @param boolean $use_post_solutions Fills the question output with answers from the previous post if TRUE, otherwise with the user results from the database
	* @access public
	*/
	function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		$test_output = $this->getTestOutput($active_id, $pass, $is_postponed, $use_post_solutions); 
		$this->tpl->setVariable("QUESTION_OUTPUT", $test_output);
		$this->tpl->setVariable("FORMACTION", $formaction);
	}

	/**
	* Creates a preview output of the question
	*
	* Creates a preview output of the question
	*
	* @return string HTML code which contains the preview output of the question
	* @access public
	*/
	function getPreview()
	{
		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_cloze_question_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		$output = $this->object->getClozeText();
		foreach ($this->object->getGaps() as $gap_index => $gap)
		{
			switch ($gap->getType())
			{
				case CLOZE_TEXT:
					$gaptemplate = new ilTemplate("tpl.il_as_qpl_cloze_question_gap_text.html", TRUE, TRUE, "Modules/TestQuestionPool");
					$gaptemplate->setVariable("TEXT_GAP_SIZE", $this->object->getFixedTextLength() ? $this->object->getFixedTextLength() : $gap->getMaxWidth());
					$gaptemplate->setVariable("GAP_COUNTER", $gap_index);
					$output = preg_replace("/\[gap\].*?\[\/gap\]/", $gaptemplate->get(), $output, 1);
					break;
				case CLOZE_SELECT:
					$gaptemplate = new ilTemplate("tpl.il_as_qpl_cloze_question_gap_select.html", TRUE, TRUE, "Modules/TestQuestionPool");
					foreach ($gap->getItemsRaw() as $item)
					{
						$gaptemplate->setCurrentBlock("select_gap_option");
						$gaptemplate->setVariable("SELECT_GAP_VALUE", $item->getOrder());
						$gaptemplate->setVariable("SELECT_GAP_TEXT", ilUtil::prepareFormOutput($item->getAnswerText()));
						$gaptemplate->parseCurrentBlock();
					}
					$gaptemplate->setVariable("PLEASE_SELECT", $this->lng->txt("please_select"));
					$gaptemplate->setVariable("GAP_COUNTER", $gap_index);
					$output = preg_replace("/\[gap\].*?\[\/gap\]/", $gaptemplate->get(), $output, 1);
					break;
				case CLOZE_NUMERIC:
					$gaptemplate = new ilTemplate("tpl.il_as_qpl_cloze_question_gap_numeric.html", TRUE, TRUE, "Modules/TestQuestionPool");
					$gaptemplate->setVariable("TEXT_GAP_SIZE", $this->object->getFixedTextLength() ? $this->object->getFixedTextLength() : $gap->getMaxWidth());
					$gaptemplate->setVariable("GAP_COUNTER", $gap_index);
					$output = preg_replace("/\[gap\].*?\[\/gap\]/", $gaptemplate->get(), $output, 1);
					break;
			}
		}
		$template->setVariable("QUESTIONTEXT", $output);
/*		
<!-- BEGIN cloze_part --><!-- BEGIN cloze_text -->{CLOZE_TEXT}<!-- END cloze_text --><!-- BEGIN text_gap --><input type="text" size="{TEXT_GAP_SIZE}" name="gap_{GAP_COUNTER}"{VALUE_GAP} /><!-- END text_gap --><!-- BEGIN select_gap --><select name="gap_{GAP_COUNTER}"><option value="-1">{PLEASE_SELECT}</option><!-- BEGIN select_gap_option --><option value="{SELECT_GAP_VALUE}"{SELECT_GAP_SELECTED}>{SELECT_GAP_TEXT}</option><!-- END select_gap_option --></select><!-- END select_gap --><!-- BEGIN feedback --><span class="feedback">{FEEDBACK}</span><!-- END feedback --><!-- END cloze_part -->
*/
		$questionoutput = $template->get();
		$questionoutput = preg_replace("/\<div[^>]*?>(.*)\<\/div>/is", "\\1", $questionoutput);
		return $questionoutput;
	}

	/**
	* Creates a solution output of the question
	*
	* Creates a solution output of the question
	*
	* @param integer $active_id The active id of the current user from the tst_active database table
	* @param integer $pass The test pass of the current user
	* @param boolean $graphicalOutput If TRUE, additional graphics (checkmark, cross) are shown to indicate wrong or right answers
	* @param boolean $result_output If TRUE, the resulting points are shown for every answer
	* @return string HTML code which contains the solution output of the question
	* @access public
	*/
	function getSolutionOutput($active_id, $pass = NULL, $graphicalOutput = FALSE, $result_output = FALSE, $show_question_only = TRUE)
	{
		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = array();
		if ($active_id)
		{
			// get the solutions of a user
			$user_solution =& $this->object->getSolutionValues($active_id, $pass);
			if (!is_array($user_solution)) 
			{
				$user_solution = array();
			}
		}
		else
		{
			// create the "best" solutions
			for ($i = 0; $i < $this->object->getGapCount(); $i++)
			{
				$gap = $this->object->getGap($i);
				if (is_object($gap))
				{
					switch ($gap->getType())
					{
						case CLOZE_SELECT:
							$maxpoints = 0;
							$foundindex = -1;
							foreach ($gap->getItems() as $$answer)
							{
								if ($answer->getPoints() > $maxpoints)
								{
									$maxpoints = $answer->getPoints();
									$foundindex = $answer->getOrder();
								}
							}
							array_push($user_solution, array("value1" => $i, "value2" => $foundindex));
							break;
						case CLOZE_TEXT:
							$best_solutions = array();
							foreach ($gap->getItems() as $answer)
							{
								if (is_array($best_solutions[$answer->getPoints()]))
								{
									array_push($best_solutions[$answer->getPoints()], "&quot;".$answer->getAnswertext()."&quot;");
								}
								else
								{
									$best_solutions[$answer->getPoints()] = array();
									array_push($best_solutions[$answer->getPoints()], "&quot;".$answer->getAnswertext()."&quot;");
								}
							}
							krsort($best_solutions, SORT_NUMERIC);
							reset($best_solutions);
							$found = current($best_solutions);
							array_push($user_solution, array("value1" => $i, "value2" => join(" " . $this->lng->txt("or") . " ", $found)));
							break;
						case CLOZE_NUMERIC:
							$maxpoints = 0;
							$foundvalue = "";
							foreach ($gap->getItems() as $answer)
							{
								if ($answer->getPoints() > $maxpoints)
								{
									$maxpoints = $answer->getPoints();
									$foundvalue = $answer->getAnswertext();
								}
							}
							array_push($user_solution, array("value1" => $i, "value2" => $foundvalue));
							break;
					}
				}
			}
		}

		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_cloze_question_output_solution.html", TRUE, TRUE, "Modules/TestQuestionPool");
		$output = $this->object->getClozeText();
		foreach ($this->object->getGaps() as $gap_index => $gap)
		{
			$gaptemplate = new ilTemplate("tpl.il_as_qpl_cloze_question_output_solution_gap.html", TRUE, TRUE, "Modules/TestQuestionPool");
			$found = array();
			foreach ($user_solution as $solutionarray)
			{
				if ($solutionarray["value1"] == $gap_index) $found = $solutionarray;
			}

			if ($active_id)
			{
				if ($graphicalOutput)
				{
					// output of ok/not ok icons for user entered solutions
					$check = $this->object->testGapSolution($found["value2"], $gap_index);
					if ($check["best"])
					{
						$gaptemplate->setCurrentBlock("icon_ok");
						$gaptemplate->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.gif"));
						$gaptemplate->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
						$gaptemplate->parseCurrentBlock();
					}
					else
					{
						$gaptemplate->setCurrentBlock("icon_not_ok");
						if ($check["positive"])
						{
							$gaptemplate->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_mostly_ok.gif"));
							$gaptemplate->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_not_correct_but_positive"));
						}
						else
						{
							$gaptemplate->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.gif"));
							$gaptemplate->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
						}
						$gaptemplate->parseCurrentBlock();
					}
				}
			}
			if ($result_output)
			{
				$points = $this->object->getMaximumGapPoints($found["value1"]);
				$resulttext = ($points == 1) ? "(%s " . $this->lng->txt("point") . ")" : "(%s " . $this->lng->txt("points") . ")"; 
				$gaptemplate->setCurrentBlock("result_output");
				$gaptemplate->setVariable("RESULT_OUTPUT", sprintf($resulttext, $points));
				$gaptemplate->parseCurrentBlock();
			}
			switch ($gap->getType())
			{
				case CLOZE_TEXT:
					$solutiontext = "";
					if ((count($found) == 0) || (strlen(trim($found["value2"])) == 0))
					{
						for ($chars = 0; $chars < $gap->getMaxWidth(); $chars++)
						{
							$solutiontext .= "&nbsp;";
						}
					}
					else
					{
						$solutiontext = $found["value2"];
					}
					$gaptemplate->setVariable("SOLUTION", $solutiontext);
					$output = preg_replace("/\[gap\].*?\[\/gap\]/", $gaptemplate->get(), $output, 1);
					break;
				case CLOZE_SELECT:
					$solutiontext = "";
					if ((count($found) == 0) || (strlen(trim($found["value2"])) == 0))
					{
						for ($chars = 0; $chars < $gap->getMaxWidth(); $chars++)
						{
							$solutiontext .= "&nbsp;";
						}
					}
					else
					{
						$solutiontext = $gap->getItem($found["value2"])->getAnswertext();
					}
					$gaptemplate->setVariable("SOLUTION", $solutiontext);
					$output = preg_replace("/\[gap\].*?\[\/gap\]/", $gaptemplate->get(), $output, 1);
					break;
				case CLOZE_NUMERIC:
					$solutiontext = "";
					if ((count($found) == 0) || (strlen(trim($found["value2"])) == 0))
					{
						for ($chars = 0; $chars < $gap->getMaxWidth(); $chars++)
						{
							$solutiontext .= "&nbsp;";
						}
					}
					else
					{
						$solutiontext = $found["value2"];
					}
					$gaptemplate->setVariable("SOLUTION", $solutiontext);
					$output = preg_replace("/\[gap\].*?\[\/gap\]/", $gaptemplate->get(), $output, 1);
					break;
			}
		}
		$template->setVariable("QUESTIONTEXT", $output);

		// generate the question output
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html",TRUE, TRUE, "Modules/TestQuestionPool");
		$questionoutput = $template->get();
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

		$solutionoutput = $solutiontemplate->get(); 
		if (!$show_question_only)
		{
			// get page object output
			$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id);
			$pageoutput = preg_replace("/\<div class\=\"ilc_PageTitle\">.*?\<\/div>/ims", "", $pageoutput);
			$solutionoutput = preg_replace("/(\<div( xmlns:xhtml\=\"http:\/\/www.w3.org\/1999\/xhtml\"){0,1} class\=\"ilc_Question\">\<\/div>)/ims", $solutionoutput, $pageoutput);
		}
		return $solutionoutput;
	}

	function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		// get page object output
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id);

		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = array();
		if ($active_id)
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			$user_solution =& $this->object->getSolutionValues($active_id, $pass);
			if (!is_array($user_solution)) 
			{
				$user_solution = array();
			}
		}
		
		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_cloze_question_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		$output = $this->object->getClozeText();
		foreach ($this->object->getGaps() as $gap_index => $gap)
		{
			switch ($gap->getType())
			{
				case CLOZE_TEXT:
					$gaptemplate = new ilTemplate("tpl.il_as_qpl_cloze_question_gap_text.html", TRUE, TRUE, "Modules/TestQuestionPool");
					$gaptemplate->setVariable("TEXT_GAP_SIZE", $this->object->getFixedTextLength() ? $this->object->getFixedTextLength() : $gap->getMaxWidth());
					$gaptemplate->setVariable("GAP_COUNTER", $gap_index);
					foreach ($user_solution as $solution)
					{
						if (strcmp($solution["value1"], $gap_index) == 0)
						{
							$gaptemplate->setVariable("VALUE_GAP", " value=\"" . ilUtil::prepareFormOutput($solution["value2"]) . "\"");
						}
					}
					$output = preg_replace("/\[gap\].*?\[\/gap\]/", $gaptemplate->get(), $output, 1);
					break;
				case CLOZE_SELECT:
					$gaptemplate = new ilTemplate("tpl.il_as_qpl_cloze_question_gap_select.html", TRUE, TRUE, "Modules/TestQuestionPool");
					foreach ($gap->getItemsRaw() as $item)
					{
						$gaptemplate->setCurrentBlock("select_gap_option");
						$gaptemplate->setVariable("SELECT_GAP_VALUE", $item->getOrder());
						$gaptemplate->setVariable("SELECT_GAP_TEXT", ilUtil::prepareFormOutput($item->getAnswerText()));
						foreach ($user_solution as $solution)
						{
							if (strcmp($solution["value1"], $gap_index) == 0)
							{
								if (strcmp($solution["value2"], $item->getOrder()) == 0)
								{
									$gaptemplate->setVariable("SELECT_GAP_SELECTED", " selected=\"selected\"");
								}
							}
						}
						$gaptemplate->parseCurrentBlock();
					}
					$gaptemplate->setVariable("PLEASE_SELECT", $this->lng->txt("please_select"));
					$gaptemplate->setVariable("GAP_COUNTER", $gap_index);
					$output = preg_replace("/\[gap\].*?\[\/gap\]/", $gaptemplate->get(), $output, 1);
					break;
				case CLOZE_NUMERIC:
					$gaptemplate = new ilTemplate("tpl.il_as_qpl_cloze_question_gap_numeric.html", TRUE, TRUE, "Modules/TestQuestionPool");
					$gaptemplate->setVariable("TEXT_GAP_SIZE", $gap->$this->object->getFixedTextLength() ? $this->object->getFixedTextLength() : $gap->getMaxWidth());
					$gaptemplate->setVariable("GAP_COUNTER", $gap_index);
					foreach ($user_solution as $solution)
					{
						if (strcmp($solution["value1"], $gap_index) == 0)
						{
							$gaptemplate->setVariable("VALUE_GAP", " value=\"" . ilUtil::prepareFormOutput($solution["value2"]) . "\"");
						}
					}
					$output = preg_replace("/\[gap\].*?\[\/gap\]/", $gaptemplate->get(), $output, 1);
					break;
			}
		}
		$template->setVariable("QUESTIONTEXT", $output);
		$questionoutput = $template->get();
		$questionoutput = preg_replace("/(\<div( xmlns:xhtml\=\"http:\/\/www.w3.org\/1999\/xhtml\"){0,1} class\=\"ilc_Question\">\<\/div>)/ims", $questionoutput, $pageoutput);
		return $questionoutput;
	}

	function addSuggestedSolution()
	{
		$addForGap = -1;
		if (array_key_exists("cmd", $_POST))
		{
			foreach ($_POST["cmd"] as $key => $value)
			{
				if (preg_match("/addSuggestedSolution_(\d+)/", $key, $matches))
				{
					$addForGap = $matches[1];
				}
			}
		}
		if ($addForGap > -1)
		{
			if ($this->writePostData())
			{
				ilUtil::sendInfo($this->getErrorMessage());
				$this->editQuestion();
				return;
			}
			if (!$this->checkInput())
			{
				ilUtil::sendInfo($this->lng->txt("fill_out_all_required_fields_add_answer"));
				$this->editQuestion();
				return;
			}
			$_POST["internalLinkType"] = $_POST["internalLinkType_$addForGap"];
			$_SESSION["subquestion_index"] = $addForGap;
		}
		$this->object->saveToDb();
		$_GET["q_id"] = $this->object->getId();
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
		$this->getQuestionTemplate();
		parent::addSuggestedSolution();
	}

	function removeSuggestedSolution()
	{
		$removeFromGap = -1;
		foreach ($_POST["cmd"] as $key => $value)
		{
			if (preg_match("/removeSuggestedSolution_(\d+)/", $key, $matches))
			{
				$removeFromGap = $matches[1];
			}
		}
		if ($removeFromGap > -1)
		{
			unset($this->object->suggested_solutions[$removeFromGap]);
		}
		$this->object->saveToDb();
		$this->editQuestion();
	}

	/**
	* Saves the feedback for a single choice question
	*
	* Saves the feedback for a single choice question
	*
	* @access public
	*/
	function saveFeedback()
	{
		include_once "./classes/class.ilObjAdvancedEditing.php";
		$this->object->saveFeedbackGeneric(0, ilUtil::stripSlashes($_POST["feedback_incomplete"], false, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment")));
		$this->object->saveFeedbackGeneric(1, ilUtil::stripSlashes($_POST["feedback_complete"], false, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment")));
		$this->object->cleanupMediaObjectUsage();
		$this->feedback();
	}

	/**
	* Creates the output of the feedback page for a single choice question
	*
	* Creates the output of the feedback page for a single choice question
	*
	* @access public
	*/
	function feedback()
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "feedback", "tpl.il_as_qpl_cloze_question_feedback.html", "Modules/TestQuestionPool");
		$this->tpl->setVariable("FEEDBACK_TEXT", $this->lng->txt("feedback"));
		$this->tpl->setVariable("FEEDBACK_COMPLETE", $this->lng->txt("feedback_complete_solution"));
		$this->tpl->setVariable("VALUE_FEEDBACK_COMPLETE", $this->object->prepareTextareaOutput($this->object->getFeedbackGeneric(1)), FALSE);
		$this->tpl->setVariable("FEEDBACK_INCOMPLETE", $this->lng->txt("feedback_incomplete_solution"));
		$this->tpl->setVariable("VALUE_FEEDBACK_INCOMPLETE", $this->object->prepareTextareaOutput($this->object->getFeedbackGeneric(0)), FALSE);
		$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->addPlugin("latex");
		$rte->addButton("latex");
		include_once "./classes/class.ilObject.php";
		$obj_id = $_GET["q_id"];
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, $obj_type, "assessment");
	}

	/**
	* Sets the ILIAS tabs for this question type
	*
	* Sets the ILIAS tabs for this question type
	*
	* @access public
	*/
	function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;
		
		$this->ctrl->setParameterByClass("ilpageobjectgui", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$q_type = $this->object->getQuestionType();

		if (strlen($q_type))
		{
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
		}

		if ($_GET["q_id"])
		{
			if ($rbacsystem->checkAccess('write', $this->ref_id))
			{
				// edit page
				$ilTabs->addTarget("edit_content",
					$this->ctrl->getLinkTargetByClass("ilPageObjectGUI", "view"),
					array("view", "insert", "exec_pg"),
					"", "", $force_active);
			}
	
			// edit page
			$ilTabs->addTarget("preview",
				$this->ctrl->getLinkTargetByClass("ilPageObjectGUI", "preview"),
				array("preview"),
				"ilPageObjectGUI", "", $force_active);
		}

		$force_active = false;
		$commands = $_POST["cmd"];
		if (is_array($commands))
		{
			foreach ($commands as $key => $value)
			{
				if (preg_match("/^delete_.*/", $key, $matches) || 
					preg_match("/^addSelectGapText_.*/", $key, $matches) ||
					preg_match("/^addGapText_.*/", $key, $matches) ||
					preg_match("/^upload_.*/", $key, $matches) ||
					preg_match("/^addSuggestedSolution_.*/", $key, $matches) ||
					preg_match("/^removeSuggestedSolution_.*/", $key, $matches)
					)
				{
					$force_active = true;
				}
			}
		}
		if ($rbacsystem->checkAccess('write', $this->ref_id))
		{
			$url = "";
			if ($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			// edit question properties
			$ilTabs->addTarget("edit_properties",
				$url,
				array("editQuestion", "save", "cancel", "addSuggestedSolution",
					 "cancelExplorer", "linkChilds", "removeSuggestedSolution",
					 "createGaps", "saveEdit", "changeGapType"),
				$classname, "", $force_active);
		}

		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("feedback",
				$this->ctrl->getLinkTargetByClass($classname, "feedback"),
				array("feedback", "saveFeedback"),
				$classname, "");
		}
		
		// Assessment of questions sub menu entry
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, "");
		}
		
		if (($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0))
		{
			$ref_id = $_GET["calling_test"];
			if (strlen($ref_id) == 0) $ref_id = $_GET["test_ref_id"];
			$ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id");
		}
		else
		{
			$ilTabs->setBackTarget($this->lng->txt("qpl"), $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions"));
		}
	}
}
?>