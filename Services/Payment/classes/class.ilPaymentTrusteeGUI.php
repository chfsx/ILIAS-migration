<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilPaymentTrusteeGUI
*
* @author Stefan Meyer
* @version $Id: class.ilPaymentTrusteeGUI.php 20288 2009-06-22 08:15:29Z mjansen $
*
* @package core
*/
include_once './Services/Payment/classes/class.ilPaymentTrustees.php';
include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
include_once './Services/Utilities/classes/class.ilConfirmationGUI.php';

class ilPaymentTrusteeGUI extends ilShopBaseGUI
{
	var $trustee_obj = null;
	var $user_obj;
	var $ctrl;

	public function ilPaymentTrusteeGUI($user_obj)
	{
		parent::__construct();

		$this->user_obj = $user_obj;
		$this->trustee_obj = new ilPaymentTrustees($this->user_obj);
		$this->lng->loadLanguageModule('crs');
		
		$this->ctrl->saveParameter($this, 'baseClass');
	}
	
	protected function prepareOutput()
	{
		global $ilTabs;
		
		$this->setSection(6);
		
		parent::prepareOutput();

		$ilTabs->setTabActive('paya_header');
		$ilTabs->setSubTabActive('paya_trustees');
	}
	
	public function executeCommand()
	{
		global $tree;

		$cmd = $this->ctrl->getCmd();
		switch ($this->ctrl->getNextClass($this))
		{
			default:
				if(!$cmd = $this->ctrl->getCmd())
				{
					$cmd = 'showTrustees';
				}
				$this->prepareOutput();
				$this->$cmd();
				break;
		}
	}

	function cancelDelete()
	{
		unset($_SESSION['paya_delete_trustee']);
		$this->showTrustees();

		return true;
	}


	function showTrustees($a_show_delete = false)
	{		
		$_SESSION['paya_delete_trustee'] = $_SESSION['paya_delete_trustee'] ? $_SESSION['paya_delete_trustee'] : array();

		$actions = array(0	=> $this->lng->txt("paya_disabled"),
						 1 	=> $this->lng->txt("paya_enabled"));


		$this->showButton('searchUser',$this->lng->txt('search_user'));

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');
						 
		if($a_show_delete)
		{
			$oConfirmationGUI = new ilConfirmationGUI();
			
			// set confirm/cancel commands
			$oConfirmationGUI->setFormAction($this->ctrl->getFormAction($this,"performDeleteTrustee"));
			$oConfirmationGUI->setHeaderText($this->lng->txt("paya_sure_delete_selected_trustees"));
			$oConfirmationGUI->setCancel($this->lng->txt("cancel"), "cancelDelete");
			$oConfirmationGUI->setConfirm($this->lng->txt("confirm"), "performDeleteTrustee");			
	
			foreach($this->trustee_obj->getTrustees() as $trustee)
			{
				$delete_row = '';
				if(in_array($trustee['trustee_id'],$_POST['trustee']))
				{
					if($tmp_obj = ilObjectFactory::getInstanceByObjId($trustee['trustee_id'],false))
					{
						$delete_row	= $tmp_obj->getLogin().' -> '.$tmp_obj->getFirstname().' '.$tmp_obj->getLastname();
					}	
				}
	
				$oConfirmationGUI->addItem('',$delete_row, $delete_row);
			}
			
			$this->tpl->setVariable("CONFIRMATION",$oConfirmationGUI->getHTML());	
			
		}

		if(!count($this->trustee_obj->getTrustees()))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_trustees'));
			
			return true;
		}
		
		$counter = 0;
		$f_result = array();
		
		$img_mail = "<img src=\"".ilUtil::getImagePath("icon_pencil_b.gif")."\" alt=\"".
			$this->lng->txt("crs_mem_send_mail").
			"\" title=\"".$this->lng->txt("crs_mem_send_mail")."\" border=\"0\" vspace=\"0\"/>";
		
		
		foreach($this->trustee_obj->getTrustees() as $trustee)
		{
			// GET USER OBJ
			if($tmp_obj = ilObjectFactory::getInstanceByObjId($trustee['trustee_id'],false))
			{
				$f_result[$counter][]	= ilUtil::formCheckbox(in_array($trustee['trustee_id'],$_SESSION['paya_delete_trustee']) ? 1 : 0,
															   "trustee[]",
															   $trustee['trustee_id']);
				$f_result[$counter][]	= $tmp_obj->getLogin();
				$f_result[$counter][]	= $tmp_obj->getFirstname();
				$f_result[$counter][]	= $tmp_obj->getLastname();

				$f_result[$counter][]	= ilUtil::formSelect((int) $trustee['perm_stat'],
															 'perm_stat['.$trustee['trustee_id'].']',
															 $actions,
															 false,
															 true);
				
				$f_result[$counter][]	= ilUtil::formSelect((int) $trustee['perm_obj'],
															 'perm_obj['.$trustee['trustee_id'].']',
															 $actions,
															 false,
															 true);
															 
				$f_result[$counter][]	= ilUtil::formSelect((int) $trustee['perm_coupons'],
															 'perm_coupons['.$trustee['trustee_id'].']',
															 $actions,
															 false,
															 true);

#				$link_mail = "<a target=\"_blank\" href=\"./ilias.php?baseClass=ilMailGUI&type=new&rcp_to=".
#					$tmp_obj->getLogin()."\"".$img_mail."</a>";
				$link_mail = "<div class=\"il_ContainerItemCommands\"><a class=\"il_ContainerItemCommand\" href=\"./ilias.php?baseClass=ilMailGUI&type=new&rcp_to=".
					$tmp_obj->getLogin()."\">".$this->lng->txt("mail")."</a></div>";
				
				$f_result[$counter][]	= $link_mail;

				unset($tmp_obj);
				++$counter;
			}

		}
		return $this->__showTrusteesTable($f_result);
	}
	function deleteTrustee()
	{
		if(!is_array($_POST['trustee']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_users_selected'));
			$this->showTrustees();

			return true;
		}
		$_SESSION['paya_delete_trustee'] = $_POST['trustee'];
		$this->showTrustees(true);
		
		return true;
	}

	function performDeleteTrustee()
	{
		if(is_array($_SESSION['paya_delete_trustee']))
		{
			foreach($_SESSION['paya_delete_trustee'] as $id)
			{
				$this->trustee_obj->setTrusteeId($id);
				$this->trustee_obj->delete();
			}
		}
		unset($_SESSION['paya_delete_trustee']);
		ilUtil::sendInfo($this->lng->txt('paya_delete_trustee_msg'));
		$this->showTrustees();

		return true;
	}

	function update()
	{
		foreach($this->trustee_obj->getTrustees() as $trustee)
		{
			$this->trustee_obj->setTrusteeId($trustee['trustee_id']);
			$this->trustee_obj->toggleStatisticPermission($_POST['perm_stat']["$trustee[trustee_id]"]);
			$this->trustee_obj->toggleObjectPermission($_POST['perm_obj']["$trustee[trustee_id]"]);
			$this->trustee_obj->toggleCouponsPermission($_POST['perm_coupons']["$trustee[trustee_id]"]);			
			$this->trustee_obj->modify();
		}
		ilUtil::sendInfo($this->lng->txt('paya_updated_trustees'));
		$this->showTrustees();

		return true;
	}

	function searchUser()
	{
		$this->tpl->addBlockFile("ADM_CONTENT","adm_content","tpl.main_view.html",'Services/Payment');
		$this->showButton('showTrustees',$this->lng->txt('back'));

		$this->lng->loadLanguageModule('search');
		
		$oForm = new ilPropertyFormGUI();
		$oForm->setFormAction($this->ctrl->getFormAction($this, 'performSearch'));
		$oForm->setTitle($this->lng->txt('crs_search_members'));
		
		$oSearch = new ilTextInputGUI($this->lng->txt("search_search_term"),'search_str');
		$oSearch->setValue($_SESSION["pays_search_str_trustee"] ? $_SESSION["pays_search_str_trustee"] : "");
		$oForm->addItem($oSearch);
		
		$oForm->addCommandButton('performSearch', $this->lng->txt('search'));
		$oForm->addCommandButton('showTrustees', $this->lng->txt('cancel'));
		
		$this->tpl->setVariable('FORM', $oForm->getHTML());

		return true;
		
	}

	function newSearch()
	{
		$_SESSION["paya_search_str"] = $_POST["search_str"];
		$this->performSearch();
	}

	function performSearch()
	{
		// SAVE it to allow sort in tables
		$_SESSION["pays_search_str_trustee"] = $_POST["search_str"] = $_POST["search_str"] ? $_POST["search_str"] : $_SESSION["pays_search_str_trustee"];


		if(!$_POST["search_str"])
		{
			ilUtil::sendInfo($this->lng->txt("crs_search_enter_search_string"));
#			$this->searchUser();
			$this->showTrustees();

			return false;
		}
		if(!count($result = $this->__search(ilUtil::stripSlashes($_POST["search_str"]))))
		{
			ilUtil::sendInfo($this->lng->txt("crs_no_results_found"));
#			$this->searchUser();
			$this->showTrustees();

			return false;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.main_view.html",'Services/Payment');
		$this->showButton("searchUser",$this->lng->txt("back"));
		
		$counter = 0;
		$f_result = array();
		foreach($result as $user)
		{
			if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($user["id"],false))
			{
				continue;
			}
			$f_result[$counter][] = ilUtil::formCheckbox(0,"user[]",$user["id"]);
			$f_result[$counter][] = $tmp_obj->getLogin();
			$f_result[$counter][] = $tmp_obj->getFirstname();
			$f_result[$counter][] = $tmp_obj->getLastname();
			
			unset($tmp_obj);
			++$counter;
		}
		$this->__showSearchUserTable($f_result);
	}

	function addTrustee()
	{
		if(!is_array($_POST["user"]))
		{
			ilUtil::sendInfo($this->lng->txt("crs_no_users_selected"));
			$this->performSearch();

			return false;
		}
		if(in_array($this->user_obj->getId(),$_POST['user']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_not_assign_yourself'));

			$this->showTrustees();

			return false;
		}


		// add them
		$counter = 0;
		foreach($_POST['user'] as $user_id)
		{
			if($this->trustee_obj->isTrustee($user_id))
			{
				continue;
			}
			$this->trustee_obj->setTrusteeId($user_id);
			$this->trustee_obj->toggleStatisticPermission(false);
			$this->trustee_obj->toggleObjectPermission(true);
			$this->trustee_obj->toggleCouponsPermission(true);
			$this->trustee_obj->add();
			++$counter;
		}

		if($counter)
		{
			ilUtil::sendInfo($this->lng->txt('paya_added_trustee'));
			$this->showTrustees();

			return true;
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt('paya_user_already_assigned'));
			$this->performSearch();

			return false;
		}

	}
	function addUser()
	{
		if(!$_POST['trustee_login'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_enter_login'));
			$this->showTrustees();
			
			return false;
		}
		if(!$user_id = ilObjUser::getUserIdByLogin($_POST['trustee_login']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_valid_login'));
			$this->showTrustees();
			
			return false;
		}
		if($this->trustee_obj->isTrustee($user_id))
		{
			ilUtil::sendInfo($this->lng->txt('paya_user_already_assigned'));
			$this->showTrustees();
			
			return false;
		}
		if($user_id == $this->user_obj->getId())
		{
			ilUtil::sendInfo($this->lng->txt('paya_not_assign_yourself'));
			$this->showTrustees();

			return false;
		}
		
		// checks passed => add trustee
		$this->trustee_obj->setTrusteeId($user_id);
		$this->trustee_obj->toggleObjectPermission(false);
		$this->trustee_obj->toggleStatisticPermission(true);
		$this->trustee_obj->toggleCouponsPermission(true);
		$this->trustee_obj->add();

		ilUtil::sendInfo($this->lng->txt('paya_added_trustee'));
		$this->showTrustees();

		return true;
	}
	

	// PRIVATE
	function __search($a_search_string)
	{
		include_once("./classes/class.ilSearch.php");

		$this->lng->loadLanguageModule("content");

		$search = new ilSearch($this->user_obj->getId());
		$search->setPerformUpdate(false);
		$search->setSearchString(ilUtil::stripSlashes($a_search_string));
		$search->setCombination("and");
		$search->setSearchFor(array(0 => 'usr'));
		$search->setSearchType('new');

		if($search->validate($message))
		{
			$search->performSearch();
		}
		else
		{
			ilUtil::sendInfo($message,true);
			$this->ctrl->redirect($this,"searchUser");
		}
		return $search->getResultByType('usr');
	}
	function __showSearchUserTable($a_result_set)
	{
		$tbl = $this->initTableGUI();
		$tpl = $tbl->getTemplateObject();


		// SET FORMACTION
		$tpl->setCurrentBlock("tbl_form_header");
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","addTrustee");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("add"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","showTrustees");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("cancel"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",5);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt("paya_trustee_table"),"icon_usr.gif",$this->lng->txt("crs_header_edit_members"));
		$tbl->setHeaderNames(array("",
								   $this->lng->txt("login"),
								   $this->lng->txt("firstname"),
								   $this->lng->txt("lastname")));
		$tbl->setHeaderVars(array("",
								  "login",
								  "firstname",
								  "lastname"),
							array("cmd" => 'performSearch',
								  "cmdClass" => "ilpaymenttrusteegui",
								  "cmdNode" => $_GET["cmdNode"],
								  'baseClass' => 'ilShopController'));

		$tbl->setColumnWidth(array("3%","32%","32%","32%"));

		$this->setTableGUIBasicData($tbl,$a_result_set);
		$tbl->render();
		
		$this->tpl->setVariable("TABLE",$tbl->tpl->get());

		return true;
	}
	
	function __showTrusteesTable($a_result_set)
	{
		$tbl = $this->initTableGUI();
		$tpl = $tbl->getTemplateObject();

		// SET FORMAACTION
		$tpl->setCurrentBlock("tbl_form_header");

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");

		$tpl->setCurrentBlock("input_text");
		$tpl->setVariable("PB_TXT_NAME",'search_str');
		$tpl->setVariable("PB_TXT_VALUE",$_SESSION["paya_search_str"]);
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("plain_button");
		$tpl->setVariable("PBTN_NAME","newSearch");
		$tpl->setVariable("PBTN_VALUE",$this->lng->txt("crs_add_member"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("plain_button");
		$tpl->setVariable("PBTN_NAME","update");
		$tpl->setVariable("PBTN_VALUE",$this->lng->txt("apply"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("plain_buttons");
		$tpl->parseCurrentBlock();

		$tpl->setVariable("COLUMN_COUNTS",8);
		$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));

		$tpl->setCurrentBlock("tbl_action_button");
		$tpl->setVariable("BTN_NAME","deleteTrustee");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("delete"));
		$tpl->parseCurrentBlock();
		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("TPLPATH",$this->tpl->tplPath);
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt("paya_trustee_table"),"icon_usr.gif",$this->lng->txt("paya_trustee_table"));
		$tbl->setHeaderNames(array('',
								   $this->lng->txt("login"),
								   $this->lng->txt("firstname"),
								   $this->lng->txt("lastname"),
								   $this->lng->txt("paya_perm_stat"),
								   $this->lng->txt("paya_perm_obj"),
								   $this->lng->txt("paya_perm_coupons"),
								   ''));
		$tbl->setHeaderVars(array("",
								  "login",
								  "firstname",
								  "lastname",
								  "perm_stat",
								  "perm_obj",
								  "perm_coupons",
								  "options"),
							array("cmd" => "showTrustees",
								  "cmdClass" => "ilpaymenttrusteegui",
								  "cmdNode" => $_GET["cmdNode"],
								  'baseClass' => 'ilShopController'));
		$tbl->setColumnWidth(array("4%","15%","15%","15%","15%","15%","15%","15%"));


		$this->setTableGUIBasicData($tbl,$a_result_set);
		$tbl->render();

		$this->tpl->setVariable("TABLE",$tbl->tpl->get());

		return true;
	}

}
?>