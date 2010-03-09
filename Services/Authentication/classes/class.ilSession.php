<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**   
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id:$
* 
* @externalTableAccess ilObjUser on usr_session
* @ingroup ServicesAuthentication
*/
class ilSession
{
	/**
	* Get session data from table
	*
	* @param	string		session id
	* @return	string		session data
	*/
	static function _getData($a_session_id)
	{
		global $ilDB;
		
		$q = "SELECT data FROM usr_session WHERE session_id = ".
			$ilDB->quote($a_session_id, "text");
		$set = $ilDB->query($q);
		$rec = $ilDB->fetchAssoc($set);
	
		return $rec["data"];
	}
	
	/**
	* Write session data
	*
	* @param	string		session id
	* @param	string		session data
	*/
	static function _writeData($a_session_id, $a_data)
	{
		global $ilDB;

		$expires = time() + ini_get("session.gc_maxlifetime");
		if (ilSession::_exists($a_session_id))
		{
			/*$q = "UPDATE usr_session SET ".
				"expires = ".$ilDB->quote($expires, "integer").", ".
				"data = ".$ilDB->quote($a_data, "clob").
				", ctime = ".$ilDB->quote(time(), "integer").
				", user_id = ".$ilDB->quote((int) $_SESSION["AccountId"], "integer").
				" WHERE session_id = ".$ilDB->quote($a_session_id, "text");
				array("integer", "clob", "integer", "integer", "text");
			$ilDB->manipulate($q);*/

			$ilDB->update("usr_session", array(
				"user_id" => array("integer", (int) $_SESSION["AccountId"]),
				"expires" => array("integer", $expires),
				"data" => array("clob", $a_data),
				"ctime" => array("integer", time())
				), array(
				"session_id" => array("text", $a_session_id)
				));

		}
		else
		{
			/*$q = "INSERT INTO usr_session (session_id, expires, data, ctime,user_id) ".
					"VALUES(".$ilDB->quote($a_session_id, "text").",".
					$ilDB->quote($expires, "integer").",".
					$ilDB->quote($a_data, "clob").",".
					$ilDB->quote(time(), "integer").",".
					$ilDB->quote((int) $_SESSION["AccountId"], "integer").")";
			$ilDB->manipulate($q);*/

			$ilDB->insert("usr_session", array(
				"session_id" => array("text", $a_session_id),
				"expires" => array("integer", $expires),
				"data" => array("clob", $a_data),
				"ctime" => array("integer", time()),
				"user_id" => array("integer", (int) $_SESSION["AccountId"])
				));

		}
		
		// finally delete deprecated sessions
		if(rand(1, 2) == 2)
		{
			ilSession::_destroyExpiredSessions();
		}
		
		
		
		return true;
	}

	/**
	* Check whether session exists
	*
	* @param	string		session id
	* @return	boolean		true, if session id exists
	*/
	static function _exists($a_session_id)
	{
		global $ilDB;

		$q = "SELECT session_id FROM usr_session WHERE session_id = ".
			$ilDB->quote($a_session_id, "text");
		$set = $ilDB->query($q);
		if ($ilDB->fetchAssoc($set))
		{
			return true;
		}
		return false;
	}

	/**
	* Destroy session
	*
	* @param	string		session id
	*/
	static function _destroy($a_session_id)
	{
		global $ilDB;

		$q = "DELETE FROM usr_session WHERE session_id = ".
			$ilDB->quote($a_session_id, "text");
		$ilDB->manipulate($q);

		return true;
	}

	/**
	* Destroy session
	*
	* @param	string		session id
	*/
	static function _destroyByUserId($a_user_id)
	{
		global $ilDB;

		$q = "DELETE FROM usr_session WHERE user_id = ".
			$ilDB->quote($a_user_id, "integer");
		$ilDB->manipulate($q);
		
		return true;
	}

	/**
	* Destroy expired sessions
	*/
	static function _destroyExpiredSessions()
	{
		global $ilDB;

		$q = "DELETE FROM usr_session WHERE expires < ".
			$ilDB->quote(time(), "integer");
		$ilDB->manipulate($q);

		return true;
	}
	
	/**
	* Duplicate session
	*
	* @param	string		session id
	* @return	string		new session id
	*/
	static function _duplicate($a_session_id)
	{
		global $ilDB;
	
		// Create new session id
		$new_session = $a_session_id;
		do
		{
			$new_session = md5($new_session);
			$q ="SELECT * FROM usr_session WHERE ".
				"session_id = ".$ilDB->quote($new_session, "text");
			$res = $ilDB->query($q);
		} while($ilDB->fetchAssoc($res));
		
		$query = "SELECT * FROM usr_session ".
			"WHERE session_id = ".$ilDB->quote($a_session_id, "text");
		$res = $ilDB->query($query);

		while ($row = $ilDB->fetchObject($res))
		{
			ilSession::_writeData($new_session,$row->data);
			return $new_session;
		}
		return false;
	}

}
?>