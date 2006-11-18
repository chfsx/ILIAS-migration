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
* Class ilContainer
*
* Base class for all container objects (categories, courses, groups)
* 
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
* @extends ilObject
* @package ilias-core
*/

require_once "class.ilObject.php";

class ilContainer extends ilObject
{
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjContainer($a_id = 0, $a_call_by_reference = true)
	{
		$this->ilObject($a_id, $a_call_by_reference);
	}
	
	/**
	* Create directory for the container.
	* It is <webspace_dir>/container_data.
	*/
	function createContainerDirectory()
	{
		$webspace_dir = ilUtil::getWebspaceDir();
		$cont_dir = $webspace_dir."/container_data";
		if (!is_dir($cont_dir))
		{
			ilUtil::makeDir($cont_dir);
		}
		$obj_dir = $cont_dir."/obj_".$this->getId();
		if (!is_dir($obj_dir))
		{
			ilUtil::makeDir($obj_dir);
		}
	}
	
	/**
	* Get the container directory.
	*
	* @return	string	container directory
	*/
	function getContainerDirectory()
	{
		return $this->_getContainerDirectory($this->getId());
	}
	
	/**
	* Get the container directory.
	*
	* @return	string	container directory
	*/
	function _getContainerDirectory($a_id)
	{
		return ilUtil::getWebspaceDir()."/container_data/obj_".$a_id;
	}
	
	/**
	* Get path for big icon.
	*
	* @return	string	icon path
	*/
	function getBigIconPath()
	{
		return ilContainer::_lookupIconPath($this->getId(), "big");
	}

	/**
	* Get path for small icon
	*
	* @return	string	icon path
	*/
	function getSmallIconPath()
	{
		return ilContainer::_lookupIconPath($this->getId(), "small");
	}
	
	/**
	* Lookup a container setting.
	*
	* @param	int			container id
	* @param	string		setting keyword 
	*
	* @return	string		setting value
	*/
	function _lookupContainerSetting($a_id, $a_keyword)
	{
		global $ilDB;
		
		$q = "SELECT * FROM container_settings WHERE ".
				" id = ".$ilDB->quote($a_id)." AND ".
				" keyword = ".$ilDB->quote($a_keyword);
		$set = $ilDB->query($q);
		$rec = $set->fetchRow(DB_FETCHMODE_ASSOC);
		
		return $rec["value"];
	}

	function _writeContainerSetting($a_id, $a_keyword, $a_value)
	{
		global $ilDB;
		
		$q = "REPLACE INTO container_settings (id, keyword, value) VALUES".
			" (".$ilDB->quote($a_id).", ".
			$ilDB->quote($a_keyword).", ".
			$ilDB->quote($a_value).")";

		$ilDB->query($q);
	}
	
	/**
	* lookup icon path
	*
	* @param	int		$a_id		container object id
	* @param	string	$a_size		"big" | "small"
	*/
	function _lookupIconPath($a_id, $a_size)
	{
		$size = ($a_size == "small")
			? "small"
			: "big";

		if (ilContainer::_lookupContainerSetting($a_id, "icon_".$size))
		{
			$cont_dir = ilContainer::_getContainerDirectory($a_id);
			$file_name = $cont_dir."/icon_".$a_size.".gif";
			
			if (is_file($file_name))
			{
				return $file_name;
			}
		}
		
		return "";
	}

	/**
	* save container icons
	*/
	function saveIcons($a_big_icon, $a_small_icon)
	{
		global $ilDB;
		
		$this->createContainerDirectory();
		$cont_dir = $this->getContainerDirectory();
		
		// save big icon
		$big_geom = $this->ilias->getSetting("custom_icon_big_width")."x".
			$this->ilias->getSetting("custom_icon_big_height");
		$big_file_name = $cont_dir."/icon_big.gif";
		if (is_file($a_big_icon["tmp_name"]))
		{
			$a_big_icon["tmp_name"] = ilUtil::escapeShellArg($a_big_icon["tmp_name"]);
			$big_file_name = ilUtil::escapeShellArg($big_file_name);
			$cmd = ilUtil::getConvertCmd()." ".$a_big_icon["tmp_name"]."[0] -geometry $big_geom GIF:$big_file_name";
			system($cmd);
		}

		if (is_file($cont_dir."/icon_big.gif"))
		{
			ilContainer::_writeContainerSetting($this->getId(), "icon_big", 1);
		}
		else
		{
			ilContainer::_writeContainerSetting($this->getId(), "icon_big", 0);
		}
	
		// save small icon
		$small_geom = $this->ilias->getSetting("custom_icon_small_width")."x".
			$this->ilias->getSetting("custom_icon_small_height");
		$small_file_name = $cont_dir."/icon_small.gif";

		if (is_file($a_small_icon["tmp_name"]))
		{
			$a_small_icon["tmp_name"] = ilUtil::escapeShellArg($a_small_icon["tmp_name"]);
			$small_file_name = ilUtil::escapeShellArg($small_file_name);
			$cmd = ilUtil::getConvertCmd()." ".$a_small_icon["tmp_name"]."[0] -geometry $small_geom GIF:$small_file_name";
			system($cmd);
		}
		if (is_file($cont_dir."/icon_small.gif"))
		{
			ilContainer::_writeContainerSetting($this->getId(), "icon_small", 1);
		}
		else
		{
			ilContainer::_writeContainerSetting($this->getId(), "icon_small", 0);
		}

	}

	/**
	* remove big icon
	*/ 
	function removeBigIcon()
	{
		$cont_dir = $this->getContainerDirectory();
		$big_file_name = $cont_dir."/icon_big.gif";
		@unlink($big_file_name);
		ilContainer::_writeContainerSetting($this->getId(), "icon_big", 0);
	}
	
	/**
	* remove small icon
	*/ 
	function removeSmallIcon()
	{
		$cont_dir = $this->getContainerDirectory();
		$small_file_name = $cont_dir."/icon_small.gif";
		@unlink($small_file_name);
		ilContainer::_writeContainerSetting($this->getId(), "icon_small", 0);
	}
	
	/**
	* Get right column
	*
	* @return	object		column object
	*/ 
	function getFirstColumn()
	{
		$col_id = ilContainer::_lookupContainerSetting($this->getId(), "first_column");
		if ($col_id > 0)
		{
			include_once("./Services/Blocks/class.ilBlockColumn.php");
			$block_column = new ilBlockColumn($col_id);
			return $block_column;
		}
		return false;
	}
	
	
} // END class.ilObjCategory
?>
