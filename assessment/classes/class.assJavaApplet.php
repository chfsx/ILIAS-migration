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
require_once "./assessment/classes/class.assQuestion.php";

define ("JAVAAPPLET_QUESTION_IDENTIFIER", "JAVA APPLET QUESTION");

/**
* Class for Java Applet Questions
*
* ASS_JavaApplet is a class for Java Applet Questions.
*
* @author		Helmut Schottmüller <hschottm@tzi.de>
* @version	$Id$
* @module   class.assJavaApplet.php
* @modulegroup   Assessment
*/
class ASS_JavaApplet extends ASS_Question
{
	/**
	* Question string
	*
	* The question string of the multiple choice question
	*
	* @var string
	*/
	var $question;

	/**
	* Java applet file name
	*
	* The file name of the java applet
	*
	* @var string
	*/
	var $javaapplet_filename;

	/**
	* Java Applet code parameter
	*
	* Java Applet code parameter
	*
	* @var string
	*/
	var $java_code;

	/**
	* Java Applet width parameter
	*
	* Java Applet width parameter
	*
	* @var integer
	*/
	var $java_width;

	/**
	* Java Applet height parameter
	*
	* Java Applet height parameter
	*
	* @var integer
	*/
	var $java_height;

	/**
	* Additional java applet parameters
	*
	* Additional java applet parameters
	*
	* @var array
	*/
	var $parameters;

	/**
	* ASS_JavaApplet constructor
	*
	* The constructor takes possible arguments an creates an instance of the ASS_JavaApplet object.
	*
	* @param string $title A title string to describe the question
	* @param string $comment A comment string to describe the question
	* @param string $author A string containing the name of the questions author
	* @param integer $owner A numerical ID to identify the owner/creator
	* @param string $question The question string of the multiple choice question
	* @param integer $response Indicates the response type of the multiple choice question
	* @param integer $output_type The output order of the multiple choice answers
	* @access public
	* @see ASS_Question:ASS_Question()
	*/
	function ASS_JavaApplet(
		$title = "",
		$comment = "",
		$author = "",
		$owner = -1,
		$question = "",
		$javaapplet_filename = ""
	)
	{
		$this->ASS_Question($title, $comment, $author, $owner);
		$this->question = $question;
		$this->javaapplet_filename = $javaapplet_filename;
		$this->parameters = array();
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
	function to_xml($a_include_header = true, $a_include_binary = true, $a_shuffle = false)
	{
		if (!empty($this->domxml))
		{
			$this->domxml->free();
		}
		$xml_header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<questestinterop></questestinterop>\n";
		$this->domxml = domxml_open_mem($xml_header);
		$root = $this->domxml->document_element();

		// qti comment with version information
		$qtiComment = $this->domxml->create_element("qticomment");

		// qti ident
		$qtiIdent = $this->domxml->create_element("item");
		$qtiIdent->set_attribute("ident", $this->getId());
		$qtiIdent->set_attribute("title", $this->getTitle());
		$root->append_child($qtiIdent);

		// add qti comment
		$qtiComment = $this->domxml->create_element("qticomment");
		$qtiCommentText = $this->domxml->create_text_node($this->getComment());
		$qtiComment->append_child($qtiCommentText);
		$qtiIdent->append_child($qtiComment);
		$qtiComment = $this->domxml->create_element("qticomment");
		$qtiCommentText = $this->domxml->create_text_node("ILIAS Version=".$this->ilias->getSetting("ilias_version"));
		$qtiComment->append_child($qtiCommentText);
		$qtiIdent->append_child($qtiComment);
		$qtiComment = $this->domxml->create_element("qticomment");
		$qtiCommentText = $this->domxml->create_text_node("Questiontype=".JAVAAPPLET_QUESTION_IDENTIFIER);
		$qtiComment->append_child($qtiCommentText);
		$qtiIdent->append_child($qtiComment);
		// add estimated working time
		$qtiIdent->append_child($qtiComment);
		$qtiDuration = $this->domxml->create_element("duration");
		$workingtime = $this->getEstimatedWorkingTime();
		$qtiDurationText = $this->domxml->create_text_node(sprintf("P0Y0M0DT%dH%dM%dS", $workingtime["h"], $workingtime["m"], $workingtime["s"]));
		$qtiDuration->append_child($qtiDurationText);
		$qtiIdent->append_child($qtiDuration);

		// PART I: qti presentation
		$qtiPresentation = $this->domxml->create_element("presentation");
		$qtiPresentation->set_attribute("label", $this->getTitle());

		// add flow to presentation
		$qtiFlow = $this->domxml->create_element("flow");

		// add material with question text to presentation
		$qtiMaterial = $this->domxml->create_element("material");
		$qtiMatText = $this->domxml->create_element("mattext");
		$qtiMatTextText = $this->domxml->create_text_node($this->get_question());
		$qtiMatText->append_child($qtiMatTextText);
		$qtiMaterial->append_child($qtiMatText);
		$qtiFlow->append_child($qtiMaterial);

		$qtiMaterial = $this->domxml->create_element("material");
		$qtiMatApplet = $this->domxml->create_element("matapplet");
		$qtiMaterial->append_child($qtiMatApplet);
		$qtiFlow->append_child($qtiMaterial);

		$xml = $this->domxml->dump_mem(true);
		if (!$a_include_header)
		{
			$pos = strpos($xml, "?>");
			$xml = substr($xml, $pos + 2);
		}
//echo htmlentities($xml);
		return $xml;

	}

	/**
	* Sets the applet parameters from a parameter string containing all parameters in a list
	*
	* Sets the applet parameters from a parameter string containing all parameters in a list
	*
	* @param string $params All applet parameters in a list
	* @access public
	*/
	function split_params($params = "")
	{
		$params_array = split("<separator>", $params);
		foreach ($params_array as $pair)
		{
			if (preg_match("/(.*?)\=(.*)/", $pair, $matches))
			{
				switch ($matches[1])
				{
					case "java_code" :
						$this->java_code = $matches[2];
						break;
					case "java_width" :
						$this->java_width = $matches[2];
						break;
					case "java_height" :
						$this->java_height = $matches[2];
						break;
				}
				if (preg_match("/param_name_(\d+)/", $matches[1], $found_key))
				{
					$this->parameters[$found_key[1]]["name"] = $matches[2];
				}
				if (preg_match("/param_value_(\d+)/", $matches[1], $found_key))
				{
					$this->parameters[$found_key[1]]["value"] = $matches[2];
				}
			}
		}
	}

	/**
	* Returns a string containing the applet parameters
	*
	* Returns a string containing the applet parameters. This is used for saving the applet data to database
	*
	* @return string All applet parameters
	* @access public
	*/
	function build_params()
	{
		$params_array = array();
		if ($this->java_code)
		{
			array_push($params_array, "java_code=$this->java_code");
		}
		if ($this->java_width)
		{
			array_push($params_array, "java_width=$this->java_width");
		}
		if ($this->java_height)
		{
			array_push($params_array, "java_height=$this->java_height");
		}
		foreach ($this->parameters as $key => $value)
		{
			array_push($params_array, "param_name_$key=" . $value["name"]);
			array_push($params_array, "param_value_$key=" . $value["value"]);
		}
		return join($params_array, "<separator>");
	}

	/**
	* Returns true, if a imagemap question is complete for use
	*
	* Returns true, if a imagemap question is complete for use
	*
	* @return boolean True, if the imagemap question is complete for use, otherwise false
	* @access public
	*/
	function isComplete()
	{
		if (($this->title) and ($this->author) and ($this->question) and ($this->javaapplet) and ($this->java_width) and ($this->java_height))
		{
			return true;
		}
			else
		{
			return false;
		}
	}


	/**
	* Saves a ASS_JavaApplet object to a database
	*
	* Saves a ASS_JavaApplet object to a database (experimental)
	*
	* @param object $db A pear DB object
	* @access public
	*/
	function saveToDb($original_id = "")
	{
		global $ilias;

		$complete = 0;
		if ($this->isComplete())
		{
			$complete = 1;
		}

		$db = & $ilias->db;

		$params = $this->build_params();
		$estw_time = $this->getEstimatedWorkingTime();
		$estw_time = sprintf("%02d:%02d:%02d", $estw_time['h'], $estw_time['m'], $estw_time['s']);

		if ($original_id)
		{
			$original_id = $db->quote($original_id);
		}
		else
		{
			$original_id = "NULL";
		}

		if ($this->id == -1)
		{
			// Neuen Datensatz schreiben
			$now = getdate();
			$question_type = 7;
			$created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
			$query = sprintf("INSERT INTO qpl_questions (question_id, question_type_fi, obj_fi, title, comment, author, owner, question_text, working_time, shuffle, complete, image_file, params, created, original_id, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NULL)",
				$db->quote($question_type),
				$db->quote($this->obj_id),
				$db->quote($this->title),
				$db->quote($this->comment),
				$db->quote($this->author),
				$db->quote($this->owner),
				$db->quote($this->question),
				$db->quote($estw_time),
				$db->quote("$this->shuffle"),
				$db->quote("$complete"),
				$db->quote($this->javaapplet_filename),
				$db->quote($params),
				$db->quote($created),
				$original_id
			);

			$result = $db->query($query);
			if ($result == DB_OK)
			{
				$this->id = $this->ilias->db->getLastInsertId();

				// create page object of question
				$this->createPageObject();

				// Falls die Frage in einen Test eingef�gt werden soll, auch diese Verbindung erstellen
				if ($this->getTestId() > 0)
				{
					$this->insertIntoTest($this->getTestId());
				}
			}
		}
		else
		{
			// Vorhandenen Datensatz aktualisieren
			$query = sprintf("UPDATE qpl_questions SET title = %s, comment = %s, author = %s, question_text = %s, working_time=%s, shuffle = %s, complete = %s, image_file = %s, params = %s WHERE question_id = %s",
				$db->quote($this->title),
				$db->quote($this->comment),
				$db->quote($this->author),
				$db->quote($this->question),
				$db->quote($estw_time),
				$db->quote("$this->shuffle"),
				$db->quote("$complete"),
				$db->quote($this->javaapplet_filename),
				$db->quote($params),
				$db->quote($this->id)
			);
			$result = $db->query($query);
		}
	}

	/**
	* Loads a ASS_JavaApplet object from a database
	*
	* Loads a ASS_JavaApplet object from a database (experimental)
	*
	* @param object $db A pear DB object
	* @param integer $question_id A unique key which defines the multiple choice test in the database
	* @access public
	*/
	function loadFromDb($question_id)
	{
		global $ilias;

		$db = & $ilias->db;
		$query = sprintf("SELECT * FROM qpl_questions WHERE question_id = %s",
			$db->quote($question_id)
		);
		$result = $db->query($query);

		if (strcmp(strtolower(get_class($result)), db_result) == 0)
		{
			if ($result->numRows() == 1)
			{
				$data = $result->fetchRow(DB_FETCHMODE_OBJECT);
				$this->id = $question_id;
				$this->title = $data->title;
				$this->comment = $data->comment;
				$this->obj_id = $data->obj_fi;
				$this->author = $data->author;
				$this->owner = $data->owner;
				$this->javaapplet_filename = $data->image_file;
				$this->question = $data->question_text;
				$this->split_params($data->params);
				$this->setShuffle($data->shuffle);
				$this->setEstimatedWorkingTime(substr($data->working_time, 0, 2), substr($data->working_time, 3, 2), substr($data->working_time, 6, 2));
			}
		}
	}

	/**
	* Duplicates an ASS_JavaApplet
	*
	* Duplicates an ASS_JavaApplet
	*
	* @access public
	*/
	function duplicate($for_test = true, $title = "", $author = "", $owner = "")
	{
		if ($this->id <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}
		// duplicate the question in database
		$clone = $this;
		$original_id = $this->id;
		if ($original_id <= 0)
		{
			$original_id = "";
		}
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
		$clone->copyPageOfQuestion($original_id);

		// duplicate the image
		$clone->duplicateApplet($original_id);
		return $clone->id;
	}

	function duplicateApplet($question_id)
	{
		$javapath = $this->getJavaPath();
		$javapath_original = preg_replace("/([^\d])$this->id([^\d])/", "\${1}$question_id\${2}", $javapath);
		if (!file_exists($javapath))
		{
			ilUtil::makeDirParents($javapath);
		}
		$filename = $this->get_javaapplet_filename();
		if (!copy($javapath_original . $filename, $javapath . $filename)) {
			print "java applet could not be duplicated!!!! ";
		}
	}

	/**
	* Gets the multiple choice question
	*
	* Gets the question string of the ASS_JavaApplet object
	*
	* @return string The question string of the ASS_JavaApplet object
	* @access public
	* @see $question
	*/
	function get_question()
	{
		return $this->question;
	}

	/**
	* Sets the question text
	*
	* Sets the question string of the ASS_JavaApplet object
	*
	* @param string $question A string containing the question text
	* @access public
	* @see $question
	*/
	function set_question($question = "")
	{
		$this->question = $question;
	}

	/**
	* Returns the maximum points, a learner can reach answering the question
	*
	* Returns the maximum points, a learner can reach answering the question
	*
	* @access public
	* @see $points
	*/
	function getMaximumPoints()
	{
		$found_values = array();
		$query = sprintf("SELECT * FROM tst_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s AND value1 = %s",
			$this->ilias->db->quote($user_id),
			$this->ilias->db->quote($test_id),
			$this->ilias->db->quote($this->getId()),
			$this->ilias->db->quote("_max_points_")
		);
		$result = $this->ilias->db->query($query);
		$points = 0;
		if ($data = $result->fetchRow(DB_FETCHMODE_OBJECT))
		{
				$points = $data->points;
		}
		return $points;
	}

	/**
	* Returns the java applet code parameter
	*
	* Returns the java applet code parameter
	*
	* @return string java applet code parameter
	* @access public
	*/
	function get_java_code()
	{
		return $this->java_code;
	}

	/**
	* Sets the java applet code parameter
	*
	* Sets the java applet code parameter
	*
	* @param string java applet code parameter
	* @access public
	*/
	function set_java_code($java_code = "")
	{
		$this->java_code = $java_code;
	}

	/**
	* Returns the java applet width parameter
	*
	* Returns the java applet width parameter
	*
	* @return integer java applet width parameter
	* @access public
	*/
	function get_java_width()
	{
		return $this->java_width;
	}

	/**
	* Sets the java applet width parameter
	*
	* Sets the java applet width parameter
	*
	* @param integer java applet width parameter
	* @access public
	*/
	function set_java_width($java_width = "")
	{
		$this->java_width = $java_width;
	}

	/**
	* Returns the java applet height parameter
	*
	* Returns the java applet height parameter
	*
	* @return integer java applet height parameter
	* @access public
	*/
	function get_java_height()
	{
		return $this->java_height;
	}

	/**
	* Sets the java applet height parameter
	*
	* Sets the java applet height parameter
	*
	* @param integer java applet height parameter
	* @access public
	*/
	function set_java_height($java_height = "")
	{
		$this->java_height = $java_height;
	}

	/**
	* Returns the points, a learner has reached answering the question
	*
	* Returns the points, a learner has reached answering the question
	*
	* @param integer $user_id The database ID of the learner
	* @param integer $test_id The database Id of the test containing the question
	* @access public
	*/
	function getReachedPoints($user_id, $test_id)
	{
		$found_values = array();
		$query = sprintf("SELECT * FROM tst_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",
			$this->ilias->db->quote($user_id),
			$this->ilias->db->quote($test_id),
			$this->ilias->db->quote($this->getId())
		);
		$result = $this->ilias->db->query($query);
		$points = 0;
		while ($data = $result->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$points += $data->points;
		}
		return $points;
	}

	/**
	* Returns the evaluation data, a learner has entered to answer the question
	*
	* Returns the evaluation data, a learner has entered to answer the question
	*
	* @param integer $user_id The database ID of the learner
	* @param integer $test_id The database Id of the test containing the question
	* @access public
	*/
	function getReachedInformation($user_id, $test_id)
	{
		$found_values = array();
		$query = sprintf("SELECT * FROM tst_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",
			$this->ilias->db->quote($user_id),
			$this->ilias->db->quote($test_id),
			$this->ilias->db->quote($this->getId())
		);
		$result = $this->ilias->db->query($query);
		$counter = 1;
		$user_result = array();
		while ($data = $result->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$true = 0;
			if ($data->points > 0)
			{
				$true = 1;
			}
			$solution = array(
				"order" => "$counter",
				"points" => "$data->points",
				"true" => "$true",
				"value" => "$data->value1 $data->value2",
			);
			$counter++;
			array_push($user_result, $solution);
		}
		return $user_result;
	}

	/**
	* Adds a new parameter value to the parameter list
	*
	* Adds a new parameter value to the parameter list
	*
	* @param string $name The name of the parameter value
	* @param string $value The value of the parameter value
	* @access public
	* @see $parameters
	*/
	function add_parameter($name = "", $value = "")
	{
		$index = get_parameter_index($name);
		if ($index > -1)
		{
			$this->parameters[$index] = array("name" => $name, "value" => $value);
		}
		else
		{
			array_push($this->parameters, array("name" => $name, "value" => $value));
		}
	}

	/**
	* Adds a new parameter value to the parameter list at a given index
	*
	* Adds a new parameter value to the parameter list at a given index
	*
	* @param integer $index The index at which the parameter should be inserted
	* @param string $name The name of the parameter value
	* @param string $value The value of the parameter value
	* @access public
	* @see $parameters
	*/
	function add_parameter_at_index($index = 0, $name = "", $value = "")
	{
		$this->parameters[$index] = array("name" => $name, "value" => $value);
	}

	/**
	* Removes a parameter value from the parameter list
	*
	* Removes a parameter value from the parameter list
	*
	* @param string $name The name of the parameter value
	* @access public
	* @see $parameters
	*/
	function remove_parameter($name)
	{
		foreach ($this->parameters as $key => $value)
		{
			if (strcmp($name, $value["name"]) == 0)
			{
				array_splice($this->parameters, $key, 1);
				return;
			}
		}
	}

	/**
	* Returns the paramter at a given index
	*
	* Returns the paramter at a given index
	*
	* @param intege $index The index value of the parameter
	* @return array The parameter at the given index
	* @access public
	* @see $parameters
	*/
	function get_parameter($index)
	{
		if (($index < 0) or ($index >= count($this->parameters)))
		{
			return undef;
		}
		return $this->parameters[$index];
	}

	/**
	* Returns the index of an applet parameter
	*
	* Returns the index of an applet parameter
	*
	* @param string $name The name of the parameter value
	* @return integer The index of the applet parameter or -1 if the parameter wasn't found
	* @access private
	* @see $parameters
	*/
	function get_parameter_index($name)
	{
		foreach ($this->parameters as $key => $value)
		{
			if (array_key_exists($name, $value))
			{
				return $key;
			}
		}
		return -1;
	}

	/**
	* Returns the number of additional applet parameters
	*
	* Returns the number of additional applet parameters
	*
	* @return integer The number of additional applet parameters
	* @access public
	* @see $parameters
	*/
	function get_parameter_count()
	{
		return count($this->parameters);
	}

	/**
	* Removes all applet parameters
	*
	* Removes all applet parameters
	*
	* @access public
	* @see $parameters
	*/
	function flush_params()
	{
		$this->parameters = array();
	}

	/**
	* Saves the learners input of the question to the database
	*
	* Saves the learners input of the question to the database
	*
	* @param integer $test_id The database id of the test containing this question
  * @return boolean Indicates the save status (true if saved successful, false otherwise)
	* @access public
	* @see $answers
	*/
	function saveWorkingData($test_id, $limit_to = LIMIT_NO_LIMIT)
	{
		/*    global $ilDB;
			global $ilUser;
	    $db =& $ilDB->db;

    	if ($this->response == RESPONSE_SINGLE) {
			$query = sprintf("SELECT * FROM tst_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",
				$db->quote($ilUser->id),
				$db->quote($test_id),
				$db->quote($this->getId())
			);
			$result = $db->query($query);
			$row = $result->fetchRow(DB_FETCHMODE_OBJECT);
			$update = $row->solution_id;
			if ($update) {
				$query = sprintf("UPDATE tst_solutions SET value1 = %s WHERE solution_id = %s",
					$db->quote($_POST["multiple_choice_result"]),
					$db->quote($update)
				);
			} else {
				$query = sprintf("INSERT INTO tst_solutions (solution_id, user_fi, test_fi, question_fi, value1, value2, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, NULL, NULL)",
					$db->quote($ilUser->id),
					$db->quote($test_id),
					$db->quote($this->getId()),
					$db->quote($_POST["multiple_choice_result"])
				);
			}
      $result = $db->query($query);
    } else {
			$query = sprintf("DELETE FROM tst_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",
				$db->quote($ilUser->id),
				$db->quote($test_id),
				$db->quote($this->getId())
			);
			$result = $db->query($query);
      foreach ($_POST as $key => $value) {
        if (preg_match("/multiple_choice_result_(\d+)/", $key, $matches)) {
					$query = sprintf("INSERT INTO tst_solutions (solution_id, user_fi, test_fi, question_fi, value1, value2, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, NULL, NULL)",
						$db->quote($ilUser->id),
						$db->quote($test_id),
						$db->quote($this->getId()),
						$db->quote($value)
					);
          $result = $db->query($query);
        }
      }
    }
    //parent::saveWorkingData($limit_to);
		return true;
*/  }

	/**
	* Gets the java applet file name
	*
	* Gets the java applet file name
	*
	* @return string The java applet file of the ASS_JavaApplet object
	* @access public
	* @see $javaapplet_filename
	*/
	function get_javaapplet_filename()
	{
		return $this->javaapplet_filename;
	}

	/**
	* Sets the java applet file name
	*
	* Sets the java applet file name
	*
	* @param string $javaapplet_file.
	* @access public
	* @see $javaapplet_filename
	*/
	function set_javaapplet_filename($javaapplet_filename, $javaapplet_tempfilename = "")
	{
		if (!empty($javaapplet_filename))
		{
			$this->javaapplet_filename = $javaapplet_filename;
		}
		if (!empty($javaapplet_tempfilename))
		{
			$javapath = $this->getJavaPath();
			if (!file_exists($javapath))
			{
				ilUtil::makeDirParents($javapath);
			}
			if (!move_uploaded_file($javaapplet_tempfilename, $javapath . $javaapplet_filename))
			{
				print "java applet not uploaded!!!! ";
			}
		}
	}

}

?>
