<?php
 /*
   +----------------------------------------------------------------------------+
   | ILIAS open source                                                          |
   +----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne           |
   |                                                                            |
   | This program is free software; you can redistribute it and/or              |
   | modify it under the terms of the GNU General Public License                |
   | as published by the Free Software Foundation; either version 2             |
   | of the License, or (at your option) any later version.                     |
   |                                                                            |
   | This program is distributed in the hope that it will be useful,            |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of             |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              |
   | GNU General Public License for more details.                               |
   |                                                                            |
   | You should have received a copy of the GNU General Public License          |
   | along with this program; if not, write to the Free Software                |
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. | 
   +----------------------------------------------------------------------------+
*/

/**
* Class for importing XML documents into a relational database
*  
* @author	Sascha Hofmann <shofmann@databay.de>
* @version	$Id$
*/

class XML2SQL
{
	/**
	* unique object id
	* 
	* @var integer obj_id
	* @access public 
	*/
	var $obj_id;

	/**
	* mapping db_id to internal dom_id
	* 
	* @var array mapping
	* @access public 
	*/
	var $mapping;
	var $version;
	var $encoding;
	var $charset;
	/**
	* db object
	* 
	* @var object db
	* @access public 
	*/
	var $db;

	/**
	* The DOM XML representation
	* 
	* @var object $xmltree
	* @access public 
	*/
	var $xmltree;	

	/**
	* constructor
	* init db-handler
	* 
	* @access public 
	*/	
	function XML2SQL ($a_dbconnection, $a_xmltree, $a_version = "1.0", $a_encoding = "UTF-8", $a_charset = "UTF-8")
	{
		$this->xmltree = $a_xmltree;
		$this->db = $a_dbconnection;
		$this->version = $a_version;
		$this->encoding = $a_encoding;
		$this->charset = $a_charset;
	}
		
	function insertDocument ()
	{ 
		$this->obj_id = $this->insertXMLObject();
		if (!$this->obj_id) 
		{
			print "There was an error writing the xml file to the database!";
			exit();
		}
		// insert basic structure of document
		foreach ($this->xmltree as $id => $node) {
			$node_id = $this->insertNode($node);
			$this->mapping[$id] = $node_id;
		} 
		// re-map node_ids
		foreach ($this->xmltree as $id => $node) {
			$this->xmltree[$id]["parent"] = $this->mapping[$node["parent"]];
			$this->xmltree[$id]["prev"] = $this->mapping[$node["prev"]];
			$this->xmltree[$id]["next"] = $this->mapping[$node["next"]];
			$this->xmltree[$id]["first"] = $this->mapping[$node["first"]];
			$this->xmltree[$id]["node"] = $this->mapping[$id];
		} 

		foreach ($this->xmltree as $id => $node) {
			$this->updateNode($node);
			$this->insertNodeData($node);
		} 

		return $this->xmltree;
	} 
	
	function insertXMLObject() {
		$q = sprintf("INSERT INTO xml_object (ID, version, encoding, charset, TIMESTAMP) VALUES (NULL, %s, %s, %s, NULL)",
			$this->db->quote($this->version),
			$this->db->quote($this->encoding),
			$this->db->quote($this->charset)
		);
		$result = $this->db->query($q);
		return $this->getLastInsertId();
	}

	function insertNode ($a_node)
	{
		$q = "INSERT INTO xml_tree ".
			 "(xml_id,lft,rgt,node_type_id,depth,struct) ".
			 "VALUES ".
			 "('".$this->obj_id."','".$a_node["left"].
			 "','".$a_node["right"]."','".$a_node["type"].
			 "','".$a_node["depth"]."','".$a_node["struct"]."') ";
		$this->db->query($q);

		return $this->getLastInsertId();
	} 

	function updateNode ($a_node)
	{
		$q = "UPDATE xml_tree SET ".
			 "parent_node_id = '".$a_node["parent"]."',".
			 "prev_sibling_node_id = '".$a_node["prev"]."',".
			 "next_sibling_node_id = '".$a_node["next"]."',".
			 "first_child_node_id = '".$a_node["first"]."' ".
			 "WHERE node_id = '".$a_node["node"]."' ".
			 "AND xml_id = '".$this->obj_id."'";
		$this->db->query($q);
	} 

	function insertNodeData ($a_node)
	{
		//echo "<PRE>";echo var_dump($a_node);echo "</PRE>";
		$a_node = $this->prepareData($a_node);
		
		//echo "<PRE>";echo var_dump($a_node);echo "</PRE>";

		switch ($a_node["type"]) {
			case 1:
				$this->insertElement($a_node);
				$this->insertAttributes($a_node);
				break;

			case 3:
				$this->insertText($a_node);
				break;

			case 4: 
				// $this->insertCData($a_node);
				break;

			case 5: 
				// $this->insertEntityRef($a_node);
				break;

			case 6: 
				// $this->insertEntity($a_node);
				break;

			case 7: 
				// $this->insertPI($a_node);
				break;

			case 8:
				$this->insertComment($a_node);
				break;

			default: 
				// nix
				break;
		} // switch
	} 

	/**
	* insertElement
	* @access	private
	* @param	array	node data
	*/
	function insertElement ($a_node)
	{
		$element_id = $this->getEntryId("xml_element_name","element","element_id",$a_node["name"]);
		
		// insert element first if it does not exist
		if ($element_id == false)
		{
			$q = "INSERT INTO xml_element_name (element) ".
				 "VALUES ('".$a_node["name"]."')";
			$this->db->query($q);
			
			$element_id = $this->getLastInsertId();
		}
		
		// create reference entry
		$q = "INSERT INTO xml_element_idx (node_id,element_id) ".
			 "VALUES ('".$a_node["node"]."','".$element_id."')";
		$this->db->query($q);
	} 

	/**
	* insertText
	* @access	private
	* @param	array	node data
	*/
	function insertText ($a_node)
	{
		// klappt nicht, weil die spaces maskiert sind :-(
		$content = $a_node["content"];
	
		$q = "INSERT INTO xml_text ".
			 "(node_id,textnode) ".
			 "VALUES ".
			 "('".$a_node["node"]."','".$content."')";
		$this->db->query($q);
	} 

	/**
	* insertComment
	* @access	private
	* @param	array	node data
	*/
	function insertComment ($a_node)
	{
		$q = "INSERT INTO xml_comment ".
			 "(node_id,comment) ".
			 "VALUES ".
			 "('".$a_node["node"]."','".$a_node["content"]."')";
		$this->db->query($q);
	} 

	/**
	* insertAttributes
	* @access	private
	* @param	array	node data
	* @return	boolean
	*/
	function insertAttributes ($a_node)
	{
		if (is_array($a_node["attr_list"]))
		{
			foreach ($a_node["attr_list"] as $attr => $value)
			{
				$attribute_id = $this->getEntryId("xml_attribute_name","attribute","attribute_id",$attr);

				// insert attribute first if it does not exist
				if ($attribute_id == false)
				{
					$q = "INSERT INTO xml_attribute_name (attribute) ".
						 "VALUES ('".$attr."')";
					$this->db->query($q);
			
					$attribute_id = $this->getLastInsertId();
				}

				//$value_id = $this->getEntryId("xml_attribute_value","value","value_id",$value);

				// insert attribute value first if it does not exist
				//if ($value_id == false)
				//{
					$q = "INSERT INTO xml_attribute_value (value) ".
						 "VALUES ('".$value."')";
					$this->db->query($q);
			
					$value_id = $this->getLastInsertId();
				//}

				// create reference entry
				$q = "INSERT INTO xml_attribute_idx (node_id,attribute_id,value_id) ".
					 "VALUES ".
					 "('".$a_node["node"]."','".$attribute_id."','".$value_id."')";
				$this->db->query($q);
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	* getEntryId
	* checks if a single value exists in database
	* @access	private
	* @param	string	db table name
	* @param	string	table column
	* @param	string	value you seek
	* @return	boolean	true when value exists
	*/
	function getEntryId ($a_table,$a_column,$a_return_value,$a_value)
	{
		$q = "SELECT DISTINCT ".$a_return_value." FROM ".$a_table." ".
			 "WHERE ".$a_column."='".$a_value."'";
		$res = $this->db->query($q, DB_FETCHMODE_ASSOC);
		if ($res->numRows() == 0)
		{
			return false;
		}

		$row = $res->fetchRow();
		return $row[0];
	}

	/**
	* getLastInsertId
	* @access	private
	* @return	integer
	*/
	function getLastInsertId ()
	{
		$q = "SELECT LAST_INSERT_ID()";
		$res = $this->db->query($q);
		$row = $res->fetchRow();
		return $row[0];
	}	

	/**
	* prepare db insertion with addslashes()
	* @access	private
	* @param	array
	* @return	arrayr
	*/
	function prepareData ($a_data)
	{
		foreach ($a_data as $key => $value)
		{
			if (is_string($value))
				$data[$key] = addslashes($value);
			else
				$data[$key] = $value;			
		}
		
		return $data;
	}

	// information saved in $mapping how the LOs are connected in the this Module is
	// written to tree
	function insertStructureIntoTree($a_nodes,$a_id)
	{
		// init tree
		$lm_tree = new Tree($a_id,$a_id);
		
		//prepare array and kick all nodes with no children
		foreach ($a_nodes as $key => $nodes)
		{
			if (!is_array($nodes[key($nodes)]))
			{
				array_splice($a_nodes,$key);
				break;
			}
		}

		// insert first_node
		$parent_id = $a_id;
		$lm_tree->insertNode(key($a_nodes[0]),$parent_id,0);
		
		// traverse array to build tree structure by inserting nodes to db-table tree
		foreach ($a_nodes as $key => $nodes)
		{
			$parent_parent_id = $parent_id;
			$parent_id = key($nodes);

			foreach (array_reverse($nodes[$parent_id]) as $child_id)
			{
				$lm_tree->insertNode($child_id,$parent_id,$parent_parent_id);
			}
		}
	}
} // END class xml2sql
?>
