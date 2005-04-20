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
* Meta Data class (element meta_data)
*
* @package ilias-core
* @version $Id$
*/
include_once 'class.ilMDBase.php';

class ilMDMetaMetadata extends ilMDBase
{
	var $parent_obj = null;

	function ilMDMetaMetadata(&$parent_obj,$a_id = null)
	{
		$this->parent_obj =& $parent_obj;

		parent::ilMDBase($this->parent_obj->getRBACId(),
						 $this->parent_obj->getObjId(),
						 $this->parent_obj->getObjType(),
						 'meta_meta_data',
						 $a_id);

		if($a_id)
		{
			$this->read();
		}
	}

	// SUBELEMENTS
	function &getIdentifierIds()
	{
		include_once 'Services/MetaData/classes/class.ilMDIdentifier.php';

		return ilMDIdentifier::_getIds($this->getRBACId(),$this->getObjId(),$this->getMetaId(),$this->getMetaType());
	}
	function &getIdentifier($a_identifier_id)
	{
		include_once 'Services/MetaData/classes/class.ilMDIdentifier.php';
		
		if(!$a_identifier_id)
		{
			return false;
		}
		return new ilMDIdentifier($this,$a_identifier_id);
	}
	function &addIdentifier()
	{
		include_once 'Services/MetaData/classes/class.ilMDIdentifier.php';

		return new ilMDIdentifier($this);
	}
	
	function &getContributeIds()
	{
		include_once 'Services/MetaData/classes/class.ilMDContribute.php';

		return ilMDContribute::_getIds($this->getRBACId(),$this->getObjId(),$this->getMetaId(),$this->getMetaType());
	}
	function &getContribute($a_contribute_id)
	{
		include_once 'Services/MetaData/classes/class.ilMDContribute.php';
		
		if(!$a_contribute_id)
		{
			return false;
		}
		return new ilMDContribute($this,$a_contribute_id);
	}
	function &addContribute()
	{
		include_once 'Services/MetaData/classes/class.ilMDContribute.php';

		return new ilMDContribute($this);
	}



	// SET/GET
	function setMetaDataScheme($a_val)
	{
		$this->meta_data_scheme = $a_val;
	}
	function getMetaDataScheme()
	{
		return $this->meta_data_scheme;
	}
	function setLanguage(&$lng_obj)
	{
		if(is_object($lng_obj))
		{
			$this->language = $lng_obj;
		}
	}
	function &getLanguage()
	{
		return is_object($this->language) ? $this->language : false;
	}
	function getLanguageCode()
	{
		return is_object($this->language) ? $this->language->getLanguageCode() : false;
	}
	

	function save()
	{
		if($this->db->autoExecute('il_meta_meta_data',
								  $this->__getFields(),
								  DB_AUTOQUERY_INSERT))
		{
			$this->setMetaId($this->db->getLastInsertId());

			return $this->getMetaId();
		}
		return false;
	}

	function update()
	{
		if($this->getMetaId())
		{
			if($this->db->autoExecute('il_meta_meta_data',
									  $this->__getFields(),
									  DB_AUTOQUERY_UPDATE,
									  "meta_meta_data_id = '".$this->getMetaId()."'"))
			{
				return true;
			}
		}
		return false;
	}

	function delete()
	{
		if($this->getMetaId())
		{
			$query = "DELETE FROM il_meta_meta_data ".
				"WHERE meta_meta_data_id = '".$this->getMetaId()."'";
			
			$this->db->query($query);
			
			return true;
		}
		return false;
	}
			

	function __getFields()
	{
		return array('rbac_id'	=> $this->getRBACId(),
					 'obj_id'	=> $this->getObjId(),
					 'obj_type'	=> ilUtil::prepareDBString($this->getObjType()),
					 'meta_data_scheme'	=> ilUtil::prepareDBString($this->getMetaDataScheme()),
					 'language' => ilUtil::prepareDBString($this->getLanguageCode()));
	}

	function read()
	{
		include_once 'Services/MetaData/classes/class.ilMDLanguageItem.php';


		if($this->getMetaId())
		{

			$query = "SELECT * FROM il_meta_meta_data ".
				"WHERE meta_meta_data_id = '".$this->getMetaId()."'";

		
			$res = $this->db->query($query);
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$this->setMetaDataScheme(ilUtil::stripSlashes($row->meta_data_scheme));
				$this->setLanguage(new ilMDLanguageItem($row->language));
			}
			return true;
		}
		return false;
	}
				
	/*
	 * XML Export of all meta data
	 * @param object (xml writer) see class.ilMD2XML.php
	 * 
	 */
	function toXML(&$writer)
	{
		if($this->getMetaDataScheme())
		{
			$attr['Metadata-Scheme'] = $this->getMetaDataScheme();
		}
		if($this->getLanguageCode())
		{
			$attr['Language'] = $this->getLanguageCode();
		}
		$writer->xmlStartTag('Meta-Metadata',$attr ? $attr : null);

		// ELEMENT IDENTIFIER
		foreach($this->getIdentifierIds() as $id)
		{
			$ide =& $this->getIdentifier($id);
			$ide->toXML($writer);
		}
		
		// ELEMETN Contribute
		foreach($this->getContributeIds() as $id)
		{
			$con =& $this->getContribute($id);
			$con->toXML($writer);
		}

		$writer->xmlEndTag('Meta-Metadata');
	}

	// STATIC
	function _getId($a_rbac_id,$a_obj_id)
	{
		global $ilDB;

		$query = "SELECT meta_meta_data_id FROM il_meta_meta_data ".
			"WHERE rbac_id = '".$a_rbac_id."' ".
			"AND obj_id = '".$a_obj_id."'";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->meta_meta_data_id;
		}
		return false;
	}
}
?>