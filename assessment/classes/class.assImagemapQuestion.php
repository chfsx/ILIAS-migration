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
require_once "class.assAnswerImagemap.php";

/**
* Class for image map questions
*
* ASS_ImagemapQuestion is a class for imagemap question.
*
* @author		Muzaffar Altaf <maltaf@tzi.de>
* @version	$Id$
* @module   class.assImagemapQuestion.php
* @modulegroup   Assessment
*/
class ASS_ImagemapQuestion extends ASS_Question {

/**
* The imagemap_Question containing the question
*
* The imagemap_Question containing the question.
*
* @var string
*/
  var $question;

/**
* The possible answers of the imagemap question
*
* $answers is an array of the predefined answers of the imagemap question
*
* @var array
*/
  var $answers;

/**
* Points for solving the imagemap question
*
* Enter the number of points the user gets when he/she solves the imagemap
* question. This value overrides the point values of single answers when set different
* from zero.
*
* @var double
*/
  var $points;

/**
* The imagemap file containing the name of imagemap file
*
* The imagemap file containing the name of imagemap file
*
* @var string
*/
  var $imagemap_filename;

/**
* The image file containing the name of image file
*
* The image file containing the name of image file
*
* @var string
*/
  var $image_filename;

/**
* The variable containing path of the file to be uploaded
*
* The variable containing path of the file to be uploaded
*
* @var string
*/
  var $image_path='./data/image/';

/**
* The variable containing path of the imagemap to be uploaded
*
* The variable containing path of the imagemap to be uploaded
*
* @var string
*/
  var $imagemap_path='./data/imagemap/';

/**
* The variable containing contents of an imagemap file
*
* The variable containing contents of an imagemap file
*
* @var string
*/
  var $imagemap_contents;


/**
* ASS_ImagemapQuestion constructor
*
* The constructor takes possible arguments an creates an instance of the ASS_ImagemapQuestion object.
*
* @param string $title A title string to describe the question
* @param string $comment A comment string to describe the question
* @param string $author A string containing the name of the questions author
* @param integer $owner A numerical ID to identify the owner/creator
* @param boolean $private Defines the privacy status
* @param boolean $free_use Defines the usage status
* @param boolean $copyable Defines the copy status
* @param string $imagemap_file The imagemap file name of the imagemap question
* @param string $image_file The image file name of the imagemap question
* @param string $question The question string of the imagemap question
* @param string $materials An uri to additional materials
* @access public
*/
  function ASS_ImagemapQuestion(
    $title = "",
    $comment = "",
    $author = "",
    $owner = -1,
    $private = FALSE,
    $free_use = TRUE,
    $copyable = TRUE,
    $question = "",
    $imagemap_filename = "",
    $image_filename = "",
    $materials = ""

  )
  {
    $this->ASS_Question($title, $comment, $author, $owner, $private, $free_use, $copyable, $materials);
    $this->question = $question;
    $this->imagemap_filename = $imagemap_filename;
    $this->image_filename = $image_filename;
    $this->answers = array();
	$this->points = $points;
  }

/**
* Saves a ASS_ImagemapQuestion object to a database
*
* Saves a ASS_ImagemapQuestion object to a database (experimental)
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
      $question_type = 6;
      $created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
      $query = sprintf("INSERT INTO qpl_questions (question_id, question_type_fi, ref_fi, title, comment, author, owner, question_text, points, imagemap_file, image_file, materials, created, TIMESTAMP) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NULL)",
        $db->quote($id),
        $db->quote($question_type),
        $db->quote($this->ref_id),
        $db->quote($this->title),
        $db->quote($this->comment),
        $db->quote($this->author),
        $db->quote($this->owner),
        $db->quote($this->question),
        $db->quote($this->points),
        $db->quote($this->imagemap_filename),
        $db->quote($this->image_filename),
        $db->quote($this->materials),
        $db->quote($created)
      );
      //print "id:".$id." question type:".$question_type." this->title:".$this->title." this->comment:".$this->comment." this->author:".$this->author." this->owner:".$this->owner." this->private:".$this->private." this->free_use:".$this->free_use." this->copyable:".$this->copyable." this->question:".$this->question." this->points:".$this->points." this->imagemap_file:".$this->imagemap_filename." this->image_file:".$this->image_filename." this->materials:".$this->materials;
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
      $query = sprintf("UPDATE qpl_questions SET title = %s, comment = %s, author = %s, question_text = %s, points = %s, imagemap_file = %s, image_file = %s, materials = %s WHERE question_id = %s",
        $db->quote($this->title),
        $db->quote($this->comment),
        $db->quote($this->author),
        $db->quote($this->question),
        $db->quote($this->points),
        $db->quote($this->imagemap_filename),
        $db->quote($this->image_filename),
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
        //print "id:".$this->id." answer tex:".$answer_obj->get_answertext()." answer_obj->get_order():".$answer_obj->get_order()." answer_obj->get_coords():".$answer_obj->get_coords()." answer_obj->get_area():".$answer_obj->get_area();
        $query = sprintf("INSERT INTO qpl_answers (answer_id, question_fi, answertext, points, `order`, correctness, coords, area, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, %s, %s, %s, NULL)",
          $db->quote($this->id),
          $db->quote($answer_obj->get_answertext()),
          $db->quote($answer_obj->get_points()),
          $db->quote($answer_obj->get_order()),
          $db->quote($answer_obj->get_correctness()),
          $db->quote($answer_obj->get_coords()),
          $db->quote($answer_obj->get_area())
        );
        $answer_result = $db->query($query);
      }
    }
  }

/**
* Loads a ASS_ImagemapQuestion object from a database
*
* Loads a ASS_ImagemapQuestion object from a database (experimental)
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
				$this->ref_id = $data->ref_fi;
        $this->title = $data->title;
        $this->comment = $data->comment;
        $this->author = $data->author;
        $this->owner = $data->owner;
        $this->question = $data->question_text;
        $this->imagemap_filename = $data->imagemap_file;
        $this->image_filename = $data->image_file;
        $this->points = $data->points;
        $this->materials = $data->materials;
      }
      $query = sprintf("SELECT * FROM qpl_answers WHERE question_fi = %s ORDER BY `order` ASC",
        $db->quote($question_id)
      );
      $result = $db->query($query);
      if (strcmp(get_class($result), db_result) == 0) {
        while ($data = $result->fetchRow(DB_FETCHMODE_OBJECT)) {
          array_push($this->answers, new ASS_AnswerImagemap($data->answertext, $data->points, $data->order, $data->correctness, $data->coords, $data->area));
        }
      }
    }
  }

/**
* Gets the imagemap question
*
* Gets the question string of the ASS_ImagemapQuestion object
*
* @return string The question string of the ASS_ImagemapQuestion object
* @access public
* @see $question
*/
  function get_question() {
    return $this->question;
  }

/**
* Sets the imagemap question
*
* Sets the question string of the ASS_ImagemapQuestion object
*
* @param string $question A string containing the imagemap question
* @access public
* @see $question
*/
  function set_question($question = "") {
    $this->question = $question;
  }

/**
* Gets the imagemap file name
*
* Gets the imagemap file name
*
* @return string The imagemap file of the ASS_ImagemapQuestion object
* @access public
* @see $imagemap_filename
*/
  function get_imagemap_filename() {
    return $this->imagemap_filename;
  }

/**
* Sets the imagemap file name
*
* Sets the imagemap file name
*
* @param string $imagemap_file.
* @access public
* @see $imagemap_filename
*/
  function set_imagemap_filename($imagemap_filename, $imagemap_tempfilename = "") {
    if (!empty($imagemap_filename)) {
      $this->imagemap_filename = $imagemap_filename;
    }
    if (!empty($imagemap_tempfilename)) {
 	    $fp = fopen($imagemap_tempfilename, "r");
 	    $contents = fread($fp, filesize($imagemap_tempfilename));
      fclose($fp);
			$this->flush_answers();
			if (preg_match_all("/<area(.+)>/siU", $contents, $matches)) {
		  	for ($i=0; $i< count($matches[1]); $i++) {
		    	preg_match("/alt\s*=\s*\"(.+)\"\s*/siU", $matches[1][$i], $alt);
		    	preg_match("/coords\s*=\s*\"(.+)\"\s*/siU", $matches[1][$i], $coords);
		    	preg_match("/shape\s*=\s*\"(.+)\"\s*/siU", $matches[1][$i], $shape);
					$this->add_answer($alt[1], 0.0, FALSE, $i, $coords[1], $shape[1]);
		  	}
			}
    }
	}

/**
* Gets the image file name
*
* Gets the image file name
*
* @return string The image file name of the ASS_ImagemapQuestion object
* @access public
* @see $image_filename
*/
  function get_image_filename() {
    return $this->image_filename;
  }

/**
* Sets the image file name
*
* Sets the image file name
*
* @param string $image_file name.
* @access public
* @see $image_filename
*/
  function set_image_filename($image_filename, $image_tempfilename = "") {
 		
    if (!empty($image_filename)) {
      $this->image_filename = $image_filename;
    }
		if (!empty($image_tempfilename)) {
			$webspace_dir = CLIENT_WEB_DIR;
			$object_dir = "/assessment/$this->ref_id/images/";
			if (!file_exists($webspace_dir . $object_dir)) {
				ilUtil::makeDir_parents($webspace_dir . $object_dir);
			}
			if (!move_uploaded_file($image_tempfilename, $webspace_dir . $object_dir . "/" . $image_filename)) {
				print "image not uploaded!!!! ";
			}
		}
  }

/**
* Gets the imagemap file contents
*
* Gets the imagemap file contents
*
* @return string The imagemap file contents of the ASS_ImagemapQuestion object
* @access public
* @see $imagemap_contents
*/
  function get_imagemap_contents() {
	$imagemap_contents = "<map name=\"".$this->title."\"> ";
	for ($i = 0; $i < count($this->answers); $i++) {
	 $imagemap_contents .= "<area alt=\"".$this->answers[$i]->get_answertext()."\" ";
	 $imagemap_contents .= "shape=\"".$this->answers[$i]->get_area()."\" ";
	 $imagemap_contents .= "coords=\"".$this->answers[$i]->get_coords()."\" ";
	 $imagemap_contents .= "href=\"il_as_question_manager.php\"> ";
	}
	$imagemap_contents .= "</map>";
    return $imagemap_contents;
  }

/**
* Gets the points
*
* Gets the points for solving the question of the ASS_ImagemapQuestion object
*
* @return double The points for solving the imagemap question
* @access public
* @see $points
*/
  function get_points() {
    return $this->points;
  }

/**
* Sets the points
*
* Sets the points for solving the question of the ASS_ImagemapQuestion object
*
* @param points double The points for solving the imagemap question
* @access public
* @see $points
*/
  function set_points($points = 0.0) {
    $this->points = $points;
  }

/**
* Adds a possible answer for a imagemap question
*
* Adds a possible answer for a imagemap question. A ASS_AnswerImagemap object will be
* created and assigned to the array $this->answers.
*
* @param string $answertext The answer text
* @param double $points The points for selecting the answer (even negative points can be used)
* @param boolean $correctness Defines the answer as correct (TRUE) or incorrect (FALSE)
* @param integer $order A possible display order of the answer
* @access public
* @see $answers
* @see ASS_AnswerImagemap
*/
  function add_answer(
    $answertext = "",
    $points = 0.0,
    $correctness = FALSE,
    $order = 0,
    $coords="",
    $area=""   
  )
  {
    if (array_key_exists($order, $this->answers)) {
      // Antwort einf�gen
      $answer = new ASS_AnswerImagemap($answertext, $points, $found, $correctness, $coords, $area);
			for ($i = count($this->answers) - 1; $i >= $order; $i--) {
				$this->answers[$i+1] = $this->answers[$i];
				$this->answers[$i+1]->set_order($i+1);
			}
			$this->answers[$order] = $answer;
    } else {
      // Anwort anh�ngen
      $answer = new ASS_AnswerImagemap($answertext, $points, count($this->answers), $correctness, $coords, $area);
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
* @return object ASS_AnswerImagemap-Object containing the answer
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
    if ($this->response == RESPONSE_SINGLE) {
      return $this->points;
    } else {
      $points = 0;
      foreach ($this->answers as $key => $value) {
        $points += $value->get_points();
      }
      return $points;
    }
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
    $query = sprintf("SELECT * FROM dum_assessment_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",
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
* @access public
* @see $answers
*/
  function save_working_data($limit_to = LIMIT_NO_LIMIT) {
    global $ilias;
    $db =& $ilias->db->db;

    $query = sprintf("DELETE FROM dum_assessment_solutions WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",
      $db->quote($this->ilias->account->id),
      $db->quote($_GET["test"]),
      $db->quote($this->get_id())
    );
    $result = $db->query($query);

      foreach ($_POST as $key => $value) {
        if (preg_match("/imagemap_(\d+)/", $key, $matches)) {
          $query = sprintf("INSERT INTO dum_assessment_solutions (solution_id, user_fi, test_fi, question_fi, value1, value2, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, NULL, NULL)",
            $db->quote($this->ilias->account->id),
            $db->quote($_GET["test"]),
            $db->quote($this->get_id()),
            $db->quote($value)
          );
          $result = $db->query($query);
        }
      }
    parent::save_working_data($limit_to);
  }
}

?>