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

define("IL_CDF_SORT_ID",'field_id');
define("IL_CDF_SORT_NAME",'field_name');

define('IL_CDF_TYPE_TEXT',1);
define('IL_CDF_TYPE_SELECT',2);


/** 
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* 
* @ingroup Modules/Course 
*/
class ilCourseDefinedFieldDefinition
{
	private $db;
	private $obj_id;

	private $id;
	private $name;
	private $type;
	private $values;
	private $required;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param int course obj_id
	 * @param int field_id
	 * 
	 */
	public function __construct($a_obj_id,$a_field_id = 0)
	{
	 	global $ilDB;
	 	
	 	$this->db = $ilDB;
	 	$this->obj_id = $a_obj_id;
	 	$this->id = $a_field_id;
	 	
	 	if($this->id)
	 	{
	 		$this->read();
	 	}
	}
	
	/**
	 * Delete all fields of a container
	 *
	 * @access public
	 * @static
	 * @param int container_id
	 * 
	 */
	public static function _deleteByContainer($a_container_id)
	{
	 	global $ilDB;
	 	
	 	// Delete user entries
	 	include_once('Modules/Course/classes/Export/class.ilCourseUserData.php');
	 	foreach(ilCourseDefinedFieldDefinition::_getFieldIds($a_container_id) as $field_id)
	 	{
		 	ilCourseUserData::_deleteByField($field_id);
	 	}

	 	$query = "DELETE FROM crs_defined_field_definitions ".
	 		"WHERE obj_id = ".$ilDB->quote($a_container_id)." ";
	 	$ilDB->query($query);
	}
	
	/**
	 * Check if there are any define fields
	 *
	 * @access public
	 * @param int container_id 
	 */
	public static function _hasFields($a_container_id)
	{
		return count(ilCourseDefinedFieldDefinition::_getFields($a_container_id));
	}
	
	/**
	 * Get all fields of a container
	 *
	 * @access public
	 * @static
	 * @param int container obj_id
	 * @return array array of ilCourseDefinedFieldDefinitions objects
	 */
	public static function _getFields($a_container_id,$a_sort = IL_CDF_SORT_NAME)
	{
		foreach(ilCourseDefinedFieldDefinition::_getFieldIds($a_container_id,$a_sort) as $field_id)
	 	{
	 		$fields[] = new ilCourseDefinedFieldDefinition($a_container_id,$field_id);
	 	}
		return $fields ? $fields : array();	
	}
	
	/**
	 * Get required filed id's
	 *
	 * @access public
	 * @static
	 *
	 * @param int container id
	 */
	public static function _getRequiredFieldIds($a_obj_id)
	{
		global $ilDB;
		
		$query = "SELECT * FROM crs_defined_field_definitions ".
			"WHERE obj_id = ".$ilDB->quote($a_obj_id)." ".
			"AND field_required = 1";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$req_fields[] = $row->field_id;
		}
		return $req_fields ? $req_fields : array();
	}
	
	/**
	 * Fields to info string
	 *
	 * @access public
	 * @static
	 *
	 * @param int obj_id
	 */
	public static function _fieldsToInfoString($a_obj_id)
	{
		global $ilDB;
		
		
		$query = "SELECT field_name FROM crs_defined_field_definitions ".
			"WHERE obj_id = ".$ilDB->quote($a_obj_id);
		
		$res = $ilDB->query($query);
		$fields = array();
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$fields[] = $row->field_name;
		}
		return implode('<br />',$fields);		
	}
	
	/**
	 * Get all field ids of a container
	 *
	 * @access public
	 * @static
	 * @param int container obj_id
	 * @return array array of field ids
	 */
	public static function _getFieldIds($a_container_id,$a_sort = IL_CDF_SORT_ID)
	{
		global $ilDB;
		
	 	$query = "SELECT field_id FROM crs_defined_field_definitions ".
	 		"WHERE obj_id = ".$ilDB->quote($a_container_id)." ".
	 		"ORDER BY ".$a_sort;
	 	$res = $ilDB->query($query);
	 	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	 	{
	 		$field_ids[] = $row->field_id;
	 	}
		return $field_ids ? $field_ids : array();	
	}
		
	/**
	 * Lookup field name
	 *
	 * @access public
	 * @static
	 *
	 * @param int field_id
	 */
	public static function _lookupName($a_field_id)
	{
		global $ilDB;
		
		$query = "SELECT * FROM crs_defined_field_definitions ".
			"WHERE field_id = ".$ilDB->quote($a_field_id);
		
		$res = $ilDB->query($query);
		$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
		
		return $row->field_name ? $row->field_name : '';
	}
	
	public function getObjId()
	{
	 	return $this->obj_id;
	}
	public function getId()
	{
		return $this->id;
	}
	public function getType()
	{
		return $this->type;
	}
	public function setType($a_type)
	{
		$this->type = $a_type;
	}
	public function getName()
	{
		return $this->name;
	}
	public function setName($a_name)
	{
		$this->name = $a_name;
	}
	public function getValues()
	{
	 	return $this->values ? $this->values : array();
	}
	public function setValues($a_values)
	{
		$this->values = $a_values;
	}
	public function getValueById($a_id)
	{
	 	if(is_array($this->values) and array_key_exists($a_id,$this->values))
	 	{
	 		return $this->values[$a_id];
	 	}
	 	return '';
	}
	public function getIdByValue($a_value)
	{
		return ($pos = array_search($a_value,$this->values) === false) ? -1 : $pos;
	}
	
	public function isRequired()
	{
		return (bool) $this->required;
	}
	public function enableRequired($a_status)
	{
		$this->required = $a_status;
	}
	
	/**
	 * Prepare an array of options for ilUtil::formSelect()
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function prepareSelectBox()
	{
		global $lng;
		
		$options = array();
		$options[-1] = $lng->txt('select_one');
		
		foreach($this->values as $value)
		{
			$options[] = $value;
		}
		return $options;
	}
	
	/**
	 * Prepare values from POST
	 *
	 * @param array array of values
	 * @access public
	 */
	public function prepareValues($a_values)
	{
		$tmp_values = array();
		
		if(!is_array($a_values))
		{
			return false;
		}
		foreach($a_values as $value)
		{
			$value = trim(ilUtil::stripSlashes($value));
			if(strlen($value))
			{
				$tmp_values[] = $value;
			}
		}
		sort($tmp_values);
		return $tmp_values ? $tmp_values : array();
	}
	
	/**
	 * Append Values
	 *
	 * @access public
	 */
	public function appendValues($a_values)
	{
		if(!is_array($a_values))
		{
			return false;
		}
		$this->values = array_unique(array_merge($this->values,$a_values));
		sort($this->values);
		return true;
	}
	
	/**
	 * Delete value by id
	 *
	 * @access public
	 */
	public function deleteValue($a_id)
	{
		if(!isset($this->values[$a_id]))
		{
			return false;
		}
		unset($this->values[$a_id]);
		array_merge($this->values);
		$this->update();
		return true;
	}
	
	/**
	 * Save
	 *
	 * @access public
	 * 
	 */
	public function save()
	{
	 	$query = "INSERT INTO crs_defined_field_definitions ".
	 		"SET obj_id = ".$this->db->quote($this->getObjId()).", ".
	 		"field_name = ".$this->db->quote($this->getName()).", ".
	 		"field_type = ".$this->db->quote($this->getType()).", ".
	 		"field_values = '".addslashes(serialize($this->getValues()))."', ".
	 		"field_required = '".(int) $this->isRequired()."'";
	 	$res = $this->db->query($query);
	 	$this->id = $this->db->getLastInsertId();
			
		return true;			
	}
	
	/**
	 * Update a field
	 *
	 * @access public
	 */
	public function update()
	{
	 	$query = "UPDATE crs_defined_field_definitions ".
	 		"SET field_name = ".$this->db->quote($this->getName()).", ".
	 		"field_type = ".$this->db->quote($this->getType()).", ".
	 		"field_values = '".addslashes(serialize($this->getValues()))."', ".
	 		"field_required = '".(int) $this->isRequired()."' ".
	 		"WHERE field_id = ".$this->db->quote($this->getId())." ".
	 		"AND obj_id = ".$this->db->quote($this->getObjId());
	 	$this->db->query($query);
	 	return true;
	}
	
	/**
	 * Delete a field
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function delete()
	{
	 	include_once('Modules/Course/classes/Export/class.ilCourseUserData.php');
	 	ilCourseUserData::_deleteByField($this->getId());
		
	 	$query = "DELETE FROM crs_defined_field_definitions ".
	 		"WHERE field_id = ".$this->db->quote($this->getId())." ";
	 	$this->db->query($query);
	}
	
	/**
	 * Read DB entries
	 *
	 * @access private
	 * 
	 */
	private function read()
	{
	 	$query = "SELECT * FROM crs_defined_field_definitions ".
	 		"WHERE field_id = ".$this->db->quote($this->getId())." ".
	 		"AND obj_id = ".$this->db->quote($this->getObjId())." ";
	 	
	 	$res = $this->db->query($query);
	 	$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
	 	
		$this->setName($row->field_name);
		$this->setType($row->field_type);
		$this->setValues(unserialize(stripslashes($row->field_values)));
		$this->enableRequired($row->field_required);
	}
}


?>