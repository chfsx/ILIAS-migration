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
* Metric survey question GUI representation
*
* The SurveyMetricQuestionGUI class encapsulates the GUI representation
* for metric survey question types.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @extends SurveyQuestionGUI
* @ingroup ModulesSurveyQuestionPool
*/
class SurveyMetricQuestionGUI extends SurveyQuestionGUI 
{
/**
* SurveyMetricQuestionGUI constructor
*
* The constructor takes possible arguments an creates an instance of the SurveyMetricQuestionGUI object.
*
* @param integer $id The database id of a metric question object
* @access public
*/
  function SurveyMetricQuestionGUI(
		$id = -1
  )

  {
		$this->SurveyQuestionGUI();
		include_once "./Modules/SurveyQuestionPool/classes/class.SurveyMetricQuestion.php";
		$this->object = new SurveyMetricQuestion();
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
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_metric.html", "Modules/SurveyQuestionPool");
	  $this->tpl->addBlockFile("OTHER_QUESTION_DATA", "other_question_data", "tpl.il_svy_qpl_other_question_data.html", "Modules/SurveyQuestionPool");
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
		$this->tpl->setVariable("QUESTION_ID", $this->object->getId());
		$this->tpl->setVariable("VALUE_TITLE", $this->object->getTitle());
		$this->tpl->setVariable("VALUE_DESCRIPTION", $this->object->getDescription());
		$this->tpl->setVariable("VALUE_AUTHOR", $this->object->getAuthor());
		$questiontext = $this->object->getQuestiontext();
		$this->tpl->setVariable("VALUE_QUESTION", $this->object->prepareTextareaOutput($questiontext));
		$this->tpl->setVariable("VALUE_MINIMUM", $this->object->getMinimum());
		$this->tpl->setVariable("VALUE_MAXIMUM", $this->object->getMaximum());
		$this->tpl->setVariable("TEXT_MINIMUM", $this->lng->txt("minimum"));
		$this->tpl->setVariable("TEXT_MAXIMUM", $this->lng->txt("maximum"));
		$this->tpl->setVariable("TEXT_SUBTYPE", $this->lng->txt("subtype"));
		$this->tpl->setVariable("DESCRIPTION_NONRATIO", $this->lng->txt("metric_subtype_description_interval"));
		$this->tpl->setVariable("TEXT_NONRATIO", $this->lng->txt("non_ratio"));
		$this->tpl->setVariable("DESCRIPTION_RATIONONABSOLUTE", $this->lng->txt("metric_subtype_description_rationonabsolute"));
		$this->tpl->setVariable("TEXT_RATIONONABSOLUTE", $this->lng->txt("ratio_non_absolute"));
		$this->tpl->setVariable("DESCRIPTION_RATIOABSOLUTE", $this->lng->txt("metric_subtype_description_ratioabsolute"));
		$this->tpl->setVariable("TEXT_RATIOABSOLUTE", $this->lng->txt("ratio_absolute"));
		if ($this->object->getSubtype() == SUBTYPE_NON_RATIO)
		{
			$this->tpl->setVariable("CHECKED_NONRATIO", " checked=\"checked\"");
		}
		else if ($this->object->getSubtype() == SUBTYPE_RATIO_NON_ABSOLUTE)
		{
			$this->tpl->setVariable("CHECKED_RATIONONABSOLUTE", " checked=\"checked\"");
		}
		else if ($this->object->getSubtype() == SUBTYPE_RATIO_ABSOLUTE)
		{
			$this->tpl->setVariable("CHECKED_RATIOABSOLUTE", " checked=\"checked\"");
		}
		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
		$this->tpl->setVariable("TEXT_DESCRIPTION", $this->lng->txt("description"));
		$this->tpl->setVariable("TEXT_QUESTION", $this->lng->txt("question"));
		$this->tpl->setVariable("TEXT_OBLIGATORY", $this->lng->txt("obligatory"));
		if ($this->object->getObligatory())
		{
			$this->tpl->setVariable("CHECKED_OBLIGATORY", " checked=\"checked\"");
		}
		$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt("questiontype"));
		$this->tpl->setVariable("SAVE",$this->lng->txt("save"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt($this->getQuestionType()));
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
		$this->object->setSubtype($_POST["type"]);
		$minimum = $_POST["minimum"];
		if ($this->object->getSubtype() > 3)
		{
			if ($minimum < 0)
			{
				$this->errormessage = $this->lng->txt("ratio_scale_ge_zero");
				$result = 1;
			}
		}
		$this->object->setMinimum($minimum);
		$this->object->setMaximum($_POST["maximum"]);

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
* Creates the question output form for the learner
*
* Creates the question output form for the learner
*
* @access public
*/
	function getWorkingForm($working_data = "", $question_title = 1, $error_message = "")
	{
		$template = new ilTemplate("tpl.il_svy_out_metric.html", TRUE, TRUE, "Modules/SurveyQuestionPool");
		if (count($this->object->material))
		{
			$template->setCurrentBlock("material_metric");
			include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
			$href = SurveyQuestion::_getInternalLinkHref($this->object->material["internal_link"]);
			$template->setVariable("TEXT_MATERIAL", $this->lng->txt("material") . ": <a href=\"$href\" target=\"content\">" . $this->object->material["title"]. "</a> ");
			$template->parseCurrentBlock();
		}

		if (strlen($this->object->getMinimum()))
		{
			$template->setCurrentBlock("minimum");
			$template->setVariable("TEXT_MINIMUM", $this->lng->txt("minimum"));
			$template->setVariable("VALUE_MINIMUM", $this->object->getMinimum());
			$template->parseCurrentBlock();
		}
		if (strlen($this->object->getMaximum()))
		{
			$template->setCurrentBlock("maximum");
			$template->setVariable("TEXT_MAXIMUM", $this->lng->txt("maximum"));
			$template->setVariable("VALUE_MAXIMUM", $this->object->getMaximum());
			$template->parseCurrentBlock();
		}

		$template->setCurrentBlock("question_data_metric");
		$questiontext = $this->object->getQuestiontext();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		if (! $this->object->getObligatory())
		{
			$template->setVariable("OBLIGATORY_TEXT", $this->lng->txt("survey_question_optional"));
		}
		if ($question_title)
		{
			$template->setVariable("QUESTION_TITLE", $this->object->getTitle());
		}
		$template->setVariable("TEXT_ANSWER", $this->lng->txt("answer"));
		$template->setVariable("QUESTION_ID", $this->object->getId());
		if (is_array($working_data))
		{
			$template->setVariable("VALUE_METRIC", $working_data[0]["value"]);
		}

		if (strlen($this->object->getMaximum())>1) 
		{
			$len = strlen($this->object->getMaximum()) + 2;
			$template->setVariable("INPUT_SIZE", $len);
		}
		else {
			$template->setVariable("INPUT_SIZE", 10);
		}

		if (strcmp($error_message, "") != 0)
		{
			$template->setVariable("ERROR_MESSAGE", "<p class=\"warning\">$error_message</p>");
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

	function setQuestionTabs()
	{
		$this->setQuestionTabsForClass("surveymetricquestiongui");
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
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("subtype"));
		switch ($this->object->getSubType())
		{
			case SUBTYPE_NON_RATIO:
				$this->tpl->setVariable("TEXT_OPTION_VALUE", $this->lng->txt("non_ratio"));
				break;
			case SUBTYPE_RATIO_NON_ABSOLUTE:
				$this->tpl->setVariable("TEXT_OPTION_VALUE", $this->lng->txt("ratio_non_absolute"));
				break;
			case SUBTYPE_RATIO_ABSOLUTE:
				$this->tpl->setVariable("TEXT_OPTION_VALUE", $this->lng->txt("ratio_absolute"));
				break;
		}
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
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("arithmetic_mean"));
		$this->tpl->setVariable("TEXT_OPTION_VALUE", $cumulated_results["ARITHMETIC_MEAN"]);
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("detail_row");
		$this->tpl->setVariable("TEXT_OPTION", $this->lng->txt("values"));
		$values = "";
		if (is_array($cumulated_results["values"]))
		{
			foreach ($cumulated_results["values"] as $key => $value)
			{
				$values .= "<li>" . $this->lng->txt("value") . ": " . "<span class=\"bold\">" . $value["value"] . "</span><br />" .
					$this->lng->txt("value_nr_entered") . ": " . "<span class=\"bold\">" . $value["selected"] . "</span><br />" .
					$this->lng->txt("percentage_of_entered_values") . ": " . "<span class=\"bold\">" . sprintf("%.2f", 100*$value["percentage"]) . "</span></li>";
			}
		}
		$values = "<ol>$values</ol>";
		$this->tpl->setVariable("TEXT_OPTION_VALUE", $values);
		$this->tpl->parseCurrentBlock();
		
		// display chart for metric question for array $eval["values"]
		$this->tpl->setVariable("TEXT_CHART", $this->lng->txt("chart"));
		$this->tpl->setVariable("ALT_CHART", $data["title"] . "( " . $this->lng->txt("chart") . ")");
		$this->tpl->setVariable("CHART","./Modules/SurveyQuestionPool/displaychart.php?grName=" . urlencode($this->object->getTitle()) . 
			"&type=bars" . 
			"&x=" . urlencode($this->lng->txt("answers")) . 
			"&y=" . urlencode($this->lng->txt("users_answered")) . 
			"&arr=".base64_encode(serialize($cumulated_results["values"])));
		
		$this->tpl->setCurrentBlock("detail");
		$this->tpl->setVariable("QUESTION_TITLE", "$counter. ".$this->object->getTitle());
		$this->tpl->parseCurrentBlock();
	}
}
?>
