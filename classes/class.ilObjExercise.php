<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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
* Class ilObjExercise
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @extends Object
* @package ilias-core
*/

require_once "class.ilObject.php";
require_once "./classes/class.ilFileDataExercise.php";
require_once "./classes/class.ilExerciseMembers.php";


class ilObjExercise extends ilObject
{
	var $file_obj;
	var $members_obj;
	var $files;

	var $timestamp;
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
	function setDate($a_day,$a_month,$a_year)
	{
		$this->day = (int) $a_day;
		$this->month = (int) $a_month;
		$this->year = (int) $a_year;
		$this->timestamp = mktime(0,0,0,$this->month,$this->day,$this->year);
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
		return $this->day == (int) date("d",$this->timestamp) and
			$this->month == (int) date("m",$this->timestamp) and
			$this->year == (int) date("Y",$this->timestamp);
	}

	function addUploadedFile($a_http_post_files)
	{
		$this->file_obj->storeUploadedFile($a_http_post_files);
		
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
	function clone($a_parent_ref)
	{		
		global $rbacadmin;

		// always call parent clone function first!!
		$new_ref_id = parent::clone($a_parent_ref);
		
		// put here exc specific stuff
		$tmp_obj =& $this->ilias->obj_factory->getInstanceByRefId($new_ref_id);
		$tmp_obj->setInstruction($this->getInstruction());
		$tmp_obj->setTimestamp($this->getTimestamp());
		$tmp_obj->saveData();

		// CLONE FILES
		$tmp_file_obj =& new ilFileDataExercise($this->getId());
		$tmp_file_obj->clone($tmp_obj->getId());

		// CLONE MEMBERS
		$tmp_members_obj =& new ilExerciseMembers($this->getId(),$new_ref_id);
		$tmp_members_obj->clone($tmp_obj->getId());

		// ... and finally always return new reference ID!!
		return $new_ref_id;
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


	// PRIVATE METHODS
	function __formatBody()
	{
		$body = $this->getInstruction();
		$body .= "\n";
		$body .= "Zu bearbeiten bis: ".date("Y-m-d",$this->getTimestamp());

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

} //END class.ilObjExercise
?>
