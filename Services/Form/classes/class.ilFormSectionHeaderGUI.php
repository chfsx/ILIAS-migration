<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2007 ILIAS open source, University of Cologne            |
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
* This class represents a section header in a property form.
*
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
*/
class ilFormSectionHeaderGUI
{
	protected $type;
	protected $title;
	protected $info;
	
	/**
	* Constructor
	*
	* @param
	*/
	function __construct()
	{
		$this->setType("section_header");
	}

	/**
	* Set Type.
	*
	* @param	string	$a_type	Type
	*/
	function setType($a_type)
	{
		$this->type = $a_type;
	}

	/**
	* Get Type.
	*
	* @return	string	Type
	*/
	function getType()
	{
		return $this->type;
	}

	/**
	* Set Title.
	*
	* @param	string	$a_title	Title
	*/
	function setTitle($a_title)
	{
		$this->title = $a_title;
	}

	/**
	* Get Title.
	*
	* @return	string	Title
	*/
	function getTitle()
	{
		return $this->title;
	}

	/**
	* Set Information Text.
	*
	* @param	string	$a_info	Information Text
	*/
	function setInfo($a_info)
	{
		$this->info = $a_info;
	}

	/**
	* Get Information Text.
	*
	* @return	string	Information Text
	*/
	function getInfo()
	{
		return $this->info;
	}

	/**
	* Insert property html
	*
	*/
	function insert(&$a_tpl)
	{
		$a_tpl->setCurrentBlock("header");
		$a_tpl->setVariable("TXT_TITLE", $this->getTitle());
		$a_tpl->parseCurrentBlock();
	}

}
