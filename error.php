<?php
require_once "include/ilias_header.inc";

$tpl->addBlockFile("CONTENT", "content", "tpl.error.html");

$tpl->setCurrentBlock("content");
$tpl->setVariable("BACK",$_SESSION["referer"]);
$tpl->setVariable("ERROR_MESSAGE",stripslashes($_GET["message"]));

$tpl->parseCurrentBlock();

session_unregister("referer");

$tpl->show();
?>