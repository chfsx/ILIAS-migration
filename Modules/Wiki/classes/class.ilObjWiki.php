<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
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

/** @defgroup ModulesWiki Modules/Wiki
 */

require_once "./classes/class.ilObject.php";

/**
* Class ilObjWiki
* 
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
* @ingroup ModulesWiki
*/
class ilObjWiki extends ilObject
{
	protected $online = false;
	
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjWiki($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "wiki";
		$this->ilObject($a_id,$a_call_by_reference);
	}

	/**
	* Set Online.
	*
	* @param	boolean	$a_online	Online
	*/
	function setOnline($a_online)
	{
		$this->online = $a_online;
	}

	/**
	* Get Online.
	*
	* @return	boolean	Online
	*/
	function getOnline()
	{
		return $this->online;
	}

	/**
	* Set Start Page.
	*
	* @param	string	$a_startpage	Start Page
	*/
	function setStartPage($a_startpage)
	{
		$this->startpage = $a_startpage;
	}

	/**
	* Get Start Page.
	*
	* @return	string	Start Page
	*/
	function getStartPage()
	{
		return $this->startpage;
	}

	/**
	* Set ShortTitle.
	*
	* @param	string	$a_shorttitle	ShortTitle
	*/
	function setShortTitle($a_shorttitle)
	{
		$this->shorttitle = $a_shorttitle;
	}

	/**
	* Get ShortTitle.
	*
	* @return	string	ShortTitle
	*/
	function getShortTitle()
	{
		return $this->shorttitle;
	}

	/**
	* Create new wiki
	*/
	function create()
	{
		global $ilDB;

		parent::create();
		
		$query = "INSERT INTO il_wiki_data (".
			" id".
			", online".
			", startpage".
			", short".
			" ) VALUES (".
			$ilDB->quote($this->getId())
			.",".$ilDB->quote($this->getOnline())
			.",".$ilDB->quote($this->getStartPage())
			.",".$ilDB->quote($this->getShortTitle())
			.")";
		$ilDB->query($query);
		
		// create start page
		if ($this->getStartPage() != "")
		{
			include_once("./Modules/Wiki/classes/class.ilWikiPage.php");
			$start_page = new ilWikiPage();
			$start_page->setWikiId($this->getId());
			$start_page->setTitle($this->getStartPage());
			$start_page->create();
		}

	}

	/**
	* update object data
	*
	* @access	public
	* @return	boolean
	*/
	function update()
	{
		global $ilDB;
		
		if (!parent::update())
		{			
			return false;
		}

		// update wiki data
		$query = "UPDATE il_wiki_data SET ".
			" online = ".$ilDB->quote($this->getOnline()).
			",startpage = ".$ilDB->quote($this->getStartPage()).
			",short = ".$ilDB->quote($this->getShortTitle()).
			" WHERE id = ".$ilDB->quote($this->getId());
		$ilDB->query($query);

		// check whether start page exists
		include_once("./Modules/Wiki/classes/class.ilWikiPage.php");
		if (!ilWikiPage::exists($this->getId(), $this->getStartPage()))
		{
			$start_page = new ilWikiPage();
			$start_page->setWikiId($this->getId());
			$start_page->setTitle($this->getStartPage());
			$start_page->create();
		}

		return true;
	}
	
	/**
	* Read wiki data
	*/
	function read()
	{
		global $ilDB;
		
		parent::read();
		
		$query = "SELECT * FROM il_wiki_data WHERE id = ".
			$ilDB->quote($this->getId());
		$set = $ilDB->query($query);
		$rec = $set->fetchRow(DB_FETCHMODE_ASSOC);

		$this->setOnline($rec["online"]);
		$this->setStartPage($rec["startpage"]);
		$this->setShortTitle($rec["short"]);

	}


	/**
	* delete object and all related data	
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{
		global $ilDB;
		
		// always call parent delete function first!!
		if (!parent::delete())
		{
			return false;
		}
				
		// delete record of table il_wiki_data
		$query = "DELETE FROM il_wiki_data".
			" WHERE id = ".$ilDB->quote($this->getId());
		$ilDB->query($query);
		
		include_once("./Modules/Wiki/classes/class.ilWikiPage.php");
		ilWikiPage::deleteAllPagesOfWiki($this->getId());
		
		return true;
	}

	/**
	* init default roles settings
	* 
	* If your module does not require any default roles, delete this method 
	* (For an example how this method is used, look at ilObjForum)
	* 
	* @access	public
	* @return	array	object IDs of created local roles.
	*/
	function initDefaultRoles()
	{
		global $rbacadmin;
		
		// create a local role folder
		//$rfoldObj = $this->createRoleFolder("Local roles","Role Folder of forum obj_no.".$this->getId());

		// create moderator role and assign role to rolefolder...
		//$roleObj = $rfoldObj->createRole("Moderator","Moderator of forum obj_no.".$this->getId());
		//$roles[] = $roleObj->getId();

		//unset($rfoldObj);
		//unset($roleObj);

		return $roles ? $roles : array();
	}

	/**
	* notifys an object about an event occured
	* Based on the event happend, each object may decide how it reacts.
	*
	* If you are not required to handle any events related to your module, just delete this method.
	* (For an example how this method is used, look at ilObjGroup)
	*
	* @access	public
	* @param	string	event
	* @param	integer	reference id of object where the event occured
	* @param	array	passes optional parameters if required
	* @return	boolean
	*/
	function notify($a_event,$a_ref_id,$a_parent_non_rbac_id,$a_node_id,$a_params = 0)
	{
		global $tree;
		
		switch ($a_event)
		{
			case "link":
				
				//var_dump("<pre>",$a_params,"</pre>");
				//echo "Module name ".$this->getRefId()." triggered by link event. Objects linked into target object ref_id: ".$a_ref_id;
				//exit;
				break;
			
			case "cut":
				
				//echo "Module name ".$this->getRefId()." triggered by cut event. Objects are removed from target object ref_id: ".$a_ref_id;
				//exit;
				break;
				
			case "copy":
			
				//var_dump("<pre>",$a_params,"</pre>");
				//echo "Module name ".$this->getRefId()." triggered by copy event. Objects are copied into target object ref_id: ".$a_ref_id;
				//exit;
				break;

			case "paste":
				
				//echo "Module name ".$this->getRefId()." triggered by paste (cut) event. Objects are pasted into target object ref_id: ".$a_ref_id;
				//exit;
				break;
			
			case "new":
				
				//echo "Module name ".$this->getRefId()." triggered by paste (new) event. Objects are applied to target object ref_id: ".$a_ref_id;
				//exit;
				break;
		}

		// At the beginning of the recursive process it avoids second call of the notify function with the same parameter
		if ($a_node_id==$_GET["ref_id"])
		{
			$parent_obj =& $this->ilias->obj_factory->getInstanceByRefId($a_node_id);
			$parent_type = $parent_obj->getType();
			if($parent_type == $this->getType())
			{
				$a_node_id = (int) $tree->getParentId($a_node_id);
			}
		}
		
		parent::notify($a_event,$a_ref_id,$a_parent_non_rbac_id,$a_node_id,$a_params);
	}
	
} // END class.ilObjWiki
?>
