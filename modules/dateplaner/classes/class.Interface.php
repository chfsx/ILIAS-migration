<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source															  |
	|	Dateplaner Modul														  |													
	+-----------------------------------------------------------------------------+
	| Copyright (c) 2004 ILIAS open source & University of Applied Sciences Bremen|
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
* Interface_class
*
* a wrapper class for userdata of ilias to provide access 
* to userinformation for the dateplaner
*
* @author       Frank Gruemmert <gruemmert@feuerwelt.de>    
* @version      $Id$                                    
* @module       class.Interface.php                            
* @modulegroup  dateplaner                    
* @package		dateplaner-core
*/
	
class Interface
{
	
	/**
	* The following Variables do not need to be kept up to date manually.
	* This versions should autodetect the needed values
	* automatically.
	* The needed values are read within the Constructor from "/config/conf.dateplaner.php" into teh module dir.
	* Therefore all Values should be edited in conf.dateplaner.php.
	*/
	
	/** 
	* private copy of the ilias object
	* @ var object
	* @ access private
	*/
	var $ilias;	

	/**
	* ilias-root-directory (including the whole path beginning from system-root!)
	* @ var string
	* @ access private
	*/
	var $iliasRootDir;

	/**
	* ilias module directory (up to the ilias root dir)
	* @ var string
	* @ access private
	*/
	var $modulDir;

	
	/** 
	* relative path of the cscw dir
	* @ var string
	* @ access private
	*/
	var $relCSCWDir;	


	/**
	* name of ilias database
	* @var string
	* @access private
	*/
	var $dbaseIlias;
	
	/**
	* name of cscw database
	* @var string
	* @access private
	*/
	var $dbaseCscw;
	
	/**
	* hostname
	* @var string
	* @access private
	*/
	var $host;
	
	/**
	* username
	* @var string
	* @access private
	*/
	var $mysqlUser;
	
	/**
	* password
	* @var string
	* @access private
	*/
	var $mysqlPass;
	
	/**
	* group_ID for all users
	* @var int
	* @access private
	*/
	var $allUserId;

	/**
	* if the Modul run in a Frame
	* 1 = using Frames (standard in Ilias3 )
	* 0 = not using Frames (standard in Ilias2 )
	* -1 = detect using Frames ( not now implemented in Ilias3 and Ilias2 )
	* @var int
	* @access private
	*/
	var $usingFrames;

	/**
	*	End of manual configured Variables, all the rest will be autodetected.
	*/
	
	/**
	* local copy of user id
	* @var integer
	* @access private
	*/
	var $uId;

	/**
	* local copy of userpreferences
	* @var array
	* @access private
	*/
	var $uPrefs;

	/**
	* local copy of templatepath
	* @var string
	* @access private
	*/	
	var $templPath;

	/**
	* local copy of skinpath
	* @var string
	* @access private
	*/	
	var $skinPath;

	/**
	/**
	* buffer for directory-name
	* @var string
	*/
	var $pBuf;
	
	/**
	/**
	* buffer for directory-name
	* @var string
	*/
	var $GroupIds;

	/**
	* Constructor
	* fetch userspecific data from ilias and store them in local variables
	* @access	public
	*/

	var $sock   = FALSE;


	function Interface($ilias,  $nb="")
	{
		
		global $tpl;
		//include manual edited variables
		include ('.'.DATEPLANER_ROOT_DIR.'/config/conf.dateplaner.php');
    
		/*generate a copy of the ilias object*/
		$this->ilias		= $ilias;
    
		$this->modulDir		= $modulDir;
		$this->relCSCWDir	= $relCSCWDir;	
		
		define("ALLUSERID", $allUserId); /* All USER ID */

		
		//gain access to ilias-variables
		if($dbaseIlias != "-1") {$this->dbaseIlias=$dbaseIlias;}else {$this->dbaseIlias	=$this->ilias->ini->readVariable("db", "name");}
		if($dbaseCscw != "-1")	{$this->dbaseCscw=$dbaseCscw;}	else {$this->dbaseCscw	=$this->ilias->ini->readVariable("db", "name");}
		if($host != "-1")		{$this->host=$host;}			else {$this->host		=$this->ilias->ini->readVariable("db", "host");}
		if($mysqlUser != "-1")	{$this->mysqlUser=$mysqlUser;}	else {$this->mysqlUser	=$this->ilias->ini->readVariable("db", "user");}
		if($mysqlPass != "-1")	{$this->mysqlPass=$mysqlPass;}	else {$this->mysqlPass	=$this->ilias->ini->readVariable("db", "pass");}
    
		$this->sock = @mysql_connect ($this->host, $this->mysqlUser, $this->mysqlPass);
		//connect to database
		if ($this->sock)
		{
			if (mysql_select_db ($this->dbaseCscw, $this->sock) == FALSE)
			{
				mysql_close ($this->sock);
				$this->sock = FALSE;
			}
		}
    
		//gain further access to ilias-variables
		$this->uId					= $ilias->account->getId();
		$this->uPrefs["language"]	= $ilias->account->getPref("language");
		$this->uPrefs["skin"]		= $ilias->account->getPref("skin");
		$this->uPrefs["style"]		= $ilias->account->getPref("style");
		$this->GroupIds				= $this->getGroupIds();


		$this->upText				= "";	//menue string not developed in ilias3

		//$this->tpl		=& $tpl;
		//$this->__showLocator();


	}//end Constructor
	
	/**
	* showLocator($tpl, $lng, $app)
	* genarate the locater for ilias3
	* @access	public
	* @param	
	* @return	integer	
	*/

	function showLocator($tpl, $lng, $app)
	{

		$tpl = new ilTemplate("tpl.locator.html", true, true);
		$tpl->addBlockFile("LOCATOR","locator","tpl.locator.html");
		$tpl->setCurrentBlock("locator_item");
		$tpl->setVariable("LINK_ITEM","./dateplaner.php");
		$tpl->setVariable("LINK_TARGET","bottom");
		$tpl->setVariable("ITEM",$lng->txt("dateplaner"));
		$tpl->parseCurrentBlock();

		$tpl->touchBlock("locator_separator_prefix");
		$tpl->setCurrentBlock("locator_item");
		$tpl->setVariable("LINK_ITEM","./dateplaner.php?app=".$app);
		$tpl->setVariable("LINK_TARGET","bottom");
		$tpl->setVariable("ITEM",$lng->txt("app_".$app));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("locator");
		$tpl->setVariable("TXT_LOCATOR",$lng->txt("locator"));
		$tpl->parseCurrentBlock();
		$locator = '
		<div class="il_Locator" style="position:absolute; top:0px; left: 0px; width=100%; hight = 10px;" >
		<table border="0" cellpadding="0" cellspacing="0" width="100%" >
			<tr><td>'.$tpl->get().'</td>
				<td align="right">
				</td>
			</tr>
		</table>
		</div>';
		
		Return $locator;


	}


	/**
	* read UserID
	* @access	public
	* @param	
	* @return	integer	
	*/
	function getUId()
	{
		return $this->uId;
	}//end function

	/**
	* read current language, the user selected
	* @access	public
	* @param	
	* @return	string		
	*/
	function getLang()
	{
		return $this->uPrefs["language"];
	}//end function
	
	/**
	* read current skin-name, the user selected
	* @access	public
	* @param	
	* @return	string		
	*/	
	function getSkin()
	{
		return $this->uPrefs["skin"];
	}//end function
	
	/**
	* read current style(-sheet)-name, the user selected
	* @access	public
	* @param	
	* @return	string		
	*/
	function getStyle()
	{
		return $this->uPrefs["style"]; 
	}//end function
	
	/**
	* read current style(-sheet)-name including path, the user selected
	* @access	public
	* @param	
	* @return	string		
	*/
	function getStyleFname()
	{
		return ilUtil::getStyleSheetLocation();
	}//end function

	/**
	* read GroupIDs of the current UserID (stub)
	* @access	public
	* @param	
	* @return	array of integers	
	*/
	function getGroupIds()
	{
		$IliasArryGroups	= ilUtil::GetObjectsByOperations ("grp", "read", False,False);
		if($IliasArryGroups[0]!="") {
			$i = 0;
			foreach ($IliasArryGroups as $Row){
				$groupContainer = $Row[ref_id];
				$groups[$i]=$groupContainer ;
			$i++;
			}
		}
		return $groups;	
	}//end function

	/**
	* Returns the Ilias-Databasename
	* @return array
	*/	
	function getIliasDbName()
	{
		return $this->dbaseIlias; 
	}//end function

	/**
	* Returns the CSCW-Databasename
	* @return array
	*/	
	function getCscwDbName()
	{
		return $this->dbaseCscw; 
	}//end function

	/**
	* Returns the mysql-Host for database-access
	* @return array
	*/
	function getMysqlHostName()
	{
		return $this->host; 
	}//end function

	/**
	* Returns the mysql-Username for database-access
	* @return array
	*/
	function getMysqlUserName()
	{
		return $this->mysqlUser; 
	}//end function

	/**
	* Returns the mysql-Password for database-access
	* @return array
	*/
	function getMysqlPass()
	{
		return $this->mysqlPass; 
	}//end function

	/**
	* Returns the userID which stands for 'all Users'
	* @return array
	*/	
	function getAllUserId()
	{
		return $this->allUserId; 
	}//end function

	/**
	* Returns the relative path where cscw is located
	* @return String
	*/
	function getrelCSCWDir()
	{
		return $this->relCSCWDir;	
	}//end function

	/**
	* Returns the string for the menue is the DP runs in no-frames modus
	* @return String
	*/
	function getFrameDec()
	{
		switch($this->usingFrames) {
			case 1 :
				$uptext = "";
				break;
			case 0 :
				$uptext = ereg_replace ('HREF="', 'HREF="../../', $this->upText);
				$uptext = ereg_replace ('SRC="', 'SRC="../../', $uptext);
				$uptext = ereg_replace ('= "images/navbar/', '= "../../images/navbar/', $uptext);
				break;
			case -1:
				// not now implemented in Ilias3 and Ilias2
				break;
			default :
				$uptext = "";
		}
		//$uptext = htmlspecialchars($uptext);
		return $uptext;
	}//end function	

	/**
	* Returns all groups which are owned by the user
	* @param int user_ID
	* @return array
	*/
	function getUserGroups ($userId)
	{
	
		$IliasArryGroups	= ilUtil::GetObjectsByOperations ("grp", "write", False, False);
		if($IliasArryGroups[0]!="") {
	   		$i = 0;
	   		foreach (@$IliasArryGroups as $Row){
	       		$groupContainer = $Row[ref_id];
	    	    $groups[$i][0]=$groupContainer ;
	        	$groups[$i][1]=$Row[title] ;
		        $i++;
			}	    
		}
		return $groups;
	}//end function


	/**
	* Returns all user-ID's which are in the given group ($groupId) except the given $userId
	* @param int group_ID
	* @param int user_ID
	* @return array
	*/
	function getOtherMembers ($groupId, $userId)
	{
		//gain access to ilias-variables
		include_once("./classes/class.ilObjGroup.php");
		$group = new ilObjGroup($groupId);
		$members = $group->getGroupMemberIds($groupId);

		if($members[0] == $userId and count($members) == 1) {
			$members = FALSE;
		}elseif ($userId != "-1") {
			array_unshift ($members, $userId);
			$members = array_unique ($members);
			$members = array_reverse ($members);
			array_pop ($members);
		}
		return $members;
	}//end function


	/**
	* Returns all groups where the user is member in
	* @param int user_ID
	* @return array
	*/
	// 
	function getMemberGroups ($userId)
	{
		$GroupIds				= interface::getGroupIds();
		return $GroupIds;
	}//end function

	/**
	* Fetches the name of a group according to its group_ID from the database
	* @param int group_IDs
	* @return string
	*/
	function getGroupName ($groupId)
	{
			
		$IliasArryGroups	= ilUtil::GetObjectsByOperations ("grp", "read", False,False);

		if($IliasArryGroups[0]!="") {
			$i = 0;
			foreach ($IliasArryGroups as $Row){
				$groupContainer = $Row[ref_id];
				$groups[$i][id]=$groupContainer ;
		        $groups[$i][title]=$Row[title] ;
			$i++;
			}
		}

		if($groups) {
			foreach ($groups as $Row ){
				$groupContainer = $Row[id];
				if ($groupId == $groupContainer ){
					$groupname = $Row[title] ;
				}
        	}
		}
		return $groupname;
 	}//end function
	
	/**
	* Returns number of members in the Group with $group_id
	* @param  $group_id (int)
	* @return int
	*/
	function getNumOfMembers ($group_id)
	{

		//gain access to ilias-variables
		include_once("./classes/class.ilObjGroup.php");
		$group = new ilObjGroup($group_id);
		$members = $group->getGroupMemberIds($group_id);
		return count($members);

	}//end function
	
	/**
	* 	Returns dte Dtateplane DB handler fo database conectivity
	*	@return
	*/
	function getDpDBHandler () {
		
		$dbase_ilias	= $this->getIliasDbName();		//Get all constants from the interface
		$dbase_cscw		= $this->getCscwDbName();		//				/
		$host 			= $this->getMysqlHostName();	//			/
		$user 			= $this->getMysqlUserName();	//		/
		$pass 			= $this->getMysqlPass();		//	/
		$alluser_id		= $this->getAllUserId();		//	
		$dlI 			= @mysql_connect ($host, $user, $pass);	//Connect to database

		if (isset($dlI))
		{
			if (mysql_select_db ($dbase_ilias, $dlI) == false)	//Select the database
			{
				mysql_close ($dlI);
				$dlI = false;
			}
		}

		Return $dlI ;
	}// end func


} // END class.Interface
?>