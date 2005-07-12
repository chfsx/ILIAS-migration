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
* Class ilAdvancedSearchGUI
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
include_once 'Services/MetaData/classes/class.ilMDUtilSelect.php';

class ilAdvancedSearchGUI extends ilSearchBaseGUI
{

	/**
	* array of all options select boxes,'and' 'or' and query strings
	* @access public
	*/
	var $options = array();

	/**
	* Constructor
	* @access public
	*/
	function ilAdvancedSearchGUI()
	{
		parent::ilSearchBaseGUI();

		$this->lng->loadLanguageModule('meta');

		$this->__setSearchOptions($_POST);
	}

	function getRootNode()
	{
		return ROOT_FOLDER_ID;
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
	function reset()
	{
		$this->options = array();
		$this->showSearch();
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

		include_once 'Services/Search/classes/class.ilSearchResult.php';

		$res =& new ilSearchResult();

		if($res_con =& $this->__performContentSearch())
		{
			$this->__storeEntries($res,$res_con);
		}
		if($res_tit =& $this->__performTitleSearch())
		{
			$this->__storeEntries($res,$res_tit);
		}
		if($res_lan =& $this->__performLanguageSearch())
		{
			$this->__storeEntries($res,$res_lan);
		}
		if($res_gen =& $this->__performGeneralSearch())
		{
			$this->__storeEntries($res,$res_gen);
		}
		if($res_lif =& $this->__performLifecycleSearch())
		{
			$this->__storeEntries($res,$res_lif);
		}
		if($res_con =& $this->__performContributeSearch())
		{
			$this->__storeEntries($res,$res_con);
		}
		if($res_ent =& $this->__performEntitySearch())
		{
			$this->__storeEntries($res,$res_ent);
		}
		if($res_req =& $this->__performRequirementSearch())
		{
			$this->__storeEntries($res,$res_req);
		}
		if($res_for =& $this->__performFormatSearch())
		{
			$this->__storeEntries($res,$res_for);
		}
		if($res_edu =& $this->__performEducationalSearch())
		{
			$this->__storeEntries($res,$res_edu);
		}
		if($res_typ =& $this->__performTypicalAgeRangeSearch())
		{
			$this->__storeEntries($res,$res_typ);
		}
		if($res_rig =& $this->__performRightsSearch())
		{
			$this->__storeEntries($res,$res_rig);
		}
		if($res_cla =& $this->__performClassificationSearch())
		{
			$this->__storeEntries($res,$res_cla);
		}
		if($res_tax =& $this->__performTaxonSearch())
		{
			$this->__storeEntries($res,$res_tax);
		}
		if($res_key =& $this->__performKeywordSearch())
		{
			$this->__storeEntries($res,$res_key);
		}

		if($this->search_mode == 'in_results')
		{
			include_once 'Services/Search/classes/class.ilSearchResult.php';

			$old_result_obj = new ilSearchResult($ilUser->getId());
			$old_result_obj->read(ADVANCED_SEARCH);

			$res->diffEntriesFromResult($old_result_obj);
		}

		
		$res->filter($this->getRootNode(),false);

		if(!count($res->getResults()))
		{
			sendInfo($this->lng->txt('search_no_match'));
		}
		else
		{
			$this->__showSearchInResults();
		}			

		$this->showSearch();

		include_once 'Services/Search/classes/class.ilSearchResultPresentationGUI.php';

		$search_result_presentation = new ilSearchResultPresentationGUI($res);
		$this->tpl->setVariable("RESULTS",$search_result_presentation->showResults());

		$res->setUserId($ilUser->getId());
		$res->save(ADVANCED_SEARCH);


		return true;
	}


	function showSearch()
	{
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.advanced_search.html','Services/Search');

		// Header
		$this->tpl->setVariable("SEARCH_ACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TBL_TITLE",$this->lng->txt('search_advanced'));

		// Content
		$this->tpl->setVariable("TXT_CONTENT",$this->lng->txt('search_content'));
		$this->tpl->setVariable("TXT_OR",$this->lng->txt('search_any_word'));
		$this->tpl->setVariable("TXT_AND",$this->lng->txt('search_all_words'));

		if($this->options['content_ao'] == 'and')
		{
			$this->tpl->setVariable("CONTENT_AND_CHECKED",'checked=checked');
		}
		else
		{
			$this->tpl->setVariable("CONTENT_OR_CHECKED",'checked=checked');
		}
		$this->tpl->setVariable("FRM_CONTENT",ilUtil::prepareFormOutput($this->options['content']));

		// Type
		$this->tpl->setVariable("TXT_TYPE",$this->lng->txt('type'));
		$this->tpl->setVariable("SEL_TYPE",$this->__getFilterSelect());

		// General
		$this->tpl->setVariable("TXT_GEN",$this->lng->txt('meta_general'));
		$this->tpl->setVariable("TXT_LAN",$this->lng->txt('language'));
		$this->tpl->setVariable("SEL_LAN",ilMDUtilSelect::_getLanguageSelect($this->options['language'],
																			 'search_adv[language]'
																			 ,array(0 => $this->lng->txt('search_any'))));

		$this->tpl->setVariable("TXT_TITLE",$this->lng->txt('meta_title').'/'.
								$this->lng->txt('meta_keyword').'/'.
								$this->lng->txt('meta_description'));
		$this->tpl->setVariable("TXT_COV",$this->lng->txt('meta_coverage'));
		$this->tpl->setVariable("TXT_STRUCT",$this->lng->txt('meta_structure'));
		$this->tpl->setVariable("SEL_STRUCT",ilMDUtilSelect::_getStructureSelect($this->options['structure'],
																				 'search_adv[structure]',
																				 array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("FRM_TITLE",ilUtil::prepareFormOutput($this->options['title'],true));
		$this->tpl->setVariable("FRM_COVERAGE",ilUtil::prepareFormOutput($this->options['coverage'],true));

		if($this->options['title_ao'] == 'and')
		{
			$this->tpl->setVariable("TITLE_AND_CHECKED",'checked=checked');
		}
		else
		{
			$this->tpl->setVariable("TITLE_OR_CHECKED",'checked=checked');
		}
		if($this->options['coverage_ao'] == 'and')
		{
			$this->tpl->setVariable("COVERAGE_AND_CHECKED",'checked=checked');
		}
		else
		{
			$this->tpl->setVariable("COVERAGE_OR_CHECKED",'checked=checked');
		}

		

		// Lifecycle
		$this->tpl->setVariable("TXT_LIFECYCLE",$this->lng->txt('meta_lifecycle'));
		$this->tpl->setVariable("TXT_STATUS",$this->lng->txt('meta_status'));
		$this->tpl->setVariable("SEL_STATUS",
								ilMDUtilSelect::_getStatusSelect($this->options['status'],
																 'search_adv[status]',
																 array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("TXT_VERSION",$this->lng->txt('meta_version'));
		$this->tpl->setVariable("FRM_VERSION",ilUtil::prepareFormOutput($this->options['version'],true));

		$this->tpl->setVariable("TXT_CONTRIBUTOR",$this->lng->txt('meta_contribute'));
		$this->tpl->setVariable("SEL_CONTRIBUTOR",
								ilMDUtilSelect::_getRoleSelect($this->options['role'],
															   'search_adv[role]',
															   array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("FRM_ENTITY",ilUtil::prepareFormOutput($this->options['entity'],true));

		if($this->options['entity_ao'] == 'and')
		{
			$this->tpl->setVariable("ENTITY_AND_CHECKED",'checked=checked');
		}
		else
		{
			$this->tpl->setVariable("ENTITY_OR_CHECKED",'checked=checked');
		}
		if($this->options['version_ao'] == 'and')
		{
			$this->tpl->setVariable("VERSION_AND_CHECKED",'checked=checked');
		}
		else
		{
			$this->tpl->setVariable("VERSION_OR_CHECKED",'checked=checked');
		}

		// Technical
		$this->tpl->setVariable("TXT_TECHNICAL",$this->lng->txt('meta_technical'));
		$this->tpl->setVariable("TXT_FORMAT",$this->lng->txt('meta_format'));
		$this->tpl->setVariable("TXT_OS",$this->lng->txt('meta_operating_system'));
		$this->tpl->setVariable("TXT_BROWSER",$this->lng->txt('meta_browser'));
		$this->tpl->setVariable("TXT_DURATION",$this->lng->txt('meta_duration'));
		$this->tpl->setVariable("FROM",$this->lng->txt('from'));
		$this->tpl->setVariable("TIL",$this->lng->txt('until'));
		
		$this->tpl->setVariable("SEL_FORMAT",
								ilMDUtilSelect::_getFormatSelect($this->options['format'],
																 'search_adv[format]',
																 array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_OS",
								ilMDUtilSelect::_getOperatingSystemSelect($this->options['os'],
																		  'search_adv[os]',
																		  array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_BROWSER",
								ilMDUtilSelect::_getBrowserSelect($this->options['browser'],
																  'search_adv[browser]',
																  array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_DURATION_1",
								ilMDUtilSelect::_getDurationSelect('','md_lan',array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_DURATION_2",
								ilMDUtilSelect::_getDurationSelect('','md_lan',array(0 => $this->lng->txt('search_any'))));

		// Educational
		$this->tpl->setVariable("TXT_EDUCATIONAL",$this->lng->txt('meta_education'));
		$this->tpl->setVariable("TXT_INTERACTIVITY",$this->lng->txt('meta_interactivity_type'));
		$this->tpl->setVariable("TXT_RESOURCE",$this->lng->txt('meta_learning_resource_type'));
		$this->tpl->setVariable("TXT_LEVEL",$this->lng->txt('meta_interactivity_level'));
		$this->tpl->setVariable("TXT_DENSITY",$this->lng->txt('meta_semantic_density'));
		$this->tpl->setVariable("TXT_END_USER",$this->lng->txt('meta_intended_end_user_role'));
		$this->tpl->setVariable("TXT_CONTEXT",$this->lng->txt('meta_context'));
		$this->tpl->setVariable("TXT_DIFFICULTY",$this->lng->txt('meta_difficulty'));
		$this->tpl->setVariable("TXT_AGE_RANGE",$this->lng->txt('meta_typical_age_range'));
		$this->tpl->setVariable("TXT_LEARNING_TIME",$this->lng->txt('meta_typical_learning_time'));


		$this->tpl->setVariable("SEL_INTERACTIVITY",
								ilMDUtilSelect::_getInteractivityTypeSelect($this->options['int_type'],
																			'search_adv[int_type]',
																			array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_RESOURCE",
								ilMDUtilSelect::_getLearningResourceTypeSelect($this->options['lea_type'],
																			   'search_adv[lea_type]',
																			   array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_LEVEL_1",
								ilMDUtilSelect::_getInteractivityLevelSelect($this->options['int_level_1'],
																			 'search_adv[int_level_1]',
																			 array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_LEVEL_2",
								ilMDUtilSelect::_getInteractivityLevelSelect($this->options['int_level_2'],
																			 'search_adv[int_level_2]',
																			 array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_DENSITY_1",
								ilMDUtilSelect::_getSemanticDensitySelect($this->options['sem_1'],
																		  'search_adv[sem_1]',
																		  array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_DENSITY_2",
								ilMDUtilSelect::_getSemanticDensitySelect($this->options['sem_2'],
																		  'search_adv[sem_2]',
																		  array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_END_USER",
								ilMDUtilSelect::_getIntendedEndUserRoleSelect($this->options['int_role'],
																			  'search_adv[int_role]',
																			  array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_CONTEXT",
								ilMDUtilSelect::_getContextSelect($this->options['con'],
																  'search_adv[con]',
																  array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_DIFFICULTY_1",
								ilMDUtilSelect::_getDifficultySelect($this->options['dif_1'],
																	 'search_adv[dif_1]',
																	 array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_DIFFICULTY_2",
								ilMDUtilSelect::_getDifficultySelect($this->options['dif_2'],
																	 'search_adv[dif_2]',
																	 array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_AGE_RANGE_1",
								ilMDUtilSelect::_getTypicalAgeRangeSelect($this->options['typ_age_1'],
																		  'search_adv[typ_age_1]',
																		  array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_AGE_RANGE_2",
								ilMDUtilSelect::_getTypicalAgeRangeSelect($this->options['typ_age_2'],
																		  'search_adv[typ_age_2]',
																		  array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_LEARNING_TIME_1",
								ilMDUtilSelect::_getTypicalLearningTimeSelect($this->options['typ_lea_1'],
																			  'search_adv[typ_lea_1]',
																			  array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_LEARNING_TIME_2",
								ilMDUtilSelect::_getTypicalLearningTimeSelect($this->options['typ_lea_2'],
																			  'search_adv[typ_lea_2]',
																			  array(0 => $this->lng->txt('search_any'))));

		// RIGHTS
		$this->tpl->setVariable("TXT_RIGHTS",$this->lng->txt('meta_rights'));
		$this->tpl->setVariable("TXT_COSTS",$this->lng->txt('meta_cost'));
		$this->tpl->setVariable("TXT_COPYRIGHT",$this->lng->txt('meta_copyright_and_other_restrictions'));
		
		$this->tpl->setVariable("SEL_COSTS",
								ilMDUtilSelect::_getCostsSelect($this->options['costs'],
																'search_adv[costs]',array(0 => $this->lng->txt('search_any'))));
		$this->tpl->setVariable("SEL_COPYRIGHT",
								ilMDUtilSelect::_getCopyrightAndOtherRestrictionsSelect($this->options['copyright'],
																						'search_adv[copyright]',
																						array(0 => $this->lng->txt('search_any'))));

		// CLASSIFICATION
		$this->tpl->setVariable("TXT_CLASSIFICATION",$this->lng->txt('meta_classification'));
		$this->tpl->setVariable("TXT_PURPOSE",$this->lng->txt('meta_purpose'));
		$this->tpl->setVariable("TXT_TAXON",$this->lng->txt('meta_taxon'));
		$this->tpl->setVariable("TXT_KEYWORD",$this->lng->txt('meta_keyword'));
		
		$this->tpl->setVariable("SEL_PURPOSE",
								ilMDUtilSelect::_getPurposeSelect($this->options['purpose'],
																  'search_adv[purpose]',
																  array(0 => $this->lng->txt('search_any'))));

		if($this->options['taxon_ao'] == 'and')
		{
			$this->tpl->setVariable("TAXON_AND_CHECKED",'checked=checked');
		}
		else
		{
			$this->tpl->setVariable("TAXON_OR_CHECKED",'checked=checked');
		}
		$this->tpl->setVariable("FRM_TAXON",ilUtil::prepareFormOutput($this->options['taxon'],true));

		if($this->options['keyword_ao'] == 'and')
		{
			$this->tpl->setVariable("KEYWORD_AND_CHECKED",'checked=checked');
		}
		else
		{
			$this->tpl->setVariable("KEYWORD_OR_CHECKED",'checked=checked');
		}
		$this->tpl->setVariable("FRM_KEYWORD",ilUtil::prepareFormOutput($this->options['keyword'],true));

		$this->tpl->setVariable("BTN_SEARCH",$this->lng->txt('search'));
		$this->tpl->setVariable("BTN_RESET",$this->lng->txt('reset'));

		return true;
	}

	function prepareOutput()
	{
		parent::prepareOutput();

		$this->tpl->setVariable("H_FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_HEADER",$this->lng->txt('search'));

		$this->tpl->addBlockFile("TABS","tabs","tpl.tabs.html");

		$this->tpl->setCurrentBlock("tab");
		$this->tpl->setVariable("TAB_TYPE","tabinactive");
		$this->tpl->setVariable("TAB_LINK",$this->ctrl->getLinkTargetByClass('ilsearchgui'));
		$this->tpl->setVariable("TAB_TEXT",$this->lng->txt("search"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("tab");
		$this->tpl->setVariable("TAB_TYPE","tabactive");
		$this->tpl->setVariable("TAB_LINK",$this->ctrl->getLinkTarget($this));
		$this->tpl->setVariable("TAB_TEXT",$this->lng->txt("search_advanced"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("tab");
		$this->tpl->setVariable("TAB_TYPE","tabinactive");
		$this->tpl->setVariable("TAB_LINK",$this->ctrl->getLinkTargetByClass('ilsearchresultgui'));
		$this->tpl->setVariable("TAB_TEXT",$this->lng->txt("search_search_results"));
		$this->tpl->parseCurrentBlock();
		
	}
	function saveResult()
	{
		include_once 'Services/Search/classes/class.ilUserResult.php';
		include_once 'Services/Search/classes/class.ilSearchFolder.php';

		global $ilUser;

		if(!$_POST['folder'])
		{
			sendInfo($this->lng->txt('search_select_one'));
			$this->showSavedResults();

			return false;
		}
		if(!count($_POST['result']))
		{
			sendInfo($this->lng->txt('search_select_one_result'));
			$this->showSavedResults();

			return false;
		}

		$folder_obj =& new ilSearchFolder($ilUser->getId(),(int) $_POST['folder']);

		foreach($_POST['result'] as $ref_id)
		{

			$title = ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id));
			$target = addslashes(serialize(array('type' => ilObject::_lookupType(ilObject::_lookupObjId($ref_id)),
												 'id'	=> $ref_id)));

			$search_res_obj =& new ilUserResult($ilUser->getId());
			$search_res_obj->setTitle($title);
			$search_res_obj->setTarget($target);

			$folder_obj->assignResult($search_res_obj);
			unset($search_res_obj);
		}
		sendInfo($this->lng->txt('search_results_saved'));
		$this->showSavedResults();

	}


	// PRIVATE
	function showSavedResults()
	{
		global $ilUser;

		// Read old result sets
		include_once 'Services/Search/classes/class.ilSearchResult.php';
	
		$result_obj = new ilSearchResult($ilUser->getId());
		$result_obj->read(ADVANCED_SEARCH);

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

	function &__performContentSearch()
	{
		if(!$this->options['content'])
		{
			return false;
		}
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';
		include_once 'Services/Search/classes/class.ilSearchResult.php';

		$res =& new ilSearchResult();


		$query_parser = new ilQueryParser(ilUtil::stripSlashes($this->options['content']));
		$query_parser->setCombination($this->options['content_ao']);
		$query_parser->parse();

		if($this->options['type'] == 'all' or $this->options['type'] == 'lms')
		{
			// LM content search
			$lm_search =& ilObjectSearchFactory::_getLMContentSearchInstance($query_parser);
			$res_cont =& $lm_search->performSearch();
			$res->mergeEntries($res_cont);
			
			// HTLM content search
			$htlm_search =& ilObjectSearchFactory::_getHTLMSearchInstance($query_parser);
			$res_htlm =& $htlm_search->performSearch();
			$res->mergeEntries($res_htlm);
		}
		if($this->options['type'] == 'all' or $this->options['type'] == 'tst')
		{
			$tst_search =& ilObjectSearchFactory::_getTestSearchInstance($query_parser);
			$res_tes =& $tst_search->performSearch();
			$res->mergeEntries($res_tes);
		}
		if($this->options['type'] == 'all' or $this->options['type'] == 'mep')
		{
			$med_search =& ilObjectSearchFactory::_getMediaPoolSearchInstance($query_parser);
			$res_med =& $med_search->performSearch();
			$res->mergeEntries($res_med);
		}
		if($this->options['type'] == 'all' or $this->options['type'] == 'glo')
		{
			$glo_search =& ilObjectSearchFactory::_getGlossaryDefinitionSearchInstance($query_parser);
			$res_glo =& $glo_search->performSearch();
			$res->mergeEntries($res_glo);
		}
		if($this->options['type'] == 'all' or $this->options['type'] == 'webr')
		{
			$web_search =& ilObjectSearchFactory::_getWebresourceSearchInstance($query_parser);
			$res_web =& $web_search->performSearch();
			$res->mergeEntries($res_web);
		}

		
		

		return $res;
	}


	function &__performTitleSearch()
	{
		if(!$this->options['title'])
		{
			return false;
		}

		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';

		$query_parser = new ilQueryParser(ilUtil::stripSlashes($this->options['title']));
		$query_parser->setCombination($this->options['title_ao']);
		$query_parser->parse();

		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance($query_parser);
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('title_description');
		$meta_search->setOptions($this->options);
		$res_tit =& $meta_search->performSearch();

		$meta_search->setMode('keyword_all');
		$res_key =& $meta_search->performSearch();
		
		// merge them
		$res_tit->mergeEntries($res_key);
		
		return $res_tit;
	}



	function &__performGeneralSearch()
	{
		if(!$this->options['coverage'] and !$this->options['structure'])
		{
			return false;
		}

		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';

		if($this->options['coverage'])
		{
			$query_parser = new ilQueryParser(ilUtil::stripSlashes($this->options['coverage']));
			$query_parser->setCombination($this->options['coverage_ao']);
			$query_parser->parse();
		}
		else
		{
			$query_parser = new ilQueryParser('');
		}

		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance($query_parser);
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('general');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}

	function &__performLifecycleSearch()
	{
		// Return if 'any'
		if(!$this->options['status'] and !$this->options['version'])
		{
			return false;
		}
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';

		$query_parser = new ilQueryParser(ilUtil::stripSlashes($this->options['version']));
		$query_parser->setCombination($this->options['version_ao']);
		$query_parser->parse();

		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance($query_parser);
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('lifecycle');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}		
	function &__performLanguageSearch()
	{
		if(!$this->options['language'])
		{
			return false;
		}
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';


		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance(new ilQueryParser(''));
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('language');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}
	function &__performContributeSearch()
	{
		if(!strlen($this->options['role']))
		{
			return false;
		}
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';


		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance(new ilQueryParser(''));
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('contribute');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}
	function &__performEntitySearch()
	{
		// Return if 'any'
		if(!$this->options['entity'])
		{
			return false;
		}
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';

		$query_parser = new ilQueryParser(ilUtil::stripSlashes($this->options['entity']));
		$query_parser->setCombination($this->options['entity_ao']);
		$query_parser->parse();

		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance($query_parser);
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('entity');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}		


	function &__performRequirementSearch()
	{
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';


		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance(new ilQueryParser(''));
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('requirement');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}
	function &__performFormatSearch()
	{
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';


		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance(new ilQueryParser(''));
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('format');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}
	function &__performEducationalSearch()
	{
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';


		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance(new ilQueryParser(''));
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('educational');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}
	function &__performTypicalAgeRangeSearch()
	{
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';


		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance(new ilQueryParser(''));
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('typical_age_range');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}
	function &__performRightsSearch()
	{
		if(!$this->options['copyright'] and !$this->options['costs'])
		{
			return false;
		}
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';


		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance(new ilQueryParser(''));
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('rights');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}

	function &__performClassificationSearch()
	{
		// Return if 'any'
		if(!$this->options['purpose'])
		{
			return false;
		}
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';


		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance(new ilQueryParser(''));
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('classification');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}

	function &__performTaxonSearch()
	{
		// Return if 'any'
		if(!$this->options['taxon'])
		{
			return false;
		}
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';

		$query_parser = new ilQueryParser(ilUtil::stripSlashes($this->options['taxon']));
		$query_parser->setCombination($this->options['taxon_ao']);
		$query_parser->parse();

		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance($query_parser);
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('taxon');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}

	function &__performKeywordSearch()
	{
		// Return if 'any'
		if(!$this->options['keyword'])
		{
			return false;
		}
		include_once 'Services/Search/classes/class.ilObjectSearchFactory.php';
		include_once 'Services/Search/classes/class.ilQueryParser.php';

		$query_parser = new ilQueryParser(ilUtil::stripSlashes($this->options['keyword']));
		$query_parser->setCombination($this->options['keyword_ao']);
		$query_parser->parse();

		$meta_search =& ilObjectSearchFactory::_getAdvancedSearchInstance($query_parser);
		$meta_search->setFilter($this->filter);
		$meta_search->setMode('keyword');
		$meta_search->setOptions($this->options);
		$res =& $meta_search->performSearch();

		return $res;
	}

	function __setSearchOptions(&$post_vars)
	{
		if(isset($_POST['cmd']['performSearch']))
		{
			$this->options = $_SESSION['search_adv'] = $_POST['search_adv'];
		}
		else
		{
			$this->options = $_SESSION['search_adv'];
		}
		
		$_POST['result'] = $_POST['rep_item_id'];

		$this->filter = array();

		switch($this->options['type'])
		{
			case 'webr':
				$this->filter[] = 'webr';
				break;

			case 'lms':
				$this->filter[] = 'lm';
				$this->filter[] = 'dbk';
				$this->filter[] = 'pg';
				$this->filter[] = 'st';
				$this->filter[] = 'sahs';
				$this->filter[] = 'htlm';
				break;

			case 'glo':
				$this->filter[] = 'glo';
				break;

			case 'tst':
				$this->filter[] = 'tst';
				$this->filter[] = 'svy';
				$this->filter[] = 'qpl';
				$this->filter[] = 'spl';
				break;

			case 'mep':
				$this->filter[] = 'mep';
				break;
					
			case 'crs':
				$this->filter[] = 'crs';
				break;

			default:
				$this->filter[] = 'lm';
				$this->filter[] = 'dbk';
				$this->filter[] = 'pg';
				$this->filter[] = 'st';
				$this->filter[] = 'sahs';
				$this->filter[] = 'htlm';
		}
		return true;
	}

	function __getFilterSelect()
	{
		$options = array('all' => $this->lng->txt('search_any'),
						 'lms' => $this->lng->txt('learning_resources'),
						 'crs' => $this->lng->txt('objs_crs'),
						 'tst' => $this->lng->txt('search_tst_svy'),
						 'mep' => $this->lng->txt('objs_mep'),
						 'glo' => $this->lng->txt('objs_glo'),
						 'webr' => $this->lng->txt('objs_webr'));


		return ilUtil::formSelect($this->options['type'],'search_adv[type]',$options,false,true);
	}


	function __storeEntries(&$res,&$new_res)
	{
		if($this->stored == false)
		{
			$res->mergeEntries($new_res);
			$this->stored = true;

			return true;
		}
		else
		{
			$res->intersectEntries($new_res);
			
			return true;
		}
	}
	/**
	* Show search in results button. If search was successful
	* @return void
	* @access public
	*/
	function __showSearchInResults()
	{
		$this->tpl->setCurrentBlock("search_results");
		$this->tpl->setVariable("BTN_SEARCHRESULTS",$this->lng->txt('search_in_result'));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("save_result");
		$this->tpl->setVariable("DOWNRIGHT",ilUtil::getImagePath('arrow_downright.gif'));
		$this->tpl->setVariable("BTN_SAVE_RESULT",$this->lng->txt('save'));
		$this->tpl->setVariable("SELECT_FOLDER",$this->__getFolderSelect());
		$this->tpl->parseCurrentBlock();

		return true;
	}
	function __getFolderSelect()
	{
		global $ilUser;

		include_once 'Services/Search/classes/class.ilSearchFolder.php';

		// INITIATE SEARCH FOLDER OBJECT
		$folder_obj =& new ilSearchFolder($ilUser->getId());


		$subtree = $folder_obj->getSubtree();

		$options[0] = $this->lng->txt("search_select_one_folder_select");
		$options[$folder_obj->getRootId()] = $this->lng->txt("search_save_as_select")." ".$this->lng->txt("search_search_results");
		
		foreach($subtree as $node)
		{
			if($node["obj_id"] == $folder_obj->getRootId())
			{
				continue;
			}
			// CREATE PREFIX
			$prefix = $this->lng->txt("search_save_as_select");
			for($i = 1; $i < $node["depth"];++$i)
			{
				$prefix .= "&nbsp;&nbsp;";
			}
			$options[$node["obj_id"]] = $prefix.$node["title"];
		}
		return ilUtil::formSelect(0,'folder',$options,false,true);
	}

		
}
?>
