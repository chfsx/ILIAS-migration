<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
* Class ilChangeEvent tracks change events on repository objects.
*
* The following events are considered to be a 'write event':
*  - The creation of a new repository object
*  - A change of the data or meta-data of an object
*  - A move, link, copy, deletion or undeletion of the object
* UI objects, which cause a 'write event', must call _recordWriteEvent(...)
* In most cases, UI objects let the user catch up with write events on the 
* object, when doing this call.
* 
* The following events are considered to be a 'read event':
*  - Opening a container object in the browser* 
*  - Opening / downloading / reading an object
* UI objects, which cause a 'read event', must call _recordReadEvent(...).
* In most cases, UI objects let the user catch up with write events on the 
* object, when doing this call.
*
* *reading the content of a container using WebDAV is not counted, because WebDAV
*  clients can't see all objects in a container.
*
* A user can catch up with write events, by calling __catchupWriteEvents(...).
*
* A user can query, if an object has changed, since the last time he has caught
* up with write events, by calling _lookupUncaughtWriteEvents(...).
*
*
* @author 		Werner Randelshofer <werner.randelshofer@hslu.ch>
* @version $Id: class.ilChangeEvent.php,v 1.02 2007/05/07 19:25:34 wrandels Exp $
*
*/
class ilChangeEvent
{
	/**
	 * Records a write event.
	 * 
	 * The parent object should be specified for the 'delete', 'undelete' and
	 * 'add' and 'remove' events.
	 *
	 * @param $obj_id int The object which was written to.
	 * @param $usr_id int The user who performed a write action.
	 * @param $action string The name of the write action.
	 *  'create', 'update', 'delete', 'add', 'remove', 'undelete'.        
	 * @param $parent_obj_id int The object id of the parent object.
	 *      If this is null, then the event is recorded for all parents
	 *      of the object. If this is not null, then the event is only 
	 *      recorded for the specified parent.
	 */
	function _recordWriteEvent($obj_id, $usr_id, $action, $parent_obj_id = null)
	{
		global $ilDB;
		
		if ($parent_obj_id == null)
		{
			$pset = $ilDB->query('SELECT r2.obj_id par_obj_id FROM object_reference r1 '.
				'JOIN tree t ON t.child = r1.ref_id '.
				'JOIN object_reference r2 ON r2.ref_id = t.parent '.
				'WHERE r1.obj_id = '.$ilDB->quote($obj_id,'integer'));
			
			while ($prec = $ilDB->fetchAssoc($pset))
			{
				$nid = $ilDB->nextId("write_event");
				$query = sprintf('INSERT INTO write_event '.
					'(write_id, obj_id, parent_obj_id, usr_id, action, ts) VALUES '.
					'(%s, %s, %s, %s, %s, '.$ilDB->now().')',
					$ilDB->quote($nid,'integer'),
					$ilDB->quote($obj_id,'integer'),
					$ilDB->quote($prec["par_obj_id"],'integer'),
					$ilDB->quote($usr_id,'integer'),
					$ilDB->quote($action,'text'));

				$aff = $ilDB->manipulate($query);
			}
		}
		else
		{
			$nid = $ilDB->nextId("write_event");
			$query = sprintf('INSERT INTO write_event '.
				'(write_id, obj_id, parent_obj_id, usr_id, action, ts) '.
				'VALUES (%s,%s,%s,%s,%s,'.$ilDB->now().')',
				$ilDB->quote($nid,'integer'),
				$ilDB->quote($obj_id,'integer'),
				$ilDB->quote($parent_obj_id,'integer'),
				$ilDB->quote($usr_id,'integer'),
				$ilDB->quote($action,'text'));
			$aff = $ilDB->manipulate($query);
			
		}
		//error_log ('ilChangeEvent::_recordWriteEvent '.$q);
	}
	
	/**
	 * Records a read event and catches up with write events.
	 *
	 * @param $obj_id int The object which was read.
	 * @param $usr_id int The user who performed a read action.
	 * @param $catchupWriteEvents boolean If true, this function catches up with
	 * 	write events.
	 */
	function _recordReadEvent($a_type, $a_ref_id, $obj_id, $usr_id,
		$isCatchupWriteEvents = true, $a_ext_rc = false, $a_ext_time = false)
	{
		global $ilDB, $tree;
		
		include_once('Services/Tracking/classes/class.ilObjUserTracking.php');
		$validTimeSpan = ilObjUserTracking::_getValidTimeSpan();		
		
		$query = sprintf('SELECT * FROM read_event '.
			'WHERE obj_id = %s '.
			'AND usr_id = %s ',
			$ilDB->quote($obj_id,'integer'),
			$ilDB->quote($usr_id,'integer'));
		$res = $ilDB->query($query);
		$row = $ilDB->fetchObject($res);

		// read counter
		if ($a_ext_rc !== false)
		{
			$read_count = 'read_count = '.$ilDB->quote($a_ext_rc, "integer").", ";
			$read_count_init = max(1, (int) $a_ext_rc);
			$read_count_diff = max(1, (int) $a_ext_rc) - $row->read_count;
		}
		else
		{
			$read_count = 'read_count = read_count + 1, ';
			$read_count_init = 1;
			$read_count_diff = 1;
		}
		
		if ($row)
		{
			
			if ($a_ext_time !== false)
			{
				$time = (int) $a_ext_time;
			}
			else
			{
				$time = $ilDB->quote((time() - $row->last_access) <= $validTimeSpan
							 ? $row->spent_seconds + time() - $row->last_access
							 : $row->spent_seconds,'integer');
			}
			$time_diff = $time - (int) $row->spent_seconds;
			
			// Update
			$query = sprintf('UPDATE read_event SET '.
				$read_count.
				'spent_seconds = %s, '.
				'last_access = %s '.
				'WHERE obj_id = %s '.
				'AND usr_id = %s ',
				$time,
				$ilDB->quote(time(),'integer'),
				$ilDB->quote($obj_id,'integer'),
				$ilDB->quote($usr_id,'integer'));
			$aff = $ilDB->manipulate($query);
		}			
		else
		{
			if ($a_ext_time !== false)
			{
				$time = (int) $a_ext_time;
			}
			else
			{
				$time = 0;
			}

			$time_diff = $time - (int) $row->spent_seconds;

			$query = sprintf('INSERT INTO read_event (obj_id,usr_id,last_access,read_count,spent_seconds,first_access) '.
				'VALUES (%s,%s,%s,%s,%s,'.$ilDB->now().') ',
				$ilDB->quote($obj_id,'integer'),
				$ilDB->quote($usr_id,'integer'),
				$ilDB->quote(time(),'integer'),
				$ilDB->quote($read_count_init,'integer'),
				$ilDB->quote($time,'integer'));
				
			$aff = $ilDB->manipulate($query);
		}
		
		if ($isCatchupWriteEvents)
		{
			ilChangeEvent::_catchupWriteEvents($obj_id, $usr_id);
		}

		// update parents (no categories or root)
		if (!in_array($a_type, array("cat", "root", "crs")))
		{
			if ($tree->isInTree($a_ref_id))
			{
				$path = $tree->getPathId($a_ref_id);

				foreach ($path as $p)
				{
					$obj2_id = ilObject::_lookupObjId($p);
					$obj2_type = ilObject::_lookupType($obj2_id);
//echo "<br>1-$obj2_type-$p-$obj2_id-";
					if (($p != $a_ref_id) && (in_array($obj2_type, array("crs", "fold", "grp"))))
					{
						$query = sprintf('SELECT * FROM read_event '.
							'WHERE obj_id = %s '.
							'AND usr_id = %s ',
							$ilDB->quote($obj2_id, 'integer'),
							$ilDB->quote($usr_id, 'integer'));
						$res2 = $ilDB->query($query);
						if ($row2 = $ilDB->fetchAssoc($res2))
						{
//echo "<br>2";
							// update read count and spent seconds
							$query = sprintf('UPDATE read_event SET '.
								'childs_read_count = childs_read_count + %s ,'.
								'childs_spent_seconds = childs_spent_seconds + %s '.
								'WHERE obj_id = %s '.
								'AND usr_id = %s ',
								$ilDB->quote((int) $read_count_diff,'integer'),
								$ilDB->quote((int) $time_diff,'integer'),
								$ilDB->quote($obj2_id,'integer'),
								$ilDB->quote($usr_id,'integer'));
							$aff = $ilDB->manipulate($query);
						}
						else
						{
//echo "<br>3";
							$query = sprintf('INSERT INTO read_event (obj_id,usr_id,last_access,read_count,spent_seconds,first_access,'.
								'childs_read_count, childs_spent_seconds) '.
								'VALUES (%s,%s,%s,%s,%s,'.$ilDB->now().', %s, %s) ',
								$ilDB->quote($obj2_id,'integer'),
								$ilDB->quote($usr_id,'integer'),
								$ilDB->quote(time(),'integer'),
								$ilDB->quote(1,'integer'),
								$ilDB->quote($time,'integer'),
								$ilDB->quote((int) $read_count_diff,'integer'),
								$ilDB->quote((int) $time_diff,'integer')
								);
							$aff = $ilDB->manipulate($query);
						}
					}
				}
			}
		}

		// @todo:
		// - calculate diff of spent_seconds and read_count
		// - use ref id to get parents of types grp, crs, fold
		// - add diffs to childs_spent_seconds and childs_read_count
	}
	
	/**
	 * Catches up with all write events which occured before the specified
	 * timestamp.
	 *
	 * @param $obj_id int The object.
	 * @param $usr_id int The user.
	 * @param $timestamp SQL timestamp.
	 */
	function _catchupWriteEvents($obj_id, $usr_id, $timestamp = null)
	{
		global $ilDB;
		
		$query = "SELECT obj_id FROM catch_write_events ".
			"WHERE obj_id = ".$ilDB->quote($obj_id ,'integer')." ".
			"AND usr_id  = ".$ilDB->quote($usr_id ,'integer');
		$res = $ilDB->query($query);
		if($res->numRows())
		{
			$query = "UPDATE catch_write_events ".
				"SET ts = ".($timestamp == null ? $ilDB->now() : $ilDB->quote($timestamp, 'timestamp'))." ".
				"WHERE usr_id = ".$ilDB->quote($usr_id ,'integer')." ".
				"AND obj_id = ".$ilDB->quote($obj_id ,'integer');
			$res = $ilDB->manipulate($query);
		}
		else
		{
			$query = "INSERT INTO catch_write_events (ts,obj_id,usr_id) ".
				"VALUES( ".
				$ilDB->now().", ".
				$ilDB->quote($obj_id,'integer').", ".
				$ilDB->quote($usr_id,'integer')." ".
				")";
			$res = $ilDB->manipulate($query);

		}
		
		/*
		$q = "INSERT INTO catch_write_events ".
			"(obj_id, usr_id, ts) ".
			"VALUES (".
			$ilDB->quote($obj_id,'integer').",".
			$ilDB->quote($usr_id,'integer').",";
		if ($timestamp == null)
		{
			$q .= "NOW()".
			") ON DUPLICATE KEY UPDATE ts=NOW()";
		}
		else {
			$q .= $ilDB->quote($timestamp).
			") ON DUPLICATE KEY UPDATE ts=".$ilDB->quote($timestamp);
		}
		//error_log ('ilChangeEvent::_catchupWriteEvents '.$q);
		$r = $ilDB->query($q);
		*/
	}
	/**
	 * Catches up with all write events which occured before the specified
	 * timestamp.
	 *
	 * THIS FUNCTION IS CURRENTLY NOT IN USE. BEFORE IT CAN BE USED, THE TABLE
	 * catch_read_events MUST BE CREATED.
	 *
	 *
	 *
	 * @param $obj_id int The object.
	 * @param $usr_id int The user.
	 * @param $timestamp SQL timestamp.
	 * /
	function _catchupReadEvents($obj_id, $usr_id, $timestamp = null)
	{
		global $ilDB;
		
		
		$q = "INSERT INTO catch_read_events ".
			"(obj_id, usr_id, action, ts) ".
			"VALUES (".
			$ilDB->quote($obj_id).",".
			$ilDB->quote($usr_id).",".
			$ilDB->quote('read').",";
		if ($timestamp == null)
		{
			$q .= "NOW()".
			") ON DUPLICATE KEY UPDATE ts=NOW()";
		}
		else {
			$q .= $ilDB->quote($timestamp).
			") ON DUPLICATE KEY UPDATE ts=".$ilDB->quote($timestamp);
		}
		
		$r = $ilDB->query($q);
	}
	*/
	
	
	/**
	 * Reads all write events which occured on the object
	 * which happened after the last time the user caught up with them.
	 *
	 * @param $obj_id int The object
	 * @param $usr_id int The user who is interested into these events.
	 * @return array with rows from table write_event
	 */
	public static function _lookupUncaughtWriteEvents($obj_id, $usr_id)
	{
		global $ilDB;
		
		$q = "SELECT ts ".
			"FROM catch_write_events ".
			"WHERE obj_id=".$ilDB->quote($obj_id ,'integer')." ".
			"AND usr_id=".$ilDB->quote($usr_id ,'integer');
		$r = $ilDB->query($q);
		$catchup = null;
		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC)) {
			$catchup = $row['ts'];
		}
		
		if($catchup == null)
		{
			$query = sprintf('SELECT * FROM write_event '.
				'WHERE obj_id = %s '.
				'AND usr_id <> %s '.
				'ORDER BY ts DESC',
				$ilDB->quote($obj_id,'integer'),
				$ilDB->quote($usr_id,'integer'));
			$res = $ilDB->query($query);
		}
		else
		{
			$query = sprintf('SELECT * FROM write_event '.
				'WHERE obj_id = %s '.
				'AND usr_id <> %s '.
				'AND ts >= %s '.
				'ORDER BY ts DESC',
				$ilDB->quote($obj_id,'integer'),
				$ilDB->quote($usr_id,'integer'),
				$ilDB->quote($catchup,'timestamp'));
			$res = $ilDB->query($query);
		}
		$events = array();
		while($row = $ilDB->fetchAssoc($res))
		{
			$events[] = $row;
		}
		return $events;
	}
	/**
	 * Returns the change state of the object for the specified user.
	 * which happened after the last time the user caught up with them.
	 *
	 * @param $obj_id int The object
	 * @param $usr_id int The user who is interested into these events.
	 * @return 0 = object is unchanged, 
	 *         1 = object is new,
	 *         2 = object has changed
	 */
	public static function _lookupChangeState($obj_id, $usr_id)
	{
		global $ilDB;
		
		$q = "SELECT ts ".
			"FROM catch_write_events ".
			"WHERE obj_id=".$ilDB->quote($obj_id ,'integer')." ".
			"AND usr_id=".$ilDB->quote($usr_id ,'integer');
		$r = $ilDB->query($q);
		$catchup = null;
		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC)) {
			$catchup = $row['ts'];
		}

		if($catchup == null)
		{
			$ilDB->setLimit(1);
			$query = sprintf('SELECT * FROM write_event '.
				'WHERE obj_id = %s '.
				'AND usr_id <> %s ',
				$ilDB->quote($obj_id,'integer'),
				$ilDB->quote($usr_id,'integer'));
			$res = $ilDB->query($query);
		}
		else
		{
			$ilDB->setLimit(1);
			$query = sprintf('SELECT * FROM write_event '.
				'WHERE obj_id = %s '.
				'AND usr_id <> %s '.
				'AND ts > %s ',
				$ilDB->quote($obj_id,'integer'),
				$ilDB->quote($usr_id,'integer'),
				$ilDB->quote($catchup,'timestamp'));
			$res = $ilDB->query($query);
		}

		$numRows = $res->numRows();
		if ($numRows > 0)
		{
			$row = $ilDB->fetchAssoc($res);
			// if we have write events, and user never catched one, report as new (1)
			// if we have write events, and user catched an old write event, report as changed (2)
			return ($catchup == null) ? 1 : 2;
		}
		else 
		{
			return 0; // user catched all write events, report as unchanged (0)
		}
	}
	/**
	 * Returns the changed state of objects which are children of the specified
	 * parent object.
	 *
	 * Note this gives a different result than calling _lookupChangeState of
	 * each child object. This is because, this function treats a catch on the
	 * write events on the parent as a catch up for all child objects.
	 * This difference was made, because it greatly improves performance
	 * of this function. 
	 *
	 * @param $parent_obj_id int The object id of the parent object.
	 * @param $usr_id int The user who is interested into these events.
	 * @return 0 = object has not been changed inside
	 *         1 = object has been changed inside
	 */
	public static function _lookupInsideChangeState($parent_obj_id, $usr_id)
	{
		global $ilDB;
		
		$q = "SELECT ts ".
			"FROM catch_write_events ".
			"WHERE obj_id=".$ilDB->quote($parent_obj_id)." ".
			"AND usr_id=".$ilDB->quote($usr_id);
		$r = $ilDB->query($q);
		$catchup = null;
		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC)) {
			$catchup = $row['ts'];
		}

		if($catchup == null)
		{
			$ilDB->setLimit(1);
			$query = sprintf('SELECT * FROM write_event '.
				'WHERE parent_obj_id = %s '.
				'AND usr_id <> %s ',
				$ilDB->quote($parent_obj_id,'integer'),
				$ilDB->quote($usr_id,'integer'));
			$res = $ilDB->query($query);
		}
		else
		{
			$ilDB->setLimit(1);
			$query = sprintf('SELECT * FROM write_event '.
				'WHERE parent_obj_id = %s '.
				'AND usr_id <> %s '.
				'AND ts > %s ',
				$ilDB->quote($parent_obj_id,'integer'),
				$ilDB->quote($usr_id,'integer'),
				$ilDB->quote($catchup,'timestamp'));
			$res = $ilDB->query($query);
		}
		$numRows = $res->numRows();
		if ($numRows > 0)
		{
			$row = $ilDB->fetchAssoc($res);
			// if we have write events, and user never catched one, report as new (1)
			// if we have write events, and user catched an old write event, report as changed (2)
			return ($catchup == null) ? 1 : 2;
		}
		else 
		{
			return 0; // user catched all write events, report as unchanged (0)
		}
	}
	/**
	 * Reads all read events which occured on the object 
	 * which happened after the last time the user caught up with them.
	 *
	 * NOTE: THIS FUNCTION NEEDS TO BE REWRITTEN. READ EVENTS ARE OF INTEREST
	 * AT REF_ID's OF OBJECTS. 
	 *
	 * @param $obj_id int The object
	 * @param $usr_id int The user who is interested into these events.
	 * /
	public static function _lookupUncaughtReadEvents($obj_id, $usr_id)
	{
		global $ilDB;
		
		$q = "SELECT ts ".
			"FROM catch_read_events ".
			"WHERE obj_id=".$ilDB->quote($obj_id)." ".
			"AND usr_id=".$ilDB->quote($usr_id);
		$r = $ilDB->query($q);
		$catchup = null;
		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC)) {
			$catchup = $row['ts'];
		}
		
		$q = "SELECT * ".
			"FROM read_event ".
			"WHERE obj_id=".$ilDB->quote($obj_id)." ".
			($catchup == null ? "" : "AND last_access > ".$ilDB->quote($catchup))." ".
			($catchup == null ? "" : "AND last_access > ".$ilDB->quote($catchup))." ".
			"ORDER BY last_access DESC";
		$r = $ilDB->query($q);
		$events = array();
		while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$events[] = $row;
		}
		return $events;
	}*/
	/**
	 * Reads all read events which occured on the object.
	 *
	 * @param $obj_id int The object
	 * @param $usr_id int Optional, the user who performed these events.
	 */
	public static function _lookupReadEvents($obj_id, $usr_id = null)
	{
		global $ilDB;
		
		if ($usr_id == null)
		{
			$query = sprintf('SELECT * FROM read_event '.
				'WHERE obj_id = %s '.
				'ORDER BY last_access DESC',
				$ilDB->quote($obj_id,'integer'));
			$res = $ilDB->query($query);
		}
		else 
		{
			$query = sprintf('SELECT * FROM read_event '.
				'WHERE obj_id = %s '.
				'AND usr_id = %s '.
				'ORDER BY last_access DESC',
				$ilDB->quote($obj_id,'integer'),
				$ilDB->quote($usr_id,'integer'));
			$res = $ilDB->query($query);
		}

		$counter = 0;
		while ($row = $ilDB->fetchAssoc($res))
		{
			$events[$counter]['obj_id'] 		= $row['obj_id'];
			$events[$counter]['usr_id'] 		= $row['usr_id'];
			$events[$counter]['last_access'] 	= $row['last_access'];
			$events[$counter]['read_count'] 	= $row['read_count'];
			$events[$counter]['spent_seconds'] 	= $row['spent_seconds'];
			$events[$counter]['first_access'] 	= $row['first_access'];
			
			$counter++;
			 
		}
		return $events ? $events : array();
	}
	
	/**
	 * Lookup users in progress 
	 *
	 * @return
	 * @static
	 */
	 public static function lookupUsersInProgress($a_obj_id)
	 {
	 	global $ilDB;
	 	
	 	$query = sprintf('SELECT DISTINCT(usr_id) usr FROM read_event '.
	 		'WHERE obj_id = %s ',
	 		$ilDB->quote($a_obj_id,'integer'));
	 	$res = $ilDB->query($query);
	 	while($row = $ilDB->fetchObject($res))
	 	{
	 		$users[] = $row->usr;
	 	}
	 	return $users ? $users : array();
	 }
	 
	 /**
	  * Has accessed
	  */
	 static function hasAccessed($a_obj_id, $a_usr_id)
	 {
		global $ilDB;
	 
		$set = $ilDB->query("SELECT usr_id FROM read_event WHERE ".
			"obj_id = ".$ilDB->quote($a_obj_id, "integer")." AND ".
			"usr_id = ".$ilDB->quote($a_usr_id, "integer")
			);
		if ($rec = $ilDB->fetchAssoc($set))
		{
			return true;
		}
		return false;
	 }

	/**
	 * Activates change event tracking.
	 *
	 * @return mixed true on success, a string with an error message on failure.
	 */
	public static function _activate() {
		if (ilChangeEvent::_isActive())
		{
			return 'change event tracking is already active';
		}
		else
		{
			global $ilDB;

			// Insert initial data into table write_event
			// We need to do this here, because we need
			// to catch up write events that occured while the change event tracking was
			// deactivated.

			// IGNORE isn't supported in oracle
			$set = $ilDB->query(sprintf('SELECT r1.obj_id,r2.obj_id p,d.owner,%s,d.create_date '.
				'FROM object_data d '.
				'LEFT JOIN write_event w ON d.obj_id = w.obj_id '.
				'JOIN object_reference r1 ON d.obj_id=r1.obj_id '.
				'JOIN tree t ON t.child=r1.ref_id '.
				'JOIN object_reference r2 on r2.ref_id=t.parent '.
				'WHERE w.obj_id IS NULL',
				$ilDB->quote('create','text')));
			while ($rec = $ilDB->fetchAssoc($set))
			{
				$nid = $ilDB->nextId("write_event");
				$query = 'INSERT INTO write_event '.
					'(write_id, obj_id,parent_obj_id,usr_id,action,ts) VALUES ('.
					$ilDB->quote($nid, "integer").",".
					$ilDB->quote($rec["obj_id"], "integer").",".
					$ilDB->quote($rec["p"], "integer").",".
					$ilDB->quote($rec["owner"], "integer").",".
					$ilDB->quote("create", "text").",".
					$ilDB->quote($rec["create_date"], "timestamp").
					')';
				$res = $ilDB->query($query);
			}
			
			if ($ilDB->isError($res) || $ilDB->isError($res->result))
			{
				return 'couldn\'t insert initial data into table "write_event": '.
				(($ilDB->isError($r->result)) ? $r->result->getMessage() : $r->getMessage());
			}


			global $ilias;
			$ilias->setSetting('enable_change_event_tracking', '1');

			return $res;
		}
	}

	/**
	 * Deactivates change event tracking.
	 *
	 * @return mixed true on success, a string with an error message on failure.
	 */
	public static function _deactivate() {
		global $ilias;
		$ilias->setSetting('enable_change_event_tracking', '0');
		
	}

	/**
	 * Returns true, if change event tracking is active.
	 *
	 * @return mixed true on success, a string with an error message on failure.
	 */
	public static function _isActive() {
		global $ilias;
		return $ilias->getSetting('enable_change_event_tracking', '0') == '1';
		
	}
	
	/**
	 * Delete object entries
	 *
	 * @return
	 * @static
	 */
	public static function _delete($a_obj_id)
	{
		global $ilDB;
		
		$query = sprintf('DELETE FROM write_event WHERE obj_id = %s ',
			$ilDB->quote($a_obj_id,'integer'));
		$aff = $ilDB->manipulate($query);
		
		$query = sprintf('DELETE FROM read_event WHERE obj_id = %s ',
			$ilDB->quote($a_obj_id,'integer'));
		$aff = $ilDB->manipulate($query);
		return true;
	}
}
?>
