<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once("./Modules/DataCollection/classes/class.ilDataCollectionTable.php");

/**
* Class ilDataCollectionTableEditGUI
*
* @author Martin Studer <ms@studer-raimann.ch>
* @author Marcel Raimann <mr@studer-raimann.ch>
* @author Fabian Schmid <fs@studer-raimann.ch>
*
* @ingroup ModulesDataCollection
*/
	
class ilDataCollectionTableEditGUI
{
	
	/**
	 * Constructor
	 *
	 * @param	object	$a_parent_obj
	 */
	function __construct(ilObjDataCollectionGUI $a_parent_obj)
	{
		$this->obj_id = $a_parent_obj->obj_id;
	}

	
	/**
	 * execute command
	 */
	function executeCommand()
	{
		global $tpl, $ilCtrl, $ilUser;
		
		$cmd = $ilCtrl->getCmd();
		$tpl->getStandardTemplate();
		
		switch($cmd)
		{
			default:
				$this->$cmd();
				break;
		}

		return true;
	}

	/**
	 * create table add form
	*/
	public function create()
	{
		global $ilTabs, $tpl;
		
		$this->initForm();
		
		$tpl->setContent($this->form->getHTML());
	}

	/**
	 * create field edit form
	*/
	public function edit()
	{
		global $ilTabs, $tpl;
		
		$this->initForm("edit");
		//$this->getFieldValues();
		$tpl->setContent($this->form->getHTML());
	}
	
	
	/**
	 * initEditCustomForm
	 *
	 * @param string $a_mode
	 */
	public function initForm($a_mode = "create")
	{
		global $ilCtrl, $ilErr, $lng;
		
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		$item = new ilTextInputGUI($lng->txt('title'),'title');
		$this->form->addItem($item);
		
		$this->form->addCommandButton('save', 	$lng->txt('dcl_table_'.$a_mode));
		$this->form->addCommandButton('cancel', 	$lng->txt('cancel'));
		
		$this->form->setFormAction($ilCtrl->getFormAction($this, "save"));

		$this->form->setTitle($lng->txt('dcl_new_table'));
	}

	
	/**
	 * save
	 *
	 * @param string $a_mode values: create | edit
	*/
	public function save($a_mode = "create")
	{
		global $ilCtrl, $ilTabs, $lng, $ilUser;
		
		if(!ilObjDataCollection::_checkAccess($this->obj_id))
		{
			$this->accessDenied();
			return;
		}

		$ilTabs->activateTab("id_fields");
		
		$this->initForm($a_mode);
		
		if ($this->form->checkInput())
		{
			$table_obj = new ilDataCollectionTable();
		
			$table_obj->setTitle($this->form->getInput("title"));
			$table_obj->setObjId($this->obj_id);
			
			if(!$table_obj->hasPermissionToAddTable($ilUser->getId()))
			{
				$this->permissionDenied();
				return;
			}
			$table_obj->doCreate();

			ilUtil::sendSuccess($lng->txt("dcl_msg_table_created"), true);
			$ilCtrl->setParameterByClass("ildatacollectionfieldlistgui","table_id", $table_obj->getId());
			$ilCtrl->redirectByClass("ildatacollectionfieldlistgui", "listFields");
		}
		else
		{
			$this->form_gui->setValuesByPost();
			$this->tpl->setContent($this->form_gui->getHTML());
		}
	}
	
	/*
	 * accessDenied
	 */
	public function accessDenied()
	{
		global $tpl;
		$tpl->setContent("Access denied.");
	}

}

?>