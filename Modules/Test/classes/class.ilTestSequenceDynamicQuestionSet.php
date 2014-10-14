<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Test sequence handler
 *
 * This class manages the sequence settings for a given user
 * and a dynamic question set test
 *
 * @author	Björn Heyser <bheyser@databay.de>
 * @version	$Id$
 * @package	Modules/Test
 */
class ilTestSequenceDynamicQuestionSet
{
	/**
	 * @var ilDB
	 */
	private $db = null;
	
	/**
	 * @var ilTestDynamicQuestionSet
	 */
	private $questionSet = null;
	
	/**
	 * @var integer
	 */
	private $activeId = null;

	/**
	 * @var bool
	 */
	private $preventCheckedQuestionsFromComingUpEnabled;
	
	/**
	 * @var array
	 */
	private $questionTracking = array();

	/**
	 * @var integer
	 */
	private $newlyTrackedQuestion;

	/**
	 * @var string
	 */
	private $newlyTrackedQuestionsStatus;
	
	/**
	 * @var array
	 */
	private $postponedQuestions = array();

	/**
	 * @var integer
	 */
	private $newlyPostponedQuestion;

	/**
	 * @var integer
	 */
	private $newlyPostponedQuestionsCount;

	/**
	 * @var array
	 */
	private $alreadyCheckedQuestions;

	/**
	 * @var integer
	 */
	private $newlyCheckedQuestion;
	
	/**
	 * @var array
	 */
	private $correctAnsweredQuestions = array();
	
	/**
	 * @var array
	 */
	private $wrongAnsweredQuestions = array();

	/**
	 * @var integer
	 */
	private $newlyAnsweredQuestion;

	/**
	 * @var boolean
	 */
	private $newlyAnsweredQuestionsAnswerStatus;
	
	/**
	 * Constructor
	 * 
	 * @param ilTestDynamicQuestionSet $questionSet
	 */
	public function __construct(ilDB $db, ilTestDynamicQuestionSet $questionSet, $activeId)
	{
		$this->db = $db;
		$this->questionSet = $questionSet;
		$this->activeId = $activeId;

		$this->newlyTrackedQuestion = null;
		$this->newlyTrackedQuestionsStatus = null;
		
		$this->newlyPostponedQuestion = null;
		$this->newlyPostponedQuestionsCount = null;
		
		$this->newlyAnsweredQuestion = null;
		$this->newlyAnsweredQuestionsAnswerStatus = null;
		
		$this->alreadyCheckedQuestions = array();
		$this->newlyCheckedQuestion = null;

		$this->preventCheckedQuestionsFromComingUpEnabled = false;
	}
	
	public function getActiveId()
	{
		return $this->activeId;
	}

	public function setPreventCheckedQuestionsFromComingUpEnabled($preventCheckedQuestionsFromComingUpEnabled)
	{
		$this->preventCheckedQuestionsFromComingUpEnabled = $preventCheckedQuestionsFromComingUpEnabled;
	}

	public function isPreventCheckedQuestionsFromComingUpEnabled()
	{
		return $this->preventCheckedQuestionsFromComingUpEnabled;
	}
	
	public function loadFromDb()
	{
		$this->loadQuestionTracking();
		$this->loadAnswerStatus();
		$this->loadPostponedQuestions();
		$this->loadCheckedQuestions();
	}
	
	private function loadQuestionTracking()
	{
		$query = "
			SELECT		question_fi, status
			FROM		tst_seq_qst_tracking
			WHERE		active_fi = %s
			AND			pass = %s
			ORDER BY	orderindex ASC
		";
		
		$res = $this->db->queryF($query, array('integer','integer'), array($this->activeId, 0));

		$this->questionTracking = array();
		
		while( $row = $this->db->fetchAssoc($res) )
		{
			$this->questionTracking[] = array(
				'qid' => $row['question_fi'],
				'status' => $row['status']
			);
		}
	}
	
	private function loadAnswerStatus()
	{
		$query = "
			SELECT	question_fi, correctness
			FROM	tst_seq_qst_answstatus
			WHERE	active_fi = %s
			AND		pass = %s
		";

		$res = $this->db->queryF($query, array('integer','integer'), array($this->activeId, 0));

		$this->correctAnsweredQuestions = array();
		$this->wrongAnsweredQuestions = array();

		while( $row = $this->db->fetchAssoc($res) )
		{
			if( $row['correctness'] )
			{
				$this->correctAnsweredQuestions[ $row['question_fi'] ] = $row['question_fi'];
			}
			else
			{
				$this->wrongAnsweredQuestions[ $row['question_fi'] ] = $row['question_fi'];
			}
		}
	}
	
	private function loadPostponedQuestions()
	{
		$query = "
			SELECT	question_fi, cnt
			FROM	tst_seq_qst_postponed
			WHERE	active_fi = %s
			AND		pass = %s
		";

		$res = $this->db->queryF($query, array('integer','integer'), array($this->activeId, 0));
		
		$this->postponedQuestions = array();
		
		while( $row = $this->db->fetchAssoc($res) )
		{
			$this->postponedQuestions[ $row['question_fi'] ] = $row['cnt'];
		}
	}

	private function loadCheckedQuestions()
	{
		$res = $this->db->queryF("SELECT question_fi FROM tst_seq_qst_checked WHERE active_fi = %s AND pass = %s",
			array('integer','integer'), array($this->getActiveId(), 0)
		);

		while( $row = $this->db->fetchAssoc($res) )
		{
			$this->alreadyCheckedQuestions[ $row['question_fi'] ] = $row['question_fi'];
		}
	}
	
	public function saveToDb()
	{
		$this->saveNewlyTrackedQuestion();
		$this->saveNewlyAnsweredQuestionsAnswerStatus();
		$this->saveNewlyPostponedQuestion();
		$this->removeQuestionsNotPostponedAnymore();
		$this->saveNewlyCheckedQuestion();
	}
	
	private function saveNewlyTrackedQuestion()
	{
		if( (int)$this->newlyTrackedQuestion )
		{
			$newOrderIndex = $this->getNewOrderIndexForQuestionTracking();
			
			$this->db->replace('tst_seq_qst_tracking',
				array(
					'active_fi' => array('integer', (int)$this->getActiveId()),
					'pass' => array('integer', 0),
					'question_fi' => array('integer', (int)$this->newlyTrackedQuestion)
				),
				array(
					'status' => array('text', $this->newlyTrackedQuestionsStatus),
					'orderindex' => array('integer', $newOrderIndex)
				)
			);
		}
	}
	
	private function getNewOrderIndexForQuestionTracking()
	{
		$query = "
				SELECT (MAX(orderindex) + 1) new_order_index
				FROM tst_seq_qst_tracking
				WHERE active_fi = %s
				AND pass = %s
			";

		$res = $this->db->queryF($query, array('integer','integer'), array($this->getActiveId(), 0));
		
		$row = $this->db->fetchAssoc($res);
		
		return $row['new_order_index'];
	}

	private function saveNewlyAnsweredQuestionsAnswerStatus()
	{
		if( (int)$this->newlyAnsweredQuestion )
		{
			$this->db->replace('tst_seq_qst_answstatus',
				array(
					'active_fi' => array('integer', (int)$this->getActiveId()),
					'pass' => array('integer', 0),
					'question_fi' => array('integer', (int)$this->newlyAnsweredQuestion)
				),
				array(
					'correctness' => array('integer', (int)$this->newlyAnsweredQuestionsAnswerStatus)
				)
			);
		}
	}

	private function saveNewlyPostponedQuestion()
	{
		if( (int)$this->newlyPostponedQuestion )
		{
			$this->db->replace('tst_seq_qst_postponed',
				array(
					'active_fi' => array('integer', (int)$this->getActiveId()),
					'pass' => array('integer', 0),
					'question_fi' => array('integer', (int)$this->newlyPostponedQuestion)
				),
				array(
					'cnt' => array('integer', (int)$this->newlyPostponedQuestionsCount)
				)
			);
		}
	}
	
	private function removeQuestionsNotPostponedAnymore()
	{
		$INquestions = $this->db->in('question_fi', array_keys($this->postponedQuestions), true, 'integer');

		$query = "
			DELETE FROM tst_seq_qst_postponed
			WHERE active_fi = %s
			AND pass = %s
			AND $INquestions
		";
		
		$this->db->manipulateF($query, array('integer','integer'), array($this->getActiveId(), 0));
	}
	
	private function saveNewlyCheckedQuestion()
	{
		if( (int)$this->newlyCheckedQuestion )
		{
			$this->db->replace('tst_seq_qst_checked', array(
				'active_fi' => array('integer', (int)$this->getActiveId()),
				'pass' => array('integer', 0),
				'question_fi' => array('integer', (int)$this->newlyCheckedQuestion)
			), array());
		}
	}
	
	public function loadQuestions(ilObjTestDynamicQuestionSetConfig $dynamicQuestionSetConfig, ilTestDynamicQuestionSetFilterSelection $filterSelection)
	{
		$this->questionSet->load($dynamicQuestionSetConfig, $filterSelection);

//		echo "<table><tr>";
//		echo "<td width='200'><pre>".print_r($this->questionSet->getActualQuestionSequence(), 1)."</pre></td>";
//		echo "<td width='200'><pre>".print_r($this->correctAnsweredQuestions, 1)."</pre></td>";
//		echo "<td width='200'><pre>".print_r($this->wrongAnsweredQuestions, 1)."</pre></td>";
//		echo "</tr></table>";
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	public function cleanupQuestions(ilTestSessionDynamicQuestionSet $testSession)
	{
		switch( true )
		{
			case !$this->questionSet->questionExists($testSession->getCurrentQuestionId()):
			case !$this->isFilteredQuestion($testSession->getCurrentQuestionId()):
				
				$testSession->setCurrentQuestionId(null);
		}
		
		foreach($this->postponedQuestions as $questionId)
		{
			if( !$this->questionSet->questionExists($questionId) )
			{
				unset($this->postponedQuestions[$questionId]);
			}
		}
		
		foreach($this->wrongAnsweredQuestions as $questionId)
		{
			if( !$this->questionSet->questionExists($questionId) )
			{
				unset($this->wrongAnsweredQuestions[$questionId]);
			}
		}
		
		foreach($this->correctAnsweredQuestions as $questionId)
		{
			if( !$this->questionSet->questionExists($questionId) )
			{
				unset($this->correctAnsweredQuestions[$questionId]);
			}
		}
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	public function getUpcomingQuestionId()
	{
		if( $questionId = $this->fetchUpcomingQuestionId(true, true) )
			return $questionId;
		
		if( $questionId = $this->fetchUpcomingQuestionId(true, false) )
			return $questionId;

		if( $questionId = $this->fetchUpcomingQuestionId(false, true) )
			return $questionId;

		if( $questionId = $this->fetchUpcomingQuestionId(false, false) )
			return $questionId;

		return null;
	}
	
	private function fetchUpcomingQuestionId($excludePostponedQuestions, $forceNonAnsweredQuestion)
	{
		foreach($this->questionSet->getActualQuestionSequence() as $level => $questions)
		{
			$postponedQuestions = array();
			
			foreach($questions as $pos => $qId)
			{
				if( isset($this->correctAnsweredQuestions[$qId]) )
				{
					continue;
				}

				if( $this->isPreventCheckedQuestionsFromComingUpEnabled() && $this->isQuestionChecked($qId) )
				{
					continue;
				}

				if( $forceNonAnsweredQuestion && isset($this->wrongAnsweredQuestions[$qId]) )
				{
					continue;
				}
				
				if( isset($this->postponedQuestions[$qId]) )
				{
					$postponedQuestions[$qId] = $this->postponedQuestions[$qId];
					continue;
				}
				
				return $qId;
			}
			
			if( !$excludePostponedQuestions && count($postponedQuestions) )
			{
				return $this->fetchMostLeastPostponedQuestion($postponedQuestions);
			}
		}
		
		return null;
	}
	
	public function isAnsweredQuestion($questionId)
	{
		return (
			isset($this->correctAnsweredQuestions[$questionId])
			|| isset($this->wrongAnsweredQuestions[$questionId])
		);
	}
	
	public function isPostponedQuestion($questionId)
	{
		return isset($this->postponedQuestions[$questionId]);
	}
	
	public function isFilteredQuestion($questionId)
	{
		foreach($this->questionSet->getActualQuestionSequence() as $level => $questions)
		{
			if( in_array($questionId, $questions) )
			{
				return true;
			}
		}
		
		return false;
	}
	
	public function trackedQuestionExists()
	{
		return (bool)count($this->questionTracking);
	}
	
	public function getTrackedQuestionList($currentQuestionId = null)
	{
		$questionList = array();
		
		if( $currentQuestionId )
		{
			$questionList[$currentQuestionId] = $this->questionSet->getQuestionData($currentQuestionId);
		}
		
		foreach( array_reverse($this->questionTracking) as $trackedQuestion)
		{
			if( !isset($questionList[ $trackedQuestion['qid'] ]) )
			{
				$questionList[ $trackedQuestion['qid'] ] = $this->questionSet->getQuestionData($trackedQuestion['qid']);
			}
		}
		
		return $questionList;
	}
	
	public function resetTrackedQuestionList()
	{
		$this->questionTracking = array();
	}
	
	public function openQuestionExists()
	{
		return count($this->getOpenQuestions()) > 0;
	}
	
	public function getOpenQuestions()
	{
		$completeQuestionIds = array_keys( $this->questionSet->getAllQuestionsData() );

		$openQuestions = array_diff($completeQuestionIds, $this->correctAnsweredQuestions);
		
		return $openQuestions;
	}
	
	public function getTrackedQuestionCount()
	{
		$uniqueQuestions = array();
		
		foreach($this->questionTracking as $trackedQuestion)
		{
			$uniqueQuestions[$trackedQuestion['qid']] = $trackedQuestion['qid'];
		}
		
		return count($uniqueQuestions);
	}
	
	public function getCurrentPositionIndex($questionId)
	{
		$i = 0;
		
		foreach($this->questionSet->getActualQuestionSequence() as $level => $questions)
		{
			foreach($questions as $pos => $qId)
			{
				$i++;
				
				if($qId == $questionId)
				{
					return $i;
				}
			}
		}

		return null;
	}
	
	public function getLastPositionIndex()
	{
		$count = 0;
		
		foreach($this->questionSet->getActualQuestionSequence() as $level => $questions)
		{
			$count += count($questions);
		}
		
		return $count;
	}
	
	// -----------------------------------------------------------------------------------------------------------------

	public function setQuestionChecked($questionId)
	{
		$this->newlyCheckedQuestion = $questionId;
		$this->alreadyCheckedQuestions[$questionId] = $questionId;
	}

	public function isQuestionChecked($questionId)
	{
		return isset($this->alreadyCheckedQuestions[$questionId]);
	}

	public function setQuestionPostponed($questionId)
	{
		$this->trackQuestion($questionId, 'postponed');
		
		if( !isset($this->postponedQuestions[$questionId]) )
		{
			$this->postponedQuestions[$questionId] = 0;
		}
		
		$this->postponedQuestions[$questionId]++;
		
		$this->newlyPostponedQuestion = $questionId;
		$this->newlyPostponedQuestionsCount = $this->postponedQuestions[$questionId];
	}
	
	public function unsetQuestionPostponed($questionId)
	{
		if( isset($this->postponedQuestions[$questionId]) )
			unset($this->postponedQuestions[$questionId]);
	}

	public function setQuestionAnsweredCorrect($questionId)
	{
		$this->trackQuestion($questionId, 'correct');
		
		$this->correctAnsweredQuestions[$questionId] = $questionId;
		
		if( isset($this->wrongAnsweredQuestions[$questionId]) )
			unset($this->wrongAnsweredQuestions[$questionId]);
		
		$this->newlyAnsweredQuestion = $questionId;
		$this->newlyAnsweredQuestionsAnswerStatus = true;
	}

	public function setQuestionAnsweredWrong($questionId)
	{
		$this->trackQuestion($questionId, 'wrong');
		
		$this->wrongAnsweredQuestions[$questionId] = $questionId;
		
		if( isset($this->correctAnsweredQuestions[$questionId]) )
			unset($this->correctAnsweredQuestions[$questionId]);

		$this->newlyAnsweredQuestion = $questionId;
		$this->newlyAnsweredQuestionsAnswerStatus = false;
	}
	
	private function trackQuestion($questionId, $answerStatus)
	{
		$this->questionTracking[] = array(
			'qid' => $questionId, 'status' => $answerStatus
		);
		
		$this->newlyTrackedQuestion = $questionId;
		$this->newlyTrackedQuestionsStatus = $answerStatus;
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	public function hasStarted()
	{
		return $this->trackedQuestionExists();
	}

	// -----------------------------------------------------------------------------------------------------------------
	
	public function getFilteredQuestionsData()
	{
		return $this->questionSet->getFilteredQuestionList()->getQuestionDataArray();
	}

	// -----------------------------------------------------------------------------------------------------------------
	
	public function getUserSequenceQuestions()
	{
		//return array_keys( $this->getTrackedQuestionList() );
		
		$questionSequence = array();
		
		foreach( $this->questionSet->getActualQuestionSequence() as $level => $questions )
		{
			$questionSequence = array_merge($questionSequence, $questions);
		}
		
		return $questionSequence;
	}

	/**
	 * @param $postponedQuestions
	 * @return int|null|string
	 */
	private function fetchMostLeastPostponedQuestion($postponedQuestions)
	{
		$minPostponeCount = null;
		$minPostponeItem = null;

		foreach(array_reverse($postponedQuestions, true) as $qId => $postponeCount)
		{
			if($minPostponeCount === null || $postponeCount <= $minPostponeCount)
			{
				$minPostponeCount = $postponeCount;
				$minPostponeItem = $qId;
			}
		}
		return $minPostponeItem;
	}

}

