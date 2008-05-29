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
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ServicesCalendar 
*/

class ilCalendarCategoryAssignments
{
	protected $db;
	
	protected $cal_entry_id = 0;
	protected $assignments = array();

	/**
	 * Constructor
	 *
	 * @access public
	 * @param int calendar entry id
	 */
	public function __construct($a_cal_entry_id)
	{
		global $ilDB;
		
		$this->db = $ilDB;
		$this->cal_entry_id = $a_cal_entry_id;
		
		$this->read();
	}
	
	/**
	 * lookup categories
	 *
	 * @access public
	 * @param int cal_id
	 * @return array of categories
	 * @static
	 */
	public static function _lookupCategories($a_cal_id)
	{
		global $ilDB;
		
		$query = "SELECT cat_id FROM cal_category_assignments ".
			"WHERE cal_id = ".$ilDB->quote($a_cal_id)." ";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$cat_ids[] = $row->cat_id;
		}
		return $cat_ids ? $cat_ids : array();
	}
	
	/**
	 * Lookup category id
	 *
	 * @access public
	 * @param
	 * @return
	 * @static
	 */
	public static function _lookupCategory($a_cal_id)
	{
		if(count($cats = self::_lookupCategories($a_cal_id)))
		{
			return $cats[0];
		}
		return 0;
	}
	
	/**
	 * lookup appointment ids by calendar
	 *
	 * @access public
	 * @param int calendar category id
	 * @return array int cal entry ids
	 * @static
	 */
	public static function _getAssignedAppointments($a_cat_id)
	{
		global $ilDB;
		
		$query = "SELECT * FROM cal_category_assignments ".
			"WHERE cat_id = ".$ilDB->quote($a_cat_id)." ";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$cal_ids[] = $row->cal_id;
		}
		return $cal_ids ? $cal_ids : array();
	}
	
	/**
	 * get automatic generated appointments of category
	 *
	 * @access public
	 * @param int obj_id
	 * @return
	 * @static
	 */
	public static function _getAutoGeneratedAppointmentsByObjId($a_obj_id)
	{
		global $ilDB;
		
		$query = "SELECT ce.cal_id FROM cal_categories AS cc ".
			"JOIN cal_category_assignments AS cca ON cc.cat_id = cca.cat_id ".
			"JOIN cal_entries AS ce ON cca.cal_id = ce.cal_id ".
			"WHERE public = 1 ".
			"AND obj_id = ".$ilDB->quote($a_obj_id)." ";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$apps[] = $row->cal_id;
		}
		return $apps ? $apps : array();
	}
	
	/**
	 * Delete appointment assignment
	 *
	 * @access public
	 * @param int appointment id
	 * @static
	 */
	public static function _deleteByAppointmentId($a_app_id)
	{
		global $ilDB;
		
		$query = "DELETE FROM cal_category_assignments ".
			"WHERE cal_id = ".$ilDB->quote($a_app_id)." ";
		$res = $ilDB->query($query);
		return true;
	}
	
	/**
	 * Delete assignments by category id
	 *
	 * @access public
	 * @param int category_id
	 * @return
	 * @static
	 */
	public static function _deleteByCategoryId($a_cat_id)
	{
		global $ilDB;
		
		$query = "DELETE FROM cal_category_assignments ".
			"WHERE cat_id = ".$ilDB->quote($a_cat_id)." ";
		$res = $ilDB->query($query);
		return true;
	}
	
	/**
	 * get first assignment
	 *
	 * @access public
	 * @return
	 */
	public function getFirstAssignment()
	{
		return isset($this->assignments[0]) ? $this->assignments[0] : false;
	}
	
	/**
	 * get assignments
	 *
	 * @access public
	 * @return
	 */
	public function getAssignments()
	{
		return $this->assignments ? $this->assignments : array();
	}
	
	/**
	 * add assignment
	 *
	 * @access public
	 * @param int calendar category id
	 * @return
	 */
	public function addAssignment($a_cal_cat_id)
	{
		$query = "INSERT INTO cal_category_assignments ".
			"SET cal_id = ".$this->db->quote($this->cal_entry_id).", ".
			"cat_id = ".$this->db->quote($a_cal_cat_id)." ";
		$this->db->query($query);
		$this->assignments[] = (int) $a_cal_cat_id;
		
		return true;
	}
	
	/**
	 * delete assignment
	 *
	 * @access public
	 * @param int calendar category id
	 * @return
	 */
	public function deleteAssignment($a_cat_id)
	{
		$query = "DELETE FROM cal_category_assignments ".
			"WHERE cal_id = ".$this->db->quote($this->cal_entry_id).", ".
			"AND cat_id = ".$this->db->quote($a_cat_id)." ";
		$this->db->query($query);
		
		if(($key = array_search($a_cat_id,$this->assignments)) !== false)
		{
			unset($this->assignments[$key]);
		}
		return true;
	}
	
	/**
	 * delete assignments
	 *
	 * @access public
	 */
	public function deleteAssignments()
	{
		$query = "DELETE FROM cal_category_assignments ".
			"WHERE cal_id = ".$this->db->quote($this->cal_entry_id)." ";
		$this->db->query($query);
		return true;
	}

	
	/**
	 * read assignments
	 *
	 * @access private
	 * @return
	 */
	private function read()
	{
		$query = "SELECT * FROM cal_category_assignments ".
			"WHERE cal_id = ".$this->db->quote($this->cal_entry_id)." ";
			
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->assignments[] = $row->cat_id;
		}
	}
}
?>