<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Table/classes/class.ilTable2GUI.php';
include_once "Services/AccessControl/classes/class.ilRbacLog.php";

/**
* Class ilRbacLogTableGUI
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
*
* @version $Id: class.ilObjRoleGUI.php 24339 2010-06-23 15:06:55Z jluetzen $
*
* @ilCtrl_Calls ilRbacLogTableGUI:
*
* @ingroup	ServicesAccessControl
*/
class ilRbacLogTableGUI extends ilTable2GUI
{
	protected $operations = array();
	protected $filter = array();
	protected $action_map = array();
	
	function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id)
	{
		global $ilCtrl, $lng, $ilAccess, $lng;

		$this->setId("rbaclog");
		$this->ref_id = $a_ref_id;

		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setTitle($lng->txt("rbac_log"));
		$this->setLimit(5);
		
		$this->addColumn($this->lng->txt("date"), "", "15%");
		$this->addColumn($this->lng->txt("user"), "", "15%");
		$this->addColumn($this->lng->txt("action"), "", "20%");
		$this->addColumn($this->lng->txt("rbac_changes"), "", "50%");

	    $this->setExternalSegmentation(true);
		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
		$this->setRowTemplate("tpl.rbac_log_row.html", "Services/AccessControl");
		$this->setFilterCommand("applyLogFilter");
		$this->setResetCommand("resetLogFilter");

		$this->action_map = array(ilRbacLog::EDIT_PERMISSIONS => $this->lng->txt("rbac_log_edit_permissions"),
			ilRbacLog::MOVE_OBJECT => $this->lng->txt("rbac_log_move_object"),
			ilRbacLog::LINK_OBJECT => $this->lng->txt("rbac_log_link_object"),
			ilRbacLog::COPY_OBJECT => $this->lng->txt("rbac_log_copy_object"),
			ilRbacLog::CREATE_OBJECT => $this->lng->txt("rbac_log_create_object"),
			ilRbacLog::EDIT_TEMPLATE => $this->lng->txt("rbac_log_edit_template"),
			ilRbacLog::EDIT_TEMPLATE_EXISTING=> $this->lng->txt("rbac_log_edit_template_existing"));

		$this->initFilter();

		$this->getItems($this->ref_id, $this->filter);
	}

	public function initFilter()
	{
		$item = $this->addFilterItemByMetaType("action", ilTable2GUI::FILTER_SELECT);
		$item->setOptions(array("" => $this->lng->txt("all"))+$this->action_map);
		$this->filter["action"] = $item->getValue();

		$item = $this->addFilterItemByMetaType("date", ilTable2GUI::FILTER_DATE_RANGE);
		$this->filter["date"] = $item->getDate();
	}

	protected function getItems($a_ref_id, array $a_current_filter = NULL)
	{
		global $rbacreview;

		$this->determineOffsetAndOrder();

		foreach($rbacreview->getOperations() as $op)
		{
			$this->operations[$op["ops_id"]] = $op["operation"];
		}

		$data = ilRbacLog::getLogItems($a_ref_id, $this->getLimit(), $this->getOffset(), $a_current_filter);

		$this->setData($data["set"]);
		$this->setMaxCount($data["cnt"]);
	}

	protected function fillRow($a_set)
	{
		$this->tpl->setVariable("DATE", ilDatePresentation::formatDate(new ilDateTime($a_set["created"], IL_CAL_UNIX)));
		$this->tpl->setVariable("USER", ilObjUser::_lookupFullname($a_set["user_id"]));
		$this->tpl->setVariable("ACTION", $this->action_map[$a_set["action"]]);

		if($a_set["action"] == ilRbacLog::EDIT_TEMPLATE)
		{
			$changes = $this->parseChangesTemplate($a_set["data"]);
		}
		else
		{
			$changes = $this->parseChangesFaPa($a_set["data"]);
		}

		$this->tpl->setCurrentBlock("changes");
		foreach($changes as $change)
		{
			$this->tpl->setVariable("CHANGE_ACTION", $change["action"]);
			$this->tpl->setVariable("CHANGE_OPERATION", $change["operation"]);
			$this->tpl->parseCurrentBlock();
		}
	}

	protected function parseChangesFaPa(array $raw)
	{
		$result = array();

		$type = ilObject::_lookupType($this->ref_id, true);
		
		if(isset($raw["src"]))
		{
			$obj_id = ilObject::_lookupObjectId($raw["src"]);
			if($obj_id)
			{
				include_once "classes/class.ilLink.php";
				$result[] = array("action"=>$this->lng->txt("rbac_log_source_object"),
							"operation"=>"<a href=\"".ilLink::_getLink($raw["src"])."\">".ilObject::_lookupTitle($obj_id)."</a>");
			}
			
			// added only
			foreach($raw["ops"] as $role_id => $ops)
			{
				foreach($ops as $op)
				{
					$result[] = array("action"=>sprintf($this->lng->txt("rbac_log_operation_add"), ilObject::_lookupTitle($role_id)),
						"operation"=>$this->lng->txt($type."_".$this->operations[$op]));
				}
			}
		}
		else if(isset($raw["ops"]))
		{
			foreach($raw["ops"] as $role_id => $actions)
			{
				foreach($actions as $action => $ops)
				{
					foreach($ops as $op)
					{
						$result[] = array("action"=>sprintf($this->lng->txt("rbac_log_operation_".$action), ilObject::_lookupTitle($role_id)),
							"operation"=>$this->lng->txt($type."_".$this->operations[$op]));
					}
				}
			}
		}

		if(isset($raw["inht"]))
		{
			foreach($raw["inht"] as $action => $role_ids)
			{
				foreach($role_ids as $role_id)
				{
					$result[] = array("action"=>sprintf($this->lng->txt("rbac_log_inheritance_".$action), ilObject::_lookupTitle($role_id)));
				}
			}
		}

        return $result;
	}

	protected function parseChangesTemplate(array $raw)
	{
		$result = array();
		foreach($raw as $type => $actions)
		{
			foreach($actions as $action => $ops)
			{
				foreach($ops as $op)
				{
					$result[] = array("action"=>sprintf($this->lng->txt("rbac_log_operation_add"), $this->lng->txt("obj_".$type)),
						"operation"=>$this->lng->txt($type."_".$this->operations[$op]));
				}
			}
		}
		return $result;
	}
}

?>