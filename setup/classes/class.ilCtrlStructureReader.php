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
* Class ilCtrlStructureReader
*
* Reads call structure of classes into db
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
*/
class ilCtrlStructureReader
{
	var $class_script;
	var $class_childs;
	var $executed;

	function ilCtrlStructureReader()
	{
		$this->class_script = array();
		$this->class_childs = array();
		$this->executed = false;
	}

	function setErrorObject(&$err)
	{
		$this->err_object =& $err;
	}
	
	/**
	* parse code files and store call structure in db
	*/
	function getStructure()
	{
		$this->get_structure = true;
	}
		
	function readStructure($a_force = false)
	{

		if (!$this->get_structure && !$a_force)
		{
			return;
		}

		// only run one time per db_update request
		if (!$this->executed)
		{
			$this->read(ILIAS_ABSOLUTE_PATH);
			$this->store();
			$this->executed = true;
		}
		
		// read module information
		// not clear whether this is a good place for module reading info
		// or not
		require_once("../classes/class.ilCtrl.php");
		$ctrl = new ilCtrl();
		$ctrl->storeCommonStructures();
	}

	/**
	* read structure into internal variables
	*
	* @access private
	*/
	function read($a_cdir)
	{
		global $ilDB, $lng;
		
		// check wether $a_cdir is a directory
		if (!@is_dir($a_cdir))
		{
			return false;
		}

		// read current directory
		$dir = opendir($a_cdir);

		while($file = readdir($dir))
		{
			if ($file != "." and
				$file != "..")
			{
				// directories
				if (@is_dir($a_cdir."/".$file))
				{
					if ($a_cdir."/".$file != ILIAS_ABSOLUTE_PATH."/data")
					{
						$this->read($a_cdir."/".$file);
					}
				}

				// files
				if (@is_file($a_cdir."/".$file))
				{
					if (eregi("^class.*php$", $file))
					{
						$handle = fopen($a_cdir."/".$file, "r");
//echo "<br>".$a_cdir."/".$file;
						while (!feof($handle)) {
							$line = fgets($handle, 4096);
							$pos = strpos(strtolower($line), "@ilctrl_calls");
							if (is_int($pos))
							{
								$com = substr($line, $pos + 14);
								$pos2 = strpos($com, ":");
								if (is_int($pos2))
								{
									$com_arr = explode(":", $com);
									$parent = strtolower(trim($com_arr[0]));
									
									// check file duplicates
									if ($parent != "" && isset($this->class_script[$parent]) &&
										$this->class_script[$parent] != $a_cdir."/".$file)
									{
										// delete all class to file assignments
										$q = "DELETE FROM ctrl_classfile";
										$ilDB->query($q);
								
										// delete all call entries
										$q = "DELETE FROM ctrl_calls";
										$ilDB->query($q);
										
										$this->err_object->raiseError(
											sprintf($lng->txt("duplicate_ctrl"),
												$parent,
												$this->class_script[$parent],
												$a_cdir."/".$file)
											, $this->err_object->MESSAGE);
									}

									$this->class_script[$parent] = $a_cdir."/".$file;
									$childs = explode(",", $com_arr[1]);
									foreach($childs as $child)
									{
										$child = trim(strtolower($child));
										if (!is_array($this->class_childs[$parent]) || !in_array($child, $this->class_childs[$parent]))
										{
											$this->class_childs[$parent][] = $child;
										}
									}
								}
							}
						}
						fclose($handle);
					}
				}
			}
		}
	}

	/**
	* read structure into internal variables
	*
	* @access private
	*/
	function store($a_cdir = "./..")
	{
		global $ilDB;

		// delete all class to file assignments
		$q = "DELETE FROM ctrl_classfile";
		$ilDB->query($q);

		// delete all call entries
		$q = "DELETE FROM ctrl_calls";
		$ilDB->query($q);

		foreach($this->class_script as $class => $script)
		{
			$file = substr($script, strlen(ILIAS_ABSOLUTE_PATH) + 1);

			// store class to file assignment
			$q = "INSERT INTO ctrl_classfile (class, file) VALUES".
				"(".$ilDB->quote($class).",".$ilDB->quote($file).")";
			$ilDB->query($q);

			if (is_array($this->class_childs[$class]))
			{
				foreach($this->class_childs[$class] as $child)
				{
					// store call entry
					$q = "INSERT INTO ctrl_calls (parent, child) VALUES".
						"(".$ilDB->quote($class).",".$ilDB->quote($child).")";
					$ilDB->query($q);
				}
			}
		}

	}

}
