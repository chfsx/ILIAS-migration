<?php
 /*
   +----------------------------------------------------------------------------+
   | ILIAS open source                                                          |
   +----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne           |
   |                                                                            |
   | This program is free software; you can redistribute it and/or              |
   | modify it under the terms of the GNU General Public License                |
   | as published by the Free Software Foundation; either version 2             |
   | of the License, or (at your option) any later version.                     |
   |                                                                            |
   | This program is distributed in the hope that it will be useful,            |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of             |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              |
   | GNU General Public License for more details.                               |
   |                                                                            |
   | You should have received a copy of the GNU General Public License          |
   | along with this program; if not, write to the Free Software                |
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. | 
   +----------------------------------------------------------------------------+
*/

require_once "PEAR.php";

/**
* Class for simple answers
* 
* ASS_AnswerSimple is a class for simple answers used for example in cloze tests with text gap.
*
* @author		Helmut Schottm�ller <hschottm@tzi.de>
* @version	$Id$
* @module   class.assAnswerSimple.php
* @modulegroup   Assessment
*/
class ASS_AnswerSimple extends PEAR
{
	/**
	* Answer text
	*
	* The answer text string of the ASS_AnswerSimple object
	*
	* @var string
	*/
	var $answertext;

	/**
	* Points for selected answer
	*
	* The number of points given for the selected answer
	*
	* @var double
	*/
	var $points;

	/**
	* A sort or display order
	*
	* A nonnegative integer value indicating a sort or display order of the answer. This value can be used by objects containing ASS_AnswerSimple objects.
	*
	* @var integer
	*/
	var $order;

	/**
	* ASS_AnswerSimple constructor
	*
	* The constructor takes possible arguments an creates an instance of the ASS_AnswerSimple object.
	*
	* @param string $answertext A string defining the answer text
	* @param double $points The number of points given for the selected answer
	* @param integer $order A nonnegative value representing a possible display or sort order
	* @access public
	*/
	function ASS_AnswerSimple (
		$answertext = "",
		$points = 0.0,
		$order = 0
	)
	{
		$this->answertext = $answertext;
		$this->points = $points;
		$this->order = $order;
	}

	/**
	* Gets the answer text
	*
	* Returns the answer text

	* @return string answer text
	* @access public
	* @see $answertext
	*/
	function get_answertext()
	{
		return $this->answertext;
	}

	/**
	* Gets the points
	*
	* Returns the points

	* @return double points
	* @access public
	* @see $points
	*/
	function get_points()
	{
		return $this->points;
	}

	/**
	* Gets the sort/display order
	*
	* Returns a nonnegative order value for display or sorting

	* @return integer order
	* @access public
	* @see $order
	*/
	function get_order()
	{
		return $this->order;
	}

	/**
	* Sets the order
	*
	* Sets the nonnegative order value which can be used for sorting or displaying multiple answers
	*
	* @param integer $order A nonnegative integer
	* @access public
	* @see $order
	*/
	function set_order($order = 0)
	{
		if ($order >= 0)
		{
			$this->order = $order;
		}
	}

	/**
	* Sets the answer text
	*
	* Sets the answer text
	*
	* @param string $answertext The answer text
	* @access public
	* @see $answertext
	*/
	function set_answertext($answertext = "")
	{
		$this->answertext = $answertext;
	}

	/**
	* Sets the points
	*
	* Sets the points given for selecting the answer. You can even use negative values for wrong answers.
	*
	* @param double $points The points given for the answer
	* @access public
	* @see $points
	*/
	function set_points($points = 0.0)
	{
		$this->points = $points;
	}
}

?>
