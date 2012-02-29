<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * startpage for ilias
 * this file decides if a frameset is used or not.
 * Frames set definition is done in 'tpl.start.html'
 * 
 * @author Peter Gabriel <pgabriel@databay.de>
 * @package ilias-core
 * @version $Id$
*/
//require_once "./include/inc.header.php";

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_STARTUP);

require_once "./include/inc.header.php";
ilUtil::redirect("index.php");
?>
