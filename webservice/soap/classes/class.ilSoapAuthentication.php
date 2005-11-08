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
* soap server
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
*
* @package ilias
*/
include_once 'Auth/Auth.php';
include_once './classes/class.ilBaseAuthentication.php';

class ilSoapAuthentication extends ilBaseAuthentication
{
	function ilSoapAuthentication()
	{
		// First unset all cookie inforamtions
		unset($_COOKIE['PHPSESSID']);

		parent::ilBaseAuthentication();
		$this->__setMessageCode('Client');
	}

	function authenticate()
	{
		if(!$this->getClient())
		{
			$this->__setMessage('No client given');
			return false;
		}
		if(!$this->getUsername())
		{
			$this->__setMessage('No username given');
			return false;
		}
		// Read ilias ini
		if(!$this->__buildAuth())
		{
			return false;
		}
		if(!$this->__setSessionSaveHandler())
		{
			return false;
		}
		if(!$this->__checkSOAPEnabled())
		{
			$this->__setMessage('SOAP is not enabled in ILIAS administration for this client');
			$this->__setMessageCode('Server');

			return false;
		}


		$this->auth->start();

		if(!$this->auth->getAuth())
		{
			$this->__getAuthStatus();

			return false;
		}			

		$this->setSid(session_id());

		return true;
	}

	
	function validateSession()
	{
		if(!$this->getClient())
		{
			$this->__setMessage('No client given');
			return false;
		}
		if(!$this->getSid())
		{
			$this->__setMessage('No session id given');
			return false;
		}
		
		if(!$this->__buildAuth())
		{
			return false;
		}
		if(!$this->__setSessionSaveHandler())
		{
			return false;
		}
		if(!$this->__checkSOAPEnabled())
		{
			$this->__setMessage('SOAP is not enabled in ILIAS administration for this client');
			$this->__setMessageCode('Server');

			return false;
		}
		$this->auth->start();
		if(!$this->auth->getAuth())
		{
			$this->__setMessage('Session not valid');
			
			return false;
		}
		
		return true;
	}

	// PRIVATE
	function __checkSOAPEnabled()
	{
		include_once './classes/class.ilDBx.php';

		
		$db =& new ilDBx($this->dsn);

		$query = "SELECT * FROM settings WHERE keyword = 'soap_user_administration' AND value = 1";

		$res = $db->query($query);

		return $res->numRows() ? true : false;
	}
}
?>
