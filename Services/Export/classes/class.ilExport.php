<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
* Export
*
* @author	Alex Killing <alex.killing@gmx.de>
* @version	$Id$
* @defgroup ServicesExport Services/Export
* @ingroup	ServicesExport
*/
class ilExport
{
	// this should be part of module.xml and be parsed in the future
	static $export_implementer = array("tst", "lm", "glo");
	
	// file type short (this is a workaround, for resource types,
	// that used the wrong file type string in the past
	static $file_type_str = array("tst" => "test_");

	/**
	* Get file type string
	*
	* @param	string		Object Type
	*/
	static function _getFileTypeString($a_obj_type)
	{
		if (!empty(self::$file_type_str[$a_obj_type]))
		{
			return self::$file_type_str[$a_obj_type];
		}
		else
		{
			return $a_obj_type;
		}
	}
	
	/**
	* Get a list of subitems of a repository resource, that implement
	* the export. Includes also information on last export file.
	*/
	static function _getValidExportSubItems($a_ref_id)
	{
		global $tree;
		
		$valid_items = array();
		$sub_items = $tree->getSubTree($tree->getNodeData($a_ref_id));
		foreach ($sub_items as $sub_item)
		{
			if (in_array($sub_item["type"], self::$export_implementer))
			{
				$valid_items[] = array("type" => $sub_item["type"],
					"title" => $sub_item["title"], "ref_id" => $sub_item["child"],
					"obj_id" => $sub_item["obj_id"],
					"timestamp" =>
					ilExport::_getLastExportFileDate($sub_item["obj_id"], "xml", $sub_item["type"]));
			}
		}
		return $valid_items;
	}
	
	/**
	* Get date of last export file
	*/
	static function _getLastExportFileDate($a_obj_id, $a_type = "", $a_obj_type = "")
	{
		$files = ilExport::_getExportFiles($a_obj_id, $a_type, $a_obj_type);
		if (is_array($files))
		{
			$files = ilUtil::sortArray($files, "timestamp", "desc");
			return $files[0]["timestamp"];
		}
		return false;
	}
	
	/**
	* Get last export file information
	*/
	static function _getLastExportFileInformation($a_obj_id, $a_type = "", $a_obj_type = "")
	{
		$files = ilExport::_getExportFiles($a_obj_id, $a_type, $a_obj_type);
		if (is_array($files))
		{
			$files = ilUtil::sortArray($files, "timestamp", "desc");
			return $files[0];
		}
		return false;
	}

	/**
	* Get export directory
	*
	* @param	integer		Object ID
	* @param	string		Export Type ("xml", "html", ...)
	* @param	string		Object Type
	*/
	function _getExportDirectory($a_obj_id, $a_type = "xml", $a_obj_type = "")
	{
		if ($a_obj_type == "")
		{
			$a_obj_type = ilObject::_lookupType($a_obj_id);
		}

		if ($a_type !=  "xml")
		{
			$export_dir = ilUtil::getDataDir()."/".$a_obj_type."_data"."/".$a_obj_type."_".$a_obj_id."/export_".$a_type;
		}
		else
		{
			$export_dir = ilUtil::getDataDir()."/".$a_obj_type."_data"."/".$a_obj_type."_".$a_obj_id."/export";
		}

		return $export_dir;
	}

	/**
	* Get Export Files
	*/
	function _getExportFiles($a_obj_id, $a_export_types = "", $a_obj_type = "")
	{

		if ($a_obj_type == "")
		{
			$a_obj_type = ilObject::_lookupType($a_obj_id);
		}

		if ($a_export_types == "")
		{
			$a_export_types = array("xml");
		}
		if (!is_array($a_export_types))
		{
			$a_export_types = array($a_export_types);
		}

		// initialize array
		$file = array();
		
		$types = $a_export_types;

		foreach($types as $type)
		{
			$dir = ilExport::_getExportDirectory($a_obj_id, $type, $a_obj_type);
			
			// quit if import dir not available
			if (!@is_dir($dir) or
				!is_writeable($dir))
			{
				continue;
			}

			// open directory
			$h_dir = dir($dir);

			// get files and save the in the array
			while ($entry = $h_dir->read())
			{
				if ($entry != "." and
					$entry != ".." and
					substr($entry, -4) == ".zip" and
					ereg("^[0-9]{10}_{2}[0-9]+_{2}(".ilExport::_getFileTypeString($a_obj_type)."_)*[0-9]+\.zip\$", $entry))
				{
					$ts = substr($entry, 0, strpos($entry, "__"));
					$file[$entry.$type] = array("type" => $type, "file" => $entry,
						"size" => filesize($dir."/".$entry),
						"timestamp" => $ts);
				}
			}
	
			// close import directory
			$h_dir->close();
		}

		// sort files
		ksort ($file);
		reset ($file);
		return $file;
	}

	/**
	* Create export directory
	*/
	function _createExportDirectory($a_obj_id, $a_export_type = "xml", $a_obj_type = "")
	{
		global $ilErr;
		
		if ($a_obj_type == "")
		{
			$a_obj_type = ilObject::_lookupType($a_obj_id);
		}

		$data_dir = ilUtil::getDataDir()."/".$a_obj_type."_data";
		ilUtil::makeDir($data_dir);
		if(!is_writable($data_dir))
		{
			$ilErr->raiseError("Data Directory (".$data_dir
				.") not writeable.",$ilErr->FATAL);
		}
		
		// create resource data directory
		$res_dir = $data_dir."/".$a_obj_type."_".$a_obj_id;
		ilUtil::makeDir($res_dir);
		if(!@is_dir($res_dir))
		{
			$ilErr->raiseError("Creation of Glossary Directory failed.",$ilErr->FATAL);
		}

		// create Export subdirectory (data_dir/glo_data/glo_<id>/Export)
		if ($a_export_type != "xml")
		{
			$export_dir = $res_dir."/export_".$a_export_type;
		}
		else
		{
			$export_dir = $res_dir."/export";
		}

		ilUtil::makeDir($export_dir);

		if(!@is_dir($export_dir))
		{
			$ilErr->raiseError("Creation of Export Directory failed.",$ilErr->FATAL);
		}
	}

	/**
	* Generates an index.html file including links to all xml files included
	* (for container exports)
	*/
	function _generateIndexFile($a_filename, $a_obj_id, $a_files, $a_type = "")
	{
		global $lng;
		
		$lng->loadLanguageModule("export");
		
		if ($a_type == "")
		{
			$a_type = ilObject::_lookupType($a_obj_id);
		}
		$a_tpl = new ilTemplate("tpl.main.html", true, true);
		$location_stylesheet = ilUtil::getStyleSheetLocation();
		$a_tpl->setVariable("LOCATION_STYLESHEET",$location_stylesheet);
		$a_tpl->getStandardTemplate();
		$a_tpl->setTitle(ilObject::_lookupTitle($a_obj_id));
		$a_tpl->setDescription($lng->txt("export_export_date").": ".
			date('Y-m-d H:i:s', time())." (".date_default_timezone_get().")");
		$f_tpl = new ilTemplate("tpl.export_list.html", true, true, "Services/Export");
		foreach ($a_files as $file)
		{
			$f_tpl->setCurrentBlock("file_row");
			$f_tpl->setVariable("TITLE", $file["title"]);
			$f_tpl->setVariable("TYPE", $lng->txt("obj_".$file["type"]));
			$f_tpl->setVariable("FILE", $file["file"]);
			$f_tpl->parseCurrentBlock();
		}
		$a_tpl->setContent($f_tpl->get());
		$index_content = $a_tpl->get("DEFAULT", false, false, false, true, false, false);

		$f = fopen ($a_filename, "w");
		fwrite($f, $index_content);
		fclose($f);
	}
	
	////
	//// New functions coming with 4.1 export revision
	////
	
	/***
	 * 
	 * - Walk through sequence
	 * - Each step in sequence creates one xml file,
	 *   e.g. Services/Mediapool/set_1.xml
	 * - manifest.xml lists all files
	 * 
	 * <manifest>
	 * <xmlfile path="Services/Mediapool/set_1.xml"/>
	 * ...
	 * </manifest
	 *    
	 * 
	 */
	
	/**
	 * Export an ILIAS object (the object type must be known by objDefinition)
	 *
	 * @param
	 * @return
	 */
	function exportObject($a_type, $a_id, $a_target_release, $a_config = "")
	{
		global $objDefinition, $tpl;
				
		$comp = $objDefinition->getComponentForType($a_type);
		$c = explode("/", $comp);
		$class = "il".$c[1]."Exporter";
		
		// manifest writer
		include_once "./Services/Xml/classes/class.ilXmlWriter.php";
		$this->manifest_writer = new ilXmlWriter();
		$this->manifest_writer->xmlHeader();
		$this->manifest_writer->xmlStartTag('Manifest',
			array("MainEntity" => $a_type, "Title" => ilObject::_lookupTitle($a_id), "TargetRelease" => $a_target_release,
				"InstallationId" => IL_INST_ID, "InstallationUrl" => ILIAS_HTTP_PATH));

		// get export class
		ilExport::_createExportDirectory($a_id, "xml", $a_type);
		$export_dir = ilExport::_getExportDirectory($a_id, "xml", $a_type);
		$ts = time();
		$sub_dir = $ts."__".IL_INST_ID."__".$a_type."_".$a_id;
		$this->export_run_dir = $export_dir."/".$sub_dir;
		ilUtil::makeDirParents($this->export_run_dir);

		$this->cnt = array();
		
		$success = $this->processExporter($comp, $class, $a_type, $a_target_release, $a_id);

		$this->manifest_writer->xmlEndTag('Manifest');

//$tpl->setContent($tpl->main_content."<pre>".htmlentities($manifest_writer->xmlDumpMem(true))."</pre>");

		$this->manifest_writer->xmlDumpFile($this->export_run_dir."/manifest.xml", false);
//echo "-".$export_run_dir."-";

		// zip the file
		ilUtil::zip($this->export_run_dir, $export_dir."/".$sub_dir.".zip");
		ilUtil::delDir($this->export_run_dir);
//exit;
		return array(
			"success" => $success,
			"file" => $filename,
			"directory" => $directory
			);
	}

	/**
	 * Process exporter
	 *
	 * @param
	 * @return
	 */
	function processExporter($a_comp, $a_class, $a_entity, $a_target_release, $a_id)
	{
		$success = true;

		if (!is_array($a_id))
		{
			if ($a_id == "")
			{
				return;
			}
			$a_id = array($a_id);
		}

		// get exporter object
		$export_class_file = "./".$a_comp."/classes/class.".$a_class.".php";
//echo "1-".$export_class_file."-"; exit;
		if (!is_file($export_class_file))
		{
echo "1-not found:".$export_class_file."-"; exit;
			return false;
		}
		include_once($export_class_file);
		$exp = new $a_class();
		if (!isset($this->cnt[$a_comp]))
		{
			$this->cnt[$a_comp] = 1;
		}
		else
		{
			$this->cnt[$a_comp]++;
		}
		$set_dir_relative = $a_comp."/set_".$this->cnt[$a_comp];
		$set_dir_absolute = $this->export_run_dir."/".$set_dir_relative;
		ilUtil::makeDirParents($set_dir_absolute);
		$exp->init();

		$sv = $exp->determineSchemaVersion($a_entity, $a_target_release);

		// process head dependencies
		$sequence = $exp->getXmlExportHeadDependencies($a_entity, $a_target_release, $a_id);
		foreach ($sequence as $s)
		{
			$comp = explode("/", $s["component"]);
			$exp_class = "il".$comp[1]."Exporter";
			$s = $this->processExporter($s["component"], $exp_class,
				$s["entity"], $a_target_release, $s["ids"]);
			if (!$s)
			{
				$success = false;
			}
		}

		// write export.xml file
		$export_writer = new ilXmlWriter();
		$export_writer->xmlHeader();

		$attribs = array("InstallationId" => IL_INST_ID,
			"InstallationUrl" => ILIAS_HTTP_PATH,
			"Entity" => $a_entity, "SchemaVersion" => $sv["schema_version"], "TargetRelease" => $a_target_release,
			"xmlns:xsi" => "http://www.w3.org/2001/XMLSchema-instance",
			"xmlns:exp" => "http://www.ilias.de/Services/Export/exp/4_1",
			"xsi:schemaLocation" => "http://www.ilias.de/Services/Export/exp/4_1 ".ILIAS_HTTP_PATH."/xml/ilias_export_4_1.xsd"
			);
		if ($sv["namespace"] != "" && $sv["xsd_file"] != "")
		{
			$attribs["xsi:schemaLocation"].= " ".$sv["namespace"]." ".
				ILIAS_HTTP_PATH."/xml/".$sv["xsd_file"];
			$attribs["xmlns"] = $sv["namespace"];
		}
		if ($sv["uses_dataset"])
		{
			$attribs["xsi:schemaLocation"].= " ".
				"http://www.ilias.de/Services/DataSet/ds/4_1 ".ILIAS_HTTP_PATH."/xml/ilias_ds_4_1.xsd";
			$attribs["xmlns:ds"] = "http://www.ilias.de/Services/DataSet/ds/4_1";
		}


		$export_writer->xmlStartTag('exp:Export', $attribs);

		$dir_cnt = 1;
		foreach ($a_id as $id)
		{
			$exp->setExportDirectories($set_dir_relative."/expDir_".$dir_cnt,
				$set_dir_absolute."/expDir_".$dir_cnt);
			$export_writer->xmlStartTag('exp:ExportItem', array("Id" => $id));
			$xml = $exp->getXmlRepresentation($a_entity, $a_target_release, $id);
			$export_writer->appendXml($xml);
			$export_writer->xmlEndTag('exp:ExportItem');
			$dir_cnt++;
		}
		
		$export_writer->xmlEndTag('exp:Export');
		$export_writer->xmlDumpFile($set_dir_absolute."/export.xml", false);
		
		$this->manifest_writer->xmlElement("ExportFile",
			array("Component" => $a_comp, "Path" => $set_dir_relative."/export.xml"));

		// process tail dependencies
		$sequence = $exp->getXmlExportTailDependencies($a_entity, $a_target_release, $a_id);
		foreach ($sequence as $s)
		{
			$comp = explode("/", $s["component"]);
			$exp_class = "il".$comp[1]."Exporter";
			$s = $this->processExporter($s["component"], $exp_class,
				$s["entity"], $a_target_release, $s["ids"]);
			if (!$s)
			{
				$success = false;
			}
		}

		return $success;
	}
}
