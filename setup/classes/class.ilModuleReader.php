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
* Class ilModuleReader
*
* Reads reads module information of modules.xml files into db
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package core
*/
class ilModuleReader extends ilSaxParser
{

	function ilModuleReader()
	{
		$this->executed = false;
		parent::ilSaxParser(ILIAS_ABSOLUTE_PATH."/modules.xml");
	}
	
	function getModules()
	{
		if (!$this->executed)
		{
			$this->clearTables();
			$this->startParsing();
			$this->executed = true;
		}
	}

	
	function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser,$this);
		xml_set_element_handler($a_xml_parser,'handlerBeginTag','handlerEndTag');
		xml_set_character_data_handler($a_xml_parser,'handlerCharacterData');
	}


	/**
	* clear the tables
	*/
	function clearTables()
	{
		global $ilDB;

		$q = "DELETE FROM module";;
		$ilDB->query($q);

		$q = "DELETE FROM module_class";;
		$ilDB->query($q);

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
		global $ilDB;

		$this->current_tag = $a_name;
		
		switch ($a_name)
		{
			case 'module':
				$this->current_module = $a_attribs["name"];
				$q = "INSERT INTO module (name, dir) VALUES ".
					"(".$ilDB->quote($a_attribs["name"]).",".
					$ilDB->quote($a_attribs["dir"]).")";
				$ilDB->query($q);
				break;
				
			case 'baseclass':
				$q = "INSERT INTO module_class (module, class, dir) VALUES ".
					"(".$ilDB->quote($this->current_module).",".
					$ilDB->quote($a_attribs["name"]).",".
					$ilDB->quote($a_attribs["dir"]).")";
				$ilDB->query($q);
				break;
				
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
				case '':
			}
		}
	}

}
