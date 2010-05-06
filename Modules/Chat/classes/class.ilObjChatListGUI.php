<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
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
* Class ilObjChatListGUI
*
* @author Alex Killing <alex.killing@gmx.de>
* $Id:class.ilObjChatListGUI.php 12853 2006-12-15 13:36:31 +0000 (Fr, 15 Dez 2006) smeyer $
*
* @extends ilObjectListGUI
*/


include_once "Services/Object/classes/class.ilObjectListGUI.php";

class ilObjChatListGUI extends ilObjectListGUI
{
	/**
	* constructor
	*
	*/
	public function ilObjChatListGUI()
	{
		$this->ilObjectListGUI();
	}

	/**
	* initialisation
	*
	* this method should be overwritten by derived classes
	*/
	public function init()
	{
		$this->copy_enabled = false;
		$this->static_link_enabled = true;
		$this->delete_enabled = true;
		$this->cut_enabled = true;
		$this->subscribe_enabled = true;
		$this->link_enabled = false;
		$this->payment_enabled = false;
		$this->info_screen_enabled = true;
		$this->type = "chat";
		$this->gui_class_name = "ilobjchatgui";
		
		// general commands array
		include_once('class.ilObjChatAccess.php');
		$this->commands = ilObjChatAccess::_getCommands();
	}

	/**
	* Overwrite this method, if link target is not build by ctrl class
	* (e.g. "forum.php"). This is the case
	* for all links now, but bringing everything to ilCtrl should
	* be realised in the future.
	*
	* @param	string		$a_cmd			command
	*
	*/
	public function getCommandLink($a_cmd)
	{
		switch($a_cmd)
		{
			case "edit":
			case "view":
			default:
				$cmd_link = "ilias.php?baseClass=ilChatHandlerGUI&ref_id=".$this->ref_id."&cmd=$a_cmd";
				break;
		}
		$this->default_command["frame"] = "_top";
		return $cmd_link;
	}


	function getCommandFrame($a_cmd)
	{
		return "_top";
	}
	
	/**
	* Get item properties
	*
	* @return	array		array of property arrays:
	*						"alert" (boolean) => display as an alert property (usually in red)
	*						"property" (string) => property name
	*						"value" (string) => property value
	*/
	public function getProperties()
	{
		global $lng;

		include_once './Modules/Chat/classes/class.ilChatRoom.php';

		$props[] = array("alert" => false, "property" => $lng->txt("chat_users_active"),
						 "value" => ilChatRoom::_getCountActiveUsers($this->obj_id));

		return $props;
	}


} // END class.ilObjCategoryGUI
?>
