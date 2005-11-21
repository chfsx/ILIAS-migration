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
* Class ilObjForumListGUI
*
* @author Alex Killing <alex.killing@gmx.de>
* $Id$
*
* @extends ilObjectListGUI
*/


include_once "class.ilObjectListGUI.php";

class ilObjForumListGUI extends ilObjectListGUI
{
	/**
	* constructor
	*
	*/
	function ilObjForumListGUI()
	{
		$this->ilObjectListGUI();
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
		$this->payment_enabled = false;
		$this->type = "frm";
		$this->gui_class_name = "ilobjforumgui";
		
		// general commands array
		include_once('class.ilObjForumAccess.php');
		$this->commands = ilObjForumAccess::_getCommands();
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
		$this->frm_obj =& ilObjectFactory::getInstanceByRefId($this->ref_id);
		$this->frm =& new ilForum();
		$this->frm->setForumRefId($a_ref_id);
		$this->frm->setWhereCondition("top_frm_fk = ".$a_obj_id);
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
		global $lng, $ilUser;

		$props = array();

		include_once("classes/class.ilForum.php");
		include_once("classes/class.ilObjForum.php");
		$frm_data = ilForum::_lookupForumData($this->obj_id);

		include_once("classes/class.ilObjUser.php");
		$MODS = ilForum::_getModerators($this->ref_id);

		$moderators = "";
		for ($i = 0; $i < count($MODS); $i++)
		{
			if ($moderators != "")
			{
				$moderators .= ", ";
			}
			$moderators .= "<a class=\"il_ItemProperty\" target=\"".
				ilFrameTargetInfo::_getFrame("MainContent").
				"\" href=\"forums_user_view.php?ref_id=".$this->ref_id."&user=".
				$MODS[$i]."&backurl=repository&offset=".$Start."\">".ilObjUser::_lookupLogin($MODS[$i])."</a>";
		}

		// Moderators
		$props[] = array("alert" => false, "property" => $lng->txt("forums_moderators"),
			"value" => $moderators);

		// Topics
		$props[] = array("alert" => false, "property" => $lng->txt("forums_threads"),
			"value" => $frm_data["top_num_threads"]);

		// Articles (Unread)
		$unread = ilObjForum::_getCountUnread($this->obj_id,$ilUser->getId());
		$alert = ($unread > 0)
			? true
			: false;
		$props[] = array("alert" => $alert, "property" => $lng->txt("forums_articles")." (".$lng->txt("unread").")",
			"value" => $frm_data['top_num_posts']." (".$unread.")");

		// New Articles
		$new = $this->frm_obj->getCountNew($ilUser->getId());
		$alert = ($new > 0)
			? true
			: false;
		$props[] = array("alert" => $alert, "property" => $lng->txt("forums_new_articles"),
			"value" => $new);

		// Visits
		$props[] = array("alert" => false, "property" => $lng->txt("visits"),
			"value" => $frm_data["visits"]);

		// Last Article
		if ($frm_data["top_last_post"] != "")
		{
			$lastPost = $this->frm->getLastPost($frm_data["top_last_post"]);
			$lastPost["pos_message"] = $this->frm->prepareText($lastPost["pos_message"]);
		}
		if (is_array($lastPost))
		{
			$last_user = $this->frm->getUserData($lastPost["pos_usr_id"],$lastPost["import_name"]);

			$lpCont = "<a class=\"il_ItemProperty\" target=\"".
				ilFrameTargetInfo::_getFrame("MainContent").
				"\" href=\"forums_frameset.php?target=true&pos_pk=".
				$lastPost["pos_pk"]."&thr_pk=".$lastPost["pos_thr_fk"]."&ref_id=".
				$this->ref_id."#".$lastPost["pos_pk"]."\">".$lastPost["pos_message"]."</a> ".
				strtolower($lng->txt("from"))."&nbsp;";

			if($lastPost["pos_usr_id"] && ilObject::_exists($lastPost["pos_usr_id"]))
			{
				$lpCont .= "<a class=\"il_ItemProperty\" target=\"".
				ilFrameTargetInfo::_getFrame("MainContent").
				"\" href=\"forums_user_view.php?ref_id=".$this->ref_id."&user=".
					$last_user["usr_id"]."&backurl=repository&offset=".$Start."\">".$last_user["login"]."</a>, ";
				$lpCont .= $lastPost["pos_date"];
			}
			else
			{
				$lpCont .= $last_user["login"];
			}
		}

		$props[] = array("alert" => false, "newline" => true, "property" => $lng->txt("forums_last_post"),
			"value" => $lpCont);

		return $props;

	}

	/**
	* Get command target
	*
	* @param	int			$a_ref_id		reference id
	* @param	string		$a_cmd			command
	*
	*/
	function getCommandFrame($a_cmd)
	{
		// separate method for this line
		$target = ilFrameTargetInfo::_getFrame("MainContent");

		return $target;
	}

} // END class.ilObjForumListGUI
?>
