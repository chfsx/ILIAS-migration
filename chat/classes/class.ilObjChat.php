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
* Class ilObjTest
* 
* @author Stefan Meyer 
* @version $Id$
*
* @extends ilObject
* @package chat
*/

require_once "classes/class.ilObjectGUI.php";
require_once "chat/classes/class.ilChatServerConfig.php";
require_once "chat/classes/class.ilChatServerCommunicator.php";
require_once "chat/classes/class.ilChatUser.php";
require_once "chat/classes/class.ilChatRoom.php";

class ilObjChat extends ilObject
{
	var $server_conf;
	var $server_comm;
	var $chat_room;
	var $chat_user;

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjChat($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "chat";
		$this->ilObject($a_id,$a_call_by_reference);

		$this->server_conf =& new ilChatServerConfig();
		$this->server_comm =& new ilChatServerCommunicator($this);
		$this->chat_user =& new ilChatUser();
		$this->chat_room =& new ilChatRoom($this->getRefId());
	}

	function read()
	{
		// USED ilObjectFactory::getInstance...
		parent::read();

		$this->server_conf =& new ilChatServerConfig();
		$this->server_comm =& new ilChatServerCommunicator($this);
		$this->chat_user =& new ilChatUser();
		$this->chat_room =& new ilChatRoom($this->getRefId());
	}

	function delete()
	{
		if(!parent::delete())
		{
			return false;
		}
		$rooms = $this->chat_room->getAllRoomsOfObject();
		foreach($rooms as $id)
		{
			$this->chat_room->delete($id);
		}

		// FINALLY DELETE MESSAGES IN PUBLIC ROOM
		$query = "DELETE FROM chat_room_messages ".
			"WHERE chat_id = '".$this->getRefId()."'";

		$res = $this->ilias->db->query($query);

		// AND ALL USERS
		$query = "DELETE FROM chat_user ".
			"WHERE chat_id = '".$this->getRefId()."'";

		$res = $this->ilias->db->query($query);

		return true;
	}

	function sendMessage($a_id)
	{
		include_once "./classes/class.ilMail.php";

		$tmp_mail_obj = new ilMail($_SESSION["AccountId"]);

		// GET USER OBJECT
		$tmp_user = ilObjectFactory::getInstanceByObjId($a_id);

		// GET USERS LANGUAGE
		$tmp_lang =& new ilLanguage($tmp_user->getLanguage());
		$tmp_lang->loadLanguageModule("chat");

		$message = $tmp_mail_obj->sendMail($this->__formatRecipient($tmp_user),"","",$this->__formatSubject($tmp_lang),
										   $this->__formatBody($tmp_user,$tmp_lang),array(),array("normal"));

		unset($tmp_mail_obj);
		unset($tmp_lang);
		unset($tmp_user);

		return true;
	}

	// PRIVATE
	function __formatRecipient(&$user)
	{
		if(is_object($user))
		{
			return $user->getLogin();
		}
		return false;
	}

	function __formatSubject(&$lang)
	{
		return $lang->txt("chat_invitation_subject");
	}

	function __formatBody(&$user,&$lang)
	{
		$body = $lang->txt("chat_invitation_body")." ";
		$body .= $user->getFullname();
		$body .= "\n";
		$body .= $lang->txt("chat_chatroom_body")." ".$this->chat_room->getTitle()."\n\n";
		$body .= "<a class=\"navigation\" href=\"./chat/chat_rep?ref_id=".$this->getRefId()."\">".
			$lang->txt("chat_to_chat_body")."</a>";

		return $body;
	}


		
		

		
	// SET/GET
} // END class.ilObjTest
?>
