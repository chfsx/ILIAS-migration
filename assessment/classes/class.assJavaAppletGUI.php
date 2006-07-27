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

include_once "./assessment/classes/class.assQuestionGUI.php";
include_once "./assessment/classes/inc.AssessmentConstants.php";

/**
* Java applet question GUI representation
*
* The assJavaAppletGUI class encapsulates the GUI representation
* for java applet questions.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @module   class.assJavaAppletGUI.php
* @modulegroup   Assessment
*/
class assJavaAppletGUI extends assQuestionGUI
{
	/**
	* assJavaAppletGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the assJavaAppletGUI object.
	*
	* @param integer $id The database id of a image map question object
	* @access public
	*/
	function assJavaAppletGUI(
		$id = -1
	)
	{
		$this->assQuestionGUI();
		include_once "./assessment/classes/class.assJavaApplet.php";
		$this->object = new assJavaApplet();
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
		return "assJavaApplet";
	}

	function getCommand($cmd)
	{
		if (substr($cmd, 0, 6) == "delete")
		{
			$cmd = "delete";
		}
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
		$this->getQuestionTemplate("qt_javaapplet");
		$this->tpl->addBlockFile("QUESTION_DATA", "question_data", "tpl.il_as_qpl_javaapplet_question.html", true);
		if ($this->error)
		{
			sendInfo($this->error);
		}
		// call to other question data i.e. estimated working time block
		$this->outOtherQuestionData();
		// image block
		$this->tpl->setCurrentBlock("post_save");

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
		
		$this->tpl->setVariable("TEXT_SOLUTION_HINT", $this->lng->txt("solution_hint"));
		if (count($this->object->suggested_solutions))
		{
			$solution_array = $this->object->getSuggestedSolution(0);
			include_once "./assessment/classes/class.assQuestion.php";
			$href = assQuestion::_getInternalLinkHref($solution_array["internal_link"]);
			$this->tpl->setVariable("TEXT_VALUE_SOLUTION_HINT", " <a href=\"$href\" target=\"content\">" . $this->lng->txt("solution_hint"). "</a> ");
			$this->tpl->setVariable("BUTTON_REMOVE_SOLUTION", $this->lng->txt("remove"));
			$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("change"));
			$this->tpl->setVariable("VALUE_SOLUTION_HINT", $solution_array["internal_link"]);
		}
		else
		{
			$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("add"));
		}
		
		// java applet block
		$javaapplet = $this->object->getJavaAppletFilename();
		$this->tpl->setVariable("TEXT_JAVAAPPLET", $this->lng->txt("javaapplet"));
		if (!empty($javaapplet))
		{
			$this->tpl->setVariable("JAVAAPPLET_FILENAME", $javaapplet);
			$this->tpl->setVariable("VALUE_JAVAAPPLET_UPLOAD", $this->lng->txt("change"));
			$this->tpl->setCurrentBlock("javaappletupload");
			$this->tpl->setVariable("UPLOADED_JAVAAPPLET", $javaapplet);
			$this->tpl->parse("javaappletupload");
			$this->tpl->setCurrentBlock("delete_applet");
			$this->tpl->setVariable("VALUE_JAVAAPPLET_DELETE", $this->lng->txt("delete"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$this->tpl->setVariable("VALUE_JAVAAPPLET_UPLOAD", $this->lng->txt("upload"));
		}
		$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("available_points"));
		$this->tpl->setVariable("VALUE_APPLET_POINTS", sprintf("%d", $this->object->getPoints()));
		$this->tpl->parseCurrentBlock();

		
		if ((strlen($this->object->getTitle()) > 0) && (strlen($this->object->getAuthor()) > 0) && (strlen($this->object->getQuestion()) > 0) && ($this->object->getPoints() > 0))
		{
			$emptyname = 0;
			for ($i = 0; $i < $this->object->getParameterCount(); $i++)
			{
				// create template for existing applet parameters
				$this->tpl->setCurrentBlock("delete_parameter");
				$this->tpl->setVariable("VALUE_DELETE_PARAMETER", $this->lng->txt("delete"));
				$this->tpl->setVariable("DELETE_PARAMETER_COUNT", $i);
				$this->tpl->parseCurrentBlock();
				$this->tpl->setCurrentBlock("applet_parameter");
				$this->tpl->setVariable("PARAM_PARAM", $this->lng->txt("applet_parameter") . " " . ($i+1));
				$this->tpl->setVariable("PARAM_NAME", $this->lng->txt("name"));
				$this->tpl->setVariable("PARAM_VALUE", $this->lng->txt("value"));
				$param = $this->object->getParameter($i);
				$this->tpl->setVariable("PARAM_NAME_VALUE", $param["name"]);
				$this->tpl->setVariable("PARAM_VALUE_VALUE", $param["value"]);
				$this->tpl->setVariable("PARAM_COUNTER", $i);
				$this->tpl->parseCurrentBlock();
				if (!$param["name"])
				{
					$emptyname = 1;
				}
			}
			if ($this->ctrl->getCmd() == "addParameter")
			{
				if ($emptyname == 0)
				{
					// create template for new applet parameter
					$this->tpl->setCurrentBlock("applet_parameter");
					$this->tpl->setVariable("PARAM_PARAM", $this->lng->txt("applet_new_parameter"));
					$this->tpl->setVariable("PARAM_NAME", $this->lng->txt("name"));
					$this->tpl->setVariable("PARAM_VALUE", $this->lng->txt("value"));
					$this->tpl->setVariable("PARAM_COUNTER", $this->object->getParameterCount());
					$this->tpl->parseCurrentBlock();
				}
				else
				{
					sendInfo($this->lng->txt("too_many_empty_parameters"));
				}
			}
			if (!strlen($javaapplet))
			{
				$this->tpl->setVariable("TEXT_ARCHIVE", $this->lng->txt("archive"));
				$this->tpl->setVariable("VALUE_ARCHIVE", $this->object->getJavaArchive());
				$this->tpl->setVariable("TEXT_CODEBASE", $this->lng->txt("codebase"));
				$this->tpl->setVariable("VALUE_CODEBASE", $this->object->getJavaCodebase());
			}

			$this->tpl->setCurrentBlock("appletcode");
			$this->tpl->setVariable("APPLET_ATTRIBUTES", $this->lng->txt("applet_attributes"));
			$this->tpl->setVariable("TEXT_CODE", $this->lng->txt("code"));
			$this->tpl->setVariable("TEXT_WIDTH", $this->lng->txt("width"));
			$this->tpl->setVariable("TEXT_HEIGHT", $this->lng->txt("height"));
			$this->tpl->setVariable("VALUE_CODE", $this->object->getJavaCode());
			$this->tpl->setVariable("VALUE_WIDTH", $this->object->getJavaWidth());
			$this->tpl->setVariable("VALUE_HEIGHT", $this->object->getJavaHeight());
			$this->tpl->setVariable("APPLET_PARAMETERS", $this->lng->txt("applet_parameters"));
			$this->tpl->setVariable("VALUE_ADD_PARAMETER", $this->lng->txt("add_applet_parameter"));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("HeadContent");
		$javascript = "<script type=\"text/javascript\">function initialSelect() {\n%s\n}</script>";
		$this->tpl->setVariable("CONTENT_BLOCK", sprintf($javascript, "document.frm_javaapplet.title.focus();"));
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("question_data");
		$this->tpl->setVariable("JAVAAPPLET_ID", $this->object->getId());
		$this->tpl->setVariable("VALUE_JAVAAPPLET_TITLE", ilUtil::prepareFormOutput($this->object->getTitle()));
		$this->tpl->setVariable("VALUE_JAVAAPPLET_COMMENT", ilUtil::prepareFormOutput($this->object->getComment()));
		$this->tpl->setVariable("VALUE_JAVAAPPLET_AUTHOR", ilUtil::prepareFormOutput($this->object->getAuthor()));
		$questiontext = $this->object->getQuestion();
		$this->tpl->setVariable("VALUE_QUESTION", ilUtil::prepareFormOutput($questiontext));
		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
		$this->tpl->setVariable("TEXT_COMMENT", $this->lng->txt("description"));
		$this->tpl->setVariable("TEXT_QUESTION", $this->lng->txt("question"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));

		$this->tpl->setVariable("SAVE",$this->lng->txt("save"));
		$this->tpl->setVariable("SAVE_EDIT", $this->lng->txt("save_edit"));
		$this->tpl->setVariable("CANCEL",$this->lng->txt("cancel"));
		$this->ctrl->setParameter($this, "sel_question_types", "assJavaApplet");
		$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt("assJavaApplet"));
		$formaction = $this->ctrl->getFormaction($this);
		if ($this->object->getId() > 0)
		{
			if (!preg_match("/q_id\=\d+/", $formaction))
			{
				$formaction = str_replace("q_id=", "q_id=" . $this->object->getId(), $formaction);
			}
		}
		$this->tpl->setVariable("ACTION_JAVAAPPLET_QUESTION", $formaction);
		$this->tpl->parseCurrentBlock();
		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->addPlugin("latex");
		$rte->addButton("latex");
		include_once "./classes/class.ilObject.php";
		$obj_id = $_GET["q_id"];
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, $obj_type, "assessment");

		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("BODY_ATTRIBUTES", " onload=\"initialSelect();\""); 
		$this->tpl->parseCurrentBlock();

	}


	/**
	* save question to db and return to question pool
	*/
	function uploadingJavaApplet()
	{
		$result = $this->writePostData();
		if ($result == 0)
		{
			$this->object->saveToDb();
		}
		$this->editQuestion();
	}


	/**
	* save question to db and return to question pool
	*/
	function removeJavaapplet()
	{
		$this->object->deleteJavaAppletFilename();
		$this->object->saveToDb();
		$this->editQuestion();
	}

	/**
	* save question to db and return to question pool
	*/
	function addParameter()
	{
		$this->writePostData();
		$this->editQuestion();
	}

	/**
	* delete a parameter
	*/
	function delete()
	{
		$this->writePostData();
		$this->editQuestion();
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
		$saved = false;
		if (!$this->checkInput())
		{
			$result = 1;
		}

		$this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
		$this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
		$this->object->setComment(ilUtil::stripSlashes($_POST["comment"]));
		include_once "./classes/class.ilObjAdvancedEditing.php";
		$questiontext = ilUtil::stripSlashes($_POST["question"], true, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment"));
		$questiontext = preg_replace("/[\n\r]+/", "<br />", $questiontext);
		$this->object->setQuestion($questiontext);
		$this->object->setSuggestedSolution($_POST["solution_hint"], 0);
		$this->object->setShuffle($_POST["shuffle"]);
		$this->object->setPoints($_POST["applet_points"]);
		if ($_POST["applet_points"] < 0)
		{
			$result = 1;
			$this->setErrorMessage($this->lng->txt("negative_points_not_allowed"));
		}
		// adding estimated working time
		$saved = $saved | $this->writeOtherPostData($result);

		if ($result == 0)
		{
			//setting java applet
			if (empty($_FILES['javaappletName']['tmp_name']))
			{
				$this->object->setJavaAppletFilename(ilUtil::stripSlashes($_POST['uploaded_javaapplet']));
			}
			else
			{
				if ($this->object->getId() < 1)
				{
					$saved = 1;
					$this->object->saveToDb();
				}
				$this->object->setJavaAppletFilename($_FILES['javaappletName']['name'], $_FILES['javaappletName']['tmp_name']);
			}
			if ((strlen($this->object->getTitle()) > 0) && (strlen($this->object->getAuthor()) > 0) && (strlen($this->object->getQuestion()) > 0) && ($this->object->getPoints() > 0) && array_key_exists("java_height", $_POST))
			{
				$this->object->setJavaCode($_POST["java_code"]);
				$this->object->setJavaCodebase($_POST["java_codebase"]);
				$this->object->setJavaArchive($_POST["java_archive"]);
				$this->object->setJavaWidth($_POST["java_width"]);
				$this->object->setJavaHeight($_POST["java_height"]);
				if ((!$_POST["java_width"]) or (!$_POST["java_height"])) $result = 1;
				$this->object->flushParams();
				foreach ($_POST as $key => $value)
				{
					if (preg_match("/param_name_(\d+)/", $key, $matches))
					{
						$this->object->addParameterAtIndex($matches[1], $value, $_POST["param_value_$matches[1]"]);
					}
				}
				if (preg_match("/delete_(\d+)/", $this->ctrl->getCmd(), $matches))
				{
					$this->object->removeParameter($_POST["param_name_$matches[1]"]);
				}
			}
		}
		if ($saved)
		{
			$this->object->saveToDb();
			$this->error .= $this->lng->txt("question_saved_for_upload");
		}
		return $result;
	}

	function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		$test_output = $this->getTestOutput($active_id, $pass, $is_postponed, $use_post_solutions); 
		$this->tpl->setVariable("QUESTION_OUTPUT", $test_output);
		$this->tpl->setVariable("FORMACTION", $formaction);
	}

	function getSolutionOutput($active_id, $pass = NULL, $graphicalOutput = FALSE)
	{
		// get page object output
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id);

		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_javaapplet_question_output_solution.html", TRUE, TRUE, TRUE);
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "test_type");
		$template->setVariable("PARAM_VALUE", ilObjTest::_getTestType($active_id));
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "test_id");
		$template->setVariable("PARAM_VALUE", $test_id);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "question_id");
		$template->setVariable("PARAM_VALUE", $this->object->getId());
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "user_id");
		$template->setVariable("PARAM_VALUE", $user_id);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "points_max");
		$template->setVariable("PARAM_VALUE", $this->object->getPoints());
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "session_id");
		$template->setVariable("PARAM_VALUE", $_COOKIE["PHPSESSID"]);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "client");
		$template->setVariable("PARAM_VALUE", CLIENT_ID);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "pass");
		$actualpass = ilObjTest::_getPass($active_id);
		$template->setVariable("PARAM_VALUE", $actualpass);
		$template->parseCurrentBlock();

		if ($active_id)
		{
			$solutions = NULL;
			include_once "./assessment/classes/class.ilObjTest.php";
			$info = $this->object->getReachedInformation($active_id, $pass);
			foreach ($info as $kk => $infodata)
			{
				$template->setCurrentBlock("appletparam");
				$template->setVariable("PARAM_NAME", "value_" . $infodata["order"] . "_1");
				$template->setVariable("PARAM_VALUE", $infodata["value1"]);
				$template->parseCurrentBlock();
				$template->setCurrentBlock("appletparam");
				$template->setVariable("PARAM_NAME", "value_" . $infodata["order"] . "_2");
				$template->setVariable("PARAM_VALUE", $infodata["value2"]);
				$template->parseCurrentBlock();
			}
		}
		
		$questiontext = $this->object->getQuestion();
		$questiontext = ilUtil::insertLatexImages($questiontext, "\<span class\=\"latex\">", "\<\/span>", URL_TO_LATEX);
		$template->setVariable("QUESTIONTEXT", $questiontext);
		$template->setVariable("APPLET_WIDTH", $this->object->getJavaWidth());
		$template->setVariable("APPLET_HEIGHT", $this->object->getJavaHeight());
		$template->setVariable("APPLET_CODE", $this->object->getJavaCode());
		if (strlen($this->object->getJavaArchive()) > 0)
		{
			$template->setVariable("APPLET_ARCHIVE", " archive=\"".$this->object->getJavaArchive()."\"");
		}
		else
		{
			if (strpos($this->object->getJavaAppletFilename(), ".jar") !== FALSE)
			{
				$template->setVariable("APPLET_ARCHIVE", " archive=\"".$this->object->getJavaPathWeb().$this->object->getJavaAppletFilename()."\"");
			}
		}
		if (strlen($this->object->getJavaCodebase()) > 0)
		{
			$template->setVariable("APPLET_CODEBASE", " codebase=\"".$this->object->getJavaCodebase()."\"");
		}
		else
		{
			if (strpos($this->object->getJavaAppletFilename(), ".class") !== FALSE)
			{
				$template->setVariable("APPLET_CODEBASE", " codebase=\"".$this->object->getJavaPathWeb()."\"");
			}
		}
		if ($active_id)
		{
			if ($graphicalOutput)
			{
				// output of ok/not ok icons for user entered solutions
				if ($this->object->getReachedPoints($active_id) == $this->object->getMaximumPoints())
				{
					$template->setCurrentBlock("icon_ok");
					$template->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.gif"));
					$template->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock("icon_ok");
					$template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.gif"));
					$template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
					$template->parseCurrentBlock();
				}
			}
		}
		$questionoutput = $template->get();
		$questionoutput = str_replace("<div xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" class=\"ilc_Question\"></div>", $questionoutput, $pageoutput);
		$questionoutput = preg_replace("/<div class\=\"ilc_PageTitle\"\>.*?\<\/div\>/", "", $questionoutput);
		return $questionoutput;
	}
	
	function getPreview()
	{
		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_javaapplet_question_output.html", TRUE, TRUE, TRUE);
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "test_id");
		$template->setVariable("PARAM_VALUE", $test_id);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "question_id");
		$template->setVariable("PARAM_VALUE", $this->object->getId());
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "user_id");
		$template->setVariable("PARAM_VALUE", $user_id);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "points_max");
		$template->setVariable("PARAM_VALUE", $this->object->getPoints());
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "session_id");
		$template->setVariable("PARAM_VALUE", $_COOKIE["PHPSESSID"]);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "client");
		$template->setVariable("PARAM_VALUE", CLIENT_ID);
		$template->parseCurrentBlock();

		$questiontext = $this->object->getQuestion();
		$questiontext = ilUtil::insertLatexImages($questiontext, "\<span class\=\"latex\">", "\<\/span>", URL_TO_LATEX);
		$template->setVariable("QUESTIONTEXT", $questiontext);
		$template->setVariable("APPLET_WIDTH", $this->object->getJavaWidth());
		$template->setVariable("APPLET_HEIGHT", $this->object->getJavaHeight());
		$template->setVariable("APPLET_CODE", $this->object->getJavaCode());
		if (strlen($this->object->getJavaArchive()) > 0)
		{
			$template->setVariable("APPLET_ARCHIVE", " archive=\"".$this->object->getJavaArchive()."\"");
		}
		else
		{
			if (strpos($this->object->getJavaAppletFilename(), ".jar") !== FALSE)
			{
				$template->setVariable("APPLET_ARCHIVE", " archive=\"".$this->object->getJavaPathWeb().$this->object->getJavaAppletFilename()."\"");
			}
		}
		if (strlen($this->object->getJavaCodebase()) > 0)
		{
			$template->setVariable("APPLET_CODEBASE", " codebase=\"".$this->object->getJavaCodebase()."\"");
		}
		else
		{
			if (strpos($this->object->getJavaAppletFilename(), ".class") !== FALSE)
			{
				$template->setVariable("APPLET_CODEBASE", " codebase=\"".$this->object->getJavaPathWeb()."\"");
			}
		}
		$questionoutput = $template->get();
		$questionoutput = preg_replace("/\<div[^>]*?>(.*)\<\/div>/is", "\\1", $questionoutput);
		return $questionoutput;
	}
	
	function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		// get page object output
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id);

		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_javaapplet_question_output.html", TRUE, TRUE, TRUE);
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "test_type");
		$template->setVariable("PARAM_VALUE", ilObjTest::_getTestType($active_id));
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "test_id");
		$template->setVariable("PARAM_VALUE", $test_id);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "question_id");
		$template->setVariable("PARAM_VALUE", $this->object->getId());
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "user_id");
		$template->setVariable("PARAM_VALUE", $user_id);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "points_max");
		$template->setVariable("PARAM_VALUE", $this->object->getPoints());
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "session_id");
		$template->setVariable("PARAM_VALUE", $_COOKIE["PHPSESSID"]);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "client");
		$template->setVariable("PARAM_VALUE", CLIENT_ID);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "pass");
		$actualpass = ilObjTest::_getPass($active_id);
		$template->setVariable("PARAM_VALUE", $actualpass);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("appletparam");
		$template->setVariable("PARAM_NAME", "post_url");
		$template->setVariable("PARAM_VALUE", ilUtil::removeTrailingPathSeparators(ILIAS_HTTP_PATH) . "/assessment/save_java_question_result.php");
		$template->parseCurrentBlock();

		if ($active_id)
		{
			$solutions = NULL;
			include_once "./assessment/classes/class.ilObjTest.php";
			if (ilObjTest::_getHidePreviousResults($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			$info = $this->object->getReachedInformation($active_id, $pass);
			foreach ($info as $kk => $infodata)
			{
				$template->setCurrentBlock("appletparam");
				$template->setVariable("PARAM_NAME", "value_" . $infodata["order"] . "_1");
				$template->setVariable("PARAM_VALUE", $infodata["value1"]);
				$template->parseCurrentBlock();
				$template->setCurrentBlock("appletparam");
				$template->setVariable("PARAM_NAME", "value_" . $infodata["order"] . "_2");
				$template->setVariable("PARAM_VALUE", $infodata["value2"]);
				$template->parseCurrentBlock();
			}
		}
		
		$questiontext = $this->object->getQuestion();
		$questiontext = ilUtil::insertLatexImages($questiontext, "\<span class\=\"latex\">", "\<\/span>", URL_TO_LATEX);
		$template->setVariable("QUESTIONTEXT", $questiontext);
		$template->setVariable("APPLET_WIDTH", $this->object->getJavaWidth());
		$template->setVariable("APPLET_HEIGHT", $this->object->getJavaHeight());
		$template->setVariable("APPLET_CODE", $this->object->getJavaCode());
		if (strlen($this->object->getJavaArchive()) > 0)
		{
			$template->setVariable("APPLET_ARCHIVE", " archive=\"".$this->object->getJavaArchive()."\"");
		}
		else
		{
			if (strpos($this->object->getJavaAppletFilename(), ".jar") !== FALSE)
			{
				$template->setVariable("APPLET_ARCHIVE", " archive=\"".$this->object->getJavaPathWeb().$this->object->getJavaAppletFilename()."\"");
			}
		}
		if (strlen($this->object->getJavaCodebase()) > 0)
		{
			$template->setVariable("APPLET_CODEBASE", " codebase=\"".$this->object->getJavaCodebase()."\"");
		}
		else
		{
			if (strpos($this->object->getJavaAppletFilename(), ".class") !== FALSE)
			{
				$template->setVariable("APPLET_CODEBASE", " codebase=\"".$this->object->getJavaPathWeb()."\"");
			}
		}
		$questionoutput = $template->get();
		$questionoutput = str_replace("<div xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" class=\"ilc_Question\"></div>", $questionoutput, $pageoutput);
		return $questionoutput;
	}
	
	/**
	* check input fields
	*/
	function checkInput()
	{
		if ((strlen($_POST["title"]) == 0) or (strlen($_POST["author"]) == 0) or (strlen($_POST["question"]) == 0) or (strlen($_POST["applet_points"]) == 0))
		{
			$this->error .= $this->lng->txt("fill_out_all_required_fields");
			return false;
		}
		return true;
	}


	function addSuggestedSolution()
	{
		$_SESSION["subquestion_index"] = 0;
		if ($_POST["cmd"]["addSuggestedSolution"])
		{
			if ($this->writePostData())
			{
				sendInfo($this->getErrorMessage());
				$this->editQuestion();
				return;
			}
			if ($result != 0)
			{
				$this->editQuestion();
				return;
			}
		}
		$this->object->saveToDb();
		$_GET["q_id"] = $this->object->getId();
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
		$this->getQuestionTemplate("qt_javaapplet");
		parent::addSuggestedSolution();
	}
}
?>
