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
* Class ilObjDlBookGUI
*
* @author Databay AG <ay@databay.de>
* @version $Id$
*
* @package content
*/
require_once "content/classes/class.ilObjContentObjectGUI.php";

class ilObjDlBookGUI extends ilObjContentObjectGUI
{
	/**
	* Constructor
	*
	* @access	public
	*/
	function ilObjDlBookGUI($a_data,$a_id = 0,$a_call_by_reference = true, $a_prepare_output = true)
	{
        $this->type = "dbk";
		parent::ilObjContentObjectGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);

		# BETTER DO IT HERE THAN IN PARENT CLASS ( PROBLEMS FOR import, create)
		$this->assignObject();
		
		// SAME REASON
		if($a_id != 0)
		{
			$this->lm_tree =& $this->object->getLMTree();
		}
	}

	function assignObject()
	{
		include_once("content/classes/class.ilObjDlBook.php");

		$this->link_params = "ref_id=".$this->ref_id;
		$this->object =& new ilObjDlBook($this->id, true);
	}


	function showCitation($page_xml)
	{
		$this->tpl->setCurrentBlock("ContentStyle");
		$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
								ilObjStyleSheet::getContentStylePath($this->object->getStyleSheetId()));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());

		$parsed_post = $this->__parseCitationPost();
		if(!count($parsed_post))
		{
			$_SESSION["citation_error"] = 1;
			header("location: lm_presentation.php?cmd=layout&frame=maincontent&ref_id=$_GET[ref_id]&obj_id=$_GET[obj_id]");
			exit;
		}
		$tmp_tpl = new ilTemplate("tpl.citation.xsl",true,true,"content");
		$tmp_tpl->setVariable("CITATIONS",$this->lng->txt("cont_citations"));

		foreach($parsed_post as $key => $data)
		{
			$tmp_tpl->setCurrentBlock("citation_row");
			$tmp_tpl->setVariable("CITATION",$this->__applyCitationText($page_xml,$data["start"],$data["end"]));
			$tmp_tpl->setVariable("PAGES_ROW",$data["text"]);
			$tmp_tpl->parseCurrentBlock();
		}
		$xsl = $tmp_tpl->get();

		$this->object->initBibItemObject();
		$xml = $this->object->bib_obj->getXML();
		if(empty($xml))
		{
			return true;
		}
		$args = array( '/_xml' => $xml, '/_xsl' => $xsl );
		$xh = xslt_create();
		$params = array ('target_id' => $_SESSION["bib_id"]);

		$output = xslt_process($xh,"arg:/_xml","arg:/_xsl",NULL,$args, $params);

		$this->tpl->setCurrentBlock("ilPage");
		$this->tpl->setVariable("PAGE_CONTENT",$output);
		$this->tpl->parseCurrentBlock();
		
		return true;
	}

	function showAbstract($a_target_id)
	{
		if(count($_POST["tr_id"]) > 1)
		{
			$message = true;
			$message_text = $this->lng->txt("cont_select_one_translation_warning");
			$show_full = false;
		}
		else if(!$a_target_id and ($_POST["action"] == "show" or $_POST["action"] == "details"))
		{
			$message = true;
			$message_text = $this->lng->txt("cont_select_one_edition");
			$show_full = false;
		}			
		else if(is_array($a_target_id) and count($a_target_id) > 1)
		{
			$message = true;
			$message_text = $this->lng->txt("cont_msg_multiple_editions");
			$show_full = false;
		}
		else if(is_array($a_target_id))
		{
			$a_target_id = $a_target_id[0];
			$show_full = true;
		}
		else
		{
			$a_target_id = 0;
			$show_full = false;
		}

		$this->object->initBibItemObject();

		$this->tpl->setCurrentBlock("ContentStyle");
		$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
								ilObjStyleSheet::getContentStylePath($this->object->getStyleSheetId()));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		$this->tpl->setCurrentBlock("ilPage");
	
		$tmp_tpl = new ilTemplate("tpl.bibliography.xsl",true,true,"content");
		$tmp_tpl->setVariable("TITLE",$this->lng->txt("title"));
		$tmp_tpl->setVariable("EDITION",$this->lng->txt("cont_edition"));
		$tmp_tpl->setVariable("AUTHORS",$this->lng->txt("authors"));

		if($show_full)
		{
			$params = array ('mode'			=> "view_full",
							 'action'		=> "lm_presentation.php?cmd=layout&frame=maincontent&ref_id=$_GET[ref_id]",
							 'target_id'    => "$a_target_id");

			$tmp_tpl->setVariable("BOOKTITLE",$this->lng->txt("cont_booktitle"));
			$tmp_tpl->setVariable("CROSS_REFERENCE",$this->lng->txt("cont_cross_reference"));
			$tmp_tpl->setVariable("DETAILS",$this->lng->txt("cont_details"));
			$tmp_tpl->setVariable("EDITOR",$this->lng->txt("editor"));
			$tmp_tpl->setVariable("HOW_PUBLISHED",$this->lng->txt("cont_how_published"));
			$tmp_tpl->setVariable("WHERE_PUBLISHED",$this->lng->txt("cont_where_published"));
			$tmp_tpl->setVariable("INSTITUTION",$this->lng->txt("institution"));
			$tmp_tpl->setVariable("JOURNAL",$this->lng->txt("cont_journal"));
			$tmp_tpl->setVariable("KEYWORD",$this->lng->txt("cont_keyword"));
			$tmp_tpl->setVariable("PAGES",$this->lng->txt("cont_pages"));
			$tmp_tpl->setVariable("SCHOOL",$this->lng->txt("cont_school"));
			$tmp_tpl->setVariable("MONTH",$this->lng->txt("cont_month"));
			$tmp_tpl->setVariable("PUBLISHER",$this->lng->txt("cont_publisher"));
			$tmp_tpl->setVariable("SERIES",$this->lng->txt("cont_series"));
			$tmp_tpl->setVariable("SERIES_TITLE",$this->lng->txt("cont_series_title"));
			$tmp_tpl->setVariable("SERIES_EDITOR",$this->lng->txt("cont_series_editor"));
			$tmp_tpl->setVariable("SERIES_VOLUME",$this->lng->txt("cont_series_volume"));
			$tmp_tpl->setVariable("YEAR",$this->lng->txt("cont_year"));
			$tmp_tpl->setVariable("ISBN",$this->lng->txt("cont_isbn"));
			$tmp_tpl->setVariable("URL",$this->lng->txt("cont_url"));
		}
		else
		{
			$params = array ('mode'				=> "view_simple",
							 'action'			=> "lm_presentation.php?cmd=layout&frame=maincontent&ref_id=$_GET[ref_id]");

			if($translations = $this->object->getTranslations())
			{
				foreach($translations as $tr_id)
				{
					$tmp_obj = ilObjectFactory::getInstanceByRefId($tr_id);

					$tmp_tpl->setCurrentBlock("TRANSLATION_ROW");
					$tmp_tpl->setVariable("ROW_TITLE",$tmp_obj->getTitle());
					$tmp_tpl->setVariable("ROW_ID",$tr_id);
					$tmp_tpl->parseCurrentBlock();
					unset($tmp_obj);
				}
				$tmp_tpl->setCurrentBlock("TRANSLATION");
				$tmp_tpl->setVariable("TRANSLATION_HEADER",$this->lng->txt("cont_translations"));
				$tmp_tpl->parseCurrentBlock();
			}
			$tmp_tpl->setVariable("DETAILS",$this->lng->txt("cont_details"));
			$tmp_tpl->setVariable("SHOW",$this->lng->txt("cont_show"));
			$tmp_tpl->setVariable("SHOW_CITATION",$this->lng->txt("cont_show_citation"));
			$tmp_tpl->setVariable("GO",$this->lng->txt("go"));
		}
		
		// SHOW MESSAGE
		if($message)
		{
			sendInfo($message_text);
		}
		$xsl = $tmp_tpl->get();
		$xml = $this->object->bib_obj->getXML();
		
		if(empty($xml))
		{
			return true;
		}
		$args = array( '/_xml' => $xml, '/_xsl' => $xsl );
		$xh = xslt_create();


		$output = xslt_process($xh,"arg:/_xml","arg:/_xsl",NULL,$args, $params);
		$this->tpl->setVariable("PAGE_CONTENT",$output);
		
		return $output;
	}
	
	/**
	*	exports the digi-lib-object into a xml structure
	*/
	function export() 
	{
		// BASE CLASS objectGUI IS INSTATIATING $this->object
		#$this->object =& new ilObjDlBook($this->id, true);
		$this->object->export($_GET["ref_id"]);
	}

	function offlineexportform() 
	{
		
		//$tpl_offline =& new ilTemplate("tpl.");
		//vd($this->tpl);
		$this->tpl->addBlockfile("CONTENT", "offline_content", "tpl.offline_export.html", true);
		$this->tpl->touchBlock("offline_content");
		
		$this->tpl->setVariable("TXT_TYPE","Export-Type");

		if ($_GET["print"]==1) 
		{
			$this->tpl->setVariable("TXT_ACTION","Digilib-Book - print");
			$this->tpl->setVariable("TXT_PRINTEXPORT",$this->lng->txt("Print") );
			$this->tpl->setVariable("PRINT_CHECKED","checked");
			$this->tpl->setVariable("EXPORT_TARGET","_blank");
		} 
		else 
		{
			$this->tpl->setVariable("TXT_ACTION","Digilib-Book - download");
			$this->tpl->setVariable("TXT_HTMLEXPORT",$this->lng->txt("HTML export") );
			$this->tpl->setVariable("TXT_PDFEXPORT",$this->lng->txt("PDF export") );
            $this->tpl->setVariable("TXT_XMLEXPORT",$this->lng->txt("XML export (only complete book)") );
			$this->tpl->setVariable("OFFLINE_CHECKED","checked");
		}
		
		$this->tpl->setVariable("TXT_PAGES",$this->lng->txt("Pages") );
		$this->tpl->setVariable("TXT_PAGESALL",$this->lng->txt("all"));
		$this->tpl->setVariable("TXT_PAGESCHAPTER",$this->lng->txt("chapter") );
		if ($_GET["obj_id"] != "") $this->tpl->setVariable("TXT_PAGESPAGE",$this->lng->txt("this page"));
		$this->tpl->setVariable("TXT_PAGESFROM",$this->lng->txt("pages from") );
		$this->tpl->setVariable("TXT_PAGESTO",$this->lng->txt("to") );
		
		$this->tpl->setVariable("BTN_VALUE",$this->lng->txt("start export") );
        $this->tpl->setVariable("BTN_C_VALUE",$this->lng->txt("cancel") );
		
		$this->tpl->setVariable("EXPORT_ACTION","lm_presentation.php?cmd=offlineexport&ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
		
		$this->tpl->show();
		
	}
	function setilCitationMenu()
	{
		include_once("./classes/class.ilTemplate.php");

		$tpl_menu =& new ilTemplate("tpl.buttons.html",true,true);

		$tpl_menu->setCurrentBlock("btn_cell");		

		$tpl_menu->setVariable("BTN_LINK","./lm_presentation.php?frame=maincontent&ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
		$tpl_menu->setVariable("BTN_TXT",$this->lng->txt("back"));
		$tpl_menu->parseCurrentBlock();

		$tpl_menu->setCurrentBlock("btn_row");
		$tpl_menu->parseCurrentBlock();

		return $tpl_menu->get();
	}		


    function setilLMMenu()
	{
		
		include_once("./classes/class.ilTemplate.php");

		$tpl_menu =& new ilTemplate("tpl.buttons.html",true,true);

		$tpl_menu->setCurrentBlock("btn_cell");		
/*
		$tpl_menu->setVariable("BTN_LINK","./lm_presentation.php?cmd=export&ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
		$tpl_menu->setVariable("BTN_TXT",$this->lng->txt("export") );
		// $tpl_menu->setVariable("BTN_TARGET","...");
		$tpl_menu->parseCurrentBlock();
*/		

        if ($_POST["action"]=="details" && count($_POST["target"])==1) 
        {
            $tpl_menu->setVariable("BTN_LINK","./lm_presentation.php?cmd=exportbibinfo&ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
            $tpl_menu->setVariable("BTN_TXT",$this->lng->txt("download"));
            $tpl_menu->parseCurrentBlock();
    
            $tpl_menu->setVariable("BTN_LINK","./lm_presentation.php?cmd=exportbibinfo&print=1&ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
            $tpl_menu->setVariable("BTN_TXT",$this->lng->txt("print"));
            $tpl_menu->parseCurrentBlock();
        }
        else 
        {
            
            $tpl_menu->setVariable("BTN_LINK","./lm_presentation.php?cmd=offlineexportform&ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
            $tpl_menu->setVariable("BTN_TXT",$this->lng->txt("download"));
            // $tpl_menu->setVariable("BTN_TARGET","...");
            $tpl_menu->parseCurrentBlock();
    
            $tpl_menu->setVariable("BTN_LINK","./lm_presentation.php?cmd=offlineexportform&print=1&ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
            $tpl_menu->setVariable("BTN_TXT",$this->lng->txt("print") );
            // $tpl_menu->setVariable("BTN_TARGET","...");
            $tpl_menu->parseCurrentBlock();

        }
		$tpl_menu->setCurrentBlock("btn_row");
		$tpl_menu->parseCurrentBlock();

		return $tpl_menu->get();
		
	}

	function properties()
	{
		// OVERWRITTEN METHOD, TO ADD TRANSLATIONS
		parent::properties();

		// BUTTONS
		$this->tpl->setVariable("BTN1_NAME","addTranslation");
		$this->tpl->setVariable("BTN1_TEXT",$this->lng->txt("cont_new_assignment"));
		
		if($trs = $this->object->getTranslations())
		{
			include_once "./classes/class.ilObjectFactory.php";
			foreach($trs as $tr)
			{
				$tmp_obj = ilObjectFactory::getInstanceByRefId($tr);
				$this->tpl->setCurrentBlock("TRANSLATION_ROW");
				$this->tpl->setVariable("ROW_ID",$tr);
				$this->tpl->setVariable("ROW_TITLE",$tmp_obj->getTitle());
				$this->tpl->parseCurrentBlock();
				
				unset($tmp_obj);
			}
			$this->tpl->setVariable("BTN2_NAME","deleteTranslation");
			$this->tpl->setVariable("BTN2_TEXT",$this->lng->txt("cont_del_assignment"));
		}
		$this->tpl->setCurrentBlock("TRANSLATION");
		$this->tpl->setVariable("TRANSLATION_HEADER",$this->lng->txt("cont_translations"));
		$this->tpl->parseCurrentBlock();
	}

	function addTranslation()
	{
		// SEARCH CANCELED
		if(isset($_POST["cancel"]))
		{
			header("location: lm_edit.php?ref_id=".$this->object->getRefId()."&cmd=properties");
			exit;
		}
		if(isset($_POST["select"]))
		{
			if(is_array($_POST["id"]))
			{
				foreach($_POST["id"] as $id)
				{
					if($id != $this->object->getRefId())
					{
						$this->object->addTranslation($id);
					}
				}
				sendInfo($this->lng->txt("cont_translations_assigned"),true);
				header("location: lm_edit.php?ref_id=".$this->object->getRefId()."&cmd=properties");
				exit;
			}
		}
		$show_search = true;

		$this->tpl->addBlockfile("ADM_CONTENT","adm_content","tpl.dbk_search_translation.html",true);
		$this->tpl->setVariable("F_ACTION", "lm_edit.php?ref_id=".
			$this->object->getRefId()."&cmd=addTranslation");

		if($_POST["search_str"])
		{
			$result = $this->searchTranslation($_POST["search_str"]);

			switch(count($result["meta"]))
			{
				case 0:
					sendInfo($this->lng->txt("cont_no_object_found"));
					break;
				case 1:
					if($result["meta"][0]["id"] == $this->object->getRefId())
					{
						sendInfo($this->lng->txt("cont_no_assign_itself"));
						break;
					}
				default:
					$this->showTranslationSelect($result);
					$show_search = false;
					break;
			}
		}
		if($show_search)
		{
			$this->lng->loadLanguageModule("search");

			$this->tpl->setVariable("SEARCH_TXT",$this->lng->txt("cont_insert_search"));
			$this->tpl->setVariable("SEARCH_ASSIGN_TR",$this->lng->txt("cont_assign_translation"));
			$this->tpl->setVariable("SEARCH_SEARCH_TERM",$this->lng->txt("search_search_term"));
			$this->tpl->setVariable("BTN1_VALUE",$this->lng->txt("search"));
			$this->tpl->setVariable("BTN2_VALUE",$this->lng->txt("cancel"));
		}
		
	}

	function deleteTranslation()
	{
		if(!$_POST["id"])
		{
			sendInfo($this->lng->txt("cont_select_one_translation"));
			header("location: lm_edit.php?ref_id=".$this->object->getRefId()."&cmd=properties");
			exit;
		}
		$this->object->deleteTranslations($_POST["id"]);
		sendInfo($this->lng->txt("cont_assignments_deleted"));
		header("location: lm_edit.php?ref_id=".$this->object->getRefId()."&cmd=properties");
		exit;
	}
	// PRIVATE METHODS
	function showTranslationSelect($a_result)
	{
		include_once "./classes/class.ilObjectFactory.php";

		foreach($a_result["meta"] as $book)
		{
			if(!($path = $this->getContextPath($book["id"])))
			{
				continue;
			}
			$tmp_obj = ilObjectFactory::getInstanceByRefId($book["id"]);
			
			$this->tpl->setCurrentBlock("TR_SELECT_ROW");
			$this->tpl->setVariable("ROW_ID",$book["id"]);
			$this->tpl->setVariable("ROW_TITLE",$tmp_obj->getTitle());
			$this->tpl->setVariable("ROW_DESCRIPTION",$tmp_obj->getDescription());
			$this->tpl->setVariable("ROW_KONTEXT",$path);
			$this->tpl->parseCurrentBlock();
					
			unset($tmp_obj);
		}
		$this->tpl->setCurrentBlock("TR_SELECT");
		$this->tpl->setVariable("SELECT_TXT",$this->lng->txt("cont_select_translation"));
		$this->tpl->setVariable("SELECT_TITLE",$this->lng->txt("title"));
		$this->tpl->setVariable("SELECT_DESCRIPTION",$this->lng->txt("description"));
		$this->tpl->setVariable("SELECT_KONTEXT",$this->lng->txt("context"));

		$this->tpl->setVariable("BTN1_VALUE",$this->lng->txt("assign"));
		$this->tpl->setVariable("BTN2_VALUE",$this->lng->txt("cancel"));
		$this->tpl->parseCurrentBlock();

	}
		
	function searchTranslation($a_search_str)
	{
		include_once("./classes/class.ilSearch.php");

		$search =& new ilSearch($_SESSION["AccountId"]);
		$search->setPerformUpdate(false);
		$search->setSearchString($_POST["search_str"]);
		$search->setCombination("and");
		$search->setSearchFor(array(0 => 'dbk'));
		$search->setSearchIn(array('dbk' => 'meta'));
		$search->setSearchType('new');

		if($search->validate($message))
		{
			$search->performSearch();
		}
		else
		{
			sendInfo($message,true);
			header("location: lm_edit.php?ref_id=".$this->object->getRefId()."&cmd=addTranslation");
			exit;
		}
		return $search->getResultByType('dbk');
	}		

	function getContextPath($a_endnode_id, $a_startnode_id = 1)
	{
		$path = "";

		include_once("./classes/class.ilTree.php");
		
		$tree = new ilTree(1);

		if(!$tree->isInTree($a_startnode_id) or !$tree->isInTree($a_endnode_id))
		{
			return '';
		}
		$tmpPath = $tree->getPathFull($a_endnode_id, $a_startnode_id);

		// count -1, to exclude the learning module itself
		for ($i = 1; $i < (count($tmpPath) - 1); $i++)
		{
			if ($path != "")
			{
				$path .= " > ";
			}

			$path .= $tmpPath[$i]["title"];
		}
		return $path;
	}

	function __checkCitationPost(&$message)
	{
		if(!$_POST["pgt_id"])
		{
			$message = "SELECT ONE<br />";
			return false;
		}
		return true;
	}

	function __parseCitationPost()
	{
		if(!is_array($_POST["pgt_id"]))
		{
			return array();
		}
		foreach($_POST["pgt_id"] as $key => $id)
		{
			switch($_POST["ct_option"][$key])
			{
				case "single":
					$output[] = array("text"	=> $id,
									  "start"	=> $key,
									  "end"		=> $key);
					break;
				case "f":
					$output[] = array("text"	=> $id."f",
									  "start"	=> $key,
									  "end"		=> $key);
					break;
				case "ff":
					$output[] = array("text"	=> $id."ff",
									  "start"	=> $key,
									  "end"		=> $key);
					break;
				case "from":
					$start = $id."-";
					$start_v = $key;
					break;
				case "to":
					if($start)
					{
						$output[] = array("text"		=> $start."".$id,
										  "start"		=> $start_v,
										  "end"			=> $key);
					}
					unset($start);
					unset($start_v);
					break;
			}
		}
		return $output ? $output : array();
	}

	function __applyCitationText($page_xml,$a_start,$a_end)
	{
		global $tpl;

		return true;
/*
		include_once("./content/classes/class.ilCitationTextParser.php");

		$ct_parser = new ilCitationTextParser($page_xml);
		$ct_parser->setStartId($a_start);
		$ct_parser->setEndId($a_end);
		$ct_parser->startParsing();

		echo $ct_parser->output;

		return "hallo";
*/


#		var_dump("<pre>",htmlentities($page_xml),"</pre>");exit;
		// NO TRANSLATIONS IN THE MOMENT
		#$tmp_tpl = new ilTemplate("tpl.citation_paragraph.xsl",true,true,"content");
		#$xsl = $tmp_tpl->get();

		$xsl = file_get_contents($tpl->tplPath."/tpl.citation_paragraph.xsl");
		$args = array( '/_xml' => $page_xml, '/_xsl' => $xsl );
		$xh = xslt_create();
		$params = array ('start_id' => $a_start,
						 'end_id'	=> $a_end);

		$output = xslt_process($xh,"arg:/_xml","arg:/_xsl",NULL,$args, $params);
		
		return $output;
	}
}
	
?>
