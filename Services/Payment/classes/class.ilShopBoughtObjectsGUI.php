<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Payment/classes/class.ilShopBaseGUI.php';
include_once './Services/Payment/classes/class.ilPaymentCurrency.php';

/**
* Class ilShopBoughtObjectsGUI
*
* @author Michael Jansen <mjansen@databay.de>
* @author Nadia Ahmad <nkrzywon@databay.de>
* @version $Id:$
* 
* @ingroup ServicesPayment
*  
*/
class ilShopBoughtObjectsGUI extends ilShopBaseGUI
{
	private $user_obj;

	private $psc_obj = null;

	public function __construct($user_obj)
	{
		parent::__construct();

		$this->user_obj = $user_obj;
	}
	
	public function executeCommand()
	{
		global $ilUser;

		if(ANONYMOUS_USER_ID == $ilUser->getId() && !isset($_SESSION['download_links']));
		{
	//		$this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
		}

		$cmd = $this->ctrl->getCmd();
		switch ($this->ctrl->getNextClass($this))
		{
 			 case 'showBillHistory':
				$this->$cmd();
				break;

			default:
				$this->prepareOutput();
				if(!$cmd = $this->ctrl->getCmd())
				{
					$cmd = 'showItems';
				}
				$this->$cmd();
				break;
		}
	}
	
/* nkrzywon	*/
  
 	protected function buildSubTabs()
	{
		global $ilTabs, $ilUser, $rbacreview;
		
		$ilTabs->addSubTabTarget('paya_buyed_objects', $this->ctrl->getLinkTarget($this, 'showItems'), '', '', '','showItems');
		$ilTabs->addSubTabTarget('paya_bill_history', $this->ctrl->getLinkTarget($this, 'showBillHistory'), '', '', '','showBillHistory');
	}	

	public function showBillHistory()
	{	
		global $ilTabs;
		
		include_once "./Services/Repository/classes/class.ilRepositoryExplorer.php";
	
		$ilTabs->setSubTabActive('paya_bill_history');
		
		$this->initBookingsObject();
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');

		$bookings = $this->bookings_obj->getDistinctTransactions($this->user_obj->getId());
		
		if(!count($bookings))
		{
			ilUtil::sendInfo($this->lng->txt('pay_not_buyed_any_object'));
			return true;
		}
		
		$counter = 0;
				
		foreach($bookings as $booking)
		{
						
			$f_result[$counter][] = "<a href=\"".$this->ctrl->getLinkTarget($this, "createBill")."&transaction=".$booking['transaction']."\">".$booking['transaction'].".pdf</a>";
			$f_result[$counter][] = ilDatePresentation::formatDate(new ilDateTime($booking['order_date'], IL_CAL_UNIX));	
	
			++$counter;
		}
		return $this->showBillHistoryTable($f_result);
	}
	
	private function showBillHistoryTable($a_result_set)
	{
		include_once('Services/Table/classes/class.ilTableGUI.php');

		$tbl = new ilTableGUI(array(), false);
		$tpl = $tbl->getTemplateObject();

		// SET FORMACTION
		$tpl->setCurrentBlock("tbl_form_header");

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt("paya_bill_history"),"icon_pays_access.gif",$this->lng->txt("paya_bill_history"));
		$tbl->setHeaderNames(array($this->lng->txt("paya_transaction"),$this->lng->txt("paya_order_date")));
	
		$this->ctrl->setParameter($this,'cmd','showBillHistory');
		$header_params = $this->ctrl->getParameterArray($this,'');

		$tbl->setHeaderVars(array("transaction","order_date"), $header_params);

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

		$this->tpl->setVariable("TABLE",$tbl->tpl->get());

		return true;
	}
	
	function createBill()
	{
		global $ilUser, $ilias, $tpl,$ilObjDataCache;
		
		$customer=$this->user_obj;
		$transaction = $_GET['transaction'];

		$total_price = 0;
		$total_vat = 0;
		$i = 0;		

		include_once './classes/class.ilTemplate.php';
		include_once './Services/Utilities/classes/class.ilUtil.php';
		include_once './Services/Payment/classes/class.ilGeneralSettings.php';

		$genSet = new ilGeneralSettings();
		$currency = $genSet->get('currency_unit');

		$user_id = $this->user_obj->getId();

		$bookings = ilPaymentBookings::__readBillByTransaction($user_id,$transaction);
		if($bookings[$i]['street'] == NULL) 	$bookings[$i]['street'] = nl2br(utf8_decode($customer->getStreet()));
		if($bookings[$i]['zipcode'] == NULL)	$bookings[$i]['zipcode'] = nl2br(utf8_decode($customer->getZipcode()));
		if($bookings[$i]['city'] == NULL)		$bookings[$i]['city'] = nl2br(utf8_decode($customer->getCity()));
		if($bookings[$i]['country'] == NULL)	$bookings[$i]['country'] = nl2br(utf8_decode($customer->getCountry()));
		
		$i = 0;
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.pay_bill.html','Services/Payment');		
		$tpl = new ilTemplate('./Services/Payment/templates/default/tpl.pay_bill.html', true, true, true);
  
		$tpl->setVariable('VENDOR_ADDRESS', nl2br(utf8_decode($genSet->get('address'))));
		$tpl->setVariable('VENDOR_ADD_INFO', nl2br(utf8_decode($genSet->get('add_info'))));
		$tpl->setVariable('VENDOR_BANK_DATA', nl2br(utf8_decode($genSet->get('bank_data'))));
		$tpl->setVariable('TXT_BANK_DATA', utf8_decode($this->lng->txt('pay_bank_data')));


		$tpl->setVariable('CUSTOMER_FIRSTNAME',$customer->getFirstName());// $customer['vorname']);
		$tpl->setVariable('CUSTOMER_LASTNAME', $customer->getLastName()); //$customer['nachname']);
		if($bookings['po_box']== '')
		{
			$tpl->setVariable('CUSTOMER_STREET',utf8_decode( $bookings[$i]['street']));
		}
		else
		{
			$tpl->setVariable('CUSTOMER_STREET', $bookings[$i]['po_box']);
		}
		$tpl->setVariable('CUSTOMER_ZIPCODE', $bookings[$i]['zipcode']);
		$tpl->setVariable('CUSTOMER_CITY', $bookings[$i]['city']);
		$tpl->setVariable('CUSTOMER_COUNTRY', $bookings[$i]['country']);

		$tpl->setVariable('BILL_NO', $transaction);
		$tpl->setVariable('DATE', ilDatePresentation::formatDate(new ilDateTime($bookings[$i]['order_date'], IL_CAL_UNIX)));

		$tpl->setVariable('TXT_BILL', utf8_decode($this->lng->txt('pays_bill')));
		$tpl->setVariable('TXT_BILL_NO', utf8_decode($this->lng->txt('pay_bill_no')));
		$tpl->setVariable('TXT_DATE', utf8_decode($this->lng->txt('date')));

		$tpl->setVariable('TXT_ARTICLE', utf8_decode($this->lng->txt('pay_article')));
		$tpl->setVariable('TXT_VAT_RATE', utf8_decode($this->lng->txt('vat_rate')));
		$tpl->setVariable('TXT_VAT_UNIT', utf8_decode($this->lng->txt('vat_unit')));		
		$tpl->setVariable('TXT_PRICE', utf8_decode($this->lng->txt('price_a')));

		for ($i = 0; $i < count($bookings[$i]); $i++)
		{
			$tmp_pobject = new ilPaymentObject($this->user_obj, $booking[$i]['pobject_id']);

			$obj_id = $ilObjDataCache->lookupObjId($bookings[$i]['ref_id']);
			$obj_type = $ilObjDataCache->lookupType($obj_id);
			
			$tpl->setCurrentBlock('loop');
			$tpl->setVariable('LOOP_OBJ_TYPE', utf8_decode($this->lng->txt($obj_type)));
			$tpl->setVariable('LOOP_TITLE', utf8_decode($bookings[$i]['object_title']) . $assigned_coupons);
			$tpl->setVariable('LOOP_TXT_ENTITLED_RETRIEVE', utf8_decode($this->lng->txt('pay_entitled_retrieve')));
			
			if( $bookings[$i]['duration'] == 0)
			{
				$tpl->setVariable('LOOP_DURATION', utf8_decode($this->lng->txt('unlimited_duration')));
			}
			else
			{
				$tpl->setVariable('LOOP_DURATION', $bookings[$i]['duration'] . ' ' . utf8_decode($this->lng->txt('paya_months')));
			}

			// old one
			$tpl->setVariable('LOOP_VAT_RATE',number_format($bookings[$i]['vat_rate'], 2, ',', '.').' %');
			$tpl->setVariable('LOOP_VAT_UNIT', number_format($bookings[$i]['vat_unit'], 2, ',', '.').' '.$currency);
			$tpl->setVariable('LOOP_PRICE',number_format($bookings[$i]['price'], 2, ',', '.').' '.$currency);
			$tpl->parseCurrentBlock('loop');
			
			// TODO: CURRENCY not finished yet
			/*****
					$currency_symbol = $bookings[$i]['currency_unit'];

					$tpl->setVariable('LOOP_VAT_RATE', $bookings[$i]['vat_rate'].' %');
			#		$tpl->setVariable('LOOP_VAT_UNIT', number_format( $bookings[$i]['vat_unit'], 2, ',', '.').' '.$currency);
			#		$tpl->setVariable('LOOP_PRICE',number_format( $bookings[$i]['price'], 2, ',', '.').' '.$currency);

					$tpl->setVariable('LOOP_VAT_UNIT',utf8_decode( ilPaymentCurrency::_formatPriceToString($bookings[$i]['vat_unit'], $currency_symbol)));
					$tpl->setVariable('LOOP_PRICE', utf8_decode(ilPaymentCurrency::_formatPriceToString($bookings[$i]['price'], $currency_symbol)));
					$tpl->parseCurrentBlock('loop');

			/*****	*/

			$bookings['total'] += (float)$bookings[$i]['price'];
			$bookings['total_vat']+= (float)$bookings[$i]['vat_unit'];
			$bookings['total_discount'] +=(float) $bookings[$i]['discount'];
			unset($tmp_pobject);

			$sub_total_amount = $bookings['total'];
		}

		if($bookings['total_discount'] < 0)
		{
			$tpl->setCurrentBlock('cloop');
			$tpl->setVariable('TXT_COUPON', utf8_decode($this->lng->txt('paya_coupons_coupon') . ' ' . $coupon['pcc_code']));
			$tpl->setVariable('BONUS', number_format($bookings['total_discount'], 2, ',', '.') . ' ' . $currency);
			// TODO: CURRENCY	$tpl->setVariable('BONUS', ilPaymentCurrency::_formatPriceToString($current_coupon_bonus * (-1),$currency_symbol));
			$tpl->parseCurrentBlock();
		}

		$bookings['total'] += $bookings['total_discount'];
		$tpl->setVariable('TXT_SUBTOTAL_AMOUNT', utf8_decode($this->lng->txt('pay_bmf_subtotal_amount')));
		$tpl->setVariable('SUBTOTAL_AMOUNT', number_format($sub_total_amount, 2, ',', '.') . ' ' . $currency);
		// TODO: CURRENCY $tpl->setVariable('SUBTOTAL_AMOUNT', ilPaymentCurrency::_formatPriceToString($sub_total_amount, $currency_symbol));

		if ($bookings['total'] < 0)
		{			
			$bookings['total'] = 0.00;
		//	$bookings['total_vat'] = 0.0;
		}

		$tpl->setVariable('TXT_TOTAL_AMOUNT', utf8_decode($this->lng->txt('pay_bmf_total_amount')));
		$tpl->setVariable('TOTAL_AMOUNT', number_format($bookings['total'], 2, ',', '.') . ' ' . $currency);
		// TODO: CURRENCY $tpl->setVariable('TOTAL_AMOUNT',utf8_decode(ilPaymentCurrency::_formatPriceToString($bookings['total'], $currency_symbol) ));
		if ($bookings['total_vat'] > 0)
		{
			$tpl->setVariable('TOTAL_VAT',number_format( $bookings['total_vat'], 2, ',', '.') . ' ' .$currency);
			// TODO: CURRENCY $tpl->setVariable('TOTAL_VAT',ilPaymentCurrency::_formatPriceToString($bookings['total_vat'],$currency_symbol));
			$tpl->setVariable('TXT_TOTAL_VAT', utf8_decode($this->lng->txt('pay_bmf_vat_included')));
		}

		$tpl->setVariable('TXT_PAYMENT_TYPE', utf8_decode($this->lng->txt('pay_payed_bill')));

		if (!@file_exists($genSet->get('pdf_path')))
		{

			ilUtil::makeDir($genSet->get('pdf_path'));
		}

		if (@file_exists($genSet->get('pdf_path')))
		{		
			ilUtil::html2pdf($tpl->get(), $genSet->get('pdf_path') . '/' . $transaction . '.pdf');
		}

		if (@file_exists($genSet->get('pdf_path') . '/' . $transaction . '.pdf')) 
		{
			 ilUtil::deliverFile(
			 	$genSet->get('pdf_path') . '/' . $transaction . '.pdf',
			 	$transaction . '.pdf',
			 	$a_mime = 'application/pdf'
			 );
		}

		@unlink($genSet->get('pdf_path') . '/' . $transaction . '.html');
		@unlink($genSet->get('pdf_path') . '/' . $transaction . '.pdf');
	}
/**/
		
	protected function prepareOutput()
	{
		global $ilTabs;		
		
		parent::prepareOutput();
		
		$ilTabs->setTabActive('paya_buyed_objects');
		$ilTabs->setSubTabActive('paya_buyed_objects');			
	}	
	public function showItems()
	{
		include_once "./Services/Repository/classes/class.ilRepositoryExplorer.php";

		$this->initBookingsObject();

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');

		if(!count($bookings = $this->bookings_obj->getBookingsOfCustomer($this->user_obj->getId())))
		{
			ilUtil::sendInfo($this->lng->txt('pay_not_buyed_any_object'));

			return true;
		}
		
		$counter = 0;
				
		foreach($bookings as $booking)
		{
			$tmp_obj = ilObjectFactory::getInstanceByRefId($booking['ref_id']);
			$tmp_vendor = ilObjectFactory::getInstanceByObjId($booking['b_vendor_id']);
			$tmp_purchaser = ilObjectFactory::getInstanceByObjId($booking['customer_id']);
			
			$transaction = $booking['transaction'];
			
			include_once './Services/Payment/classes/class.ilPayMethods.php';
			$str_paymethod = ilPayMethods::getStringByPaymethod($booking['b_pay_method']);	
			$transaction .= " (" . $str_paymethod . ")";
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
			$f_result[$counter][] = ilDatePresentation::formatDate(new ilDateTime($booking['order_date'], IL_CAL_UNIX)); 

			if($booking['duration'] != 0)
			{
				$f_result[$counter][] = $booking['duration'].' '.$this->lng->txt('paya_months');
			
			}
			else
			{
					$f_result[$counter][] = $this->lng->txt("unlimited_duration");
			}
			$f_result[$counter][] = $booking['price'].' '.$booking['currency_unit'];
			$f_result[$counter][] = ($booking['discount'] != '' ? ($booking['discount'].' '.$booking['currency_unit']) : '&nbsp;');
// TODO CURRENCY
/*
 			$f_result[$counter][] = ilPaymentCurrency::_formatPriceToString($booking['price'], $booking['currency_unit']);
			$f_result[$counter][] = ($booking['discount'] != '' ?  ilPaymentCurrency::_formatPriceToString($booking['discount']) : '&nbsp;');

 */
			$payed_access = $booking['payed'] ? 
				$this->lng->txt('yes') : 
				$this->lng->txt('no');

			$payed_access .= '/';
			$payed_access .= $booking['access_granted'] ?
				$this->lng->txt('yes') : 
				$this->lng->txt('no');

			$f_result[$counter][] = $payed_access;

			unset($tmp_obj);
			unset($tmp_vendor);
			unset($tmp_purchaser);

			++$counter;
		}
		return $this->showStatisticTable($f_result);
	}

	private function showStatisticTable($a_result_set)
	{
		include_once('Services/Table/classes/class.ilTableGUI.php');

		$tbl = new ilTableGUI(array(), false);
		$tpl = $tbl->getTemplateObject();

		// SET FORMACTION
		$tpl->setCurrentBlock("tbl_form_header");

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt("paya_buyed_objects"),"icon_pays_access.gif",$this->lng->txt("bookings"));
		$tbl->setHeaderNames(array($this->lng->txt("paya_transaction"),
								   $this->lng->txt("title"),
								   $this->lng->txt("paya_vendor"),
								   $this->lng->txt("paya_customer"),
								   $this->lng->txt("paya_order_date"),
								   $this->lng->txt("duration"),
								   $this->lng->txt("price_a"),
								   $this->lng->txt("paya_coupons_coupon"),
								   $this->lng->txt("paya_payed_access")));
		$header_params = $this->ctrl->getParameterArray($this,'');
		$tbl->setHeaderVars(array("transaction",
								  "title",
								  "vendor",
								  "customer",
								  "order_date",
								  "duration",
								  "price",
								  "discount",
								  "payed_access"), $header_params);

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

		$this->tpl->setVariable("TABLE",$tbl->tpl->get());

		return true;
	}

	private function initBookingsObject()
	{
		include_once './Services/Payment/classes/class.ilPaymentBookings.php';

		$this->bookings_obj = new ilPaymentBookings();
		
		return true;
	}
}
?>