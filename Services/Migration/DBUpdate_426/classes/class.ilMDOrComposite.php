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
* Meta Data class (element orComposite)
* Extends MDRequirement
*
* @package ilias-core
* @version $Id$
*/
include_once 'class.ilMDBase.php';
include_once 'Services/Migration/DBUpdate_426/classes/class.ilMDRequirement.php';

class ilMDOrComposite extends ilMDRequirement
{
	function ilMDOrComposite($a_rbac_id = 0,$a_obj_id = 0,$a_obj_type = '')
	{
		parent::ilMDRequirement($a_rbac_id,
								$a_obj_id,
								$a_obj_type);
	}

	// SET/GET
	function setOrCompositeId($a_or_composite_id)
	{
		$this->or_composite_id = (int) $a_or_composite_id;
	}
	function getOrCompositeId()
	{
		if(!$this->or_composite_id)
		{
			$query = "SELECT MAX(or_composite_id) AS orc FROM il_meta_requirement ".
				"WHERE rbac_id = '".$this->getRBACId()."' ".
				"AND obj_id = '".$this->getObjId()."' ".
				"GROUP BY or_composite_id";

			$res = $this->db->query($query);
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$this->or_composite_id = $row->orc;
			}
			++$this->or_composite_id;
		}
		return $this->or_composite_id;
	}

	function &getRequirementIds()
	{
		include_once 'Services/Migration/DBUpdate_426/classes/class.ilMDRequirement.php';

		return ilMDRequirement::_getIds($this->getRBACId(),
										$this->getObjId(),
										$this->getParentId(),
										'meta_technical',
										$this->getOrCompositeId());
	}

	function &getRequirement($a_requirement_id)
	{
		include_once 'Services/Migration/DBUpdate_426/classes/class.ilMDRequirement.php';

		if(!$a_requirement_id)
		{
			return false;
		}
		$req =& new ilMDRequirement();
		$req->setMetaId($a_requirement_id);

		return $req;
	}

	function &addRequirement()
	{
		include_once 'Services/Migration/DBUpdate_426/classes/class.ilMDRequirement.php';

		$req =& new ilMDRequirement($this->getRBACId(),$this->getObjId(),$this->getObjType());
		$req->setParentId($this->getParentId());
		$req->setParentType('meta_technical');
		$req->setOrCompositeId($this->getOrCompositeId());

		return $req;
	}

	/*
	 * Overwritten save method, to get new or_composite_id
	 * 
	 */
	function save()
	{
		echo 'Use ilMDOrcomposite::addRequirement()';
	}

	function delete()
	{
		foreach($this->getRequirementIds() as $id)
		{
			$req = $this->getRequirement($id);
			$req->delete();
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
		// For all requirements
		$writer->xmlStartTag('OrComposite');

		foreach($this->getRequirementIds() as $id)
		{
			$req = $this->getRequirement($id);
			$req->toXML($writer);
		}
		$writer->xmlEndTag('OrComposite');
		
	}


	// STATIC
	function _getIds($a_rbac_id,$a_obj_id,$a_parent_id,$a_parent_type)
	{
		global $ilDB;

		$query = "SELECT DISTINCT(or_composite_id) AS or_composite_id FROM il_meta_requirement ".
			"WHERE rbac_id = '".$a_rbac_id."' ".
			"AND obj_id = '".$a_obj_id."' ".
			"AND parent_id = '".$a_parent_id."' ".
			"AND parent_type = '".$a_parent_type."' ".
			"AND or_composite_id > 0 ".
			"ORDER BY or_composite_id";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ids[] = $row->or_composite_id;
		}
		return $ids ? $ids : array();
	}
}
?>