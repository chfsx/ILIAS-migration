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
* Class ilPaymentObjectGUI
*
* @author Stefan Meyer
* @version $Id$
*
* @package core
*/
include_once './payment/classes/class.ilPaymentObject.php';

class ilPaymentObjectGUI extends ilPaymentBaseGUI
{
	var $ctrl;
	var $lng;
	var $user_obj;

	function ilPaymentObjectGUI(&$user_obj)
	{
		global $ilCtrl;

		$this->ctrl =& $ilCtrl;
		$this->ilPaymentBaseGUI();
		$this->user_obj =& $user_obj;

		$this->lng->loadLanguageModule('crs');

	}
	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $tree;

		$cmd = $this->ctrl->getCmd();
		switch ($this->ctrl->getNextClass($this))
		{

			default:
				if(!$cmd = $this->ctrl->getCmd())
				{
					$cmd = 'showObjects';
				}
				$this->$cmd();
				break;
		}
	}

	function showObjects()
	{
		$this->showButton('showObjectSelector',$this->lng->txt('paya_sell_object'));

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.paya_objects.html',true);

		if(!count($objects = ilPaymentObject::_getObjectsData($this->user_obj->getId())))
		{
			sendInfo($this->lng->txt('paya_no_objects_assigned'));
			
			return true;
		}
		include_once './payment/classes/class.ilPaymentObject.php';
		$po =& new ilPaymentObject($this->user_obj);


		$img_change = "<img src=\"".ilUtil::getImagePath("edit.gif")."\" alt=\"".
			$this->lng->txt("edit")."\" title=\"".$this->lng->txt("edit").
			"\" border=\"0\" vspace=\"0\"/>";

		$counter = 0;
		foreach($objects as $data)
		{
			$tmp_obj =& ilObjectFactory::getInstanceByRefId($data['ref_id']);
			$f_result[$counter][] = $tmp_obj->getTitle();


			switch($data['status'])
			{
				case $po->STATUS_BUYABLE:
					$f_result[$counter][] = $this->lng->txt('paya_buyable');
					break;

				case $po->STATUS_NOT_BUYABLE:
					$f_result[$counter][] = $this->lng->txt('paya_not_buyable');
					break;
					
				case $po->STATUS_EXPIRES:
					$f_result[$counter][] = $this->lng->txt('paya_expires');
					break;
			}
			switch($data['pay_method'])
			{
				case $po->PAY_METHOD_NOT_SPECIFIED:
					$f_result[$counter][] = $this->lng->txt('paya_pay_method_not_specified');
					break;

				case $po->PAY_METHOD_BILL:
					$f_result[$counter][] = $this->lng->txt('paya_bill');
					break;
			}
			$tmp_user =& ilObjectFactory::getInstanceByObjId($data['vendor_id']);
			$f_result[$counter][] = $tmp_user->getFullname().' ['.$tmp_user->getLogin().']';

			$f_result[$counter][] = 2;


			// edit link
			$this->ctrl->setParameter($this,"pobject_id",$data['pobject_id']);
			$link_change = "<a href=\"".$this->ctrl->getLinkTarget($this,"editDetails")."\" ".
				$img_change."</a>";

			$f_result[$counter][] = $link_change;
			unset($tmp_user);
			unset($tmp_obj);

			++$counter;
		}

		return $this->__showObjectsTable($f_result);
	}

	function editDetails()
	{
		include_once './payment/classes/class.ilPaymentObject.php';

		if(!$_GET['pobject_id'])
		{
			sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->showObjects();
			return true;
		}
		$this->ctrl->setParameter($this,'pobject_id',(int) $_GET['pobject_id']);

		$this->showButton('editDetails',$this->lng->txt('paya_edit_details'));
		$this->showButton('editPrices',$this->lng->txt('paya_edit_prices'));
		$this->showButton('EditPayMethod',$this->lng->txt('paya_edit_pay_method'));


		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.paya_edit.html',true);
		$this->tpl->setVariable("DETAILS_FORMACTION",$this->ctrl->getFormAction($this));
		
		$po =& new ilPaymentObject($this->user_obj,(int) $_GET['pobject_id']);


		$tmp_obj =& ilObjectFactory::getInstanceByRefId($po->getRefId());
		
		$this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_'.$tmp_obj->getType().'_b.gif'));
		$this->tpl->setVariable("ALT_IMG",$this->lng->txt('obj_'.$tmp_obj->getType()));
		$this->tpl->setVariable("TITLE",$tmp_obj->getTitle());
		$this->tpl->setVariable("DESCRIPTION",$tmp_obj->getDescription());
		$this->tpl->setVariable("TXT_PATH",$this->lng->txt('path'));
		$this->tpl->setVariable("PATH",$this->__getHTMLPath($po->getRefId()));
		$this->tpl->setVariable("TXT_VENDOR",$this->lng->txt('paya_vendor'));
		$this->tpl->setVariable("VENDOR",$this->__showVendorSelector($po->getVendorId()));
		$this->tpl->setVariable("TXT_COUNT_PURCHASER",$this->lng->txt('pay_count_purchaser'));
		$this->tpl->setVariable("COUNT_PURCHASER",17);
		$this->tpl->setVariable("TXT_STATUS",$this->lng->txt('status'));
		$this->tpl->setVariable("STATUS",$this->__showStatusSelector($po));
		$this->tpl->setVariable("TXT_PAY_METHOD",$this->lng->txt('paya_pay_method'));
		$this->tpl->setVariable("PAY_METHOD",$this->__showPayMethodSelector($po));

		$this->tpl->setVariable("INPUT_CMD",'updateDetails');
		$this->tpl->setVariable("INPUT_VALUE",$this->lng->txt('save'));

	}

	function editPrices()
	{
		include_once './payment/classes/class.ilPaymentPrices.php';
		include_once './payment/classes/class.ilPaymentObject.php';
		include_once './classes/class.ilTableGUI.php';

		if(!$_GET['pobject_id'])
		{
			sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->showObjects();
			return true;
		}
		$this->ctrl->setParameter($this,'pobject_id',(int) $_GET['pobject_id']);

		$this->showButton('editDetails',$this->lng->txt('paya_edit_details'));
		$this->showButton('editPrices',$this->lng->txt('paya_edit_prices'));
		$this->showButton('EditPayMethod',$this->lng->txt('paya_edit_pay_method'));

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.paya_edit_prices.html',true);

		$price_obj =& new ilPaymentPrices((int) $_GET['pobject_id']);
		$po =& new ilPaymentObject($this->user_obj,(int) $_GET['pobject_id']);

		// Fill table cells
		$tpl =& new ilTemplate('tpl.table.html',true,true);

		// set table header
		$tpl->setCurrentBlock("tbl_form_header");
		
		$this->ctrl->setParameter($this,'pobject_id',(int) $_GET['pobject_id']);
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->addBlockfile("TBL_CONTENT", "tbl_content",'tpl.paya_edit_prices_row.html',true);
		
		$counter = 0;
		foreach($price_obj->getPrices() as $price)
		{
			$tpl->setCurrentBlock("tbl_content");
			$tpl->setVariable("ROWCOL", ilUtil::switchColor($counter,"tblrow2","tblrow1"));

			$tpl->setVariable("CHECKBOX",ilUtil::formCheckBox(0,'price_id',$price['price_id']));
			$tpl->setVariable("DURATION_NAME",'duration['.$price['price_id'].']');
			$tpl->setVariable("DURATION",$price['duration']);
			$tpl->setVariable("MONTH",$this->lng->txt('paya_months'));
			$tpl->setVariable("UNIT_NAME",'unit_value['.$price['price_id'].']');
			$tpl->setVariable("UNIT",$price['unit_value']);
			$tpl->setVariable("SHORTFORM",'&euro;');
			
			$tpl->setVariable("SUB_UNIT_NAME",'sub_unit_value['.$price['price_id'].']');
			$tpl->setVariable("SUB_UNIT",$price['sub_unit_value']);
			$tpl->setVariable("SUB_UNIT_TXT",'Cent');
			$tpl->parseCurrentBlock();
			
			++$counter;
		}

		// SET FOOTER
		$tpl->setCurrentBlock("plain_buttons");
		$tpl->setVariable("PBTN_NAME","addPrice");
		$tpl->setVariable("PBTN_VALUE",$this->lng->txt("paya_add_price"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_button");
		$tpl->setVariable("BTN_NAME","deletePrice");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("paya_delete_price"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("TPLPATH",$this->tpl->tplPath);
		$tpl->setVariable("COLUMN_COUNTS",4);
		$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		$tpl->parseCurrentBlock();

		$tbl = new ilTableGUI();
		$tbl->setTemplate($tpl);

		// title & header columns
		$tbl->setStyle('table','std');

		$tmp_obj =& ilObjectFactory::getInstanceByRefId($po->getRefId());

		$tbl->setTitle($tmp_obj->getTitle().' ('.$this->lng->txt('paya_prices').')',
					   "icon_".$tmp_obj->getType()."_b.gif",
					   $this->lng->txt("objs_".$tmp_obj->getType()));
		$tbl->setHeaderNames(array('',
								   $this->lng->txt('duration'),
								   $this->lng->txt('price_a'),
								   $this->lng->txt('price_b')));
		$tbl->setHeaderVars(array("",
								  "duration",
								  "price_unit",
								  "price_sub_unit"),
							array("ref_id" => $this->cur_ref_id));


		// control
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($price_obj->getPrices()));

		$tbl->disable("sort");

		// render table
		$tbl->render();

		$this->tpl->setVariable("PRICES_TABLE",$tpl->get());
		
		return true;
	}

		

	function updateDetails()
	{
		include_once './payment/classes/class.ilPaymentObject.php';

		if(!$_GET['pobject_id'])
		{
			sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->showObjects();
			return true;
		}
		$this->ctrl->setParameter($this,'pobject_id',(int) $_GET['pobject_id']);


		// TODO
		// check if object is sold
		$po =& new ilPaymentObject($this->user_obj,(int) $_GET['pobject_id']);

		$po->setVendorId((int) $_POST['vendor']);
		$po->setPayMethod((int) $_POST['pay_method']);
		$po->update();

		sendInfo($this->lng->txt('paya_details_updated'));
		$this->editDetails();

		return true;
	}

	function showObjectSelector()
	{
		global $tree;

		include_once './payment/classes/class.ilPaymentObjectSelector.php';

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.paya_object_selector.html",true);
		$this->showButton('showObjects',$this->lng->txt('back'));


		sendInfo($this->lng->txt("paya_select_object_to_sell"));

		$exp = new ilPaymentObjectSelector($this->ctrl->getLinkTarget($this,'showObjectSelector'));
		$exp->setExpand($_GET["paya_link_expand"] ? $_GET["paya_link_expand"] : $tree->readRootId());
		$exp->setExpandTarget($this->ctrl->getLinkTarget($this,'showObjectSelector'));
		
		$exp->setOutput(0);

		$this->tpl->setVariable("EXPLORER",$exp->getOutput());

		return true;
	}

	function showSelectedObject()
	{
		if(!$_GET['sell_id'])
		{
			sendInfo($this->lng->txt('paya_no_object_selected'));
			
			$this->showObjectSelector();
			return true;
		}
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.paya_selected_object.html',true);
		$this->showButton('showObjectSelector',$this->lng->txt('back'));

		$this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_pays.gif',false));
		$this->tpl->setVariable("ALT_IMG",$this->lng->txt('details'));

		$this->ctrl->setParameter($this,'sell_id',$_GET['sell_id']);
		$this->tpl->setVariable("SO_FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_TITLE",$this->lng->txt('title'));
		$this->tpl->setVariable("TXT_DESCRIPTION",$this->lng->txt('description'));
		$this->tpl->setVariable("TXT_OWNER",$this->lng->txt('owner'));
		$this->tpl->setVariable("TXT_PATH",$this->lng->txt('path'));
		$this->tpl->setVariable("TXT_VENDOR",$this->lng->txt('pays_vendor'));
		$this->tpl->setVariable("BTN1_NAME",'showObjects');
		$this->tpl->setVariable("BTN1_VALUE",$this->lng->txt('cancel'));
		$this->tpl->setVariable("BTN2_NAME",'addObject');
		$this->tpl->setVariable("BTN2_VALUE",$this->lng->txt('next'));

		// fill values
		$this->tpl->setVariable("DETAILS",$this->lng->txt('details'));
		
		if($tmp_obj =& ilObjectFactory::getInstanceByRefId($_GET['sell_id']))
		{
			$this->tpl->setVariable("TITLE",$tmp_obj->getTitle());
			$this->tpl->setVariable("DESCRIPTION",$tmp_obj->getDescription());
			$this->tpl->setVariable("OWNER",$tmp_obj->getOwnerName());
			$this->tpl->setVariable("PATH",$this->__getHTMLPath((int) $_GET['sell_id']));
			$this->tpl->setVariable("VENDOR",$this->__showVendorSelector());
		}
		return true;
	}

	function addObject()
	{
		include_once './payment/classes/class.ilPaymentObject.php';

		if(!$_GET['sell_id'])
		{
			sendInfo($this->lng->txt('paya_no_object_selected'));
			
			$this->showObjectSelector();
			return true;
		}
		if(!(int) $_POST['vendor'])
		{
			sendInfo($this->lng->txt('paya_no_vendor_selected'));
			
			$this->showSelectedObject();
			return true;
		}
		if(!ilPaymentObject::_isPurchasable($_GET['sell_id']))
		{
			sendInfo($this->lng->txt('paya_object_not_purchasable'));

			$this->showObjectSelector();
			return true;
		}

		
		$p_obj =& new ilPaymentObject($this->user_obj);
		
		$p_obj->setRefId((int) $_GET['sell_id']);
		$p_obj->setStatus($p_obj->STATUS_NOT_BUYABLE);
		$p_obj->setPayMethod($p_obj->PAY_METHOD_NOT_SPECIFIED);
		$p_obj->setVendorId((int) $_POST['vendor']);

		if($p_obj->add())
		{
			sendInfo($this->lng->txt('paya_added_new_object'));
			$this->showObjects();

			return true;
		}
		else
		{
			sendInfo($this->lng->txt('paya_err_adding_object'));
			$this->showObjects();

			return false;
		}
	}
	
	// PRIVATE
	function __showVendorSelector($a_selected = 0)
	{
		include_once './payment/classes/class.ilPaymentVendors.php';
		
		$vendors = array();
		if(ilPaymentVendors::_isVendor($this->user_obj->getId()))
		{
			$vendors[] = $this->user_obj->getId();
		}
		if($vend = ilPaymentTrustees::_getVendorsForObjects($this->user_obj->getId()))
		{
			$vendors = array_merge($vendors,$vend);
		}
		foreach($vendors as $vendor)
		{
			$tmp_obj =& ilObjectFactory::getInstanceByObjId($vendor,false);

			$action[$vendor] = $tmp_obj->getFullname().' ['.$tmp_obj->getLogin().']';
		}
		
		return ilUtil::formSelect($a_selected,'vendor',$action,false,true);
	}

	function __showStatusSelector(&$po)
	{
		$action[$po->STATUS_NOT_BUYABLE] = $this->lng->txt('paya_not_buyable');
		$action[$po->STATUS_BUYABLE] = $this->lng->txt('paya_buyable');
		$action[$po->STATUS_EXPIRES] = $this->lng->txt('paya_expires');

		return ilUtil::formSelect($po->getStatus(),'status',$action,false,true);
	}

	function __showPayMethodSelector(&$po)
	{
		$action[$po->PAY_METHOD_NOT_SPECIFIED] = $this->lng->txt('paya_pay_method_not_specified');
		$action[$po->PAY_METHOD_BILL] = $this->lng->txt('paya_bill');

		return ilUtil::formSelect($po->getPayMethod(),'pay_method',$action,false,true);
	}

	function __showObjectsTable($a_result_set)
	{

		$tbl =& $this->initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		// SET FORMAACTION
		$tpl->setCurrentBlock("tbl_form_header");

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		/*
		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setCurrentBlock("plain_buttons");
		$tpl->parseCurrentBlock();

		$tpl->setVariable("COLUMN_COUNTS",6);
		$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));

		$tpl->setCurrentBlock("tbl_action_button");
		$tpl->setVariable("BTN_NAME","deleteTrustee");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("delete"));
		$tpl->parseCurrentBlock();
		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("TPLPATH",$this->tpl->tplPath);
		$tpl->parseCurrentBlock();
		*/

		$tbl->setTitle($this->lng->txt("paya_objects"),"icon_pays_b.gif",$this->lng->txt("paya_objects"));
		$tbl->setHeaderNames(array($this->lng->txt("title"),
								   $this->lng->txt("paya_status"),
								   $this->lng->txt("paya_pay_method"),
								   $this->lng->txt("paya_vendor"),
								   $this->lng->txt("paya_count_purchasers"),
								   $this->lng->txt("paya_options")));
		$tbl->setHeaderVars(array("title",
								  "status",
								  "pay_method",
								  "vendor",
								  "purchasers",
								  "options"),
							array("cmd" => "",
								  "cmdClass" => "ilpaymentobjectgui",
								  "cmdNode" => $_GET["cmdNode"]));
		$tbl->setColumnWidth(array("15%","15%","15%","20%","20%"));

		$this->setTableGUIBasicData($tbl,$a_result_set);
		$tbl->render();

		$this->tpl->setVariable("OBJECTS_TABLE",$tbl->tpl->get());

		return true;
	}


	function __getHTMLPath($a_ref_id)
	{
		global $tree;

		$path = $tree->getPathFull($a_ref_id);
		unset($path[0]);

		foreach($path as $data)
		{
			$html .= $data['title'].' > ';
		}
		return substr($html,0,-2);
	}

		
	
}
?>