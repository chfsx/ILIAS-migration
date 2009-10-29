<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
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


chdir(dirname(__FILE__));
chdir('../../..');

require_once 'Services/Authentication/classes/class.ilAuthFactory.php';
ilAuthFactory::setContext(ilAuthFactory::CONTEXT_CRON);

$usr_id = $_REQUEST['ilUser'];

try
{
  include_once './include/inc.header.php';
  include_once './payment/classes/class.ilPaymentObject.php';
  include_once './payment/classes/class.ilPaymentBookings.php';
  require_once './payment/classes/class.ilPaymentShoppingCart.php';
  require_once './Services/User/classes/class.ilObjUser.php';

  global $ilias;

  require_once './Services/Payment/classes/class.ilERP.php';
  $active = ilERP::getActive();
  $cls = "ilERPDebtor_" . $active['erp_short']; 
  include_once './Services/Payment/classes/class.' . $cls. '.php';

  $ilUser = new ilObjUser($usr_id);
  $cart = new ilPaymentShoppingCart($ilUser);
  $sc = $cart->getShoppingCart(PAY_METHOD_EPAY);
  $deb = new $cls();

  if (!$deb->getDebtorByNumber($usr_id))
  {
    $deb->setAll( array(
      'number' => $usr_id,
      'name' => $ilUser->getFullName(),
      'email' => $ilUser->email,
      'address' => $ilUser->street,
      'postalcode' => $ilUser->zipcode,
      'city' => $ilUser->city,
      'country' => $ilUser->country,
      'phone' => $ilUser->phone_mobile)
    );
    $deb->createDebtor($usr_id);
  }  
  $deb->createInvoice();  
  $products = array();
  foreach ($sc as $i)
  {    
    $pod = ilPaymentObject::_getObjectData($i['pobject_id']);
    $bo  =& new ilPaymentBookings($ilUser->getId());
    
    $product_name = $i['buchungstext'];
    $duration = $i['dauer'];
    $amount = $i['betrag'];
    
    // psc_id, pobject_id, obj_id, typ, betrag_string
    
    if (!($bo->getPayedStatus()) && ($bo->getAccessStatus()))
    {    
      $bo->setPayed(1);
      $bo->setAccess(1);
      $bo->update();
    }
    if ( $i['typ'] == 'crs')
    {
      include_once './Modules/Course/classes/class.ilCourseParticipants.php';
      $deb->createInvoiceLine( 0, $product_name . " (" . $duration. ")", 1, $amount );      
      $products[] = $product_name;
      $obj_id = ilObject::_lookupObjId($pod["ref_id"]);    
      $cp = ilCourseParticipants::_getInstanceByObjId($obj_id); 
      $cp->add($usr_id, IL_CRS_MEMBER);
      $cp->sendNotification($cp->NOTIFY_ACCEPT_SUBSCRIBER, $usr_id);
    }
  }
    
  $inv = $deb->bookInvoice();
  $invoice_number = $deb->getInvoiceNumber();

  $attach = $deb->getInvoicePDF($inv);
  $deb->saveInvoice($attach, false);
  $lng->loadLanguageModule('payment');
  $deb->sendInvoice($lng->txt('pay_order_paid_subject'), 
        $ilUser->getFullName() . ",\n" . 
          str_replace( '%products%', implode(", ", $products), $lng->txt('pay_order_paid_body')) , 
        $ilUser->getEmail(), 
        $attach, $lng->txt('pays_invoice') ."-" . $invoice_number
  );
  $cart->emptyShoppingCart();
}
catch (Exception $e)
{  
  die($e->getMessage());
}
?>