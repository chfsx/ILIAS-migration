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

/*
* Helper class for local user accounts (in categories)
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
*
* @package core
*/

class ilLocalUser
{
	var $db;
	
	var $parent_id;
		
	/**
	* Constructor
	* @access	public
	* @param	string	scriptname
	* @param    int user_id
	*/
	function ilLocalUser($a_parent_id)
	{
		global $ilDB;

		$this->db =& $ilDB;
		$this->parent_id = $a_parent_id;
		
	}

	function setParentId($a_parent_id)
	{
		$this->parent_id = $a_parent_id;
	}
	function getParentId()
	{
		return $this->parent_id;
	}

	// STATIC
	function _getFolderIds()
	{
		global $ilDB,$rbacsystem;

		$query = "SELECT DISTINCT(time_limit_owner) as parent_id FROM usr_data ";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if($rbacsystem->checkAccess('read_users',$row->parent_id))
			{
				$parent[] = $row->parent_id;
			}
		}
		return $parent ? $parent : array();
	}
	function _getAllUserIds($a_filter = 0)
	{
		global $ilDB;

		switch($a_filter)
		{
			case 0:
				if(ilLocalUser::_getFolderIds())
				{
					$where = "WHERE time_limit_owner IN ";
					$where .= '(';
					$where .= implode(",",ilLocalUser::_getFolderIds());
					$where .= ')';
				}
				else
				{
					$where = "WHERE time_limit_owner IN ('')";
				}

				break;

			default:
				$where = "WHERE time_limit_owner = '".$a_filter."'";

				break;
		}
		
		$query = "SELECT usr_id FROM usr_data ".$where;
		$res = $ilDB->query($query);

		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$users[] = $row->usr_id;
		}

		return $users ? $users : array();
	}

	function _getUserFolderId()
	{
		return 7;
	}
		
			

		

} // CLASS ilLocalUser
?>
