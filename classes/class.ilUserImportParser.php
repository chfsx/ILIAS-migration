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

define ("IL_EXTRACT_ROLES", 1);
define ("IL_USER_IMPORT", 2);
define ("IL_VERIFY", 3);

define ("IL_FAIL_ON_CONFLICT", 1);
define ("IL_UPDATE_ON_CONFLICT", 2);
define ("IL_IGNORE_ON_CONFLICT", 3);

define ("IL_IMPORT_SUCCESS", 1);
define ("IL_IMPORT_WARNING", 2);
define ("IL_IMPORT_FAILURE", 3);

require_once("classes/class.ilSaxParser.php");

/**
* User Import Parser
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @extends ilSaxParser
* @package core
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
	* Constructor
	*
	* @param	string		$a_xml_file		xml file
	* @param	int			$a_mode			IL_EXTRACT_ROLES | IL_USER_IMPORT | IL_VERIFY
	* @param	int			$a_conflict_rue	IL_FAIL_ON_CONFLICT | IL_UPDATE_ON_CONFLICT | IL_IGNORE_ON_CONFLICT
	*
	* @access	public
	*/
	function ilUserImportParser($a_xml_file, $a_mode = IL_USER_IMPORT, $a_conflict_rule = IL_FAIL_ON_CONFLICT)
	{
		global $lng, $tree;

		$this->roles = array();
		$this->mode = $a_mode;
		$this->conflict_rule = $a_conflict_rule;
		$this->error_level = IL_IMPORT_SUCCESS;
		$this->protocol = array();
		$this->logins = array();
		$this->userCount = 0;
		$this->localRoleCache = array();
		$this->ilincdata = array();
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

			case "User":
				$this->personalPicture = null;
				$this->userCount++;
				$this->userObj = new ilObjUser();
				// if we have an object id, store it
				$this->user_id = (is_null($a_attribs["ObjId"]))? -1 : $a_attribs["ObjId"];
				$this->userObj->setLanguage($a_attribs["Language"]);
				$this->userObj->setImportId($a_attribs["Id"]);
				$this->action = (is_null($a_attribs["Action"])) ? "Insert" : $a_attribs["Action"];
				$this->currPassword = null;
				$this->currPasswordType = null;
				$this->currActive = null;
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
		global $ilias, $rbacadmin, $rbacreview, $ilUser, $lng;

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
				if ($this->user_id == -1)
					$user_id = ilObjUser::getUserIdByLogin($this->userObj->getLogin());
				else
					$user_id = $this->user_id;


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
							switch ($this->currPasswordType)
							{
								case "ILIAS2":
									$this->userObj->setPasswd($this->currPassword, IL_PASSWD_CRYPT);
									break;

								case "ILIAS3":
									$this->userObj->setPasswd($this->currPassword, IL_PASSWD_MD5);
									break;
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
							$ilincdata = $ilUser->getiLincData();
							$this->userObj->setiLincData($ilincdata["id"], $ilincdata["login"], $ilincdata["password"]);
							$this->userObj->setActive($this->currActive == 'true' || is_null($this->currActive), $ilUser->getId());

							$this->userObj->create();

							//insert user data in table user_data
							$this->userObj->saveAsNew(false);

							// set user preferences
							$this->userObj->setPref("skin",
								$ilias->ini->readVariable("layout","skin"));
							$this->userObj->setPref("style",
								$ilias->ini->readVariable("layout","style"));
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

							//set role entries
							foreach($this->roles as $role_id => $role)
							{
								if ($this->role_assign[$role_id])
								{
									$this->assignToRole($this->userObj, $this->role_assign[$role_id]);
								}
							}

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
							if (! is_null($this->currActive)) $updateUser->setActive($this->currActive == "true", $ilUser->getId());
							if (! is_null($this->userObj->getClientIP())) $updateUser->setClientIP($this->userObj->getClientIP());
							if (! is_null($this->userObj->getTimeLimitOwner())) $updateUser->setTimeLimitOwner($this->userObj->getTimeLimitOwner());
							if (! is_null($this->userObj->getTimeLimitUnlimited())) $updateUser->setTimeLimitUnlimited($this->userObj->getTimeLimitUnlimited());
							if (! is_null($this->userObj->getTimeLimitFrom())) $updateUser->setTimeLimitFrom($this->userObj->getTimeLimitFrom());
							if (! is_null($this->userObj->getTimeLimitUntil())) $updateUser->setTimeLimitUntil($this->userObj->getTimeLimitUntil());
							if (! is_null($this->userObj->getTimeLimitMessage())) $updateUser->setTimeLimitMessage($this->userObj->getTimeLimitMessage());
							if (! is_null($this->userObj->getApproveDate())) $updateUser->setApproveDate($this->userObj->getApproveDate());
							$ilincdata = $this->userObj->getiLincData();
							if (! is_null($ilincdata)) $updateUser->setiLincData($ilincdata["id"], $ilincdata["login"], $ilincdata["password"]);

							$updateUser->update();

							if (!is_null($this->userObj->getLogin()) && $this->user_id != -1)
								$updateUser->updateLogin($this->userObj->getLogin());

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
				$ilincdata["id"] = $this->cdata;
				break;

			case "iLincLogin":
				$ilincdata["login"] = $this->cdata;
				break;

			case "iLincPasswd":
				$ilincdata["passwd"] = $this->cdata;
				$this->userObj->setiLincData($this->ilincdata);
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
		global $lng,$ilAccess;

		switch($a_name)
		{
			case "Role":
				$this->roles[$this->current_role_id]["name"] = $this->cdata;
				$this->roles[$this->current_role_id]["type"] = $this->current_role_type;
				$this->roles[$this->current_role_id]["action"] = $this->current_role_action;
				break;

			case "User":
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
}
?>
