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
 * startpage for ilias
 * this file decides if a frameset is used or not.
 * Frames set definition is done in 'tpl.start.html'
 * 
 * Frames: 'tpl.start.html' exists in your template directory
 * No frames: Remove 'tpl.start.html' from your template directory
 * 
 * @author Peter Gabriel <pgabriel@databay.de>
 * @package ilias-core
 * @version $Id$
*/
require_once "./include/inc.header.php";

// define here on what page to enter the system the first time
if ($_SESSION["AccountId"] == ANONYMOUS_USER_ID)
{
	$default_start_script = "repository.php";
}
else
{
	$default_start_script = "usr_personaldesktop.php";
}

// look if there is a file tpl.start.html (containing a frameset definition)
$start_template = $ilias->tplPath.$ilias->account->getPref("skin")."/tpl.start.html";

// TO DO: $script is never used via $_GET. Maybe deprecated
$start_script = (!empty($_GET["script"])) ? $_GET["script"] : $default_start_script;

if (file_exists($start_template))
{
	$tpl = new ilTemplate("tpl.start.html", false, false);
	$tpl->setVariable("SCRIPT", $start_script);
	$tpl->show();
}
else
{
	header("location: ".$start_script);
	exit;
}

?>
