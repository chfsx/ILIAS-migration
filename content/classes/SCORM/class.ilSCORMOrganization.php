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

require_once("content/classes/SCORM/class.ilSCORMObject.php");

/**
* SCORM Organization
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @extends ilSCORMObject
* @package content
*/
class ilSCORMOrganization extends ilSCORMObject
{
	var $import_id;
	var $structure;


	/**
	* Constructor
	*
	* @param	int		$a_id		Object ID
	* @access	public
	*/
	function ilSCORMOrganization($a_id = 0)
	{
		parent::ilSCORMObject($a_id);
		$this->setType("sor");
	}

	function getImportId()
	{
		return $this->import_id;
	}

	function setImportId($a_import_id)
	{
		$this->import_id = $a_import_id;
	}

	function getStructure()
	{
		return $this->structure;
	}

	function setStructure($a_structure)
	{
		$this->structure = $a_structure;
	}

	function read()
	{
		parent::read();

		$q = "SELECT * FROM sc_organization WHERE id = '".$this->getId()."'";

		$obj_set = $this->ilias->db->query($q);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);
		$this->setImportId($obj_rec["import_id"]);
		$this->setStructure($obj_rec["structure"]);
	}

	function create()
	{
		parent::create();

		$q = "INSERT INTO sc_organization (obj_id, import_id, structure) VALUES ".
			"('".$this->getId()."', '".$this->getImportId()."','".$this->getStructure()."')";
		$this->ilias->db->query($q);
	}

	function update()
	{
		parent::update();

		$q = "UPDATE sc_organization SET ".
			"import_id = '".$this->getImportId()."', ".
			"structure = '".$this->getStructure()."' ".
			"WHERE obj_id = '".$this->getId()."'";
		$this->ilias->db->query($q);
	}


}
?>
