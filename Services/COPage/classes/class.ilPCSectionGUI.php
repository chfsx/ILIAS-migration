<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2008 ILIAS open source, University of Cologne            |
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

require_once("./Services/COPage/classes/class.ilPCSection.php");
require_once("./Services/COPage/classes/class.ilPageContentGUI.php");

/**
* Class ilPCSectionGUI
*
* User Interface for Section Editing
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ingroup ServicesCOPage
*/
class ilPCSectionGUI extends ilPageContentGUI
{

	/**
	* Constructor
	* @access	public
	*/
	function ilPCSectionGUI(&$a_pg_obj, &$a_content_obj, $a_hier_id, $a_pc_id = "")
	{
		parent::ilPageContentGUI($a_pg_obj, $a_content_obj, $a_hier_id, $a_pc_id);
		
		$this->setCharacteristics(ilPCSectionGUI::_getStandardCharacteristics());
	}

	/**
	* Get standard characteristics
	*/
	static function _getStandardCharacteristics()
	{
		global $lng;
		
		return array("Block" => $lng->txt("cont_Block"),
			"Mnemonic" => $lng->txt("cont_Mnemonic"),
			"Remark" => $lng->txt("cont_Remark"),
			"Example" => $lng->txt("cont_Example"),
			"Additional" => $lng->txt("cont_Additional"),
			"Special" => $lng->txt("cont_Special"),
			"Excursus" => $lng->txt("cont_Excursus"));
	}
	
	/**
	* Get characteristics
	*/
	static function _getCharacteristics($a_style_id)
	{
		$chars = ilPCSectionGUI::_getStandardCharacteristics();

		if ($a_style_id > 0 &&
			ilObject::_lookupType($a_style_id) == "sty")
		{
			include_once("./Services/Style/classes/class.ilObjStyleSheet.php");
			$style = new ilObjStyleSheet($a_style_id);
			$chars = $style->getCharacteristics("section");
			$new_chars = array();
			foreach ($chars as $char)
			{
				if ($chars[$char] != "")	// keep lang vars for standard chars
				{
					$new_chars[$char] = $chars[$char];
				}
				else
				{
					$new_chars[$char] = $char;
				}
				asort($new_chars);
			}
			$chars = $new_chars;
		}
		return $chars;
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		$this->getCharacteristicsOfCurrentStyle("section");	// scorm-2004
		
		// get next class that processes or forwards current command
		$next_class = $this->ctrl->getNextClass($this);

		// get current command
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				$ret =& $this->$cmd();
				break;
		}

		return $ret;
	}

	/**
	* Insert new section form.
	*/
	function insert()
	{
		$this->edit(true);
	}

	/**
	* Edit section form.
	*/
	function edit($a_insert = false)
	{
		global $ilCtrl, $tpl, $lng;
		
		$this->displayValidationError();
		
		// edit form
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		if ($a_insert)
		{
			$form->setTitle($this->lng->txt("cont_insert_section"));
		}
		else
		{
			$form->setTitle($this->lng->txt("cont_update_section"));
		}
		
		// characteristic selection
		require_once("./Services/Form/classes/class.ilRadioMatrixInputGUI.php");
		$char_prop = new ilRadioMatrixInputGUI($this->lng->txt("cont_characteristic"),
			"characteristic");
			
		$chars = $this->getCharacteristics();
		if (is_object($this->content_obj))
		{
			if ($chars[$a_seleted_value] == "" && ($this->content_obj->getCharacteristic() != ""))
			{
				$chars = array_merge(
					array($this->content_obj->getCharacteristic() => $this->content_obj->getCharacteristic()),
					$chars);
			}
		}

		$selected = ($a_insert)
			? "Block"
			: $this->content_obj->getCharacteristic();
			
		foreach($chars as $k => $char)
		{
			$chars[$k] = '<div class="ilc_section_'.$k.'">'.
				$char.'</div>';
		}

		$char_prop->setValue($selected);
		$char_prop->setOptions($chars);
		$form->addItem($char_prop);
		
		// save/cancel buttons
		if ($a_insert)
		{
			$form->addCommandButton("create_section", $lng->txt("save"));
			$form->addCommandButton("cancelCreate", $lng->txt("cancel"));
		}
		else
		{
			$form->addCommandButton("update", $lng->txt("save"));
			$form->addCommandButton("cancelUpdate", $lng->txt("cancel"));
		}
		$html = $form->getHTML();
		$tpl->setContent($html);
		return $ret;

	}


	/**
	* Create new Section.
	*/
	function create()
	{
		$this->content_obj = new ilPCSection($this->dom);
		$this->content_obj->create($this->pg_obj, $this->hier_id, $this->pc_id);
		$this->content_obj->setCharacteristic($_POST["characteristic"]);
		$this->updated = $this->pg_obj->update();
		if ($this->updated === true)
		{
			$this->ctrl->returnToParent($this, "jump".$this->hier_id);
		}
		else
		{
			$this->insert();
		}
	}

	/**
	* Update Section.
	*/
	function update()
	{
		$this->content_obj->setCharacteristic($_POST["characteristic"]);
		$this->updated = $this->pg_obj->update();
		if ($this->updated === true)
		{
			$this->ctrl->returnToParent($this, "jump".$this->hier_id);
		}
		else
		{
			$this->pg_obj->addHierIDs();
			$this->edit();
		}
	}
}
?>
