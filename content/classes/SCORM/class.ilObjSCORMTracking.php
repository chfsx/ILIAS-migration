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
* Class ilObjSCORMTracking
*
* @author Alex Killing <alex.killing@gmx.de>
*
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

	function store($ref_id=0, $sahs_id=0, $extractData=1)
	{
		global $ilDB, $ilUser;

		if (empty($ref_id))
			$ref_id=$_GET["ref_id"];
		
		if (empty($sahs_id))
			$sahs_id = ($_GET["sahs_id"] != "")	? $_GET["sahs_id"] : $_POST["sahs_id"];
			
		if ($extractData==1)
			$this->extractData();

		if (is_object($ilUser))
		{
			$user_id = $ilUser->getId();
		}

		// writing to scorm test log
		$f = fopen("content/scorm.log", "a");
		fwrite($f, "\nCALLING SCORM store()\n");
		if ($ref_id <= 1)
		{
			fwrite($f, "Error: No ref_id given.\n");
		}
		else
		{
			foreach($this->insert as $insert)
			{
				$q = "SELECT * FROM scorm_tracking WHERE ".
					" user_id = ".$ilDB->quote($user_id).
					" AND sco_id = ".$ilDB->quote($sahs_id).
					" AND lvalue = ".$ilDB->quote($insert["left"]);
				$set = $ilDB->query($q);
				if ($rec = $set->fetchRow(DB_FETCHMODE_ASSOC))
				{
					fwrite($f, "Error Insert, left value already exists. L:".$insert["left"].",R:".
						$insert["right"].",sahs_id:".$sahs_id.",user_id:".$user_id."\n");
				}
				else
				{
					$q = "INSERT INTO scorm_tracking (user_id, sco_id, ref_id, lvalue, rvalue) VALUES ".
						"(".$ilDB->quote($user_id).",".$ilDB->quote($sahs_id).",".
						$ilDB->quote($ref_id).",".
						$ilDB->quote($insert["left"]).",".$ilDB->quote($insert["right"]).")";
					$ilDB->query($q);
					fwrite($f, "Insert - L:".$insert["left"].",R:".
						$insert["right"].",sahs_id:".$sahs_id.",user_id:".$user_id."\n");
				}
			}
			foreach($this->update as $update)
			{
				$q = "SELECT * FROM scorm_tracking WHERE ".
					" user_id = ".$ilDB->quote($user_id).
					" AND sco_id = ".$ilDB->quote($sahs_id).
					" AND lvalue = ".$ilDB->quote($update["left"]);
				$set = $ilDB->query($q);
				if ($rec = $set->fetchRow(DB_FETCHMODE_ASSOC))
				{
					$q = "REPLACE INTO scorm_tracking (user_id, sco_id, ref_id, lvalue, rvalue) VALUES ".
						"(".$ilDB->quote($user_id).",".$ilDB->quote($sahs_id).",".
						$ilDB->quote($ref_id).",".
						$ilDB->quote($update["left"]).",".$ilDB->quote($update["right"]).")";
					$ilDB->query($q);
					fwrite($f, "Update - L:".$update["left"].",R:".
						$update["right"].",sahs_id:".$sahs_id.",user_id:".$user_id."\n");
				}
				else
				{
					fwrite($f, "ERROR Update, left value does not exist. L:".$update["left"].",R:".
						$update["right"].",sahs_id:".$sahs_id.",user_id:".$user_id."\n");
				}
			}
		}
		fclose($f);
	}

	function _insertTrackData($a_sahs_id, $a_lval, $a_rval, $a_ref_id)
	{
		global $ilDB, $ilUser;

		$q = "INSERT INTO scorm_tracking (user_id, sco_id, lvalue, rvalue, ref_id) ".
			" VALUES (".$ilDB->quote($ilUser->getId()).",".$ilDB->quote($a_sahs_id).
			",".$ilDB->quote($a_lval).",".$ilDB->quote($a_rval).
			",".$ilDB->quote($a_ref_id).")";
		$ilDB->query($q);

	}

} // END class.ilObjSCORMTracking
?>
