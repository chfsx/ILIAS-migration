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

include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
include_once "./Modules/Survey/classes/inc.SurveyConstants.php";

/**
* Text survey question
*
* The SurveyTextQuestion class defines and encapsulates basic methods and attributes
* for text survey question types.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @extends SurveyQuestion
* @ingroup ModulesSurveyQuestionPool
*/
class SurveyTextQuestion extends SurveyQuestion 
{
	var $maxchars;
/**
* SurveyTextQuestion constructor
*
* The constructor takes possible arguments an creates an instance of the SurveyTextQuestion object.
*
* @param string $title A title string to describe the question
* @param string $description A description string to describe the question
* @param string $author A string containing the name of the questions author
* @param integer $owner A numerical ID to identify the owner/creator
* @access public
*/
  function SurveyTextQuestion(
    $title = "",
    $description = "",
    $author = "",
		$questiontext = "",
    $owner = -1
  )

  {
		$this->SurveyQuestion($title, $description, $author, $questiontext, $owner);
		$this->maxchars = 0;
	}
	
	/**
	* Returns the question data fields from the database
	*
	* Returns the question data fields from the database
	*
	* @param integer $id The question ID from the database
	* @return array Array containing the question fields and data from the database
	* @access public
	*/
	function _getQuestionDataArray($id)
	{
		global $ilDB;
		
    $query = sprintf("SELECT survey_question.*, survey_question_text.* FROM survey_question, survey_question_text WHERE survey_question.question_id = %s AND survey_question.question_id = survey_question_text.question_fi",
      $ilDB->quote($id)
    );
    $result = $ilDB->query($query);
		if ($result->numRows() == 1)
		{
			return $result->fetchRow(DB_FETCHMODE_ASSOC);
		}
		else
		{
			return array();
		}
	}
	
/**
* Loads a SurveyTextQuestion object from the database
*
* Loads a SurveyTextQuestion object from the database
*
* @param integer $id The database id of the text survey question
* @access public
*/
  function loadFromDb($id) 
	{
		global $ilDB;
    $query = sprintf("SELECT survey_question.*, survey_question_text.* FROM survey_question, survey_question_text WHERE survey_question.question_id = %s AND survey_question.question_id = survey_question_text.question_fi",
      $ilDB->quote($id)
    );
    $result = $ilDB->query($query);
    if (strcmp(strtolower(get_class($result)), db_result) == 0) 
		{
      if ($result->numRows() == 1) 
			{
				$data = $result->fetchRow(DB_FETCHMODE_OBJECT);
				$this->id = $data->question_id;
				$this->title = $data->title;
				$this->description = $data->description;
				$this->obj_id = $data->obj_fi;
				$this->author = $data->author;
				$this->obligatory = $data->obligatory;
				$this->owner = $data->owner_fi;
				$this->original_id = $data->original_id;
				$this->maxchars = $data->maxchars;
				include_once("./Services/RTE/classes/class.ilRTE.php");
				$this->questiontext = ilRTE::_replaceMediaObjectImageSrc($data->questiontext, 1);
				$this->complete = $data->complete;
      }
      // loads materials uris from database
      $this->loadMaterialFromDb($id);
		}
		parent::loadFromDb($id);
  }

/**
* Returns true if the question is complete for use
*
* Returns true if the question is complete for use
*
* @result boolean True if the question is complete for use, otherwise false
* @access public
*/
	function isComplete()
	{
		if ($this->title and $this->author and $this->questiontext)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}
	
/**
* Sets the maximum number of allowed characters for the text answer
*
* Sets the maximum number of allowed characters for the text answer
*
* @access public
*/
	function setMaxChars($maxchars = 0)
	{
		$this->maxchars = $maxchars;
	}
	
/**
* Returns the maximum number of allowed characters for the text answer
*
* Returns the maximum number of allowed characters for the text answer
*
* @access public
*/
	function getMaxChars()
	{
		return $this->maxchars;
	}
	
/**
* Saves a SurveyTextQuestion object to a database
*
* Saves a SurveyTextQuestion object to a database
*
* @access public
*/
  function saveToDb($original_id = "")
  {
		global $ilDB;
		$maxchars = "NULL";
		if ($this->maxchars)
		{
			$maxchars = $ilDB->quote($this->maxchars . "");
		}
		$complete = 0;
		if ($this->isComplete()) {
			$complete = 1;
		}
		if ($original_id)
		{
			$original_id = $ilDB->quote($original_id);
		}
		else
		{
			$original_id = "NULL";
		}

		// cleanup RTE images which are not inserted into the question text
		include_once("./Services/RTE/classes/class.ilRTE.php");
		ilRTE::_cleanupMediaObjectUsage($this->questiontext, "spl:html",
			$this->getId());

    if ($this->id == -1) 
		{
      // Write new dataset
      $now = getdate();
      $created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
      $query = sprintf("INSERT INTO survey_question (question_id, questiontype_fi, obj_fi, owner_fi, title, description, author, questiontext, obligatory, complete, created, original_id, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NULL)",
				$ilDB->quote($this->getQuestionTypeID() . ""),
				$ilDB->quote($this->obj_id),
				$ilDB->quote($this->owner),
				$ilDB->quote($this->title),
				$ilDB->quote($this->description),
				$ilDB->quote($this->author),
				$ilDB->quote(ilRTE::_replaceMediaObjectImageSrc($this->questiontext, 0)),
				$ilDB->quote(sprintf("%d", $this->obligatory)),
				$ilDB->quote("$complete"),
				$ilDB->quote($created),
				$original_id
      );
      $result = $ilDB->query($query);
      if ($result == DB_OK) 
			{
        $this->id = $ilDB->getLastInsertId();
				$query = sprintf("INSERT INTO survey_question_text (question_fi, maxchars) VALUES (%s, %s)",
					$ilDB->quote($this->id . ""),
					$maxchars
				);
				$ilDB->query($query);
      }
    } 
		else 
		{
      // update existing dataset
      $query = sprintf("UPDATE survey_question SET title = %s, description = %s, author = %s, questiontext = %s, obligatory = %s, complete = %s WHERE question_id = %s",
				$ilDB->quote($this->title),
				$ilDB->quote($this->description),
				$ilDB->quote($this->author),
				$ilDB->quote(ilRTE::_replaceMediaObjectImageSrc($this->questiontext, 0)),
				$ilDB->quote(sprintf("%d", $this->obligatory)),
				$ilDB->quote("$complete"),
				$ilDB->quote($this->id)
      );
      $result = $ilDB->query($query);
			$query = sprintf("UPDATE survey_question_text SET maxchars = %s WHERE question_fi = %s",
				$maxchars,
				$ilDB->quote($this->id . "")
			);
			$result = $ilDB->query($query);
    }
    if ($result == DB_OK) {
      // saving material uris in the database
      $this->saveMaterialsToDb();
    }
		parent::saveToDb($original_id);
  }

	/**
	* Returns an xml representation of the question
	*
	* Returns an xml representation of the question
	*
	* @return string The xml representation of the question
	* @access public
	*/
	function toXML($a_include_header = true, $obligatory_state = "")
	{
		include_once("./classes/class.ilXmlWriter.php");
		$a_xml_writer = new ilXmlWriter;

		$a_xml_writer->xmlHeader();
		$attrs = array(
			"id" => $this->getId(),
			"title" => $this->getTitle(),
			"type" => $this->getQuestiontype(),
			"obligatory" => $this->getObligatory()
		);
		$a_xml_writer->xmlStartTag("question", $attrs);
		
		$a_xml_writer->xmlElement("description", NULL, $this->getDescription());
		$a_xml_writer->xmlElement("author", NULL, $this->getAuthor());
		$a_xml_writer->xmlStartTag("questiontext");
		$this->addMaterialTag($a_xml_writer, $this->getQuestiontext());
		$a_xml_writer->xmlEndTag("questiontext");

		$a_xml_writer->xmlStartTag("responses");
		$attrs = array(
			"id" => "0"
		);
		if ($this->getMaxChars() > 0)
		{
			$attrs["maxlength"] = $this->getMaxChars();
		}
		$a_xml_writer->xmlElement("response_text", $attrs);
		$a_xml_writer->xmlEndTag("responses");

		if (count($this->material))
		{
			if (preg_match("/il_(\d*?)_(\w+)_(\d+)/", $this->material["internal_link"], $matches))
			{
				$attrs = array(
					"label" => $this->material["title"]
				);
				$a_xml_writer->xmlStartTag("material", $attrs);
				$intlink = "il_" . IL_INST_ID . "_" . $matches[2] . "_" . $matches[3];
				if (strcmp($matches[1], "") != 0)
				{
					$intlink = $this->material["internal_link"];
				}
				$a_xml_writer->xmlElement("mattext", NULL, $intlink);
				$a_xml_writer->xmlEndTag("material");
			}
		}
		
		$a_xml_writer->xmlEndTag("question");

		$xml = $a_xml_writer->xmlDumpMem(FALSE);
		if (!$a_include_header)
		{
			$pos = strpos($xml, "?>");
			$xml = substr($xml, $pos + 2);
		}
		return $xml;
	}

	function syncWithOriginal()
	{
		global $ilDB;
		if ($this->original_id)
		{
			$complete = 0;
			if ($this->isComplete()) 
			{
				$complete = 1;
			}
      $query = sprintf("UPDATE survey_question SET title = %s, description = %s, author = %s, questiontext = %s, obligatory = %s, complete = %s WHERE question_id = %s",
				$ilDB->quote($this->title . ""),
				$ilDB->quote($this->description . ""),
				$ilDB->quote($this->author . ""),
				$ilDB->quote($this->questiontext . ""),
				$ilDB->quote(sprintf("%d", $this->obligatory) . ""),
				$ilDB->quote($complete . ""),
				$ilDB->quote($this->original_id . "")
      );
      $result = $ilDB->query($query);
			$query = sprintf("UPDATE survey_question_text SET maxchars = %s WHERE question_fi = %s",
				$ilDB->quote($this->getMaxChars() . ""),
				$ilDB->quote($this->original_id . "")
			);
			$result = $ilDB->query($query);
		}
		parent::syncWithOriginal();
	}
	
	/**
	* Returns the maxium number of allowed characters for the text answer
	*
	* Returns the maxium number of allowed characters for the text answer
	*
	* @return integer The maximum number of characters
	* @access public
	*/
	function _getMaxChars($question_id)
	{
		global $ilDB;
		$query = sprintf("SELECT maxchars FROM survey_question WHERE question_id = %s",
			$ilDB->quote($question_id . "")
		);
		$result = $ilDB->query($query);
		if ($result->numRows())
		{
			$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
			return $row["maxchars"];
		}
		return 0;
	}

	/**
	* Returns the question type of the question
	*
	* Returns the question type of the question
	*
	* @return integer The question type of the question
	* @access public
	*/
	function getQuestionType()
	{
		return "SurveyTextQuestion";
	}

	/**
	* Returns the name of the additional question data table in the database
	*
	* Returns the name of the additional question data table in the database
	*
	* @return string The additional table name
	* @access public
	*/
	function getAdditionalTableName()
	{
		return "survey_question_text";
	}
	
	/**
	* Creates the user data of the survey_answer table from the POST data
	*
	* Creates the user data of the survey_answer table from the POST data
	*
	* @return array User data according to the survey_answer table
	* @access public
	*/
	function &getWorkingDataFromUserInput($post_data)
	{
		$entered_value = $post_data[$this->getId() . "_text_question"];
		if (strlen($entered_value))
		{
			$data = array("textanswer" => $entered_value);
		}
		return $data;
	}
	
	function checkUserInput($post_data)
	{
		$entered_value = $post_data[$this->getId() . "_text_question"];
		
		if ((!$this->getObligatory()) && (strlen($entered_value) == 0)) return "";
		
		if (strlen($entered_value) == 0) return $this->lng->txt("text_question_not_filled_out");

		return "";
	}
	
	function saveUserInput($post_data, $survey_id, $user_id, $anonymous_id)
	{
		global $ilDB;

		include_once "./classes/class.ilUtil.php";
		$entered_value = ilUtil::stripSlashes($post_data[$this->getId() . "_text_question"]);
		$maxchars = $this->getMaxChars();
		if ($maxchars > 0)
		{
			$entered_value = substr($entered_value, 0, $maxchars);
		}
		if (strlen($entered_value) == 0) return;
		$entered_value = $ilDB->quote($entered_value . "");
		$query = sprintf("INSERT INTO survey_answer (answer_id, survey_fi, question_fi, user_fi, anonymous_id, value, textanswer, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, %s, %s, NULL)",
			$ilDB->quote($survey_id . ""),
			$ilDB->quote($this->getId() . ""),
			$ilDB->quote($user_id . ""),
			$ilDB->quote($anonymous_id . ""),
			"NULL",
			$entered_value
		);
		$result = $ilDB->query($query);
	}
	
	function &getCumulatedResults($survey_id, $nr_of_users)
	{
		global $ilDB;
		
		$question_id = $this->getId();
		
		$result_array = array();
		$cumulated = array();
		$textvalues = array();

		$query = sprintf("SELECT * FROM survey_answer WHERE question_fi = %s AND survey_fi = %s",
			$ilDB->quote($question_id),
			$ilDB->quote($survey_id)
		);
		$result = $ilDB->query($query);
		
		while ($row = $result->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$cumulated["$row->value"]++;
			array_push($textvalues, $row->textanswer);
		}
		asort($cumulated, SORT_NUMERIC);
		end($cumulated);
		$numrows = $result->numRows();
		$result_array["USERS_ANSWERED"] = $result->numRows();
		$result_array["USERS_SKIPPED"] = $nr_of_users - $result->numRows();
		$result_array["QUESTION_TYPE"] = "SurveyTextQuestion";
		$result_array["textvalues"] = $textvalues;
		return $result_array;
	}
	
	/**
	* Creates an Excel worksheet for the detailed cumulated results of this question
	*
	* Creates an Excel worksheet for the detailed cumulated results of this question
	*
	* @param object $workbook Reference to the parent excel workbook
	* @param object $format_title Excel title format
	* @param object $format_bold Excel bold format
	* @param array $eval_data Cumulated evaluation data
	* @access public
	*/
	function setExportDetailsXLS(&$workbook, &$format_title, &$format_bold, &$eval_data)
	{
		include_once ("./classes/class.ilExcelUtils.php");
		$worksheet =& $workbook->addWorksheet();
		$worksheet->writeString(0, 0, ilExcelUtils::_convert_text($this->lng->txt("title")), $format_bold);
		$worksheet->writeString(0, 1, ilExcelUtils::_convert_text($this->getTitle()));
		$worksheet->writeString(1, 0, ilExcelUtils::_convert_text($this->lng->txt("question")), $format_bold);
		$worksheet->writeString(1, 1, ilExcelUtils::_convert_text($this->getQuestiontext()));
		$worksheet->writeString(2, 0, ilExcelUtils::_convert_text($this->lng->txt("question_type")), $format_bold);
		$worksheet->writeString(2, 1, ilExcelUtils::_convert_text($this->lng->txt($this->getQuestionType())));
		$worksheet->writeString(3, 0, ilExcelUtils::_convert_text($this->lng->txt("users_answered")), $format_bold);
		$worksheet->write(3, 1, $eval_data["USERS_ANSWERED"]);
		$worksheet->writeString(4, 0, ilExcelUtils::_convert_text($this->lng->txt("users_skipped")), $format_bold);
		$worksheet->write(4, 1, $eval_data["USERS_SKIPPED"]);
		$rowcounter = 5;

		$worksheet->write($rowcounter, 0, ilExcelUtils::_convert_text($this->lng->txt("given_answers")), $format_bold);
		$textvalues = "";
		if (is_array($eval_data["textvalues"]))
		{
			foreach ($eval_data["textvalues"] as $textvalue)
			{
				$worksheet->write($rowcounter++, 1, $textvalue);
			}
		}
	}

	/**
	* Adds the values for the user specific results export for a given user
	*
	* Adds the values for the user specific results export for a given user
	*
	* @param array $a_array An array which is used to append the values
	* @param array $resultset The evaluation data for a given user
	* @access public
	*/
	function addUserSpecificResultsData(&$a_array, &$resultset)
	{
		if (count($resultset["answers"][$this->getId()]))
		{
			foreach ($resultset["answers"][$this->getId()] as $key => $answer)
			{
				array_push($a_array, $answer["textanswer"]);
			}
		}
		else
		{
			array_push($a_array, $this->lng->txt("skipped"));
		}
	}

	/**
	* Returns an array containing all answers to this question in a given survey
	*
	* Returns an array containing all answers to this question in a given survey
	*
	* @param integer $survey_id The database ID of the survey
	* @return array An array containing the answers to the question. The keys are either the user id or the anonymous id
	* @access public
	*/
	function &getUserAnswers($survey_id)
	{
		global $ilDB;
		
		$answers = array();

		$query = sprintf("SELECT * FROM survey_answer WHERE survey_fi = %s AND question_fi = %s",
			$ilDB->quote($survey_id),
			$ilDB->quote($this->getId())
		);
		$result = $ilDB->query($query);
		while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if (strlen($row["anonymous_id"]) > 0)
			{
				$answers[$row["anonymous_id"]] = $row["textanswer"];
			}
			else
			{
				$answers[$row["user_fi"]] = $row["textanswer"];
			}
		}
		return $answers;
	}

	/**
	* Import response data from the question import file
	*
	* Import response data from the question import file
	*
	* @return array $a_data Array containing the response data
	* @access public
	*/
	function importResponses($a_data)
	{
		foreach ($a_data as $id => $data)
		{
			if ($data["maxlength"] > 0)
			{
				$this->setMaxChars($data["maxlength"]);
			}
		}
	}
}
?>
