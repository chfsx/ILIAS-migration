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


/**
* A class defining marks for assessment test objects
* 
* A class defining marks for assessment test objects
*
* @author		Helmut Schottmüller <hschottm@tzi.de>
* @version	$Id$
* @module   class.assMark.php
* @modulegroup   Assessment
*/
class ASS_Mark {
/**
* The short name of the mark
* 
* The short name of the mark, e.g. F or 3 or 1,3
*
* @var string
*/
  var $short_name;

/**
* The official name of the mark
* 
* The official name of the mark, e.g. failed, passed, befriedigend
*
* @var string
*/
  var $official_name;

/**
* The minimum percentage level reaching the mark
* 
* The minimum percentage level reaching the mark. A double value between 0 and 100
*
* @var double
*/
  var $minimum_level;

/**
* The passed status of the mark
* 
* The passed status of the mark. 0 indicates that the mark is failed, 1 indicates that the mark is passed
*
* @var integer
*/
  var $passed;

/**
* ASS_Mark constructor
* 
* The constructor takes possible arguments an creates an instance of the ASS_Mark object.
*
* @param string $short_name The short name of the mark
* @param string $official_name The official name of the mark
* @param double $minimum_level The minimum percentage level reaching the mark
* @access public
*/
  function ASS_Mark(
    $short_name = "",
    $official_name = "",
    $minimum_level = 0,
		$passed = 0
  ) 
  {
    $this->short_name = $short_name;
    $this->official_name = $official_name;
    $this->minimum_level = $minimum_level;
		$this->passed = $passed;
  }
  
/**
* Returns the short name of the mark
* 
* Returns the short name of the mark
*
* @return string The short name of the mark
* @access public
* @see $short_name
*/
  function get_short_name() {
    return $this->short_name;
  }
  
/**
* Returns passed status of the mark
* 
* Returns the passed status of the mark
*
* @return string The passed status of the mark
* @access public
* @see $passed
*/
  function get_passed() {
    return $this->passed;
  }
  
/**
* Returns the official name of the mark
* 
* Returns the official name of the mark
*
* @return string The official name of the mark
* @access public
* @see $official_name
*/
  function get_official_name() {
    return $this->official_name;
  }
  
/**
* Returns the minimum level reaching the mark
* 
* Returns the minimum level reaching the mark
*
* @return double The minimum level reaching the mark
* @access public
* @see $minimum_level
*/
  function get_minimum_level() {
    return $this->minimum_level;
  }
  
/**
* Sets the short name of the mark
* 
* Sets the short name of the mark
*
* @param string $short_name The short name of the mark
* @access public
* @see $short_name
*/
  function set_short_name($short_name = "") {
    $this->short_name = $short_name;
  }

/**
* Sets the passed status the mark
* 
* Sets the passed status of the mark
*
* @param integer $passed The passed status of the mark
* @access public
* @see $passed
*/
  function set_passed($passed = 0) {
    $this->passed = $passed;
  }

/**
* Sets the official name of the mark
* 
* Sets the official name of the mark
*
* @param string $official_name The official name of the mark
* @access public
* @see $official_name
*/
  function set_official_name($official_name = "") {
    $this->official_name = $official_name;
  }

/**
* Sets the minimum level reaching the mark
* 
* Sets the minimum level reaching the mark
*
* @param string $minimum_level The minimum level reaching the mark
* @access public
* @see $minimum_level
*/
  function set_minimum_level($minimum_level = 0) {
    if (($minmum_level >= 0) and ($minimum_level <= 100))
      $this->minimum_level = $minimum_level;
  }
}

?>