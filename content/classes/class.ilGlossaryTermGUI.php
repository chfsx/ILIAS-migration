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

require_once("content/classes/class.ilGlossaryTerm.php");

/**
* GUI class for glossary terms
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilGlossaryTermGUI
{
	var $ilias;
	var $lng;
	var $tpl;
	var $glossary;
	var $term;

	/**
	* Constructor
	* @access	public
	*/
	function ilGlossaryTermGUI($a_id = 0)
	{
		global $lng, $ilias, $tpl, $ilCtrl;

		$this->lng =& $lng;
		$this->ilias =& $ilias;
		$this->tpl =& $tpl;
		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this, array("term_id"));

		if($a_id != 0)
		{
			$this->term =& new ilGlossaryTerm($a_id);
		}
	}


	/**
	* get forward classes
	*/
	function _forwards()
	{
		return array("ilTermDefinitionEditorGUI");
	}


	/**
	* execute command
	*/
	function executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch ($next_class)
		{

			case "iltermdefinitioneditorgui":
				$def_edit =& new ilTermDefinitionEditorGUI();
				$ret =& $def_edit->executeCommand();
				break;

			default:
				$ret =& $this->$cmd();
				break;
		}
	}


	function setGlossary(&$a_glossary)
	{
		$this->glossary =& $a_glossary;
	}

	/**
	* form for new content object creation
	*/
	function create()
	{
		global $ilUser;

		$this->getTemplate();
		$this->tpl->setVariable("HEADER", $this->lng->txt("cont_new_term"));
		$this->setTabs();

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.glossary_term_new.html", true);
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_new_term"));
		$this->tpl->setVariable("TXT_TERM", $this->lng->txt("cont_term"));
		$this->tpl->setVariable("INPUT_TERM", "term");
		$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		$lang = ilMetaData::getLanguages();

		if ($_SESSION["il_text_lang_".$_GET["ref_id"]] != "")
		{
			$s_lang = $_SESSION["il_text_lang_".$_GET["ref_id"]];
		}
		else
		{
			$s_lang = $ilUser->getLanguage();
		}

		$select_language = ilUtil::formSelect ($s_lang, "term_language",$lang,false,true);
		$this->tpl->setVariable("SELECT_LANGUAGE", $select_language);
		$this->tpl->setVariable("BTN_NAME", "saveTerm");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
	}

	/**
	* save term
	*/
	function saveTerm()
	{
		$term =& new ilGlossaryTerm();
		$term->setGlossary($this->glossary);
		$term->setTerm($_POST["term"]);
		$term->setLanguage($_POST["term_language"]);
		$_SESSION["il_text_lang_".$_GET["ref_id"]] = $_POST["term_language"];
		$term->create();

		sendinfo($this->lng->txt("cont_added_term"),true);
		$this->ctrl->returnToParent($this);
	}


	/**
	* edit term
	*/
	function editTerm()
	{
		$this->getTemplate();
		$this->setTabs();
		$this->tpl->setVariable("HEADER", $this->lng->txt("cont_term").": ".$this->term->getTerm());

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.glossary_term_edit.html", true);
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_edit_term"));
		$this->tpl->setVariable("TXT_TERM", $this->lng->txt("cont_term"));
		$this->tpl->setVariable("INPUT_TERM", "term");
		$this->tpl->setVariable("VALUE_TERM", htmlspecialchars($this->term->getTerm()));
		$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		$lang = ilMetaData::getLanguages();
		$select_language = ilUtil::formSelect ($this->term->getLanguage(),"term_language",$lang,false,true);
		$this->tpl->setVariable("SELECT_LANGUAGE", $select_language);
		$this->tpl->setVariable("BTN_NAME", "updateTerm");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
	}


	/**
	* update term
	*/
	function updateTerm()
	{
		$this->term->setTerm($_POST["term"]);
		$this->term->setLanguage($_POST["term_language"]);
		$this->term->update();
		sendinfo($this->lng->txt("msg_obj_modified"),true);
		$this->ctrl->redirect($this, "listDefinitions");
	}

	function output()
	{
		require_once("content/classes/class.ilGlossaryDefinition.php");
		require_once("content/classes/Pages/class.ilPageObjectGUI.php");
		$defs = ilGlossaryDefinition::getDefinitionList($this->term->getId());

		$this->tpl->setVariable("TXT_TERM", $this->term->getTerm());

		for($j=0; $j<count($defs); $j++)
		{
			$def = $defs[$j];
			$page =& new ilPageObject("gdf", $def["id"]);
			$page_gui =& new ilPageObjectGUI($page);
			//$page_gui->setOutputMode("edit");
			//$page_gui->setPresentationTitle($this->term->getTerm());
			$page_gui->setTemplateOutput(false);
			$output = $page_gui->preview();

			if (count($defs) > 1)
			{
				$this->tpl->setCurrentBlock("definition_header");
						$this->tpl->setVariable("TXT_DEFINITION",
				$this->lng->txt("cont_definition")." ".($j+1));
				$this->tpl->parseCurrentBlock();
			}

			if ($j > 0)
			{
				$this->tpl->setCurrentBlock("up");
				$this->tpl->setVariable("TXT_UP", $this->lng->txt("up"));
				$this->tpl->setVariable("LINK_UP",
					"glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=moveUp&def=".$def["id"]);
				$this->tpl->parseCurrentBlock();
			}

			if ($j+1 < count($defs))
			{
				$this->tpl->setCurrentBlock("down");
				$this->tpl->setVariable("TXT_DOWN", $this->lng->txt("down"));
				$this->tpl->setVariable("LINK_DOWN",
					"glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=moveDown&def=".$def["id"]);
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("definition");
			$this->tpl->setVariable("PAGE_CONTENT", $output);
			$this->tpl->setVariable("TXT_EDIT", $this->lng->txt("edit"));
			$this->tpl->setVariable("LINK_EDIT",
				"glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=view&def=".$def["id"]);
			$this->tpl->setVariable("TXT_DELETE", $this->lng->txt("delete"));
			$this->tpl->setVariable("LINK_DELETE",
				"glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=confirmDefinitionDeletion&def=".$def["id"]);
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* list definitions
	*/
	function listDefinitions()
	{
		$this->getTemplate();
		$this->setTabs();

		require_once("content/classes/Pages/class.ilPageObjectGUI.php");

		$this->tpl->setCurrentBlock("ContentStyle");
		$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
			ilObjStyleSheet::getContentStylePath(0));
		$this->tpl->parseCurrentBlock();

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "def_list", "tpl.glossary_definition_list.html", true);
		//$this->tpl->addBlockfile("CONTENT", "def_list", "tpl.glossary_definition_list.html", true);
		//sendInfo();
		$this->tpl->addBlockfile("STATUSLINE", "statusline", "tpl.statusline.html");
		$this->tpl->setVariable("HEADER",
			$this->lng->txt("cont_term").": ".$this->term->getTerm());

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$this->tpl->setCurrentBlock("add_def");
		$this->tpl->setVariable("TXT_ADD_DEFINITION",
			$this->lng->txt("cont_add_definition"));
		$this->tpl->setVariable("BTN_ADD", "addDefinition");
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("def_list");

		$defs = ilGlossaryDefinition::getDefinitionList($_GET["term_id"]);

		$this->tpl->setVariable("TXT_TERM", $this->term->getTerm());

		for($j=0; $j<count($defs); $j++)
		{
			$def = $defs[$j];
			$page =& new ilPageObject("gdf", $def["id"]);
			$page_gui =& new ilPageObjectGUI($page);
			//$page_gui->setOutputMode("edit");
			//$page_gui->setPresentationTitle($this->term->getTerm());
			$page_gui->setTemplateOutput(false);
			$output = $page_gui->preview();

			if (count($defs) > 1)
			{
				$this->tpl->setCurrentBlock("definition_header");
						$this->tpl->setVariable("TXT_DEFINITION",
				$this->lng->txt("cont_definition")." ".($j+1));
				$this->tpl->parseCurrentBlock();
			}

			if ($j > 0)
			{
				$this->tpl->setCurrentBlock("up");
				$this->tpl->setVariable("TXT_UP", $this->lng->txt("up"));
				$this->tpl->setVariable("LINK_UP",
					"glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=moveUp&def=".$def["id"]);
				$this->tpl->parseCurrentBlock();
			}

			if ($j+1 < count($defs))
			{
				$this->tpl->setCurrentBlock("down");
				$this->tpl->setVariable("TXT_DOWN", $this->lng->txt("down"));
				$this->tpl->setVariable("LINK_DOWN",
					"glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=moveDown&def=".$def["id"]);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("submit_btns");
			$this->tpl->setVariable("TXT_EDIT", $this->lng->txt("edit"));
			$this->tpl->setVariable("LINK_EDIT",
				"glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=view&def=".$def["id"]);
			$this->tpl->setVariable("TXT_DELETE", $this->lng->txt("delete"));
			$this->tpl->setVariable("LINK_DELETE",
				"glossary_edit.php?ref_id=".$_GET["ref_id"]."&cmd=confirmDefinitionDeletion&def=".$def["id"]);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("definition");
			$this->tpl->setVariable("PAGE_CONTENT", $output);
			$this->tpl->parseCurrentBlock();
		}
		//$this->tpl->setCurrentBlock("def_list");
		//$this->tpl->parseCurrentBlock();

	}


	/**
	* add definition
	*/
	function addDefinition()
	{
		$this->getTemplate();
		$this->setTabs();
		$this->tpl->setVariable("HEADER", $this->lng->txt("cont_term").": ".$this->term->getTerm());

		//$this->prepareOutput();
		if (empty($_GET["term_id"]))
		{
			if (count($_POST["id"]) < 1)
			{
				$this->ilias->raiseError($this->lng->txt("cont_select_term"),$this->ilias->error_obj->MESSAGE);
			}

			if (count($_POST["id"]) > 1)
			{
				$this->ilias->raiseError($this->lng->txt("cont_select_max_one_term"),$this->ilias->error_obj->MESSAGE);
			}
		}

		$term_id = empty($_GET["term_id"])
			? $_POST["id"][0]
			: $_GET["term_id"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.obj_edit.html");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_HEADER", $this->lng->txt("gdf_new"));
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt("gdf_add"));
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TXT_DESC", $this->lng->txt("description"));
		$this->tpl->setVariable("CMD_SUBMIT", "saveDefinition");
		//$this->tpl->setVariable("TARGET", $this->getTargetFrame("save"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->parseCurrentBlock();

		/*
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setTargetFrame("save",$this->getTargetFrame("save"));
		$meta_gui->edit("ADM_CONTENT", "adm_content",
			"glossary_edit.php?ref_id=".$_GET["ref_id"]."&term_id=".$term_id."&cmd=saveDefinition");
		*/

	}

	/**
	* cancel adding definition
	*/
	function cancel()
	{
		sendInfo($this->lng->txt("msg_cancel"),true);
		$this->ctrl->redirect($this, "listDefinitions");
	}

	/**
	* save definition
	*/
	function saveDefinition()
	{
		//$meta_gui =& new ilMetaDataGUI();
		//$meta_data =& $meta_gui->create();
		$def =& new ilGlossaryDefinition();
		$def->setTermId($_GET["term_id"]);
		$def->setTitle($_POST["Fobject"]["title"]);#"content object ".$newObj->getId());		// set by meta_gui->save
		$def->setDescription($_POST["Fobject"]["desc"]);	// set by meta_gui->save
		$def->create();

		$this->ctrl->redirect($this, "listDefinitions");
	}


	/**
	* get template
	*/
	function getTemplate()
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		sendInfo();
	}

	/**
	* output tabs
	*/
	function setTabs()
	{

		// catch feedback message
		include_once("classes/class.ilTabsGUI.php");
		$tabs_gui =& new ilTabsGUI();
		$this->getTabs($tabs_gui);

		$this->tpl->setVariable("TABS", $tabs_gui->getHTML());

	}

	/**
	* get tabs
	*/
	function getTabs(&$tabs_gui)
	{
//echo ":".$_GET["term_id"].":";
		if ($_GET["term_id"] != "")
		{
			// list definitions
			$tabs_gui->addTarget("cont_definitions",
				$this->ctrl->getLinkTarget($this, "listDefinitions"), "listDefinitions",
				get_class($this));

			// properties
			$tabs_gui->addTarget("properties",
				$this->ctrl->getLinkTarget($this, "editTerm"), "editTerm",
				get_class($this));
		}

		// back to upper context
		$tabs_gui->addTarget("cont_back",
			$this->ctrl->getParentReturn($this), "",
			"");

	}

}

?>
