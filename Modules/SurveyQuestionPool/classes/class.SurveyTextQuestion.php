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
	* Imports a question from XML
	*
	* Sets the attributes of the question from the XML text passed
	* as argument
	*
	* @return boolean True, if the import succeeds, false otherwise
	* @access public
	*/
	function from_xml($xml_text)
	{
		$result = false;
		if (!empty($this->domxml))
		{
			$this->domxml->free();
		}
		$xml_text = preg_replace("/>\s*?</", "><", $xml_text);
		$this->domxml = domxml_open_mem($xml_text);
		if (!empty($this->domxml))
		{
			$root = $this->domxml->document_element();
			$item = $root->first_child();
			$this->setTitle($item->get_attribute("title"));
			$this->gaps = array();
			$itemnodes = $item->child_nodes();
			foreach ($itemnodes as $index => $node)
			{
				switch ($node->node_name())
				{
					case "qticomment":
						$comment = $node->get_content();
						if (strpos($comment, "ILIAS Version=") !== false)
						{
						}
						elseif (strpos($comment, "Questiontype=") !== false)
						{
						}
						elseif (strpos($comment, "Author=") !== false)
						{
							$comment = str_replace("Author=", "", $comment);
							$this->setAuthor($comment);
						}
						else
						{
							$this->setDescription($comment);
						}
						break;
					case "itemmetadata":
						$qtimetadata = $node->first_child();
						$metadata_fields = $qtimetadata->child_nodes();
						foreach ($metadata_fields as $index => $metadata_field)
						{
							$fieldlabel = $metadata_field->first_child();
							$fieldentry = $fieldlabel->next_sibling();
							switch ($fieldlabel->get_content())
							{
								case "obligatory":
									$this->setObligatory($fieldentry->get_content());
									break;
								case "maxchars":
									$this->setMaxChars($fieldentry->get_content());
									break;
							}
						}
						break;
					case "presentation":
						$flow = $node->first_child();
						$flownodes = $flow->child_nodes();
						foreach ($flownodes as $idx => $flownode)
						{
							if (strcmp($flownode->node_name(), "material") == 0)
							{
								$mattext = $flownode->first_child();
								$this->setQuestiontext($mattext->get_content());
							}
							elseif (strcmp($flownode->node_name(), "response_str") == 0)
							{
								$ident = $flownode->get_attribute("ident");
								$response_lid_nodes = $flownode->child_nodes();
								foreach ($response_lid_nodes as $resp_lid_id => $resp_lid_node)
								{
									switch ($resp_lid_node->node_name())
									{
										case "material":
											$matlabel = $resp_lid_node->get_attribute("label");
											$mattype = $resp_lid_node->first_child();
											if (strcmp($mattype->node_name(), "mattext") == 0)
											{
												$material = $mattype->get_content();
												if ($material)
												{
													if ($this->getId() < 1)
													{
														$this->saveToDb();
													}
													$this->setMaterial($material, true, $matlabel);
												}
											}
											break;
									}
								}
							}
						}
						break;
				}
			}
			$result = true;
		}
		return $result;
	}

	/**
	* Returns a QTI xml representation of the question
	*
	* Returns a QTI xml representation of the question and sets the internal
	* domxml variable with the DOM XML representation of the QTI xml representation
	*
	* @return string The QTI xml representation of the question
	* @access public
	*/
	function to_xml($a_include_header = true, $obligatory_state = "")
	{
		include_once("./classes/class.ilXmlWriter.php");
		$a_xml_writer = new ilXmlWriter;
		// set xml header
		$a_xml_writer->xmlHeader();
		$a_xml_writer->xmlStartTag("questestinterop");
		$attrs = array(
			"ident" => $this->getId(),
			"title" => $this->getTitle()
		);
		$a_xml_writer->xmlStartTag("item", $attrs);
		// add question description
		$a_xml_writer->xmlElement("qticomment", NULL, $this->getDescription());
		$a_xml_writer->xmlElement("qticomment", NULL, "ILIAS Version=".$this->ilias->getSetting("ilias_version"));
		$a_xml_writer->xmlElement("qticomment", NULL, "Questiontype=".$this->getQuestionType());
		$a_xml_writer->xmlElement("qticomment", NULL, "Author=".$this->getAuthor());
		// add ILIAS specific metadata
		$a_xml_writer->xmlStartTag("itemmetadata");
		$a_xml_writer->xmlStartTag("qtimetadata");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "obligatory");
		if (strcmp($obligatory_state, "") != 0)
		{
			$this->setObligatory($obligatory_state);
		}
		$a_xml_writer->xmlElement("fieldentry", NULL, sprintf("%d", $this->getObligatory()));
		$a_xml_writer->xmlEndTag("qtimetadatafield");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "maxchars");
		if (!$this->getMaxChars())
		{
			$a_xml_writer->xmlElement("fieldentry", NULL, "");
		}
		else
		{
			$a_xml_writer->xmlElement("fieldentry", NULL, sprintf("%d", $this->getMaxChars()));
		}
		$a_xml_writer->xmlEndTag("qtimetadatafield");
		$a_xml_writer->xmlEndTag("qtimetadata");
		$a_xml_writer->xmlEndTag("itemmetadata");

		// PART I: qti presentation
		$attrs = array(
			"label" => $this->getTitle()
		);
		$a_xml_writer->xmlStartTag("presentation", $attrs);
		// add flow to presentation
		$a_xml_writer->xmlStartTag("flow");
		// add material with question text to presentation
		$this->addQTIMaterial($a_xml_writer, $this->getQuestiontext());
		// add answers to presentation
		$attrs = array(
			"ident" => "TEXT",
			"rcardinality" => "Single"
		);
		$a_xml_writer->xmlStartTag("response_str", $attrs);
		
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

		$a_xml_writer->xmlEndTag("response_str");
		$a_xml_writer->xmlEndTag("flow");
		$a_xml_writer->xmlEndTag("presentation");
		$a_xml_writer->xmlEndTag("item");
		$a_xml_writer->xmlEndTag("questestinterop");

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
}
?>
