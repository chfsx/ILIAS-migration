<?php
require_once "include/ilias_header.inc";
require_once "classes/class.Explorer.php";

$tplContent = new Template("explorer.html",true,true);

$explorer = new Explorer("content.php");

$explorer->setExpand($_GET["expand"]);

$explorer->setOutput(0);

$output = $explorer->getOutput();

$tplContent->setVariable("EXPLORER",$output);
$tplContent->setVariable("ACTION", "lo_menu.php?expand=1");

$tplmain->setVariable("PAGECONTENT", $tplContent->get());
$tplmain->show();
?>