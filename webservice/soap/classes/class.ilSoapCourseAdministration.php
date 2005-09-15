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
   * Soap course administration methods
   *
   * @author Stefan Meyer <smeyer@databay.de>
   * @version $Id$
   *
   * @package ilias
   */
include_once './webservice/soap/classes/class.ilSoapAdministration.php';

class ilSoapCourseAdministration extends ilSoapAdministration
{
	function ilSoapCourseAdministration()
	{
		parent::ilSoapAdministration();
	}
		

	// Service methods
	function addCourse($sid,$target_id,$crs_xml)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}			

		if(!is_numeric($target_id))
		{
			return $this->__raiseError('No valid target id given. Please choose an existing reference id of an ILIAS category',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';

		if(!$rbacsystem->checkAccess('create',$target_id,'crs'))
		{
			return $this->__raiseError('Check access failed. No permission to create courses','Server');
		}

		// Start import
		include_once("course/classes/class.ilObjCourse.php");

		$newObj = new ilObjCourse();
		$newObj->setType('crs');
		$newObj->setTitle('dummy');
		$newObj->setDescription("");
		$newObj->create(true); // true for upload
		$newObj->createReference();
		$newObj->putInTree($target_id);
		$newObj->setPermissions($target_id);
		$newObj->__initDefaultRoles();

		include_once 'course/classes/class.ilCourseXMLParser.php';

		$xml_parser = new ilCourseXMLParser($newObj);
		$xml_parser->setXMLContent($crs_xml);

		$xml_parser->startParsing();

		$newObj->MDUpdateListener('General');

		return $newObj->getId() ? $newObj->getId() : 0;
		
	}

	function deleteCourse($sid,$course_id)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}			

		if(!is_numeric($course_id))
		{
			return $this->__raiseError('No valid course id given. Please choose an existing reference id of an ILIAS course',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';

		if(ilObject::_lookupType(ilObject::_lookupObjId($course_id)) != 'crs')
		{
			return $this->__raiseError('Invalid course id. Object with id "'. $course_id.'" is not of type "course"','Client');
		}

		if(!$rbacsystem->checkAccess('delete',$course_id))
		{
			return $this->__raiseError('Check access failed. No permission to delete course','Server');
		}


		global $tree,$rbacadmin;

		$subnodes = $tree->getSubtree($tree->getNodeData($course_id));
			
		foreach ($subnodes as $subnode)
		{
			$rbacadmin->revokePermission($subnode["child"]);

			// remove item from all user desktops
			$affected_users = ilUtil::removeItemFromDesktops($subnode["child"]);
				
			$tree->saveSubTree($course_id);
			$tree->deleteTree($tree->getNodeData($course_id));

			// write log entry
			$log->write("SOAP ilObjectGUI::confirmedDeleteObject(), moved ref_id ".$course_id." to trash");
				
			// remove item from all user desktops
			$affected_users = ilUtil::removeItemFromDesktops($course_id);
		}
		return true;
	}

	function assignCourseMember($sid,$course_id,$user_id,$type)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}

		if(!is_numeric($course_id))
		{
			return $this->__raiseError('No valid course id given. Please choose an existing reference id of an ILIAS course',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';

		if(ilObject::_lookupType(ilObject::_lookupObjId($course_id)) != 'crs')
		{
			return $this->__raiseError('Invalid course id. Object with id "'. $course_id.'" is not of type "course"','Client');
		}
		
		if(ilObject::_lookupType($user_id) != 'usr')
		{
			return $this->__raiseError('Invalid user id. User with id "'. $user_id.' does not exist','Client');
		}
		if($type != 'Admin' and
		   $type != 'Tutor' and
		   $type != 'Member')
		{
			return $this->__raiseError('Invalid type given. Parameter "type" must be "Admin", "Tutor" or "Member"','Client');
		}

		if(!$tmp_course = ilObjectFactory::getInstanceByRefId($course_id,false))
		{
			return $this->__raiseError('Cannot create course instance!','Server');
		}

		if(!$tmp_user = ilObjectFactory::getInstanceByObjId($user_id,false))
		{
			return $this->__raiseError('Cannot create user instance!','Server');
		}

		include_once 'course/classes/class.ilCourseMembers.php';
		
		$course_members = new ilCourseMembers($tmp_course);

		switch($type)
		{
			case 'Admin':
				$course_members->add($tmp_user,$course_members->ROLE_ADMIN,$course_members->STATUS_NOTIFY,0);
				break;

			case 'Tutor':
				$course_members->add($tmp_user,$course_members->ROLE_TUTOR,$course_members->STATUS_NO_NOTIFY,0);
				break;

			case 'Member'
				$course_members->add($tmp_user,$course_members->ROLE_MEMBER,$course_members->STATUS_UNBLOCKED,0);
				break;
		}

		return true;
	}

	function excludeCourseMember($sid,$course_id,$user_id)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}			
		if(!is_numeric($course_id))
		{
			return $this->__raiseError('No valid course id given. Please choose an existing reference id of an ILIAS course',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';

		if(ilObject::_lookupType(ilObject::_lookupObjId($course_id)) != 'crs')
		{
			return $this->__raiseError('Invalid course id. Object with id "'. $course_id.'" is not of type "course"','Client');
		}

		if(ilObject::_lookupType($user_id) != 'usr')
		{
			return $this->__raiseError('Invalid user id. User with id "'. $user_id.' does not exist','Client');
		}

		include_once 'course/classes/class.ilCourseMembers.php';
		
		$course_members = new ilCourseMembers($tmp_course);

		if($course_members->checkLastAdmin($user_id))
		{
			return $this->__raiseError('Cannot deassign last administrator from course','Server');
		}

		$course_members->delete($user_id);

		return true;
	}

		
		
	// PRIVATE

}
?>