<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once("./Services/COPage/classes/class.ilPCParagraph.php");
require_once("./Services/COPage/classes/class.ilPageContentGUI.php");
require_once("./Services/COPage/classes/class.ilWysiwygUtil.php");

/**
* Class ilPCParagraphGUI
*
* User Interface for Paragraph Editing
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ingroup ServicesCOPage
*/
class ilPCParagraphGUI extends ilPageContentGUI
{
	/**
	* Constructor
	* @access	public
	*/
	function ilPCParagraphGUI(&$a_pg_obj, &$a_content_obj, $a_hier_id, $a_pc_id = "")
	{
		$this->setEnableWikiLinks(false);
		parent::ilPageContentGUI($a_pg_obj, $a_content_obj, $a_hier_id, $a_pc_id);
		
		// characteristics (should be flexible in the future)
		$this->setCharacteristics(ilPCParagraphGUI::_getStandardCharacteristics());
	}
	
	/**
	* Get standard characteristics
	*/
	static function _getStandardCharacteristics()
	{
		global $lng;
		
		return array("" => $lng->txt("none"),
			"Headline1" => $lng->txt("cont_Headline1"),
			"Headline2" => $lng->txt("cont_Headline2"),
			"Headline3" => $lng->txt("cont_Headline3"),
			"Citation" => $lng->txt("cont_Citation"),
			"Mnemonic" => $lng->txt("cont_Mnemonic"),
			"Example" => $lng->txt("cont_Example"),
			"Additional" => $lng->txt("cont_Additional"),
			"Remark" => $lng->txt("cont_Remark"),
			"List" => $lng->txt("cont_List"),
			"TableContent" => $lng->txt("cont_TableContent")
		);
	}

	/**
	* Get characteristics
	*/
	static function _getCharacteristics($a_style_id)
	{
		$chars = ilPCParagraphGUI::_getStandardCharacteristics();

		if ($a_style_id > 0 &&
			ilObject::_lookupType($a_style_id) == "sty")
		{
			include_once("./Services/Style/classes/class.ilObjStyleSheet.php");
			$style = new ilObjStyleSheet($a_style_id);
			$types = array("text_block", "heading1", "heading2", "heading3");
			$chars = array();
			foreach ($types as $t)
			{
				$chars = array_merge($chars, $style->getCharacteristics($t));
			}
			$new_chars = array();
			foreach ($chars as $char)
			{
				if ($chars[$char] != "")	// keep lang vars for standard chars
				{
					$new_chars[$char] = $chars[$char];
				}
				else
				{
					$new_chars[$char] = $char;
				}
				asort($new_chars);
			}
			$chars = $new_chars;
		}
		return $chars;
	}
	
	/**
	* execute command
	*/
	function &executeCommand()
	{
		// get next class that processes or forwards current command
		$next_class = $this->ctrl->getNextClass($this);

		$this->getCharacteristicsOfCurrentStyle(
			array("text_block", "heading1", "heading2", "heading3"));	// scorm-2004
		
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
	* Set Enable Wiki Links.
	*
	* @param	boolean	$a_enablewikilinks	Enable Wiki Links
	*/
	function setEnableWikiLinks($a_enablewikilinks)
	{
		$this->enablewikilinks = $a_enablewikilinks;
	}

	/**
	* Get Enable Wiki Links.
	*
	* @return	boolean	Enable Wiki Links
	*/
	function getEnableWikiLinks()
	{
		return $this->enablewikilinks;
	}

	/**
	* edit paragraph form
	*/
	function edit($a_insert = false)
	{
		global $ilUser, $ilias;
		
		// add paragraph edit template
		$tpl = new ilTemplate("tpl.paragraph_edit.html", true, true, "Services/COPage");

		// help text
		$this->insertHelp($tpl);
		
		// operations
		if ($a_insert)
		{
			$tpl->setCurrentBlock("commands");
			$tpl->setVariable("BTN_NAME", "create_par");
			$tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
			$tpl->setVariable("BTN_CANCEL", "cancelCreate");
			$tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$tpl->parseCurrentBlock();
			$tpl->setCurrentBlock("commands2");
			$tpl->setVariable("BTN_NAME", "create_par");
			$tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
			$tpl->setVariable("BTN_CANCEL", "cancelCreate");
			$tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$tpl->parseCurrentBlock();
			$tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_insert_par"));
		}
		else
		{
			$tpl->setCurrentBlock("commands");
			$tpl->setVariable("BTN_NAME", "update");
			$tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
			$tpl->setVariable("BTN_CANCEL", "cancelUpdate");
			$tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$tpl->parseCurrentBlock();
			$tpl->setCurrentBlock("commands2");
			$tpl->setVariable("BTN_NAME", "update");
			$tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
			$tpl->setVariable("BTN_CANCEL", "cancelUpdate");
			$tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$tpl->parseCurrentBlock();
			$tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_edit_par"));
		}

		// language and characteristic selection
		if (!$a_insert)
		{
			if (key($_POST["cmd"]) == "update")
			{
				$s_lang = $_POST["par_language"];
				$s_char = $_POST["par_characteristic"];
			}
			else
			{
				$s_lang = $this->content_obj->getLanguage();
				$s_char = $this->content_obj->getCharacteristic();
				if ($s_char == "")
				{
					$s_char = "Standard";
				}
			}
		}
		else
		{
			if (key($_POST["cmd"]) == "create_par")
			{
				$s_lang = $_POST["par_language"];
				$s_char = $_POST["par_characteristic"];
			}
			else
			{
				$s_char = "Standard";
				if ($_SESSION["il_text_lang_".$_GET["ref_id"]] != "")
				{
					$s_lang = $_SESSION["il_text_lang_".$_GET["ref_id"]];
				}
				else
				{
					$s_lang = $ilUser->getLanguage();
				}
	
				// set characteristic of new paragraphs in list items to "List"
				$cont_obj =& $this->pg_obj->getContentObject($this->getHierId());
				if (is_object($cont_obj))
				{
					if ($cont_obj->getType() == "li" ||
						($cont_obj->getType() == "par" && $cont_obj->getCharacteristic() == "List"))
					{
						$s_char = "List";
					}
									
					if ($cont_obj->getType() == "td" ||
						($cont_obj->getType() == "par" && $cont_obj->getCharacteristic() == "TableContent"))
					{
						$s_char = "TableContent";
					}
	
				}
			}
		}

		$this->insertStyleSelectionList($tpl, $s_char);
//		$this->insertCharacteristicTable($tpl, $s_char);
		
		
		$tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		
		$tpl->setVariable("PAR_TA_NAME", "par_content");
		$tpl->setVariable("BB_MENU", $this->getBBMenu());
		$this->tpl->addJavascript("./Services/COPage/phpBB/3_0_5/editor.js");
		$this->tpl->addJavascript("./Services/COPage/js/paragraph_editing.js");
		$this->setStyle();

		$this->displayValidationError();

		$tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		$tpl->setVariable("TXT_ANCHOR", $this->lng->txt("cont_anchor"));

		require_once("Services/MetaData/classes/class.ilMDLanguageItem.php");
		$lang = ilMDLanguageItem::_getLanguages();
		$select_lang = ilUtil::formSelect ($s_lang,"par_language",$lang,false,true);
		$tpl->setVariable("SELECT_LANGUAGE", $select_lang);
		
		$tpl->setVariable("TXT_CHARACTERISTIC", $this->lng->txt("cont_characteristic"));
//		$select_char = ilUtil::formSelect ($s_char,
//			"par_characteristic",$this->chars,false,true);
//		$tpl->setVariable("SELECT_CHARACTERISTIC", $select_char);

		if (key($_POST["cmd"]) == "update" || key($_POST["cmd"]) == "create_par")
		{
			$s_text = ilUtil::stripSlashes($_POST["par_content"], false);
			// prevent curly brackets from being swallowed up by template engine
			$s_text = str_replace("{", "&#123;", $s_text);
			$s_text = str_replace("}", "&#125;", $s_text);
		}
		else if (!$a_insert)
		{
			$s_text = $this->content_obj->xml2output($this->content_obj->getText());
		}

		$tpl->setVariable("PAR_TA_CONTENT", $s_text);

		$tpl->parseCurrentBlock();
		
		$this->tpl->setContent($tpl->get());
		return $tpl->get();
	}

	/**
	* Insert characteristic table
	*/
	function insertCharacteristicTable($a_tpl, $a_seleted_value)
	{
		$i = 0;

		$chars = $this->getCharacteristics();

		if ($chars[$a_seleted_value] == "" && ($a_seleted_value != ""))
		{
			$chars = array_merge(array($a_seleted_value => $a_seleted_value),
				$chars);
		}

		foreach ($chars as $char => $char_lang)
		{
			$a_tpl->setCurrentBlock("characteristic_cell");
			$a_tpl->setVariable("CHAR_HTML",
				'<div class="ilc_text_block_'.$char.'" style="margin-top:2px; margin-bottom:2px; position:static;">'.$char_lang."</div>");
			$a_tpl->setVariable("CHAR_VALUE", $char);
			if ($char == $a_seleted_value)
			{
				$a_tpl->setVariable("SELECTED",
					' checked="checked" ');
			}
			$a_tpl->parseCurrentBlock();
			if ((($i+1) % 3) == 0)	// 
			{
				$a_tpl->touchBlock("characteristic_row");
			}
			$i++;
		}
		$a_tpl->touchBlock("characteristic_table");
	}

	function insertStyleSelectionList($a_tpl, $a_s_char)
	{
		include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
		$selection = new ilAdvancedSelectionListGUI();
		$selection->setFormSelectMode("par_characteristic", "", false,
			"", "", "",
			"", "", "", "");
		$selection->setId("style_selection");
		//$selection->setSelectionHeaderClass("MMInactive");
		$selection->setHeaderIcon(ilAdvancedSelectionListGUI::DOWN_ARROW_DARK);
		$selection->setSelectedValue($a_s_char);
		//$selection->setItemLinkClass("small");
		$selection->setUseImages(false);
		$selection->setOnClickMode(ilAdvancedSelectionListGUI::ON_ITEM_CLICK_FORM_SELECT);
		
		$chars = $this->getCharacteristics();
		$title_char = $chars[$a_s_char] != ""
			? $chars[$a_s_char]
			: $a_s_char;
		$selection->setListTitle($title_char);

		if ($chars[$a_seleted_value] == "" && ($a_seleted_value != ""))
		{
			$chars = array_merge(array($a_seleted_value => $a_seleted_value),
				$chars);
		}

		foreach ($chars as $char => $char_lang)
		{
			$t = "text_block";
			$tag = "div";
//echo "-".$char;
			switch($char)
			{
				case "Headline1": $t = "heading1"; $tag = "h1"; break;
				case "Headline2": $t = "heading2"; $tag = "h2"; break;
				case "Headline3": $t = "heading3"; $tag = "h3"; break; 
			}
			$html = '<'.$tag.' class="ilc_'.$t.'_'.$char.'" style="margin-top:2px; margin-bottom:2px; position:static;">'.$char_lang."</".$tag.">";
			$selection->addItem($char_lang, $char, "",
				"", $char, "", $html);
		}
		$a_tpl->setVariable("ADV_SEL_STYLE", $selection->getHTML());
	}
	
	/**
	* Set Style
	*/
	private function setStyle()
	{
		include_once("./Services/Style/classes/class.ilObjStyleSheet.php");
		
		if ($this->pg_obj->getParentType() == "gdf" ||
			$this->pg_obj->getParentType() == "lm" ||
			$this->pg_obj->getParentType() == "dbk")
		{
			if ($this->pg_obj->getParentType() != "gdf")
			{
				$this->tpl->setContentStylesheet(ilObjStyleSheet::getContentStylePath(
						ilObjContentObject::_lookupStyleSheetId($this->pg_obj->getParentId())));
			}
			else
			{
				$this->tpl->setContentStylesheet(ilObjStyleSheet::getContentStylePath(0));
			}
		}
		else
		{
			if ($this->pg_obj->getParentType() != "sahs")
			{
//				$this->tpl->setContentStylesheet(ilObjStyleSheet::getContentStylePath(0));
			}
		}
	}
	
	/**
	* insert paragraph form
	*/
	function insert()
	{
		return $this->edit(true);
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
		//$this->content_obj->setAnchor(ilUtil::stripSlashes($_POST["anchor"]));

//echo "<br>PARupdate1:".$_POST["par_content"].":";
//echo "<br>PARupdate2:".htmlentities($_POST["par_content"]).":";
//echo "<br>PARupdate3:".htmlentities($this->content_obj->input2xml($_POST["par_content"])).":";
//echo "<br>PARupdate4:".$this->content_obj->input2xml($_POST["par_content"]).":";

		//$this->updated = $this->content_obj->setText(
		//	$this->content_obj->input2xml(stripslashes($_POST["par_content"]),
		//		$_POST["usedwsiwygeditor"]));
		$this->updated = $this->content_obj->setText(
			$this->content_obj->input2xml($_POST["par_content"],
				$_POST["usedwsiwygeditor"]), true);
//echo "<br>PARupdate2";
		if ($this->updated !== true)
		{
			$ilBench->stop("Editor","Paragraph_update");
			$this->edit();
			return;
		}

		$this->updated = $this->pg_obj->update();
//echo "<br>PARupdate_after:".htmlentities($this->pg_obj->dom->dump_mem(0, "UTF-8")).":";

		$ilBench->stop("Editor","Paragraph_update");

		if ($this->updated === true)
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
//echo "+".$this->pc_id."+";
		$this->content_obj->create($this->pg_obj, $this->hier_id, $this->pc_id);
		$this->content_obj->setLanguage($_POST["par_language"]);
		$_SESSION["il_text_lang_".$_GET["ref_id"]] = $_POST["par_language"];
		$this->content_obj->setCharacteristic($_POST["par_characteristic"]);

		$this->updated = $this->content_obj->setText(
			$this->content_obj->input2xml($_POST["par_content"],
				$_POST["usedwsiwygeditor"]), true);

		if ($this->updated !== true)
		{
			$this->insert();
			return;
		}
		$this->updated = $this->pg_obj->update();

		if ($this->updated === true)
		{
			$this->ctrl->returnToParent($this, "jump".$this->hier_id);
		}
		else
		{
			$this->insert();
		}
	}
	
	/**
	* Insert Help
	*/
	function insertHelp($a_tpl)
	{
		global $lng;
		
		$a_tpl->setCurrentBlock("help_item");
		$a_tpl->setVariable("TXT_HELP", "<b>".$lng->txt("cont_syntax_help")."</b>");
		$a_tpl->parseCurrentBlock();
		$a_tpl->setCurrentBlock("help_item");
		$a_tpl->setVariable("TXT_HELP", "* ".$lng->txt("cont_bullet_list"));
		$a_tpl->parseCurrentBlock();
		$a_tpl->setCurrentBlock("help_item");
		$a_tpl->setVariable("TXT_HELP", "# ".$lng->txt("cont_numbered_list"));
		$a_tpl->parseCurrentBlock();
		$a_tpl->setCurrentBlock("help_item");
		$a_tpl->setVariable("TXT_HELP", "=".$lng->txt("cont_Headline1")."=<br />".
			"==".$lng->txt("cont_Headline2")."==<br />".
			"===".$lng->txt("cont_Headline3")."===");
		$a_tpl->parseCurrentBlock();
		
		if ($this->getEnableWikiLinks())
		{
			$a_tpl->setCurrentBlock("help_item");
			$a_tpl->setVariable("TXT_HELP", "[[".$lng->txt("cont_wiki_page_link")."]]");
			$a_tpl->parseCurrentBlock();
		}

		$a_tpl->setCurrentBlock("help");
		$a_tpl->parseCurrentBlock();
	}
	
}
?>
