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
* $Id$Id: class.ilObjSystemFolderGUI.php,v 1.19 2003/11/18 13:57:27 shofmann Exp $
*
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "class.ilObjectGUI.php";

class ilObjSystemFolderGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjSystemFolderGUI($a_data,$a_id,$a_call_by_reference)
	{
		$this->type = "adm";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference);
	}

	// display basicdata formular
	function viewObject()
	{
		global $tree, $rbacsystem, $tpl, $ilias,$lng, $styleDefinition;

		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		//prepare objectlist
		$this->objectList = array();
		$this->data["data"] = array();
		$this->data["ctrl"] = array();
		$this->data["cols"] = array("type", "title", "last_change");

		$childs = $tree->getChilds($_GET["ref_id"], $_GET["order"], $_GET["direction"]);

		foreach ($childs as $key => $val)
	    {
			// visible
			if (!$rbacsystem->checkAccess("visible",$val["ref_id"]))
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

		$this->displayList();
		
		$tpl->addBlockFile("SYSTEMSETTINGS", "systemsettings", "tpl.adm_basicdata.html");
		$tpl->setCurrentBlock("systemsettings");

		$settings = $ilias->getAllSettings();

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
				sendInfo($lng->txt("fill_out_all_required_fields"));
				$form_valid = false;
			}
			// check email adresses
			// feedback_recipient
			if (!ilUtil::is_email($_POST["feedback_recipient"]) and !empty($_POST["feedback_recipient"]) and $form_valid)
			{
				sendInfo($lng->txt("input_error").": '".$lng->txt("feedback_recipient")."'<br/>".$lng->txt("email_not_valid"));
				$form_valid = false;
			}

			// error_recipient
			if (!ilUtil::is_email($_POST["error_recipient"]) and !empty($_POST["error_recipient"]) and $form_valid)
			{
				sendInfo($lng->txt("input_error").": '".$lng->txt("error_recipient")."'<br/>".$lng->txt("email_not_valid"));
				$form_valid = false;
			}

			// admin email
			if (!ilUtil::is_email($_POST["admin_email"]) and $form_valid)
			{
				sendInfo($lng->txt("input_error").": '".$lng->txt("email")."'<br/>".$lng->txt("email_not_valid"));
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
				$ilias->setSetting("feedback_recipient",$_POST["feedback_recipient"]);
				$ilias->setSetting("error_recipient",$_POST["error_recipient"]);
				$ilias->ini->setVariable("language","default",$_POST["default_language"]);

				//set default skin and style
				if ($_POST["default_skin_style"] != "")
				{
					$sknst = explode(":", $_POST["default_skin_style"]);
					
					if ($ilias->ini->readVariable("layout","style") != $sknst[1] ||
						$ilias->ini->readVariable("layout","skin") != $sknst[0])
					{
						$ilias->ini->setVariable("layout","skin", $sknst[0]);
						$ilias->ini->setVariable("layout","style",$sknst[1]);
					}
				}

				// modules
				$ilias->setSetting("pub_section",$_POST["pub_section"]);
				$ilias->setSetting("enable_registration",$_POST["enable_registration"]);

				// contact
				$ilias->setSetting("admin_firstname",$_POST["admin_firstname"]);
				$ilias->setSetting("admin_lastname",$_POST["admin_lastname"]);
				$ilias->setSetting("admin_title",$_POST["admin_title"]);
				$ilias->setSetting("admin_position",$_POST["admin_position"]);
				$ilias->setSetting("admin_institution",$_POST["admin_institution"]);
				$ilias->setSetting("admin_street",$_POST["admin_street"]);
				$ilias->setSetting("admin_zipcode",$_POST["admin_zipcode"]);
				$ilias->setSetting("admin_city",$_POST["admin_city"]);
				$ilias->setSetting("admin_country",$_POST["admin_country"]);
				$ilias->setSetting("admin_phone",$_POST["admin_phone"]);
				$ilias->setSetting("admin_email",$_POST["admin_email"]);

				// write ini settings
				$ilias->ini->write();

				$settings = $ilias->getAllSettings();

				// feedback
				sendInfo($lng->txt("saved_successfully"));
			}
		}

		$tpl->setVariable("TXT_BASIC_DATA", $lng->txt("basic_data"));

		////////////////////////////////////////////////////////////
		// setting language vars

		// basic data
		$tpl->setVariable("TXT_ILIAS_VERSION", $lng->txt("ilias_version"));
		$tpl->setVariable("TXT_DB_VERSION", $lng->txt("db_version"));
		$tpl->setVariable("TXT_CLIENT_ID", $lng->txt("client_id"));
		$tpl->setVariable("TXT_INST_ID", $lng->txt("inst_id"));
		$tpl->setVariable("TXT_HOSTNAME", $lng->txt("host"));
		$tpl->setVariable("TXT_IP_ADDRESS", $lng->txt("ip_address"));
		$tpl->setVariable("TXT_SERVER_DATA", $lng->txt("server_data"));
		$tpl->setVariable("TXT_SERVER_PORT", $lng->txt("port"));
		$tpl->setVariable("TXT_SERVER_SOFTWARE", $lng->txt("server_software"));
		$tpl->setVariable("TXT_HTTP_PATH", $lng->txt("http_path"));
		$tpl->setVariable("TXT_ABSOLUTE_PATH", $lng->txt("absolute_path"));
		$tpl->setVariable("TXT_INST_NAME", $lng->txt("inst_name"));
		$tpl->setVariable("TXT_INST_INFO", $lng->txt("inst_info"));
		$tpl->setVariable("TXT_DEFAULT_SKIN_STYLE", $lng->txt("default_skin_style"));
		$tpl->setVariable("TXT_DEFAULT_LANGUAGE", $lng->txt("default_language"));
		$tpl->setVariable("TXT_FEEDBACK_RECIPIENT", $lng->txt("feedback_recipient"));
		$tpl->setVariable("TXT_ERROR_RECIPIENT", $lng->txt("error_recipient"));

		include ("./classes/class.ilDBUpdate.php");
		$dbupdate = new ilDBUpdate($ilias->db,true);

		if (!$dbupdate->getDBVersionStatus())
		{
			$tpl->setVariable("TXT_DB_UPDATE", "&nbsp;(<span class=\"warning\">".$lng->txt("db_need_update")."</span>)");
		}

		// modules
		//$tpl->setVariable("TXT_MODULES", $lng->txt("modules"));
		$tpl->setVariable("TXT_PUB_SECTION", $lng->txt("pub_section"));
		$tpl->setVariable("TXT_ENABLE_REGISTRATION", $lng->txt("enable_registration"));

		// pathes
		$tpl->setVariable("TXT_SOFTWARE", $lng->txt("3rd_party_software"));
		$tpl->setVariable("TXT_CONVERT_PATH", $lng->txt("path_to_convert"));
		$tpl->setVariable("TXT_ZIP_PATH", $lng->txt("path_to_zip"));
		$tpl->setVariable("TXT_UNZIP_PATH", $lng->txt("path_to_unzip"));
		$tpl->setVariable("TXT_JAVA_PATH", $lng->txt("path_to_java"));
		$tpl->setVariable("TXT_HTMLDOC_PATH", $lng->txt("path_to_htmldoc"));

		// contact
		$tpl->setVariable("TXT_CONTACT_DATA", $lng->txt("contact_data"));
		$tpl->setVariable("TXT_REQUIRED_FIELDS", $lng->txt("required_field"));
		$tpl->setVariable("TXT_ADMIN", $lng->txt("administrator"));
		$tpl->setVariable("TXT_FIRSTNAME", $lng->txt("firstname"));
		$tpl->setVariable("TXT_LASTNAME", $lng->txt("lastname"));
		$tpl->setVariable("TXT_TITLE", $lng->txt("title"));
		$tpl->setVariable("TXT_POSITION", $lng->txt("position"));
		$tpl->setVariable("TXT_INSTITUTION", $lng->txt("institution"));
		$tpl->setVariable("TXT_STREET", $lng->txt("street"));
		$tpl->setVariable("TXT_ZIPCODE", $lng->txt("zipcode"));
		$tpl->setVariable("TXT_CITY", $lng->txt("city"));
		$tpl->setVariable("TXT_COUNTRY", $lng->txt("country"));
		$tpl->setVariable("TXT_PHONE", $lng->txt("phone"));
		$tpl->setVariable("TXT_EMAIL", $lng->txt("email"));
		$tpl->setVariable("TXT_SAVE", $lng->txt("save"));

		///////////////////////////////////////////////////////////
		// display formula data

		// basic data
		$loc = "adm_object.php?ref_id=".$_GET["ref_id"];
		$tpl->setVariable("FORMACTION_BASICDATA", $loc);
		$tpl->setVariable("HTTP_PATH",ILIAS_HTTP_PATH);
		$tpl->setVariable("ABSOLUTE_PATH",ILIAS_ABSOLUTE_PATH);
		$tpl->setVariable("HOSTNAME", $_SERVER["SERVER_NAME"]);
		$tpl->setVariable("SERVER_PORT", $_SERVER["SERVER_PORT"]);
		$tpl->setVariable("SERVER_ADMIN", $_SERVER["SERVER_ADMIN"]);	// not used
		$tpl->setVariable("SERVER_SOFTWARE", $_SERVER["SERVER_SOFTWARE"]);
		$tpl->setVariable("IP_ADDRESS", $_SERVER["SERVER_ADDR"]);
		$tpl->setVariable("DB_VERSION",$settings["db_version"]);
		$tpl->setVariable("ILIAS_VERSION",$settings["ilias_version"]);
		$tpl->setVariable("INST_ID",$settings["inst_id"]);
		$tpl->setVariable("CLIENT_ID",CLIENT_ID);
		$tpl->setVariable("INST_NAME",$ilias->ini->readVariable("client","name"));
		$tpl->setVariable("INST_INFO",$ilias->ini->readVariable("client","description"));
		$tpl->setVariable("FEEDBACK_RECIPIENT",$settings["feedback_recipient"]);
		$tpl->setVariable("ERROR_RECIPIENT",$settings["error_recipient"]);

		// get all templates
		$templates = $styleDefinition->getAllTemplates();

		$tpl->setCurrentBlock("selectskin");

		foreach ($templates as $template)
		{
			// get styles definition for template
			$styleDef =& new ilStyleDefinition($template["id"]);
			$styleDef->startParsing();
			$styles = $styleDef->getStyles();

			foreach ($styles as $style)
			{
				if ($ilias->ini->readVariable("layout","skin") == $template["id"] &&
					$ilias->ini->readVariable("layout","style") == $style["id"])
				{
					$tpl->setVariable("SKINSELECTED", "selected=\"selected\"");
				}

				$tpl->setVariable("SKINVALUE", $template["id"].":".$style["id"]);
				$tpl->setVariable("SKINOPTION", $styleDef->getTemplateName()." / ".$style["name"]);
				$tpl->parseCurrentBlock();
			}
		}

		// language selection
		$languages = $lng->getInstalledLanguages();
		$tpl->setCurrentBlock("selectlanguage");

		foreach ($languages as $lang_key)
		{
			if ($ilias->ini->readVariable("language","default") == $lang_key)
			{
				$tpl->setVariable("LANGSELECTED", " selected=\"selected\"");
			}

			$tpl->setVariable("LANGVALUE", $lang_key);
			$tpl->setVariable("LANGOPTION", $lng->txt("lang_".$lang_key));	
			$tpl->parseCurrentBlock();
		}

		// modules
		if ($settings["pub_section"])
		{
			$tpl->setVariable("PUB_SECTION","checked=\"checked\"");
		}

		if ($settings["enable_registration"])
		{
			$tpl->setVariable("ENABLE_REGISTRATION","checked=\"checked\"");
		}

		// pathes to tools
		$not_set = $lng->txt("path_not_set");
		
		$tpl->setVariable("CONVERT_PATH",(PATH_TO_CONVERT) ? PATH_TO_CONVERT : $not_set);
		$tpl->setVariable("ZIP_PATH",(PATH_TO_ZIP) ? PATH_TO_ZIP : $not_set);
		$tpl->setVariable("UNZIP_PATH",(PATH_TO_UNZIP) ? PATH_TO_UNZIP : $not_set);
		$tpl->setVariable("JAVA_PATH",(PATH_TO_JAVA) ? PATH_TO_JAVA : $not_set);
		$tpl->setVariable("HTMLDOC_PATH",(PATH_TO_HTMLDOC) ? PATH_TO_HTMLDOC : $not_set);

		// contact
		$tpl->setVariable("ADMIN_FIRSTNAME",$settings["admin_firstname"]);
		$tpl->setVariable("ADMIN_LASTNAME",$settings["admin_lastname"]);
		$tpl->setVariable("ADMIN_TITLE",$settings["admin_title"]);
		$tpl->setVariable("ADMIN_POSITION",$settings["admin_position"]);
		$tpl->setVariable("ADMIN_INSTITUTION",$settings["admin_institution"]);
		$tpl->setVariable("ADMIN_STREET",$settings["admin_street"]);
		$tpl->setVariable("ADMIN_ZIPCODE",$settings["admin_zipcode"]);
		$tpl->setVariable("ADMIN_CITY",$settings["admin_city"]);
		$tpl->setVariable("ADMIN_COUNTRY",$settings["admin_country"]);
		$tpl->setVariable("ADMIN_PHONE",$settings["admin_phone"]);
		$tpl->setVariable("ADMIN_EMAIL",$settings["admin_email"]);

		$tpl->parseCurrentBlock();
	}

	/**
	* display object list
	*
	* @access	public
 	*/
	function displayList()
	{
		global $tree, $rbacsystem;

		require_once "./classes/class.ilTableGUI.php";

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
} // END class.ilObjSystemFolderGUI
?>
