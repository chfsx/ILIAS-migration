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
* Class ilObject
* Basic functions for all objects
*
* @author Stefan Meyer <smeyer@databay.de>
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package ilias-core
*/
class ilObject
{
	/**
	* ilias object
	* @var		object ilias
	* @access	private
	*/
	var $ilias;

	/**
	* lng object
	* @var		object language
	* @access	private
	*/
	var $lng;

	/**
	* object id
	* @var		integer object id of object itself
	* @access	private
	*/
	var $id;	// true object_id!!!!
	var $ref_id;// reference_id
	var $type;
	var $title;
	var $desc;
	var $owner;
	var $create_date;
	var $last_update;
	var $import_id;
	var $register = false;		// registering required for object? set to true to implement a subscription interface

	/**
	* indicates if object is a referenced object
	* @var		boolean
	* @access	private
	*/
	var $referenced;

	/**
	* object list
	* @var		array	contains all child objects of current object
	* @access	private
	*/
	var $objectList;

	/**
	* max title length
	* @var integer
	*/
	var $max_title;

	/**
	* max description length
	* @var integer
	*/
	var $max_desc;

	/**
	* add dots to shortened titles and descriptions
	* @var boolean
	*/
	var $add_dots;

	/**
	* object_data record
	*/
	var $obj_data_record;

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObject($a_id = 0, $a_reference = true)
	{
		global $ilias, $lng, $ilBench;

		$ilBench->start("Core", "ilObject_Constructor");

		if (DEBUG)
		{
			echo "<br/><font color=\"red\">type(".$this->type.") id(".$a_id.") referenced(".$a_reference.")</font>";
		}

		$this->ilias =& $ilias;
		$this->lng =& $lng;

		$this->max_title = MAXLENGTH_OBJ_TITLE;
		$this->max_desc = MAXLENGTH_OBJ_DESC;
		$this->add_dots = true;

		$this->referenced = $a_reference;
		$this->call_by_reference = $a_reference;

		if ($a_id == 0)
		{
			$this->referenced = false;		// newly created objects are never referenced
		}									// they will get referenced if createReference() is called

		if ($this->referenced)
		{
			$this->ref_id = $a_id;
		}
		else
		{
			$this->id = $a_id;
		}
		// read object data
		if ($a_id != 0)
		{
			$this->read();
		}

		$ilBench->stop("Core", "ilObject_Constructor");
	}

	/**
	* determines wehter objects are referenced or not (got ref ids or not)
	*/
	function withReferences()
	{
		// both vars could differ. this method should always return true if one of them is true without changing their status
		return ($this->call_by_reference) ? true : $this->referenced;
	}


	/**
	* read object data from db into object
	* @param	boolean
	* @access	public
	*/
	function read($a_force_db = false)
	{
		global $objDefinition, $ilBench;

		$ilBench->start("Core", "ilObject_read");

		if (isset($this->obj_data_record) && !$a_force_db)
		{
			$obj = $this->obj_data_record;
		}
		else if ($this->referenced)
		{
			// check reference id
			if (!isset($this->ref_id))
			{
				$message = "ilObject::read(): No ref_id given!";
				$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
			}

			// read object data
			$ilBench->start("Core", "ilObject_read_readData");
			/* old query (very slow)
			$q = "SELECT * FROM object_data ".
				 "LEFT JOIN object_reference ON object_data.obj_id=object_reference.obj_id ".
				 "WHERE object_reference.ref_id='".$this->ref_id."'"; */
			$q = "SELECT * FROM object_data, object_reference WHERE object_data.obj_id=object_reference.obj_id ".
				 "AND object_reference.ref_id='".$this->ref_id."'";
			$object_set = $this->ilias->db->query($q);
			$ilBench->stop("Core", "ilObject_read_readData");

			// check number of records
			if ($object_set->numRows() == 0)
			{
				$message = "ilObject::read(): Object with ref_id ".$this->ref_id." not found!";
				$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
			}

			$obj = $object_set->fetchRow(DB_FETCHMODE_ASSOC);
		}
		else
		{
			// check object id
			if (!isset($this->id))
			{
				$message = "ilObject::read(): No obj_id given!";
				$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
			}

			// read object data
			$q = "SELECT * FROM object_data ".
				 "WHERE obj_id = '".$this->id."'";
			$object_set = $this->ilias->db->query($q);

			// check number of records
			if ($object_set->numRows() == 0)
			{
				$message = "ilObject::read(): Object with obj_id: ".$this->id." not found!";
				$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
			}

			$obj = $object_set->fetchRow(DB_FETCHMODE_ASSOC);
		}

		$this->id = $obj["obj_id"];
		$this->type = $obj["type"];
		$this->title = $obj["title"];
		$this->desc = $obj["description"];
		$this->owner = $obj["owner"];
		$this->create_date = $obj["create_date"];
		$this->last_update = $obj["last_update"];
		$this->import_id = $obj["import_id"];

		// multilingual support systemobjects (sys) & categories (db)
		$ilBench->start("Core", "ilObject_Constructor_getTranslation");
		$translation_type = $objDefinition->getTranslationType($this->type);

		if ($translation_type == "sys")
		{
			$this->title = $this->lng->txt("obj_".$this->type);
			$this->desc = $this->lng->txt("obj_".$this->type."_desc");
		}
		elseif ($translation_type == "db")
		{
			$q = "SELECT title,description FROM object_translation ".
				 "WHERE obj_id = ".$this->id." ".
				 "AND lang_code = '".$this->ilias->account->getPref("language")."' ".
				 "AND NOT lang_default = 1";
			$r = $this->ilias->db->query($q);
			$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

			if ($row)
			{
				$this->title = $row->title;
				$this->desc = $row->description;
			}
		}

		$ilBench->stop("Core", "ilObject_Constructor_getTranslation");

		$ilBench->stop("Core", "ilObject_read");
	}

	/**
	* get object id
	* @access	public
	* @return	integer	object id
	*/
	function getId()
	{
		return $this->id;
	}

	/**
	* set object id
	* @access	public
	* @param	integer	$a_id		object id
	*/
	function setId($a_id)
	{
		$this->id = $a_id;
	}

	/**
	* set reference id
	* @access	public
	* @param	integer	$a_id		reference id
	*/
	function setRefId($a_id)
	{
		$this->ref_id = $a_id;
		$this->referenced = true;
	}

	/**
	* get reference id
	* @access	public
	* @return	integer	reference id
	*/
	function getRefId()
	{
		return $this->ref_id;
	}

	/**
	* get object type
	* @access	public
	* @return	string		object type
	*/
	function getType()
	{
		return $this->type;
	}

	/**
	* set object type
	* @access	public
	* @param	integer	$a_type		object type
	*/
	function setType($a_type)
	{
		$this->type = $a_type;
	}

	/**
	* get object title
	* @access	public
	* @return	string		object title
	*/
	function getTitle()
	{
		return $this->title;
	}

	/**
	* set object title
	*
	* @access	public
	* @param	string		$a_title		object title
	*/
	function setTitle($a_title)
	{
		if ($a_title == "")
		{
			$a_title = "NO TITLE";
		}

		$this->title = ilUtil::shortenText($a_title, $this->max_title, $this->add_dots);
	}

	/**
	* get object description
	*
	* @access	public
	* @return	string		object description
	*/
	function getDescription()
	{
		return $this->desc;
	}

	/**
	* set object description
	*
	* @access	public
	* @param	string		$a_desc		object description
	*/
	function setDescription($a_desc)
	{
		$this->desc = ilUtil::shortenText($a_desc, $this->max_desc, $this->add_dots);
	}

	/**
	* get import id
	*
	* @access	public
	* @return	string	import id
	*/
	function getImportId()
	{
		return $this->import_id;
	}

	/**
	* set import id
	*
	* @access	public
	* @param	string		$a_import_id		import id
	*/
	function setImportId($a_import_id)
	{
		$this->import_id = $a_import_id;
	}

	/**
	* get object owner
	*
	* @access	public
	* @return	integer	owner id
	*/
	function getOwner()
	{
		return $this->owner;
	}

	/*
	* get full name of object owner
	*
	* @access	public
	* @return	string	owner name or unknown
	*/
	function getOwnerName()
	{
		if ($this->getOwner() != -1)
		{
			$owner = new ilObjUser($this->getOwner());
		}

		if (is_object($owner))
		{
			$own_name = $owner->getFullname();
		}
		else
		{
			$own_name = $this->lng->txt("unknown");
		}

		return $own_name;
	}

	/**
	* set object owner
	*
	* @access	public
	* @param	integer	$a_owner	owner id
	*/
	function setOwner($a_owner)
	{
		$this->owner = $a_owner;
	}

	/**
	* get create date
	* @access	public
	* @return	string		creation date
	*/
	function getCreateDate()
	{
		return $this->create_date;
	}

	/**
	* get last update date
	* @access	public
	* @return	string		date of last update
	*/
	function getLastUpdateDate()
	{
		return $this->last_update;
	}

	/**
	* set object_data record (note: this method should
	* only be called from the ilObjectFactory class)
	*
	* @param	array	$a_record	assoc. array from table object_data
	* @access	public
	* @return	integer	object id
	*/
	function setObjDataRecord($a_record)
	{
		$this->obj_data_record = $a_record;
	}

	/**
	* create
	*
	* note: title, description and type should be set when this function is called
	*
	* @access	public
	* @return	integer		object id
	*/
	function create()
	{
		global $ilDB;

		if (!isset($this->type))
		{
			$message = get_class($this)."::create(): No object type given!";
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$this->title = ilUtil::shortenText($this->getTitle(), $this->max_title, $this->add_dots);
		$this->desc = ilUtil::shortenText($this->getDescription(), $this->max_desc, $this->add_dots);

		$q = "INSERT INTO object_data ".
			 "(type,title,description,owner,create_date,last_update,import_id) ".
			 "VALUES ".
			 "('".$this->type."',".$ilDB->quote($this->getTitle()).",'".ilUtil::prepareDBString($this->getDescription())."',".
			 "'".$this->ilias->account->getId()."',now(),now(),'".$this->getImportId()."')";
		//$this->ilias->db->query($q);
		$ilDB->query($q);

		$this->id = $ilDB->getLastInsertId();

		// the line ($this->read();) messes up meta data handling: meta data,
		// that is not saved at this time, gets lost, so we query for the dates alone
		//$this->read();
		$q = "SELECT last_update, create_date FROM object_data".
			 " WHERE obj_id = '".$this->id."'";
		$obj_set = $this->ilias->db->query($q);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);
		$this->last_update = $obj_rec["last_update"];
		$this->create_date = $obj_rec["create_date"];

		// set owner for new objects
		$this->setOwner($this->ilias->account->getId());

		return $this->id;
	}

	/**
	* update object in db
	*
	* @access	public
	* @return	boolean	true on success
	*/
	function update()
	{

		$q = "UPDATE object_data ".
			"SET ".
			"title = '".ilUtil::prepareDBString($this->getTitle())."',".
			"description = '".ilUtil::prepareDBString($this->getDescription())."', ".
			"import_id = '".$this->getImportId()."', ".
			"last_update = now() ".
			"WHERE obj_id = '".$this->getId()."'";
		$this->ilias->db->query($q);

		// the line ($this->read();) messes up meta data handling: meta data,
		// that is not saved at this time, gets lost, so we query for the dates alone
		//$this->read();
		$q = "SELECT last_update FROM object_data".
			 " WHERE obj_id = '".$this->getId()."'";
		$obj_set = $this->ilias->db->query($q);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);
		$this->last_update = $obj_rec["last_update"];

		return true;
	}

	/**
	* get current object id for import id (static)
	*
	* @param	int		$a_import_id		import id
	*
	* @return	int		id
	*/
	function _getIdForImportId($a_import_id)
	{
		$q = "SELECT * FROM object_data WHERE import_id = '".$a_import_id."'".
			" ORDER BY create_date DESC LIMIT 1";
		$obj_set = $this->ilias->db->query($q);

		if ($obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			return $obj_rec["obj_id"];
		}
		else
		{
			return 0;
		}
	}

	/**
	* get all reference ids of object
	*
	* @param	int		$a_id		object id
	*/
	function _getAllReferences($a_id)
	{
		global $ilDB;

		$q = "SELECT * FROM object_reference WHERE obj_id = '".$a_id."'";
		$obj_set = $ilDB->query($q);
		$ref = array();

		while ($obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$ref[$obj_rec["ref_id"]] = $obj_rec["ref_id"];
		}

		return $ref;
	}

	/**
	* lookup object title
	*
	* @param	int		$a_id		object id
	*/
	function _lookupTitle($a_id)
	{
		global $ilDB;

		$q = "SELECT title FROM object_data WHERE obj_id = '".$a_id."'";
		$obj_set = $ilDB->query($q);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);

		return $obj_rec["title"];
	}

	function _lookupObjId($a_id)
	{
		global $ilDB;
		
		$query = "SELECT obj_id FROM object_reference ".
			"WHERE ref_id = '".$a_id."'";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->obj_id;
		}
		return 0;
	}
		
		

	/**
	* lookup object type
	*
	* @param	int		$a_id		object id
	*/
	function _lookupType($a_id,$a_reference = false)
	{
		global $ilDB;

		if ($a_reference === true)
		{
			$q = "SELECT type FROM object_reference as obr, object_data as obd ".
				"WHERE obr.ref_id = '".$a_id."' ".
				"AND obr.obj_id = obd.obj_id ";
			
			#$q = "SELECT type FROM object_data as obj ".
			#	 "LEFT JOIN object_reference as ref ON ref.obj_id=obj.obj_id ".
			#	 "WHERE ref.ref_id = '".$a_id."'";
		}
		else
		{
			$q = "SELECT type FROM object_data WHERE obj_id = '".$a_id."'";
		}

		$obj_set = $ilDB->query($q);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);

		return $obj_rec["type"];
	}

	/**
	* checks wether object is in trash
	*/
	function _isInTrash($a_ref_id)
	{
		global $tree;

		return $tree->isSaved($a_ref_id);
	}

	/**
	* checks wether an object has at least one reference that is not in trash
	*/
	function _hasUntrashedReference($a_obj_id)
	{
		$ref_ids  = ilObject::_getAllReferences($a_obj_id);
		foreach($ref_ids as $ref_id)
		{
			if(!ilObject::_isInTrash($ref_id))
			{
				return true;
			}
		}

		return false;
	}

	/**
	* lookup object id
	*
	* @param	int		$a_id		object id
	*/
	function _lookupObjectId($a_ref_id)
	{
		global $ilDB;

		$q = "SELECT obj_id FROM object_reference WHERE ref_id = '".$a_ref_id."'";
		$obj_set = $ilDB->query($q);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);

		return $obj_rec["obj_id"];
	}

	/**
	* get all objects of a certain type
	*
	* @param	string		$a_type			desired object type
	* @param	boolean		$a_omit_trash	omit objects, that are in trash only
	*										(default: false)
	*
	* @return	array		array of object data arrays ("id", "title", "type",
	*						"description")
	*/
	function _getObjectsDataForType($a_type, $a_omit_trash = false)
	{
		global $ilDB;

		$q = "SELECT * FROM object_data WHERE type = ".$ilDB->quote($a_type);
		$obj_set = $ilDB->query($q);

		$objects = array();
		while ($obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if ((!$a_omit_trash) || ilObject::_hasUntrashedReference($obj_rec["obj_id"]))
			{
				$objects[$obj_rec["title"].".".$obj_rec["obj_id"]] = array("id" => $obj_rec["obj_id"],
					"type" => $obj_rec["type"], "title" => $obj_rec["title"],
					"description" => $obj_rec["description"]);
			}
		}
		ksort($objects);
		return $objects;
	}

	/**
	* maybe this method should be in tree object!?
	*
	* @todo	role/rbac stuff
	*/
	function putInTree($a_parent_ref)
	{
		global $tree;

		$tree->insertNode($this->getRefId(), $a_parent_ref);
	}

	/**
	* set permissions of object
	*
	* @param	integer	reference_id of parent object
	* @access	public
	*/
	function setPermissions($a_parent_ref)
	{
		global $rbacadmin, $rbacreview;

		$parentRoles = $rbacreview->getParentRoleIds($a_parent_ref);

		foreach ($parentRoles as $parRol)
		{
			$ops = $rbacreview->getOperationsOfRole($parRol["obj_id"], $this->getType(), $parRol["parent"]);
			$rbacadmin->grantPermission($parRol["obj_id"], $ops, $this->getRefId());
		}
	}

	/**
	* creates reference for object
	*
	* @access	public
	* @return	integer	reference_id of object
	*/
	function createReference()
	{
		global $ilDB;

		if (!isset($this->id))
		{
			$message = "ilObject::createNewReference(): No obj_id given!";
			$this->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$q = "INSERT INTO object_reference ".
			 "(obj_id) VALUES ('".$this->id."')";
		$this->ilias->db->query($q);

		$this->ref_id = $ilDB->getLastInsertId();

		return $this->ref_id;
	}


	/**
	* count references of object
	*
	* @access	public
	* @return	integer		number of references for this object
	*/
	function countReferences()
	{
		if (!isset($this->id))
		{
			$message = "ilObject::countReferences(): No obj_id given!";
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		$q = "SELECT COUNT(ref_id) AS num FROM object_reference ".
		 	"WHERE obj_id = '".$this->id."'";
		$row = $this->ilias->db->getRow($q);

		return $row->num;
	}


	/**
	* ilClone object into tree
	* basic clone function. Register new object in object_data, creates reference and
	* insert reference ID in tree. All object specific data must be copied in the ilClone function of the appropriate object class.
	* Look in ilObjForum::ilClone() for example code
	* 
	* @access	public
	* @param	integer		$a_parent_ref		ref id of parent object
	* @return	integer		new ref id
	*/
	function ilClone($a_parent_ref)
	{
		$new_obj = new ilObject();
		$new_obj->setTitle($this->getTitle());
		$new_obj->setType($this->getType());
		$new_obj->setDescription($this->getDescription());
		$new_obj->create();
		$new_ref_id = $new_obj->createReference();
		$new_obj->putInTree($a_parent_ref);
		$new_obj->setPermissions($a_parent_ref);

		unset($new_obj);
	
		// ... and finally always return new reference ID!!
		return $new_ref_id;
	}


	/**
	* delete object or referenced object
	* (in the case of a referenced object, object data is only deleted
	* if last reference is deleted)
	* This function removes an object entirely from system!!
	*
 	* @access	public
	* @return	boolean	true if object was removed completely; false if only a references was removed
	*/
	function delete()
	{
		global $rbacadmin;

		$remove = false;

		// delete object_data entry
		if ((!$this->referenced) || ($this->countReferences() == 1))
		{
			// delete entry in object_data
			$q = "DELETE FROM object_data ".
				"WHERE obj_id = '".$this->getId()."'";
			$this->ilias->db->query($q);
			
			$remove = true;
		}
		
		// delete object_reference entry
		if ($this->referenced)
		{
			// delete entry in object_reference
			$q = "DELETE FROM object_reference ".
				"WHERE ref_id = '".$this->getRefId()."'";
			$this->ilias->db->query($q);

			// DELETE PERMISSION ENTRIES IN RBAC_PA
			// DONE: method overwritten in ilObjRole & ilObjUser.
			// this call only applies for objects in rbac (not usr,role,rolt)
			// TODO: Do this for role templates too
			$rbacadmin->revokePermission($this->getRefId());
		}

		return $remove;
	}

	/**
	* init default roles settings
	* Purpose of this function is to create a local role folder and local roles, that are needed depending on the object type
	* If you want to setup default local roles you MUST overwrite this method in derived object classes (see ilObjForum for an example)
	* @access	public
	* @return	array	empty array
	*/
	function initDefaultRoles()
	{
		return array();
	}
	
	/**
	* creates a local role folder
	* 
	* @access	public
	* @param	string	rolefolder title
	* @param	string	rolefolder description
	* @param	object	parent object where the rolefolder is attached to
	* @return	object	rolefolder object
	*/
	function createRoleFolder()
	{
		global $rbacreview;
		
		// does a role folder already exists?
		// (this check is only 'to be sure' that no second role folder is created under one object.
		// the if-construct should never return true)
		if ($rolf_data = $rbacreview->getRoleFolderofObject($this->getRefId()))
		{
			$rfoldObj = $this->ilias->obj_factory->getInstanceByRefId($rolf_data["ref_id"]);
		}
		else
		{
			include_once ("classes/class.ilObjRoleFolder.php");
			$rfoldObj = new ilObjRoleFolder();
			$rfoldObj->setTitle($this->getId());
			$rfoldObj->setDescription(" (ref_id ".$this->getRefId().")");
			$rfoldObj->create();
			$rfoldObj->createReference();
			$rfoldObj->putInTree($this->getRefId());
			$rfoldObj->setPermissions($this->getRefId());
		}
		
		return $rfoldObj;
	}

	/**
	* checks if an object exists in object_data
	* @static
	* @access	public
	* @param	integer	object id or reference id
	* @param	boolean	ture if id is a reference, else false (default)
	* @return	boolean	true if object exists
	*/
	function _exists($a_id, $a_reference = false)
	{
		global $ilias;
		
		if ($a_reference)
		{
			$q = "SELECT * FROM object_data ".
				 "LEFT JOIN object_reference ON object_reference.obj_id=object_data.obj_id ".
				 "WHERE object_reference.ref_id='".$a_id."'";
		}
		else
		{
			$q = "SELECT * FROM object_data WHERE obj_id='".$a_id."'";
		}
		
		$r = $ilias->db->query($q);

		return $r->numRows() ? true : false;
	}

	/**
	* notifys an object about an event occured
	* Based on the event passed, each object may decide how it reacts.
	* TODO: add optional array to pass parameters
	*
	* @access	public
	* @param	string	event
	* @param	integer	reference id of object where the event occured
	* @param	integer reference id of node in the tree which is actually notified
	* @param	array	passes optional parameters if required
	* @return	boolean
	*/
	function notify($a_event,$a_ref_id,$a_parent_non_rbac_id,$a_node_id,$a_params = 0)
	{ 
		global $tree;
		
		$parent_id = (int) $tree->getParentId($a_node_id);
		
		if ($parent_id != 0)
		{
			$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($a_node_id);
			$obj_data->notify($a_event,$a_ref_id,$a_parent_non_rbac_id,$parent_id,$a_params);
		}
				
		return true;
	}
	
	// toggle subscription interface
	function setRegisterMode($a_bool)
	{
		$this->register = (bool) $a_bool;
	}
	
	// check register status of current user
	// abstract method; overwrite in object type class
	function isUserRegistered($a_user_id = 0)
	{
		return false;
	}

	function requireRegistration()
	{
		return $this->register;
	}


	function getXMLZip()
	{
		return false;
	}
	function getHTMLDirectory()
	{
		return false;
	}

	// static method for condition handler
	function _checkCondition($a_obj_id,$a_operator,$a_value)
	{
		switch($a_operator)
		{
			default:
				return true;
		}
	}


} // END class.ilObject
?>
