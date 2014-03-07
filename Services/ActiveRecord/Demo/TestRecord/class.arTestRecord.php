<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Demo/TestRecord/class.arTestRecordStorage.php');

/**
 * Class arTestRecord
 *
 * @description A Cliss which does not extend from ActiveRecord
 *              uses arStorage for dynamic DB usage
 *
 * @author      Fabian Schmid <fs@studer-raimann.ch>
 */
class arTestRecord {

	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        integer
	 * @db_length           4
	 */
	protected $id = 0;
	/**
	 * @var string
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           200
	 */
	protected $title = '';
	/**
	 * @var string
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           200
	 */
	public $description = '';
	/**
	 * @var array
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           200
	 */
	protected $usr_ids = array();
	/**
	 * @var arTestRecordStorage
	 */
	protected $storage;


	/**
	 * @param $id
	 */
	public function __construct($id = 0) {
		$this->id = $id;
		$this->storage = arTestRecordStorage::getInstance($this);
	}


	public function create() {
		$this->storage->create();
	}


	public function update() {
		$this->storage->update();
	}


	public function delete() {
		$this->storage->delete();
	}


	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param array $usr_ids
	 */
	public function setUsrIds($usr_ids) {
		$this->usr_ids = $usr_ids;
	}


	/**
	 * @return array
	 */
	public function getUsrIds() {
		return $this->usr_ids;
	}


	/**
	 * @param \arTestRecordStorage $storage
	 */
	public function setStorage($storage) {
		$this->storage = $storage;
	}


	/**
	 * @return \arTestRecordStorage
	 */
	public function getStorage() {
		return $this->storage;
	}
}

?>
