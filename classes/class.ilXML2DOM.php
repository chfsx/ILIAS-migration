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
* Class for creating an object (new node) by parsing XML code and adding it to an existing DOM object
*  
* @author	Jens Conze <jconze@databay.de>
* @version	$Id$
*/

class XMLStruct
{
	var $childs = array();	// child nodes
	var	$parent;			// parent node
	var $name;				// tag name
	var $content = array();	// tag content
	var $attrs;				// tag attributes

	/**
	* constructor
	*/
	function XMLStruct($a_name = "", $a_attrs = array())
	{
		$this->name = $a_name;
		$this->attrs = $a_attrs;
	}

	/**
	* append node
	*/
	function append($a_name, $a_attrs)
	{
		$struct = new XMLStruct($a_name, $a_attrs);
		$struct->parent =& $GLOBALS["lastObj"];
		
		$GLOBALS["lastObj"] =& $struct;
		$this->childs[] =& $struct;
	}

	/**
	* set parent node
	*/
	function setParent()
	{
		$GLOBALS["lastObj"] =& $GLOBALS["lastObj"]->parent;
	}

	/**
	* set content text
	*/
	function setContent($a_data)
	{
		$this->content[] = $a_data;
	}

	/**
	* insert new node in existing DOM object
	* @param	object	$dom	DOM object
	* @param	object	$node	parent node
	*/
	function insert(&$dom, &$node)
	{
		$newNode = $dom->create_element($this->name);
		if ($this->content != "")
		{
			$newNode->set_content(implode("", $this->content));
		}
		if (is_array($this->attrs))
		{
			#vd($this->attrs);
			reset ($this->attrs);
			while (list ($key, $val) = each ($this->attrs)) {
				$newNode->set_attribute($key, $val);
			}			
		}
		$node = $node->append_child($newNode);
		for ($j = 0; $j < count($this->childs); $j++)
		{
			$this->childs[$j]->insert($dom, $node);
		}
		$node = $node->parent_node();
	}
}

class XML2DOM
{

	function XML2DOM($a_xml)
	{
		$xml_parser = xml_parser_create();
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_object($xml_parser, $this);
		xml_set_element_handler($xml_parser, "startElement", "endElement");
		xml_set_character_data_handler($xml_parser, "characterData");
					
		if (!xml_parse($xml_parser, $a_xml, true))
		{
			die(sprintf("XML error: %s at line %d",
	                    xml_error_string(xml_get_error_code($xml_parser)),
	                    xml_get_current_line_number($xml_parser)));
		}
		xml_parser_free($xml_parser);
	}

	function startElement($a_parser, $a_name, $a_attrs)
	{
		if (!is_object($this->xmlStruct))
		{
			#vd($a_attrs);
			$this->xmlStruct = new XMLStruct($a_name, $a_attrs);
			$GLOBALS["lastObj"] =& $this->xmlStruct;
		}
		else
		{
			$GLOBALS["lastObj"]->append($a_name, $a_attrs);
		}
	}

	function endElement($a_parser, $a_name)
	{
		$GLOBALS["lastObj"]->setParent();
	}

	function characterData($a_parser, $a_data)
	{
		$a_data = preg_replace("/&/","&amp;",$a_data);
		$GLOBALS["lastObj"]->setContent($a_data);
	}

	function insertNode(&$dom, &$node)
	{
		$node = $this->xmlStruct->insert($dom, $node);
	}
}

