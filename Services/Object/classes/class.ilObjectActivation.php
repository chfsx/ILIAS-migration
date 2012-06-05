<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilObjectActivation
*
* @author Stefan Meyer <meyer@leifos.com> 
* @version $Id: class.ilCourseItems.php 30321 2011-08-22 12:05:03Z jluetzen $
* 
* @extends Object
*/
class ilObjectActivation
{
	protected $timing_type;
	protected $timing_start;
	protected $timing_end;
	protected $suggestion_start;
	protected $suggestion_end;
	protected $earliest_start;
	protected $latest_end;
	protected $visible;
	protected $changeable;
	
	protected static $preloaded_data = array();
	
	const TIMINGS_ACTIVATION = 0;
	const TIMINGS_DEACTIVATED = 1;
	const TIMINGS_PRESETTING = 2;
	const TIMINGS_FIXED = 3; // session only => obsolete?
	
	function __construct()
	{
		
	}
	
	/**
	 * Set timing type
	 * 
	 * @see class constants
	 * @param int $a_type 
	 */
	function setTimingType($a_type)
	{
		$this->timing_type = $a_type;
	}
	
	/**
	 * get timing type
	 * 
	 * @see class constants
	 * @return int
	 */
	function getTimingType()
	{
		return $this->timing_type;
	}
	
	/**
	 * Set timing start
	 * 
	 * @param timestamp $a_start 
	 */
	function setTimingStart($a_start)
	{
		$this->timing_start = $a_start;
	}
	
	/**
	 * Get timing start
	 * 
	 * @return timestamp
	 */
	function getTimingStart()
	{
		return $this->timing_start;
	}
	
	/**
	 * Set timing end
	 * 
	 * @param timestamp $a_end
	 */
	function setTimingEnd($a_end)
	{
		$this->timing_end = $a_end;
	}
	
	/**
	 * Get timing end
	 * 
	 * @return timestamp 
	 */
	function getTimingEnd()
	{
		return $this->timing_end;
	}
	
	/**
	 * Set suggestion start
	 * 
	 * @param timestamp $a_start 
	 */
	function setSuggestionStart($a_start)
	{
		$this->suggestion_start = $a_start;
	}
	
	/**
	 * Get suggestion start
	 * 
	 * @return timestamp 
	 */
	function getSuggestionStart()
	{
		return $this->suggestion_start ? $this->suggestion_start : mktime(0,0,1,date('n',time()),date('j',time()),date('Y',time()));
	}
	
	/**
	 * Set suggestion end
	 * 
	 * @param timestamp $a_end 
	 */
	function setSuggestionEnd($a_end)
	{
		$this->suggestion_end = $a_end;
	}
	
	/**
	 * Get suggestion end
	 * 
	 * @return timestamp
	 */
	function getSuggestionEnd()
	{
		return $this->suggestion_end ? $this->suggestion_end : mktime(23,55,00,date('n',time()),date('j',time()),date('Y',time()));
	}
	
	/**
	 * Set earliest start
	 * 
	 * @param timestamp $a_start 
	 */
	function setEarliestStart($a_start)
	{
		$this->earliest_start = $a_start;
	}
	
	/**
	 * Get earliest start
	 * 
	 * @return timestamp
	 */
	function getEarliestStart()
	{
		return $this->earliest_start ? $this->earliest_start : mktime(0,0,1,date('n',time()),date('j',time()),date('Y',time()));
	}
	
	/**
	 * Set latest end
	 * 
	 * @param timestamp $a_end 
	 */
	function setLatestEnd($a_end)
	{
		$this->latest_end = $a_end;
	}
	
	/**
	 * Get latest end
	 * 
	 * @return timestamp
	 */
	function getLatestEnd()
	{
		return $this->latest_end ? $this->latest_end : mktime(23,55,00,date('n',time()),date('j',time()),date('Y',time()));
	}
	
	/**
	 * Set visible status
	 * 
	 * @param bool $a_status
	 */
	function toggleVisible($a_status)
	{
		$this->visible = (int) $a_status;
	}
	
	/**
	 * Get visible status
	 * 
	 * @return bool 
	 */
	function enabledVisible()
	{
		return (bool) $this->visible;
	}
	
	/**
	 * Set changeable status
	 * 
	 * @param bool $a_status
	 */
	function toggleChangeable($a_status)
	{
		$this->changeable = (int) $a_status;
	}
	
	/**
	 * Get changeable status
	 * 
	 * @return bool 
	 */
	function enabledChangeable()
	{
		return (bool) $this->changeable;
	}
		
	/**
	 * Validate current properties
	 * 
	 * @return boolean  
	 */
	function validateActivation()
	{
		global $ilErr, $lng;
		
		$ilErr->setMessage('');

		if($this->getTimingType() == self::TIMINGS_ACTIVATION)
		{
			if($this->getTimingStart() > $this->getTimingEnd())
			{
				$ilErr->appendMessage($lng->txt("crs_activation_start_invalid"));
			}
		}
		else if($this->getTimingType() == self::TIMINGS_PRESETTING)
		{
			if($this->getSuggestionStart() > $this->getSuggestionEnd())
			{
				$ilErr->appendMessage($lng->txt('crs_latest_end_not_valid'));
			}
		}
	
		if($ilErr->getMessage())
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Update db entry
	 * 
	 * @param int $a_ref_id
	 */
	function update($a_ref_id)
	{
		global $ilDB;
		
		$query = "UPDATE crs_items SET ".
			"timing_type = ".$ilDB->quote($this->getTimingType(),'integer').", ".
			"timing_start = ".$ilDB->quote($this->getTimingStart(),'integer').", ".
			"timing_end = ".$ilDB->quote($this->getTimingEnd(),'integer').", ".
			"suggestion_start = ".$ilDB->quote($this->getSuggestionStart(),'integer').", ".
			"suggestion_end = ".$ilDB->quote($this->getSuggestionEnd(),'integer').", ".
			"changeable = ".$ilDB->quote($this->enabledChangeable(),'integer').", ".
			"earliest_start = ".$ilDB->quote($this->getEarliestStart(),'integer').", ".
			"latest_end = ".$ilDB->quote($this->getLatestEnd(),'integer').", ".
			"visible = ".$ilDB->quote($this->enabledVisible(),'integer')." ".
			"WHERE obj_id = ".$ilDB->quote($a_ref_id,'integer')."";
		$ilDB->manipulate($query);
		
		unset(self::$preloaded_data[$a_ref_id]);
	
		return true;
	}

	/**
	 * Preload data to internal cache 
	 *
	 * @param array $a_ref_ids 
	 */
	public static function preloadData(array $a_ref_ids)
	{
		global $ilDB;
		
		$sql = "SELECT * FROM crs_items".
			" WHERE ".$ilDB->in("obj_id", $a_ref_ids, "", "integer");		
		$set = $ilDB->query($sql);
		while($row = $ilDB->fetchAssoc($set))
		{
			self::$preloaded_data[$row["obj_id"]] = $row;						
		}
	}
		
	/**
	 * Get item data
	 * 
	 * @param int $a_ref_id
	 * @return array 
	 */
	public static function getItem($a_ref_id)
	{
		global $ilDB;
		
		if(isset(self::$preloaded_data[$a_ref_id]))
		{
			return self::$preloaded_data[$a_ref_id];
		}
		
		$sql = "SELECT * FROM crs_items".
			" WHERE obj_id = ".$ilDB->quote($a_ref_id, "integer");	
		$set = $ilDB->query($sql);
		$row = $ilDB->fetchAssoc($set);
		
		if(!isset($row["obj_id"]))
		{			
			$row = self::createDefaultEntry($a_ref_id);
		}
		
		self::$preloaded_data[$row["obj_id"]] = $row;
		return $row;
	}

	/**
	 * Parse item data for list entries
	 * 
	 * @param array &$a_item
	 */
	public static function addAdditionalSubItemInformation(array &$a_item)
	{
		global $ilUser;
		
		$item = self::getItem($a_item['ref_id']);
		
		$a_item['obj_id'] = ($a_item['obj_id'] > 0)
			? $a_item['obj_id']
			: ilObject::_lookupObjId($a_item['ref_id']);
		$a_item['type'] = ($a_item['type'] != '')
			? $a_item['type']
			: ilObject::_lookupType($a_item['obj_id']);
		
		$a_item['timing_type'] = $item['timing_type'];
		
		if($item['changeable'] &&  
			$item['timing_type'] == self::TIMINGS_PRESETTING)
		{
			include_once 'Modules/Course/classes/Timings/class.ilTimingPlaned.php';
			$user_data = ilTimingPlaned::_getPlanedTimings($ilUser->getId(), $a_item['ref_id']);			
			if($user_data['planed_start'])
			{
				$a_item['start'] = $user_data['planed_start'];
				$a_item['end'] = $user_data['planed_end'];
				$a_item['activation_info'] = 'crs_timings_planed_info';
			}
			else
			{
				$a_item['start'] = $item['suggestion_start'];
				$a_item['end'] = $item['suggestion_end'];
				$a_item['activation_info'] = 'crs_timings_suggested_info';
			}
		}
		elseif($item['timing_type'] == self::TIMINGS_PRESETTING)
		{
			$a_item['start'] = $item['suggestion_start'];
			$a_item['end'] = $item['suggestion_end'];
			$a_item['activation_info'] = 'crs_timings_suggested_info';
		}
		elseif($item['timing_type'] == self::TIMINGS_ACTIVATION)
		{
			$a_item['start'] = $item['timing_start'];
			$a_item['end'] = $item['timing_end'];
			$a_item['activation_info'] = 'activation';
		}
		/* obsolete?
		elseif($obj_type == 'sess')
		{
			include_once('./Modules/Session/classes/class.ilSessionAppointment.php');
			$info = ilSessionAppointment::_lookupAppointment($obj_id);
			
			$a_item['timing_type'] = self::TIMINGS_FIXED;
			$a_item['start'] = $info['start'];
			$a_item['end'] = $info['end'];
			$a_item['fullday'] = $info['fullday'];
			$a_item['activation_info'] = 'crs_timings_suggested_info';
		}		 
		*/
		else
		{
			$a_item['start'] = 'abc';
		}			
	}
		
	/**
	 * Create db entry with default values
	 * 
	 * @param int $a_ref_id
	 * @return array 
	 */
	protected static function createDefaultEntry($a_ref_id)
	{
		global $ilDB, $tree;
		
		$a_item = array();
		$a_item["timing_type"]		= self::TIMINGS_DEACTIVATED;
		$a_item["timing_start"]		= time();
		$a_item["timing_end"]		= time();
		$a_item["suggestion_start"]	= time();
		$a_item["suggestion_end"]	= time();
		$a_item['visible']			= 0;
		$a_item['changeable']		= 0;
		$a_item['earliest_start']	= time();
		$a_item['latest_end']	    = mktime(23,55,00,date('n',time()),date('j',time()),date('Y',time()));
		$a_item['visible']			= 0;
		$a_item['changeable']		= 0;
		
	 	$query = "INSERT INTO crs_items (parent_id,obj_id,timing_type,timing_start,timing_end," .
	 		"suggestion_start,suggestion_end, ".
	 		"changeable,earliest_start,latest_end,visible,position) ".
	 		"VALUES( ".
			$ilDB->quote($tree->getParentId($a_ref_id),'integer').",".
			$ilDB->quote($a_ref_id,'integer').",".
			$ilDB->quote($a_item["timing_type"],'integer').",".
			$ilDB->quote($a_item["timing_start"],'integer').",".
			$ilDB->quote($a_item["timing_end"],'integer').",".
			$ilDB->quote($a_item["suggestion_start"],'integer').",".
			$ilDB->quote($a_item["suggestion_end"],'integer').",".
			$ilDB->quote($a_item["changeable"],'integer').",".
			$ilDB->quote($a_item['earliest_start'],'integer').", ".
			$ilDB->quote($a_item['latest_end'],'integer').", ".
			$ilDB->quote($a_item["visible"],'integer').", ".
			$ilDB->quote(0,'integer').")";
		$ilDB->manipulate($query);
	
		return $a_item;
	}
		
	/**
	 * Delete all db entries for ref id
	 * 
	 * @param int $a_ref_id
	 */
	public static function deleteAllEntries($a_ref_id)
	{
		global $ilDB;
		
		if(!$a_ref_id)
		{
			return;
		}
		
		$query = "DELETE FROM crs_items ".
			"WHERE obj_id = ".$ilDB->quote($a_ref_id,'integer');
		$ilDB->manipulate($query);
		
		$query = "DELETE FROM crs_items ".
			"WHERE parent_id = ".$ilDB->quote($a_ref_id,'integer');
		$ilDB->manipulate($query);		

		return true;
	}
	
	
	//
	// TIMINGS VIEW RELATED (COURSE ONLY)
	// 	
	
	/**
	 * Check if there is any active timing (in subtree)
	 * 
	 * @param int ref_id
	 * @return bool
	 */
	public static function hasTimings($a_ref_id)
	{
		global $tree, $ilDB;

		$subtree = $tree->getSubTree($tree->getNodeData($a_ref_id));
		$ref_ids = array();
		foreach($subtree as $node)
		{
			$ref_ids[] = $node['ref_id'];
		}

		$query = "SELECT * FROM crs_items ".
			"WHERE timing_type = ".$ilDB->quote(self::TIMINGS_PRESETTING,'integer')." ".
			"AND ".$ilDB->in('obj_id',$ref_ids,false,'integer');
		$res = $ilDB->query($query);
		return $res->numRows() ? true :false;
	}

	/**
	 * Check if there is any active changeable timing (in subtree)
	 * 
	 * @param int ref_id
	 * @return bool
	 */
	public static function hasChangeableTimings($a_ref_id)
	{
		global $tree, $ilDB;

		$subtree = $tree->getSubTree($tree->getNodeData($a_ref_id));
		$ref_ids = array();
		foreach($subtree as $node)
		{
			$ref_ids[] = $node['ref_id'];
		}

		$query = "SELECT * FROM crs_items ".
			"WHERE timing_type = ".$ilDB->quote(self::TIMINGS_PRESETTING,'integer')." ".
			"AND changeable = ".$ilDB->quote(1,'integer')." ".
			"AND ".$ilDB->in('obj_id',$ref_ids,false,'integer');
		$res = $ilDB->query($query);
		return $res->numRows() ? true : false;
	}			
	
	/**
	 * Validate ref ids and add list data
	 * 
	 * @param array $a_ref_ids
	 * @return array
	 */
	protected static function processListItems(array $a_ref_ids)
	{
		global $tree;
		
		$res = array();
		
		foreach($a_ref_ids as $item_ref_id)
		{			
			if($tree->isDeleted($item_ref_id))
			{
				continue;
			}
			// #7571: when node is removed from system, e.g. inactive trashcan, an empty array is returned
			$node = $tree->getNodeData($item_ref_id);
			if($node["ref_id"] != $item_ref_id)
			{
				continue;
			}			
			$res[$item_ref_id] = $node;
		}
					
		if(sizeof($res))
		{			
			self::preloadData(array_keys($res));			
			foreach($res as $idx => $item)
			{
				self::addAdditionalSubItemInformation($item);
				$res[$idx] = $item;
			}			
		}
		
		return array_values($res);
	}

	/**
	 * Get session material / event items
	 * 
	 * @param int $a_event_id (object id)
	 * @return array 
	 */
	public static function getItemsByEvent($a_event_id)
	{		
		include_once 'Modules/Session/classes/class.ilEventItems.php';
		$event_items = new ilEventItems($a_event_id);		
		return self::processListItems($event_items->getItems());
	}
		
	/**
	 * Get objective items
	 * 
	 * @param int $a_objective_id
	 * @return array
	 */
	public static function getItemsByObjective($a_objective_id)
	{
		include_once('./Modules/Course/classes/class.ilCourseObjectiveMaterials.php');
		$item_ids = ilCourseObjectiveMaterials::_getAssignedMaterials($a_objective_id);
		return self::processListItems($item_ids);		
	}
	
	/**
	 * Get sub item data
	 * 
	 * @param int $a_parent_id
	 * @return array 
	 */
	public static function getItems($a_parent_id)
	{
		global $tree;
		
		$items = array();	
		
		$ref_ids = array();
		foreach($tree->getChilds($a_parent_id) as $item)
		{			
			if($item['type'] != 'rolf')
			{
				$items[] = $item;
				$ref_ids[] = $item['ref_id'];
			}
		}
		
		if($ref_ids)
		{
			self::preloadData($ref_ids);
			
			foreach($items as $idx => $item)
			{				
				$items[$idx] = array_merge($item, self::getItem($item['ref_id']));
			}
		}
		
		return $items;
	}
	
	/**
	 * Get (sub) item data for timings administration view (active/inactive)
	 * 
	 * @param int $a_parent_id
	 * @return array
	 */
	public static function getTimingsAdministrationItems($a_parent_id)
	{		
		$items = self::getItems($a_parent_id);
		
		if($items)
		{			
			$active = $inactive = array();
			foreach($items as $item)
			{								
				// active should be first in order
				if($item['timing_type'] == self::TIMINGS_DEACTIVATED)
				{
					$inactive[] = $item;
				}
				else
				{
					$active[] = $item;
				}
			}
			
			$active = ilUtil::sortArray($active,'start','asc');
			$inactive = ilUtil::sortArray($inactive,'title','asc');				
			$items = array_merge($active,$inactive);
		}
		
		return $items;		
	}
	
	/**
	 * Get (sub) item data for timings view (no session material, no side blocks)
	 * 
	 * @param int $a_container_ref_id
	 * @return array
	 */
	public static function getTimingsItems($a_container_ref_id)
	{
		global $objDefinition;
		
		$filtered = array();
		
		include_once 'Modules/Session/classes/class.ilEventItems.php';
		$event_items = ilEventItems::_getItemsOfContainer($a_container_ref_id);
		foreach(self::getTimingsAdministrationItems($a_container_ref_id) as $item)
		{
			if(!in_array($item['ref_id'],$event_items) &&
				!$objDefinition->isSideBlock($item['type']))
			{
				$filtered[] = $item;
			}
		}
		
		return $filtered;
	} 	
}	
	
?>