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
* Class ilSearchResultPresaentationGUI
*
* class for presentastion of search results. Called from class.ilSearchGUI or class.ilAdvancedSearchGUI
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* @extends ilObjectGUI
* @package ilias-core
*/

class ilSearchResultPresentationGUI
{
	var $tpl;
	var $lng;

	var $result = 0;

	function ilSearchResultPresentationGUI(&$result)
	{
		global $tpl,$lng,$ilCtrl;

		$this->lng =& $lng;
		
		$this->result =& $result;

		$this->type_ordering = array(
			"cat", "crs", "grp", "chat", "frm", "lres",
			"glo", "webr", "file", "exc",
			"tst", "svy", "mep", "qpl", "spl");

		$this->ctrl =& $ilCtrl;
	}

	function &showResults()
	{
		// Get results
		$results = $this->result->getResultsForPresentation();

		return $html =& $this->renderItemList($results);


	}

	function &renderItemList(&$results)
	{
		global $objDefinition;

		$html = '';

		$cur_obj_type = "";
		$tpl =& $this->newBlockTemplate();
		$first = true;
		
		foreach($this->type_ordering as $act_type)
		{
			$item_html = array();

			if (is_array($results[$act_type]))
			{
				foreach($results[$act_type] as $key => $item)
				{
					// get list gui class for each object type
					if ($cur_obj_type != $item["type"])
					{
						include_once 'Services/Search/classes/class.ilSearchObjectListFactory.php';

						$item_list_gui = ilSearchObjectListFactory::_getInstance($item['type']);
					}
					$html = $item_list_gui->getListItemHTML(
						$item["ref_id"],
						$item["obj_id"], 
						$item["title"], 
						$item["description"]);

					if($html)
					{
						$item_html[] = $html;
					}
				}
				// output block for resource type
				if(count($item_html) > 0)
				{
					// separator row
					if (!$first)
					{
						$this->addSeparatorRow($tpl);
					}
					$first = false;
						
					// add a header for each resource type
					$this->addHeaderRow($tpl, $act_type);
					$this->resetRowType();
						
					// content row
					foreach($item_html as $html)
					{
						$this->addStandardRow($tpl, $html);
					}
				}
			}
		}

		
		return $tpl->get();
	}

	/**
	* adds a header row to a block template
	*
	* @param	object		$a_tpl		block template
	* @param	string		$a_type		object type
	* @access	private
	*/
	function addHeaderRow(&$a_tpl, $a_type)
	{
		if ($a_type != "lres")
		{
			$icon = ilUtil::getImagePath("icon_".$a_type.".gif");
			$title = $this->lng->txt("objs_".$a_type);
		}
		else
		{
			$icon = ilUtil::getImagePath("icon_lm_b.gif");
			$title = $this->lng->txt("learning_resources");
		}
		$a_tpl->setCurrentBlock("container_header_row");
		$a_tpl->setVariable("HEADER_IMG", $icon);
		$a_tpl->setVariable("BLOCK_HEADER_CONTENT", $title);
		$a_tpl->parseCurrentBlock();
		$a_tpl->touchBlock("container_row");
	}

	function resetRowType()
	{
		$this->cur_row_type = "";
	}

	/**
	* adds a standard row to a block template
	*
	* @param	object		$a_tpl		block template
	* @param	string		$a_html		html code
	* @access	private
	*/
	function addStandardRow(&$a_tpl, $a_html)
	{
		$this->cur_row_type = ($this->cur_row_type == "row_type_1")
			? "row_type_2"
			: "row_type_1";

		$a_tpl->touchBlock($this->cur_row_type);
		$a_tpl->setCurrentBlock("container_standard_row");
		$a_tpl->setVariable("BLOCK_ROW_CONTENT", $a_html);
		$a_tpl->parseCurrentBlock();
		$a_tpl->touchBlock("container_row");
	}


	/**
	* returns a new list block template
	*
	* @access	private
	* @return	object		block template
	*/
	function &newBlockTemplate()
	{
		$tpl =& new ilTemplate ("tpl.container_list_block.html",true, true);
		$this->cur_row_type = "row_type_1";

		return $tpl;
	}

	function addSeparatorRow(&$a_tpl)
	{
		$a_tpl->touchBlock("separator_row");
		$a_tpl->touchBlock("container_row");
	}



}

?>