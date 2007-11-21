<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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


require_once "./Modules/ScormAicc/classes/class.ilObjSCORMLearningModule.php";

/**
* Class ilObjSCORM2004LearningModule
*
* @author Alex Killing <alex.killing@gmx.de>
* $Id: class.ilObjSCORMLearningModule.php 13123 2007-01-29 13:57:16Z smeyer $
*
* @ingroup ModulesScormAicc
*/
class ilObjSCORM2004LearningModule extends ilObjSCORMLearningModule
{
	var $validator;
//	var $meta_data;

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjSCORM2004LearningModule($a_id = 0, $a_call_by_reference = true)
	{
		$this->type = "sahs";
		parent::ilObject($a_id,$a_call_by_reference);
	}


	/**
	* Validate all XML-Files in a SCOM-Directory
	*
	* @access       public
	* @return       boolean true if all XML-Files are wellfomred and valid
	*/
	function validate($directory)
	{
		//$this->validator =& new ilObjSCORMValidator($directory);
		//$returnValue = $this->validator->validate();
		return true;
	}


	/**
	* read manifest file
	* @access	public
	*/
	function readObject()
	{
		
		// the seems_utf8($str) function
		include_once("include/inc.utf8checker.php");
		$needs_convert = false;

		// convert imsmanifest.xml file in iso to utf8 if needed
		// include_once("include/inc.convertcharset.php");
		$manifest_file = $this->getDataDirectory()."/imsmanifest.xml";

		// check if manifestfile exists and space left on device...
		$check_for_manifest_file = is_file($manifest_file);

		
			
		// if no manifestfile
		if (!$check_for_manifest_file)
		{
			$this->ilias->raiseError($this->lng->txt("Manifestfile $manifest_file not found!"), $this->ilias->error_obj->MESSAGE);
			return;
		}

		
		if ($check_for_manifest_file)
		{
			$manifest_file_array = file($manifest_file);
			
			foreach($manifest_file_array as $mfa)
			{
					
				if (!seems_not_utf8($mfa))
				{
					$needs_convert = true;
					break;
				}
			}
						
			
							
			// to copy the file we need some extraspace, counted in bytes *2 ... we need 2 copies....
			$estimated_manifest_filesize = filesize($manifest_file) * 2;
			
			// i deactivated this, because it seems to fail on some windows systems (see bug #1795)
			//$check_disc_free = disk_free_space($this->getDataDirectory()) - $estimated_manifest_filesize;
			$check_disc_free = 2;
		}

		
	
		// if $manifest_file needs to be converted to UTF8
		if ($needs_convert)
		{
			// if file exists and enough space left on device
			if ($check_for_manifest_file && ($check_disc_free > 1))
			{

				// create backup from original
				if (!copy($manifest_file, $manifest_file.".old"))
				{
					echo "Failed to copy $manifest_file...<br>\n";
				}

				// read backupfile, convert each line to utf8, write line to new file
				// php < 4.3 style
				$f_write_handler = fopen($manifest_file.".new", "w");
				$f_read_handler = fopen($manifest_file.".old", "r");
				while (!feof($f_read_handler))
				{
					$zeile = fgets($f_read_handler);
					//echo mb_detect_encoding($zeile);
					fputs($f_write_handler, utf8_encode($zeile));
				}
				fclose($f_read_handler);
				fclose($f_write_handler);

				// copy new utf8-file to imsmanifest.xml
				if (!copy($manifest_file.".new", $manifest_file))
				{
					echo "Failed to copy $manifest_file...<br>\n";
				}

				if (!@is_file($manifest_file))
				{
					$this->ilias->raiseError($this->lng->txt("cont_no_manifest"),
					$this->ilias->error_obj->WARNING);
				}
			}
			else
			{
				// gives out the specific error

				if (!($check_disc_free > 1))
					$this->ilias->raiseError($this->lng->txt("Not enough space left on device!"),$this->ilias->error_obj->MESSAGE);
					return;
			}

		}
		else
		{
			// check whether file starts with BOM (that confuses some sax parsers, see bug #1795)
			$hmani = fopen($manifest_file, "r");
			$start = fread($hmani, 3);
			if (strtolower(bin2hex($start)) == "efbbbf")
			{
				$f_write_handler = fopen($manifest_file.".new", "w");
				while (!feof($hmani))
				{
					$n = fread($hmani, 900);
					fputs($f_write_handler, $n);
				}
				fclose($f_write_handler);
				fclose($hmani);

				// copy new utf8-file to imsmanifest.xml
				if (!copy($manifest_file.".new", $manifest_file))
				{
					echo "Failed to copy $manifest_file...<br>\n";
				}
			}
			else
			{
				fclose($hmani);
			}
		}

		//validate the XML-Files in the SCORM-Package
		if ($_POST["validate"] == "y")
		{
			if (!$this->validate($this->getDataDirectory()))
			{
				$this->ilias->raiseError("<b>Validation Error(s):</b><br>".$this->getValidationSummary(),
					$this->ilias->error_obj->WARNING);
			}
		}
			
		// start SCORM 2004 package parser/importer
		include_once ("./Modules/Scorm2004/classes/ilSCORM13Package.php");
		$newPack = new ilSCORM13Package();
		return $newPack->il_import($this->getDataDirectory(),$this->getId(),$this->ilias);
	}

	/**
	* get all tracked items of current user
	*/
	function getTrackedUsers()
	{
		global $ilUser, $ilDB, $ilUser;

		$query = "SELECT DISTINCT user_id FROM cmi_node, cp_node WHERE".
			" cmi_node.cp_node_id = cp_node.cp_node_id ".
			" AND cp_node.slm_id = ".$ilDB->quote($this->getId());

		$sco_set = $ilDB->query($query);

		$items = array();
		while($sco_rec = $sco_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$name = ilObjUser::_lookupName($sco_rec["user_id"]);
			$items[] = array("user_full_name" => $name["lastname"].", ".
				$name["firstname"]." [".ilObjUser::_lookupLogin($sco_rec["user_id"])."]",
				"user_id" => $sco_rec["user_id"]);
		}

		return $items;
	}

	/**
	* get all tracked items of current user
	*/
	function deleteTrackingDataOfUsers($a_users)
	{
		global $ilDB;
		
		foreach($a_users as $user)
		{
			$q = "DELETE FROM cmi_node WHERE user_id = ".$ilDB->quote($user).
				" AND cp_node_id IN (SELECT cp_node_id FROM cp_node WHERE slm_id = ".
				$ilDB->quote($this->getId()).")";
			$ilDB->query($q);
		}
	}
	
	/**
	* get all tracking items of scorm object
	*
	* currently a for learning progress only
	*
	* @access static
	*/
	function _getTrackingItems($a_obj_id)
	{
		global $ilDB;
		
		$q = "SELECT cp_item.cp_node_id as id, cp_item.title as title FROM cp_node, cp_item, cp_resource WHERE slm_id = ".
			$ilDB->quote($a_obj_id).
			" AND cp_node.cp_node_id = cp_item.cp_node_id ".
			" AND cp_item.resourceId = cp_resource.id ".
			" AND cp_resource.scormType = 'sco' ".
			" ORDER BY id ";

		$item_set = $ilDB->query($q);
			
		$items = array();
		while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$items[] = array("id" => $item_rec["id"],
				"title" => $item_rec["title"]);
		}

		return $items;
	}


} // END class.ilObjSCORMLearningModule
?>
