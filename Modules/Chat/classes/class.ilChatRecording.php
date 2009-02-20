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
* Class ilChatRecording
* 
* @author Jens Conze 
* @version $Id$
*
*/

class ilChatRecording
{
	var $ilias;
	var $lng;

	var $error_msg;

	var $obj_id = 0; // OF CHAT OBJECT
	var $moderator_id = 0;
	var $room_id = 0;
	var $record_id = 0;

	var $data = array();

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilChatRecording($a_id = 0)
	{
		global $ilias,$lng;

		define(MAX_TIME,60*60*24);

		$this->ilias =& $ilias;
		$this->lng =& $lng;

		if ($a_id > 0)
		{
			$this->setObjId($a_id);
		}
	}

	// SET/GET
	function getErrorMessage()
	{
		return $this->error_msg;
	}

	function setRoomId($a_id)
	{
		$this->room_id = $a_id;
	}
	function getRoomId()
	{
		return $this->room_id;
	}

	function setObjId($a_id)
	{
		$this->obj_id = $a_id;
	}
	function getObjId()
	{
		return $this->obj_id;
	}

	function setRecordId($a_id)
	{
		$this->record_id = $a_id;
	}
	function getRecordId()
	{
		return $this->record_id;
	}

	function getStartTime()
	{
		return $this->data["start_time"];
	}
	function getEndTime()
	{
		return $this->data["end_time"];
	}

	function setModeratorId($a_id)
	{
		$this->moderator_id = $a_id;
	}
	function getModeratorId()
	{
		return $this->moderator_id;
	}

	function setRecord($a_data)
	{
		$this->data = $a_data;
	}
	function getRecord($a_id = 0)
	{
		global $ilDB;
		
		if ($a_id != 0)
		{
			$this->setRecordId($a_id);
		}

		$res = $ilDB->queryf('
			SELECT * FROM chat_records WHERE record_id = %s',
			array('integer'),array($this->getRecordId()));

		if (ilDB::isDbError($res)) die("ilChatRecording::getRecord(): " . $res->getMessage() . "<br>SQL-Statement: ".$res);
		
		$row = array();
		$status = false;
		
		if ($res->numRows() > 0)
		{
			$data = $res->fetchRow(DB_FETCHMODE_ASSOC);
			$status = true;
		}

		$this->setRecord($row);
		return $status;
		
	}

	function setTitle($a_title)
	{
		$this->data["title"] = $a_title;
	}
	function getTitle()
	{
		return $this->data["title"];
	}

	function setDescription($a_description)
	{
		$this->data["description"] = $a_description;
	}
	function getDescription()
	{
		return $this->data["description"];
	}

	function startRecording($a_title = "")
	{
		global $ilDB;

		$res = $ilDB->manipulateF('
			INSERT INTO chat_records 
			SET moderator_id = %s,
				chat_id = %s,
				room_id = %s,
				title =  %s,
				start_time = %s',
			array('integer', 'integer', 'integer', 'text', 'integer'),
			array($this->getModeratorId(), $this->getObjId(), $this->getRoomId(), (($a_title == "") ? "-N/A-" : $a_title), time()));
		
		if (ilDB::isDbError($res)) die("ilChatRecording::startRecording(): " . $res->getMessage() . "<br>SQL-Statement: ".$res);
		
		$res = $ilDB->query('SELECT LAST_INSERT_ID()');

		if (ilDB::isDbError($res)) die("ilChatRecording::startRecording(): " . $res->getMessage() . "<br>SQL-Statement: ".$res);
		
		if ($res->numRows() > 0)
		{
			$lastId = $res->fetchRow(DB_FETCHMODE_ASSOC);
			$this->setRecordId($lastId[0]);

			$this->getRecord();
		}				
		
	}

	function stopRecording()
	{
		global $ilDB;

		$res = $ilDB->manipulateF('
			UPDATE chat_records 
			SET end_time = %s 
			WHERE chat_id = %s 
			AND room_id = %s
			AND record_id = %s',
			array('integer', 'integer', 'integer', 'integer'),
			array(time(), $this->getObjId(), $this->getRoomId(), $this->getRecordId()));

		if (ilDB::isDbError($res)) die("ilChatRecording::startRecording(): " . $res->getMessage() . "<br>SQL-Statement: ".$res);		
		
		$this->setRecordId(0);

		$data = array();
		$this->setRecord($data);
	}

	function isRecording()
	{
		global $ilDB;
		
		$res = $ilDB->queryf('
			SELECT record_id FROM chat_records 
			WHERE chat_id = %s 
			AND room_id = %s 
			AND start_time > %s 
			AND end_time = %s',
			array('integer', 'integer', 'integer', 'integer'),
			array($this->getObjId(), $this->getRoomId(), '0', '0'));
		

		if (ilDB::isDbError($res)) die("ilChatRecording::startRecording(): " . $res->getMessage() . "<br>SQL-Statement: ".$res);	
		
		if ($res->numRows() > 0)
		{
			$id = $res->fetchRow(DB_FETCHMODE_ASSOC);
			$this->setRecordId($id["record_id"]);

			$this->getRecord();
			return true;
		}
		
		return false;
	}

	function getRecordings()
	{
		global $ilDB;

		$res = $ilDB->queryf('
			SELECT * FROM chat_records WHERE chat_id = %s',
			array('integer'), array($this->getObjId()));

		if (ilDB::isDbError($res)) die("ilChatRecording::startRecording(): " . $res->getMessage() . "<br>SQL-Statement: ".$res);	
			
		if (($num = $res->numRows()) > 0)
		{
			for ($i = 0; $i < $num; $i++)
			{
				$row[] = $res->fetchRow(DB_FETCHMODE_ASSOC);
			}
			return $row;
		}		
		
		return false;
	}

	function getModerator($a_id = 0)
	{
		if ($a_id == 0)
		{
			$a_id = $this->getModeratorId();
		}

		return ilObjUser::_lookupLogin($a_id);
	}

	function delete($a_id = 0)
	{
		global $ilDB;
		
		if ($a_id == 0 ||
			$a_id == $this->getRecordId())
		{
			$a_id = $this->getRecordId();
			$this->setRecordId(0);

			$data = array();
			$this->setRecord($data);
		}

		$res = $ilDB->manipulateF('
			DELETE FROM chat_records WHERE record_id = %s',
			array('integer'), array($a_id));

		if (ilDB::isDbError($res)) die("ilChatRecording::delete(): " . $res->getMessage() . "<br>SQL-Statement: ".$res);
		
		$res = $ilDB->manipulateF('
			DELETE FROM chat_record_data WHERE record_id = %s',
			array('integer'),array($a_id));

		if (ilDB::isDbError($res)) die("ilChatRecording::delete(): " . $res->getMessage() . "<br>SQL-Statement: ".$res);
		
		
	}

	function exportMessages()
	{
		global $ilDB;

		$res = $ilDB->queryf('
			SELECT message FROM chat_record_data 
			WHERE record_id = %s
			ORDER BY msg_time ASC',
			array('integer'),
			array($this->getRecordId()));
			
		if (ilDB::isDbError($res)) die("ilChatRecording::delete(): " . $res->getMessage() . "<br>SQL-Statement: ".$res);
		
		$html = "";
		if (($num = $res->numRows()) > 0)
		{
			$html = "";
			for ($i = 0; $i < $num; $i++)
			{
				$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
				$html .= $row["message"] . "<br />\n";
			}
		}
		
		return $html;
	}

} // END class.ilChatRecording
?>