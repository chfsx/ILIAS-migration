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

require_once("./classes/class.ilObjAICCLearningModule.php");
require_once("content/classes/AICC/class.ilAICCObjectGUI.php");
require_once("content/classes/SCORM/class.ilSCORMPresentationGUI.php");


/**
* Class ilAICCPresentationGUI
*
* GUI class for aicc learning module presentation
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilAICCPresentationGUI extends ilSCORMPresentationGUI
{
	var $ilias;
	var $slm;
	var $tpl;
	var $lng;

	function ilAICCPresentationGUI()
	{
		global $ilias, $tpl, $lng;

		$this->ilias =& $ilias;
		$this->tpl =& $tpl;
		$this->lng =& $lng;

		$cmd = (!empty($_GET["cmd"])) ? $_GET["cmd"] : "frameset";

		// Todo: check lm id
		$this->slm =& new ilObjAICCLearningModule($_GET["ref_id"], true);

		$this->$cmd();
	}
	
	function view()
	{
		$sc_gui_object =& ilAICCObjectGUI::getInstance($_GET["obj_id"]);

		if(is_object($sc_gui_object))
		{
			$sc_gui_object->view();
		}

		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		$this->tpl->show();
	}
	
	/**
	* output table of content
	*/
	function explorer($a_target = "sahs_content")
	{
		$this->tpl = new ilTemplate("tpl.sahs_exp_main.html", true, true, true);
		//$this->tpl->setVariable("LOCATION_JAVASCRIPT", "./scorm_functions.js");
		
		require_once("./content/classes/AICC/class.ilAICCExplorer.php");
		$exp = new ilAICCExplorer("sahs_presentation.php?cmd=view&ref_id=".$this->slm->getRefId(), $this->slm);
		$exp->setTargetGet("obj_id");
		$exp->setFrameTarget($a_target);
		//$exp->setFiltered(true);

		if ($_GET["scexpand"] == "")
		{
			$mtree = new ilSCORMTree($this->slm->getId());
			$expanded = $mtree->readRootId();
		}
		else
		{
			$expanded = $_GET["scexpand"];
		}
		$exp->setExpand($expanded);

		// build html-output
		//666$exp->setOutput(0);
		$exp->setOutput(0);

		$output = $exp->getOutput();

		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.explorer.html");
		$this->tpl->setVariable("TXT_EXPLORER_HEADER", $this->lng->txt("cont_content"));
		$this->tpl->setVariable("EXPLORER",$output);
		$this->tpl->setVariable("ACTION", "sahs_presentation.php?cmd=".$_GET["cmd"]."&frame=".$_GET["frame"].
			"&ref_id=".$this->slm->getRefId()."&scexpand=".$_GET["scexpand"]);
		$this->tpl->parseCurrentBlock();
		$this->tpl->show();
	}

	function launchSahs()
	{
		global $ilUser, $ilDB;

		$sahs_id = ($_GET["sahs_id"] == "")
			? $_POST["sahs_id"]
			: $_GET["sahs_id"];
		$ref_id = ($_GET["ref_id"] == "")
			? $_POST["ref_id"]
			: $_GET["ref_id"];

		$this->slm =& new ilObjAICCLearningModule($ref_id, true);

		include_once("content/classes/AICC/class.ilAICCUnit.php");
		$unit =& new ilAICCUnit($sahs_id);
		
		//guess the url
		$url=$unit->getCommand_line();
		if (strlen($url)==0)
			$url=$unit->getFilename();
		if (strcasecmp(substr($unit->getFilename(),0,4),"http")!=0)
			$url=$this->slm->getDataDirectory("output")."/".$url;
		if (strlen($unit->getWebLaunch())>0)
			$url.="?".$unit->getWebLaunch();
/*		
		if (strcasecmp(substr($unit->getFilename(),0,4),"http")==0)
			$href=$unit->getFilename();
		else
			$href=$this->slm->getDataDirectory("output")."/".$unit->getFilename();
*/		
		$this->tpl = new ilTemplate("tpl.sahs_launch_cbt.html", true, true, true);
		$this->tpl->setVariable("HREF", $url);
//		$this->tpl->setVariable("LAUNCH_DATA", $unit->getDataFromLms());
		$this->tpl->setVariable("MAST_SCORE", $unit->getMasteryScore());
		$this->tpl->setVariable("MAX_TIME", $unit->getMaxTimeAllowed());
		$this->tpl->setVariable("LIMIT_ACT", $unit->getTimeLimitAction());
		if($ilUser->getFirstName() == "Joe")	// for test purpose
		{
			$this->tpl->setCurrentBlock("credit");
			$this->tpl->setVariable("CREDIT_MODE", "normal");
			$this->tpl->parseCurrentBlock();
		}
		$query = "SELECT * FROM scorm_tracking WHERE".
			" user_id = ".$ilDB->quote($ilUser->getId()).
			" AND sco_id = ".$ilDB->quote($sahs_id);


		$val_set = $ilDB->query($query);
		$re_value = array();
		while($val_rec = $val_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$val_rec["rvalue"] = str_replace("\r\n", "\n", $val_rec["rvalue"]);
			$val_rec["rvalue"] = str_replace("\r", "\n", $val_rec["rvalue"]);
			$val_rec["rvalue"] = str_replace("\n", "%n%", $val_rec["rvalue"]);
			$re_value[$val_rec["lvalue"]] = $val_rec["rvalue"];
		}

		foreach($re_value as $var => $value)
		{
			switch ($var)
			{
				case "cmi.core.lesson_location":
				case "cmi.core.lesson_status":
				case "cmi.core.entry":
				case "cmi.core.score.raw":
				case "cmi.core.score.max":
				case "cmi.core.score.min":
				case "cmi.core.total_time":
				case "cmi.core.exit":
				case "cmi.suspend_data":
				case "cmi.comments":
				case "cmi.student_preference.audio":
				case "cmi.student_preference.language":
				case "cmi.student_preference.speed":
				case "cmi.student_preference.text":
					$this->setSingleVariable($var, $value);
					break;

				case "cmi.objectives._count":
					$this->setSingleVariable($var, $value);
					$this->setArray("cmi.objectives", $value, "id", $re_value);
					$this->setArray("cmi.objectives", $value, "score.raw", $re_value);
					$this->setArray("cmi.objectives", $value, "score.max", $re_value);
					$this->setArray("cmi.objectives", $value, "score.min", $re_value);
					$this->setArray("cmi.objectives", $value, "status", $re_value);
					break;

				case "cmi.interactions._count":
					$this->setSingleVariable($var, $value);
					$this->setArray("cmi.interactions", $value, "id", $re_value);
					for($i=0; $i<$value; $i++)
					{
						$var2 = "cmi.interactions.".$i.".objectives._count";
						if (isset($v_array[$var2]))
						{
							$cnt = $v_array[$var2];
							$this->setArray("cmi.interactions.".$i.".objectives",
								$cnt, "id", $re_value);
							/*
							$this->setArray("cmi.interactions.".$i.".objectives",
								$cnt, "score.raw", $re_value);
							$this->setArray("cmi.interactions.".$i.".objectives",
								$cnt, "score.max", $re_value);
							$this->setArray("cmi.interactions.".$i.".objectives",
								$cnt, "score.min", $re_value);
							$this->setArray("cmi.interactions.".$i.".objectives",
								$cnt, "status", $re_value);*/
						}
					}
					$this->setArray("cmi.interactions", $value, "time", $re_value);
					$this->setArray("cmi.interactions", $value, "type", $re_value);
					for($i=0; $i<$value; $i++)
					{
						$var2 = "cmi.interactions.".$i.".correct_responses._count";
						if (isset($v_array[$var2]))
						{
							$cnt = $v_array[$var2];
							$this->setArray("cmi.interactions.".$i.".correct_responses",
								$cnt, "pattern", $re_value);
							$this->setArray("cmi.interactions.".$i.".correct_responses",
								$cnt, "weighting", $re_value);
						}
					}
					$this->setArray("cmi.interactions", $value, "student_response", $re_value);
					$this->setArray("cmi.interactions", $value, "result", $re_value);
					$this->setArray("cmi.interactions", $value, "latency", $re_value);
					break;
			}
		}

		global $lng;
		$this->tpl->setCurrentBlock("switch_icon");
		$this->tpl->setVariable("SCO_ID", $_GET["sahs_id"]);
		$this->tpl->setVariable("SCO_ICO", ilUtil::getImagePath("scorm/running.gif"));
		$this->tpl->setVariable("SCO_ALT",
			 $lng->txt("cont_status").": "
			.$lng->txt("cont_sc_stat_running")
		);
		$this->tpl->parseCurrentBlock();

		// lesson mode
		$lesson_mode = $this->slm->getDefaultLessonMode();
		if ($this->slm->getAutoReview())
		{
			if ($re_value["cmi.core.lesson_status"] == "completed" ||
				$re_value["cmi.core.lesson_status"] == "passed" ||
				$re_value["cmi.core.lesson_status"] == "failed")
			{
				$lesson_mode = "review";
			}
		}
		$this->tpl->setVariable("LESSON_MODE", $lesson_mode);

		// credit mode
		if ($lesson_mode == "normal")
		{
			$this->tpl->setVariable("CREDIT_MODE",
				str_replace("_", " ", $this->slm->getCreditMode()));
		}
		else
		{
			$this->tpl->setVariable("CREDIT_MODE", "no-credit");
		}

		// init cmi.core.total_time, cmi.core.lesson_status and cmi.core.entry
		if (!isset($re_value["cmi.core.total_time"]))
		{
			$unit->insertTrackData("cmi.core.total_time", "0000:00:00", $_GET["ref_id"]);
		}
		if (!isset($re_value["cmi.core.lesson_status"]))
		{
			$unit->insertTrackData("cmi.core.lesson_status", "not attempted", $_GET["ref_id"]);
		}
		if (!isset($re_value["cmi.core.entry"]))
		{
			$unit->insertTrackData("cmi.core.entry", "", $_GET["ref_id"]);
		}

		$this->tpl->show();
	}

}
?>
