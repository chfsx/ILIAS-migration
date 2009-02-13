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

include_once("classes/class.ilObjectAccess.php");

/**
* Class ilFileBasedLMAccess
*
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ingroup ModulesHTMLLearningModule
*/
class ilObjFileBasedLMAccess extends ilObjectAccess
{
	/**
	* checks wether a user may invoke a command or not
	* (this method is called by ilAccessHandler::checkAccess)
	*
	* @param	string		$a_cmd		command (not permission!)
	* @param	string		$a_permission	permission
	* @param	int			$a_ref_id	reference id
	* @param	int			$a_obj_id	object id
	* @param	int			$a_user_id	user id (if not provided, current user is taken)
	*
	* @return	boolean		true, if everything is ok
	*/
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
	{
		global $ilUser, $lng, $rbacsystem, $ilAccess;

		if ($a_user_id == "")
		{
			$a_user_id = $ilUser->getId();
		}

		switch ($a_cmd)
		{
			case "view":

				if ((!ilObjFileBasedLMAccess::_lookupOnline($a_obj_id)
					&& !$rbacsystem->checkAccessOfUser($a_user_id,'write',$a_ref_id)) ||
					ilObjFileBasedLMAccess::_determineStartUrl($a_obj_id) == "")
				{
					$ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("offline"));
					return false;
				}
				break;
		}

		switch ($a_permission)
		{
			case "visible":
				if (!ilObjFileBasedLMAccess::_lookupOnline($a_obj_id) &&
					(!$rbacsystem->checkAccessOfUser($a_user_id,'write', $a_ref_id)))
				{
					$ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("offline"));
					return false;
				}
				break;
		}


		return true;
	}
	
	/**
	 * get commands
	 * 
	 * this method returns an array of all possible commands/permission combinations
	 * 
	 * example:	
	 * $commands = array
	 *	(
	 *		array("permission" => "read", "cmd" => "view", "lang_var" => "show"),
	 *		array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
	 *	);
	 */
	function _getCommands()
	{
		$commands = array
		(
			array("permission" => "read", "cmd" => "view", "lang_var" => "show",
				"default" => true),
			array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
		);
		
		return $commands;
	}

	//
	// access relevant methods
	//

	/**
	* check wether learning module is online
	*/
	function _lookupOnline($a_id)
	{
		global $ilDB;

		$q = "SELECT * FROM file_based_lm WHERE id = ".$ilDB->quote($a_id);
		$set = $ilDB->query($q);
		$rec = $set->fetchRow(DB_FETCHMODE_ASSOC);

		return ilUtil::yn2tf($rec["online"]);
	}

	/**
	* check wether learning module is online
	*/
	function _determineStartUrl($a_id)
	{
		global $ilDB;

		$q = "SELECT * FROM file_based_lm WHERE id = ".$ilDB->quote($a_id);
		$set = $ilDB->query($q);
		$rec = $set->fetchRow(DB_FETCHMODE_ASSOC);
		$start_file = $rec["startfile"];
		$dir = ilUtil::getWebspaceDir()."/lm_data/lm_".$a_id;
		
		if (($start_file != "") &&
			(@is_file($dir."/".$start_file)))
		{
			return "./".$dir."/".$start_file;
		}
		else if (@is_file($dir."/index.html"))
		{
			return "./".$dir."/index.html";
		}
		else if (@is_file($dir."/index.htm"))
		{
			return "./".$dir."/index.htm";
		}

		return "";
	}

	/**
	* check whether goto script will succeed
	*/
	function _checkGoto($a_target)
	{
		global $ilAccess;
		
		$t_arr = explode("_", $a_target);

		if ($t_arr[0] != "htlm" || ((int) $t_arr[1]) <= 0)
		{
			return false;
		}

		if ($ilAccess->checkAccess("visible", "", $t_arr[1]))
		{
			return true;
		}
		return false;
	}

	//BEGIN DiskQuota: Get used disk space
	/**
	 * Returns the number of bytes used on the harddisk by the learning module
	 * with the specified object id.
	 * @param int object id of a file object.
	 */
	function _getDiskSpaceUsed($a_id)
	{
		$lm_data_dir = ilUtil::getWebspaceDir('filesystem')."/lm_data";
		$lm_dir = $lm_data_dir.DIRECTORY_SEPARATOR."lm_".$a_id;
		
		return file_exists($lm_dir) ? ilUtil::dirsize($lm_dir) : 0;
		
	}
	/**
	 * Returns the number of bytes used on the harddisk by the user with
	 * the specified user id.
	 * @param int user id.
	 */
	function _getDiskSpaceUsedBy($user_id, $as_string = false)
	{
		// XXXX - This method is extremely slow. We should
		// use a cache to speed it up, for example, we should
		// store the disk space used in table file_data.
		global $ilDB, $lng;
		
		$size = 0;
		$count = 0;
		$obs = ilObject::_getObjectsByType("htlm", $user_id);
		foreach($obs as $ob)
		{
			$size += ilObjFileBasedLMAccess::_getDiskSpaceUsed($ob["obj_id"]);
			$count++;
		}
		include_once("Modules/File/classes/class.ilObjFileAccess.php");
		return ($as_string) ? 
			$count.' '.$lng->txt('htlm').', '.ilObjFileAccess::_sizeToString($size) : 
			$size;
	}
	//END DiskQuota: Get used disk space

}

?>
