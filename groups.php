<?php
/**
* groups
*
* @author Peter Gabriel <pgabriel@databay.de>
* @version $Id$
*
* @package ilias
*/
require_once "./include/ilias_header.inc";

$grp_sys[] = array("name" => "Administrator",
				"desc" => "System Administrators",
				"owner" => "System Administrator [root]"
			);

			
$groups = array();
//go through valid objects and filter out the lessons only
if ($objects = $tree->getChilds(1,"title"))
{
	foreach ($objects as $key => $object)
	{
		if (($object["type"] == "cat" || $object["type"] == "grp") && $rbacsystem->checkAccess('visible',$object["id"],$object["parent"]))
		{
			$groups[$key] = $object;
		}
	}
}

//TODO: maybe move the code above to this method
//$groups = $ilias->account->getGroups();

//foreach ($groups as $row)			
//{
//	vd($row);
//}			

$tplbtn = new Template("tpl.buttons.html", true, true);
$tplbtn->setCurrentBlock("btn_cell");
$tplbtn->setVariable("BTN_LINK","group_new.php");
$tplbtn->setVariable("BTN_TXT", $lng->txt("new_group"));
$tplbtn->parseCurrentBlock();
$tplbtn->setCurrentBlock("btn_row");
$tplbtn->parseCurrentBlock();

$tpl = new Template("tpl.groups.html", false, true);
$tpl->setVariable("BUTTONS",$tplbtn->get());

$tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("groups"));

$i=0;

foreach ($grp_sys as $row)
{
	$i++;
	$tpl->setCurrentBlock("group_row");
	$tpl->setVariable("ROWCOL","tblrow".(($i%2)+1));
	$tpl->setVariable("GRP_NAME", $row["name"]);
	$tpl->setVariable("GRP_DESC", $row["desc"]);
	$tpl->setVariable("GRP_OWNER", $row["owner"]);
	$tpl->parseCurrentBlock();
}

$tpl->setCurrentBlock("group");
$tpl->setVariable("TXT_GRP_TITLE", $lng->txt("system_groups"));
$tpl->setVariable("TXT_NAME", $lng->txt("name"));
$tpl->setVariable("TXT_DESC", $lng->txt("description"));
$tpl->setVariable("TXT_OWNER", $lng->txt("owner"));

$tpl->parseCurrentBlock("group");

$tplmain->setVariable("PAGECONTENT",$tpl->get());
$tplmain->show();
session_unregister("Error_Message");
?>