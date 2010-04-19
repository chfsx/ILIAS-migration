<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilObjSCORMTracking
*
* @author Alex Killing <alex.killing@gmx.de>
*
* @ingroup ModulesScormAicc
*/
class ilObjSCORMTracking
{
	/**
	* Constructor
	* @access	public
	*/
	function ilObjSCORMTracking()
	{
		global $ilias;

	}

	function extractData()
	{
		$this->insert = array();
		if (is_array($_GET["iL"]))
		{
			foreach($_GET["iL"] as $key => $value)
			{
				$this->insert[] = array("left" => $value, "right" => $_GET["iR"][$key]);
			}
		}
		if (is_array($_POST["iL"]))
		{
			foreach($_POST["iL"] as $key => $value)
			{
				$this->insert[] = array("left" => $value, "right" => $_POST["iR"][$key]);
			}
		}

		$this->update = array();
		if (is_array($_GET["uL"]))
		{
			foreach($_GET["uL"] as $key => $value)
			{
				$this->update[] = array("left" => $value, "right" => $_GET["uR"][$key]);
			}
		}
		if (is_array($_POST["uL"]))
		{
			foreach($_POST["uL"] as $key => $value)
			{
				$this->update[] = array("left" => $value, "right" => $_POST["uR"][$key]);
			}
		}
	}

	function store($obj_id=0, $sahs_id=0, $extractData=1)
	{
		global $ilDB, $ilUser;
		
		if (empty($obj_id))
		{
			$obj_id = ilObject::_lookupObjId($_GET["ref_id"]);
		}
		
		// writing to scorm test log
		$f = fopen("./Modules/ScormAicc/log/scorm.log", "a");
		fwrite($f, "\nCALLING SCORM store()\n");
		fwrite($f,'POST: '.print_r($_POST,true));
		
		
		if (empty($sahs_id))
			$sahs_id = ($_GET["sahs_id"] != "")	? $_GET["sahs_id"] : $_POST["sahs_id"];
			
		if ($extractData==1)
			$this->extractData();

		if (is_object($ilUser))
		{
			$user_id = $ilUser->getId();
		}

		

		if ($obj_id <= 1)
		{
			fwrite($f, "Error: No obj_id given.\n");
		}
		else
		{
			foreach($this->insert as $insert)
			{		
				$set = $ilDB->queryF('
				SELECT * FROM scorm_tracking 
				WHERE user_id = %s
				AND sco_id =  %s
				AND lvalue =  %s
				AND obj_id = %s',
				array('integer','integer','text','integer'), 
				array($user_id,$sahs_id,$insert["left"],$obj_id));
				if ($rec = $ilDB->fetchAssoc($set))
				{
					fwrite($f, "Error Insert, left value already exists. L:".$insert["left"].",R:".
						$insert["right"].",sahs_id:".$sahs_id.",user_id:".$user_id."\n");
				}
				else
				{
					$ilDB->insert('scorm_tracking', array(
						'obj_id'		=> array('integer', $obj_id),
						'user_id'		=> array('integer', $user_id),
						'sco_id'		=> array('integer', $sahs_id),
						'lvalue'		=> array('text', $insert["left"]),
						'rvalue'		=> array('clob', $insert["right"]),
						'c_timestamp'	=> array('timestamp', ilUtil::now())
					));
										
					fwrite($f, "Insert - L:".$insert["left"].",R:".
						$insert["right"].",sahs_id:".$sahs_id.",user_id:".$user_id."\n");
				}
			}
			foreach($this->update as $update)
			{

				$set = $ilDB->queryF('
				SELECT * FROM scorm_tracking 
				WHERE user_id = %s
				AND sco_id =  %s
				AND lvalue =  %s
				AND obj_id = %s',
				array('integer','integer','text','integer'), 
				array($user_id,$sahs_id,$update["left"],$obj_id));
				
				if ($rec = $ilDB->fetchAssoc($set))
				{
					$ilDB->update('scorm_tracking',
						array(
							'rvalue'		=> array('clob', $update["right"]),
							'c_timestamp'	=> array('timestamp', ilUtil::now())
						),
						array(
							'user_id'		=> array('integer', $user_id),
							'sco_id'		=> array('integer', $sahs_id),
							'lvalue'		=> array('text', $update["left"]),
							'obj_id'		=> array('integer', $obj_id)
						)
					);
				}
				else
				{
					fwrite($f, "ERROR Update, left value does not exist. L:".$update["left"].",R:".
						$update["right"].",sahs_id:".$sahs_id.",user_id:".$user_id."\n");
				}
				
			}
		}
		fclose($f);
		
		// update status
		include_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");	
		ilLPStatusWrapper::_updateStatus($obj_id, $user_id);
		
		// update time and numbers of attempts in change event
		ilObjSCORMTracking::_syncReadEvent($obj_id, $user_id);
	}
	
	/**
	 * Synch read event table
	 *
	 * @param
	 * @return
	 */
	function _syncReadEvent($a_obj_id, $a_user_id)
	{
		global $ilDB, $ilLog;

		// get attempts
		$val_set = $ilDB->queryF('
		SELECT * FROM scorm_tracking 
		WHERE user_id = %s
		AND sco_id = %s
		AND lvalue = %s
		AND obj_id = %s',
		array('integer','integer','text','integer'),
		array($a_user_id,0,'package_attempts',$a_obj_id));
		
		$val_rec = $ilDB->fetchAssoc($val_set);
		
		$val_rec["rvalue"] = str_replace("\r\n", "\n", $val_rec["rvalue"]);
		if ($val_rec["rvalue"] == null) {
			$val_rec["rvalue"]="";
		}
		$attempts = $val_rec["rvalue"];

		// get learning time
		$sco_set = $ilDB->queryF('
		SELECT sco_id, rvalue FROM scorm_tracking 
		WHERE obj_id = %s
		AND user_id = %s
		AND lvalue = %s
		AND sco_id <> %s',
		array('integer','integer','text','integer'),
		array($a_obj_id,$a_user_id, 'cmi.core.total_time',0));

		$time = 0;
		while($sco_rec = $ilDB->fetchAssoc($sco_set))		
		{
			$tarr = explode(":", $sco_rec["rvalue"]);
			$sec = (int) $tarr[2] + (int) $tarr[1] * 60 +
				(int) substr($tarr[0], strlen($tarr[0]) - 3) * 60 * 60;
			$time += $sec;
		}
		
		include_once("./Services/Tracking/classes/class.ilChangeEvent.php");
		ilChangeEvent::_recordReadEvent($a_obj_id, $a_user_id, false, $attempts, $time);
	}

	function _insertTrackData($a_sahs_id, $a_lval, $a_rval, $a_obj_id)
	{
		global $ilDB, $ilUser;

		$ilDB->insert('scorm_tracking', array(
			'obj_id'		=> array('integer', $a_obj_id),
			'user_id'		=> array('integer', $ilUser->getId()),
			'sco_id'		=> array('integer', $a_sahs_id),
			'lvalue'		=> array('text', $a_lval),
			'rvalue'		=> array('clob', $a_rval),
			'c_timestamp'	=> array('timestamp', ilUtil::now())
		));
		
		if ($a_lval == "cmi.core.lesson_status")
		{
			include_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");	
			ilLPStatusWrapper::_updateStatus($a_obj_id, $ilUser->getId());
		}
	}


	/**
	 * Redesign required
	 * @todo avoid like search against clob field rvalue
	 * @deprecated
	 * @param object $scorm_item_id
	 * @param object $a_obj_id
	 * @return 
	 */
	public static function _getInProgress($scorm_item_id,$a_obj_id)
	{
		global $ilDB;
		
		if(is_array($scorm_item_id))
		{
			$in = $ilDB->in('sco_id', $scorm_item_id, false, 'integer');
			
			$res = $ilDB->queryF('SELECT user_id,sco_id FROM scorm_tracking
			WHERE '.$in.'
			AND obj_id = %s 
			GROUP BY user_id, sco_id',
			array('integer'),array($a_obj_id));
			   
		}
		else
		{
			$res = $ilDB->queryF('SELECT user_id,sco_id FROM scorm_tracking			
			WHERE sco_id = %s 
			AND obj_id = %s',
			array('integer','integer'),array($scorm_item_id,$a_obj_id)
			);
		}
		
		while($row = $ilDB->fetchObject($res))
		{
			$in_progress[$row->sco_id][] = $row->user_id;
		}
		return is_array($in_progress) ? $in_progress : array();
	}

	/**
	 * Redesign required
	 * @todo avoid like search against clob field rvalue
	 * @deprecated
	 * @param object $scorm_item_id
	 * @param object $a_obj_id
	 * @return 
	 */
	public static function _getCompleted($scorm_item_id,$a_obj_id)
	{
		global $ilDB;

		if(is_array($scorm_item_id))
		{
			$in = $ilDB->in('sco_id', $scorm_item_id, false, 'integer');
			
			$res = $ilDB->queryF('SELECT DISTINCT(user_id) FROM scorm_tracking 
			WHERE '.$in.'
			AND obj_id = %s
			AND lvalue = %s 
			AND ('.$ilDB->like('rvalue', 'clob', 'completed').' OR '.$ilDB->like('rvalue', 'clob', 'passed').')',
			array('integer','text'), 
			array($a_obj_id,'cmi.core.lesson_status'));
		}
		else
		{	
			$res = $ilDB->queryF('SELECT DISTINCT(user_id) FROM scorm_tracking 
			WHERE sco_id = %s
			AND obj_id = %s
			AND lvalue = %s 
			AND ('.$ilDB->like('rvalue', 'clob', 'completed').' OR '.$ilDB->like('rvalue', 'clob', 'passed').')',
			array('integer','integer','text'), 
			array($scorm_item_id,$a_obj_id,'cmi.core.lesson_status'));
		}
		
		while($row = $ilDB->fetchObject($res))
		{
			$user_ids[] = $row->user_id;
		}
		return $user_ids ? $user_ids : array();
	}

	public static function _getCollectionStatus($a_scos, $a_obj_id, $a_user_id)
	{
		global $ilDB;


		$status = "not_attempted";
		
		if (is_array($a_scos))
		{
			$in = $ilDB->in('sco_id', $a_scos, false, 'integer');
			
			$res = $ilDB->queryF('SELECT sco_id, rvalue FROM scorm_tracking 
			WHERE '.$in.'
			AND obj_id = %s
			AND lvalue = %s
			AND user_id = %s',
			array('integer','text', 'integer'),
			array($a_obj_id,'cmi.core.lesson_status', $a_user_id));
			
			$cnt = 0;
			$completed = true;
			$failed = false;
			while ($rec = $ilDB->fetchAssoc($res))
			{
				if ($rec["rvalue"] == "failed")
				{
					$failed = true;
				}
				if ($rec["rvalue"] != "completed" && $rec["rvalue"] != "passed")
				{
					$completed = false;
				}
				$cnt++;
			}
			if ($cnt > 0)
			{
				$status = "in_progress";
			}
			if ($completed && $cnt == count($a_scos))
			{
				$status = "completed";
			}
			if ($failed)
			{
				$status = "failed";
			}

		}
		return $status;
	}

	public static function _countCompleted($a_scos, $a_obj_id, $a_user_id)
	{
		global $ilDB;

		if (is_array($a_scos))
		{
			$in = $ilDB->in('sco_id', $a_scos, false, 'integer');
			
			$res = $ilDB->queryF('SELECT sco_id, rvalue FROM scorm_tracking 
			WHERE '.$in.'
			AND obj_id = %s
			AND lvalue = %s
			AND user_id = %s',
			array('integer','text', 'integer'),
			array($a_obj_id,'cmi.core.lesson_status', $a_user_id));
			
			$cnt = 0;
			while ($rec = $ilDB->fetchAssoc($res))
			{
				if ($rec["rvalue"] == "completed" || $rec["rvalue"] == "passed")
				{
					$cnt++;
				}
			}
		}
		return $cnt;
	}

	/**
	 * Get all tracked users
	 * @param object $a_obj_id
	 * @return 
	 */
	function _getTrackedUsers($a_obj_id)
	{
		global $ilDB, $ilLog;

		$res = $ilDB->queryF('SELECT DISTINCT user_id FROM scorm_tracking 
			WHERE obj_id = %s
			AND lvalue = %s',
			array('integer','text'),
			array($a_obj_id,'cmi.core.lesson_status'));
		
		$users = array();
		while ($row = $ilDB->fetchAssoc($res))
		{
			$users[] = $row["user_id"];
		}
		return $users;
	}

	/**
	 * Redesign required
	 * @todo avoid like search against clob field rvalue 
	 * @deprecated
	 * @param object $scorm_item_id
	 * @param object $a_obj_id
	 * @return 
	 */
	function _getFailed($scorm_item_id,$a_obj_id)
	{
		global $ilDB;

		if(is_array($scorm_item_id))
		{
			$in = $ilDB->in('sco_id', $scorm_item_id, false, 'integer');
			
			$res = $ilDB->queryF('
				SELECT DISTINCT(user_id) FROM scorm_tracking 
				WHERE '.$in.'
				AND obj_id = %s
				AND lvalue =  %s
				AND '.$ilDB->like('rvalue', 'clob', 'failed').' ',
			array('integer','text'),
			array($a_obj_id,'cmi.core.lesson_status'));				
		}
		else
		{
			
			$res = $ilDB->queryF('
				SELECT DISTINCT(user_id) FROM scorm_tracking 
				WHERE sco_id = %s
				AND obj_id = %s
				AND lvalue =  %s
				AND '.$ilDB->like('rvalue', 'clob', 'failed').' ',
			array('integer','integer','text'),
			array($scorm_item_id,$a_obj_id,'cmi.core.lesson_status'));
		}

		while($row = $ilDB->fetchObject($res))
		{
			$user_ids[] = $row->user_id;
		}
		return $user_ids ? $user_ids : array();
	}

	/**
	 * Get users who have status completed or passed.
	 * @param object $a_scorm_item_ids
	 * @param object $a_obj_id
	 * @return 
	 */
	public static function _getCountCompletedPerUser($a_scorm_item_ids,$a_obj_id)
	{
		global $ilDB;
		
		$in = $ilDB->in('sco_id', $a_scorm_item_ids, false, 'integer');

		// Why does this query use a like search against "passed" and "failed"
		/*
		$res = $ilDB->queryF('
			SELECT user_id, COUNT(user_id) completed FROM scorm_tracking
			WHERE '.$in.'
			AND obj_id = %s
			AND lvalue = %s 
			AND ('.$ilDB->like('rvalue', 'clob', 'completed').' OR '.$ilDB->like('rvalue', 'clob', 'passed').')
			GROUP BY user_id',
			array('integer', 'text'),
			array($a_obj_id, 'cmi.core.lesson_status')
		);
		*/
		
		// Avoid searches against field rvalue.
		// This gives the possibility to reuse the obj_id,sco_id,lvalue index.
		$query = "SELECT user_id,rvalue FROM scorm_tracking ".
			"WHERE ".$in." ".
			"AND obj_id = ".$ilDB->quote($a_obj_id,'integer')." ".
			"AND lvalue = ".$ilDB->quote('cmi.core.lesson_status','text');
		
		$res = $ilDB->query($query);
		while($row = $ilDB->fetchObject($res))
		{
			if($row->rvalue == 'passed' or $row->rvalue == 'completed')
			{
				++$users[$row->user_id];
			}
		}
		return $users ? $users : array();
	}

	/**
	 * Get info about 
	 * @param object $sco_item_ids
	 * @param object $a_obj_id
	 * @return 
	 */
	public static function _getProgressInfo($sco_item_ids,$a_obj_id)
	{
		global $ilDB;
		
		$in = $ilDB->in('sco_id', $sco_item_ids, false, 'integer');

		$res = $ilDB->queryF('
		SELECT * FROM scorm_tracking 
		WHERE '.$in.'
		AND obj_id = %s 
		AND lvalue = %s ',
		array('integer','text'), 
		array($a_obj_id,'cmi.core.lesson_status'));
		
		$info['completed'] = array();
		$info['failed'] = array();
		
		while($row = $ilDB->fetchObject($res))
		{
			switch($row->rvalue)
			{
				case 'completed':
				case 'passed':
					$info['completed'][$row->sco_id][] = $row->user_id;
					break;

				case 'failed':
					$info['failed'][$row->sco_id][] = $row->user_id;
					break;
			}
		}
		$info['in_progress'] = ilObjSCORMTracking::_getInProgress($sco_item_ids,$a_obj_id);

		return $info;
	}
			

} // END class.ilObjSCORMTracking
?>
