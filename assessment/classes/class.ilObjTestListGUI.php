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
* Class ilObjTestListGUI
*
* @author		Helmut Schottmueller <hschottm@tzi.de>
* @author		Alex Killing <alex.killing@gmx.de>
* $Id$
*
* @extends ilObjectListGUI
*/


include_once "classes/class.ilObjectListGUI.php";

class ilObjTestListGUI extends ilObjectListGUI
{
	/**
	* constructor
	*
	*/
	function ilObjTestListGUI()
	{
		$this->ilObjectListGUI();
		$this->info_screen_enabled = true;
	}

	/**
	* initialisation
	*/
	function init()
	{
		$this->delete_enabled = true;
		$this->cut_enabled = true;
		$this->subscribe_enabled = true;
		$this->link_enabled = true;
		$this->payment_enabled = true;
		$this->type = "tst";
		$this->gui_class_name = "ilobjtestgui";

		// general commands array
		include_once "./assessment/classes/class.ilObjTestAccess.php";
		$this->commands = ilObjTestAccess::_getCommands();
	}


	/**
	* inititialize new item
	*
	* @param	int			$a_ref_id		reference id
	* @param	int			$a_obj_id		object id
	* @param	string		$a_title		title
	* @param	string		$a_description	description
	*/
	function initItem($a_ref_id, $a_obj_id, $a_title = "", $a_description = "")
	{
		parent::initItem($a_ref_id, $a_obj_id, $a_title, $a_description);
	}


	/**
	* Get command target frame
	*
	* @param	string		$a_cmd			command
	*
	* @return	string		command target frame
	*/
	function getCommandFrame($a_cmd)
	{
		switch($a_cmd)
		{
			case "":
			case "infoScreen":
			case "eval_a":
			case "eval_stat":
				include_once "./classes/class.ilFrameTargetInfo.php";
				$frame = ilFrameTargetInfo::_getFrame("MainContent");
				break;

			default:
		}

		return $frame;
	}



	/**
	* Get item properties
	*
	* @return	array		array of property arrays:
	*						"alert" (boolean) => display as an alert property (usually in red)
	*						"property" (string) => property name
	*						"value" (string) => property value
	*/
	function getProperties()
	{
		global $lng, $ilUser;

		$props = array();

		include_once "./assessment/classes/class.ilObjTestAccess.php";
		if (!ilObjTestAccess::_lookupCreationComplete($this->obj_id))
		{
			$props[] = array("alert" => true, "property" => $lng->txt("status"),
				"value" => $lng->txt("tst_warning_test_not_complete"));
		}
		$onlineaccess = ilObjTestAccess::_lookupOnlineTestAccess($this->obj_id, $ilUser->id);
		if ($onlineaccess !== true)
		{
			$props[] = array("alert" => true, "property" => $lng->txt("status"),
				"value" => $onlineaccess);
		}

		return $props;
	}


	/**
	* Get command link url.
	*
	* @param	int			$a_ref_id		reference id
	* @param	string		$a_cmd			command
	*
	*/
	function getCommandLink($a_cmd)
	{
		// separate method for this line
		//$cmd_link = "assessment/test.php?ref_id=".$this->ref_id."&cmd=$a_cmd";
		$cmd_link = "ilias.php?baseClass=ilObjTestGUI&amp;ref_id=".$this->ref_id."&amp;cmd=$a_cmd";

		return $cmd_link;
	}



} // END class.ilObjTestListGUI
?>
