<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './Modules/TestQuestionPool/classes/class.assQuestionGUI.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilGuiQuestionScoringAdjustable.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilGuiAnswerScoringAdjustable.php';
require_once './Modules/Test/classes/inc.AssessmentConstants.php';

/**
 * Matching question GUI representation
 *
 * The assMatchingQuestionGUI class encapsulates the GUI representation
 * for matching questions.
 *
 * @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
 * @author		Björn Heyser <bheyser@databay.de>
 * @author		Maximilian Becker <mbecker@databay.de>
 * 
 * @version	$Id$
 * 
 * @ingroup ModulesTestQuestionPool
 */
class assMatchingQuestionGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable, ilGuiAnswerScoringAdjustable
{
	/**
	 * assMatchingQuestionGUI constructor
	 *
	 * The constructor takes possible arguments an creates an instance of the assMatchingQuestionGUI object.
	 *
	 * @param integer $id The database id of a image map question object
	 * 
	 * @return \assMatchingQuestionGUI
	 */
	public function __construct($id = -1)
	{
		parent::__construct();
		include_once "./Modules/TestQuestionPool/classes/class.assMatchingQuestion.php";
		$this->object = new assMatchingQuestion();
		$this->setErrorMessage($this->lng->txt("msg_form_save_error"));
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
			$this->writeQuestionGenericPostData();
			$this->writeQuestionSpecificPostData();
			$this->writeAnswerSpecificPostData();
			$this->saveTaxonomyAssignments();
			return 0;
		}
		return 1;
	}

	public function writeAnswerSpecificPostData($always = true)
	{
		// Delete all existing answers and create new answers from the form data
		$this->object->flushMatchingPairs();
		$this->object->flushTerms();
		$this->object->flushDefinitions();

		// add terms
		require_once './Modules/TestQuestionPool/classes/class.assAnswerMatchingTerm.php';
		foreach ($_POST['terms']['answer'] as $index => $answer)
		{
			$filename = $_POST['terms']['imagename'][$index];
			if (strlen( $_FILES['terms']['name']['image'][$index] ))
			{
				// upload the new file
				$name = $_FILES['terms']['name']['image'][$index];
				if ($this->object->setImageFile( $_FILES['terms']['tmp_name']['image'][$index],
												 $this->object->getEncryptedFilename( $name )
				)
				)
				{
					$filename = $this->object->getEncryptedFilename( $name );
				}
				else
				{
					$filename = "";
				}
			}
			$this->object->addTerm( new assAnswerMatchingTerm($answer, $filename, $_POST['terms']['identifier'][$index])
			);
		}
		// add definitions
		require_once './Modules/TestQuestionPool/classes/class.assAnswerMatchingDefinition.php';
		foreach ($_POST['definitions']['answer'] as $index => $answer)
		{
			$filename = $_POST['definitions']['imagename'][$index];
			if (strlen( $_FILES['definitions']['name']['image'][$index] ))
			{
				// upload the new file
				$name = $_FILES['definitions']['name']['image'][$index];
				if ($this->object->setImageFile( $_FILES['definitions']['tmp_name']['image'][$index],
												 $this->object->getEncryptedFilename( $name )
				)
				)
				{
					$filename = $this->object->getEncryptedFilename( $name );
				}
				else
				{
					$filename = "";
				}
			}
			$this->object->addDefinition( 
				new assAnswerMatchingDefinition($answer, $filename, $_POST['definitions']['identifier'][$index])
			);
		}

		// add matching pairs
		if (is_array( $_POST['pairs']['points'] ))
		{
			require_once './Modules/TestQuestionPool/classes/class.assAnswerMatchingPair.php';
			foreach ($_POST['pairs']['points'] as $index => $points)
			{
				$term_id = $_POST['pairs']['term'][$index];
				$definition_id = $_POST['pairs']['definition'][$index];
				$this->object->addMatchingPair( $this->object->getTermWithIdentifier( $term_id ),
												$this->object->getDefinitionWithIdentifier( $definition_id ),
												$points
				);
			}
		}
	}

	public function writeQuestionSpecificPostData($always = true)
	{
		if (!$this->object->getSelfAssessmentEditingMode())
		{
			$this->object->setShuffle( $_POST["shuffle"] );
		}
		else
		{
			$this->object->setShuffle( 1 );
		}
		$this->object->setThumbGeometry( $_POST["thumb_geometry"] );
		$this->object->setElementHeight( $_POST["element_height"] );
	}

	public function uploadterms()
	{
		$this->writePostData(true);
		$this->editQuestion();
	}

	public function removeimageterms()
	{
		$this->writePostData(true);
		$position = key($_POST['cmd']['removeimageterms']);
		$this->object->removeTermImage($position);
		$this->editQuestion();
	}

	public function uploaddefinitions()
	{
		$this->writePostData(true);
		$this->editQuestion();
	}

	public function removeimagedefinitions()
	{
		$this->writePostData(true);
		$position = key($_POST['cmd']['removeimagedefinitions']);
		$this->object->removeDefinitionImage($position);
		$this->editQuestion();
	}

	public function addterms()
	{
		$this->writePostData();
		$position = key($_POST["cmd"]["addterms"]);
		$this->object->insertTerm($position+1);
		$this->editQuestion();
	}

	public function removeterms()
	{
		$this->writePostData();
		$position = key($_POST["cmd"]["removeterms"]);
		$this->object->deleteTerm($position);
		$this->editQuestion();
	}

	public function adddefinitions()
	{
		$this->writePostData();
		$position = key($_POST["cmd"]["adddefinitions"]);
		$this->object->insertDefinition($position+1);
		$this->editQuestion();
	}

	public function removedefinitions()
	{
		$this->writePostData();
		$position = key($_POST["cmd"]["removedefinitions"]);
		$this->object->deleteDefinition($position);
		$this->editQuestion();
	}

	public function addpairs()
	{
		$this->writePostData();
		$position = key($_POST["cmd"]["addpairs"]);
		$this->object->insertMatchingPair($position+1);
		$this->editQuestion();
	}

	public function removepairs()
	{
		$this->writePostData();
		$position = key($_POST["cmd"]["removepairs"]);
		$this->object->deleteMatchingPair($position);
		$this->editQuestion();
	}

	public function editQuestion($checkonly = FALSE)
	{
		$save = $this->isSaveCommand();
		$this->getQuestionTemplate();

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$form->setMultipart(true);
		$form->setTableWidth("100%");
		$form->setId("matching");


		// title, author, description, question, working time (assessment mode)
		$this->addBasicQuestionFormProperties($form);
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
			if( !$errors && !$this->isValidTermAndDefinitionAmount($form) && !$this->object->getSelfAssessmentEditingMode() )
			{
				$errors = true;
				$terms = $form->getItemByPostVar('terms');
				$terms->setAlert($this->lng->txt("msg_number_of_terms_too_low"));
				ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
			}
			if ($errors) $checkonly = false;
		}

		if (!$checkonly) $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
		return $errors;
	}

	/**
	 * @param ilPropertyFormGUI $form
	 * @return bool
	 */
	private function isValidTermAndDefinitionAmount(ilPropertyFormGUI $form)
	{
		$numTerms = count($form->getItemByPostVar('terms')->getValues());
		$numDefinitions = count($form->getItemByPostVar('definitions')->getValues());
		
		if($numTerms >= $numDefinitions)
		{
			return true;
		}
		
		return false;
	}

	public function populateAnswerSpecificFormPart(\ilPropertyFormGUI $form)
	{
		// Definitions
		include_once "./Modules/TestQuestionPool/classes/class.ilMatchingWizardInputGUI.php";
		$definitions = new ilMatchingWizardInputGUI($this->lng->txt( "definitions" ), "definitions");
		if ($this->object->getSelfAssessmentEditingMode())
		{
			$definitions->setHideImages( true );
		}
		
		$definitions->setRequired( true );
		$definitions->setQuestionObject( $this->object );
		$definitions->setTextName( $this->lng->txt( 'definition_text' ) );
		$definitions->setImageName( $this->lng->txt( 'definition_image' ) );
		include_once "./Modules/TestQuestionPool/classes/class.assAnswerMatchingDefinition.php";
		if (!count( $this->object->getDefinitions() ))
		{
			$this->object->addDefinition( new assAnswerMatchingDefinition() );
		}
		$definitionvalues = $this->object->getDefinitions();
		$definitions->setValues( $definitionvalues );
		$form->addItem( $definitions );

		// Terms
		include_once "./Modules/TestQuestionPool/classes/class.ilMatchingWizardInputGUI.php";
		$terms = new ilMatchingWizardInputGUI($this->lng->txt( "terms" ), "terms");
		if ($this->object->getSelfAssessmentEditingMode())
			$terms->setHideImages( true );
		$terms->setRequired( true );
		$terms->setQuestionObject( $this->object );
		$terms->setTextName( $this->lng->txt( 'term_text' ) );
		$terms->setImageName( $this->lng->txt( 'term_image' ) );
		include_once "./Modules/TestQuestionPool/classes/class.assAnswerMatchingTerm.php";
		if (!count( $this->object->getTerms() ))
			$this->object->addTerm( new assAnswerMatchingTerm() );
		$termvalues = $this->object->getTerms();
		$terms->setValues( $termvalues );
		$form->addItem( $terms );

		// Matching Pairs
		include_once "./Modules/TestQuestionPool/classes/class.ilMatchingPairWizardInputGUI.php";
		$pairs = new ilMatchingPairWizardInputGUI($this->lng->txt( 'matching_pairs' ), 'pairs');
		$pairs->setRequired( true );
		$pairs->setTerms( $this->object->getTerms() );
		$pairs->setDefinitions( $this->object->getDefinitions() );
		include_once "./Modules/TestQuestionPool/classes/class.assAnswerMatchingPair.php";
		if (count( $this->object->getMatchingPairs() ) == 0)
		{
			$this->object->addMatchingPair( new assAnswerMatchingPair($termvalues[0], $definitionvalues[0], 0) );
		}
		$pairs->setPairs( $this->object->getMatchingPairs() );
		$form->addItem( $pairs );
		return $form;
	}

	public function populateQuestionSpecificFormPart(\ilPropertyFormGUI $form)
	{
		// Edit mode
		$hidden = new ilHiddenInputGUI("matching_type");
		$hidden->setValue($matchingtype);
		$form->addItem($hidden);

		if (!$this->object->getSelfAssessmentEditingMode())
		{
			// shuffle
			$shuffle         = new ilSelectInputGUI($this->lng->txt( "shuffle_answers" ), "shuffle");
			$shuffle_options = array(
				0 => $this->lng->txt( "no" ),
				1 => $this->lng->txt( "matching_shuffle_terms_definitions" ),
				2 => $this->lng->txt( "matching_shuffle_terms" ),
				3 => $this->lng->txt( "matching_shuffle_definitions" )
			);
			$shuffle->setOptions( $shuffle_options );
			$shuffle->setValue($this->object->getShuffle() != null ? $this->object->getShuffle() : 1);
			$shuffle->setRequired( FALSE );
			$form->addItem( $shuffle );

			$element_height = new ilNumberInputGUI($this->lng->txt( "element_height" ), "element_height");
			$element_height->setValue( $this->object->getElementHeight() );
			$element_height->setRequired( false );
			$element_height->setMaxLength( 6 );
			$element_height->setMinValue( 20 );
			$element_height->setSize( 6 );
			$element_height->setInfo( $this->lng->txt( "element_height_info" ) );
			$form->addItem( $element_height );

			$geometry = new ilNumberInputGUI($this->lng->txt( "thumb_geometry" ), "thumb_geometry");
			$geometry->setValue( $this->object->getThumbGeometry() );
			$geometry->setRequired( true );
			$geometry->setMaxLength( 6 );
			$geometry->setMinValue( 20 );
			$geometry->setSize( 6 );
			$geometry->setInfo( $this->lng->txt( "thumb_geometry_info" ) );
			$form->addItem( $geometry );
		}
	}

	function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = FALSE, $user_post_solution = FALSE)
	{
		$test_output = $this->getTestOutput($active_id, $pass, $is_postponed, $user_post_solution); 
		$this->tpl->setVariable("QUESTION_OUTPUT", $test_output);
		$this->tpl->setVariable("FORMACTION", $formaction);
	}

	/**
	* Get the question solution output
	*
	* @param integer $active_id The active user id
	* @param integer $pass The test pass
	* @param boolean $graphicalOutput Show visual feedback for right/wrong answers
	* @param boolean $result_output Show the reached points for parts of the question
	* @param boolean $show_question_only Show the question without the ILIAS content around
	* @param boolean $show_feedback Show the question feedback
	* @param boolean $show_correct_solution Show the correct solution instead of the user solution
	* @param boolean $show_manual_scoring Show specific information for the manual scoring output
	* @return The solution output of the question as HTML code
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
		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_matching_output_solution.html", TRUE, TRUE, "Modules/TestQuestionPool");
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html",TRUE, TRUE, "Modules/TestQuestionPool");
		
		$solutions = array();
		if (($active_id > 0) && (!$show_correct_solution))
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
			$solution_script .= "";
		}
		else
		{
			foreach ($this->object->getMatchingPairs() as $pair)
			{
				if( $pair->points <= 0 )
				{
					continue;
				}
				
				$solutions[] = array(
					"value1" => $pair->term->identifier,
					"value2" => $pair->definition->identifier,
					'points' => $pair->points
				);
			}
		}

		$i = 0;
		
		foreach ($solutions as $solution)
		{
			$definition = $this->object->getDefinitionWithIdentifier($solution['value2']);
			$term = $this->object->getTermWithIdentifier($solution['value1']);
			$points = $solution['points'];

			if (is_object($definition))
			{
				if (strlen($definition->picture))
				{
					$template->setCurrentBlock('definition_image');
					$template->setVariable('ANSWER_IMAGE_URL', $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $definition->picture);
					$template->setVariable('ANSWER_IMAGE_ALT', (strlen($definition->text)) ? ilUtil::prepareFormOutput($definition->text) : ilUtil::prepareFormOutput($definition->picture));
					$template->setVariable('ANSWER_IMAGE_TITLE', (strlen($definition->text)) ? ilUtil::prepareFormOutput($definition->text) : ilUtil::prepareFormOutput($definition->picture));
					$template->setVariable('URL_PREVIEW', $this->object->getImagePathWeb() . $definition->picture);
					$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
					$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
					$template->setVariable("TEXT_DEFINITION", (strlen($definition->text)) ? $this->lng->txt('definition') . ' ' . ($i+1) . ': ' . ilUtil::prepareFormOutput($definition->text) : $this->lng->txt('definition') . ' ' . ($i+1));
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock('definition_text');
					$template->setVariable("DEFINITION", $this->object->prepareTextareaOutput($definition->text, TRUE));
					$template->parseCurrentBlock();
				}
			}
			if (is_object($term))
			{
				if (strlen($term->picture))
				{
					$template->setCurrentBlock('term_image');
					$template->setVariable('ANSWER_IMAGE_URL', $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $term->picture);
					$template->setVariable('ANSWER_IMAGE_ALT', (strlen($term->text)) ? ilUtil::prepareFormOutput($term->text) : ilUtil::prepareFormOutput($term->picture));
					$template->setVariable('ANSWER_IMAGE_TITLE', (strlen($term->text)) ? ilUtil::prepareFormOutput($term->text) : ilUtil::prepareFormOutput($term->picture));
					$template->setVariable('URL_PREVIEW', $this->object->getImagePathWeb() . $term->picture);
					$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
					$template->setVariable("TEXT_TERM", (strlen($term->text)) ? $this->lng->txt('term') . ' ' . ($i+1) . ': ' . ilUtil::prepareFormOutput($term->text) : $this->lng->txt('term') . ' ' . ($i+1));
					$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock('term_text');
					$template->setVariable("TERM", $this->object->prepareTextareaOutput($term->text, TRUE));
					$template->parseCurrentBlock();
				}
				$i++;
			}
			if (($active_id > 0) && (!$show_correct_solution))
			{
				if ($graphicalOutput)
				{
					// output of ok/not ok icons for user entered solutions
					$ok = false;
					foreach ($this->object->getMatchingPairs() as $pair)
					{
						if( $this->isCorrectMatching($pair, $definition, $term) )
						{
							$ok = true;
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

			if ($result_output)
			{
				$resulttext = ($points == 1) ? "(%s " . $this->lng->txt("point") . ")" : "(%s " . $this->lng->txt("points") . ")"; 
				$template->setCurrentBlock("result_output");
				$template->setVariable("RESULT_OUTPUT", sprintf($resulttext, $points));
				$template->parseCurrentBlock();
			}

			$template->setCurrentBlock("row");
			if ($this->object->getEstimatedElementHeight() > 0)
			{
				$template->setVariable("ELEMENT_HEIGHT", " style=\"height: " . $this->object->getEstimatedElementHeight() . "px;\"");
			}
			$template->setVariable("TEXT_MATCHES", $this->lng->txt("matches"));
			$template->parseCurrentBlock();
		}

		$questiontext = $this->object->getQuestion();
		if ($show_question_text==true)
		{
			$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		}
		
		$questionoutput = $template->get();
		
		$feedback = '';
		if($show_feedback)
		{
			$fb = $this->getGenericFeedbackOutput($active_id, $pass);
			$feedback .=  strlen($fb) ? $fb : '';
			
			$fb = $this->getSpecificFeedbackOutput($active_id, $pass);
			$feedback .=  strlen($fb) ? $fb : '';
		}
		if (strlen($feedback)) $solutiontemplate->setVariable("FEEDBACK", $this->object->prepareTextareaOutput($feedback, true));
		
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

		$solutionoutput = $solutiontemplate->get(); 
		if (!$show_question_only)
		{
			// get page object output
			$solutionoutput = '<div class="ilc_question_Standard">'.$solutionoutput."</div>";
		}
		return $solutionoutput;
	}

	public function getPreviewJS($show_question_only = FALSE)
	{
		global $ilUser;
		
		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_matching_output_js.html", TRUE, TRUE, "Modules/TestQuestionPool");

		$jsswitch = "";
		if (strcmp($this->ctrl->getCmd(), 'preview') == 0)
		{
			if (array_key_exists('js', $_GET))
			{
				$ilUser->writePref('tst_javascript', $_GET['js']);
			}
			$jstemplate = new ilTemplate("tpl.il_as_qpl_javascript_switch.html", TRUE, TRUE, "Modules/TestQuestionPool");
			if ($ilUser->getPref("tst_javascript") == 1)
			{
				$jstemplate->setVariable("JAVASCRIPT_IMAGE", ilUtil::getImagePath("javascript_disable.png"));
				$jstemplate->setVariable("JAVASCRIPT_IMAGE_ALT", $this->lng->txt("disable_javascript"));
				$jstemplate->setVariable("JAVASCRIPT_IMAGE_TITLE", $this->lng->txt("disable_javascript"));
				$this->ctrl->setParameterByClass($this->ctrl->getCmdClass(), "js", "0");
				$jstemplate->setVariable("JAVASCRIPT_URL", $this->ctrl->getLinkTargetByClass($this->ctrl->getCmdClass(), $this->ctrl->getCmd()));
			}
			else
			{
				$jstemplate->setVariable("JAVASCRIPT_IMAGE", ilUtil::getImagePath("javascript.png"));
				$jstemplate->setVariable("JAVASCRIPT_IMAGE_ALT", $this->lng->txt("enable_javascript"));
				$jstemplate->setVariable("JAVASCRIPT_IMAGE_TITLE", $this->lng->txt("enable_javascript"));
				$this->ctrl->setParameterByClass($this->ctrl->getCmdClass(), "js", "1");
				$jstemplate->setVariable("JAVASCRIPT_URL", $this->ctrl->getLinkTargetByClass($this->ctrl->getCmdClass(), $this->ctrl->getCmd()));
			}
			$jsswitch = $jstemplate->get();
			if ($ilUser->getPref('tst_javascript')) $this->object->setOutputType(OUTPUT_JAVASCRIPT);
		}
		
		// shuffle output
		$terms = $this->object->getTerms();
		$definitions = $this->object->getDefinitions();
		switch ($this->object->getShuffle())
		{
			case 1:
				$terms = $this->object->pcArrayShuffle($terms);
				$definitions = $this->object->pcArrayShuffle($definitions);
				break;
			case 2:
				$terms = $this->object->pcArrayShuffle($terms);
				break;
			case 3:
				$definitions = $this->object->pcArrayShuffle($definitions);
				break;
		}

		include_once "./Services/YUI/classes/class.ilYuiUtil.php";
		ilYuiUtil::initDragDrop();

		// create definitions
		$counter = 0;
		foreach ($definitions as $definition)
		{
			if (strlen($definition->picture))
			{
				$template->setCurrentBlock("definition_picture");
				$template->setVariable("DEFINITION_ID", $definition->identifier);
				$template->setVariable("IMAGE_HREF", $this->object->getImagePathWeb() . $definition->picture);
				$thumbweb = $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $definition->picture;
				$thumb = $this->object->getImagePath() . $this->object->getThumbPrefix() . $definition->picture;
				if (!@file_exists($thumb)) $this->object->rebuildThumbnails();
				$template->setVariable("THUMBNAIL_HREF", $thumbweb);
				$template->setVariable("THUMB_ALT", $this->lng->txt("image"));
				$template->setVariable("THUMB_TITLE", $this->lng->txt("image"));
				$template->setVariable("TEXT_DEFINITION", (strlen($definition->text)) ? $this->object->prepareTextareaOutput($definition->text, TRUE) : '');
				$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
				$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
				$template->parseCurrentBlock();
			}
			else
			{
				$template->setCurrentBlock("definition_text");
				$template->setVariable("DEFINITION", $this->object->prepareTextareaOutput($definition->text, TRUE));
				$template->parseCurrentBlock();
			}

			$template->setCurrentBlock("droparea");
			$template->setVariable("ID_DROPAREA", $definition->identifier);
			$template->setVariable("QUESTION_ID", $this->object->getId());
			if ($this->object->getEstimatedElementHeight() > 0)
			{
				$template->setVariable("ELEMENT_HEIGHT", " style=\"height: " . $this->object->getEstimatedElementHeight() . "px;\"");
			}
			$template->parseCurrentBlock();

			$template->setCurrentBlock("init_dropareas");
			$template->setVariable("COUNTER", $counter++);
			$template->setVariable("ID_DROPAREA", $definition->identifier);
			$template->parseCurrentBlock();
		}


		// create terms
		$counter = 0;
		foreach ($terms as $term)
		{
			if (strlen($term->picture))
			{
				$template->setCurrentBlock("term_picture");
				$template->setVariable("TERM_ID", $term->identifier);
				$template->setVariable("IMAGE_HREF", $this->object->getImagePathWeb() . $term->picture);
				$thumbweb = $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $term->picture;
				$thumb = $this->object->getImagePath() . $this->object->getThumbPrefix() . $term->picture;
				if (!@file_exists($thumb)) $this->object->rebuildThumbnails();
				$template->setVariable("THUMBNAIL_HREF", $thumbweb);
				$template->setVariable("THUMB_ALT", $this->lng->txt("image"));
				$template->setVariable("THUMB_TITLE", $this->lng->txt("image"));
				$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
				$template->setVariable("TEXT_TERM", (strlen($term->text)) ? $this->object->prepareTextareaOutput($term->text, TRUE) : '');
				$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
				$template->parseCurrentBlock();
			}
			else
			{
				$template->setCurrentBlock("term_text");
				$template->setVariable("TERM_TEXT", $this->object->prepareTextareaOutput($term->text, TRUE));
				$template->parseCurrentBlock();
			}
			$template->setCurrentBlock("draggable");
			$template->setVariable("ID_DRAGGABLE", $term->identifier);
			if ($this->object->getEstimatedElementHeight() > 0)
			{
				$template->setVariable("ELEMENT_HEIGHT", " style=\"height: " . $this->object->getEstimatedElementHeight() . "px;\"");
			}
			$template->parseCurrentBlock();

			$template->setCurrentBlock("init_draggables");
			$template->setVariable("COUNTER", $counter++);
			$template->setVariable("ID_DRAGGABLE", $term->identifier);
			$template->parseCurrentBlock();
		}

		$template->setVariable("RESET_BUTTON", $this->lng->txt("reset_terms"));

		$this->tpl->setVariable("LOCATION_ADDITIONAL_STYLESHEET", ilUtil::getStyleSheetLocation("output", "test_javascript.css", "Modules/TestQuestionPool"));

		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $jsswitch . $template->get();
		if (!$show_question_only)
		{
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}
		return $questionoutput;
	}
	
	public function getPreview($show_question_only = FALSE)
	{
		global $ilUser;
		
		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_matching_output.html", TRUE, TRUE, "Modules/TestQuestionPool");

		$jsswitch = "";
		if (strcmp($this->ctrl->getCmd(), 'preview') == 0)
		{
			if (array_key_exists('js', $_GET))
			{
				$ilUser->writePref('tst_javascript', $_GET['js']);
			}
			$jstemplate = new ilTemplate("tpl.il_as_qpl_javascript_switch.html", TRUE, TRUE, "Modules/TestQuestionPool");
			if ($ilUser->getPref("tst_javascript") == 1)
			{
				$jstemplate->setVariable("JAVASCRIPT_IMAGE", ilUtil::getImagePath("javascript_disable.png"));
				$jstemplate->setVariable("JAVASCRIPT_IMAGE_ALT", $this->lng->txt("disable_javascript"));
				$jstemplate->setVariable("JAVASCRIPT_IMAGE_TITLE", $this->lng->txt("disable_javascript"));
				$this->ctrl->setParameterByClass($this->ctrl->getCmdClass(), "js", "0");
				$jstemplate->setVariable("JAVASCRIPT_URL", $this->ctrl->getLinkTargetByClass($this->ctrl->getCmdClass(), $this->ctrl->getCmd()));
			}
			else
			{
				$jstemplate->setVariable("JAVASCRIPT_IMAGE", ilUtil::getImagePath("javascript.png"));
				$jstemplate->setVariable("JAVASCRIPT_IMAGE_ALT", $this->lng->txt("enable_javascript"));
				$jstemplate->setVariable("JAVASCRIPT_IMAGE_TITLE", $this->lng->txt("enable_javascript"));
				$this->ctrl->setParameterByClass($this->ctrl->getCmdClass(), "js", "1");
				$jstemplate->setVariable("JAVASCRIPT_URL", $this->ctrl->getLinkTargetByClass($this->ctrl->getCmdClass(), $this->ctrl->getCmd()));
			}
			$jsswitch = $jstemplate->get();
			if ($ilUser->getPref('tst_javascript')) $this->object->setOutputType(OUTPUT_JAVASCRIPT);
		}
		
		if ($this->object->getOutputType() == OUTPUT_JAVASCRIPT)
		{
			return $this->getPreviewJS($show_question_only);
		}
		
		// shuffle output
		$terms = $this->object->getTerms();
		$definitions = $this->object->getDefinitions();
		switch ($this->object->getShuffle())
		{
			case 1:
				$terms = $this->object->pcArrayShuffle($terms);
				$definitions = $this->object->pcArrayShuffle($definitions);
				break;
			case 2:
				$terms = $this->object->pcArrayShuffle($terms);
				break;
			case 3:
				$definitions = $this->object->pcArrayShuffle($definitions);
				break;
		}

		foreach ($definitions as $key => $definition)
		{
			if (is_object($definition))
			{
				if (strlen($definition->picture))
				{
					$template->setCurrentBlock('definition_image');
					$template->setVariable('ANSWER_IMAGE_URL', $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $definition->picture);
					$template->setVariable('ANSWER_IMAGE_ALT', (strlen($definition->text)) ? ilUtil::prepareFormOutput($definition->text) : ilUtil::prepareFormOutput($definition->picture));
					$template->setVariable('ANSWER_IMAGE_TITLE', (strlen($definition->text)) ? ilUtil::prepareFormOutput($definition->text) : ilUtil::prepareFormOutput($definition->picture));
					$template->setVariable('URL_PREVIEW', $this->object->getImagePathWeb() . $definition->picture);
					$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
					$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
					$template->setVariable("TEXT_DEFINITION", (strlen($definition->text)) ? $this->lng->txt('definition') . ' ' . ($key+1) . ': ' . $this->object->prepareTextareaOutput($definition->text, TRUE) : $this->lng->txt('definition') . ' ' . ($key+1));
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock('definition_text');
					$template->setVariable("DEFINITION", $this->object->prepareTextareaOutput($definition->text, TRUE));
					$template->parseCurrentBlock();
				}
			}

			$template->setCurrentBlock('option');
			$template->setVariable("VALUE_OPTION", 0);
			$template->setVariable("TEXT_OPTION", ilUtil::prepareFormOutput($this->lng->txt('please_select')));
			$template->parseCurrentBlock();
			foreach ($terms as $key => $term)
			{
				$template->setCurrentBlock('option');
				$template->setVariable("VALUE_OPTION", $term->identifier);
				$template->setVariable("TEXT_OPTION", (strlen($term->text)) ? $this->lng->txt('term') . ' ' . ($key +1) . ': ' . ilUtil::prepareFormOutput($term->text) : $this->lng->txt('term') . ' ' . ($key+1));
				$template->parseCurrentBlock();
			}
			
			$template->setCurrentBlock('row');
			$template->setVariable("TEXT_MATCHES", $this->lng->txt("matches"));
			if ($this->object->getEstimatedElementHeight() > 0)
			{
				$template->setVariable("ELEMENT_HEIGHT", " style=\"height: " . $this->object->getEstimatedElementHeight() . "px;\"");
			}
			$template->setVariable("QUESTION_ID", $this->object->getId());
			$template->setVariable("DEFINITION_ID", $definition->identifier);
			$template->parseCurrentBlock();
		}

		foreach ($terms as $key => $term)
		{
			if (strlen($term->picture))
			{
				$template->setCurrentBlock('term_image');
				$template->setVariable('ANSWER_IMAGE_URL', $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $term->picture);
				$template->setVariable('ANSWER_IMAGE_ALT', (strlen($term->text)) ? ilUtil::prepareFormOutput($term->text) : ilUtil::prepareFormOutput($term->picture));
				$template->setVariable('ANSWER_IMAGE_TITLE', (strlen($term->text)) ? ilUtil::prepareFormOutput($term->text) : ilUtil::prepareFormOutput($term->picture));
				$template->setVariable('URL_PREVIEW', $this->object->getImagePathWeb() . $term->picture);
				$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
				$template->setVariable("TEXT_TERM", (strlen($term->text)) ? $this->lng->txt('term') . ' ' . ($key+1) . ': ' . $this->object->prepareTextareaOutput($term->text, TRUE) : $this->lng->txt('term') . ' ' . ($key+1));
				$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
				$template->parseCurrentBlock();
			}
			else
			{
				$template->setCurrentBlock('term_text');
				$template->setVariable("TERM", $this->object->prepareTextareaOutput($term->text, TRUE));
				$template->parseCurrentBlock();
			}
			$template->touchBlock('terms');
		}

		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$template->setVariable("TEXT_TERMS", ilUtil::prepareFormOutput($this->lng->txt('available_terms')));
		$template->setVariable('TEXT_SELECTION', ilUtil::prepareFormOutput($this->lng->txt('selection')));
		$questionoutput = $jsswitch . $template->get();
		if (!$show_question_only)
		{
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}
		return $questionoutput;
	}

	protected function sortDefinitionsBySolution($solution)
	{
		$neworder = array();
		foreach ($solution as $solution_values)
		{
			$id = $solution_values['value2'];
			array_push($neworder, $this->object->getDefinitionWithIdentifier($id));
		}
		return $neworder;
	}

	function getTestOutputJS($active_id, $pass = NULL, $is_postponed = FALSE, $user_post_solution = FALSE)
	{
		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_matching_output_js.html", TRUE, TRUE, "Modules/TestQuestionPool");

		if ($active_id)
		{
			$solutions = NULL;
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			if (is_array($user_post_solution)) 
			{ 
				$solutions = array();
				foreach ($user_post_solution['matching'][$this->object->getId()] as $definition => $term)
				{
					array_push($solutions, array("value1" => $term, "value2" => $definition));
				}
			}
			else
			{ 
				$solutions =& $this->object->getSolutionValues($active_id, $pass);
			}

			foreach ($solutions as $idx => $solution_value)
			{
				if ($this->object->getOutputType() == OUTPUT_JAVASCRIPT)
				{
					if (($solution_value["value2"] > -1) && ($solution_value["value1"] > -1))
					{
						$template->setCurrentBlock("restoreposition");
						$template->setVariable("TERM_ID", $solution_value["value1"]);
						$template->setVariable("PICTURE_DEFINITION_ID", $solution_value["value2"]);
						$template->parseCurrentBlock();
					}
				}
			}
		}

		// shuffle output
		$terms = $this->object->getTerms();
		$definitions = $this->object->getDefinitions();
		switch ($this->object->getShuffle())
		{
			case 1:
				$terms = $this->object->pcArrayShuffle($terms);
				if (count($solutions))
				{
					$definitions = $this->sortDefinitionsBySolution($solutions);
				}
				else
				{
					$definitions = $this->object->pcArrayShuffle($definitions);
				}
				break;
			case 2:
				$terms = $this->object->pcArrayShuffle($terms);
				break;
			case 3:
				if (count($solutions))
				{
					$definitions = $this->sortDefinitionsBySolution($solutions);
				}
				else
				{
					$definitions = $this->object->pcArrayShuffle($definitions);
				}
				break;
		}

		include_once "./Services/YUI/classes/class.ilYuiUtil.php";
		ilYuiUtil::initDragDrop();

		// create definitions
		$counter = 0;
		foreach ($definitions as $definition)
		{
			if (strlen($definition->picture))
			{
				$template->setCurrentBlock("definition_picture");
				$template->setVariable("DEFINITION_ID", $definition->identifier);
				$template->setVariable("IMAGE_HREF", $this->object->getImagePathWeb() . $definition->picture);
				$thumbweb = $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $definition->picture;
				$thumb = $this->object->getImagePath() . $this->object->getThumbPrefix() . $definition->picture;
				if (!@file_exists($thumb)) $this->object->rebuildThumbnails();
				$template->setVariable("THUMBNAIL_HREF", $thumbweb);
				$template->setVariable("THUMB_ALT", $this->lng->txt("image"));
				$template->setVariable("THUMB_TITLE", $this->lng->txt("image"));
				$template->setVariable("TEXT_DEFINITION", (strlen($definition->text)) ? ilUtil::prepareFormOutput($definition->text) : '');
				$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
				$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
				$template->parseCurrentBlock();
			}
			else
			{
				$template->setCurrentBlock("definition_text");
				$template->setVariable("DEFINITION", $this->object->prepareTextareaOutput($definition->text, true));
				$template->parseCurrentBlock();
			}

			$template->setCurrentBlock("droparea");
			$template->setVariable("ID_DROPAREA", $definition->identifier);
			$template->setVariable("QUESTION_ID", $this->object->getId());
			if ($this->object->getEstimatedElementHeight() > 0)
			{
				$template->setVariable("ELEMENT_HEIGHT", " style=\"height: " . $this->object->getEstimatedElementHeight() . "px;\"");
			}
			$template->parseCurrentBlock();

			$template->setCurrentBlock("init_dropareas");
			$template->setVariable("COUNTER", $counter++);
			$template->setVariable("ID_DROPAREA", $definition->identifier);
			$template->parseCurrentBlock();
		}


		// create terms
		$counter = 0;
		foreach ($terms as $term)
		{
			if (strlen($term->picture))
			{
				$template->setCurrentBlock("term_picture");
				$template->setVariable("TERM_ID", $term->identifier);
				$template->setVariable("IMAGE_HREF", $this->object->getImagePathWeb() . $term->picture);
				$thumbweb = $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $term->picture;
				$thumb = $this->object->getImagePath() . $this->object->getThumbPrefix() . $term->picture;
				if (!@file_exists($thumb)) $this->object->rebuildThumbnails();
				$template->setVariable("THUMBNAIL_HREF", $thumbweb);
				$template->setVariable("THUMB_ALT", $this->lng->txt("image"));
				$template->setVariable("THUMB_TITLE", $this->lng->txt("image"));
				$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
				$template->setVariable("TEXT_TERM", (strlen($term->text)) ? ilUtil::prepareFormOutput($term->text) : '');
				$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
				$template->parseCurrentBlock();
			}
			else
			{
				$template->setCurrentBlock("term_text");
				$template->setVariable("TERM_TEXT", $this->object->prepareTextareaOutput($term->text, true));
				$template->parseCurrentBlock();
			}
			$template->setCurrentBlock("draggable");
			$template->setVariable("ID_DRAGGABLE", $term->identifier);
			if ($this->object->getEstimatedElementHeight() > 0)
			{
				$template->setVariable("ELEMENT_HEIGHT", " style=\"height: " . $this->object->getEstimatedElementHeight() . "px;\"");
			}
			$template->parseCurrentBlock();

			$template->setCurrentBlock("init_draggables");
			$template->setVariable("COUNTER", $counter++);
			$template->setVariable("ID_DRAGGABLE", $term->identifier);
			$template->parseCurrentBlock();
		}

		$template->setVariable("RESET_BUTTON", $this->lng->txt("reset_terms"));

		$this->tpl->setVariable("LOCATION_ADDITIONAL_STYLESHEET", ilUtil::getStyleSheetLocation("output", "test_javascript.css", "Modules/TestQuestionPool"));

		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
		return $pageoutput;
	}

	function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $user_post_solution = FALSE)
	{
		if ($this->object->getOutputType() == OUTPUT_JAVASCRIPT)
		{
			return $this->getTestOutputJS($active_id, $pass, $is_postponed, $user_post_solution);
		}
		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_matching_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		
		if ($active_id)
		{
			$solutions = NULL;
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			if (is_array($user_post_solution)) 
			{ 
				$solutions = array();
				foreach ($user_post_solution['matching'][$this->object->getId()] as $definition => $term)
				{
					array_push($solutions, array("value1" => $term, "value2" => $definition));
				}
			}
			else
			{ 
				$solutions =& $this->object->getSolutionValues($active_id, $pass);
			}
		}

		
		// shuffle output
		$terms = $this->object->getTerms();
		$definitions = $this->object->getDefinitions();
		switch ($this->object->getShuffle())
		{
			case 1:
				$terms = $this->object->pcArrayShuffle($terms);
				if (count($solutions))
				{
					$definitions = $this->sortDefinitionsBySolution($solutions);
				}
				else
				{
					$definitions = $this->object->pcArrayShuffle($definitions);
				}
				break;
			case 2:
				$terms = $this->object->pcArrayShuffle($terms);
				break;
			case 3:
				if (count($solutions))
				{
					$definitions = $this->sortDefinitionsBySolution($solutions);
				}
				else
				{
					$definitions = $this->object->pcArrayShuffle($definitions);
				}
				break;
		}
		$maxcount = max(count($terms), count($definitions));
		foreach ($definitions as $key => $definition)
		{
			if (is_object($definition))
			{
				if (strlen($definition->picture))
				{
					$template->setCurrentBlock('definition_image');
					$template->setVariable('ANSWER_IMAGE_URL', $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $definition->picture);
					$template->setVariable('ANSWER_IMAGE_ALT', (strlen($definition->text)) ? ilUtil::prepareFormOutput($definition->text) : ilUtil::prepareFormOutput($definition->picture));
					$template->setVariable('ANSWER_IMAGE_TITLE', (strlen($definition->text)) ? ilUtil::prepareFormOutput($definition->text) : ilUtil::prepareFormOutput($definition->picture));
					$template->setVariable('URL_PREVIEW', $this->object->getImagePathWeb() . $definition->picture);
					$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
					$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
					$template->setVariable("TEXT_DEFINITION", (strlen($definition->text)) ? $this->lng->txt('definition') . ' ' . ($key+1) . ': ' . ilUtil::prepareFormOutput($definition->text) : $this->lng->txt('definition') . ' ' . ($key+1));
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock('definition_text');
					$template->setVariable("DEFINITION", $this->object->prepareTextareaOutput($definition->text, true));
					$template->parseCurrentBlock();
				}
			}

			$template->setCurrentBlock('option');
			$template->setVariable("VALUE_OPTION", 0);
			$template->setVariable("TEXT_OPTION", ilUtil::prepareFormOutput($this->lng->txt('please_select')));
			$template->parseCurrentBlock();
			foreach ($terms as $key => $term)
			{
				$template->setCurrentBlock('option');
				$template->setVariable("VALUE_OPTION", $term->identifier);
				$template->setVariable("TEXT_OPTION", (strlen($term->text)) ? $this->lng->txt('term') . ' ' . ($key+1) . ': ' . ilUtil::prepareFormOutput($term->text) : $this->lng->txt('term') . ' ' . ($key +1));
				foreach ($solutions as $solution)
				{
					if ($solution["value1"] == $term->identifier && $solution["value2"] == $definition->identifier)
					{
						$template->setVariable("SELECTED_OPTION", " selected=\"selected\"");
					}
				}
				$template->parseCurrentBlock();
			}
			
			$template->setCurrentBlock('row');
			$template->setVariable("TEXT_MATCHES", $this->lng->txt("matches"));
			if ($this->object->getEstimatedElementHeight() > 0)
			{
				$template->setVariable("ELEMENT_HEIGHT", " style=\"height: " . $this->object->getEstimatedElementHeight() . "px;\"");
			}
			$template->setVariable("QUESTION_ID", $this->object->getId());
			$template->setVariable("DEFINITION_ID", $definition->identifier);
			$template->parseCurrentBlock();
		}
		foreach ($terms as $key=>$term)
		{
			if (strlen($term->picture))
			{
				$template->setCurrentBlock('term_image');
				$template->setVariable('ANSWER_IMAGE_URL', $this->object->getImagePathWeb() . $this->object->getThumbPrefix() . $term->picture);
				$template->setVariable('ANSWER_IMAGE_ALT', (strlen($term->text)) ? ilUtil::prepareFormOutput($term->text) : ilUtil::prepareFormOutput($term->picture));
				$template->setVariable('ANSWER_IMAGE_TITLE', (strlen($term->text)) ? ilUtil::prepareFormOutput($term->text) : ilUtil::prepareFormOutput($term->picture));
				$template->setVariable('URL_PREVIEW', $this->object->getImagePathWeb() . $term->picture);
				$template->setVariable("TEXT_PREVIEW", $this->lng->txt('preview'));
				$template->setVariable("TEXT_TERM", (strlen($term->text)) ? $this->lng->txt('term') . ' ' . ($key+1) . ': ' . ilUtil::prepareFormOutput($term->text) : $this->lng->txt('term') . ' ' . ($key+1));
				$template->setVariable("IMG_PREVIEW", ilUtil::getImagePath('enlarge.png'));
				$template->parseCurrentBlock();
			}
			else
			{
				$template->setCurrentBlock('term_text');
				$template->setVariable("TERM", $this->object->prepareTextareaOutput($term->text, true));
				$template->parseCurrentBlock();
			}
			$template->touchBlock('terms');
		}

		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$template->setVariable("TEXT_TERMS", ilUtil::prepareFormOutput($this->lng->txt('available_terms')));
		$template->setVariable('TEXT_SELECTION', ilUtil::prepareFormOutput($this->lng->txt('selection')));

		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
		return $pageoutput;

	}

	/**
	* check input fields
	*/
	function checkInput()
	{
		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]))
		{
			return false;
		}
		return true;
	}

	/**
	 * Sets the ILIAS tabs for this question type
	 *
	 * @access public
	 * 
	 * @todo:	MOVE THIS STEPS TO COMMON QUESTION CLASS assQuestionGUI
	 */
	function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;
		
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
	
			// edit page
			$ilTabs->addTarget("preview",
				$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "preview"),
				array("preview"),
				"ilAssQuestionPageGUI", "", $force_active);
		}

		$force_active = false;
		if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
		{
			$url = "";
			if ($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			// edit question properties
			$ilTabs->addTarget("edit_question",
				$url,
				array("editQuestion", "save", "saveEdit", "removeimageterms", "uploadterms", "removeimagedefinitions", "uploaddefinitions",
					"addpairs", "removepairs", "addterms", "removeterms", "adddefinitions", "removedefinitions", "originalSyncForm"),
				$classname, "", $force_active);
		}

		// add tab for question feedback within common class assQuestionGUI
		$this->addTab_QuestionFeedback($ilTabs);

		// add tab for question hint within common class assQuestionGUI
		$this->addTab_QuestionHints($ilTabs);
		
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("solution_hint",
				$this->ctrl->getLinkTargetByClass($classname, "suggestedsolution"),
				array("suggestedsolution", "saveSuggestedSolution", "outSolutionExplorer", "cancel", 
				"addSuggestedSolution","cancelExplorer", "linkChilds", "removeSuggestedSolution"
				),
				$classname, 
				""
			);
		}

		// Assessment of questions sub menu entry
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, "");
		}
		
		if (($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0))
		{
			$ref_id = $_GET["calling_test"];
			if (strlen($ref_id) == 0) $ref_id = $_GET["test_ref_id"];

                        global $___test_express_mode;

                        if (!$_GET['test_express_mode'] && !$___test_express_mode) {
                            $ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id");
                        }
                        else {
                            $link = ilTestExpressPage::getReturnToPageLink();
                            $ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), $link);
                        }
		}
		else
		{
			$ilTabs->setBackTarget($this->lng->txt("qpl"), $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions"));
		}
	}

	function getSpecificFeedbackOutput($active_id, $pass)
	{
		$feedback = '<table><tbody>';

		foreach ($this->object->getMatchingPairs() as $idx => $ans)
		{
			$fb = $this->object->feedbackOBJ->getSpecificAnswerFeedbackTestPresentation(
					$this->object->getId(), $idx
			);
			$feedback .= '<tr><td><b><i>' . $ans->definition->text . '</i></b></td><td>'. $this->lng->txt("matches") . '&nbsp;';
			$feedback .= '</td><td><b><i>' . $ans->term->text . '</i></b></td><td>&nbsp;</td><td>';
			$feedback .= $fb . '</td> </tr>';
		}

		$feedback .= '</tbody></table>';
		return $this->object->prepareTextareaOutput($feedback, TRUE);
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
		return array('shuffle', 'element_height', 'thumb_geometry');
	}

	public function resetFormValuesForSuppressedPostvars($form)
	{
		$element = $form->getItemByPostvar('thumb_geometry');
		$_POST['thumb_geometry'] = $this->object->getThumbGeometry();
		$element->setValue(	$this->object->getThumbGeometry() );
	}

	public function reworkFormForCorrectionMode(ilPropertyFormGUI $form)
	{
		foreach(array('definitions','terms') as $postvar)
		{
			/** @var ilMatchingWizardInputGUI $matching_wizardinputgui */
			$matching_wizardinputgui = $form->getItemByPostVar($postvar);
			$matching_wizardinputgui->setDisableUpload(true);
			$matching_wizardinputgui->setDisableActions(true);
			$matching_wizardinputgui->setDisableText(true);
		}
		return $form;
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
		$passes = array();
		foreach($relevant_answers as $pass)
		{
			$passes[$pass['active_fi'].'-'.$pass['pass']] = '-';
		}
		$passcount = count($passes);
		
		foreach($relevant_answers as $pass)
		{
			$actives[$pass['active_fi']] = $pass['active_fi'];
		}
		$usercount = count($actives);
		$tpl = new ilTemplate('tpl.il_as_aggregated_answers_header.html', true, true, "Modules/TestQuestionPool");
		$tpl->setVariable('HEADERTEXT', $this->lng->txt('overview'));
		$tpl->setVariable('NUMBER_OF_USERS_INFO', $this->lng->txt('number_of_users'));
		$tpl->setVariable('NUMBER_OF_USERS', $usercount);
		$tpl->setVariable('NUMBER_OF_PASSES_INFO', $this->lng->txt('number_of_passes'));
		$tpl->setVariable('NUMBER_OF_PASSES', $passcount);

		$header = $tpl->get();

		$variants = $this->renderVariantsView(
			$this->aggregateAnswerVariants($relevant_answers, $this->object->getTerms(), $this->object->getDefinitions())
		)->get();

		return  $header . $variants ;
	}

	public function aggregateAnswerVariants($relevant_answers_chosen, $terms, $definitions)
	{
		$variants = array();
		$passdata = array();
		foreach ($relevant_answers_chosen as $relevant_answer)
		{
			$pass_ident = $relevant_answer['active_fi'].$relevant_answer['pass'];
			$answer = $passdata[$pass_ident];
			if (strlen($answer))
			{
				$answer_elements = explode(',', $answer);
			} else {
				$answer_elements = array();
			}
			$answer_elements[] = $relevant_answer['value1'].'.'.$relevant_answer['value2'];
			$passdata[$pass_ident] = implode(',',$answer_elements);
		}
		foreach($passdata as $passident => $behaviour)
		{
			$variants[$behaviour]++;
		}
		arsort($variants);
		return $variants;
	}

	public function renderVariantsView($aggregate)
	{
		$tpl = new ilTemplate( 'tpl.il_as_aggregated_answers_table.html', true, true, "Modules/TestQuestionPool" );
		$tpl->setVariable( 'OPTION_HEADER', $this->lng->txt( 'answer_variant' ) );
		$tpl->setVariable( 'COUNT_HEADER', $this->lng->txt( 'count' ) );
		$tpl->setVariable( 'AGGREGATION_HEADER', $this->lng->txt( 'aggregated_answers_variants' ) );
		foreach ($aggregate as $options => $count)
		{
			$tpl->setCurrentBlock( 'aggregaterow' );
			$optionstext = array();
			foreach (explode( ',', $options ) as $option)
			{
				$pair = explode('.',$option);
				if($pair[0] == -1 || $pair[1] == -1)
				{
					continue;
				}

				$term = $this->object->getTermWithIdentifier($pair[0]);
				$term_rep = $term->text;
				if($term->picture)
				{
					$term_rep .= '&nbsp;<img src="' 
						. $this->object->getImagePathWeb() 
						. $this->object->getThumbPrefix() 
						. $term->picture 
						. '" />';
				}

				$definition = $this->object->getDefinitionWithIdentifier($pair[1]);
				$definition_rep = $definition->text;
				if($definition->picture)
				{
					$definition_rep .= '&nbsp;<img src="' 
						. $this->object->getImagePathWeb() 
						. $this->object->getThumbPrefix() 
						. $definition->picture 
						. '" />';
				}

				$optionstext[] = $definition_rep. '&nbsp;-&gt;&nbsp;'. $term_rep;
			}
			$tpl->setVariable( 'OPTION', implode( '<br />', $optionstext ) );
			$tpl->setVariable( 'COUNT', $count );
			$tpl->parseCurrentBlock();
		}

		return $tpl;
	}
	
	private function isCorrectMatching($pair, $definition, $term)
	{
		if( !($pair->points > 0) )
		{
			return false;
		}
		
		if( !is_object($term) )
		{
			return false;
		}

		if( $pair->definition->identifier != $definition->identifier )
		{
			return false;
		}

		if( $pair->term->identifier != $term->identifier )
		{
			return false;
		}
		
		return true;
	}
}