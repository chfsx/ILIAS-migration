<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/COPage/classes/class.ilPageObjectGUI.php");
include_once("./Modules/Wiki/classes/class.ilWikiPage.php");

/**
* Class ilWikiPage GUI class
* 
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
* @ilCtrl_Calls ilWikiPageGUI: ilPageEditorGUI, ilEditClipboardGUI, ilMediaPoolTargetSelector
* @ilCtrl_Calls ilWikiPageGUI: ilRatingGUI, ilPublicUserProfileGUI, ilPageObjectGUI, ilNoteGUI
*
* @ingroup ModulesWiki
*/
class ilWikiPageGUI extends ilPageObjectGUI
{
	/**
	* Constructor
	*/
	function __construct($a_id = 0, $a_old_nr = 0, $a_wiki_ref_id = 0)
	{
		global $tpl;

		// needed for notifications
		$this->setWikiRefId($a_wiki_ref_id);
		
		parent::__construct("wpg", $a_id, $a_old_nr);
		
		// content style
		include_once("./Services/Style/classes/class.ilObjStyleSheet.php");
		
		$tpl->setCurrentBlock("SyntaxStyle");
		$tpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
			ilObjStyleSheet::getSyntaxStylePath());
		$tpl->parseCurrentBlock();
		
		$this->setEnabledMaps(true);
		$this->setPreventHTMLUnmasking(true);
		$this->setEnabledInternalLinks(false);
		$this->setEnabledWikiLinks(true);
		$this->setEnabledPCTabs(true);

	}
	
	function initPageObject($a_parent_type, $a_id, $a_old_nr)
	{
		$page = new ilWikiPage($a_id, $a_old_nr);
		$page->setWikiRefId($this->getWikiRefId());
		$this->setPageObject($page);
	}

	function setWikiRefId($a_ref_id)
    {
		$this->wiki_ref_id = $a_ref_id;
	}

	function getWikiRefId()
    {
		return $this->wiki_ref_id;
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilCtrl, $ilTabs, $ilUser;
		
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			case "ilnotegui":
				$this->getTabs();
				$ilTabs->setTabActive("pg");
				return $this->preview();
				break;

			case "ilratinggui":
				include_once("./Services/Rating/classes/class.ilRatingGUI.php");
				$rating_gui = new ilRatingGUI();
				$rating_gui->setObject($this->getPageObject()->getParentId(), "wiki",
					$this->getPageObject()->getId(), "wpg");
				$this->ctrl->forwardCommand($rating_gui);
				$ilCtrl->redirect($this, "preview");
				break;
				
			case "ilpageobjectgui":
				$page_gui = new ilPageObjectGUI("wpg",
					$this->getPageObject()->getId(), $this->getPageObject()->old_nr);
				$page_gui->setPresentationTitle($this->getWikiPage()->getTitle());
				return $ilCtrl->forwardCommand($page_gui);
				
			default:

				if($_GET["ntf"])
				{
					include_once "./Services/Notification/classes/class.ilNotification.php";
                    switch($_GET["ntf"])
				    {
						case 1:
							ilNotification::setNotification(ilNotification::TYPE_WIKI, $ilUser->getId(), $this->getPageObject()->getParentId(), false);
							break;

						case 2:
							// remove all page notifications here?
							ilNotification::setNotification(ilNotification::TYPE_WIKI, $ilUser->getId(), $this->getPageObject()->getParentId(), true);
							break;

						case 3:
							ilNotification::setNotification(ilNotification::TYPE_WIKI_PAGE, $ilUser->getId(), $this->getPageObject()->getId(), false);
							break;

						case 4:
							ilNotification::setNotification(ilNotification::TYPE_WIKI_PAGE, $ilUser->getId(), $this->getPageObject()->getId(), true);
							break;
				   }
				   $ilCtrl->redirect($this, "preview");
				}
				
				$this->setPresentationTitle($this->getWikiPage()->getTitle());
				return parent::executeCommand();
		}
	}

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
	static function getGUIForTitle($a_wiki_id, $a_title, $a_old_nr = 0, $a_wiki_ref_id = 0)
	{
		global $ilDB;

		include_once("./Modules/Wiki/classes/class.ilWikiPage.php");
		$id = ilWikiPage::getPageIdForTitle($a_wiki_id, $a_title);
		$page_gui = new ilWikiPageGUI($id, $a_old_nr, $a_wiki_ref_id);
		
		return $page_gui;
	}
	
	function setSideBlock()
	{
		global $tpl;
		
		// side block
		include_once("./Modules/Wiki/classes/class.ilWikiSideBlockGUI.php");
		$wiki_side_block = new ilWikiSideBlockGUI();
		$wiki_side_block->setPageObject($this->getWikiPage());
		
		// search block
		include_once("./Modules/Wiki/classes/class.ilWikiSearchBlockGUI.php");
		$wiki_search_block = new ilWikiSearchBlockGUI();
		$rcontent = $wiki_side_block->getHTML().$wiki_search_block->getHTML();

		$tpl->setRightContent($rcontent);
	}

	/**
	* View wiki page.
	*/
	function preview()
	{
		global $ilCtrl, $ilAccess, $lng, $tpl, $ilUser;
		
		$this->getWikiPage()->increaseViewCnt(); // todo: move to page object
		$this->setSideBlock();
		$wtpl = new ilTemplate("tpl.wiki_page_view_main_column.html",
			true, true, "Modules/Wiki");
		
		// wiki page commands
		// delete
		$page_commands = false;
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
			$st_page = ilObjWiki::_lookupStartPage($this->getPageObject()->getParentId());
			if ($st_page != $this->getPageObject()->getTitle())
			{
				$wtpl->setCurrentBlock("page_command");
				$wtpl->setVariable("HREF_PAGE_CMD",
					$ilCtrl->getLinkTarget($this, "deleteWikiPageConfirmationScreen"));
				$wtpl->setVariable("TXT_PAGE_CMD", $lng->txt("delete"));
				$wtpl->parseCurrentBlock();
			}
		}		
		if ($page_commands)
		{
			$wtpl->setCurrentBlock("page_commands");
			$wtpl->parseCurrentBlock();
		}
			
		// notification
		if ($ilUser->getId() != ANONYMOUS_USER_ID)
		{
			$wtpl->setCurrentBlock("notification");
			include_once "./Services/Notification/classes/class.ilNotification.php";
			$wtpl->setVariable("TXT_NOTIFICATION", $lng->txt("wiki_notification_toggle_info"));
			if(ilNotification::hasNotification(ilNotification::TYPE_WIKI, $ilUser->getId(), $this->getPageObject()->getParentId()))
			{
				$ilCtrl->setParameter($this, "ntf", 1);
				$wtpl->setVariable("URL_NOTIFICATION_TOGGLE_WIKI", $ilCtrl->getLinkTarget($this));

				$wtpl->setVariable("TXT_NOTIFICATION_TOGGLE_WIKI", $lng->txt("wiki_notification_toggle_wiki_deactivate"));
			}
			else
			{
				$ilCtrl->setParameter($this, "ntf", 2);
				$wtpl->setVariable("URL_NOTIFICATION_TOGGLE_WIKI", $ilCtrl->getLinkTarget($this));

				$wtpl->setVariable("TXT_NOTIFICATION_TOGGLE_WIKI", $lng->txt("wiki_notification_toggle_wiki_activate"));
				$wtpl->setVariable("TXT_NOTIFICATION_TOGGLE_DIVIDER", "|");

				if(ilNotification::hasNotification(ilNotification::TYPE_WIKI_PAGE, $ilUser->getId(), $this->getPageObject()->getId()))
				{
					$ilCtrl->setParameter($this, "ntf", 3);
					$wtpl->setVariable("URL_NOTIFICATION_TOGGLE_PAGE", $ilCtrl->getLinkTarget($this));

					$wtpl->setVariable("TXT_NOTIFICATION_TOGGLE_PAGE", $lng->txt("wiki_notification_toggle_page_deactivate"));
				}
				else
				{
					$ilCtrl->setParameter($this, "ntf", 4);
					$wtpl->setVariable("URL_NOTIFICATION_TOGGLE_PAGE", $ilCtrl->getLinkTarget($this));

					$wtpl->setVariable("TXT_NOTIFICATION_TOGGLE_PAGE", $lng->txt("wiki_notification_toggle_page_activate"));
				}
			}
			$ilCtrl->setParameter($this, "ntf", "");
			$wtpl->parseCurrentBlock();
		}

		// rating
		if (ilObjWiki::_lookupRating($this->getPageObject()->getParentId())
			&& $this->getPageObject()->old_nr == 0)
		{
			include_once("./Services/Rating/classes/class.ilRatingGUI.php");
			$rating_gui = new ilRatingGUI();
			$rating_gui->setObject($this->getPageObject()->getParentId(), "wiki",
				$this->getPageObject()->getId(), "wpg");
			$wtpl->setVariable("RATING", $ilCtrl->getHtml($rating_gui));
		}

		// notes
		include_once("Services/Notes/classes/class.ilNoteGUI.php");
		$pg_id = $this->getPageObject()->getId();
		$notes_gui = new ilNoteGUI($this->getPageObject()->getParentId(),
			$pg_id, "wpg");
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
			$notes_gui->enablePublicNotesDeletion(true);
		}
		$notes_gui->enablePrivateNotes();
		//if ($this->lm->publicNotes())
		//{
			$notes_gui->enablePublicNotes();
		//}
		
		$next_class = $this->ctrl->getNextClass($this);
		if ($next_class == "ilnotegui")
		{
			$html = $this->ctrl->forwardCommand($notes_gui);
		}
		else
		{	
			$html = $notes_gui->getNotesHTML();
		}
		$wtpl->setVariable("NOTES", $html);
		
		// permanent link
		$append = ($_GET["page"] != "")
			? "_".ilWikiUtil::makeUrlTitle($_GET["page"])
			: "";
		include_once("./Services/PermanentLink/classes/class.ilPermanentLinkGUI.php");
		$perma_link = new ilPermanentLinkGUI("wiki", $_GET["ref_id"], $append);
		$wtpl->setVariable("PERMA_LINK", $perma_link->getHTML());
		
		$wtpl->setVariable("PAGE", parent::preview());

		$tpl->setLoginTargetPar("wiki_".$_GET["ref_id"].$append);
		
		//highlighting
		if ($_GET["srcstring"] != "")
		{
			include_once './Services/Search/classes/class.ilUserSearchCache.php';
			$cache =  ilUserSearchCache::_getInstance($ilUser->getId());
			$cache->switchSearchType(ilUserSearchCache::LAST_QUERY);
			$search_string = $cache->getQuery();

			include_once("./Services/UIComponent/TextHighlighter/classes/class.ilTextHighlighterGUI.php");
			include_once("./Services/Search/classes/class.ilQueryParser.php");
			$p = new ilQueryParser($search_string);
			$p->parse();

			$words = $p->getQuotedWords();
			if (is_array($words))
			{
				foreach ($words as $w)
				{
					ilTextHighlighterGUI::highlight("ilCOPageContent", $w, $tpl);
				}
			}
			$this->fill_on_load_code = true;
		}
		
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
		$this->setTemplateOutput(false);
		$this->setPresentationTitle($this->getWikiPage()->getTitle());
		$this->getWikiPage()->increaseViewCnt();
		$output = parent::showPage();
		
		return $output;
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
	
	/**
	* All links to a specific page
	*/
	function whatLinksHere()
	{
		global $tpl;
		
		include_once("./Modules/Wiki/classes/class.ilWikiPagesTableGUI.php");
		
		$this->setSideBlock($_GET["wpg_id"]);
		$table_gui = new ilWikiPagesTableGUI($this, "whatLinksHere",
			$this->getWikiPage()->getWikiId(), IL_WIKI_WHAT_LINKS_HERE, $_GET["wpg_id"]);
			
		$tpl->setContent($table_gui->getHTML());
	}

	function getTabs($a_activate = "")
	{
		global $ilTabs, $ilCtrl;

		parent::getTabs($a_activate);
		
		$ilCtrl->setParameterByClass("ilobjwikigui", "wpg_id",
			ilWikiPage::getPageIdForTitle($this->getPageObject()->getParentId(),
			ilWikiUtil::makeDbTitle($_GET["page"])));
		$ilCtrl->setParameterByClass("ilobjwikigui", "page", ilWikiUtil::makeUrlTitle($_GET["page"]));

		$ilTabs->addTarget("wiki_what_links_here",
			$this->ctrl->getLinkTargetByClass("ilwikipagegui",
			"whatLinksHere"), "whatLinksHere");
		//$ilTabs->addTarget("wiki_print_view",
		//	$this->ctrl->getLinkTargetByClass("ilobjwikigui",
		//	"printViewSelection"), "printViewSelection");
		$ilTabs->addTarget("wiki_print_view",
			$this->ctrl->getLinkTargetByClass("ilwikipagegui",
			"printViewSelection"), "printViewSelection");

	}

	/**
	* Delete wiki page confirmation screen.
	*/
	function deleteWikiPageConfirmationScreen()
	{
		global $ilAccess, $tpl, $ilCtrl, $lng;
		
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
			include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
			$confirmation_gui = new ilConfirmationGUI();
			$confirmation_gui->setFormAction($ilCtrl->getFormAction($this));
			$confirmation_gui->setHeaderText($lng->txt("wiki_page_deletion_confirmation"));
			$confirmation_gui->setCancel($lng->txt("cancel"), "cancelWikiPageDeletion");
			$confirmation_gui->setConfirm($lng->txt("delete"), "confirmWikiPageDeletion");
			
			$dtpl = new ilTemplate("tpl.wiki_page_deletion_confirmation.html", true,
				true, "Modules/Wiki");
				
			$dtpl->setVariable("PAGE_TITLE", $this->getWikiPage()->getTitle());
			
			// other pages that link to this page
			$dtpl->setVariable("TXT_OTHER_PAGES", $lng->txt("wiki_other_pages_linking"));
			$pages = ilWikiPage::getLinksToPage($this->getWikiPage()->getWikiId(),
					$this->getWikiPage()->getId());
			if (count($pages) > 0)
			{
				foreach($pages as $page)
				{
					$dtpl->setCurrentBlock("lpage");
					$dtpl->setVariable("TXT_LINKING_PAGE", $page["title"]);
					$dtpl->parseCurrentBlock();
				}
			}
			else
			{
				$dtpl->setCurrentBlock("lpage");
				$dtpl->setVariable("TXT_LINKING_PAGE", "-");
				$dtpl->parseCurrentBlock();
			}
			
			// contributors
			$dtpl->setVariable("TXT_CONTRIBUTORS", $lng->txt("wiki_contributors"));
			$contributors = ilWikiPage::getPageContributors($this->getWikiPage()->getId());
			foreach($contributors as $contributor)
			{
				$dtpl->setCurrentBlock("contributor");
				$dtpl->setVariable("TXT_CONTRIBUTOR",
					$contributor["lastname"].", ".$contributor["firstname"]);
				$dtpl->parseCurrentBlock();
			}
			
			// notes/comments
			include_once("./Services/Notes/classes/class.ilNote.php");
			$cnt_note_users = ilNote::getUserCount($this->getPageObject()->getParentId(),
				$this->getPageObject()->getId(), "wpg");
			$dtpl->setVariable("TXT_NUMBER_USERS_NOTES_OR_COMMENTS",
				$lng->txt("wiki_number_users_notes_or_comments"));
			$dtpl->setVariable("TXT_NR_NOTES_COMMENTS", $cnt_note_users);
			
			$confirmation_gui->addItem("", "", $dtpl->get());
			
			$tpl->setContent($confirmation_gui->getHTML());
		}
	}

	/**
	* Cancel wiki page deletion
	*/
	function cancelWikiPageDeletion()
	{
		global $lng, $ilCtrl;
		
		$ilCtrl->redirect($this, "preview");
		
	}
	
	/**
	* Delete the wiki page
	*/
	function confirmWikiPageDeletion()
	{
		global $ilAccess, $tpl, $ilCtrl, $lng;
		
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
			$this->getPageObject()->delete();
			
			ilUtil::sendSuccess($lng->txt("wiki_page_deleted"), true);
		}
		
		$ilCtrl->redirectByClass("ilobjwikigui", "allPages");
	}

	////
	//// Print view selection
	////

	/**
	 * Print view selection
	 *
	 * @param
	 * @return
	 */
	function printViewSelection()
	{
		global $ilUser, $lng, $ilToolbar, $ilCtrl, $tpl;

		$ilToolbar->setFormAction($ilCtrl->getFormActionByClass("ilobjwikigui", "printView"),
			false, "print_view");
		$ilToolbar->addFormButton($lng->txt("cont_show_print_view"), "printView");
		$ilToolbar->setCloseFormTag(false);

		$this->initPrintViewSelectionForm();

		$tpl->setContent($this->form->getHTML());
	}

	/**
	 * Init print view selection form.
	 */
	public function initPrintViewSelectionForm()
	{
		global $lng, $ilCtrl;

		$pages = ilWikiPage::getAllPages(ilObject::_lookupObjId($this->getWikiRefId()));

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
//var_dump($pages);
		// selection type
		$radg = new ilRadioGroupInputGUI($lng->txt("cont_selection"), "sel_type");
		$radg->setValue("page");
			$op1 = new ilRadioOption($lng->txt("cont_current_page"), "page");
			$radg->addOption($op1);
			$op2 = new ilRadioOption($lng->txt("wiki_whole_wiki")
				." (".$lng->txt("wiki_pages").": ".count($pages).")", "wiki");
			$radg->addOption($op2);
			$op3= new ilRadioOption($lng->txt("wiki_selected_pages"), "selection");
			$radg->addOption($op3);

			include_once("./Services/Form/classes/class.ilNestedListInputGUI.php");
			$nl = new ilNestedListInputGUI("", "obj_id");
			$op3->addSubItem($nl);

			foreach ($pages as $p)
			{
				$nl->addListNode($p["id"], $p["title"], 0, false, false,
						ilUtil::getImagePath("icon_pg_s.gif"), $lng->txt("wiki_page"));
			}

		$this->form->addItem($radg);

		$this->form->addCommandButton("printView", $lng->txt("cont_show_print_view"));
		//$this->form->setOpenTag(false);
		$this->form->setCloseTag(false);

		$this->form->setTitle($lng->txt("cont_print_selection"));
		//$this->form->setFormAction($ilCtrl->getFormAction($this));
	}

} 
?>
