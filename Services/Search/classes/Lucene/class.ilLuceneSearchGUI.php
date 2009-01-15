<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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

include_once './Services/Search/classes/class.ilSearchBaseGUI.php';

/** 
* Lucene Search GUI
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
* @ilCtrl_IsCalledBy ilLuceneSearchGUI: ilSearchController
* 
* @ingroup ServicesSearch
*/
class ilLuceneSearchGUI extends ilSearchBaseGUI
{
	protected $ilTabs;
	
	/**
	 * Constructor 
	 */
	public function __construct()
	{
		global $ilTabs;
		
		$this->tabs_gui = $ilTabs;
		parent::__construct();
	}
	
	/**
	 * Execute Command 
	 */
	public function executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		$this->prepareOutput();
		switch($next_class)
		{
			default:
				if(!$cmd)
				{
					$cmd = "showSavedResults";
				}
				$this->$cmd();
				break;
		}
		return true;
	}
	
	/**
	 * Show saved results 
	 * @return
	 */
	public function showSavedResults()
	{
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lucene_search.html','Services/Search');
		
		$this->initFormSearch();
		$this->tpl->setVariable('SEARCH_TABLE',$this->form->getHTML());
	}
	
	/**
	 * Perform search 
	 */
	protected function search()
	{
		global $ilUser;
		
		if(!strlen(ilUtil::stripSlashes($_POST['query'])))
		{
			ilUtil::sendInfo($this->lng->txt('msg_no_search_string'));
			$this->showSavedResults();
			return false;
		}

		include_once './Services/Search/classes/Lucene/class.ilLuceneRPCAdapter.php';
		$adapter = new ilLuceneRPCAdapter();
		$adapter->setQueryString(ilUtil::stripSlashes($_POST['query']));
		$adapter->setMode('search');
		$res = $adapter->send();
		// TODO: Error handling
		
		include_once './Services/Search/classes/Lucene/class.ilLuceneSearchResultFilter.php';
		$filter = ilLuceneSearchResultFilter::getInstance($ilUser->getId());
		$filter->setResultIds($res);
		$filter->filter();
				
		foreach($filter->getFilteredIds() as $ref_id)
		{
			echo "Result: ".ilObject::_lookupTitle($ref_id)."<br />";
		}
		
		$this->showSavedResults();
	}
	
	
	/**
	 * Show search form  
	 */
	protected function initFormSearch()
	{
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this,'search'));
		$this->form->setTitle($this->lng->txt('search'));
		$this->form->addCommandButton('search',$this->lng->txt('search'));
		
		$term = new ilTextInputGUI($this->lng->txt('search_search_term'),'query');
		$term->setValue("der OR die");
		$term->setSize(40);
		$term->setMaxLength(255);
		$term->setRequired(true);
		$this->form->addItem($term);
		
		return true;
	}
	
	/**
	 * Prepare output 
	 */
	public function prepareOutput()
	{
		parent::prepareOutput();
		$this->getTabs();
	}
	
	/**
	 * get tabs 
	 */
	protected function getTabs()
	{
		$this->tabs_gui->addTarget('search',$this->ctrl->getLinkTarget($this));
		$this->tabs_gui->addTarget('search_advanced',$this->ctrl->getLinkTargetByClass('illuceneAdvancedSearchgui'));
		
		$this->tabs_gui->setTabActive('search');
		
	}
}
?>
