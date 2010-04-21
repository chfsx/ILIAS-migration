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

require_once("./Modules/Scorm2004/classes/class.ilSCORM2004NodeGUI.php");
require_once("./Modules/Scorm2004/classes/class.ilSCORM2004PageNode.php");

/**
* Class ilSCORM2004PageNodeGUI
*
* User Interface for Scorm 2004 Page Nodes
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilSCORM2004PageNodeGUI: ilSCORM2004PageGUI
*
* @ingroup ModulesScorm2004
*/
class ilSCORM2004PageNodeGUI extends ilSCORM2004NodeGUI
{

	/**
	* Constructor
	* @access	public
	*/
	function ilSCORM2004PageNodeGUI($a_slm_obj, $a_node_id = 0)
	{
		global $ilCtrl;
		
		$ilCtrl->saveParameter($this, "obj_id");
		
		parent::ilSCORM2004NodeGUI($a_slm_obj, $a_node_id);
	}

	/**
	* Get Node Type
	*/
	function getType()
	{
		return "page";
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilCtrl, $tpl;
		
		$next_class = $ilCtrl->getNextClass($this);
		$cmd = $ilCtrl->getCmd();

		switch($next_class)
		{
				
			case "ilscorm2004pagegui":
				$tpl->getStandardTemplate();
				$this->setLocator();
				// Determine whether the view of a learning resource should
				// be shown in the frameset of ilias, or in a separate window.
				//$showViewInFrameset = $this->ilias->ini->readVariable("layout","view_target") == "frame";
				$showViewInFrameset = true;

				$ilCtrl->setReturn($this, "edit");
				include_once("./Modules/Scorm2004/classes/class.ilSCORM2004PageGUI.php");
				$page_gui =& new ilSCORM2004PageGUI($this->slm_object->getType(),
					$this->node_object->getId(), 0,
					$this->getParentGUI()->object->getId(),
					$this->slm_object->getAssignedGlossary());
				$page_gui->setEditPreview(true);
				$page_gui->setPresentationTitle($this->node_object->getTitle());
				include_once("./Services/Style/classes/class.ilObjStyleSheet.php");
				$page_gui->setStyleId(ilObjStyleSheet::getEffectiveContentStyleId(
					$this->slm_object->getStyleSheetId(), "sahs"));

				//$page_gui->activateMetaDataEditor($this->content_object->getID(),
				//	$this->obj->getId(), $this->obj->getType(),
				//	$this->obj, "MDUpdateListener");
				
				$ilCtrl->setParameterByClass("ilobjscorm2004learningmodulegui",
					"active_node", $_GET["obj_id"]);
				$page_gui->setExplorerUpdater("tree", "tree_div",
					$ilCtrl->getLinkTargetByClass("ilobjscorm2004learningmodulegui",
						"showTree", "", true));
				$ilCtrl->setParameterByClass("ilobjscorm2004learningmodulegui",
					"active_node", "");

				// set page view link
				$view_frame = ilFrameTargetInfo::_getFrame("MainContent");
				//$page_gui->setViewPageLink(ILIAS_HTTP_PATH."/goto.php?target=pg_".$this->obj->getId().
				//	"_".$_GET["ref_id"],
				//	$view_frame);

				//$page_gui->setTemplateTargetVar("ADM_CONTENT");
				//$page_gui->setLinkXML($link_xml);
				//$page_gui->enableChangeComments($this->content_object->isActiveHistoryUserComments());
				//$page_gui->setFileDownloadLink("ilias.php?cmd=downloadFile&ref_id=".$_GET["ref_id"]."&baseClass=ilLMPresentationGUI");
				//$page_gui->setFullscreenLink("ilias.php?cmd=fullscreen&ref_id=".$_GET["ref_id"]."&baseClass=ilLMPresentationGUI");
				$page_gui->setLinkParams("ref_id=".$this->slm_object->getRefId());
				//$page_gui->setSourcecodeDownloadScript("ilias.php?ref_id=".$_GET["ref_id"]."&baseClass=ilLMPresentationGUI");
				/*$page_gui->setPresentationTitle(
					ilLMPageObject::_getPresentationTitle($this->obj->getId(),
					$this->content_object->getPageHeader(), $this->content_object->isActiveNumbering()));*/
				//$page_gui->setLocator($contObjLocator);
				//$page_gui->setHeader($this->lng->txt("page").": ".$this->obj->getTitle());
				
				//$page_gui->setEnabledActivation(true);
				//$page_gui->setActivationListener($this, "activatePage");
				//$page_gui->setActivated($this->obj->getActive());
				
				$tpl->setTitleIcon(ilUtil::getImagePath("icon_pg_b.gif"));
				//$tpl->setTitle($this->lng->txt("page").": ".$this->obj->getTitle());
				
				$page_gui->activateMetaDataEditor($this->slm_object->getID(),
					$this->node_object->getId(), $this->node_object->getType(),
					$this->node_object,'MDUpdateListener');

				
				$ret = $ilCtrl->forwardCommand($page_gui);
				$this->setTabs();
				$tpl->setContent($ret);
				break;

			default:
				$ret =& $this->$cmd();
				break;
		}

		return $ret;
	}

	/**
	* Edit -> switch to ilscorm2004pagegui
	*/
	function edit()
	{
		global $ilCtrl;
		
		$ilCtrl->setCmdClass("ilscorm2004pagegui");
		$ilCtrl->setCmd("edit");
		$this->executeCommand();
	}
	
	/**
	* output tabs
	*/
	function setTabs()
	{
		global $ilTabs, $ilCtrl, $tpl, $lng;

		// metadata
/*		$ilTabs->addTarget("meta_data",
			 $ilCtrl->getLinkTargetByClass("ilmdeditorgui",''),
			 "", "ilmdeditorgui");*/
			 
		$tpl->setTitleIcon(ilUtil::getImagePath("icon_pg_b.gif"));
		$tpl->setTitle(
			$lng->txt("sahs_page").": ".$this->node_object->getTitle());
	}

}
?>
