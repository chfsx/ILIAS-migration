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

require_once "classes/class.ilObject.php";
require_once "./Modules/Exercise/classes/class.ilFileDataExercise.php";
require_once "./Modules/Exercise/classes/class.ilExerciseMembers.php";

/** @defgroup ModulesExercise Modules/Exercise
 */

/**
* Class ilObjExercise
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
*
* @ingroup ModulesExercise
*/
class ilObjExercise extends ilObject
{
	var $file_obj;
	var $members_obj;
	var $files;

	var $timestamp;
	var $hour;
	var $minutes;
	var $day;
	var $month;
	var $year;
	var $instruction;

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjExercise($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "exc";
		$this->ilObject($a_id,$a_call_by_reference);
	}

	// SET, GET METHODS
	function setDate($a_hour,$a_minutes,$a_day,$a_month,$a_year)
	{
		$this->hour = (int) $a_hour;
		$this->minutes = (int) $a_minutes;
		$this->day = (int) $a_day;
		$this->month = (int) $a_month;
		$this->year = (int) $a_year;
		$this->timestamp = mktime($this->hour,$this->minutes,0,$this->month,$this->day,$this->year);
		return true;
	}
	function getTimestamp()
	{
		return $this->timestamp;
	}
	function setTimestamp($a_timestamp)
	{
		$this->timestamp = $a_timestamp;
	}
	function setInstruction($a_instruction)
	{
		$this->instruction = $a_instruction;
	}
	function getInstruction()
	{
		return $this->instruction;
	}
	function getFiles()
	{
		return $this->files;
	}

	function checkDate()
	{
		return	$this->hour == (int) date("H",$this->timestamp) and
			$this->minutes == (int) date("i",$this->timestamp) and
			$this->day == (int) date("d",$this->timestamp) and
			$this->month == (int) date("m",$this->timestamp) and
			$this->year == (int) date("Y",$this->timestamp);

	}

	function deliverFile($a_http_post_files, $user_id)
	{
		$deliver_result = $this->file_obj->deliverFile($a_http_post_files, $user_id);
		if ($deliver_result)
		{
			$query = sprintf("INSERT INTO exc_returned ".
							 "(returned_id, obj_id, user_id, filename, filetitle, mimetype, TIMESTAMP) ".
							 "VALUES (NULL, %s, %s, %s, %s, %s, NULL)",
				$this->ilias->db->quote($this->getId() . ""),
				$this->ilias->db->quote($user_id . ""),
				$this->ilias->db->quote($deliver_result["fullname"]),
				$this->ilias->db->quote($a_http_post_files["name"]),
				$this->ilias->db->quote($deliver_result["mimetype"])
			);
			$this->ilias->db->query($query);
			if (!$this->members_obj->isAssigned($user_id))
			{
				$this->members_obj->assignMember($user_id);
			}
			$this->members_obj->setStatusReturnedForMember($user_id, 1);
		}
		return true;
	}

	function addUploadedFile($a_http_post_files)
	{
		$this->file_obj->storeUploadedFile($a_http_post_files, true);
		
		return true;
	}
	function deleteFiles($a_files)
	{
		$this->file_obj->unlinkFiles($a_files);
	}

	function saveData()
	{
		
		// SAVE ONLY EXERCISE SPECIFIC DATA
		$query = "INSERT INTO exc_data SET ".
			"obj_id = '".$this->getId()."', ".
			"instruction = '".addslashes($this->getInstruction())."', ".
			"time_stamp = ".$this->getTimestamp();
		$this->ilias->db->query($query);
		return true;
	}

	/**
	* copy all properties and subobjects of a course.
	* 
	* @access	public
	* @return	integer	new ref id
	*/
	function ilClone($a_parent_ref)
	{		
		global $rbacadmin;

		// always call parent ilClone function first!!
		$new_ref_id = parent::ilClone($a_parent_ref);
		
		// put here exc specific stuff
		$tmp_obj =& $this->ilias->obj_factory->getInstanceByRefId($new_ref_id);
		$tmp_obj->setInstruction($this->getInstruction());
		$tmp_obj->setTimestamp($this->getTimestamp());
		$tmp_obj->saveData();

		// CLONE FILES
		$tmp_file_obj =& new ilFileDataExercise($this->getId());
		$tmp_file_obj->ilClone($tmp_obj->getId());

		// CLONE MEMBERS
		$tmp_members_obj =& new ilExerciseMembers($this->getId(),$new_ref_id);
		$tmp_members_obj->ilClone($tmp_obj->getId());

		// ... and finally always return new reference ID!!
		return $new_ref_id;
	}

	/**
	* Returns the delivered files of an user
	* @param numeric $user_id The database id of the user
	* @return array An array containing the information on the delivered files
	* @access	public
	*/
	function &getDeliveredFiles($user_id)
	{
		$delivered_files =& $this->members_obj->getDeliveredFiles($user_id);
		return $delivered_files;
	}

	/**
	* Deletes already delivered files
	* @param array $file_id_array An array containing database ids of the delivered files
	* @param numeric $user_id The database id of the user
	* @access	public
	*/
	function deleteDeliveredFiles($file_id_array, $user_id)
	{
		$this->members_obj->deleteDeliveredFiles($file_id_array, $user_id);

		// Finally update status 'returned' of member if no file exists
		if(!count($this->members_obj->getDeliveredFiles($user_id)))
		{
			$this->members_obj->setStatusReturnedForMember($user_id,0);
		}

	}
	
	/**
	* Delivers the returned files of an user
	* @param numeric $user_id The database id of the user
	* @access	public
	*/
	function deliverReturnedFiles($user_id)
	{
		require_once "./Services/Utilities/classes/class.ilUtil.php";
	}

	/**
	* delete course and all related data	
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{		
		// always call parent delete function first!!
		if (!parent::delete())
		{
			return false;
		}	
		// put here course specific stuff
		$query = "DELETE FROM exc_data ".
			"WHERE obj_id = '".$this->getId()."'";
		
		$this->ilias->db->query($query);

		$this->file_obj->delete();
		$this->members_obj->delete();

		return true;
	}

	/**
	* notifys an object about an event occured
	* Based on the event happend, each object may decide how it reacts.
	* 
	* @access	public
	* @param	string	event
	* @param	integer	reference id of object where the event occured
	* @param	array	passes optional paramters if required
	* @return	boolean
	*/
	function notify($a_event,$a_ref_id,$a_node_id,$a_params = 0)
	{
		// object specific event handling
		
		parent::notify($a_event,$a_ref_id,$a_node_id,$a_params);
	}

	function read()
	{
		parent::read();

		$query = "SELECT * FROM exc_data ".
			"WHERE obj_id = '".$this->getId()."'";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->setInstruction($row->instruction);
			$this->setTimestamp($row->time_stamp);
		}
		$this->members_obj =& new ilExerciseMembers($this->getId(),$this->getRefId());
		$this->members_obj->read();

		// GET FILE ASSIGNED TO EXERCISE
		$this->file_obj = new ilFileDataExercise($this->getId());
		$this->files = $this->file_obj->getFiles();

		return true;
	}

	function update()
	{
		parent::update();

		$query = "UPDATE exc_data SET ".
			"instruction = '".addslashes($this->getInstruction())."', ".
			"time_stamp = '".$this->getTimestamp()."' ".
			"WHERE obj_id = '".$this->getId()."'";

		$res = $this->ilias->db->query($query);

		#$this->members_obj->update();
		return true;
	}
	
	/**
	* get member list data
	*/
	function getMemberListData()
	{
		global $ilDB;
		
		$mem = array();
		$q = "SELECT * FROM exc_members ".
			"WHERE obj_id = ".$ilDB->quote($this->getId());
		$set = $ilDB->query($q);
		while($rec = $set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if (ilObject::_exists($rec["usr_id"]) &&
				(ilObject::_lookupType($rec["usr_id"]) == "usr"))
			{
				$name = ilObjUser::_lookupName($rec["usr_id"]);
				$login = ilObjUser::_lookupLogin($rec["usr_id"]);
				$mem[] =
					array("name" => $name["lastname"].", ".$name["firstname"],
					"login" => $login,
					"sent_time" => $rec["sent_time"],
					"submission" => $this->getLastSubmission($rec["usr_id"]),
					"status_time" => $rec["status_time"],
					"feedback_time" => $rec["feedback_time"],
					"usr_id" => $rec["usr_id"]
					);
			}
		}
		return $mem;
	}

	/**
	* Get the date of the last submission of a user for the exercise.
	*
	* @param	int		$member_id	User ID of member.
	* @return	mixed	false or mysql timestamp of last submission
	*/
	function getLastSubmission($member_id) 
	{
		global $ilDB, $lng;

		$q="SELECT obj_id,user_id,timestamp FROM exc_returned ".
		"WHERE obj_id =".$this->getId()." AND user_id=".$member_id.
		" ORDER BY timestamp DESC";

		$usr_set = $ilDB->query($q);

		$array=$usr_set->fetchRow(DB_FETCHMODE_ASSOC);
		if ($array["timestamp"]==NULL) 
		{
			return false;
  		}
		else 
		{
			return ilUtil::getMySQLTimestamp($array["timestamp"]);
  		}  
	}

	/**
	* send exercise per mail to members
	*/
	function send($a_members)
	{
		$files = $this->file_obj->getFiles();
		if(count($files))
		{
			include_once "./classes/class.ilFileDataMail.php";

			$mfile_obj = new ilFileDataMail($_SESSION["AccountId"]);
			foreach($files as $file)
			{
				$mfile_obj->copyAttachmentFile($this->file_obj->getAbsolutePath($file["name"]),$file["name"]);
				$file_names[] = $file["name"];
			}
		}

		include_once "./classes/class.ilMail.php";

		$tmp_mail_obj = new ilMail($_SESSION["AccountId"]);
		$message = $tmp_mail_obj->sendMail($this->__formatRecipients($a_members),"","",$this->__formatSubject(),$this->__formatBody(),
										   count($file_names) ? $file_names : array(),array("normal"));

		unset($tmp_mail_obj);

		if(count($file_names))
		{
			$mfile_obj->unlinkFiles($file_names);
			unset($mfile_obj);
		}


		// SET STATUS SENT FOR ALL RECIPIENTS
		foreach($a_members as $member_id => $value)
		{
			$this->members_obj->setStatusSentForMember($member_id,1);
		}

		return true;
	}

	/**
	* Check whether student has upload new files after tutor has
	* set the exercise to another than notgraded.
	*/
	function _lookupUpdatedSubmission($exc_id, $member_id) 
	{

  		global $ilDB, $lng;

  		$q="SELECT exc_members.status_time, exc_returned.timestamp ".
			"FROM exc_members, exc_returned ".
			"WHERE exc_members.status_time < exc_returned.timestamp ".
			"AND exc_members.status_time <> '0000-00-00 00:00:00' ".
			"AND exc_returned.obj_id = exc_members.obj_id ".
			"AND exc_returned.user_id = exc_members.usr_id ".
			"AND exc_returned.obj_id='".$exc_id."' AND exc_returned.user_id='".$member_id."'";

  		$usr_set = $ilDB->query($q);

  		$array=$usr_set->fetchRow(DB_FETCHMODE_ASSOC);

		if (count($array)==0) 
		{
			return 0;
  		}
		else 
		{
			return 1;
		}

	}


	/**
	* Check whether exercise has been sent to any student per mail.
	*/
	function _lookupAnyExerciseSent($a_exc_id)
	{
  		global $ilDB;

  		$q = "SELECT count(*) AS cnt FROM exc_members".
			" WHERE sent_time <> '0000-00-00 00:00:00'".
			" AND obj_id = ".$ilDB->quote($a_exc_id);
		$set = $ilDB->query($q);
		$rec = $set->fetchRow(DB_FETCHMODE_ASSOC);
		
		if ($rec["cnt"] > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	* Check how much files have been uploaded by the learner
	* after the last download of the tutor.
	*/
	function _lookupNewFiles($exc_id, $member_id) 
	{
  		global $ilDB, $ilUser;

  		$q = "SELECT exc_returned.returned_id AS id ".
			"FROM exc_usr_tutor, exc_returned ".
			"WHERE exc_returned.obj_id = exc_usr_tutor.obj_id ".
			"AND exc_returned.user_id = exc_usr_tutor.usr_id ".
			"AND exc_returned.obj_id = ".$ilDB->quote($exc_id).
			"AND exc_returned.user_id = ".$ilDB->quote($member_id).
			"AND exc_usr_tutor.tutor_id = ".$ilDB->quote($ilUser->getId()).
			"AND exc_usr_tutor.download_time < exc_returned.timestamp ";

  		$new_up_set = $ilDB->query($q);

		$new_up = array();
  		while ($new_up_rec = $new_up_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$new_up[] = $new_up_rec["id"];
		}

		return $new_up;
	}

	/**
	* Get time when exercise has been set to solved.
	*/
	function _lookupStatusTime($exc_id, $member_id) 
	{

  		global $ilDB, $lng;

  		$q = "SELECT * ".
		"FROM exc_members ".
		"WHERE obj_id='".$exc_id."' AND usr_id='".$member_id."'";

  		$set = $ilDB->query($q);
		if ($rec = $set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			return ilUtil::getMySQLTimestamp($rec["status_time"]);
		}
	}

	/**
	* Get time when exercise has been sent per e-mail to user
	*/
	function _lookupSentTime($exc_id, $member_id) 
	{

  		global $ilDB, $lng;

  		$q = "SELECT * ".
		"FROM exc_members ".
		"WHERE obj_id='".$exc_id."' AND usr_id='".$member_id."'";

  		$set = $ilDB->query($q);
		if ($rec = $set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			return ilUtil::getMySQLTimestamp($rec["sent_time"]);
		}
	}

	/**
	* Get time when feedback mail has been sent.
	*/
	function _lookupFeedbackTime($exc_id, $member_id) 
	{

  		global $ilDB, $lng;

  		$q = "SELECT * ".
		"FROM exc_members ".
		"WHERE obj_id='".$exc_id."' AND usr_id='".$member_id."'";

  		$set = $ilDB->query($q);
		if ($rec = $set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			return ilUtil::getMySQLTimestamp($rec["feedback_time"]);
		}
	}

	// PRIVATE METHODS
	function __formatBody()
	{
		global $lng;

		$body = $this->getInstruction();
		$body .= "\n\n";
		$body .= $lng->txt("exc_edit_until") . ": ".
			ilFormat::formatDate(date("Y-m-d H:i:s",$this->getTimestamp()), "datetime", true);
		$body .= "\n\n";
		$body .= ILIAS_HTTP_PATH.
			"/goto.php?target=".
			$this->getType().
			"_".$this->getRefId()."&client_id=".CLIENT_ID;

		return $body;
	}

	function __formatSubject()
	{
		return $subject = $this->getTitle()." (".$this->getDescription().")";
	}

	function __formatRecipients($a_members)
	{
		foreach($a_members as $member_id => $value)
		{
			$tmp_obj = ilObjectFactory::getInstanceByObjId($member_id); 
			$tmp_members[] = $tmp_obj->getLogin();

			unset($tmp_obj);
		}

		return implode(',',$tmp_members ? $tmp_members : array());
	}

	function _checkCondition($a_exc_id,$a_operator,$a_value)
	{
		global $ilias;

		switch($a_operator)
		{
			case 'passed':
				if (ilExerciseMembers::_lookupStatus($a_exc_id, $ilias->account->getId()) == "passed")
				{
					return true;
				}
				else
				{
					return false;
				}
				break;

			default:
				return true;
		}
		return true;
	}
		
} //END class.ilObjExercise
?>
