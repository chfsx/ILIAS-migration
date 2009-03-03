<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
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

/** @defgroup ServicesXHTMLPage Services/XHTMLPage
 */

/**
* XHTML Page class. Should be used to store XHTML pages created by tiny
* (e.g. for ategories).
* 
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
* @ingroup	ServicesXHTMLPage
*/
class ilXHTMLPage
{
	var $id = 0;
	var $content = "";

	/**
	* Constructor
	*
	* @param	int		$a_id		page ID
	*/
	function ilXHTMLPage($a_id = 0)
	{
		if ($a_id > 0)
		{
			$this->setId($a_id);
			$this->read();
		}
	}
	
	/**
	* Get page ID.
	*
	* @return	int		page ID
	*/
	function getId()
	{
		return $this->id;
	}
	
	/**
	* Set page ID.
	*
	* @param	int		$a_id		page ID
	*/
	function setId($a_id)
	{
		$this->id = $a_id;
	}

	/**
	* Get content of page.
	*
	* @return	string		page content
	*/
	function getContent()
	{
		return $this->content;
	}
	
	/**
	* Set content of page.
	*
	* @param	string	$a_content		page content
	*/
	function setContent($a_content)
	{
		$this->content = $a_content;
	}

	/**
	* Read page data from database.
	*/
	function read()
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM xhtml_page WHERE id = ".
			$ilDB->quote($this->getId(), "integer"));
		if ($rec = $ilDB->fetchAssoc($set))
		{
			$this->setContent($rec["content"]);
		}
	}
	
	/**
	* Lookup Content
	*/
	function _lookupContent($a_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT content FROM xhtml_page WHERE id = ".
			$ilDB->quote($a_id, "integer"));
		if ($rec = $ilDB->fetchAssoc($set))
		{
			return $rec["content"];
		}
	}

	/**
	* Lookup Saved Content
	*/
	function _lookupSavedContent($a_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT save_content FROM xhtml_page WHERE id = ".
			$ilDB->quote($a_id, "integer"));
		if ($rec = $ilDB->fetchAssoc($set))
		{
			return $rec["save_content"];
		}
	}

	/**
	* Save the page.
	*/
	function save()
	{
		global $ilDB;
		
		if ($this->getId() > 0)
		{
			$old_content = ilXHTMLPage::_lookupContent($this->getId());
			$ilDB->update("xhtml_page", array(
				"content" => array("clob", $this->getContent()),
				"save_content" => array("clob", $old_content)
				), array (
				"id" => array("integer", $this->getId())
				));
		}
		else
		{
			$this->setId($ilDB->nextId("xhtml_page"));
			$ilDB->insert("xhtml_page", array(
				"id" => array("integer", $this->getId()),
				"content" => array("clob", $this->getContent())
				));
		}
	}
	
	/**
	* Undo last change.
	*/
	function undo()
	{
		global $ilDB;
		
		if ($this->getId() > 0)
		{
			$content = ilXHTMLPage::_lookupContent($this->getId());
			$save_content = ilXHTMLPage::_lookupSavedContent($this->getId());
			$ilDB->update("xhtml_page", array(
				"content" => array("clob", $save_content),
				"save_content" => array("clob", $content)
				), array (
				"id" => array("integer", $this->getId())
				));
		}
	}

	/**
	* Clear page.
	*/
	function clear()
	{
		global $ilDB;
		
		if ($this->getId() > 0)
		{
			$this->setContent("");
			$this->save();
		}
	}

}
?>
