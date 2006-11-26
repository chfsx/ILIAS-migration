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
include_once "./Modules/Survey/classes/inc.SurveyConstants.php";

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

/**
* SurveyMatrixQuestionGUI constructor
*
* The constructor takes possible arguments an creates an instance of the SurveyMatrixQuestionGUI object.
*
* @param integer $id The database id of a matrix question object
* @access public
*/
  function SurveyMatrixQuestionGUI(
		$id = -1
  )

  {
		$this->SurveyQuestionGUI();
		include_once "./Modules/SurveyQuestionPool/classes/class.SurveyMatrixQuestion.php";
		$this->object = new SurveyMatrixQuestion();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
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
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_matrix.html", "Modules/SurveyQuestionPool");
	  $this->tpl->addBlockFile("OTHER_QUESTION_DATA", "other_question_data", "tpl.il_svy_qpl_other_question_data.html", "Modules/SurveyQuestionPool");
		
		$subtypes = array(
			"0" => "matrix_subtype_sr",
			"1" => "matrix_subtype_mr",
			//"2" => "matrix_subtype_text",
			//"3" => "matrix_subtype_integer",
			//"4" => "matrix_subtype_double",
			//"5" => "matrix_subtype_date",
			//"6" => "matrix_subtype_time"
		);
		
		foreach ($subtypes as $value => $text)
		{
			$this->tpl->setCurrentBlock("subtype_row");
			$this->tpl->setVariable("VALUE_SUBTYPE", $value);
			$this->tpl->setVariable("TEXT_SUBTYPE", $this->lng->txt($text));
			if ($value == $this->object->getSubtype())
			{
				$this->tpl->setVariable("CHECKED_SUBTYPE", " checked=\"checked\"");
			}
			$this->tpl->parseCurrentBlock();
		}
		
		$internallinks = array(
			"lm" => $this->lng->txt("obj_lm"),
			"st" => $this->lng->txt("obj_st"),
			"pg" => $this->lng->txt("obj_pg"),
			"glo" => $this->lng->txt("glossary_term")
		);
		foreach ($internallinks as $key => $value)
		{
			$this->tpl->setCurrentBlock("internallink");
			$this->tpl->setVariable("TYPE_INTERNAL_LINK", $key);
			$this->tpl->setVariable("TEXT_INTERNAL_LINK", $value);
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("TEXT_MATERIAL", $this->lng->txt("material"));
		if (count($this->object->material))
		{
			include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
			$href = SurveyQuestion::_getInternalLinkHref($this->object->material["internal_link"]);
			$this->tpl->setVariable("TEXT_VALUE_MATERIAL", " <a href=\"$href\" target=\"content\">" . $this->lng->txt("material"). "</a> ");
			$this->tpl->setVariable("BUTTON_REMOVE_MATERIAL", $this->lng->txt("remove"));
			$this->tpl->setVariable("BUTTON_ADD_MATERIAL", $this->lng->txt("change"));
			$this->tpl->setVariable("VALUE_MATERIAL", $this->object->material["internal_link"]);
			$this->tpl->setVariable("VALUE_MATERIAL_TITLE", $this->object->material["title"]);
			$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		}
		else
		{
			$this->tpl->setVariable("BUTTON_ADD_MATERIAL", $this->lng->txt("add"));
		}
		$this->tpl->setVariable("TEXT_ORIENTATION", $this->lng->txt("orientation"));
		$this->tpl->setVariable("QUESTION_ID", $this->object->getId());
		$this->tpl->setVariable("VALUE_TITLE", $this->object->getTitle());
		$this->tpl->setVariable("VALUE_DESCRIPTION", $this->object->getDescription());
		$this->tpl->setVariable("VALUE_AUTHOR", $this->object->getAuthor());
		$questiontext = $this->object->getQuestiontext();
		$this->tpl->setVariable("VALUE_QUESTION", $this->object->prepareTextareaOutput($questiontext));
		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
		$this->tpl->setVariable("TEXT_DESCRIPTION", $this->lng->txt("description"));
		$this->tpl->setVariable("TEXT_QUESTION", $this->lng->txt("question"));
		$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt("questiontype"));
		$this->tpl->setVariable("TEXT_OBLIGATORY", $this->lng->txt("obligatory"));
		if ($this->object->getObligatory())
		{
			$this->tpl->setVariable("CHECKED_OBLIGATORY", " checked=\"checked\"");
		}
		$this->tpl->setVariable("SAVE",$this->lng->txt("save"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt($this->getQuestionType()));
		$this->tpl->setVariable("TEXT_SUBTYPE", $this->lng->txt("subtype"));
		$this->tpl->setVariable("DESCRIPTION_SUBTYPE", $this->lng->txt("matrix_subtype_description"));
		$this->tpl->parseCurrentBlock();
		
		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->addPlugin("latex");		
		$rte->addButton("latex");
		$rte->removePlugin("ibrowser");
		include_once "./classes/class.ilObject.php";
		$obj_id = $_GET["q_id"];
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, $obj_type, "survey");
  }

/**
* Creates the question output form for the learner
* 
* Creates the question output form for the learner
*
* @access public
*/
	function getWorkingForm($working_data = "", $question_title = 1, $error_message = "")
	{
		$template = new ilTemplate("tpl.il_svy_out_matrix.html", TRUE, TRUE, "Modules/SurveyQuestionPool");
		if (count($this->object->material))
		{
			$template->setCurrentBlock("material_matrix");
			include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
			$href = SurveyQuestion::_getInternalLinkHref($this->object->material["internal_link"]);
			$template->setVariable("TEXT_MATERIAL", $this->lng->txt("material") . ": <a href=\"$href\" target=\"content\">" . $this->object->material["title"]. "</a> ");
			$template->parseCurrentBlock();
		}
		switch ($this->object->orientation)
		{
			case 0:
				// vertical orientation
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$category = $this->object->categories->getCategory($i);
					$template->setCurrentBlock("matrix_row");
					$template->setVariable("TEXT_MATRIX", $category);
					$template->setVariable("VALUE_MATRIX", $i);
					$template->setVariable("QUESTION_ID", $this->object->getId());
					if (is_array($working_data))
					{
						if (strcmp($working_data[0]["value"], "") != 0)
						{
							if ($working_data[0]["value"] == $i)
							{
								$template->setVariable("CHECKED_MATRIX", " checked=\"checked\"");
							}
						}
					}
					$template->parseCurrentBlock();
				}
				break;
			case 1:
				// horizontal orientation
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$category = $this->object->categories->getCategory($i);
					$template->setCurrentBlock("radio_col_matrix");
					$template->setVariable("VALUE_MATRIX", $i);
					$template->setVariable("QUESTION_ID", $this->object->getId());
					if (is_array($working_data))
					{
						if (strcmp($working_data[0]["value"], "") != 0)
						{
							if ($working_data[0]["value"] == $i)
							{
								$template->setVariable("CHECKED_MATRIX", " checked=\"checked\"");
							}
						}
					}
					$template->parseCurrentBlock();
				}
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$category = $this->object->categories->getCategory($i);
					$template->setCurrentBlock("text_col_matrix");
					$template->setVariable("VALUE_MATRIX", $i);
					$template->setVariable("TEXT_MATRIX", $category);
					$template->setVariable("QUESTION_ID", $this->object->getId());
					$template->parseCurrentBlock();
				}
				break;
			case 2:
				// combobox output
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$category = $this->object->categories->getCategory($i);
					$template->setCurrentBlock("comborow");
					$template->setVariable("TEXT_MATRIX", $category);
					$template->setVariable("VALUE_MATRIX", $i);
					if (is_array($working_data))
					{
						if (strcmp($working_data[0]["value"], "") != 0)
						{
							if ($working_data[0]["value"] == $i)
							{
								$template->setVariable("SELECTED_MATRIX", " selected=\"selected\"");
							}
						}
					}
					$template->parseCurrentBlock();
				}
				$template->setCurrentBlock("combooutput");
				$template->setVariable("QUESTION_ID", $this->object->getId());
				$template->setVariable("SELECT_OPTION", $this->lng->txt("select_option"));
				$template->setVariable("TEXT_SELECTION", $this->lng->txt("selection"));
				$template->parseCurrentBlock();
				break;
		}
		if ($question_title)
		{
			$template->setVariable("QUESTION_TITLE", $this->object->getTitle());
		}
		$template->setCurrentBlock("question_data_matrix");
		if (strcmp($error_message, "") != 0)
		{
			$template->setVariable("ERROR_MESSAGE", "<p class=\"warning\">$error_message</p>");
		}
		$questiontext = $this->object->getQuestiontext();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		if (! $this->object->getObligatory())
		{
			$template->setVariable("OBLIGATORY_TEXT", $this->lng->txt("survey_question_optional"));
		}
		$template->parseCurrentBlock();
		return $template->get();
	}

/**
* Creates a preview of the question
*
* Creates a preview of the question
*
* @access private
*/
	function preview()
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_preview.html", "Modules/SurveyQuestionPool");
		$question_output = $this->getWorkingForm();
		$this->tpl->setVariable("QUESTION_OUTPUT", $question_output);
		$this->tpl->parseCurrentBlock();
	}
	
/**
* Evaluates a posted edit form and writes the form data in the question object
*
* Evaluates a posted edit form and writes the form data in the question object
*
* @return integer A positive value, if one of the required fields wasn't set, else 0
* @access private
*/
  function writePostData() 
	{
    $result = 0;
    if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]))
      $result = 1;

    // Set the question id from a hidden form parameter
    if ($_POST["id"] > 0)
      $this->object->setId($_POST["id"]);
		include_once "./classes/class.ilUtil.php";
    $this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
    $this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
    $this->object->setDescription(ilUtil::stripSlashes($_POST["description"]));
		$this->object->setOrientation($_POST["orientation"]);
		if (strlen($_POST["material"]))
		{
			$this->object->setMaterial($_POST["material"], 0, ilUtil::stripSlashes($_POST["material_title"]));
		}
		include_once "./classes/class.ilObjAdvancedEditing.php";
		$questiontext = ilUtil::stripSlashes($_POST["question"], true, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("survey"));
		$this->object->setQuestiontext($questiontext);
		if ($_POST["obligatory"])
		{
			$this->object->setObligatory(1);
		}
		else
		{
			$this->object->setObligatory(0);
		}

		if ($saved) {
			// If the question was saved automatically before an upload, we have to make
			// sure, that the state after the upload is saved. Otherwise the user could be
			// irritated, if he presses cancel, because he only has the question state before
			// the upload process.
			$this->object->saveToDb();
		}
    return $result;
  }

/**
* Creates the form to edit the question categories
*
* Creates the form to edit the question categories
*
* @access private
*/
	function categories($add = false)
	{
		if ($this->object->getId() < 1) 
		{
			sendInfo($this->lng->txt("fill_out_all_required_fields_add_category"), true);
			$this->ctrl->redirect($this, "editQuestion");
		}
		if (strcmp($this->ctrl->getCmd(), "categories") == 0) $_SESSION["spl_modified"] = false;
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_matrix_answers.html", "Modules/SurveyQuestionPool");
		// create an empty category if nothing is defined
		if ($this->object->categories->getCategoryCount() == 0)
		{
			$this->object->categories->addCategory("");
		}
		if ($add)
		{
			$nrOfCategories = $_POST["nrOfCategories"];
			if ($nrOfCategories < 1) $nrOfCategories = 1;
			// Create template for a new category
			for ($i = 1; $i <= $nrOfCategories; $i++)
			{
				$this->object->categories->addCategory("");
			}
		}
    // output of existing single response answers
		for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
		{
			$this->tpl->setCurrentBlock("categories");
			$this->tpl->setVariable("CATEGORY_ORDER", $i);
			$category = $this->object->categories->getCategory($i);
			$this->tpl->setVariable("CATEGORY_ORDER", $i);
			$this->tpl->setVariable("CATEGORY_NUMBER", $i+1);
			$this->tpl->setVariable("VALUE_CATEGORY", $category);
			$this->tpl->setVariable("TEXT_CATEGORY", $this->lng->txt("category"));
			$this->tpl->parseCurrentBlock();
		}

		if ($this->object->getRowCount() == 0)
		{
			$this->object->addRow("");
		}
		if ($addrows)
		{
			$nrOfRows = $_POST["nrOfRows"];
			if ($nrOfRows < 1) $nrOfRows = 1;
			// Create template for a new category
			for ($i = 1; $i <= $nrOfRows; $i++)
			{
				$this->object->addRow("");
			}
		}
    // output of existing rows
		for ($i = 0; $i < $this->object->getRowCount(); $i++) 
		{
			$this->tpl->setCurrentBlock("rows");
			$this->tpl->setVariable("ROW_ORDER", $i);
			$row = $this->object->getRow($i);
			$this->tpl->setVariable("ROW_ORDER", $i);
			$this->tpl->setVariable("ROW_NUMBER", $i+1);
			$this->tpl->setVariable("VALUE_ROW", $row);
			$this->tpl->setVariable("TEXT_ROW", $this->lng->txt("row"));
			$this->tpl->parseCurrentBlock();
		}
		
		if (is_array($_SESSION["spl_move"]))
		{
			if (count($_SESSION["spl_move"]))
			{
				$this->tpl->setCurrentBlock("move_buttons");
				$this->tpl->setVariable("INSERT_BEFORE", $this->lng->txt("insert_before"));
				$this->tpl->setVariable("INSERT_AFTER", $this->lng->txt("insert_after"));
				$this->tpl->parseCurrentBlock();
			}
		}
		
		include_once "./classes/class.ilUtil.php";
		if ($this->object->categories->getCategoryCount() > 0)
		{
			$this->tpl->setCurrentBlock("selectall");
			$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("existingcategories");
			$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
			$this->tpl->setVariable("MOVE", $this->lng->txt("move"));
			$this->tpl->setVariable("VALUE_SAVE_PHRASE", $this->lng->txt("save_phrase"));
			$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
			$this->tpl->parseCurrentBlock();
		}
		
		for ($i = 1; $i < 10; $i++)
		{
			$this->tpl->setCurrentBlock("numbers");
			$this->tpl->setVariable("VALUE_NUMBER", $i);
			if ($i == 1)
			{
				$this->tpl->setVariable("TEXT_NUMBER", $i . " " . $this->lng->txt("category"));
			}
			else
			{
				$this->tpl->setVariable("TEXT_NUMBER", $i . " " . $this->lng->txt("categories"));
			}
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("rownumbers");
			$this->tpl->setVariable("VALUE_NUMBER", $i);
			if ($i == 1)
			{
				$this->tpl->setVariable("TEXT_NUMBER", $i . " " . $this->lng->txt("row"));
			}
			else
			{
				$this->tpl->setVariable("TEXT_NUMBER", $i . " " . $this->lng->txt("rows"));
			}
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TEXT_ANSWERS", $this->lng->txt("matrix_columns"));
		$this->tpl->setVariable("VALUE_ADD_CATEGORY", $this->lng->txt("add"));
		$this->tpl->setVariable("VALUE_ADD_PHRASE", $this->lng->txt("add_phrase"));
		$this->tpl->setVariable("TEXT_STANDARD_ANSWERS", $this->lng->txt("matrix_standard_answers"));
		$this->tpl->setVariable("TEXT_NEUTRAL_ANSWER", $this->lng->txt("matrix_neutral_answer"));
		$this->tpl->setVariable("CATEGORY_NEUTRAL", $this->object->categories->getCategoryCount() + 1);
		$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
		
		$this->tpl->setVariable("TEXT_ROWS", $this->lng->txt("matrix_rows"));
		$this->tpl->setVariable("SAVEROWS", $this->lng->txt("save"));
		$this->tpl->setVariable("VALUE_ADD_ROW", $this->lng->txt("add"));
		
		if ($_SESSION["spl_modified"])
		{
			$this->tpl->setVariable("FORM_DATA_MODIFIED_PRESS_SAVE", $this->lng->txt("form_data_modified_press_save"));
		}
		$questiontext = $this->object->getQuestiontext();
		$this->tpl->setVariable("QUESTION_TEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$this->tpl->parseCurrentBlock();
	}
	
	function setQuestionTabs()
	{
		global $rbacsystem,$ilTabs;
		$this->ctrl->setParameterByClass("$guiclass", "sel_question_types", $this->getQuestionType());
		$this->ctrl->setParameterByClass("$guiclass", "q_id", $_GET["q_id"]);

		if (($_GET["calling_survey"] > 0) || ($_GET["new_for_survey"] > 0))
		{
			$ref_id = $_GET["calling_survey"];
			if (!strlen($ref_id)) $ref_id = $_GET["new_for_survey"];
			$addurl = "";
			if (strlen($_GET["new_for_survey"]))
			{
				$addurl = "&new_id=" . $_GET["q_id"];
			}
			$ilTabs->setBackTarget($this->lng->txt("menubacktosurvey"), "ilias.php?baseClass=ilObjSurveyGUI&ref_id=$ref_id&cmd=questions" . $addurl);
		}
		else
		{
			$ilTabs->setBackTarget($this->lng->txt("spl"), $this->ctrl->getLinkTargetByClass("ilObjSurveyQuestionPoolGUI", "questions"));
		}
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("preview",
				$this->ctrl->getLinkTarget($this, "preview"), 
				array("preview"),
				"",
				"");
		}
		if ($rbacsystem->checkAccess('edit', $_GET["ref_id"])) 
		{
			$ilTabs->addTarget("edit_properties",
				$this->ctrl->getLinkTarget($this, "editQuestion"), 
				array("editQuestion", "cancelExplorer", "linkChilds", "addGIT", "addST",
					"addPG", "editQuestion", "addMaterial", "removeMaterial", 
					"save", "cancel"),
				"",
				"");
		}

		if ($this->object->getId() > 0) 
		{
			$ilTabs->addTarget("categories",
				$this->ctrl->getLinkTarget($this, "categories"), 
					array("categories", "addCategory", "insertBeforeCategory",
						"insertAfterCategory", "moveCategory", "deleteCategory",
						"saveCategories", "savePhrase", "addPhrase",
						"savePhrase", "addSelectedPhrase", "cancelViewPhrase", "confirmSavePhrase",
						"cancelSavePhrase", "confirmDeleteCategory", "cancelDeleteCategory"),
				"",
				""
			);
		}
		
		if ($this->object->getId() > 0) 
		{
			$title = $this->lng->txt("edit") . " &quot;" . $this->object->getTitle() . "&quot";
		} 
		else 
		{
			$title = $this->lng->txt("create_new") . " " . $this->lng->txt($this->getQuestionType());
		}

		$this->tpl->setVariable("HEADER", $title);
	}

/**
* Creates an output for the addition of phrases
*
* Creates an output for the addition of phrases
*
* @access public
*/
  function addPhrase() 
	{
		$this->writeCategoryData(true);
		$this->ctrl->setParameterByClass(get_class($this), "q_id", $this->object->getId());
		$this->ctrl->setParameterByClass("ilobjsurveyquestionpoolgui", "q_id", $this->object->getId());

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_addphrase.html", "Modules/SurveyQuestionPool");

		// set the id to return to the selected question
		$this->tpl->setCurrentBlock("hidden");
		$this->tpl->setVariable("HIDDEN_NAME", "id");
		$this->tpl->setVariable("HIDDEN_VALUE", $this->object->getId());
		$this->tpl->parseCurrentBlock();

		$phrases =& $this->object->getAvailablePhrases();
		$colors = array("tblrow1", "tblrow2");
		$counter = 0;
		foreach ($phrases as $phrase_id => $phrase_array)
		{
			$this->tpl->setCurrentBlock("phraserow");
			$this->tpl->setVariable("COLOR_CLASS", $colors[$counter++ % 2]);
			$this->tpl->setVariable("PHRASE_VALUE", $phrase_id);
			$this->tpl->setVariable("PHRASE_NAME", $phrase_array["title"]);
			$categories =& $this->object->getCategoriesForPhrase($phrase_id);
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

/**
* Cancels the form adding a phrase
*
* Cancels the form adding a phrase
*
* @access public
*/
	function cancelViewPhrase() 
	{
		$this->ctrl->redirect($this, "categories");
	}

/**
* Adds a selected phrase
*
* Adds a selected phrase
*
* @access public
*/
	function addSelectedPhrase() 
	{
		if (strcmp($_POST["phrases"], "") == 0)
		{
			sendInfo($this->lng->txt("select_phrase_to_add"));
			$this->addPhrase();
		}
		else
		{
			if (strcmp($this->object->getPhrase($_POST["phrases"]), "dp_standard_numbers") != 0)
			{
				$this->object->addPhrase($_POST["phrases"]);
				$this->object->saveCategoriesToDb();
			}
			else
			{
				$this->addStandardNumbers();
				return;
			}
			$this->ctrl->redirect($this, "categories");
		}
	}
	
/**
* Creates an output for the addition of standard numbers
*
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
* Cancels the form adding standard numbers
*
* @access public
*/
	function cancelStandardNumbers() 
	{
		$this->ctrl->redirect($this, "categories");
	}

/**
* Insert standard numbers to the question
*
* Insert standard numbers to the question
*
* @access public
*/
	function insertStandardNumbers() 
	{
		if ((strcmp($_POST["lower_limit"], "") == 0) or (strcmp($_POST["upper_limit"], "") == 0))
		{
			sendInfo($this->lng->txt("missing_upper_or_lower_limit"));
			$this->addStandardNumbers();
		}
		else if ((int)$_POST["upper_limit"] <= (int)$_POST["lower_limit"])
		{
			sendInfo($this->lng->txt("upper_limit_must_be_greater"));
			$this->addStandardNumbers();
		}
		else
		{
			$this->object->addStandardNumbers($_POST["lower_limit"], $_POST["upper_limit"]);
			$this->object->saveCategoriesToDb();
			$this->ctrl->redirect($this, "categories");
		}
	}

/**
* Creates an output to save a phrase
*
* Creates an output to save a phrase
*
* @access public
*/
  function savePhrase() 
	{
		$this->writeCategoryData(true);
		$nothing_selected = true;
		if (array_key_exists("chb_category", $_POST))
		{
			if (count($_POST["chb_category"]))
			{
				$nothing_selected = false;
				$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_savephrase.html", "Modules/SurveyQuestionPool");
				$rowclass = array("tblrow1", "tblrow2");
				$counter = 0;
				foreach ($_POST["chb_category"] as $category)
				{
					$this->tpl->setCurrentBlock("row");
					$this->tpl->setVariable("TXT_TITLE", $this->object->categories->getCategory($category));
					$this->tpl->setVariable("COLOR_CLASS", $rowclass[$counter % 2]);
					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("hidden");
					$this->tpl->setVariable("HIDDEN_NAME", "chb_category[]");
					$this->tpl->setVariable("HIDDEN_VALUE", $category);
					$this->tpl->parseCurrentBlock();
				}
			
				$this->tpl->setCurrentBlock("adm_content");
				$this->tpl->setVariable("SAVE_PHRASE_INTRODUCTION", $this->lng->txt("save_phrase_introduction"));
				$this->tpl->setVariable("TEXT_PHRASE_TITLE", $this->lng->txt("enter_phrase_title"));
				$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("category"));
				$this->tpl->setVariable("VALUE_PHRASE_TITLE", $_POST["phrase_title"]);
				$this->tpl->setVariable("BTN_CANCEL",$this->lng->txt("cancel"));
				$this->tpl->setVariable("BTN_CONFIRM",$this->lng->txt("confirm"));
				$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
				$this->tpl->parseCurrentBlock();
			}
		}
		if ($nothing_selected)
		{
			sendInfo($this->lng->txt("check_category_to_save_phrase"), true);
			$this->ctrl->redirect($this, "categories");
		}
	}

/**
* Cancels the form saving a phrase
*
* Cancels the form saving a phrase
*
* @access public
*/
	function cancelSavePhrase() 
	{
		$this->ctrl->redirect($this, "categories");
	}

/**
* Save a new phrase to the database
*
* Save a new phrase to the database
*
* @access public
*/
	function confirmSavePhrase() 
	{
		if (!$_POST["phrase_title"])
		{
			sendInfo($this->lng->txt("qpl_savephrase_empty"));
			$this->savePhrase();
			return;
		}
		
		if ($this->object->phraseExists($_POST["phrase_title"]))
		{
			sendInfo($this->lng->txt("qpl_savephrase_exists"));
			$this->savePhrase();
			return;
		}

		$this->object->savePhrase($_POST["chb_category"], $_POST["phrase_title"]);
		sendInfo($this->lng->txt("phrase_saved"), true);
		$this->ctrl->redirect($this, "categories");
	}

	function outCumulatedResultsDetails(&$cumulated_results, $counter)
	{
		$this->tpl->setCurrentBlock("detail_row");
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("question"));
		$questiontext = $this->object->getQuestiontext();
		$this->tpl->setVariable("TEXT_OPTION_VALUE", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("detail_row");
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("question_type"));
		$this->tpl->setVariable("TEXT_OPTION_VALUE", $this->lng->txt($this->getQuestionType()));
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("detail_row");
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("users_answered"));
		$this->tpl->setVariable("TEXT_OPTION_VALUE", $cumulated_results["USERS_ANSWERED"]);
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("detail_row");
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("users_skipped"));
		$this->tpl->setVariable("TEXT_OPTION_VALUE", $cumulated_results["USERS_SKIPPED"]);
		$this->tpl->parseCurrentBlock();
		
		$this->tpl->setCurrentBlock("detail_row");
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("mode"));
		$this->tpl->setVariable("TEXT_OPTION_VALUE", $cumulated_results["MODE"]);
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("detail_row");
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("mode_nr_of_selections"));
		$this->tpl->setVariable("TEXT_OPTION_VALUE", $cumulated_results["MODE_NR_OF_SELECTIONS"]);
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("detail_row");
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("median"));
		$this->tpl->setVariable("TEXT_OPTION_VALUE", $cumulated_results["MEDIAN"]);
		$this->tpl->parseCurrentBlock();
		
		$this->tpl->setCurrentBlock("detail_row");
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("categories"));
		$categories = "";
		foreach ($cumulated_results["variables"] as $key => $value)
		{
			$categories .= "<li>" . $this->lng->txt("title") . ":" . "<span class=\"bold\">" . $value["title"] . "</span><br />" .
				$this->lng->txt("category_nr_selected") . ": " . "<span class=\"bold\">" . $value["selected"] . "</span><br />" .
				$this->lng->txt("percentage_of_selections") . ": " . "<span class=\"bold\">" . sprintf("%.2f", 100*$value["percentage"]) . "</span></li>";
		}
		$categories = "<ol>$categories</ol>";
		$this->tpl->setVariable("TEXT_OPTION_VALUE", $categories);
		$this->tpl->parseCurrentBlock();
		
		// display chart for matrix question for array $eval["variables"]
		$this->tpl->setVariable("TEXT_CHART", $this->lng->txt("chart"));
		$this->tpl->setVariable("ALT_CHART", $data["title"] . "( " . $this->lng->txt("chart") . ")");
		$this->tpl->setVariable("CHART","./Modules/SurveyQuestionPool/displaychart.php?grName=" . urlencode($this->object->getTitle()) . 
			"&type=bars" . 
			"&x=" . urlencode($this->lng->txt("answers")) . 
			"&y=" . urlencode($this->lng->txt("users_answered")) . 
			"&arr=".base64_encode(serialize($cumulated_results["variables"])));
		
		$this->tpl->setCurrentBlock("detail");
		$this->tpl->setVariable("QUESTION_TITLE", "$counter. ".$this->object->getTitle());
		$this->tpl->parseCurrentBlock();
	}

/**
* Adds a category to the question
*
* Adds a category to the question
*
* @access private
*/
	function addCategory()
	{
		$result = $this->writeCategoryData();
		if ($result == false)
		{
			sendInfo($this->lng->txt("fill_out_all_category_fields"));
		}
		$_SESSION["spl_modified"] = true;
		$this->categories($result);
	}

/**
* Recreates the categories from the POST data
*
* Recreates the categories from the POST data and
* saves it (optionally) to the database.
*
* @param boolean $save If set to true the POST data will be saved to the database
* @access private
*/
	function writeCategoryData($save = false)
	{
    // Delete all existing categories and create new categories from the form data
    $this->object->categories->flushCategories();
		$complete = true;
		$array1 = array();
    // Add all categories from the form into the object
		include_once "./classes/class.ilUtil.php";
		foreach ($_POST as $key => $value) 
		{
			if (preg_match("/^category_(\d+)/", $key, $matches)) 
			{
				$array1[$matches[1]] = ilUtil::stripSlashes($value);
			}
		}
		$this->object->categories->addCategoryArray($array1);
		if ($save)
		{	
			$this->object->saveCategoriesToDb();
		}
		return $complete;
	}
	
}
?>
