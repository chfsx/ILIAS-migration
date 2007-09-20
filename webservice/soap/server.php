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

chdir("../..");
define ("ILIAS_MODULE", "webservice/soap");
define ("IL_SOAPMODE_NUSOAP", 0);
define ("IL_SOAPMODE_INTERNAL", 1);

define ("IL_SOAPMODE", IL_SOAPMODE_INTERNAL);

if (IL_SOAPMODE == IL_SOAPMODE_INTERNAL) {
	//ini_set("soap.wsdl_cache_enabled", "1"); 
	include_once('webservice/soap/include/inc.soap_functions.php');
	$soapServer = new SoapServer("webservice/soap/server.wsdl");
	$soapServer->setClass("ilSoapFunctions");
	$soapServer->handle();
} else {
	global $HTTP_RAW_POST_DATA;
	$HTTP_RAW_POST_DATA = file_get_contents("php://input");
	include_once './webservice/soap/classes/class.ilNusoapUserAdministrationAdapter.php';
	$server =& new ilNusoapUserAdministrationAdapter(true);
	$server->start();
	break;
}

?>