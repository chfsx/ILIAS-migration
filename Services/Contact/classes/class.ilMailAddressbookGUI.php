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

require_once './Services/User/classes/class.ilObjUser.php';
require_once "Services/Mail/classes/class.ilMailbox.php";
require_once "Services/Mail/classes/class.ilFormatMail.php";
require_once "Services/Contact/classes/class.ilAddressbook.php";
require_once "Services/Contact/classes/class.ilAddressbookTableGUI.php";


/**
* @author Jens Conze
* @version $Id$
*
* @ingroup ServicesMail
* @ilCtrl_Calls ilMailAddressbookGUI: ilMailSearchCoursesGUI, ilMailSearchGroupsGUI, ilMailingListsGUI
* @ilCtrl_Calls ilMailAddressbookGUI: ilObjChat, ilObjChatGUI, ilMailFormGUI
*/
class ilMailAddressbookGUI
{
	private $tpl = null;
	private $ctrl = null;
	private $lng = null;
	private $tabs_gui = null;
	
	private $umail = null;
	private $abook = null;

	public function __construct()
	{
		global $tpl, $ilCtrl, $lng, $ilUser, $ilTabs;

		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tabs_gui =& $ilTabs;
		
		$this->ctrl->saveParameter($this, "mobj_id");

		$this->umail = new ilFormatMail($ilUser->getId());
		$this->abook = new ilAddressbook($ilUser->getId());
	}

	public function executeCommand()
	{
		global $ilUser;
		$this->showSubTabs();

		$forward_class = $this->ctrl->getNextClass($this);
		
		// delete all stored maildata
		$this->umail->savePostData($ilUser->getId(), array(), '', '', '', '', '', '', '', '');
		
		switch($forward_class)
		{
			case 'ilmailformgui':
				include_once 'Services/Mail/classes/class.ilMailFormGUI.php';
				$this->ctrl->forwardCommand(new ilMailFormGUI());
				break;

			case 'ilmailsearchcoursesgui':
				include_once 'Services/Contact/classes/class.ilMailSearchCoursesGUI.php';

				$this->tabs_gui->setSubTabActive('mail_my_courses');

				$this->ctrl->setReturn($this, "showAddressbook");
				$this->ctrl->forwardCommand(new ilMailSearchCoursesGUI());
				break;

			case 'ilmailsearchgroupsgui':
				include_once 'Services/Contact/classes/class.ilMailSearchGroupsGUI.php';

				$this->tabs_gui->setSubTabActive('mail_my_groups');

				$this->ctrl->setReturn($this, "showAddressbook");
				$this->ctrl->forwardCommand(new ilMailSearchGroupsGUI());
				break;
			
			case 'ilmailinglistsgui':
				include_once 'Services/Contact/classes/class.ilMailingListsGUI.php';

				$this->tabs_gui->setSubTabActive('mail_my_mailing_lists');

				$this->ctrl->setReturn($this, "showAddressbook");
				$this->ctrl->forwardCommand(new ilMailingListsGUI());
				break;

			default:
				$this->tabs_gui->setSubTabActive('mail_my_entries');

				if (!($cmd = $this->ctrl->getCmd()))
				{
					$cmd = "showAddressbook";
				}

				$this->$cmd();
				break;
		}
		return true;
	}

	/**
	 * Check user's input
	 */
	function checkInput($addr_id = 0)
	{
		// check if user login and e-mail-address are empty 
		if (!strcmp(trim($_POST["login"]), "") &&
			!strcmp(trim($_POST["email"]), ""))
		{
			ilUtil::sendInfo($this->lng->txt("mail_enter_login_or_email_addr"));
			$error = true;
		}
		else if ($_POST["login"] != "" && 
				 !(ilObjUser::_lookupId(ilUtil::stripSlashes($_POST["login"]))))
		{
			ilUtil::sendInfo($this->lng->txt("mail_enter_valid_login"));
			$error = true;
		}
		else if ($_POST["email"] &&
				 !(ilUtil::is_email($_POST["email"])))
		{
			ilUtil::sendInfo($this->lng->txt("mail_enter_valid_email_addr"));
			$error = true;
		}

		if (($this->existingEntry = $this->abook->checkEntryByLogin(ilUtil::stripSlashes($_POST["login"]))) > 0 &&
			(($this->existingEntry != $addr_id && $addr_id > 0) || !$addr_id))
		{
			ilUtil::sendInfo($this->lng->txt("mail_entry_exists"));
			$error = true;
		}

		return $error ? false : true; 
	}

	/**
	 * Save/edit entry
	 */
	public function saveEntry()
	{
		global $lng;
		
		if ($this->checkInput($_GET["addr_id"]))
		{
			if ($_GET["addr_id"])
			{
				$this->abook->updateEntry(ilUtil::stripSlashes($_GET["addr_id"]),
										ilUtil::stripSlashes($_POST["login"]),
										ilUtil::stripSlashes($_POST["firstname"]),
										ilUtil::stripSlashes($_POST["lastname"]),
										ilUtil::stripSlashes($_POST["email"]));
				ilUtil::sendInfo($lng->txt("mail_entry_changed"));
			}
			else
			{
				$this->abook->addEntry(ilUtil::stripSlashes($_POST["login"]),
									   ilUtil::stripSlashes($_POST["firstname"]),
						 			   ilUtil::stripSlashes($_POST["lastname"]),
									   ilUtil::stripSlashes($_POST["email"]));
				ilUtil::sendInfo($lng->txt("mail_entry_added"));
			}
			
			unset($_SESSION['addr_search']);
			
			$this->showAddressbook();
		}
		else
		{
			$this->showAddressForm();
		}
		
		return true;
	}
	
	/**
	 * Confirm delete entry
	 */
	function confirmDelete()
	{
		global $lng;
		
		$addr_ids = ((int)$_GET['addr_id']) ? array((int)$_GET['addr_id']) : $_POST['addr_id'];
		
		if (!$addr_ids)
	 	{
	 		ilUtil::sendInfo($this->lng->txt('mail_select_one_entry'));
	 		$this->showAddressbook();	 		
	 		return true;
	 	}
		
		include_once("Services/Utilities/classes/class.ilConfirmationGUI.php");
		$c_gui = new ilConfirmationGUI();
		
		// set confirm/cancel commands
		$c_gui->setFormAction($this->ctrl->getFormAction($this, "performDelete"));
		$c_gui->setHeaderText($this->lng->txt("mail_sure_delete_entry"));
		$c_gui->setCancel($this->lng->txt("cancel"), "showAddressbook");
		$c_gui->setConfirm($this->lng->txt("confirm"), "performDelete");

		// add items to delete
		foreach($addr_ids as $addr_id)
		{
			$entry = $this->abook->getEntry($addr_id);
			$c_gui->addItem("addr_id[]", $addr_id, $entry["login"] ? $entry["login"] : $entry["email"]);
		}
		
		$this->tpl->setVariable("HEADER", $this->lng->txt("mail"));
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.mail_addressbook.html", "Services/Contact");
		$this->tpl->setVariable('DELETE_CONFIRMATION', $c_gui->getHTML());
		
		$this->tpl->show();
		
		return true;
	}
	
	/**
	 * Delete entry
	 */
	function performDelete()
	{
		global $lng;
		
		if (is_array($_POST['addr_id']))
		{			
			if ($this->abook->deleteEntries($_POST['addr_id']))
			{
				ilUtil::sendInfo($lng->txt("mail_deleted_entry"));
			}
			else
			{
				ilUtil::sendInfo($lng->txt("mail_delete_error"));
			}
		}
		else
		{
			ilUtil::sendInfo($lng->txt("mail_delete_error"));
		}
		
		$this->showAddressbook();
	
		return true;	
	}

	/**
	 * Cancel action
	 */
	function cancel()
	{
		$this->showAddressbook();
	}
	
	public function showAddressForm()
	{
		global $rbacsystem, $lng, $ilUser;

		$this->tpl->setVariable("HEADER", $this->lng->txt("mail"));		
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.mail_addressbook_form.html", "Services/Contact");
		
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();		
		$form->setTitle($_GET['addr_id'] ? $lng->txt("mail_edit_entry") : $lng->txt("mail_new_entry"));	
		
		if ($_GET['addr_id'])
		{
			$this->ctrl->setParameter($this, 'addr_id', $_GET['addr_id']);
		}
		
		$entry = $this->abook->getEntry($_GET['addr_id']);
		$form->setFormAction($this->ctrl->getFormAction($this, "saveEntry"));
		
		$formItem = new ilTextInputGUI($this->lng->txt("username"), "login");
		$formItem->setValue(isset($_POST['login']) ? ilUtil::prepareFormOutput($_POST['login'], true) : ilUtil::prepareFormOutput($entry['login']));
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt("firstname"), "firstname");
		$formItem->setValue(isset($_POST['firstname']) ? ilUtil::prepareFormOutput($_POST['firstname'], true) : ilUtil::prepareFormOutput($entry['firstname']));
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt("lastname"), "lastname");
		$formItem->setValue(isset($_POST['lastname']) ? ilUtil::prepareFormOutput($_POST['lastname'], true) : ilUtil::prepareFormOutput($entry['lastname']));
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt("email"), "email");
		$formItem->setValue(isset($_POST['email']) ? ilUtil::prepareFormOutput($_POST['email'], true) : ilUtil::prepareFormOutput($entry['email']));
		$form->addItem($formItem);
		
		$form->addCommandButton('saveEntry',$this->lng->txt('save'));
		$form->addCommandButton('cancel',$this->lng->txt('cancel'));
		
		$this->tpl->setVariable('FORM', $form->getHTML());
		
		$this->tpl->show();

		return true;
	}
	
	public function mailToUsers()
	{
		global $ilUser;

		if ($_GET['addr_id'] && is_array($_GET['addr_id']))
			$addr_ids = $_GET['addr_id'];
		else if ((int)$_GET['addr_id'])
			$addr_ids = array((int)$_GET['addr_id']);
		else if ($_POST['addr_id'] && is_array($_POST['addr_id']))
			$addr_ids = $_POST['addr_id'];
		else if ((int)$_POST['addr_id'])
			$addr_ids = array((int)$_POST['addr_id']);

//		$addr_ids = ((int)$_GET['addr_id']) ? array((int)$_GET['addr_id']) : $_POST['addr_id'];
		
		if (!$addr_ids)
	 	{
	 		ilUtil::sendInfo($this->lng->txt('mail_select_one_entry'));
	 		$this->showAddressbook();	 		
	 		return true;
	 	}
	 	
	 	$mail_data = $this->umail->getSavedData();		
		if(!is_array($mail_data))
		{
			$this->umail->savePostData($ilUser->getId(), array(), '', '', '', '', '', '', '', '');
		}	
		
		$members = array();	
		foreach ($addr_ids as $addr_id)
		{
			$entry = $this->abook->getEntry($addr_id);
			
			if(strlen($entry['login']) && !$this->umail->doesRecipientStillExists($entry['login'], $mail_data['rcp_to'])) {
				$members[] = $entry['login'];
			} else if(strlen($entry['email']) && !$this->umail->doesRecipientStillExists($entry['email'], $mail_data['rcp_to'])) {
				$members[] = $entry['email'];
			}
		}
		
		if(count($members))
		{
			$mail_data = $this->umail->appendSearchResult($members, 'to');
			$this->umail->savePostData(
				$mail_data['user_id'],
				$mail_data['attachments'],
				$mail_data['rcp_to'],
				$mail_data['rcp_cc'],
				$mail_data['rcp_bcc'],
				$mail_data['m_type'],
				$mail_data['m_email'],
				$mail_data['m_subject'],
				$mail_data['m_message'],
				$mail_data['use_placeholders']
			);
		}

		ilUtil::redirect("ilias.php?baseClass=ilMailGUI&type=search_res");
	}
	
	public function search()
	{
		$_SESSION['addr_search'] = $_POST['search_qry'];
		
		$this->showAddressbook();
		
		return true;
	}
	
	/**
	 * Show user's addressbook
	 */
	public function showAddressbook()
	{
		global $rbacsystem, $lng, $ilUser, $ilCtrl, $ilias;

		$this->tpl->setVariable("HEADER", $this->lng->txt("mail"));		
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.mail_addressbook.html", "Services/Contact");		

		// check if current user may send mails
		include_once "Services/Mail/classes/class.ilMail.php";
		$mail = new ilMail($_SESSION["AccountId"]);
		$mailing_allowed = $rbacsystem->checkAccess('mail_visible',$mail->getMailObjectReferenceId());

		// searchbox
		include_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
		include_once 'Services/YUI/classes/class.ilYuiUtil.php';
		ilYuiUtil::initAutoComplete();
		$searchform = new ilPropertyFormGUI();
		$searchform->setFormAction($this->ctrl->getFormAction($this, "saveEntry"));
		
		//$dsSchema = array('response.results', 'login', 'firstname', 'lastname');
		$dsSchema = array("resultsList" => 'response.results',
			"fields" => array('login', 'firstname', 'lastname'));
		$dsFormatCallback = 'formatAutoCompleteResults';
		$dsDataLink = $ilCtrl->getLinkTarget($this, 'lookupAddressbookAsync');
		
		$inp = new ilTextInputGUI($this->lng->txt('search_for'), 'search_qry');
		$inp->setDataSource($dsDataLink);
		$inp->setDataSourceSchema($dsSchema);
		$inp->setDataSourceResultFormat($dsFormatCallback);
		
		$searchform->addItem($inp);
		$searchform->addCommandButton('search', $this->lng->txt("send"));
		$this->tpl->setVariable('SEARCHFORM', $searchform->getHtml());
		
		
		$this->tpl->setVariable('ACTION', $this->ctrl->getFormAction($this, "saveEntry"));
		$this->tpl->setVariable("TXT_SEARCH_FOR",$this->lng->txt("search_for"));
		$this->tpl->setVariable("BUTTON_SEARCH",$this->lng->txt("send"));
		
		if (strlen(trim($_SESSION["addr_search"])) > 0)
		{
			$this->tpl->setVariable("VALUE_SEARCH_FOR", ilUtil::prepareFormOutput(trim($_SESSION["addr_search"]), true));
		}
		
		$tbl = new ilAddressbookTableGUI($this);
		$tbl->setTitle($lng->txt("mail_addr_entries"));
		$tbl->setRowTemplate("tpl.mail_addressbook_row.html", "Services/Contact");				

	 	$tbl->setDefaultOrderField('login');	
		
		$result = array();
		$this->abook->setSearchQuery($_SESSION['addr_search']);
		$entries = $this->abook->getEntries();
		
		$tbl->addColumn('', 'check', '10%', true);
	 	$tbl->addColumn($this->lng->txt('login'), 'login', '20%');
	 	$tbl->addColumn($this->lng->txt('firstname'), 'firstname', '20%');
		$tbl->addColumn($this->lng->txt('lastname'), 'lastname', '20%');
		$tbl->addColumn($this->lng->txt('email'), 'email', '20%');
		$tbl->addColumn($this->lng->txt('actions'), '', '10%');
		
		include_once("./Services/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");

		if (count($entries))
		{		
			$tbl->enable('select_all');				
			$tbl->setSelectAllCheckbox('addr_id');
			
			// cache setting for iteration
			$chat_active = $ilias->getSetting("chat_active");
			
			$counter = 0;
			foreach ($entries as $entry)
			{				
				$result[$counter]['check'] = ilUtil::formCheckbox(0, 'addr_id[]', $entry["addr_id"]);
				
				$this->ctrl->setParameter($this, 'addr_id',  $entry['addr_id']);
				
				if ($entry["login"] != "")
				{
					if ($mailing_allowed)
					{
						$result[$counter]['login_linked_link'] = $this->ctrl->getLinkTarget($this, 'mailToUsers');
						$result[$counter]['login_linked_login'] = $entry["login"];
					}
					else
						$result[$counter]['login_unliked'] = $entry["login"];
				}				
				
				$result[$counter]['firstname'] = $entry["firstname"];
				$result[$counter]['lastname'] = $entry["lastname"];
								
				if ($_GET["baseClass"] == "ilMailGUI" && $rbacsystem->checkAccess("smtp_mail", $this->umail->getMailObjectReferenceId()))
				{
					$result[$counter]['email_linked_email'] = $entry["email"];
					$result[$counter]['email_linked_link'] = $this->ctrl->getLinkTarget($this, "mailToUsers");
				}
				else
					$result[$counter]['email_unlinked'] = $entry["email"] ? $entry["email"] : "&nbsp;";
		
				$current_selection_list = new ilAdvancedSelectionListGUI();
				$current_selection_list->setListTitle($this->lng->txt("actions"));
				$current_selection_list->setId("act_".$counter);

				$current_selection_list->addItem($this->lng->txt("edit"), '', $this->ctrl->getLinkTarget($this, "showAddressForm"));

				if ($mailing_allowed)
					$current_selection_list->addItem($this->lng->txt("send_mail_to"), '', $this->ctrl->getLinkTarget($this, "mailToUsers"));

				$current_selection_list->addItem($this->lng->txt("delete"), '', $this->ctrl->getLinkTarget($this, "confirmDelete"));
				
				if ($chat_active)
					$current_selection_list->addItem($this->lng->txt("invite_to_chat"), '', $this->ctrl->getLinkTarget($this, "inviteToChat"));
				
				$this->ctrl->clearParameters($this);
				
				$result[$counter]['COMMAND_SELECTION_LIST'] = $current_selection_list->getHTML();
				++$counter;
			}			
			
			if ($mailing_allowed)
				$tbl->addMultiCommand('mailToUsers', $this->lng->txt('send_mail_to'));

			$tbl->addMultiCommand('confirmDelete', $this->lng->txt('delete'));
			
			if ($chat_active)
				$tbl->addMultiCommand('inviteToChat', $this->lng->txt('invite_to_chat'));			
		}
		else
		{
			$tbl->disable('header');
			$tbl->disable('footer');

			$tbl->setNoEntriesText($this->lng->txt('mail_search_no'));			
		}

		$tbl->setData($result);
		
		$tbl->addCommandButton('showAddressForm', $this->lng->txt('add'));
		
		$this->tpl->setVariable('TABLE', $tbl->getHTML());		
		
		$this->tpl->show();
		
		unset($_SESSION['addr_search']);
		
		return true;
	}

	
	/**
	 * send chat invitations to selected Users
	 */
	public function inviteToChat()
	{
		global $ilUser, $ilObjDataCache, $lng, $ilCtrl, $tpl;

		$addr_ids = ((int)$_GET['addr_id']) ? array((int)$_GET['addr_id']) : $_POST['addr_id'];
		
		// check if users has been selected
		if (!$addr_ids)
		{
			ilUtil::sendInfo($lng->txt('chat_no_users_selected'), true);
			ilUtil::redirect($ilCtrl->getLinkTarget($this, 'showAddressbook'));
			exit;
		}

		// check for anonymous accounts
		
		// store userdata for users without ilias login
		$no_login = array();
		
		foreach($addr_ids as $id)
		{
			$entry = $this->abook->getEntry($id);
			
			// if login-name available, user has a local account
			if (!$entry['login'])
			{
				$no_login[] = $id;
			}
		}
		
		// error message for anonymous users
		if (count($no_login))
		{
			$message .= $lng->txt('chat_users_without_login') . ':<br>';
			$list = '';
			foreach($no_login as $e)
			{
				$list .= '<li>'.$this->abook->entryToString($e).'</li>';
			}
			$message .= '<ul>';
			$message .= $list;
			$message .= '</ul>';
			ilUtil::sendInfo($message);
			$this->showAddressbook();
			exit;
		}
		
		// include chat classes
		include_once 'Modules/Chat/classes/class.ilChatRoom.php';
		include_once 'Modules/Chat/classes/class.ilObjChat.php';
		include_once 'Modules/Chat/classes/class.ilObjChatGUI.php';
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';
		
		// fetch rooms
		$chat_rooms = ilChatRoom::getAllRooms();
		$rooms = array();
		foreach($chat_rooms as $room)
		{
			$rooms[] = $room;
			$rooms[count($rooms)-1]["subrooms"] = ilChatRoom::getRoomsOfObject($ilObjDataCache->lookupObjId($room["ref_id"]), $ilUser->getId());
		}

		// sort rooms by title
		$titel = array();
		foreach($rooms as $k => $v) {
			$titel[$k] = strtolower($v['title']);
		}
		array_multisort($titel, SORT_STRING, $rooms);
		
		// buid room select form
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");

		$form = new ilPropertyFormGUI();		
		$form->setTitle($lng->txt("mail_invite_users_to_chat"));
		$psel = new ilSelectInputGUI($lng->txt("chat_select_room"), 'room_id');
		$options = array();
		foreach($rooms as $room)
		{
			$ref_id = $room['ref_id'];
			if (ilChatBlockedUsers::_isBlocked($ilObjDataCache->lookupObjId($ref_id), $ilUser->getId()))
				continue;
			
			$options[$ref_id] = $room['title'];
			foreach($room['subrooms'] as $subroom)
			{
				if (ilChatRoom::_checkWriteAccess($ref_id, $subroom['room_id'], $ilUser->getId()))
					$options[$ref_id.','.$subroom['room_id']] = '+&nbsp;'.$subroom['title'];
			}
		}
		$psel->setOptions($options);
		$form->addItem($psel);
		$phidden = new ilHiddenInputGUI('addr_ids');
		$phidden->setValue(join(',', $addr_ids));
		$form->addItem($phidden);
		$form->addCommandButton('submitInvitation',$this->lng->txt('submit'));
		$form->addCommandButton('cancel',$this->lng->txt('cancel'));
		$form->setFormAction($ilCtrl->getFormAction($this));
		
		// finish... show form
		$tpl->setTitle($lng->txt('mail_invite_users_to_chat'));
		$tpl->setContent($form->getHtml());
		$tpl->show();
	}
	
	/**
	 * last step of chat invitations
	 * check access for every selected user and do invitation
	 */
	public function submitInvitation()
	{
		global $ilObjDataCache, $ilUser,$ilCtrl, $rbacsystem, $lng, $ilias;
		
		if (!$_POST["addr_ids"])
		{
			ilUtil::sendInfo($lng->txt('chat_no_users_selected'), true);
			$this->showAddressbook();
			exit;
		}

		if (!$_POST["room_id"])
		{
			ilUtil::sendInfo($lng->txt('chat_no_room_selected'), true);
			$_POST['addr_id'] = explode(',', $_POST["addr_ids"]);
			$this->showAddressbook();
			exit;
		}
		
		// get selected users (comma seperated user id list)
		$ids = explode(',', $_POST["addr_ids"]);
		
		// get selected chat room from POST-String
		// format: "ref_id , room_id"
		$chat_ids = explode(',', $_POST['room_id']);
		$chat_id = (int)$chat_ids[0];
		// room_id is optional with default value 0
		$room_id = 0;
		if (count($chat_ids) > 0)
		{
			$room_id = (int)$chat_ids[1];
		}
		
		// ready to check for room access
		
		// incldue chat room classes
		include_once 'Modules/Chat/classes/class.ilChatRoom.php';
		include_once 'Modules/Chat/classes/class.ilObjChat.php';
		include_once 'Modules/Chat/classes/class.ilObjChatGUI.php';
		include_once 'Modules/Chat/classes/class.ilChatBlockedUsers.php';
		
		$obj_id = $ilObjDataCache->lookupObjId($chat_id);
		
		// initiate target room
		$room = new ilChatRoom($chat_id);
		$room->setRoomId((int)$room_id);
		
		// store userdata for users with no access
		$no_access = array();
		
		// store userdata for users without ilias login
		$no_login = array();
		
		// store usersids with access
		$valid_users = array();

		foreach($ids as $id)
		{
			$entry = $this->abook->getEntry($id);
			
			// if login-name available, user has a local account
			if ($entry['login'])
			{
				$user_id = $ilUser->getUserIdByLogin($entry['login']);
				if ( 
					!$rbacsystem->checkAccessOfUser($user_id, 'read', $chat_id)
					|| ilChatBlockedUsers::_isBlocked($obj_id, $user_id)
				)
				{
					$no_access[] = $id;
				}
				else
				{
					$valid_users[] = $user_id;
				}
			}
			// if no login could be found, user has no access
			// so anonymous users cant be invitated
			else
			{
				$no_login[] = $id;
			}
		}
		
		if (count($no_access) || count($no_login))
		{
			$message = "";
			// error message for users without access permissions
			if (count($no_access))
			{
				$message .= $lng->txt('chat_users_without_permission') . ':<br>';
				$list = '';
				foreach($no_access as $e)
				{
					$list .= '<li>'.$this->abook->entryToString($e).'</li>';
				}
				$message .= '<ul>';
				$message .= $list;
				$message .= '</ul>';
			}
			
			// error message for anonymous users
			if (count($no_login))
			{
				$message .= $lng->txt('chat_users_without_login') . ':<br>';
				$list = '';
				foreach($no_login as $e)
				{
					$list .= '<li>'.$this->abook->entryToString($e).'</li>';
				}
				$message .= '<ul>';
				$message .= $list;
				$message .= '</ul>';
			}
			ilUtil::sendInfo($message);
			$_POST["addr_id"] = $ids;
			$this->inviteToChat();
			exit;
		}
		
		// load chat handler for room
		$chatObject = new ilObjChat($ref_id);
		foreach($valid_users as $id)
		{
			$room->invite($id);
			$chatObject->sendMessageForRoom($id, $room);
		}
		$link = '<p><a target="chatframe" href="ilias.php?baseClass=ilChatPresentationGUI&ref_id='.$chat_id.'&room_id='.$room_id.'">'.$lng->txt('goto_invitation_chat').'</a></p>';		
		ilUtil::sendInfo($lng->txt('chat_users_have_been_invited') . $userlist .$link, true);
		$link = $ilCtrl->getLinkTarget($this, 'showAddressbook');
		ilUtil::redirect($link);
	}
	
	public function lookupAddressbookAsync()
	{
		include_once 'Services/JSON/classes/class.ilJsonUtil.php';
		include_once 'Services/Contact/classes/class.ilMailAddressbook.php';
		
		$search = "%" . $_REQUEST["query"] . "%";
		$result = new stdClass();
		$result->response = new stdClass();
		$result->response->results = array();
		if (!$search)
		{
			$result->response->total = 0;
			echo ilJsonUtil::encode($result);
			exit;
		}

		$mailAdrBookObj = new ilMailAddressbook;
		$result = $mailAdrBookObj->getAddressbookAsync($search);
		
		echo ilJsonUtil::encode($result);
		exit;
	}
	
	function showSubTabs()
	{
		$this->tabs_gui->addSubTabTarget('mail_my_entries', $this->ctrl->getLinkTarget($this));
		$this->tabs_gui->addSubTabTarget('mail_my_mailing_lists', $this->ctrl->getLinkTargetByClass('ilmailinglistsgui'));
		$this->tabs_gui->addSubTabTarget('mail_my_courses', $this->ctrl->getLinkTargetByClass('ilmailsearchcoursesgui'));
		$this->tabs_gui->addSubTabTarget('mail_my_groups', $this->ctrl->getLinkTargetByClass('ilmailsearchgroupsgui'));		
	}
}
?>
