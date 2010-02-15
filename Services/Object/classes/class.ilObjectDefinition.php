<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2008 ILIAS open source, University of Cologne            |
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
* parses the objects.xml
* it handles the xml-description of all ilias objects
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @externalTableAccess ilObjDefReader on il_object_def, il_object_subobj, il_object_group 
*/
class ilObjectDefinition extends ilSaxParser
{
	/**
	* // TODO: var is not used
	* object id of specific object
	* @var obj_id
	* @access private
	*/
	var $obj_id;

	/**
	* parent id of object
	* @var parent id
	* @access private
	*/
	var $parent;

	/**
	* array representation of objects
	* @var objects
	* @access private
	*/
	var $obj_data;

	/**
	* Constructor
	* 
	* setup ILIAS global object
	* @access	public
	*/
	function ilObjectDefinition()
	{
		global $ilias;
		
		$this->readDefinitionData();
		$this->ilias = $ilias;

		parent::ilSaxParser(ILIAS_ABSOLUTE_PATH."/objects.xml");
		
		// removing this line leads to segmentation faults in
		// learning module editor with
		// - PHP 5.2.1, libxml 2.6.22, libxslt 1.1.15 (MacOsX)
		// - PHP 5.2.1, libxml 2.6.31, libxslt 1.1.22 (MacOsX)
		// - PHP 5.2.5, libxml 2.6.31, libxslt 1.1.22 (MacOsX)
		// - PHP 5.2.0-8+etch7, libxml 2.6.27, libxslt 1.1.19
		// - PHP 5.2.0, libxml, libxml 2.6.26, libxslt 1.1.17 (OpenSuse 10.2)
		// (needs further investigation)
		// OK with:
		// - PHP 5.1.2, libxml 2.6.24, libxslt 1.1.15
		
		//
		// Replacing all "=&" with "=" in xml5compliance seems to solve the problem
		//
		
//		$this->startParsing();
	}

		
	/**
	* Read object definition data
	*/
	function readDefinitionData()
	{
		global $ilDB, $ilPluginAdmin;
		
		$this->obj_data = array();
		

		// Select all object_definitions and collect the definition id's in
		// this array.
        $defIds = array();
		$set = $ilDB->query("SELECT * FROM il_object_def");
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$this->obj_data[$rec["id"]] = array(
				"name" => $rec["id"],
				"class_name" => $rec["class_name"],
				"location" => $rec["location"],
				"checkbox" => $rec["checkbox"],
				"inherit" => $rec["inherit"],
				"component" => $rec["component"],
				"translate" => $rec["translate"],
				"devmode" => $rec["devmode"],
				"allow_link" => $rec["allow_link"],
				"allow_copy" => $rec["allow_copy"],
				"rbac" => $rec["rbac"],
				"group" => $rec["grp"],
				"system" => $rec["system"],
				"default_pos" => $rec["default_pos"],
				"sideblock" => $rec["sideblock"]);
			$this->obj_data[$rec["id"]]["subobjects"] = array();

            $defIds[] = $rec["id"];
        }
		// get all subobject definitions in a single query
		$set2 = $ilDB->query("SELECT * FROM il_object_subobj WHERE ".
				$ilDB->in('parent', $defIds, false, 'text'));
		while ($rec2 = $ilDB->fetchAssoc($set2))
		{
			$max = $rec2["mmax"];
			if ($max <= 0)				// for backward compliance
			{
				$max = "";
			}
			$this->obj_data[$rec2["parent"]]["subobjects"][$rec2["subobj"]] = array(
				"name" => $rec2["subobj"],
				"max" => $max,
				"lng" => $rec2["subobj"]
				);
		}
		
		$set = $ilDB->query("SELECT * FROM il_object_group");
		$this->obj_group = array();
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$this->obj_group[$rec["id"]] = $rec;
		}

		// now get objects from repository plugin
		$pl_names = $ilPluginAdmin->getActivePluginsForSlot(IL_COMP_SERVICE, "Repository", "robj");
		foreach ($pl_names as $pl_name)
		{
			include_once("./Services/Component/classes/class.ilPlugin.php");
			$pl_id = ilPlugin::lookupIdForName(IL_COMP_SERVICE, "Repository", "robj", $pl_name);
			if ($pl_id != "" && !isset($this->obj_data[$pl_id]))
			{
				include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");
				$loc = ilPlugin::_getDirectory(IL_COMP_SERVICE, "Repository", "robj",
					$pl_name)."/classes";

				$this->obj_data[$pl_id] = array(
					"name" => $pl_id,
					"class_name" => $pl_name,
					"plugin" => "1",
					"location" => $loc,
					"checkbox" => "1",
					"inherit" => "0",
					"component" => "",
					"translate" => "0",
					"devmode" => "0",
					"allow_link" => "1",
					"allow_copy" => "0",
					"rbac" => "1",
					"group" => null,
					"system" => "0",
					"default_pos" => "2000",
					"sideblock" => "0");
				$this->obj_data[$rec["id"]]["subobjects"] = array();
			}
		}
//var_dump($this->obj_data);
//var_dump($this->obj_data["root"]);
//var_dump($this->obj_data2["root"]);

	}
	
	
// PUBLIC METHODS

	/**
	* get class name by type
	*
	* @param	string	object type
	* @access	public
	*/
	function getClassName($a_obj_name)
	{
		return $this->obj_data[$a_obj_name]["class_name"];
	}


	/**
	* get location by type
	*
	* @param	string	object type
	* @access	public
	*/
	function getLocation($a_obj_name)
	{
		return $this->obj_data[$a_obj_name]["location"];
	}

	/**
	* Get Group information
	*/
	function getGroup($a_id)
	{
		return $this->obj_group[$a_id];
	}

	/**
	* Get Group of object type
	*/
	function getGroupOfObj($a_obj_name)
	{
		return $this->obj_data[$a_obj_name]["group"];
	}

	/**
	* should the object get a checkbox (needed for 'cut','copy' ...)
	*
	* @param	string	object type
	* @access	public
	*/
	function hasCheckbox($a_obj_name)
	{
		return (bool) $this->obj_data[$a_obj_name]["checkbox"];
	}
	
	/**
	* get translation type (sys, db or 0)s
	*
	* @param	string	object type
	* @access	public
	*/
	function getTranslationType($a_obj_name)
	{
		global $ilDB;
		
		if ($a_obj_name == "root")
		{
			if (!isset($this->root_trans_type))
			{
				$q = "SELECT count(obj_id) cnt FROM object_translation WHERE obj_id = ".
					$ilDB->quote(ROOT_FOLDER_ID,'integer')." ";
				$set = $ilDB->query($q);
				$rec = $set->fetchRow(DB_FETCHMODE_ASSOC);
				if($rec["cnt"] > 0)
				{
					$this->root_trans_type = "db";
				}
				else
				{
					$this->root_trans_type = $this->obj_data[$a_obj_name]["translate"];
				}
			}
			return $this->root_trans_type;
		}
		
		return $this->obj_data[$a_obj_name]["translate"];
	}
	

	/**
	* Does object permits stopping inheritance?
	*
	* @param	string	object type
	* @access	public
	*/
	function stopInheritance($a_obj_name)
	{
		return (bool) $this->obj_data[$a_obj_name]["inherit"];
	}

	/**
	* get devmode status by type
	*
	* @param	string	object type
	* @access	public
	*/
	function getDevMode($a_obj_name)
	{
		return (bool) $this->obj_data[$a_obj_name]["devmode"];
	}

	/**
	* get all object types in devmode
	*
	* @access	public
	* @return	array	object types set to development
	*/
	function getDevModeAll()
	{
		$types = array_keys($this->obj_data);
		
		foreach ($types as $type)
		{
			if ($this->getDevMode($type))
			{
				$devtypes[] = $type;
			}
		}

		return $devtypes ? $devtypes : array();
	}

	/**
	* get RBAC status by type
	* returns true if object type is a RBAC object type
	*
	* @param	string	object type
	* @access	public
	*/
	function isRBACObject($a_obj_name)
	{
		return (bool) $this->obj_data[$a_obj_name]["rbac"];
	}

	/**
	* get RBAC status by type
	* returns true if object type is a RBAC object type
	*
	* @param	string	object type
	* @access	public
	*/
	function isPlugin($a_obj_name)
	{
		return (bool) isset($this->obj_data[$a_obj_name]["plugin"]);
	}

	/**
	* get all RBAC object types
	*
	* @access	public
	* @return	array	object types set to development
	*/
	function getAllRBACObjects()
	{
		$types = array_keys($this->obj_data);
		
		foreach ($types as $type)
		{
			if ($this->isRBACObject($type))
			{
				$rbactypes[] = $type;
			}
		}

		return $rbactypes ? $rbactypes : array();
	}

	/**
	* get all object types
	*
	* @access	public
	* @return	array	object types
	*/
	function getAllObjects()
	{
		return array_keys($this->obj_data);
	}

	/**
	* checks if linking of an object type is allowed
	*
	* @param	string	object type
	* @access	public
	*/
	public function allowLink($a_obj_name)
	{
		return (bool) $this->obj_data[$a_obj_name]["allow_link"];
	}

	/**
	* checks if copying of an object type is allowed
	*
	* @param	string	object type
	* @access	public
	*/
	public function allowCopy($a_obj_name)
	{
		return (bool) $this->obj_data[$a_obj_name]["allow_copy"];
	}
	
	/**
	* get all subobjects by type
	*
	* @param	string	object type
	* @param	boolean	filter disabled objects? (default: true)
	* @access	public
	* @return	array	list of allowed object types
	*/
	function getSubObjects($a_obj_type,$a_filter = true)
	{
		global $ilSetting;
		
		$subs = array();

		if ($subobjects = $this->obj_data[$a_obj_type]["subobjects"])
		{
			// Filter some objects e.g chat object are creatable if chat is active
			if ($a_filter)
			{
				$this->__filterObjects($subobjects);
			}

			foreach ($subobjects as $data => $sub)
			{
				if ($sub["module"] != "n")
				{
					if (!($ilSetting->get("obj_dis_creation_".$data)))
					{
						$subs[$data] = $sub;
						
						// determine position
						$pos = ($ilSetting->get("obj_add_new_pos_".$data) > 0)
							? (int) $ilSetting->get("obj_add_new_pos_".$data)
							: (int) $this->obj_data[$data]["default_pos"];
						$subs[$data]["pos"] = $pos;
					}
				}
			}

			$subs2 = ilUtil::sortArray($subs, "pos", ASC, true, true);

			return $subs2;
		}
		
		return $subs;
	}

	/**
	* Get all subobjects by type.
	* This function returns all subobjects allowed by the provided object type
	* and all its subobject types recursively.
	*
	* This function is used to create local role templates. It is important,
	* that we do not filter out any objects here!
	*
	*
	* @param	string	object type
	* @access	public
	* @return	array	list of allowed object types
	*/
	function getSubObjectsRecursively($a_obj_type)
	{
		// This associative array is used to collect all subobject types.
		// key=>type, value=data
		$recursivesubs = array();

		// This array is used to keep track of the object types, we
		// need to call function getSubobjects() for.
		$to_do = array($a_obj_type);

		// This array is used to keep track of the object types, we
		// have called function getSubobjects() already. This is to
		// prevent endless loops, for object types that support 
		// themselves as subobject types either directly or indirectly.
		$done = array();

		while (count($to_do) > 0)
		{
			$type = array_pop($to_do);
			$done[] = $type;
			$subs = $this->getSubObjects($type);
			foreach ($subs as $subtype => $data)
			{
				$recursivesubs[$subtype] = $data;
				if (! in_array($subtype, $done)
				&& ! in_array($subtype, $to_do))
				{
					$to_do[] = $subtype;
				}
			}
		}

		return $recursivesubs;
	}

	/**
	* get all subjects except (rolf) of the adm object
	* This is neceesary for filtering these objects in role perm view.
	* e.g It it not necessary to view/edit role permission for the usrf object since it's not possible to create a new one
	*
	* @param	string	object type
 	* @access	public
	* @return	array	list of object types to filter
	*/
	function getSubobjectsToFilter($a_obj_type = "adm")
	{
		foreach($this->obj_data[$a_obj_type]["subobjects"] as $key => $value)
		{
			switch($key)
			{
				case "rolf":
					// DO NOTHING
					break;

				default:
					$tmp_subs[] = $key;
			}
		}
		// ADD adm and root object
		$tmp_subs[] = "adm";
		#$tmp_subs[] = "root";

		return $tmp_subs ? $tmp_subs : array();
	}
		
	/**
	* get only creatable subobjects by type
	*
	* @param	string	object type
 	* @access	public
	* @return	array	list of createable object types
	*/
	function getCreatableSubObjects($a_obj_type)
	{
		$subobjects = $this->getSubObjects($a_obj_type);

		// remove role folder object from list 
		unset($subobjects["rolf"]);
		unset($subobjects['rcrs']);
		
		$sub_types = array_keys($subobjects);

		// remove object types in development from list
		foreach ($sub_types as $type)
		{
			
			if ($this->getDevMode($type) || $this->isSystemObject($type))
			{
				unset($subobjects[$type]);
			}
		}

		return $subobjects;
	}
	
	/**
	* get a string of all subobjects by type
	*
	* @param	string	object type
	* @access	public
	*/
	function getSubObjectsAsString($a_obj_type)
	{
		$string = "";

		if (is_array($this->obj_data[$a_obj_type]["subobjects"]))
		{
			$data = array_keys($this->obj_data[$a_obj_type]["subobjects"]);

			$string = "'".implode("','", $data)."'";
		}
		
		return $string;
	}
	
	/**
	 * Check if object type is container ('crs','fold','grp' ...)
	 *
	 * @access public
	 * @param string object type
	 * @return bool
	 * 
	 */
	public function isContainer($a_obj_name)
	{
		if(!is_array($this->obj_data[$a_obj_name]['subobjects']))
		{
			return false;
		}
		return count($this->obj_data[$a_obj_name]['subobjects']) > 1 ? true : false;
	}

// PRIVATE METHODS

	/**
	* set event handler
	*
	* @param	ressouce	internal xml_parser_handler
	* @access	private
	*/
	function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser,$this);
		xml_set_element_handler($a_xml_parser,'handlerBeginTag','handlerEndTag');
		xml_set_character_data_handler($a_xml_parser,'handlerCharacterData');
	}

	/**
	* start tag handler
	*
	* @param	ressouce	internal xml_parser_handler
	* @param	string		element tag name
	* @param	array		element attributes
	* @access	private
	*/
	function handlerBeginTag($a_xml_parser,$a_name,$a_attribs)
	{
		switch ($a_name)
		{
			case 'object':
				$this->parent_tag_name = $a_attribs["name"];
				break;
			case 'property':
				$this->current_tag = "property";
				$this->current_tag_name = $a_attribs["name"];
//				$this->obj_data[$this->parent_tag_name]["properties"][$this->current_tag_name]["name"] = $a_attribs["name"];
				$this->obj_data[$this->parent_tag_name]["properties"][$this->current_tag_name]["module"] = $a_attribs["module"];
//echo '<br>$this->obj_data["'.$this->parent_tag_name.'"]["properties"]["'.$this->current_tag_name.'"]["module"] = "'.$a_attribs["module"].'";';
				break;
		}
	}

	/**
	* end tag handler
	*
	* @param	ressouce	internal xml_parser_handler
	* @param	string		data
	* @access	private
	*/
	function handlerCharacterData($a_xml_parser,$a_data)
	{
	}

	/**
	* end tag handler
	* 
	* @param	ressouce	internal xml_parser_handler
	* @param	string		element tag name
	* @access	private
	*/
	function handlerEndTag($a_xml_parser,$a_name)
	{
		$this->current_tag = '';
		$this->current_tag_name = '';
	}

	
	function __filterObjects(&$subobjects)
	{
		foreach($subobjects as $type => $data)
		{
			switch($type)
			{
				case "chat":
					if(!$this->ilias->getSetting("chat_active"))
					{
						unset($subobjects[$type]);
					}
					break;

				case "icrs":
					if(!$this->ilias->getSetting("ilinc_active"))
					{
						unset($subobjects[$type]);
					}
					break;					

				default:
					// DO NOTHING
			}
		}
	}
	
	/**
	* checks if object type is a system object
	* 
	* system objects are those object types that are only used for
	* internal purposes and to keep the object type model consistent.
	* Typically they are used in the administation, exist only once
	* and may contain only specific object types.
	* To mark an object type as a system object type, use 'system=1'
	* in the object definition in objects.xml
	*
	* @param	string	object type
	* @access	public
	*/
	function isSystemObject($a_obj_name)
	{
		return (bool) $this->obj_data[$a_obj_name]["system"];
	}
	
	/**
	* Check, whether object type is a side block.
	*
	* @param	string		object type
	* @return	boolean		side block true/false
	*/
	function isSideBlock($a_obj_name)
	{
		return (bool) $this->obj_data[$a_obj_name]["sideblock"];
	}

	/**
	* Get all repository object types of component
	*/
	static function getRepositoryObjectTypesForComponent($a_component_type,
		$a_component_name)
	{
		global $ilDB;
		
		$set = $ilDB->queryF("SELECT * FROM il_object_def WHERE component = %s",
			array("text"), array($a_component_type."/".$a_component_name));
			
		$types = array();
		while($rec = $ilDB->fetchAssoc($set))
		{
			if ($rec["system"] != 1)
			{
				$types[] = $rec;
			}
		}
		
		return $types;
	}

	/**
	* Get grouped repository object types
	*/
	static function getGroupedRepositoryObjectTypes($a_parent_obj_type)
	{
		global $ilDB, $ilPluginAdmin;
		
		$set = $ilDB->query("SELECT * FROM il_object_group");
		$groups = array();
		while ($gr_rec = $set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$groups[$gr_rec["id"]] = $gr_rec;
		}

		if (!is_array($a_parent_obj_type))
		{
			$set = $ilDB->queryF("SELECT il_object_def.* FROM il_object_def, il_object_subobj ".
				" WHERE NOT (system = 1) AND NOT (sideblock = 1) AND ".
				" parent = %s ".
				" AND subobj = id ", array("text"), array($a_parent_obj_type));
		}
		else
		{
			$q = "SELECT DISTINCT (id) as sid, il_object_def.* FROM il_object_def, il_object_subobj ".
				" WHERE NOT (system = 1) AND NOT (sideblock = 1) AND ".
				$ilDB->in("parent", $a_parent_obj_type, false, "text").
				" AND subobj = id ";
			$set = $ilDB->query($q);
		}
			
		$grouped_obj = array();
		while($rec = $ilDB->fetchAssoc($set))
		{
			if ($rec["grp"] != "")
			{
				$grouped_obj[$rec["grp"]]["pos"] = (int) $groups[$rec["grp"]]["default_pres_pos"];
				$grouped_obj[$rec["grp"]]["objs"][] = $rec["id"];
			}
			else
			{
				$grouped_obj[$rec["id"]]["pos"] = (int) $rec["default_pres_pos"];
				$grouped_obj[$rec["id"]]["objs"][] = $rec["id"];
			}
		}
//var_dump($grouped_obj);
		// now get objects from repository plugin
		$pl_names = $ilPluginAdmin->getActivePluginsForSlot(IL_COMP_SERVICE, "Repository", "robj");
		foreach ($pl_names as $pl_name)
		{
			include_once("./Services/Component/classes/class.ilPlugin.php");
			$pl_id = ilPlugin::lookupIdForName(IL_COMP_SERVICE, "Repository", "robj", $pl_name);
			if (!isset($grouped_obj[$pl_id]))
			{
				$grouped_obj[$pl_id] = array(
					"pos" => 2000,
					"objs" => array(0 => $pl_id)
					);
			}
		}

//var_dump($grouped_obj);

		$ret = ilUtil::sortArray($grouped_obj, "pos", "asc", true, true);
//var_dump($ret);
		return $ret;
	}

}
?>
