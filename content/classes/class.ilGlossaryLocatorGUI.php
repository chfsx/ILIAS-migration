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
* Glossary Locator GUI
*
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilGlossaryLocatorGUI
{
	var $mode;
	var $temp_var;
	var $tree;
	var $obj;
	var $lng;
	var $tpl;


	function ilGlossaryLocatorGUI()
	{
		global $lng, $tpl, $tree;

		$this->mode = "edit";
		$this->temp_var = "LOCATOR";
		$this->lng =& $lng;
		$this->tpl =& $tpl;
		$this->tree =& $tree;
	}

	function setTemplateVariable($a_temp_var)
	{
		$this->temp_var = $a_temp_var;
	}

	function setTerm(&$a_term)
	{
		$this->term =& $a_term;
	}

	function setGlossary(&$a_glossary)
	{
		$this->glossary =& $a_glossary;
	}

	function setDefinition(&$a_def)
	{
		$this->definition =& $a_def;
	}

	function setMode($a_mode)
	{
		$this->mode = $a_mode;
	}

	/**
	* display locator
	*/
	function display()
	{
		global $lng, $ilCtrl;

		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");

		$path = $this->tree->getPathFull($_GET["ref_id"]);

		if (is_object($this->term))
		{
			$modifier = 0;
		}
		else
		{
			$modifier = 1;
		}

		switch($this->mode)
		{
			case "edit":
				$script = "glossary_edit.php";
				break;

			case "presentation":
				$script = "glossary_presentation.php";
				break;
		}
		$repository = "../repository.php";

		foreach ($path as $key => $row)
		{
			if (($key < count($path)-$modifier))
			{
				$this->tpl->touchBlock("locator_separator");
			}

			$this->tpl->setCurrentBlock("locator_item");
			if ($row["child"] == $this->tree->getRootId())
			{
				$title = $this->lng->txt("repository");
				$link = $repository."?ref_id=".$row["child"];
			}
			else if (($_GET["ref_id"] == $row["child"]))
			{
				$title = $this->glossary->getTitle();
				if ($this->mode == "edit")
				{
					$link = $ilCtrl->getLinkTargetByClass("ilobjglossarygui", "listTerms");
				}
				else
				{
					$link = $script."?ref_id=".$_GET["ref_id"];
				}
			}
			else if ($row["type"] == "grp")
			{
				$title = $row["title"];
				$link = "../group.php?cmd=view&ref_id=".$row["child"];
			}
			else
			{
				$title = $row["title"];
				$link = $repository."?ref_id=".$row["child"];
			}
			$this->tpl->setVariable("ITEM", $title);
			$this->tpl->setVariable("LINK_ITEM", $link);
			$this->tpl->setVariable("LINK_TARGET", "target=\"bottom\"");
			$this->tpl->parseCurrentBlock();
		}

		if (is_object($this->definition))
		{
			$this->tpl->touchBlock("locator_separator");
		}

		if (is_object($this->term))
		{
			$this->tpl->setCurrentBlock("locator_item");
			$this->tpl->setVariable("ITEM", $this->term->getTerm());
			if ($this->mode == "edit")
			{
				$this->tpl->setVariable("LINK_ITEM",
					$ilCtrl->getLinkTargetByClass("ilglossarytermgui", "listDefinitions"));
			}
			else
			{
				$this->tpl->setVariable("LINK_ITEM", $script."?ref_id=".$_GET["ref_id"].
					"&cmd=listDefinitions&term_id=".$this->term->getId());
			}
			$this->tpl->parseCurrentBlock();
		}

		//$this->tpl->touchBlock("locator_separator");

		if (is_object($this->definition))
		{
			$this->tpl->setCurrentBlock("locator_item");
			$this->tpl->setVariable("ITEM", $this->lng->txt("cont_definition")." ".$this->definition->getNr());
			if ($this->mode == "edit")
			{
				$this->tpl->setVariable("LINK_ITEM",
					$ilCtrl->getLinkTargetByClass("ilpageobjectgui", "view"));
			}
			else
			{
				$this->tpl->setVariable("LINK_ITEM", $script."?ref_id=".$_GET["ref_id"].
					"&cmd=view&def=".$_GET["def"]);
			}
			$this->tpl->parseCurrentBlock();
		}

		//$this->tpl->touchBlock("locator_separator");

		$this->tpl->setCurrentBlock("locator");
		$this->tpl->setVariable("TXT_LOCATOR", $debug.$this->lng->txt("locator"));
		$this->tpl->parseCurrentBlock();
	}

}
?>
