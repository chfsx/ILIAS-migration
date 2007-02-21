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
	const COPY_WIZARD_OMIT = 1;
	const COPY_WIZARD_COPY = 2;
	const COPY_WIZARD_LINK = 3;
	
	private $db;
	
	private $copy_id;
	private $source_id;
	private $options = array();	
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function __construct($a_copy_id = 0)
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
	 * Allocate a copy for further entries
	 *
	 * @access public
	 * 
	 */
	public function allocateCopyId()
	{
	 	$query = "SELECT MAX(copy_id) as latest FROM copy_wizard_options ";
	 	$res = $this->db->query($query);
	 	$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
	 	
	 	$query = "INSERT INTO copy_wizard_options ".
	 		"SET copy_id = ".$this->db->quote($row->latest + 1);
	 	$this->db->query($query);
	 	
	 	return $this->copy_id = $row->latest + 1;
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
		return true; 
	}
	
	/**
	 * Get first node of stored tree
	 *
	 * @access public
	 * 
	 */
	public function fetchFirstNode()
	{
		$tree = $this->getOptions(0);
		if(isset($tree[0]) and is_array($tree[0]))
		{
			return $tree[0];
		}
		return false;
	}
	
	/**
	 * Drop first node
	 *
	 * @access public
	 * 
	 */
	public function dropFirstNode()
	{
		if(!isset($this->options[0]) or !is_array($this->options[0]))
		{
			return false;
		}
		
		$this->options[0] = array_slice($this->options[0],1);
		$query = "UPDATE copy_wizard_options ".
			"SET options = '".addslashes(serialize($this->options[0]))."' ".
			"WHERE copy_id = ".$this->db->quote($this->copy_id)." ".
			"AND source_id = 0";
		$this->db->query($query);
		$this->read();
		// check for role_folder
		if(($node = $this->fetchFirstNode()) === false)
		{
			return true;
		}
		if($node['type'] == 'rolf')
		{
			$this->dropFirstNode();
		}
		return true;
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
			"AND source_id = -1 ";
		$res = $this->db->query($query);
		$mappings = array();
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$mappings = unserialize(stripslashes($row->options));
		}
		$mappings[$a_source_id] = $a_target_id;
		
		$query = "REPLACE INTO copy_wizard_options ".
			"SET copy_id = ".$this->db->quote($this->copy_id).", ".
			"source_id = -1, ".
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
	 	if(isset($this->options[-1]) and is_array($this->options[-1]))
	 	{
	 		return $this->options[-1];
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