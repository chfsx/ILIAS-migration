<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

/**
* Class ilTestEvaluationUserData
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version $Id$
*
* @defgroup ModulesTest Modules/Test
* @extends ilObject
*/

include_once "./classes/class.ilObject.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

class ilTestEvaluationUserData
{
	/**
	* User name
	*
	* @var string
	*/
	var $name;

	/**
	* Login
	*
	* @var string
	*/
	var $login;

	/**
	* Reached points
	*
	* @var double
	*/
	var $reached;

	/**
	* Maximum available points
	*
	* @var double
	*/
	var $maxpoints;

	/**
	* Mark
	*
	* @var string
	*/
	var $mark;

	/**
	* ECTS Mark
	*
	* @var string
	*/
	var $markECTS;

	/**
	* Questions worked through
	*
	* @var integer
	*/
	var $questionsWorkedThrough;

	/**
	* Total number of questions
	*
	* @var integer
	*/
	var $numberOfQuestions;

	/**
	* Working time
	*
	* @var string
	*/
	var $timeOfWork;

	/**
	* First visit
	*
	* @var string
	*/
	var $firstVisit;

	/**
	* Last visit
	*
	* @var string
	*/
	var $lastVisit;
	
	/**
	* Is the test passed
	*
	* @var boolean
	*/
	var $passed;
	
	/**
	* Test passes
	*
	* @var array
	*/
	var $passes;

	/**
	* Questions
	*
	* @var array
	*/
	var $questions;

	/**
	* Constructor
	*
	* @access	public
	*/
	function ilTestEvaluationUserData()
	{
		$this->passes = array();
		$this->questions = array();
		$this->passed = FALSE;
	}
	
	function getPassed()
	{
		return $this->passed;
	}
	
	function setPassed($a_passed)
	{
		$this->passed = ($a_passed ? TRUE : FALSE);
	}
	
	function getName()
	{
		return $this->name;
	}
	
	function setName($a_name)
	{
		$this->name = $a_name;
	}
	
	function getLogin()
	{
		return $this->login;
	}
	
	function setLogin($a_login)
	{
		$this->login = $a_login;
	}
	
	function getReached()
	{
		return $this->reached;
	}
	
	function setReached($a_reached)
	{
		$this->reached = $a_reached;
	}
	
	function getMaxpoints()
	{
		return $this->maxpoints;
	}
	
	function setMaxpoints($a_max_points)
	{
		$this->maxpoints = $a_max_points;
	}
	
	function getReachedPointsInPercent()
	{
		return $this->getMaxPoints() ? $this->getReached() / $this->getMaxPoints() * 100.0 : 0;
	}
	
	function getMark()
	{
		return $this->mark;
	}
	
	function setMark($a_mark)
	{
		$this->mark = $a_mark;
	}
	
	function getECTSMark()
	{
		return $this->markECTS;
	}
	
	function setECTSMark($a_mark_ects)
	{
		$this->markECTS = $a_mark_ects;
	}
	
	function getQuestionsWorkedThrough()
	{
		return $this->questionsWorkedThrough;
	}
	
	function setQuestionsWorkedThrough($a_nr)
	{
		$this->questionsWorkedThrough = $a_nr;
	}

	function getNumberOfQuestions()
	{
		return $this->numberOfQuestions;
	}
	
	function setNumberOfQuestions($a_nr)
	{
		$this->numberOfQuestions = $a_nr;
	}
	
	function getQuestionsWorkedThroughInPercent()
	{
		return $this->getNumberOfQuestions() ? $this->getQuestionsWorkedThrough() / $this->getNumberOfQuestions() * 100.0 : 0;
	}
	
	function getTimeOfWork()
	{
		return $this->timeOfWork;
	}
	
	function setTimeOfWork($a_time_of_work)
	{
		$this->timeOfWork = $a_time_of_work;
	}
	
	function getFirstVisit()
	{
		return $this->firstVisit;
	}
	
	function setFirstVisit($a_time)
	{
		$this->firstVisit = $a_time;
	}
	
	function getLastVisit()
	{
		return $this->lastVisit;
	}
	
	function setLastVisit($a_time)
	{
		$this->lastVisit = $a_time;
	}
	
	function getPasses()
	{
		return $this->passes;
	}
	
	function addPass($pass_nr, $pass)
	{
		$this->passes[$pass_nr] = $pass;
	}
	
	function &getPass($pass_nr)
	{
		if (array_key_exists($pass_nr, $this->passes))
		{
			return $this->passes[$pass_nr];
		}
		else
		{
			return NULL;
		}
	}
	
	function getPassCount()
	{
		return count($this->passes);
	}
	
	function getBestPass()
	{
		$bestpoints = 0;
		$bestpass = 0;
		foreach ($this->passes as $pass)
		{
			$reached = $this->getReachedPointsInPercentForPass($pass->getPass());
			if ($reached > $bestpoints)
			{
				$bestpoints = $reached;
				$bestpass = $pass->getPass();
			}
		}
		return $bestpass;
	}
	
	function getLastPass()
	{
		$lastpass = 0;
		foreach (array_keys($this->passes) as $pass)
		{
			if ($pass > $lastpass) $lastpass = $pass;
		}
		return $lastpass;
	}
	
	function addQuestionTitle($question_id, $question_title)
	{
		$this->questionTitles[$question_id] = $question_title;
	}
	
	function getQuestionTitles()
	{
		return $this->questionTitles;
	}

	function &getQuestions($pass = 0)
	{
		if (array_key_exists($pass, $this->questions))
		{
			return $this->questions[$pass];
		}
		else
		{
			return NULL;
		}
	}
	
	function addQuestion($question_id, $max_points, $sequence = NULL, $pass = 0)
	{
		if (!array_key_exists($pass, $this->questions)) $this->questions[$pass] = array();
		array_push($this->questions[$pass], array("id" => $question_id, "points" => $max_points, "sequence" => $sequence));
	}
	
	function &getQuestion($index, $pass = 0)
	{
		if (array_key_exists($index, $this->questions[$pass]))
		{
			return $this->questions[$pass][$index];
		}
		else
		{
			return NULL;
		}
	}
	
	function getQuestionCount($pass = 0)
	{
		return count($this->questions[$pass]);
	}

	function getReachedPoints($pass = 0)
	{
		$reached = 0;
		if (array_key_exists($pass, $this->passes))
		{
			foreach ($this->passes[$pass]->getAnsweredQuestions() as $question)
			{
				$reached += $question["reached"];
			}
		}
		return $reached;
	}

	function getAvailablePoints($pass = 0)
	{
		$available = 0;
		if (!is_array($this->questions[$pass])) $pass = 0;
		foreach ($this->questions[$pass] as $question)
		{
			$available += $question["points"];
		}
		return $available;
	}

	function getReachedPointsInPercentForPass($pass = 0)
	{
		$reached = $this->getReachedPoints($pass);
		$available = $this->getAvailablePoints($pass);
		$percent = ($available > 0 ) ? $reached / $available : 0;
		return $percent;
	}
} // END ilTestEvaluationUserData

?>
