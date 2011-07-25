<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/DidacticTemplate/classes/class.ilDidacticTemplateAction.php';

/**
 * Description of class
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @ingroup ServicesDidacticTemplates
 */
class ilDidacticTemplateLocalPolicyAction extends ilDidacticTemplateAction
{

	const FILTER_POSITIVE = 1;
	const FILTER_NEGATIVE = 2;

	const TPL_ACTION_OVERWRITE = 1;
	const TPL_ACTION_INTERSECT = 2;
	const TPL_ACTION_ADD = 3;
	const TPL_ACTION_SUBTRACT = 4;
	const TPL_ACTION_UNION = 5;

	private $filter = array();
	private $filter_type = self::FILTER_POSITIVE;
	private $role_template_type = self::TPL_ACTION_OVERWRITE;
	private $role_template_id = 0;


	/**
	 * Constructor
	 * @param int $action_id 
	 */
	public function  __construct($action_id = 0)
	{
		parent::__construct($action_id);
	}

	/**
	 * Add filter
	 * @param ilDidactiTemplateLocalPolicyFilter $filter
	 */
	public function addFilter(ilDidactiTemplateLocalPolicyFilter $filter)
	{
		$this->filter[] = $filter;
	}

	/**
	 * Get filter
	 * @return array
	 */
	public function getFilter()
	{
		return $this->filter;
	}

	/**
	 * Set filter type
	 * @param int $a_type
	 */
	public function setFilterType($a_type)
	{
		$this->filter_type = $a_type;
	}

	/**
	 * Get filter type
	 * @return int
	 */
	public function getFilterType()
	{
		return $this->filter_type;
	}

	/**
	 * Set Role template type
	 * @param int $a_tpl_type
	 */
	public function setRoleTemplateType($a_tpl_type)
	{
		$this->role_template_type = $a_tpl_type;
	}

	/**
	 * Get role template type
	 */
	public function getRoleTemplateType()
	{
		return $this->role_template_type;
	}

	/**
	 * Set role template id
	 * @param int $a_id
	 */
	public function setRoleTemplateId($a_id)
	{
		$this->role_template_id = $a_id;
	}

	/**
	 * Get role template id
	 * @return int
	 */
	public function getRoleTemplateId()
	{
		return $this->role_template_id;
	}

	/**
	 * Save action
	 */
	public function save()
	{
		global $ilDB;

		parent::save();

		$query = 'INSERT INTO didactic_tpl_alp (action_id,filter_type,template_type,template_id) '.
			'VALUES( '.
			$ilDB->quote($this->getActionId(),'integer').', '.
			$ilDB->quote($this->getFilterType(),'integer').', '.
			$ilDB->quote($this->getRoleTemplateType(),'integer').', '.
			$ilDB->quote($this->getTemplateId(),'integer').' '.
			')';
		$ilDB->manipulate($query);

		foreach($this->getFilter() as $filter)
		{
			/* @var ilDidacticTemplateLocalPolicyFilter $filter */
			$filter->setActionId($this->getActionId());
			$filter->save();
		}
	}

	/**
	 * delete action filter
	 * @global ilDB $ilDB
	 * @return bool
	 */
	public function delete()
	{
		global $ilDB;

		parent::delete();

		$query = 'DELETE FROM didactic_tpl_alp '.
			'WHERE action_id  = '.$ilDB->quote($this->getActionId(),'integer');
		$ilDB->manipulate($query);

		foreach($this->getFilter() as $filter)
		{
			$filter->delete();
		}
		return true;
	}




	/**
	 * Apply action
	 */
	public function  apply()
	{
		;
	}

	/**
	 * Revert action
	 */
	public function  revert()
	{
		;
	}

	/**
	 * Get action type
	 * @return int
	 */
	public function getType()
	{
		return self::TYPE_LOCAL_POLICY;
	}

	/**
	 * Export to xml
	 * @param ilXmlWriter $writer
	 */
	public function  toXml(ilXmlWriter $writer)
	{
		;
	}

	/**
	 *  clone method
	 */
	public function  __clone()
	{
		parent::__clone();
		
	}

	public function read()
	{
		if(!parent::read())
		{
			return false;
		}
		// Read filter
		foreach(ilDidacticTemplateLocalPolicyFilter::lookupFilterIds($this->getActionId()) as $filter_id)
		{
			$this->addFilter(new ilDidacticTemplateLocalPolicyFilter($filter_id));
		}
	}

}
?>
