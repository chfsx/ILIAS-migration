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

require_once("content/classes/class.ilPageContent.php");

/**
* Class ilLMTableData
*
* Table Data content object - a table cell (see ILIAS DTD)
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilLMTableData extends ilPageContent
{
	var $dom;

	/**
	* Constructor
	* @access	public
	*/
	function ilLMTableData(&$a_dom)
	{
		parent::ilPageContent();
		$this->setType("td");

		$this->dom =& $a_dom;
	}

	/**
	* insert new row after cell
	*/
	function newRowAfter()
	{
		$td =& $this->getNode();
		$parent_tr =& $td->parent_node();
		$new_tr = $parent_tr->clone_node(true);
		if ($next_tr =& $parent_tr->next_sibling())
		{
			$new_tr =& $next_tr->insert_before($new_tr, $next_tr);
		}
		else
		{
			$parent_table =& $parent_tr->parent_node();
			$new_tr =& $parent_table->append_child($new_tr);
		}

		// remove td content of new row
		$this->deleteRowContent($new_tr);
	}


	/**
	* insert new row after cell
	*/
	function newRowBefore()
	{
		$td =& $this->getNode();
		$parent_tr =& $td->parent_node();
		$new_tr = $parent_tr->clone_node(true);
		$new_tr =& $parent_tr->insert_before($new_tr, $parent_tr);

		// remove td content of new row
		$this->deleteRowContent($new_tr);
	}


	/**
	* delete content of cells of a row (not the cells itself)
	*
	* @access private
	*/
	function deleteRowContent(&$a_row_node)
	{
		// remove td content of row
		$tds =& $a_row_node->child_nodes();
		for($i=0; $i<count($tds); $i++)
		{
			$td_childs =& $tds[$i]->child_nodes();
			for($j=0; $j<count($td_childs); $j++)
			{
				$tds[$i]->remove_child($td_childs[$j]);
			}
		}
	}

	/**
	* delete content of a cell (not the cell itself)
	*
	* @access private
	*/
	function deleteTDContent(&$a_td_node)
	{
		$td_childs =& $a_td_node->child_nodes();
		for($j=0; $j<count($td_childs); $j++)
		{
			$a_td_node->remove_child($td_childs[$j]);
		}
	}


	/**
	* delete row of cell
	*/
	function deleteRow()
	{
		$td =& $this->getNode();
		$parent_tr =& $td->parent_node();
		$parent_tr->unlink($parent_tr);
	}


	/**
	* insert new column after cell
	*/
	function newColAfter()
	{
		$td =& $this->getNode();

		// determine current column nr
		$hier_id = $this->getHierId();
		$parts = explode("_", $hier_id);
		$col_nr = array_pop($parts);
		$col_nr--;

		$parent_tr =& $td->parent_node();
		$parent_table =& $parent_tr->parent_node();

		// iterate all table rows
		$rows =& $parent_table->child_nodes();
		for($i=0; $i<count($rows); $i++)
		{
			if($rows[$i]->node_name() == "TableRow")
			{
				// clone td at $col_nr
				$tds =& $rows[$i]->child_nodes();
				$new_td =& $tds[$col_nr]->clone_node(true);

				// insert clone after $col_nr
				if ($next_td =& $tds[$col_nr]->next_sibling())
				{
					$new_td =& $next_td->insert_before($new_td, $next_td);
				}
				else
				{
					$new_td =& $rows[$i]->append_child($new_td);
				}
				$this->deleteTDContent($new_td);
			}
		}
	}

	/**
	* insert new column before cell
	*/
	function newColBefore()
	{
		$td =& $this->getNode();

		// determine current column nr
		$hier_id = $this->getHierId();
		$parts = explode("_", $hier_id);
		$col_nr = array_pop($parts);
		$col_nr--;

		$parent_tr =& $td->parent_node();
		$parent_table =& $parent_tr->parent_node();

		// iterate all table rows
		$rows =& $parent_table->child_nodes();
		for($i=0; $i<count($rows); $i++)
		{
			if($rows[$i]->node_name() == "TableRow")
			{
				// clone td at $col_nr
				$tds =& $rows[$i]->child_nodes();
				$new_td =& $tds[$col_nr]->clone_node(true);

				// insert clone before $col_nr
				$new_td =& $tds[$col_nr]->insert_before($new_td, $tds[$col_nr]);
				$this->deleteTDContent($new_td);
			}
		}
	}

	/**
	* delete column of cell
	*/
	function deleteCol()
	{
		$td =& $this->getNode();

		// determine current column nr
		$hier_id = $this->getHierId();
		$parts = explode("_", $hier_id);
		$col_nr = array_pop($parts);
		$col_nr--;

		$parent_tr =& $td->parent_node();
		$parent_table =& $parent_tr->parent_node();

		// iterate all table rows
		$rows =& $parent_table->child_nodes();
		for($i=0; $i<count($rows); $i++)
		{
			if($rows[$i]->node_name() == "TableRow")
			{
				// unlink td at $col_nr
				$tds =& $rows[$i]->child_nodes();
				$tds[$col_nr]->unlink($tds[$col_nr]);
			}
		}
	}


}
?>
