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

require_once "class.SurveyMetricQuestion.php";

/**
* Metric survey question GUI representation
*
* The SurveyMetricQuestionGUI class encapsulates the GUI representation
* for metric survey question types.
*
* @author		Helmut Schottm�ller <hschottm@tzi.de>
* @version	$Id$
* @module   class.SurveyMetricQuestionGUI.php
* @modulegroup   Survey
*/
class SurveyMetricQuestionGUI {
/**
* Question object
*
* A reference to the metric question object
*
* @var object
*/
  var $object;

	var $tpl;
	var $lng;

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
		global $lng;
		global $tpl;

    $this->lng =& $lng;
    $this->tpl =& $tpl;

		$this->object = new SurveyMetricQuestion();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
	}

/**
* Returns the question type string
*
* Returns the question type string
*
* @result string The question type string
* @access public
*/
	function getQuestionType()
	{
		return "qt_metric";
	}

/**
* Creates an output of the edit form for the question
*
* Creates an output of the edit form for the question
*
* @access public
*/
  function showEditForm() {
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_metric.html", true);
	  $this->tpl->addBlockFile("OTHER_QUESTION_DATA", "other_question_data", "tpl.il_svy_qpl_other_question_data.html", true);
		// call to other question data
		$this->outOtherQuestionData();
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("QUESTION_ID", $this->object->getId());
		$this->tpl->setVariable("VALUE_TITLE", $this->object->getTitle());
		$this->tpl->setVariable("VALUE_DESCRIPTION", $this->object->getDescription());
		$this->tpl->setVariable("VALUE_AUTHOR", $this->object->getAuthor());
		$this->tpl->setVariable("VALUE_QUESTION", $this->object->getQuestiontext());
		$this->tpl->setVariable("VALUE_MINIMUM", $this->object->getMinimum());
		$this->tpl->setVariable("VALUE_MAXIMUM", $this->object->getMaximum());
		$this->tpl->setVariable("TEXT_MINIMUM", $this->lng->txt("minimum"));
		$this->tpl->setVariable("TEXT_MAXIMUM", $this->lng->txt("maximum"));
		$this->tpl->setVariable("TEXT_NONRATIO", $this->lng->txt("non_ratio"));
		$this->tpl->setVariable("TEXT_RATIONONABSOLUTE", $this->lng->txt("ratio_non_absolute"));
		$this->tpl->setVariable("TEXT_RATIOABSOLUTE", $this->lng->txt("ratio_absolute"));
		if ($this->object->getSubtype() == SUBTYPE_NON_RATIO)
		{
			$this->tpl->setVariable("SELECTED_NONRATIO", " selected=\"selected\"");
		}
		else if ($this->object->getSubtype() == SUBTYPE_RATIO_NON_ABSOLUTE)
		{
			$this->tpl->setVariable("SELECTED_RATIONONABSOLUTE", " selected=\"selected\"");
		}
		else if ($this->object->getSubtype() == SUBTYPE_RATIO_ABSOLUTE)
		{
			$this->tpl->setVariable("SELECTED_RATIOABSOLUTE", " selected=\"selected\"");
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
		$this->tpl->setVariable("APPLY", $this->lng->txt("apply"));
		$this->tpl->setVariable("CANCEL",$this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->setVariable("FORM_ACTION", $_SERVER["PHP_SELF"] . "?ref_id=" . $_GET["ref_id"] . "&cmd=questions&sel_question_types=qt_metric");
		$this->tpl->parseCurrentBlock();
  }

/**
* Sets the extra fields i.e. estimated working time and material of a question from a posted create/edit form
*
* Sets the extra fields i.e. estimated working time and material of a question from a posted create/edit form
*
* @access private
*/
  function outOtherQuestionData() {
		if (!empty($this->object->materials)) {
			$this->tpl->setCurrentBlock("mainselect_block");
			$this->tpl->setCurrentBlock("select_block");
			foreach ($this->object->materials as $key => $value) {
				$this->tpl->setVariable("MATERIAL_VALUE", $key);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("materiallist_block");
			$i = 1;
			foreach ($this->object->materials as $key => $value) {
				$this->tpl->setVariable("MATERIAL_COUNTER", $i);
				$this->tpl->setVariable("MATERIAL_VALUE", $key);
				$this->tpl->setVariable("MATERIAL_FILE_VALUE", $value);
				$this->tpl->parseCurrentBlock();
				$i++;
			}
			$this->tpl->setVariable("UPLOADED_MATERIAL", $this->lng->txt("uploaded_material"));
			$this->tpl->setVariable("VALUE_MATERIAL_DELETE", $this->lng->txt("delete"));
			$this->tpl->setVariable("COLSPAN_MATERIAL", " colspan=\"3\"");
			$this->tpl->parse("mainselect_block");
		}

		$this->tpl->setVariable("TEXT_MATERIAL", $this->lng->txt("material"));
		$this->tpl->setVariable("TEXT_MATERIAL_FILE", $this->lng->txt("material_file"));
		$this->tpl->setVariable("VALUE_MATERIAL_UPLOAD", $this->lng->txt("upload"));
		$this->tpl->setVariable("COLSPAN_MATERIAL", " colspan=\"3\"");
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
  function writePostData() {
    $result = 0;
    if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]))
      $result = 1;

    // Set the question id from a hidden form parameter
    if ($_POST["id"] > 0)
      $this->object->setId($_POST["id"]);

    $this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
    $this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
    $this->object->setDescription(ilUtil::stripSlashes($_POST["description"]));
    $this->object->setQuestiontext(ilUtil::stripSlashes($_POST["question"]));
		if ($_POST["obligatory"])
		{
			$this->object->setObligatory(1);
		}
		else
		{
			$this->object->setObligatory(0);
		}
		$this->object->setSubtype($_POST["type"]);
		$this->object->setMinimum($_POST["minimum"]);
		$this->object->setMaximum($_POST["maximum"]);
    // adding materials uris
    $saved = $this->writeOtherPostData();

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
	function outWorkingForm()
	{
		$this->tpl->setCurrentBlock("question_data_metric");
		$this->tpl->setVariable("QUESTIONTEXT", $this->object->getQuestiontext());
		$this->tpl->setVariable("TEXT_ANSWER", $this->lng->txt("answer"));
		$this->tpl->setVariable("TEXT_MINIMUM", $this->lng->txt("minimum"));
		$this->tpl->setVariable("VALUE_MINIMUM", $this->object->getMinimum());
		$this->tpl->setVariable("TEXT_MAXIMUM", $this->lng->txt("maximum"));
		$this->tpl->setVariable("VALUE_MAXIMUM", $this->object->getMaximum());

		if (strlen($this->object->getMaximum())>1) {
			$len = strlen($this->object->getMaximum()) + 2;
			$this->tpl->setVariable("INPUT_SIZE", $len);
		}
		else {
			$this->tpl->setVariable("INPUT_SIZE", 10);
		}

		$this->tpl->parseCurrentBlock();
	}

/**
* Creates a preview of the question
*
* Creates a preview of the question
*
* @access private
*/
	function outPreviewForm()
	{
		$this->tpl->addBlockFile("METRIC", "metric", "tpl.il_svy_out_metric.html", true);
		$this->outWorkingForm();
	}

/**
* Sets the other data i.e. materials uris of a question from a posted create/edit form
*
* Sets the other data i.e. materials uris of a question from a posted create/edit form
*
* @return boolean Returns true, if the question had to be autosaved to get a question id for the save path of the material, otherwise returns false.
* @access private
*/
	function writeOtherPostData() {
		// Add all materials uris from the form into the object
		$saved = false;
		$this->object->flushMaterials();
		foreach ($_POST as $key => $value) {
			if (preg_match("/material_list_/", $key, $matches)) {
				$this->object->addMaterials($value, str_replace("material_list_", "", $key));
			}
		}
		if (!empty($_FILES['materialFile']['tmp_name'])) {
			if ($this->object->getId() <= 0) {
				$this->object->saveToDb();
				$saved = true;
				sendInfo($this->lng->txt("question_saved_for_upload"));
			}
			$this->object->setMaterialsFile($_FILES['materialFile']['name'], $_FILES['materialFile']['tmp_name'], $_POST[materialName]);
		}

		// Delete material if the delete button was pressed
		if ((strlen($_POST["cmd"]["deletematerial"]) > 0)&&(!empty($_POST[materialselect]))) {
			foreach ($_POST[materialselect] as $value) {
				$this->object->deleteMaterial($value);
			}
		}
		return $saved;
	}


}
?>
