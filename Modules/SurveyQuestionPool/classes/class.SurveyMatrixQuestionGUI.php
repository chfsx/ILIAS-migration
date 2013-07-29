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

include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestionGUI.php";

/**
* Matrix question GUI representation
*
* The SurveyMatrixQuestionGUI class encapsulates the GUI representation
* for matrix question types.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @extends SurveyQuestionGUI
* @ingroup ModulesSurveyQuestionPool
*/
class SurveyMatrixQuestionGUI extends SurveyQuestionGUI 
{
	protected $show_layout_row;
	
	protected function initObject()
	{
		include_once "./Modules/SurveyQuestionPool/classes/class.SurveyMatrixQuestion.php";
		$this->object = new SurveyMatrixQuestion();
	}

	/*
	function &executeCommand()
	{
		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass($this);

		$cmd = $this->getCommand($cmd);
		switch($next_class)
		{
			default:
				$ret =& $this->$cmd();
				break;
		}
		return $ret;
	}
	*/
	
	
	// 
	// EDITOR
	//
	
	public function setQuestionTabs()
	{
		$this->setQuestionTabsForClass("surveymatrixquestiongui");
	}

	protected function addFieldsToEditForm(ilPropertyFormGUI $a_form)
	{	
		// subtype
		$subtype = new ilRadioGroupInputGUI($this->lng->txt("subtype"), "type");
		$subtype->setRequired(false);	
		$subtypes = array(
			"0" => "matrix_subtype_sr",
			"1" => "matrix_subtype_mr",
			//"2" => "matrix_subtype_text",
			//"3" => "matrix_subtype_integer",
			//"4" => "matrix_subtype_double",
			//"5" => "matrix_subtype_date",
			//"6" => "matrix_subtype_time"
		);
		foreach ($subtypes as $idx => $st)
		{
			$subtype->addOption(new ilRadioOption($this->lng->txt($st), $idx));
		}
		$a_form->addItem($subtype);

		
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->lng->txt("matrix_appearance"));
		$a_form->addItem($header);
		
		// column separators
		$column_separators = new ilCheckboxInputGUI($this->lng->txt("matrix_column_separators"), "column_separators");
		$column_separators->setValue(1);
		$column_separators->setInfo($this->lng->txt("matrix_column_separators_description"));	
		$column_separators->setRequired(false);
		$a_form->addItem($column_separators);

		// row separators
		$row_separators = new ilCheckboxInputGUI($this->lng->txt("matrix_row_separators"), "row_separators");
		$row_separators->setValue(1);
		$row_separators->setInfo($this->lng->txt("matrix_row_separators_description"));		
		$row_separators->setRequired(false);
		$a_form->addItem($row_separators);

		// neutral column separators
		$neutral_column_separator = new ilCheckboxInputGUI($this->lng->txt("matrix_neutral_column_separator"), "neutral_column_separator");
		$neutral_column_separator->setValue(1);
		$neutral_column_separator->setInfo($this->lng->txt("matrix_neutral_column_separator_description"));	
		$neutral_column_separator->setRequired(false);
		$a_form->addItem($neutral_column_separator);

		
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->lng->txt("matrix_columns"));
		$a_form->addItem($header);
		
		// Answers
		include_once "./Modules/SurveyQuestionPool/classes/class.ilCategoryWizardInputGUI.php";
		$columns = new ilCategoryWizardInputGUI("", "columns");
		$columns->setRequired(false);
		$columns->setAllowMove(true);
		$columns->setShowWizard(true);
		$columns->setShowNeutralCategory(true);
		$columns->setDisabledScale(false);
		$columns->setNeutralCategoryTitle($this->lng->txt('matrix_neutral_answer'));
		$columns->setCategoryText($this->lng->txt('matrix_standard_answers'));
		$columns->setShowSavePhrase(true);		
		$a_form->addItem($columns);
		
		
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->lng->txt("matrix_column_settings"));
		$a_form->addItem($header);
		
		// bipolar adjectives
		$bipolar = new ilCustomInputGUI($this->lng->txt("matrix_bipolar_adjectives"));
		$bipolar->setInfo($this->lng->txt("matrix_bipolar_adjectives_description"));
		
		// left pole
		$bipolar1 = new ilTextInputGUI($this->lng->txt("matrix_left_pole"), "bipolar1");		
		$bipolar1->setRequired(false);
		$bipolar->addSubItem($bipolar1);
		
		// right pole
		$bipolar2 = new ilTextInputGUI($this->lng->txt("matrix_right_pole"), "bipolar2");		
		$bipolar2->setRequired(false);
		$bipolar->addSubItem($bipolar2);

		$a_form->addItem($bipolar);
		

		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->lng->txt("matrix_rows"));
		$a_form->addItem($header);

		// matrix rows
		include_once "./Modules/SurveyQuestionPool/classes/class.ilMatrixRowWizardInputGUI.php";
		$rows = new ilMatrixRowWizardInputGUI("", "rows");
		$rows->setRequired(false);
		$rows->setAllowMove(true);
		$rows->setLabelText($this->lng->txt('label'));
		$rows->setUseOtherAnswer(true);	
		$a_form->addItem($rows);
		
		
		// values
		$subtype->setValue($this->object->getSubtype());		
		$column_separators->setChecked($this->object->getColumnSeparators());
		$row_separators->setChecked($this->object->getRowSeparators());
		$neutral_column_separator->setChecked($this->object->getNeutralColumnSeparator());
		
		if (!$this->object->getColumnCount())
		{
			$this->object->columns->addCategory("");
		}
		$columns->setValues($this->object->getColumns());
		
		$bipolar1->setValue($this->object->getBipolarAdjective(0));
		$bipolar2->setValue($this->object->getBipolarAdjective(1));
		
		if ($this->object->getRowCount() == 0)
		{
			$this->object->getRows()->addCategory("");
		}
		$rows->setValues($this->object->getRows());
	}
	
	protected function importEditFormValues(ilPropertyFormGUI $a_form)
	{
		$this->object->setSubtype($a_form->getInput("type"));
		$this->object->setRowSeparators($a_form->getInput("row_separators") ? 1 : 0);
		$this->object->setColumnSeparators($a_form->getInput("column_separators") ? 1 : 0);
		$this->object->setNeutralColumnSeparator($a_form->getInput("neutral_column_separator") ? 1 : 0);
		
		// Set bipolar adjectives
		$this->object->setBipolarAdjective(0, $a_form->getInput("bipolar1"));
		$this->object->setBipolarAdjective(1, $a_form->getInput("bipolar2"));
		
		// set columns
		$this->object->flushColumns();
		
		foreach ($_POST['columns']['answer'] as $key => $value)
		{
			if (strlen($value)) $this->object->getColumns()->addCategory($value, $_POST['columns']['other'][$key], 0, null, $_POST['columns']['scale'][$key]);
		}
		if (strlen($_POST["columns"]["neutral"]))
		{
			$this->object->getColumns()->addCategory($_POST['columns']['neutral'], 0, 1, null, $_POST['columns_neutral_scale']);
		}
		
		// set rows
		$this->object->flushRows();
		foreach ($_POST['rows']['answer'] as $key => $value)
		{
			if (strlen($value)) $this->object->getRows()->addCategory($value, $_POST['rows']['other'][$key], 0, $_POST['rows']['label'][$key]);
		}
	}
	
	/**
	* Creates a HTML representation of the question
	*
	* @access private
	*/
	function getPrintView($question_title = 1, $show_questiontext = 1)
	{
		$layout = $this->object->getLayout();
		$neutralstyle = "3px solid #808080";
		$bordercolor = "#808080";
		$template = new ilTemplate("tpl.il_svy_qpl_matrix_printview.html", TRUE, TRUE, "Modules/SurveyQuestionPool");

		if ($this->show_layout_row)
		{
			$layout_row = $this->getLayoutRow();
			$template->setCurrentBlock("matrix_row");
			$template->setVariable("ROW", $layout_row);
			$template->parseCurrentBlock();
		}
		
		$tplheaders = new ilTemplate("tpl.il_svy_out_matrix_columnheaders.html", TRUE, TRUE, "Modules/SurveyQuestionPool");
		if ((strlen($this->object->getBipolarAdjective(0))) && (strlen($this->object->getBipolarAdjective(1))))
		{
			$tplheaders->setCurrentBlock("bipolar_start");
			$style = array();
			array_push($style, sprintf("width: %.2f%s!important", $layout["percent_bipolar_adjective1"], "%"));
			if (count($style) > 0)
			{
				$tplheaders->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
			}
			$tplheaders->parseCurrentBlock();
		}
		// column headers
		for ($i = 0; $i < $this->object->getColumnCount(); $i++)
		{
			$cat = $this->object->getColumn($i);
			if ($cat->neutral)
			{
				$tplheaders->setCurrentBlock("neutral_column_header");
				$tplheaders->setVariable("TEXT", ilUtil::prepareFormOutput($cat->title));
				$tplheaders->setVariable("CLASS", "rsep");
				$style = array();
				array_push($style, sprintf("width: %.2f%s!important", $layout["percent_neutral"], "%"));
				if ($this->object->getNeutralColumnSeparator())
				{
					array_push($style, "border-left: $neutralstyle!important;");
				}
				if (count($style) > 0)
				{
					$tplheaders->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
				}
				$tplheaders->parseCurrentBlock();
			}
			else
			{
				$style = array();
				if ($this->object->getColumnSeparators() == 1)
				{
					if (($i < $this->object->getColumnCount() - 1))
					{
						array_push($style, "border-right: 1px solid $bordercolor!important");
					}
				}
				array_push($style, sprintf("width: %.2f%s!important", $layout["percent_columns"] / $this->object->getColumnCount(), "%"));
				$tplheaders->setCurrentBlock("column_header");
				$tplheaders->setVariable("TEXT", ilUtil::prepareFormOutput($cat->title));
				$tplheaders->setVariable("CLASS", "center");
				if (count($style) > 0)
				{
					$tplheaders->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
				}
				$tplheaders->parseCurrentBlock();
			}
		}

		if ((strlen($this->object->getBipolarAdjective(0))) && (strlen($this->object->getBipolarAdjective(1))))
		{
			$tplheaders->setCurrentBlock("bipolar_end");
			$style = array();
			array_push($style, sprintf("width: %.2f%s!important", $layout["percent_bipolar_adjective2"], "%"));
			if (count($style) > 0)
			{
				$tplheaders->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
			}
			$tplheaders->parseCurrentBlock();
		}

		$style = array();
		array_push($style, sprintf("width: %.2f%s!important", $layout["percent_row"], "%"));
		if (count($style) > 0)
		{
			$tplheaders->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
		}
		
		$template->setCurrentBlock("matrix_row");
		$template->setVariable("ROW", $tplheaders->get());
		$template->parseCurrentBlock();

		$rowclass = array("tblrow1", "tblrow2");
		
		for ($i = 0; $i < $this->object->getRowCount(); $i++)
		{
			$rowobj = $this->object->getRow($i);
			$tplrow = new ilTemplate("tpl.il_svy_qpl_matrix_printview_row.html", TRUE, TRUE, "Modules/SurveyQuestionPool");
			for ($j = 0; $j < $this->object->getColumnCount(); $j++)
			{
				$cat = $this->object->getColumn($j);
				if (($i == 0) && ($j == 0))
				{
					if ((strlen($this->object->getBipolarAdjective(0))) && (strlen($this->object->getBipolarAdjective(1))))
					{
						$tplrow->setCurrentBlock("bipolar_start");
						$tplrow->setVariable("TEXT_BIPOLAR_START", ilUtil::prepareFormOutput($this->object->getBipolarAdjective(0)));
						$tplrow->setVariable("ROWSPAN", $this->object->getRowCount());
						$tplrow->parseCurrentBlock();
					}
				}
				if (($i == 0) && ($j == $this->object->getColumnCount()-1))
				{
					if ((strlen($this->object->getBipolarAdjective(0))) && (strlen($this->object->getBipolarAdjective(1))))
					{
						$tplrow->setCurrentBlock("bipolar_end");
						$tplrow->setVariable("TEXT_BIPOLAR_END", ilUtil::prepareFormOutput($this->object->getBipolarAdjective(1)));
						$tplrow->setVariable("ROWSPAN", $this->object->getRowCount());
						$tplrow->parseCurrentBlock();
					}
				}
				switch ($this->object->getSubtype())
				{
					case 0:
						if ($cat->neutral)
						{
							$tplrow->setCurrentBlock("neutral_radiobutton");
							$tplrow->setVariable("IMAGE_RADIO", ilUtil::getHtmlPath(ilUtil::getImagePath("radiobutton_unchecked.png")));
							$tplrow->setVariable("ALT_RADIO", $this->lng->txt("unchecked"));
							$tplrow->setVariable("TITLE_RADIO", $this->lng->txt("unchecked"));
							$tplrow->parseCurrentBlock();
						}
						else
						{
							$tplrow->setCurrentBlock("radiobutton");
							$tplrow->setVariable("IMAGE_RADIO", ilUtil::getHtmlPath(ilUtil::getImagePath("radiobutton_unchecked.png")));
							$tplrow->setVariable("ALT_RADIO", $this->lng->txt("unchecked"));
							$tplrow->setVariable("TITLE_RADIO", $this->lng->txt("unchecked"));
							$tplrow->parseCurrentBlock();
						}
						break;
					case 1:
						if ($cat->neutral)
						{
							$tplrow->setCurrentBlock("neutral_checkbox");
							$tplrow->setVariable("IMAGE_CHECKBOX", ilUtil::getHtmlPath(ilUtil::getImagePath("checkbox_unchecked.png")));
							$tplrow->setVariable("ALT_CHECKBOX", $this->lng->txt("unchecked"));
							$tplrow->setVariable("TITLE_CHECKBOX", $this->lng->txt("unchecked"));
							$tplrow->parseCurrentBlock();
						}
						else
						{
							$tplrow->setCurrentBlock("checkbox");
							$tplrow->setVariable("IMAGE_CHECKBOX", ilUtil::getHtmlPath(ilUtil::getImagePath("checkbox_unchecked.png")));
							$tplrow->setVariable("ALT_CHECKBOX", $this->lng->txt("unchecked"));
							$tplrow->setVariable("TITLE_CHECKBOX", $this->lng->txt("unchecked"));
							$tplrow->parseCurrentBlock();
						}
						break;
				}
				if ($cat->neutral)
				{
					$tplrow->setCurrentBlock("neutral_answer");
					$style = array();
					if ($this->object->getNeutralColumnSeparator())
					{
						array_push($style, "border-left: $neutralstyle!important");
					}
					if ($this->object->getColumnSeparators() == 1)
					{
						if ($j < $this->object->getColumnCount() - 1)
						{
							array_push($style, "border-right: 1px solid $bordercolor!important");
						}
					}

					if ($this->object->getRowSeparators() == 1)
					{
						if ($i < $this->object->getRowCount() - 1)
						{
							array_push($style, "border-bottom: 1px solid $bordercolor!important");
						}
					}
					if (count($style))
					{
						$tplrow->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
					}
					$tplrow->parseCurrentBlock();
				}
				else
				{
					$tplrow->setCurrentBlock("answer");
					$style = array();

					if ($this->object->getColumnSeparators() == 1)
					{
						if ($j < $this->object->getColumnCount() - 1)
						{
							array_push($style, "border-right: 1px solid $bordercolor!important");
						}
					}

					if ($this->object->getRowSeparators() == 1)
					{
						if ($i < $this->object->getRowCount() - 1)
						{
							array_push($style, "border-bottom: 1px solid $bordercolor!important");
						}
					}
					if (count($style))
					{
						$tplrow->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
					}
					$tplrow->parseCurrentBlock();
				}
			}

			if ($rowobj->other)
			{
				$tplrow->setCurrentBlock("text_other");
				$tplrow->setVariable("TEXT_OTHER", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
				$tplrow->parseCurrentBlock();
			}

			$tplrow->setVariable("TEXT_ROW", ilUtil::prepareFormOutput($rowobj->title));
			$tplrow->setVariable("ROWCLASS", $rowclass[$i % 2]);
			if ($this->object->getRowSeparators() == 1)
			{
				if ($i < $this->object->getRowCount() - 1)
				{
					$tplrow->setVariable("STYLE", " style=\"border-bottom: 1px solid $bordercolor!important\"");
				}
			}
			$template->setCurrentBlock("matrix_row");
			$template->setVariable("ROW", $tplrow->get());
			$template->parseCurrentBlock();
		}
		
		if ($question_title)
		{
			$template->setVariable("QUESTION_TITLE", ilUtil::prepareFormOutput($this->object->getTitle()));
		}
		$template->setCurrentBlock();
		if ($show_questiontext)
		{
			$this->outQuestionText($template);
		}
		$template->parseCurrentBlock();
		return $template->get();
	}

	
	//
	// PHRASES (see SurveySingleChoiceQuestionGUI)
	//
	
	/**
	* Creates an output for the addition of phrases
	*/
	 function wizardcolumns($save_post_data = true) 
	{
		if ($save_post_data) 
		{			
			$result = $this->saveForm();
		}		
		if ($result || !$save_post_data)
		{
			if ($save_post_data) 
			{
				$this->object->saveToDb();
			}
			
			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_addphrase.html", "Modules/SurveyQuestionPool");

			// set the id to return to the selected question
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("HIDDEN_NAME", "id");
			$this->tpl->setVariable("HIDDEN_VALUE", $this->object->getId());
			$this->tpl->parseCurrentBlock();

			include_once "./Modules/SurveyQuestionPool/classes/class.ilSurveyPhrases.php";
			$phrases =& ilSurveyPhrases::_getAvailablePhrases();
			$colors = array("tblrow1", "tblrow2");
			$counter = 0;
			foreach ($phrases as $phrase_id => $phrase_array)
			{
				$this->tpl->setCurrentBlock("phraserow");
				$this->tpl->setVariable("COLOR_CLASS", $colors[$counter++ % 2]);
				$this->tpl->setVariable("PHRASE_VALUE", $phrase_id);
				$this->tpl->setVariable("PHRASE_NAME", $phrase_array["title"]);
				$categories =& ilSurveyPhrases::_getCategoriesForPhrase($phrase_id);
				$this->tpl->setVariable("PHRASE_CONTENT", join($categories, ","));
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("adm_content");
			$this->tpl->setVariable("TEXT_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TEXT_PHRASE", $this->lng->txt("phrase"));
			$this->tpl->setVariable("TEXT_CONTENT", $this->lng->txt("categories"));
			$this->tpl->setVariable("TEXT_ADD_PHRASE", $this->lng->txt("add_phrase"));
			$this->tpl->setVariable("TEXT_INTRODUCTION",$this->lng->txt("add_phrase_introduction"));
			$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
			$this->tpl->parseCurrentBlock();
		}
	}
	
	/**
	* Creates an output to save the current answers as a phrase
	*
	* @access public
	*/
	function savePhrasecolumns($haserror = false) 
	{
		if (!$haserror) 
		{
			$result = $this->saveForm();
		}
		if ($result || $haserror)
		{
			if (!$haserror) 
			{
				$this->object->saveToDb();
			}

			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_savephrase.html", "Modules/SurveyQuestionPool");
			$this->tpl->setCurrentBlock("adm_content");
			$this->tpl->setVariable("SAVE_PHRASE_INTRODUCTION", $this->lng->txt("save_phrase_introduction"));
			$this->tpl->setVariable("TEXT_PHRASE_TITLE", $this->lng->txt("enter_phrase_title"));
			$this->tpl->setVariable("VALUE_PHRASE_TITLE", $_POST["phrase_title"]);
			$this->tpl->setVariable("BTN_CANCEL",$this->lng->txt("cancel"));
			$this->tpl->setVariable("BTN_CONFIRM",$this->lng->txt("confirm"));
			$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));

			include_once "./Modules/SurveyQuestionPool/classes/tables/class.ilSurveySavePhraseTableGUI.php";
			$table_gui = new ilSurveySavePhraseTableGUI($this, 'editQuestion');
			
			$data = array();
			if (!$haserror)
			{
				foreach ($_POST['columns']['answer'] as $key => $value)
				{
					array_push($data, array('answer' => $value, 'other' => (($_POST['columns']['other'][$key]) ? true : false), 'scale' => $_POST['columns']['scale'][$key], 'neutral' => false));
				}
				if (strlen($_POST['columns']['neutral']))
				{
					array_push($data, array('answer' => $_POST['columns']['neutral'], 'other' => false, 'scale' => $_POST['columns_neutral_scale'], 'neutral' => true));
				}
			}
			else
			{
				$data = $_SESSION['save_phrase_data'];
			}
			$table_gui->setData($data);
			$_SESSION['save_phrase_data'] = $data;
			$this->tpl->setVariable('TABLE', $table_gui->getHTML());	
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* Cancels the form saving a phrase
	*
	* @access public
	*/
	function cancelSavePhrase() 
	{
		ilUtil::sendInfo($this->lng->txt("msg_cancel"), true);
		$this->ctrl->redirect($this, "editQuestion");
	}

	/**
	* Save a new phrase to the database
	*
	* @access public
	*/
	function confirmSavePhrase() 
	{
		if (!$_POST["phrase_title"])
		{
			ilUtil::sendInfo($this->lng->txt("qpl_savephrase_empty"));
			$this->savePhrasecolumns(true);
			return;
		}

		if ($this->object->phraseExists($_POST["phrase_title"]))
		{
			ilUtil::sendInfo($this->lng->txt("qpl_savephrase_exists"));
			$this->savePhrasecolumns(true);
			return;
		}

		$this->object->savePhrase($_POST["phrase_title"]);
		ilUtil::sendSuccess($this->lng->txt("phrase_saved"), true);
		$this->ctrl->redirect($this, "editQuestion");
	}
	
	/**
	* Cancels the form adding a phrase
	*
	* @access public
	*/
	function cancelViewPhrase() 
	{
		ilUtil::sendInfo($this->lng->txt('msg_cancel'), true);
		$this->ctrl->redirect($this, 'editQuestion');
	}

	/**
	* Adds a selected phrase
	*
	* @access public
	*/
	function addSelectedPhrase() 
	{
		if (strcmp($_POST["phrases"], "") == 0)
		{
			ilUtil::sendInfo($this->lng->txt("select_phrase_to_add"));
			$this->wizardcolumns(false);
		}
		else
		{
			if (strcmp($this->object->getPhrase($_POST["phrases"]), "dp_standard_numbers") != 0)
			{
				$this->object->addPhrase($_POST["phrases"]);
				$this->object->saveToDb();
			}
			else
			{
				$this->addStandardNumbers();
				return;
			}
			ilUtil::sendSuccess($this->lng->txt('phrase_added'), true);
			$this->ctrl->redirect($this, 'editQuestion');
		}
	}

	/**
	* Creates an output for the addition of standard numbers
	*
	* @access public
	*/
	function addStandardNumbers() 
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_addphrase_standard_numbers.html", "Modules/SurveyQuestionPool");

		// set the id to return to the selected question
		$this->tpl->setCurrentBlock("hidden");
		$this->tpl->setVariable("HIDDEN_NAME", "id");
		$this->tpl->setVariable("HIDDEN_VALUE", $this->object->getId());
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("ADD_STANDARD_NUMBERS", $this->lng->txt("add_standard_numbers"));
		$this->tpl->setVariable("TEXT_ADD_LIMITS", $this->lng->txt("add_limits_for_standard_numbers"));
		$this->tpl->setVariable("TEXT_LOWER_LIMIT",$this->lng->txt("lower_limit"));
		$this->tpl->setVariable("TEXT_UPPER_LIMIT",$this->lng->txt("upper_limit"));
		$this->tpl->setVariable("VALUE_LOWER_LIMIT", $_POST["lower_limit"]);
		$this->tpl->setVariable("VALUE_UPPER_LIMIT", $_POST["upper_limit"]);
		$this->tpl->setVariable("BTN_ADD",$this->lng->txt("add_phrase"));
		$this->tpl->setVariable("BTN_CANCEL",$this->lng->txt("cancel"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* Cancels the form adding standard numbers
	*
	* @access public
	*/
	function cancelStandardNumbers() 
	{
		ilUtil::sendInfo($this->lng->txt("msg_cancel"), true);
		$this->ctrl->redirect($this, "editQuestion");
	}

	/**
	* Insert standard numbers to the question
	*
	* @access public
	*/
	function insertStandardNumbers() 
	{
		if ((strcmp($_POST["lower_limit"], "") == 0) or (strcmp($_POST["upper_limit"], "") == 0))
		{
			ilUtil::sendInfo($this->lng->txt("missing_upper_or_lower_limit"));
			$this->addStandardNumbers();
		}
		else if ((int)$_POST["upper_limit"] <= (int)$_POST["lower_limit"])
		{
			ilUtil::sendInfo($this->lng->txt("upper_limit_must_be_greater"));
			$this->addStandardNumbers();
		}
		else
		{
			$this->object->addStandardNumbers($_POST["lower_limit"], $_POST["upper_limit"]);
			$this->object->saveToDb();
			ilUtil::sendSuccess($this->lng->txt('phrase_added'), true);
			$this->ctrl->redirect($this, "editQuestion");
		}
	}	
	
	
	//
	// EXECUTION
	// 
	
	/**
	* Creates the question output form for the learner
	*
	* @access public
	*/
	function getWorkingForm($working_data = "", $question_title = 1, $show_questiontext = 1, $error_message = "", $survey_id = null)
	{
		$layout = $this->object->getLayout();
		$neutralstyle = "3px solid #808080";
		$bordercolor = "#808080";
		$template = new ilTemplate("tpl.il_svy_out_matrix.html", TRUE, TRUE, "Modules/SurveyQuestionPool");
		$template->setCurrentBlock("material_matrix");
		$template->setVariable("TEXT_MATERIAL", $this->getMaterialOutput());
		$template->parseCurrentBlock();
		
		if ($this->show_layout_row)
		{
			$layout_row = $this->getLayoutRow();
			$template->setCurrentBlock("matrix_row");
			$template->setVariable("ROW", $layout_row);
			$template->parseCurrentBlock();
		}
		
		$tplheaders = new ilTemplate("tpl.il_svy_out_matrix_columnheaders.html", TRUE, TRUE, "Modules/SurveyQuestionPool");
		if ((strlen($this->object->getBipolarAdjective(0))) && (strlen($this->object->getBipolarAdjective(1))))
		{
			$tplheaders->setCurrentBlock("bipolar_start");
			$style = array();
			array_push($style, sprintf("width: %.2f%s!important", $layout["percent_bipolar_adjective1"], "%"));
			if (count($style) > 0)
			{
				$tplheaders->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
			}
			$tplheaders->parseCurrentBlock();
		}
		// column headers
		for ($i = 0; $i < $this->object->getColumnCount(); $i++)
		{
			$style = array();
			$col = $this->object->getColumn($i);
			if ($col->neutral)
			{
				$tplheaders->setCurrentBlock("neutral_column_header");
				$tplheaders->setVariable("TEXT", ilUtil::prepareFormOutput($col->title));
				$tplheaders->setVariable("CLASS", "rsep");
				$style = array();
				array_push($style, sprintf("width: %.2f%s!important", $layout["percent_neutral"], "%"));
				if ($this->object->getNeutralColumnSeparator())
				{
					array_push($style, "border-left: $neutralstyle!important;");
				}
				if (count($style) > 0)
				{
					$tplheaders->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
				}
				$tplheaders->parseCurrentBlock();
			}
			else
			{
				if ($this->object->getColumnSeparators() == 1)
				{
					if (($i < $this->object->getColumnCount() - 1))
					{
						array_push($style, "border-right: 1px solid $bordercolor!important");
					}
				}
				array_push($style, sprintf("width: %.2f%s!important", $layout["percent_columns"] / $this->object->getColumnCount(), "%"));
				$tplheaders->setCurrentBlock("column_header");
				$tplheaders->setVariable("TEXT", ilUtil::prepareFormOutput($col->title));
				$tplheaders->setVariable("CLASS", "center");
				if (count($style) > 0)
				{
					$tplheaders->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
				}
				$tplheaders->parseCurrentBlock();
			}
		}
		if ((strlen($this->object->getBipolarAdjective(0))) && (strlen($this->object->getBipolarAdjective(1))))
		{
			$tplheaders->setCurrentBlock("bipolar_end");
			$style = array();
			array_push($style, sprintf("width: %.2f%s!important", $layout["percent_bipolar_adjective2"], "%"));
			if (count($style) > 0)
			{
				$tplheaders->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
			}
			$tplheaders->parseCurrentBlock();
		}

		$style = array();
		array_push($style, sprintf("width: %.2f%s!important", $layout["percent_row"], "%"));
		if (count($style) > 0)
		{
			$tplheaders->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
		}
		
		$template->setCurrentBlock("matrix_row");
		$template->setVariable("ROW", $tplheaders->get());
		$template->parseCurrentBlock();

		$rowclass = array("tblrow1", "tblrow2");
		for ($i = 0; $i < $this->object->getRowCount(); $i++)
		{
			$rowobj = $this->object->getRow($i);
			$tplrow = new ilTemplate("tpl.il_svy_out_matrix_row.html", TRUE, TRUE, "Modules/SurveyQuestionPool");
			for ($j = 0; $j < $this->object->getColumnCount(); $j++)
			{
				$cat = $this->object->getColumn($j);
				if (($i == 0) && ($j == 0))
				{
					if ((strlen($this->object->getBipolarAdjective(0))) && (strlen($this->object->getBipolarAdjective(1))))
					{
						$tplrow->setCurrentBlock("bipolar_start");
						$tplrow->setVariable("TEXT_BIPOLAR_START", ilUtil::prepareFormOutput($this->object->getBipolarAdjective(0)));
						$tplrow->setVariable("ROWSPAN", $this->object->getRowCount());
						$tplrow->parseCurrentBlock();
					}
				}
				if (($i == 0) && ($j == $this->object->getColumnCount()-1))
				{
					if ((strlen($this->object->getBipolarAdjective(0))) && (strlen($this->object->getBipolarAdjective(1))))
					{
						$tplrow->setCurrentBlock("bipolar_end");
						$tplrow->setVariable("TEXT_BIPOLAR_END", ilUtil::prepareFormOutput($this->object->getBipolarAdjective(1)));
						$tplrow->setVariable("ROWSPAN", $this->object->getRowCount());
						$tplrow->parseCurrentBlock();
					}
				}
				switch ($this->object->getSubtype())
				{
					case 0:
						if ($cat->neutral)
						{
							$tplrow->setCurrentBlock("neutral_radiobutton");
							$tplrow->setVariable("QUESTION_ID", $this->object->getId());
							$tplrow->setVariable("ROW", $i);
							$tplrow->setVariable("VALUE", ($cat->scale) ? ($cat->scale - 1) : $j);
							if (is_array($working_data))
							{
								foreach ($working_data as $data)
								{
									if (($data["value"] == $cat->scale-1) && ($data["rowvalue"] == $i))
									{
										$tplrow->setVariable("CHECKED_RADIOBUTTON", " checked=\"checked\"");
									}
								}
							}
							$tplrow->parseCurrentBlock();
						}
						else
						{
							$tplrow->setCurrentBlock("radiobutton");
							$tplrow->setVariable("QUESTION_ID", $this->object->getId());
							$tplrow->setVariable("ROW", $i);
							$tplrow->setVariable("VALUE", ($cat->scale) ? ($cat->scale - 1) : $j);
							if (is_array($working_data))
							{
								foreach ($working_data as $data)
								{
									if (($data["value"] == $cat->scale-1) && ($data["rowvalue"] == $i))
									{
										$tplrow->setVariable("CHECKED_RADIOBUTTON", " checked=\"checked\"");
									}
								}
							}
							$tplrow->parseCurrentBlock();
						}
						break;
					case 1:
						if ($cat->neutral)
						{
							$tplrow->setCurrentBlock("neutral_checkbox");
							$tplrow->setVariable("QUESTION_ID", $this->object->getId());
							$tplrow->setVariable("ROW", $i);
							$tplrow->setVariable("VALUE", ($cat->scale) ? ($cat->scale - 1) : $j);
							if (is_array($working_data))
							{
								foreach ($working_data as $data)
								{
									if (($data["value"] == $cat->scale-1) && ($data["rowvalue"] == $i))
									{
										$tplrow->setVariable("CHECKED_CHECKBOX", " checked=\"checked\"");
									}
								}
							}
							$tplrow->parseCurrentBlock();
						}
						else
						{
							$tplrow->setCurrentBlock("checkbox");
							$tplrow->setVariable("QUESTION_ID", $this->object->getId());
							$tplrow->setVariable("ROW", $i);
							$tplrow->setVariable("VALUE", ($cat->scale) ? ($cat->scale - 1) : $j);
							if (is_array($working_data))
							{
								foreach ($working_data as $data)
								{
									if (($data["value"] == $cat->scale-1) && ($data["rowvalue"] == $i))
									{
										$tplrow->setVariable("CHECKED_CHECKBOX", " checked=\"checked\"");
									}
								}
							}
							$tplrow->parseCurrentBlock();
						}
						break;
				}
				if ($cat->neutral)
				{
					$tplrow->setCurrentBlock("neutral_answer");
					$style = array();
					if ($this->object->getNeutralColumnSeparator())
					{
						array_push($style, "border-left: $neutralstyle!important");
					}
					if ($this->object->getColumnSeparators() == 1)
					{
						if ($j < $this->object->getColumnCount() - 1)
						{
							array_push($style, "border-right: 1px solid $bordercolor!important");
						}
					}
				}
				else
				{
					$tplrow->setCurrentBlock("answer");
					$style = array();

					if ($this->object->getColumnSeparators() == 1)
					{
						if ($j < $this->object->getColumnCount() - 1)
						{
							array_push($style, "border-right: 1px solid $bordercolor!important");
						}
					}
				}
				if ($this->object->getRowSeparators() == 1)
				{
					if ($i < $this->object->getRowCount() - 1)
					{
						array_push($style, "border-bottom: 1px solid $bordercolor!important");
					}
				}
				if (count($style))
				{
					$tplrow->setVariable("STYLE", " style=\"" . implode(";", $style) . "\"");
				}
				$tplrow->parseCurrentBlock();
			}

			if ($rowobj->other)
			{
				$tplrow->setCurrentBlock("row_other");
				$tplrow->setVariable("QUESTION_ID", $this->object->getId());
				$tplrow->setVariable("ROW", $i);
				if (is_array($working_data))
				{
					foreach ($working_data as $data)
					{
						if ($data["rowvalue"] == $i)
						{
							$tplrow->setVariable("VALUE_OTHER", ilUtil::prepareFormOutput($data['textanswer']));
						}
					}
				}
				$tplrow->parseCurrentBlock();
			}
			$tplrow->setVariable("TEXT_ROW", ilUtil::prepareFormOutput($rowobj->title));
			$tplrow->setVariable("ROWCLASS", $rowclass[$i % 2]);
			if ($this->object->getRowSeparators() == 1)
			{
				if ($i < $this->object->getRowCount() - 1)
				{
					$tplrow->setVariable("STYLE", " style=\"border-bottom: 1px solid $bordercolor!important\"");
				}
			}
			$template->setCurrentBlock("matrix_row");
			$template->setVariable("ROW", $tplrow->get());
			$template->parseCurrentBlock();
		}
		
		if ($question_title)
		{
			$template->setVariable("QUESTION_TITLE", ilUtil::prepareFormOutput($this->object->getTitle()));
		}
		$template->setCurrentBlock("question_data_matrix");
		if (strcmp($error_message, "") != 0)
		{
			$template->setVariable("ERROR_MESSAGE", "<p class=\"warning\">$error_message</p>");
		}
		if ($show_questiontext)
		{
			$this->outQuestionText($template);
		}
		$template->parseCurrentBlock();
		return $template->get();
	}

	

/**
* Creates a layout view of the question
*
* @access public
*/
	function layout()
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_layout.html", "Modules/SurveyQuestionPool");
		$this->show_layout_row = TRUE;
		$question_output = $this->getWorkingForm();
		$this->tpl->setVariable("QUESTION_OUTPUT", $question_output);
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this, "saveLayout"));
		$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
	}
	
/**
 * Saves the layout for the matrix question
 *
 * @return void
 **/
	function saveLayout()
	{
		$percent_values = array(
			"percent_row" => (int)$_POST["percent_row"],
			"percent_columns" => (int)$_POST["percent_columns"],
			"percent_bipolar_adjective1" => (int)$_POST['percent_bipolar_adjective1'],
			"percent_bipolar_adjective2" => (int)$_POST['percent_bipolar_adjective2'],
			"percent_neutral" => (int)$_POST["percent_neutral"]
		);
		$this->object->setLayout($percent_values);
		
		// #9364
		if(array_sum($percent_values) == 100)
		{		
			$this->object->saveLayout($percent_values["percent_row"], 
				$percent_values['percent_columns'], 
				$percent_values['percent_bipolar_adjective1'], 
				$percent_values['percent_bipolar_adjective2'], 
				$percent_values["percent_neutral"]);						
			ilUtil::sendSuccess($this->lng->txt("settings_saved"));
		}
		else
		{
			ilUtil::sendFailure($this->lng->txt("svy_matrix_layout_percentages_sum_invalid"));
		}
		$this->layout();
	}

/**
* Creates a row to define the matrix question layout with percentage values
*
* @access public
*/
	function getLayoutRow()
	{
		$percent_values = $this->object->getLayout();
		$template = new ilTemplate("tpl.il_svy_out_matrix_layout.html", TRUE, TRUE, "Modules/SurveyQuestionPool");
		if (strlen($this->object->getBipolarAdjective(0)) && strlen($this->object->getBipolarAdjective(1)))
		{
			$template->setCurrentBlock("bipolar_start");
			$template->setVariable("VALUE_PERCENT_BIPOLAR_ADJECTIVE1", " value=\"" . $percent_values["percent_bipolar_adjective1"] . "\"");
			$template->setVariable("STYLE", " style=\"width:" . $percent_values["percent_bipolar_adjective1"] . "%\"");
			$template->parseCurrentBlock();
			$template->setCurrentBlock("bipolar_end");
			$template->setVariable("VALUE_PERCENT_BIPOLAR_ADJECTIVE2", " value=\"" . $percent_values["percent_bipolar_adjective2"] . "\"");
			$template->setVariable("STYLE", " style=\"width:" . $percent_values["percent_bipolar_adjective2"] . "%\"");
			$template->parseCurrentBlock();
		}
		$counter = $this->object->getColumnCount();
		if (strlen($this->object->hasNeutralColumn()))
		{
			$template->setCurrentBlock("neutral_start");
			$template->setVariable("VALUE_PERCENT_NEUTRAL", " value=\"" . $percent_values["percent_neutral"] . "\"");
			$template->setVariable("STYLE_NEUTRAL", " style=\"width:" . $percent_values["percent_neutral"] . "%\"");
			$template->parseCurrentBlock();
			$counter--;
		}
		$template->setVariable("VALUE_PERCENT_ROW", " value=\"" . $percent_values["percent_row"] . "\"");
		$template->setVariable("STYLE_ROW", " style=\"width:" . $percent_values["percent_row"] . "%\"");		
		$template->setVariable("COLSPAN_COLUMNS", $counter);
		$template->setVariable("VALUE_PERCENT_COLUMNS", " value=\"" . $percent_values["percent_columns"] . "\"");
		$template->setVariable("STYLE_COLUMNS", " style=\"width:" . $percent_values["percent_columns"] . "%\"");
		return $template->get();
	}

	/**
	* Creates the detailed output of the cumulated results for the question
	*
	* @param integer $survey_id The database ID of the survey
	* @param integer $counter The counter of the question position in the survey
	* @return string HTML text with the cumulated results
	* @access private
	*/
	function getCumulatedResultsDetails($survey_id, $counter, $finished_ids)
	{
		if (count($this->cumulated) == 0)
		{
			if(!$finished_ids)
			{
				include_once "./Modules/Survey/classes/class.ilObjSurvey.php";			
				$nr_of_users = ilObjSurvey::_getNrOfParticipants($survey_id);
			}
			else
			{
				$nr_of_users = sizeof($finished_ids);
			}
			$this->cumulated =& $this->object->getCumulatedResults($survey_id, $nr_of_users, $finished_ids);
		}
		
		$cumulated_count = 0;
		foreach ($this->cumulated as $key => $value)
		{
			if (is_numeric($key))	
			{
				$cumulated_count++;							
			}
		}
		
		$output = "";
		
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_svy_svy_cumulated_results_detail.html", TRUE, TRUE, "Modules/Survey");
		
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("question"));
		$questiontext = $this->object->getQuestiontext();
		$template->setVariable("TEXT_OPTION_VALUE", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$template->parseCurrentBlock();
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("question_type"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->lng->txt($this->getQuestionType()).
			" (".$cumulated_count." ".$this->lng->txt("rows").")");
		$template->parseCurrentBlock();
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("users_answered"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->cumulated["TOTAL"]["USERS_ANSWERED"]);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("users_skipped"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->cumulated["TOTAL"]["USERS_SKIPPED"]);
		$template->parseCurrentBlock();
		/*
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("mode"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->cumulated["TOTAL"]["MODE"]);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("mode_nr_of_selections"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->cumulated["TOTAL"]["MODE_NR_OF_SELECTIONS"]);		
	    $template->parseCurrentBlock();
		 */
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("median"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->cumulated["TOTAL"]["MEDIAN"]);
		$template->parseCurrentBlock();
		
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("categories"));
		$columns = "";
		foreach ($this->cumulated["TOTAL"]["variables"] as $key => $value)
		{
			$columns .= "<li>" . $value["title"] . ": n=" . $value["selected"] . 
				" (" . sprintf("%.2f", 100*$value["percentage"]) . "%)</li>";
		}
		$columns = "<ol>$columns</ol>";
		$template->setVariable("TEXT_OPTION_VALUE", $columns);
		$template->parseCurrentBlock();
				
		// total chart 
		$template->setCurrentBlock("detail_row");				
		$template->setVariable("TEXT_OPTION", $this->lng->txt("chart"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->renderChart("svy_ch_".$this->object->getId()."_total", $this->cumulated["TOTAL"]["variables"]));
		$template->parseCurrentBlock();
		
		$template->setVariable("QUESTION_TITLE", "$counter. ".$this->object->getTitle());		
		
		$output .= $template->get();
		
		foreach ($this->cumulated as $key => $value)
		{
			if (is_numeric($key))	
			{
				$template = new ilTemplate("tpl.il_svy_svy_cumulated_results_detail.html", TRUE, TRUE, "Modules/Survey");	
				
				$template->setCurrentBlock("detail_row");
				$template->setVariable("TEXT_OPTION", $this->lng->txt("users_answered"));
				$template->setVariable("TEXT_OPTION_VALUE", $value["USERS_ANSWERED"]);
				$template->parseCurrentBlock();
				$template->setCurrentBlock("detail_row");
				$template->setVariable("TEXT_OPTION", $this->lng->txt("users_skipped"));
				$template->setVariable("TEXT_OPTION_VALUE", $value["USERS_SKIPPED"]);
				$template->parseCurrentBlock();				
				/*
				$template->setCurrentBlock("detail_row");
				$template->setVariable("TEXT_OPTION", $this->lng->txt("mode"));
				$template->setVariable("TEXT_OPTION_VALUE", $value["MODE"]);
				$template->parseCurrentBlock();				
				$template->setCurrentBlock("detail_row");
				$template->setVariable("TEXT_OPTION", $this->lng->txt("mode_nr_of_selections"));
				$template->setVariable("TEXT_OPTION_VALUE", $value["MODE_NR_OF_SELECTIONS"]);
				$template->parseCurrentBlock();
				*/
				$template->setCurrentBlock("detail_row");				
				$template->setVariable("TEXT_OPTION", $this->lng->txt("median"));
				$template->setVariable("TEXT_OPTION_VALUE", $value["MEDIAN"]);
				$template->parseCurrentBlock();
				
				$template->setCurrentBlock("detail_row");
				$template->setVariable("TEXT_OPTION", $this->lng->txt("categories"));
				$columns = "";
				foreach ($value["variables"] as $cvalue)
				{
					$columns .= "<li>" . $cvalue["title"] . ": n=". $cvalue["selected"] . 
						" (".sprintf("%.2f", 100*$cvalue["percentage"]) . "%)</li>";
				}
				$columns = "<ol>".$columns."</ol>";
				$template->setVariable("TEXT_OPTION_VALUE", $columns);
				$template->parseCurrentBlock();
				
				// add text answers to detailed results
				if (is_array($value["textanswers"]))
				{
					$template->setCurrentBlock("detail_row");
					$template->setVariable("TEXT_OPTION", $this->lng->txt("freetext_answers"));	
					$html = "";		
					foreach ($value["textanswers"] as $tkey => $answers)
					{
						$html .= $value["variables"][$tkey]["title"] ."\n";
						$html .= "<ul>\n";
						foreach ($answers as $answer)
						{
							$html .= "<li>" . preg_replace("/\n/", "<br>\n", $answer) . "</li>\n";
						}
						$html .= "</ul>\n";
					}
					$template->setVariable("TEXT_OPTION_VALUE", $html);
					$template->parseCurrentBlock();
				}			
			
				// chart 
				$template->setCurrentBlock("detail_row");				
				$template->setVariable("TEXT_OPTION", $this->lng->txt("chart"));
				$template->setVariable("TEXT_OPTION_VALUE", $this->renderChart("svy_ch_".$this->object->getId()."_".$key, $value["variables"]));
				$template->parseCurrentBlock();
				
				$template->setVariable("QUESTION_SUBTITLE", $counter.".".($key+1)." ".
					$this->object->prepareTextareaOutput($value["ROW"], TRUE));
				
				$output .= $template->get();
			}
		}

		return $output;
	}		
}

?>
