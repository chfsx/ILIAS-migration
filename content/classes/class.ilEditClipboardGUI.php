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

require_once("content/classes/Media/class.ilObjMediaObjectGUI.php");

/**
* Class ilEditClipboardGUI
*
* Clipboard for editing
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilEditClipboardGUI
{
	var $ilias;
	var $tpl;
	var $lng;
	var $ctrl;

	/**
	* Constructor
	* @access	public
	*/
	function ilEditClipboardGUI()
	{
		global $ilias, $tpl, $lng, $ilCtrl;

		$this->ctrl =& $ilCtrl;
		$this->ilias =& $ilias;
		$this->tpl =& $tpl;
		$this->lng =& $lng;
		if ($_GET["returnCommand"] != "")
		{
			$this->mode = "getObject";
		}
		else
		{
			$this->mode = "";
		}

		$this->ctrl->saveParameter($this, array("clip_mob_id", "returnCommand"));
	}

	/**
	* get all gui classes that are called from this one (see class ilCtrl)
	*
	* @param	array		array of gui classes that are called
	*/
	function _forwards()
	{
		return array("ilObjMediaObjectGUI");
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		// get next class that processes or forwards current command
		$next_class = $this->ctrl->getNextClass($this);

		// get current command
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			case "ilobjmediaobjectgui":
//echo "<br>calling objmediaomjgui";
				$this->ctrl->setReturn($this, "view");
				require_once("classes/class.ilTabsGUI.php");
				$tabs_gui =& new ilTabsGUI();
				$mob_gui =& new ilObjMediaObjectGUI("", $_GET["clip_mob_id"],false, false);
				//$mob_gui->getTabs($tabs_gui);
				$mob_gui->setAdminTabs();
				//$this->tpl->setVariable("TABS", $tabs_gui->getHTML());
				$ret =& $mob_gui->executeCommand();
				break;


			default:
				$ret =& $this->$cmd();
				break;
		}

		return $ret;
	}


	/*
	* display clipboard content
	*/
	function view()
	{
		global $tree;

		$this->setTabs();

		include_once "./classes/class.ilTableGUI.php";
//echo ":".$_GET["returnCommand"].":";
		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");

		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.clipboard_tbl_row.html", true);

		$num = 0;

		$obj_str = ($this->call_by_reference) ? "" : "&obj_id=".$this->obj_id;
		if ($this->mode == "getObject")
		{
			$this->ctrl->setParameter($this, "returnCommand",
				rawurlencode($_GET["returnCommand"]));
		}
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		// create table
		$tbl = new ilTableGUI();

		// title & header columns
		$tbl->setTitle($this->lng->txt("objs_mob"));
		//$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));

		$tbl->setHeaderNames(array("", $this->lng->txt("cont_object")));

		$cols = array("", "object");
		$header_params = array("ref_id" => $_GET["ref_id"], "obj_id" => $_GET["obj_id"],
			"cmd" => "clipboard");
		$tbl->setHeaderVars($cols, $header_params);
		$tbl->setColumnWidth(array("1%","99%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);		// ???
		//$tbl->setMaxCount(30);		// ???

		$this->tpl->setVariable("COLUMN_COUNTS", 2);

		$this->tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		if ($this->mode != "getObject")
		{
			// delete button
			$this->tpl->setCurrentBlock("tbl_action_btn");
			$this->tpl->setVariable("BTN_NAME", "remove");
			$this->tpl->setVariable("BTN_VALUE", "remove");
			$this->tpl->parseCurrentBlock();

			// add list
			/*
			$opts = ilUtil::formSelect("","new_type",array("mob" => "mob"));
			$this->tpl->setCurrentBlock("add_object");
			$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
			$this->tpl->setVariable("BTN_NAME", "createMediaInClipboard");
			$this->tpl->setVariable("TXT_ADD", $this->lng->txt("add"));
			$this->tpl->parseCurrentBlock();*/
		}
		else
		{
			// insert button
			$this->tpl->setCurrentBlock("tbl_action_btn");
			$this->tpl->setVariable("BTN_NAME", "insert");
			$this->tpl->setVariable("BTN_VALUE", "insert");
			$this->tpl->parseCurrentBlock();
		}

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		//$tbl->disable("footer");

		$objs = $this->ilias->account->getClipboardObjects("mob");
		$objs = ilUtil::sortArray($objs, $_GET["sort_by"], $_GET["sort_order"]);
		$objs = array_slice($objs, $_GET["offset"], $_GET["limit"]);
		$tbl->setMaxCount(count($objs));

		$tbl->render();
		if(count($objs) > 0)
		{
			$i=0;
			foreach($objs as $obj)
			{
				if ($this->mode != "getObject")
				{
					$this->tpl->setCurrentBlock("edit");
					$this->ctrl->setParameter($this, "clip_mob_id", $obj["id"]);
					$this->tpl->setVariable("EDIT_LINK",
						$this->ctrl->getLinkTargetByClass("ilObjMediaObjectGUI", "edit",
							array("ilEditClipboardGUI")));
					$this->tpl->setVariable("TEXT_OBJECT", $obj["title"].
						" [".$obj["id"]."]");
					$this->tpl->parseCurrentBlock();
				}
				else
				{
					$this->tpl->setCurrentBlock("show");
					$this->tpl->setVariable("TEXT_OBJECT2", $obj["title"].
						" [".$obj["id"]."]");
					$this->tpl->parseCurrentBlock();
				}

				$this->tpl->setCurrentBlock("tbl_content");
				$css_row = ilUtil::switchColor($i++,"tblrow1","tblrow2");
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->setVariable("CHECKBOX_ID", $obj["id"]);
				$this->tpl->parseCurrentBlock();
			}
		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", $num);
			$this->tpl->parseCurrentBlock();
		}

	}


	/**
	* get Object
	*/
	function getObject()
	{
		$this->mode = "getObject";
		$this->view();
	}


	/**
	* remove item from clipboard
	*/
	function remove()
	{
		// check number of objects
		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		foreach($_POST["id"] AS $obj_id)
		{
			$this->ilias->account->removeObjectFromClipboard($obj_id, "mob");
			include_once("content/classes/Media/class.ilObjMediaObject.php");
			$mob = new ilObjMediaObject($obj_id);
			$mob->delete();			// this method don't delete, if mob is used elsewhere
		}
		$this->ctrl->redirect($this, "view");
	}

	/**
	* insert
	*/
	function insert()
	{
		// check number of objects
		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		if(count($_POST["id"]) > 1)
		{
			$this->ilias->raiseError($this->lng->txt("cont_select_max_one_item"),$this->ilias->error_obj->MESSAGE);
		}

		ilUtil::redirect(ilUtil::appendUrlParameterString(
			$_GET["returnCommand"], "clip_obj_type=mob&clip_obj_id=".$_POST["id"][0]));


	}

	/**
	* create new medi object in clipboard
	*/
	function createMediaInClipboard()
	{
		require_once ("content/classes/Pages/class.ilPCMediaObjectGUI.php");
		$mob_gui =& new ilPCMediaObjectGUI($this->obj, $this->lm_obj);
		$mob_gui->setTargetScript("lm_edit.php?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
		$mob_gui->insert("post", "saveMediaInClipboard");
	}

	/**
	* save new media object in clipboard
	*/
	function saveMediaInClipboard()
	{
		require_once ("content/classes/Pages/class.ilPCMediaObjectGUI.php");
		$mob_gui =& new ilPCMediaObjectGUI($this->obj, $this->lm_obj);
		$mob =& $mob_gui->create(false);
		$this->ilias->account->addObjectToClipboard($mob->getId(), "mob", $mob->getTitle());
		$this->clipboard();
	}

	/**
	* output tabs
	*/
	function setTabs()
	{
		$this->tpl->setVariable("HEADER", $this->lng->txt("clipboard"));

		// catch feedback message
		include_once("classes/class.ilTabsGUI.php");
		$tabs_gui =& new ilTabsGUI();
		$this->getTabs($tabs_gui);
		$this->tpl->setVariable("TABS", $tabs_gui->getHTML());
	}

	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		if ($this->mode == "getObject")
		{
			// back to upper context
			$tabs_gui->addTarget("cont_back",
				$_GET["returnCommand"], "",
				"");
		}
		else
		{
			// back to upper context
			$tabs_gui->addTarget("cont_back",
				$this->ctrl->getParentReturn($this), "",
				"");
		}
	}

}
?>
