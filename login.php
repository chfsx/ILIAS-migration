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
* login script for ilias
*
* @author Sascha Hofmann <shofmann@databay.de>
* @author Peter Gabriel <pgabriel@databay.de>
* @version $Id$
*
* @package ilias-layout
*/

// start correct client
// if no client_id is given, default client is loaded (in class.ilias.php)
if (isset($_GET["client_id"]))
{	
	setcookie("ilClientId",$_GET["client_id"]);
	$_COOKIE["ilClientId"] = $_GET["client_id"];
}

require_once "include/inc.check_pear.php";
require_once "include/inc.header.php";

// check correct setup
if (!$ilias->getSetting("setup_ok"))
{
	echo "setup is not completed. Please run setup routine again. (login.php)";
	exit();
}

// check for auth
if ($ilias->auth->getAuth())
{
	if ($ilias->getSetting("chat_active"))
	{
		include_once "./chat/classes/class.ilChatServerCommunicator.php";
		ilChatServerCommunicator::_login();
	}
	
	$return_to = "start.php";
	
	if (!empty($_GET["return_to"]))
	{
		$return_to = urldecode($_GET["return_to"]);
	}

	ilUtil::redirect($return_to);
}

//instantiate login template
$tpl->addBlockFile("CONTENT", "content", "tpl.login.html");

//language handling
if ($_GET["lang"] == "")
{
	$_GET["lang"] = $ilias->ini->readVariable("language","default");
}

//instantiate language
$lng = new ilLanguage($_GET["lang"]);

// catch reload
if ($_GET["reload"])
{
	$tpl->setVariable("RELOAD","<script language=\"Javascript\">\ntop.location.href = \"./login.php?expired=true\";\n</script>\n");
}

$languages = $lng->getInstalledLanguages();

foreach ($languages as $lang_key)
{
	$tpl->setCurrentBlock("languages");
	$tpl->setVariable("LANG_KEY", $lang_key);
	$tpl->setVariable("LANG_NAME", $lng->txt("lang_".$lang_key));
	$tpl->setVariable("BORDER", 0);
	$tpl->setVariable("VSPACE", 0);
	$tpl->parseCurrentBlock();
}

$tpl->setVariable("ILIAS_RELEASE", $ilias->getSetting("ilias_version"));
$tpl->setVariable("TXT_ILIAS_LOGIN", $lng->txt("login_to_ilias"));
$tpl->setVariable("FORMACTION", "login.php?lang=".$_GET["lang"]);
$tpl->setVariable("TXT_USERNAME", $lng->txt("username"));
$tpl->setVariable("TXT_PASSWORD", $lng->txt("password"));
$tpl->setVariable("TXT_SUBMIT", $lng->txt("submit"));
$tpl->setVariable("TXT_CHOOSE_LANGUAGE", $lng->txt("choose_language"));
$tpl->setVariable("LANG_ID", $_GET["lang"]);

if ($_GET["expired"])
{
	$tpl->setVariable(TXT_MSG_LOGIN_FAILED, $lng->txt("err_session_expired"));
}

// TODO: Move this to header.inc since an expired session could not detected in login script 
$status = $ilias->auth->getStatus();

if (!empty($status))
{
	switch ($status)
	{
		case AUTH_EXPIRED:
			$tpl->setVariable(TXT_MSG_LOGIN_FAILED, $lng->txt("err_session_expired"));
			break;
		case AUTH_IDLED:
			$tpl->setVariable(TXT_MSG_LOGIN_FAILED, $lng->txt("err_idled"));
			break;
		case AUTH_WRONG_LOGIN:
		default:
			$tpl->setVariable(TXT_MSG_LOGIN_FAILED, $lng->txt("err_wrong_login"));
			break;
	}
}

$tpl->setCurrentBlock("content");
$tpl->setVariable("PHP_SELF", $_SERVER['PHP_SELF']);
$tpl->setVariable("USERNAME", $_GET["username"]);
$tpl->setVariable("USER_AGREEMENT", "[ ".$lng->txt("usr_agreement")." ]");
if ($ilias->getSetting("enable_registration"))
{
	$tpl->setVariable("REGISTER", "[ ".$lng->txt("registration")." ]");
}


$tpl->parseCurrentBlock();

$tpl->show(false);
?>
