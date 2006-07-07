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

define("IL_LAST_NODE", -2);
define("IL_FIRST_NODE", -1);

/**
* Tree class
* data representation in hierachical trees using the Nested Set Model with Gaps 
* by Joe Celco
*
* @author Sascha Hofmann <saschahofmann@gmx.de>
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
*
* @package ilias-core
*/
class ilTree
{
	/**
	* ilias object
	* @var		object	ilias
	* @access	private
	*/
	var $ilias;


	/**
	* Logger object
	* @var		object	ilias
	* @access	private
	*/
	var $log;

	/**
	* points to root node (may be a subtree)
	* @var		integer
	* @access	public
	*/
	var $root_id;

	/**
	* to use different trees in one db-table
	* @var		integer
	* @access	public
	*/
	var $tree_id;

	/**
	* table name of tree table
	* @var		string
	* @access	private
	*/
	var $table_tree;

	/**
	* table name of object_data table
	* @var		string
	* @access	private
	*/
	var $table_obj_data;

	/**
	* table name of object_reference table
	* @var		string
	* @access	private
	*/
	var $table_obj_reference;

	/**
	* column name containing primary key in reference table
	* @var		string
	* @access	private
	*/
	var $ref_pk;

	/**
	* column name containing primary key in object table
	* @var		string
	* @access	private
	*/
	var $obj_pk;

	/**
	* column name containing tree id in tree table
	* @var		string
	* @access	private
	*/
	var $tree_pk;

	/**
	* Size of the gaps to be created in the nested sets sequence numbering of the
    * tree nodes. 
	* Having gaps in the tree greatly improves performance on all operations
	* that add or remove tree nodes.
	*
	* Setting this to zero will leave no gaps in the tree.
	* Setting this to a value larger than zero will create gaps in the tree.
	* Each gap leaves room in the sequence numbering for the specified number of
    * nodes.
    * (The gap is expressed as the number of nodes. Since each node consumes 
    * two sequence numbers, specifying a gap of 1 will leave space for 2 
    * sequence numbers.)
	*
	* A gap is created, when a new child is added to a node, and when not
	* enough room between node.rgt and the child with the highest node.rgt value 
	* of the node is available.
	* A gap is closed, when a node is removed and when (node.rgt - node.lft) 
	* is bigger than gap * 2.
	*
	*
	* @var		integer
	* @access	private
	*/
	var $gap;

	/**
	* Constructor
	* @access	public
	* @param	integer	$a_tree_id		tree_id
	* @param	integer	$a_root_id		root_id (optional)
	*/
	function ilTree($a_tree_id, $a_root_id = 0)
	{
		global $ilDB,$ilErr,$ilias,$ilLog;

		// set db & error handler
		(isset($ilDB)) ? $this->ilDB =& $ilDB : $this->ilDB =& $ilias->db;

		if (!isset($ilErr))
		{
			$ilErr = new ilErrorHandling();
			$ilErr->setErrorHandling(PEAR_ERROR_CALLBACK,array($ilErr,'errorHandler'));
		}
		else
		{
			$this->ilErr =& $ilErr;
		}

		$this->lang_code = "en";
		
		if (!isset($a_tree_id) or (func_num_args() == 0) )
		{
			$this->ilErr->raiseError(get_class($this)."::Constructor(): No tree_id given!",$this->ilErr->WARNING);
		}

		if (func_num_args() > 2)
		{
			$this->ilErr->raiseError(get_class($this)."::Constructor(): Wrong parameter count!",$this->ilErr->WARNING);
		}

		// CREATE LOGGER INSTANCE
		$this->log =& $ilLog;

		//init variables
		if (empty($a_root_id))
		{
			$a_root_id = ROOT_FOLDER_ID;
		}

		$this->tree_id		  = $a_tree_id;
		$this->root_id		  = $a_root_id;
		$this->table_tree     = 'tree';
		$this->table_obj_data = 'object_data';
		$this->table_obj_reference = 'object_reference';
		$this->ref_pk = 'ref_id';
		$this->obj_pk = 'obj_id';
		$this->tree_pk = 'tree';

		// By default, we create gaps in the tree sequence numbering for 50 nodes 
		$this->gap = 50;
	}
	
	// store user language
	function initLangCode()
	{
		global $ilUser;
		
		// lang_code is only required in $this->fetchnodedata
		if (!is_object($ilUser))
		{
			$this->lang_code = "en";
		}
		else
		{
			$this->lang_code = $ilUser->getCurrentLanguage();
		}
	}


	/**
	* set table names
	* The primary key of the table containing your object_data must be 'obj_id'
	* You may use a reference table.
	* If no reference table is specified the given tree table is directly joined
	* with the given object_data table.
	* The primary key in object_data table and its foreign key in reference table must have the same name!
	*
	* @param	string	table name of tree table
	* @param	string	table name of object_data table
	* @param	string	table name of object_reference table (optional)
	* @access	public
	* @return	boolean
	*/
	function setTableNames($a_table_tree,$a_table_obj_data,$a_table_obj_reference = "")
	{
		if (!isset($a_table_tree) or !isset($a_table_obj_data))
		{
			$this->ilErr->raiseError(get_class($this)."::setTableNames(): Missing parameter! ".
								"tree table: ".$a_table_tree." object data table: ".$a_table_obj_data,$this->ilErr->WARNING);
		}

		$this->table_tree = $a_table_tree;
		$this->table_obj_data = $a_table_obj_data;
		$this->table_obj_reference = $a_table_obj_reference;

		return true;
	}

	/**
	* set column containing primary key in reference table
	* @access	public
	* @param	string	column name
	* @return	boolean	true, when successfully set
	*/
	function setReferenceTablePK($a_column_name)
	{
		if (!isset($a_column_name))
		{
			$this->ilErr->raiseError(get_class($this)."::setReferenceTablePK(): No column name given!",$this->ilErr->WARNING);
		}

		$this->ref_pk = $a_column_name;
		return true;
	}

	/**
	* set column containing primary key in object table
	* @access	public
	* @param	string	column name
	* @return	boolean	true, when successfully set
	*/
	function setObjectTablePK($a_column_name)
	{
		if (!isset($a_column_name))
		{
			$this->ilErr->raiseError(get_class($this)."::setObjectTablePK(): No column name given!",$this->ilErr->WARNING);
		}

		$this->obj_pk = $a_column_name;
		return true;
	}

	/**
	* set column containing primary key in tree table
	* @access	public
	* @param	string	column name
	* @return	boolean	true, when successfully set
	*/
	function setTreeTablePK($a_column_name)
	{
		if (!isset($a_column_name))
		{
			$this->ilErr->raiseError(get_class($this)."::setTreeTablePK(): No column name given!",$this->ilErr->WARNING);
		}

		$this->tree_pk = $a_column_name;
		return true;
	}

	/**
	* build join depending on table settings
	* @access	private
	* @return	string
	*/
	function buildJoin()
	{
		if ($this->table_obj_reference)
		{
			return "LEFT JOIN ".$this->table_obj_reference." ON ".$this->table_tree.".child=".$this->table_obj_reference.".".$this->ref_pk." ".
				   "LEFT JOIN ".$this->table_obj_data." ON ".$this->table_obj_reference.".".$this->obj_pk."=".$this->table_obj_data.".".$this->obj_pk." ";
		}
		else
		{
			return "LEFT JOIN ".$this->table_obj_data." ON ".$this->table_tree.".child=".$this->table_obj_data.".".$this->obj_pk." ";
		}
	}

	/**
	* get child nodes of given node
	* @access	public
	* @param	integer		node_id
	* @param	string		sort order of returned childs, optional (possible values: 'title','desc','last_update' or 'type')
	* @param	string		sort direction, optional (possible values: 'DESC' or 'ASC'; defalut is 'ASC')
	* @return	array		with node data of all childs or empty array
	*/
	function getChilds($a_node_id, $a_order = "", $a_direction = "ASC")
	{
		global $ilBench;
		
		if (!isset($a_node_id))
		{
			$message = get_class($this)."::getChilds(): No node_id given!";
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		// init childs
		$childs = array();

		// number of childs
		$count = 0;

		// init order_clause
		$order_clause = "";

		// set order_clause if sort order parameter is given
		if (!empty($a_order))
		{
			$order_clause = "ORDER BY ".$a_order." ".$a_direction;
		}
		else
		{
			$order_clause = "ORDER BY ".$this->table_tree.".lft";
		}

	//666
		$q = "SELECT * FROM ".$this->table_tree." ".
			 $this->buildJoin().
			 "WHERE parent = '".$a_node_id."' ".
			 "AND ".$this->table_tree.".".$this->tree_pk." = '".$this->tree_id."' ".
			 $order_clause;

		//$ilBench->start("Tree", "getChilds_Query");
		$r = $this->ilDB->query($q);
		//$ilBench->stop("Tree", "getChilds_Query");

		$count = $r->numRows();


		if ($count > 0)
		{
			//$ilBench->start("Tree", "getChilds_fetchNodeData");
			while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
			{
				$childs[] = $this->fetchNodeData($row);
			}
			//$ilBench->stop("Tree", "getChilds_fetchNodeData");

			// mark the last child node (important for display)
			$childs[$count - 1]["last"] = true;
			return $childs;
		}
		else
		{
			return $childs;
		}
	}

	/**
	* get child nodes of given node (exclude filtered obj_types)
	* @access	public
	* @param	array		objects to filter (e.g array('rolf'))
	* @param	integer		node_id
	* @param	string		sort order of returned childs, optional (possible values: 'title','desc','last_update' or 'type')
	* @param	string		sort direction, optional (possible values: 'DESC' or 'ASC'; defalut is 'ASC')
	* @return	array		with node data of all childs or empty array
	*/
	function getFilteredChilds($a_filter,$a_node,$a_order = "",$a_direction = "ASC")
	{
		$childs = $this->getChilds($a_node,$a_order,$a_direction);

		foreach($childs as $child)
		{
			if(!in_array($child["type"],$a_filter))
			{
				$filtered[] = $child;
			}
		}
		return $filtered ? $filtered : array();
	}


	/**
	* get child nodes of given node by object type
	* @access	public
	* @param	integer		node_id
	* @param	string		object type
	* @return	array		with node data of all childs or empty array
	*/
	function getChildsByType($a_node_id,$a_type)
	{
		if (!isset($a_node_id) or !isset($a_type))
		{
			$message = get_class($this)."::getChildsByType(): Missing parameter! node_id:".$a_node_id." type:".$a_type;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		// init childs
		$childs = array();

		$q = "SELECT * FROM ".$this->table_tree." ".
			 $this->buildJoin().
			 "WHERE parent = '".$a_node_id."' ".
			 "AND ".$this->table_tree.".".$this->tree_pk." = '".$this->tree_id."' ".
			 "AND ".$this->table_obj_data.".type='".$a_type."' ".
			 "ORDER BY ".$this->table_tree.".lft";
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$childs[] = $this->fetchNodeData($row);
		}


		return $childs;
	}


	/**
	* insert new node with node_id under parent node with parent_id
	* @access	public
	* @param	integer		node_id
	* @param	integer		parent_id
	* @param	integer		IL_LAST_NODE | IL_FIRST_NODE | node id of preceding child
	*/
	function insertNode($a_node_id, $a_parent_id, $a_pos = IL_LAST_NODE, $a_reset_deletion_date = false)
	{
//echo "+$a_node_id+$a_parent_id+";
		// CHECK node_id and parent_id > 0 if in main tree
		if($this->__isMainTree())
		{
			if($a_node_id <= 1 or $a_parent_id <= 0)
			{
				$message = sprintf('%s::insertNode(): Invalid parameters! $a_node_id: %s $a_parent_id: %s',
								   get_class($this),
								   $a_node_id,
								   $a_parent_id);
				$this->log->write($message,$this->log->FATAL);
				$this->ilErr->raiseError($message,$this->ilErr->WARNING);
			}
		}


		if (!isset($a_node_id) or !isset($a_parent_id))
		{
			$this->ilErr->raiseError(get_class($this)."::insertNode(): Missing parameter! ".
				"node_id: ".$a_node_id." parent_id: ".$a_parent_id,$this->ilErr->WARNING);
		}
		if ($this->isInTree($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::insertNode(): Node ".$a_node_id." already in tree ".
									 $this->table_tree."!",$this->ilErr->WARNING);
		}

		//
		switch ($a_pos)
		{
			case IL_FIRST_NODE:

				if($this->__isMainTree())
				{
					ilDBx::_lockTables(array('tree' => 'WRITE'));
				}

				// get left value of parent
				$q = "SELECT * FROM ".$this->table_tree." ".
					"WHERE child = '".$a_parent_id."' ".
					"AND ".$this->tree_pk." = '".$this->tree_id."'";
				$res = $this->ilDB->query($q);
				$r = $res->fetchRow(DB_FETCHMODE_OBJECT);

				if ($r->parent == NULL)
				{
					if($this->__isMainTree())
					{
						ilDBx::_unlockTables();
					}
					$this->ilErr->raiseError(get_class($this)."::insertNode(): Parent with ID ".$a_parent_id." not found in ".
											 $this->table_tree."!",$this->ilErr->WARNING);
				}

				$left = $r->lft;
				$lft = $left + 1;
				$rgt = $left + 2;

				// spread tree
				$q = "UPDATE ".$this->table_tree." SET ".
					"lft = CASE ".
					"WHEN lft > ".$left." ".
					"THEN lft + 2 ".
					"ELSE lft ".
					"END, ".
					"rgt = CASE ".
					"WHEN rgt > ".$left." ".
					"THEN rgt + 2 ".
					"ELSE rgt ".
					"END ".
					"WHERE ".$this->tree_pk." = '".$this->tree_id."'";
				$this->ilDB->query($q);

				if($this->__isMainTree())
				{
					ilDBx::_unlockTables();
				}

				break;

			case IL_LAST_NODE:
				// Special treatment for trees with gaps
				if ($this->gap > 0)
				{
					if($this->__isMainTree())
					{
						ilDBx::_lockTables(array('tree' => 'WRITE'));
					}

					// get lft and rgt value of parent
					$q = 'SELECT rgt,lft,parent FROM '.$this->table_tree.' '.
						'WHERE child = '.$a_parent_id.' '.
						'AND '.$this->tree_pk.' = '.$this->tree_id;
					$res = $this->ilDB->query($q);
					$r = $res->fetchRow(DB_FETCHMODE_ASSOC);

									
					if ($r['parent'] == NULL)
					{
						if($this->__isMainTree())
						{
							ilDBx::_unlockTables();
						}
						$this->ilErr->raiseError(get_class($this)."::insertNode(): Parent with ID ".
												$a_parent_id." not found in ".$this->table_tree."!",$this->ilErr->WARNING);
					}
					$parentRgt = $r['rgt'];
					$parentLft = $r['lft'];
					
					// Get the available space, without taking children into account yet
					$availableSpace = $parentRgt - $parentLft;
					if ($availableSpace < 2)
					{
						// If there is not enough space between parent lft and rgt, we don't need
						// to look any further, because we must spread the tree.
						$lft = $parentRgt;
					}
					else
					{
						// If there is space between parent lft and rgt, we need to check
						// whether there is space left between the rightmost child of the
						// parent and parent rgt.
						$q = 'SELECT MAX(rgt) AS max_rgt FROM '.$this->table_tree.' '.
							'WHERE parent = '.$a_parent_id.' '.
							'AND '.$this->tree_pk.' = '.$this->tree_id;
						$res = $this->ilDB->query($q);
						$r = $res->fetchRow(DB_FETCHMODE_ASSOC);
						if (isset($r['max_rgt']))
						{
							// If the parent has children, we compute the available space
							// between rgt of the rightmost child and parent rgt.
							$availableSpace = $parentRgt - $r['max_rgt'];
							$lft = $r['max_rgt'] + 1;
						}
						else
						{
							// If the parent has no children, we know now, that we can
							// add the new node at parent lft + 1 without having to spread
							// the tree.
							$lft = $parentLft + 1;
						}
					}
					$rgt = $lft + 1;
					

					// spread tree if there is not enough space to insert the new node
					if ($availableSpace < 2)
					{
						$this->log->write('ilTree.insertNode('.$a_node_id.','.$a_parent_id.') creating gap at '.$a_parent_id.' '.$parentLft.'..'.$parentRgt.'+'.(2 + $this->gap * 2));
						$q = "UPDATE ".$this->table_tree." SET ".
							"lft = CASE ".
							"WHEN lft > ".$parentRgt." ".
							"THEN lft + ".(2 + $this->gap * 2).' '.
							"ELSE lft ".
							"END, ".
							"rgt = CASE ".
							"WHEN rgt >= ".$parentRgt." ".
							"THEN rgt + ".(2 + $this->gap * 2).' '.
							"ELSE rgt ".
							"END ".
							"WHERE ".$this->tree_pk." = '".$this->tree_id."'";
						$this->ilDB->query($q);
					}
					else
					{
						$this->log->write('ilTree.insertNode('.$a_node_id.','.$a_parent_id.') reusing gap at '.$a_parent_id.' '.$parentLft.'..'.$parentRgt.' for node '.$a_node_id.' '.$lft.'..'.$rgt);
					}				
					if($this->__isMainTree())
					{
						ilDBx::_unlockTables();
					}
				}
				// Treatment for trees without gaps
				else 
				{
					if($this->__isMainTree())
					{
						ilDBx::_lockTables(array('tree' => 'WRITE'));
					}

					// get right value of parent
					$q = "SELECT * FROM ".$this->table_tree." ".
						"WHERE child = '".$a_parent_id."' ".
						"AND ".$this->tree_pk." = '".$this->tree_id."'";
					$res = $this->ilDB->query($q);
					$r = $res->fetchRow(DB_FETCHMODE_OBJECT);

					if ($r->parent == NULL)
					{
						if($this->__isMainTree())
						{
							ilDBx::_unlockTables();
						}
						$this->ilErr->raiseError(get_class($this)."::insertNode(): Parent with ID ".
												 $a_parent_id." not found in ".$this->table_tree."!",$this->ilErr->WARNING);
					}

					$right = $r->rgt;
					$lft = $right;
					$rgt = $right + 1;

					// spread tree
					$q = "UPDATE ".$this->table_tree." SET ".
						"lft = CASE ".
						"WHEN lft > ".$right." ".
						"THEN lft + 2 ".
						"ELSE lft ".
						"END, ".
						"rgt = CASE ".
						"WHEN rgt >= ".$right." ".
						"THEN rgt + 2 ".
						"ELSE rgt ".
						"END ".
						"WHERE ".$this->tree_pk." = '".$this->tree_id."'";
					$this->ilDB->query($q);

					if($this->__isMainTree())
					{
						ilDBx::_unlockTables();
					}
				}

				break;

			default:

				// get right value of preceeding child
				$q = "SELECT * FROM ".$this->table_tree." ".
					"WHERE child = '".$a_pos."' ".
					"AND ".$this->tree_pk." = '".$this->tree_id."'";
				$res = $this->ilDB->query($q);
				$r = $res->fetchRow(DB_FETCHMODE_OBJECT);

				// crosscheck parents of sibling and new node (must be identical)
				if ($r->parent != $a_parent_id)
				{
					if($this->__isMainTree())
					{
						ilDBx::_unlockTables();
					}
					$this->ilErr->raiseError(get_class($this)."::insertNode(): Parents mismatch! ".
						"new node parent: ".$a_parent_id." sibling parent: ".$r->parent,$this->ilErr->WARNING);
				}

				$right = $r->rgt;
				$lft = $right + 1;
				$rgt = $right + 2;

				// update lft/rgt values
				$q = "UPDATE ".$this->table_tree." SET ".
					"lft = CASE ".
					"WHEN lft > ".$right." ".
					"THEN lft + 2 ".
					"ELSE lft ".
					"END, ".
					"rgt = CASE ".
					"WHEN rgt > ".$right." ".
					"THEN rgt + 2 ".
					"ELSE rgt ".
					"END ".
					"WHERE ".$this->tree_pk." = '".$this->tree_id."'";
				$this->ilDB->query($q);
				if($this->__isMainTree())
				{
					ilDBx::_unlockTables();
				}

				break;

		}

		// get depth
		$depth = $this->getDepth($a_parent_id) + 1;

		// insert node
		$this->log->write('ilTree.insertNode('.$a_node_id.','.$a_parent_id.') inserting node:'.$a_node_id.' parent:'.$a_parent_id." ".$lft."..".$rgt." depth:".$depth);
		$q = "INSERT INTO ".$this->table_tree." (".$this->tree_pk.",child,parent,lft,rgt,depth) ".
			 "VALUES ".
			 "('".$this->tree_id."','".$a_node_id."','".$a_parent_id."','".$lft."','".$rgt."','".$depth."')";

		$this->ilDB->query($q);
		
		// reset deletion date
		if ($a_reset_deletion_date)
		{
			ilObject::_resetDeletedDate($a_node_id);
		}
	}

	/**
	* get all nodes in the subtree under specified node
	*
	* @access	public
	* @param	array		node_data
	* @param    boolean     with data: default is true otherwise this function return only a ref_id array
	* @return	array		2-dim (int/array) key, node_data of each subtree node including the specified node
	*/
	function getSubTree($a_node,$a_with_data = true)
	{
		if (!is_array($a_node))
		{
			$this->ilErr->raiseError(get_class($this)."::getSubTree(): Wrong datatype for node_data! ",$this->ilErr->WARNING);
		}

		if($a_node['lft'] < 1 or $a_node['rgt'] < 2)
		{
			$message = sprintf('%s::getSubTree(): Invalid node given! $a_node["lft"]: %s $a_node["rgt"]: %s',
								   get_class($this),
								   $a_node['lft'],
								   $a_node['rgt']);

			$this->log->write($message,$this->log->FATAL);

			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

	    $subtree = array();

		$q = "SELECT * FROM ".$this->table_tree." ".
			 $this->buildJoin().
			 "WHERE ".$this->table_tree.".lft BETWEEN '".$a_node["lft"]."' AND '".$a_node["rgt"]."' ".
			 "AND ".$this->table_tree.".".$this->tree_pk." = '".$this->tree_id."' ".
			 "ORDER BY ".$this->table_tree.".lft";

		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if($a_with_data)
			{
				$subtree[] = $this->fetchNodeData($row);
			}
			else
			{
				$subtree[] = $row['child_id'];
			}
		}

		return $subtree ? $subtree : array();
	}

	/**
	* get types of nodes in the subtree under specified node
	*
	* @access	public
	* @param	array		node_id
	* @param	array		object types to filter e.g array('rolf')
	* @return	array		2-dim (int/array) key, node_data of each subtree node including the specified node
	*/
	function getSubTreeTypes($a_node,$a_filter = 0)
	{
		$a_filter = $a_filter ? $a_filter : array();

		foreach($this->getSubtree($this->getNodeData($a_node)) as $node)
		{
			if(in_array($node["type"],$a_filter))
			{
				continue;
			}
			$types["$node[type]"] = $node["type"];
		}
		return $types ? $types : array();
	}

	/**
	* delete node and the whole subtree under this node
	* @access	public
	* @param	array		node_data of a node
	*/
	function deleteTree($a_node)
	{
		if (!is_array($a_node))
		{
			$this->ilErr->raiseError(get_class($this)."::deleteTree(): Wrong datatype for node_data! ",$this->ilErr->WARNING);
		}
		if($this->__isMainTree() and $a_node[$this->tree_pk] === 1)
		{
			if($a_node['lft'] <= 1 or $a_node['rgt'] <= 2)
			{
				$message = sprintf('%s::deleteTree(): Invalid parameters given: $a_node["lft"]: %s, $a_node["rgt"] %s',
								   get_class($this),
								   $a_node['lft'],
								   $a_node['rgt']);

				$this->log->write($message,$this->log->FATAL);
				$this->ilErr->raiseError($message,$this->ilErr->WARNING);
			}
			else if(!$this->__checkDelete($a_node))
			{
				$message = sprintf('%s::deleteTree(): Check delete failed: $a_node["lft"]: %s, $a_node["rgt"] %s',
								   get_class($this),
								   $a_node['lft'],
								   $a_node['rgt']);
				$this->log->write($message,$this->log->FATAL);
				$this->ilErr->raiseError($message,$this->ilErr->WARNING);
			}

		}
		$diff = $a_node["rgt"] - $a_node["lft"] + 1;


		// LOCKED ###########################################################
		// get lft and rgt values. Don't trust parameter lft/rgt values of $a_node
		if($this->__isMainTree())
		{
			ilDBx::_lockTables(array('tree' => 'WRITE'));
		}

		$query = "SELECT * FROM ".$this->table_tree." ".
			"WHERE child = '".$a_node['child']."' ".
			"AND ".$this->tree_pk." = '".$a_node[$this->tree_pk]."'";

		$res = $this->ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$a_node['lft'] = $row->lft;
			$a_node['rgt'] = $row->rgt;
			$diff = $a_node["rgt"] - $a_node["lft"] + 1;
		}

		// delete subtree
		$q = "DELETE FROM ".$this->table_tree." ".
			"WHERE lft BETWEEN '".$a_node["lft"]."' AND '".$a_node["rgt"]."' ".
			"AND rgt BETWEEN '".$a_node["lft"]."' AND '".$a_node["rgt"]."' ".
			"AND ".$this->tree_pk." = '".$a_node[$this->tree_pk]."'";
		$this->ilDB->query($q);

		// We only close the gap, if the resulting gap will be larger then the gap value 
		if ($a_node['rgt'] - $a_node['lft'] >= $this->gap * 2)
		{
			$this->log->write('ilTree.deleteTree('.$a_node['child'].') closing gap at '.$a_node['lft'].'...'.$a_node['rgt']);
			// close gaps
			$q = "UPDATE ".$this->table_tree." SET ".
				 "lft = CASE ".
				 "WHEN lft > '".$a_node["lft"]." '".
				 "THEN lft - '".$diff." '".
				 "ELSE lft ".
				 "END, ".
				 "rgt = CASE ".
				 "WHEN rgt > '".$a_node["lft"]." '".
				 "THEN rgt - '".$diff." '".
				 "ELSE rgt ".
				 "END ".
				 "WHERE ".$this->tree_pk." = '".$a_node[$this->tree_pk]."'";
			$this->ilDB->query($q);
		}
		else
		{
			$this->log->write('ilTree.deleteTree('.$a_node['child'].') leaving gap open '.$a_node['lft'].'...'.$a_node['rgt']);
		}

		if($this->__isMainTree())
		{
			ilDBx::_unlockTables();
		}
		// LOCKED ###########################################################
	}

	/**
	* get path from a given startnode to a given endnode
	* if startnode is not given the rootnode is startnode.
	* This function chooses the algorithm to be used.
	*
	* @access	public
	* @param	integer	node_id of endnode
	* @param	integer	node_id of startnode (optional)
	* @return	array	ordered path info (id,title,parent) from start to end
	*/
	function getPathFull($a_endnode_id, $a_startnode_id = 0)
	{
		$pathIds =& $this->getPathId($a_endnode_id, $a_startnode_id);
		$dataPath = array();
		foreach ($pathIds as $id) {
			$dataPath[] = $this->getNodeData($id);
		}

		return $dataPath;
	}
	/**
	* get path from a given startnode to a given endnode
	* if startnode is not given the rootnode is startnode
	* @access	public
	* @param	integer		node_id of endnode
	* @param	integer		node_id of startnode (optional)
	* @return	array		all path ids from startnode to endnode
	*/
	function getPathIdsUsingNestedSets($a_endnode_id, $a_startnode_id = 0)
	{
		// The nested sets algorithm is very easy to implement.
		// Unfortunately it always does a full table space scan to retrieve the path
		// regardless whether indices on lft and rgt are set or not.
		// (At least, this is what happens on MySQL 4.1).
		// This algorithms performs well for small trees which are deeply nested.
		
		
		if (!isset($a_endnode_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getPathId(): No endnode_id given! ",$this->ilErr->WARNING);
		}
		
		$q = "SELECT T2.child ".
			"FROM ".$this->table_tree." AS T1, ".$this->table_tree." AS T2 ".
			"WHERE T1.child = '".$a_endnode_id."' ".
			"AND T1.lft BETWEEN T2.lft AND T2.rgt ".
			"AND T1.".$this->tree_pk." = '".$this->tree_id." '".
			"AND T2.".$this->tree_pk." = '".$this->tree_id." '".
			"ORDER BY T2.depth";

		$r = $this->ilDB->query($q);
		$takeId = $a_startnode_id == 0;
		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if ($takeId || $row['child'] == $a_startnode_id)
			{
				$takeId = true;
				$pathIds[] = $row['child'];
			}
		}
		return $pathIds;

	}
	/**
	* get path from a given startnode to a given endnode
	* if startnode is not given the rootnode is startnode
	* @access	public
	* @param	integer		node_id of endnode
	* @param	integer		node_id of startnode (optional)
	* @return	array		all path ids from startnode to endnode
	*/
	function getPathIdsUsingAdjacencyMap($a_endnode_id, $a_startnode_id = 0)
	{
		// The adjacency map algorithm is harder to implement than the nested sets algorithm.
		// This algorithms performs an index search for each of the path element.
		// This algorithms performs well for large trees which are not deeply nested.

		// The $takeId variable is used, to determine if a given id shall be included in the path
		$takeId = $a_startnode_id == 0;
		
		if (!isset($a_endnode_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getPathId(): No endnode_id given! ",$this->ilErr->WARNING);
		}
		
		global $log, $ilDB;
		
		// Determine the depth of the endnode, and fetch its parent field also.
		$q = 'SELECT t.depth,t.parent '.
			'FROM '.$this->table_tree.' AS t '.
			'WHERE child='.$a_endnode_id.' '.
			'AND '.$this->tree_pk.' = '.$this->tree_id.' '.
			'LIMIT 1';
			//$this->writelog('getIdsUsingAdjacencyMap q='.$q);
		$r = $this->ilDB->query($q);
		
		if ($r->numRows() == 0)
		{
			return array();
		}
		$row = $r->fetchRow(DB_FETCHMODE_ASSOC);
		$nodeDepth = $row['depth'];
		$parentId = $row['parent'];
			//$this->writelog('getIdsUsingAdjacencyMap depth='.$nodeDepth);

		// Fetch the node ids. For shallow depths we can fill in the id's directly.	
		$pathIds = array();
		if ($nodeDepth == 1)
		{
				$takeId = $takeId || $a_endnode_id == $a_startnode_id;
				if ($takeId) $pathIds[] = $a_endnode_id;
		}
		else if ($nodeDepth == 2)
		{
				$takeId = $takeId || $parentId == $a_startnode_id;
				if ($takeId) $pathIds[] = $parentId;
				$takeId = $takeId || $a_endnode_id == $a_startnode_id;
				if ($takeId) $pathIds[] = $a_endnode_id;
		}
		else if ($nodeDepth == 3)
		{
				$takeId = $takeId || $this->root_id == $a_startnode_id;
				if ($takeId) $pathIds[] = $this->root_id;
				$takeId = $takeId || $parentId == $a_startnode_id;
				if ($takeId) $pathIds[] = $parentId;
				$takeId = $takeId || $a_endnode_id == $a_startnode_id;
				if ($takeId) $pathIds[] = $a_endnode_id;
		}
		else if ($nodeDepth < 10)
		{
			// The following code construct nested self-joins
			// Since we already know the root-id of the tree and
			// we also know the id and parent id of the current node,
			// we only need to perform $nodeDepth - 3 self-joins. 
			// We can further reduce the number of self-joins by 1
			// by taking into account, that each row in table tree
			// contains the id of itself and of its parent.
			$qSelect = 't1.child as c0';
			$qJoin = '';
			for ($i = 1; $i < $nodeDepth - 2; $i++)
			{
				$qSelect .= ', t'.$i.'.parent as c'.$i;
				$qJoin .= ' JOIN '.$this->table_tree.' AS t'.$i.' ON '.
							't'.$i.'.child=t'.($i - 1).'.parent AND '.
							't'.$i.'.'.$this->tree_pk.' = '.$this->tree_id;
			}
			$q = 'SELECT '.$qSelect.' '.
				'FROM '.$this->table_tree.' AS t0 '.$qJoin.' '.
				'WHERE t0.'.$this->tree_pk.' = '.$this->tree_id.' '.
				'AND t0.child='.$parentId.' '.
				'LIMIT 1';
			$r = $this->ilDB->query($q);
			if ($r->numRows() == 0)
			{
				return array();
			}
			$row = $r->fetchRow(DB_FETCHMODE_ASSOC);
			
			$takeId = $takeId || $this->root_id == $a_startnode_id;			
			if ($takeId) $pathIds[] = $this->root_id;
			for ($i = $nodeDepth - 4; $i >=0; $i--)
			{
				$takeId = $takeId || $row['c'.$i] == $a_startnode_id;
				if ($takeId) $pathIds[] = $row['c'.$i];
			}
			$takeId = $takeId || $parentId == $a_startnode_id;
			if ($takeId) $pathIds[] = $parentId;
			$takeId = $takeId || $a_endnode_id == $a_startnode_id;
			if ($takeId) $pathIds[] = $a_endnode_id;
		}
		else
		{
			return $this->getPathIdsUsingNestedSets($a_endnode_id, $a_startnode_id);
		}
		
		return $pathIds;
	}

	/**
	* get path from a given startnode to a given endnode
	* if startnode is not given the rootnode is startnode
	* @access	public
	* @param	integer		node_id of endnode
	* @param	integer		node_id of startnode (optional)
	* @return	array		all path ids from startnode to endnode
	*/
	function getPathId($a_endnode_id, $a_startnode_id = 0)
	{
		$pathIds =& $this->getPathIdsUsingAdjacencyMap($a_endnode_id, $a_startnode_id);
		return $pathIds;
	}

	/**
	* check consistence of tree
	* all left & right values are checked if they are exists only once
	* @access	public
	* @return	boolean		true if tree is ok; otherwise throws error object
	*/
	function checkTree()
	{
		$q = "SELECT lft,rgt FROM ".$this->table_tree." ".
			 "WHERE ".$this->tree_pk." = '".$this->tree_id."'";

		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$lft[] = $row->lft;
			$rgt[] = $row->rgt;
		}

		$all = array_merge($lft,$rgt);
		$uni = array_unique($all);

		if (count($all) != count($uni))
		{
			$message = sprintf('%s::checkTree(): Tree is corrupted!',
							   get_class($this));

			$this->log->write($message,$this->log->FATAL);
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		return true;
	}

	/**
	* check, if all childs of tree nodes exist in object table
	*/
	function checkTreeChilds($a_no_zero_child = true)
	{
		$q = "SELECT * FROM ".$this->table_tree." ".
			 "WHERE ".$this->tree_pk." = '".$this->tree_id."' ".
			 "ORDER BY lft";
		$r1 = $this->ilDB->query($q);
		while ($row = $r1->fetchRow(DB_FETCHMODE_ASSOC))
		{
//echo "tree:".$row[$this->tree_pk].":lft:".$row["lft"].":rgt:".$row["rgt"].":child:".$row["child"].":<br>";
			if (($row["child"] == 0) && $a_no_zero_child)
			{
				$this->ilErr->raiseError(get_class($this)."::checkTreeChilds(): Tree contains child with ID 0!",$this->ilErr->WARNING);
			}

			if ($this->table_obj_reference)
			{
				// get object reference data
				$q = "SELECT * FROM ".$this->table_obj_reference." WHERE ".$this->ref_pk."='".$row["child"]."'";
				$r2 = $this->ilDB->query($q);
//echo "num_childs:".$r2->numRows().":<br>";
				if ($r2->numRows() == 0)
				{
					$this->ilErr->raiseError(get_class($this)."::checkTree(): No Object-to-Reference entry found for ID ".
						$row["child"]."!",$this->ilErr->WARNING);
				}
				if ($r2->numRows() > 1)
				{
					$this->ilErr->raiseError(get_class($this)."::checkTree(): More Object-to-Reference entries found for ID ".
						$row["child"]."!",$this->ilErr->WARNING);
				}

				// get object data
				$obj_ref = $r2->fetchRow(DB_FETCHMODE_ASSOC);

				$q = "SELECT * FROM ".$this->table_obj_data." WHERE ".$this->obj_pk."='".$obj_ref[$this->obj_pk]."'";
				$r3 = $this->ilDB->query($q);
				if ($r3->numRows() == 0)
				{
					$this->ilErr->raiseError(get_class($this)."::checkTree(): No child found for ID ".
						$obj_ref[$this->obj_pk]."!",$this->ilErr->WARNING);
				}
				if ($r3->numRows() > 1)
				{
					$this->ilErr->raiseError(get_class($this)."::checkTree(): More childs found for ID ".
						$obj_ref[$this->obj_pk]."!",$this->ilErr->WARNING);
				}

			}
			else
			{
				// get only object data
				$q = "SELECT * FROM ".$this->table_obj_data." WHERE ".$this->obj_pk."='".$row["child"]."'";
				$r2 = $this->ilDB->query($q);
//echo "num_childs:".$r2->numRows().":<br>";
				if ($r2->numRows() == 0)
				{
					$this->ilErr->raiseError(get_class($this)."::checkTree(): No child found for ID ".
						$row["child"]."!",$this->ilErr->WARNING);
				}
				if ($r2->numRows() > 1)
				{
					$this->ilErr->raiseError(get_class($this)."::checkTree(): More childs found for ID ".
						$row["child"]."!",$this->ilErr->WARNING);
				}
			}
		}

		return true;
	}

	/**
	* Return the maximum depth in tree
	* @access	public
	* @return	integer	max depth level of tree
	*/
	function getMaximumDepth()
	{
		$q = "SELECT MAX(depth) FROM ".$this->table_tree;
		$r = $this->ilDB->query($q);

		$row = $r->fetchRow();

		return $row[0];
	}

	/**
	* return depth of a node in tree
	* @access	private
	* @param	integer		node_id of parent's node_id
	* @return	integer		depth of node in tree
	*/
	function getDepth($a_node_id)
	{
		if ($a_node_id)
		{
			$q = "SELECT depth FROM ".$this->table_tree." ".
				 "WHERE child = '".$a_node_id."' ".
				 "AND ".$this->tree_pk." = '".$this->tree_id."'";

			$res = $this->ilDB->query($q);
			$row = $res->fetchRow(DB_FETCHMODE_OBJECT);

			return $row->depth;
		}
		else
		{
			return 1;
		}
	}


	/**
	* get all information of a node.
	* get data of a specific node from tree and object_data
	* @access	public
	* @param	integer		node id
	* @return	array		2-dim (int/str) node_data
	*/
	function getNodeData($a_node_id)
	{
		if (!isset($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getNodeData(): No node_id given! ",$this->ilErr->WARNING);
		}
		if($this->__isMainTree())
		{
			if($a_node_id < 1)
			{
				$message = sprintf('%s::getNodeData(): No valid parameter given! $a_node_id: %s',
								   get_class($this),
								   $a_node_id);

				$this->log->write($message,$this->log->FATAL);
				$this->ilErr->raiseError($message,$this->ilErr->WARNING);
			}
		}

		$q = "SELECT * FROM ".$this->table_tree." ".
			 $this->buildJoin().
			 "WHERE ".$this->table_tree.".child = '".$a_node_id."' ".
			 "AND ".$this->table_tree.".".$this->tree_pk." = '".$this->tree_id."'";
		$r = $this->ilDB->query($q);
		$row = $r->fetchRow(DB_FETCHMODE_ASSOC);
		$row[$this->tree_pk] = $this->tree_id;

		return $this->fetchNodeData($row);
	}

	/**
	* get data of parent node from tree and object_data
	* @access	private
 	* @param	object	db	db result object containing node_data
	* @return	array		2-dim (int/str) node_data
	* TODO: select description twice for compability. Please use 'desc' in future only
	*/
	function fetchNodeData($a_row)
	{
		global $objDefinition, $lng, $ilBench;

		//$ilBench->start("Tree", "fetchNodeData_getRow");
		$data = $a_row;
		$data["desc"] = $a_row["description"];  // for compability
		//$ilBench->stop("Tree", "fetchNodeData_getRow");

		// multilingual support systemobjects (sys) & categories (db)
		//$ilBench->start("Tree", "fetchNodeData_readDefinition");
		if (is_object($objDefinition))
		{
			$translation_type = $objDefinition->getTranslationType($data["type"]);
		}
		//$ilBench->stop("Tree", "fetchNodeData_readDefinition");

		if ($translation_type == "sys")
		{
			//$ilBench->start("Tree", "fetchNodeData_getLangData");
			if ($data["type"] == "rolf" and $data["obj_id"] != ROLE_FOLDER_ID)
			{
				$data["description"] = $lng->txt("obj_".$data["type"]."_local_desc").$data["title"].$data["desc"];
				$data["desc"] = $lng->txt("obj_".$data["type"]."_local_desc").$data["title"].$data["desc"];
				$data["title"] = $lng->txt("obj_".$data["type"]."_local");
			}
			else
			{
				$data["title"] = $lng->txt("obj_".$data["type"]);
				$data["description"] = $lng->txt("obj_".$data["type"]."_desc");
				$data["desc"] = $lng->txt("obj_".$data["type"]."_desc");
			}
			//$ilBench->stop("Tree", "fetchNodeData_getLangData");
		}
		elseif ($translation_type == "db")
		{
			//$ilBench->start("Tree", "fetchNodeData_getTranslation");
			$q = "SELECT title,description FROM object_translation ".
				 "WHERE obj_id = ".$data["obj_id"]." ".
				 "AND lang_code = '".$this->lang_code."' ".
				 "AND NOT lang_default = 1";
			$r = $this->ilDB->query($q);

			$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

			if ($row)
			{
				$data["title"] = $row->title;
				$data["description"] = ilUtil::shortenText($row->description,MAXLENGTH_OBJ_DESC,true);
				$data["desc"] = $row->description;
			}
			//$ilBench->stop("Tree", "fetchNodeData_getTranslation");
		}

		return $data ? $data : array();
	}


	/**
	* get all information of a node.
	* get data of a specific node from tree and object_data
	* @access	public
	* @param	integer		node id
	* @return	boolean		true, if node id is in tree
	*/
	function isInTree($a_node_id)
	{
		if (!isset($a_node_id))
		{
			return false;
			#$this->ilErr->raiseError(get_class($this)."::getNodeData(): No node_id given! ",$this->ilErr->WARNING);
		}

		$q = "SELECT * FROM ".$this->table_tree." ".
			 "WHERE ".$this->table_tree.".child = '".$a_node_id."' ".
			 "AND ".$this->table_tree.".".$this->tree_pk." = '".$this->tree_id."'";
		$r = $this->ilDB->query($q);

		if ($r->numRows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}

	}

	/**
	* get data of parent node from tree and object_data
	* @access	public
 	* @param	integer		node id
	* @return	array
	*/
	function getParentNodeData($a_node_id)
	{
		if (!isset($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getParentNodeData(): No node_id given! ",$this->ilErr->WARNING);
		}

		if ($this->table_obj_reference)
		{
			$leftjoin = "LEFT JOIN ".$this->table_obj_reference." ON v.child=".$this->table_obj_reference.".".$this->ref_pk." ".
				  		"LEFT JOIN ".$this->table_obj_data." ON ".$this->table_obj_reference.".".$this->obj_pk."=".$this->table_obj_data.".".$this->obj_pk." ";
		}
		else
		{
			$leftjoin = "LEFT JOIN ".$this->table_obj_data." ON v.child=".$this->table_obj_data.".".$this->obj_pk." ";
		}

		$q = "SELECT * FROM ".$this->table_tree." s,".$this->table_tree." v ".
			 $leftjoin.
			 "WHERE s.child = '".$a_node_id."' ".
			 "AND s.parent = v.child ".
			 "AND s.lft > v.lft ".
			 "AND s.rgt < v.rgt ".
			 "AND s.".$this->tree_pk." = '".$this->tree_id."' ".
			 "AND v.".$this->tree_pk." = '".$this->tree_id."'";
		$r = $this->ilDB->query($q);

		$row = $r->fetchRow(DB_FETCHMODE_ASSOC);

		return $this->fetchNodeData($row);
	}

	/**
	* checks if a node is in the path of an other node
	* @access	public
 	* @param	integer		object id of start node
	* @param    integer     object id of query node
	* @return	integer		number of entries
	*/
	function isGrandChild($a_startnode_id,$a_querynode_id)
	{
		if (!isset($a_startnode_id) or !isset($a_querynode_id))
		{
			return false;
			// No raise error, since it is a already a check function
			#$this->ilErr->raiseError(get_class($this)."::isGrandChild(): Missing parameter! startnode: ".$a_startnode_id." querynode: ".
			#						 $a_querynode_id,$this->ilErr->WARNING);
		}

		$q = "SELECT * FROM ".$this->table_tree." s,".$this->table_tree." v ".
			 "WHERE s.child = '".$a_startnode_id."' ".
			 "AND v.child = '".$a_querynode_id."' ".
			 "AND s.".$this->tree_pk." = '".$this->tree_id."' ".
			 "AND v.".$this->tree_pk." = '".$this->tree_id."' ".
			 "AND v.lft BETWEEN s.lft AND s.rgt ".
			 "AND v.rgt BETWEEN s.lft AND s.rgt";
		$r = $this->ilDB->query($q);

		return $r->numRows();
	}

	/**
	* create a new tree
	* to do: ???
	* @param	integer		a_tree_id: obj_id of object where tree belongs to
	* @param	integer		a_node_id: root node of tree (optional; default is tree_id itself)
	* @return	boolean		true on success
	* @access	public
	*/
	function addTree($a_tree_id,$a_node_id = -1)
	{
		// FOR SECURITY addTree() IS NOT ALLOWED ON MAIN TREE
		// IF SOMEONE WILL NEED FEATURES LIKE $tree->addTree(2) ON THE MAIN TREE PLEASE CONTACT ME (smeyer@databay.de)
		if($this->__isMainTree())
		{
			$message = sprintf('%s::addTree(): Operation not allowed on main tree! $a_tree_if: %s $a_node_id: %s',
							   get_class($this),
							   $a_tree_id,
							   $a_node_id);
			$this->log->write($message,$this->log->FATAL);
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		if (!isset($a_tree_id))
		{
			$this->ilErr->raiseError(get_class($this)."::addTree(): No tree_id given! ",$this->ilErr->WARNING);
		}

		if ($a_node_id <= 0)
		{
			$a_node_id = $a_tree_id;
		}

		$q = "INSERT INTO ".$this->table_tree." (".$this->tree_pk.", child, parent, lft, rgt, depth) ".
			 "VALUES ".
			 "('".$a_tree_id."','".$a_node_id."', 0, 1, 2, 1)";

		$this->ilDB->query($q);

		return true;
	}

	/**
	* get nodes by type
	* // TODO: method needs revision
	* @param	integer		a_tree_id: obj_id of object where tree belongs to
	* @param	integer		a_type_id: type of object
	* @access	public
	*/
	function getNodeDataByType($a_type)
	{
		if (!isset($a_type) or (!is_string($a_type)))
		{
			$this->ilErr->raiseError(get_class($this)."::getNodeDataByType(): Type not given or wrong datatype!",$this->ilErr->WARNING);
		}

		$data = array();	// node_data
		$row = "";			// fetched row
		$left = "";			// tree_left
		$right = "";		// tree_right

		$q = "SELECT * FROM ".$this->table_tree." ".
			 "WHERE ".$this->tree_pk." = '".$this->tree_id."'".
			 "AND parent = '0'";
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$left = $row->lft;
			$right = $row->rgt;
		}

		$q = "SELECT * FROM ".$this->table_tree." ".
			 $this->buildJoin().
			 "WHERE ".$this->table_obj_data.".type = '".$a_type."' ".
			 "AND ".$this->table_tree.".lft BETWEEN '".$left."' AND '".$right."' ".
			 "AND ".$this->table_tree.".rgt BETWEEN '".$left."' AND '".$right."' ".
			 "AND ".$this->table_tree.".".$this->tree_pk." = '".$this->tree_id."'";
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$data[] = $this->fetchNodeData($row);
		}

		return $data;
	}

	/**
	* remove an existing tree
	*
	* @param	integer		a_tree_id: tree to be removed
	* @return	boolean		true on success
	* @access	public
 	*/
	function removeTree($a_tree_id)
	{
		// OPERATION NOT ALLOWED ON MAIN TREE
		if($this->__isMainTree())
		{
			$message = sprintf('%s::removeTree(): Operation not allowed on main tree! $a_tree_if: %s',
							   get_class($this),
							   $a_tree_id);
			$this->log->write($message,$this->log->FATAL);
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
		if (!$a_tree_id)
		{
			$this->ilErr->raiseError(get_class($this)."::removeTree(): No tree_id given! Action aborted",$this->ilErr->MESSAGE);
		}

		$q = "DELETE FROM ".$this->table_tree." WHERE ".$this->tree_pk." = '".$a_tree_id."'";
		$this->ilDB->query($q);

		return true;
	}

	/**
	* save subtree: copy a subtree (defined by node_id) to a new tree
	* with $this->tree_id -node_id. This is neccessary for undelete functionality
	* @param	integer	node_id
	* @return	integer
	* @access	public
	*/
	function saveSubTree($a_node_id, $a_set_deleted = false)
	{
		if (!$a_node_id)
		{
			$message = sprintf('%s::saveSubTree(): No valid parameter given! $a_node_id: %s',
							   get_class($this),
							   $a_node_id);
			$this->log->write($message,$this->log->FATAL);
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		// LOCKED ###############################################
		if($this->__isMainTree())
		{
			ilDBx::_lockTables(array('tree' => 'WRITE',
				'object_reference' => 'WRITE'));
		}

		// GET LEFT AND RIGHT VALUE
		$q = "SELECT * FROM ".$this->table_tree." ".
			 "WHERE ".$this->tree_pk." = '".$this->tree_id."' ".
			 "AND child = '".$a_node_id."' ";
		$r = $this->ilDB->query($q);

		while($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$lft = $row->lft;
			$rgt = $row->rgt;
		}

		// GET ALL SUBNODES
		$q = "SELECT * FROM ".$this->table_tree." ".
			 "WHERE ".$this->tree_pk." = '".$this->tree_id."' ".
			 "AND lft BETWEEN '".$lft."' AND '".$rgt."'";
		$r = $this->ilDB->query($q);

		while($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$subnodes[$row["child"]] = $this->fetchNodeData($row);
		}

		// SAVE SUBTREE
		foreach($subnodes as $node)
		{
			$q = "INSERT INTO ".$this->table_tree." ".
				 "VALUES ('".-$a_node_id."','".$node["child"]."','".$node["parent"]."','".
				 $node["lft"]."','".$node["rgt"]."','".$node["depth"]."')";
			$r = $this->ilDB->query($q);
			
			// set node as deleted
			if ($a_set_deleted)
			{
				ilObject::_setDeletedDate($node["child"]);
			}
		}
		if($this->__isMainTree())
		{
			ilDBX::_unlockTables();
		}

		// LOCKED ###############################################
		return true;
	}

	/**
	* This is a wrapper for isSaved() with a more useful name
	*/
	function isDeleted($a_node_id)
	{
		return $this->isSaved($a_node_id);
	}

	/**
	* check if node is saved
	*/
	function isSaved($a_node_id)
	{
		$q = "SELECT * FROM ".$this->table_tree." ".
			 "WHERE child = '".$a_node_id."'";
		$s = $this->ilDB->query($q);
		$r = $s->fetchRow(DB_FETCHMODE_ASSOC);

		if ($r[$this->tree_pk] < 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	* save node: copy a node (defined by obj_id and parent) to a new tree
	* with tree_id -obj_id.This is neccessary for link
	*
	* DEPRECATED? (I grepped through the whole code, it seems that this
	* method is never used) If you need this method, send me an e-mail
	* (alex.killing@gmx.de), the deletion date handling is missing)
	*
	* @param	integer	node_id
	* @param	integer	parent_id
	* @return	boolean
	* @access	public
	*/
	function saveNode($a_node_id,$a_parent_id)
	{
		if($this->__isMainTree())
		{
			if($a_node_id <= 1 or $a_parent_id <= 0)
			{
				$message = sprintf('%s::saveSubTree(): No valid parameter given! $a_node_id: %s $a_parent_id: %s',
								   get_class($this),
								   $a_node_id,
								   $a_parent_id);

				$this->log->write($message,$this->log->FATAL);
				$this->ilErr->raiseError($message,$this->ilErr->WARNING);
			}
		}
		if ($a_node_id < 1 or !isset($a_parent_id))
		{
			$this->ilErr->raiseError(get_class($this)."::saveNode(): Missing parameter! ".
									 "node_id: ".$a_node_id." parent_id: ".$a_parent_id,$this->ilErr->WARNING);
		}

		// SAVE NODE
		$q = "INSERT INTO ".$this->table_tree." ".
			 "VALUES ('".-$a_node_id."','".$a_node_id."','".$a_parent_id."','1','2','1')";
		$r = $this->ilDB->query($q);

		return true;
	}

	/**
	* get data saved/deleted nodes
	* @return	array	data
	* @param	integer	id of parent object of saved object
	* @access	public
	*/
	function getSavedNodeData($a_parent_id)
	{
		if (!isset($a_parent_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getSavedNodeData(): No node_id given!",$this->ilErr->WARNING);
		}

		$q =	"SELECT * FROM ".$this->table_tree." ".
				$this->buildJoin().
				"WHERE ".$this->table_tree.".".$this->tree_pk." < 0 ".
				"AND ".$this->table_tree.".parent = '".$a_parent_id."' ";
		$r = $this->ilDB->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$saved[] = $this->fetchNodeData($row);
		}

		return $saved ? $saved : array();
	}

	/**
	* get parent id of given node
	* @access	public
	* @param	integer	node id
	* @return	integer	parent id
	*/
	function getParentId($a_node_id)
	{
		if (!isset($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getParentId(): No node_id given! ",$this->ilErr->WARNING);
		}

		$q = "SELECT parent FROM ".$this->table_tree." ".
			 "WHERE child='".$a_node_id."' ".
			 "AND ".$this->tree_pk."='".$this->tree_id."'";
		$r = $this->ilDB->query($q);

		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

		return $row->parent;
	}

	/**
	* get left value of given node
	* @access	public
	* @param	integer	node id
	* @return	integer	left value
	*/
	function getLeftValue($a_node_id)
	{
		if (!isset($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getLeftValued(): No node_id given! ",$this->ilErr->WARNING);
		}

		$q = "SELECT lft FROM ".$this->table_tree." ".
			 "WHERE child='".$a_node_id."' ".
			 "AND ".$this->tree_pk."='".$this->tree_id."'";
		$r = $this->ilDB->query($q);

		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

		return $row->lft;
	}

	/**
	* get sequence number of node in sibling sequence
	* @access	public
	* @param	array		node
	* @return	integer		sequence number
	*/
	function getChildSequenceNumber($a_node, $type = "")
	{
		if (!isset($a_node))
		{
			$this->ilErr->raiseError(get_class($this)."::getChildSequenceNumber(): No node_id given! ",$this->ilErr->WARNING);
		}

		$type_str = ($type != "")
			? "AND type='$type'"
			: "";

		$q = "SELECT count(*) AS cnt FROM ".$this->table_tree." ".
			$this->buildJoin().
			"WHERE lft <=".$this->ilDB->quote($a_node["lft"])." ".
			$type_str.
			"AND parent=".$this->ilDB->quote($a_node["parent"])." ".
			"AND ".$this->table_tree.".".$this->tree_pk."=".$this->ilDB->quote($this->tree_id);
		$r = $this->ilDB->query($q);

		$row = $r->fetchRow(DB_FETCHMODE_ASSOC);

		return $row["cnt"];
	}

	/**
	* read root id from database
	* @param root_id
	* @access public
	* @return int new root id
	*/
	function readRootId()
	{
		$query = "SELECT child FROM $this->table_tree ".
			"WHERE parent = '0'".
			"AND ".$this->tree_pk." = '".$this->tree_id."'";
		$row = $this->ilDB->getRow($query,DB_FETCHMODE_OBJECT);

		$this->root_id = $row->child;
		return $this->root_id;
	}

	/**
	* get the root id of tree
	* @access	public
	* @return	integer	root node id
	*/
	function getRootId()
	{
		return $this->root_id;
	}
	function setRootId($a_root_id)
	{
		$this->root_id = $a_root_id;
	}

	/**
	* get tree id
	* @access	public
	* @return	integer	tree id
	*/
	function getTreeId()
	{
		return $this->tree_id;
	}

	/**
	* set tree id
	* @access	public
	* @return	integer	tree id
	*/
	function setTreeId($a_tree_id)
	{
		$this->tree_id = $a_tree_id;
	}

	/**
	* get node data of successor node
	*
	* @access	public
	* @param	integer		node id
	* @return	array		node data array
	*/
	function fetchSuccessorNode($a_node_id, $a_type = "")
	{
		if (!isset($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getNodeData(): No node_id given! ",$this->ilErr->WARNING);
		}

		// get lft value for current node
		$q = "SELECT lft FROM ".$this->table_tree." ".
			 "WHERE ".$this->table_tree.".child = '".$a_node_id."' ".
			 "AND ".$this->table_tree.".".$this->tree_pk." = '".$this->tree_id."'";
		$r = $this->ilDB->query($q);
		$curr_node = $r->fetchRow(DB_FETCHMODE_ASSOC);

		// get data of successor node
		$type_where = ($a_type != "")
			? "AND ".$this->table_obj_data.".type = '$a_type' "
			: "";
		$q = "SELECT * FROM ".$this->table_tree." ".
			 $this->buildJoin().
			 "WHERE lft > '".$curr_node["lft"]."' ".
			 $type_where.
			 "AND ".$this->table_tree.".".$this->tree_pk." = '".$this->tree_id."'".
			 "ORDER BY lft LIMIT 1";
		$r = $this->ilDB->query($q);

		if ($r->numRows() < 1)
		{
			return false;
		}
		else
		{
			$row = $r->fetchRow(DB_FETCHMODE_ASSOC);
			return $this->fetchNodeData($row);
		}
	}

	/**
	* get node data of predecessor node
	*
	* @access	public
	* @param	integer		node id
	* @return	array		node data array
	*/
	function fetchPredecessorNode($a_node_id, $a_type = "")
	{
		if (!isset($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getNodeData(): No node_id given! ",$this->ilErr->WARNING);
		}

		// get lft value for current node
		$q = "SELECT lft FROM ".$this->table_tree." ".
			 "WHERE ".$this->table_tree.".child = '".$a_node_id."' ".
			 "AND ".$this->table_tree.".".$this->tree_pk." = '".$this->tree_id."'";
		$r = $this->ilDB->query($q);
		$curr_node = $r->fetchRow(DB_FETCHMODE_ASSOC);

		// get data of predecessor node
		$type_where = ($a_type != "")
			? "AND ".$this->table_obj_data.".type = '$a_type' "
			: "";
		$q = "SELECT * FROM ".$this->table_tree." ".
			 $this->buildJoin().
			 "WHERE lft < '".$curr_node["lft"]."' ".
			 $type_where.
			 "AND ".$this->table_tree.".".$this->tree_pk." = '".$this->tree_id."'".
			 "ORDER BY lft DESC LIMIT 1";
		$r = $this->ilDB->query($q);

		if ($r->numRows() < 1)
		{
			return false;
		}
		else
		{
			$row = $r->fetchRow(DB_FETCHMODE_ASSOC);
			return $this->fetchNodeData($row);
		}
	}

	/**
	* Wrapper for renumber. This method locks the table tree
	* (recursive)
	* @access	public
	* @param	integer	node_id where to start (usually the root node)
	* @param	integer	first left value of start node (usually 1)
	* @return	integer	current left value of recursive call
	*/
	function renumber($node_id = 1, $i = 1)
	{
		// LOCKED ###################################
		if($this->__isMainTree())
		{
			ilDBx::_lockTables(array($this->table_tree => 'WRITE',
									 $this->table_obj_data => 'WRITE',
									 $this->table_obj_reference => 'WRITE',
									 'object_translation' => 'WRITE'));
		}
		$return = $this->__renumber($node_id,$i);
		if($this->__isMainTree())
		{
			ilDBx::_unlockTables();
		}
		// LOCKED ###################################
		return $return;
	}

	// PRIVATE
	/**
	* This method is private. Always call ilTree->renumber() since it locks the tree table
 	* renumber left/right values and close the gaps in numbers
	* (recursive)
	* @access	private
	* @param	integer	node_id where to start (usually the root node)
	* @param	integer	first left value of start node (usually 1)
	* @return	integer	current left value of recursive call
	*/
	function __renumber($node_id = 1, $i = 1)
	{
		$q = "UPDATE ".$this->table_tree." SET lft='".$i."' WHERE child='".$node_id."'";
		$this->ilDB->query($q);

		$childs = $this->getChilds($node_id);

		foreach ($childs as $child)
		{
			$i = $this->__renumber($child["child"],$i+1);
		}

		$i++;
		
		// Insert a gap at the end of node, if the node has children
		if (count($childs) > 0)
		{
			$i += $this->gap * 2;
		}
		
		$q = "UPDATE ".$this->table_tree." SET rgt='".$i."' WHERE child='".$node_id."'";
		$this->ilDB->query($q);

		return $i;
	}


	/**
	* Check for parent type
	* e.g check if a folder (ref_id 3) is in a parent course obj => checkForParentType(3,'crs');
	*
 	* @access	public
	* @param	integer	ref_id
	* @param	string type
	* @param	int ref_id of last parent type
	*/
	function checkForParentType($a_ref_id,$a_type)
	{
		if(!$this->isInTree($a_ref_id))
		{
			return false;
		}
		$path = array_reverse($this->getPathFull($a_ref_id));

		foreach($path as $node)
		{
			if($node["type"] == $a_type)
			{
				return $node["child"];
			}
		}
		return 0;
	}

	/**
	* STATIC METHOD
	* Removes a single entry from a tree. The tree structure is NOT updated!
	*
 	* @access	public
	* @param	integer	tree id
	* @param	integer	child id
	* @param	string	db_table name. default is 'tree' (optional)
	*/
	function _removeEntry($a_tree,$a_child,$a_db_table = "tree")
	{
		global $ilDB,$ilLog,$ilErr;

		if($a_db_table === 'tree')
		{
			if($a_tree == 1 and $a_child == ROOT_FOLDER_ID)
			{
				$message = sprintf('%s::_removeEntry(): Tried to delete root node! $a_tree: %s $a_child: %s',
								   get_class($this),
								   $a_tree,
								   $a_child);
				$ilLog->write($message,$ilLog->FATAL);
				$ilErr->raiseError($message,$ilErr->WARNING);
			}
		}

		$q = "DELETE from ".$a_db_table." WHERE tree='".$a_tree."' AND child='".$a_child."'";
		$ilDB->query($q);
	}

	// PRIVATE METHODS
	/**
	* Check if operations are done on main tree
	*
 	* @access	private
	* @return boolean
	*/
	function __isMainTree()
	{
		return $this->table_tree === 'tree';
	}

	/**
	* Check for deleteTree()
	* compares a subtree of a given node by checking lft, rgt against parent relation
	*
 	* @access	private
	* @param array node data from ilTree::getNodeData()
	* @return boolean
	*/
	function __checkDelete($a_node)
	{
		// get subtree by lft,rgt
		$query = "SELECT * FROM ".$this->table_tree." ".
			"WHERE lft >= ".$a_node['lft']." ".
			"AND rgt <= ".$a_node['rgt']." ".
			"AND ".$this->tree_pk." = '".$a_node[$this->tree_pk]."'";


		$res = $this->ilDB->query($query);

		$counter = (int) $lft_childs = array();
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$lft_childs[$row->child] = $row->parent;
			++$counter;
		}

		// CHECK FOR DUPLICATE CHILD IDS
		if($counter != count($lft_childs))
		{
			$message = sprintf('%s::__checkTree(): Duplicate entries for "child" in maintree! $a_node_id: %s',
								   get_class($this),
							   $a_node['child']);
			$this->log->write($message,$this->log->FATAL);
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		// GET SUBTREE BY PARENT RELATION
		$parent_childs = array();
		$this->__getSubTreeByParentRelation($a_node['child'],$parent_childs);
		$this->__validateSubtrees($lft_childs,$parent_childs);

		return true;
	}

	function __getSubTreeByParentRelation($a_node_id,&$parent_childs)
	{
		// GET PARENT ID
		$query = "SELECT * FROM ".$this->table_tree." ".
			"WHERE child = '".$a_node_id."' ".
			"AND tree = '".$this->tree_id."'";

		$res = $this->ilDB->query($query);
		$counter = 0;
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$parent_childs[$a_node_id] = $row->parent;
			++$counter;
		}
		// MULTIPLE ENTRIES
		if($counter > 1)
		{
			$message = sprintf('%s::__getSubTreeByParentRelation(): Multiple entries in maintree! $a_node_id: %s',
							   get_class($this),
							   $a_node_id);
			$this->log->write($message,$this->log->FATAL);
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		// GET ALL CHILDS
		$query = "SELECT * FROM ".$this->table_tree." ".
			"WHERE parent = '".$a_node_id."'";

		$res = $this->ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			// RECURSION
			$this->__getSubTreeByParentRelation($row->child,$parent_childs);
		}
		return true;
	}

	function __validateSubtrees(&$lft_childs,$parent_childs)
	{
		// SORT BY KEY
		ksort($lft_childs);
		ksort($parent_childs);

		if(count($lft_childs) != count($parent_childs))
		{
			$message = sprintf('%s::__validateSubtrees(): (COUNT) Tree is corrupted! Left/Right subtree does not comply .'.
							   'with parent relation',
							   get_class($this));
			$this->log->write($message,$this->log->FATAL);
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

		foreach($lft_childs as $key => $value)
		{
			if($parent_childs[$key] != $value)
			{
				$message = sprintf('%s::__validateSubtrees(): (COMPARE) Tree is corrupted! Left/Right subtree does not comply '.
								   'with parent relation',
								   get_class($this));
				$this->log->write($message,$this->log->FATAL);
				$this->ilErr->raiseError($message,$this->ilErr->WARNING);
			}
			if($key == ROOT_FOLDER_ID)
			{
				$message = sprintf('%s::__validateSubtrees(): (ROOT_FOLDER) Tree is corrupted! Tried to delete root folder',
								   get_class($this));
				$this->log->write($message,$this->log->FATAL);
				$this->ilErr->raiseError($message,$this->ilErr->WARNING);
			}
		}
		return true;
	}

} // END class.tree
?>
