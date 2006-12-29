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

include_once("Services/Block/classes/class.ilBlockGUI.php");

/**
* BlockGUI class for Personal Desktop Notes block
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*/
class ilPDNotesBlockGUI extends ilBlockGUI
{
	
	/**
	* Constructor
	*/
	function ilPDNotesBlockGUI($a_parent_class, $a_parent_cmd = "")
	{
		global $ilCtrl, $lng, $ilUser;
		
		parent::ilBlockGUI($a_parent_class, $a_parent_cmd);
		
		$this->setLimit(5);
		$this->setImage(ilUtil::getImagePath("icon_note_s.gif"));
		$this->setTitle($lng->txt("notes"));
		$this->setBlockIdentification("pdnote", $ilUser->getId());
		$this->setPrefix("pdnotes");
		$this->setAvailableDetailLevels(3);
	}
	
	function getHTML()
	{
		if ($this->getCurrentDetailLevel() == 0)
		{
			return "";
		}
		else
		{
			return parent::getHTML();
		}
	}
	
	/**
	* Fill data section
	*/
	function fillDataSection()
	{
		global $ilUser;
		
		include_once("Services/Notes/classes/class.ilNote.php");
		$this->notes = ilNote::_getLastNotesOfUser();

		if ($this->getCurrentDetailLevel() > 1 && count($this->notes) > 0)
		{
			$this->setRowTemplate("tpl.pd_notes_overview.html", "Services/Notes");
			$this->getListRowData();
			//$this->setColSpan(2);
			parent::fillDataSection();
		}
		else
		{
			$this->setEnableNumInfo(false);
			$this->setDataSection($this->getOverview());
		}
	}
	

	/**
	* Get list data.
	*/
	function getListRowData()
	{
		global $ilUser, $lng, $ilCtrl;

		$data = array();
		
		foreach($this->notes as $note)
		{
			switch ($note->getLabel())
			{
				case IL_NOTE_UNLABELED:
					$img = ilUtil::getImagePath("note_unlabeled.gif");
					$alt = $lng->txt("note");
					break;
					
				case IL_NOTE_IMPORTANT:
					$img = ilUtil::getImagePath("note_important.gif");
					$alt = $lng->txt("note").", ".$lng->txt("important");
					break;
					
				case IL_NOTE_QUESTION:
					$img = ilUtil::getImagePath("note_question.gif");
					$alt = $lng->txt("note").", ".$lng->txt("question");
					break;
					
				case IL_NOTE_PRO:
					$img = ilUtil::getImagePath("note_pro.gif");
					$alt = $lng->txt("note").", ".$lng->txt("pro");
					break;
					
				case IL_NOTE_CONTRA:
					$img = ilUtil::getImagePath("note_contra.gif");
					$alt = $lng->txt("note").", ".$lng->txt("contra");
					break;
			}

			// details
			$target = $note->getObject();

			$data[] = array(
				"subject" => $note->getSubject(),
				"img" => $img,
				"alt" => $alt,
				"text" => ilUtil::shortenText($note->getText(), 150, true, true),
				"date" => substr($note->getCreationDate(),0,10),
				"id" => $note->getId(),
				"obj_type" => $target["obj_type"],
				"obj_id" => $target["obj_id"],
				"rep_obj_id" => $target["rep_obj_id"]);
		}
		
		$this->setData($data);
	}
	
	/**
	* get flat bookmark list for personal desktop
	*/
	function fillRow($a_set)
	{
		global $ilUser, $ilCtrl, $lng;
		
		include_once("Services/Notes/classes/class.ilNoteGUI.php");
		if (!is_object($this->note_gui))
		{
			$this->note_gui = new ilNoteGUI(0,0,"");
			$this->note_gui->enableTargets();
		}

		if ($this->getCurrentDetailLevel() > 2)
		{
			$this->tpl->setVariable("VAL_SUBJECT", "<b>".$a_set["subject"]."</b>");
		}
		else
		{
			$this->tpl->setVariable("VAL_SUBJECT", $a_set["subject"]);
		}
		$this->tpl->setVariable("IMG_NOTE", $a_set["img"]);
		$this->tpl->setVariable("ALT_NOTE", $a_set["alt"]);
		
		// details
		if ($this->getCurrentDetailLevel() > 2)
		{
			$this->tpl->setCurrentBlock("details");
			$this->tpl->setVariable("NOTE_TEXT", $a_set["text"]);
			$this->tpl->setVariable("VAL_DATE", $a_set["date"]);
			$this->tpl->parseCurrentBlock();
				
			// target objects
			$this->note_gui->showTargets($this->tpl, $a_set["rep_obj_id"], $a_set["id"],
				$a_set["obj_type"], $a_set["obj_id"]);
		}

		// edit button
		$this->tpl->setCurrentBlock("edit_note");
		$this->tpl->setVariable("TXT_EDIT_NOTE", $lng->txt("edit"));
		$ilCtrl->setParameterByClass("ilnotegui", "rel_obj", $a_set["rep_obj_id"]);
		$ilCtrl->setParameterByClass("ilnotegui", "note_id", $a_set["id"]);
		$ilCtrl->setParameterByClass("ilnotegui", "note_type", IL_NOTE_PRIVATE);
		$this->tpl->setVariable("LINK_EDIT_NOTE",
			$ilCtrl->getLinkTargetByClass(array("ilpdnotesgui", "ilnotegui"), "editNoteForm")
			."#note_edit");
		$this->tpl->parseCurrentBlock();
	}

	/**
	* Get overview.
	*/
	function getOverview()
	{
		global $ilUser, $lng, $ilCtrl;
				
		return '<div class="small">'.((int) count($this->notes))." ".$lng->txt("notes")."</div>";
	}

}

?>
