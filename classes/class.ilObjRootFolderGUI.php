<?php
/**
* Class ilObjRootFolderGUI
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$Id: class.ilObjRootFolderGUI.php,v 1.1 2003/03/24 15:41:43 akill Exp $
* 
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "class.ilObjectGUI.php";

class ilObjRootFolderGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjRootFolderGUI($a_data,$a_id,$a_call_by_reference)
	{
		$this->type = "root";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference);
	}
}
?>
