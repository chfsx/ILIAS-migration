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
*/

require_once "classes/class.ilObjectGUI.php";
require_once "Modules/Chat/classes/class.ilChatServerConfig.php";
require_once "Modules/Chat/classes/class.ilChatServerCommunicator.php";
require_once "Modules/Chat/classes/class.ilChatUser.php";
require_once "Modules/Chat/classes/class.ilChatRoom.php";
require_once "Modules/Chat/classes/class.ilFileDataChat.php";

class ilObjChat extends ilObject
{
	var $server_conf;
	var $server_comm;
	var $chat_room;
	var $chat_user;
	var $chat_recording = null;

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
		$this->chat_room =& new ilChatRoom($this->getId());
	}

	function read()
	{
		// USED ilObjectFactory::getInstance...
		parent::read();

		$this->server_conf =& new ilChatServerConfig();
		$this->server_comm =& new ilChatServerCommunicator($this);
		$this->chat_user =& new ilChatUser();
		$this->chat_room =& new ilChatRoom($this->getId());
	}

	/**
	* init default roles settings
	* @access	public
	* @return	array	object IDs of created local roles.
	*/
	function initDefaultRoles()
	{
		global $rbacadmin;
		
		// create a local role folder
		$rolf_obj =& $this->createRoleFolder();

		// create moderator role and assign role to rolefolder...
		$role_obj = $rolf_obj->createRole("il_chat_moderator_".$this->getRefId(),"Moderator of chat obj_no.".$this->getId());

		// grant permissions: visible,read,write,chat_moderate
		$permissions = ilRbacReview::_getOperationIdsByName(array('visible','read','moderate'));
		$rbacadmin->grantPermission($role_obj->getId(),
									$permissions,
									$this->getRefId());

		unset($rolf_obj);

		return array($role_obj->getId());
	}

	function ilClone($a_parent_ref)
	{
		$tmp_obj =& ilObjectFactory::getInstanceByRefId(parent::ilClone($a_parent_ref));
	}


	function delete()
	{
		global $ilDB;
				
		if(!parent::delete())
		{
			return false;
		}
		$rooms = $this->chat_room->getAllRoomsOfObject();
		foreach($rooms as $room)
		{
			$this->chat_room->delete($room["room_id"]);
		}

		// FINALLY DELETE MESSAGES IN PUBLIC ROOM
		$query = "DELETE FROM chat_room_messages ".
			"WHERE chat_id = ".$ilDB->quote($this->getRefId())."";

		$res = $this->ilias->db->query($query);

		// AND ALL USERS
		$query = "DELETE FROM chat_user ".
			"WHERE chat_id = ".$ilDB->quote($this->getRefId())."";

		$res = $this->ilias->db->query($query);

		// AND ALL RECORDINGS
		$query = "SELECT record_id FROM chat_records WHERE 
					chat_id = ".$ilDB->quote($this->getId())."";
		$res = $this->ilias->db->query($query);
		if (DB::isError($res)) die("ilObjChat::delete(): " . $res->getMessage() . "<br>SQL-Statement: ".$query);
		if (($num = $res->numRows()) > 0)
		{
			for ($i = 0; $i < $num; $i++)
			{
				$data = $res->fetchRow(DB_FETCHMODE_ASSOC);
				$this->ilias->db->query("DELETE FROM chat_record_data WHERE record_id = ".$ilDB->quote($data["record_id"])."");
			}
			
		}
		$query = "DELETE FROM chat_records WHERE 
					chat_id = ".$ilDB->quote($this->getId())."";
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

	function getHTMLDirectory()
	{
		$tmp_tpl =& new ilTemplate("tpl.chat_export.html",true,true);
		
		$this->chat_room->setRoomId(0);

		$tmp_tpl->setVariable("CHAT_NAME",$this->getTitle());
		$tmp_tpl->setVariable("CHAT_DATE",strftime("%c",time()));
		$tmp_tpl->setVariable("CONTENT",$this->chat_room->getAllMessages());

		$file_obj =& new ilFileDataChat($this);
		
		// return directory name of index.html
		return $file_obj->addFile('index.html',$tmp_tpl->get());
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
		$room_id = $this->chat_room->getRoomId();

		$body = $lang->txt("chat_invitation_body")." ";
		$body .= $this->ilias->account->getFullname();
		$body .= "\n";
		$body .= $lang->txt("chat_chatroom_body")." ".$this->chat_room->getTitle()."\n\n";
		$body .= "<a class=\"navigation\" href=\"./Modules/Chat/chat.php?room_id=".$room_id."&ref_id=".$this->getRefId()."\" target=\"_blank\">".
			$lang->txt("chat_to_chat_body")."</a>";

		return $body;
	}

	// Protected
	function __initChatRecording()
	{
		if(!is_object($this->chat_recording))
		{
			include_once 'Modules/Chat/classes/class.ilChatRecording.php';

			$this->chat_recording = new ilChatRecording($this->getId());

			return true;
		}
		return false;
	}

	function _getPublicChatRefId()
	{
		static $public_chat_ref_id = 0;

		global $tree;

		if($public_chat_ref_id)
		{
			return $public_chat_ref_id;
		}
		else
		{
			foreach($tree->getSubTree($tree->getNodeData(SYSTEM_FOLDER_ID)) as $node)
			{
				if($node['type'] == 'chat')
				{
					return $public_chat_ref_id = $node['child'];
				}
			}
		}
		return false;
	}
			
	// SET/GET
} // END class.ilObjTest
?>