<?php
/**
 * class RbacSystem
 * system function like checkAccess, addActiveRole ...
 * @author Stefan Meyer <smeyer@databay.de> 
 * $Id$ 
 * 
 */
class RbacSystem
{
    var $db; // Database Handle

    var $Errno = 0; 
    var $Error = "";

// PUBLIC METHODS
    function RbacSystem(&$dbhandle)
    {
        $this->db =& $dbhandle;
    }
// @access public
// @params void
// @return type String
    function getErrorMessage()
    {
        return $this->Error;
    }
// @access public
// @params 
// @return 
    function createSession()
    {
    }
// @access public
// @params 
// @return 
    function deleteSession()
    {
    }
// @access public
// @params 
// @return 
    function addActiveRole()
    {
    }
// @access public
// @params 
// @return 
    function dropActiveRole()
    {
    }
/**	
 * @access public
 * @params ObjectId, das abzufragende Recht
 * @return true false
 */
    function checkAccess($Aoperation,$a_type = '')
    {
		global $ilias;
		global $tree;

		$ops = array();

		$rbacadmin = new RbacAdminH($this->db);
		$rbacreview = new RbacReviewH($this->db);

		// Abfrage der ops_id der gewŁnschten Operation
		$query = "SELECT ops_id FROM rbac_operations ".
			"WHERE operation ='".$Aoperation."'";

		
		$res = $this->db->query($query);
		
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			//echo $row->ops_id."<br>";
			$ops_id = $row->ops_id;
		}
		// Case 'create': naturally there is no rbac_pa entry
		// => looking for the next template and compare operation with template permission
		if($Aoperation == 'create')
		{
			$obj = new Object($ilias);
			$path_ids = $tree->showPathId($_GET["obj_id"],$obj->ROOT_FOLDER_ID);
			array_unshift($path_ids,$obj->SYSTEM_FOLDER_ID);
			$parent_roles = $rbacadmin->getParentRoles($path_ids);
			foreach($parent_roles as $par_rol)
			{
				if(in_array($par_rol["obj_id"],$_SESSION["RoleId"]))
				{
					$ops = $rbacreview->getOperations($par_rol["obj_id"],$a_type,$par_rol["parent"]);
					if(in_array($ops_id,$ops))
					{
						return true;
					}
				}
			}
			return false;
		} // END CASE 'create'

		// Um nur eine Abfrage zu haben
		$in = " IN ('";
		$in .= implode("','",$_SESSION["RoleId"]);
		$in .= "')";

		$query = "SELECT * FROM rbac_pa ".
			"WHERE rol_id ".$in." ".
			"AND obj_id = '".$_GET["obj_id"]."' ".
			"AND set_id = '".$_GET["parent"]."'";
		
		$res = $this->db->query($query);
		
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ops = array_merge($ops,unserialize(stripslashes($row->ops_id)));
		}
		return in_array($ops_id,$ops);
    }
/**
 * @access public
 * @params ObjectId,RoleIds, das abzufragende Recht
 * @return true false
 */
	function checkPermission($Aobj_id,$Arol_id,$Aoperation,$Aset_id="")
	{
		$ops = array();

		// Abfrage der ops_id der gewŁnschten Operation
		$query = "SELECT ops_id FROM rbac_operations ".
			"WHERE operation ='".$Aoperation."'";

		
		$res = $this->db->query($query);
		
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			//echo $row->ops_id."<br>";
			$ops_id = $row->ops_id;
		}
	
		// ABFRAGE DER OPS_ID
		if(!$Aset_id)
		{
			$and = "";
		}
		else
		{
			$and = " AND set_id = '".$Aset_id."'";
		}
		
		$query = "SELECT * FROM rbac_pa ".
			"WHERE rol_id = '".$Arol_id."' ".
			"AND obj_id = '".$Aobj_id."' ".
			$and;
		
		$res = $this->db->query($query);
		
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ops = array_merge($ops,unserialize(stripslashes($row->ops_id)));
		}
		
		return in_array($ops_id,$ops);
	}
} // END CLASS RbacSystem
?>