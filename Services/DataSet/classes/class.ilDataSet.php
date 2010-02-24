<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* A dataset contains in data in a common structure that can be
* shared and transformed for different purposes easily, examples
* - transform associative arrays into (set-)xml and back (e.g. for import/export)
* - transform assiciative arrays into json and back (e.g. for ajax requests)
*
* The general structure is:
* - entity name (many times this corresponds to a table name)
* - structure (this is a set of field names and types pairs)
*   currently supported types: text, integer, timestamp
*   planned: date, time, clob
*   types correspond to db types, see
*   http://www.ilias.de/docu/goto.php?target=pg_25354_42&client_id=docu
* - records (similar to records of a database query; associative arrays)
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
* @ingroup 
*/
abstract class ilDataSet
{
	var $dircnt;
	
	/**
	 * Constructor
	 */
	function __construct()
	{
	}
	
	/**
	 * Init
	 *
	 * @param	string		(abstract) entity name 
	 * @param	string		version string, always the ILIAS release
	 * 						versions that defined the a structure
	 * 						or made changes to it, never use another
	 * 						version. Example: structure is defined
	 * 						in 4.1.0 and changed in 4.3.0 -> use these
	 * 						values only, not 4.2.0 (ask for the 4.1.0
	 * 						version in ILIAS 4.2.0)
	 */
	final public function init($a_entity, $a_target_release)
	{
		$this->entity = $a_entity;
		$this->target_release = $a_target_release;
		$this->data = array();
	}
	
	/**
	 * Get supported version
	 * 
	 * @return	array		array of supported version
	 */
	abstract public function getSupportedVersions($a_entity);
		
	/**
	 * Get (abstract) types for (abstract) field names.
	 * Please note that the abstract fields/types only depend on
	 * the version! Not on a choosen representation!
	 * 
	 * @return	array		types array, e.g.
	 * array("field_1" => "text", "field_2" => "integer", ...) 
	 */
	abstract protected function getTypes($a_entity, $a_version);
	
	/**
	 * Get xml namespace
	 *
	 */
	abstract protected function getXmlNamespace($a_entity, $a_target_release);
	
	/**
	 * Read data from DB. This should result in the
	 * abstract field structure of the version set in the constructor.
	 * 
	 * @param	array	one or multiple ids
	 */
	abstract function readData($a_entity, $a_version, $a_ids);
	
	/**
	 * Set export directories
	 *
	 * @param
	 * @return
	 */
	function setExportDirectories($a_relative, $a_absolute)
	{
		$this->relative_export_dir = $a_relative;
		$this->absolute_export_dir = $a_absolute;
	}
	
	/**
	 * Get data from query.This is a standard procedure,
	 * all db field names are directly mapped to abstract fields.
	 * 
	 * @param
	 * @return
	 */
	function getDirectDataFromQuery($a_query)
	{
		global $ilDB;
		
		$set = $ilDB->query($a_query);
		$this->data = array();
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$this->data[] = $rec;
		}
	}
			
	/**
	 * Get json representation
	 */
	final function getJsonRepresentation()
	{
		if ($this->version === false)
		{
			return false;
		}
		
		$arr["entity"] = $this->getJsonEntityName();
		$arr["version"] = $this->version;
		$arr["install_id"] = IL_INST_ID;
		$arr["install_url"] = ILIAS_HTTP_PATH;
		$arr["types"] = $this->getJsonTypes();
		$arr["set"] = array();
		foreach ($this->data as $d)
		{
			$arr["set"][] = $this->getJsonRecord($d);
		}
		
		include_once("./Services/JSON/classes/class.ilJsonUtil.php");

		return ilJsonUtil::encode($arr);
	}

	/**
	 * Get xml representation
	 * 	<data_set install_id="123" install_url="...">
	 * 	<types entity="table_name" version="4.0.1">
	 *		<ftype name="field_1" type="text" />
	 *		<ftype name="field_2" type="date" />
	 *		<ftype name="field_3" type="integer" />
	 *	</types>
	 *  <types ...>
	 *    ...
	 *  </types>
	 *	<set entity="table_name">
	 *		<rec>
	 *			<field_1>content</field_1>
	 *			<field_2>my_date</field_2>
	 *			<field_3>my_number</field_3>
	 *		</rec>
	 *		...
	 *	</set>
	 *  </data_set>
	 */
	final function getXmlRepresentation($a_entity, $a_target_release,
		$a_ids, $a_field = "")
	{	
		$this->dircnt = 1;
		
		// step 1: check target release and supported versions
		
		
		
		// step 2: init writer
		include_once "./Services/Xml/classes/class.ilXmlWriter.php";
		$writer = new ilXmlWriter();
		$writer->xmlHeader();
		
		// collect namespaces
		$namespaces = $prefixes = array();
		$this->getNamespaces($namespaces, $a_entity, $a_target_release);
		$atts = array("install_id" => IL_INST_ID,
			"install_url" => ILIAS_HTTP_PATH);
		$cnt = 1;
		foreach ($namespaces as $entity => $ns)
		{
			$prefix = "ns".$cnt;
			$prefixes[$entity] = $prefix; 
			$atts["xmlns:".$prefix] = $ns;
			$cnt++;
		}
		
		$writer->xmlStartTag('data_set', $atts);		
		
		// add types
		$this->addTypesXml($writer, $a_entity, $a_target_release);
		
		// add records
		$this->addRecordsXml($writer, $prefixes, $a_entity, $a_target_release, $a_ids, $a_field = "");
		
		
		$writer->xmlEndTag("data_set");
global $tpl;		
$tpl->setContent($tpl->main_content."<pre>".htmlentities($writer->xmlDumpMem(true))."</pre>");
		
		return $writer->xmlDumpMem(false);
	}
	
	
	/**
	 * Add records xml
	 *
	 * @param
	 * @return
	 */
	function addRecordsXml($a_writer, $a_prefixes, $a_entity, $a_target_release, $a_ids, $a_field = "")
	{
		$types = $this->getXmlTypes($a_entity, $a_target_release);
		
		$this->readData($a_entity, $a_target_release, $a_ids, $a_field);
		if (is_array($this->data))
		{		
			foreach ($this->data as $d)
			{
				$a_writer->xmlStartTag("rec",
					array("entity" => $this->getXmlEntityName($a_entity, $a_target_release)));
				$rec = $this->getXmlRecord($a_entity, $a_target_release, $d);
				foreach ($rec as $f => $c)
				{
					switch ($types[$f])
					{
						case "directory":
							if ($this->absolute_export_dir != "" && $this->relative_export_dir != "")
							{
								ilUtil::makeDir($this->absolute_export_dir."/dir_".$this->dircnt);
								ilUtil::rCopy($c, $this->absolute_export_dir."/dir_".$this->dircnt);
//echo "<br>copy-".$c."-".$this->absolute_export_dir."/dir_".$this->dircnt."-";
								$c = $this->relative_export_dir."/dir_".$this->dircnt;
								$this->dircnt++;
							}
							break;
					}	
					
					// this changes schema/dtd
					$a_writer->xmlElement($a_prefixes[$a_entity].":".$f,
						array(), $c);
				}
				$a_writer->xmlEndTag("rec");

				// foreach record records of dependent entities (no record)
				$deps = $this->getDependencies($a_entity, $a_target_release, $rec, $a_ids);
				if (is_array($deps))
				{
					foreach ($deps as $dp => $par)
					{
						$this->addRecordsXml($a_writer, $a_prefixes, $dp, $a_target_release, $par["ids"], $par["field"]);
					}
				}
			}
		}
		else if ($this->data === false)
		{
			// false -> add records of dependent entities (no record)
			$deps = $this->getDependencies($a_entity, $a_target_release, null, $a_ids);
			if (is_array($deps))
			{
				foreach ($deps as $dp => $par)
				{
					$this->addRecordsXml($a_writer, $a_prefixes, $dp, $a_target_release, $par["ids"], $par["field"]);
				}
			}
		}
	}
	
	/**
	 * Add types to xml writer
	 *
	 * @param
	 */
	private function addTypesXml($a_writer, $a_entity, $a_target_release)
	{
		$types = $this->getXmlTypes($a_entity, $a_target_release);
		
		// add types of current entity
		if (is_array($types))
		{
			$a_writer->xmlStartTag("types",
				array("entity" => $this->getXmlEntityName($a_entity, $a_target_release),
					"version" => $a_target_release));
			foreach ($this->getXmlTypes($a_entity, $a_target_release) as $f => $t)
			{
				$a_writer->xmlElement('ftype',
					array("name" => $f, "type" => $t));
			}
			$a_writer->xmlEndTag("types");
		}
		
		// add types of dependent entities
		$deps = $this->getDependencies($a_entity, $a_target_release, null, null);
		if (is_array($deps))
		{
			foreach ($deps as $dp => $w)
			{
				$this->addTypesXml($a_writer, $dp, $a_target_release);
			}
		}
		
	}
	
	/**
	 * Get xml namespaces
	 *
	 * @param		array		namespaces per entity
	 * @param		string		entity
	 * @param		string		target release
	 */
	function getNamespaces(&$namespaces, $a_entity, $a_target_release)
	{
		$ns = $this->getXmlNamespace($a_entity, $a_target_release);
		if ($ns != "")
		{
			$namespaces[$a_entity] = $ns;
		}		
		// add types of dependent entities
		$deps = $this->getDependencies($a_entity, $a_target_release, null, null);
		if (is_array($deps))
		{
			foreach ($deps as $dp => $w)
			{
				$this->getNamespaces($namespaces, $dp, $a_target_release);
			}
		}
	}
	
	/**
	 * Get xml record for version
	 *
	 * @param	array	abstract data record
	 * @return	array	xml record
	 */
	function getXmlRecord($a_entity, $a_version, $a_set)
	{
		return $a_set;
	}
	
	/**
	 * Get json record for version
	 *
	 * @param	array	abstract data record
	 * @return	array	json record
	 */
	function getJsonRecord($a_set)
	{
		return $a_set;		
	}
	
	/**
	 * Get xml types
	 *
	 * @return	array	types array for xml/version set in constructor
	 */
	function getXmlTypes($a_entity, $a_version)
	{
		return $this->getTypes($a_entity, $a_version);
	}
	
	/**
	 * Get json types
	 *
	 * @return	array	types array for json/version set in constructor
	 */
	function getJsonTypes($a_entity, $a_version)
	{
		return $this->getTypes($a_entity, $a_version);
	}
	
	/**
	 * Get entity name for xml
	 * (may be overwritten)
	 * 
	 * @return	string		
	 */
	function getXMLEntityName($a_entity, $a_version)
	{
		return $a_entity;
	}
	
	/**
	 * Get entity name for json
	 * (may be overwritten)
	 */
	function getJsonEntityName($a_entity, $a_version)
	{
		return $a_entity;
	}
}

?>