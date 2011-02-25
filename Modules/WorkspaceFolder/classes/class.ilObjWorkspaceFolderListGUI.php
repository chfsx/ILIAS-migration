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
		$this->type = "wfld";
		$this->gui_class_name = "ilobjworkspacefoldergui";

		// general commands array
		include_once('./Modules/WorkspaceFolder/classes/class.ilObjWorkspaceFolderAccess.php');
		$this->commands = ilObjWorkspaceFolderAccess::_getCommands();
	}
	
} // END class.ilObjFolderListGUI
?>
