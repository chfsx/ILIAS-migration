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
* Class ilObjFile
*
* @author Sascha Hofmann <shofmann@databay.de> 
* @version $Id$
*
* @extends ilObject
* @package ilias-core
*/

require_once "class.ilObject.php";

class ilObjFile extends ilObject
{
	var $filename;
	var $filetype;
	var $filepath;
	var $filemaxsize = "20000000";	// not used yet

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjFile($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "file";
		$this->ilObject($a_id,$a_call_by_reference);

		/*if ($a_id != 0)
		{
			$this->read();
		}*/
	}

	function create()
	{
		parent::create();

		$q = "INSERT INTO file_data (file_id,file_name,file_type) VALUES ('".$this->getId()."','".ilUtil::addSlashes($this->getFileName())."','".$this->getFileType()."')";
		$this->ilias->db->query($q);
	}

	function getDirectory()
	{
		return ilUtil::getDataDir()."/files/file_".$this->getId();
	}

	function createDirectory()
	{
		ilUtil::makeDir($this->getDirectory());
	}

	function getUploadFile($a_upload_file, $a_filename)
	{
		$file = $this->getDirectory()."/".$a_filename;
		move_uploaded_file($a_upload_file, $file);
	}

	function copy($a_source,$a_destination)
	{
		return copy($a_source,$this->getDirectory()."/".$a_destination);
	}

	function read()
	{
		parent::read();

		$q = "SELECT * FROM file_data WHERE file_id = '".$this->getId()."'";
		$r = $this->ilias->db->query($q);
		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

		$this->setFileName(ilUtil::stripSlashes($row->file_name));
		$this->setFileType($row->file_type);
		$this->setFilePath($this->getDirectory());
	}

	function update()
	{
		parent::update();

		$q = "UPDATE file_data SET file_name = '".$this->getFileName().
			"', file_type = '".$this->getFiletype()."' ".
			"WHERE file_id = '".$this->getId()."'";
		$this->ilias->db->query($q);
		
		return true;
	}

	function setFileName($a_name)
	{
		$this->filename = $a_name;
	}

	function getFileName()
	{
		return $this->filename;
	}

	function setFilePath($a_path)
	{
		$this->filepath = $a_path;
	}

	function getFilePath()
	{
		return $this->filepath;
	}

	function setFileType($a_type)
	{
		$this->filetype = $a_type;
	}

	function getFileType()
	{
		return $this->filetype;
	}


	function _lookupFileName($a_id)
	{
		global $ilDB;

		$q = "SELECT * FROM file_data WHERE file_id = '".$a_id."'";
		$r = $ilDB->query($q);
		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

		return ilUtil::stripSlashes($row->file_name);
	}


	function _lookupFileSize($a_id)
	{
		global $ilDB;

		$q = "SELECT * FROM file_data WHERE file_id = '".$a_id."'";
		$r = $ilDB->query($q);
		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

		$file = ilUtil::getDataDir()."/files/file_".$a_id."/".$row->file_name;

		if (is_file($file))
		{
			return filesize($file);
		}
		else
		{
			return 0;
		}
	}

	function sendFile()
	{
		$file = $this->getDirectory()."/".$this->getFileName();

		if (@is_file($file))
		{
			// send file
			$file_type = ($this->getFileType() != "")
				? $this->getFileType()
				: "application/octet-stream";
			header("Content-type: ".$file_type);
			header("Content-disposition: attachment; filename=\"".$this->getFileName()."\"");
			//readfile($file);
			$fp = @fopen($file, 'r');

			do
			{
				echo fread($fp, 10000);
			} while (!feof($fp));

			@fclose($fp);

			return true;
		}

		return false;
	}

	function clone()
	{
		// always call parent clone function first!!
		$new_ref_id = parent::clone($a_parent_ref);

		$fileObj =& $this->ilias->obj_factory->getInstanceByRefId($new_ref_id);
		$fileObj->createDirectory();
		
		copy($this->getDirectory()."/".$this->getFileName(),$fileObj->getDirectory()."/".$fileObj->getFileName());

		unset($fileObj);
	
		// ... and finally always return new reference ID!!
		return $new_ref_id;
	}

	/**
	* delete file and all related data	
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{
		// check, if file is used somewhere
		$usages = $this->getUsages();

		if (count($usages) == 0)
		{
			// always call parent delete function first!!
			if (!parent::delete())
			{
				return false;
			}

			// delete file data entry
			$q = "DELETE FROM file_data WHERE file_id = '".$this->getId()."'";
			$this->ilias->db->query($q);

			// unlink file
			$file = $this->getDirectory()."/".$this->getFileName();
			if (@is_file($file))
			{
				unlink($file);
			}

			if (@is_dir($this->getDirectory()))
			{
				rmdir($this->getDirectory());
			}

			return true;
		}

		return false;
	}

	/**
	* export files of object to target directory
	* note: target directory must be the export target directory,
	* "/objects/il_<inst>_file_<file_id>/..." will be appended to this directory
	*
	* @param	string		$a_target_dir		target directory
	*/
	function export($a_target_dir)
	{
		$subdir = "il_".IL_INST_ID."_file_".$this->getId();
		ilUtil::makeDir($a_target_dir."/objects/".$subdir);

		$filedir = $this->getDirectory();
		ilUtil::rCopy($filedir, $a_target_dir."/objects/".$subdir);
	}

	/**
	* static delete all usages of
	*/
	function _deleteAllUsages($a_type, $a_id)
	{
		$q = "DELETE FROM file_usage WHERE usage_type='$a_type' AND usage_id='$a_id'";
		$this->ilias->db->query($q);
	}

	/**
	* save usage
	*/
	function _saveUsage($a_mob_id, $a_type, $a_id)
	{
		$q = "REPLACE INTO file_usage (id, usage_type, usage_id) VALUES".
			" ('$a_mob_id', '$a_type', '$a_id')";
		$this->ilias->db->query($q);
	}

	/**
	* get all usages of file object
	*/
	function getUsages()
	{
		global $ilDB;

		// get usages in learning modules
		$q = "SELECT * FROM file_usage WHERE id = '".$this->getId()."'";
		$us_set = $ilDB->query($q);
		$ret = array();
		while($us_rec = $us_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$ret[] = array("type" => $us_rec["usage_type"],
				"id" => $us_rec["usage_id"]);
		}

		return $ret;
	}

} // END class.ilObjFile
?>
