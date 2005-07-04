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
* Adapter class for communication between ilias and ilRPCServer
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
*
* @package ilias
*/
include_once 'Services/WebServices/RPC/classes/class.ilRPCServerAdapter.php';

class ilLuceneRPCAdapter extends ilRPCServerAdapter
{
	var $mode = '';
	var $files = array();
	var $query_str = '';
	var $filter = '';


	function ilLuceneRPCAdapter()
	{
		parent::ilRPCServerAdapter();
	}

	function setMode($a_mode)
	{
		$this->mode = $a_mode;
	}
	function getMode()
	{
		return $this->mode;
	}
	function setFiles(&$files)
	{
		$this->files =& $files;
	}
	function &getFiles()
	{
		return $this->files ? $this->files : array();
	}
	function setHTLMs(&$htlms)
	{
		$this->htlms = $htlms;
	}
	function &getHTLMs()
	{
		return $this->htlms;
	}

	function setQueryString($a_str)
	{
		$this->query_str = $a_str;
	}
	function getQueryString()
	{
		return $this->query_str;
	}
	
	function setSearchFilter($a_filter)
	{
		$this->filter = $a_filter;
	}
	function getSearchFilter()
	{
		return $this->filter ? $this->filter : array();
	}

	/**
	 * Create a unique client id. Since the lucene index can be used from multiple ILIAS-Installations it must be unique over installations
	 *
	 * @return string client_identifier
	 */
	function __getClientId()
	{
		global $ilias;

		// TODO: handle problem if nic_key isn't set
		return $ilias->getSetting('nic_key').'_'.CLIENT_ID;
	}



	function send()
	{
		$this->__initClient();
		switch($this->getMode())
		{
			case 'ping':
				$this->__preparePingParams();
				break;

			case 'file':
				$this->__prepareIndexFileParams();
				break;

			case 'query':
				$this->__prepareQueryParams();
				break;

			case 'htlm':
				$this->__prepareIndexHTLMParams();
				break;

			case 'flush':
				$this->__prepareFlushIndex();
				break;

			default:
				$this->log->write('ilLuceneRPCHandler(): No valid mode given');
				return false;

		}
		return parent::send();
	}
	// PRIVATE
	function __prepareQueryParams()
	{
		$filter = array();
		foreach($this->getSearchFilter() as $obj_type)
		{
			$filter[] = new XML_RPC_Value($obj_type,'string');
		}
		$this->__initMessage('Searcher.ilSearch',array(new XML_RPC_Value($this->__getClientId(),"string"),
													   new XML_RPC_Value($this->getQueryString(),"string"),
													   new XML_RPC_Value($filter,'array')));

		return true;
	}

	function __preparePingParams()
	{
		$this->__initMessage('Searcher.ilPing',array(new XML_RPC_Value($this->__getClientId(),"string")));

		return true;
	}

	function __prepareIndexFileParams()
	{
		foreach($this->getFiles() as $obj_id => $fname)
		{
			$struct[$obj_id] = new XML_RPC_Value($fname,"string");
		}
		$params = array(new XML_RPC_Value($this->__getClientId(),"string"),
						new XML_RPC_Value($struct,"struct"));

		$this->__initMessage('Indexer.ilFileIndexer',$params);

		return true;
	}

	function __prepareIndexHTLMParams()
	{
		foreach($this->getHTLMs() as $obj_id => $fname)
		{
			$struct[$obj_id] = new XML_RPC_Value($fname,"string");
		}

		$this->__initMessage('Indexer.ilHTLMIndexer',array(new XML_RPC_Value($this->__getClientId(),"string"),
														   new XML_RPC_Value($struct,"struct")));

		return true;
	}
	function __prepareFlushIndex()
	{

		$this->__initMessage('Indexer.ilClearIndex',array(new XML_RPC_Value($this->__getClientId(),"string")));

		return true;
	}
}
?>
