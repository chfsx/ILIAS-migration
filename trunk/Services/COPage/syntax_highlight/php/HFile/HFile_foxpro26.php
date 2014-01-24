<?php
$BEAUT_PATH = realpath(".")."/Services/COPage/syntax_highlight/php";
if (!isset ($BEAUT_PATH)) return;
require_once("$BEAUT_PATH/Beautifier/HFile.php");
  class HFile_foxpro26 extends HFile{
   function HFile_foxpro26(){
     $this->HFile();	
/*************************************/
// Beautifier Highlighting Configuration File 
// FoxPro 2.6
/*************************************/
// Flags

$this->nocase            	= "1";
$this->notrim            	= "0";
$this->perl              	= "0";

// Colours

$this->colours        	= array("blue", "gray", "purple");
$this->quotecolour       	= "blue";
$this->blockcommentcolour	= "green";
$this->linecommentcolour 	= "green";

// Indent Strings

$this->indent            	= array("IF", "DO CASE", "SCAN", "DO WHIL", "FOR");
$this->unindent          	= array("ELSE", "ENDI", "ENDC", "ENDS", "ENDF", "CASE", "DEFAULT");

// String characters and delimiters

$this->stringchars       	= array();
$this->delimiters        	= array("~", "!", "@", "%", "^", "&", "*", "-", "+", "=", "|", "{", "}", "[", "]", ";", "\"", "'", "<", ">", " ", ",", "	", "?");
$this->escchar           	= "";

// Comment settings

$this->linecommenton     	= array("*");
$this->blockcommenton    	= array("&&");
$this->blockcommentoff   	= array("");

// Keywords (keyword mapping to colour number)

$this->keywords          	= array(
			"#DEFINE" => "1", 
			"#ELIF" => "1", 
			"#ELSE" => "1", 
			"#ENDIF" => "1", 
			"#IF" => "1", 
			"#REGION" => "1", 
			"#UNDEF" => "1", 
			"//" => "3", 
			"/A" => "1", 
			"/C" => "1", 
			"/D" => "1", 
			"/N" => "1", 
			"/NK" => "1", 
			":B" => "1", 
			":E" => "1", 
			":F" => "1", 
			":H" => "1", 
			":P" => "1", 
			":R" => "1", 
			":V" => "1", 
			":W" => "1", 
			"?" => "1", 
			"??" => "1", 
			"@" => "1", 
			"ACCEPT" => "1", 
			"ACTIVATE" => "1", 
			"ADDITIVE" => "1", 
			"AFTER" => "1", 
			"AGAIN" => "1", 
			"ALIAS" => "1", 
			"ALL" => "1", 
			"ALTERNATE" => "1", 
			"AMERICAN" => "1", 
			"ANSI" => "1", 
			"APP" => "1", 
			"APPEND" => "1", 
			"ARRAY" => "1", 
			"ASC" => "1", 
			"ASCENDING" => "1", 
			"AT" => "1", 
			"AUTO" => "1", 
			"AUTOMATIC" => "1", 
			"AUTOSAVE" => "1", 
			"AVERAGE" => "1", 
			"BAR" => "1", 
			"BEFORE" => "1", 
			"BELL" => "1", 
			"BITMAP" => "1", 
			"BLANK" => "1", 
			"BLINK" => "1", 
			"BLOCKSIZE" => "1", 
			"BORDER" => "1", 
			"BOTTOM" => "1", 
			"BOX" => "1", 
			"BRITISH" => "1", 
			"BROWSE" => "1", 
			"BRSTATUS" => "1", 
			"BUILD" => "1", 
			"BY" => "1", 
			"CALCULATE" => "1", 
			"CALL" => "1", 
			"CANCEL" => "1", 
			"CARRY" => "1", 
			"CASE" => "1", 
			"CDX" => "1", 
			"CENTER" => "1", 
			"CENTURY" => "1", 
			"CGA" => "1", 
			"CHANGE" => "1", 
			"CLASS" => "1", 
			"CLEAR" => "1", 
			"CLOCK" => "1", 
			"CLOSE" => "1", 
			"COLOR" => "1", 
			"COLUMN" => "1", 
			"COMMAND" => "1", 
			"COMPACT" => "1", 
			"COMPATIBLE" => "1", 
			"COMPILE" => "1", 
			"CONFIRM" => "1", 
			"CONSOLE" => "1", 
			"CONTINUE" => "1", 
			"COPY" => "1", 
			"COUNT" => "1", 
			"CREATE" => "1", 
			"CURRENCY" => "1", 
			"CURSOR" => "1", 
			"CYCLE" => "1", 
			"DATABASES" => "1", 
			"DATE" => "1", 
			"DB4" => "1", 
			"DBF" => "1", 
			"DEACTIVATE" => "1", 
			"DEBUG" => "1", 
			"DECIMALS" => "1", 
			"DECLARE" => "1", 
			"DEFAULT" => "1", 
			"DEFINE" => "1", 
			"DELETE" => "1", 
			"DELETED" => "1", 
			"DELIMITED" => "1", 
			"DELIMITERS" => "1", 
			"DESC" => "1", 
			"DESCENDING" => "1", 
			"DESKTOP" => "1", 
			"DEVELOPMENT" => "1", 
			"DEVICE" => "1", 
			"DIF" => "1", 
			"DIMENSION" => "1", 
			"DIR" => "1", 
			"DIRECTORY" => "1", 
			"DISABLE" => "1", 
			"DISPLAY" => "1", 
			"DISTINCT" => "1", 
			"DMY" => "1", 
			"DO" => "1", 
			"DOHISTORY" => "1", 
			"DOS" => "1", 
			"DOUBLE" => "1", 
			"ECHO" => "1", 
			"EDIT" => "1", 
			"EGA25" => "1", 
			"EGA43" => "1", 
			"EJECT" => "1", 
			"ELSE" => "1", 
			"ENABLE" => "1", 
			"ENCRYPT" => "1", 
			"ENDCASE" => "1", 
			"ENDDO" => "1", 
			"ENDFOR" => "1", 
			"ENDIF" => "1", 
			"ENDPRINTJOB" => "1", 
			"ENDSCAN" => "1", 
			"ENDTEXT" => "1", 
			"ENVIRONMENT" => "1", 
			"ERASE" => "1", 
			"ERROR" => "1", 
			"ESCAPE" => "1", 
			"EXACT" => "1", 
			"EXCEPT" => "1", 
			"EXCLUSIVE" => "1", 
			"EXE" => "1", 
			"EXIT" => "1", 
			"EXPORT" => "1", 
			"EXTENDED" => "1", 
			"EXTERNAL" => "1", 
			"FIELD" => "1", 
			"FIELDS" => "1", 
			"FILE" => "1", 
			"FILER" => "1", 
			"FILES" => "1", 
			"FILL" => "1", 
			"FILTER" => "1", 
			"FIND" => "1", 
			"FIXED" => "1", 
			"FLOAT" => "1", 
			"FLUSH" => "1", 
			"FONT" => "1", 
			"FOOTER" => "1", 
			"FOR" => "1", 
			"FORM" => "1", 
			"FORMAT" => "1", 
			"FOXPLUS" => "1", 
			"FREEZE" => "1", 
			"FRENCH" => "1", 
			"FROM" => "1", 
			"FULLPATH" => "1", 
			"FUNCTION" => "1", 
			"FW2" => "1", 
			"GATHER" => "1", 
			"GENERAL" => "1", 
			"GERMAN" => "1", 
			"GET" => "1", 
			"GETEXPR" => "1", 
			"GETS" => "1", 
			"GO" => "1", 
			"GOTO" => "1", 
			"GROUP" => "1", 
			"GROW" => "1", 
			"HALFHEIGHT" => "1", 
			"HAVING" => "1", 
			"HEADING" => "1", 
			"HEIGHT" => "1", 
			"HELP" => "1", 
			"HELPFILTER" => "1", 
			"HIDE" => "1", 
			"HIGHLIGHT" => "1", 
			"HOURS" => "1", 
			"ICON" => "1", 
			"IF" => "1", 
			"IMPORT" => "1", 
			"IN" => "1", 
			"INDEX" => "1", 
			"INDEXES" => "1", 
			"INFORMATION" => "1", 
			"INPUT" => "1", 
			"INSERT" => "1", 
			"INTENSITY" => "1", 
			"INTO" => "1", 
			"ISOMETRIC" => "1", 
			"ITALIAN" => "1", 
			"JAPAN" => "1", 
			"JOIN" => "1", 
			"KEY" => "1", 
			"KEYBOARD" => "1", 
			"KEYCOMP" => "1", 
			"LABEL" => "1", 
			"LAST" => "1", 
			"LEDIT" => "1", 
			"LEFT" => "1", 
			"LEVEL" => "1", 
			"LIBRARY" => "1", 
			"LIKE" => "1", 
			"LINE" => "1", 
			"LIST" => "1", 
			"LOAD" => "1", 
			"LOCATE" => "1", 
			"LOCK" => "1", 
			"LOGERRORS" => "1", 
			"LOOP" => "1", 
			"LPARTITION" => "1", 
			"MACKEY" => "1", 
			"MACRO" => "1", 
			"MACROS" => "1", 
			"MARGIN" => "1", 
			"MARK" => "1", 
			"MASTER" => "1", 
			"MDI" => "1", 
			"MDY" => "1", 
			"MEMO" => "1", 
			"MEMORY" => "1", 
			"MEMOWIDTH" => "1", 
			"MEMVAR" => "1", 
			"MENU" => "1", 
			"MENUS" => "1", 
			"MESSAGE" => "1", 
			"MESSAGES" => "1", 
			"MINIMIZE" => "1", 
			"MOD" => "1", 
			"MODAL" => "1", 
			"MODIFY" => "1", 
			"MODULE" => "1", 
			"MONO" => "1", 
			"MOUSE" => "1", 
			"MOVE" => "1", 
			"MOVER" => "1", 
			"MULTILOCKS" => "1", 
			"MULTISELECT" => "1", 
			"NEAR" => "1", 
			"NEXT" => "1", 
			"NOAPPEND" => "1", 
			"NOCLEAR" => "1", 
			"NOCLOSE" => "1", 
			"NOCONSOLE" => "1", 
			"NODEBUG" => "1", 
			"NODELETE" => "1", 
			"NOEDIT" => "1", 
			"NOEJECT" => "1", 
			"NOENVIRONMENT" => "1", 
			"NOFLOAT" => "1", 
			"NOGROW" => "1", 
			"NOLGRID" => "1", 
			"NOLINK" => "1", 
			"NOLOCK" => "1", 
			"NOMARGIN" => "1", 
			"NOMDI" => "1", 
			"NOMENU" => "1", 
			"NOMODIFY" => "1", 
			"NOMOUSE" => "1", 
			"NONE" => "1", 
			"NOOPTIMIZE" => "1", 
			"NOOVERWRITE" => "1", 
			"NOREFRESH" => "1", 
			"NORGRID" => "1", 
			"NORM" => "1", 
			"NORMAL" => "1", 
			"NOSAVE" => "1", 
			"NOSHOW" => "1", 
			"NOTE" => "1", 
			"NOTIFY" => "1", 
			"NOUPDATE" => "1", 
			"NOWAIT" => "1", 
			"NOWINDOW" => "1", 
			"NOZOOM" => "1", 
			"NUMBER" => "1", 
			"OBJECT" => "1", 
			"ODOMETER" => "1", 
			"OF" => "1", 
			"OFF" => "1", 
			"ON" => "1", 
			"ONLY" => "1", 
			"OPEN" => "1", 
			"OPTIMIZE" => "1", 
			"ORDER" => "1", 
			"OTHERWISE" => "1", 
			"OVERWRITE" => "1", 
			"PACK" => "1", 
			"PAD" => "1", 
			"PAGE" => "1", 
			"PALETTE" => "1", 
			"PANEL" => "1", 
			"PARAMETERS" => "1", 
			"PARTITION" => "1", 
			"PATH" => "1", 
			"PATTERN" => "1", 
			"PDOX" => "1", 
			"PDSETUP" => "1", 
			"PEN" => "1", 
			"PICTURE" => "1", 
			"PLAIN" => "1", 
			"PLAY" => "1", 
			"POINT" => "1", 
			"POP" => "1", 
			"POPUP" => "1", 
			"POPUPS" => "1", 
			"POXPLUS" => "1", 
			"PRERERENCE" => "1", 
			"PREVIEW" => "1", 
			"PRINTER" => "1", 
			"PRINTJOB" => "1", 
			"PRIVATE" => "1", 
			"PROCEDURE" => "1", 
			"PRODUCTION" => "1", 
			"PROGRAM" => "1", 
			"PROJECT" => "1", 
			"PROMPT" => "1", 
			"PUBLIC" => "1", 
			"PUSH" => "1", 
			"QUERY" => "1", 
			"QUIT" => "1", 
			"RANDOM" => "1", 
			"RANGE" => "1", 
			"READ" => "1", 
			"READERROR" => "1", 
			"RECALL" => "1", 
			"RECORD" => "1", 
			"REDIT" => "1", 
			"REFERENCE" => "1", 
			"REFRESH" => "1", 
			"REGIONAL" => "1", 
			"REINDEX" => "1", 
			"RELATION" => "1", 
			"RELATIVE" => "1", 
			"RELEASE" => "1", 
			"RENAME" => "1", 
			"REPLACE" => "1", 
			"REPORT" => "1", 
			"REPROCESS" => "1", 
			"RESOURCE" => "1", 
			"REST" => "1", 
			"RESTORE" => "1", 
			"RESUME" => "1", 
			"RETRY" => "1", 
			"RETURN" => "1", 
			"RIGHT" => "1", 
			"ROW" => "1", 
			"RPD" => "1", 
			"RUN" => "1", 
			"SAFETY" => "1", 
			"SAME" => "1", 
			"SAMPLE" => "1", 
			"SAVE" => "1", 
			"SAY" => "1", 
			"SCAN" => "1", 
			"SCATTER" => "1", 
			"SCHEME" => "1", 
			"SCOREBOARD" => "1", 
			"SCREEN" => "1", 
			"SCROLL" => "1", 
			"SDF" => "1", 
			"SECONDS" => "1", 
			"SEEK" => "1", 
			"SELECT" => "1", 
			"SELECTION" => "1", 
			"SEPERATOR" => "1", 
			"SET" => "1", 
			"SHADOW" => "1", 
			"SHADOWS" => "1", 
			"SHARE" => "1", 
			"SHOW" => "1", 
			"SINGLE" => "1", 
			"SIZE" => "1", 
			"SKIP" => "1", 
			"SORT" => "1", 
			"SPACE" => "1", 
			"STANDALONE" => "1", 
			"STATUS" => "1", 
			"STEP" => "1", 
			"STICKY" => "1", 
			"STORE" => "1", 
			"STRETCH" => "1", 
			"STRUCTURE" => "1", 
			"STYLE" => "1", 
			"SUM" => "1", 
			"SUMMARY" => "1", 
			"SUSPEND" => "1", 
			"SYLK" => "1", 
			"SYSMENU" => "1", 
			"SYSTEM" => "1", 
			"TAB" => "1", 
			"TABLE" => "1", 
			"TAG" => "1", 
			"TALK" => "1", 
			"TEXT" => "1", 
			"TEXTMERGE" => "1", 
			"TIME" => "1", 
			"TIMEOUT" => "1", 
			"TITLE" => "1", 
			"TITLES" => "1", 
			"TO" => "1", 
			"TOP" => "1", 
			"TOPIC" => "1", 
			"TOTAL" => "1", 
			"TRBETWEEN" => "1", 
			"TYPE" => "1", 
			"TYPEAHEAD" => "1", 
			"UDFPARMS" => "1", 
			"UNION" => "1", 
			"UNIQUE" => "1", 
			"UNLOCK" => "1", 
			"UPDATE" => "1", 
			"USA" => "1", 
			"USE" => "1", 
			"VALID" => "1", 
			"VALUE" => "1", 
			"VALUES" => "1", 
			"VGA25" => "1", 
			"VGA50" => "1", 
			"VIEW" => "1", 
			"WAIT" => "1", 
			"WHEN" => "1", 
			"WHERE" => "1", 
			"WHILE" => "1", 
			"WIDE" => "1", 
			"WIDTH" => "1", 
			"WINDOW" => "1", 
			"WINDOWS" => "1", 
			"WITH" => "1", 
			"WK1" => "1", 
			"WK3" => "1", 
			"WKS" => "1", 
			"WR1" => "1", 
			"WRAP" => "1", 
			"WRK" => "1", 
			"XLS" => "1", 
			"YMD" => "1", 
			"ZAP" => "1", 
			"ZOOM" => "1", 
			"\\B" => "1", 
			"\\C" => "1", 
			"\\F" => "1", 
			"\\NB" => "1", 
			"\\P" => "1", 
			"\\Q" => "1", 
			"\\S" => "1", 
			"\\\\SPOOLER" => "1", 
			"ABS(" => "2", 
			"ACOPY(" => "2", 
			"ACOS(" => "2", 
			"ADEL(" => "2", 
			"ADIR(" => "2", 
			"AELEMENT(" => "2", 
			"AFIELDS(" => "2", 
			"AFONT(" => "2", 
			"AINS(" => "2", 
			"ALEN(" => "2", 
			"ALIAS(" => "2", 
			"ALLTRIM(" => "2", 
			"ANSITOOEM(" => "2", 
			"ASC(" => "2", 
			"ASCAN(" => "2", 
			"ASIN(" => "2", 
			"ASORT(" => "2", 
			"ASUBSCRIPT(" => "2", 
			"AT(" => "2", 
			"ATAN(" => "2", 
			"ATC(" => "2", 
			"ATCLINE(" => "2", 
			"ATLINE(" => "2", 
			"ATN2(" => "2", 
			"BAR()" => "2", 
			"BETWEEN(" => "2", 
			"BOF(" => "2", 
			"CAPSLOCK(" => "2", 
			"CDOW(" => "2", 
			"CDX(" => "2", 
			"CEILING(" => "2", 
			"CHR(" => "2", 
			"CHRSAW(" => "2", 
			"CHRTRAN(" => "2", 
			"CMONTH(" => "2", 
			"CNTBAR(" => "2", 
			"CNTPAD(" => "2", 
			"COL()" => "2", 
			"COS(" => "2", 
			"CPCONVERT(" => "2", 
			"CPCURRENT(" => "2", 
			"CPDBF(" => "2", 
			"CTOD(" => "2", 
			"CURDIR(" => "2", 
			"DATE()" => "2", 
			"DAY(" => "2", 
			"DBF(" => "2", 
			"DDEAbortTrans(" => "2", 
			"DDEAdvise(" => "2", 
			"DDEEnabled(" => "2", 
			"DDEExecute(" => "2", 
			"DDEInitiate(" => "2", 
			"DDELastError()" => "2", 
			"DDEPoke(" => "2", 
			"DDERequest(" => "2", 
			"DDESetOption(" => "2", 
			"DDESetService(" => "2", 
			"DDESetTopic(" => "2", 
			"DDETerminate(" => "2", 
			"DELETED(" => "2", 
			"DESCENDING(" => "2", 
			"DIFFERENCE(" => "2", 
			"DISKSPACE()" => "2", 
			"DMY(" => "2", 
			"DOW(" => "2", 
			"DTOC(" => "2", 
			"DTOR(" => "2", 
			"DTOS(" => "2", 
			"EMPTY(" => "2", 
			"EOF(" => "2", 
			"ERROR()" => "2", 
			"EVALUATE(" => "2", 
			"EXP(" => "2", 
			"FCHSIZE(" => "2", 
			"FCLOSE(" => "2", 
			"FCOUNT(" => "2", 
			"FCREATE(" => "2", 
			"FEOF(" => "2", 
			"FERROR()" => "2", 
			"FFLUSH(" => "2", 
			"FGETS(" => "2", 
			"FIELD(" => "2", 
			"FILE(" => "2", 
			"FILTER(" => "2", 
			"FKLABEL(" => "2", 
			"FKMAX()" => "2", 
			"FLOCK(" => "2", 
			"FLOOR(" => "2", 
			"FONTMETRIC(" => "2", 
			"FOPEN(" => "2", 
			"FOR(" => "2", 
			"FOUND(" => "2", 
			"FPUTS(" => "2", 
			"FREAD(" => "2", 
			"FSEEK(" => "2", 
			"FSIZE(" => "2", 
			"FULLPATH(" => "2", 
			"FV(" => "2", 
			"FWRITE(" => "2", 
			"GETBAR(" => "2", 
			"GETDIR(" => "2", 
			"GETENV(" => "2", 
			"GETFILE(" => "2", 
			"GETFONT()" => "2", 
			"GETPAD(" => "2", 
			"GOMONTH(" => "2", 
			"HEADER(" => "2", 
			"HOME(" => "2", 
			"IDXCOLLATE(" => "2", 
			"IIF(" => "2", 
			"INKEY(" => "2", 
			"INLIST(" => "2", 
			"INSMODE(" => "2", 
			"INT(" => "2", 
			"ISALPHA(" => "2", 
			"ISBLANK(" => "2", 
			"ISCOLOR()" => "2", 
			"ISDIGIT(" => "2", 
			"ISLOWER(" => "2", 
			"ISREADONLY(" => "2", 
			"ISUPPER(" => "2", 
			"KEY(" => "2", 
			"KEYMATCH(" => "2", 
			"LASTKEY()" => "2", 
			"LEFT(" => "2", 
			"LEN(" => "2", 
			"LIKE(" => "2", 
			"LINENO(" => "2", 
			"LOCFILE(" => "2", 
			"LOCK(" => "2", 
			"LOG(" => "2", 
			"LOG10(" => "2", 
			"LOOKUP(" => "2", 
			"LOWER(" => "2", 
			"LTRIM(" => "2", 
			"LUPDATE(" => "2", 
			"MAX(" => "2", 
			"MCOL(" => "2", 
			"MDOWN()" => "2", 
			"MDX(" => "2", 
			"MDY(" => "2", 
			"MEMLINES(" => "2", 
			"MEMORY()" => "2", 
			"MENU()" => "2", 
			"MESSAGE(" => "2", 
			"MIN(" => "2", 
			"MLINE(" => "2", 
			"MOD(" => "2", 
			"MONTH(" => "2", 
			"MRKBAR(" => "2", 
			"MRKPAD(" => "2", 
			"MROW(" => "2", 
			"MWINDOW(" => "2", 
			"NDX(" => "2", 
			"NORMALIZE(" => "2", 
			"NUMLOCK(" => "2", 
			"OBJNUM(" => "2", 
			"OBJVAR(" => "2", 
			"OCCURS(" => "2", 
			"OEMTOANSI(" => "2", 
			"ON(" => "2", 
			"ORDER(" => "2", 
			"OS()" => "2", 
			"PAD()" => "2", 
			"PADC(" => "2", 
			"PADL(" => "2", 
			"PADR(" => "2", 
			"PARAMETERS()" => "2", 
			"PAYMENT(" => "2", 
			"PCOL()" => "2", 
			"PI()" => "2", 
			"POPUP(" => "2", 
			"PRINTSTATUS()" => "2", 
			"PRMBAR(" => "2", 
			"PRMPAD(" => "2", 
			"PROGRAM(" => "2", 
			"PROMPT()" => "2", 
			"PROPER(" => "2", 
			"PROW()" => "2", 
			"PRTINFO(" => "2", 
			"PUTFILE(" => "2", 
			"PV(" => "2", 
			"RAND(" => "2", 
			"RAT(" => "2", 
			"RATLINE(" => "2", 
			"RDLEVEL()" => "2", 
			"READKEY(" => "2", 
			"RECCOUNT(" => "2", 
			"RECNO(" => "2", 
			"RECSIZE(" => "2", 
			"RELATION(" => "2", 
			"REPLICATE(" => "2", 
			"RGBSCHEME(" => "2", 
			"RIGHT(" => "2", 
			"RLOCK(" => "2", 
			"ROUND(" => "2", 
			"ROW()" => "2", 
			"RTOD(" => "2", 
			"RTRIM(" => "2", 
			"SCHEME(" => "2", 
			"SCOLS()" => "2", 
			"SECONDS()" => "2", 
			"SEEK(" => "2", 
			"SELECT(" => "2", 
			"SET(" => "2", 
			"SIGN(" => "2", 
			"SIN(" => "2", 
			"SKPBAR(" => "2", 
			"SKPPAD(" => "2", 
			"SOUNDEX(" => "2", 
			"SPACE(" => "2", 
			"SQRT(" => "2", 
			"SROWS()" => "2", 
			"STR(" => "2", 
			"STRTRAN(" => "2", 
			"STUFF(" => "2", 
			"SUBSTR(" => "2", 
			"SYS()" => "2", 
			"SYS(0)" => "2", 
			"SYS(1)" => "2", 
			"SYS(10)" => "2", 
			"SYS(100)" => "2", 
			"SYS(1001)" => "2", 
			"SYS(101)" => "2", 
			"SYS(1016)" => "2", 
			"SYS(102)" => "2", 
			"SYS(103)" => "2", 
			"SYS(1037)" => "2", 
			"SYS(11)" => "2", 
			"SYS(12)" => "2", 
			"SYS(13)" => "2", 
			"SYS(14)" => "2", 
			"SYS(15)" => "2", 
			"SYS(16)" => "2", 
			"SYS(17)" => "2", 
			"SYS(18)" => "2", 
			"SYS(2)" => "2", 
			"SYS(20)" => "2", 
			"SYS(2000)" => "2", 
			"SYS(2001)" => "2", 
			"SYS(2002)" => "2", 
			"SYS(2003)" => "2", 
			"SYS(2004)" => "2", 
			"SYS(2005)" => "2", 
			"SYS(2006)" => "2", 
			"SYS(2007)" => "2", 
			"SYS(2008)" => "2", 
			"SYS(2009)" => "2", 
			"SYS(2010)" => "2", 
			"SYS(2011)" => "2", 
			"SYS(2012)" => "2", 
			"SYS(2013)" => "2", 
			"SYS(2014)" => "2", 
			"SYS(2015)" => "2", 
			"SYS(2016)" => "2", 
			"SYS(2017)" => "2", 
			"SYS(2018)" => "2", 
			"SYS(2019)" => "2", 
			"SYS(2020)" => "2", 
			"SYS(2021)" => "2", 
			"SYS(2022)" => "2", 
			"SYS(2023)" => "2", 
			"SYS(21)" => "2", 
			"SYS(22)" => "2", 
			"SYS(23)" => "2", 
			"SYS(24)" => "2", 
			"SYS(3)" => "2", 
			"SYS(5)" => "2", 
			"SYS(6)" => "2", 
			"SYS(7)" => "2", 
			"SYS(9)" => "2", 
			"SYSMETRIC(" => "2", 
			"TAG(" => "2", 
			"TAN(" => "2", 
			"TARGET(" => "2", 
			"TIME(" => "2", 
			"TRANSFORM(" => "2", 
			"TRIM(" => "2", 
			"TXTWIDTH(" => "2", 
			"TYPE(" => "2", 
			"UPDATED()" => "2", 
			"UPPER(" => "2", 
			"USED(" => "2", 
			"VAL(" => "2", 
			"VARREAD()" => "2", 
			"VERSION()" => "2", 
			"WBORDER(" => "2", 
			"WCHILD(" => "2", 
			"WCOLS(" => "2", 
			"WEXIST(" => "2", 
			"WFONT(" => "2", 
			"WLAST(" => "2", 
			"WLCOL(" => "2", 
			"WLROW(" => "2", 
			"WMAXIMUM(" => "2", 
			"WMINIMUM(" => "2", 
			"WONTOP(" => "2", 
			"WOUTPUT(" => "2", 
			"WPARENT(" => "2", 
			"WREAD(" => "2", 
			"WROWS(" => "2", 
			"WTITLE(" => "2", 
			"WVISIBLE(" => "2", 
			"YEAR(" => "2", 
			"!" => "3", 
			"!=" => "3", 
			"#" => "3", 
			"$" => "3", 
			"%" => "3", 
			"*" => "3", 
			"**" => "3", 
			"+" => "3", 
			"-" => "3", 
			".AND." => "3", 
			".F." => "3", 
			".NOT." => "3", 
			".OR." => "3", 
			".T." => "3", 
			"/" => "3", 
			"<" => "3", 
			"<=" => "3", 
			"<>" => "3", 
			"=" => "3", 
			"==" => "3", 
			">" => "3", 
			">=" => "3", 
			"AND" => "3", 
			"NOT" => "3", 
			"OR" => "3", 
			"^" => "3");

// Special extensions

// Each category can specify a PHP function that returns an altered
// version of the keyword.
        
        

$this->linkscripts    	= array(
			"1" => "donothing", 
			"3" => "donothing", 
			"2" => "donothing");
}


function donothing($keywordin)
{
	return $keywordin;
}

}?>
