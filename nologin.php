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

// Don't change username and password
$_POST["username"] = "anonymous";
$_POST["password"] = "anonymous";

require_once "include/inc.header.php";

$anon = ANONYMOUS_USER_ID;

if (empty($anon))
{
	echo "<p>You enabled the public section feature without declaring a user account as 'anonymous'.";
	echo "<br/>Do the following:<ul>";
	echo "<li>Go to login.php and log in with a regular user account</li>";
	echo "<li>Remember the object_id of a user account designated for anonymous access (you may also add a new user)</li>";
	echo "<li>Username and password MUST BE 'anonymous','anonymous'</li>";
	echo "<li>Open your ilias.ini.php and enter the object_id to the directive 'ANONYMOUS_USER_ID'</li></ul></p>";
	exit();
}

if (!$ilias->getSetting("pub_section"))
{
	$ilias->auth->logout();
	session_destroy();
	header("location: login.php");
	exit();
}

// catch reload
if ($_GET["reload"])
{
	echo "<script language=\"Javascript\">\ntop.location.href = \"./login.php?expired=true\";\n</script>\n";
	exit();
}
// check for auth
if ($ilias->auth->getAuth())
{
	header("location: start.php");
	exit();
}
else
{
	echo "ANONYMOUS user with the object_id ".ANONYMOUS_USER_ID." not found!";
	exit;
}
?>