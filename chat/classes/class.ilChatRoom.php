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
* Class ilChatUser
* 
* @author Stefan Meyer 
* @version $Id$
*
* @package chat
*/

class ilChatRoom
{
	var $ilias;
	var $lng;

	var $error_msg;

	var $ref_id; // OF CHAT OBJECT
	var $owner_id;
	var $room_id;
	var $guests;
	var $title;
	
	var $user_id;

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilChatRoom($a_id)
	{
		global $ilias,$lng;

		define(MAX_LINES,1000);

		$this->ilias =& $ilias;
		$this->lng =& $lng;

		$this->ref_id = $a_id;
		$this->owner_id = $a_owner_id;
		$this->user_id = $_SESSION["AccountId"];
	}

	// SET/GET
	function getErrorMessage()
	{
		return $this->error_msg;
	}

	function setRoomId($a_id)
	{
		$this->room_id = $a_id;
		
		// READ DATA OF ROOM
		$this->__read();
	}
	function getRoomId()
	{
		return $this->room_id;
	}
	function getRefId()
	{
		return $this->ref_id;
	}
	function setOwnerId($a_id)
	{
		$this->owner_id = $a_id;
	}
	function getOwnerId()
	{
		return $this->owner_id;
	}
	
	function getName()
	{
		if(!$this->getRoomId())
		{
			return $this->getRefId();
		}
		else
		{
			// GET NAME OF PRIVATE CHATROOM
		}
	}
	function setTitle($a_title)
	{
		$this->title = $a_title;
	}
	function getTitle()
	{
		return $this->title;
	}
	function getGuests()
	{
		return $this->guests ? $this->guests : array();
	}
	function setUserId($a_id)
	{
		$this->user_id = $a_id;
	}
	function getUserId()
	{
		return $this->user_id;
	}

	function invite($a_id)
	{
		$query = "REPLACE INTO chat_invitations ".
			"SET room_id = '".$this->getRoomId()."',".
			"guest_id = '".$a_id."'";

		$res = $this->ilias->db->query($query);
	}
	function drop($a_id)
	{
		$query = "DELETE FROM chat_invitations ".
			"WHERE room_id = '".$this->getRoomId()."' ".
			"AND guest_id = '".$a_id."'";

		$res = $this->ilias->db->query($query);
	}

	function checkAccess()
	{
		if($this->getRoomId())
		{
			if(!$this->isInvited($this->getUserId()) and !$this->isOwner())
			{
				$this->setRoomId(0);
				return false;
			}
		}
		return true;
	}

	function isInvited($a_id)
	{
		$query = "SELECT * FROM chat_invitations AS ci JOIN chat_rooms AS ca ".
			"WHERE ci.room_id = ca.room_id ".
			"AND ci.room_id = '".$this->getRoomId()."' ".
			"AND owner = '".$this->getOwnerId()."' ".
			"AND ci.guest_id = '".$a_id."'";

		$res = $this->ilias->db->query($query);
		
		return $res->numRows() ? true : false;
	}
	function isOwner()
	{
		return $this->getOwnerId() == $this->getUserId();
	}

	// METHODS FOR EXPORTTING CHAT
	function appendMessageToDb($message)
	{
		if($this->__getCountLines() >= MAX_LINES)
		{
			$this->__deleteFirstLine();
		}
		$this->__addLine($message);

		return true;
	}
	function getAllMessages()
	{
		$query = "SELECT message FROM chat_room_messages ".
			"WHERE chat_id = '".$this->getRefId()."' ".
			"AND room_id = '".$this->getRoomId()."' ".
			"ORDER BY commit_timestamp ";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$data[] = $row->message;
		}
		return is_array($data) ? implode("<br />",$data) : "";
	}
	function deleteAllMessages()
	{
		$query = "DELETE FROM chat_room_messages ".
			"WHERE chat_id = '".$this->getRefId()."' ".
			"AND room_id = '".$this->getRoomId()."'";

		$res = $this->ilias->db->query($query);

		return true;
	}

	function updateLastVisit()
	{
		// CHECK IF OLD DATA EXISTS
		$query = "SELECT * FROM chat_user ".
			"WHERE usr_id = '".$this->getUserId()."' ".
			"AND chat_id = '".$this->getRefId()."' ".
			"AND room_id = '".$this->getRoomId()."'";

		$res = $this->ilias->db->query($query);
		if($res->numRows())
		{
 			$query = "UPDATE chat_user ".
				"SET last_conn_timestamp = now() ".
				"WHERE usr_id = '".$this->getUserId()."' ".
				"AND room_id = '".$this->getRoomId()."' ".
				"AND chat_id = '".$this->getRefId()."'";
		}
		else
		{
			$query = "INSERT INTO chat_user ".
				"SET usr_id = '".$this->getUserId()."', ".
				"room_id = '".$this->getRoomId()."', ".
				"chat_id = '".$this->getRefId()."', ".
				"last_conn_timestamp = now()";
		}
		$res = $this->ilias->db->query($query);
		return true;
	}
	function getCountActiveUser($chat_id,$room_id)
	{
		$query = "SELECT * FROM chat_user ".
			"WHERE chat_id = '".$chat_id."' ".
			"AND room_id = '".$room_id."' ".
			"AND last_conn_timestamp > now() - 100";
		$res = $this->ilias->db->query($query);

		return $res->numRows();
	}		

	function getActiveUsers()
	{
		$query = "SELECT * FROM chat_user ".
			"WHERE chat_id = '".$this->ref_id."' ".
			"AND room_id = '".$this->room_id."' ".
			"AND last_conn_timestamp > now() - 100";
		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$usr_ids[] = $row->usr_id;
		}
		return $usr_ids ? $usr_ids : array();
	}

	function getOnlineUsers()
	{
		// TODO: CHECK INVITABLE AND ALLOW MESSAGES 
		return ilUtil::getUsersOnline();
	}

	function validate()
	{
		$this->error_msg = "";

		if(!$this->getTitle())
		{
			$this->error_msg .= $this->lng->txt("!!chat_title_missing");
		}
		if(!$this->getOwnerId())
		{
			$this->ilias->raiseError("MISSING OWNER ID",$this->ilias->error_obj->FATAL);
		}
		return $this->error_msg ? false : true;
	}
	function deleteRooms($a_ids)
	{
		if(!is_array($a_ids))
		{
			$this->ilias->raiseError("ARRAY REQUIRED",$this->ilias->error_obj->FATAL);
		}
		foreach($a_ids as $id)
		{
			$this->delete($id);
		}
		return true;
	}

	function delete($a_id)
	{
		// DELETE ROOM
		$query = "DELETE FROM chat_rooms WHERE ".
			"room_id = '".$a_id."' ".
			"AND owner = '".$this->getOwnerId()."' ".
			"AND chat_id = '".$this->getRefId()."'";
		$res = $this->ilias->db->query($query);

		// DELETE INVITATIONS
		$query = "DELETE FROM chat_invitations WHERE ".
			"room_id = '".$a_id."'";
		$res = $this->ilias->db->query($query);

		// DELETE MESSAGES
		$query = "DELETE FROM chat_room_messages WHERE ".
			"room_id = '".$a_id."'";
		$res = $this->ilias->db->query($query);

		// DELETE USER_DATA
		$query = "DELETE FROM chat_user WHERE ".
			"room_id = '".$a_id."' ".
			"AND chat_id = '".$this->getRefId()."'";
		$res = $this->ilias->db->query($query);
			
		return true;
	}

	function rename()
	{
		$query = "UPDATE chat_rooms ".
			"SET title = '".ilUtil::prepareDBString($this->getTitle())."' ".
			"WHERE room_id = '".$this->getRoomId()."'";

		$res = $this->ilias->db->query($query);

		return true;
	}

	function add()
	{
		$query = "INSERT INTO chat_rooms ".
			"SET title = '".ilUtil::prepareDBString($this->getTitle())."', ".
			"chat_id = '".$this->getRefId()."', ".
			"owner = '".$this->getOwnerId()."'";

		$res = $this->ilias->db->query($query);

		return true;
	}

	function getInternalName()
	{
		if(!$this->getRoomId())
		{
			return $this->getRefId();
		}
		else
		{
			return $this->getRefId()."_".$this->getRoomId();
		}
	}
	function getRooms()
	{
		$query = "SELECT DISTINCT(cr.room_id) as room_id,owner,title,chat_id FROM chat_rooms AS cr NATURAL LEFT JOIN chat_invitations ".
			"WHERE (owner = '".$this->getUserId()."') ".
			"OR (guest_id = '".$this->getUserId()."')";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$data[$row->room_id]["room_id"] = $row->room_id;
			$data[$row->room_id]["chat_id"] = $row->chat_id;
			$data[$row->room_id]["owner"] = $row->owner;
			$data[$row->room_id]["title"] = $row->title;
		}
		return $data ? $data : array();
	}
		


	function getRoomsOfObject()
	{
		$query = "SELECT * FROM chat_rooms ".
			"WHERE chat_id = '".$this->getRefId()."' ".
			"AND owner = '".$this->getUserId()."'";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$data[$row->room_id]["room_id"] = $row->room_id;
			$data[$row->room_id]["owner"] = $row->owner;
			$data[$row->room_id]["title"] = $row->title;
			$data[$row->room_id]["owner"] = $row->owner;
		}
		return $data ? $data : array();
	}
			

	function getAllRooms()
	{
		return ilUtil::getObjectsByOperations("chat","read");
	}

	function checkWriteAccess()
	{
		if(!$this->getRoomId())
		{
			return true;
		}
		if($this->getUserId() == $this->getOwnerId())
		{
			return true;
		}
		if($this->isInvited($this->getUserId()))
		{
			return true;
		}
		return false;
	}

	// PRIVATE
	function __getCountLines()
	{
		$query = "SELECT COUNT(entry_id) as number_lines FROM chat_room_messages ".
			"WHERE chat_id = '".$this->getRefId()."' ".
			"AND room_id = '".$this->getRoomId()."'";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->number_lines;
		}
		return 0;
	}
	function __deleteFirstLine()
	{
		$query = "SELECT entry_id, MIN(commit_timestamp) as last_comm FROM chat_room_messages ".
			"WHERE chat_id = '".$this->getRefId()."' ".
			"AND room_id = '".$this->getRoomId()."' ".
			"GROUP BY null";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$entry_id = $row->entry_id;
		}
		if($entry_id)
		{
			$query = "DELETE FROM chat_room_messages ".
				"WHERE entry_id = '".$entry_id."'";
			
			$res = $this->ilias->db->query($query);
		}
		return true;
	}
	function __addLine($message)
	{
		$query = "INSERT INTO chat_room_messages ".
			"VALUES('0','".$this->getRefId()."','".$this->getRoomId()."','".addslashes($message)."',now())";
		
		$res = $this->ilias->db->query($query);

		return true;
	}


	function __read()
	{
		$this->guests = array();

		$query = "SELECT * FROM chat_rooms ".
			"WHERE room_id = '".$this->getRoomId()."'";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->setTitle($row->title);
			$this->setOwnerId($row->owner);
		}

		$query = "SELECT * FROM chat_invitations ".
			"WHERE room_id = '".$this->getRoomId()."'";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->guests[] = $row->guest_id;
		}
		return true;
	}

} // END class.ilChatRoom
?>