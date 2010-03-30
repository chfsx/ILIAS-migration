<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
include_once './Services/Utilities/classes/class.ilConfirmationGUI.php';

class ilPaymentCouponGUI extends ilShopBaseGUI
{
	var $ctrl;

	var $lng;	
	
	var $user_obj = null;
	var $coupon_obj = null;
	var $pobject = null;	

	public function ilPaymentCouponGUI($user_obj)
	{
		parent::__construct();
		
		$this->ctrl->saveParameter($this, 'baseClass');
		$this->user_obj = $user_obj;		
		$this->__initCouponObject();
	}
	
	protected function prepareOutput()
	{
		global $ilTabs;
		
		$this->setSection(6);
		
		parent::prepareOutput();

		$ilTabs->setTabActive('paya_header');
		$ilTabs->setSubTabActive('paya_coupons_coupons');		
	}
	
	public function executeCommand()
	{
		global $tree;

		$cmd = $this->ctrl->getCmd();
		switch ($this->ctrl->getNextClass($this))
		{
			default:
				if (!$cmd = $this->ctrl->getCmd())
				{
					$cmd = 'showCoupons';
				}
				$this->prepareOutput();
				$this->$cmd();
				break;
		}
	}
	
	function resetFilter()
	{
		unset($_SESSION["pay_coupons"]);
		unset($_POST["title_type"]);
		unset($_POST["title_value"]);
		unset($_POST["coupon_type"]);
		unset($_POST["updateView"]);
		unset($_POST['from_check']);
		unset($_POST['until_check']);		
		
		$this->showCoupons();
		
		return true;
	}
	
	function showCoupons()
	{
		global $ilToolbar;
		
		include_once("Services/User/classes/class.ilObjUser.php");
		
		$ilToolbar->addButton($this->lng->txt('paya_coupons_add'), $this->ctrl->getLinkTarget($this,'addCoupon'));
		if(!$_POST['show_filter'] && $_POST['updateView'] == '1')
		{
			$this->resetFilter();
		}
		else
		if ($_POST['updateView'] == 1)
		{ 
			$_SESSION['pay_coupons']['show_filter'] = $_POST['show_filter'];		
			$_SESSION['pay_coupons']['updateView'] = true;
			$_SESSION['pay_coupons']['until_check'] = $_POST['until_check'];
			$_SESSION['pay_coupons']['from_check'] = $_POST['from_check'];
			$_SESSION['pay_coupons']['title_type'] = isset($_POST['title_type']) ? $_POST['title_type'] : '' ;
			$_SESSION['pay_coupons']['title_value'] = isset($_POST['title_value']) ?  $_POST['title_value'] : '';
			
			if($_SESSION['pay_coupons']['from_check'] == '1')
			{
				$_SESSION['pay_coupons']['from']['date']['d'] = $_POST['from']['date']['d'];
				$_SESSION['pay_coupons']['from']['date']['m'] = $_POST['from']['date']['m'];
				$_SESSION['pay_coupons']['from']['date']['y'] = $_POST['from']['date']['y'];
			} 
			else 
			{
				$_SESSION['pay_coupons']['from']['date']['d'] = '';
				$_SESSION['pay_coupons']['from']['date']['m'] = '';
				$_SESSION['pay_coupons']['from']['date']['y'] = '';
			}
			
			if($_SESSION['pay_coupons']['until_check']== '1')
			{
				$_SESSION['pay_coupons']['til']['date']['d'] = $_POST['til']['date']['d'];
				$_SESSION['pay_coupons']['til']['date']['m'] = $_POST['til']['date']['m'];
				$_SESSION['pay_coupons']['til']['date']['y'] = $_POST['til']['date']['y'];
			} 
			else 
			{
				$_SESSION['pay_coupons']['til']['date']['d'] = '';
				$_SESSION['pay_coupons']['til']['date']['m'] = '';
				$_SESSION['pay_coupons']['til']['date']['y'] = '';
			}

			$_SESSION['pay_coupons']['coupon_type'] = $_POST['coupon_type'];

		}
	
		$this->coupon_obj->setSearchTitleType(ilUtil::stripSlashes($_SESSION['pay_coupons']['title_type']));
		$this->coupon_obj->setSearchTitleValue(ilUtil::stripSlashes($_SESSION['pay_coupons']['title_value']));
		$this->coupon_obj->setSearchType(ilUtil::stripSlashes($_SESSION['pay_coupons']['coupon_type']));

		$this->tpl->addBlockfile('ADM_CONTENT', 'adm_content', 'tpl.main_view.html', 'Services/Payment');		

		$filter_form = new ilPropertyFormGUI();
		$filter_form->setFormAction($this->ctrl->getFormAction($this));
		$filter_form->setTitle($this->lng->txt('pay_filter'));
		$filter_form->setId('formular');
		$filter_form->setTableWidth('100 %');
			
		$o_hide_check = new ilCheckBoxInputGUI($this->lng->txt('show_filter'),'show_filter');
		$o_hide_check->setValue(1);		
		$o_hide_check->setChecked($_SESSION['pay_coupons']['show_filter'] ? 1 : 0);	

		$o_hidden = new ilHiddenInputGUI('updateView');
		$o_hidden->setValue(1);
		$o_hidden->setPostVar('updateView');
		$o_hide_check->addSubItem($o_hidden);

		// Title type
		$o_title_type = new ilSelectInputGUI(); 
		$title_option = array($this->lng->txt('pay_starting'),$this->lng->txt('pay_ending'));
		$title_value = array('0','1'); 
		$o_title_type->setTitle($this->lng->txt('title'));
		$o_title_type->setOptions($title_option);
		$o_title_type->setValue($_SESSION['pay_coupons']['title_type']);		
		$o_title_type->setPostVar('title_type');
		$o_hide_check->addSubItem($o_title_type);
		
		// Title value
		$o_title_val = new ilTextInputGUI();
		$o_title_val->setValue($_SESSION['pay_coupons']['title_value']);		
		$o_title_val->setPostVar('title_value');
		$o_hide_check->addSubItem($o_title_val);

		//coupon type
		$o_coupon_type = new ilSelectInputGUI();
		$coupon_option = array(''=>'','fix'=>$this->lng->txt('paya_coupons_fix'),'percent'=>$this->lng->txt('paya_coupons_percentaged'));

		$o_coupon_type->setTitle($this->lng->txt('coupon_type'));
		$o_coupon_type->setOptions($coupon_option);
		$o_coupon_type->setValue($_SESSION['pay_coupons']['coupon_type']);		
		$o_coupon_type->setPostVar('coupon_type');

		$o_hide_check->addSubItem($o_coupon_type);
		
		// date from
		$o_from_check = new ilCheckBoxInputGUI($this->lng->txt('pay_order_date_from'),'from_check');
		$o_from_check->setValue(1);		
		$o_from_check->setChecked($_SESSION['pay_coupons']['from_check'] ? 1 : 0);		
		
		$o_date_from = new ilDateTimeInputGUI();
		$o_date_from->setPostVar('from');			
		$_POST['from'] = $_SESSION['pay_coupons']['from'];
		if($_SESSION['pay_coupons']['from_check'] == '1') 
		{
			$o_date_from->checkInput();	
		}

		$o_from_check->addSubItem($o_date_from);
		$o_hide_check->addSubItem($o_from_check);

		// date until
		$o_until_check = new ilCheckBoxInputGUI($this->lng->txt('pay_order_date_til'), 'until_check');
		$o_until_check->setValue(1);	
		$o_until_check->setChecked($_SESSION['pay_coupons']['until_check'] ? 1 : 0);				

		$o_date_until = new ilDateTimeInputGUI();
		$o_date_until->setPostVar('til');
		$_POST['til'] = $_SESSION['pay_coupons']['til'];
		if($_SESSION['pay_coupons']['until_check'] == '1') 
		{
			$o_date_until->checkInput();	
		}
		
		$o_until_check->addSubItem($o_date_until);
		$o_hide_check->addSubItem($o_until_check);	
	
		$filter_form->addCommandButton('showCoupons', $this->lng->txt('pay_update_view'));
		$filter_form->addCommandButton('resetFilter', $this->lng->txt('pay_reset_filter'));
		
		$filter_form->addItem($o_hide_check);		
	
		$this->tpl->setVariable('FORM', $filter_form->getHTML());
	
		if (!count($coupons = $this->coupon_obj->getCoupons()))
		{
			ilUtil::sendInfo($this->lng->txt('paya_coupons_not_found'));
			//if ($_POST['updateView'] == '1') ilUtil::sendInfo($this->lng->txt('paya_coupons_not_found'));
			//else ilUtil::sendInfo($this->lng->txt('paya_coupons_not_available'));		
			
			return true;
		}		
		
		$counter = 0;
		foreach ($coupons as $coupon)
		{
			$f_result[$counter][] = $coupon['pc_title'];
			$f_result[$counter][] = $coupon['number_of_codes'];
			$f_result[$counter][] = $coupon['usage_of_codes'];
			
			if (!empty($coupon['objects']))
			{
				$objects = "";
				for ($i = 0; $i < count($coupon['objects']); $i++)
				{
					$tmp_obj = ilObjectFactory::getInstanceByRefId($coupon['objects'][$i]);
					$objects .= $tmp_obj->getTitle();
					
					if ($i < count($coupon['objects']) - 1) $objects .= "<br />";
					
					unset($tmp_obj);	
				}				
			}
			else
			{
				$objects = "";
			}
			
			$f_result[$counter][] = $objects;			
	
		//	$f_result[$counter][] = ($coupon['pc_from'] != '0000-00-00' && $coupon['pc_from_enabled'] == '1') ? ilFormat::formatDate($coupon['pc_from'], 'date') : '';
		//	$f_result[$counter][] = ($coupon['pc_till'] != '0000-00-00' && $coupon['pc_till_enabled'] == '1') ? ilFormat::formatDate($coupon['pc_till'], 'date') : '';
			$f_result[$counter][] = ($coupon['pc_from'] != NULL && $coupon['pc_from_enabled'] == '1') ? ilFormat::formatDate($coupon['pc_from'], 'date') : '';
			$f_result[$counter][] = ($coupon['pc_till'] != NULL && $coupon['pc_till_enabled'] == '1') ? ilFormat::formatDate($coupon['pc_till'], 'date') : '';
			$f_result[$counter][] = 
				($coupon['pc_last_changed'] != '0000-00-00 00:00:00' ? ilFormat::formatDate($coupon['pc_last_changed']) : '') .
				($coupon['pc_last_change_usr_id'] != '0' ? "[" . ilObjUser::_lookupLogin($coupon['pc_last_change_usr_id']) . "]" : '');
			$this->ctrl->setParameter($this, 'coupon_id',  $coupon['pc_pk']);
			$f_result[$counter][] = "<div class=\"il_ContainerItemCommands\"><a class=\"il_ContainerItemCommand\" href=\"".$this->ctrl->getLinkTarget($this, "addCoupon")."\">".$this->lng->txt("edit")."</a></div>";
					
			++$counter;
		}
		
		return $this->__showCouponsTable($f_result);
	}
	
	function __showCouponsTable($f_result)
	{
		$tbl = $this->initTableGUI();
		$tpl = $tbl->getTemplateObject();

		$tpl->setCurrentBlock("tbl_form_header");

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();	

		$tbl->setTitle($this->lng->txt("paya_coupons_coupons"), "icon_pays.gif", $this->lng->txt("paya_coupons_coupons"));
		$tbl->setHeaderNames(array($this->lng->txt("paya_coupons_title"),
								   $this->lng->txt("paya_coupons_number_of_codes"),
								   $this->lng->txt("paya_coupons_usage_of_codes"),
								   $this->lng->txt("paya_coupons_objects"),
								   $this->lng->txt("paya_coupons_from"),
								   $this->lng->txt("paya_coupons_till"),
								   $this->lng->txt("last_change"), ''));
		$header_params = $this->ctrl->getParameterArray($this, '');
		$tbl->setHeaderVars(array('pc_title',
								  'number_of_codes',
								  'usage_of_codes',
								  'objects',
								  'pc_from',
								  'pc_till',								  
								  'last_changed'), 
								  $header_params
							);
		$offset = $_GET['offset'];
		$order = $_GET['sort_by'];
		$direction = $_GET['sort_order'] ? $_GET['sort_order'] : 'desc';
		$tbl->setOrderColumn($order,'pc_title');
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET['limit']);
		$tbl->setMaxCount(count($f_result));
		$tbl->setFooter('tblfooter', $this->lng->txt('previous'), $this->lng->txt('next'));
		$tbl->setData($f_result);
		$tbl->render();

		$this->tpl->setVariable('TABLE', $tbl->tpl->get());
		
		return true;	
	}

	function saveCouponForm()
	{
		$this->error = '';

		if ($_POST['title'] == '') $this->error .= 'paya_coupons_title,';
		if ($_POST['coupon_type'] == '') $this->error .= 'paya_coupons_type,';
		if ($_POST['coupon_value'] == '') $this->error .= 'paya_coupons_value,';
		else $_POST['coupon_value'] = ilFormat::checkDecimal($_POST['coupon_value']);		
		
		$this->coupon_obj->setId($_GET['coupon_id']);
		$this->coupon_obj->setCouponUser($this->user_obj->getId());
		$this->coupon_obj->setTitle(ilUtil::stripSlashes($_POST['title']));
		$this->coupon_obj->setDescription(ilUtil::stripSlashes($_POST['description']));			
		$this->coupon_obj->setType(ilUtil::stripSlashes($_POST['coupon_type']));
		$this->coupon_obj->setValue(ilUtil::stripSlashes($_POST['coupon_value']));			
		$this->coupon_obj->setFromDate( date("Y-m-d",mktime(0,0,0,$_POST['from']['date']['m'],$_POST['from']['date']['d'],$_POST['from']['date']['y'])));
		$this->coupon_obj->setTillDate( date("Y-m-d",mktime(0,0,0,$_POST['til']['date']['m'],$_POST['til']['date']['d'],$_POST['til']['date']['y'])));		
	//	$this->coupon_obj->setFromDateEnabled(ilUtil::stripSlashes($_POST['from_check']));
	//$this->coupon_obj->setTillDateEnabled(ilUtil::stripSlashes($_POST['until_check']));

		$this->coupon_obj->setFromDateEnabled($_POST['from_check']);
		$this->coupon_obj->setTillDateEnabled($_POST['until_check']);
		$this->coupon_obj->setUses((int)ilUtil::stripSlashes($_POST['usage']));			
		$this->coupon_obj->setChangeDate(date('Y-m-d H:i:s'));				
		
		if ($this->error == '')
		{		
			if ($_GET['coupon_id'] != "")
			{	
				$this->coupon_obj->update();

			}
			else
			{
				$_GET['coupon_id'] = $this->coupon_obj->add();				 
			}
			
			ilUtil::sendInfo($this->lng->txt('saved_successfully'));
		}
		else
		{			
			if (is_array($e = explode(',', $this->error)))
			{				
				$mandatory = '';
				for ($i = 0; $i < count($e); $i++)
				{
					$e[$i] = trim($e[$i]);
					if ($e[$i] != '')
					{
						$mandatory .= $this->lng->txt($e[$i]);
						if (array_key_exists($i + 1, $e) && $e[$i + 1] != '') $mandatory .= ', ';
					}
				}
				ilUtil::sendInfo($this->lng->txt('fill_out_all_required_fields') . ': ' . $mandatory);
			}			
		}		
		
		$this->addCoupon();
		
		return true;
	}
	
	function addCoupon()
	{		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');	
		
		if (isset($_GET['coupon_id']))
		{
			if ($this->error == '') $this->coupon_obj->getCouponById($_GET['coupon_id']);
			
			$this->ctrl->setParameter($this, 'coupon_id', $this->coupon_obj->getId());						
			
			$this->__showButtons();			
		}		

		
		$oForm = new ilPropertyFormGUI();
		$oForm->setId('frm_add_coupon');
		$oForm->setFormAction($this->ctrl->getFormAction($this,'saveCouponForm'));
		$oForm->setTitle($this->coupon_obj->getId() ? $this->lng->txt('paya_coupons_edit') : $this->lng->txt('paya_coupons_add'));

		// Title
		$oTitle = new ilTextInputGUI($this->lng->txt(paya_coupons_title),'title');
		$oTitle->setValue($this->coupon_obj->getTitle());
		$oTitle->setRequired(true);
		$oForm->addItem($oTitle);
		
		// Description
		$oDescription = new ilTextAreaInputGUI($this->lng->txt(paya_coupons_description),'description');
		$oDescription->setValue($this->coupon_obj->getDescription());
		$oForm->addItem($oDescription);
		
		// Type
		$o_coupon_type = new ilSelectInputGUI();
		$coupon_option = array('fix'=>$this->lng->txt('paya_coupons_fix'),'percent'=>$this->lng->txt('paya_coupons_percentaged'));

		$o_coupon_type->setTitle($this->lng->txt('coupon_type'));
		$o_coupon_type->setOptions($coupon_option);
		$o_coupon_type->setValue($this->coupon_obj->getType());		
		$o_coupon_type->setRequired(true);
		$o_coupon_type->setPostVar('coupon_type');
		
		$oForm->addItem($o_coupon_type);
		
		// Value
		include_once './Services/Payment/classes/class.ilGeneralSettings.php';

	
		$o_coupon_value = new ilTextInputGUI($this->lng->txt('paya_coupons_value'),'coupon_value');
		$o_coupon_value->setValue($this->coupon_obj->getValue());

		$o_coupon_value->setRequired(true);
		$oForm->addItem($o_coupon_value);
		
		// Date Valid From
		$o_from_check = new ilCheckBoxInputGUI($this->lng->txt('paya_coupons_from'),'from_check');
		$o_from_check->setValue(1);
		$o_from_check->setChecked($this->coupon_obj->getFromDateEnabled() ? 1 : 0);

		$o_date_from = new ilDateTimeInputGUI();
		$o_date_from->setPostVar('from');			
	
		$from_date = explode('-', $this->coupon_obj->getFromDate());
		$date_f['from']['date']['d'] = $from_date[2] != '00' ? $from_date[2] : '';
		$date_f['from']['date']['m'] = $from_date[1] != '00' ? $from_date[1] : '';
		$date_f['from']['date']['y'] = $from_date[0] != '0000' ? $from_date[0] : '';

		$_POST['from'] = $date_f['from'];
		if($this->coupon_obj->getFromDateEnabled() == '1') 
		{
			$o_date_from->checkInput();
		}

		$o_from_check->addSubItem($o_date_from);
		$oForm->addItem($o_from_check);
		
		// Date Valid Until
		$o_until_check = new ilCheckBoxInputGUI($this->lng->txt('paya_coupons_till'), 'until_check');
		$o_until_check->setValue(1);
		$o_until_check->setChecked($this->coupon_obj->getTillDateEnabled() ? 1 : 0);				

		$o_date_until = new ilDateTimeInputGUI();
		$o_date_until->setPostVar('til');
			
		$till_date = explode('-', $this->coupon_obj->getTillDate());
		$date_t['til']['date']['d']= $till_date[2] != '00' ? $till_date[2] : '';
		$date_t['til']['date']['m'] = $till_date[1] != '00' ? $till_date[1] : '';
		$date_t['til']['date']['y'] = $till_date[0] != '0000' ? $till_date[0] : '';
		
		$_POST['til'] = $date_t['til'];
		if($this->coupon_obj->getTillDateEnabled() == '1') 
		{
			$o_date_until->checkInput();	
		}
		
		$o_until_check->addSubItem($o_date_until);
		$oForm->addItem($o_until_check);	
		
		$o_usage = new ilTextInputGUI($this->lng->txt('paya_coupons_availability'),'usage');
		$o_usage->setValue($this->coupon_obj->getUses());
		$oForm->addItem($o_usage);
		
		$oForm->addCommandButton('saveCouponForm', $this->lng->txt('save'));
		$oForm->addCommandButton('showCoupons', $this->lng->txt('cancel'));
		
		$this->tpl->setVariable('FORM',$oForm->getHTML());
	}
	
	function deleteAllCodes()
	{		
		$this->showCodes("all");
		
		return true;
	}
	
	function performDeleteAllCodes()
	{
		$this->coupon_obj->deleteAllCodesByCouponId($_GET['coupon_id']);	
		
		$this->showCodes();

		return true;
	}
	
	function deleteCodes()
	{
		$_SESSION['paya_delete_codes'] = $_POST['codes'];
		
		if (!is_array($_POST['codes']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_coupons_no_codes_selected'));
			
			$this->showCodes();

			return true;
		}
		
		$this->showCodes("selected");
		
		return true;
	}

	function performDeleteCodes()
	{
		if (is_array($_SESSION['paya_delete_codes']))
		{			
			foreach($_SESSION['paya_delete_codes'] as $id)
			{
				$this->coupon_obj->deleteCode($id);
			}
		}
		unset($_SESSION['paya_delete_codes']);
		ilUtil::sendInfo($this->lng->txt('paya_coupons_code_deleted_successfully'));
		
		$this->showCodes();

		return true;
	}
	
	function cancelDelete()
	{
		unset($_SESSION['paya_delete_codes']);
		
		$this->showCodes();

		return true;
	}
	
	function showCodes($a_show_delete = "")
	{		
		$this->coupon_obj->setId($_GET['coupon_id']);
		
		if (!count($codes = $this->coupon_obj->getCodesByCouponId($_GET['coupon_id'])))
		{
			ilUtil::sendInfo($this->lng->txt('paya_coupons_codes_not_available'));			
			
			$this->generateCodes();			
			
			return true;
		}		
		
		$this->coupon_obj->getCouponById(ilUtil::stripSlashes($_GET['coupon_id']));
		
		$this->ctrl->setParameter($this, 'coupon_id', $_GET['coupon_id']);		
		$this->__showButtons();	

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');	
		
		if($a_show_delete)
		{
			switch($a_show_delete)
			{
				case 'all': $del_cmd = 'performDeleteAllCodes';
							$del_info = $this->lng->txt('paya_coupons_sure_delete_all_codes');
						break;
				case 'selected': $del_cmd = 'performDeleteCodes';
							$del_info = $this->lng->txt('paya_coupons_sure_delete_selected_codes');
						break;
				
			}
			
			$oConfirmationGUI = new ilConfirmationGUI() ;
			// set confirm/cancel commands
			$oConfirmationGUI->setFormAction($this->ctrl->getFormAction($this, $del_cmd));
			$oConfirmationGUI->setHeaderText($del_info);
			$oConfirmationGUI->setCancel($this->lng->txt("cancel"), "cancelDelete");
			$oConfirmationGUI->setConfirm($this->lng->txt("confirm"), $del_cmd);			
	
			foreach ($codes as $code)
			{
				if(in_array($code['pcc_pk'],$_SESSION['paya_delete_codes']))
				{
					$oConfirmationGUI->addItem('',$code['pcc_code'], $code['pcc_code']);					
				}
			}
			
			$this->tpl->setVariable('CONFIRMATION', $oConfirmationGUI->getHTML());
			return true;
			
		}	

		$_SESSION['paya_delete_codes'] = $_SESSION['paya_delete_codes'] ? $_SESSION['paya_delete_codes'] : array();
		
		$counter = 0;
		foreach ($codes as $code)
		{
			$f_result[$counter][]	= ilUtil::formCheckbox(in_array($code['pcc_pk'], $_SESSION['paya_delete_codes']) ? 1 : 0,
										"codes[]", $code['pcc_pk']);
			$f_result[$counter][] = $code['pcc_code'];
			$f_result[$counter][] = $code['pcc_used']." ".strtolower($this->lng->txt('of'))." ".$this->coupon_obj->getUses();
						
			++$counter;
		}
						
		$tbl = $this->initTableGUI();
		$tpl = $tbl->getTemplateObject();
		
		$tpl->setCurrentBlock("tbl_form_header");		
		$tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();
		$tbl->setTitle($this->lng->txt("paya_coupons_coupon")." ".$this->coupon_obj->getTitle().": ".$this->lng->txt("paya_coupons_codes"), "icon_pays.gif", $this->lng->txt("paya_coupons_codes"));
		$tbl->setHeaderNames(array('', $this->lng->txt("paya_coupons_code"), $this->lng->txt('paya_coupons_usage_of_codes')));		
		$this->ctrl->setParameter($this, "cmd", "showCodes");
		$header_params = $this->ctrl->getParameterArray($this, '');
		$tbl->setHeaderVars(array('', 'pcc_code', 'paya_coupons_usage_of_codes'), $header_params);
		$offset = $_GET['offset'];
		$order = $_GET['sort_by'];
		$direction = $_GET['sort_order'] ? $_GET['sort_order'] : 'desc';
		$tbl->setOrderColumn($order,'pcc_code');
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET['limit']);
		$tbl->setMaxCount(count($f_result));
		$tbl->setFooter('tblfooter', $this->lng->txt('previous'), $this->lng->txt('next'));
		$tbl->setData($f_result);
		$tpl->setVariable('COLUMN_COUNTS', 3);
		
		$tbl->enable('select_all');
		$tbl->setFormName('cmd');
		$tbl->setSelectAllCheckbox('codes');
		
		$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		
		$tpl->setCurrentBlock("tbl_action_button");
		$tpl->setVariable("BTN_NAME","deleteCodes");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("delete"));
		$tpl->parseCurrentBlock();		
				
		$tpl->setCurrentBlock('plain_button');
		$tpl->setVariable('PBTN_NAME', 'generateCodes');
		$tpl->setVariable('PBTN_VALUE', $this->lng->txt('paya_coupons_generate_codes'));
		$tpl->parseCurrentBlock();
		
		$tpl->setCurrentBlock('plain_button');
		$tpl->setVariable('PBTN_NAME', 'deleteAllCodes');
		$tpl->setVariable('PBTN_VALUE', $this->lng->txt('delete_all'));
		$tpl->parseCurrentBlock();		
		
		$tpl->setCurrentBlock("plain_button");
		$tpl->setVariable("PBTN_NAME","exportCodes");
		$tpl->setVariable("PBTN_VALUE",$this->lng->txt("export"));
		$tpl->parseCurrentBlock();
				
		$tbl->setColumnWidth(array("10%","90%"));		
		$tbl->render();		

		//$this->tpl->setVariable('CODES_TABLE', $tbl->tpl->get());
		$this->tpl->setVariable('TABLE', $tbl->tpl->get());		
		
		return true;
	}
	
	function exportCodes()
	{
		$codes = $this->coupon_obj->getCodesByCouponId($_GET["coupon_id"]);
		
		if (is_array($codes))
		{
			include_once('./Services/Utilities/classes/class.ilCSVWriter.php');			
			
			$csv = new ilCSVWriter();
			$csv->setDelimiter("");			
			
			foreach($codes as $data)
			{							
				if ($data["pcc_code"])
				{					
					$csv->addColumn($data["pcc_code"]);
					$csv->addRow();					
				}
			}
			
			ilUtil::deliverData($csv->getCSVString(), "code_export_".date("Ymdhis").".csv");
		}
		
		$this->showCodes();
		
		return true;
	}
	
	function saveCodeForm()
	{
		if (isset($_POST["generate_length"])) $_SESSION["pay_coupons"]["code_length"] = $_POST["generate_length"];
		else $_POST["generate_length"] = $_SESSION["pay_coupons"]["code_length"];
		
		if (isset($_POST["generate_type"])) $_SESSION["pay_coupons"]["code_type"] = $_POST["generate_type"];
		else $_POST["generate_type"] = $_SESSION["pay_coupons"]["code_type"];
		
		if ($_POST["generate_type"] == "self")
		{
			if ($_GET["coupon_id"] && is_array($_POST["codes"]))
			{				
				$count_inserts = 0;
				
				for ($i = 0; $i < count($_POST["codes"]); $i++)
				{
					$_POST["codes"][$i] = trim($_POST["codes"][$i]);
					
					if ($_POST["codes"][$i] != "")
					{					
						$code = $this->__makeCode($_POST["codes"][$i], $_SESSION["pay_coupons"]["code_length"]);
						
						if ($code != "")
						{
							if ($this->coupon_obj->addCode(ilUtil::stripSlashes($code), $_GET["coupon_id"]))
							{
								++$count_inserts;
							}
						}
					}
				}
				
				if ($count_inserts) 
				{
					ilUtil::sendInfo($this->lng->txt("saved_successfully"));
					$this->showCodes();
				}
				else
				{
					ilUtil::sendInfo($this->lng->txt("paya_coupons_no_codes_generated"));
					$this->generateCodes();
				}				
			}
			else if (!is_numeric($_POST["generate_number"]) ||  $_POST["generate_number"] <= 0)
			{
				ilUtil::sendInfo($this->lng->txt("fill_out_all_required_fields"));					
				
				$this->generateCodes();	
			}
			else
			{
				$this->generateCodes("input");
			}
		}
		else if ($_POST["generate_type"] == "auto")
		{
			if ($_GET["coupon_id"] && is_numeric($_POST["generate_number"]) && $_POST["generate_number"] > 0)
			{				
				for ($i = 0; $i < $_POST["generate_number"]; $i++)
				{
					$code = $this->__makeCode("", $_SESSION["pay_coupons"]["code_length"]);
					
					if ($code != "")
					{
						$this->coupon_obj->addCode($code, $_GET["coupon_id"]);
					}
				}
				
				ilUtil::sendInfo($this->lng->txt("saved_successfully"));
				
				$this->showCodes();					
			}
			else
			{	
				ilUtil::sendInfo($this->lng->txt("fill_out_all_required_fields"));					
				
				$this->generateCodes();
			}
		}
	}
	
	function __makeCode($a_code = "", $a_length = 32)
	{
		if ($a_code == "") $a_code = md5(uniqid(rand()));
	
		if (strlen($a_code) > $a_length)
		{
			$a_code = substr($a_code, 0, $a_length);
		}		
				
		return $a_code;
	}
	
	function generateCodes($view = "choice")
	{		
		$this->coupon_obj->setId($_GET['coupon_id']);
		
		$this->ctrl->setParameter($this, 'coupon_id', $_GET['coupon_id']);
		$this->__showButtons();
		
		$this->coupon_obj->getCouponById(ilUtil::stripSlashes($_GET['coupon_id']));

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');
		$oForm_1 = new ilPropertyFormGUI();
		$oForm_1->setId('save_frm');
		$oForm_1->setFormAction($this->ctrl->getFormAction($this),'saveCodeForm');
		$oForm_1->setTitle($this->lng->txt("paya_coupons_coupon")." ".$this->coupon_obj->getTitle().": ".$this->lng->txt('paya_coupons_code_generation'));
		
		if ($view == "choice")
		{
			$oTypeRadio = new ilRadioGroupInputGUI($this->lng->txt('paya_coupons_generate_codes'), 'generate_type');
			
			$radio_option = new ilRadioOption($this->lng->txt('paya_coupons_type_auto'), 'auto');
			$oTypeRadio->addOption($radio_option);
			$radio_option = new ilRadioOption($this->lng->txt('paya_coupons_type_self'), 'self');
			$oTypeRadio->addOption($radio_option);
			
			$oTypeRadio->setValue(isset($_POST["generate_type"]) ? $_POST["generate_type"] : "auto");
			$oTypeRadio->setPostVar('generate_type'); 
			$oForm_1->addItem($oTypeRadio);

			$oNumCodes = new ilNumberInputGUI($this->lng->txt("paya_coupons_number_of_codes"),'generate_number');
			$oNumCodes->setValue($_POST['generate_number']);
			$oNumCodes->setRequired(true);
			$oForm_1->addItem($oNumCodes);
					
			$oLength = new ilTextInputGUI($this->lng->txt("paya_coupons_code_length"),'generate_length');
			$oLength->setValue($_POST['generate_length']);
			$oLength->setInfo($this->lng->txt('paya_coupons_type_self'));
			$oForm_1->addItem($oLength);
			
			$oForm_1->addCommandButton('saveCodeForm',$this->lng->txt('save'));
			
			$this->tpl->setVariable('FORM', $oForm_1->getHTML());
		
			$oForm_2 = new ilPropertyformGUI();
			$oForm_2->setId('import_frm');
			$oForm_2->setFormAction($this->ctrl->getFormAction($this), 'showCodeImport');
			$oForm_2->addCommandButton('showCodeImport',$this->lng->txt('import'));
			$this->tpl->setVariable('FORM_2', $oForm_2->getHTML());
			
		}
		else if ($view == "input")
		{
			if (is_numeric($_POST['generate_number']) && $_POST['generate_number'] > 0)
			{
				for ($i = 0; $i < $_POST['generate_number']; $i++)
				{
					$index = $i +1;
					$oLoopCode = new ilTextInputGUI('#'.$index,'codes['.$i.']');
					$oForm_1->addItem($oLoopCode);
				}
					$oForm_1->addCommandButton('saveCodeForm',$this->lng->txt('save'));
			}
			
			$this->tpl->setVariable('FORM',$oForm_1->getHTML());
			
			$oLoopCode = new ilTextInputGUI();
		}
				
		return true;
	}
	
	function assignObjects()
	{
		if (is_array($_POST['objects']))
		{
			$this->coupon_obj->setId($_GET["coupon_id"]);		
			foreach($_POST['objects'] as $id)
			{							
				if ($id)
				{					
					$this->coupon_obj->assignObjectToCoupon($id);
				}
			}			
		}
		
		$this->showObjects();
		
		return true;
	}
	
	function unassignObjects()
	{
		if (is_array($_POST['objects']))
		{			
			$this->coupon_obj->setId($_GET["coupon_id"]);				
			foreach($_POST['objects'] as $id)
			{							
				if ($id)
				{
					$this->coupon_obj->unassignObjectFromCoupon($id);
				}
			}			
		}
		
		$this->showObjects();
		
		return true;
	}
	
	function showObjects()
	{
		$this->coupon_obj->setId($_GET['coupon_id']);
		
		$this->__initPaymentObject();		
		
		$this->ctrl->setParameter($this, 'coupon_id', $_GET['coupon_id']);
		$this->__showButtons();
		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');		

		$objects = $this->pobject->_getObjectsData($this->user_obj->getId());

		$this->coupon_obj->getCouponById(ilUtil::stripSlashes($_GET['coupon_id']));
	
		$counter_assigned = 0;
		$counter_unassigned = 0;
		$f_result_assigned = array();
		$f_result_unassigned = array();
		foreach($objects as $data)
		{					
			if ($this->coupon_obj->isObjectAssignedToCoupon($data['ref_id']))
			{
				$p_counter =& $counter_assigned;
				$p_result =& $f_result_assigned;
			}
			else
			{
				$p_counter =& $counter_unassigned;
				$p_result =& $f_result_unassigned;
			}
			
			$tmp_obj = ilObjectFactory::getInstanceByRefId($data['ref_id']);
			
			$p_result[$p_counter][]	= ilUtil::formCheckbox(0, 'objects[]', $data['ref_id']);			
			$p_result[$p_counter][] = $tmp_obj->getTitle();
			switch($data['status'])
			{
				case $this->pobject->STATUS_BUYABLE:
					$p_result[$p_counter][] = $this->lng->txt('paya_buyable');
					break;

				case $this->pobject->STATUS_NOT_BUYABLE:
					$p_result[$p_counter][] = $this->lng->txt('paya_not_buyable');
					break;
					
				case $this->pobject->STATUS_EXPIRES:
					$p_result[$p_counter][] = $this->lng->txt('paya_expires');
					break;
			}
			include_once './Services/Payment/classes/class.ilPayMethods.php';
			$p_result[$p_counter][] = ilPaymethods::getStringByPaymethod($data['pay_method']);
				
			++$p_counter;				
							
			unset($tmp_obj);			
		}
		
		$this->ctrl->setParameter($this, "cmd", "showObjects");
	
		if (count($f_result_assigned) > 0)
		{	
			$tbl = $this->initTableGUI();
			$tpl = $tbl->getTemplateObject();
			$tbl->setPrefix('assigned');
			
			$tpl->setCurrentBlock("tbl_form_header");		
			$tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
			$tpl->parseCurrentBlock();	
			$tbl->setTitle($this->lng->txt("paya_coupons_coupon")." ".$this->coupon_obj->getTitle().": ".$this->lng->txt("paya_coupons_assigned_objects"),"icon_pays.gif",$this->lng->txt("paya_coupons_assigned_objects"));
			$tbl->setHeaderNames(array("", 
									   $this->lng->txt("title"),
								   	   $this->lng->txt("status"),
								   	   $this->lng->txt("paya_pay_method")));
			$header_params = $this->ctrl->getParameterArray($this,'');
			$tbl->setHeaderVars(array("", 
									  "title",
								  	  "status",
								  	  "pay_method"),$header_params);		
			$offset = $_GET['assignedoffset'];
			$order = $_GET['assignedsort_by'];
			$direction = $_GET['assignedsort_order'] ? $_GET['assignedsort_order'] : 'desc';		
			$tbl->setOrderColumn($order,'title');
			$tbl->setOrderDirection($direction);
			$tbl->setOffset($offset);
			$tbl->setLimit($_GET['limit']);
			$tbl->setMaxCount(count($f_result_assigned));
			$tbl->setFooter('tblfooter', $this->lng->txt('previous'), $this->lng->txt('next'));
			$tbl->setData($f_result_assigned);								  
			$tpl->setVariable('COLUMN_COUNTS', 4);		
			$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));		
			$tpl->setCurrentBlock("tbl_action_button");
			$tpl->setVariable("BTN_NAME","unassignObjects");
			$tpl->setVariable("BTN_VALUE",$this->lng->txt("remove"));
			$tpl->parseCurrentBlock();
			$tbl->setColumnWidth(array("10%","20%","20%","20%"));			
			$tbl->render();

			$this->tpl->setVariable('TABLE', $tbl->tpl->get());
		}
		
		if (count($f_result_unassigned) > 0)
		{		
			$tbl = $this->initTableGUI();
			$tpl = $tbl->getTemplateObject();
			$tpl->setCurrentBlock("tbl_form_header");		
			$tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
			$tpl->parseCurrentBlock();	
			$tbl->setTitle($this->lng->txt("paya_coupons_coupon")." ".$this->coupon_obj->getTitle().": ".$this->lng->txt("paya_coupons_unassigned_objects"),"icon_pays.gif",$this->lng->txt("paya_coupons_unassigned_objects"));
			$tbl->setHeaderNames(array("", 
									   $this->lng->txt("title"),
								   	   $this->lng->txt("status"),
								   	   $this->lng->txt("paya_pay_method")));
			$header_params = $this->ctrl->getParameterArray($this,'');
			$tbl->setHeaderVars(array("", 
									  "title_ua",
								  	  "status_ua",
								  	  "pay_method_ua"),$header_params);		
			$offset = $_GET['offset'];
			$order = $_GET['sort_by'];
			$direction = $_GET['sort_order'] ? $_GET['sort_order'] : 'desc';		
			$tbl->setOrderColumn($order,'title_ua');
			$tbl->setOrderDirection($direction);
			$tbl->setOffset($offset);
			$tbl->setLimit($_GET['limit']);
			$tbl->setMaxCount(count($f_result_unassigned));
			$tbl->setFooter('tblfooter', $this->lng->txt('previous'), $this->lng->txt('next'));
			$tbl->setData($f_result_unassigned);								  
			$tpl->setVariable('COLUMN_COUNTS', 4);		
			$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));		
			$tpl->setCurrentBlock("tbl_action_button");
			$tpl->setVariable("BTN_NAME","assignObjects");
			$tpl->setVariable("BTN_VALUE",$this->lng->txt("add"));
			$tpl->parseCurrentBlock();
			$tbl->setColumnWidth(array("10%","20%","20%","20%"));			
			$tbl->render();
			$this->tpl->setVariable('TABLE_2', $tbl->tpl->get());
		}
		
		return true;
	}
	
	function importCodes()
	{	
		include_once('./Services/Utilities/classes/class.ilCSVReader.php');
		
		if ($_FILES["codesfile"]["tmp_name"] != "")
		{
			$csv = new ilCSVReader();
			$csv->setDelimiter("");
			
			if ($csv->open($_FILES["codesfile"]["tmp_name"]))
			{		
				$data = $csv->getDataArrayFromCSVFile();
				
				for ($i = 0; $i < count($data); $i++)
				{
					for ($j = 0; $j < count($data[$i]); $j++)
					{
						$this->coupon_obj->addCode(ilUtil::stripSlashes($data[$i][$j]), $_GET["coupon_id"]);	
					}
				}				
				
				ilUtil::sendInfo($this->lng->txt("paya_coupon_codes_import_successful"));
				
				$this->showCodes();
			}
			else
			{
				ilUtil::sendInfo($this->lng->txt("paya_coupons_import_error_opening_file"));
				
				$this->showCodeImport();
			}
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt("fill_out_all_required_fields"));
			
			$this->showCodeImport();					
		}
		
		return true;
	}
	
	function showCodeImport()
	{
		$this->ctrl->setParameter($this, 'coupon_id', $_GET['coupon_id']);
		$this->__showButtons();
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');
		
		$this->coupon_obj->getCouponById(ilUtil::stripSlashes($_GET['coupon_id']));
		$oForm = new ilPropertyFormGUI();
		$oForm->setId('coup_form');
		$oForm->setFormAction($this->ctrl->getFormAction($this), 'importCodes');
		$oForm->setTitle( $this->lng->txt("paya_coupons_coupon")." ".$this->coupon_obj->getTitle().": ".$this->lng->txt('paya_coupons_codes_import'));
		
		$oFile = new ilFileInputGUI($this->lng->txt('file'),'codesfile');
		$oFile->setSuffixes(array("txt"));
		$oFile->setRequired(true);
		$oFile->setInfo($this->lng->txt('import_use_only_textfile'));
		$oForm->addItem($oFile);
		
		$oForm->addCommandButton('importCodes',$this->lng->txt('upload'));
		
		$this->tpl->setVariable('FORM', $oForm->getHTML());
		
		return true;
	}
	
	function __showButtons()
	{		
		global $ilToolbar;

		$ilToolbar->addButton($this->lng->txt('paya_coupons_edit'), $this->ctrl->getLinkTarget($this, 'addCoupon'));
		$ilToolbar->addButton($this->lng->txt('paya_coupons_edit_codes'), $this->ctrl->getLinkTarget($this, 'showCodes'));
		$ilToolbar->addButton($this->lng->txt('paya_coupons_edit_objects'), $this->ctrl->getLinkTarget($this, 'showObjects'));
	
		return true;
	}
	
	function __initPaymentObject($a_pobject_id = 0)
	{
		include_once './Services/Payment/classes/class.ilPaymentObject.php';

		$this->pobject = new ilPaymentObject($this->user_obj, $a_pobject_id);

		return true;
	}
	
	function __initCouponObject()
	{
		include_once './Services/Payment/classes/class.ilPaymentCoupons.php';	

		$this->coupon_obj = new ilPaymentCoupons($this->user_obj, true);

		return true;
	}
}
?>
