<?php
/**
* Class ForumObject
*
* @author Wolfgang Merkens <wmerkens@databay.de> 
* @version $Id$
*
* @extends Object
* @package ilias-core
*/

require_once "class.Forum.php";

class ForumObject extends Object
{
	/**
	* Forum object
	* @var		object Forum
	* @access	private
	*/
	var $Forum;
	
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ForumObject($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "frm";
		$this->Object($a_id,$a_call_by_reference);
		
		// TODO: needs to rewrite scripts that are using Forum outside this class
		$this->Forum = new Forum();
	}

	/**
	* saves new object in admin interface
	*
	* @param	integer		obj_id
	* @param	integer		parent_id
	* @param	string		obj_type
	* @param	string		new_obj_type
	* @param	array		title & description
	* @return	integer		new obj_id
	* @access	public
	*/
	//function create($a_obj_id, $a_parent,$a_type, $a_new_type, $a_data)
	function create()
	{
		$newFrm_ID = parent::create();
	}

	/**
	* put forum into tree
	*/
	function putInTree($a_parent)
	{
		global $tree, $rbacadmin;

		// put forum in tree
		parent::putInTree($a_parent);

		// create new forum tree -- DEPRECATED. no extra tree for forum objects!!
		//$tree->addTree($this->id);

		// create a local role-folder
		require_once "class.RoleFolderObject.php";
		$rfoldObj = new RoleFolderObject();
		$rfoldObj->setTitle("Local roles");
		$rfoldObj->setDescription("Role Folder of forum ref_no.".$this->ref_id);
		$rfoldObj->create();
		$rfoldObj->createReference();
		$rfoldObj->putInTree($this->ref_id);
		//$rolF_obj["title"] = "Local roles";
		//$rolF_obj["desc"] = "Role Folder of forum no.".$newFrm_ID;

		//$rolF_ID = parent::saveObject($newFrm_ID, $a_obj_id ,"frm" , "rolf" , $rolF_obj);

		// create moderator role in local role-folder
		require_once "class.RoleObject.php";
		$roleObj = new RoleObject();
		$roleObj->setTitle("moderator_".$this->ref_id);
		$roleObj->setDescription("moderator of forum ref_no.".$this->ref_id);
		$roleObj->create();
		
		//$roleObj->createReference();
		//$roleObj->putInTree($rfoldObj->getRefId());

		//$roleID = $roleObj->saveObject($rolF_ID, $newFrm_ID , $role_data);

		// insert new forum as new topic into frm_data
		//$frm_data = getObject($newFrm_ID);

		$top_data = array(
            "top_frm_fk"   		=> $this->getId(),
			"top_name"   		=> addslashes($this->getTitle()),
            "top_description" 	=> addslashes($this->getDescription()),
            "top_num_posts"     => 0,
            "top_num_threads"   => 0,
            "top_last_post"     => "",
			"top_mods"      	=> $roleObj->getId(),
			"top_usr_id"      	=> $_SESSION["AccountId"],
            "top_date" 			=> date("Y-m-d H:i:s")
        );

		$q = "INSERT INTO frm_data ";
		$q .= "(top_frm_fk,top_name,top_description,top_num_posts,top_num_threads,top_last_post,top_mods,top_date,top_usr_id) ";
		$q .= "VALUES ";
		$q .= "('".$top_data["top_frm_fk"]."','".$top_data["top_name"]."','".$top_data["top_description"]."','".$top_data["top_num_posts"]."','".$top_data["top_num_threads"]."','".$top_data["top_last_post"]."','".$top_data["top_mods"]."','".$top_data["top_date"]."','".$top_data["top_usr_id"]."')";
		$result = $this->ilias->db->query($q);
		//echo "roleID: ".$roleID." owner: ".$frm_data["owner"]."<br>";
		// assign moderator
		$rbacadmin->assignUser($roleObj->getId(), $this->getOwner(), "n");
	}

	/**
	* update forum data
	*
	* @access	public
	*/
	function update()
	{
		if (parent::update())
		{			
			$query = "UPDATE frm_data ".
					 "SET ".
					 "top_name = '".$this->getTitle()."',".
					 "top_description = '".$this->getDescription()."',".
					 "top_update = '".date("Y-m-d H:i:s")."',".
					 "update_user = '".$_SESSION["AccountId"]."' ".
					 "WHERE top_frm_fk = '".$this->getId()."'";
			$res = $this->ilias->db->query($query);
		
			return true;
		}
		return false;
	}
	
	/**
	* delete forum and all its contents	
	* @param	integer	a_obj_id
	* @param	integer	a_parent_id
	* @param	integer	a_tree_id (optional)
	* @access	public
	*/
	function deleteObject($a_obj_id, $a_parent_id, $a_tree_id = 1)
	{		
		global $tree;
		
		// IF THERE IS NO OTHER REFERENCE, DELETE ENTRY IN OBJECT_DATA
		if (countReferencesOfObject($a_obj_id) == 1)
		{
			return parent::deleteObject($a_obj_id);
		}
		
		$this->Forum->setWhereCondition("top_frm_fk = ".$a_obj_id);			
		$topData = $this->Forum->getOneTopic();	
		
		$resThreads = $this->Forum->getThreadList($topData["top_pk"]);	
		
		while ($thrData = $resThreads->fetchRow(DB_FETCHMODE_ASSOC))
		{
			// delete tree
			$query = "DELETE FROM frm_posts_tree WHERE thr_fk = '".$thrData["thr_pk"]."'";
			$this->ilias->db->query($query);
			
			// delete posts
			$query = "DELETE FROM frm_posts WHERE pos_thr_fk = '".$thrData["thr_pk"]."'";
			$this->ilias->db->query($query);
			
			// delete threads
			$query = "DELETE FROM frm_threads WHERE thr_pk = '".$thrData["thr_pk"]."'";
			$this->ilias->db->query($query);
		}
		// delete topic
		$query = "DELETE FROM frm_data WHERE top_frm_fk = '".$a_obj_id."'";
		$this->ilias->db->query($query);
		
		// delete forum-object in tree
		$query = "DELETE FROM tree WHERE tree = '".$a_obj_id."'";		
		$this->ilias->db->query($query);
		
		return parent::deleteObject($a_obj_id, $a_parent_id, $a_tree_id);
	}

	/**
	* copy all entries of a forum object !!! IT MUST RETURN THE NEW OBJECT ID !!
	* @param	integer	a_obj_id
	* @param	integer	a_parent
	* @param	integer	a_dest_id
	* @param	integer	a_dest_parent
	* @access	public
	* @return	integer	new object id
	*/
	function cloneObject($a_obj_id,$a_parent,$a_dest_id,$a_dest_parent)
	{		
		$new_obj_id = parent::cloneObject($a_obj_id,$a_parent,$a_dest_id,$a_dest_parent);
		
		// get forum data
		$this->Forum->setWhereCondition("top_frm_fk = ".$a_obj_id);			
		$topData = $this->Forum->getOneTopic();	
		
		// insert new forum as a copy 
		$q = "INSERT INTO frm_data ";
		$q .= "(top_frm_fk,top_name,top_description,top_num_posts,top_num_threads,top_last_post,top_mods,top_date,top_usr_id,visits,top_update,update_user) ";
		$q .= "VALUES ";
		$q .= "('".$new_obj_id."','".addslashes($topData["top_name"])."','".addslashes($topData["top_description"])."','".$topData["top_num_posts"]."','".$topData["top_num_threads"]."','".$topData["top_last_post"]."','".$topData["top_mods"]."','".$topData["top_date"]."','".$topData["top_usr_id"]."','".$topData["visits"]."','".$topData["top_update"]."','".$topData["update_user"]."')";
		$this->ilias->db->query($q);
		
		// get last insert id and return it
		$query = "SELECT LAST_INSERT_ID()";
		$res = $this->ilias->db->query($query);
		$lastInsert = $res->fetchRow();
		$new_top_pk = $lastInsert[0];
		
		// get threads from old forum and insert them as copys
		$resThreads = $this->Forum->getThreadList($topData["top_pk"]);	
		
		while ($thrData = $resThreads->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$q = "INSERT INTO frm_threads ";
			$q .= "(thr_top_fk,thr_usr_id,thr_subject,thr_date,thr_update,thr_num_posts,thr_last_post,visits) ";
			$q .= "VALUES ";
			$q .= "('".$new_top_pk."','".$thrData["thr_usr_id"]."','".addslashes($thrData["thr_subject"])."','".$thrData["thr_date"]."','".$thrData["thr_update"]."','".$thrData["thr_num_posts"]."','".$thrData["thr_last_post"]."','".$thrData["visits"]."')";
			$this->ilias->db->query($q);
			
			// get last insert id and return it
			$query = "SELECT LAST_INSERT_ID()";
			$res = $this->ilias->db->query($query);
			$lastInsert = $res->fetchRow();
			$new_thr_pk = $lastInsert[0];
						
			// get posts from old thread and insert them as copys
			$resPosts = $this->Forum->getPostList($topData["top_pk"], $thrData["thr_pk"]);
			
			while ($posData = $resPosts->fetchRow(DB_FETCHMODE_ASSOC))
			{
				$q2 = "INSERT INTO frm_posts ";
				$q2 .= "(pos_top_fk,pos_thr_fk,pos_usr_id,pos_message,pos_date,pos_update) ";
				$q2 .= "VALUES ";
				$q2 .= "('".$new_top_pk."','".$new_thr_pk."','".$posData["pos_usr_id"]."','".addslashes($posData["pos_message"])."','".$posData["pos_date"]."','".$posData["pos_update"]."')";
				$this->ilias->db->query($q2);
				
				// get last insert id and return it
				$query2 = "SELECT LAST_INSERT_ID()";
				$res2 = $this->ilias->db->query($query2);
				$lastInsert2 = $res2->fetchRow();
				$new_pos_pk = $lastInsert2[0];	
				
				// get tree data from old post and insert copy
			    $q3 = "SELECT * FROM frm_posts_tree ";
				$q3 .= "WHERE pos_fk = '".$posData["pos_pk"]."' ";	   
				$q3 .= "AND thr_fk = '".$thrData["thr_pk"]."'";
				$treeData = $this->ilias->db->getRow($q3, DB_FETCHMODE_ASSOC);
								
				$q4 = "INSERT INTO frm_posts_tree (thr_fk,pos_fk,parent_pos,lft,rgt,depth,date) ";
				$q4 .= "VALUES ";
				$q4 .= "('".$new_thr_pk."','".$new_pos_pk."','".$treeData["parent_pos"]."','".$treeData["lft"]."','".$treeData["rgt"]."','".$treeData["depth"]."','".$treeData["date"]."')";
				$this->ilias->db->query($q4);
			}
		}

		return $new_obj_id;
	}
} // END class.ForumObject
?>
