<?php
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
	* @var int
	*/
	var $max_title;


	/**
	* max description length
	* @var int
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
		global $ilias, $lng;

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
	}


	/**
	* read object data from db into object
	* @param	boolean
	* @access	public
	*/
	function read($a_force_db = false)
	{
		global $ilias,$log;

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
				$log->writeWarning($message);
				$ilias->raiseError($message,$ilias->error_obj->WARNING);	
			}

			// read object data
			$q = "SELECT * FROM object_data ".
				 "LEFT JOIN object_reference ON object_data.obj_id=object_reference.obj_id ".
			 	"WHERE object_reference.ref_id='".$this->ref_id."'";
			$object_set = $ilias->db->query($q);
	
			// check number of records
			if ($object_set->numRows() == 0)
			{
				$message = "ilObject::read(): Object with ref_id ".$this->ref_id." not found!";
				$log->writeWarning($message);
				$ilias->raiseError($message,$ilias->error_obj->WARNING);
			}

			$obj = $object_set->fetchRow(DB_FETCHMODE_ASSOC);
		}
		else
		{
			// check object id
			if (!isset($this->id))
			{
				$message = "ilObject::read(): No obj_id given!";
				$log->writeWarning($message);
				$ilias->raiseError($message,$ilias->error_obj->WARNING);
			}

			// read object data
			$q = "SELECT * FROM object_data ".
				 "WHERE obj_id = '".$this->id."'";
			$object_set = $ilias->db->query($q);

			// check number of records
			if ($object_set->numRows() == 0)
			{
				$message = "ilObject::read(): Object with obj_id: ".$this->id." not found!";
				$log->writeWarning($message);
				$ilias->raiseError($message,$ilias->error_obj->WARNING);
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
	}


	/**
	* get object id
	* @access	public
	* @return	int		object id
	*/
	function getId()
	{
		return $this->id;
	}


	/**
	* set object id
	* @access	public
	* @param	int		$a_id		object id
	*/
	function setId($a_id)
	{
		$this->id = $a_id;
	}


	/**
	* set reference id
	* @access	public
	* @param	int		$a_id		reference id
	*/
	function setRefId($a_id)
	{
		$this->ref_id = $a_id;
		$this->referenced = true;
	}


	/**
	* get reference id
	* @access	public
	* @return	int		reference id
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
	* @param	int		$a_type		object type
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
	* @access	public
	* @param	string		$a_title		object title
	*/
	function setTitle($a_title)
	{
		$this->title = addslashes(shortenText($a_title, $this->max_title, $this->add_dots));
	}


	/**
	* get object description
	* @access	public
	* @return	string		object description
	*/
	function getDescription()
	{
		return $this->desc;
	}


	/**
	* set object description
	* @access	public
	* @param	string		$a_desc		object description
	*/
	function setDescription($a_desc)
	{
		$this->desc = addslashes(shortenText($a_desc, $this->max_desc, $this->add_dots));
	}

	/**
	* get object owner
	* @access	public
	* @return	int			owner id
	*/
	function getOwner()
	{
		return $this->owner;
	}


	/*
	* get full name of object owner
	*
	* @return	string		owner name or unknown
	*/
	function getOwnerName()
	{
		global $lng;

		if($this->getOwner() != -1)
		{
			$owner = new ilUser($this->getOwner());
		}

		if (is_object($owner))
		{
			$own_name = $owner->getFullname();
		}
		else
		{
			$own_name = $lng->txt("unknown");
		}

		return $own_name;
	}


	/**
	* set object owner
	* @access	public
	* @param	int		$a_owner		owner id
	*/
	function setOwner($a_owner)
	{
		$this->owner = $a_owner;
	}


	/**
	* get create date
	* @access	public
	* @return	string			creation date
	*/
	function getCreateDate()
	{
		return $this->create_date;
	}


	/**
	* get last update date
	* @access	public
	* @return	string			date of last update
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
	* @return	int		object id
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
	* @return	int		object id
	*/
	function create()
	{
		global $ilias;

		if (!isset($this->type))
		{
			$message = "Object->create(): No object type given!";
			$ilias->raiseError($message,$ilias->error_obj->WARNING);
		}

		if (empty($this->title))
		{
			$message = "Object->create(): No title given! A title is required!";
			$ilias->raiseError($message,$ilias->error_obj->WARNING);
		}

		$this->title = addslashes(shortenText($this->title, $this->max_title, $this->add_dots));
		$this->desc = addslashes(shortenText($this->desc, $this->max_desc, $this->add_dots));

		$q = "INSERT INTO object_data ".
			 "(type,title,description,owner,create_date,last_update) ".
			 "VALUES ".
			 "('".$this->type."','".$this->title."','".$this->desc."',".
			 "'".$ilias->account->getId()."',now(),now())";
		$ilias->db->query($q);

		$this->id = getLastInsertId();

		$this->read();

		return $this->id;
	}

	/*
	* update object in db
	*/
	function update()
	{
		global $ilias;

		$q = "UPDATE object_data ".
			"SET ".
			"title = '".$this->title."',".
			"description = '".$this->desc."', ".
			"last_update = now() ".
			"WHERE obj_id = '".$this->id."'";
		$ilias->db->query($q);

		$this->read();						// to get all data (incl. dates!)

		return true;
	}


	/**
	* maybe this method should be in tree object!?
	* @todo	role/rbac stuff
	*/
	function putInTree($a_parent)
	{
		global $tree, $rbacadmin;

		$tree->insertNode($this->getRefId(), $a_parent);

		// TODO: MAKE THIS WORK!
		/*
		$parentRoles = $rbacadmin->getParentRoleIds();
		foreach ($parentRoles as $parRol)
		{
    		// Es werden die im Baum am 'n�chsten liegenden' Templates ausgelesen
			$ops = $rbacreview->getOperations($parRol["obj_id"], $this->getType(), $parRol["parent"]);
			$rbacadmin->grantPermission($parRol["obj_id"], $ops, $this->getRefId(), $a_parent);
		}*/
	}


	/**
	* creates reference for object
	*/
	function createReference()
	{
		if (!isset($this->id))
		{
			$message = "perm::createNewReference(): No obj_id given!";
			$ilias->raiseError($message,$ilias->error_obj->WARNING);
		}

		$q = "INSERT INTO object_reference ".
			 "(obj_id) VALUES ('".$this->id."')";
		$this->ilias->db->query($q);

		$this->ref_id = getLastInsertId();

		return $this->ref_id;
	}


	/**
	* count references of object
	*
	* @return	int		number of references of object
	*/
	function countReferences()
	{
		global $ilias, $log;

		if (!isset($this->obj_id))
		{
			$message = "ilObject::countReferences(): No obj_id given!";
			$log->writeWarning($message);
			$ilias->raiseError($message,$ilias->error_obj->WARNING);
		}

		$q = "SELECT COUNT(ref_id) AS num FROM object_reference ".
		 	"WHERE obj_id = '".$this->obj_id."'";
		$row = $ilias->db->getRow($q);

		return $row->num;
	}


	/**
	* clone object into tree
	* @access	public
	* @param	int		$a_parent_ref		parent ref id
	* @return	new object id
	*/
	function clone($a_parent_ref)
	{
		global $tree,$rbacadmin,$rbacreview;

		$q = "INSERT INTO object_data ".
			"(type,title,description,owner,create_date,last_update) ".
			"VALUES ".
			"('".$this->getType()."','".$this->getTitle()."','".$this->getDescription()."',".
			"'".$ilias->account->getId()."',now(),now())";
		$this->ilias->db->query($q);
	
		$new_id = getLastInsertId();

		$tree->insertNode($new_id,$a_parent_ref);

		$parentRoles = $rbacadmin->getParentRoleIds($a_parent_ref);

		foreach ($parentRoles as $parRol)
		{
			// Es werden die im Baum am 'n�chsten liegenden' Templates ausgelesen
			$ops = $rbacreview->getOperations($parRol["obj_id"], $this->getType(), $parRol["parent"]);
			$rbacadmin->grantPermission($parRol["obj_id"],$ops, $new_id);
		}
		return $new_id;
	}


	/**
	* This method is called automatically from out class
	* It removes all object entries for a specific object
	* This method should be overwritten by all object types
	* @access public
	**/
	function delete()
	{
		global $rbacadmin, $tree;

		// ALL OBJECT ENTRIES IN TREE HAVE BEEN DELETED FROM CLASS ADMIN.PHP

		// IF THERE IS NO OTHER REFERENCE, DELETE ENTRY IN OBJECT_DATA
		// TODO: What happens with ref_id entries? They are not deleted
		if ($this->countReferences() == 1)
		{
			deleteObject($this->getId());
		}

		// DELETE PERMISSION ENTRIES IN RBAC_PA
		// TODO: this call only applies for objects in rbac (not usr and role)
		$rbacadmin->revokePermission($this->getRefId());

		return true;
	}

	/**
	* returns the parent object id of $_GET["parent"]
	* @access	private
	* @param	integer		node_id where to start
	* @return	integer
	*/
	/*            deprecated!?
	function getParentObjectId($a_start = 0)
	{
		global $tree;

		$a_start = $a_start ? $a_start : $_GET["parent"];

		$path_ids = $tree->getPathId($a_start,ROOT_FOLDER_ID);
		array_pop($path_ids);

		return array_pop($path_ids);
	} //function*/


	function getSubObjects()
	{
		global $rbacsystem,$rbacadmin;

		$data = array();

		// show only objects with permission 'create'
		$objects = $rbacadmin->getModules($this->type,$this->id);

		foreach ($objects as $key => $object)
		{
			if ($rbacsystem->checkAccess("create", $this->id, $key))
			{
				$data[$key] = $object;
			} //if
		} //foreach
		return $data;
	}
} // class
?>
