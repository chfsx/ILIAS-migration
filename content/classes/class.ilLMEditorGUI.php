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

require_once ("content/classes/class.ilLMObjectFactory.php");
//require_once ("classes/class.ilObjLearningModule.php");
require_once ("content/classes/class.ilLearningModule.php");
require_once ("content/classes/class.ilPageObjectGUI.php");
require_once ("content/classes/class.ilStructureObjectGUI.php");
require_once ("content/classes/class.ilLearningModuleGUI.php");
require_once ("content/classes/class.ilMetaDataGUI.php");

/**
* GUI class for learning module editor
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilLMEditorGUI
{
	/**
	* ilias object
	* @var object ilias
	* @access public
	*/
	var $ilias;
	var $tpl;
	var $lng;
	var $objDefinition;
	var $lm_id;
	var $lm_obj;

	var $tree;
	var $obj_id;

	/**
	* Constructor
	* @access	public
	*/
	function ilLMEditorGUI()
	{
		global $ilias, $tpl, $lng, $objDefinition;

		// initiate variables
		$this->ilias =& $ilias;
		$this->tpl =& $tpl;
		$this->lng =& $lng;
		$this->objDefinition = $objDefinition;
		$this->lm_id = $_GET["lm_id"];
		$this->obj_id = $_GET["obj_id"];

		$hier_id = $_GET["hier_id"];		// Position of content object (starting with 1)
		if(isset($_POST["new_hier_id"]))
		{
			$hier_id = $_POST["new_hier_id"];
		}

		$cmd = (empty($_GET["cmd"]))
			? "frameset"
			: $_GET["cmd"];

		$new_type = (isset($_GET["new_type"]))
			? $_GET["new_type"]
			: $_POST["new_type"];

		if ($cmd == "post")
		{
			$cmd = key($_POST["cmd"]);
		}

		if ($cmd == "edpost")
		{
			$type = "content";
			if (isset($_GET["hier_id"]))
			{
				$cmd = key($_POST["cmd"]);
				$hier_id = $_GET["hier_id"];
			}
			else
			{
				$cmd = explode("_", key($_POST["cmd"]));
				unset($cmd[0]);
				$hier_id = implode($cmd, "_");
				$cmd = $_POST["command".$hier_id];
			}
		}

		switch($cmd)
		{
			case "explorer":
			case "frameset":
				$this->$cmd();
				break;

			default:
				$this->tree = new ilTree($_GET["lm_id"]);
				$this->tree->setTableNames('lm_tree','lm_data');
				$this->tree->setTreeTablePK("lm_id");
				$this->lm_obj =& new ilLearningModule($this->lm_id);

				if(!empty($_GET["obj_id"]))		// we got a page or structure object
				{
					$obj =& ilLMObjectFactory::getInstance($_GET["obj_id"]);
					$this->main_header($this->lng->txt($obj->getType()).": ".$obj->getTitle(),$obj->getType());
					if($type != "content")
					{
						$type = ($cmd == "create" || $cmd == "save")
							? $new_type
							: $obj->getType();
					}
					else
					{
						$obj->buildDom();
						$obj->addHierIDs();
				 		$cont_obj =& $obj->getContentObject($hier_id);
						//$cont_obj =& $content[$hier_id - 1];
						$com = explode("_", $cmd);
						$cmd = $com[0];
						$ctype = ($cmd == "insert" || $cmd == "create")
							? $com[1]
							: $cont_obj->getType();
					}
				}
				else		// command belongs to learning module
				{
					$this->main_header($this->lng->txt("lm").": ".$this->lm_obj->getTitle(),"lm");
					$type = ($cmd == "create" || $cmd == "save")
							? $new_type
							: "lm";
				}
//echo "type:$type:cmd:$cmd:ctype:$ctype:";
				if($type == "content")
				{
					switch($ctype)
					{
						case "par":
							require_once ("content/classes/class.ilParagraphGUI.php");
							$par_gui =& new ilParagraphGUI($this->lm_obj, $obj, $cont_obj, $hier_id);
							$par_gui->$cmd();
							break;

						case "tab":
							require_once ("content/classes/class.ilLMTableGUI.php");
							$tab_gui =& new ilLMTableGUI($this->lm_obj, $obj, $cont_obj, $hier_id);
							$tab_gui->$cmd();
							break;

					}
				}
				else
				{
					switch ($type)
					{
						case "pg":
							$pg_gui =& new ilPageObjectGUI($this->lm_obj);
							$pg_gui->setPageObject($obj);
							$pg_gui->$cmd();
							break;

						case "st":
							$st_gui =& new ilStructureObjectGUI($this->lm_obj, $this->tree);
							$st_gui->setStructureObject($obj);
							$st_gui->$cmd();
							break;

						case "lm":
							$lm_gui =& new ilLearningModuleGUI($this->tree);
							$lm_gui->setLearningModuleObject($this->lm_obj);
							$lm_gui->$cmd();
							break;

						case "meta":
							$meta_gui =& new ilMetaDataGUI($obj->getMetaData());
							$meta_gui->setLMObject($this->lm_obj);
							$meta_gui->setObject($obj);
							$meta_gui->$cmd();
							break;

					}
				}
				$this->tpl->show();
				break;
		}
	}


	/**
	* output main frameset of editor
	* left frame: explorer tree of chapters
	* right frame: editor content
	*/
	function frameset()
	{
		$this->tpl = new ilTemplate("tpl.lm_edit_frameset.html", false, false, "content");
		$this->tpl->setVariable("LM_ID",$this->lm_id);
		$this->tpl->show();
	}

	/**
	* output explorer tree with bookmark folders
	*/
	function explorer()
	{
		$this->tpl = new ilTemplate("tpl.main.html", true, true);

		// get learning module object
		$this->lm_obj =& new ilLearningModule($this->lm_id);

		$path = (substr($this->tpl->tplPath,0,2) == "./") ?
			".".$this->tpl->tplPath :
			$this->tpl->tplPath;
		$this->tpl->setVariable("LOCATION_STYLESHEET", $path."/".$this->ilias->account->prefs["style"].".css");

		//$this->tpl = new ilTemplate("tpl.explorer.html", false, false);
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.explorer.html");

		require_once ("content/classes/class.ilLMEditorExplorer.php");
		$exp = new ilLMEditorExplorer("lm_edit.php?cmd=view&lm_id=".$this->lm_obj->getId(),$this->lm_obj);
		$exp->setTargetGet("obj_id");

		if ($_GET["mexpand"] == "")
		{
			$mtree = new ilTree($this->lm_id);
			$mtree->setTableNames('lm_tree','lm_data');
			$mtree->setTreeTablePK("lm_id");
			$expanded = $mtree->readRootId();
		}
		else
		{
			$expanded = $_GET["mexpand"];
		}

		$exp->setExpand($expanded);

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();

		$this->tpl->setCurrentBlock("content");
		$this->tpl->setVariable("EXPLORER",$output);
		$this->tpl->setVariable("ACTION", "lm_edit.php?cmd=explorer&lm_id=".$this->lm_id."&mexpand=".$_GET["mexpand"]);
		$this->tpl->parseCurrentBlock();
		$this->tpl->show();
	}


	/**
	* output main header (title and locator)
	*/
	function main_header($a_header_title, $a_type)
	{
		global $lng;

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->setVariable("HEADER", $a_header_title);
		$this->displayLocator();
		$this->setAdminTabs($a_type);
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
	* display locator
	*/
	function displayLocator()
	{
		global $lng;

		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");

		if(!empty($this->obj_id))
		{
			$path = $this->tree->getPathFull($this->obj_id);
		}
		else
		{
			$path = $this->tree->getPathFull($this->tree->getRootId());
		}

		$modifier = 1;

		foreach ($path as $key => $row)
		{
			if ($key < count($path)-$modifier)
			{
				$this->tpl->touchBlock("locator_separator");
			}

			$this->tpl->setCurrentBlock("locator_item");
			$title = ($row["child"] == 1) ?
				$this->lm_obj->getTitle() :
				$row["title"];
			$this->tpl->setVariable("ITEM", $title);
			$obj_str = ($row["child"] == 1)
				? ""
				: "&obj_id=".$row["child"];
			$this->tpl->setVariable("LINK_ITEM", "lm_edit.php?cmd=view&lm_id=".
				$this->lm_id.$obj_str);
			$this->tpl->parseCurrentBlock();

		}

		/*
		if (isset($_GET["obj_id"]))
		{
			$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($_GET["obj_id"]);

			$this->tpl->setCurrentBlock("locator_item");
			$this->tpl->setVariable("ITEM", $obj_data->getTitle());
			// TODO: SCRIPT NAME HAS TO BE VARIABLE!!!
			$this->tpl->setVariable("LINK_ITEM", "adm_object.php?ref_id=".$row["ref_id"]."&obj_id=".$_GET["obj_id"]);
			$this->tpl->parseCurrentBlock();
		}*/

		$this->tpl->setCurrentBlock("locator");

		$this->tpl->parseCurrentBlock();
	}

	function setAdminTabs($a_type)
	{
		$tabs = array();
		$this->tpl->addBlockFile("TABS", "tabs", "tpl.tabs.html");
		$d = $this->objDefinition->getProperties($a_type);

		foreach ($d as $key => $row)
		{
			$tabs[] = array($row["lng"], $row["name"]);
		}

		if (isset($_GET["obj_id"]))
		{
			$object_link = "&obj_id=".$_GET["obj_id"];
		}

		foreach ($tabs as $row)
		{
			$i++;

			if ($row[1] == $_GET["cmd"])
			{
				$tabtype = "tabactive";
				$tab = $tabtype;
			}
			else
			{
				$tabtype = "tabinactive";
				$tab = "tab";
			}

			$this->tpl->setCurrentBlock("tab");
			$this->tpl->setVariable("TAB_TYPE", $tabtype);
			$this->tpl->setVariable("TAB_TYPE2", $tab);
			$this->tpl->setVariable("IMG_LEFT", ilUtil::getImagePath("eck_l.gif"));
			$this->tpl->setVariable("IMG_RIGHT", ilUtil::getImagePath("eck_r.gif"));
			$this->tpl->setVariable("TAB_LINK", "lm_edit.php?lm_id=".$_GET["lm_id"]."&obj_id=".
				$_GET["obj_id"]."&cmd=".$row[1]);
			$this->tpl->setVariable("TAB_TEXT", $this->lng->txt($row[0]));
			$this->tpl->parseCurrentBlock();
		}
	}


}
?>
