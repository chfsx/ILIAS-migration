<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once("./Modules/DataCollection/classes/class.ilDataCollectionField.php");
require_once("./Modules/DataCollection/classes/class.ilDataCollectionDatatype.php");
require_once("./Modules/DataCollection/classes/class.ilDataCollectionTable.php");

/**
* Class ilDataCollectionFieldEditGUI
*
* @author Martin Studer <ms@studer-raimann.ch>
* @author Marcel Raimann <mr@studer-raimann.ch>
* @author Fabian Schmid <fs@studer-raimann.ch>
* @version $Id: 
*
*
*
* @ingroup ModulesDataCollection
*/
	
class ilDataCollectionFieldEditGUI
{
	private $obj_id;
	private $table_id;
    private $parent_obj;
	private $table;
    
	/**
	 * Constructor
	 *
	 * @param	object	$a_parent_obj
	 * @param	int $table_id We need a table_id if no field_id is set (creation mode). We ignore the table_id by edit mode
	 * @param	int $field_id The field_id of a existing fiel (edit mode) 
	*/
	public function __construct(ilObjDataCollectionGUI $a_parent_obj, $table_id, $field_id)
	{


		$this->obj_id = $a_parent_obj->obj_id;
        $this->parent_obj = $a_parent_obj;
		$this->table_id = $table_id;

		if(isset($field_id)) 
		{
			$this->field_obj = new ilDataCollectionField($field_id);
		}
		else
		{
			$this->field_obj = new ilDataCollectionField();
			//TODO prüfen ob table_id gesetzt, andernfalls Fehlermeldung und abbruch
			$this->field_obj->setTableId($table_id);
		}
		$this->table = new ilDataCollectionTable($table_id);
	}

	
	/**
	 * execute command
	 */
	function executeCommand()
	{
		global $tpl, $ilCtrl, $ilUser;
		
		$cmd = $ilCtrl->getCmd();

		if(!$this->table->hasPermissionToFields($ilUser->getId())){
			$this->permissionDenied();
			return;
		}

		switch($cmd)
		{
			case "update":
						$this->save("update");
						break;

			default:
				$this->$cmd();
				break;
		}

		return true;
	}
	
	/**
	 * create field add form
	*/
	public function create()
	{
		global $tpl;
		
		$this->initForm();
		$tpl->setContent($this->form->getHTML());
	}

	/**
	 * create field edit form
	*/
	public function edit()
	{
		global $tpl;
		
		$this->initForm("edit");
		$this->getValues();
		
		$tpl->setContent($this->form->getHTML());
	}
	
	/*
	 * permissionDenied
	 */
	public function permissionDenied()
	{
		global $tpl;
		$tpl->setContent("Permission denied");
	}
	
	
	
	/**
	 * confirmDelete
	 */
	public function confirmDelete()
	{
		global $ilCtrl, $lng, $tpl;
		
		include_once './Services/Utilities/classes/class.ilConfirmationGUI.php';
		$conf = new ilConfirmationGUI();
		$conf->setFormAction($ilCtrl->getFormAction($this));
		$conf->setHeaderText($lng->txt('dcl_confirm_delete_field'));

		$conf->addItem('field_id', (int) $this->field_obj->getId(), $this->field_obj->getTitle());
		
		$conf->setConfirm($lng->txt('delete'), 'delete');
		$conf->setCancel($lng->txt('cancel'), 'cancelDelete');

		$tpl->setContent($conf->getHTML());
	}
	
	/**
	 * cancelDelete
	 */
	public function cancelDelete()
	{
		global $ilCtrl;
		
		$ilCtrl->redirectByClass("ildatacollectionfieldlistgui", "listFields");
	}
	
	/*
	 * delete
	 */
    public function delete()
    {
        global $ilCtrl;
        
        $this->field_obj->doDelete();
        $ilCtrl->redirectByClass("ildatacollectionfieldlistgui", "listFields");
    }

	public function cancel(){
		global $ilCtrl;
        $ilCtrl->redirectByClass("ildatacollectionfieldlistgui", "listFields");
	}
	/**
	 * initEditCustomForm
	 *
	 * @param string $a_mode values: create | edit
	 */
	public function initForm($a_mode = "create")
	{
		global $ilCtrl, $lng;

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		
		
		if($a_mode == "edit")
		{
			$hidden_prop = new ilHiddenInputGUI("field_id");
			$this->form->addItem($hidden_prop);

			$this->form->setFormAction($ilCtrl->getFormAction($this),"update");
			$this->form->addCommandButton('update', $lng->txt('dcl_field_'.$a_mode));
		} 
		else 
		{
			$hidden_prop = new ilHiddenInputGUI("table_id");
			$hidden_prop->setValue($this->field_obj->getTableId());
			$this->form->addItem($hidden_prop);

			$this->form->setFormAction($ilCtrl->getFormAction($this),"save");
			$this->form->addCommandButton('save', 		$lng->txt('dcl_field_'.$a_mode));
		}
		
		$this->form->addCommandButton('cancel', 	$lng->txt('cancel'));
		$this->form->setTitle($lng->txt('dcl_new_field'));
		
		$text_prop = new ilTextInputGUI($lng->txt("title"), "title");
		$text_prop->setRequired(true);
		$this->form->addItem($text_prop);


			$edit_datatype = new ilRadioGroupInputGUI($lng->txt('dcl_datatype'),'datatype');
			
			foreach(ilDataCollectionDatatype::getAllDatatypes() as $datatype)
			{
				$opt = new ilRadioOption($lng->txt('dcl_'.$datatype['title']), $datatype['id']);
				
				foreach(ilDataCollectionDatatype::getProperties($datatype['id']) as $property)
				{
					//Type Reference: List Tabels
					if ($datatype['id'] == ilDataCollectionDatatype::INPUTFORMAT_REFERENCE) 
					{
					    // Get Tables
						require_once("./Modules/DataCollection/classes/class.ilDataCollectionTable.php");
						$arrTables = ilDataCollectionTable::getAll($this->obj_id);
						foreach($arrTables as $table)
						{
							$options[$table['id']] = $table['title'];
						}
						$table_selection = new ilSelectInputGUI(
							'',
								'prop_'.$property['id']
							);
						$table_selection->setOptions($options);
						//$table_selection->setValue($this->table_id);
						$opt->addSubItem($table_selection);
	
					} 
					//All other Types: List properties saved in propertie definition table
					elseif($property['datatype_id'] == $datatype['id']) 
					{
							$subitem = new ilTextInputGUI($lng->txt('dcl_'.$property['title']), 'prop_'.$property['id']);
							$opt->addSubItem($subitem);
					}
				}
				$edit_datatype->addOption($opt);
			}
			$edit_datatype->setRequired(true);

			//you can't change type but we still need it in POST
			if($a_mode == "edit")
				$edit_datatype->setDisabled(true);
			$this->form->addItem($edit_datatype);

		
		
		// Description
		$text_prop = new ilTextAreaInputGUI($lng->txt("dcl_field_description"), "description");
		$this->form->addItem($text_prop);
		
		// Required
		$cb = new ilCheckboxInputGUI($lng->txt("dcl_field_required"), "required");
		$this->form->addItem($cb);
	}

	/**
	 * getFieldValues
	 */
	public function getValues()
	{
		//Std-Values
		$values =  array(
			'table_id'		=>	$this->field_obj->getTableId(),
			'field_id'		=>	$this->field_obj->getId(),
			'title'			=>	$this->field_obj->getTitle(),
			'datatype'		=>	$this->field_obj->getDatatypeId(),
			'description'	=>	$this->field_obj->getDescription(),
			'required'		=>	$this->field_obj->getRequired(),
		);

		$propertyvalues = $this->field_obj->getPropertyvalues();

		
		// Propertie-Values - Subitems
		foreach(ilDataCollectionDatatype::getAllDatatypes() as $datatype)
		{
			foreach(ilDataCollectionDatatype::getProperties($datatype['id']) as $property)
			{
				$values['prop_'.$property['id']] = $propertyvalues[$property['id']];
			}
		}
		
		$this->form->setValuesByArray($values);
		
		return true;
	}
	
	
	/**
	 * save Field
	 *
	 * @param string $a_mode values: create | update
	 */
	public function save($a_mode = "create")
	{
		global $ilCtrl, $lng;

		//check access
		if(!$this->table->hasPermissionToFields($this->parent_obj->ref_id)){
			$this->accessDenied();
			return;
		}

		$this->initForm();
		if ($this->form->checkInput())
		{

			$this->field_obj->setTitle($this->form->getInput("title"));
			$this->field_obj->setDescription($this->form->getInput("description"));
			$this->field_obj->setDatatypeId($this->form->getInput("datatype"));
			$this->field_obj->setRequired($this->form->getInput("required"));
			
			if($a_mode == "update") 
			{
				$this->field_obj->doUpdate();
			}
			else 
			{
                $this->field_obj->setVisible(true);
				$this->field_obj->doCreate();
			}
		
			// Get possible properties and save them
			include_once("./Modules/DataCollection/classes/class.ilDataCollectionFieldProp.php");
			foreach(ilDataCollectionDatatype::getProperties($this->field_obj->getDatatypeId()) as $property)
			{
				$fieldprop_obj = new ilDataCollectionFieldProp();
				$fieldprop_obj->setDatatypePropertyId($property['id']);
				$fieldprop_obj->setFieldId($this->field_obj->getId());
				$fieldprop_obj->setValue($this->form->getInput("prop_".$property['id']));
				if($a_mode == "update")
				{
					$fieldprop_obj->doUpdate();
				}
				else
				{
					$fieldprop_obj->doCreate();
				}

			}
			
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"),true);

			$ilCtrl->setParameter($this, "field_id", $this->field_obj->getId());

			if($a_mode == "update")
			{
				$ilCtrl->redirect($this, "edit");
			}
			else
			{
				ilUtil::sendSuccess($lng->txt("msg_field_created"), false);
				$ilCtrl->redirectByClass(strtolower("ilDataCollectionFieldListGUI"), "listFields");
			}
		}
		else
		{
			global $tpl;
			$this->form->setValuesByPost();
			$tpl->setContent($this->form->getHTML());
		}
	}

	private function accessDenied(){
		global $tpl;
		$tpl->setContent("Access Denied");
	}
}

?>