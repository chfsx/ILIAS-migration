<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Tracking/classes/class.ilLPTableBaseGUI.php");

/**
* TableGUI class for learning progress
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilLPProgressTableGUI: ilFormPropertyDispatchGUI
* @ingroup ServicesTracking
*/
class ilLPProgressTableGUI extends ilLPTableBaseGUI
{
	/**
	* Constructor
	*/
	function __construct($a_parent_obj, $a_parent_cmd, $a_user = "", $obj_ids = NULL, $details = false, $objectives_mode = false)
	{
		global $ilCtrl, $lng, $ilAccess, $lng, $ilObjDataCache;

		$this->tracked_user = $a_user;
		$this->obj_ids = $obj_ids;
		$this->objectives_mode = $objectives_mode;
		$this->details = $details;

		$this->setId("lpprgtbl");
		
		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setLimit(ilSearchSettings::getInstance()->getMaxHits());

		if(!$this->details)
		{
			$this->addColumn("", "", "1", true);
			$this->addColumn($this->lng->txt("trac_title"), "title", "26%");
			$this->addColumn($this->lng->txt("status"), "status", "7%");
			$this->addColumn($this->lng->txt("trac_percentage"), "percentage", "7%");
			$this->addColumn($this->lng->txt("trac_mark"), "", "5%");
			$this->addColumn($this->lng->txt("comment"), "", "10%");
			$this->addColumn($this->lng->txt("trac_mode"), "", "20%");
			$this->addColumn($this->lng->txt("path"), "", "20%");
			$this->addColumn($this->lng->txt("actions"), "", "5%");

			$this->setTitle($this->lng->txt("learning_progress"));
			$this->initFilter();

			$this->setSelectAllCheckbox("item_id");
			$this->addMultiCommand("hideSelected", $lng->txt("trac_hide_selected"));
		}
		else
		{
			$this->parseTitle($a_parent_obj->details_obj_id, "trac_subitems");

			$this->addColumn($this->lng->txt("trac_title"), "title", "31%");
			$this->addColumn($this->lng->txt("status"), "status", "7%");
			$this->addColumn($this->lng->txt("trac_percentage"), "percentage", "7%");
			$this->addColumn($this->lng->txt("trac_mark"), "", "5%");
			$this->addColumn($this->lng->txt("comment"), "", "10%");
			$this->addColumn($this->lng->txt("trac_mode"), "", "20%");
			$this->addColumn($this->lng->txt("path"), "", "20%");
		}
		
		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormActionByClass(get_class($this)));
		$this->setRowTemplate("tpl.lp_progress_list_row.html", "Services/Tracking");
		$this->setEnableHeader(true);
		$this->setEnableNumInfo(true);
		$this->setEnableTitle(true);
		$this->setDefaultOrderField("title");
		$this->setDefaultOrderDirection("asc");
		$this->setShowTemplates(true);

		$this->setExportFormats(array(self::EXPORT_CSV, self::EXPORT_EXCEL));

		// area selector gets in the way
		if($this->tracked_user)
		{
			$this->getItems();
		}
	}

	function getItems()
	{
		$obj_ids = $this->obj_ids;
		if(!$obj_ids && !$this->details)
	    {
			$obj_ids = $this->searchObjects($this->getCurrentFilter(true));
		}
		if($obj_ids)
		{
			include_once("./Services/Tracking/classes/class.ilTrQuery.php");
			if(!$this->objectives_mode)
			{
				$data = ilTrQuery::getObjectsStatusForUser($this->tracked_user->getId(), $obj_ids);
			}
			else
			{
				$data = ilTrQuery::getObjectivesStatusForUser($this->tracked_user->getId(), $obj_ids);
			}
			$this->setData($data);
		}
	}
	
	/**
	* Fill table row
	*/
	protected function fillRow($a_set)
	{
		global $ilObjDataCache, $ilCtrl;

		if(!$this->details)
		{
			$this->tpl->setCurrentBlock("column_checkbox");
			$this->tpl->setVariable("OBJ_ID", $a_set["obj_id"]);
			$this->tpl->parseCurrentBlock();
		}

		if(!$this->isPercentageAvailable($a_set["obj_id"]) || (int)$a_set["percentage"] === 0)
		{
			$this->tpl->setVariable("PERCENTAGE_VALUE", "");
		}
		else
		{
			$this->tpl->setVariable("PERCENTAGE_VALUE", sprintf("%d%%", $a_set["percentage"]));
		}

		$this->tpl->setVariable("ICON_SRC", ilUtil::getTypeIconPath($a_set["type"], $a_set["obj_id"], "tiny"));
		$this->tpl->setVariable("ICON_ALT", $this->lng->txt($a_set["type"]));
		$this->tpl->setVariable("TITLE_TEXT", $a_set["title"]);
		
		$this->tpl->setVariable("STATUS_ALT", ilLearningProgressBaseGUI::_getStatusText($a_set["status"]));
		$this->tpl->setVariable("STATUS_IMG", ilLearningProgressBaseGUI::_getImagePathForStatus($a_set["status"]));

		$this->tpl->setVariable("MODE_TEXT", ilLPObjSettings::_mode2Text($a_set["u_mode"]));
		$this->tpl->setVariable("MARK_VALUE", $a_set["mark"]);
		$this->tpl->setVariable("COMMENT_TEXT", $a_set["comment"]);

		// path
		$path = $this->buildPath($a_set["ref_ids"]);
		if($path)
		{
			$this->tpl->setCurrentBlock("item_path");
			foreach($path as $path_item)
			{
				$this->tpl->setVariable("PATH_ITEM", $path_item);
				$this->tpl->parseCurrentBlock();
			}
		}

		// tlt warning
		if($a_set["status"] != LP_STATUS_COMPLETED_NUM)
		{
			$ref_id = $a_set["ref_ids"];
			$ref_id = array_shift($ref_id);
			include_once 'Modules/Course/classes/Timings/class.ilTimingCache.php';
			if(ilCourseItems::_hasCollectionTimings($ref_id) && ilTimingCache::_showWarning($ref_id, $this->tracked_user->getId()))
			{
				$this->tpl->setCurrentBlock('warning_img');
				$this->tpl->setVariable('WARNING_IMG', ilUtil::getImagePath('warning.gif'));
				$this->tpl->setVariable('WARNING_ALT', $this->lng->txt('trac_time_passed'));
				$this->tpl->parseCurrentBlock();
			}
		}

		// hide / unhide?!
		if(!$this->details)
		{
			$this->tpl->setCurrentBlock("item_command");
			$ilCtrl->setParameterByClass(get_class($this),'hide', $a_set["obj_id"]);
			$this->tpl->setVariable("HREF_COMMAND", $ilCtrl->getLinkTargetByClass(get_class($this),'hide'));
			$this->tpl->setVariable("TXT_COMMAND", $this->lng->txt('trac_hide'));
			$this->tpl->parseCurrentBlock();

			if(ilLPObjSettings::_isContainer($a_set["u_mode"]))
			{
				$ref_id = $a_set["ref_ids"];
				$ref_id = array_shift($ref_id);
				$ilCtrl->setParameterByClass($ilCtrl->getCmdClass(), 'details_id', $ref_id);
				$this->tpl->setVariable("HREF_COMMAND", $ilCtrl->getLinkTargetByClass($ilCtrl->getCmdClass(), 'details'));
				$ilCtrl->setParameterByClass($ilCtrl->getCmdClass(), 'details_id', '');
				$this->tpl->setVariable("TXT_COMMAND", $this->lng->txt('trac_subitems'));
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("column_action");
			$this->tpl->parseCurrentBlock();
		}
	}

	protected function fillHeaderExcel($worksheet, &$a_row)
	{
		$worksheet->write($a_row, 0, $this->lng->txt("type"));
		$worksheet->write($a_row, 1, $this->lng->txt("trac_title"));
		$worksheet->write($a_row, 2, $this->lng->txt("status"));
		$worksheet->write($a_row, 3, $this->lng->txt("trac_percentage"));
		$worksheet->write($a_row, 4, $this->lng->txt("trac_mark"));
		$worksheet->write($a_row, 5, $this->lng->txt("comment"));
		$worksheet->write($a_row, 6, $this->lng->txt("trac_mode"));
		// $worksheet->write($a_row, 7, $this->lng->txt("path"));
	}
	
	protected function fillRowExcel($worksheet, &$a_row, $a_set)
	{
		$worksheet->write($a_row, 0, $this->lng->txt($a_set["type"]));
		$worksheet->write($a_row, 1, $a_set["title"]);
		$worksheet->write($a_row, 2, ilLearningProgressBaseGUI::_getStatusText($a_set["status"]));
		$worksheet->write($a_row, 3, sprintf("%d%%", $a_set["percentage"]));
		$worksheet->write($a_row, 4, $a_set["mark"]);
		$worksheet->write($a_row, 5, $a_set["comment"]);
		$worksheet->write($a_row, 6, ilLPObjSettings::_mode2Text($a_set["u_mode"]));

		/*
		// path
		$path = $this->buildPath($a_set["ref_ids"]);
		if($path)
		{
			$col = 7;
			foreach($path as $path_item)
			{
				$worksheet->write($a_row, $col, strip_tags($path_item));
				$col++;
			}
		}
		*/

	}

	protected function fillHeaderCSV($a_csv)
	{
		$a_csv->addColumn($this->lng->txt("type"));
		$a_csv->addColumn($this->lng->txt("trac_title"));
		$a_csv->addColumn($this->lng->txt("status"));
		$a_csv->addColumn($this->lng->txt("trac_percentage"));
		$a_csv->addColumn($this->lng->txt("trac_mark"));
		$a_csv->addColumn($this->lng->txt("comment"));
		$a_csv->addColumn($this->lng->txt("trac_mode"));
		// $a_csv->addColumn($this->lng->txt("path"));
		$a_csv->addRow();
	}

	protected function fillRowCSV($a_csv, $a_set)
	{
		$a_csv->addColumn($this->lng->txt($a_set["type"]));
		$a_csv->addColumn($a_set["title"]);
		$a_csv->addColumn(ilLearningProgressBaseGUI::_getStatusText($a_set["status"]));
		$a_csv->addColumn(sprintf("%d%%", $a_set["percentage"]));
		$a_csv->addColumn($a_set["mark"]);
		$a_csv->addColumn($a_set["comment"]);
		$a_csv->addColumn(ilLPObjSettings::_mode2Text($a_set["u_mode"]));

		/*
		// path
		$path = $this->buildPath($a_set["ref_ids"]);
		if($path)
		{
			$col = 7;
			foreach($path as $path_item)
			{
				$a_csv->addColumn(strip_tags($path_item));
				$col++;
			}
		}
		*/

		$a_csv->addRow();
	}
}

?>