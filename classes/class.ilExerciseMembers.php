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
* @extends Object
* @package ilias-core
*/
class ilExerciseMembers
{
	var $ilias;

	var $obj_id;
	var $members;
	var $status_solved;
	var $status_sent;
	var $status_returned;
	var $notice;

	function ilExerciseMembers($a_obj_id)
	{
		global $ilias;

		$this->ilias =& $ilias;
		$this->obj_id = $a_obj_id;
	}

	// GET SET METHODS
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
		$query = "REPLACE INTO exc_members ".
			"SET obj_id = '".$this->getObjId()."', ".
			"usr_id = '".$a_usr_id."', ".
			"solved = '0', sent = '0'";

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
		$query = "DELETE FROM exc_members ".
			"WHERE obj_id = '".$this->getObjId()." '".
			"AND usr_id = '".$a_usr_id."'";

		$this->ilias->db->query($query);
		$this->read();
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
			"SET solved = '".($a_status ? 1 : 0)."' ".
			"WHERE obj_id = '".$this->getObjId()."' ".
			"AND usr_id = '".$a_member_id."'";

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
			"SET sent = '".($a_status ? 1 : 0)."' ".
			"WHERE obj_id = '".$this->getObjId()."' ".
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
		}
		$this->setMembers($tmp_arr_members);
		$this->setNotice($tmp_arr_notice);
		$this->setStatusSolved($tmp_arr_solved);
		$this->setStatusSent($tmp_arr_sent);
		$this->setStatusReturned($tmp_arr_returned);
		
		return true;
	}
	function clone($a_new_id)
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
							"sent"	 => $row->sent);
		}
		foreach($data as $row)
		{
			$query = "INSERT INTO exc_members ".
				"SET obj_id = '".$a_new_id."', ".
				"usr_id = '".$row["usr_id"]."', ".
				"notice = '".addslashes($row["notice"])."', ".
				"returned = '".$row["returned"]."', ".
				"solved = '".$row["solved"]."', ".
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
} //END class.ilObjExercise
?>
