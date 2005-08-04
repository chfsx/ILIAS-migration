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
* Class ilObjContentObjectGUI
*
* @author Stefan Meyer <smeyer@databay.de>
* @author Sascha Hofmann <saschahofmann@gmx.de>
* @author Alex Killing <alex.killing@gmx.de>
* $Id$
*
* @extends ilObjectGUI
* @package ilias-core
*/

include_once "classes/class.ilObjectGUI.php";
include_once "content/classes/class.ilObjContentObject.php";
include_once ("content/classes/class.ilLMPageObjectGUI.php");
include_once ("content/classes/class.ilStructureObjectGUI.php");

class ilObjContentObjectGUI extends ilObjectGUI
{
	var $ctrl;

	/**
	* Constructor
	*
	* @access	public
	*/
	function ilObjContentObjectGUI($a_data,$a_id = 0,$a_call_by_reference = true, $a_prepare_output = true)
	{
		global $lng, $ilCtrl;

		$this->ctrl =& $ilCtrl;
		$lng->loadLanguageModule("content");
		parent::ilObjectGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);
		$this->actions = $this->objDefinition->getActions("lm");

	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		if ($this->ctrl->getRedirectSource() == "ilinternallinkgui")
		{
			$this->explorer();
			return;
		}

		if ($this->ctrl->getCmdClass() == "ilinternallinkgui")
		{
			$this->ctrl->setReturn($this, "explorer");
		}

		// get next class that processes or forwards current command
		$next_class = $this->ctrl->getNextClass($this);

		// get current command
		$cmd = $this->ctrl->getCmd();

		$this->addLocations();
		switch($next_class)
		{
			case 'ilmdeditorgui':

				include_once 'Services/MetaData/classes/class.ilMDEditorGUI.php';
				$this->setTabs();
				$md_gui =& new ilMDEditorGUI($this->object->getId(), 0, $this->object->getType());
				$md_gui->addObserver($this->object,'MDUpdateListener','General');

				$this->ctrl->forwardCommand($md_gui);
				break;

			case "ilobjstylesheetgui":
				include_once ("classes/class.ilObjStyleSheetGUI.php");
				$this->ctrl->setReturn($this, "properties");
				$style_gui =& new ilObjStyleSheetGUI("", $this->object->getStyleSheetId(), false, false);
				$ret =& $this->ctrl->forwardCommand($style_gui);
				//$ret =& $style_gui->executeCommand();

				if ($cmd == "save")
				{
					$style_id = $ret;
					$this->object->setStyleSheetId($style_id);
					$this->object->update();
					$this->ctrl->redirectByClass("ilobjstylesheetgui", "edit");
				}
				break;

			case "illmpageobjectgui":
				$this->ctrl->setReturn($this, "properties");
//echo "!";
				//$this->lm_obj =& $this->ilias->obj_factory->getInstanceByRefId($this->ref_id);

				$pg_gui =& new ilLMPageObjectGUI($this->object);
				if ($_GET["obj_id"] != "")
				{
					$obj =& ilLMObjectFactory::getInstance($this->object, $_GET["obj_id"]);
					$pg_gui->setLMPageObject($obj);
				}
				//$ret =& $pg_gui->executeCommand();
				$ret =& $this->ctrl->forwardCommand($pg_gui);
				if ($cmd == "save" || $cmd == "cancel")
				{
					$this->ctrl->redirect($this, "pages");
				}
				break;

			case "ilstructureobjectgui":
				$this->ctrl->setReturn($this, "properties");
				$st_gui =& new ilStructureObjectGUI($this->object, $this->object->lm_tree);
				if ($_GET["obj_id"] != "")
				{
					$obj =& ilLMObjectFactory::getInstance($this->object, $_GET["obj_id"]);
					$st_gui->setStructureObject($obj);
				}
				//$ret =& $st_gui->executeCommand();
				$ret =& $this->ctrl->forwardCommand($st_gui);
				if ($cmd == "save" || $cmd == "cancel")
				{
					if ($_GET["obj_id"] == "")
					{
						$this->ctrl->redirect($this, "chapters");
					}
					else
					{
						$this->ctrl->setCmd("subchap");
						$this->executeCommand();
					}
				}
				break;

			default:
				if ($cmd == "create")
				{
					switch ($_POST["new_type"])
					{
						case "pg":
							$this->setTabs();
							$this->ctrl->setCmdClass("ilLMPageObjectGUI");
							$ret =& $this->executeCommand();
							break;

						case "st":
							$this->setTabs();
							$this->ctrl->setCmdClass("ilStructureObjectGUI");
							$ret =& $this->executeCommand();
							break;
					}
				}
				else
				{
					$ret =& $this->$cmd();

				}
				break;
		}
		return $ret;
	}

	function _forwards()
	{
		return array("ilLMPageObjectGUI", "ilStructureObjectGUI","ilObjStyleSheetGUI");
	}

	/**
	* edit properties form
	*/
	function properties()
	{
		$this->setTabs();

		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// view button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK",ILIAS_HTTP_PATH."/content/"."lm_presentation.php?ref_id=".$this->object->getRefID());
		$this->tpl->setVariable("BTN_TARGET"," target=\"ilContObj".$this->object->getID()."\" ");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("view"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "fixTreeConfirm"));
		//$this->tpl->setVariable("BTN_TARGET"," target=\"_top\" ");
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_fix_tree"));
		$this->tpl->parseCurrentBlock();

		//$this->tpl->touchBlock("btn_row");

		// edit public section
		if ($this->ilias->getSetting("pub_section"))
		{
			if ($this->object->getType() != "dbk")
			{
				$this->tpl->setCurrentBlock("btn_cell");
				$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "editPublicSection"));
				//$this->tpl->setVariable("BTN_TARGET"," target=\"_top\" ");
				$this->tpl->setVariable("BTN_TXT", $this->lng->txt("public_section"));
				$this->tpl->parseCurrentBlock();
			}
		}

		// lm properties
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_properties.html", true);
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_PROPERTIES", $this->lng->txt("cont_lm_properties"));

		// online
		$this->tpl->setVariable("TXT_ONLINE", $this->lng->txt("cont_online"));
		$this->tpl->setVariable("CBOX_ONLINE", "cobj_online");
		$this->tpl->setVariable("VAL_ONLINE", "y");
		if ($this->object->getOnline())
		{
			$this->tpl->setVariable("CHK_ONLINE", "checked");
		}

		// layout
		$this->tpl->setVariable("TXT_LAYOUT", $this->lng->txt("cont_def_layout"));
		$layouts = ilObjContentObject::getAvailableLayouts();
		$select_layout = ilUtil::formSelect ($this->object->getLayout(), "lm_layout",
			$layouts, false, true);
		$this->tpl->setVariable("SELECT_LAYOUT", $select_layout);

		// style
		$this->tpl->setVariable("TXT_STYLE", $this->lng->txt("cont_style"));
		$fixed_style = $this->ilias->getSetting("fixed_content_style_id");
		if ($fixed_style > 0)
		{
			$this->tpl->setVariable("VAL_STYLE",
				ilObject::_lookupTitle($fixed_style)." (".
				$this->lng->txt("global_fixed").")");
		}
		else
		{
			$this->tpl->setCurrentBlock("style_edit");
			if ($this->object->getStyleSheetId() > 0)
			{
				$this->tpl->setVariable("VAL_STYLE",
					ilObject::_lookupTitle($this->object->getStyleSheetId()));

				// edit icon
				$this->tpl->setVariable("LINK_STYLE_EDIT",
					$this->ctrl->getLinkTargetByClass("ilObjStyleSheetGUI", "edit"));
				$this->tpl->setVariable("TXT_STYLE_EDIT",
					$this->lng->txt("edit"));
				$this->tpl->setVariable("IMG_STYLE_EDIT",
					ilUtil::getImagePath("icon_pencil.gif"));

				// delete icon
				$this->tpl->setVariable("LINK_STYLE_DROP",
					$this->ctrl->getLinkTargetByClass("ilObjStyleSheetGUI", "delete"));
				$this->tpl->setVariable("TXT_STYLE_DROP",
					$this->lng->txt("delete"));
				$this->tpl->setVariable("IMG_STYLE_DROP",
					ilUtil::getImagePath("delete.gif"));
			}
			else
			{
				$this->tpl->setVariable("VAL_STYLE",
					$this->lng->txt("default"));
				$this->tpl->setVariable("LINK_STYLE_CREATE",
					$this->ctrl->getLinkTargetByClass("ilObjStyleSheetGUI", "create"));
				$this->tpl->setVariable("TXT_STYLE_CREATE",
					$this->lng->txt("create"));
			}
			$this->tpl->parseCurrentBlock();
		}

		// page header
		$this->tpl->setVariable("TXT_PAGE_HEADER", $this->lng->txt("cont_page_header"));
		$pg_header = array ("st_title" => $this->lng->txt("cont_st_title"),
			"pg_title" => $this->lng->txt("cont_pg_title"),
			"none" => $this->lng->txt("cont_none"));
		$select_pg_head = ilUtil::formSelect ($this->object->getPageHeader(), "lm_pg_header",
			$pg_header, false, true);
		$this->tpl->setVariable("SELECT_PAGE_HEADER", $select_pg_head);

		// chapter numbers
		$this->tpl->setVariable("TXT_NUMBER", $this->lng->txt("cont_act_number"));
		$this->tpl->setVariable("CBOX_NUMBER", "cobj_act_number");
		$this->tpl->setVariable("VAL_NUMBER", "y");
		if ($this->object->isActiveNumbering())
		{
			$this->tpl->setVariable("CHK_NUMBER", "checked");
		}

		// toc mode
		$this->tpl->setVariable("TXT_TOC_MODE", $this->lng->txt("cont_toc_mode"));
		$arr_toc_mode = array ("chapters" => $this->lng->txt("cont_chapters_only"),
			"pages" => $this->lng->txt("cont_chapters_and_pages"));
		$select_toc_mode = ilUtil::formSelect ($this->object->getTOCMode(), "toc_mode",
			$arr_toc_mode, false, true);
		$this->tpl->setVariable("SELECT_TOC_MODE", $select_toc_mode);

		// clean frames
		$this->tpl->setVariable("TXT_CLEAN_FRAMES", $this->lng->txt("cont_clean_frames"));
		$this->tpl->setVariable("TXT_CLEAN_FRAMES_DESC", $this->lng->txt("cont_clean_frames_desc"));
		$this->tpl->setVariable("CBOX_CLEAN_FRAMES", "cobj_clean_frames");
		$this->tpl->setVariable("VAL_CLEAN_FRAMES", "y");
		if ($this->object->cleanFrames())
		{
			$this->tpl->setVariable("CHK_CLEAN_FRAMES", "checked");
		}
		
		// history user comments
		$this->tpl->setVariable("TXT_HIST_USER_COMMENTS", $this->lng->txt("enable_hist_user_comments"));
		$this->tpl->setVariable("TXT_HIST_USER_COMMENTS_DESC", $this->lng->txt("enable_hist_user_comments_desc"));
		$this->tpl->setVariable("CBOX_HIST_USER_COMMENTS", "cobj_user_comments");
		$this->tpl->setVariable("VAL_HIST_USER_COMMENTS", "y");
		if ($this->object->isActiveHistoryUserComments())
		{
			$this->tpl->setVariable("CHK_HIST_USER_COMMENTS", "checked");
		}

		// lm menu
		$this->tpl->setVariable("TXT_LM_MENU", $this->lng->txt("cont_lm_menu"));
		$this->tpl->setVariable("TXT_ACT_MENU", $this->lng->txt("cont_active"));
		$this->tpl->setVariable("CBOX_LM_MENU", "cobj_act_lm_menu");
		$this->tpl->setVariable("VAL_LM_MENU", "y");
		if ($this->object->isActiveLMMenu())
		{
			$this->tpl->setVariable("CHK_LM_MENU", "checked");
		}

		// toc
		$this->tpl->setVariable("TXT_TOC", $this->lng->txt("cont_toc"));
		$this->tpl->setVariable("CBOX_TOC", "cobj_act_toc");
		$this->tpl->setVariable("VAL_TOC", "y");
		if ($this->object->isActiveTOC())
		{
			$this->tpl->setVariable("CHK_TOC", "checked");
		}

		// print view
		$this->tpl->setVariable("TXT_PRINT", $this->lng->txt("cont_print_view"));
		$this->tpl->setVariable("CBOX_PRINT", "cobj_act_print");
		$this->tpl->setVariable("VAL_PRINT", "y");
		if ($this->object->isActivePrintView())
		{
			$this->tpl->setVariable("CHK_PRINT", "checked");
		}
		
		// downloads
		$this->tpl->setVariable("TXT_DOWNLOADS", $this->lng->txt("cont_downloads"));
		$this->tpl->setVariable("TXT_DOWNLOADS_DESC", $this->lng->txt("cont_downloads_desc"));
		$this->tpl->setVariable("CBOX_DOWNLOADS", "cobj_act_downloads");
		$this->tpl->setVariable("VAL_DOWNLOADS", "y");
		if ($this->object->isActiveDownloads())
		{
			$this->tpl->setVariable("CHK_DOWNLOADS", "checked");
		}
		
		// get user defined menu entries
		$this->__initLMMenuEditor();
		$entries = $this->lmme_obj->getMenuEntries();
		
		if (count($entries) > 0)
		{
			foreach ($entries as $entry)
			{
				$this->ctrl->setParameter($this, "menu_entry", $entry["id"]);

				$this->tpl->setCurrentBlock("menu_entries");
				
				if ($entry["type"] == "intern")
				{
					$entry["link"] = ILIAS_HTTP_PATH."/goto.php?target=".$entry["link"];
				}

				// add http:// prefix if not exist
				if (!strstr($entry["link"],'http://'))
				{
					$entry["link"] = "http://".$entry["link"];
				}
				
				$this->tpl->setVariable("ENTRY_LINK", $entry["link"]);
				$this->tpl->setVariable("ENTRY_TITLE", $entry["title"]);

				$this->tpl->setVariable("CBOX_ENTRY", "menu_entries[]");
				$this->tpl->setVariable("VAL_ENTRY", $entry["id"]);

				if (ilUtil::yn2tf($entry["active"]))
				{
					$this->tpl->setVariable("CHK_ENTRY", "checked=\"checked\"");
				}


				$this->tpl->setVariable("LINK_EDIT", $this->ctrl->getLinkTarget($this,"editMenuEntry"));
				$this->tpl->setVariable("TARGET_EDIT", "content");
				$this->tpl->setVariable("TXT_EDIT", $this->lng->txt("edit"));
				$this->tpl->setVariable("IMG_EDIT", ilUtil::getImagePath("icon_pencil.gif"));

				$this->tpl->setVariable("LINK_DROP", $this->ctrl->getLinkTarget($this,"deleteMenuEntry"));
				$this->tpl->setVariable("TARGET_DROP", "content");
				$this->tpl->setVariable("TXT_DROP", $this->lng->txt("drop"));
				$this->tpl->setVariable("IMG_DROP", ilUtil::getImagePath("delete.gif"));

				$this->tpl->parseCurrentBlock();
			}
		}
		
		// add entry link


		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "saveProperties");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->setVariable("BTN_NAME2", "addMenuEntry");
		$this->tpl->setVariable("BTN_TEXT2", $this->lng->txt("add_menu_entry"));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* output explorer tree
	*/
	function explorer()
	{
		global $ilUser, $ilias;
		
		switch ($this->object->getType())
		{
			case "lm":
				$gui_class = "ilobjlearningmodulegui";
				break;

			case "dlb":
				$gui_class = "ilobjdlbookgui";
				break;
		}



		$this->tpl = new ilTemplate("tpl.main.html", true, true);
		// get learning module object
		//$this->lm_obj =& new ilObjLearningModule($this->ref_id, true);

		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());

		//$this->tpl = new ilTemplate("tpl.explorer.html", false, false);
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.explorer.html");

		require_once ("content/classes/class.ilLMEditorExplorer.php");
		$exp = new ilLMEditorExplorer($this->ctrl->getLinkTarget($this, "view"),
			$this->object, $gui_class);

		$exp->setTargetGet("obj_id");
		$exp->setExpandTarget($this->ctrl->getLinkTarget($this, "explorer"));

		if ($_GET["lmmovecopy"] == "1")
		{
			$this->proceedDragDrop();
		}
	

		if ($_GET["lmexpand"] == "")
		{
			$mtree = new ilTree($this->object->getId());
			$mtree->setTableNames('lm_tree','lm_data');
			$mtree->setTreeTablePK("lm_id");
			$expanded = $mtree->readRootId();
		}
		else
		{
			$expanded = $_GET["lmexpand"];
		}

		$exp->setExpand($expanded);

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();

		include_once("content/classes/Pages/class.ilPageEditorGUI.php");
		if (ilPageEditorGUI::_doJSEditing()) 
		{
			//$this->tpl->touchBlock("includejavascript");
			
			$IDS = "";
			for ($i=0;$i<count($exp->iconList);$i++) 
			{
				if ($i>0) $IDS .= ",";
				$IDS .= "'".$exp->iconList[$i]."'";
			}
			$this->tpl->setVariable("ICONIDS",$IDS);
			//$this->ctrl->setParameter($this, "lmovecopy", 1);
			$this->tpl->setVariable("TESTPFAD",$this->ctrl->getLinkTarget($this, "explorer")."&lmmovecopy=1");
			$this->tpl->setVariable("POPUPLINK",$this->ctrl->getLinkTarget($this, "popup")."&ptype=movecopytreenode");
		}
		
		
		$this->tpl->setCurrentBlock("content");
		$this->tpl->setVariable("TXT_EXPLORER_HEADER", $this->lng->txt("cont_chap_and_pages"));
		$this->tpl->setVariable("EXP_REFRESH", $this->lng->txt("refresh"));
		$this->tpl->setVariable("EXPLORER",$output);
		$this->ctrl->setParameter($this, "lmexpand", $_GET["lmexpand"]);
		//$this->tpl->setVariable("ACTION", "lm_edit.php?cmd=explorer&ref_id=".$this->ref_id."&lmexpand=".$_GET["lmexpand"]);
		$this->tpl->setVariable("ACTION", $this->ctrl->getLinkTarget($this, "explorer"));
		$this->tpl->parseCurrentBlock();
		//$this->tpl->show(false);

	}
	
	
	/**
	* proceed drag and drop operations on pages/chapters
	*/
	function proceedDragDrop()
	{
		$lmtree = new ilTree($this->object->getId());
		$lmtree->setTableNames('lm_tree','lm_data');
		$lmtree->setTreeTablePK("lm_id");


		// node-id of dragged object
		$source_id = $_GET["dragdropSource"];

		// node-id of object under dragged obj at drop
		$target_id = $_GET["dragdropTarget"];

		// "move" | "copy"
		$movecopy = $_GET["dragdropCopymove"];

		// "after" | "before" : copy or move the source-object before or after the selected target-object.
		$position = $_GET["dragdropPosition"];

		//echo "sourceId: $sourceId<br>";
		//echo "targetId: $targetId<br>";
		//echo "move or copy: $movecopy<br>";
		//echo "position: $position<br>";

		$source_obj = ilLMObjectFactory::getInstance($this->object, $source_id, true);
		$source_obj->setLMId($this->object->getId());
		$target_obj = ilLMObjectFactory::getInstance($this->object, $target_id, true);
		$target_obj->setLMId($this->object->getId());
		$target_parent = $lmtree->getParentId($target_id);


		// handle pages
		if ($source_obj->getType() == "pg")
		{
			if ($lmtree->isInTree($source_obj->getId()))
			{
				$node_data = $lmtree->getNodeData($source_obj->getId());

				// cut on move
				if ($movecopy == "move")
				{
					$parent_id = $lmtree->getParentId($source_obj->getId());
					$lmtree->deleteTree($node_data);

					// write history entry
					require_once("classes/class.ilHistory.php");
					ilHistory::_createEntry($source_obj->getId(), "cut",
						array(ilLMObject::_lookupTitle($parent_id), $parent_id),
						$this->object->getType().":pg");
					ilHistory::_createEntry($parent_id, "cut_page",
						array(ilLMObject::_lookupTitle($source_obj->getId()), $source_obj->getId()),
						$this->object->getType().":st");
				}
				else
				{
					// copy page
					$new_page =& $source_obj->copy();
					$source_id = $new_page->getId();
					$source_obj =& $new_page;
				}

				// paste page
				if(!$lmtree->isInTree($source_obj->getId()))
				{
					// move page after/before other page
					if ($target_obj->getType() == "pg")
					{
						$target_pos = $target_id;
						if ($position == "before")
						{
							$target_pos = IL_FIRST_NODE;
							if ($pred = $lmtree->fetchPredecessorNode($target_id))
							{
								if ($lmtree->getParentId($pred["child"]) == $target_parent)
								{
									$target_pos = $pred["child"];
								}
							}
						}
						$parent = $target_parent;
					}
					else // move page into chapter
					{
						$target_pos = IL_FIRST_NODE;
						$parent = $target_id;
					}

					// insert page into tree
					$lmtree->insertNode($source_obj->getId(),
						$parent, $target_pos);

					// write hisroty entry
					if ($movecopy == "move")
					{
						// write history comments
						include_once("classes/class.ilHistory.php");
						ilHistory::_createEntry($source_obj->getId(), "paste",
							array(ilLMObject::_lookupTitle($parent), $parent),
							$this->object->getType().":pg");
						ilHistory::_createEntry($parent, "paste_page",
							array(ilLMObject::_lookupTitle($source_obj->getId()), $source_obj->getId()),
							$this->object->getType().":st");
					}

				}
			}
		}

		// handle chapters
		if ($source_obj->getType() == "st")
		{
			// check wether target is a chapter
			if ($target_obj->getType() != "st")
			{
				return;
			}
			$source_node = $lmtree->getNodeData($source_id);
			$subnodes = $lmtree->getSubtree($source_node);

			// check, if target is within subtree
			foreach ($subnodes as $subnode)
			{
				if($subnode["obj_id"] == $target_id)
				{
					return;
				}
			}

			$target_pos = $target_id;

			// insert before
			if ($position == "before")
			{
				$target_pos = IL_FIRST_NODE;

				// look for predecessor chapter on same level
				$childs = $lmtree->getChildsByType($target_parent, "st");
				$found = false;
				foreach ($childs as $child)
				{
					if ($child["obj_id"] == $target_id)
					{
						$found = true;
					}
					if (!$found)
					{
						$target_pos = $child["obj_id"];
					}
				}

				// if target_pos is still first node we must skip all pages
				if ($target_pos == IL_FIRST_NODE)
				{
					$pg_childs =& $lmtree->getChildsByType($target_parent, "pg");
					if (count($pg_childs) != 0)
					{
						$target_pos = $pg_childs[count($pg_childs) - 1]["obj_id"];
					}
				}
			}

			// insert into
			if ($position == "into")
			{
				$target_parent = $target_id;
				$target_pos = IL_FIRST_NODE;

				// if target_pos is still first node we must skip all pages
				if ($target_pos == IL_FIRST_NODE)
				{
					$pg_childs =& $lmtree->getChildsByType($target_parent, "pg");
					if (count($pg_childs) != 0)
					{
						$target_pos = $pg_childs[count($pg_childs) - 1]["obj_id"];
					}
				}
			}


			// delete source tree
			if ($movecopy == "move")
			{
				$lmtree->deleteTree($source_node);
			}
			else
			{
				// copy chapter (incl. subcontents)
				$new_chapter =& $source_obj->copy($lmtree, $target_parent, $target_pos);
			}

			if (!$lmtree->isInTree($source_id))
			{
				$lmtree->insertNode($source_id, $target_parent, $target_pos);

				// insert moved tree
				if ($movecopy == "move")
				{
					foreach ($subnodes as $node)
					{
						if($node["obj_id"] != $source_id)
						{
							$lmtree->insertNode($node["obj_id"], $node["parent"]);
						}
					}
				}
			}

			// check the tree
			$this->object->checkTree();

		}


		$this->object->checkTree();
	}



	/**
	* save properties
	*/
	function saveProperties()
	{
		$this->object->setLayout($_POST["lm_layout"]);
		$this->object->setPageHeader($_POST["lm_pg_header"]);
		$this->object->setTOCMode($_POST["toc_mode"]);
		$this->object->setOnline(ilUtil::yn2tf($_POST["cobj_online"]));
		$this->object->setActiveLMMenu(ilUtil::yn2tf($_POST["cobj_act_lm_menu"]));
		$this->object->setActiveNumbering(ilUtil::yn2tf($_POST["cobj_act_number"]));
		$this->object->setActiveTOC(ilUtil::yn2tf($_POST["cobj_act_toc"]));
		$this->object->setActivePrintView(ilUtil::yn2tf($_POST["cobj_act_print"]));
		$this->object->setActiveDownloads(ilUtil::yn2tf($_POST["cobj_act_downloads"]));
		$this->object->setCleanFrames(ilUtil::yn2tf($_POST["cobj_clean_frames"]));
		$this->object->setHistoryUserComments(ilUtil::yn2tf($_POST["cobj_user_comments"]));
		$this->object->updateProperties();

		$this->__initLMMenuEditor();
		$this->lmme_obj->updateActiveStatus($_POST["menu_entries"]);

		sendInfo($this->lng->txt("msg_obj_modified"), true);
		$this->ctrl->redirect($this, "properties");
	}

	/**
	* form for new content object creation
	*/
	function createObject()
	{
		global $rbacsystem;

		$new_type = $_POST["new_type"] ? $_POST["new_type"] : $_GET["new_type"];

		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $new_type))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			// fill in saved values in case of error
			$data = array();
			$data["fields"] = array();
			$data["fields"]["title"] = ilUtil::prepareFormOutput($_SESSION["error_post_vars"]["Fobject"]["title"],true);
			$data["fields"]["desc"] = ilUtil::stripSlashes($_SESSION["error_post_vars"]["Fobject"]["desc"]);

			$this->getTemplateFile("create", $new_type);

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);

				if ($this->prepare_output)
				{
					$this->tpl->parseCurrentBlock();
				}
			}

			$this->tpl->setVariable("FORMACTION", $this->getFormAction("save","adm_object.php?cmd=gateway&ref_id=".
				$_GET["ref_id"]."&new_type=".$new_type));
			$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($new_type."_new"));
			$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt($new_type."_add"));
			$this->tpl->setVariable("CMD_SUBMIT", "save");
			$this->tpl->setVariable("TARGET", $this->getTargetFrame("save"));
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));

			$this->tpl->setVariable("TXT_IMPORT_LM", $this->lng->txt("import_".$new_type));
			$this->tpl->setVariable("TXT_LM_FILE", $this->lng->txt("file"));
			$this->tpl->setVariable("TXT_IMPORT", $this->lng->txt("import"));

			// get the value for the maximal uploadable filesize from the php.ini (if available)
			$umf=get_cfg_var("upload_max_filesize");
			// get the value for the maximal post data from the php.ini (if available)
			$pms=get_cfg_var("post_max_size");

			// use the smaller one as limit
			$max_filesize=min($umf, $pms);
			if (!$max_filesize) $max_filesize=max($umf, $pms);
			
			// gives out the limit as a littel notice :)
			$this->tpl->setVariable("TXT_FILE_INFO", $this->lng->txt("file_notice")." $max_filesize.");

		}
	}

	/**
	* save new content object to db
	*/
	function saveObject()
	{
		global $rbacadmin, $rbacsystem;

		// always call parent method first to create an object_data entry & a reference
		//$newObj = parent::saveObject();
		// TODO: fix MetaDataGUI implementation to make it compatible to use parent call
		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $_GET["new_type"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_create_permission"), $this->ilias->error_obj->MESSAGE);
		}
		else
		{
			// check title
			if ($_POST["Fobject"]["title"] == "")
			{
				$this->ilias->raiseError($this->lng->txt("please_enter_title"), $this->ilias->error_obj->MESSAGE);
				return;
			}

			// create and insert object in objecttree
			include_once("content/classes/class.ilObjContentObject.php");
			$newObj = new ilObjContentObject();
			$newObj->setType($this->type);
			$newObj->setTitle($_POST["Fobject"]["title"]);#"content object ".$newObj->getId());		// set by meta_gui->save
			$newObj->setDescription($_POST["Fobject"]["desc"]);	// set by meta_gui->save
			$newObj->create();
			$newObj->createReference();
			$newObj->putInTree($_GET["ref_id"]);
			$newObj->setPermissions($_GET["ref_id"]);
			$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());

			// setup rolefolder & default local roles (moderator)
			//$roles = $newObj->initDefaultRoles();
			// assign author role to creator of forum object
			//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "n");
			//ilObjUser::updateActiveRoles($newObj->getOwner());
			// create content object tree
			$newObj->createLMTree();
			unset($newObj);

			// always send a message
			sendInfo($this->lng->txt($this->type."_added"), true);
			ilUtil::redirect($this->getReturnLocation("save","adm_object.php?".$this->link_params));
		}
	}

	// called by administration
/*
	function chooseMetaSectionObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->edit("ADM_CONTENT", "adm_content",
			$a_target, $_REQUEST["meta_section"]);
	}
*/

	// called by editor
/*
	function chooseMetaSection()
	{
		$this->setTabs();
//echo "<br>target:".$this->ctrl->getLinkTarget($this).":";
		$this->chooseMetaSectionObject($this->ctrl->getLinkTarget($this));
	}
*/

/*
	function addMetaObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_name = $_POST["meta_name"] ? $_POST["meta_name"] : $_GET["meta_name"];
		$meta_index = $_POST["meta_index"] ? $_POST["meta_index"] : $_GET["meta_index"];
		if ($meta_index == "")
			$meta_index = 0;
		$meta_path = $_POST["meta_path"] ? $_POST["meta_path"] : $_GET["meta_path"];
		$meta_section = $_POST["meta_section"] ? $_POST["meta_section"] : $_GET["meta_section"];
		if ($meta_name != "")
		{
			$meta_gui->meta_obj->add($meta_name, $meta_path, $meta_index);
		}
		else
		{
			sendInfo($this->lng->txt("meta_choose_element"), true);
		}
		$meta_gui->edit("ADM_CONTENT", "adm_content", $a_target, $meta_section);
	}
*/

/*
	function addMeta()
	{
		$this->setTabs();
		$this->addMetaObject($this->ctrl->getLinkTarget($this));
	}
*/

	/**
	* add bib item (admin call)
	*/
	function addBibItemObject($a_target = "")
	{
		include_once "content/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemName = $_POST["bibItemName"] ? $_POST["bibItemName"] : $_GET["bibItemName"];
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		if ($bibItemIndex == "")
			$bibItemIndex = 0;
		$bibItemPath = $_POST["bibItemPath"] ? $_POST["bibItemPath"] : $_GET["bibItemPath"];
		if ($bibItemName != "")
		{
			$bib_gui->bib_obj->add($bibItemName, $bibItemPath, $bibItemIndex);
			$data = $bib_gui->bib_obj->getElement("BibItem");
			$bibItemIndex = (count($data) - 1);
		}
		else
		{
			sendInfo($this->lng->txt("bibitem_choose_element"), true);
		}
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* add bib item (module call)
	*/
	function addBibItem()
	{
		$this->setTabs();
		$this->addBibItemObject($this->ctrl->getLinkTarget($this));
	}

/*
	function deleteMetaObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_index = $_POST["meta_index"] ? $_POST["meta_index"] : $_GET["meta_index"];
		$meta_gui->meta_obj->delete($_GET["meta_name"], $_GET["meta_path"], $meta_index);
		$meta_gui->edit("ADM_CONTENT", "adm_content", $a_target, $_GET["meta_section"]);
	}
*/

/*
	function deleteMeta()
	{
		$this->setTabs();
		$this->deleteMetaObject($this->ctrl->getLinkTarget($this));
	}
*/

	/**
	* delete bib item (admin call)
	*/
	function deleteBibItemObject($a_target = "")
	{
		include_once "content/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		$bib_gui->bib_obj->delete($_GET["bibItemName"], $_GET["bibItemPath"], $bibItemIndex);
		if (strpos($bibItemIndex, ",") > 0)
		{
			$bibItemIndex = substr($bibItemIndex, 0, strpos($bibItemIndex, ","));
		}
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* delete bib item (module call)
	*/
	function deleteBibItem()
	{
		$this->setTabs();
		$this->deleteBibItemObject($this->ctrl->getLinkTarget($this));
	}

/*
	function editMetaObject($a_target = "")
	{

		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
//echo "target:$a_target:";
		$meta_gui->edit("ADM_CONTENT", "adm_content", $a_target, $_GET["meta_section"]);
	}

	function editMeta()
	{
		$this->setTabs();
		$this->editMetaObject($this->ctrl->getLinkTarget($this));
	}
*/

	/**
	* edit bib items (admin call)
	*/
	function editBibItemObject($a_target = "")
	{
		include_once "content/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		$bibItemIndex *= 1;
		if ($bibItemIndex < 0)
		{
			$bibItemIndex = 0;
		}
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* edit bib items (module call)
	*/
	function editBibItem()
	{
		$this->setTabs();
		$this->editBibItemObject($this->ctrl->getLinkTarget($this));
	}

/*
	function saveMetaObject($a_target = "adm_object.php")
	{
		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
//echo "title_value:".htmlentities($_POST["meta"]["Title"]["Value"]); exit;
		$meta_gui->save($_POST["meta_section"]);
		ilUtil::redirect($a_target . "?cmd=editMeta&ref_id=" . $this->object->getRefId() . "&meta_section=" . $_POST["meta_section"]);
	}

	function saveMeta()
	{
//		$this->saveMetaObject("lm_edit.php");
	}
*/

	/**
	* save bib item (admin call)
	*/
	function saveBibItemObject($a_target = "")
	{
		include_once "content/classes/class.ilBibItemGUI.php";
		$bib_gui =& new ilBibItemGUI();
		$bib_gui->setObject($this->object);
		$bibItemIndex = $_POST["bibItemIndex"] ? $_POST["bibItemIndex"] : $_GET["bibItemIndex"];
		$bibItemIndex *= 1;
		if ($bibItemIndex < 0)
		{
			$bibItemIndex = 0;
		}
		$bibItemIndex = $bib_gui->save($bibItemIndex);

		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=" . $this->object->getRefId();
		}

		$bib_gui->edit("ADM_CONTENT", "adm_content", $a_target, $bibItemIndex);
	}

	/**
	* save bib item (module call)
	*/
	function saveBibItem()
	{
		$this->setTabs();
		$this->saveBibItemObject($this->ctrl->getLinkTarget($this));
	}

	/**
	* export object
	*
	* @access	public
	*/
	function exportObject()
	{
		return;
	}

	/**
	* display dialogue for importing XML-LeaningObjects
	*
	* @access	public
	*/
	function importObject()
	{
		$this->createObject();
		return;
	}


	/**
	* display status information or report errors messages
	* in case of error
	*
	* @access	public
	*/
	function importFileObject()
	{
		global $_FILES, $rbacsystem, $ilDB;

		include_once "content/classes/class.ilObjLearningModule.php";

		// check if file was uploaded
		$source = $_FILES["xmldoc"]["tmp_name"];
		if (($source == 'none') || (!$source))
		{
			$this->ilias->raiseError("No file selected!",$this->ilias->error_obj->MESSAGE);
		}
		// check create permission
		/*
		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $_GET["new_type"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_create_permission"), $this->ilias->error_obj->WARNING);
		}*/

		// check correct file type
		$info = pathinfo($_FILES["xmldoc"]["name"]);
		if (strtolower($info["extension"]) != "zip")
		{
			$this->ilias->raiseError("File must be a zip file!",$this->ilias->error_obj->MESSAGE);
		}

		// create and insert object in objecttree
		include_once("content/classes/class.ilObjContentObject.php");
		$newObj = new ilObjContentObject();
		$newObj->setType($_GET["new_type"]);
		$newObj->setTitle($_FILES["xmldoc"]["name"]);
		$newObj->setDescription("");
		$newObj->create(true);
		$newObj->createReference();
		$newObj->putInTree($_GET["ref_id"]);
		$newObj->setPermissions($_GET["ref_id"]);
		$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());

		// create learning module tree
		$newObj->createLMTree();

		// create import directory
		$newObj->createImportDirectory();

		// copy uploaded file to import directory
		$file = pathinfo($_FILES["xmldoc"]["name"]);
		$full_path = $newObj->getImportDirectory()."/".$_FILES["xmldoc"]["name"];

		ilUtil::moveUploadedFile($_FILES["xmldoc"]["tmp_name"],
			$_FILES["xmldoc"]["name"], $full_path);

		//move_uploaded_file($_FILES["xmldoc"]["tmp_name"], $full_path);

		// unzip file
		ilUtil::unzip($full_path);

		// determine filename of xml file
		$subdir = basename($file["basename"],".".$file["extension"]);
		$xml_file = $newObj->getImportDirectory()."/".$subdir."/".$subdir.".xml";

		// check whether subdirectory exists within zip file
		if (!is_dir($newObj->getImportDirectory()."/".$subdir))
		{
			$this->ilias->raiseError(sprintf($this->lng->txt("cont_no_subdir_in_zip"), $subdir),
				$this->ilias->error_obj->MESSAGE);
		}

		// check whether xml file exists within zip file
		if (!is_file($xml_file))
		{
			$this->ilias->raiseError(sprintf($this->lng->txt("cont_zip_file_invalid"), $subdir."/".$subdir.".xml"),
				$this->ilias->error_obj->MESSAGE);
		}

		include_once ("content/classes/class.ilContObjParser.php");
		$contParser = new ilContObjParser($newObj, $xml_file, $subdir);
		$contParser->startParsing();
		ilObject::_writeImportId($newObj->getId(), $newObj->getImportId());
		$newObj->MDUpdateListener('General');

		// import style
		$style_file = $newObj->getImportDirectory()."/".$subdir."/style.xml";
		if (is_file($style_file))
		{
			require_once("classes/class.ilObjStyleSheet.php");
			$style = new ilObjStyleSheet();
			$style->createFromXMLFile($style_file);
			$newObj->writeStyleSheetId($style->getId());
		}
		
		// delete import directory
		ilUtil::delDir($newObj->getImportDirectory());

		sendInfo($this->lng->txt($this->type."_added"),true);
		ilUtil::redirect($this->getReturnLocation("save","adm_object.php?".$this->link_params));

	}

	/**
	* show chapters
	*/
	function chapters()
	{
		global $tree;

		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.structure_edit.html", true);
		$num = 0;

		$this->ctrl->setParameter($this, "backcmd", "chapters");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("HEADER_TEXT", $this->lng->txt("cont_chapters"));
		$this->tpl->setVariable("CHECKBOX_TOP", IL_FIRST_NODE);


		$cnt = 0;
		$childs = $this->lm_tree->getChilds($this->lm_tree->getRootId());
		foreach ($childs as $child)
		{
			if($child["type"] != "st")
			{
				continue;
			}

			$this->tpl->setCurrentBlock("table_row");
			// color changing
			$css_row = ilUtil::switchColor($cnt++,"tblrow1","tblrow2");

			// checkbox
			$this->tpl->setVariable("CHECKBOX_ID", $child["obj_id"]);
			$this->tpl->setVariable("CSS_ROW", $css_row);
			$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_st.gif"));

			// link
			$this->ctrl->setParameter($this, "backcmd", "");
			$this->ctrl->setParameterByClass("ilStructureObjectGUI", "obj_id", $child["obj_id"]);
			$this->tpl->setVariable("LINK_TARGET",
				$this->ctrl->getLinkTargetByClass("ilStructureObjectGUI", "view"));

			// title
			$this->tpl->setVariable("TEXT_CONTENT", $child["title"]);

			$this->tpl->parseCurrentBlock();
		}
		if($cnt == 0)
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("NUM_COLS", 3);
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			// SHOW VALID ACTIONS
			$this->tpl->setVariable("NUM_COLS", 3);
			$acts = array("delete" => "delete", "move" => "moveChapter",
				"copy" => "copyChapter");
			if (ilEditClipboard::getContentObjectType() == "st")
			{
				if ($this->lm_tree->isInTree(ilEditClipboard::getContentObjectId())
					|| ilEditClipboard::getAction() == "copy")
				{
					$acts["pasteChapter"] =  "pasteChapter";
				}
			}
			$this->setActions($acts);
			$this->showActions();
		}

		// SHOW POSSIBLE SUB OBJECTS
		$this->tpl->setVariable("NUM_COLS", 3);
		$subobj = array("st");
		$opts = ilUtil::formSelect(12,"new_type",$subobj);
		$this->tpl->setCurrentBlock("add_object");
		$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
		$this->tpl->setVariable("BTN_NAME", "create");
		$this->tpl->setVariable("TXT_ADD", $this->lng->txt("insert"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("form");
		$this->tpl->parseCurrentBlock();

	}


	/*
	* list all pages of learning module
	*/
	function pages()
	{
		global $tree;

		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.all_pages.html", true);
		$num = 0;

		$this->tpl->setCurrentBlock("form");
		$this->ctrl->setParameter($this, "backcmd", "pages");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("HEADER_TEXT", $this->lng->txt("cont_pages"));
		$this->tpl->setVariable("CONTEXT", $this->lng->txt("context"));
		$this->tpl->setVariable("CHECKBOX_TOP", IL_FIRST_NODE);

		$cnt = 0;
		$pages = ilLMPageObject::getPageList($this->object->getId());
		foreach ($pages as $page)
		{
			$this->tpl->setCurrentBlock("table_row");
			// color changing
			$css_row = ilUtil::switchColor($cnt++,"tblrow1","tblrow2");

			// checkbox
			$this->tpl->setVariable("CHECKBOX_ID", $page["obj_id"]);
			$this->tpl->setVariable("CSS_ROW", $css_row);
			$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_le.gif"));

			// link
			$this->ctrl->setParameter($this, "backcmd", "");
			$this->ctrl->setParameterByClass("ilLMPageObjectGUI", "obj_id", $page["obj_id"]);
//echo "<br>:".$this->ctrl->getLinkTargetByClass("ilLMPageObjectGUI", "view").":";
			$this->tpl->setVariable("LINK_TARGET",
				$this->ctrl->getLinkTargetByClass("ilLMPageObjectGUI", "view"));

			// title
			$this->tpl->setVariable("TEXT_CONTENT", $page["title"]);

			// context
			if ($this->lm_tree->isInTree($page["obj_id"]))
			{
				$path_str = $this->getContextPath($page["obj_id"]);
			}
			else
			{
				$path_str = "---";
			}
			$this->tpl->setVariable("TEXT_CONTEXT", $path_str);

			$this->tpl->parseCurrentBlock();
		}
		if($cnt == 0)
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("NUM_COLS", 4);
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$acts = array("delete" => "delete", "movePage" => "movePage", "copyPage" => "copyPage");
			if(ilEditClipboard::getContentObjectType() == "pg" &&
				ilEditClipboard::getAction() == "copy")
			{
				$acts["pastePage"] = "pastePage";
			}

			/*
			if (ilEditClipboard::getContentObjectType() == "st")
			{
				$acts["pasteChapter"] =  "pasteChapter";
			}*/
			$this->setActions($acts);
			$this->tpl->setVariable("NUM_COLS", 4);
			$this->showActions();

			// SHOW VALID ACTIONS
			/*
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME", "delete");
			$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("delete"));
			$this->tpl->parseCurrentBlock();*/

		}

		// SHOW POSSIBLE SUB OBJECTS
		$this->tpl->setVariable("NUM_COLS", 4);
		//$this->showPossibleSubObjects("st");
		$subobj = array("pg");
		$opts = ilUtil::formSelect(12,"new_type",$subobj);
		$this->tpl->setCurrentBlock("add_object");
		$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
		$this->tpl->setVariable("BTN_NAME", "create");
		$this->tpl->setVariable("TXT_ADD", $this->lng->txt("create"));
		$this->tpl->parseCurrentBlock();


		$this->tpl->setCurrentBlock("form");
		$this->tpl->parseCurrentBlock();

	}

	/**
	* paste page
	*/
	function pastePage()
	{
		if(ilEditClipboard::getContentObjectType() != "pg")
		{
			$this->ilias->raiseError($this->lng->txt("no_page_in_clipboard"),$this->ilias->error_obj->MESSAGE);
		}

		// paste selected object
		$id = ilEditClipboard::getContentObjectId();

		// copy page, if action is copy
		if (ilEditClipboard::getAction() == "copy")
		{
			// check wether page belongs to lm
			if (ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId())
				== $this->object->getID())
			{
				$lm_page = new ilLMPageObject($this->object, $id);
				$new_page =& $lm_page->copy();
				$id = $new_page->getId();
			}
			else
			{
				// get page from other content object into current content object
				$lm_id = ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId());
				$lm_obj =& $this->ilias->obj_factory->getInstanceByObjId($lm_id);
				$lm_page = new ilLMPageObject($lm_obj, $id);
				$new_page =& $lm_page->copyToOtherContObject($this->object);
				$id = $new_page->getId();
			}
		}
		
		// cut is not be possible in "all pages" form yet
		if (ilEditClipboard::getAction() == "cut")
		{
			// check wether page belongs not to lm
			if (ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId())
				!= $this->object->getID())
			{
				$lm_id = ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId());
				$lm_obj =& $this->ilias->obj_factory->getInstanceByObjId($lm_id);
				$lm_page = new ilLMPageObject($lm_obj, $id);
				$lm_page->setLMId($this->object->getID());
				$lm_page->update();
				$page =& $lm_page->getPageObject();
				$page->buildDom();
				$page->setParentId($this->object->getID());
				$page->update();
			}
		}


		ilEditClipboard::clear();
		$this->ctrl->redirect($this, "pages");
	}

	/**
	* copy page
	*/
	function copyPage()
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		// SAVE POST VALUES
		ilEditClipboard::storeContentObject("pg",$_POST["id"][0],"copy");

		sendInfo($this->lng->txt("msg_copy_clipboard"), true);

		$this->ctrl->redirect($this, "pages");
	}

	/**
	* confirm deletion screen for page object and structure object deletion
	*
	* @param	int		$a_parent_subobj_id		id of parent object (structure object)
	*											of the objects, that should be deleted
	*											(or no parent object id for top level)
	*/
	function delete($a_parent_subobj_id = 0)
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		if(count($_POST["id"]) == 1 && $_POST["id"][0] == IL_FIRST_NODE)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_item"), $this->ilias->error_obj->MESSAGE);
		}

		if ($a_parent_subobj_id == 0)
		{
			$this->setTabs();
		}

		// SAVE POST VALUES
		$_SESSION["saved_post"] = $_POST["id"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm_deletion.html", true);

		sendInfo($this->lng->txt("info_delete_sure"));

		if ($a_parent_subobj_id != 0)
		{
			$this->ctrl->setParameterByClass("ilStructureObjectGUI", "backcmd", $_GET["backcmd"]);
			$this->ctrl->setParameterByClass("ilStructureObjectGUI", "obj_id", $a_parent_subobj_id);
			$this->tpl->setVariable("FORMACTION",
				$this->ctrl->getFormActionByClass("ilStructureObjectGUI"));
		}
		else
		{
			$this->ctrl->setParameter($this, "backcmd", $_GET["backcmd"]);
			$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		}
		// BEGIN TABLE HEADER
		$this->tpl->setCurrentBlock("table_header");
		$this->tpl->setVariable("TEXT",$this->lng->txt("objects"));
		$this->tpl->parseCurrentBlock();

		// END TABLE HEADER

		// BEGIN TABLE DATA
		$counter = 0;
		foreach($_POST["id"] as $id)
		{
			if ($id != IL_FIRST_NODE)
			{
				$obj =& new ilLMObject($this->object, $id);
				switch($obj->getType())		// ok that's not so nice, could be done better
				{
					case "pg":
						$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_le.gif"));
						break;
					case "st":
						$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_cat.gif"));
						break;
				}
				$this->tpl->setCurrentBlock("table_row");
				$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
				$this->tpl->setVariable("TEXT_CONTENT", $obj->getTitle());
				$this->tpl->parseCurrentBlock();
			}
		}

		// cancel/confirm button
		$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$buttons = array( "cancelDelete"  => $this->lng->txt("cancel"),
								  "confirmedDelete"  => $this->lng->txt("confirm"));
		foreach ($buttons as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* cancel delete
	*/
	function cancelDelete()
	{
		session_unregister("saved_post");

		$this->ctrl->redirect($this, $_GET["backcmd"]);

	}

	/**
	* delete page object or structure objects
	*
	* @param	int		$a_parent_subobj_id		id of parent object (structure object)
	*											of the objects, that should be deleted
	*											(or no parent object id for top level)
	*/
	function confirmedDelete($a_parent_subobj_id = 0)
	{
		$tree = new ilTree($this->object->getId());
		$tree->setTableNames('lm_tree','lm_data');
		$tree->setTreeTablePK("lm_id");

		// check number of objects
		if (!isset($_SESSION["saved_post"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// delete all selected objects
		foreach ($_SESSION["saved_post"] as $id)
		{
			if ($id != IL_FIRST_NODE)
			{
				$obj =& ilLMObjectFactory::getInstance($this->object, $id, false);
				$node_data = $tree->getNodeData($id);
				if (is_object($obj))
				{
					$obj->setLMId($this->object->getId());
					$obj->delete();
				}
				if($tree->isInTree($id))
				{
					$tree->deleteTree($node_data);
				}
			}
		}

		// check the tree
		$this->object->checkTree();

		// feedback
		sendInfo($this->lng->txt("info_deleted"),true);

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, $_GET["backcmd"]);
		}
	}



	/**
	* get context path in content object tree
	*
	* @param	int		$a_endnode_id		id of endnode
	* @param	int		$a_startnode_id		id of startnode
	*/
	function getContextPath($a_endnode_id, $a_startnode_id = 1)
	{
		$path = "";

		$tmpPath = $this->lm_tree->getPathFull($a_endnode_id, $a_startnode_id);

		// count -1, to exclude the learning module itself
		for ($i = 1; $i < (count($tmpPath) - 1); $i++)
		{
			if ($path != "")
			{
				$path .= " > ";
			}

			$path .= $tmpPath[$i]["title"];
		}

		return $path;
	}



	/**
	* show possible action (form buttons)
	*
	* @access	public
	*/
	function showActions()
	{
		$notoperations = array();

		$operations = array();

		$d = $this->actions;

		foreach ($d as $row)
		{
			if (!in_array($row["name"], $notoperations))
			{
				$operations[] = $row;
			}
		}

		if (count($operations)>0)
		{
			foreach ($operations as $val)
			{
				$this->tpl->setCurrentBlock("operation_btn");
				$this->tpl->setVariable("BTN_NAME", $val["lng"]);
				$this->tpl->setVariable("BTN_VALUE", $this->lng->txt($val["lng"]));
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("operation");
			$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* edit permissions
	*/
	function perm()
	{
		$this->setTabs();

		$this->setFormAction("addRole", $this->ctrl->getLinkTarget($this, "addRole"));
		$this->setFormAction("permSave", $this->ctrl->getLinkTarget($this, "permSave"));
		$this->permObject();
	}


	/**
	* save permissions
	*/
	function permSave()
	{
		$this->setReturnLocation("permSave", $this->ctrl->getLinkTarget($this, "perm"));
		$this->permSaveObject();
	}
	
	/**
	* info permissions
	*/
	function info()
	{
		$this->setTabs();
		$this->infoObject();
	}


	/**
	* add local role
	*/
	function addRole()
	{
		$this->setReturnLocation("addRole", $this->ctrl->getLinkTarget($this, "perm"));
		$this->addRoleObject();
	}


	/**
	* show owner of content object
	*/
	function owner()
	{
		$this->setTabs();
		$this->ownerObject();
	}


	/**
	* view content object
	*/
	function view()
	{
		$this->viewObject();
	}


	/**
	* move a single chapter  (selection)
	*/
	function moveChapter($a_parent_subobj_id = 0)
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
//echo "Hallo::"; exit;
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		if(count($_POST["id"]) == 1 && $_POST["id"][0] == IL_FIRST_NODE)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_item"), $this->ilias->error_obj->MESSAGE);
		}

		// SAVE POST VALUES
		ilEditClipboard::storeContentObject("st", $_POST["id"][0], "move");

		sendInfo($this->lng->txt("cont_chap_select_target_now"), true);

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}


	/**
	* copy a single chapter  (selection)
	*/
	function copyChapter($a_parent_subobj_id = 0)
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		if(count($_POST["id"]) == 1 && $_POST["id"][0] == IL_FIRST_NODE)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_item"), $this->ilias->error_obj->MESSAGE);
		}

		// SAVE POST VALUES
		ilEditClipboard::storeContentObject("st", $_POST["id"][0], "copy");

		sendInfo($this->lng->txt("cont_chap_copy_select_target_now"), true);

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}

	/**
	* paste chapter
	*/
	function pasteChapter($a_parent_subobj_id = 0)
	{
		if (ilEditClipboard::getContentObjectType() != "st")
		{
			$this->ilias->raiseError($this->lng->txt("no_chapter_in_clipboard"),$this->ilias->error_obj->MESSAGE);
		}

		$target_tree = new ilTree($this->object->getId());
		$target_tree->setTableNames('lm_tree','lm_data');
		$target_tree->setTreeTablePK("lm_id");

		// check wether page belongs to lm
		if (ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId())
			!= $this->object->getID())
		{
			$source_tree = new ilTree(ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId()));
			$source_tree->setTableNames('lm_tree','lm_data');
			$source_tree->setTreeTablePK("lm_id");
		}
		else
		{
			$source_tree =& $target_tree;
		}

		// check, if target is within subtree
		$id = ilEditClipboard::getContentObjectId();
		$node = $source_tree->getNodeData($id);
		$subnodes = $source_tree->getSubtree($node);
		foreach ($subnodes as $subnode)
		{
			if($subnode["obj_id"] == $a_parent_subobj_id)
			{
				$this->ilias->raiseError($this->lng->txt("cont_target_within_source"),$this->ilias->error_obj->MESSAGE);
			}
		}
		if($_POST["id"][0] == $id)
		{
			ilEditClipboard::clear();
			$this->ctrl->redirect($this, "chapters");
		}

		// determin parent
		$parent = ($a_parent_subobj_id == 0)
			? $target_tree->getRootId()
			: $a_parent_subobj_id;

		// determine target position
		if(!isset($_POST["id"]))
		{
			$target = IL_LAST_NODE;
		}
		else
		{
			$target = $_POST["id"][0];
		}
		if($target == IL_FIRST_NODE) // do not move a chapter in front of a page
		{
			$childs =& $target_tree->getChildsByType($parent, "pg");
			if (count($childs) != 0)
			{
				$target = $childs[count($childs) - 1]["obj_id"];
			}
		}

		//echo ":".$id.":";
		// delete old tree entries
//echo "-".ilEditClipboard::getAction()."-";
		if (ilEditClipboard::getAction() == "move")
		{
			if (ilLMObject::_lookupContObjID(ilEditClipboard::getContentObjectId())
				!= $this->object->getID())
			{
				// we should never reach these lines, moving chapters from on
				// lm to another is not supported
				ilEditClipboard::clear();
				if ($a_parent_subobj_id == 0)
				{
					$this->ctrl->redirect($this, "chapters");
				}
				return;
			}

			$target_tree->deleteTree($node);

			if (!$target_tree->isInTree($id))
			{
				$target_tree->insertNode($id, $parent, $target);

				foreach ($subnodes as $node)
				{
					//$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);
					//$obj_data->putInTree($node["parent"]);
					if($node["obj_id"] != $id)
					{
						$target_tree->insertNode($node["obj_id"], $node["parent"]);
					}
				}
			}
		}
		else	// copy
		{
			$id = ilEditClipboard::getContentObjectId();

			if (ilLMObject::_lookupContObjID($id)
				== $this->object->getID())
			{
				$source_obj = ilLMObjectFactory::getInstance($this->object, $id, true);
				$source_obj->setLMId($this->object->getId());
				$source_obj->copy($target_tree, $parent, $target);
			}
			else
			{
				$lm_id = ilLMObject::_lookupContObjID($id);

				$source_lm =& ilObjectFactory::getInstanceByObjId($lm_id);
				$source_obj = ilLMObjectFactory::getInstance($source_lm, $id, true);
				$source_obj->setLMId($lm_id);
				$source_obj->copy($target_tree, $parent, $target);
			}
		}

		ilEditClipboard::clear();

		// check the tree
		$this->object->checkTree();

		if ($a_parent_subobj_id == 0)
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}

	/**
	* move page
	*/
	function movePage()
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		// SAVE POST VALUES
		ilEditClipboard::storeContentObject("pg", $_POST["id"][0]);

		sendInfo($this->lng->txt("cont_page_select_target_now"), true);
		$this->ctrl->redirect($this, "pages");
	}

	/**
	* cancel action
	*/
	function cancel()
	{
		if ($_GET["new_type"] == "pg")
		{
			$this->ctrl->redirect($this, "pages");
		}
		else
		{
			$this->ctrl->redirect($this, "chapters");
		}
	}


	/**
	* export content object
	*/
	function export()
	{
		require_once("content/classes/class.ilContObjectExport.php");
		$cont_exp = new ilContObjectExport($this->object);
		$cont_exp->buildExportFile();
		$this->ctrl->redirect($this, "exportList");
	}

	/**
	* show list of export files
	*/
	function exportMenu()
	{
		// create xml export file button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "export"));
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_create_export_file_xml"));
		$this->tpl->parseCurrentBlock();
		
		// create html export file button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "exportHTML"));
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_create_export_file_html"));
		$this->tpl->parseCurrentBlock();

		// create scorm export file button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "exportSCORM"));
		$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_create_export_file_scorm"));
		$this->tpl->parseCurrentBlock();

		// view last export log button
		if (is_file($this->object->getExportDirectory()."/export.log"))
		{
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK", $this->ctrl->getLinkTarget($this, "viewExportLog"));
			$this->tpl->setVariable("BTN_TXT", $this->lng->txt("cont_view_last_export_log"));
			$this->tpl->parseCurrentBlock();
		}

	}
		
	/*
	* list all export files
	*/
	function exportList()
	{
		global $tree;

		$this->setTabs();

		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		$this->exportMenu();
		
		$export_files = $this->object->getExportFiles();

		// create table
		require_once("classes/class.ilTableGUI.php");
		$tbl = new ilTableGUI();

		// load files templates
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");

		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.export_file_row.html", true);

		$num = 0;

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$tbl->setTitle($this->lng->txt("cont_export_files"));

		$tbl->setHeaderNames(array("", $this->lng->txt("type"),
			$this->lng->txt("cont_file"),
			$this->lng->txt("cont_size"), $this->lng->txt("date") ));

		$cols = array("", "type", "file", "size", "date");
		$header_params = array("ref_id" => $_GET["ref_id"],
			"cmd" => "exportList", "cmdClass" => strtolower(get_class($this)));
		$tbl->setHeaderVars($cols, $header_params);
		$tbl->setColumnWidth(array("1%", "9%", "40%", "25%", "25%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);		// ???
		$tbl->disable("sort");


		$this->tpl->setVariable("COLUMN_COUNTS", 5);

		// delete button
		$this->tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "confirmDeleteExportFile");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("delete"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "downloadExportFile");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("download"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", "publishExportFile");
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("cont_public_access"));
		$this->tpl->parseCurrentBlock();

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		//$tbl->disable("footer");

		$tbl->setMaxCount(count($export_files));
		$export_files = array_slice($export_files, $_GET["offset"], $_GET["limit"]);
		$tbl->render();
		if(count($export_files) > 0)
		{
			$i=0;
			foreach($export_files as $exp_file)
			{
				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("TXT_FILENAME", $exp_file["file"]);

				$css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
				$this->tpl->setVariable("CSS_ROW", $css_row);

				$this->tpl->setVariable("TXT_SIZE", $exp_file["size"]);
				$public_str = ($exp_file["file"] == $this->object->getPublicExportFile($exp_file["type"]))
					? " <b>(".$this->lng->txt("public").")<b>"
					: "";
				$this->tpl->setVariable("TXT_TYPE", $exp_file["type"].$public_str);
				$this->tpl->setVariable("CHECKBOX_ID", $exp_file["type"].":".$exp_file["file"]);

				$file_arr = explode("__", $exp_file["file"]);
				$this->tpl->setVariable("TXT_DATE", date("Y-m-d H:i:s",$file_arr[0]));

				$this->tpl->parseCurrentBlock();
			}
		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", 4);
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->parseCurrentBlock();
	}

	/*
	* view last export log
	*/
	function viewExportLog()
	{
		global $tree;

		$this->setTabs();

		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");
		
		$this->exportMenu();

		// load files templates
		$this->tpl->setVariable("ADM_CONTENT",
			nl2br(file_get_contents($this->object->getExportDirectory()."/export.log")));

		$this->tpl->parseCurrentBlock();
	}

	/**
	* download export file
	*/
	function downloadExportFile()
	{
		if(!isset($_POST["file"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		if (count($_POST["file"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		$file = explode(":", $_POST["file"][0]);
		$export_dir = $this->object->getExportDirectory($file[0]);
		ilUtil::deliverFile($export_dir."/".$file[1],
			$file[1]);
	}

	/**
	* download export file
	*/
	function publishExportFile()
	{
		if(!isset($_POST["file"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if (count($_POST["file"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}
		
		$file = explode(":", $_POST["file"][0]);
		$export_dir = $this->object->getExportDirectory($file[0]);
		
		if ($this->object->getPublicExportFile($file[0]) ==
			$file[1])
		{
			$this->object->setPublicExportFile($file[0], "");
		}
		else
		{
			$this->object->setPublicExportFile($file[0], $file[1]);
		}
		$this->object->update();
		$this->ctrl->redirect($this, "exportList");
	}

	/**
	* download export file
	*/
	function downloadPDFFile()
	{
		if(!isset($_POST["file"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		if (count($_POST["file"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}


		$export_dir = $this->object->getOfflineDirectory();
		ilUtil::deliverFile($export_dir."/".$_POST["file"][0],
			$_POST["file"][0]);
	}

	/**
	* confirmation screen for export file deletion
	*/
	function confirmDeleteExportFile()
	{
		if(!isset($_POST["file"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		$this->setTabs();

		// SAVE POST VALUES
		$_SESSION["ilExportFiles"] = $_POST["file"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm_deletion.html", true);

		sendInfo($this->lng->txt("info_delete_sure"));

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		// BEGIN TABLE HEADER
		$this->tpl->setCurrentBlock("table_header");
		$this->tpl->setVariable("TEXT",$this->lng->txt("objects"));
		$this->tpl->parseCurrentBlock();

		// BEGIN TABLE DATA
		$counter = 0;
		foreach($_POST["file"] as $file)
		{
				$file = explode(":", $file);
				$this->tpl->setCurrentBlock("table_row");
				$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
				$this->tpl->setVariable("TEXT_CONTENT", $file[1]." (".$file[0].")");
				$this->tpl->parseCurrentBlock();
		}

		// cancel/confirm button
		$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$buttons = array( "cancelDeleteExportFile"  => $this->lng->txt("cancel"),
			"deleteExportFile"  => $this->lng->txt("confirm"));
		foreach ($buttons as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
	}


	/**
	* cancel deletion of export files
	*/
	function cancelDeleteExportFile()
	{
		session_unregister("ilExportFiles");
		$this->ctrl->redirect($this, "exportList");
	}


	/**
	* delete export files
	*/
	function deleteExportFile()
	{
		foreach($_SESSION["ilExportFiles"] as $file)
		{
			$file = explode(":", $file);
			$export_dir = $this->object->getExportDirectory($file[0]);
			
			$exp_file = $export_dir."/".$file[1];
			$exp_dir = $export_dir."/".substr($file[1], 0, strlen($file[1]) - 4);
			if (@is_file($exp_file))
			{
				unlink($exp_file);
			}
			if (@is_dir($exp_dir))
			{
				ilUtil::delDir($exp_dir);
			}
		}
		$this->ctrl->redirect($this, "exportList");
	}

	/**
	* confirm screen for tree fixing
	*
	*/
	function fixTreeConfirm()
	{
		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm.html");

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		//
		$this->tpl->setVariable("TXT_CONFIRM", $this->lng->txt("confirmation"));
		$this->tpl->setVariable("TXT_CONTENT", $this->lng->txt("cont_fix_tree_confirm"));
		$this->tpl->setVariable("CMD_CANCEL", "cancelFixTree");
		$this->tpl->setVariable("CMD_OK", "fixTree");
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_OK", $this->lng->txt("cont_fix_tree"));
	}

	/**
	* cancel tree fixing
	*/
	function cancelFixTree()
	{
		$this->ctrl->redirect($this, "properties");
	}

	/**
	* fix tree
	*/
	function fixTree()
	{
		$this->object->fixTree();
		sendInfo($this->lng->txt("cont_tree_fixed"), true);
		$this->ctrl->redirect($this, "properties");
	}

	/**
	* get lm menu html
	*/
	function setilLMMenu($a_offline = false, $a_export_format = "")
	{
		if (!$this->object->isActiveLMMenu())
		{
			return "";
		}

		include_once("./classes/class.ilTemplate.php");

		$tpl_menu =& new ilTemplate("tpl.lm_menu.html", true, true, true);
		$tpl_menu->setCurrentBlock("lm_menu_btn");

		// Determine whether the view of a learning resource should
		// be shown in the frameset of ilias, or in a separate window.
		$showViewInFrameset = $this->ilias->ini->readVariable("layout","view_target") == "frame";

		if ($showViewInFrameset)
		{
			$buttonTarget = "bottom";
		}
		else
		{
			$buttonTarget = "_top";
		}

		if ($a_export_format == "scorm")
		{
			$buttonTarget = "";
		}

		if ($this->object->isActiveTOC())
		{
			if (!$a_offline)
			{
				$tpl_menu->setVariable("BTN_LINK", ILIAS_HTTP_PATH."/content/lm_presentation.php?cmd=showTableOfContents&ref_id="
									   .$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
			}
			else
			{
				$tpl_menu->setVariable("BTN_LINK", "./table_of_contents.html");
			}
			$tpl_menu->setVariable("BTN_TXT", $this->lng->txt("cont_contents"));
                        $tpl_menu->setVariable("BTN_TARGET", $buttonTarget);
			$tpl_menu->parseCurrentBlock();
		}

		// print view
		if ($this->object->isActivePrintView())
		{
			if (!$a_offline)		// has to be implemented for offline mode
			{
				$tpl_menu->setVariable("BTN_LINK", ILIAS_HTTP_PATH."/content/lm_presentation.php?cmd=showPrintViewSelection&ref_id="
									   .$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
				$tpl_menu->setVariable("BTN_TXT", $this->lng->txt("cont_print_view"));
				$tpl_menu->setVariable("BTN_TARGET", $buttonTarget);
				$tpl_menu->parseCurrentBlock();
			}
		}

		// download
		if ($this->object->isActiveDownloads() && !$a_offline)
		{
			$tpl_menu->setVariable("BTN_LINK", ILIAS_HTTP_PATH."/content/lm_presentation.php?cmd=showDownloadList&ref_id="
								   .$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
			$tpl_menu->setVariable("BTN_TXT", $this->lng->txt("download"));
						$tpl_menu->setVariable("BTN_TARGET", $buttonTarget);
			$tpl_menu->parseCurrentBlock();
		}

		// get user defined menu entries
		$this->__initLMMenuEditor();
		$entries = $this->lmme_obj->getMenuEntries(true);

		if (count($entries) > 0)
		{
			foreach ($entries as $entry)
			{
				// build goto-link for internal resources
				if ($entry["type"] == "intern")
				{
					$entry["link"] = ILIAS_HTTP_PATH."/goto.php?target=".$entry["link"];
				}

				// add http:// prefix if not exist
				if (!strstr($entry["link"],'http://'))
				{
					$entry["link"] = "http://".$entry["link"];
				}

				$tpl_menu->setVariable("BTN_LINK", $entry["link"]);
				$tpl_menu->setVariable("BTN_TXT", $entry["title"]);
				//$tpl_menu->setVariable("BTN_TARGET", $buttonTarget);
				$tpl_menu->setVariable("BTN_TARGET", "_blank");
				$tpl_menu->parseCurrentBlock();
			}
		}

		// close button
		if(!$showViewInFrameset and
		   $this->object->isActiveLMMenu())
		{
			$tpl_menu->setCurrentBlock("js_close");
			$tpl_menu->setVariable("JS_BTN_TXT",$this->lng->txt('close'));
			$tpl_menu->parseCurrentBlock();
		}

		// Jump to...
		/*
		$tpl_menu->setCurrentBlock("jump");
		$tpl_menu->setVariable("JUMP_TXT", $this->lng->txt("jump_to"));
		$tpl_menu->parseCurrentBlock();
		*/

		return $tpl_menu->get();
	}

	/**
	* export content object
	*/
	function createPDF()
	{
		require_once("content/classes/class.ilContObjectExport.php");
		$cont_exp = new ilContObjectExport($this->object, "pdf");
		$cont_exp->buildExportFile();
		$this->offlineList();
	}

	/**
	* create html package
	*/
	function exportHTML()
	{
		require_once("content/classes/class.ilContObjectExport.php");
		$cont_exp = new ilContObjectExport($this->object, "html");
		$cont_exp->buildExportFile();
//echo $this->tpl->get();
		$this->ctrl->redirect($this, "exportList");
	}

	/**
	* create scorm package
	*/
	function exportSCORM()
	{
		require_once("content/classes/class.ilContObjectExport.php");
		$cont_exp = new ilContObjectExport($this->object, "scorm");
		$cont_exp->buildExportFile();
//echo $this->tpl->get();
		$this->ctrl->redirect($this, "exportList");
	}

	/**
	* display locator
	*/
	function addLocations()
	{
		global $lng;

		$obj_id = $_GET["obj_id"];
		$tree =& $this->object->getTree();

		if (($obj_id != 0) && $tree->isInTree($obj_id))
		{
			$path = $tree->getPathFull($obj_id);
		}
		else
		{
			$path = $tree->getPathFull($tree->getRootId());
			if ($obj_id != 0)
			{
				$path[] = array("type" => "pg", "child" => $this->obj_id,
					"title" => ilLMPageObject::_getPresentationTitle($this->obj_id));
			}
		}

		$modifier = 1;

		foreach ($path as $key => $row)
		{
			if ($row["child"] == 1)
			{
				$this->ctrl->setParameter($this, "obj_id", "");
				$this->ctrl->addLocation(
					$this->object->getTitle(),
					$this->ctrl->getLinkTarget($this, "properties"));
			}
			else
			{
				$title = $row["title"];
				switch($row["type"])
				{
					case "st":
						$this->ctrl->setParameterByClass("ilstructureobjectgui", "obj_id", $row["child"]);
						$this->ctrl->addLocation(
							$title,
							$this->ctrl->getLinkTargetByClass("ilstructureobjectgui", "view"));
						break;

					case "pg":
						$this->ctrl->setParameterByClass("illmpageobjectgui", "obj_id", $row["child"]);
						$this->ctrl->addLocation(
							$title,
							$this->ctrl->getLinkTargetByClass("illmpageobjectgui", "view"));
						break;
				}
			}
		}
		$this->ctrl->setParameter($this, "obj_id", $_GET["obj_id"]);
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
		$this->tpl->setCurrentBlock("header_image");
		$this->tpl->setVariable("IMG_HEADER", ilUtil::getImagePath("icon_lm_b.gif"));
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("content");
		$this->tpl->setVariable("TABS", $tabs_gui->getHTML());
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
	}

	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		// back to upper context
		//$tabs_gui->getTargetsByObjectType($this, $this->object->getType());

		// properties
		$tabs_gui->addTarget("properties",
			$this->ctrl->getLinkTarget($this,'properties'),
			"properties", get_class($this));

		// meta data
		$tabs_gui->addTarget("meta_data",
			$this->ctrl->getLinkTargetByClass('ilmdeditorgui',''),
			"meta_data", "ilmdeditorgui");

		// chapters
		$tabs_gui->addTarget("cont_chapters",
			$this->ctrl->getLinkTarget($this, "chapters"),
			"chapters", get_class($this));

		// pages
		$tabs_gui->addTarget("cont_all_pages",
			$this->ctrl->getLinkTarget($this, "pages"),
			"pages", get_class($this));

		if ($this->object->getType() == "lm")
		{
			// export
			$tabs_gui->addTarget("export",
				$this->ctrl->getLinkTarget($this, "exportList"),
				"exportList", get_class($this));
	
			// link checker
			$tabs_gui->addTarget("link_check",
				$this->ctrl->getLinkTarget($this, "linkChecker"),
				"linkChecker", get_class($this));
		}
		else
		{
			// bibliographical data
			$tabs_gui->addTarget("bib_data",
				$this->ctrl->getLinkTarget($this, "editBibItem"),
				"editBibItem", get_class($this));
		}

		// permissions
		$tabs_gui->addTarget("perm_settings",
			$this->ctrl->getLinkTarget($this, "perm"),
			"perm", get_class($this));

	}

	function editPublicSection()
	{
		$this->setTabs();

		switch ($this->object->getType())
		{
			case "lm":
				$gui_class = "ilobjlearningmodulegui";
				break;

			case "dlb":
				$gui_class = "ilobjdlbookgui";
				break;
		}

		// get learning module object
		$this->lm_obj =& new ilObjLearningModule($this->ref_id, true);

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_public_selector.html");

		require_once ("content/classes/class.ilPublicSectionSelector.php");
		$exp = new ilPublicSectionSelector($this->ctrl->getLinkTarget($this, "view"),
			$this->object, $gui_class);

		$exp->setTargetGet("obj_id");

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();
		
		// get page ids
		foreach ($exp->format_options as $node)
		{
			if (!$node["container"])
			{
				$pages[] = $node["child"];
			}
		}
		
		$js_pages = ilUtil::array_php2js($pages);

		//$this->tpl->setCurrentBlock("content");
		//var_dump($this->object->getPublicAccessMode());
		// access mode selector
		$this->tpl->setVariable("TXT_SET_PUBLIC_MODE", $this->lng->txt("set_public_mode"));
		$this->tpl->setVariable("TXT_CHOOSE_PUBLIC_MODE", $this->lng->txt("choose_public_mode"));
		$modes = array("complete" => $this->lng->txt("all_pages"), "selected" => $this->lng->txt("selected_pages_only"));
		$select_public_mode = ilUtil::formSelect ($this->object->getPublicAccessMode(),"lm_public_mode",$modes, false, true);
		$this->tpl->setVariable("SELECT_PUBLIC_MODE", $select_public_mode);

		$this->tpl->setVariable("TXT_EXPLORER_HEADER", $this->lng->txt("choose_public_pages"));
		$this->tpl->setVariable("EXP_REFRESH", $this->lng->txt("refresh"));
		$this->tpl->setVariable("EXPLORER",$output);
		$this->tpl->setVariable("ONCLICK",$js_pages);
		$this->tpl->setVariable("TXT_CHECKALL", $this->lng->txt("check_all"));
		$this->tpl->setVariable("TXT_UNCHECKALL", $this->lng->txt("uncheck_all"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getLinkTarget($this, "savePublicSection"));
		$this->tpl->parseCurrentBlock();
	}
	
	function savePublicSection()
	{
		//var_dump($_POST["lm_public_mode"]);exit;
		$this->object->setPublicAccessMode($_POST["lm_public_mode"]);
		$this->object->updateProperties();
		ilLMObject::_writePublicAccessStatus($_POST["pages"],$this->object->getId());
		sendInfo($this->lng->txt("msg_obj_modified"), true);
		$this->ctrl->redirect($this, "editPublicSection");
	}

	function linkChecker()
	{
		global $ilias,$ilUser;

		$this->__initLinkChecker();

		$invalid_links = $this->link_checker_obj->getInvalidLinksFromDB();


		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.link_check.html", true);

		if($last_access = $this->link_checker_obj->getLastCheckTimestamp())
		{
			$this->tpl->setCurrentBlock("LAST_MODIFIED");
			$this->tpl->setVariable("AS_OF",$this->lng->txt('last_change').": ");
			$this->tpl->setVariable("LAST_CHECK",date('Y-m-d H:i:s',$last_access));
			$this->tpl->parseCurrentBlock();
		}


		$this->tpl->setVariable("F_ACTION",$this->ctrl->getFormAction($this));

		$this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_lm.gif'));
		$this->tpl->setVariable("ALT_IMG",$this->lng->txt('learning_module'));
		$this->tpl->setVariable("TITLE",$this->object->getTitle().' ('.$this->lng->txt('link_check').')');
		$this->tpl->setVariable("PAGE_TITLE",$this->lng->txt('cont_pg_title'));
		$this->tpl->setVariable("URL",$this->lng->txt('url'));
		$this->tpl->setVariable("OPTIONS",$this->lng->txt('edit'));

		if(!count($invalid_links))
		{
			$this->tpl->setCurrentBlock("no_invalid");
			$this->tpl->setVariable("TXT_NO_INVALID",$this->lng->txt('no_invalid_links'));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$counter = 0;
			foreach($invalid_links as $invalid)
			{
				$this->tpl->setCurrentBlock("invalid_row");
				$this->tpl->setVariable("ROW_COLOR",ilUtil::switchColor(++$counter,'tblrow1','tblrow2'));
				$this->tpl->setVariable("ROW_PAGE_TITLE",
										ilLMPageObject::_getPresentationTitle($invalid['page_id'],$this->object->getPageHeader()));
				$this->tpl->setVariable("ROW_URL",$invalid['url']);


				// EDIT IMAGE
				$this->ctrl->setParameterByClass('ilLMPageObjectGUI','obj_id',$invalid['page_id']);
				$this->tpl->setVariable("ROW_EDIT_LINK",$this->ctrl->getLinkTargetByClass('ilLMPageObjectGUI','view'));

				$this->tpl->setVariable("ROW_IMG",ilUtil::getImagePath('edit.gif'));
				$this->tpl->setVariable("ROW_ALT_IMG",$this->lng->txt('edit'));
				$this->tpl->parseCurrentBlock();
			}
		}
		if((bool) $ilias->getSetting('cron_link_check'))
		{
			include_once './classes/class.ilLinkCheckNotify.php';

			// Show message block
			$this->tpl->setCurrentBlock("MESSAGE_BLOCK");
			$this->tpl->setVariable("INFO_MESSAGE",$this->lng->txt('link_check_message_a'));
			$this->tpl->setVariable("CHECK_MESSAGE",ilUtil::formCheckbox(
										ilLinkCheckNotify::_getNotifyStatus($ilUser->getId(),$this->object->getId()),
										'link_check_message',
										1));
			$this->tpl->setVariable("INFO_MESSAGE_LONG",$this->lng->txt('link_check_message_b'));
			$this->tpl->parseCurrentBlock();

			// Show save button
			$this->tpl->setCurrentBlock("CRON_ENABLED");
			$this->tpl->setVariable("DOWNRIGHT_IMG",ilUtil::getImagePath('arrow_downright.gif'));
			$this->tpl->setVariable("BTN_SUBMIT_LINK_CHECK",$this->lng->txt('save'));
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setVariable("BTN_REFRESH",$this->lng->txt('refresh'));

		return true;

	}
	function saveLinkCheck()
	{
		global $ilDB,$ilUser;

		include_once './classes/class.ilLinkCheckNotify.php';

		$link_check_notify =& new ilLinkCheckNotify($ilDB);
		$link_check_notify->setUserId($ilUser->getId());
		$link_check_notify->setObjId($this->object->getId());

		if($_POST['link_check_message'])
		{
			sendInfo($this->lng->txt('link_check_message_enabled'));
			$link_check_notify->addNotifier();
		}
		else
		{
			sendInfo($this->lng->txt('link_check_message_disabled'));
			$link_check_notify->deleteNotifier();
		}
		$this->linkChecker();

		return true;
	}
		


	function refreshLinkCheck()
	{
		$this->__initLinkChecker();

		if(!$this->link_checker_obj->checkPear())
		{
			sendInfo($this->lng->txt('missing_pear_library'));
			$this->linkChecker();

			return false;
		}

		$this->link_checker_obj->checkLinks();
		sendInfo($this->lng->txt('link_checker_refreshed'));

		$this->linkChecker();

		return true;
	}

	function __initLinkChecker()
	{
		global $ilDB;

		include_once './classes/class.ilLinkChecker.php';

		$this->link_checker_obj =& new ilLinkChecker($ilDB,false);
		$this->link_checker_obj->setObjId($this->object->getId());

		return true;
	}
	
	function __initLMMenuEditor()
	{
		include_once './content/classes/class.ilLMMenuEditor.php';

		$this->lmme_obj =& new ilLMMenuEditor();
		$this->lmme_obj->setObjId($this->object->getId());

		return true;
	}

	/**
	* display add menu entry form
	*/
	function addMenuEntry()
	{
		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_menu_entry_form.html",true);
		
		if (isset($_GET["link_ref_id"]))
		{
			$obj_type = ilObject::_lookupType($_GET["link_ref_id"],true);
			$obj_id = ilObject::_lookupObjectId($_GET["link_ref_id"]);
			$title = ilObject::_lookupTitle($obj_id);
			
			$target_link = $obj_type."_".$_GET["link_ref_id"];
			$this->tpl->setVariable("TITLE", $title);
			$this->tpl->setVariable("TARGET", $target_link);
			$this->tpl->setVariable("LINK_REF_ID", $_GET["link_ref_id"]);
		}

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_NEW_ENTRY", $this->lng->txt("lm_menu_new_entry"));
		$this->tpl->setVariable("TXT_TARGET", $this->lng->txt("lm_menu_entry_target"));
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("lm_menu_entry_title"));
		$this->tpl->setVariable("BTN_NAME", "saveMenuEntry");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->setVariable("BTN_NAME2", "showEntrySelector");
		$this->tpl->setVariable("BTN_TEXT2", $this->lng->txt("lm_menu_select_internal_object"));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* save new menu entry
	*/
	function saveMenuEntry()
	{
		// check title and target
		if (empty($_POST["title"]))
		{
			$this->ilias->raiseError($this->lng->txt("please_enter_title"),$this->ilias->error_obj->MESSAGE);
		}
		if (empty($_POST["target"]))
		{
			$this->ilias->raiseError($this->lng->txt("please_enter_target"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->__initLMMenuEditor();
		$this->lmme_obj->setTitle($_POST["title"]);
		$this->lmme_obj->setTarget($_POST["target"]);
		$this->lmme_obj->setLinkRefId($_POST["link_ref_id"]);
		
		if ($_POST["link_ref_id"])
		{
			$this->lmme_obj->setLinkType("intern");
		}
		
		$this->lmme_obj->create();

		sendInfo($this->lng->txt("msg_entry_added"), true);
		$this->ctrl->redirect($this, "properties");
	}
	
	/**
	* drop a menu entry
	*/
	function deleteMenuEntry()
	{
		if (empty($_GET["menu_entry"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_menu_entry_id"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->__initLMMenuEditor();
		$this->lmme_obj->delete($_GET["menu_entry"]);

		sendInfo($this->lng->txt("msg_entry_removed"), true);
		$this->ctrl->redirect($this, "properties");
	}
	
	/**
	* edit menu entry form
	*/
	function editMenuEntry()
	{
		if (empty($_GET["menu_entry"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_menu_entry_id"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->__initLMMenuEditor();
		$this->lmme_obj->readEntry($_GET["menu_entry"]);

		$this->setTabs();

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_menu_entry_form.html",true);

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_NEW_ENTRY", $this->lng->txt("lm_menu_edit_entry"));
		$this->tpl->setVariable("TXT_TARGET", $this->lng->txt("lm_menu_entry_target"));
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("lm_menu_entry_title"));
		$this->tpl->setVariable("TITLE", $this->lmme_obj->getTitle());
		$this->tpl->setVariable("TARGET", $this->lmme_obj->getTarget());
		$this->tpl->setVariable("ENTRY_ID", $this->lmme_obj->getEntryId());
		$this->tpl->setVariable("BTN_NAME", "updateMenuEntry");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->setVariable("BTN_NAME2", "showEntrySelector");
		$this->tpl->setVariable("BTN_TEXT2", $this->lng->txt("lm_menu_select_internal_object"));
		$this->tpl->parseCurrentBlock();
	}
	
	/**
	* update a menu entry
	*/
	function updateMenuEntry()
	{
		if (empty($_POST["menu_entry"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_menu_entry_id"),$this->ilias->error_obj->MESSAGE);
		}
		
		// check title and target
		if (empty($_POST["title"]))
		{
			$this->ilias->raiseError($this->lng->txt("please_enter_title"),$this->ilias->error_obj->MESSAGE);
		}
		if (empty($_POST["target"]))
		{
			$this->ilias->raiseError($this->lng->txt("please_enter_target"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->__initLMMenuEditor();
		$this->lmme_obj->readEntry($_POST["menu_entry"]);
		$this->lmme_obj->setTitle($_POST["title"]);
		$this->lmme_obj->setTarget($_POST["target"]);
		$this->lmme_obj->update();

		sendInfo($this->lng->txt("msg_entry_updated"), true);
		$this->ctrl->redirect($this, "properties");
	}
	
	function showEntrySelector()
	{
		$this->setTabs();
		
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.lm_menu_object_selector.html",true);

		sendInfo($this->lng->txt("lm_menu_select_object_to_add"));
		
		require_once ("content/classes/class.ilLMMenuObjectSelector.php");
		$exp = new ilLMMenuObjectSelector($this->ctrl->getLinkTarget($this,'test'),$this);

		$exp->setExpand($_GET["lm_menu_expand"] ? $_GET["lm_menu_expand"] : $this->tree->readRootId());
		$exp->setExpandTarget($this->ctrl->getLinkTarget($this,'showEntrySelector'));
		$exp->setTargetGet("ref_id");
		$exp->setRefId($this->cur_ref_id);
		
		$sel_types = array('lm','dbk','glo','frm','exc','tst','svy');
		$exp->setSelectableTypes($sel_types);

		//$exp->setTargetGet("obj_id");

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();
		
		// get page ids
		foreach ($exp->format_options as $node)
		{
			if (!$node["container"])
			{
				$pages[] = $node["child"];
			}
		}
		
		//$this->tpl->setCurrentBlock("content");
		//var_dump($this->object->getPublicAccessMode());
		// access mode selector
		$this->tpl->setVariable("TXT_SET_PUBLIC_MODE", $this->lng->txt("set_public_mode"));
		$this->tpl->setVariable("TXT_CHOOSE_PUBLIC_MODE", $this->lng->txt("choose_public_mode"));
		$modes = array("complete" => $this->lng->txt("all_pages"), "selected" => $this->lng->txt("selected_pages_only"));
		$select_public_mode = ilUtil::formSelect ($this->object->getPublicAccessMode(),"lm_public_mode",$modes, false, true);
		$this->tpl->setVariable("SELECT_PUBLIC_MODE", $select_public_mode);

		$this->tpl->setVariable("TXT_EXPLORER_HEADER", $this->lng->txt("choose_public_pages"));
		$this->tpl->setVariable("EXP_REFRESH", $this->lng->txt("refresh"));
		$this->tpl->setVariable("EXPLORER",$output);
		$this->tpl->setVariable("ONCLICK",$js_pages);
		$this->tpl->setVariable("TXT_CHECKALL", $this->lng->txt("check_all"));
		$this->tpl->setVariable("TXT_UNCHECKALL", $this->lng->txt("uncheck_all"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getLinkTarget($this, "savePublicSection"));
		$this->tpl->parseCurrentBlock();
	}
} // END class.ilObjContentObjectGUI
?>
