<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 8/9/12
 * Time: 9:44 AM
 * To change this template use File | Settings | File Templates.
 */

require_once './Modules/DataCollection/classes/class.ilDataCollectionWrongTypeException.php';

class ilDataCollectionRecordField
{
    private $id;
    private $field;
    private $record;
    private $value;

    function __construct(ilDataCollectionRecord $record, ilDataCollectionField $field)
    {
        $this->record = $record;
        $this->field = $field;
        $this->doRead();
    }

    private function doRead(){
        global $ilDB;
        $query = "SELECT * FROM il_dcl_record_field WHERE field_id LIKE '".$this->field->getId()."' AND record_id = ".$this->record->getId();
        $set = $ilDB->query($query);
        $rec = $ilDB->fetchAssoc($set);
        $this->id = $rec['id'];
        if($this->id == Null)
            $this->doCreate();
    }

    private function doCreate(){
        global $ilDB;

        $id = $ilDB->nextId("il_dcl_record_field");
        $query = "INSERT INTO il_dcl_record_field (id, record_id, field_id) VALUES (".$id.", ".$this->record->getId().", ".$this->field->getId().")";
        $ilDB->manipulate($query);
        $this->id = $id;
    }

    function getValue(){
        $this->loadValue();
        return $this->value?$this->value:"-";
    }

    function delete(){
        $datatype = $this->field->getDatatype();
        global $ilDB;
        $query = "DELETE FROM il_dcl_stloc".$datatype->getStorageLocation()."_value WHERE record_field_id = ".$this->id;
        $ilDB->manipulate($query);

        $query2 = "DELETE FROM il_dcl_record_field WHERE id = ".$this->id;
        $ilDB->manipulate($query2);
    }

    function setValue($value){
        $type = $this->field->getDatatype()->getId();
        $this->loadValue();
        if(!$this->checkValidity($type, $value))
            throw new ilDataCollectionWrongTypeException();
        else
            $this->value = $this->field->getDatatype()->parseValue($value);
    }

	private function checkValidity($type, $value){
		if(!ilDataCollectionDatatype::checkValidity($type, $value))
			return false;
		$properties = $this->field->getPropertyvalues();
		$length = ilDataCollectionField::PROPERTYID_LENGTH;
		$regex = ilDataCollectionField::PROPERTYID_REGEX;
		if($this->field->getDatatypeId() == ilDataCollectionDatatype::INPUTFORMAT_TEXT){
			echo "regex: ".$properties[$regex]." length: ".$properties[$length]."<br>";
			if($properties[$length] < strlen($value) && is_numeric($properties[$length]))
				return false;
			if($properties[$regex] !== Null && !preg_match($properties[$regex], $value))
				return false;
		}
		return true;
	}
    function getFormInput(){
        $datatype = $this->field->getDatatype();
        return $datatype->parseFormInput($this->getValue());
    }

    function getHTML(){
        $datatype = $this->field->getDatatype();
        return $datatype->parseHTML($this->getValue());
    }

    function doUpdate(){

        $datatype = $this->field->getDatatype();
        global $ilDB;
        $query = "DELETE FROM il_dcl_stloc".$datatype->getStorageLocation()."_value WHERE record_field_id = ".$this->id;
        $ilDB->manipulate($query);
		$next_id = $ilDB->nextId("il_dcl_stloc".$datatype->getStorageLocation()."_value");
        $ilDB->insert("il_dcl_stloc".$datatype->getStorageLocation()."_value",
            array("value" => array($datatype->getDbType(), $this->value),
            "record_field_id " => array("integer", $this->id),
			"id" => array("integer", $next_id))
        );
    }

    private function loadValue(){
        if($this->value == Null){
            global $ilDB;
            $datatype = $this->field->getDatatype();
            $query = "SELECT * FROM il_dcl_stloc".$datatype->getStorageLocation()."_value WHERE record_field_id = ".$this->id;
            $set = $ilDB->query($query);
            $rec = $ilDB->fetchAssoc($set);
            $this->value = $rec['value'];
        }
    }

}
