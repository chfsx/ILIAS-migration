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
* Class ilExerciseMembers
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @ingroup ModulesExercise
*/
class ilExerciseMembers
{
	var $ilias;

	var $ref_id;
	var $obj_id;
	var $members;
	var $status_solved;
	var $status_feedback;
	var $status_sent;
	var $status_returned;
	var $notice;

	function ilExerciseMembers($a_obj_id,$a_ref_id)
	{
		global $ilias;

		$this->ilias =& $ilias;
		$this->obj_id = $a_obj_id;
		$this->ref_id = $a_ref_id;
	}

	// GET SET METHODS
	function getRefId()
	{
		return $this->ref_id;
	}
	function getObjId()
	{
		return $this->obj_id;
	}
	function setObjId($a_obj_id)
	{
		$this->obj_id = $a_obj_id;
	}
	function getMembers()
	{
		return $this->members ? $this->members : array();
	}
	function setMembers($a_members)
	{
		$this->members = $a_members;
	}
	function assignMember($a_usr_id)
	{
		$tmp_user = ilObjectFactory::getInstanceByObjId($a_usr_id);
		$tmp_user->addDesktopItem($this->getRefId(),"exc");


		$query = "REPLACE INTO exc_members ".
			"SET obj_id = '".$this->getObjId()."', ".
			"usr_id = '".$a_usr_id."', ".
			"solved = '0', sent = '0', feedback='0'";

		$res = $this->ilias->db->query($query);
		$this->read();

		return true;
	}
	function isAssigned($a_id)
	{
		return in_array($a_id,$this->getMembers());
	}

	function assignMembers($a_members)
	{
		$assigned = 0;
		if(is_array($a_members))
		{
			foreach($a_members as $member)
			{
				if(!$this->isAssigned($member))
				{
					$this->assignMember($member);
				}
				else
				{
					++$assigned;
				}
			}
		}
		if($assigned == count($a_members))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	function deassignMember($a_usr_id)
	{
		$tmp_user = ilObjectFactory::getInstanceByObjId($a_usr_id);
		$tmp_user->dropDesktopItem($this->getRefId(),"exc");

		$query = "DELETE FROM exc_members ".
			"WHERE obj_id = '".$this->getObjId()." '".
			"AND usr_id = '".$a_usr_id."'";

		$this->ilias->db->query($query);
		$this->read();
		// delete all delivered files of the member
		$delivered_files =& $this->getDeliveredFiles($a_usr_id);
		$files_to_delete = array();
		$userfile = "";
		foreach ($delivered_files as $key => $value)
		{
			array_push($files_to_delete, $value["returned_id"]);
			$userfile = $value["filename"];
		}
		$this->deleteDeliveredFiles($files_to_delete, $a_usr_id);
		// delete the user directory if existing
		if ($userfile)
		{
			$pathinfo = pathinfo($userfile);
			$dir = $pathinfo["dirname"];
		}
		if (is_dir($dir))
		{
			rmdir($dir);
		}
		return false;
	}
	
	function deassignMembers($a_members)
	{
		if(is_array($a_members))
		{
			foreach($a_members as $member)
			{
				$this->deassignMember($member);
			}
		}
		else
		{
			return false;
		}
	}
	function setStatusSolved($a_status)
	{
		if(is_array($a_status))
		{
			$this->status_solved = $a_status;
			return true;
		}
	}
	function getStatusSolved()
	{
		return $this->status_solved ? $this->status_solved : array(0 => 0);
	}
	function getStatusSolvedByMember($a_member_id)
	{
		if(isset($this->status_solved[$a_member_id]))
		{
			return $this->status_solved[$a_member_id];
		}
		return false;
	}
	function setStatusSolvedForMember($a_member_id,$a_status)
	{

	  $query = "UPDATE exc_members ".
	    "SET solved = '".($a_status ? 1 : 0)."', ".
	    "solved_time=".($a_status ? ("'".date("Y-m-d H:i:s")."'") : ("'0000-00-00 00:00:00'")).
	    " WHERE obj_id = '".$this->getObjId()."' ".
	    "AND usr_id = '".$a_member_id."'".
		" AND solved <> '".($a_status ? 1 : 0)."'";
	  
	  $this->ilias->db->query($query);
	  $this->read();
	  
	  return true;
	}
	
	function setStatusSent($a_status)
	{
		if(is_array($a_status))
		{
			$this->status_sent = $a_status;
			return true;
		}
	}
	function getStatusSent()
	{
		return $this->status_sent ? $this->status_sent : array(0 => 0);
	}
	function getStatusSentByMember($a_member_id)
	{
		if(isset($this->status_sent[$a_member_id]))
		{
			return $this->status_sent[$a_member_id];
		}
		return false;
	}
	function setStatusSentForMember($a_member_id,$a_status)
	{
		$query = "UPDATE exc_members ".
			"SET sent = '".($a_status ? 1 : 0)."', ".
			"sent_time=".($a_status ? ("'".date("Y-m-d H:i:s")."'") : ("'0000-00-00 00:00:00'")).
			" WHERE obj_id = '".$this->getObjId()."' ".
			"AND usr_id = '".$a_member_id."'";

		$this->ilias->db->query($query);
		$this->read();

		return true;
	}

	function getStatusReturned()
	{
		return $this->status_returned ? $this->status_returned : array(0 => 0);
	}
	function setStatusReturned($a_status)
	{
		if(is_array($a_status))
		{
			$this->status_returned = $a_status;
			return true;
		}
		return false;
	}

	function getStatusReturnedByMember($a_member_id)
	{
		if(isset($this->status_returned[$a_member_id]))
		{
			return $this->status_returned[$a_member_id];
		}
		return false;
	}
	function setStatusReturnedForMember($a_member_id,$a_status)
	{
		$query = "UPDATE exc_members ".
			"SET returned = '".($a_status ? 1 : 0)."' ".
			"WHERE obj_id = '".$this->getObjId()."' ".
			"AND usr_id = '".$a_member_id."'";

		$this->ilias->db->query($query);
		$this->read();

		return true;
	}
	
	// feedback functions
	function setStatusFeedback($a_status)
	{
		if(is_array($a_status))
		{
			$this->status_feedback = $a_status;
			return true;
		}
	}
	function getStatusFeedback()
	{
		return $this->status_feedback ? $this->status_feedback : array(0 => 0);
	}
	function getStatusFeedbackByMember($a_member_id)
	{
		if(isset($this->status_feedback[$a_member_id]))
		{
			return $this->status_feedback[$a_member_id];
		}
		return false;
	}
	function setStatusFeedbackForMember($a_member_id,$a_status)
	{

	  $query = "UPDATE exc_members ".
	    "SET feedback = '".($a_status ? 1 : 0)."', ".
	    "feedback_time=".($a_status ? ("'".date("Y-m-d H:i:s")."'") : ("'0000-00-00 00:00:00'")).
	    " WHERE obj_id = '".$this->getObjId()."' ".
	    "AND usr_id = '".$a_member_id."'".
		" AND feedback <> '".($a_status ? 1 : 0)."'";
//echo "<br/><br/>$query";
	  $this->ilias->db->query($query);
	  $this->read();
	  
	  return true;
	}

	function getNotice()
	{
		return $this->notice ? $this->notice : array(0 => 0);
	}

	function setNotice($a_notice)
	{
		if(is_array($a_notice))
		{
			$this->notice = $a_notice;
			return true;
		}
		return false;
	}

	function getNoticeByMember($a_member_id)
	{
		if(isset($this->notice[$a_member_id]))
		{
			return $this->notice[$a_member_id];
		}
		else
		{
			return "";
		}
	}

	function hasReturned($a_member_id)
	{
		$query = sprintf("SELECT returned_id FROM exc_returned WHERE obj_id = %s AND user_id = %s",
			$this->ilias->db->quote($this->getObjId() . ""),
			$this->ilias->db->quote($a_member_id . "")
		);
		$result = $this->ilias->db->query($query);
		return $result->numRows();
	}

	function getAllDeliveredFiles()
	{
		$query = "SELECT * FROM exc_returned WHERE obj_id = '".$this->getObjId()."'";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$delivered[] = $row;
		}
		return $delivered ? $delivered : array();
	}
			
	/**
	* Returns an array of all delivered files of an user
	*
	* @param numeric $a_member_id The user id
	* @access	public
	* @return array An array containing the information on the delivered files
	*/
	function &getDeliveredFiles($a_member_id)
	{
		$query = sprintf("SELECT *, TIMESTAMP + 0 AS TIMESTAMP14 FROM exc_returned WHERE obj_id = %s AND user_id = %s ORDER BY TIMESTAMP14",
			$this->ilias->db->quote($this->getObjId() . ""),
			$this->ilias->db->quote($a_member_id . "")
		);
		$result = $this->ilias->db->query($query);
		$delivered_files = array();
		if ($result->numRows())
		{
			while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
			{
				array_push($delivered_files, $row);
			}
		}
		return $delivered_files;
	}
	
	/**
	* Deletes already delivered files
	* @param array $file_id_array An array containing database ids of the delivered files
	* @param numeric $a_member_id The database id of the user
	* @access	public
	*/
	function deleteDeliveredFiles($file_id_array, $a_member_id)
	{
		if (count($file_id_array))
		{
			$query = sprintf("SELECT * FROM exc_returned WHERE user_id = %s AND returned_id IN (" . join($file_id_array, ",") . ")",
				$this->ilias->db->quote($a_member_id . "")
			);
			$result = $this->ilias->db->query($query);
			if ($result->numRows())
			{
				$result_array = array();
				while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
				{
					array_push($result_array, $row);
				}
				// delete the entries in the database
				$query = sprintf("DELETE FROM exc_returned WHERE user_id = %s AND returned_id IN (" . join($file_id_array, ",") . ")",
					$this->ilias->db->quote($a_member_id . "")
				);
				$result = $this->ilias->db->query($query);
				// delete the files
				foreach ($result_array as $key => $value)
				{
					unlink($value["filename"]);
				}
			}
		}
	}

	/**
	* Delivers the returned files of an user
	* @param numeric $a_member_id The database id of the user
	* @access	public
	*/
	function deliverReturnedFiles($a_member_id, $a_only_new = false)
	{
		global $ilUser, $ilDB;
		
		// get last download time
		$and_str = "";
		if ($a_only_new)
		{
			$q = "SELECT download_time FROM exc_usr_tutor WHERE ".
				" obj_id = ".$ilDB->quote($this->getObjId())." AND ".
				" usr_id = ".$ilDB->quote($a_member_id)." AND ".
				" tutor_id = ".$ilDB->quote($ilUser->getId());
			$lu_set = $ilDB->query($q);
			if ($lu_rec = $lu_set->fetchRow(DB_FETCHMODE_ASSOC))
			{
				if ($lu_rec["download_time"] > 0)
				{
					$and_str = " AND timestamp > ".$ilDB->quote($lu_rec["download_time"]);
				}
			}
		}
		
		$this->updateTutorDownloadTime($a_member_id);
		
		$query = sprintf("SELECT * FROM exc_returned WHERE obj_id = %s AND user_id = %s".
			$and_str,
			$this->ilias->db->quote($this->getObjId() . ""),
			$this->ilias->db->quote($a_member_id . "")
		);
		$result = $this->ilias->db->query($query);
		$count = $result->numRows();
		if ($count == 1)
		{
			$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
			$this->downloadSingleFile($row["filename"], $row["filetitle"]);
		}
		else
		{
			$array_files = array();
			$filename = "";
			while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
			{
				$filename = $row["filename"];
				$pathinfo = pathinfo($filename);
				$dir = $pathinfo["dirname"];
				$file = $pathinfo["basename"];
				array_push($array_files, $file);
			}
			$pathinfo = pathinfo($filename);
			$dir = $pathinfo["dirname"];
			$this->downloadMultipleFiles($array_files, $dir);
		}
	}
	
	/**
	* Update the timestamp of the last download of current user (=tutor)
	* for member $a_member_id.
	*
	* @param	int		$a_member_id	Member ID.
	*/
	function updateTutorDownloadTime($a_member_id)
	{
		global $ilUser, $ilDB;
		
		// set download time
		$q = "REPLACE INTO exc_usr_tutor (obj_id, usr_id, tutor_id, download_time) VALUES ".
			"(".$ilDB->quote($this->getObjId()).",".$ilDB->quote($a_member_id).
			",".$ilDB->quote($ilUser->getId()).",now())";
		$ilDB->query($q);
	}

	function downloadSelectedFiles($array_file_id)
	{
		if (count($array_file_id))
		{
			$query = "SELECT * FROM exc_returned WHERE returned_id IN (" . join($array_file_id, ",") . ")";
			$result = $this->ilias->db->query($query);
			if ($result->numRows())
			{
				$array_found = array();
				while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
				{
					array_push($array_found, $row);
				}
				if (count($array_found) == 1)
				{
					$this->downloadSingleFile($array_found[0]["filename"], $array_found[0]["filetitle"]);
				}
				else
				{
					$filenames = array();
					$dir = "";
					$file = "";
					foreach ($array_found as $key => $value)
					{
						$pathinfo = pathinfo($value["filename"]);
						$dir = $pathinfo["dirname"];
						$file = $pathinfo["basename"];
						array_push($filenames, $file); 
					}
					$this->downloadMultipleFiles($filenames, $dir);
				}
			}
		}
	}
	
	function downloadSingleFile($filename, $filetitle)
	{
		require_once "./classes/class.ilUtil.php";
		ilUtil::deliverFile($filename, $filetitle);
	}
	
	function downloadMultipleFiles($array_filenames, $pathname)
	{
		global $lng;
		require_once "./classes/class.ilUtil.php";
		$cdir = getcwd();
		chdir($pathname);
		$zip = PATH_TO_ZIP;
		$tmpfile = ilUtil::ilTempnam();
		$tmpzipfile = $tmpfile . ".zip";
		foreach ($array_filenames as $key => $filename)
		{
			$array_filenames[$key] = ilUtil::escapeShellArg($array_filenames[$key]);
		}
		$zipcmd = $zip." ".ilUtil::escapeShellArg($tmpzipfile)." ".join($array_filenames, " ");
		exec($zipcmd);
		ilUtil::deliverFile($tmpzipfile, strtolower($lng->txt("excs")) . ".zip");
		chdir($cdir);
		unlink($tmpfile);
		unlink($tmpzipfile);
	}
	
	function setNoticeForMember($a_member_id,$a_notice)
	{

		$query = "UPDATE exc_members ".
			"SET notice = '".addslashes($a_notice)."' ".
			"WHERE obj_id = '".$this->getObjId()."' ".
			"AND usr_id = '".$a_member_id."'";

		$this->ilias->db->query($query);
		$this->read();

		return true;
	}
/*
	function update()
	{
		$save_members = $this->getMembers();
		$save_notice = $this->getNotice();
		$saved_st_solved = $this->getStatusSolved();
		$saved_st_sent = $this->getStatusSent();
		$saved_st_return = $this->getStatusReturned();

		$this->read();

		// UPDATE MEMBERS
		foreach(array_diff($this->getMembers(),$save_members) as $member)
		{
			$query  = "DELETE FROM exc_members ".
				"WHERE obj_id = '".$this->getObjId()."' ".
				"AND usr_id = '".$member."'";
			$this->ilias->db->query($query);
		}
		foreach(array_diff($save_members,$this->getMembers()) as $member)
		{
			$query  = "INSERT INTO exc_members ".
				"SET obj_id = '".$this->getObjId()."', ".
				"usr_id = '".$member."', ".
				"sent = '0', ".
				"solved = '0'";
			$this->ilias->db->query($query);
		}
		$this->setMembers($save_members);
		$this->setNotice($save_notice);
		$this->setStatusSent($saved_st_sent);
		$this->setStatusSolved($saved_st_solved);
		$this->setStatusReturned($saved_st_return);

		
		// UPDATE SOLVED AND SENT
		foreach($this->getMembers() as $member)
		{
			$query = "UPDATE exc_members ".
				"SET solved = '".$this->getStatusSolvedByMember($member)."', ".
				"notice = '".addslashes($this->getNoticeByMember($member))."', ".
				"returned = '".$this->getStatusReturnedByMember($member)."', ".
			    "sent = '".$this->getStatusSentByMember($member)."'";
			$this->ilias->db->query($query);
		}
		return true;
	}
*/
	function read()
	{
		$tmp_arr_members = array();
		$tmp_arr_solved = array();
		$tmp_arr_sent = array();
		$tmp_arr_notice = array();
		$tmp_arr_returned = array();
		$tmp_arr_feedback = array();

		$query = "SELECT * FROM exc_members ".
			"WHERE obj_id = '".$this->getObjId()."' ";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$tmp_arr_members[] = $row->usr_id;
			$tmp_arr_notice[$row->usr_id] = $row->notice;
			$tmp_arr_returned[$row->usr_id] = $row->returned;
			$tmp_arr_solved[$row->usr_id] = $row->solved;
			$tmp_arr_sent[$row->usr_id] = $row->sent;
			$tmp_arr_feedback[$row->usr_id] = $row->feedback;
		}
		$this->setMembers($tmp_arr_members);
		$this->setNotice($tmp_arr_notice);
		$this->setStatusSolved($tmp_arr_solved);
		$this->setStatusSent($tmp_arr_sent);
		$this->setStatusReturned($tmp_arr_returned);
		$this->setStatusFeedback($tmp_arr_feedback);
		
		return true;
	}
	function ilClone($a_new_id)
	{
		$data = array();

		$query = "SELECT * FROM exc_members ".
			"WHERE obj_id = '".$this->getObjId()."'";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$data[] = array("usr_id" => $row->usr_id,
							"notice" => $row->notice,
							"returned" => $row->returned,
							"solved" => $row->solved,
							"sent"	 => $row->sent,
							"feedback"	 => $row->feedback
							);
		}
		foreach($data as $row)
		{
			$query = "INSERT INTO exc_members ".
				"SET obj_id = '".$a_new_id."', ".
				"usr_id = '".$row["usr_id"]."', ".
				"notice = '".addslashes($row["notice"])."', ".
				"returned = '".$row["returned"]."', ".
				"solved = '".$row["solved"]."', ".
				"feedback = '".$row["feedback"]."', ".
				"sent = '".$row["sent"]."'";

			$res = $this->ilias->db->query($query);
		}
		return true;
	}

	function delete()
	{
		$query = "DELETE FROM exc_members WHERE obj_id = '".$this->getObjId()."'";
		$this->ilias->db->query($query);

		return true;
	}

	function _hasSolved($a_exc_id,$a_usr_id)
	{
		global $ilDB;

		// check if user is owner
		$query = "SELECT owner FROM object_data ".
			"WHERE obj_id = '".$a_exc_id."'";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if($row->owner == $a_usr_id)
			{
				return true;
			}
		}

		// check if user has solved exercise
		$query = "SELECT solved FROM exc_members ".
			"WHERE obj_id = '".$a_exc_id."' ".
			"AND usr_id = '".$a_usr_id."'";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if($row->solved == 1)
			{
				return true;
			}
		}
		return false;
	}

	function _getMembers($a_obj_id)
	{
		global $ilDB;

		$query = "SELECT DISTINCT(usr_id) as ud FROM exc_members ".
			"WHERE obj_id = '".$a_obj_id."'";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$usr_ids[] = $row->ud;
		}

		return $usr_ids ? $usr_ids : array();
	}

	function _getReturned($a_obj_id)
	{
		global $ilDB;

		$query = "SELECT DISTINCT(usr_id) as ud FROM exc_members ".
			"WHERE obj_id = '".$a_obj_id."' ".
			"AND returned = 1";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$usr_ids[] = $row->ud;
		}

		return $usr_ids ? $usr_ids : array();
	}
	
	function _getSolved($a_obj_id)
	{
		global $ilDB;

		$query = "SELECT DISTINCT(usr_id) as ud FROM exc_members ".
			"WHERE obj_id = '".$a_obj_id."' ".
			"AND solved = 1";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$usr_ids[] = $row->ud;
		}

		return $usr_ids ? $usr_ids : array();
	}

} //END class.ilObjExercise
?>
