<?php
  /*
   +-----------------------------------------------------------------------------+
   | ILIAS open source                                                           |
   +-----------------------------------------------------------------------------+
   | Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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
   * Soap exercise administration methods
   *
   * @author Roland Küstermann <roland@kuestermann.com>
   * @version $Id: class.ilSoapExerciseAdministration.php 12992 2007-01-25 10:04:26Z rkuester $
   *
   * @package ilias
   */
include_once './webservice/soap/classes/class.ilSoapAdministration.php';

class ilSoapExerciseAdministration extends ilSoapAdministration
{
	function ilSoapExerciseAdministration()
	{
		parent::ilSoapAdministration();
	}

    /**
     * add an exercise with id.
     *
     * @param string $session_id    current session
     * @param int $target_id refid of parent in repository
     * @param string $exercise_xml   qti xml description of test
     *
     * @return int reference id in the tree, 0 if not successful
     */
	function addExercise ($sid, $target_id, $exercise_xml) {
   	    if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
        include_once './include/inc.header.php';
        global $rbacsystem, $tree, $ilLog;

        if(!$target_obj =& ilObjectFactory::getInstanceByRefId($target_id,false))
		{
			return $this->__raiseError('No valid target given.', 'Client');
		}

   		// Check access
		$allowed_types = array('cat','grp','crs','fold');
		if(!in_array($target_obj->getType(), $allowed_types))
		{
			return $this->__raiseError('No valid target type. Target must be reference id of "course, group, category or folder"', 'Client');
		}

	    if(!$rbacsystem->checkAccess('create',$target_id,"exc"))
		{
			return $this->__raiseError('No permission to create exercises in target  '.$target_id.'!', 'Client');
		}

        // create object, put it into the tree and use the parser to update the settings
		include_once './Modules/Exercise/classes/class.ilObjExercise.php';
		include_once './Modules/Exercise/classes/class.ilExerciseXMLParser.php';
		include_once './Modules/Exercise/classes/class.ilExerciseException.php';


		$exercise = new ilObjExercise();
        $exercise->create();
    	$exercise->createReference();
    	$exercise->putInTree($target_id);
    	$exercise->setPermissions($target_id);
    	$exercise->saveData();

    	// we need this as workaround because file and member objects need to be initialised
		$exercise->read();

		$exerciseXMLParser = new ilExerciseXMLParser($exercise, $exercise_xml);
        try
        {

            return $exerciseXMLParser->start() &&  $exercise->update() ? $exercise->getRefId() : 0;

        } catch(ilExerciseException $exception) {
            return $this->__raiseError($exception->getMessage(),
									   $exception->getCode() == ilExerciseException::$ID_MISMATCH ? "Client" : "Server");
        }
	}


    /**
     * update a exercise with id.
     *
     * @param string $session_id    current session
     * @param int $ref_id   refid id of exercise in repository
     * @param string $exercise_xml   qti xml description of test
     *
     * @return boolean true, if update successful, false otherwise
     */
	function updateExercise ($sid, $ref_id, $exercise_xml) {
	    if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
        include_once './include/inc.header.php';
        global $rbacsystem, $tree, $ilLog;

        // get obj_id
		if(!$obj_id = ilObject::_lookupObjectId($ref_id))
		{
			return $this->__raiseError('No exercise found for id: '.$ref_id,
									   'Client');
		}

   		// Check access
		$permission_ok = false;
		foreach($ref_ids = ilObject::_getAllReferences($obj_id) as $ref_id)
		{
			if($rbacsystem->checkAccess('edit',$ref_id))
			{
				$permission_ok = true;
				break;
			}
		}

		if(!$permission_ok)
		{
			return $this->__raiseError('No permission to edit the exercise with id: '.$ref_id,
									   'Server');
		}


		$exercise = ilObjectFactory::getInstanceByObjId($obj_id, false);

		if (!is_object($exercise) || $exercise->getType()!= "exc")
		{
            return $this->__raiseError('Wrong obj id or type for exercise with id '.$ref_id,
									   'Server');
		}

		include_once './Modules/Exercise/classes/class.ilExerciseXMLParser.php';
		include_once './Modules/Exercise/classes/class.ilExerciseException.php';
        $exerciseXMLParser = new ilExerciseXMLParser($exercise, $exercise_xml, $obj_id);

        try
        {

            return $exerciseXMLParser->start() && $exercise->update();

        } catch(ilExerciseException $exception) {
           return $this->__raiseError($exception->getMessage(),
									   $exception->getCode() == ilExerciseException::$ID_MISMATCH ? "Client" : "Server");
        }
        return false;
    }

	/**
	 * get exercise xml
	 *
	 * @param string $sid
	 * @param int $ref_id
	 * @param  int  $attachFileContentsMode see constants
	 *
	 * @return xml following ilias_exercise_x.dtd
	 */

	function getExerciseXML ($sid, $ref_id, $attachFileContentsMode) {
	    if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!strlen($ref_id))
		{
			return $this->__raiseError('No ref id given. Aborting!',
									   'Client');
		}
	    include_once './include/inc.header.php';
		global $rbacsystem, $tree, $ilLog;

		// get obj_id
		if(!$obj_id = ilObject::_lookupObjectId($ref_id))
		{
			return $this->__raiseError('No exercise found for id: '.$ref_id,
									   'Client');
		}


		// Check access
		$permission_ok = false;
		foreach($ref_ids = ilObject::_getAllReferences($obj_id) as $ref_id)
		{
			if($rbacsystem->checkAccess('read',$ref_id))
			{
				$permission_ok = true;
				break;
			}
		}

		if(!$permission_ok)
		{
			return $this->__raiseError('No permission to edit the object with id: '.$ref_id,
									   'Server');
		}

		$exercise = ilObjectFactory::getInstanceByObjId($obj_id, false);

		if (!is_object($exercise) || $exercise->getType()!= "exc")
		{
            return $this->__raiseError('Wrong obj id or type for exercise with id '.$ref_id,
									   'Server');
		}
   	    // store into xml result set
		include_once './Modules/Exercise/classes/class.ilExerciseXMLWriter.php';

		// create writer
		$xmlWriter = new ilExerciseXMLWriter();
		$xmlWriter->setExercise($exercise);
		$xmlWriter->setAttachFileContents($attachFileContentsMode);
		$xmlWriter->start();

		return $xmlWriter->getXML();
	}
}
?>