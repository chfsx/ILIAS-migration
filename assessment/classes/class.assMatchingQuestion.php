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

require_once "class.assQuestion.php";
require_once "class.assAnswerMatching.php";

define ("MT_TERMS_PICTURES", 0);
define ("MT_TERMS_DEFINITIONS", 1);

/**
* Class for matching questions
*
* ASS_MatchingQuestion is a class for matching questions.
*
* @author		Helmut Schottm�ller <hschottm@tzi.de>
* @version	$Id$
* @module   class.assMatchingQuestion.php
* @modulegroup   Assessment
*/
class ASS_MatchingQuestion extends ASS_Question {
/**
* The question text
*
* The question text of the matching question.
*
* @var string
*/
  var $question;

/**
* The possible matching pairs of the matching question
*
* $matchingpairs is an array of the predefined matching pairs of the matching question
*
* @var array
*/
  var $matchingpairs;

/**
* Type of matching question
*
* There are two possible types of matching questions: Matching terms and definitions (=1)
* and Matching terms and pictures (=0).
*
* @var integer
*/
  var $matching_type;

/**
* Points for solving the matching question
*
* Enter the number of points the user gets when he/she enters the correct matching pairs
* This value overrides the point values of single matching pairs when set different
* from zero.
*
* @var double
*/
  var $points;

/**
* ASS_MatchingQuestion constructor
*
* The constructor takes possible arguments an creates an instance of the ASS_MatchingQuestion object.
*
* @param string $title A title string to describe the question
* @param string $comment A comment string to describe the question
* @param string $author A string containing the name of the questions author
* @param integer $owner A numerical ID to identify the owner/creator
* @param string $materials An uri to additional materials
* @param string $question The question string of the matching question
* @param points double The points for solving the matching question
* @access public
*/
  function ASS_MatchingQuestion (
    $title = "",
    $comment = "",
    $author = "",
    $owner = -1,
    $materials = "",
    $question = "",
    $points = 0.0,
		$matching_type = MT_TERMS_DEFINITIONS
  )
  {
    $this->ASS_Question($title, $comment, $author, $owner, $materials);
    $this->matchingpairs = array();
    $this->question = $question;
    $this->points = $points;
		$this->matching_type = $matching_type;
  }

/**
* Saves a ASS_MatchingQuestion object to a database
*
* Saves a ASS_MatchingQuestion object to a database (experimental)
*
* @param object $db A pear DB object
* @access public
*/
  function save_to_db()
  {
    global $ilias;
    $db =& $ilias->db->db;
    
    if ($this->id == -1) {
      // Neuen Datensatz schreiben
      $id = $db->nextId('qpl_questions');
      $now = getdate();
      $question_type = 4;
      $created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
      $query = sprintf("INSERT INTO qpl_questions (question_id, question_type_fi, ref_fi, title, comment, author, owner, question_text, matching_type, points, materials, created, TIMESTAMP) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NULL)",
        $db->quote($id),
        $db->quote($question_type),
        $db->quote($this->ref_id),
        $db->quote($this->title),
        $db->quote($this->comment),
        $db->quote($this->author),
        $db->quote($this->owner),
        $db->quote($this->question),
				$db->quote($this->matching_type),
        $db->quote($this->points),
        $db->quote($this->materials),
        $db->quote($created)
      );
      $result = $db->query($query);
      if ($result == DB_OK) {
        $this->id = $id;
        // Falls die Frage in einen Test eingef�gt werden soll, auch diese Verbindung erstellen
        if ($this->get_test_id() > 0) {
          $this->insert_into_test($this->get_test_id());
        }
      }
    } else {
      // Vorhandenen Datensatz aktualisieren
      $query = sprintf("UPDATE qpl_questions SET title = %s, comment = %s, author = %s, question_text = %s, matching_type = %s, points = %s, materials = %s WHERE question_id = %s",
        $db->quote($this->title),
        $db->quote($this->comment),
        $db->quote($this->author),
        $db->quote($this->question),
				$db->quote($this->matching_type),
        $db->quote($this->points),
        $db->quote($this->materials),
        $db->quote($this->id)
      );
      $result = $db->query($query);
    }

    if ($result == DB_OK) {
      // Antworten schreiben
      // alte Antworten l�schen
      $query = sprintf("DELETE FROM qpl_answers WHERE question_fi = %s",
        $db->quote($this->id)
      );
      $result = $db->query($query);
      // Anworten wegschreiben
      foreach ($this->matchingpairs as $key => $value) {
        $matching_obj = $this->matchingpairs[$key];
        $query = sprintf("INSERT INTO qpl_answers (answer_id, question_fi, answertext, points, aorder, matchingtext, matching_order, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, %s, %s, NULL)",
          $db->quote($this->id),
          $db->quote($matching_obj->get_answertext()),
          $db->quote($matching_obj->get_points()),
          $db->quote($matching_obj->get_order()),
          $db->quote($matching_obj->get_matchingtext()),
          $db->quote($matching_obj->get_matchingtext_order())
        );
        $matching_result = $db->query($query);
      }
    }
  }

/**
* Loads a ASS_MatchingQuestion object from a database
*
* Loads a ASS_MatchingQuestion object from a database (experimental)
*
* @param object $db A pear DB object
* @param integer $question_id A unique key which defines the multiple choice test in the database
* @access public
*/
  function load_from_db($question_id)
  {
    global $ilias;
    $db =& $ilias->db->db;
    
    $query = sprintf("SELECT * FROM qpl_questions WHERE question_id = %s",
      $db->quote($question_id)
    );
    $result = $db->query($query);
    if (strcmp(get_class($result), db_result) == 0) {
      if ($result->numRows() == 1) {
        $data = $result->fetchRow(DB_FETCHMODE_OBJECT);
        $this->id = $question_id;
        $this->title = $data->title;
        $this->comment = $data->comment;
        $this->author = $data->author;
        $this->ref_id = $data->ref_fi;
        $this->owner = $data->owner;
				$this->matching_type = $data->matching_type;
        $this->question = $data->question_text;
        $this->points = $data->points;
        $this->materials = $data->materials;
      }
      $query = sprintf("SELECT * FROM qpl_answers WHERE question_fi = %s ORDER BY answer_id ASC",
        $db->quote($question_id)
      );
      $result = $db->query($query);
      if (strcmp(get_class($result), db_result) == 0) {
        while ($data = $result->fetchRow(DB_FETCHMODE_OBJECT)) {
          array_push($this->matchingpairs, new ASS_AnswerMatching($data->answertext, $data->points, $data->aorder, $data->matchingtext, $data->matching_order));
        }
      }
    }
  }

/**
* Sets the matching question text
*
* Sets the matching question text
*
* @param string $question The question text
* @access public
* @see $question
*/
  function set_question($question = "") {
    $this->question = $question;
  }

/**
* Sets the matching question type
*
* Sets the matching question type
*
* @param integer $matching_type The question matching type
* @access public
* @see $matching_type
*/
  function set_matching_type($matching_type = MT_TERMS_DEFINITIONS) {
    $this->matching_type = $matching_type;
  }

/**
* Returns the question text
*
* Returns the question text
*
* @return string The question text string
* @access public
* @see $question
*/
  function get_question() {
    return $this->question;
  }

/**
* Returns the matching question type
*
* Returns the matching question type
*
* @return integer The matching question type
* @access public
* @see $matching_type
*/
  function get_matching_type() {
    return $this->matching_type;
  }

/**
* Adds an matching pair for an matching question
*
* Adds an matching pair for an matching choice question. The students have to fill in an order for the matching pair.
* The matching pair is an ASS_AnswerMatching object that will be created and assigned to the array $this->matchingpairs.
*
* @param string $answertext The answer text
* @param string $matchingtext The matching text of the answer text
* @param double $points The points for selecting the matching pair (even negative points can be used)
* @param integer $order A possible display order of the matching pair
* @access public
* @see $matchingpairs
* @see ASS_AnswerMatching
*/
  function add_matchingpair(
    $answertext = "",
    $matchingtext = "",
    $points = 0.0,
    $order = 0,
    $matching_order = 0
  )
  {
    if ($order > 0) {
      $random_number_answertext = $order;
    } else {
      $random_number_answertext = $this->get_random_id("answer");
    }
    if ($matching_order > 0) {
      $random_number_matchingtext = $matching_order;
    } else {
      $random_number_matchingtext = $this->get_random_id("matching");
    }
    // Anwort anh�ngen
    $matchingpair = new ASS_AnswerMatching($answertext, $points, $random_number_answertext, $matchingtext, $random_number_matchingtext);
    array_push($this->matchingpairs, $matchingpair);
  }
  
  function get_random_id($type = "answer") {
    if (strcmp($type, "answer") == 0) {
      $random_number_answertext = mt_rand(1, 100000);
      $found = FALSE;
      while ($found) {
        $found = FALSE;
        foreach ($this->matchingpairs as $key => $value) {
          if ($value->get_order() == $random_number_answertext) {
            $found = TRUE;
            $random_number_answertext++;
          }
        }
      }
      return $random_number_answertext;
    } else {
      $random_number_matchingtext = mt_rand(1, 100000);
      $found = FALSE;
      while ($found) {
        $found = FALSE;
        foreach ($this->matchingpairs as $key => $value) {
          if ($value->get_order() == $random_number_matchingtext) {
            $found = TRUE;
            $random_number_matchingtext++;
          }
        }
      }
      return $random_number_matchingtext;
    }
  }

/**
* Returns a matching pair
*
* Returns a matching pair with a given index. The index of the first
* matching pair is 0, the index of the second matching pair is 1 and so on.
*
* @param integer $index A nonnegative index of the n-th matching pair
* @return object ASS_AnswerMatching-Object
* @access public
* @see $matchingpairs
*/
  function get_matchingpair($index = 0) {
    if ($index < 0) return NULL;
    if (count($this->matchingpairs) < 1) return NULL;
    if ($index >= count($this->matchingpairs)) return NULL;
    return $this->matchingpairs[$index];
  }

/**
* Deletes a matching pair
*
* Deletes a matching pair with a given index. The index of the first
* matching pair is 0, the index of the second matching pair is 1 and so on.
*
* @param integer $index A nonnegative index of the n-th matching pair
* @access public
* @see $matchingpairs
*/
  function delete_matchingpair($index = 0) {
    if ($index < 0) return;
    if (count($this->matchingpairs) < 1) return;
    if ($index >= count($this->matchingpairs)) return;
    unset($this->matchingpairs[$index]);
    $this->matchingpairs = array_values($this->matchingpairs);
  }

/**
* Deletes all matching pairs
* 
* Deletes all matching pairs
*
* @access public
* @see $matchingpairs
*/
  function flush_matchingpairs() {
    $this->matchingpairs = array();
  }

/**
* Returns the number of matching pairs
*
* Returns the number of matching pairs
*
* @return integer The number of matching pairs of the matching question
* @access public
* @see $matchingpairs
*/
  function get_matchingpair_count() {
    return count($this->matchingpairs);
  }

/**
* Gets the points
*
* Gets the points for entering the correct order of the ASS_MatchingQuestion object
*
* @return double The points for entering the correct order of the matching question
* @access public
* @see $points
*/
  function get_points() {
    return $this->points;
  }

/**
* Sets the points
*
* Sets the points for entering the correct order of the ASS_MatchingQuestion object
*
* @param points double The points for entering the correct order of the matching question
* @access public
* @see $points
*/
  function set_points($points = 0.0) {
    $this->points = $points;
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
  function get_reached_points($user_id, $test_id) {
    $found_value1 = array();
    $found_value2 = array();
    $query = sprintf("SELECT * FROM tst_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",
      $this->ilias->db->db->quote($user_id),
      $this->ilias->db->db->quote($test_id),
      $this->ilias->db->db->quote($this->get_id())
    );
    $result = $this->ilias->db->query($query);
    while ($data = $result->fetchRow(DB_FETCHMODE_OBJECT)) {
      array_push($found_value1, $data->value1);
      array_push($found_value2, $data->value2);
    }
    $points = 0;
    foreach ($found_value1 as $key => $value) {
      foreach ($this->matchingpairs as $answer_key => $answer_value) {
        if (($answer_value->get_order() == $value) and ($answer_value->get_matchingtext_order() == $found_value2[$key])) {
					$points += $answer_value->get_points();
        }
      }
    }
    return $points;
  }
  
/**
* Returns the maximum points, a learner can reach answering the question
* 
* Returns the maximum points, a learner can reach answering the question
*
* @access public
* @see $points
*/
  function get_maximum_points() {
		$points = 0;
		foreach ($this->matchingpairs as $key => $value) {
			if ($value->get_points() > 0) {
				$points += $value->get_points();
			}
		}
    return $points;
  }
  
/**
* Sets the image file
*
* Sets the image file and uploads the image to the object's image directory.
*
* @param string $image_filename Name of the original image file
* @param string $image_tempfilename Name of the temporary uploaded image file
* @access public
*/
  function set_image_file($image_filename, $image_tempfilename = "") {
 		
		if (!empty($image_tempfilename)) {
			$imagepath = $this->get_image_path();
			if (!file_exists($imagepath)) {
				ilUtil::makeDirParents($imagepath);
			}
			if (!move_uploaded_file($image_tempfilename, $imagepath . $image_filename)) {
				print "image not uploaded!!!! ";
			} else {
				// create thumbnail file
				$size = 100;
				$thumbpath = $imagepath . $image_filename . "." . "thumb.jpg";
				$convert_cmd = ilUtil::getConvertCmd() . " $imagepath$image_filename -resize $sizex$size $thumbpath";
				system($convert_cmd);
			}
		}
  }

/**
* Saves the learners input of the question to the database
* 
* Saves the learners input of the question to the database
*
* @param integer $test_id The database id of the test containing this question
* @access public
* @see $answers
*/
  function save_working_data($test_id, $limit_to = LIMIT_NO_LIMIT) {
    global $ilDB;
		global $ilUser;
    $db =& $ilDB->db;

    $query = sprintf("DELETE FROM tst_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",
      $db->quote($ilUser->id),
      $db->quote($test_id),
      $db->quote($this->get_id())
    );
    $result = $db->query($query);

    foreach ($_POST as $key => $value) {
      if (preg_match("/sel_matching_(\d+)/", $key, $matches)) {
        $query = sprintf("INSERT INTO tst_solutions (solution_id, user_fi, test_fi, question_fi, value1, value2, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, %s, NULL)",
          $db->quote($ilUser->id),
          $db->quote($test_id),
          $db->quote($this->get_id()),
          $db->quote($value),
          $db->quote($matches[1])
        );
        $result = $db->query($query);
      }
    }
//    parent::save_working_data($limit_to);
  }
  
}

?>