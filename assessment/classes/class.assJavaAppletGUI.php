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

require_once "./assessment/classes/class.assQuestionGUI.php";
require_once "./assessment/classes/class.assJavaApplet.php";

/**
* Java applet question GUI representation
*
* The ASS_JavaAppletGUI class encapsulates the GUI representation
* for java applet questions.
*
* @author		Helmut Schottmüller <hschottm@tzi.de>
* @version	$Id$
* @module   class.assJavaAppletGUI.php
* @modulegroup   Assessment
*/
class ASS_JavaAppletGUI extends ASS_QuestionGUI
{
	/**
	* ASS_JavaAppletGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the ASS_JavaAppletGUI object.
	*
	* @param integer $id The database id of a image map question object
	* @access public
	*/
	function ASS_JavaAppletGUI(
		$id = -1
	)
	{
		$this->ASS_QuestionGUI();
		$this->object = new ASS_JavaApplet();
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
		return "qt_javaapplet";
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
		$this->tpl->addBlockFile("OTHER_QUESTION_DATA", "other_question_data", "tpl.il_as_qpl_other_question_data.html", true);

		if ($this->object->getId() > 0)
		{
			// call to other question data i.e. estimated working time block
			$this->outOtherQuestionData();
			// image block
			$this->tpl->setCurrentBlock("post_save");

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
			}
			else
			{
				$this->tpl->setVariable("VALUE_JAVAAPPLET_UPLOAD", $this->lng->txt("upload"));
			}
			$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("available_points"));
			$this->tpl->setVariable("VALUE_APPLET_POINTS", sprintf("%d", $this->object->getPoints()));
			$this->tpl->parseCurrentBlock();

			if ($javaapplet)
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
					if (!$emptyname)
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
				$this->tpl->setCurrentBlock("appletcode");
				$this->tpl->setVariable("APPLET_ATTRIBUTES", $this->lng->txt("applet_attributes"));
				$this->tpl->setVariable("TEXT_ARCHIVE", $this->lng->txt("archive"));
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
		}
		else
		{
			$this->tpl->setCurrentBlock("pre_save");
			$this->tpl->setVariable("APPLY_MESSAGE", $this->lng->txt("save_before_upload_javaapplet"));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("question_data");
		$this->tpl->setVariable("JAVAAPPLET_ID", $this->object->getId());
		$this->tpl->setVariable("VALUE_JAVAAPPLET_TITLE", htmlspecialchars($this->object->getTitle()));
		$this->tpl->setVariable("VALUE_JAVAAPPLET_COMMENT", htmlspecialchars($this->object->getComment()));
		$this->tpl->setVariable("VALUE_JAVAAPPLET_AUTHOR", htmlspecialchars($this->object->getAuthor()));
		$this->tpl->setVariable("VALUE_QUESTION", htmlspecialchars($this->object->getQuestion()));
		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
		$this->tpl->setVariable("TEXT_COMMENT", $this->lng->txt("description"));
		$this->tpl->setVariable("TEXT_QUESTION", $this->lng->txt("question"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));

		$this->tpl->setVariable("SAVE",$this->lng->txt("save"));
		$this->tpl->setVariable("CANCEL",$this->lng->txt("cancel"));
		$this->ctrl->setParameter($this, "sel_question_types", "qt_javaapplet");
		$this->tpl->setVariable("ACTION_JAVAAPPLET_QUESTION",
			$this->ctrl->getFormaction($this));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->parseCurrentBlock();

	}


	/**
	* save question to db and return to question pool
	*/
	function uploadingJavaApplet()
	{
		$this->writePostData();
		$this->object->saveToDb();
		$this->editQuestion();
	}

	/**
	* save question to db and return to question pool
	*/
	function addParameter()
	{
		$this->writePostData();
		$this->object->saveToDb();
		$this->editQuestion();
	}

	/**
	* delete a parameter
	*/
	function delete()
	{
		$this->writePostData();
		$this->object->saveToDb();
		$this->editQuestion();
	}

	/**
	* Sets the extra fields i.e. estimated working time of a question from a posted create/edit form
	*
	* Sets the extra fields i.e. estimated working time of a question from a posted create/edit form
	*
	* @access private
	*/
	function outOtherQuestionData()
	{
		$this->tpl->setCurrentBlock("other_question_data");
		$est_working_time = $this->object->getEstimatedWorkingTime();
		$this->tpl->setVariable("TEXT_WORKING_TIME", $this->lng->txt("working_time"));
		$this->tpl->setVariable("TIME_FORMAT", $this->lng->txt("time_format"));
		$this->tpl->setVariable("VALUE_WORKING_TIME", ilUtil::makeTimeSelect("Estimated", false, $est_working_time[h], $est_working_time[m], $est_working_time[s]));
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
		$saved = false;
		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]))
		{
			$result = 1;
		}

		$this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
		$this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
		$this->object->setComment(ilUtil::stripSlashes($_POST["comment"]));
		$this->object->setQuestion(ilUtil::stripSlashes($_POST["question"]));
		$this->object->setShuffle($_POST["shuffle"]);
		$this->object->setPoints($_POST["applet_points"]);

		if ($_POST["id"] > 0)
		{
			// Question is already saved, appledcode can be uploaded
			//setting java applet
			if (empty($_FILES['javaappletName']['tmp_name']))
			{
				$this->object->setJavaAppletFilename(ilUtil::stripSlashes($_POST['uploaded_javaapplet']));
			}
			else
			{
				$this->object->setJavaAppletFilename($_FILES['javaappletName']['name'], $_FILES['javaappletName']['tmp_name']);
			}
			if ($this->object->getJavaAppletFilename())
			{
				$this->object->setJavaCode($_POST["java_code"]);
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
				foreach ($_POST["cmd"] as $key => $value)
				{
					if (preg_match("/delete_(\d+)/", $key, $matches))
					{
						$this->object->removeParameter($_POST["param_name_$matches[1]"]);
					}
				}
			}
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
	function outWorkingForm($test_id = "", $is_postponed = false)
	{
		global $ilUser;

		$this->tpl->addBlockFile("JAVAAPPLET_QUESTION", "javaappletblock", "tpl.il_as_execute_javaapplet_question.html", true);
		$solutions = array();
		$postponed = "";
		if ($test_id)
		{
			$solutions =& $this->object->getSolutionValues($test_id);
		}
		if ($is_postponed)
		{
			$postponed = " (" . $this->lng->txt("postponed") . ")";
		}

		$this->tpl->setCurrentBlock("additional_params");
		if (!$test_id)
		{
			$test_id = 0;
		}
		$this->tpl->setVariable("PARAM_NAME", "test_id");
		$this->tpl->setVariable("PARAM_VALUE", $test_id);
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("additional_params");
		$this->tpl->setVariable("PARAM_NAME", "user_id");
		$this->tpl->setVariable("PARAM_VALUE", $ilUser->id);
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("additional_params");
		$this->tpl->setVariable("PARAM_NAME", "question_id");
		$this->tpl->setVariable("PARAM_VALUE", $this->object->getId());
		$this->tpl->parseCurrentBlock();
		for ($i = 0; $i < $this->object->getParameterCount(); $i++)
		{
			$this->tpl->setCurrentBlock("additional_params");
			$param = $this->object->getParameter($i);
			$this->tpl->setVariable("PARAM_NAME", $param["name"]);
			$this->tpl->setVariable("PARAM_VALUE", $param["value"]);
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("javaappletblock");
		$this->tpl->setVariable("JAVAAPPLET_QUESTION_HEADLINE", $this->object->getTitle());
		$this->tpl->setVariable("JAVAAPPLET_QUESTION", $this->object->getQuestion());
		$javaappletpath_working = $this->object->getJavaPathWeb() . $this->object->getJavaAppletFilename();
		$this->tpl->setVariable("PARAM_ARCHIVE", "archive=$javaappletpath_working ");

		if ($this->object->getJavaCode())
		{
			$this->tpl->setVariable("PARAM_CODE", "code=" . $this->object->getJavaCode() . " ");
		}

		$this->tpl->setVariable("PARAM_WIDTH", $this->object->getJavaWidth());
		$this->tpl->setVariable("PARAM_HEIGHT", $this->object->getJavaHeight());
		$this->tpl->parseCurrentBlock();
	}

	/**
	* Creates an output of the user's solution
	*
	* Creates an output of the user's solution
	*
	* @access public
	*/
	function outUserSolution($user_id, $test_id)
	{
	}
	
}
?>
