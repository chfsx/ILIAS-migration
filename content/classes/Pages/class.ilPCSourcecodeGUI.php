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

require_once("./content/classes/Pages/class.ilPCParagraph.php");
require_once("./content/classes/Pages/class.ilPageContentGUI.php");

/**
* Class ilPCSourcecodeGUI
*
* User Interface for Paragraph Editing
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilPCSourcecodeGUI extends ilPageContentGUI
{
	
	/**
	* Constructor
	* @access	public
	*/
	function ilPCSourcecodeGUI(&$a_pg_obj, &$a_content_obj, $a_hier_id)
	{
		parent::ilPageContentGUI($a_pg_obj, $a_content_obj, $a_hier_id);
	}


	/**
	* execute command
	*/
	function &executeCommand()
	{
		// get next class that processes or forwards current command
		$next_class = $this->ctrl->getNextClass($this);

		// get current command
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				$ret =& $this->$cmd();
				break;
		}

		return $ret;
	}

	/**
	* edit paragraph form
	*/
	function edit()
	{
		// set tabs
		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.sourcecode_edit.html", true);
		//$content = $this->pg_obj->getContent();
		//$cnt = 1;
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_edit_src"));
		
		if ($this->pg_obj->getParentType() == "lm" ||
			$this->pg_obj->getParentType() == "dbk")
		{
			$this->tpl->setVariable("LINK_ILINK",
			$this->ctrl->getLinkTargetByClass("ilInternalLinkGUI", "showLinkHelp"));
			$this->tpl->setVariable("TXT_ILINK", "[".$this->lng->txt("cont_internal_link")."]");
		}

		$this->displayValidationError();

		// language and characteristic selection
		if (key($_POST["cmd"]) == "update")
		{
			$s_lang = $_POST["par_language"];
			$s_char = $_POST["par_characteristic"];
			$s_isexample = $_POST["par_isexample"];
			if (strcmp ($s_isexample,"on") == 0 )
			{
				$s_char = "Code-Example";
			}
			else
			{
				$s_char = "Code";
			}
			$s_subchar = $_POST["par_subcharacteristic"];
			$s_downloadtitle = $_POST["par_downloadtitle"];
			$s_showlinenumbers = $_POST["par_showlinenumbers"];
			if (strcmp($s_showlinenumbers, 'on') == 0)
			{
				$s_showlinenumbers = 'y';
			}
			else
			{
				$s_showlinenumbers = 'n';
			}
			
		}
		else
		{
			$s_lang = $this->content_obj->getLanguage();
			$s_char = $this->content_obj->getCharacteristic();
			
			if ($s_char == "Code-Example")
			{
				$s_isexample = 'y';
			}
			else
			{
				$s_isexample = 'n';
			}

			$s_subchar = $this->content_obj->getSubCharacteristic();			
			$s_downloadtitle = $this->content_obj->getDownloadTitle();
			$s_showlinenumbers = $this->content_obj->getShowLineNumbers();					
		}
				
		$this->setTemplateText($s_lang, $s_subchar);
		
		if (key($_POST["cmd"]) == "update")
		{
			$s_text = stripslashes($_POST["par_content"]);
		}
		else
		{
			$s_text = $this->content_obj->xml2output($this->content_obj->getText());
		}

		$this->tpl->setVariable("PAR_TA_NAME", "par_content");
		$this->tpl->setVariable("PAR_TA_CONTENT", $s_text);
		$this->tpl->parseCurrentBlock();
			
		if (strcmp($s_showlinenumbers,"y")==0)
		{
			$this->tpl->setVariable("SHOWLINENUMBERS", "checked=\"checked\"");
		}
		
		if (strcmp($s_isexample,"y") == 0)
		{
			$this->tpl->setVariable("ISEXAMPLE", "checked=\"checked\"");
		}
		

		$this->tpl->setVariable("DOWNLOAD_TITLE_VALUE", $s_downloadtitle);
		
		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "update");
		$this->tpl->setVariable("UPLOAD_BTN_NAME", "upload");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();

	}


	function setTemplateText ($s_lang, $s_proglang) {
		$this->tpl->setVariable ("TXT_CREATEFILE", $this->lng->txt("create_download_link"));
		$this->tpl->setVariable ("TXT_DOWNLOADTITLE", $this->lng->txt("cont_download_title"));
		$this->tpl->setVariable ("TXT_IMPORTFILE", $this->lng->txt("import_file"));
		$this->tpl->setVariable ("TXT_UPLOAD_BTN", $this->lng->txt("import"));
		$this->tpl->setVariable ("TXT_SUBCHARACTERISTIC", $this->lng->txt("cont_src"));
		$this->tpl->setVariable ("TXT_LANGUAGE", $this->lng->txt("language"));
		$this->tpl->setVariable ("TXT_SHOWLINENUMBERS", $this->lng->txt("cont_show_line_numbers"));

				
		$this->tpl->setVariable ("FORMACTION", $this->ctrl->getFormAction($this));		
		
		require_once("classes/class.ilMetaData.php");
		$lang = ilMetaData::getLanguages();
		$select_lang = ilUtil::formSelect ($s_lang,"par_language",$lang,false,true);
		$this->tpl->setVariable ("SELECT_LANGUAGE", $select_lang);

		$prog_langs = $this->readProgLangs ();
				
		$select_subchar = ilUtil::formSelect ($s_proglang, "par_subcharacteristic",$prog_langs,false,true);
		$this->tpl->setVariable ("SELECT_SUBCHARACTERISTIC", $select_subchar);	
		
	}
	
	/**
	* insert paragraph form
	*/
	function insert()
	{
		global $ilUser;

		// set tabs
		$this->setTabs();

		// add paragraph edit template
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.sourcecode_edit.html", true);
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_insert_src"));
		
		if ($this->pg_obj->getParentType() == "lm" ||
			$this->pg_obj->getParentType() == "dbk")
		{
			$this->tpl->setVariable("LINK_ILINK",
				$this->ctrl->getLinkTargetByClass("ilInternalLinkGUI", "showLinkHelp"));
			$this->tpl->setVariable("TXT_ILINK", "[".$this->lng->txt("cont_internal_link")."]");
		}

		$this->displayValidationError();

		// get values from new object (repeated form display on error)
		
		//echo key ($_POST["cmd"]);
		
		if (key($_POST["cmd"]) == "create_src")
		{
			$s_lang = $_POST["par_language"];			
			$s_subchar = $_POST["par_subcharacteristic"];
			$s_downloadtitle = $_POST["par_downloadtitle"];
			$s_showlinenumbers = strcmp($_POST["par_showlinenumbers"],'on')==0?'checked=\"true\"':'';	
			$s_isexample = strcmp($_POST["par_isexample"],"on")==0?'checked=\"true\"':'';			
		}
		else
		{
			if ($_SESSION["il_text_lang_".$_GET["ref_id"]] != "")
			{
				$s_lang = $_SESSION["il_text_lang_".$_GET["ref_id"]];
			}
			else
			{
				$s_lang = $ilUser->getLanguage();
			}
			
			$s_showlinenumbers = 'CHECKED';
			$s_isexample = '';			
			$s_subchar = '';
		}
		
		$this->setTemplateText($s_lang, $s_subchar);

		$this->tpl->setVariable("SHOWLINENUMBERS", $s_showlinenumbers);
		$this->tpl->setVariable("DOWNLOAD_TITLE_VALUE", $s_downloadtitle);
		$this->tpl->setVariable("ISEXAMPLE", $s_isexample);

				
		// content is in utf-8, todo: set globally
		// header('Content-type: text/html; charset=UTF-8');

		// input text area
		$this->tpl->setVariable("PAR_TA_NAME", "par_content");
		
		if (key($_POST["cmd"]) == "create_src")
		{
			$this->tpl->setVariable("PAR_TA_CONTENT", stripslashes($_POST["par_content"]));
		}
		else
		{
			$this->tpl->setVariable("PAR_TA_CONTENT", "");
		}
		$this->tpl->parseCurrentBlock();

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "create_src");	//--		
		$this->tpl->setVariable("UPLOAD_BTN_NAME", "create_src");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();

	}


	/**
	* update paragraph in dom and update page in db
	*/
	function update()
	{
		global $ilBench;

		$ilBench->start("Editor","Paragraph_update");
		// set language and characteristic
		
		$this->content_obj->setLanguage($_POST["par_language"]);
		$this->content_obj->setCharacteristic($_POST["par_characteristic"]);

		//echo "PARupdate:".htmlentities($this->content_obj->input2xml($_POST["par_content"])).":<br>"; exit;

		 
		// set language and characteristic
		$this->content_obj->setLanguage($_POST["par_language"]);
		$this->content_obj->setSubCharacteristic($_POST["par_subcharacteristic"]);
		$this->content_obj->setDownloadTitle($_POST["par_downloadtitle"]);
		
		$s_showlinenumbers = $_POST["par_showlinenumbers"];
		if ($s_showlinenumbers == 'on')
		{
			$s_showlinenumbers = 'y';
		}
		else
		{
			$s_showlinenumbers = 'n';
		}
		
		$this->content_obj->setShowLineNumbers($s_showlinenumbers);
		$s_isexample = $_POST["par_isexample"];
			
		if ($s_isexample == 'on')
		{
			$this->content_obj->setCharacteristic("Code-Example");
		}
		else
		{
			$this->content_obj->setCharacteristic("Code");
		}
		

		$this->updated = $this->content_obj->setText($this->content_obj->input2xml(stripslashes($_POST["par_content"])));

		if ($this->updated !== true)
		{
			//echo "Did not update!";
			$ilBench->stop("Editor","Paragraph_update");
			$this->edit();
			return;
		}

		$this->updated = $this->pg_obj->update();

		$ilBench->stop("Editor","Paragraph_update");

		if ($this->updated === true && $this->ctrl->getCmd () != "upload" )
		{
			$this->ctrl->returnToParent($this, "jump".$this->hier_id);
		}
		else
		{
			$this->edit();
		}
	}

	/**
	* create new paragraph in dom and update page in db
	*/
	function create()
	{	
		$this->content_obj =& new ilPCParagraph($this->dom);
		$this->content_obj->create($this->pg_obj, $this->hier_id);
		$this->content_obj->setLanguage($_POST["par_language"]);

		$_SESSION["il_text_lang_".$_GET["ref_id"]] = $_POST["par_language"];

		$uploaded = $this->upload_source();
				
		$this->content_obj->setCharacteristic   ($_POST["par_characteristic"]);
		$this->content_obj->setSubCharacteristic($_POST["par_subcharacteristic"]);
		$this->content_obj->setDownloadTitle    ($_POST["par_downloadtitle"]);
		$this->content_obj->setShowLineNumbers  (($_POST["par_showlinenumbers"]=='on')?'y':'n');
		$this->content_obj->setCharacteristic   (($_POST["par_isexample"]=='on')?'Code-Example':'Code');
				
		if ($uploaded) {
			$this->insert ();
			return;
		}
		
		$this->updated = $this->content_obj->setText($this->content_obj->input2xml($_POST["par_content"]));
		
		if ($this->updated !== true)
		{
			$this->insert();
			return;
		}
		
		$this->updated = $this->pg_obj->update();

		if ($this->updated === true && !$uploaded)
		{
			$this->ctrl->returnToParent($this, "jump".$this->hier_id);
		}
		else
		{
			$this->insert ();
		}
	}

	/**
	* output tabs
	*/
	function setTabs()
	{
		// catch feedback message
		include_once("classes/class.ilTabsGUI.php");
		$tabs_gui =& new ilTabsGUI();
		$this->getTabs($tabs_gui);
		$this->tpl->setVariable("TABS", $tabs_gui->getHTML());
	}

	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		// back to upper context
		$tabs_gui->addTarget("cont_back",
			$this->ctrl->getParentReturn($this), "",
			"");
	}

	function upload () {
		$this->upload_source();
		$this->update ();
	}
		
	function upload_source () {		
		if (isset($_FILES['userfile']['name']))
		{
			$userfile = $_FILES['userfile']['tmp_name'];
			
			if ($userfile == "" || !is_uploaded_file($userfile))
			{
				$error_str = "<b>Error(s):</b><br>Upload error: file name must not be empty!";
				$this->tpl->setVariable("MESSAGE", $error_str);
				$this->content_obj->setText($this->content_obj->input2xml(stripslashes($_POST["par_content"])));
				return false;
			}

			$_POST["par_content"] = file_get_contents($userfile);								
			$_POST["par_downloadtitle"] = $_FILES['userfile']['name'];			
			return true;
		}				
		
		return false;
	} 
	
	
	function readProgLangs () {
		$prog_langs_ini = file ("syntax_highlight/php/admin/prog_langs.ini");
		$prog_langs = array ();
		foreach ($prog_langs_ini as $prog_lang) {
			$prog_lang_prop = split (":", $prog_lang);
			if ($prog_lang_prop[2] == 1) {
				$prog_langs[$prog_lang_prop[0]] = $prog_lang_prop[1];
			}
		}
		
		return $prog_langs;
	}	
}
?>
