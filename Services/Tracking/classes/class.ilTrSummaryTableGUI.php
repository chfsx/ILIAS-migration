<?php

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
 * name table
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 * @ingroup Services
 */
class ilTrSummaryTableGUI extends ilTable2GUI
{
	/**
	 * Constructor
	 */
	function __construct($a_parent_obj, $a_parent_cmd)
	{
		global $ilCtrl, $lng, $ilAccess, $lng;

		$this->setId("tr_summary");

		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setTitle($lng->txt("tr_summary"));
		$this->setLimit(9999);

		$this->addColumn($this->lng->txt("title"));

		// re-use caption from learners list
		$this->lng_map = array("activity_earliest" => "trac_first_access", "activity_latest" => "trac_last_access",
			"mark" => "trac_mark", "status" => "trac_status", "time_average" => "trac_spent_seconds",
			"access_total" => "trac_read_count", "completion_average" => "trac_percentage"
			);

		foreach ($this->getSelectedColumns() as $c)
		{
			$l = $c;
			if(isset($this->lng_map[$l]))
			{
				$l = $this->lng_map[$l];
			}
			$this->addColumn($this->lng->txt($l), $c);
		}

		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj, "applyFilter"));
		$this->setRowTemplate("tpl.trac_summary_row.html", "Services/Tracking");
		// $this->disable("footer");
		$this->initFilter($a_parent_obj->getObjectId());

		// $this->addMultiCommand("", $lng->txt(""));
		// $this->addCommandButton("", $lng->txt(""));
	}

	function getSelectableColumns()
	{
		global $lng;

		$columns = array();
		$all = array("user_total", "country", "registration_earliest", "registration_latest",
			"gender", "city", "language", "access_total", "access_average", "activity_earliest",
			"activity_latest", "time_average", "status", "mark", "completion_average");
		foreach($all as $column)
		{
			$l = $column;
			if(isset($this->lng_map[$l]))
			{
				$l = $this->lng_map[$l];
			}
			$columns[$column] = array(
				"txt" => $lng->txt($l),
				"default" => false
			);
		}
		return $columns;
	}

	/**
	* Init filter
	*/
	function initFilter($a_obj_id)
	{
		global $lng;

		include_once("./Services/Tracking/classes/class.ilTrQuery.php");
		
		$data = ilTrQuery::getFilterData($a_obj_id);

		$item = $this->addFilterItemByMetaType("title");
		$this->filter["title"] = $item->getValue();

		$item = $this->addFilterItemByMetaType("country", ilTable2GUI::FILTER_TEXT, true);
		$this->filter["country"] = $item->getValue();

		$item = $this->addFilterItemByMetaType("registration_earliest", ilTable2GUI::FILTER_DATE, true);
		$item->setDate(new ilDateTime($data["first_registration"], IL_CAL_DATETIME));
		$item->readFromSession();
		$this->filter["registration_earliest"] = $item->getDate();
		$item = $this->addFilterItemByMetaType("registration_latest", ilTable2GUI::FILTER_DATE, true);
		$item->setDate(new ilDateTime($data["last_registration"], IL_CAL_DATETIME));
		$item->readFromSession();
		$this->filter["registration_latest"] = $item->getDate();

		$item = $this->addFilterItemByMetaType("gender", ilTable2GUI::FILTER_SELECT, true);
		$item->setOptions(array("" => $lng->txt("all"), "m" => $lng->txt("gender_m"), "f" => $lng->txt("gender_f")));
		$this->filter["gender"] = $item->getValue();

        $item = $this->addFilterItemByMetaType("city", ilTable2GUI::FILTER_TEXT, true);
		$this->filter["city"] = $item->getValue();
		
        $item = $this->addFilterItemByMetaType("language", ilTable2GUI::FILTER_LANGUAGE, true);
		$this->filter["language"] = $item->getValue();
	}

	/**
	 * Fill table row
	 */
	protected function fillRow($a_set)
	{
		global $lng;

		if(!$a_set["title"])
		{
			$a_set["title"] = "--".$lng->txt("none")."--";
		}

		$this->tpl->setVariable("ICON", ilUtil::getTypeIconPath($a_set["type"], $a_set["id"], "small"));
	    $this->tpl->setVariable("TITLE", $a_set["title"]);

		foreach ($this->getSelectedColumns() as $c)
		{
			switch($c)
			{
				case "title":
				case "user_total":
				case "registration_earliest":
				case "registration_latest":
				case "access_total":
				case "access_average":
				case "activity_earliest":
				case "activity_latest":
				case "completion_average":
				case "time_average":
					$this->tpl->setVariable(strtoupper($c), $a_set[$c]);
					break;

				case "country":
				case "gender":
				case "city":
				case "language":
				case "status":
				case "mark":
					$this->tpl->touchBlock($c);
					$this->renderPercentages($c, $a_set[$c]);
					break;
			}
		}
	}

	protected function renderPercentages($id, $data)
	{
	  if($data)
	  {
		  $this->tpl->touchBlock($id."_percentages");
		  $this->tpl->setCurrentBlock($id."_row");
		  foreach($data as $item)
		  {
			$this->tpl->setVariable("CAPTION", $item["caption"]);
			$this->tpl->setVariable("ABSOLUTE", $item["absolute"]);
			$this->tpl->setVariable("PERCENTAGE", $item["percentage"]);
			$this->tpl->parseCurrentBlock();
		  }
		 
	   }
	}

	public function getCurrentFilter()
	{
		$result = array();
		foreach($this->filter as $id => $value)
		{
		  $item = $this->getFilterItemByPostVar($id);
		  switch($id)
		  {
			 case "title":
			 case "country":
			 case "gender":
			 case "city":
			 case "language":
			     if($value)
				 {
					 $result[$id] = $value;
				 }
				 break;

			 case "registration_earliest":
			 case "registration_latest":
                 $result[$id] = $value->get(IL_CAL_DATETIME);
				 break;
		  }
		}
		return $result;
	}
}
?>
