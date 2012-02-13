<?php

/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Taxonomy node
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 * @ingroup ServicesTaxonomy
 */
class ilTaxonomyNode
{
	var $type;
	var $id;
	var $title;

	/**
	 * Constructor
	 * @access	public
	 */
	function __construct($a_id = 0)
	{
		$this->id = $a_id;
		
		include_once("./Services/Taxonomy/classes/class.ilTaxonomyTree.php");
		$this->taxonomy_tree = new ilTaxonomyTree();

		if($a_id != 0)
		{
			$this->read();
		}

		$this->setType("taxn");
	}

		/**
	 * Set title
	 *
	 * @param	string		$a_title	title
	 */
	function setTitle($a_title)
	{
		$this->title = $a_title;
	}

	/**
	 * Get title
	 *
	 * @return	string		title
	 */
	function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set type
	 *
	 * @param	string		Type
	 */
	function setType($a_type)
	{
		$this->type = $a_type;
	}

	/**
	 * Get type
	 *
	 * @return	string		Type
	 */
	function getType()
	{
		return $this->type;
	}

	/**
	 * Set Node ID
	 *
	 * @param	int		Node ID
	 */
	function setId($a_id)
	{
		$this->id = $a_id;
	}

	/**
	 * Get Node ID
	 *
	 * @param	int		Node ID
	 */
	function getId()
	{
		return $this->id;
	}

	/**
	 * Set order nr
	 *
	 * @param int $a_val order nr	
	 */
	function setOrderNr($a_val)
	{
		$this->order_nr = $a_val;
	}
	
	/**
	 * Get order nr
	 *
	 * @return int order nr
	 */
	function getOrderNr()
	{
		return $this->order_nr;
	}

	/**
	 * Read data from database
	 */
	function read()
	{
		global $ilDB;

		if(!isset($this->data_record))
		{
			$query = "SELECT * FROM tax_node WHERE obj_id = ".
				$ilDB->quote($this->id, "integer");
			$obj_set = $ilDB->query($query);
			$this->data_record = $ilDB->fetchAssoc($obj_set);
		}
		$this->setType($this->data_record["type"]);
		$this->setTitle($this->data_record["title"]);
		$this->setOrderNr($this->data_record["order_nr"]);
	}

	/**
	 * Create taxonomy node
	 */
	function create()
	{
		global $ilDB;

		// insert object data
		$id = $ilDB->nextId("tax_node");
		$query = "INSERT INTO tax_node (obj_id, title, type, create_date, order_nr) ".
			"VALUES (".
			$ilDB->quote($id, "integer").",".
			$ilDB->quote($this->getTitle(), "text").",".
			$ilDB->quote($this->getType(), "text").", ".
			$ilDB->now().", ".
			$ilDB->quote((int) $this->getOrderNr(), "integer").
			")";
		$ilDB->manipulate($query);
		$this->setId($id);

	}

	/**
	 * Update Node
	 */
	function update()
	{
		global $ilDB;

		$query = "UPDATE tax_node SET ".
			" title = ".$ilDB->quote($this->getTitle(), "text").
			" ,order_nr = ".$ilDB->quote((int) $this->getOrderNr(), "integer").
			" WHERE obj_id = ".$ilDB->quote($this->getId(), "integer");

		$ilDB->manipulate($query);
	}

	/**
	 * Delete taxonomy node
	 */
	function delete()
	{
		global $ilDB;
		
		$query = "DELETE FROM tax_node WHERE obj_id= ".
			$ilDB->quote($this->getId(), "integer");
		$ilDB->manipulate($query);
	}

	/**
	 * Copy taxonomy node
	 */
	function copy()
	{
		$taxn = new ilTaxonomyNode();
		$taxn->setTitle($this->getTitle());
		$taxn->setType($this->getType());
		$taxn->setOrderNr($this->getOrderNr());
		$taxn->create();

		return $taxn;
	}

	/**
	 * Lookup
	 *
	 * @param	int			Node ID
	 */
	protected static function _lookup($a_obj_id, $a_field)
	{
		global $ilDB;

		$query = "SELECT $a_field FROM tax_node WHERE obj_id = ".
			$ilDB->quote($a_obj_id, "integer");
		$obj_set = $ilDB->query($query);
		$obj_rec = $ilDB->fetchAssoc($obj_set);

		return $obj_rec[$a_field];
	}

	/**
	 * Lookup Title
	 *
	 * @param	int			node ID
	 * @return	string		title
	 */
	static function _lookupTitle($a_obj_id)
	{
		global $ilDB;

		return self::_lookup($a_obj_id, "title");
	}

	/**
	 * Put this node into the taxonomy tree
	 */
	static function putInTree($a_tree_id, $a_node, $a_parent_id = "", $a_target_node_id = "")
	{
		include_once("./Services//classes/class.il.php");
		$tax_tree = new ilTaxonomyTree($a_tree_id);

		// determine parent
		$parent_id = ($a_parent_id != "")
			? $a_parent_id
			: $tax_tree->getRootId();

		// determine target
		if ($a_target_node_id != "")
		{
			$target = $a_target_node_id;
		}
		else
		{
			// determine last child that serves as predecessor
			$childs = $tax_tree->getChilds($parent_id);

			if (count($childs) == 0)
			{
				$target = IL_FIRST_NODE;
			}
			else
			{
				$target = $childs[count($childs) - 1]["obj_id"];
			}
		}

		if ($tax_tree->isInTree($parent_id) && !$taxtree->isInTree($a_node->getId()))
		{
			$tax_tree->insertNode($a_node->getId(), $parent_id, $target);
		}
	}

}
?>
