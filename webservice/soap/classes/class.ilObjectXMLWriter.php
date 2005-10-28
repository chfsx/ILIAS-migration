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
* XML writer class
*
* Class to simplify manual writing of xml documents.
* It only supports writing xml sequentially, because the xml document
* is saved in a string with no additional structure information.
* The author is responsible for well-formedness and validity
* of the xml document.
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
*/

include_once "./classes/class.ilXmlWriter.php";

class ilObjectXMLWriter extends ilXmlWriter
{
	var $ilias;

	var $xml;
	var $enable_operations = false;
	var $objects = array();
	var $user_id = 0;

	/**
	* constructor
	* @param	string	xml version
	* @param	string	output encoding
	* @param	string	input encoding
	* @access	public
	*/
	function ilObjectXMLWriter()
	{
		global $ilias;

		parent::ilXmlWriter();

		$this->ilias =& $ilias;
	}

	function setUserId($a_id)
	{
		$this->user_id = $a_id;
	}
	function getUserId()
	{
		return $this->user_id;
	}

	function enableOperations($a_status)
	{
		$this->enable_operations = $a_status;
		
		return true;
	}

	function enabledOperations()
	{
		return $this->enable_operations;
	}

	function setObjects($objects)
	{
		$this->objects = $objects;
	}

	function __getObjects()
	{
		return $this->objects ? $this->objects : array();
	}

	function start()
	{
		if(!count($objects = $this->__getObjects()))
		{
			return false;
		}

		$this->__buildHeader();

		foreach($this->__getObjects() as $object)
		{
			$this->__appendObject($object);
		}
		$this->__buildFooter();

		return true;
	}

	function getXML()
	{
		return $this->xmlDumpMem();
	}


	// PRIVATE
	function __appendObject(&$object)
	{
		$this->xmlStartTag('Object',
						   array('type' => $object->getType(),
								 'obj_id' => $object->getId()));
		$this->xmlElement('Title',null,$object->getTitle());
		$this->xmlElement('Description',null,$object->getDescription());
		$this->xmlElement('Owner',null,$object->getOwner());
		$this->xmlElement('CreateDate',null,$object->getCreateDate());
		$this->xmlElement('LastUpdate',null,$object->getLastUpdateDate());
		$this->xmlElement('ImportId',null,$object->getImportId());
		
		foreach(ilObject::_getAllReferences($object->getId()) as $ref_id)
		{
			$this->xmlStartTag('References',array('ref_id' => $ref_id));
			$this->__appendOperations($ref_id,$object->getType());
			$this->xmlEndTag('References');
		}
		$this->xmlEndTag('Object');
	}

	function __appendOperations($a_ref_id,$a_type)
	{
		global $ilAccess,$rbacreview;

		if($this->enabledOperations())
		{
			foreach($rbacreview->getOperationsOnTypeString($a_type) as $ops_id)
			{
				$operation = $rbacreview->getOperation($ops_id);

				if($ilAccess->checkAccessOfUser($this->getUserId(),$operation['operation'],'view',$a_ref_id))
				{
					$this->xmlElement('Operation',null,$operation['operation']);
				}
			}
		}
		return true;
	}
	

	function __buildHeader()
	{
		$this->xmlSetDtdDef("<!DOCTYPE Objects SYSTEM \"http://www.ilias.uni-koeln.de/download/dtd/ilias_object_0_1.dtd\">");
		$this->xmlSetGenCmt("Export of ILIAS objects");
		$this->xmlHeader();

		$this->xmlStartTag("Objects");

		return true;
	}

	function __buildFooter()
	{
		$this->xmlEndTag('Objects');
	}
}


?>
