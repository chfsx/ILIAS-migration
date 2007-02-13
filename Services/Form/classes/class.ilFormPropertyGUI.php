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
* This class represents a property in a property form.
*
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
*/
class ilFormPropertyGUI
{
	protected $type;
	protected $title;
	protected $postvar;
	protected $info;
	protected $alert;
	protected $required = false;
	
	/**
	* Constructor
	*
	* @param	string	$a_title	Title
	* @param	string	$a_postvar	Post Variable
	*/
	function __construct($a_title = "", $a_postvar = "")
	{
		$this->setTitle($a_title);
		$this->setPostVar($a_postvar);
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
	* Set Post Variable.
	*
	* @param	string	$a_postvar	Post Variable
	*/
	function setPostVar($a_postvar)
	{
		$this->postvar = $a_postvar;
	}

	/**
	* Get Post Variable.
	*
	* @return	string	Post Variable
	*/
	function getPostVar()
	{
		return $this->postvar;
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
	* Set Alert Text.
	*
	* @param	string	$a_alert	Alert Text
	*/
	function setAlert($a_alert)
	{
		$this->alert = $a_alert;
	}

	/**
	* Get Alert Text.
	*
	* @return	string	Alert Text
	*/
	function getAlert()
	{
		return $this->alert;
	}

	/**
	* Set Required.
	*
	* @param	boolean	$a_required	Required
	*/
	function setRequired($a_required)
	{
		$this->required = $a_required;
	}

	/**
	* Get Required.
	*
	* @return	boolean	Required
	*/
	function getRequired()
	{
		return $this->required;
	}
	
	/**
	* Check input, strip slashes etc. set alert, if input is not ok.
	*
	* @return	boolean		Input ok, true/false
	*/	
	function checkInput()
	{
		return false;		// please overwrite
	}

}
