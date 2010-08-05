<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "classes/class.ilObjectGUI.php";
include_once "./Modules/LearningModule/classes/class.ilObjContentObject.php";
include_once ("./Modules/LearningModule/classes/class.ilLMPageObjectGUI.php");
include_once ("./Modules/LearningModule/classes/class.ilStructureObjectGUI.php");
require_once 'Services/LinkChecker/interfaces/interface.ilLinkCheckerGUIRowHandling.php';

/**
 * Class ilObjContentObjectGUI
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @author Stefan Meyer <meyer@leifos.com>
 * @author Sascha Hofmann <saschahofmann@gmx.de>
 *
 * $Id$
 *
 * @ingroup ModulesIliasLearningModule
 */
class ilObjContentObjectGUI extends ilObjectGUI implements ilLinkCheckerGUIRowHandling
{
	var $ctrl;

	/**
	* Constructor
	*
	* @access	public
	*/
	function ilObjContentObjectGUI($a_data,$a_id = 0,$a_call_by_reference = true, $a_prepare_output = false)
	{
		global $lng, $ilCtrl;
//echo "<br>ilobjcontobjgui-constructor-id-$a_id";
		$this->ctrl =& $ilCtrl;
		$lng->loadLanguageModule("content");
		parent::ilObjectGUI($a_data,$a_id,$a_call_by_reference,false);
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilAccess, $lng;
		
		if ($this->ctrl->getRedirectSource() == "ilinternallinkgui")
		{
			$this->explorer();
			return;
		}

		if ($this->ctrl->getCmdClass() == "ilinternallinkgui")
		{
			$this->ctrl->setReturn($this, "explorer");
		}

		// get next class that processes or forwards current command
		$next_class = $this->ctrl->getNextClass($this);

		// get current command
		$cmd = $this->ctrl->getCmd("", array("downloadExportFile"));
//echo "-$cmd-";
		switch($next_class)
		{
			case "illearningprogressgui":
				$this->addLocations();
				include_once './Services/Tracking/classes/class.ilLearningProgressGUI.php';
				$this->setTabs();

				$new_gui =& new ilLearningProgressGUI(LP_MODE_REPOSITORY,$this->object->getRefId());
				$new_gui->activateStatistics();
				$this->ctrl->forwardCommand($new_gui);

				break;

			case 'ilmdeditorgui':
				$this->addLocations();
				include_once 'Services/MetaData/classes/class.ilMDEditorGUI.php';
				$this->setTabs();
				$md_gui =& new ilMDEditorGUI($this->object->getId(), 0, $this->object->getType());
				$md_gui->addObserver($this->object,'MDUpdateListener','General');

				$this->ctrl->forwardCommand($md_gui);
				break;

			case "ilobjstylesheetgui":
				$this->addLocations();
				include_once ("./Services/Style/classes/class.ilObjStyleSheetGUI.php");
				$this->ctrl->setReturn($this, "editStyleProperties");
				$style_gui =& new ilObjStyleSheetGUI("", $this->object->getStyleSheetId(), false, false);
				$style_gui->omitLocator();
				if ($cmd == "create" || $_GET["new_type"]=="sty")
				{
					$style_gui->setCreationMode(true);
				}
				$ret =& $this->ctrl->forwardCommand($style_gui);
				//$ret =& $style_gui->executeCommand();

				if ($cmd == "save" || $cmd == "copyStyle" || $cmd == "importStyle")
				{
					$style_id = $ret;
					$this->object->setStyleSheetId($style_id);
					$this->object->update();
					$this->ctrl->redirectByClass("ilobjstylesheetgui", "edit");
				}
				break;

			case "illmpageobjectgui":
				$this->ctrl->saveParameter($this, array("obj_id"));
				$this->addLocations();
				$this->ctrl->setReturn($this, "chapters");
//echo "!";
				//$this->lm_obj =& $this->ilias->obj_factory->getInstanceByRefId($this->ref_id);

				$pg_gui =& new ilLMPageObjectGUI($this->object);
				if ($_GET["obj_id"] != "")
				{
					$obj =& ilLMObjectFactory::getInstance($this->object, $_GET["obj_id"]);
					$pg_gui->setLMPageObject($obj);
				}
				//$ret =& $pg_gui->executeCommand();
				$ret =& $this->ctrl->forwardCommand($pg_gui);
				if ($cmd == "save" || $cmd == "cancel")
				{
					$this->ctrl->redirect($this, "pages");
				}
				break;

			case "ilstructureobjectgui":
				$this->ctrl->saveParameter($this, array("obj_id"));
				$this->addLocations();
				$this->ctrl->setReturn($this, "chapters");
				$st_gui =& new ilStructureObjectGUI($this->object, $this->object->lm_tree);
				if ($_GET["obj_id"] != "")
				{
					$obj =& ilLMObjectFactory::getInstance($this->object, $_GET["obj_id"]);
					$st_gui->setStructureObject($obj);
				}
				//$ret =& $st_gui->executeCommand();
				$ret =& $this->ctrl->forwardCommand($st_gui);
				if ($cmd == "save" || $cmd == "cancel")
				{
					if ($_GET["obj_id"] == "")
					{
						$this->ctrl->redirect($this, "chapters");
					}
					else
					{
						$this->ctrl->setCmd("subchap");
						$this->executeCommand();
					}
				}
				break;

			case 'ilpermissiongui':
				if (strtolower($_GET["baseClass"]) == "iladministrationgui")
				{
					$this->prepareOutput();
				}
				else
				{
					$this->addLocations(true);
					$this->setTabs();
				}
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			// infoscreen
			case 'ilinfoscreengui':
				$this->addLocations(true);
				$this->setTabs();
				include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
				$info = new ilInfoScreenGUI($this);
				$info->enablePrivateNotes();
				$info->enableLearningProgress();
		
				$info->enableNews();
				if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
				{
					$info->enableNewsEditing();
					$info->setBlockProperty("news", "settings", true);
				}
				
				// show standard meta data section
				$info->addMetaDataSections($this->object->getId(), 0,
					$this->object->getType());
		
				$ret =& $this->ctrl->forwardCommand($info);
				break;
			
			case "ilexportgui":
				$this->addLocations(true);
				$this->setTabs();
				include_once("./Services/Export/classes/class.ilExportGUI.php");
				$exp_gui = new ilExportGUI($this);
				$exp_gui->addFormat("xml", "", $this, "export");
				$exp_gui->addFormat("html", "", $this, "exportHTML");
				$exp_gui->addFormat("scorm", "", $this, "exportSCORM");
				$exp_gui->addCustomColumn($lng->txt("cont_public_access"),
						$this, "getPublicAccessColValue");
				$exp_gui->addCustomMultiCommand($lng->txt("cont_public_access"),
						$this, "publishExportFile");
				$ret = $this->ctrl->forwardCommand($exp_gui);
				break;


			default:
				$new_type = $_POST["new_type"]
					? $_POST["new_type"]
					: $_GET["new_type"];


				if ($cmd == "create" &&
					!in_array($new_type, array("dbk", "lm")))
				{
					//$this->addLocations();
					switch ($new_type)
					{
						case "pg":
							$this->setTabs();
							$this->ctrl->setCmdClass("ilLMPageObjectGUI");
							$ret =& $this->executeCommand();
							break;

						case "st":
							$this->setTabs();
							$this->ctrl->setCmdClass("ilStructureObjectGUI");
							$ret =& $this->executeCommand();
							break;
					}
				}
				else
				{
					// creation of new dbk/lm in repository
					if ($this->getCreationMode() == true &&
						in_array($new_type, array("dbk", "lm")))
					{
						$this->prepareOutput();
						if ($cmd == "")			// this may be due to too big upload files
						{
							$cmd = "create";
						}
						$cmd .= "Object";
						$ret =& $this->$cmd();
					}
					else
					{
						$this->addLocations();
						$ret =& $this->$cmd();
					}
				}
				break;
		}
		return $ret;
	}

	function _forwards()
	{
		return array("ilLMPageObjectGUI", "ilStructureObjectGUI","ilObjStyleSheetGUI");
	}

	/**
	* edit properties form
	*/
	function properties()
	{
		global $lng;

		$lng->loadLanguageModule("style");
		$this->setTabs();
		$this->setSubTabs("cont_general_properties");

		//$showViewInFrameset = $this->ilias->ini->readVariable("layout","view_target") == "frame";
		$showViewInFrameset = true;

		if ($showViewInFrameset)
		{
			$buttonTarget = ilFrameTargetInfo::_getFrame("MainContent");
		}
		else
		{
			$buttonTarget = "ilContObj".$this->object->getID();
		}

		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// view button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", "ilias.php?baseClass=ilLMPresentationGUI&ref_id=".$this->object->getRefID());
		$this->tpl->setVariable("BTN_TARGET"," target=\"".$buttonTarget."\" ");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("view"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "fixTreeConfirm"));
		//$this->tpl->setVariable("BTN_TARGET"," target=\"_top\" ");
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_fix_tree"));
		$this->tpl->parseCurrentBlock();

		//$this->tpl->touchBlock("btn_row");

		// lm properties
		$this->initPropertiesForm();
		$this->getPropertiesFormValues();
		$this->tpl->setContent($this->form->getHTML());
	}
	
	/**
	* Init properties form
	*/
	function initPropertiesForm()
	{
		global $ilCtrl, $lng;
		
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		
		// title
		$ti = new ilTextInputGUI($lng->txt("title"), "title");
		//$ti->setMaxLength();
		//$ti->setSize();
		//$ti->setInfo($lng->txt(""));
		$ti->setRequired(true);
		$this->form->addItem($ti);
		
		// description
		$ta = new ilTextAreaInputGUI($lng->txt("desc"), "description");
		//$ta->setCols();
		//$ta->setRows();
		//$ta->setInfo($lng->txt(""));
		$this->form->addItem($ta);
		
		// online
		$online = new ilCheckboxInputGUI($lng->txt("cont_online"), "cobj_online");
		$this->form->addItem($online);
		
		// default layout
		$layout = new ilRadioMatrixInputGUI($lng->txt("cont_def_layout"), "lm_layout");
		$option = array();
		foreach(ilObjContentObject::getAvailableLayouts() as $l)
		{
			$im_tag = "";
			if (is_file($im = ilUtil::getImagePath("layout_".$l.".gif")))
			{
				$im_tag = ilUtil::img($im, $l);
			}
			$option[$l] = "<table><tr><td>".$im_tag."</td><td><b>".$lng->txt("cont_layout_".$l)."</b>: ".$lng->txt("cont_layout_".$l."_desc")."</td></tr></table>";
		}
		$layout->setOptions($option);
		$this->form->addItem($layout);
		
		// page header
		$page_header = new ilSelectInputGUI($lng->txt("cont_page_header"), "lm_pg_header");
		$option = array ("st_title" => $this->lng->txt("cont_st_title"),
			"pg_title" => $this->lng->txt("cont_pg_title"),
			"none" => $this->lng->txt("cont_none"));
		$page_header->setOptions($option);
		$this->form->addItem($page_header);
		
		// chapter numeration
		$chap_num = new ilCheckboxInputGUI($lng->txt("cont_act_number"), "cobj_act_number");
		$this->form->addItem($chap_num);

		// toc mode
		$toc_mode = new ilSelectInputGUI($lng->txt("cont_toc_mode"), "toc_mode");
		$option = array ("chapters" => $this->lng->txt("cont_chapters_only"),
			"pages" => $this->lng->txt("cont_chapters_and_pages"));
		$toc_mode->setOptions($option);
		$this->form->addItem($toc_mode);
		
		// public notes
		if (!$this->ilias->getSetting('disable_notes'))
		{
			$pub_nodes = new ilCheckboxInputGUI($lng->txt("cont_public_notes"), "cobj_pub_notes");
			$pub_nodes->setInfo($this->lng->txt("cont_public_notes_desc"));
			$this->form->addItem($pub_nodes);
		}

		// layout per page
		$lpp = new ilCheckboxInputGUI($lng->txt("cont_layout_per_page"), "layout_per_page");
		$lpp->setInfo($this->lng->txt("cont_layout_per_page_info"));
		$this->form->addItem($lpp);

		// synchronize frames
		$synch = new ilCheckboxInputGUI($lng->txt("cont_synchronize_frames"), "cobj_clean_frames");
		$synch->setInfo($this->lng->txt("cont_synchronize_frames_desc"));
		$this->form->addItem($synch);
		
		// history user comments
		$com = new ilCheckboxInputGUI($lng->txt("enable_hist_user_comments"), "cobj_user_comments");
		$com->setInfo($this->lng->txt("enable_hist_user_comments_desc"));
		$this->form->addItem($com);

		$this->form->setTitle($lng->txt("cont_general_properties"));
		$this->form->addCommandButton("saveProperties", $lng->txt("save"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}

	/**
	* Get values for properties form
	*/
	function getPropertiesFormValues()
	{
		$values = array();
		$values["title"] = $this->object->getTitle();
		$values["description"] = $this->object->getDescription();
		if ($this->object->getOnline())
		{
			$values["cobj_online"] = true;
		}
		$values["lm_layout"] = $this->object->getLayout();
		$values["lm_pg_header"] = $this->object->getPageHeader();
		if ($this->object->isActiveNumbering())
		{
			$values["cobj_act_number"] = true;
		}
		$values["toc_mode"] = $this->object->getTOCMode();
		if ($this->object->publicNotes())
		{
			$values["cobj_pub_notes"] = true;
		}
		if ($this->object->cleanFrames())
		{
			$values["cobj_clean_frames"] = true;
		}
		if ($this->object->isActiveHistoryUserComments())
		{
			$values["cobj_user_comments"] = true;
		}
		$values["layout_per_page"] = $this->object->getLayoutPerPage();
		
		$this->form->setValuesByArray($values);
	}
	
	/**
	* save properties
	*/
	function saveProperties()
	{
		global $ilias;

		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$this->object->setTitle($_POST['title']);
			$this->object->setDescription($_POST['description']);
			$this->object->setLayout($_POST["lm_layout"]);
			$this->object->setPageHeader($_POST["lm_pg_header"]);
			$this->object->setTOCMode($_POST["toc_mode"]);
			$this->object->setOnline($_POST["cobj_online"]);
			$this->object->setActiveNumbering($_POST["cobj_act_number"]);
			$this->object->setCleanFrames($_POST["cobj_clean_frames"]);
			$this->object->setPublicNotes($_POST["cobj_pub_notes"]);
			$this->object->setHistoryUserComments($_POST["cobj_user_comments"]);
			$this->object->setLayoutPerPage($_POST["layout_per_page"]);
			$this->object->updateProperties();
			$this->object->update();
			ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
			$this->ctrl->redirect($this, "properties");
		}
		else
		{
			$this->form->setValuesByPost();
			$tpl->setContent($this->form->getHTML());
		}
	}

	/**
	* Edit style properties
	*/
	function editStyleProperties()
	{
		global $tpl;
		
		$this->initStylePropertiesForm();
		$tpl->setContent($this->form->getHTML());
	}
	
	/**
	* Init style properties form
	*/
	function initStylePropertiesForm()
	{
		global $ilCtrl, $lng, $ilTabs, $ilSetting;
		
		$lng->loadLanguageModule("style");
		$this->setTabs();
		$ilTabs->setTabActive("settings");
		$this->setSubTabs("cont_style");

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		
		$fixed_style = $ilSetting->get("fixed_content_style_id");
		$style_id = $this->object->getStyleSheetId();

		if ($fixed_style > 0)
		{
			$st = new ilNonEditableValueGUI($lng->txt("cont_current_style"));
			$st->setValue(ilObject::_lookupTitle($fixed_style)." (".
				$this->lng->txt("global_fixed").")");
			$this->form->addItem($st);
		}
		else
		{
			$st_styles = ilObjStyleSheet::_getStandardStyles(true, false,
				$_GET["ref_id"]);

			$st_styles[0] = $this->lng->txt("default");
			ksort($st_styles);

			if ($style_id > 0)
			{
				// individual style
				if (!ilObjStyleSheet::_lookupStandard($style_id))
				{
					$st = new ilNonEditableValueGUI($lng->txt("cont_current_style"));
					$st->setValue(ilObject::_lookupTitle($style_id));
					$this->form->addItem($st);

//$this->ctrl->getLinkTargetByClass("ilObjStyleSheetGUI", "edit"));

					// delete command
					$this->form->addCommandButton("editStyle",
						$lng->txt("cont_edit_style"));
					$this->form->addCommandButton("deleteStyle",
						$lng->txt("cont_delete_style"));
//$this->ctrl->getLinkTargetByClass("ilObjStyleSheetGUI", "delete"));
				}
			}

			if ($style_id <= 0 || ilObjStyleSheet::_lookupStandard($style_id))
			{
				$style_sel = ilUtil::formSelect ($style_id, "style_id",
					$st_styles, false, true);
				$style_sel = new ilSelectInputGUI($lng->txt("cont_current_style"), "style_id");
				$style_sel->setOptions($st_styles);
				$style_sel->setValue($style_id);
				$this->form->addItem($style_sel);
//$this->ctrl->getLinkTargetByClass("ilObjStyleSheetGUI", "create"));
				$this->form->addCommandButton("saveStyleSettings",
						$lng->txt("save"));
				$this->form->addCommandButton("createStyle",
					$lng->txt("sty_create_ind_style"));
			}
		}
		$this->form->setTitle($lng->txt("cont_style"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}
	
	/**
	* Create Style
	*/
	function createStyle()
	{
		global $ilCtrl;

		$ilCtrl->redirectByClass("ilobjstylesheetgui", "create");
	}
	
	/**
	* Edit Style
	*/
	function editStyle()
	{
		global $ilCtrl;

		$ilCtrl->redirectByClass("ilobjstylesheetgui", "edit");
	}

	/**
	* Delete Style
	*/
	function deleteStyle()
	{
		global $ilCtrl;

		$ilCtrl->redirectByClass("ilobjstylesheetgui", "delete");
	}

	/**
	* Save style settings
	*/
	function saveStyleSettings()
	{
		global $ilSetting;
	
		if ($ilSetting->get("fixed_content_style_id") <= 0 &&
			(ilObjStyleSheet::_lookupStandard($this->object->getStyleSheetId())
			|| $this->object->getStyleSheetId() == 0))
		{
			$this->object->setStyleSheetId(ilUtil::stripSlashes($_POST["style_id"]));
			$this->object->update();
			ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
		}
		$this->ctrl->redirect($this, "editStyleProperties");
	}

	/**
	* Edit menu properies
	*/
	function editMenuProperties()
	{
		global $lng, $ilTabs, $ilCtrl;

		$lng->loadLanguageModule("style");
		$this->setTabs();
		$ilTabs->setTabActive("settings");
		$this->setSubTabs("cont_lm_menu");

		// lm menu properties
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_properties.html", "Modules/LearningModule");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$this->tpl->setVariable("TXT_LM_MENU", $this->lng->txt("cont_lm_menu"));
		$this->tpl->setVariable("TXT_ACT_MENU", $this->lng->txt("cont_active"));
		$this->tpl->setVariable("CBOX_LM_MENU", "cobj_act_lm_menu");
		$this->tpl->setVariable("VAL_LM_MENU", "y");
		if ($this->object->isActiveLMMenu())
		{
			$this->tpl->setVariable("CHK_LM_MENU", "checked");
		}

		// toc
		$this->tpl->setVariable("TXT_TOC", $this->lng->txt("cont_toc"));
		$this->tpl->setVariable("CBOX_TOC", "cobj_act_toc");
		$this->tpl->setVariable("VAL_TOC", "y");
		if ($this->object->isActiveTOC())
		{
			$this->tpl->setVariable("CHK_TOC", "checked");
		}

		// print view
		$this->tpl->setVariable("TXT_PRINT", $this->lng->txt("cont_print_view"));
		$this->tpl->setVariable("CBOX_PRINT", "cobj_act_print");
		$this->tpl->setVariable("VAL_PRINT", "y");
		if ($this->object->isActivePrintView())
		{
			$this->tpl->setVariable("CHK_PRINT", "checked");
		}
		
		// prevent glossary appendix
		$this->tpl->setVariable("TXT_PRINT_PREV_GLO", $this->lng->txt("cont_print_view_pre_glo"));
		$this->tpl->setVariable("CBOX_PRINT_PREV_GLO", "cobj_act_print_prev_glo");
		$this->tpl->setVariable("VAL_PRINT_PREV_GLO", "y");
		if ($this->object->isActivePreventGlossaryAppendix())
		{
			$this->tpl->setVariable("CHK_PRINT_PREV_GLO", "checked");
		}

		// downloads
		$no_download_file_available =
			" ".$lng->txt("cont_no_download_file_available").
			" <a href='".$ilCtrl->getLinkTargetByClass("ilexportgui", "")."'>".$lng->txt("change")."</a>";
		$types = array("xml", "html", "scorm");
		foreach($types as $type)
		{
			if ($this->object->getPublicExportFile($type) != "")
			{
				if (is_file($this->object->getExportDirectory($type)."/".
					$this->object->getPublicExportFile($type)))
				{
					$no_download_file_available = "";
				}
			}
		}
		$this->tpl->setVariable("TXT_DOWNLOADS", $this->lng->txt("cont_downloads"));
		$this->tpl->setVariable("TXT_DOWNLOADS_DESC", $this->lng->txt("cont_downloads_desc").$no_download_file_available);
		$this->tpl->setVariable("CBOX_DOWNLOADS", "cobj_act_downloads");
		$this->tpl->setVariable("VAL_DOWNLOADS", "y");

		if ($this->object->isActiveDownloads())
		{
			$this->tpl->setVariable("CHK_DOWNLOADS", "checked=\"checked\"");
		}

		$this->tpl->setVariable("TXT_DOWNLOADS_PUBLIC_DESC", $this->lng->txt("cont_downloads_public_desc"));
		$this->tpl->setVariable("CBOX_DOWNLOADS_PUBLIC", "cobj_act_downloads_public");
		$this->tpl->setVariable("VAL_DOWNLOADS_PUBLIC", "y");

		if ($this->object->isActiveDownloadsPublic())
		{
			$this->tpl->setVariable("CHK_DOWNLOADS_PUBLIC", "checked=\"checked\"");
		}

		if (!$this->object->isActiveDownloads())
		{
			$this->tpl->setVariable("CHK2_DOWNLOADS_PUBLIC", "disabled=\"disabled\"");
		}

		// get user defined menu entries
		$this->__initLMMenuEditor();
		$entries = $this->lmme_obj->getMenuEntries();

		if (count($entries) > 0)
		{
			foreach ($entries as $entry)
			{
				$this->ctrl->setParameter($this, "menu_entry", $entry["id"]);

				$this->tpl->setCurrentBlock("menu_entries");

				if ($entry["type"] == "intern")
				{
					$entry["link"] = ILIAS_HTTP_PATH."/goto.php?target=".$entry["link"];
				}

				// add http:// prefix if not exist
				if (!strstr($entry["link"],'://') && !strstr($entry["link"],'mailto:'))
				{
					$entry["link"] = "http://".$entry["link"];
				}

				$this->tpl->setVariable("ENTRY_LINK", $entry["link"]);
				$this->tpl->setVariable("ENTRY_TITLE", $entry["title"]);

				$this->tpl->setVariable("CBOX_ENTRY", "menu_entries[]");
				$this->tpl->setVariable("VAL_ENTRY", $entry["id"]);

				if (ilUtil::yn2tf($entry["active"]))
				{
					$this->tpl->setVariable("CHK_ENTRY", "checked=\"checked\"");
				}


				$this->tpl->setVariable("LINK_EDIT", $this->ctrl->getLinkTarget($this,"editMenuEntry"));
				$this->tpl->setVariable("TARGET_EDIT", "content");
				$this->tpl->setVariable("TXT_EDIT", $this->lng->txt("edit"));
				$this->tpl->setVariable("IMG_EDIT", ilUtil::getImagePath("icon_pencil.gif"));

				$this->tpl->setVariable("LINK_DROP", $this->ctrl->getLinkTarget($this,"deleteMenuEntry"));
				$this->tpl->setVariable("TARGET_DROP", "content");
				$this->tpl->setVariable("TXT_DROP", $this->lng->txt("drop"));
				$this->tpl->setVariable("IMG_DROP", ilUtil::getImagePath("delete.gif"));

				$this->tpl->parseCurrentBlock();
			}
		}

		// add entry link


		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "saveMenuProperties");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->setVariable("BTN_NAME2", "addMenuEntry");
		$this->tpl->setVariable("BTN_TEXT2", $this->lng->txt("add_menu_entry"));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* save properties
	*/
	function saveMenuProperties()
	{
		global $ilias;

		$this->object->setActiveLMMenu(ilUtil::yn2tf($_POST["cobj_act_lm_menu"]));
		$this->object->setActiveTOC(ilUtil::yn2tf($_POST["cobj_act_toc"]));
		$this->object->setActivePrintView(ilUtil::yn2tf($_POST["cobj_act_print"]));
		$this->object->setActivePreventGlossaryAppendix(ilUtil::yn2tf($_POST["cobj_act_print_prev_glo"]));
		$this->object->setActiveDownloads(ilUtil::yn2tf($_POST["cobj_act_downloads"]));
		$this->object->setActiveDownloadsPublic(ilUtil::yn2tf($_POST["cobj_act_downloads_public"]));
		$this->object->updateProperties();

		$this->__initLMMenuEditor();
//var_dump($_POST["menu_entries"]); exit;
		$this->lmme_obj->updateActiveStatus($_POST["menu_entries"]);

		ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
		$this->ctrl->redirect($this, "editMenuProperties");
	}

	/**
	* output explorer tree
	*/
	function explorer()
	{
		global $ilUser, $ilias, $ilCtrl;

		switch ($this->object->getType())
		{
			case "lm":
				$gui_class = "ilobjlearningmodulegui";
				break;

			case "dlb":
				$gui_class = "ilobjdlbookgui";
				break;
		}

		$ilCtrl->setParameterByClass($gui_class, "active_node", $_GET["active_node"]);
		
		$this->tpl = new ilTemplate("tpl.main.html", true, true);
		// get learning module object
		//$this->lm_obj =& new ilObjLearningModule($this->ref_id, true);

		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());

		//$this->tpl = new ilTemplate("tpl.explorer.html", false, false);
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.explorer.html");
		$this->tpl->setVariable("IMG_SPACE", ilUtil::getImagePath("spacer.gif", false));

		require_once ("./Modules/LearningModule/classes/class.ilLMEditorExplorer.php");
		$exp = new ilLMEditorExplorer($this->ctrl->getLinkTarget($this, "view"),
			$this->object, $gui_class);

		$exp->setTargetGet("obj_id");
		$exp->setExpandTarget($this->ctrl->getLinkTarget($this, "explorer"));

		if ($_GET["lmmovecopy"] == "1")
		{
			$this->proceedDragDrop();
		}


		if ($_GET["lmexpand"] == "")
		{
			$mtree = new ilTree($this->object->getId());
			$mtree->setTableNames('lm_tree','lm_data');
			$mtree->setTreeTablePK("lm_id");
			$expanded = $mtree->readRootId();
		}
		else
		{
			$expanded = $_GET["lmexpand"];
		}
		if ($_GET["active_node"] != "")
		{
			$path = $this->lm_tree->getPathId($_GET["active_node"]);
			$exp->setForceOpenPath($path);

			$exp->highlightNode($_GET["active_node"]);
		}
		$exp->setExpand($expanded);

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();
		
		// asynchronous output
		if ($ilCtrl->isAsynch())
		{
			echo $output; exit;
		}

		include_once("./Services/COPage/classes/class.ilPageEditorGUI.php");
		
		/*if (ilPageEditorGUI::_doJSEditing())
		{
			//$this->tpl->touchBlock("includejavascript");

			$IDS = "";
			for ($i=0;$i<count($exp->iconList);$i++)
			{
				if ($i>0) $IDS .= ",";
				$IDS .= "'".$exp->iconList[$i]."'";
			}
			$this->tpl->setVariable("ICONIDS",$IDS);
			//$this->ctrl->setParameter($this, "lmovecopy", 1);
			$this->tpl->setVariable("TESTPFAD",$this->ctrl->getLinkTarget($this, "explorer")."&lmmovecopy=1");
			//$this->tpl->setVariable("POPUPLINK",$this->ctrl->getLinkTarget($this, "popup")."&ptype=movecopytreenode");
			$this->tpl->setVariable("POPUPLINK",$this->ctrl->getLinkTarget($this, "popup")."&ptype=movecopytreenode");
		}*/

		$this->tpl->setCurrentBlock("content");
		$this->tpl->setVariable("TXT_EXPLORER_HEADER", $this->lng->txt("cont_chap_and_pages"));
		$this->tpl->setVariable("EXP_REFRESH", $this->lng->txt("refresh"));
		$this->tpl->setVariable("EXPLORER",$output);
		$this->ctrl->setParameter($this, "lmexpand", $_GET["lmexpand"]);
		$this->tpl->setVariable("ACTION", $this->ctrl->getLinkTarget($this, "explorer"));
		$this->tpl->parseCurrentBlock();
		$this->tpl->show(false);
		exit;
	}

	/**
	* popup window for wysiwyg editor
	*/
	function popup()
	{
		include_once "./Services/COPage/classes/class.ilWysiwygUtil.php";
		$popup = new ilWysiwygUtil();
		$popup->show($_GET["ptype"]);
		exit;
	}

	/**
	* proceed drag and drop operations on pages/chapters
	*/
	function proceedDragDrop()
	{
		global $ilCtrl;
		
		$this->object->executeDragDrop($_POST["il_hform_source_id"], $_POST["il_hform_target_id"],
			$_POST["il_hform_fc"], $_POST["il_hform_as_subitem"]);
		$ilCtrl->redirect($this, "chapters");
	}

	/**
	* form for new content object creation
	*/
	function createObject()
	{
		global $rbacsystem, $tpl;

		$new_type = $_POST["new_type"] ? $_POST["new_type"] : $_GET["new_type"];

		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $new_type))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			$this->initCreationForm();
			$f1 = $this->form->getHTML();
			$this->initImportForm();
			$f2 = $this->form->getHTML();
			$this->tpl->setContent($f1."<br />".$f2);
			
			
			
return;
			// fill in saved values in case of error
			$data = array();
			$data["fields"] = array();
			$data["fields"]["title"] = ilUtil::prepareFormOutput($_SESSION["error_post_vars"]["Fobject"]["title"],true);
			$data["fields"]["desc"] = ilUtil::stripSlashes($_SESSION["error_post_vars"]["Fobject"]["desc"]);

			$this->getTemplateFile("create", $new_type);

			$this->tpl->setVariable("TYPE_IMG",
				ilUtil::getImagePath("icon_".$new_type.".gif"));
			$this->tpl->setVariable("ALT_IMG",
				$this->lng->txt("obj_".$new_type));

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);

				if ($this->prepare_output)
				{
					$this->tpl->parseCurrentBlock();
				}
			}
			
			
			//$this->tpl->setVariable("FORMACTION", $this->getFormAction("save",
			//	"adm_object.php?cmd=gateway&ref_id=".
			//	$_GET["ref_id"]."&new_type=".$new_type));
			$this->ctrl->setParameter($this, "new_type", $new_type);
			$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this, "save"));


			$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($new_type."_new"));
			$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt($new_type."_add"));
			$this->tpl->setVariable("CMD_SUBMIT", "save");
			$this->tpl->setVariable("TARGET", ' target="'.
				ilFrameTargetInfo::_getFrame("MainContent").'" ');
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));

			$this->tpl->setVariable("TXT_IMPORT_LM", $this->lng->txt("import_".$new_type));
			$this->tpl->setVariable("TXT_LM_FILE", $this->lng->txt("file"));
			$this->tpl->setVariable("TXT_IMPORT", $this->lng->txt("import"));

			// get the value for the maximal uploadable filesize from the php.ini (if available)
			$umf=get_cfg_var("upload_max_filesize");
			// get the value for the maximal post data from the php.ini (if available)
			$pms=get_cfg_var("post_max_size");

			// use the smaller one as limit
			$max_filesize = ((int) $umf < (int) $pms)
				? $umf
				: $pms;
			if ((int) $pms == 0) $max_filesize = $umf;
			
			if (!$max_filesize) $max_filesize=max($umf, $pms);

			// gives out the limit as a littel notice :)
			$this->tpl->setVariable("TXT_FILE_INFO", $this->lng->txt("file_notice")." $max_filesize.");

		}
	}

	/**
	* Init creation form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initCreationForm($a_mode = "create")
	{
		global $lng, $ilCtrl;
		
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		$new_type = $_POST["new_type"] ? $_POST["new_type"] : $_GET["new_type"];
		$this->ctrl->setParameter($this, "new_type", $new_type);
		
		$this->form->setTarget(ilFrameTargetInfo::_getFrame("MainContent"));
		$this->form->setTableWidth("600px");
		
		// title
		$ti = new ilTextInputGUI($this->lng->txt("title"), "title");
		$ti->setMaxLength(128);
		$ti->setRequired(true);
		//$ti->setSize();
		$this->form->addItem($ti);
		
		// text area
		$ta = new ilTextAreaInputGUI($this->lng->txt("description"), "desc");
		//$ta->setCols();
		//$ta->setRows();
		$this->form->addItem($ta);
		
		$this->form->addCommandButton("save", $this->lng->txt($new_type."_add"));
		$this->form->addCommandButton("cancel", $lng->txt("cancel"));
		$this->form->setTitle($this->lng->txt($new_type."_new"));
		$this->form->setFormAction($this->ctrl->getFormAction($this, "save"));
	}
	
	/**
	* Init import form.
	*/
	public function initImportForm()
	{
		global $lng, $ilCtrl;
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
	
		$new_type = $_POST["new_type"] ? $_POST["new_type"] : $_GET["new_type"];
		$this->ctrl->setParameter($this, "new_type", $new_type);
		
		$this->form->setTarget(ilFrameTargetInfo::_getFrame("MainContent"));
		$this->form->setTableWidth("600px");
		
		// import file
		$fi = new ilFileInputGUI($this->lng->txt("file"), "xmldoc");
		$fi->setSuffixes(array("zip"));
		$fi->setRequired(true);
		$fi->setSize(30);
		$this->form->addItem($fi);
		
		// validation
		$cb = new ilCheckboxInputGUI($this->lng->txt("cont_validate_file"), "validate");
		$cb->setInfo($this->lng->txt(""));
		$this->form->addItem($cb);
		
		$this->form->addCommandButton("importFile", $lng->txt("import"));
		$this->form->addCommandButton("cancel", $lng->txt("cancel"));
	                
		$this->form->setTitle($this->lng->txt("import_".$new_type));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	 
	}
	
	/**
	* save new content object to db
	*/
	function saveObject()
	{
		global $rbacadmin, $rbacsystem, $tpl;

		// always call parent method first to create an object_data entry & a reference
		//$newObj = parent::saveObject();
		// TODO: fix MetaDataGUI implementation to make it compatible to use parent call
		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $_GET["new_type"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_create_permission"), $this->ilias->error_obj->MESSAGE);
			return;
		}
		
		$this->initCreationForm();
		if ($this->form->checkInput())
		{
			// create and insert object in objecttree
			include_once("./Modules/LearningModule/classes/class.ilObjContentObject.php");
			$newObj = new ilObjContentObject();
			$newObj->setType($this->type);
			$newObj->setTitle($_POST["title"]);#"content object ".$newObj->getId());		// set by meta_gui->save
			$newObj->setDescription($_POST["desc"]);	// set by meta_gui->save
			$newObj->create();
			$newObj->createReference();
			$newObj->putInTree($_GET["ref_id"]);
			$newObj->setPermissions($_GET["ref_id"]);
			$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());
			$newObj->setCleanFrames(true);
			$newObj->update();

			// create content object tree
			$newObj->createLMTree();

			// always send a message
			ilUtil::sendSuccess($this->lng->txt($this->type."_added"), true);
			ilUtil::redirect("ilias.php?ref_id=".$newObj->getRefId().
				"&baseClass=ilLMEditorGUI");

		}
		else
		{
			$this->form->setValuesByPost();
			$tpl->setContent($this->form->getHtml());
		}
	}

	/**
	* add bib item (admin call)
	*/
	function addBibItemObject($a_target = "")
	{
		include_once "./Modules/LearningModule/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemName = $_POST["bibItemName"] ? $_POST["bibItemName"] : $_GET["bibItemName"];
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		if ($bibItemIndex == "")
			$bibItemIndex = 0;
		$bibItemPath = $_POST["bibItemPath"] ? $_POST["bibItemPath"] : $_GET["bibItemPath"];
		if ($bibItemName != "")
		{
			$bib_gui->bib_obj->add($bibItemName, $bibItemPath, $bibItemIndex);
			$data = $bib_gui->bib_obj->getElement("BibItem");
			$bibItemIndex = (count($data) - 1);
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt("bibitem_choose_element"), true);
		}
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* add bib item (module call)
	*/
	function addBibItem()
	{
		$this->setTabs();
		
		// questionable workaround to make this old stuff work
		$this->ctrl->setParameter($this, ilCtrl::IL_RTOKEN_NAME, $this->ctrl->getRequestToken());

		$this->addBibItemObject($this->ctrl->getLinkTarget($this));
	}

	/**
	* delete bib item (admin call)
	*/
	function deleteBibItemObject($a_target = "")
	{
		include_once "./Modules/LearningModule/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		$bib_gui->bib_obj->delete($_GET["bibItemName"], $_GET["bibItemPath"], $bibItemIndex);
		if (strpos($bibItemIndex, ",") > 0)
		{
			$bibItemIndex = substr($bibItemIndex, 0, strpos($bibItemIndex, ","));
		}
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* delete bib item (module call)
	*/
	function deleteBibItem()
	{
		$this->setTabs();
		
		// questionable workaround to make this old stuff work
		$this->ctrl->setParameter($this, ilCtrl::IL_RTOKEN_NAME, $this->ctrl->getRequestToken());

		$this->deleteBibItemObject($this->ctrl->getLinkTarget($this));
	}


	/**
	* edit bib items (admin call)
	*/
	function editBibItemObject($a_target = "")
	{
		include_once "./Modules/LearningModule/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		$bibItemIndex *= 1;
		if ($bibItemIndex < 0)
		{
			$bibItemIndex = 0;
		}
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* edit bib items (module call)
	*/
	function editBibItem()
	{
		$this->setTabs();
		
		// questionable workaround to make this old stuff work
		$this->ctrl->setParameter($this, ilCtrl::IL_RTOKEN_NAME, $this->ctrl->getRequestToken());
		
		$this->editBibItemObject($this->ctrl->getLinkTarget($this));
	}

	/**
	* save bib item (admin call)
	*/
	function saveBibItemObject($a_target = "")
	{
		include_once "./Modules/LearningModule/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		$bibItemIndex *= 1;
		if ($bibItemIndex < 0)
		{
			$bibItemIndex = 0;
		}
		$bibItemIndex = $bib_gui->save($bibItemIndex);

		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* save bib item (module call)
	*/
	function saveBibItem()
	{
		$this->setTabs();

		// questionable workaround to make this old stuff work
		$this->ctrl->setParameter($this, ilCtrl::IL_RTOKEN_NAME, $this->ctrl->getRequestToken());

		$this->saveBibItemObject($this->ctrl->getLinkTarget($this));
	}

	/**
	* export object
	*
	* @access	public
	*/
	function exportObject()
	{
		return;
	}

	/**
	* display dialogue for importing XML-LeaningObjects
	*
	* @access	public
	*/
	function importObject()
	{
		$this->createObject();
		return;
	}


	/**
	* display status information or report errors messages
	* in case of error
	*
	* @access	public
	*/
	function importFileObject()
	{
		global $_FILES, $rbacsystem, $ilDB, $tpl;

		include_once "./Modules/LearningModule/classes/class.ilObjLearningModule.php";

		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $_GET["new_type"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_create_permission"), $this->ilias->error_obj->MESSAGE);
			return;
		}

		$this->initImportForm();
		if ($this->form->checkInput())
		{
			// create and insert object in objecttree
			include_once("./Modules/LearningModule/classes/class.ilObjContentObject.php");
			$newObj = new ilObjContentObject();
			$newObj->setType($_GET["new_type"]);
			$newObj->setTitle($_FILES["xmldoc"]["name"]);
			$newObj->setDescription("");
			$newObj->create(true);
			$newObj->createReference();
			$newObj->putInTree($_GET["ref_id"]);
			$newObj->setPermissions($_GET["ref_id"]);
			$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());
	
			// create learning module tree
			$newObj->createLMTree();
	
			// create import directory
			$newObj->createImportDirectory();
	
			// copy uploaded file to import directory
			$file = pathinfo($_FILES["xmldoc"]["name"]);
			$full_path = $newObj->getImportDirectory()."/".$_FILES["xmldoc"]["name"];
	
			ilUtil::moveUploadedFile($_FILES["xmldoc"]["tmp_name"],
				$_FILES["xmldoc"]["name"], $full_path);
	
			//move_uploaded_file($_FILES["xmldoc"]["tmp_name"], $full_path);
	
			// unzip file
			ilUtil::unzip($full_path);
	
			// determine filename of xml file
			$subdir = basename($file["basename"],".".$file["extension"]);
			$xml_file = $newObj->getImportDirectory()."/".$subdir."/".$subdir.".xml";
	
			// check whether subdirectory exists within zip file
			if (!is_dir($newObj->getImportDirectory()."/".$subdir))
			{
				$this->ilias->raiseError(sprintf($this->lng->txt("cont_no_subdir_in_zip"), $subdir),
					$this->ilias->error_obj->MESSAGE);
			}
	
			// check whether xml file exists within zip file
			if (!is_file($xml_file))
			{
				$this->ilias->raiseError(sprintf($this->lng->txt("cont_zip_file_invalid"), $subdir."/".$subdir.".xml"),
					$this->ilias->error_obj->MESSAGE);
			}
	
			include_once ("./Modules/LearningModule/classes/class.ilContObjParser.php");
			$contParser = new ilContObjParser($newObj, $xml_file, $subdir);
			$contParser->startParsing();
			ilObject::_writeImportId($newObj->getId(), $newObj->getImportId());
			$newObj->MDUpdateListener('General');
	
			// import style
			$style_file = $newObj->getImportDirectory()."/".$subdir."/style.xml";
			$style_zip_file = $newObj->getImportDirectory()."/".$subdir."/style.zip";
			if (is_file($style_zip_file))	// try to import style.zip first
			{
				require_once("./Services/Style/classes/class.ilObjStyleSheet.php");
				$style = new ilObjStyleSheet();
				$style->import($style_zip_file);
				$newObj->writeStyleSheetId($style->getId());
			}
			else if (is_file($style_file))	// try to import style.xml
			{
				require_once("./Services/Style/classes/class.ilObjStyleSheet.php");
				$style = new ilObjStyleSheet();
				$style->import($style_file);
				$newObj->writeStyleSheetId($style->getId());
			}
			
			// delete import directory
			ilUtil::delDir($newObj->getImportDirectory());
			
			// validate
			if ($_POST["validate"])
			{
				$mess = $newObj->validatePages();
			}
			
			if ($mess == "")
			{
				ilUtil::sendSuccess($this->lng->txt($this->type."_added"),true);
		
				// handle internal links to this learning module
				include_once("./Services/COPage/classes/class.ilPageObject.php");
				ilPageObject::_handleImportRepositoryLinks($newObj->getImportId(),
					$newObj->getType(), $newObj->getRefId());
				ilUtil::redirect("ilias.php?ref_id=".$newObj->getRefId().
					"&baseClass=ilLMEditorGUI");
			}
			else
			{
				$link = '<a href="'."ilias.php?ref_id=".$newObj->getRefId().
					"&baseClass=ilLMEditorGUI".'" target="_top">'.$this->lng->txt("btn_next").'</a>';
				$tpl->setContent("<br />".$link."<br /><br />".$mess.$link);
			}
		}
		else
		{
			$this->form->setValuesByPost();
			$tpl->setContent($this->form->getHtml());
		}
	}

	/**
	* show chapters
	*/
	function chapters()
	{
		global $tree, $lng, $ilCtrl, $ilUser;

		$this->setTabs();
		
		if ($ilUser->getPref("lm_js_chapter_editing") != "disable")
		{
			$ilCtrl->setParameter($this, "backcmd", "chapters");
			
			include_once("./Modules/LearningModule/classes/class.ilChapterHierarchyFormGUI.php");
			$form_gui = new ilChapterHierarchyFormGUI($this->object->getType());
			$form_gui->setFormAction($ilCtrl->getFormAction($this));
			$form_gui->setTitle($this->object->getTitle());
			$form_gui->setIcon(ilUtil::getImagePath("icon_lm.gif"));
			$form_gui->setTree($this->lm_tree);
			$form_gui->setMaxDepth(0);
			$form_gui->setCurrentTopNodeId($this->tree->getRootId());
			$form_gui->addMultiCommand($lng->txt("delete"), "delete");
			$form_gui->addMultiCommand($lng->txt("cut"), "cutItems");
			$form_gui->addMultiCommand($lng->txt("copy"), "copyItems");
			$form_gui->setDragIcon(ilUtil::getImagePath("icon_st_s.gif"));
			$form_gui->addCommand($lng->txt("cont_save_all_titles"), "saveAllTitles");
			$up_gui = ($this->object->getType() == "dbk")
				? "ilobjdlbookgui"
				: "ilobjlearningmodulegui";
			$form_gui->setExplorerUpdater("tree", "tree_div",
				$ilCtrl->getLinkTargetByClass($up_gui, "explorer", "", true));

			$ctpl = new ilTemplate("tpl.chap_and_pages.html", true, true, "Modules/LearningModule");
			$ctpl->setVariable("HIERARCHY_FORM", $form_gui->getHTML());
			$ilCtrl->setParameter($this, "obj_id", "");
			$ctpl->setVariable("HREF_NO_JS_EDIT",
				$ilCtrl->getLinkTarget($this, "deactivateJSChapterEditing"));
			$ctpl->setVariable("TXT_NO_JS_EDIT",
				$lng->txt("cont_not_js_chap_editing"));

			$this->tpl->setContent($ctpl->get());
		}
		else
		{
			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.structure_edit.html", "Modules/LearningModule");
			$num = 0;
	
			$this->ctrl->setParameter($this, "backcmd", "chapters");
			$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
			$this->tpl->setVariable("HEADER_TEXT", $this->lng->txt("cont_chapters"));
			$this->tpl->setVariable("CHECKBOX_TOP", IL_FIRST_NODE);
	
			$cnt = 0;
			$childs = $this->lm_tree->getChilds($this->lm_tree->getRootId());
			foreach ($childs as $child)
			{
				if($child["type"] != "st")
				{
					continue;
				}
	
				$this->tpl->setCurrentBlock("table_row");
				// color changing
				$css_row = ilUtil::switchColor($cnt++,"tblrow1","tblrow2");
	
				// checkbox
				$this->tpl->setVariable("CHECKBOX_ID", $child["obj_id"]);
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_st.gif"));
	
				// link
				$this->ctrl->setParameter($this, "backcmd", "");
				$this->ctrl->setParameterByClass("ilStructureObjectGUI", "obj_id", $child["obj_id"]);
				$this->tpl->setVariable("LINK_TARGET",
					$this->ctrl->getLinkTargetByClass("ilStructureObjectGUI", "view"));
	
				// title
				$this->tpl->setVariable("TEXT_CONTENT",
					ilStructureObject::_getPresentationTitle($child["obj_id"],
					$this->object->isActiveNumbering()));
	
				$this->tpl->parseCurrentBlock();
			}
	
			$paste_active = false;
			if ($ilUser->clipboardHasObjectsOfType("st"))
			{
				$paste_active = true;
			}
	
			if($cnt == 0 && !$paste_active)
			{
				$this->tpl->setCurrentBlock("notfound");
				$this->tpl->setVariable("NUM_COLS", 3);
				$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				// SHOW VALID ACTIONS
				$this->tpl->setVariable("NUM_COLS", 3);
				if ($cnt > 0)
				{
					$acts = array("delete" => "delete", "cutChapter" => "cut",
						"copyChapter" => "copyChapter");
				}
				if ($paste_active)
				{
					$acts["pasteChapter"] =  "pasteChapter";
				}
				$this->showActions($acts);
			}
	
			// SHOW POSSIBLE SUB OBJECTS
			$this->tpl->setVariable("NUM_COLS", 3);
			$subobj = array("st");
			$opts = ilUtil::formSelect(12,"new_type",$subobj);
			$this->tpl->setCurrentBlock("add_object");
			$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
			$this->tpl->setVariable("BTN_NAME", "create");
			$this->tpl->setVariable("TXT_ADD", $this->lng->txt("insert"));
			$this->tpl->parseCurrentBlock();
	
			$this->tpl->setCurrentBlock("form");
			$this->tpl->parseCurrentBlock();
			
			$this->tpl->setVariable("HREF_JS_EDIT",
				$ilCtrl->getLinkTarget($this, "activateJSChapterEditing"));
			$this->tpl->setVariable("TXT_JS_EDIT",
				$lng->txt("cont_js_chap_editing"));
		}
	}


	/*
	* list all pages of learning module
	*/
	function pages()
	{
		global $tree;

		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.all_pages.html", "Modules/LearningModule");
		$num = 0;

		$this->tpl->setCurrentBlock("form");
		$this->ctrl->setParameter($this, "backcmd", "pages");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("HEADER_TEXT", $this->lng->txt("cont_pages"));
		$this->tpl->setVariable("CONTEXT", $this->lng->txt("cont_usage"));
		$this->tpl->setVariable("CHECKBOX_TOP", IL_FIRST_NODE);

		$cnt = 0;
		$pages = ilLMPageObject::getPageList($this->object->getId());
		foreach ($pages as $page)
		{
			$this->tpl->setCurrentBlock("table_row");
			
			// check activation
			include_once("./Services/COPage/classes/class.ilPageObject.php");
			$lm_set = new ilSetting("lm");
			$active = ilPageObject::_lookupActive($page["obj_id"], $this->object->getType(),
				$lm_set->get("time_scheduled_page_activation"));
				
			// is page scheduled?
			$img_sc = ($lm_set->get("time_scheduled_page_activation") &&
				ilPageObject::_isScheduledActivation($page["obj_id"], $this->object->getType()))
				? "_sc"
				: "";

			if (!$active)
			{
				$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_pg_d".$img_sc.".gif"));
				$this->tpl->setVariable("IMG_ALT",
					$this->lng->txt("cont_page_deactivated"));
			}
			else
			{
				if (ilPageObject::_lookupContainsDeactivatedElements($page["obj_id"],
					$this->object->getType()))
				{
					$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_pg_del".$img_sc.".gif"));
					$this->tpl->setVariable("IMG_ALT",
						$this->lng->txt("cont_page_deactivated_elements"));
				}
				else
				{
					$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_pg".$img_sc.".gif"));
					$this->tpl->setVariable("IMG_ALT",
						$this->lng->txt("pg"));
				}
			}

			// color changing
			$css_row = ilUtil::switchColor($cnt++,"tblrow1","tblrow2");

			// checkbox
			$this->tpl->setVariable("CHECKBOX_ID", $page["obj_id"]);
			$this->tpl->setVariable("CSS_ROW", $css_row);

			// link
			$this->ctrl->setParameter($this, "backcmd", "");
			$this->ctrl->setParameterByClass("ilLMPageObjectGUI", "obj_id", $page["obj_id"]);
//echo "<br>:".$this->ctrl->getLinkTargetByClass("ilLMPageObjectGUI", "view").":";
			$this->tpl->setVariable("LINK_TARGET",
				$this->ctrl->getLinkTargetByClass("ilLMPageObjectGUI", "edit"));

			// title
			$this->tpl->setVariable("TEXT_CONTENT", $page["title"]);

			// context
			if ($this->lm_tree->isInTree($page["obj_id"]))
			{
				$path_str = $this->getContextPath($page["obj_id"]);
			}
			else
			{
				$path_str = "---";
			}

			// check whether page is header or footer
			$add_str = "";
			if ($page["obj_id"] == $this->object->getHeaderPage())
			{
				$add_str = " <b>(".$this->lng->txt("cont_header").")</b>";
			}
			if ($page["obj_id"] == $this->object->getFooterPage())
			{
				$add_str = " <b>(".$this->lng->txt("cont_footer").")</b>";
			}

			$this->tpl->setVariable("TEXT_CONTEXT", $path_str.$add_str);


			$this->tpl->parseCurrentBlock();
		}
		if($cnt == 0)
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("NUM_COLS", 4);
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$acts = array("delete" => "delete", "movePage" => "movePage", "copyPage" => "copyPage",
				"selectHeader" => "selectHeader", "selectFooter" => "selectFooter",
				"activatePages" => "cont_de_activate");
			if(ilEditClipboard::getContentObjectType() == "pg" &&
				ilEditClipboard::getAction() == "copy")
			{
				$acts["pastePage"] = "pastePage";
			}

			/*
			if (ilEditClipboard::getContentObjectType() == "st")
			{
				$acts["pasteChapter"] =  "pasteChapter";
			}*/
			$this->tpl->setVariable("NUM_COLS", 4);
			$this->showActions($acts);

			// SHOW VALID ACTIONS
			/*
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME", "delete");
			$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("delete"));
			$this->tpl->parseCurrentBlock();*/

		}

		// SHOW POSSIBLE SUB OBJECTS
		$this->tpl->setVariable("NUM_COLS", 4);
		//$this->showPossibleSubObjects("st");
		$subobj = array("pg");
		$opts = ilUtil::formSelect(12,"new_type",$subobj);
		$this->tpl->setCurrentBlock("add_object");
		$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
		$this->tpl->setVariable("BTN_NAME", "create");
		$this->tpl->setVariable("TXT_ADD", $this->lng->txt("create"));
		$this->tpl->parseCurrentBlock();


		$this->tpl->setCurrentBlock("form");
		$this->tpl->parseCurrentBlock();

	}

	/**
	* List all broken links
	*/
	function listLinks()
	{
		global $tpl;
		
		$this->setTabs();
		$this->setLinkSubTabs("internal_links");
		
		include_once("./Modules/LearningModule/classes/class.ilLinksTableGUI.php");
		$table_gui = new ilLinksTableGUI($this, "listLinks",
			$this->object->getId(), $this->object->getType());
		
		$tpl->setContent($table_gui->getHTML());
	}
	
	
	/**
	* activates or deactivates pages
	*/
	function activatePages()
	{
		if (is_array($_POST["id"]))
		{
			foreach($_POST["id"] as $id)
			{
				include_once("./Services/COPage/classes/class.ilPageObject.php");
				$act = ilPageObject::_lookupActive($id, $this->object->getType());
				ilPageObject::_writeActive($id, $this->object->getType(), !$act);
			}
		}

		$this->ctrl->redirect($this, "pages");
	}

	/**
	* Deactivate Javascript Chapter Editing
	*/
	function deactivateJSChapterEditing()
	{
		global $ilCtrl, $ilUser;
		
		$ilUser->writePref("lm_js_chapter_editing", "disable");
		$ilCtrl->redirect($this, "chapters");
	}
	
	/**
	* Deactivate Javascript Chapter Editing
	*/
	function activateJSChapterEditing()
	{
		global $ilCtrl, $ilUser;
		
		$ilUser->writePref("lm_js_chapter_editing", "enable");
		$ilCtrl->redirect($this, "chapters");
	}

	/**
	* paste page
	*/
	function pastePage()
	{
		if(ilEditClipboard::getContentObjectType() != "pg")
		{
			$this->ilias->raiseError($this->lng->txt("no_page_in_clipboard"),$this->ilias->error_obj->MESSAGE);
		}

		// paste selected object
		$id = ilEditClipboard::getContentObjectId();

		// copy page, if action is copy
		if (ilEditClipboard::getAction() == "copy")
		{
			// check wether page belongs to lm
			if (ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId())
				== $this->object->getID())
			{
				$lm_page = new ilLMPageObject($this->object, $id);
				$new_page =& $lm_page->copy();
				$id = $new_page->getId();
			}
			else
			{
				// get page from other content object into current content object
				$lm_id = ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId());
				$lm_obj =& $this->ilias->obj_factory->getInstanceByObjId($lm_id);
				$lm_page = new ilLMPageObject($lm_obj, $id);
				$copied_nodes = array();
				$new_page =& $lm_page->copyToOtherContObject($this->object, $copied_nodes);
				$id = $new_page->getId();
				ilLMObject::updateInternalLinks($copied_nodes);
			}
		}

		// cut is not be possible in "all pages" form yet
		if (ilEditClipboard::getAction() == "cut")
		{
			// check wether page belongs not to lm
			if (ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId())
				!= $this->object->getID())
			{
				$lm_id = ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId());
				$lm_obj =& $this->ilias->obj_factory->getInstanceByObjId($lm_id);
				$lm_page = new ilLMPageObject($lm_obj, $id);
				$lm_page->setLMId($this->object->getID());
				$lm_page->update();
				$page =& $lm_page->getPageObject();
				$page->buildDom();
				$page->setParentId($this->object->getID());
				$page->update();
			}
		}


		ilEditClipboard::clear();
		$this->ctrl->redirect($this, "pages");
	}

	/**
	* copy page
	*/
	function copyPage()
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		$items = ilUtil::stripSlashesArray($_POST["id"]);
		ilLMObject::clipboardCopy($this->object->getId(), $items);
		ilEditClipboard::setAction("copy");

		ilUtil::sendInfo($this->lng->txt("msg_copy_clipboard"), true);

		$this->ctrl->redirect($this, "pages");
	}

	/**
	* confirm deletion screen for page object and structure object deletion
	*
	* @param	int		$a_parent_subobj_id		id of parent object (structure object)
	*											of the objects, that should be deleted
	*											(or no parent object id for top level)
	*/
	function delete($a_parent_subobj_id = 0)
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		if(count($_POST["id"]) == 1 && $_POST["id"][0] == IL_FIRST_NODE)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_item"), $this->ilias->error_obj->MESSAGE);
		}

		if ($a_parent_subobj_id == 0)
		{
			$this->setTabs();
		}

		// SAVE POST VALUES
		$_SESSION["saved_post"] = $_POST["id"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm_deletion.html", "Modules/LearningModule");

		ilUtil::sendQuestion($this->lng->txt("info_delete_sure"));

		if ($a_parent_subobj_id != 0)
		{
			$this->ctrl->setParameterByClass("ilStructureObjectGUI", "backcmd", $_GET["backcmd"]);
			$this->ctrl->setParameterByClass("ilStructureObjectGUI", "obj_id", $a_parent_subobj_id);
			$this->tpl->setVariable("FORMACTION",
				$this->ctrl->getFormActionByClass("ilStructureObjectGUI"));
		}
		else
		{
			$this->ctrl->setParameter($this, "backcmd", $_GET["backcmd"]);
			$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		}
		// BEGIN TABLE HEADER
		$this->tpl->setCurrentBlock("table_header");
		$this->tpl->setVariable("TEXT",$this->lng->txt("objects"));
		$this->tpl->parseCurrentBlock();

		// END TABLE HEADER

		// BEGIN TABLE DATA
		$counter = 0;
//var_dump($_POST);
		foreach($_POST["id"] as $id)
		{
			if ($id != IL_FIRST_NODE)
			{
				$obj =& new ilLMObject($this->object, $id);
				switch($obj->getType())		// ok that's not so nice, could be done better
				{
					case "pg":
						$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_pg.gif"));
						break;
					case "st":
						$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_st.gif"));
						break;
				}
				$this->tpl->setCurrentBlock("table_row");
				$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow2","tblrow1"));
				$this->tpl->setVariable("TEXT_CONTENT", $obj->getTitle());
				$this->tpl->parseCurrentBlock();
			}
		}

		// cancel/confirm button
		$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$buttons = array( "cancelDelete"  => $this->lng->txt("cancel"),
								  "confirmedDelete"  => $this->lng->txt("confirm"));
		foreach ($buttons as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* cancel delete
	*/
	function cancelDelete()
	{
		session_unregister("saved_post");

		$this->ctrl->redirect($this, $_GET["backcmd"]);

	}

	/**
	* delete page object or structure objects
	*
	* @param	int		$a_parent_subobj_id		id of parent object (structure object)
	*											of the objects, that should be deleted
	*											(or no parent object id for top level)
	*/
	function confirmedDelete($a_parent_subobj_id = 0)
	{
		$tree = new ilTree($this->object->getId());
		$tree->setTableNames('lm_tree','lm_data');
		$tree->setTreeTablePK("lm_id");

		// check number of objects
		if (!isset($_SESSION["saved_post"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// delete all selected objects
		foreach ($_SESSION["saved_post"] as $id)
		{
			if ($id != IL_FIRST_NODE)
			{
				$obj =& ilLMObjectFactory::getInstance($this->object, $id, false);
				$node_data = $tree->getNodeData($id);
				if (is_object($obj))
				{
					$obj->setLMId($this->object->getId());

					include_once("classes/class.ilHistory.php");
					ilHistory::_createEntry($this->object->getId(), "delete_".$obj->getType(),
						array(ilLMObject::_lookupTitle($id), $id),
						$this->object->getType());

					$obj->delete();
				}
				if($tree->isInTree($id))
				{
					$tree->deleteTree($node_data);
				}
			}
		}

		// check the tree
		$this->object->checkTree();

		// feedback
		ilUtil::sendSuccess($this->lng->txt("info_deleted"),true);

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, $_GET["backcmd"]);
		}
	}



	/**
	* get context path in content object tree
	*
	* @param	int		$a_endnode_id		id of endnode
	* @param	int		$a_startnode_id		id of startnode
	*/
	function getContextPath($a_endnode_id, $a_startnode_id = 1)
	{
		$path = "";

		$tmpPath = $this->lm_tree->getPathFull($a_endnode_id, $a_startnode_id);

		// count -1, to exclude the learning module itself
		for ($i = 1; $i < (count($tmpPath) - 1); $i++)
		{
			if ($path != "")
			{
				$path .= " > ";
			}

			$path .= $tmpPath[$i]["title"];
		}

		return $path;
	}



	/**
	* show possible action (form buttons)
	*
	* @access	public
	*/
	function showActions($a_actions)
	{
		foreach ($a_actions as $name => $lng)
		{
			$d[$name] = array("name" => $name, "lng" => $lng);
		}

		$notoperations = array();

		$operations = array();

		if (is_array($d))
		{
			foreach ($d as $row)
			{
				if (!in_array($row["name"], $notoperations))
				{
					$operations[] = $row;
				}
			}
		}

		if (count($operations)>0)
		{
			foreach ($operations as $val)
			{
				$this->tpl->setCurrentBlock("operation_btn");
				$this->tpl->setVariable("BTN_NAME", $val["name"]);
				$this->tpl->setVariable("BTN_VALUE", $this->lng->txt($val["lng"]));
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("operation");
			$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* edit permissions
	*/
	function perm()
	{
		$this->setTabs();

		$this->setFormAction("addRole", $this->ctrl->getLinkTarget($this, "addRole"));
		$this->setFormAction("permSave", $this->ctrl->getLinkTarget($this, "permSave"));
		$this->permObject();
	}


	/**
	* save permissions
	*/
	function permSave()
	{
		$this->setReturnLocation("permSave", $this->ctrl->getLinkTarget($this, "perm"));
		$this->permSaveObject();
	}

	/**
	* info permissions
	*/
	function info()
	{
		$this->setTabs();
		$this->infoObject();
	}


	/**
	* add local role
	*/
	function addRole()
	{
		$this->setReturnLocation("addRole", $this->ctrl->getLinkTarget($this, "perm"));
		$this->addRoleObject();
	}


	/**
	* show owner of content object
	*/
	function owner()
	{
		$this->setTabs();
		$this->ownerObject();
	}


	/**
	* view content object
	*/
	function view()
	{
		if (strtolower($_GET["baseClass"]) == "iladministrationgui")
		{
			$this->prepareOutput();
			parent::viewObject();
		}
		else
		{
			$this->viewObject();
		}
	}


	/**
	* move a single chapter  (selection)
	*/
	function moveChapter($a_parent_subobj_id = 0)
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
//echo "Hallo::"; exit;
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		if(count($_POST["id"]) == 1 && $_POST["id"][0] == IL_FIRST_NODE)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_item"), $this->ilias->error_obj->MESSAGE);
		}

		// SAVE POST VALUES
		ilEditClipboard::storeContentObject("st", $_POST["id"][0], "move");

		ilUtil::sendInfo($this->lng->txt("cont_chap_select_target_now"), true);

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}


	/**
	* copy a single chapter  (selection)
	*/
	function copyChapter($a_parent_subobj_id = 0)
	{
		$this->copyItems();
	return;

		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		if(count($_POST["id"]) == 1 && $_POST["id"][0] == IL_FIRST_NODE)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_item"), $this->ilias->error_obj->MESSAGE);
		}

		// SAVE POST VALUES
		ilEditClipboard::storeContentObject("st", $_POST["id"][0], "copy");

		ilUtil::sendInfo($this->lng->txt("cont_chap_copy_select_target_now"), true);

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}

	/**
	* paste chapter
	*/
	function pasteChapter($a_parent_subobj_id = 0)
	{
		return $this->insertChapterClip(false);
	return;

		if (ilEditClipboard::getContentObjectType() != "st")
		{
			$this->ilias->raiseError($this->lng->txt("no_chapter_in_clipboard"),$this->ilias->error_obj->MESSAGE);
		}

		$target_tree = new ilTree($this->object->getId());
		$target_tree->setTableNames('lm_tree','lm_data');
		$target_tree->setTreeTablePK("lm_id");

		// check wether page belongs to lm
		if (ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId())
			!= $this->object->getID())
		{
			$source_tree = new ilTree(ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId()));
			$source_tree->setTableNames('lm_tree','lm_data');
			$source_tree->setTreeTablePK("lm_id");
		}
		else
		{
			$source_tree =& $target_tree;
		}

		// check, if target is within subtree
		$id = ilEditClipboard::getContentObjectId();
		$node = $source_tree->getNodeData($id);
		$subnodes = $source_tree->getSubtree($node);
		foreach ($subnodes as $subnode)
		{
			if($subnode["obj_id"] == $a_parent_subobj_id)
			{
				$this->ilias->raiseError($this->lng->txt("cont_target_within_source"),$this->ilias->error_obj->MESSAGE);
			}
		}
		if($_POST["id"][0] == $id)
		{
			ilEditClipboard::clear();
			$this->ctrl->redirect($this, "chapters");
		}

		// determin parent
		$parent = ($a_parent_subobj_id == 0)
			? $target_tree->getRootId()
			: $a_parent_subobj_id;

		// determine target position
		if(!isset($_POST["id"]))
		{
			$target = IL_LAST_NODE;
		}
		else
		{
			$target = $_POST["id"][0];
		}
		if($target == IL_FIRST_NODE) // do not move a chapter in front of a page
		{
			$childs =& $target_tree->getChildsByType($parent, "pg");
			if (count($childs) != 0)
			{
				$target = $childs[count($childs) - 1]["obj_id"];
			}
		}

		//echo ":".$id.":";
		// delete old tree entries
//echo "-".ilEditClipboard::getAction()."-";
		if (ilEditClipboard::getAction() == "move")
		{
			if (ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId())
				!= $this->object->getID())
			{
				// we should never reach these lines, moving chapters from on
				// lm to another is not supported
				ilEditClipboard::clear();
				if ($a_parent_subobj_id == 0)
				{
					$this->ctrl->redirect($this, "chapters");
				}
				return;
			}

			$target_tree->deleteTree($node);

			if (!$target_tree->isInTree($id))
			{
				$target_tree->insertNode($id, $parent, $target);

				foreach ($subnodes as $node)
				{
					//$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);
					//$obj_data->putInTree($node["parent"]);
					if($node["obj_id"] != $id)
					{
						$target_tree->insertNode($node["obj_id"], $node["parent"]);
					}
				}
			}
		}
		else	// copy
		{
			$id = ilEditClipboard::getContentObjectId();

			$copied_nodes = array();
			if (ilLMObject::_lookupContObjID($id)
				== $this->object->getID())
			{
				$source_obj = ilLMObjectFactory::getInstance($this->object, $id, true);
				$source_obj->setLMId($this->object->getId());
				$source_obj->copy($target_tree, $parent, $target, $copied_nodes);
			}
			else
			{
				$lm_id = ilLMObject::_lookupContObjID($id);

				$source_lm =& ilObjectFactory::getInstanceByObjId($lm_id);
				$source_obj = ilLMObjectFactory::getInstance($source_lm, $id, true);
				$source_obj->setLMId($lm_id);
				$source_obj->copy($target_tree, $parent, $target, $copied_nodes);
			}
		}

		ilLMObject::updateInternalLinks($copied_nodes);
		
		ilEditClipboard::clear();

		// check the tree
		$this->object->checkTree();

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}

	/**
	* move page
	*/
	function movePage()
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		$items = ilUtil::stripSlashesArray($_POST["id"]);
		ilLMObject::clipboardCut($this->object->getId(), $items);
		ilEditClipboard::setAction("cut");
		
		$this->ctrl->redirect($this, "pages");
	}

	/**
	* cancel action
	*/
	function cancel()
	{
		if ($_GET["new_type"] == "pg")
		{
			$this->ctrl->redirect($this, "pages");
		}
		else
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}


	/**
	* export content object
	*/
	function export()
	{
		require_once("./Modules/LearningModule/classes/class.ilContObjectExport.php");
		$cont_exp = new ilContObjectExport($this->object);
		$cont_exp->buildExportFile();
//		$this->ctrl->redirect($this, "exportList");
	}

	/**
	 * Get public access value for export table 
	 */
	function getPublicAccessColValue($a_type, $a_file)
	{
		global $lng, $ilCtrl;

		$changelink = "<a href='".$ilCtrl->getLinkTarget($this, "editMenuProperties")."'>".$lng->txt("change")."</a>";
		if (!$this->object->isActiveLMMenu())
		{
			$add = "<br />".$lng->txt("cont_download_no_menu")." ".$changelink;
		}
		else if (!$this->object->isActiveDownloads())
		{
			$add = "<br />".$lng->txt("cont_download_no_download")." ".$changelink;
		}

		
		if ($this->object->getPublicExportFile($a_type) == $a_file)
		{
			return $lng->txt("yes").$add;
		}
	
		return " ";		
	}



	/**
	* download export file
	*/
	function publishExportFile($a_files)
	{
		global $ilCtrl;
		
		if(!isset($a_files))
		{
			ilUtil::sendFailure($this->lng->txt("no_checkbox"), true);
		}
		else
		{
			foreach ($a_files as $f)
			{
				$file = explode(":", $f);
				$export_dir = $this->object->getExportDirectory($file[0]);
		
				if ($this->object->getPublicExportFile($file[0]) ==
					$file[1])
				{
					$this->object->setPublicExportFile($file[0], "");
				}
				else
				{
					$this->object->setPublicExportFile($file[0], $file[1]);
				}
			}
			$this->object->update();
		}
		$ilCtrl->redirectByClass("ilexportgui");
	}

	/**
	* download export file
	*/
	function downloadPDFFile()
	{
		if(!isset($_POST["file"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		if (count($_POST["file"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}


		$export_dir = $this->object->getOfflineDirectory();
		ilUtil::deliverFile($export_dir."/".$_POST["file"][0],
			$_POST["file"][0]);
	}


	/**
	* confirm screen for tree fixing
	*
	*/
	function fixTreeConfirm()
	{
		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm.html");

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		//
		$this->tpl->setVariable("TXT_CONFIRM", $this->lng->txt("confirmation"));
		$this->tpl->setVariable("TXT_CONTENT", $this->lng->txt("cont_fix_tree_confirm"));
		$this->tpl->setVariable("CMD_CANCEL", "cancelFixTree");
		$this->tpl->setVariable("CMD_OK", "fixTree");
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_OK", $this->lng->txt("cont_fix_tree"));
	}

	/**
	* cancel action and go back to previous page
	* @access	public
	*
	*/
	function cancelObject($in_rep = false)
	{
		ilUtil::redirect("repository.php?cmd=frameset&ref_id=".$_GET["ref_id"]);
		//$this->ctrl->redirectByClass("ilrepositorygui", "frameset");
	}

	/**
	* cancel tree fixing
	*/
	function cancelFixTree()
	{
		$this->ctrl->redirect($this, "properties");
	}

	/**
	* fix tree
	*/
	function fixTree()
	{
		$this->object->fixTree();
		ilUtil::sendSuccess($this->lng->txt("cont_tree_fixed"), true);
		$this->ctrl->redirect($this, "properties");
	}

	/**
	* get lm menu html
	*/
	function setilLMMenu($a_offline = false, $a_export_format = "",
		$a_active = "content", $a_use_global_tabs = false, $a_as_subtabs = false,
		$a_cur_page = 0)
	{
		global $ilCtrl,$ilUser, $ilAccess, $ilTabs, $rbacsystem, $ilPluginAdmin;

		
		if ($a_as_subtabs)
		{
			$addcmd = "addSubTabTarget";
			$getcmd = "getSubTabHTML";
		}
		else
		{
			$addcmd = "addTarget";
			$getcmd = "getHTML";
		}
		
		$active[$a_active] = true;

		if (!$this->object->isActiveLMMenu())
		{
			return "";
		}

		if ($a_use_global_tabs)
		{
			$tabs_gui = $ilTabs;
		}
		else
		{
			$tabs_gui = new ilTabsGUI();
		}
		
		// Determine whether the view of a learning resource should
		// be shown in the frameset of ilias, or in a separate window.
		//$showViewInFrameset = $this->ilias->ini->readVariable("layout","view_target") == "frame";
		$showViewInFrameset = true;

		if ($showViewInFrameset && !$a_offline)
		{
			$buttonTarget = ilFrameTargetInfo::_getFrame("MainContent");
		}
		else
		{
			$buttonTarget = "_top";
		}

		if ($a_export_format == "scorm")
		{
			$buttonTarget = "";
		}
		
		$requires_purchase_to_access = ilPaymentObject::_requiresPurchaseToAccess((int)$_GET['ref_id']);

		// content
		if (!$a_offline && $ilAccess->checkAccess("read", "", $_GET["ref_id"]))
		{
			$ilCtrl->setParameterByClass("illmpresentationgui", "obj_id", $_GET["obj_id"]);
			$tabs_gui->$addcmd("content",
				$ilCtrl->getLinkTargetByClass("illmpresentationgui", "layout"),
				"", "", $buttonTarget,  $active["content"]);
		}

		// table of contents
		if (!$requires_purchase_to_access && $this->object->isActiveTOC() && $ilAccess->checkAccess("read", "", $_GET["ref_id"]))
		{
			if (!$a_offline)
			{
				$ilCtrl->setParameterByClass("illmpresentationgui", "obj_id", $_GET["obj_id"]);
				$link = $ilCtrl->getLinkTargetByClass("illmpresentationgui", "showTableOfContents");
			}
			else
			{
				$link = "./table_of_contents.html";
			}
			
			$tabs_gui->$addcmd("cont_toc", $link,
					"", "", $buttonTarget, $active["toc"]);
		}

		// print view
		if (!$requires_purchase_to_access && $this->object->isActivePrintView() && $ilAccess->checkAccess("read", "", $_GET["ref_id"]))
		{
			if (!$a_offline)		// has to be implemented for offline mode
			{
				$ilCtrl->setParameterByClass("illmpresentationgui", "obj_id", $_GET["obj_id"]);
				$link = $ilCtrl->getLinkTargetByClass("illmpresentationgui", "showPrintViewSelection");
				$tabs_gui->$addcmd("cont_print_view", $link,
					"", "", $buttonTarget, $active["print"]);
			}
		}
		
		// download
		if (!$requires_purchase_to_access && $ilUser->getId() == ANONYMOUS_USER_ID)
		{
			$is_public = $this->object->isActiveDownloadsPublic();
		}
		else if(!$requires_purchase_to_access)
		{
			$is_public = true;
		}

		if (!$requires_purchase_to_access && $this->object->isActiveDownloads() && !$a_offline && $is_public &&
			$ilAccess->checkAccess("read", "", $_GET["ref_id"]))
		{
			$ilCtrl->setParameterByClass("illmpresentationgui", "obj_id", $_GET["obj_id"]);
			$link = $ilCtrl->getLinkTargetByClass("illmpresentationgui", "showDownloadList");
			$tabs_gui->$addcmd("download", $link,
				"", "", $buttonTarget, $active["download"]);
		}

		// info button
		if ($a_export_format != "scorm" && !$a_offline)
		{
			if (!$a_offline)
			{
				$ilCtrl->setParameterByClass("illmpresentationgui", "obj_id", $_GET["obj_id"]);
				$link = $this->ctrl->getLinkTargetByClass(
						array("illmpresentationgui", "ilinfoscreengui"), "showSummary");
			}
			else
			{
				$link = "./info.html";
			}
			
			$tabs_gui->$addcmd(($requires_purchase_to_access ? 'buy' : 'info_short'), $link,
					"", "", $buttonTarget, $active["info"]);
		}
		
		// edit learning module
		if (!$a_offline && $a_cur_page > 0)
		{
			if ($rbacsystem->checkAccess("write", $_GET["ref_id"]))
			{
				//$page_id = $this->getCurrentPageId();
				$page_id = $a_cur_page;
				$tabs_gui->$addcmd("edit_page", ILIAS_HTTP_PATH."/ilias.php?baseClass=ilLMEditorGUI&ref_id=".$_GET["ref_id"].
					"&obj_id=".$page_id."&to_page=1",
					"", "", $buttonTarget, $active["edit_page"]);
			}
		}
		
		if(!$requires_purchase_to_access)
		{
			// get user defined menu entries
			$this->__initLMMenuEditor();
			$entries = $this->lmme_obj->getMenuEntries(true);
	
			if (count($entries) > 0 && $ilAccess->checkAccess("read", "", $_GET["ref_id"]))
			{
				foreach ($entries as $entry)
				{
					// build goto-link for internal resources
					if ($entry["type"] == "intern")
					{
						$entry["link"] = ILIAS_HTTP_PATH."/goto.php?target=".$entry["link"];
					}
	
					// add http:// prefix if not exist
					if (!strstr($entry["link"],'://') && !strstr($entry["link"],'mailto:'))
					{
						$entry["link"] = "http://".$entry["link"];
					}
					
					if (!strstr($entry["link"],'mailto:'))
					{
						$entry["link"] = ilUtil::appendUrlParameterString($entry["link"], "ref_id=".$this->ref_id."&structure_id=".$this->obj_id);
					}
					$tabs_gui->$addcmd($entry["title"],
						$entry["link"],
						"", "", "_blank", "", true);
				}
			}
		}

		// user interface hook [uihk]
		$pl_names = $ilPluginAdmin->getActivePluginsForSlot(IL_COMP_SERVICE, "UIComponent", "uihk");
		$plugin_html = false;
		foreach ($pl_names as $pl)
		{
			$ui_plugin = ilPluginAdmin::getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", $pl);
			$gui_class = $ui_plugin->getUIClassInstance();
			$resp = $gui_class->modifyGUI("Modules/LearningModule", "lm_menu_tabs",
				array("lm_menu_tabs" => $tabs_gui));
		}


		return $tabs_gui->$getcmd();
	}

	/**
	* export content object
	*/
	function createPDF()
	{
		require_once("./Modules/LearningModule/classes/class.ilContObjectExport.php");
		$cont_exp = new ilContObjectExport($this->object, "pdf");
		$cont_exp->buildExportFile();
		$this->offlineList();
	}

	/**
	 * create html package
	 */
	function exportHTML()
	{
		require_once("./Modules/LearningModule/classes/class.ilContObjectExport.php");
		$cont_exp = new ilContObjectExport($this->object, "html");
		$cont_exp->buildExportFile();
//echo $this->tpl->get();
//		$this->ctrl->redirect($this, "exportList");
	}

	/**
	* create scorm package
	*/
	function exportSCORM()
	{
		require_once("./Modules/LearningModule/classes/class.ilContObjectExport.php");
		$cont_exp = new ilContObjectExport($this->object, "scorm");
		$cont_exp->buildExportFile();
//echo $this->tpl->get();
//		$this->ctrl->redirect($this, "exportList");
	}

	/**
	* display locator
	*
	* @param	boolean		$a_omit_obj_id	set to true, if obj id is not page id (e.g. permission gui)
	*/
	function addLocations($a_omit_obj_id = false)
	{
		global $lng, $tree, $ilLocator;

		//$ilLocator->clearItems();

		$this->ctrl->addLocation(
			"...",
			"");

		$par_id = $tree->getParentId($_GET["ref_id"]);
		$this->ctrl->addLocation(
			ilObject::_lookupTitle(ilObject::_lookupObjId($par_id)),
			"repository.php?cmd=frameset&amp;ref_id=".$par_id,
			ilFrameTargetInfo::_getFrame("MainContent"), $par_id);
		if (!$a_omit_obj_id)
		{
			$obj_id = $_GET["obj_id"];
		}
		$lmtree =& $this->object->getTree();

		if (($obj_id != 0) && $lmtree->isInTree($obj_id))
		{
			$path = $lmtree->getPathFull($obj_id);
		}
		else
		{
			$path = $lmtree->getPathFull($lmtree->getRootId());
			if ($obj_id != 0)
			{
				$path[] = array("type" => "pg", "child" => $this->obj_id,
					"title" => ilLMPageObject::_getPresentationTitle($this->obj_id));
			}
		}

		$modifier = 1;

		foreach ($path as $key => $row)
		{
			if ($row["child"] == 1)
			{
				$this->ctrl->setParameter($this, "obj_id", "");
				$this->ctrl->addLocation(
					$this->object->getTitle(),
					$this->ctrl->getLinkTarget($this, "chapters"), "", $_GET["ref_id"]);
			}
			else
			{
				$title = $row["title"];
				switch($row["type"])
				{
					case "st":
						$this->ctrl->setParameterByClass("ilstructureobjectgui", "obj_id", $row["child"]);
						$this->ctrl->addLocation(
							$title,
							$this->ctrl->getLinkTargetByClass("ilstructureobjectgui", "view"));
						break;

					case "pg":
						$this->ctrl->setParameterByClass("illmpageobjectgui", "obj_id", $row["child"]);
						$this->ctrl->addLocation(
							$title,
							$this->ctrl->getLinkTargetByClass("illmpageobjectgui", "edit"));
						break;
				}
			}
		}
		if (!$a_omit_obj_id)
		{
			$this->ctrl->setParameter($this, "obj_id", $_GET["obj_id"]);
		}
	}

	////
	//// Questions
	////


	/**
	 * List questions
	 *
	 * @param
	 * @return
	 */
	function listQuestions()
	{
		global $tpl;

		$this->setTabs();
		
		include_once("./Modules/LearningModule/classes/class.ilLMQuestionListTableGUI.php");
		$table = new ilLMQuestionListTableGUI($this, "listQuestions", $this->object);
		$tpl->setContent($table->getHTML());

	}

	////
	//// Tabs
	////


	/**
	* output tabs
	*/
	function setTabs()
	{
		// catch feedback message
		#include_once("classes/class.ilTabsGUI.php");
		#$tabs_gui =& new ilTabsGUI();
		$this->getTabs($this->tabs_gui);
		$this->tpl->setCurrentBlock("header_image");
		$this->tpl->setVariable("IMG_HEADER", ilUtil::getImagePath("icon_lm_b.gif"));
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("content");
		#$this->tpl->setVariable("TABS", $tabs_gui->getHTML());
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
	}

	/**
	 * Set link sub tabs
	 *
	 * @param
	 * @return
	 */
	function setLinkSubTabs($a_active)
	{
		global $ilTabs, $lng, $ilCtrl;

		$ilTabs->addSubtab("internal_links",
			$lng->txt("cont_internal_links"),
			$ilCtrl->getLinkTarget($this, "listLinks"));

		if ($this->object->getType() == "lm")
		{
			if(@include_once('HTTP/Request.php'))
			{
				$ilTabs->addSubtab("link_check",
					$lng->txt("link_check"),
					$ilCtrl->getLinkTarget($this, "linkChecker"));
			}
		}

		$ilTabs->activateSubTab($a_active);
		$ilTabs->activateTab("cont_links");
	}


	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		global $rbacsystem,$ilUser;

		// back to upper context
		//$tabs_gui->getTargetsByObjectType($this, $this->object->getType());

		// chapters
		$tabs_gui->addTarget("cont_chapters",
			$this->ctrl->getLinkTarget($this, "chapters"),
			"chapters", get_class($this));

		// pages
		$tabs_gui->addTarget("cont_all_pages",
			$this->ctrl->getLinkTarget($this, "pages"),
			"pages", get_class($this));

		// questions
		$tabs_gui->addTarget("objs_qst",
			$this->ctrl->getLinkTarget($this, "listQuestions"),
			"listQuestions", get_class($this));

		// info
		$tabs_gui->addTarget("info_short",
			$this->ctrl->getLinkTargetByClass("ilinfoscreengui",'showSummary'),
			"", "ilinfoscreengui");
			
		// properties
		$tabs_gui->addTarget("settings",
			$this->ctrl->getLinkTarget($this,'properties'),
			"properties", get_class($this));

		// learning progress
		include_once './Services/Tracking/classes/class.ilLearningProgressAccess.php';
		if(ilLearningProgressAccess::checkAccess($this->object->getRefId()) and ($this->object->getType() == 'lm' or $this->object->getType() == 'dbk'))
		{
			$tabs_gui->addTarget('learning_progress',
								 $this->ctrl->getLinkTargetByClass(array('illearningprogressgui'),''),
								 '',
								 array('illplistofobjectsgui','illplistofsettingsgui','illearningprogressgui','illplistofprogressgui','illmstatisticsgui'));
		}
			
		// links
		$tabs_gui->addTarget("cont_links",
			$this->ctrl->getLinkTarget($this,'listLinks'),
			"listLinks", get_class($this));

		if ($this->object->getType() != "lm")
		{
			// bibliographical data
			$tabs_gui->addTarget("bib_data",
				$this->ctrl->getLinkTarget($this, "editBibItem"),
				"editBibItem", get_class($this));
		}
		
		$tabs_gui->addTarget("history", $this->ctrl->getLinkTarget($this, "history")
			, "history", get_class($this));

		$tabs_gui->addTarget("meta_data",
			$this->ctrl->getLinkTargetByClass('ilmdeditorgui',''),
			"", "ilmdeditorgui");

		if ($this->object->getType() == "lm")
		{				
			// export
			$tabs_gui->addTarget("export",
				$this->ctrl->getLinkTargetByClass("ilexportgui", ""),
				"", "ilexportgui");		
		}
		
		// permissions
		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), array("perm","info","owner"), 'ilpermissiongui');
		}
	}

	/**
	* Set sub tabs
	*/
	function setSubTabs($a_active)
	{
		global $ilTabs, $ilSetting;

		if (in_array($a_active,
			array("cont_general_properties", "cont_style", "cont_lm_menu", "public_section")))
		{
			// general properties
			$ilTabs->addSubTabTarget("cont_general_properties",
				$this->ctrl->getLinkTarget($this, 'properties'),
				"", "");
				
			// style properties
			$ilTabs->addSubTabTarget("cont_style",
				$this->ctrl->getLinkTarget($this, 'editStyleProperties'),
				"", "");

			// menu properties
			$ilTabs->addSubTabTarget("cont_lm_menu",
				$this->ctrl->getLinkTarget($this, 'editMenuProperties'),
				"", "");
				
			if ($ilSetting->get("pub_section"))
			{
				if ($this->object->getType() != "dbk")
				{
					// public section
					$ilTabs->addSubTabTarget("public_section",
						$this->ctrl->getLinkTarget($this, 'editPublicSection'),
						"", "");
				}
			}
				
			$ilTabs->setSubTabActive($a_active);
		}
	}

	function editPublicSection()
	{
		global $ilTabs;
		
		$this->setTabs();
		$this->setSubTabs("public_section");
		$ilTabs->setTabActive("settings");

		switch ($this->object->getType())
		{
			case "lm":
				$gui_class = "ilobjlearningmodulegui";
				break;

			case "dlb":
				$gui_class = "ilobjdlbookgui";
				break;
		}

		// get learning module object
		$this->lm_obj =& new ilObjLearningModule($this->ref_id, true);

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_public_selector.html");
		//$this->tpl->setVariable("ADM_CONTENT", "adm_content", "bla");

		require_once ("./Modules/LearningModule/classes/class.ilPublicSectionSelector.php");
		$exp = new ilPublicSectionSelector($this->ctrl->getLinkTarget($this, "view"),
			$this->object, $gui_class);

		$exp->setTargetGet("obj_id");

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();

		// get page ids
		foreach ($exp->format_options as $node)
		{
			if (!$node["container"])
			{
				$pages[] = $node["child"];
			}
		}

		$js_pages = ilUtil::array_php2js($pages);

		//$this->tpl->setCurrentBlock("content");
		//var_dump($this->object->getPublicAccessMode());
		// access mode selector
		$this->tpl->setVariable("TXT_SET_PUBLIC_MODE", $this->lng->txt("set_public_mode"));
		$this->tpl->setVariable("TXT_CHOOSE_PUBLIC_MODE", $this->lng->txt("choose_public_mode"));
		$modes = array("complete" => $this->lng->txt("all_pages"), "selected" => $this->lng->txt("selected_pages_only"));
		$select_public_mode = ilUtil::formSelect ($this->object->getPublicAccessMode(),"lm_public_mode",$modes, false, true);
		$this->tpl->setVariable("SELECT_PUBLIC_MODE", $select_public_mode);

		$this->tpl->setVariable("TXT_EXPLORER_HEADER", $this->lng->txt("choose_public_pages"));
		$this->tpl->setVariable("EXP_REFRESH", $this->lng->txt("refresh"));
		$this->tpl->setVariable("EXPLORER",$output);
		$this->tpl->setVariable("ONCLICK",$js_pages);
		$this->tpl->setVariable("TXT_CHECKALL", $this->lng->txt("check_all"));
		$this->tpl->setVariable("TXT_UNCHECKALL", $this->lng->txt("uncheck_all"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getLinkTarget($this, "savePublicSection"));
		//$this->tpl->parseCurrentBlock();

	}

	function savePublicSection()
	{
		//var_dump($_POST["lm_public_mode"]);exit;
		$this->object->setPublicAccessMode($_POST["lm_public_mode"]);
		$this->object->updateProperties();
		ilLMObject::_writePublicAccessStatus($_POST["pages"],$this->object->getId());
		ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
		$this->ctrl->redirect($this, "editPublicSection");
	}

	/**
	* history
	*
	* @access	public
	*/
	function history()
	{
		$this->setTabs();

		require_once("classes/class.ilHistoryGUI.php");
		$hist_gui =& new ilHistoryGUI($this->object->getId() ,
			$this->object->getType());
		$hist_html = $hist_gui->getHistoryTable(
			$this->ctrl->getParameterArray($this, "history"),
			$this->object->isActiveHistoryUserComments()
			);

		$this->tpl->setVariable("ADM_CONTENT", $hist_html);
	}
	
	/**
	 * 
	 * @see		ilLinkCheckerGUIRowHandling::formatInvalidLinkArray()
	 * @param	array Unformatted array
	 * @return	array Formatted array
	 * @access	public
	 * 
	 */
	public function formatInvalidLinkArray(Array $row)
	{
		$row['title'] =  ilLMPageObject::_getPresentationTitle($row['page_id'], $this->object->getPageHeader());
	
		require_once 'Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php';
		$actions = new ilAdvancedSelectionListGUI();
		$actions->setSelectionHeaderClass('small');	
		$actions->setItemLinkClass('xsmall');		
		$actions->setListTitle($this->lng->txt('actions'));		
		$actions->setId($row['page_id']);
		$this->ctrl->setParameterByClass('ilLMPageObjectGUI', 'obj_id', $row['page_id']);
		$actions->addItem(
			$this->lng->txt('edit'),
			'',
			$this->ctrl->getLinkTargetByClass('ilLMPageObjectGUI', 'edit')
		);
		$this->ctrl->clearParametersByClass('ilLMPageObjectGUI');
		$row['action_html'] = $actions->getHTML();		
		
		return $row;
	}

	function linkChecker()
	{
		global $ilias, $ilUser, $tpl;

		$this->__initLinkChecker();

		$this->setTabs();
		$this->setLinkSubTabs("link_check");
		
		require_once 'Services/LinkChecker/classes/class.ilLinkCheckerTableGUI.php';
		
		$toolbar = new ilToolbarGUI();
		
		if((bool)$ilias->getSetting('cron_web_resource_check'))
		{
			include_once 'classes/class.ilLinkCheckNotify.php';
			include_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
			
			$chb = new ilCheckboxInputGUI($this->lng->txt('link_check_message_a'), 'link_check_message');
			$chb->setValue(1);
			$chb->setChecked((bool)ilLinkCheckNotify::_getNotifyStatus($ilUser->getId(),$this->object->getId()));
			$chb->setOptionTitle($this->lng->txt('link_check_message_b'));
			
			$toolbar->addInputItem($chb);
			$toolbar->addFormButton($this->lng->txt('save'), 'saveLinkCheck');
			$toolbar->setFormAction($this->ctrl->getLinkTarget($this, 'saveLinkCheck'));
		}
		
		$tgui = new ilLinkCheckerTableGUI($this, 'linkChecker');
		$tgui->setLinkChecker($this->link_checker_obj)
			 ->setRowHandler($this)
			 ->setRefreshButton($this->lng->txt('refresh'), 'refreshLinkCheck');
		
		return $tpl->setContent($tgui->prepareHTML()->getHTML().$toolbar->getHTML());
	}
	
	function saveLinkCheck()
	{
		global $ilDB,$ilUser;

		include_once './classes/class.ilLinkCheckNotify.php';

		$link_check_notify =& new ilLinkCheckNotify($ilDB);
		$link_check_notify->setUserId($ilUser->getId());
		$link_check_notify->setObjId($this->object->getId());

		if($_POST['link_check_message'])
		{
			ilUtil::sendSuccess($this->lng->txt('link_check_message_enabled'));
			$link_check_notify->addNotifier();
		}
		else
		{
			ilUtil::sendSuccess($this->lng->txt('link_check_message_disabled'));
			$link_check_notify->deleteNotifier();
		}
		$this->linkChecker();

		return true;
	}



	function refreshLinkCheck()
	{
		$this->__initLinkChecker();

		if(!$this->link_checker_obj->checkPear())
		{
			ilUtil::sendFailure($this->lng->txt('missing_pear_library'));
			$this->linkChecker();

			return false;
		}

		$this->link_checker_obj->checkLinks();
		ilUtil::sendSuccess($this->lng->txt('link_checker_refreshed'));

		$this->linkChecker();

		return true;
	}

	function __initLinkChecker()
	{
		global $ilDB;

		include_once './classes/class.ilLinkChecker.php';

		$this->link_checker_obj =& new ilLinkChecker($ilDB,false);
		$this->link_checker_obj->setObjId($this->object->getId());

		return true;
	}

	function __initLMMenuEditor()
	{
		include_once './Modules/LearningModule/classes/class.ilLMMenuEditor.php';

		$this->lmme_obj =& new ilLMMenuEditor();
		$this->lmme_obj->setObjId($this->object->getId());

		return true;
	}

	/**
	* display add menu entry form
	*/
	function addMenuEntry()
	{
		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_menu_entry_form.html","Modules/LearningModule");

		if (isset($_GET["link_ref_id"]))
		{
			$obj_type = ilObject::_lookupType($_GET["link_ref_id"],true);
			$obj_id = ilObject::_lookupObjectId($_GET["link_ref_id"]);
			$title = ilObject::_lookupTitle($obj_id);

			$target_link = $obj_type."_".$_GET["link_ref_id"];
			$this->tpl->setVariable("TITLE", $title);
			$this->tpl->setVariable("TARGET", $target_link);
			$this->tpl->setVariable("LINK_REF_ID", $_GET["link_ref_id"]);
		}


		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this, "saveMenuEntry"));
		$this->tpl->setVariable("TXT_NEW_ENTRY", $this->lng->txt("lm_menu_new_entry"));
		$this->tpl->setVariable("TXT_TARGET", $this->lng->txt("lm_menu_entry_target"));
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("lm_menu_entry_title"));
		$this->tpl->setVariable("BTN_NAME", "saveMenuEntry");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->setVariable("BTN_NAME2", "showEntrySelector");
		$this->tpl->setVariable("BTN_TEXT2", $this->lng->txt("lm_menu_select_internal_object"));
		//$this->tpl->parseCurrentBlock();

	}

	/**
	* save new menu entry
	*/
	function saveMenuEntry()
	{
		global $ilCtrl;
		
		// check title and target
		if (empty($_POST["title"]))
		{
			//$this->ilias->raiseError($this->lng->txt("please_enter_title"),$this->ilias->error_obj->MESSAGE);
			ilUtil::sendFailure($this->lng->txt("please_enter_title") , true);
			$ilCtrl->redirect($this, "addMenuEntry");
		}
		if (empty($_POST["target"]))
		{
			//$this->ilias->raiseError($this->lng->txt("please_enter_target"),$this->ilias->error_obj->MESSAGE);
			ilUtil::sendFailure($this->lng->txt("please_enter_target"), true);
			$ilCtrl->redirect($this, "addMenuEntry");
		}

		$this->__initLMMenuEditor();
		$this->lmme_obj->setTitle($_POST["title"]);
		$this->lmme_obj->setTarget($_POST["target"]);
		$this->lmme_obj->setLinkRefId($_POST["link_ref_id"]);

		if ($_POST["link_ref_id"])
		{
			$this->lmme_obj->setLinkType("intern");
		}

		$this->lmme_obj->create();

		ilUtil::sendSuccess($this->lng->txt("msg_entry_added"), true);
		$this->ctrl->redirect($this, "editMenuProperties");
	}

	/**
	* drop a menu entry
	*/
	function deleteMenuEntry()
	{
		if (empty($_GET["menu_entry"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_menu_entry_id"),$this->ilias->error_obj->MESSAGE);
		}

		$this->__initLMMenuEditor();
		$this->lmme_obj->delete($_GET["menu_entry"]);

		ilUtil::sendSuccess($this->lng->txt("msg_entry_removed"), true);
		$this->ctrl->redirect($this, "editMenuProperties");
	}

	/**
	* edit menu entry form
	*/
	function editMenuEntry()
	{
		if (empty($_GET["menu_entry"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_menu_entry_id"),$this->ilias->error_obj->MESSAGE);
		}

		$this->__initLMMenuEditor();
		$this->lmme_obj->readEntry($_GET["menu_entry"]);

		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_menu_entry_form.html","Modules/LearningModule");

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_NEW_ENTRY", $this->lng->txt("lm_menu_edit_entry"));
		$this->tpl->setVariable("TXT_TARGET", $this->lng->txt("lm_menu_entry_target"));
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("lm_menu_entry_title"));
		$this->tpl->setVariable("TITLE", $this->lmme_obj->getTitle());
		$this->tpl->setVariable("TARGET", $this->lmme_obj->getTarget());
		$this->tpl->setVariable("ENTRY_ID", $this->lmme_obj->getEntryId());
		$this->tpl->setVariable("BTN_NAME", "updateMenuEntry");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->setVariable("BTN_NAME2", "showEntrySelector");
		$this->tpl->setVariable("BTN_TEXT2", $this->lng->txt("lm_menu_select_internal_object"));
		//$this->tpl->parseCurrentBlock();
	}

	/**
	* update a menu entry
	*/
	function updateMenuEntry()
	{
		if (empty($_POST["menu_entry"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_menu_entry_id"),$this->ilias->error_obj->MESSAGE);
		}

		// check title and target
		if (empty($_POST["title"]))
		{
			$this->ilias->raiseError($this->lng->txt("please_enter_title"),$this->ilias->error_obj->MESSAGE);
		}
		if (empty($_POST["target"]))
		{
			$this->ilias->raiseError($this->lng->txt("please_enter_target"),$this->ilias->error_obj->MESSAGE);
		}

		$this->__initLMMenuEditor();
		$this->lmme_obj->readEntry($_POST["menu_entry"]);
		$this->lmme_obj->setTitle($_POST["title"]);
		$this->lmme_obj->setTarget($_POST["target"]);
		$this->lmme_obj->update();

		ilUtil::sendSuccess($this->lng->txt("msg_entry_updated"), true);
		$this->ctrl->redirect($this, "editMenuProperties");
	}

	function showEntrySelector()
	{
		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_menu_object_selector.html","Modules/LearningModule");

		ilUtil::sendInfo($this->lng->txt("lm_menu_select_object_to_add"));

		require_once ("./Modules/LearningModule/classes/class.ilLMMenuObjectSelector.php");
		$exp = new ilLMMenuObjectSelector($this->ctrl->getLinkTarget($this,'test'),$this);

		$exp->setExpand($_GET["lm_menu_expand"] ? $_GET["lm_menu_expand"] : $this->tree->readRootId());
		$exp->setExpandTarget($this->ctrl->getLinkTarget($this,'showEntrySelector'));
		$exp->setTargetGet("ref_id");
		$exp->setRefId($this->cur_ref_id);

		$sel_types = array('lm','dbk','glo','frm','exc','tst','svy', 'chat', 'wiki', 'sahs');
		$exp->setSelectableTypes($sel_types);

		//$exp->setTargetGet("obj_id");

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();

		// get page ids
		foreach ($exp->format_options as $node)
		{
			if (!$node["container"])
			{
				$pages[] = $node["child"];
			}
		}

		//$this->tpl->setCurrentBlock("content");
		//var_dump($this->object->getPublicAccessMode());
		// access mode selector
		$this->tpl->setVariable("TXT_SET_PUBLIC_MODE", $this->lng->txt("set_public_mode"));
		$this->tpl->setVariable("TXT_CHOOSE_PUBLIC_MODE", $this->lng->txt("choose_public_mode"));
		$modes = array("complete" => $this->lng->txt("all_pages"), "selected" => $this->lng->txt("selected_pages_only"));
		$select_public_mode = ilUtil::formSelect ($this->object->getPublicAccessMode(),"lm_public_mode",$modes, false, true);
		$this->tpl->setVariable("SELECT_PUBLIC_MODE", $select_public_mode);

		$this->tpl->setVariable("TXT_EXPLORER_HEADER", $this->lng->txt("choose_public_pages"));
		$this->tpl->setVariable("EXP_REFRESH", $this->lng->txt("refresh"));
		$this->tpl->setVariable("EXPLORER",$output);
		$this->tpl->setVariable("ONCLICK",$js_pages);
		$this->tpl->setVariable("TXT_CHECKALL", $this->lng->txt("check_all"));
		$this->tpl->setVariable("TXT_UNCHECKALL", $this->lng->txt("uncheck_all"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getLinkTarget($this, "savePublicSection"));
		//$this->tpl->parseCurrentBlock();
	}

	/**
	* select page as header
	*/
	function selectHeader()
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}
		if ($_POST["id"][0] != $this->object->getHeaderPage())
		{
			$this->object->setHeaderPage($_POST["id"][0]);
		}
		else
		{
			$this->object->setHeaderPage(0);
		}
		$this->object->updateProperties();
		$this->ctrl->redirect($this, "pages");
	}

	/**
	* select page as footer
	*/
	function selectFooter()
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}
		if ($_POST["id"][0] != $this->object->getFooterPage())
		{
			$this->object->setFooterPage($_POST["id"][0]);
		}
		else
		{
			$this->object->setFooterPage(0);
		}
		$this->object->updateProperties();
		$this->ctrl->redirect($this, "pages");
	}

	/**
	* Save all titles of chapters/pages
	*/
	function saveAllTitles()
	{
		global $ilCtrl;
		
		ilLMObject::saveTitles($this->object, ilUtil::stripSlashesArray($_POST["title"]));
		
		$ilCtrl->redirect($this, "chapters");
	}

	/**
	* Insert (multiple) chapters at node
	*/
	function insertChapter()
	{
		global $ilCtrl, $lng;
		
		include_once("./Modules/LearningModule/classes/class.ilChapterHierarchyFormGUI.php");
		
		$num = ilChapterHierarchyFormGUI::getPostMulti();
		$node_id = ilChapterHierarchyFormGUI::getPostNodeId();
		
		if (!ilChapterHierarchyFormGUI::getPostFirstChild())	// insert after node id
		{
			$parent_id = $this->lm_tree->getParentId($node_id);
			$target = $node_id;
		}
		else													// insert as first child
		{
			$parent_id = $node_id;
			$target = IL_FIRST_NODE;
		}

		for ($i = 1; $i <= $num; $i++)
		{
			$chap = new ilStructureObject($this->object);
			$chap->setType("st");
			$chap->setTitle($lng->txt("cont_new_chap"));
			$chap->setLMId($this->object->getId());
			$chap->create();
			ilLMObject::putInTree($chap, $parent_id, $target);
		}

		$ilCtrl->redirect($this, "chapters");
	}
	
	/**
	* Insert Chapter from clipboard
	*/
	function insertChapterClip()
	{
		global $ilUser, $ilCtrl, $ilLog;
		
		include_once("./Modules/LearningModule/classes/class.ilChapterHierarchyFormGUI.php");
		
		if ($ilUser->getPref("lm_js_chapter_editing") != "disable")
		{
			//$num = ilChapterHierarchyFormGUI::getPostMulti();
			$node_id = ilChapterHierarchyFormGUI::getPostNodeId();
			$first_child = ilChapterHierarchyFormGUI::getPostFirstChild();
		}
		else
		{
			if (!isset($_POST["id"]) || $_POST["id"][0] == -1)
			{
				$node_id = $this->lm_tree->getRootId();
				$first_child = true;
			}
			else
			{
				$node_id = $_POST["id"][0];
				$first_child = false;
			}
		}

		$ilLog->write("InsertChapterClip, num: $num, node_id: $node_id, ".
			" getPostFirstChild ".ilChapterHierarchyFormGUI::getPostFirstChild());

		if (!$first_child)	// insert after node id
		{
			$parent_id = $this->lm_tree->getParentId($node_id);
			$target = $node_id;
		}
		else													// insert as first child
		{
			$parent_id = $node_id;
			$target = IL_FIRST_NODE;
		}
		
		// copy and paste
		$chapters = $ilUser->getClipboardObjects("st", true);
		$copied_nodes = array();
		foreach ($chapters as $chap)
		{
			$ilLog->write("Call pasteTree, Target LM: ".$this->object->getId().", Chapter ID: ".$chap["id"]
				.", Parent ID: ".$parent_id.", Target: ".$target);
			$cid = ilLMObject::pasteTree($this->object, $chap["id"], $parent_id,
				$target, $chap["insert_time"], $copied_nodes,
				(ilEditClipboard::getAction() == "copy"));
			$target = $cid;
		}
		ilLMObject::updateInternalLinks($copied_nodes);

		if (ilEditClipboard::getAction() == "cut")
		{
			$ilUser->clipboardDeleteObjectsOfType("pg");
			$ilUser->clipboardDeleteObjectsOfType("st");
			ilEditClipboard::clear();
		}
		
		$this->object->checkTree();
		$ilCtrl->redirect($this, "chapters");
	}

	/**
	* redirect script
	*
	* @param	string		$a_target
	*/
	function _goto($a_target)
	{
		global $ilAccess, $ilErr, $lng;

		if ($ilAccess->checkAccess("read", "", $a_target))
		{
			$_GET["baseClass"] = "ilLMPresentationGUI";
			$_GET["ref_id"] = $a_target;
			include("ilias.php");
			exit;
		} else if ($ilAccess->checkAccess("visible", "", $a_target))
		{
			$_GET["baseClass"] = "ilLMPresentationGUI";
			$_GET["ref_id"] = $a_target;
			$_GET["cmd"] = "infoScreen";
			include("ilias.php");
			exit;
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

	/**
	* Copy items to clipboard, then cut them from the current tree
	*/
	function cutItems($a_return = "chapters")
	{
		global $ilCtrl, $lng;
		
		$items = ilUtil::stripSlashesArray($_POST["id"]);
		if (!is_array($items))
		{
			ilUtil::sendFailure($lng->txt("no_checkbox"), true);
			$ilCtrl->redirect($this, $a_return);
		}

		$todel = array();			// delete IDs < 0 (needed for non-js editing)
		foreach($items as $k => $item)
		{
			if ($item < 0)
			{
				$todel[] = $k;
			}
		}
		foreach($todel as $k)
		{
			unset($items[$k]);
		}
		ilLMObject::clipboardCut($this->object->getId(), $items);
		ilEditClipboard::setAction("cut");
		ilUtil::sendInfo($lng->txt("cont_selected_items_have_been_cut"), true);
		
		$ilCtrl->redirect($this, $a_return);
	}

	/**
	* Copy items to clipboard
	*/
	function copyItems()
	{
		global $ilCtrl, $lng;
		
		$items = ilUtil::stripSlashesArray($_POST["id"]);
		if (!is_array($items))
		{
			ilUtil::sendFailure($lng->txt("no_checkbox"), true);
			$ilCtrl->redirect($this, "chapters");
		}

		$todel = array();				// delete IDs < 0 (needed for non-js editing)
		foreach($items as $k => $item)
		{
			if ($item < 0)
			{
				$todel[] = $k;
			}
		}
		foreach($todel as $k)
		{
			unset($items[$k]);
		}
		ilLMObject::clipboardCopy($this->object->getId(), $items);
		ilEditClipboard::setAction("copy");
		ilUtil::sendInfo($lng->txt("cont_selected_items_have_been_copied"), true);
		$ilCtrl->redirect($this, "chapters");
	}

	/**
	* Cut chapter(s)
	*/
	function cutChapter()
	{
		$this->cutItems("chapters");
	}

} // END class.ilObjContentObjectGUI
?>
