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

include_once("Services/Table/classes/class.ilTable2GUI.php");

/**
* TableGUI class for table NewsForContext
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ingroup ServicesNews
*/
class ilMediaCastTableGUI extends ilTable2GUI
{

	function ilMediaCastTableGUI($a_parent_obj, $a_parent_cmd = "")
	{
		global $ilCtrl, $lng;
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
		
		$this->addColumn("", "f", "1");
		$this->addColumn($lng->txt("mcst_entry"), "", "33%");
		$this->addColumn("", "", "33%");
		$this->addColumn("", "", "34%");
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
		$this->setRowTemplate("tpl.table_media_cast_row.html",
			"Modules/MediaCast");
		$this->setDefaultOrderField("creation_date");
		$this->setDefaultOrderDirection("desc");

	}
	
	/**
	* Standard Version of Fill Row. Most likely to
	* be overwritten by derived class.
	*/
	protected function fillRow($a_set)
	{
		global $lng, $ilCtrl, $ilAccess;
		
		include_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
		
		// edit link
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
			$this->tpl->setCurrentBlock("edit");
			$ilCtrl->setParameterByClass("ilobjmediacastgui", "item_id", $a_set["id"]);
			$this->tpl->setVariable("TXT_EDIT", $lng->txt("edit"));
			$this->tpl->setVariable("CMD_EDIT",
				$ilCtrl->getLinkTargetByClass("ilobjmediacastgui", "editCastItem"));
			$this->tpl->setVariable("TXT_DET_PLAYTIME", $lng->txt("mcst_det_playtime"));
			$this->tpl->setVariable("CMD_DET_PLAYTIME",
				$ilCtrl->getLinkTargetByClass("ilobjmediacastgui", "determinePlaytime"));
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setVariable("TXT_DOWNLOAD", $lng->txt("download"));
		$this->tpl->setVariable("CMD_DOWNLOAD",
			$ilCtrl->getLinkTargetByClass("ilobjmediacastgui", "downloadItem"));

		$ilCtrl->setParameterByClass("ilobjmediacastgui", "item_id", "");

		if (ilObject::_exists($a_set["mob_id"]))
		{
			if ($a_set["update_date"] != "")
			{
				$this->tpl->setCurrentBlock("last_update");
				$this->tpl->setVariable("TXT_LAST_UPDATE",
					$lng->txt("last_update"));
				$this->tpl->setVariable("VAL_LAST_UPDATE",
					$a_set["update_date"]);
				$this->tpl->parseCurrentBlock();
			}
			
			$mob = new ilObjMediaObject($a_set["mob_id"]);
			$med = $mob->getMediaItem("Standard");
			
			$this->tpl->setVariable("VAL_TITLE",
				$a_set["title"]);
			$this->tpl->setVariable("VAL_DESCRIPTION",
				$a_set["description"]);
			$this->tpl->setVariable("TXT_FILENAME",
				$lng->txt("filename"));
			$this->tpl->setVariable("VAL_FILENAME",
				$mob->getTitle());
			$this->tpl->setVariable("TXT_CREATED",
				$lng->txt("created"));
			$this->tpl->setVariable("VAL_CREATED",
				$a_set["creation_date"]);
			$this->tpl->setVariable("TXT_DURATION",
				$lng->txt("mcst_play_time"));
			$this->tpl->setVariable("VAL_DURATION",
				$a_set["length"]);
				
			include_once("./Services/MediaObjects/classes/class.ilMediaPlayerGUI.php");
			$mpl = new ilMediaPlayerGUI();

			$mpl->setFile(ilObjMediaObject::_getDirectory($mob->getId())."/".
				$med->getLocation());
			$this->tpl->setVariable("PLAYER",
				$mpl->getMp3PlayerHtml());
			$this->tpl->setVariable("VAL_ID", $a_set["id"]);
		}
		
	}

}
?>
