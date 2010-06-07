<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
 * Build table list for objects of given user
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 *
 * @ilCtrl_Calls ilTrUserObjectsPropsTableGUI: ilFormPropertyDispatchGUI
 * @ingroup ServicesTracking
 */
class ilTrUserObjectsPropsTableGUI extends ilLPTableBaseGUI
{
	/**
	* Constructor
	*/
	function __construct($a_parent_obj, $a_parent_cmd, $a_table_id, $a_user_id, $a_obj_id, $a_ref_id)
	{
		global $ilCtrl, $lng, $ilAccess, $lng, $rbacsystem;
		
		$this->setId($a_table_id);
		$this->user_id = $a_user_id;
		$this->obj_id = $a_obj_id;
		$this->ref_id = $a_ref_id;

		parent::__construct($a_parent_obj, $a_parent_cmd);

		$user = ilObjUser::_lookupFullName($this->user_id)." (".
			ilObjUser::_lookupLogin($this->user_id).")";

		$this->setTitle($this->lng->txt("trac_user_objects").": ".$user);
		
		$this->addColumn($this->lng->txt("title"), "title");
		
		foreach ($this->getSelectedColumns() as $c)
		{
			$l = $c;
			if (in_array($l, array("last_access", "first_access", "read_count", "spent_seconds", "mark", "status", "percentage")))
			{
				$l = "trac_".$l;
			}
			if ($l == "u_comment")
			{
				$l = "trac_comment";
			}
			$this->addColumn($this->lng->txt($l), $c);
		}

		$this->addColumn($this->lng->txt("actions"), "");

		$this->setExternalSorting(true);
		$this->setExternalSegmentation(true);
		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormActionByClass(get_class($this)));
		$this->setRowTemplate("tpl.user_objects_props_row.html", "Services/Tracking");
		//$this->disable("footer");
		$this->setEnableTitle(true);
		$this->initFilter();
		$this->setDefaultOrderField("title");
		$this->setDefaultOrderDirection("asc");

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
		global $lng;
	
		// default fields
		$cols = array();
		$cols["first_access"] = array(
			"txt" => $lng->txt("trac_first_access"),
			"default" => true);
		$cols["last_access"] = array(
			"txt" => $lng->txt("trac_last_access"),
			"default" => true);
		$cols["read_count"] = array(
			"txt" => $lng->txt("trac_read_count"),
			"default" => true);
		$cols["spent_seconds"] = array(
			"txt" => $lng->txt("trac_spent_seconds"),
			"default" => true);
		$cols["percentage"] = array(
			"txt" => $lng->txt("trac_percentage"),
			"default" => true);
		$cols["status"] = array(
			"txt" => $lng->txt("trac_status"),
			"default" => true);
		$cols["mark"] = array(
			"txt" => $lng->txt("trac_mark"),
			"default" => true);
		$cols["u_comment"] = array(
			"txt" => $lng->txt("trac_comment"),
			"default" => false);
		
		return $cols;
	}
	
	/**
	* Get user items
	*/
	function getItems()
	{
		global $lng, $tree;

		$this->determineOffsetAndOrder();
		
		$additional_fields = $this->getSelectedColumns();

		include_once("./Services/Tracking/classes/class.ilTrQuery.php");

		$tr_data = ilTrQuery::getObjectsDataForUser(
			$this->user_id,
			$this->obj_id,
			$this->ref_id,
			ilUtil::stripSlashes($this->getOrderField()),
			ilUtil::stripSlashes($this->getOrderDirection()),
			ilUtil::stripSlashes($this->getOffset()),
			ilUtil::stripSlashes($this->getLimit()),
			$this->filter,
			$additional_fields,
			$this->filter["view_mode"]);
			
		if (count($tr_data["set"]) == 0 && $this->getOffset() > 0)
		{
			$this->resetOffset();
			$tr_data = ilTrQuery::getObjectsDataForUser(
				$this->user_id,
				$this->obj_id,
				$this->ref_id,
				ilUtil::stripSlashes($this->getOrderField()),
				ilUtil::stripSlashes($this->getOrderDirection()),
				ilUtil::stripSlashes($this->getOffset()),
				ilUtil::stripSlashes($this->getLimit()),
				$this->filter,
				$additional_fields,
				$this->filter["view_mode"]);
		}

		$this->setMaxCount($tr_data["cnt"]);
		$this->setData($tr_data["set"]);
	}


	/**
	 * Get children for given object
	 * @param	int		$a_parent_id
	 * @param	array	$result
	 */
	function getObjectHierarchy($a_parent_id, array &$result)
	{
		include_once 'Services/Tracking/classes/class.ilLPCollectionCache.php';
		foreach(ilLPCollectionCache::_getItems($a_parent_id) as $child_ref_id)
		{
			$child_id = ilObject::_lookupObjId($child_ref_id);
			$result[] = $child_id;
			$this->getObjectHierarchy($child_id, $result);
		}
	}
	
	
	/**
	* Init filter
	*/
	function initFilter()
	{
		global $lng, $rbacreview, $ilUser;

		include_once("./Services/Form/classes/class.ilSubEnabledFormPropertyGUI.php");
		include_once("./Services/Table/interfaces/interface.ilTableFilterItem.php");
		
		// show collection only/all
		include_once("./Services/Form/classes/class.ilRadioGroupInputGUI.php");
		include_once("./Services/Form/classes/class.ilRadioOption.php");
		$ti = new ilRadioGroupInputGUI($lng->txt("trac_view_mode"), "view_mode");
		$ti->addOption(new ilRadioOption($lng->txt("trac_view_mode_all"), ""));
		$ti->addOption(new ilRadioOption($lng->txt("trac_view_mode_collection"), "coll"));
		$this->addFilterItem($ti);
		$ti->readFromSession();
		$this->filter["view_mode"] = $ti->getValue(); 
		
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
			$val = (trim($data[$c]) == "")
				? " "
				: $data[$c];

			if ($data[$c] != "" || $c == "status")
			{
				switch ($c)
				{
					case "first_access":
						$val = ilDatePresentation::formatDate(new ilDateTime($data[$c],IL_CAL_DATETIME));
						break;

					case "last_access":
						$val = ilDatePresentation::formatDate(new ilDateTime($data[$c],IL_CAL_UNIX));
						break;

					case "status":
						include_once("./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php");
						$path = ilLearningProgressBaseGUI::_getImagePathForStatus($data[$c]);
						$text = ilLearningProgressBaseGUI::_getStatusText($data[$c]);
						$val = ilUtil::img($path, $text);
						break;

					case "spent_seconds":
						include_once("./classes/class.ilFormat.php");
						$val = ilFormat::_secondsToString($data[$c]);
						break;

					case "percentage":
						$val = $data[$c]."%";
						break;

				}
			}
			if ($c == "mark" && in_array($this->type, array("lm", "dbk")))
			{
				$val = "-";
			}
			if ($c == "spent_seconds" && in_array($this->type, array("exc")))
			{
				$val = "-";
			}
			if ($c == "percentage" &&
				(in_array(strtolower($this->status_class),
						  array("illpstatusmanual", "illpstatusscormpackage", "illpstatustestfinished")) ||
				$this->type == "exc"))
			{
				$val = "-";
			}

			$this->tpl->setVariable("VAL_UF", $val);
			$this->tpl->parseCurrentBlock();
		}

		if($data["title"] == "")
		{
			// sessions have no title
			if($data["type"] == "sess")
			{
				include_once "./Modules/Session/classes/class.ilObjSession.php";
				$sess = new ilObjSession($data["obj_id"], false);
				$data["title"] = $sess->getFirstAppointment()->appointmentToString();
			}
			if($data["title"] == "")
			{
				$data["title"] = "--".$lng->txt("none")."--";
			}
		}
		$this->tpl->setVariable("ICON", ilUtil::getTypeIconPath($data["type"], $data["obj_id"], "small"));
		$this->tpl->setVariable("VAL_TITLE", $data["title"]);

		$this->tpl->setCurrentBlock("item_command");
		$ilCtrl->setParameterByClass("illplistofobjectsgui", "user_id", $this->user_id);
		$ilCtrl->setParameterByClass("illplistofobjectsgui", "details_id", $this->ref_id);
		$ilCtrl->setParameterByClass("illplistofobjectsgui", "userdetails_id", $data["ref_id"]);
		$this->tpl->setVariable("HREF_COMMAND", $ilCtrl->getLinkTargetByClass("illplistofobjectsgui", 'edituser'));
		$this->tpl->setVariable("TXT_COMMAND", $lng->txt('edit'));
		$ilCtrl->setParameterByClass("illplistofobjectsgui", "userdetails_id", "");
		$ilCtrl->setParameterByClass("illplistofobjectsgui", "details_id", "");
		$ilCtrl->setParameterByClass("illplistofobjectsgui", "user_id", "");
		$this->tpl->parseCurrentBlock();
	}

}
?>