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
* class ilobjcourse
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* This class is aggregated in folders, groups which have a parent course object
* Since it is something like an interface, all varirables, methods have there own name space (names start with cci) to avoid collisions
* 
* @extends Object
* @package ilias-core
*/

class ilCourseContentInterface
{
	var $cci_course_obj;
	var $cci_course_id;
	var $cci_ref_id;
	var $cci_client_class;
	
	
	function cci_init(&$client_class,$a_ref_id)
	{
		$this->cci_ref_id = $a_ref_id;
		$this->cci_read();
		$this->cci_client_class = strtolower(get_class($client_class));

		$this->cci_course_obj =& ilObjectFactory::getInstanceByRefId($this->cci_course_id);
		$this->cci_course_obj->initCourseItemObject($this->cci_ref_id);
		
		return true;
	}

	function cci_view()
	{
		include_once "./classes/class.ilRepositoryExplorer.php";

		global $rbacsystem;

		$write_perm = $rbacsystem->checkAccess("write",$this->cci_ref_id);
			

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_view.html",true);
		#$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		
		if($write_perm)
		{
			$items = $this->cci_course_obj->items_obj->getAllItems();
		}
		else
		{
			$items = $this->cci_course_obj->items_obj->getItems();
		}
		
		// NO ITEMS FOUND
		if(!count($items))
		{
			sendInfo($this->lng->txt("crs_no_items_found"));

			return true;
		}

		$tpl =& new ilTemplate("tpl.table.html", true, true);

		$maxcount = count($items);

		$cont_arr = array_slice($items, $_GET["offset"], $_GET["limit"]);
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.crs_content_row.html",true);
		$cont_num = count($cont_arr);

		// render table content data
		if ($cont_num > 0)
		{
			// counter for rowcolor change
			$num = 0;
			foreach ($cont_arr as $cont_data)
			{
				if ($rbacsystem->checkAccess('read',$cont_data["ref_id"]))
				{
					$obj_link = ilRepositoryExplorer::buildLinkTarget($cont_data["child"],$cont_data["type"]);
					$tpl->setCurrentBlock("crs_read");
					$tpl->setVariable("READ_TITLE", $cont_data["title"]);
					$tpl->setVariable("READ_LINK", $obj_link);
					$tpl->setVariable("READ_TARGET", "bottom");
					$tpl->parseCurrentBlock();
				}
				else
				{
					$tpl->setCurrentBlock("crs_visible");
					$tpl->setVariable("VIEW_TITLE", $cont_data["title"]);
					$tpl->parseCurrentBlock();
				}

				// OPTIONS
				if($write_perm)
				{
					if($this->cci_course_obj->getOrderType() == $this->cci_course_obj->SORT_MANUAL)
					{
						if($num != 0)
						{
							$tmp_array["gif"] = ilUtil::getImagePath("a_up.gif");
							$tmp_array["lng"] = $this->lng->txt("crs_move_up");
							$this->ctrl->setParameter($this,"item_id",$cont_data["child"]);
							$tmp_array["lnk"] = $this->ctrl->getLinkTarget($this,"cciMove");
							$tmp_array["tar"] = "";

							$images[] = $tmp_array;
						}
						if($num != count($cont_arr) - 1)
						{
							$tmp_array["gif"] = ilUtil::getImagePath("a_down.gif");
							$tmp_array["lng"] = $this->lng->txt("crs_move_down");
							$this->ctrl->setParameter($this,"item_id",-$cont_data["child"]);
							$tmp_array["lnk"] = $this->ctrl->getLinkTarget($this,"cciMove");
							$tmp_array["tar"] = "";

							$images[] = $tmp_array;
						}
					}
					$tmp_array["gif"] = ilUtil::getImagePath("edit.gif");
					$tmp_array["lng"] = $this->lng->txt("edit");
					$this->ctrl->setParameter($this,"item_id",$cont_data["child"]);
					$tmp_array["lnk"] = $this->ctrl->getLinkTarget($this,"cciEdit");
					$tmp_array["tar"] = "";
					
					$images[] = $tmp_array;
					
					if ($rbacsystem->checkAccess('delete',$cont_data["ref_id"]))
					{
						$tmp_array["gif"] = ilUtil::getImagePath("delete.gif");
						$tmp_array["lng"] = $this->lng->txt("delete");
						$this->ctrl->setParameterByClass("ilRepositoryGUI","ref_id",$cont_data["child"]);
						$tmp_array["lnk"] = $this->ctrl->getLinkTargetByClass("ilRepositoryGUI","delete");
						$tmp_array["tar"] = "";

						$images[] = $tmp_array;
					}
					
					foreach($images as $key => $image)
					{
						$tpl->setCurrentBlock("img");
						$tpl->setVariable("IMG_TYPE",$image["gif"]);
						$tpl->setVariable("IMG_ALT",$image["lng"]);
						$tpl->setVariable("IMG_LINK",$image["lnk"]);
						$tpl->setVariable("IMG_TARGET",$image["tar"]);
						$tpl->parseCurrentBlock();
					}
					unset($images);

					$tpl->setCurrentBlock("options");
					$tpl->setVariable("OPT_ROWCOL", ilUtil::switchColor($num,"tblrow2","tblrow1"));
					$tpl->parseCurrentBlock();
				}

				$tpl->setCurrentBlock("tbl_content");

				// change row color
				$tpl->setVariable("ROWCOL", ilUtil::switchColor($num,"tblrow2","tblrow1"));
				$tpl->setVariable("TYPE_IMG", ilUtil::getImagePath("icon_".$cont_data["type"].".gif"));
				$tpl->setVariable("ALT_IMG", $this->lng->txt("obj_".$cont_data["type"]));
				$tpl->setVariable("DESCRIPTION", $cont_data["description"]);

				// ACTIVATION
				if($cont_data["activation_unlimited"])
				{
					$txt = $this->lng->txt("unlimited");
				}
				else
				{
					$txt = $this->lng->txt("until").": ".strftime("%c",$cont_data["activation_end"]);
				}
				$tpl->setVariable("ACTIVATION_END",$txt);

				$tpl->parseCurrentBlock();
				$num++;
			}
		}

		// create table
		$tbl = new ilTableGUI();

		// title & header columns
		$tbl->setTitle($this->lng->txt("course_content"),"icon_crs_b.gif",$this->lng->txt("courses"));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));

		if($write_perm)
		{
			$tbl->setHeaderNames(array($this->lng->txt("type"),$this->lng->txt("title"),
									   $this->lng->txt("activation"),$this->lng->txt("options")));
			$tbl->setHeaderVars(array("type","title","activation","options"), 
								array("ref_id" => $this->ref_id,"cmdClass" => "ilobjcoursegui","cmdNode" => $_GET["cmdNode"]));
			$tbl->setColumnWidth(array("1%","69%","20%","10%"));
		}
		else
		{
			$tbl->setHeaderNames(array($this->lng->txt("type"),$this->lng->txt("title"),
									   $this->lng->txt("activation")));
			$tbl->setHeaderVars(array("type","title","activation","options"), 
								array("ref_id" => $this->ref_id,"cmdClass" => "ilobjcoursegui","cmdNode" => $_GET["cmdNode"]));
			$tbl->setColumnWidth(array("1%","89%","20%"));
		}

		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($maxcount);

		// footer
		$tbl->disable("footer");

		// render table
		$tbl->setTemplate($tpl);
		$tbl->render();

		$this->tpl->setVariable("CONTENT_TABLE", $tpl->get());

		return true;
	}

	function cci_edit()
	{
		global $rbacsystem;

		if(!$rbacsystem->checkAccess("write", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read"),$this->ilias->error_obj->MESSAGE);
		}
		if(!isset($_GET["item_id"]))
		{
			sendInfo($this->lng->txt("crs_no_item_id_given"));
			$this->cci_view();

			return false;
		}
		
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.crs_editItem.html",true);
		$this->ctrl->setParameter($this,"item_id",$_GET["item_id"]);
		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));

		$item_data = $this->cci_course_obj->items_obj->getItem((int) $_GET["item_id"]);

		$tmp_obj = ilObjectFactory::getInstanceByRefId($_GET["item_id"]);
		$title = $tmp_obj->getTitle();

		// LOAD SAVED DATA IN CASE OF ERROR
		$activation_unlimited = $_SESSION["error_post_vars"]["crs"]["activation_unlimited"] ? 
			1 : 
			(int) $item_data["activation_unlimited"];

		$activation_start = $_SESSION["error_post_vars"]["crs"]["activation_start"] ? 
			$this->cciToUnix($_SESSION["error_post_vars"]["crs"]["activation_start"]) :
			$item_data["activation_start"];
		
		$activation_end = $_SESSION["error_post_vars"]["crs"]["activation_end"] ? 
			$this->cciToUnix($_SESSION["error_post_vars"]["crs"]["activation_end"]) :
			$item_data["activation_end"];
		
		// SET TEXT VARIABLES
		$this->tpl->setVariable("ALT_IMG",$this->lng->txt("obj_".$tmp_obj->getType()));
		$this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath("icon_".$tmp_obj->getType().".gif"));
		$this->tpl->setVariable("TITLE",$title);
		$this->tpl->setVariable("TXT_ACTIVATION",$this->lng->txt("activation"));
		$this->tpl->setVariable("TXT_ACTIVATION_UNLIMITED",$this->lng->txt("activation_unlimited"));
		$this->tpl->setVariable("TXT_ACTIVATION_START",$this->lng->txt("activation_start"));
		$this->tpl->setVariable("TXT_ACTIVATION_END",$this->lng->txt("activation_end"));
		$this->tpl->setVariable("CMD_SUBMIT","cciUpdate");
		$this->tpl->setVariable("TXT_CANCEL",$this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_SUBMIT",$this->lng->txt("submit"));
		
		$this->tpl->setVariable("ACTIVATION_UNLIMITED",ilUtil::formCheckbox($activation_unlimited,"crs[activation_unlimited]",1));


		$this->tpl->setVariable("SELECT_ACTIVATION_START_DAY",$this->cciGetDateSelect("day","crs[activation_start][day]",
																					 date("d",$activation_start)));
		$this->tpl->setVariable("SELECT_ACTIVATION_START_MONTH",$this->cciGetDateSelect("month","crs[activation_start][month]",
																					   date("m",$activation_start)));
		$this->tpl->setVariable("SELECT_ACTIVATION_START_YEAR",$this->cciGetDateSelect("year","crs[activation_start][year]",
																					  date("Y",$activation_start)));
		$this->tpl->setVariable("SELECT_ACTIVATION_START_HOUR",$this->cciGetDateSelect("hour","crs[activation_start][hour]",
																					  date("G",$activation_start)));
		$this->tpl->setVariable("SELECT_ACTIVATION_START_MINUTE",$this->cciGetDateSelect("minute","crs[activation_start][minute]",
																					  date("i",$activation_start)));
		$this->tpl->setVariable("SELECT_ACTIVATION_END_DAY",$this->cciGetDateSelect("day","crs[activation_end][day]",
																				   date("d",$activation_end)));
		$this->tpl->setVariable("SELECT_ACTIVATION_END_MONTH",$this->cciGetDateSelect("month","crs[activation_end][month]",
																					 date("m",$activation_end)));
		$this->tpl->setVariable("SELECT_ACTIVATION_END_YEAR",$this->cciGetDateSelect("year","crs[activation_end][year]",
																					date("Y",$activation_end)));
		$this->tpl->setVariable("SELECT_ACTIVATION_END_HOUR",$this->cciGetDateSelect("hour","crs[activation_end][hour]",
																					  date("G",$activation_end)));
		$this->tpl->setVariable("SELECT_ACTIVATION_END_MINUTE",$this->cciGetDateSelect("minute","crs[activation_end][minute]",
																					  date("i",$activation_end)));

	}

	function cci_update()
	{
		if(!isset($_GET["item_id"]))
		{
			echo "CourseContentInterface: No item_id given!";
			exit;
		}

		$this->cci_course_obj->items_obj->setActivationUnlimitedStatus((bool) $_POST["crs"]["activation_unlimited"]);
		$this->cci_course_obj->items_obj->setActivationStart($this->cciToUnix($_POST["crs"]["activation_start"]));
		$this->cci_course_obj->items_obj->setActivationEnd($this->cciToUnix($_POST["crs"]["activation_end"]));
		
		if(!$this->cci_course_obj->items_obj->validateActivation())
		{
			sendInfo($this->cci_course_obj->getMessage());
			$this->cci_edit();

			return true;
		}
		$this->cci_course_obj->items_obj->update((int) $_GET["item_id"]);
		$this->cci_view();

		return true;
	}
			
	function cci_move()
	{
		if($_GET["item_id"] > 0)
		{
			$this->cci_course_obj->items_obj->moveUp((int) $_GET["item_id"]);
		}
		else
		{
			$this->cci_course_obj->items_obj->moveDown((int) -$_GET["item_id"]);
		}
		sendInfo($this->lng->txt("crs_moved_item"));

		$this->cci_view();

		return true;
	}

	// PRIVATE

	function cci_read()
	{
		global $tree;

		if(!$this->cci_course_id = $tree->checkForParentType($this->cci_ref_id,'crs'))
		{
			echo "ilCourseContentInterface: Cannot find course object";
			exit;
		}
		return true;
	}

	function cciToUnix($a_time_arr)
	{
		return mktime($a_time_arr["hour"],
					  $a_time_arr["minute"],
					  $a_time_arr["second"],
					  $a_time_arr["month"],
					  $a_time_arr["day"],
					  $a_time_arr["year"]);
	}
	function cciGetDateSelect($a_type,$a_varname,$a_selected)
	{
		switch($a_type)
		{
			case "minute":
				for($i=0;$i<=60;$i++)
				{
					$days[$i] = $i < 10 ? "0".$i : $i;
				}
				return ilUtil::formSelect($a_selected,$a_varname,$days,false,true);

			case "hour":
				for($i=0;$i<24;$i++)
				{
					$days[$i] = $i < 10 ? "0".$i : $i;
				}
				return ilUtil::formSelect($a_selected,$a_varname,$days,false,true);

			case "day":
				for($i=1;$i<32;$i++)
				{
					$days[$i] = $i < 10 ? "0".$i : $i;
				}
				return ilUtil::formSelect($a_selected,$a_varname,$days,false,true);
			
			case "month":
				for($i=1;$i<13;$i++)
				{
					$month[$i] = $i < 10 ? "0".$i : $i;
				}
				return ilUtil::formSelect($a_selected,$a_varname,$month,false,true);

			case "year":
				for($i = date("Y",time());$i < date("Y",time()) + 3;++$i)
				{
					$year[$i] = $i;
				}
				return ilUtil::formSelect($a_selected,$a_varname,$year,false,true);
		}
	}
}
?>