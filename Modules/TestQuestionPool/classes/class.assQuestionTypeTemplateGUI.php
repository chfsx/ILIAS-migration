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
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
* Question type GUI representation
*
* The assQuestionTypeTemplateGUI class encapsulates the GUI representation
* of a new question type
*
* @author		Unknown <unknowns@email>
* @version	$Id$
* @ingroup ModulesTestQuestionPool
*/
class assQuestionTypeTemplateGUI extends assQuestionGUI
{
	/**
	* assQuestionTypeTemplateGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the question type GUI object.
	*
	* @param integer $id The database id of the question object
	* @access public
	*/
	function assQuestionTypeTemplateGUI(
			$id = -1
	)
	{
		$this->assQuestionGUI();

		// instanciate the question type class, save it in the object attribute
		// and load the question type data from the database
		include_once "./Modules/TestQuestionPool/classes/class.assQuestionTypeTemplate.php";
		$this->object = new assQuestionTypeTemplate();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	/**
	* This method has to be implemented to make the ilCtrl class work
	*
	* This method has to be implemented to make the ilCtrl class work
	*
	* @param string $cmd The command which was called by an ILIAS form
	* @access public
	*/
	function getCommand($cmd)
	{
		// do something here if you want to change certain commands
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
		// this method creates the output of the property edit form for your question
		// where the author adds all necessary information
		// Please have a look at one of the existing question GUI classes how to create a working output

		// 1. Load the template file of your question type (you have to create one)
		// The last parameter TRUE forces ILIAS to load the template from the assessment/templates/default directory
		$this->tpl->addBlockFile("QUESTION_DATA", "question_data", "tpl.il_as_qpl_mytype.html", "Modules/TestQuestionPool");

		// 2. Do some output stuff
		
		// fill the form
		// use the language variables
		// enter the form action
		// ...
	}

	/**
	* Define methods for all the form commands in your edit form
	*
	* Define methods for all the form commands in your edit form
	*
	* @access public
	*/
	function mycommandObject()
	{
		// this method handles the cmd[mycommand] POST command in the edit form
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
	* Creates a solution output of the question
	*
	* Creates a solution output of the question
	*
	* @param integer $active_id The active id of the current user from the tst_active database table
	* @param integer $pass The test pass of the current user
	* @param boolean $graphicalOutput If TRUE, additional graphics (checkmark, cross) are shown to indicate wrong or right answers
	* @return string HTML code which contains the solution output of the question
	* @access public
	*/
	function getSolutionOutput($active_id, $pass = NULL, $graphicalOutput = FALSE)
	{
		// Please have a look at the existing question types how to create a solution output for a question
		// you should use your own template for the output
		return $questionoutput;
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
		// Please have a look at the existing question types how to create a preview output for a question
		// you should use your own template for the output
	}

	/**
	* Helper method for outQuestionForTest to create the question output for a test
	*
	* Helper method for outQuestionForTest to create the question output for a test
	*
	* @param integer $active_id The active id of the current user from the tst_active database table
	* @param integer $pass The test pass of the current user
	* @param boolean $is_postponed The information if the question is a postponed question or not
	* @param boolean $use_post_solutions Fills the question output with answers from the previous post if TRUE, otherwise with the user results from the database
	* @return string HTML code which contains the output of the question
	* @access public
	*/
	function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		// Please have a look at the existing question types how to create an output for a question
		// you should use your own template for the output
	}

	/**
	* Handler for cmd[addSuggestedSolution] to add a suggested solution for the question
	*
	* Handler for cmd[addSuggestedSolution] to add a suggested solution for the question
	*
	* @access public
	*/
	function addSuggestedSolution()
	{
		// this method is inherited from assQuestionGUI
		
		// overwrite it here and define your own code which adds a suggested solution to the question
		// the following code is example code and you have to exchange it with your own code:
		$_SESSION["subquestion_index"] = 0;
		if ($_POST["cmd"]["addSuggestedSolution"])
		{
			if ($this->writePostData())
			{
				sendInfo($this->getErrorMessage());
				$this->editQuestion();
				return;
			}
			if (!$this->checkInput())
			{
				sendInfo($this->lng->txt("fill_out_all_required_fields_add_answer"));
				$this->editQuestion();
				return;
			}
		}
		$this->object->saveToDb();
		$_GET["q_id"] = $this->object->getId();
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
		$this->getQuestionTemplate();
		parent::addSuggestedSolution();
	}
}
?>
