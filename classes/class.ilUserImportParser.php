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

define ("IL_EXTRACT_ROLES", 1);
define ("IL_USER_IMPORT", 2);

require_once("classes/class.ilSaxParser.php");

/**
* User Import Parser
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @extends ilSaxParser
* @package core
*/
class ilUserImportParser extends ilSaxParser
{
	var $roles;

	/**
	* Constructor
	*
	* @param	string		$a_xml_file		xml file
	* @param	int			$a_mode			IL_EXTRACT_ROLES | IL_USER_IMPORT
	*
	* @access	public
	*/
	function ilUserImportParser($a_xml_file, $a_mode = IL_USER_IMPORT)
	{
		global $lng, $tree;

		$this->roles = array();

		parent::ilSaxParser($a_xml_file);
	}


	/**
	* set event handler
	* should be overwritten by inherited class
	* @access	private
	*/
	function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser,$this);
		xml_set_element_handler($a_xml_parser,'handlerBeginTag','handlerEndTag');
		xml_set_character_data_handler($a_xml_parser,'handlerCharacterData');
	}

	function startParsing()
	{
		parent::startParsing();
	}



	/**
	* generate a tag with given name and attributes
	*
	* @param	string		"start" | "end" for starting or ending tag
	* @param	string		element/tag name
	* @param	array		array of attributes
	*/
	function buildTag ($type, $name, $attr="")
	{
		$tag = "<";

		if ($type == "end")
			$tag.= "/";

		$tag.= $name;

		if (is_array($attr))
		{
			while (list($k,$v) = each($attr))
				$tag.= " ".$k."=\"$v\"";
		}

		$tag.= ">";

		return $tag;
	}

	/**
	* handler for begin of element
	*/
	function handlerBeginTag($a_xml_parser, $a_name, $a_attribs)
	{

		switch($a_name)
		{
			case "Role":
				$this->current_role_id = $a_attribs["Id"];
				break;
		}
	}


	/**
	* handler for end of element
	*/
	function handlerEndTag($a_xml_parser, $a_name)
	{

		switch($a_name)
		{
			case "Role":
				$this->roles[$this->current_role_id] = $this->cdata;
				break;

		}

		$this->cdata = "";
	}

	/**
	* handler for character data
	*/
	function handlerCharacterData($a_xml_parser, $a_data)
	{
		// i don't know why this is necessary, but
		// the parser seems to convert "&gt;" to ">" and "&lt;" to "<"
		// in character data, but we don't want that, because it's the
		// way we mask user html in our content, so we convert back...
		$a_data = str_replace("<","&lt;",$a_data);
		$a_data = str_replace(">","&gt;",$a_data);

		// DELETE WHITESPACES AND NEWLINES OF CHARACTER DATA
		$a_data = preg_replace("/\n/","",$a_data);
		$a_data = preg_replace("/\t+/","",$a_data);
		if(!empty($a_data))
		{
			$this->cdata .= $a_data;
		}
	}

	/**
	* get collected roles
	*/
	function getCollectedRoles()
	{
		return $this->roles;
	}
}
?>
