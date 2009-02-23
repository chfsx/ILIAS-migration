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
* This class provides methods for building a DB generation script,
* getting a full overview on abstract table definitions and more...
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id: class.ilDBUpdate.php 18649 2009-01-21 09:59:23Z akill $
* @ingroup ServicesDatabase
*/
class ilDBGenerator
{
	var $whitelist = array();
	var $blacklist = array();
	var $tables = array();
	
	/**
	* Constructor
	*/
	function __construct()
	{
		global $ilDB;
		
		$this->manager = $ilDB->db->loadModule('Manager');
		$this->reverse = $ilDB->db->loadModule('Reverse');
		$this->il_db = $ilDB;
		include_once("./Services/Database/classes/class.ilDBAnalyzer.php");
		$this->analyzer = new ilDBAnalyzer();
		
		$this->allowed_attributes = $ilDB->getAllowedAttributes();
	}
	
	/**
	* Set Table Black List.
	* (Tables that should not be included in the processing)
	*
	* @param	array	$a_blacklist	Table Black List
	*/
	function setBlackList($a_blacklist)
	{
		$this->blacklist = $a_blacklist;
	}

	/**
	* Get Table Black List.
	*
	* @return	array	Table Black List
	*/
	function getBlackList()
	{
		return $this->blacklist;
	}

	/**
	* Set Table White List.
	* Per default all tables are included in the processing. If a white
	* list ist provided, only them will be used.
	*
	* @param	array	$a_whitelist	Table White List
	*/
	function setWhiteList($a_whitelist)
	{
		$this->whitelist = $a_whitelist;
	}

	/**
	* Get Table White List.
	*
	* @return	array	Table White List
	*/
	function getWhiteList()
	{
		return $this->whitelist;
	}

	/**
	* Set filter
	*/
	function setFilter($a_filter, $a_value)
	{
		$this->filter[$a_filter] = $a_value;
	}
	
	/**
	* Get (all) tables
	*/
	function getTables()
	{
		$r = $this->manager->listTables();
		if (!MDB2::isError($r))
		{
			$this->tables = $r;
		}
	}
	
	/**
	* Check whether a table should be processed or not
	*/
	function checkProcessing($a_table)
	{
		// check black list
		if (in_array($a_table, $this->blacklist))
		{
			return false;
		}
		
		// check white list
		if (count($this->whitelist) > 0 && !in_array($a_table, $this->whitelist))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	* Build DB generation script
	*
	* @param	string		output filename, if no filename is given, script is echoed
	*/
	function buildDBGenerationScript($a_filename = "")
	{
echo "<br>3"; flush();
		$file = "";
		if ($a_filename != "")
		{
			$file = fopen($a_filename, "w");
			
			$start = '<?php'."\n".'function setupILIASDatabase()'."\n{\n";
			$start.= "\t".'global $ilDB;'."\n\n";
			fwrite($file, $start);
		}
		else
		{
			echo "<pre>";
		}
echo "<br>4"; flush();
		$this->getTables();
		foreach ($this->tables as $table)
		{
			if ($this->checkProcessing($table))
			{
				if ($a_filename != "")
				{
					echo "<br>$table"; flush();
				}
				
				// create table statement
				$this->buildCreateTableStatement($table, $file);
				
				// primary key
				$this->buildAddPrimaryKeyStatement($table, $file);
				
				// indices
				$this->buildAddIndexStatements($table, $file);
				
				// auto increment sequence
				$this->buildCreateSequenceStatement($table, $file);
				
				// inserts
				$this->buildInsertStatements($table, $file);
			}
		}
		
		if ($a_filename == "")
		{
			echo "</pre>";
		}
		else
		{
			$end = "\n}\n?>\n";
			fwrite ($file, $end);
			fclose ($file);
		}
	}
	
	/**
	* Build CreateTable statement
	*
	* @param	string		table name
	* @param	file		file resource or empty string
	*/
	function buildCreateTableStatement($a_table, $a_file = "")
	{
		$fields = $this->analyzer->getFieldInformation($a_table, true);
		$this->fields = $fields;
		$create_st = "\n\n//\n// ".$a_table."\n//\n";
		$create_st.= '$fields = array ('."\n";
		$f_sep = "";
		foreach ($fields as $f => $def)
		{

			$create_st.= "\t".$f_sep.'"'.$f.'" => array ('."\n";
			$f_sep = ",";
			$a_sep = "";
			foreach ($def as $k => $v)
			{
				if ($k != "nativetype" && $k != "alt_types" && $k != "autoincrement" && !is_null($v))
				{
					switch ($k)
					{
						case "notnull":
						case "unsigned":
						case "fixed":
							$v = $v ? "true" : "false";
							break;
							
						case "default":
						case "type":
							$v = '"'.$v.'"';
							brak;
							
						default:
							break;
					}
					$create_st.= "\t\t".$a_sep.'"'.$k.'" => '.$v."\n";
					$a_sep = ",";
				}
			}
			$create_st.= "\t".')'."\n";
		}
		$create_st.= ');'."\n";
		$create_st.= '$ilDB->createTable("'.$a_table.'", $fields);'."\n";
		
		if ($a_file == "")
		{
			echo $create_st;
		}
		else
		{
			fwrite($a_file, $create_st);
		}
	}
	
	/**
	* Build AddPrimaryKey statement
	*
	* @param	string		table name
	* @param	file		file resource or empty string
	*/
	function buildAddPrimaryKeyStatement($a_table, $a_file = "")
	{
		$pk = $this->analyzer->getPrimaryKeyInformation($a_table);

		if (is_array($pk["fields"]) && count($pk["fields"]) > 0)
		{
			$pk_st = "\n".'$pk_fields = array(';
			$sep = "";
			foreach ($pk["fields"] as $f => $pos)
			{
				$pk_st.= $sep.'"'.$f.'"';
				$sep = ",";
			}
			$pk_st.= ");\n";
			$pk_st.= '$ilDB->addPrimaryKey("'.$a_table.'", $pk_fields);'."\n";
			
			if ($a_file == "")
			{
				echo $pk_st;
			}
			else
			{
				fwrite($a_file, $pk_st);
			}
		}
	}

	/**
	* Build AddIndex statements
	*
	* @param	string		table name
	* @param	file		file resource or empty string
	*/
	function buildAddIndexStatements($a_table, $a_file = "")
	{
		$ind = $this->analyzer->getIndicesInformation($a_table);

		if (is_array($ind))
		{
			foreach ($ind as $i)
			{
				$in_st = "\n".'$in_fields = array(';
				$sep = "";
				foreach ($i["fields"] as $f => $pos)
				{
					$in_st.= $sep.'"'.$f.'"';
					$sep = ",";
				}
				$in_st.= ");\n";
				$in_st.= '$ilDB->addIndex("'.$a_table.'", $in_fields, "'.$i["name"].'");'."\n";
				
				if ($a_file == "")
				{
					echo $in_st;
				}
				else
				{
					fwrite($a_file, $in_st);
				}
			}
		}
	}

	/**
	* Build CreateSequence statement
	*
	* @param	string		table name
	* @param	file		file resource or empty string
	*/
	function buildCreateSequenceStatement($a_table, $a_file = "")
	{
		$seq = $this->analyzer->hasSequence($a_table);
		if ($seq !== false)
		{
			$seq_st = "\n".'$ilDB->createSequence("'.$a_table.'", '.(int) $seq.');'."\n";

			if ($a_file == "")
			{
				echo $seq_st;
			}
			else
			{
				fwrite($a_file, $seq_st);
			}
		}
	}

	/**
	* Build Insert statements
	*
	* @param	string		table name
	* @param	file		file resource or empty string
	*/
	function buildInsertStatements($a_table, $a_file = "")
	{
		if ($a_table == "lng_data")
		{
			return;
		}
		
		$set = $this->il_db->query("SELECT * FROM `".$a_table."`");
		$ins_st = "";
		$first = true;
		while ($rec = $this->il_db->fetchAssoc($set))
		{
			$fields = array();
			$types = array();
			$values = array();
			$i_str = array();
			foreach ($rec as $f => $v)
			{
				$fields[] = $f;
				$types[] = '"'.$this->fields[$f]["type"].'"';
				$values[] = "'".str_replace("'", "\'", $v)."'";
				$i_str[] = "'".$f."' => array('".$this->fields[$f]["type"].
					"', '".str_replace("'", "\'", $v)."')";
			}
			$fields_str = "(".implode($fields, ",").")";
			$types_str = "array(".implode($types, ",").")";
			$values_str = "array(".implode($values, ",").")";
			$ins_st = "\n".'$ilDB->insert("'.$a_table.'", array('."\n";
			$ins_st.= implode($i_str, ", ")."));\n";
			//$ins_st.= "\t".$fields_str."\n";
			//$ins_st.= "\t".'VALUES '."(%s".str_repeat(",%s", count($fields) - 1).')"'.",\n";
			//$ins_st.= "\t".$types_str.','.$values_str.');'."\n";
			
			if ($a_file == "")
			{
				echo $ins_st;
			}
			else
			{
				fwrite($a_file, $ins_st);
			}
			$ins_st = "";
		}
	}

	/**
	* Get table definition overview in HTML
	*
	* @param	string		output filename, if no filename is given, script is echoed
	*/
	function getHTMLOverview($a_filename = "")
	{
		$tpl = new ilTemplate("tpl.db_overview.html", true, true, "Services/Database");
		
		$this->getTables();
		$cnt = 1;
		foreach ($this->tables as $table)
		{
			if ($this->checkProcessing($table))
			{
				// create table statement
				if ($this->addTableToOverview($table, $tpl, $cnt))
				{
					$cnt++;
				}
			}
		}
		
		$tpl->setVariable("TXT_TITLE", "ILIAS Abstract DB Tables (".ILIAS_VERSION.")");
		
		if ($a_filename == "")
		{
			echo $tpl->get();
		}
	}
	
	/**
	* Add table to overview template
	*/
	function addTableToOverview($a_table, $a_tpl, $a_cnt)
	{
		$fields = $this->analyzer->getFieldInformation($a_table);
		$indices = $this->analyzer->getIndicesInformation($a_table);
		$pk = $this->analyzer->getPrimaryKeyInformation($a_table);
		$auto = $this->analyzer->getAutoIncrementField($a_table);
		$has_sequence = $this->analyzer->hasSequence($a_table);
		
		// table filter
		if (isset($this->filter["has_sequence"]))
		{
			if ((!$has_sequence && $auto == "" && $this->filter["has_sequence"]) ||
				(($has_sequence || $auto != "") && !$this->filter["has_sequence"]))
			{
				return false;
			}
		}
		
		// indices
		$indices_output = false;
		if (is_array($indices) && count($indices) > 0 && !$this->filter["skip_indices"])
		{
			foreach ($indices as $index => $def)
			{
				$f2 = array();
				foreach ($def["fields"] as $f => $pos)
				{
					$f2[] = $f;
				}
				$a_tpl->setCurrentBlock("index");
				$a_tpl->setVariable("VAL_INDEX", $def["name"]);
				$a_tpl->setVariable("VAL_FIELDS", implode($f2, ", "));
				$a_tpl->parseCurrentBlock();
				$indices_output = true;
			}
			$a_tpl->setCurrentBlock("index_table");
			$a_tpl->parseCurrentBlock();
		}

		// fields
		$fields_output = false;
		foreach ($fields as $field => $def)
		{
			// field filter
			if (isset($this->filter["alt_types"]))
			{
				if (($def["alt_types"] == "" && $this->filter["alt_types"]) ||
					($def["alt_types"] != "" && !$this->filter["alt_types"]))
				{
					continue;
				}
			}
			if (isset($this->filter["type"]))
			{
				if ($def["type"] != $this->filter["type"])
				{
					continue;
				}
			}
			if (isset($this->filter["nativetype"]))
			{
				if ($def["nativetype"] != $this->filter["nativetype"])
				{
					continue;
				}
			}
			if (isset($this->filter["unsigned"]))
			{
				if ($def["unsigned"] != $this->filter["unsigned"])
				{
					continue;
				}
			}
			
			$a_tpl->setCurrentBlock("field");
			if (empty($pk["fields"][$field]))
			{
				$a_tpl->setVariable("VAL_FIELD", strtolower($field));
			}
			else
			{
				$a_tpl->setVariable("VAL_FIELD", "<u>".strtolower($field)."</u>");
			}
			$a_tpl->setVariable("VAL_TYPE", $def["type"]);
			$a_tpl->setVariable("VAL_LENGTH", (!is_null($def["length"])) ? $def["length"] : "&nbsp;");
			
			if (strtolower($def["default"]) == "current_timestamp")
			{
				//$def["default"] = "0000-00-00 00:00:00";
				unset($def["default"]);
			}
			
			$a_tpl->setVariable("VAL_DEFAULT", (!is_null($def["default"])) ? $def["default"] : "&nbsp;");
			$a_tpl->setVariable("VAL_NOT_NULL", (!is_null($def["notnull"]))
				? (($def["notnull"]) ? "true" : "false")
				: "&nbsp;");
			$a_tpl->setVariable("VAL_FIXED", (!is_null($def["fixed"]))
				? (($def["fixed"]) ? "true" : "false")
				: "&nbsp;");
			$a_tpl->setVariable("VAL_UNSIGNED", (!is_null($def["unsigned"]))
				? (($def["unsigned"]) ? "true" : "false")
				: "&nbsp;");
			$a_tpl->setVariable("VAL_ALTERNATIVE_TYPES", ($def["alt_types"] != "") ? $def["alt_types"] : "&nbsp;");
			$a_tpl->setVariable("VAL_NATIVETYPE", ($def["nativetype"] != "") ? $def["nativetype"] : "&nbsp;");
			$a_tpl->parseCurrentBlock();
			$fields_output = true;
		}
		
		if ($fields_output)
		{
			$a_tpl->setCurrentBlock("field_table");
			$a_tpl->parseCurrentBlock();
		}
		
		// table information
		if ($indices_output || $fields_output)
		{
			$a_tpl->setCurrentBlock("table");
			$a_tpl->setVariable("TXT_TABLE_NAME", strtolower($a_table));
			if ($has_sequence || $auto != "")
			{
				$a_tpl->setVariable("TXT_SEQUENCE", "Has Sequence");
			}
			else
			{
				$a_tpl->setVariable("TXT_SEQUENCE", "No Sequence");
			}
			$a_tpl->setVariable("VAL_CNT", (int) $a_cnt);
			$a_tpl->parseCurrentBlock();
			
			return true;
		}
		
		return false;
	}

}
?>
