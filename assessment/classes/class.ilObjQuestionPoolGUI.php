<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/


/**
* Class ilObjQuestionPoolGUI
*
* @author Helmut Schottm�ller <hschottm@tzi.de>
* $Id$
*
* @ilCtrl_Calls ilObjQuestionPoolGUI: ilPageObjectGUI
* @ilCtrl_Calls ilObjQuestionPoolGUI: ASS_MultipleChoiceGUI, ASS_ClozeTestGUI, ASS_MatchingQuestionGUI
* @ilCtrl_Calls ilObjQuestionPoolGUI: ASS_OrderingQuestionGUI, ASS_ImagemapQuestionGUI, ASS_JavaAppletGUI
*
* @extends ilObjectGUI
* @package ilias-core
* @package assessment
*/

require_once "./classes/class.ilObjectGUI.php";
require_once "./assessment/classes/class.assQuestionGUI.php";
require_once "./assessment/classes/class.ilObjQuestionPool.php";
require_once "./classes/class.ilMetaDataGUI.php";

class ilObjQuestionPoolGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjQuestionPoolGUI($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
	{
    	global $lng, $ilCtrl;

		$this->type = "qpl";
    	$lng->loadLanguageModule("assessment");
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference, false);
		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this, "ref_id");

		if (!defined("ILIAS_MODULE"))
		{
			$this->setTabTargetScript("adm_object.php");
		}
		else
		{
			$this->setTabTargetScript("questionpool.php");
		}
		if ($a_prepare_output)
		{
			include_once("classes/class.ilObjStyleSheet.php");
			$this->prepareOutput();
			$this->tpl->setCurrentBlock("ContentStyle");
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
				ilObjStyleSheet::getContentStylePath(0));
			$this->tpl->parseCurrentBlock();

			// syntax style
			$this->tpl->setCurrentBlock("SyntaxStyle");
			$this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
				ilObjStyleSheet::getSyntaxStylePath());
			$this->tpl->parseCurrentBlock();

		}
//echo "<br>ilObjQuestionPool_End of constructor.";
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass($this);
		$this->ctrl->setReturn($this, "questions");

		if ($_GET["q_id"] < 1)
		{
			$q_type = ($_POST["sel_question_types"] != "")
				? $_POST["sel_question_types"]
				: $_GET["sel_question_types"];
		}

//echo "<br>nextclass:$next_class:cmd:$cmd:";
		switch($next_class)
		{
			case "ilpageobjectgui":

				$q_gui =& ASS_QuestionGUI::_getQuestionGUI("", $_GET["q_id"]);
				$q_gui->object->setObjId($this->object->getId());
				$question =& $q_gui->object;
				$this->ctrl->saveParameter($this, "q_id");

				include_once("content/classes/Pages/class.ilPageObjectGUI.php");
				$this->lng->loadLanguageModule("content");
				$this->setPageEditorTabs();
				$this->ctrl->setReturnByClass("ilPageObjectGUI", "view");
				$this->ctrl->setReturn($this, "questions");

				$page =& new ilPageObject("qpl", $_GET["q_id"]);
				$page_gui =& new ilPageObjectGUI($page);
				$page_gui->setQuestionXML($question->to_xml(false, false, true));
				$page_gui->setTemplateTargetVar("ADM_CONTENT");
				$page_gui->setOutputMode("edit");
				$page_gui->setHeader($question->getTitle());
				$page_gui->setFileDownloadLink("questionpool.php?cmd=downloadFile".
					"&amp;ref_id=".$_GET["ref_id"]);
				$page_gui->setSourcecodeDownloadScript("questionpool.php?ref_id=".$_GET["ref_id"]);
				/*
				$page_gui->setTabs(array(array("cont_all_definitions", "listDefinitions"),
						array("edit", "view"),
						array("cont_preview", "preview"),
						array("meta_data", "editDefinitionMetaData")
						));*/
				$page_gui->setPresentationTitle($question->getTitle());
				//$page_gui->executeCommand();

				$ret =& $this->ctrl->forwardCommand($page_gui);

				break;


			case "ass_multiplechoicegui":
				$this->setAdminTabs($_POST["new_type"]);
				$this->ctrl->setReturn($this, "questions");
				$q_gui =& ASS_QuestionGUI::_getQuestionGUI($q_type, $_GET["q_id"]);
				$q_gui->object->setObjId($this->object->getId());
				$ret =& $this->ctrl->forwardCommand($q_gui);
				break;

			case "ass_clozetestgui":
				$this->setAdminTabs($_POST["new_type"]);
				$this->ctrl->setReturn($this, "questions");
				$q_gui =& ASS_QuestionGUI::_getQuestionGUI($q_type, $_GET["q_id"]);
				$q_gui->object->setObjId($this->object->getId());
				$ret =& $this->ctrl->forwardCommand($q_gui);
				break;

			case "ass_orderingquestiongui":
				$this->setAdminTabs($_POST["new_type"]);
				$this->ctrl->setReturn($this, "questions");
				$q_gui =& ASS_QuestionGUI::_getQuestionGUI($q_type, $_GET["q_id"]);
				$q_gui->object->setObjId($this->object->getId());
				$ret =& $this->ctrl->forwardCommand($q_gui);
				break;

			case "ass_matchingquestiongui":
				$this->setAdminTabs($_POST["new_type"]);
				$this->ctrl->setReturn($this, "questions");
				$q_gui =& ASS_QuestionGUI::_getQuestionGUI($q_type, $_GET["q_id"]);
				$q_gui->object->setObjId($this->object->getId());
				$ret =& $this->ctrl->forwardCommand($q_gui);
				break;

			case "ass_imagemapquestiongui":
				$this->setAdminTabs($_POST["new_type"]);
				$this->ctrl->setReturn($this, "questions");
				$q_gui =& ASS_QuestionGUI::_getQuestionGUI($q_type, $_GET["q_id"]);
				$q_gui->object->setObjId($this->object->getId());
				$ret =& $this->ctrl->forwardCommand($q_gui);
				break;

			case "ass_javaappletgui":
				$this->setAdminTabs($_POST["new_type"]);
				$this->ctrl->setReturn($this, "questions");
				$q_gui =& ASS_QuestionGUI::_getQuestionGUI($q_type, $_GET["q_id"]);
				$q_gui->object->setObjId($this->object->getId());
				$ret =& $this->ctrl->forwardCommand($q_gui);
				break;

			default:
				$this->setAdminTabs($_POST["new_type"]);
				$cmd.= "Object";
				$ret =& $this->$cmd();
				break;
		}
	}

	/**
	* download file
	*/
	function downloadFileObject()
	{
		$file = explode("_", $_GET["file_id"]);
		require_once("classes/class.ilObjFile.php");
		$fileObj =& new ilObjFile($file[count($file) - 1], false);
		$fileObj->sendFile();
		exit;
	}

	/**
	* set question list filter
	*/
	function filterObject()
	{
		$this->questionsObject();
	}

	/**
	* resets filter
	*/
	function resetFilterObject()
	{
		$_POST["filter_text"] = "";
		$_POST["sel_filter_type"] = "";
		$this->questionsObject();
	}

	/**
	* download source code paragraph
	*/
	function download_paragraphObject()
	{
		require_once("content/classes/Pages/class.ilPageObject.php");
		$pg_obj =& new ilPageObject("qpl", $_GET["pg_id"]);
		$pg_obj->send_paragraph ($_GET["par_id"], $_GET["downloadtitle"]);
		exit;
	}

	/**
	* imports question(s) into the questionpool
	*/
	function uploadObject()
	{
		// check if file was uploaded
		$source = $_FILES["qtidoc"]["tmp_name"];
		$error = 0;
		if (($source == 'none') || (!$source) || $_FILES["qtidoc"]["error"] > UPLOAD_ERR_OK)
		{
//			$this->ilias->raiseError("No file selected!",$this->ilias->error_obj->MESSAGE);
			$error = 1;
		}
		// check correct file type
		if (strcmp($_FILES["qtidoc"]["type"], "text/xml") != 0)
		{
//			$this->ilias->raiseError("Wrong file type!",$this->ilias->error_obj->MESSAGE);
			$error = 1;
		}
		if (!$error)
		{
			// import file into questionpool
			$fh = fopen($source, "r") or die("");
			$xml = fread($fh, filesize($source));
			fclose($fh) or die("");
			if (preg_match_all("/(<item[^>]*>.*?<\/item>)/si", $xml, $matches))
			{
				foreach ($matches[1] as $index => $item)
				{
					$question = "";
					if (preg_match("/<qticomment>Questiontype\=(.*?)<\/qticomment>/is", $item, $questiontype))
					{
						switch ($questiontype[1])
						{
							case CLOZE_TEST_IDENTIFIER:
								$question = new ASS_ClozeTest();
								break;
							case IMAGEMAP_QUESTION_IDENTIFIER:
								$question = new ASS_ImagemapQuestion();
								break;
							case MATCHING_QUESTION_IDENTIFIER:
								$question = new ASS_MatchingQuestion();
								break;
							case MULTIPLE_CHOICE_QUESTION_IDENTIFIER:
								$question = new ASS_MultipleChoice();
								break;
							case ORDERING_QUESTION_IDENTIFIER:
								$question = new ASS_OrderingQuestion();
								break;
						}
						if ($question)
						{
							$question->setObjId($this->object->getId());
							$question->from_xml("<questestinterop>$item</questestinterop>");
							$question->saveToDb();
						}
					}
				}
			}
		}
		$this->questionsObject();
	}
	
	/**
	* display the import form to import questions into the questionpool
	*/
		function importObject()
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_as_import_question.html", true);
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("TEXT_IMPORT_QUESTION", $this->lng->txt("import_question"));
		$this->tpl->setVariable("TEXT_SELECT_FILE", $this->lng->txt("select_file"));
		$this->tpl->setVariable("TEXT_UPLOAD", $this->lng->txt("upload"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* create new question
	*/
	function &createQuestionObject()
	{
// echo "<br>create--".$_POST["sel_question_types"];
		$q_gui =& ASS_QuestionGUI::_getQuestionGUI($_POST["sel_question_types"]);
		$q_gui->object->setObjId($this->object->getId());
		$this->ctrl->setCmdClass(get_class($q_gui));
		$this->ctrl->setCmd("editQuestion");

		$ret =& $this->executeCommand();
		return $ret;
	}

	/**
	* save object
	* @access	public
	*/
	function saveObject()
	{
		global $rbacadmin;

		// create and insert forum in objecttree
		$newObj = parent::saveObject();

		// setup rolefolder & default local roles
		//$roles = $newObj->initDefaultRoles();

		// ...finally assign role to creator of object
		//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "y");

		// put here object specific stuff

		// always send a message
		sendInfo($this->lng->txt("object_added"),true);

		$returnlocation = "questionpool.php";
		if (!defined("ILIAS_MODULE"))
		{
			$returnlocation = "adm_object.php";
		}
		header("Location:".$this->getReturnLocation("save","$returnlocation?".$this->link_params));
		exit();
	}


	/**
	* cancel action
	*/
	function cancelObject()
	{
		unset($_SESSION["ass_q_id"]);
		$this->ctrl->redirect($this, "questions");
	}

	/**
	* Creates the create/edit template form of a question
	*
	* Creates the create/edit template form of a question and fills it with
	* that data of the question.
	*
	* @access public
	*/
	/*
	function editQuestionForm($type)
	{
echo "<br>ilObjQuestionPoolGUI->editQuestionForm()"; exit;
		if ($_POST["cmd"]["unlock_no"])
		{
	    	header("location:" . $_SERVER["PHP_SELF"] . "?ref_id=" . $_GET["ref_id"] . "&cmd=questions");
			exit;
		}

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.il_as_qpl_content.html", true);
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");

		if (!$type)
		{
			$type = $this->object->getQuestiontype($_GET["edit"]);
		}

		// catch feedback message
		sendInfo();

		// First of all: Load question data from database
		$question =& $this->object->createQuestion($type);
		if (($_POST["id"] > 0) or ($_GET["edit"] > 0))
		{
			$question->object->loadFromDb($_POST["id"] + $_GET["edit"]);
		}


		$this->setLocator("", "", "", $question->object->getTitle());
		$missing_required_fields = 0;

		// cancel action
		if (strlen($_POST["cmd"]["cancel"]) > 0)
		{
			// Cancel
			$this->cancel_action();
			exit();
		}

		$question->object->setObjId($this->obj_id);
		$question_type = $question->getQuestionType();

		// set screen title (Edit/Create Question)
		if ($question->object->id > 0)
		{
			$title = $this->lng->txt("edit") . " " . $this->lng->txt($question_type);
		}
		else
		{
			$title = $this->lng->txt("create_new") . " " . $this->lng->txt($question_type);
		}
		$this->tpl->setVariable("HEADER", $title);

		// lock confirmation
		if ($_GET["locked"] == 1)
		{
			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_as_qpl_askunlock.html", true);
			$this->tpl->setCurrentBlock("adm_content");
			$this->tpl->setVariable("FORM_ACTION", $_SERVER["PHP_SELF"] . $this->getAddParameter() . "&edit=" . $_GET["edit"]);
			$this->tpl->setVariable("BUTTON_YES", $this->lng->txt("unlock"));
			$this->tpl->setVariable("BUTTON_NO", $this->lng->txt("cancel"));
			$this->tpl->setVariable("UNLOCK_QUESTION", sprintf($this->lng->txt("unlock_question"), $this->object->isInUse($_GET["edit"])));
			$this->tpl->parseCurrentBlock();
			return;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_as_question.html", true);

		if ((!$_GET["edit"]) and (!$_POST["cmd"]["create"]))
		{
			$missing_required_fields = $question->writePostData();
		}

		$question->showEditForm();

		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->parseCurrentBlock();
	}*/


	/**
	* show assessment data of object
	*/
	function assessmentObject()
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.il_as_qpl_content.html", true);
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");

		// catch feedback message
		sendInfo();

		$this->setLocator();

		$title = $this->lng->txt("qpl_assessment_of_questions");
		if (!empty($title))
		{
			$this->tpl->setVariable("HEADER", $title);
		}
		$question =& $this->object->createQuestion("", $_GET["q_id"]);
		$total_of_answers = $question->getTotalAnswers();
		$counter = 0;
		$color_class = array("tblrow1", "tblrow2");
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_as_qpl_assessment_of_questions.html", true);
		if (!$total_of_answers)
		{
			$this->tpl->setCurrentBlock("emptyrow");
			$this->tpl->setVariable("TXT_NO_ASSESSMENT", $this->lng->txt("qpl_assessment_no_assessment_of_questions"));
			$this->tpl->setVariable("COLOR_CLASS", $color_class[$counter % 2]);
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$this->tpl->setCurrentBlock("row");
			$this->tpl->setVariable("TXT_RESULT", $this->lng->txt("qpl_assessment_total_of_answers"));
			$this->tpl->setVariable("TXT_VALUE", $total_of_answers);
			$this->tpl->setVariable("COLOR_CLASS", $color_class[$counter % 2]);
			$counter++;
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("row");
			$this->tpl->setVariable("TXT_RESULT", $this->lng->txt("qpl_assessment_total_of_right_answers"));
			$this->tpl->setVariable("TXT_VALUE", sprintf("%2.2f", $this->object->get_total_right_answers($_GET["edit"]) * 100.0) . " %");
			$this->tpl->setVariable("COLOR_CLASS", $color_class[$counter % 2]);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("TXT_QUESTION_TITLE", $question->object->getTitle());
		$this->tpl->setVariable("TXT_RESULT", $this->lng->txt("result"));
		$this->tpl->setVariable("TXT_VALUE", $this->lng->txt("value"));
		$this->tpl->parseCurrentBlock();
	}

	function getAddParameter()
	{
		return "?ref_id=" . $_GET["ref_id"] . "&cmd=" . $_GET["cmd"];
	}

	function questionObject()
	{
//echo "<br>ilObjQuestionPoolGUI->questionObject()";
		$type = $_GET["sel_question_types"];
		$this->editQuestionForm($type);
		//$this->set_question_form($type, $_GET["edit"]);
	}

	/**
	* delete questions confirmation screen
	*/
	function deleteQuestionsObject()
	{
//echo "<br>ilObjQuestionPoolGUI->deleteQuestions()";
		// duplicate button was pressed
		if (count($_POST["q_id"]) < 1)
		{
			sendInfo($this->lng->txt("qpl_delete_select_none"), true);
			$this->ctrl->redirect($this, "questions");
		}

		$checked_questions = $_POST["q_id"];
		$_SESSION["ass_q_id"] = $_POST["q_id"];
		sendInfo();
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_as_qpl_confirm_delete_questions.html", true);

		// buidling SQL statements is not allowed in GUI classes!
		$whereclause = join($checked_questions, " OR qpl_questions.question_id = ");
		$whereclause = " AND (qpl_questions.question_id = " . $whereclause . ")";
		$query = "SELECT qpl_questions.*, qpl_question_type.type_tag FROM qpl_questions, qpl_question_type WHERE qpl_questions.question_type_fi = qpl_question_type.question_type_id$whereclause ORDER BY qpl_questions.title";
		$query_result = $this->ilias->db->query($query);
		$colors = array("tblrow1", "tblrow2");
		$counter = 0;
		$img_locked = "<img src=\"" . ilUtil::getImagePath("locked.gif", true) . "\" alt=\"" . $this->lng->txt("locked") . "\" title=\"" . $this->lng->txt("locked") . "\" border=\"0\" />";
		if ($query_result->numRows() > 0)
		{
			while ($data = $query_result->fetchRow(DB_FETCHMODE_OBJECT))
			{
				if (in_array($data->question_id, $checked_questions))
				{
					$this->tpl->setCurrentBlock("row");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					if ($this->object->isInUse($data->question_id))
					{
						$this->tpl->setVariable("TXT_LOCKED", $img_locked);
					}
					$this->tpl->setVariable("TXT_TITLE", $data->title);
					$this->tpl->setVariable("TXT_DESCRIPTION", $data->comment);
					$this->tpl->setVariable("TXT_TYPE", $this->lng->txt($data->type_tag));
					$this->tpl->parseCurrentBlock();
					$counter++;
				}
			}
		}
		foreach ($checked_questions as $id)
		{
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("HIDDEN_NAME", "id_$id");
			$this->tpl->setVariable("HIDDEN_VALUE", "1");
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("tst_question_title"));
		$this->tpl->setVariable("TXT_DESCRIPTION", $this->lng->txt("description"));
		$this->tpl->setVariable("TXT_TYPE", $this->lng->txt("tst_question_type"));
		$this->tpl->setVariable("TXT_LOCKED", $this->lng->txt("locked"));
		$this->tpl->setVariable("BTN_CONFIRM", $this->lng->txt("confirm"));
		$this->tpl->setVariable("BTN_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("FORM_ACTION", $_SERVER['PHP_SELF'] . $this->getAddParameter());
		$this->tpl->parseCurrentBlock();
	}


	/**
	* delete questions
	*/
	function confirmDeleteQuestionsObject()
	{
		// delete questions after confirmation
		sendInfo($this->lng->txt("qpl_questions_deleted"), true);
		foreach ($_SESSION["ass_q_id"] as $key => $value)
		{
			$this->object->deleteQuestion($value);
		}
		$this->ctrl->redirect($this, "questions");
	}

	/**
	* duplicate a question
	*/
	function duplicateObject()
	{
		// duplicate button was pressed
		if (count($_POST["q_id"]) > 0)
		{
			foreach ($_POST["q_id"] as $key => $value)
			{
				$this->object->duplicateQuestion($value);
			}
		}
		else
		{
			sendInfo($this->lng->txt("qpl_duplicate_select_none"), true);
		}
		$this->ctrl->redirect($this, "questions");
	}

	/**
	* export question
	*/
	function exportObject()
	{
		// export button was pressed
		if (count($_POST["q_id"]) > 0)
		{
			foreach ($_POST["q_id"] as $key => $value)
			{
				$question =& $this->object->createQuestion("", $value);
				$xml .= $question->object->to_xml();
			}
			if (count($_POST["q_id"]) > 1)
			{
				$xml = preg_replace("/<\/questestinterop>\s*<.xml.*?>\s*<questestinterop>/", "", $xml);
			}
			$questiontitle = $question->object->getTitle();
			$questiontitle = preg_replace("/[\s]/", "_", $questiontitle);
			ilUtil::deliverData($xml, "$questiontitle.xml");
			exit();
		}
		else
		{
			sendInfo($this->lng->txt("qpl_export_select_none"), true);
		}
		$this->ctrl->redirect($this, "questions");
	}


	/**
	* list questions of question pool
	*/
	function questionsObject()
	{
		global $rbacsystem;

		$type = $_GET["sel_question_types"];

		// reset test_id SESSION variable
		$_SESSION["test_id"] = "";
		$add_parameter = $this->getAddParameter();

		// create an array of all checked checkboxes
		$checked_questions = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/cb_(\d+)/", $key, $matches))
			{
				array_push($checked_questions, $matches[1]);
			}
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.qpl_questions.html", true);
		if ($rbacsystem->checkAccess('write', $this->ref_id))
		{
			$this->tpl->addBlockFile("CREATE_QUESTION", "create_question", "tpl.il_as_create_new_question.html", true);
			$this->tpl->addBlockFile("A_BUTTONS", "a_buttons", "tpl.il_as_qpl_action_buttons.html", true);
		}
		$this->tpl->addBlockFile("FILTER_QUESTION_MANAGER", "filter_questions", "tpl.il_as_qpl_filter_questions.html", true);

		// create filter form
		$filter_fields = array(
			"title" => $this->lng->txt("title"),
			"comment" => $this->lng->txt("description"),
			"author" => $this->lng->txt("author"),
		);
		$this->tpl->setCurrentBlock("filterrow");
		foreach ($filter_fields as $key => $value)
		{
			$this->tpl->setVariable("VALUE_FILTER_TYPE", "$key");
			$this->tpl->setVariable("NAME_FILTER_TYPE", "$value");
			if (strcmp($_POST["sel_filter_type"], $key) == 0)
			{
				$this->tpl->setVariable("VALUE_FILTER_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("filter_questions");
		$this->tpl->setVariable("FILTER_TEXT", $this->lng->txt("filter"));
		$this->tpl->setVariable("TEXT_FILTER_BY", $this->lng->txt("by"));
		$this->tpl->setVariable("VALUE_FILTER_TEXT", $_POST["filter_text"]);
		$this->tpl->setVariable("VALUE_SUBMIT_FILTER", $this->lng->txt("set_filter"));
		$this->tpl->setVariable("VALUE_RESET_FILTER", $this->lng->txt("reset_filter"));
		$this->tpl->parseCurrentBlock();

		// create edit buttons & table footer
		if ($rbacsystem->checkAccess('write', $this->ref_id))
		{
			$this->tpl->setCurrentBlock("standard");
			$this->tpl->setVariable("DELETE", $this->lng->txt("delete"));
			$this->tpl->setVariable("DUPLICATE", $this->lng->txt("duplicate"));
			$this->tpl->setVariable("EXPORT", $this->lng->txt("export"));
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("Footer");
			$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"\">");
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("QTab");

		// reset the filter
		$startrow = 0;
		if ($_GET["prevrow"])
		{
			$startrow = $_GET["prevrow"];
		}
		if ($_GET["nextrow"])
		{
			$startrow = $_GET["nextrow"];
		}
		if ($_GET["startrow"])
		{
			$startrow = $_GET["startrow"];
		}
		$table = $this->object->getQuestionsTable($_GET["sort"], $_POST["filter_text"], $_POST["sel_filter_type"], $startrow);
		$colors = array("tblrow1", "tblrow2");
		$img_locked = "<img src=\"" . ilUtil::getImagePath("locked.gif", true) . "\" alt=\"" . $this->lng->txt("locked") . "\" title=\"" . $this->lng->txt("locked") . "\" border=\"0\" />";
		$counter = 0;
		$editable = $rbacsystem->checkAccess('write', $this->ref_id);
		foreach ($table["rows"] as $data)
		{
			if (($data["private"] != 1) or ($data["owner"] == $this->ilias->account->id))
			{
				$this->tpl->setVariable("QUESTION_ID", $data["question_id"]);
				if ($editable)
				{
					$class = strtolower(ASS_QuestionGUI::_getGUIClassNameForId($data["question_id"]));

					$this->ctrl->setParameterByClass("ilpageobjectgui", "q_id", $data["question_id"]);
					$this->ctrl->setParameterByClass($class, "q_id", $data["question_id"]);
					if ($this->object->isInUse($data["question_id"]))
					{
						//$this->ctrl->setParameterByClass($class, "locked", "1");
						//$link = $this->ctrl->getLinkTarget("
						$this->tpl->setVariable("TXT_EDIT", $img_locked);
						$this->tpl->setVariable("LINK_EDIT",
							$this->ctrl->getLinkTargetByClass("ilpageobjectgui", "view"));
						//$this->tpl->setVariable("EDIT", "<a href=\"" . $_SERVER["PHP_SELF"] . "?ref_id=" . $_GET["ref_id"] . "&cmd=question&edit=" . $data["question_id"] . "&locked=1\">" . $img_locked . "</a>");
					}
					else
					{
						//$this->ctrl->setParameterByClass($class, "locked", "");
						$this->tpl->setVariable("TXT_EDIT", $this->lng->txt("edit"));
						$this->tpl->setVariable("LINK_EDIT",
							$this->ctrl->getLinkTargetByClass("ilpageobjectgui", "view"));
						//$this->tpl->setVariable("EDIT", "[<a href=\"" . $_SERVER["PHP_SELF"] . "?ref_id=" . $_GET["ref_id"] . "&cmd=question&edit=" . $data["question_id"] . "\">" . $this->lng->txt("edit") . "</a>]");
					}
				}
				$this->tpl->setVariable("QUESTION_TITLE", "<strong>" .$data["title"] . "</strong>");

				//$this->tpl->setVariable("PREVIEW", "[<a href=\"" . $_SERVER["PHP_SELF"] . "$add_parameter&preview=" . $data["question_id"] . "\">" . $this->lng->txt("preview") . "</a>]");

				$this->tpl->setVariable("TXT_PREVIEW", $this->lng->txt("preview"));
				$this->tpl->setVariable("LINK_PREVIEW",
					$this->ctrl->getLinkTargetByClass("ilpageobjectgui", "preview"));

				$this->tpl->setVariable("QUESTION_COMMENT", $data["comment"]);
				$this->tpl->setVariable("QUESTION_TYPE", $this->lng->txt($data["type_tag"]));
				//$this->tpl->setVariable("QUESTION_ASSESSMENT", "<a href=\"".
				//	$_SERVER["PHP_SELF"] . "?ref_id=" . $_GET["ref_id"] . "&cmd=assessment&edit=" . $data["question_id"].""."\"><img src=\"".ilUtil::getImagePath("assessment.gif", true) . "\" alt=\"" . $this->lng->txt("qpl_assessment_of_questions") . "\" title=\"" . $this->lng->txt("qpl_assessment_of_questions") . "\" border=\"0\" /></a>");
				$this->tpl->setVariable("LINK_ASSESSMENT",
					$this->ctrl->getLinkTargetByClass($class, "assessment"));
				$this->tpl->setVariable("TXT_ASSESSMENT", $this->lng->txt("qpl_assessment_of_questions"));
				$this->tpl->setVariable("IMG_ASSESSMENT",
					ilUtil::getImagePath("assessment.gif", true));
				$this->tpl->setVariable("QUESTION_AUTHOR", $data["author"]);
				$this->tpl->setVariable("QUESTION_CREATED", ilFormat::formatDate(ilFormat::ftimestamp2dateDB($data["created"]), "date"));
				$this->tpl->setVariable("QUESTION_UPDATED", ilFormat::formatDate(ilFormat::ftimestamp2dateDB($data["TIMESTAMP"]), "date"));
				$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
				$this->tpl->parseCurrentBlock();
				$counter++;
			}
		}

		if ($table["rowcount"] > count($table["rows"]))
		{
			$this->tpl->setCurrentBlock("navigation_bottom");
			$this->tpl->setVariable("PREV_ROWS", sprintf($this->lng->txt("previous_question_rows"), $table["prevrow"] + 1, $table["prevrow"] + $table["step"], $table["rowcount"]));
			$nextstep = $table["nextrow"] + $table["step"];
			if ($nextstep > $table["rowcount"])
			{
				$nextstep = $table["rowcount"];
			}
			$sort = "";
			if (is_array($_GET["sort"]))
			{
				$key = key($_GET["sort"]);
				$sort = "&sort[$key]=" . $_GET["sort"]["$key"];
			}
			$this->tpl->setVariable("NEXT_ROWS", sprintf($this->lng->txt("next_question_rows"), $table["nextrow"] + 1, $nextstep, $table["rowcount"]));
			$this->tpl->setVariable("HREF_PREV_ROWS", $_SERVER['PHP_SELF'] . $add_parameter . "$sort&prevrow=" . $table["prevrow"]);
			$this->tpl->setVariable("HREF_NEXT_ROWS", $_SERVER['PHP_SELF'] . $add_parameter . "$sort&nextrow=" . $table["nextrow"]);
			$this->tpl->parseCurrentBlock();
		}

		// if there are no questions, display a message
		if ($counter == 0)
		{
			$this->tpl->setCurrentBlock("Emptytable");
			$this->tpl->setVariable("TEXT_EMPTYTABLE", $this->lng->txt("no_questions_available"));
			$this->tpl->parseCurrentBlock();
		}

		if ($rbacsystem->checkAccess('write', $this->ref_id))
		{
			// "create question" form
			$this->tpl->setCurrentBlock("QTypes");
			$query = "SELECT * FROM qpl_question_type ORDER BY question_type_id";
			$query_result = $this->ilias->db->query($query);
			while ($data = $query_result->fetchRow(DB_FETCHMODE_OBJECT))
			{
// temporary disable imagemap and java questions
				if ($data->type_tag != "qt_javaapplet" && $data->type_tag != "qt_imagemap")
				{
					$this->tpl->setVariable("QUESTION_TYPE_ID", $data->type_tag);
					$this->tpl->setVariable("QUESTION_TYPE", $this->lng->txt($data->type_tag));
					$this->tpl->parseCurrentBlock();
				}
			}
			$this->tpl->setCurrentBlock("CreateQuestion");
			$this->tpl->setVariable("QUESTION_ADD", $this->lng->txt("create"));
			$this->tpl->setVariable("ACTION_QUESTION_ADD", $_SERVER["PHP_SELF"] . $add_parameter);
			$this->tpl->setVariable("QUESTION_IMPORT", $this->lng->txt("import"));
			$this->tpl->parseCurrentBlock();
		}

		// define the sort column parameters
		$sort = array(
			"title" => $_GET["sort"]["title"],
			"comment" => $_GET["sort"]["comment"],
			"type" => $_GET["sort"]["type"],
			"author" => $_GET["sort"]["author"],
			"created" => $_GET["sort"]["created"],
			"updated" => $_GET["sort"]["updated"]
		);
		foreach ($sort as $key => $value)
		{
			if (strcmp($value, "ASC") == 0)
			{
				$sort[$key] = "DESC";
			}
			else
			{
				$sort[$key] = "ASC";
			}
		}

		$this->tpl->setCurrentBlock("adm_content");
		// create table header
		$this->tpl->setVariable("QUESTION_TITLE", "<a href=\"" . $_SERVER["PHP_SELF"] . "$add_parameter&startrow=" . $table["startrow"] . "&sort[title]=" . $sort["title"] . "\">" . $this->lng->txt("title") . "</a>" . $table["images"]["title"]);
		$this->tpl->setVariable("QUESTION_COMMENT", "<a href=\"" . $_SERVER["PHP_SELF"] . "$add_parameter&startrow=" . $table["startrow"] . "&sort[comment]=" . $sort["comment"] . "\">" . $this->lng->txt("description") . "</a>". $table["images"]["comment"]);
		$this->tpl->setVariable("QUESTION_TYPE", "<a href=\"" . $_SERVER["PHP_SELF"] . "$add_parameter&startrow=" . $table["startrow"] . "&sort[type]=" . $sort["type"] . "\">" . $this->lng->txt("question_type") . "</a>" . $table["images"]["type"]);
		$this->tpl->setVariable("QUESTION_AUTHOR", "<a href=\"" . $_SERVER["PHP_SELF"] . "$add_parameter&startrow=" . $table["startrow"] . "&sort[author]=" . $sort["author"] . "\">" . $this->lng->txt("author") . "</a>" . $table["images"]["author"]);
		$this->tpl->setVariable("QUESTION_CREATED", "<a href=\"" . $_SERVER["PHP_SELF"] . "$add_parameter&startrow=" . $table["startrow"] . "&sort[created]=" . $sort["created"] . "\">" . $this->lng->txt("create_date") . "</a>" . $table["images"]["created"]);
		$this->tpl->setVariable("QUESTION_UPDATED", "<a href=\"" . $_SERVER["PHP_SELF"] . "$add_parameter&startrow=" . $table["startrow"] . "&sort[updated]=" . $sort["updated"] . "\">" . $this->lng->txt("last_update") . "</a>" . $table["images"]["updated"]);
		$this->tpl->setVariable("BUTTON_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("ACTION_QUESTION_FORM", $this->ctrl->getFormAction($this));
		$this->tpl->parseCurrentBlock();
	}

	function editMetaObject()
	{
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->edit("ADM_CONTENT", "adm_content",
			"questionpool.php?ref_id=".$_GET["ref_id"]."&cmd=saveMeta");
	}

	function saveMetaObject()
	{
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->save($_POST["meta_section"]);
		ilUtil::redirect("questionpool.php?ref_id=".$_GET["ref_id"]);
	}

	// called by administration
	function chooseMetaSectionObject($a_script = "",
		$a_templ_var = "ADM_CONTENT", $a_templ_block = "adm_content")
	{
		if ($a_script == "")
		{
			$a_script = "questionpool.php?ref_id=".$_GET["ref_id"];
		}
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->edit($a_templ_var, $a_templ_block, $a_script, $_REQUEST["meta_section"]);
	}

	// called by editor
	function chooseMetaSection()
	{
		$this->chooseMetaSectionObject("questionpool.php?ref_id=".
			$this->object->getRefId());
	}

	function addMetaObject($a_script = "",
		$a_templ_var = "ADM_CONTENT", $a_templ_block = "adm_content")
	{
		if ($a_script == "")
		{
			$a_script = "questionpool.php?ref_id=".$_GET["ref_id"];
		}
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_name = $_POST["meta_name"] ? $_POST["meta_name"] : $_GET["meta_name"];
		$meta_index = $_POST["meta_index"] ? $_POST["meta_index"] : $_GET["meta_index"];
		if ($meta_index == "")
			$meta_index = 0;
		$meta_path = $_POST["meta_path"] ? $_POST["meta_path"] : $_GET["meta_path"];
		$meta_section = $_POST["meta_section"] ? $_POST["meta_section"] : $_GET["meta_section"];
		if ($meta_name != "")
		{
			$meta_gui->meta_obj->add($meta_name, $meta_path, $meta_index);
		}
		else
		{
			sendInfo($this->lng->txt("meta_choose_element"), true);
		}
		$meta_gui->edit($a_templ_var, $a_templ_block, $a_script, $meta_section);
	}

	function addMeta()
	{
		$this->addMetaObject("questionpool.php?ref_id=".
			$this->object->getRefId());
	}

	function deleteMetaObject($a_script = "",
		$a_templ_var = "ADM_CONTENT", $a_templ_block = "adm_content")
	{
		if ($a_script == "")
		{
			$a_script = "questionpool.php?ref_id=".$_GET["ref_id"];
		}
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_index = $_POST["meta_index"] ? $_POST["meta_index"] : $_GET["meta_index"];
		$meta_gui->meta_obj->delete($_GET["meta_name"], $_GET["meta_path"], $meta_index);
		$meta_gui->edit($a_templ_var, $a_templ_block, $a_script, $_GET["meta_section"]);
	}

	function deleteMeta()
	{
		$this->deleteMetaObject("questionpool.php?ref_id=".
			$this->object->getRefId());
	}

	function updateObject()
	{
		$this->update = $this->object->updateMetaData();
		sendInfo($this->lng->txt("msg_obj_modified"), true);
	}

	/**
	* show permissions of current node
	*
	* @access	public
	*/
	function permObject()
	{
		global $rbacsystem, $rbacreview;
		static $num = 0;

		if (!$rbacsystem->checkAccess("edit_permission", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->MESSAGE);
			exit();
		}

		// only display superordinate roles; local roles with other scope are not displayed
		$parentRoles = $rbacreview->getParentRoleIds($this->object->getRefId());

		$data = array();

		// GET ALL LOCAL ROLE IDS
		$role_folder = $rbacreview->getRoleFolderOfObject($this->object->getRefId());

		$local_roles = array();

		if ($role_folder)
		{
			$local_roles = $rbacreview->getRolesOfRoleFolder($role_folder["ref_id"]);
		}

		foreach ($parentRoles as $key => $r)
		{
			if ($r["obj_id"] == SYSTEM_ROLE_ID)
			{
				unset($parentRoles[$key]);
				continue;
			}

			if (!in_array($r["obj_id"],$local_roles))
			{
				$data["check_inherit"][] = ilUtil::formCheckBox(0,"stop_inherit[]",$r["obj_id"]);
			}
			else
			{
				$r["link"] = true;

				// don't display a checkbox for local roles AND system role
				if ($rbacreview->isAssignable($r["obj_id"],$role_folder["ref_id"]))
				{
					$data["check_inherit"][] = "&nbsp;";
				}
				else
				{
					// linked local roles with stopped inheritance
					$data["check_inherit"][] = ilUtil::formCheckBox(1,"stop_inherit[]",$r["obj_id"]);
				}
			}

			$data["roles"][] = $r;
		}

		$ope_list = getOperationList($this->object->getType());

		// BEGIN TABLE_DATA_OUTER
		foreach ($ope_list as $key => $operation)
		{
			$opdata = array();

			$opdata["name"] = $operation["operation"];

			$colspan = count($parentRoles) + 1;

			foreach ($parentRoles as $role)
			{
				$checked = $rbacsystem->checkPermission($this->object->getRefId(), $role["obj_id"],$operation["operation"],$_GET["parent"]);
				$disabled = false;

				// Es wird eine 2-dim Post Variable bergeben: perm[rol_id][ops_id]
				$box = ilUtil::formCheckBox($checked,"perm[".$role["obj_id"]."][]",$operation["ops_id"],$disabled);
				$opdata["values"][] = $box;
			}

			$data["permission"][] = $opdata;
		}

		/////////////////////
		// START DATA OUTPUT
		/////////////////////

		$this->getTemplateFile("perm");
		$this->tpl->setCurrentBlock("tableheader");
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("permission_settings"));
		$this->tpl->setVariable("COLSPAN", $colspan);
		$this->tpl->setVariable("TXT_OPERATION", $this->lng->txt("operation"));
		$this->tpl->setVariable("TXT_ROLES", $this->lng->txt("roles"));
		$this->tpl->parseCurrentBlock();

		$num = 0;

		foreach($data["roles"] as $role)
		{
			// BLOCK ROLENAMES
			if ($role["link"])
			{
				$this->tpl->setCurrentBlock("ROLELINK_OPEN");
				$this->tpl->setVariable("LINK_ROLE_RULESET","questionpool.php?ref_id=".$role_folder["ref_id"]."&obj_id=".$role["obj_id"]."&cmd=perm");
				$this->tpl->setVariable("TXT_ROLE_RULESET",$this->lng->txt("edit_perm_ruleset"));
				$this->tpl->parseCurrentBlock();

				$this->tpl->touchBlock("ROLELINK_CLOSE");
			}

			$this->tpl->setCurrentBlock("ROLENAMES");
			$this->tpl->setVariable("ROLE_NAME",$role["title"]);
			$this->tpl->parseCurrentBlock();

			// BLOCK CHECK INHERIT
			if ($this->objDefinition->stopInheritance($this->type))
			{
				$this->tpl->setCurrentBLock("CHECK_INHERIT");
				$this->tpl->setVariable("CHECK_INHERITANCE",$data["check_inherit"][$num]);
				$this->tpl->parseCurrentBlock();
			}

			$num++;
		}

		// save num for required column span and the end of parsing
		$colspan = $num + 1;
		$num = 0;

		// offer option 'stop inheritance' only to those objects where this option is permitted
		if ($this->objDefinition->stopInheritance($this->type))
		{
			$this->tpl->setCurrentBLock("STOP_INHERIT");
			$this->tpl->setVariable("TXT_STOP_INHERITANCE", $this->lng->txt("stop_inheritance"));
			$this->tpl->parseCurrentBlock();
		}

		foreach ($data["permission"] as $ar_perm)
		{
			foreach ($ar_perm["values"] as $box)
			{
				// BEGIN TABLE CHECK PERM
				$this->tpl->setCurrentBlock("CHECK_PERM");
				$this->tpl->setVariable("CHECK_PERMISSION",$box);
				$this->tpl->parseCurrentBlock();
				// END CHECK PERM
			}

			// BEGIN TABLE DATA OUTER
			$this->tpl->setCurrentBlock("TABLE_DATA_OUTER");
			$css_row = ilUtil::switchColor($num++, "tblrow1", "tblrow2");
			$this->tpl->setVariable("CSS_ROW",$css_row);
			$this->tpl->setVariable("PERMISSION", $this->lng->txt($this->object->getType()."_".$ar_perm["name"]));
			$this->tpl->parseCurrentBlock();
			// END TABLE DATA OUTER
		}

		// ADD LOCAL ROLE - Skip that until I know how it works with the module folder
		if (false)
		// if ($this->object->getRefId() != ROLE_FOLDER_ID and $rbacsystem->checkAccess('create_role',$this->object->getRefId()))
		{
			$this->tpl->setCurrentBlock("LOCAL_ROLE");

			// fill in saved values in case of error
			$data = array();
			$data["fields"] = array();
			$data["fields"]["title"] = $_SESSION["error_post_vars"]["Fobject"]["title"];
			$data["fields"]["desc"] = $_SESSION["error_post_vars"]["Fobject"]["desc"];

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);
			}

			$this->tpl->setVariable("FORMACTION_LR",$this->getFormAction("addRole", "questionpool.php?ref_id=".$_GET["ref_id"]."&cmd=addRole"));
			$this->tpl->setVariable("TXT_HEADER", $this->lng->txt("you_may_add_local_roles"));
			$this->tpl->setVariable("TXT_ADD", $this->lng->txt("role_add_local"));
			$this->tpl->setVariable("TARGET", $this->getTargetFrame("addRole"));
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
			$this->tpl->parseCurrentBlock();
		}

		// PARSE BLOCKFILE
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORMACTION",
		$this->getFormAction("permSave","questionpool.php?".$this->link_params."&cmd=permSave"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("COL_ANZ",$colspan);
		$this->tpl->parseCurrentBlock();
	}

	/**
	* set Locator
	*
	* @param	object	tree object
	* @param	integer	reference id
	* @param	scriptanme that is used for linking; if not set adm_object.php is used
	* @access	public
	*/
	function setLocator($a_tree = "", $a_id = "", $scriptname="repository.php", $question_title = "")
	{
//echo "<br>ilObjQuestionPoolGUI->setLocator()";
		$ilias_locator = new ilLocatorGUI(false);
		if (!is_object($a_tree))
		{
			$a_tree =& $this->tree;
		}
		if (!($a_id))
		{
			$a_id = $_GET["ref_id"];
		}
		if (!($scriptname))
		{
			$scriptname = "repository.php";
		}
		$path = $a_tree->getPathFull($a_id);
		//check if object isn't in tree, this is the case if parent_parent is set
		// TODO: parent_parent no longer exist. need another marker
		if ($a_parent_parent)
		{
			//$subObj = getObject($a_ref_id);
			$subObj =& $this->ilias->obj_factory->getInstanceByRefId($a_ref_id);

			$path[] = array(
				"id"	 => $a_ref_id,
				"title"  => $this->lng->txt($subObj->getTitle())
			);
		}

		// this is a stupid workaround for a bug in PEAR:IT
		$modifier = 1;

		if (isset($_GET["obj_id"]))
		{
			$modifier = 0;
		}

		// ### AA 03.11.10 added new locator GUI class ###
		$i = 1;

		if (!defined("ILIAS_MODULE"))
		{
			foreach ($path as $key => $row)
			{
				$ilias_locator->navigate($i++, $row["title"], ILIAS_HTTP_PATH . "/adm_object.php?ref_id=".$row["child"],"bottom");
			}
		}
		else
		{
			foreach ($path as $key => $row)
			{
				if (strcmp($row["title"], "ILIAS") == 0)
				{
					$row["title"] = $this->lng->txt("repository");
				}
				if ($this->ref_id == $row["child"])
				{
					$param = "&cmd=questions";
					$ilias_locator->navigate($i++, $row["title"], ILIAS_HTTP_PATH . "/assessment/questionpool.php" . "?ref_id=".$row["child"] . $param,"bottom");
					switch ($_GET["cmd"])
					{
						case "question":
							$id = $_GET["edit"];
							if (!$id)
							{
								$id = $_POST["id"];
							}
							if ($question_title)
							{
								$ilias_locator->navigate($i++, $question_title, ILIAS_HTTP_PATH . "/assessment/questionpool.php" . "?ref_id=".$row["child"] . "&cmd=question&edit=$id","bottom");
							}
							break;
					}
				}
				else
				{
					$ilias_locator->navigate($i++, $row["title"], ILIAS_HTTP_PATH . "/" . $scriptname."?ref_id=".$row["child"],"bottom");
				}
			}

			if (isset($_GET["obj_id"]))
			{
				$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($_GET["obj_id"]);
				$ilias_locator->navigate($i++,$obj_data->getTitle(),$scriptname."?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"],"bottom");
			}
		}
		$ilias_locator->output(true);
	}

	function prepareOutput()
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		$title = $this->object->getTitle();

		// catch feedback message
		sendInfo();

		if (!empty($title))
		{
			$this->tpl->setVariable("HEADER", $title);
		}

		$this->setLocator();

	}


	/**
	* output tabs
	*/
	function setPageEditorTabs()
	{

		// catch feedback message
		include_once("classes/class.ilTabsGUI.php");
		$tabs_gui =& new ilTabsGUI();
		$this->getPageEditorTabs($tabs_gui);

		$this->tpl->setVariable("TABS", $tabs_gui->getHTML());

	}

	/**
	* get tabs
	*/
	function getPageEditorTabs(&$tabs_gui)
	{
		// edit page
		$tabs_gui->addTarget("edit",
			$this->ctrl->getLinkTargetByClass("ilPageObjectGUI", "view"), "view",
			"ilPageObjectGUI");

		// preview page
		$tabs_gui->addTarget("cont_preview",
			$this->ctrl->getLinkTargetByClass("ilPageObjectGUI", "preview"), "preview",
			"ilPageObjectGUI");

		// properties
		/*
		$tabs_gui->addTarget("meta_data",
			$this->ctrl->getLinkTarget($this, "editMeta"), "editMeta",
			get_class($this));*/

		// back to upper context
		$tabs_gui->addTarget("cont_back",
			$this->ctrl->getLinkTarget($this, "questions"), "questions",
			"ilObjQuestionPoolGUI");

	}

} // END class.ilObjQuestionPoolGUI
?>
