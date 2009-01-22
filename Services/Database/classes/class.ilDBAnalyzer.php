<?php

/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
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
* This class gives all kind of DB information using the MDB2 manager
* and reverse module.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id: class.ilDBUpdate.php 18649 2009-01-21 09:59:23Z akill $
* @ingroup ServicesDatabase
*/
class ilDBAnalyzer
{
	
	/**
	* Constructor
	*/
	function __construct()
	{
		global $ilDB;
		
		$this->manager = $ilDB->db->loadModule('Manager');
		$this->reverse = $ilDB->db->loadModule('Reverse');
		$this->il_db = $ilDB;		
	}
	
	
	/**
	* Get field information of a table.
	*
	* @param	string		table name
	* @return	array		field information array
	*/
	function getFieldInformation($a_table)
	{
		$fields = $this->manager->listTableFields($a_table);
		$inf = array();
		foreach ($fields as $field)
		{
			$rdef = $this->reverse->getTableFieldDefinition($a_table, $field);

			// is this possible?
			if ($rdef["type"] != $rdef["mdb2type"])
			{
				echo "ilDBAnalyzer::getFielInformation: Found type != mdb2type: $a_table, $field";
			}
			$inf[$field] = array(
				"notnull" => $rdef[0]["notnull"],
				"nativetype" => $rdef[0]["nativetype"],
				"length" => $rdef[0]["length"],
				"unsigned" => $rdef[0]["unsigned"],
				"default" => $rdef[0]["default"],
				"fixed" => $rdef[0]["fixed"],
				"autoincrement" => $rdef[0]["autoincrement"],
				"type" => $rdef[0]["type"]
				);
		}
		return $inf;
	}
	
	/**
	* Gets the auto increment field of a table.
	* This should be used on ILIAS 3.10.x "MySQL" tables only.
	*
	* @param	string		table name
	* @return	string		name of autoincrement field
	*/
	function getAutoIncrementField($a_table)
	{
		$fields = $this->manager->listTableFields($a_table);
		$inf = array();

		foreach ($fields as $field)
		{
			$rdef = $this->reverse->getTableFieldDefinition($a_table, $field);
			if ($rdef[0]["autoincrement"])
			{
				return $field;
			}
		}
		return false;
	}
	
	/**
	* Get primary key of a table
	*
	* @param	string		table name
	* @return	array		primary key information array
	*/
	function getPrimaryKeyInformation($a_table)
	{
		$constraints = $this->manager->listTableConstraints($a_table);
		$pk = false;
		foreach ($constraints as $c)
		{
			$info = $this->reverse->getTableConstraintDefinition($a_table, $c);
			if ($info["primary"])
			{
				$pk["name"] = $c;
				foreach ($info["fields"] as $k => $f)
				{
					$pk["fields"][$k] = array(
						"position" => $f["position"],
						"sorting" => $f["sorting"]);
				}
			}
		}

		return $pk;
	}
	
	/**
	* Get information on indices of a table.
	* Primary key is NOT included!
	* Fulltext indices are included but not marked (@todo)
	*
	* @param	string		table name
	* @return	array		indices information array
	*/
	function getIndicesInformation($a_table)
	{
		//$constraints = $this->manager->listTableConstraints($a_table);
		$indexes = $this->manager->listTableIndexes($a_table);

		$ind = array();
		foreach ($indexes as $c)
		{
			$info = $this->reverse->getTableIndexDefinition($a_table, $c);
			$i = array();
			if (!$info["primary"])
			{
				$i["name"] = $c;
				foreach ($info["fields"] as $k => $f)
				{
					$i["fields"][$k] = array(
						"position" => $f["position"],
						"sorting" => $f["sorting"]);
				}
				$ind[] = $i;
			}
		}

		return $ind;
	}

	/**
	* Check whether sequence is defined for current table (only works on "abstraced" tables)
	*
	* @param	string		table name
	* @return	mixed		false, if no sequence is defined, start number otherwise
	*/
	function hasSequence($a_table)
	{
		$seq = $this->manager->listSequences();
		if (is_array($seq) && in_array($a_table, $seq))
		{
			// sequence field is (only) primary key field of table
			$pk = $this->getPrimaryKeyInformation($a_table);
			if (is_array($pk["fields"]) && count($pk["fields"] == 1))
			{
				$seq_field = key($pk["fields"]);
			}
			else
			{
				die("ilDBAnalyzer::hasSequence: Error, sequence defined, but no one-field primary key given. Table: ".$a_table.".");
			}
			
			$set = $this->il_db->query("SELECT MAX(`".$seq_field."`) ma FROM `".$a_table."`");
			$rec = $this->il_db->fetchAssoc($set);
			$next = $rec["ma"] + 1;

			return $next;
		}
		return false;
	}

}
?>
