<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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
* 
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* 
* @ilCtrl_Calls 
* @ingroup ServicesWebServicesECS 
*/

include_once('Services/WebServices/ECS/classes/class.ilECSSettings.php');
include_once('Services/WebServices/ECS/classes/class.ilECSResult.php');
include_once('Services/WebServices/Curl/classes/class.ilCurlConnection.php');

class ilECSConnector
{
	protected $path_postfix = '';
	
	protected $settings;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function __construct()
	{
	 	$this->settings = ilECSSettings::_getInstance();
	}
	
	
	/**
	 * Get resources from ECS server.
	 * 
	 * 
	 *
	 * @access public
	 * @param int e-content id
	 * @return object ECSResult
	 * @throws 
	 */
	public function getResources($a_econtent_id = 0)
	{
	 	$this->path_postfix = '/econtents';
	 	if($a_econtent_id)
	 	{
	 		$this->path_postfix .= ('/'.(int) $a_econtent_id);
	 	}
	 	
	 	try 
	 	{
	 		$this->prepareConnection();
			$res = $this->call();
			
			return new ilECSResult($res);
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	}
	
	/**
	 * prepare connection
	 *
	 * @access private
	 * @throws ilCurlConnectionException
	 */
	private function prepareConnection()
	{
	 	try
	 	{
	 		$this->curl = new ilCurlConnection($this->settings->getServerURI().$this->path_postfix);
 			$this->curl->init();
	 		if($this->settings->getProtocol() == ilECSSettings::PROTOCOL_HTTPS)
	 		{
	 			$this->curl->setOpt(CURLOPT_HTTPHEADER,array(0 => 'Accept: application/json'));
	 			$this->curl->setOpt(CURLOPT_SSL_VERIFYPEER,0);
	 			$this->curl->setOpt(CURLOPT_SSL_VERIFYHOST,1);
	 			$this->curl->setOpt(CURLOPT_VERBOSE,1);
	 			$this->curl->setOpt(CURLOPT_RETURNTRANSFER,true);
	 			$this->curl->setOpt(CURLE_SSL_PEER_CERTIFICATE,$this->settings->getCACertPath());
	 			$this->curl->setOpt(CURLOPT_SSLCERT,$this->settings->getClientCertPath());
	 			$this->curl->setOpt(CURLOPT_SSLKEY,'/home/smeyer/certs/ecs/databay-key.pem');
	 			$this->curl->setOpt(CURLOPT_SSLKEYPASSWD,'******');
				
	 		}
	 	}
		catch(ilCurlConnectionException $exc)
		{
			throw($exc);
		}
	}
	
	/**
	 * call peer
	 *
	 * @access private
	 * @throws ilCurlConnectionException 
	 */
	private function call()
	{
 		try
 		{
 			$res = $this->curl->exec();
 			return $res;
 		}	 	
		catch(ilCurlConnectionException $exc)
		{
			throw($exc);
		}
	}
}


?>