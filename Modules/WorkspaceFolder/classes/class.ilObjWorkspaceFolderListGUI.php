<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilObjWorkspaceFolderListGUI
*
* @author Alex Killing <alex.killing@gmx.de>
* $Id: class.ilObjFolderListGUI.php 26089 2010-10-20 08:08:05Z smeyer $
*
* @extends ilObjectListGUI
*/

include_once "Services/Object/classes/class.ilObjectListGUI.php";

class ilObjWorkspaceFolderListGUI extends ilObjectListGUI
{
	/**
	* initialisation
	*/
	function init()
	{
		$this->static_link_enabled = true;
		$this->delete_enabled = true;
		$this->cut_enabled = true;
		$this->copy_enabled = true;
		$this->subscribe_enabled = true;
		$this->link_enabled = false;
		$this->payment_enabled = false;
		$this->info_screen_enabled = true;
		$this->type = "fold";
		$this->gui_class_name = "ilobjfoldergui";

		// general commands array
		include_once('./Modules/Folder/classes/class.ilObjFolderAccess.php');
		$this->commands = ilObjFolderAccess::_getCommands();
	}

	// BEGIN WebDAV: Get parent properties
	// BEGIN ChangeEvent: Get parent properties
	/**
	* Get item properties
	*
	* @return	array		array of property arrays:
	*						"alert" (boolean) => display as an alert property (usually in red)
	*						"property" (string) => property name
	*						"value" (string) => property value
	*/
	function getProperties()
	{
		global $lng, $ilUser, $ilias;

		// BEGIN WebDAV get parent properties
		$props = parent::getProperties();
		// END WebDAV get parent properties

		return $props;
	}
	// END ChangeEvent: Get parent properties
	// END WebDAV: Get parent properties

	/**
	* Get command link url.
	*
	* @param	int			$a_ref_id		reference id
	* @param	string		$a_cmd			command
	*
	*/
	function getCommandLink($a_cmd)
	{
		// BEGIN WebDAV: Mount webfolder.
		switch ($a_cmd) 
		{
			case 'mount_webfolder' :
				require_once ('Services/WebDAV/classes/class.ilDAVActivationChecker.php');
				if (ilDAVActivationChecker::_isActive())
				{
					require_once ('Services/WebDAV/classes/class.ilDAVServer.php');
					$davServer = ilDAVServer::getInstance();
					
					// XXX: The following is a very dirty, ugly trick. 
					//        To mount URI needs to be put into two attributes:
					//        href and folder. This hack returns both attributes
					//        like this:  http://...mount_uri..." folder="http://...folder_uri...
					$cmd_link = $davServer->getMountURI($this->ref_id, $this->title, $this->parent).
								'" folder="'.$davServer->getFolderURI($this->ref_id, $this->title, $this->parent);
					break;
				} // Fall through, when plugin is inactive.
			default :
				// separate method for this line
				$cmd_link = "repository.php?ref_id=".$this->ref_id."&cmd=$a_cmd";
				break;
		}
		
		return $cmd_link;
		// END WebDAV: Mount Webfolder
	}

	// BEGIN WebDAV: mount_webfolder in _blank frame
	/**
	* Get command target frame.
	*
	* Overwrite this method if link frame is not current frame
	*
	* @param	string		$a_cmd			command
	*
	* @return	string		command target frame
	*/
	function getCommandFrame($a_cmd)
	{
		require_once ('Services/WebDAV/classes/class.ilDAVActivationChecker.php');
		if (ilDAVActivationChecker::_isActive())
		{
			return ($a_cmd == 'mount_webfolder') ? '_blank' : '';
		}
		else
		{
			return '';
		}
	}
	// END WebDAV: mount_webfolder in _blank frame


} // END class.ilObjFolderListGUI
?>
