<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

chdir("../../../..");
include_once("./include/inc.header.php");
include_once("./Services/Captcha/classes/class.ilSecurImage.php");
$si = new ilSecurImage();
$si->getSecureImageObject()->image_width = 160;
$si->getSecureImageObject()->image_height = 60;
$si->showImage();

?>
