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
		
		return array("" => $lng->txt("cont_standard"),
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
		$s_char = $this->determineCharacteristic($a_insert);
		if (!$a_insert)
		{
			if (key($_POST["cmd"]) == "update")
			{
				$s_lang = $_POST["par_language"];
			}
			else
			{
				$s_lang = $this->content_obj->getLanguage();
			}
		}
		else
		{
			if (key($_POST["cmd"]) == "create_par")
			{
				$s_lang = $_POST["par_language"];
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
	 * Determine current characteristic
	 *
	 * @param
	 * @return
	 */
	function determineCharacteristic($a_insert = false)
	{
		// language and characteristic selection
		if (!$a_insert)
		{
			if (key($_POST["cmd"]) == "update")
			{
				$s_char = $_POST["par_characteristic"];
			}
			else
			{
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
				$s_char = $_POST["par_characteristic"];
			}
			else
			{
				$s_char = "Standard";

				// set characteristic of new paragraphs in list items to "List"
				$cont_obj = $this->pg_obj->getContentObject($this->getHierId());
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
		return $s_char;
	}

	/**
	 * Edit paragraph (Ajax mode, sends the content of the paragraph)
	 */
	function editJS()
	{
		global $ilUser, $ilias;

		$s_text = $this->content_obj->getText();
//echo htmlentities($s_text);
		$s_text = $this->content_obj->xml2output($s_text, true, false);
		$char = $this->determineCharacteristic(false);
		$s_text = ilPCParagraphGUI::xml2outputJS($s_text, $char);
		echo $s_text;
		exit;
	}

	/**
	 * Prepare content for js output
	 */
	static function xml2outputJS($s_text, $char)
	{
		$s_text = "<div class='ilc_text_block_".$char."'>".$s_text."</div>";
		// lists
		$s_text = str_replace(array("<SimpleBulletList>", "</SimpleBulletList>"),
			array("<ul>", "</ul>"),
			$s_text);
		$s_text = str_replace(array("<SimpleNumberedList>", "</SimpleNumberedList>"),
			array("<ol>", "</ol>"), $s_text);
		$s_text = str_replace(array("<SimpleListItem>", "</SimpleListItem>"),
			array("<li>", "</li>"), $s_text);
		//$s_text = str_replace("<SimpleBulletList><br />", "<SimpleBulletList>", $s_text);
		//$s_text = str_replace("<SimpleNumberedList><br />", "<SimpleNumberedList>", $s_text);
		//$s_text = str_replace("</SimpleListItem><br />", "</SimpleListItem>", $s_text);


		// spans
		include_once("./Services/COPage/classes/class.ilPageContentGUI.php");
		foreach (ilPageContentGUI::_getCommonBBButtons() as $bb => $cl)
		{
			if (!in_array($bb, array("code", "tex", "fn", "xln")))
			{
				$s_text = str_replace("[".$bb."]",
					'<span class="ilc_text_inline_'.$cl.'">', $s_text);
				$s_text = str_replace("[/".$bb."]",
					'</span>', $s_text);
			}
		}

		// code
		$s_text = str_replace(array("[code]", "[/code]"),
			array("<code>", "</code>"), $s_text);

		return $s_text;
	}


	/**
	 * Save paragraph by JS call
	 *
	 * @param
	 * @return
	 */
	function saveJS()
	{
		global $ilCtrl;

		$text = self::handleAjaxContent($_POST["ajaxform_content"]);
		if ($text === false)
		{
			$ilCtrl->returnToParent($this, "jump".$this->hier_id);
		}
		$this->content_obj->setCharacteristic(ilUtil::stripSlashes($_POST["ajaxform_char"]));
		$text = $this->content_obj->input2xml($text, true, false);
		$text = self::handleAjaxContentPost($text);
		$this->updated = $this->content_obj->setText($text, true);
		if ($this->updated)
		{
			$this->updated = $this->pg_obj->update();
		}

		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}
	
	/**
	 * Cancel
	 */
	function cancel()
	{
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}
	

	/**
	 * Handle ajax content
	 */
	static function handleAjaxContent($a_content)
	{
		$doc = new DOMDocument();

		$content = ilUtil::stripSlashes($a_content, false);
		$content = str_replace("&lt;", "<", $content);
		$content = str_replace("&gt;", ">", $content);
//echo htmlentities($content); exit;
		$res = $doc->loadXML($content);

		if (!$res)
		{
			return false;
		}

		// convert tags
		$xpath = new DOMXpath($doc);
		$elements = $xpath->query("//span");
		include_once("./Services/Utilities/classes/class.ilDOM2Util.php");
		if (!is_null($elements))
		{
			foreach ($elements as $element)
			{
				$class = $element->getAttribute("class");
				if (substr($class, 0, 16) == "ilc_text_inline_")
				{
					ilDOM2Util::changeName($element, "il".substr($class, 16), false);
				}
			}
		}

		$text = $doc->saveXML($doc->documentElement);
		$text = str_replace("<br/>", "\n", $text);

		// remove wrapping div
//		$text = $_POST["ajaxform_content"];
		$pos = strpos($text, ">");
		$text = substr($text, $pos + 1);
		$pos = strrpos($text, "<");
		$text = substr($text, 0, $pos);

// todo: remove empty spans <span ...> </span>

		// replace tags by bbcode
		foreach (ilPageContentGUI::_getCommonBBButtons() as $bb => $cl)
		{
			if (!in_array($bb, array("code", "tex", "fn", "xln")))
			{
				$text = str_replace("<il".$cl.">",
					"[".$bb."]", $text);
				$text = str_replace("</il".$cl.">",
					"[/".$bb."]", $text);
			}
		}
		$text = str_replace(array("<code>", "</code>"),
			array("[code]", "[/code]"), $text);


		return $text;
	}

	/**
	 * Post input2xml handling of ajax content
	 */
	static function handleAjaxContentPost($text)
	{
		$text = str_replace(array("&lt;ul&gt;", "&lt;/ul&gt;"),
			array("<SimpleBulletList>", "</SimpleBulletList>"), $text);
		$text = str_replace(array("&lt;ol&gt;", "&lt;/ol&gt;"),
			array("<SimpleNumberedList>", "</SimpleNumberedList>"), $text);
		$text = str_replace(array("&lt;li&gt;", "&lt;/li&gt;"),
			array("<SimpleListItem>", "</SimpleListItem>"), $text);
		$text = str_replace("<SimpleBulletList><br />", "<SimpleBulletList>", $text);
		$text = str_replace("<SimpleNumberedList><br />", "<SimpleNumberedList>", $text);
		$text = str_replace("<br /><SimpleBulletList>", "<SimpleBulletList>", $text);
		$text = str_replace("<br /><SimpleNumberedList>", "<SimpleNumberedList>", $text);
		$text = str_replace("</SimpleBulletList><br />", "</SimpleBulletList>", $text);
		$text = str_replace("</SimpleNumberedList><br />", "</SimpleNumberedList>", $text);
		$text = str_replace("</SimpleListItem><br />", "</SimpleListItem>", $text);
		return $text;
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

	/**
	 * Insert style selection list
	 * 
	 * @param object $a_tpl
	 * @param string $a_selected
	 */
	function insertStyleSelectionList($a_tpl, $a_selected)
	{
		$a_tpl->setVariable("ADV_SEL_STYLE", self::getStyleSelector($a_selected,
			$this->getCharacteristics()));
	}

	/**
	 * Get style selector
	 */
	static function getStyleSelector($a_selected, $a_chars, $a_use_callback = false)
	{
		include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
		$selection = new ilAdvancedSelectionListGUI();
		$selection->setFormSelectMode("par_characteristic", "", false,
			"", "", "",
			"", "", "", "");
		$selection->setId("style_selection");
		$selection->setSelectionHeaderClass("ilEditSubmit ilTinyMenuDropDown");
		$selection->setHeaderIcon(ilAdvancedSelectionListGUI::DOWN_ARROW_DARK);
		$selection->setSelectedValue($a_selected);
		$selection->setUseImages(false);
		$selection->setOnClickMode(ilAdvancedSelectionListGUI::ON_ITEM_CLICK_FORM_SELECT);
		if ($a_use_callback)
		{
			$selection->setSelectCallback("ilCOPage.setParagraphClass");
		}
		
		$chars = $a_chars;
		$title_char = ($chars[$a_selected] != "")
			? $chars[$a_selected]
			: $a_selected;
		$selection->setListTitle($title_char);

		if ($chars[$a_seleted] == "" && ($a_seleted != ""))
		{
			$chars = array_merge(array($a_seleted => $a_seleted),
				$chars);
		}

		foreach ($chars as $char => $char_lang)
		{
			$t = "text_block";
			$tag = "div";
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
		return $selection->getHTML();
	}
	
	/**
	 * Get character style selector
	 */
	static function getCharStyleSelector($a_par_type, $a_use_callback = true)
	{
		global $lng;
		
		include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
		$selection = new ilAdvancedSelectionListGUI();
		$selection->setFormSelectMode("char_characteristic", "", false,
			"", "", "",
			"", "", "", "");
		$selection->setId("char_style_selection");
		$selection->setSelectionHeaderClass("ilEditSubmit");
		$selection->setHeaderIcon(ilAdvancedSelectionListGUI::DOWN_ARROW_DARK);
		//$selection->setSelectedValue($a_selected);
		$selection->setUseImages(false);
		$selection->setOnClickMode(ilAdvancedSelectionListGUI::ON_ITEM_CLICK_NOP);
		if ($a_use_callback)
		{
			$selection->setSelectCallback("ilCOPage.setCharacterClass");
		}
		
		//$chars = $a_chars;
		//$title_char = ($chars[$a_selected] != "")
		//	? $chars[$a_selected]
		//	: $a_selected;
		$selection->setListTitle("&nbsp;<i>A</i>");

		/*if ($chars[$a_seleted] == "" && ($a_seleted != ""))
		{
			$chars = array_merge(array($a_seleted => $a_seleted),
				$chars);
		}*/

		$chars = array(
			"Comment" => array("code" => "com", "txt" => $lng->txt("cont_char_style_com")),
			"Quotation" => array("code" => "quot", "txt" =>$lng->txt("cont_char_style_quot")),
			"Accent" => array("code" => "acc", "txt" => $lng->txt("cont_char_style_acc")),
			"Code" => array("code" => "code", "txt" => $lng->txt("cont_char_style_code"))
			);
		foreach ($chars as $key => $char)
		{
			if (ilPageEditorSettings::lookupSettingByParentType(
				$a_par_type, "active_".$char["code"], true))
			{
				$t = "text_inline";
				$tag = "span";
				switch($key)
				{
					case "Code": $tag = "code"; break;
				}
				$html = '<'.$tag.' class="ilc_'.$t.'_'.$key.'" style="font-size:90%; margin-top:2px; margin-bottom:2px; position:static;">'.$char["txt"]."</".$tag.">";
				$selection->addItem($char["txt"], $key, "",
					"", $key, "", $html);
			}
		}
		return $selection->getHTML();
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
		if ($_POST["ajaxform_hier_id"] != "")
		{
			return $this->createJS();
		}

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
	 * Create paragraph per JS
	 */
	function createJS()
	{
		global $ilUser, $ilCtrl;

		// get paragraph object
		$this->content_obj = new ilPCParagraph($this->dom);

		// create object, set language and characteristic
		$this->content_obj->create($this->pg_obj, $_POST["ajaxform_hier_id"], "");
		$lang = $_POST["ajaxform_lang"]
			? $_POST["ajaxform_char"]
			: $ilUser->getLanguage();
		$this->content_obj->setLanguage($lang);
		$_SESSION["il_text_lang_".$_GET["ref_id"]] = $lang;
		$this->content_obj->setCharacteristic($_POST["ajaxform_char"]);

		// handle and insert text into xml
		$text = self::handleAjaxContent($_POST["ajaxform_content"]);
		if ($text === false)
		{
			$ilCtrl->returnToParent($this, "jump".$this->hier_id);
		}

		$text = $this->content_obj->input2xml($text, true, false);
		$text = self::handleAjaxContentPost($text);
		$this->updated = $this->content_obj->setText($text, true);

		if ($this->updated)
		{
			$this->updated = $this->pg_obj->update();
		}

		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
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
