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

define ("IL_EXTRACT_ROLES", 1);
define ("IL_USER_IMPORT", 2);
define ("IL_VERIFY", 3);

define ("IL_FAIL_ON_CONFLICT", 1);
define ("IL_UPDATE_ON_CONFLICT", 2);
define ("IL_IGNORE_ON_CONFLICT", 3);

define ("IL_IMPORT_SUCCESS", 1);
define ("IL_IMPORT_WARNING", 2);
define ("IL_IMPORT_FAILURE", 3);

define ("IL_USER_MAPPING_LOGIN", 1);
define ("IL_USER_MAPPING_ID", 2);

require_once("classes/class.ilSaxParser.php");

/**
* User Import Parser
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @extends ilSaxParser
*/
class ilUserImportParser extends ilSaxParser
{
	var $time_limit_set = false;

	var $folder_id;
	var $roles;
	/**
	 * The Action attribute determines what to do for the current User element.
     * This variable supports the following values: "Insert","Update","Delete".
	 */
	var $action;
	/**
	 * The variable holds the protocol of the import.
     * This variable is an associative array.
	 * - Keys are login names of users or "missing login", if the login name is
	 *   missing.
	 * - Values are an array of error messages associated with the login.
	 *   If the value array is empty, then the user was imported successfully.
	 */
	var $protocol;
	/**
	 * This variable is used to collect each login that we encounter in the
	 * import data.
	 * This variable is needed to detect duplicate logins in the import data.
	 * The variable is an associative array. (I would prefer using a set, but PHP
	 * does not appear to support sets.)
	 * Keys are logins.
	 * Values are logins.
	 */
	var $logins;

	/**
	 * Conflict handling rule.
	 *
	 * Values:  IL_FAIL_ON_CONFLICT
	 *          IL_UPDATE_ON_CONFLICT
	 *          IL_IGNORE_ON_CONFLICT
	 */
	var $conflict_rule;


	/**
	 * send account notification
	 *
	 * @var boolean
	 */
	var $send_mail;

	/**
	 * This variable is used to report the error level of the validation process
	 * or the importing process.
     *
	 * Values:  IL_IMPORT_SUCCESS
	 *          IL_IMPORT_WARNING
	 *          IL_IMPORT_FAILURE
     *
     * Meaning of the values when in validation mode:
	 *          IL_IMPORT_WARNING
	 *					Some of the entity actions can not be processed
	 *                  as specified in the XML file. One or more of the
	 *                  following conflicts have occurred:
	 *                  -	An "Insert" action has been specified for a user
	 *						who is already in the database.
	 *                  -	An "Update" action has been specified for a user
	 *						who is not in the database.
	 *                  -	A "Delete" action has been specified for a user
     *					   who is not in the database.
	 *          IL_IMPORT_FAILURE
	 *					Some of the XML elements are invalid.
     *
     * Meaning of the values when in import mode:
	 *          IL_IMPORT_WARNING
	 *					Some of the entity actions have not beeen processed
	 *					as specified in the XML file.
     *
     *                  In IL_UPDATE_ON_CONFLICT mode, the following
	 *					 may have occured:
     *                  -	An "Insert" action has been replaced by a
	 *						"Update" action for a user who is already in the
	 *						database.
     *                   -	An "Update" action has been replaced by a
	 *						"Insert" action for a user who is not in the
	 *						database.
     *                  -	A "Delete" action has been replaced by a "Ignore"
	 *						action for a user who is not in the database.
	 *
     *                 In IL_IGNORE_ON_CONFLICT mode, the following
	 *					 may have occured:
     *                 -	An "Insert" action has been replaced by a
	 *						"Ignore" action for a user who is already in the
	 *						database.
     *                 -	An "Update" action has been replaced by a
	 *						"Ignore" action for a user who is not in the
	 *						database.
     *                  -	A "Delete" action has been replaced by a "Ignore"
	 *						action for a user who is not in the database.
     *
	 *          IL_IMPORT_FAILURE
	 *					The import could not be completed.
     *
	 *                       In IL_FAIL_ON_CONFLICT mode, the following
	 *						 may have occured:
	 *                       -	An "Insert" action has failed for a user who is
	 *							already in the database.
	 *                       -	An "Update" action has failed for a user who is
	 *							not in the database.
	 *                       -	A "Delete" action has failed for a user who is
	 *							not in the database.
	 */
	var $error_level;

	/**
	 * The password type of the current user.
	 */
	var $currPasswordType;
	/**
	 * The password of the current user.
	 */
	var $currPassword;
	/**
	 * The active state of the current user.
	*/
	var $currActive;
	/**
	* The count of user elements in the XML file
    	*/
	var $userCount;

	/**
	 * record user mappings for successful actions
	 *
	 * @var assoc array (key = user id, value= login)
	 */
	var $user_mapping;

	/**
	 *
	 * mapping mode is used for import process
	 *
	 * @var int
	 */
	var $mapping_mode;

	/**
	 * Cached local roles.
	 * This is used to speed up access to local roles.
	 * This is an associative array.
	 * The key is either a role_id  or  a role_id with the string "_parent" appended.
	 * The value is a role object or  the object for which the role is defined
	 */
	var $localRoleCache;

	/**
	 * Cached personal picture of the actual user
	 * This is used because the ilObjUser object has no field for the personal picture
	 */
	var $personalPicture;

	/**
	 * Cached iLinc data
	 */
	var $ilincdata;
	
	/**
	 * ILIAS skin
	 */
	var $skin;
	
	/**
	 * ILIAS style
	 */
	var $style;

	/**
	 * User assigned styles
	 */
	var $userStyles;

	/**
	 * Indicates if the skins are hidden
	 */
	var $hideSkin;

	/**
	 * Indicates if the skins are enabled
	 */
	var $disableSkin;

	/**
	 * current user id, used for updating the login
	 *
	 * @var unknown_type
	 */
	var $user_id;

	/**
	* Constructor
	*
	* @param	string		$a_xml_file		xml file
	* @param	int			$a_mode			IL_EXTRACT_ROLES | IL_USER_IMPORT | IL_VERIFY
	* @param	int			$a_conflict_rue	IL_FAIL_ON_CONFLICT | IL_UPDATE_ON_CONFLICT | IL_IGNORE_ON_CONFLICT
	*
	* @access	public
	*/
	function ilUserImportParser($a_xml_file = '', $a_mode = IL_USER_IMPORT, $a_conflict_rule = IL_FAIL_ON_CONFLICT)
	{
		global $lng, $tree, $ilias,$ilUser;
		
		$this->roles = array();
		$this->mode = $a_mode;
		$this->conflict_rule = $a_conflict_rule;
		$this->error_level = IL_IMPORT_SUCCESS;
		$this->protocol = array();
		$this->logins = array();
		$this->userCount = 0;
		$this->localRoleCache = array();
		$this->ilincdata = array();
		$this->send_mail = false;
		$this->mapping_mode = IL_USER_MAPPING_LOGIN;
		include_once "./classes/class.ilObjUser.php"; 
		$this->userStyles = ilObjUser::_getAllUserAssignedStyles();
		$settings = $ilias->getAllSettings();
		if ($settings["usr_settings_hide_skin_style"] == 1)
		{
			$this->hideSkin = TRUE;
		}
		else
		{
			$this->hideSkin = FALSE;
		}
		if ($settings["usr_settings_disable_skin_style"] == 1)
		{
			$this->disableSkin = TRUE;
		}
		else
		{
			$this->disableSkin = FALSE;
		}

		include_once("classes/class.ilAccountMail.php");
		$this->acc_mail = new ilAccountMail();

		parent::ilSaxParser($a_xml_file);
	}

	/**
	* assign users to this folder (normally the usr_folder)
	* But if called from local admin => the ref_id of the category
	* @access	public
	*/
	function setFolderId($a_folder_id)
	{
		$this->folder_id = $a_folder_id;
	}

	function getFolderId()
	{
		return $this->folder_id;
	}

	/**
	* set event handler
	* should be overwritten by inherited class
	* @access	private
	*/
	function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser,$this);
		xml_set_element_handler($a_xml_parser,'handlerBeginTag','handlerEndTag');
		xml_set_character_data_handler($a_xml_parser,'handlerCharacterData');
	}

	/**
	* start the parser
	*/
	function startParsing()
	{
		parent::startParsing();
	}

	/**
	* set import to local role assignemt
	*
	* @param	array		role assignment (key: import id; value: local role id)
	*/
	function setRoleAssignment($a_assign)
	{
		$this->role_assign = $a_assign;
	}

	/**
	* generate a tag with given name and attributes
	*
	* @param	string		"start" | "end" for starting or ending tag
	* @param	string		element/tag name
	* @param	array		array of attributes
	*/
	function buildTag ($type, $name, $attr="")
	{
		$tag = "<";

		if ($type == "end")
			$tag.= "/";

		$tag.= $name;

		if (is_array($attr))
		{
			while (list($k,$v) = each($attr))
				$tag.= " ".$k."=\"$v\"";
		}

		$tag.= ">";

		return $tag;
	}

	/**
	* handler for begin of element
	*/
	function handlerBeginTag($a_xml_parser, $a_name, $a_attribs)
	{
		switch ($this->mode)
		{
			case IL_EXTRACT_ROLES :
				$this->extractRolesBeginTag($a_xml_parser, $a_name, $a_attribs);
				break;
			case IL_USER_IMPORT :
				$this->importBeginTag($a_xml_parser, $a_name, $a_attribs);
				break;
			case IL_VERIFY :
				$this->verifyBeginTag($a_xml_parser, $a_name, $a_attribs);
				break;
		}

		$this->cdata = "";
	}

	/**
	* handler for begin of element in extract roles mode
	*/
	function extractRolesBeginTag($a_xml_parser, $a_name, $a_attribs)
	{
		switch($a_name)
		{
			case "Role":
				$this->current_role_id = $a_attribs["Id"];
				$this->current_role_type = $a_attribs["Type"];
				break;
		}
	}
	/**
	* handler for begin of element in user import mode
	*/
	function importBeginTag($a_xml_parser, $a_name, $a_attribs)
	{
		global $ilias,$lng;
		
		switch($a_name)
		{
			case "Role":
				$this->current_role_id = $a_attribs["Id"];
				$this->current_role_type = $a_attribs["Type"];
				$this->current_role_action = (is_null($a_attribs["Action"])) ? "Assign" : $a_attribs["Action"];
				break;

			case "PersonalPicture":
				$this->personalPicture = array(
					"encoding" => $a_attribs["encoding"],
					"imagetype" => $a_attribs["imagetype"],
					"content" => ""
				);
				break;

			case "Look":
				$this->skin = $a_attribs["Skin"];
				$this->style = $a_attribs["Style"];
				break;

			case "User":
				$this->skin = "";
				$this->style = "";
				$this->personalPicture = null;
				$this->userCount++;
				$this->userObj = new ilObjUser();

				// user defined fields
				$this->udf_data = array();

				// if we have an object id, store it
				$this->user_id = -1;
				if (!is_null($a_attribs["Id"]) && $this->getUserMappingMode() == IL_USER_MAPPING_ID)
				{
				    if (is_numeric($a_attribs["Id"]))
				    {
				        $this->user_id = $a_attribs["Id"];
				    }
				    elseif ($id = IlUtil::__extractId ($a_attribs["Id"], IL_INST_ID))
				    {
				        $this->user_id = $id;
				    }
				}

				$this->userObj->setPref("skin",
					$ilias->ini->readVariable("layout","skin"));
				$this->userObj->setPref("style",
					$ilias->ini->readVariable("layout","style"));
				
				$this->userObj->setLanguage($a_attribs["Language"]);
				$this->userObj->setImportId($a_attribs["Id"]);
				$this->action = (is_null($a_attribs["Action"])) ? "Insert" : $a_attribs["Action"];
				$this->currPassword = null;
				$this->currPasswordType = null;
				$this->currActive = null;

				// reset account mail object
				$this->acc_mail->reset();
				break;

			case "Password":
				$this->currPasswordType = $a_attribs["Type"];
				break;
			case "AuthMode":
				if (array_key_exists("type", $a_attribs))
				{
					switch ($a_attribs["type"])
					{
						case "default":
						case "local":
						case "ldap":
						case "radius":
						case "shibboleth":
						case "script":
						case "cas":
						case "soap":
							$this->userObj->setAuthMode($a_attribs["type"]);
							break;
						default:
							$this->logFailure($this->userObj->getLogin(),
											  sprintf($lng->txt("usrimport_xml_element_inapplicable"),"AuthMode",$a_attribs["type"]));
							break;
					}
				}
				else
				{
					$this->logFailure($this->userObj->getLogin(),
									  sprintf($lng->txt("usrimport_xml_element_inapplicable"),"AuthMode",$a_attribs["type"]));
				}
				break;
				
			case 'UserDefinedField':
				$this->tmp_udf_id = $a_attribs['Id'];
				$this->tmp_udf_name = $a_attribs['Name'];
				break;
		}
	}
	/**
	* handler for begin of element
	*/
	function verifyBeginTag($a_xml_parser, $a_name, $a_attribs)
	{
		global $lng;

		switch($a_name)
		{
			case "Role":
				if (is_null($a_attribs['Id'])
				|| $a_attribs['Id'] == "")
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_attribute_missing"),"Role","Id"));
				}
				$this->current_role_id = $a_attribs["Id"];
				$this->current_role_type = $a_attribs["Type"];
				if ($this->current_role_type != 'Global'
				&& $this->current_role_type != 'Local')
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_attribute_missing"),"Role","Type"));
				}
				$this->current_role_action = (is_null($a_attribs["Action"])) ? "Assign" : $a_attribs["Action"];
				if ($this->current_role_action != "Assign"
				&& $this->current_role_action != "Detach")
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_attribute_value_illegal"),"Role","Action",$a_attribs["Action"]));
				}
				if ($this->action == "Insert"
				&& $this->current_role_action == "Detach")
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_attribute_value_inapplicable"),"Role","Action",$this->current_role_action,$this->action));
				}
				if ($this->action == "Delete")
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_element_inapplicable"),"Role","Delete"));
				}
				break;

			case "User":
				$this->userCount++;
				$this->userObj = new ilObjUser();
				$this->userObj->setLanguage($a_attribs["Language"]);
				$this->userObj->setImportId($a_attribs["Id"]);

				// if we have an object id, store it
				$this->user_id = -1;

                if (!is_null($a_attribs["Id"]) && $this->getUserMappingMode() == IL_USER_MAPPING_ID)
				{
				    if (is_numeric($a_attribs["Id"]))
				    {
				        $this->user_id = $a_attribs["Id"];
				    }
				    elseif ($id = IlUtil::__extractId ($a_attribs["Id"], IL_INST_ID))
				    {
				        $this->user_id = $id;
				    }
				}

				$this->action = (is_null($a_attribs["Action"])) ? "Insert" : $a_attribs["Action"];
				if ($this->action != "Insert"
				&& $this->action != "Update"
				&& $this->action != "Delete")
				{
					$this->logFailure($this->userObj->getImportId(), sprintf($lng->txt("usrimport_xml_attribute_value_illegal"),"User","Action",$a_attribs["Action"]));
				}
				$this->currPassword = null;
				$this->currPasswordType = null;
				break;

			case "Password":
				$this->currPasswordType = $a_attribs["Type"];
				break;
			case "AuthMode":
				if (array_key_exists("type", $a_attribs))
				{
					switch ($a_attribs["type"])
					{
						case "default":
						case "local":
						case "ldap":
						case "radius":
						case "shibboleth":
						case "script":
						case "cas":
						case "soap":
							$this->userObj->setAuthMode($a_attribs["type"]);
							break;
						default:
							$this->logFailure($this->userObj->getImportId(), sprintf($lng->txt("usrimport_xml_attribute_value_illegal"),"AuthMode","type",$a_attribs["type"]));
							break;
					}
				}
				else
				{
					$this->logFailure($this->userObj->getImportId(), sprintf($lng->txt("usrimport_xml_attribute_value_illegal"),"AuthMode","type",""));
				}
				break;
		}
	}

	/**
	* handler for end of element
	*/
	function handlerEndTag($a_xml_parser, $a_name)
	{
		switch ($this->mode)
		{
			case IL_EXTRACT_ROLES :
				$this->extractRolesEndTag($a_xml_parser, $a_name);
				break;
			case IL_USER_IMPORT :
				$this->importEndTag($a_xml_parser, $a_name);
				break;
			case IL_VERIFY :
				$this->verifyEndTag($a_xml_parser, $a_name);
				break;
		}
	}

	/**
	* handler for end of element when in extract roles mode.
	*/
	function extractRolesEndTag($a_xml_parser, $a_name)
	{
		switch($a_name)
		{
			case "Role":
				$this->roles[$this->current_role_id]["name"] = $this->cdata;
				$this->roles[$this->current_role_id]["type"] =
					$this->current_role_type;
				break;
		}
	}

	/**
	 * Returns the parent object of the role folder object which contains the specified role.
	 */
	function getRoleObject($a_role_id)
	{
		if (array_key_exists($a_role_id, $this->localRoleCache))
		{
			return $this->localRoleCache[$a_role_id];
		}
		else
		{
			$role_obj = new ilObjRole($a_role_id, false);
			$role_obj->read();
			$this->localRoleCache[$a_role_id] = $role_obj;
			return $role_obj;
		}

	}
	/**
	 * Returns the parent object of the role folder object which contains the specified role.
	 */
	function getCourseMembersObjectForRole($a_role_id)
	{
		global $rbacreview, $rbacadmin, $tree;

		if (array_key_exists($a_role_id.'_courseMembersoObject', $this->localRoleCache))
		{
			return $this->localRoleCache[$a_role_id];
		}
		else
		{
			$rolf_refs = $rbacreview->getFoldersAssignedToRole($a_role_id, true);
			$course_ref = $tree->getParentId($rolf_refs[0]);
			$course_obj = new ilObjCourse($course_ref, true);
			$crsmembers_obj = new ilCourseMembers($course_obj);
			$this->localRoleCache[$a_role_id.'_courseMembersObject'] = $crsmembers_obj;
			return $crsmembers_obj;
		}

	}

	/**
	 * Assigns a user to a role.
         */
	function assignToRole($a_user_obj, $a_role_id)
	{
		require_once "classes/class.ilObjRole.php";
		require_once "course/classes/class.ilObjCourse.php";
		require_once "course/classes/class.ilCourseMembers.php";

		global $rbacreview, $rbacadmin, $tree;

		// If it is a course role, use the ilCourseMember object to assign
		// the user to the role
		$role_obj = $this->getRoleObject($a_role_id);
		if (substr($role_obj->getTitle(),0,6) == 'il_crs')
		{
			$crsmembers_obj = $this->getCourseMembersObjectForRole($a_role_id);

			switch (substr($role_obj->getTitle(),0,12))
			{
				case 'il_crs_admin' :
					$crs_role = $crsmembers_obj->ROLE_ADMIN;
					$crs_status = $crsmembers_obj->STATUS_NO_NOTIFY;
					break;
				case 'il_crs_tutor' :
					$crs_role = $crsmembers_obj->ROLE_TUTOR;
					$crs_status = $crsmembers_obj->STATUS_NO_NOTIFY;
					break;
				case 'il_crs_membe' :
				default :
					$crs_role = $crsmembers_obj->ROLE_MEMBER;
					$crs_status = $crsmembers_obj->STATUS_UNBLOCKED;
					break;
			}

			if ($crsmembers_obj->isAssigned($a_user_obj->getID()))
			{
				$crsmembers_obj->update($a_user_obj->getID(), $crs_role, $crs_status, false);
			}
			else
			{
				$crsmembers_obj->add($a_user_obj, $crs_role);
			}
		}
		// If it is not a course role, use RBAC to assign the user to the role
		else
		{
			$rbacadmin->assignUser($a_role_id, $a_user_obj->getId(), true);
		}
	}
	/**
	 * Detachs a user from a role.
     */
	function detachFromRole($a_user_obj, $a_role_id)
	{
		require_once "classes/class.ilObjRole.php";
		require_once "course/classes/class.ilObjCourse.php";
		require_once "course/classes/class.ilCourseMembers.php";

		global $rbacreview, $rbacadmin, $tree;

		// If it is a course role, use the ilCourseMember object to assign
		// the user to the role
		$role_obj = $this->getRoleObject($a_role_id);
		//print_r($role_obj->getTitle());
		if (substr($role_obj->getTitle(),0,6) == 'il_crs_')
		{
			$crsmembers_obj = $this->getCourseMembersObjectForRole($role_id);
			//print_r($crsmembers_obj ->getTitle());
			switch (substr($role_obj->getTitle(),0,12))
			{
				case 'il_crs_membe' :
					if ($crsmembers_obj->isMember($a_user_obj->getId()))
					{
						$crsmembers_obj->delete($a_user_obj->getId());
					}
					break;
				case 'il_crs_admin' :
					if ($crsmembers_obj->isAdmin($a_user_obj->getId()))
					{
						$crsmembers_obj->delete($a_user_obj->getId());
					}
					break;
				case 'il_crs_tutor' :
					if ($crsmembers_obj->isTutor($a_user_obj->getId()))
					{
						$crsmembers_obj->delete($a_user_obj->getId());
					}
					break;
			}
		}
		// If it is not a course role, use RBAC to assign the user to the role
		else
		{
			$rbacadmin->deassignUser($a_role_id, $a_user_obj->getId());
		}
	}

	/**
	* handler for end of element when in import user mode.
	*/
	function importEndTag($a_xml_parser, $a_name)
	{
		global $ilias, $rbacadmin, $rbacreview, $ilUser, $lng, $ilSetting;

		switch($a_name)
		{
			case "Role":
				$this->roles[$this->current_role_id]["name"] = $this->cdata;
				$this->roles[$this->current_role_id]["type"] = $this->current_role_type;
				$this->roles[$this->current_role_id]["action"] = $this->current_role_action;
				break;

			case "PersonalPicture":
				switch ($this->personalPicture["encoding"])
				{
					case "Base64":
						$this->personalPicture["content"] = base64_decode($this->cdata);
						break;
					case "UUEncode":
						// this only works with PHP >= 5
						if (version_compare(PHP_VERSION,'5','>='))
						{
							$this->personalPicture["content"] = convert_uudecode($this->cdata);
						}
						break;
				}
				break;

			case "User":
				// Fetch the user_id from the database, if we didn't have it in xml file
				// fetch as well, if we are trying to insert -> recognize duplicates!
				if ($this->user_id == -1 || $this->action=="Insert")
					$user_id = ilObjUser::getUserIdByLogin($this->userObj->getLogin());
				else
					$user_id = $this->user_id;

                //echo $user_id.":".$this->userObj->getLogin();

				// Handle conflicts
				switch ($this->conflict_rule)
				{
					case IL_FAIL_ON_CONFLICT :
						// do not change action
						break;
					case IL_UPDATE_ON_CONFLICT :
						switch ($this->action)
						{
							case "Insert" :
								if ($user_id)
								{
									$this->logWarning($this->userObj->getLogin(),sprintf($lng->txt("usrimport_action_replaced"),"Insert","Update"));
									$this->action = "Update";
								}
								break;
							case "Update" :
								if (! $user_id)
								{
									$this->logWarning($this->userObj->getLogin(),sprintf($lng->txt("usrimport_action_replaced"),"Update","Insert"));
									$this->action = "Insert";
								}
								break;
							case "Delete" :
								if (! $user_id)
								{
									$this->logWarning($this->userObj->getLogin(),sprintf($lng->txt("usrimport_action_ignored"),"Delete"));
									$this->action = "Ignore";
								}
								break;
						}
						break;
					case IL_IGNORE_ON_CONFLICT :
						switch ($this->action)
						{
							case "Insert" :
								if ($user_id)
								{
									$this->logWarning($this->userObj->getLogin(),sprintf($lng->txt("usrimport_action_ignored"),"Insert"));
									$this->action = "Ignore";
								}
								break;
							case "Update" :
								if (! $user_id)
								{
									$this->logWarning($this->userObj->getLogin(),sprintf($lng->txt("usrimport_action_ignored"),"Update"));
									$this->action = "Ignore";
								}
								break;
							case "Delete" :
								if (! $user_id)
								{
									$this->logWarning($this->userObj->getLogin(),sprintf($lng->txt("usrimport_action_ignored"),"Delete"));
									$this->action = "Ignore";
								}
								break;
						}
						break;
				}
				
				// check external account conflict (if external account is already used)
				// note: we cannot apply conflict rules in the same manner as to logins here
				// so we ignore records with already existing external accounts.
				$am = ($this->userObj->getAuthMode() == "default" || $this->userObj->getAuthMode() == "")
					? ilAuthUtils::_getAuthModeName($ilSetting->get('auth_mode'))
					: $this->userObj->getAuthMode();
				$elogin = ($this->userObj->getExternalAccount() == "")
					? ""
					: ilObjUser::_checkExternalAuthAccount($am, $this->userObj->getExternalAccount());
				switch ($this->action)
				{
					case "Insert" :
						if ($elogin != "")
						{
							$this->logWarning($this->userObj->getLogin(),
								$lng->txt("usrimport_no_insert_ext_account_exists")." (".$this->userObj->getExternalAccount().")");
							$this->action = "Ignore";
						}
						break;
						
					case "Update" :
						if ($elogin != "" && $elogin != $this->userObj->getLogin())
						{
							$this->logWarning($this->userObj->getLogin(),
								$lng->txt("usrimport_no_update_ext_account_exists")." (".$this->userObj->getExternalAccount().")");
							$this->action = "Ignore";
						}
						break;
				}

				// Perform the action
				switch ($this->action)
				{
					case "Insert" :
						if ($user_id)
						{
							$this->logFailure($this->userObj->getLogin(),$lng->txt("usrimport_cant_insert"));
						}
						else
						{

						    if (!strlen($this->currPassword)==0)
						      switch ($this->currPasswordType)
							  {
								case "ILIAS2":
									$this->userObj->setPasswd($this->currPassword, IL_PASSWD_CRYPT);
									break;

								case "ILIAS3":
									$this->userObj->setPasswd($this->currPassword, IL_PASSWD_MD5);
									break;

								case "PLAIN":
									$this->userObj->setPasswd($this->currPassword, IL_PASSWD_PLAIN);
									$this->acc_mail->setUserPassword($this->currPassword);
									break;

							  }
							else
							{
							    // this does the trick for empty passwords
							    // since a MD5 string has always 32 characters,
							    // no hashed password combination will ever equal to
							    // an empty string
 							    $this->userObj->setPasswd("", IL_PASSWD_MD5);

							}

							$this->userObj->setTitle($this->userObj->getFullname());
							$this->userObj->setDescription($this->userObj->getEmail());
							$this->userObj->setTimeLimitOwner($this->getFolderId());

							// default time limit settings
							if(!$this->time_limit_set)
							{
								$this->userObj->setTimeLimitUnlimited(1);
								$this->userObj->setTimeLimitMessage(0);
								$this->userObj->setApproveDate(date("Y-m-d H:i:s"));
							}


							$this->userObj->setActive($this->currActive == 'true' || is_null($this->currActive),6);
							
							// Finally before saving new user.
							// Check if profile is incomplete
							$this->userObj->setProfileIncomplete($this->checkProfileIncomplete($this->userObj));
							$this->userObj->create();

							//insert user data in table user_data
							$this->userObj->saveAsNew(false);

							
							// Set default prefs
							$this->userObj->setPref('hits_per_page',$ilSetting->get('hits_per_page',30));
							$this->userObj->setPref('show_users_online',$ilSetting->get('show_users_online','y'));
							// save user preferences (skin and style)
							
							$this->userObj->writePrefs();

							if (is_array($this->personalPicture))
							{
								if (strlen($this->personalPicture["content"]))
								{
									$extension = "jpg";
									if (preg_match("/.*(png|jpg|gif|jpeg)$/", $this->personalPicture["imagetype"], $matches))
									{
										$extension = $matches[1];
									}
									$tmp_name = $this->saveTempImage($this->personalPicture["content"], ".$extension");
									if (strlen($tmp_name))
									{
										ilObjUser::_uploadPersonalPicture($tmp_name, $this->userObj->getId());
										unlink($tmp_name);
									}
								}
							}

							if ($this->ilincdata["id"]) {
							    include_once './ilinc/classes.ilObjiLincUser.php';
                                $ilinc_user = new ilObjiLincUser($this->userObj);
                                $ilinc_user->setVar("id", $this->ilincdata["id"]);
                                $ilinc_user->setVar("login", $this->ilincdata["login"]);
                                $ilinc_user->setVar("passwd", $this->ilincdata["password"]);
                                $ilinc_user->update();
							}

							//set role entries
							foreach($this->roles as $role_id => $role)
							{
								if ($this->role_assign[$role_id])
								{
									$this->assignToRole($this->userObj, $this->role_assign[$role_id]);
								}
							}

							if(count($this->udf_data))
							{
								include_once 'classes/class.ilUserDefinedData.php';
								$udd = new ilUserDefinedData($this->userObj->getId());
								foreach($this->udf_data as $field => $value)
								{
									$udd->set($field,$value);
								}
								$udd->update();
							}

							$this->sendAccountMail();
							$this->logSuccess($this->userObj->getLogin(),$this->userObj->getId(), "Insert");
						}
						break;

					case "Update" :
						if (! $user_id)
						{
							$this->logFailure($this->userObj->getLogin(),$lng->txt("usrimport_cant_update"));
						}
						else
						{
							$updateUser = new ilObjUser($user_id);
							$updateUser->read();
							$updateUser->readPrefs();
							if ($this->currPassword != null)
							{
								switch ($this->currPasswordType)
								{
									case "ILIAS2":
										$updateUser->setPasswd($this->currPassword, IL_PASSWD_CRYPT);
										break;

									case "ILIAS3":
										$updateUser->setPasswd($this->currPassword, IL_PASSWD_MD5);
										break;

									case "PLAIN":
										$updateUser->setPasswd($this->currPassword, IL_PASSWD_PLAIN);
										$this->acc_mail->setUserPassword($this->currPassword);
										break;
								}
							}
							if (! is_null($this->userObj->getFirstname())) $updateUser->setFirstname($this->userObj->getFirstname());
							if (! is_null($this->userObj->getLastname())) $updateUser->setLastname($this->userObj->getLastname());
							if (! is_null($this->userObj->getUTitle())) $updateUser->setUTitle($this->userObj->getUTitle());
							if (! is_null($this->userObj->getGender())) $updateUser->setGender($this->userObj->getGender());
							if (! is_null($this->userObj->getEmail())) $updateUser->setEmail($this->userObj->getEmail());
							if (! is_null($this->userObj->getInstitution())) $updateUser->setInstitution($this->userObj->getInstitution());
							if (! is_null($this->userObj->getStreet())) $updateUser->setStreet($this->userObj->getStreet());
							if (! is_null($this->userObj->getCity())) $updateUser->setCity($this->userObj->getCity());
							if (! is_null($this->userObj->getZipCode())) $updateUser->setZipCode($this->userObj->getZipCode());
							if (! is_null($this->userObj->getCountry())) $updateUser->setCountry($this->userObj->getCountry());
							if (! is_null($this->userObj->getPhoneOffice())) $updateUser->setPhoneOffice($this->userObj->getPhoneOffice());
							if (! is_null($this->userObj->getPhoneHome())) $updateUser->setPhoneHome($this->userObj->getPhoneHome());
							if (! is_null($this->userObj->getPhoneMobile())) $updateUser->setPhoneMobile($this->userObj->getPhoneMobile());
							if (! is_null($this->userObj->getFax())) $updateUser->setFax($this->userObj->getFax());
							if (! is_null($this->userObj->getHobby())) $updateUser->setHobby($this->userObj->getHobby());
							if (! is_null($this->userObj->getComment())) $updateUser->setComment($this->userObj->getComment());
							if (! is_null($this->userObj->getDepartment())) $updateUser->setDepartment($this->userObj->getDepartment());
							if (! is_null($this->userObj->getMatriculation())) $updateUser->setMatriculation($this->userObj->getMatriculation());
							if (! is_null($this->currActive)) $updateUser->setActive($this->currActive == "true", is_object($ilUser) ? $ilUser->getId() : 0);
							if (! is_null($this->userObj->getClientIP())) $updateUser->setClientIP($this->userObj->getClientIP());
							if (! is_null($this->userObj->getTimeLimitOwner())) $updateUser->setTimeLimitOwner($this->userObj->getTimeLimitOwner());
							if (! is_null($this->userObj->getTimeLimitUnlimited())) $updateUser->setTimeLimitUnlimited($this->userObj->getTimeLimitUnlimited());
							if (! is_null($this->userObj->getTimeLimitFrom())) $updateUser->setTimeLimitFrom($this->userObj->getTimeLimitFrom());
							if (! is_null($this->userObj->getTimeLimitUntil())) $updateUser->setTimeLimitUntil($this->userObj->getTimeLimitUntil());
							if (! is_null($this->userObj->getTimeLimitMessage())) $updateUser->setTimeLimitMessage($this->userObj->getTimeLimitMessage());
							if (! is_null($this->userObj->getApproveDate())) $updateUser->setApproveDate($this->userObj->getApproveDate());
							if (! is_null($this->userObj->getLanguage())) $updateUser->setLanguage($this->userObj->getLanguage());
							if (! is_null($this->userObj->getExternalAccount())) $updateUser->setExternalAccount($this->userObj->getExternalAccount());
							if (! is_null($this->userObj->getAuthMode())) $updateUser->setAuthMode($this->userObj->getAuthMode());

							// save user preferences (skin and style)
							$updateUser->setPref("skin", $this->userObj->getPref("skin"));
							$updateUser->setPref("style", $this->userObj->getPref("style"));
							$updateUser->writePrefs();
							
							$updateUser->setProfileIncomplete($this->checkProfileIncomplete($updateUser));
					
							$updateUser->update();

							if ($this->ilincdata["id"]) {
							    include_once './ilinc/classes.ilObjiLincUser.php';
                                $ilinc_user = new ilObjiLincUser($updateUser);
                                $ilinc_user->setVar("id", $this->ilincdata["id"]);
                                $ilinc_user->setVar("login", $this->ilincdata["login"]);
                                $ilinc_user->setVar("passwd", $this->ilincdata["password"]);
                                $ilinc_user->update();
							}

							if(count($this->udf_data))
							{
								include_once 'classes/class.ilUserDefinedData.php';
								$udd = new ilUserDefinedData($updateUser->getId());
								foreach($this->udf_data as $field => $value)
								{
									$udd->set($field,$value);
								}
								$udd->update();
							}

							// update login
							if (!is_null($this->userObj->getLogin()) && $this->user_id != -1)
								$updateUser->updateLogin($this->userObj->getLogin());

						    // if language has changed

							if (is_array($this->personalPicture))
							{
								if (strlen($this->personalPicture["content"]))
								{
									$extension = "jpg";
									if (preg_match("/.*(png|jpg|gif|jpeg)$/", $this->personalPicture["imagetype"], $matches))
									{
										$extension = $matches[1];
									}
									$tmp_name = $this->saveTempImage($this->personalPicture["content"], ".$extension");
									if (strlen($tmp_name))
									{
										ilObjUser::_uploadPersonalPicture($tmp_name, $this->userObj->getId());
										unlink($tmp_name);
									}
								}
							}


							//update role entries
							//-------------------
							foreach ($this->roles as $role_id => $role)
							{
								if ($this->role_assign[$role_id])
								{
									switch ($role["action"])
									{
										case "Assign" :
											$this->assignToRole($updateUser, $this->role_assign[$role_id]);
											break;
										case "Detach" :
											$this->detachFromRole($updateUser, $this->role_assign[$role_id]);
											break;
									}
								}
							}
    						$this->logSuccess($updateUser->getLogin(), $user_id, "Update");
						}
						break;
					case "Delete" :
						if (! $user_id)
						{
							$this->logFailure($this->userObj->getLogin(),$lng->txt("usrimport_cant_delete"));
						}
						else
						{
							$deleteUser = new ilObjUser($user_id);
							$deleteUser->delete();

							$this->logSuccess($this->userObj->getLogin(),$user_id, "Delete");
						}
						break;
				}

				// init role array for next user
				$this->roles = array();
				break;

			case "Login":
				$this->userObj->setLogin($this->cdata);
				break;

			case "Password":
				$this->currPassword = $this->cdata;
				break;

			case "Firstname":
				$this->userObj->setFirstname($this->cdata);
				break;

			case "Lastname":
				$this->userObj->setLastname($this->cdata);
				$this->userObj->setFullname();
				break;

			case "Title":
				$this->userObj->setUTitle($this->cdata);
				break;

			case "Gender":
				$this->userObj->setGender($this->cdata);
				break;

			case "Email":
				$this->userObj->setEmail($this->cdata);
				break;

			case "Institution":
				$this->userObj->setInstitution($this->cdata);
				break;

			case "Street":
				$this->userObj->setStreet($this->cdata);
				break;

			case "City":
				$this->userObj->setCity($this->cdata);
				break;

			case "PostalCode":
				$this->userObj->setZipCode($this->cdata);
				break;

			case "Country":
				$this->userObj->setCountry($this->cdata);
				break;

			case "PhoneOffice":
				$this->userObj->setPhoneOffice($this->cdata);
				break;

			case "PhoneHome":
				$this->userObj->setPhoneHome($this->cdata);
				break;

			case "PhoneMobile":
				$this->userObj->setPhoneMobile($this->cdata);
				break;

			case "Fax":
				$this->userObj->setFax($this->cdata);
				break;

			case "Hobby":
				$this->userObj->setHobby($this->cdata);
				break;

			case "Comment":
				$this->userObj->setComment($this->cdata);
				break;

			case "Department":
				$this->userObj->setDepartment($this->cdata);
				break;

			case "Matriculation":
				$this->userObj->setMatriculation($this->cdata);
				break;

			case "Active":
				$this->currActive = $this->cdata;
				break;

			case "ClientIP":
				$this->userObj->setClientIP($this->cdata);
				break;

			case "TimeLimitOwner":
				$this->userObj->setTimeLimitOwner($this->cdata);
				break;

			case "TimeLimitUnlimited":
				$this->time_limit_set = true;
				$this->userObj->setTimeLimitUnlimited($this->cdata);
				break;

			case "TimeLimitFrom":
				$this->userObj->setTimeLimitFrom($this->cdata);
				break;

			case "TimeLimitUntil":
				$this->userObj->setTimeLimitUntil($this->cdata);
				break;

			case "TimeLimitMessage":
				$this->userObj->setTimeLimitMessage($this->cdata);
				break;

			case "ApproveDate":
				$this->userObj->setApproveDate($this->cdata);
				break;

			case "iLincID":
				$this->ilincdata["id"] = $this->cdata;
				break;

			case "iLincLogin":
				$this->$ilincdata["login"] = $this->cdata;
				break;

			case "iLincPasswd":
				$this->$ilincdata["password"] = $this->cdata;
				//$this->userObj->setiLincData($this->ilincdata);
				break;
				
			case "ExternalAccount":
				$this->userObj->setExternalAccount($this->cdata);
				break;

			case "Look":
				if (!$this->hideSkin)
				{
					// TODO: what to do with disabled skins? is it possible to change the skin via import?
					if ((strlen($this->skin) > 0) && (strlen($this->style) > 0))
					{
						if (is_array($this->userStyles))
						{
							if (in_array($this->skin . ":" . $this->style, $this->userStyles))
							{
								$this->userObj->setPref("skin", $this->skin);
								$this->userObj->setPref("style", $this->style);
							}
						}
					}
				}
				break;

			case 'UserDefinedField':
				include_once 'classes/class.ilUserDefinedFields.php';
				$udf = ilUserDefinedFields::_getInstance();
				if($field_id = $udf->fetchFieldIdFromImportId($this->tmp_udf_id))
				{
					$this->udf_data[$field_id] = $this->cdata;
				}
				elseif($field_id = $udf->fetchFieldIdFromName($this->tmp_udf_name))
				{
					$this->udf_data[$field_id] = $this->cdata;
				}
				break;
		}
	}

	/**
	* Saves binary image data to a temporary image file and returns
	* the name of the image file on success.
	*/
	function saveTempImage($image_data, $filename)
	{
		$tempname = ilUtil::ilTempnam() . $filename;
		$fh = fopen($tempname, "wb");
		if ($fh == false)
		{
			return "";
		}
		$imagefile = fwrite($fh, $image_data);
		fclose($fh);
		return $tempname;
	}

	/**
	* handler for end of element when in verify mode.
	*/
	function verifyEndTag($a_xml_parser, $a_name)
	{
		global $lng,$ilAccess,$ilSetting;

		switch($a_name)
		{
			case "Role":
				$this->roles[$this->current_role_id]["name"] = $this->cdata;
				$this->roles[$this->current_role_id]["type"] = $this->current_role_type;
				$this->roles[$this->current_role_id]["action"] = $this->current_role_action;
				break;

			case "User":
				if ($this->user_id != -1 && $this->action == "Update")
				    $user_exists = !is_null(ilObjUser::_lookupLogin($this->user_id));
			    else
			        $user_exists = ilObjUser::getUserIdByLogin($this->userObj->getLogin()) != 0;

				if (is_null($this->userObj->getLogin()))
				{
					$this->logFailure("---",sprintf($lng->txt("usrimport_xml_element_for_action_required"),"Login", "Insert"));
				}

				switch ($this->action)
				{
					case "Insert" :
						if ($user_exists)
						{
							$this->logWarning($this->userObj->getLogin(),$lng->txt("usrimport_cant_insert"));
						}
						if (is_null($this->userObj->getGender()))
						{
							$this->logFailure($this->userObj->getLogin(),sprintf($lng->txt("usrimport_xml_element_for_action_required"),"Gender", "Insert"));
						}
						if (is_null($this->userObj->getFirstname()))
						{
							$this->logFailure($this->userObj->getLogin(),sprintf($lng->txt("usrimport_xml_element_for_action_required"),"Firstname", "Insert"));
						}
						if (is_null($this->userObj->getLastname()))
						{
							$this->logFailure($this->userObj->getLogin(),sprintf($lng->txt("usrimport_xml_element_for_action_required"),"Lastname", "Insert"));
						}
						if (count($this->roles) == 0)
						{
							$this->logFailure($this->userObj->getLogin(),sprintf($lng->txt("usrimport_xml_element_for_action_required"),"Role", "Insert"));
						}
						else
						{
							$has_global_role = false;
							foreach ($this->roles as $role)
							{
								if ($role['type'] == 'Global')
								{
									$has_global_role = true;
									break;
								}
							}
							if (! $has_global_role)
							{
								$this->logFailure($this->userObj->getLogin(),sprintf($lng->txt("usrimport_global_role_for_action_required"),"Insert"));
							}
						}
						break;
					case "Update" :
						if (! $user_exists)
						{
							$this->logWarning($this->userObj->getLogin(),$lng->txt("usrimport_cant_update"));
						} elseif ($this->user_id != -1 && !is_null($this->userObj->getLogin()))
							// check if someone owns the new login name!
                        {
                            $someonesId = ilObjUser::_lookupId($this->userObj->getLogin());

                            if (is_numeric($someonesId ) && $someonesId != $this->user_id) {
               			          $this->logFailure($this->userObj->getLogin(), $lng->txt("usrimport_login_is_not_unique"));
                            }
                        }
						break;
					case "Delete" :
						if (! $user_exists)
						{
							$this->logWarning($this->userObj->getLogin(),$lng->txt("usrimport_cant_delete"));
						}
						break;
				}

				// init role array for next user
				$this->roles = array();
				break;

			case "Login":
				if (array_key_exists($this->cdata, $this->logins))
				{
					$this->logFailure($this->cdata, $lng->txt("usrimport_login_is_not_unique"));
				}
				else
				{
					$this->logins[$this->cdata] = $this->cdata;
				}
    			$this->userObj->setLogin($this->cdata);
				break;

			case "Password":
				switch ($this->currPasswordType)
				{
					case "ILIAS2":
						$this->userObj->setPasswd($this->cdata, IL_PASSWD_CRYPT);
						break;

					case "ILIAS3":
						$this->userObj->setPasswd($this->cdata, IL_PASSWD_MD5);
						break;

					case "PLAIN":
						$this->userObj->setPasswd($this->cdata, IL_PASSWD_PLAIN);
						$this->acc_mail->setUserPassword($this->currPassword);
						break;

					default :
						$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_attribute_value_illegal"),"Type","Password",$this->currPasswordType));
						break;
				}
				break;

			case "Firstname":
				$this->userObj->setFirstname($this->cdata);
				break;

			case "Lastname":
				$this->userObj->setLastname($this->cdata);
				$this->userObj->setFullname();
				break;

			case "Title":
				$this->userObj->setUTitle($this->cdata);
				break;

			case "Gender":
				if ($this->cdata != "m"
				&& $this->cdata != "f")
				{
					$this->logFailure($this->userObj->getLogin(),
									  sprintf($lng->txt("usrimport_xml_attribute_value_illegal"),"Gender",$this->cdata));
				}
				$this->userObj->setGender($this->cdata);
				break;

			case "Email":
				$this->userObj->setEmail($this->cdata);
				break;

			case "Institution":
				$this->userObj->setInstitution($this->cdata);
				break;

			case "Street":
				$this->userObj->setStreet($this->cdata);
				break;

			case "City":
				$this->userObj->setCity($this->cdata);
				break;

			case "PostalCode":
				$this->userObj->setZipCode($this->cdata);
				break;

			case "Country":
				$this->userObj->setCountry($this->cdata);
				break;

			case "PhoneOffice":
				$this->userObj->setPhoneOffice($this->cdata);
				break;

			case "PhoneHome":
				$this->userObj->setPhoneHome($this->cdata);
				break;

			case "PhoneMobile":
				$this->userObj->setPhoneMobile($this->cdata);
				break;

			case "Fax":
				$this->userObj->setFax($this->cdata);
				break;

			case "Hobby":
				$this->userObj->setHobby($this->cdata);
				break;

			case "Comment":
				$this->userObj->setComment($this->cdata);
				break;

			case "Department":
				$this->userObj->setDepartment($this->cdata);
				break;

			case "Matriculation":
				$this->userObj->setMatriculation($this->cdata);
				break;
				
			case "ExternalAccount":
//echo "-".$this->userObj->getAuthMode()."-".$this->userObj->getLogin()."-";
				$am = ($this->userObj->getAuthMode() == "default" || $this->userObj->getAuthMode() == "")
					? ilAuthUtils::_getAuthModeName($ilSetting->get('auth_mode'))
					: $this->userObj->getAuthMode();
				$elogin = (trim($this->cdata) == "")
					? ""
					: ilObjUser::_checkExternalAuthAccount($am, trim($this->cdata));
				switch ($this->action)
				{
					case "Insert" :
						if ($elogin != "")
						{
							$this->logWarning($this->userObj->getLogin(),
								$lng->txt("usrimport_no_insert_ext_account_exists")." (".$this->cdata.")");
						}
						break;
						
					case "Update" :
						if ($elogin != "" && $elogin != $this->userObj->getLogin())
						{
							$this->logWarning($this->userObj->getLogin(),
								$lng->txt("usrimport_no_update_ext_account_exists")." (".$this->cdata.")");
						}
						break;
				}
				$this->userObj->setExternalAccount(trim($this->cdata));
				break;
				
			case "Active":
				if ($this->cdata != "true"
				&& $this->cdata != "false")
				{
					$this->logFailure($this->userObj->getLogin(),
									  sprintf($lng->txt("usrimport_xml_element_content_illegal"),"Active",$this->cdata));
				}
				$this->currActive = $this->cdata;
				break;
			case "TimeLimitOwner":
				if (!preg_match("/\d+/", $this->cdata))
				{
					$this->logFailure($this->userObj->getLogin(),
									  sprintf($lng->txt("usrimport_xml_element_content_illegal"),"TimeLimitOwner",$this->cdata));
				} else
				if(!$ilAccess->checkAccess('cat_administrate_users','',$this->cdata))
				{
					$this->logFailure($this->userObj->getLogin(),
									  sprintf($lng->txt("usrimport_xml_element_content_illegal"),"TimeLimitOwner",$this->cdata));
				}

				$this->userObj->setTimeLimitOwner($this->cdata);
				break;
			case "TimeLimitUnlimited":
				switch (strtolower($this->cdata))
				{
					case "true":
					case "1":
						$this->userObj->setTimeLimitUnlimited(1);
						break;
					case "false":
					case "0":
						$this->userObj->setTimeLimitUnlimited(0);
						break;
					default:
						$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_element_content_illegal"),"TimeLimitUnlimited",$this->cdata));
						break;
				}
				break;
			case "TimeLimitFrom":
				if (!preg_match("/\d+/", $this->cdata))
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_element_content_illegal"),"TimeLimitFrom",$this->cdata));
				}
				$this->userObj->setTimeLimitFrom($this->cdata);
				break;
			case "TimeLimitUntil":
				if (!preg_match("/\d+/", $this->cdata))
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_element_content_illegal"),"TimeLimitUntil",$this->cdata));
				}
				$this->userObj->setTimeLimitUntil($this->cdata);
				break;
			case "TimeLimitMessage":
				switch (strtolower($this->cdata))
				{
					case "1":
						$this->userObj->setTimeLimitMessage(1);
						break;
					case "0":
						$this->userObj->setTimeLimitMessage(0);
						break;
					default:
						$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_element_content_illegal"),"TimeLimitMessage",$this->cdata));
						break;
				}
				break;
			case "ApproveDate":
				if (!preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $this->cdata))
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_element_content_illegal"),"ApproveDate",$this->cdata));
				}
				$this->userObj->setTimeLimitUntil($this->cdata);
				break;
			case "iLincID":
				if (!preg_match("/\d+/", $this->cdata))
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_element_content_illegal"),"iLincID",$this->cdata));
				}
				break;
			case "iLincUser":
				if (!preg_match("/\w+/", $this->cdata))
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_element_content_illegal"),"iLincUser",$this->cdata));
				}
				break;
			case "iLincPasswd":
				if (!preg_match("/\w+/", $this->cdata))
				{
					$this->logFailure($this->userObj->getLogin(), sprintf($lng->txt("usrimport_xml_element_content_illegal"),"iLincPasswd",$this->cdata));
				}
				break;
		}
	}

	/**
	* handler for character data
	*/
	function handlerCharacterData($a_xml_parser, $a_data)
	{
		// DELETE WHITESPACES AND NEWLINES OF CHARACTER DATA
		// TODO: Mit Alex klären, ob das noch benötigt wird $a_data = preg_replace("/\n/","",$a_data);
		// TODO: Mit Alex klären, ob das noch benötigt wird $a_data = preg_replace("/\t+/","",$a_data);
		if($a_data != "\n") $a_data = preg_replace("/\t+/"," ",$a_data);

		if(strlen($a_data) > 0)
		{
			$this->cdata .= $a_data;
		}
	}

	/**
	* get collected roles
	*/
	function getCollectedRoles()
	{
		return $this->roles;
	}
	/**
	* get count of User elements
	*/
	function getUserCount()
	{
		return $this->userCount;
	}

	/**
     * Writes a warning log message to the protocol.
	 *
	* @param	string		login
	* @param	string		message
	 */
	function logWarning($aLogin, $aMessage)
	{
		if (! array_key_exists($aLogin, $this->protocol))
		{
			$this->protocol[$aLogin] = array();
		}
		if ($aMessage)
		{
			$this->protocol[$aLogin][] = $aMessage;
		}
		if ($this->error_level == IL_IMPORT_SUCCESS)
		{
			$this->error_level = IL_IMPORT_WARNING;
		}
	}
	/**
     * Writes a failure log message to the protocol.
	 *
	* @param	string		login
	* @param	string		message
	 */
	function logFailure($aLogin, $aMessage)
	{
		if (! array_key_exists($aLogin, $this->protocol))
		{
			$this->protocol[$aLogin] = array();
		}
		if ($aMessage)
		{
			$this->protocol[$aLogin][] = $aMessage;
		}
		$this->error_level = IL_IMPORT_FAILURE;
	}

	/**
     * Writes a success log message to the protocol.
	 *
	 * @param	string		login
	 * @param	string		userid
	 * @param   string      action
	 */
	function logSuccess($aLogin, $userid, $action)
	{
	    $this->user_mapping[$userid] = array("login" => $aLogin, "action" => $action, "message" => "successful");
	}


	/**
     * Returns the protocol.
	 *
	 * The protocol is an associative array.
	 * Keys are login names.
	 * Values are non-associative arrays. Each array element contains an error
	 * message.
	 */
	function getProtocol()
	{
		return $this->protocol;
	}
	/**
     * Returns the protocol as a HTML table.
	 */
	function getProtocolAsHTML($a_log_title)
	{
		global $lng;

		$block = new ilTemplate("tpl.usr_import_log_block.html", true, true);
		$block->setVariable("TXT_LOG_TITLE", $a_log_title);
		$block->setVariable("TXT_MESSAGE_ID", $lng->txt("login"));
		$block->setVariable("TXT_MESSAGE_TEXT", $lng->txt("message"));
		foreach ($this->getProtocol() as $login => $messages)
		{
			$block->setCurrentBlock("log_row");
			$reason = "";
			foreach ($messages as $message)
			{
				if ($reason == "")
				{
					$reason = $message;
				}
				else
				{
					$reason = $reason."<br>".$message;
				}
			}
			$block->setVariable("MESSAGE_ID", $login);
			$block->setVariable("MESSAGE_TEXT", $reason);
			$block->parseCurrentBlock();
		}
		return $block->get();
	}

	/**
     * Returns true, if the import was successful.
	 */
	function isSuccess()
	{
		return $this->error_level == IL_IMPORT_SUCCESS;
	}

	/**
     * Returns the error level.
	 * @return IL_IMPORT_SUCCESS | IL_IMPORT_WARNING | IL_IMPORT_FAILURE
	 */
	function getErrorLevel()
	{
		return $this->error_level;
	}

	/**
	 * returns a map user_id <=> login
	 *
	 * @return assoc array, with user_id as key and login as value
	 */
	function getUserMapping() {
	    return $this->user_mapping;
	}

	/**
	* send account mail
	*/
	function sendAccountMail()
	{
//var_dump($_POST["send_mail"]);
		if ($_POST["send_mail"] != "" ||
		   ($this->isSendMail() && $this->userObj->getEmail() != "")
		   )
		{
			$this->acc_mail->setUser($this->userObj);
			$this->acc_mail->send();
		}
	}

	/**
	 * write access to property send mail
	 *
	 * @param mixed $value
	 */
	function setSendMail ($value) {
	    $this->send_mail = $value ? true: false;
	}

	/**
	 * read access to property send mail
	 *
	 * @return boolean
	 */
	function isSendMail () {
	    return $this->send_mail;
	}

	/**
	 * write access to user mapping mode
	 *
	 * @param int $value must be one of IL_USER_MAPPING_ID or IL_USER_MAPPING_LOGIN, die otherwise
	 */
	function setUserMappingMode($value)
	{
	    if ($value == IL_USER_MAPPING_ID || $value == IL_USER_MAPPING_LOGIN)
	       $this->mapping_mode = $value;
	    else die ("wrong argument using methode setUserMappingMethod in ".__FILE__);
	}

	/**
	 * read access to user mapping mode
	 *
	 * @return int one of IL_USER_MAPPING_ID or IL_USER_MAPPING_LOGIN
	 */
	function getUserMappingMode()
	{
	    return $this->mapping_mode;
	}
	
	/**
	 * read required fields
	 *
	 * @access private
	 * 
	 */
	private function readRequiredFields()
	{
		global $ilSetting;
		
	 	if(is_array($this->required_fields))
	 	{
	 		return $this->required_fields;
	 	}
	 	foreach($ilSetting->getAll() as $field => $value)
	 	{
	 		if(substr($field,0,8) == 'require_' and $value == 1)
	 		{
	 			$this->required_fields[] = substr($field,8);
	 		}
	 	}
	 	return $this->required_fields ? $this->required_fields : array();
	}
	
	/**
	 * Check if profile is incomplete
	 * Will set the usr_data field profile_incomplete if any required field is missing 
	 *
	 *
	 * @access private
	 * 
	 */
	private function checkProfileIncomplete($user_obj)
	{
	 	$this->readRequiredFields();
	 	
	 	foreach($this->required_fields as $field)
	 	{
			
	 		switch($field)
	 		{
	 			case 'login':
	 				if(!strlen($user_obj->getLogin()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'gender':
	 				if(!strlen($user_obj->getGender()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'firstname':
	 				if(!strlen($user_obj->getFirstname()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'lastname':
	 				if(!strlen($user_obj->getLastname()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'email':
	 				if(!strlen($user_obj->getEmail()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'title':
	 				if(!strlen($user_obj->getUTitle()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'institution':
	 				if(!strlen($user_obj->getInstitution()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'department':
	 				if(!strlen($user_obj->getDepartment()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'street':
	 				if(!strlen($user_obj->getStreet))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'zipcode':
	 				if(!strlen($user_obj->getZipcode()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'city':
	 				if(!strlen($user_obj->getCity))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'country':
	 				if(!strlen($user_obj->getCountry))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'phone_office':
	 				if(!strlen($user_obj->getPhoneOffice()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'phone_mobile':
	 				if(!strlen($user_obj->getPhoneMobile()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'phone_home':
	 				if(!strlen($user_obj->getPhoneHome()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'fax':
	 				if(!strlen($user_obj->getFax()))
	 				{
	 					return true;
	 				}
	 				break;
	 			case 'hobby':
	 				if(!strlen($user_obj->getHobby()))
	 				{
	 					return true;
	 				}
	 				break;
	 			default:
	 				continue;
	 		}
	 	}
	 	return false;
	}

}
?>
