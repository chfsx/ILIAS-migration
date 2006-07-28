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
* Class ilPaymentObject
* 
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
*
* @package ilias-core
*/

define ("PAY_METHOD_NOT_SPECIFIED", 0);
define ("PAY_METHOD_BILL", 1);
define ("PAY_METHOD_BMF", 2);
define ("PAY_METHOD_PAYPAL", 3);

class ilPaymentObject
{
	var $db = null;
	var $user_obj = null;
	var $pobject_id = null;

	var $ref_id = null;
	var $status = null;
	var $pay_method = null;
	var $vendor_id = null;

	function ilPaymentObject(&$user_obj,$a_pobject_id = null)
	{
		global $ilDB;

		$this->db =& $ilDB;
		$this->user_obj =& $user_obj;

		$this->STATUS_NOT_BUYABLE = 0;
		$this->STATUS_BUYABLE = 1;
		$this->STATUS_EXPIRES = 2;

		$this->PAY_METHOD_NOT_SPECIFIED = PAY_METHOD_NOT_SPECIFIED;
		$this->PAY_METHOD_BILL = PAY_METHOD_BILL;
		$this->PAY_METHOD_BMF = PAY_METHOD_BMF;
		$this->PAY_METHOD_PAYPAL = PAY_METHOD_PAYPAL;
		
		$this->pobject_id = $a_pobject_id;
		$this->__read();
	}

	// SETTER GETTER
	function getPobjectId()
	{
		return $this->pobject_id;
	}

	function setRefId($a_ref_id)
	{
		$this->ref_id = $a_ref_id;
	}
	function getRefId()
	{
		return $this->ref_id;
	}
	function setStatus($a_status)
	{
		$this->status = $a_status;
	}
	function getStatus()
	{
		return $this->status;
	}
	function setPayMethod($a_method)
	{
		$this->pay_method = $a_method;
	}
	function getPayMethod()
	{
		return $this->pay_method;
	}
	function setVendorId($a_vendor_id)
	{
		$this->vendor_id= $a_vendor_id;
	}
	function getVendorId()
	{
		return $this->vendor_id;
	}

	

	// return new unique id
	function add()
	{
		$query = "INSERT INTO payment_objects ".
			"VALUES('','".
			$this->getRefId()."','".
			$this->getStatus()."',' ".
			$this->getPayMethod()."',' ".
			$this->getVendorId()."')";

		$this->db->query($query);

		$query = "SELECT LAST_INSERT_ID() as new_id";

		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->new_id;
		}
		return false;
	}
	function delete()
	{
		if($this->getPobjectId())
		{
			$query = "DELETE FROM payment_objects ".
				"WHERE pobject_id = '".$this->getPobjectId()."'";
			
			$this->db->query($query);
			
			return true;
		}
		return false;
	}

	function update()
	{
		$query = "UPDATE payment_objects ".
			"SET ref_id = '".$this->getRefId()."', ".
			"status = '".$this->getStatus()."', ".
			"pay_method = '".$this->getPayMethod()."', ".
			"vendor_id = '".$this->getVendorId()."' ".
			"WHERE pobject_id = '".$this->getPobjectId()."'";

		$this->db->query($query);

		return true;
	}
	// STATIC
	function _lookupPobjectId($a_ref_id)
	{
		global $ilDB;

		$query = "SELECT * FROM payment_objects ".
			"WHERE ref_id = '".$a_ref_id."'";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->pobject_id;
		}
		return 0;
	}

	function _getCountObjectsByPayMethod($a_type)
	{
		global $ilDB;

		switch($a_type)
		{
			case 'pm_bill':
				$pm = 1;
				break;

			case 'pm_bmf':
				$pm = 2;
				break;

			case 'pm_paypal':
				$pm = 3;
				break;

			default:
				$pm = -1;
		}
		
		$query = 'SELECT count(pay_method) as pm FROM payment_objects '.
			"WHERE pay_method = '".$pm."'";

		$res = $ilDB->query($query);

		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->pm;
		}
		return 0;
	}
		

	// only called from payment settings object. Since there is no vendor check.
	function _getAllObjectsData()
	{
		;
	}

	function _getObjectsData($a_user_id)
	{
		global $ilDB;

		// get all vendors user is assigned to
		include_once './payment/classes/class.ilPaymentTrustees.php';
		include_once './payment/classes/class.ilPaymentVendors.php';

		$vendors = ilPaymentTrustees::_getVendorsForObjects($a_user_id);

		if(ilPaymentVendors::_isVendor($a_user_id))
		{
			$vendors[] = $a_user_id;
		}

		if(!count($vendors))
		{
			return array();
		}
		$in = " IN ('";
		$in .= implode("','",$vendors);
		$in .= "')";

		$query = "SELECT * FROM payment_objects ".
			"WHERE vendor_id ".$in;

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$objects[$row->pobject_id]['pobject_id'] = $row->pobject_id;
			$objects[$row->pobject_id]['ref_id'] = $row->ref_id;
			$objects[$row->pobject_id]['status'] = $row->status;
			$objects[$row->pobject_id]['pay_method'] = $row->pay_method;
			$objects[$row->pobject_id]['vendor_id'] = $row->vendor_id;
		}
		return $objects ? $objects : array();
	}

	function _getObjectData($a_id)
	{
		global $ilDB;

		$query = "SELECT * FROM payment_objects ".
			"WHERE pobject_id = '".$a_id."'";

		$res = $ilDB->query($query);

		if (is_object($res))
		{
			return $res->fetchRow(DB_FETCHMODE_ASSOC);
		}

		return false;
	}

	function _isPurchasable($a_ref_id)
	{
		global $ilDB;

		// In the moment it's not possible to sell one object twice
		$query = "SELECT * FROM payment_objects ".
			"WHERE ref_id = '".$a_ref_id."'";
		#"AND status = '1' OR status = '3' ";
		
		$res = $ilDB->query($query);

		return $res->numRows() ? false : true;
	}

	// base method to check access for a specific object
	function _hasAccess($a_ref_id)
	{
		include_once './payment/classes/class.ilPaymentBookings.php';

		global $rbacsystem,$ilDB;

		// check write access
		if($rbacsystem->checkAccess('write',$a_ref_id))
		{
			return true;
		}
		$query = "SELECT * FROM payment_objects ".
			"WHERE ref_id = '".$a_ref_id."' ".
			"AND (status = '1' OR status = '2')";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if(!ilPaymentBookings::_hasAccess($row->pobject_id))
			{
				return false;
			}
		}
		return true;
	}
	// base method to check access for a specific object
	function _getActivation($a_ref_id)
	{
		include_once './payment/classes/class.ilPaymentBookings.php';

		global $rbacsystem,$ilDB;

		$query = "SELECT * FROM payment_objects ".
			"WHERE ref_id = '".$a_ref_id."' ".
			"AND (status = '1' OR status = '2')";

		$res = $ilDB->query($query);
		$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
		return ilPaymentBookings::_getActivation($row->pobject_id);
	}
	function _isBuyable($a_ref_id)
	{
		global $ilDB;

		$query = "SELECT * FROM payment_objects ".
			"WHERE ref_id = '".$a_ref_id."' ".
			"AND (status = 1 or status = 2)";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return true;
		}
		return false;
	}
	function _isInCart($a_ref_id)
	{
		global $ilDB, $ilUser;

		$query = "SELECT psc_id FROM payment_objects AS po, payment_shopping_cart AS psc ".
			"WHERE ref_id = '".$a_ref_id."' ".
			"AND customer_id = '".$ilUser->getId()."' ".
			"AND po.pobject_id = psc.pobject_id";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return true;
		}
		return false;
	}
		
	// PRIVATE
	function __read()
	{
		if($this->getPobjectId())
		{
			$query = "SELECT * FROM payment_objects ".
				"WHERE pobject_id = '".$this->getPobjectId()."'";

			$res = $this->db->query($query);
			while($row =& $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$this->setRefId($row->ref_id);
				$this->setStatus($row->status);
				$this->setPayMethod($row->pay_method);
				$this->setVendorId($row->vendor_id);
				
				return true;
			}
		}
		return false;
	}
				

} // END class.ilPaymentObject
?>
