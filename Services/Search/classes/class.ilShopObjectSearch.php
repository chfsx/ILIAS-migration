<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilShopObjectSearch
*
* @author Michael Jansen <mjansen@databay.de>* 
* @package ilias-search
*
*/
include_once 'Services/Search/classes/class.ilAbstractSearch.php';

class ilShopObjectSearch extends ilAbstractSearch
{
	private $filter_shop_topic_id = 0;
	
	public function __construct($qp_obj)
	{
		parent::__construct($qp_obj);
		$this->setFields(array('title', 'description'));
	}
	
	public function setCustomSearchResultObject($a_search_result_obect)
	{
		$this->search_result = $a_search_result_obect;
	}	
	
	public function setFilterShopTopicId($a_topic_id)
	{
		$this->filter_shop_topic_id = $a_topic_id;
	}	
	public function getFilterShopTopicId()
	{
		return $this->filter_shop_topic_id;
	}
	
	public function performSearch()
	{
		$types = array();
		$values = array();
		
		$in = $this->__createInStatement();
		$where = $this->__createWhereCondition();
		$locate = $this->__createLocateString();
		
		$query = "SELECT object_data.obj_id,object_data.type ".$locate."			  
				  FROM payment_objects 
				  INNER JOIN object_reference ON object_reference.ref_id = payment_objects.ref_id
				  INNER JOIN object_data ON object_data.obj_id = object_reference.obj_id ";	
				  
		$query .= $where['query'];
		$types = array_merge($types, $where['types']);
		$values = array_merge($values, $where['values']);
		$query .= $in;  
				  
		$query .= " GROUP BY object_data.obj_id,object_data.type,object_data.title,object_data.description";		
		$query .= " ORDER BY object_data.obj_id DESC";

		$statement = $this->db->queryf(
			$query,
			$types,
			$values
		);
				
		while($row = $statement->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->search_result->addEntry($row->obj_id,$row->type,$this->__prepareFound($row));
		}		
		return $this->search_result;
	}

	public function __createInStatement()
	{		
		return ' AND ' . $this->db->in('type', $this->object_types, false, 'text');
	}
}
?>