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
require_once "class.assAnswerTrueFalse.php";

define("RESPONSE_SINGLE", "0");
define("RESPONSE_MULTIPLE", "1");

define("OUTPUT_ORDER", "0");
define("OUTPUT_RANDOM", "1");

/**
* Class for multiple choice tests
* 
* ASS_MultipleChoice is a class for multiple choice tests. It
* supports single and multiple response.
*
* @author		Helmut Schottm�ller <hschottm@tzi.de>
* @version	$Id$
* @module   class.assMultipleChoice.php
* @modulegroup   Assessment
*/
class ASS_MultipleChoice extends ASS_Question {
/**
* Question string
* 
* The question string of the multiple choice question
*
* @var string
*/
  var $question;
/**
* The given answers of the multiple choice question
* 
* $answers is an array of the given answers of the multiple choice question
*
* @var array
*/
  var $answers;
/**
* Response type
* 
* This is the response type of the multiple choice question. You can select
* RESPONSE_SINGLE (=0) or RESPONSE_MULTI (=1).
*
* @var integer
*/
  var $response;
/**
* Output type
* 
* This is the output type for the answers of the multiple choice question. You can select
* OUTPUT_ORDER(=0) or OUTPUT_RANDOM (=1). The default output type is OUTPUT_ORDER
*
* @var integer
*/
  var $output_type;
  
/**
* ASS_MultipleChoice constructor
* 
* The constructor takes possible arguments an creates an instance of the ASS_MultipleChoice object.
*
* @param string $title A title string to describe the question
* @param string $comment A comment string to describe the question
* @param string $author A string containing the name of the questions author
* @param integer $owner A numerical ID to identify the owner/creator
* @param string $materials An uri to additional materials
* @param string $question The question string of the multiple choice question
* @param integer $response Indicates the response type of the multiple choice question
* @param integer $output_type The output order of the multiple choice answers
* @access public
* @see ASS_Question:ASS_Question()
*/
  function ASS_MultipleChoice(
    $title = "", 
    $comment = "",
    $author = "",
    $owner = -1,
    $materials = "",
    $question = "",
    $response = RESPONSE_SINGLE,
    $output_type = OUTPUT_ORDER
  )
  {
    $this->ASS_Question($title, $comment, $author, $owner, $materials);
    $this->question = $question;
    $this->response = $response;
    $this->output_type = $output_type;
    $this->answers = array();
  }

/**
* Saves a ASS_MultipleChoice object to a database
* 
* Saves a ASS_MultipleChoice object to a database (experimental)
*
* @param object $db A pear DB object
* @access public
*/
  function save_to_db()
  {
    global $ilias;
    
    $db = & $ilias->db->db;
    if ($this->id == -1) {
      // Neuen Datensatz schreiben
      $id = $db->nextId('qpl_questions');
      $now = getdate();
      if ($this->response == RESPONSE_SINGLE) {
        $question_type = 1;
      } else {
        $question_type = 2;
      }
      $created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
      $query = sprintf("INSERT INTO qpl_questions (question_id, question_type_fi, ref_fi, title, comment, author, owner, question_text, choice_response, materials, created, TIMESTAMP) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NULL)",
        $db->quote($id), 
        $db->quote($question_type),
        $db->quote($this->ref_id),
        $db->quote($this->title), 
        $db->quote($this->comment), 
        $db->quote($this->author), 
        $db->quote($this->owner), 
        $db->quote($this->question), 
        $db->quote($this->response),
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
      $query = sprintf("UPDATE qpl_questions SET title = %s, comment = %s, author = %s, question_text = %s, choice_response = %s, materials = %s WHERE question_id = %s",
        $db->quote($this->title), 
        $db->quote($this->comment), 
        $db->quote($this->author), 
        $db->quote($this->question), 
        $db->quote($this->response), 
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
      foreach ($this->answers as $key => $value) {
        $answer_obj = $this->answers[$key];
        $query = sprintf("INSERT INTO qpl_answers (answer_id, question_fi, answertext, points, aorder, correctness, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, %s, NULL)",
          $db->quote($this->id),
          $db->quote($answer_obj->get_answertext()),
          $db->quote($answer_obj->get_points()),
          $db->quote($answer_obj->get_order()),
          $db->quote($answer_obj->get_correctness())
        );
        $answer_result = $db->query($query);
      }
    }
  }

/**
* Loads a ASS_MultipleChoice object from a database
* 
* Loads a ASS_MultipleChoice object from a database (experimental)
*
* @param object $db A pear DB object
* @param integer $question_id A unique key which defines the multiple choice test in the database
* @access public
*/
  function load_from_db($question_id)
  {
    global $ilias;
    
    $db = & $ilias->db->db;
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
        $this->ref_id = $data->ref_fi;
        $this->author = $data->author;
        $this->owner = $data->owner;
        $this->question = $data->question_text;
        $this->response = $data->choice_response;
        $this->materials = $data->materials;
      }
      $query = sprintf("SELECT * FROM qpl_answers WHERE question_fi = %s ORDER BY aorder ASC",
        $db->quote($question_id)
      );
      $result = $db->query($query);
      if (strcmp(get_class($result), db_result) == 0) {
        while ($data = $result->fetchRow(DB_FETCHMODE_OBJECT)) {
          array_push($this->answers, new ASS_AnswerTrueFalse($data->answertext, $data->points, $data->aorder, $data->correctness));
        }
      }
    }
  }

/**
* Gets the multiple choice question
* 
* Gets the question string of the ASS_MultipleChoice object
*
* @return string The question string of the ASS_MultipleChoice object
* @access public
* @see $question
*/
  function get_question() {
    return $this->question;
  }

/**
* Sets the multiple choice question
* 
* Sets the question string of the ASS_MultipleChoice object
*
* @param string $question A string containing the multiple choice question
* @access public
* @see $question
*/
  function set_question($question = "") {
    $this->question = $question;
  }

/**
* Gets the multiple choice response type
* 
* Gets the multiple choice response type which is either RESPONSE_SINGLE (=0) or RESPONSE_MULTI (=1).
*
* @return integer The response type of the ASS_MultipleChoice object
* @access public
* @see $response
*/
  function get_response() {
    return $this->response;
  }

/**
* Sets the multiple choice response type
* 
* Sets the response type of the ASS_MultipleChoice object
*
* @param integer $response A nonnegative integer value specifying the response type. It is RESPONSE_SINGLE (=0) or RESPONSE_MULTI (=1).
* @access public
* @see $response
*/
  function set_response($response = "") {
    $this->response = $response;
  }

/**
* Gets the multiple choice output type
* 
* Gets the multiple choice output type which is either OUTPUT_ORDER (=0) or OUTPUT_RANDOM (=1).
*
* @return integer The output type of the ASS_MultipleChoice object
* @access public
* @see $output_type
*/
  function get_output_type() {
    return $this->output_type;
  }

/**
* Sets the multiple choice output type
* 
* Sets the output type of the ASS_MultipleChoice object
*
* @param integer $output_type A nonnegative integer value specifying the output type. It is OUTPUT_ORDER (=0) or OUTPUT_RANDOM (=1).
* @access public
* @see $response
*/
  function set_output_type($output_type = OUTPUT_ORDER) {
    $this->output_type = $output_type;
  }

/**
* Adds a possible answer for a multiple choice question
* 
* Adds a possible answer for a multiple choice question. A ASS_AnswerTrueFalse object will be
* created and assigned to the array $this->answers.
*
* @param string $answertext The answer text
* @param double $points The points for selecting the answer (even negative points can be used)
* @param boolean $correctness Defines the answer as correct (TRUE) or incorrect (FALSE)
* @param integer $order A possible display order of the answer
* @access public
* @see $answers
* @see ASS_AnswerTrueFalse
*/
  function add_answer(
    $answertext = "",
    $points = 0.0,
    $correctness = FALSE,
    $order = 0
  )
  {
    $found = -1;
    foreach ($this->answers as $key => $value) {
      if ($value->get_order() == $order) {
        $found = $order;
      }
    }
    if ($found >= 0) {
      // Antwort einf�gen
      $answer = new ASS_AnswerTrueFalse($answertext, $points, $found, $correctness);
      array_push($this->answers, $answer);
      for ($i = $found + 1; $i < count($this->answers); $i++) {
        $this->answers[$i] = $this->answers[$i-1];
      }
      $this->answers[$found] = $answer;
    } else {
      // Anwort anh�ngen
      $answer = new ASS_AnswerTrueFalse($answertext, $points, count($this->answers), $correctness);
      array_push($this->answers, $answer);
    }
  }
  
/**
* Returns the number of answers
* 
* Returns the number of answers
*
* @return integer The number of answers of the multiple choice question
* @access public
* @see $answers
*/
  function get_answer_count() {
    return count($this->answers);
  }
  
/**
* Returns an answer
* 
* Returns an answer with a given index. The index of the first
* answer is 0, the index of the second answer is 1 and so on.
*
* @param integer $index A nonnegative index of the n-th answer
* @return object ASS_AnswerTrueFalse-Object containing the answer
* @access public
* @see $answers
*/
  function get_answer($index = 0) {
    if ($index < 0) return NULL;
    if (count($this->answers) < 1) return NULL;
    if ($index >= count($this->answers)) return NULL;
    return $this->answers[$index];
  }
  
/**
* Deletes an answer
* 
* Deletes an answer with a given index. The index of the first
* answer is 0, the index of the second answer is 1 and so on.
*
* @param integer $index A nonnegative index of the n-th answer
* @access public
* @see $answers
*/
  function delete_answer($index = 0) {
    if ($index < 0) return;
    if (count($this->answers) < 1) return;
    if ($index >= count($this->answers)) return;
    unset($this->answers[$index]);
    $this->answers = array_values($this->answers);
    for ($i = 0; $i < count($this->answers); $i++) {
      if ($this->answers[$i]->get_order() > $index) {
        $this->answers[$i]->set_order($i);
      }
    }
  }
  
/**
* Deletes all answers
* 
* Deletes all answers
*
* @access public
* @see $answers
*/
  function flush_answers() {
    $this->answers = array();
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
    foreach ($this->answers as $key => $value) {
			if ($value->is_true()) {
	      $points += $value->get_points();
			}
    }
    return $points;
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
    $found_values = array();
    $query = sprintf("SELECT * FROM tst_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",
      $this->ilias->db->db->quote($user_id),
      $this->ilias->db->db->quote($test_id),
      $this->ilias->db->db->quote($this->get_id())
    );
    $result = $this->ilias->db->query($query);
    while ($data = $result->fetchRow(DB_FETCHMODE_OBJECT)) {
      array_push($found_values, $data->value1);
    }
    $points = 0;
    foreach ($found_values as $key => $value) {
      if (strlen($value) > 0) {
        if ($this->answers[$value]->is_true()) {
          $points += $this->answers[$value]->get_points();
        }
      }
    }
    return $points;
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

    if ($this->response == RESPONSE_SINGLE) {
			$query = sprintf("SELECT * FROM tst_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",    
				$db->quote($ilUser->id),
				$db->quote($test_id),
				$db->quote($this->get_id())
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
					$db->quote($this->get_id()),
					$db->quote($_POST["multiple_choice_result"])
				);
			}
      $result = $db->query($query);
    } else {
			$query = sprintf("DELETE FROM tst_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",    
				$db->quote($ilUser->id),
				$db->quote($test_id),
				$db->quote($this->get_id())
			);
			$result = $db->query($query);
      foreach ($_POST as $key => $value) {
        if (preg_match("/multiple_choice_result_(\d+)/", $key, $matches)) {
					$query = sprintf("INSERT INTO tst_solutions (solution_id, user_fi, test_fi, question_fi, value1, value2, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, NULL, NULL)",
						$db->quote($ilUser->id),
						$db->quote($test_id),
						$db->quote($this->get_id()),
						$db->quote($value)
					);
          $result = $db->query($query);
        }
      }
    }
    //parent::save_working_data($limit_to);
  }
}

?>