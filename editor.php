<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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


/**
* editor view
*
* @author Peter Gabriel <pgabriel@databay.de>
* @version $Id$
*
* @package ilias-core
*/
require_once "./include/inc.header.php";

// limit access only to authors
if (!$rbacsystem->checkAccess("write", ROOT_FOLDER_ID))
{
	$ilias->raiseError("You are not entitled to access this page!",$ilias->error_obj->WARNING);
}

sendInfo("Not available in this release.");

$tpl->addBlockFile("CONTENT", "content", "tpl.editor.html");
$tpl->addBlockFile("BUTTONS", "buttons", "tpl.buttons.html");

/*
$tpl->setCurrentBlock("btn_cell");
$tpl->setVariable("BTN_LINK","???.php");
$tpl->setVariable("BTN_TXT", $lng->txt("test_intern"));

$tpl->parseCurrentBlock();
$tpl->setCurrentBlock("btn_cell");
$tpl->setVariable("BTN_LINK","lo_new.php");
$tpl->setVariable("BTN_TXT", $lng->txt("lo_new"));

$tpl->parseCurrentBlock();
$tpl->setCurrentBlock("btn_cell");
$tpl->setVariable("BTN_LINK","crs_edit.php");
$tpl->setVariable("BTN_TXT", $lng->txt("courses"));

$tpl->parseCurrentBlock();
$tpl->setCurrentBlock("btn_row");
$tpl->parseCurrentBlock();


for ($i = 0; $i < 5; $i++)
{
	$id = $i;
	$tpl->setCurrentBlock("row");
	$tpl->setVariable("ROWCOL", "tblrow".(($i%2)+1));
	$tpl->setVAriable("DATE", date("d.m.Y H:i:s"));
	$tpl->setVAriable("TITLE", $lng->txt("lo").$i);
	$status = "on";
	$switchstatus = "off";
	$tpl->setVariable("LINK_STATUS", "editor.php?cmd=set".$switchstatus."line&amp;id=".$id);
	$tpl->setVariable("LINK_GENERATE", "editor.php?cmd=generate&amp;id=".$id);
	$tpl->setVariable("LINK_ANNOUNCE", "mail.php?cmd=announce&amp;id=".$id);
	$tpl->setVariable("LINK_EDIT", "lo_edit.php?id=".$id);
	$tpl->setVAriable("STATUS", $status);
	$tpl->setVariable("TXT_LO_SET_STATUS", $lng->txt("set_".$switchstatus."line"));
	$tpl->setVariable("TXT_ANNOUNCE", $lng->txt("announce"));
	$tpl->setVariable("TXT_GENERATE", $lng->txt("generate"));
	$tpl->setVariable("TXT_EDIT", $lng->txt("edit"));
	$tpl->setVAriable("TITLE", $lng->txt("lo").$i);
	
	$tpl->parseCurrentBlock();
}

$tpl->setCurrentBlock("content");
$tpl->setVariable("TXT_LO_EDIT", $lng->txt("lo_edit"));

$tpl->setVariable("TXT_TITLE", $lng->txt("title"));
$tpl->setVariable("TXT_ONLINE_VERSION", $lng->txt("online_version"));
$tpl->setVariable("TXT_OFFLINE_VERSION", $lng->txt("offline_version"));
$tpl->setVariable("TXT_PUBLISHED", $lng->txt("published"));
$tpl->parseCurrentBlock();
*/
$tpl->show();
?>