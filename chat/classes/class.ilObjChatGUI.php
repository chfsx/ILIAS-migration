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
* Class ilObjTestGUI
*
* @author Stefan Meyer <smeyer@databay.de>
* $Id$
*
* @extends ilObjectGUI
* @package chat
*/

require_once "classes/class.ilObjectGUI.php";

class ilObjChatGUI extends ilObjectGUI
{
	var $target_script = "adm_object.php";
	var $in_module = false;

	/**
	* Constructor
	* @access public
	*/
	function ilObjChatGUI($a_data,$a_id,$a_call_by_reference = true, $a_prepare_output = true)
	{
		define("ILIAS_MODULE","chat");
		
		$this->type = "chat";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference, $a_prepare_output);

		if(is_object($this->object->chat_user))
		{
			$this->object->chat_user->setUserId($_SESSION["AccountId"]);
		}
	}
	function setTargetScript($a_script)
	{
		$this->target_script = $a_script;
	}
	function getTargetScript($a_params)
	{
		return $this->target_script."?".$a_params;
	}
	function setInModule($in_module)
	{
		$this->in_module = $in_module;
	}
	function inModule()
	{
		return $this->in_module;
	}

	function saveObject()
	{
		$new_obj =& parent::saveObject();

		ilUtil::redirect($this->getReturnLocation("save","adm_object.php?ref_id=".$new_obj->getRefId()));
	}

	function cancelObject()
	{
		unset($_SESSION["room_id_rename"]);
		unset($_SESSION["room_id_delete"]);
		parent::cancelObject();
	}


	function viewObject()
	{

		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
 		}
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.chat_view.html",true);

		// CHAT SERVER NOT ACTIVE
		if(!$this->object->server_comm->isAlive() or !$this->ilias->getSetting("chat_active"))
		{
			sendInfo($this->lng->txt("chat_server_not_active"));
		}

		// DELETE ROOMS CONFIRM
		$checked = array();
		if($_SESSION["room_id_delete"])
		{
			$checked = $_SESSION["room_id_delete"];
			sendInfo($this->lng->txt("chat_delete_sure"));
			$this->tpl->setVariable("TXT_DELETE_CANCEL",$this->lng->txt("cancel"));
			$this->tpl->setVariable("TXT_DELETE_CONFIRM",$this->lng->txt("delete"));
		}

		// SHOW ROOMS TABLE
		$this->tpl->setVariable("FORMACTION",$this->getTargetScript("cmd=gateway&ref_id=".
																	$this->ref_id));
		$this->tpl->setVariable("TXT_CHATROOMS",$this->lng->txt("chat_chatrooms"));

		$counter = 0;
		$this->object->chat_room->setOwnerId($_SESSION["AccountId"]);
		$rooms = $this->object->chat_room->getRoomsOfObject();
		$script = $this->inModule() ? "./chat.php" : "./chat/chat.php";

		// ADD PUBLIC ROOM
		// CHAT SERVER  ACTIVE
		if($this->object->server_comm->isAlive() and $this->ilias->getSetting("chat_active"))
		{
			$this->tpl->setCurrentBlock("active");
			$this->tpl->setVariable("ROOM_LINK",$script."?ref_id=".$this->ref_id."&room_id=0");
			$this->tpl->setVariable("ROOM_TARGET","_blank");
			$this->tpl->setVariable("ROOM_TXT_LINK",$this->lng->txt("show"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$this->tpl->touchBlock("not_active");
		}
		$this->tpl->setCurrentBlock("tbl_rooms_row");
		$this->tpl->setVariable("ROWCOL",++$counter % 2 ? "tblrow1" : "tblrow2");
		$this->tpl->setVariable("ROOM_CHECK",
								ilUtil::formCheckbox(in_array(0,$checked) ? 1 : 0,
													 "del_id[]",
													 0));
		$this->tpl->setVariable("ROOM_NAME",$this->object->getTitle()." ".$this->lng->txt("chat_public_room"));
		$this->tpl->parseCurrentBlock();

		$script = $this->inModule() ? "./chat.php" : "./chat/chat.php";



		foreach($rooms as $room)
		{
			// CHAT SERVER  ACTIVE
			if($this->object->server_comm->isAlive() and $this->ilias->getSetting("chat_active"))
			{
				$this->tpl->setCurrentBlock("active");
				$this->tpl->setVariable("ROOM_LINK",$script."?ref_id=".$this->ref_id."&room_id=".$room["room_id"]);
				$this->tpl->setVariable("ROOM_TARGET","_blank");
				$this->tpl->setVariable("ROOM_TXT_LINK",$this->lng->txt("show"));
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->touchBlock("not_active");
			}
			$this->tpl->setCurrentBlock("tbl_rooms_row");
			$this->tpl->setVariable("ROWCOL",++$counter % 2 ? "tblrow1" : "tblrow2");
			$this->tpl->setVariable("ROOM_CHECK",
									ilUtil::formCheckbox(in_array($room["room_id"],$checked) ? 1 : 0,
									"del_id[]",
									$room["room_id"]));

			$this->tpl->setVariable("ROOM_NAME",$room["title"]);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("has_rooms");
		$this->tpl->setVariable("TBL_FOOTER_IMG_SRC",substr(ilUtil::getImagePath("arrow_downright.gif"),1));
		$this->tpl->setVariable("TBL_FOOTER_SELECT",$this->__showAdminRoomSelect(count($rooms)));
		$this->tpl->setVariable("FOOTER_HAS_ROOMS_OK",$this->lng->txt("ok"));
		$this->tpl->parseCurrentBlock();
		$this->tpl->setVariable("TBL_FOOTER_ADD_SELECT",$this->__showAdminAddRoomSelect());
		$this->tpl->setVariable("FOOTER_OK",$this->lng->txt("add"));
	}
	function adminRoomsObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		if(!isset($_POST["del_id"]))
		{
			$this->ilias->raiseError($this->lng->txt("chat_select_one_room"),$this->ilias->error_obj->MESSAGE);
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.chat_edit_room.html",true);
		$this->tpl->setVariable("FORMACTION",$this->getTargetScript("cmd=gateway&ref_id=".$this->ref_id));
		$this->tpl->setVariable("TXT_ROOM_NAME",$this->lng->txt("chat_room_name"));
		$this->tpl->setVariable("ROOM_CANCEL",$this->lng->txt("cancel"));
	
		switch($_POST["action"])
		{
			case "renameRoom":
				if(count($_POST["del_id"]) > 1)
				{
					$this->ilias->raiseError($this->lng->txt("chat_select_one_room"),$this->ilias->error_obj->MESSAGE);
				}
				if(in_array(0,$_POST["del_id"]))
				{
					$this->ilias->raiseError($this->lng->txt("chat_no_rename_public"),$this->ilias->error_obj->MESSAGE);
				}

				// STORE ID IN SESSION
				$_SESSION["room_id_rename"] = (int) $_POST["del_id"][0];

				$room =& new ilChatRoom($this->ref_id);
				$room->setRoomId($_SESSION["room_id_rename"]);

				$this->tpl->setVariable("TXT_EDIT_CHATROOMS",$this->lng->txt("chat_chatroom_rename"));
				$this->tpl->setVariable("ROOM_NAME",$room->getTitle());
				$this->tpl->setVariable("CMD","renameRoom");
				$this->tpl->setVariable("ROOM_EDIT",$this->lng->txt("rename"));
				break;

			case "deleteRoom":
				if(in_array(0,$_POST["del_id"]))
				{
					$this->ilias->raiseError($this->lng->txt("chat_no_delete_public"),$this->ilias->error_obj->MESSAGE);
				}
				$_SESSION["room_id_delete"] = $_POST["del_id"];
				header("location: ".$this->getTargetScript("cmd=gateway&ref_id=".$this->ref_id));
				exit;

			case "exportRoom":
				$this->__exportRooms();
				break;

			case "refreshRoom":
				if(in_array(0,$_POST["del_id"]))
				{
					$this->ilias->raiseError($this->lng->txt("chat_no_refresh_public"),$this->ilias->error_obj->MESSAGE);
				}
				foreach($_POST["del_id"] as $room_id)
				{
					$this->object->chat_room->setRoomId($room_id);
					$this->object->server_comm->setType("delete");
					$this->object->server_comm->send();
					$this->object->chat_room->deleteAllMessages();
				}
				sendInfo($this->lng->txt("chat_messages_deleted"),true);
				header("location: ".$this->getTargetScript("cmd=gateway&ref_id=".$this->ref_id));
				exit;
		}	
		
	}
	function confirmedDeleteRoomObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		if(!$_SESSION["room_id_delete"])
		{
			$this->ilias->raiseError($this->lng->txt("chat_select_one_room"),$this->ilias->error_obj->MESSAGE);
		}
		$this->object->chat_room->setOwnerId($_SESSION["AccountId"]);
		if(!$this->object->chat_room->deleteRooms($_SESSION["room_id_delete"]))
		{
			$this->ilias->raiseError($this->object->chat_room->getErrorMessage(),$this->ilias->error_obj->MESSAGE);
		}
		unset($_SESSION["room_id_delete"]);
		sendInfo($this->lng->txt("chat_rooms_deleted"),true);

		header("location: ".$this->getTargetScript("cmd=gateway&ref_id=".$this->ref_id));
		exit;
	}

	function addRoomObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		$room =& new ilChatRoom($this->ref_id);
		$room->setTitle(ilUtil::stripSlashes($_POST["room_name"]));
		$room->setOwnerId($_SESSION["AccountId"]);

		if(!$room->validate())
		{
			$this->ilias->raiseError($room->getErrorMessage(),$this->ilias->error_obj->MESSAGE);
		}
		$room->add();

		sendInfo("chat_room_added");
		header("location: ".$this->getTargetScript("cmd=gateway&ref_id=".$this->ref_id));
		exit;
	}

	function renameRoomObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		$room =& new ilChatRoom($this->ref_id);
		$room->setRoomId($_SESSION["room_id_rename"]);
		$room->setTitle(ilUtil::stripSlashes($_POST["room_name"]));
		if(!$room->validate())
		{
			$this->ilias->raiseError($room->getErrorMessage(),$this->ilias->error_obj->MESSAGE);
		}
		$room->rename();

		unset($_SESSION["room_id_rename"]);
		sendInfo("chat_room_renamed");
		header("location: ".$this->getTargetScript("cmd=gateway&ref_id=".$this->ref_id));
		exit;
	}		
		


	function adminAddRoomObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.chat_edit_room.html",true);
		$this->tpl->setVariable("FORMACTION",$this->getTargetScript("cmd=gateway&ref_id=".$this->ref_id));
		$this->tpl->setVariable("TXT_ROOM_NAME",$this->lng->txt("chat_room_name"));
		$this->tpl->setVariable("ROOM_CANCEL",$this->lng->txt("cancel"));
	
		$this->tpl->setVariable("TXT_EDIT_CHATROOMS",$this->lng->txt("chat_chatroom_rename"));
		$this->tpl->setVariable("ROOM_NAME","");
		$this->tpl->setVariable("CMD","addRoom");
		$this->tpl->setVariable("ROOM_EDIT",$this->lng->txt("add"));

	}	

	function showFrames()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}

		// LOAD FRAMESET
		$this->tpl = new ilTemplate("tpl.chat_start.html",false,false,true);
		$this->tpl->setVariable("USER_TARGET","chat.php?cmd=showUserFrame&ref_id=".
								$this->object->getRefId()."&room_id=".
								$_REQUEST["room_id"]."&p_id=".$_GET["p_id"]);
		$this->tpl->setVariable("TOP_TARGET","chat.php?cmd=showTopFrame&ref_id=".
								$this->object->getRefId()."&room_id=".
								$_REQUEST["room_id"]."&p_id=".$_GET["p_id"]);
		$this->tpl->setVariable("SERVER_TARGET",$this->object->server_comm->getServerFrameSource());
		$this->tpl->setVariable("INPUT_TARGET","chat.php?cmd=showInputFrame&ref_id=".
								$this->object->getRefId()."&room_id=".
								$_REQUEST["room_id"]."&p_id=".$_GET["p_id"]);
		$this->tpl->setVariable("RIGHT_TARGET","chat.php?cmd=showRightFrame&ref_id=".
								$this->object->getRefId()."&room_id=".
								$_REQUEST["room_id"]."&p_id=".$_GET["p_id"]);
	}
	function showUserFrame()
	{
		$this->object->chat_room->setUserId($_SESSION["AccountId"]);
		$this->object->chat_room->updateLastVisit();

		$this->__loadStylesheet(true);
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.chat_user_frame.html",true);

		if($_REQUEST["room_id"])
		{
			$this->tpl->setVariable("TITLE",$this->object->chat_room->getTitle());
		}
		else
		{
			$this->tpl->setVariable("TITLE",$this->object->getTitle());
		}

		$this->__showRooms();
		$this->__showActiveUsers();
		if($this->object->chat_room->isOwner())
		{
			$this->__showOnlineUsers();
		}
		
	}
	function showTopFrame()
	{
		$this->__loadStylesheet();
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.chat_top_frame.html",true);
	}
	function showInputFrame()
	{
		$this->__loadStylesheet();
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.chat_input_frame.html",true);

		if($this->error)
		{
			sendInfo($this->error);
		}
		if($_GET["p_id"])
		{
			$user_obj =& new ilObjUser((int) $_GET["p_id"]);
			$message = $this->lng->txt("chat_private_message")." ";
			$message .= $this->object->chat_user->getLogin()." -> ".$user_obj->getLogin();
			sendInfo($message);
		}
		
		$this->tpl->setVariable("FORMACTION","chat.php?cmd=gateway&ref_id=".
								$this->object->getRefId()."&room_id=".
								$_REQUEST["room_id"]."&p_id=".$_GET["p_id"]);

		$this->tpl->setVariable("TXT_COLOR",$this->lng->txt("chat_color"));
		$this->tpl->setVariable("TXT_TYPE",$this->lng->txt("chat_type"));
		$this->tpl->setVariable("TXT_FACE",$this->lng->txt("chat_face"));
		$this->tpl->setVariable("TXT_INPUT",$this->lng->txt("chat_input"));

		if($_GET["p_id"])
		{
			$this->tpl->setCurrentBlock("whisper");
			$this->tpl->setVariable("TXT_SUBMIT_CANCEL",$this->lng->txt("cancel"));
			$this->tpl->parseCurrentBlock();
			$this->tpl->setVariable("TXT_SUBMIT_OK",$this->lng->txt("ok"));
		}
		else
		{
			$this->tpl->setVariable("TXT_SUBMIT_OK",$this->lng->txt("ok"));
		}
		$this->tpl->setVariable("SELECT_COLOR",$this->__getColorSelect());
		$this->tpl->setVariable("RADIO_TYPE",$this->__getFontType());
		$this->tpl->setVariable("CHECK_FACE",$this->__getFontFace());


	}
	function showRightFrame()
	{
		$this->__loadStylesheet();
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.chat_right_frame.html",true);
	}

	function cancel()
	{
		unset($_GET["p_id"]);

		$this->showInputFrame();
	}


	function input()
	{
		if(!$_POST["message"])
		{
			sendInfo($this->lng->txt("chat_insert_message"),true);
		}
		if($_POST["message"] and $this->object->chat_room->checkWriteAccess())
		{
			// FORMAT MESSAGE
			$message = $this->__formatMessage();
			
			// SET MESSAGE AND SEND IT
			$this->object->server_comm->setMessage($message);
			if((int) $_GET["p_id"])
			{
				$this->object->server_comm->setType('private');
			}
			if(!$this->object->server_comm->send())
			{
				$this->error = $this->lng->txt("chat_no_connection");
			}
		}
		$this->showInputFrame();
	}

	function invite()
	{
		if($_GET["i_id"])
		{
			$this->object->chat_room->invite((int) $_GET["i_id"]);
			$this->object->sendMessage((int) $_GET["i_id"]);
			sendInfo($this->lng->txt("chat_user_invited"),true);
			$this->showFrames();
		}
	}
	function drop()
	{
		if($_GET["i_id"])
		{
			$this->object->chat_room->drop((int) $_GET["i_id"]);

			$tmp_user =& new ilObjUser($_GET["i_id"]);
			$this->object->server_comm->setKickedUser($tmp_user->getLogin());
			$this->object->server_comm->setType("kick");
			$this->object->server_comm->send();
			sendInfo($this->lng->txt("chat_user_dropped"),true);
			$this->showFrames();
		}
	}
	function closeFrame()
	{
		$this->__loadStylesheet(true);
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.chat_close.html",true);
		sendInfo("Your session is expired please login to ILIAS.");
		$this->tpl->touchBlock("content");
	}

	function export()
	{
		$tmp_tpl =& new ilTemplate("tpl.chat_export.html",true,true,true);

		if($this->object->chat_room->getRoomId())
		{
			$tmp_tpl->setVariable("CHAT_NAME",$this->object->chat_room->getTitle());
		}
		else
		{
			$tmp_tpl->setVariable("CHAT_NAME",$this->object->getTitle());
		}
		$tmp_tpl->setVariable("CHAT_DATE",strftime("%c",time()));
		$tmp_tpl->setVariable("CONTENT",$this->object->chat_room->getAllMessages());
		ilUtil::deliverData($tmp_tpl->get(),"1.html");
		exit;
	}
		
		

	// PRIVATE
	function __showOnlineUsers()
	{
		$users = $this->object->chat_room->getOnlineUsers();
		if(count($users) <= 1)
		{
			$this->tpl->setCurrentBlock("no_actice");
			$this->tpl->setVariable("NO_ONLINE_USERS",$this->lng->txt("chat_no_active_users"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$counter = 0;
			foreach($users as $user)
			{
				if($user["user_id"] == $_SESSION["AccountId"])
				{
					continue;
				}
				if(!($counter%2))
				{
					$this->tpl->touchBlock("online_row_start");
				}
				else
				{
					$this->tpl->touchBlock("online_row_end");
				}

				$this->tpl->setCurrentBlock("online");
				$this->tpl->setVariable("ONLINE_FONT_A",$_GET["p_id"] == $user["user_id"] ? "smallred" : "small");
				
				if($this->object->chat_room->isInvited($user["user_id"]))
				{
					$img = "minus.gif";
					$cmd = "drop";
				}
				else
				{
					$img = "plus.gif";
					$cmd = "invite";
				}
				$this->tpl->setVariable("ONLINE_LINK_A","chat.php?cmd=".$cmd.
										"&ref_id=".$this->ref_id."&room_id=".
										$_REQUEST["room_id"]."&i_id=".$user["user_id"]);
				$this->tpl->setVariable("TXT_INVITE_USER",$cmd == "invite" ? $this->lng->txt("chat_invite_user") :
					$this->lng->txt("chat_drop_user"));
				$this->tpl->setVariable("ONLINE_USER_NAME_A",$user["login"]);
				$this->tpl->setVariable("INVITE_IMG_SRC",ilUtil::getImagePath($img,true));
				
				$this->tpl->parseCurrentBlock();
				$counter++;
			}
		}
		$this->tpl->setCurrentBlock("show_online");
		$this->tpl->setVariable("ONLINE_USERS",$this->lng->txt("chat_online_users"));
		$this->tpl->parseCurrentBlock();
	}
	function __showActiveUsers()
	{
		if(isset($_GET["a_users"]))
		{
			if($_GET["a_users"])
			{
				$_SESSION["a_users"] = true;
			}
			else
			{
				$_SESSION["a_users"] = 0;
				unset($_SESSION["a_users"]);
			}
		}

		$hide = $_SESSION["a_users"] ? true : false;

		$this->tpl->setVariable("ACTIVE_USERS",$this->lng->txt("chat_active_users"));
		$this->tpl->setVariable("DETAILS_B_TXT",$hide ? $this->lng->txt("chat_show_details") : $this->lng->txt("chat_hide_details"));
		$this->tpl->setVariable("DETAILS_B","chat.php?ref_id=".$this->object->getRefId().
								"&room_id=".$this->object->chat_room->getRoomId().
								"&a_users=".($hide ? 0 : 1)."&cmd=showUserFrame");

		if($hide)
		{
			return true;
		}
		$users = $this->object->chat_room->getActiveUsers();
		if(count($users) <= 1)
		{
			$this->tpl->setCurrentBlock("no_actice");
			$this->tpl->setVariable("NO_ACTIVE_USERS",$this->lng->txt("chat_no_active_users"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$user_obj =& new ilObjUser();
			$counter = 0;
			foreach($users as $user)
			{
				if($user == $_SESSION["AccountId"])
				{
					continue;
				}
				if(!($counter%2))
				{
					$this->tpl->touchBlock("active_row_start");
				}
				else
				{
					$this->tpl->touchBlock("active_row_end");
				}
				$user_obj->setId($user);
				$user_obj->read();

				$this->tpl->setCurrentBlock("active");
				$this->tpl->setVariable("ACTIVE_FONT_A",$_GET["p_id"] == $user ? "smallred" : "small");
				$this->tpl->setVariable("ACTIVE_LINK_A","chat.php?cmd=showInputFrame&".
										"ref_id=".$this->ref_id."&room_id=".
										$_REQUEST["room_id"]."&p_id=".$user);
				$this->tpl->setVariable("ACTIVE_USER_NAME_A",$user_obj->getLogin());
				$this->tpl->parseCurrentBlock();
				$counter++;
			}
		}
	}
	function __showAdminAddRoomSelect()
	{
		$opt = array("createRoom" => $this->lng->txt("chat_room_select"));

		return ilUtil::formSelect("","action_b",$opt,false,true);
	}

	function __showAdminRoomSelect()
	{
		if(count($this->object->chat_room->getRoomsOfObject()))
		{
			$opt = array("renameRoom" => $this->lng->txt("rename"),
						 "refreshRoom" => $this->lng->txt("chat_refresh"),
						 "exportRoom" => $this->lng->txt("chat_html_export"),
						 "deleteRoom" => $this->lng->txt("delete"));
		}
		else
		{
			$opt = array("exportRoom" => $this->lng->txt("chat_html_export"));
		}
		if(!$this->object->server_comm->isAlive() or !$this->ilias->getSetting("chat_active"))
		{
			unset($opt["refreshRoom"]);
		}
		return ilUtil::formSelect(isset($_SESSION["room_id_delete"]) ? "deleteRoom" : "",
								  "action",
								  $opt,
								  false,
								  true);
	}

	function __showRooms()
	{
		$public_rooms = $this->object->chat_room->getAllRooms();
		$private_rooms = $this->object->chat_room->getRooms();

		if(isset($_GET["h_rooms"]))
		{
			if($_GET["h_rooms"])
			{
				$_SESSION["h_rooms"] = true;
			}
			else
			{
				$_SESSION["h_rooms"] = 0;
				unset($_SESSION["h_rooms"]);
			}
		}
		$hide = $_SESSION["h_rooms"] ? true : false;

		$this->tpl->setVariable("ROOMS_ROOMS",$this->lng->txt("chat_rooms"));
		$this->tpl->setVariable("DETAILS_TXT",$hide ? $this->lng->txt("chat_show_details") : $this->lng->txt("chat_hide_details"));
		$this->tpl->setVariable("ROOMS_COUNT",count($public_rooms) + count($private_rooms));
		$this->tpl->setVariable("DETAILS_A","chat.php?ref_id=".$this->object->getRefId().
								"&room_id=".$this->object->chat_room->getRoomId().
								"&h_rooms=".($hide ? 0 : 1)."&cmd=showUserFrame");

		if($hide)
		{
			return true;
		}

		$counter = 0;
		foreach($public_rooms as $room)
		{
			$this->tpl->setCurrentBlock("room_row");
			$this->tpl->setVariable("ROOM_ROW_CSS",++$counter%2 ? "tblrow1" : "tblrow2");
			$this->tpl->setVariable("ROOM_LINK","chat.php?ref_id=".$room["child"]);
			$this->tpl->setVariable("ROOM_TARGET","_top");
			$this->tpl->setVariable("ROOM_NAME",$room["title"]);
			$this->tpl->setVariable("ROOM_ONLINE",$this->object->chat_room->getCountActiveUser($room["child"],0));
			$this->tpl->parseCurrentBlock();
		}

		foreach($private_rooms as $room)
		{
			$this->tpl->setCurrentBlock("room_row");
			$this->tpl->setVariable("ROOM_ROW_CSS",++$counter%2 ? "tblrow1" : "tblrow2");
			$this->tpl->setVariable("ROOM_LINK","chat.php?ref_id=".$room["chat_id"].
																		 "&room_id=".$room["room_id"]);
			$this->tpl->setVariable("ROOM_TARGET","_top");
			$this->tpl->setVariable("ROOM_NAME",$room["title"]);
			$this->tpl->setVariable("ROOM_ONLINE",$this->object->chat_room->getCountActiveUser($room["chat_id"],$room["room_id"]));
			$this->tpl->parseCurrentBlock();
		}

	}
	function __loadStylesheet($expires = false)
	{
		$this->tpl->setCurrentBlock("ChatStyle");
		$this->tpl->setVariable("LOCATION_CHAT_STYLESHEET",ilUtil::getStyleSheetLocation());
		if($expires)
		{
			$this->tpl->setVariable("EXPIRES","<meta http-equiv=\"expires\" content=\"now\">".
									"<meta http-equiv=\"refresh\" content=\"30\">");
		}
		$this->tpl->parseCurrentBlock();
	}

	function __getColorSelect()
	{
		$colors = array("black" => $this->lng->txt("chat_black"),
						"red" => $this->lng->txt("chat_red"),
						"green" => $this->lng->txt("chat_green"),
						"maroon" => $this->lng->txt("chat_maroon"),
						"olive" => $this->lng->txt("chat_olive"),
						"navy" => $this->lng->txt("chat_navy"),
						"purple" => $this->lng->txt("chat_purple"),
						"teal" => $this->lng->txt("chat_teal"),
						"silver" => $this->lng->txt("chat_silver"),
						"gray" => $this->lng->txt("chat_gray"),
						"lime" => $this->lng->txt("chat_lime"),
						"yellow" => $this->lng->txt("chat_yellow"),
						"fuchsia" => $this->lng->txt("chat_fuchsia"),
						"aqua" => $this->lng->txt("chat_aqua"),
						"blue" => $this->lng->txt("chat_blue"));

		return ilUtil::formSelect($_POST["color"],"color",$colors,false,true);
	}

	function __getFontType()
	{
		$types = array("times" => $this->lng->txt("chat_times"),
					   "tahoma" => $this->lng->txt("chat_tahoma"),
					   "arial" => $this->lng->txt("chat_arial"));

		$_POST["type"] = $_POST["type"] ? $_POST["type"] : "times";

		foreach($types as $name => $type)
		{
			$this->tpl->setCurrentBlock("FONT_TYPES");
			$this->tpl->setVariable("BL_TXT_TYPE",$type);
			$this->tpl->setVariable("FONT_TYPE",$name);
			$this->tpl->setVariable("TYPE_CHECKED",$_POST["type"] == $name ? "checked=\"checked\"" : "");
			$this->tpl->parseCurrentBlock();
		}
	}

	function __getFontFace()
	{
		$_POST["face"] = is_array($_POST["face"]) ? $_POST["face"] : array();

		$types = array("bold" => $this->lng->txt("chat_bold"),
					   "italic" => $this->lng->txt("chat_italic"),
					   "underlined" => $this->lng->txt("chat_underlined"));

		$this->tpl->setCurrentBlock("FONT_FACES");
		$this->tpl->setVariable("BL_TXT_FACE","<b>".$this->lng->txt("chat_bold")."</b>");
		$this->tpl->setVariable("FONT_FACE","bold");
		$this->tpl->setVariable("FACE_CHECKED",in_array("bold",$_POST["face"]) ? "checked=\"checked\"" : "");
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("FONT_FACES");
		$this->tpl->setVariable("BL_TXT_FACE","<i>".$this->lng->txt("chat_italic")."</i>");
		$this->tpl->setVariable("FONT_FACE","italic");
		$this->tpl->setVariable("FACE_CHECKED",in_array("italic",$_POST["face"]) ? "checked=\"checked\"" : "");
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("FONT_FACES");
		$this->tpl->setVariable("BL_TXT_FACE","<u>".$this->lng->txt("chat_underlined")."</u>");
		$this->tpl->setVariable("FONT_FACE","underlined");
		$this->tpl->setVariable("FACE_CHECKED",in_array("underlined",$_POST["face"]) ? "checked=\"checked\"" : "");
		$this->tpl->parseCurrentBlock();
	}

	function __formatMessage()
	{
		$tpl = new ilTemplate("tpl.chat_message.html",true,true,true);

		$tpl->setVariable("MESSAGE",$_POST["message"]);
		$tpl->setVariable("FONT_COLOR",$_POST["color"]);
		$tpl->setVariable("FONT_FACE",$_POST["type"]);

		if($_GET["p_id"])
		{
			$user_obj =& new ilObjUser((int) $_SESSION["AccountId"]);
			$user_obj->read();

			$tpl->setCurrentBlock("private");
			$tpl->setVariable("PRIVATE_U_COLOR","red");
			$tpl->setVariable("PRIVATE_FROM",$user_obj->getLogin());

			$user_obj =& new ilObjUser((int) $_GET["p_id"]);
			$user_obj->read();
			$tpl->setVariable("PRIVATE_TO",$user_obj->getLogin());
			$tpl->parseCurrentBlock();
		}
		else
		{
			$tpl->setCurrentBlock("normal");
			$tpl->setVariable("NORMAL_U_COLOR","navy");
			$tpl->setVariable("NORMAL_UNAME",$this->object->chat_user->getLogin());
			$tpl->parseCurrentBlock();
		}
		// OPEN TAGS
		if($_POST["face"])
		{
			foreach($_POST["face"] as $face)
			{
				$tpl->setCurrentBlock("type_open");
				switch($face)
				{
					case "bold":
						$tpl->setVariable("TYPE_TYPE_O","b");
						break;
					case "italic":
						$tpl->setVariable("TYPE_TYPE_O","i");
						break;

					case "underlined":
						$tpl->setVariable("TYPE_TYPE_O","u");
						break;
				}
				$tpl->parseCurrentBlock();
			}
			$_POST["face"] = array_reverse($_POST["face"]);
			foreach($_POST["face"] as $face)
			{
				$tpl->setCurrentBlock("type_close");
				switch($face)
				{
					case "bold":
						$tpl->setVariable("TYPE_TYPE_C","b");
						break;
					case "italic":
						$tpl->setVariable("TYPE_TYPE_C","i");
						break;

					case "underlined":
						$tpl->setVariable("TYPE_TYPE_C","u");
						break;
				}
				$tpl->parseCurrentBlock();
			}
		}

		$message = preg_replace("/\r/","",$tpl->get());
		$message = preg_replace("/\n/","",$message);

		return $message;
	}
	function __exportRooms()
	{
		include_once "chat/classes/class.ilFileDataChat.php";

		if(count($_POST["del_id"]) == 1)
		{
			$this->object->chat_room->setRoomId($_POST["del_id"][0]);
			$this->export();
		}

		$file_obj =& new ilFileDataChat($this->object);

		foreach($_POST["del_id"] as $id)
		{
			$this->object->chat_room->setRoomId((int) $id);

			$tmp_tpl =& new ilTemplate("tpl.chat_export.html",true,true,true);
			
			if($id)
			{
				$tmp_tpl->setVariable("CHAT_NAME",$this->object->chat_room->getTitle());
			}
			else
			{
				$tmp_tpl->setVariable("CHAT_NAME",$this->object->getTitle());
			}
			$tmp_tpl->setVariable("CHAT_DATE",strftime("%c",time()));
			$tmp_tpl->setVariable("CONTENT",$this->object->chat_room->getAllMessages());

			$file_obj->addFile("chat_".$this->object->chat_room->getRoomId().".html",$tmp_tpl->get());
		}
		$fname = $file_obj->zip();
		ilUtil::deliverFile($fname,"ilias_chat.zip");
	}
} // END class.ilObjChatGUI
?>
