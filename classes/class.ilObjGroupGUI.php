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
* Class ilObjGroupGUI
*
* @author Stefan Meyer <smeyer@databay.de>

* $Id$Id: class.ilObjGroupGUI.php,v 1.30 2003/08/14 09:42:52 mmaschke Exp $

*
* @extends ilObjectGUI
* @package ilias-core
*/


include_once "class.ilObjectGUI.php";
include_once "class.ilObjGroup.php";
require_once("classes/class.ilTableGUI.php");


class ilObjGroupGUI extends ilObjectGUI
{	
	/**
	*comment fails
	*/
	
	var $grp_tree;


	/**
	* Constructor
	* @access public
	*/
	function ilObjGroupGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output = true)
	{
		global $tree;

		$this->type = "grp";
		//$this->lng =& $lng;
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);

		$this->grp_tree = new ilTree($this->object->getRefId());

		$this->grp_tree->setTableNames("grp_tree","object_data","object_reference");


		//$this->grp_tree = new ilTree($this->object->getRefId());
		$this->grp_tree = new ilTree($this->object->getId());
		$this->grp_tree->setTableNames("grp_tree","object_data","object_reference");

	}

	/**
	* create new object form
	*/
	function createObject()
	{
		//TODO: check the acces rights; compare class.ilObjectGUI.php

		global $rbacsystem;

			$data = array();
			$data["fields"] = array();
			$data["fields"]["group_name"] = "";
			$data["fields"]["desc"] = "";

			$this->getTemplateFile("new","group");

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);

				if ($this->prepare_output)
				{
					$this->tpl->parseCurrentBlock();
				}
			}

			$stati = array("group_status_public","group_status_private","group_status_closed");

			//build form
			$opts = ilUtil::formSelect(0,"group_status_select",$stati);

			$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
			$this->tpl->setVariable("TXT_GROUP_STATUS", $this->lng->txt("group_status"));
			$this->tpl->setVariable("FORMACTION", $this->getFormAction("save","adm_object.php?cmd=save&ref_id=".$_GET["ref_id"]."&new_type=".$_POST["new_type"]));
			$this->tpl->setVariable("TARGET", $this->getTargetFrame("save"));
			//$this->tpl->setVariable("FORMACTION", "adm_object.php?cmd=save"."&ref_id=".$_GET["ref_id"]."&new_type=".$_POST["new_type"]);
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
			$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
	}


	/**
	*  Object
	* @access public
	*/
	function saveObject()
	{
		global $rbacadmin,$ilias;

		$groupObj = parent::saveObject();

		$rfoldObj = $groupObj->initRoleFolder();

		// setup rolefolder & default local roles if needed (see ilObjForum & ilObjForumGUI for an example)

		$groupObj->createDefaultGroupRoles($rfoldObj->getRefId());
		$groupObj->joinGroup($this->ilias->account->getId(),1); //join as admin=1

		//0=public,1=private,2=closed
		$groupObj->setGroupStatus($_POST["group_status_select"]);
		//$groupObj->createNewGroupTree($groupObj->getId(),$groupObj->getRefId());
		$groupObj->insertGroupNode($rfoldObj->getId(),$groupObj->getId(),$groupObj->getId(),$rfoldObj->getRefId());

		// always send a message
		sendInfo($this->lng->txt("grp_added"),true);
		header("Location: ".$this->getReturnLocation("save","adm_object.php?".$this->link_params));
		exit();
	}

	/**
	* list childs of current object
	*
	* @access	public
	*/
	function viewObject()
	{
		global $rbacsystem,$lng;

		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		//prepare objectlist
		$this->objectList = array();
		$this->data["data"] = array();
		$this->data["ctrl"] = array();
		$this->data["cols"] = array("", "type", "title", "description", "last_change");
		$childs = $this->tree->getChilds($_GET["ref_id"], $_GET["order"], $_GET["direction"]);


		// tmp display std tree
		$childs = $this->tree->getChilds($_GET["ref_id"], $_GET["order"], $_GET["direction"]);

		foreach ($childs as $key => $val)
		{

			//nur fr Objecte mit Rechten

			// visible
			if (!$rbacsystem->checkAccess("visible",$val["ref_id"]))
			{
				continue;
			}

			//visible data part
			$this->data["data"][] = array(
										"type" => $val["type"],
										"title" => $val["title"],
										"description" => $val["desc"],
										"last_change" => $val["last_update"],
										"ref_id" => $val["ref_id"]
										);
			//control information is set below

	    } //foreach

		$this->maxcount = count($this->data["data"]);
		// sorting array
		include_once "./include/inc.sort.php";
		$this->data["data"] = sortArray($this->data["data"],$_GET["sort_by"],$_GET["sort_order"]);
		$this->data["data"] = array_slice($this->data["data"],$_GET["offset"],$_GET["limit"]);

		// now compute control information
		foreach ($this->data["data"] as $key => $val)
		{
			$this->data["ctrl"][$key] = array(
											"type" => $val["type"],
											"ref_id" => $val["ref_id"],
											"tree_id" => $_GET["ref_id"],
											"tree_table" => $this->grp_tree->table_tree
											);
			//"tree_id" => $_GET["ref_id"],
			unset($this->data["data"][$key]["ref_id"]);
						$this->data["data"][$key]["last_change"] = ilFormat::formatDate($this->data["data"][$key]["last_change"]);
		}

		$this->displayList();
	}

	/**
	* update GroupObject
	* @access public
	*/
	function updateObject()
	{
		global $rbacsystem;
		if($rbacsystem->checkAccess("write",$this->object->getRefId()) )
		{
			if(isset($_POST["group_status_select"]))
				$this->object->setGroupStatus($_POST["group_status_select"]);
			parent::updateObject();
		}
		header("Location: adm_object.php?".$this->link_params);

	}

	/**
	* edit Group
	* @access public
	*/
	function editObject()
	{
		global $rbacsystem;

		$data = array();
		$data["fields"] = array();
		$data["fields"]["group_name"] = "";
		$data["fields"]["desc"] = "";

		$this->getTemplateFile("new","group");

		foreach ($data["fields"] as $key => $val)
		{
			$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
			$this->tpl->setVariable(strtoupper($key), $val);
			$this->tpl->parseCurrentBlock();
		}

		$stati = array(0=>"group_status_public",1=>"group_status_private",2=>"group_status_closed");

		//build form
		$grp_status = $this->object->getGroupStatus();
		$opts = ilUtil::formSelect($grp_status,"group_status_select",$stati,false,true);

		$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
		$this->tpl->setVariable("TXT_GROUP_STATUS", $this->lng->txt("group_status"));
		$this->tpl->setVariable("FORMACTION", "adm_object.php?cmd=update"."&ref_id=".$_GET["ref_id"].
			"&new_type=".$_POST["new_type"]);
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->setVariable("TITLE",$this->object->getTitle() );
		$this->tpl->setVariable("DESC",$this->object->getDescription() );

		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("update"));

	}


	/**
	* displays confirmation form
	* @access public
	*/
	function confirmationObject($user_id="", $confirm, $cancel, $info="", $status="")
	{
		$this->data["cols"] = array("type", "title", "description", "last_change");
		if(is_array($user_id))
			foreach($user_id as $id)
			{
				$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($id);

				$this->data["data"]["$id"] = array(
					"type"        => $obj_data->getType(),
					"title"       => $obj_data->getTitle(),
					"desc"        => $obj_data->getDescription(),
					"last_update" => $obj_data->getLastUpdateDate(),

					);

			}
		else
		{
			$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($user_id);

			$this->data["data"]["$id"] = array(
				"type"        => $obj_data->getType(),
				"title"       => $obj_data->getTitle(),
				"desc"        => $obj_data->getDescription(),
				"last_update" => $obj_data->getLastUpdateDate(),
				);
		}

		//write  in sessionvariables
		if(is_array($user_id))
			$_SESSION["saved_post"]["user_id"] = $user_id;
		else
			$_SESSION["saved_post"]["user_id"][0] = $user_id;

		if(isset($status))
			$_SESSION["saved_post"]["status"] = $status;

		$this->data["buttons"] = array( $confirm  => $this->lng->txt("confirm"),
						$cancel  => $this->lng->txt("cancel"));

		$this->getTemplateFile("confirm");
		//$this->tpl->addBlockFile("CONTENT", "content", "tpl.obj_confirm.html");
		infoPanel();

		sendInfo($this->lng->txt($info));
		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=gateway");

		// BEGIN TABLE HEADER
		foreach ($this->data["cols"] as $key)
		{
			$this->tpl->setCurrentBlock("table_header");
			$this->tpl->setVariable("TEXT",$this->lng->txt($key));
			$this->tpl->parseCurrentBlock();

		}
		// END TABLE HEADER

		// BEGIN TABLE DATA
		$counter = 0;

		foreach ($this->data["data"] as $key => $value)
		{
			// BEGIN TABLE CELL
			foreach($value as $key => $cell_data)
			{
				$this->tpl->setCurrentBlock("table_cell");

				// CREATE TEXT STRING
				if($key == "type")
				{
					$this->tpl->setVariable("TEXT_CONTENT",ilUtil::getImageTagByType($cell_data,$this->tpl->tplPath));
				}
				else
				{
					$this->tpl->setVariable("TEXT_CONTENT",$cell_data);
				}
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("table_row");
			$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
			$this->tpl->parseCurrentBlock();
			// END TABLE CELL
		}
		// END TABLE DATA

		// BEGIN OPERATION_BTN
		foreach ($this->data["buttons"] as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}

	}

	/**
	* leave Group
	* @access public
	*/
	function leaveGrpObject()
	{
		global $rbacsystem, $ilias;

		$member = array($_GET["mem_id"]);
		//set methods that are called after confirmation
		$confirm = "confirmedDeleteMember";
		$cancel  = "canceled";
		$info	 = "info_delete_sure";
		$status  = "";
		$this->confirmationObject($member, $confirm, $cancel, $info, $status);
	}


	/**
	* method is called when confirmation is canceled
	* @access public
	*/
	function canceledObject()
	{
		header("Location: adm_object.php?".$this->link_params);
	}

	/**
	* displays confirmation formular with users that shall be assigned to gorup
	* @access public
	*/
	function assignMemberObject()
	{
		$user_ids = $_POST["user_id"];
		if(isset($user_ids))
		{
			$confirm = "confirmedAssignMember";
			$cancel  = "canceled";
			$info	 = "info_assign_sure";
			$status  = $_SESSION["post_vars"]["status"];

			$this->confirmationObject($user_ids, $confirm, $cancel, $info, $status);
		}
		else
		{
			sendInfo($this->lng->txt("You have to choose at least one user !"),true);
			header("Location: adm_object.php?".$this->link_params);
		}

	}

	/**
	* assign new member to group
	* @access public
	*/
	function confirmedAssignMemberObject()
	{
		if(isset($_SESSION["saved_post"]["user_id"]) && isset($_SESSION["saved_post"]["status"]) )
		{
			//let new members join the group
			$newGrp = new ilObjGroup($this->object->getRefId(), true);
			foreach($_SESSION["saved_post"]["user_id"] as $new_member)
			{
				if(!$newGrp->joinGroup($new_member, $_SESSION["saved_post"]["status"]) )
					$this->ilias->raiseError("An Error occured while assigning user to group !",$this->ilias->error_obj->MESSAGE);
			}
			unset($_SESSION["saved_post"]);
		}

		header("Location: adm_object.php?".$this->link_params);
	}

	/**
	* displays confirmation formular with users that shall be removed from group
	* @access public
	*/
	function removeMemberObject()
	{
		$user_ids = array();
		if(isset($_POST["user_id"]))
			$user_ids = $_POST["user_id"];
		else if(isset($_GET["mem_id"]))
			$user_ids = $_GET["mem_id"];
		if(isset($user_ids))
		{
			$confirm = "confirmedRemoveMember";
			$cancel  = "canceled";
			$info	 = "info_delete_sure";
			$status  = "";
			$this->confirmationObject($user_ids, $confirm, $cancel, $info, $status);
		}
		else
		{
			sendInfo($this->lng->txt("You have to choose at least one user !"),true);
			header("Location: adm_object.php?".$this->link_params."&cmd=members");
		}
	}

	/**
	* remove members from group
	* @access public
	*/
	function confirmedRemoveMemberObject()
	{
		global $rbacsystem,$ilias;
//		print_r($_SESSION["saved_post"]);

		if(isset($_SESSION["saved_post"]["user_id"]) )
		{
			foreach($_SESSION["saved_post"]["user_id"] as $mem_id)
			{
				$newGrp = new ilObjGroup($_GET["ref_id"],true);
				if($rbacsystem->checkAccess('leave',$_GET["ref_id"]))
				{
					//check ammount of members
					if(count($newGrp->getGroupMemberIds()) == 1)
					{
						if($rbacsystem->checkAccess('delete',$_GET["ref_id"]))
						{
							//GROUP DELETE
							$this->ilias->raiseError("Gruppe loeschen, da letztes Mitglied!",$this->ilias->error_obj->MESSAGE);
						}
						else
							$this->ilias->raiseError("You do not have the permissions to delete this group!",$this->ilias->error_obj->MESSAGE);
					}
					else
					{
						//MEMBER LEAVES GROUP
						if($this->object->isMember($mem_id) && !$this->object->isAdmin($mem_id))
						{
							if(!$newGrp->leaveGroup($mem_id))
								$this->ilias->raiseError("Error while attempting to discharge user!",$this->ilias->error_obj->MESSAGE);
						}
						else	//ADMIN LEAVES GROUP
						if($this->object->isAdmin($mem_id))
						{
							if(count($this->object->getGroupAdminIds()) <= 1 )
							{
								$this->ilias->raiseError("At least one group administrator is required! Please entitle a new group administrator first ! ",$this->ilias->error_obj->WARNING);
							}
							else if(!$newGrp->leaveGroup($mem_id))
								$this->ilias->raiseError("Error while attempting to discharge user!",$this->ilias->error_obj->MESSAGE);
						}
					}
				}
				else
					$this->ilias->raiseError("You are not allowed to leave this group!",$this->ilias->error_obj->MESSAGE);
			}
		}
		unset($_SESSION["saved_post"]);
		header("Location: adm_object.php?".$this->link_params."&cmd=members");
	}


	/**
	* displays form in which the member-status can be changed
	* @access public
	*/
	function changeMemberObject()
	{
		global $ilias,$tpl;

		include_once "./classes/class.ilTableGUI.php";

		$member_ids = array();

		if(isset($_POST["user_id"]))
			$member_ids = $_POST["user_id"];
		else if(isset($_GET["mem_id"]))
			$member_ids[0] = $_GET["mem_id"];

		$newGrp = new ilObjGroup($_GET["ref_id"],true);
		$stati = array(0=>"grp_member_role",1=>"grp_admin_role");

		//build data structure
		foreach($member_ids as $member_id)
		{
			$member =& $ilias->obj_factory->getInstanceByObjId($member_id);
			$mem_status = $newGrp->getMemberStatus($member_id);

			$this->data["data"][$member->getId()]= array(
				"login"        => $member->getLogin(),
				"firstname"       => $member->getFirstname(),
				"lastname"        => $member->getLastname(),
				"grp_role" => ilUtil::formSelect($mem_status,"member_status_select[".$member->getId()."]",$stati,false,true)
				);
			unset($member);
		}

		$this->getTemplateFile("chooseuser","grp");
		infoPanel();

		$this->tpl->addBlockfile("NEW_MEMBERS_TABLE", "member_table", "tpl.table.html");

		// load template for table content data
		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=gateway");

		$this->data["buttons"] = array( "updateMemberStatus"  => $this->lng->txt("confirm"),
						"members"  => $this->lng->txt("cancel"));

		$this->tpl->setCurrentBlock("tbl_action_row");
		$this->tpl->setVariable("COLUMN_COUNTS",4);
		$this->tpl->setVariable("TPLPATH",$this->ilias->tplPath);

		foreach ($this->data["buttons"] as $name => $value)
		{
			$this->tpl->setCurrentBlock("tbl_action_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}

		// create table
		$tbl = new ilTableGUI($this->data["data"]);
		// title & header columns
		$tbl->setTitle($this->lng->txt("change member status"),"icon_usr_b.gif",$this->lng->txt("change member status"));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));
		$tbl->setHeaderNames(array($this->lng->txt("firstname"),$this->lng->txt("lastname"),$this->lng->txt("role"),$this->lng->txt("status")));
		$tbl->setHeaderVars(array("firstname","lastname","role","status"),array("ref_id"=>$_GET["ref_id"],"cmd"=>$_GET["cmd"]));

		$tbl->setColumnWidth(array("25%","25%","25%","25%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit(10);
		$tbl->setOffset(0);
		$tbl->setMaxCount(count($this->data["data"]));

		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));

		// render table
		$tbl->render();
	}


	/**
	* displays search form for new users
	* @access public
	*/
	function newMembersObject()
	{
		$this->getTemplateFile("newmember","grp");

		$this->tpl->setVariable("TXT_MEMBER_NAME", $this->lng->txt("username"));
		$this->tpl->setVariable("TXT_STATUS", $this->lng->txt("member_status"));

		$radio_member = ilUtil::formRadioButton($_POST["status"] ? 0:1,"status",0);
		$radio_admin  = ilUtil::formRadioButton($_POST["status"] ? 1:0,"status",1);
		$this->tpl->setVariable("RADIO_MEMBER", $radio_member);
		$this->tpl->setVariable("RADIO_ADMIN", $radio_admin);
		$this->tpl->setVariable("TXT_MEMBER_STATUS", "Member");
		$this->tpl->setVariable("TXT_ADMIN_STATUS", "Admin");
		$this->tpl->setVariable("TXT_SEARCH", "Search");

		if(isset($_POST["search_user"]) )
			$this->tpl->setVariable("SEARCH_STRING", $_POST["search_user"]);
		else if(isset($_GET["search_user"]) )
			$this->tpl->setVariable("SEARCH_STRING", $_GET["search_user"]);

		$this->tpl->setVariable("FORMACTION_NEW_MEMBER", "adm_object.php?type=grp&cmd=newMembers&ref_id=".$_GET["ref_id"]);//"&search_user=".$_POST["search_user"]
		$this->tpl->parseCurrentBlock();

		//query already started ?
		if( (isset($_POST["search_user"]) && isset($_POST["status"]) ) || ( isset($_GET["search_user"]) && isset($_GET["status"]) ) )//&& isset($_GET["ref_id"]) )
		{
			$member_ids = ilObjUser::searchUsers($_POST["search_user"] ? $_POST["search_user"] : $_GET["search_user"]);

			foreach($member_ids as $member)
			{
				$this->data["data"][$member["usr_id"]]= array(
					"check"		=> ilUtil::formCheckBox(0,"user_id[]",$member["usr_id"]),
					"login"        => $member["login"],
					"firstname"       => $member["firstname"],
					"lastname"        => $member["lastname"]
					);

			}

			//display search results
			infoPanel();

			$this->tpl->addBlockfile("NEW_MEMBERS_TABLE", "member_table", "tpl.table.html");
			// load template for table content data

			$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=gateway");

			$this->data["buttons"] = array( "assignMember"  => $this->lng->txt("assign"),
							"canceled"  => $this->lng->txt("cancel"));

			$this->tpl->setCurrentBlock("tbl_action_row");
			$this->tpl->setVariable("COLUMN_COUNTS",4);
			$this->tpl->setVariable("TPLPATH",$this->tplPath);

			foreach ($this->data["buttons"] as $name => $value)
			{
				$this->tpl->setCurrentBlock("tbl_action_btn");
				$this->tpl->setVariable("BTN_NAME",$name);
				$this->tpl->setVariable("BTN_VALUE",$value);
				$this->tpl->parseCurrentBlock();
			}

			//sort data array
			include_once "./include/inc.sort.php";
			$this->data["data"] = sortArray($this->data["data"], $_GET["sort_by"], $_GET["sort_order"]);
			$output = array_slice($this->data["data"],$_GET["offset"],$_GET["limit"]);

			// create table
			include_once "./classes/class.ilTableGUI.php";
			$tbl = new ilTableGUI($output);
			// title & header columns
			$tbl->setTitle($this->lng->txt("member list"),"icon_usr_b.gif",$this->lng->txt("member list"));
			$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));
			$tbl->setHeaderNames(array($this->lng->txt("check"),$this->lng->txt("username"),$this->lng->txt("firstname"),$this->lng->txt("lastname")));
			$tbl->setHeaderVars(array("check","login","firstname","lastname"),array("ref_id"=>$_GET["ref_id"],"cmd"=>$_GET["cmd"],"search_user"=>$_POST["search_user"] ? $_POST["search_user"] : $_GET["search_user"],"status"=>$_POST["status"] ? $_POST["status"] : $_GET["status"]));

			$tbl->setColumnWidth(array("5%","25%","35%","35%"));

			// control
			$tbl->setOrderColumn($_GET["sort_by"]);
			$tbl->setOrderDirection($_GET["sort_order"]);
			$tbl->setLimit($_GET["limit"]);
			$tbl->setOffset($_GET["offset"]);
			$tbl->setMaxCount(count($this->data["data"]));

			$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));

			// render table
			$tbl->render();
		}
	}

	/**
	* displays form with all members of group
	* @access public
	*/
	function membersObject()
	{
		global $rbacsystem;
		//check Access
  		if(!$rbacsystem->checkAccess("read,leave",$this->object->getRefId() ))
		{
			$this->ilias->raiseError("Permission denied !",$this->ilias->error_obj->MESSAGE);
		}

		$img_contact = "pencil";
		$img_change = "change";
		$img_leave = "group_out";
		$val_contact = ilUtil::getImageTagByType($img_contact, $this->tpl->tplPath);
		$val_change = ilUtil::getImageTagByType($img_change, $this->tpl->tplPath);
		$val_leave  = ilUtil::getImageTagByType($img_leave, $this->tpl->tplPath);

		$newGrp = new ilObjGroup($_GET["ref_id"],true);
		$member_ids = $newGrp->getGroupMemberIds($_GET["ref_id"]);
		$admin_ids = $newGrp->getGroupAdminIds($_GET["ref_id"]);

		foreach($member_ids as $member_id)
		{
			$member =& $this->ilias->obj_factory->getInstanceByObjId($member_id);

			$link_contact = "mail_new.php?mobj_id=3&type=new&mail_data[rcp_to]=".$member->getLogin();
			$link_change = "adm_object.php?cmd=changeMember&ref_id=".$this->ref_id."&mem_id=".$member->getId();
			$link_leave = "adm_object.php?type=grp&cmd=removeMember&ref_id=".$_GET["ref_id"]."&mem_id=".$member->getId();

			//build function
			if(in_array($_SESSION["AccountId"], $admin_ids))
			{
				$member_functions = "<a href=\"$link_change\">$val_change</a>";
			}
			if(in_array($_SESSION["AccountId"], $admin_ids) || $member->getId() == $_SESSION["AccountId"])
			{
				$member_functions .="<a href=\"$link_leave\">$val_leave</a>";

			}



			$grp_role_id = $newGrp->getGroupRoleId($member->getId());
			$newObj	     = new ilObject($grp_role_id,false);

			$this->data["data"][$member->getId()]= array(
			        "check"		=> ilUtil::formCheckBox(0,"user_id[]",$member->getId()),
				"login"        => $member->getLogin(),
				"firstname"       => $member->getFirstname(),
				"lastname"        => $member->getLastname(),
				"grp_role" => $newObj->getTitle(),
				"functions" => "<a href=\"$link_contact\">".$val_contact."</a>".$member_functions
				);
			unset($member_functions);
			unset($member);
			unset($newObj);
		}

		$this->getTemplateFile("chooseuser","grp");
		infoPanel();

		$this->tpl->addBlockfile("NEW_MEMBERS_TABLE", "member_table", "tpl.table.html");

		// load template for table content data
		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=gateway");

		$this->data["buttons"] = array( "removeMember"  => $this->lng->txt("remove"),
						"changeMember"  => $this->lng->txt("change"));

		$this->tpl->setCurrentBlock("tbl_action_row");
		$this->tpl->setVariable("COLUMN_COUNTS",6);
		$this->tpl->setVariable("TPLPATH",$this->tplPath);

		foreach ($this->data["buttons"] as $name => $value)
		{
			$this->tpl->setCurrentBlock("tbl_action_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}

		//sort data array
		include_once "./include/inc.sort.php";
		include_once "./classes/class.ilTableGUI.php";

		$this->data["data"] = sortArray($this->data["data"], $_GET["sort_by"], $_GET["sort_order"]);

		$output = array_slice($this->data["data"],$_GET["offset"],$_GET["limit"]);

		// create table
		$tbl = new ilTableGUI($output);
		// title & header columns
		$tbl->setTitle($this->lng->txt("member list"),"icon_usr_b.gif",$this->lng->txt("member list"));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));
		$tbl->setHeaderNames(array($this->lng->txt("check"),$this->lng->txt("username"),$this->lng->txt("firstname"),$this->lng->txt("lastname"),$this->lng->txt("role"),$this->lng->txt("functions")));
		$tbl->setHeaderVars(array("check","login","firstname","lastname","role","functions"),array("ref_id"=>$_GET["ref_id"],"cmd"=>$_GET["cmd"]));

		$tbl->setColumnWidth(array("5%","15%","30%","30%","10%","10%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount(count($this->data["data"]));

		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));

		$tbl->render();
	}


	/**
	* displays form in which the member-status can be changed
	* @access public
	*/
	function updateMemberStatusObject()
	{
		global $rbacsystem;

		if(!$rbacsystem->checkAccess("write",$this->object->getRefId()) )
		{
			$this->ilias->raiseError("No permissions to change member status!",$this->ilias->error_obj->WARNING);
		}
		else
		{
			if(isset($_POST["member_status_select"]))
			{
				foreach($_POST["member_status_select"] as $key=>$value)
				{
					$this->object->setMemberStatus($key,$value);
				}
			}
		}
		//TODO: link back
		header("Location: adm_object.php?".$this->link_params."&cmd=members");


	}



	/**
	* displays groups
	* @access public
	*/
	function listGroups()
	{

		$this->getTemplateFile("overview", "grp");

		//$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");
		//$this->tpl->addBlockfile("CONTENT", "content", "tpl.grp_overview.html");

		$this->tpl->setCurrentBlock("content");

		$this->tpl->setVariable("TXT_GROUPS",  $this->lng->txt("groups"));
		$this->tpl->setCurrentBlock("tblheader");
		$this->tpl->setVariable("TXT_NAME",  $this->lng->txt("name"));
		$this->tpl->setVariable("TXT_DESC",  $this->lng->txt("description"));
		$this->tpl->setVariable("TXT_ROLE_IN_GROUP",  $this->lng->txt("role"));
		$this->tpl->setVariable("TXT_OWNER",  $this->lng->txt("owner"));
		$this->tpl->setVariable("TXT_CONTEXT",  $this->lng->txt("context"));

		$lr_arr = ilUtil::getObjectsByOperations('grp','visible');

		usort($lr_arr,"sortObjectsByTitle");

		$lr_num = count($lr_arr);

		if ($lr_num > 0)
		{
			// counter for rowcolor change

			$num = 0;
			//var_dump ($lr_arr);
			foreach ($lr_arr as $grp_data)
			{
				$this->tpl->setCurrentBlock("tblcontent");

				// change row color
				$this->tpl->setVariable("ROWCOL", ilUtil::switchColor($num,"tblrow2","tblrow1"));
				$num++;
				$newuser = new ilObjUser($grp_data["owner"]);
				$obj_link = "grp_details.php?ref_id=".$grp_data["ref_id"];
				$obj_icon = "icon_".$grp_data["type"]."_b.gif";

				$this->tpl->setVariable("GRP_NAME", $grp_data["title"]);
				$this->tpl->setVariable("GRP_LINK", $obj_link);
				/*if($lgrp_data["type"] == "grp")		// Test
				{
					//$this->tpl->setVariable("EDIT_LINK","content/lm_edit.php?lm_id=".$lr_data["obj_id"]);
					$this->tpl->setVariable("TXT_EDIT", "(".$this->lng->txt("edit").")");
					$this->tpl->setVariable("VIEW_LINK","content/lm_presentation.php?lm_id=".$grp_data["obj_id"]);
					$this->tpl->setVariable("TXT_VIEW", "(".$this->lng->txt("view").")");
				}*/
				//$this->tpl->setVariable("IMG", $obj_icon);
				//$this->tpl->setVariable("ALT_IMG", $lng->txt("obj_".$lr_data["type"]));
				$this->tpl->setVariable("GRP_DESC", $grp_data["desc"]);
				$this->tpl->setVariable("GRP_OWNER", $newuser->getFullname() );
				//$this->tpl->setVariable("STATUS", "N/A");
				//$this->tpl->setVariable("LAST_VISIT", "N/A");
				//$this->tpl->setVariable("LAST_CHANGE", ilFormat::formatDate($lr_data["last_update"]));
				$this->tpl->setVariable("GRP_CONTEXT", ilObjGroup::getContextPath2($grp_data["ref_id"]));

				$this->tpl->parseCurrentBlock("tblcontent");
			}

		}

	}


	/**
	* show details -> HOW NEEDS THIS METHOD
	* @access public
	*/
	function showDetails()
	{
		$this->getTemplateFile("details", "grp");
		//$this->tpl->addBlockFile("CONTENT", "content", "tpl.grp_details.html");
		$this->tpl->addBlockFile("BUTTONS", "buttons", "tpl.buttons.html");

		$this->tpl->setVariable("TXT_GRP_TITLE", $this->lng->txt("group_members"));
		$this->tpl->setCurrentBlock("groupheader");

		$this->tpl->setVariable("TXT_NAME", $this->lng->txt("name"));
		$this->tpl->setVariable("TXT_DESC", $this->lng->txt("description"));
		$this->tpl->setVariable("TXT_OWNER", $this->lng->txt("owner"));
		$this->tpl->setVariable("TXT_ROLE_IN_GROUP", $this->lng->txt("role"));
		$this->tpl->parseCurrentBlock("grouphesder");
		$lr_arr = array();
		$objects = $this->tree->getChilds($this->object->getId(),"title");
		//var_dump ($objects);
		if (count($objects) > 0)
		{
			foreach ($objects as $key => $object)
			{
				//var_dump ($object);
				if ($object["type"] == "le")// && $rbacsystem->checkAccess('visible',$objects["child"]))
				{

					$lr_arr[$key] = $object;
					//var_dump ($lr_arr);
				}
			}
		}
		//var_dump ($lr_arr);
		$maxcount = count($lr_arr);
		include_once "./include/inc.sort.php";
		$lr_arr = sortArray($lr_arr,$_GET["sort_by"],$_GET["sort_order"]);
		//$lr_arr = array_slice($lr_arr,$offset,$limit);


			$this->tpl->setCurrentBlock("loheader");
			$this->tpl->setVariable("TXT_LO_TITLE", $this->lng->txt("lo"));
			$this->tpl->setVariable("TXT_LO_NAME", $this->lng->txt("name"));
			$this->tpl->setVariable("TXT_LO_DESC", $this->lng->txt("description"));
			$this->tpl->setVariable("TXT_LO_OWNER", $this->lng->txt("owner"));
			$this->tpl->setVariable("TXT_LO_LAST_CHANGE", $this->lng->txt("last_change"));

		//var_dump ($lr_arr);
		$num = 0;
		foreach ($lr_arr as $lr_data)
		{
			$this->tpl->setCurrentBlock("locontent");
			//var_dump ($lr_data);
			// change row color
			$this->tpl->setVariable("ROWCOL", ilUtil::switchColor($num,"tblrow2","tblrow1"));
			$num++;

			//$obj_link = "lo_view.php?lm_id=".$lr_data["ref_id"];
			$obj_icon = "icon_".$lr_data["type"]."_b.gif";

			$this->tpl->setVariable("LO_DESC", $lr_data["description"]);
			$this->tpl->setVariable("LO_NAME", $lr_data["title"]);
			$this->tpl->setVariable("LO_LAST_CHANGE", ilFormat::formatDate($lr_data["last_update"]));
			$this->tpl->setVariable("LO_CONTEXTPATH", ilObjGroup::getContextPath2($lr_data["ref_id"]));
			$this->tpl->parseCurrentBlock("locontent");
		}
	}

	/**
	* paste object from clipboard to current place
	* TODO an die Besonderheiten der Gruppe (grp_tree) anpassen
	* @access	public
 	*/
	/*function pasteObject()
	{
		global $rbacsystem,$rbacadmin,$tree,$objDefinition;

		// CHECK SOME THINGS
		if ($_SESSION["clipboard"]["cmd"] == "copy")
		{
			// IF CMD WAS 'copy' CALL PRIVATE CLONE METHOD
			$this->cloneObject($_GET["ref_id"]);
			return true;
			exit; // und wech... will never be executed
		}

		// PASTE IF CMD WAS 'cut' (TODO: Could be merged with 'link' routine below in some parts)
		if ($_SESSION["clipboard"]["cmd"] == "cut")
		{
			// TODO:i think this can be substituted by $this->object ????
			$object =& $this->ilias->obj_factory->getInstanceByRefId($_GET["ref_id"]);

			// this loop does all checks
			foreach ($_SESSION["clipboard"]["ref_ids"] as $ref_id)
			{
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($ref_id);

				// CHECK ACCESS
				if (!$rbacsystem->checkAccess('create', $_GET["ref_id"], $obj_data->getType()))
				{
					$no_paste[] = $ref_id;
				}

				// CHECK IF REFERENCE ALREADY EXISTS
				if ($_GET["ref_id"] == $obj_data->getRefId())
				{
					$exists[] = $ref_id;
					break;
				}

				// CHECK IF PASTE OBJECT SHALL BE CHILD OF ITSELF
				// TODO: FUNCTION IST NOT LONGER NEEDED IN THIS WAY. WE ONLY NEED TO CHECK IF
				// THE COMBINATION child/parent ALREADY EXISTS

				//if ($tree->isGrandChild(1,0))
				//if ($tree->isGrandChild($id, $_GET["ref_id"]))
				//{
			//		$is_child[] = $ref_id;
				//}

				// CHECK IF OBJECT IS ALLOWED TO CONTAIN PASTED OBJECT AS SUBOBJECT
				$obj_type = $obj_data->getType();

				if (!in_array($obj_type, array_keys($objDefinition->getSubObjects($object->getType()))))
				{
					$not_allowed_subobject[] = $obj_data->getType();
				}
			}

//////////////////////////
// process checking results

			if (count($exists))
			{
				$this->ilias->raiseError($this->lng->txt("msg_obj_exists"),$this->ilias->error_obj->MESSAGE);
			}

			if (count($is_child))
			{
				$this->ilias->raiseError($this->lng->txt("msg_not_in_itself")." ".implode(',',$is_child),
										 $this->ilias->error_obj->MESSAGE);
			}

			if (count($not_allowed_subobject))
			{
				$this->ilias->raiseError($this->lng->txt("msg_may_not_contain")." ".implode(',',$not_allowed_subobject),
										 $this->ilias->error_obj->MESSAGE);
			}

			if (count($no_paste))
			{
				$this->ilias->raiseError($this->lng->txt("msg_no_perm_paste")." ".
										 implode(',',$no_paste),$this->ilias->error_obj->MESSAGE);
			}
/////////////////////////////////////////
// everything ok: now paste the objects to new location

			foreach($_SESSION["clipboard"]["ref_ids"] as $ref_id)
			{

				// get node data
				$top_node = $tree->getNodeData($ref_id);

				// get subnodes of top nodes
				$subnodes[$ref_id] = $tree->getSubtree($top_node);

				// delete old tree entries
				$tree->deleteTree($top_node);
			}

			// now move all subtrees to new location
			foreach($subnodes as $key => $subnode)
			{
				//first paste top_node....
				$rbacadmin->revokePermission($key);
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($key);
				$obj_data->putInTree($_GET["ref_id"]);
				$obj_data->setPermissions($_GET["ref_id"]);

				//paste the node also into the "grp_tree" table
				//todo
				$groupObj = new ilObjGroup($_GET["ref_id"]);
				$groupObj->insertGroupNode($obj_data->getId(),$obj_data->getRefId(), $groupObj->getId(),$this->grp_tree );

				// ... remove top_node from list....
				array_shift($subnode);

				// ... insert subtree of top_node if any subnodes exist
				if (count($subnode) > 0)
				{
					foreach ($subnode as $node)
					{
						$rbacadmin->revokePermission($node["child"]);
						$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);
						$obj_data->putInTree($node["parent"]);
						$obj_data->setPermissions($node["parent"]);
					}
				}
			}
		} // END IF 'cut & paste'

		// PASTE IF CMD WAS 'linkt' (TODO: Could be merged with 'cut' routine above)
		if ($_SESSION["clipboard"]["cmd"] == "link")
		{
			// TODO:i think this can be substituted by $this->object ????
			$object =& $this->ilias->obj_factory->getInstanceByRefId($_GET["ref_id"]);

			// this loop does all checks
			foreach ($_SESSION["clipboard"]["ref_ids"] as $ref_id)
			{
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($ref_id);

				// CHECK ACCESS
				if (!$rbacsystem->checkAccess('create', $_GET["ref_id"], $obj_data->getType()))
				{
					$no_paste[] = $ref_id;
				}

				// CHECK IF REFERENCE ALREADY EXISTS
				if ($_GET["ref_id"] == $obj_data->getRefId())
				{
					$exists[] = $ref_id;
					break;
				}

				// CHECK IF PASTE OBJECT SHALL BE CHILD OF ITSELF
				// TODO: FUNCTION IST NOT LONGER NEEDED IN THIS WAY. WE ONLY NEED TO CHECK IF
				// THE COMBINATION child/parent ALREADY EXISTS

				//if ($tree->isGrandChild(1,0))
				//if ($tree->isGrandChild($id, $_GET["ref_id"]))
				//{
			//		$is_child[] = $ref_id;
				//}

				// CHECK IF OBJECT IS ALLOWED TO CONTAIN PASTED OBJECT AS SUBOBJECT
				$obj_type = $obj_data->getType();

				if (!in_array($obj_type, array_keys($objDefinition->getSubObjects($object->getType()))))
				{
					$not_allowed_subobject[] = $obj_data->getType();
				}

				//todo
				if( $object->objectExist($this->grp_tree->getTreeId(), $obj_data->getId()))
				{
					$is_already_node[]= $obj_data->getId();
				}

			}

//////////////////////////
// process checking results

			if (count($exists))
			{
				$this->ilias->raiseError($this->lng->txt("msg_obj_exists"),$this->ilias->error_obj->MESSAGE);
			}

			if (count($is_child))
			{
				$this->ilias->raiseError($this->lng->txt("msg_not_in_itself")." ".implode(',',$is_child),
										 $this->ilias->error_obj->MESSAGE);
			}

			if (count($not_allowed_subobject))
			{
				$this->ilias->raiseError($this->lng->txt("msg_may_not_contain")." ".implode(',',$not_allowed_subobject),
										 $this->ilias->error_obj->MESSAGE);
			}

			if (count($no_paste))
			{
				$this->ilias->raiseError($this->lng->txt("msg_no_perm_paste")." ".
										 implode(',',$no_paste),$this->ilias->error_obj->MESSAGE);
			}
			if(count($is_already_node))
			{
				$this->ilias->raiseError("Das gew�lte Objekt existiert bereits unter dem Gruppen Knoten",$this->ilias->error_obj->MESSAGE);
			}
/////////////////////////////////////////
// everything ok: now paste the objects to new location

			foreach($_SESSION["clipboard"]["ref_ids"] as $ref_id)
			{

				// get node data
				$top_node = $tree->getNodeData($ref_id);

				// get subnodes of top nodes
				$subnodes[$ref_id] = $tree->getSubtree($top_node);
			}

			// now move all subtrees to new location
			foreach($subnodes as $key => $subnode)
			{  
				//first paste top_node....
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($key);
				$obj_data->createReference();
				$obj_data->putInTree($_GET["ref_id"]);
				$obj_data->setPermissions($_GET["ref_id"]);
				//var_dump($_POST);var_dump($_GET);
				
				
				//paste the node also into the "grp_tree" table
<<<<<<< class.ilObjGroupGUI.php
				echo ($obj_data->getRefId()." ". $_GET["ref_id"]);
				$this->grp_tree->insertNode($obj_data->getRefId(), $_GET["ref_id"]);
=======
				//todo
<<<<<<< class.ilObjGroupGUI.php
				//baue group_obj
				//include_once("classes/class.ilObjGroup.php");
				//$groupObj = new ilObjGroup($_GET["ref_id"]);
				//$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($_GET["ref_id"]);
				//$groupObj->insertGroupNode($obj_data->getId(),$obj_data->getRefId(), $parent_obj_id,$thisgrp_tree );
				
				//$this->grp_tree->insertNode($obj_data->getRefId(), $_GET["ref_id"]);
>>>>>>> 1.22
				
				
				
				 
=======
				$groupObj = new ilObjGroup($_GET["ref_id"]);
				$groupObj->insertGroupNode($obj_data->getId(),$obj_data->getRefId(), $groupObj->getId(),$this->grp_tree );
>>>>>>> 1.27

				
				// ... remove top_node from list....
				array_shift($subnode);

				// ... insert subtree of top_node if any subnodes exist
				if (count($subnode) > 0)
				{
					foreach ($subnode as $node)
					{
						$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);
						$obj_data->createReference();
						// TODO: $node["parent"] is wrong in case of new reference!!!!
						$obj_data->putInTree($node["parent"]);
						$obj_data->setPermissions($node["parent"]);

						//todo !!!
						//$this->grp_tree->insertNode($obj_data->getRefId(), $node["parent"]);
					
					}
				}
			}
		} // END IF 'link & paste'

		// clear clipboard
		$this->clearObject();
		
		// TODO: sendInfo does not work in this place :-(
		sendInfo($this->lng->txt("msg_changes_ok"),true);
		header("location: adm_object.php?ref_id=".$_GET["ref_id"]);
		exit();
	}*/

	/**
	* display object list
	*
	* @access	public
 	*/
	function displayList()
	{
		global $tree, $rbacsystem;

		require_once "./classes/class.ilTableGUI.php";

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");
		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.obj_tbl_rows.html");

		$num = 0;

		$obj_str = ($this->call_by_reference) ? "" : "&obj_id=".$this->obj_id;
		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$this->ref_id."$obj_str&cmd=gateway&tree_id=".$this->grp_tree->getTreeId()."&tree_table=grp_tree");

		$this->tree_id = $_GET["tree_id"];
		$this->tree_table = $_GET["tree_table"];

		// create table
		$tbl = new ilTableGUI();

		// title & header columns
		$tbl->setTitle($this->object->getTitle(),"icon_".$this->object->getType()."_b.gif",$this->lng->txt("obj_".$this->object->getType()));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));

		foreach ($this->data["cols"] as $val)
		{
			$header_names[] = $this->lng->txt($val);
		}

		$tbl->setHeaderNames($header_names);

		$header_params = array("ref_id" => $this->ref_id);
		$tbl->setHeaderVars($this->data["cols"],$header_params);
		//$tbl->setColumnWidth(array("7%","7%","15%","31%","6%","17%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);

		// display action buttons only if at least 1 object in the list can be manipulated
		// temp. deactivated
		/*if (is_array($this->data["data"][0]))
		{
			foreach ($this->data["ctrl"] as $val)
			{
				if ($this->objDefinition->hasCheckbox($val["type"]))
				{
					// SHOW VALID ACTIONS
					$this->tpl->setVariable("COLUMN_COUNTS",count($this->data["cols"]));
					$this->showActions(true);
					break;
				}
			}
		}*/
		$this->tpl->setVariable("COLUMN_COUNTS",count($this->data["cols"]));
		$this->showActions(true);

		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		//$tbl->disable("footer");

		// render table
		$tbl->render();

		if (is_array($this->data["data"][0]))
		{
			//table cell
			for ($i=0; $i < count($this->data["data"]); $i++)
			{
				$data = $this->data["data"][$i];
				$ctrl = $this->data["ctrl"][$i];

				// color changing
				$css_row = ilUtil::switchColor($i+1,"tblrow1","tblrow2");

				// surpress checkbox for particular object types
				if (!$this->objDefinition->hasCheckbox($ctrl["type"]))
				{
					$this->tpl->touchBlock("empty_cell");
				}
				else
				{
					if ($ctrl["type"] == "usr" or $ctrl["type"] == "role")
					{
						$link_id = $ctrl["obj_id"];
					}
					else
					{
						$link_id = $ctrl["ref_id"];
					}

					$this->tpl->setCurrentBlock("checkbox");
					$this->tpl->setVariable("CHECKBOX_ID", $link_id);
					$this->tpl->setVariable("CSS_ROW", $css_row);
					$this->tpl->parseCurrentBlock();
				}

				$this->tpl->setCurrentBlock("table_cell");
				$this->tpl->setVariable("CELLSTYLE", "tblrow1");
				$this->tpl->parseCurrentBlock();

				foreach ($data as $key => $val)
				{
					//build link
					$link = "adm_object.php?";

					if ($_GET["type"] == "lo" && $key == "type")
					{
						$link = "lo_view.php?";
					}

					$n = 0;

					foreach ($ctrl as $key2 => $val2)
					{
						$link .= $key2."=".$val2;

						if ($n < count($ctrl)-1)
						{
					    	$link .= "&";
							$n++;
						}
					}

					if ($key == "title" || $key == "type")
					{
						$this->tpl->setCurrentBlock("begin_link");
						$this->tpl->setVariable("LINK_TARGET", $link);

						if ($_GET["type"] == "lo" && $key == "type")
						{
							$this->tpl->setVariable("NEW_TARGET", "\" target=\"lo_view\"");
						}

						$this->tpl->parseCurrentBlock();
						$this->tpl->touchBlock("end_link");
					}

					// process clipboard information"
					if (isset($_SESSION["clipboard"]))
					{
						$cmd = $_SESSION["clipboard"]["cmd"];
						$parent = $_SESSION["clipboard"]["parent"];

						foreach ($_SESSION["clipboard"]["ref_ids"] as $clip_id)
						{
							if ($ctrl["ref_id"] == $clip_id)
							{
								if ($cmd == "cut" and $key == "title")
								{
									$val = "<del>".$val."</del>";
								}

								if ($cmd == "copy" and $key == "title")
								{
									$val = "<font color=\"green\">+</font>  ".$val;
								}

								if ($cmd == "link" and $key == "title")
								{
									$val = "<font color=\"black\"><</font> ".$val;
								}
							}
						}
					}

					$this->tpl->setCurrentBlock("text");

					if ($key == "type")
					{
						$val = ilUtil::getImageTagByType($val,$this->tpl->tplPath);
					}

					$this->tpl->setVariable("TEXT_CONTENT", $val);
					$this->tpl->parseCurrentBlock();

					$this->tpl->setCurrentBlock("table_cell");
					$this->tpl->parseCurrentBlock();

				} //foreach

				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->parseCurrentBlock();
			} //for

		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", $num);
			$this->tpl->parseCurrentBlock();
		}
	}




	function getContextPath($a_endnode_id, $a_startnode_id = 0)
	{
		global $tree;

		$path = "";

		$tmpPath = $this->tree->getPathFull($a_endnode_id, $a_startnode_id);

		// count -1, to exclude the forum itself
		for ($i = 0; $i < (count($tmpPath) - 1); $i++)
		{
			if ($path != "")
			{
				$path .= " > ";
			}

			$path .= $tmpPath[$i]["title"];
		}

		return $path;
	}


} // END class.ilObjGroupGUI
?>
