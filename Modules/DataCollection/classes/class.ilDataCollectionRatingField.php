<?php

require_once 'class.ilDataCollectionRecordField.php';
require_once("./Services/Rating/classes/class.ilRatingGUI.php");

class ilDataCollectionRatingField extends ilDataCollectionRecordField{

	/**
	 * @var bool
	 */
	protected $rated;

	/**
	 * @var int
	 */
	protected $dcl_obj_id;

	public function __construct(ilDataCollectionRecord $record, ilDataCollectionField $field){
		parent::__construct($record, $field);
		$dclTable = new ilDataCollectionTable($this->getField()->getTableId());
		$this->dcl_obj_id = $dclTable->getCollectionObject()->getId();
	}

	/**
	 * override the loadValue.
	 */
	protected function loadValue(){
		// explicitly do nothing. we don't have to load the value as it is saved somewhere else.
	}

	public function setValue($value){
		// explicitly do nothing. the value is handled via the model and gui of ilRating.
	}

	public function doUpdate(){
		// explicitly do nothing. the value is handled via the model and gui of ilRating.
	}

	public function doRead(){
		// explicitly do nothing. the value is handled via the model and gui of ilRating.
	}

	public function getFormInput(){
		global $lng;
		return $lng->txt("dcl_editable_in_table_gui");
	}

	public function getHTML(){
		global $ilCtrl;
		$rgui = new ilRatingGUI();
		$rgui->setObject($this->getRecord()->getId(), "dcl_record",
		$this->getField()->getId(), "dcl_field");
		$ilCtrl->setParameterByClass("ilratinggui", "field_id", $this->getField()->getId());
		$ilCtrl->setParameterByClass("ilratinggui", "record_id", $this->getRecord()->getId());
        $html = $rgui->getHTML();

		return $html;
	}

	public function getExportValue(){
		return ilRating::getOverallRatingForObject($this->getRecord()->getId(), "dcl_record",
			$this->getField()->getId(), "dcl_field");
	}

	public function getValue(){
		return ilRating::getOverallRatingForObject($this->getRecord()->getId(), "dcl_record",
			$this->getField()->getId(), "dcl_field");
	}
}
?>