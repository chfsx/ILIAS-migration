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
* parses the objects.xml
* it handles the xml-description of all ilias objects
*
* @author Stefan Meyer <smeyer@databay>
* @version $Id$
*
* @extends PEAR
* @package ilias-core
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
		parent::ilSaxParser(ILIAS_ABSOLUTE_PATH."/objects.xml");
	}

// PUBLIC METHODS

	/**
	* get object definition by type
	*
	* @param	string	object type
	* @access	public
	*/
	function getDefinition($a_obj_name)
	{
		return $this->obj_data[$a_obj_name];
	}

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
	* get class name by type
	*
	* @param	string	object type
	* @access	public
	*/
	function getModule($a_obj_name)
	{
		return $this->obj_data[$a_obj_name]["module"];
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
	* get properties by type
	*
	* @param	string	object type
	* @access	public
	*/
	function getProperties($a_obj_name)
	{
		if (defined("ILIAS_MODULE"))
		{
			$props = array();
			if (is_array($this->obj_data[$a_obj_name]["properties"]))
			{
				foreach ($this->obj_data[$a_obj_name]["properties"] as $data => $prop)
				{
					if ($prop["module"] != "n")
					{
						$props[$data] = $prop;
					}
				}
			}
			return $props;
		}
		else
		{
			$props = array();
			if (is_array($this->obj_data[$a_obj_name]["properties"]))
			{
				foreach ($this->obj_data[$a_obj_name]["properties"] as $data => $prop)
				{
					if ($prop["module"] != 1)
					{
						$props[$data] = $prop;
					}
				}
			}
			return $props;
		}
	}

	/**
	* get devmode status by type
	*
	* @param	string	object type
	* @access	public
	*/
	function getDevMode($a_obj_name)
	{
		// always return false if devmode is enabled
		if (DEVMODE)
		{
			return false;
		}
		
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
		// always return empty array if devmode is enabled
		if (DEVMODE)
		{
			return array();
		}

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
	* checks if linking of an object type is allowed
	*
	* @param	string	object type
	* @access	public
	*/
	function allowLink($a_obj_name)
	{
		return (bool) $this->obj_data[$a_obj_name]["allow_link"];
	}

	/**
	* get all subobjects by type
	*
	* @param	string	object type
	* @access	public
	* @return	array	list of allowed object types
	*/
	function getSubObjects($a_obj_type)
	{
		$subs = array();

		if ($subobjects = $this->obj_data[$a_obj_type]["subobjects"])
		{
			// Filter some objects e.g chat object are creatable if chat is active
			$this->__filterObjects($subobjects);

			foreach ($subobjects as $data => $sub)
			{
				if ($sub["module"] != "n")
				{
					$subs[$data] = $sub;
				}
			}

			return $subs;
		}

		return $subs;
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
		$tmp_subs[] = "root";

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
		
		$sub_types = array_keys($subobjects);

		// remove object types in development from list
		foreach ($sub_types as $type)
		{
			if ($this->getDevMode($type))
			{
				unset($subobjects[$type]);
			}
		}

		return $subobjects;
	}

	/**
	* get possible actions by type
	*
	* @param	string	object type
 	* @access	public
	*/
	function getActions($a_obj_name)
	{
		$ret = (is_array($this->obj_data[$a_obj_name]["actions"])) ?
			$this->obj_data[$a_obj_name]["actions"] :
			array();
		return $ret;
	}

	/**
	* get default property by type
	*
	* @param	string	object type
	* @access	public
	*/
	function getFirstProperty($a_obj_name)
	{
		if (defined("ILIAS_MODULE"))
		{
			foreach ($this->obj_data[$a_obj_name]["properties"] as $data => $prop)
			{
				if($prop["module"] != "n")
				{
					return $data;
				}
			}
		}
		else
		{
			foreach ($this->obj_data[$a_obj_name]["properties"] as $data => $prop)
			{
				if ($prop["module"] != 1)
				{
					return $data;
				}
			}
		}
	}

	/**
	* get name of property by type
	*
	* @param	string	object type
	* @access	public
	*/
	function getPropertyName($a_cmd, $a_obj_name)
	{
		return $this->obj_data[$a_obj_name]["properties"][$a_cmd]["lng"];
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
	* get all subobjects that may be imported
	*
	* @param	string	object type
	* @access	public
	*/
	function getImportObjects($a_obj_type)
	{
		$imp = array();

		if (is_array($this->obj_data[$a_obj_type]["subobjects"]))
		{
			foreach ($this->obj_data[$a_obj_type]["subobjects"] as $sub)
			{
				if ($sub["import"] == 1)
				{
					$imp[] = $sub["name"];
				}
			}
		}

		return $imp;
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
			case 'objects':
				$this->current_tag = '';
				break;
			case 'object':
				$this->parent_tag_name = $a_attribs["name"];
				$this->current_tag = '';
				$this->obj_data["$a_attribs[name]"]["name"] = $a_attribs["name"];
				$this->obj_data["$a_attribs[name]"]["class_name"] = $a_attribs["class_name"];
				$this->obj_data["$a_attribs[name]"]["checkbox"] = $a_attribs["checkbox"];
				$this->obj_data["$a_attribs[name]"]["inherit"] = $a_attribs["inherit"];
				$this->obj_data["$a_attribs[name]"]["module"] = $a_attribs["module"];
				$this->obj_data["$a_attribs[name]"]["translate"] = $a_attribs["translate"];
				$this->obj_data["$a_attribs[name]"]["devmode"] = $a_attribs["devmode"];
				$this->obj_data["$a_attribs[name]"]["allow_link"] = $a_attribs["allow_link"];
				$this->obj_data["$a_attribs[name]"]["rbac"] = $a_attribs["rbac"];
				break;
			case 'subobj':
				$this->current_tag = "subobj";
				$this->current_tag_name = $a_attribs["name"];
				$this->obj_data[$this->parent_tag_name]["subobjects"][$this->current_tag_name]["name"] = $a_attribs["name"];
				// NUMBER OF ALLOWED SUBOBJECTS (NULL means no limit)
				$this->obj_data[$this->parent_tag_name]["subobjects"][$this->current_tag_name]["max"] = $a_attribs["max"];
				// also allow import ("1" means yes)
				$this->obj_data[$this->parent_tag_name]["subobjects"][$this->current_tag_name]["import"] = $a_attribs["import"];
				$this->obj_data[$this->parent_tag_name]["subobjects"][$this->current_tag_name]["module"] = $a_attribs["module"];
				break;
			case 'property':
				$this->current_tag = "property";
				$this->current_tag_name = $a_attribs["name"];
				$this->obj_data[$this->parent_tag_name]["properties"][$this->current_tag_name]["name"] = $a_attribs["name"];
				$this->obj_data[$this->parent_tag_name]["properties"][$this->current_tag_name]["module"] = $a_attribs["module"];
				break;
			case 'action':
				$this->current_tag = "action";
				$this->current_tag_name = $a_attribs["name"];
				$this->obj_data[$this->parent_tag_name]["actions"][$this->current_tag_name]["name"] = $a_attribs["name"];
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
		// DELETE WHITESPACES AND NEWLINES OF CHARACTER DATA
		$a_data = preg_replace("/\n/","",$a_data);
		$a_data = preg_replace("/\t+/","",$a_data);

		if (!empty($a_data))
		{
			switch ($this->current_tag)
			{
				case "subobj":
					$this->obj_data[$this->parent_tag_name]["subobjects"][$this->current_tag_name]["lng"] .= $a_data;
					break;
				case "action" :
					$this->obj_data[$this->parent_tag_name]["actions"][$this->current_tag_name]["lng"] .= $a_data;
					break;
				case "property" :
					$this->obj_data[$this->parent_tag_name]["properties"][$this->current_tag_name]["lng"] .= $a_data;
					break;
				default:
					break;
			}
		}
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

				default:
					// DO NOTHING
			}
		}
	}
}
?>
