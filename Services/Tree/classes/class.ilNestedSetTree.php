<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Tree/interfaces/interface.ilTreeImplementation.php';

/**
 * Base class for materialize path based trees
 * 
 * 
 * @author Stefan Meyer <meyer@leifos.com>
 * @version $Id$

 * @ingroup ServicesTree
 * 
 */
class ilNestedSetTree implements ilTreeImplementation
{
	private $tree = NULL;
	
	/**
	 * Constructor
	 * @param ilTree $tree
	 */
	public function __construct(ilTree $a_tree)
	{
		$this->tree = $a_tree;
	}

	/**
	 * Get tree object
	 * @return ilTree $tree
	 */
	public function getTree()
	{
		return $this->tree;
	}
	
	/**
	 * Get subtree ids
	 * @param type $a_node_id
	 */
	public function getSubTreeIds($a_node_id)
	{
		global $ilDB;
		
		$query = 'SELECT s.child FROM '.
			$this->getTree()->getTreeTable().' s, '.
			$this->getTree()->getTreeTable().' t '. 
			'WHERE t.child = %s '.
			'AND s.lft > t.lft '.
			'AND s.rgt < t.rgt '.
			'AND s.'.$this->getTree()->getTreePk().' = %s';
		
		$res = $ilDB->queryF(
			$query, 
			array('integer','integer'),
			array($a_node_id,$this->getTree()->getTreeId())
		);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$childs[] = $row->child;
		}
		return $childs ? $childs : array();
	}
	
	/**
	 * Get subtree
	 * @param type $a_node
	 * @param type $a_with_data
	 * @param type $a_types
	 */
	public function getSubTreeQuery($a_node, $a_types = '', $a_force_join_reference = true)
	{
		global $ilDB;
		
		$type_str = '';
		if (is_array($a_types))
		{
			if($a_types)
			{
				$type_str = "AND ".$ilDB->in($this->getTree()->getObjectDataTable().".type", $a_types, false, "text");
			}
		}
		else if(strlen($a_types))
		{
			$type_str = "AND ".$this->getTree()->getObjectDataTable().".type = ".$ilDB->quote($a_types, "text");
		}
		
		$join = '';
		if($type_str or $a_force_join_reference)
		{
			$join = $this->getTree()->buildJoin();
		}
		
		$query = "SELECT * FROM ".$this->getTree()->getTreeTable()." ".
			$join.' '.
			"WHERE ".$this->getTree()->getTreeTable().'.lft '.
			'BETWEEN '.$ilDB->quote($a_node['lft'],'integer').' '.
			'AND '.$ilDB->quote($a_node['rgt'],'integer').' '.
			"AND ".$this->getTree()->getTreeTable().".".$this->getTree()->getTreePk()." = ".$ilDB->quote($this->getTree()->getTreeId(),'integer').' '.
			$type_str.
			"ORDER BY ".$this->getTree()->getTreeTable().".lft";
		
		$GLOBALS['ilLog']->write(__METHOD__.'-----------------: '. $query);
		
		return $query;
	}
	

	/**
	 * Get relation
	 * @param type $a_node_a
	 * @param type $a_node_b
	 */
	public function getRelation($a_node_a, $a_node_b)
	{
		$node_a = $this->getTree()->getNodeData($a_node_a);
		$node_b = $this->getTree()->getNodeData($a_node_b);
		
		if($node_a['lft'] < $node_b['lft'] and $node_a['rgt'] > $node_b['rgt'])
		{
			return ilTree::RELATION_PARENT;
		}
		if($node_b['lft'] < $node_a['lft'] and $node_b['rgt'] > $node_a['rgt'])
		{
			return ilTree::RELATION_CHILD;
		}
		
		// if node is also parent of node b => sibling
		if($node_a['parent'] == $node_b['parent'])
		{
			return ilTree::RELATION_SIBLING;
		}
		return ilTree::RELATION_NONE;
	}
	
	/**
	 * Get path ids
	 * @param int $a_endnode
	 * @param int $a_startnode
	 */
	public function getPathIds($a_endnode, $a_startnode = 0)
	{
		return $this->getPathIdsUsingAdjacencyMap($a_endnode, $a_startnode);
	}
	

	/**
	 * Delete a subtree
	 * @param type $a_node_id
	 */
	public function deleteTree($a_node_id)
	{
		global $ilDB;
		
		// LOCKED ###########################################################
		// get lft and rgt values. Don't trust parameter lft/rgt values of $a_node
		if($this->getTree()->__isMainTree())
		{
			$ilDB->lockTables(
				array(
					0 => array('name' => 'tree', 'type' => ilDB::LOCK_WRITE)));
		}

		// Fetch lft, rgt directly (without fetchNodeData) to avoid unnecessary table locks
		// (object_reference, object_data)
		$query = 'SELECT *  FROM '.$this->getTree()->getTreeTable().' '.
				'WHERE child = '.$ilDB->quote($a_node_id,'integer');
		$res = $ilDB->query($query);
		$a_node = $res->fetchRow(DB_FETCHMODE_ASSOC);
		
		$GLOBALS['ilLog']->write(__METHOD__.' '.print_r($a_node,true));
		

		// delete subtree
		$query = sprintf('DELETE FROM '.$this->getTree()->getTreeTable().' '.
			'WHERE lft BETWEEN %s AND %s '.
			'AND rgt BETWEEN %s AND %s '.
			'AND '.$this->getTree()->getTreePk().' = %s',
			$ilDB->quote($a_node['lft'],'integer'),
			$ilDB->quote($a_node['rgt'],'integer'),
			$ilDB->quote($a_node['lft'],'integer'),
			$ilDB->quote($a_node['rgt'],'integer'),
			$ilDB->quote($a_node[$this->getTree()->getTreePk()],'integer'));
		$res = $ilDB->manipulate($query);
			
        // Performance improvement: We only close the gap, if the node 
        // is not in a trash tree, and if the resulting gap will be 
        // larger than twice the gap value 

		$diff = $a_node["rgt"] - $a_node["lft"] + 1;
		if(
			$a_node[$this->getTree()->getTreePk()] >= 0 && 
			$a_node['rgt'] - $a_node['lft'] >= $this->getTree()->getGap() * 2
		)
		{
			// close gaps
			$query = sprintf('UPDATE '.$this->getTree()->getTreeTable().' SET '.
				'lft = CASE WHEN lft > %s THEN lft - %s ELSE lft END, '.
				'rgt = CASE WHEN rgt > %s THEN rgt - %s ELSE rgt END '.
				'WHERE '.$this->getTree()->getTreePk().' = %s ',
				$ilDB->quote($a_node['lft'],'integer'),
				$ilDB->quote($diff,'integer'),
				$ilDB->quote($a_node['lft'],'integer'),
				$ilDB->quote($diff,'integer'),
				$ilDB->quote($a_node[$this->getTree()->getTreePk()],'integer'));
				
			$res = $ilDB->manipulate($query);
		}

		if($this->getTree()->__isMainTree())
		{
			$ilDB->unlockTables();
		}
		// LOCKED ###########################################################
		return true;
	}
	
	/**
	 * Move to trash
	 * @param type $a_node_id
	 */
	public function moveToTrash($a_node_id)
	{
		global $ilDB;

		$node = $this->getTree()->getNodeTreeData($a_node_id);

		$query = 'UPDATE '.$this->getTree()->getTreeTable().' '.
			'SET tree = '.$ilDB->quote(-1 * $node['child'],'integer').' '.
			'WHERE '.$this->getTree()->getTreePk().' =  '.$ilDB->quote($this->getTree()->getTreeId(),'integer').' '.
			'AND lft BETWEEN '.$ilDB->quote($node['lft'],'integer').' AND '.$ilDB->quote($node['rgt'],'integer').' ';

		$ilDB->manipulate($query);
		return true;
	}


	/**
	* get path from a given startnode to a given endnode
	* if startnode is not given the rootnode is startnode
	* @access	public
	* @param	integer		node_id of endnode
	* @param	integer		node_id of startnode (optional)
	* @return	array		all path ids from startnode to endnode
	*/
	protected function getPathIdsUsingAdjacencyMap($a_endnode_id, $a_startnode_id = 0)
	{
		global $ilDB;

		// The adjacency map algorithm is harder to implement than the nested sets algorithm.
		// This algorithms performs an index search for each of the path element.
		// This algorithms performs well for large trees which are not deeply nested.

		// The $takeId variable is used, to determine if a given id shall be included in the path
		$takeId = $a_startnode_id == 0;
		
		$depth_cache = $this->getTree()->getDepthCache();
		$parent_cache = $this->getTree()->getParentCache();
		
		if(
			$this->getTree()->__isMainTree() && 
			isset($depth_cache[$a_endnode_id]) &&
			isset($parent_cache[$a_endnode_id]))
		{
			$nodeDepth = $depth_cache[$a_endnode_id];
			$parentId = $parent_cache[$a_endnode_id];
		}
		else
		{
			$nodeDepth = $this->getTree()->getDepth($a_endnode_id);
			$parentId = $this->getTree()->getParentId($a_endnode_id);
		}

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
				$takeId = $takeId || $this->getTree()->getRootId() == $a_startnode_id;
				if ($takeId) $pathIds[] = $this->getTree()->getRootId();
				$takeId = $takeId || $parentId == $a_startnode_id;
				if ($takeId) $pathIds[] = $parentId;
				$takeId = $takeId || $a_endnode_id == $a_startnode_id;
				if ($takeId) $pathIds[] = $a_endnode_id;
		}
		else if ($nodeDepth < 32)
		{
			// Adjacency Map Tree performs better than
			// Nested Sets Tree even for very deep trees.
			// The following code construct nested self-joins
			// Since we already know the root-id of the tree and
			// we also know the id and parent id of the current node,
			// we only need to perform $nodeDepth - 3 self-joins. 
			// We can further reduce the number of self-joins by 1
			// by taking into account, that each row in table tree
			// contains the id of itself and of its parent.
			$qSelect = 't1.child c0';
			$qJoin = '';
			for ($i = 1; $i < $nodeDepth - 2; $i++)
			{
				$qSelect .= ', t'.$i.'.parent c'.$i;
				$qJoin .= ' JOIN '.$this->getTree()->getTreeTable().' t'.$i.' ON '.
							't'.$i.'.child=t'.($i - 1).'.parent AND '.
							't'.$i.'.'.$this->getTree()->getTreePk().' = '.(int) $this->getTree()->getTreeId();
			}
			
			$types = array('integer','integer');
			$data = array($this->getTree()->getTreeId(),$parentId);
			$query = 'SELECT '.$qSelect.' '.
				'FROM '.$this->getTree()->getTreeTable().' t0 '.$qJoin.' '.
				'WHERE t0.'.$this->getTree()->getTreePk().' = %s '.
				'AND t0.child = %s ';
				
			$ilDB->setLimit(1);
			$res = $ilDB->queryF($query,$types,$data);

			if ($res->numRows() == 0)
			{
				return array();
			}
			
			$row = $ilDB->fetchAssoc($res);
			
			$takeId = $takeId || $this->getTree()->getRootId() == $a_startnode_id;
			if ($takeId) $pathIds[] = $this->getTree()->getRootId();
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
			// Fall back to nested sets tree for extremely deep tree structures
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
	public function getPathIdsUsingNestedSets($a_endnode_id, $a_startnode_id = 0)
	{
		global $ilDB;
		
		// The nested sets algorithm is very easy to implement.
		// Unfortunately it always does a full table space scan to retrieve the path
		// regardless whether indices on lft and rgt are set or not.
		// (At least, this is what happens on MySQL 4.1).
		// This algorithms performs well for small trees which are deeply nested.
		
		$fields = array('integer','integer','integer');
		$data = array($a_endnode_id,$this->getTree()->getTreeId(),$this->getTree()->getTreeId());
		
		$query = "SELECT T2.child ".
			"FROM ".$this->getTree()->getTreeTable()." T1, ".$this->getTree()->getTreeTable()." T2 ".
			"WHERE T1.child = %s ".
			"AND T1.lft BETWEEN T2.lft AND T2.rgt ".
			"AND T1.".$this->getTree()->getTreePk()." = %s ".
			"AND T2.".$this->getTree()->getTreePk()." = %s ".
			"ORDER BY T2.depth";

		$res = $ilDB->queryF($query,$fields,$data);
		
		$takeId = $a_startnode_id == 0;
		while($row = $ilDB->fetchAssoc($res))
		{
			if ($takeId || $row['child'] == $a_startnode_id)
			{
				$takeId = true;
				$pathIds[] = $row['child'];
			}
		}
		return $pathIds ? $pathIds : array();
	}
}
?>
