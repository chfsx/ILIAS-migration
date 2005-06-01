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
* Class ilSearchGUI
*
* GUI class for 'simple' search
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* @package ilias-search
*
*/
include_once 'Services/Search/classes/class.ilSearchBaseGUI.php';

define('SEARCH_FAST',1);
define('SEARCH_DETAILS',2);
define('SEARCH_AND','and');
define('SEARCH_OR','or');

class ilSearchGUI extends ilSearchBaseGUI
{
	var $root_node;
	var $combination;
	var $string;
	var $type;

	/**
	* Constructor
	* @access public
	*/
	function ilSearchGUI()
	{
		$this->root_node = $_SESSION['search_root'] ? $_SESSION['search_root'] : ROOT_FOLDER_ID;
		$this->setType($_POST['search']['type'] ? $_POST['search']['type'] : $_SESSION['search']['type']);
		$this->setCombination($_POST['search']['combination'] ? $_POST['search']['combination'] : $_SESSION['search']['combination']);
		$this->setString($_POST['search']['string'] ? $_POST['search']['string'] : $_SESSION['search']['string']);
		$this->setDetails($_POST['search']['details'] ? $_POST['search']['details'] : $_SESSION['search']['details']);

		parent::ilSearchBaseGUI();
	}


	/**
	* Set/get type of search (detail or 'fast' search)
	* @access public
	*/
	function setType($a_type)
	{
		$_SESSION['search']['type'] = $this->type = $a_type;
	}
	function getType()
	{
		return $this->type ? $this->type : SEARCH_DETAILS;
	}
	/**
	* Set/get combination of search ('and' or 'or')
	* @access public
	*/
	function setCombination($a_combination)
	{
		$_SESSION['search']['combination'] = $this->combination = $a_combination;
	}
	function getCombination()
	{
		return $this->combination ? $this->combination : SEARCH_AND;
	}
	/**
	* Set/get search string
	* @access public
	*/
	function setString($a_str)
	{
		$_SESSION['search']['string'] = $this->string = $a_str;
	}
	function getString()
	{
		return $this->string;
	}
	/**
	* Set/get details (object types for details search)
	* @access public
	*/
	function setDetails($a_details)
	{
		$_SESSION['search']['details'] = $this->details = $a_details;
	}
	function getDetails()
	{
		return $this->details ? $this->details : array();
	}

		
	function getRootNode()
	{
		return $this->root_node;
	}
	function setRootNode($a_node_id)
	{
		$_SESSION['search_root'] = $this->root_node = $a_node_id;
	}
		
	/**
	* Control
	* @access public
	*/
	function &executeCommand()
	{
		global $rbacsystem;

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				if(!$cmd)
				{
					$cmd = "showSavedResults";
				}

				$this->prepareOutput();
				$this->$cmd();
				break;
		}
		return true;
	}

	function showSearch()
	{

		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.search.html','Services/Search');

		$this->tpl->setVariable("TBL_TITLE",$this->lng->txt('search'));
		$this->tpl->setVariable("TXT_SEARCHAREA",$this->lng->txt('search_area'));
		$this->tpl->setVariable("SEARCH_ACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_SEARCHTERM",$this->lng->txt("search_search_term"));
		$this->tpl->setVariable("TXT_AND",$this->lng->txt('search_all_words'));
		$this->tpl->setVariable("TXT_OR",$this->lng->txt('search_any_word'));
		$this->tpl->setVariable("BTN_SEARCH",$this->lng->txt('search'));

		// Check 'or' as default
		if($this->getCombination() == SEARCH_AND)
		{
			$this->tpl->setVariable("AND_CHECKED",'checked=checked');
		}
		else
		{
			$this->tpl->setVariable("OR_CHECKED",'checked=checked');
		}
		// Set old query string
		$this->tpl->setVariable("FORM_SEARCH_STR",ilUtil::prepareFormOutput($this->getString(),true));

		$this->tpl->setVariable("HREF_UPDATE_AREA",$this->ctrl->getLinkTarget($this,'showSelectRoot'));
		$this->tpl->setVariable("UPDATE_AREA",$this->lng->txt('search_change'));

		// SEARCHTYPE
		$this->tpl->setVariable("TXT_SEARCH_TYPE",$this->lng->txt('search_type'));
		$this->tpl->setVariable("INFO_FAST",$this->lng->txt('search_fast_info'));
		$this->tpl->setVariable("INFO_DETAILS",$this->lng->txt('search_details_info'));

		$this->tpl->setVariable("CHECK_FAST",ilUtil::formRadioButton($this->getType() == SEARCH_FAST ? 1 : 0,
																	 'search[type]',
																	 SEARCH_FAST ));

		$this->tpl->setVariable("CHECK_DETAILS",ilUtil::formRadioButton($this->getType() == SEARCH_DETAILS ? 1 : 0,
																	 'search[type]',
																	 SEARCH_DETAILS));
		// SEARCH DETAILS
		$this->tpl->setVariable("LMS",$this->lng->txt('learning_resources'));
		$this->tpl->setVariable("GLO",$this->lng->txt('objs_glo'));
		$this->tpl->setVariable("MEP",$this->lng->txt('objs_mep'));
		$this->tpl->setVariable("TST",$this->lng->txt('search_tst_svy'));
		$this->tpl->setVariable("FOR",$this->lng->txt('objs_frm'));
		$this->tpl->setVariable("EXC",$this->lng->txt('obj_exc'));
		$this->tpl->setVariable("FIL",$this->lng->txt('objs_file'));

		
		$details = $this->getDetails();
		$this->tpl->setVariable("CHECK_GLO",ilUtil::formCheckbox($details['glo'] ? 1 : 0,'search[details][glo]',1,true));
		$this->tpl->setVariable("CHECK_LMS",ilUtil::formCheckbox($details['lms'] ? 1 : 0,'search[details][lms]',1,true));
		$this->tpl->setVariable("CHECK_MEP",ilUtil::formCheckbox($details['mep'] ? 1 : 0,'search[details][mep]',1,true));
		$this->tpl->setVariable("CHECK_TST",ilUtil::formCheckbox($details['tst'] ? 1 : 0,'search[details][tst]',1,true));
		$this->tpl->setVariable("CHECK_FOR",ilUtil::formCheckbox($details['for'] ? 1 : 0,'search[details][for]',1,true));
		$this->tpl->setVariable("CHECK_EXC",ilUtil::formCheckbox($details['exc'] ? 1 : 0,'search[details][exc]',1,true));
		$this->tpl->setVariable("CHECK_FIL",ilUtil::formCheckbox($details['fil'] ? 1 : 0,'search[details][fil]',1,true));



		// SEARCHAREA
		if($this->getRootNode() == ROOT_FOLDER_ID)
		{
			$this->tpl->setVariable("SEARCHAREA",$this->lng->txt('search_in_magazin'));
		}
		else
		{
			$text = $this->lng->txt('search_below')." '";
			$text .= ilObject::_lookupTitle(ilObject::_lookupObjId($this->getRootNode()));
			$text .= "'";
			$this->tpl->setVariable("SEARCHAREA",$text);
		}

		return true;
	}

	function showSelectRoot()
	{
		global $tree;

		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.search_root_selector.html','Services/Search');

		include_once 'Services/Search/classes/class.ilSearchRootSelector.php';

		sendInfo($this->lng->txt('search_area_info'));

		$exp = new ilSearchRootSelector($this->ctrl->getLinkTarget($this,'showSelectRoot'));
		$exp->setExpand($_GET["search_root_expand"] ? $_GET["search_root_expand"] : $tree->readRootId());
		$exp->setExpandTarget($this->ctrl->getLinkTarget($this,'showSelectRoot'));

		// build html-output
		$exp->setOutput(0);

		$this->tpl->setVariable("EXPLORER",$exp->getOutput());
	}

	function selectRoot()
	{
		$this->setRootNode((int) $_GET['root_id']);
		$this->showSavedResults();

		return true;
	}

	
	function showSavedResults()
	{
		global $ilUser;

		// Read old result sets
		include_once 'Services/Search/classes/class.ilSearchResult.php';
	
		$result_obj = new ilSearchResult($ilUser->getId());
		$result_obj->read();

		$this->showSearch();

		// Show them
		if(count($result_obj->getResults()))
		{
			$this->__showSearchInResults();

			include_once 'Services/Search/classes/class.ilSearchResultPresentationGUI.php';
			
			$search_result_presentation = new ilSearchResultPresentationGUI($result_obj);
			$this->tpl->setVariable("RESULTS",$search_result_presentation->showResults());
		}

		return true;
	}

	function searchInResults()
	{
		$this->search_mode = 'in_results';
		$this->performSearch();

		return true;
	}
		

	function performSearch()
	{
		global $ilUser;

		include_once 'Services/Search/classes/class.ilQueryParser.php';

		// Step 1: parse query string
		$query_parser = new ilQueryParser(ilUtil::stripSlashes($this->getString()));
		$query_parser->setCombination($this->getCombination());
		$query_parser->parse();

		if(!$query_parser->validate())
		{
			sendInfo($query_parser->getMessage());
			$this->showSearch();
			
			return false;
		}

		// Step 2: perform object search. Get an ObjectSearch object via factory. Depends on fulltext or like search type.
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';

		$obj_search =& ilObjectSearchFactory::_getObjectSearchInstance($query_parser);
		$result =& $obj_search->performSearch();


		// Step 3: perform meta keyword search. Get an MetaDataSearch object.
		$meta_search =& ilObjectSearchFactory::_getMetaDataSearchInstance($query_parser);
		$meta_search->setMode('keyword_contribute');
		$result_meta =& $meta_search->performSearch();

		$result->mergeEntries($result_meta);

		// Search in results
		if($this->search_mode == 'in_results')
		{
			include_once 'Services/Search/classes/class.ilSearchResult.php';

			$old_result_obj = new ilSearchResult($ilUser->getId());
			$old_result_obj->read();

			$result->diffEntries($old_result_obj);
		}
			

		// Step 4: merge and validate results
		$result->filter($this->getRootNode());

		$this->showSearch();

		if(!count($result->getResults()))
		{
			sendInfo($this->lng->txt('search_no_match'));
		}
		else
		{
			$this->__showSearchInResults();
		}

		// Step 6: show results
		include_once 'Services/Search/classes/class.ilSearchResultPresentationGUI.php';

		$search_result_presentation = new ilSearchResultPresentationGUI($result);
		$this->tpl->setVariable("RESULTS",$search_result_presentation->showResults());

		// Step 7: save as user result
		$result->setUserId($ilUser->getId());
		$result->save();

		return true;
	}

		

	function prepareOutput()
	{
		parent::prepareOutput();

		$this->tpl->setVariable("H_FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_HEADER",$this->lng->txt('search'));

		$this->tpl->addBlockFile("TABS","tabs","tpl.tabs.html");

		$this->tpl->setCurrentBlock("tab");
		$this->tpl->setVariable("TAB_TYPE","tabactive");
		$this->tpl->setVariable("TAB_LINK",$this->ctrl->getLinkTarget($this));
		$this->tpl->setVariable("TAB_TEXT",$this->lng->txt("search"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("tab");
		$this->tpl->setVariable("TAB_TYPE","tabinactive");
		$this->tpl->setVariable("TAB_LINK",$this->ctrl->getLinkTarget($this));
		$this->tpl->setVariable("TAB_TEXT",$this->lng->txt("search_advanced"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("tab");
		$this->tpl->setVariable("TAB_TYPE","tabinactive");
		$this->tpl->setVariable("TAB_LINK",$this->ctrl->getLinkTargetByClass('ilsearchresultgui'));
		$this->tpl->setVariable("TAB_TEXT",$this->lng->txt("search_search_results"));
		$this->tpl->parseCurrentBlock();
		
	}

	function __showSearchInResults()
	{
		$this->tpl->setCurrentBlock("search_results");
		$this->tpl->setVariable("BTN_SEARCHRESULTS",$this->lng->txt('search_in_result'));
		$this->tpl->parseCurrentBlock();

		return true;
	}
}
?>
