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
* Class ilObjRecoveryFolderGUI
*
* @author Sascha Hofmann <shofmann@databay.de> 
* @version $Id$
*
* @ilCtrl_Calls ilObjRecoveryFolderGUI: ilPermissionGUI
*
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "class.ilObjectGUI.php";

class ilObjRecoveryFolderGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjRecoveryFolderGUI($a_data,$a_id,$a_call_by_reference)
	{
		$this->type = "recf";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference,false);
	}
	
	/**
	* save object
	* @access	public
	*/
	function saveObject()
	{
		global $rbacadmin;

		// create and insert forum in objecttree
		$newObj = parent::saveObject();

		// setup rolefolder & default local roles
		//$roles = $newObj->initDefaultRoles();

		// ...finally assign role to creator of object
		//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "y");

		// put here object specific stuff
			
		// always send a message
		sendInfo($this->lng->txt("object_added"),true);
		
		header("Location:".$this->getReturnLocation("save","adm_object.php?".$this->link_params));
		exit();
	}

	function removeFromSystemObject()
	{
		global $rbacsystem;
		
		// AT LEAST ONE OBJECT HAS TO BE CHOSEN.
		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		$this->object->notify("removeFromSystem", $_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$_POST["trash_id"]);

		// DELETE THEM
		foreach ($_POST["id"] as $id)
		{
			// GET COMPLETE NODE_DATA OF ALL SUBTREE NODES
			$node_data = $this->tree->getNodeData($id);
			$subtree_nodes = $this->tree->getSubTree($node_data);

			// remember already checked deleted node_ids
			$checked[] = $id;

			// dive in recursive manner in each already deleted subtrees and remove these objects too
			$this->removeDeletedNodes($id,$checked);

			foreach ($subtree_nodes as $node)
			{
				$node_obj =& $this->ilias->obj_factory->getInstanceByRefId($node["ref_id"]);
				$node_obj->delete();
			}

			// FIRST DELETE ALL ENTRIES IN RBAC TREE
			$this->tree->deleteTree($node_data);
		}
		
		sendInfo($this->lng->txt("msg_removed"),true);

		ilUtil::redirect($this->getReturnLocation("removeFromSystem","adm_object.php?ref_id=".$_GET["ref_id"]));

	}
	
		function &executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();
		$this->prepareOutput();

		switch($next_class)
		{
			case 'ilpermissiongui':
				include_once("./classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			default:
				if(!$cmd)
				{
					$cmd = "view";
				}
				$cmd .= "Object";
				$this->$cmd();

				break;
		}
		return true;
	}

	
	function showPossibleSubObjects()
	{
		$this->sub_objects = "";
	}
} // END class.ilObjRecoveryFolderGUI
?>
