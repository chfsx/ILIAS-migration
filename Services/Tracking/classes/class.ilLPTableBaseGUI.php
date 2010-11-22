<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");
include_once  './Services/Search/classes/class.ilSearchSettings.php';

/**
* TableGUI class for learning progress
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id$
*
* @ingroup ServicesTracking
*/
class ilLPTableBaseGUI extends ilTable2GUI
{
	protected $filter; // array

	public function __construct($a_parent_obj, $a_parent_cmd = "", $a_template_context = "")
	{
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

		// country names
		$this->lng->loadLanguageModule("meta");
	}

	public function executeCommand()
	{
		global $ilCtrl;

		$this->determineSelectedFilters();

		if(!$ilCtrl->getNextClass($this))
		{
			$to_hide = false;

			switch($ilCtrl->getCmd())
			{
				case "applyFilter":
					$this->resetOffset();
					$this->writeFilterToSession();
					break;

				case "resetFilter":
					$this->resetOffset();
					$this->resetFilter();
					break;

				case "hideSelected":
					$to_hide = $_POST["item_id"];
					break;

				case "hide":
					$to_hide = array((int)$_GET["hide"]);
					break;
			}

			if($to_hide)
			{
				$obj = $this->getFilterItemByPostVar("hide");
				$value = array_unique(array_merge((array)$obj->getValue(), $to_hide));
				$obj->setValue($value);
				$obj->writeToSession();
			}

			if(isset($_POST["tbltplcrt"]))
			{
				$ilCtrl->setParameter($this->parent_obj, "tbltplcrt", $_POST["tbltplcrt"]);
			}
			if(isset($_POST["tbltpldel"]))
			{
				$ilCtrl->setParameter($this->parent_obj, "tbltpldel", $_POST["tbltpldel"]);
			}

			$ilCtrl->redirect($this->parent_obj, $this->parent_cmd);
		}
        else
		{
			// e.g. repository selector
			return parent::executeCommand();
		}
	}

	/**
	 * Search objects that match current filters
	 *
	 * @param	array	$filter
	 * @param	string	$permission
	 * @return	array
	 */
	protected function searchObjects(array $filter, $permission)
	{
		global $ilObjDataCache;

		include_once './Services/Search/classes/class.ilQueryParser.php';

		$query_parser =& new ilQueryParser($filter["query"]);
		$query_parser->setMinWordLength(0);
		$query_parser->setCombination(QP_COMBINATION_OR);
		$query_parser->parse();
		if(!$query_parser->validate())
		{
			// echo $query_parser->getMessage();
			return false;
		}

		include_once 'Services/Search/classes/Like/class.ilLikeObjectSearch.php';
		$object_search =& new ilLikeObjectSearch($query_parser);
		$object_search->setFilter($filter["type"]);
		$res =& $object_search->performSearch();
		$res->setRequiredPermission($permission);
		$res->setMaxHits(1000);
		$res->addObserver($this, "searchFilterListener");

		if(!$this->filter["area"])
		{
			$res->filter(ROOT_FOLDER_ID, false);
		}
		else
		{
			$res->filter($this->filter["area"], false);
		}

		$objects = array();
		foreach($res->getResults() as $obj_data)
		{
			$objects[$obj_data['obj_id']][] = $obj_data['ref_id'];
		}

		// Check if search max hits is reached
		$this->limit_reached = $res->isLimitReached();

		return $objects ? $objects : array();
	}

	/**
	 * Listener for SearchResultFilter
	 * Checks wheather the object is hidden and mode is not LP_MODE_DEACTIVATED
	 * @access public
	 */
	public function searchFilterListener($a_ref_id, $a_data)
	{
		if(is_array($this->filter["hide"]) && in_array($a_data["obj_id"], $this->filter["hide"]))
		{
			return false;
		}
		// :TODO: mode does not have to be set in db
		if(ilLPObjSettings::_lookupMode($a_data["obj_id"]) == LP_MODE_DEACTIVATED)
		{
			return false;
		}
		return true;
	}
	
	/**
	* Init filter
	*/
	public function initFilter()
	{
		global $lng, $ilObjDataCache;
		
		$this->setDisableFilterHiding(true);
		
		// object type selection
		include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
		$si = new ilSelectInputGUI($this->lng->txt("obj_type"), "type");
		$si->setOptions($this->getPossibleTypes());
		$this->addFilterItem($si);
		$si->readFromSession();
		if(!$si->getValue())
		{
			$si->setValue("crs");
		}
		$this->filter["type"] = $si->getValue();

		// hidden items
		include_once("./Services/Form/classes/class.ilMultiSelectInputGUI.php");
		$msi = new ilMultiSelectInputGUI($lng->txt("trac_filter_hidden"), "hide");
		$this->addFilterItem($msi);
		$msi->readFromSession();
		$this->filter["hide"] = $msi->getValue();
		if($this->filter["hide"])
		{
			// create options from current value
			$types = $this->getCurrentFilter(true);
			$types = $types["type"];
			$options = array();
			foreach($this->filter["hide"] as $obj_id)
			{
				if(in_array($ilObjDataCache->lookupType($obj_id), $types))
				{
					$options[$obj_id] = $ilObjDataCache->lookupTitle($obj_id);
				}
			}
			$msi->setOptions($options);
		}

		// title/description
		include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		$ti = new ilTextInputGUI($lng->txt("trac_title_description"), "query");
		$ti->setMaxLength(64);
		$ti->setSize(20);
		$this->addFilterItem($ti);
		$ti->readFromSession();
		$this->filter["query"] = $ti->getValue();
		
		// repository area selection
		include_once("./Services/Form/classes/class.ilRepositorySelectorInputGUI.php");
		$rs = new ilRepositorySelectorInputGUI($lng->txt("trac_filter_area"), "area");
		$rs->setSelectText($lng->txt("trac_select_area"));
		$this->addFilterItem($rs);
		$rs->readFromSession();
		$this->filter["area"] = $rs->getValue();
	}

    /**
 	 * Build path with deep-link
	 *
	 * @param	array	$ref_ids
	 * @return	array 
	 */
	protected function buildPath($ref_ids)
	{
		global $tree, $ilCtrl;

		include_once 'classes/class.ilLink.php';
		
		if(!count($ref_ids))
		{
			return false;
		}
		foreach($ref_ids as $ref_id)
		{
			$path = "...";
			$counter = 0;
			$path_full = $tree->getPathFull($ref_id);
			foreach($path_full as $data)
			{
				if(++$counter < (count($path_full)-1))
				{
					continue;
				}
				$path .= " &raquo; ";
				if($ref_id != $data['ref_id'])
				{
					$path .= $data['title'];
				}
				else
				{
					$path .= ('<a target="_top" href="'.
							  ilLink::_getLink($data['ref_id'],$data['type']).'">'.
							  $data['title'].'</a>');
				}
			}

			$result[] = $path;
		}
		return $result;
	}

	/**
	* Get possible subtypes
	*/
	protected function getPossibleTypes()
	{
		global $lng;

		return array('lm' => $lng->txt('learning_resources'),
					 'crs' => $lng->txt('objs_crs'),
					 'tst' => $lng->txt('objs_tst'),
					 'grp' => $lng->txt('objs_grp'),
					 'exc' => $lng->txt('objs_exc'));
	}

	protected function parseValue($id, $value, $type)
	{
		global $lng;

		// get rid of aggregation
		$pos = strrpos($id, "_");
		if($pos !== false)
		{
			$function = strtoupper(substr($id, $pos+1));
			if(in_array($function, array("MIN", "MAX", "SUM", "AVG", "COUNT")))
			{
				$id = substr($id, 0, $pos);
			}
		}

		if(trim($value) == "" && $id != "status")
		{
			if($id == "title" && get_class($this) != "ilTrObjectUsersPropsTableGUI")
			{
				return "--".$lng->txt("none")."--";
			}
			return " ";
		}

		switch($id)
		{
			case "first_access":
			case "create_date":
				$value = ilDatePresentation::formatDate(new ilDateTime($value, IL_CAL_DATETIME));
				break;

			case "last_access":
				$value = ilDatePresentation::formatDate(new ilDateTime($value, IL_CAL_UNIX));
				break;

			case "birthday":
				$value = ilDatePresentation::formatDate(new ilDate($value, IL_CAL_DATE));
				break;

			case "spent_seconds":
				if(in_array($type, array("exc")))
				{
					$value = "-";
				}
				else
				{
					include_once("./classes/class.ilFormat.php");
					$value = ilFormat::_secondsToString($value);
				}
				break;

			case "percentage":
				/* :TODO:
				if(in_array(strtolower($this->status_class),
						  array("illpstatusmanual", "illpstatusscormpackage", "illpstatustestfinished")) ||
				$type == "exc"))
				*/
			    if(false)
				{
					$value = "-";
				}
				else
				{
					$value = $value."%";
				}
				break;

			case "mark":
				if(in_array($type, array("lm", "dbk")))
				{
					$value = "-";
				}
				break;

			case "gender":
				$value = $lng->txt("gender_".$value);
				break;

			case "status":
				include_once("./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php");
				$path = ilLearningProgressBaseGUI::_getImagePathForStatus($value);
				$text = ilLearningProgressBaseGUI::_getStatusText($value);
				$value = ilUtil::img($path, $text);
				break;

			case "language":
				$value = $lng->txt("lang_".$value);
				break;

			case "sel_country":
				$value = $lng->txt("meta_c_".$value);
				break;
		}

		return $value;
	}

	public function getCurrentFilter($as_query = false)
	{
		$result = array();
		foreach($this->filter as $id => $value)
		{
			$item = $this->getFilterItemByPostVar($id);
			switch($id)
			{
				case "type":
					switch($value)
					{
						case 'lm':
							$result["type"] = array('lm','sahs','htlm','dbk');
							break;

						default:
							$result["type"] = array($value);
							break;
					 }
					 break;

				case "title":
				case "country":
				case "gender":
				case "city":
				case "language":
				case "login":
				case "firstname":
				case "lastname":
				case "mark":
				case "u_comment":
				case "institution":
				case "department":
				case "title":
				case "street":
				case "zipcode":
				case "email":
				case "matriculation":
				case "sel_country":
				case "query":
					if($value)
					{
						$result[$id] = $value;
					}
					break;

				case "status":
					if($value !== false)
					{
						$result[$id] = $value;
					}
					break;

				case "user_total":
				case "read_count":
				case "percentage":
				case "hidden":
				case "spent_seconds":
					if(is_array($value) && implode("", $value))
					{
						$result[$id] = $value;
					}
					break;

				 case "registration":
				 case "create_date":
				 case "first_access":
				 case "last_access":
				 case "birthday":
					 if($value)
					 {
						 if($value["from"])
						 {
							 $result[$id]["from"] = $value["from"]->get(IL_CAL_DATETIME);
							 $result[$id]["from"] = substr($result[$id]["from"], 0, -8)."00:00:00";
						 }
						 if($value["to"])
						 {
							 $result[$id]["to"] = $value["to"]->get(IL_CAL_DATETIME);
							 $result[$id]["to"] = substr($result[$id]["to"], 0, -8)."23:59:59";
						 }
					 }
					 break;
		  }
		}

		return $result;
	}

	protected function isPercentageAvailable($a_obj_id)
	{
		include_once("./Services/Tracking/classes/class.ilLPObjSettings.php");
		$mode = ilLPObjSettings::_lookupMode($a_obj_id);
		if(in_array($mode, array(LP_MODE_TLT, LP_MODE_VISITS, LP_MODE_OBJECTIVES, LP_MODE_SCORM,
			LP_MODE_TEST_PASSED)))
		{
			return true;
		}
		return false;
	}

	protected function parseTitle($a_obj_id, $action, $a_user_id = false)
	{
		global $lng, $ilObjDataCache, $ilUser;

		$user = "";
		if($a_user_id)
		{
			if($a_user_id != $ilUser->getId())
			{
				$a_user = ilObjectFactory::getInstanceByObjId($a_user_id);
			}
			else
			{
				$a_user = $ilUser;
			}
			$user .= ", ".$a_user->getFullName(); // " [".$a_user->getLogin()."]";
		}

		$this->setTitle($lng->txt($action).": ".$ilObjDataCache->lookupTitle($a_obj_id).$user);
		$this->setDescription($this->lng->txt('trac_mode').": ".ilLPObjSettings::_mode2Text(ilLPObjSettings::_lookupMode($a_obj_id)));
	}

	/**
	 * Build export meta data
	 *
	 * @return array 
	 */
	protected function getExportMeta()
	{
		global $lng, $ilObjDataCache, $ilUser, $ilClientIniFile;

		/* see spec
			Name of installation
			Name of the course
			Permalink to course
			Owner of course object
			Date of report generation
			Reporting period
			Name of person who generated the report.
		*/

	    ilDatePresentation::setUseRelativeDates(false);
		include_once './classes/class.ilLink.php';
		
		$data = array(
			$lng->txt("trac_name_of_installation") => $ilClientIniFile->readVariable('client', 'name'),
			$lng->txt("trac_object_name") => $ilObjDataCache->lookupTitle($this->obj_id),
			$lng->txt("trac_object_link") => ilLink::_getLink($this->ref_id, ilObject::_lookupType($this->obj_id)),
			$lng->txt("trac_object_owner") => ilObjUser::_lookupFullname(ilObject::_lookupOwner($this->obj_id)),
			$lng->txt("trac_report_date") =>
				ilDatePresentation::formatDate(new ilDateTime(time(), IL_CAL_UNIX), IL_CAL_DATETIME),
			$lng->txt("trac_report_owner") => $ilUser->getFullName()
			);
		return $data;
	}

	protected function fillMetaExcel($worksheet, &$a_row)
	{
		foreach($this->getExportMeta() as $caption => $value)
		{
			$worksheet->write($a_row, 0, $caption);
			$worksheet->write($a_row, 1, $value);
			$a_row++;
		}
		$a_row++;
	}
	
	protected function fillMetaCSV($a_csv)
	{
		foreach($this->getExportMeta() as $caption => $value)
		{
			$a_csv->addColumn(strip_tags($caption));
			$a_csv->addColumn(strip_tags($value));
			$a_csv->addRow();
		}
		$a_csv->addRow();
	}
}

?>