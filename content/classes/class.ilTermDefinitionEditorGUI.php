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

require_once("classes/class.ilObjStyleSheet.php");
require_once ("content/classes/Pages/class.ilPageObjectGUI.php");

/**
* GUI class for glossary term definition editor
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilTermDefinitionEditorGUI
{
	var $ilias;
	var $tpl;
	var $lng;
	var $glossary;
	var $definition;
	var $term;

	/**
	* Constructor
	* @access	public
	*/
	function ilTermDefinitionEditorGUI()
	{
		global $ilias, $tpl, $lng, $objDefinition, $ilCtrl;

		// initiate variables
		$this->ilias =& $ilias;
		$this->tpl =& $tpl;
		$this->lng =& $lng;
		$this->ctrl =& $ilCtrl;
		$this->glossary =& new ilObjGlossary($_GET["ref_id"], true);
		$this->definition =& new ilGlossaryDefinition($_GET["def"]);
		$this->term =& new ilGlossaryTerm($this->definition->getTermId());

		$this->ctrl->saveParameter($this, array("def"));
	}

	/**
	* get forward classes
	*/
	function _forwards()
	{
		return array("ilPageObjectGUI");
	}


	function &executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();


		// content style
		$this->tpl->setCurrentBlock("ContentStyle");
		$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
			ilObjStyleSheet::getContentStylePath(0));
		$this->tpl->parseCurrentBlock();

		// syntax style
		$this->tpl->setCurrentBlock("SyntaxStyle");
		$this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
			ilObjStyleSheet::getSyntaxStylePath());
		$this->tpl->parseCurrentBlock();

		//$this->tpl->setVariable("TXT_LOCATOR",$this->lng->txt("locator"));

		//$this->main_header($this->lng->txt("cont_term").": ".$this->term->getTerm().", ".
		//	$this->lng->txt("cont_definition")." ".$this->definition->getNr());

		require_once ("content/classes/class.ilGlossaryLocatorGUI.php");
		$gloss_loc =& new ilGlossaryLocatorGUI();
		$gloss_loc->setTerm($this->term);
		$gloss_loc->setGlossary($this->glossary);
		$gloss_loc->setDefinition($this->definition);

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		$this->tpl->setVariable("HEADER", $this->term->getTerm());

		switch ($next_class)
		{

			case "ilpageobjectgui":
				if ($this->ctrl->getCmdClass() == "ilpageobjectgui")
				{
					$gloss_loc->display();
				}
				$this->setTabs();
				$this->ctrl->setReturnByClass("ilPageObjectGUI", "view");
//echo "MK";
				$this->ctrl->setReturn($this, "listDefinitions");
				$page =& $this->definition->getPageObject();
				$page->addUpdateListener($this, "saveShortText");
				$page_gui =& new ilPageObjectGUI($page);
				$page_gui->setTemplateTargetVar("ADM_CONTENT");
				$page_gui->setOutputMode("edit");
				$page_gui->setLocator($gloss_loc);
				$page_gui->setHeader($this->term->getTerm());
				/*
				$page_gui->setTabs(array(array("cont_all_definitions", "listDefinitions"),
						array("edit", "view"),
						array("cont_preview", "preview"),
						array("meta_data", "editDefinitionMetaData")
						));*/
				$page_gui->setPresentationTitle($this->term->getTerm());
				//$page_gui->setTargetScript("glossary_edit.php?ref_id=".
				//	$this->glossary->getRefId()."&def=".$this->definition->getId()."&mode=page_edit");
				//$page_gui->setReturnLocation("glossary_edit.php?ref_id=".
				//	$this->glossary->getRefId()."&def=".$this->definition->getId()."&cmd=view");
				$page_gui->executeCommand();

				break;

			default:
				$this->setTabs();
				$gloss_loc->display();
				$ret =& $this->$cmd();
				break;

				/*
				case "editDefinitionMetaData":
					$gloss_loc->display();
					$this->editMeta();
					break;

				case "saveMeta":
				case "saveDefinitionMetaData":
					$this->saveMeta();
					break;

				case "confirmDefinitionDeletion":
					$gloss_loc->display();
					$this->$cmd();
					break;

				case "chooseMetaSection":
				case "addMeta":
				case "deleteMeta":
				case "cancelDefinitionDeletion":
				case "deleteDefinition":
					$this->$cmd();
					break;


				default:
					$gloss_loc->display();
					$page_gui->$cmd();
					break;
				*/
		}
	}




	/**
	* edit meta data of glossary term definition
	*/
	function editMeta()
	{
		include_once("classes/class.ilMetaDataGUI.php");
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->definition);
		$meta_gui->edit("ADM_CONTENT", "adm_content",
			$this->ctrl->getLinkTarget($this, "saveMeta"));
	}


	/**
	* save meta data of glossary term definition
	*/
	function saveMeta()
	{
		include_once("classes/class.ilMetaDataGUI.php");
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->definition);
		$meta_gui->save();
		$this->ctrl->redirect($this, "editMeta");
	}

	/**
	* choose meta section
	*/
	function chooseMetaSection()
	{
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->definition);
		$meta_gui->edit("ADM_CONTENT", "adm_content", $this->ctrl->getLinkTarget($this, "editMeta"),
			$_REQUEST["meta_section"]);
	}


	/**
	* add a meta subsection/-tag
	*/
	function addMeta()
	{
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->definition);
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
		$meta_gui->edit("ADM_CONTENT", "adm_content", $this->ctrl->getLinkTarget($this, "editMeta"),
			$meta_section);
	}

	/**
	* delete meta subsection/-tag
	*/
	function deleteMeta()
	{
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->definition);
		$meta_index = $_POST["meta_index"] ? $_POST["meta_index"] : $_GET["meta_index"];
		$meta_gui->meta_obj->delete($_GET["meta_name"], $_GET["meta_path"], $meta_index);
		$meta_gui->edit("ADM_CONTENT", "adm_content", $this->ctrl->getLinkTarget($this, "editMeta"),
			$_GET["meta_section"]);
	}

	/**
	* output main header (title and locator)
	*/
	function main_header($a_header_title)
	{
		global $lng;

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->setVariable("HEADER", $a_header_title);
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		$this->displayLocator();
		//$this->setAdminTabs($a_type);
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
		// edit page
		$tabs_gui->addTarget("edit",
			$this->ctrl->getLinkTargetByClass("ilPageObjectGUI", "view"), "view",
			"ilPageObjectGUI");

		// preview page
		$tabs_gui->addTarget("cont_preview",
			$this->ctrl->getLinkTargetByClass("ilPageObjectGUI", "preview"), "preview",
			"ilPageObjectGUI");

		// properties
		$tabs_gui->addTarget("meta_data",
			$this->ctrl->getLinkTarget($this, "editMeta"), "editMeta",
			get_class($this));

		// back to upper context
		$tabs_gui->addTarget("cont_back",
			$this->ctrl->getParentReturn($this), "",
			"");

	}

	/*
	function setAdminTabs()
	{
		$tabs = array();
		$this->tpl->addBlockFile("TABS", "tabs", "tpl.tabs.html");

		$this->tpl->setCurrentBlock("tab");
		$this->tpl->setVariable("TAB_TYPE", "tabinactive");
		$this->tpl->setVariable("TAB_LINK", "glossary_edit.php?ref_id=".$_GET["ref_id"].
			"&cmd=listDefinitions&term_id=".$this->term->getId());
		$this->tpl->setVariable("TAB_TEXT", $this->lng->txt("cont_all_definitions"));
		$this->tpl->parseCurrentBlock();

		$tabs[] = array("edit", "view");
		$tabs[] = array("cont_preview", "preview");
		$tabs[] = array("meta_data", "editDefinitionMetaData");

		foreach ($tabs as $row)
		{
			$i++;

			if ($row[1] == $_GET["cmd"])
			{
				$tabtype = "tabactive";
				$tab = $tabtype;
			}
			else
			{
				$tabtype = "tabinactive";
				$tab = "tab";
			}

			$this->tpl->setCurrentBlock("tab");
			$this->tpl->setVariable("TAB_TYPE", $tabtype);
			$this->tpl->setVariable("TAB_TYPE2", $tab);
			$this->tpl->setVariable("TAB_LINK", "glossary_edit.php?ref_id=".$_GET["ref_id"]."&def=".
				$_GET["def"]."&cmd=".$row[1]);
			$this->tpl->setVariable("TAB_TEXT", $this->lng->txt($row[0]));
			$this->tpl->parseCurrentBlock();
		}

	}*/


	function displayLocator()
	{
		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");

		$this->tpl->touchBlock("locator_separator");

		$this->tpl->setCurrentBlock("locator_item");
		$this->tpl->setVariable("ITEM", $this->glossary->getTitle());
		$this->tpl->setVariable("LINK_ITEM", "glossary_edit.php?ref_id=".$_GET["ref_id"]);
		$this->tpl->parseCurrentBlock();

		$this->tpl->touchBlock("locator_separator");

		$this->tpl->setCurrentBlock("locator_item");
		$this->tpl->setVariable("ITEM", $this->term->getTerm());
		$this->tpl->setVariable("LINK_ITEM", "glossary_edit.php?ref_id=".$_GET["ref_id"].
			"&cmd=listDefinitions&term_id=".$this->term->getId());
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("locator_item");
		$this->tpl->setVariable("ITEM", $this->lng->txt("cont_definition")." ".$this->definition->getNr());
		$this->tpl->setVariable("LINK_ITEM", "glossary_edit.php?ref_id=".$_GET["ref_id"].
			"&cmd=".$_GET["cmd"]."&def=".$_GET["def"]);
		$this->tpl->parseCurrentBlock();

		//$this->tpl->touchBlock("locator_separator");

		$this->tpl->setCurrentBlock("locator");
		$this->tpl->setVariable("TXT_LOCATOR", $debug.$this->lng->txt("locator"));
		$this->tpl->parseCurrentBlock();
	}

	function saveShortText()
	{
		$page =& $this->definition->getPageObject();
		$text = $page->getFirstParagraphText();
		$this->definition->setShortText(ilUtil::shortenText($text, 190, true));
		$this->definition->update();
	}
}
?>
