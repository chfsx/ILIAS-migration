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
require_once "classes/class.ilMetaData.php";

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
	var $meta_data;

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
		if ($a_id == 0)
		{
			$new_meta =& new ilMetaData();
			$this->assignMetaData($new_meta);
		}

	}

	/**
	* create file based lm
	*/
	function create()
	{
		global $ilDB;

		parent::create();
		$this->createDataDirectory();
		$this->meta_data->setId($this->getId());
//echo "<br>title:".$this->getId();
		$this->meta_data->setType($this->getType());
//echo "<br>title:".$this->getType();
		$this->meta_data->setTitle($this->getTitle());
//echo "<br>title:".$this->getTitle();
		$this->meta_data->setDescription($this->getDescription());
		$this->meta_data->setObject($this);
		$this->meta_data->create();

		$q = "INSERT INTO scorm_lm (id, online, api_adapter) VALUES ".
			" (".$ilDB->quote($this->getID()).",".$ilDB->quote("n").",".
			$ilDB->quote("API").")";
		$ilDB->query($q);
	}

	/**
	* read object
	*/
	function read()
	{
		parent::read();
		$this->meta_data =& new ilMetaData($this->getType(), $this->getId());

		$q = "SELECT * FROM scorm_lm WHERE id = '".$this->getId()."'";
		$lm_set = $this->ilias->db->query($q);
		$lm_rec = $lm_set->fetchRow(DB_FETCHMODE_ASSOC);
		$this->setOnline(ilUtil::yn2tf($lm_rec["online"]));
		$this->setAPIAdapterName($lm_rec["api_adapter"]);
		$this->setAPIFunctionsPrefix($lm_rec["api_func_prefix"]);

	}

	/**
	* check wether scorm module is online
	*/
	function _lookupOnline($a_id)
	{
		$q = "SELECT * FROM scorm_lm WHERE id = '".$a_id."'";
		$lm_set = $this->ilias->db->query($q);
		$lm_rec = $lm_set->fetchRow(DB_FETCHMODE_ASSOC);

		return ilUtil::yn2tf($lm_rec["online"]);
	}

	/**
	* get title of content object
	*
	* @return	string		title
	*/
	function getTitle()
	{
		return $this->meta_data->getTitle();
	}

	/**
	* set title of content object
	*
	* @param	string	$a_title		title
	*/
	function setTitle($a_title)
	{
		$this->meta_data->setTitle($a_title);
	}

	/**
	* get description of content object
	*
	* @return	string		description
	*/
	function getDescription()
	{
		return $this->meta_data->getDescription();
	}

	/**
	* set description of content object
	*
	* @param	string	$a_description		description
	*/
	function setDescription($a_description)
	{
		$this->meta_data->setDescription($a_description);
	}

	/**
	* assign a meta data object to content object
	*
	* @param	object		$a_meta_data	meta data object
	*/
	function assignMetaData(&$a_meta_data)
	{
		$this->meta_data =& $a_meta_data;
	}

	/**
	* get meta data object of content object
	*
	* @return	object		meta data object
	*/
	function &getMetaData()
	{
		return $this->meta_data;
	}


	/**
	* creates data directory for package files
	* ("./data/lm_data/lm_<id>")
	*/
	function createDataDirectory()
	{
		$lm_data_dir = ilUtil::getWebspaceDir()."/lm_data";
		ilUtil::makeDir($lm_data_dir);
		ilUtil::makeDir($this->getDataDirectory());
	}

	/**
	* get data directory of lm
	*/
	function getDataDirectory($mode = "filesystem")
	{
		$lm_data_dir = ilUtil::getWebspaceDir($mode)."/lm_data";
		$lm_dir = $lm_data_dir."/lm_".$this->getId();

		return $lm_dir;
	}

	/**
	* update meta data only
	*/
	function updateMetaData()
	{
		$this->meta_data->update();
		if ($this->meta_data->section != "General")
		{
			$meta = $this->meta_data->getElement("Title", "General");
			$this->meta_data->setTitle($meta[0]["value"]);
			$meta = $this->meta_data->getElement("Description", "General");
			$this->meta_data->setDescription($meta[0]["value"]);
		}
		else
		{
			$this->setTitle($this->meta_data->getTitle());
			$this->setDescription($this->meta_data->getDescription());
		}
		parent::update();

	}


	/**
	* update object data
	*
	* @access	public
	* @return	boolean
	*/
	function update()
	{
		$this->updateMetaData();

		$q = "UPDATE scorm_lm SET ".
			" online = '".ilUtil::tf2yn($this->getOnline())."',".
			" api_adapter = '".$this->getAPIAdapterName()."',".
			" api_func_prefix = '".$this->getAPIFunctionsPrefix()."'".
			" WHERE id = '".$this->getId()."'";
		$this->ilias->db->query($q);

		return true;
	}

	/**
	* get api adapter name
	*/
	function getAPIAdapterName()
	{
		return $this->api_adapter;
	}

	/**
	* set api adapter name
	*/
	function setAPIAdapterName($a_api)
	{
		$this->api_adapter = $a_api;
	}

	/**
	* get api functions prefix
	*/
	function getAPIFunctionsPrefix()
	{
		return $this->api_func_prefix;
	}

	/**
	* set api functions prefix
	*/
	function setAPIFunctionsPrefix($a_prefix)
	{
		$this->api_func_prefix = $a_prefix;
	}

	/**
	* get online
	*/
	function setOnline($a_online)
	{
		$this->online = $a_online;
	}

	/**
	* set online
	*/
	function getOnline()
	{
		return $this->online;
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
	* this method has been tested on may 9th 2004
	* meta data, scorm lm data, scorm tree, scorm objects (organization(s),
	* manifest, resources and items), tracking data and data directory
	* have been deleted correctly as desired
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{
		global $ilDB;

		// always call parent delete function first!!
		if (!parent::delete())
		{
			return false;
		}

		// delete meta data of scorm content object
		$nested = new ilNestedSetXML();
		$nested->init($this->getId(), $this->getType());
		$nested->deleteAllDBData();

		// delete data directory
		ilUtil::delDir($this->getDataDirectory());

		// delete scorm learning module record
		$q = "DELETE FROM scorm_lm WHERE id = ".$ilDB->quote($this->getId());
		$this->ilias->db->query($q);

		// remove all scorm objects and scorm tree
		include_once("content/classes/SCORM/class.ilSCORMTree.php");
		include_once("content/classes/SCORM/class.ilSCORMObject.php");
		$sc_tree = new ilSCORMTree($this->getId());
		$items = $sc_tree->getSubTree($sc_tree->getNodeData($sc_tree->readRootId()));
		foreach($items as $item)
		{
			$sc_object =& ilSCORMObject::_getInstance($item["obj_id"]);
			$sc_object->delete();
		}
		$sc_tree->removeTree($sc_tree->getTreeId());


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

	/**
	* get all tracking items of scorm object
	*/
	function getTrackingItems()
	{
		include_once("content/classes/SCORM/class.ilSCORMTree.php");
		$tree = new ilSCORMTree($this->getId());
		$root_id = $tree->readRootId();

		$items = array();
		$childs = $tree->getSubTree($tree->getNodeData($root_id));
		foreach($childs as $child)
		{
			if($child["type"] == "sit")
			{
				include_once("content/classes/SCORM/class.ilSCORMItem.php");
				$sc_item =& new ilSCORMItem($child["obj_id"]);
				if ($sc_item->getIdentifierRef() != "")
				{
					$items[count($items)] =& $sc_item;
				}
			}
		}

		return $items;
	}

	/**
	* get all tracked items of current user
	*/
	function getTrackedItems()
	{
		global $ilUser, $ilDB, $ilUser;

		$query = "SELECT DISTINCT sco_id FROM scorm_tracking2 WHERE".
			//" user_id = ".$ilDB->quote($ilUser->getId()).
			" ref_id = ".$ilDB->quote($this->getRefId());

		$sco_set = $ilDB->query($query);

		$items = array();
		while($sco_rec = $sco_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			include_once("content/classes/SCORM/class.ilSCORMItem.php");
			$sc_item =& new ilSCORMItem($sco_rec["sco_id"]);
			if ($sc_item->getIdentifierRef() != "")
			{
				$items[count($items)] =& $sc_item;
			}
		}

		return $items;
	}

	function getTrackingData2($a_sco_id)
	{
		global $ilDB;
		
		$query = "SELECT * FROM scorm_tracking2 WHERE".
			" ref_id = ".$ilDB->quote($this->getRefId()).
			" AND sco_id = ".$ilDB->quote($a_sco_id).
			" ORDER BY user_id, lvalue";
		$data_set = $ilDB->query($query);

		$data = array();
		while($data_rec = $data_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$data[] = $data_rec;
		}

		return $data;
	}

} // END class.ilObjSCORMLearningModule
?>
