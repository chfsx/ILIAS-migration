<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package		Modules/Test
 */
class ilTestRandomQuestionSetSourcePoolDefinition
{
	/**
	 * global $ilDB object instance
	 *
	 * @var ilDB
	 */
	protected $db = null;
	
	/**
	 * object instance of current test
	 *
	 * @var ilObjTest
	 */
	protected $testOBJ = null;

    private $id = null;
	
	private $poolId = null;
	
	private $poolTitle = null;
	
	private $poolPath = null;
	
	private $poolQuestionCount = null;
	
	private $originalFilterTaxId = null;
	
	private $originalFilterTaxNodeId = null;

	private $mappedFilterTaxId = null;

	private $mappedFilterTaxNodeId = null;

	private $questionAmount = null;
	
	private $sequencePosition = null;
	
	public function __construct(ilDB $db, ilObjTest $testOBJ)
	{
		$this->db = $db;
		$this->testOBJ = $testOBJ;
	}

    public function setId($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}
	
	public function setPoolId($poolId)
	{
		$this->poolId = $poolId;
	}
	
	public function getPoolId()
	{
		return $this->poolId;
	}
	
	public function setPoolTitle($poolTitle)
	{
		$this->poolTitle = $poolTitle;
	}
	
	public function getPoolTitle()
	{
		return $this->poolTitle;
	}
	
	public function setPoolPath($poolPath)
	{
		$this->poolPath = $poolPath;
	}
	
	public function getPoolPath()
	{
		return $this->poolPath;
	}
	
	public function setPoolQuestionCount($poolQuestionCount)
	{
		$this->poolQuestionCount = $poolQuestionCount;
	}
	
	public function getPoolQuestionCount()
	{
		return $this->poolQuestionCount;
	}
	
	public function setOriginalFilterTaxId($originalFilterTaxId)
	{
		$this->originalFilterTaxId = $originalFilterTaxId;
	}
	
	public function getOriginalFilterTaxId()
	{
		return $this->originalFilterTaxId;
	}
	
	public function setOriginalFilterTaxNodeId($originalFilterNodeId)
	{
		$this->originalFilterTaxNodeId = $originalFilterNodeId;
	}
	
	public function getOriginalFilterTaxNodeId()
	{
		return $this->originalFilterTaxNodeId;
	}

	public function setMappedFilterTaxId($mappedFilterTaxId)
	{
		$this->mappedFilterTaxId = $mappedFilterTaxId;
	}

	public function getMappedFilterTaxId()
	{
		return $this->mappedFilterTaxId;
	}

	public function setMappedFilterTaxNodeId($mappedFilterTaxNodeId)
	{
		$this->mappedFilterTaxNodeId = $mappedFilterTaxNodeId;
	}

	public function getMappedFilterTaxNodeId()
	{
		return $this->mappedFilterTaxNodeId;
	}

	public function setQuestionAmount($questionAmount)
	{
		$this->questionAmount = $questionAmount;
	}
	
	public function getQuestionAmount()
	{
		return $this->questionAmount;
	}
	
	public function setSequencePosition($sequencePosition)
	{
		$this->sequencePosition = $sequencePosition;
	}
	
	public function getSequencePosition()
	{
		return $this->sequencePosition;
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**
	 * @param array $dataArray
	 */
	public function initFromArray($dataArray)
	{
		foreach($dataArray as $field => $value)
		{
			switch($field)
			{
				case 'def_id':				$this->setId($value);						break;
				case 'pool_fi':				$this->setPoolId($value);					break;
				case 'pool_title':			$this->setPoolTitle($value);				break;
				case 'pool_path':			$this->setPoolPath($value);					break;
				case 'pool_quest_count':	$this->setPoolQuestionCount($value);		break;
				case 'origin_tax_fi':		$this->setOriginalFilterTaxId($value);		break;
				case 'origin_node_fi':		$this->setOriginalFilterTaxNodeId($value);	break;
				case 'mapped_tax_fi':		$this->setMappedFilterTaxId($value);		break;
				case 'mapped_node_fi':		$this->setMappedFilterTaxNodeId($value);	break;
				case 'quest_amount':		$this->setQuestionAmount($value);			break;
				case 'sequence_pos':		$this->setSequencePosition($value);			break;
			}
		}
	}
	
	/**
	 * @param integer $poolId
	 * @return boolean
	 */
	public function loadFromDb($id)
	{
		$res = $this->db->queryF(
				"SELECT * FROM tst_rnd_quest_set_qpls WHERE def_id = %s", array('integer'), array($id)
		);
		
		while( $row = $this->db->fetchAssoc($res) )
		{
			$this->initFromArray($row);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return boolean
	 */
	public function saveToDb()
	{
		if( $this->getId() )
		{
			return $this->updateDbRecord();
		}
		
		return $this->insertDbRecord();
	}
	
	/**
	 * @return integer
	 */
	public function deleteFromDb()
	{
		$aff = $this->db->manipulateF(
				"DELETE FROM tst_rnd_quest_set_qpls WHERE def_id = %s", array('integer'), array($this->getId())
		);
		
		return $aff;
	}
	
	/**
	 * @return boolean
	 */
	private function dbRecordExists()
	{
		$res = $this->db->queryF(
			"SELECT COUNT(*) cnt FROM tst_rnd_quest_set_qpls WHERE test_fi = %s AND pool_fi = %s",
			array('integer', 'integer'), array($this->testOBJ->getTestId(), $this->getPoolId())
		);
		
		$row = $this->db->fetchAssoc($res);
		
		return (bool)$row['cnt'];
	}
	
	/**
	 * @return integer
	 */
	private function updateDbRecord()
	{
		$this->db->update('tst_rnd_quest_set_qpls',
			array(
				'test_fi' => array('integer', $this->testOBJ->getTestId()),
				'pool_fi' => array('integer', $this->getPoolId()),
				'pool_title' => array('text', $this->getPoolTitle()),
				'pool_path' => array('text', $this->getPoolPath()),
				'pool_quest_count' => array('integer', $this->getPoolQuestionCount()),
				'origin_tax_fi' => array('integer', $this->getOriginalFilterTaxId()),
				'origin_node_fi' => array('integer', $this->getOriginalFilterTaxNodeId()),
				'mapped_tax_fi' => array('integer', $this->getMappedFilterTaxId()),
				'mapped_node_fi' => array('integer', $this->getMappedFilterTaxNodeId()),
				'quest_amount' => array('integer', $this->getQuestionAmount()),
				'sequence_pos' => array('integer', $this->getSequencePosition())
			),
			array(
				'def_id' => array('integer', $this->getId())
			)
		);
	}
	
	/**
	 * @return boolean
	 */
	private function insertDbRecord()
	{
		$nextId = $this->db->nextId('tst_rnd_quest_set_qpls');

		$this->db->insert('tst_rnd_quest_set_qpls', array(
				'def_id' => array('integer', $nextId),
				'test_fi' => array('integer', $this->testOBJ->getTestId()),
				'pool_fi' => array('integer', $this->getPoolId()),
				'pool_title' => array('text', $this->getPoolTitle()),
				'pool_path' => array('text', $this->getPoolPath()),
				'pool_quest_count' => array('integer', $this->getPoolQuestionCount()),
				'origin_tax_fi' => array('integer', $this->getOriginalFilterTaxId()),
				'origin_node_fi' => array('integer', $this->getOriginalFilterTaxNodeId()),
				'mapped_tax_fi' => array('integer', $this->getMappedFilterTaxId()),
				'mapped_node_fi' => array('integer', $this->getMappedFilterTaxNodeId()),
				'quest_amount' => array('integer', $this->getQuestionAmount()),
				'sequence_pos' => array('integer', $this->getSequencePosition())
		));

		$this->testOBJ->setId($nextId);
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	public function getPoolInfoLabel(ilLanguage $lng)
	{
		$poolInfoLabel = sprintf(
			$lng->txt('tst_dynamic_question_set_source_questionpool_summary_string'),
			$this->getPoolTitle(),
			$this->getPoolPath(),
			$this->getPoolQuestionCount()
		);
		
		return $poolInfoLabel;
	}

	// -----------------------------------------------------------------------------------------------------------------
}
