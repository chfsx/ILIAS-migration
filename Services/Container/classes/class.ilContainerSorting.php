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
* @ingroup ServicesContainer 
*/
class ilContainerSorting
{
	protected static $instances = array();

	protected $obj_id;
	protected $db;
	
	protected $manual_sort_enabled = false;
	protected $sorting_mode = 0;

	/**
	 * Constructor
	 *
	 * @access private
	 * @param int obj_id
	 * 
	 */
	private function __construct($a_obj_id)
	{
	 	global $ilDB;
	 	
	 	$this->db = $ilDB;
	 	$this->obj_id = $a_obj_id;
	 	
	 	$this->read();
	}
	
	/**
	 * get sort mode
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function getSortMode()
	{
		return $this->sorting_mode;
	}
	
	
	/**
	 * get instance by obj_id
	 *
	 * @access public
	 * @param int obj_id
	 * @return
	 * @static
	 */
	public static function _getInstance($a_obj_id)
	{
		if(isset(self::$instances[$a_obj_id]))
		{
			return self::$instances[$a_obj_id];
		}
		return self::$instances[$a_obj_id] = new ilContainerSorting($a_obj_id);
	}
	
	
	/**
	 * sort subitems
	 *
	 * @access public
	 * @param array item data
	 * @return array sorted item data
	 */
	public function sortItems($a_items)
	{
		$sorted = array();
		if(!$this->manual_sort_enabled)
		{
			switch($this->getSortMode())
			{
				case ilContainer::SORT_TITLE:
					foreach((array) $a_items as $type => $data)
					{
						$sorted[$type] = ilUtil::sortArray((array) $data,'title','asc',true);
					}
					return $sorted ? $sorted : array();
			}
			return $a_items;
		}
		if(!count($a_items))
		{
			return $a_items;
		}
		foreach($a_items as $type => $data)
		{
			// Add position
			$items = array();
			foreach($data as $key => $item)
			{
				$items[$key] = $item;
				$items[$key]['position'] = isset($this->sorting['all'][$item['child']]) ? $this->sorting['all'][$item['child']] : 9999;
			}

			switch($type)
			{
				case '_all':
					$sorted[$type] = ilUtil::sortArray((array) $items,'position','asc',true);
					break;
				
				case '_non_sess':
					$sorted[$type] = ilUtil::sortArray((array) $items,'position','asc',true);
					break;
				
				default:
					$sorted[$type] = ilUtil::sortArray((array) $items,'position','asc',true);
					break;
			}
		}
		return $sorted ? $sorted : array();
	}
	
	/**
	 * sort subitems (items of sessions or learning objectives)
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function sortSubItems($a_parent_type,$a_parent_id,$a_items)
	{
		switch($this->getSortMode())
		{
			case ilContainer::SORT_MANUAL:
				// Add position
				$items = array();
				foreach($a_items as $key => $item)
				{
					$items[$key] = $item;
					$items[$key]['position'] = isset($this->sorting[$a_parent_type][$a_parent_id][$item['child']]) ? 
													$this->sorting[$a_parent_type][$a_parent_id][$item['child']] : 9999;
				}
				return ilUtil::sortArray((array) $items,'position','asc',true);
				

			case ilContainer::SORT_TITLE:
			default:
				return ilUtil::sortArray((array) $a_items,'title','asc',true);
		}

	}
	
	
	
		
	/**
	 * is manual sorting enabled
	 *
	 * @access public
	 * @return bool
	 */
	public function isManualSortingEnabled()
	{
		return (bool) $this->manual_sort_enabled;
	}
	
	/**
	 * Save post
	 *
	 * @access public
	 * @param array of positions e.g array(crs => array(1,2,3),'lres' => array(3,5,6))
	 * 
	 */
	public function savePost($a_type_positions)
	{
	 	if(!is_array($a_type_positions))
	 	{
	 		return false;
	 	}
	 	foreach($a_type_positions as $key => $position)
	 	{
	 		if(!is_array($position))
	 		{
	 			$items[$key] = (float) $position;
	 		}
	 		else
	 		{
				foreach($position as $parent_id => $items)
				{
					$this->saveItems($key,$parent_id,$items ? $items : array());
				}
	 		}
	 	}
	 	$this->saveItems('',0,$items ? $items : array());
	}
	
	
	/**
	 * save items
	 *
	 * @access protected
	 * @param string parent_type only used for sessions and objectives in the moment. Otherwise empty
	 * @param int parent id
	 * @param array array of items
	 * @return
	 */
	protected function saveItems($a_parent_type,$a_parent_id,$a_items)
	{
		foreach($a_items as $child_id => $position)
		{
			$query = "REPLACE INTO container_sorting SET ".
				"obj_id = ".$this->db->quote($this->obj_id).", ".
				"parent_type = ".$this->db->quote($a_parent_type).", ".
				"parent_id = ".$this->db->quote($a_parent_id).", ".
				"child_id = ".$this->db->quote($child_id).", ".
				"position = ".$this->db->quote($position);
			$res = $this->db->query($query);
		}
		return true;
	}
	
	
	/**
	 * Read
	 *
	 * @access private
	 * 
	 */
	private function read()
	{
	 	if(!$this->obj_id)
	 	{
	 		return true;
	 	}
	 	
	 	include_once('Services/Container/classes/class.ilContainerSortingSettings.php');
	 	$this->manual_sort_enabled = ilContainerSortingSettings::_isManualSortingEnabled($this->obj_id);
	 	$this->sorting_mode = ilContainerSortingSettings::_lookupSortMode($this->obj_id);
	 	
	 	$query = "SELECT * FROM container_sorting ".
	 		"WHERE obj_id = ".$this->db->quote($this->obj_id)." ORDER BY position";
	 	$res = $this->db->query($query);
	 	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	 	{
	 		if($row->parent_type)
	 		{
		 		$this->sorting[$row->parent_type][$row->parent_id][$row->child_id] = $row->position;
	 		}
	 		else
	 		{
	 			$this->sorting['all'][$row->child_id] = $row->position;
	 		}
	 	}
		return true;
	}
}


?>