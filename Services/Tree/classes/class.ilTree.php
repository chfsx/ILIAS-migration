<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

define("IL_LAST_NODE", -2);
define("IL_FIRST_NODE", -1);

include_once './Services/Tree/exceptions/class.ilInvalidTreeStructureException.php';

/**
 *  @defgroup ServicesTree Services/Tree
 */

/**
* Tree class
* data representation in hierachical trees using the Nested Set Model with Gaps 
* by Joe Celco.
*
* @author Sascha Hofmann <saschahofmann@gmx.de>
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
*
* @ingroup ServicesTree
*/
class ilTree
{
	const POS_LAST_NODE = -2;
	const POS_FIRST_NODE = -1;
	
	
	const RELATION_CHILD = 1;		// including grand child
	const RELATION_PARENT = 2;		// including grand child
	const RELATION_SIBLING = 3;
	const RELATION_EQUALS = 4;
	const RELATION_NONE = 5;
	
	
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

	protected $depth_cache = array();
	protected $parent_cache = array();
	protected $in_tree_cache = array();
	
	private $tree_impl = NULL;


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
		$this->ilDB = $ilDB;

		if (!isset($ilErr))
		{
			$ilErr = new ilErrorHandling();
			$ilErr->setErrorHandling(PEAR_ERROR_CALLBACK,array($ilErr,'errorHandler'));
		}
		else
		{
			$this->ilErr = $ilErr;
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
		$this->log = $ilLog;

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

		$this->use_cache = true;

		// If cache is activated, cache object translations to improve performance
		$this->translation_cache = array();
		$this->parent_type_cache = array();

		// By default, we create gaps in the tree sequence numbering for 50 nodes
		$this->gap = 50;
		
		
		// init tree implementation 
		$this->initTreeImplementation();
	}
	
	/**
	 * Init tree implementation
	 */
	public function initTreeImplementation()
	{
		global $ilDB;
		
		
		if(!is_object($GLOBALS['ilSetting']) or $GLOBALS['ilSetting']->getModule() != 'common')
		{
			include_once './Services/Administration/classes/class.ilSetting.php';
			$setting = new ilSetting('common');
		}
		else
		{
			$setting = $GLOBALS['ilSetting'];
		}
		
		if($this->__isMainTree())
		{
			if($setting->get('main_tree_impl','ns') == 'ns')
			{
				#$GLOBALS['ilLog']->write(__METHOD__.': Using nested set.');
				include_once './Services/Tree/classes/class.ilNestedSetTree.php';
				$this->tree_impl = new ilNestedSetTree($this);
			}
			else
			{
				#$GLOBALS['ilLog']->write(__METHOD__.': Using materialized path.');
				include_once './Services/Tree/classes/class.ilMaterializedPathTree.php';
				$this->tree_impl = new ilMaterializedPathTree($this);
			}
		}
		else
		{
			#$GLOBALS['ilLog']->write(__METHOD__.': Using netsted set for non main tree.');
			include_once './Services/Tree/classes/class.ilNestedSetTree.php';
			$this->tree_impl = new ilNestedSetTree($this);
		}
	}
	
	/**
	 * Get tree implementation
	 * @return ilTreeImplementation $impl
	 */
	public function getTreeImplementation()
	{
		return $this->tree_impl;
	}
	
	/**
	* Use Cache (usually activated)
	*/
	public function useCache($a_use = true)
	{
		$this->use_cache = $a_use;
	}
	
	/**
	 * Check if cache is active
	 * @return bool
	 */
	public function isCacheUsed()
	{
		return $this->__isMainTree() and $this->use_cache;
	}
	
	/**
	 * Get depth cache
	 * @return type
	 */
	public function getDepthCache()
	{
		return (array) $this->depth_cache;
	}
	
	/**
	 * Get parent cache
	 * @return type
	 */
	public function getParentCache()
	{
		return (array) $this->parent_cache;
	}
	
	/**
	* Store user language. This function is used by the "main"
	* tree only (during initialisation).
	*/
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
	 * Get tree table name
	 * @return string tree table name
	 */
	public function getTreeTable()
	{
		return $this->table_tree;
	}
	
	/**
	 * Get object data table
	 * @return type
	 */
	public function getObjectDataTable()
	{
		return $this->table_obj_data;
	}
	
	/**
	 * Get tree primary key
	 * @return string column of pk
	 */
	public function getTreePk()
	{
		return $this->tree_pk;
	}
	
	/**
	 * Get reference table if available
	 */
	public function getTableReference()
	{
		return $this->table_obj_reference;
	}
	
	/**
	 * Get default gap	 * @return int
	 */
	public function getGap()
	{
		return $this->gap;
	}
	
	/***
	 * reset in tree cache
	 */
	public function resetInTreeCache()
	{
		$this->in_tree_cache = array();
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
		
		$this->initTreeImplementation();

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
			// Use inner join instead of left join to improve performance
			return "JOIN ".$this->table_obj_reference." ON ".$this->table_tree.".child=".$this->table_obj_reference.".".$this->ref_pk." ".
				   "JOIN ".$this->table_obj_data." ON ".$this->table_obj_reference.".".$this->obj_pk."=".$this->table_obj_data.".".$this->obj_pk." ";
		}
		else
		{
			// Use inner join instead of left join to improve performance
			return "JOIN ".$this->table_obj_data." ON ".$this->table_tree.".child=".$this->table_obj_data.".".$this->obj_pk." ";
		}
	}
	
	/**
	 * Get relation of two nodes
	 * @param int $a_node_a
	 * @param int $a_node_b
	 */
	public function getRelation($a_node_a, $a_node_b)
	{
		return $this->getRelationOfNodes(
					$this->getNodeTreeData($a_node_a),
					$this->getNodeTreeData($a_node_b)
		);
	}
	
	/**
	 * get relation of two nodes by node data
	 * @param array $a_node_a_arr
	 * @param array $a_node_b_arr
	 * 
	 */
	public function getRelationOfNodes($a_node_a_arr, $a_node_b_arr)
	{
		return $this->getTreeImplementation()->getRelation($a_node_a_arr, $a_node_b_arr);
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
		global $ilBench,$ilDB, $ilObjDataCache, $ilUser;
		
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

			 
		$query = sprintf('SELECT * FROM '.$this->table_tree.' '.
				$this->buildJoin().
				"WHERE parent = %s " .
				"AND ".$this->table_tree.".".$this->tree_pk." = %s ".
				$order_clause,
				$ilDB->quote($a_node_id,'integer'),
				$ilDB->quote($this->tree_id,'integer'));

		$res = $ilDB->query($query);
		
		if(!$count = $res->numRows())
		{
			return array();
		}

		// get rows and object ids
		$rows = array();
		while($r = $ilDB->fetchAssoc($res))
		{
			$rows[] = $r;
			$obj_ids[] = $r["obj_id"];
		}

		// preload object translation information
		if ($this->__isMainTree() && $this->isCacheUsed() && is_object($ilObjDataCache) &&
			is_object($ilUser) && $this->lang_code == $ilUser->getLanguage() && !$this->oc_preloaded[$a_node_id])
		{
//			$ilObjDataCache->preloadTranslations($obj_ids, $this->lang_code);
			$ilObjDataCache->preloadObjectCache($obj_ids, $this->lang_code);
			$this->fetchTranslationFromObjectDataCache($obj_ids);
			$this->oc_preloaded[$a_node_id] = true;
		}

		foreach ($rows as $row)
		{
			$childs[] = $this->fetchNodeData($row);

			// Update cache of main tree
			if ($this->__isMainTree())
			{
				#$GLOBALS['ilLog']->write(__METHOD__.': Storing in tree cache '.$row['child'].' = true');
				$this->in_tree_cache[$row['child']] = $row['tree'] == 1;
			}
		}
		$childs[$count - 1]["last"] = true;
		return $childs;
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
		global $ilDB;
		
		if (!isset($a_node_id) or !isset($a_type))
		{
			$message = get_class($this)."::getChildsByType(): Missing parameter! node_id:".$a_node_id." type:".$a_type;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}

        if ($a_type=='rolf' && $this->table_obj_reference) {
            // Performance optimization: A node can only have exactly one
            // role folder as its child. Therefore we don't need to sort the
            // results, and we can let the database know about the expected limit.
            $ilDB->setLimit(1,0);
            $query = sprintf("SELECT * FROM ".$this->table_tree." ".
                $this->buildJoin().
                "WHERE parent = %s ".
                "AND ".$this->table_tree.".".$this->tree_pk." = %s ".
                "AND ".$this->table_obj_data.".type = %s ",
                $ilDB->quote($a_node_id,'integer'),
                $ilDB->quote($this->tree_id,'integer'),
                $ilDB->quote($a_type,'text'));
        } else {
            $query = sprintf("SELECT * FROM ".$this->table_tree." ".
                $this->buildJoin().
                "WHERE parent = %s ".
                "AND ".$this->table_tree.".".$this->tree_pk." = %s ".
                "AND ".$this->table_obj_data.".type = %s ".
                "ORDER BY ".$this->table_tree.".lft",
                $ilDB->quote($a_node_id,'integer'),
                $ilDB->quote($this->tree_id,'integer'),
                $ilDB->quote($a_type,'text'));
        }
		$res = $ilDB->query($query);
		
		// init childs
		$childs = array();
		while($row = $ilDB->fetchAssoc($res))
		{
			$childs[] = $this->fetchNodeData($row);
		}
		
		return $childs ? $childs : array();
	}


	/**
	* get child nodes of given node by object type
	* @access	public
	* @param	integer		node_id
	* @param	array		array of object type
	* @return	array		with node data of all childs or empty array
	*/
	public function getChildsByTypeFilter($a_node_id,$a_types,$a_order = "",$a_direction = "ASC")
	{
		global $ilDB;
		
		if (!isset($a_node_id) or !$a_types)
		{
			$message = get_class($this)."::getChildsByType(): Missing parameter! node_id:".$a_node_id." type:".$a_types;
			$this->ilErr->raiseError($message,$this->ilErr->WARNING);
		}
	
		$filter = ' ';
		if($a_types)
		{
			$filter = 'AND '.$this->table_obj_data.'.type IN('.implode(',',ilUtil::quoteArray($a_types)).') ';
		}

		// set order_clause if sort order parameter is given
		if (!empty($a_order))
		{
			$order_clause = "ORDER BY ".$a_order." ".$a_direction;
		}
		else
		{
			$order_clause = "ORDER BY ".$this->table_tree.".lft";
		}
		
		$query = 'SELECT * FROM '.$this->table_tree.' '.
			$this->buildJoin().
			'WHERE parent = '.$ilDB->quote($a_node_id,'integer').' '.
			'AND '.$this->table_tree.'.'.$this->tree_pk.' = '.$ilDB->quote($this->tree_id,'integer').' '.
			$filter.
			$order_clause;
		
		$res = $ilDB->query($query);
		while($row = $ilDB->fetchAssoc($res))
		{
			$childs[] = $this->fetchNodeData($row);
		}	
		
		return $childs ? $childs : array();
	}
	
	/**
	* insert new node with node_id under parent node with parent_id
	* @access	public
	* @param	integer		node_id
	* @param	integer		parent_id
	* @param	integer		IL_LAST_NODE | IL_FIRST_NODE | node id of preceding child
	*/
	public function insertNode($a_node_id, $a_parent_id, $a_pos = IL_LAST_NODE, $a_reset_deletion_date = false)
	{
		global $ilDB;
		
//echo "+$a_node_id+$a_parent_id+";
		// CHECK node_id and parent_id > 0 if in main tree
		if($this->__isMainTree())
		{
			if($a_node_id <= 1 or $a_parent_id <= 0)
			{
				$GLOBALS['ilLog']->logStack();
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
			$GLOBALS['ilLog']->logStack();
			$this->ilErr->raiseError(get_class($this)."::insertNode(): Missing parameter! ".
				"node_id: ".$a_node_id." parent_id: ".$a_parent_id,$this->ilErr->WARNING);
		}
		if ($this->isInTree($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::insertNode(): Node ".$a_node_id." already in tree ".
									 $this->table_tree."!",$this->ilErr->WARNING);
		}

		$this->getTreeImplementation()->insertNode($a_node_id, $a_parent_id, $a_pos);
		
		$this->in_tree_cache[$a_node_id] = true;

		// reset deletion date
		if ($a_reset_deletion_date)
		{
			ilObject::_resetDeletedDate($a_node_id);
		}
	}
	
	/**
	 * get filtered subtree
	 * 
	 * get all subtree nodes beginning at a specific node
	 * excluding specific object types and their child nodes.
	 * 
	 * E.g getFilteredSubTreeNodes()
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function getFilteredSubTree($a_node_id,$a_filter = array())
	{
		$node = $this->getNodeData($a_node_id);
		
		$first = true;
		$depth = 0;
		foreach($this->getSubTree($node) as $subnode)
		{
			if($depth and $subnode['depth'] > $depth)
			{
				continue;
			}
			if(!$first and in_array($subnode['type'],$a_filter))
			{
				$depth = $subnode['depth'];
				$first = false;
				continue;
			}
			$depth = 0;
			$first = false;
			$filtered[] = $subnode; 
		}
		return $filtered ? $filtered : array();
	}
	
	/**
	 * Get all ids of subnodes
	 * @return 
	 * @param object $a_ref_id
	 */
	public function getSubTreeIds($a_ref_id)
	{
		return $this->getTreeImplementation()->getSubTreeIds($a_ref_id);
	}
	

	/**
	* get all nodes in the subtree under specified node
	*
	* @access	public
	* @param	array		node_data
	* @param    boolean     with data: default is true otherwise this function return only a ref_id array
	* @return	array		2-dim (int/array) key, node_data of each subtree node including the specified node
	* @throws InvalidArgumentException
	*/
	function getSubTree($a_node,$a_with_data = true, $a_type = "")
	{
		global $ilDB;
		
		if (!is_array($a_node))
		{
			$GLOBALS['ilLog']->logStack();
			throw new InvalidArgumentException(__METHOD__.': wrong datatype for node data given');
		}

		/*
		if($a_node['lft'] < 1 or $a_node['rgt'] < 2)
		{
			$GLOBALS['ilLog']->logStack();
			$message = sprintf('%s: Invalid node given! $a_node["lft"]: %s $a_node["rgt"]: %s',
								   __METHOD__,
								   $a_node['lft'],
								   $a_node['rgt']);

			throw new InvalidArgumentException($message);
		}
		*/
		
		$query = $this->getTreeImplementation()->getSubTreeQuery($a_node, $a_type);
		$res = $ilDB->query($query);
		while($row = $ilDB->fetchAssoc($res))
		{
			if($a_with_data)
			{
				$subtree[] = $this->fetchNodeData($row);
			}
			else
			{
				$subtree[] = $row['child'];
			}
			// the lm_data "hack" should be removed in the trunk during an alpha
			if($this->__isMainTree() || $this->table_tree == "lm_tree")
			{
				$this->in_tree_cache[$row['child']] = true;
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
	 * @throws InvalidArgumentException, InvalidTreeStructureException
	 */
	function deleteTree($a_node)
	{
		global $ilDB;

		$GLOBALS['ilLog']->write(__METHOD__.': Delete tree with node '. $a_node);
		
		if (!is_array($a_node))
		{
			$GLOBALS['ilLog']->logStack();
			throw new InvalidArgumentException(__METHOD__.': Wrong datatype for node data!');
		}
		
		$GLOBALS['ilLog']->write(__METHOD__.': '. $this->tree_pk);
		
		if($this->__isMainTree() )
		{
			// @todo normally this part is not executed, since the subtree is first 
			// moved to trash and then deleted.
			if(!$this->__checkDelete($a_node))
			{
				$GLOBALS['ilLog']->logStack();
				throw new ilInvalidTreeStructureException('Deletion canceled due to invalid tree structure.' . print_r($a_node,true));
			}
		}

		$this->getTreeImplementation()->deleteTree($a_node['child']);
		
		$this->resetInTreeCache();
		
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

		// We retrieve the full path in a single query to improve performance
        global $ilDB;

		// Abort if no path ids were found
		if (count($pathIds) == 0)
		{
			return null;
		}

		$inClause = 'child IN (';
		for ($i=0; $i < count($pathIds); $i++)
		{
			if ($i > 0) $inClause .= ',';
			$inClause .= $ilDB->quote($pathIds[$i],'integer');
		}
		$inClause .= ')';

		$q = 'SELECT * '.
			'FROM '.$this->table_tree.' '.
            $this->buildJoin().' '.
			'WHERE '.$inClause.' '.
            'AND '.$this->table_tree.'.'.$this->tree_pk.' = '.$this->ilDB->quote($this->tree_id,'integer').' '.
			'ORDER BY depth';
		$r = $ilDB->query($q);

		$pathFull = array();
		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$pathFull[] = $this->fetchNodeData($row);

			// Update cache
			if ($this->__isMainTree())
			{
				#$GLOBALS['ilLog']->write(__METHOD__.': Storing in tree cache '.$row['child']);
				$this->in_tree_cache[$row['child']] = $row['tree'] == 1;
			}
		}
		return $pathFull;
	}
	

	/**
	 * Preload depth/parent
	 *
	 * @param
	 * @return
	 */
	function preloadDepthParent($a_node_ids)
	{
		global $ilDB;

		if (!$this->__isMainTree() || !is_array($a_node_ids) || !$this->isCacheUsed())
		{
			return;
		}

		$res = $ilDB->query('SELECT t.depth, t.parent, t.child '.
			'FROM '.$this->table_tree.' t '.
			'WHERE '.$ilDB->in("child", $a_node_ids, false, "integer").
			'AND '.$this->tree_pk.' = '.$ilDB->quote($this->tree_id, "integer"));
		while ($row = $ilDB->fetchAssoc($res))
		{
			$this->depth_cache[$row["child"]] = $row["depth"];
			$this->parent_cache[$row["child"]] = $row["parent"];
		}
	}

	/**
	* get path from a given startnode to a given endnode
	* if startnode is not given the rootnode is startnode
	* @access	public
	* @param	integer		node_id of endnode
	* @param	integer		node_id of startnode (optional)
	* @return	array		all path ids from startnode to endnode
	* @throws InvalidArgumentException
	*/
	public function getPathId($a_endnode_id, $a_startnode_id = 0)
	{
		if(!$a_endnode_id)
		{
			$GLOBALS['ilLog']->logStack();
			throw new InvalidArgumentException(__METHOD__.': No endnode given!');
		}
		
		// path id cache
		if ($this->isCacheUsed() && isset($this->path_id_cache[$a_endnode_id][$a_startnode_id]))
		{
//echo "<br>getPathIdhit";
			return $this->path_id_cache[$a_endnode_id][$a_startnode_id];
		}
//echo "<br>miss";

		$pathIds = $this->getTreeImplementation()->getPathIds($a_endnode_id, $a_startnode_id);
		
		if($this->__isMainTree())
		{
			$this->path_id_cache[$a_endnode_id][$a_startnode_id] = $pathIds;
		}
		return $pathIds;
	}

	// BEGIN WebDAV: getNodePathForTitlePath function added
	/**
	* Converts a path consisting of object titles into a path consisting of tree
	* nodes. The comparison is non-case sensitive.
	*
	* Note: this function returns the same result as getNodePath, 
	* but takes a title path as parameter.
	*
	* @access	public
	* @param	Array	Path array with object titles.
	*                       e.g. array('ILIAS','English','Course A')
	* @param	ref_id	Startnode of the relative path. 
	*                       Specify null, if the title path is an absolute path.
	*                       Specify a ref id, if the title path is a relative 
	*                       path starting at this ref id.
	* @return	array	ordered path info (depth,parent,child,obj_id,type,title)
	*               or null, if the title path can not be converted into a node path.
	*/
	function getNodePathForTitlePath($titlePath, $a_startnode_id = null)
	{
		global $ilDB, $log;
		//$log->write('getNodePathForTitlePath('.implode('/',$titlePath));
		
		// handle empty title path
		if ($titlePath == null || count($titlePath) == 0)
		{
			if ($a_startnode_id == 0)
			{
				return null;
			}
			else
			{
				return $this->getNodePath($a_startnode_id);
			}
		}

		// fetch the node path up to the startnode
		if ($a_startnode_id != null && $a_startnode_id != 0)
		{
			// Start using the node path to the root of the relative path
			$nodePath = $this->getNodePath($a_startnode_id);
			$parent = $a_startnode_id;
		}
		else
		{
			// Start using the root of the tree
			$nodePath = array();
			$parent = 0;
		}

		
		// Convert title path into Unicode Normal Form C
		// This is needed to ensure that we can compare title path strings with
		// strings from the database.
		require_once('include/Unicode/UtfNormal.php');
		include_once './Services/Utilities/classes/class.ilStr.php';
		$inClause = 'd.title IN (';
		for ($i=0; $i < count($titlePath); $i++)
		{
			$titlePath[$i] = ilStr::strToLower(UtfNormal::toNFC($titlePath[$i]));
			if ($i > 0) $inClause .= ',';
			$inClause .= $ilDB->quote($titlePath[$i],'text');
		}
		$inClause .= ')';

		// Fetch all rows that are potential path elements
		if ($this->table_obj_reference)
		{
			$joinClause = 'JOIN '.$this->table_obj_reference.'  r ON t.child = r.'.$this->ref_pk.' '.
				'JOIN '.$this->table_obj_data.' d ON r.'.$this->obj_pk.' = d.'.$this->obj_pk;
		}
		else
		{
			$joinClause = 'JOIN '.$this->table_obj_data.'  d ON t.child = d.'.$this->obj_pk;
		}
		// The ORDER BY clause in the following SQL statement ensures that,
		// in case of a multiple objects with the same title, always the Object
		// with the oldest ref_id is chosen.
		// This ensure, that, if a new object with the same title is added,
		// WebDAV clients can still work with the older object.
		$q = 'SELECT t.depth, t.parent, t.child, d.'.$this->obj_pk.' obj_id, d.type, d.title '.
			'FROM '.$this->table_tree.'  t '.
			$joinClause.' '.
			'WHERE '.$inClause.' '.
			'AND t.depth <= '.(count($titlePath)+count($nodePath)).' '.
			'AND t.tree = 1 '.
			'ORDER BY t.depth, t.child ASC';
		$r = $ilDB->query($q);
		
		$rows = array();
		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$row['title'] = UtfNormal::toNFC($row['title']);
			$row['ref_id'] = $row['child'];
			$rows[] = $row;
		}

		// Extract the path elements from the fetched rows
		for ($i = 0; $i < count($titlePath); $i++) {
			$pathElementFound = false; 
			foreach ($rows as $row) {
				if ($row['parent'] == $parent && 
				ilStr::strToLower($row['title']) == $titlePath[$i])
				{
					// FIXME - We should test here, if the user has 
					// 'visible' permission for the object.
					$nodePath[] = $row;
					$parent = $row['child'];
					$pathElementFound = true;
					break;
				}
			}
			// Abort if we haven't found a path element for the current depth
			if (! $pathElementFound)
			{
				//$log->write('ilTree.getNodePathForTitlePath('.var_export($titlePath,true).','.$a_startnode_id.'):null');
				return null;
			}
		}
		// Return the node path
		//$log->write('ilTree.getNodePathForTitlePath('.var_export($titlePath,true).','.$a_startnode_id.'):'.var_export($nodePath,true));
		return $nodePath;
	}
	// END WebDAV: getNodePathForTitlePath function added
	// END WebDAV: getNodePath function added
	/**
	* Returns the node path for the specified object reference.
	*
	* Note: this function returns the same result as getNodePathForTitlePath, 
	* but takes ref-id's as parameters.
	*
	* This function differs from getPathFull, in the following aspects:
	* - The title of an object is not translated into the language of the user
	* - This function is significantly faster than getPathFull.
	*
	* @access	public
	* @param	integer	node_id of endnode
	* @param	integer	node_id of startnode (optional)
	* @return	array	ordered path info (depth,parent,child,obj_id,type,title)
	*               or null, if the node_id can not be converted into a node path.
	*/
	function getNodePath($a_endnode_id, $a_startnode_id = 0)
	{
		global $ilDB;

		$pathIds = $this->getPathId($a_endnode_id, $a_startnode_id);

		// Abort if no path ids were found
		if (count($pathIds) == 0)
		{
			return null;
		}

		
		$types = array();
		$data = array();
		for ($i = 0; $i < count($pathIds); $i++)
		{
			$types[] = 'integer';
			$data[] = $pathIds[$i];
		}

		$query = 'SELECT t.depth,t.parent,t.child,d.obj_id,d.type,d.title '.
			'FROM '.$this->table_tree.' t '.
			'JOIN '.$this->table_obj_reference.' r ON r.ref_id = t.child '.
			'JOIN '.$this->table_obj_data.' d ON d.obj_id = r.obj_id '.
			'WHERE '.$ilDB->in('t.child',$data,false,'integer').' '.
			'ORDER BY t.depth ';
			
		$res = $ilDB->queryF($query,$types,$data);

		$titlePath = array();
		while ($row = $ilDB->fetchAssoc($res))
		{
			$titlePath[] = $row;
		}
		return $titlePath;
	}
	// END WebDAV: getNodePath function added

	/**
	* check consistence of tree
	* all left & right values are checked if they are exists only once
	* @access	public
	* @return	boolean		true if tree is ok; otherwise throws error object
	*/
	function checkTree()
	{
		global $ilDB;
		
		$types = array('integer');
		$query = 'SELECT lft,rgt FROM '.$this->table_tree.' '.
			'WHERE '.$this->tree_pk.' = %s ';
		
		$res = $ilDB->queryF($query,$types,array($this->tree_id));
		while ($row = $ilDB->fetchObject($res))
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
		global $ilDB;
		
		$query = 'SELECT * FROM '.$this->table_tree.' '.
				'WHERE '.$this->tree_pk.' = %s '.
				'ORDER BY lft';
		$r1 = $ilDB->queryF($query,array('integer'),array($this->tree_id));
		
		while ($row = $ilDB->fetchAssoc($r1))
		{
//echo "tree:".$row[$this->tree_pk].":lft:".$row["lft"].":rgt:".$row["rgt"].":child:".$row["child"].":<br>";
			if (($row["child"] == 0) && $a_no_zero_child)
			{
				$this->ilErr->raiseError(get_class($this)."::checkTreeChilds(): Tree contains child with ID 0!",$this->ilErr->WARNING);
			}

			if ($this->table_obj_reference)
			{
				// get object reference data
				$query = 'SELECT * FROM '.$this->table_obj_reference.' WHERE '.$this->ref_pk.' = %s ';
				$r2 = $ilDB->queryF($query,array('integer'),array($row['child']));
				
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
				$obj_ref = $ilDB->fetchAssoc($r2);

				$query = 'SELECT * FROM '.$this->table_obj_data.' WHERE '.$this->obj_pk.' = %s';
				$r3 = $ilDB->queryF($query,array('integer'),array($obj_ref[$this->obj_pk]));
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
				$query = 'SELECT * FROM '.$this->table_obj_data.' WHERE '.$this->obj_pk.' = %s';
				$r2 = $ilDB->queryF($query,array('integer'),array($row['child']));
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
	 * Return the current maximum depth in the tree
	 * @access	public
	 * @return	integer	max depth level of tree
	 */
	public function getMaximumDepth()
	{
		global $ilDB;
		
		$query = 'SELECT MAX(depth) depth FROM '.$this->table_tree;
		$res = $ilDB->query($query);		
		
		$row = $ilDB->fetchAssoc($res);
		return $row['depth'];
	}

	/**
	* return depth of a node in tree
	* @access	private
	* @param	integer		node_id of parent's node_id
	* @return	integer		depth of node in tree
	*/
	function getDepth($a_node_id)
	{
		global $ilDB;
		
		if ($a_node_id)
		{
			$query = 'SELECT depth FROM '.$this->table_tree.' '.
				'WHERE child = %s '.
				'AND '.$this->tree_pk.' = %s ';
			$res = $ilDB->queryF($query,array('integer','integer'),array($a_node_id,$this->tree_id));
			$row = $ilDB->fetchObject($res);

			return $row->depth;
		}
		else
		{
			return 1;
		}
	}
	
	/**
	 * return all columns of tabel tree
	 * @param type $a_node_id
	 * @return array of table column => values
	 * 
	 * @throws InvalidArgumentException
	 */
	public function getNodeTreeData($a_node_id)
	{
		global $ilDB;
		
		if(!$a_node_id)
		{
			$GLOBALS['ilLog']->logStack();
			throw new InvalidArgumentException('Missing or empty parameter $a_node_id: '. $a_node_id);
		}
		
		$query = 'SELECT * FROM tree '.
				'WHERE child = '.$ilDB->quote($a_node_id,'integer');
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			return $row;
		}
		return array();
	}


	/**
	* get all information of a node.
	* get data of a specific node from tree and object_data
	* @access	public
	* @param	integer		node id
	* @return	array		2-dim (int/str) node_data
	*/
	// BEGIN WebDAV: Pass tree id to this method
	//function getNodeData($a_node_id)
	function getNodeData($a_node_id, $a_tree_pk = null)
	// END PATCH WebDAV: Pass tree id to this method
	{
		global $ilDB;
		
		if (!isset($a_node_id))
		{
			$GLOBALS['ilLog']->logStack();
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

		// BEGIN WebDAV: Pass tree id to this method
		$query = 'SELECT * FROM '.$this->table_tree.' '.
			$this->buildJoin().
			'WHERE '.$this->table_tree.'.child = %s '.
			'AND '.$this->table_tree.'.'.$this->tree_pk.' = %s ';
		$res = $ilDB->queryF($query,array('integer','integer'),array(
			$a_node_id,
			$a_tree_pk === null ? $this->tree_id : $a_tree_pk));
		// END WebDAV: Pass tree id to this method
		$row = $ilDB->fetchAssoc($res);
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
		global $objDefinition, $lng, $ilBench,$ilDB;

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

			// Try to retrieve object translation from cache
			if ($this->isCacheUsed() &&
				array_key_exists($data["obj_id"].'.'.$lang_code, $this->translation_cache)) {

				$key = $data["obj_id"].'.'.$lang_code;
				$data["title"] = $this->translation_cache[$key]['title'];
				$data["description"] = $this->translation_cache[$key]['description'];
				$data["desc"] = $this->translation_cache[$key]['desc'];
			} 
			else 
			{
				// Object translation is not in cache, read it from database
				//$ilBench->start("Tree", "fetchNodeData_getTranslation");
				$query = 'SELECT title,description FROM object_translation '.
					'WHERE obj_id = %s '.
					'AND lang_code = %s '.
					'AND NOT lang_default = %s';

				$res = $ilDB->queryF($query,array('integer','text','integer'),array(
					$data['obj_id'],
					$this->lang_code,
					1));
				$row = $ilDB->fetchObject($res);

				if ($row)
				{
					$data["title"] = $row->title;
					$data["description"] = ilUtil::shortenText($row->description,ilObject::DESC_LENGTH,true);
					$data["desc"] = $row->description;
				}
				//$ilBench->stop("Tree", "fetchNodeData_getTranslation");

				// Store up to 1000 object translations in cache
				if ($this->isCacheUsed() && count($this->translation_cache) < 1000)
				{
					$key = $data["obj_id"].'.'.$lang_code;
					$this->translation_cache[$key] = array();
					$this->translation_cache[$key]['title'] = $data["title"] ;
					$this->translation_cache[$key]['description'] = $data["description"];
					$this->translation_cache[$key]['desc'] = $data["desc"];
				}
			}
		}

		// TODO: Handle this switch by module.xml definitions
		if($data['type'] == 'crsr' or $data['type'] == 'catr')
		{
			include_once('./Services/ContainerReference/classes/class.ilContainerReference.php');
			$data['title'] = ilContainerReference::_lookupTargetTitle($data['obj_id']);
		}

		return $data ? $data : array();
	}

	/**
	 * Get translation data from object cache (trigger in object cache on preload)
	 *
	 * @param	array	$a_obj_ids		object ids
	 */
	protected function fetchTranslationFromObjectDataCache($a_obj_ids)
	{
		global $ilObjDataCache;

		if ($this->isCacheUsed() && is_array($a_obj_ids) && is_object($ilObjDataCache))
		{
			foreach ($a_obj_ids as $id)
			{
				$this->translation_cache[$id.'.']['title'] = $ilObjDataCache->lookupTitle($id);
				$this->translation_cache[$id.'.']['description'] = $ilObjDataCache->lookupDescription($id);;
				$this->translation_cache[$id.'.']['desc'] =
					$this->translation_cache[$id.'.']['description'];
			}
		}
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
		global $ilDB;

		if (!isset($a_node_id))
		{
			return false;
			#$this->ilErr->raiseError(get_class($this)."::getNodeData(): No node_id given! ",$this->ilErr->WARNING);
		}
		// is in tree cache
		if ($this->isCacheUsed() && isset($this->in_tree_cache[$a_node_id]))
		{
			#$GLOBALS['ilLog']->write(__METHOD__.': Using in tree cache '.$a_node_id);
//echo "<br>in_tree_hit";
			return $this->in_tree_cache[$a_node_id];
		}

		$query = 'SELECT * FROM '.$this->table_tree.' '.
			'WHERE '.$this->table_tree.'.child = %s '.
			'AND '.$this->table_tree.'.'.$this->tree_pk.' = %s';
			
		$res = $ilDB->queryF($query,array('integer','integer'),array(
			$a_node_id,
			$this->tree_id));

		if ($res->numRows() > 0)
		{
			if($this->__isMainTree())
			{
				#$GLOBALS['ilLog']->write(__METHOD__.': Storing in tree cache '.$a_node_id.' = true');
				$this->in_tree_cache[$a_node_id] = true;
			}
			return true;
		}
		else
		{
			if($this->__isMainTree())
			{
				#$GLOBALS['ilLog']->write(__METHOD__.': Storing in tree cache '.$a_node_id.' = false');
				$this->in_tree_cache[$a_node_id] = false;
			}
			return false;
		}
	}

	/**
	* get data of parent node from tree and object_data
	* @access	public
 	* @param	integer		node id
	* @return	array
	* @throws InvalidArgumentException
	*/
	public function getParentNodeData($a_node_id)
	{
		global $ilDB;
		global $ilLog;
		
		if (!isset($a_node_id))
		{
			$ilLog->logStack();
			throw new InvalidArgumentException(__METHOD__.': No node_id given!');
		}

		if ($this->table_obj_reference)
		{
			// Use inner join instead of left join to improve performance
			$innerjoin = "JOIN ".$this->table_obj_reference." ON v.child=".$this->table_obj_reference.".".$this->ref_pk." ".
				  		"JOIN ".$this->table_obj_data." ON ".$this->table_obj_reference.".".$this->obj_pk."=".$this->table_obj_data.".".$this->obj_pk." ";
		}
		else
		{
			// Use inner join instead of left join to improve performance
			$innerjoin = "JOIN ".$this->table_obj_data." ON v.child=".$this->table_obj_data.".".$this->obj_pk." ";
		}

		$query = 'SELECT * FROM '.$this->table_tree.' s, '.$this->table_tree.' v '.
			$innerjoin.
			'WHERE s.child = %s '.
			'AND s.parent = v.child '.
			'AND s.'.$this->tree_pk.' = %s '.
			'AND v.'.$this->tree_pk.' = %s';
		$res = $ilDB->queryF($query,array('integer','integer','integer'),array(
			$a_node_id,
			$this->tree_id,
			$this->tree_id));
		$row = $ilDB->fetchAssoc($res);
		return $this->fetchNodeData($row);
	}

	/**
	* checks if a node is in the path of an other node
	* @access	public
 	* @param	integer		object id of start node
	* @param    integer     object id of query node
	* @return	integer		number of entries
	*/
	public function isGrandChild($a_startnode_id,$a_querynode_id)
	{
		return $this->getRelation($a_startnode_id, $a_querynode_id) == self::RELATION_PARENT;
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
		global $ilDB;

		// FOR SECURITY addTree() IS NOT ALLOWED ON MAIN TREE
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

		$query = 'INSERT INTO '.$this->table_tree.' ('.
			$this->tree_pk.', child,parent,lft,rgt,depth) '.
			'VALUES '.
			'(%s,%s,%s,%s,%s,%s)';
		$res = $ilDB->manipulateF($query,array('integer','integer','integer','integer','integer','integer'),array(
			$a_tree_id,
			$a_node_id,
			0,
			1,
			2,
			1));

		return true;
	}

	/**
	 * get nodes by type
	 * @param	integer		a_tree_id: obj_id of object where tree belongs to
	 * @param	integer		a_type_id: type of object
	 * @access	public
	 * @throws InvalidArgumentException 
	 * @deprecated since 4.4.0
	 */
	public function getNodeDataByType($a_type)
	{
		global $ilDB;
		
		if(!isset($a_type) or (!is_string($a_type)))
		{
			$GLOBALS['ilLog']->logStack();
			throw new InvalidArgumentException('Type not given or wrong datatype');
		}

		$query = 'SELECT * FROM ' . $this->table_tree . ' ' .
			$this->buildJoin().
			'WHERE ' . $this->table_obj_data . '.type = ' . $this->ilDB->quote($a_type, 'text').
			'AND ' . $this->table_tree . '.' . $this->tree_pk . ' = ' . $this->ilDB->quote($this->tree_id, 'integer');

		$res = $ilDB->query($query);
		$data = array();
		while($row = $ilDB->fetchAssoc($res))
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
	public function removeTree($a_tree_id)
	{
		global $ilDB;
		
		// OPERATION NOT ALLOWED ON MAIN TREE
		if($this->__isMainTree())
		{
			$GLOBALS['ilLog']->logStack();
			throw new InvalidArgumentException('Operation not allowed on main tree');
		}
		if (!$a_tree_id)
		{
			$GLOBALS['ilLog']->logStack();
			throw new InvalidArgumentException('Missing parameter tree id');
		}

		$query = 'DELETE FROM '.$this->table_tree.
			' WHERE '.$this->tree_pk.' = %s ';
		$ilDB->manipulateF($query,array('integer'),array($a_tree_id));
		return true;
	}
	
	/**
	 * Wrapper for saveSubTree
	 * @param type $a_node_id
	 * @param type $a_set_deleted
	 * @throws InvalidArgumentException
	 */
	public function moveToTrash($a_node_id, $a_set_deleted = false)
	{
		return $this->saveSubTree($a_node_id, $a_set_deleted);
	}

	/**
	 * Use the wrapper moveToTrash
	 * save subtree: delete a subtree (defined by node_id) to a new tree
	 * with $this->tree_id -node_id. This is neccessary for undelete functionality
	 * @param	integer	node_id
	 * @return	integer
	 * @access	public
	 * @throws InvalidArgumentException
	 * @deprecated since 4.4.0
	 */
	public function saveSubTree($a_node_id, $a_set_deleted = false)
	{
		global $ilDB;
		
		if(!$a_node_id)
		{
			$GLOBALS['ilLog']->logStack();
			throw new InvalidArgumentException('No valid parameter given! $a_node_id: '.$a_node_id);
		}

		// LOCKED ###############################################
		if($this->__isMainTree())
		{
			$ilDB->lockTables(
				array(
					0 => array('name' => 'tree', 'type' => ilDB::LOCK_WRITE),
					1 => array('name' => 'object_reference', 'type' => ilDB::LOCK_WRITE)));
		}

		$query = $this->getTreeImplementation()->getSubTreeQuery($this->getNodeTreeData($a_node_id),'',false);
		$res = $ilDB->query($query);

		$subnodes = array();
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$subnodes[] = $row['child'];
		}
		
		if(!count($subnodes))
		{
			// Possibly already deleted
			// Unlock locked tables before returning
			if($this->__isMainTree())
			{
				$ilDB->unlockTables();
			}
			return false;
		}
		
		if($a_set_deleted)
		{
			include_once './Services/Object/classes/class.ilObject.php';
			ilObject::setDeletedDates($subnodes);
		}
		
		// netsted set <=> mp
		$this->getTreeImplementation()->moveToTrash($a_node_id);
		
		if($this->__isMainTree())
		{
			$ilDB->unlockTables();
		}

		// LOCKED ###############################################
		return true;
	}

	/**
	 * This is a wrapper for isSaved() with a more useful name
	 * @param int $a_node_id
	 */
	public function isDeleted($a_node_id)
	{
		return $this->isSaved($a_node_id);
	}

	/**
	 * Use method isDeleted
	 * check if node is saved
	 * @deprecated since 4.4.0
	 */
	public function isSaved($a_node_id)
	{
		global $ilDB;
		
		// is saved cache
		if ($this->isCacheUsed() && isset($this->is_saved_cache[$a_node_id]))
		{
//echo "<br>issavedhit";
			return $this->is_saved_cache[$a_node_id];
		}

		$query = 'SELECT '.$this->tree_pk.' FROM '.$this->table_tree.' '.
			'WHERE child = %s ';
		$res = $ilDB->queryF($query,array('integer'),array($a_node_id));
		$row = $ilDB->fetchAssoc($res);

		if ($row[$this->tree_pk] < 0)
		{
			if($this->__isMainTree())
			{
				$this->is_saved_cache[$a_node_id] = true;
			}
			return true;
		}
		else
		{
			if($this->__isMainTree())
			{
				$this->is_saved_cache[$a_node_id] = false;
			}
			return false;
		}
	}

	/**
	 * Preload deleted information
	 *
	 * @param array nodfe ids
	 * @return bool
	 */
	public function preloadDeleted($a_node_ids)
	{
		global $ilDB;

		if (!is_array($a_node_ids) || !$this->isCacheUsed())
		{
			return;
		}

		$query = 'SELECT '.$this->tree_pk.', child FROM '.$this->table_tree.' '.
			'WHERE '.$ilDB->in("child", $a_node_ids, false, "integer");

		$res = $ilDB->query($query);
		while ($row = $ilDB->fetchAssoc($res))
		{
			if ($row[$this->tree_pk] < 0)
			{
				if($this->__isMainTree())
				{
					$this->is_saved_cache[$row["child"]] = true;
				}
			}
			else
			{
				if($this->__isMainTree())
				{
					$this->is_saved_cache[$row["child"]] = false;
				}
			}
		}
	}


	/**
	* get data saved/deleted nodes
	* @return	array	data
	* @param	integer	id of parent object of saved object
	* @access	public
	*/
	function getSavedNodeData($a_parent_id)
	{
		global $ilDB;
		
		if (!isset($a_parent_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getSavedNodeData(): No node_id given!",$this->ilErr->WARNING);
		}

		$query = 'SELECT * FROM '.$this->table_tree.' '.
			$this->buildJoin().
			'WHERE '.$this->table_tree.'.'.$this->tree_pk.' < %s '.
			'AND '.$this->table_tree.'.parent = %s';
		$res = $ilDB->queryF($query,array('integer','integer'),array(
			0,
			$a_parent_id));

		while($row = $ilDB->fetchAssoc($res))
		{
			$saved[] = $this->fetchNodeData($row);
		}

		return $saved ? $saved : array();
	}
	
	/**
	* get object id of saved/deleted nodes
	* @return	array	data
	* @param	array	object ids to check
	* @access	public
	*/
	function getSavedNodeObjIds(array $a_obj_ids)
	{
		global $ilDB;
		
		$query = 'SELECT '.$this->table_obj_data.'.obj_id FROM '.$this->table_tree.' '.
			$this->buildJoin().
			'WHERE '.$this->table_tree.'.'.$this->tree_pk.' < '.$ilDB->quote(0, 'integer').' '.
			'AND '.$ilDB->in($this->table_obj_data.'.obj_id', $a_obj_ids, '', 'integer');
		$res = $ilDB->query($query);
		while($row = $ilDB->fetchAssoc($res))
		{
			$saved[] = $row['obj_id'];
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
		global $ilDB;
		
		if (!isset($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getParentId(): No node_id given! ",$this->ilErr->WARNING);
		}

		$query = 'SELECT parent FROM '.$this->table_tree.' '.
			'WHERE child = %s '.
			'AND '.$this->tree_pk.' = %s ';
		$res = $ilDB->queryF($query,array('integer','integer'),array(
			$a_node_id,
			$this->tree_id));

		$row = $ilDB->fetchObject($res);
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
		global $ilDB;
		
		if (!isset($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getLeftValued(): No node_id given! ",$this->ilErr->WARNING);
		}

		$query = 'SELECT lft FROM '.$this->table_tree.' '.
			'WHERE child = %s '.
			'AND '.$this->tree_pk.' = %s ';
		$res = $ilDB->queryF($query,array('integer','integer'),array(
			$a_node_id,
			$this->tree_id));
		$row = $ilDB->fetchObject($res);
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
		global $ilDB;
		
		if (!isset($a_node))
		{
			$this->ilErr->raiseError(get_class($this)."::getChildSequenceNumber(): No node_id given! ",$this->ilErr->WARNING);
		}
		
		if($type)
		{
			$query = 'SELECT count(*) cnt FROM '.$this->table_tree.' '.
				$this->buildJoin().
				'WHERE lft <= %s '.
				'AND type = %s '.
				'AND parent = %s '.
				'AND '.$this->table_tree.'.'.$this->tree_pk.' = %s ';

			$res = $ilDB->queryF($query,array('integer','text','integer','integer'),array(
				$a_node['lft'],
				$type,
				$a_node['parent'],
				$this->tree_id));
		}
		else
		{
			$query = 'SELECT count(*) cnt FROM '.$this->table_tree.' '.
				$this->buildJoin().
				'WHERE lft <= %s '.
				'AND parent = %s '.
				'AND '.$this->table_tree.'.'.$this->tree_pk.' = %s ';

			$res = $ilDB->queryF($query,array('integer','integer','integer'),array(
				$a_node['lft'],
				$a_node['parent'],
				$this->tree_id));
			
		}
		$row = $ilDB->fetchAssoc($res);
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
		global $ilDB;
		
		$query = 'SELECT child FROM '.$this->table_tree.' '.
			'WHERE parent = %s '.
			'AND '.$this->tree_pk.' = %s ';
		$res = $ilDB->queryF($query,array('integer','integer'),array(
			0,
			$this->tree_id));
		$row = $ilDB->fetchObject($res);
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
		global $ilDB;
		
		if (!isset($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getNodeData(): No node_id given! ",$this->ilErr->WARNING);
		}

		// get lft value for current node
		$query = 'SELECT lft FROM '.$this->table_tree.' '.
			'WHERE '.$this->table_tree.'.child = %s '.
			'AND '.$this->table_tree.'.'.$this->tree_pk.' = %s ';
		$res = $ilDB->queryF($query,array('integer','integer'),array(
			$a_node_id,
			$this->tree_id));
		$curr_node = $ilDB->fetchAssoc($res);
		
		if($a_type)
		{
			$query = 'SELECT * FROM '.$this->table_tree.' '.
				$this->buildJoin().
				'WHERE lft > %s '.
				'AND '.$this->table_obj_data.'.type = %s '.
				'AND '.$this->table_tree.'.'.$this->tree_pk.' = %s '.
				'ORDER BY lft ';
			$ilDB->setLimit(1);
			$res = $ilDB->queryF($query,array('integer','text','integer'),array(
				$curr_node['lft'],
				$a_type,
				$this->tree_id));
		}
		else
		{
			$query = 'SELECT * FROM '.$this->table_tree.' '.
				$this->buildJoin().
				'WHERE lft > %s '.
				'AND '.$this->table_tree.'.'.$this->tree_pk.' = %s '.
				'ORDER BY lft ';
			$ilDB->setLimit(1);
			$res = $ilDB->queryF($query,array('integer','integer'),array(
				$curr_node['lft'],
				$this->tree_id));
		}

		if ($res->numRows() < 1)
		{
			return false;
		}
		else
		{
			$row = $ilDB->fetchAssoc($res);
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
		global $ilDB;
		
		if (!isset($a_node_id))
		{
			$this->ilErr->raiseError(get_class($this)."::getNodeData(): No node_id given! ",$this->ilErr->WARNING);
		}

		// get lft value for current node
		$query = 'SELECT lft FROM '.$this->table_tree.' '.
			'WHERE '.$this->table_tree.'.child = %s '.
			'AND '.$this->table_tree.'.'.$this->tree_pk.' = %s ';
		$res = $ilDB->queryF($query,array('integer','integer'),array(
			$a_node_id,
			$this->tree_id));

		$curr_node = $ilDB->fetchAssoc($res);
		
		if($a_type)
		{
			$query = 'SELECT * FROM '.$this->table_tree.' '.
				$this->buildJoin().
				'WHERE lft < %s '.
				'AND '.$this->table_obj_data.'.type = %s '.
				'AND '.$this->table_tree.'.'.$this->tree_pk.' = %s '.
				'ORDER BY lft DESC';
			$ilDB->setLimit(1);
			$res = $ilDB->queryF($query,array('integer','text','integer'),array(
				$curr_node['lft'],
				$a_type,
				$this->tree_id));
		}
		else
		{
			$query = 'SELECT * FROM '.$this->table_tree.' '.
				$this->buildJoin().
				'WHERE lft < %s '.
				'AND '.$this->table_tree.'.'.$this->tree_pk.' = %s '.
				'ORDER BY lft DESC';
			$ilDB->setLimit(1);
			$res = $ilDB->queryF($query,array('integer','integer'),array(
				$curr_node['lft'],
				$this->tree_id));
		}
		
		if ($res->numRows() < 1)
		{
			return false;
		}
		else
		{
			$row = $ilDB->fetchAssoc($res);
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
		global $ilDB;
		
		// LOCKED ###################################
		if($this->__isMainTree())
		{
			/*
			ilDB::_lockTables(array($this->table_tree => 'WRITE',
									 $this->table_obj_data => 'WRITE',
									 $this->table_obj_reference => 'WRITE',
									 'object_translation' => 'WRITE',
									 'object_data od' => 'WRITE',
									 'container_reference cr' => 'WRITE'));
			*/	
			$ilDB->lockTables(
				array(			
					0 => array('name' => $this->table_tree, 'type' => ilDB::LOCK_WRITE),
					1 => array('name' => $this->table_obj_data, 'type' => ilDB::LOCK_WRITE),
					2 => array('name' => $this->table_obj_reference, 'type' => ilDB::LOCK_WRITE),
					3 => array('name' => 'object_translation', 'type' => ilDB::LOCK_WRITE),
					4 => array('name' => 'object_data', 'type' => ilDB::LOCK_WRITE, 'alias' => 'od'),
					5 => array('name' => 'container_reference', 'type' => ilDB::LOCK_WRITE, 'alias' => 'cr')
				));
		}
		$return = $this->__renumber($node_id,$i);
		if($this->__isMainTree())
		{
			$ilDB->unlockTables();
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
		global $ilDB;
		
		$query = 'UPDATE '.$this->table_tree.' SET lft = %s WHERE child = %s';
		$res = $ilDB->manipulateF($query,array('integer','integer'),array(
			$i,
			$node_id));

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
		
		
		$query = 'UPDATE '.$this->table_tree.' SET rgt = %s WHERE child = %s';
		$res = $ilDB->manipulateF($query,array('integer','integer'),array(
			$i,
			$node_id));
		return $i;
	}


	/**
	* Check for parent type
	* e.g check if a folder (ref_id 3) is in a parent course obj => checkForParentType(3,'crs');
	*
 	* @access	public
	* @param	integer	ref_id
	* @param	string type
	* @return	mixed false if item is not in tree, 
	* 				  int (object ref_id) > 0 if path container course, int 0 if pathc does not contain the object type 
	*/
	function checkForParentType($a_ref_id,$a_type,$a_exclude_source_check = false)
	{				
		// #12577
		$cache_key = $a_ref_id.'.'.$a_type.'.'.((int)$a_exclude_source_check);
		
		// Try to return a cached result
		if($this->isCacheUsed() &&
			array_key_exists($cache_key, $this->parent_type_cache)) 
		{			
			return $this->parent_type_cache[$cache_key];
		}
		
		// Store up to 1000 results in cache
		$do_cache = ($this->__isMainTree() && count($this->parent_type_cache) < 1000);

		// ref_id is not in tree
		if(!$this->isInTree($a_ref_id))
		{
            if($do_cache) 
			{
                $this->parent_type_cache[$cache_key] = false;
            }
			return false;
		}
		
		$path = array_reverse($this->getPathFull($a_ref_id));

		// remove first path entry as it is requested node
		if($a_exclude_source_check)
		{
			array_shift($path);
		}

		foreach($path as $node)
		{
			// found matching parent
			if($node["type"] == $a_type)
			{
				if($do_cache) 
				{
					$this->parent_type_cache[$cache_key] = $node["child"];
				}
				return $node["child"];
			}
		}
		
		if($do_cache)
		{
			$this->parent_type_cache[$cache_key] = false;
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
		
		$query = 'DELETE FROM '.$a_db_table.' '.
			'WHERE tree = %s '.
			'AND child = %s ';
		$res = $ilDB->manipulateF($query,array('integer','integer'),array(
			$a_tree,
			$a_child));
		
	}
	
	/**
	* Check if operations are done on main tree
	*
 	* @access	private
	* @return boolean
	*/
	public function __isMainTree()
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
	 * 
	 * @deprecated since 4.4.0
	*/
	function __checkDelete($a_node)
	{
		global $ilDB;
		
		
		$query = $this->getTreeImplementation()->getSubTreeQuery($a_node, array(),false);
		$GLOBALS['ilLog']->write(__METHOD__.': '.$query);
		$res = $ilDB->query($query);
		
		$counter = (int) $lft_childs = array();
		while($row = $ilDB->fetchObject($res))
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

	/**
	 * 
	 * @global type $ilDB
	 * @param type $a_node_id
	 * @param type $parent_childs
	 * @return boolean
	 * @deprecated since 4.4.0
	 */
	function __getSubTreeByParentRelation($a_node_id,&$parent_childs)
	{
		global $ilDB;
		
		// GET PARENT ID
		$query = 'SELECT * FROM '.$this->table_tree.' '.
			'WHERE child = %s '.
			'AND tree = %s ';
		$res = $ilDB->queryF($query,array('integer','integer'),array(
			$a_node_id,
			$this->tree_id));

		$counter = 0;
		while($row = $ilDB->fetchObject($res))
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
		$query = 'SELECT * FROM '.$this->table_tree.' '.
			'WHERE parent = %s ';
		$res = $ilDB->queryF($query,array('integer'),array($a_node_id));

		while($row = $ilDB->fetchObject($res))
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

		$GLOBALS['ilLog']->write(__METHOD__.': left childs '. print_r($lft_childs,true));
		$GLOBALS['ilLog']->write(__METHOD__.': parent childs '. print_r($parent_childs,true));

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
	
	/**
	 * Move Tree Implementation
	 * 
	 * @access	public
	 * @param int source ref_id
	 * @param int target ref_id
	 * @param int location IL_LAST_NODE or IL_FIRST_NODE (IL_FIRST_NODE not implemented yet)
	 *
	 */
	public function moveTree($a_source_id, $a_target_id, $a_location = self::POS_LAST_NODE)
	{
		$this->getTreeImplementation()->moveTree($a_source_id,$a_target_id,$a_location);
		$GLOBALS['ilAppEventHandler']->raise(
				"Services/Tree", 
				"moveTree", 
				array(
					'tree'		=> $this->table_tree,
					'source_id' => $a_source_id, 
					'target_id' => $a_target_id)
		);
		return true;
	}
	
	
	
	
	/**
	 * This method is used for change existing objects 
	 * and returns all necessary information for this action.
	 * The former use of ilTree::getSubtree needs to much memory.
	 * @param ref_id ref_id of source node 
	 * @return 
	 */
	public function getRbacSubtreeInfo($a_endnode_id)
	{
		return $this->getTreeImplementation()->getSubtreeInfo($a_endnode_id);
	}
	

	/**
	 * Get tree subtree query
	 * @param type $a_node_id
	 * @param type $a_types
	 * @param type $a_force_join_reference
	 * @return type
	 */
	public function getSubTreeQuery($a_node_id,$a_fields = array(), $a_types = '', $a_force_join_reference = false)
	{
		return $this->getTreeImplementation()->getSubTreeQuery(
				$this->getNodeTreeData($a_node_id),
				$a_types, 
				$a_force_join_reference, 
				$a_fields);
	}
} // END class.tree
?>
