<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/


/**
* Class ilObjSystemFolderGUI
*
* @author Stefan Meyer <smeyer@databay.de>
* $Id$Id: class.ilObjSystemFolderGUI.php,v 1.31 2004/04/08 20:49:03 akill Exp $
*
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "class.ilObjectGUI.php";

class ilObjSystemFolderGUI extends ilObjectGUI
{
	/**
	* ILIAS3 object type abbreviation
	* @var		string
	* @access	public
	*/
	var $type;

	/**
	* Constructor
	* @access public
	*/
	function ilObjSystemFolderGUI($a_data,$a_id,$a_call_by_reference)
	{
		$this->type = "adm";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference);
		
		$this->lng->loadLanguageModule("administration");
	}

	/**
	* show admin subpanels and basic settings form
	*
	* @access	public
	*/
	function viewObject()
	{
		global $rbacsystem, $styleDefinition;

		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		//prepare objectlist
		$this->objectList = array();
		$this->data["data"] = array();
		$this->data["ctrl"] = array();
		$this->data["cols"] = array("type", "title", "last_change");

		$childs = $this->tree->getChilds($this->object->getRefId(),$_GET["order"],$_GET["direction"]);

		foreach ($childs as $key => $val)
	    {
			// visible
			if (!$rbacsystem->checkAccess("visible",$val["ref_id"]))
			{
				continue;
			}
			
			// hide object types in devmode
			if ($this->objDefinition->getDevMode($val["type"]))
			{
				continue;
			}
			
			// hide RecoveryFolder if empty
			if ($val["ref_id"] == RECOVERY_FOLDER_ID and !$this->tree->getChilds(RECOVERY_FOLDER_ID))
			{
				continue;
			}

			//visible data part
			$this->data["data"][] = array(
										"type" => $val["type"],
										"title" => $val["title"]."#separator#".$val["desc"],
										//"description" => $val["desc"],
										"last_change" => $val["last_update"],
										"ref_id" => $val["ref_id"]
										);

			//control information is set below

	    } //foreach

		$this->maxcount = count($this->data["data"]);

		// sorting array
		$this->data["data"] = ilUtil::sortArray($this->data["data"],$_GET["sort_by"],$_GET["sort_order"]);

		// now compute control information
		foreach ($this->data["data"] as $key => $val)
		{
			$this->data["ctrl"][$key] = array(
											"type" => $val["type"],
											"ref_id" => $val["ref_id"]
											);

			unset($this->data["data"][$key]["ref_id"]);
						$this->data["data"][$key]["last_change"] = ilFormat::formatDate($this->data["data"][$key]["last_change"]);
		}

		// display admin subpanels
		$this->displayList();
		
		// display basic settings form
		if ($rbacsystem->checkAccess("write",$this->object->getRefId()))
		{
			$this->displayBasicSettings();
		}
	}

	/**
	* display object list
	*
	* @access	public
 	*/
	function displayList()
	{
		global $rbacsystem;

		include_once "./classes/class.ilTableGUI.php";

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");
		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.obj_tbl_rows.html");

		$num = 0;

		$obj_str = ($this->call_by_reference) ? "" : "&obj_id=".$this->obj_id;
		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$this->ref_id."$obj_str&cmd=gateway");

		// create table
		$tbl = new ilTableGUI();
		
		// title & header columns
		$tbl->setTitle($this->lng->txt("obj_".$this->object->getType()),"icon_".$this->object->getType()."_b.gif",$this->lng->txt("obj_".$this->object->getType()));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));
		
		foreach ($this->data["cols"] as $val)
		{
			$header_names[] = $this->lng->txt($val);
		}
		
		$tbl->setHeaderNames($header_names);
		
		$header_params = array("ref_id" => $this->ref_id);
		$tbl->setHeaderVars($this->data["cols"],$header_params);
		$tbl->setColumnWidth(array("15","75%","25%"));
		
		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit(0);
		$tbl->setOffset(0);
		$tbl->setMaxCount($this->maxcount);
		
		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		$tbl->disable("numinfo");
		
		// render table
		$tbl->render();

		if (is_array($this->data["data"][0]))
		{
			//table cell
			for ($i=0; $i < count($this->data["data"]); $i++)
			{
				$data = $this->data["data"][$i];
				$ctrl = $this->data["ctrl"][$i];

				// color changing
				$css_row = ilUtil::switchColor($i+1,"tblrow1","tblrow2");

				$this->tpl->setCurrentBlock("table_cell");
				$this->tpl->setVariable("CELLSTYLE", "tblrow1");
				$this->tpl->parseCurrentBlock();

				foreach ($data as $key => $val)
				{
					//build link
					$link = "adm_object.php?";

					$n = 0;

					foreach ($ctrl as $key2 => $val2)
					{
						$link .= $key2."=".$val2;

						if ($n < count($ctrl)-1)
						{
					    	$link .= "&";
							$n++;
						}
					}
					
					if ($key == "title")
					{
						$name_field = explode("#separator#",$val);
					}

					if ($key == "title" || $key == "type")
					{
						$this->tpl->setCurrentBlock("begin_link");
						$this->tpl->setVariable("LINK_TARGET", $link);

						$this->tpl->parseCurrentBlock();
						$this->tpl->touchBlock("end_link");
					}

					$this->tpl->setCurrentBlock("text");

					if ($key == "type")
					{
						$val = ilUtil::getImageTagByType($val,$this->tpl->tplPath);						
					}

					if ($key == "title")
					{
						$this->tpl->setVariable("TEXT_CONTENT", $name_field[0]);
						
						$this->tpl->setCurrentBlock("subtitle");
						$this->tpl->setVariable("DESC", $name_field[1]);
						$this->tpl->parseCurrentBlock();
					}
					else
					{
						$this->tpl->setVariable("TEXT_CONTENT", $val);
					}
				
					$this->tpl->parseCurrentBlock();

					$this->tpl->setCurrentBlock("table_cell");
					$this->tpl->parseCurrentBlock();

				} //foreach

				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->parseCurrentBlock();
			} //for

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
	* displays ILIAS basic settings form
	*
	* @access	private
	*/
	function displayBasicSettings()
	{
		global $rbacsystem, $styleDefinition;

		$this->tpl->addBlockFile("SYSTEMSETTINGS", "systemsettings", "tpl.adm_basicdata.html");
		$this->tpl->setCurrentBlock("systemsettings");

		$settings = $this->ilias->getAllSettings();

		if (isset($_POST["save_settings"]))  // formular sent
		{
			//init checking var
			$form_valid = true;

			// check required fields
			if (empty($_POST["admin_firstname"]) or empty($_POST["admin_lastname"])
				or empty($_POST["admin_street"]) or empty($_POST["admin_zipcode"])
				or empty($_POST["admin_country"]) or empty($_POST["admin_city"])
				or empty($_POST["admin_phone"]) or empty($_POST["admin_email"]))
			{
				// feedback
				sendInfo($this->lng->txt("fill_out_all_required_fields"));
				$form_valid = false;
			}
			// check email adresses
			// feedback_recipient
			if (!ilUtil::is_email($_POST["feedback_recipient"]) and !empty($_POST["feedback_recipient"]) and $form_valid)
			{
				sendInfo($this->lng->txt("input_error").": '".$this->lng->txt("feedback_recipient")."'<br/>".$this->lng->txt("email_not_valid"));
				$form_valid = false;
			}

			// error_recipient
			if (!ilUtil::is_email($_POST["error_recipient"]) and !empty($_POST["error_recipient"]) and $form_valid)
			{
				sendInfo($this->lng->txt("input_error").": '".$this->lng->txt("error_recipient")."'<br/>".$this->lng->txt("email_not_valid"));
				$form_valid = false;
			}

			// admin email
			if (!ilUtil::is_email($_POST["admin_email"]) and $form_valid)
			{
				sendInfo($this->lng->txt("input_error").": '".$this->lng->txt("email")."'<br/>".$this->lng->txt("email_not_valid"));
				$form_valid = false;
			}

			// prepare output
			foreach ($_POST as $key => $val)
			{
				$_POST[$key] = ilUtil::prepareFormOutput($val,true);
			}

			if (!$form_valid)	//required fields not satisfied. Set formular to already fill in values
			{
		////////////////////////////////////////////////////////////
		// load user modified settings again
		
				// basic data
				$settings["feedback_recipient"] = $_POST["feedback_recipient"];
				$settings["error_recipient"] = $_POST["error_recipient"];

				// modules
				$settings["pub_section"] = $_POST["pub_section"];
				$settings["enable_registration"] = $_POST["enable_registration"];

				// contact
				$settings["admin_firstname"] = $_POST["admin_firstname"];
				$settings["admin_lastname"] = $_POST["admin_lastname"];
				$settings["admin_title"] = $_POST["admin_title"];
				$settings["admin_position"] = $_POST["admin_position"];
				$settings["admin_institution"] = $_POST["admin_institution"];
				$settings["admin_street"] = $_POST["admin_street"];
				$settings["admin_zipcode"] = $_POST["admin_zipcode"];
				$settings["admin_city"] = $_POST["admin_city"];
				$settings["admin_country"] = $_POST["admin_country"];
				$settings["admin_phone"] = $_POST["admin_phone"];
				$settings["admin_email"] = $_POST["admin_email"];
			}
			else // all required fields ok
			{

		////////////////////////////////////////////////////////////
		// write new settings

				// basic data
				$this->ilias->setSetting("feedback_recipient",$_POST["feedback_recipient"]);
				$this->ilias->setSetting("error_recipient",$_POST["error_recipient"]);
				$this->ilias->ini->setVariable("language","default",$_POST["default_language"]);

				//set default skin and style
				if ($_POST["default_skin_style"] != "")
				{
					$sknst = explode(":", $_POST["default_skin_style"]);
					
					if ($this->ilias->ini->readVariable("layout","style") != $sknst[1] ||
						$this->ilias->ini->readVariable("layout","skin") != $sknst[0])
					{
						$this->ilias->ini->setVariable("layout","skin", $sknst[0]);
						$this->ilias->ini->setVariable("layout","style",$sknst[1]);
					}
				}

				// modules
				$this->ilias->setSetting("pub_section",$_POST["pub_section"]);
				$this->ilias->setSetting("enable_registration",$_POST["enable_registration"]);

				// contact
				$this->ilias->setSetting("admin_firstname",$_POST["admin_firstname"]);
				$this->ilias->setSetting("admin_lastname",$_POST["admin_lastname"]);
				$this->ilias->setSetting("admin_title",$_POST["admin_title"]);
				$this->ilias->setSetting("admin_position",$_POST["admin_position"]);
				$this->ilias->setSetting("admin_institution",$_POST["admin_institution"]);
				$this->ilias->setSetting("admin_street",$_POST["admin_street"]);
				$this->ilias->setSetting("admin_zipcode",$_POST["admin_zipcode"]);
				$this->ilias->setSetting("admin_city",$_POST["admin_city"]);
				$this->ilias->setSetting("admin_country",$_POST["admin_country"]);
				$this->ilias->setSetting("admin_phone",$_POST["admin_phone"]);
				$this->ilias->setSetting("admin_email",$_POST["admin_email"]);

				// write ini settings
				$this->ilias->ini->write();

				$settings = $this->ilias->getAllSettings();

				// feedback
				sendInfo($this->lng->txt("saved_successfully"));
			}
		}

		$this->tpl->setVariable("TXT_BASIC_DATA", $this->lng->txt("basic_data"));

		////////////////////////////////////////////////////////////
		// setting language vars

		// basic data
		$this->tpl->setVariable("TXT_ILIAS_VERSION", $this->lng->txt("ilias_version"));
		$this->tpl->setVariable("TXT_DB_VERSION", $this->lng->txt("db_version"));
		$this->tpl->setVariable("TXT_CLIENT_ID", $this->lng->txt("client_id"));
		$this->tpl->setVariable("TXT_INST_ID", $this->lng->txt("inst_id"));
		$this->tpl->setVariable("TXT_HOSTNAME", $this->lng->txt("host"));
		$this->tpl->setVariable("TXT_IP_ADDRESS", $this->lng->txt("ip_address"));
		$this->tpl->setVariable("TXT_SERVER_DATA", $this->lng->txt("server_data"));
		$this->tpl->setVariable("TXT_SERVER_PORT", $this->lng->txt("port"));
		$this->tpl->setVariable("TXT_SERVER_SOFTWARE", $this->lng->txt("server_software"));
		$this->tpl->setVariable("TXT_HTTP_PATH", $this->lng->txt("http_path"));
		$this->tpl->setVariable("TXT_ABSOLUTE_PATH", $this->lng->txt("absolute_path"));
		$this->tpl->setVariable("TXT_INST_NAME", $this->lng->txt("inst_name"));
		$this->tpl->setVariable("TXT_INST_INFO", $this->lng->txt("inst_info"));
		$this->tpl->setVariable("TXT_DEFAULT_SKIN_STYLE", $this->lng->txt("default_skin_style"));
		$this->tpl->setVariable("TXT_DEFAULT_LANGUAGE", $this->lng->txt("default_language"));
		$this->tpl->setVariable("TXT_FEEDBACK_RECIPIENT", $this->lng->txt("feedback_recipient"));
		$this->tpl->setVariable("TXT_ERROR_RECIPIENT", $this->lng->txt("error_recipient"));

		include_once ("./classes/class.ilDBUpdate.php");
		$dbupdate = new ilDBUpdate($this->ilias->db,true);

		if (!$dbupdate->getDBVersionStatus())
		{
			$this->tpl->setVariable("TXT_DB_UPDATE", "&nbsp;(<span class=\"warning\">".$this->lng->txt("db_need_update")."</span>)");
		}

		// modules
		//$this->tpl->setVariable("TXT_MODULES", $this->lng->txt("modules"));
		$this->tpl->setVariable("TXT_PUB_SECTION", $this->lng->txt("pub_section"));
		$this->tpl->setVariable("TXT_ENABLE_REGISTRATION", $this->lng->txt("enable_registration"));

		// pathes
		$this->tpl->setVariable("TXT_SOFTWARE", $this->lng->txt("3rd_party_software"));
		$this->tpl->setVariable("TXT_CONVERT_PATH", $this->lng->txt("path_to_convert"));
		$this->tpl->setVariable("TXT_ZIP_PATH", $this->lng->txt("path_to_zip"));
		$this->tpl->setVariable("TXT_UNZIP_PATH", $this->lng->txt("path_to_unzip"));
		$this->tpl->setVariable("TXT_JAVA_PATH", $this->lng->txt("path_to_java"));
		$this->tpl->setVariable("TXT_HTMLDOC_PATH", $this->lng->txt("path_to_htmldoc"));

		// contact
		$this->tpl->setVariable("TXT_CONTACT_DATA", $this->lng->txt("contact_data"));
		$this->tpl->setVariable("TXT_REQUIRED_FIELDS", $this->lng->txt("required_field"));
		$this->tpl->setVariable("TXT_ADMIN", $this->lng->txt("administrator"));
		$this->tpl->setVariable("TXT_FIRSTNAME", $this->lng->txt("firstname"));
		$this->tpl->setVariable("TXT_LASTNAME", $this->lng->txt("lastname"));
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TXT_POSITION", $this->lng->txt("position"));
		$this->tpl->setVariable("TXT_INSTITUTION", $this->lng->txt("institution"));
		$this->tpl->setVariable("TXT_STREET", $this->lng->txt("street"));
		$this->tpl->setVariable("TXT_ZIPCODE", $this->lng->txt("zipcode"));
		$this->tpl->setVariable("TXT_CITY", $this->lng->txt("city"));
		$this->tpl->setVariable("TXT_COUNTRY", $this->lng->txt("country"));
		$this->tpl->setVariable("TXT_PHONE", $this->lng->txt("phone"));
		$this->tpl->setVariable("TXT_EMAIL", $this->lng->txt("email"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));

		///////////////////////////////////////////////////////////
		// display formula data

		// basic data
		$loc = "adm_object.php?ref_id=".$this->object->getRefId();
		$this->tpl->setVariable("FORMACTION_BASICDATA", $loc);
		$this->tpl->setVariable("HTTP_PATH",ILIAS_HTTP_PATH);
		$this->tpl->setVariable("ABSOLUTE_PATH",ILIAS_ABSOLUTE_PATH);
		$this->tpl->setVariable("HOSTNAME", $_SERVER["SERVER_NAME"]);
		$this->tpl->setVariable("SERVER_PORT", $_SERVER["SERVER_PORT"]);
		$this->tpl->setVariable("SERVER_ADMIN", $_SERVER["SERVER_ADMIN"]);	// not used
		$this->tpl->setVariable("SERVER_SOFTWARE", $_SERVER["SERVER_SOFTWARE"]);
		$this->tpl->setVariable("IP_ADDRESS", $_SERVER["SERVER_ADDR"]);
		$this->tpl->setVariable("DB_VERSION",$settings["db_version"]);
		$this->tpl->setVariable("ILIAS_VERSION",$settings["ilias_version"]);
		$this->tpl->setVariable("INST_ID",$settings["inst_id"]);
		$this->tpl->setVariable("CLIENT_ID",CLIENT_ID);
		$this->tpl->setVariable("INST_NAME",$this->ilias->ini->readVariable("client","name"));
		$this->tpl->setVariable("INST_INFO",$this->ilias->ini->readVariable("client","description"));
		$this->tpl->setVariable("FEEDBACK_RECIPIENT",$settings["feedback_recipient"]);
		$this->tpl->setVariable("ERROR_RECIPIENT",$settings["error_recipient"]);

		// get all templates
		$templates = $styleDefinition->getAllTemplates();

		$this->tpl->setCurrentBlock("selectskin");

		foreach ($templates as $template)
		{
			// get styles definition for template
			$styleDef =& new ilStyleDefinition($template["id"]);
			$styleDef->startParsing();
			$styles = $styleDef->getStyles();

			foreach ($styles as $style)
			{
				if ($this->ilias->ini->readVariable("layout","skin") == $template["id"] &&
					$this->ilias->ini->readVariable("layout","style") == $style["id"])
				{
					$this->tpl->setVariable("SKINSELECTED", "selected=\"selected\"");
				}

				$this->tpl->setVariable("SKINVALUE", $template["id"].":".$style["id"]);
				$this->tpl->setVariable("SKINOPTION", $styleDef->getTemplateName()." / ".$style["name"]);
				$this->tpl->parseCurrentBlock();
			}
		}

		// language selection
		$languages = $this->lng->getInstalledLanguages();
		$this->tpl->setCurrentBlock("selectlanguage");

		foreach ($languages as $lang_key)
		{
			if ($this->ilias->ini->readVariable("language","default") == $lang_key)
			{
				$this->tpl->setVariable("LANGSELECTED", " selected=\"selected\"");
			}

			$this->tpl->setVariable("LANGVALUE", $lang_key);
			$this->tpl->setVariable("LANGOPTION", $this->lng->txt("lang_".$lang_key));	
			$this->tpl->parseCurrentBlock();
		}

		// modules
		if ($settings["pub_section"])
		{
			$this->tpl->setVariable("PUB_SECTION","checked=\"checked\"");
		}

		if ($settings["enable_registration"])
		{
			$this->tpl->setVariable("ENABLE_REGISTRATION","checked=\"checked\"");
		}

		// pathes to tools
		$not_set = $this->lng->txt("path_not_set");
		
		$this->tpl->setVariable("CONVERT_PATH",(PATH_TO_CONVERT) ? PATH_TO_CONVERT : $not_set);
		$this->tpl->setVariable("ZIP_PATH",(PATH_TO_ZIP) ? PATH_TO_ZIP : $not_set);
		$this->tpl->setVariable("UNZIP_PATH",(PATH_TO_UNZIP) ? PATH_TO_UNZIP : $not_set);
		$this->tpl->setVariable("JAVA_PATH",(PATH_TO_JAVA) ? PATH_TO_JAVA : $not_set);
		$this->tpl->setVariable("HTMLDOC_PATH",(PATH_TO_HTMLDOC) ? PATH_TO_HTMLDOC : $not_set);

		// contact
		$this->tpl->setVariable("ADMIN_FIRSTNAME",$settings["admin_firstname"]);
		$this->tpl->setVariable("ADMIN_LASTNAME",$settings["admin_lastname"]);
		$this->tpl->setVariable("ADMIN_TITLE",$settings["admin_title"]);
		$this->tpl->setVariable("ADMIN_POSITION",$settings["admin_position"]);
		$this->tpl->setVariable("ADMIN_INSTITUTION",$settings["admin_institution"]);
		$this->tpl->setVariable("ADMIN_STREET",$settings["admin_street"]);
		$this->tpl->setVariable("ADMIN_ZIPCODE",$settings["admin_zipcode"]);
		$this->tpl->setVariable("ADMIN_CITY",$settings["admin_city"]);
		$this->tpl->setVariable("ADMIN_COUNTRY",$settings["admin_country"]);
		$this->tpl->setVariable("ADMIN_PHONE",$settings["admin_phone"]);
		$this->tpl->setVariable("ADMIN_EMAIL",$settings["admin_email"]);

		$this->tpl->parseCurrentBlock();
	}
	
	function checkObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		if ($_POST["systemcheck"])
		{
			return $this->viewScanLog();
		}
		
		if ($_POST["mode"])
		{
			$this->startValidator($_POST["mode"],$_POST["log_scan"]);
		}
		else
		{
			include_once "classes/class.ilValidator.php";
			$validator = new ilValidator();
			$last_scan = $validator->readScanLog();

			$this->getTemplateFile("check");

			if (is_array($last_scan))
			{
				$this->tpl->setVariable("TXT_VIEW_LOG", $this->lng->txt("view_last_log"));
			}

			$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("systemcheck"));
			$this->tpl->setVariable("COLSPAN", 3);
			$this->tpl->setVariable("TXT_OPTIONS", $this->lng->txt("options"));
			$this->tpl->setVariable("TXT_ANALYZE_TITLE", $this->lng->txt("analyze_data"));
			$this->tpl->setVariable("TXT_ANALYZE", $this->lng->txt("scan_only"));
			$this->tpl->setVariable("TXT_ANALYZE_DESC", $this->lng->txt("analyze_desc"));
			$this->tpl->setVariable("TXT_CLEAN", $this->lng->txt("clean"));
			$this->tpl->setVariable("TXT_CLEAN_DESC", $this->lng->txt("clean_desc"));
			$this->tpl->setVariable("TXT_RESTORE", $this->lng->txt("restore_missing"));
			$this->tpl->setVariable("TXT_RESTORE_DESC", $this->lng->txt("restore_missing_desc"));
			$this->tpl->setVariable("TXT_PURGE", $this->lng->txt("purge_missing"));
			$this->tpl->setVariable("TXT_PURGE_DESC", $this->lng->txt("purge_missing_desc"));
			$this->tpl->setVariable("TXT_RESTORE_TRASH", $this->lng->txt("restore_trash"));
			$this->tpl->setVariable("TXT_RESTORE_TRASH_DESC", $this->lng->txt("restore_trash_desc"));
			$this->tpl->setVariable("TXT_PURGE_TRASH", $this->lng->txt("purge_trash"));
			$this->tpl->setVariable("TXT_PURGE_TRASH_DESC", $this->lng->txt("purge_trash_desc"));
			$this->tpl->setVariable("TXT_LOG_SCAN", $this->lng->txt("log_scan"));
			$this->tpl->setVariable("TXT_LOG_SCAN_DESC", $this->lng->txt("log_scan_desc"));
			$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt("start_scan"));
		}
	}
	
	function startValidator($a_mode,$a_log)
	{
		global $rbacsystem; 

		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		$logging = ($a_log) ? true : false;
		include_once "classes/class.ilValidator.php";
		$validator = new ilValidator($logging);
		$validator->setMode("all",false);

		foreach ($a_mode as $mode => $value)
		{
			$validator->setMode($mode,(bool) $value);
		}
		
		// STEP 1: Analyzing: Get all incomplete entries
		$scan_log .= $this->lng->txt("analyzing");
		
		if (!$validator->isModeEnabled("analyze"))
		{
			$scan_log .= $this->lng->txt("disabled");
		}
		else
		{
			$scan_log .= "<br />".$this->lng->txt("searching_invalid_refs");
			if ($validator->findInvalidReferences())
			{
				$scan_log .= count($validator->getInvalidReferences())." ".$this->lng->txt("found");
			}
			else
			{
				$scan_log .= $this->lng->txt("found_none");
			}
			
			$scan_log .= "<br />".$this->lng->txt("searching_invalid_childs");
			if ($validator->findInvalidChilds())
			{
				$scan_log .= count($validator->getInvalidChilds())." ".$this->lng->txt("found");

			}
			else
			{
				$scan_log .= $this->lng->txt("found_none");
			}
			
			$scan_log .= "<br />".$this->lng->txt("searching_missing_objs");
			if ($validator->findMissingObjects())
			{
				$scan_log .= count($validator->getMissingObjects())." ".$this->lng->txt("found");

			}
			else
			{
				$scan_log .= $this->lng->txt("found_none");
			}
		
			$scan_log .= "<br />".$this->lng->txt("searching_unbound_objs");
			if ($validator->findUnboundObjects())
			{
				$scan_log .=  count($validator->getUnboundObjects())." ".$this->lng->txt("found");

			}
			else
			{
				$scan_log .= $this->lng->txt("found_none");
			}
		
			$scan_log .= "<br />".$this->lng->txt("searching_deleted_objs");
			if ($validator->findDeletedObjects())
			{
				$scan_log .= count($validator->getDeletedObjects())." ".$this->lng->txt("found");

			}
			else
			{
				$scan_log .= $this->lng->txt("found_none");
			}
		}
		
		// STEP 2: Cleaning: Remove unbound references & tree entries
		$scan_log .= "<br /><br />".$this->lng->txt("cleaning");
		
		if (!$validator->isModeEnabled("clean"))
		{
			$scan_log .= $this->lng->txt("disabled");
		}
		else
		{
			$scan_log .= "<br />".$this->lng->txt("removing_invalid_refs");
			if ($validator->removeInvalidReferences())
			{
				$scan_log .= strtolower($this->lng->txt("done"));
			}
			else
			{
				$scan_log .= $this->lng->txt("nothing_to_remove").$this->lng->txt("skipped");
			}
			
			$scan_log .= "<br />".$this->lng->txt("removing_invalid_childs");
			if ($validator->removeInvalidChilds())
			{
				$scan_log .= strtolower($this->lng->txt("done"));
			}
			else
			{
				$scan_log .= $this->lng->txt("nothing_to_remove").$this->lng->txt("skipped");
			}
		}

		// find unbound objects again AFTER cleaning process!
		$validator->findUnboundObjects();
		
		// STEP 3: Restore objects
		$scan_log .= "<br /><br />".$this->lng->txt("restoring");
		
		if (!$validator->isModeEnabled("restore"))
		{
			$scan_log .= $this->lng->txt("disabled");
		}
		else
		{
			$scan_log .= "<br />".$this->lng->txt("restoring_missing_objs");
			if ($validator->restoreMissingObjects())
			{
				$scan_log .= strtolower($this->lng->txt("done"));
			}
			else
			{
				$scan_log .= $this->lng->txt("nothing_to_restore").$this->lng->txt("skipped");
			}
			
			$scan_log .= "<br />".$this->lng->txt("restoring_unbound_objs");
			if ($validator->restoreUnboundObjects())
			{
				$scan_log .= strtolower($this->lng->txt("done"));
			}
			else
			{
				$scan_log .= $this->lng->txt("nothing_to_restore").$this->lng->txt("skipped");
			}
		}
		
		// STEP 4: Restoring Trash
		$scan_log .= "<br /><br />".$this->lng->txt("restoring_trash");

		if (!$validator->isModeEnabled("restore_trash"))
		{
			$scan_log .= $this->lng->txt("disabled");
		}
		else
		{
			if ($validator->restoreTrash())
			{
				$scan_log .= strtolower($this->lng->txt("done"));
			}
			else
			{
				$scan_log .= $this->lng->txt("nothing_to_restore").$this->lng->txt("skipped");
			}
		}
		
		// STEP 5: Purging...
		$scan_log .= "<br /><br />".$this->lng->txt("purging");
		
		if (!$validator->isModeEnabled("purge"))
		{
			$scan_log .= $this->lng->txt("disabled");
		}
		else
		{
			$scan_log .= "<br />".$this->lng->txt("purging_missing_objs");
			if ($validator->purgeMissingObjects())
			{
				$scan_log .= strtolower($this->lng->txt("done"));
			}
			else
			{
				$scan_log .= $this->lng->txt("nothing_to_purge").$this->lng->txt("skipped");
			}

			$scan_log .= "<br />".$this->lng->txt("purging_unbound_objs");
			if ($validator->purgeUnboundObjects())
			{
				$scan_log .= strtolower($this->lng->txt("done"));
			}
			else
			{
				$scan_log .= $this->lng->txt("nothing_to_purge").$this->lng->txt("skipped");
			}
		}

		// STEP 6: Purging trash...
		$scan_log .= "<br /><br />".$this->lng->txt("purging_trash");
		
		if (!$validator->isModeEnabled("purge_trash"))
		{
			$scan_log .= $this->lng->txt("disabled");
		}
		else
		{
			if ($validator->purgeTrash())
			{
				$scan_log .= strtolower($this->lng->txt("done"));
			}
			else
			{
				$scan_log .= $this->lng->txt("nothing_to_purge").$this->lng->txt("skipped");
			}
		}
		
		// STEP 6: Close gaps in tree
		if ($validator->isModeEnabled("clean"))
		{
			$scan_log .= "<br /><br />".$this->lng->txt("cleaning_final");
			if ($validator->closeGapsInTree())
			{
				$scan_log .= "<br />".$this->lng->txt("closing_gaps")." ".strtolower($this->lng->txt("done"));
			}
		}
		
		// check RBAC starts here
		// ...
		
		// el fin
		foreach ($validator->mode as $mode => $value)
		{
			$arr[] = $mode."[".(int)$value."]";
		}
		
		$scan_log .= "<br /><br />".$this->lng->txt("scan_completed");

	
		$mode = $this->lng->txt("scan_modes").": ".implode(', ',$arr);
		
		// output
		$this->getTemplateFile("scan");
		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$this->ref_id."&cmd=check");
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("scanning_system"));
		$this->tpl->setVariable("COLSPAN", 3);
		$this->tpl->setVariable("TXT_SCAN_LOG", $scan_log);
		$this->tpl->setVariable("TXT_MODE", $mode);
		
		if ($logging === true)
		{
			$this->tpl->setVariable("TXT_VIEW_LOG", $this->lng->txt("view_log"));
		}

		$this->tpl->setVariable("TXT_DONE", $this->lng->txt("done"));

		$validator->writeScanLogLine($mode);
	}

	function viewScanLog()
	{
		include_once "classes/class.ilValidator.php";
		$validator = new IlValidator();
		$scan_log = $validator->readScanLog();

		if (is_array($scan_log))
		{
			$scan_log = nl2br(implode("",$scan_log));
			$this->tpl->setVariable("ADM_CONTENT", $scan_log);
		}
		else
		{
			$scan_log = "no scanlog found.";
		}

		// output
		$this->getTemplateFile("scan");
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("scan_details"));
		$this->tpl->setVariable("COLSPAN", 3);
		$this->tpl->setVariable("TXT_SCAN_LOG", $scan_log);
		$this->tpl->setVariable("TXT_DONE", $this->lng->txt("done"));
	}


	/**
	* view benchmark settings
	*/
	function benchmarkObject()
	{
		global $ilBench;

		$this->getTemplateFile("bench");
		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$_GET["ref_id"]."&cur_mod=".$_GET["cur_mod"]."&cmd=gateway");
		$this->tpl->setVariable("TXT_BENCH_SETTINGS", $this->lng->txt("benchmark_settings"));
		$this->tpl->setVariable("TXT_ACTIVATION", $this->lng->txt("activation"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save_settings"));
		$this->tpl->setVariable("TXT_CUR_RECORDS", $this->lng->txt("cur_number_rec"));
		$this->tpl->setVariable("VAL_CUR_RECORDS", $ilBench->getCurrentRecordNumber());
		$this->tpl->setVariable("TXT_MAX_RECORDS", $this->lng->txt("max_number_rec"));
		$this->tpl->setVariable("VAL_MAX_RECORDS", $ilBench->getMaximumRecords());
		$this->tpl->setVariable("TXT_CLEAR", $this->lng->txt("delete_all_rec"));
		if($ilBench->isEnabled())
		{
			$this->tpl->setVariable("ACT_CHECKED", " checked=\"1\" ");
		}

		$modules = $ilBench->getMeasuredModules();

		if (count($modules) > 0)
		{
			$this->tpl->setCurrentBlock("eval_table");

			$cur_module = ($_GET["cur_mod"] != "" &&
				in_array($_GET["cur_mod"], $modules))
				? $_GET["cur_mod"]
				: current($modules);

			$benchs = $ilBench->getEvaluation($cur_module);

			$i=0;
			foreach($benchs as $bench)
			{
				$this->tpl->setCurrentBlock("eval_row");
				$this->tpl->setVariable("ROWCOL",
					ilUtil::switchColor($i++, "tblrow2", "tblrow1"));

				$this->tpl->setVariable("VAL_BENCH", $bench["benchmark"]);
				$this->tpl->setVariable("VAL_NUMBER_RECORDS", $bench["cnt"]);
				$this->tpl->setVariable("VAL_AVG_TIME", $bench["duration"]);
				$this->tpl->setVariable("VAL_MIN_TIME", $bench["min"]);
				$this->tpl->setVariable("VAL_MAX_TIME", $bench["max"]);
				$this->tpl->setVariable("VAL_CUM_TIME", $bench["duration"] * $bench["cnt"]);
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setVariable("SELECT_MODULE",
				ilUtil::formSelect($cur_module, "module",$modules, false, true));

			$this->tpl->setVariable("TXT_SHOW", $this->lng->txt("show"));
			$this->tpl->setVariable("TXT_BENCH", $this->lng->txt("benchmark"));
			$this->tpl->setVariable("TXT_NUMBER_RECORDS", $this->lng->txt("number_of_records"));
			$this->tpl->setVariable("TXT_AVG_TIME", $this->lng->txt("average_time"));
			$this->tpl->setVariable("TXT_MIN_TIME", $this->lng->txt("min_time"));
			$this->tpl->setVariable("TXT_MAX_TIME", $this->lng->txt("max_time"));
			$this->tpl->setVariable("TXT_CUM_TIME", $this->lng->txt("cumulative_time"));

			$this->tpl->parseCurrentBlock();
		}
	}


	/**
	* save benchmark settings
	*/
	function saveBenchSettingsObject()
	{
		global $ilBench;

		if ($_POST["activate"] == "y")
		{
			$ilBench->enable(true);
		}
		else
		{
			$ilBench->enable(false);
		}
//echo ":".$_POST["max_records"].":<br>"; exit;
		$ilBench->setMaximumRecords($_POST["max_records"]);

		sendinfo($this->lng->txt("msg_obj_modified"), true);

		ilUtil::redirect("adm_object.php?cur_mod=".$_POST["module"]."&ref_id=".$_GET["ref_id"]."&cmd=benchmark");
	}


	/**
	* save benchmark settings
	*/
	function switchBenchModuleObject()
	{
		global $ilBench;

		ilUtil::redirect("adm_object.php?cur_mod=".$_POST["module"]."&ref_id=".$_GET["ref_id"]."&cmd=benchmark");
	}


	/**
	* delete all benchmark records
	*/
	function clearBenchObject()
	{
		global $ilBench;

		$ilBench->clearData();
		$this->saveBenchSettingsObject();

	}

} // END class.ilObjSystemFolderGUI
?>
