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

require_once("content/classes/Pages/class.ilPageContent.php");

/**
* Class ilPCFileItem
*
* File Item content object (see ILIAS DTD)
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilPCFileItem extends ilPageContent
{
	var $dom;

	/**
	* Constructor
	* @access	public
	*/
	function ilPCFileItem(&$a_dom)
	{
		parent::ilPageContent();
		$this->setType("flit");

		$this->dom =& $a_dom;
	}


	/**
	* insert new list item after current one
	*/
	function newItemAfter($a_id, $a_location, $a_format)
	{
		$li =& $this->getNode();
		$new_item =& $this->dom->create_element("FileItem");
		if ($next_li =& $li->next_sibling())
		{
			$new_item =& $next_li->insert_before($new_item, $next_li);
		}
		else
		{
			$parent_list =& $li->parent_node();
			$new_item =& $parent_list->append_child($new_item);
		}

		// Identifier
		$id_node =& $this->dom->create_element("Identifier");
		$id_node =& $new_item->append_child($id_node);
		$id_node->set_attribute("Catalog", "ILIAS");
		$id_node->set_attribute("Entry", "il__file_".$a_id);

		// Location
		$loc_node =& $this->dom->create_element("Location");
		$loc_node =& $new_item->append_child($loc_node);
		$loc_node->set_attribute("Type", "LocalFile");
		$loc_node->set_content($a_location);

		// Format
		$form_node =& $this->dom->create_element("Format");
		$form_node =& $new_item->append_child($form_node);
		$form_node->set_content($a_format);
	}


	/**
	* insert new list item before current one
	*/
	function newItemBefore($a_id, $a_location, $a_format)
	{
		$li =& $this->getNode();
		$new_item =& $this->dom->create_element("FileItem");
		$new_item =& $li->insert_before($new_item, $li);

		// Identifier
		$id_node =& $this->dom->create_element("Identifier");
		$id_node =& $new_item->append_child($id_node);
		$id_node->set_attribute("Catalog", "ILIAS");
		$id_node->set_attribute("Entry", "il__file_".$a_id);

		// Location
		$loc_node =& $this->dom->create_element("Location");
		$loc_node =& $new_item->append_child($loc_node);
		$loc_node->set_attribute("Type", "LocalFile");
		$loc_node->set_content($a_location);

		// Format
		$form_node =& $this->dom->create_element("Format");
		$form_node =& $new_item->append_child($form_node);
		$form_node->set_content($a_format);
	}


	/**
	* delete row of cell
	*/
	function deleteItem()
	{
		$li =& $this->getNode();
		$li->unlink($li);
	}

}
?>
