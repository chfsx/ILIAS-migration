<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
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
include_once("./Modules/Wiki/classes/class.ilWikiPage.php");

/**
* Class ilWikiPage GUI class
* 
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
* @ilCtrl_Calls ilWikiPageGUI: ilPageEditorGUI, ilEditClipboardGUI, ilMediaPoolTargetSelector
* @ilCtrl_Calls ilWikiPageGUI: ilRatingGUI, ilPublicUserProfileGUI
*
* @ingroup ModulesWiki
*/
class ilWikiPageGUI extends ilPageObjectGUI
{
	/**
	* Constructor
	*/
	function __construct($a_id = 0, $a_old_nr = 0)
	{
		global $tpl;
		
		parent::__construct("wpg", $a_id, $a_old_nr);
		
		// content style
		include_once("./Services/Style/classes/class.ilObjStyleSheet.php");
		$tpl->setCurrentBlock("ContentStyle");
		$tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
			ilObjStyleSheet::getContentStylePath(0));
		$tpl->parseCurrentBlock();
		
		$this->setEnabledMaps(true);

	}
	
	function initPageObject($a_parent_type, $a_id, $a_old_nr)
	{
		$page = new ilWikiPage($a_id, $a_old_nr);
		$this->setPageObject($page);
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilCtrl;
		
		$next_class = $this->ctrl->getNextClass($this);

		switch($next_class)
		{
			case "ilratinggui":
				include_once("./Services/Rating/classes/class.ilRatingGUI.php");
				$rating_gui = new ilRatingGUI();
				$rating_gui->setObject($this->getPageObject()->getParentId(), "wiki",
					$this->getPageObject()->getId(), "wpg");
				$this->ctrl->forwardCommand($rating_gui);
				$ilCtrl->redirect($this, "preview");
				break;
				
			default:
				return parent::executeCommand();
		}
	}


/*	function &executeCommand()
	{
  		global $ilUser, $ilCtrl;
  
		$next_class = $ilCtrl->getNextClass($this);
		$cmd = $ilCtrl->getCmd();

  		switch($next_class)
		{
			default:
//				$ilCtrl->setReturnByClass("ilPageObjectGUI", "view");
//				$ilCtrl->setReturn($this, "editPage");
//				$page_gui->setSourcecodeDownloadScript("ilias.php?baseClass=ilGlossaryPresentationGUI&amp;ref_id=".$_GET["ref_id"]);
//				$page_gui->setFullscreenLink("ilias.php?baseClass=ilGlossaryPresentationGUI&amp;cmd=fullscreen&amp;ref_id=".$_GET["ref_id"]);
//				$page_gui->setTemplateTargetVar("ADM_CONTENT");
				$this->setOutputMode("edit");
//				$page_gui->setLocator($gloss_loc);
//				$page_gui->setIntLinkHelpDefault("GlossaryItem", $_GET["ref_id"]);
//				$page_gui->setIntLinkReturn($this->ctrl->getLinkTargetByClass("ilobjglossarygui", "quickList"));
//				$page_gui->setPageBackTitle($this->lng->txt("cont_definition"));
//				$page_gui->setLinkParams("ref_id=".$_GET["ref_id"]);
//				$page_gui->setHeader($this->getWikiPage()->getTitle());
//				$page_gui->setFileDownloadLink("ilias.php?baseClass=ilGlossaryPresentationGUI&amp;cmd=downloadFile&amp;ref_id=".$_GET["ref_id"]);
				$this->setTemplateOutput(false);
				$this->setPresentationTitle($this->getWikiPage()->getTitle());
				return $this->$cmd();	
				break;
		}
  
  		return true;
	}
*/

	/**
	* Set Wiki Page Object.
	*
	* @param	object	$a_wikipage	Wiki Page Object
	*/
	function setWikiPage($a_wikipage)
	{
		$this->setPageObject($a_wikipage);
	}

	/**
	* Get Wiki Page Object.
	*
	* @return	object	Wiki Page Object
	*/
	function getWikiPage()
	{
		return $this->getPageObject();
	}

	/**
	* Get wiki page gui for id and title
	*/
	static function getGUIForTitle($a_wiki_id, $a_title, $a_old_nr = 0)
	{
		global $ilDB;
		
		include_once("./Modules/Wiki/classes/class.ilWikiPage.php");
		$id = ilWikiPage::getPageIdForTitle($a_wiki_id, $a_title);
		$page_gui = new ilWikiPageGUI($id, $a_old_nr);
		
		return $page_gui;
	}
	
	function setSideBlock()
	{
		global $tpl;
		
		// side block
		include_once("./Modules/Wiki/classes/class.ilWikiSideBlockGUI.php");
		$wiki_side_block = new ilWikiSideBlockGUI();
		$wiki_side_block->setPageObject($this->getWikiPage());
		$tpl->setRightContent($wiki_side_block->getHTML());
	}

	function preview()
	{
		global $ilCtrl;
		
		$this->getWikiPage()->increaseViewCnt(); // todo: move to page object
		$this->setSideBlock();
		$wtpl = new ilTemplate("tpl.wiki_page_view_main_column.html",
			true, true, "Modules/Wiki");
		
		// rating
		if (ilObjWiki::_lookupRating($this->getPageObject()->getParentId()))
		{
			include_once("./Services/Rating/classes/class.ilRatingGUI.php");
			$rating_gui = new ilRatingGUI();
			$rating_gui->setObject($this->getPageObject()->getParentId(), "wiki",
				$this->getPageObject()->getId(), "wpg");
			$wtpl->setVariable("RATING", $ilCtrl->getHtml($rating_gui));
		}
		
		$wtpl->setVariable("PAGE", parent::preview());
		return $wtpl->get();
	}
	
	function showPage()
	{
		global $tpl, $ilCtrl;
		
		// content style
/*		include_once("./classes/class.ilObjStyleSheet.php");
		$tpl->setCurrentBlock("ContentStyle");
		$tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
			ilObjStyleSheet::getContentStylePath(0));
		$tpl->parseCurrentBlock();
*/
		$this->setSourcecodeDownloadScript("ilias.php?baseClass=ilWikiHandlerGUI&amp;ref_id=".$_GET["ref_id"]);
		$this->setFullscreenLink("ilias.php?baseClass=ilWikiHandlerGUI&amp;cmd=fullscreen&amp;ref_id=".$_GET["ref_id"]);
		$this->setFileDownloadLink("ilias.php?baseClass=ilWikiHandlerGUI&amp;cmd=downloadFile&amp;ref_id=".$_GET["ref_id"]);
		$this->setLinkXML($this->getLinkXML());
		$this->setTemplateOutput(false);
		$this->setPresentationTitle($this->getWikiPage()->getTitle());
		$this->getWikiPage()->increaseViewCnt();
		$output = parent::showPage();
		
		return $output;
	}
	
	function getLinkXML()
	{
		return "";
	}
	
	/**
	* Finalizing output processing.
	*/
	function postOutputProcessing($a_output)
	{
//echo htmlentities($a_output);
		include_once("./Modules/Wiki/classes/class.ilWikiUtil.php");
		$output = ilWikiUtil::replaceInternalLinks($a_output,
			$this->getWikiPage()->getWikiId());
		return $output;
	}
	
} // END class.ilWikiPageGUI
?>
