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

require_once("./content/classes/class.ilParagraph.php");
require_once("./content/classes/class.ilPageContent.php");

/**
* Class ilParagraphGUI
*
* User Interface for Paragraph Editing
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilParagraphGUI
{
	var $para_obj;
	var $ilias;
	var $tpl;
	var $lng;
	var $lm_obj;
	var $pg_obj;
	var $cont_cnt;

	/**
	* Constructor
	* @access	public
	*/
	function ilParagraphGUI(&$a_lm_obj, &$a_pg_obj, &$a_para_obj, $a_cont_cnt)
	{
		global $ilias, $tpl, $lng;

		$this->ilias =& $ilias;
		$this->tpl =& $tpl;
		$this->lng =& $lng;
		$this->lm_obj =& $a_lm_obj;
		$this->pg_obj =& $a_pg_obj;
		$this->para_obj =& $a_para_obj;
		$this->cont_cnt = $a_cont_cnt;
	}

	function edit()
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.paragraph_edit.html", true);
		$content = $this->pg_obj->getContent();
		$cnt = 1;

		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_edit_par"));
		$this->tpl->setVariable("FORMACTION", "lm_edit.php?lm_id=".
			$this->lm_obj->getId()."&obj_id=".$this->pg_obj->getId().
			"&cont_cnt=".$this->cont_cnt."&cmd=edpost");

		// content edit
		$cur_content_obj = $content[$this->cont_cnt - 1];

		$this->tpl->setVariable("PAR_TA_NAME", "par_content");
		//echo htmlentities($this->para_obj->getText());
		$this->tpl->setVariable("PAR_TA_CONTENT", $this->para_obj->xml2output($this->para_obj->getText()));
		//$this->tpl->setVariable("PAR_TA_CONTENT", "Hallo Echo");
		$this->tpl->parseCurrentBlock();

		reset($content);
		foreach ($content as $content_obj)
		{
			switch (get_class($content_obj))
			{
				case "ilparagraph":
					$cont_sel[$cnt] = ilUtil::shortenText($content_obj->getText(),40);
					break;
			}
			$cnt++;
		}
		$this->tpl->setCurrentBlock("content_selection");
		$this->tpl->setVariable("SELECT_CONTENT" ,
			ilUtil::formSelect($this->cont_cnt, "new_cont_cnt",$cont_sel, false, true));
		$this->tpl->setVariable("BTN_NAME", "edit");
		$this->tpl->setVariable("TXT_SELECT",$this->lng->txt("select"));
		$this->tpl->parseCurrentBlock();

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "update");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();

	}


	function insert()
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.paragraph_edit.html", true);
		$content = $this->pg_obj->getContent();
		$cnt = 1;

		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_insert_par"));
		$this->tpl->setVariable("FORMACTION", "lm_edit.php?lm_id=".
			$this->lm_obj->getId()."&obj_id=".$this->pg_obj->getId().
			"&cont_cnt=".$this->cont_cnt."&cmd=edpost");

		// content edit
		//--$cur_content_obj = $content[$this->cont_cnt - 1];

		$this->tpl->setVariable("PAR_TA_NAME", "par_content");
		//echo htmlentities($this->para_obj->getText());
		//--$this->tpl->setVariable("PAR_TA_CONTENT", $this->xml2output($this->para_obj->getText()));
		$this->tpl->setVariable("PAR_TA_CONTENT", "");
		$this->tpl->parseCurrentBlock();

		/*
		reset($content);
		foreach ($content as $content_obj)
		{
			switch (get_class($content_obj))
			{
				case "ilparagraph":
					$cont_sel[$cnt] = ilUtil::shortenText($content_obj->getText(),40);
					break;
			}
			$cnt++;
		}
		$this->tpl->setCurrentBlock("content_selection");
		$this->tpl->setVariable("SELECT_CONTENT" ,
			ilUtil::formSelect($this->cont_cnt, "cont_cnt",$cont_sel, false, true));
		$this->tpl->setVariable("BTN_NAME", "edit");
		$this->tpl->setVariable("TXT_SELECT",$this->lng->txt("select"));
		$this->tpl->parseCurrentBlock();*/

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "create");	//--
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();

	}


	function update()
	{
		//$content = $this->pg_obj->getContent();

		//$cur_content_obj =& $content[$_GET["cont_cnt"] - 1];
//echo "PARupdate:".$this->para_obj->input2xml($_POST["par_content"]).":<br>";
		$this->para_obj->setText($this->para_obj->input2xml($_POST["par_content"]));
		$this->pg_obj->update();
		header("location: lm_edit.php?cmd=viewWysiwyg&lm_id=".$this->lm_obj->getId()."&obj_id=".
			$this->pg_obj->getId());
		exit;

	}

	function create()
	{
		$new_par = new ilParagraph();
		$new_par->setText($new_par->input2xml($_POST["par_content"]));
		$this->pg_obj->insertContent($new_par,
			ilPageContent::incEdId($this->cont_cnt));
		//$this->pg_obj->update();
		header("location: lm_edit.php?cmd=viewWysiwyg&lm_id=".$this->lm_obj->getId()."&obj_id=".
			$this->pg_obj->getId());
	}

	function delete()
	{
		$this->pg_obj->deleteContent($this->cont_cnt);
		//$this->pg_obj->update();
		header("location: lm_edit.php?cmd=viewWysiwyg&lm_id=".$this->lm_obj->getId()."&obj_id=".
			$this->pg_obj->getId());
	}

	function moveAfter()
	{
		if(!isset($_POST["target"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if(count($_POST["target"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("only_one_target"),$this->ilias->error_obj->MESSAGE);
		}
		$this->pg_obj->moveContent($this->cont_cnt, $_POST["target"][0] + 1);
		header("location: lm_edit.php?cmd=viewWysiwyg&lm_id=".$this->lm_obj->getId()."&obj_id=".
			$this->pg_obj->getId());
	}

	function moveBefore()
	{
		if(!isset($_POST["target"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if(count($_POST["target"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("only_one_target"),$this->ilias->error_obj->MESSAGE);
		}
		$this->pg_obj->moveContent($this->cont_cnt, $_POST["target"][0]);
		header("location: lm_edit.php?cmd=viewWysiwyg&lm_id=".$this->lm_obj->getId()."&obj_id=".
			$this->pg_obj->getId());
	}

	function input2xml($a_text)
	{
		// note: the order of the processing steps is crucial
		// and should be the same as in xml2output() in REVERSE order!

		$a_text = trim($a_text);

		// mask html
		$a_text = str_replace("<","&lt;",$a_text);
		$a_text = str_replace(">","&gt;",$a_text);

		// linefeed to br
		$a_text = str_replace(chr(13).chr(10),"<br />",$a_text);
		$a_text = str_replace(chr(13),"<br />", $a_text);
		$a_text = str_replace(chr(10),"<br />", $a_text);

		// bb code to xml
		$a_text = eregi_replace("\[com\]","<Comment>",$a_text);
		$a_text = eregi_replace("\[\/com\]","</Comment>",$a_text);
		$a_text = eregi_replace("\[emp]","<Emph>",$a_text);
		$a_text = eregi_replace("\[\/emp\]","</Emph>",$a_text);
		$a_text = eregi_replace("\[str]","<Strong>",$a_text);
		$a_text = eregi_replace("\[\/str\]","</Strong>",$a_text);
		/*$blob = ereg_replace("<NR><NR>","<P>",$blob);
		$blob = ereg_replace("<NR>"," ",$blob);*/

		//$a_text = nl2br($a_text);
		return $a_text;
	}

	function xml2output($a_text)
	{
		// note: the order of the processing steps is crucial
		// and should be the same as in input2xml() in REVERSE order!

		// xml to bb code
		$a_text = eregi_replace("<Comment>","[com]",$a_text);
		$a_text = eregi_replace("</Comment>","[/com]",$a_text);
		$a_text = eregi_replace("<Emph>","[emp]",$a_text);
		$a_text = eregi_replace("</Emph>","[/emp]",$a_text);
		$a_text = eregi_replace("<Strong>","[str]",$a_text);
		$a_text = eregi_replace("</Strong>","[/str]",$a_text);

		// br to linefeed
		$a_text = str_replace("<br />", "\n", $a_text);

		// unmask html
		$a_text = str_replace("&lt;", "<", $a_text);
		$a_text = str_replace("&gt;", ">",$a_text);
		return $a_text;
		//return str_replace("<br />", chr(13).chr(10), $a_text);
	}

}
?>
