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

require_once "PEAR.php";

define("LIMIT_NO_LIMIT", 0);
define("LIMIT_TIME_ONLY", 1);

/**
* Basic class for all assessment question types
* 
* The ASS_Question class defines and encapsulates basic methods and attributes 
* for assessment question types to be used for all parent classes.
*
* @author		Helmut Schottmüller <hschottm@tzi.de>
* @version	$Id$
* @module   class.assQuestion.php
* @modulegroup   Assessment
*/
class ASS_Question extends PEAR {
/**
* Question id
* 
* A unique question id
*
* @var integer
*/
  var $id;

/**
* Question title
* 
* A title string to describe the question
*
* @var string
*/
  var $title;
/**
* Question comment
* 
* A comment string to describe the question more detailed as the title
*
* @var string
*/
  var $comment;
/**
* Question owner/creator
* 
* A unique positive numerical ID which identifies the owner/creator of the question.
* This can be a primary key from a database table for example.
*
* @var integer
*/
  var $owner;
/**
* Contains the name of the author
* 
* A text representation of the authors name. The name of the author must 
* not necessary be the name of the owner.
*
* @var string
*/
  var $author;

/**
* Contains an uri to additional materials
* 
* Contains an uri to additional materials
*
* @var string
*/
  var $materials;

/**
* The database id of a test in which the question is contained
* 
* The database id of a test in which the question is contained
*
* @var integer
*/
  var $test_id;

/**
* Reference id of the container object
* 
* Reference id of the container object
*
* @var double
*/
  var $ref_id;

/**
* The reference to the ILIAS class
* 
* The reference to the ILIAS class
*
* @var object
*/
  var $ilias;

/**
* The reference to the Template class
* 
* The reference to the Template class
*
* @var object
*/
  var $tpl;

/**
* The reference to the Language class
* 
* The reference to the Language class
*
* @var object
*/
  var $lng;
  
/**
* ASS_Question constructor
* 
* The constructor takes possible arguments an creates an instance of the ASS_Question object.
*
* @param string $title A title string to describe the question
* @param string $comment A comment string to describe the question
* @param string $author A string containing the name of the questions author
* @param integer $owner A numerical ID to identify the owner/creator
* @access public
*/
  function ASS_Question(
    $title = "", 
    $comment = "",
    $author = "",
    $owner = -1,
    $materials = ""
  ) 
  
  {
		global $ilias;
    global $lng;
    global $tpl;

		$this->ilias =& $ilias;
    $this->lng =& $lng;
    $this->tpl =& $tpl;
    
    $this->title = $title;
    $this->comment = $comment;
    $this->author = $author;
    if (!$this->author) {
      $this->author = $this->ilias->account->fullname;
    }
    $this->owner = $owner;
    if ($this->owner == -1) {
      $this->owner = $this->ilias->account->id;
    }
    $this->id = -1;
    $this->test_id = -1;
    $this->materials = $materials;
  }
  
/**
* Returns TRUE if the question title exists in the database
* 
* Returns TRUE if the question title exists in the database
*
* @param string $title The title of the question
* @return boolean The result of the title check
* @access public
*/
  function question_title_exists($title) {
    $query = sprintf("SELECT * FROM qpl_questions WHERE title = %s",
      $this->ilias->db->db->quote($title)
    );
    $result = $this->ilias->db->query($query);
    if (strcmp(get_class($result), db_result) == 0) {
      if ($result->numRows() == 1) {
        return TRUE;
      }
    }
    return FALSE;
  }
  
/**
* Sets the title string
* 
* Sets the title string of the ASS_Question object
*
* @param string $title A title string to describe the question
* @access public
* @see $title
*/
  function set_title($title = "") {
    $this->title = $title;
  }

/**
* Sets the id
* 
* Sets the id of the ASS_Question object
*
* @param integer $id A unique integer value
* @access public
* @see $id
*/
  function set_id($id = -1) {
    $this->id = $id;
  }

/**
* Sets the test id
* 
* Sets the test id of the ASS_Question object
*
* @param integer $id A unique integer value
* @access public
* @see $test_id
*/
  function set_test_id($id = -1) {
    $this->test_id = $id;
  }

/**
* Sets the comment
* 
* Sets the comment string of the ASS_Question object
*
* @param string $comment A comment string to describe the question
* @access public
* @see $comment
*/
  function set_comment($comment = "") {
    $this->comment = $comment;
  }

/**
* Sets the materials uri
* 
* Sets the materials uri
*
* @param string $materials An uri to additional materials
* @access public
* @see $materials
*/
  function set_materials($materials = "") {
    $this->materials = $materials;
  }

/**
* Sets the authors name
* 
* Sets the authors name of the ASS_Question object
*
* @param string $author A string containing the name of the questions author
* @access public
* @see $author
*/
  function set_author($author = "") {
    if (!$author) {
      $author = $this->ilias->account->fullname;
    }
    $this->author = $author;
  }

/**
* Sets the creator/owner
* 
* Sets the creator/owner ID of the ASS_Question object
*
* @param integer $owner A numerical ID to identify the owner/creator
* @access public
* @see $owner
*/
  function set_owner($owner = "") {
    $this->owner = $owner;
  }

/**
* Gets the title string
* 
* Gets the title string of the ASS_Question object
*
* @return string The title string to describe the question
* @access public
* @see $title
*/
  function get_title() {
    return $this->title;
  }

/**
* Gets the id
* 
* Gets the id of the ASS_Question object
*
* @return integer The id of the ASS_Question object
* @access public
* @see $id
*/
  function get_id() {
    return $this->id;
  }

/**
* Gets the test id
* 
* Gets the test id of the ASS_Question object
*
* @return integer The test id of the ASS_Question object
* @access public
* @see $test_id
*/
  function get_test_id() {
    return $this->test_id;
  }

/**
* Gets the comment
* 
* Gets the comment string of the ASS_Question object
*
* @return string The comment string to describe the question
* @access public
* @see $comment
*/
  function get_comment() {
    return $this->comment;
  }

/**
* Gets the materials uri
* 
* Gets the materials uri
*
* @return string The uri to additional materials
* @access public
* @see $materials
*/
  function get_materials() {
    return $this->materials;
  }

/**
* Gets the authors name
* 
* Gets the authors name of the ASS_Question object
*
* @return string The string containing the name of the questions author
* @access public
* @see $author
*/
  function get_author() {
    return $this->author;
  }

/**
* Gets the creator/owner
* 
* Gets the creator/owner ID of the ASS_Question object
*
* @return integer The numerical ID to identify the owner/creator
* @access public
* @see $owner
*/
  function get_owner() {
    return $this->owner;
  }

/**
* Get the reference id of the container object
* 
* Get the reference id of the container object
*
* @return integer The reference id of the container object
* @access public
* @see $ref_id
*/
  function get_ref_id() {
    return $this->ref_id;
  }

/**
* Set the reference id of the container object
* 
* Set the reference id of the container object
*
* @param integer $ref_id The reference id of the container object
* @access public
* @see $ref_id
*/
  function set_ref_id($ref_id = 0) {
    $this->ref_id = $ref_id;
  }

/**
* Insert the question into a test
* 
* Insert the question into a test
*
* @param integer $test_id The database id of the test
* @access private
*/
  function insert_into_test($test_id) {
    // get maximum sequence index in test
    $query = sprintf("SELECT MAX(sequence) AS seq FROM dum_test_question WHERE test_fi=%s",
      $this->ilias->db->db->quote($test_id)
    );
    $result = $this->ilias->db->db->query($query);
    $sequence = 1;
    if ($result->numRows() == 1) {
      $data = $result->fetchRow(DB_FETCHMODE_OBJECT);
      $sequence = $data->seq + 1;
    }
    $query = sprintf("INSERT INTO dum_test_question (test_question_id, test_fi, question_fi, sequence, TIMESTAMP) VALUES (NULL, %s, %s, %s, NULL)",
      $this->ilias->db->db->quote($test_id),
      $this->ilias->db->db->quote($this->get_id()),
      $this->ilias->db->db->quote($sequence)
    );
    $result = $this->ilias->db->db->query($query);
    if ($result != DB_OK) {
      // Fehlermeldung
    }
  }
  
/**
* Cancels actions editing this question
* 
* Cancels actions editing this question
*
* @access private
*/
  function cancel_action() {
    if ($this->get_test_id() > 0) {
      header("location:il_as_test_composer.php?tab=questions&edit=" . $this->get_test_id());
    } else {
      header("location:il_as_question_manager.php");
    }
  }
  
/**
* Saves a ASS_Question object to a database
* 
* Saves a ASS_Question object to a database (only method body)
*
* @access public
*/
  function save_to_db() {
    // Method body
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
    return 0;
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
    return 0;
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
    
    // Increase the number of tries for that question
    $query = sprintf("SELECT * FROM dum_assessment_solution_order WHERE user_fi = %s AND test_fi = %s AND question_fi = %s",
      $db->quote($this->ilias->account->id),
      $db->quote($_GET["test"]),
      $db->quote($this->get_id())
    );
    $result = $db->query($query);
    $data = $result->fetchRow(DB_FETCHMODE_OBJECT);
    $query = sprintf("UPDATE dum_assessment_solution_order SET tries = %s WHERE solution_order_id = %s",
      $db->quote($data->tries + 1),
      $db->quote($data->solution_order_id)
    );
    $result = $db->query($query);
  }

/**
* Duplicates the question in the database
* 
* Duplicates the question in the database
*
* @access public
*/
  function duplicate() {
    $clone = $this;
    $clone->set_id(-1);
    $counter = 2;
    while ($this->question_title_exists($clone->get_title() . " ($counter)")) {
      $counter++;
    }
    $clone->set_title($clone->get_title() . " ($counter)");
    $clone->set_owner($this->ilias->account->id);
    $clone->set_author($this->ilias->account->fullname);
    $clone->save_to_db($this->ilias->db->db);
  }
	
/**
* Returns the image path for web accessable images of a question
* 
* Returns the image path for web accessable images of a question.
* The image path is under the CLIENT_WEB_DIR in assessment/REFERENCE_ID_OF_QUESTION_POOL/ID_OF_QUESTION/images
*
* @access public
*/
	function get_image_path() {
		return CLIENT_WEB_DIR . "/assessment/$this->ref_id/$this->id/images/";
	}
}

?>