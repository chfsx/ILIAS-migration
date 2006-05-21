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
* Class ilObjiLincClassroomListGUI
*
* @author 		Alex Killing <alex.killing@gmx.de>
* @author		Sascha Hofmann <saschahofmann@gmx.de>
* @version		$Id$
*
* @extends ilObjectListGUI
* @package iLinc
*/


include_once "classes/class.ilObjectListGUI.php";

class ilObjiLincClassroomListGUI extends ilObjectListGUI
{
	/**
	* constructor
	*
	*/
	function ilObjiLincClassroomListGUI()
	{
		$this->ilinc_crs_id = $_GET['ref_id'];
		
		$this->ilObjectListGUI();
		//var_dump($this->gui_class_name);exit;
	}

	/**
	* initialisation
	*/
	function init()
	{
		$this->delete_enabled = false;
		$this->cut_enabled = false;
		$this->subscribe_enabled = false;
		$this->link_enabled = false;
		$this->payment_enabled = false;
		$this->type = "icla";
		$this->gui_class_name = "ilobjilincclassroomgui";

		// general commands array
		include_once('class.ilObjiLincClassroomAccess.php');
		$this->commands = ilObjiLincClassroomAccess::_getCommands();
	}
	
	/**
	* Get all item information (title, commands, description) in HTML
	*
	* @access	public
	* @param	int			$a_ref_id		item reference id
	* @param	int			$a_obj_id		item object id
	* @param	int			$a_title		item title
	* @param	int			$a_description	item description
	* @return	string		html code
	*/
	function getListItemHTML($a_icrs_ref_id, $a_icla_id, $a_title, $a_description,$a_item)
	{
		// this variable stores wheter any admin commands
		// are included in the output
		$this->adm_commands_included = false;

		// initialization
		$this->tpl =& new ilTemplate ("tpl.container_list_item.html", true, true);
		$this->initItem($a_icrs_ref_id, $a_icla_id, $a_title, $a_description);

		// commands
		$this->insertCommands();

		// insert title and describtion
		$this->insertTitle();

		if (!$this->isMode(IL_LIST_AS_TRIGGER))
		{
			if ($this->getDescriptionStatus())
			{
				$this->insertDescription();
			}
		}

		// properties
		if ($this->getPropertiesStatus())
		{
			$this->insertProperties($a_item);
		}

		// preconditions
		//if ($this->getPreconditionsStatus())
		//{
		//	$this->insertPreconditions();
		//}

		// path
		//$this->insertPath();

		return $this->tpl->get();
	}
	
	/**
	* inititialize new item (is called by getItemHTML())
	*
	* @param	int			$a_ref_id		reference id
	* @param	int			$a_obj_id		object id
	* @param	string		$a_title		title
	* @param	string		$a_description	description
	*/
	function initItem($a_icrs_ref_id, $a_icla_id, $a_title, $a_description)
	{
		$this->ref_id = $a_icla_id;
		$this->obj_id = $a_icrs_ref_id;
		$this->title = $a_title;
		$this->description = $a_description;
		
		// checks, whether any admin commands are included in the output
		$this->adm_commands_included = false;
	}
	
	/**
	* get all current commands for a specific ref id (in the permission
	* context of the current user)
	*
	* !!!NOTE!!!: Please use getListHTML() if you want to display the item
	* including all commands
	*
	* !!!NOTE 2!!!: Please do not overwrite this method in derived
	* classes becaus it will get pretty large and much code will be simply
	* copy-and-pasted. Insert smaller object type related method calls instead.
	* (like getCommandLink() or getCommandFrame())
	*
	* @access	public
	* @param	int		$a_ref_id		ref id of object
	* @return	array	array of command arrays including
	*					"permission" => permission name
	*					"cmd" => command
	*					"link" => command link url
	*					"frame" => command link frame
	*					"lang_var" => language variable of command
	*					"granted" => true/false: command granted or not
	*					"access_info" => access info object (to do: implementation)
	*/
	function getCommands()
	{
		global $ilAccess, $ilBench;

		$ref_commands = array();

		foreach($this->commands as $command)
		{
			$permission = $command["permission"];
			$cmd = $command["cmd"];
			$lang_var = $command["lang_var"];

			// all access checking should be made within $ilAccess and
			// the checkAccess of the ilObj...Access classes
			$item_data = $this->container_obj->items['icla'][$this->ref_id];
			$ilAccess->enable("cache",false);
			$access = $ilAccess->doStatusCheck($permission, $cmd, $this->obj_id, $item_data,$this->ref_id,"icla");
			$ilAccess->enable("cache",true);

			if ($access)
			{
				$cmd_link = $this->getCommandLink($command["cmd"]);
				$cmd_frame = $this->getCommandFrame($command["cmd"]);
				$access_granted = true;
			}
			else
			{
				$access_granted = false;
				//$info_object = $ilAccess->getInfo();
			}

			$ref_commands[] = array(
				"permission" => $permission,
				"cmd" => $cmd,
				"link" => $cmd_link,
				"frame" => $cmd_frame,
				"lang_var" => $lang_var,
				"granted" => $access_granted,
				"access_info" => $info_object,
				"default" => $command["default"]
			);
		}

		return $ref_commands;
	}
	
	/**
	* insert delete command
	*
	* @access	private
	* @param	object		$a_tpl		template object
	* @param	int			$a_ref_id	item reference id
	*/
	/*
	function insertDeleteCommand()
	{
		if (true)  // query here docent flag
		{
			$this->ctrl->setParameter($this->container_obj, "ref_id",
				$this->container_obj->object->getRefId());
			$this->ctrl->setParameter($this->container_obj, "class_id", $this->ref_id);
			$cmd_link = $this->ctrl->getLinkTarget($this->container_obj, "removeRoom");
			$this->insertCommand($cmd_link, $this->lng->txt("delete"));
			$this->adm_commands_included = false;
		}
	}*/
	
	/**
	* insert all commands into html code
	*
	* @access	private
	* @param	object		$a_tpl		template object
	* @param	int			$a_ref_id	item reference id
	*/
	function insertCommands()
	{
		$this->ctrl->setParameterByClass($this->gui_class_name, "ref_id", $this->ref_id);

		$commands = $this->getCommands($this->ref_id, $this->obj_id);

		$this->default_command = false;
		
		foreach($commands as $command)
		{
			if ($command["granted"] == true )
			{
				if (!$command["default"] === true)
				{
					$cmd_link = $command["link"];
					$this->insertCommand($cmd_link, $this->lng->txt($command["lang_var"]),
						$command["frame"]);
				}
				else
				{
					// this is view/show most times and will be linked
					// with the item title in insertTitle
					$this->default_command = $command;
				}
			}
		}

		if (!$this->isMode(IL_LIST_AS_TRIGGER))
		{
			// delete
			if ($this->delete_enabled)
			{
				$this->insertDeleteCommand();
			}
		}
	}
	
	/**
	* Get command link url.
	*
	* Overwrite this method, if link target is not build by ctrl class
	* (e.g. "lm_presentation.php", "forum.php"). This is the case
	* for all links now, but bringing everything to ilCtrl should
	* be realised in the future.
	*
	* @param	string		$a_cmd			command
	*
	* @return	string		command link url
	*/
	function getCommandLink($a_cmd)
	{
		// don't use ctrl here in the moment
		//return 'repository.php?ref_id='.$this->ilinc_crs_id.'&class_id='.$this->ref_id.'&cmd='.$a_cmd;
		
		// pass current class_id as ref_id
		$this->ctrl->setParameterByClass($this->gui_class_name,"ref_id",$this->ilinc_crs_id);
		$this->ctrl->setParameterByClass($this->gui_class_name,"class_id",$this->ref_id);
		
		// separate method for this line
		$cmd_link = $this->ctrl->getLinkTargetByClass($this->gui_class_name,
			$a_cmd);
		return $cmd_link;
	}
	
	function getCommandFrame($a_cmd)
	{
		switch($a_cmd)
		{
			case "joinClassroom":
			case "agendaClassroom":
				$frame = "_blank";
				break;

			default:
				$frame = "";
				break;
		}

		return $frame;
	}
	
	/**
	* Get item properties
	*
	* Overwrite this method to add properties at
	* the bottom of the item html
	*
	* @return	array		array of property arrays:
	*						"alert" (boolean) => display as an alert property (usually in red)
	*						"property" (string) => property name
	*						"value" (string) => property value
	*/
	function getProperties($a_item = '')
	{
		$props = array();

		// docent
		include_once ('ilinc/classes/class.ilObjiLincClassroom.php');
		$docent = ilObjiLincClassroom::_getDocent($a_item['instructoruserid']);
				
		if (!$docent)
		{
			$props[] = array("alert" => true, "property" => $this->lng->txt(ILINC_MEMBER_DOCENT), "value" => $this->lng->txt('ilinc_no_docent_assigned'));
		}
		else
		{
			$props[] = array("alert" => false, "property" => $this->lng->txt(ILINC_MEMBER_DOCENT), "value" => $docent);
		}
//var_dump($a_item['alwaysopen']);
		// offline
		if ($a_item['alwaysopen'] == 'Wahr')
		{
			$props[] = array("alert" => false, "property" => $this->lng->txt("status"),
				"value" => $this->lng->txt("ilinc_classroom_always_open"));
		}
		else
		{
			$props[] = array("alert" => true, "property" => $this->lng->txt("status"),
				"value" => $this->lng->txt("ilinc_classroom_closed"));
		}

		return $props;
	}
} // END class.ilObjiLincClassroomListGUI
?>
