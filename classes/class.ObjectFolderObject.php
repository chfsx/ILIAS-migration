<?php
/**
* Class ObjectFolderObject
* 
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @extends Object
* @package ilias-core
*/
class ObjectFolderObject extends Object
{
	/**
	* Constructor
	* @access	public
	**/
	function ObjectFolderObject()
	{
		$this->Object();
	}
	
	function viewObject()
	{
		global $rbacsystem, $rbacadmin, $tpl;

		//prepare objectlist
		$this->objectList = array();
		$this->objectList["data"] = array();
		$this->objectList["ctrl"] = array();

		$this->objectList["cols"] = array("", "type", "name", "description", "last_change");
		
		if ($rbacsystem->checkAccess("read", $this->id, $this->parent))
		{
			if ($list = getTypeList($_GET["order"],$_GET["direction"]))
			{
				foreach ($list as $key => $val)
				{
					//visible data part
					$this->objectList["data"][] = array(
						"type" => "<img src=\"".$tpl->tplPath."/images/"."icon_type_b".".gif\" border=\"0\">",
						"name" => $val["title"],
						"description" => $val["desc"],
						"last_change" => $val["last_update"]
					);
	
					//control information
					$this->objectList["ctrl"][] = array(
						"type" => $val["type"],
						"obj_id" => $val["id"],
						"parent" => $val["parent"],
						"parent_parent" => $val["parent_parent"],
					);
				}
			} //if userdata

			return $this->objectList;

		} //if rbac
		else
		{
			$ilias->raiseError("No permission to read user folder",$ilias->error_obj->MESSAGE);
		}
	} //function
	
	function getSubObjects()	
	{
		return false;
	} //function
	
} // END class.ObjectFolderObject
?>