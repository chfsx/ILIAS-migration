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
* Class ilObjSurveyQuestionPoolGUI
*
* @author Helmut Schottmüller <hschottm@tzi.de>
* $Id$
*
* @extends ilObjectGUI
* @package ilias-core
* @package assessment
*/

require_once "classes/class.ilObjectGUI.php";
require_once "classes/class.ilMetaDataGUI.php";

class ilObjSurveyQuestionPoolGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjSurveyQuestionPoolGUI($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
	{
    		global $lng;
		$this->type = "spl";
		$lng->loadLanguageModule("survey");
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference, false);
		$this->setTabTargetScript("surveypool.php");
		if ($a_prepare_output) {
			$this->prepareOutput();
		}
	}

	/**
	* save object
	* @access	public
	*/
	function saveObject()
	{
		global $rbacadmin;

		// create and insert forum in objecttree
		$newObj = parent::saveObject();

		// setup rolefolder & default local roles
		//$roles = $newObj->initDefaultRoles();

		// ...finally assign role to creator of object
		//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "y");

		// put here object specific stuff
			
		// always send a message
		sendInfo($this->lng->txt("object_added"),true);
		
		header("Location:".$this->getReturnLocation("save","surveypool.php?".$this->link_params));
		exit();
	}
	
	/**
	* Displays the question browser
	* @access	public
	*/
	function questionsObject() {
	}
	
} // END class.ilObjSurveyQuestionPoolGUI
?>
