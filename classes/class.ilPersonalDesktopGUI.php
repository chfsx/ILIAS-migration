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

include_once "classes/class.ilObjUser.php";
include_once "classes/class.ilMail.php";
include_once "classes/class.ilPersonalDesktopGUI.php";


/**
* GUI class for personal desktop
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilPersonalDesktopGUI: ilPersonalProfileGUI, ilBookmarkAdministrationGUI
* @ilCtrl_Calls ilPersonalDesktopGUI: ilObjUserGUI, ilPDNotesGUI, ilLearningProgressGUI, ilFeedbackGUI, ilPaymentGUI, ilPaymentAdminGUI
* @ilCtrl_Calls ilPersonalDesktopGUI: ilColumnGUI
*
*/
class ilPersonalDesktopGUI
{
	var $tpl;
	var $lng;
	var $ilias;
	
	var $cmdClass = '';

	/**
	* constructor
	*/
	function ilPersonalDesktopGUI()
	{
		global $ilias, $tpl, $lng, $rbacsystem, $ilCtrl, $ilMainMenu;
		
		
		$this->tpl =& $tpl;
		$this->lng =& $lng;
		$this->ilias =& $ilias;
		$this->ctrl =& $ilCtrl;
		
		$ilMainMenu->setActive("desktop");
		$this->lng->loadLanguageModule("pdesk");
		
		// catch hack attempts
		if ($_SESSION["AccountId"] == ANONYMOUS_USER_ID)
		{
			$this->ilias->raiseError($this->lng->txt("msg_not_available_for_anon"),$this->ilias->error_obj->MESSAGE);
		}
		$this->cmdClass = $_GET['cmdClass'];
	}
	
	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilUser;

		$next_class = $this->ctrl->getNextClass();
		$this->ctrl->setReturn($this, "show");
		
		// check whether personal profile of user is incomplete
		if ($ilUser->getProfileIncomplete() && $next_class != "ilpersonalprofilegui")
		{
			$this->ctrl->redirectByClass("ilpersonalprofilegui");
		}
		
		// read last active subsection
		if($_GET['PDHistory'])
		{
			$next_class = $this->__loadNextClass();
		}
		$this->__storeLastClass($next_class);

		switch($next_class)
		{
			//Feedback
			case "ilfeedbackgui":
				$this->getStandardTemplates();
				$this->setTabs();
				$this->tpl->setTitle($this->lng->txt("personal_desktop"));
				$this->tpl->setTitleIcon(ilUtil::getImagePath("icon_pd_b.gif"),
					$this->lng->txt("personal_desktop"));

				include_once("Services/Feedback/classes/class.ilFeedbackGUI.php");
				$feedback_gui = new ilFeedbackGUI();
				$ret =& $this->ctrl->forwardCommand($feedback_gui);
				break;
				// bookmarks
			case "ilbookmarkadministrationgui":
				include_once("./Services/PersonalDesktop/classes/class.ilBookmarkAdministrationGUI.php");
				$bookmark_gui = new ilBookmarkAdministrationGUI();
				if ($bookmark_gui->getMode() == 'tree') {
					$this->getTreeModeTemplates();
				} else {
					$this->getStandardTemplates();
				}
				$this->setTabs();
				$ret =& $this->ctrl->forwardCommand($bookmark_gui);
				break;
			
				// profile
			case "ilpersonalprofilegui":
				$this->getStandardTemplates();
				$this->setTabs();
				include_once("classes/class.ilPersonalProfileGUI.php");
				$profile_gui = new ilPersonalProfileGUI();
				$ret =& $this->ctrl->forwardCommand($profile_gui);
				break;
			
				// profile
			case "ilobjusergui":
				include_once("classes/class.ilObjUserGUI.php");
				$user_gui = new ilObjUserGUI("",$_GET["user"], false, false);
				$ret =& $this->ctrl->forwardCommand($user_gui);
				break;
			
				// pd notes
			case "ilpdnotesgui":
				$this->getStandardTemplates();
				$this->setTabs();
				include_once("classes/class.ilPDNotesGUI.php");
				$pd_notes_gui = new ilPDNotesGUI();
				$ret =& $this->ctrl->forwardCommand($pd_notes_gui);
				break;
			
			case "illearningprogressgui":
				$this->getStandardTemplates();
				$this->setTabs();
			
				include_once './Services/Tracking/classes/class.ilLearningProgressGUI.php';
			
				$new_gui =& new ilLearningProgressGUI(LP_MODE_PERSONAL_DESKTOP,0);
				$ret =& $this->ctrl->forwardCommand($new_gui);
			
				break;
			
				// payment
			case "ilpaymentgui":
				$this->showShoppingCart();
				break;

			case "ilpaymentadmingui":
				$this->getStandardTemplates();
				$this->setTabs();
				include_once("./payment/classes/class.ilPaymentAdminGUI.php");
				$pa =& new ilPaymentAdminGUI($ilUser);
				$ret =& $this->ctrl->forwardCommand($pa);
				$this->tpl->show();
				break;

			case "ilcolumngui":
				$this->getStandardTemplates();
				$this->setTabs();
				include_once("./Services/Block/classes/class.ilColumnGUI.php");
				$column_gui = new ilColumnGUI("pd");
				$this->show();
				break;
				
			default:
				$this->getStandardTemplates();
				$this->setTabs();
				$cmd = $this->ctrl->getCmd("show");
				$this->$cmd();
				break;
		}
		return true;
	}
	
	function showShoppingCart()
	{
		global $ilUser;
		$this->getStandardTemplates();
		$this->setTabs();
		include_once("./payment/classes/class.ilPaymentGUI.php");
		$pa =& new ilPaymentGUI($ilUser);
		$ret =& $this->ctrl->forwardCommand($pa);
		$this->tpl->show();
		return true;
	}

	/**
	* get standard templates
	*/
	function getStandardTemplates()
	{
		// add template for content
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
	}
	
	/**
	* get tree mode templates
	*/
	function getTreeModeTemplates()
	{
		// add template for content
		//$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_tree_content.html");
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
	}
	
	/**
	* show desktop
	*/
	function show()
	{
		// add template for content
		$this->pd_tpl = new ilTemplate("tpl.usr_personaldesktop.html", true, true);
		$this->tpl->getStandardTemplate();
		
		// catch feedback message
		sendInfo();
		
		// display infopanel if something happened
		infoPanel();
		
		$this->tpl->setTitleIcon(ilUtil::getImagePath("icon_pd_b.gif"),
			$this->lng->txt("personal_desktop"));
		$this->tpl->setTitle($this->lng->txt("personal_desktop"));
		$this->tpl->setVariable("IMG_SPACE", ilUtil::getImagePath("spacer.gif", false));
		
		$this->tpl->setContent($this->getCenterColumnHTML());
		$this->tpl->setRightContent($this->getRightColumnHTML());
		$this->tpl->setLeftContent($this->getLeftColumnHTML());
		$this->tpl->show();
	}
	
	
	/**
	* Display center column
	*/
	function getCenterColumnHTML()
	{
		global $ilCtrl;
		
		include_once("Services/Block/classes/class.ilColumnGUI.php");
		$column_gui = new ilColumnGUI("pd", IL_COL_CENTER);

		if ($ilCtrl->getNextClass() == "ilcolumngui" &&
			$column_gui->getCmdSide() == IL_COL_CENTER)
		{
			$html = $ilCtrl->forwardCommand($column_gui);
		}
		else
		{
			if (!$ilCtrl->isAsynch())
			{
				if ($column_gui->getScreenMode() != IL_SCREEN_SIDE)
				{
					// right column wants center
					if ($column_gui->getCmdSide() == IL_COL_RIGHT)
					{
						$column_gui = new ilColumnGUI("pd", IL_COL_RIGHT);
						$html = $ilCtrl->forwardCommand($column_gui);
					}
					// left column wants center
					if ($column_gui->getCmdSide() == IL_COL_LEFT)
					{
						$column_gui = new ilColumnGUI("pd", IL_COL_LEFT);
						$html = $ilCtrl->forwardCommand($column_gui);
					}
				}
				else
				{
					$html = $ilCtrl->getHTML($column_gui);
				}
			}
		}
		return $html;
	}

	/**
	* Display right column
	*/
	function getRightColumnHTML()
	{
		global $ilUser, $lng, $ilCtrl;
		
		include_once("Services/Block/classes/class.ilColumnGUI.php");
		$column_gui = new ilColumnGUI("pd", IL_COL_RIGHT);
		
		if ($ilCtrl->getNextClass() == "ilcolumngui" &&
			$column_gui->getCmdSide() == IL_COL_RIGHT &&
			$column_gui->getScreenMode() == IL_SCREEN_SIDE)
		{
			$html = $ilCtrl->forwardCommand($column_gui);
		}
		else
		{
			if (!$ilCtrl->isAsynch())
			{
				$html = $ilCtrl->getHTML($column_gui);
			}
		}

		return $html;
	}

	/**
	* Display left column
	*/
	function getLeftColumnHTML()
	{
		global $ilUser, $lng, $ilCtrl;
		
		include_once("Services/Block/classes/class.ilColumnGUI.php");
		$column_gui = new ilColumnGUI("pd", IL_COL_LEFT);
		
		if ($ilCtrl->getNextClass() == "ilcolumngui" &&
			$column_gui->getCmdSide() == IL_COL_LEFT &&
			$column_gui->getScreenMode() == IL_SCREEN_SIDE)
		{
			$html = $ilCtrl->forwardCommand($column_gui);
		}
		else
		{
			if (!$ilCtrl->isAsynch())
			{
				$html = $ilCtrl->getHTML($column_gui);
			}
		}

		return $html;
	}

	function prepareContentView()
	{
		// add template for content
		$this->pd_tpl = new ilTemplate("tpl.usr_personaldesktop.html", true, true);
		$this->tpl->getStandardTemplate();
		
		// catch feedback message
		sendInfo();
		
		// display infopanel if something happened
		infoPanel();
		
		$this->tpl->setTitleIcon(ilUtil::getImagePath("icon_pd_b.gif"),
			$this->lng->txt("personal_desktop"));
		$this->tpl->setTitle($this->lng->txt("personal_desktop"));
		$this->tpl->setVariable("IMG_SPACE", ilUtil::getImagePath("spacer.gif", false));
	}

	/**
	* drop item from desktop
	*/
	function dropItem()
	{
		global $ilUser;
		
		$ilUser->dropDesktopItem($_GET["item_ref_id"], $_GET["type"]);
		$this->show();
	}
	
	/**
	* copied from usr_personaldesktop.php
	*/
	function removeMember()
	{
		global $err_msg;
		if (strlen($err_msg) > 0)
		{
			$this->ilias->raiseError($this->lng->txt($err_msg),$this->ilias->error_obj->MESSAGE);
		}
		$this->show();
	}	
	
	
	/**
	* order desktop items by location
	*/
	function orderPDItemsByLocation()
	{
		global $ilUser, $ilCtrl;
		
		$ilUser->writePref("pd_order_items", "location");
		
		if ($ilCtrl->isAsynch())
		{
			echo $this->displaySelectedItems();
			exit;
		}
		else
		{
			$this->show();
		}
	}
	
	/**
	* order desktop items by Type
	*/
	function orderPDItemsByType()
	{
		global $ilUser, $ilCtrl;
		
		$ilUser->writePref("pd_order_items", "type");
		if ($ilCtrl->isAsynch())
		{
			echo $this->displaySelectedItems();
			exit;
		}
		else
		{
			$this->show();
		}
	}
	
	

	
	/**
	* Display system messages.
	*/
	function displaySystemMessages()
	{
		include_once("Services/Mail/classes/class.ilPDSysMessageBlockGUI.php");
		$sys_block = new ilPDSysMessageBlockGUI("ilpersonaldesktopgui", "show");
		return $sys_block->getHTML();
	}
	
	
	/**
	* Display Links for Feedback
	*/
	function displayFeedback()
	{
		include_once("./Services/Feedback/classes/class.ilPDFeedbackBlockGUI.php");
		$fb_block = new ilPDFeedbackBlockGUI("ilpersonaldesktopgui", "show");
		return $fb_block->getHTML();

		include_once('Services/Feedback/classes/class.ilFeedbackGUI.php');
		$feedback_gui = new ilFeedbackGUI();
		return $feedback_gui->getPDFeedbackListHTML();
	}
	
	/**
	* Returns the multidimenstional sorted array
	*
	* Returns the multidimenstional sorted array
	*
	* @author       Muzaffar Altaf <maltaf@tzi.de>
	* @param array $arrays The array to be sorted
	* @param string $key_sort The keys on which array must be sorted
	* @access public
	*/
	function multiarray_sort ($array, $key_sort)
	{
		if ($array) {
			$key_sorta = explode(";", $key_sort);
			
			$multikeys = array_keys($array);
			$keys = array_keys($array[$multikeys[0]]);
			
			for($m=0; $m < count($key_sorta); $m++) {
				$nkeys[$m] = trim($key_sorta[$m]);
			}
			$n += count($key_sorta);
			
			for($i=0; $i < count($keys); $i++){
				if(!in_array($keys[$i], $key_sorta)) {
					$nkeys[$n] = $keys[$i];
					$n += "1";
				}
			}
			
			for($u=0;$u<count($array); $u++) {
				$arr = $array[$multikeys[$u]];
				for($s=0; $s<count($nkeys); $s++) {
					$k = $nkeys[$s];
					$output[$multikeys[$u]][$k] = $array[$multikeys[$u]][$k];
				}
			}
			sort($output);
			return $output;
		}
	}
	
	/**
	* set personal desktop tabs
	*/
	function setTabs()
	{
		global $ilCtrl;
		
		$this->tpl->addBlockFile("TABS", "tabs", "tpl.tabs.html");
		
		$script_name = basename($_SERVER["SCRIPT_NAME"]);
		
		$command = $_GET["cmd"] ? $_GET["cmd"] : "";
		
		if (ereg("whois",$command) or $script_name == "profile.php")
		{
			$who_is_online = true;
		}
		
		// to do: use ilTabsGUI here!
		
		// personal desktop home
		$inc_type = (strtolower($_GET["baseClass"]) == "ilpersonaldesktopgui" &&
		(strtolower($this->cmdClass) == "ilpersonaldesktopgui" ||
		$this->cmdClass == "" || (strtolower($this->cmdClass)) == "ilfeedbackgui"
		|| $ilCtrl->getNextClass() == "ilcolumngui"))
		? "tabactive"
		: "tabinactive";
		$inhalt1[] = array($inc_type, $this->ctrl->getLinkTarget($this), $this->lng->txt("overview"));
		
		// user profile
		$inc_type = (strtolower($this->cmdClass) == "ilpersonalprofilegui")
		? "tabactive"
		: "tabinactive";
		$inhalt1[] = array($inc_type, $this->ctrl->getLinkTargetByClass("ilPersonalProfileGUI"),
		$this->lng->txt("personal_profile"));
		
		if ($_SESSION["AccountId"] != ANONYMOUS_USER_ID)
		{
			// user calendar
			if ($this->ilias->getSetting("enable_calendar"))
			{
				$inc_type = ($script_name == "dateplaner.php")
				? "tabactive"
				: "tabinactive";
				$inhalt1[] = array($inc_type,"dateplaner.php",$this->lng->txt("calendar"));
			}
			
			// private notes
			$inc_type = (strtolower($this->cmdClass) == "ilpdnotesgui" ||
			strtolower($this->cmdClass) == "ilnotegui")
			? "tabactive"
			: "tabinactive";
			$inhalt1[] = array($inc_type,
			$this->ctrl->getLinkTargetByClass("ilpdnotesgui"),
			$this->lng->txt("private_notes"));
			
			// user bookmarks
			$inc_type = (strtolower($this->cmdClass) == "ilbookmarkadministrationgui")
			? "tabactive"
			: "tabinactive";
			$inhalt1[] = array($inc_type,
			$this->ctrl->getLinkTargetByClass("ilbookmarkadministrationgui"),
			$this->lng->txt("bookmarks"));
			
		}
		
		// Tracking
		
		include_once("Services/Tracking/classes/class.ilObjUserTracking.php");
		if (ilObjUserTracking::_enabledLearningProgress())
		{
			$cmd_classes = array('illplistofobjectsgui','illplistofsettingsgui','illearningprogressgui','illplistofprogressgui');
			$inc_type = in_array(strtolower($this->cmdClass),$cmd_classes) ? 'tabactive' : 'tabinactive';
			
			$inhalt1[] = array($inc_type, $this->ctrl->getLinkTargetByClass("ilLearningProgressGUI"),
			$this->lng->txt("learning_progress"));
		}
		
		include_once "./payment/classes/class.ilPaymentVendors.php";
		include_once "./payment/classes/class.ilPaymentTrustees.php";
		include_once "./payment/classes/class.ilPaymentShoppingCart.php";
		include_once "./payment/classes/class.ilPaymentBookings.php";
		
		if(ilPaymentShoppingCart::_hasEntries($this->ilias->account->getId()) or
		ilPaymentBookings::_getCountBookingsByCustomer($this->ilias->account->getId()))
		{
			$this->lng->loadLanguageModule('payment');

			$cmd_classes = array('ilpaymentgui','ilpaymentshoppingcartgui','ilpaymentbuyedobjectsgui');
			$inc_type = in_array(strtolower($this->cmdClass),$cmd_classes) ? 'tabactive' : 'tabinactive';

			$inhalt1[] = array($inc_type, $this->ctrl->getLinkTargetByClass("ilPaymentGUI"),
			$this->lng->txt("paya_shopping_cart"));
		}
		if(ilPaymentVendors::_isVendor($this->ilias->account->getId()) or
		ilPaymentTrustees::_hasAccess($this->ilias->account->getId()))
		{
			$this->lng->loadLanguageModule('payment');

			$cmd_classes = array('ilpaymentstatisticgui','ilpaymentobjectgui','ilpaymenttrusteegui','ilpaymentadmingui');
			$inc_type = in_array(strtolower($this->cmdClass),$cmd_classes) ? 'tabactive' : 'tabinactive';

			$inhalt1[] = array($inc_type, $this->ctrl->getLinkTargetByClass("ilPaymentAdminGUI"),
			$this->lng->txt("paya_header"));
		}
		
		for ( $i=0; $i<sizeof($inhalt1); $i++)
		{
			if ($inhalt1[$i][1] != "")
			{	$this->tpl->setCurrentBlock("tab");
				$this->tpl->setVariable("TAB_TYPE",$inhalt1[$i][0]);
				$this->tpl->setVariable("TAB_LINK",$inhalt1[$i][1]);
				$this->tpl->setVariable("TAB_TEXT",$inhalt1[$i][2]);
				$this->tpl->setVariable("TAB_TARGET",$inhalt1[$i][3]);
				$this->tpl->parseCurrentBlock();
			}
		}
		
		$this->tpl->setCurrentBlock("tabs");
		$this->tpl->parseCurrentBlock();
	}
	
	/**
	* workaround for menu in calendar only
	*/
	function jumpToProfile()
	{
		$this->ctrl->redirectByClass("ilpersonalprofilegui");
	}
	
	/**
	* workaround for menu in calendar only
	*/
	function jumpToBookmarks()
	{
		$this->ctrl->redirectByClass("ilbookmarkadministrationgui");
	}
	
	/**
	* workaround for menu in calendar only
	*/
	function jumpToNotes()
	{
		$this->ctrl->redirectByClass("ilpdnotesgui");
	}
	
	/**
	* workaround for menu in calendar only
	*/
	function jumpToLP()
	{
		$this->ctrl->redirectByClass("illearningprogressgui");
	}

	function __loadNextClass()
	{
		$stored_classes = array('ilpersonaldesktopgui',
								'ilpersonalprofilegui',
								'ilpdnotesgui',
								'ilbookmarkadministrationgui',
								'illearningprogressgui',
								'ilpaymentadmingui');

		if(isset($_SESSION['il_pd_history']) and in_array($_SESSION['il_pd_history'],$stored_classes))
		{
			return $_SESSION['il_pd_history'];
		}
		else
		{
			$this->ctrl->getNextClass($this);
		}
	}
	function __storeLastClass($a_class)
	{
		$_SESSION['il_pd_history'] = $a_class;
		$this->cmdClass = $a_class;
	}
	
	/**
	* show profile of other user
	*/
	function showUserProfile()
	{
		global $lng, $ilCtrl;
		
		$this->prepareContentView();
		
		include_once("classes/class.ilObjUserGUI.php");
		$user_gui = new ilObjUserGUI("",$_GET["user"], false, false);
		
		include_once("./Services/PersonalDesktop/classes/class.ilPDContentBlockGUI.php");
		$content_block = new ilPDContentBlockGUI("ilpersonaldesktopgui", "show");
		$content_block->setContent($user_gui->getPublicProfile("", false, true));
		$content_block->setTitle($lng->txt("profile_of")." ".
			$user_gui->object->getLogin());
		$content_block->setColSpan(2);
		$content_block->setImage(ilUtil::getImagePath("icon_usr.gif"));
		$content_block->addHeaderCommand($ilCtrl->getLinkTarget($this, "show"),
			$lng->txt("close"));
		
		$this->tpl->setContent($content_block->getHTML());
		$this->tpl->setRightContent($this->getRightColumnHTML());
		$this->tpl->setLeftContent($this->getLeftColumnHTML());

		$this->tpl->show();
	}

}
?>