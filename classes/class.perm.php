<?php
// TODO: this function collection must cleaned up!!! Many functions belong to other classes
/**
* perm class (actually a function library)
* general object handling functions
*
* @author	Sascha Hofmann <shofmann@databay.de>
* @author	Stefan Meyer <smeyer@databay.de>
* @version	$Id$
* @package	ilias-core
*/

/**
* creates a new object entry in obj_data
* @access	public
* @param	string	object type
* @param	string	object title
* @param	string	object description
* @param	integer	cut length of title string to given value (optional, default: MAXLENGTH_OBJ_TITLE)
* @param	integer	cut length of description string to given value (optional, default: MAXLENGTH_OBJ_DESC)
* @param	boolean	adding 3 dots to shortended string (optional, default: true)
* @return	integer	object id
*/
function createNewObject ($a_type,$a_title,$a_desc,$a_len_title=MAXLENGTH_OBJ_TITLE,$a_len_desc=MAXLENGTH_OBJ_DESC,$a_dots=true)
{
	global $ilias;
	
	// cut length of text
	$a_title = addslashes(shortenText($a_title,$a_len_title,$a_dots));
	$_desc = addslashes(shortenText($a_desc,$a_len_desc,$a_dots));

	$q = "INSERT INTO object_data ".
		 "(type,title,description,owner,create_date,last_update) ".
		 "VALUES ".
		 "('".$a_type."','".$a_title."','".$a_desc."',".
		 "'".$ilias->account->Id."',now(),now())";
	$ilias->db->query($q);

	return getLastInsertId();
}

/**
* get an object
* @access	public
* @param	integer	object id
* @return	array	object data
*/
function getObject ($a_obj_id)
{
	global $ilias;

	$q = "SELECT * FROM object_data ".
		 "WHERE obj_id = '".$a_obj_id."'";
	$r = $ilias->db->query($q);
	
	$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

	$arr = fetchObjectData($row);

	return $arr;
}

/**
* creates a copy of an existing object
* @access	public
* @param	integer	object id of object you want to copy
* @return	integer	returns object id
*/
function copyObject ($a_obj_id)
{
	global $ilias;
	
	$old_object_data = getObject($a_obj_id);

	$q = "INSERT INTO object_data ".
		 "(type,title,description,owner,create_date,last_update) ".
		 "VALUES ".
		 "('".$old_object_data["type"]."','".$old_object_data["title"]."','".$old_object_data["desc"]."',".
		 "'".$ilias->account->Id."',now(),now())";
	$ilias->db->query($q);
	
	return getLastInsertId();
}

/**
* delete an object from tbl.object_data
* @access	public
* @param	integer		object id
* @return	boolean		returns true if successful otherwise false
*/
function deleteObject ($a_obj_id)
{
	global $ilias;

	$q = "DELETE FROM object_data ".
		 "WHERE obj_id = '".$a_obj_id."'";
	$ilias->db->query($q);

	return true;
}

/**
* updates an object
* @access	public
* @param	integer	object id
* @param	string	object title
* @param	string	object description
* @param	integer	cut length of title string to given value (optional, default: MAXLENGTH_OBJ_TITLE)
* @param	integer	cut length of description string to given value (optional, default: MAXLENGTH_OBJ_DESC)
* @param	boolean	adding 3 dots to shortended string (optional, default: true)
* @return	boolean	true
*/
function updateObject ($a_id,$a_title,$a_desc,$a_len_title=MAXLENGTH_OBJ_TITLE,$a_len_desc=MAXLENGTH_OBJ_DESC,$a_dots=true)
{
	global $ilias;
	
	// cut length of text
	$a_title = addslashes(shortenText($a_title,$a_len_title,$a_dots));
	$_desc = addslashes(shortenText($a_desc,$a_len_desc,$a_dots));

	$q = "UPDATE object_data ".
		 "SET ".
		 "title='".$a_title."',".
		 "description='".$a_desc."', ".
		 "last_update=now() ".
		 "WHERE obj_id='".$a_id."'";
	$ilias->db->query($q);

	return true;
}

/**
* updates a single value in a column of object data
* @access	public
* @param	integer	object id of object to change
* @param	string	column name of obj_data table
* @param	string	value to be changed
* @return	boolean	true on success
*/
function updateObjectValue($a_id,$a_column,$a_value)
{
	global $ilias;

	$q = "UPDATE object_data ".
		 "SET ".$a_column."='".$a_value."',".
		 "last_update=now() ".
		 "WHERE obj_id='".$a_id."'";
	$ilias->db->query($q);
	
	return true;
}

/**
* fetch object data from mysql result set and returns an array
* @access	private
* @param	object	result row of mysql result set
* @return 	array
*/
function fetchObjectData($a_row)
{
	$arr = array (
					"obj_id"		=> $a_row->obj_id,
					"type"			=> $a_row->type,
					"title"			=> stripslashes($a_row->title),
					"description"	=> stripslashes($a_row->description),	// for compability only
					"desc"			=> stripslashes($a_row->description),
					"usr_id"		=> $a_row->owner,
					"owner"			=> $a_row->owner,
					"create_date"	=> $a_row->create_date,
					"last_update"	=> $a_row->last_update,
					"last_login"	=> $a_row->last_login
				);

	return $arr;
}

/*
* create a reference id of an object
* @access	public
* @param	integer	object id
* @return 	integer	reference id
*/
function createNewReference ($a_obj_id)
{
	$q = "INSERT INTO object_reference ".
		 "(obj_id) VALUES ('".$a_obj_id."')";
	$ilias->db->query($q);

	return getLastInsertId();
}

/**
* get list of object, optional only a list of a particular type
* @access	public
* @param	string	object type
* @param	integer	start row of result set
* @param	integer	maximum rows in result set
* @param	string	order column
* @param	string	order direction (possible values: ASC or DESC)
* @return	array/boolean	returns array of objects or false if no objects found
*/
function getObjectList ($a_obj_type = "",$a_order = "", $a_direction = "ASC", $a_offset = "",$a_limit = "")
{
	global $ilias;
	
	// order
	if (!$a_order)
	{
		$a_order = "title";
	}
	
	$order = "ORDER BY ".$a_order." ".$a_direction;

	// limit clause
	if ($a_limit && $a_offset)
	{
		$limit_clause = " LIMIT ".$a_offset.",".$a_limit;
	}

	// where clause
	if ($a_obj_type)
	{
		$where_clause = "WHERE type = '".$a_obj_type."' ";
	}

	$q = "SELECT * FROM object_data ".$where_clause.$order.$limit_clause;
	$r = $ilias->db->query($q);

	if ($r->numRows() > 0)
	{
		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$arr[] = fetchObjectData($row);
		}

		return $arr;
	}

	return false;
}

/**
* get operation list
* @access	public
* @param	string	object type you want to have the operation list
* @param	string	order column
* @param	string	order direction (possible values: ASC or DESC)
* @return	array	returns array of operations
*/
function getOperationList ($a_type = "",$a_order= "",$a_direction = "")
 {
	global $ilias;

	if (!$a_order)
	{
		$a_order = "operation";
	}

	if ($a_type)
	{
		$q = "SELECT * FROM rbac_operations ".
			 "LEFT JOIN rbac_ta ON rbac_operations.ops_id = rbac_ta.ops_id ".
			 "LEFT JOIN object_data ON rbac_ta.typ_id = object_data.obj_id ".
			 "WHERE object_data.title='".$a_type."' AND object_data.type='typ' ".
			 "ORDER BY rbac_operations.".$a_order." ".$a_direction; 
	}
	else
	{
		$q = "SELECT * FROM rbac_operations ".
			 "ORDER BY ".$a_order." ".$a_direction;
	}
	
	$r = $ilias->db->query($q);

	while ($row = $r->fetchRow())
	{
		$arr[] = array(
					"ops_id"	=> $row[0],
					"operation"	=> $row[1],
					"desc"		=> $row[2]
					);
	}

	return $arr;
}

/**
* creates a new object
* @access	public
* @param	string	operation name
* @param	string	operation description
* @return 	integer	returns operation id
*/
function createNewOperation ($a_operation,$a_description)
{
	global $ilias;

	$q = "INSERT INTO operations ".
		 "(operation,description) ".
		 "VALUES ".
		 "('".$a_operation."','".$a_description."')";
	$ilias->db->query($q);
	
	return getLastInsertId();
}

/*
* get last insert id of a mysql query
* @access	public
* @return	integer	last insert id
*/
function getLastInsertId()
{
	global $ilias;

	$r = $ilias->db->query("SELECT LAST_INSERT_ID()");
	$row = $r->fetchRow();
	
	return $row[0];
}

/**
* POSSIBLE DEPRECATED. IF NOT, FUNCTION BELONGS TO class.user
* check if user is logged in
* @access	public
* @return	boolean	true if logged in
*/
function isUserLoggedIn ()
{
	global $ilias;

	if (empty($ilias->account->Id))
	{
		return false;
	}

	return true;
}

/**
* TODO: move to class.util
* removes spaces and tabs within text strings
* @access	public
* @param	string	string to be trimmed
* @return	string 	trimmed string
*/
function trimDeluxe ($a_text)
{
	str_replace("\t"," ",$a_text);

	for ($i=0;$i<50;$i++)
	{
		str_replace("  "," ",$a_text);
	}

	$a_text = trim($a_text);

	return $a_text;
}

/**
* TODO: move to class.util
* shorten a string to given length.
* Adds 3 dots at the end of string (optional)
* TODO: do not cut within words (->wordwrap function)
* @access	public
* @param	string	string to be shortened
* @param	integer	string length in chars
* @param	boolean	adding 3 dots (true) or not (false, default)
* @return	string 	shortended string
*/
function shortenText ($a_str, $a_len, $a_dots = "false")
{
	if (strlen($a_str) > $a_len)
	{

		$a_str = substr($a_str,0,$a_len); 

		if ($a_dots)
		{
			$a_str .= "...";
		}
	}

	return $a_str;
}
?>