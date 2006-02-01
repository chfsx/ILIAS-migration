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
* This class handles all operations on files for the exercise object
*  
* @author	Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* @package	ilias-mail
*/
require_once("classes/class.ilFileData.php");
				
class ilFileDataExercise extends ilFileData
{
	/**
	* obj_id
	* @var integer obj_id of exercise object
	* @access private
	*/
	var $obj_id;

	/**
	* path of exercise directory
	* @var string path
	* @access private
	*/
	var $exercise_path;

	/**
	* Constructor
	* call base constructors
	* checks if directory is writable and sets the optional obj_id
	* @param integereger obj_id
	* @access	public
	*/
	function ilFileDataExercise($a_obj_id = 0)
	{
		define('EXERCISE_PATH','exercise');
		parent::ilFileData();
		$this->exercise_path = parent::getPath()."/".EXERCISE_PATH;
		
		// IF DIRECTORY ISN'T CREATED CREATE IT
		if(!$this->__checkPath())
		{
			$this->__initDirectory();
		}
		$this->obj_id = $a_obj_id;
	}

	function getObjId()
	{
		return $this->obj_id;
	}

	/**
	* get exercise path 
	* @access	public
	* @return string path
	*/
	function getExercisePath()
	{
		return $this->exercise_path;
	}

	function getFiles()
	{
		$files = array();
		$dp = opendir($this->exercise_path);

		while($file = readdir($dp))
		{
			if(is_dir($file))
			{
				continue;
			}
			list($obj_id,$rest) = split('_',$file,2);
			if($obj_id == $this->obj_id)
			{
				if(!is_dir($this->exercise_path.'/'.$file))
				{
					$files[] = array(
						'name'     => $rest,
						'size'     => filesize($this->exercise_path.'/'.$file),
						'ctime'    => ilFormat::formatDate(date('Y-m-d H:i:s',filectime($this->exercise_path.'/'.$file))));
				}
			}
		}
		closedir($dp);
		return $files;
	}

	function ilClone($a_new_obj_id)
	{
		foreach($this->getFiles() as $file)
		{
			@copy($this->getExercisePath()."/".$this->obj_id.'_'.$file["name"],
				  $this->getExercisePath()."/".$a_new_obj_id.'_'.$file["name"]);
		}
		return true;
	}
	function delete()
	{
		foreach($this->getFiles() as $file)
		{
			$this->unlinkFile($file["name"]);
		}
		
		$delivered_file_path = $this->getExercisePath() . "/" . $this->obj_id . "/";
		if (is_dir($delivered_file_path))
		{
			system("rm -rf " . ilUtil::escapeShellArg($delivered_file_path));
		}
		
		return true;
	}

	/**
	* store uploaded file in filesystem
	* @param array HTTP_POST_FILES
	* @access	public
	* @return bool
	*/
	function storeUploadedFile($a_http_post_file, $secure_filename = false)
	{
		// TODO: 
		// CHECK UPLOAD LIMIT
		// 
		$filename = $a_http_post_file['name'];
		if ($secure_filename)
		{
			// replace whitespaces with underscores
			$filename = preg_replace("/\s/", "_", $filename);
			// remove all special characters
			$filename = preg_replace("/[^_a-zA-Z0-9\.]/", "", $filename);
		}
		if(isset($a_http_post_file) && $a_http_post_file['size'])
		{
			// CHECK IF FILE WITH SAME NAME EXISTS
			$this->__rotateFiles($this->getExercisePath().'/'.$this->obj_id.'_'.$filename);
			//move_uploaded_file($a_http_post_file['tmp_name'],$this->getExercisePath().'/'.$this->obj_id.'_'.
			//				   $filename);
			ilUtil::moveUploadedFile($a_http_post_file['tmp_name'], $a_http_post_file['name'],
				$this->getExercisePath().'/'.$this->obj_id.'_'.$filename);

		}
		return true;
	}

	/**
	* store delivered file in filesystem
	* @param array HTTP_POST_FILES
	* @param numeric database id of the user who delivered the file
	* @access	public
	* @return mixed Returns a result array with filename and mime type of the saved file, otherwise false
	*/
	function deliverFile($a_http_post_file, $user_id)
	{
		// TODO: 
		// CHECK UPLOAD LIMIT
		// 
		$result = false;
		if(isset($a_http_post_file) && $a_http_post_file['size'])
		{
			$filename = $a_http_post_file['name'];
			// replace whitespaces with underscores
			$filename = preg_replace("/\s/", "_", $filename);
			// remove all special characters
			$filename = preg_replace("/[^_a-zA-Z0-9\.]/", "", $filename);

			if(!is_dir($savepath = $this->getExercisePath()."/".$this->obj_id))
			{
				ilUtil::makeDir($savepath);
			}
			$savepath .= '/' .$user_id;
			if(!is_dir($savepath))
			{
				ilUtil::makeDir($savepath);
			}				

			// CHECK IF FILE PATH EXISTS
			if (!is_dir($savepath))
			{
				require_once "./classes/class.ilUtil.php";
				#ilUtil::makeDirParents($savepath);
				ilUtil::makeDir($savepath);
			}
			$now = getdate();
			$prefix = sprintf("%04d%02d%02d%02d%02d%02d", $now["year"], $now["mon"], $now["mday"], $now["hours"], 
							  $now["minutes"], $now["seconds"]);
			//move_uploaded_file($a_http_post_file["tmp_name"], $savepath . $prefix . "_" . $filename);
			ilUtil::moveUploadedFile($a_http_post_file["tmp_name"], $a_http_post_file["name"],
				$savepath . $prefix . "_" . $filename);
			require_once "./content/classes/Media/class.ilObjMediaObject.php";
			$result = array(
				"filename" => $prefix . "_" . $filename,
				"fullname" => $savepath . $prefix . "_" . $filename,
				"mimetype" =>	ilObjMediaObject::getMimeType($savepath . $prefix . "_" . $filename)
			);
		}
		return $result;
	}
	
	function downloadAllDeliveredFiles($members)
	{
		require_once "./classes/class.ilUtil.php";
		global $lng;
		
		ksort($members);
		$tmpfile = ilUtil::ilTempnam();
		$fh = fopen($tmpfile, "w");

		if ($fh)
		{
			foreach ($members as $id => $member)
			{
				fwrite($fh, "$id\t$member\n");
			}
			fclose($fh);
		}

		$savepath = $this->getExercisePath() . "/" . $this->obj_id . "/";
		copy($tmpfile, $savepath . "users.txt");
		$cdir = getcwd();

		// important check: if the directory does not exist
		// ILIAS stays in the current directory (echoing only a warning)
		// and the zip command below archives the whole ILIAS directory
		// (including the data directory) and sends a mega file to the user :-o
		if (!is_dir($savepath))
		{
			return;
		}
		// Safe mode fix
		chdir($this->getExercisePath());
		$zip = PATH_TO_ZIP;
		$tmpfile = ilUtil::ilTempnam();
		$tmpzipfile = $tmpfile . ".zip";
		// Safe mode fix
		$zipcmd = $zip." -r ".ilUtil::escapeShellArg($tmpzipfile)." ".$this->obj_id;
		exec($zipcmd);
		ilUtil::deliverFile($tmpzipfile, strtolower($lng->txt("excs")) . ".zip");
		chdir($cdir);
		unlink($savepath . "users.txt");
		unlink($tmpfile);
		unlink($tmpzipfile);
	}
	
	/**
	* unlink files: expects an array of filenames e.g. array('foo','bar')
	* @param array filenames to delete
	* @access	public
	* @return string error message with filename that couldn't be deleted
	*/
	function unlinkFiles($a_filenames)
	{
		if(is_array($a_filenames))
		{
			foreach($a_filenames as $file)
			{
				if(!$this->unlinkFile($file))
				{
					return $file;
				}
			}
		}
		return '';
	}
	/**
	* unlink one uploaded file expects a filename e.g 'foo'
	* @param string filename to delete
	* @access	public
	* @return bool
	*/
	function unlinkFile($a_filename)
	{
		if(file_exists($this->exercise_path.'/'.$this->obj_id.'_'.$a_filename))
		{
			return unlink($this->exercise_path.'/'.$this->obj_id.'_'.$a_filename);
		}
	}
	/**
	* get absolute path of filename
	* @param string relative path
	* @access	public
	* @return string absolute path
	*/
	function getAbsolutePath($a_path)
	{
		return $this->exercise_path.'/'.$this->obj_id.'_'.$a_path;
	}

	/**
	* check if files exist
	* @param array filenames to check
	* @access	public
	* @return bool
	*/
	function checkFilesExist($a_files)
	{
		if($a_files)
		{
			foreach($a_files as $file)
			{
				if(!file_exists($this->exercise_path.'/'.$this->obj_id.'_'.$file))
				{
					return false;
				}
			}
			return true;
		}
		return true;
	}

	// PRIVATE METHODS
	function __checkPath()
	{
		if(!@file_exists($this->getExercisePath()))
		{
			return false;
		}
		$this->__checkReadWrite();

		return true;
	}
	/**
	* check if directory is writable
	* overwritten method from base class
	* @access	private
	* @return bool
	*/
	function __checkReadWrite()
	{
		if(is_writable($this->exercise_path) && is_readable($this->exercise_path))
		{
			return true;
		}
		else
		{
			$this->ilias->raiseError("Exercise directory is not readable/writable by webserver",$this->ilias->error_obj->FATAL);
		}
	}
	/**
	* init directory
	* overwritten method
	* @access	public
	* @return string path
	*/
	function __initDirectory()
	{
		if(is_writable($this->getPath()))
		{
			return ilUtil::makeDir($this->excercise_path = $this->getPath().'/'.EXERCISE_PATH);
		}
		return false;
	}
	/**
	* rotate files with same name
	* recursive method
	* @param string filename
	* @access	private
	* @return bool
	*/
	function __rotateFiles($a_path)
	{
		if(file_exists($a_path))
		{
			$this->__rotateFiles($a_path.".old");
			return rename($a_path,$a_path.'.old');
		}
		return true;
	}
}
