<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './Modules/TestQuestionPool/classes/class.assQuestionGUI.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilGuiQuestionScoringAdjustable.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilGuiAnswerScoringAdjustable.php';
require_once './Modules/Test/classes/inc.AssessmentConstants.php';

/**
 * Multiple choice question GUI representation
 *
 * The assMultipleChoiceGUI class encapsulates the GUI representation
 * for multiple choice questions.
 *
 * @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
 * @author		Björn Heyser <bheyser@databay.de>
 * @author		Maximilian Becker <mbecker@databay.de>
 *
 * @version	$Id$
 *          
 * @ingroup ModulesTestQuestionPool
 */
class assMultipleChoiceGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable, ilGuiAnswerScoringAdjustable
{
	var $choiceKeys;
	
	/**
	* assMultipleChoiceGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the assMultipleChoiceGUI object.
	*
	* @param integer $id The database id of a multiple choice question object
	* @access public
	*/
	function __construct($id = -1)
	{
		parent::__construct();
		include_once "./Modules/TestQuestionPool/classes/class.assMultipleChoice.php";
		$this->object = new assMultipleChoice();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	/**
	 * Evaluates a posted edit form and writes the form data in the question object
	 *
	 * @param bool $always
	 *
	 * @return integer A positive value, if one of the required fields wasn't set, else 0
	 */
	public function writePostData($always = false)
	{
		$hasErrors = (!$always) ? $this->editQuestion(true) : false;
		if (!$hasErrors)
		{
			require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
			$this->writeQuestionGenericPostData();
			$this->writeQuestionSpecificPostData(new ilPropertyFormGUI());
			$this->writeAnswerSpecificPostData(new ilPropertyFormGUI());
			$this->saveTaxonomyAssignments();
			return 0;
		}
		return 1;
	}

	/**
	 * Creates an output of the edit form for the question
	 *
	 * @param bool $checkonly
	 *
	 * @return bool
	 */
	public function editQuestion($checkonly = FALSE)
	{
		$save = $this->isSaveCommand();
		$this->getQuestionTemplate();

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$isSingleline = ($this->object->lastChange == 0 && !array_key_exists('types', $_POST)) ? (($this->object->getMultilineAnswerSetting()) ? false : true) : $this->object->isSingleline;
		if ($checkonly) $isSingleline = ($_POST['types'] == 0) ? true : false;
		if ($isSingleline)
		{
			$form->setMultipart(TRUE);
		}
		else
		{
			$form->setMultipart(FALSE);
		}
		$form->setTableWidth("100%");
		$form->setId("assmultiplechoice");

		// title, author, description, question, working time (assessment mode)
		$this->addBasicQuestionFormProperties( $form );
		$this->populateQuestionSpecificFormPart( $form );
		$this->populateAnswerSpecificFormPart( $form );
		$this->populateTaxonomyFormSection($form);
		$this->addQuestionFormCommandButtons($form);

		$errors = false;

		if ($save)
		{
			$form->setValuesByPost();
			$errors = !$form->checkInput();
			$form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
			if ($errors) $checkonly = false;
		}

		if (!$checkonly) $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
		return $errors;
	}

	/**
	 * Upload an image
	 */
	public function uploadchoice()
	{
		$this->writePostData(true);
		$position = key($_POST['cmd']['uploadchoice']);
		$this->editQuestion();
	}

	/**
	 * Remove an image
	 */
	public function removeimagechoice()
	{
		$this->writePostData(true);
		$position = key($_POST['cmd']['removeimagechoice']);
		$filename = $_POST['choice']['imagename'][$position];
		$this->object->removeAnswerImage($position);
		$this->editQuestion();
	}

	/**
	 * Add a new answer
	 */
	public function addchoice()
	{
		$this->writePostData(true);
		$position = key($_POST['cmd']['addchoice']);
		$this->object->addAnswer("", 0, 0, $position+1);
		$this->editQuestion();
	}

	/**
	 * Remove an answer
	 */
	public function removechoice()
	{
		$this->writePostData(true);
		$position = key($_POST['cmd']['removechoice']);
		$this->object->deleteAnswer($position);
		$this->editQuestion();
	}

	/**
	 * Get the question solution output
	 *
	 * @param integer $active_id             The active user id
	 * @param integer $pass                  The test pass
	 * @param boolean $graphicalOutput       Show visual feedback for right/wrong answers
	 * @param boolean $result_output         Show the reached points for parts of the question
	 * @param boolean $show_question_only    Show the question without the ILIAS content around
	 * @param boolean $show_feedback         Show the question feedback
	 * @param boolean $show_correct_solution Show the correct solution instead of the user solution
	 * @param boolean $show_manual_scoring   Show specific information for the manual scoring output
	 * @param bool    $show_question_text
	 *
	 * @return string The solution output of the question as HTML code
	 */
	function getSolutionOutput(
		$active_id,
		$pass = NULL,
		$graphicalOutput = FALSE,
		$result_output = FALSE,
		$show_question_only = TRUE,
		$show_feedback = FALSE,
		$show_correct_solution = FALSE,
		$show_manual_scoring = FALSE,
		$show_question_text = TRUE
	)
	{
		// shuffle output
		$keys = $this->getChoiceKeys();

		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = array();
		if (($active_id > 0) && (!$show_correct_solution))
		{
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
			foreach ($solutions as $idx => $solution_value)
			{
				array_push($user_solution, $solution_value["value1"]);
			}
		}
		else
		{
			// take the correct solution instead of the user solution
			foreach ($this->object->answers as $index => $answer)
			{
				$points_checked = $answer->getPointsChecked();
				$points_unchecked = $answer->getPointsUnchecked();
				if ($points_checked > $points_unchecked)
				{
					if ($points_checked > 0)
					{
						array_push($user_solution, $index);
					}
				}
			}
		}
		
		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_mc_mr_output_solution.html", TRUE, TRUE, "Modules/TestQuestionPool");
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html",TRUE, TRUE, "Modules/TestQuestionPool");
		foreach ($keys as $answer_id)
		{
			$answer = $this->object->answers[$answer_id];
			if (($active_id > 0) && (!$show_correct_solution))
			{
				if ($graphicalOutput)
				{
					// output of ok/not ok icons for user entered solutions
					$ok = FALSE;
					$checked = FALSE;
					foreach ($user_solution as $mc_solution)
					{
						if (strcmp($mc_solution, $answer_id) == 0)
						{
							$checked = TRUE;
						}
					}
					if ($checked)
					{
						if ($answer->getPointsChecked() > $answer->getPointsUnchecked())
						{
							$ok = TRUE;
						}
						else
						{
							$ok = FALSE;
						}
					}
					else
					{
						if ($answer->getPointsChecked() > $answer->getPointsUnchecked())
						{
							$ok = FALSE;
						}
						else
						{
							$ok = TRUE;
						}
					}
					if ($ok)
					{
						$template->setCurrentBlock("icon_ok");
						$template->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.png"));
						$template->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
						$template->parseCurrentBlock();
					}
					else
					{
						$template->setCurrentBlock("icon_ok");
						$template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.png"));
						$template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
						$template->parseCurrentBlock();
					}
				}
			}
			if (strlen($answer->getImage()))
			{
				$template->setCurrentBlock("answer_image");
				if ($this->object->getThumbSize())
				{
					$template->setVariable("ANSWER_IMAGE_URL", $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $answer->getImage());
				}
				else
				{
					$template->setVariable("ANSWER_IMAGE_URL", $this->object->getImagePathWeb() . $answer->getImage());
				}
				$alt = $answer->getImage();
				if (strlen($answer->getAnswertext()))
				{
					$alt = $answer->getAnswertext();
				}
				$alt = preg_replace("/<[^>]*?>/", "", $alt);
				$template->setVariable("ANSWER_IMAGE_ALT", ilUtil::prepareFormOutput($alt));
				$template->setVariable("ANSWER_IMAGE_TITLE", ilUtil::prepareFormOutput($alt));
				$template->parseCurrentBlock();
			}
		
			if ($show_feedback)
			{
				
				if ($this->object->getSpecificFeedbackSetting() == 2)
				{
					foreach ($user_solution as $mc_solution)
					{
						if (strcmp($mc_solution, $answer_id) == 0)
						{
							$fb = $this->object->feedbackOBJ->getSpecificAnswerFeedbackTestPresentation(
									$this->object->getId(), $answer_id
							);
							if (strlen($fb))
							{
								$template->setCurrentBlock("feedback");
								$template->setVariable("FEEDBACK", $this->object->prepareTextareaOutput( $fb, true ));
								$template->parseCurrentBlock();
							}
						}
					}
				}
				
				if ($this->object->getSpecificFeedbackSetting() == 1)
				{
					$fb = $this->object->feedbackOBJ->getSpecificAnswerFeedbackTestPresentation(
							$this->object->getId(), $answer_id
					);
					if (strlen($fb))
					{
						$template->setCurrentBlock("feedback");
						$template->setVariable("FEEDBACK", $this->object->prepareTextareaOutput( $fb, true ));
						$template->parseCurrentBlock();
					}					
				}
				
				if ($this->object->getSpecificFeedbackSetting() == 3)
				{
					$answer = $this->object->getAnswer($answer_id);
					
					if ($answer->getPoints() > 0)
					{
						$fb = $this->object->feedbackOBJ->getSpecificAnswerFeedbackTestPresentation(
								$this->object->getId(), $answer_id
						);
						if (strlen($fb))
						{
							$template->setCurrentBlock("feedback");
							$template->setVariable("FEEDBACK", $this->object->prepareTextareaOutput( $fb, true ));
							$template->parseCurrentBlock();
						}
					}
					
				}
			}
			$template->setCurrentBlock("answer_row");
			$template->setVariable("ANSWER_TEXT", $this->object->prepareTextareaOutput($answer->getAnswertext(), TRUE));
			$checked = FALSE;
			if ($result_output)
			{
				$pointschecked = $this->object->answers[$answer_id]->getPointsChecked();
				$pointsunchecked = $this->object->answers[$answer_id]->getPointsUnchecked();
				$resulttextchecked = ($pointschecked == 1) || ($pointschecked == -1) ? "%s " . $this->lng->txt("point") : "%s " . $this->lng->txt("points");
				$resulttextunchecked = ($pointsunchecked == 1) || ($pointsunchecked == -1) ? "%s " . $this->lng->txt("point") : "%s " . $this->lng->txt("points"); 
				$template->setVariable("RESULT_OUTPUT", sprintf("(" . $this->lng->txt("checkbox_checked") . " = $resulttextchecked, " . $this->lng->txt("checkbox_unchecked") . " = $resulttextunchecked)", $pointschecked, $pointsunchecked));
			}
			foreach ($user_solution as $mc_solution)
			{
				if (strcmp($mc_solution, $answer_id) == 0)
				{
					$template->setVariable("SOLUTION_IMAGE", ilUtil::getHtmlPath(ilUtil::getImagePath("checkbox_checked.png")));
					$template->setVariable("SOLUTION_ALT", $this->lng->txt("checked"));
					$checked = TRUE;
				}
			}
			if (!$checked)
			{
				$template->setVariable("SOLUTION_IMAGE", ilUtil::getHtmlPath(ilUtil::getImagePath("checkbox_unchecked.png")));
				$template->setVariable("SOLUTION_ALT", $this->lng->txt("unchecked"));
			}
			$template->parseCurrentBlock();
		}
		$questiontext = $this->object->getQuestion();
		if ($show_question_text==true)
		{
			$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		}
		$questionoutput = $template->get();
		$feedback = ($show_feedback) ? $this->getAnswerFeedbackOutput($active_id, $pass) : "";
		
		if (strlen($feedback)) $solutiontemplate->setVariable("FEEDBACK", $this->object->prepareTextareaOutput( $feedback , true ));
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

		$solutionoutput = $solutiontemplate->get(); 
		if (!$show_question_only)
		{
			// get page object output
			$solutionoutput = '<div class="ilc_question_Standard">'.$solutionoutput."</div>";
		}
		return $solutionoutput;
	}
	
	function getPreview($show_question_only = FALSE, $showInlineFeedback = false)
	{
		$user_solution = is_object($this->getPreviewSession()) ? (array)$this->getPreviewSession()->getParticipantsSolution() : array();
		// shuffle output
		$keys = $this->getChoiceKeys();

		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_mc_mr_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		foreach ($keys as $answer_id)
		{
			$answer = $this->object->answers[$answer_id];
			if (strlen($answer->getImage()))
			{
				if ($this->object->getThumbSize())
				{
					$template->setCurrentBlock("preview");
					$template->setVariable("URL_PREVIEW", $this->object->getImagePathWeb() . $answer->getImage());
					$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
					$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
					$template->setVariable("ANSWER_IMAGE_URL", $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $answer->getImage());
					list($width, $height, $type, $attr) = getimagesize($this->object->getImagePath() . $answer->getImage());
					$alt = $answer->getImage();
					if (strlen($answer->getAnswertext()))
					{
						$alt = $answer->getAnswertext();
					}
					$alt = preg_replace("/<[^>]*?>/", "", $alt);
					$template->setVariable("ANSWER_IMAGE_ALT", ilUtil::prepareFormOutput($alt));
					$template->setVariable("ANSWER_IMAGE_TITLE", ilUtil::prepareFormOutput($alt));
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock("answer_image");
					$template->setVariable("ANSWER_IMAGE_URL", $this->object->getImagePathWeb() . $answer->getImage());
					list($width, $height, $type, $attr) = getimagesize($this->object->getImagePath() . $answer->getImage());
					$alt = $answer->getImage();
					if (strlen($answer->getAnswertext()))
					{
						$alt = $answer->getAnswertext();
					}
					$alt = preg_replace("/<[^>]*?>/", "", $alt);
					$template->setVariable("ATTR", $attr);
					$template->setVariable("ANSWER_IMAGE_ALT", ilUtil::prepareFormOutput($alt));
					$template->setVariable("ANSWER_IMAGE_TITLE", ilUtil::prepareFormOutput($alt));
					$template->parseCurrentBlock();
				}
			}

			if( $showInlineFeedback )
			{
				$this->populateSpecificFeedbackInline($user_solution, $answer_id, $template);
			}
			
			$template->setCurrentBlock("answer_row");
			$template->setVariable("ANSWER_ID", $answer_id);
			$template->setVariable("ANSWER_TEXT", $this->object->prepareTextareaOutput($answer->getAnswertext(), TRUE));
			foreach ($user_solution as $mc_solution)
			{
				if (strcmp($mc_solution, $answer_id) == 0)
				{
					$template->setVariable("CHECKED_ANSWER", " checked=\"checked\"");
				}
			}
			$template->parseCurrentBlock();
		}
		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		if (!$show_question_only)
		{
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}
		return $questionoutput;
	}

	/**
	 * @param integer		$active_id
	 * @param integer|null	$pass
	 * @param bool			$is_postponed
	 * @param bool			$use_post_solutions
	 * @param bool			$show_feedback
	 *
	 * @return string
	 */
	function getTestOutput(
		$active_id, 
		$pass = NULL, 
		$is_postponed = FALSE, 
		$use_post_solutions = FALSE, 
		$show_feedback = FALSE
	)
	{
		// shuffle output
		$keys = $this->getChoiceKeys();

		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = array();
		if ($active_id)
		{
			$solutions = NULL;
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
			foreach ($solutions as $idx => $solution_value)
			{
				array_push($user_solution, $solution_value["value1"]);
			}
		}
		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_mc_mr_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		foreach ($keys as $answer_id)
		{
			$answer = $this->object->answers[$answer_id];
			if (strlen($answer->getImage()))
			{
				if ($this->object->getThumbSize())
				{
					$template->setCurrentBlock("preview");
					$template->setVariable("URL_PREVIEW", $this->object->getImagePathWeb() . $answer->getImage());
					$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
					$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
					$template->setVariable("ANSWER_IMAGE_URL", $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $answer->getImage());
					list($width, $height, $type, $attr) = getimagesize($this->object->getImagePath() . $answer->getImage());
					$alt = $answer->getImage();
					if (strlen($answer->getAnswertext()))
					{
						$alt = $answer->getAnswertext();
					}
					$alt = preg_replace("/<[^>]*?>/", "", $alt);
					$template->setVariable("ANSWER_IMAGE_ALT", ilUtil::prepareFormOutput($alt));
					$template->setVariable("ANSWER_IMAGE_TITLE", ilUtil::prepareFormOutput($alt));
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock("answer_image");
					$template->setVariable("ANSWER_IMAGE_URL", $this->object->getImagePathWeb() . $answer->getImage());
					list($width, $height, $type, $attr) = getimagesize($this->object->getImagePath() . $answer->getImage());
					$alt = $answer->getImage();
					if (strlen($answer->getAnswertext()))
					{
						$alt = $answer->getAnswertext();
					}
					$alt = preg_replace("/<[^>]*?>/", "", $alt);
					$template->setVariable("ATTR", $attr);
					$template->setVariable("ANSWER_IMAGE_ALT", ilUtil::prepareFormOutput($alt));
					$template->setVariable("ANSWER_IMAGE_TITLE", ilUtil::prepareFormOutput($alt));
					$template->parseCurrentBlock();
				}
			}
			
			if( $show_feedback )
			{
				$this->populateSpecificFeedbackInline($user_solution, $answer_id, $template);					
			}

			$template->setCurrentBlock("answer_row");
			$template->setVariable("ANSWER_ID", $answer_id);
			$template->setVariable("ANSWER_TEXT", $this->object->prepareTextareaOutput($answer->getAnswertext(), TRUE));
			foreach ($user_solution as $mc_solution)
			{
				if (strcmp($mc_solution, $answer_id) == 0)
				{
					$template->setVariable("CHECKED_ANSWER", " checked=\"checked\"");
				}
			}
			$template->parseCurrentBlock();
		}
		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
		return $pageoutput;
	}

	/**
	 * Sets the ILIAS tabs for this question type
	 * 
	 * @todo:	MOVE THIS STEPS TO COMMON QUESTION CLASS assQuestionGUI
	 */
	public function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;

		$ilTabs->clearTargets();
		
		$this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET["q_id"]);
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
			if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
			{
				// edit page
				$ilTabs->addTarget("edit_page",
					$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
					array("edit", "insert", "exec_pg"),
					"", "", $force_active);
			}

			$this->addTab_QuestionPreview($ilTabs);
		}
		$force_active = false;
		if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
		{
			$url = "";
			if ($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			$force_active = false;
			// edit question properties
			$ilTabs->addTarget("edit_question",
				$url,
				array("editQuestion", "save", "saveEdit", "addchoice", "removechoice", "removeimagechoice", "uploadchoice", "originalSyncForm"),
				$classname, "", $force_active);
		}

		// add tab for question feedback within common class assQuestionGUI
		$this->addTab_QuestionFeedback($ilTabs);

		// add tab for question hint within common class assQuestionGUI
		$this->addTab_QuestionHints($ilTabs);

		// add tab for question's suggested solution within common class assQuestionGUI
		$this->addTab_SuggestedSolution($ilTabs, $classname);

		// Assessment of questions sub menu entry
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, "");
		}

		$this->addBackTab($ilTabs);
	}
	
	/**
	 * Create the key index numbers for the array of choices
	 * 
	 * @return array
	 */
	function getChoiceKeys()
	{
		if (strcmp($_GET["activecommand"], "directfeedback") == 0)
		{
			if (is_array($_SESSION["choicekeys"])) $this->choiceKeys = $_SESSION["choicekeys"];
		}
		if (!is_array($this->choiceKeys))
		{
			$this->choiceKeys = array_keys($this->object->answers);
			if ($this->object->getShuffle())
			{
				$this->choiceKeys = $this->object->pcArrayShuffle($this->choiceKeys);
			}
		}
		$_SESSION["choicekeys"] = $this->choiceKeys;
		return $this->choiceKeys;
	}

	function getSpecificFeedbackOutput($active_id, $pass)
	{
		// No return value, this question type supports inline specific feedback.
		$output = "";
		return $this->object->prepareTextareaOutput($output, TRUE);
	}

	public function writeQuestionSpecificPostData(ilPropertyFormGUI $form)
	{
		$this->object->setShuffle( $_POST["shuffle"] );

		$this->object->setSpecificFeedbackSetting( $_POST['feedback_setting'] );

		$this->object->setMultilineAnswerSetting( $_POST["types"] );
		if (is_array( $_POST['choice']['imagename'] ) && $_POST["types"] == 1)
		{
			$this->object->isSingleline = true;
			ilUtil::sendInfo( $this->lng->txt( 'info_answer_type_change' ), true );
		}
		else
		{
			$this->object->isSingleline = ($_POST["types"] == 0) ? true : false;
		}
		$this->object->setThumbSize( (strlen( $_POST["thumb_size"] )) ? $_POST["thumb_size"] : "" );
	}

	public function writeAnswerSpecificPostData(ilPropertyFormGUI $form)
	{
		// Delete all existing answers and create new answers from the form data
		$this->object->flushAnswers();
		if ($this->object->isSingleline)
		{
			foreach ($_POST['choice']['answer'] as $index => $answertext)
			{
				$picturefile    = $_POST['choice']['imagename'][$index];
				$file_org_name  = $_FILES['choice']['name']['image'][$index];
				$file_temp_name = $_FILES['choice']['tmp_name']['image'][$index];

				if (strlen( $file_temp_name ))
				{
					// check suffix						
					$suffix = strtolower( array_pop( explode( ".", $file_org_name ) ) );
					if (in_array( $suffix, array( "jpg", "jpeg", "png", "gif" ) ))
					{
						// upload image
						$filename = $this->object->createNewImageFileName( $file_org_name );
						if ($this->object->setImageFile( $filename, $file_temp_name ) == 0)
						{
							$picturefile = $filename;
						}
					}
				}
				$this->object->addAnswer( $answertext,
										  $_POST['choice']['points'][$index],
										  $_POST['choice']['points_unchecked'][$index],
										  $index,
										  $picturefile
				);
			}
		}
		else
		{
			foreach ($_POST['choice']['answer'] as $index => $answer)
			{
				$answertext = $answer;
				$this->object->addAnswer( $answertext,
										  $_POST['choice']['points'][$index],
										  $_POST['choice']['points_unchecked'][$index],
										  $index
				);
			}
		}
	}

	public function populateQuestionSpecificFormPart(\ilPropertyFormGUI $form)
	{
		// shuffle
		$shuffle = new ilCheckboxInputGUI($this->lng->txt( "shuffle_answers" ), "shuffle");
		$shuffle->setValue( 1 );
		$shuffle->setChecked( $this->object->getShuffle() );
		$shuffle->setRequired( FALSE );
		$form->addItem( $shuffle );

		if ($this->object->getId())
		{
			$hidden = new ilHiddenInputGUI("", "ID");
			$hidden->setValue( $this->object->getId() );
			$form->addItem( $hidden );
		}

		if (!$this->object->getSelfAssessmentEditingMode())
		{
			$isSingleline = ($this->object->lastChange == 0 && !array_key_exists( 'types',
																				  $_POST
				)) ? (($this->object->getMultilineAnswerSetting()) ? false : true) : $this->object->isSingleline;
			// Answer types
			$types = new ilSelectInputGUI($this->lng->txt( "answer_types" ), "types");
			$types->setRequired( false );
			$types->setValue( ($isSingleline) ? 0 : 1 );
			$types->setOptions( array(
									0 => $this->lng->txt( 'answers_singleline' ),
									1 => $this->lng->txt( 'answers_multiline' ),
								)
			);
			$form->addItem( $types );
		}

		if ($isSingleline)
		{
			// thumb size
			$thumb_size = new ilNumberInputGUI($this->lng->txt( "thumb_size" ), "thumb_size");
			$thumb_size->setSuffix($this->lng->txt("thumb_size_unit_pixel"));
			$thumb_size->setMinValue( 20 );
			$thumb_size->setDecimals( 0 );
			$thumb_size->setSize( 6 );
			$thumb_size->setInfo( $this->lng->txt( 'thumb_size_info' ) );
			$thumb_size->setValue( $this->object->getThumbSize() );
			$thumb_size->setRequired( false );
			$form->addItem( $thumb_size );
			return $isSingleline;
		}
		return $isSingleline;
	}

	public function populateAnswerSpecificFormPart(\ilPropertyFormGUI $form)
	{
		// Choices
		include_once "./Modules/TestQuestionPool/classes/class.ilMultipleChoiceWizardInputGUI.php";
		$choices = new ilMultipleChoiceWizardInputGUI($this->lng->txt( "answers" ), "choice");
		$choices->setRequired( true );
		$choices->setQuestionObject( $this->object );
		$isSingleline = ($this->object->lastChange == 0 && !array_key_exists( 'types',
																			  $_POST
			)) ? (($this->object->getMultilineAnswerSetting()) ? false : true) : $this->object->isSingleline;
		$choices->setSingleline( $isSingleline );
		$choices->setAllowMove( false );
		if ($this->object->getSelfAssessmentEditingMode())
		{
			$choices->setSize( 40 );
			$choices->setMaxLength( 800 );
		}
		if ($this->object->getAnswerCount() == 0)
			$this->object->addAnswer( "", 0, 0, 0 );
		$choices->setValues( $this->object->getAnswers() );
		$form->addItem( $choices );
	}

	/**
	 * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
	 * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
	 * make sense in the given context.
	 *
	 * E.g. array('cloze_type', 'image_filename')
	 *
	 * @return string[]
	 */
	public function getAfterParticipationSuppressionAnswerPostVars()
	{
		return array();
	}

	/**
	 * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
	 * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
	 * make sense in the given context.
	 *
	 * E.g. array('cloze_type', 'image_filename')
	 *
	 * @return string[]
	 */
	public function getAfterParticipationSuppressionQuestionPostVars()
	{
		return array();
	}

	/**
	 * Returns an html string containing a question specific representation of the answers so far
	 * given in the test for use in the right column in the scoring adjustment user interface.
	 *
	 * @param array $relevant_answers
	 *
	 * @return string
	 */
	public function getAggregatedAnswersView($relevant_answers)
	{
		return  $this->renderAggregateView( 
			$this->aggregateAnswers( $relevant_answers, $this->object->getAnswers() ) )->get();
	}

	public function aggregateAnswers($relevant_answers_chosen, $answers_defined_on_question)
	{
		$aggregate = array();
		foreach ($answers_defined_on_question as $answer)
		{
			$aggregated_info_for_answer 					= array();
			$aggregated_info_for_answer['answertext']		= $answer->getAnswerText();
			$aggregated_info_for_answer['count_checked']	= 0;

			foreach ($relevant_answers_chosen as $relevant_answer)
			{
				if ($relevant_answer['value1'] == $answer->getOrder())
				{
					$aggregated_info_for_answer['count_checked']++;
				}
			}
			$aggregated_info_for_answer['count_unchecked'] = 
				ceil(count($relevant_answers_chosen) / count($answers_defined_on_question))
				- $aggregated_info_for_answer['count_checked'];

			$aggregate[] = $aggregated_info_for_answer;
		}
		return $aggregate;
	}

	/**
	 * @param $aggregate
	 *
	 * @return ilTemplate
	 */
	public function renderAggregateView($aggregate)
	{
		$tpl = new ilTemplate('tpl.il_as_aggregated_answers_table.html', true, true, "Modules/TestQuestionPool");

		foreach ($aggregate as $line_data)
		{
			$tpl->setCurrentBlock( 'aggregaterow' );
			$tpl->setVariable( 'OPTION', $line_data['answertext'] );
			$tpl->setVariable( 'COUNT', $line_data['count_checked'] );
			$tpl->parseCurrentBlock();
		}
		return $tpl;
	}

	/**
	 * @param $user_solution
	 * @param $answer_id
	 * @param $template
	 * @return array
	 */
	private function populateSpecificFeedbackInline($user_solution, $answer_id, $template)
	{
		if($this->object->getSpecificFeedbackSetting() == 2)
		{
			foreach($user_solution as $mc_solution)
			{
				if(strcmp($mc_solution, $answer_id) == 0)
				{
					$fb = $this->object->feedbackOBJ->getSpecificAnswerFeedbackTestPresentation($this->object->getId(), $answer_id);
					if(strlen($fb))
					{
						$template->setCurrentBlock("feedback");
						$template->setVariable("FEEDBACK", $this->object->prepareTextareaOutput($fb, true));
						$template->parseCurrentBlock();
					}
				}
			}
		}

		if($this->object->getSpecificFeedbackSetting() == 1)
		{
			$fb = $this->object->feedbackOBJ->getSpecificAnswerFeedbackTestPresentation($this->object->getId(), $answer_id);
			if(strlen($fb))
			{
				$template->setCurrentBlock("feedback");
				$template->setVariable("FEEDBACK", $this->object->prepareTextareaOutput($fb, true));
				$template->parseCurrentBlock();
			}
		}

		if($this->object->getSpecificFeedbackSetting() == 3)
		{
			$answer = $this->object->getAnswer($answer_id);

			if($answer->getPoints() > 0)
			{
				$fb = $this->object->feedbackOBJ->getSpecificAnswerFeedbackTestPresentation($this->object->getId(), $answer_id);
				if(strlen($fb))
				{
					$template->setCurrentBlock("feedback");
					$template->setVariable("FEEDBACK", $this->object->prepareTextareaOutput($fb, true));
					$template->parseCurrentBlock();
				}
			}
		}
	}
}