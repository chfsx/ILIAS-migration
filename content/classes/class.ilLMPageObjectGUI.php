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
require_once ("content/classes/class.ilEditClipboardGUI.php");
require_once ("content/classes/class.ilInternalLinkGUI.php");

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
	* get all gui classes that are called from this one (see class ilCtrl)
	*
	* @param	array		array of gui classes that are called
	*/
	function _forwards()
	{
		return (array("ilPageObjectGUI", "ilInternalLinkGUI", "ilEditClipboardGUI"));
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

	/**
	* execute command
	*/
	function &executeCommand()
	{
//echo "<br>:cmd:".$this->ctrl->getCmd().":cmdClass:".$this->ctrl->getCmdClass().":";
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			case "ilpageobjectgui":
				$this->ctrl->setReturn($this, "view");
				//require_once("content/classes/class.ilContObjLocatorGUI.php");
				//$contObjLocator =& new ilContObjLocatorGUI($this->content_object->getTree());
				//$contObjLocator->setObject($this->obj);
				//$contObjLocator->setContentObject($this->content_object);
				$page_object =& $this->obj->getPageObject();
				$page_object->buildDom();
				$int_links = $page_object->getInternalLinks();
				$link_xml = $this->getLinkXML($int_links);
				$page_gui =& new ilPageObjectGUI($page_object);
				$page_gui->setTemplateTargetVar("ADM_CONTENT");
				$page_gui->setLinkXML($link_xml);
				$page_gui->setFileDownloadLink("lm_presentation.php?cmd=downloadFile".
					"&amp;ref_id=".$this->content_object->getRefId());
				$page_gui->setLinkParams("ref_id=".$this->content_object->getRefId());
				$page_gui->setSourcecodeDownloadScript("lm_presentation.php?ref_id=".$this->content_object->getRefId());
				$page_gui->setPresentationTitle(ilLMPageObject::_getPresentationTitle($this->obj->getId(), $this->content_object->getPageHeader()));
				$page_gui->setLocator($contObjLocator);
				$page_gui->setHeader($this->lng->txt("page").": ".$this->obj->getTitle());
				$ret =& $page_gui->executeCommand();
				break;

			case "ilinternallinkgui":
				$link_gui = new ilInternalLinkGUI("StructureObject", $this->content_object->getRefId());
				$link_gui->setMode("normal");
				$link_gui->setSetLinkTargetScript(
					$this->ctrl->getLinkTarget($this, "setInternalLink"));
				//$link_gui->filterLinkType("Media");
				$ret =& $link_gui->executeCommand();
				break;

			case "ileditclipboardgui":
				$this->ctrl->setReturn($this, "view");
				$clip_gui = new ilEditClipboardGUI();
				$ret =& $clip_gui->executeCommand();
				break;

			default:
				$ret =& $this->$cmd();
				break;
		}
	}


	/*
	* display content of page (edit view)
	*/
	function view()
	{
//echo "<br>umschuss";
		$this->setTabs();
		$this->ctrl->setCmdClass("ilpageobjectgui");
		$this->ctrl->setCmd("view");
		$this->executeCommand();
	}

	/*
	* display content of page (edit view)
	*/
	function preview()
	{
		$this->setTabs();
		$this->ctrl->setCmdClass("ilpageobjectgui");
		$this->ctrl->setCmd("preview");
		$this->executeCommand();
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
		$this->obj->setType("pg");
		$this->obj->setTitle(ilUtil::stripSlashes($_POST["Fobject"]["title"]));
		$this->obj->setDescription(ilUtil::stripSlashes($_POST["Fobject"]["desc"]));
		$this->obj->setLMId($this->content_object->getId());
		$this->obj->create();

		// obj_id is empty, if page is created from "all pages" screen
		// -> a free page is created (not in the tree)
		if ($_GET["obj_id"] != 0)
		{
			$this->putInTree();

			// check the tree
			$this->checkTree();

			ilUtil::redirect($this->ctrl->getLinkTargetByClass("ilStructureObjectGUI",
				"view", "", true));
		}
	}

	/**
	* cancel
	*/
	function cancel()
	{
		sendInfo($this->lng->txt("msg_cancel"), true);
		if ($_GET["obj_id"] != 0)
		{
			ilUtil::redirect($this->ctrl->getLinkTargetByClass("ilStructureObjectGUI",
				"view", "", true));
		}
		//$this->ctrl->returnToParent($this);
	}

	/**
	* get link targets
	*/
	function getLinkXML($a_int_links)
	{
		if ($a_layoutframes == "")
		{
			$a_layoutframes = array();
		}
		$link_info = "<IntLinkInfos>";
		foreach ($a_int_links as $int_link)
		{
			$target = $int_link["Target"];
			if (substr($target, 0, 4) == "il__")
			{
				$target_arr = explode("_", $target);
				$target_id = $target_arr[count($target_arr) - 1];
				$type = $int_link["Type"];
				$targetframe = ($int_link["TargetFrame"] != "")
					? $int_link["TargetFrame"]
					: "None";

				switch($type)
				{
					case "PageObject":
					case "StructureObject":
						$lm_id = ilLMObject::_lookupContObjID($target_id);
						$cont_obj =& $this->content_object;
						if ($lm_id == $cont_obj->getId())
						{
							if ($type == "PageObject")
							{
								$this->ctrl->setParameter($this, "obj_id", $target_id);
								$href = $this->ctrl->getLinkTargetByClass(get_class($this), "view", "", true);
							}
							else
							{
								$this->ctrl->setParameterByClass("ilstructureobjectgui", "obj_id", $target_id);
								$href = $this->ctrl->getLinkTargetByClass("ilstructureobjectgui", "view", "", true);
							}
							$href = str_replace("&", "&amp;", $href);
							$this->ctrl->setParameter($this, "obj_id", $_GET["obj_id"]);
						}
						else
						{
							if ($type == "PageObject")
							{
								$href = "../goto.php?target=pg_".$target_id;
							}
							else
							{
								$href = "../goto.php?target=st_".$target_id;
							}
							$ltarget = "ilContObj".$lm_id;
						}
						break;

					case "GlossaryItem":
						$ltarget = $nframe = "_new";
						$href = "lm_presentation.php?obj_type=$type&amp;cmd=glossary&amp;ref_id=".$_GET["ref_id"].
							"&amp;obj_id=".$target_id."&amp;frame=$nframe";
						break;

					case "MediaObject":
						$ltarget = $nframe = "_new";
						$href = "lm_presentation.php?obj_type=$type&amp;cmd=media&amp;ref_id=".$_GET["ref_id"].
							"&amp;mob_id=".$target_id."&amp;frame=$nframe";
						break;
				}
				$link_info.="<IntLinkInfo Target=\"$target\" Type=\"$type\" ".
					"TargetFrame=\"$targetframe\" LinkHref=\"$href\" LinkTarget=\"$ltarget\" />";
			}
		}
		$link_info.= "</IntLinkInfos>";

		return $link_info;
	}


	/**
	* output tabs
	*/
	function setTabs()
	{
		// catch feedback message
		include_once("classes/class.ilTabsGUI.php");
		$tabs_gui =& new ilTabsGUI();
		$this->getTabs($tabs_gui);
		$this->tpl->setVariable("TABS", $tabs_gui->getHTML());
		$this->tpl->setVariable("HEADER",
			$this->lng->txt($this->obj->getType()).": ".$this->obj->getTitle());
	}

	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		// back to upper context
		$tabs_gui->addTarget("edit", $this->ctrl->getLinkTarget($this, "view")
			, "view", get_class($this));

		$tabs_gui->addTarget("cont_preview", $this->ctrl->getLinkTarget($this, "preview")
			, "preview", get_class($this));

		$tabs_gui->addTarget("meta_data", $this->ctrl->getLinkTarget($this, "editMeta")
			, "editMeta", get_class($this));

		$tabs_gui->addTarget("clipboard", $this->ctrl->getLinkTargetByClass("ilEditClipboardGUI", "view")
			, "view", "ilEditClipboardGUI");

	}


}
?>
