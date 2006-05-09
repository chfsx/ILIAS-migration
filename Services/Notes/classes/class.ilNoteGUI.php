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

include_once ("Services/Notes/classes/class.ilNote.php");

/**
* Notes GUI class. An instance of this class handles all notes
* (and their lists) of an object.
*
* @author	Alex Killing <alex.killing@gmx.de>
* @version	$Id$
*/
class ilNoteGUI
{
	var $public_deletion_enabled = false;
	
	
	/**
	* constructor, specifies notes set
	*
	* @param	$a_rep_obj_id	int		object id of repository object (0 for personal desktop)
	* @param	$a_obj_id		int		subobject id (0 for repository items, user id for personal desktop)
	* @param	$a_obj_type		string	"pd" for personal desktop
	* @param	$a_include_subobjects	string		include all subobjects of rep object (e.g. pages)
	*/
	function ilNoteGUI($a_rep_obj_id, $a_obj_id, $a_obj_type, $a_include_subobjects = false)
	{
		global $ilCtrl, $lng;

		$this->rep_obj_id = $a_rep_obj_id;
		$this->obj_id = $a_obj_id;
		$this->obj_type = $a_obj_type;
		$this->inc_sub = $a_include_subobjects;
		
		$this->ctrl =& $ilCtrl;
		$this->lng =& $lng;
		
		$this->add_note_form = false;
		$this->edit_note_form = false;
		$this->private_enabled = false;
		$this->public_enabled = false;
		$this->enable_hiding = true;
		$this->targets_enabled = false;
		$this->multi_selection = false;
		$this->export_html = false;
		$this->print = false;
	}
	
	/**
	* execute command
	*/
	function &executeCommand()
	{
		$cmd = $this->ctrl->getCmd("getNotesHTML");
		$next_class = $this->ctrl->getNextClass($this);

		switch($next_class)
		{
			default:
				return $this->$cmd();
				break;
		}
	}
	
	/**
	* enable private notes
	*/
	function enablePrivateNotes($a_enable = true)
	{
		$this->private_enabled = $a_enable;
	}
	
	/**
	* enable public notes
	*/
	function enablePublicNotes($a_enable = true)
	{
		$this->public_enabled =  $a_enable;
	}

	/**
	* enable public notes
	*/
	function enablePublicNotesDeletion($a_enable = true)
	{
		$this->public_deletion_enabled =  $a_enable;
	}

	/**
	* enable hiding
	*/
	function enableHiding($a_enable = true)
	{
		$this->enable_hiding = $a_enable;
	}
	
	/**
	* enable target objects
	*/
	function enableTargets($a_enable = true)
	{
		$this->targets_enabled = $a_enable;
	}

	/**
	* enable multi selection (checkboxes and commands)
	*/
	function enableMultiSelection($a_enable = true)
	{
		$this->multi_selection = $a_enable;
	}

	/***
	* get note lists html code
	*/
	function getNotesHTML()
	{
		global $ilUser;

		$html = "";
		if ($this->private_enabled && ($ilUser->getId() != ANONYMOUS_USER_ID))
		{
			$html.= $this->getNoteListHTML(IL_NOTE_PRIVATE);
		}
		
		if ($this->public_enabled && (!$this->delete_note || $this->public_deletion_enabled))
		{
			$html.= $this->getNoteListHTML(IL_NOTE_PUBLIC);
		}
		
		return $html;
	}

	/**
	* get notes list as html code
	*/
	function getNoteListHTML($a_type = IL_NOTE_PRIVATE)
	{
		global $lng, $ilCtrl, $ilUser, $ilAccess, $tree, $objDefinition;

		$suffix = ($a_type == IL_NOTE_PRIVATE)
			? "private"
			: "public";
		
		if ($this->delete_note || $this->export_html || $this->print)
		{
			if ($_GET["note_id"] != "")
			{
				$filter = $_GET["note_id"];
			}
			else
			{
				$filter = $_POST["note"];
			}
		}
		
		$notes = ilNote::_getNotesOfObject($this->rep_obj_id, $this->obj_id,
			$this->obj_type, $a_type, $this->inc_sub, $filter,
			$ilUser->getPref("notes_pub_all"), $this->public_deletion_enabled);

		$tpl = new ilTemplate("tpl.notes_list.html", true, true, "Services/Notes");
		
		// show counter if notes are hidden
		$cnt_str = ($ilUser->getPref("notes_".$suffix) == "n"
			&& count($notes) > 0)
			? " (".count($notes).")"
			: "";
		
		if ($this->delete_note)
		{
			$tpl->setVariable("TXT_NOTES", $lng->txt("info_delete_sure"));
		}
		else if ($a_type == IL_NOTE_PRIVATE)
		{
			$tpl->setVariable("TXT_NOTES", $lng->txt("private_notes").$cnt_str);
			$ilCtrl->setParameterByClass("ilnotegui", "note_type", IL_NOTE_PRIVATE);
		}
		else
		{
			$tpl->setVariable("TXT_NOTES", $lng->txt("public_notes").$cnt_str);
			$ilCtrl->setParameterByClass("ilnotegui", "note_type", IL_NOTE_PUBLIC);
		}
		$tpl->setVariable("FORMACTION", $ilCtrl->getFormAction($this));
		
		if ($this->export_html || $this->print)
		{
			$tpl->touchBlock("print_style");
		}
		
		// show add new note button
		if (!$this->add_note_form && !$this->edit_note_form && !$this->delete_note &&
			!$this->export_html && !$this->print &&
			($ilUser->getId() != ANONYMOUS_USER_ID))
		{
			if (!$this->inc_sub)	// we cannot offer add button if aggregated notes
			{						// are displayed
				$tpl->setCurrentBlock("add_note_btn");
				$tpl->setVariable("TXT_ADD_NOTE", $lng->txt("add_note"));
				$tpl->setVariable("LINK_ADD_NOTE", $ilCtrl->getLinkTargetByClass("ilnotegui", "addNoteForm").
					"#note_edit");
				$tpl->parseCurrentBlock();
			}
		}
		
		// show show/hide button for note list
		if (count($notes) > 0 && $this->enable_hiding && !$this->delete_note
			&& !$this->export_html && !$this->print)
		{
			if ($ilUser->getPref("notes_".$suffix) == "n")
			{
				$tpl->setCurrentBlock("show_notes");
				$tpl->setVariable("LINK_SHOW_NOTES", $this->ctrl->getLinkTargetByClass("ilnotegui", "showNotes"));
				$tpl->setVariable("TXT_SHOW_NOTES", $lng->txt("show_".$suffix."_notes"));
				$tpl->parseCurrentBlock();
			}
			else
			{
				// never individually hide for anonymous users
				if (($ilUser->getId() != ANONYMOUS_USER_ID))
				{
					$tpl->setCurrentBlock("hide_notes");
					$tpl->setVariable("LINK_HIDE_NOTES", $this->ctrl->getLinkTargetByClass("ilnotegui", "hideNotes"));
					$tpl->setVariable("TXT_HIDE_NOTES", $lng->txt("hide_".$suffix."_notes"));
					$tpl->parseCurrentBlock();
					
					// show all public notes / my notes only switch
					if ($a_type == IL_NOTE_PUBLIC)
					{
						if ($ilUser->getPref("notes_pub_all") == "n")
						{
							$tpl->setCurrentBlock("all_pub_notes");
							$tpl->setVariable("LINK_ALL_PUB_NOTES", $this->ctrl->getLinkTargetByClass("ilnotegui", "showAllPublicNotes"));
							$tpl->setVariable("TXT_ALL_PUB_NOTES", $lng->txt("note_all_pub_notes"));
							$tpl->parseCurrentBlock();
						}
						else
						{
							$tpl->setCurrentBlock("my_pub_notes");
							$tpl->setVariable("LINK_MY_PUB_NOTES", $this->ctrl->getLinkTargetByClass("ilnotegui", "showMyPublicNotes"));
							$tpl->setVariable("TXT_MY_PUB_NOTES", $lng->txt("note_my_pub_notes"));
							$tpl->parseCurrentBlock();
						}
					}
				}
			}
		}
		
		// show add new note text area
		if ($this->add_note_form && $a_type == $_GET["note_type"])
		{
			$tpl->setCurrentBlock("edit_note");
			$tpl->setVariable("TXT_SUBJECT", $lng->txt("subject"));
			$tpl->setVariable("TXT_NOTE", $lng->txt("note"));
			$tpl->setVariable("NOTE_SUBJECT", "");
			$tpl->setVariable("SUB_NOTE", "sub_note");
			$tpl->setVariable("TA_NOTE", "note");
			$tpl->setVariable("NOTE_CONTENT", "");
			$tpl->setVariable("BTN_ADD_NOTE", "addNote");
			$tpl->setVariable("TXT_ADD_NOTE", $lng->txt("add"));
			$tpl->setVariable("BTN_CANCEL_ADD_NOTE", "cancelAddNote");
			$tpl->setVariable("TXT_CANCEL_ADD_NOTE", $lng->txt("cancel"));
			$tpl->setVariable("VAL_LABEL_NONE", IL_NOTE_UNLABELED);
			$tpl->setVariable("TXT_LABEL_NONE", $lng->txt("unlabeled"));
			$tpl->setVariable("VAL_LABEL_QUESTION", IL_NOTE_QUESTION);
			$tpl->setVariable("TXT_LABEL_QUESTION", $lng->txt("question"));
			$tpl->setVariable("VAL_LABEL_IMPORTANT", IL_NOTE_IMPORTANT);
			$tpl->setVariable("TXT_LABEL_IMPORTANT", $lng->txt("important"));
			$tpl->setVariable("VAL_LABEL_PRO", IL_NOTE_PRO);
			$tpl->setVariable("TXT_LABEL_PRO", $lng->txt("pro"));
			$tpl->setVariable("VAL_LABEL_CONTRA", IL_NOTE_CONTRA);
			$tpl->setVariable("TXT_LABEL_CONTRA", $lng->txt("contra"));
			$tpl->parseCurrentBlock();
			$tpl->setCurrentBlock("note_row");
			$tpl->parseCurrentBlock();
		}

		// list all notes
		if ($ilUser->getPref("notes_".$suffix) != "n" || !$this->enable_hiding)
		{
			foreach($notes as $note)
			{
				if ($this->edit_note_form && ($note->getId() == $_GET["note_id"])
					&& $a_type == $_GET["note_type"])
				{
					$tpl->setCurrentBlock("edit_note_form");
					$tpl->setVariable("TXT_SUBJECT", $lng->txt("subject"));
					$tpl->setVariable("TXT_NOTE", $lng->txt("note"));
					$tpl->setVariable("NOTE_SUBJECT",
						ilUtil::prepareFormOutput($note->getSubject()));
					$tpl->setVariable("SUB_NOTE", "sub_note");
					$tpl->setVariable("TA_NOTE", "note");
					$tpl->setVariable("NOTE_CONTENT",
						ilUtil::prepareFormOutput($note->getText()));
					$tpl->setVariable("BTN_ADD_NOTE", "updateNote");
					$tpl->setVariable("TXT_ADD_NOTE", $lng->txt("save"));
					$tpl->setVariable("BTN_CANCEL_ADD_NOTE", "cancelUpdateNote");
					$tpl->setVariable("TXT_CANCEL_ADD_NOTE", $lng->txt("cancel"));
					$tpl->setVariable("VAL_LABEL_NONE", IL_NOTE_UNLABELED);
					$tpl->setVariable("TXT_LABEL_NONE", $lng->txt("unlabeled"));
					$tpl->setVariable("VAL_LABEL_QUESTION", IL_NOTE_QUESTION);
					$tpl->setVariable("TXT_LABEL_QUESTION", $lng->txt("question"));
					$tpl->setVariable("VAL_LABEL_IMPORTANT", IL_NOTE_IMPORTANT);
					$tpl->setVariable("TXT_LABEL_IMPORTANT", $lng->txt("important"));
					$tpl->setVariable("VAL_LABEL_PRO", IL_NOTE_PRO);
					$tpl->setVariable("TXT_LABEL_PRO", $lng->txt("pro"));
					$tpl->setVariable("VAL_LABEL_CONTRA", IL_NOTE_CONTRA);
					$tpl->setVariable("TXT_LABEL_CONTRA", $lng->txt("contra"));
					$tpl->setVariable("VAL_NOTE_ID", $_GET["note_id"]);
					switch($note->getLabel())
					{
						case IL_NOTE_UNLABELED:
							$tpl->setVariable("SEL_NONE", 'selected="selected"');
							break;
							
						case IL_NOTE_IMPORTANT:
							$tpl->setVariable("SEL_IMPORTANT", 'selected="selected"');
							break;
							
						case IL_NOTE_QUESTION:
							$tpl->setVariable("SEL_QUESTION", 'selected="selected"');
							break;
							
						case IL_NOTE_PRO:
							$tpl->setVariable("SEL_PRO", 'selected="selected"');
							break;
							
						case IL_NOTE_CONTRA:
							$tpl->setVariable("SEL_CONTRA", 'selected="selected"');
							break;
					}
					$tpl->parseCurrentBlock();
				}
				else
				{
					$cnt_col = 2;
					
					// delete note stuff for all private notes
					if (($note->getAuthor() == $ilUser->getId() ||
						$this->public_deletion_enabled)
						&& ($ilUser->getId() != ANONYMOUS_USER_ID))
					{
						// only private notes can be deleted by the user
						// public notes can be deleted if flag set (outside permission checking)
						if (($a_type == IL_NOTE_PRIVATE || $this->public_deletion_enabled) 
							&& !$this->delete_note
							&& !$this->export_html && !$this->print)
						{
							$tpl->setCurrentBlock("delete_note");
							$tpl->setVariable("TXT_DELETE_NOTE", $lng->txt("delete"));
							$ilCtrl->setParameterByClass("ilnotegui", "note_id", $note->getId());
							$tpl->setVariable("LINK_DELETE_NOTE",
								$ilCtrl->getLinkTargetByClass("ilnotegui", "deleteNote")
								."#note_".$note->getId());
							$tpl->parseCurrentBlock();
						}
					}
					
					// edit note stuff for all private notes
					if ($note->getAuthor() == $ilUser->getId()
						&& ($ilUser->getId() != ANONYMOUS_USER_ID))
					{
						// checkboxes in multiselection mode
						if ($this->multi_selection && !$this->delete_note)
						{
							$tpl->setCurrentBlock("checkbox_col");
							$tpl->setVariable("CHK_NOTE", "note[]");
							$tpl->setVariable("CHK_NOTE_ID", $note->getId());
							$tpl->parseCurrentBlock();
							$cnt_col = 1;
						}

						if (!$this->delete_note && !$this->export_html && !$this->print)
						{
							$tpl->setCurrentBlock("edit_note");
							$tpl->setVariable("TXT_EDIT_NOTE", $lng->txt("edit"));
							$ilCtrl->setParameterByClass("ilnotegui", "note_id", $note->getId());
							$tpl->setVariable("LINK_EDIT_NOTE",
								$ilCtrl->getLinkTargetByClass("ilnotegui", "editNoteForm")
								."#note_edit");
							$tpl->parseCurrentBlock();
						}
					}
					
					$tpl->setVariable("CNT_COL", $cnt_col);
					
					// output author account
					if ($a_type == IL_NOTE_PUBLIC)
					{
						$tpl->setCurrentBlock("author");
						$tpl->setVariable("VAL_AUTHOR", ilObjUser::_lookupLogin($note->getAuthor()));
						$tpl->parseCurrentBlock();
					}
					
					// last edited
					if ($note->getUpdateDate() != "0000-00-00 00:00:00")
					{
						$tpl->setCurrentBlock("last_edit");
						$tpl->setVariable("TXT_LAST_EDIT", $lng->txt("last_edited_on"));
						$tpl->setVariable("DATE_LAST_EDIT", $note->getUpdateDate());
						$tpl->parseCurrentBlock();
					}
					
					// hidden note ids for deletion
					if ($this->delete_note)
					{
						$tpl->setCurrentBlock("delete_ids");
						$tpl->setVariable("HID_NOTE", "note[]");
						$tpl->setVariable("HID_NOTE_ID", $note->getId());
						$tpl->parseCurrentBlock();						
					}
					$target = $note->getObject();
					
					// target objects							
					$this->showTargets($tpl, $this->rep_obj_id, $note->getId(),
						$target["obj_type"], $target["obj_id"]);
					
					$rowclass = ($rowclass != "tblrow1")
						? "tblrow1"
						: "tblrow2";
					if (!$this->export_html && !$this->print)
					{
						$tpl->setCurrentBlock("note_img");
						switch ($note->getLabel())
						{
							case IL_NOTE_UNLABELED:
								$tpl->setVariable("IMG_NOTE", ilUtil::getImagePath("note_unlabeled.gif"));
								$tpl->setVariable("ALT_NOTE", $lng->txt("note"));
								break;
								
							case IL_NOTE_IMPORTANT:
								$tpl->setVariable("IMG_NOTE", ilUtil::getImagePath("note_important.gif"));
								$tpl->setVariable("ALT_NOTE", $lng->txt("note").", ".$lng->txt("important"));
								break;
								
							case IL_NOTE_QUESTION:
								$tpl->setVariable("IMG_NOTE", ilUtil::getImagePath("note_question.gif"));
								$tpl->setVariable("ALT_NOTE", $lng->txt("note").", ".$lng->txt("question"));
								break;
								
							case IL_NOTE_PRO:
								$tpl->setVariable("IMG_NOTE", ilUtil::getImagePath("note_pro.gif"));
								$tpl->setVariable("ALT_NOTE", $lng->txt("note").", ".$lng->txt("pro"));
								break;
								
							case IL_NOTE_CONTRA:
								$tpl->setVariable("IMG_NOTE", ilUtil::getImagePath("note_contra.gif"));
								$tpl->setVariable("ALT_NOTE", $lng->txt("note").", ".$lng->txt("contra"));
								break;
						}
						$tpl->parseCurrentBlock();
					}
					else
					{
						switch ($note->getLabel())
						{
							case IL_NOTE_UNLABELED:
								$tpl->setVariable("EXP_ICON", "[&nbsp;]");
								break;
								
							case IL_NOTE_IMPORTANT:
								$tpl->setVariable("EXP_ICON", "[!]");
								break;
								
							case IL_NOTE_QUESTION:
								$tpl->setVariable("EXP_ICON", "[?]");
								break;

							case IL_NOTE_PRO:
								$tpl->setVariable("EXP_ICON", "[+]");
								break;
								
							case IL_NOTE_CONTRA:
								$tpl->setVariable("EXP_ICON", "[-]");
								break;
						}
					}
					$tpl->setCurrentBlock("note");
					$tpl->setVariable("ROWCLASS", $rowclass);
					$tpl->setVariable("TXT_DATE", $lng->txt("date"));
					$tpl->setVariable("TXT_CREATED", $lng->txt("create_date"));
					$tpl->setVariable("VAL_DATE", $note->getCreationDate());
					$tpl->setVariable("NOTE_TEXT", nl2br($note->getText()));
					$tpl->setVariable("VAL_SUBJECT", $note->getSubject());
					$tpl->setVariable("NOTE_ID", $note->getId());
					$tpl->parseCurrentBlock();
				}
				$tpl->setCurrentBlock("note_row");
				$tpl->parseCurrentBlock();
			}
			
			// multiple items commands
			if ($this->multi_selection && !$this->delete_note)
			{
				$tpl->setCurrentBlock("multiple_commands");
				$tpl->setVariable("TXT_SELECT_ALL", $this->lng->txt("select_all"));
				$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
				$tpl->setVariable("ALT_ARROW", $this->lng->txt("actions"));
				$tpl->setVariable("TXT_DELETE_NOTES", $this->lng->txt("delete"));
				$tpl->setVariable("TXT_PRINT_NOTES", $this->lng->txt("print"));
				$tpl->setVariable("TXT_EXPORT_NOTES", $this->lng->txt("exp_html"));
				$tpl->parseCurrentBlock();
			}

			// delete / cancel row
			if ($this->delete_note)
			{
				$tpl->setCurrentBlock("delete_cancel");
				$tpl->setVariable("TXT_DEL_NOTES", $this->lng->txt("delete"));
				$tpl->setVariable("TXT_CANCEL_DEL_NOTES", $this->lng->txt("cancel"));
				$tpl->parseCurrentBlock();
			}
			
			// print
			if ($this->print)
			{
				$tpl->touchBlock("print_js");
				$tpl->setCurrentBlock("print_back");
				$tpl->setVariable("LINK_BACK", $this->ctrl->getLinkTarget($this, "showNotes"));
				$tpl->setVariable("TXT_BACK", $this->lng->txt("back"));
				$tpl->parseCurrentBlock();
			}
		}
		
		if ($this->delete_note && count($notes) == 0)
		{
			return "";
		}
		else
		{
			return $tpl->get();
		}
	}
	
	/**
	* show related objects as links
	*/
	function showTargets(&$tpl, $a_rep_obj_id, $a_note_id, $a_obj_type, $a_obj_id)
	{
		global $tree, $ilAccess, $objDefinition;

		if ($this->targets_enabled)
		{
			if ($a_rep_obj_id > 0)
			{
				// get all visible references of target object
				$ref_ids = ilObject::_getAllReferences($a_rep_obj_id);
				$vis_ref_ids = array();
				foreach($ref_ids as $ref_id)
				{
					if ($ilAccess->checkAccess("visible", "", $ref_id))
					{
						$vis_ref_ids[] = $ref_id;
					}
				}
			
				// output links to targets
				if (count($vis_ref_ids) > 0)
				{
					foreach($vis_ref_ids as $vis_ref_id)
					{
						$type = ilObject::_lookupType($vis_ref_id, true);
						
						if ($a_obj_type != "pg")
						{
							if (!is_object($this->item_list_gui[$type]))
							{
								$class = $objDefinition->getClassName($type);
								$location = $objDefinition->getLocation($type);
								$full_class = "ilObj".$class."ListGUI";
								include_once($location."/class.".$full_class.".php");
								$this->item_list_gui[$type] = new $full_class();
							}
							$title = ilObject::_lookupTitle($a_rep_obj_id);
							$this->item_list_gui[$type]->initItem($vis_ref_id, $a_rep_obj_id, $title);
							$link = $this->item_list_gui[$type]->getCommandLink("infoScreen");
							
							// workaround, because # anchor can't be passed through frameset
							$link = ilUtil::appendUrlParameterString($link, "anchor=note_".$a_note_id);
							
							$link = $this->item_list_gui[$type]->appendRepositoryFrameParameter($link)."#note_".$a_note_id;
						}
						else
						{
							$title = ilObject::_lookupTitle($a_rep_obj_id);
							$link = "goto.php?target=pg_".$a_obj_id."_".$vis_ref_id;
						}
						
						$par_id = $tree->getParentId($vis_ref_id);
						if ($this->export_html || $this->print)
						{
							$tpl->setCurrentBlock("exp_target_object");
						}
						else
						{
							$tpl->setCurrentBlock("target_object");
							$tpl->setVariable("LINK_TARGET", $link);
						}
						$tpl->setVariable("TXT_CONTAINER",
							ilObject::_lookupTitle(
							ilObject::_lookupObjId($par_id)));
						$tpl->setVariable("TXT_TARGET",
							$title);
						$tpl->parseCurrentBlock();
					}
					$tpl->touchBlock("target_objects");
				}
			}
		}
	}

	
	/**
	* notes overview on personal desktop
	* shows 10 recent notes
	*/
	function getPDOverviewNoteListHTML()
	{
		global $lng, $ilUser, $ilCtrl;
		
		//$notes = ilNote::_getNotesOfObject($this->rep_obj_id, $this->obj_id, $this->obj_type, $a_type);
		
		$tpl = new ilTemplate("tpl.pd_notes_overview.html", true, true, "Services/Notes");
		$tpl->setVariable("TXT_NOTES", $lng->txt("notes"));
		$showdetails = $ilUser->getPref('show_pd_notes_details') == 'y';
		// add details link
		if ($showdetails)
		{
			$tpl->setCurrentBlock("hide_details");
			$tpl->setVariable("LINK_HIDE_DETAILS",
				$ilCtrl->getLinkTargetByClass("ilpersonaldesktopgui",
					"hidePDNotesDetails"));
			$tpl->setVariable("TXT_HIDE_DETAILS",
				$this->lng->txt("hide_details"));
			$tpl->parseCurrentBlock();
		}
		else
		{
			$tpl->setCurrentBlock("show_details");
			$tpl->setVariable("LINK_SHOW_DETAILS",
				$ilCtrl->getLinkTargetByClass("ilpersonaldesktopgui",
					"showPDNotesDetails"));
			$tpl->setVariable("TXT_SHOW_DETAILS",
				$this->lng->txt("show_details"));
			$tpl->parseCurrentBlock();
		}

		// get last ten notes
		include_once("Services/Notes/classes/class.ilNote.php");
		$notes = ilNote::_getLastNotesOfUser();

		$output = false;
		foreach($notes as $note)
		{
			$output = true;

			$rowclass = ($rowclass != "tblrow1")
				? "tblrow1"
				: "tblrow2";
			$tpl->setCurrentBlock("note");
			$tpl->setVariable("ROWCLASS", $rowclass);
			$tpl->setVariable("VAL_SUBJECT", $note->getSubject());
			switch ($note->getLabel())
			{
				case IL_NOTE_UNLABELED:
					$tpl->setVariable("IMG_NOTE", ilUtil::getImagePath("note_unlabeled.gif"));
					$tpl->setVariable("ALT_NOTE", $lng->txt("note"));
					break;
					
				case IL_NOTE_IMPORTANT:
					$tpl->setVariable("IMG_NOTE", ilUtil::getImagePath("note_important.gif"));
					$tpl->setVariable("ALT_NOTE", $lng->txt("note").", ".$lng->txt("important"));
					break;
					
				case IL_NOTE_QUESTION:
					$tpl->setVariable("IMG_NOTE", ilUtil::getImagePath("note_question.gif"));
					$tpl->setVariable("ALT_NOTE", $lng->txt("note").", ".$lng->txt("question"));
					break;
					
				case IL_NOTE_PRO:
					$tpl->setVariable("IMG_NOTE", ilUtil::getImagePath("note_pro.gif"));
					$tpl->setVariable("ALT_NOTE", $lng->txt("note").", ".$lng->txt("pro"));
					break;
					
				case IL_NOTE_CONTRA:
					$tpl->setVariable("IMG_NOTE", ilUtil::getImagePath("note_contra.gif"));
					$tpl->setVariable("ALT_NOTE", $lng->txt("note").", ".$lng->txt("contra"));
					break;
			}

			// details
			$target = $note->getObject();
			if ($showdetails)
			{
				$tpl->setVariable("NOTE_TEXT",
					ilUtil::shortenText($note->getText(), 150, true, true));
				$tpl->setVariable("TXT_CREATED", $lng->txt("create_date"));
				$tpl->setVariable("VAL_DATE", substr($note->getCreationDate(),0,10));
				
				// target objects
				$this->showTargets($tpl, $target["rep_obj_id"], $note->getId(),
					$target["obj_type"], $target["obj_id"]);
			}
			
			// edit button
			$tpl->setCurrentBlock("edit_note");
			$tpl->setVariable("TXT_EDIT_NOTE", $this->lng->txt("edit"));
			$this->ctrl->setParameterByClass("ilnotegui", "rel_obj", $target["rep_obj_id"]);
			$this->ctrl->setParameterByClass("ilnotegui", "note_id", $note->getId());
			$this->ctrl->setParameterByClass("ilnotegui", "note_type", IL_NOTE_PRIVATE);
			$tpl->setVariable("LINK_EDIT_NOTE",
				$this->ctrl->getLinkTargetByClass(array("ilpdnotesgui", "ilnotegui"), "editNoteForm")
				."#note_edit");
			$tpl->parseCurrentBlock();
			
			$tpl->parseCurrentBlock();
			$tpl->setCurrentBlock("note_row");
			$tpl->parseCurrentBlock();
		}
		
		if ($output)
		{
			return $tpl->get();
		}
		else
		{
			return "";
		}
	}
	
	/**
	* get notes list including add note area
	*/ 
	function addNoteForm()
	{
		global $ilUser;
		
		$suffix = ($_GET["note_type"] == IL_NOTE_PRIVATE)
			? "private"
			: "public";
		$ilUser->setPref("notes_".$suffix, "y");

		$this->add_note_form = true;
		return $this->getNotesHTML();
	}
	
	/**
	* cancel add note
	*/ 
	function cancelAddNote()
	{
		return $this->getNotesHTML();
	}
	
	/**
	* cancel edit note
	*/ 
	function cancelUpdateNote()
	{
		return $this->getNotesHTML();
	}
	
	/**
	* add note
	*/
	function addNote()
	{
		global $ilUser;

		if($_POST["note"] != "" || $_POST["sub_note"] != "")
		{
			$note = new ilNote();
			$note->setObject($this->obj_type, $this->rep_obj_id, $this->obj_id);			
			$note->setType($_GET["note_type"]);
			$note->setAuthor($ilUser->getId());
			$note->setText(ilUtil::stripSlashes($_POST["note"]));
			$note->setSubject(ilUtil::stripSlashes($_POST["sub_note"]));
			$note->setLabel($_POST["note_label"]);
			$note->create();
		}
		
		return $this->getNotesHTML();
	}

	/**
	* update note
	*/
	function updateNote()
	{
		global $ilUser;

		$note = new ilNote($_POST["note_id"]);
		//$note->setObject($this->obj_type, $this->rep_obj_id, $this->obj_id);
		//$note->setType(IL_NOTE_PRIVATE);
		//$note->setAuthor($ilUser->getId());
		$note->setText(ilUtil::stripSlashes($_POST["note"]));
		$note->setSubject(ilUtil::stripSlashes($_POST["sub_note"]));
		$note->setLabel($_POST["note_label"]);
		$note->update();
		
		return $this->getNotesHTML();
	}

	/**
	* get notes list including add note area
	*/ 
	function editNoteForm()
	{
		$this->edit_note_form = true;
		return $this->getNotesHTML();
	}

	/**
	* delete note confirmation
	*/ 
	function deleteNote()
	{
		$this->delete_note = true;
		return $this->getNotesHTML();
	}
	
	/**
	* delete notes confirmation
	*/ 
	function deleteNotes()
	{
		global $lng;
		
		if (!$_POST["note"])
		{
			sendinfo($lng->txt("no_checkbox"));
		}
		else
		{
			$this->delete_note = true;
		}
		return $this->getNotesHTML();
	}

	/**
	* cancel deletion of note
	*/ 
	function cancelDelete()
	{
		return $this->getNotesHTML();
	}
	
	/**
	* cancel deletion of note
	*/ 
	function confirmDelete()
	{
		foreach($_POST["note"] as $id)
		{
			$note = new ilNote($id);
			$note->delete();
		}
		return $this->getNotesHTML();
	}

	/**
	* export selected notes to html
	*/ 
	function exportNotesHTML()
	{
		$tpl = new ilTemplate("tpl.main.html", true, true);

		$this->export_html = true;
		$this->multi_selection = false;
		$tpl->setVariable("CONTENT", $this->getNotesHTML());
		ilUtil::deliverData($tpl->get(), "notes.html");
	}
	
	/**
	* notes print view screen
	*/
	function printNotes()
	{
		$tpl = new ilTemplate("tpl.main.html", true, true);

		$this->print = true;
		$this->multi_selection = false;
		$tpl->setVariable("CONTENT", $this->getNotesHTML());
		echo $tpl->get(); exit;
	}

	/**
	* show notes
	*/
	function showNotes()
	{
		global $ilUser;

		$suffix = ($_GET["note_type"] == IL_NOTE_PRIVATE)
			? "private"
			: "public";
		$ilUser->writePref("notes_".$suffix, "y");

		return $this->getNotesHTML();
	}
	
	/**
	* hide notes
	*/
	function hideNotes()
	{
		global $ilUser;

		$suffix = ($_GET["note_type"] == IL_NOTE_PRIVATE)
			? "private"
			: "public";
		$ilUser->writePref("notes_".$suffix, "n");

		return $this->getNotesHTML();
	}

	/**
	* show all public notes to user
	*/
	function showAllPublicNotes()
	{
		global $ilUser;
		
		$ilUser->writePref("notes_pub_all", "y");
		
		return $this->getNotesHTML();
	}

	/**
	* show only public notes of user
	*/
	function showMyPublicNotes()
	{
		global $ilUser;
		
		$ilUser->writePref("notes_pub_all", "n");
		
		return $this->getNotesHTML();
	}
}
?>