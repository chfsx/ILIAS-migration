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
include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
* Class for horizontal ordering questions
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @ingroup ModulesTestQuestionPool
*/
class assOrderingHorizontal extends assQuestion
{
	protected $ordertext;
	protected $textsize;
	protected $separator = "::";
	
	/**
	* assOrderingHorizontal constructor
	*
	* The constructor takes possible arguments an creates an instance of the assOrderingHorizontal object.
	*
	* @param string $title A title string to describe the question
	* @param string $comment A comment string to describe the question
	* @param string $author A string containing the name of the questions author
	* @param integer $owner A numerical ID to identify the owner/creator
	* @param string $question The question string of the single choice question
	* @see assQuestion:__construct()
	*/
	function __construct(
		$title = "",
		$comment = "",
		$author = "",
		$owner = -1,
		$question = ""
	)
	{
		parent::__construct($title, $comment, $author, $owner, $question);
		$this->ordertext = "";
	}
	
	/**
	* Returns true, if a single choice question is complete for use
	*
	* @return boolean True, if the single choice question is complete for use, otherwise false
	*/
	public function isComplete()
	{
		if (($this->title) and ($this->author) and ($this->question) and ($this->getMaximumPoints() > 0))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Saves a assOrderingHorizontal object to a database
	*
	*/
	public function saveToDb($original_id = "")
	{
		global $ilDB, $ilLog;

		$complete = "0";
		if ($this->isComplete())
		{
			$complete = "1";
		}
		$estw_time = $this->getEstimatedWorkingTime();
		$estw_time = sprintf("%02d:%02d:%02d", $estw_time['h'], $estw_time['m'], $estw_time['s']);

		include_once("./Services/RTE/classes/class.ilRTE.php");
		if ($this->id == -1)
		{
			// Neuen Datensatz schreiben
			$now = getdate();
			$created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
			
			$statement = $ilDB->prepareManip("INSERT INTO qpl_questions (question_id, question_type_fi, obj_fi, title, comment, author, owner, question_text, points, working_time, complete, created, original_id, TIMESTAMP) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)", 
				array("integer", "integer", "text", "text", "text", "integer", "text", "float", "time", "text", "timestamp")
			);
			$data = array(
				$this->getQuestionTypeID(), 
				$this->getObjId(), 
				$this->getTitle(), 
				$this->getComment(), 
				$this->getAuthor(), 
				$this->getOwner(), 
				ilRTE::_replaceMediaObjectImageSrc($this->question, 0), 
				$this->getMaximumPoints(),
				$estw_time,
				$complete,
				$created,
				($original_id) ? $original_id : NULL
			);
			$affectedRows = $ilDB->execute($statement, $data);
			$this->setId($ilDB->getLastInsertId());
			// create page object of question
			$this->createPageObject();

			if ($this->getTestId() > 0)
			{
				$this->insertIntoTest($this->getTestId());
			}
		}
		else
		{
			// Vorhandenen Datensatz aktualisieren
			$statement = $ilDB->prepareManip("UPDATE qpl_questions SET obj_fi = ?, title = ?, comment = ?, author = ?, question_text = ?, points = ?, working_time=?, complete = ? WHERE question_id = ?", 
				array("integer", "text", "text", "text", "text", "float", "time", "text", "integer")
			);
			$data = array(
				$this->getObjId(), 
				$this->getTitle(), 
				$this->getComment(), 
				$this->getAuthor(), 
				ilRTE::_replaceMediaObjectImageSrc($this->question, 0), 
				$this->getMaximumPoints(),
				$estw_time,
				$complete,
				$this->getId()
			);
			$affectedRows = $ilDB->execute($statement, $data);
		}
		// save additional data
	
		$statement = $ilDB->prepareManip("DELETE FROM " . $this->getAdditionalTableName() . " WHERE question_fi = ?", 
			array("integer")
		);
		$data = array($this->getId());
		$affectedRows = $ilDB->execute($statement, $data);
		$statement = $ilDB->prepareManip("INSERT INTO " . $this->getAdditionalTableName() . " (question_fi, ordertext, textsize) VALUES (?, ?, ?)", 
			array("integer", "text", "float")
		);
		$data = array(
			$this->getId(),
			$this->getOrderText(),
			($this->getTextSize() < 10) ? NULL : $this->getTextSize()
		);
		$affectedRows = $ilDB->execute($statement, $data);
		parent::saveToDb();
	}

	/**
	* Loads a assOrderingHorizontal object from a database
	*
	* @param object $db A pear DB object
	* @param integer $question_id A unique key which defines the multiple choice test in the database
	*/
	public function loadFromDb($question_id)
	{
		global $ilDB;
		$statement = $ilDB->prepare("SELECT qpl_questions.*, " . $this->getAdditionalTableName() . ".* FROM qpl_questions, " . $this->getAdditionalTableName() . " WHERE qpl_questions.question_id = ? AND qpl_questions.question_id = " . $this->getAdditionalTableName() . ".question_fi",
			array("integer")
		);
		$result = $ilDB->execute($statement, array($question_id));
		if ($result->numRows() == 1)
		{
			$data = $ilDB->fetchAssoc($result);
			$this->setId($question_id);
			$this->setTitle($data["title"]);
			$this->setComment($data["comment"]);
			$this->setSuggestedSolution($data["solution_hint"]);
			$this->setOriginalId($data["original_id"]);
			$this->setObjId($data["obj_fi"]);
			$this->setAuthor($data["author"]);
			$this->setOwner($data["owner"]);
			$this->setPoints($data["points"]);

			include_once("./Services/RTE/classes/class.ilRTE.php");
			$this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
			$this->setEstimatedWorkingTime(substr($data["working_time"], 0, 2), substr($data["working_time"], 3, 2), substr($data["working_time"], 6, 2));
			$this->setOrderText($data["ordertext"]);
			$this->setTextSize($data["textsize"]);
		}
		parent::loadFromDb($question_id);
	}

	/**
	* Duplicates an assOrderingHorizontal
	*/
	public function duplicate($for_test = true, $title = "", $author = "", $owner = "")
	{
		if ($this->id <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}
		// duplicate the question in database
		$this_id = $this->getId();
		$clone = $this;
		include_once ("./Modules/TestQuestionPool/classes/class.assQuestion.php");
		$original_id = assQuestion::_getOriginalId($this->id);
		$clone->id = -1;
		if ($title)
		{
			$clone->setTitle($title);
		}

		if ($author)
		{
			$clone->setAuthor($author);
		}
		if ($owner)
		{
			$clone->setOwner($owner);
		}

		if ($for_test)
		{
			$clone->saveToDb($original_id);
		}
		else
		{
			$clone->saveToDb();
		}

		// copy question page content
		$clone->copyPageOfQuestion($this_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($this_id);
		// duplicate the generic feedback
		$clone->duplicateFeedbackGeneric($this_id);

		$clone->onDuplicate($this_id);
		return $clone->id;
	}

	/**
	* Copies an assOrderingHorizontal object
	*/
	public function copyObject($target_questionpool, $title = "")
	{
		if ($this->id <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}
		// duplicate the question in database
		$clone = $this;
		include_once ("./Modules/TestQuestionPool/classes/class.assQuestion.php");
		$original_id = assQuestion::_getOriginalId($this->id);
		$clone->id = -1;
		$source_questionpool = $this->getObjId();
		$clone->setObjId($target_questionpool);
		if ($title)
		{
			$clone->setTitle($title);
		}
		$clone->saveToDb();

		// copy question page content
		$clone->copyPageOfQuestion($original_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($original_id);
		// duplicate the generic feedback
		$clone->duplicateFeedbackGeneric($original_id);

		$clone->onCopy($this->getObjId(), $this->getId());

		return $clone->id;
	}

	/**
	* Returns the maximum points, a learner can reach answering the question
	*
	* @see $points
	*/
	public function getMaximumPoints()
	{
		return $this->points;
	}

	/**
	* Returns the points, a learner has reached answering the question
	* The points are calculated from the given answers including checks
	* for all special scoring options in the test container.
	*
	* @param integer $user_id The database ID of the learner
	* @param integer $test_id The database Id of the test containing the question
	*/
	public function calculateReachedPoints($active_id, $pass = NULL)
	{
		global $ilDB;
		
		$found_values = array();
		if (is_null($pass))
		{
			$pass = $this->getSolutionMaxPass($active_id);
		}
		$statement = $ilDB->prepare("SELECT * FROM tst_solutions WHERE active_fi = ? AND question_fi = ? AND pass = ?",
			array("integer", "integer", "integer")
		);
		$result = $ilDB->execute($statement, array($active_id, $this->getId(), $pass));

		$points = 0;
		while ($data = $ilDB->fetchAssoc($result))
		{
			$points += $data["points"];
		}

		$points = parent::calculateReachedPoints($active_id, $pass = NULL, $points);
		return $points;
	}
	
	/**
	* Saves the learners input of the question to the database
	*
	* @param integer $test_id The database id of the test containing this question
  * @return boolean Indicates the save status (true if saved successful, false otherwise)
	* @see $answers
	*/
	public function saveWorkingData($active_id, $pass = NULL)
	{
		parent::saveWorkingData($active_id, $pass);
		return true;
	}

	/**
	* Returns the question type of the question
	*
	* @return integer The question type of the question
	*/
	public function getQuestionType()
	{
		return "assOrderingHorizontal";
	}
	
	/**
	* Returns the name of the additional question data table in the database
	*
	* @return string The additional table name
	*/
	public function getAdditionalTableName()
	{
		return "qpl_question_orderinghorizontal";
	}
	
	/**
	* Returns the name of the answer table in the database
	*
	* @return string The answer table name
	*/
	public function getAnswerTableName()
	{
		return "";
	}
	
	/**
	* Deletes datasets from answers tables
	*
	* @param integer $question_id The question id which should be deleted in the answers table
	*/
	public function deleteAnswers($question_id)
	{
	}

	/**
	* Collects all text in the question which could contain media objects
	* which were created with the Rich Text Editor
	*/
	public function getRTETextWithMediaObjects()
	{
		$text = parent::getRTETextWithMediaObjects();
		return $text;
	}

	/**
	* Creates an Excel worksheet for the detailed cumulated results of this question
	*
	* @param object $worksheet Reference to the parent excel worksheet
	* @param object $startrow Startrow of the output in the excel worksheet
	* @param object $active_id Active id of the participant
	* @param object $pass Test pass
	* @param object $format_title Excel title format
	* @param object $format_bold Excel bold format
	* @param array $eval_data Cumulated evaluation data
	*/
	public function setExportDetailsXLS(&$worksheet, $startrow, $active_id, $pass, &$format_title, &$format_bold)
	{
		include_once ("./classes/class.ilExcelUtils.php");
		$worksheet->writeString($startrow, 0, ilExcelUtils::_convert_text($this->lng->txt($this->getQuestionType())), $format_title);
		$worksheet->writeString($startrow, 1, ilExcelUtils::_convert_text($this->getTitle()), $format_title);
		return $startrow + 1;
	}
	
	/**
	* Creates a question from a QTI file
	*
	* Receives parameters from a QTI parser and creates a valid ILIAS question object
	*
	* @param object $item The QTI item object
	* @param integer $questionpool_id The id of the parent questionpool
	* @param integer $tst_id The id of the parent test if the question is part of a test
	* @param object $tst_object A reference to the parent test object
	* @param integer $question_counter A reference to a question counter to count the questions of an imported question pool
	* @param array $import_mapping An array containing references to included ILIAS objects
	*/
	public function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		include_once "./Modules/TestQuestionPool/classes/import/qti12/class.assOrderingHorizontalImport.php";
		$import = new assOrderingHorizontalImport($this);
		$import->fromXML($item, $questionpool_id, $tst_id, $tst_object, $question_counter, $import_mapping);
	}
	
	/**
	* Returns a QTI xml representation of the question and sets the internal
	* domxml variable with the DOM XML representation of the QTI xml representation
	*
	* @return string The QTI xml representation of the question
	*/
	public function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
		include_once "./Modules/TestQuestionPool/classes/export/qti12/class.assOrderingHorizontalExport.php";
		$export = new assOrderingHorizontalExport($this);
		return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
	}

	/**
	* Returns the best solution for a given pass of a participant
	*
	* @return array An associated array containing the best solution
	*/
	public function getBestSolution($active_id, $pass)
	{
		$user_solution = array();
		return $user_solution;
	}
	
	/**
	* Get ordering elements from order text
	*
	* @return array Ordering elements
	*/
	public function getOrderingElements()
	{
		$text = $this->getOrderText();
		$result = array();
		if (ilStr::strPos($text, $this->separator) === false)
		{
			$result = preg_split("/\\s+/", $text);
		}
		else
		{
			$result = split($this->separator, $text);
		}
		return $result;
	}
	
	/**
	* Get order text
	*
	* @return string Order text
	*/
	public function getOrderText()
	{
		return $this->ordertext;
	}
	
	/**
	* Set order text
	*
	* @param string $a_value Order text
	*/
	public function setOrderText($a_value)
	{
		$this->ordertext = $a_value;
	}
	
	/**
	* Get text size
	*
	* @return double Text size in percent
	*/
	public function getTextSize()
	{
		return $this->textsize;
	}
	
	/**
	* Set text size
	*
	* @param double $a_value Text size in percent
	*/
	public function setTextSize($a_value)
	{
		if ($a_value >= 10)
		{
			$this->textsize = $a_value;
		}
	}
	
	/**
	* Get order text separator
	*
	* @return string Separator
	*/
	public function getSeparator()
	{
		return $this->separator;
	}
	
	/**
	* Set order text separator
	*
	* @param string $a_value Separator
	*/
	public function setSeparator($a_value)
	{
		$this->separator = $a_value;
	}
	
	/**
	* Object getter
	*/
	protected function __get($value)
	{
		switch ($value)
		{
			case "ordertext":
				return $this->getOrderText();
				break;
			case "textsize":
				return $this->getTextSize();
				break;
			case "separator":
				return $this->getSeparator();
				break;
			default:
				return parent::__get($value);
				break;
		}
	}

	/**
	* Object setter
	*/
	protected function __set($key, $value)
	{
		switch ($key)
		{
			case "ordertext":
				$this->setOrderText($value);
				break;
			case "textsize":
				$this->setTextSize($value);
				break;
			case "separator":
				$this->setSeparator($value);
				break;
			default:
				parent::__set($key, $value);
				break;
		}
	}
}

?>
