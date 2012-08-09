<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
include_once("./Modules/DataCollection/classes/class.ilDataCollectionRecord.php");
include_once("./Modules/DataCollection/classes/class.ilDataCollectionTable.php");
include_once("./Modules/DataCollection/classes/class.ilDataCollectionDatatype.php");

/**
* Class ilDataCollectionRecordListGUI
*
* @author Martin Studer <ms@studer-raimann.ch>
* @author Marcel Raimann <mr@studer-raimann.ch>
* @author Fabian Schmid <fs@studer-raimann.ch>
* @version $Id: 
*
*
* @ingroup ModulesDataCollection
*/


class ilDataCollectionRecordListGUI
{
    private $table_obj;
	/**
	 * Constructor
     *
	 * @param	object	$a_parent_obj
     * @param	int $table_id
	 */
	public function  __construct($a_parent_obj, $table_id)
	{
		$this->main_table_id = $a_parent_obj->object->getMainTableId();
		$this->table_id = $table_id;
		$this->obj_id = $a_parent_obj->obj_id;
		$this->table_obj = new ilDataCollectionTable($table_id);

		return;
	}

	/**
	* execute command
	*/
	function executeCommand()
	{
		global $tpl, $ilCtrl;

		$cmd = $ilCtrl->getCmd();
		
		switch($cmd)
		{
			default:
				$this->$cmd();
				break;
		}
	}
	
	/**
	 * List Records
     *
     * 
	 */
	public function listRecords()
	{
		global $ilTabs, $tpl, $lng, $ilCtrl, $ilToolbar;
    
		//$ilTabs->setTabActive("id_records");

		// Show tables
		require_once("./Modules/DataCollection/classes/class.ilDataCollectionTable.php");
		$arrTables = ilDataCollectionTable::getAll($this->obj_id);
		foreach($arrTables as $table)
		{
				$options[$table['id']] = $table['title'];
		}
		include_once './Services/Form/classes/class.ilSelectInputGUI.php';
		$table_selection = new ilSelectInputGUI(
			'',
				'table_id'
			);
		$table_selection->setOptions($options);
		$table_selection->setValue($this->table_id);
		$ilToolbar->setFormAction($ilCtrl->getFormActionByClass("ilDataCollectionRecordListGUI", "doTableSwitch"));
        $ilToolbar->addInputItem($table_selection);
		$ilToolbar->addFormButton($lng->txt('change'),'doTableSwitch');

		//TODO Falls Reihenfolge festgelegt Reihenfolge und Felder festgelegt in DB abfragen. Andernfalls alle Felder anzeigen
		require_once("./Modules/DataCollection/classes/class.ilDataCollectionRecordListViewdefinition.php");
		$listViewdefinition = new ilDataCollectionRecordListViewdefinition($this->table_id);

		if(is_array($listViewdefinition->getArrTabledefinition()))
		{
			$tabledefinition = $listViewdefinition->getArrTabledefinition();
			$recordsfields	 = $listViewdefinition->getArrRecordfield();
		}
		else
		{
			require_once("./Modules/DataCollection/classes/class.ilDataCollectionField.php");
			$recordsfields = $this->table_obj->getFields();
  
			$tabledefinition = array(
									"id" => array("title" => $lng->txt("id")), 
									"dcl_table_id" => array("title" => $lng->txt("dcl_table_id")), 
									"create_date" => array("title" => $lng->txt("create_date")), 
									"last_update" => array("title" => $lng->txt("last_update")), 
									"owner" => array("title" => $lng->txt("owner"))
								);	

			foreach($recordsfields as $recordsfield) 
			{
				$tabledefinition["record_field_".$recordsfield['id']] = array("title" => $recordsfield['title'], "datatype_id" => $recordsfield['datatype_id']);
			}
		}					

		$records = ilDataCollectionRecord::getAll($this->table_id, $recordsfields, $tabledefinition);
		//echo "<pre>".print_r($records,1)."</pre>";
		/*echo "<pre>".print_r($tabledefinition,1)."</pre>";
		echo "<pre>".print_r($recordsfields,1)."</pre>";
		echo "<pre>".print_r($records,1)."</pre>";*/
		
		
		require_once('./Modules/DataCollection/classes/class.ilDataCollectionRecordListTableGUI.php');
		$list = new ilDataCollectionRecordListTableGUI($this, $ilCtrl->getCmd(), $records, $tabledefinition);
		
		$tpl->setContent($list->getHTML());
	}
	
	
	/**
	 * doTableSwitch
	 */
	public function doTableSwitch()
	{
		global $ilCtrl;

		$ilCtrl->setParameterByClass("ilObjDataCollectionGUI", "table_id", $_POST['table_id']);
		$ilCtrl->redirect($this, "listRecords"); 			
	}
	
}

?>