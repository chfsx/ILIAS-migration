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
* Singleton class that stores all privacy settings
*
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
*
*
* @ingroup Services/PrivacySecurity
*/

class ilPrivacySettings
{
    private static $instance = null;
		private $db;
		private $settings;
	
		private $export_course;
		private $export_group;
		private $export_confirm_course;
		private $export_confirm_group;
		private $fora_statistics;
		private $anonymous_fora;
		private $show_grp_access_times;
		private $show_crs_access_times;
		private $ref_id;

    /**
	 * Private constructor: use _getInstance()
	 *
	 * @access private
	 * @param
	 *
	 */
	private function __construct()
	{
		global $ilSetting,$ilDB;

		$this->db = $ilDB;
		$this->settings = $ilSetting;

	 	$this->read();
	}

	/**
	 * Get instance of ilPrivacySettings
	 *
	 * @return object ilPrivacySettings
	 * @access public
	 *
	 */
	public static function _getInstance()
	{
		if(is_object(self::$instance))
		{
			return self::$instance;
		}
	 	return self::$instance = new ilPrivacySettings();
	}

	public function getPrivacySettingsRefId()
	{
		return $this->ref_id;
	}

	public function enabledCourseExport()
	{
		return $this->export_course;
	}
	
	public function enabledGroupExport()
	{
		return $this->export_group;
	}
	
	/**
	 * @todo rename
	 * @param object $a_ref_id
	 * @return 
	 */
	public function checkExportAccess($a_ref_id)
	{
		global $ilUser,$ilAccess,$rbacsystem;
		
		if(ilObject::_lookupObjId(ilObject::_lookupType($a_ref_id)) == 'crs')
		{
			return $this->enabledCourseExport() and 
				!$this->courseConfirmationRequired() or 
				($ilAccess->checkAccess('write','',$a_ref_id) and $rbacsystem->checkAccess('export_member_data',$this->getPrivacySettingsRefId()));
		}
		else
		{
			return $this->enabledGroupExport() and 
				!$this->groupConfirmationRequired() or 
				($ilAccess->checkAccess('write','',$a_ref_id) and $rbacsystem->checkAccess('export_member_data',$this->getPrivacySettingsRefId()));
		}		
	}

	public function enableCourseExport($a_status)
	{
		$this->export_course = (bool) $a_status;
	}

	public function enableGroupExport($a_status)
	{
		$this->export_group = (bool) $a_status;
	}

	/**
	*	write access to property fora statitics
	* @param bool $a_status	value to set property
	*/	
	public function enableForaStatistics ($a_status) 
	{
		$this->fora_statistics = (bool) $a_status;
	}
	
	/**
	*	read access to property enable fora statistics
	* @return bool true if enabled, false otherwise
	*/
	public function enabledForaStatistics () 
	{
			return $this->fora_statistics;
	}

	/**
	* write access to property anonymous fora
	* @param bool $a_status	value to set property
	*/	
	public function disableAnonymousFora ($a_status) 
	{
		$this->anonymous_fora = (bool) $a_status;
	}
	
	/**
	* read access to property enable anonymous fora
	* @return bool true if enabled, false otherwise
	*/
	public function disabledAnonymousFora () 
	{
			return $this->anonymous_fora;
	}
	
	public function confirmationRequired($a_type)
	{
		switch($a_type)
		{
			case 'crs':
				return $this->courseConfirmationRequired();
				
			case 'grp':
				return $this->groupConfirmationRequired();
		}
		return false;
	}

	public function courseConfirmationRequired()
	{
		return $this->export_confirm_course;
	}

	public function groupConfirmationRequired()
	{
		return $this->export_confirm_group;
	}

	public function setCourseConfirmationRequired($a_status)
	{
		$this->export_confirm_course = (bool) $a_status;
	}
	
	public function setGroupConfirmationRequired($a_status)
	{
		$this->export_confirm_group = (bool) $a_status;
	}

	/**
	 * Show group last access times
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function showGroupAccessTimes($a_status)
	{
	 	$this->show_grp_access_times = $a_status;
	}
	
	/**
	 * check if group access time are visible
	 *
	 * @access public
	 * 
	 */
	public function enabledGroupAccessTimes()
	{
	 	return (bool) $this->show_grp_access_times;
	}
	
	/**
	 * show course access times
	 *
	 * @access public
	 * @param bool status
	 * @return
	 */
	public function showCourseAccessTimes($a_status)
	{
		$this->show_crs_access_times = $a_status;
	}
	
	/**
	 * check if access time are enabled in courses
	 *
	 * @access public
	 * @return
	 */
	public function enabledCourseAccessTimes()
	{
		return (bool) $this->show_crs_access_times;
	}

	/**
	 * Save settings
	 *
	 *
	 */
	public function save()
	{
	 	$this->settings->set('ps_export_confirm',(bool) $this->courseConfirmationRequired());
	 	$this->settings->set('ps_export_confirm_group',(bool) $this->groupConfirmationRequired());
	 	$this->settings->set('ps_export_course',(bool) $this->enabledCourseExport());
	 	$this->settings->set('ps_export_group',(bool) $this->enabledGroupExport());
	 	$this->settings->set('enable_fora_statistics',(bool) $this->enabledForaStatistics());
	 	$this->settings->set('disable_anonymous_fora',(bool) $this->disabledAnonymousFora());
	 	$this->settings->set('ps_access_times',(bool) $this->enabledGroupAccessTimes());
	 	$this->settings->set('ps_crs_access_times',(bool) $this->enabledCourseAccessTimes());
	}
	/**
	 * read settings
	 *
	 * @access private
	 * @param
	 *
	 */
	private function read()
	{
		global $ilDB;
		
	    $query = "SELECT object_reference.ref_id FROM object_reference,tree,object_data ".
				"WHERE tree.parent = ".$ilDB->quote(SYSTEM_FOLDER_ID,'integer')." ".
				"AND object_data.type = 'ps' ".
				"AND object_reference.ref_id = tree.child ".
				"AND object_reference.obj_id = object_data.obj_id";
		$res = $this->db->query($query);
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		$this->ref_id = $row["ref_id"];

		$this->export_course = (bool) $this->settings->get('ps_export_course',false);
		$this->export_group = (bool) $this->settings->get('ps_export_group',false);
		$this->export_confirm_course = (bool) $this->settings->get('ps_export_confirm',false);
		$this->export_confirm_group = (bool) $this->settings->get('ps_export_confirm_group',false);
		$this->fora_statistics = (bool) $this->settings->get('enable_fora_statistics',false);
		$this->anonymous_fora = (bool) $this->settings->get('disable_anonymous_fora',false);
		$this->show_grp_access_times = (bool) $this->settings->get('ps_access_times',false);
		$this->show_crs_access_times = (bool) $this->settings->get('ps_crs_access_times',false);

	}

	/**
	 * validate settings
	 *
	 * @return 0, if everything is ok, an error code otherwise
	 */
	public function validate() 
	{
	    return 0;
	}


}
?>
