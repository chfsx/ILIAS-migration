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
require_once("include/inc.header.php");
require_once("classes/class.ilObjGroupGUI.php");
require_once("classes/class.ilGroupExplorer.php");
require_once("classes/class.ilTableGUI.php");
require_once("classes/class.ilTree.php");
require_once("classes/class.ilObjGroup.php");
/**
* Class ilGroupGUI
*
* GUI class for ilLearningModule
*
* @author Martin Rus <mrus@smail.uni-koeln.de>

* @version $Id$

*
* @package group
*/
class ilGroupGUI extends ilObjectGUI
{
	var $g_obj;
	var $g_tree;
	var $tpl;
	var $lng;
	var $objDefinition;
	var $tree;
	var $rbacsystem;
	var $ilias;
	var $object;
	var $grp_tree;
	/**
	* Constructor
	* @access	public
	*/

	function ilGroupGUI($a_data,$a_id,$a_call_by_reference)

	{
		global $tpl, $ilias, $lng, $tree, $rbacsystem, $objDefinition;

		$this->type ="grp";
		$this->ilias =& $ilias;
		$this->objDefinition =& $objDefinition;
		$this->tpl =& $tpl;
		$this->tree =& $tree;
		$this->formaction = array();
		$this->return_location = array();

		$this->data = $a_data;
		$this->id = $a_id;
		$this->call_by_reference = $a_call_by_reference;

		$this->ref_id = $_GET["ref_id"];
		$this->obj_id = $_GET["obj_id"];


		// get the object
		$this->assignObject();
		//$this->object =& $ilias->obj_factory->getInstanceByRefId($_GET["ref_id"]);
		$this->lng =& $this->object->lng;

		if (isset($_GET["tree_id"]))
		{
			$this->grp_tree = new ilTree($_GET["tree_id"]);
		}else{
			$this->grp_tree = new ilTree($this->object->getId());
		}
		$this->grp_tree->setTableNames("grp_tree","object_data");
		
		//return to the same place , where the action was executed
		$this->setReturnLocation("cut","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("clear","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("copy","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("link","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		$this->setReturnLocation("paste","group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		
		$cmd = $_GET["cmd"];
		//var_dump ($cmd);
		if($cmd == "")
		{
			$cmd = "view";
		}
		if (isset($_POST["cmd"]))
		{
			//var_dump ($_POST);
			//var_dump ($_POST["id"]);
			$cmd = key($_POST["cmd"]);
			$fullcmd = $cmd."object";
			//echo ($fullcmd);

			$this->$fullcmd();
			exit();

		}

		$this->$cmd();

		//var_dump($_GET);

	}

	/**
	* loads basic template file for group-enviroment
	*
	* @param	access public
	* @param	boolean variable, if not set or set true tabs are displayed
	* @param	multidimensional array for additional tabs; is only passed on;optional
	* @param	script that is used for linking in loacator;optional; default: "group.php"
	**/
	function prepareOutput($tabs=true, $addtab="")
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.group_basic.html");
		//$title = $this->object->getTitle();
		$locatorscript = "group.php?cmd=choose_view&";
		infoPanel();
		sendInfo();
		$this->setAdminTabs($tabs, $addtab);
		$this->setLocator("", "",$locatorscript);
	}

	/**
	* set admin tabs
	* @access	public
	* @param	boolean; whether standard tabs are set or not
	* @param	multdimensional array for additional tabs; optional
	*/
	function setAdminTabs($settabs=false, $addtabs="")
	{
		if (!isset($_SESSION["viewmode"]) or $_SESSION["viewmode"] == "flat")
		{
			$ftabtype = "tabactive";
			$ttabtype = "tabinactive";
		}
		else
		{
			$ftabtype = "tabinactive";
			$ttabtype = "tabactive";
		}
		$this->tpl->addBlockFile("TABS", "tabs", "tpl.tabs.html");
		if ($settabs)
		{

			$this->tpl->setCurrentBlock("tab");
			$this->tpl->setVariable("TAB_TYPE", $ttabtype);
			$this->tpl->setVariable("TAB_TARGET", "bottom");
			$this->tpl->setVariable("TAB_LINK", "group.php?cmd=choose_view&viewmode=tree&ref_id=".$_GET["ref_id"]);
			$this->tpl->setVariable("TAB_TEXT", $this->lng->txt("treeview"));
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("tab");
			$this->tpl->setVariable("TAB_TYPE", $ftabtype);
			$this->tpl->setVariable("TAB_TARGET", "bottom");
			$this->tpl->setVariable("TAB_LINK", "group.php?cmd=choose_view&viewmode=flat&ref_id=".$_GET["ref_id"]);
			$this->tpl->setVariable("TAB_TEXT", $this->lng->txt("flatview"));
			$this->tpl->parseCurrentBlock();
		}

		if (!empty($addtabs))
		{

			foreach($addtabs as $addtab)
			{
				$this->tpl->setCurrentBlock("tab");
				$this->tpl->setVariable("TAB_TYPE", $addtab["ftabtype"]);
				$this->tpl->setVariable("TAB_TARGET", $addtab["target"]);
				$this->tpl->setVariable("TAB_LINK", "group.php?".$addtab["tab_cmd"]);
				$this->tpl->setVariable("TAB_TEXT", $this->lng->txt($addtab["tab_text"]));
				$this->tpl->parseCurrentBlock();
			}
		}
	}


	/**
	* calls current view mode (tree frame or list)
	*/
	function view()
	{
		if (isset($_GET["viewmode"]))
		{
			$_SESSION["viewmode"] = $_GET["viewmode"];
		}

		// tree frame
		if ($_SESSION["viewmode"] == "tree")
		{
			$this->tpl = new ilTemplate("tpl.group.html", false, false);
			$this->tpl->setVariable ("SOURCE", "group.php?cmd=DisplayList&ref_id=".$_GET["ref_id"]);
			$this->tpl->show();
		}
		else	// list
		{

			$this->displayList();
		}
	}




	/**
	* displays list of groups that are located under the node given by ref_id
	*/

	function displayList()
	{

		global  $tree, $rbacsystem;

		require_once "./include/inc.header.php";
		require_once "./classes/class.ilExplorer.php";
		require_once "./classes/class.ilTableGUI.php";

		$this->prepareOutput();
		$this->tpl->setVariable("HEADER",  $this->lng->txt("groups_overview"));

		// set offset & limit
		$offset = intval($_GET["offset"]);
		$limit = intval($_GET["limit"]);

		if ($limit == 0)
		{
			$limit = 10;	// TODO: move to user settings
		}
		if ($offset == "")
		{

			$offset = 0;	// TODO: move to user settings
		}
		// set default sort column
		if (empty($_GET["sort_by"]))
		{
			$_GET["sort_by"] = "title";
		}

		if (!isset($_SESSION["viewmode"]))
		{
			$_SESSION["viewmode"] = "flat";
		}



		$this->tpl->addBlockFile("BUTTONS", "buttons", "tpl.buttons.html");
		$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($_GET["ref_id"]);

		//check if user got permission to create new groups


		if($rbacsystem->checkAccess("write",$this->object->getRefId() ))
		{
			//show "new group" button only if category or dlib objects were chosen(current object)
			if(strcmp($obj_data->getType(), "cat") == 0 || strcmp($obj_data->getType(), "dlib") == 0)
			{
				$this->tpl->setCurrentBlock("btn_cell");
				//right solution
				//$this->tpl->setVariable("BTN_LINK","obj_location_new.php?new_type=grp&from=group.php");
				//$this->tpl->setVariable("BTN_TARGET","target=\"bottom\"");
				//temp.solution
				$this->tpl->setVariable("BTN_LINK","group.php?cmd=create&parent_ref_id=".$_GET["ref_id"]."&type=grp&ref_id=".$_GET["ref_id"]);
				$this->tpl->setVariable("BTN_TXT", $this->lng->txt("grp_new"));
				$this->tpl->parseCurrentBlock();
			}
		}


/*		if ($this->tree->getSavedNodeData($this->ref_id))
		{
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK","group.php?cmd=trash&ref_id=".$_GET["ref_id"]);
			$this->tpl->setVariable("BTN_TXT", $this->lng->txt("trash"));
			$this->tpl->parseCurrentBlock();
		}
*/
		// display different content depending on viewmode
		switch ($_SESSION["viewmode"])
		{
			case "flat":
				$cont_arr = ilUtil::getObjectsByOperations('grp','visible');
				break;

			case "tree":
				//go through valid objects and filter out the groups only
				$cont_arr = array();

				$objects = $tree->getChilds($_GET["ref_id"],"title");

				if (count($objects) > 0)
				{
					foreach ($objects as $key => $object)
					{
						if ($object["type"] == "grp" && $rbacsystem->checkAccess('visible',$object["child"]))
						{
							$cont_arr[$key] = $object;
						}
					}
				}
				break;
		}

		$maxcount = count($cont_arr);

		include_once "./include/inc.sort.php";
		$cont_arr = sortArray($cont_arr,$_GET["sort_by"],$_GET["sort_order"]);
		$cont_arr = array_slice($cont_arr,$offset,$limit);


		// load template for table
		$this->tpl->addBlockfile("CONTENT", "group_table", "tpl.table.html");
		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.grp_tbl_rows.html");
		$cont_num = count($cont_arr);


		// render table content data
		if ($cont_num > 0)
		{
			// counter for rowcolor change
			$num = 0;
			foreach ($cont_arr as $cont_data)
			{
				$this->tpl->setCurrentBlock("tbl_content");
				$newuser = new ilObjUser($cont_data["owner"]);
				// change row color
				$this->tpl->setVariable("ROWCOL", ilUtil::switchColor($num,"tblrow2","tblrow1"));
				$num++;
				$obj_link = "group.php?cmd=show_content&ref_id=".$cont_data["ref_id"]."&tree_id=".$cont_data["obj_id"]."&obj_id=".$cont_data["obj_id"];
				$obj_icon = "icon_".$cont_data["type"]."_b.gif";
				$this->tpl->setVariable("TITLE", $cont_data["title"]);
				$this->tpl->setVariable("LINK", $obj_link);
				$this->tpl->setVariable("LINK_TARGET", "bottom");
				$this->tpl->setVariable("IMG", $obj_icon);
				$this->tpl->setVariable("ALT_IMG", $this->lng->txt("obj_".$cont_data["type"]));
				$this->tpl->setVariable("DESCRIPTION", $cont_data["description"]);
				$this->tpl->setVariable("OWNER", $newuser->getFullName($cont_data["owner"]));
				$this->tpl->setVariable("LAST_CHANGE", $cont_data["last_update"]);
				$this->tpl->setVariable("CONTEXTPATH", $this->getContextPath($cont_data["ref_id"]));
				$this->tpl->parseCurrentBlock();
			}
		}
		else
		{
			$this->tpl->setCurrentBlock("no_content");
			$this->tpl->setVariable("TXT_MSG_NO_CONTENT",$this->lng->txt("group_not_available"));
			$this->tpl->parseCurrentBlock("no_content");
		}

		// create table
		$tbl = new ilTableGUI();

		// title & header columns

		$tbl->setTitle($this->lng->txt("groups_overview"),"icon_grp_b.gif",$this->lng->txt("groups_overview"));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));
		$tbl->setHeaderNames(array($this->lng->txt("title"),$this->lng->txt("description"),$this->lng->txt("owner"),$this->lng->txt("last_change"),$this->lng->txt("context")));
		$tbl->setHeaderVars(array("title","description","owner","last_change","context"), array("cmd"=>"DisplayList", "ref_id"=>$_GET["ref_id"]));
		$tbl->setColumnWidth(array("7%","10%","15%","15%","22%"));
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($limit);
		$tbl->setOffset($offset);
		$tbl->setMaxCount($maxcount);

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));

		// render table
		$tbl->render();
		$this->tpl->show();
	}

	function explorer()
	{
		require_once "include/inc.header.php";
		require_once "classes/class.ilExplorer.php";
		require_once "classes/class.ilGroupExplorer.php";

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.explorer.html");

		$exp = new ilGroupExplorer("group.php?cmd=displayList");

		if ($_GET["expand"] == "")
		{
			$expanded = "1";
		}
		else
		{
			$expanded = $_GET["expand"];
		}

		$exp->setExpand($expanded);

		//filter object types
		$exp->addFilter("root");
		$exp->addFilter("cat");
		$exp->addFilter("grp");
		$exp->setFiltered(true);

		//build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();

		$this->tpl->setCurrentBlock("content");
		$this->tpl->setVariable("TXT_EXPLORER_HEADER",$this->lng->txt("groups"));
		$this->tpl->setVariable("EXPLORER",$output);
		//$this->tpl->setVariable("ACTION", "group_menu.php?expand=".$_GET["expand"]);
		$this->tpl->parseCurrentBlock();

		$this->tpl->show();

	}
	/*
	*function displays content of a group given by its ref_id
	*via formaction contained objects of given group can be handled
	*
	*access public
	*/
	function show_content()
	{

		global $tree, $tpl, $lng, $rbacsystem;

		$tab = array();

		$tab[0] = array ();
		$tab[0]["tab_cmd"] = 'cmd=show_content&ref_id='.$_GET["ref_id"]; 	//link for tab
		$tab[0]["ftabtype"] = 'tabactive'; 					//tab is marked
		$tab[0]["target"] = "bottom";  						//target-frame of tab_cmd
		$tab[0]["tab_text"] = 'group_objects'; 					//tab -text

		$tab[1] = array ();
		$tab[1]["tab_cmd"] = 'cmd=groupmembers&ref_id='.$_GET["ref_id"];	//link for tab
		$tab[1]["ftabtype"] = 'tabinactive';					//tab is marked
		$tab[1]["target"] = "bottom";						//target-frame of tab_cmd
		$tab[1]["tab_text"] = 'group_members';					//tab -text

		$this->prepareOutput(false, $tab);
		$this->tpl->setVariable("HEADER",  $this->lng->txt("group_details"));

		$this->tpl->addBlockFile("BUTTONS", "buttons", "tpl.buttons.html");

		if($_GET["type"]=="fold")
		{
			$this->tpl->setVariable("FORMACTION", "group.php?gateway=true&ref_id=".$_GET["ref_id"]."&parent_non_rbac_id=".$this->object->getId()."&obj_id=".$this->object->getId()."&tree_id=".$this->grp_tree->getTreeId()."&tree_table=grp_tree");
		}
		else
		{
			$this->tpl->setVariable("FORMACTION", "group.php?gateway=true&ref_id=".$_GET["ref_id"]."&parent_non_rbac_id=-1&obj_id=".$this->object->getId()."&tree_id=".$this->grp_tree->getTreeId()."&tree_table=grp_tree");
		}
		$this->tpl->setVariable("FORM_ACTION_METHOD", "post");

		// set offset & limit
		$offset = intval($_GET["offset"]);
		$limit = intval($_GET["limit"]);


		if ($limit == 0)
		{
			$limit = 10;	// TODO: move to user settings
		}
		if ($offset == "")
		{
			$offset = 0;	// TODO: move to user settings
		}

		// set default sort column
		if (empty($_GET["sort_by"]))
		{
			$_GET["sort_by"] = "title";
		}

		$cont_arr = array();
		$objects = $this->grp_tree->getChilds($this->object->getId(),"title"); //provides variable with objects located under given node

		if (count($objects) > 0)
		{
			foreach ($objects as $key => $object)
			{
				if ($rbacsystem->checkAccess('visible',$object["ref_id"]))
				{
					$cont_arr[$key] = $object;

				}
			}
		}
		$maxcount = count($cont_arr);
		$cont_arr = sortArray($cont_arr,$_GET["sort_by"],$_GET["sort_order"]);
		$cont_arr = array_slice($cont_arr,$offset,$limit);

		// load template for table
		$this->tpl->addBlockfile("CONTENT", "group_table", "tpl.table.html");
		// load template for table content data
		$access = false;
		
		//check if user got "write" permissions; if so $access is set true to prevent further database queries in this function
		if($rbacsystem->checkAccess("write", $this->object->getId() ))
		{
			 $access = true;
			 $this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.grp_tbl_rows_checkbox.html");
		}
		else
		{
			 $this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.grp_tbl_rows.html");
		}

		$cont_num = count($cont_arr);

		// render table content data
		if ($cont_num > 0)
		{
			// counter for rowcolor change
			$num = 0;

			foreach ($cont_arr as $cont_data)
			{
				//temporary solution later rolf should be viewablle for grp admin
				if ($cont_data["type"]!="rolf")
				{
				$this->tpl->setCurrentBlock("tbl_content");
				$newuser = new ilObjUser($cont_data["owner"]);
				// change row color
				$this->tpl->setVariable("ROWCOL", ilUtil::switchColor($num,"tblrow2","tblrow1"));
				$num++;

				if($cont_data["type"] == "lm" || $cont_data["type"] == "frm" )
				{
					$link_target = "_top";
				}else{
					$link_target = "_self";
				}
				$obj_link = $this->getURLbyType($cont_data);


				$obj_icon = "icon_".$cont_data["type"]."_b.gif";
				if ($access)
				{
					$this->tpl->setVariable("CHECKBOX", ilUtil::formCheckBox(0,"id[]",$cont_data["ref_id"]));
				}
				$this->tpl->setVariable("TITLE", $cont_data["title"]);
				$this->tpl->setVariable("LO_LINK", $obj_link);
				$this->tpl->setVariable("LINK_TARGET", $link_target);
				$this->tpl->setVariable("IMG", $obj_icon);
				$this->tpl->setVariable("ALT_IMG", $lng->txt("obj_".$cont_data["type"]));
				$this->tpl->setVariable("DESCRIPTION", $cont_data["description"]);
				$this->tpl->setVariable("OWNER", $newuser->getFullName());
				$this->tpl->setVariable("LAST_CHANGE", ilFormat::formatDate($cont_data["last_update"]));
				//TODO
				if($cont_data["ref_id"] != -1)
				{
					$this->tpl->setVariable("CONTEXTPATH", $this->getContextPath($cont_data["ref_id"]));
				}
				$this->tpl->parseCurrentBlock();
				}
			}
		}
		else
		{
			$this->tpl->setCurrentBlock("no_content");
			$this->tpl->setVariable("TXT_MSG_NO_CONTENT",$lng->txt("group_any_objects"));
			$this->tpl->parseCurrentBlock("no_content");
		}

		// create table
		$tbl = new ilTableGUI();
		// buttons in bottom-bar
		if ($access)
		{
			$tbl->setHeaderNames(array("",$lng->txt("title"),$lng->txt("description"),$lng->txt("owner"),$lng->txt("last_change"),$lng->txt("context")));
			$tbl->setHeaderVars(array("checkbox","title","description","status","last_change","context"), array("cmd"=>"show_content", "ref_id"=>$_GET["ref_id"]));
			$tbl->setColumnWidth(array("3%","7%","10%","15%","15%","22%"));
			$this->tpl->setCurrentBlock("tbl_action_btn");
			$this->tpl->SetVariable("COLUMN_COUNTS", "6");
			$this->showActions(true);
		}
		else
		{
			$tbl->setHeaderNames(array($lng->txt("title"),$lng->txt("description"),$lng->txt("owner"),$lng->txt("last_change"),$lng->txt("context")));
			$tbl->setHeaderVars(array("title","description","status","last_change","context"), array("cmd"=>"show_content", "ref_id"=>$_GET["ref_id"]));
			$tbl->setColumnWidth(array("7%","10%","15%","15%","22%"));
		}

		// title & header columns
		$tbl->setTitle($lng->txt("group_details")." - ".$this->object->getTitle(),"icon_grp_b.gif", $lng->txt("group_details"));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$lng->txt("help"));
		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($limit);
		$tbl->setOffset($offset);
		$tbl->setMaxCount($maxcount);


		// footer
		$tbl->setFooter("tblfooter",$lng->txt("previous"),$lng->txt("next"));
		// render table
		$tbl->render();
		$this->tpl->show();
	}

	/**
	* function chooses right view depending on what kind of object is selected in locator bar
	* preferred view (treeview or flatview) if a category is selected
	* flat view if group is selected
	*@access	public
	**/
	function choose_view()
	{
		$obj_data = & $this->ilias->obj_factory->getInstanceByRefId($_GET["ref_id"]);
		if (isset($_GET["viewmode"]))
		{
			$_SESSION["viewmode"] = $_GET["viewmode"];
		}

		if (strcmp ($_SESSION["viewmode"], "tree") == 0)
		{
			if (strcmp($obj_data->getType(), "grp") == 0)
			{
				$this->show_content();
			}
			else
			{
				$this->view();
			}
		}
		else
		{
			if (strcmp($obj_data->getType(), "grp") == 0)
			{
				$this->show_content();
			}
			else
			{
				$this->DisplayList();
			}
		}
	}

	/*
	* function returns specific link-url depending on object-type
	*
	*
	* access public
	*/
	function getURLbyType($cont_data)
	{
		switch ($cont_data["type"])
		{

  		case "frm":
			$URL = "forums_threads_liste.php?ref_id=".$cont_data["ref_id"];
		break;

		case "crs":
			$URL = "lo_list.php?cmd=displayList&ref_id=".$cont_data["ref_id"];
		break;

		case "lm":
			$URL = "content/lm_presentation.php?ref_id=".$cont_data["ref_id"];
		break;

		case "fold":
			//TODO

			if (isset($_GET["tree_id"]))
			{
				$URL = "group.php?obj_id=".$cont_data["obj_id"]."&ref_id=".$_GET["ref_id"]."&tree_id=".$_GET["tree_id"]."&tree_table=grp_tree&cmd=show_content";

			}else{

				$URL = "group.php?obj_id=".$cont_data["obj_id"]."&ref_id=".$_GET["ref_id"]."&tree_id=".$this->object->getId()."&tree_table=grp_tree&cmd=show_content";
			}

		break;
		}

	return $URL;
	}


	function getContextPath($a_endnode_id, $a_startnode_id = 0)
	{
		global $tree;

		$path = "";

		$tmpPath = $this->tree->getPathFull($a_endnode_id, $a_startnode_id);

		// count -1, to exclude the forum itself
		for ($i = 0; $i < (count($tmpPath) - 1); $i++)
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
	* paste object from clipboard to current place
	* Depending on the chosen command the object(s) are linked, copied or moved
	*
	* @access	public
 	*/
	/*function pasteObject()
	{
		global $rbacsystem, $rbacadmin, $rbacreview, $log;
//echo "command".$_SESSION["clipboard"]["cmd"];
		if (!in_array($_SESSION["clipboard"]["cmd"],array("cut","link","copy")))
		{
			$message = get_class($this)."::pasteObject(): cmd was neither 'cut','link' or 'copy'; may be a hack attempt!";
			$log->writeWarning($message);
			$this->ilias->raiseError($message,$this->ilias->error_obj->WARNING);
		}

		// this loop does all checks
		foreach ($_SESSION["clipboard"]["ref_ids"] as $ref_id)
		{
			$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($ref_id);

			// CHECK ACCESS
			if (!$rbacsystem->checkAccess('create', $_GET["ref_id"], $obj_data->getType()))
			{
				$no_paste[] = $ref_id;
			}

			// CHECK IF REFERENCE ALREADY EXISTS
			if ($_GET["ref_id"] == $this->tree->getParentId($obj_data->getRefId()))
			{
				$exists[] = $ref_id;
				break;
			}

			// CHECK IF PASTE OBJECT SHALL BE CHILD OF ITSELF
			if ($this->tree->isGrandChild($ref_id,$_GET["ref_id"]))
			{
				$is_child[] = $ref_id;
			}

			// CHECK IF OBJECT IS ALLOWED TO CONTAIN PASTED OBJECT AS SUBOBJECT
			$obj_type = $obj_data->getType();

			if (!in_array($obj_type, array_keys($this->objDefinition->getSubObjects($this->object->getType()))))
			{
				$not_allowed_subobject[] = $obj_data->getType();
			}
		}

		////////////////////////////
		// process checking results
		if (count($exists))
		{
			$this->ilias->raiseError($this->lng->txt("msg_obj_exists"),$this->ilias->error_obj->MESSAGE);
		}

		if (count($is_child))
		{
			$this->ilias->raiseError($this->lng->txt("msg_not_in_itself")." ".implode(',',$is_child),
									 $this->ilias->error_obj->MESSAGE);
		}

		if (count($not_allowed_subobject))
		{
			$this->ilias->raiseError($this->lng->txt("msg_may_not_contain")." ".implode(',',$not_allowed_subobject),
									 $this->ilias->error_obj->MESSAGE);
		}

		if (count($no_paste))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_paste")." ".
									 implode(',',$no_paste),$this->ilias->error_obj->MESSAGE);
		}

		////////////////////////////////////////////////////////
		// everything ok: now paste the objects to new location

		// process COPY command
		if ($_SESSION["clipboard"]["cmd"] == "copy")
		{		
			// CALL PRIVATE CLONE METHOD
			$this->cloneObject($_GET["ref_id"]);
		}

		// process CUT command
		if ($_SESSION["clipboard"]["cmd"] == "cut")
		{
			foreach($_SESSION["clipboard"]["ref_ids"] as $ref_id)
			{

				$parent_id = $this->tree->getParentId($ref_id);
				
				// get node data
				$top_node = $this->tree->getNodeData($ref_id);

				// get subnodes of top nodes
				$subnodes[$ref_id] = $this->tree->getSubtree($top_node);

				// delete old tree entries
				$this->tree->deleteTree($top_node);
				
				$this->object->notify("cut", $_GET["ref_id"],$_GET["ref_id"]);

				unset($tmpObj);
			}

			// now move all subtrees to new location
			foreach($subnodes as $key => $subnode)
			{
				// first paste top_node ...
				$rbacadmin->revokePermission($key);
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($key);
				$obj_data->putInTree($_GET["ref_id"]);
				$obj_data->setPermissions($_GET["ref_id"]);


				// paste top_node also to the grp_tree table
				//$this->object->insertGroupNode($obj_data->getId(),$this->object->getId(),$this->object->getId(),$obj_data->getRefId());


				// ... remove top_node from list ...
				array_shift($subnode);

				// ... insert subtree of top_node if any subnodes exist
				if (count($subnode) > 0)
				{
					foreach ($subnode as $node)
					{
						$rbacadmin->revokePermission($node["child"]);
						$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);
						$obj_data->putInTree($node["parent"]);
						$obj_data->setPermissions($node["parent"]);

						// ... put the node also into the grp_tree table

						//$new_parent_data =& $this->ilias->obj_factory->getInstanceByRefId($node["parent"]);
						//$this->object->insertGroupNode($obj_data->getId(),$new_parent_data->getId(),$this->object->getId(),$obj_data->getRefId());


					}
				}
			}
			// inform other objects in hierarchy about paste operation
			$this->object->notify("paste",$_GET["ref_id"],$_GET["ref_id"]);
				
		} // END CUT

		// process LINK command
		if ($_SESSION["clipboard"]["cmd"] == "link")
		{
			foreach ($_SESSION["clipboard"]["ref_ids"] as $ref_id)
			{
				// get node data
				$top_node = $this->tree->getNodeData($ref_id);

				// get subnodes of top nodes
				$subnodes[$ref_id] = $this->tree->getSubtree($top_node);
			}

			// now move all subtrees to new location
			foreach ($subnodes as $key => $subnode)
			{
				// first paste top_node....
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($key);
				$new_ref_id = $obj_data->createReference();
				$obj_data->putInTree($_GET["ref_id"]);
				$obj_data->setPermissions($_GET["ref_id"]);


				// paste top_node also to the grp_tree table
				//$this->object->insertGroupNode($obj_data->getId(),$this->object->getId(),$this->object->getId(),$obj_data->getRefId());


				// ... remove top_node from list ...
				array_shift($subnode);

				// ... store mapping of old ref_id => new_ref_id in hash array ...
				$mapping[$new_ref_id] = $key;

				// ... insert subtree of top_node if any subnodes exist ...
				if (count($subnode) > 0)
				{
					foreach ($subnode as $node)
					{
						if ($node["type"] != 'rolf')
						{
							$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);
							$new_ref_id = $obj_data->createReference();

							// ... use mapping array to find out the correct new parent node where to put in the node...
							$new_parent = array_search($node["parent"],$mapping);

							// ... append node to mapping for further possible subnodes ...
							$mapping[$new_ref_id] = (int) $node["child"];

							$obj_data->putInTree($new_parent);
							$obj_data->setPermissions($new_parent);

							// ... put the node also into the grp_tree table

							//$new_parent_data =& $this->ilias->obj_factory->getInstanceByRefId($new_parent);
							//$this->object->insertGroupNode($obj_data->getId(),$new_parent_data->getId(),$this->object->getId(),$obj_data->getRefId());


						}
						else
						{
							// ... use mapping array to find out the correct new parent node where to put in the node...
							$new_parent = array_search($node["parent"],$mapping);

							// get the parent object that contains the rolefolder ...
							$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($new_parent);
							// setup rolefolder and link in default local roles
							// createRoleFolder
							$rfoldObj = $obj_data->createRoleFolder("Local Roles","Role Folder of object ref_no.".$new_parent,$new_parent);

							// ... append node to mapping
							$mapping[$rfoldObj->getRefId()] = (int) $node["child"];

							$localroles = $rbacreview->getRolesOfRoleFolder($node["child"],false);

							foreach ($localroles as $role_id)
							{
								$rbacadmin->assignRoleToFolder($role_id,$rfoldObj->getRefId(),"y");
							}
						}
					}
				}
			}
			// inform other objects in hierarchy about link operation
			$this->object->notify("link",$_GET["ref_id"],$_GET["ref_id"],$mapping);
		} // END LINK

		// save cmd for correct message output after clearing the clipboard
		$last_cmd = $_SESSION["clipboard"]["cmd"];

		// clear clipboard
		$this->clearObject();

		if ($last_cmd == "cut")
		{
			sendInfo($this->lng->txt("msg_cut_copied"),true);
		}
		else
		{
			sendInfo($this->lng->txt("msg_linked"),true);
		}

		header("location: group.php?cmd=show_content&ref_id".$_GET["ref_id"]);
		exit();
	} // END PASTE
	*/





	/**
	* remove member object from group preparation(messages,link)
	* @access	public
	*/

	function removeMemberObject()
	{
		$user_ids = array();
		if(isset($_POST["user_id"]))
			$user_ids = $_POST["user_id"];
		else if(isset($_GET["mem_id"]))
			$user_ids = $_GET["mem_id"];
		if(isset($user_ids))
		{
			$confirm = "confirmedRemoveMember";
			$cancel  = "canceldelete";
			$info	 = "info_delete_sure";
			$status  = "";
			$call_by_reference="n";
			$this->confirmation($user_ids, $confirm, $cancel, $info, $status,$call_by_reference);
			$this->tpl->show();

		}
		else
		{
			sendInfo($this->lng->txt("You have to choose at least one user !"),true);
			header("location: group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		}
	}

	/**
	* remove members from group
	* @access public
	*/
	function confirmedRemoveMemberObject()
	{
		global $rbacsystem,$ilias;

		if(isset($_SESSION["saved_post"]["user_id"]) )
		{
			foreach($_SESSION["saved_post"]["user_id"] as $mem_id)
			{
				$newGrp = new ilObjGroup($_GET["ref_id"],true);
				if($rbacsystem->checkAccess('leave',$_GET["ref_id"]))
				{
					//check ammount of members
					if(count($newGrp->getGroupMemberIds()) == 1)
					{
						if($rbacsystem->checkAccess('delete',$_GET["ref_id"]))
						{
							//GROUP DELETE
							$this->ilias->raiseError("Gruppe loeschen, da letztes Mitglied!",$this->ilias->error_obj->MESSAGE);
						}
						else
							$this->ilias->raiseError("You do not have the permissions to delete this group!",$this->ilias->error_obj->MESSAGE);
					}
					else
					{
						//MEMBER LEAVES GROUP
						if($newGrp->isMember($mem_id) && !$newGrp->isAdmin($mem_id))
						{
							if(!$newGrp->leaveGroup($mem_id))
								$this->ilias->raiseError("Error while attempting to discharge user!",$this->ilias->error_obj->MESSAGE);
						}
						else	//ADMIN LEAVES GROUP
						if($newGrp->isAdmin($mem_id))
						{
							if(count($newGrp->getGroupAdminIds()) <= 1 )
							{
								$this->ilias->raiseError("At least one group administrator is required! Please entitle a new group administrator first ! ",$this->ilias->error_obj->WARNING);
							}
							else if(!$newGrp->leaveGroup($mem_id))
								$this->ilias->raiseError("Error while attempting to discharge user!",$this->ilias->error_obj->MESSAGE);
						}
					}
				}
				else
					$this->ilias->raiseError("You are not allowed to leave this group!",$this->ilias->error_obj->MESSAGE);
			}
		}
		unset($_SESSION["saved_post"]);
		header("location: group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
	}

	/**
	* displays confirmation form that is used from several methods(deleteObject,newMembers, removeMember, ChangeMemberStatus)
	* works like a gateway
	* @access public
	* @param	id of the displayed objects
	* @param	confirm = name of method that is called after confirmation
	* @param	cancel  = name of method that is called when canceled
	* @param	info	= message
	* @param	status  = userstatus of new members[member=0|admin=1]
	* @param	call_by_ref = message
	*
	*/

	function confirmation($user_id="", $confirm, $cancel, $info="", $status="",$call_by_reference="n")
	{
		$num =0;
		$this->prepareOutput(false);
		$this->tpl->setVariable("HEADER", $this->lng->txt("confirm_action"));
		sendInfo ($info);
		$this->tpl->addBlockFile("CONTENT", "confirmation", "tpl.table.html");
		$this->tpl->setVariable("FORMACTION", "group.php?ref_id=".$_GET["ref_id"]."&gateway=true");
		$this->tpl->addBlockFile("TBL_CONTENT", "confirmcontent","tpl.grp_tbl_confirm.html" );

		// set offset & limit
		$offset = intval($_GET["offset"]);
		$limit = intval($_GET["limit"]);


		if ($limit == 0)
		{
			$limit = 10;	// TODO: move to user settings
		}
		if ($offset == "")
		{
			$offset = 0;	// TODO: move to user settings
		}

		if(is_array($user_id))
		{

			$maxcount = count ($user_id);
			foreach($user_id as $id)
			{
				if($call_by_reference == 'y')
				{
					$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($id);
				}
				else
				{
					$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($id);
				}
				$this->tpl->setVariable("ROWCOL", ilUtil::switchColor($num,"tblrow2","tblrow1"));
				$num++;
				$this->tpl->setVariable("DESCRIPTION", $obj_data->getDescription());
				$this->tpl->setVariable("TITLE", $obj_data->getTitle());
				$this->tpl->setVariable("TYPE", ilUtil::getImageTagByType($obj_data->getType(),$this->tpl->tplPath));
				$this->tpl->setVariable("LAST_UPDATE", $obj_data->getLastUpdateDate());
				$this->tpl->parseCurrentBlock();
			}
		}
		else
		{
			$maxcount = 1;
			if($call_by_reference == 'y')
			{
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($user_id);
			}
			else
			{
				$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($user_id);
			}
			$this->tpl->setVariable("DESCRIPTION", $obj_data->getDescription());
			$this->tpl->setVariable("TITLE", $obj_data->getTitle());
			$this->tpl->setVariable("TYPE", ilUtil::getImageTagByType($obj_data->getType(),$this->tpl->tplPath));
			$this->tpl->setVariable("LAST_UPDATE", $obj_data->getLastUpdateDate());
			$this->tpl->parseCurrentBlock();
		}

		if(is_array($user_id))
		{
			$_SESSION["saved_post"]["user_id"] = $user_id;
		}
		else
		{
			$_SESSION["saved_post"]["user_id"][0] = $user_id;
		}

		if(isset($status))
		{
			$_SESSION["saved_post"]["status"] = $status;
		}


		$this->tpl->setVariable("COLUMN_COUNTS", "4");

		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", $confirm);
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("confirm"));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("tbl_action_btn");
		$this->tpl->setVariable("BTN_NAME", $cancel);
		$this->tpl->setVariable("BTN_VALUE", $this->lng->txt("cancel"));
		$this->tpl->parseCurrentBlock();

		$tbl = new ilTableGUI();
		$tbl->setHeaderNames(array($this->lng->txt("type"),$this->lng->txt("title"),$this->lng->txt("description"),$this->lng->txt("last_change")));
		$tbl->setHeaderVars(array("typ","title","description","last_change"));
		$tbl->setColumnWidth(array("3%","16%","22%","*"));
		$tbl->setMaxcount($maxcount);
		$tbl->setOffset($offset);
		$tbl->setLimit($limit);
		$tbl->setTitle($this->lng->txt("confirm_action"),"icon_grp_b.gif",$this->lng->txt("group_details"));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		$tbl->render();

		//$this->tpl->show();
	}


	/**
	* displays form with all members of group
	* @access public
	*/
	function groupmembers()
	{
		global $rbacsystem;

		//check Access
  		if(!$rbacsystem->checkAccess("read",$this->object->getRefId() ))
		{
			$this->ilias->raiseError("Permission denied !",$this->ilias->error_obj->MESSAGE);
		}

		$tab = array();

		//create additional tabs for tab-bar
		$tab[0] = array ();
		$tab[0]["tab_cmd"] = 'cmd=show_content&ref_id='.$_GET["ref_id"];
		$tab[0]["ftabtype"] = 'tabinactive';
		$tab[0]["target"] = "bottom";
		$tab[0]["tab_text"] = 'group_objects';

		$tab[1] = array ();
		$tab[1]["tab_cmd"] = 'cmd=groupmembers&ref_id='.$_GET["ref_id"];
		$tab[1]["ftabtype"] = 'tabactive';
		$tab[1]["target"] = "bottom";
		$tab[1]["tab_text"] = 'group_members';

		$this->prepareOutput(false, $tab);

		$this->tpl->addBlockFile("BUTTONS", "buttons", "tpl.buttons.html");
		$newGrp = new ilObjGroup($_GET["ref_id"],true);
		$admin_ids = $newGrp->getGroupAdminIds();

		//if current user is admin he is able to add new members to group
		if(in_array($_SESSION["AccountId"], $admin_ids))
		{
			$this->tpl->setCurrentBlock("btn_cell");
			$this->tpl->setVariable("BTN_LINK","group.php?cmd=newmembersobject&ref_id=".$_GET["ref_id"]);
			$this->tpl->setVariable("BTN_TXT", $this->lng->txt("add_member"));
			$this->tpl->parseCurrentBlock();
		}

		$img_contact = "pencil";
		$img_change = "change";
		$img_leave = "group_out";
		$val_contact = ilUtil::getImageTagByType($img_contact, $this->tpl->tplPath);
		$val_change = ilUtil::getImageTagByType($img_change, $this->tpl->tplPath);
		$val_leave  = ilUtil::getImageTagByType($img_leave, $this->tpl->tplPath);

		$newGrp = new ilObjGroup($_GET["ref_id"],true);
		$member_ids = $newGrp->getGroupMemberIds($_GET["ref_id"]);

		foreach($member_ids as $member_id)
		{
			$member =& $this->ilias->obj_factory->getInstanceByObjId($member_id);

			$link_contact = "mail_new.php?mobj_id=3&type=new&mail_data[rcp_to]=".$member->getLogin();
			$link_change = "group.php?cmd=changeMemberObject&ref_id=".$this->ref_id."&mem_id=".$member->getId();
			$link_leave = "group.php?type=grp&cmd=removeMemberObject&ref_id=".$_GET["ref_id"]."&mem_id=".$member->getId();

			//build function
			if(in_array($_SESSION["AccountId"], $admin_ids))
			{
				$member_functions = "<a href=\"$link_change\">$val_change</a>";
			}
			if(in_array($_SESSION["AccountId"], $admin_ids) || $member->getId() == $_SESSION["AccountId"])
			{
				$member_functions .="<a href=\"$link_leave\">$val_leave</a>";
			}

			$grp_role_id = $newGrp->getGroupRoleId($member->getId());
			$newObj	     = new ilObject($grp_role_id,false);


			//INTERIMS:quite a circumstantial way to handle the table structure....
			if($rbacsystem->checkAccess("write",$this->object->getRefId() ))
			{
				$this->data["data"][$member->getId()]= array(
					"check"		=> ilUtil::formCheckBox(0,"user_id[]",$member->getId()),
					"login"        => $member->getLogin(),
					"firstname"       => $member->getFirstname(),
					"lastname"        => $member->getLastname(),
					"grp_role" => $newObj->getTitle(),
					"functions" => "<a href=\"$link_contact\">".$val_contact."</a>".$member_functions
					);

				unset($member_functions);
				unset($member);
				unset($newObj);
			}
			else
			{
				//discarding the checkboxes
				$this->data["data"][$member->getId()]= array(
					"login"        => $member->getLogin(),
					"firstname"       => $member->getFirstname(),
					"lastname"        => $member->getLastname(),
					"grp_role" => $newObj->getTitle(),
					"functions" => "<a href=\"$link_contact\">".$val_contact."</a>".$member_functions
					);

				unset($member_functions);
				unset($member);
				unset($newObj);
			}

		}

		$this->tpl->setVariable("HEADER",  $this->lng->txt("group_members"));
		$this->tpl->addBlockfile("CONTENT", "member_table", "tpl.table.html");

		// load template for table content data
		$this->tpl->setVariable("FORMACTION", "group.php?ref_id=".$_GET["ref_id"]."&gateway=true");

		$this->data["buttons"] = array( "RemoveMember"  => $this->lng->txt("remove"),
						"changeMember"  => $this->lng->txt("change"));

		$this->tpl->setCurrentBlock("tbl_action_row");
		$this->tpl->setVariable("TPLPATH",$this->tplPath);

		//INTERIMS:quite a circumstantial way to show the list on rolebased accessrights
		if($rbacsystem->checkAccess("write",$this->object->getRefId() ))
		{
			//user is administrator
			$this->tpl->setVariable("COLUMN_COUNTS",6);
			foreach ($this->data["buttons"] as $name => $value)
			{
				$this->tpl->setCurrentBlock("tbl_action_btn");
				$this->tpl->setVariable("BTN_NAME",$name);
				$this->tpl->setVariable("BTN_VALUE",$value);
				$this->tpl->parseCurrentBlock();
			}
		}
		else
		{
			//user is member
			$this->tpl->setVariable("COLUMN_COUNTS",5);//user must be member
		}

		//sort data array
		include_once "./include/inc.sort.php";
		include_once "./classes/class.ilTableGUI.php";

		$this->data["data"] = sortArray($this->data["data"], $_GET["sort_by"], $_GET["sort_order"]);

		$offset = intval($_GET["offset"]);
		$limit = intval($_GET["limit"]);

		if ($limit == 0)
		{
			$limit = 10;	// TODO: move to user settings
		}
		if ($offset == "")
		{

			$offset = 0;	// TODO: move to user settings
		}

		$output = array_slice($this->data["data"],$offset,$limit);

		// create table
		$tbl = new ilTableGUI($output);

		// title & header columns
		$tbl->setTitle($this->lng->txt("group_members")." - ".$this->object->getTitle(),"icon_usr_b.gif",$this->lng->txt("group_members"));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));

		//INTERIMS:quite a circumstantial way to show the list on rolebased accessrights
		if($rbacsystem->checkAccess("write",$this->object->getRefId() ))
		{
			//user must be administrator
			$tbl->setHeaderNames(array($this->lng->txt("check"),$this->lng->txt("username"),$this->lng->txt("firstname"),$this->lng->txt("lastname"),$this->lng->txt("role"),$this->lng->txt("functions")));
			$tbl->setHeaderVars(array("check","login","firstname","lastname","role","functions"),array("ref_id"=>$_GET["ref_id"],"cmd"=>$_GET["cmd"]));
			$tbl->setColumnWidth(array("5%","15%","30%","30%","10%","10%"));
		}
		else
		{
			//user must be member
			$tbl->setHeaderNames(array($this->lng->txt("username"),$this->lng->txt("firstname"),$this->lng->txt("lastname"),$this->lng->txt("role"),$this->lng->txt("functions")));
			$tbl->setHeaderVars(array("login","firstname","lastname","role","functions"),array("ref_id"=>$_GET["ref_id"],"cmd"=>$_GET["cmd"]));
			$tbl->setColumnWidth(array("20%","30%","30%","10%","10%"));
		}

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($limit);
		$tbl->setOffset($offset);
		$tbl->setMaxCount(count($this->data["data"]));

		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));

		$tbl->render();
		$this->tpl->show();
	}


	function deleteObject()
	{
		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		$_SESSION["saved_post"] = $_POST["id"];
		unset($this->data);
		$confirm = "confirmedDelete";
		$cancel  = "canceldelete";
		$info	 = "info_delete_sure";
		$status  = "";
/*
		$oid = array();
		foreach($_POST["id"] as $pid)
		{
			$tmp = $this->ilias->obj_factory->getInstanceByRefId($pid);
			array_push($oid,$tmp->getRefId() );
		}
*/

		$this->confirmation($_POST["id"], $confirm, $cancel, $info,"","y");
		$this->tpl->show();
	}

	/**
	* confirmed deletion if object -> objects are moved to trash
	*
	* However objects are only removed from tree!! That means that the objects
	* itself stay in the database but are not linked in any context within the system.
	* Trash Bin Feature: Objects can be refreshed in trash
	*
	* @access	public
	*/
	function confirmedDeleteObject()
	{
		global $tree, $rbacsystem, $rbacadmin, $objDefinition;

		// AT LEAST ONE OBJECT HAS TO BE CHOSEN.
		if (!isset($_SESSION["saved_post"]["user_id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// FOR ALL SELECTED OBJECTS
		foreach ($_SESSION["saved_post"]["user_id"] as $id)
		{
			// GET COMPLETE NODE_DATA OF ALL SUBTREE NODES
			$node_data = $tree->getNodeData($id);
			$subtree_nodes = $tree->getSubTree($node_data);

			$all_node_data[] = $node_data;
			$all_subtree_nodes[] = $subtree_nodes;

			// CHECK DELETE PERMISSION OF ALL OBJECTS
			foreach ($subtree_nodes as $node)
			{
			//TODO h�gt von den Rechten ab
				/*if (!$rbacsystem->checkAccess('delete',$node["child"]))
				{
					$not_deletable[] = $node["child"];
					$perform_delete = false;
				}*/
			}
		}

		// IF THERE IS ANY OBJECT WITH NO PERMISSION TO DELETE
		if (count($not_deletable))
		{
			$not_deletable = implode(',',$not_deletable);
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_delete")." ".
									 $not_deletable,$this->ilias->error_obj->MESSAGE);
		}

		// DELETE THEM
		if (!$all_node_data[0]["type"])
		{
			// OBJECTS ARE NO 'TREE OBJECTS'
			echo "<br>OBJECTS ARE NO 'TREE OBJECTS";
			if ($rbacsystem->checkAccess('delete',$_GET["ref_id"]))
			{
				//foreach($_SESSION["saved_post"] as $id)
				foreach($_SESSION["saved_post"]["user_id"] as $id)
				{
					//$obj = getObject($id);
					$obj =& $this->ilias->obj_factory->getInstanceByObjId($id);
					$obj->delete();
				}
			}
			else
			{
				$this->ilias->raiseError($this->lng->txt("no_perm_delete"),$this->ilias->error_obj->MESSAGE);
			}
		}
		else
		{
			// SAVE SUBTREE AND DELETE SUBTREE FROM TREE
			//foreach ($_SESSION["saved_post"] as $id)
			foreach ($_SESSION["saved_post"]["user_id"] as $id)
			{
				// DELETE OLD PERMISSION ENTRIES
				$subnodes = $tree->getSubtree($tree->getNodeData($id));

				foreach ($subnodes as $subnode)
				{
					$rbacadmin->revokePermission($subnode["child"]);
				}
				$tree->saveSubTree($id);
				$tree->deleteTree($tree->getNodeData($id));
			}
		}
		// Feedback
		sendInfo($this->lng->txt("info_deleted"),true);

		header("location: group.php?cmd=displayList&ref_id=".$_GET["ref_id"]);
		exit();

	}

	/**
	* remove objects from trash bin and all entries therefore every object needs a specific deleteObject() method
	*
	* @access	public
	*/
	function removeFromSystem()
	{
		global $rbacsystem;

		// AT LEAST ONE OBJECT HAS TO BE CHOSEN.
		if (!isset($_POST["trash_id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// DELETE THEM
		foreach ($_POST["trash_id"] as $id)
		{
			// GET COMPLETE NODE_DATA OF ALL SUBTREE NODES
			$saved_tree = new ilTree(-(int)$id);
			$node_data = $saved_tree->getNodeData($id);
			$subtree_nodes = $saved_tree->getSubTree($node_data);

			// remember already checked deleted node_ids
			$checked[] = -(int) $id;

			// dive in recursive manner in each already deleted subtrees and remove these objects too
			$this->removeDeletedNodes($id,$checked);

			foreach ($subtree_nodes as $node)
			{
				$node_obj =& $this->ilias->obj_factory->getInstanceByRefId($node["ref_id"]);
				$node_obj->delete();
			}

			// FIRST DELETE ALL ENTRIES IN RBAC TREE
			$this->tree->deleteTree($node_data);
		}

		sendInfo($this->lng->txt("msg_removed"),true);

		header("location: group.php?cmd=displaylist&ref_id=".$_GET["ref_id"]);

		exit();
	}
	/**
	* show trash content of object
	*
	* @access	public
 	*/
	function trash()
	{
		$objects = $this->tree->getSavedNodeData($_GET["ref_id"]);

		if (count($objects) == 0)
		{
			sendInfo($this->lng->txt("msg_trash_empty"));
			$this->data["empty"] = true;
		}
		else
		{
			$this->data["empty"] = false;
			$this->data["cols"] = array("","type", "title", "description", "last_change");

			foreach ($objects as $obj_data)
			{
				$this->data["data"]["$obj_data[child]"] = array(
					"checkbox"    => "",
					"type"        => $obj_data["type"],
					"title"       => $obj_data["title"],
					"desc"        => $obj_data["desc"],
					"last_update" => $obj_data["last_update"]);
			}

			$this->data["buttons"] = array( "undelete"  => $this->lng->txt("btn_undelete"),
									  "removeFromSystem"  => $this->lng->txt("btn_remove_system"));
		}

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.obj_confirm.html");

		if ($this->data["empty"] == true)
		{
			return;
		}

		/* TODO: fix message display in conjunction with sendIfno & raiseError functionality
		$this->tpl->addBlockfile("MESSAGE", "adm_trash", "tpl.message.html");
		$this->tpl->setCurrentBlock("adm_trash");
		$this->tpl->setVariable("MSG",$this->lng->txt("info_trash"));
		$this->tpl->parseCurrentBlock();
		*/
		//sendInfo($this->lng->txt("info_trash"));

		$this->tpl->setVariable("FORMACTION", "group?cmd=removeFromSystem&gateway=true&ref_id=".$_GET["ref_id"]);

		// BEGIN TABLE HEADER
		foreach ($this->data["cols"] as $key)
		{
			$this->tpl->setCurrentBlock("table_header");
			$this->tpl->setVariable("TEXT",$this->lng->txt($key));
			$this->tpl->parseCurrentBlock();
		}
		// END TABLE HEADER

		// BEGIN TABLE DATA
		$counter = 0;

		foreach ($this->data["data"] as $key1 => $value)
		{
			// BEGIN TABLE CELL
			foreach ($value as $key2 => $cell_data)
			{
				$this->tpl->setCurrentBlock("table_cell");
				// CREATE CHECKBOX
				if ($key2 == "checkbox")
				{
					$this->tpl->setVariable("TEXT_CONTENT",ilUtil::formCheckBox(0,"trash_id[]",$key1));
				}

				// CREATE TEXT STRING
				elseif ($key2 == "type")
				{
					$this->tpl->setVariable("TEXT_CONTENT",ilUtil::getImageTagByType($cell_data,$this->tpl->tplPath));
				}
				else
				{
					$this->tpl->setVariable("TEXT_CONTENT",$cell_data);
				}

				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("table_row");
			$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
			$this->tpl->parseCurrentBlock();
			// END TABLE CELL
		}
		// END TABLE DATA

		// BEGIN OPERATION_BTN
		foreach ($this->data["buttons"] as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->show();
	}

	/**
	* cancel deletion of object
	*
	* @access	public
	*/
	function canceldeleteObject()
	{
		session_unregister("saved_post");

		header("location: group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
		exit();

	}

	
	/**
	* create new object form
	*
	* @access	public
	*/
	function createobject()
	{
		
		echo $_POST["new_type"];
		
		//TODO: check the
		// creates a child object
		global $rbacsystem;

		$this->prepareOutput();
		// TODO: get rid of $_GET variable
		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $_POST["new_type"]))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			$data = array();
			$data["fields"] = array();
			$data["fields"]["title"] = "";
			$data["fields"]["desc"] = "";


			//$this->tpl->setVariable("HEADER", $this->lng->txt("new_obj"));
			$this->tpl->setVariable("HEADER", $this->lng->txt($_POST["new_type"]."_new"));
			$this->tpl->addBlockFile("CONTENT", "create_table" ,"tpl.obj_edit.html");

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);
			}

			$this->tpl->setVariable("FORMACTION","group.php?gateway=false&cmd=save&ref_id=".$_GET["ref_id"]."&parent_non_rbac_id=".$_GET["parent_non_rbac_id"]."&new_type=".$_POST["new_type"]);
			$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt("save"));
			$this->tpl->setVariable("CMD_SUBMIT", "save");
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		}
		$this->tpl->show();
	}

	/* save object
	*
	* @access	public
	*/
	function saveObject()
	{

	//functionality is implemented in function "save"
	//ToDo: move this functionality from functon "save" to function "saveobject"
	}
	
	/**                          
	* show possible action (form buttons)
	*
	* @access	public
 	*/
	function showActions($with_subobjects = false)
	{
		$notoperations = array();
		// NO PASTE AND CLEAR IF CLIPBOARD IS EMPTY
		if (empty($_SESSION["clipboard"]))
		{
			$notoperations[] = "paste";
			$notoperations[] = "clear";
		}
		// CUT COPY PASTE LINK DELETE IS NOT POSSIBLE IF CLIPBOARD IS FILLED
		if ($_SESSION["clipboard"])
		{
			$notoperations[] = "cut";
			$notoperations[] = "copy";
			$notoperations[] = "link";
		}

		$operations = array();

		$d = $this->objDefinition->getActions("grp");

		foreach ($d as $row)
		{
			if (!in_array($row["name"], $notoperations))
			{
				$operations[] = $row;
			}
		}

		if (count($operations) > 0)
		{
			foreach ($operations as $val)
			{


				$this->tpl->setCurrentBlock("tbl_action_btn");
				$this->tpl->setVariable("BTN_NAME", $val["lng"]);
				$this->tpl->setVariable("BTN_VALUE", $this->lng->txt($val["lng"]));
				$this->tpl->parseCurrentBlock();
			}
		}

		if ($with_subobjects == true)
		{
			$this->showPossibleSubObjects();
		}

		$this->tpl->setCurrentBlock("tbl_action_row");
		$this->tpl->parseCurrentBlock();
	}

	/**
	* show possible subobjects (pulldown menu)
	*
	* @access	public
 	*/

	function showPossibleSubObjects()
	{
		$d = $this->objDefinition->getSubObjects("grp");

		$import = false;

		if (count($d) > 0)
		{
			foreach ($d as $row)
			{
			    $count = 0;
				if ($row["max"] > 0)
				{
					//how many elements are present?
					for ($i=0; $i<count($this->data["ctrl"]); $i++)
					{
						if ($this->data["ctrl"][$i]["type"] == $row["name"])
						{
						    $count++;
						}
					}
				}
				if ($row["max"] == "" || $count < $row["max"])
				{
					$subobj[] = $row["name"];
					if($row["import"] == "1")	// import allowed?
					{
						$import = true;
					}
				}
			}
		}

		$import = false;
		if (is_array($subobj))
		{
			// show import button if at least one
			// object type can be imported
			if ($import)
			{
				$this->tpl->setCurrentBlock("import_object");
				$this->tpl->setVariable("BTN_IMP", "import");
				$this->tpl->setVariable("TXT_IMP", $this->lng->txt("import"));
				$this->tpl->parseCurrentBlock();
			}

			//build form
			$opts = ilUtil::formSelect(12,"new_type",$subobj);
			$this->tpl->setCurrentBlock("add_object");
			$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
			$this->tpl->setVariable("BTN_NAME", "create");
			$this->tpl->setVariable("TXT_ADD", $this->lng->txt("add"));
			$this->tpl->parseCurrentBlock();
		}
	}



	/**
	* create new object form
	*/
	function create()
	{
		//TODO: check the acces rights; compare class.ilObjectGUI.php

		global $rbacsystem;

			if(isset($_POST["new_type"]))
			{
				$new_type =  $_POST["new_type"];
			}else{
				$new_type =	 $_GET["type"];
			}

			$data = array();
			$data["fields"] = array();
			$data["fields"]["group_name"] = "";
			$data["fields"]["desc"] = "";


			$this->prepareOutput();
			$this->tpl->addBlockFile("CONTENT", "newgroup", "tpl.group_new.html");
			$this->tpl->setVariable("HEADER", $this->lng->txt("grp_new"));

			$node = $this->tree->getNodeData($_GET["parent_ref_id"]);
			$this->tpl->setVariable("TXT_PAGEHEADLINE", $node["title"]);

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);

			}

			$stati = array("group_status_public","group_status_private","group_status_closed");

			//build form
			$opts = ilUtil::formSelect(0,"group_status_select",$stati);

			$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
			$this->tpl->setVariable("TXT_GROUP_STATUS", $this->lng->txt("group_status"));
			$this->tpl->setVariable("FORMACTION", "group.php?gateway=true&cmd=save"."&ref_id=".$_GET["parent_ref_id"]."&new_type=".$new_type);
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
			$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
			$this->tpl->parseCurrentBlock();
			$this->tpl->show();
	}

	/**
	* function that executes "displayList"
	* function ist only executed if $_POST["cmd"] is set to "cancel"
	*/

	function cancelObject()
	{
		header("Location: group.php?cmd=displaylist&ref_id=".$_GET["ref_id"]);
	}


	/**
	* displays form in which the member-status can be changed
	* @access public
	*/
	function changeMemberObject()
	{
		global $ilias,$tpl;

		include_once "./classes/class.ilTableGUI.php";


		$member_ids = array();
		if(isset($_POST["user_id"]))
			$member_ids = $_POST["user_id"];
		else if(isset($_GET["mem_id"]))
			$member_ids[0] = $_GET["mem_id"];

		$newGrp = new ilObjGroup($_GET["ref_id"],true);
		$stati = array(0=>"grp_member_role",1=>"grp_admin_role");

		//build data structure
		foreach($member_ids as $member_id)
		{
			$member =& $ilias->obj_factory->getInstanceByObjId($member_id);
			$mem_status = $newGrp->getMemberStatus($member_id);

			$this->data["data"][$member->getId()]= array(
				"login"        => $member->getLogin(),
				"firstname"       => $member->getFirstname(),
				"lastname"        => $member->getLastname(),
				"grp_role" => ilUtil::formSelect($mem_status,"member_status_select[".$member->getId()."]",$stati,false,true)
				);
			unset($member);
		}


		$tab = array();
		$tab[0] = array ();
		$tab[0]["tab_cmd"] = 'cmd=groupmembers&ref_id='.$_GET["ref_id"];
		$tab[0]["ftabtype"] = 'tabinactive';
		$tab[0]["target"] = "bottom";
		$tab[0]["tab_text"] = 'group_members';


		$this->prepareOutput(false, $tab);
		$this->tpl->setVariable("HEADER", $this->lng->txt("obj_change"));

		$this->tpl->addBlockfile("CONTENT", "member_table", "tpl.table.html");

		//load template for table content data
		//$this->tpl->setVariable("FORMACTION", "group.php?ref_id=".$_GET["ref_id"]."&gateway=true");
		$this->tpl->setVariable("FORMACTION", "group.php?gateway=true&ref_id=".$_GET["ref_id"]."&obj_id=".$this->object->getId()."&tree_id=".$this->grp_tree->getTreeId()."&tree_table=grp_tree");
		$this->data["buttons"] = array( "updateMemberStatus"  => $this->lng->txt("confirm"),
						"canceldelete"  => $this->lng->txt("cancel"));

		$this->tpl->setCurrentBlock("tbl_action_row");
		$this->tpl->setVariable("COLUMN_COUNTS",4);
		$this->tpl->setVariable("TPLPATH",$this->ilias->tplPath);

		foreach ($this->data["buttons"] as $name => $value)
		{
			$this->tpl->setCurrentBlock("tbl_action_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}

		$offset = intval($_GET["offset"]);
		$limit = intval($_GET["limit"]);

		if ($limit == 0)
		{
			$limit = 10;	// TODO: move to user settings
		}
		if ($offset == "")
		{
			$offset = 0;	// TODO: move to user settings
		}

		// create table
		$tbl = new ilTableGUI($this->data["data"]);
		// title & header columns
		$tbl->setTitle($this->lng->txt("change member status"),"icon_usr_b.gif",$this->lng->txt("change member status"));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));
		$tbl->setHeaderNames(array($this->lng->txt("firstname"),$this->lng->txt("lastname"),$this->lng->txt("role"),$this->lng->txt("status")));
		$tbl->setHeaderVars(array("firstname","lastname","role","status"),array("ref_id"=>$_GET["ref_id"],"cmd"=>$_GET["cmd"]));

		$tbl->setColumnWidth(array("25%","25%","25%","25%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($limit);
		$tbl->setOffset($offset);
		$tbl->setMaxCount(count($this->data["data"]));

		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));

		// render table
		$tbl->render();
		$this->tpl->parseCurrentBlock();
		$this->tpl->show();
	}

	function updateMemberStatusObject()
	{
		global $rbacsystem;
		if(!$rbacsystem->checkAccess("write",$_GET["ref_id"]) )
		{
			$this->ilias->raiseError("No permissions to change member status!",$this->ilias->error_obj->WARNING);
		}
		else
		{
			$grp = new ilObjGroup($_GET["ref_id"]);

			if(isset($_POST["member_status_select"]))
			{
				foreach($_POST["member_status_select"] as $key=>$value)
				{
					$grp->setMemberStatus($key,$value);
				}
			}
		}
		//TODO: link back
		header("location: group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);


	}

	/**
	* displays confirmation formular with users that shall be DEassigned from group
	* @access public
	*/
	/*function deassignMemberObject()
	{
		$this->tpl->setVariable("FORMACTION", "group.php?ref_id=".$_GET["ref_id"]."&gateway=true");
		$this->tpl->setVariable("FORM_ACTION_METHOD", "post");
		$user_ids = array();

		if(isset($_POST["user_id"]))
		{
			$user_ids = $_POST["user_id"];


		}

		else if(isset($_GET["mem_id"]))$user_ids = $_GET["mem_id"];
		if(isset($user_ids))
		{
			$confirm = "confirmedDeassignMember";
			$cancel  = "canceldelete";
			$info	 = "info_delete_sure";
			$status  = "";
			$this->confirmation($user_ids, $confirm, $cancel, $info, $status);
		}
	}
*/
	/**
	* Deassign members from group
	* @access public
	*/
/*
	function confirmedDeassignMemberObject()
	{
		global $rbacsystem,$ilias;

		if(isset($_SESSION["saved_post"]) )
		{
			//$mem_id = $_SESSION["saved_post"][0];
			foreach($_SESSION["saved_post"]["user_id"] as $mem_id)
			{

				$newGrp = new ilObjGroup($_GET["ref_id"],true);
				//Check if user wants to skip himself
				//if($_SESSION["AccountId"] == $_GET["mem_id"])
				if($_SESSION["AccountId"] == $mem_id)
				{
					if($rbacsystem->checkAccess('leave',$_GET["ref_id"]))
					{
						//check ammount of members
						if(count($newGrp->getGroupMemberIds()) == 1)
						{
							if($rbacsystem->checkAccess('delete',$_GET["ref_id"]))
							{
								//GROUP DELETE
								$this->ilias->raiseError("Gruppe loeschen, da letztes Mitglied!",$this->ilias->error_obj->MESSAGE);
							}
							else
								$this->ilias->raiseError("You do not have the permissions to delete this group!",$this->ilias->error_obj->MESSAGE);
						}
						else
						{
							$role_id = $newGrp->getGroupRoleId($_SESSION["AccountId"]);

							$member_Obj =& $ilias->obj_factory->getInstanceByObjId($role_id);

							if(strcmp($member_Obj->getTitle(), "grp_Member")==0)
							{
								//if(!$newGrp->leaveGroup($_GET["mem_id"]))
								if(!$newGrp->leaveGroup($mem_id))
									$this->ilias->raiseError("Error while attempting to discharge user!",$this->ilias->error_obj->MESSAGE);
							}
							//if user is admin, he has to make another user become admin
							else if(strcmp($member_Obj->getTitle(),"grp_Administrator")==0 )
							{
								if(count($newGrp->getGroupAdminIds()) <= 1)
								{
									if(!isset($_POST["newAdmin_id"]) )
										$this->chooseNewAdmin();
									else
									{
										foreach($_POST["newAdmin_id"] as $newAdmin)
										{
											$newGrp->leaveGroup($newAdmin);
											$newGrp->joinGroup($newAdmin,1); //join as admin
										}
										//remove old admin from group
										//if(!$newGrp->leaveGroup($_GET["mem_id"]))
										if(!$newGrp->leaveGroup($mem_id))
											$this->ilias->raiseError("Error while attempting to discharge user!",$this->ilias->error_obj->MESSAGE);
									}
								}
								else if(!$newGrp->leaveGroup($_SESSION["AccountId"]))
									$this->ilias->raiseError("Error while attempting to discharge user!",$this->ilias->error_obj->MESSAGE);
							}
						}
					}
					else
						$this->ilias->raiseError("You are not allowed to leave this group!",$this->ilias->error_obj->MESSAGE);
				}
				//check if user has the permission to skip other groupmember
				else if($rbacsystem->checkAccess('write',$_GET["ref_id"]))
				{
					//if(!$newGrp->leaveGroup($_GET["mem_id"]))
					if(!$newGrp->leaveGroup($mem_id))
						$this->ilias->raiseError("Error while attempting to discharge user!",$this->ilias->error_obj->MESSAGE);

				}
				else
				{
					$this->ilias->raiseError("You are not allowed to discharge this group member!",$this->ilias->error_obj->MESSAGE);
				}
			}
		}
		header("Location: group.php?cmd=show_content&ref_id=".$_GET["ref_id"]);
	}

*/


	/*
	* save object
	*
	* @access	public
	*/
 	function save()
	{
		global $rbacsystem;

		//TODO: check the acces rights; compare class.ilObjectGUI.php

		// always call parent method first to create an object_data entry & a reference
		$groupObj = parent::saveObject();

		$rfoldObj = $groupObj->initRoleFolder();

		// setup rolefolder & default local roles if needed (see ilObjForum & ilObjForumGUI for an example)

		$groupObj->createDefaultGroupRoles($rfoldObj->getRefId());

		$groupObj->joinGroup($this->ilias->account->getId(),1); //join as admin=1

		//0=public,1=private,2=closed
		$groupObj->setGroupStatus($_POST["group_status_select"]);

		//$groupObj->createNewGroupTree($groupObj->getId(),$groupObj->getRefId());
		$groupObj->insertGroupNode($rfoldObj->getId(),$groupObj->getId(),$groupObj->getId(),$rfoldObj->getRefId());


		// always send a message
		sendInfo($this->lng->txt("grp_added"),true);

		header("location: group.php?cmd=choose_view&ref_id=".$_GET["ref_id"]);
		exit();
	}


	/**
	* displays search form for new users
	* @access public
	*/

	function newMembersObject()
	{
		$this->prepareOutput(false);
		$this->tpl->setVariable("HEADER", $this->lng->txt("add_member"));

		$this->tpl->addBlockFile("CONTENT", "newmember","tpl.grp_newmember.html");

		$this->tpl->setVariable("TXT_MEMBER_NAME", $this->lng->txt("Username"));
		$this->tpl->setVariable("TXT_STATUS", $this->lng->txt("Member Status"));

		$radio_member = ilUtil::formRadioButton($_POST["status"] ? 0:1,"status",0);
		$radio_admin = ilUtil::formRadioButton($_POST["status"] ? 1:0,"status",1);
		$this->tpl->setVariable("RADIO_MEMBER", $radio_member);
		$this->tpl->setVariable("RADIO_ADMIN", $radio_admin);
		$this->tpl->setVariable("TXT_MEMBER_STATUS", "Member");
		$this->tpl->setVariable("TXT_ADMIN_STATUS", "Admin");
		$this->tpl->setVariable("TXT_SEARCH", "Search");

		if(isset($_POST["search_user"]) )
			$this->tpl->setVariable("SEARCH_STRING", $_POST["search_user"]);
		else if(isset($_GET["search_user"]) )
			$this->tpl->setVariable("SEARCH_STRING", $_GET["search_user"]);

		$this->tpl->setVariable("FORMACTION_NEW_MEMBER", "group.php?type=grp&cmd=newMembersObject&ref_id=".$_GET["ref_id"]."&search_user=".$_POST["search_user"]);
		//$this->tpl->parseCurrentBlock();

		//query already started ?
		//$this->tpl->show();
		if( (isset($_POST["search_user"]) && isset($_POST["status"]) ) || ( isset($_GET["search_user"]) && isset($_GET["status"]) ) )//&& isset($_GET["ref_id"]) )
		{
			$member_ids = ilObjUser::searchUsers($_POST["search_user"] ? $_POST["search_user"] : $_GET["search_user"]);

			//INTERIMS SOLUTION
			$_SESSION["status"] = $_POST["status"];
			//var_dump($member_ids);
			foreach($member_ids as $member)
			{
				$this->data["data"][$member["usr_id"]]= array(
					"check"		=> ilUtil::formCheckBox(0,"user_id[]",$member["usr_id"]),
					"login"        => $member["login"],
					"firstname"       => $member["firstname"],
					"lastname"        => $member["lastname"]
					);

			}

			//display search results
			infoPanel();

			$this->tpl->addBlockfile("NEW_MEMBERS_TABLE", "member_table", "tpl.table.html");
			// load template for table content data

			$this->tpl->setVariable("FORMACTION", "group.php?gateway=true&ref_id=".$_GET["ref_id"]."&obj_id=".$this->object->getId()."&tree_id=".$this->grp_tree->getTreeId()."&tree_table=grp_tree");
			$this->tpl->setVariable("FORM_ACTION_METHOD", "post");

			$this->data["buttons"] = array( "assignMember"  => $this->lng->txt("assign"),
							"canceldelete"  => $this->lng->txt("cancel"));

			$this->tpl->setCurrentBlock("tbl_action_row");
			$this->tpl->setVariable("COLUMN_COUNTS",4);
			$this->tpl->setVariable("TPLPATH",$this->tplPath);

			foreach ($this->data["buttons"] as $name => $value)
			{
				$this->tpl->setCurrentBlock("tbl_action_btn");
				$this->tpl->setVariable("BTN_NAME",$name);
				$this->tpl->setVariable("BTN_VALUE",$value);
				$this->tpl->parseCurrentBlock();
			}

			//sort data array
			include_once "./include/inc.sort.php";
			$this->data["data"] = sortArray($this->data["data"], $_GET["sort_by"], $_GET["sort_order"]);

			$offset = intval($_GET["offset"]);
			$limit = intval($_GET["limit"]);

			if ($limit == 0)
			{
				$limit = 10;	// TODO: move to user settings
			}
			if ($offset == "")
			{
				$offset = 0;	// TODO: move to user settings
			}

			$output = array_slice($this->data["data"],$offset,$limit);

			// create table
			include_once "./classes/class.ilTableGUI.php";
			$tbl = new ilTableGUI($output);
			// title & header columns
			$tbl->setTitle($this->lng->txt("member list"),"icon_usr_b.gif",$this->lng->txt("member list"));
			$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));
			$tbl->setHeaderNames(array($this->lng->txt("check"),$this->lng->txt("username"),$this->lng->txt("firstname"),$this->lng->txt("lastname")));
			$tbl->setHeaderVars(array("check","login","firstname","lastname"),array("ref_id"=>$_GET["ref_id"],"cmd"=>$_GET["cmd"],"search_user"=>$_POST["search_user"] ? $_POST["search_user"] : $_GET["search_user"],"status"=>$_POST["status"] ? $_POST["status"] : $_GET["status"]));

			$tbl->setColumnWidth(array("5%","25%","35%","35%"));

			// control
			$tbl->setOrderColumn($_GET["sort_by"]);
			$tbl->setOrderDirection($_GET["sort_order"]);
			$tbl->setLimit($limit);
			$tbl->setOffset($offset);
			$tbl->setMaxCount(count($this->data["data"]));

			$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));

			// render table

			$tbl->render();

		}
		$this->tpl->show();
	}


	function confirmedAssignMemberObject()
	{
		if(isset($_SESSION["saved_post"]) && isset($_SESSION["status"]) )
		{
			//let new members join the group
			$newGrp = new ilObjGroup($this->object->getRefId(), true);
			foreach($_SESSION["saved_post"]["user_id"] as $new_member)
			{
				if(!$newGrp->joinGroup($new_member, $_SESSION["status"]) )
				{
					$this->ilias->raiseError("An Error occured while assigning user to group !",$this->ilias->error_obj->MESSAGE);

				}

			}
			unset($_SESSION["status"]);
		}
		//echo ($this->link_params);
		header("Location: group.php?cmd=show_content&".$this->link_params);

	}

	/**
	* displays confirmation formular with users that shall be assigned to group
	* @access public
	*/
	function assignMemberObject()
	{
		$user_ids = $_POST["user_id"];
		if(isset($user_ids))
		{
			$confirm = "confirmedAssignMember";
			$cancel  = "canceldelete";
			$info	 = "info_assign_sure";
			$status  = $_SESSION["status"];
			$this->confirmation($user_ids, $confirm, $cancel, $info, $status);
			$this->tpl->show();
			
		}
		else
		{
			sendInfo($this->lng->txt("You have to choose at least one user !"),true);
			header("location: group.php?cmd=show_content&ref_id=".$_GET["ref_id"]."target=\"bottom\"");
		}

	}

	/**
	* set Locator
	*
	* @param	object	tree object
	* @param	integer	reference id
	* @param	scriptanme that is used for linking; if not set group.php is used
	* @access	public
	*/
	function setLocator($a_tree = "", $a_id = "", $scriptname="group.php?")
	{


		if (!is_object($a_tree))
		{
			$a_tree =& $this->tree;
		}

		if (!($a_id))
		{
			$a_id = $_GET["ref_id"];
		}

		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");

		$path = $a_tree->getPathFull($a_id);

        	//check if object isn't in tree, this is the case if parent_parent is set
		// TODO: parent_parent no longer exist. need another marker
		if ($a_parent_parent)
		{
			//$subObj = getObject($a_ref_id);
			$subObj = & $this->ilias->obj_factory->getInstanceByRefId($a_ref_id);

			$path[] = array(
				"id"	 => $a_ref_id,
				"title"  => $this->lng->txt($subObj->getTitle())
				);
		}

		// this is a stupid workaround for a bug in PEAR:IT
		$modifier = 1;

		if (isset($_GET["obj_id"]))
		{
			$modifier = 1;
		}

		foreach ($path as $key => $row)
		{
			if ($key < count($path)-$modifier)
			{
				$this->tpl->touchBlock("locator_separator");
			}

			$this->tpl->setCurrentBlock("locator_item");
			$this->tpl->setVariable("ITEM", $row["title"]);
			//$this->tpl->setVariable("LINK_TARGET", $target);
			$this->tpl->setVariable("LINK_ITEM", $scriptname."ref_id=".$row["child"]);
			$this->tpl->setVariable("LINK_TARGET", "target=\"bottom\"");
			$this->tpl->parseCurrentBlock();

		}

		/*if (isset($_GET["obj_id"]))
		{
			$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($_GET["obj_id"]);

			$this->tpl->setCurrentBlock("locator_item");
			$this->tpl->setVariable("ITEM", $obj_data->getTitle());
			//$this->tpl->setVariable("LINK_TARGET", $target);
			$this->tpl->setVariable("LINK_ITEM", $scriptname."ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
			$this->tpl->parseCurrentBlock();
		}*/

		$this->tpl->setCurrentBlock("locator");

		if (DEBUG)
		{
			$debug = "DEBUG: <font color=\"red\">".$this->type."::".$this->id."::".$_GET["cmd"]."</font><br/>";
		}

		$prop_name = $this->objDefinition->getPropertyName($_GET["cmd"],$this->type);

		if ($_GET["cmd"] == "confirmDeleteAdm")
		{
			$prop_name = "delete_object";
		}

		$this->tpl->setVariable("TXT_LOCATOR",$debug.$this->lng->txt("locator"));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* DEPRECATED??? (shofmann)
	* displays groups
	* @access public
	*/
	function listGroups()
	{

		$this->getTemplateFile("overview", "grp");

		//$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");
		//$this->tpl->addBlockfile("CONTENT", "content", "tpl.grp_overview.html");

		$this->tpl->setCurrentBlock("content");

		$this->tpl->setVariable("TXT_GROUPS",  $this->lng->txt("groups"));
		$this->tpl->setCurrentBlock("tblheader");
		$this->tpl->setVariable("TXT_NAME",  $this->lng->txt("name"));
		$this->tpl->setVariable("TXT_DESC",  $this->lng->txt("description"));
		$this->tpl->setVariable("TXT_ROLE_IN_GROUP",  $this->lng->txt("role"));
		$this->tpl->setVariable("TXT_OWNER",  $this->lng->txt("owner"));
		$this->tpl->setVariable("TXT_CONTEXT",  $this->lng->txt("context"));

		$lr_arr = ilUtil::getObjectsByOperations('grp','visible');

		usort($lr_arr,"sortObjectsByTitle");

		$lr_num = count($lr_arr);

		if ($lr_num > 0)
		{
			// counter for rowcolor change

			$num = 0;
			//var_dump ($lr_arr);
			foreach ($lr_arr as $grp_data)
			{
				$this->tpl->setCurrentBlock("tblcontent");

				// change row color
				$this->tpl->setVariable("ROWCOL", ilUtil::switchColor($num,"tblrow2","tblrow1"));
				$num++;
				$newuser = new ilObjUser($grp_data["owner"]);
				$obj_link = "grp_details.php?ref_id=".$grp_data["ref_id"];
				$obj_icon = "icon_".$grp_data["type"]."_b.gif";

				$this->tpl->setVariable("GRP_NAME", $grp_data["title"]);
				$this->tpl->setVariable("GRP_LINK", $obj_link);
				/*if($lgrp_data["type"] == "grp")		// Test
				{
					//$this->tpl->setVariable("EDIT_LINK","content/lm_edit.php?lm_id=".$lr_data["obj_id"]);
					$this->tpl->setVariable("TXT_EDIT", "(".$this->lng->txt("edit").")");
					$this->tpl->setVariable("VIEW_LINK","content/lm_presentation.php?lm_id=".$grp_data["obj_id"]);
					$this->tpl->setVariable("TXT_VIEW", "(".$this->lng->txt("view").")");
				}*/
				//$this->tpl->setVariable("IMG", $obj_icon);
				//$this->tpl->setVariable("ALT_IMG", $lng->txt("obj_".$lr_data["type"]));
				$this->tpl->setVariable("GRP_DESC", $grp_data["desc"]);
				$this->tpl->setVariable("GRP_OWNER", $newuser->getFullname() );
				//$this->tpl->setVariable("STATUS", "N/A");
				//$this->tpl->setVariable("LAST_VISIT", "N/A");
				//$this->tpl->setVariable("LAST_CHANGE", ilFormat::formatDate($lr_data["last_update"]));
				$this->tpl->setVariable("GRP_CONTEXT", ilObjGroup::getContextPath2($grp_data["ref_id"]));

				$this->tpl->parseCurrentBlock("tblcontent");
			}
		}
	}
	
	/**
	* show details -> HOW NEEDS THIS METHOD
	* DEPRECATED??? (shofmann)
	* @access public
	*/
	function showDetails()
	{
		$this->getTemplateFile("details", "grp");
		//$this->tpl->addBlockFile("CONTENT", "content", "tpl.grp_details.html");
		$this->tpl->addBlockFile("BUTTONS", "buttons", "tpl.buttons.html");

		$this->tpl->setVariable("TXT_GRP_TITLE", $this->lng->txt("group_members"));
		$this->tpl->setCurrentBlock("groupheader");

		$this->tpl->setVariable("TXT_NAME", $this->lng->txt("name"));
		$this->tpl->setVariable("TXT_DESC", $this->lng->txt("description"));
		$this->tpl->setVariable("TXT_OWNER", $this->lng->txt("owner"));
		$this->tpl->setVariable("TXT_ROLE_IN_GROUP", $this->lng->txt("role"));
		$this->tpl->parseCurrentBlock("grouphesder");
		$lr_arr = array();
		$objects = $this->tree->getChilds($this->object->getId(),"title");
		//var_dump ($objects);
		if (count($objects) > 0)
		{
			foreach ($objects as $key => $object)
			{
				//var_dump ($object);
				if ($object["type"] == "le")// && $rbacsystem->checkAccess('visible',$objects["child"]))
				{

					$lr_arr[$key] = $object;
					//var_dump ($lr_arr);
				}
			}
		}
		//var_dump ($lr_arr);
		$maxcount = count($lr_arr);
		include_once "./include/inc.sort.php";
		$lr_arr = sortArray($lr_arr,$_GET["sort_by"],$_GET["sort_order"]);
		//$lr_arr = array_slice($lr_arr,$offset,$limit);


			$this->tpl->setCurrentBlock("loheader");
			$this->tpl->setVariable("TXT_LO_TITLE", $this->lng->txt("lo"));
			$this->tpl->setVariable("TXT_LO_NAME", $this->lng->txt("name"));
			$this->tpl->setVariable("TXT_LO_DESC", $this->lng->txt("description"));
			$this->tpl->setVariable("TXT_LO_OWNER", $this->lng->txt("owner"));
			$this->tpl->setVariable("TXT_LO_LAST_CHANGE", $this->lng->txt("last_change"));

		//var_dump ($lr_arr);
		$num = 0;
		foreach ($lr_arr as $lr_data)
		{
			$this->tpl->setCurrentBlock("locontent");
			//var_dump ($lr_data);
			// change row color
			$this->tpl->setVariable("ROWCOL", ilUtil::switchColor($num,"tblrow2","tblrow1"));
			$num++;

			//$obj_link = "lo_view.php?lm_id=".$lr_data["ref_id"];
			$obj_icon = "icon_".$lr_data["type"]."_b.gif";

			$this->tpl->setVariable("LO_DESC", $lr_data["description"]);
			$this->tpl->setVariable("LO_NAME", $lr_data["title"]);
			$this->tpl->setVariable("LO_LAST_CHANGE", ilFormat::formatDate($lr_data["last_update"]));
			$this->tpl->setVariable("LO_CONTEXTPATH", ilObjGroup::getContextPath2($lr_data["ref_id"]));
			$this->tpl->parseCurrentBlock("locontent");
		}
	}
}
?>
