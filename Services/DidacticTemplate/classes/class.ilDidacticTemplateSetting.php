<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Settings for a single didactic template
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @defgroup ServicesDidacticTemplate
 */
class ilDidacticTemplateSetting
{
	private $id = 0;
	private $enabled = false;
	private $title = '';
	private $description = '';
	private $type = '';
	private $assignments = array();


	/**
	 * Constructor
	 * @param int $a_id
	 */
	public function __construct($a_id = 0)
	{
		$this->setId($a_id);
		$this->read();
	}

	/**
	 * Set id
	 * @param int $a_id 
	 */
	protected function setId($a_id)
	{
		$this->id = $a_id;
	}

	/**
	 * Get id
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set enabled status
	 * @param bool $a_status
	 */
	public function enable($a_status)
	{
		$this->enabled = $a_status;
	}

	/**
	 * Check if template is enabled
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * Set title
	 * @param string $a_title
	 */
	public function setTitle($a_title)
	{
		$this->title = $a_title;
	}

	/**
	 * Get title
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Get description
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set description
	 * @param string $a_desc
	 */
	public function setDescription($a_desc)
	{
		$this->description = $a_desc;
	}

	/**
	 * Set description
	 * @param string $a_description
	 */
	public function setDescription($a_description)
	{
		$this->description = $a_description;
	}

	/**
	 * Set type
	 * @param int $a_type
	 */
	public function setType($a_type)
	{
		$this->type = $a_type;
	}

	/**
	 * Get type
	 * @return int
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * Set assignments
	 * @param array $a_ass 
	 */
	public function setAssignments(Array $a_ass)
	{
		$this->assignments = (array) $a_ass;
	}

	/**
	 * Get object assignemnts
	 * @return array
	 */
	public function getAssignments()
	{
		return (array) $this->assignments;
	}

	/**
	 * Delete settings
	 */
	public function delete()
	{
		global $ilDB;

		// Delete settings
		$query = 'DELETE FROM didactic_tpl_settings '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$ilDB->manipulate($query);

		// Delete obj assignments
		$query = 'DELETE FROM didactic_tpl_settings_ass '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$ilDB->manipulate($query);

		return true;
	}

	/**
	 * Save settings
	 */
	public function save()
	{
		global $ilDB;

		$this->setId($ilDB->nextId('didactic_tpl_settings'));

		$query = 'INSERT INTO didactic_tpl_settings (id,enabled,title,description,type) '.
			'VALUES( '.
			$ilDB->quote($this->getId(),'integer').', '.
			$ilDB->quote($this->isEnabled(),'integer').', '.
			$ilDB->quote($this->getTitle(),'text').', '.
			$ilDB->quote($this->getDescription(),'text').', '.
			$ilDB->quote($this->getType(),'integer').
			')';
		$ilDB->manipulate($query);

		$this->saveAssignments();


		return true;
	}

	/**
	 * Save assignments in DB
	 * @return bool
	 */
	private function saveAssignments()
	{
		foreach($this->getAssignments() as $ass)
		{
			$this->saveAssignment($ass);
		}
		return true;
	}

	/**
	 * Add one object assignment
	 * @global ilDB $ilDB
	 * @param string $a_obj_type 
	 */
	private function saveAssignment($a_obj_type)
	{
		global $ilDB;

		$query = 'INSERT INTO didactic_tpl_settings_ass (id,obj_type) '.
			'VALUES( '.
			'id = '.$ilDB->quote($this->getId(),'integer').', '.
			'obj_type = '.$ilDB->quote($a_obj_type,'text').
			')';
		$ilDB->manipulate($query);
	}

	/**
	 * Delete assignments
	 * @global ilDB $ilDB
	 * @return bool
	 */
	private function deleteAssignments()
	{
		global $ilDB;

		$query = 'DELETE FROM didactic_tpl_settings_ass '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$ilDB->manipulate($query);
		return true;
	}

	/**
	 * Update settings
	 * @global ilDB $ilDB
	 */
	public function update()
	{
		global $ilDB;

		$query = 'UPDATE didactic_tpl_settings '.
			'SET '.
			'enabled = '.$ilDB->quote($this->isEnabled(),'integer').', '.
			'title = '.$ilDB->quote($this->getTitle(),'text').', '.
			'description = '.$ilDB->quote($this->getDescription(),'text').', '.
			'type = '.$ilDB->quote($this->getType(),'integer').' '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$ilDB->manipulate($query);

		$this->deleteAssignments();
		$this->saveAssignments();

		return true;
	}

	/**
	 * read settings from db
	 * @return bool
	 */
	protected function read()
	{
		global $ilDB;

		if(!$this->getId())
		{
			return false;
		}

		/**
		 * Read settings
		 */
		$query = 'SELECT * FROM didactic_tpl_settings dtpl '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->setType($row->type);
			$this->setTitle($row->title);
			$this->setDescription($row->description);
		}

		/**
		 * Read assigned objects
		 */
		$query = 'SELECT * FROM didactic_tpl_settings_ass '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->addAssignment($row->obj_type);
		}
		return true;
	}
}

?>