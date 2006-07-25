<?php
$BEAUT_PATH = realpath(".")."/syntax_highlight/php";
if (!isset ($BEAUT_PATH)) return;
require_once("$BEAUT_PATH/Beautifier/HFile.php");
  class HFile_xhtml10 extends HFile{
   function HFile_xhtml10(){
     $this->HFile();	
/*************************************/
// Beautifier Highlighting Configuration File 
// XHTML 1.0
/*************************************/
// Flags

$this->nocase            	= "0";
$this->notrim            	= "0";
$this->perl              	= "0";

// Colours

$this->colours        	= array("blue", "purple", "gray", "brown", "blue");
$this->quotecolour       	= "blue";
$this->blockcommentcolour	= "green";
$this->linecommentcolour 	= "green";

// Indent Strings

$this->indent            	= array();
$this->unindent          	= array();

// String characters and delimiters

$this->stringchars       	= array("\"", "'");
$this->delimiters        	= array("~", "@", "$", "%", "^", "&", "*", "(", ")", "+", "=", "|", "\\", "{", "}", ";", "\"", "'", "<", ">", " ", ",", "	", ".");
$this->escchar           	= "";

// Comment settings

$this->linecommenton     	= array("");
$this->blockcommenton    	= array("<!--");
$this->blockcommentoff   	= array("-->");

// Keywords (keyword mapping to colour number)

$this->keywords          	= array(
			"<a>" => "1", 
			"<a" => "1", 
			"</a>" => "1", 
			"<abbr>" => "1", 
			"<abbr" => "1", 
			"</abbr>" => "1", 
			"<acronym>" => "1", 
			"<acronym" => "1", 
			"</acronym>" => "1", 
			"<address>" => "1", 
			"<address" => "1", 
			"</address>" => "1", 
			"<area" => "1", 
			"<b>" => "1", 
			"<b" => "1", 
			"</b>" => "1", 
			"<base" => "1", 
			"<bdo>" => "1", 
			"<bdo" => "1", 
			"</bdo>" => "1", 
			"<big>" => "1", 
			"<big" => "1", 
			"</big>" => "1", 
			"<blockquote>" => "1", 
			"<blockquote" => "1", 
			"</blockquote>" => "1", 
			"<body>" => "1", 
			"<body" => "1", 
			"</body>" => "1", 
			"<br" => "1", 
			"<button>" => "1", 
			"<button" => "1", 
			"</button>" => "1", 
			"<caption>" => "1", 
			"<caption" => "1", 
			"</caption>" => "1", 
			"<cite>" => "1", 
			"<cite" => "1", 
			"</cite>" => "1", 
			"<code>" => "1", 
			"<code" => "1", 
			"</code>" => "1", 
			"<col" => "1", 
			"<colgroup>" => "1", 
			"<colgroup" => "1", 
			"</colgroup>" => "1", 
			"<dd>" => "1", 
			"<dd" => "1", 
			"</dd>" => "1", 
			"<del>" => "1", 
			"<del" => "1", 
			"</del>" => "1", 
			"<dfn>" => "1", 
			"<dfn" => "1", 
			"</dfn>" => "1", 
			"<div>" => "1", 
			"<div" => "1", 
			"</div>" => "1", 
			"<dl>" => "1", 
			"<dl" => "1", 
			"</dl>" => "1", 
			"<dt>" => "1", 
			"<dt" => "1", 
			"</dt>" => "1", 
			"<em>" => "1", 
			"<em" => "1", 
			"</em>" => "1", 
			"<fieldset>" => "1", 
			"<fieldset" => "1", 
			"</fieldset>" => "1", 
			"<form>" => "1", 
			"<form" => "1", 
			"</form>" => "1", 
			"<frame" => "1", 
			"<frameset>" => "1", 
			"<frameset" => "1", 
			"</frameset>" => "1", 
			"<h1>" => "1", 
			"<h1" => "1", 
			"</h1>" => "1", 
			"<h2>" => "1", 
			"<h2" => "1", 
			"</h2>" => "1", 
			"<h3>" => "1", 
			"<h3" => "1", 
			"</h3>" => "1", 
			"<h4>" => "1", 
			"<h4" => "1", 
			"</h4>" => "1", 
			"<h5>" => "1", 
			"<h5" => "1", 
			"</h5>" => "1", 
			"<h6>" => "1", 
			"<h6" => "1", 
			"</h6>" => "1", 
			"<head>" => "1", 
			"<head" => "1", 
			"</head>" => "1", 
			"<hr" => "1", 
			"<html>" => "1", 
			"<html" => "1", 
			"</html>" => "1", 
			"<i>" => "1", 
			"<i" => "1", 
			"</i>" => "1", 
			"<iframe>" => "1", 
			"<iframe" => "1", 
			"</iframe>" => "1", 
			"<img" => "1", 
			"<input" => "1", 
			"<ins>" => "1", 
			"<ins" => "1", 
			"</ins>" => "1", 
			"<kbd>" => "1", 
			"<kbd" => "1", 
			"</kbd>" => "1", 
			"<label>" => "1", 
			"<label" => "1", 
			"</label>" => "1", 
			"<legend>" => "1", 
			"<legend" => "1", 
			"</legend>" => "1", 
			"<li>" => "1", 
			"<li" => "1", 
			"</li>" => "1", 
			"<link" => "1", 
			"<map>" => "1", 
			"<map" => "1", 
			"</map>" => "1", 
			"<meta" => "1", 
			"<noframes>" => "1", 
			"</noframes>" => "1", 
			"<noscript>" => "1", 
			"</noscript>" => "1", 
			"<object>" => "1", 
			"<object" => "1", 
			"</object>" => "1", 
			"<ol>" => "1", 
			"<ol" => "1", 
			"</ol>" => "1", 
			"<optgroup>" => "1", 
			"<optgroup" => "1", 
			"</optgroup>" => "1", 
			"<option>" => "1", 
			"<option" => "1", 
			"</option>" => "1", 
			"<p>" => "1", 
			"<p" => "1", 
			"</p>" => "1", 
			"<param" => "1", 
			"<pre>" => "1", 
			"<pre" => "1", 
			"</pre>" => "1", 
			"<q>" => "1", 
			"<q" => "1", 
			"</q>" => "1", 
			"<samp>" => "1", 
			"<samp" => "1", 
			"</samp>" => "1", 
			"<script>" => "1", 
			"<script" => "1", 
			"</script>" => "1", 
			"<select>" => "1", 
			"<select" => "1", 
			"</select>" => "1", 
			"<small>" => "1", 
			"<small" => "1", 
			"</small>" => "1", 
			"<span>" => "1", 
			"<span" => "1", 
			"</span>" => "1", 
			"<strong>" => "1", 
			"<strong" => "1", 
			"</strong>" => "1", 
			"<style>" => "1", 
			"<style" => "1", 
			"</style>" => "1", 
			"<sub>" => "1", 
			"<sub" => "1", 
			"</sub>" => "1", 
			"<sup>" => "1", 
			"<sup" => "1", 
			"</sup>" => "1", 
			"<table>" => "1", 
			"<table" => "1", 
			"</table>" => "1", 
			"<tbody>" => "1", 
			"<tbody" => "1", 
			"</tbody>" => "1", 
			"<td>" => "1", 
			"<td" => "1", 
			"</td>" => "1", 
			"<textarea>" => "1", 
			"<textarea" => "1", 
			"</textarea>" => "1", 
			"<tfoot>" => "1", 
			"<tfoot" => "1", 
			"</tfoot>" => "1", 
			"<th>" => "1", 
			"<th" => "1", 
			"</th>" => "1", 
			"<thead>" => "1", 
			"<thead" => "1", 
			"</thead>" => "1", 
			"<title>" => "1", 
			"<title" => "1", 
			"</title>" => "1", 
			"<tr>" => "1", 
			"<tr" => "1", 
			"</tr>" => "1", 
			"<tt>" => "1", 
			"<tt" => "1", 
			"</tt>" => "1", 
			"<ul>" => "1", 
			"<ul" => "1", 
			"</ul>" => "1", 
			"<var>" => "1", 
			"<var" => "1", 
			"</var>" => "1", 
			"//" => "1", 
			"/>" => "1", 
			">" => "1", 
			"abbr=" => "2", 
			"accept-charset=" => "2", 
			"accept=" => "2", 
			"accesskey=" => "2", 
			"action=" => "2", 
			"align=" => "2", 
			"alt=" => "2", 
			"archive=" => "2", 
			"axis=" => "2", 
			"border=" => "2", 
			"cellpadding=" => "2", 
			"cellspacing=" => "2", 
			"char=" => "2", 
			"charoff=" => "2", 
			"charset=" => "2", 
			"checked=" => "2", 
			"cite=" => "2", 
			"class=" => "2", 
			"classid=" => "2", 
			"codebase=" => "2", 
			"codetype=" => "2", 
			"cols=" => "2", 
			"colspan=" => "2", 
			"content=" => "2", 
			"coords=" => "2", 
			"data=" => "2", 
			"datetime=" => "2", 
			"declare=" => "2", 
			"defer=" => "2", 
			"dir=" => "2", 
			"disabled=" => "2", 
			"encoding=" => "2", 
			"enctype=" => "2", 
			"for=" => "2", 
			"frame=" => "2", 
			"frameborder=" => "2", 
			"headers=" => "2", 
			"height=" => "2", 
			"href=" => "2", 
			"hreflang=" => "2", 
			"http-equiv=" => "2", 
			"id=" => "2", 
			"ismap=" => "2", 
			"label=" => "2", 
			"lang=" => "2", 
			"longdesc=" => "2", 
			"marginheight=" => "2", 
			"marginwidth=" => "2", 
			"maxlength=" => "2", 
			"media=" => "2", 
			"method=" => "2", 
			"multiple=" => "2", 
			"name=" => "2", 
			"nohref=" => "2", 
			"noresize=" => "2", 
			"onblur=" => "2", 
			"onchange=" => "2", 
			"onclick=" => "2", 
			"ondblclick=" => "2", 
			"onfocus=" => "2", 
			"onkeydown=" => "2", 
			"onkeypress=" => "2", 
			"onkeyup=" => "2", 
			"onload=" => "2", 
			"onmousedown=" => "2", 
			"onmousemove=" => "2", 
			"onmouseout=" => "2", 
			"onmouseover=" => "2", 
			"onmouseup=" => "2", 
			"onreset=" => "2", 
			"onselect=" => "2", 
			"onsubmit=" => "2", 
			"onunload=" => "2", 
			"profile=" => "2", 
			"readonly=" => "2", 
			"rel=" => "2", 
			"rev=" => "2", 
			"rows=" => "2", 
			"rowspan=" => "2", 
			"rules=" => "2", 
			"scheme=" => "2", 
			"scope=" => "2", 
			"scrolling=" => "2", 
			"selected=" => "2", 
			"shape=" => "2", 
			"size=" => "2", 
			"span=" => "2", 
			"src=" => "2", 
			"standby=" => "2", 
			"style=" => "2", 
			"summary=" => "2", 
			"tabindex=" => "2", 
			"target=" => "2", 
			"title=" => "2", 
			"type=" => "2", 
			"usemap=" => "2", 
			"valign=" => "2", 
			"value=" => "2", 
			"valuetype=" => "2", 
			"version=" => "2", 
			"width=" => "2", 
			"xmlns=" => "2", 
			"xmlns:isbn=" => "2", 
			"xml:lang=" => "2", 
			"=" => "2", 
			"<applet" => "3", 
			"</applet>" => "3", 
			"<basefont" => "3", 
			"<center>" => "3", 
			"<center" => "3", 
			"</center>" => "3", 
			"<dir>" => "3", 
			"<dir" => "3", 
			"</dir>" => "3", 
			"<font>" => "3", 
			"<font" => "3", 
			"</font>" => "3", 
			"<isindex" => "3", 
			"<menu>" => "3", 
			"<menu" => "3", 
			"</menu>" => "3", 
			"<s>" => "3", 
			"<s" => "3", 
			"<strike>" => "3", 
			"<strike" => "3", 
			"</strike>" => "3", 
			"<u>" => "3", 
			"<u" => "3", 
			"</u>" => "3", 
			"&;" => "3", 
			"<!DOCTYPE" => "4", 
			"<![CDATA[" => "4", 
			"<?phpxml" => "4", 
			"<?phpxml-stylesheet" => "4", 
			"?>" => "4", 
			"DTD" => "4", 
			"PUBLIC" => "4", 
			"SCHEMA" => "4", 
			"]]>" => "4", 
			"alink=" => "5", 
			"background=" => "5", 
			"bgcolor=" => "5", 
			"clear=" => "5", 
			"code=" => "5", 
			"color=" => "5", 
			"compact=" => "5", 
			"face=" => "5", 
			"hspace=" => "5", 
			"language=" => "5", 
			"link=" => "5", 
			"noshade=" => "5", 
			"nowrap=" => "5", 
			"object=" => "5", 
			"prompt=" => "5", 
			"start=" => "5", 
			"text=" => "5", 
			"vlink=" => "5", 
			"vspace=" => "5");

// Special extensions

// Each category can specify a PHP function that returns an altered
// version of the keyword.
        
        

$this->linkscripts    	= array(
			"1" => "donothing", 
			"2" => "donothing", 
			"3" => "donothing", 
			"4" => "donothing", 
			"5" => "donothing");
}


function donothing($keywordin)
{
	return $keywordin;
}

}?>
