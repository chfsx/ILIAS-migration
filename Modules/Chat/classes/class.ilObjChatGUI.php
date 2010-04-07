<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
* Class ilObjChatGUI
*
* @author Stefan Meyer <meyer@leifos.com>
* $Id$
* 
* @ilCtrl_Calls ilObjChatGUI: ilPermissionGUI, ilPublicUserProfileGUI
* @ilCtrl_Calls ilObjChatGUI: ilInfoScreenGUI, ilObjectCopyGUI
* @extends ilObjectGUI
*/

require_once "classes/class.ilObjectGUI.php";
require_once "Modules/Chat/classes/class.ilChatRecording.php";

class ilObjChatGUI extends ilObjectGUI
{
	var $target_script = "adm_object.php";
	var $in_module = false;

	/**
	* Constructor
	* @access public
	*/
	public function __construct($a_data,$a_id,$a_call_by_reference = true, $a_prepare_output = true)
	{
		global $ilCtrl, $lng;

		$lng->loadLanguageModule('chat');
		
		$this->type = "chat";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference, $a_prepare_output);

		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this,array("ref_id","cmdClass"));


		if(is_object($this->object->chat_user))
		{
			$this->object->chat_user->setUserId($_SESSION["AccountId"]);
		}
	}

	public function &executeCommand()
	{
		global $rbacsystem;

		if($_GET["baseClass"] == "ilChatPresentationGUI")
		{
			$this->__prepareOutput();
		}
		else
		{
			$this->prepareOutput();
		}

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();


		switch($next_class)
		{
			case 'ilpermissiongui':
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			case "ilpublicuserprofilegui":
				include_once("./Services/User/classes/class.ilPublicUserProfileGUI.php");
				$profile_gui = new ilPublicUserProfileGUI((int)$_GET['user']);
				$ret = $this->ctrl->forwardCommand($profile_gui);
				break;

			case 'ilinfoscreengui':
				//$this->prepareOutput();
				$this->infoScreen();
				break;

			default:
				if(!$cmd)
				{
					$cmd = "view";
				}
				$cmd .= "Object";
				$this->$cmd();
					
				break;
		}

		return true;
	}
	
	public function testObject()
	{
		global $tpl;
		include_once 'Services/YUI/classes/class.ilYuiUtil.php';
		ilYuiUtil::initTreeView();
		$testTpl = new ilTemplate('tpl.test_treeview.html', true, true, 'Modules/Chat');
		$testTpl->setVariable('SOME_CONTENT','asdf');
		$tpl->setContent($testTpl->get());
		$tpl->show();
		exit;
	}
	
	/**
	 * Cancel
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function cancelObject()
	{
		unset($_SESSION["room_id_rename"]);
		unset($_SESSION["room_id_delete"]);
		unset($_SESSION['saved_post']);
		//ilUtil::sendInfo($this->lng->txt("msg_cancel"), true);
		$this->ctrl->redirect($this);
	}

	/**
	* should be overwritten to add object specific items
	* (repository items are preloaded)
	*/
	public function addAdminLocatorItems()
	{
		global $ilLocator,$tree,$ilObjDataCache;
		
		if ($_GET["admin_mode"] == "settings")	// system settings
		{
			$ilLocator->addItem($this->lng->txt("administration"),
				$this->ctrl->getLinkTargetByClass("iladministrationgui", "frameset"),
				ilFrameTargetInfo::_getFrame("MainContent"));

			// add chat settings
			$chat_settings_ref_id = $tree->getParentId($this->object->getRefId());
			$chat_settings_obj_id = $ilObjDataCache->lookupObjId($chat_settings_ref_id);

			$this->ctrl->setParameterByClass('ilobjchatservergui','ref_id',$chat_settings_ref_id);
			$ilLocator->addItem($ilObjDataCache->lookupTitle($chat_settings_obj_id),
								$this->ctrl->getLinkTargetByClass(array('iladministrationgui','ilobjchatservergui'),
																  'view'));

			if ($this->object->getRefId() != SYSTEM_FOLDER_ID)
			{
				$ilLocator->addItem($this->object->getTitle(),
					$this->ctrl->getLinkTarget($this, "view"));
			}
		}
		else // repository administration
		{
			return parent::addAdminLocatorItems();
		}

	}


	public function setTargetScript($a_script)
	{
		$this->target_script = $a_script;
	}
	
	public function getTargetScript($a_params)
	{
		return $this->target_script."?".$a_params;
	}
	
	public function setInModule($in_module)
	{
		$this->in_module = $in_module;
	}
	
	public function inModule()
	{
		return $this->in_module;
	}

	public function saveObject()
	{
		global $ilUser,$rbacadmin;

		$new_obj =& parent::saveObject();
		
		// Add new moderator role
		$roles = $new_obj->initDefaultRoles();

		// Assign current user.
		$rbacadmin->assignUser($roles[0],$ilUser->getId());
		
		//$this->ctrl->setParameter($this, "ref_id", $new_obj->getRefId());
		ilUtil::redirect($this->getReturnLocation("save",
			"ilias.php?baseClass=ilChatHandlerGUI&ref_id=".$new_obj->getRefId()."&amp;cmd=view"));
	}

	// Methods for blocked users (administration)
	public function blockedUsersObject()
	{
		global $rbacsystem, $lng, $tpl, $ilCtrl;
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';
		
		if(!$rbacsystem->checkAccess('moderate',$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}

		include_once('Services/Table/classes/class.ilTable2GUI.php');
		$table = new ilTable2GUI($this, 'blockedUsers');
		
		$table->setTitle($lng->txt('chat_blocked_users'));
		$table->setId('cht_blocked_users_' . $this->object->getId());
		$table->setFormAction($ilCtrl->getFormAction($this));
		$table->addColumn('', 'checkbox', '2%', true);
		$table->addColumn($lng->txt('chat_user_name'), 'login');
		$table->addColumn($lng->txt('lastname'), 'lastname');
		$table->addColumn($lng->txt('firstname'), 'firstname');
		$table->addColumn($lng->txt('actions'));
		$table->setNoEntriesText($lng->txt('chat_no_blocked'));
		
		$table->setSelectAllCheckbox('blocked_check');
		$table->setRowTemplate('tpl.chat_blocked_users_row.html', 'Modules/Chat');
		
		$blocked_obj = new ilChatBlockedUsers($this->object->getId());
		$blocked_users = $blocked_obj->getBlockedUsers();
		$tabledata = array();		
		
		$table->addMultiCommand('unblockUsers', $lng->txt('chat_blocked_unlocked'));
		
		include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
		
		foreach($blocked_users as $usrId)
		{
			$current_selection_list = new ilAdvancedSelectionListGUI();
			$current_selection_list->setListTitle($lng->txt("actions"));
			$current_selection_list->setId("act_".$usrId);
			$ilCtrl->setParameter($this, 'blocked_check', $usrId);
			$current_selection_list->addItem($this->lng->txt("chat_blocked_unlocked"), '', $ilCtrl->getLinkTarget($this, 'unblockUsers'));
			
			$usr = new ilObjUser($usrId);
			$tabledata[] = array
			(
				'id' => $usrId,
				'login' => $usr->getLogin(),
				'firstname' => $usr->getFirstname(),
				'lastname' => $usr->getLastname(),
				'actions' => $current_selection_list->getHTML() 
			);
		}
		$table->setData($tabledata);
		
		// add blocked user
		
		// create form
		include_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

		$form = new ilPropertyFormGUI();
		$form->setTitle($lng->txt('chat_add_blocking'));
		$form->setFormAction($ilCtrl->getFormAction($this, 'blockUser'));

		$inp = new ilTextInputGUI($lng->txt('chat_user_name'), 'block');
		$form->addItem($inp);
				
		$form->addCommandButton('blockUser', $lng->txt('chat_block_user'));
		
		$tpl->setVariable('ADM_CONTENT', $table->getHTML() . "<br />" . $form->getHTML());
		
		return;
	}

	public function blockUserObject()
	{
		global $rbacsystem;
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';

		if(!$rbacsystem->checkAccess('moderate',$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}

		$blocked_obj = new ilChatBlockedUsers($this->object->getId());

		if(!$_POST['block'] or !($usr_id = ilObjUser::getUserIdByLogin($_POST['block'])))
		{
			ilUtil::sendFailure($this->lng->txt('chat_enter_valid_username'));
			$this->blockedUsersObject();

			return false;
		}
		if($blocked_obj->isBlocked($usr_id))
		{
			ilUtil::sendInfo($this->lng->txt('chat_user_already_blocked'));
			$this->blockedUsersObject();

			return false;
		}			

		$blocked_obj->block($usr_id);
		ilUtil::sendSuccess($this->lng->txt('chat_user_blocked'));
		$this->blockedUsersObject();

		return true;
	}

	public function unblockUsersObject()
	{
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';

		global $rbacsystem;

		if(!$rbacsystem->checkAccess('moderate',$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}

		$blocked_obj = new ilChatBlockedUsers($this->object->getId());

		if ($_GET['blocked_check'])
			$_POST['blocked_check'] = array($_GET['blocked_check']);
		
		if(!is_array($_POST['blocked_check']))
		{
			ilUtil::sendFailure($this->lng->txt('chat_no_users_selected'));
			return $this->blockedUsersObject();
		}

		foreach($_POST['blocked_check'] as $usr_id)
		{
			$blocked_obj->unblock($usr_id);
		}

		ilUtil::sendSuccess($this->lng->txt('chat_unblocked_user'));
		return $this->blockedUsersObject();
	}
	
	public function viewObject()
	{
		global $rbacsystem,$ilUser,$lng;
		
		// administration
		if (strtolower($_GET["baseClass"]) == "iladministrationgui")
		{
			parent::viewObject();
			return true;
		}
		
		// check general read access
		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
 		}

 		// check if chatserver is available
 		$isActive = ($this->object->server_comm->isAlive() && $this->ilias->getSetting("chat_active"));
		if(!$isActive)
		{
			ilUtil::sendFailure($this->lng->txt("chat_server_not_active"));
		}
 		
 		// check if user is blocked from current room
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';
 		if(ilChatBlockedUsers::_isBlocked($this->object->getId(),$ilUser->getId()))
		{
			ilUtil::sendInfo($this->lng->txt('chat_access_blocked'));
			return true;
		}

		// todo räume löschen? hier stand mal ein manueller confirm
		
		// get table data
		$rooms_unprepared = array();
		if($rbacsystem->checkAccess('write',$this->ref_id))
		{
			$rooms_unprepared = $this->object->chat_room->getAllRoomsOfObject();
		}
		else
		{
			$this->object->chat_room->setOwnerId($_SESSION["AccountId"]);
			$rooms_unprepared = $this->object->chat_room->getRoomsOfObject();
		}
		
		// prepare roomset for output and merge public room
		$rooms = array
			(
				array
				(
					'room_id' => 0,
					'title' => $this->object->getTitle()." ".$lng->txt("chat_public_room"),
					'usercount' => ilChatRoom::_getCountActiveUsers($this->object->getId()),
				)
			);
		$i = 1;
		foreach($rooms_unprepared as $key => $value)
		{
			$rooms[$i] = $rooms_unprepared[$key];
			$rooms[$i]['usercount'] = ilChatRoom::_getCountActiveUsers($this->object->getId(),$rooms_unprepared[$key]['room_id']);
			$i++;
		}
		
		// initialize table
		include_once 'Modules/Chat/classes/class.ilChatRoomsTableGUI.php';
		$tbl = new ilChatRoomsTableGUI($this, $rbacsystem->checkAccess('write',$this->object->getRefId()), $isActive);
		$tbl->setData($rooms);
		
		// permanent link
		include_once 'Services/PermanentLink/classes/class.ilPermanentLinkGUI.php';
		$permalink = new ilPermanentLinkGUI('chat', $this->object->getRefId());
		$this->tpl->setVariable('PRMLINK', $permalink->getHTML());
		
		$this->tpl->setVariable("ADM_CONTENT", $tbl->getHTML());
		
	}
	
// TODO: delete me
	public function adminRoomsObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		if(!isset($_POST["del_id"]))
		{
			ilUtil::sendInfo($this->lng->txt("chat_select_one_room"));
			$this->viewObject();
			
			return false;
		}

		switch($_POST["action"])
		{
			case "renameRoom":
				if(count($_POST["del_id"]) > 1)
				{
					ilUtil::sendInfo($this->lng->txt("chat_select_one_room"));
					$this->viewObject();

					return false;
				}
				if(in_array(0,$_POST["del_id"]))
				{
					ilUtil::sendFailure($this->lng->txt("chat_no_rename_public"));
					$this->viewObject();

					return false;
				}

				// STORE ID IN SESSION
				$_SESSION["room_id_rename"] = (int) $_POST["del_id"][0];

				$room =& new ilChatRoom($this->object->getId());
				$room->setRoomId($_SESSION["room_id_rename"]);

				$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.chat_edit_room.html","Modules/Chat");
				$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
				$this->tpl->setVariable("TXT_ROOM_NAME",$this->lng->txt("chat_room_name"));
				$this->tpl->setVariable("ROOM_CANCEL",$this->lng->txt("cancel"));
				$this->tpl->setVariable("TXT_EDIT_CHATROOMS",$this->lng->txt("chat_chatroom_rename"));
				$this->tpl->setVariable("ROOM_NAME",$room->getTitle());
				$this->tpl->setVariable("CMD","renameRoom");
				$this->tpl->setVariable("ROOM_EDIT",$this->lng->txt("rename"));
				break;

			case "deleteRoom":
				if(in_array(0,$_POST["del_id"]))
				{
					ilUtil::sendFailure($this->lng->txt("chat_no_delete_public"));
					$this->viewObject();

					return false;
				}
				$_SESSION["room_id_delete"] = $_POST["del_id"];
				$this->viewObject();

				return true;


			case "exportRoom":
				$this->__exportRooms();
				break;

			case "refreshRoom":
				if(in_array(0,$_POST["del_id"]) and !$rbacsystem->checkAccess('write',$this->object->getRefId()))
				{
					ilUtil::sendFailure($this->lng->txt("chat_no_refresh_public"));
					$this->viewObject();

					return true;
				}
				foreach($_POST["del_id"] as $room_id)
				{
					$this->object->chat_room->setRoomId($room_id);
					$this->object->server_comm->setType("delete");
					$this->object->server_comm->send();
					$this->object->chat_room->deleteAllMessages();
				}
				ilUtil::sendSuccess($this->lng->txt('chat_refreshed'));
				$this->viewObject();

				return true;
		}	
		
	}
	
	public function deleteRoomObject()
	{
		global $rbacsystem, $lng, $tpl, $ilCtrl;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		if ($_GET['del_id'])
			$_POST['del_id'] = array($_GET['del_id']);
		
		if(!isset($_POST["del_id"]))
		{
			ilUtil::sendInfo($lng->txt("chat_select_one_room"));
			$this->viewObject();
			return;
		}
		
		if(in_array(0,$_POST["del_id"]) and !$rbacsystem->checkAccess('write',$this->object->getRefId()))
		{
			ilUtil::sendFailure($this->lng->txt("chat_no_delete_public"));
			$this->viewObject();
			return;
		}
		
		include_once 'Services/Utilities/classes/class.ilConfirmationGUI.php';
		$conf = new ilConfirmationGUI();
		$conf->setFormAction($ilCtrl->getFormAction($this));
		$conf->setHeaderText($lng->txt('chat_confirm_delete'));
		foreach($_POST["del_id"] as $id)
		{
			$room = new ilChatRoom($this->object->getId());
			$room->setRoomId($id);
			$conf->addItem('del_id[]', $id, $room->getTitle());
		}
		$conf->setConfirm($lng->txt('confirm'), 'confirmedDeleteRoom');
		$conf->setCancel($lng->txt('cancel'), 'view');
		$tpl->setVariable('ADM_CONTENT', $conf->getHTML());
	}
	
	public function refreshRoomObject()
	{
		global $rbacsystem, $lng;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		if ($_GET['del_id'])
			$_POST['del_id'] = array($_GET['del_id']);
		
		if(!isset($_POST["del_id"]))
		{
			ilUtil::sendInfo($lng->txt("chat_select_one_room"));
			$this->viewObject();
			
			return false;
		}
		
		if(in_array(0,$_POST["del_id"]) &&!$rbacsystem->checkAccess('write',$this->object->getRefId()))
		{
			ilUtil::sendFailure($lng->txt("chat_no_refresh_public"));
			$this->viewObject();
			return;
		}
		foreach($_POST["del_id"] as $room_id)
		{
			$this->object->chat_room->setRoomId($room_id);
			$this->object->server_comm->setType("delete");
			$this->object->server_comm->send();
			$this->object->chat_room->deleteAllMessages();
		}
		ilUtil::sendSuccess($this->lng->txt('chat_refreshed'));
		$this->viewObject();
	}
	
	public function renameObject()
	{
		global $rbacsystem, $lng, $tpl, $ilCtrl;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		if(!isset($_GET["room_id"]))
		{
			ilUtil::sendInfo($this->lng->txt("chat_select_one_room"));
			$this->viewObject();
			return false;
		}

		if($_GET["room_id"] == 0)
		{
			ilUtil::sendFailure($this->lng->txt("chat_no_rename_public"));
			$this->viewObject();
			return false;
		}

		$room = new ilChatRoom($this->object->getId());
		$room->setRoomId($_GET['room_id']);
		
		// create form
		include_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

		$form = new ilPropertyFormGUI();
		
		$form->setFormAction($ilCtrl->getFormAction($this, 'renameRoom'));
		$form->setTitle($lng->txt("chat_chatroom_rename"));

		$inp = new ilTextInputGUI($lng->txt('chat_room_name'), 'room_name');
		$inp->setValue($room->getTitle());
		$form->addItem($inp);
		
		$hinp = new ilHiddenInputGUI('room_id');
		$hinp->setValue($_GET['room_id']);
		$form->addItem($hinp);
		
		$form->addCommandButton('renameRoom', $lng->txt('rename'));
		$form->addCommandButton('view', $lng->txt('cancel'));
	
		$tpl->setVariable('ADM_CONTENT',$form->getHTML());
	}
/*
	function deleteRoomObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		if(!$_GET["room_id_delete"])
		{
			$this->ilias->raiseError($this->lng->txt("chat_select_one_room"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->object->chat_room->setOwnerId($_SESSION["AccountId"]);
		$rooms = array($_GET["room_id_delete"]);
		
		if (!$rbacsystem->checkAccess("write", $this->ref_id))
		{
			$delResult = $this->object->chat_room->deleteRooms($rooms, $this->object->chat_room->getOwnerId());
		}
		else
		{
			$delResult = $this->object->chat_room->deleteRooms($rooms);
		}
		if(!$delResult)
		{
			$this->ilias->raiseError($this->object->chat_room->getErrorMessage(),$this->ilias->error_obj->MESSAGE);
		}
		unset($_GET["room_id_delete"]);
		ilUtil::sendSuccess($this->lng->txt("chat_rooms_deleted"), true);				
		$this->ctrl->redirect($this, 'view');
	}
*/	
	public function confirmedDeleteRoomObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		if(!$_POST['del_id'])
		{
			$this->ilias->raiseError($this->lng->txt("chat_select_one_room"),$this->ilias->error_obj->MESSAGE);
		}
		$this->object->chat_room->setOwnerId($_SESSION["AccountId"]);
		if(!$this->object->chat_room->deleteRooms($_POST['del_id']))
		{
			$this->ilias->raiseError($this->object->chat_room->getErrorMessage(),$this->ilias->error_obj->MESSAGE);
		}
		ilUtil::sendSuccess($this->lng->txt("chat_rooms_deleted"), true);

		$this->viewObject();
		return true;
	}

	public function addRoomObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		$room =& new ilChatRoom($this->object->getId());
		$room->setTitle(ilUtil::stripSlashes($_POST["room_name"]));
		$room->setOwnerId($_SESSION["AccountId"]);

		if(!$room->validate())
		{
			$this->ilias->raiseError($room->getErrorMessage(),$this->ilias->error_obj->MESSAGE);
		}
		$room->add();
		ilUtil::sendSuccess($this->lng->txt("chat_room_added"));
		$this->viewObject();

		return true;
	}

	public function renameRoomObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		$room = new ilChatRoom($this->object->getId());
		$room->setRoomId($_POST["room_id"]);
		$room->setTitle(ilUtil::stripSlashes($_POST["room_name"]));
		if(!$room->validate())
		{
			$this->ilias->raiseError($room->getErrorMessage(),$this->ilias->error_obj->MESSAGE);
		}
		$room->rename();
		ilUtil::sendSuccess($this->lng->txt("chat_room_renamed"));
		$this->viewObject();
	}		

	public function adminAddRoomObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.chat_edit_room.html","Modules/Chat");
		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_ROOM_NAME",$this->lng->txt("chat_room_name"));
		$this->tpl->setVariable("ROOM_CANCEL",$this->lng->txt("cancel"));
	
		$this->tpl->setVariable("TXT_EDIT_CHATROOMS",$this->lng->txt("chat_chatroom_rename"));
		$this->tpl->setVariable("ROOM_NAME","");
		$this->tpl->setVariable("CMD","addRoom");
		$this->tpl->setVariable("ROOM_EDIT",$this->lng->txt("add"));

	}	

	public function recordingsObject()
	{
		global $rbacsystem, $lng, $ilCtrl;
		
		if (!$rbacsystem->checkAccess("moderate", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->object->__initChatRecording();
		$data = $this->object->chat_recording->getRecordings();
		if (!is_array($data))
		{
			ilUtil::sendInfo($this->lng->txt('chat_no_recordings_available'));
			return true;
		}
		
		include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
		
		// prepare data
		$tablelines = array();
		foreach($data as $d)
		{
			$mod = '';
			if ($d['moderator_id'])
			{
				$usr = new ilObjUser($d['moderator_id']);
				$mod = $usr->getPublicName();
			}

			$current = array
			(
				'REC_ID' => $d['record_id'],
				'RECORDING_TITLE' => $d['title'],
				'RECORDING_DESCRIPTION' => $d['description'],
				'MODERATOR' => $mod,
				'START_TIME' => ilDatePresentation::formatDate(new ilDateTime($d['start_time'], IL_CAL_UNIX)),
				'start_timestamp' => $d['start_time']
			);
			
			if (is_array($moderator = $this->object->chat_recording->getModerator($d["moderator_id"])))
			{
				$this->tpl->setVariable("MODERATOR", $moderator);
			}
			
			$current_selection_list = new ilAdvancedSelectionListGUI();
			$current_selection_list->setListTitle($this->lng->txt("actions"));
			$current_selection_list->setId("act_".$current['REC_ID']);
			
			if ($d['end_time'] > 0)
			{
				$current['END_TIME'] = ilDatePresentation::formatDate(new ilDateTime($d['end_time'], IL_CAL_UNIX));
				$ilCtrl->setParameter($this,'record_id',$d["record_id"]);
				$current_selection_list->addItem($lng->txt('export'), '', $ilCtrl->getLinkTarget($this,'exportRecording'));
				$ilCtrl->clearParameters($this);
				
			}
			
			$current_selection_list->addItem($lng->txt('delete'), '', $ilCtrl->getLinkTarget($this,'askDeleteRecordings'));
			
			$current['COMMAND_SELECTION_LIST'] = $current_selection_list->getHTML();
			
			$tablelines[] = $current;
		}
		
		// prepare output
		include_once('Services/Table/classes/class.ilTable2GUI.php');
		$table = new ilTable2GUI($this, $lng->txt('chat_recordings'));
		$table->setTitle($lng->txt('chat_recordings'));
		$table->setData($tablelines);
		$table->setId('cht_recordings_tbl_' . $this->object->getId());
		$table->addColumn('', 'checkbox', '1%', true);
		$table->addColumn($lng->txt('title'), 'RECORDING_TITLE', '35%');
		$table->addColumn($lng->txt('chat_recording_moderator'), 'RECORDING_MODERATOR', '15%');
		$table->addColumn($lng->txt('chat_recording_time_frame'), 'start_timestamp', '30%');
		$table->addColumn($lng->txt('actions'), false, '10%');
		
		$table->setFormAction($ilCtrl->getFormAction($this));
		$table->setSelectAllCheckbox('recordings');
		
		$table->setRowTemplate('tpl.chat_recordings_list_row.html', 'Modules/Chat');
		
		$table->addMultiCommand('askDeleteRecordings', $lng->txt('delete'));
	
		$this->tpl->setVariable('ADM_CONTENT', $table->getHTML());
	}

	public function askDeleteRecordingsObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("moderate", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}

		if(!is_array($_POST['recordings']))
		{
			ilUtil::sendFailure($this->lng->txt('chat_recordings_select_one'));
			$this->recordingsObject();
			
			return false;
		}

		$this->object->__initChatRecording();

		ilUtil::sendQuestion($this->lng->txt('chat_recordings_delete_sure'));
		$this->tpl->addBlockFile("ADM_CONTENT","adm_content","tpl.chat_ask_delete_recordings.html","Modules/Chat");

		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TBL_TITLE",$this->lng->txt('chat_recordings'));
		$this->tpl->setVariable("HEADER_DESC",$this->lng->txt('chat_recording_description'));
		$this->tpl->setVariable("HEADER_MOD",$this->lng->txt('chat_recording_moderator'));
		$this->tpl->setVariable("HEADER_TIME",$this->lng->txt('chat_recording_time_frame'));
		$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath('arrow_downright.gif'));
		$this->tpl->setVariable("BTN_DELETE",$this->lng->txt('delete'));
		$this->tpl->setVariable("BTN_CANCEL",$this->lng->txt('cancel'));

		$counter = 0;
		for ($i = 0; $i < count($_POST["recordings"]); $i++)
		{
			$this->object->chat_recording->getRecord($_POST["recordings"][$i]);
			$this->tpl->setCurrentBlock("recordings_row");
			if($this->object->chat_recording->getTitle() != "")
			{
				$this->tpl->setVariable("RECORDING_TITLE", $this->object->chat_recording->getTitle());
			}
			if($this->object->chat_recording->getDescription() != "")
			{
				$this->tpl->setVariable("RECORDING_DESCRIPTION", $this->object->chat_recording->getDescription());
			}
			if ($moderator = $this->object->chat_recording->getModerator())
			{
				$this->tpl->setVariable("MODERATOR", $moderator);
			}
			$this->tpl->setVariable("START_TIME", date("Y-m-d H:i:s", $this->object->chat_recording->getStartTime()));
			if ($this->object->chat_recording->getEndTime() > 0)
			{
				$this->tpl->setVariable("END_TIME", date("Y-m-d H:i:s", $this->object->chat_recording->getEndTime()));
			}
			$this->tpl->setVariable("ROW_CLASS",ilUtil::switchColor(++$counter,'tblrow1','tblrow2'));
			$this->tpl->parseCurrentBlock();
		}
		$_SESSION['chat_recordings_del'] = $_POST['recordings'];
	}

	public function deleteRecordingsObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("moderate", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}

		if(!is_array($_SESSION['chat_recordings_del']))
		{
			ilUtil::sendFailure($this->lng->txt('chat_recordings_none_selected'));
			$this->recordingsObject();

			return false;
		}

		$this->object->__initChatRecording();

		foreach($_SESSION['chat_recordings_del'] as $record_id)
		{
			$this->object->chat_recording->delete($record_id);
		}
		ilUtil::sendSuccess($this->lng->txt('chat_recordings_deleted'));
		$this->recordingsObject();
		
		unset($_SESSION['chat_recordings_del']);
		return true;
	}

	public function exportRecordingObject()
	{
		global $rbacsystem;
		
		if (!$rbacsystem->checkAccess("moderate", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		$this->object->__initChatRecording();
		
		if (!$this->object->chat_recording->getRecord($_GET["record_id"]) ||
			$this->object->chat_recording->getEndTime() == 0)
		{
			ilUtil::sendFailure($this->lng->txt('chat_recording_not_found'));
			$this->recordingsObject();

			return false;
		}

		$tmp_tpl =& new ilTemplate("tpl.chat_export_recording.html",true,true,"Modules/Chat");

		if($this->object->chat_recording->getTitle())
		{
			$tmp_tpl->setVariable("TITLE",$this->object->chat_recording->getTitle());
		}
		$tmp_tpl->setVariable("START_TIME",date("Y-m-d H:i:s", $this->object->chat_recording->getStartTime()));
		$tmp_tpl->setVariable("END_TIME",date("Y-m-d H:i:s", $this->object->chat_recording->getEndTime()));
		$tmp_tpl->setVariable("CONTENT",$this->object->chat_recording->exportMessages());

		ilUtil::deliverData($tmp_tpl->get(), "chat_recording_" . $_GET["record_id"] . ".html");
		exit;
	}
/*
	function startRecordingObject()
	{
		global $rbacsystem,$ilUser;

		if (!$rbacsystem->checkAccess("moderate", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}

		$this->object->__initChatRecording();
		if($_GET["room_id"])
		{
			$this->object->chat_recording->setRoomId($_GET["room_id"]);
		}
		if (!$this->object->chat_recording->isRecording())
		{
			$this->object->chat_recording->setModeratorId($ilUser->getId());
			$this->object->chat_recording->startRecording($_POST["title"]);
		}
		ilUtil::sendInfo($this->lng->txt("chat_recording_started"),true);
		$this->showFramesObject();
	}
*/
/*
	function stopRecordingObject()
	{
		global $rbacsystem,$ilUser;

		if (!$rbacsystem->checkAccess("moderate", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}

		$this->object->__initChatRecording();
		if($_GET["room_id"])
		{
			$this->object->chat_recording->setRoomId($_GET["room_id"]);
		}
		if ($this->object->chat_recording->isRecording())
		{
			$this->object->chat_recording->stopRecording($ilUser->getId());
		}
		ilUtil::sendInfo($this->lng->txt("chat_recording_stopped"),true);
		$this->showFramesObject();
	}
*/
	/**
	* Show main chat window
	* @deprecated
	*/
	function showFramesObject()
	{
		global $rbacsystem, $ilCtrl;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->showUserFrameObject();
		$this->tpl->show(false, false);
		exit;
	}
	
	/**
	* Displays main chat page. JS-Inits (e.g. language, links, ...) are placed here.
	*/
	function showUserFrameObject()
	{
		global $ilCtrl, $ilSetting, $ilMainMenu, $ilLocator, $ilUser, $rbacsystem, $ilObjDataCache, $lng;
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';
		if (
			!$rbacsystem->checkAccess("read", $this->ref_id)
			|| ilChatBlockedUsers::_isBlocked($ilObjDataCache->lookupObjId($this->ref_id), $ilUser->getId())
			)
		{
			$baseClass = 'ilchatpresentationgui';
			$ilCtrl->setParameter($baseClass, 'ref_id', $this->ref_id);
			ilUtil::redirect($ilCtrl->getLinkTarget($this, 'view'));
			exit;
		}
		
		if ($_REQUEST["room_id"] && !$this->object->chat_room->getTitle())
		{
			$baseClass = 'ilchatpresentationgui';
			$ilCtrl->setParameter($baseClass, 'ref_id', $this->ref_id);
			ilUtil::sendFailure($lng->txt('chat_room_does_not_exist'), true);
			ilUtil::redirect($ilCtrl->getLinkTarget($this, 'view'));
			exit;			
		}
		$this->object->chat_room->setUserId($_SESSION["AccountId"]);
		$this->object->chat_room->updateLastVisit();
		$this->tpl = new ilTemplate("tpl.main.html", true, true);

		$this->__loadStylesheet(true);
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.chat_user_frame_async.html",'Modules/Chat');
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		
		$this->tpl->addCss("./Modules/Chat/templates/default/chat.css");
		
		$ilMainMenu->setSmallMode(false);
		$this->tpl->setVariable("MAINMENU", $ilMainMenu->getHTML());

		include_once 'Services/YUI/classes/class.ilYuiUtil.php';
		ilYuiUtil::initMenu();
		ilYuiUtil::initJson();
		
		$this->tpl->addJavascript("./Services/JavaScript/js/Basic.js");
		$this->tpl->addJavascript("./Services/Navigation/js/ServiceNavigation.js");
		$this->tpl->addJavascript('./Services/YUI/js/2_5_0/yahoo/yahoo-min.js');
		$this->tpl->addJavascript('./Services/YUI/js/2_5_0/event/event-min.js');
		$this->tpl->addJavascript('./Services/YUI/js/2_5_0/connection/connection-min.js');
		
		$this->tpl->addJavascript('./Modules/Chat/js/ChatRoomList.js');
		$this->tpl->addJavascript('./Modules/Chat/js/ChatActiveUsersRoom.js');
		$this->tpl->addJavascript('./Modules/Chat/js/ChatOnlineUsers.js');
		$this->tpl->addJavascript('./Modules/Chat/js/ChatLanguage.js');
		$this->tpl->addJavascript('./Modules/Chat/js/ChatUserList.js');
		$this->tpl->addJavascript('./Modules/Chat/js/ChatContextMenu.js');
		$this->tpl->addJavascript('./Modules/Chat/js/ChatMessages.js');
		$this->tpl->addJavascript('./Modules/Chat/js/ChatUserFrameAsync.js');
		
		//$this->tpl->addJavascript('Modules/Chat/js/debug.js');
		
		//$this->tpl->addJavascript('./Modules/Chat/js/json.js');
		$this->tpl->addJavascript('./Modules/Chat/js/ChatMenu.js');
		
		$ilLocator->addRepositoryItems($this->object->getRefId());
		$ilLocator->addItem($this->object->getTitle(), 'repository.php?ref_id='.$this->object->getRefId(), '_top', $this->object->getRefId());
		$this->tpl->setLocator();
		
		$this->tpl->setCurrentBlock("js_chat_init");
		$ilCtrl->setParameter($this, 'ref_id', '#__ref_id');	
		$link = $ilCtrl->getLinkTarget($this, "#__cmd", '', true);
		$this->tpl->setVariable("CHAT_BASE_URL_TEMPLATE", $link);
		$ilCtrl->clearParameters($this);
		
		$this->tpl->setVariable("BASE_REF_ID", $this->object->getRefId());
		$this->tpl->setVariable("BASE_ROOM_ID", $this->object->chat_room->getRoomId());
		$this->tpl->setVariable("ONLINE_USERS_TITLE", $this->lng->txt('chat_online_users'));
		$this->tpl->setVariable("ACTIVE_USERS_TITLE", $this->lng->txt('chat_active_users'));
		$this->tpl->setVariable("ROOM_LIST_TITLE", $this->lng->txt('chat_rooms'));
		$this->tpl->setVariable("CHATSERVER_ADDRESS",$this->object->server_comm->getServerFrameSource());		

		$this->tpl->setVariable("CHAT_HIDE", $this->lng->txt('hide'));
		$this->tpl->setVariable("CHAT_SHOW", $this->lng->txt('show'));
		$this->tpl->setVariable("CHAT_OPEN", $this->lng->txt('chat_open'));
		$this->tpl->setVariable("CHAT_RECORDING_RUNNING", $this->lng->txt('chat_recording_running'));
		$this->tpl->setVariable("CHAT_RECORDING_ALREADY_RUNNING", $this->lng->txt('chat_recording_already_running'));
		$this->tpl->setVariable("CHAT_RECORDING_STOPPED", $this->lng->txt('chat_recording_stopped'));

		$this->tpl->setVariable("CHAT_EMPTY_MESSAGE", $this->lng->txt('chat_empty'));

		$this->tpl->setVariable("CHAT_CONFIRM_USER_INVITE", $this->lng->txt('chat_confirm_user_invite'));
		$this->tpl->setVariable("CHAT_CONFIRM_KICK_USER", $this->lng->txt('chat_confirm_kick_user'));
		$this->tpl->setVariable("CHAT_NO_TITLE_GIVEN", $this->lng->txt('chat_no_title_given'));
		$this->tpl->setVariable("CHAT_ADDRESS", $this->lng->txt('chat_address'));
		$this->tpl->setVariable("CHAT_WHISPER", $this->lng->txt('chat_whisper'));
		$this->tpl->setVariable("CHAT_KICK", $this->lng->txt('chat_kick'));
		$this->tpl->setVariable("CHAT_UNKICK", $this->lng->txt('chat_unkick'));
		$this->tpl->setVariable("CHAT_INVITE", $this->lng->txt('chat_invite'));
		$this->tpl->setVariable("CHAT_DISINVITE", $this->lng->txt('chat_disinvite'));
		$this->tpl->setVariable("CHAT_PROFILE", $this->lng->txt('chat_profile'));
		$this->tpl->setVariable("CANCEL", $this->lng->txt('cancel'));
		
		$this->tpl->setVariable("ADD_TO_BOOKMARK", $this->lng->txt('chat_add_to_bookmark'));
		$this->tpl->setVariable("ADD_TO_ADDRESSBOOK", $this->lng->txt('chat_add_to_addressbook'));
		$this->tpl->setVariable("EMPTY_ROOM", $this->lng->txt('chat_empty'));
		$this->tpl->setVariable("DELETE", $this->lng->txt('delete'));
		$this->tpl->setVariable("CONFIRM_DELETE_PRIVATE_ROOM", $this->lng->txt('chat_confirm_delete_private_room'));
		$this->tpl->setVariable("CHAT_CONFIRM_USER_INVITE", $this->lng->txt('chat_confirm_user_invite'));
		$this->tpl->setVariable("INVITE", $this->lng->txt('chat_invite'));
		$this->tpl->setVariable("DISINVITE", $this->lng->txt('chat_disinvite'));
		$this->tpl->setVariable("CHAT_USER_HIDDEN", $this->lng->txt('chat_user_hidden'));
		$this->tpl->setVariable("CHAT_USER_VISIBLE", $this->lng->txt('chat_user_visible'));

		$this->tpl->setVariable("TXT_REFRESH", $this->lng->txt('refresh'));
		$this->tpl->setVariable("TXT_HIDE_TEXTFORMAT", $this->lng->txt('chat_hide_textformat'));
		$this->tpl->setVariable("TXT_SHOW_TEXTFORMAT", $this->lng->txt('chat_show_textformat'));
		$this->tpl->setVariable("TXT_TEXTFORMAT", $this->lng->txt('chat_textformat'));
		
		$this->tpl->parseCurrentBlock();		
		
		if($_REQUEST["room_id"])
		{
			$this->tpl->setVariable("TITLE",$this->object->chat_room->getTitle());
		}
		else
		{
			$this->tpl->setVariable("TITLE",$this->object->getTitle());
		}

		if($ilUser->getId() != ANONYMOUS_USER_ID)
		{
			$ilCtrl->setParameter($this, "room_id", $this->object->chat_room->getRoomId());
			$this->tpl->setVariable("ADD_FORMACTION", $ilCtrl->getFormAction($this, "addPrivateRoom"));
			$this->tpl->setVariable("TXT_ADD_PRIVATE_CHATROOM", $this->lng->txt("chat_add_private_chatroom"));
			$this->tpl->setVariable("TXT_ADD", $this->lng->txt("add"));
		}
		
		// smilies
		if ($ilSetting->get('chat_smilies_status') == 1)
		{
			include_once 'Modules/Chat/classes/class.ilChatSmilies.php';
			$smilies = ilChatSmilies::_getSmilies();
			foreach($smilies as $smiley)
			{
				$this->tpl->setCurrentBlock("smilies_element");
				$this->tpl->setVariable("SMILEY_SRC", $smiley['smiley_fullpath']);
				$parts = explode("\n", $smiley["smiley_keywords"]);
				$this->tpl->setVariable("SMILEY_CONTENT", $parts[0]);
				
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("smilies_selector");
			$this->tpl->setVariable('TXT_SHOW_SMILIES', $this->lng->txt('chat_show_smilies'));
			$this->tpl->setVariable('TXT_HIDE_SMILIES', $this->lng->txt('chat_hide_smilies'));
			$this->tpl->parseCurrentBlock();
		}

		$this->__showInputAreas();
		$this->tpl->fillJavaScriptFiles();
		$this->tpl->fillCssFiles();
		$this->tpl->fillContentStyle();
		$this->tpl->show(false, false);
		exit;		
	}
	
	public function getChatViewerBlockContentObject()
	{
		global $rbacsystem, $ilUser;
		
		$result = new stdClass();
		
		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
			$result->ok = false;
			$result->errormsg = $this->lng->txt("msg_no_perm_read");
			include_once 'Services/JSON/classes/class.ilJsonUtil.php';
			echo ilJsonUtil::encode($result);
			exit;
		}
		
		include 'Modules/Chat/classes/class.ilChatBlock.php';
		$block = new ilChatBlock();
		$last_known_id = $_REQUEST["chat_last_known_id"] ? $_REQUEST["chat_last_known_id"] : 0;
		
		$new_last_known_id = 0; 
		
		$msg = $block->getMessages
		(
			$this->object->chat_room->getObjId(),
			$this->object->chat_room->getRoomId(),
			$last_known_id,
			$new_last_known_id	// by ref
		);
		
		$ilUser->setPref
		(
			'chatviewer_last_selected_room',
			$this->object->chat_room->getObjId(). ',' . $this->object->chat_room->getRoomId()
		);
		$ilUser->writePrefs();
		$result->messages = $msg;
		$result->ok = true;
		
		$result->lastId = $new_last_known_id;

		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	private function __showInputAreas()
	{
		global $rbacsystem, $ilCtrl, $ilUser, $ilSetting;
		
		//$this->tpl = new ilTemplate("tpl.main.html", true, true);
		$this->__loadStylesheet();
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.chat_input_frame.html",'Modules/Chat');

		if($this->error)
		{
			ilUtil::sendFailure($this->error);
		}
		if($ilUser->getId() != ANONYMOUS_USER_ID)
		{		
			if($_GET["p_id"])
			{
				$user_obj =& new ilObjUser((int) $_GET["p_id"]);
				$message = $this->lng->txt("chat_private_message")." ";
				$message .= $this->object->chat_user->getLogin()." -> ".$user_obj->getLogin();
				ilUtil::sendInfo($message);
			}
			else if($_GET["a_id"])
			{
				$user_obj =& new ilObjUser((int) $_GET["a_id"]);
				$message = $this->lng->txt("chat_address_user")." ".$user_obj->getLogin();
				ilUtil::sendInfo($message);
			}
			
			$ilCtrl->setParameter($this, "room_id", $_REQUEST["room_id"]);
			if ($_GET["p_id"])
			{
				$ilCtrl->setParameter($this, "p_id", $_GET["p_id"]);
			}
			else if ($_GET["a_id"])
			{
				$ilCtrl->setParameter($this, "a_id", $_GET["a_id"]);
			}
			$this->tpl->setVariable("FORMACTION", $ilCtrl->getFormAction($this, "inputAsync"));
			$ilCtrl->clearParameters($this);
	
			$this->tpl->setVariable("TXT_NEW_MESSAGE",$this->lng->txt('chat_new_message'));
			$this->tpl->setVariable("TXT_COLOR",$this->lng->txt("chat_color"));
			$this->tpl->setVariable("TXT_TYPE",$this->lng->txt("chat_type"));
			$this->tpl->setVariable("TXT_FACE",$this->lng->txt("chat_face"));
			$this->tpl->setVariable("TXT_INPUT",$this->lng->txt("chat_input"));
	
			if ($_GET["p_id"])
			{
				$this->tpl->setCurrentBlock("cancel");
				$this->tpl->setVariable("TXT_SUBMIT_CANCEL",$this->lng->txt("cancel_whisper"));
				$this->tpl->parseCurrentBlock();
				$this->tpl->setVariable("TXT_SUBMIT_OK",$this->lng->txt("ok"));
			}
			elseif($_GET["a_id"])
			{
				$this->tpl->setCurrentBlock("cancel");
				$this->tpl->setVariable("TXT_SUBMIT_CANCEL",$this->lng->txt("cancel_talk"));
				$this->tpl->parseCurrentBlock();
				$this->tpl->setVariable("TXT_SUBMIT_OK",$this->lng->txt("ok"));
			}
			else
			{
				$this->tpl->setVariable("TXT_SUBMIT_OK",$this->lng->txt("ok"));
			}
			// export
			if ($ilSetting->get('chat_export_status') == 0 || ($ilSetting->get('chat_export_status') == 1 && $rbacsystem->checkAccess("moderate", $this->ref_id) ) )
			{
				$this->tpl->setVariable("TXT_HTML_EXPORT",$this->lng->txt('exp_html'));
			}
			
			//$this->tpl->setVariable("TXT_HTML_EXPORT",$this->lng->txt('exp_html'));
			
			$this->tpl->setVariable("SELECT_COLOR",$this->__getColorSelect());
			$this->tpl->setVariable("RADIO_TYPE",$this->__getFontType());
			$this->tpl->setVariable("CHECK_FACE",$this->__getFontFace());
	
			if ($rbacsystem->checkAccess("moderate", $this->object->getRefId()))
			{
				$this->object->__initChatRecording();
				$this->tpl->setCurrentBlock("moderator");
				$this->object->chat_recording->setRoomId($this->object->chat_room->getRoomId());
				//if ($this->object->chat_recording->isRecording())
				//{
					if ($this->object->chat_recording->getTitle() != "")
					{
						$this->tpl->setVariable("TXT_TITLE_STOP_RECORDING", $this->lng->txt("chat_recording_title"));
						$this->tpl->setVariable("VAL_TITLE_STOP_RECORDING", $this->object->chat_recording->getTitle());
					}
					if ($this->object->chat_recording->getDescription() != "")
					{
						$this->tpl->setVariable("TXT_DESCRIPTION_STOP_RECORDING", $this->lng->txt("chat_recording_description"));
						$this->tpl->setVariable("VAL_DESCRIPTION_STOP_RECORDING", $this->object->chat_recording->getDescription());
					}
					$this->tpl->setVariable("TXT_SUBMIT_STOP_RECORDING", $this->lng->txt("chat_stop_recording"));
				//}
				//else
				//{
					$this->tpl->setVariable("TXT_TITLE_START_RECORDING", $this->lng->txt("chat_recording_title"));
					$this->tpl->setVariable("TXT_DESCRIPTION_START_RECORDING", $this->lng->txt("chat_recording_description"));
					$this->tpl->setVariable("TXT_SUBMIT_START_RECORDING", $this->lng->txt("chat_start_recording"));
				//}
				
				$ilCtrl->setParameter($this, "room_id", $this->object->chat_room->getRoomId());
				$this->tpl->setVariable("MODERATOR_FORMACTION",
					$ilCtrl->getFormAction($this, "startRecording"));
				$this->tpl->setVariable("TXT_RECORDINGS",$this->lng->txt('chat_recordings'));
				$this->tpl->setVariable("MODERATOR_TARGET","_top");
				$this->tpl->parseCurrentBlock("moderator");
			}
		}
		// permanent link
		include_once 'Services/PermanentLink/classes/class.ilPermanentLinkGUI.php';
		$permalink = new ilPermanentLinkGUI('chat', $this->object->getRefId());
		$this->tpl->setVariable('PERMANENT_LINK', $permalink->getHTML(), '&room_id='.$this->object->chat_room->getRoomId());
		/*
		$this->tpl->setCurrentBlock('perma_link');
		$this->tpl->setVariable('PERMA_LINK', ILIAS_HTTP_PATH.'/goto.php?target='.$this->object->getType().'_'.$this->object->getRefId().'&client_id='.CLIENT_ID);
		$this->tpl->setVariable('TXT_PERMA_LINK', $this->lng->txt('chat_link_to_this_chat'));
		$this->tpl->setVariable('PERMA_TARGET', '_top');
		$this->tpl->parseCurrentBlock();
		*/
		//$this->tpl->show(false);
		//exit;
	}

	// Direct invitations from personal desktop
	public function invitePDObject()
	{
		global $ilUser;

		if(!$_GET['usr_id'])
		{
			ilUtil::sendFailure($this->lng->txt('chat_no_user_selected',true));
			$this->showFramesObject();
		}
		// Create room
		$this->object->chat_room->setOwnerId($ilUser->getId());
		$this->object->chat_room->setTitle(ilObjUser::_lookupLogin($ilUser->getId()). 
										   ' : '.
										   ilObjUser::_lookupLogin($_GET['usr_id']));

		// only add room if it doesn't exist
		if(!$id = $this->object->chat_room->lookupRoomId())
		{
			$id = $this->object->chat_room->add();
		}			

		// Send message
		$this->object->chat_room->setRoomId($id);
		$this->object->chat_room->invite((int) $_GET["usr_id"]);
		$this->object->sendMessage((int) $_GET['usr_id']);
		
		if((int)$this->object->chat_room->getRoomId())
		{
			ilUtil::sendSuccess(sprintf($this->lng->txt("chat_user_invited_private"), $this->object->chat_room->getTitle()),true);
		}
		else
		{
			ilUtil::sendSuccess(sprintf($this->lng->txt("chat_user_invited_public"), $this->object->getTitle()),true);
		}

		ilUtil::redirect('ilias.php?baseClass=ilChatPresentationGUI&ref_id='.$this->object->getRefId().'&room_id='.$id);
	}

	public function closeFrame()
	{
		$this->__loadStylesheet(true);
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.chat_close.html",'Modules/Chat');
		ilUtil::sendInfo("Your session is expired please login to ILIAS.");
		$this->tpl->touchBlock("content");
	}

	public function exportObject()
	{
		global $lng, $ilSetting, $rbacsystem;
		if ($ilSetting->get('chat_export_status') == 0 || ($ilSetting->get('chat_export_status') == 1 && $rbacsystem->checkAccess("moderate", $this->ref_id) ) )
		{
			$tmp_tpl =& new ilTemplate("tpl.chat_export.html",true,true,"Modules/Chat");
			$filename = $this->object->getTitle();
			if($this->object->chat_room->getRoomId())
			{
				$tmp_tpl->setVariable("CHAT_NAME",$this->object->chat_room->getTitle());
				$filename .= ' - ' . $this->object->chat_room->getTitle();
			}
			else
			{
				$tmp_tpl->setVariable("CHAT_NAME",$this->object->getTitle());
			}
			
			global $ilSetting;
			
			$export_period = $ilSetting->get('chat_export_period');
			$time_min = time() - $export_period * 60 * 60 * 24;
			$tmp_tpl->setVariable("CHAT_DATE",strftime("%c",time()));
			$tmp_tpl->setVariable("CONTENT",$this->object->chat_room->getAllMessages($time_min));
			ilUtil::deliverData($tmp_tpl->get(), $filename . ".html");
			exit;
		}
		else
		{
			ilUtil::sendFailure($lng->txt("msg_no_perm_read"));
		}
	}

	
	private function __showAdminAddRoomSelect()
	{
		$opt = array("createRoom" => $this->lng->txt("chat_room_select"));

		return ilUtil::formSelect("","action_b",$opt,false,true);
	}

	private function __showAdminRoomSelect()
	{
		global $rbacsystem;

		$opt["exportRoom"] = $this->lng->txt("chat_html_export");

		if($rbacsystem->checkAccess('write',$this->object->getRefId()) or
		   count($this->object->chat_room->getRoomsOfObject()))
		{
			$opt["refreshRoom"] = $this->lng->txt("chat_refresh");
		}
		
		if(count($this->object->chat_room->getRoomsOfObject()))
		{
			$opt["renameRoom"] = $this->lng->txt("rename");
			$opt["deleteRoom"] = $this->lng->txt("delete");
		}
		return ilUtil::formSelect(isset($_SESSION["room_id_delete"]) ? "deleteRoom" : "",
								  "action",
								  $opt,
								  false,
								  true);
	}


	private function __loadStylesheet($expires = false)
	{
		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		$this->tpl->setCurrentBlock("ChatStyle");
		$this->tpl->setVariable("LOCATION_CHAT_STYLESHEET", ilUtil::getStyleSheetLocation());
		if($expires)
		{
			//$this->tpl->setVariable("EXPIRES","<meta http-equiv=\"expires\" content=\"now\">".
			//						"<meta http-equiv=\"refresh\" content=\"30\">");
		}
		$this->tpl->parseCurrentBlock();
	}

	private function __getColorSelect()
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

	private function __getFontType()
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

	private function __getFontFace()
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

	private function __formatMessage()
	{
		$tpl = new ilTemplate("tpl.chat_message.html",true,true,'Modules/Chat');
		$_POST['message'] = htmlentities(trim($_POST['message']));
		$_POST['message'] = ilUtil::stripSlashes($_POST['message']);
		$_POST['message'] = preg_replace('/%u([0-9A-Fa-f]{4})/im', '&#x$1;', $_POST['message']);
		// make links clickable
		$_POST['message'] = ilUtil::makeClickable($_POST['message']);
		
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
		else if($_GET["a_id"])
		{
			$tpl->setCurrentBlock("address");
			$tpl->setVariable("ADDRESS_FROM_COLOR","navy");
			$user_obj =& new ilObjUser((int) $_SESSION["AccountId"]);
			$user_obj->read();
			$tpl->setVariable("ADDRESS_FROM",$user_obj->getLogin());

			$tpl->setVariable("ADDRESS_TO_COLOR","red");
			$user_obj =& new ilObjUser((int) $_GET["a_id"]);
			$user_obj->read();
			$tpl->setVariable("ADDRESS_TO",$user_obj->getLogin());
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
	
	public function exportRoomObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("read", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		
		if (isset($_GET['del_id']))
			$_POST['del_id'] = array($_GET['del_id']);

		if(!isset($_POST["del_id"]))
		{
			ilUtil::sendInfo($this->lng->txt("chat_select_one_room"));
			$this->viewObject();
			
			return false;
		}
		$this->__exportRooms();
	}
	
	private function __exportRooms()
	{
		include_once "Modules/Chat/classes/class.ilFileDataChat.php";

		if(count($_POST["del_id"]) == 1)
		{
			$this->object->chat_room->setRoomId($_POST["del_id"][0]);
			$this->exportObject();
		}

		$file_obj =& new ilFileDataChat($this->object);

		foreach($_POST["del_id"] as $id)
		{
			$this->object->chat_room->setRoomId((int) $id);

			$tmp_tpl =& new ilTemplate("tpl.chat_export.html",true,true,"Modules/Chat");
			
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

	/**
	* get tabs
	* @access	public
	* @param	object	tabs gui object
	*/
	public function getTabs(&$tabs_gui)
	{
		global $rbacsystem,$rbacreview, $ilAccess;

		$this->ctrl->setParameter($this,"ref_id",$this->object->getRefId());

//echo "-".$this->ctrl->getCmd()."-";

		if($rbacsystem->checkAccess('read',$this->object->getRefId()))
		{
			$force_active = ($_GET["cmd"] == "" || $_GET["cmd"] == "view")
				? true
				: false;
			$tabs_gui->addTarget("chat_rooms",
				$this->ctrl->getLinkTarget($this, "view"), array("view", ""), get_class($this),
				"", $force_active);
		}
		
		// info tab
		if ($ilAccess->checkAccess('visible', '', $this->ref_id))
		{
			$force_active = ($this->ctrl->getNextClass() == "ilinfoscreengui"
				|| strtolower($_GET["cmdClass"]) == "ilnotegui")
				? true
				: false;
	//echo "-$force_active-";
			$tabs_gui->addTarget("info_short",
				 $this->ctrl->getLinkTargetByClass(
				 array("ilobjchatgui", "ilinfoscreengui"), "showSummary"),
				 array("showSummary", "infoScreen"),
				 "", "", $force_active);
		}
		// settings tab
		if($rbacsystem->checkAccess('write',$this->object->getRefId()))
		{
			$force_active = ($_GET["cmd"] == "edit")
				? true
				: false;
			$tabs_gui->addTarget("edit_properties",
				$this->ctrl->getLinkTarget($this, "edit"), "edit", get_class($this),
				"", $force_active);
		}

		// blocked users
		if($rbacsystem->checkAccess('moderate',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("chat_blocked_users",
				$this->ctrl->getLinkTarget($this, "blockedUsers"),
				array("blockedUsers", "unBlockUsers", "blockUser"), get_class($this));
		}

		// recordings
		if($rbacsystem->checkAccess('moderate',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("chat_recordings",
								 $this->ctrl->getLinkTarget($this, "recordings"), "recordings", get_class($this));
		}

		// permissions
		if($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), array("perm","info","owner"), 'ilpermissiongui');
		}
	}

	private function __prepareOutput()
	{
		// output objects
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");

		// output locator
		$this->__setLocator();

		// output message
		if ($this->message)
		{
			ilUtil::sendInfo($this->message);
		}

		// display infopanel if something happened
		ilUtil::infoPanel();

		// set header
		$this->__setHeader();
	}

	private function __setHeader()
	{
		include_once './classes/class.ilTabsGUI.php';

		$this->tpl->setCurrentBlock("header_image");
		$this->tpl->setVariable("IMG_HEADER", ilUtil::getImagePath("icon_chat_b.gif"));
		$this->tpl->parseCurrentBlock();
		$this->tpl->setVariable("HEADER",$this->object->getTitle());
		$this->tpl->setVariable("H_DESCRIPTION",$this->object->getDescription());

		#$tabs_gui =& new ilTabsGUI();
		$this->getTabs($this->tabs_gui);

		// output tabs
		#$this->tpl->setVariable("TABS", $tabs_gui->getHTML());
	}

	private function __setLocator()
	{
		global $tree;
		global $ilias_locator;

		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html", "Services/Locator");

		$counter = 0;
		foreach ($tree->getPathFull($this->object->getRefId()) as $key => $row)
		{
			if($counter++)
			{
				$this->tpl->touchBlock('locator_separator_prefix');
			}

			$this->tpl->setCurrentBlock("locator_item");

			if($row["type"] == 'chat')
			{
				$this->tpl->setVariable("ITEM",$this->object->getTitle());
				$this->tpl->setVariable("LINK_ITEM",$this->ctrl->getLinkTarget($this));
			}
			elseif ($row["child"] != $tree->getRootId())
			{
				$this->tpl->setVariable("ITEM", $row["title"]);
				$this->tpl->setVariable("LINK_ITEM","repository.php?ref_id=".$row["child"]);
			}
			else
			{
				$this->tpl->setVariable("ITEM", $this->lng->txt("repository"));
				$this->tpl->setVariable("LINK_ITEM","repository.php?ref_id=".$row["child"]);
			}

			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setVariable("TXT_LOCATOR",$this->lng->txt("locator"));
		$this->tpl->parseCurrentBlock();
	}
	
	public function _goto($a_target)
	{
		global $ilAccess, $ilErr, $lng;

		if ($ilAccess->checkAccess("read", "", $a_target))
		{
			$_GET["ref_id"] = $a_target;
			$_GET["cmd"] = "view";
			$_GET["baseClass"] = "ilChatHandlerGUI";
			include("ilias.php");
			exit;
		}
		else if ($ilAccess->checkAccess("read", "", ROOT_FOLDER_ID))
		{
			$_GET["cmd"] = "frameset";
			$_GET["target"] = "";
			$_GET["ref_id"] = ROOT_FOLDER_ID;
			ilUtil::sendFailure(sprintf($lng->txt("msg_no_perm_read_item"),
				ilObject::_lookupTitle(ilObject::_lookupObjId($a_target))), true);
			include("repository.php");
			exit;
		}

		$ilErr->raiseError($lng->txt("msg_no_perm_read"), $ilErr->FATAL);
	}
	
	public function showUserProfileObject()
	{
		global $tpl, $ilCtrl;
		
		require_once './Services/User/classes/class.ilPublicUserProfileGUI.php';
		$profile_gui = new ilPublicUserProfileGUI((int)$_GET['user']);
		$tpl->setContent($ilCtrl->getHTML($profile_gui));
		$tpl->show();
		exit();
	}
	
	public function addLocatorItems()
	{
		global $ilLocator;
		
		if (is_object($this->object))
		{
			$ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTarget($this, ''), '', $_GET['ref_id']);
		}
	}
	
	/**
	* this one is called from the info button in the repository
	* not very nice to set cmdClass/Cmd manually, if everything
	* works through ilCtrl in the future this may be changed
	*/
	function infoScreenObject()
	{
		$this->ctrl->setCmd("showSummary");
		$this->ctrl->setCmdClass("ilinfoscreengui");
		$this->infoScreen();
	}

	/**
	* show information screen
	*/
	function infoScreen()
	{
		global $ilAccess;

		if (!$ilAccess->checkAccess("visible", "", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}

		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);

		$info->enablePrivateNotes();
		
		if ($ilAccess->checkAccess("read", "", $_GET["ref_id"]))
		{
			//$info->enableNews();
		}

		// no news editing for files, just notifications
//		$info->enableNewsEditing(false);
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
//			$news_set = new ilSetting("news");
//			$enable_internal_rss = $news_set->get("enable_rss_for_internal");
			
//			if ($enable_internal_rss)
//			{
//				$info->setBlockProperty("news", "settings", true);
//				$info->setBlockProperty("news", "public_notifications_option", true);
//			}
		}
		
		// standard meta data
		$info->addMetaDataSections($this->object->getId(),0, $this->object->getType());
		
		// forward the command
		$this->ctrl->forwardCommand($info);

	}

	
	private function __updateChatSessionAsync() {
		$this->object->chat_room->setUserId($_SESSION["AccountId"]);
		$this->object->chat_room->updateLastVisit();
	}
	
	private function fetchOnlineUsers($active_users = array()) {
		global $ilCtrl, $ilUser;
		$this->__updateChatSessionAsync();
		$all_users = $this->object->chat_room->getOnlineUsers();
		
		// filter blocked users 
		$filtered_users = array();
		
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';

		$activeMap = array();
		foreach($active_users as $user)
		{
			$activeMap[$user->id] = 1;
		}

		$hidden_count = 0;
		foreach($all_users as $user)
		{
			if($user['user_id'] == $_SESSION['AccountId'] ||  $user['user_id'] == ANONYMOUS_USER_ID || $activeMap[$user['user_id']]) {
				continue;
			}

			$oUser = new ilObjUser();				
			$oUser->setId($user['user_id']);
			$oUser->read();

			if($oUser->getPref('hide_own_online_status') == 'y') {
				$hidden_count++;
				continue;
			}
							
			$filtered_users[] = $oUser;
		}

		$out = array();
		
		foreach($filtered_users as $user) {
			$new_user = new stdClass();
			if($user->getId() != ANONYMOUS_USER_ID) {
				$new_user->anonymous = false;
				$new_user->id = $user->getId();
				$new_user->login = $user->getLogin();
				if (in_array($user->getPref("public_profile"), array("y", "g"))) {
					// public_profile
					$new_user->pp = '1';
					$new_user->img = $user->getPersonalPicturePath();
					// display_name
					$new_user->dn = $user->getFirstname().' '.$user->getLastname().' ('.$user->getLogin().')';
				}
				else {
					// public_profile
					$new_user->pp = '0';
					$new_user->img = ilUtil::getImagePath("no_photo_xsmall.jpg");
					// display_name
					$new_user->dn = $user->getLogin();
				}
				
				if(!ilChatBlockedUsers::_isBlocked($this->object->getId(),$user->getId()))
				{	
					/*
					 * invitation message
					 */
					$link = "";
					if($this->object->chat_room->isInvited($user->getId())) {
						// permission_disinvite
						$new_user->pmdi = 1;
						// permisssion_invite
						$new_user->pmi = 0;
					}
					else {
						// permission_disinvite
						$new_user->pmdi = 0;
						// permission_invite
						$new_user->pmi = 1;
					}
				}
			}
			else
			{
				$new_user->anonymous = true;
				$new_user->id = 0;
				// display_name
				$new_user->dn = $user->getLogin();
				// permission_disinvite
				$new_user->pmdi = 0;
				//permission_invite
				$new_user->pmi = 0;
			}
			$out[] = $new_user;		
		}
		//$out['hidden_count'] = $hidden_count;
		$result = new stdClass();
		$result->hidden_count = $hidden_count;
		$result->users = $out;
		return $result;
	}
	
	private function fetchActiveUsers() {
		global $rbacsystem, $ilCtrl, $ilUser;
		$this->__updateChatSessionAsync();
		$users = $this->object->chat_room->getActiveUsers();

		$user_obj = new ilObjUser();
			
		$out = array();
		
		foreach($users as $user) {
			if($user == $_SESSION["AccountId"]) {
				continue;
			}
				
			$new_user = new stdClass();
			$new_user->menu = array();
			$new_user->id = $user;
			$user_obj->setId($user);
			$user_obj->read();

			$new_user->login = $user_obj->getLogin();
			
			if (in_array($user_obj->getPref("public_profile"), array("y", "g"))) {
				//public_profile
				$new_user->pp = '1';
				$new_user->img = $user_obj->getPersonalPicturePath();
				//display_name
				$new_user->dn = $user_obj->getFirstname().' '.$user_obj->getLastname().' ('.$user_obj->getLogin().')';
			}
			else {
				//public_profile
				$new_user->pp = '0';
				$new_user->img = ilUtil::getImagePath("no_photo_xsmall.jpg");
				//display_name
				$new_user->dn = $user_obj->getLogin();
			}
			
			if($ilUser->getId() != ANONYMOUS_USER_ID && $user != ANONYMOUS_USER_ID)	{
				$ilCtrl->clearParameters($this);
				if(in_array($user_obj->getPref("public_profile"), array("y", "g")))	{

					$ilCtrl->setParameter($this, "user", $user_obj->getId());
					$link = $ilCtrl->getLinkTarget($this, "showUserProfile");
					$ilCtrl->clearParameters($this);
					$new_user->profile = $link;
				}				
				
			}
			if($rbacsystem->checkAccess('moderate',$this->object->getRefId()) and !$_REQUEST['room_id']) {
				$ilCtrl->setParameter($this, "kick_id", $user_obj->getId());

				if($this->object->chat_room->isKicked($user_obj->getId())) {
					//permission_kick
					$new_user->pmk = false;
					//permission_unkick
					$new_user->pmuk = true;
				}
				else {
					//permission_kick
					$new_user->pmk = true;
					//permission_unkick
					$new_user->pmuk = false;
				}
				$ilCtrl->clearParameters($this);
			}
			else
			{
				//permission_kick
				$new_user->pmk = false;	
			}
			$out[] = $new_user;
		}
		return $out;
	}
	
	public function fetchRooms() {
		global $rbacsystem, $ilUser, $ilCtrl;
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';

		$this->__updateChatSessionAsync();
		
		$public_rooms = $this->object->chat_room->getAllRooms();
		$private_rooms = $this->object->chat_room->getRooms();
		
		$this->object->__initChatRecording();

		$user_obj =& new ilObjUser();

		$current_room = false;		

		$rooms = array();
		
		foreach($public_rooms as $room) {
			if ($room['child'] == $this->object->getRefId()) {
				$current_room = $room;
				continue;
			}
			$rooms[] = $room;
			$rooms[count($rooms)-1]['users'] = $this->object->chat_room->getCountActiveUser($room['obj_id'],0);
		}
		
		$private_rooms_by_parent_id = array();
		
		foreach($private_rooms as $room) {

			if (!is_array($private_rooms_by_parent_id[$room['chat_id']]))
				$private_rooms_by_parent_id[$room['chat_id']] = array();
			
			$private_rooms_by_parent_id[$room['chat_id']][] = $room;
			$private_rooms_by_parent_id[$room['chat_id']][count($private_rooms_by_parent_id[$room['chat_id']])-1]['users'] = $this->object->chat_room->getCountActiveUser($room['chat_id'],$room['room_id']);
			
		}

		$titel = array();
		$users = array();
		
		foreach($rooms as $k => $v) {
			$titel[$k] = strtolower($v['title']);
			$users[$k] = $v['users'];
		}
		
		array_multisort($users, SORT_DESC, $titel, SORT_STRING, $rooms);
		
		foreach($private_rooms_by_parent_id as $k => $v) {
			$titel = array();
			$users = array();
			foreach($v as $k1 => $v1) {
			
				$titel[$k1] = strtolower($v1['title']);
				$users[$k1] = $v1['users'];
			}
			array_multisort($users, SORT_DESC, $titel, SORT_STRING, $private_rooms_by_parent_id[$k]);
		}
		
		/*
		 * show current user room
		 */
		
		$croom = $this->__prepareRoomForAsyncOutput($current_room);
		
		if ($this->object->chat_room->getRoomId() == 0) {
			$croom->act = true;
		}
		else {
			$croom->act = false;
		}
		
		
		if (is_array($private_rooms_by_parent_id[$current_room['obj_id']])) {
			$croom->subrooms = array();
			$ref_id = $current_room["ref_id"];
			foreach($private_rooms_by_parent_id[$current_room['obj_id']] as $priv_room) {
				$tmp = $this->__preparePrivateRoomForAsyncOutput($priv_room, $ref_id);
				if (isset($_REQUEST["room_id"]) && $_REQUEST["room_id"] == $priv_room["room_id"]) {
					$tmp->act = true;
				}
				else {
					$tmp->act = false;
				}
				$croom->subrooms[] = $tmp;
			}
		}
		
		$out_rooms = array();
		foreach($rooms as $room) {
			if (ilChatBlockedUsers::_isBlocked($room['obj_id'], $ilUser->getId())) {
				continue;
			}

			$new_room = $this->__prepareRoomForAsyncOutput($room);
			$new_room->subrooms = array();
			if (is_array($private_rooms_by_parent_id[$room['obj_id']])) {
				foreach($private_rooms_by_parent_id[$room['obj_id']] as $priv_room) {
					$new_room->subrooms[] = $this->__preparePrivateRoomForAsyncOutput($priv_room, $room["ref_id"]);
				}
			}
			$out_rooms[] = $new_room;		
		}
		
		$result = new stdClass();
		$result->currentRoom = $croom;
		$result->rooms = $out_rooms;
		return $result;
	}
	
	public function getOnlineUsersAsyncObject() {
		$out = $this->fetchOnlineUsers();
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($out);
		exit;
	}
	
	public function getActiveUsersAsyncObject()
	{
		$out = $this->fetchActiveUsers();
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($out);
		exit;
	}
	
	public function getCurrentRoomAsyncObject()
	{
		$result = $this->fetchRooms();
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	public function getUpdateAsyncObject() {

		global $ilCtrl, $rbacsystem, $ilObjDataCache, $lng, $ilUser;
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';
		if (
			!$rbacsystem->checkAccess("read", $_REQUEST["ref_id"])
			|| ilChatBlockedUsers::_isBlocked($ilObjDataCache->lookupObjId($_REQUEST["ref_id"]), $ilUser->getId())
			)
		{
			$res = new stdClass();
			
			$baseClass = 'ilchatpresentationgui';
			$res->forceRedirect = 'ilias.php?baseClass='.$baseClass.'&ref_id='.ilObjChat::_getPublicChatRefId();

			include_once 'Services/JSON/classes/class.ilJsonUtil.php';
			$json = ilJsonUtil::encode($res);
			echo $json;

			exit;
		}
		
		$res = new stdClass();
		
		$res->rooms = $this->fetchRooms();
		$res->activeUsers = $this->fetchActiveUsers();
		$res->onlineUsers = $this->fetchOnlineUsers($res->activeUsers);
		
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		$json = ilJsonUtil::encode($res);
		echo $json;
		exit;
	}
	
	public function enterRoomAsyncObject() {
		global $rbacsystem, $ilCtrl;
		$result = new stdClass();
		
		if (!$rbacsystem->checkAccess("read", $this->ref_id)) {
			$result->errormsg = $this->lng->txt("msg_no_perm_read");
		}
		else {
			$ilCtrl->setParameter($this, "room_id", $_REQUEST["room_id"]);
			if ($_GET["p_id"]) {
				$ilCtrl->setParameter($this, "p_id", $_GET["p_id"]);
			}
			else if ($_GET["a_id"])	{
				$ilCtrl->setParameter($this, "pa_id", $_GET["a_id"]);
				$ilCtrl->setParameter($this, "a_id", $_GET["a_id"]);
			}
			$result->serverTarget = $this->object->server_comm->getServerFrameSource();
			$result->ok = true;
		}
		
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	public function emptyRoomAsyncObject() {
		global	$rbacsystem;
		$result = new stdClass();
		if (
			$rbacsystem->checkAccess("moderate", $this->object->getRefId()) &&
			$this->object->chat_room->checkWriteAccess()
		) {

			if ($rid = (int)$_REQUEST['room_id'])
			{
				$this->object->chat_room->setRoomId($rid);
			}
		
			$this->object->server_comm->setType('delete');
			$message = $this->__formatMessage();
			$this->object->server_comm->setMessage($message);
			$this->object->server_comm->send();

			$this->object->chat_room->deleteAllMessages();
			
			$result->ok = true;
		}
		else {
			$result->ok = false;	
		}

		
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	public function kickUserAsyncObject()
	{
		global $rbacsystem;
		$result = new stdClass();
		if(!$rbacsystem->checkAccess('moderate',$this->object->getRefId()))	{
			//$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
			$result->errormsg = $this->lng->txt("msg_no_perm_write");
		}

		if($_REQUEST["kick_id"])
		{
			$tmp_user = new ilObjUser($_REQUEST['kick_id']);

			$this->object->server_comm->setKickedUser($tmp_user->getLogin());
			$this->object->server_comm->setType("kick");
			$this->object->server_comm->send();

			$this->object->chat_room->setKicked((int)$_REQUEST['kick_id']);

			$result->infomsg = $this->lng->txt("chat_user_dropped");
			$result->ok = true;
		}
		else {
			$result->ok = false;
		}
		
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
		
	}
	
	public function unkickUserAsyncObject()
	{
		global $rbacsystem;
		$result = new stdClass();
		
		if(!$rbacsystem->checkAccess('moderate',$this->object->getRefId()))
		{
			$result->errormsg = $this->lng->txt("msg_no_perm_write");
		}

		if($_REQUEST["kick_id"])
		{
			$this->object->chat_room->setUnkicked((int)$_REQUEST['kick_id']);
			$result->ok = true;
			$result->infomsg = $this->lng->txt("chat_user_unkicked");
		}
		else {
			$result->ok = false;
		}
		
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	public function addPrivateRoomAsyncObject()
	{
		global $rbacsystem;

		$result = new stdClass();
		
		if (!$rbacsystem->checkAccess("read", $this->ref_id)) {
			$result->errormsg = $this->lng->txt("msg_no_perm_read");
			$result->ok = false;
		}
		else
		{
			$room =& new ilChatRoom($this->object->getId());
			$room->setTitle(ilUtil::stripSlashes($_REQUEST["room_name"]));
			$room->setOwnerId($_SESSION["AccountId"]);
	
			if(!$room->validate())
			{
				$result->infomsg = $room->getErrorMessage();
				$result->ok = false;
			}
			else
			{
				$result->room_id = $room->add();
				$result->infomsg = $this->lng->txt("chat_room_added");
				$result->ref_id = $this->ref_id;
				$result->ok = true;
				
			}
		}
		
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	public function inviteAsyncObject()
	{
		$result = new stdClass();
		if($_GET["i_id"]) {
			$this->object->chat_room->invite((int) $_GET["i_id"]);
			$this->object->sendMessage((int) $_GET["i_id"]);
			if((int)$this->object->chat_room->getRoomId()) {
				$result->infomsg = sprintf($this->lng->txt("chat_user_invited_private"), $this->object->chat_room->getTitle());
			}
			else {
				$result->infomsg = sprintf($this->lng->txt("chat_user_invited_public"), $this->object->getTitle());
			}
			$result->ok = true;
		}
		else {
			$result->ok = false;
			$result->errormsg = "user id not found";
		}
		
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	public function dropAsyncObject()
	{
		$result = new stdClass();
		if($_GET["i_id"])
		{
			$this->object->chat_room->drop((int) $_GET["i_id"]);

			$tmp_user =& new ilObjUser($_GET["i_id"]);
			$this->object->server_comm->setKickedUser($tmp_user->getLogin());
			$this->object->server_comm->setType("kick");
			$this->object->server_comm->send();
			//ilUtil::sendInfo($this->lng->txt("chat_user_dropped_private"),true);
			$result->infomsg = $this->lng->txt("chat_user_dropped_private");
			//$this->showFramesObject();
			$result->ok = true;
		}
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	public function deleteRoomAsyncObject()
	{
		global $rbacsystem;

		$result = new stdClass();
		
		if (!$rbacsystem->checkAccess("read", $this->ref_id)) {
			$result->errormsg = $this->lng->txt("msg_no_perm_read");
		}
		
		if(!$_GET["room_id_delete"]) {
			$result->errormsg = $this->lng->txt("chat_select_one_room");
		}
		if (!$result->errormsg) {
			$this->object->chat_room->setOwnerId($_SESSION["AccountId"]);
			$rooms = array($_GET["room_id_delete"]);
			
			if (!$rbacsystem->checkAccess("write", $this->ref_id))	{
				$delResult = $this->object->chat_room->deleteRooms($rooms, $this->object->chat_room->getOwnerId());
			}
			else {
				$delResult = $this->object->chat_room->deleteRooms($rooms);
			}
	
			if(!$delResult)	{
				//$this->ilias->raiseError($this->object->chat_room->getErrorMessage(),$this->ilias->error_obj->MESSAGE);
				$result->errormsg = $this->object->chat_room->getErrorMessage();
			}
			
			$result->infomsg = $this->lng->txt("chat_rooms_deleted"); 
		}
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);		
		exit;
	}
	
	public function inputAsyncObject()
	{
		$result = new stdClass();
		$this->object->chat_room->setUserId($_SESSION["AccountId"]);
		$this->object->chat_room->updateLastVisit();

		if(!$_REQUEST["message"])
		{
			$result->errormsg = $this->lng->txt("chat_insert_message");
		}

		if($_REQUEST["message"] and $this->object->chat_room->checkWriteAccess())
		{
			$id = false;
			// FORMAT MESSAGE
			$message = $this->__formatMessage();
			
			// SET MESSAGE AND SEND IT
			$this->object->server_comm->setMessage($message);
			if((int) $_GET["p_id"])
			{
				$this->object->server_comm->setType('private');
			}
			else if((int) $_GET["a_id"])
			{
				$this->object->server_comm->setType('address');
			}
			
			if(!$this->object->server_comm->send($id))
			{
				$result->errormsg = $this->lng->txt("chat_no_connection");
			}
			else {
				$result->ok = true;
			}
			
			$_SESSION["il_notify_last_msg_checksum"] = $id;
		}
		else
		{
			$result->errormsg = $this->lng->txt('chat_kicked_from_room');
		}
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
		
		//$this->showInputFrameObject();
	}
	
	public function startRecordingAsyncObject()
	{
		global $rbacsystem,$ilUser;
		$result = new stdClass();

		if (!$rbacsystem->checkAccess("moderate", $this->object->getRefId()))
		{
			$result->errormsg = $this->lng->txt("msg_no_perm_read");
			$result->ok = false;
		}
		else {
			$this->object->__initChatRecording();
			if($_GET["room_id"])
			{
				$this->object->chat_recording->setRoomId($_GET["room_id"]);
			}

			if (!$this->object->chat_recording->isRecording())
			{
				$this->object->chat_recording->setModeratorId($ilUser->getId());
				$this->object->chat_recording->startRecording($_REQUEST["title"]);
				$result->ok = true;
				$result->infomsg = $this->lng->txt("chat_recording_started");
			}
			else {
				$result->ok = false;
				$result->errormsg = $this->lng->txt("chat_recording_already_running");
			}
		}
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	

	public function stopRecordingAsyncObject()
	{
		global $rbacsystem,$ilUser;
		$result = new stdClass();
		if (!$rbacsystem->checkAccess("moderate", $this->object->getRefId()))
		{
			$result->errormsg = $this->lng->txt("msg_no_perm_read");
			$result->ok = false;
		}
		else {
			$this->object->__initChatRecording();
			if($_GET["room_id"]) {
				$this->object->chat_recording->setRoomId($_GET["room_id"]);
			}
			if ($this->object->chat_recording->isRecording()) {
				$this->object->chat_recording->stopRecording($ilUser->getId());
			}
			$result->infomsg = $this->lng->txt("chat_recording_stopped");
			$result->ok = true;
		}
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	private function addRoomToBookmarkAsyncObject()
	{
		global $ilObjDataCache, $lng;
		$result = new stdClass();
		include_once 'Services/PersonalDesktop/classes/class.ilBookmark.php';

		$targetclass = 'ilchatpresentationgui';
		$ref_id = $_REQUEST["ref_id"];
		$room_id = $_REQUEST["room_id"];
		
		$result->obj_id = $this->object->getId();
		$result->ref_id = $ref_id;

		$bookmark = new ilBookmark();
		
		// for main chats
		if ($room_id == 0)
		{
			$obj_id = $ilObjDataCache->lookupObjId($ref_id);
			
			$bookmark->setTitle(vsprintf($lng->txt('chat_default_bookmark_title'), $ilObjDataCache->lookupTitle($obj_id)));
			$bookmark->setDescription(vsprintf($lng->txt('chat_default_bookmark_description'), $ilObjDataCache->lookupTitle($obj_id)));
			$bookmark->setParent(1);
			$bookmark->setTarget("ilias.php?baseClass=$targetclass&ref_id=$ref_id&room_id=0");
			$result->msg = vsprintf($lng->txt('chat_added_to_bookmarks'), $ilObjDataCache->lookupTitle($obj_id));
		}
		// for private rooms
		else
		{
			$obj_id = $ilObjDataCache->lookupObjId($ref_id);
			$chat_title = $ilObjDataCache->lookupTitle($obj_id);
			$room =& new ilChatRoom($ref_id);
			$room->setRoomId($room_id);
			$bookmark->setTitle(vsprintf($lng->txt('chat_default_bookmark_title'), $room->getTitle() . ' - ' . $chat_title ));
			$bookmark->setDescription(vsprintf($lng->txt('chat_default_bookmark_description'), $room->getTitle()));
			$bookmark->setParent(1);
			$bookmark->setTarget("ilias.php?baseClass=$targetclass&ref_id=$ref_id&room_id=$room_id");
			$result->msg = vsprintf($lng->txt('chat_added_to_bookmarks'), $chat_title . ' - ' . $room->getTitle());			
		}

		$bookmark->create();

		$result->ok = true;
//		$result->msg = "link has been created";
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo @ilJsonUtil::encode($result);
		exit;
	}
	
	public function addUserToAddressbookAsyncObject()
	{
		global $lng, $ilUser;
		$result = new stdClass();
		include_once 'Services/Contact/classes/class.ilAddressbook.php';
		$addressbook = new ilAddressbook($ilUser->getId());
		if ($addressbook->checkEntryByLogin($_REQUEST["ulogin"]))
		{
			$result->ok = false;
			$result->msg = $lng->txt('chat_user_already_in_addressbook');
		}
		else
		{
			$login = $_REQUEST["ulogin"];
			$id = ilObjUser::getUserIdByLogin($login);
			
			$oUser = new ilObjUser();				
			$oUser->setId($id);
			$oUser->read();

			$firstname = "";
			$lastname = "";

			if ($oUser->hasPublicProfile())
			{
				$firstname = $oUser->getFirstname();
				$lastname = $oUser->getLastname();
			}
			$result->msg = vsprintf($lng->txt('chat_added_to_addressbook'), $login);
			$email = ($oUser->getPref('public_email') == 'y' ? $oUser->getEmail() : '') ;
			$addressbook->addEntry($login, $firstname, $lastname, $email);
			$result->ok = true;
		}
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	private function __prepareRoomForAsyncOutput($room) {
		global $rbacsystem, $ilUser, $ilCtrl;
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';
		
		$new_room = new stdClass();
		$new_room->title = $room["title"];
		$new_room->users_online = $this->object->chat_room->getCountActiveUser($room["obj_id"],0);		
		$new_room->obj_id = $room["obj_id"];
		$new_room->room_id = 0;
		$new_room->ref_id = $room["child"];		
		
		//permission_enter
		$new_room->pme = true;
		//permission_bookmark
		$new_room->pmbo = true;
		
		$ilCtrl->setParameter($this, "ref_id", $room["child"]);
		$link = $ilCtrl->getLinkTarget($this, "showFrames");
		$this->tpl->setVariable("ROOM_LINK", $link);
		$ilCtrl->setParameter($this, "ref_id", $_GET["ref_id"]);
		
		$link = "il_chat_async_handler.enterRoom('".$room['child']."',false);";
		
		$this->object->chat_recording->setObjId($room["obj_id"]);
		
		if ($room["child"] == $this->object->getRefId() &&
			$this->object->chat_room->getRoomId() == 0 &&
			$rbacsystem->checkAccess("moderate", $this->object->getRefId()))
		{
			$link = "il_chat_async_handler.emptyRoom();";
			// permission_empty
			$new_room->pmem = true;
		}
		else
		{
			// permission_empty
			$new_room->pmem = false;
		}

		$this->object->chat_recording->setObjId($room["obj_id"]);
		$this->object->chat_recording->setRoomId(0);
		
		if ($this->object->chat_recording->isRecording()) {
			$new_room->recording = true;
		}
		else {
			$new_room->recording = false;
		}
		
		
		return $new_room;
	}
	
	private function __preparePrivateRoomForAsyncOutput($room, $ref_id) {
		global $rbacsystem, $ilUser, $ilCtrl;
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';
		$new_room = new stdClass();

		$new_room->title = $room["title"];
		$new_room->users_online = $this->object->chat_room->getCountActiveUser($room["chat_id"],$room["room_id"]);
		
		$new_room->obj_id = $room["chat_id"];
		$new_room->room_id = $room["room_id"];
		$new_room->ref_id = $ref_id;

		//permission_enter
		$new_room->pme = true;
		//permission_bookmark
		$new_room->pmbo = true;
		
		$ilCtrl->setParameter($this, "ref_id", $ref_id);
		$ilCtrl->setParameter($this, "room_id", $room["room_id"]);
		$link = $ilCtrl->getLinkTarget($this, "showFrames");
		
		$link = "il_chat_async_handler.enterRoom('$ref_id','".$room["room_id"]."');";
		
		if ($room["owner"] != $_SESSION["AccountId"] && !$rbacsystem->checkAccess('moderate', $this->object->getRefId())) {
			if($user_obj =& ilObjectFactory::getInstanceByObjId($priv_room['owner'],false))	{
				$new_room->chat_initiated = $user_obj->getLogin();
			}
			//permission_delete
			$new_room->pmde = false;
		}
		else {
			/*
			 * build context menu
			 */
			$ilCtrl->setParameter($this, "room_id", $this->object->chat_room->getRoomId());
			$ilCtrl->setParameter($this, "room_id_delete", $room["room_id"]);
			$link = $ilCtrl->getLinkTarget($this, "deleteRoom");
			$ilCtrl->clearParameters($this);
			//permission_delete
			$new_room->pmde = true;
			
			$link = "il_chat_async_handler.deletePrivateRoom('".$room['room_id']."');";
		}
				
		$this->object->chat_recording->setObjId($room["chat_id"]);
		$this->object->chat_recording->setRoomId($room["room_id"]);
				
		if ($this->object->chat_recording->isRecording()) {
			$new_room->recording = true;
		}
		else {
			$new_room->recording = false;
		} 
		
		$new_room->own_room_id = $room["room_id"];
		//permission_moderate
		$new_room->mod = $rbacsystem->checkAccess("moderate", $this->object->getRefId());
		
		if (
			$room["room_id"] == $this->object->chat_room->getRoomId() &&
			$rbacsystem->checkAccess("moderate", $this->object->getRefId())
		)
		{
			$link = "il_chat_async_handler.emptyRoom();";
			$ilCtrl->clearParameters($this);
			//permission_empty
			$new_room->pmem = true;
		}
		else
		{
			//permission_empty
			$new_room->pmem = false;	
		}
		return $new_room;
	}
}
// END class.ilObjChatGUI

?>
