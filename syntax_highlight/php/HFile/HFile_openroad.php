<?php
global $BEAUT_PATH;
if (!isset ($BEAUT_PATH)) return;
require_once("$BEAUT_PATH/Beautifier/HFile.php");
  class HFile_openroad extends HFile{
   function HFile_openroad(){
     $this->HFile();	
/*************************************/
// Beautifier Highlighting Configuration File 
// OpenROAD
/*************************************/
// Flags

$this->nocase            	= "1";
$this->notrim            	= "0";
$this->perl              	= "0";

// Colours

$this->colours        	= array("blue", "purple", "gray", "brown", "blue", "purple", "gray", "brown");
$this->quotecolour       	= "blue";
$this->blockcommentcolour	= "green";
$this->linecommentcolour 	= "green";

// Indent Strings

$this->indent            	= array();
$this->unindent          	= array();

// String characters and delimiters

$this->stringchars       	= array("'");
$this->delimiters        	= array("~", "!", "@", "%", "^", "&", "*", "(", ")", "+", "=", "|", "\\", "{", "}", "[", "]", ":", ";", "\"", "'", "<", ">", " ", ",", "	", ".", "?");
$this->escchar           	= "";

// Comment settings

$this->linecommenton     	= array("");
$this->blockcommenton    	= array("/*");
$this->blockcommentoff   	= array("*/");

// Keywords (keyword mapping to colour number)

$this->keywords          	= array(
			"abort" => "1", 
			"all" => "1", 
			"alter" => "1", 
			"any" => "1", 
			"as" => "1", 
			"asc" => "1", 
			"at" => "1", 
			"avg" => "1", 
			"begin" => "1", 
			"byref" => "1", 
			"case" => "1", 
			"callFrame" => "1", 
			"ChildClick" => "1", 
			"ChildClickPoint" => "1", 
			"ChildDetails" => "1", 
			"ChildDoubleClick" => "1", 
			"ChildDragBox" => "1", 
			"ChildDragSegment" => "1", 
			"ChildEntry" => "1", 
			"ChildExit" => "1", 
			"ChildMoved" => "1", 
			"ChildProperties" => "1", 
			"ChildResized" => "1", 
			"ChildScroll" => "1", 
			"ChildSelect" => "1", 
			"ChildSetValue" => "1", 
			"ChildUnselect" => "1", 
			"ChildValidate" => "1", 
			"Click" => "1", 
			"ClickPoint" => "1", 
			"committed" => "1", 
			"continue" => "1", 
			"copy" => "1", 
			"current" => "1", 
			"declare" => "1", 
			"default" => "1", 
			"deleterow" => "1", 
			"desc" => "1", 
			"Details" => "1", 
			"direct" => "1", 
			"DoubleClick" => "1", 
			"DragBox" => "1", 
			"DragSegment" => "1", 
			"end" => "1", 
			"endcase" => "1", 
			"enddeclare" => "1", 
			"Entry" => "1", 
			"escape" => "1", 
			"exit" => "1", 
			"extclassevent" => "1", 
			"field" => "1", 
			"FrameActivate" => "1", 
			"FrameDeactivate" => "1", 
			"gotoframe" => "1", 
			"group" => "1", 
			"initialize" => "1", 
			"inquire_ingres" => "1", 
			"inquire_sql" => "1", 
			"installation" => "1", 
			"integrity" => "1", 
			"key" => "1", 
			"link" => "1", 
			"lockmode" => "1", 
			"message" => "1", 
			"method" => "1", 
			"mode" => "1", 
			"modify" => "1", 
			"Moved" => "1", 
			"next" => "1", 
			"nextcase" => "1", 
			"noecho" => "1", 
			"of" => "1", 
			"on" => "1", 
			"only" => "1", 
			"openframe" => "1", 
			"procedure" => "1", 
			"prompt" => "1", 
			"Properties" => "1", 
			"qualification" => "1", 
			"raise" => "1", 
			"read" => "1", 
			"register" => "1", 
			"relocate" => "1", 
			"remove" => "1", 
			"repeat" => "1", 
			"repeatable" => "1", 
			"repeated" => "1", 
			"Resized" => "1", 
			"resume" => "1", 
			"return" => "1", 
			"revoke" => "1", 
			"role" => "1", 
			"row" => "1", 
			"rule" => "1", 
			"save" => "1", 
			"savepoint" => "1", 
			"Scroll" => "1", 
			"SelectionChanged" => "1", 
			"serializable" => "1", 
			"setvalue" => "1", 
			"sleep" => "1", 
			"some" => "1", 
			"system" => "1", 
			"uncommitted" => "1", 
			"unique" => "1", 
			"UnSelect" => "1", 
			"until" => "1", 
			"UserEvent" => "1", 
			"Validate" => "1", 
			"WindowClose" => "1", 
			"WindowMoved" => "1", 
			"WindowResized" => "1", 
			"WindowVisible" => "1", 
			"work" => "1", 
			"write" => "1", 
			"between" => "2", 
			"by" => "2", 
			"commit" => "2", 
			"count" => "2", 
			"delete" => "2", 
			"distinct" => "2", 
			"drop" => "2", 
			"execute" => "2", 
			"exists" => "2", 
			"fetch" => "2", 
			"from" => "2", 
			"grant" => "2", 
			"having" => "2", 
			"immediate" => "2", 
			"in" => "2", 
			"index" => "2", 
			"insert" => "2", 
			"into" => "2", 
			"join" => "2", 
			"like" => "2", 
			"max" => "2", 
			"min" => "2", 
			"order" => "2", 
			"permit" => "2", 
			"rollback" => "2", 
			"select" => "2", 
			"set" => "2", 
			"sum" => "2", 
			"table" => "2", 
			"transaction" => "2", 
			"union" => "2", 
			"update" => "2", 
			"values" => "2", 
			"view" => "2", 
			"where" => "2", 
			"with" => "2", 
			"AND" => "3", 
			"callProc" => "3", 
			"ELSE" => "3", 
			"ELSEIF" => "3", 
			"ENDIF" => "3", 
			"ENDFOR" => "3", 
			"ENDLOOP" => "3", 
			"ENDWHILE" => "3", 
			"DO" => "3", 
			"DOWNTO" => "3", 
			"IF" => "3", 
			"IS" => "3", 
			"FALSE" => "3", 
			"FOR" => "3", 
			"NOT" => "3", 
			"NULL" => "3", 
			"OR" => "3", 
			"THEN" => "3", 
			"TO" => "3", 
			"TRUE" => "3", 
			"WHILE" => "3", 
			"$_ComponentName" => "4", 
			"$_CurFld" => "4", 
			"$_CurFldRow" => "4", 
			"$_DefaultReturnValue" => "4", 
			"$ApplicationName" => "4", 
			"$MB_OKOnly" => "4", 
			"$MB_CancelOnly" => "4", 
			"$MB_OKCancel" => "4", 
			"$MB_AbortRetryIgnore" => "4", 
			"$MB_YesNoCancel" => "4", 
			"$MB_YesNo" => "4", 
			"$MB_RetryCancel" => "4", 
			"$MB_YesYesToAllNoCancel" => "4", 
			"$MB_AbortOnly" => "4", 
			"$MB_RetryOnly" => "4", 
			"$MB_IgnoreOnly" => "4", 
			"$MB_YesOnly" => "4", 
			"$MB_NoOnly" => "4", 
			"$MB_YesToAllOnly" => "4", 
			"$MB_Critical" => "4", 
			"$MB_Question" => "4", 
			"$MB_Exclamation" => "4", 
			"$MB_Information" => "4", 
			"$MB_Reply" => "4", 
			"$MB_DefaultButton1" => "4", 
			"$MB_DefaultButton2" => "4", 
			"$MB_DefaultButton3" => "4", 
			"$MB_DefaultButton4" => "4", 
			"$MB_OK" => "4", 
			"$MB_Cancel" => "4", 
			"$MB_Abort" => "4", 
			"$MB_Retry" => "4", 
			"$MB_Ignore" => "4", 
			"$MB_Yes" => "4", 
			"$MB_No" => "4", 
			"$MB_YesToAll" => "4", 
			"$ShortRemark" => "4", 
			"BF_BMP" => "4", 
			"BF_GIF" => "4", 
			"BF_SUNRASTER" => "4", 
			"BF_TIFF" => "4", 
			"BF_XBM" => "4", 
			"BF_WINDOWCURSOR" => "4", 
			"BF_WINDOWICON" => "4", 
			"CC_BACKGROUND" => "4", 
			"CC_BLACK" => "4", 
			"CC_BLUE" => "4", 
			"CC_BROWN" => "4", 
			"CC_CYAN" => "4", 
			"CC_FOREGROUND" => "4", 
			"CC_GRAY" => "4", 
			"CC_GREEN" => "4", 
			"CC_LIGHT_BLUE" => "4", 
			"CC_LIGHT_BROWN" => "4", 
			"CC_LIGHT_CYAN" => "4", 
			"CC_LIGHT_GRAY" => "4", 
			"CC_LIGHT_GREEN" => "4", 
			"CC_LIGHT_ORANGE" => "4", 
			"CC_LIGHT_PINK" => "4", 
			"CC_LIGHT_PURPLE" => "4", 
			"CC_LIGHT_RED" => "4", 
			"CC_LIGHT_YELLOW" => "4", 
			"CC_MAGENTA" => "4", 
			"CC_ORANGE" => "4", 
			"CC_PALE_BLUE" => "4", 
			"CC_PALE_BROWN" => "4", 
			"CC_PALE_CYAN" => "4", 
			"CC_PALE_GRAY" => "4", 
			"CC_PALE_GREEN" => "4", 
			"CC_PALE_ORANGE" => "4", 
			"CC_PALE_PINK" => "4", 
			"CC_PALE_PURPLE" => "4", 
			"CC_PALE_RED" => "4", 
			"CC_PALE_YELLOW" => "4", 
			"CC_PINK" => "4", 
			"CC_PURPLE" => "4", 
			"CC_RED" => "4", 
			"CC_WHITE" => "4", 
			"CC_YELLOW" => "4", 
			"CC_DEFAULT_1" => "4", 
			"CC_DEFAULT_2" => "4", 
			"CC_DEFAULT_3" => "4", 
			"CC_DEFAULT_4" => "4", 
			"CC_DEFAULT_5" => "4", 
			"CC_DEFAULT_6" => "4", 
			"CC_DEFAULT_7" => "4", 
			"CC_DEFAULT_8" => "4", 
			"CC_DEFAULT_9" => "4", 
			"CC_DEFAULT_10" => "4", 
			"CC_DEFAULT_11" => "4", 
			"CC_DEFAULT_12" => "4", 
			"CC_DEFAULT_13" => "4", 
			"CC_DEFAULT_14" => "4", 
			"CC_DEFAULT_15" => "4", 
			"CC_DEFAULT_16" => "4", 
			"CC_DEFAULT_17" => "4", 
			"CC_DEFAULT_18" => "4", 
			"CC_DEFAULT_19" => "4", 
			"CC_DEFAULT_20" => "4", 
			"CC_DEFAULT_21" => "4", 
			"CC_DEFAULT_22" => "4", 
			"CC_DEFAULT_23" => "4", 
			"CC_DEFAULT_24" => "4", 
			"CC_DEFAULT_25" => "4", 
			"CC_DEFAULT_26" => "4", 
			"CC_DEFAULT_27" => "4", 
			"CC_DEFAULT_28" => "4", 
			"CC_DEFAULT_29" => "4", 
			"CC_DEFAULT_30" => "4", 
			"CC_SYS_ACTIVEBORDER" => "4", 
			"CC_SYS_ACTIVECAPTION" => "4", 
			"CC_SYS_APPWORKSPACE" => "4", 
			"CC_SYS_BACKGROUND" => "4", 
			"CC_SYS_BTNFACE" => "4", 
			"CC_SYS_BTNSHADOW" => "4", 
			"CC_SYS_BTNTEXT" => "4", 
			"CC_SYS_CAPTIONTEXT" => "4", 
			"CC_SYS_GRAYTEXT" => "4", 
			"CC_SYS_HIGHLIGHT" => "4", 
			"CC_SYS_HIGHLIGHTTEXT" => "4", 
			"CC_SYS_INACTIVEBORDER" => "4", 
			"CC_SYS_INACTIVECAPTION" => "4", 
			"CC_SYS_INACTIVECAPTIONTEXT" => "4", 
			"CC_SYS_MENU" => "4", 
			"CC_SYS_MENUTEXT" => "4", 
			"CC_SYS_SCROLLBAR" => "4", 
			"CC_SYS_SHADOW" => "4", 
			"CC_SYS_WINDOW" => "4", 
			"CC_SYS_WINDOWFRAME" => "4", 
			"CC_SYS_WINDOWTEXT" => "4", 
			"CL_INVALIDVALUE" => "4", 
			"CP_NONE" => "4", 
			"CP_BOTH" => "4", 
			"CP_ROWS" => "4", 
			"CP_COLUMNS" => "4", 
			"CS_CLOSED" => "4", 
			"CS_CURRENT" => "4", 
			"CS_OPEN" => "4", 
			"CS_OPEN_CACHED" => "4", 
			"CS_NOCURRENT" => "4", 
			"CS_NO_MORE_ROWS" => "4", 
			"DC_BW" => "4", 
			"DC_COLOR" => "4", 
			"DP_CLIP_IMAGE" => "4", 
			"DP_AUTOSIZE_FIELD" => "4", 
			"DP_SCALE_IMAGE_HW" => "4", 
			"DP_SCALE_IMAGE_H" => "4", 
			"DP_SCALE_IMAGE_W" => "4", 
			"DS_CONNECTED" => "4", 
			"DS_DISABLED" => "4", 
			"DS_DISCONNECTED" => "4", 
			"DS_INGRES_DBMS" => "4", 
			"DS_INGRESDSK_DBMS" => "4", 
			"DS_NO_DBMS" => "4", 
			"DS_ORACLE_DBMS" => "4", 
			"DS_SQLSERVER_DBMS" => "4", 
			"DV_NULL" => "4", 
			"DV_STRING" => "4", 
			"DV_SYSTEM" => "4", 
			"EH_NEXT_HANDLER" => "4", 
			"EH_RESUME" => "4", 
			"EH_RETRY" => "4", 
			"ER_OK" => "4", 
			"ER_FAIL" => "4", 
			"ER_USER1" => "4", 
			"ER_USER2" => "4", 
			"ER_USER3" => "4", 
			"ER_USER4" => "4", 
			"ER_USER5" => "4", 
			"ER_USER6" => "4", 
			"ER_USER7" => "4", 
			"ER_USER8" => "4", 
			"ER_USER9" => "4", 
			"ER_USER10" => "4", 
			"ER_OUTOFRANGE" => "4", 
			"ER_ROWNOTFOUND" => "4", 
			"ER_NAMEEXISTS" => "4", 
			"EP_NONE" => "4", 
			"EP_INTERACTIVE" => "4", 
			"EP_OUTPUT" => "4", 
			"FA_BOTTOMCENTER" => "4", 
			"FA_BOTTOMLEFT" => "4", 
			"FA_BOTTOMRIGHT" => "4", 
			"FA_CENTER" => "4", 
			"FA_CENTERLEFT" => "4", 
			"FA_CENTERRIGHT" => "4", 
			"FA_DEFAULT" => "4", 
			"FA_NONE" => "4", 
			"FA_TOPCENTER" => "4", 
			"FA_TOPLEFT" => "4", 
			"FA_TOPRIGHT" => "4", 
			"FB_CHANGEABLE" => "4", 
			"FB_CLICKPOINT" => "4", 
			"FB_DIMMED" => "4", 
			"FB_DRAGBOX" => "4", 
			"FB_DRAGSEGMENT" => "4", 
			"FB_FLEXIBLE" => "4", 
			"FB_INVISIBLE" => "4", 
			"FB_LANDABLE" => "4", 
			"FB_MARKABLE" => "4", 
			"FB_MOVEABLE" => "4", 
			"FB_RESIZEABLE" => "4", 
			"FB_VISIBLE" => "4", 
			"FC_LOWER" => "4", 
			"FC_NONE" => "4", 
			"FC_UPPER" => "4", 
			"FM_ADD" => "4", 
			"FM_DELETE" => "4", 
			"FM_QUERY" => "4", 
			"FM_READ" => "4", 
			"FM_UPDATE" => "4", 
			"FM_USER1" => "4", 
			"FM_USER2" => "4", 
			"FM_USER3" => "4", 
			"FO_DEFAULT" => "4", 
			"FO_VERTICAL" => "4", 
			"FO_HORIZONTAL" => "4", 
			"FP_BITMAP" => "4", 
			"FP_CLEAR" => "4", 
			"FP_CROSSHATCH" => "4", 
			"FP_DARKSHADE" => "4", 
			"FP_DEFAULT" => "4", 
			"FP_HORIZONTAL" => "4", 
			"FP_LIGHTSHADE" => "4", 
			"FP_SHADE" => "4", 
			"FP_SOLID" => "4", 
			"FP_VERTICAL" => "4", 
			"FT_NOSETVALUE" => "4", 
			"FT_SETVALUE" => "4", 
			"FT_TABTO" => "4", 
			"FT_TAKEFOCUS" => "4", 
			"GF_BOTTOM" => "4", 
			"GF_DEFAULT" => "4", 
			"GF_LEFT" => "4", 
			"GF_RIGHT" => "4", 
			"GF_TOP" => "4", 
			"GW_NO_DBMS" => "4", 
			"GW_INFORMIX_DBMS" => "4", 
			"HC_DOUBLEQUOTE" => "4", 
			"HC_QUOTE" => "4", 
			"HC_SPACE" => "4", 
			"HC_FORMFEED" => "4", 
			"HC_NEWLINE" => "4", 
			"HC_TAB" => "4", 
			"HV_CONTENTS" => "4", 
			"HV_CONTEXT" => "4", 
			"HV_HELPONHELP" => "4", 
			"HV_KEY" => "4", 
			"HV_QUIT" => "4", 
			"LS_DASH" => "4", 
			"LS_DASHDOT" => "4", 
			"LS_DASHDOTDOT" => "4", 
			"LS_DEFAULT" => "4", 
			"LS_DOT" => "4", 
			"LS_SOLID" => "4", 
			"LS_3D" => "4", 
			"LW_DEFAULT" => "4", 
			"LW_MAXIMUM" => "4", 
			"LW_MIDDLE" => "4", 
			"LW_MINIMUM" => "4", 
			"LW_NOLINE" => "4", 
			"LW_THICK" => "4", 
			"LW_THIN" => "4", 
			"LW_VERYTHICK" => "4", 
			"LW_VERYTHIN" => "4", 
			"LW_EXTRATHIN" => "4", 
			"MB_DISABLED" => "4", 
			"MB_ENABLED" => "4", 
			"MB_INVISIBLE" => "4", 
			"MT_ERROR" => "4", 
			"MT_NONE" => "4", 
			"MT_INFO" => "4", 
			"MT_WARNING" => "4", 
			"OP_APPEND" => "4", 
			"OP_NONE" => "4", 
			"OS_DEFAULT" => "4", 
			"OS_SHADOW" => "4", 
			"OS_SOLID" => "4", 
			"OS_3D" => "4", 
			"PU_CANCEL" => "4", 
			"PU_OK" => "4", 
			"QS_ACTIVE" => "4", 
			"QS_INACTIVE" => "4", 
			"QS_SETCOL" => "4", 
			"QY_ARRAY" => "4", 
			"QY_CACHE" => "4", 
			"QY_CURSOR" => "4", 
			"QY_DIRECT" => "4", 
			"RC_CHILDSELECTED" => "4", 
			"RC_DOWN" => "4", 
			"RC_END" => "4", 
			"RC_FIELDFREED" => "4", 
			"RC_FIELDORPHANED" => "4", 
			"RC_GROUPSELECT" => "4", 
			"RC_HOME" => "4", 
			"RC_LEFT" => "4", 
			"RC_MODECHANGED" => "4", 
			"RC_MOUSECLICK" => "4", 
			"RC_MOUSEDRAG" => "4", 
			"RC_NEXT" => "4", 
			"RC_NOTAPPLICABLE" => "4", 
			"RC_PAGEDOWN" => "4", 
			"RC_PAGEUP" => "4", 
			"RC_PARENTSELECTED" => "4", 
			"RC_PREVIOUS" => "4", 
			"RC_PROGRAM" => "4", 
			"RC_RESUME" => "4", 
			"RC_RETURN" => "4", 
			"RC_RIGHT" => "4", 
			"RC_ROWDELETED" => "4", 
			"RC_ROWINSERTED" => "4", 
			"RC_ROWSALLDELETED" => "4", 
			"RC_SELECT" => "4", 
			"RC_TFSCROLL" => "4", 
			"RC_TOGGLESELECT" => "4", 
			"RC_UP" => "4", 
			"RS_CHANGED" => "4", 
			"RS_DELETED" => "4", 
			"RS_NEW" => "4", 
			"RS_UNCHANGED" => "4", 
			"RS_UNDEFINED" => "4", 
			"SK_CLOSE" => "4", 
			"SK_COPY" => "4", 
			"SK_CUT" => "4", 
			"SK_DELETE" => "4", 
			"SK_DETAILS" => "4", 
			"SK_DUPLICATE" => "4", 
			"SK_FIND" => "4", 
			"SK_GO" => "4", 
			"SK_HELP" => "4", 
			"SK_NEXT" => "4", 
			"SK_NONE" => "4", 
			"SK_PASTE" => "4", 
			"SK_PROPS" => "4", 
			"SK_QUIT" => "4", 
			"SK_REDO" => "4", 
			"SK_SAVE" => "4", 
			"SK_TFDELETEALLROWS" => "4", 
			"SK_TFDELETEROW" => "4", 
			"SK_TFFIND" => "4", 
			"SK_TFINSERTROW" => "4", 
			"SK_UNDO" => "4", 
			"SP_APPSTARTING" => "4", 
			"SP_ARROW" => "4", 
			"SP_CROSS" => "4", 
			"SP_IBEAM" => "4", 
			"SP_ICON" => "4", 
			"SP_NO" => "4", 
			"SP_SIZE" => "4", 
			"SP_SIZENESW" => "4", 
			"SP_SIZENS" => "4", 
			"SP_SIZENWSE" => "4", 
			"SP_SIZEWE" => "4", 
			"SP_UPARROW" => "4", 
			"SP_WAIT" => "4", 
			"SY_NT" => "4", 
			"SY_UNIX" => "4", 
			"SY_VMS" => "4", 
			"SY_OS2" => "4", 
			"SY_MSDOS" => "4", 
			"SY_WIN95" => "4", 
			"TF_COURIER" => "4", 
			"TF_HELVETICA" => "4", 
			"TF_LUCIDA" => "4", 
			"TF_MENUDEFAULT" => "4", 
			"TF_NEWCENTURY" => "4", 
			"TF_SYSTEM" => "4", 
			"TF_TIMESROMAN" => "4", 
			"UE_DATAERROR" => "4", 
			"UE_EXITED" => "4", 
			"UE_NOTACTIVE" => "4", 
			"UE_PURGED" => "4", 
			"UE_RESUMED" => "4", 
			"UE_UNKNOWN" => "4", 
			"WI_MOTIF" => "4", 
			"WI_MSWIN32" => "4", 
			"WI_MSWINDOWS" => "4", 
			"WI_NONE" => "4", 
			"WI_PM" => "4", 
			"WP_FLOATING" => "4", 
			"WP_INTERACTIVE" => "4", 
			"WP_PARENTCENTERED" => "4", 
			"WP_PARENTRELATIVE" => "4", 
			"WP_SCREENCENTERED" => "4", 
			"WP_SCREENRELATIVE" => "4", 
			"WV_ICON" => "4", 
			"WV_INVISIBLE" => "4", 
			"WV_UNREALIZED" => "4", 
			"WV_VISIBLE" => "4", 
			"ActiveField" => "5", 
			"AnalogField" => "5", 
			"AppFlag" => "5", 
			"AppSource" => "5", 
			"Array" => "5", 
			"ArrayObject" => "5", 
			"AttributeObject" => "5", 
			"BarField" => "5", 
			"BitmapObject" => "5", 
			"BoxTrim" => "5", 
			"BreakSpec" => "5", 
			"ButtonField" => "5", 
			"CellAttribute" => "5", 
			"Char" => "5", 
			"ChoiceBitmap" => "5", 
			"ChoiceField" => "5", 
			"ChoiceItem" => "5", 
			"ChoiceList" => "5", 
			"ClassSource" => "5", 
			"ColumnCross" => "5", 
			"ColumnField" => "5", 
			"CompositeField" => "5", 
			"CompSource" => "5", 
			"CrossTable" => "5", 
			"CursorBitmap" => "5", 
			"CursorObject" => "5", 
			"DataStream" => "5", 
			"Date" => "5", 
			"DateObject" => "5", 
			"DBEventObject" => "5", 
			"DBSessionObject" => "5", 
			"DisplayForm" => "5", 
			"DynExpr" => "5", 
			"EllipseShape" => "5", 
			"EntryField" => "5", 
			"EnumField" => "5", 
			"Event" => "5", 
			"FieldObject" => "5", 
			"FlexibleForm" => "5", 
			"Float" => "5", 
			"FloatObject" => "5", 
			"FormField" => "5", 
			"FrameExec" => "5", 
			"FrameForm" => "5", 
			"FrameSource" => "5", 
			"FreeTrim" => "5", 
			"GhostExec" => "5", 
			"GhostSource" => "5", 
			"ImageField" => "5", 
			"ImageTrim" => "5", 
			"Integer" => "5", 
			"IntegerObject" => "5", 
			"ListField" => "5", 
			"MatrixField" => "5", 
			"MenuBar" => "5", 
			"MenuButton" => "5", 
			"MenuField" => "5", 
			"MenuGroup" => "5", 
			"MenuItem" => "5", 
			"MenuList" => "5", 
			"MenuSeparator" => "5", 
			"MenuStack" => "5", 
			"MenuToggle" => "5", 
			"MethodExec" => "5", 
			"MethodObject" => "5", 
			"Money" => "5", 
			"MoneyObject" => "5", 
			"Object" => "5", 
			"OptionField" => "5", 
			"PaletteField" => "5", 
			"Proc4GLSource" => "5", 
			"ProcExec" => "5", 
			"ProcHandle" => "5", 
			"QueryCol" => "5", 
			"QueryObject" => "5", 
			"QueryParm" => "5", 
			"QueryTable" => "5", 
			"RadioField" => "5", 
			"RectangleShape" => "5", 
			"RowCross" => "5", 
			"ScalarField" => "5", 
			"ScrollBarField" => "5", 
			"SegmentShape" => "5", 
			"SessionObject" => "5", 
			"ShapeField" => "5", 
			"SliderField" => "5", 
			"smallInt" => "5", 
			"SQLSelect" => "5", 
			"StackField" => "5", 
			"StringObject" => "5", 
			"SubForm" => "5", 
			"TableField" => "5", 
			"ToggleField" => "5", 
			"UserClassObject" => "5", 
			"UserObject" => "5", 
			"VarChar" => "5", 
			"ViewportField" => "5", 
			"createInstance" => "6", 
			"getReferenceCopy" => "6", 
			"hasReferenceCopy" => "6", 
			"initAsExemplar" => "6", 
			"initAsInformation" => "6", 
			"initializeUpdateCounterWith" => "6", 
			"initObject" => "6", 
			"isStateNewOKPending" => "6", 
			"finalizeObject" => "6", 
			"saveObject" => "6", 
			"selectFromDB" => "6", 
			"selectFromDBScript" => "6", 
			"selectManyFromDB" => "6", 
			"selectManyFromDBScript" => "6", 
			"swapStar" => "6", 
			"AddBitmapItem" => "7", 
			"AddBreak" => "7", 
			"AddTextItem" => "7", 
			"AppendToFile" => "7", 
			"Assign" => "7", 
			"Beep" => "7", 
			"BitmapByIndex" => "7", 
			"BitmapByText" => "7", 
			"BitmapByValue" => "7", 
			"BringToFront" => "7", 
			"Call" => "7", 
			"CheckFailedMandatory" => "7", 
			"CheckFeature" => "7", 
			"Clear" => "7", 
			"ClearBreaks" => "7", 
			"Close" => "7", 
			"CommitToCache" => "7", 
			"CommitWork" => "7", 
			"ConcatString" => "7", 
			"ConcatVarchar" => "7", 
			"ConfirmPopup" => "7", 
			"Connect" => "7", 
			"CopyToClipboard" => "7", 
			"Create" => "7", 
			"CreateClass" => "7", 
			"CreateDynExpr" => "7", 
			"DBDelete" => "7", 
			"DBIdentifier" => "7", 
			"DBInsert" => "7", 
			"DBUpdate" => "7", 
			"DeclareData" => "7", 
			"DeferConfigure" => "7", 
			"DeleteColumn" => "7", 
			"DeleteFromDB" => "7", 
			"Disconnect" => "7", 
			"Duplicate" => "7", 
			"ExpandParm" => "7", 
			"ExportApp" => "7", 
			"ExportComp" => "7", 
			"ExtractString" => "7", 
			"FetchComponent" => "7", 
			"FetchRow" => "7", 
			"FieldByFullName" => "7", 
			"FieldByName" => "7", 
			"FieldByPosition" => "7", 
			"FilePopup" => "7", 
			"Find" => "7", 
			"FirstRow" => "8", 
			"Flush" => "7", 
			"GetAttribute" => "7", 
			"GetColorName" => "7", 
			"GetEnv" => "7", 
			"GetField" => "7", 
			"GetFieldValue" => "7", 
			"GetProcHandle" => "7", 
			"GetValue" => "7", 
			"ImportComp" => "7", 
			"IndexByBitmap" => "7", 
			"IndexByText" => "7", 
			"IndexByValue" => "7", 
			"InfoPopup" => "7", 
			"InsertChild" => "7", 
			"InsertColumn" => "7", 
			"InsertIntoDB" => "7", 
			"InsertRow" => "7", 
			"IsA" => "7", 
			"IsAncestorOf" => "7", 
			"IsDescendantOf" => "7", 
			"LastRow" => "8", 
			"LeftTruncate" => "7", 
			"Load" => "7", 
			"LocateString" => "7", 
			"MarkAllText" => "7", 
			"MarkSubText" => "7", 
			"NextRow" => "7", 
			"Open" => "7", 
			"OpenNewConnection" => "7", 
			"PrevRow" => "7", 
			"PurgeDBEvent" => "7", 
			"PurgeUserEvent" => "7", 
			"RaiseDBEvent" => "7", 
			"RegisterDBEvent" => "7", 
			"RegisterUserEvent" => "7", 
			"RemoveDBEvent" => "7", 
			"RemoveRow" => "7", 
			"ReplyPopup" => "7", 
			"RightTruncate" => "7", 
			"RollbackWork" => "7", 
			"SelectAll" => "7", 
			"SendSuperClass" => "7", 
			"SendToBack" => "7", 
			"SendUserEvent" => "7", 
			"SequenceValue" => "7", 
			"SetAttribute" => "7", 
			"SetAutoCommit" => "7", 
			"SetCols" => "7", 
			"SetEndPoints" => "7", 
			"SetFieldValue" => "7", 
			"SetInputFocus" => "7", 
			"SetRowDeleted" => "7", 
			"SetToDefault" => "7", 
			"SetValue" => "7", 
			"SetWindowIcon" => "7", 
			"SnapToGrid" => "7", 
			"Sort" => "7", 
			"StringValue" => "8", 
			"SubString" => "7", 
			"Terminate" => "7", 
			"TextByBitmap" => "7", 
			"TextByIndex" => "7", 
			"TextByValue" => "7", 
			"Trace" => "7", 
			"UndeclareData" => "7", 
			"UnmarkAllText" => "7", 
			"UnRegisterUserEvent" => "7", 
			"UpdateInDB" => "7", 
			"UpdBackground" => "7", 
			"UpdChoiceList" => "7", 
			"UpdField" => "7", 
			"UpdForeground" => "7", 
			"UpdMenu" => "7", 
			"ValueByBitmap" => "7", 
			"ValueByIndex" => "7", 
			"ValueByText" => "7", 
			"WaitFor" => "7", 
			"WhichCell" => "7", 
			"WhichRow" => "7", 
			"WhichTablefield" => "7", 
			"WinHelp" => "7", 
			"WriteToFile" => "7", 
			"_RowSelected" => "8", 
			"_RowState" => "8", 
			"AbsXLeft" => "8", 
			"AbsXRight" => "8", 
			"AbsYBottom" => "8", 
			"AbsYTop" => "8", 
			"AcrossValues" => "8", 
			"ActiveRow" => "8", 
			"Aggregate" => "8", 
			"AllBias" => "8", 
			"AllRows" => "8", 
			"AlterBy" => "8", 
			"AlterCount" => "8", 
			"AlterDate" => "8", 
			"AnchorPoint" => "8", 
			"AppFlags" => "8", 
			"Attributes" => "8", 
			"AutoUpdField" => "8", 
			"BgBitmap" => "8", 
			"BgColor" => "8", 
			"BgPattern" => "8", 
			"BitmapLabel" => "8", 
			"BlocksFrames" => "8", 
			"Breaks" => "8", 
			"CharsPerLine" => "8", 
			"ChildBottomMargin" => "8", 
			"ChildFields" => "8", 
			"ChildGravity" => "8", 
			"ChildLeftMargin" => "8", 
			"ChildRightMargin" => "8", 
			"ChildTopMargin" => "8", 
			"ChoiceItems" => "8", 
			"ClassName" => "8", 
			"ClientData" => "8", 
			"ClientInteger" => "8", 
			"ClientText" => "8", 
			"ClipHeight" => "8", 
			"ClipWidth" => "8", 
			"CollapsePolicy" => "8", 
			"ColSeparatorColor" => "8", 
			"ColSeparatorStyle" => "8", 
			"ColSeparatorWidth" => "8", 
			"ColumnIndex" => "8", 
			"ColumnNumber" => "8", 
			"Columns" => "8", 
			"Compile_Errors" => "8", 
			"CompiledExpression" => "8", 
			"CompiledResult" => "8", 
			"ControlButton" => "8", 
			"ControlField" => "8", 
			"CreateDate" => "8", 
			"Creator" => "8", 
			"CrossColumn" => "8", 
			"CurBias" => "8", 
			"CurBreakLevel" => "8", 
			"CurEnumBitmap" => "8", 
			"CurEnumText" => "8", 
			"CurEnumValue" => "8", 
			"CurFrame" => "8", 
			"CurMarkedText" => "8", 
			"CurMethod" => "8", 
			"CurMode" => "8", 
			"CurObject" => "8", 
			"CurOps" => "8", 
			"CurRow" => "8", 
			"Cursor" => "8", 
			"CursorPosition" => "8", 
			"Database" => "8", 
			"DataEntryErrorHandler" => "8", 
			"DataType" => "8", 
			"DBEvent" => "8", 
			"DBEventDatabase" => "8", 
			"DBEventName" => "8", 
			"DBEventOwner" => "8", 
			"DBEventPollrate" => "8", 
			"DBEventText" => "8", 
			"DBEventTime" => "8", 
			"DBHandle" => "8", 
			"DBMSError" => "8", 
			"DBMSErrorPrinting" => "8", 
			"DBMSMessagePrinting" => "8", 
			"DBSession" => "8", 
			"Declared" => "8", 
			"DefaultButton" => "8", 
			"DefaultString" => "8", 
			"DefaultValue" => "8", 
			"DesignTimeWhere" => "8", 
			"DisplayCapability" => "8", 
			"DisplayPolicy" => "8", 
			"ElevatorSize" => "8", 
			"EntityID" => "8", 
			"EnumBitmap" => "8", 
			"EnumText" => "8", 
			"EnumValue" => "8", 
			"ErrorNo" => "8", 
			"ErrorNumber" => "8", 
			"ErrorStatus" => "8", 
			"EventName" => "8", 
			"EventType" => "8", 
			"Expression" => "8", 
			"FgBitmap" => "8", 
			"FgColor" => "8", 
			"FgPattern" => "8", 
			"FileHandle" => "8", 
			"FirstMarked" => "8", 
			"Flags" => "8", 
			"FlagValues" => "8", 
			"FocusBehavior" => "8", 
			"ForceCase" => "8", 
			"FormatString" => "8", 
			"FullName" => "8", 
			"Gravity" => "8", 
			"GridX" => "8", 
			"GridY" => "8", 
			"GrowFrom" => "8", 
			"HasCellAttributes" => "8", 
			"HasDataChanged" => "8", 
			"HasFieldChanged" => "8", 
			"HasHeader" => "8", 
			"HasScrollBar" => "8", 
			"HasScrollBars" => "8", 
			"HasSingleCharFind" => "8", 
			"HasStatusBar" => "8", 
			"HavingClause" => "8", 
			"Height" => "8", 
			"HeightConstrained" => "8", 
			"Image" => "8", 
			"Ingres" => "8", 
			"InnerShadowWidth" => "8", 
			"InputFocusField" => "8", 
			"InputMasking" => "8", 
			"IsArray" => "8", 
			"IsAutoSized" => "8", 
			"IsBold" => "8", 
			"IsCurField" => "8", 
			"IsDBError" => "8", 
			"IsDBHandleField" => "8", 
			"IsDeleteWhere" => "8", 
			"IsDistinct" => "8", 
			"IsFileHandleField" => "8", 
			"IsGridOn" => "8", 
			"IsHighlighted" => "8", 
			"IsInsertTarget" => "8", 
			"IsItalic" => "8", 
			"IsLassoActive" => "8", 
			"IsMandatory" => "8", 
			"IsMoveBounded" => "8", 
			"IsMultiLine" => "8", 
			"IsNullable" => "8", 
			"IsPassword" => "8", 
			"IsPlain" => "8", 
			"IsPopup" => "8", 
			"IsPrivate" => "8", 
			"IsResizeable" => "8", 
			"IsResizeBounded" => "8", 
			"IsReverse" => "8", 
			"IsSelected" => "8", 
			"IsSelectTarget" => "8", 
			"IsStale" => "8", 
			"IsUpdateTarget" => "8", 
			"IsUpdateWhere" => "8", 
			"LastMarked" => "8", 
			"LayerSequence" => "8", 
			"Length" => "8", 
			"Level" => "8", 
			"LineColor" => "8", 
			"Lines" => "8", 
			"LineStyle" => "8", 
			"LineWidth" => "8", 
			"MaxCharacters" => "8", 
			"MaxRow" => "8", 
			"MaxValue" => "8", 
			"MessageErrorCode" => "8", 
			"MessageFloat" => "8", 
			"MessageInteger" => "8", 
			"MessageObject" => "8", 
			"MessageVarchar" => "8", 
			"Methods" => "8", 
			"MinValue" => "8", 
			"MouseDownText" => "8", 
			"MouseMoveText" => "8", 
			"Name" => "8", 
			"NativeHandle" => "8", 
			"NextBreakLevel" => "8", 
			"NumVisibleRows" => "8", 
			"ObjectShortRemarks" => "8", 
			"ObjectSource" => "8", 
			"OffBitmapLabel" => "8", 
			"OffTextLabel" => "8", 
			"OnBitmapLabel" => "8", 
			"OnTextLabel" => "8", 
			"OperatingSystem" => "8", 
			"OptionMenu" => "8", 
			"Orientation" => "8", 
			"OriginatorField" => "8", 
			"OuterHeight" => "8", 
			"OuterWidth" => "8", 
			"OutlineColor" => "8", 
			"OutlineStyle" => "8", 
			"OutlineWidth" => "8", 
			"PageSize" => "8", 
			"ParentApplication" => "8", 
			"ParentField" => "8", 
			"ParentFrame" => "8", 
			"ParentTable" => "8", 
			"PixelScreenHeight" => "8", 
			"PixelScreenWidth" => "8", 
			"Point1X" => "8", 
			"Point1Y" => "8", 
			"Point2X" => "8", 
			"Point2Y" => "8", 
			"PreFetchRows" => "8", 
			"PreviousField" => "8", 
			"ProtoField" => "8", 
			"Queries" => "8", 
			"Query" => "8", 
			"QueryBias" => "8", 
			"QueryMode" => "8", 
			"QueryName" => "8", 
			"QueryOps" => "8", 
			"ReadBias" => "8", 
			"ReadOps" => "8", 
			"ReasonCode" => "8", 
			"RequireRealField" => "8", 
			"ResultAssignment" => "8", 
			"RowCount" => "8", 
			"Rows" => "8", 
			"RowSeparatorColor" => "8", 
			"RowSeparatorStyle" => "8", 
			"RowSeparatorWidth" => "8", 
			"RunTimeWhere" => "8", 
			"Scope" => "8", 
			"ScreenHeight" => "8", 
			"ScreenWidth" => "8", 
			"Script" => "8", 
			"ScrollBarWidth" => "8", 
			"ScrollingChangesSelection" => "8", 
			"SelectedList" => "8", 
			"SelectionType" => "8", 
			"SeparatorColor" => "8", 
			"SeparatorStyle" => "8", 
			"SeparatorWidth" => "8", 
			"ServerType" => "8", 
			"SessionID" => "8", 
			"StartMenu" => "8", 
			"State" => "8", 
			"StatusText" => "8", 
			"StepSize" => "8", 
			"SuperClass" => "8", 
			"SuppressErrorTrace" => "8", 
			"SysCursor" => "8", 
			"TableBody" => "8", 
			"TableHeader" => "8", 
			"Tables" => "8", 
			"TabSeqNum" => "8", 
			"TargetArray" => "8", 
			"TargetField" => "8", 
			"TargetPrefix" => "8", 
			"TextLabel" => "8", 
			"TextLength" => "8", 
			"TextValue" => "8", 
			"Title" => "8", 
			"TitleTrim" => "8", 
			"TopForm" => "8", 
			"TopRow" => "8", 
			"TriggerField" => "8", 
			"TypeFace" => "8", 
			"TypeSize" => "8", 
			"UpdateBias" => "8", 
			"UpdateOps" => "8", 
			"UsePrefix" => "8", 
			"User1Bias" => "8", 
			"User1Ops" => "8", 
			"User2Bias" => "8", 
			"User2Ops" => "8", 
			"User3Bias" => "8", 
			"User3Ops" => "8", 
			"UseWidestCharacter" => "8", 
			"Value" => "8", 
			"ValueList" => "8", 
			"Version" => "8", 
			"VersionNumber" => "8", 
			"VersShortRemarks" => "8", 
			"VisibleRows" => "8", 
			"WidgetID" => "8", 
			"Width" => "8", 
			"WidthConstrained" => "8", 
			"WindowHeight" => "8", 
			"WindowIcon" => "8", 
			"WindowPlacement" => "8", 
			"WindowSystem" => "8", 
			"WindowTitle" => "8", 
			"WindowVisibility" => "8", 
			"WindowWidth" => "8", 
			"WindowXLeft" => "8", 
			"WindowYTop" => "8", 
			"XAnchorPoint" => "8", 
			"XEnd" => "8", 
			"XLeft" => "8", 
			"XOffset" => "8", 
			"XRight" => "8", 
			"XStart" => "8", 
			"YAnchorPoint" => "8", 
			"YBottom" => "8", 
			"YEnd" => "8", 
			"YOffset" => "8", 
			"YStart" => "8", 
			"YTop" => "8");

// Special extensions

// Each category can specify a PHP function that returns an altered
// version of the keyword.
        
        

$this->linkscripts    	= array(
			"1" => "donothing", 
			"2" => "donothing", 
			"3" => "donothing", 
			"4" => "donothing", 
			"5" => "donothing", 
			"6" => "donothing", 
			"7" => "donothing", 
			"8" => "donothing");
}


function donothing($keywordin)
{
	return $keywordin;
}

}?>
