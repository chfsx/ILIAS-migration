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
* Class ilLMList
*
* List content object (see ILIAS DTD)
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilLMList extends ilPageContent
{
	var $dom;
	var $list_node;


	/**
	* Constructor
	* @access	public
	*/
	function ilLMList(&$a_dom)
	{
		parent::ilPageContent();
		$this->setType("list");

		$this->dom =& $a_dom;
	}

	function setNode(&$a_node)
	{
		parent::setNode($a_node);		// this is the PageContent node
		$this->list_node =& $a_node->first_child();		// this is the Table node
	}

	function create(&$a_pg_obj, $a_hier_id)
	{
		$this->node =& $this->dom->create_element("PageContent");
		$a_pg_obj->insertContent($this, $a_hier_id, IL_INSERT_AFTER);
		$this->list_node =& $this->dom->create_element("List");
		$this->list_node =& $this->node->append_child($this->list_node);
	}

	function addItems($a_nr)
	{
		for ($i=1; $i<=$a_nr; $i++)
		{
			$new_item =& $this->dom->create_element("Item");
			$new_item =& $this->list_node->append_child($new_item);
		}
	}

	function setOrderType($a_type = "Unordered")
	{
		switch ($a_type)
		{
			case "Unordered":
				$this->list_node->set_attribute("Type", "Unordered");
				if($this->list_node->has_attribute("NumberingType")
				{
					$this->list_node->remove_attribute("NumberingType");
				}
				break;

			case "Number":
				$this->list_node->set_attribute("Type", "Ordered");
				$this->list_node->set_attribute("NumberingType", "Number");
				break;

			case "Roman":
				$this->list_node->set_attribute("Type", "Ordered");
				$this->list_node->set_attribute("NumberingType", "Roman");
				break;

			case "roman":
				$this->list_node->set_attribute("Type", "Ordered");
				$this->list_node->set_attribute("NumberingType", "roman");
				break;

			case "Alphabetic":
				$this->list_node->set_attribute("Type", "Ordered");
				$this->list_node->set_attribute("NumberingType", "Alphabetic");
				break;

			case "alphabetic":
				$this->list_node->set_attribute("Type", "Ordered");
				$this->list_node->set_attribute("NumberingType", "alphabetic");
				break;
		}
	}

}
?>
