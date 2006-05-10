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


/**
* ILIAS Setting Class
*
* @author Alex Killing <alex.killing@databay.de>

* @version $Id$
*/
class ilSetting
{
	var $setting = array();
	
	/**
	* Initialise Settings 
	*/
	function ilSetting()
	{
		global $ilDB;
		
		// check whether ini file object exists
		if (!is_object($ilDB))
		{
			die ("Fatal Error: ilSettings object instantiated without DB initialisation.");
		}
		
		$query = "SELECT * FROM settings";
		$res = $ilDB->query($query);

		while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$this->setting[$row["keyword"]] = $row["value"];
		}

	}
	
	/**
	* get setting
	*
	* @access	public
	*
	* @param	string	keyword
	* @param	string	default_value This value is returned, when no setting has
    *								  been found for the keyword.
	* @return	string	value
	*/
	function get($a_keyword, $a_default_value = false)
	{

		if ($a_keyword == "ilias_version")
		{
			return ILIAS_VERSION;
		}
		
		if (isset($this->setting[$a_keyword]))
		{
			return $this->setting[$a_keyword];
		}
		else
		{
			return $a_default_value;
		}
	}
	
	/**
	* delete one value from settingstable
	* @access	public
	* @param	string	keyword
	* @return	string	value
	*/
	function delete($a_keyword)
	{
		global $ilDB;

		$query = "DELETE FROM settings WHERE keyword = ".
			$ilDB->quote($a_keyword);
		$ilDB->query($query);
		unset($this->setting[$a_keyword]);

		return true;
	}


	/**
	* read all values from settingstable
	* @access	public
	* @return	array	keyword/value pairs
	*/
	function getAll()
	{
		return $this->setting;
	}

	/**
	* write one value to db-table settings
	* @access	public
	* @param	string		keyword
	* @param	string		value
	* @return	boolean		true on success
	*/
	function set($a_key, $a_val)
	{
		global $ilDB;
		
		$sql = "DELETE FROM settings WHERE keyword=".$ilDB->quote($a_key);
		$ilDB->query($sql);

		$sql = "INSERT INTO settings (keyword, value) VALUES (".
			$ilDB->quote($a_key).",".$ilDB->quote($a_val).")";
		$ilDB->query($sql);
		
		$this->setting[$a_key] = $a_val;

		return true;
	}

}
?>
