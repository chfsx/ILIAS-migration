<?php
global $BEAUT_PATH;
if (!isset ($BEAUT_PATH)) return;
require_once("$BEAUT_PATH/Beautifier/HFile.php");
  class HFile_aspjscript extends HFile{
   function HFile_aspjscript(){
     $this->HFile();	
/*************************************/
// Beautifier Highlighting Configuration File 
// ASP JScript
/*************************************/
// Flags

$this->nocase            	= "1";
$this->notrim            	= "0";
$this->perl              	= "0";

// Colours

$this->colours        	= array("blue", "purple", "brown", "gray", "brown", "blue", "purple", "gray");
$this->quotecolour       	= "blue";
$this->blockcommentcolour	= "green";
$this->linecommentcolour 	= "green";

// Indent Strings

$this->indent            	= array("{");
$this->unindent          	= array("}");

// String characters and delimiters

$this->stringchars       	= array();
$this->delimiters        	= array("~", "!", "@", "$", "%", "^", "&", "*", "(", ")", "+", "=", "|", "\\", "{", "}", "[", "]", ":", ";", "\"", "'", "<", ">", " ", ",", "	", ".", "?");
$this->escchar           	= "";

// Comment settings

$this->linecommenton     	= array("//");
$this->blockcommenton    	= array("<!--");
$this->blockcommentoff   	= array("-->");

// Keywords (keyword mapping to colour number)

$this->keywords          	= array(
			"<A" => "1", 
			"</A>" => "1", 
			"<ABBR>" => "1", 
			"<ABBR" => "1", 
			"</ABBR>" => "1", 
			"<ABOVE>" => "1", 
			"<ACRONYM>" => "1", 
			"<ACRONYM" => "1", 
			"</ACRONYM>" => "1", 
			"<ADDRESS>" => "1", 
			"<ADDRESS" => "1", 
			"</ADDRESS>" => "1", 
			"<APPLET" => "1", 
			"</APPLET>" => "1", 
			"<ARRAY>" => "1", 
			"<AREA" => "1", 
			"</AREA" => "1", 
			"<B>" => "1", 
			"<B" => "1", 
			"</B>" => "1", 
			"<BASE" => "1", 
			"<BASEFONT" => "1", 
			"<BDO>" => "1", 
			"<BDO" => "1", 
			"</BDO>" => "1", 
			"<BGSOUND" => "1", 
			"<BIG>" => "1", 
			"<BIG" => "1", 
			"</BIG>" => "1", 
			"<BLINK>" => "1", 
			"</BLINK>" => "1", 
			"<BLOCKQUOTE>" => "1", 
			"<BLOCKQUOTE" => "1", 
			"</BLOCKQUOTE>" => "1", 
			"<BODY" => "1", 
			"<BODY>" => "1", 
			"</BODY>" => "1", 
			"<BOX>" => "1", 
			"<BR" => "1", 
			"<BR>" => "1", 
			"<BLINK" => "1", 
			"<BUTTON>" => "1", 
			"</BUTTON>" => "1", 
			"<CAPTION>" => "1", 
			"<CAPTION" => "1", 
			"</CAPTION>" => "1", 
			"<CENTER>" => "1", 
			"<CENTER" => "1", 
			"</CENTER>" => "1", 
			"<CITE>" => "1", 
			"<CITE" => "1", 
			"</CITE>" => "1", 
			"<CODE>" => "1", 
			"<CODE" => "1", 
			"</CODE>" => "1", 
			"<COL>" => "1", 
			"</COL>" => "1", 
			"<COLGROUP>" => "1", 
			"</COLGROUP>" => "1", 
			"<COMMENT>" => "1", 
			"</COMMENT>" => "1", 
			"<DD>" => "1", 
			"<DD" => "1", 
			"</DD>" => "1", 
			"<DEL>" => "1", 
			"<DEL" => "1", 
			"</DEL>" => "1", 
			"<DFN>" => "1", 
			"<DFN" => "1", 
			"</DFN>" => "1", 
			"<DIR>" => "1", 
			"<DIR" => "1", 
			"</DIR>" => "1", 
			"<DIV>" => "1", 
			"<DIV" => "1", 
			"</DIV>" => "1", 
			"<DL>" => "1", 
			"<DL" => "1", 
			"</DL>" => "1", 
			"<DT>" => "1", 
			"<DT" => "1", 
			"</DT>" => "1", 
			"<EM>" => "1", 
			"<EM" => "1", 
			"</EM>" => "1", 
			"<EMBED" => "1", 
			"<FIELDSET>" => "1", 
			"<FIELDSET" => "1", 
			"</FIELDSET>" => "1", 
			"<FIG>" => "1", 
			"<FONT" => "1", 
			"</FONT>" => "1", 
			"<FORM>" => "1", 
			"<FORM" => "1", 
			"</FORM>" => "1", 
			"<FRAME" => "1", 
			"<FRAMESET" => "1", 
			"</FRAMESET>" => "1", 
			"<H1>" => "1", 
			"<H1" => "1", 
			"</H1>" => "1", 
			"<H2>" => "1", 
			"<H2" => "1", 
			"</H2>" => "1", 
			"<H3>" => "1", 
			"<H3" => "1", 
			"</H3>" => "1", 
			"<H4>" => "1", 
			"<H4" => "1", 
			"</H4>" => "1", 
			"<H5>" => "1", 
			"<H5" => "1", 
			"</H5>" => "1", 
			"<H6>" => "1", 
			"<H6" => "1", 
			"</H6>" => "1", 
			"<HEAD>" => "1", 
			"<HEAD" => "1", 
			"</HEAD>" => "1", 
			"<HR>" => "1", 
			"<HR" => "1", 
			"<HTML>" => "1", 
			"<HTML" => "1", 
			"</HTML>" => "1", 
			"<I>" => "1", 
			"<I" => "1", 
			"</I>" => "1", 
			"<IFRAME>" => "1", 
			"</IFRAME>" => "1", 
			"<ILAYER>" => "1", 
			"</ILAYER>" => "1", 
			"<IMG" => "1", 
			"<INPUT>" => "1", 
			"<INPUT" => "1", 
			"<INS>" => "1", 
			"<INS" => "1", 
			"</INS>" => "1", 
			"<ISINDEX>" => "1", 
			"<ISINDEX" => "1", 
			"<KBD>" => "1", 
			"<KBD" => "1", 
			"</KBD>" => "1", 
			"<LABEL>" => "1", 
			"<LABEL" => "1", 
			"</LABEL>" => "1", 
			"<LAYER>" => "1", 
			"<LEGEND>" => "1", 
			"<LEGEND" => "1", 
			"</LEGEND>" => "1", 
			"<LI>" => "1", 
			"<LI" => "1", 
			"</LI>" => "1", 
			"<LINK" => "1", 
			"<LISTING>" => "1", 
			"</LISTING>" => "1", 
			"<MAP" => "1", 
			"</MAP>" => "1", 
			"<MARQUEE" => "1", 
			"</MARQUEE>" => "1", 
			"<MENU>" => "1", 
			"<MENU" => "1", 
			"</MENU>" => "1", 
			"<META" => "1", 
			"<MULTICOL>" => "1", 
			"</MULTICOL>" => "1", 
			"<NEXTID" => "1", 
			"<NOBR>" => "1", 
			"</NOBR>" => "1", 
			"<NOFRAMES>" => "1", 
			"</NOFRAMES>" => "1", 
			"<NOLAYER>" => "1", 
			"</NOLAYER>" => "1", 
			"<NOTE>" => "1", 
			"</NOTE>" => "1", 
			"<NOSCRIPT>" => "1", 
			"</NOSCRIPT>" => "1", 
			"<OBJECT>" => "1", 
			"<OBJECT" => "1", 
			"<OL>" => "1", 
			"<OL" => "1", 
			"</OL>" => "1", 
			"<OPTION>" => "1", 
			"<OPTION" => "1", 
			"<OPTGROUP>" => "1", 
			"<OPTGROUP" => "1", 
			"</OPTGROUP>" => "1", 
			"<P" => "1", 
			"<P>" => "1", 
			"</P>" => "1", 
			"<PARAM" => "1", 
			"<PRE>" => "1", 
			"<PRE" => "1", 
			"</PRE>" => "1", 
			"<Q>" => "1", 
			"<Q" => "1", 
			"</Q>" => "1", 
			"<QUOTE>" => "1", 
			"<RANGE>" => "1", 
			"<ROOT>" => "1", 
			"<S>" => "1", 
			"<S" => "1", 
			"</S>" => "1", 
			"<SAMP>" => "1", 
			"<SAMP" => "1", 
			"</SAMP>" => "1", 
			"<SCRIPT" => "1", 
			"<SCRIPT>" => "1", 
			"</SCRIPT>" => "1", 
			"<SELECT" => "1", 
			"</SELECT>" => "1", 
			"<SMALL>" => "1", 
			"<SMALL" => "1", 
			"</SMALL>" => "1", 
			"<SOUND" => "1", 
			"<SPACER>" => "1", 
			"<SPAN>" => "1", 
			"<SPAN" => "1", 
			"</SPAN>" => "1", 
			"<SQRT>" => "1", 
			"<STRIKE>" => "1", 
			"<STRIKE" => "1", 
			"</STRIKE>" => "1", 
			"<STRONG>" => "1", 
			"<STRONG" => "1", 
			"</STRONG>" => "1", 
			"<STYLE>" => "1", 
			"<STYLE" => "1", 
			"</STYLE>" => "1", 
			"<SUB>" => "1", 
			"<SUB" => "1", 
			"</SUB>" => "1", 
			"<SUP>" => "1", 
			"<SUP" => "1", 
			"</SUP>" => "1", 
			"<TABLE>" => "1", 
			"<TABLE" => "1", 
			"</TABLE>" => "1", 
			"<TBODY>" => "1", 
			"<TBODY" => "1", 
			"</TBODY>" => "1", 
			"<TD" => "1", 
			"<TD>" => "1", 
			"</TD>" => "1", 
			"<TEXT>" => "1", 
			"<TEXTAREA" => "1", 
			"<TEXTAREA>" => "1", 
			"</TEXTAREA>" => "1", 
			"<TFOOT>" => "1", 
			"<TFOOT" => "1", 
			"</TFOOT>" => "1", 
			"<TH" => "1", 
			"<TH>" => "1", 
			"</TH>" => "1", 
			"<THEAD>" => "1", 
			"<THEAD" => "1", 
			"</THEAD>" => "1", 
			"<TITLE>" => "1", 
			"</TITLE>" => "1", 
			"<TR" => "1", 
			"<TR>" => "1", 
			"</TR>" => "1", 
			"<TT>" => "1", 
			"</TT>" => "1", 
			"<TT" => "1", 
			"<U>" => "1", 
			"<U" => "1", 
			"</U>" => "1", 
			"<UL>" => "1", 
			"<UL" => "1", 
			"</UL>" => "1", 
			"<VAR>" => "1", 
			"</VAR>" => "1", 
			"<VAR" => "1", 
			"<WBR>" => "1", 
			"<XMP>" => "1", 
			"</XMP>" => "1", 
			"ABBR=" => "2", 
			"ACCEPT-CHARSET=" => "2", 
			"ACCEPT=" => "2", 
			"ACCESSKEY=" => "2", 
			"ACTION=" => "2", 
			"ALIGN=" => "2", 
			"ALINK=" => "2", 
			"ALT=" => "2", 
			"ARCHIVE=" => "2", 
			"AXIS=" => "2", 
			"BACKGROUND=" => "2", 
			"BEHAVIOR" => "2", 
			"BELOW" => "2", 
			"BGCOLOR=" => "2", 
			"BORDER=" => "2", 
			"CELLPADDING=" => "2", 
			"CELLSPACING=" => "2", 
			"CHAR=" => "2", 
			"CHAROFF=" => "2", 
			"CHARSET=" => "2", 
			"CHECKED" => "2", 
			"CITE=" => "2", 
			"CLASS=" => "2", 
			"CLASSID=" => "2", 
			"CLEAR=" => "2", 
			"CODE=" => "2", 
			"CODEBASE=" => "2", 
			"CODETYPE=" => "2", 
			"COLOR=" => "2", 
			"COLS=" => "2", 
			"COLSPAN=" => "2", 
			"COMPACT" => "2", 
			"CONTENT=" => "2", 
			"COORDS=" => "2", 
			"DATA=" => "2", 
			"DATETIME=" => "2", 
			"DECLARE" => "2", 
			"DEFER" => "2", 
			"DIR=" => "2", 
			"DISABLED" => "2", 
			"ENCTYPE=" => "2", 
			"FACE=" => "2", 
			"FOR=" => "2", 
			"FRAME=" => "2", 
			"FRAMEBORDER=" => "2", 
			"FRAMESPACING=" => "2", 
			"HEADERS=" => "2", 
			"HEIGHT=" => "2", 
			"HIDDEN=" => "2", 
			"HREF=" => "2", 
			"HREFLANG=" => "2", 
			"HSPACE=" => "2", 
			"HTTP-EQUIV=" => "2", 
			"ID=" => "2", 
			"ISMAP=" => "2", 
			"LABEL=" => "2", 
			"LANG=" => "2", 
			"LANGUAGE=" => "2", 
			"LINK=" => "2", 
			"LOOP=" => "2", 
			"LONGDESC=" => "2", 
			"MAILTO=" => "2", 
			"MARGINHEIGHT=" => "2", 
			"MARGINWIDTH=" => "2", 
			"MAXLENGTH=" => "2", 
			"MEDIA=" => "2", 
			"METHOD=" => "2", 
			"MULTIPLE" => "2", 
			"NAME=" => "2", 
			"NOHREF" => "2", 
			"NORESIZE" => "2", 
			"NOSHADE" => "2", 
			"OBJECT=" => "2", 
			"ONBLUR=" => "2", 
			"ONCHANGE=" => "2", 
			"ONFOCUS=" => "2", 
			"ONKEYDOWN=" => "2", 
			"ONKEYPRESS=" => "2", 
			"ONKEYUP=" => "2", 
			"ONLOAD=" => "2", 
			"ONRESET=" => "2", 
			"ONSELECT=" => "2", 
			"ONSUBMIT=" => "2", 
			"ONUNLOAD=" => "2", 
			"ONCLICK=" => "2", 
			"ONDBLCLICK=" => "2", 
			"ONMOUSEDOWN=" => "2", 
			"ONMOUSEMOVE=" => "2", 
			"ONMOUSEOUT=" => "2", 
			"ONMOUSEOVER=" => "2", 
			"ONMOUSEUP=" => "2", 
			"PROFILE=" => "2", 
			"PROMPT=" => "2", 
			"READONLY" => "2", 
			"REL=" => "2", 
			"REV=" => "2", 
			"ROWS=" => "2", 
			"ROWSPAN=" => "2", 
			"RULES=" => "2", 
			"SCHEME=" => "2", 
			"SCOPE=" => "2", 
			"SCROLLING=" => "2", 
			"SELECTED" => "2", 
			"SHAPE=" => "2", 
			"SIZE=" => "2", 
			"SPAN=" => "2", 
			"SRC=" => "2", 
			"STANDBY=" => "2", 
			"START=" => "2", 
			"STYLE=" => "2", 
			"SUMMARY=" => "2", 
			"TABINDEX=" => "2", 
			"TARGET=" => "2", 
			"TEXT=" => "2", 
			"TITLE=" => "2", 
			"TOPMARGIN=" => "2", 
			"TYPE=" => "2", 
			"URL=" => "2", 
			"USEMAP=" => "2", 
			"VALIGN=" => "2", 
			"VALUE=" => "2", 
			"VALUETYPE=" => "2", 
			"VERSION=" => "2", 
			"VLINK=" => "2", 
			"VSPACE=" => "2", 
			"WIDTH=" => "2", 
			"=" => "8", 
			"abstract" => "3", 
			"boolean" => "3", 
			"break" => "3", 
			"byte" => "3", 
			"case" => "3", 
			"catch" => "3", 
			"char" => "3", 
			"class" => "3", 
			"const" => "3", 
			"continue" => "3", 
			"default" => "3", 
			"do" => "3", 
			"double" => "3", 
			"else" => "3", 
			"extends" => "3", 
			"false" => "3", 
			"final" => "3", 
			"finally" => "3", 
			"float" => "3", 
			"for" => "3", 
			"function" => "3", 
			"goto" => "3", 
			"if" => "3", 
			"implements" => "3", 
			"import" => "3", 
			"in" => "3", 
			"instanceof" => "3", 
			"int" => "3", 
			"interface" => "3", 
			"long" => "3", 
			"native" => "3", 
			"new" => "3", 
			"null" => "3", 
			"package" => "3", 
			"private" => "3", 
			"protected" => "3", 
			"public" => "3", 
			"return" => "3", 
			"short" => "3", 
			"static" => "3", 
			"super" => "3", 
			"switch" => "3", 
			"synchronized" => "3", 
			"this" => "3", 
			"throw" => "3", 
			"throws" => "3", 
			"transient" => "3", 
			"true" => "3", 
			"try" => "3", 
			"var" => "3", 
			"void" => "3", 
			"while" => "3", 
			"with" => "3", 
			"escape" => "4", 
			"eval" => "4", 
			"isNaN" => "4", 
			"parseFloat" => "4", 
			"parseInt" => "4", 
			"unescape" => "4", 
			"abs" => "5", 
			"acos" => "5", 
			"alert" => "5", 
			"anchor" => "5", 
			"asin" => "5", 
			"atan" => "5", 
			"atan2" => "5", 
			"back" => "5", 
			"big" => "5", 
			"blink" => "5", 
			"blur" => "5", 
			"bold" => "5", 
			"captureEvents" => "5", 
			"ceil" => "5", 
			"charAt" => "5", 
			"clearInterval" => "5", 
			"clearTimeout" => "5", 
			"click" => "5", 
			"close" => "5", 
			"confirm" => "5", 
			"cos" => "5", 
			"exp" => "5", 
			"find" => "5", 
			"fixed" => "5", 
			"floor" => "5", 
			"focus" => "5", 
			"fontcolor" => "5", 
			"fontsize" => "5", 
			"forward" => "5", 
			"getDate" => "5", 
			"getDay" => "5", 
			"getHours" => "5", 
			"getMinutes" => "5", 
			"getMonth" => "5", 
			"getSeconds" => "5", 
			"getSelection" => "5", 
			"getTime" => "5", 
			"getTimezoneOffset" => "5", 
			"getYear" => "5", 
			"go" => "5", 
			"handleEvent" => "5", 
			"home" => "5", 
			"indexof" => "5", 
			"italics" => "5", 
			"javaEnabled" => "5", 
			"join" => "5", 
			"lastinexOf" => "5", 
			"link" => "5", 
			"log" => "5", 
			"match" => "5", 
			"max" => "5", 
			"min" => "5", 
			"moveBy" => "5", 
			"moveTo" => "5", 
			"open" => "5", 
			"parse" => "5", 
			"pow" => "5", 
			"print" => "5", 
			"pompt" => "5", 
			"random" => "5", 
			"refresh" => "5", 
			"releaseEvents" => "5", 
			"reload" => "5", 
			"replace" => "5", 
			"reset" => "5", 
			"resizeBy" => "5", 
			"resizeTo" => "5", 
			"reverse" => "5", 
			"round" => "5", 
			"routeEvent" => "5", 
			"scroll" => "5", 
			"scrollBy" => "5", 
			"scrollTo" => "5", 
			"select" => "5", 
			"setDate" => "5", 
			"setHours" => "5", 
			"setInterval" => "5", 
			"setMinutes" => "5", 
			"setMonth" => "5", 
			"setSeconds" => "5", 
			"setTime" => "5", 
			"setTimeout" => "5", 
			"setYear" => "5", 
			"sin" => "5", 
			"small" => "5", 
			"sort" => "5", 
			"split" => "5", 
			"sqrt" => "5", 
			"stop" => "5", 
			"strikesub" => "5", 
			"submit" => "5", 
			"substring" => "5", 
			"sup" => "5", 
			"tan" => "5", 
			"toGMTString" => "5", 
			"toLocaleString" => "5", 
			"toLowerCase" => "5", 
			"toString" => "5", 
			"toUpperCase" => "5", 
			"UTC" => "5", 
			"write" => "5", 
			"writeln" => "5", 
			"Application" => "6", 
			"Dictionary" => "6", 
			"Err" => "6", 
			"FileSystemObject" => "6", 
			"Matches" => "6", 
			"ObjectContext" => "6", 
			"RegExp" => "6", 
			"Request" => "6", 
			"Response" => "6", 
			"Scripting" => "6", 
			"Server" => "6", 
			"Session" => "6", 
			"Abandon" => "7", 
			"AddHeader" => "7", 
			"AppendToLog" => "7", 
			"BinaryRead" => "7", 
			"BinaryWrite" => "7", 
			"Buffer" => "7", 
			"CacheControl" => "7", 
			"Charset" => "7", 
			"Clear" => "7", 
			"ClientCertificate" => "7", 
			"CodePage" => "7", 
			"Contents" => "7", 
			"ContentType" => "7", 
			"Cookies" => "7", 
			"CreateObject" => "7", 
			"Description" => "7", 
			"Execute" => "7", 
			"Expires" => "7", 
			"ExpiresAbsolute" => "7", 
			"Fields" => "7", 
			"FirstIndex" => "7", 
			"Flush" => "7", 
			"Form" => "7", 
			"Global" => "7", 
			"HelpContext" => "7", 
			"HelpFile" => "7", 
			"HTMLEncode" => "7", 
			"IgnoreCase" => "7", 
			"Initialize" => "7", 
			"IsClientConnected" => "7", 
			"Item" => "7", 
			"LCID" => "7", 
			"Length" => "7", 
			"Lock" => "7", 
			"MapPath" => "7", 
			"Number" => "7", 
			"OnEnd" => "7", 
			"OnStart" => "7", 
			"OnTransactionCommit" => "7", 
			"OnTransactionAbort" => "7", 
			"Pattern" => "7", 
			"Pics" => "7", 
			"QueryString" => "7", 
			"Raise" => "7", 
			"Redirect" => "7", 
			"ScriptTimeout" => "7", 
			"ServerVariables" => "7", 
			"SessionID" => "7", 
			"SetComplete" => "7", 
			"SetAbort" => "7", 
			"Source" => "7", 
			"StaticObjects" => "7", 
			"Status" => "7", 
			"Terminate" => "7", 
			"Test" => "7", 
			"Timeout" => "7", 
			"TotalBytes" => "7", 
			"Unlock" => "7", 
			"URLEncode" => "7", 
			"Value" => "7", 
			"&&" => "8", 
			"==" => "8", 
			"!=" => "8", 
			"<" => "8", 
			">" => "8", 
			"!" => "8", 
			"~" => "8", 
			"+" => "8", 
			"*" => "8", 
			"^" => "8", 
			"-" => "8", 
			"&" => "8");

// Special extensions

// Each category can specify a PHP function that returns an altered
// version of the keyword.
        
        

$this->linkscripts    	= array(
			"1" => "donothing", 
			"2" => "donothing", 
			"8" => "donothing", 
			"3" => "donothing", 
			"4" => "donothing", 
			"5" => "donothing", 
			"6" => "donothing", 
			"7" => "donothing");
}


function donothing($keywordin)
{
	return $keywordin;
}

}?>
