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
* Class ilPaymentShoppingCartGUI
*
* @author Stefan Meyer
* @version $Id$
*
* @package core
*/

include_once './payment/classes/class.ilPaymentBaseGUI.php';

class ilPaymentBuyedObjectsGUI extends ilPaymentBaseGUI
{
	var $ctrl;

	var $lng;
	var $user_obj;

	/*
	 * shopping cart obj
	 */
	var $psc_obj = null;

	function ilPaymentBuyedObjectsGUI(&$user_obj)
	{
		global $ilCtrl,$tpl;

		$this->ctrl =& $ilCtrl;
		$this->tpl =& $tpl;

		$this->ilPaymentBaseGUI();

		$this->user_obj =& $user_obj;

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
					$cmd = 'showItems';
				}
				$this->$cmd();
				break;
		}
	}

	function showItems()
	{
		include_once "./classes/class.ilRepositoryExplorer.php";

		$this->__initBookingsObject();

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.pay_personal_statistic.html','payment');

		if(!count($bookings = $this->bookings_obj->getBookingsOfCustomer($this->user_obj->getId())))
		{
			sendInfo($this->lng->txt('pay_not_buyed_any_object'));

			return true;
		}
		$counter = 0;
		foreach($bookings as $booking)
		{
			$tmp_obj =& ilObjectFactory::getInstanceByRefId($booking['ref_id']);
			$tmp_vendor =& ilObjectFactory::getInstanceByObjId($booking['b_vendor_id']);
			$tmp_purchaser =& ilObjectFactory::getInstanceByObjId($booking['customer_id']);
			
			$transaction = $booking['transaction_extern'];
			switch ($booking['b_pay_method'])
			{
				case PAY_METHOD_BILL :
					$transaction .= " (" . $this->lng->txt("pays_bill") . ")";
					break;
				case PAY_METHOD_BMF :
					$transaction .= " (" . $this->lng->txt("pays_bmf") . ")";
					break;
				case PAY_METHOD_PAYPAL :
					$transaction .= " (" . $this->lng->txt("pays_paypal") . ")";
					break;
			}
			$f_result[$counter][] = $transaction;

			$obj_link = ilRepositoryExplorer::buildLinkTarget($booking['ref_id'],$tmp_obj->getType());
			$obj_target = ilRepositoryExplorer::buildFrameTarget($tmp_obj->getType(),$booking['ref_id'],$tmp_obj->getId());
			$f_result[$counter][] = "<a href=\"".$obj_link."\" target=\"".$obj_target."\">".$tmp_obj->getTitle()."</a>";
			
			/*
			if ($tmp_obj->getType() == "crs")
			{
				$f_result[$counter][] = "<a href=\"" . ILIAS_HTTP_PATH . "/repository.php?ref_id=" . 
					$booking["ref_id"] . "\">" . $tmp_obj->getTitle() . "</a>";
			}
			else if ($tmp_obj->getType() == "lm")
			{
				$f_result[$counter][] = "<a href=\"" . ILIAS_HTTP_PATH . "/content/lm_presentation.php?ref_id=" . 
					$booking["ref_id"] . "\" target=\"_blank\">" . $tmp_obj->getTitle() . "</a>";
			}
			else
			{
				$f_result[$counter][] = $tmp_obj->getTitle();
			}
			*/
			$f_result[$counter][] = '['.$tmp_vendor->getLogin().']';
			$f_result[$counter][] = '['.$tmp_purchaser->getLogin().']';
			$f_result[$counter][] = date('Y m d H:i:s',$booking['order_date']);
			$f_result[$counter][] = $booking['duration'];
			$f_result[$counter][] = $booking['price'];

			$payed_access = $booking['payed'] ? 
				$this->lng->txt('yes') : 
				$this->lng->txt('no');

			$payed_access .= '/';
			$payed_access .= $booking['access'] ?
				$this->lng->txt('yes') : 
				$this->lng->txt('no');

			$f_result[$counter][] = $payed_access;

			unset($tmp_obj);
			unset($tmp_vendor);
			unset($tmp_purchaser);

			++$counter;
		}
		return $this->__showStatisticTable($f_result);
	}

	// PRIVATE
	function __showStatisticTable($a_result_set)
	{
		$tbl =& $this->initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		// SET FORMAACTION
		$tpl->setCurrentBlock("tbl_form_header");

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();


		$tbl->setTitle($this->lng->txt("paya_statistic"),"icon_pays_b.gif",$this->lng->txt("paya_statistic"));
		$tbl->setHeaderNames(array($this->lng->txt("paya_transaction"),
								   $this->lng->txt("title"),
								   $this->lng->txt("paya_vendor"),
								   $this->lng->txt("paya_customer"),
								   $this->lng->txt("paya_order_date"),
								   $this->lng->txt("duration"),
								   $this->lng->txt("price_a"),
								   $this->lng->txt("paya_payed_access")));
		$header_params = $this->ctrl->getParameterArray($this,'');
		$tbl->setHeaderVars(array("transaction",
								  "title",
								  "vendor",
								  "customer",
								  "order_date",
								  "duration",
								  "price",
								  "payed_access"),$header_params);
								  /*
							array("cmd" => "",
								  "cmdClass" => "ilpaymentbuyedobjectsgui",
								  "baseClass" => "ilPersonalDesktopGUI",
								  "cmdNode" => $_GET["cmdNode"]));
								  */

		$offset = $_GET["offset"];
		$order = $_GET["sort_by"];
		$direction = $_GET["sort_order"] ? $_GET['sort_order'] : 'desc';

		$tbl->setOrderColumn($order,'order_date');
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setMaxCount(count($a_result_set));
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		$tbl->setData($a_result_set);


		$tbl->render();

		$this->tpl->setVariable("STATISTIC_TABLE",$tbl->tpl->get());

		return true;
	}


	function __initBookingsObject()
	{
		include_once './payment/classes/class.ilPaymentBookings.php';

		$this->bookings_obj =& new ilPaymentBookings();
		
		return true;
	}

}
?>