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
* Class ilMediaItem
*
* Media Item, component of a media object (file or reference)
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilMediaItem
{
	var $purpose;
	var $width;
	var $height;
	var $caption;
	var $halign;
	var $parameters;

	function ilMediaItem()
	{
		$this->parameters = array();
	}


	function setPurpose($a_purpose)
	{
		$this->purpose = $a_purpose;
	}

	function getPurpose()
	{
		return $this->purpose;
	}

	/**
	* get width
	*/
	function getWidth()
	{
		return $this->width;
	}

	/**
	* set width
	*/
	function setWidth($a_width)
	{
		$this->width = $a_width;
	}

	/**
	* get height
	*/
	function getHeight()
	{
		return $this->height;
	}

	/**
	* set height
	*/
	function setHeight($a_height)
	{
		$this->height = $a_height;
	}

	/**
	* set caption
	*/
	function setCaption($a_caption)
	{
		$this->caption = $a_caption;
	}

	/**
	* get caption
	*/
	function getCaption()
	{
		return $this->caption;
	}

	/**
	* set horizontal align
	*/
	function setHAlign($a_halign)
	{
		$this->halign = $a_halign;
	}

	/**
	* get horizontal align
	*/
	function getHAlign()
	{
		return $this->halign;
	}


	/**
	* set parameter
	*/
	function setParameter($a_name, $a_value)
	{
		$this->parameters[$a_name] = $a_value;
	}

	/**
	* get all parameters
	*/
	function getParameters()
	{
		return $this->parameters;
	}

	/**
	* get a single parameter
	*/
	function getParameter($a_name)
	{
		return $this->parameters[$a_name];
	}

}
?>
