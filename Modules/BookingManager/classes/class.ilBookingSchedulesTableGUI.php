<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
 * List booking schedules (for booking pool)
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com> 
 * @version $Id$
 *
 * @ingroup ModulesBookingManager
 */
class ilBookingSchedulesTableGUI extends ilTable2GUI
{
	/**
	 * Constructor
	 * @param	object	$a_parent_obj
	 * @param	string	$a_parent_cmd
	 * @param	int		$a_ref_id
	 */
	function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id)
	{
		global $ilCtrl, $lng, $ilAccess, $lng, $ilObjDataCache;

		$this->ref_id = $a_ref_id;
		$this->setId("bksd");

		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setLimit(9999);
		
		if ($ilAccess->checkAccess('write', '', $this->ref_id))
		{
			$this->addCommandButton('create', $this->lng->txt('book_add_schedule'));
		}

		$this->addColumn($this->lng->txt("title"), "title");
		$this->addColumn($this->lng->txt("book_no_of_objects"));
		$this->addColumn($this->lng->txt("actions"));

		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
		$this->setRowTemplate("tpl.booking_schedule_row.html", "Modules/BookingManager");
		$this->initFilter();

		$this->getItems($ilObjDataCache->lookupObjId($this->ref_id), $this->getCurrentFilter());
	}

	/**
	* Init filter
	*/
	function initFilter()
	{
		global $lng;

		/*
		$item = $this->addFilterItemByMetaType("country", ilTable2GUI::FILTER_TEXT, true);
		$this->filter["country"] = $item->getValue();
		 */
	}

	/**
	 * Get current filter settings
	 * @return	array
	 */
	function getCurrentFilter()
	{

	}
	
	/**
	 * Build summary item rows for given object and filter(s)
	 *
	 * @param	int	$a_pool_id (aka parent obj id)
	 */
	function getItems($a_pool_id)
	{
		include_once 'Modules/BookingManager/classes/class.ilBookingSchedule.php';
		$data = ilBookingSchedule::getList($a_pool_id);
		
		$this->setMaxCount(sizeof($data));
		$this->setData($data);
	}

	/**
	 * Fill table row
	 * @param	array	$a_set
	 */
	protected function fillRow($a_set)
	{
		global $lng, $ilAccess, $ilCtrl;

	    $this->tpl->setVariable("TXT_TITLE", $a_set["title"]);
	    $this->tpl->setVariable("VALUE_OBJECTS_NO", $a_set["counter"]);

		$ilCtrl->setParameter($this->parent_obj, 'schedule_id', $a_set['booking_schedule_id']);
	
		if ($ilAccess->checkAccess('write', '', $this->ref_id))
		{
			$this->tpl->setCurrentBlock('item_command');

			$this->tpl->setVariable('HREF_COMMAND', $ilCtrl->getLinkTarget($this->parent_obj, 'confirmDelete'));
			$this->tpl->setVariable('TXT_COMMAND', $lng->txt('delete'));
			$this->tpl->parseCurrentBlock();

			$this->tpl->setVariable('HREF_COMMAND', $ilCtrl->getLinkTarget($this->parent_obj, 'edit'));
			$this->tpl->setVariable('TXT_COMMAND', $lng->txt('edit'));
			$this->tpl->parseCurrentBlock();
		}
	}
}
?>
