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
* Class ilObjFolder
*
* @author Wolfgang Merkens <wmerkens@databay.de> 
* @version $Id$
*
* @extends ilObject
* @package ilias-core
*/

require_once "class.ilObject.php";
require_once "class.ilGroupTree.php";

class ilObjFolder extends ilObject
{
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjFolder($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "fold";
		$this->ilObject($a_id,false);
	}
	
	/**
	* insert folder into grp_tree
	*
	*/
	function putInTree($a_parent_ref)
	{
		$grp_id = $this->getGroupId($a_parent_ref);
		
		$gtree = new ilGroupTree($grp_id);
		
		$gtree->insertNode($this->getRefId(), $a_parent_ref);
	}
	
	/**
	* get the tree_id of group where folder belongs to
	* TODO: function is also in ilGroupGUI and ilObjFile. merge them!!
	* @param	string	ref_id of parent under which folder is inserted
	* @access	private
	*/
	function getGroupId($a_parent_ref = 0)
	{
		if ($a_parent_ref == 0)
		{
			$a_parent_ref = $this->getRefId();
		}
		
		$q = "SELECT DISTINCT tree FROM grp_tree WHERE child='".$a_parent_ref."'";
		$r = $this->ilias->db->query($q);
		$row = $r->fetchRow();
		
		return $row[0];
	}
} // END class.ilObjFolder
?>
