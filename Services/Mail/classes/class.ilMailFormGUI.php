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

require_once "classes/class.ilObjUser.php";
require_once "classes/class.ilMailbox.php";
require_once "classes/class.ilFormatMail.php";
require_once "classes/class.ilFileDataMail.php";

/**
* @author Jens Conze
* @version $Id$
*
* @ingroup ServicesMail
* @ilCtrl_Calls ilMailFormGUI: ilMailAttachmentGUI, ilMailSearchGUI, ilMailSearchCoursesGUI, ilMailSearchGroupsGUI
*/
class ilMailFormGUI
{
	private $tpl = null;
	private $ctrl = null;
	private $lng = null;
	
	private $umail = null;
	private $mbox = null;
	private $mfile = null;

	public function __construct()
	{
		global $tpl, $ilCtrl, $lng, $ilUser;

		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		
		$this->ctrl->saveParameter($this, "mobj_id");

		$this->umail = new ilFormatMail($ilUser->getId());
		$this->mfile = new ilFileDataMail($ilUser->getId());
		$this->mbox = new ilMailBox($ilUser->getId());
	}

	public function executeCommand()
	{
		$forward_class = $this->ctrl->getNextClass($this);
		switch($forward_class)
		{
			case 'ilmailattachmentgui':
				include_once 'Services/Mail/classes/class.ilMailAttachmentGUI.php';

				$this->ctrl->setReturn($this, "returnFromAttachments");
				$this->ctrl->forwardCommand(new ilMailAttachmentGUI());
				break;

			case 'ilmailsearchgui':
				include_once 'Services/Mail/classes/class.ilMailSearchGUI.php';

				$this->ctrl->setReturn($this, "searchResults");
				$this->ctrl->forwardCommand(new ilMailSearchGUI());
				break;

			case 'ilmailsearchcoursesgui':
				include_once 'Services/Mail/classes/class.ilMailSearchCoursesGUI.php';

				$this->ctrl->setReturn($this, "searchResults");
				$this->ctrl->forwardCommand(new ilMailSearchCoursesGUI());
				break;

			case 'ilmailsearchgroupsgui':
				include_once 'Services/Mail/classes/class.ilMailSearchGroupsGUI.php';

				$this->ctrl->setReturn($this, "searchResults");
				$this->ctrl->forwardCommand(new ilMailSearchGroupsGUI());
				break;

			default:
				if (!($cmd = $this->ctrl->getCmd()))
				{
					$cmd = "showForm";
				}

				$this->$cmd();
				break;
		}
		return true;
	}

	public function sendMessage()
	{
		$f_message = $this->umail->formatLinebreakMessage(ilUtil::stripSlashes($_POST["m_message"]));
		$this->umail->setSaveInSentbox(true);
		if($errorMessage = $this->umail->sendMail($_POST["rcp_to"],$_POST["rcp_cc"],
													 $_POST["rcp_bcc"],ilUtil::stripSlashes($_POST["m_subject"]),$f_message,
													 $_POST["attachments"],$_POST["m_type"]))
		{
			ilUtil::sendInfo($errorMessage);
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt("mail_message_send",true));
			ilUtil::redirect("ilias.php?baseClass=ilMailGUI&mobj_id=".$this->mbox->getInboxFolder());
		}

		$this->showForm();
	}

	public function saveDraft()
	{ 
		if(!$_POST["m_subject"])
		{
			$_POST["m_subject"] = "No title";
		}

		$draftsId = $this->mbox->getDraftsFolder();
		
		if(isset($_SESSION["draft"]))
		{
			$this->umail->updateDraft($draftsId,$_POST["attachments"],$_POST["rcp_to"],$_POST["rcp_cc"],
									  $_POST["rcp_bcc"],$_POST["m_type"],$_POST["m_email"],
									  ilUtil::stripSlashes($_POST["m_subject"]),
									  ilUtil::stripSlashes($_POST["m_message"]),$_SESSION["draft"]);
			session_unregister("draft");
			ilUtil::sendInfo($this->lng->txt("mail_saved"),true);
			ilUtil::redirect("ilias.php?baseClass=ilMailGUI&mobj_id=".$mbox->getInboxFolder());
		}
		else
		{
			if($this->umail->sendInternalMail($drafts_id,$_SESSION["AccountId"],$_POST["attachments"],$_POST["rcp_to"],$_POST["rcp_cc"],
												$_POST["rcp_bcc"],'read',$_POST["m_type"],$_POST["m_email"],
												ilUtil::stripSlashes($_POST["m_subject"]),
												ilUtil::stripSlashes($_POST["m_message"]),$_SESSION["AccountId"]))
			{
				ilUtil::sendInfo($this->lng->txt("mail_saved"),true);
				ilUtil::redirect("ilias.php?baseClass=ilMailGUI&mobj_id=".$mbox->getInboxFolder());
			}
			else
			{
				ilUtil::sendInfo($this->lng->txt("mail_send_error"));
			}
		}
		
		$this->showForm();
	}

	public function searchRcpTo()
	{
		$_SESSION["mail_search"] = 'to';
		ilUtil::sendInfo($this->lng->txt("mail_insert_query"));

		$this->showSearchForm();
	}

	public function searchCoursesTo()
	{
		global $ilUser;

		$this->umail->savePostData($ilUser->getId(),$_POST["attachments"],$_POST["rcp_to"],
									 $_POST["rcp_cc"],$_POST["rcp_bcc"],$_POST["m_type"],
									 $_POST["m_email"],
									 ilUtil::stripSlashes($_POST["m_subject"]),
									 ilUtil::stripSlashes($_POST["m_message"]));

		$this->ctrl->setParameterByClass("ilmailsearchcoursesgui", "ref", "mail");
		$this->ctrl->redirectByClass("ilmailsearchcoursesgui");
	}

	public function searchGroupsTo()
	{
		global $ilUser;

		$this->umail->savePostData($ilUser->getId(),$_POST["attachments"],$_POST["rcp_to"],
									 $_POST["rcp_cc"],$_POST["rcp_bcc"],$_POST["m_type"],
									 $_POST["m_email"],
									 ilUtil::stripSlashes($_POST["m_subject"]),
									 ilUtil::stripSlashes($_POST["m_message"]));

		$this->ctrl->setParameterByClass("ilmailsearchgroupsgui", "ref", "mail");
		$this->ctrl->redirectByClass("ilmailsearchgroupsgui");
	}

	public function searchRcpCc()
	{
		$_SESSION["mail_search"] = 'cc';
		ilUtil::sendInfo($this->lng->txt("mail_insert_query"));

		$this->showSearchForm();
	}

	public function searchRcpBc()
	{
		$_SESSION["mail_search"] = 'bc';
		ilUtil::sendInfo($this->lng->txt("mail_insert_query"));

		$this->showSearchForm();
	}

	private function showSearchForm()
	{
		$this->tpl->setCurrentBlock("search");
		$this->tpl->setVariable("TXT_SEARCH_FOR",$this->lng->txt("search_for"));
		$this->tpl->setVariable("TXT_SEARCH_SYSTEM",$this->lng->txt("mail_search_system"));
		$this->tpl->setVariable("TXT_SEARCH_ADDRESS",$this->lng->txt("mail_search_addressbook"));
		$this->tpl->setVariable("BUTTON_SEARCH",$this->lng->txt("search"));
		$this->tpl->setVariable("BUTTON_CANCEL",$this->lng->txt("cancel"));
		if (strlen(trim($_POST['search'])) > 0)
		{
			$this->tpl->setVariable("VALUE_SEARCH_FOR", ilUtil::prepareFormOutput(trim($_POST["search"]), true));
		}
		$this->tpl->parseCurrentBlock();

		$this->showForm();
	}

	public function search()
	{
		global $ilUser;

		$this->umail->savePostData($ilUser->getId(),$_POST["attachments"],$_POST["rcp_to"],
									 $_POST["rcp_cc"],$_POST["rcp_bcc"],$_POST["m_type"],
									 $_POST["m_email"],
									 ilUtil::stripSlashes($_POST["m_subject"]),
									 ilUtil::stripSlashes($_POST["m_message"]));
		// IF NO TYPE IS GIVEN SEARCH IN BOTH 'system' and 'addressbook'
		if(!$_POST["type_system"] &&
			!$_POST["type_addressbook"])
		{
			$_POST["type_system"] = 1;
			$_POST["type_addressbook"] = 1;
		}
		if(strlen(trim($_POST['search'])) == 0)
		{
			ilUtil::sendInfo($this->lng->txt("mail_insert_query"));
			$this->showSearchForm();
		}
		else if(strlen(trim($_POST['search'])) < 3)
		{
			$this->lng->loadLanguageModule('search');
			ilUtil::sendInfo($this->lng->txt('search_minimum_three'));
			$this->showSearchForm();
		}
		else
		{
			$this->ctrl->setParameterByClass("ilmailsearchgui", "search", urlencode($_POST["search"]));
			if($_POST["type_system"])
			{
				$this->ctrl->setParameterByClass("ilmailsearchgui", "system", 1);
			}
			if($_POST["type_addressbook"])
			{
				$this->ctrl->setParameterByClass("ilmailsearchgui", "addressbook", 1);
			}
			$this->ctrl->redirectByClass("ilmailsearchgui");
		}
	}

	public function cancelSearch()
	{
		unset($_SESSION["mail_search"]);

		$this->showForm();
	}

	public function editAttachments()
	{
		$this->umail->savePostData($_SESSION["AccountId"],$_POST["attachments"],
									$_POST["rcp_to"],$_POST["rcp_cc"],$_POST["rcp_bcc"],$_POST["m_type"],
									$_POST["m_email"],
							 		ilUtil::stripSlashes($_POST["m_subject"]),
									ilUtil::stripSlashes($_POST["m_message"]));
			
		$this->ctrl->redirectByClass("ilmailattachmentgui");
	}

	public function returnFromAttachments()
	{
		$_GET["type"] = "attach";
		$this->showForm();
	} 
	
	public function searchResults()
	{
		$_GET["type"] = "search_res";
		$this->showForm();		
	}

	public function showForm()
	{
		global $rbacsystem;

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.mail_new.html", "Services/Mail");
		$this->tpl->setVariable("HEADER", $this->lng->txt("mail"));

		switch($_GET["type"])
		{
			case 'reply':
				$mailData = $this->umail->getMail($_GET["mail_id"]);
				$mailData["m_subject"] = $this->umail->formatReplySubject();
				$mailData["m_message"] = $this->umail->formatReplyMessage(); 
				$mailData["m_message"] = $this->umail->appendSignature();
				// NO ATTACHMENTS FOR REPLIES
				$mailData["attachments"] = array();
				$mailData["rcp_to"] = $this->umail->formatReplyRecipient();
				break;
		
			case 'search_res':
				$mailData = $this->umail->getSavedData();
				if($_SESSION["mail_search_results"])
				{
					$mailData = $this->umail->appendSearchResult($_SESSION["mail_search_results"],$_SESSION["mail_search"]);
				}
				unset($_SESSION["mail_search"]);
				unset($_SESSION["mail_search_results"]);
				break;
		
			case 'attach':
				$mailData = $this->umail->getSavedData();
				break;
		
			case 'draft':
				$_SESSION["draft"] = $_GET["mail_id"];
				$mailData = $this->umail->getMail($_GET["mail_id"]);
				break;
		
			case 'forward':
				$mailData = $this->umail->getMail($_GET["mail_id"]);
				$mailData["rcp_to"] = $mailData["rcp_cc"] = $mailData["rcp_bcc"] = '';
				$mailData["m_subject"] = $this->umail->formatForwardSubject();
				$mailData["m_message"] = $this->umail->appendSignature();
				if(count($mailData["attachments"]))
				{
					if($error = $this->mfile->adoptAttachments($mailData["attachments"],$_GET["mail_id"]))
					{
						ilUtil::sendInfo($error);
					}
				}
				break;
		
			case 'new':
				$mailData["rcp_to"] = $_GET['rcp_to'];
				$mailData["m_message"] = $this->umail->appendSignature();
				break;
		
			case 'role':
		
				if(is_array($_POST['roles']))
				{
					$mailData['rcp_to'] = implode(',',$_POST['roles']);
				}
				elseif(is_array($_SESSION['mail_roles']))
				{
					$mailData['rcp_to'] = implode(',',$_SESSION['mail_roles']);
				}
		
				$mailData['m_message'] = $_POST["additional_message_text"].chr(13).chr(10).$this->umail->appendSignature();
				$_POST["additional_message_text"] = "";
				$_SESSION['mail_roles'] = "";
				break;
		
			case 'address':
				$mailData["rcp_to"] = urldecode($_GET["rcp"]);
				break;
		
			default:
				// GET DATA FROM POST
				$mailData = $_POST;
				break;
		}
		$this->ctrl->setParameter($this, "cmd", "post");
		$this->tpl->setVariable("ACTION", $this->ctrl->getLinkTarget($this));
		$this->ctrl->clearParameters($this);

		// RECIPIENT
		$this->tpl->setVariable("TXT_RECIPIENT", $this->lng->txt("mail_to"));
		$this->tpl->setVariable("TXT_SEARCH_RECIPIENT", $this->lng->txt("search_recipient"));
		$this->lng->loadLanguageModule("crs");
		$this->tpl->setVariable("BUTTON_COURSES_TO",$this->lng->txt("mail_my_courses"));
		$this->tpl->setVariable("BUTTON_GROUPS_TO",$this->lng->txt("mail_my_groups"));
		$this->tpl->setVariable("BUTTON_TO",$this->lng->txt("mail_to_search"));
		
		// CC
		$this->tpl->setVariable("TXT_CC", $this->lng->txt("cc"));
		$this->tpl->setVariable("TXT_SEARCH_CC_RECIPIENT", $this->lng->txt("search_cc_recipient"));
		$this->tpl->setVariable("BUTTON_CC",$this->lng->txt("mail_cc_search"));
		// BCC
		$this->tpl->setVariable("TXT_BC", $this->lng->txt("bc"));
		$this->tpl->setVariable("TXT_SEARCH_BC_RECIPIENT", $this->lng->txt("search_bc_recipient"));
		$this->tpl->setVariable("BUTTON_BC",$this->lng->txt("mail_bc_search"));
		// SUBJECT
		$this->tpl->setVariable("TXT_SUBJECT", $this->lng->txt("subject"));
		
		// TYPE
		$this->tpl->setVariable("TXT_TYPE", $this->lng->txt("type"));
		$this->tpl->setVariable("TXT_NORMAL", $this->lng->txt("mail_intern"));
		if(!is_array($mailData["m_type"]) or (is_array($mailData["m_type"]) and in_array('normal',$mailData["m_type"])))
		{
			$this->tpl->setVariable("CHECKED_NORMAL",'checked="checked"');
		}
		
		// ONLY IF SYSTEM MAILS ARE ALLOWED
		if($rbacsystem->checkAccess("system_message",$this->umail->getMailObjectReferenceId()))
		{
			$this->tpl->setCurrentBlock("system_message");
			$this->tpl->setVariable("SYSTEM_TXT_TYPE", $this->lng->txt("type"));
			$this->tpl->setVariable("TXT_SYSTEM", $this->lng->txt("system_message"));
			if(is_array($mailData["m_type"]) and in_array('system',$mailData["m_type"]))
			{
				$this->tpl->setVariable("CHECKED_SYSTEM",'checked="checked"');
			}
			$this->tpl->parseCurrentBlock();
		}
			
		// ONLY IF SMTP MAILS ARE ALLOWED
		if($rbacsystem->checkAccess("smtp_mail",$this->umail->getMailObjectReferenceId()))
		{
			$this->tpl->setCurrentBlock("allow_smtp");
			$this->tpl->setVariable("TXT_EMAIL", $this->lng->txt("email"));
			if(is_array($mailData["m_type"]) and in_array('email',$mailData["m_type"]))
			{
				$this->tpl->setVariable("CHECKED_EMAIL",'checked="checked"');
			}
			$this->tpl->parseCurrentBlock();
		}
		
		// ATTACHMENT
		$this->tpl->setVariable("TXT_ATTACHMENT",$this->lng->txt("mail_attachments"));
		// SWITCH BUTTON 'add' 'edit'
		if($mailData["attachments"])
		{
			$this->tpl->setVariable("BUTTON_EDIT",$this->lng->txt("edit"));
		}
		else
		{
			$this->tpl->setVariable("BUTTON_EDIT",$this->lng->txt("add"));
		}
		
		// MESSAGE
		$this->tpl->setVariable("TXT_MSG_CONTENT", $this->lng->txt("message_content"));
		
		// BUTTONS
		$this->tpl->setVariable("TXT_SEND", $this->lng->txt("send"));
		$this->tpl->setVariable("TXT_MSG_SAVE", $this->lng->txt("save_message"));
		
		// MAIL DATA
		$this->tpl->setVariable("RCP_TO", ilUtil::stripSlashes($mailData["rcp_to"]));
		$this->tpl->setVariable("RCP_CC", ilUtil::stripSlashes($mailData["rcp_cc"]));
		$this->tpl->setVariable("RCP_BCC",ilUtil::stripSlashes($mailData["rcp_bcc"]));
		
		$this->tpl->setVariable("M_SUBJECT",ilUtil::stripSlashes($mailData["m_subject"]));
		
		if (is_array($mailData["attachments"]) &&
			count($mailData["attachments"]))
		{
			$this->tpl->setCurrentBlock("files");
			$this->tpl->setCurrentBlock("hidden");
			foreach($mailData["attachments"] as $data)
			{
				$this->tpl->setVariable("ATTACHMENTS",$data);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setVariable("ROWS",count($mailData["attachments"]));
			$this->tpl->setVariable("FILES",implode("\n",$mailData["attachments"]));
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setVariable("M_MESSAGE",ilUtil::stripSlashes($mailData["m_message"]));
		$this->tpl->parseCurrentBlock();

		$this->tpl->show();
	}

}

?>