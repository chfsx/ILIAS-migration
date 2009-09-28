<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");
include_once  './Services/Search/classes/class.ilSearchSettings.php';

/**
* TableGUI class for learning progress (object overview)
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilLPObjectsTableGUI: ilFormPropertyDispatchGUI
* @ingroup ServicesTracking
*/
class ilLPObjectsTableGUI extends ilTable2GUI
{
	protected $objs = array();
	
	
	/**
	* Constructor
	*/
	function __construct($a_parent_obj, $a_parent_cmd, $a_type = "", $a_objs = "")
	{
		global $ilCtrl, $lng, $ilAccess, $lng;

		$this->tracked_user = $a_user;
		$this->objs = $a_objs;
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setTitle($this->lng->txt("learning_progress"));
		$this->setLimit(ilSearchSettings::getInstance()->getMaxHits());
		$this->setLimit(9999);
		
		$this->addColumn("", "", "1", true);
		$this->addColumn($this->lng->txt("trac_title_description"), "", "90%");
		$this->addColumn($this->lng->txt("actions"), "", "10%");
		
		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormActionByClass("illpfiltergui"));
		$this->setRowTemplate("tpl.lp_object_list_row.html", "Services/Tracking");
		#$this->disable("footer");
		$this->setEnableHeader(true);
		$this->setEnableNumInfo(true);
		$this->setEnableTitle(true);
		$this->setId("lp_obj_table");
		$this->initFilter();

		$this->setSelectAllCheckbox("item_id");

		$this->addMultiCommand("hideSelected", $lng->txt("trac_hide_selected"));
		//$this->addCommandButton("", $lng->txt(""));
	}
	
	/**
	* Init filter
	*/
	function initFilter()
	{
		global $lng;
		
		$this->setDisableFilterHiding(true);
		
		include_once("./Services/Tracking/classes/class.ilLPFilterGUI.php");
		$filter_gui = new ilLPFilterGUI($this->tracked_user);
		
		// object type selection
		include_once("./Services/Tracking/classes/class.ilLPFilterGUI.php");
		$options = ilLPFilterGUI::getPossibleTypes();
		include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
		$si = new ilSelectInputGUI($this->lng->txt("obj_type"), "type");
		$si->setOptions($options);
		$si->setValue($filter_gui->filter->getFilterType());
		$this->addFilterItem($si);

		// hidden items
		$options = $filter_gui->prepareHidden();
		$values = array_keys($options);
		if (count($options) > 0)
		{
			include_once("./Services/Form/classes/class.ilMultiSelectInputGUI.php");
			$msi = new ilMultiSelectInputGUI($lng->txt("trac_filter_hidden"), "hide");
			$msi->setValue($values);
			$msi->setOptions($options);
			$this->addFilterItem($msi);
		}
		else
		{
			include_once("./Services/Form/classes/class.ilNonEditableValueGUI.php");
			$ne = new ilNonEditableValueGUI($lng->txt("trac_filter_hidden"),
				"dummy");
			$ne->setValue($lng->txt("none"));
			$this->addFilterItem($ne);
		}

		// title/description
		include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		$ti = new ilTextInputGUI($lng->txt("trac_title_description"), "query");
		$ti->setMaxLength(64);
		$ti->setSize(20);
		$ti->setValue($filter_gui->filter->getQueryString());
		$this->addFilterItem($ti);
		
		// repository area selection
		include_once("./Services/Form/classes/class.ilRepositorySelectorInputGUI.php");
		$rs = new ilRepositorySelectorInputGUI($lng->txt("trac_filter_area"), "area");
		$rs->setSelectText($lng->txt("trac_select_area"));
		$this->addFilterItem($rs);
		$rs->readFromSession();

	}
	
	/**
	* Fill table row
	*/
	protected function fillRow($object_id)
	{
		global $lng, $ilObjDataCache, $ilCtrl;

		$this->tpl->setCurrentBlock("item_command");
		$ilCtrl->setParameterByClass('illpfiltergui','hide',$object_id);
		$this->tpl->setVariable("HREF_COMMAND", $ilCtrl->getLinkTargetByClass('illpfiltergui','hide'));
		$this->tpl->setVariable("TXT_COMMAND", $lng->txt('trac_hide'));
		$this->tpl->parseCurrentBlock();

		//$this->tpl->setVariable("", );
		
		$item_list = ilLPItemListFactory::_getInstance(0,$object_id,$ilObjDataCache->lookupType($object_id));
		$item_list->addCheckbox("");
		$item_list->setCmdClass(get_class($this->parent_obj));
		$item_list->addReferences($this->objs[$object_id]['ref_ids']);
		$item_list->enable('path');
		$item_list->renderObjectList();
		
		// Hide link
		$this->tpl->setVariable("OBJ_ID", $object_id);
		$this->tpl->setVariable("ITEM_HTML",$item_list->getHTML());
		ilLearningProgressBaseGUI::_showImageByStatus($this->tpl,
			$item_list->getUserStatus());
	}

}
?>
