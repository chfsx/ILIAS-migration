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

/*
* Explorer View for Learning Module Editor
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/

require_once("content/classes/class.ilLMExplorer.php");
require_once("content/classes/class.ilStructureObject.php");

class ilLMTOCExplorer extends ilLMExplorer
{
	var	$offline;
	
	/**
	* Constructor
	* @access	public
	* @param	string	scriptname
	* @param    int user_id
	*/
	function ilLMTOCExplorer($a_target,&$a_lm_obj)
	{
		$this->offline = false;
		$this->force_open_path = array();
		parent::ilLMExplorer($a_target, $a_lm_obj);
	}
	
	/**
	* set offline mode
	*/
	function setOfflineMode($a_offline = true)
	{
		$this->offline = $a_offline;
	}

	/**
	* get offline mode
	*/
	function offlineMode()
	{
		return $this->offline;
	}
	
	
	/**
	* set force open path
	*/
	function setForceOpenPath($a_path)
	{
		$this->force_open_path = $a_path;
	}
	
	/**
	* standard implementation for title, maybe overwritten by derived classes
	*/
	function buildTitle($a_title, $a_id, $a_type)
	{
//echo "<br>-$a_title-$a_type-$a_id-";
		if ($a_type == "st")
		{
			return ilStructureObject::_getPresentationTitle($a_id,
				$this->lm_obj->isActiveNumbering());
		}

		if ($this->lm_obj->getTOCMode() == "chapters" || $a_type != "pg")
		{
			return $a_title;
		}
		else
		{
			if ($a_type == "pg")
			{
				return ilLMPageObject::_getPresentationTitle($a_id,
					$this->lm_obj->getPageHeader(), $this->lm_obj->isActiveNumbering());
			}
		}
	}
	
	
	/**
	* get image path (may be overwritten by derived classes)
	*/
	function getImage($a_name)
	{
		return ilUtil::getImagePath($a_name, false, "output", $this->offlineMode());
	}

	
	/**
	* build link target
	*/
	function buildLinkTarget($a_node_id, $a_type)
	{
		if (!$this->offlineMode())
		{
			return parent::buildLinkTarget($a_node_id, $a_type);
		}
		else
		{
			if ($a_node_id < 1)
			{
				$a_node_id = $this->tree->getRootId();
			}
			if ($a_type != "pg")
			{
				$a_node = $this->tree->fetchSuccessorNode($a_node_id, "pg");
				$a_node_id = $a_node["child"];
			}
			if (!$this->lm_obj->cleanFrames())
			{
				return "frame_".$a_node_id."_maincontent.html";
			}
			else
			{
				return "lm_pg_".$a_node_id.".html";
			}
		}
	}
	
	/**
	* force expansion of node
	*/
	function forceExpanded($a_obj_id)
	{
		if ($this->offlineMode())
		{
			return true;
		}
		else
		{
			if (in_array($a_obj_id, $this->force_open_path))
			{
				return true;
			}
			return false;
		}
	}



} // END class.ilLMTOCExplorer
?>
