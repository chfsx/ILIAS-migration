<?php
/**
 * groups
 *
 * @author Peter Gabriel <pgabriel@databay.de>
 * @package ilias
 * @version $Id$
 */
include_once("./include/ilias_header.inc");
include("./include/inc.main.php");

$tpl = new Template("tpl.group_new.html", false, true);

//$tpl->setVariable("BUTTONS",$tplbtn->get());

$tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("new_group"));

$tpl->setVariable("TXT_GROUPNAME", $lng->txt("groupname"));
$tpl->setVariable("TXT_DESCRIPTION", $lng->txt("description"));
$tpl->setVariable("TXT_ACCESS", $lng->txt("access"));
$tpl->setVariable("TXT_GROUP_SCOPE", $lng->txt("groupscope"));
$tpl->setVariable("TXT_SAVE", $lng->txt("save"));

$tplmain->setVariable("PAGECONTENT",$tpl->get());
$tplmain->show();

?>