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

require_once("classes/class.ilSaxParser.php");
require_once("classes/class.ilObjUser.php");


/**
 * Group Import Parser
 *
 * @author Stefan Meyer <smeyer@databay.de>
 * @version $Id$
 *
 * @extends ilSaxParser
 * @package core
 */
class ilGroupImportParser extends ilSaxParser
{
	var $group_data;
	var $group_obj;

	var $parent;
	var $counter;

	/**
	 * Constructor
	 *
	 * @param	string		$a_xml_file		xml file
	 *
	 * @access	public
	 */

	function ilGroupImportParser($a_xml_file,$a_parent_id)
	{
		define('EXPORT_VERSION',1);

		parent::ilSaxParser($a_xml_file);

		// SET MEMBER VARIABLES
		$this->__pushParentId($a_parent_id);


	}

	function __pushParentId($a_id)
	{
		$this->parent[] = $a_id;
	}
	function __popParentId()
	{
		array_pop($this->parent);

		return true;
	}
	function __getParentId()
	{
		return $this->parent[count($this->parent) - 1];
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
		return true;
	}


	/**
	 * handler for begin of element
	 */
	function handlerBeginTag($a_xml_parser, $a_name, $a_attribs)
	{
		global $ilErr;

		switch($a_name)
		{
			// GROUP DATA
			case "group":
				if($a_attribs["exportVersion"] < EXPORT_VERSION)
				{
					$ilErr->raiseError("!!! This export Version isn't supported, update your ILIAS 2 installation"
									   ,$ilErr->WARNING);
				}
				// DEFAULT
				$this->group_data["admin"] = array();
				$this->group_data["member"] = array();

				$this->group_data["type"] = $a_attribs["type"];
				$this->group_data["id"] = $a_attribs["id"];
				
				break;

			case "owner":
				$this->group_data["owner"] = $a_attribs["id"];
				break;

			case "admin":
				$this->group_data["admin"][] = $a_attribs["id"];
				break;

			case "member":
				$this->group_data["member"][] = $a_attribs["id"];
				
				// NOW SAVE THE NEW OBJECT
				$this->__save();
				break;

			case "folder":
				break;

			case "file":
				$this->file["fileName"] = $a_attribs["fileName"];
				$this->file["id"] = $a_attribs["id"];

				// SAVE IT
				$this->__saveFile();
				break;
		}
	}


	function handlerEndTag($a_xml_parser, $a_name)
	{
		switch($a_name)
		{
			case "title":
				$this->group_data["title"] = $this->cdata;
				break;

			case "description":
				$this->group_data["description"] = $this->cdata;
				break;
				
			case "folder":
				$this->__popParentId();
				break;

			case "folderTitle":
				$this->folder = $this->cdata;
				$this->__saveFolder();

				break;
		}
		$this->cdata = '';
	}
	
	
	/**
	 * handler for character data
	 */
	function handlerCharacterData($a_xml_parser, $a_data)
	{
		// i don't know why this is necessary, but
		// the parser seems to convert "&gt;" to ">" and "&lt;" to "<"
		// in character data, but we don't want that, because it's the
		// way we mask user html in our content, so we convert back...
		$a_data = str_replace("<","&lt;",$a_data);
		$a_data = str_replace(">","&gt;",$a_data);

		if(!empty($a_data))
		{
			$this->cdata .= $a_data;
		}
	}
	
	// PRIVATE
	function __save()
	{
		if($this->group_imported)
		{
			return true;
		}

		$this->__initGroupObject();
		
		$this->group_obj->setImportId($this->group_data["id"]);
		$this->group_obj->setTitle($this->group_data["title"]);
		$this->group_obj->setDescription($this->group_data["description"]);

		// CREATE IT
		$this->group_obj->create();
		$this->group_obj->createReference();
		$this->group_obj->putInTree($this->__getParentId());
		$this->group_obj->initDefaultRoles();


		// SET GROUP SPECIFIC DATA
		$this->group_obj->setRegistrationFlag(0);
		$this->group_obj->setGroupStatus($this->group_data["type"] == "open" ? 0 : 1);
		
		// ASSIGN ADMINS/MEMBERS
		$this->__assignMembers();

		$this->__pushParentId($this->group_obj->getRefId());

		
		$this->group_imported = true;
	
		return true;
	}

	function __saveFolder()
	{
		$this->__initFolderObject();

		$this->folder_obj->setTitle($this->folder);
		$this->folder_obj->create();
		$this->folder_obj->createReference();
		$this->folder_obj->putInTree($this->__getParentId());
		$this->folder_obj->initDefaultRoles();

		$this->__pushParentId($this->folder_obj->getRefId());

		$this->__destroyFolderObject();

		return true;
	}
	
	function __saveFile()
	{
		$this->__initFileObject();

		$this->file_obj->setType("file");
		$this->file_obj->setTitle($this->file["fileName"]);
		$this->file_obj->setFileName($this->file["fileName"]);
		$this->file_obj->create();
		$this->file_obj->createReference();
		$this->file_obj->putInTree($this->__getParentId());
		$this->file_obj->setPermissions($this->__getParentId());

		// COPY FILE
		$this->file_obj->createDirectory();
		
		$this->__initImportFileObject();

		if($this->import_file_obj->findObjectFile($this->file["id"]))
		{
			$this->file_obj->copy($this->import_file_obj->getObjectFile(),$this->file["fileName"]);
		}

		unset($this->file_obj);
		unset($this->import_file_obj);

		return true;
	}

	function __assignMembers()
	{
		global $ilias;

		// OWNER
		if(!($usr_id = ilObjUser::_getImportedUserId($this->group_data["owner"])))
		{
			$usr_id = $ilias->account->getId();
		}
		$this->group_obj->addMember($usr_id,$this->group_obj->getDefaultAdminRole());

		// ADMIN
		foreach($this->group_data["admin"] as $user)
		{
			if($usr_id = ilObjUser::_getImportedUserId($this->group_data["owner"]))
			{
				$this->group_obj->addMember($usr_id,$this->group_obj->getDefaultAdminRole());
			}
		}
		
		// MEMBER
		foreach($this->group_data["member"] as $user)
		{
			if($usr_id = ilObjUser::_getImportedUserId($this->group_data["owner"]))
			{
				$this->group_obj->addMember($usr_id,$this->group_obj->getDefaultAdminRole());
			}
		}
		return true;
	}
	
	function __initGroupObject()
	{
		include_once "classes/class.ilObjGroup.php";
		
		$this->group_obj =& new ilObjGroup();
		
		return true;
	}
	
	function __initFolderObject()
	{
		include_once "classes/class.ilObjFolder.php";
		
		$this->folder_obj =& new ilObjFolder();
		
		return true;
	}

	function __initImportFileObject()
	{
		include_once "classes/class.ilFileDataImportGroup.php";

		$this->import_file_obj =& new ilFileDataImportGroup();

		return true;
	}

	function __initFileObject()
	{
		include_once "classes/class.ilObjFile.php";
		
		$this->file_obj =& new ilObjFile();
		
		return true;
	}		

	function __destroyFolderObject()
	{
		unset($this->folder_obj);
	}
}
?>
