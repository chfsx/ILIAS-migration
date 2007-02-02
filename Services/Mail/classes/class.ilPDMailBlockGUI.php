<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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

include_once("Services/Block/classes/class.ilBlockGUI.php");

/**
* BlockGUI class for Personal Desktop Mail block
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_IsCalledBy ilPDMailBlockGUI: ilColumnGUI
*/
class ilPDMailBlockGUI extends ilBlockGUI
{
	static $block_type = "pdmail";
	
	/**
	* Constructor
	*/
	function ilPDMailBlockGUI()
	{
		global $ilCtrl, $lng, $ilUser;
		
		include_once "./include/inc.mail.php";
		include_once "classes/class.ilObjUser.php";
		include_once "classes/class.ilMailbox.php";
		include_once "classes/class.ilMail.php";

		
		parent::ilBlockGUI();
		
		$this->setLimit(5);
		$this->setImage(ilUtil::getImagePath("icon_mail_s.gif"));
		$this->setTitle($lng->txt("mail"));
		$this->setAvailableDetailLevels(3);
	}
	
	/**
	* Get block type
	*
	* @return	string	Block type.
	*/
	function getBlockType()
	{
		return self::$block_type;
	}
	
	/**
	* Get Screen Mode for current command.
	*/
	static function getScreenMode()
	{
		switch($_GET["cmd"])
		{
			case "showMail":
				return IL_SCREEN_CENTER;
				break;
				
			default:
				return IL_SCREEN_SIDE;
				break;
		}
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilCtrl;

		$next_class = $ilCtrl->getNextClass();
		$cmd = $ilCtrl->getCmd("getHTML");

		return $this->$cmd();
	}

	function getHTML()
	{
		if ($this->getCurrentDetailLevel() == 0)
		{
			return "";
		}
		else
		{
			$html = parent::getHTML();
			return $html;
		}
	}
	
	/**
	* Get Mails
	*/
	function getMails()
	{
		global $ilUser;
		
		// BEGIN MAILS
		$umail = new ilMail($_SESSION["AccountId"]);
		$mbox = new ilMailBox($_SESSION["AccountId"]);
		$inbox = $mbox->getInboxFolder();
		
		//SHOW MAILS FOR EVERY USER
		$mail_data = $umail->getMailsOfFolder($inbox);
		$mail_counter = $umail->getMailCounterData();
		$unreadmails = 0;
	
		$this->mails = array();
		foreach ($mail_data as $mail)
		{
			//ONLY NEW MAILS WOULD BE ON THE PERONAL DESKTOP
			if($mail["m_status"] == 'unread')
			{
				$this->mails[] = $mail;
			}
		}
	}
	
	/**
	* Fill data section
	*/
	function fillDataSection()
	{
		$this->getMails();
		$this->setData($this->mails);

		if ($this->getCurrentDetailLevel() > 1 && count($this->mails) > 0)
		{
			$this->setRowTemplate("tpl.pd_mail_row.html", "Services/Mail");
			if ($this->getCurrentDetailLevel() > 2)
			{
				$this->setColSpan(2);
			}
			parent::fillDataSection();
		}
		else
		{
			$this->setEnableNumInfo(false);
			$this->setDataSection($this->getOverview());
		}
	}
		
	/**
	* get flat bookmark list for personal desktop
	*/
	function fillRow($mail)
	{
		global $ilUser, $ilCtrl, $lng;

		// GET SENDER NAME
		$user = new ilObjUser($mail["sender_id"]);
		
		if(!($fullname = $user->getFullname()))
		{
			$fullname = $lng->txt("unknown");
		}

		if ($this->getCurrentDetailLevel() > 2)
		{
			$this->tpl->setCurrentBlock("image");
			$this->tpl->setVariable("IMG_SENDER", $user->getPersonalPicturePath("xxsmall"));
			$this->tpl->setVariable("ALT_SENDER", $user->getLogin());
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("long");
			$this->tpl->setVariable("NEW_MAIL_FROM", $fullname);
			$this->tpl->setVariable("NEW_MAIL_FROM_LOGIN", $user->getLogin());
			$this->tpl->setVariable("NEW_MAIL_DATE", substr($mail["send_time"],0,10));
			$this->tpl->setVariable("TXT_FROM", $lng->txt("from"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$this->tpl->setCurrentBlock("short");
			$this->tpl->setVariable("NEW_MAIL_FROM_LOGIN", $user->getLogin());
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setVariable("NEW_MAIL_SUBJ", $mail["m_subject"]);
		$ilCtrl->setParameter($this, "mobj_id", $inbox);
		$ilCtrl->setParameter($this, "mail_id", $mail["mail_id"]);
		$ilCtrl->setParameter($this, "mail_mode", $this->mail_mode);
		$this->tpl->setVariable("NEW_MAIL_LINK_READ",
			$ilCtrl->getLinkTarget($this, "showMail"));
		$ilCtrl->clearParameters($this);
	}

	/**
	* Get overview.
	*/
	function getOverview()
	{
		global $ilUser, $lng, $ilCtrl;
				
		return '<div class="small">'.((int) count($this->mails))." ".$lng->txt("mails_pl")."</div>";
	}

	/**
	* show mail
	*/
	function showMail()
	{
		global $lng, $ilCtrl;
		
		include_once("./Services/Mail/classes/class.ilPDMailGUI.php");
		$mail_gui = new ilPDMailGUI();

		include_once("./Services/PersonalDesktop/classes/class.ilPDContentBlockGUI.php");
		$content_block = new ilPDContentBlockGUI();
		$content_block->setContent($mail_gui->getPDMailHTML($_GET["mail_id"],
			$_GET["mobj_id"]));
		$content_block->setTitle($lng->txt("message"));
		$content_block->setColSpan(2);
		$content_block->setImage(ilUtil::getImagePath("icon_mail.gif"));
		$content_block->addHeaderCommand($ilCtrl->getLinkTargetByClass("ilpersonaldesktopgui", "show"),
			$lng->txt("close"), true);
			
		if ($_GET["mail_mode"] != "system")
		{
			$content_block->addBlockCommand("mail_new.php?mail_id=".
				$_GET["mail_id"]."&mobj_id".$_GET["mobj_id"]."&type=reply",
				$lng->txt("reply"));
			$content_block->addBlockCommand("mail_read.php?mail_id=".
				$_GET["mail_id"]."&mobj_id".$_GET["mobj_id"],
				$lng->txt("inbox"));
		}
		else
		{
			$ilCtrl->setParameter($this, "mail_id", $_GET["mail_id"]);
			$ilCtrl->setParameter($this, "mobj_id", $_GET["mobj_id"]);
			$content_block->addBlockCommand(
				$ilCtrl->getLinkTarget($this, "deleteMail"),
				$lng->txt("delete"));
			$ilCtrl->clearParameters($this);
		}
		
		return $content_block->getHTML();
	}

	/**
	* delete mail
	*/
	function deleteMail()
	{
		global $lng, $ilCtrl;
		
		$lng->loadLanguageModule("mail");
		
		$umail = new ilMail($_SESSION["AccountId"]);
		$mbox = new ilMailBox($_SESSION["AccountId"]);

		// IF THERE IS NO OBJ_ID GIVEN GET THE ID OF MAIL ROOT NODE
		if(!$_GET["mobj_id"])
		{
			$_GET["mobj_id"] = $mbox->getInboxFolder();
		}

		if ($umail->moveMailsToFolder(array($_GET["mail_id"]),
			$mbox->getTrashFolder()))
		{
			ilUtil::sendInfo($lng->txt("mail_moved_to_trash"), true);
		}
		else
		{
			ilUtil::sendInfo($lng->txt("mail_move_error"), true);
		}
		$ilCtrl->redirectByClass("ilpersonaldesktopgui", "show");
	}

}

?>
