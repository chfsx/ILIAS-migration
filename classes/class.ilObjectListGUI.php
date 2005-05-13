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


define ("IL_LIST_AS_TRIGGER", "trigger");
define ("IL_LIST_FULL", "full");


/**
* Class ilObjectListGUI
*
* @author Alex Killing <alex.killing@gmx.de>
* $Id$
*
*/
class ilObjectListGUI
{
	var $ctrl;


	/**
	* constructor
	*
	* @param	object		$a_container_obj	container gui object, e.g.
	*											an instance of ilObjCategoryGUI or
	*											ilObjCourseGUI
	*/
	function ilObjectListGUI(&$a_container_obj)
	{
		global $rbacsystem, $ilCtrl, $lng, $ilias;

		$this->rbacsystem = $rbacsystem;
		$this->ilias = $ilias;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->container_obj = $a_container_obj;
		$this->mode = IL_LIST_FULL;

		$this->init();
	}


	/**
	* initialisation
	*
	* this method should be overwritten by derived classes
	*/
	function init()
	{
		$this->delete_enabled = true;
		$this->cut_enabled = true;
		$this->subscribe_enabled = true;
		$this->link_enabled = false;
		$this->payment_enabled = false;
		$this->type = "";					// "cat", "course", ...
		$this->gui_class_name = "";			// "ilobjcategorygui", "ilobjcoursegui", ...

		// general commands array, e.g.
		$this->commands = array();

		/*	example:
		$this->commands = array
		(
			array("permission" => "read", "cmd" => "render", "lang_var" => "show"),
			array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
			array("permission" => "delete", "cmd" => "delete", "lang_var" => "delete")
		);*/
	}

	/**
	* inititialize new item (is called by getItemHTML())
	*
	* @param	int			$a_ref_id		reference id
	* @param	int			$a_obj_id		object id
	* @param	string		$a_title		title
	* @param	string		$a_description	description
	*/
	function initItem($a_ref_id, $a_obj_id, $a_title = "", $a_description = "")
	{
		$this->ref_id = $a_ref_id;
		$this->obj_id = $a_obj_id;
		$this->title = $a_title;
		$this->description = $a_description;
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
		// separate method for this line
		$cmd_link = $this->ctrl->getLinkTargetByClass($this->gui_class_name,
			$a_cmd);

		return $cmd_link;
	}


	/**
	* Get command target frame.
	*
	* Overwrite this method if link frame is not current frame
	*
	* @param	string		$a_cmd			command
	*
	* @return	string		command target frame
	*/
	function getCommandFrame($a_cmd)
	{
		return "";
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
	function getProperties()
	{
		$props = array();

		// please list alert properties first
		// example (use $lng->txt instead of "Status"/"Offline" strings):
		// $props[] = array("alert" => true, "property" => "Status", "value" => "Offline");
		// $props[] = array("alert" => false, "property" => ..., "value" => ...);
		// ...

		return $props;
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

			$ilBench->start("ilObjectListGUI", "4110_get_commands_check_access");
			$access = $ilAccess->checkAccess($permission, $cmd, $this->ref_id);
			$ilBench->stop("ilObjectListGUI", "4110_get_commands_check_access");

			if ($access)
			{
				$cmd_link = $this->getCommandLink($command["cmd"]);
				$cmd_frame = $this->getCommandFrame($command["cmd"]);
				$access_granted = true;			// todo: check additional conditions
			}
			else
			{
				$access_granted = false;
				$info_object = $ilAccess->getInfo();
			}

			$ref_commands[] = array(
				"permission" => $permission,
				"cmd" => $cmd,
				"link" => $cmd_link,
				"frame" => $cmd_frame,
				"lang_var" => $lang_var,
				"granted" => $access_granted,
				"access_info" => $info_object
			);
		}

		return $ref_commands;
	}


	/**
	* insert item title
	*
	* @access	private
	* @param	object		$a_tpl		template object
	* @param	string		$a_title	item title
	*/
	function insertTitle()
	{
		$this->tpl->setCurrentBlock("item_title");
		$this->tpl->setVariable("TXT_TITLE", $this->title);
		$this->tpl->parseCurrentBlock();
	}


	/**
	* insert item description
	*
	* @access	private
	* @param	object		$a_tpl		template object
	* @param	string		$a_desc		item description
	*/
	function insertDescription()
	{
		$this->tpl->setCurrentBlock("item_description");
		$this->tpl->setVariable("TXT_DESC", $this->description);
		$this->tpl->parseCurrentBlock();
	}

	/**
	* set output mode
	*
	* @param	string	$a_mode		output mode (IL_LIST_FULL | IL_LIST_AS_TRIGGER)
	*/
	function setMode($a_mode)
	{
		$this->mode = $a_mode;
	}

	/**
	* get output mode
	*
	* @return	string		output mode (IL_LIST_FULL | IL_LIST_AS_TRIGGER)
	*/
	function getMode()
	{
		return $this->mode;
	}

	/**
	* check current output mode
	*
	* @param	string		$a_mode (IL_LIST_FULL | IL_LIST_AS_TRIGGER)
	*
	* @return 	boolen		true if current mode is $a_mode
	*/
	function isMode($a_mode)
	{
		if ($a_mode == $this->mode)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* insert properties
	*
	* @access	private
	*/
	function insertProperties()
	{
		$props = $this->getProperties();

		if (is_array($props) && count($props) > 0)
		{
			foreach($props as $prop)
			{
				if ($prop["alert"] == true)
				{
					$this->tpl->touchBlock("alert_prop");
				}
				else
				{
					$this->tpl->touchBlock("std_prop");
				}
				if ($prop["newline"] == true)
				{
					$this->tpl->touchBlock("newline_prop");
				}
				$this->tpl->setCurrentBlock("item_property");
				$this->tpl->setVariable("TXT_PROP", $prop["property"]);
				$this->tpl->setVariable("VAL_PROP", $prop["value"]);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("item_properties");
			$this->tpl->parseCurrentBlock();
		}
	}


	/**
	* insert payment information
	*
	* @access	private
	*/
	function insertPayment()
	{
		if ($this->payment_enabled)
		{
			if (ilPaymentObject::_isBuyable($this->ref_id))
			{
				$this->tpl->setCurrentBlock("payment");
				$this->tpl->setVariable("PAYMENT_TYPE_IMG", ilUtil::getImagePath('icon_pays_b.gif'));
				$this->tpl->setVariable("PAYMENT_ALT_IMG", $this->lng->txt('payment_system'));
				$this->tpl->parseCurrentBlock();
			}
		}
	}

	/**
	* insert all missing preconditions
	*/
	function insertPreconditions()
	{
		global $ilAccess, $lng, $objDefinition;
return;
		include_once("classes/class.ilConditionHandler.php");

		$missing_cond_exist = false;

		foreach(ilConditionHandler::_getConditionsOfTarget($this->obj_id) as $condition)
		{
			if(ilConditionHandler::_checkCondition($condition['id']))
			{
				continue;
			}
			$missing_cond_exist = true;

			$cond_txt = $lng->txt("condition_".$condition["operator"])." ".
				$condition["value"];

			// display trigger item
			$class = $objDefinition->getClassName($condition["trigger_type"]);
			$location = $objDefinition->getLocation($condition["trigger_type"]);
			$full_class = "ilObj".$class."ListGUI";
			include_once($location."/class.".$full_class.".php");
			$item_list_gui = new $full_class($this);
			$item_list_gui->setMode(IL_LIST_AS_TRIGGER);
			$trigger_html = $item_list_gui->getListItemHTML($condition['trigger_ref_id'],
				$condition['trigger_obj_id'], ilObject::_lookupTitle($condition["trigger_obj_id"]),
				 "");
			$this->tpl->setCurrentBlock("precondition");
			$this->tpl->setVariable("TXT_CONDITION", trim($cond_txt));
			$this->tpl->setVariable("TRIGGER_ITEM", $trigger_html);
			$this->tpl->parseCurrentBlock();
		}

		if ($missing_cond_exist)
		{
			$this->tpl->setCurrentBlock("preconditions");
			$this->tpl->setVariable("TXT_PRECONDITIONS", $lng->txt("preconditions"));
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* insert command button
	*
	* @access	private
	* @param	string		$a_href		link url target
	* @param	string		$a_text		link text
	* @param	string		$a_frame	link frame target
	*/
	function insertCommand($a_href, $a_text, $a_frame = "")
	{
		if ($a_frame != "")
		{
			$this->tpl->setCurrentBlock("item_frame");
			$this->tpl->setVariable("TARGET_COMMAND", $a_frame);
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("item_command");
		$this->tpl->setVariable("HREF_COMMAND", $a_href);
		$this->tpl->setVariable("TXT_COMMAND", $a_text);
		$this->tpl->parseCurrentBlock();
	}

	/**
	* insert cut command
	*
	* @access	private
	* @param	object		$a_tpl		template object
	* @param	int			$a_ref_id	item reference id
	*/
	function insertDeleteCommand()
	{
		if ($this->rbacsystem->checkAccess("delete", $this->ref_id))
		{
			$cmd_link = "repository.php?ref_id=".$this->ref_id."&cmd=delete";
			$this->insertCommand($cmd_link, $this->lng->txt("delete"));
		}
	}

	/**
	* insert link command
	*
	* @access	private
	* @param	object		$a_tpl		template object
	* @param	int			$a_ref_id	item reference id
	*/
	function insertLinkCommand()
	{
		if ($this->rbacsystem->checkAccess("delete", $this->ref_id))
		{
			$this->ctrl->setParameter($this->container_obj, "ref_id",
				$this->container_obj->object->getRefId());
			$this->ctrl->setParameter($this->container_obj, "item_ref_id", $this->ref_id);
			$cmd_link = $this->ctrl->getLinkTarget($this->container_obj, "link");
			$this->insertCommand($cmd_link, $this->lng->txt("link"));
		}
	}

	/**
	* insert cut command
	*
	* @access	private
	* @param	object		$a_tpl		template object
	* @param	int			$a_ref_id	item reference id
	*/
	function insertCutCommand()
	{
		if ($this->rbacsystem->checkAccess("delete", $this->ref_id))
		{
			$this->ctrl->setParameter($this->container_obj, "ref_id",
				$this->container_obj->object->getRefId());
			$this->ctrl->setParameter($this->container_obj, "item_ref_id", $this->ref_id);
			$cmd_link = $this->ctrl->getLinkTarget($this->container_obj, "cut");
			$this->insertCommand($cmd_link, $this->lng->txt("move"));
		}
	}

	/**
	* insert subscribe command
	*
	* @access	private
	* @param	object		$a_tpl		template object
	* @param	int			$a_ref_id	item reference id
	*/
	function insertSubscribeCommand()
	{
		if ($this->ilias->account->getId() != ANONYMOUS_USER_ID)
		{
			if (!$this->ilias->account->isDesktopItem($this->ref_id, $this->type))
			{
				if ($this->rbacsystem->checkAccess("read", $this->ref_id))
				{
					$this->ctrl->setParameter($this->container_obj, "ref_id",
						$this->container_obj->object->getRefId());
					$this->ctrl->setParameter($this->container_obj, "type", $this->type);
					$this->ctrl->setParameter($this->container_obj, "item_ref_id", $this->ref_id);
					$cmd_link = $this->ctrl->getLinkTarget($this->container_obj, "addToDesk");
					$this->insertCommand($cmd_link, $this->lng->txt("to_desktop"));
				}
			}
			else
			{
					$this->ctrl->setParameter($this->container_obj, "ref_id",
						$this->container_obj->object->getRefId());
					$this->ctrl->setParameter($this->container_obj, "type", $this->type);
					$this->ctrl->setParameter($this->container_obj, "item_ref_id", $this->ref_id);
					$cmd_link = $this->ctrl->getLinkTarget($this->container_obj, "removeFromDesk");
					$this->insertCommand($cmd_link, $this->lng->txt("unsubscribe"));
			}
		}
	}

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

		foreach($commands as $command)
		{
			if ($command["granted"] == true )
			{
				$cmd_link = $command["link"];
				$this->insertCommand($cmd_link, $this->lng->txt($command["lang_var"]),
					$command["frame"]);
			}
		}

		if (!$this->isMode(IL_LIST_AS_TRIGGER))
		{
			// delete
			if ($this->delete_enabled)
			{
				$this->insertDeleteCommand();
			}

			// link
			if ($this->link_enabled)
			{
				$this->insertLinkCommand();
			}

			// cut
			if ($this->cut_enabled)
			{
				$this->insertCutCommand();
			}

			// subscribe
			if ($this->subscribe_enabled)
			{
				$this->insertSubscribeCommand();
			}
		}
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
	function getListItemHTML($a_ref_id, $a_obj_id, $a_title, $a_description)
	{
		global $ilAccess, $ilBench;

		// only for permformance exploration
		//$type = ilObject::_lookupType($a_obj_id);

		// initialization
		$ilBench->start("ilObjectListGUI", "1000_getListHTML_init$type");
		$this->tpl =& new ilTemplate ("tpl.container_list_item.html", true, true);
		$this->initItem($a_ref_id, $a_obj_id, $a_title, $a_description);
		$ilBench->stop("ilObjectListGUI", "1000_getListHTML_init$type");

  		// visible check
		$ilBench->start("ilObjectListGUI", "2000_getListHTML_check_visible");
		if (!$ilAccess->checkAccess("visible", "", $a_ref_id, "", $a_obj_id))
		{
			$ilBench->stop("ilObjectListGUI", "2000_getListHTML_check_visible");
			return "";
		}
		$ilBench->stop("ilObjectListGUI", "2000_getListHTML_check_visible");

		// insert title and describtion
		$ilBench->start("ilObjectListGUI", "3000_insert_title_desc");
		$this->insertTitle();
		if (!$this->isMode(IL_LIST_AS_TRIGGER))
		{
			$this->insertDescription();
		}
		$ilBench->stop("ilObjectListGUI", "3000_insert_title_desc");

		// commands
		$ilBench->start("ilObjectListGUI", "4000_insert_commands");
		$this->insertCommands();
		$ilBench->stop("ilObjectListGUI", "4000_insert_commands");

		// payment
		$ilBench->start("ilObjectListGUI", "5000_insert_pay");
		$this->insertPayment();
		$ilBench->stop("ilObjectListGUI", "5000_insert_pay");

		// properties
		$ilBench->start("ilObjectListGUI", "6000_insert_properties");
		$this->insertProperties();
		$ilBench->stop("ilObjectListGUI", "6000_insert_properties");

		// preconditions
		$ilBench->start("ilObjectListGUI", "6000_insert_preconditions");
		$this->insertPreconditions();
		$ilBench->stop("ilObjectListGUI", "6000_insert_preconditions");

		return $this->tpl->get();
	}

} // END class.ilObjectListGUI
?>
