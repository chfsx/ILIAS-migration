<?php 
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
 * Term list table
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 * @ingroup Services
 */
class ilTermListTableGUI extends ilTable2GUI
{
	
	/**
	 * Constructor
	 */
	function __construct($a_parent_obj, $a_parent_cmd)
	{
		global $ilCtrl, $lng;
		
		$this->glossary = $a_parent_obj->object;
		$this->setId("glotl".$this->glossary->getId());

		// selectable columns
		$this->selectable_cols = array(
			"language" => array(
				"txt" => $lng->txt("language"),
				"default" => true),
			"usage" => array(
				"txt" => $lng->txt("cont_usage"),
				"default" => true)
		);
		
		include_once("./Modules/Glossary/classes/class.ilGlossaryAdvMetaDataAdapter.php");
		$adv_ad = new ilGlossaryAdvMetaDataAdapter($this->glossary->getId());
		$this->adv_fields = $adv_ad->getAllFields();
		foreach ($this->adv_fields as $f)
		{
			$this->selectable_cols["md_".$f["id"]] = array(
				"txt" => $f["title"],
				"default" => false
				);
		}
		
		// selectable columns of advanced metadata
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setTitle($lng->txt("cont_terms"));
		
		$this->addColumn("", "", "1", true);
		$this->addColumn($this->lng->txt("cont_term"));
		
		
		foreach ($this->getSelectedColumns() as $c)
		{
			if (in_array($c, array ("language", "usage")))
			{
				$this->addColumn($this->selectable_cols[$c]["txt"]);
			}
			else if (substr($c, 0, 3) == "md_")
			{
				$id = (int) substr($c, 3);
				$this->addColumn($this->adv_fields[$id]["title"]);
			}
			
//			$this->addColumn($this->lng->txt("language"));
//			$this->addColumn($this->lng->txt("cont_usage"));
		}

		
		$this->addColumn($this->lng->txt("cont_definitions"));
		
		if (in_array($this->glossary->getVirtualMode(),
			array("level", "subtree")))
		{
			$this->addColumn($this->lng->txt("obj_glo"));
		}
		
		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
		$this->setRowTemplate("tpl.term_tbl_row.html", "Modules/Glossary");
		$this->setEnableTitle(true);

		$this->addMultiCommand("confirmTermDeletion", $lng->txt("delete"));
		$this->addMultiCommand("addDefinition", $lng->txt("cont_add_definition"));
		
		$this->setShowRowsSelector(true);
		
		$this->initFilter();
		$this->setData($this->glossary->getTermList($this->filter["term"], "",
			$this->filter["definition"], 0, true, true));
	}
	
	/**
	 * Get selectable columns
	 *
	 * @param
	 * @return
	 */
	function getSelectableColumns()
	{
		return $this->selectable_cols;
	}
	
	
	/**
	 * Init filter
	 */
	function initFilter()
	{
		global $lng, $ilDB;
		
		// term
		include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		$ti = new ilTextInputGUI($lng->txt("cont_term"), "term");
		$ti->setMaxLength(64);
		$ti->setSize(20);
		$ti->setSubmitFormOnEnter(true);
		$this->addFilterItem($ti);
		$ti->readFromSession();
		$this->filter["term"] = $ti->getValue();
		
		// definition
		if ($ilDB->getDBType() != "oracle")
		{
			include_once("./Services/Form/classes/class.ilTextInputGUI.php");
			$ti = new ilTextInputGUI($lng->txt("cont_definition"), "defintion");
			$ti->setMaxLength(64);
			$ti->setSize(20);
			$ti->setSubmitFormOnEnter(true);
			$this->addFilterItem($ti);
			$ti->readFromSession();
			$this->filter["definition"] = $ti->getValue();
		}
	}

	/**
	 * Fill table row
	 */
	protected function fillRow($term)
	{
		global $lng, $ilCtrl;

		$defs = ilGlossaryDefinition::getDefinitionList($term["id"]);
		$ilCtrl->setParameterByClass("ilglossarytermgui", "term_id", $term["id"]);
		
		for($j=0; $j<count($defs); $j++)
		{
			$def = $defs[$j];

			if ($this->glossary->getId() == $term["glo_id"])
			{
				// up
				if ($j > 0)
				{
					$this->tpl->setCurrentBlock("move_up");
					$this->tpl->setVariable("TXT_UP", $lng->txt("up"));
					$ilCtrl->setParameter($this->parent_obj, "term_id", $term["id"]);
					$ilCtrl->setParameter($this->parent_obj, "def", $def["id"]);
					$this->tpl->setVariable("LINK_UP",
						$ilCtrl->getLinkTarget($this->parent_obj, "moveDefinitionUp"));
					$this->tpl->parseCurrentBlock();
				}
	
				// down
				if ($j+1 < count($defs))
				{
					$this->tpl->setCurrentBlock("move_down");
					$this->tpl->setVariable("TXT_DOWN", $lng->txt("down"));
					$ilCtrl->setParameter($this->parent_obj, "term_id", $term["id"]);
					$ilCtrl->setParameter($this->parent_obj, "def", $def["id"]);
					$this->tpl->setVariable("LINK_DOWN",
						$ilCtrl->getLinkTarget($this->parent_obj, "moveDefinitionDown"));
					$this->tpl->parseCurrentBlock();
				}
	
				// delete
				$this->tpl->setCurrentBlock("delete");
				$ilCtrl->setParameter($this->parent_obj, "term_id", $term["id"]);
				$ilCtrl->setParameter($this->parent_obj, "def", $def["id"]);
				$this->tpl->setVariable("LINK_DELETE",
					$ilCtrl->getLinkTarget($this->parent_obj, "confirmDefinitionDeletion"));
				$this->tpl->setVariable("TXT_DELETE", $lng->txt("delete"));
				$this->tpl->parseCurrentBlock();
	
				// edit
				$this->tpl->setCurrentBlock("edit");
				$ilCtrl->setParameterByClass("ilglossarydefpagegui", "term_id", $term["id"]);
				$ilCtrl->setParameterByClass("ilglossarydefpagegui", "def", $def["id"]);
				$this->tpl->setVariable("LINK_EDIT",
					$ilCtrl->getLinkTargetByClass(array("ilglossarytermgui",
					"iltermdefinitioneditorgui",
					"ilglossarydefpagegui"), "edit"));
				$this->tpl->setVariable("TXT_EDIT", $lng->txt("edit"));
				$this->tpl->parseCurrentBlock();
			}

			// text
			$this->tpl->setCurrentBlock("definition");
			$short_str = $def["short_text"];
			
			// replace tex
			// if a tex end tag is missing a tex end tag
			$ltexs = strrpos($short_str, "[tex]");
			$ltexe = strrpos($short_str, "[/tex]");
			if ($ltexs > $ltexe)
			{
				$page = new ilGlossaryDefPage($def["id"]);
				$page->buildDom();
				$short_str = $page->getFirstParagraphText();
				$short_str = strip_tags($short_str, "<br>");
				$ltexe = strpos($short_str, "[/tex]", $ltexs);
				$short_str = ilUtil::shortenText($short_str, $ltexe+6, true);
			}
			$short_str = ilUtil::insertLatexImages($short_str);
			$short_str = ilPCParagraph::xml2output($short_str);
			$this->tpl->setVariable("DEF_SHORT", $short_str);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("definition_row");
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("check_col");
		$this->tpl->setVariable("CHECKBOX_ID", $term["id"]);
		$this->tpl->parseCurrentBlock();

		// edit term link
		$this->tpl->setCurrentBlock("edit_term");
		$this->tpl->setVariable("TEXT_TERM", $term["term"]);
		$ilCtrl->setParameter($this->parent_obj, "term_id", $term["id"]);
		if ($this->glossary->getId() == $term["glo_id"])
		{
			$this->tpl->setVariable("LINK_EDIT_TERM",
				$ilCtrl->getLinkTargetByClass("ilglossarytermgui", "editTerm"));
			$this->tpl->setVariable("TXT_EDIT_TERM", $lng->txt("edit"));
		}
		$this->tpl->parseCurrentBlock();

		// usage
		if (in_array("usage", $this->getSelectedColumns()))
		{
			$nr_usage = ilGlossaryTerm::getNumberOfUsages($term["id"]);
			if ($nr_usage > 0 && $this->glossary->getId() == $term["glo_id"])
			{
				$this->tpl->setCurrentBlock("link_usage");
				$ilCtrl->setParameterByClass("ilglossarytermgui", "term_id", $term["id"]);
				$this->tpl->setVariable("LUSAGE", ilGlossaryTerm::getNumberOfUsages($term["id"]));
				$this->tpl->setVariable("LINK_USAGE",
					$ilCtrl->getLinkTargetByClass("ilglossarytermgui", "listUsages"));
				$ilCtrl->setParameterByClass("ilglossarytermgui", "term_id", "");
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->setCurrentBlock("usage");
				$this->tpl->setVariable("USAGE", ilGlossaryTerm::getNumberOfUsages($term["id"]));
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("td_usage");
			$this->tpl->parseCurrentBlock();
		}
		
		// glossary title
		if (in_array($this->glossary->getVirtualMode(),
			array("level", "subtree")))
		{
			$this->tpl->setCurrentBlock("glossary");
			$this->tpl->setVariable("GLO_TITLE", ilObject::_lookupTitle($term["glo_id"]));
			$this->tpl->parseCurrentBlock();
		}

		// output language
		if (in_array("language", $this->getSelectedColumns()))
		{
			$this->tpl->setCurrentBlock("td_lang");
			$this->tpl->setVariable("TEXT_LANGUAGE", $lng->txt("meta_l_".$term["language"]));
			$this->tpl->parseCurrentBlock();
		}

		// adv metadata
		foreach ($this->getSelectedColumns() as $c)
		{
			if (substr($c, 0, 3) == "md_")
			{
				$id = (int) substr($c, 3);
				$this->tpl->setCurrentBlock("td_md");
				switch ($this->adv_fields[$id]["type"])
				{
					case ilAdvancedMDFieldDefinition::TYPE_DATETIME:
						$val = ($term["md_".$id] > 0)
							? ilDatePresentation::formatDate(new ilDateTime($term["md_".$id], IL_CAL_UNIX))
							: " ";
						break;
						
					case ilAdvancedMDFieldDefinition::TYPE_DATE:
						$val = ($term["md_".$id] > 0)
							? ilDatePresentation::formatDate(new ilDate($term["md_".$id], IL_CAL_UNIX))
							: " ";
						break;
						
					default:
						$val = ($term["md_".$id] != "")
							? $term["md_".$id]
							: " ";
						breal;
				}
				$this->tpl->setVariable("MD_VAL", $val);
				$this->tpl->parseCurrentBlock();
			}
		}
		
	}

}
?>