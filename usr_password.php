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
* change user password
* 
* @author	Peter Gabriel <pgabriel@databay.de> 
* @version	$Id$
* @package	ilias
*/
require_once "./include/inc.header.php";

// catch hack attempts
if ($_SESSION["AccountId"] == ANONYMOUS_USER_ID)
{
	$ilias->raiseError($lng->txt("msg_not_available_for_anon"),$ilias->error_obj->MESSAGE);
}

$tpl->addBlockFile("CONTENT", "content", "tpl.usr_password.html");
$tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
//$tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

// set locator 
$tpl->setVariable("TXT_LOCATOR",$lng->txt("locator"));
$tpl->touchBlock("locator_separator");
$tpl->setCurrentBlock("locator_item");
$tpl->setVariable("ITEM", $lng->txt("personal_desktop"));
$tpl->setVariable("LINK_ITEM", "usr_personaldesktop.php");
$tpl->parseCurrentBlock();

$tpl->setCurrentBlock("locator_item");
$tpl->setVariable("ITEM", $lng->txt("chg_password"));
$tpl->setVariable("LINK_ITEM", "usr_password.php");
$tpl->parseCurrentBlock();

// catch feedback message
sendInfo();
// display infopanel if something happened
infoPanel();

// display tabs
include "./include/inc.personaldesktop_buttons.php";

if ($_POST["save_passwd"])
{
	// check old password
	if (md5($_POST["pw_old"]) != $ilias->account->getPasswd())
	{
		$ilias->raiseError($lng->txt("passwd_wrong"),$ilias->error_obj->MESSAGE);
	}
	
	// check new password
	if ($_POST["pw1"] != $_POST["pw2"])
	{
		sendInfo($lng->txt("passwd_not_match"));
	}
			
	// validate password
	if (!ilUtil::is_password($_POST["pw1"]))
	{
		$ilias->raiseError($lng->txt("passwd_invalid"),$ilias->error_obj->MESSAGE);
	}
	
	if ($_POST["pw_old"] != "")
	{
		if ($ilias->account->updatePassword($_POST["pw_old"], $_POST["pw1"], $_POST["pw2"]))
		{
			sendInfo($lng->txt("msg_changes_ok"));
		}
		else
		{
			sendInfo($lng->txt("msg_failed"));
		}
	}
}

$tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("personal_desktop"));
$tpl->setVariable("TXT_CHANGE_PASSWORD", $lng->txt("chg_password"));
$tpl->setVariable("TXT_CURRENT_PW", $lng->txt("current_password"));
$tpl->setVariable("TXT_DESIRED_PW", $lng->txt("desired_password"));
$tpl->setVariable("TXT_RETYPE_PW", $lng->txt("retype_password"));
$tpl->setVariable("TXT_SAVE", $lng->txt("save"));

$tpl->show();
?>
