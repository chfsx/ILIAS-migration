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

include_once("classes/class.ilObjectAccess.php");

/**
* Class ilFileBasedLMAccess
*
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package AccessControl
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
	* @return	mixed		true, if everything is ok, message (string) when
	*						access is not granted
	*/
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
	{
		global $ilUser, $lng, $rbacsystem;

		if ($a_user_id == "")
		{
			$a_user_id = $ilUser->getId();
		}

		switch ($a_cmd)
		{
			case "view":

				if(!ilObjFileBasedLMAccess::_lookupOnline($a_obj_id)
					&& !$rbacsystem->checkAccess('write',$a_ref_id))
				{
					return $lng->txt("offline");
				}
				break;
		}

		switch ($a_permission)
		{
			case "visible":
				if (!ilObjFileBasedLMAccess::_lookupOnline($a_obj_id) &&
					(!$rbacsystem->checkAccess('write', $a_ref_id)))
				{
					return $lng->txt("offline");
				}
				break;
		}


		return true;
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

		$q = "SELECT * FROM file_based_lm WHERE id = '".$a_id."'";
		$set = $ilDB->query($q);
		$rec = $set->fetchRow(DB_FETCHMODE_ASSOC);

		return ilUtil::yn2tf($rec["online"]);
	}



}

?>
