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

/**
* Class ilObjStyleSettings
* 
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
* @extends ilObject
*/

require_once "class.ilObject.php";

class ilObjStyleSettings extends ilObject
{
	var $styles;
	
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjStyleSettings($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "stys";
		$this->ilObject($a_id,$a_call_by_reference);
		
		$this->styles = array();
	}
	
	/**
	* add style to style folder
	*
	* @param	int		$a_style_id		style id
	*/
	function addStyle($a_style_id)
	{
		$this->styles[$a_style_id] =
			array("id" => $a_style_id,
			"title" => ilObject::_lookupTitle($a_style_id));
	}

	
	/**
	* remove Style from style list
	*/
	function removeStyle($a_id)
	{
		unset($a_id);
	}


	/**
	* update object data
	*
	* @access	public
	* @return	boolean
	*/
	function update()
	{
		global $ilDB;
		
		if (!parent::update())
		{			
			return false;
		}

		// save styles of style folder
		$q = "DELETE FROM style_folder_styles WHERE folder_id = ".
			$ilDB->quote($this->getId());
		$ilDB->query($q);
		foreach($this->styles as $style)
		{
			$q = "INSERT INTO style_folder_styles (folder_id, style_id) VALUES".
				"(".$ilDB->quote($this->getId()).", ".
				$ilDB->quote($style["id"]).")";
			$ilDB->query($q);
		}
		
		return true;
	}
	
	/**
	* read style folder data
	*/
	function read()
	{
		global $ilDB;

		parent::read();

		// get styles of style folder
		$q = "SELECT * FROM style_folder_styles, object_data as obj, style_data WHERE folder_id = ".
			$ilDB->quote($this->getId()).
			" AND style_id = obj.obj_id".
			" AND style_data.id = obj.obj_id";

		$style_set = $ilDB->query($q);
		while ($style_rec = $style_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$this->styles[$style_rec["style_id"]] =
				array("id" => $style_rec["style_id"],
				"title" => $style_rec["title"],
				"category" => $style_rec["category"]);
//echo "<br>-".$style_rec["category"]."-";
		}
	}
	
	/**
	* lookup if a style is activated
	*/
	function _lookupActivatedStyle($a_skin, $a_style)
	{
		global $ilDB;
		
		$q = "SELECT count(*) AS cnt FROM settings_deactivated_styles".
			" WHERE skin = ".$ilDB->quote($a_skin).
			" AND style = ".$ilDB->quote($a_style);
		
		$cnt_set = $ilDB->query($q);
		$cnt_rec = $cnt_set->fetchRow(DB_FETCHMODE_ASSOC);
		
		if ($cnt_rec["cnt"] > 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	* deactivate style
	*/
	function _deactivateStyle($a_skin, $a_style)
	{
		global $ilDB;

		$q = "REPLACE into settings_deactivated_styles".
			" (skin, style) VALUES ".
			" (".$ilDB->quote($a_skin).",".
			" ".$ilDB->quote($a_style).")";

		$ilDB->query($q);
	}

	/**
	* activate style
	*/
	function _activateStyle($a_skin, $a_style)
	{
		global $ilDB;

		$q = "DELETE FROM settings_deactivated_styles".
			" WHERE skin = ".$ilDB->quote($a_skin).
			" AND style = ".$ilDB->quote($a_style);

		$ilDB->query($q);
	}
	
	/**
	* get style ids
	*
	* @return		array		ids
	*/
	function getStyles()
	{
		return $this->styles;
	}
	

	/**
	* delete object and all related data	
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
		
		//put here your module specific stuff
		
		return true;
	}

	/**
	* init default roles settings
	* 
	* If your module does not require any default roles, delete this method 
	* (For an example how this method is used, look at ilObjForum)
	* 
	* @access	public
	* @return	array	object IDs of created local roles.
	*/
	function initDefaultRoles()
	{
		global $rbacadmin;
		
		// create a local role folder
		//$rfoldObj = $this->createRoleFolder("Local roles","Role Folder of forum obj_no.".$this->getId());

		// create moderator role and assign role to rolefolder...
		//$roleObj = $rfoldObj->createRole("Moderator","Moderator of forum obj_no.".$this->getId());
		//$roles[] = $roleObj->getId();

		//unset($rfoldObj);
		//unset($roleObj);

		return $roles ? $roles : array();
	}

	/**
	* notifys an object about an event occured
	* Based on the event happend, each object may decide how it reacts.
	* 
	* If you are not required to handle any events related to your module, just delete this method.
	* (For an example how this method is used, look at ilObjGroup)
	* 
	* @access	public
	* @param	string	event
	* @param	integer	reference id of object where the event occured
	* @param	array	passes optional parameters if required
	* @return	boolean
	*/
	function notify($a_event,$a_ref_id,$a_parent_non_rbac_id,$a_node_id,$a_params = 0)
	{
		global $tree;
		
		switch ($a_event)
		{
			case "link":
				
				//var_dump("<pre>",$a_params,"</pre>");
				//echo "Module name ".$this->getRefId()." triggered by link event. Objects linked into target object ref_id: ".$a_ref_id;
				//exit;
				break;
			
			case "cut":
				
				//echo "Module name ".$this->getRefId()." triggered by cut event. Objects are removed from target object ref_id: ".$a_ref_id;
				//exit;
				break;
				
			case "copy":
			
				//var_dump("<pre>",$a_params,"</pre>");
				//echo "Module name ".$this->getRefId()." triggered by copy event. Objects are copied into target object ref_id: ".$a_ref_id;
				//exit;
				break;

			case "paste":
				
				//echo "Module name ".$this->getRefId()." triggered by paste (cut) event. Objects are pasted into target object ref_id: ".$a_ref_id;
				//exit;
				break;
			
			case "new":
				
				//echo "Module name ".$this->getRefId()." triggered by paste (new) event. Objects are applied to target object ref_id: ".$a_ref_id;
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
} // END class.ilObjStyleSettings
?>
