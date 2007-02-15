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

/** @defgroup ServicesForm Services/Form
 */

/**
* This class represents a form user interface
*
* @author 	Alex Killing <alex.killing@gmx.de> 
* @version 	$Id$
* @ingroup	ServicesForm
*/
class ilFormGUI
{
	protected $formaction;
	protected $multipart = false;
	
	/**
	* Constructor
	*
	* @param
	*/
	function ilFormGUI()
	{
	}

	/**
	* Set FormAction.
	*
	* @param	string	$a_formaction	FormAction
	*/
	function setFormAction($a_formaction)
	{
		$this->formaction = $a_formaction;
	}

	/**
	* Get FormAction.
	*
	* @return	string	FormAction
	*/
	function getFormAction()
	{
		return $this->formaction;
	}

	/**
	* Set Enctype Multipart/Formdata true/false.
	*
	* @param	boolean	$a_multipart	Enctype Multipart/Formdata true/false
	*/
	function setMultipart($a_multipart)
	{
		$this->multipart = $a_multipart;
	}

	/**
	* Get Enctype Multipart/Formdata true/false.
	*
	* @return	boolean	Enctype Multipart/Formdata true/false
	*/
	function getMultipart()
	{
		return $this->multipart;
	}

	/**
	* Get HTML.
	*/
	function getHTML()
	{
		$tpl = new ilTemplate("tpl.form.html", true, true, "Services/Form");
		$tpl->setVariable("FORM_CONTENT", $this->getContent());
		$tpl->setVariable("FORM_ACTION", $this->getFormAction());
		if ($this->getMultipart())
		{
			$tpl->touchBlock("multipart");
		}
		return $tpl->get();
	}

	/**
	* Get Content.
	*/
	function getContent()
	{
		return "";
	}

}
