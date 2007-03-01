<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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
* 
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* 
* @ingroup ServicesCopyWizard 
*/

class ilCopyWizardOptions
{
	private static $instances = null;
	
	const COPY_WIZARD_OMIT = 1;
	const COPY_WIZARD_COPY = 2;
	const COPY_WIZARD_LINK = 3;
	
	private $db;
	
	private $copy_id;
	private $source_id;
	private $options = array();	
	
	/**
	 * Private Constructor (Singleton class)
	 *
	 * @access private
	 * @param int copy_id
	 * 
	 */
	private function __construct($a_copy_id = 0)
	{
		global $ilDB;
		
		$this->db = $ilDB;
		$this->copy_id = $a_copy_id;
		
		if($this->copy_id)
		{
			$this->read();
		}	
	}
	
	/**
	 * Get instance of copy wizard options
	 *
	 * @access public
	 * @static
	 *
	 * @param int copy id
	 */
	public static function _getInstance($a_copy_id)
	{
		if(is_array(self::$instances) and isset(self::$instances[$a_copy_id]))
		{
			return self::$instances[$a_copy_id];
		}
		return self::$instances[$a_copy_id] = new ilCopyWizardOptions($a_copy_id);
	}
	
	/**
	 * Allocate a copy for further entries
	 *
	 * @access public
	 * @static
	 * 
	 */
	public static function _allocateCopyId()
	{
		global $ilDB;
		
	 	$query = "SELECT MAX(copy_id) as latest FROM copy_wizard_options ";
	 	$res = $ilDB->query($query);
	 	$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
	 	
	 	$query = "INSERT INTO copy_wizard_options ".
	 		"SET copy_id = ".$ilDB->quote($row->latest + 1);
	 	$ilDB->query($query);
	 	
	 	return $row->latest + 1;
	}
	
	/**
	 * Save owner for copy. It will be checked against this user id in all soap calls
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function saveOwner($a_user_id)
	{
	 	$query = "INSERT INTO copy_wizard_options ".
	 		"SET copy_id = ".$this->db->quote($this->getCopyId()).", ".
	 		"source_id = ".$this->db->quote(-3).", ".
	 		"options = '".addslashes(serialize(array($a_user_id)))."'";
	 	$this->db->query($query);
		return true;
	}
	
	/**
	 * check owner 
	 *
	 * @access public
	 * @param int user_id
	 * 
	 */
	public function checkOwner($a_user_id)
	{
	 	return in_array($a_user_id,$this->getOptions(-3));
	}
	
	/**
	 * Get copy id
	 *
	 * @access public
	 * 
	 */
	public function getCopyId()
	{
	 	return $this->copy_id;
	}
	
	
	/**
	 * Init container
	 * Add copy entry
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function initContainer($a_source_id,$a_target_id)
	{
		global $tree;
		
		$mapping_source = $tree->getParentId($a_source_id);
	 	$this->addEntry($a_source_id,array('type' => ilCopyWizardOptions::COPY_WIZARD_COPY));
	 	$this->appendMapping($mapping_source,$a_target_id);
	}
	
	/**
	 * Save tree 
	 * Stores two copies of the tree structure:
	 * id 0 is used for recursive call of cloneObject()
	 * id -1 is used for recursive call of cloneDependencies()
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function storeTree($a_tree_structure)
	{
	 	$query = "UPDATE copy_wizard_options ".
			"SET options = '".addslashes(serialize($a_tree_structure))."' ".
			"WHERE copy_id = ".$this->db->quote($this->copy_id)." ".
			"AND source_id = 0 ";
		$res = $this->db->query($query);

	 	$query = "INSERT INTO copy_wizard_options ".
			"SET options = '".addslashes(serialize($a_tree_structure))."', ".
			"copy_id = ".$this->db->quote($this->copy_id).", ".
			"source_id = -1 ";
		$res = $this->db->query($query);
		return true; 
	}
	
	/**
	 * Get first node of stored tree
	 *
	 * @access private
	 * 
	 */
	private function fetchFirstNodeById($a_id)
	{
		$tree = $this->getOptions($a_id);
		if(isset($tree[0]) and is_array($tree[0]))
		{
			return $tree[0];
		}
		return false;
	}
	
	/**
	 * Fetch first node for cloneObject
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function fetchFirstNode()
	{
	 	return $this->fetchFirstNodeById(0);
	}
	
	/**
	 * Fetch first dependencies node
	 *
	 * @access public
	 * 
	 */
	public function fetchFirstDependenciesNode()
	{
	 	return $this->fetchFirstNodeById(-1);
	}
	
	/**
	 * Drop first node by id
	 *
	 * @access private
	 * 
	 */
	public function dropFirstNodeById($a_id)
	{
		if(!isset($this->options[$a_id]) or !is_array($this->options[$a_id]))
		{
			return false;
		}
		
		$this->options[$a_id] = array_slice($this->options[$a_id],1);
		$query = "UPDATE copy_wizard_options ".
			"SET options = '".addslashes(serialize($this->options[$a_id]))."' ".
			"WHERE copy_id = ".$this->db->quote($this->copy_id)." ".
			"AND source_id = ".$this->db->quote($a_id)." ";
			;
		$this->db->query($query);
		$this->read();
		// check for role_folder
		if(($node = $this->fetchFirstNodeById($a_id)) === false)
		{
			return true;
		}
		if($node['type'] == 'rolf')
		{
			$this->dropFirstNodeById($a_id);
		}
		return true;
	}
	
	/**
	 * Drop first node (for cloneObject())
	 *
	 * @access public
	 * 
	 */
	public function dropFirstNode()
	{
	 	return $this->dropFirstNodeById(0);
	}
	
	/**
	 * Drop first node (for cloneDependencies())
	 *
	 * @access public
	 * 
	 */
	public function dropFirstDependenciesNode()
	{
	 	return $this->dropFirstNodeById(-1);
	}
	
	/**
	 * Get entry by source
	 *
	 * @access public
	 * @param int source ref_id
	 * 
	 */
	public function getOptions($a_source_id)
	{
		if(isset($this->options[$a_source_id]) and is_array($this->options[$a_source_id]))
		{
			return $this->options[$a_source_id];
		}
		return array();
	}
	
	/**
	 * Add new entry
	 *
	 * @access public
	 * @param int ref_id of source
	 * @param array array of options
	 * 
	 */
	public function addEntry($a_source_id,$a_options)
	{
		if(!is_array($a_options))
		{
			return false;
		}
		
		$query = "DELETE FROM copy_wizard_options ".
			"WHERE copy_id = ".$this->db->quote($this->copy_id)." ".
			"AND source_id = ".$this->db->quote($a_source_id);
		$this->db->query($query);

		$query 	= "INSERT INTO copy_wizard_options ".
			"SET copy_id = ".$this->db->quote($this->copy_id).", ".
			"source_id = ".$this->db->quote($a_source_id).", ".
			"options = '".addslashes(serialize($a_options))."' ";
		$res = $this->db->query($query);
		return true;
	}
	
	/**
	 * Add mapping of source -> target
	 *
	 * @access public
	 * @param int source ref_id
	 * @param int target ref_id
	 * 
	 */
	public function appendMapping($a_source_id,$a_target_id)
	{
		$query = "SELECT * FROM copy_wizard_options ".
			"WHERE copy_id = ".$this->db->quote($this->copy_id)." ".
			"AND source_id = -2 ";
		$res = $this->db->query($query);
		$mappings = array();
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$mappings = unserialize(stripslashes($row->options));
		}
		$mappings[$a_source_id] = $a_target_id;
		
		$query = "REPLACE INTO copy_wizard_options ".
			"SET copy_id = ".$this->db->quote($this->copy_id).", ".
			"source_id = -2, ".
			"options = '".addslashes(serialize($mappings))."'";
		$this->db->query($query);
		return true;				
	}
	
	/**
	 * Get Mappings
	 *
	 * @access public
	 * 
	 */
	public function getMappings()
	{
	 	if(isset($this->options[-2]) and is_array($this->options[-2]))
	 	{
	 		return $this->options[-2];
	 	}
	 	return array();
	}
	
	/**
	 * Delete all entries
	 *
	 * @access public
	 * 
	 */
	public function deleteAll()
	{
	 	$query = "DELETE FROM copy_wizard_options ".
	 		"WHERE copy_id = ".$this->db->quote($this->copy_id);
	 	$this->db->query($query);
	}
	
	/**
	 * 
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function read()
	{
	 	$query = "SELECT * FROM copy_wizard_options ".
	 		"WHERE copy_id = ".$this->db->quote($this->copy_id);
	 	$res = $this->db->query($query);
	 	
	 	$this->options = array();
	 	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	 	{
	 		$this->options[$row->source_id] = unserialize(stripslashes($row->options));
	 	}

		return true;
	}
}


?>