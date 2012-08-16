<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once "Services/Object/classes/class.ilObject2.php";

/**
* Class ilObjDataCollection
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id: class.ilObjFolder.php 25528 2010-09-03 10:37:11Z smeyer $
*
* @extends ilObject2
*/
class ilObjDataCollection extends ilObject2
{	
	
	
	const IL_DCL_EDIT_TYPE_NON = 0;
	const IL_DCL_EDIT_TYPE_UNLIM = 1;
	const IL_DCL_EDIT_TYPE_LIM = 2;

	var $edit_by_owner;
	/*
	 * initType
	 */
	function initType()
	{
		$this->type = "dcl";
	}
	
	/*
	 * doRead
	 */
	public function doRead()
	{
		global $ilDB;
		
		$result = $ilDB->query("SELECT * FROM il_dcl_data WHERE id = ".$ilDB->quote($this->getId(), "integer"));

		$data = $ilDB->fetchObject($result);
		$this->setMainTableId($data->main_table_id);
		$this->setEditType($data->edit_type);
		$this->setEditStart($data->edit_start);
		$this->setEditEnd($data->edit_end);
		$this->setOnline($data->is_online);
		$this->setRating($data->rating);
		$this->setApproval($data->approval);
		$this->setPublicNotes($data->public_notes);
		$this->setNotification($data->notification);
		$this->setEditByOwner($data->edit_by_owner);
		
	}
	

	/*
	 * doCreate
	 * Ceate a New DataCollection Object
	 */
	protected function doCreate()
	{
		global $ilDB;

		//Create Main Table - The title of the table is per default the title of the data collection object
		include_once("./Modules/DataCollection/classes/class.ilDataCollectionTable.php");
			$main_table = new ilDataCollectionTable();
			$main_table->setObjId($this->getId());
			$main_table->setTitle($this->getTitle());
			$main_table->doCreate();

		$ilDB->insert("il_dcl_data", array(
			"id" => array("integer", $this->getId()),
			"main_table_id" => array("integer", (int) $main_table->getId()),
			"is_online" => array("integer", (int) $this->getOnline()),
			"edit_type" => array("integer", (int) $this->getEditType()),
			"edit_start" => array("integer", (int) $this->getEditStart()),
			"edit_end" => array("integer", (int) $this->getEditEnd()),
			"rating" => array("integer", (int) $this->getRating()),
			"public_notes" => array("integer", (int) $this->getPublicNotes()),
			"approval" => array("integer", (int) $this->getApproval()),
			"notification" => array("integer", (int) $this->getNotification()),
			"edit_by_owner" => array("integer", (int) $this->getEditByOwner())
			));
	}
	
	protected function doDelete()
	{

		
	}
	
	 protected  function doUpdate()
	{
		global $ilDB;

		$ilDB->update("il_dcl_data", array(
			"id" => array("integer", $this->getId()),
			"main_table_id" => array("integer", (int) $this->getMainTableId()),
			"is_online" => array("integer", (int) $this->getOnline()),
			"edit_type" => array("integer", (int) $this->getEditType()),
			"edit_start" => array("date", $this->getEditStart()),
			"edit_end" => array("date", $this->getEditEnd()),
			"rating" => array("integer", (int) $this->getRating()),
			"public_notes" => array("integer", (int) $this->getPublicNotes()),
			"approval" => array("integer", (int) $this->getApproval()),
			"notification" => array("integer", (int) $this->getNotification()),
			"edit_by_owner" => array("integer", (int) $this->getEditByOwner())
			),
		array(
			"id" => array("integer", $this->getId())
			)
		);
	}	 
	
	
	/*
	 * sendNotification
	 */
	static function sendNotification($a_action, $a_ref_id)
	{
		global $ilUser, $ilAccess;
		
		// recipients
		include_once "./Services/Notification/classes/class.ilNotification.php";		
		$users = ilNotification::getNotificationsForObject(ilNotification::TYPE_DATA_COLLECTION, 
			$a_ref_id);
		if(!sizeof($users))
		{
			return;
		}
		
		ilNotification::updateNotificationTime(ilNotification::TYPE_DATA_COLLECTION, $a_ref_id, $users);
		
		
		// prepare mail content
		
//		...
	 
	  	
		// send mails
		
		include_once "./Services/Mail/classes/class.ilMail.php";
		include_once "./Services/User/classes/class.ilObjUser.php";
		include_once "./Services/Language/classes/class.ilLanguageFactory.php";
		include_once("./Services/User/classes/class.ilUserUtil.php");
				
		foreach(array_unique($users) as $idx => $user_id)
		{			
			// the user responsible for the action should not be notified
			if($user_id != $ilUser->getId() &&
				$ilAccess->checkAccessOfUser($user_id, 'read', '', $a_ref_id))
			{
				// use language of recipient to compose message
				$ulng = ilLanguageFactory::_getLanguageOfUser($user_id);
				$ulng->loadLanguageModule('dcl');

				$subject = "...";
				$message = "...";

				$mail_obj = new ilMail(ANONYMOUS_USER_ID);
				$mail_obj->appendInstallationSignature(true);
				$mail_obj->sendMail(ilObjUser::_lookupLogin($user_id),
					"", "", $subject, $message, array(), array("system"));
			}
			else
			{
				unset($users[$idx]);
			}
		}
	}
	
	/**
	 * set main Table Id
	 */
	public function setMainTableId($a_val)
	{
		$this->main_table_id = $a_val;
	}
	
	/**
	 * get main Table Id
	 */
	public function getMainTableId()
	{
		return $this->main_table_id;
	}
	
	
	/**
	 * setEditType
	 */
	public function setEditType($a_val)
	{
		$this->edit_type = $a_val;
	}
	
	/**
	 * getEditType
	 */
	public function getEditType()
	{
		return $this->edit_type;
	}
	
	/**
	 * setEditStart
	 */
	public function setEditStart($a_val)
	{
		$this->edit_start = $a_val;
	}
	
	/**
	 * getEditStart
	 */
	public function getEditStart()
	{
		return $this->edit_start;
	}
	
	/**
	 * setEditEnd
	 */
	public function setEditEnd($a_val)
	{
		$this->edit_end = $a_val;
	}
	
	/**
	 * getEditEnd
	 */
	public function getEditEnd()
	{
		return $this->edit_end;
	}
	
	/**
	 * setOnline
	 */
	public function setOnline($a_val)
	{
		$this->is_online = $a_val;
	}
	
	/**
	 * getOnline
	 */
	public function getOnline()
	{
		return $this->is_online;
	}
	
	/**
	 * setRating
	 */
	public function setRating($a_val)
	{
		$this->rating = $a_val;
	}
	
	/**
	 * getRating
	 */
	public function getRating()
	{
		return $this->rating;
	}
	
	/**
	 * setPublicNotes
	 */
	public function setPublicNotes($a_val)
	{
		$this->public_notes = $a_val;
	}
	
	/**
	 * getPublicNotes
	 */
	public function getPublicNotes()
	{
		return $this->public_notes;
	}
	
	/**
	 * setApproval
	 */
	public function setApproval($a_val)
	{
		$this->approval = $a_val;
	}
	
	/**
	 * getApproval
	 */
	public function getApproval()
	{
		return $this->approval;
	}
	
	/**
	 * setNotification
	 */
	public function setNotification($a_val)
	{
		$this->notification = $a_val;
	}
	
	/**
	 * getNotification
	 */
	public function getNotification()
	{
		return $this->notification;
	}

	/**
	 * Checks whether users with read access are allowed to edit records. (Depending on the information set in the settings tab)
	 * @return bool
	 */
	public function isRecordsEditable(){
		$perm = false;
		$now = new ilDateTime(time(), IL_CAL_UNIX);
		switch($this->getEditType())
		{
			case self::IL_DCL_EDIT_TYPE_UNLIM;
				$perm = true;
				break;
			case self::IL_DCL_EDIT_TYPE_LIM;
				if($this->getEditStart() < $now && $now <= $this->getEditEnd())
					$perm = true;
				break;
		}
		return $perm;
	}

	/**
	 * @param $edit_by_owner int 1 for true 0 for false.
	 */
	public function setEditByOwner($edit_by_owner){
		$this->edit_by_owner = $edit_by_owner;
	}

	public function getEditByOwner(){
		return $this->edit_by_owner;
	}

	function hasPermissionToAddTable(){
		return self::_checkAccess($this->getId());
	}

	public static function _checkAccess($data_collection_id){
		global $ilAccess;
		$perm = false;
		$references = self::_getAllReferences($data_collection_id);
		if($ilAccess->checkAccess("add_entry", "", array_shift($references)))
			$perm = true;
		return $perm;
	}


	public function getTables(){
		global $ilDB;
		$query = "SELECT id FROM il_dcl_table WHERE obj_id = ".$this->getId();
		$set = $ilDB->query($query);
		$return = array();
		while($rec = $ilDB->fetchAssoc($set)){
			array_push($return, new ilDataCollectionTable($rec['id']));
		}
		return $return;
	}

}

?>