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
* Class ilObjSurveyGUI
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version  $Id$
*
* @ilCtrl_Calls ilObjSurveyGUI: ilSurveyEvaluationGUI
* @ilCtrl_Calls ilObjSurveyGUI: ilSurveyExecutionGUI
* @ilCtrl_Calls ilObjSurveyGUI: ilMDEditorGUI, ilPermissionGUI
* @ilCtrl_Calls ilObjSurveyGUI: ilInfoScreenGUI, ilObjectCopyGUI
* @ilCtrl_Calls ilObjSurveyGUI: ilRepositorySearchGUI
*
* @extends ilObjectGUI
* @ingroup ModulesSurvey
*/

include_once "./classes/class.ilObjectGUI.php";
include_once "./Modules/Survey/classes/inc.SurveyConstants.php";

class ilObjSurveyGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjSurveyGUI()
	{
    global $lng, $ilCtrl;

		$this->type = "svy";
		$lng->loadLanguageModule("survey");
		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this, "ref_id");

		$this->ilObjectGUI("",$_GET["ref_id"], true, false);
	}
	
	function backToRepositoryObject()
	{
		include_once "./Services/Utilities/classes/class.ilUtil.php";
		$path = $this->tree->getPathFull($this->object->getRefID());
		ilUtil::redirect($this->getReturnLocation("cancel","./repository.php?cmd=frameset&ref_id=" . $path[count($path) - 2]["child"]));
	}

	/**
	* execute command
	*/
	function executeCommand()
	{
		global $ilAccess, $ilNavigationHistory,$ilCtrl;

		if ((!$ilAccess->checkAccess("read", "", $_GET["ref_id"])) && (!$ilAccess->checkAccess("visible", "", $_GET["ref_id"])))
		{
			global $ilias;
			$ilias->raiseError($this->lng->txt("permission_denied"), $ilias->error_obj->MESSAGE);
		}
		// add entry to navigation history
		if (!$this->getCreationMode() &&
			$ilAccess->checkAccess("read", "", $_GET["ref_id"]))
		{
			$ilNavigationHistory->addItem($_GET["ref_id"],
				"ilias.php?baseClass=ilObjSurveyGUI&cmd=infoScreen&ref_id=".$_GET["ref_id"], "svy");
		}

		$cmd = $this->ctrl->getCmd("properties");

		// workaround for bug #6288, needs better solution
		if ($cmd == "saveTags")
		{
			$ilCtrl->setCmdClass("ilinfoscreengui");
		}

		$next_class = $this->ctrl->getNextClass($this);
		$this->ctrl->setReturn($this, "properties");
		$this->tpl->addCss(ilUtil::getStyleSheetLocation("output", "survey.css", "Modules/Survey"), "screen");
		$this->prepareOutput();
		//echo "<br>nextclass:$next_class:cmd:$cmd:qtype=$q_type";
		switch($next_class)
		{
			case "ilinfoscreengui":
				$this->infoScreen();	// forwards command
				break;
			case 'ilmdeditorgui':
				include_once 'Services/MetaData/classes/class.ilMDEditorGUI.php';
				$md_gui =& new ilMDEditorGUI($this->object->getId(), 0, $this->object->getType());
				$md_gui->addObserver($this->object,'MDUpdateListener','General');

				$this->ctrl->forwardCommand($md_gui);
				break;
			
			case "ilsurveyevaluationgui":
				include_once("./Modules/Survey/classes/class.ilSurveyEvaluationGUI.php");
				$eval_gui = new ilSurveyEvaluationGUI($this->object);
				$ret =& $this->ctrl->forwardCommand($eval_gui);
				break;

			case 'ilrepositorysearchgui':
				include_once('./Services/Search/classes/class.ilRepositorySearchGUI.php');
				$rep_search =& new ilRepositorySearchGUI();
				$rep_search->setCallback($this,
					'inviteUserGroupObject',
					array(
						)
					);

				// Set tabs
				$this->ctrl->setReturn($this, 'invite');
				$ret =& $this->ctrl->forwardCommand($rep_search);
				$this->tabs_gui->setTabActive('invitation');
				break;

			case "ilsurveyexecutiongui":
				include_once("./Modules/Survey/classes/class.ilSurveyExecutionGUI.php");
				$exec_gui = new ilSurveyExecutionGUI($this->object);
				$ret =& $this->ctrl->forwardCommand($exec_gui);
				break;
				
			case 'ilpermissiongui':
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;
				
			case 'ilobjectcopygui':
				include_once './Services/Object/classes/class.ilObjectCopyGUI.php';
				$cp = new ilObjectCopyGUI($this);
				$cp->setType('svy');
				$this->ctrl->forwardCommand($cp);
				break;
				

			default:
				$cmd.= "Object";
				$ret =& $this->$cmd();
				break;
		}

		if (strtolower($_GET["baseClass"]) != "iladministrationgui" &&
			$this->getCreationMode() != true)
		{
			$this->tpl->show();
		}
	}

	/**
	* save object
	* @access	public
	*/
	function saveObject()
	{
		global $rbacadmin;

		if (!strlen($_POST['Fobject']['title']))
		{
			ilUtil::sendFailure($this->lng->txt('title_required'), true);
			$this->ctrl->setParameter($this, 'new_type', $_GET['new_type']);
			$this->ctrl->redirect($this, 'create');
		}

		// create and insert forum in objecttree
		$newObj = parent::saveObject();
		// always send a message
		ilUtil::sendSuccess($this->lng->txt("object_added"),true);
		ilUtil::redirect("ilias.php?baseClass=ilObjSurveyGUI&ref_id=".$newObj->getRefId()."&cmd=properties");
	}
	
	/**
	* cancel action and go back to previous page
	* @access	public
	*
	*/
	function cancelObject($in_rep = false)
	{
		ilUtil::redirect("repository.php?cmd=frameset&ref_id=".$_GET["ref_id"]);
	}

	/**
	* Cancel actions in the properties form
	*
	* Cancel actions in the properties form
	*
	* @access private
	*/
	function cancelPropertiesObject()
	{
		$this->ctrl->redirect($this, "properties");
	}
	
/**
* Checks for write access and returns to the parent object
*
* Checks for write access and returns to the parent object
*
* @access public
*/
  function handleWriteAccess()
	{
		global $ilAccess;
		if (!$ilAccess->checkAccess("write", "", $this->ref_id)) 
		{
			// allow only write access
			ilUtil::sendInfo($this->lng->txt("cannot_edit_survey"), TRUE);
			$this->ctrl->redirect($this, "infoScreen");
		}
	}
	
	/**
	* Save the survey properties
	*
	* Save the survey properties
	*
	* @access private
	*/
	function savePropertiesObject()
	{
		$hasErrors = $this->propertiesObject(true);
		if (!$hasErrors)
		{
			$result = $this->object->setStatus($_POST['online']);
			$this->object->setEvaluationAccess($_POST["evaluation_access"]);
			$this->object->setStartDateEnabled($_POST["enabled_start_date"]);
			if ($this->object->getStartDateEnabled())
			{
				$this->object->setStartDate($_POST['start_date']['date']);
			}
			else
			{
				$this->object->setStartDate(null);
			}
			$this->object->setEndDateEnabled($_POST["enabled_end_date"]);
			if ($this->object->getEndDateEnabled())
			{
				$this->object->setEndDate($_POST['end_date']['date']);
			}
			else
			{
				$this->object->setEndDate(null);
			}

			include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
			$introduction = $_POST["introduction"];
			$this->object->setIntroduction($introduction);
			$outro = $_POST["outro"];
			$this->object->setOutro($outro);

			$hasDatasets = $this->object->_hasDatasets($this->object->getSurveyId());
			if (!$hasDatasets)
			{
				$anonymize = $_POST["anonymization"];
				if ($anonymize)
				{
					if (strcmp($_POST['anonymization_options'], 'anonymize_without_code') == 0) $anonymize = ANONYMIZE_FREEACCESS;
				}
				$this->object->setAnonymize($anonymize);
			}
			$this->object->setShowQuestionTitles($_POST["show_question_titles"]);
			$this->object->setMailNotification($_POST['mailnotification']);
			$this->object->setMailAddresses($_POST['mailaddresses']);
			$this->object->setMailParticipantData($_POST['mailparticipantdata']);
			$this->object->saveToDb();
			if (strcmp($_SESSION["info"], "") != 0)
			{
				ilUtil::sendSuccess($_SESSION["info"] . "<br />" . $this->lng->txt("settings_saved"), true);
			}
			else
			{
				ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
			}
			$this->ctrl->redirect($this, "properties");
		}
	}

	/**
	* Display and fill the properties form of the test
	*
	* @access	public
	*/
	function propertiesObject($checkonly = FALSE)
	{
		global $ilAccess;
		
		$save = (strcmp($this->ctrl->getCmd(), "saveProperties") == 0) ? TRUE : FALSE;

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTableWidth("100%");
		$form->setId("survey_properties");

		// general properties
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->lng->txt("properties"));
		$form->addItem($header);
		
		// online
		$online = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$online->setValue(1);
		$online->setChecked($this->object->isOnline());
		$form->addItem($online);

		// introduction
		$intro = new ilTextAreaInputGUI($this->lng->txt("introduction"), "introduction");
		$intro->setValue($this->object->prepareTextareaOutput($this->object->getIntroduction()));
		$intro->setRows(10);
		$intro->setCols(80);
		$intro->setUseRte(TRUE);
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$intro->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("survey"));
		$intro->addPlugin("latex");
		$intro->addButton("latex");
		$intro->addPlugin("pastelatex");
		$intro->setRTESupport($this->object->getId(), "svy", "survey");
		$form->addItem($intro);

		// enable start date
		$enablestartingtime = new ilCheckboxInputGUI($this->lng->txt("start_date"), "enabled_start_date");
		$enablestartingtime->setValue(1);
		$enablestartingtime->setOptionTitle($this->lng->txt("enabled"));
		$enablestartingtime->setChecked($this->object->getStartDateEnabled());
		// start date
		$startingtime = new ilDateTimeInputGUI('', 'start_date');
		$startingtime->setShowDate(true);
		$startingtime->setShowTime(false);
		if ($this->object->getStartDateEnabled())
		{
			$startingtime->setDate(new ilDate($this->object->getStartDate(), IL_CAL_DATE));
		}
		else
		{
			$startingtime->setDate(new ilDate(time(), IL_CAL_UNIX));
		}
		$enablestartingtime->addSubItem($startingtime);
		$form->addItem($enablestartingtime);

		// enable end date
		$enableendingtime = new ilCheckboxInputGUI($this->lng->txt("end_date"), "enabled_end_date");
		$enableendingtime->setValue(1);
		$enableendingtime->setOptionTitle($this->lng->txt("enabled"));
		$enableendingtime->setChecked($this->object->getEndDateEnabled());
		// end date
		$endingtime = new ilDateTimeInputGUI('', 'end_date');
		$endingtime->setShowDate(true);
		$endingtime->setShowTime(false);
		if ($this->object->getEndDateEnabled())
		{
			$endingtime->setDate(new ilDate($this->object->getEndDate(), IL_CAL_DATE));
		}
		else
		{
			$endingtime->setDate(new ilDate(time(), IL_CAL_UNIX));
		}
		$enableendingtime->addSubItem($endingtime);
		$form->addItem($enableendingtime);

		// anonymization
		$anonymization = new ilCheckboxInputGUI($this->lng->txt("anonymization"), "anonymization");
		$hasDatasets = $this->object->_hasDatasets($this->object->getSurveyId());
		if ($hasDatasets)
		{
			$anonymization->setDisabled(true);
		}
		$anonymization->setOptionTitle($this->lng->txt("on"));
		$anonymization->setValue(1);
		$anonymization->setChecked($this->object->getAnonymize());
		$anonymization->setInfo($this->lng->txt("anonymize_survey_description"));

		$anonymization_options = new ilRadioGroupInputGUI('', "anonymization_options");
		if ($hasDatasets)
		{
			$anonymization_options->setDisabled(true);
		}
		$anonymization_options->addOption(new ilCheckboxOption($this->lng->txt("anonymize_without_code"), 'anonymize_without_code', ''));
		$anonymization_options->addOption(new ilCheckboxOption($this->lng->txt("anonymize_with_code"), 'anonymize_with_code', ''));
		$anonymization_options->setValue(($this->object->isAccessibleWithoutCode()) ? 'anonymize_without_code' : 'anonymize_with_code');

		$anonymization->addSubItem($anonymization_options);
		$form->addItem($anonymization);

		// show question titles
		$show_question_titles = new ilCheckboxInputGUI('', "show_question_titles");
		$show_question_titles->setOptionTitle($this->lng->txt("svy_show_questiontitles"));
		$show_question_titles->setValue(1);
		$show_question_titles->setChecked($this->object->getShowQuestionTitles());
		$form->addItem($show_question_titles);

		// final statement
		$finalstatement = new ilTextAreaInputGUI($this->lng->txt("outro"), "outro");
		$finalstatement->setValue($this->object->prepareTextareaOutput($this->object->getOutro()));
		$finalstatement->setRows(10);
		$finalstatement->setCols(80);
		$finalstatement->setUseRte(TRUE);
		$finalstatement->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("survey"));
		$finalstatement->addPlugin("latex");
		$finalstatement->addButton("latex");
		$finalstatement->addPlugin("pastelatex");
		$finalstatement->setRTESupport($this->object->getId(), "svy", "survey");
		$form->addItem($finalstatement);

		// results properties
		$results = new ilFormSectionHeaderGUI();
		$results->setTitle($this->lng->txt("results"));
		$form->addItem($results);

		// evaluation access
		$evaluation_access = new ilRadioGroupInputGUI($this->lng->txt('evaluation_access'), "evaluation_access");
		$evaluation_access->setInfo($this->lng->txt('evaluation_access_description'));
		$evaluation_access->addOption(new ilCheckboxOption($this->lng->txt("evaluation_access_off"), EVALUATION_ACCESS_OFF, ''));
		$evaluation_access->addOption(new ilCheckboxOption($this->lng->txt("evaluation_access_all"), EVALUATION_ACCESS_ALL, ''));
		$evaluation_access->addOption(new ilCheckboxOption($this->lng->txt("evaluation_access_participants"), EVALUATION_ACCESS_PARTICIPANTS, ''));
		$evaluation_access->setValue($this->object->getEvaluationAccess());
		$form->addItem($evaluation_access);

		// mail notification
		$mailnotification = new ilCheckboxInputGUI($this->lng->txt("mailnotification"), "mailnotification");
		$mailnotification->setOptionTitle($this->lng->txt("activate"));
		$mailnotification->setValue(1);
		$mailnotification->setChecked($this->object->getMailNotification());

		// addresses
		$mailaddresses = new ilTextInputGUI($this->lng->txt("mailaddresses"), "mailaddresses");
		$mailaddresses->setValue($this->object->getMailAddresses());
		$mailaddresses->setSize(80);
		$mailaddresses->setInfo($this->lng->txt('mailaddresses_info'));
		$mailaddresses->setRequired(true);
		if (($save) && !$_POST['mailnotification'])
		{
			$mailaddresses->setRequired(false);
		}

		// participant data
		$participantdata = new ilTextAreaInputGUI($this->lng->txt("mailparticipantdata"), "mailparticipantdata");
		$participantdata->setValue($this->object->getMailParticipantData());
		$participantdata->setRows(6);
		$participantdata->setCols(80);
		$participantdata->setUseRte(false);
		$participantdata->setInfo($this->lng->txt('mailparticipantdata_info'));

		$mailnotification->addSubItem($mailaddresses);
		$mailnotification->addSubItem($participantdata);
		$form->addItem($mailnotification);

		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"])) $form->addCommandButton("saveProperties", $this->lng->txt("save"));
		$errors = false;
		
		if ($save)
		{
			$errors = !$form->checkInput();
			$form->setValuesByPost();
			if (!$errors)
			{
				if (($online->getChecked()) && (count($this->object->questions) == 0))
				{
					$online->setAlert($this->lng->txt("cannot_switch_to_online_no_questions"));
					ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
					$errors = true;
				}
			}
			if ($errors) $checkonly = false;
		}
		$mailaddresses->setRequired(true);
		if (!$checkonly) $this->tpl->setVariable("ADM_CONTENT", $form->getHTML());
		return $errors;
	}
	
	/**
	* Remove questions from the survey
	*
	* Remove questions from the survey
	*
	* @access private
	*/
	function removeQuestionsObject()
	{
		$checked_questions = array();
		$checked_questionblocks = array();
		foreach ($_POST as $key => $value) 
		{
			if (preg_match("/cb_(\d+)/", $key, $matches)) 
			{
				array_push($checked_questions, $matches[1]);
			}
			if (preg_match("/cb_qb_(\d+)/", $key, $matches))
			{
				array_push($checked_questionblocks, $matches[1]);
			}
		}
		if (count($checked_questions) + count($checked_questionblocks) > 0) 
		{
			ilUtil::sendQuestion($this->lng->txt("remove_questions"));
			$this->removeQuestionsForm($checked_questions, $checked_questionblocks);
			return;
		} 
		else 
		{
			ilUtil::sendInfo($this->lng->txt("no_question_selected_for_removal"), true);
			$this->ctrl->redirect($this, "questions");
		}
	}

	/**
	* Insert questions into the survey
	*/
	public function insertQuestionsObject()
	{
		$inserted_objects = 0;
		if (is_array($_POST['q_id']))
		{
			foreach ($_POST['q_id'] as $question_id)
			{
				$this->object->insertQuestion($question_id);
				$inserted_objects++;
			}
		}
		if ($inserted_objects)
		{
			$this->object->saveCompletionStatus();
			ilUtil::sendSuccess($this->lng->txt("questions_inserted"), true);
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt("insert_missing_question"), true);
			$this->ctrl->redirect($this, 'browseForQuestions');
		}
	}

	/**
	* Insert question blocks into the survey
	*/
	public function insertQuestionblocksObject()
	{
		$inserted_objects = 0;
		if (is_array($_POST['cb']))
		{
			foreach ($_POST['cb'] as $questionblock_id)
			{
				$this->object->insertQuestionblock($questionblock_id);
				$inserted_objects++;
			}
		}
		if ($inserted_objects)
		{
			$this->object->saveCompletionStatus();
			ilUtil::sendSuccess(($inserted_objects == 1) ? $this->lng->txt("questionblock_inserted") : $this->lng->txt("questionblocks_inserted"), true);
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt("insert_missing_questionblock"), true);
			$this->ctrl->redirect($this, 'browseForQuestionblocks');
		}
	}
	
	/**
	* Change the object type in the question browser
	*/
	public function changeDatatypeObject()
	{
		global $ilUser;
		$ilUser->writePref('svy_insert_type', $_POST['datatype']);
		switch ($_POST["datatype"])
		{
			case 0:
				$this->ctrl->redirect($this, 'browseForQuestionblocks');
				break;
			case 1:
			default:
				$this->ctrl->redirect($this, 'browseForQuestions');
				break;
		}
	}
	
	/**
	* Filter the questionblock browser
	*/
	public function filterQuestionblockBrowserObject()
	{
		include_once "./Modules/Survey/classes/tables/class.ilSurveyQuestionblockbrowserTableGUI.php";
		$table_gui = new ilSurveyQuestionblockbrowserTableGUI($this, 'browseForQuestionblocks');
		$table_gui->writeFilterToSession();
		$this->ctrl->redirect($this, 'browseForQuestionblocks');
	}
	
	/**
	* Reset the questionblock browser filter
	*/
	public function resetfilterQuestionblockBrowserObject()
	{
		include_once "./Modules/Survey/classes/tables/class.ilSurveyQuestionblockbrowserTableGUI.php";
		$table_gui = new ilSurveyQuestionblockbrowserTableGUI($this, 'browseForQuestionblocks');
		$table_gui->resetFilter();
		$this->ctrl->redirect($this, 'browseForQuestionblocks');
	}
	
	/**
	* list questions of question pool
	*/
	public function browseForQuestionblocksObject($arrFilter = null)
	{
		global $rbacsystem;
		global $ilUser;

		$this->setBrowseForQuestionsSubtabs();
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_questionbrowser.html", "Modules/Survey");
		include_once "./Modules/Survey/classes/tables/class.ilSurveyQuestionblockbrowserTableGUI.php";
		$table_gui = new ilSurveyQuestionblockbrowserTableGUI($this, 'browseForQuestionblocks', (($rbacsystem->checkAccess('write', $_GET['ref_id']) ? true : false)));
		$table_gui->setEditable($rbacsystem->checkAccess('write', $_GET['ref_id']));
		$arrFilter = array();
		foreach ($table_gui->getFilterItems() as $item)
		{
			if ($item->getValue() !== false)
			{
				$arrFilter[$item->getPostVar()] = $item->getValue();
			}
		}
		$data = $this->object->getQuestionblocksTable($arrFilter);
		$table_gui->setData($data);
		$this->tpl->setVariable('TABLE', $table_gui->getHTML());	

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this, 'changeDatatype'));
		$this->tpl->setVariable("OPTION_QUESTIONS", $this->lng->txt("questions"));
		$this->tpl->setVariable("OPTION_QUESTIONBLOCKS", $this->lng->txt("questionblocks"));
		$this->tpl->setVariable("SELECTED_QUESTIONBLOCKS", " selected=\"selected\"");
		$this->tpl->setVariable("TEXT_DATATYPE", $this->lng->txt("display_all_available"));
		$this->tpl->setVariable("BTN_CHANGE", $this->lng->txt("change"));
	}

	/**
	* Filter the question browser
	*/
	public function filterQuestionBrowserObject()
	{
		include_once "./Modules/Survey/classes/tables/class.ilSurveyQuestionbrowserTableGUI.php";
		$table_gui = new ilSurveyQuestionbrowserTableGUI($this, 'browseForQuestions');
		$table_gui->writeFilterToSession();
		$this->ctrl->redirect($this, 'browseForQuestions');
	}
	
	/**
	* Reset the question browser filter
	*/
	public function resetfilterQuestionBrowserObject()
	{
		include_once "./Modules/Survey/classes/tables/class.ilSurveyQuestionbrowserTableGUI.php";
		$table_gui = new ilSurveyQuestionbrowserTableGUI($this, 'browseForQuestions');
		$table_gui->resetFilter();
		$this->ctrl->redirect($this, 'browseForQuestions');
	}
	
	/**
	* list questions of question pool
	*/
	public function browseForQuestionsObject($arrFilter = null)
	{
		global $rbacsystem;
		global $ilUser;

		$this->setBrowseForQuestionsSubtabs();
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_questionbrowser.html", "Modules/Survey");
		include_once "./Modules/Survey/classes/tables/class.ilSurveyQuestionbrowserTableGUI.php";
		$table_gui = new ilSurveyQuestionbrowserTableGUI($this, 'browseForQuestions', (($rbacsystem->checkAccess('write', $_GET['ref_id']) ? true : false)));
		$table_gui->setEditable($rbacsystem->checkAccess('write', $_GET['ref_id']));
		$arrFilter = array();
		foreach ($table_gui->getFilterItems() as $item)
		{
			if ($item->getValue() !== false)
			{
				$arrFilter[$item->getPostVar()] = $item->getValue();
			}
		}
		$data = $this->object->getQuestionsTable($arrFilter);
		$table_gui->setData($data);
		$this->tpl->setVariable('TABLE', $table_gui->getHTML());	

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this, 'changeDatatype'));
		$this->tpl->setVariable("OPTION_QUESTIONS", $this->lng->txt("questions"));
		$this->tpl->setVariable("OPTION_QUESTIONBLOCKS", $this->lng->txt("questionblocks"));
		$this->tpl->setVariable("SELECTED_QUESTIONS", " selected=\"selected\"");
		$this->tpl->setVariable("TEXT_DATATYPE", $this->lng->txt("display_all_available"));
		$this->tpl->setVariable("BTN_CHANGE", $this->lng->txt("change"));
	}

/**
* Creates a confirmation form to remove questions from the survey
*
* Creates a confirmation form to remove questions from the survey
*
* @param array $checked_questions An array containing the id's of the questions to be removed
* @param array $checked_questionblocks An array containing the id's of the question blocks to be removed
* @access public
*/
	function removeQuestionsForm($checked_questions, $checked_questionblocks)
	{
		ilUtil::sendInfo();
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_remove_questions.html", "Modules/Survey");
		$colors = array("tblrow1", "tblrow2");
		$counter = 0;
		$surveyquestions =& $this->object->getSurveyQuestions();
		include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
		foreach ($surveyquestions as $question_id => $data)
		{
			if (in_array($data["question_id"], $checked_questions) or (in_array($data["questionblock_id"], $checked_questionblocks)))
			{
				$this->tpl->setCurrentBlock("row");
				$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
				$this->tpl->setVariable("TEXT_TITLE", $data["title"]);
				$this->tpl->setVariable("TEXT_DESCRIPTION", $data["description"]);
				$this->tpl->setVariable("TEXT_TYPE", SurveyQuestion::_getQuestionTypeName($data["type_tag"]));
				$this->tpl->setVariable("TEXT_QUESTIONBLOCK", $data["questionblock_title"]);
				$this->tpl->parseCurrentBlock();
				$counter++;
			}
		}
		foreach ($checked_questions as $id)
		{
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("HIDDEN_NAME", "id_$id");
			$this->tpl->setVariable("HIDDEN_VALUE", "$id");
			$this->tpl->parseCurrentBlock();
		}
		foreach ($checked_questionblocks as $id)
		{
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("HIDDEN_NAME", "id_qb_$id");
			$this->tpl->setVariable("HIDDEN_VALUE", "$id");
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TEXT_DESCRIPTION", $this->lng->txt("description"));
		$this->tpl->setVariable("TEXT_TYPE", $this->lng->txt("question_type"));
		$this->tpl->setVariable("TEXT_QUESTIONBLOCK", $this->lng->txt("questionblock"));
		$this->tpl->setVariable("BTN_CONFIRM", $this->lng->txt("confirm"));
		$this->tpl->setVariable("BTN_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "confirmRemoveQuestions"));
		$this->tpl->parseCurrentBlock();
	}


/**
* Displays the definition form for a question block
*
* Displays the definition form for a question block
*
* @param integer $questionblock_id The database id of the questionblock to edit an existing questionblock
* @access public
*/
	function defineQuestionblock($questionblock_id = "")
	{
		$this->questionsSubtabs("questions");
		ilUtil::sendInfo();
		if ($questionblock_id)
		{
			$questionblock = $this->object->getQuestionblock($questionblock_id);
		}
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_define_questionblock.html", "Modules/Survey");
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/cb_(\d+)/", $key, $matches))
			{
				$this->tpl->setCurrentBlock("hidden");
				$this->tpl->setVariable("HIDDEN_NAME", "cb_$matches[1]");
				$this->tpl->setVariable("HIDDEN_VALUE", $matches[1]);
				$this->tpl->parseCurrentBlock();
			}
		}
		if ($questionblock_id)
		{
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("HIDDEN_NAME", "questionblock_id");
			$this->tpl->setVariable("HIDDEN_VALUE", $questionblock_id);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		if ($questionblock_id)
		{
			$this->tpl->setVariable("VALUE_TITLE", $questionblock["title"]);
		}
		$this->tpl->setVariable("TXT_QUESTIONTEXT_DESCRIPTION", $this->lng->txt("show_questiontext_description"));
		$this->tpl->setVariable("TXT_QUESTIONTEXT", $this->lng->txt("show_questiontext"));
		if (($questionblock["show_questiontext"]) || (strlen($questionblock_id) == 0))
		{
			$this->tpl->setVariable("CHECKED_QUESTIONTEXT", " checked=\"checked\"");
		}
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->setVariable("HEADING_QUESTIONBLOCK", $this->lng->txt("define_questionblock"));
		$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "saveDefineQuestionblock"));
		$this->tpl->parseCurrentBlock();
	}

/**
* Creates a form to select a survey question pool for storage
*
* Creates a form to select a survey question pool for storage
*
* @access public
*/
	function createQuestionObject()
	{
		global $ilUser;
		$this->questionsSubtabs("questions");
		$tpl = new ilTemplate("tpl.il_svy_svy_qpl_select.html", TRUE, TRUE, "Modules/Survey");
		$questionpools =& $this->object->getAvailableQuestionpools(FALSE, TRUE, TRUE, "write");
		if (count($questionpools))
		{
			foreach ($questionpools as $key => $value)
			{
				$tpl->setCurrentBlock("option");
				$tpl->setVariable("VALUE_OPTION", $key);
				$tpl->setVariable("TEXT_OPTION", $value);
				$tpl->parseCurrentBlock();
			}
			$tpl->setCurrentBlock("selection");
			$tpl->setVariable("TXT_QPL_SELECT", $this->lng->txt("select_questionpool"));
			$tpl->parseCurrentBlock();
		}
		else
		{
			$tpl->setCurrentBlock("selection");
			$tpl->setVariable("TXT_QPL_ENTER", $this->lng->txt("cat_create_spl"));
			$tpl->parseCurrentBlock();
		}
		$tpl->setVariable("BTN_SUBMIT", $this->lng->txt("submit"));
		$sel_question_types = (strlen($_POST["sel_question_types"])) ? $_POST["sel_question_types"] : $_GET["sel_question_types"];
		$this->ctrl->setParameter($this, "sel_question_types", $sel_question_types);
		$tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "executeCreateQuestion"));
		$tpl->setVariable("BTN_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("ADM_CONTENT", $tpl->get());
	}

/**
* Cancel the creation of a new questions in a survey
*
* Cancel the creation of a new questions in a survey
*
* @access private
*/
	function cancelCreateQuestionObject()
	{
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Execute the creation of a new questions in a survey
*
* Execute the creation of a new questions in a survey
*
* @access private
*/
	function executeCreateQuestionObject()
	{
		if (strlen($_POST["sel_spl"]))
		{
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			ilUtil::redirect("ilias.php?baseClass=ilObjSurveyQuestionPoolGUI&ref_id=" . $_POST["sel_spl"] . "&cmd=createQuestionForSurvey&new_for_survey=".$_GET["ref_id"]."&sel_question_types=".$_GET["sel_question_types"]);
		}
		elseif (strlen($_POST["name_spl"]))
		{
			$ref_id = $this->createQuestionPool($_POST["name_spl"]);
			ilUtil::redirect("ilias.php?baseClass=ilObjSurveyQuestionPoolGUI&ref_id=" . $ref_id . "&cmd=createQuestionForSurvey&new_for_survey=".$_GET["ref_id"]."&sel_question_types=".$_GET["sel_question_types"]);
		}
		else
		{
			ilUtil::sendFailure($this->lng->txt("err_no_pool_name"), true);
			$this->ctrl->setParameter($this, "sel_question_types", $_GET["sel_question_types"]);
			$this->ctrl->redirect($this, "createQuestion");
		}
	}
	
	/**
	* Creates a new questionpool and returns the reference id
	*
	* @return integer Reference id of the newly created questionpool
	* @access	public
	*/
	private function createQuestionPool($name = "dummy")
	{
		global $tree;
		$parent_ref = $tree->getParentId($this->object->getRefId());
		include_once "./Modules/SurveyQuestionPool/classes/class.ilObjSurveyQuestionPool.php";
		$qpl = new ilObjSurveyQuestionPool();
		$qpl->setType("spl");
		$qpl->setTitle($name);
		$qpl->setDescription("");
		$qpl->create();
		$qpl->createReference();
		$qpl->putInTree($parent_ref);
		$qpl->setPermissions($parent_ref);
		$qpl->setOnline(1); // must be online to be available
		$qpl->saveToDb();
		return $qpl->getRefId();
	}

/**
* Creates a form to add a heading to a survey
*
* Creates a form to add a heading to a survey
*
* @param integer $question_id The id of the question directly after the heading. If the id is given, an existing heading will be edited
* @access public
*/
	function addHeadingObject($question_id = "")
	{
		$this->questionsSubtabs("questions");
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_heading.html", "Modules/Survey");
		$survey_questions =& $this->object->getSurveyQuestions();
		if ($question_id)
		{
			$_POST["insertbefore"] = $question_id;
			$_POST["heading"] = $survey_questions[$question_id]["heading"];
		}
		foreach ($survey_questions as $key => $value)
		{
			$this->tpl->setCurrentBlock("insertbefore_row");
			$this->tpl->setVariable("VALUE_OPTION", $key);
			$option = $this->lng->txt("before") . ": \"" . $value["title"] . "\"";
			if (strlen($option) > 80)
			{
				$option = preg_replace("/^(.{40}).*(.{40})$/", "\\1 [...] \\2", $option);
			}
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			$this->tpl->setVariable("TEXT_OPTION", ilUtil::prepareFormOutput($option));
			if ($key == $_POST["insertbefore"])
			{
				$this->tpl->setVariable("SELECTED_OPTION", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock();
		}
		if ($question_id)
		{
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("INSERTBEFORE_ORIGINAL", $question_id);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "saveHeading"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		if ($question_id)
		{
			$this->tpl->setVariable("TEXT_ADD_HEADING", $this->lng->txt("edit_heading"));
			$this->tpl->setVariable("SELECT_DISABLED", " disabled=\"disabled\"");
		}
		else
		{
			$this->tpl->setVariable("TEXT_ADD_HEADING", $this->lng->txt("add_heading"));
		}
		$this->tpl->setVariable("TEXT_HEADING", $this->lng->txt("heading"));
		$this->tpl->setVariable("VALUE_HEADING", $_POST["heading"]);
		$this->tpl->setVariable("TEXT_INSERT", $this->lng->txt("insert"));
		$this->tpl->setVariable("CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();
		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->removePlugin("ibrowser");
		include_once "./classes/class.ilObject.php";
		$obj_id = ilObject::_lookupObjectId($_GET["ref_id"]);
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, $obj_type, "survey");
	}

/**
* Insert questions or question blocks into the survey after confirmation
*
* Insert questions or question blocks into the survey after confirmation
*
* @access public
*/
	function confirmInsertQuestionObject()
	{
		// insert questions from test after confirmation
		foreach ($_POST as $key => $value) {
			if (preg_match("/id_(\d+)/", $key, $matches)) {
				if ($_GET["browsetype"] == 1)
				{
					$this->object->insertQuestion($matches[1]);
				}
				else
				{
					$this->object->insertQuestionBlock($matches[1]);
				}
			}
		}
		$this->object->saveCompletionStatus();
		ilUtil::sendSuccess($this->lng->txt("questions_inserted"), true);
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Cancels insert questions or question blocks into the survey
*
* Cancels insert questions or question blocks into the survey
*
* @access public
*/
	function cancelInsertQuestionObject()
	{
		$this->ctrl->redirect($this, "questions");
	}

/**
* Saves an edited heading in the survey questions list
*
* Saves an edited heading in the survey questions list
*
* @access public
*/
	function saveHeadingObject()
	{
		if ($_POST["heading"])
		{
			$insertbefore = $_POST["insertbefore"];
			if (!$insertbefore)
			{
				$insertbefore = $_POST["insertbefore_original"];
			}
			include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
			$this->object->saveHeading(ilUtil::stripSlashes($_POST["heading"], TRUE, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("survey")), $insertbefore);
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			ilUtil::sendFailure($this->lng->txt("error_add_heading"));
			$this->addHeadingObject();
			return;
		}
	}
	
/**
* Cancels saving a heading in the survey questions list
*
* Cancels saving a heading in the survey questions list
*
* @access public
*/
	function cancelHeadingObject()
	{
		$this->ctrl->redirect($this, "questions");
	}

/**
* Remove a survey heading after confirmation
*
* Remove a survey heading after confirmation
*
* @access public
*/
	function confirmRemoveHeadingObject()
	{
		$this->object->saveHeading("", $_POST["removeheading"]);
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Cancels the removal of survey headings
*
* Cancels the removal of survey headings
*
* @access public
*/
	function cancelRemoveHeadingObject()
	{
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Displays a confirmation form to delete a survey heading
*
* Displays a confirmation form to delete a survey heading
*
* @access public
*/
	function confirmRemoveHeadingForm()
	{
		ilUtil::sendQuestion($this->lng->txt("confirm_remove_heading"));
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_confirm_removeheading.html", "Modules/Survey");
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("BTN_CONFIRM_REMOVE", $this->lng->txt("confirm"));
		$this->tpl->setVariable("BTN_CANCEL_REMOVE", $this->lng->txt("cancel"));
		$this->tpl->setVariable("REMOVE_HEADING", $_GET["removeheading"]);
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "confirmRemoveHeading"));
		$this->tpl->parseCurrentBlock();
	}
	
/**
* Remove questions from survey after confirmation
*
* Remove questions from survey after confirmation
*
* @access private
*/
	function confirmRemoveQuestionsObject()
	{
		$checked_questions = array();
		$checked_questionblocks = array();
		foreach ($_POST as $key => $value) 
		{
			if (preg_match("/id_(\d+)/", $key, $matches)) 
			{
				array_push($checked_questions, $matches[1]);
			}
			if (preg_match("/id_qb_(\d+)/", $key, $matches)) 
			{
				array_push($checked_questionblocks, $matches[1]);
			}
		}
		$this->object->removeQuestions($checked_questions, $checked_questionblocks);
		$this->object->saveCompletionStatus();
		ilUtil::sendSuccess($this->lng->txt("questions_removed"), true);
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Cancel remove questions from survey after confirmation
*
* Cancel remove questions from survey after confirmation
*
* @access private
*/
	function cancelRemoveQuestionsObject()
	{
		$this->ctrl->redirect($this, "questions");
	}

/**
* Cancel remove questions from survey after confirmation
*
* Cancel remove questions from survey after confirmation
*
* @access private
*/
	function defineQuestionblockObject()
	{
		$questionblock = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/cb_(\d+)/", $key, $matches))
			{
				array_push($questionblock, $value);
			}
		}
		if (count($questionblock) < 2)
		{
			ilUtil::sendInfo($this->lng->txt("qpl_define_questionblock_select_missing"), true);
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			$this->defineQuestionblock();
			return;
		}
	}
	
/**
* Confirm define a question block
*/
	public function saveDefineQuestionblockObject()
	{
		if ($_POST["title"])
		{
			$show_questiontext = ($_POST["show_questiontext"]) ? 1 : 0;
			if ($_POST["questionblock_id"])
			{
				include_once "./Services/Utilities/classes/class.ilUtil.php";
				$this->object->modifyQuestionblock($_POST["questionblock_id"], ilUtil::stripSlashes($_POST["title"]), $show_questiontext);
			}
			else
			{
				$questionblock = array();
				foreach ($_POST as $key => $value)
				{
					if (preg_match("/cb_(\d+)/", $key, $matches))
					{
						array_push($questionblock, $value);
					}
				}
				include_once "./Services/Utilities/classes/class.ilUtil.php";
				$this->object->createQuestionblock(ilUtil::stripSlashes($_POST["title"]), $show_questiontext, $questionblock);
			}
			ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt("enter_questionblock_title"));
			$this->defineQuestionblockObject();
			return;
		}
	}

/**
* Unfold a question block
*/
	public function unfoldQuestionblockObject()
	{
		$unfoldblocks = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/cb_qb_(\d+)/", $key, $matches))
			{
				array_push($unfoldblocks, $matches[1]);
			}
		}
		if (count($unfoldblocks))
		{
			ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
			$this->object->unfoldQuestionblocks($unfoldblocks);
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt("qpl_unfold_select_none"), true);
		}
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Cancel define a question block
*/
	public function cancelDefineQuestionblockObject()
	{
		ilUtil::sendInfo($this->lng->txt('msg_cancel'), true);
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Move questions
*/
	public function moveQuestionsObject()
	{
		$move_questions = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/cb_(\d+)/", $key, $matches))
			{
				array_push($move_questions, $matches[1]);
			}
			if (preg_match("/cb_qb_(\d+)/", $key, $matches))
			{
				$ids = $this->object->getQuestionblockQuestionIds($matches[1]);
				foreach ($ids as $qkey => $qid)
				{
					array_push($move_questions, $qid);
				}
			}
		}
		if (count($move_questions) == 0)
		{
			ilUtil::sendInfo($this->lng->txt("no_question_selected_for_move"), true);
			$this->ctrl->redirect($this, "questions");
		}
		else
		{
			$_SESSION["move_questions"] = $move_questions;
			ilUtil::sendInfo($this->lng->txt("select_target_position_for_move_question"));
			$this->questionsObject();
		}
	}

/**
* Insert questions from move clipboard
*/
	public function insertQuestions($insert_mode)
	{
		// get all questions to move
		$move_questions = $_SESSION["move_questions"];
		// get insert point
		$insert_id = -1;
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/^cb_(\d+)$/", $key, $matches))
			{
				if ($insert_id < 0)
				{
					$insert_id = $matches[1];
				}
			}
			if (preg_match("/^cb_qb_(\d+)$/", $key, $matches))
			{
				if ($insert_id < 0)
				{
					$ids =& $this->object->getQuestionblockQuestionIds($matches[1]);
					if (count($ids))
					{
						if ($insert_mode == 0)
						{
							$insert_id = $ids[0];
						}
						else if ($insert_mode == 1)
						{
							$insert_id = $ids[count($ids)-1];
						}
					}
				}
			}
		}
		if ($insert_id <= 0)
		{
			ilUtil::sendInfo($this->lng->txt("no_target_selected_for_move"), true);
		}
		else
		{
			ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
			$this->object->moveQuestions($move_questions, $insert_id, $insert_mode);
		}
		unset($_SESSION["move_questions"]);
		$this->ctrl->redirect($this, "questions");
	}

/**
* Insert questions before selection
*/
	public function insertQuestionsBeforeObject()
	{
		$this->insertQuestions(0);
	}
	
/**
* Insert questions after selection
*/
	public function insertQuestionsAfterObject()
	{
		$this->insertQuestions(1);
	}

/**
* Save obligatory states
*/
	public function saveObligatoryObject()
	{
		$obligatory = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/obligatory_(\d+)/", $key, $matches))
			{
				$obligatory[$matches[1]] = 1;
			}
		}
		$this->object->setObligatoryStates($obligatory);
		ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
		$this->ctrl->redirect($this, "questions");
	}
	
/**
* Creates the questions form for the survey object
*/
	public function questionsObject() 
	{
		$this->handleWriteAccess();
		global $rbacsystem;
		
		$hasDatasets = $this->object->_hasDatasets($this->object->getSurveyId());
		include_once "./Services/Utilities/classes/class.ilUtil.php";
		if ((!$rbacsystem->checkAccess("read", $this->ref_id)) && (!$rbacsystem->checkAccess("write", $this->ref_id))) 
		{
			// allow only read and write access
			ilUtil::sendInfo($this->lng->txt("cannot_edit_survey"), true);
			$path = $this->tree->getPathFull($this->object->getRefID());
			ilUtil::redirect($this->getReturnLocation("cancel","./repository.php?cmd=frameset&ref_id=" . $path[count($path) - 2]["child"]));
			return;
		}
		
		if ($_GET["new_id"] > 0)
		{
			// add a question to the survey previous created in a questionpool
			$existing = $this->object->getExistingQuestions();
			if (!in_array($_GET["new_id"], $existing))
			{
				$inserted = $this->object->insertQuestion($_GET["new_id"]);
				if (!$inserted)
				{
					ilUtil::sendFailure($this->lng->txt("survey_error_insert_incomplete_question"));
				}
			}
		}
		
		if ($_GET["eqid"] and $_GET["eqpl"])
		{
			ilUtil::redirect("ilias.php?baseClass=ilObjSurveyQuestionPoolGUI&ref_id=" . $_GET["eqpl"] . "&cmd=editQuestionForSurvey&calling_survey=".$_GET["ref_id"]."&q_id=" . $_GET["eqid"]);
		}


		$_SESSION["calling_survey"] = $this->object->getRefId();
		unset($_SESSION["survey_id"]);

		if ($_GET["editheading"])
		{
			$this->addHeadingObject($_GET["editheading"]);
			return;
		}
		
		if ($_GET["up"] > 0)
		{
			$this->object->moveUpQuestion($_GET["up"]);
			ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'));
		}
		if ($_GET["down"] > 0)
		{
			$this->object->moveDownQuestion($_GET["down"]);
			ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'));
		}
		if ($_GET["qbup"] > 0)
		{
			$this->object->moveUpQuestionblock($_GET["qbup"]);
			ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'));
		}
		if ($_GET["qbdown"] > 0)
		{
			$this->object->moveDownQuestionblock($_GET["qbdown"]);
			ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'));
		}
		
		if ($_GET["removeheading"])
		{
			$this->confirmRemoveHeadingForm();
			return;
		}
		
		if ($_GET["editblock"])
		{
			$this->defineQuestionblock($_GET["editblock"]);
			return;
		}

		if ($_GET["add"])
		{
			// called after a new question was created from a questionpool
			$selected_array = array();
			array_push($selected_array, $_GET["add"]);
			ilUtil::sendQuestion($this->lng->txt("ask_insert_questions"));
			$this->insertQuestionsForm($selected_array);
			return;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_questions.html", "Modules/Survey");
		$survey_questions =& $this->object->getSurveyQuestions();
		$questionpools =& $this->object->getQuestionpoolTitles();
		$colors = array("tblrow1", "tblrow2");
		$counter = 0;
		$title_counter = 0;
		$last_color_class = "";
		$obligatory = "<img src=\"" . ilUtil::getImagePath("obligatory.gif", "Modules/Survey") . "\" alt=\"" . $this->lng->txt("question_obligatory") . "\" title=\"" . $this->lng->txt("question_obligatory") . "\" />";
		include_once "./Modules/SurveyQuestionPool/classes/class.ilObjSurveyQuestionPool.php";
		$questiontypes =& ilObjSurveyQuestionPool::_getQuestiontypes();
		if (count($survey_questions) > 0)
		{
			foreach ($survey_questions as $question_id => $data)
			{
				$title_counter++;
				if (($last_questionblock_id > 0) && ($data["questionblock_id"] == 0))
				{
					$counter++;
				}
				if (($last_questionblock_id > 0) && ($data["questionblock_id"] > 0) && ($data["questionblock_id"] != $last_questionblock_id))
				{
					$counter++;
				}
				if (($data["questionblock_id"] > 0) and ($data["questionblock_id"] != $last_questionblock_id))
				{
					// add a separator line for the beginning of a question block
					$this->tpl->setCurrentBlock("separator");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("QTab");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();

					$this->tpl->setCurrentBlock("block");
					$this->tpl->setVariable("TYPE_ICON", "<img src=\"" . ilUtil::getImagePath("questionblock.gif", "Modules/Survey") . "\" alt=\"".$this->lng->txt("questionblock_icon")."\" />");
					$this->tpl->setVariable("TEXT_QUESTIONBLOCK", $this->lng->txt("questionblock") . ": " . $data["questionblock_title"]);
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					if ($rbacsystem->checkAccess("write", $this->ref_id) and !$hasDatasets) 
					{
						if ($data["question_id"] != $this->object->questions[0])
						{
							$this->tpl->setVariable("BUTTON_UP", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&qbup=" . $data["questionblock_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_up.gif") . "\" alt=\"" . $this->lng->txt("up") . "\" title=\"" . $this->lng->txt("up") . "\" border=\"0\" /></a>");
						}
						$akeys = array_keys($survey_questions);
						if ($data["questionblock_id"] != $survey_questions[$akeys[count($akeys)-1]]["questionblock_id"])
						{
							$this->tpl->setVariable("BUTTON_DOWN", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&qbdown=" . $data["questionblock_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_down.gif") . "\" alt=\"" . $this->lng->txt("down") . "\" title=\"" . $this->lng->txt("down") . "\" border=\"0\" /></a>");
						}
						$this->tpl->setVariable("TEXT_EDIT", $this->lng->txt("edit"));
						$this->tpl->setVariable("HREF_EDIT", $this->ctrl->getLinkTarget($this, "questions") . "&editblock=" . $data["questionblock_id"]);
					}
					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("QTab");
					$this->tpl->setVariable("QUESTION_ID", "qb_" . $data["questionblock_id"]);
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();
				}
				if (($last_questionblock_id > 0) && ($data["questionblock_id"] == 0))
				{
					// add a separator line for the end of a question block
					$this->tpl->setCurrentBlock("separator");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("QTab");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();
				}
				if ($data["heading"])
				{
					$this->tpl->setCurrentBlock("heading");
					$this->tpl->setVariable("TEXT_HEADING", $data["heading"]);
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					if ($rbacsystem->checkAccess("write", $this->ref_id) and !$hasDatasets) 
					{
						$this->tpl->setVariable("TEXT_EDIT", $this->lng->txt("edit"));
						$this->tpl->setVariable("HREF_EDIT", $this->ctrl->getLinkTarget($this, "questions") . "&editheading=" . $data["question_id"]);
						$this->tpl->setVariable("TEXT_DELETE", $this->lng->txt("remove"));
						$this->tpl->setVariable("HREF_DELETE", $this->ctrl->getLinkTarget($this, "questions") . "&removeheading=" . $data["question_id"]);
					}
					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("QTab");
					$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
					$this->tpl->parseCurrentBlock();
				}
				if (!$data["questionblock_id"])
				{
					$this->tpl->setCurrentBlock("checkable");
					$this->tpl->setVariable("QUESTION_ID", $data["question_id"]);
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock("QTab");
				include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
				if ($rbacsystem->checkAccess("write", $this->ref_id) and !$hasDatasets) 
				{
					$q_id = $data["question_id"];
					$qpl_ref_id = current(ilObject::_getAllReferences($data["obj_fi"]));
					$this->tpl->setVariable("QUESTION_TITLE", "$title_counter. <a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&eqid=$q_id&eqpl=$qpl_ref_id" . "\">" . $data["title"] . "</a>");
				}
				else
				{
					$this->tpl->setVariable("QUESTION_TITLE", "$title_counter. ". $data["title"]);
				}
				$this->tpl->setVariable("TYPE_ICON", "<img src=\"" . ilUtil::getImagePath("question.gif", "Modules/Survey") . "\" alt=\"".$this->lng->txt("question_icon")."\" />");
				if ($rbacsystem->checkAccess("write", $this->ref_id) and !$hasDatasets) 
				{
					$obligatory_checked = "";
					if ($data["obligatory"] == 1)
					{
						$obligatory_checked = " checked=\"checked\"";
					}
					$this->tpl->setVariable("QUESTION_OBLIGATORY", "<input type=\"checkbox\" name=\"obligatory_" . $data["question_id"] . "\" value=\"1\"$obligatory_checked />");
				}
				else
				{
					if ($data["obligatory"] == 1)
					{
						$this->tpl->setVariable("QUESTION_OBLIGATORY", $obligatory);
					}
				}
				$this->tpl->setVariable("QUESTION_COMMENT", $data["description"]);
				if ($rbacsystem->checkAccess("write", $this->ref_id) and !$hasDatasets) 
				{
					if (!$data["questionblock_id"])
					{
						// up/down buttons for non-questionblock questions
						if ($data["question_id"] != $this->object->questions[0])
						{
							$this->tpl->setVariable("BUTTON_UP", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&up=" . $data["question_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_up.gif") . "\" alt=\"".$this->lng->txt("up")."\" border=\"0\" /></a>");
						}
						if ($data["question_id"] != $this->object->questions[count($this->object->questions)-1])
						{
							$this->tpl->setVariable("BUTTON_DOWN", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&down=" . $data["question_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_down.gif") . "\" alt=\"".$this->lng->txt("down")."\" border=\"0\" /></a>");
						}
					}
					else
					{
						// up/down buttons for questionblock questions
						if ($data["questionblock_id"] == $last_questionblock_id)
						{
							$this->tpl->setVariable("BUTTON_UP", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&up=" . $data["question_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_up.gif") . "\" alt=\"".$this->lng->txt("up")."\" border=\"0\" /></a>");
						}
						$tmp_questions = array_keys($survey_questions);
						$blockkey = array_search($question_id, $tmp_questions);
						if (($blockkey !== FALSE) && ($blockkey < count($tmp_questions)-1))
						{
							if ($data["questionblock_id"] == $survey_questions[$tmp_questions[$blockkey+1]]["questionblock_id"])
							{
								$this->tpl->setVariable("BUTTON_DOWN", "<a href=\"" . $this->ctrl->getLinkTarget($this, "questions") . "&down=" . $data["question_id"] . "\"><img src=\"" . ilUtil::getImagePath("a_down.gif") . "\" alt=\"".$this->lng->txt("down")."\" border=\"0\" /></a>");
							}
						}
					}
				}
				foreach ($questiontypes as $trans => $typedata)
				{
					if (strcmp($typedata["type_tag"], $data["type_tag"]) == 0)
					{
						$this->tpl->setVariable("QUESTION_TYPE", $trans);
					}
				}
				$this->tpl->setVariable("QUESTION_AUTHOR", $data["author"]);
				$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
				$last_color_class = $colors[$counter % 2];
				if (!$data["questionblock_id"])
				{
					$counter++;
				}
				$this->tpl->parseCurrentBlock();
				$last_questionblock_id = $data["questionblock_id"];
			}

			if ($rbacsystem->checkAccess("write", $this->ref_id) and !$hasDatasets) 
			{
				$this->tpl->setCurrentBlock("selectall");
				$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
				$this->tpl->setVariable("COLOR_CLASS", $last_color_class);
				$this->tpl->parseCurrentBlock();
				if (array_key_exists("move_questions", $_SESSION))
				{
					$this->tpl->setCurrentBlock("move_buttons");
					$this->tpl->setVariable("INSERT_BEFORE", $this->lng->txt("insert_before"));
					$this->tpl->setVariable("INSERT_AFTER", $this->lng->txt("insert_after"));
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock("QFooter");
				$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
				$this->tpl->setVariable("REMOVE", $this->lng->txt("remove_question"));
				$this->tpl->setVariable("MOVE", $this->lng->txt("move"));
				$this->tpl->setVariable("QUESTIONBLOCK", $this->lng->txt("define_questionblock"));
				$this->tpl->setVariable("UNFOLD", $this->lng->txt("unfold"));
				$this->tpl->setVariable("SAVE", $this->lng->txt("save_obligatory_state"));
				$this->tpl->parseCurrentBlock();
			}
		}
		else
		{
			$this->tpl->setCurrentBlock("Emptytable");
			$this->tpl->setVariable("TEXT_EMPTYTABLE", $this->lng->txt("no_questions_available"));
			$this->tpl->parseCurrentBlock();
		}
		if (($last_questionblock_id > 0))
		{
			// add a separator line for the end of a question block (if the last question is a questionblock question)
			$this->tpl->setCurrentBlock("separator");
			$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("QTab");
			$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
			$this->tpl->parseCurrentBlock();
		}

		if ($rbacsystem->checkAccess("write", $this->ref_id) and !$hasDatasets) 
		{
			$this->tpl->setCurrentBlock("QTypes");
			include_once "./Modules/SurveyQuestionPool/classes/class.ilObjSurveyQuestionPool.php";
			$qtypes = ilObjSurveyQuestionPool::_getQuestiontypes();
			foreach ($qtypes as $translation => $data)
			{
				$this->tpl->setVariable("QUESTION_TYPE_ID", $data["type_tag"]);
				$this->tpl->setVariable("QUESTION_TYPE", $translation);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "questions"));
		$this->tpl->setVariable("QUESTION_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("QUESTION_COMMENT", $this->lng->txt("description"));
		$this->tpl->setVariable("QUESTION_OBLIGATORY", $this->lng->txt("obligatory"));
		$this->tpl->setVariable("QUESTION_SEQUENCE", $this->lng->txt("sequence"));
		$this->tpl->setVariable("QUESTION_TYPE", $this->lng->txt("question_type"));
		$this->tpl->setVariable("QUESTION_AUTHOR", $this->lng->txt("author"));

		if ($rbacsystem->checkAccess("write", $this->ref_id) and !$hasDatasets)
		{
			$this->tpl->setVariable("BUTTON_INSERT_QUESTION", $this->lng->txt("browse_for_questions"));
			global $ilUser;
			$this->tpl->setVariable('BROWSE_COMMAND', ($ilUser->getPref('svy_insert_type') == 1 || strlen($ilUser->getPref('svy_insert_type')) == 0) ? 'browseForQuestions' : 'browseForQuestionblocks');
			$this->tpl->setVariable("TEXT_CREATE_NEW", " " . strtolower($this->lng->txt("or")) . " " . $this->lng->txt("create_new"));
			$this->tpl->setVariable("BUTTON_CREATE_QUESTION", $this->lng->txt("create"));
			$this->tpl->setVariable("HEADING", $this->lng->txt("add_heading"));
		}
		if ($hasDatasets)
		{
			ilUtil::sendInfo($this->lng->txt("survey_has_datasets_warning"));
		}

		$this->tpl->parseCurrentBlock();
		$this->questionsSubtabs("questions");
	}

	/**
	* Redirects the evaluation object call to the ilSurveyEvaluationGUI class
	*
	* Redirects the evaluation object call to the ilSurveyEvaluationGUI class
	*
	* @access	private
	*/
	function evaluationObject()
	{
		include_once("./Modules/Survey/classes/class.ilSurveyEvaluationGUI.php");
		$eval_gui = new ilSurveyEvaluationGUI($this->object);
		$this->ctrl->setCmdClass(get_class($eval_gui));
		$this->ctrl->redirect($eval_gui, "evaluation");
	}
	
	/**
	* Disinvite users or groups from a survey
	*/
	public function disinviteUserGroupObject()
	{
		// disinvite users
		if (is_array($_POST["user_select"]))
		{
			foreach ($_POST["user_select"] as $user_id)
			{
				$this->object->disinviteUser($user_id);
			}
		}
		ilUtil::sendSuccess($this->lng->txt('msg_users_disinvited'), true);
		$this->ctrl->redirect($this, "invite");
	}
	
	/**
	* Invite users or groups to a survey
	*/
	public function inviteUserGroupObject()
	{
		$invited = 0;
		// add users to invitation
		if (is_array($_POST["user"]))
		{
			foreach ($_POST["user"] as $user_id)
			{
				$this->object->inviteUser($user_id);
				$invited++;
			}
		}
		if ($invited == 0)
		{
			ilUtil::sendFailure($this->lng->txt('no_user_invited'), TRUE);	
		}
		else
		{
			ilUtil::sendSuccess(sprintf($this->lng->txt('users_invited'), $invited), TRUE);	
		}
		$this->ctrl->redirect($this, "invite");
	}

	/**
	* Saves the status of the invitation tab
	*/
	public function saveInvitationStatusObject()
	{
		$mode = $_POST['invitation'];
		switch ($mode)
		{
			case 0:
				$this->object->setInvitation(0);
				break;
			case 1:
				$this->object->setInvitation(1);
				$this->object->setInvitationMode(0);
				break;
			case 2:
				$this->object->setInvitation(1);
				$this->object->setInvitationMode(1);
				break;
		}
		$this->object->saveToDb();
		ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
		$this->ctrl->redirect($this, "invite");
	}
	
	
	/**
	* Creates the output for user/group invitation to a survey
	*/
	public function inviteObject()
	{
		global $ilAccess;
		global $rbacsystem;
		global $ilToolbar;

		if ((!$rbacsystem->checkAccess("visible,invite", $this->ref_id)) && (!$rbacsystem->checkAccess("write", $this->ref_id))) 
		{
			// allow only read and write access
			ilUtil::sendInfo($this->lng->txt("cannot_edit_survey"), true);
			$path = $this->tree->getPathFull($this->object->getRefID());
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			ilUtil::redirect($this->getReturnLocation("cancel","./repository.php?cmd=frameset&ref_id=" . $path[count($path) - 2]["child"]));
			return;
		}

		if ($this->object->getStatus() == STATUS_OFFLINE)
		{
			ilUtil::sendInfo($this->lng->txt("survey_offline_message"));
			return;
		}

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTableWidth("500");
		$form->setId("invite");

		// invitation
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->lng->txt("invitation"));
		$form->addItem($header);
		
		// invitation mode
		$invitation = new ilRadioGroupInputGUI($this->lng->txt('invitation_mode'), "invitation");
		$invitation->setInfo($this->lng->txt('invitation_mode_desc'));
		$invitation->addOption(new ilRadioOption($this->lng->txt("invitation_off"), 0, ''));
		$surveySetting = new ilSetting("survey");
		if ($surveySetting->get("unlimited_invitation"))
		{
			$invitation->addOption(new ilRadioOption($this->lng->txt("unlimited_users"), 1, ''));
		}
		$invitation->addOption(new ilRadioOption($this->lng->txt("predefined_users"), 2, ''));
		$inv = 0;
		if ($this->object->getInvitation())
		{
			$inv = $this->object->getInvitationMode() + 1;
		}
		$invitation->setValue($inv);
		$form->addItem($invitation);
		
		$form->addCommandButton("saveInvitationStatus", $this->lng->txt("save"));

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_invite.html", "Modules/Survey");
		$this->tpl->setVariable("INVITATION_TABLE", $form->getHTML());

		if ($this->object->getInvitation() && $this->object->getInvitationMode() == 1)
		{
			// search button
			$ilToolbar->addButton($this->lng->txt("svy_search_users"),
				$this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI','start'));

			$this->tpl->setVariable("ADM_CONTENT", $form->getHTML());

			$invited_users = $this->object->getUserData($this->object->getInvitedUsers());
			include_once "./Modules/Survey/classes/tables/class.ilSurveyInvitedUsersTableGUI.php";
			$table_gui = new ilSurveyInvitedUsersTableGUI($this, 'invite');
			$table_gui->setData($invited_users);
			$this->tpl->setVariable('TBL_INVITED_USERS', $table_gui->getHTML());	
		}
	}

	/**
	* Creates a confirmation form for delete all user data
	*/
	public function deleteAllUserDataObject()
	{
		ilUtil::sendQuestion($this->lng->txt("confirm_delete_all_user_data"));
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_maintenance.html", "Modules/Survey");
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("BTN_CONFIRM_DELETE_ALL", $this->lng->txt("confirm"));
		$this->tpl->setVariable("BTN_CANCEL_DELETE_ALL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "deleteAllUserData"));
		$this->tpl->parseCurrentBlock();
	}
	
	/**
	* Deletes all user data of the survey after confirmation
	*/
	public function confirmDeleteAllUserDataObject()
	{
		$this->object->deleteAllUserData();
		ilUtil::sendSuccess($this->lng->txt("svy_all_user_data_deleted"), true);
		$this->ctrl->redirect($this, "maintenance");
	}
	
	/**
	* Cancels delete of all user data in maintenance
	*/
	public function cancelDeleteAllUserDataObject()
	{
		$this->ctrl->redirect($this, "maintenance");
	}
	
	/**
	* Deletes all user data for the test object
	*/
	public function confirmDeleteSelectedUserDataObject()
	{
		$this->object->removeSelectedSurveyResults($_POST["chbUser"]);
		ilUtil::sendSuccess($this->lng->txt("svy_selected_user_data_deleted"), true);
		$this->ctrl->redirect($this, "maintenance");
	}
	
	/**
	* Cancels the deletion of all user data for the test object
	*/
	public function cancelDeleteSelectedUserDataObject()
	{
		ilUtil::sendInfo($this->lng->txt('msg_cancel'), true);
		$this->ctrl->redirect($this, "maintenance");
	}
	
	/**
	* Asks for a confirmation to delete selected user data of the test object
	*/
	public function deleteSingleUserResultsObject()
	{
		$this->handleWriteAccess();

		if (count($_POST["chbUser"]) == 0)
		{
			ilUtil::sendInfo($this->lng->txt('no_checkbox'), true);
			$this->ctrl->redirect($this, "maintenance");
		}

		ilUtil::sendQuestion($this->lng->txt("confirm_delete_single_user_data"));
		include_once "./Modules/Survey/classes/tables/class.ilSurveyMaintenanceTableGUI.php";
		$table_gui = new ilSurveyMaintenanceTableGUI($this, 'maintenance', true);
		$total =& $this->object->getSurveyParticipants();
		$data = array();
		foreach ($total as $user_data)
		{
			if (in_array($user_data['active_id'], $_POST['chbUser']))
			{
				$last_access = $this->object->_getLastAccess($user_data["active_id"]);
				array_push($data, array(
					'id' => $user_data["active_id"],
					'name' => $user_data["sortname"],
					'login' => $user_data["login"],
					'last_access' => ilDatePresentation::formatDate(new ilDateTime($last_access,IL_CAL_UNIX))
				));
			}
		}
		$table_gui->setData($data);
		$this->tpl->setVariable('ADM_CONTENT', $table_gui->getHTML());	
	}
	
	/**
	* Participants maintenance
	*/
	public function maintenanceObject()
	{
		$this->handleWriteAccess();

		if ($_GET["fill"] > 0) 
		{
			for ($i = 0; $i < $_GET["fill"]; $i++) $this->object->fillSurveyForUser();
		}
		include_once "./Modules/Survey/classes/tables/class.ilSurveyMaintenanceTableGUI.php";
		$table_gui = new ilSurveyMaintenanceTableGUI($this, 'maintenance');
		$total =& $this->object->getSurveyParticipants();
		$data = array();
		foreach ($total as $user_data)
		{
			$last_access = $this->object->_getLastAccess($user_data["active_id"]);
			array_push($data, array(
				'id' => $user_data["active_id"],
				'name' => $user_data["sortname"],
				'login' => $user_data["login"],
				'last_access' => ilDatePresentation::formatDate(new ilDateTime($last_access,IL_CAL_UNIX))
			));
		}
		$table_gui->setData($data);
		$this->tpl->setVariable('ADM_CONTENT', $table_gui->getHTML());	
	}	

	/**
	* display dialogue for importing tests
	*
	* @access	public
	*/
	function importObject()
	{
		$this->getTemplateFile("import", "svy");
		$this->tpl->setCurrentBlock("option_qpl");
		include_once("./Modules/Survey/classes/class.ilObjSurvey.php");
		$svy = new ilObjSurvey();
		$questionpools =& $svy->getAvailableQuestionpools(TRUE, FALSE, TRUE);
		if (count($questionpools) == 0)
		{
		}
		else
		{
			foreach ($questionpools as $key => $value)
			{
				$this->tpl->setCurrentBlock("option_spl");
				$this->tpl->setVariable("OPTION_VALUE", $key);
				$this->tpl->setVariable("TXT_OPTION", $value);
				$this->tpl->parseCurrentBlock();
			}
		}
		$this->tpl->setVariable("TXT_SELECT_QUESTIONPOOL", $this->lng->txt("select_questionpool"));
		$this->tpl->setVariable("OPTION_SELECT_QUESTIONPOOL", $this->lng->txt("select_questionpool_option"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this, "import"));
		$this->tpl->setVariable("BTN_NAME", "upload");
		$this->tpl->setVariable("TXT_UPLOAD", $this->lng->txt("upload"));
		$this->tpl->setVariable("TXT_IMPORT_TST", $this->lng->txt("import_tst"));
		$this->tpl->setVariable("TXT_SELECT_MODE", $this->lng->txt("select_mode"));
		$this->tpl->setVariable("TXT_SELECT_FILE", $this->lng->txt("select_file"));

	}

	/**
	* display status information or report errors messages
	* in case of error
	*
	* @access	public
	*/
	function uploadObject($redirect = true)
	{
		if ($_POST["spl"] < 1)
		{
			ilUtil::sendInfo($this->lng->txt("svy_select_questionpools"));
			$this->importObject();
			return;
		}
		if (strcmp($_FILES["xmldoc"]["tmp_name"], "") == 0)
		{
			ilUtil::sendInfo($this->lng->txt("svy_select_file_for_import"));
			$this->importObject();
			return;
		}
		
		include_once("./Modules/Survey/classes/class.ilObjSurvey.php");
		$newObj = new ilObjSurvey();
		$newObj->setType($_GET["new_type"]);
		$newObj->setTitle("dummy");
		$newObj->setDescription("dummy");
		$newObj->create(true);
		$newObj->createReference();
		$newObj->putInTree($_GET["ref_id"]);
		$newObj->setPermissions($_GET["ref_id"]);
		$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());
		// copy uploaded file to import directory
		$error = $newObj->importObject($_FILES["xmldoc"], $_POST["spl"]);
		if (strlen($error)) 
		{  
			$newObj->delete();
			$this->ilias->raiseError($error, $this->ilias->error_obj->MESSAGE);
			return;
		}
		else
		{
			$ref_id = $newObj->getRefId();
		}
		if ($redirect)
		{
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			ilUtil::redirect($this->getReturnLocation("upload",$this->ctrl->getTargetScript()."?".$this->link_params));
		}
		return $ref_id;
	}

	/**
	* form for new content object creation
	*/
	function createObject()
	{
		global $rbacsystem;
		$new_type = $_POST["new_type"] ? $_POST["new_type"] : $_GET["new_type"];
		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $new_type))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			$this->getTemplateFile("create", $new_type);

			include_once("./Modules/Survey/classes/class.ilObjSurvey.php");
			$svy = new ilObjSurvey();
			
			$this->fillCloneTemplate('DUPLICATE','svy');
			$questionpools =& $svy->getAvailableQuestionpools($use_obj_id = TRUE, $could_be_offline = TRUE, $showPath = TRUE);
			if (count($questionpools) > 0)
			{
				foreach ($questionpools as $key => $value)
				{
					$this->tpl->setCurrentBlock("option_spl");
					$this->tpl->setVariable("OPTION_VALUE", $key);
					$this->tpl->setVariable("TXT_OPTION", $value);
					if ($_POST["spl"] == $key)
					{
						$this->tpl->setVariable("OPTION_SELECTED", " selected=\"selected\"");				
					}
					$this->tpl->parseCurrentBlock();
				}
			}
			// fill in saved values in case of error
			$data = array();
			$data["fields"] = array();
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			$data["fields"]["title"] = ilUtil::prepareFormOutput($_SESSION["error_post_vars"]["Fobject"]["title"],true);
			$data["fields"]["desc"] = ilUtil::prepareFormOutput($_SESSION["error_post_vars"]["Fobject"]["desc"]);

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);

				if ($this->prepare_output)
				{
					$this->tpl->parseCurrentBlock();
				}
			}

			$this->ctrl->setParameter($this, "new_type", $this->type);
			$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this, "create"));
			$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($new_type."_new"));
			$this->tpl->setVariable("TXT_SELECT_QUESTIONPOOL", $this->lng->txt("select_questionpool_short"));
			$this->tpl->setVariable("OPTION_SELECT_QUESTIONPOOL", $this->lng->txt("select_questionpool_option"));
			$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt($new_type."_add"));
			$this->tpl->setVariable("CMD_SUBMIT", "save");
			$this->tpl->setVariable("TARGET", ' target="'.
				ilFrameTargetInfo::_getFrame("MainContent").'" ');
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));

			$this->tpl->setVariable("TXT_IMPORT_SVY", $this->lng->txt("import_svy"));
			$this->tpl->setVariable("TXT_SVY_FILE", $this->lng->txt("svy_upload_file"));
			$this->tpl->setVariable("TXT_IMPORT", $this->lng->txt("import"));

			$this->tpl->setVariable("TYPE_IMG", ilUtil::getImagePath('icon_svy.gif'));
			$this->tpl->setVariable("ALT_IMG",$this->lng->txt("obj_svy"));
			$this->tpl->setVariable("TYPE_IMG2", ilUtil::getImagePath('icon_svy.gif'));
			$this->tpl->setVariable("ALT_IMG2",$this->lng->txt("obj_svy"));
		}
	}
	
	/**
	* form for new survey object import
	*/
	function importFileObject()
	{
		if ($_POST["spl"] < 1)
		{
			ilUtil::sendInfo($this->lng->txt("svy_select_questionpools"));
			$this->createObject();
			return;
		}
		if (strcmp($_FILES["xmldoc"]["tmp_name"], "") == 0)
		{
			ilUtil::sendInfo($this->lng->txt("svy_select_file_for_import"));
			$this->createObject();
			return;
		}
		$this->ctrl->setParameter($this, "new_type", $this->type);
		$ref_id = $this->uploadObject(false);
		// always send a message
		ilUtil::sendSuccess($this->lng->txt("object_imported"),true);

		ilUtil::redirect("ilias.php?ref_id=".$ref_id.
			"&baseClass=ilObjSurveyGUI");
//		$this->ctrl->redirect($this, "importFile");
	}

  /*
	* list all export files
	*/
	public function exportObject()
	{
		$this->handleWriteAccess();

		$export_dir = $this->object->getExportDirectory();
		$export_files = $this->object->getExportFiles($export_dir);
		$data = array();
		if(count($export_files) > 0)
		{
			foreach($export_files as $exp_file)
			{
				$file_arr = explode("__", $exp_file);
				$date = new ilDateTime($file_arr[0], IL_CAL_UNIX);
				array_push($data, array(
					'file' => $exp_file,
					'size' => filesize($export_dir."/".$exp_file),
					'date' => $date->get(IL_CAL_DATETIME)
				));
			}
		}

		include_once "./Modules/Survey/classes/tables/class.ilSurveyExportTableGUI.php";
		$table_gui = new ilSurveyExportTableGUI($this, 'export');
		$table_gui->setData($data);
		$this->tpl->setVariable('ADM_CONTENT', $table_gui->getHTML());	
	}

	/**
	* create export file
	*/
	public function createExportFileObject()
	{
		$this->handleWriteAccess();
		include_once("./Modules/Survey/classes/class.ilSurveyExport.php");
		$survey_exp = new ilSurveyExport($this->object);
		$survey_exp->buildExportFile();
		$this->ctrl->redirect($this, "export");
	}

	/**
	* download export file
	*/
	public function downloadExportFileObject()
	{
		if(!isset($_POST["file"]))
		{
			ilUtil::sendFailure($this->lng->txt("no_checkbox"), true);
			$this->ctrl->redirect($this, "export");
		}

		if (count($_POST["file"]) > 1)
		{
			ilUtil::sendFailure($this->lng->txt("select_max_one_item"), true);
			$this->ctrl->redirect($this, "export");
		}


		$export_dir = $this->object->getExportDirectory();
		include_once "./Services/Utilities/classes/class.ilUtil.php";
		ilUtil::deliverFile($export_dir."/".$_POST["file"][0],
			$_POST["file"][0]);
	}

	/**
	* confirmation screen for export file deletion
	*/
	function confirmDeleteExportFileObject()
	{
		$this->handleWriteAccess();

		if (!isset($_POST["file"]))
		{
			ilUtil::sendFailure($this->lng->txt("no_checkbox"), true);
			$this->ctrl->redirect($this, "export");
		}

		ilUtil::sendQuestion($this->lng->txt("info_delete_sure"));

		$export_dir = $this->object->getExportDirectory();
		$export_files = $this->object->getExportFiles($export_dir);
		$data = array();
		if (count($_POST["file"]) > 0)
		{
			foreach ($_POST["file"] as $exp_file)
			{
				$file_arr = explode("__", $exp_file);
				$date = new ilDateTime($file_arr[0], IL_CAL_UNIX);
				array_push($data, array(
					'file' => $exp_file,
					'size' => filesize($export_dir."/".$exp_file),
					'date' => $date->get(IL_CAL_DATETIME)
				));
			}
		}

		include_once "./Modules/Survey/classes/tables/class.ilSurveyExportTableGUI.php";
		$table_gui = new ilSurveyExportTableGUI($this, 'export', true);
		$table_gui->setData($data);
		$this->tpl->setVariable('ADM_CONTENT', $table_gui->getHTML());	
	}


	/**
	* cancel deletion of export files
	*/
	public function cancelDeleteExportFileObject()
	{
		ilUtil::sendInfo($this->lng->txt('msg_cancel'), true);
		$this->ctrl->redirect($this, "export");
	}


	/**
	* delete export files
	*/
	public function deleteExportFileObject()
	{
		$export_dir = $this->object->getExportDirectory();
		foreach ($_POST["file"] as $file)
		{
			$exp_file = $export_dir."/".$file;
			$exp_dir = $export_dir."/".substr($file, 0, strlen($file) - 4);
			if (@is_file($exp_file))
			{
				unlink($exp_file);
			}
			if (@is_dir($exp_dir))
			{
				include_once "./Services/Utilities/classes/class.ilUtil.php";
				ilUtil::delDir($exp_dir);
			}
		}
		ilUtil::sendSuccess($this->lng->txt('msg_deleted_export_files'), true);
		$this->ctrl->redirect($this, "export");
	}

	/**
	* Change survey language for direct access URL's
	*/
	public function setCodeLanguageObject()
	{
		if (strcmp($_POST["lang"], "-1") != 0)
		{
			global $ilUser;
			$ilUser->writePref("survey_code_language", $_POST["lang"]);
		}
		ilUtil::sendSuccess($this->lng->txt('language_changed'), true);
		$this->ctrl->redirect($this, 'codes');
	}
	
	/**
	* Display the survey access codes tab
	*/
	public function codesObject()
	{
		$this->handleWriteAccess();
		$this->setCodesSubtabs();
		global $ilUser;
		if ($this->object->getAnonymize() != 1)
		{
			return ilUtil::sendInfo($this->lng->txt("survey_codes_no_anonymization"));
		}

		include_once "./Modules/Survey/classes/tables/class.ilSurveyCodesTableGUI.php";
		$table_gui = new ilSurveyCodesTableGUI($this, 'codes');
		$default_lang = $ilUser->getPref("survey_code_language");
		$survey_codes =& $this->object->getSurveyCodesTableData($default_lang);
		$table_gui->setData($survey_codes);
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_codes.html", true);
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "codes"));
		$this->tpl->setVariable("TEXT_CREATE", $this->lng->txt("create"));
		$this->tpl->setVariable("TEXT_SURVEY_CODES", $this->lng->txt("new_survey_codes"));
		$this->tpl->setVariable('TABLE', $table_gui->getHTML());	
	}
	
	/**
	* Delete a list of survey codes
	*/
	public function deleteCodesObject()
	{
		if (is_array($_POST["chb_code"]) && (count($_POST["chb_code"]) > 0))
		{
			foreach ($_POST["chb_code"] as $survey_code)
			{
				$this->object->deleteSurveyCode($survey_code);
			}
			ilUtil::sendSuccess($this->lng->txt('codes_deleted'), true);
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt('no_checkbox'), true);
		}
		$this->ctrl->redirect($this, 'codes');
	}
	
	/**
	* Exports a list of survey codes
	*/
	public function exportCodesObject()
	{
		if (is_array($_POST["chb_code"]) && (count($_POST["chb_code"]) > 0))
		{
			$export = $this->object->getSurveyCodesForExport($_POST["chb_code"]);
			ilUtil::deliverData($export, ilUtil::getASCIIFilename($this->object->getTitle() . ".txt"));
		}
		else
		{
			ilUtil::sendFailure($this->lng->txt("no_checkbox"), true);
			$this->ctrl->redirect($this, 'codes');
		}
	}
	
	/**
	* Exports all survey codes
	*/
	public function exportAllCodesObject()
	{
		$export = $this->object->getSurveyCodesForExport(array());
		ilUtil::deliverData($export, ilUtil::getASCIIFilename($this->object->getTitle() . ".txt"));
	}
	
	/**
	* Create access codes for the survey
	*/
	public function createSurveyCodesObject()
	{
		if (preg_match("/\d+/", $_POST["nrOfCodes"]))
		{
			$this->object->createSurveyCodes($_POST["nrOfCodes"]);
			ilUtil::sendSuccess($this->lng->txt('codes_created'), true);
		}
		else
		{
			ilUtil::sendFailure($this->lng->txt("enter_valid_number_of_codes"), true);
		}
		$this->ctrl->redirect($this, 'codes');
	}
	
	/**
	* Sending access codes via email
	*/
	public function codesMailObject($checkonly = false)
	{
		global $ilAccess;
		
		$this->handleWriteAccess();
		$this->setCodesSubtabs();

		$savefields = (strcmp($this->ctrl->getCmd(), "saveMailTableFields") == 0) ? TRUE : FALSE;

		include_once "./Modules/Survey/classes/tables/class.ilSurveyCodesMailTableGUI.php";
		$data = $this->object->getExternalCodeRecipients();
		$table_gui = new ilSurveyCodesMailTableGUI($this, 'codesMail');
		$table_gui->setData($data);
		$table_gui->setTitle($this->lng->txt('externalRecipients'));
		$table_gui->completeColumns();
		$tabledata = $table_gui->getHTML();	
		
		if (!$checkonly)
		{
			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_codes_mail.html", true);
			$this->tpl->setCurrentBlock("adm_content");
			$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "codesMail"));
			$this->tpl->setVariable("MAIL_CODES", $this->lng->txt("mail_survey_codes"));
			$this->tpl->setVariable('TABLE', $tabledata);	
		}
		return $errors;
	}
	
	public function insertSavedMessageObject()
	{
		$this->handleWriteAccess();
		$this->setCodesSubtabs();

		include_once("./Modules/Survey/classes/forms/FormMailCodesGUI.php");
		$form_gui = new FormMailCodesGUI($this);
		$form_gui->setValuesByPost();
		try
		{
			if ($form_gui->getSavedMessages()->getValue() > 0)
			{
				global $ilUser;
				$settings = $this->object->getUserSettings($ilUser->getId(), 'savemessage');
				$form_gui->getMailMessage()->setValue($settings[$form_gui->getSavedMessages()->getValue()]['value']);
				ilUtil::sendSuccess($this->lng->txt('msg_message_inserted'));
			}
			else
			{
				ilUtil::sendFailure($this->lng->txt('msg_no_message_inserted'));
			}
		}
		catch (Exception $e)
		{
			global $ilLog;
			$ilLog->write('Error: ' + $e->getMessage());
		}
		$this->tpl->setVariable("ADM_CONTENT", $form_gui->getHTML());
	}

	public function deleteSavedMessageObject()
	{
		$this->handleWriteAccess();
		$this->setCodesSubtabs();

		include_once("./Modules/Survey/classes/forms/FormMailCodesGUI.php");
		$form_gui = new FormMailCodesGUI($this);
		$form_gui->setValuesByPost();
		try
		{
			if ($form_gui->getSavedMessages()->getValue() > 0)
			{
				$this->object->deleteUserSettings($form_gui->getSavedMessages()->getValue());
				$form_gui = new FormMailCodesGUI($this);
				$form_gui->setValuesByPost();
				ilUtil::sendSuccess($this->lng->txt('msg_message_deleted'));
			}
			else
			{
				ilUtil::sendFailure($this->lng->txt('msg_no_message_deleted'));
			}
		}
		catch (Exception $e)
		{
			global $ilLog;
			$ilLog->write('Error: ' + $e->getMessage());
		}
		$this->tpl->setVariable("ADM_CONTENT", $form_gui->getHTML());
	}
	
	public function mailCodesObject()
	{
		$this->handleWriteAccess();
		$this->setCodesSubtabs();

		$mailData['m_subject'] = (array_key_exists('m_subject', $_POST)) ? $_POST['m_subject'] : sprintf($this->lng->txt('default_codes_mail_subject'), $this->object->getTitle());
		$mailData['m_message'] = (array_key_exists('m_message', $_POST)) ? $_POST['m_message'] : $this->lng->txt('default_codes_mail_message');
		$mailData['m_type'] = (array_key_exists('m_type', $_POST)) ? $_POST['m_type'] : '';
		$mailData['m_notsent'] = (array_key_exists('m_notsent', $_POST)) ? $_POST['m_notsent'] : '1';

		include_once("./Modules/Survey/classes/forms/FormMailCodesGUI.php");
		$form_gui = new FormMailCodesGUI($this);
		$form_gui->setValuesByArray($mailData);
		$this->tpl->setVariable("ADM_CONTENT", $form_gui->getHTML());
	}
	
	public function sendCodesMailObject()
	{
		include_once("./Modules/Survey/classes/forms/FormMailCodesGUI.php");
		$form_gui = new FormMailCodesGUI($this);
		if ($form_gui->checkInput())
		{
			$code_exists = strpos($_POST['m_message'], '[code]') !== FALSE;
			if (!$code_exists)
			{
				if (!$code_exists) ilUtil::sendFailure($this->lng->txt('please_enter_mail_code'));
				$form_gui->setValuesByPost();
			}
			else
			{
				if ($_POST['savemessage'] == 1)
				{
					global $ilUser;
					$title = (strlen($_POST['savemessagetitle'])) ? $_POST['savemessagetitle'] : ilStr::substr($_POST['m_message'], 0, 40) . '...';
					$this->object->saveUserSettings($ilUser->getId(), 'savemessage', $title, $_POST['m_message']);
				}
				$this->object->sendCodes($_POST['m_type'], $_POST['m_notsent'], $_POST['m_subject'], $_POST['m_message']);
				ilUtil::sendSuccess($this->lng->txt('mail_sent'), true);
				$this->ctrl->redirect($this, 'codesMail');
			}
		}
		else
		{
			$form_gui->setValuesByPost();
		}
		$this->tpl->setVariable("ADM_CONTENT", $form_gui->getHTML());
	}
	
	public function cancelCodesMailObject()
	{
		$this->ctrl->redirect($this, 'codesMail');
	}

	public function deleteInternalMailRecipientObject()
	{
		if (!is_array($_POST['chb_ext']) || count(is_array($_POST['chb_ext'])) == 0)
		{
			ilUtil::sendInfo($this->lng->txt("err_no_selection"), true);
			$this->ctrl->redirect($this, 'codesMail');
		}
		foreach ($_POST['chb_ext'] as $code)
		{
			$this->object->deleteSurveyCode($code);
		}
		ilUtil::sendSuccess($this->lng->txt('external_recipients_deleted'), true);
		$this->ctrl->redirect($this, 'codesMail');
	}
	
	public function importExternalRecipientsFromDatasetObject()
	{
		$hasErrors = $this->importExternalMailRecipientsObject(true, 2);
		if (!$hasErrors)
		{
			$data = array();
			$existingdata = $this->object->getExternalCodeRecipients();
			if (count($existingdata))
			{
				$first = array_shift($existingdata);
				foreach ($first as $key => $value)
				{
					if (strcmp($key, 'code') != 0 && strcmp($key, 'sent') != 0)
					{
						$data[$key] = $_POST[$key];
					}
				}
			}
			if (count($data))
			{
				$this->object->createSurveyCodesForExternalData(array($data));
				ilUtil::sendSuccess($this->lng->txt('external_recipients_imported'), true);
			}
			$this->ctrl->redirect($this, 'codesMail');
		}
	}

	public function importExternalRecipientsFromTextObject()
	{
		$hasErrors = $this->importExternalMailRecipientsObject(true, 1);
		if (!$hasErrors)
		{
			$data = preg_split("/[\n\r]/", $_POST['externaltext']);
			$fields = preg_split("/;/", array_shift($data));
			if (!in_array('email', $fields))
			{
				ilUtil::sendFailure($this->lng->txt('err_external_rcp_no_email'), true);
				$this->ctrl->redirect($this, 'codesMail');
			}
			$existingdata = $this->object->getExternalCodeRecipients();
			$existingcolumns = array();
			if (count($existingdata))
			{
				$first = array_shift($existingdata);
				foreach ($first as $key => $value)
				{
					array_push($existingcolumns, $key);
				}
			}
			$founddata = array();
			foreach ($data as $datarow)
			{
				$row = preg_split("/;/", $datarow);
				if (count($row) == count($fields))
				{
					$dataset = array();
					foreach ($fields as $idx => $fieldname)
					{
						if (count($existingcolumns))
						{
							if (array_key_exists($idx, $existingcolumns))
							{
								$dataset[$fieldname] = $row[$idx];
							}
						}
						else
						{
							$dataset[$fieldname] = $row[$idx];
						}
					}
					if (strlen($dataset['email']))
					{
						array_push($founddata, $dataset);
					}
				}
			}
			$this->object->createSurveyCodesForExternalData($founddata);
			ilUtil::sendSuccess($this->lng->txt('external_recipients_imported'), true);
			$this->ctrl->redirect($this, 'codesMail');
		}
	}

	public function importExternalRecipientsFromFileObject()
	{
		$hasErrors = $this->importExternalMailRecipientsObject(true, 0);
		if (!$hasErrors)
		{
			include_once "./Services/Utilities/classes/class.ilCSVReader.php";
			$reader = new ilCSVReader();
			$reader->open($_FILES['externalmails']['tmp_name']);
			$data = $reader->getDataArrayFromCSVFile();
			$fields = array_shift($data);
			if (!in_array('email', $fields))
			{
				$reader->close();
				ilUtil::sendFailure($this->lng->txt('err_external_rcp_no_email'), true);
				$this->ctrl->redirect($this, 'codesMail');
			}
			$existingdata = $this->object->getExternalCodeRecipients();
			$existingcolumns = array();
			if (count($existingdata))
			{
				$first = array_shift($existingdata);
				foreach ($first as $key => $value)
				{
					array_push($existingcolumns, $key);
				}
			}
			$founddata = array();
			foreach ($data as $row)
			{
				if (count($row) == count($fields))
				{
					$dataset = array();
					foreach ($fields as $idx => $fieldname)
					{
						if (count($existingcolumns))
						{
							if (array_key_exists($idx, $existingcolumns))
							{
								$dataset[$fieldname] = $row[$idx];
							}
						}
						else
						{
							$dataset[$fieldname] = $row[$idx];
						}
					}
					if (strlen($dataset['email']))
					{
						array_push($founddata, $dataset);
					}
				}
			}
			$reader->close();
			$this->object->createSurveyCodesForExternalData($founddata);
			ilUtil::sendSuccess($this->lng->txt('external_recipients_imported'), true);
			$this->ctrl->redirect($this, 'codesMail');
		}
	}

	function importExternalMailRecipientsObject($checkonly = false, $formindex = -1)
	{
		global $ilAccess;
		
		$this->handleWriteAccess();
		$this->setCodesSubtabs();

		$savefields = (
			strcmp($this->ctrl->getCmd(), "importExternalRecipientsFromFile") == 0 || 
			strcmp($this->ctrl->getCmd(), "importExternalRecipientsFromText") == 0 ||
			strcmp($this->ctrl->getCmd(), "importExternalRecipientsFromDataset") == 0
		) ? TRUE : FALSE;

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form_import_file = new ilPropertyFormGUI();
		$form_import_file->setFormAction($this->ctrl->getFormAction($this));
		$form_import_file->setTableWidth("100%");
		$form_import_file->setId("codes_import_file");

		$headerfile = new ilFormSectionHeaderGUI();
		$headerfile->setTitle($this->lng->txt("import_from_file"));
		$form_import_file->addItem($headerfile);
		
		$externalmails = new ilFileInputGUI($this->lng->txt("externalmails"), "externalmails");
		$externalmails->setInfo($this->lng->txt('externalmails_info'));
		$externalmails->setRequired(true);
		$form_import_file->addItem($externalmails);
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"])) $form_import_file->addCommandButton("importExternalRecipientsFromFile", $this->lng->txt("import"));
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"])) $form_import_file->addCommandButton("codesMail", $this->lng->txt("cancel"));

		// import text

		$form_import_text = new ilPropertyFormGUI();
		$form_import_text->setFormAction($this->ctrl->getFormAction($this));
		$form_import_text->setTableWidth("100%");
		$form_import_text->setId("codes_import_text");

		$headertext = new ilFormSectionHeaderGUI();
		$headertext->setTitle($this->lng->txt("import_from_text"));
		$form_import_text->addItem($headertext);

		$inp = new ilTextAreaInputGUI($this->lng->txt('externaltext'), 'externaltext');
		$inp->setValue("email\n");
		$inp->setRequired(true);
		$inp->setCols(80);
		$inp->setRows(10);
		$inp->setInfo($this->lng->txt('externaltext_info'));
		$form_import_text->addItem($inp);

		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"])) $form_import_text->addCommandButton("importExternalRecipientsFromText", $this->lng->txt("import"));
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"])) $form_import_text->addCommandButton("codesMail", $this->lng->txt("cancel"));

		// import dataset
		
		$form_import_dataset = new ilPropertyFormGUI();
		$form_import_dataset->setFormAction($this->ctrl->getFormAction($this));
		$form_import_dataset->setTableWidth("100%");
		$form_import_dataset->setId("codes_import_dataset");

		$headerfile = new ilFormSectionHeaderGUI();
		$headerfile->setTitle($this->lng->txt("import_from_dataset"));
		$form_import_dataset->addItem($headerfile);
		
		$existingdata = $this->object->getExternalCodeRecipients();
		$existingcolumns = array('email');
		if (count($existingdata))
		{
			$first = array_shift($existingdata);
			foreach ($first as $key => $value)
			{
				if (strcmp($key, 'email') != 0 && strcmp($key, 'code') != 0 && strcmp($key, 'sent') != 0)
				{
					array_push($existingcolumns, $key);
				}
			}
		}
		
		foreach ($existingcolumns as $column)
		{
			$inp = new ilTextInputGUI($column, $column);
			$inp->setSize(50);
			if (strcmp($column, 'email') == 0)
			{
				$inp->setRequired(true);
			}
			else
			{
				$inp->setRequired(false);
			}
			$form_import_dataset->addItem($inp);
		}
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"])) $form_import_dataset->addCommandButton("importExternalRecipientsFromDataset", $this->lng->txt("import"));
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"])) $form_import_dataset->addCommandButton("codesMail", $this->lng->txt("cancel"));

		$errors = false;
		
		if ($savefields)
		{
			switch ($formindex)
			{
				case 0:
					$errors = !$form_import_file->checkInput();
					$form_import_file->setValuesByPost();
					if ($errors) $checkonly = false;
					break;
				case 1:
					$errors = !$form_import_text->checkInput();
					$form_import_text->setValuesByPost();
					if ($errors) $checkonly = false;
					break;
				case 2:
					$errors = !$form_import_dataset->checkInput();
					$form_import_dataset->setValuesByPost();
					if ($errors) $checkonly = false;
					break;
			}
		}

		if (!$checkonly) 
		{
			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_external_mail.html", "Modules/Survey");
			$this->tpl->setVariable("HEADLINE", $this->lng->txt("external_mails_import"));
			$this->tpl->setVariable("FORM1", $form_import_file->getHTML());
			$this->tpl->setVariable("FORM2", $form_import_text->getHTML());
			$this->tpl->setVariable("FORM3", $form_import_dataset->getHTML());
		}
		return $errors;
	}

	/**
	* Add a precondition for a survey question or question block
	*/
	public function constraintsAddObject()
	{
		if (strlen($_POST["v"]) == 0)
		{
			ilUtil::sendFailure($this->lng->txt("msg_enter_value_for_valid_constraint"));
			return $this->constraintStep3Object();
		}
		$survey_questions =& $this->object->getSurveyQuestions();
		$structure =& $_SESSION["constraintstructure"];
		$include_elements = $_SESSION["includeElements"];
		foreach ($include_elements as $elementCounter)
		{
			if (is_array($structure[$elementCounter]))
			{
				if (strlen($_GET["precondition"]))
				{
					$this->object->updateConstraint($_GET['precondition'], $_POST["q"], $_POST["r"], $_POST["v"], $_POST['c']);
				}
				else
				{
					$constraint_id = $this->object->addConstraint($_POST["q"], $_POST["r"], $_POST["v"], $_POST['c']);
					foreach ($structure[$elementCounter] as $key => $question_id)
					{
						$this->object->addConstraintToQuestion($question_id, $constraint_id);
					}
				}
				if (count($structure[$elementCounter]) > 1)
				{
					$this->object->updateConjunctionForQuestions($structure[$elementCounter], $_POST['c']);
				}
			}
		}
		unset($_SESSION["includeElements"]);
		unset($_SESSION["constraintstructure"]);
		$this->ctrl->redirect($this, "constraints");
	}

	/**
	* Handles the first step of the precondition add action
	*/
	public function constraintStep1Object()
	{
		$survey_questions =& $this->object->getSurveyQuestions();
		$structure =& $_SESSION["constraintstructure"];
		$start = $_GET["start"];
		$option_questions = array();
		for ($i = 1; $i < $start; $i++)
		{
			if (is_array($structure[$i]))
			{
				foreach ($structure[$i] as $key => $question_id)
				{
					if ($survey_questions[$question_id]["usableForPrecondition"])
					{
						array_push($option_questions, array("question_id" => $survey_questions[$question_id]["question_id"], "title" => $survey_questions[$question_id]["title"], "type_tag" => $survey_questions[$question_id]["type_tag"]));
					}
				}
			}
		}
		if (count($option_questions) == 0)
		{
			unset($_SESSION["includeElements"]);
			unset($_SESSION["constraintstructure"]);
			ilUtil::sendInfo($this->lng->txt("constraints_no_nonessay_available"), true);
			$this->ctrl->redirect($this, "constraints");
		}
		$this->constraintForm(1, $_POST, $survey_questions, $option_questions);
	}
	
	/**
	* Handles the second step of the precondition add action
	*/
	public function constraintStep2Object()
	{
		$survey_questions =& $this->object->getSurveyQuestions();
		$option_questions = array();
		array_push($option_questions, array("question_id" => $_POST["q"], "title" => $survey_questions[$_POST["q"]]["title"], "type_tag" => $survey_questions[$_POST["q"]]["type_tag"]));
		$this->constraintForm(2, $_POST, $survey_questions, $option_questions);
	}
	
	/**
	* Handles the third step of the precondition add action
	*/
	public function constraintStep3Object()
	{
		$survey_questions =& $this->object->getSurveyQuestions();
		$option_questions = array();
		if (strlen($_GET["precondition"]))
		{
			$pc = $this->object->getPrecondition($_GET["precondition"]);
			$postvalues = array(
				"c" => $pc["conjunction"],
				"q" => $pc["question_fi"],
				"r" => $pc["relation_id"],
				"v" => $pc["value"]
			);
			array_push($option_questions, array("question_id" => $pc["question_fi"], "title" => $survey_questions[$pc["question_fi"]]["title"], "type_tag" => $survey_questions[$pc["question_fi"]]["type_tag"]));
			$this->constraintForm(3, $postvalues, $survey_questions, $option_questions);
		}
		else
		{
			array_push($option_questions, array("question_id" => $_POST["q"], "title" => $survey_questions[$_POST["q"]]["title"], "type_tag" => $survey_questions[$_POST["q"]]["type_tag"]));
			$this->constraintForm(3, $_POST, $survey_questions, $option_questions);
		}
	}
	
	public function constraintForm($step, $postvalues, &$survey_questions, $questions = FALSE)
	{
		if (strlen($_GET["start"])) $this->ctrl->setParameter($this, "start", $_GET["start"]);
		$this->ctrl->saveParameter($this, "precondition");
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTableWidth("100%");
		$form->setId("constraintsForm");

		$title = "";
		if ($survey_questions[$_SESSION["constraintstructure"][$_GET["start"]][0]]["questionblock_id"] > 0)
		{
			$title = $this->lng->txt("questionblock") . ": " . $survey_questions[$_SESSION["constraintstructure"][$_GET["start"]][0]]["questionblock_title"];
		}
		else
		{
			$title = $this->lng->txt($survey_questions[$_SESSION["constraintstructure"][$_GET["start"]][0]]["type_tag"]) . ": " . $survey_questions[$_SESSION["constraintstructure"][$_GET["start"]][0]]["title"];
		}
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($title);
		$form->addItem($header);
		
		$fulfilled = new ilRadioGroupInputGUI($this->lng->txt("constraint_fulfilled"), "c");
		$fulfilled->addOption(new ilRadioOption($this->lng->txt("conjunction_and"), '0', ''));
		$fulfilled->addOption(new ilRadioOption($this->lng->txt("conjunction_or"), '1', ''));
		$fulfilled->setValue((strlen($postvalues['c'])) ? $postvalues['c'] : 0);
		$form->addItem($fulfilled);

		$step1 = new ilSelectInputGUI($this->lng->txt("step") . " 1: " . $this->lng->txt("select_prior_question"), "q");
		$options = array();
		if (is_array($questions))
		{
			foreach ($questions as $question)
			{
				$options[$question["question_id"]] = $question["title"] . " (" . SurveyQuestion::_getQuestionTypeName($question["type_tag"]) . ")";
			}
		}
		$step1->setOptions($options);
		$step1->setValue($postvalues["q"]);
		$form->addItem($step1);

		if ($step > 1)
		{
			$relations = $this->object->getAllRelations();
			$step2 = new ilSelectInputGUI($this->lng->txt("step") . " 2: " . $this->lng->txt("select_relation"), "r");
			$options = array();
			foreach ($relations as $rel_id => $relation)
			{
				if (in_array($relation["short"], $survey_questions[$postvalues["q"]]["availableRelations"]))
				{
					$options[$rel_id] = $relation['short'];
				}
			}
			$step2->setOptions($options);
			$step2->setValue($postvalues["r"]);
			$form->addItem($step2);
		}
		
		if ($step > 2)
		{
			$variables =& $this->object->getVariables($postvalues["q"]);
			$question_type = $survey_questions[$postvalues["q"]]["type_tag"];
			include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
			SurveyQuestion::_includeClass($question_type);
			$question = new $question_type();
			$question->loadFromDb($postvalues["q"]);

			$step3 = $question->getPreconditionSelectValue($postvalues["v"], $this->lng->txt("step") . " 3: " . $this->lng->txt("select_value"), "v");
			$form->addItem($step3);
		}

		switch ($step)
		{
			case 1:
				$cmd_continue = "constraintStep2";
				$cmd_back = "constraints";
				break;
			case 2:
				$cmd_continue = "constraintStep3";
				$cmd_back = "constraintStep1";
				break;
			case 3:
				$cmd_continue = "constraintsAdd";
				$cmd_back = "constraintStep2";
				break;
		}
		$form->addCommandButton($cmd_back, $this->lng->txt("back"));
		$form->addCommandButton($cmd_continue, $this->lng->txt("continue"));

		$this->tpl->setVariable("ADM_CONTENT", $form->getHTML());
	}

	/**
	* Delete constraints of a survey
	*/
	public function deleteConstraintsObject()
	{
		$survey_questions =& $this->object->getSurveyQuestions();
		$structure =& $_SESSION["constraintstructure"];
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/^constraint_(\d+)_(\d+)/", $key, $matches)) 
			{
				$this->object->deleteConstraint($matches[2]);
			}
		}

		$this->ctrl->redirect($this, "constraints");
	}
	
	function createConstraintsObject()
	{
		$include_elements = $_POST["includeElements"];
		if ((!is_array($include_elements)) || (count($include_elements) == 0))
		{
			ilUtil::sendInfo($this->lng->txt("constraints_no_questions_or_questionblocks_selected"), true);
			$this->ctrl->redirect($this, "constraints");
		}
		else if (count($include_elements) >= 1)
		{
			$_SESSION["includeElements"] = $include_elements;
			sort($include_elements, SORT_NUMERIC);
			$_GET["start"] = $include_elements[0];
			$this->constraintStep1Object();
		}
	}
	
	function editPreconditionObject()
	{
		$_SESSION["includeElements"] = array($_GET["start"]);
		$this->ctrl->setParameter($this, "precondition", $_GET["precondition"]);
		$this->ctrl->setParameter($this, "start", $_GET["start"]);
		$this->ctrl->redirect($this, "constraintStep3");
	}
	
	/**
	* Administration page for survey constraints
	*/
	public function constraintsObject()
	{
		$this->handleWriteAccess();

		global $rbacsystem;
		
		$hasDatasets = $this->object->_hasDatasets($this->object->getSurveyId());
		$step = 0;
		if (array_key_exists("step", $_GET))	$step = $_GET["step"];
		switch ($step)
		{
			case 1:
				$this->constraintStep1Object();
				return;
				break;
			case 2:
				return;
				break;
			case 3:
				return;
				break;
		}
		
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_constraints_list.html", "Modules/Survey");
		$survey_questions =& $this->object->getSurveyQuestions();
		$last_questionblock_title = "";
		$counter = 1;
		$hasPreconditions = FALSE;
		$structure = array();
		$colors = array("tblrow1", "tblrow2");
		foreach ($survey_questions as $question_id => $data)
		{
			$title = $data["title"];
			$show = true;
			if ($data["questionblock_id"] > 0)
			{
				$title = $data["questionblock_title"];
				$type = $this->lng->txt("questionblock");
				if (strcmp($title, $last_questionblock_title) != 0) 
				{
					$last_questionblock_title = $title;
					$structure[$counter] = array();
					array_push($structure[$counter], $data["question_id"]);
				}
				else
				{
					array_push($structure[$counter-1], $data["question_id"]);
					$show = false;
				}
			}
			else
			{
				$structure[$counter] = array($data["question_id"]);
				$type = $this->lng->txt("question");
			}
			if ($show)
			{
				if ($counter == 1)
				{
					$this->tpl->setCurrentBlock("description");
					$this->tpl->setVariable("DESCRIPTION", $this->lng->txt("constraints_first_question_description"));
					$this->tpl->parseCurrentBlock();
				}
				else
				{
					$constraints =& $this->object->getConstraints($data["question_id"]);
					$rowcount = 0;
					if (count($constraints))
					{
						$hasPreconditions = TRUE;
						foreach ($constraints as $constraint)
						{
							$this->tpl->setCurrentBlock("constraint");
							$this->tpl->setVariable("SEQUENCE_ID", $counter);
							$this->tpl->setVariable("CONSTRAINT_ID", $constraint["id"]);
							$this->tpl->setVariable("CONSTRAINT_TEXT", $survey_questions[$constraint["question"]]["title"] . " " . $constraint["short"] . " " . $constraint["valueoutput"]);
							$this->tpl->setVariable("TEXT_EDIT_PRECONDITION", $this->lng->txt("edit"));
							$this->ctrl->setParameter($this, "precondition", $constraint["id"]);
							$this->ctrl->setParameter($this, "start", $counter);
							$this->tpl->setVariable("EDIT_PRECONDITION", $this->ctrl->getLinkTarget($this, "editPrecondition"));
							$this->ctrl->setParameter($this, "precondition", "");
							$this->ctrl->setParameter($this, "start", "");
							$this->tpl->parseCurrentBlock();
						}
						if (count($constraints) > 1)
						{
							$this->tpl->setCurrentBlock("conjunction");
							$this->tpl->setVariable("TEXT_CONJUNCTION", ($constraints[0]['conjunction']) ? $this->lng->txt('conjunction_or_title') : $this->lng->txt('conjunction_and_title'));
							$this->tpl->parseCurrentBlock();
						}
					}
				}
				if ($counter != 1)
				{
					$this->tpl->setCurrentBlock("include_elements");
					$this->tpl->setVariable("QUESTION_NR", "$counter");
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->setCurrentBlock("constraint_section");
				$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
				$this->tpl->setVariable("QUESTION_NR", "$counter");
				$this->tpl->setVariable("TITLE", "$title");
				$icontype = "question.gif";
				if ($data["questionblock_id"] > 0)
				{
					$icontype = "questionblock.gif";
				}
				$this->tpl->setVariable("TYPE", "$type: ");
				include_once "./Services/Utilities/classes/class.ilUtil.php";
				$this->tpl->setVariable("ICON_HREF", ilUtil::getImagePath($icontype, "Modules/Survey"));
				$this->tpl->setVariable("ICON_ALT", $type);
				$this->tpl->parseCurrentBlock();
				$counter++;
			}
		}
		if ($rbacsystem->checkAccess("write", $this->ref_id) and !$hasDatasets)
		{
			if ($hasPreconditions)
			{
				$this->tpl->setCurrentBlock("selectall_preconditions");
				$this->tpl->setVariable("SELECT_ALL_PRECONDITIONS", $this->lng->txt("select_all"));
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("selectall");
			$counter++;
			$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
			$this->tpl->setVariable("COLOR_CLASS", $colors[$counter % 2]);
			$this->tpl->parseCurrentBlock();

			if ($hasPreconditions)
			{
				$this->tpl->setCurrentBlock("delete_button");
				$this->tpl->setVariable("BTN_DELETE", $this->lng->txt("delete"));
				include_once "./Services/Utilities/classes/class.ilUtil.php";
				$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("buttons");
			$this->tpl->setVariable("ARROW", "<img src=\"" . ilUtil::getImagePath("arrow_downright.gif") . "\" alt=\"".$this->lng->txt("arrow_downright")."\">");
			$this->tpl->setVariable("BTN_CREATE_CONSTRAINTS", $this->lng->txt("constraint_add"));
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("CONSTRAINTS_INTRODUCTION", $this->lng->txt("constraints_introduction"));
		$this->tpl->setVariable("DEFINED_PRECONDITIONS", $this->lng->txt("existing_constraints"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "constraints"));
		$this->tpl->setVariable("CONSTRAINTS_HEADER", $this->lng->txt("constraints_list_of_entities"));
		$this->tpl->parseCurrentBlock();
		$_SESSION["constraintstructure"] = $structure;
		if ($hasDatasets)
		{
			ilUtil::sendInfo($this->lng->txt("survey_has_datasets_warning"));
		}
	}

	/**
	* this one is called from the info button in the repository
	* not very nice to set cmdClass/Cmd manually, if everything
	* works through ilCtrl in the future this may be changed
	*/
	function infoScreenObject()
	{
		$this->ctrl->setCmd("showSummary");
		$this->ctrl->setCmdClass("ilinfoscreengui");
		$this->infoScreen();
	}
	
	function setNewTemplate()
	{
		global $tpl;
		$tpl = new ilTemplate("tpl.il_svy_svy_main.html", TRUE, TRUE, "Modules/Survey");
		// load style sheet depending on user's settings
		$location_stylesheet = ilUtil::getStyleSheetLocation();
		$tpl->setVariable("LOCATION_STYLESHEET",$location_stylesheet);
		$tpl->setVariable("LOCATION_JAVASCRIPT",dirname($location_stylesheet));
	}
	
	/**
	* show information screen
	*/
	function infoScreen()
	{
		global $ilAccess;
		global $ilUser;

		if (!$ilAccess->checkAccess("visible", "", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);
		include_once "./Modules/Survey/classes/class.ilSurveyExecutionGUI.php";
		$output_gui =& new ilSurveyExecutionGUI($this->object);
		$info->setFormAction($this->ctrl->getFormAction($output_gui, "infoScreen"));
		$info->enablePrivateNotes();
		$anonymize_key = NULL;
		if ($this->object->getAnonymize() == 1)
		{
			if ($_SESSION["anonymous_id"])
			{
				$anonymize_key = $_SESSION["anonymous_id"];
			}
			else if ($_POST["anonymous_id"])
			{
				$anonymize_key = $_POST["anonymous_id"];
			}
		}
		$canStart = $this->object->canStartSurvey($anonymize_key);
		$showButtons = $canStart["result"];
		if (!$showButtons) ilUtil::sendInfo(implode("<br />", $canStart["messages"]));

		if ($showButtons)
		{
			// output of start/resume buttons for personalized surveys
			if (!$this->object->getAnonymize())
			{
				$survey_started = $this->object->isSurveyStarted($ilUser->getId(), "");
				// Anonymous User tries to start a personalized survey
				if ($_SESSION["AccountId"] == ANONYMOUS_USER_ID)
				{
					ilUtil::sendInfo($this->lng->txt("anonymous_with_personalized_survey"));
				}
				else
				{
					if ($survey_started === 1)
					{
						ilUtil::sendInfo($this->lng->txt("already_completed_survey"));
					}
					elseif ($survey_started === 0)
					{
						$info->addFormButton("resume", $this->lng->txt("resume_survey"));
					}
					elseif ($survey_started === FALSE)
					{
						$info->addFormButton("start", $this->lng->txt("start_survey"));
					}
				}
			}
			// output of start/resume buttons for anonymized surveys
			else if ($this->object->getAnonymize() && !$this->object->isAccessibleWithoutCode())
			{
				if (($_SESSION["AccountId"] == ANONYMOUS_USER_ID) && (strlen($_POST["anonymous_id"]) == 0) && (strlen($_SESSION["anonymous_id"]) == 0))
				{
					$info->setFormAction($this->ctrl->getFormAction($this, "infoScreen"));
					$info->addSection($this->lng->txt("anonymization"));
					$info->addProperty("", $this->lng->txt("anonymize_anonymous_introduction"));
					$info->addPropertyTextinput($this->lng->txt("enter_anonymous_id"), "anonymous_id", "", 8, "infoScreen", $this->lng->txt("submit"));
				}
				else
				{
					if (strlen($_POST["anonymous_id"]) > 0)
					{
						if (!$this->object->checkSurveyCode($_POST["anonymous_id"]))
						{
							ilUtil::sendInfo($this->lng->txt("wrong_survey_code_used"));
						}
						else
						{
							$anonymize_key = $_POST["anonymous_id"];
						}
					}
					else if (strlen($_SESSION["anonymous_id"]) > 0)
					{
						if (!$this->object->checkSurveyCode($_SESSION["anonymous_id"]))
						{
							ilUtil::sendInfo($this->lng->txt("wrong_survey_code_used"));
						}
						else
						{
							$anonymize_key = $_SESSION["anonymous_id"];
						}
					}
					else
					{
						// registered users do not need to know that there is an anonymous key. The data is anonymized automatically
						$anonymize_key = $this->object->getUserAccessCode($ilUser->getId());
						if (!strlen($anonymize_key))
						{
							$anonymize_key = $this->object->createNewAccessCode();
							$this->object->saveUserAccessCode($ilUser->getId(), $anonymize_key);
						}
					}
					$info->addHiddenElement("anonymous_id", $anonymize_key);
					$survey_started = $this->object->isSurveyStarted($ilUser->getId(), $anonymize_key);
					if ($survey_started === 1)
					{
						ilUtil::sendInfo($this->lng->txt("already_completed_survey"));
					}
					elseif ($survey_started === 0)
					{
						$info->addFormButton("resume", $this->lng->txt("resume_survey"));
					}
					elseif ($survey_started === FALSE)
					{
						$info->addFormButton("start", $this->lng->txt("start_survey"));
					}
				}
			}
			else
			{
				// free access
				$survey_started = $this->object->isSurveyStarted($ilUser->getId(), "");
				if ($survey_started === 1)
				{
					ilUtil::sendInfo($this->lng->txt("already_completed_survey"));
				}
				elseif ($survey_started === 0)
				{
					$info->addFormButton("resume", $this->lng->txt("resume_survey"));
				}
				elseif ($survey_started === FALSE)
				{
					$info->addFormButton("start", $this->lng->txt("start_survey"));
				}
			}
		}
		
		if (strlen($this->object->getIntroduction()))
		{
			$introduction = $this->object->getIntroduction();
			$info->addSection($this->lng->txt("introduction"));
			$info->addProperty("", $this->object->prepareTextareaOutput($introduction));
		}
		
		$info->addSection($this->lng->txt("svy_general_properties"));
		if (strlen($this->object->getAuthor()))
		{
			$info->addProperty($this->lng->txt("author"), $this->object->getAuthor());
		}
		$info->addProperty($this->lng->txt("title"), $this->object->getTitle());
		switch ($this->object->getAnonymize())
		{
			case ANONYMIZE_OFF:
				$info->addProperty($this->lng->txt("anonymization"), $this->lng->txt("anonymize_personalized"));
				break;
			case ANONYMIZE_ON:
				if ($_SESSION["AccountId"] == ANONYMOUS_USER_ID)
				{
					$info->addProperty($this->lng->txt("anonymization"), $this->lng->txt("info_anonymize_with_code"));
				}
				else
				{
					$info->addProperty($this->lng->txt("anonymization"), $this->lng->txt("info_anonymize_registered_user"));
				}
				break;
			case ANONYMIZE_FREEACCESS:
				$info->addProperty($this->lng->txt("anonymization"), $this->lng->txt("info_anonymize_without_code"));
				break;
		}
		include_once "./Modules/Survey/classes/class.ilObjSurveyAccess.php";
		if ($ilAccess->checkAccess("write", "", $this->ref_id) || ilObjSurveyAccess::_hasEvaluationAccess($this->object->getId(), $ilUser->getId()))
		{
			$info->addProperty($this->lng->txt("evaluation_access"), $this->lng->txt("evaluation_access_info"));
		}
		$info->addMetaDataSections($this->object->getId(),0, $this->object->getType());
		$this->ctrl->forwardCommand($info);
	}

	/**
	* Creates a print view of the survey questions
	*
	* @access public
	*/
	function printViewObject()
	{
		global $ilias;
		
		$this->questionsSubtabs("printview");
		$template = new ilTemplate("tpl.il_svy_svy_printview.html", TRUE, TRUE, "Modules/Survey");
			
		include_once './Services/WebServices/RPC/classes/class.ilRPCServerSettings.php';
		if(ilRPCServerSettings::getInstance()->isEnabled())
		{
			$this->ctrl->setParameter($this, "pdf", "1");
			$template->setCurrentBlock("pdf_export");
			$template->setVariable("PDF_URL", $this->ctrl->getLinkTarget($this, "printView"));
			$this->ctrl->setParameter($this, "pdf", "");
			$template->setVariable("PDF_TEXT", $this->lng->txt("pdf_export"));
			$template->setVariable("PDF_IMG_ALT", $this->lng->txt("pdf_export"));
			$template->setVariable("PDF_IMG_URL", ilUtil::getHtmlPath(ilUtil::getImagePath("application-pdf.png")));
			$template->parseCurrentBlock();
		}
		$template->setVariable("PRINT_TEXT", $this->lng->txt("print"));
		$template->setVariable("PRINT_URL", "javascript:window.print();");

		$pages =& $this->object->getSurveyPages();
		foreach ($pages as $page)
		{
			if (count($page) > 0)
			{
				foreach ($page as $question)
				{
					$questionGUI = $this->object->getQuestionGUI($question["type_tag"], $question["question_id"]);
					if (is_object($questionGUI))
					{
						if (strlen($question["heading"]))
						{
							$template->setCurrentBlock("textblock");
							$template->setVariable("TEXTBLOCK", $question["heading"]);
							$template->parseCurrentBlock();
						}
						$template->setCurrentBlock("question");
						$template->setVariable("QUESTION_DATA", $questionGUI->getPrintView($this->object->getShowQuestionTitles(), $question["questionblock_show_questiontext"], $this->object->getSurveyId()));
						$template->parseCurrentBlock();
					}
				}
				if (count($page) > 1)
				{
					$template->setCurrentBlock("page");
					$template->setVariable("BLOCKTITLE", $page[0]["questionblock_title"]);
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock("page");
					$template->parseCurrentBlock();
				}
			}
		}
		$this->tpl->addCss("./Modules/Survey/templates/default/survey_print.css", "print");
		if (array_key_exists("pdf", $_GET) && ($_GET["pdf"] == 1))
		{
			$printbody = new ilTemplate("tpl.il_as_tst_print_body.html", TRUE, TRUE, "Modules/Test");
			$printbody->setVariable("TITLE", sprintf($this->lng->txt("tst_result_user_name"), $uname));
			$printbody->setVariable("ADM_CONTENT", $template->get());
			$printoutput = $printbody->get();
			$printoutput = preg_replace("/href=\".*?\"/", "", $printoutput);
			$fo = $this->object->processPrintoutput2FO($printoutput);
			$this->object->deliverPDFfromFO($fo);
		}
		else
		{
			$this->tpl->setVariable("ADM_CONTENT", $template->get());
		}
	}
	
	function addLocatorItems()
	{
		global $ilLocator;
		switch ($this->ctrl->getCmd())
		{
			case "next":
			case "previous":
			case "start":
			case "resume":
			case "redirectQuestion":
				$ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTarget($this, "infoScreen"), "", $_GET["ref_id"]);
				break;
			case "evaluation":
			case "checkEvaluationAccess":
			case "evaluationdetails":
			case "evaluationuser":
				$ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTargetByClass("ilsurveyevaluationgui", "evaluation"), "", $_GET["ref_id"]);
				break;
			case "create":
			case "save":
			case "cancel":
			case "importFile":
			case "cloneAll":
				break;
			case "infoScreen":
				$ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTarget($this, "infoScreen"), "", $_GET["ref_id"]);
				break;
		default:
				$ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTarget($this, ""), "", $_GET["ref_id"]);
				break;
		}
	}
	
	/**
	* Set the subtabs for the questions tab
	*
	* Set the subtabs for the questions tab
	*
	* @access private
	*/
	function questionsSubtabs($a_cmd)
	{
		$questions = ($a_cmd == 'questions') ? true : false;
		$printview = ($a_cmd == 'printview') ? true : false;

		$this->tabs_gui->addSubTabTarget("survey_question_editor", $this->ctrl->getLinkTarget($this, "questions"),
										 "", "", "", $questions);
		$this->tabs_gui->addSubTabTarget("print_view", $this->ctrl->getLinkTarget($this, "printView"),
											"", "", "", $printview);
	}
	
	/**
	* Set the tabs for the access codes section
	*
	* @access private
	*/
	function setCodesSubtabs()
	{
		global $ilTabs;
		global $ilAccess;

		$ilTabs->addSubTabTarget
		(
			"codes", 
			$this->ctrl->getLinkTarget($this,'codes'),
			array("codes", "createSurveyCodes", "setCodeLanguage", "deleteCodes", "exportCodes"),
			""
		);

		$ilTabs->addSubTabTarget
		(
			"mail", 
			$this->ctrl->getLinkTarget($this, "codesMail"), 
			array("codesMail", "saveMailTableFields", "importExternalMailRecipients", 'mailCodes', 
			'sendCodesMail', 'importExternalRecipientsFromFile', 'importExternalRecipientsFromText',
			'importExternalRecipientsFromDataset', 'insertSavedMessage', 'deleteSavedMessage'),	
			""
		);
	}

	/**
	* Set the tabs for the evaluation output
	*
	* @access private
	*/
	function setEvalSubtabs()
	{
		global $ilTabs;
		global $ilAccess;

		$ilTabs->addSubTabTarget(
			"svy_eval_cumulated", 
			$this->ctrl->getLinkTargetByClass("ilsurveyevaluationgui", "evaluation"), 
			array("evaluation", "checkEvaluationAccess"),	
			""
		);

		$ilTabs->addSubTabTarget(
			"svy_eval_detail", 
			$this->ctrl->getLinkTargetByClass("ilsurveyevaluationgui", "evaluationdetails"), 
			array("evaluationdetails"),	
			""
		);
		
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addSubTabTarget(
				"svy_eval_user", 
				$this->ctrl->getLinkTargetByClass("ilsurveyevaluationgui", "evaluationuser"), 
				array("evaluationuser"),	
				""
			);
		}
	}

	function setBrowseForQuestionsSubtabs()
	{
		global $ilAccess;
		global $ilTabs;
		
		if ($ilAccess->checkAccess("write", "", $this->ref_id))
		{
			$ilTabs->setBackTarget($this->lng->txt("menubacktosurvey"), $this->ctrl->getLinkTarget($this, "questions"));
			$ilTabs->addTarget("browse_for_questions",
				$this->ctrl->getLinkTarget($this, "browseForQuestions"),
				 array("browseForQuestions", "browseForQuestionblocks"),
				"", ""
			);
		}
	}

	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		global $ilAccess, $ilUser;
		
		if (strcmp($this->ctrl->getNextClass(), 'ilrepositorysearchgui') != 0)
		{
			switch ($this->ctrl->getCmd())
			{
				case "browseForQuestions":
				case "browseForQuestionblocks":
				case "insertQuestions":
				case "filterQuestions":
				case "resetFilterQuestions":
				case "changeDatatype":

				case "start":
				case "resume":
				case "next":
				case "previous":
				case "redirectQuestion":
					return;
					break;
				case "evaluation":
				case "checkEvaluationAccess":
				case "evaluationdetails":
				case "evaluationuser":
					$this->setEvalSubtabs();
					break;
			}
		}
		
		// questions
		if ($ilAccess->checkAccess("write", "", $this->ref_id))
		{
			$force_active = ($_GET["up"] != "" || $_GET["down"] != "")
				? true
				: false;
	
			$tabs_gui->addTarget("survey_questions",
				 $this->ctrl->getLinkTarget($this,'questions'),
				 array("questions", "browseForQuestions", "createQuestion",
				 "filterQuestions", "resetFilterQuestions", "changeDatatype", "insertQuestions",
				 "removeQuestions", "cancelRemoveQuestions", "confirmRemoveQuestions",
				 "defineQuestionblock", "saveDefineQuestionblock", "cancelDefineQuestionblock",
				 "unfoldQuestionblock", "moveQuestions",
				 "insertQuestionsBefore", "insertQuestionsAfter", "saveObligatory",
				 "addHeading", "saveHeading", "cancelHeading", "editHeading",
				 "confirmRemoveHeading", "cancelRemoveHeading", "printView"),
				 "", "", $force_active);
		}
		
		if ($ilAccess->checkAccess("visible", "", $this->ref_id))
		{
			$tabs_gui->addTarget("info_short",
				 $this->ctrl->getLinkTarget($this,'infoScreen'),
				 array("infoScreen", "showSummary"));
		}
			
		// properties
		if ($ilAccess->checkAccess("write", "", $this->ref_id))
		{
			$force_active = ($this->ctrl->getCmd() == "")
				? true
				: false;
			$tabs_gui->addTarget("settings",
				 $this->ctrl->getLinkTarget($this,'properties'),
				 array("properties", "save", "cancel", 'saveProperties'), "",
				 "", $force_active);
		}

		// questions
		if ($ilAccess->checkAccess("write", "", $this->ref_id))
		{
			// constraints
			$tabs_gui->addTarget("constraints",
				 $this->ctrl->getLinkTarget($this, "constraints"),
				 array("constraints", "constraintStep1", "constraintStep2",
				 "constraintStep3", "constraintsAdd", "createConstraints",
				"editPrecondition"),
				 "");
		}
		
		if (($ilAccess->checkAccess("write", "", $this->ref_id)) || ($ilAccess->checkAccess("invite", "", $this->ref_id)))
		{
			// invite
			$tabs_gui->addTarget("invitation",
				 $this->ctrl->getLinkTarget($this, 'invite'),
				 array("invite", "saveInvitationStatus",
				 "inviteUserGroup", "disinviteUserGroup"),
				 "");
		}
		
		if ($ilAccess->checkAccess("write", "", $this->ref_id))
		{
			// maintenance
			$tabs_gui->addTarget("maintenance",
				 $this->ctrl->getLinkTarget($this,'maintenance'),
				 array("maintenance", "deleteAllUserData"),
				 "");

			if ($this->object->getAnonymize() == 1)
			{
				// code
				$tabs_gui->addTarget("codes",
					 $this->ctrl->getLinkTarget($this,'codes'),
					 array("codes", "exportCodes", 'codesMail', 'saveMailTableFields', 'importExternalMailRecipients',
						'mailCodes', 'sendCodesMail', 'importExternalRecipientsFromFile', 'importExternalRecipientsFromText',
						'importExternalRecipientsFromDataset', 'insertSavedMessage', 'deleteSavedMessage'),
					 "");
			}
		}
			
		include_once "./Modules/Survey/classes/class.ilObjSurveyAccess.php";
		if ($ilAccess->checkAccess("write", "", $this->ref_id) || ilObjSurveyAccess::_hasEvaluationAccess($this->object->getId(), $ilUser->getId()))
		{
			// evaluation
			$tabs_gui->addTarget("svy_evaluation",
				 $this->ctrl->getLinkTargetByClass("ilsurveyevaluationgui", "evaluation"),
				 array("evaluation", "checkEvaluationAccess", "evaluationdetails",
				 	"evaluationuser"),
				 "");
		}

		if ($ilAccess->checkAccess("write", "", $this->ref_id))
		{
			// meta data
			$tabs_gui->addTarget("meta_data",
				 $this->ctrl->getLinkTargetByClass('ilmdeditorgui','listSection'),
				 "", "ilmdeditorgui");

			// export
			$tabs_gui->addTarget("export",
				 $this->ctrl->getLinkTarget($this,'export'),
				 array("export", "createExportFile", "confirmDeleteExportFile",
				 "downloadExportFile"), 
				 ""
				);
		}

		if ($ilAccess->checkAccess("edit_permission", "", $this->ref_id))
		{
			// permissions
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), array("perm","info","owner"), 'ilpermissiongui');
		}
	}
	
	/**
	* redirect script
	*
	* @param	string		$a_target
	*/
	function _goto($a_target, $a_access_code = "")
	{
		global $ilAccess, $ilErr, $lng;
		if ($ilAccess->checkAccess("read", "", $a_target))
		{
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			if (strlen($a_access_code))
			{
				$_SESSION["anonymous_id"] = $a_access_code;
				$_GET["baseClass"] = "ilObjSurveyGUI";
				$_GET["cmd"] = "infoScreen";
				$_GET["ref_id"] = $a_target;
				include("ilias.php");
				exit;
			}
			else
			{
				$_GET["baseClass"] = "ilObjSurveyGUI";
				$_GET["cmd"] = "infoScreen";
				$_GET["ref_id"] = $a_target;
				include("ilias.php");
				exit;
			}
		}
		else if ($ilAccess->checkAccess("read", "", ROOT_FOLDER_ID))
		{
			$_GET["cmd"] = "frameset";
			$_GET["target"] = "";
			$_GET["ref_id"] = ROOT_FOLDER_ID;
			ilUtil::sendFailure(sprintf($lng->txt("msg_no_perm_read_item"),
				ilObject::_lookupTitle(ilObject::_lookupObjId($a_target))), true);
			include("repository.php");
			exit;
		}

		$ilErr->raiseError($lng->txt("msg_no_perm_read_lm"), $ilErr->FATAL);
	}

} // END class.ilObjSurveyGUI
?>
