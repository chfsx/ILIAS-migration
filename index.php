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
* start page of ilias 
*
* @author Peter Gabriel <pgabriel@databay.de>
* @version $Id$
*
* @package ilias
*/

//var_dump($_POST);exit;

require_once "include/inc.check_pear.php";
require_once "include/inc.header.php";

if ($_GET["cmd"] == "login")
{
	$ilias->auth->logout();
	session_destroy();
	header("location: login.php");
	exit();
}

// Specify your start page in ilias.ini.php
$start = $ilias->ini->readVariable("server", "start");

// if no start page was given, ILIAS defaults to the standard login page
if ($start == "")
{
	$start = "login.php";
}

if ($ilias->getSetting("pub_section"))
{
	$start = "nologin.php";
}

// catch reload
if ($_GET["reload"])
{
	$start .= "?reload=true";
}

header("location: ".$start);
exit();
?>
