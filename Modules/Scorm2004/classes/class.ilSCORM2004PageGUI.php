<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2008 ILIAS open source, University of Cologne            |
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

include_once("./Services/COPage/classes/class.ilPageObjectGUI.php");
include_once("./Modules/Scorm2004/classes/class.ilSCORM2004Page.php");
require_once './Modules/Scorm2004/classes/class.ilQuestionExporter.php';

/**
* Class ilSCORM2004Page GUI class
* 
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
* @ilCtrl_Calls ilSCORM2004PageGUI: ilPageEditorGUI, ilEditClipboardGUI, ilMediaPoolTargetSelector
* @ilCtrl_Calls ilSCORM2004PageGUI: ilRatingGUI, ilPublicUserProfileGUI, ilPageObjectGUI, ilNoteGUI
* @ilCtrl_Calls ilSCORM2004PageGUI: ilMDEditorGUI
*
* @ingroup ModulesScormAicc
*/
class ilSCORM2004PageGUI extends ilPageObjectGUI
{
	protected $glossary_links = array();
	protected $scorm_mode = "preview";
	static $export_glo_tpl;
	
	/**
	* Constructor
	*/
	function __construct($a_parent_type, $a_id = 0, $a_old_nr = 0, $a_slm_id = 0,
		$a_glo_id = 0)
	{
		global $tpl, $ilCtrl;

		$this->glo_id = $a_glo_id;
		
		parent::__construct($a_parent_type, $a_id, $a_old_nr);
		

		$this->setEnabledMaps(false);
		$this->setPreventHTMLUnmasking(false);
		// $this->setEnabledInternalLinks(false);
		$this->setEnabledInternalLinks(true);
		$this->setEnabledSelfAssessment(true);
		$this->setEnabledPCTabs(true);
		
		$this->getPageConfig()->addIntLinkFilter(array("StructureObject",
			"StructureObject_New", "PageObject", "PageObject_FAQ", "PageObject_New",
			"GlossaryItem_New",
			"Media", "Media_FAQ", "Media_Media", "Media_New",
			"RepositoryItem"));
		$this->setIntLinkHelpDefault("File", 0);
		$this->setIntLinkReturn(
			$ilCtrl->getLinkTargetByClass("ilobjscorm2004learningmodulegui", "showTree"));
		
		$this->slm_id = $a_slm_id;
		$this->enableNotes(true, $this->slm_id);
	}
	
	function initPageObject($a_parent_type, $a_id, $a_old_nr)
	{
		$page = new ilSCORM2004Page($a_id, $a_old_nr);
		$page->setGlossaryId($this->glo_id);
		$this->setPageObject($page);
	}
	
	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilCtrl;
		
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			case 'ilmdeditorgui':
				return parent::executeCommand();
				break;

			case "ilpageobjectgui":
				$page_gui = new ilPageObjectGUI("sahs",
					$this->getPageObject()->getId(), $this->getPageObject()->old_nr);
				$page_gui->setEnabledPCTabs(true);
				$html = $ilCtrl->forwardCommand($page_gui);
				return $html;
				
			default:
				$html = parent::executeCommand();
				return $html;
		}
	}

	/**
	* Set SCORM2004 Page Object.
	*
	* @param	object	$a_scpage	Page Object
	*/
	function setSCORM2004Page($a_scpage)
	{
		$this->setPageObject($a_scpage);
	}

	/**
	* Get SCORM2004 Page Object.
	*
	* @return	object		Page Object
	*/
	function getSCORM2004Page()
	{
		return $this->getPageObject();
	}

	/*function preview()
	{
		global $ilCtrl;
		
		$wtpl = new ilTemplate("tpl....html",
			true, true, "Modules/Scorm2004");
		
		$wtpl->setVariable("PAGE", parent::preview());
		return $wtpl->get();
	}*/
	
	/**
	* Get question html for page
	*/
	function getQuestionHtmlOfPage()
	{
		$q_ids = $this->getPageObject()->getQuestionIds();

		$html = array();
		if (count($q_ids) > 0)
		{
			foreach ($q_ids as $q_id)
			{
				include_once("./Modules/TestQuestionPool/classes/class.assQuestionGUI.php");
				$q_gui =& assQuestionGUI::_getQuestionGUI("", $q_id);
				$q_gui->outAdditionalOutput();				
				$html[$q_id] = $q_gui->getPreview(TRUE);
			}
		}

		return $html;
	}
	

	// moved to parent
	//function getQuestionJsOfPage($a_no_interaction = false)

	/**
	 * Init question handling
	 *
	 * @param
	 * @return
	 */
	function initSelfAssessmentRendering()
	{
		if ($this->scorm_rendering_mode == "preview")
		{
			parent::initSelfAssessmentRendering();
		}
	}
	
	/**
	 * Self assessment question rendering
	 *
	 * @param
	 * @return
	 */
	function selfAssessmentRendering($a_output)
	{
		if ($this->scorm_rendering_mode == "preview")
		{
			$a_output = parent::selfAssessmentRendering($a_output);
		}

		return $a_output;
	}
	
	/**
	* Show the page
	*/
	function showPage($a_mode = "preview")
	{
		global $tpl, $ilCtrl;
		
		$this->scorm_rendering_mode = $a_mode;
						
		$this->setTemplateOutput(false);
		
		$output = parent::showPage();
		
		return $output;
	}
	
	/**
	 * Set standard link xml (currently only glossaries)
	 */
	function setDefaultLinkXml()
	{
		$int_links = $this->getPageObject()->getInternalLinks(true);
		$this->glossary_links = $int_links;
//var_dump($int_links);

// key is il__git_18:GlossaryItem:Glossary::4 => id is il__git_18_4, 

		$link_info = "<IntLinkInfos>";
		$targetframe = "None";
		foreach ($int_links as $int_link)
		{
			$onclick = "";
			$target = $int_link["Target"];
			$targetframe = "None";
			if (substr($target, 0, 4) == "il__")
			{
				$target_arr = explode("_", $target);
				$target_id = $target_arr[count($target_arr) - 1];
				$type = $int_link["Type"];
				
				switch($type)
				{
					case "GlossaryItem":
						$ltarget = "";
						//$href = "./goto.php?target=git_".$target_id;
						$href = "#";
						$onclick = 'OnClick="return false;"';
						$anc_par = 'Anchor=""';
						$targetframe = "Glossary";
						break;

				}
				$link_info.="<IntLinkInfo $onclick Target=\"$target\" Type=\"$type\" ".$anc_par." ".
					"TargetFrame=\"$targetframe\" LinkHref=\"$href\" LinkTarget=\"$ltarget\" />";
			}
		}
		$link_info.= "</IntLinkInfos>";
		$this->setLinkXML($link_info);
//var_dump($link_info);
	}
	
	/**
	 * Post output processing:
	 * - Add glossary divs
	 */
	function postOutputProcessing($a_output)
	{
//var_dump($this->glossary_links);
		include_once("./Services/UIComponent/Overlay/classes/class.ilOverlayGUI.php");
		
		if ($this->scorm_mode != "export")
		{
			$tpl = new ilTemplate("tpl.glossary_entries.html", true, true, "Modules/Scorm2004");
		}
		else
		{
			$tpl = self::$export_glo_tpl;
		}
		$glossary = false;
		if (is_array($this->glossary_links))
		{
			$overlays = array();
			foreach ($this->glossary_links as $k => $e)
			{
				
				if ($e["Type"] == "GlossaryItem")
				{
					$karr = explode(":", $k);
					$link_id = $karr[0]."_".$this->getPageObject()->getId()."_".$karr[4];
					//$ov_id = "ov".$karr[0]."_".$karr[4];
					$ov_id = "ov".$karr[0];
					
					include_once("./Modules/Glossary/classes/class.ilGlossaryTermGUI.php");
					$id_arr = explode("_", $karr[0]); 
					$term_gui =& new ilGlossaryTermGUI($id_arr[count($id_arr) - 1]);
					$html = $term_gui->getOverlayHTML();
					
					$tpl->setCurrentBlock("entry");
					$tpl->setVariable("CONTENT", $html);
					$tpl->setVariable("OVERLAY_ID", $ov_id);
	
					$glossary = true;
					
					if (!isset($overlays[$ov_id]))
					{
						$overlays[$ov_id] = new ilOverlayGUI($ov_id);
						$overlays[$ov_id]->setAnchor($link_id);
						$overlays[$ov_id]->setTrigger($link_id);
						if ($this->scorm_mode != "export" ||
							$this->getOutputMode() == IL_PAGE_PREVIEW)
						{
							$overlays[$ov_id]->add();
						}
						else
						{
							$tpl->setVariable("SCRIPT", "ilAddOnLoad(function () {".$overlays[$ov_id]->getOnLoadCode()."});");
						}
					}
					else
					{
						if ($this->scorm_mode != "export" ||
							$this->getOutputMode() == IL_PAGE_PREVIEW)
						{
							$overlays[$ov_id]->addTrigger($link_id, "click", $link_id);
						}
						else
						{
							$tpl->setVariable("SCRIPT",
								"ilAddOnLoad(function () {".$overlays[$ov_id]->getTriggerOnLoadCode($link_id, "click", $link_id)."});");
						}
					}
					
					$tpl->parseCurrentBlock();
				}
			}
		}
		
		if ($glossary && $this->scorm_mode != "export")
		{
			return $a_output.$tpl->get();
		}
		
		return $a_output;
	}
	
	/**
	 * Init export
	 */
	static function initExport()
	{
		self::$export_glo_tpl = new ilTemplate("tpl.glossary_entries.html", true, true, "Modules/Scorm2004");
	}
	
	/**
	 * Get glossary html (only in export mode)
	 */
	static function getGlossaryHTML()
	{
		return self::$export_glo_tpl->get();
	}
	
}
?>
