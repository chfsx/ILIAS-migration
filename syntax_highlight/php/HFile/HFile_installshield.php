<?php
global $BEAUT_PATH;
if (!isset ($BEAUT_PATH)) return;
require_once("$BEAUT_PATH/Beautifier/HFile.php");
  class HFile_installshield extends HFile{
   function HFile_installshield(){
     $this->HFile();	
/*************************************/
// Beautifier Highlighting Configuration File 
// InstallShield
/*************************************/
// Flags

$this->nocase            	= "0";
$this->notrim            	= "0";
$this->perl              	= "0";

// Colours

$this->colours        	= array("blue", "purple", "gray", "brown");
$this->quotecolour       	= "blue";
$this->blockcommentcolour	= "green";
$this->linecommentcolour 	= "green";

// Indent Strings

$this->indent            	= array("begin", "program", "then", "else", "repeat");
$this->unindent          	= array("end;", "endprogram;", "endif;", "endwhile;", "endswitch;", "else");

// String characters and delimiters

$this->stringchars       	= array("\"", "'");
$this->delimiters        	= array("\"", "'", ",", ";", ".", ":", "*", "{", "}", "[", "]", "<", ">", "(", ")", "=", "/", "+", "-", "#", "%", "|", "&", "	", "!");
$this->escchar           	= "";

// Comment settings

$this->linecommenton     	= array("//");
$this->blockcommenton    	= array("/*");
$this->blockcommentoff   	= array("*/");

// Keywords (keyword mapping to colour number)

$this->keywords          	= array(
			"abort" => "1", 
			"begin" => "1", 
			"BOOL" => "1", 
			"BYREF" => "1", 
			"case" => "1", 
			"CHAR" => "1", 
			"default" => "1", 
			"downto" => "1", 
			"define" => "1", 
			"else" => "1", 
			"end" => "1", 
			"endif" => "1", 
			"endfor" => "1", 
			"endwhile" => "1", 
			"endswitch" => "1", 
			"endprogram" => "1", 
			"exit" => "1", 
			"elseif" => "1", 
			"error" => "1", 
			"elif" => "1", 
			"for" => "1", 
			"function" => "1", 
			"goto" => "1", 
			"GDI" => "1", 
			"HWND" => "1", 
			"if" => "1", 
			"include" => "1", 
			"ifdef" => "1", 
			"ifndef" => "1", 
			"INT" => "1", 
			"KERNEL" => "1", 
			"LIST" => "1", 
			"LONG" => "1", 
			"NUMBER" => "1", 
			"number" => "1", 
			"POINTER" => "1", 
			"program" => "1", 
			"prototype" => "1", 
			"return" => "1", 
			"repeat" => "1", 
			"SHORT" => "1", 
			"STRING" => "1", 
			"string" => "1", 
			"step" => "1", 
			"switch" => "1", 
			"struct" => "1", 
			"then" => "1", 
			"to" => "1", 
			"typedef" => "1", 
			"until" => "1", 
			"undef" => "1", 
			"USER" => "1", 
			"while" => "1", 
			"#" => "1", 
			"_MAX_LENGTH" => "2", 
			"_MAX_STRING" => "2", 
			"AFTER" => "2", 
			"ALLCONTENTS" => "2", 
			"ALLCONTROLS" => "2", 
			"APPEND" => "2", 
			"ASKDESTPATH" => "2", 
			"ASKOPTIONS" => "2", 
			"ASKPATH" => "2", 
			"ASKTEXT" => "2", 
			"BATCH_INSTALL" => "2", 
			"BACK" => "2", 
			"BACKBUTTON" => "2", 
			"BACKGROUND" => "2", 
			"BACKGROUNDCAPTION" => "2", 
			"BADPATH" => "2", 
			"BADTAGFILE" => "2", 
			"BASEMEMORY" => "2", 
			"BEFORE" => "2", 
			"BILLBOARD" => "2", 
			"BINARY" => "2", 
			"BITMAP256COLORS" => "2", 
			"BITMAPFADE" => "2", 
			"BITMAPICON" => "2", 
			"BK_BLUE" => "2", 
			"BK_GREEN" => "2", 
			"BK_MAGENTA" => "2", 
			"BK_MAGENTA1" => "2", 
			"BK_ORANGE" => "2", 
			"BK_PINK" => "2", 
			"BK_RED" => "2", 
			"BK_SMOOTH" => "2", 
			"BK_SOLIDBLACK" => "2", 
			"BK_SOLIDBLUE" => "2", 
			"BK_SOLIDGREEN" => "2", 
			"BK_SOLIDMAGENTA" => "2", 
			"BK_SOLIDORANGE" => "2", 
			"BK_SOLIDPINK" => "2", 
			"BK_SOLIDRED" => "2", 
			"BK_SOLIDWHITE" => "2", 
			"BK_SOLIDYELLOW" => "2", 
			"BK_YELLOW" => "2", 
			"BLACK" => "2", 
			"BLUE" => "2", 
			"BOOTUPDRIVE" => "2", 
			"BUTTON_CHECKED" => "2", 
			"BUTTON_ENTER" => "2", 
			"BUTTON_UNCHECKED" => "2", 
			"BUTTON_UNKNOWN" => "2", 
			"CMDLINE" => "2", 
			"COMMONFILES" => "2", 
			"CANCEL" => "2", 
			"CANCELBUTTON" => "2", 
			"CC_ERR_FILEFORMATERROR" => "2", 
			"CC_ERR_FILEREADERROR" => "2", 
			"CC_ERR_NOCOMPONENTLIST" => "2", 
			"CC_ERR_OUTOFMEMORY" => "2", 
			"CDROM" => "2", 
			"CDROM_DRIVE" => "2", 
			"CENTERED" => "2", 
			"CHANGEDIR" => "2", 
			"CHECKBOX" => "2", 
			"CHECKBOX95" => "2", 
			"CHECKLINE" => "2", 
			"CHECKMARK" => "2", 
			"CMD_CLOSE" => "2", 
			"CMD_MAXIMIZE" => "2", 
			"CMD_MINIMIZE" => "2", 
			"CMD_PUSHDOWN" => "2", 
			"CMD_RESTORE" => "2", 
			"COLORMODE256" => "2", 
			"COLORS" => "2", 
			"COMBOBOX_ENTER" => "2", 
			"COMBOBOX_SELECT" => "2", 
			"COMMAND" => "2", 
			"COMMANDEX" => "2", 
			"COMMON" => "2", 
			"COMP_DONE" => "2", 
			"COMP_ERR_CREATEDIR" => "2", 
			"COMP_ERR_DESTCONFLICT" => "2", 
			"COMP_ERR_FILENOTINLIB" => "2", 
			"COMP_ERR_FILESIZE" => "2", 
			"COMP_ERR_FILETOOLARGE" => "2", 
			"COMP_ERR_HEADER" => "2", 
			"COMP_ERR_INCOMPATIBLE" => "2", 
			"COMP_ERR_INTPUTNOTCOMPRESSED" => "2", 
			"COMP_ERR_INVALIDLIST" => "2", 
			"COMP_ERR_LAUNCHSERVER" => "2", 
			"COMP_ERR_MEMORY" => "2", 
			"COMP_ERR_NODISKSPACE" => "2", 
			"COMP_ERR_OPENINPUT" => "2", 
			"COMP_ERR_OPENOUTPUT" => "2", 
			"COMP_ERR_OPTIONS" => "2", 
			"COMP_ERR_OUTPUTNOTCOMPRESSED" => "2", 
			"COMP_ERR_SPLIT" => "2", 
			"COMP_ERR_TARGET" => "2", 
			"COMP_ERR_TARGETREADONLY" => "2", 
			"COMP_ERR_WRITE" => "2", 
			"COMP_INFO_ATTRIBUTE" => "2", 
			"COMP_INFO_COMPSIZE" => "2", 
			"COMP_INFO_DATE" => "2", 
			"COMP_INFO_INVALIDATEPASSWORD" => "2", 
			"COMP_INFO_ORIGSIZE" => "2", 
			"COMP_INFO_SETPASSWORD" => "2", 
			"COMP_INFO_TIME" => "2", 
			"COMP_INFO_VERSIONLS" => "2", 
			"COMP_INFO_VERSIONMS" => "2", 
			"COMP_NORMAL" => "2", 
			"COMP_UPDATE_DATE" => "2", 
			"COMP_UPDATE_DATE_NEWER" => "2", 
			"COMP_UPDATE_SAME" => "2", 
			"COMP_UPDATE_VERSION" => "2", 
			"COMPACT" => "2", 
			"COMPARE_DATE" => "2", 
			"COMPARE_SIZE" => "2", 
			"COMPARE_VERSION" => "2", 
			"COMPONENT_FIELD_CDROM_FOLDER" => "2", 
			"COMPONENT_FIELD_DESCRIPTION" => "2", 
			"COMPONENT_FIELD_DESTINATION" => "2", 
			"COMPONENT_FIELD_DISPLAYNAME" => "2", 
			"COMPONENT_FIELD_FILENEED" => "2", 
			"COMPONENT_FIELD_FTPLOCATION" => "2", 
			"COMPONENT_FIELD_HTTPLOCATION" => "2", 
			"COMPONENT_FIELD_MISC" => "2", 
			"COMPONENT_FIELD_OVERWRITE" => "2", 
			"COMPONENT_FIELD_PASSWORD" => "2", 
			"COMPONENT_FIELD_SELECTED" => "2", 
			"COMPONENT_FIELD_SIZE" => "2", 
			"COMPONENT_FIELD_STATUS" => "2", 
			"COMPONENT_FIELD_VISIBLE" => "2", 
			"COMPONENT_FILEINFO_COMPRESSED" => "2", 
			"COMPONENT_FILEINFO_COMPRESSENGINE" => "2", 
			"COMPONENT_FILEINFO_LANGUAGECOMPONENT_FILEINFO_OS" => "2", 
			"COMPONENT_FILEINFO_POTENTIALLYLOCKED" => "2", 
			"COMPONENT_FILEINFO_SELFREGISTERING" => "2", 
			"COMPONENT_FILEINFO_SHARED" => "2", 
			"COMPONENT_INFO_ATTRIBUTE" => "2", 
			"COMPONENT_INFO_COMPSIZE" => "2", 
			"COMPONENT_INFO_DATE" => "2", 
			"COMPONENT_INFO_DATE_EX_EX" => "2", 
			"COMPONENT_INFO_LANGUAGE" => "2", 
			"COMPONENT_INFO_ORIGSIZE" => "2", 
			"COMPONENT_INFO_OS" => "2", 
			"COMPONENT_INFO_TIME" => "2", 
			"COMPONENT_INFO_VERSIONLS" => "2", 
			"COMPONENT_INFO_VERSIONMS" => "2", 
			"COMPONENT_INFO_VERSIONSTR" => "2", 
			"COMPONENT_VALUE_ALWAYSOVERWRITE" => "2", 
			"COMPONENT_VALUE_CRITICAL" => "2", 
			"COMPONENT_VALUE_HIGHLYRECOMMENDED" => "2", 
			"COMPONENT_FILEINFO_LANGUAGE" => "2", 
			"COMPONENT_FILEINFO_OS" => "2", 
			"COMPONENT_VALUE_NEVEROVERWRITE" => "2", 
			"COMPONENT_VALUE_NEWERDATE" => "2", 
			"COMPONENT_VALUE_NEWERVERSION" => "2", 
			"COMPONENT_VALUE_OLDERDATE" => "2", 
			"COMPONENT_VALUE_OLDERVERSION" => "2", 
			"COMPONENT_VALUE_SAMEORNEWDATE" => "2", 
			"COMPONENT_VALUE_SAMEORNEWERVERSION" => "2", 
			"COMPONENT_VALUE_STANDARD" => "2", 
			"COMPONENT_VIEW_CHANGE" => "2", 
			"COMPONENT_INFO_DATE_EX" => "2", 
			"COMPONENT_VIEW_CHILDVIEW" => "2", 
			"COMPONENT_VIEW_COMPONENT" => "2", 
			"COMPONENT_VIEW_DESCRIPTION" => "2", 
			"COMPONENT_VIEW_MEDIA" => "2", 
			"COMPONENT_VIEW_PARENTVIEW" => "2", 
			"COMPONENT_VIEW_SIZEAVAIL" => "2", 
			"COMPONENT_VIEW_SIZETOTAL" => "2", 
			"COMPONENT_VIEW_TARGETLOCATION" => "2", 
			"COMPRESSHIGH" => "2", 
			"COMPRESSLOW" => "2", 
			"COMPRESSMED" => "2", 
			"COMPRESSNONE" => "2", 
			"CONTIGUOUS" => "2", 
			"CONTINUE" => "2", 
			"COPY_ERR_CREATEDIR" => "2", 
			"COPY_ERR_NODISKSPACE" => "2", 
			"COPY_ERR_OPENINPUT" => "2", 
			"COPY_ERR_OPENOUTPUT" => "2", 
			"COPY_ERR_TARGETREADONLY" => "2", 
			"COPY_ERR_MEMORY" => "2", 
			"CORECOMPONENTHANDLING" => "2", 
			"CPU" => "2", 
			"CUSTOM" => "2", 
			"DATA_COMPONENT" => "2", 
			"DATA_LIST" => "2", 
			"DATA_NUMBER" => "2", 
			"DATA_STRING" => "2", 
			"DATE" => "2", 
			"DEFAULT" => "2", 
			"DEFWINDOWMODE" => "2", 
			"DELETE_EOF" => "2", 
			"DIALOG" => "2", 
			"DIALOGCACHE" => "2", 
			"DIALOGTHINFONT" => "2", 
			"DIR_WRITEABLE" => "2", 
			"DIRECTORY" => "2", 
			"DISABLE" => "2", 
			"DISK" => "2", 
			"DISK_FREESPACE" => "2", 
			"DISK_TOTALSPACE" => "2", 
			"DISKID" => "2", 
			"DLG_ASK_OPTIONS" => "2", 
			"DLG_ASK_PATH" => "2", 
			"DLG_ASK_TEXT" => "2", 
			"DLG_ASK_YESNO" => "2", 
			"DLG_CANCEL" => "2", 
			"DLG_CDIR" => "2", 
			"DLG_CDIR_MSG" => "2", 
			"DLG_CENTERED" => "2", 
			"DLG_CLOSE" => "2", 
			"DLG_DIR_DIRECTORY" => "2", 
			"DLG_DIR_FILE" => "2", 
			"DLG_ENTER_DISK" => "2", 
			"DLG_ERR" => "2", 
			"DLG_ERR_ALREADY_EXISTS" => "2", 
			"DLG_ERR_ENDDLG" => "2", 
			"DLG_INFO_ALTIMAGE" => "2", 
			"DLG_INFO_CHECKMETHOD" => "2", 
			"DLG_INFO_CHECKSELECTION" => "2", 
			"DLG_INFO_ENABLEIMAGE" => "2", 
			"DLG_INFO_KUNITS" => "2", 
			"DLG_INFO_USEDECIMAL" => "2", 
			"DLG_INIT" => "2", 
			"DLG_MSG_ALL" => "2", 
			"DLG_MSG_INFORMATION" => "2", 
			"DLG_MSG_NOT_HAND" => "2", 
			"DLG_MSG_SEVERE" => "2", 
			"DLG_MSG_STANDARD" => "2", 
			"DLG_MSG_WARNING" => "2", 
			"DLG_OK" => "2", 
			"DLG_STATUS" => "2", 
			"DLG_USER_CAPTION" => "2", 
			"DRIVE" => "2", 
			"DRIVEOPEN" => "2", 
			"DLG_DIR_DRIVE" => "2", 
			"EDITBOX_CHANGE" => "2", 
			"EFF_BOXSTRIPE" => "2", 
			"EFF_FADE" => "2", 
			"EFF_HORZREVEAL" => "2", 
			"EFF_HORZSTRIPE" => "2", 
			"EFF_NONE" => "2", 
			"EFF_REVEAL" => "2", 
			"EFF_VERTSTRIPE" => "2", 
			"ENABLE" => "2", 
			"END_OF_FILE" => "2", 
			"END_OF_LIST" => "2", 
			"ENHANCED" => "2", 
			"ENTERDISK" => "2", 
			"ENTERDISK_ERRMSG" => "2", 
			"ENTERDISKBEEP" => "2", 
			"ENVSPACE" => "2", 
			"EQUALS" => "2", 
			"ERR_BADPATH" => "2", 
			"ERR_BADTAGFILE" => "2", 
			"ERR_BOX_BADPATH" => "2", 
			"ERR_BOX_BADTAGFILE" => "2", 
			"ERR_BOX_DISKID" => "2", 
			"ERR_BOX_DRIVEOPEN" => "2", 
			"ERR_BOX_EXIT" => "2", 
			"ERR_BOX_HELP" => "2", 
			"ERR_BOX_NOSPACE" => "2", 
			"ERR_BOX_PAUSE" => "2", 
			"ERR_BOX_READONLY" => "2", 
			"ERR_DISKID" => "2", 
			"ERR_DRIVEOPEN" => "2", 
			"EXCLUDE_SUBDIR" => "2", 
			"EXCLUSIVE" => "2", 
			"EXISTS" => "2", 
			"EXIT" => "2", 
			"EXTENDEDMEMORY" => "2", 
			"EXTENSION_ONLY" => "2", 
			"ERRORFILENAME" => "2", 
			"FADE_IN" => "2", 
			"FADE_OUT" => "2", 
			"FAILIFEXISTS" => "2", 
			"FALSE" => "2", 
			"FDRIVE_NUM" => "2", 
			"FEEDBACK" => "2", 
			"FEEDBACK_FULL" => "2", 
			"FEEDBACK_OPERATION" => "2", 
			"FEEDBACK_SPACE" => "2", 
			"FILE_ATTR_ARCHIVED" => "2", 
			"FILE_ATTR_DIRECTORY" => "2", 
			"FILE_ATTR_HIDDEN" => "2", 
			"FILE_ATTR_NORMAL" => "2", 
			"FILE_ATTR_READONLY" => "2", 
			"FILE_ATTR_SYSTEM" => "2", 
			"FILE_ATTRIBUTE" => "2", 
			"FILE_BIN_CUR" => "2", 
			"FILE_BIN_END" => "2", 
			"FILE_BIN_START" => "2", 
			"FILE_DATE" => "2", 
			"FILE_EXISTS" => "2", 
			"FILE_INSTALLED" => "2", 
			"FILE_INVALID" => "2", 
			"FILE_IS_LOCKED" => "2", 
			"FILE_LINE_LENGTH" => "2", 
			"FILE_LOCKED" => "2", 
			"FILE_MODE_APPEND" => "2", 
			"FILE_MODE_BINARY" => "2", 
			"FILE_MODE_BINARYREADONLY" => "2", 
			"FILE_MODE_NORMAL" => "2", 
			"FILE_NO_VERSION" => "2", 
			"FILE_NOT_FOUND" => "2", 
			"FILE_RD_ONLY" => "2", 
			"FILE_SIZE" => "2", 
			"FILE_SRC_EQUAL" => "2", 
			"FILE_SRC_OLD" => "2", 
			"FILE_TIME" => "2", 
			"FILE_WRITEABLE" => "2", 
			"FILENAME" => "2", 
			"FILENAME_ONLY" => "2", 
			"FINISHBUTTON" => "2", 
			"FIXED_DRIVE" => "2", 
			"FONT_TITLE" => "2", 
			"FREEENVSPACE" => "2", 
			"FS_CREATEDIR" => "2", 
			"FS_DISKONEREQUIRED" => "2", 
			"FS_DONE" => "2", 
			"FS_FILENOTINLIB" => "2", 
			"FS_GENERROR" => "2", 
			"FS_INCORRECTDISK" => "2", 
			"FS_LAUNCHPROCESS" => "2", 
			"FS_OPERROR" => "2", 
			"FS_OUTOFSPACE" => "2", 
			"FS_PACKAGING" => "2", 
			"FS_RESETREQUIRED" => "2", 
			"FS_TARGETREADONLY" => "2", 
			"FS_TONEXTDISK" => "2", 
			"FULL" => "2", 
			"FULLSCREEN" => "2", 
			"FULLSCREENSIZE" => "2", 
			"FULLWINDOWMODE" => "2", 
			"FOLDER_DESKTOP" => "2", 
			"FOLDER_PROGRAMS" => "2", 
			"FOLDER_STARTMENU" => "2", 
			"FOLDER_STARTUP" => "2", 
			"GREATER_THAN" => "2", 
			"GREEN" => "2", 
			"HELP" => "2", 
			"HKEY_CLASSES_ROOT" => "2", 
			"HKEY_CURRENT_CONFIG" => "2", 
			"HKEY_CURRENT_USER" => "2", 
			"HKEY_DYN_DATA" => "2", 
			"HKEY_LOCAL_MACHINE" => "2", 
			"HKEY_PERFORMANCE_DATA" => "2", 
			"HKEY_USERS" => "2", 
			"HOURGLASS" => "2", 
			"HWND_DESKTOP" => "2", 
			"HWND_INSTALL" => "2", 
			"IGNORE_READONLY" => "2", 
			"INCLUDE_SUBDIR" => "2", 
			"INDVFILESTATUS" => "2", 
			"INFO" => "2", 
			"INFO_DESCRIPTION" => "2", 
			"INFO_IMAGE" => "2", 
			"INFO_MISC" => "2", 
			"INFO_SIZE" => "2", 
			"INFO_SUBCOMPONENT" => "2", 
			"INFO_VISIBLE" => "2", 
			"INFORMATION" => "2", 
			"INVALID_LIST" => "2", 
			"IS_186" => "2", 
			"IS_286" => "2", 
			"IS_386" => "2", 
			"IS_486" => "2", 
			"IS_8514A" => "2", 
			"IS_86" => "2", 
			"IS_ALPHA" => "2", 
			"IS_CDROM" => "2", 
			"IS_CGA" => "2", 
			"IS_DOS" => "2", 
			"IS_EGA" => "2", 
			"IS_FIXED" => "2", 
			"IS_FOLDER" => "2", 
			"IS_ITEM" => "2", 
			"ISLANG_ALL" => "2", 
			"ISLANG_ARABIC" => "2", 
			"ISLANG_ARABIC_SAUDIARABIA" => "2", 
			"ISLANG_ARABIC_IRAQ" => "2", 
			"ISLANG_ARABIC_EGYPT" => "2", 
			"ISLANG_ARABIC_LIBYA" => "2", 
			"ISLANG_ARABIC_ALGERIA" => "2", 
			"ISLANG_ARABIC_MOROCCO" => "2", 
			"ISLANG_ARABIC_TUNISIA" => "2", 
			"ISLANG_ARABIC_OMAN" => "2", 
			"ISLANG_ARABIC_YEMEN" => "2", 
			"ISLANG_ARABIC_SYRIA" => "2", 
			"ISLANG_ARABIC_JORDAN" => "2", 
			"ISLANG_ARABIC_LEBANON" => "2", 
			"ISLANG_ARABIC_KUWAIT" => "2", 
			"ISLANG_ARABIC_UAE" => "2", 
			"ISLANG_ARABIC_BAHRAIN" => "2", 
			"ISLANG_ARABIC_QATAR" => "2", 
			"ISLANG_AFRIKAANS" => "2", 
			"ISLANG_AFRIKAANS_STANDARD" => "2", 
			"ISLANG_ALBANIAN" => "2", 
			"ISLANG_ENGLISH_TRINIDAD" => "2", 
			"ISLANG_ALBANIAN_STANDARD" => "2", 
			"ISLANG_BASQUE" => "2", 
			"ISLANG_BASQUE_STANDARD" => "2", 
			"ISLANG_BULGARIAN" => "2", 
			"ISLANG_BULGARIAN_STANDARD" => "2", 
			"ISLANG_BELARUSIAN" => "2", 
			"ISLANG_BELARUSIAN_STANDARD" => "2", 
			"ISLANG_CATALAN" => "2", 
			"ISLANG_CATALAN_STANDARD" => "2", 
			"ISLANG_CHINESE" => "2", 
			"ISLANG_CHINESE_TAIWAN" => "2", 
			"ISLANG_CHINESE_PRC" => "2", 
			"ISLANG_SPANISH_PUERTORICO" => "2", 
			"ISLANG_CHINESE_HONGKONG" => "2", 
			"ISLANG_CHINESE_SINGAPORE" => "2", 
			"ISLANG_CROATIAN" => "2", 
			"ISLANG_CROATIAN_STANDARD" => "2", 
			"ISLANG_CZECH" => "2", 
			"ISLANG_CZECH_STANDARD" => "2", 
			"ISLANG_DANISH" => "2", 
			"ISLANG_DANISH_STANDARD" => "2", 
			"ISLANG_DUTCH" => "2", 
			"ISLANG_DUTCH_STANDARD" => "2", 
			"ISLANG_DUTCH_BELGIAN" => "2", 
			"ISLANG_ENGLISH" => "2", 
			"ISLANG_ENGLISH_BELIZE" => "2", 
			"ISLANG_ENGLISH_UNITEDSTATES" => "2", 
			"ISLANG_ENGLISH_UNITEDKINGDOM" => "2", 
			"ISLANG_ENGLISH_AUSTRALIAN" => "2", 
			"ISLANG_ENGLISH_CANADIAN" => "2", 
			"ISLANG_ENGLISH_NEWZEALAND" => "2", 
			"ISLANG_ENGLISH_IRELAND" => "2", 
			"ISLANG_ENGLISH_SOUTHAFRICA" => "2", 
			"ISLANG_ENGLISH_JAMAICA" => "2", 
			"ISLANG_ENGLISH_CARIBBEAN" => "2", 
			"ISLANG_ESTONIAN" => "2", 
			"ISLANG_ESTONIAN_STANDARD" => "2", 
			"ISLANG_FAEROESE" => "2", 
			"ISLANG_FAEROESE_STANDARD" => "2", 
			"ISLANG_FARSI" => "2", 
			"ISLANG_FINNISH" => "2", 
			"ISLANG_FINNISH_STANDARD" => "2", 
			"ISLANG_FRENCH" => "2", 
			"ISLANG_FRENCH_STANDARD" => "2", 
			"ISLANG_FRENCH_BELGIAN" => "2", 
			"ISLANG_FRENCH_CANADIAN" => "2", 
			"ISLANG_FRENCH_SWISS" => "2", 
			"ISLANG_FRENCH_LUXEMBOURG" => "2", 
			"ISLANG_FARSI_STANDARD" => "2", 
			"ISLANG_GERMAN" => "2", 
			"ISLANG_GERMAN_STANDARD" => "2", 
			"ISLANG_GERMAN_SWISS" => "2", 
			"ISLANG_GERMAN_AUSTRIAN" => "2", 
			"ISLANG_GERMAN_LUXEMBOURG" => "2", 
			"ISLANG_GERMAN_LIECHTENSTEIN" => "2", 
			"ISLANG_GREEK" => "2", 
			"ISLANG_GREEK_STANDARD" => "2", 
			"ISLANG_HEBREW" => "2", 
			"ISLANG_HEBREW_STANDARD" => "2", 
			"ISLANG_HUNGARIAN" => "2", 
			"ISLANG_HUNGARIAN_STANDARD" => "2", 
			"ISLANG_ICELANDIC" => "2", 
			"ISLANG_ICELANDIC_STANDARD" => "2", 
			"ISLANG_INDONESIAN" => "2", 
			"ISLANG_INDONESIAN_STANDARD" => "2", 
			"ISLANG_ITALIAN" => "2", 
			"ISLANG_ITALIAN_STANDARD" => "2", 
			"ISLANG_ITALIAN_SWISS" => "2", 
			"ISLANG_JAPANESE" => "2", 
			"ISLANG_JAPANESE_STANDARD" => "2", 
			"ISLANG_KOREAN" => "2", 
			"ISLANG_KOREAN_STANDARD" => "2", 
			"ISLANG_KOREAN_JOHAB" => "2", 
			"ISLANG_LATVIAN" => "2", 
			"ISLANG_LATVIAN_STANDARD" => "2", 
			"ISLANG_LITHUANIAN" => "2", 
			"ISLANG_LITHUANIAN_STANDARD" => "2", 
			"ISLANG_NORWEGIAN" => "2", 
			"ISLANG_NORWEGIAN_BOKMAL" => "2", 
			"ISLANG_NORWEGIAN_NYNORSK" => "2", 
			"ISLANG_POLISH" => "2", 
			"ISLANG_POLISH_STANDARD" => "2", 
			"ISLANG_PORTUGUESE" => "2", 
			"ISLANG_PORTUGUESE_BRAZILIAN" => "2", 
			"ISLANG_PORTUGUESE_STANDARD" => "2", 
			"ISLANG_ROMANIAN" => "2", 
			"ISLANG_ROMANIAN_STANDARD" => "2", 
			"ISLANG_RUSSIAN" => "2", 
			"ISLANG_RUSSIAN_STANDARD" => "2", 
			"ISLANG_SLOVAK" => "2", 
			"ISLANG_SLOVAK_STANDARD" => "2", 
			"ISLANG_SLOVENIAN" => "2", 
			"ISLANG_SLOVENIAN_STANDARD" => "2", 
			"ISLANG_SERBIAN" => "2", 
			"ISLANG_SERBIAN_LATIN" => "2", 
			"ISLANG_SERBIAN_CYRILLIC" => "2", 
			"ISLANG_SPANISH" => "2", 
			"ISLANG_SPANISH_ARGENTINA" => "2", 
			"ISLANG_SPANISH_BOLIVIA" => "2", 
			"ISLANG_SPANISH_CHILE" => "2", 
			"ISLANG_SPANISH_COLOMBIA" => "2", 
			"ISLANG_SPANISH_COSTARICA" => "2", 
			"ISLANG_SPANISH_DOMINICANREPUBLIC" => "2", 
			"ISLANG_SPANISH_ECUADOR" => "2", 
			"ISLANG_SPANISH_ELSALVADOR" => "2", 
			"ISLANG_SPANISH_GUATEMALA" => "2", 
			"ISLANG_SPANISH_HONDURAS" => "2", 
			"ISLANG_SPANISH_MEXICAN" => "2", 
			"ISLANG_THAI_STANDARD" => "2", 
			"ISLANG_SPANISH_MODERNSORT" => "2", 
			"ISLANG_SPANISH_NICARAGUA" => "2", 
			"ISLANG_SPANISH_PANAMA" => "2", 
			"ISLANG_SPANISH_PARAGUAY" => "2", 
			"ISLANG_SPANISH_PERU" => "2", 
			"IISLANG_SPANISH_PUERTORICO" => "2", 
			"ISLANG_SPANISH_TRADITIONALSORT" => "2", 
			"ISLANG_SPANISH_VENEZUELA" => "2", 
			"ISLANG_SPANISH_URUGUAY" => "2", 
			"ISLANG_SWEDISH" => "2", 
			"ISLANG_SWEDISH_FINLAND" => "2", 
			"ISLANG_SWEDISH_STANDARD" => "2", 
			"ISLANG_THAI" => "2", 
			"ISLANG_THA_STANDARDI" => "2", 
			"ISLANG_TURKISH" => "2", 
			"ISLANG_TURKISH_STANDARD" => "2", 
			"ISLANG_UKRAINIAN" => "2", 
			"ISLANG_UKRAINIAN_STANDARD" => "2", 
			"ISLANG_VIETNAMESE" => "2", 
			"ISLANG_VIETNAMESE_STANDARD" => "2", 
			"IS_MIPS" => "2", 
			"IS_MONO" => "2", 
			"IS_OS2" => "2", 
			"ISOSL_ALL" => "2", 
			"ISOSL_WIN31" => "2", 
			"ISOSL_WIN95" => "2", 
			"ISOSL_NT351" => "2", 
			"ISOSL_NT351_ALPHA" => "2", 
			"ISOSL_NT351_MIPS" => "2", 
			"ISOSL_NT351_PPC" => "2", 
			"ISOSL_NT40" => "2", 
			"ISOSL_NT40_ALPHA" => "2", 
			"ISOSL_NT40_MIPS" => "2", 
			"ISOSL_NT40_PPC" => "2", 
			"IS_PENTIUM" => "2", 
			"IS_POWERPC" => "2", 
			"IS_RAMDRIVE" => "2", 
			"IS_REMOTE" => "2", 
			"IS_REMOVABLE" => "2", 
			"IS_SVGA" => "2", 
			"IS_UNKNOWN" => "2", 
			"IS_UVGA" => "2", 
			"IS_VALID_PATH" => "2", 
			"IS_VGA" => "2", 
			"IS_WIN32S" => "2", 
			"IS_WINDOWS" => "2", 
			"IS_WINDOWS95" => "2", 
			"IS_WINDOWSNT" => "2", 
			"IS_WINOS2" => "2", 
			"IS_XVGA" => "2", 
			"ISTYPE" => "2", 
			"INFOFILENAME" => "2", 
			"ISRES" => "2", 
			"ISUSER" => "2", 
			"ISVERSION" => "2", 
			"LANGUAGE" => "2", 
			"LANGUAGE_DRV" => "2", 
			"LESS_THAN" => "2", 
			"LINE_NUMBER" => "2", 
			"LISTBOX_ENTER" => "2", 
			"LISTBOX_SELECT" => "2", 
			"LISTFIRST" => "2", 
			"LISTLAST" => "2", 
			"LISTNEXT" => "2", 
			"LISTPREV" => "2", 
			"LOCKEDFILE" => "2", 
			"LOGGING" => "2", 
			"LOWER_LEFT" => "2", 
			"LOWER_RIGHT" => "2", 
			"LIST_NULL" => "2", 
			"MAGENTA" => "2", 
			"MAINCAPTION" => "2", 
			"MATH_COPROCESSOR" => "2", 
			"MAX_STRING" => "2", 
			"MENU" => "2", 
			"METAFILE" => "2", 
			"MMEDIA_AVI" => "2", 
			"MMEDIA_MIDI" => "2", 
			"MMEDIA_PLAYASYNCH" => "2", 
			"MMEDIA_PLAYCONTINUOUS" => "2", 
			"MMEDIA_PLAYSYNCH" => "2", 
			"MMEDIA_STOP" => "2", 
			"MMEDIA_WAVE" => "2", 
			"MOUSE" => "2", 
			"MOUSE_DRV" => "2", 
			"MEDIA" => "2", 
			"MODE" => "2", 
			"NETWORK" => "2", 
			"NETWORK_DRV" => "2", 
			"NEXT" => "2", 
			"NEXTBUTTON" => "2", 
			"NO" => "2", 
			"NO_SUBDIR" => "2", 
			"NO_WRITE_ACCESS" => "2", 
			"NONCONTIGUOUS" => "2", 
			"NONEXCLUSIVE" => "2", 
			"NORMAL" => "2", 
			"NORMALMODE" => "2", 
			"NOSET" => "2", 
			"NOTEXISTS" => "2", 
			"NOTRESET" => "2", 
			"NOWAIT" => "2", 
			"NULL" => "2", 
			"NUMBERLIST" => "2", 
			"OFF" => "2", 
			"OK" => "2", 
			"ON" => "2", 
			"ONLYDIR" => "2", 
			"OS" => "2", 
			"OSMAJOR" => "2", 
			"OSMINOR" => "2", 
			"OTHER_FAILURE" => "2", 
			"OUT_OF_DISK_SPACE" => "2", 
			"PARALLEL" => "2", 
			"PARTIAL" => "2", 
			"PATH" => "2", 
			"PATH_EXISTS" => "2", 
			"PAUSE" => "2", 
			"PERSONAL" => "2", 
			"PROFSTRING" => "2", 
			"PROGMAN" => "2", 
			"PROGRAMFILES" => "2", 
			"RAM_DRIVE" => "2", 
			"REAL" => "2", 
			"RECORDMODE" => "2", 
			"RED" => "2", 
			"REGDB_APPPATH" => "2", 
			"REGDB_APPPATH_DEFAULT" => "2", 
			"REGDB_BINARY" => "2", 
			"REGDB_ERR_CONNECTIONEXISTS" => "2", 
			"REGDB_ERR_CORRUPTEDREGISTRY" => "2", 
			"REGDB_ERR_FILECLOSE" => "2", 
			"REGDB_ERR_FILENOTFOUND" => "2", 
			"REGDB_ERR_FILEOPEN" => "2", 
			"REGDB_ERR_FILEREAD" => "2", 
			"REGDB_ERR_INITIALIZATION" => "2", 
			"REGDB_ERR_INVALIDFORMAT" => "2", 
			"REGDB_ERR_INVALIDHANDLE" => "2", 
			"REGDB_ERR_INVALIDNAME" => "2", 
			"REGDB_ERR_INVALIDPLATFORM" => "2", 
			"REGDB_ERR_OUTOFMEMORY" => "2", 
			"REGDB_ERR_REGISTRY" => "2", 
			"REGDB_KEYS" => "2", 
			"REGDB_NAMES" => "2", 
			"REGDB_NUMBER" => "2", 
			"REGDB_STRING" => "2", 
			"REGDB_STRING_EXPAND" => "2", 
			"REGDB_STRING_MULTI" => "2", 
			"REGDB_UNINSTALL_NAME" => "2", 
			"REGKEY_CLASSES_ROOT" => "2", 
			"REGKEY_CURRENT_USER" => "2", 
			"REGKEY_LOCAL_MACHINE" => "2", 
			"REGKEY_USERS" => "2", 
			"REMOTE_DRIVE" => "2", 
			"REMOVE" => "2", 
			"REMOVEABLE_DRIVE" => "2", 
			"REPLACE" => "2", 
			"REPLACE_ITEM" => "2", 
			"RESET" => "2", 
			"RESTART" => "2", 
			"ROOT" => "2", 
			"ROTATE" => "2", 
			"RUN_MAXIMIZED" => "2", 
			"RUN_MINIMIZED" => "2", 
			"RUN_SEPARATEMEMORY" => "2", 
			"SELECTFOLDER" => "2", 
			"SELFREGISTER" => "2", 
			"SELFREGISTERBATCH" => "2", 
			"SELFREGISTRATIONPROCESS" => "2", 
			"SERIAL" => "2", 
			"SET" => "2", 
			"SETUPTYPE" => "2", 
			"SETUPTYPE_INFO_DESCRIPTION" => "2", 
			"SETUPTYPE_INFO_DISPLAYNAME" => "2", 
			"SEVERE" => "2", 
			"SHARE" => "2", 
			"SHAREDFILE" => "2", 
			"SHELL_OBJECT_FOLDER" => "2", 
			"SILENTMODE" => "2", 
			"SPLITCOMPRESS" => "2", 
			"SPLITCOPY" => "2", 
			"SRCTARGETDIR" => "2", 
			"STANDARD" => "2", 
			"STATUS" => "2", 
			"STATUS95" => "2", 
			"STATUSBAR" => "2", 
			"STATUSDLG" => "2", 
			"STATUSEX" => "2", 
			"STATUSOLD" => "2", 
			"STRINGLIST" => "2", 
			"STYLE_BOLD" => "2", 
			"STYLE_ITALIC" => "2", 
			"STYLE_NORMAL" => "2", 
			"STYLE_SHADOW" => "2", 
			"STYLE_UNDERLINE" => "2", 
			"SW_HIDE" => "2", 
			"SW_MAXIMIZE" => "2", 
			"SW_MINIMIZE" => "2", 
			"SW_NORMAL" => "2", 
			"SW_RESTORE" => "2", 
			"SW_SHOW" => "2", 
			"SW_SHOWMAXIMIZED" => "2", 
			"SW_SHOWMINIMIZED" => "2", 
			"SW_SHOWMINNOACTIVE" => "2", 
			"SW_SHOWNA" => "2", 
			"SW_SHOWNOACTIVATE" => "2", 
			"SW_SHOWNORMAL" => "2", 
			"SYS_BOOTMACHINE" => "2", 
			"SYS_BOOTWIN" => "2", 
			"SYS_BOOTWIN_INSTALL" => "2", 
			"SYS_RESTART" => "2", 
			"SYS_SHUTDOWN" => "2", 
			"SYS_TODOS" => "2", 
			"SELECTED_LANGUAGE" => "2", 
			"SHELL_OBJECT_LANGUAGE" => "2", 
			"SRCDIR" => "2", 
			"SRCDISK" => "2", 
			"SUPPORTDIR" => "2", 
			"TEXT" => "2", 
			"TILED" => "2", 
			"TIME" => "2", 
			"TRUE" => "2", 
			"TYPICAL" => "2", 
			"TARGETDIR" => "2", 
			"TARGETDISK" => "2", 
			"UPPER_LEFT" => "2", 
			"UPPER_RIGHT" => "2", 
			"USER_ADMINISTRATOR" => "2", 
			"UNINST" => "2", 
			"VALID_PATH" => "2", 
			"VARIABLE_LEFT" => "2", 
			"VARIABLE_UNDEFINED" => "2", 
			"VER_DLL_NOT_FOUND" => "2", 
			"VER_UPDATE_ALWAYS" => "2", 
			"VER_UPDATE_COND" => "2", 
			"VERSION" => "2", 
			"VIDEO" => "2", 
			"VOLUMELABEL" => "2", 
			"WAIT" => "2", 
			"WARNING" => "2", 
			"WELCOME" => "2", 
			"WHITE" => "2", 
			"WIN32SINSTALLED" => "2", 
			"WIN32SMAJOR" => "2", 
			"WIN32SMINOR" => "2", 
			"WINDOWS_SHARED" => "2", 
			"WINMAJOR" => "2", 
			"WINMINOR" => "2", 
			"WINDIR" => "2", 
			"WINDISK" => "2", 
			"WINSYSDIR" => "2", 
			"WINSYSDISK" => "2", 
			"XCOPY_DATETIME" => "2", 
			"YELLOW" => "2", 
			"YES" => "2", 
			"AskDestPath" => "3", 
			"AskOptions" => "3", 
			"AskPath" => "3", 
			"AskText" => "3", 
			"AskYesNo" => "3", 
			"AppCommand" => "3", 
			"AddProfString" => "3", 
			"AddFolderIcon" => "3", 
			"BatchAdd" => "3", 
			"BatchDeleteEx" => "3", 
			"BatchFileLoad" => "3", 
			"BatchFileSave" => "3", 
			"BatchFind" => "3", 
			"BatchGetFileName" => "3", 
			"BatchMoveEx" => "3", 
			"BatchSetFileName" => "3", 
			"ComponentDialog" => "3", 
			"ComponentAddItem" => "3", 
			"ComponentCompareSizeRequired" => "3", 
			"ComponentError" => "3", 
			"ComponentFileEnum" => "3", 
			"ComponentFileInfo" => "3", 
			"ComponentFilterLanguage" => "3", 
			"ComponentFilterOS" => "3", 
			"ComponentGetData" => "3", 
			"ComponentGetItemSize" => "3", 
			"ComponentInitialize" => "3", 
			"ComponentIsItemSelected" => "3", 
			"ComponentListItems" => "3", 
			"ComponentMoveData" => "3", 
			"ComponentSelectItem" => "3", 
			"ComponentSetData" => "3", 
			"ComponentSetTarget" => "3", 
			"ComponentSetupTypeEnum" => "3", 
			"ComponentSetupTypeGetData" => "3", 
			"ComponentSetupTypeSet" => "3", 
			"ComponentTotalSize" => "3", 
			"ComponentValidate" => "3", 
			"ConfigAdd" => "3", 
			"ConfigDelete" => "3", 
			"ConfigFileLoad" => "3", 
			"ConfigFileSave" => "3", 
			"ConfigFind" => "3", 
			"ConfigGetFileName" => "3", 
			"ConfigGetInt" => "3", 
			"ConfigMove" => "3", 
			"ConfigSetFileName" => "3", 
			"ConfigSetInt" => "3", 
			"CmdGetHwndDlg" => "3", 
			"CtrlClear" => "3", 
			"CtrlDir" => "3", 
			"CtrlGetCurSel" => "3", 
			"CtrlGetMLEText" => "3", 
			"CtrlGetMultCurSel" => "3", 
			"CtrlGetState" => "3", 
			"CtrlGetSubCommand" => "3", 
			"CtrlGetText" => "3", 
			"CtrlPGroups" => "3", 
			"CtrlSelectText" => "3", 
			"CtrlSetCurSel" => "3", 
			"CtrlSetFont" => "3", 
			"CtrlSetList" => "3", 
			"CtrlSetMLEText" => "3", 
			"CtrlSetMultCurSel" => "3", 
			"CtrlSetState" => "3", 
			"CtrlSetText" => "3", 
			"CallDLLFx" => "3", 
			"ChangeDirectory" => "3", 
			"CloseFile" => "3", 
			"CopyFile" => "3", 
			"CreateDir" => "3", 
			"CreateFile" => "3", 
			"CreateRegistrySet" => "3", 
			"CommitSharedFiles" => "3", 
			"CreateProgramFolder" => "3", 
			"CreateShellObjects" => "3", 
			"CopyBytes" => "3", 
			"DefineDialog" => "3", 
			"Delay" => "3", 
			"DeleteDir" => "3", 
			"DeleteFile" => "3", 
			"Do" => "3", 
			"DoInstall" => "3", 
			"DeinstallSetReference" => "3", 
			"DeinstallStart" => "3", 
			"DialogSetInfo" => "3", 
			"DeleteFolderIcon" => "3", 
			"DeleteProgramFolder" => "3", 
			"Disable" => "3", 
			"EzBatchAddPath" => "3", 
			"EzBatchAddString" => "3", 
			"ExBatchReplace" => "3", 
			"EnterDisk" => "3", 
			"EzConfigAddDriver" => "3", 
			"EzConfigAddString" => "3", 
			"EzConfigGetValue" => "3", 
			"EzConfigSetValue" => "3", 
			"EndDialog" => "3", 
			"EzDefineDialog" => "3", 
			"ExistsDir" => "3", 
			"ExistsDisk" => "3", 
			"ExitProgMan" => "3", 
			"Enable" => "3", 
			"EzBatchReplace" => "3", 
			"FileCompare" => "3", 
			"FileDeleteLine" => "3", 
			"FileGrep" => "3", 
			"FileInsertLine" => "3", 
			"FindAllDirs" => "3", 
			"FindAllFiles" => "3", 
			"FindFile" => "3", 
			"FindWindow" => "3", 
			"GetFileInfo" => "3", 
			"GetLine" => "3", 
			"GetFont" => "3", 
			"GetDiskSpace" => "3", 
			"GetEnvVar" => "3", 
			"GetExtents" => "3", 
			"GetMemFree" => "3", 
			"GetMode" => "3", 
			"GetSystemInfo" => "3", 
			"GetValidDrivesList" => "3", 
			"GetWindowHandle" => "3", 
			"GetProfInt" => "3", 
			"GetProfString" => "3", 
			"GetFolderNameList" => "3", 
			"GetGroupNameList" => "3", 
			"GetItemNameList" => "3", 
			"GetDir" => "3", 
			"GetDisk" => "3", 
			"HIWORD" => "3", 
			"Handler" => "3", 
			"Is" => "3", 
			"ISCompareServicePack" => "3", 
			"InstallationInfo" => "3", 
			"LOWORD" => "3", 
			"LaunchApp" => "3", 
			"LaunchAppAndWait" => "3", 
			"ListAddItem" => "3", 
			"ListAddString" => "3", 
			"ListCount" => "3", 
			"ListCreate" => "3", 
			"ListCurrentItem" => "3", 
			"ListCurrentString" => "3", 
			"ListDeleteItem" => "3", 
			"ListDeleteString" => "3", 
			"ListDestroy" => "3", 
			"ListFindItem" => "3", 
			"ListFindString" => "3", 
			"ListGetFirstItem" => "3", 
			"ListGetFirstString" => "3", 
			"ListGetNextItem" => "3", 
			"ListGetNextString" => "3", 
			"ListReadFromFile" => "3", 
			"ListSetCurrentItem" => "3", 
			"ListSetCurrentString" => "3", 
			"ListSetIndex" => "3", 
			"ListWriteToFile" => "3", 
			"LongPathFromShortPath" => "3", 
			"LongPathToQuote" => "3", 
			"LongPathToShortPath" => "3", 
			"MessageBox" => "3", 
			"MessageBeep" => "3", 
			"NumToStr" => "3", 
			"OpenFile" => "3", 
			"OpenFileMode" => "3", 
			"PathAdd" => "3", 
			"PathDelete" => "3", 
			"PathFind" => "3", 
			"PathGet" => "3", 
			"PathMove" => "3", 
			"PathSet" => "3", 
			"ProgDefGroupType" => "3", 
			"ParsePath" => "3", 
			"PlaceBitmap" => "3", 
			"PlaceWindow" => "3", 
			"PlayMMedia" => "3", 
			"QueryProgGroup" => "3", 
			"QueryProgItem" => "3", 
			"QueryShellMgr" => "3", 
			"RebootDialog" => "3", 
			"ReleaseDialog" => "3", 
			"ReadBytes" => "3", 
			"RenameFile" => "3", 
			"ReplaceProfString" => "3", 
			"ReloadProgGroup" => "3", 
			"ReplaceFolderIcon" => "3", 
			"RGB" => "3", 
			"RegDBConnectRegistry" => "3", 
			"RegDBCreateKeyEx" => "3", 
			"RegDBDeleteKey" => "3", 
			"RegDBDeleteValue" => "3", 
			"RegDBDisConnectRegistry" => "3", 
			"RegDBGetAppInfo" => "3", 
			"RegDBGetItem" => "3", 
			"RegDBGetKeyValueEx" => "3", 
			"RegDBKeyExist" => "3", 
			"RegDBQueryKey" => "3", 
			"RegDBSetAppInfo" => "3", 
			"RegDBSetDefaultRoot" => "3", 
			"RegDBSetItem" => "3", 
			"RegDBSetKeyValueEx" => "3", 
			"SeekBytes" => "3", 
			"SelectDir" => "3", 
			"SetFileInfo" => "3", 
			"SelectFolder" => "3", 
			"SetupType" => "3", 
			"SprintfBox" => "3", 
			"SdSetupType" => "3", 
			"SdSetupTypeEx" => "3", 
			"SdMakeName" => "3", 
			"SilentReadData" => "3", 
			"SilentWriteData" => "3", 
			"SendMessage" => "3", 
			"Sprintf" => "3", 
			"System" => "3", 
			"SdAskDestPath" => "3", 
			"SdAskOptions" => "3", 
			"SdAskOptionsList" => "3", 
			"SdBitmap" => "3", 
			"SdComponentDialog" => "3", 
			"SdComponentDialog2" => "3", 
			"SdComponentDialogAdv" => "3", 
			"SdComponentMult" => "3", 
			"SdConfirmNewDir" => "3", 
			"SdConfirmRegistration" => "3", 
			"SdDisplayTopics" => "3", 
			"SdFinish" => "3", 
			"SdFinishReboot" => "3", 
			"SdInit" => "3", 
			"SdLicense" => "3", 
			"SdOptionsButtons" => "3", 
			"SdProductName" => "3", 
			"SdRegisterUser" => "3", 
			"SdRegisterUserEx" => "3", 
			"SdSelectFolder" => "3", 
			"SdShowAnyDialog" => "3", 
			"SdShowDlgEdit1" => "3", 
			"SdShowDlgEdit2" => "3", 
			"SdShowDlgEdit3" => "3", 
			"SdShowFileMods" => "3", 
			"SdShowInfoList" => "3", 
			"SdShowMsg" => "3", 
			"SdStartCopy" => "3", 
			"SdWelcome" => "3", 
			"ShowGroup" => "3", 
			"ShowProgamFolder" => "3", 
			"SetColor" => "3", 
			"SetDialogTitle" => "3", 
			"SetDisplayEffect" => "3", 
			"SetErrorMsg" => "3", 
			"SetErrorTitle" => "3", 
			"SetFont" => "3", 
			"SetStatusWindow" => "3", 
			"SetTitle" => "3", 
			"SizeWindow" => "3", 
			"StatusUpdate" => "3", 
			"StrCompare" => "3", 
			"StrFind" => "3", 
			"StrGetTokens" => "3", 
			"StrLength" => "3", 
			"StrRemoveLastSlash" => "3", 
			"StrSub" => "3", 
			"StrToLower" => "3", 
			"StrToNum" => "3", 
			"StrToUpper" => "3", 
			"ShowProgramFolder" => "3", 
			"UnUseDLL" => "3", 
			"UseDLL" => "3", 
			"VarRestore" => "3", 
			"VarSave" => "3", 
			"VerUpdateFile" => "3", 
			"VerCompare" => "3", 
			"VerFindFileVersion" => "3", 
			"VerGetFileVersion" => "3", 
			"VerSearchAndUpdateFile" => "3", 
			"Welcome" => "3", 
			"WaitOnDialog" => "3", 
			"WriteBytes" => "3", 
			"WriteLine" => "3", 
			"WriteProfString" => "3", 
			"XCopyFile" => "3", 
			"+" => "4", 
			"-" => "4", 
			"=" => "4", 
			"//" => "4", 
			"/" => "4", 
			"%" => "4", 
			"&" => "4", 
			">" => "4", 
			"<" => "4", 
			"^" => "4", 
			"!" => "4", 
			"|" => "4");

// Special extensions

// Each category can specify a PHP function that returns an altered
// version of the keyword.
        
        

$this->linkscripts    	= array(
			"1" => "donothing", 
			"2" => "donothing", 
			"3" => "donothing", 
			"4" => "donothing");
}


function donothing($keywordin)
{
	return $keywordin;
}

}?>
