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
* This class represents a checkbox property in a property form.
*
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
* @ingroup	ServicesForm
*/
class ilCheckboxInputGUI extends ilFormPropertyGUI
{
	protected $value = "1";
	protected $checked;
	protected $sub_items = array();
	protected $optiontitle = "";
	
	/**
	* Constructor
	*
	* @param	string	$a_title	Title
	* @param	string	$a_postvar	Post Variable
	*/
	function __construct($a_title = "", $a_postvar = "")
	{
		parent::__construct($a_title, $a_postvar);
		$this->setType("checkbox");
	}

	/**
	* Set Value.
	*
	* @param	string	$a_value	Value
	*/
	function setValue($a_value)
	{
		$this->value = $a_value;
	}

	/**
	* Get Value.
	*
	* @return	string	Value
	*/
	function getValue()
	{
		return $this->value;
	}

	/**
	* Set Checked.
	*
	* @param	boolean	$a_checked	Checked
	*/
	function setChecked($a_checked)
	{
		$this->checked = $a_checked;
	}

	/**
	* Get Checked.
	*
	* @return	boolean	Checked
	*/
	function getChecked()
	{
		return $this->checked;
	}

	/**
	* Set Option Title (optional).
	*
	* @param	string	$a_optiontitle	Option Title (optional)
	*/
	function setOptionTitle($a_optiontitle)
	{
		$this->optiontitle = $a_optiontitle;
	}

	/**
	* Get Option Title (optional).
	*
	* @return	string	Option Title (optional)
	*/
	function getOptionTitle()
	{
		return $this->optiontitle;
	}

	/**
	* Set value by array
	*
	* @param	object	$a_item		Item
	*/
	function setValueByArray($a_values)
	{
		$this->setChecked($a_values[$this->getPostVar()]);
	}

	/**
	* Add Subitem
	*
	* @param	object	$a_item		Item
	*/
	function addSubItem($a_item)
	{
		$this->sub_items[] = $a_item;
	}

	/**
	* Get Subitems
	*
	* @return	array	Array of items
	*/
	function getSubItems()
	{
		return $this->sub_items;
	}

	/**
	* Check input, strip slashes etc. set alert, if input is not ok.
	*
	* @return	boolean		Input ok, true/false
	*/	
	function checkInput()
	{
		global $lng;
		
		$_POST[$this->getPostVar()] = 
			ilUtil::stripSlashes($_POST[$this->getPostVar()]);

		$ok = true;
		foreach($this->getSubItems() as $item)
		{
			$item_ok = $item->checkInput();
			if(!$item_ok)
			{
				$ok = false;
			}
		}
		return $ok;
	}

	/**
	* Insert property html
	*
	*/
	function insert(&$a_tpl)
	{
		$a_tpl->setCurrentBlock("prop_checkbox");
		$a_tpl->setVariable("POST_VAR", $this->getPostVar());
		$a_tpl->setVariable("PROPERTY_VALUE", $this->getValue());
		$a_tpl->setVariable("OPTION_TITLE", $this->getOptionTitle());
		if ($this->getChecked())
		{
			$a_tpl->setVariable("PROPERTY_CHECKED",
				'checked="checked"');
		}
		
		// subitems
		if (count($this->getSubItems()) > 0)
		{
			$pf = new ilPropertyFormGUI();
			$pf->setMode("subform");
			$pf->setItems($this->getSubItems());
			$a_tpl->setVariable("SUB_FORM", $pf->getContent());
		}

		$a_tpl->parseCurrentBlock();
	}

}
