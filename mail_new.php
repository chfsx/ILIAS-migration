<?php
/**
 * mail
 *
 * @author Peter Gabriel <pgabriel@databay.de>
 * @package ilias
 * @version $Id$
 */
include_once("./include/ilias_header.inc");
include("./include/inc.main.php");

$tpl = new Template("tpl.mail_new.html", false, false);

$lng = new Language($ilias->account->data["language"]);

$tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("mail"));

include("./include/inc.mail_buttons.php");

$tpl->setVariable("TXT_RECIPIENT", $lng->txt("recipient"));
$tpl->setVariable("TXT_SEARCH_RECIPIENT", $lng->txt("search_recipient"));
$tpl->setVariable("TXT_CC", $lng->txt("cc"));
$tpl->setVariable("TXT_SEARCH_CC_RECIPIENT", $lng->txt("search_cc_recipient"));
$tpl->setVariable("TXT_BC", $lng->txt("bc"));
$tpl->setVariable("TXT_SEARCH_BC_RECIPIENT", $lng->txt("search_bc_recipient"));
$tpl->setVariable("TXT_SUBJECT", $lng->txt("subject"));
$tpl->setVariable("TXT_TYPE", $lng->txt("type"));
$tpl->setVariable("TXT_NORMAL", $lng->txt("normal"));
$tpl->setVariable("TXT_SYSTEM_MSG", $lng->txt("system_message"));
$tpl->setVariable("TXT_ALSO_AS_EMAIL", $lng->txt("also_as_email"));
$tpl->setVariable("TXT_URL", $lng->txt("url"));
$tpl->setVariable("TXT_URL_DESC", $lng->txt("url_description"));
$tpl->setVariable("TXT_MSG_CONTENT", $lng->txt("message_content"));
$tpl->setVariable("TXT_SEND", $lng->txt("send"));
$tpl->setVariable("TXT_MSG_SAVE", $lng->txt("save_message"));

$tplmain->setVariable("PAGECONTENT",$tpl->get());
$tplmain->show();

?>