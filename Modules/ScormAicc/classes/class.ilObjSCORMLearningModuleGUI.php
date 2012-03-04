<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once "./Services/Object/classes/class.ilObjectGUI.php";
require_once("./Services/FileSystem/classes/class.ilFileSystemGUI.php");
require_once("Services/User/classes/class.ilObjUser.php");

require_once("./Modules/ScormAicc/classes/class.ilObjSAHSLearningModuleGUI.php");
require_once("./Modules/ScormAicc/classes/class.ilObjSCORMLearningModule.php");

/**
* Class ilObjSCORMLearningModuleGUI
*
* @author Alex Killing <alex.killing@gmx.de>, Hendrik Holtmann <holtmann@mac.com>
* $Id$
*
* @ilCtrl_Calls ilObjSCORMLearningModuleGUI: ilFileSystemGUI, ilMDEditorGUI, ilPermissionGUI, ilLearningProgressGUI
* @ilCtrl_Calls ilObjSCORMLearningModuleGUI: ilInfoScreenGUI
* @ilCtrl_Calls ilObjSCORMLearningModuleGUI: ilCertificateGUI
* @ilCtrl_Calls ilObjSCORMLearningModuleGUI: ilLicenseGUI
*
* @ingroup ModulesScormAicc
*/
class ilObjSCORMLearningModuleGUI extends ilObjSAHSLearningModuleGUI
{
	/**
	* Constructor
	*
	* @access	public
	*/
	function ilObjSCORMLearningModuleGUI($a_data,$a_id,$a_call_by_reference, $a_prepare_output = true)
	{
		global $lng;

		$lng->loadLanguageModule("content");
		$lng->loadLanguageModule("search");
		
		$this->type = "sahs";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference,false);
	}

	/**
	* assign scorm object to scorm gui object
	*/
	function assignObject()
	{
		if ($this->id != 0)
		{
			if ($this->call_by_reference)
			{
				$this->object =& new ilObjSCORMLearningModule($this->id, true);
			}
			else
			{
				$this->object =& new ilObjSCORMLearningModule($this->id, false);
			}
		}
	}

	/**
	* scorm module properties
	*/
	function properties()
	{	
		global $rbacsystem, $tree, $tpl;
		
		// edit button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// view link
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK",
			"ilias.php?baseClass=ilSAHSPresentationGUI&amp;ref_id=".$this->object->getRefID());
		$this->tpl->setVariable("BTN_TARGET"," target=\"ilContObj".$this->object->getID()."\" ");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("view"));
		$this->tpl->parseCurrentBlock();

		if (ilObjSAHSLearningModule::_lookupSubType($this->object->getID()) == "scorm") {
			// upload new version
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "newModuleVersion"));
			$this->tpl->setVariable("BTN_TXT",$this->lng->txt("cont_sc_new_version"));
			$this->tpl->parseCurrentBlock();
		}
		
		// scorm lm properties
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.sahs_properties.html", "Modules/ScormAicc");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_PROPERTIES", $this->lng->txt("cont_lm_properties"));

		// online
		$this->tpl->setVariable("TXT_ONLINE", $this->lng->txt("cont_online"));
		$this->tpl->setVariable("CBOX_ONLINE", "cobj_online");
		$this->tpl->setVariable("VAL_ONLINE", "y");
		if ($this->object->getOnline())
		{
			$this->tpl->setVariable("CHK_ONLINE", "checked");
		}

		//open
		$this->tpl->setVariable("TXT_OPEN", $this->lng->txt("cont_open"));
		$open_modes = array(
			"0" => $this->lng->txt("cont_open_normal"),
			"1" => $this->lng->txt("cont_open_iframe_max"),
			"2" => $this->lng->txt("cont_open_iframe_defined"),
			"5" => $this->lng->txt("cont_open_window_undefined"),
			"6" => $this->lng->txt("cont_open_window_defined")
			);
		$sel_open = ilUtil::formSelect($this->object->getOpenMode(),
			"open_mode", $open_modes, false, true);
		$this->tpl->setVariable("SEL_OPEN", $sel_open);

		//width
		$this->tpl->setVariable("TXT_WIDTH", $this->lng->txt("cont_open_width"));
		$this->tpl->setVariable("VAL_WIDTH", $this->object->getWidth());

		//heigth
		$this->tpl->setVariable("TXT_HEIGHT", $this->lng->txt("cont_open_heigth"));
		$this->tpl->setVariable("VAL_HEIGHT", $this->object->getHeight());

		// api adapter name
		$this->tpl->setVariable("TXT_API_ADAPTER", $this->lng->txt("cont_api_adapter"));
		$this->tpl->setVariable("VAL_API_ADAPTER", $this->object->getAPIAdapterName());

		// api functions prefix
		$this->tpl->setVariable("TXT_API_PREFIX", $this->lng->txt("cont_api_func_prefix"));
		$this->tpl->setVariable("VAL_API_PREFIX", $this->object->getAPIFunctionsPrefix());

		// default lesson mode
		$this->tpl->setVariable("TXT_LESSON_MODE", $this->lng->txt("cont_def_lesson_mode"));
		$lesson_modes = array("normal" => $this->lng->txt("cont_sc_less_mode_normal"),
			"browse" => $this->lng->txt("cont_sc_less_mode_browse"));
		$sel_lesson = ilUtil::formSelect($this->object->getDefaultLessonMode(),
			"lesson_mode", $lesson_modes, false, true);
		$this->tpl->setVariable("SEL_LESSON_MODE", $sel_lesson);

		// credit mode
		$this->tpl->setVariable("TXT_CREDIT_MODE", $this->lng->txt("cont_credit_mode"));
		$credit_modes = array("credit" => $this->lng->txt("cont_credit_on"),
			"no_credit" => $this->lng->txt("cont_credit_off"));
		$sel_credit = ilUtil::formSelect($this->object->getCreditMode(),
			"credit_mode", $credit_modes, false, true);
		$this->tpl->setVariable("SEL_CREDIT_MODE", $sel_credit);

		// auto review mode
		$this->tpl->setVariable("TXT_AUTO_REVIEW", $this->lng->txt("cont_sc_auto_review"));
		$this->tpl->setVariable("CBOX_AUTO_REVIEW", "auto_review");
		$this->tpl->setVariable("VAL_AUTO_REVIEW", "y");
		if ($this->object->getAutoReview())
		{
			$this->tpl->setVariable("CHK_AUTO_REVIEW", "checked");
		}
		
		// max attempts
		$this->tpl->setVariable("MAX_ATTEMPTS", $this->lng->txt("cont_sc_max_attempt"));
		$this->tpl->setVariable("VAL_MAX_ATTEMPT", $this->object->getMaxAttempt());
		
		// version
		$this->tpl->setVariable("TXT_VERSION", $this->lng->txt("cont_sc_version"));
		$this->tpl->setVariable("VAL_VERSION", $this->object->getModuleVersion());
		
		//unlimited session
		$this->tpl->setVariable("TXT_SESSION", $this->lng->txt("cont_sc_usession"));
		$this->tpl->setVariable("CBOX_SESSION", "cobj_session");
		$this->tpl->setVariable("VAL_SESSION", "y");
		if ($this->object->getSession())
		{
			$this->tpl->setVariable("CHK_SESSION", "checked");
		}

		// auto continue
		$this->tpl->setVariable("TXT_AUTO_CONTINUE", $this->lng->txt("cont_sc_auto_continue"));
		$this->tpl->setVariable("CBOX_AUTO_CONTINUE", "auto_continue");
		$this->tpl->setVariable("VAL_AUTO_CONTINUE", "y");
		if ($this->object->getAutoContinue())
		{
			$this->tpl->setVariable("CHK_AUTO_CONTINUE", "checked");
		}

		//debug
		$this->tpl->setVariable("TXT_DEBUG", $this->lng->txt("cont_debug"));
		$this->tpl->setVariable("CBOX_DEBUG", "cobj_debug");
		$this->tpl->setVariable("VAL_DEBUG", "y");
		if ($this->object->getDebug())
		{
			$this->tpl->setVariable("CHK_DEBUG", "checked");
		}
		
		//debugActivated
		if ($this->object->getDebugActivated() == false) {
			$this->tpl->setVariable("CHK_ACTIVATED", "disabled");
			$this->tpl->setVariable("TXT_ACTIVATED", $this->lng->txt("cont_debug_deactivated"));
		} else {
			$this->tpl->setVariable("TXT_ACTIVATED", $this->lng->txt("cont_debug_deactivate"));
		}
		
		
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "saveProperties");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();

	}

	/**
	* upload new version of module
	*/
	function newModuleVersion()
	{
	
	   $obj_id = ilObject::_lookupObjectId($_GET['ref_id']);
	   $type = ilObjSAHSLearningModule::_lookupSubType($obj_id);
	  
	   // display import form
	   $this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.scorm_new_version_import.html", "Modules/ScormAicc");
    
	   $this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_slm.gif'));
	   $this->tpl->setVariable("ALT_IMG", $this->lng->txt("obj_sahs"));
    
	   $this->ctrl->setParameter($this, "new_type", "sahs");
	   $this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
    
	   $this->tpl->setVariable("BTN_NAME", "newModuleVersionUpload");
	   $this->tpl->setVariable("TARGET", ' target="'.
	   	ilFrameTargetInfo::_getFrame("MainContent").'" ');
    
	   $this->tpl->setVariable("TXT_SELECT_LMTYPE", $this->lng->txt("type"));
	  
	   if ($type == "scorm2004") {
		   $this->tpl->setVariable("TXT_TYPE", $this->lng->txt("lm_type_scorm2004"));
	   } else {
		   $this->tpl->setVariable("TXT_TYPE", $this->lng->txt("lm_type_scorm"));
	   }    
	
		include_once 'Services/FileSystem/classes/class.ilUploadFiles.php';
		if (ilUploadFiles::_getUploadDirectory())
		{
			$files = ilUploadFiles::_getUploadFiles();
			foreach($files as $file)
			{
				$file = htmlspecialchars($file, ENT_QUOTES, "utf-8");
				$this->tpl->setCurrentBlock("option_uploaded_file");
				$this->tpl->setVariable("UPLOADED_FILENAME", $file);
				$this->tpl->setVariable("TXT_UPLOADED_FILENAME", $file);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("select_uploaded_file");
			$this->tpl->setVariable("TXT_SELECT_FROM_UPLOAD_DIR", $this->lng->txt("cont_select_from_upload_dir"));
			$this->tpl->setVariable("TXT_UPLOADED_FILE", $this->lng->txt("cont_uploaded_file"));
			$this->tpl->parseCurrentBlock();
		}

	   $this->tpl->setVariable("TXT_UPLOAD", $this->lng->txt("upload"));
	   $this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
	   $this->tpl->setVariable("TXT_IMPORT_LM", $this->lng->txt("import_sahs"));
	   $this->tpl->setVariable("TXT_SELECT_FILE", $this->lng->txt("select_file"));
    
	   // gives out the limit as a little notice
	   $this->tpl->setVariable("TXT_FILE_INFO", $this->lng->txt("file_notice")." ".$this->getMaxFileSize());
	}
	
	function getMaxFileSize()
	{

	   // get the value for the maximal uploadable filesize from the php.ini (if available)
	   $umf=get_cfg_var("upload_max_filesize");
	   // get the value for the maximal post data from the php.ini (if available)
	   $pms=get_cfg_var("post_max_size");
     
	   //convert from short-string representation to "real" bytes
	   $multiplier_a=array("K"=>1024, "M"=>1024*1024, "G"=>1024*1024*1024);
     
	   $umf_parts=preg_split("/(\d+)([K|G|M])/", $umf, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
	   $pms_parts=preg_split("/(\d+)([K|G|M])/", $pms, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
     
	   if (count($umf_parts) == 2) { $umf = $umf_parts[0]*$multiplier_a[$umf_parts[1]]; }
	   if (count($pms_parts) == 2) { $pms = $pms_parts[0]*$multiplier_a[$pms_parts[1]]; }
     
	   // use the smaller one as limit
	   $max_filesize=min($umf, $pms);
     
	   if (!$max_filesize) $max_filesize=max($umf, $pms);
     
	   //format for display in mega-bytes
	   return $max_filesize=sprintf("%.1f MB",$max_filesize/1024/1024);
	}
	
	
	function newModuleVersionUpload()
	{
		global $_FILES, $rbacsystem;

		$unzip = PATH_TO_UNZIP;
		$tocheck = "imsmanifest.xml";
		
		include_once 'Services/FileSystem/classes/class.ilUploadFiles.php';

		// check create permission before because the uploaded file will be copied
		if (!$rbacsystem->checkAccess("write", $_GET["ref_id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_create_permission"), $this->ilias->error_obj->WARNING);
		}
		elseif ($_FILES["scormfile"]["name"])
		{
			// check if file was uploaded
			$source = $_FILES["scormfile"]["tmp_name"];
			if (($source == 'none') || (!$source))
			{
				ilUtil::sendInfo($this->lng->txt("upload_error_file_not_found"),true);
				$this->newModuleVersion();
				return;
			}
		}
		elseif ($_POST["uploaded_file"])
		{
			// check if the file is in the ftp directory and readable
 			if (!ilUploadFiles::_checkUploadFile($_POST["uploaded_file"]))
			{
				$this->ilias->raiseError($this->lng->txt("upload_error_file_not_found"),$this->ilias->error_obj->MESSAGE);
			}

			// copy the uploaded file to the client web dir to analyze the imsmanifest
			// the copy will be moved to the lm directory or deleted
 			$source = CLIENT_WEB_DIR . "/" . $_POST["uploaded_file"];
			ilUploadFiles::_copyUploadFile($_POST["uploaded_file"], $source);
			$source_is_copy = true;
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt("upload_error_file_not_found"),true);
			$this->newModuleVersion();
			return;
		}
		// fim.
		
		//unzip the imsmanifest-file from new uploaded file
		$pathinfo = pathinfo($source);
		$dir = $pathinfo["dirname"];
		$file = $pathinfo["basename"];
		$cdir = getcwd();
		chdir($dir);
		
		//we need more flexible unzip here than ILIAS standard classes allow
		$unzipcmd = $unzip." -o ".ilUtil::escapeShellArg($source)." ".$tocheck;
		exec($unzipcmd);
		chdir($cdir);
		$tmp_file = $dir."/".$tocheck.".".$_GET["ref_id"];
		
		rename($dir."/".$tocheck,$tmp_file);
		$new_manifest = file_get_contents($tmp_file);
		
		//remove temp file
		unlink($tmp_file);
		
		//get old manifest file	
		$old_manifest = file_get_contents($this->object->getDataDirectory()."/".$tocheck);
		
		//reload fixed version of file
		$check ='/xmlns="http:\/\/www.imsglobal.org\/xsd\/imscp_v1p1"/';
		$replace="xmlns=\"http://www.imsproject.org/xsd/imscp_rootv1p1p2\"";
		$reload_manifest = preg_replace($check, $replace, $new_manifest);

		//do testing for converted versions as well as earlier ILIAS version messed up utf8 conversion
		if (strcmp($new_manifest,$old_manifest) == 0 || strcmp(utf8_encode($new_manifest),$old_manifest) == 0 ||
			strcmp ($reload_manifest, $old_manifest) == 0 || strcmp(utf8_encode($reload_manifest),$old_manifest) == 0 ){

			//get exisiting module version
			$module_version = $this->object->getModuleVersion();
			
			if ($_FILES["scormfile"]["name"])
			{
				//build targetdir in lm_data
				$file_path = $this->object->getDataDirectory()."/".$_FILES["scormfile"]["name"].".".$module_version;
				
				//move to data directory and add subfix for versioning
				ilUtil::moveUploadedFile($_FILES["scormfile"]["tmp_name"],$_FILES["scormfile"]["name"], $file_path);
			}
			else
			{
				//build targetdir in lm_data
				$file_path = $this->object->getDataDirectory()."/".$_POST["uploaded_file"].".".$module_version;

				// move the already copied file to the lm_data directory
				rename($source, $file_path);
			}
			
			//unzip and replace old extracted files
			ilUtil::unzip($file_path, true);
			ilUtil::renameExecutables($this->object->getDataDirectory()); //(security)
			
			//increase module version
			$this->object->setModuleVersion($module_version+1);
			$this->object->update();
			
			//redirect to properties and display success
			ilUtil::sendInfo( $this->lng->txt("cont_new_module_added"), true);
			ilUtil::redirect("ilias.php?baseClass=ilSAHSEditGUI&ref_id=".$_GET["ref_id"]);
			exit;
		}
		else
		{
			if ($source_is_copy)
			{
				unlink($source);
			}
			
			ilUtil::sendInfo($this->lng->txt("cont_invalid_new_module"),true);
			$this->newModuleVersion();
		}
				
	}
	
	/**
	* save properties
	*/
	function saveProperties()
	{
		$this->object->setOnline(ilUtil::yn2tf($_POST["cobj_online"]));
		$this->object->setOpenMode($_POST["open_mode"]);
		$this->object->setWidth($_POST["width"]);
		$this->object->setHeight($_POST["height"]);
		$this->object->setAutoReview(ilUtil::yn2tf($_POST["auto_review"]));
		$this->object->setAPIAdapterName($_POST["api_adapter"]);
		$this->object->setAPIFunctionsPrefix($_POST["api_func_prefix"]);
		$this->object->setCreditMode($_POST["credit_mode"]);
		$this->object->setDefaultLessonMode($_POST["lesson_mode"]);
		$this->object->setMaxAttempt($_POST["max_attempt"]);
		$this->object->setSession(ilUtil::yn2tf($_POST["cobj_session"]));
		$this->object->setDebug(ilUtil::yn2tf($_POST["cobj_debug"]));
		$this->object->setAutoContinue(ilUtil::yn2tf($_POST["auto_continue"]));
		$this->object->update();
		ilUtil::sendInfo($this->lng->txt("msg_obj_modified"), true);
		$this->ctrl->redirect($this, "properties");
	}


	/**
	* show tracking data
	*/
	
	function showTrackingItemsBySco()
	{
			
		global $ilTabs;
		
		include_once "./Services/Table/classes/class.ilTableGUI.php";

		$this->setSubTabs();
		
		$ilTabs->setSubTabActive("cont_tracking_bysco");
		$ilTabs->setTabActive("cont_tracking_data");
		

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");
		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.scorm_track_items_sco.html", "Modules/ScormAicc");

		$num = 1;

		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$this->ref_id."$obj_str&cmd=gateway");

		// create table
		$tbl = new ilTableGUI();

		// title & header columns
		$tbl->setTitle($this->lng->txt("cont_tracking_items"));

		$tbl->setHeaderNames(array($this->lng->txt("title")));

		$header_params = array("ref_id" => $this->ref_id, "cmd" => $_GET["cmd"],
			"cmdClass" => get_class($this), "baseClass"=>"ilSAHSEditGUI");
		$cols = array("title");
		$tbl->setHeaderVars($cols, $header_params);
		$tbl->setColumnWidth(array("100%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);

		//$this->tpl->setVariable("COLUMN_COUNTS",count($this->data["cols"]));
		//$this->showActions(true);

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		#$tbl->disable("footer");

		//$items = $this->object->getTrackingItems();
		$items = $this->object->getTrackedItems();
		
		$item_array = array();
		foreach($items as $item)
		{
			$tmp['title'] = $item->getTitle();
			$tmp['id'] = $item->getId();
			
			$item_array[] = $tmp;
		}
		
		$items = ilUtil::sortArray($item_array, $_GET["sort_by"], $_GET["sort_order"]);
		$tbl->setMaxCount(count($items));
		$items = array_slice($items, $_GET["offset"], $_GET["limit"]);
		
		
		$tbl->render();
		if (count($items) > 0)
		{
			foreach ($items as $item)
			{
				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("TXT_ITEM_TITLE", $item['title']);
				$this->ctrl->setParameter($this, "obj_id", $item['id']);
				$this->tpl->setVariable("LINK_ITEM",
					$this->ctrl->getLinkTarget($this, "showTrackingItemSco"));

				$css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->parseCurrentBlock();
			}
		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", $num);
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* show tracking data
	*/
	function showTrackingItems()
	{
		include_once('./Services/PrivacySecurity/classes/class.ilPrivacySettings.php');
		$privacy = ilPrivacySettings::_getInstance();
		if(!$privacy->enabledSahsProtocolData())
		{
			$this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
		}

		global $ilTabs;

		include_once "./Services/Table/classes/class.ilTableGUI.php";
		
		$this->setSubTabs();
		
		$ilTabs->setSubTabActive("cont_tracking_byuser");
		$ilTabs->setTabActive("cont_tracking_data");
		
		//set search
		
		if ($_POST["search_string"] != "")
		{
			$_SESSION["scorm_search_string"] = trim($_POST["search_string"]);
		} else 	if (isset($_POST["search_string"]) && $_POST["search_string"] == "") {
			unset($_SESSION["scorm_search_string"]);
		}


		// load template for search additions
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl_scorm_track_items_search.html","Modules/ScormAicc");
		// load template for table
		$this->tpl->addBlockfile("USR_TABLE", "usr_table", "tpl.table.html");
		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.scorm_track_items.html", "Modules/ScormAicc");

		$num = 6;

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		// create table
		$tbl = new ilTableGUI();
		
		// title & header columns
		if (isset($_SESSION["scorm_search_string"])) {
			$tbl->setTitle($this->lng->txt("cont_tracking_items").' - Aktive Suche: "'.$_SESSION["scorm_search_string"].'"');
		} else {
			$tbl->setTitle($this->lng->txt("cont_tracking_items"));
		}
		
//		$tbl->setHeaderNames(array("",$this->lng->txt("name"), $this->lng->txt("last_access"),$this->lng->txt("status"), $this->lng->txt("attempts"), $this->lng->txt("version")  ));
		$tbl->setHeaderNames(array("",$this->lng->txt("name"), $this->lng->txt("last_access"), $this->lng->txt("attempts"), $this->lng->txt("version")  ));

		$header_params = $this->ctrl->getParameterArray($this, "showTrackingItems");
				
		$tbl->setColumnWidth(array("1%", "35%", "20%", "15%","15%","15%"));
			
	//	$cols = array("user_id","username","last_access","status","attempts","version");
		$cols = array("user_id","username","last_access","attempts","version");
	
		$tbl->setHeaderVars($cols, $header_params);

		//set defaults
		$_GET["sort_order"] = $_GET["sort_order"] ? $_GET["sort_order"] : "asc";
		$_GET["sort_by"] = $_GET["sort_by"] ? $_GET["sort_by"] : "username";
		
		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);
		
		$this->tpl->setVariable("COLUMN_COUNTS", 6);
		
		// delete button
		$this->tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "deleteTrackingForUser");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("delete"));
		$this->tpl->parseCurrentBlock();
		
		// decrease attempts
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "decreaseAttempts");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("decrease_attempts"));
		$this->tpl->parseCurrentBlock();
		
		// export aggregated data for selected users
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "exportSelected");
		$this->tpl->setVariable("BTN_VALUE",  $this->lng->txt("export"));
		$this->tpl->parseCurrentBlock();
			
		// add search and export all
		// export aggregated data for all users
		$this->tpl->setVariable("EXPORT_ACTION",$this->ctrl->getFormAction($this));
		
		$this->tpl->setVariable("EXPORT_ALL_VALUE", $this->lng->txt('cont_export_all'));
		$this->tpl->setVariable("EXPORT_ALL_NAME", "exportAll");
		$this->tpl->setVariable("IMPORT_VALUE", $this->lng->txt('import'));
		$this->tpl->setVariable("IMPORT_NAME", "Import");
		
		$this->tpl->setVariable("SEARCH_TXT_SEARCH",$this->lng->txt('search'));
		$this->tpl->setVariable("SEARCH_ACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("SEARCH_NAME",'showTrackingItems');
		if (isset($_SESSION["scorm_search_string"])) {
			$this->tpl->setVariable("STYLE",'display:inline;');
		} else {
			$this->tpl->setVariable("STYLE",'display:none;');
		}
		$this->tpl->setVariable("SEARCH_VAL", 	$_SESSION["scorm_search_string"]);
		$this->tpl->setVariable("SEARCH_VALUE",$this->lng->txt('search_users'));
		$this->tpl->parseCurrentBlock();
		
		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));

		$items = $this->object->getTrackedUsers($_SESSION["scorm_search_string"]);
		$tbl->setMaxCount(count($items));
		
		$items  = ilUtil::sortArray($items ,$_GET["sort_by"],$_GET["sort_order"]);
		$items = array_slice($items, $_GET["offset"], $_GET["limit"]);

		$tbl->render();
		
		if (count($items) > 0)
		{
			foreach ($items as $item)
			{		
				if (ilObject::_exists($item["user_id"]) && ilObject::_lookUpType($item["user_id"])=="usr") 
				{	
				     $this->tpl->setCurrentBlock("tbl_content");
				     $this->tpl->setVariable("VAL_USERNAME",$item["username"]);
				     $this->tpl->setVariable("VAL_LAST", $item["last_access"]);
				     $this->tpl->setVariable("VAL_ATTEMPT",$item["attempts"]);
				     $this->tpl->setVariable("VAL_VERSION", $item["version"]);
				   //  $this->tpl->setVariable("VAL_STATUS", $item["status"]);
				     $this->ctrl->setParameter($this, "user_id", $item["user_id"]);
				     $this->ctrl->setParameter($this, "obj_id", $_GET["obj_id"]);
				     $this->tpl->setVariable("LINK_ITEM",
				     $this->ctrl->getLinkTarget($this, "showTrackingItem"));
				     $this->tpl->setVariable("CHECKBOX_ID", $item["user_id"]);
				     $css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
				     $this->tpl->setVariable("CSS_ROW", $css_row);
				     $this->tpl->parseCurrentBlock();
				}	
			}
			$this->tpl->setCurrentBlock("selectall");
			$this->tpl->setVariable("SELECT_ALL", $this->lng->txt("select_all"));
			$this->tpl->setVariable("CSS_ROW", $css_row);
			$this->tpl->parseCurrentBlock();
			
		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", $num);
			$this->tpl->parseCurrentBlock();
		}
		
	}
	
	
	function resetSearch() {
		unset($_SESSION["scorm_search_string"]);
		$this->ctrl->redirect($this, "showTrackingItems");
	}
	
	
	/**
		* display deletion confirmation screen
		*/
		function deleteTrackingForUser()
		{

			if(!isset($_POST["user"]))
			{
				$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
			}
			// SAVE POST VALUES
			$_SESSION["scorm_user_delete"] = $_POST["user"];

			unset($this->data);
			$this->data["cols"] = array("type","title", "description");

			foreach($_POST["user"] as $id)
			{
				if (ilObject::_exists($id) && ilObject::_lookUpType($id)=="usr" ) {	
					$user = new ilObjUser($id);
					$this->data["data"]["$id"] = array(
						"type"		  => "sahs",
						"title"       => $user->getLastname().", ".$user->getFirstname(),
						"desc"        => $this->lng->txt("cont_trackinging_data")
					);
				}
			}

			$this->data["buttons"] = array( "cancelDelete"  => $this->lng->txt("cancel"),
									  "confirmedDelete"  => $this->lng->txt("confirm"));

			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.obj_confirm.html");

			ilUtil::sendInfo($this->lng->txt("info_delete_sure"));

			$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));

			// BEGIN TABLE HEADER
			foreach ($this->data["cols"] as $key)
			{
				$this->tpl->setCurrentBlock("table_header");
				$this->tpl->setVariable("TEXT",$this->lng->txt($key));
				$this->tpl->parseCurrentBlock();
			}
			// END TABLE HEADER

			// BEGIN TABLE DATA
			$counter = 0;

			foreach($this->data["data"] as $key => $value)
			{
				// BEGIN TABLE CELL
				foreach($value as $key => $cell_data)
				{
					$this->tpl->setCurrentBlock("table_cell");

					// CREATE TEXT STRING
					if($key == "type")
					{
						$this->tpl->setVariable("TEXT_CONTENT",ilUtil::getImageTagByType($cell_data,$this->tpl->tplPath));
					}
					else
					{
						$this->tpl->setVariable("TEXT_CONTENT",$cell_data);
					}
					$this->tpl->parseCurrentBlock();
				}

				$this->tpl->setCurrentBlock("table_row");
				$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
				$this->tpl->parseCurrentBlock();
				// END TABLE CELL
			}
			// END TABLE DATA

			// BEGIN OPERATION_BTN
			foreach($this->data["buttons"] as $name => $value)
			{
				$this->tpl->setCurrentBlock("operation_btn");
				$this->tpl->setVariable("BTN_NAME",$name);
				$this->tpl->setVariable("BTN_VALUE",$value);
				$this->tpl->parseCurrentBlock();
			}
		}
		/**
		* cancel deletion of export files
		*/
		function cancelDelete()
		{
			session_unregister("scorm_user_delete");
			ilUtil::sendInfo($this->lng->txt("msg_cancel"),true);
			$this->ctrl->redirect($this, "showTrackingItems");
		}	

		function confirmedDelete()
		{
		 	global $ilDB, $ilUser;


		 	foreach ($_SESSION["scorm_user_delete"] as $user)
		 	{
		 		$ret = $ilDB->manipulateF('
		 		DELETE FROM scorm_tracking 
		 		WHERE user_id = %s
		 		AND obj_id = %s',
		 		array('integer','integer'),
		 		array($user,$this->object->getID()));
		 		
		 	}

			include_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");	
			ilLPStatusWrapper::_updateStatus($this->object->getId(), $user);

		 	$this->ctrl->redirect($this, "showTrackingItems");
		}
	
	/**
	* overwrite..jump back to trackingdata not parent
	*/
	function cancel()
	{
		ilUtil::sendInfo($this->lng->txt("msg_cancel"),true);
	 	$this->ctrl->redirect($this, "properties");
	}
	
	
	/**
	* gui functions for GUI export
	*/
	
	function import()
	{	
		if (!isset($_POST["type"])) {
			//show form
			$this->importForm();
		} else {
			//
			// check if file was uploaded
			$source = $_FILES["datafile"]["tmp_name"];
			if (($source == 'none') || (!$source))
			{
				ilUtil::sendInfo($this->lng->txt("No file selected!"),true);
				$this->importForm();
			} else {
				$error = $this->object->importTrackingData($source);
				switch ($error) {
					case 0 :
						ilUtil::sendInfo($this->lng->txt("Trackingdata imported"),true);
					 	$this->ctrl->redirect($this, "showTrackingItems");
						break;
					case -1 :
						ilUtil::sendInfo($this->lng->txt("Invalid import file"),true);
						$this->importForm();
						break;	
				}
			}
		}		
	}		
	
	
	function importForm(){

	 	$obj_id = ilObject::_lookupObjectId($_GET['ref_id']);
	    $type = ilObjSAHSLearningModule::_lookupSubType($obj_id);
       
	    // display import form
	    $this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.scorm_tracking_data_import.html", "Modules/ScormAicc");
       
	    $this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_slm.gif'));
	    $this->tpl->setVariable("ALT_IMG", $this->lng->txt("obj_sahs"));
       
	    $this->ctrl->setParameter($this, "new_type", "sahs");
	    $this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
       
	    $this->tpl->setVariable("BTN_NAME", "import");
	    $this->tpl->setVariable("TARGET", ' target="'.
	    	ilFrameTargetInfo::_getFrame("MainContent").'" ');
       
	    $this->tpl->setVariable("TXT_SELECT_LMTYPE", $this->lng->txt("type"));
       
	    $this->tpl->setVariable("TXT_TYPE","CSV");
       
	    $this->tpl->setVariable("TXT_UPLOAD", $this->lng->txt("upload"));
	    $this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
	    $this->tpl->setVariable("TXT_IMPORT_TRACKING", $this->lng->txt("cont_import_tracking"));
	    $this->tpl->setVariable("TXT_SELECT_FILE", $this->lng->txt("select_file"));
		// gives out the limit as a little notice
	   	$this->tpl->setVariable("TXT_FILE_INFO", $this->lng->txt("file_notice")." ".$this->getMaxFileSize());
	}
	
	
	function exportAll(){
		$this->export(1);
	}
	
	function exportSelected()
	{
		if (!isset($_POST["user"]))
		{
			ilUtil::sendInfo($this->lng->txt("no_checkbox"),true);
			$this->ctrl->redirect($this, "showTrackingItems");
		} else {
			$this->export(0);
		}	
	}
	
	function export($a_export_all = 0)
	{	
		if (!isset($_POST["export_type"])) {
			//show form
			$this->exportOptions($a_export_all,$_POST["user"]);
		} else {
			if (isset($_POST["cancel"])) {
				$this->ctrl->redirect($this, "showTrackingItems");
			} else {
				$a_export_all = $_POST["export_all"];
				if ($_POST["export_type"]=="raw") {
					$this->object->exportSelectedRaw($a_export_all, unserialize(stripslashes($_POST["user"])));
				} else {
					$this->object->exportSelected($a_export_all, unserialize(stripslashes($_POST["user"])));	
				}
			}
		}
	}
	
	
	function exportOptions($a_export_all=0, $a_users)
	{
	  	$obj_id = ilObject::_lookupObjectId($_GET['ref_id']);
	    $type = ilObjSAHSLearningModule::_lookupSubType($obj_id);
      
	    // display import form
	    $this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.scorm_tracking_data_export.html", "Modules/ScormAicc");
      
	    $this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_slm.gif'));
	    $this->tpl->setVariable("ALT_IMG", $this->lng->txt("obj_sahs"));
      
	    $this->tpl->setVariable("TXT_EXPORT", $this->lng->txt("cont_export_options"));

	    $this->ctrl->setParameter($this, "new_type", "sahs");
	    $this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
	
	    $this->tpl->setVariable("BTN_NAME", "export");
		
	    $this->tpl->setVariable("TARGET", ' target="'.
	    	ilFrameTargetInfo::_getFrame("MainContent").'" ');
      
	    $this->tpl->setVariable("TXT_SELECT_TYPE", $this->lng->txt("cont_export_type"));
	    $this->tpl->setVariable("TXT_EXPORT_RAW", $this->lng->txt("cont_export_raw"));
	    $this->tpl->setVariable("TXT_EXPORT_SUCCESS", $this->lng->txt("cont_export_success"));
	    $this->tpl->setVariable("TXT_EXPORT_TRACKING", $this->lng->txt("cont_export_tracking"));
	
	    $this->tpl->setVariable("TXT_EXPORT", $this->lng->txt("export"));
	    $this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));	
	    $this->tpl->setVariable("VAL_USER", htmlentities(serialize($a_users)));	
	    $this->tpl->setVariable("VAL_EXPORTALL",$a_export_all);		
		
		
	}
	
		
	function decreaseAttempts()
	{
		global $ilDB, $ilUser;
		
		if (!isset($_POST["user"]))
		{
			ilUtil::sendInfo($this->lng->txt("no_checkbox"),true);
		}
		
		foreach ($_POST["user"] as $user)
		{
			//first check if there is a package_attempts entry

			//get existing account - sco id is always 0
			$val_set = $ilDB->queryF('
			SELECT * FROM scorm_tracking 
			WHERE user_id = %s
			AND sco_id = %s 
			AND lvalue = %s
			AND obj_id = %s',
			array('integer','integer','text','integer'),
			array($user,0,'package_attempts',$this->object->getID()));
			
			$val_rec = $ilDB->fetchAssoc($val_set);
			
			$val_rec["rvalue"] = str_replace("\r\n", "\n", $val_rec["rvalue"]);
			if ($val_rec["rvalue"] != null && $val_rec["rvalue"] != 0) 
			{
				$new_rec =  $val_rec["rvalue"]-1;
				//decrease attempt by 1
				if($res = $ilDB->numRows($val_set) > 0)
				{		
					$ilDB->update('scorm_tracking',
						array(
							'rvalue'	=> array('clob', $new_rec)
						),
						array(
							'user_id'	=> array('integer', $user),
							'sco_id'	=> array('integer', 0),
							'obj_id'	=> array('integer', $this->object->getId()),
							'lvalue'	=> array('text', 'package_attempts')
						)
					);
				}
				else
				{
					$ilDB->insert('scorm_tracking', array(
						'rvalue'	=> array('clob', $new_rec),
						'user_id'	=> array('integer', $user),
						'sco_id'	=> array('integer', 0),
						'obj_id'	=> array('integer', $this->object->getId()),
						'lvalue'	=> array('text', 'package_attempts')
					));					
				}
				
				include_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");	
				ilLPStatusWrapper::_updateStatus($this->object->getId(), $user);
			}
		}

		//$this->ctrl->saveParameter($this, "cdir");
		$this->ctrl->redirect($this, "showTrackingItems");
	}
	
	
	/**
	* show tracking data of item
	*/
	function showTrackingItem()
	{
		global $ilTabs;
		
		include_once "./Services/Table/classes/class.ilTableGUI.php";

		$this->setSubTabs();
		$ilTabs->setTabActive("cont_tracking_data");
	    $ilTabs->setSubTabActive("cont_tracking_byuser");

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");
		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.scorm_track_item.html", "Modules/ScormAicc");


		$num = 2;

		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$this->ref_id."$obj_str&cmd=gateway");

		// create table
		$tbl = new ilTableGUI();

		include_once("./Modules/ScormAicc/classes/SCORM/class.ilSCORMItem.php");
		$sc_item =& new ilSCORMItem($_GET["obj_id"]);

		// title & header columns
		$user = new ilObjUser( $_GET["user_id"]);
		$tbl->setTitle($user->getLastname().", ".$user->getFirstname());

		$tbl->setHeaderNames(array($this->lng->txt("title"),
			$this->lng->txt("cont_status"), $this->lng->txt("cont_time"),
			$this->lng->txt("cont_score")));

		$header_params = array("ref_id" => $this->ref_id, "cmd" => $_GET["cmd"],
			"cmdClass" => get_class($this), "obj_id" => $_GET["obj_id"], "baseClass"=>"ilSAHSEditGUI", 'user_id'=>$_GET["user_id"]);
		$cols = array("title", "status", "time", "score");
		$tbl->setHeaderVars($cols, $header_params);
		//$tbl->setColumnWidth(array("25%",));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);

		//$this->tpl->setVariable("COLUMN_COUNTS",count($this->data["cols"]));
		//$this->showActions(true);

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		#$tbl->disable("footer");

		$tr_data = $this->object->getTrackingDataAgg($_GET["user_id"]);
		

		$tr_data = ilUtil::sortArray($tr_data, $_GET["sort_by"], $_GET["sort_order"]);
		$tbl->setMaxCount(count($tr_data));
		$tr_data = array_slice($tr_data, $_GET["offset"], $_GET["limit"]);

		$tbl->render();
		if (count($tr_data) > 0)
		{
			foreach ($tr_data as $data)
			{
					$this->tpl->setCurrentBlock("tbl_content");
					$this->tpl->setVariable("VAL_TITLE", $data["title"]);
					$this->ctrl->setParameter($this, "user_id",  $_GET["user_id"]);
					$this->ctrl->setParameter($this, "obj_id",  $data["sco_id"]);
					
					$this->tpl->setVariable("LINK_SCO",
						$this->ctrl->getLinkTarget($this, "showTrackingItemPerUser"));
					$this->tpl->setVariable("VAL_TIME", $data["time"]);
					$this->tpl->setVariable("VAL_STATUS", $data["status"]);
					$this->tpl->setVariable("VAL_SCORE", $data["score"]);
	
					$css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
					$this->tpl->setVariable("CSS_ROW", $css_row);
					$this->tpl->parseCurrentBlock();
			
			}
		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", $num);
			$this->tpl->parseCurrentBlock();
		}
	}
	
	
	/**
   * show tracking data of item
   */
   function showTrackingItemSco()
   { 
 	   global $ilTabs;

       include_once "./Services/Table/classes/class.ilTableGUI.php";
  
	   $this->setSubTabs();
	   $ilTabs->setTabActive("cont_tracking_data");
	   $ilTabs->setSubTabActive("cont_tracking_bysco");

       // load template for table
       $this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");
       // load template for table content data
       $this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.scorm_track_item_sco.html", "Modules/ScormAicc");
  
       $num = 2;
  
       $this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$this->ref_id."$obj_str&cmd=gateway");
  
       // create table
       $tbl = new ilTableGUI();
  
       include_once("./Modules/ScormAicc/classes/SCORM/class.ilSCORMItem.php");
       $sc_item =& new ilSCORMItem($_GET["obj_id"]);
  
       // title & header columns
       $tbl->setTitle($sc_item->getTitle());
  
       $tbl->setHeaderNames(array($this->lng->txt("name"),
           $this->lng->txt("cont_status"), $this->lng->txt("cont_time"),
           $this->lng->txt("cont_score")));
  
       $header_params = $this->ctrl->getParameterArray($this, "showTrackingItemSco");
		$header_params['obj_id'] = (int) $_GET['obj_id'];
  
       $cols = array("name", "status", "time", "score");
       $tbl->setHeaderVars($cols, $header_params);
       //$tbl->setColumnWidth(array("25%",));
  
       // control
       $tbl->setOrderColumn($_GET["sort_by"]);
       $tbl->setOrderDirection($_GET["sort_order"]);
       $tbl->setLimit($_GET["limit"]);
       $tbl->setOffset($_GET["offset"]);
       $tbl->setMaxCount($this->maxcount);
  
       //$this->tpl->setVariable("COLUMN_COUNTS",count($this->data["cols"]));
       //$this->showActions(true);
  
       // footer
       $tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
       #$tbl->disable("footer");
  
       $tr_data = $this->object->getTrackingDataAggSco($_GET["obj_id"]);
	   
	   $tmp = array();
		foreach($tr_data as $data)
		{
			$name = ilObjUser::_lookupName($data['user_id']);
			$data['name'] = $name['lastname'].', '.$name['firstname'];
			
			$tmp[] = $data;
		}
		$tr_data = $tmp;
		
       //$objs = ilUtil::sortArray($objs, $_GET["sort_by"], $_GET["sort_order"]);
       $tbl->setMaxCount(count($tr_data));

		$tr_data  = ilUtil::sortArray($tr_data ,$_GET["sort_by"],$_GET["sort_order"]);

       $tr_data = array_slice($tr_data, $_GET["offset"], $_GET["limit"]);
  
       $tbl->render();
       if (count($tr_data) > 0)
       {
           foreach ($tr_data as $data)
           {
               if (ilObject::_exists($data["user_id"]))
               {
                   $this->tpl->setCurrentBlock("tbl_content");
                   $user = new ilObjUser($data["user_id"]);
                   $this->tpl->setVariable("VAL_USERNAME", $user->getLastname().", ".
                       $user->getFirstname());
                   $this->ctrl->setParameter($this, "user_id", $data["user_id"]);
                   $this->ctrl->setParameter($this, "obj_id", $_GET["obj_id"]);
                   $this->tpl->setVariable("LINK_USER",
                       $this->ctrl->getLinkTarget($this, "showTrackingItemPerUser"));
                   $this->tpl->setVariable("VAL_TIME", $data["time"]);
                   $this->tpl->setVariable("VAL_STATUS", $data["status"]);
                   $this->tpl->setVariable("VAL_SCORE", $data["score"]);
  
                   $css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
                   $this->tpl->setVariable("CSS_ROW", $css_row);
                   $this->tpl->parseCurrentBlock();
               }
           }
       } //if is_array
       else
       {
           $this->tpl->setCurrentBlock("notfound");
           $this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
           $this->tpl->setVariable("NUM_COLS", $num);
           $this->tpl->parseCurrentBlock();
       }
	}

	/**
	* show tracking data of item per user
	*/
	function showTrackingItemPerUser()
	{
		global $ilTabs;
		
		$ilTabs->setTabActive("cont_tracking_data");
		
		include_once "./Services/Table/classes/class.ilTableGUI.php";

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");
		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.scorm_track_item_per_user.html", "Modules/ScormAicc");

		$num = 2;

		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$this->ref_id."$obj_str&cmd=gateway");

		// create table
		$tbl = new ilTableGUI();

		include_once("./Modules/ScormAicc/classes/SCORM/class.ilSCORMItem.php");
		$sc_item =& new ilSCORMItem($_GET["obj_id"]);
		$user = new ilObjUser($_GET["user_id"]);

		// title & header columns
		$tbl->setTitle($sc_item->getTitle()." - ".$user->getLastname().", ".$user->getFirstname());

		$tbl->setHeaderNames(array($this->lng->txt("cont_lvalue"), $this->lng->txt("cont_rvalue")));

		$header_params = array("ref_id" => $this->ref_id, "cmd" => $_GET["cmd"],
			"cmdClass" => get_class($this), "obj_id" => $_GET["obj_id"],
			"user_id" => $_GET["user_id"],"baseClass"=>"ilSAHSEditGUI");
		$cols = array("lvalue", "rvalue");
		$tbl->setHeaderVars($cols, $header_params);
		//$tbl->setColumnWidth(array("25%",));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);

		//$this->tpl->setVariable("COLUMN_COUNTS",count($this->data["cols"]));
		//$this->showActions(true);

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		#$tbl->disable("footer");

		$tr_data = $this->object->getTrackingDataPerUser($_GET["obj_id"], $_GET["user_id"]);

		//$objs = ilUtil::sortArray($objs, $_GET["sort_by"], $_GET["sort_order"]);
		$tbl->setMaxCount(count($tr_data));
		$tr_data = array_slice($tr_data, $_GET["offset"], $_GET["limit"]);

		$tbl->render();
		if (count($tr_data) > 0)
		{
			foreach ($tr_data as $data)
			{
				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("VAR", $data["lvalue"]);
				$this->tpl->setVariable("VAL", $data["rvalue"]);

				$css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->parseCurrentBlock();
			}
		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", $num);
			$this->tpl->parseCurrentBlock();
		}
	}
	
	//setTabs
	function setSubTabs()
	{
		global $lng, $ilTabs, $ilCtrl;
		
		$ilTabs->addSubTabTarget("cont_tracking_byuser",
			$this->ctrl->getLinkTarget($this, "showTrackingItems"), array("edit", ""),
			get_class($this));

		$ilTabs->addSubTabTarget("cont_tracking_bysco",
			$this->ctrl->getLinkTarget($this, "showTrackingItemsBySco"), array("edit", ""),
			get_class($this));
	}		

} // END class.ilObjSCORMLearningModule
?>
