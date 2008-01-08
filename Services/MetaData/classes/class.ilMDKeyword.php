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
* Meta Data class (element keyword)
*
* @package ilias-core
* @version $Id$
*/
include_once 'class.ilMDBase.php';

class ilMDKeyword extends ilMDBase
{
	function ilMDKeyword($a_rbac_id = 0,$a_obj_id = 0,$a_obj_type = '')
	{
		parent::ilMDBase($a_rbac_id,
						 $a_obj_id,
						 $a_obj_type);
	}

	// SET/GET
	function setKeyword($a_keyword)
	{
		$this->keyword = $a_keyword;
	}
	function getKeyword()
	{
		return $this->keyword;
	}
	function setKeywordLanguage(&$lng_obj)
	{
		if(is_object($lng_obj))
		{
			$this->keyword_language = $lng_obj;
		}
	}
	function &getKeywordLanguage()
	{
		return is_object($this->keyword_language) ? $this->keyword_language : false;
	}
	function getKeywordLanguageCode()
	{
		return is_object($this->keyword_language) ? $this->keyword_language->getLanguageCode() : false;
	}

	function save()
	{
		if($this->db->autoExecute('il_meta_keyword',
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
		global $ilDB;
		
		if($this->getMetaId())
		{
			if($this->db->autoExecute('il_meta_keyword',
									  $this->__getFields(),
									  DB_AUTOQUERY_UPDATE,
									  "meta_keyword_id = ".$ilDB->quote($this->getMetaId())))
			{
				return true;
			}
		}
		return false;
	}

	function delete()
	{
		global $ilDB;
		
		if($this->getMetaId())
		{
			$query = "DELETE FROM il_meta_keyword ".
				"WHERE meta_keyword_id = ".$ilDB->quote($this->getMetaId());
			
			$this->db->query($query);
			
			return true;
		}
		return false;
	}
			

	function __getFields()
	{
		return array('rbac_id'	=> $this->getRBACId(),
					 'obj_id'	=> $this->getObjId(),
					 'obj_type'	=> $this->getObjType(),
					 'parent_type' => $this->getParentType(),
					 'parent_id' => $this->getParentId(),
					 'keyword'	=> $this->getKeyword(),
					 'keyword_language' => $this->getKeywordLanguageCode());
	}

	function read()
	{
		global $ilDB;
		
		include_once 'Services/MetaData/classes/class.ilMDLanguageItem.php';

		if($this->getMetaId())
		{
			$query = "SELECT * FROM il_meta_keyword ".
				"WHERE meta_keyword_id = ".$ilDB->quote($this->getMetaId());

			$res = $this->db->query($query);
			while($row = $res->fetchRow(MDB2_FETCHMODE_OBJECT))
			{
				$this->setRBACId($row->rbac_id);
				$this->setObjId($row->obj_id);
				$this->setObjType($row->obj_type);
				$this->setParentId($row->parent_id);
				$this->setParentType($row->parent_type);
				$this->setKeyword($row->keyword);
				$this->setKeywordLanguage( new ilMDLanguageItem($row->keyword_language));
			}
		}
		return true;
	}
				
	/*
	 * XML Export of all meta data
	 * @param object (xml writer) see class.ilMD2XML.php
	 * 
	 */
	function toXML(&$writer)
	{
		$writer->xmlElement('Keyword',array('Language' => $this->getKeywordLanguageCode() ?
											$this->getKeywordLanguageCode() :
											'en'),
							$this->getKeyword());
	}


	// STATIC
	function _getIds($a_rbac_id,$a_obj_id,$a_parent_id,$a_parent_type)
	{
		global $ilDB;

		$query = "SELECT meta_keyword_id FROM il_meta_keyword ".
			"WHERE rbac_id = ".$ilDB->quote($a_rbac_id)." ".
			"AND obj_id = ".$ilDB->quote($a_obj_id)." ".
			"AND parent_id = ".$ilDB->quote($a_parent_id)." ".
			"AND parent_type = ".$ilDB->quote($a_parent_type)." ".
			"ORDER BY meta_keyword_id ";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(MDB2_FETCHMODE_OBJECT))
		{
			$ids[] = $row->meta_keyword_id;
		}
		return $ids ? $ids : array();
	}
	
	/**
	 * Get keywords by language 
	 *
	 * @access public
	 * @static
	 *
	 * @param int rbac_id
	 * @param int obj_id
	 * @param string obj type
	 */
	public static function _getKeywordsByLanguage($a_rbac_id,$a_obj_id,$a_type)
	{
		global $ilDB,$ilObjDataCache;
		
		$query = "SELECT keyword,keyword_language ".
			"FROM il_meta_keyword ".
			"WHERE rbac_id = ".$ilDB->quote($a_rbac_id)." ".
			"AND obj_id = ".$ilDB->quote($a_obj_id)." ".
			"AND obj_type = ".$ilDB->quote($a_type)." ";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(MDB2_FETCHMODE_OBJECT))
		{
			if($row->keyword)
			{
				$keywords[$row->keyword_language][] = $row->keyword;
			}
		}
		return $keywords ? $keywords : array();	
	}
	/**
	 * Get keywords by language as string
	 *
	 * @access public
	 * @static
	 *
	 * @param int rbac_id
	 * @param int obj_id
	 * @param string obj type
	 */
	public static function _getKeywordsByLanguageAsString($a_rbac_id,$a_obj_id,$a_type)
	{
		foreach(ilMDKeyword::_getKeywordsByLanguage($a_rbac_id,$a_obj_id,$a_type) as $lng_code => $keywords)
		{
			$key_string[$lng_code] = implode(",",$keywords);
		}
		return $key_string ? $key_string : array();
	}
}
?>