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

require_once("content/classes/class.ilParagraph.php");

/**
* Page Parser, parses xml content of page as stored in db table lm_page_object
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @extends ilSaxParser
* @package content
*/
class ilPageParser extends ilSaxParser
{
	var $paragraph;
	var $page_object;
	var $xml_data;
	var $current_element;


	/**
	* Constructor
	* @access	public
	*/
	function ilPageParser(&$a_page_object, $a_xml_data)
	{
		global $ilias, $lng;

//echo "Parsing:".htmlentities($a_xml_data).":<br>";
		$a_xml_data = "<dummy>".$a_xml_data."</dummy>";
		$this->page_object =& $a_page_object;
		$this->xml_data = $a_xml_data;
		$this->ilias = &$ilias;
		$this->lng = &$lng;
		$this->current_element = array();

		parent::ilSaxParser($a_xml_file);	//???
	}

	function startParsing()
	{
		$xml_parser = $this->createParser();
		$this->setOptions($xml_parser);
		$this->setHandlers($xml_parser);
		$this->parse($xml_parser);
		$this->freeParser($xml_parser);
	}


	function parse($a_xml_parser)
	{
		$parseOk = xml_parse($a_xml_parser, $this->xml_data);
		if(!$parseOk
		   && (xml_get_error_code($a_xml_parser) != XML_ERROR_NONE))
		{
			$this->ilias->raiseError("XML Parse Error: ",$this->ilias->error_obj->FATAL);
		}
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


	/*
	* update parsing status for a element begin
	*/
	function beginElement($a_name)
	{
		if(!isset($this->status["$a_name"]))
		{
			$this->cnt[$a_name] == 1;
		}
		else
		{
			$this->cnt[$a_name]++;
		}
		$this->current_element[count($this->current_element)] = $a_name;
	}

	/*
	* update parsing status for an element ending
	*/
	function endElement($a_name)
	{
		$this->cnt[$a_name]--;
		unset ($this->current_element[count($this->current_element) - 1]);
	}

	/*
	* returns current element
	*/
	function getCurrentElement()
	{
		return ($this->current_element[count($this->current_element) - 1]);
	}

	/**
	* handler for begin of element
	*/
	function handlerBeginTag($a_xml_parser,$a_name,$a_attribs)
	{
//echo "BeginTag:$a_name:<br>";
		switch($a_name)
		{
			case "Paragraph":
				$this->paragraph =& new ilParagraph();
				$this->paragraph->setLanguage($a_attribs["Language"]);
				$this->paragraph->setCharacteristic($a_attribs["Characteristic"]);
				$this->page_object->appendContent($this->paragraph);
				break;

			case "br":
				if (is_object($this->paragraph))
				{
					$this->paragraph->appendText("<br />");
				}
				break;

		}
		$this->beginElement($a_name);
	}

	/**
	* handler for end of element
	*/
	function handlerEndTag($a_xml_parser,$a_name)
	{
//echo "EndTag:$a_name:<br>";
		/*
		switch($a_name)
		{
		}
		*/
		$this->endElement($a_name);
	}

	/**
	* handler for character data
	*/
	function handlerCharacterData($a_xml_parser,$a_data)
	{
//echo "Data:$a_name:<br>";
		// DELETE WHITESPACES AND NEWLINES OF CHARACTER DATA
		$a_data = preg_replace("/\n/","",$a_data);
		$a_data = preg_replace("/\t+/","",$a_data);
		if(!empty($a_data))
		{
			switch($this->getCurrentElement())
			{
				case "Paragraph":
					$this->paragraph->appendText($a_data);
//echo "setText(".htmlentities($a_data)."), strlen:".strlen($a_data)."<br>";
					break;
			}
		}

	}

}
?>
