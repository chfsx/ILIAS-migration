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
* Class ilObjSysUserTrackingGUI
*
* @author Arlon Yin <arlon_yin@sina.com.cn>
* @author Alex Killing <alex.killing@gmx.de>
*
* @version $Id$
*
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "class.ilObjectGUI.php";
require_once "tracking/classes/class.ilUserTracking.php";

class ilObjSysUserTrackingGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	var $conditions;

	function ilObjSysUserTrackingGUI($a_data,$a_id,$a_call_by_reference)
	{
		global $rbacsystem;

		$this->type = "trac";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference);

		if (!$rbacsystem->checkAccess('read',$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_read_track"),$this->ilias->error_obj->WARNING);
		}

	}

	/**
	* save object
	* @access	public
	*/
	function saveObject()
	{
		global $rbacadmin;

		// create and insert forum in objecttree
		$newObj = parent::saveObject();

		// setup rolefolder & default local roles
		//$roles = $newObj->initDefaultRoles();

		// ...finally assign role to creator of object
		//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "y");

		// put here object specific stuff

		// always send a message
		sendInfo($this->lng->txt("object_added"),true);

		header("Location:".$this->getReturnLocation("save","adm_object.php?".$this->link_params));
		exit();
	}


	/**
	* display tracking settings form
	*/
	function settingsObject()
	{
		global $tpl,$lng,$ilias;

		// tracking settings
		$tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.tracking_settings.html");
		$tpl->setVariable("FORMACTION", "adm_object?ref_id=".$_GET["ref_id"].
			"&cmd=gateway");
		$tpl->setVariable("TXT_TRACKING_SETTINGS", $this->lng->txt("tracking_settings"));
		$tpl->setVariable("TXT_ACTIVATE_TRACKING", $this->lng->txt("activate_tracking"));
		$tpl->setVariable("TXT_USER_RELATED_DATA", $this->lng->txt("save_user_related_data"));
		$tpl->setVariable("TXT_NUMBER_RECORDS", $this->lng->txt("number_of_records"));
		$tpl->setVariable("NUMBER_RECORDS", $this->object->getRecordsTotal());
		$tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));

		if($this->object->_enabledTracking())
		{
			$this->tpl->setVariable("ACT_TRACK_CHECKED", " checked=\"1\" ");
		}

		if($this->object->_enabledUserRelatedData())
		{
			$this->tpl->setVariable("USER_RELATED_CHECKED", " checked=\"1\" ");
		}

		$tpl->parseCurrentBlock();

	}

	/**
	* save user tracking settings
	*/
	function saveSettingsObject()
	{
		// (de)activate tracking
		if ($_POST["act_track"] == "y")
		{
			$this->object->enableTracking(true);
		}
		else
		{
			$this->object->enableTracking(false);
		}

		// (de)activate tracking of user related data
		if ($_POST["user_related"] == "y")
		{
			$this->object->enableUserRelatedData(true);
		}
		else
		{
			$this->object->enableUserRelatedData(false);
		}

		sendinfo($this->lng->txt("msg_obj_modified"), true);
		ilUtil::redirect("adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=settings");
	}

	/**
	* display tracking settings form
	*/
	function manageDataObject()
	{
		global $tpl,$lng,$ilias;

		// tracking settings
		$tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.tracking_manage_data.html");
		$tpl->setVariable("FORMACTION", "adm_object?ref_id=".$_GET["ref_id"].
			"&cmd=gateway");
		$tpl->setVariable("TXT_TRACKING_DATA", $this->lng->txt("tracking_data"));
		$tpl->setVariable("TXT_MONTH", $lng->txt("month"));
		$tpl->setVariable("TXT_NUMBER_OF_ACC", $lng->txt("number_of_accesses"));
		$tpl->setVariable("TXT_DELETE_OLDER", $lng->txt("delete"));
		$overw = $this->object->getMonthTotalOverview();
		foreach($overw as $month)
		{
			$tpl->setCurrentBlock("load_row");
			$rcol = ($rcol != "tblrow1") ? "tblrow1" : "tblrow2";
			$tpl->setVariable("ROWCOL", $rcol);
			$tpl->setVariable("VAL_MONTH", $month["month"]);
			$tpl->setVariable("VAL_NUMBER_OF_ACC", $month["cnt"]);
			$tpl->parseCurrentBlock();
		}
		$tpl->parseCurrentBlock();
	}

	/**
	* confirm delete tracking data
	*/
	function confirmDeletionDataObject()
	{
		global $tpl, $lng, $rbacsystem;

		if (!$rbacsystem->checkAccess('delete',$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_delete_track"),$this->ilias->error_obj->WARNING);
		}

		if (!isset($_POST["month"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		$nr = $this->object->getTotalOlderThanMonth($_POST["month"]);
		$tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.tracking_confirm_data_deletion.html");
		$tpl->setVariable("FORMACTION", "adm_object?ref_id=".$_GET["ref_id"].
			"&cmd=gateway&month=".$_POST["month"]);
		$tpl->setVariable("TXT_CONFIRMATION", $this->lng->txt("tracking_data_del_confirm"));
		$tpl->setVariable("TXT_MONTH", $lng->txt("month"));
		$tpl->setVariable("VAL_MONTH", $_POST["month"]);
		$tpl->setVariable("TXT_NUMBER_OF_RECORDS", $lng->txt("number_of_records"));
		$tpl->setVariable("VAL_NUMBER_OF_RECORDS", $nr);
		$tpl->setVariable("TXT_NUMBER_OF_ACC", $lng->txt("number_of_accesses"));
		$tpl->setVariable("TXT_DELETE_DATA", $lng->txt("delete_tr_data"));
		$tpl->setVariable("TXT_CANCEL", $lng->txt("cancel"));
	}

	/**
	* cancel deletion of tracking data
	*/
	function cancelDeleteDataObject()
	{
		sendInfo($this->lng->txt("msg_cancel"),true);

		ilUtil::redirect("adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=manageData");
	}

	/**
	* delete tracking data
	*/
	function deleteDataObject()
	{
		global $rbacsystem;
		
		if (!$rbacsystem->checkAccess('read',$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_delete_track"),$this->ilias->error_obj->WARNING);
		}

		$this->object->deleteTrackingDataBeforeMonth($_GET["month"]);

		sendInfo($this->lng->txt("tracking_data_deleted"),true);
		ilUtil::redirect("adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=manageData");
	}

	/**
	* display tracking query form
	*/
	function trackingDataQueryFormObject()
	{
		global $tpl,$lng,$ilias;
		$year = array(2004,2005,2006,2007);
		$month = array(1,2,3,4,5,6,7,8,9,10,11,12);
		$day = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31);
		//subject module
		$tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.usr_tracking.html");

		if (ilObjSysUserTracking::_enabledUserRelatedData())
		{
			$tpl->setCurrentBlock("user_stat");
			$tpl->setVariable("TXT_STATISTIC_U", $lng->txt("user_access"));
			if ($_SESSION["il_track_stat"] == "u")
			{
				$tpl->setVariable("U_CHK", " checked=\"1\" ");
			}
			$tpl->parseCurrentBlock();
		}

		//$tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		$tpl->setVariable("SEARCH_ACTION", "adm_object.php?ref_id=".$_GET["ref_id"].
			"&cmd=gateway");
		$tpl->setVariable("TXT_TRACKING_DATA", $lng->txt("tracking_data"));
		$tpl->setVariable("TXT_TIME_SEGMENT", $lng->txt("time_segment"));
		$tpl->setVariable("TXT_STATISTIC", $lng->txt("statistic"));
		$tpl->setVariable("TXT_STATISTIC_H", $lng->txt("hours_of_day"));
		$tpl->setVariable("TXT_STATISTIC_D", $lng->txt("days_of_period"));
		$tpl->setVariable("TXT_USER_LANGUAGE",$lng->txt("user_language"));
		$tpl->setVariable("TXT_LM",$lng->txt("lm"));
		$tpl->setVariable("TXT_SHOW_TR_DATA",$lng->txt("query_data"));
		$tpl->setVariable("TXT_TRACKED_OBJECTS",$lng->txt("tracked_objects"));

		$languages = $lng->getInstalledLanguages();

		// get all learning modules
		// $lms = ilObject::_getObjectsDataForType("lm", true);
		$authors = ilObjSysUserTracking::allAuthor("usr","lm");
		if(count($authors)>0)
		{
			$tpl->setCurrentBlock("javascript");
			$tpl->setVariable("ALL_LMS", $this->lng->txt("all_lms"));
			foreach ($authors as $author)
			{
				$lms = ilObjSysUserTracking::authorLms($author["obj_id"],"lm");
				//echo count($lms);
				foreach ($lms as $lm)
				{
					$tpl->setCurrentBlock("select_value");
					$tpl->setVariable("VALUE", $author["title"]);
					$tpl->setVariable("LMVALUE", $lm["title"]);
					$tpl->parseCurrentBlock();
				}
			
			}
			$tpl->parseCurrentBlock();
		}
		$authors1 = ilObjSysUserTracking::allAuthor("usr","tst");
		if(count($authors1)>0)
		{
			$tpl->setCurrentBlock("javascript1");
			$tpl->setVariable("ALL_TSTS", $this->lng->txt("all_tsts"));
			foreach ($authors1 as $author1)
			{
				$tsts = ilObjSysUserTracking::authorLms($author1["obj_id"],"tst");
				foreach ($tsts as $tst)
				{
					$tpl->setCurrentBlock("select_value1");
					$tpl->setVariable("VALUE1", $author1["title"]);
					$tpl->setVariable("TSTVALUE", $tst["title"]);
					$tpl->parseCurrentBlock();
				}
			}
			$tpl->parseCurrentBlock();
		}
		foreach($year as $key)
		{
			$tpl->setCurrentBlock("fromyear_selection");
			$tpl->setVariable("YEARFR", $key);
			$tpl->setVariable("YEARF", $key);
			if ($_SESSION["il_track_yearf"] == $key)
			{
				$tpl->setVariable("YEARF_SEL", " selected=\"1\" ");
			}
			$tpl->parseCurrentBlock();
		}
		foreach($month as $key)
		{
			$tpl->setCurrentBlock("frommonth_selection");
			$tpl->setVariable("MONTHFR", $key);
			$tpl->setVariable("MONTHF", $key);
			if ($_SESSION["il_track_monthf"] == $key)
			{
				$tpl->setVariable("MONTHF_SEL", " selected=\"1\" ");
			}
			$tpl->parseCurrentBlock();
		}
		foreach($day as $key)
		{
			$tpl->setCurrentBlock("fromday_selection");
			$tpl->setVariable("DAYFR", $key);
			$tpl->setVariable("DAYF", $key);
			if ($_SESSION["il_track_dayf"] == $key)
			{
				$tpl->setVariable("DAYF_SEL", " selected=\"1\" ");
			}
			$tpl->parseCurrentBlock();
		}
		foreach($day as $key)
		{
			$tpl->setCurrentBlock("today_selection");
			$tpl->setVariable("DAYTO", $key);
			$tpl->setVariable("DAYT", $key);
			if ($_SESSION["il_track_dayt"] == $key)
			{
				$tpl->setVariable("DAYT_SEL", " selected=\"1\" ");
			}
			$tpl->parseCurrentBlock();
		}
		foreach($month as $key)
		{
			$tpl->setCurrentBlock("tomonth_selection");
			$tpl->setVariable("MONTHTO", $key);
			$tpl->setVariable("MONTHT", $key);
			if ($_SESSION["il_track_montht"] == $key)
			{
				$tpl->setVariable("MONTHT_SEL", " selected=\"1\" ");
			}
			$tpl->parseCurrentBlock();
		}
		foreach($year as $key)
		{
			$tpl->setCurrentBlock("toyear_selection");
			$tpl->setVariable("YEARTO", $key);
			$tpl->setVariable("YEART", $key);
			if ($_SESSION["il_track_yeart"] == $key)
			{
				$tpl->setVariable("YEART_SEL", " selected=\"1\" ");
			}
			$tpl->parseCurrentBlock();
		}
		// language selection
		$tpl->setCurrentBlock("language_selection");
		$tpl->setVariable("LANG", $lng->txt("any_language"));
		$tpl->setVariable("LANGSHORT", "0");
		$tpl->parseCurrentBlock();
		foreach ($languages as $lang_key)
		{
			$tpl->setCurrentBlock("language_selection");
			$tpl->setVariable("LANG", $lng->txt("lang_".$lang_key));
			$tpl->setVariable("LANGSHORT", $lang_key);
			if ($_SESSION["il_track_language"] == $lang_key)
			{
				$tpl->setVariable("LANG_SEL", " selected=\"1\" ");
			}
			$tpl->parseCurrentBlock();
		}

		// statistic type
		if ($_SESSION["il_track_stat"] == "d")
		{
			$tpl->setVariable("D_CHK", " checked=\"1\" ");
		}
		else if ($_SESSION["il_track_stat"] != "u")
		{
			$tpl->setVariable("H_CHK", " checked=\"1\" ");
		}

		// tracked object type
		if ($_SESSION["il_object_type"] == "tst")
		{
			$tpl->setVariable("TST_CHK", " checked=\"1\" ");
		}
		else
		{
			$tpl->setVariable("LM_CHK", " checked=\"1\" ");
		}

		// author selection
		$tpl->setCurrentBlock("author_selection");
		$tpl->setVariable("AUTHOR", 0);
		$tpl->setVariable("AUTHOR_SELECT", $this->lng->txt("all_authors"));
		$tpl->parseCurrentBlock();
		foreach ($authors as $author)
		{
			$tpl->setCurrentBlock("author_selection");
			$tpl->setVariable("AUTHOR", $author["title"]);
			$tpl->setVariable("AUTHOR_SELECT", $author["title"]);
			if ($_SESSION["il_track_author"] == $author["title"])
			{
				$tpl->setVariable("AUTHOR_SEL", " selected=\"1\" ");
			}
			$tpl->parseCurrentBlock();
		}
		$tpl->setCurrentBlock("author_selection_tst");
		$tpl->setVariable("AUTHOR1", 0);
		$tpl->setVariable("AUTHOR1_SELECT", $this->lng->txt("all_authors"));
		$tpl->parseCurrentBlock();
		foreach ($authors1 as $author1)
		{
			$tpl->setCurrentBlock("author_selection_tst");
			$tpl->setVariable("AUTHOR1", $author1["title"]);
			$tpl->setVariable("AUTHOR1_SELECT", $author1["title"]);
			if ($_SESSION["il_track_author1"] == $author1["title"])
			{
				$tpl->setVariable("AUTHOR1_SEL", " selected=\"1\" ");
			}
			$tpl->parseCurrentBlock();
		}
		//test module
		//arlon modified,if there isn't test of the login,the test tracking module will not display!
		$usertracking = new ilUserTracking();
		$result_test = $usertracking->getTestId($_SESSION["AccountId"]);

		$tpl->setVariable("TXT_TEST",$lng->txt("test"));
		$tracking = new ilUserTracking();

		//$test = $tracking->TestTitle($_SESSION["AccountId"]);

		$tsts = ilObject::_getObjectsDataForType($type, true);
		$tpl->setCurrentBlock("test_selection");
		$tpl->setVariable("TEST", 0);
		$tpl->setVariable("TEST_SELECT", $this->lng->txt("all_tsts"));
		$tpl->parseCurrentBlock();
		foreach($tsts as $tst)
		{
			$tpl->setCurrentBlock("test_selection");
			$tpl->setVariable("TEST", $tst["id"]);
			$tpl->setVariable("TEST_SELECT", $tst["title"]." [".$tst["id"]."]");
			$tpl->parseCurrentBlock();
		}

	}

	/**
	* output tracking data
	*/
	function outputTrackingDataObject()
	{
		global $tpl,$lng,$ilias;

		// save selected values in session
		$_SESSION["il_track_yearf"] = $_POST["yearf"];
		$_SESSION["il_track_yeart"] = $_POST["yeart"];
		$_SESSION["il_track_monthf"] = $_POST["monthf"];
		$_SESSION["il_track_montht"] = $_POST["montht"];
		$_SESSION["il_track_dayf"] = $_POST["dayf"];
		$_SESSION["il_track_dayt"] = $_POST["dayt"];
		$_SESSION["il_track_stat"] = $_POST["stat"];
		$_SESSION["il_track_language"] = $_POST["language"];
		$_SESSION["il_track_author"] = $_POST["author"];
		$_SESSION["il_track_author1"] = $_POST["author1"];
		$_SESSION["il_track_lm"] = $_POST["lm"];
		$_SESSION["il_track_tst"] = $_POST["tst"];
		$_SESSION["il_object_type"] = $_POST["object_type"];

		$yearf = $_POST["yearf"];
		$monthf = $_POST["monthf"];
		$dayf = $_POST["dayf"];
		$yeart = $_POST["yeart"];
		$montht= $_POST["montht"];
		$dayt = $_POST["dayt"];
		$from = $yearf."-".$monthf."-".$dayf;
		$to = $yeart."-".$montht."-".$dayt;

		if(($yearf > $yeart)or($yearf==$yeart and $monthf>$montht)or($yearf==$yeart and $monthf==$montht and $dayf>$dayt))
		{
			$this->ilias->raiseError($lng->txt("msg_err_search_time"),
				$this->ilias->error_obj->MESSAGE);
		}

		/*
		if($_POST["stat"]!='h' and $_POST["stat"]!='d')
		{
			$this->ilias->raiseError($lng->txt("msg_no_search_time"),
				$this->ilias->error_obj->MESSAGE);
		}*/

		$usertracking = new ilUserTracking();
		//$result_id = $usertracking->getSubId($_SESSION["AccountId"]);
		$condition = $this->getCondition()." and acc_time >='".$from."' and acc_time< '".$to."'";

		if(count($usertracking->countResults($condition))== 0)
		{
			$this->ilias->raiseError($lng->txt("msg_no_search_result"),
				$this->ilias->error_obj->MESSAGE);
		}

		include_once "./classes/class.ilTableGUI.php";
		$tbl = new ilTableGUI();
		$tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.tracking_result.html");
		$tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		$tpl->addBlockfile("TRACK_TABLE", "track_table", "tpl.table.html");
		$tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.obj_tbl_rows.html");

		// user access statistic
		if($_POST["stat"] == "u")	// user access
		{
			
			if($_POST["mode"] == "user")
			{
				$tpl->setCurrentBlock("user_mode");
				$tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$_GET["ref_id"].
				"&cmd=gateway");
				if($_POST["object_type"]=="lm")
				{
					$tpl->setVariable("AUTHOR", "author");
					$tpl->setVariable("AUTHORS", $_POST["author"]);
					$tpl->setVariable("OBJECT", "lm");
					$tpl->setVariable("OBJECTS", $_POST["lm"]);
				}
				else
				{
					$tpl->setVariable("AUTHOR", "author1");
					$tpl->setVariable("AUTHORS", $_POST["author1"]);
					$tpl->setVariable("OBJECT", "tst");
					$tpl->setVariable("OBJECTS", $_POST["tst"]);
				}
				$tpl->setVariable("YEARF",$_POST["yearf"]);
				$tpl->setVariable("MONTHF",$_POST["monthf"]);
				$tpl->setVariable("DAYF",$_POST["dayf"]);
				$tpl->setVariable("YEART",$_POST["yeart"]);
				$tpl->setVariable("MONTHT",$_POST["montht"]);
				$tpl->setVariable("DAYT",$_POST["dayt"]);
				$tpl->setVariable("LAN", $_POST["language"]);
				$tpl->setVariable("TYPE", $_POST["object_type"]);
				$tpl->setVariable("FROM", $from);
				$tpl->setVariable("TO", $to);
				$tpl->setVariable("TXT_SHOW_USER_DATA", $lng->txt("user_statistics"));
				$tpl->parseCurrentBlock();
				$title_new = array("user","client_ip","language","object","time");
				$condition = $this->getConditions()." and acc_time>='".$from."' and acc_time<'".$to."'";
				$user_acc = $this->object->getAccessPerUserDetail($condition);
				$this->maxcount = count($user_acc);
				if (count($user_acc) < 1)
				{
					$this->ilias->raiseError($lng->txt("msg_no_search_result"),
						$this->ilias->error_obj->MESSAGE);
				}

				$tbl->setTitle($lng->txt("search_result"),0,0);
				foreach ($title_new as $val)
				{
					$header_names[] = $lng->txt($val);
				}
				$tbl->setHeaderNames($header_names);
				//$tbl->setColumnWidth(array("15","75%","25%"));
				$tbl->setMaxCount($this->maxcount);
				$tbl->setStyle("table", "std");
				$tbl->render();
				$max = 0;

				foreach ($user_acc as $user)
				{
					$data[0] = $user["name"];
					$data[1] = $user["client_ip"];
					$data[2] = $user["language"];
					$data[3] = $user["acc_obj_id"];
					$data[4] = $user["acc_time"];
					$css_row = $i%2==0?"tblrow1":"tblrow2";
					foreach ($data as $key => $val)
					{
						if($val=="")
						{
							$val=0;
						}
						$tpl->setCurrentBlock("text");
						$tpl->setVariable("TEXT_CONTENT", $val);
						$tpl->parseCurrentBlock();
						$tpl->setCurrentBlock("table_cell");
						$tpl->parseCurrentBlock();
					} //foreach
					$tpl->setCurrentBlock("tbl_content");
					$tpl->setVariable("CSS_ROW", $css_row);
					$tpl->parseCurrentBlock();
				} //for
			}
			else
			{
				$tpl->setCurrentBlock("user_mode");
				$tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$_GET["ref_id"].
				"&cmd=gateway");
				if($_POST["object_type"]=="lm")
				{
					$tpl->setVariable("AUTHOR", "author");
					$tpl->setVariable("AUTHORS", $_POST["author"]);
					$tpl->setVariable("OBJECT", "lm");
					$tpl->setVariable("OBJECTS", $_POST["lm"]);
				}
				else
				{
					$tpl->setVariable("AUTHOR", "author1");
					$tpl->setVariable("AUTHORS", $_POST["author1"]);
					$tpl->setVariable("OBJECT", "tst");
					$tpl->setVariable("OBJECTS", $_POST["tst"]);
				}
				$tpl->setVariable("YEARF",$_POST["yearf"]);
				$tpl->setVariable("MONTHF",$_POST["monthf"]);
				$tpl->setVariable("DAYF",$_POST["dayf"]);
				$tpl->setVariable("YEART",$_POST["yeart"]);
				$tpl->setVariable("MONTHT",$_POST["montht"]);
				$tpl->setVariable("DAYT",$_POST["dayt"]);
				$tpl->setVariable("USER", "user");
				$tpl->setVariable("LAN", $_POST["language"]);
				$tpl->setVariable("TYPE", $_POST["object_type"]);
				$tpl->setVariable("FROM", $from);
				$tpl->setVariable("TO", $to);
				$tpl->setVariable("TXT_SHOW_USER_DATA", $lng->txt("user_detail"));
				$tpl->parseCurrentBlock();
				$title_new = array("user", "count", "");

				// condition
				$condition = $this->getCondition()." and acc_time>='".$from."' and acc_time<'".$to."'";

				$user_acc = $this->object->getAccessTotalPerUser($condition);

				$this->maxcount = count($user_acc);

				// check if result is given
				if (count($user_acc) < 1)
				{
					$this->ilias->raiseError($lng->txt("msg_no_search_result"),
						$this->ilias->error_obj->MESSAGE);
				}

				$tbl->setTitle($lng->txt("search_result"),0,0);
				foreach ($title_new as $val)
				{
					$header_names[] = $lng->txt($val);
				}
				$tbl->setHeaderNames($header_names);
				//$tbl->setColumnWidth(array("15","75%","25%"));
				$tbl->setMaxCount($this->maxcount);
				$tbl->setStyle("table", "std");
				$tbl->render();
				$max = 0;
				foreach ($user_acc as $user)
				{
					$max = ($max > $user["cnt"]) ? $max : $user["cnt"];
				}

				foreach ($user_acc as $user)
				{
					$data[0] = $user["name"];
					$data[1] = $user["cnt"];
					$width = ($max > 0)
						? round($data[1] / $max * 100)
						: 0;
					$data[2] = "<img src=\"".ilUtil::getImagePath("ray.gif")."\" border=\"0\" ".
						"width=\"".$width."\" height=\"10\"/>";

					$css_row = $i%2==0?"tblrow1":"tblrow2";
					foreach ($data as $key => $val)
					{
						if($val=="")
						{
							$val=0;
						}
						$tpl->setCurrentBlock("text");
						$tpl->setVariable("TEXT_CONTENT", $val);
						$tpl->parseCurrentBlock();
						$tpl->setCurrentBlock("table_cell");
						$tpl->parseCurrentBlock();
					} //foreach
					$tpl->setCurrentBlock("tbl_content");
					$tpl->setVariable("CSS_ROW", $css_row);
					$tpl->parseCurrentBlock();
				} //for
			}

		}
		else //user not selected
		{
			$title_new = array("time", "count", "");

			include_once "./classes/class.ilTableGUI.php";
			$tbl = new ilTableGUI();
			$tbl->setTitle($lng->txt("obj_trac"),0,0);
			foreach ($title_new as $val)
			{
				$header_names[] = $lng->txt($val);
			}
			$tbl->setHeaderNames($header_names);

			if($_POST["stat"]=='h')
			{
				$num = 24;
				$tbl->setMaxCount($num);
			}
			else
			{
				$num = $usertracking->numDay($from,$to);
				$from1 = $usertracking->addDay($from);
				$tbl->setMaxCount($num);
			}
			$tbl->setStyle("table", "std");
			$tbl->render();

			// contition
			$condition = $this->getCondition();

			if($_POST["stat"]=='h')		//hours of day
			{
				$time = $usertracking->selectTime($from,$to,$condition);
				$max = 0;
				for($i=0;$i<24;$i++)
				{
					$k = $i+1;

					// count number of accesses in hour $i
					$cou = 0;
					for($j=0;$j<count($time);$j++)
					{
						$time1 = strtotime($time[$j][0]);
						$day = date("d",$time1);
						$month = date("m",$time1);
						$year = date("Y",$time1);
						$hour = date("H",$time1);
						$min = date("i",$time1);
						$sec = date("s",$time1);
						$numb = date("H",mktime($hour,$min,$sec,$month,$day,$year));
						$numb = intval($numb);
						if($numb >=$i and $numb <$k)
						{
							$cou=$cou+1;
						}
					}
					$count[$i] = $cou;
					$max = ($cou > $max) ? $cou : $max;
				}

				for($i=0;$i<24;$i++)
				{
					$k = $i+1;
					$data[0] = $i.":00:00  ~  ".$k.":00:00";
					$data[1] = $count[$i];
					$width = ($max > 0)
						? round($count[$i] / $max * 100)
						: 0;
					$data[2] = "<img src=\"".ilUtil::getImagePath("ray.gif")."\" border=\"0\" ".
						"width=\"".$width."\" height=\"10\"/>";
					$css_row = $i%2==0?"tblrow1":"tblrow2";
					foreach ($data as $key => $val)
					{

						$tpl->setCurrentBlock("text");
						$tpl->setVariable("TEXT_CONTENT", $val);
						$tpl->parseCurrentBlock();
						$tpl->setCurrentBlock("table_cell");
						$tpl->parseCurrentBlock();
					}
					$tpl->setCurrentBlock("tbl_content");
					$tpl->setVariable("CSS_ROW", $css_row);
					$tpl->parseCurrentBlock();

				} //for
			}
			else //day selected
			{
				$max = 0;
				for($i=0;$i<$num;$i++)
				{
					$fro[$i] = $from;
					$cou[$i] = $usertracking->countNum($from,$from1,$condition);
					$from = $from1;
					$from1 = $usertracking->addDay($from);
					$max = ($max > $cou[$i]) ? $max : $cou[$i];
				}
				for($i=0;$i<$num;$i++)
				{
					$data[0] = $fro[$i];
					$data[1] = $cou[$i];
					$width = ($max > 0)
						? round($cou[$i] / $max * 100)
						: 0;
					$data[2] = "<img src=\"".ilUtil::getImagePath("ray.gif")."\" border=\"0\" ".
						"width=\"".$width."\" height=\"10\"/>";

					$css_row = $i%2==0?"tblrow1":"tblrow2";

					foreach ($data as $key => $val)
					{
						$tpl->setCurrentBlock("text");
						$tpl->setVariable("TEXT_CONTENT", $val);
						$tpl->parseCurrentBlock();
						$tpl->setCurrentBlock("table_cell");
						$tpl->parseCurrentBlock();
					}
					$tpl->setCurrentBlock("tbl_content");
					$tpl->setVariable("CSS_ROW", $css_row);
					$tpl->parseCurrentBlock();
				} //for
			}
		}//else
		$tpl->setCurrentBlock("adm_content");

		// output statistic settings
		$tpl->setVariable("TXT_TIME_PERIOD", $lng->txt("time_segment"));
		switch ($_POST["stat"])
		{
			case "h":
				$tpl->setVariable("TXT_STATISTIC", $lng->txt("hours_of_day"));
				break;

			case "u":
				$tpl->setVariable("TXT_STATISTIC", $lng->txt("user_access"));
				break;

			case "d":
				$tpl->setVariable("TXT_STATISTIC", $lng->txt("days_of_period"));
				break;
		}
		$tpl->setVariable("VAL_DATEF", date("Y-m-d", mktime(0,0,0,$monthf,$dayf,$yearf)));
		$tpl->setVariable("TXT_TO", $lng->txt("to"));
		$tpl->setVariable("VAL_DATET", date("Y-m-d", mktime(0,0,0,$montht,$dayt,$yeart)));
		$tpl->setVariable("TXT_USER_LANGUAGE", $lng->txt("user_language"));
		if ($_POST["language"] == "0")
		{
			$tpl->setVariable("VAL_LANGUAGE", $lng->txt("any_language"));
		}
		else
		{
			$tpl->setVariable("VAL_LANGUAGE", $lng->txt("lang_".$_POST["language"]));
		}
		$tpl->setVariable("TXT_TRACKED_OBJECTS", $lng->txt("tracked_objects"));
		if ($_POST[$_POST["object_type"]] != 0)
		{
			$tpl->setVariable("VAL_TRACKED_OBJECTS",
				ilObject::_lookupTitle($_POST[$_POST["object_type"]]));
		}
		else
		{
			$tpl->setVariable("VAL_TRACKED_OBJECTS",
				$lng->txt("all_".$_POST["object_type"]."s"));
		}
		$tpl->parseCurrentBlock();
	}

	/**
	* get complete condition string
	*/
	function getCondition()
	{
		$lang_cond = $this->getLanguageCondition();
		//echo ":$lang_cond:";
		if ($lang_cond == "")
		{
			$this->setConditions($this->getObjectCondition());
			return $this->getObjectCondition();
		}
		else
		{
			$this->setConditions($lang_cond." AND ".$this->getObjectCondition());
			return $lang_cond." AND ".$this->getObjectCondition();
		}
	}


	/**
	* get object condition string
	*/
	function getObjectCondition()
	{
		global $ilDB;

		$type = $_POST["object_type"];
		$condition = "";
		if($_POST["object_type"]=="lm")
		{
			if($_POST["author"]=="0")
			{
				return " acc_obj_type = 'lm'";
			}
			elseif($_POST["lm"]=="0" or $_POST["lm"]=="")
			{
				$authors = ilObjSysUserTracking::allAuthor("usr","lm");
				foreach ($authors as $author)
				{
					if($author["title"]==$_POST["author"])
					$lms = ilObjSysUserTracking::authorLms($author["obj_id"],"lm");
					foreach ($lms as $lm)
					{
						$condition = $condition." or acc_obj_id = ".$lm["obj_id"];
					}
				}
				return " ( 0 ".$condition." ) ";
			}
			else
			{
				$condition.= " acc_obj_id = ".ilObjSysUserTracking::getObjId($_POST["lm"],$type);
				return $condition;
			}

		}
		else
		{
			if($_POST["author1"]=="0")
			{
				return " acc_obj_type = 'tst'";
			}
			elseif($_POST["tst"]=="0" or $_POST["tst"]=="")
			{
				$authors = ilObjSysUserTracking::allAuthor("usr","tst");
				foreach ($authors as $author)
				{
					if($author["title"]==$_POST["author1"])
					$lms = ilObjSysUserTracking::authorLms($author["obj_id"],"tst");
					foreach ($lms as $lm)
					{
						$condition = $condition." or acc_obj_id = ".$lm["obj_id"];
					}
				}
				return " ( 0 ".$condition." ) ";
			}
			else
			{
				$condition.= " acc_obj_id = ".ilObjSysUserTracking::getObjId($_POST["tst"],$type);
				return $condition;
			}
		}
	}

	/**
	* get language condition string
	*/
	function getLanguageCondition()
	{
		global $ilDB;

		if ($_POST["language"] != "0")
		{
			return "ut_access.language =".$ilDB->quote($_POST["language"]);
		}

		return "";
	}	
	function setConditions($con)
	{
		$this->conditions = $con;
	}
	function getConditions()
	{
		return $this->conditions;
	}

} // END class.ilObj<module_name>
?>
