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

require_once("content/classes/class.ilObjContentObject.php");

/**
* Export class for content objects
*
* @author Databay AG <ay@databay.de>
* @author Alex Killing <alex.killing@gmx.de>
*
* @version $Id$
*
* @package content
*/
class ilContObjectExport
{
	var $err;			// error object
	var $db;			// database object
	var $ilias;			// ilias object
	var $cont_obj;		// content object (learning module | digilib book)
	var $inst_id;		// installation id
	var $mode;

	/**
	* Constructor
	* @access	public
	*/
	function ilContObjectExport(&$a_cont_obj, $a_mode = "xml")
	{
		global $ilErr, $ilDB, $ilias;

		$this->cont_obj =& $a_cont_obj;

		$this->err =& $ilErr;
		$this->ilias =& $ilias;
		$this->db =& $ilDB;
		$this->mode = $a_mode;

		$settings = $this->ilias->getAllSettings();
		$this->inst_id = $settings["inst_id"];

		$date = time();
		switch($this->mode)
		{
			case "pdf":
				$this->export_dir = $this->cont_obj->getOfflineDirectory();
				$this->subdir = $date."__".$this->inst_id."__".
					$this->cont_obj->getType()."_".$this->cont_obj->getId();
				$this->filename = $this->subdir.".fo";
				break;

			default:
				$this->export_dir = $this->cont_obj->getExportDirectory();
				$this->subdir = $date."__".$this->inst_id."__".
					$this->cont_obj->getType()."_".$this->cont_obj->getId();
				$this->filename = $this->subdir.".xml";
				break;
		}
	}

	function getInstId()
	{
		return $this->inst_id;
	}

    /**
    *   exports lm_data-table to xml-structure
    *
    *   @param  integer $depth
    *   @param  integer $left   left border of nested-set-structure
    *   @param  integer $right  right border of nested-set-structure
    *   @access public
    *   @return string  xml-structure
    */
    function exportRekursiv($depth, $left, $right)
	{
		// Jetzt alle lm_data anhand der obj_id auslesen.
		$query = "SELECT  *
			FROM lm_tree, lm_data
			WHERE lm_tree.lm_id = ".$this->cont_obj->getId()."
			AND   lm_tree.child = lm_data.obj_id
			AND   ( lm_data.type =  'st' OR lm_data.type =  'pg' )
			AND lm_tree.depth = $depth
			AND lm_tree.lft>$left and lm_tree.rgt<$right
			ORDER BY lm_tree.lft";

        $result = $this->db->query($query);

        while (is_array($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) )
		{
			if ($row["type"] == "st")
			{
				$xml .= "<StructureObject>";

				$nested = new ilNestedSetXML();
				$xml .= $nested->export($row["obj_id"],"st");
				$xml .= "\n";

				$xml .= $this->exportRekursiv($depth+1, $row["lft"], $row["rgt"]);

				$xml .= "</StructureObject>";
			}

			if ($row["type"] == "pg")
			{

                $query = "SELECT * FROM page_object WHERE page_id='".$row["obj_id"]."' ";
				$result2 = $this->db->query($query);

				$row2 = $result2->fetchRow(DB_FETCHMODE_ASSOC);

				$PO = $row2["content"]."\n";

				if (stristr($PO,"MediaObject"))
				{

					$dom = domxml_open_mem($PO);
					$xpc = xpath_new_context($dom);
					$path = "//MediaObject/MediaAlias";
					$res =& xpath_eval($xpc, $path);
					for($i = 0; $i < count($res->nodeset); $i++)
					{
						$id_arr = explode("_", $res->nodeset[$i]->get_attribute("OriginId"));
						$mob_id = $id_arr[count($id_arr) - 1];
						$this->mob_ids[$mob_id] = true;
					}
				}

				$nested = new ilNestedSetXML();
				$mdxml = $nested->export($row["obj_id"],"pg");

				$PO = str_replace("<PageObject>","<PageObject>\n$mdxml\n",$PO);

				$xml .= $PO;

			}
		}
		return($xml);
	}



    /**
    *   build export file (complete zip file)
    *
    *   @access public
    *   @return
    */
	function buildExportFile()
	{
		switch ($this->mode)
		{
			case "pdf":
				$this->buildExportFilePDF();
				break;

			default:
				$this->buildExportFileXML();
				break;
		}
	}

	/**
	* build xml export file
	*/
	function buildExportFileXML()
	{
		global $ilBench;

		$ilBench->start("ContentObjectExport", "buildExportFile");

		require_once("classes/class.ilXmlWriter.php");

		$this->xml = new ilXmlWriter;

		// set dtd definition
		$this->xml->xmlSetDtdDef("<!DOCTYPE LearningModule SYSTEM \"http://www.ilias.uni-koeln.de/download/dtd/ilias_co.dtd\">");

		// set generated comment
		$this->xml->xmlSetGenCmt("Export of ILIAS Content Module ".
			$this->cont_obj->getId()." of installation ".$this->inst.".");

		// set xml header
		$this->xml->xmlHeader();

		// create directories
		$this->cont_obj->createExportDirectory();
		ilUtil::makeDir($this->export_dir."/".$this->subdir);
		ilUtil::makeDir($this->export_dir."/".$this->subdir."/objects");

		// get Log File
		$expDir = $this->cont_obj->getExportDirectory();
		$expLog = new ilLog($expDir, "export.log");
		$expLog->delete();
		$expLog->setLogFormat("");
		$expLog->write(date("[y-m-d H:i:s] ")."Start Export");

		// get xml content
//echo "ContObjExport:".$this->inst_id.":<br>";
		$ilBench->start("ContentObjectExport", "buildExportFile_getXML");
		$this->cont_obj->exportXML($this->xml, $this->inst_id,
			$this->export_dir."/".$this->subdir, $expLog);
		$ilBench->stop("ContentObjectExport", "buildExportFile_getXML");

		// dump xml document to screen (only for debugging reasons)
		/*
		echo "<PRE>";
		echo htmlentities($this->xml->xmlDumpMem($format));
		echo "</PRE>";
		*/

		// dump xml document to file
		$ilBench->start("ContentObjectExport", "buildExportFile_dumpToFile");
		$this->xml->xmlDumpFile($this->export_dir."/".$this->subdir."/".$this->filename
			, false);
		$ilBench->stop("ContentObjectExport", "buildExportFile_dumpToFile");

		// zip the file
		$ilBench->start("ContentObjectExport", "buildExportFile_zipFile");
		ilUtil::zip($this->export_dir."/".$this->subdir,
			$this->export_dir."/".$this->subdir.".zip");
		$ilBench->stop("ContentObjectExport", "buildExportFile_zipFile");

		// destroy writer object
		$this->xml->_XmlWriter;

		$expLog->write(date("[y-m-d H:i:s] ")."Finished Export");
		$ilBench->stop("ContentObjectExport", "buildExportFile");
	}

	/**
	* build pdf offline file
	*/
	function buildExportFilePDF()
	{
		global $ilBench;

		$ilBench->start("ContentObjectExport", "buildPDFFile");

		require_once("classes/class.ilXmlWriter.php");

		$this->xml = new ilXmlWriter;

		// set dtd definition
		//$this->xml->xmlSetDtdDef("<!DOCTYPE LearningModule SYSTEM \"http://www.ilias.uni-koeln.de/download/dtd/ilias_co.dtd\">");

		// set generated comment
		//$this->xml->xmlSetGenCmt("Export of ILIAS Content Module ".
		//	$this->cont_obj->getId()." of installation ".$this->inst.".");

		// set xml header
		$this->xml->xmlHeader();

		// create directories
		$this->cont_obj->createOfflineDirectory();
		//ilUtil::makeDir($this->export_dir."/".$this->subdir);
		//ilUtil::makeDir($this->export_dir."/".$this->subdir."/objects");

		// get Log File
		/*
		$expDir = $this->cont_obj->getExportDirectory();
		$expLog = new ilLog($expDir, "export.log");
		$expLog->delete();
		$expLog->setLogFormat("");
		$expLog->write(date("[y-m-d H:i:s] ")."Start Export");*/

		// get xml content
		$ilBench->start("ContentObjectExport", "buildPDFFile_getFO");
		$this->cont_obj->exportFO($this->xml,
			$this->export_dir."/".$this->subdir, $expLog);
		$ilBench->stop("ContentObjectExport", "buildPDFFile_getFO");


		// dump fo document to file
		$ilBench->start("ContentObjectExport", "buildPDFFile_dumpToFile");
//echo "dumping:".$this->export_dir."/".$this->filename;
		$this->xml->xmlDumpFile($this->export_dir."/".$this->filename
			, false);
		$ilBench->stop("ContentObjectExport", "buildPDFFile_dumpToFile");

		// convert fo to pdf file
		//$ilBench->start("ContentObjectExport", "buildExportFile_zipFile");
		include_once("classes/class.ilFOPUtil.php");
		ilFOPUtil::makePDF($this->export_dir."/".$this->filename,
			$this->export_dir."/".$this->subdir.".pdf");
		//$ilBench->stop("ContentObjectExport", "buildExportFile_zipFile");

		// destroy writer object
		$this->xml->_XmlWriter;

		//$expLog->write(date("[y-m-d H:i:s] ")."Finished Export");
		$ilBench->stop("ContentObjectExport", "buildPDFFile");
	}

}

?>
