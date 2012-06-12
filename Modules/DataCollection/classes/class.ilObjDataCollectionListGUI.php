<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Object/classes/class.ilObjectListGUI.php";

/**
* Class ilObjDataCollectionListGUI
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* $Id: class.ilObjRootFolderListGUI.php 23764 2010-05-06 15:11:30Z smeyer $
*
* @extends ilObjectListGUI
*/
class ilObjDataCollectionListGUI extends ilObjectListGUI
{
	/**
	* initialisation
	*/
	function init()
	{
		$this->copy_enabled = true;
		$this->delete_enabled = true;
		$this->cut_enabled = true;
		$this->subscribe_enabled = false;
		$this->link_enabled = false;
		$this->payment_enabled = false;
		$this->info_screen_enabled = true;
		$this->type = "dcl";
		$this->gui_class_name = "ilobjdatacollectiongui";

		// general commands array
		include_once('./Modules/DataCollection/classes/class.ilObjDataCollectionAccess.php');
		$this->commands = ilObjDataCollectionAccess::_getCommands();
	}
}

?>