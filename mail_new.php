<?php
/**
 * mail
 *
 * @author Stefan Meyer <smeyer@databay.de>
 * @version $Id$
 *
 * @package ilias
 */
require_once "./include/inc.header.php";
require_once "./include/inc.mail.php";
require_once "classes/class.ilObjUser.php";
require_once "classes/class.ilFormatMail.php";
require_once "classes/class.ilMailbox.php";
require_once "classes/class.ilFileDataMail.php";

$lng->loadLanguageModule("mail");

$_POST["attachments"] = $_POST["attachments"] ? $_POST["attachments"] : array();

$umail = new ilFormatMail($_SESSION["AccountId"]);
$mfile = new ilFileDataMail($_SESSION["AccountId"]);
$allow_smtp = $ilias->getSetting("mail_allow_smtp");

$tpl->addBlockFile("CONTENT", "content", "tpl.mail_new.html");
$tpl->setVariable("TXT_COMPOSE",$lng->txt("mail_compose"));
infoPanel();

// LOCATOR
setLocator($_GET["mobj_id"],$_SESSION["AccountId"],"");

if(isset($_POST["cmd"]["send"]))
{
	$f_message = $umail->formatLinebreakMessage($_POST["m_message"]);
	if($error_message = $umail->sendMail($_POST["rcp_to"],$_POST["rcp_cc"],
										 $_POST["rcp_bcc"],$_POST["m_subject"],$f_message,
										 $_POST["attachments"],$_POST["m_type"],$_POST["m_email"]))
	{
		sendInfo($error_message);
	}
	else
	{
		sendInfo($lng->txt("mail_message_send"));
	}
}
if(isset($_POST["cmd"]["save_message"]))
{
	$mbox = new ilMailbox($_SESSION["AccountId"]);
	$drafts_id = $mbox->getDraftsFolder();

	if($umail->sendInternalMail($drafts_id,$_SESSION["AccountId"],$_POST["attachments"],$_POST["rcp_to"],$_POST["rcp_cc"],
								$_POST["rcp_bcc"],'read',$_POST["m_type"],$_POST["m_email"],
								$_POST["m_subject"],$_POST["m_message"],$_SESSION["AccountId"]))
	{
		sendInfo($lng->txt("mail_saved"));
	}
	else
	{
		sendInfo($lng->txt("mail_send_error"));
	}
}
if(isset($_POST["cmd"]["rcp_to"]))
{
	$_SESSION["mail_search"] = 'to';
	sendInfo($lng->txt("mail_insert_query"));
}
if(isset($_POST["cmd"]["rcp_cc"]))
{
	$_SESSION["mail_search"] = 'cc';
	sendInfo($lng->txt("mail_insert_query"));
}
if(isset($_POST["cmd"]["rcp_bc"]))
{
	$_SESSION["mail_search"] = 'bc';
	sendInfo($lng->txt("mail_insert_query"));
}
if(isset($_POST["cmd"]["edit"]))
{
	$umail->savePostData($_SESSION["AccountId"],$_POST["attachments"],
						 $_POST["rcp_to"],$_POST["rcp_cc"],$_POST["rcp_bcc"],$_POST["m_type"],
						 $_POST["m_email"],$_POST["m_subject"],$_POST["m_message"]);
	header("location: mail_attachment.php?mobj_id=$_GET[mobj_id]");
}
if(isset($_POST["cmd"]["search_system"]))
{
	$umail->savePostData($_SESSION["AccountId"],$_POST["attachments"],$_POST["rcp_to"],
						 $_POST["rcp_cc"],$_POST["rcp_bcc"],$_POST["m_type"],
						 $_POST["m_email"],$_POST["m_subject"],$_POST["m_message"]);
	header("location: mail_search.php?mobj_id=$_GET[mobj_id]&search=".urlencode($_POST["search"])."&type=system");
	exit();
}
if(isset($_POST["cmd"]["search_addr"]))
{
	$umail->savePostData($_SESSION["AccountId"],$_POST["attachments"],$_POST["rcp_to"],
						 $_POST["rcp_cc"],$_POST["rcp_bcc"],$_POST["m_type"],
						 $_POST["m_email"],$_POST["m_subject"],$_POST["m_message"]);
	header("location: mail_search.php?mobj_id=$_GET[mobj_id]&search=".urlencode($_POST["search"])."&type=addr");
	exit();
}
if(isset($_POST["cmd"]["search_cancel"]) or isset($_POST["cmd"]["cancel"]))
{
	unset($_SESSION["mail_search"]);
}

// BUTTONS
include "./include/inc.mail_buttons.php";


// FORWARD, REPLY, SEARCH

switch($_GET["type"])
{
	case 'reply':
		$mail_data = $umail->getMail($_GET["mail_id"]);
		$mail_data["m_subject"] = $umail->formatReplySubject();
		$mail_data["m_message"] = $umail->formatReplyMessage(); 
		$mail_data["m_message"] = $umail->appendSignature();
		// NO ATTACHMENTS FOR REPLIES
		$mail_data["attachments"] = array();
		$mail_data["rcp_to"] = $umail->formatReplyRecipient();
		break;

	case 'search_res':
		$mail_data = $umail->getSavedData();
		if($_POST["search_name"])
		{
			$mail_data = $umail->appendSearchResult($_POST["search_name"],$_SESSION["mail_search"]);
		}
		unset($_SESSION["mail_search"]);
		break;

	case 'attach':
		$mail_data = $umail->getSavedData();
		break;

	case 'draft':
		$mail_data = $umail->getMail($_GET["mail_id"]);
		break;

	case 'forward':
		$mail_data = $umail->getMail($_GET["mail_id"]);
		$mail_data["rcp_to"] = $mail_data["rcp_cc"] = $mail_data["rcp_bcc"] = '';
		$mail_data["m_subject"] = $umail->formatForwardSubject();
		$mail_data["m_message"] = $umail->appendSignature();
		if(count($mail_data["attachments"]))
		{
			if($error = $mfile->adoptAttachments($mail_data["attachments"],$_GET["mail_id"]))
			{
				sendInfo($error);
			}
		}
		break;

	case 'new':
		$mail_data["m_message"] = $umail->appendSignature();
		break;

	default:
		// GET DATA FROM POST
		$mail_data = $_POST;
		break;
}
$tpl->setVariable("ACTION", "mail_new.php?mobj_id=$_GET[mobj_id]");

// SEARCH BLOCK
if(isset($_POST["cmd"]["rcp_to"]) or
   isset($_POST["cmd"]["rcp_cc"]) or
   isset($_POST["cmd"]["rcp_bc"]))
#   isset($_POST["cmd"][""] == $lng->txt("search"))
{
	$tpl->setCurrentBlock("search");
	$tpl->setVariable("BUTTON_SEARCH_SYSTEM",$lng->txt("search_system"));
	$tpl->setVariable("BUTTON_SEARCH_ADDRESSBOOK",$lng->txt("search_addressbook"));
	$tpl->setVariable("BUTTON_CANCEL",$lng->txt("cancel"));
}

// RECIPIENT
$tpl->setVariable("TXT_RECIPIENT", $lng->txt("to"));
$tpl->setVariable("TXT_SEARCH_RECIPIENT", $lng->txt("search_recipient"));
$tpl->setVariable("BUTTON_TO",$lng->txt("mail_to_search"));
// CC
$tpl->setVariable("TXT_CC", $lng->txt("cc"));
$tpl->setVariable("TXT_SEARCH_CC_RECIPIENT", $lng->txt("search_cc_recipient"));
$tpl->setVariable("BUTTON_CC",$lng->txt("mail_cc_search"));
// BCC
$tpl->setVariable("TXT_BC", $lng->txt("bc"));
$tpl->setVariable("TXT_SEARCH_BC_RECIPIENT", $lng->txt("search_bc_recipient"));
$tpl->setVariable("BUTTON_BC",$lng->txt("mail_bc_search"));
// SUBJECT
$tpl->setVariable("TXT_SUBJECT", $lng->txt("subject"));
// TYPE
$tpl->setVariable("TXT_TYPE", $lng->txt("type"));
$tpl->setVariable("TXT_NORMAL", $lng->txt("normal"));
$tpl->setVariable("TXT_SYSTEM", $lng->txt("system_message"));

// ONLY IF SMTP MAIL ARE ALLOWED
if($allow_smtp == 'y')
{
	$tpl->setCurrentBlock("allow_smtp");
	$tpl->setVariable("TXT_EMAIL", $lng->txt("email"));
	if($mail_data["m_type"] == 'email')
	{
		$tpl->setVariable("CHECKED_EMAIL",'CHECKED');
	}
	$tpl->parseCurrentBlock();
	
	$tpl->setCurrentBlock("smtp");
	$tpl->setVariable("TXT_ALSO_AS_EMAIL", $lng->txt("also_as_email"));
	$tpl->setVariable("CHECKED_ALSO_EMAIL",$mail_data["m_email"] ? 'CHECKED' : ''); 
	$tpl->parseCurrentBlock();
}

// ATTACHMENT
$tpl->setVariable("TXT_ATTACHMENT",$lng->txt("mail_attachments"));
$tpl->setVariable("BUTTON_EDIT",$lng->txt("edit"));

// MESSAGE
$tpl->setVariable("TXT_MSG_CONTENT", $lng->txt("message_content"));

// BUTTONS
$tpl->setVariable("TXT_SEND", $lng->txt("send"));
$tpl->setVariable("TXT_MSG_SAVE", $lng->txt("save_message"));

// MAIL DATA
$tpl->setVariable("RCP_TO", $mail_data["rcp_to"]);
$tpl->setVariable("RCP_CC", $mail_data["rcp_cc"]);
$tpl->setVariable("RCP_BCC", $mail_data["rcp_bcc"]);
$tpl->setVariable("M_SUBJECT",$mail_data["m_subject"]);

if(count($mail_data["attachments"]))
{
	$tpl->setCurrentBlock("files");
	$tpl->setCurrentBlock("hidden");
	foreach($mail_data["attachments"] as $data)
	{
		$tpl->setVariable("ATTACHMENTS",$data);
		$tpl->parseCurrentBlock();
	}
	$tpl->setVariable("ROWS",count($mail_data["attachments"]));
	$tpl->setVariable("FILES",implode("\n",$mail_data["attachments"]));
	$tpl->parseCurrentBlock();
}
if($mail_data["m_type"] == 'normal' or !$mail_data["m_type"])
{
	$tpl->setVariable("CHECKED_NORMAL",'CHECKED');
}
if($mail_data["m_type"] == 'system')
{
	$tpl->setVariable("CHECKED_SYSTEM",'CHECKED');
}
$tpl->setVariable("M_MESSAGE",$mail_data["m_message"]);
$tpl->parseCurrentBlock();

$tpl->show();
?>
