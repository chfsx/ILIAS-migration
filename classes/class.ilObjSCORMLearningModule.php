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


require_once "classes/class.ilObject.php";
require_once "classes/class.ilObjSCORMValidator.php";
//require_once "classes/class.ilMetaData.php";  //we need that later

/**
* Class ilObjSCORMLearningModule
*
* @author Alex Killing <alex.killing@gmx.de>
* $Id$
*
* @extends ilObject
* @package ilias-core
*/
class ilObjSCORMLearningModule extends ilObject
{
	var $validator;

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjSCORMLearningModule($a_id = 0, $a_call_by_reference = true)
	{
		$this->type = "slm";

		parent::ilObject($a_id,$a_call_by_reference);
	}

	/**
	* creates data directory for package files
	* (webspace_dir/lm_data/lm_<id>, depending on webspace
	* directory that is set in ILIAS setup/ini)
	*/
	function createDataDirectory()
	{
		$lm_data_dir = ilUtil::getWebspaceDir()."/lm_data";
		if(!is_writable($lm_data_dir))
		{
			$this->ilias->raiseError("LM Data Directory (".$lm_data_dir
				.") not writeable.",$this->ilias->error_obj->FATAL);
		}
		$lm_dir = $lm_data_dir."/lm_".$this->getId();
		@mkdir($lm_dir);
		@chmod($lm_dir,0755);
		if(!@is_dir($lm_dir))
		{
			$this->ilias->raiseError("Creation of Data Directory failed.",$this->ilias->error_obj->FATAL);
		}
	}

	/**
	* get data directory of lm
	*/
	function getDataDirectory()
	{
		$lm_data_dir = ilUtil::getWebspaceDir()."/lm_data";

		$lm_dir = $lm_data_dir."/lm_".$this->getId();
		if(@is_dir($lm_dir))
		{
			return $lm_dir;
		}
		else
		{
			return false;
		}
	}

	function getWebDirectory() {
	        $lm_data_dir = "/lm_data";

                $lm_dir = $lm_data_dir."/lm_".$this->getId();
                        return $lm_dir;
                        return false;
	}

	/**
	* copy all properties and subobjects of a SCROM LearningModule.
	*
	* @access	public
	* @return	integer	new ref id
	*/
	function clone($a_parent_ref)
	{		
		global $rbacadmin;

		// always call parent clone function first!!
		$new_ref_id = parent::clone($a_parent_ref);
		
		// put here slm specific stuff

		// ... and finally always return new reference ID!!
		return $new_ref_id;
	}

	/**
	* delete SCORM learning module and all related data	
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{		
		// always call parent delete function first!!
		if (!parent::delete())
		{
			return false;
		}

		// put here SCORM learning module specific stuff
		
		// always call parent delete function at the end!!
		return true;
	}


	/**
	* Validate all XML-Files in a SCOM-Directory
	*
	* @access       public
	* @return       boolean true if all XML-Files are wellfomred and valid
	*/
	function validate($directory)
	{
		$this->validator =& new ilObjSCORMValidator($directory);
		$returnValue = $this->validator->validate();
		return $returnValue;
	}

	function getValidationSummary()
	{
		if(is_object($this->validator))
		{
			return $this->validator->getSummary();
		}
		return "";
	}

	/**
	* notifys an object about an event occured
	* Based on the event happend, each object may decide how it reacts.
	*
	* @access	public
	* @param	string	event
	* @param	integer	reference id of object where the event occured
	* @param	array	passes optional paramters if required
	* @return	boolean
	*/
	function notify($a_event,$a_ref_id,$a_parent_non_rbac_id,$a_node_id,$a_params = 0)
	{
		global $tree;
		
		switch ($a_event)
		{
			case "link":
				
				//var_dump("<pre>",$a_params,"</pre>");
				//echo "SCORMLearningModule ".$this->getRefId()." triggered by link event. Objects linked into target object ref_id: ".$a_ref_id;
				//exit;
				break;
			
			case "cut":
				
				//echo "SCORMLearningModule ".$this->getRefId()." triggered by cut event. Objects are removed from target object ref_id: ".$a_ref_id;
				//exit;
				break;
				
			case "copy":
			
				//var_dump("<pre>",$a_params,"</pre>");
				//echo "SCORMLearningModule ".$this->getRefId()." triggered by copy event. Objects are copied into target object ref_id: ".$a_ref_id;
				//exit;
				break;

			case "paste":
				
				//echo "SCORMLearningModule ".$this->getRefId()." triggered by paste (cut) event. Objects are pasted into target object ref_id: ".$a_ref_id;
				//exit;
				break;
			
			case "new":
				
				//echo "SCORMLearningModule ".$this->getRefId()." triggered by paste (new) event. Objects are applied to target object ref_id: ".$a_ref_id;
				//exit;
				break;
		}
		
		// At the beginning of the recursive process it avoids second call of the notify function with the same parameter
		if ($a_node_id==$_GET["ref_id"])
		{	
			$parent_obj =& $this->ilias->obj_factory->getInstanceByRefId($a_node_id);
			$parent_type = $parent_obj->getType();
			if($parent_type == $this->getType())
			{
				$a_node_id = (int) $tree->getParentId($a_node_id);
			}
		}
		
		parent::notify($a_event,$a_ref_id,$a_parent_non_rbac_id,$a_node_id,$a_params);
	}
} // END class.ilObjSCORMLearningModule
?>
