<?php
global $BEAUT_PATH;
if (!isset ($BEAUT_PATH)) return;

require_once($BEAUT_PATH.'/Beautifier/HFile.php');

class HFile_php3 extends HFile{

 function HFile_php3(){

    $this->HFile();	


/*************************************/
// Beautifier Highlighting Configuration File 
// PHP3
/*************************************/
// Flags

$this->nocase            	= "1";
$this->notrim            	= "0";
$this->perl              	= "0";

// Colours

$this->colours        	= array("purple", "blue", "green");
$this->quotecolour       	= "blue";
$this->blockcommentcolour	= "green";
$this->linecommentcolour 	= "green";

// Indent Strings

$this->indent            	= array("{");
$this->unindent          	= array("}");

$this->selecton			= "<!";
$this->selectoff		= "!>";

// String characters and delimiters

$this->stringchars       	= array("\"", "'");
$this->delimiters        	= array("~", "!", "@", "%", "^", "&", "*", "(", ")", "+", "=", "|", "\\", "{", "}", "[", "]", ":", ";", "\"", "'", "<", ">", " ", ",", "	", ".", "?");
$this->escchar           	= "\\";

// Comment settings

$this->linecommenton     	= array("//", "#");
$this->blockcommenton    	= array("/*");
$this->blockcommentoff   	= array("*/");

// Keywords (keyword mapping to colour number)

$this->keywords          	= array(
			"echo" => "2", 
			"else" => "1", 
			"for" => "1", 
			"global" => "1", 
			"if" => "1", 
			"return" => "1", 
			"while" => "1", 
			"include" => "1",
			"require" => "1",
			"require_once" => "1",
			"Abs" => "2", 
			"Acos" => "2", 
			"ada_afetch" => "2", 
			"ada_autocommit" => "2", 
			"ada_close" => "2", 
			"ada_commit" => "2", 
			"ada_connect" => "2", 
			"ada_exec" => "2", 
			"ada_fetchrow" => "2", 
			"ada_fieldname" => "2", 
			"ada_fieldnum" => "2", 
			"ada_fieldtype" => "2", 
			"ada_freeresult" => "2", 
			"ada_numfields" => "2", 
			"ada_numrows" => "2", 
			"ada_result" => "2", 
			"ada_resultall" => "2", 
			"ada_rollback" => "2", 
			"AddCSlashes" => "2", 
			"AddSlashes" => "2", 
			"apache_lookup_uri" => "2", 
			"apache_note" => "2", 
			"array" => "2", 
			"array_count_values" => "2", 
			"array_flip" => "2", 
			"array_keys" => "2", 
			"array_merge" => "2", 
			"array_pad" => "2", 
			"array_pop" => "2", 
			"array_push" => "2", 
			"array_reverse" => "2", 
			"array_shift" => "2", 
			"array_slice" => "2", 
			"array_splice" => "2", 
			"array_unshift" => "2", 
			"array_values" => "2", 
			"array_walk" => "2", 
			"arsort" => "2", 
			"Asin" => "2", 
			"asort" => "2", 
			"aspell_check" => "2", 
			"aspell_check-raw" => "2", 
			"aspell_new" => "2", 
			"aspell_suggest" => "2", 
			"Atan" => "2", 
			"Atan2" => "2", 
			"base64_decode" => "2", 
			"base64_encode" => "2", 
			"basename" => "2", 
			"base_convert" => "2", 
			"bcadd" => "2", 
			"bccomp" => "2", 
			"bcdiv" => "2", 
			"bcmod" => "2", 
			"bcmul" => "2", 
			"bcpow" => "2", 
			"bcscale" => "2", 
			"bcsqrt" => "2", 
			"bcsub" => "2", 
			"bin2hex" => "2", 
			"BinDec" => "2", 
			"Ceil" => "2", 
			"chdir" => "2", 
			"checkdate" => "2", 
			"checkdnsrr" => "2", 
			"chgrp" => "2", 
			"chmod" => "2", 
			"Chop" => "2", 
			"chown" => "2", 
			"Chr" => "2", 
			"chunk_split" => "2", 
			"clearstatcache" => "2", 
			"closedir" => "2", 
			"closelog" => "2", 
			"connection_aborted" => "2", 
			"connection_status" => "2", 
			"connection_timeout" => "2", 
			"contained" => "2", 
			"convert_cyr_string" => "2", 
			"copy" => "2", 
			"Cos" => "2", 
			"count" => "2", 
			"cpdf_add_annotation" => "2", 
			"cpdf_add_outline" => "2", 
			"cpdf_arc" => "2", 
			"cpdf_begin_text" => "2", 
			"cpdf_circle" => "2", 
			"cpdf_clip" => "2", 
			"cpdf_close" => "2", 
			"cpdf_closepath" => "2", 
			"cpdf_closepath_fill_stroke" => "2", 
			"cpdf_closepath_stroke" => "2", 
			"cpdf_continue_text" => "2", 
			"cpdf_curveto" => "2", 
			"cpdf_end_text" => "2", 
			"cpdf_fill" => "2", 
			"cpdf_fill_stroke" => "2", 
			"cpdf_finalize" => "2", 
			"cpdf_finalize_page" => "2", 
			"cpdf_import_jpeg" => "2", 
			"cpdf_lineto" => "2", 
			"cpdf_moveto" => "2", 
			"cpdf_open" => "2", 
			"cpdf_output_buffer" => "2", 
			"cpdf_page_init" => "2", 
			"cpdf_place_inline_image" => "2", 
			"cpdf_rect" => "2", 
			"cpdf_restore" => "2", 
			"cpdf_rlineto" => "2", 
			"cpdf_rmoveto" => "2", 
			"cpdf_rotate" => "2", 
			"cpdf_save" => "2", 
			"cpdf_save_to_file" => "2", 
			"cpdf_scale" => "2", 
			"cpdf_setdash" => "2", 
			"cpdf_setflat" => "2", 
			"cpdf_setgray" => "2", 
			"cpdf_setgray_fill" => "2", 
			"cpdf_setgray_stroke" => "2", 
			"cpdf_setlinecap" => "2", 
			"cpdf_setlinejoin" => "2", 
			"cpdf_setlinewidth" => "2", 
			"cpdf_setmiterlimit" => "2", 
			"cpdf_setrgbcolor" => "2", 
			"cpdf_setrgbcolor_fill" => "2", 
			"cpdf_setrgbcolor_stroke" => "2", 
			"cpdf_set_char_spacing" => "2", 
			"cpdf_set_creator" => "2", 
			"cpdf_set_current_page" => "2", 
			"cpdf_set_font" => "2", 
			"cpdf_set_horiz_scaling" => "2", 
			"cpdf_set_keywords" => "2", 
			"cpdf_set_leading" => "2", 
			"cpdf_set_page_animation" => "2", 
			"cpdf_set_subject" => "2", 
			"cpdf_set_text_matrix" => "2", 
			"cpdf_set_text_pos" => "2", 
			"cpdf_set_text_rendering" => "2", 
			"cpdf_set_text_rise" => "2", 
			"cpdf_set_title" => "2", 
			"cpdf_set_word_spacing" => "2", 
			"cpdf_show" => "2", 
			"cpdf_show_xy" => "2", 
			"cpdf_stringwidth" => "2", 
			"cpdf_stroke" => "2", 
			"cpdf_text" => "2", 
			"cpdf_translate" => "2", 
			"crypt" => "2", 
			"current" => "2", 
			"date" => "2", 
			"dbase_add_record" => "2", 
			"dbase_close" => "2", 
			"dbase_create" => "2", 
			"dbase_delete_record" => "2", 
			"dbase_get_record" => "2", 
			"dbase_get_record_with_names" => "2", 
			"dbase_numfields" => "2", 
			"dbase_numrecords" => "2", 
			"dbase_open" => "2", 
			"dbase_pack" => "2", 
			"dbase_replace_record" => "2", 
			"dblist" => "2", 
			"dbmclose" => "2", 
			"dbmdelete" => "2", 
			"dbmexists" => "2", 
			"dbmfetch" => "2", 
			"dbmfirstkey" => "2", 
			"dbminsert" => "2", 
			"dbmnextkey" => "2", 
			"dbmopen" => "2", 
			"dbmreplace" => "2", 
			"debugger_off" => "2", 
			"debugger_on" => "2", 
			"DecBin" => "2", 
			"DecHex" => "2", 
			"DecOct" => "2", 
			"define" => "2", 
			"defined" => "2", 
			"delete" => "2", 
			"die" => "2", 
			"dir" => "2", 
			"dirname" => "2", 
			"diskfreespace" => "2", 
			"dl" => "2", 
			"doubleval" => "2", 
			"each" => "2", 
			"easter_date" => "2", 
			"easter_days" => "2", 
			"empty" => "2", 
			"end" => "2", 
			"endwhile" => "2", 
			"ereg" => "2", 
			"eregi" => "2", 
			"eregi_replace" => "2", 
			"ereg_replace" => "2", 
			"error_log" => "2", 
			"error_reporting" => "2", 
			"escapeshellcmd" => "2", 
			"eval" => "2", 
			"exec" => "2", 
			"exit" => "2", 
			"Exp" => "2", 
			"explode" => "2", 
			"extension_loaded" => "2", 
			"extract" => "2", 
			"fclose" => "2", 
			"fdf_close" => "2", 
			"fdf_create" => "2", 
			"fdf_get_file" => "2", 
			"fdf_get_status" => "2", 
			"fdf_get_value" => "2", 
			"fdf_next_field_name" => "2", 
			"fdf_open" => "2", 
			"fdf_save" => "2", 
			"fdf_set_ap" => "2", 
			"fdf_set_file" => "2", 
			"fdf_set_status" => "2", 
			"fdf_set_value" => "2", 
			"feof" => "2", 
			"fgetc" => "2", 
			"fgetcsv" => "2", 
			"fgets" => "2", 
			"fgetss" => "2", 
			"file" => "2", 
			"fileatime" => "2", 
			"filectime" => "2", 
			"filegroup" => "2", 
			"fileinode" => "2", 
			"filemtime" => "2", 
			"fileowner" => "2", 
			"fileperms" => "2", 
			"filepro" => "2", 
			"filepro_fieldcount" => "2", 
			"filepro_fieldname" => "2", 
			"filepro_fieldtype" => "2", 
			"filepro_fieldwidth" => "2", 
			"filepro_retrieve" => "2", 
			"filepro_rowcount" => "2", 
			"filesize" => "2", 
			"filetype" => "2", 
			"file_exists" => "2", 
			"flock" => "2", 
			"Floor" => "2", 
			"flush" => "2", 
			"fopen" => "2", 
			"fpassthru" => "2", 
			"fputs" => "2", 
			"fread" => "2", 
			"FrenchToJD" => "2", 
			"fseek" => "2", 
			"fsockopen" => "2", 
			"ftell" => "2", 
			"ftp_cdup" => "2", 
			"ftp_chdir" => "2", 
			"ftp_connect" => "2", 
			"ftp_delete" => "2", 
			"ftp_fget" => "2", 
			"ftp_fput" => "2", 
			"ftp_get" => "2", 
			"ftp_login" => "2", 
			"ftp_mdtm" => "2", 
			"ftp_mkdir" => "2", 
			"ftp_nlist" => "2", 
			"ftp_pasv" => "2", 
			"ftp_put" => "2", 
			"ftp_pwd" => "2", 
			"ftp_quit" => "2", 
			"ftp_rawlist" => "2", 
			"ftp_rename" => "2", 
			"ftp_rmdir" => "2", 
			"ftp_size" => "2", 
			"ftp_systype" => "2", 
			"function_exists" => "2", 
			"func_get_arg" => "2", 
			"func_get_args" => "2", 
			"func_num_args" => "2", 
			"fwrite" => "2", 
			"getallheaders" => "2", 
			"getdate" => "2", 
			"getenv" => "2", 
			"gethostbyaddr" => "2", 
			"gethostbyname" => "2", 
			"gethostbynamel" => "2", 
			"GetImageSize" => "2", 
			"getlastmod" => "2", 
			"getmxrr" => "2", 
			"getmyinode" => "2", 
			"getmypid" => "2", 
			"getmyuid" => "2", 
			"getprotobyname" => "2", 
			"getprotobynumber" => "2", 
			"getrandmax" => "2", 
			"getrusage" => "2", 
			"getservbyname" => "2", 
			"getservbyport" => "2", 
			"gettimeofday" => "2", 
			"gettype" => "2", 
			"get_browser" => "2", 
			"get_cfg_var" => "2", 
			"get_current_user" => "2", 
			"get_html_translation_table" => "2", 
			"get_magic_quotes_gpc" => "2", 
			"get_magic_quotes_runtime" => "2", 
			"get_meta_tags" => "2", 
			"gmdate" => "2", 
			"gmmktime" => "2", 
			"gmstrftime" => "2", 
			"GregorianToJD" => "2", 
			"gzclose" => "2", 
			"gzeof" => "2", 
			"gzfile" => "2", 
			"gzgetc" => "2", 
			"gzgets" => "2", 
			"gzgetss" => "2", 
			"gzopen" => "2", 
			"gzpassthru" => "2", 
			"gzputs" => "2", 
			"gzread" => "2", 
			"gzrewind" => "2", 
			"gzseek" => "2", 
			"gztell" => "2", 
			"gzwrite" => "2", 
			"header" => "2", 
			"HexDec" => "2", 
			"htmlentities" => "2", 
			"htmlspecialchars" => "2", 
			"hw_Array2Objrec" => "2", 
			"hw_Children" => "2", 
			"hw_ChildrenObj" => "2", 
			"hw_Close" => "2", 
			"hw_Connect" => "2", 
			"hw_Cp" => "2", 
			"hw_Deleteobject" => "2", 
			"hw_DocByAnchor" => "2", 
			"hw_DocByAnchorObj" => "2", 
			"hw_DocumentAttributes" => "2", 
			"hw_DocumentBodyTag" => "2", 
			"hw_DocumentContent" => "2", 
			"hw_DocumentSetContent" => "2", 
			"hw_DocumentSize" => "2", 
			"hw_EditText" => "2", 
			"hw_Error" => "2", 
			"hw_ErrorMsg" => "2", 
			"hw_Free_Document" => "2", 
			"hw_GetAnchors" => "2", 
			"hw_GetAnchorsObj" => "2", 
			"hw_GetAndLock" => "2", 
			"hw_GetChildColl" => "2", 
			"hw_GetChildCollObj" => "2", 
			"hw_GetChildDocColl" => "2", 
			"hw_GetChildDocCollObj" => "2", 
			"hw_GetObject" => "2", 
			"hw_GetObjectByQuery" => "2", 
			"hw_GetObjectByQueryColl" => "2", 
			"hw_GetObjectByQueryCollObj" => "2", 
			"hw_GetObjectByQueryObj" => "2", 
			"hw_GetParents" => "2", 
			"hw_GetParentsObj" => "2", 
			"hw_GetRemote" => "2", 
			"hw_GetRemoteChildren" => "2", 
			"hw_GetSrcByDestObj" => "2", 
			"hw_GetText" => "2", 
			"hw_Identify" => "2", 
			"hw_InCollections" => "2", 
			"hw_Info" => "2", 
			"hw_InsColl" => "2", 
			"hw_InsDoc" => "2", 
			"hw_InsertDocument" => "2", 
			"hw_InsertObject" => "2", 
			"hw_mapid" => "2", 
			"hw_Modifyobject" => "2", 
			"hw_Mv" => "2", 
			"hw_New_Document" => "2", 
			"hw_Objrec2Array" => "2", 
			"hw_OutputDocument" => "2", 
			"hw_pConnect" => "2", 
			"hw_PipeDocument" => "2", 
			"hw_Root" => "2", 
			"hw_Unlock" => "2", 
			"hw_Username" => "2", 
			"hw_Who" => "2", 
			"ibase_bind" => "2", 
			"ibase_close" => "2", 
			"ibase_connect" => "2", 
			"ibase_execute" => "2", 
			"ibase_fetch_row" => "2", 
			"ibase_free_query" => "2", 
			"ibase_free_result" => "2", 
			"ibase_pconnect" => "2", 
			"ibase_prepare" => "2", 
			"ibase_query" => "2", 
			"ibase_timefmt" => "2", 
			"ifxus_close_slob" => "2", 
			"ifxus_create_slob" => "2", 
			"ifxus_open_slob" => "2", 
			"ifxus_read_slob" => "2", 
			"ifxus_seek_slob" => "2", 
			"ifxus_tell_slob" => "2", 
			"ifxus_write_slob" => "2", 
			"ifx_affected_rows" => "2", 
			"ifx_blobinfile_mode" => "2", 
			"ifx_byteasvarchar" => "2", 
			"ifx_close" => "2", 
			"ifx_connect" => "2", 
			"ifx_copy_blob" => "2", 
			"ifx_create_blob" => "2", 
			"ifx_create_char" => "2", 
			"ifx_do" => "2", 
			"ifx_error" => "2", 
			"ifx_errormsg" => "2", 
			"ifx_fetch_row" => "2", 
			"ifx_fieldproperties" => "2", 
			"ifx_fieldtypes" => "2", 
			"ifx_free_blob" => "2", 
			"ifx_free_char" => "2", 
			"ifx_free_result" => "2", 
			"ifx_free_slob" => "2", 
			"ifx_getsqlca" => "2", 
			"ifx_get_blob" => "2", 
			"ifx_get_char" => "2", 
			"ifx_htmltbl_result" => "2", 
			"ifx_nullformat" => "2", 
			"ifx_num_fields" => "2", 
			"ifx_num_rows" => "2", 
			"ifx_pconnect" => "2", 
			"ifx_prepare" => "2", 
			"ifx_query" => "2", 
			"ifx_textasvarchar" => "2", 
			"ifx_update_blob" => "2", 
			"ifx_update_char" => "2", 
			"ignore_user_abort" => "2", 
			"ImageArc" => "2", 
			"ImageChar" => "2", 
			"ImageCharUp" => "2", 
			"ImageColorAllocate" => "2", 
			"ImageColorAt" => "2", 
			"ImageColorClosest" => "2", 
			"ImageColorExact" => "2", 
			"ImageColorResolve" => "2", 
			"ImageColorSet" => "2", 
			"ImageColorsForIndex" => "2", 
			"ImageColorsTotal" => "2", 
			"ImageColorTransparent" => "2", 
			"ImageCopyResized" => "2", 
			"ImageCreate" => "2", 
			"ImageCreateFromGif" => "2", 
			"ImageDashedLine" => "2", 
			"ImageDestroy" => "2", 
			"ImageFill" => "2", 
			"ImageFilledPolygon" => "2", 
			"ImageFilledRectangle" => "2", 
			"ImageFillToBorder" => "2", 
			"ImageFontHeight" => "2", 
			"ImageFontWidth" => "2", 
			"ImageGif" => "2", 
			"ImageInterlace" => "2", 
			"ImageLine" => "2", 
			"ImageLoadFont" => "2", 
			"ImagePolygon" => "2", 
			"ImagePSBBox" => "2", 
			"ImagePSEncodeFont" => "2", 
			"ImagePSFreeFont" => "2", 
			"ImagePSLoadFont" => "2", 
			"ImagePSText" => "2", 
			"ImageRectangle" => "2", 
			"ImageSetPixel" => "2", 
			"ImageString" => "2", 
			"ImageStringUp" => "2", 
			"ImageSX" => "2", 
			"ImageSY" => "2", 
			"ImageTTFBBox" => "2", 
			"ImageTTFText" => "2", 
			"imap_8bit" => "2", 
			"imap_alerts" => "2", 
			"imap_append" => "2", 
			"imap_base64" => "2", 
			"imap_binary" => "2", 
			"imap_body" => "2", 
			"imap_check" => "2", 
			"imap_clearflag_full" => "2", 
			"imap_close" => "2", 
			"imap_createmailbox" => "2", 
			"imap_delete" => "2", 
			"imap_deletemailbox" => "2", 
			"imap_errors" => "2", 
			"imap_expunge" => "2", 
			"imap_fetchbody" => "2", 
			"imap_fetchheader" => "2", 
			"imap_fetchstructure" => "2", 
			"imap_getmailboxes" => "2", 
			"imap_getsubscribed" => "2", 
			"imap_header" => "2", 
			"imap_headers" => "2", 
			"imap_last_error" => "2", 
			"imap_listmailbox" => "2", 
			"imap_listsubscribed" => "2", 
			"imap_mailboxmsginfo" => "2", 
			"imap_mail_copy" => "2", 
			"imap_mail_move" => "2", 
			"imap_msgno" => "2", 
			"imap_num_msg" => "2", 
			"imap_num_recent" => "2", 
			"imap_open" => "2", 
			"imap_ping" => "2", 
			"imap_qprint" => "2", 
			"imap_renamemailbox" => "2", 
			"imap_reopen" => "2", 
			"imap_rfc822_parse_adrlist" => "2", 
			"imap_rfc822_write_address" => "2", 
			"imap_scanmailbox" => "2", 
			"imap_search" => "2", 
			"imap_setflag_full" => "2", 
			"imap_sort" => "2", 
			"imap_status" => "2", 
			"imap_subscribe" => "2", 
			"imap_uid" => "2", 
			"imap_undelete" => "2", 
			"imap_unsubscribe" => "2", 
			"implode" => "2", 
			"intval" => "2", 
			"in_array" => "2", 
			"iptcparse" => "2", 
			"isset" => "2", 
			"is_array" => "2", 
			"is_dir" => "2", 
			"is_double" => "2", 
			"is_executable" => "2", 
			"is_file" => "2", 
			"is_float" => "2", 
			"is_int" => "2", 
			"is_integer" => "2", 
			"is_link" => "2", 
			"is_long" => "2", 
			"is_object" => "2", 
			"is_readable" => "2", 
			"is_real" => "2", 
			"is_string" => "2", 
			"is_writeable" => "2", 
			"JDDayOfWeek" => "2", 
			"JDMonthName" => "2", 
			"JDToFrench" => "2", 
			"JDToGregorian" => "2", 
			"JDToJewish" => "2", 
			"JDToJulian" => "2", 
			"JewishToJD" => "2", 
			"join" => "2", 
			"JulianToJD" => "2", 
			"key" => "2", 
			"krsort" => "2", 
			"ksort" => "2", 
			"ldap_add" => "2", 
			"ldap_bind" => "2", 
			"ldap_close" => "2", 
			"ldap_connect" => "2", 
			"ldap_count_entries" => "2", 
			"ldap_delete" => "2", 
			"ldap_dn2ufn" => "2", 
			"ldap_err2str" => "2", 
			"ldap_errno" => "2", 
			"ldap_error" => "2", 
			"ldap_explode_dn" => "2", 
			"ldap_first_attribute" => "2", 
			"ldap_first_entry" => "2", 
			"ldap_free_result" => "2", 
			"ldap_get_attributes" => "2", 
			"ldap_get_dn" => "2", 
			"ldap_get_entries" => "2", 
			"ldap_get_values" => "2", 
			"ldap_get_values_len" => "2", 
			"ldap_list" => "2", 
			"ldap_modify" => "2", 
			"ldap_mod_add" => "2", 
			"ldap_mod_del" => "2", 
			"ldap_mod_replace" => "2", 
			"ldap_next_attribute" => "2", 
			"ldap_next_entry" => "2", 
			"ldap_read" => "2", 
			"ldap_search" => "2", 
			"ldap_unbind" => "2", 
			"leak" => "2", 
			"link" => "2", 
			"linkinfo" => "2", 
			"list" => "2", 
			"Log" => "2", 
			"Log10" => "2", 
			"lstat" => "2", 
			"ltrim" => "2", 
			"mail" => "2", 
			"max" => "2", 
			"mcal_close" => "2", 
			"mcal_date_compare" => "2", 
			"mcal_date_valid" => "2", 
			"mcal_days_in_month" => "2", 
			"mcal_day_of_week" => "2", 
			"mcal_day_of_year" => "2", 
			"mcal_delete_event" => "2", 
			"mcal_event_init" => "2", 
			"mcal_event_set_alarm" => "2", 
			"mcal_event_set_category" => "2", 
			"mcal_event_set_class" => "2", 
			"mcal_event_set_description" => "2", 
			"mcal_event_set_end" => "2", 
			"mcal_event_set_recur_daily" => "2", 
			"mcal_event_set_recur_monthly_mday" => "2", 
			"mcal_event_set_recur_monthly_wday" => "2", 
			"mcal_event_set_recur_weekly" => "2", 
			"mcal_event_set_recur_yearly" => "2", 
			"mcal_event_set_start" => "2", 
			"mcal_event_set_title" => "2", 
			"mcal_fetch_current_stream_event" => "2", 
			"mcal_fetch_event" => "2", 
			"mcal_is_leap_year" => "2", 
			"mcal_list_alarms" => "2", 
			"mcal_list_events" => "2", 
			"mcal_next_recurrence" => "2", 
			"mcal_open" => "2", 
			"mcal_snooze" => "2", 
			"mcal_store_event" => "2", 
			"mcal_time_valid" => "2", 
			"mcrypt_cbc" => "2", 
			"mcrypt_cfb" => "2", 
			"mcrypt_create_iv" => "2", 
			"mcrypt_ecb" => "2", 
			"mcrypt_get_block_size" => "2", 
			"mcrypt_get_cipher_name" => "2", 
			"mcrypt_get_key_size" => "2", 
			"mcrypt_ofb" => "2", 
			"md5" => "2", 
			"Metaphone" => "2", 
			"mhash" => "2", 
			"mhash_count" => "2", 
			"mhash_get_block_size" => "2", 
			"mhash_get_hash_name" => "2", 
			"microtime" => "2", 
			"min" => "2", 
			"mkdir" => "2", 
			"mktime" => "2", 
			"Modifiers" => "2", 
			"msql" => "2", 
			"msql_affected_rows" => "2", 
			"msql_close" => "2", 
			"msql_connect" => "2", 
			"msql_createdb" => "2", 
			"msql_create_db" => "2", 
			"msql_data_seek" => "2", 
			"msql_dbname" => "2", 
			"msql_dropdb" => "2", 
			"msql_drop_db" => "2", 
			"msql_error" => "2", 
			"msql_fetch_array" => "2", 
			"msql_fetch_field" => "2", 
			"msql_fetch_object" => "2", 
			"msql_fetch_row" => "2", 
			"msql_fieldflags" => "2", 
			"msql_fieldlen" => "2", 
			"msql_fieldname" => "2", 
			"msql_fieldtable" => "2", 
			"msql_fieldtype" => "2", 
			"msql_field_seek" => "2", 
			"msql_freeresult" => "2", 
			"msql_free_result" => "2", 
			"msql_listdbs" => "2", 
			"msql_listfields" => "2", 
			"msql_listtables" => "2", 
			"msql_list_dbs" => "2", 
			"msql_list_fields" => "2", 
			"msql_list_tables" => "2", 
			"msql_numfields" => "2", 
			"msql_numrows" => "2", 
			"msql_num_fields" => "2", 
			"msql_num_rows" => "2", 
			"msql_pconnect" => "2", 
			"msql_query" => "2", 
			"msql_regcase" => "2", 
			"msql_result" => "2", 
			"msql_selectdb" => "2", 
			"msql_select_db" => "2", 
			"msql_tablename" => "2", 
			"mssql_close" => "2", 
			"mssql_connect" => "2", 
			"mssql_data_seek" => "2", 
			"mssql_fetch_array" => "2", 
			"mssql_fetch_field" => "2", 
			"mssql_fetch_object" => "2", 
			"mssql_fetch_row" => "2", 
			"mssql_field_seek" => "2", 
			"mssql_free_result" => "2", 
			"mssql_num_fields" => "2", 
			"mssql_num_rows" => "2", 
			"mssql_pconnect" => "2", 
			"mssql_query" => "2", 
			"mssql_result" => "2", 
			"mssql_select_db" => "2", 
			"mt_getrandmax" => "2", 
			"mt_rand" => "2", 
			"mt_srand" => "2", 
			"mysql_affected_rows" => "2", 
			"mysql_change_user" => "2", 
			"mysql_close" => "2", 
			"mysql_connect" => "2", 
			"mysql_create_db" => "2", 
			"mysql_data_seek" => "2", 
			"mysql_db_query" => "2", 
			"mysql_drop_db" => "2", 
			"mysql_errno" => "2", 
			"mysql_error" => "2", 
			"mysql_fetch_array" => "2", 
			"mysql_fetch_field" => "2", 
			"mysql_fetch_lengths" => "2", 
			"mysql_fetch_object" => "2", 
			"mysql_fetch_row" => "2", 
			"mysql_field_flags" => "2", 
			"mysql_field_len" => "2", 
			"mysql_field_name" => "2", 
			"mysql_field_seek" => "2", 
			"mysql_field_table" => "2", 
			"mysql_field_type" => "2", 
			"mysql_free_result" => "2", 
			"mysql_insert_id" => "2", 
			"mysql_list_dbs" => "2", 
			"mysql_list_fields" => "2", 
			"mysql_list_tables" => "2", 
			"mysql_num_fields" => "2", 
			"mysql_num_rows" => "2", 
			"mysql_pconnect" => "2", 
			"mysql_query" => "2", 
			"mysql_result" => "2", 
			"mysql_select_db" => "2", 
			"mysql_tablename" => "2", 
			"next" => "2", 
			"nl2br" => "2", 
			"number_format" => "2", 
			"OCIBindByName" => "2", 
			"OCIColumnIsNULL" => "2", 
			"OCIColumnName" => "2", 
			"OCIColumnSize" => "2", 
			"OCIColumnType" => "2", 
			"OCICommit" => "2", 
			"OCIDefineByName" => "2", 
			"OCIError" => "2", 
			"OCIExecute" => "2", 
			"OCIFetch" => "2", 
			"OCIFetchInto" => "2", 
			"OCIFetchStatement" => "2", 
			"OCIFreeCursor" => "2", 
			"OCIFreeStatement" => "2", 
			"OCIInternalDebug" => "2", 
			"OCILogOff" => "2", 
			"OCILogon" => "2", 
			"OCINewCursor" => "2", 
			"OCINewDescriptor" => "2", 
			"OCINLogon" => "2", 
			"OCINumCols" => "2", 
			"OCIParse" => "2", 
			"OCIPLogon" => "2", 
			"OCIResult" => "2", 
			"OCIRollback" => "2", 
			"OCIRowCount" => "2", 
			"OCIServerVersion" => "2", 
			"OCIStatementType" => "2", 
			"OctDec" => "2", 
			"odbc_autocommit" => "2", 
			"odbc_binmode" => "2", 
			"odbc_close" => "2", 
			"odbc_close_all" => "2", 
			"odbc_commit" => "2", 
			"odbc_connect" => "2", 
			"odbc_cursor" => "2", 
			"odbc_do" => "2", 
			"odbc_exec" => "2", 
			"odbc_execute" => "2", 
			"odbc_fetch_into" => "2", 
			"odbc_fetch_row" => "2", 
			"odbc_field_len" => "2", 
			"odbc_field_name" => "2", 
			"odbc_field_type" => "2", 
			"odbc_free_result" => "2", 
			"odbc_longreadlen" => "2", 
			"odbc_num_fields" => "2", 
			"odbc_num_rows" => "2", 
			"odbc_pconnect" => "2", 
			"odbc_prepare" => "2", 
			"odbc_result" => "2", 
			"odbc_result_all" => "2", 
			"odbc_rollback" => "2", 
			"odbc_setoption" => "2", 
			"opendir" => "2", 
			"openlog" => "2", 
			"Ora_Bind" => "2", 
			"Ora_Close" => "2", 
			"Ora_ColumnName" => "2", 
			"Ora_ColumnType" => "2", 
			"Ora_Commit" => "2", 
			"Ora_CommitOff" => "2", 
			"Ora_CommitOn" => "2", 
			"Ora_Error" => "2", 
			"Ora_ErrorCode" => "2", 
			"Ora_Exec" => "2", 
			"Ora_Fetch" => "2", 
			"Ora_GetColumn" => "2", 
			"Ora_Logoff" => "2", 
			"Ora_Logon" => "2", 
			"Ora_Open" => "2", 
			"Ora_Parse" => "2", 
			"Ora_Rollback" => "2", 
			"Ord" => "2", 
			"pack" => "2", 
			"parse_str" => "2", 
			"parse_url" => "2", 
			"passthru" => "2", 
			"Pattern" => "2", 
			"pclose" => "2", 
			"pdf_add_annotation" => "2", 
			"PDF_add_outline" => "2", 
			"PDF_arc" => "2", 
			"PDF_begin_page" => "2", 
			"PDF_circle" => "2", 
			"PDF_clip" => "2", 
			"PDF_close" => "2", 
			"PDF_closepath" => "2", 
			"PDF_closepath_fill_stroke" => "2", 
			"PDF_closepath_stroke" => "2", 
			"PDF_close_image" => "2", 
			"PDF_continue_text" => "2", 
			"PDF_curveto" => "2", 
			"PDF_endpath" => "2", 
			"PDF_end_page" => "2", 
			"PDF_execute_image" => "2", 
			"PDF_fill" => "2", 
			"PDF_fill_stroke" => "2", 
			"PDF_get_info" => "2", 
			"PDF_lineto" => "2", 
			"PDF_moveto" => "2", 
			"PDF_open" => "2", 
			"PDF_open_gif" => "2", 
			"PDF_open_jpeg" => "2", 
			"PDF_open_memory_image" => "2", 
			"PDF_place_image" => "2", 
			"PDF_put_image" => "2", 
			"PDF_rect" => "2", 
			"PDF_restore" => "2", 
			"PDF_rotate" => "2", 
			"PDF_save" => "2", 
			"PDF_scale" => "2", 
			"PDF_setdash" => "2", 
			"PDF_setflat" => "2", 
			"PDF_setgray" => "2", 
			"PDF_setgray_fill" => "2", 
			"PDF_setgray_stroke" => "2", 
			"PDF_setlinecap" => "2", 
			"PDF_setlinejoin" => "2", 
			"PDF_setlinewidth" => "2", 
			"PDF_setmiterlimit" => "2", 
			"PDF_setrgbcolor" => "2", 
			"PDF_setrgbcolor_fill" => "2", 
			"PDF_setrgbcolor_stroke" => "2", 
			"PDF_set_char_spacing" => "2", 
			"PDF_set_duration" => "2", 
			"PDF_set_font" => "2", 
			"PDF_set_horiz_scaling" => "2", 
			"PDF_set_info_author" => "2", 
			"PDF_set_info_creator" => "2", 
			"PDF_set_info_keywords" => "2", 
			"PDF_set_info_subject" => "2", 
			"PDF_set_info_title" => "2", 
			"PDF_set_leading" => "2", 
			"PDF_set_text_matrix" => "2", 
			"PDF_set_text_pos" => "2", 
			"PDF_set_text_rendering" => "2", 
			"PDF_set_text_rise" => "2", 
			"PDF_set_transition" => "2", 
			"PDF_set_word_spacing" => "2", 
			"PDF_show" => "2", 
			"PDF_show_xy" => "2", 
			"PDF_stringwidth" => "2", 
			"PDF_stroke" => "2", 
			"PDF_translate" => "2", 
			"pfsockopen" => "2", 
			"pg_Close" => "2", 
			"pg_cmdTuples" => "2", 
			"pg_Connect" => "2", 
			"pg_DBname" => "2", 
			"pg_ErrorMessage" => "2", 
			"pg_Exec" => "2", 
			"pg_Fetch_Array" => "2", 
			"pg_Fetch_Object" => "2", 
			"pg_Fetch_Row" => "2", 
			"pg_FieldIsNull" => "2", 
			"pg_FieldName" => "2", 
			"pg_FieldNum" => "2", 
			"pg_FieldPrtLen" => "2", 
			"pg_FieldSize" => "2", 
			"pg_FieldType" => "2", 
			"pg_FreeResult" => "2", 
			"pg_GetLastOid" => "2", 
			"pg_Host" => "2", 
			"pg_loclose" => "2", 
			"pg_locreate" => "2", 
			"pg_loopen" => "2", 
			"pg_loread" => "2", 
			"pg_loreadall" => "2", 
			"pg_lounlink" => "2", 
			"pg_lowrite" => "2", 
			"pg_NumFields" => "2", 
			"pg_NumRows" => "2", 
			"pg_Options" => "2", 
			"pg_pConnect" => "2", 
			"pg_Port" => "2", 
			"pg_Result" => "2", 
			"pg_tty" => "2", 
			"phpinfo" => "2", 
			"phpversion" => "2", 
			"pi" => "2", 
			"popen" => "2", 
			"pos" => "2", 
			"posix_ctermid" => "2", 
			"posix_getcwd" => "2", 
			"posix_getegid" => "2", 
			"posix_geteuid" => "2", 
			"posix_getgid" => "2", 
			"posix_getgrgid" => "2", 
			"posix_getgrnam" => "2", 
			"posix_getgroups" => "2", 
			"posix_getlogin" => "2", 
			"posix_getpgid" => "2", 
			"posix_getpgrp" => "2", 
			"posix_getpid" => "2", 
			"posix_getppid" => "2", 
			"posix_getpwnam" => "2", 
			"posix_getpwuid" => "2", 
			"posix_getrlimit" => "2", 
			"posix_getuid" => "2", 
			"posix_isatty" => "2", 
			"posix_kill" => "2", 
			"posix_mkfifo" => "2", 
			"posix_setgid" => "2", 
			"posix_setpgid" => "2", 
			"posix_setsid" => "2", 
			"posix_setuid" => "2", 
			"posix_times" => "2", 
			"posix_ttyname" => "2", 
			"posix_uname" => "2", 
			"pow" => "2", 
			"preg_grep" => "2", 
			"preg_match" => "2", 
			"preg_match_all" => "2", 
			"preg_quote" => "2", 
			"preg_replace" => "2", 
			"preg_split" => "2", 
			"prev" => "2", 
			"print" => "2", 
			"printf" => "2", 
			"putenv" => "2", 
			"quoted_printable_decode" => "2", 
			"QuoteMeta" => "2", 
			"rand" => "2", 
			"range" => "2", 
			"rawurldecode" => "2", 
			"rawurlencode" => "2", 
			"readdir" => "2", 
			"readfile" => "2", 
			"readgzfile" => "2", 
			"readlink" => "2", 
			"recode_file" => "2", 
			"recode_string" => "2", 
			"register_shutdown_function" => "2", 
			"rename" => "2", 
			"reset" => "2", 
			"rewind" => "2", 
			"rewinddir" => "2", 
			"rmdir" => "2", 
			"round" => "2", 
			"rsort" => "2", 
			"sem_acquire" => "2", 
			"sem_get" => "2", 
			"sem_release" => "2", 
			"serialize" => "2", 
			"session_decode" => "2", 
			"session_destroy" => "2", 
			"session_encode" => "2", 
			"session_id" => "2", 
			"session_is_registered" => "2", 
			"session_module_name" => "2", 
			"session_name" => "2", 
			"session_register" => "2", 
			"session_save_path" => "2", 
			"session_start" => "2", 
			"session_unregister" => "2", 
			"setcookie" => "2", 
			"setlocale" => "2", 
			"settype" => "2", 
			"set_file_buffer" => "2", 
			"set_magic_quotes_runtime" => "2", 
			"set_socket_blocking" => "2", 
			"set_time_limit" => "2", 
			"shm_attach" => "2", 
			"shm_detach" => "2", 
			"shm_get_var" => "2", 
			"shm_put_var" => "2", 
			"shm_remove" => "2", 
			"shm_remove_var" => "2", 
			"shuffle" => "2", 
			"similar_text" => "2", 
			"Sin" => "2", 
			"sizeof" => "2", 
			"sleep" => "2", 
			"snmpget" => "2", 
			"snmpset" => "2", 
			"snmpwalk" => "2", 
			"snmpwalkoid" => "2", 
			"snmp_get_quick_print" => "2", 
			"snmp_set_quick_print" => "2", 
			"solid_close" => "2", 
			"solid_connect" => "2", 
			"solid_exec" => "2", 
			"solid_fetchrow" => "2", 
			"solid_fieldname" => "2", 
			"solid_fieldnum" => "2", 
			"solid_freeresult" => "2", 
			"solid_numfields" => "2", 
			"solid_numrows" => "2", 
			"solid_result" => "2", 
			"sort" => "2", 
			"soundex" => "2", 
			"split" => "2", 
			"sprintf" => "2", 
			"sql_regcase" => "2", 
			"Sqrt" => "2", 
			"srand" => "2", 
			"stat" => "2", 
			"strcasecmp" => "2", 
			"strchr" => "2", 
			"strcmp" => "2", 
			"strcspn" => "2", 
			"strftime" => "2", 
			"StripCSlashes" => "2", 
			"StripSlashes" => "2", 
			"strip_tags" => "2", 
			"stristr" => "2", 
			"strlen" => "2", 
			"strpos" => "2", 
			"strrchr" => "2", 
			"strrev" => "2", 
			"strrpos" => "2", 
			"strspn" => "2", 
			"strstr" => "2", 
			"strtok" => "2", 
			"strtolower" => "2", 
			"strtoupper" => "2", 
			"strtr" => "2", 
			"strval" => "2", 
			"str_repeat" => "2", 
			"str_replace" => "2", 
			"substr" => "2", 
			"substr_replac" => "2", 
			"sybase_affected_rows" => "2", 
			"sybase_close" => "2", 
			"sybase_connect" => "2", 
			"sybase_data_seek" => "2", 
			"sybase_fetch_array" => "2", 
			"sybase_fetch_field" => "2", 
			"sybase_fetch_object" => "2", 
			"sybase_fetch_row" => "2", 
			"sybase_field_seek" => "2", 
			"sybase_free_result" => "2", 
			"sybase_num_fields" => "2", 
			"sybase_num_rows" => "2", 
			"sybase_pconnect" => "2", 
			"sybase_query" => "2", 
			"sybase_result" => "2", 
			"sybase_select_db" => "2", 
			"symlink" => "2", 
			"Syntax" => "2", 
			"syslog" => "2", 
			"system" => "2", 
			"Tan" => "2", 
			"tempnam" => "2", 
			"time" => "2", 
			"touch" => "2", 
			"trim" => "2", 
			"uasort" => "2", 
			"ucfirst" => "2", 
			"ucwords" => "2", 
			"uksort" => "2", 
			"umask" => "2", 
			"uniqid" => "2", 
			"unlink" => "2", 
			"unpack" => "2", 
			"unserialize" => "2", 
			"unset" => "2", 
			"urldecode" => "2", 
			"urlencode" => "2", 
			"usleep" => "2", 
			"usort" => "2", 
			"utf8_decode" => "2", 
			"utf8_encode" => "2", 
			"virtual" => "2", 
			"vm_addalias" => "2", 
			"vm_adduser" => "2", 
			"vm_delalias" => "2", 
			"vm_deluser" => "2", 
			"vm_passwd" => "2", 
			"wddx_add_vars" => "2", 
			"wddx_deserialize" => "2", 
			"wddx_packet_end" => "2", 
			"wddx_packet_start" => "2", 
			"wddx_serialize_value" => "2", 
			"wddx_serialize_vars" => "2", 
			"xml_error_string" => "2", 
			"xml_get_current_byte_index" => "2", 
			"xml_get_current_column_number" => "2", 
			"xml_get_current_line_number" => "2", 
			"xml_get_error_code" => "2", 
			"xml_parse" => "2", 
			"xml_parser_create" => "2", 
			"xml_parser_free" => "2", 
			"xml_parser_get_option" => "2", 
			"xml_parser_set_option" => "2", 
			"xml_set_character_data_handler" => "2", 
			"xml_set_default_handler" => "2", 
			"xml_set_element_handler" => "2", 
			"xml_set_external_entity_ref_handler" => "2", 
			"xml_set_notation_decl_handler" => "2", 
			"xml_set_object" => "2", 
			"xml_set_processing_instruction_handler" => "2", 
			"xml_set_unparsed_entity_decl_handler" => "2", 
			"yp_errno" => "2", 
			"yp_err_string" => "2", 
			"yp_first" => "2", 
			"yp_get_default_domain" => "2", 
			"yp_master" => "2", 
			"yp_match" => "2", 
			"yp_next" => "2", 
			"yp_order" => "2");

// Special extensions

// Each category can specify a PHP function that returns an altered
// version of the keyword.



$this->linkscripts    	= array(
			"2" => "dofunction", 
			"1" => "donothing");
}

function donothing($keywordin)
{
	return $keywordin;
}

function dofunction($keywordin)
{
	$outlink = "http://www.php.net/manual/en/function.".strtr($keywordin, "_", "-").".php";
	return "<a href=\"$outlink\">$keywordin</a>";
}



}
?>
