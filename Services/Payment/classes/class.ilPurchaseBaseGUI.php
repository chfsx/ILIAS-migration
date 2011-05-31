<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class class.ilPurchaseBaseGUI.php
*
* @author Nadia Ahmad <nahmad@databay.de>
* @version $Id: class.ilPurchaseBaseGUI.php 
*
* 
*/

include_once './Services/Payment/classes/class.ilPaymentShoppingCart.php';
include_once './Services/Payment/classes/class.ilShopShoppingCartGUI.php';
include_once './Services/Payment/classes/class.ilPaymentCoupons.php';
include_once './Services/Payment/classes/class.ilShopVatsList.php';
include_once './Services/Payment/classes/class.ilPayMethods.php';
include_once './Services/Payment/classes/class.ilShopUtils.php';
include_once './Services/Payment/classes/class.ilInvoiceNumberPlaceholdersPropertyGUI.php';

class ilPurchaseBaseGUI
{
	public $ctrl;
	public $tpl;

	public $psc_obj = null;
	public $user_obj = null;
	
	public $coupon_obj = null;
	public $error;

	public $pmethod_obj = null;
	private $pm_id = 0;
	private $totalVat = 0;
	private $session_var = null;

	public function ilPurchaseBaseGUI($user_obj, $pay_method)
	{
		global $ilias, $lng, $tpl, $rbacsystem, $ilCtrl,  $ilTabs;

		$this->ilias = $ilias;
		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->user_obj = $user_obj;
		
		$this->pmethod_obj = new ilPayMethods($pay_method);
		$this->session_var = $this->pmethod_obj->getPmTitle();
	
		$this->pm_id = $pay_method;		
	
		$this->psc_obj = new ilPaymentShoppingCart($this->user_obj);
		
		$this->coupon_obj = new ilPaymentCoupons($this->user_obj);
		
		if (!is_array($_SESSION[$this->session_var]['personal_data']))
		{
			$_SESSION[$this->session_var]['personal_data']['firstname'] = $this->user_obj->getFirstname();
			$_SESSION[$this->session_var]['personal_data']['lastname'] = $this->user_obj->getLastname();
			if (strpos('_' . $this->user_obj->getStreet(), ' ') > 0)
			{
				$houseNo = substr($this->user_obj->getStreet(), strrpos($this->user_obj->getStreet(), ' ')+1);
				$street = substr($this->user_obj->getStreet(), 0, strlen($this->user_obj->getStreet())-(strlen($houseNo)+1));
				$_SESSION[$this->session_var]['personal_data']['street'] = $street;
				$_SESSION[$this->session_var]['personal_data']['house_number'] = $houseNo;
			}
			else
			{
				$_SESSION[$this->session_var]['personal_data']['street'] = $this->user_obj->getStreet();
				$_SESSION[$this->session_var]['personal_data']['house_number'] = '';
			}
			$_SESSION[$this->session_var]['personal_data']['po_box'] = '';
			$_SESSION[$this->session_var]['personal_data']['zipcode'] = $this->user_obj->getZipcode();
			$_SESSION[$this->session_var]['personal_data']['city'] = $this->user_obj->getCity();
			$_SESSION[$this->session_var]['personal_data']['country'] = $this->__getCountryCode($this->user_obj->getCountry());
			$_SESSION[$this->session_var]['personal_data']['email'] = $this->user_obj->getEmail();
			$_SESSION[$this->session_var]['personal_data']['language'] = $this->user_obj->getLanguage();
		}
		
		if (!is_array($_SESSION['coupons'][$this->session_var]))
		{
			$_SESSION['coupons'][$this->session_var] = array();
		}

		$this->__loadTemplate();
		$this->error = '';
		$this->lng->loadLanguageModule('payment');
		
		$ilTabs->clearTargets();
		$ilTabs->clearSubTabs();
	}
	
	public function cancel()
	{
		ilUtil::redirect('./payment.php');
	}

	public function showPersonalData()
	{
		$this->psc_obj = new ilPaymentShoppingCart($this->user_obj);

		if(!count($items = $this->psc_obj->getEntries($this->pm_id)))
		{
			$this->tpl->setVariable('HEADER',$this->lng->txt('pay_bmf_your_order'));
			$this->tpl->touchBlock('stop_floating');
			ilUtil::sendInfo($this->lng->txt('pay_shopping_cart_empty'));
		}
		else
		{
			$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');
			
			$oForm = new ilPropertyFormGUI();
			$oForm->setFormAction($this->ctrl->getFormAction($this, 'getPersonalData'));
			$oForm->setTitle($this->lng->txt('pay_bmf_personal_data'));
		
			$oFirstname = new ilNonEditableValueGUI($this->lng->txt('firstname'));
			$oFirstname->setValue($this->user_obj->getFirstname());
			$oForm->addItem($oFirstname);
			
			$oLastname = new ilNonEditableValueGUI($this->lng->txt('lastname'));
			$oLastname->setValue($this->user_obj->getLastname());
			$oForm->addItem($oLastname);
			
			$oStreet = new ilTextInputGUI($this->lng->txt('street'),'street');
			$oStreet->setValue($this->error != '' && isset($_POST['street'])
								? ilUtil::prepareFormOutput($_POST['street'],true)
								: ilUtil::prepareFormOutput($_SESSION[$this->session_var]['personal_data']['street'],true));
			$oForm->addItem($oStreet);
			
			$oHouseNumber = new ilTextInputGUI($this->lng->txt('pay_bmf_house_number'), 'house_number');
			$oHouseNumber->setValue($this->error != '' && isset($_POST['house_number'])
									? ilUtil::prepareFormOutput($_POST['house_number'],true)
									: ilUtil::prepareFormOutput($_SESSION[$this->session_var]['personal_data']['house_number'],true));
			$oForm->addItem($oHouseNumber);
			
			$oPoBox = new ilTextInputGUI($this->lng->txt('pay_bmf_or').'  '.$this->lng->txt('pay_bmf_po_box'), 'po_box');
			$oPoBox->setValue($this->error != '' && isset($_POST['po_box'])
									? ilUtil::prepareFormOutput($_POST['po_box'],true)
									: ilUtil::prepareFormOutput($_SESSION[$this->session_var]['personal_data']['po_box'],true));
			$oForm->addItem($oPoBox);

			$oZipCode = new ilTextInputGUI($this->lng->txt('zipcode'), 'zipcode');
			$oZipCode->setValue($this->error != '' && isset($_POST['zipcode'])
									? ilUtil::prepareFormOutput($_POST['zipcode'],true)
									: ilUtil::prepareFormOutput($_SESSION[$this->session_var]['personal_data']['zipcode'],true));
			$oForm->addItem($oZipCode);

			$oCity = new ilTextInputGUI($this->lng->txt('city'), 'city');
			$oCity->setValue($this->error != '' && isset($_POST['city'])
									? ilUtil::prepareFormOutput($_POST['city'],true)
									: ilUtil::prepareFormOutput($_SESSION[$this->session_var]['personal_data']['city'],true));
			$oForm->addItem($oCity);						

			$oCountry = new ilSelectInputGUI($this->lng->txt('country'), 'country');
			$oCountry->setOptions($this->__getCountries());
			$oCountry->setValue($this->error != '' && isset($_POST['country']) ? $_POST['country'] 
					: $_SESSION[$this->session_var]['personal_data']['country']);
			$oForm->addItem($oCountry);	

			$oEmail = new ilNonEditableValueGUI($this->lng->txt('email'));
			$oEmail->setValue($this->user_obj->getEmail());
			$oForm->addItem($oEmail);
			
			$oForm->addcommandButton('getPersonalData',ucfirst($this->lng->txt('next')));		

			$this->tpl->setVariable('FORM', $oForm->getHTML());
		}
	}

	public function getPersonalData()
	{	

		if ($_SESSION[$this->session_var]['personal_data']['firstname'] == '' ||
			$_SESSION[$this->session_var]['personal_data']['lastname'] == '' ||
			$_POST['zipcode'] == '' ||
			$_POST['city'] == '' ||
			$_POST['country'] == '' ||
			$_SESSION[$this->session_var]['personal_data']['email'] == '')
		{

			$this->error = $this->lng->txt('pay_bmf_personal_data_not_valid');
			ilUtil::sendInfo($this->error);
			$this->showPersonalData();
			return;
		}
		
		if (($_POST['street'] == '' && $_POST['house_number'] == '' && $_POST['po_box'] == '') ||
			(($_POST['street'] != '' || $_POST['house_number'] != '') && $_POST['po_box'] != '') ||
			($_POST['street'] != '' && $_POST['house_number'] == '') ||
			($_POST['street'] == '' && $_POST['house_number'] != ''))
		{		
			$this->error = $this->lng->txt('pay_bmf_street_or_pobox');
			ilUtil::sendInfo($this->error);
			$this->showPersonalData();
			return;
		}

		$_SESSION[$this->session_var]['personal_data']['firstname'] = $this->user_obj->getFirstname();
		$_SESSION[$this->session_var]['personal_data']['lastname'] = $this->user_obj->getLastname();
		$_SESSION[$this->session_var]['personal_data']['street'] = $_POST['street'];
		$_SESSION[$this->session_var]['personal_data']['house_number'] = $_POST['house_number'];
		$_SESSION[$this->session_var]['personal_data']['po_box'] = $_POST['po_box'];
		$_SESSION[$this->session_var]['personal_data']['zipcode'] = $_POST['zipcode'];
		$_SESSION[$this->session_var]['personal_data']['city'] = $_POST['city'];
		$_SESSION[$this->session_var]['personal_data']['country'] = $_POST['country'];

		$_SESSION[$this->session_var]['personal_data']['email'] = $this->user_obj->getEmail();
		$_SESSION[$this->session_var]['personal_data']['language'] = $this->user_obj->getLanguage();

		$this->error = '';
		$this->showBillConfirm();

	}

	public function showBillConfirm()
	{
		$this->psc_obj = new ilPaymentShoppingCart($this->user_obj);

		if(!count($items = $this->psc_obj->getEntries($this->pm_id)))
		{
			$this->tpl->setVariable('HEADER',$this->lng->txt('pay_bmf_your_order'));
			$this->tpl->touchBlock('stop_floating');
			ilUtil::sendInfo($this->lng->txt('pay_shopping_cart_empty'));
		}
		else
		{
			$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.pay_bill_confirm.html','Services/Payment');
			
			$this->__showShoppingCart();
	
			$this->tpl->setVariable('BILL_CONFIRM_FORMACTION',$this->ctrl->getFormAction($this));
	
			// set table header
			$this->tpl->setVariable('TYPE_IMG',ilUtil::getImagePath('icon_pays_b.gif'));
			$this->tpl->setVariable('ALT_IMG',$this->lng->txt('obj_usr'));
			$this->tpl->touchBlock('stop_floating');
			$this->tpl->setVariable('TXT_CLOSE_WINDOW',$this->lng->txt('close_window'));
	
			// set plain text variables
			$this->tpl->setVariable('TXT_TERMS_CONDITIONS',$this->lng->txt('pay_bmf_terms_conditions'));
			$this->tpl->setVariable('TXT_TERMS_CONDITIONS_READ',$this->lng->txt('pay_bmf_terms_conditions_read'));
			$this->tpl->setVariable('TXT_TERMS_CONDITIONS_SHOW',$this->lng->txt('pay_bmf_terms_conditions_show'));
			$this->tpl->setVariable('LINK_TERMS_CONDITIONS','./payment.php?view=conditions');
			$this->tpl->setVariable('TXT_PASSWORD',$this->lng->txt('password'));
			$this->tpl->setVariable('TXT_CONFIRM_ORDER',$this->lng->txt('pay_confirm_order'));
	
			$this->tpl->setVariable('INPUT_VALUE',$this->lng->txt('pay_send_order'));
			$this->tpl->setVariable('CANCEL',$this->lng->txt('cancel'));
			if ($this->error != '' &&
				isset($_POST['terms_conditions']))
			{
				$this->tpl->setVariable('TERMS_CONDITIONS_' . strtoupper($_POST['terms_conditions']), ' checked') ;
			}
			if ($this->error != '' &&
				isset($_POST['password']))
			{
				$this->tpl->setVariable('PASSWORD', ilUtil::prepareFormOutput($_POST['password'],true));
			}
	
			// Button
			$this->tpl->addBlockfile('BUTTONS', 'buttons', 'tpl.buttons.html');
			$this->tpl->setCurrentBlock('btn_cell');
			$this->tpl->setVariable('BTN_LINK', $this->ctrl->getLinkTarget($this, 'showPersonalData'));
			$this->tpl->setVariable('BTN_TXT', $this->lng->txt('pay_bmf_back'));
			$this->tpl->parseCurrentBlock('btn_cell');
		}
	}
	
	/**
	* execute command
	*/
	public function executeCommand()
	{
		global $tree;

		$cmd = $this->ctrl->getCmd();

		switch ($this->ctrl->getNextClass($this))
		{
			default:
				if(!$cmd = $this->ctrl->getCmd())
				{
					$cmd = 'showPersonalData';
				}
				$this->$cmd();
				break;
		}
	}

	public function __addBookings($external_data = null)
	{
		global $ilUser,	$ilObjDataCache;
			
		$sc = $this->psc_obj->getShoppingCart($this->pm_id);

		$this->psc_obj->clearCouponItemsSession();		

		if (is_array($sc) && count($sc) > 0)
		{
			include_once './Services/Payment/classes/class.ilPaymentBookings.php';
			$book_obj = new ilPaymentBookings($this->usr_obj);
			
			for ($i = 0; $i < count($sc); $i++)
			{
				if (!empty($_SESSION['coupons'][$this->session_var]))
				{									
					$sc[$i]['math_price'] = (float) $sc[$i]['price'];  								

					$tmp_pobject = new ilPaymentObject($this->user_obj, $sc[$i]['pobject_id']);	
													
					foreach ($_SESSION['coupons'][$this->session_var] as $key => $coupon)
					{					
						$this->coupon_obj->setId($coupon['pc_pk']);
						$this->coupon_obj->setCurrentCoupon($coupon);
						
						if ($this->coupon_obj->isObjectAssignedToCoupon($tmp_pobject->getRefId()))
						{
							$_SESSION['coupons'][$this->session_var][$key]['total_objects_coupon_price'] += (float) $sc[$i]['price'];							
							$_SESSION['coupons'][$this->session_var][$key]['items'][] = $sc[$i];
						}								
					}
					
					unset($tmp_pobject);
				}
			}
			
			$coupon_discount_items = $this->psc_obj->calcDiscountPrices($_SESSION['coupons'][$this->session_var]);
			$transaction = ilInvoiceNumberPlaceholdersPropertyGUI::_generateInvoiceNumber($ilUser->getId());
 
			for ($i = 0; $i < count($sc); $i++)
			{
				$pobjectData = ilPaymentObject::_getObjectData($sc[$i]['pobject_id']);
				$pobject = new ilPaymentObject($this->user_obj,$sc[$i]['pobject_id']);
				
				$price = $sc[$i]['price'];				
				$bonus = 0.0;
				
				if (array_key_exists($sc[$i]['pobject_id'], $coupon_discount_items))
				{
					$bonus = $coupon_discount_items[$sc[$i]['pobject_id']]['math_price'] - $coupon_discount_items[$sc[$i]['pobject_id']]['discount_price'];
					if($bonus > 0)
						 $discount = round($bonus, 2)* (-1);
					else $discount = round($bonus, 2);

				}				

				$book_obj->setTransaction($transaction);
				$book_obj->setPobjectId($sc[$i]['pobject_id']);
				$book_obj->setCustomerId($this->user_obj->getId());
				$book_obj->setVendorId($pobjectData['vendor_id']);
				$book_obj->setPayMethod($pobjectData['pay_method']);
				$book_obj->setOrderDate(time());
				$book_obj->setDuration($sc[$i]['duration']);						
				$book_obj->setUnlimitedDuration($sc[i]['unlimited_duration']);
				$book_obj->setPrice($sc[$i]['price_string']);					
				//$book_obj->setDiscount($bonus > 0 ? ilPaymentPrices::_getPriceStringFromAmount($bonus * (-1)) : '');
				$book_obj->setDiscount($discount);
				$book_obj->setPayed(1);
				$book_obj->setAccess(1);
				
				$book_obj->setVoucher(''); // bmf
				$book_obj->setTransactionExtern(''); // bmf , paypal	
	
				$book_obj->setVatRate($sc[$i]['vat_rate']);
				$book_obj->setVatUnit($sc[$i]['vat_unit']);
				$book_obj->setObjectTitle(strip_tags($sc[$i]['object_title']));				
				$book_obj->setAccessExtension($sc[$i]['extension']);

				if($external_data)
				{
					$book_obj->setVoucher($external_data['voucher']); // bmf
					$book_obj->setTransactionExtern($external_data['transaction_extern']); // bmf, paypal
					$book_obj->setStreet($external_data['street'],'');
					$book_obj->setZipcode($external_data['zipcode']);
					$book_obj->setCity($external_data['city']);
					$book_obj->setCountry($external_data['country']);
				}
				else
				if(isset($_SESSION[$this->session_var]['personal_data']))
				{
					$book_obj->setStreet($_SESSION[$this->session_var]['personal_data']['street'], $_SESSION[$this->session_var]['personal_data']['house_number']);
					$book_obj->setPoBox($_SESSION[$this->session_var]['personal_data']['po_box']);
					$book_obj->setZipcode($_SESSION[$this->session_var]['personal_data']['zipcode']);
					$book_obj->setCity($_SESSION[$this->session_var]['personal_data']['city']);
					$book_obj->setCountry($_SESSION[$this->session_var]['personal_data']['country']);
				}
				else
				{
					$book_obj->setStreet($this->user_obj->getStreet(), '');
					$book_obj->setPoBox($this->user_obj->getPoBox());
					$book_obj->setZipcode($this->user_obj->getZipCode());
					$book_obj->setCity($this->user_obj->getCity());
					$book_obj->setCountry($this->user_obj->getCountry());
				}

				$booking_id = $book_obj->add();
				
            // add purchased item to desktop
            ilShopUtils::_addPurchasedObjToDesktop($pobject);

            // autosubscribe user if purchased object is a course
            $obj_type = ilObject::_lookupType($pobject->getRefId(),true);

            if($obj_type == 'crs')
            {
                ilShopUtils::_assignPurchasedCourseMemberRole($pobject);
            }

				if (!empty($_SESSION['coupons'][$this->session_var]) && $booking_id)
				{				
					foreach ($_SESSION['coupons'][$this->session_var] as $coupon)
					{	
						$this->coupon_obj->setId($coupon['pc_pk']);				
						$this->coupon_obj->setCurrentCoupon($coupon);																
							
						if ($this->coupon_obj->isObjectAssignedToCoupon($pobject->getRefId()))
						{						
							$this->coupon_obj->addCouponForBookingId($booking_id);																					
						}				
					}			
				}

				$obj_id = $ilObjDataCache->lookupObjId($pobjectData['ref_id']);
				$obj_type = $ilObjDataCache->lookupType($obj_id);
				$obj_title = $ilObjDataCache->lookupTitle($obj_id);

				// put bought object on personal desktop
			#	ilObjUser::_addDesktopItem($this->user_obj->getId(), $pobjectData['ref_id'], $obj_type);

				$bookings['list'][] = array(
					'pobject_id' => $sc[$i]['pobject_id'],
					'type' => $obj_type,
					'title' => '['.$obj_id.']: ' . $obj_title,
					'duration' => $sc[$i]['duration'],		
					'vat_rate' => $sc[$i]['vat_rate'], 
					'vat_unit' => $sc[$i]['vat_unit'],  
					'price_string' => $sc[$i]['price_string'],	
					'price' => $sc[$i]['price'],				
					'discount'=> $discount
				);
#'bonus'=> $bonus
				$total += $sc[$i]['price'];				
				$total_vat += $sc[$i]['vat_unit'];
				$total_discount += $discount;
				
				if ($sc[$i]['psc_id']) $this->psc_obj->delete($sc[$i]['psc_id']);				
			}
			
			if (!empty($_SESSION['coupons'][$this->session_var]))
			{				
				foreach ($_SESSION['coupons'][$this->session_var] as $coupon)
				{	
					$this->coupon_obj->setId($coupon['pc_pk']);				
					$this->coupon_obj->setCurrentCoupon($coupon);
					$this->coupon_obj->addTracking();			
				}			
			}
		}

		$bookings['total'] = $total;
		$bookings['total_vat'] = $total_vat;
		$bookings['total_discount'] = $total_discount;
		$bookings['transaction'] = $transaction;
		$bookings['street'] = $book_obj->getStreet();
		$bookings['zipcode'] = $book_obj->getZipCode();
		$bookings['city'] = $book_obj->getCity();
		$bookings['country'] = $book_obj->getCountry();
		$bookings['transaction_extern'] = $book_obj->getTransactionExtern();

		$this->__sendBill($bookings);
	}
	
	public function __sendBill($bookings)
	{
		global $tpl;

		include_once './classes/class.ilTemplate.php';
		include_once './Services/Utilities/classes/class.ilUtil.php';
		include_once './Services/Payment/classes/class.ilPaymentSettings.php';
		include_once './Services/Payment/classes/class.ilPaymentShoppingCart.php';
		include_once 'Services/Mail/classes/class.ilMimeMail.php';

		$psc_obj = new ilPaymentShoppingCart($this->user_obj);
		$genSet = ilPaymentSettings::_getInstance();
		$currency = $genSet->get('currency_unit');

		$tpl = new ilTemplate('./Services/Payment/templates/default/tpl.pay_bill.html', true, true, true);
  
		if($tpl->placeholderExists('HTTP_PATH'))
		{
			$http_path = ilUtil::_getHttpPath();
			$tpl->setVariable('HTTP_PATH', $http_path);
		}
		ilDatePresentation::setUseRelativeDates(false);
		$tpl->setVariable('DATE', utf8_decode(ilDatePresentation::formatDate(new ilDateTime($bookings['list'][$i]['order_date'], IL_CAL_UNIX))));
		$tpl->setVariable('TXT_CREDIT', utf8_decode($this->lng->txt('credit')));
		$tpl->setVariable('TXT_DAY_OF_SERVICE_PROVISION',$this->lng->txt('day_of_service_provision'));
		include_once './Services/Payment/classes/class.ilPayMethods.php';
		$str_paymethod = ilPayMethods::getStringByPaymethod($bookings['list'][$i]['b_pay_method']);
		$tpl->setVariable('TXT_EXTERNAL_BILL_NO', str_replace('%s',$str_paymethod,utf8_decode($this->lng->txt('external_bill_no'))));
		$tpl->setVariable('EXTERNAL_BILL_NO', $bookings['list'][$i]['transaction_extern']);
		$tpl->setVariable('TXT_POSITION',$this->lng->txt('position'));
		$tpl->setVariable('TXT_AMOUNT',$this->lng->txt('amount'));
		$tpl->setVariable('TXT_UNIT_PRICE', utf8_decode($this->lng->txt('unit_price')));

		$tpl->setVariable('VENDOR_ADDRESS', nl2br(utf8_decode($genSet->get('address'))));
		$tpl->setVariable('VENDOR_ADD_INFO', nl2br(utf8_decode($genSet->get('add_info'))));
		$tpl->setVariable('VENDOR_BANK_DATA', nl2br(utf8_decode($genSet->get('bank_data'))));
		$tpl->setVariable('TXT_BANK_DATA', utf8_decode($this->lng->txt('pay_bank_data')));


		$tpl->setVariable('CUSTOMER_FIRSTNAME', $this->user_obj->getFirstname());
		$tpl->setVariable('CUSTOMER_LASTNAME', $this->user_obj->getLastname());
		if($bookings['po_box']== '')
		{
			$tpl->setVariable('CUSTOMER_STREET', $bookings['street']); // contains also housenumber
		}
		else
		{
			$tpl->setVariable('CUSTOMER_STREET', $bookings['po_box']);
		}
		$tpl->setVariable('CUSTOMER_ZIPCODE', $bookings['zipcode']);
		$tpl->setVariable('CUSTOMER_CITY', $bookings['city']);
		$tpl->setVariable('CUSTOMER_COUNTRY', $bookings['country']);

		$tpl->setVariable('BILL_NO', $bookings['transaction']);
		$tpl->setVariable('DATE', date('d.m.Y'));

		$tpl->setVariable('TXT_BILL', utf8_decode($this->lng->txt('pays_bill')));
		$tpl->setVariable('TXT_BILL_NO', utf8_decode($this->lng->txt('pay_bill_no')));
		$tpl->setVariable('TXT_DATE', utf8_decode($this->lng->txt('date')));

		$tpl->setVariable('TXT_ARTICLE', utf8_decode($this->lng->txt('pay_article')));
		$tpl->setVariable('TXT_VAT_RATE', utf8_decode($this->lng->txt('vat_rate')));
		$tpl->setVariable('TXT_VAT_UNIT', utf8_decode($this->lng->txt('vat_unit')));		
		$tpl->setVariable('TXT_PRICE', utf8_decode($this->lng->txt('price_a')));

		for ($i = 0; $i < count($bookings['list']); $i++)
		{
			$tmp_pobject = new ilPaymentObject($this->user_obj, $bookings['list'][$i]['pobject_id']);
		
			$assigned_coupons = '';					
			if (!empty($_SESSION['coupons'][$this->session_var]))
			{											
				foreach ($_SESSION['coupons'][$this->session_var] as $coupon)
				{
					$this->coupon_obj->setId($coupon['pc_pk']);
					$this->coupon_obj->setCurrentCoupon($coupon);

					if ($this->coupon_obj->isObjectAssignedToCoupon($tmp_pobject->getRefId()))
					{
						$assigned_coupons .= '<br />' . $this->lng->txt('paya_coupons_coupon') . ': ' . $coupon['pcc_code'];
					}
				}
			}

			$tpl->setCurrentBlock('loop');
			$tpl->setVariable('LOOP_POSITION', $i+1);
			$tpl->setVariable('LOOP_AMOUNT', '1');
			$tpl->setVariable('LOOP_TXT_PERIOD_OF_SERVICE_PROVISION', utf8_decode($this->lng->txt('period_of_service_provision')));

			$tpl->setVariable('LOOP_OBJ_TYPE', utf8_decode($this->lng->txt($bookings['list'][$i]['type'])));
			$tpl->setVariable('LOOP_TITLE',$tmp = utf8_decode($bookings['list'][$i]['title']));
			$tpl->setVariable('LOOP_COUPON', utf8_decode( $assigned_coupons));
			$tpl->setVariable('LOOP_TXT_ENTITLED_RETRIEVE', utf8_decode($this->lng->txt('pay_entitled_retrieve')));
			
		if( $bookings['list'][$i]['duration'] == 0)
		{
			$tpl->setVariable('LOOP_DURATION', utf8_decode($this->lng->txt('unlimited_duration')));
		} 	
		else
			$tpl->setVariable('LOOP_DURATION', $bookings['list'][$i]['duration'] . ' ' . utf8_decode($this->lng->txt('paya_months')));

			#$currency = $bookings['list'][$i]['currency_unit'];
			$tpl->setVariable('LOOP_VAT_RATE', number_format($bookings['list'][$i]['vat_rate'], 2, ',', '.').' %');
			$tpl->setVariable('LOOP_VAT_UNIT', number_format($bookings['list'][$i]['vat_unit'], 2, ',', '.').' '.$currency);
			$tpl->setVariable('LOOP_PRICE', number_format($bookings['list'][$i]['price'], 2, ',', '.').' '.$currency);
			$tpl->setVariable('LOOP_UNIT_PRICE',number_format($bookings['list'][$i]['price'], 2, ',', '.').' '.$currency);
			$tpl->parseCurrentBlock('loop');


			$bookings['total'] += (float)$bookings[$i]['price'];
			$bookings['total_vat']+= (float)$bookings[$i]['vat_unit'];
			#$bookings['total_discount'] +=(float) $bookings[$i]['discount'];
			unset($tmp_pobject);

			$sub_total_amount = $bookings['total'];
		}

		$bookings['total'] += $bookings['total_discount'];
		if($bookings['total_discount'] < 0)
		{
			$tpl->setCurrentBlock('cloop');

			$tpl->setVariable('TXT_SUBTOTAL_AMOUNT', utf8_decode($this->lng->txt('pay_bmf_subtotal_amount')));
			$tpl->setVariable('SUBTOTAL_AMOUNT', number_format($sub_total_amount, 2, ',', '.') . ' ' . $currency);

			$tpl->setVariable('TXT_COUPON', utf8_decode($this->lng->txt('paya_coupons_coupon')));
			$tpl->setVariable('BONUS', number_format($bookings['total_discount'], 2, ',', '.') . ' ' . $currency);
			// TODO: CURRENCY	$tpl->setVariable('BONUS', ilPaymentCurrency::_formatPriceToString($current_coupon_bonus * (-1),$currency_symbol));
			$tpl->parseCurrentBlock();
		}

		if ($bookings['total'] < 0)
		{
			$bookings['total'] = 0.00;
		//	$bookings['total_vat'] = 0.0;
		}
		$total_net_price = $sub_total_amount-$bookings['total_vat'];

		$tpl->setVariable('TXT_TOTAL_NETPRICE', utf8_decode($this->lng->txt('total_netprice')));
		$tpl->setVariable('TOTAL_NETPRICE', number_format($total_net_price, 2, ',', '.') . ' ' . $currency);

		$tpl->setVariable('TXT_TOTAL_AMOUNT', utf8_decode($this->lng->txt('pay_bmf_total_amount')));
		$tpl->setVariable('TOTAL_AMOUNT', number_format($bookings['total'], 2, ',', '.') . ' ' . $currency);
		if ($bookings['total_vat'] > 0)
		{
			$tpl->setVariable('TOTAL_VAT',number_format( $bookings['total_vat'], 2, ',', '.') . ' ' .$currency);
			$tpl->setVariable('TXT_TOTAL_VAT', utf8_decode($this->lng->txt('plus_vat')));
		}

		$tpl->setVariable('TXT_PAYMENT_TYPE', utf8_decode($this->lng->txt('pay_payed_bill')));

		if (!@file_exists($genSet->get('pdf_path')))
		{

			ilUtil::makeDir($genSet->get('pdf_path'));
		}

		$file_name = time();
		if (@file_exists($genSet->get('pdf_path')))
		{		
			ilUtil::html2pdf($tpl->get(), $genSet->get('pdf_path') . '/' . $file_name . '.pdf');
		}

		if (@file_exists($genSet->get('pdf_path') . '/' . $file_name . '.pdf') &&
			$this->user_obj->getEmail() != '' &&
			$this->ilias->getSetting('admin_email') != '')
		{
			$m= new ilMimeMail; // create the mail
			$m->From( $this->ilias->getSetting('admin_email') );
			$m->To( $this->user_obj->getEmail() );
			$m->Subject( $this->lng->txt('pay_message_subject') );	

			// if there is no mailbillingtext use this as standard
			$message = $this->lng->txt('pay_message_hello') . ' ' . $this->user_obj->getFirstname() . ' ' . $this->user_obj->getLastname() . ",\n\n";
			$message .= $this->lng->txt('pay_message_thanks') . "\n\n";
			$message .= $this->lng->txt('pay_message_attachment') . "\n\n";
			$message .= $this->lng->txt('pay_message_regards') . "\n\n";
			$message .= strip_tags($genSet->get('address'));

			//replacePlaceholders...
			$billing_text = $genSet->getMailBillingText();
			if(!$billing_text)
			{
				$message = '';
			}
			if($genSet->getMailUsePlaceholders() == 1)
			{
				include_once './Services/Payment/classes/class.ilBillingMailPlaceholdersPropertyGUI.php';
				$message = ilBillingMailPlaceholdersPropertyGUI::replaceBillingMailPlaceholders($billing_text, $this->user_obj->getId());
			}

			$m->Body( $message );	// set the body
			$m->Attach( $genSet->get('pdf_path') . '/' . $file_name . '.pdf', 'application/pdf' ) ;	// attach a file of type image/gif
			$m->Send();	// send the mail
		}

		@unlink($genSet->get('pdf_path') . '/' . $file_name . '.html');
		@unlink($genSet->get('pdf_path') . '/' . $file_name . '.pdf');
		
		unset($current_booking_id);
		unset($pobject);
		unset($_SESSION['coupons'][$this->session_var]);
	}

	function __emptyShoppingCart()
	{
		include_once './Services/Payment/classes/class.ilPaymentShoppingCart.php';
		
		$sc_obj =& new ilPaymentShoppingCart($this->user_obj);

		return $sc_obj->emptyShoppingCart();
	}
		
	function __clearSession()
	{
		$_SESSION['coupons'][$this->session_var] = '';
		$_SESSION[$this->session_var] = '';
	}

	function __loadTemplate()
	{
		$this->tpl->addBlockFile('CONTENT', 'content', 'tpl.payb_content.html');

		$this->__buildStylesheet();
		$this->__buildStatusline();
	}

	function  __buildStatusline()
	{
		$this->tpl->addBlockFile('STATUSLINE', 'statusline', 'tpl.statusline.html');
#		$this->__buildLocator();
	}

	function __buildLocator()
	{
		$this->tpl->addBlockFile('LOCATOR', 'locator', 'tpl.locator.html');
		$this->tpl->setVariable('TXT_LOCATOR',$this->lng->txt('locator'));

		$this->tpl->setCurrentBlock('locator_item');
		$this->tpl->setVariable('ITEM', $this->lng->txt('personal_desktop'));
		$this->tpl->setVariable('LINK_ITEM','../ilias.php?baseClass=ilPersonalDesktopGUI');
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock('locator_item');
		$this->tpl->setVariable('PREFIX','>&nbsp;');
		$this->tpl->setVariable('ITEM', 'Payment');
		$this->tpl->setVariable('LINK_ITEM', './payment.php');
		$this->tpl->parseCurrentBlock();

		// CHECK for new mail and info
		ilUtil::sendInfo();

		return true;
	}

	function __buildStylesheet()
	{
		$this->tpl->setVariable('LOCATION_STYLESHEET',ilUtil::getStyleSheetLocation());
	}

	/**
	* shows select box f�r countries
	*/
	public function __getCountries()
	{
		global $lng;

		$lng->loadLanguageModule('meta');

		$cntcodes = array ('DE','ES','FR','GB','AT','CH','AF','AL','DZ','AS','AD','AO',
			'AI','AQ','AG','AR','AM','AW','AU','AT','AZ','BS','BH','BD','BB','BY',
			'BE','BZ','BJ','BM','BT','BO','BA','BW','BV','BR','IO','BN','BG','BF',
			'BI','KH','CM','CA','CV','KY','CF','TD','CL','CN','CX','CC','CO','KM',
			'CG','CK','CR','CI','HR','CU','CY','CZ','DK','DJ','DM','DO','TP','EC',
			'EG','SV','GQ','ER','EE','ET','FK','FO','FJ','FI','FR','FX','GF','PF',
			'TF','GA','GM','GE','DE','GH','GI','GR','GL','GD','GP','GU','GT','GN',
			'GW','GY','HT','HM','HN','HU','IS','IN','ID','IR','IQ','IE','IL','IT',
			'JM','JP','JO','KZ','KE','KI','KP','KR','KW','KG','LA','LV','LB','LS',
			'LR','LY','LI','LT','LU','MO','MK','MG','MW','MY','MV','ML','MT','MH',
			'MQ','MR','MU','YT','MX','FM','MD','MC','MN','MS','MA','MZ','MM','NA',
			'NR','NP','NL','AN','NC','NZ','NI','NE','NG','NU','NF','MP','NO','OM',
			'PK','PW','PA','PG','PY','PE','PH','PN','PL','PT','PR','QA','RE','RO',
			'RU','RW','KN','LC','VC','WS','SM','ST','SA','CH','SN','SC','SL','SG',
			'SK','SI','SB','SO','ZA','GS','ES','LK','SH','PM','SD','SR','SJ','SZ',
			'SE','SY','TW','TJ','TZ','TH','TG','TK','TO','TT','TN','TR','TM','TC',
			'TV','UG','UA','AE','GB','UY','US','UM','UZ','VU','VA','VE','VN','VG',
			'VI','WF','EH','YE','ZR','ZM','ZW');
		$cntrs = array();
		foreach($cntcodes as $cntcode)
		{
			$cntrs[$cntcode] = $lng->txt('meta_c_'.$cntcode);
		}
		asort($cntrs);
		return $cntrs;
	}

	public function __getCountryCode($value = '')
	{
		$countries = $this->__getCountries();
		foreach($countries as $code => $text)
		{
			if ($text == $value)
			{
				return $code;
			}
		}
		return;
	}

	public function __getCountryName($value = '')
	{
		$countries = $this->__getCountries();
		return $countries[$value];
	}

	function __getShoppingCart()
	{
		$this->psc_obj = new ilPaymentShoppingCart($this->user_obj);

		if(!count($items = $this->psc_obj->getEntries($this->pm_id)))
		{
			return 0;
		}

		$counter = 0;
		foreach($items as $item)
		{
			$tmp_pobject =& new ilPaymentObject($this->user_obj,$item['pobject_id']);

			$tmp_obj =& ilObjectFactory::getInstanceByRefId($tmp_pobject->getRefId());
					   $f_result[$counter]["object_title"] = $tmp_obj->getTitle();

			$price_arr = ilPaymentPrices::_getPrice($item['price_id']);

			$price = (float) $price_arr['price'];
                        $f_result[$counter]['price'] = $price * 1.0;
			unset($tmp_obj);
			unset($tmp_pobject);

			++$counter;
		}

		return $f_result;
	}

	function __getTotalAmount()
	{
		$amount = 0;

		if (is_array($result = $this->__getShoppingCart()))
		{
			for ($i = 0; $i < count($result); $i++)
			{
				$amount += $result[$i]['price'];
			}
		}
		return $amount;
	}
	
	// if ok, a transaction-id will be generated and the customer gets a bill 
	function getBill()
	{
		if ($_POST['terms_conditions'] != 1)
		{
			$this->error = $this->lng->txt('pay_bmf_check_terms_conditions');
			ilUtil::sendInfo($this->error);
			$this->getPersonalData();
			return;
		}
		if ($_POST['password'] == '' ||
			md5($_POST['password']) != $this->user_obj->getPasswd())
		{
			$this->error = $this->lng->txt('pay_bmf_password_not_valid');
			ilUtil::sendInfo($this->error);
			$this->getPersonalData();
			return;
		}
		$this->error = '';
		ilUtil::sendInfo($this->lng->txt('pay_message_thanks'));
		
		$this->__addBookings();
		
	}
	
	function __showShoppingCart()
	{
		include_once './Services/Payment/classes/class.ilPaymentSettings.php';

		$genSet = ilPaymentSettings::_getInstance();
		
		$this->psc_obj = new ilPaymentShoppingCart($this->user_obj);
		
		if(!count($items = $this->psc_obj->getEntries($this->pm_id)))
		{
			ilUtil::sendInfo($this->lng->txt('pay_shopping_cart_empty'));
		}

		$counter = 0;
		foreach($items as $item)
		{
			$tmp_pobject = new ilPaymentObject($this->user_obj,$item['pobject_id']);

			$tmp_obj = ilObjectFactory::getInstanceByRefId($tmp_pobject->getRefId());

			$price_arr = ilPaymentPrices::_getPrice($item['price_id']);
			
			$assigned_coupons = '';					
			if (!empty($_SESSION['coupons'][$this->session_var]))
			{															
				foreach ($_SESSION['coupons'][$this->session_var] as $key => $coupon)
				{
					$this->coupon_obj->setId($coupon['pc_pk']);
					$this->coupon_obj->setCurrentCoupon($coupon);

					if ($this->coupon_obj->isObjectAssignedToCoupon($tmp_pobject->getRefId()))
					{
						$assigned_coupons .= '<br />' . $this->lng->txt('paya_coupons_coupon') . ': ' . $coupon['pcc_code'];						
					}
				}
			}
			$f_result[$counter]['item'] = '';
			$f_result[$counter]['title'] = $tmp_obj->getTitle();
			if ($assigned_coupons != '') $f_result[$counter][count($f_result[$counter]) - 1] .= $assigned_coupons;
		
			if($price_arr['duration'] == 0)
			{
				$f_result[$counter]['duration'] = $this->lng->txt('unlimited_duration');
			}
			else
			{
				$f_result[$counter]['duration'] = $price_arr['duration'] . ' ' . $this->lng->txt('paya_months');
			}
			
			$oVAT = new ilShopVats((int)$tmp_pobject->getVatId());
		    $f_result[$counter]['vat_rate'] = ilShopUtils::_formatVAT($oVAT->getRate());
		
		    $float_price = $price_arr['price'];
		
		    $currency = ilPaymentCurrency::_getUnit($price_arr['currency']);
		    $f_result[$counter]['vat_unit'] = $tmp_pobject->getVat($float_price, 'GUI').' '.$genSet->get('currency_unit');
		    $this->totalVat = $this->totalVat + $tmp_pobject->getVat($float_price);			
			
			$f_result[$counter]['price'] = number_format($float_price, 2, ',', '.') .' '.$genSet->get('currency_unit');

			unset($tmp_obj);
			unset($tmp_pobject);

			++$counter;
		}

		return $this->__showItemsTable($f_result);
	}

	private function __showItemsTable($a_result_set)
	{
		include_once './Services/Payment/classes/class.ilPaymentSettings.php';
		
		$genSet = ilPaymentSettings::_getInstance();
		include_once './Services/Payment/classes/class.ilShoppingCartTableGUI.php';

		$tbl = new ilShoppingCartTableGUI($this);
		$tbl->setId('tbl_id_'.$this->session_var);
		$tbl->setTitle($this->lng->txt('paya_shopping_cart'));
		/*
				" (".$this->lng->txt('payment_system').": ".
				ilPayMethods::getStringByPaymethod($a_pay_method['pm_title']) .")");
		*/
		$tbl->setRowTemplate("tpl.shop_shoppingcart_row.html", "Services/Payment");
		$tbl->addColumn('','item','1%');
		$tbl->addColumn($this->lng->txt('title'), 'title', '30%');
		$tbl->addColumn($this->lng->txt('duration'),'duration', '30%');
		$tbl->addColumn($this->lng->txt('vat_rate'), 'vat_rate', '15%');
		$tbl->addColumn($this->lng->txt('vat_unit'), 'vat_unit', '15%');
		$tbl->addColumn($this->lng->txt('price_a'), 'price', '10%');
		$tbl->disable('sort');

		#$tbl->setPrefix("table". $a_pay_method['pm_title']."_");
		
		// show total amount of costs
		$sc_obj = new ilPaymentShoppingCart($this->user_obj);
		$totalAmount =  $sc_obj->getTotalAmount();

		if (!empty($_SESSION['coupons'][$this->session_var]))
		{
			if (count($items = $sc_obj->getEntries($this->pm_id)))
			{
				$tbl->setTotalData('TXT_SUB_TOTAL', $this->lng->txt('pay_bmf_subtotal_amount') . ": ");
				$tbl->setTotalData('VAL_SUB_TOTAL', number_format($totalAmount[$this->pm_id], 2, ',', '.') . " " . $genSet->get('currency_unit'));
				#$tbl->setTotalData('VAL_SUB_TOTAL',ilPaymentPrices::_formatPriceToString($totalAmount[$a_pay_method['pm_id']], (int)$this->default_currency['currency_id'] ));

				$totalAmount[$current_coupon_bonus] = 0;
				foreach ($_SESSION['coupons'][$this->session_var] as $coupon)
				{
					$this->coupon_obj->setId($coupon['pc_pk']);
					$this->coupon_obj->setCurrentCoupon($coupon);

					$total_object_price = 0.0;
					$current_coupon_bonus = 0.0;

					foreach ($items as $item)
					{
						$tmp_pobject = new ilPaymentObject($this->user_obj, $item['pobject_id']);

						if ($this->coupon_obj->isObjectAssignedToCoupon($tmp_pobject->getRefId()))
						{
							$price_data = ilPaymentPrices::_getPrice($item['price_id']);
							$price = (float) $price_data['price'];

							$total_object_price += $price;
						}
						unset($tmp_pobject);
					}
					$current_coupon_bonus = $this->coupon_obj->getCouponBonus($total_object_price);
					$totalAmount[$current_coupon_bonus] += $current_coupon_bonus * (-1);
				}
					$tbl->setTotalData('TXT_COUPON_BONUS', $this->lng->txt('paya_coupons_coupon') . ": ");# . $coupon['pcc_code'] . ": ");
					$tbl->setTotalData('VAL_COUPON_BONUS', number_format($totalAmount[$current_coupon_bonus], 2, ',', '.') . " " . $genSet->get('currency_unit'));


				if ($totalAmount[$this->pm_id] < 0)
				{
					$totalAmount[$this->pm_id] = 0;
					$this->totalVat = 0;
				}
			}
		}

		$this->totalAmount[$this->pm_id] = $totalAmount[$this->pm_id]-($totalAmount[$current_coupon_bonus] * (-1));

		$tbl->setTotalData('TXT_TOTAL_AMOUNT', $this->lng->txt('pay_bmf_total_amount').": ");
		$tbl->setTotalData('VAL_TOTAL_AMOUNT',  number_format($this->totalAmount[$this->pm_id] , 2, ',', '.') . " " . $genSet->get('currency_unit')); #.$item['currency']);

		// TODO: CURRENCY
		#$currency_conversion_totalvat = (float)$_SESSION['currency_conversion'][$a_pay_method['pm_title']]['total_vat'];
		#if($currency_conversion_totalvat > 0) $this->totalVat = $currency_conversion_totalvat;

		if ($this->totalVat > 0)
		{
			$tbl->setTotalData('TXT_TOTAL_VAT', $this->lng->txt('pay_bmf_vat_included') . ": ");
			$tbl->setTotalData('VAL_TOTAL_VAT',  number_format($this->totalVat , 2, ',', '.') . " " . $genSet->get('currency_unit'));
		}

		$tbl->setData($a_result_set);
		$this->tpl->setVariable('ITEMS_TABLE',$tbl->getCartHTML());

		return true;
	}
}
?>