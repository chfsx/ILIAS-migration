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
* class ilpaymentpurchasegui
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @extends Object
* @package ilias-core
*/

class ilPaymentPurchaseGUI
{
	var $ctrl;
	var $ilias;
	var $lng;
	var $tpl;

	var $object = null;

	function ilPaymentPurchaseGUI($a_ref_id)
	{
		global $ilCtrl,$lng,$ilErr,$ilias,$tpl,$tree;

		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this,array("ref_id"));

		$this->ilErr =& $ilErr;
		$this->ilias =& $ilias;

		$this->lng =& $lng;
		$this->lng->loadLanguageModule('payment');

		$this->tpl =& $tpl;

		$this->ref_id = $a_ref_id;

		$this->object =& ilObjectFactory::getInstanceByRefId($this->ref_id);
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		$cmd = $this->ctrl->getCmd();

		if (!$cmd = $this->ctrl->getCmd())
		{
			$cmd = "showDetails";
		}
		
		// build header if called from start_purchase.php
		if($this->ctrl->getTargetScript() == 'start_purchase.php')
		{
			$this->__buildHeader();
		}
		$this->$cmd();
	}

	function showDetails()
	{
		$this->__initPaymentObject();
		$this->__initPricesObject();

		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.pay_purchase_details.html','payment');

		$this->tpl->setVariable("DETAILS_FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_'.$this->object->getType().'_b.gif'));
		$this->tpl->setVariable("ALT_IMG",$this->lng->txt('obj_'.$this->object->getType()));
		$this->tpl->setVariable("TITLE",$this->object->getTitle());

		// payment infos
		$this->tpl->setVariable("TXT_INFO",$this->lng->txt('info'));
		switch($this->pobject->getPayMethod())
		{
			case $this->pobject->PAY_METHOD_BILL:
				$this->tpl->setVariable("INFO_PAY",$this->lng->txt('pay_bill'));
				$this->tpl->setVariable("INPUT_CMD",'getBill');
				$this->tpl->setVariable("INPUT_VALUE",$this->lng->txt('pay_get_bill'));
				break;

			case $this->pobject->PAY_METHOD_BMF:
				$this->tpl->setVariable("INFO_PAY",$this->lng->txt('pay_bmf'));
				$this->tpl->setVariable("INPUT_CMD",'addToShoppingCart');
				$this->tpl->setVariable("INPUT_VALUE",$this->lng->txt('pay_add_to_shopping_cart'));
				break;
		}

		$this->tpl->setVariable("ROWSPAN",count($prices = $this->price_obj->getPrices()));
		$this->tpl->setVariable("TXT_PRICES",$this->lng->txt('prices'));

		$counter = 0;
		foreach($prices as $price)
		{
			if(!$counter)
			{
				$this->tpl->setVariable("CHECKBOX",ilUtil::formRadioButton(0,'price_id',$price['price_id']));
				$this->tpl->setVariable("DURATION",$price['duration'].' '.$this->lng->txt('paya_months'));
				$this->tpl->setVariable("PRICE",'36 &euro;');
			}
			else
			{
				$this->tpl->setCurrentBlock("price_row");
				$this->tpl->setVariable("ROW_CHECKBOX",ilUtil::formRadioButton(0,'price_id',$price['price_id']));
				$this->tpl->setVariable("ROW_DURATION",$price['duration'].' '.$this->lng->txt('paya_months'));
				$this->tpl->setVariable("ROW_PRICE",'36 &euro;');
				$this->tpl->parseCurrentBlock();
			}
			++$counter;
		}

	}

	function addToShoppingCart()
	{
		if(!isset($_POST['price_id']))
		{
			sendInfo($this->lng->txt('pay_select_price'));
			$this->showDetails();

			return true;
		}
		else
		{
			$this->__initPaymentObject();
			$this->__initShoppingCartObject();
			

			$this->sc_obj->setPriceId((int) $_POST['price_id']);
			$this->sc_obj->setPobjectId($this->pobject->getPobjectId());
			$this->sc_obj->add();

			sendInfo($this->lng->txt('pay_added_to_shopping_cart'));
			$this->showDetails();

			return true;
		}
	}
	// PRIVATE
	function __initShoppingCartObject()
	{
		include_once './payment/classes/class.ilPaymentShoppingCart.php';

		$this->sc_obj =& new ilPaymentShoppingCart($this->ilias->account);

		return true;
	}

	function __initPaymentObject()
	{
		include_once './payment/classes/class.ilPaymentObject.php';

		$this->pobject =& new ilPaymentObject($this->ilias->account,ilPaymentObject::_lookupPobjectId($this->ref_id));

		return true;
	}
	function __initPricesObject()
	{
		include_once './payment/classes/class.ilPaymentPrices.php';
		
		$this->price_obj =& new ilPaymentPrices($this->pobject->getPobjectId());

		return true;
	}

	function __buildHeader()
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.payb_content.html");
		
		$this->tpl->setVariable("HEADER",$this->object->getTitle());
		$this->tpl->setVariable("DESCRIPTION",$this->object->getDescription());

		$this->__buildStylesheet();
		$this->__buildStatusline();
	}

	function  __buildStatusline()
	{
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
	}	

	function __buildStylesheet()
	{
		$this->tpl->setVariable("LOCATION_STYLESHEET",ilUtil::getStyleSheetLocation());
		$this->tpl->setVariable("LOCATION_JAVASCRIPT",ilUtil::getJSPath('functions.js'));
	}

	

}
?>