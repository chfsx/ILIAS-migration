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

require_once("./content/classes/class.ilLMObjectGUI.php");
require_once("./content/classes/class.ilLMPageObject.php");
require_once("./content/classes/Pages/class.ilPageObjectGUI.php");
require_once("./content/classes/Pages/class.ilPageEditorGUI.php");

/**
* Class ilLMPageObjectGUI
*
* User Interface for Learning Module Page Objects Editing
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilLMPageObjectGUI extends ilLMObjectGUI
{
	var $obj;

	/**
	* Constructor
	*
	* @param	object		$a_content_obj		content object (lm | dbk)
	* @access	public
	*/
	function ilLMPageObjectGUI(&$a_content_obj)
	{
		global $ilias, $tpl, $lng;

		parent::ilLMObjectGUI($a_content_obj);

	}


	/**
	* set content object dependent page object (co page)
	*/
	function setLMPageObject(&$a_pg_obj)
	{
		$this->obj =& $a_pg_obj;
		$this->obj->setLMId($this->content_object->getId());
		$this->actions = $this->objDefinition->getActions($this->obj->getType());
	}


	/*
	* display content of page (edit view)
	*/
	function view()
	{
		$page_object_gui =& new ilPageObjectGUI($this->obj->getPageObject());
		//$page_object_gui->setPresentationTitle($this->obj->getPresentationTitle($this->content_object->getPageHeader()));
		$page_object_gui->setPresentationTitle(ilLMPageObject::_getPresentationTitle($this->obj->getId(), $this->content_object->getPageHeader()));
		$page_object_gui->setTargetScript("lm_edit.php?ref_id=".
			$this->content_object->getRefId()."&obj_id=".$this->obj->getId()."&mode=page_edit");
		$page_object_gui->setLinkParams("ref_id=".$this->content_object->getRefId());
		$page_object_gui->setTemplateTargetVar("ADM_CONTENT");
		$page_object_gui->view();

	}

	/**
	* show page editor
	*/
	function showPageEditor()
	{
		$this->forwardToPageObjGUI("showPageEditor");
	}

	/**
	* show internal link help
	*/
	function showLinkHelp()
	{
		$this->forwardToPageObjGUI("showLinkHelp");
	}

	/**
	* change internal link type
	*/
	function changeLinkType()
	{
		$this->forwardToPageObjGUI("changeLinkType");
	}

	function closeLinkHelp()
	{
		;
	}

	/**
	* reset internal link list
	*/
	function resetLinkList()
	{
		$this->forwardToPageObjGUI("resetLinkList");
	}

	/**
	* reset internal link list
	*/
	function changeTargetObject()
	{
		$this->forwardToPageObjGUI("changeTargetObject");
	}

	function clipboard()
	{
		$this->forwardToPageObjGUI("clipboard");
	}

	function clipboardDeletion()
	{
		$this->forwardToPageObjGUI("clipboardDeletion");
	}

	function createMediaInClipboard()
	{
		$this->forwardToPageObjGUI("createMediaInClipboard");
	}

	function saveMediaInClipboard()
	{
		$this->forwardToPageObjGUI("saveMediaInClipboard");
	}

	function newMediaObject()
	{
		$this->forwardToPageObjGUI("createMediaInClipboard");
	}

	function forwardToPageObjGUI($cmd)
	{
		require_once("content/classes/class.ilContObjLocatorGUI.php");
		$contObjLocator =& new ilContObjLocatorGUI($this->content_object->getTree());
		$contObjLocator->setObject($this->obj);
		$contObjLocator->setContentObject($this->content_object);

		require_once ("content/classes/Pages/class.ilPageObjectGUI.php");
		$page_gui =& new ilPageObjectGUI($this->obj->getPageObject());
		$page_gui->setLocator($contObjLocator);
		$page_gui->setHeader($this->lng->txt("page").": ".$this->obj->getTitle());
		$page_gui->setTargetScript("lm_edit.php?ref_id=".
			$this->content_object->getRefId()."&obj_id=".$this->obj->getId()."&mode=page_edit");
		$page_gui->setReturnLocation("lm_edit.php?ref_id=".
			$this->content_object->getRefId()."&obj_id=".$this->obj->getId()."&cmd=view");
		$page_gui->$cmd();
	}

	function editMob()
	{
		$this->forwardToMediaObjGUI("edit");
	}

	function forwardToMediaObjGUI($cmd)
	{
		require_once("content/classes/class.ilContObjLocatorGUI.php");
		$contObjLocator =& new ilContObjLocatorGUI($this->content_object->getTree());
		$contObjLocator->setObject($this->obj);
		$contObjLocator->setContentObject($this->content_object);

		require_once ("content/classes/Pages/class.ilPCMediaObjectGUI.php");
		$media =& new ilMediaObject($_GET["mob_id"]);
		$media_gui =& new ilPCMediaObjectGUI($this->obj->getPageObject(), $media);
		//$page_gui->setLocator($contObjLocator);
		//$page_gui->setHeader($this->lng->txt("page").": ".$this->obj->getTitle());
		//$page_gui->setTargetScript("lm_edit.php?ref_id=".
		//	$this->content_object->getRefId()."&obj_id=".$this->obj->getId()."&mode=page_edit");
		//$page_gui->setReturnLocation("lm_edit.php?ref_id=".
		//	$this->content_object->getRefId()."&obj_id=".$this->obj->getId()."&cmd=view");
		$media_gui->$cmd();
	}

	/*
	* preview
	*/
	function preview()
	{
		$page_object_gui =& new ilPageObjectGUI($this->obj->getPageObject());

		//$page_object_gui->setPresentationTitle($this->obj->getPresentationTitle($this->content_object->getPageHeader()));
		$page_object_gui->setPresentationTitle(ilLMPageObject::_getPresentationTitle($this->obj->getId(), $this->content_object->getPageHeader()));
		$page_object_gui->setTargetScript("lm_edit.php?ref_id=".
			$this->content_object->getRefId()."&obj_id=".$this->obj->getId()."&mode=page_edit");
		$page_object_gui->setLinkParams("ref_id=".$this->content_object->getRefId());
		$page_object_gui->setTemplateTargetVar("ADM_CONTENT");
		$page_object_gui->preview();
	}

	/**
	* output a cell in object list
	*/
	function add_cell($val, $link = "")
	{
		if(!empty($link))
		{
			$this->tpl->setCurrentBlock("begin_link");
			$this->tpl->setVariable("LINK_TARGET", $link);
			$this->tpl->parseCurrentBlock();
			$this->tpl->touchBlock("end_link");
		}

		$this->tpl->setCurrentBlock("text");
		$this->tpl->setVariable("TEXT_CONTENT", $val);
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("table_cell");
		$this->tpl->parseCurrentBlock();
	}


	/**
	* save co page object
	*/
	function save()
	{
		// create new object
		$meta_data =& new ilMetaData($_GET["new_type"], $this->content_object->getId());

		$this->obj =& new ilLMPageObject($this->content_object);
		$this->obj->assignMetaData($meta_data);
		$this->obj->setType($_GET["new_type"]);
		$this->obj->setTitle($_POST["Fobject"]["title"]);
		$this->obj->setDescription($_POST["Fobject"]["desc"]);
		$this->obj->setLMId($this->content_object->getId());
		$this->obj->create();

		// obj_id is empty, if page is created from "all pages" screen
		// -> a free page is created (not in the tree)
		if (empty($_GET["obj_id"]))
		{
			ilUtil::redirect("lm_edit.php?cmd=pages&ref_id=".$this->content_object->getRefId());
		}
		else
		{
			$this->putInTree();

			// check the tree
			$this->checkTree();

			ilUtil::redirect("lm_edit.php?cmd=view&ref_id=".$this->content_object->getRefId()."&obj_id=".
				$_GET["obj_id"]);
		}
	}

}
?>
