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

require_once ("classes/class.ilTree.php");

/**
* SCORM Object Tree
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @extends ilSCORMTree
* @package content
*/
class ilSCORMTree extends ilTree
{

	/**
	* Constructor
	*
	* @param	int		$a_id		tree id (= SCORM Learning Module Object ID)
	* @access	public
	*/
	function ilSCORMTree($a_id = 0)
	{
		parent::ilTree($a_id);
		$this->setTableNames('scorm_tree','scorm_object');
		$this->setTreeTablePK('slm_id');
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
	
		$r = $this->ilDB->query($q);

		$count = $r->numRows();
		

		if ($count > 0)
		{
			while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
			{
//				echo "lese data-----------------<br>";
				$data=$this->fetchNodeData($row);
/*				
				if ($data["child"]==3 && $data["parent"]==0) {
					$data2=$data;
					
					
					$data2["child"]=2;
					$data2["parent"]=0;
					$data2["obj_id"]=2;
					
					$data["parent"]=2;
					$data["depth"]=2;
					
					$childs[]=$data2;
					
					foreach($data2 as $k=>$v)
						echo " $k=>$v ";
					echo "<br>";
				}
				
				foreach($data as $k=>$v)
					echo " $k=>$v ";
				echo "<br>";
*/				
				$childs[] = $data;
				
			}

			// mark the last child node (important for display)
			$childs[$count - 1]["last"] = true;

			return $childs;
		}
		else
		{
			return $childs;
		}
	}
}
?>
