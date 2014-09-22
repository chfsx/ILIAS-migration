<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Table/classes/class.ilTable2GUI.php';
include_once './Modules/Scorm2004/classes/class.ilSCORM2004TrackingItems.php';

/**
 * Class ilSCORM2004TrackingItemsScoTableGUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @ingroup ModulesScorm2004
 */
class ilSCORM2004TrackingItemsScoTableGUI extends ilTable2GUI
{
    private $obj_id = 0;
	private $user_id = 0;
	private $bySCO = false;
	private $scosSelected = array();
	private $userSelected = array();
	private $allowExportPrivacy = false;
	private $scoTite = "";

	/**
	 * Constructor
	 */
	public function __construct($a_obj_id, $a_parent_obj, $a_parent_cmd, $a_userSelected, $a_scosSelected, $a_report)
	{
	
		global $ilCtrl, $lng, $ilAccess, $lng, $rbacsystem;
		$lng->loadLanguageModule("scormtrac");
	
		$this->obj_id = $a_obj_id;
		$this->report = $a_report;
		$this->scosSelected=$a_scosSelected;
		$this->userSelected=$a_userSelected;
		if ($a_parent_cmd == "showTrackingItemsBySco") $this->bySCO = true;
		$this->lmTitle = $a_parent_obj->object->getTitle();

		$this->setId('2004'.$this->report);
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setLimit(9999);

		include_once('./Services/PrivacySecurity/classes/class.ilPrivacySettings.php');
		$privacy = ilPrivacySettings::_getInstance();
		$this->allowExportPrivacy = $privacy->enabledExportSCORM();


		// if($a_print_view)
		// {
			// $this->setPrintMode(true);
		// }


		foreach ($this->getSelectedColumns() as $c)
		{
			$l = $c;
			if (in_array($l, array("status", "time", "score"))) {
				$l = "cont_".$l;
			// } else {
				// $l =
			}
			$s = $this->lng->txt($l);
			if (substr($l,0,14) == "interaction_id") $s = $this->lng->txt(substr($l,0,14)).' '.substr($l,14);
			if (substr($l,0,17) == "interaction_value") $s = sprintf($this->lng->txt(substr($l,0,17)),substr($l,17,(strpos($l,' ')-17))).substr($l,strpos($l,' '));
			if (substr($l,0,23) == "interaction_description") $s = $this->lng->txt(substr($l,0,23)).' '.substr($l,23);
			$this->addColumn($s, $c);
		}

		$this->setRowTemplate('tpl.scorm2004_tracking_items.html', 'Modules/Scorm2004');
		$this->setFormAction($ilCtrl->getFormAction($this->getParentObject()));

		$this->setExternalSorting(true);
//		$this->setExternalSegmentation(true);
		$this->setEnableHeader(true);
		$this->setEnableTitle(true);
//		$this->setDefaultOrderField("cp_node_id, user_id");
		$this->setDefaultOrderField("");
		$this->setDefaultOrderDirection("asc");
		$this->setShowTemplates(true);

		$this->setExportFormats(array(self::EXPORT_CSV, self::EXPORT_EXCEL));
//		$this->initFilter();
		$this->getItems();
	}
	/**
	 * Get selectable columns
	 *
	 * @param
	 * @return
	 */
	function getSelectableColumns()
	{
		// default fields
		$cols = array();
		
/*		include_once 'Services/Tracking/classes/class.ilObjUserTracking.php';
		$tracking = new ilObjUserTracking();
		if($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_LAST_ACCESS))
		{
			$cols["first_access"] = array(
				"txt" => $lng->txt("trac_first_access"),
				"default" => true);
			$cols["last_access"] = array(
				"txt" => $lng->txt("trac_last_access"),
				"default" => true);
		}
		if($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_READ_COUNT))
		{
			$cols["read_count"] = array(
				"txt" => $lng->txt("trac_read_count"),
				"default" => true);
		}
		if($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_SPENT_SECONDS))
		{
			$cols["spent_seconds"] = array(
				"txt" => $lng->txt("trac_spent_seconds"),
				"default" => true);
		}
	*/
		switch($this->report) {
			case "exportSelectedCore":
				$cols=ilSCORM2004TrackingItems::exportSelectedCoreColumns($this->bySCO, $this->allowExportPrivacy);
			break;
			case "exportSelectedInteractions":
				$cols=ilSCORM2004TrackingItems::exportSelectedInteractionsColumns($this->bySCO, $this->allowExportPrivacy);
			break;
			case "tracInteractionItem":
				$cols=ilSCORM2004TrackingItems::tracInteractionItemColumns($this->bySCO, $this->allowExportPrivacy);
			break;
			case "tracInteractionUser":
				$cols=ilSCORM2004TrackingItems::tracInteractionUserColumns($this->bySCO, $this->allowExportPrivacy);
			break;
			case "tracInteractionUserAnswers":
				$cols=ilSCORM2004TrackingItems::tracInteractionUserAnswersColumns($this->userSelected, $this->scosSelected, $this->bySCO, $this->allowExportPrivacy);
			break;
		}
		
		return $cols;
	}

	/**
	 * Get Obj id
	 * @return int
	 */
	public function getObjId()
	{
		return $this->obj_id;
	}


	function getItems() {
		global $lng;

		$this->determineOffsetAndOrder();
		switch($this->report) {
			case "exportSelectedCore":
				$tr_data = ilSCORM2004TrackingItems::exportSelectedCore($this->userSelected, $this->scosSelected, $this->bySCO, $this->allowExportPrivacy);
			break;
			case "exportSelectedInteractions":
				$tr_data = ilSCORM2004TrackingItems::exportSelectedInteractions($this->userSelected, $this->scosSelected, $this->bySCO, $this->allowExportPrivacy);
			break;
			case "tracInteractionItem":
				$tr_data = ilSCORM2004TrackingItems::tracInteractionItem($this->userSelected, $this->scosSelected, $this->bySCO, $this->allowExportPrivacy);
			break;
			case "tracInteractionUser":
				$tr_data = ilSCORM2004TrackingItems::tracInteractionUser($this->userSelected, $this->scosSelected, $this->bySCO, $this->allowExportPrivacy);
			break;
			case "tracInteractionUserAnswers":
				$tr_data = ilSCORM2004TrackingItems::tracInteractionUserAnswers($this->userSelected, $this->scosSelected, $this->bySCO, $this->allowExportPrivacy);
			break;
		}
		$this->setMaxCount($tr_data["cnt"]);
		if (ilUtil::stripSlashes($this->getOrderField()) !="") {
			include_once "Services/Utilities/classes/class.ilStr.php";
			$tr_data = ilUtil::stableSortArray($tr_data, ilUtil::stripSlashes($this->getOrderField()), ilUtil::stripSlashes($this->getOrderDirection()) );
		}

		$this->setData($tr_data);
	}
	protected function parseValue($id, $value, $type)
	{
		global $lng;
		$lng->loadLanguageModule("scormtrac");
		switch($id)
		{
			case "status":
				include_once("./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php");
				$path = ilLearningProgressBaseGUI::_getImagePathForStatus($value);
				$text = ilLearningProgressBaseGUI::_getStatusText($value);
				$value = ilUtil::img($path, $text);
				break;
		}
		//BLUM round
		if ($id=="launch_data" || $id=="suspend_data") return $value;
		if (is_numeric($value)) return round($value,2);
		return $value;
	}
	/**
	* Fill table row
	*/
	protected function fillRow($data)
	{
		global $ilCtrl, $lng;
		foreach ($this->getSelectedColumns() as $c)
		{
			$this->tpl->setCurrentBlock("user_field");
			$val = $this->parseValue($c, $data[$c], "scormtrac");
			$this->tpl->setVariable("VAL_UF", $val);
			$this->tpl->parseCurrentBlock();
		}
		
	}

	protected function fillHeaderExcel($worksheet, &$a_row)
	{
		$labels = $this->getSelectableColumns();
		$cnt = 0;
		foreach ($this->getSelectedColumns() as $c)
		{
			$worksheet->write($a_row, $cnt, $labels[$c]["txt"]);
			$cnt++;
		}
	}

	protected function fillRowExcel($worksheet, &$a_row, $a_set)
	{
		$cnt = 0;
		foreach ($this->getSelectedColumns() as $c)
		{
			if($c != 'status')
			{
				$val = $this->parseValue($c, $a_set[$c], "user");
			}
			else
			{
				$val = ilLearningProgressBaseGUI::_getStatusText((int)$a_set[$c]);
			}
			$worksheet->write($a_row, $cnt, $val);
			$cnt++;
		}
	}

	protected function fillHeaderCSV($a_csv)
	{
		$labels = $this->getSelectableColumns();
		foreach ($this->getSelectedColumns() as $c)
		{
			$a_csv->addColumn($labels[$c]["txt"]);
		}

		$a_csv->addRow();
	}

	protected function fillRowCSV($a_csv, $a_set)
	{
		foreach ($this->getSelectedColumns() as $c)
		{
			if($c != 'status')
			{
				$val = $this->parseValue($c, $a_set[$c], "user");
			}
			else
			{
				$val = ilLearningProgressBaseGUI::_getStatusText((int)$a_set[$c]);
			}
			$a_csv->addColumn($val);
		}
		
		$a_csv->addRow();
	}

}
?>
