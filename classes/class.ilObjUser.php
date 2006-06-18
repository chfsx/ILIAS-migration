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

define ("IL_PASSWD_PLAIN", "plain");
define ("IL_PASSWD_MD5", "md5");			// ILIAS 3 Password
define ("IL_PASSWD_CRYPT", "crypt");		// ILIAS 2 Password


require_once "classes/class.ilObject.php";

/**
* user class for ilias
*
* @author	Sascha Hofmann <saschahofmann@gmx.de>
* @author	Stefan Meyer <smeyer@databay.de>
* @author	Peter Gabriel <pgabriel@databay.de>
* @version	$Id$
* @package	ilias-core
*/
class ilObjUser extends ilObject
{
	/**
	* all user related data in single vars
	* @access	public
	*/
	// personal data

	var $login;		// username in system

	var $passwd;	// password encoded in the format specified by $passwd_type
	var $passwd_type;
					// specifies the password format. 
					// value: IL_PASSWD_PLAIN, IL_PASSWD_MD5 or IL_PASSWD_CRYPT.

					// Differences between password format in class ilObjUser and
					// in table usr_data:
					// Class ilObjUser supports three different password types 
					// (plain, MD5 and CRYPT) and it uses the variables $passwd 
					// and $passwd_type to store them.
					// Table usr_data supports only two different password types
					// (MD5 and CRYPT) and it uses the columns "passwd" and 
					// "il2passwd" to store them.
					// The conversion between these two storage layouts is done 
					// in the methods that perform SQL statements. All other 
					// methods work exclusively with the $passwd and $passwd_type 
					// variables.

	var $gender;	// 'm' or 'f'
	var $utitle;	// user title (keep in mind, that we derive $title from object also!)
	var $firstname;
	var $lastname;
	var $fullname;	// title + firstname + lastname in one string
	//var $archive_dir = "./image";  // point to image file (should be flexible)
 	// address data
	var $institution;
	var $department;
	var $street;
	var $city;
	var $zipcode;
	var $country;
	var $phone_office;
	var $phone_home;
	var $phone_mobile;
	var $fax;
	var $email;
	var $hobby;
	var $matriculation;
	var $referral_comment;
	var $approve_date;
	var $active;
	//var $ilinc_id; // unique Id for netucate ilinc service
	var $client_ip; // client ip to check before login
	var $auth_mode; // authentication mode

	var $user_defined_data = array();

	/**
	* Contains variable Userdata (Prefs, Settings)
	* @var		array
	* @access	public
	*/
	var $prefs;

	/**
	* Contains template set
	* @var		string
	* @access	public
	*/
	var $skin;


	/**
	* default role
	* @var		string
	* @access	private
	*/
	var $default_role;

	/**
	* ilias object
	* @var object ilias
	* @access private
	*/
	var $ilias;


	/**
	* Constructor
	* @access	public
	* @param	integer		user_id
	*/
	function ilObjUser($a_user_id = 0, $a_call_by_reference = false)
	{
		global $ilias,$ilDB;

		// init variables
		$this->ilias =& $ilias;
		$this->db =& $ilDB;

		$this->type = "usr";
		$this->ilObject($a_user_id, $a_call_by_reference);
		$this->auth_mode = "default";
		$this->passwd_type = IL_PASSWD_PLAIN;

		// for gender selection. don't change this
		/*$this->gender = array(
							  'm'    => "salutation_m",
							  'f'    => "salutation_f"
							  );*/
		if (!empty($a_user_id))
		{
			$this->setId($a_user_id);
			$this->read();
		}
		else
		{
			// TODO: all code in else-structure doesn't belongs in class user !!!
			//load default data
			$this->prefs = array();
			//language
			$this->prefs["language"] = $this->ilias->ini->readVariable("language","default");

			//skin and pda support
			$this->skin = $this->ilias->ini->readVariable("layout","skin");

			$this->prefs["skin"] = $this->skin;
			$this->prefs["show_users_online"] = "y";

			//style (css)
		 	$this->prefs["style"] = $this->ilias->ini->readVariable("layout","style");
		}
	}

	/**
	* loads a record "user" from database
	* @access private
	*/
	function read()
	{
		global $ilErr;

		// TODO: fetching default role should be done in rbacadmin
		$q = "SELECT * FROM usr_data ".
			 "LEFT JOIN rbac_ua ON usr_data.usr_id=rbac_ua.usr_id ".
			 "WHERE usr_data.usr_id='".$this->id."'";
		$r = $this->ilias->db->query($q);

		if ($r->numRows() > 0)
		{
			$data = $r->fetchRow(DB_FETCHMODE_ASSOC);

			// convert password storage layout used by table usr_data into
			// storage layout used by class ilObjUser
			if ($data["passwd"] == "" && $data["i2passwd"] != "")
			{
				$data["passwd_type"] = IL_PASSWD_CRYPT;
				$data["passwd"] = $data["i2passwd"];
			}
			else 
			{
				$data["passwd_type"] = IL_PASSWD_MD5;
				//$data["passwd"] = $data["passwd"]; (implicit)
			}
			unset($data["i2passw"]);


			// fill member vars in one shot
			$this->assignData($data);

			//get userpreferences from usr_pref table
			$this->readPrefs();

			//set language to default if not set
			if ($this->prefs["language"] == "")
			{
				$this->prefs["language"] = $this->oldPrefs["language"];
			}

			//check skin-setting
			if ($this->prefs["skin"] == "" || file_exists($this->ilias->tplPath."/".$this->prefs["skin"]) == false)
			{
				$this->prefs["skin"] = $this->oldPrefs["skin"];
			}
			
			$this->skin = $this->prefs["skin"];

			//check style-setting (skins could have more than one stylesheet
			if ($this->prefs["style"] == "" || !file_exists($this->ilias->tplPath."/".$this->skin."/".$this->prefs["style"].".css"))
			{
				//load default (css)
		 		$this->prefs["style"] = $this->ilias->ini->readVariable("layout","style");
			}
			
			if (empty($this->prefs["hits_per_page"]))
			{
				$this->prefs["hits_per_page"] = 10;
			}

		}
		else
		{
			$ilErr->raiseError("<b>Error: There is no dataset with id ".
							   $this->id."!</b><br />class: ".get_class($this)."<br />Script: ".__FILE__.
							   "<br />Line: ".__LINE__, $ilErr->FATAL);
		}

		$this->readUserDefinedFields();

		parent::read();
	}

	/**
	* loads a record "user" from array
	* @access	public
	* @param	array		userdata
	*/
	function assignData($a_data)
	{
		global $ilErr;

		// basic personal data
		$this->setLogin($a_data["login"]);
		if (! $a_data["passwd_type"])
		{
			 $ilErr->raiseError("<b>Error: passwd_type missing in function assignData(). ".
								$this->id."!</b><br />class: ".get_class($this)."<br />Script: "
								.__FILE__."<br />Line: ".__LINE__, $ilErr->FATAL);
		}

		if ($a_data["passwd"] != "********")
		{
			$this->setPasswd($a_data["passwd"], $a_data["passwd_type"]);
		}

		$this->setGender($a_data["gender"]);
		$this->setUTitle($a_data["title"]);
		$this->setFirstname($a_data["firstname"]);
		$this->setLastname($a_data["lastname"]);
		$this->setFullname();

		// address data
		$this->setInstitution($a_data["institution"]);
		$this->setDepartment($a_data["department"]);
		$this->setStreet($a_data["street"]);
		$this->setCity($a_data["city"]);
		$this->setZipcode($a_data["zipcode"]);
		$this->setCountry($a_data["country"]);
		$this->setPhoneOffice($a_data["phone_office"]);
		$this->setPhoneHome($a_data["phone_home"]);
		$this->setPhoneMobile($a_data["phone_mobile"]);
		$this->setFax($a_data["fax"]);
		$this->setMatriculation($a_data["matriculation"]);
		$this->setEmail($a_data["email"]);
		$this->setHobby($a_data["hobby"]);
		$this->setClientIP($a_data["client_ip"]);

		// system data
		$this->setLastLogin($a_data["last_login"]);
		$this->setLastUpdate($a_data["last_update"]);
		$this->create_date	= $a_data["create_date"];
        $this->setComment($a_data["referral_comment"]);
        $this->approve_date = $a_data["approve_date"];
        $this->active = $a_data["active"];
		$this->accept_date = $a_data["agree_date"];

        // time limitation
        $this->setTimeLimitOwner($a_data["time_limit_owner"]);
        $this->setTimeLimitUnlimited($a_data["time_limit_unlimited"]);
        $this->setTimeLimitFrom($a_data["time_limit_from"]);
        $this->setTimeLimitUntil($a_data["time_limit_until"]);
		$this->setTimeLimitMessage($a_data['time_limit_message']);
		
		// user profile incomplete?
		$this->setProfileIncomplete($a_data["profile_incomplete"]);

		//iLinc
		//$this->setiLincData($a_data['ilinc_id'],$a_data['ilinc_login'],$a_data['ilinc_passwd']);
		
		//authentication
		$this->setAuthMode($a_data['auth_mode']);
		$this->setExternalAccount($a_data['ext_account']);
	}

	/**
	* TODO: drop fields last_update & create_date. redundant data in object_data!
	* saves a new record "user" to database
	* @access	public
	* @param	boolean	user data from formular (addSlashes) or not (prepareDBString)
	*/
	function saveAsNew($a_from_formular = true)
	{
		global $ilErr;

		switch ($this->passwd_type)
		{
			case IL_PASSWD_PLAIN:
				$pw_field = "passwd";
				$pw_value = md5($this->passwd);
				break;

			case IL_PASSWD_MD5:
				$pw_field = "passwd";
				$pw_value = $this->passwd;
				break;

			case IL_PASSWD_CRYPT:
				$pw_field = "i2passwd";
				$pw_value = $this->passwd;
				break;

			default :
				 $ilErr->raiseError("<b>Error: passwd_type missing in function saveAsNew. ".
									$this->id."!</b><br />class: ".get_class($this)."<br />Script: ".__FILE__.
									"<br />Line: ".__LINE__, $ilErr->FATAL);
		}

		if ($a_from_formular)
		{
            $q = "INSERT INTO usr_data "
                . "(usr_id,login,".$pw_field.",firstname,lastname,title,gender,"
                . "email,hobby,institution,department,street,city,zipcode,country,"
                . "phone_office,phone_home,phone_mobile,fax,last_login,last_update,create_date,"
                . "referral_comment,matriculation,client_ip, approve_date,active,"
                . "time_limit_unlimited,time_limit_until,time_limit_from,time_limit_owner,auth_mode,ext_account,profile_incomplete) "
                . "VALUES "
                . "('".$this->id."','".$this->login."','".$pw_value."', "
                . "'".ilUtil::addSlashes($this->firstname)."','".ilUtil::addSlashes($this->lastname)."', "
                . "'".ilUtil::addSlashes($this->utitle)."','".ilUtil::addSlashes($this->gender)."', "
                . "'".ilUtil::addSlashes($this->email)."','".ilUtil::addSlashes($this->hobby)."', "
                . "'".ilUtil::addSlashes($this->institution)."','".ilUtil::addSlashes($this->department)."', "
                . "'".ilUtil::addSlashes($this->street)."', "
                . "'".ilUtil::addSlashes($this->city)."','".ilUtil::addSlashes($this->zipcode)."','".
				ilUtil::addSlashes($this->country)."', "
                . "'".ilUtil::addSlashes($this->phone_office)."','".ilUtil::addSlashes($this->phone_home)."', "
                . "'".ilUtil::addSlashes($this->phone_mobile)."','".ilUtil::addSlashes($this->fax)."', 0, now(), now(), "
                . "'".ilUtil::addSlashes($this->referral_comment)."', '". ilUtil::addSlashes($this->matriculation) . "', '".
				ilUtil::addSlashes($this->client_ip) . "', '" .$this->approve_date."', '".$this->active."', "
                . "'".$this->getTimeLimitUnlimited()."','" . $this->getTimeLimitUntil()."','".$this->getTimeLimitFrom()."','".
				$this->getTimeLimitOwner()."', "
                . "'".$this->getAuthMode()."', "
				. "'".$this->getExternalAccount()."', "
				. "'".$this->getProfileIncomplete()."')";
		}
		else
		{
            $q = "INSERT INTO usr_data ".
                "(usr_id,login,".$pw_field.",firstname,lastname,title,gender,"
                . "email,hobby,institution,department,street,city,zipcode,country,"
                . "phone_office,phone_home,phone_mobile,fax,last_login,last_update,create_date,"
                . "referral_comment,matriculation,client_ip, approve_date,active,"
                . "time_limit_unlimited,time_limit_until,time_limit_from,time_limit_owner,auth_mode,ext_account,profile_incomplete) "
                . "VALUES "
                . "('".$this->id."','".$this->login."','".$pw_value."', "
                . "'".ilUtil::prepareDBString($this->firstname)."','".ilUtil::prepareDBString($this->lastname)."', "
                . "'".ilUtil::prepareDBString($this->utitle)."','".ilUtil::prepareDBString($this->gender)."', "
                . "'".ilUtil::prepareDBString($this->email)."','".ilUtil::prepareDBString($this->hobby)."', "
                . "'".ilUtil::prepareDBString($this->institution)."','".ilUtil::prepareDBString($this->department)."', "
                . "'".ilUtil::prepareDBString($this->street)."', "
                . "'".ilUtil::prepareDBString($this->city)."','".ilUtil::prepareDBString($this->zipcode)."','".
				ilUtil::prepareDBString($this->country)."', "
                . "'".ilUtil::prepareDBString($this->phone_office)."','".ilUtil::prepareDBString($this->phone_home)."', "
                . "'".ilUtil::prepareDBString($this->phone_mobile)."','".ilUtil::prepareDBString($this->fax)."', 0, now(), now(), "
                . "'".ilUtil::prepareDBString($this->referral_comment)."', '".ilUtil::prepareDBString($this->matriculation)."', '".
				ilUtil::prepareDBString($this->client_ip)."', '".$this->approve_date."','".$this->active."', "
                . "'".$this->getTimeLimitUnlimited()."','".$this->getTimeLimitUntil()."','".$this->getTimeLimitFrom()."','".
				$this->getTimeLimitOwner()."',"
				."'".$this->getAuthMode()."', "
				."'".$this->getExternalAccount()."', "
				."'".$this->getProfileIncomplete()."'"
                . ")";
		}

		$this->ilias->db->query($q);

		// add new entry in usr_defined_data
		$this->addUserDefinedFieldEntry();
		// ... and update
		$this->updateUserDefinedFields();

		// CREATE ENTRIES FOR MAIL BOX
		include_once ("classes/class.ilMailbox.php");
		$mbox = new ilMailbox($this->id);
		$mbox->createDefaultFolder();

		include_once "classes/class.ilMailOptions.php";
		$mail_options = new ilMailOptions($this->id);
		$mail_options->createMailOptionsEntry();

		// create personal bookmark folder tree
		include_once "classes/class.ilBookmarkFolder.php";
		$bmf = new ilBookmarkFolder(0, $this->id);
		$bmf->createNewBookmarkTree();

	}

	/**
	* updates a record "user" and write it into database
	* @access	public
	*/
	function update()
	{
		global $ilErr, $ilDB;

		//$this->id = $this->data["Id"];

        $this->syncActive();

		$pw_udpate = '';
		switch ($this->passwd_type)
		{
			case IL_PASSWD_PLAIN:
				$pw_update = "i2passwd='', passwd='".md5($this->passwd)."'";
				break;

			case IL_PASSWD_MD5:
				$pw_update = "i2passwd='', passwd='".$this->passwd."'";
				break;

			case IL_PASSWD_CRYPT:
				$pw_update = "passwd='', i2passwd='".$this->passwd."'";
				break;

			default :
				$ilErr->raiseError("<b>Error: passwd_type missing in function update()".$this->id."!</b><br />class: ".
								   get_class($this)."<br />Script: ".__FILE__."<br />Line: ".__LINE__, $ilErr->FATAL);
		}
		$q = "UPDATE usr_data SET ".
            "gender='".$this->gender."', ".
            "title='".ilUtil::prepareDBString($this->utitle)."', ".
            "firstname='".ilUtil::prepareDBString($this->firstname)."', ".
            "lastname='".ilUtil::prepareDBString($this->lastname)."', ".
            "email='".ilUtil::prepareDBString($this->email)."', ".
            "hobby='".ilUtil::prepareDBString($this->hobby)."', ".
            "institution='".ilUtil::prepareDBString($this->institution)."', ".
            "department='".ilUtil::prepareDBString($this->department)."', ".
            "street='".ilUtil::prepareDBString($this->street)."', ".
            "city='".ilUtil::prepareDBString($this->city)."', ".
            "zipcode='".ilUtil::prepareDBString($this->zipcode)."', ".
            "country='".ilUtil::prepareDBString($this->country)."', ".
            "phone_office='".ilUtil::prepareDBString($this->phone_office)."', ".
            "phone_home='".ilUtil::prepareDBString($this->phone_home)."', ".
            "phone_mobile='".ilUtil::prepareDBString($this->phone_mobile)."', ".
            "fax='".ilUtil::prepareDBString($this->fax)."', ".
            "referral_comment='".ilUtil::prepareDBString($this->referral_comment)."', ".
            "matriculation='".ilUtil::prepareDBString($this->matriculation)."', ".
            "client_ip='".ilUtil::prepareDBString($this->client_ip)."', ".
            "approve_date='".ilUtil::prepareDBString($this->approve_date)."', ".
            "active='".ilUtil::prepareDBString($this->active)."', ".
            "time_limit_owner='".ilUtil::prepareDBString($this->getTimeLimitOwner())."', ".
            "time_limit_unlimited='".ilUtil::prepareDBString($this->getTimeLimitUnlimited())."', ".
            "time_limit_from='".ilUtil::prepareDBString($this->getTimeLimitFrom())."', ".
            "time_limit_until='".ilUtil::prepareDBString($this->getTimeLimitUntil())."', ".
            "time_limit_message='".$this->getTimeLimitMessage()."', ".
			"profile_incomplete = ".$ilDB->quote($this->getProfileIncomplete()).", ".
            "auth_mode='".ilUtil::prepareDBString($this->getAuthMode())."', ".
			"ext_account='".ilUtil::prepareDBString($this->getExternalAccount())."', ".
			$pw_update.", ".
            "last_update=now() ".
		//	"ilinc_id='".ilUtil::prepareDBString($this->ilinc_id)."', ".
		//	"ilinc_login='".ilUtil::prepareDBString($this->ilinc_login)."', ".
		//	"ilinc_passwd='".ilUtil::prepareDBString($this->ilinc_passwd)."' ".
            "WHERE usr_id='".$this->id."'";

		$this->ilias->db->query($q);
		$this->writePrefs();

		// update user defined fields
		$this->updateUserDefinedFields();
		

		parent::update();
        parent::updateOwner();

		$this->read();

		return true;
	}
		
	/**
	* write accept date of user agreement to db
	*/
	function writeAccepted()
	{
		global $ilDB;
		
		$q = "UPDATE usr_data SET agree_date = now()".
			 "WHERE usr_id = ".$ilDB->quote($this->getId());
		$ilDB->query($q);

	}

	function _lookupEmail($a_user_id)
	{
		global $ilDB;

		$query = "SELECT email FROM usr_data WHERE usr_id = '".(int) $a_user_id."'";
		$res = $ilDB->query($query);
		
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->email;
		}
		return false;
	}


	/**
	* lookup user name
	*/
	function _lookupName($a_user_id)
	{
		global $ilDB;

		$q = "SELECT firstname, lastname, title FROM usr_data".
			" WHERE usr_id =".$ilDB->quote($a_user_id);
		$user_set = $ilDB->query($q);
		$user_rec = $user_set->fetchRow(DB_FETCHMODE_ASSOC);
		return array("user_id" => $a_user_id,
			"firstname" => $user_rec["firstname"],
			"lastname" => $user_rec["lastname"],
			"title" => $user_rec["title"]);
	}

	/**
	* lookup login
	*/
	function _lookupLogin($a_user_id)
	{
		global $ilDB;

		$q = "SELECT login FROM usr_data".
			" WHERE usr_id =".$ilDB->quote($a_user_id);
		$user_set = $ilDB->query($q);
		$user_rec = $user_set->fetchRow(DB_FETCHMODE_ASSOC);
		return $user_rec["login"];
	}
	
	/**
	* lookup id by login
	*/
	function _lookupId($a_user_str)
	{
		global $ilDB;

		$q = "SELECT usr_id FROM usr_data".
			" WHERE login =".$ilDB->quote($a_user_str);
		$user_set = $ilDB->query($q);
		$user_rec = $user_set->fetchRow(DB_FETCHMODE_ASSOC);
		return $user_rec["usr_id"];
	}


	/**
	* updates the login data of a "user"
	* // TODO set date with now() should be enough
	* @access	public
	*/
	function refreshLogin()
	{
		$q = "UPDATE usr_data SET ".
			 "last_login = now() ".
			 "WHERE usr_id = '".$this->id."'";

		$this->ilias->db->query($q);
	}

	/**
	* replaces password with new md5 hash
	* @param	string	new password as md5
	* @return	boolean	true on success; otherwise false
	* @access	public
	*/
	function replacePassword($new_md5)
	{
		$this->passwd_type = IL_PASSWD_MD5;
		$this->passwd = $new_md5;

		$q = "UPDATE usr_data SET ".
			 "passwd='".$this->passwd."' ".
			 "WHERE usr_id='".$this->id."'";

		$this->ilias->db->query($q);

		return true;
	}

	/**
	* updates password
	* @param	string	old password as plaintext
	* @param	string	new password1 as plaintext
	* @param	string	new password2 as plaintext
	* @return	boolean	true on success; otherwise false
	* @access	public
	*/
	function updatePassword($a_old, $a_new1, $a_new2)
	{
		if (func_num_args() != 3)
		{
			return false;
		}

		if (!isset($a_old) or !isset($a_new1) or !isset($a_new2))
		{
			return false;
		}

		if ($a_new1 != $a_new2)
		{
			return false;
		}

		// is catched by isset() ???
		if ($a_new1 == "" || $a_old == "")
		{
			return false;
		}

		//check old password
		switch ($this->passwd_type)
		{
			case IL_PASSWD_PLAIN:
				if ($a_old != $this->passwd)
				{
					return false;
				}
				break;

			case IL_PASSWD_MD5:
				if (md5($a_old) != $this->passwd)
				{
					return false;
				}
				break;

			case IL_PASSWD_CRYPT:
				if (_makeIlias2Password($a_old) != $this->passwd)
				{
					return false;
				}
				break;
		}

		//update password
		$this->passwd = md5($a_new1);
		$this->passwd_type = IL_PASSWD_MD5;

		$q = "UPDATE usr_data SET ".
			 "passwd='".$this->passwd."' ".
			 "WHERE usr_id='".$this->id."'";
		$this->ilias->db->query($q);

		return true;
	}

	/**
	* reset password
	* @param	string	new password1 as plaintext
	* @param	string	new password2 as plaintext
	* @return	boolean	true on success; otherwise false
	* @access	public
	*/
	function resetPassword($a_new1, $a_new2)
	{
		if (func_num_args() != 2)
		{
			return false;
		}

		if (!isset($a_new1) or !isset($a_new2))
		{
			return false;
		}

		if ($a_new1 != $a_new2)
		{
			return false;
		}

		//update password
		$this->passwd = md5($a_new1);
		$this->passwd_type = IL_PASSWD_MD5;

		$q = "UPDATE usr_data SET ".
			 "passwd='".$this->passwd."' ".
			 "WHERE usr_id='".$this->id."'";
		$this->ilias->db->query($q);

		return true;
	}

	/**
	* get encrypted Ilias 2 password (needed for imported ilias 2 users)
	*/
	function _makeIlias2Password($a_passwd)
	{
		return (crypt($a_passwd,substr($a_passwd,0,2)));
	}

	/**
	* check if user has ilias 2 password (imported user)
	*/
	function _lookupHasIlias2Password($a_user_login)
	{
		global $ilias;

		$q = "SELECT i2passwd FROM usr_data ".
			 "WHERE login = '".$a_user_login."'";
		$user_set = $ilias->db->query($q);

		if ($user_rec = $user_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if ($user_rec["i2passwd"] != "")
			{
				return true;
			}
		}

		return false;
	}

	function _switchToIlias3Password($a_user, $a_pw)
	{
		global $ilias;

		$q = "SELECT i2passwd FROM usr_data ".
			 "WHERE login = '".$a_user."'";
		$user_set = $ilias->db->query($q);

		if ($user_rec = $user_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if ($user_rec["i2passwd"] == ilObjUser::_makeIlias2Password($a_pw))
			{
				$q = "UPDATE usr_data SET passwd='".md5($a_pw)."', i2passwd=''".
					"WHERE login = '".$a_user."'";
				$ilias->db->query($q);
				return true;
			}
		}

		return false;
	}

	/**
	* update login name
	* @param	string	new login
	* @return	boolean	true on success; otherwise false
	* @access	public
	*/
	function updateLogin($a_login)
	{
		if (func_num_args() != 1)
		{
			return false;
		}

		if (!isset($a_login))
		{
			return false;
		}

		//update login
		$this->login = $a_login;

		$q = "UPDATE usr_data SET ".
			 "login='".$this->login."' ".
			 "WHERE usr_id='".$this->id."'";
		$this->ilias->db->query($q);

		return true;
	}

	/**
	* write userpref to user table
	* @access	private
	* @param	string	keyword
	* @param	string		value
	*/
	function writePref($a_keyword, $a_value)
	{
		ilObjUser::_writePref($this->id, $a_keyword, $a_value);
		$this->setPref($a_keyword, $a_value);
	}


	function _writePref($a_usr_id, $a_keyword, $a_value)
	{
		global $ilDB;

		//DELETE
		$q = "DELETE FROM usr_pref ".
			 "WHERE usr_id='".$a_usr_id."' ".
			 "AND keyword='".$a_keyword."'";
		$ilDB->query($q);

		//INSERT
		if ($a_value != "")
		{
			$q = "INSERT INTO usr_pref ".
				 "(usr_id, keyword, value) ".
				 "VALUES ".
				 "('".$a_usr_id."', '".$a_keyword."', '".$a_value."')";

			$ilDB->query($q);
		}
	}

	/**
	* write all userprefs
	* @access	private
	*/
	function writePrefs()
	{
		//DELETE
		$q = "DELETE FROM usr_pref ".
			 "WHERE usr_id='".$this->id."'";
		$this->ilias->db->query($q);

		foreach ($this->prefs as $keyword => $value)
		{
			//INSERT
			$q = "INSERT INTO usr_pref ".
				 "(usr_id, keyword, value) ".
				 "VALUES ".
				 "('".$this->id."', '".$keyword."', '".$value."')";
			$this->ilias->db->query($q);
		}
	}

	/**
	* set a user preference
	* @param	string	name of parameter
	* @param	string	value
	* @access	public
	*/
	function setPref($a_keyword, $a_value)
	{
		if ($a_keyword != "")
		{
			$this->prefs[$a_keyword] = $a_value;
		}
	}

	/**
	* get a user preference
	* @param	string	name of parameter
	* @access	public
	*/
	function getPref($a_keyword)
	{
		return $this->prefs[$a_keyword];
	}

	/**
	* get all user preferences
	* @access	private
	* @return	integer		number of preferences
	*/
	function readPrefs()
	{
		if (is_array($this->prefs))
		{
			$this->oldPrefs = $this->prefs;
		}

		$this->prefs = array();

		$q = "SELECT * FROM usr_pref WHERE usr_id='".$this->id."'";
	//	$q = "SELECT * FROM usr_pref WHERE value='"y"'";
		$r = $this->ilias->db->query($q);

		while($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$this->prefs[$row["keyword"]] = $row["value"];
		} // while

		return $r->numRows();
	}

// Adding new function by
// ratanatyrupp@yahoo.com
// purpose: for unsing in usr_profile.php


// End of testing purpose
//
//
	/**
	* deletes a user
	* @access	public
	* @param	integer		user_id
	*/
	function delete()
	{
		global $rbacadmin;

		// remove mailbox / update sent mails
		include_once ("classes/class.ilMailbox.php");
		$mailbox = new ilMailbox($this->getId());
		$mailbox->delete();
		$mailbox->updateMailsOfDeletedUser();

		// delete user_account
		$this->ilias->db->query("DELETE FROM usr_data WHERE usr_id='".$this->getId()."'");

		// delete user_prefs
		$this->ilias->db->query("DELETE FROM usr_pref WHERE usr_id='".$this->getId()."'");
		
		// delete user_session
		$this->ilias->db->query("DELETE FROM usr_session WHERE user_id='".$this->getId()."'");

		// remove user from rbac
		$rbacadmin->removeUser($this->getId());

		// remove bookmarks
		// TODO: move this to class.ilBookmarkFolder
		$q = "DELETE FROM bookmark_tree WHERE tree='".$this->getId()."'";
		$this->ilias->db->query($q);

		$q = "DELETE FROM bookmark_data WHERE user_id='".$this->getId()."'";
		$this->ilias->db->query($q);

		// DELETE FORUM ENTRIES (not complete in the moment)
		include_once './classes/class.ilObjForum.php';
		ilObjForum::_deleteUser($this->getId());

		// Delete link check notify entries
		include_once './classes/class.ilLinkCheckNotify.php';
		ilLinkCheckNotify::_deleteUser($this->getId());

		// Delete crs entries
		include_once './course/classes/class.ilObjCourse.php';
		ilObjCourse::_deleteUser($this->getId());

		// Delete user tracking
		include_once './Services/Tracking/classes/class.ilObjUserTracking.php';
		ilObjUserTracking::_deleteUser($this->getId());

		// Delete group registrations
		$q = "DELETE FROM grp_registration WHERE user_id='".$this->getId()."'";
		$this->ilias->db->query($q);

		// Delete user defined field entries
		$this->deleteUserDefinedFieldEntries();

		// delete object data
		parent::delete();
		return true;
	}

	/**
	* builds a string with title + firstname + lastname
	* method is used to build fullname in member variable $this->fullname. But you
	* may use the function in static manner.
	* @access	static
	* @param	string	title (opt.)
	* @param	string	firstname (opt.)
	* @param	string	lastname (opt.)
	*/
	function setFullname($a_title = "",$a_firstname = "",$a_lastname = "")
	{
		$this->fullname = "";

		if ($a_title)
		{
			$fullname = $a_title." ";
		}
		elseif ($this->utitle)
		{
			$this->fullname = $this->utitle." ";
		}

		if ($a_firstname)
		{
			$fullname .= $a_firstname." ";
		}
		elseif ($this->firstname)
		{
			$this->fullname .= $this->firstname." ";
		}

		if ($a_lastname)
		{
			return $fullname.$a_lastname;
		}

		$this->fullname .= $this->lastname;
	}

	/**
	* get fullname
	* @access	public
	* @param	integer	max. string length to return (optional)
	* 			if string length of fullname is greater than given a_max_strlen
	* 			the name is shortened in the following way:
	* 			1. abreviate firstname (-> Dr. J. Smith)
	* 			if fullname is still too long
	* 			2. drop title (-> John Smith)
	* 			if fullname is still too long
	* 			3. drop title and abreviate first name (J. Smith)
	* 			if fullname is still too long
	* 			4. drop title and firstname and shorten lastname to max length (--> Smith)
	*/
	function getFullname($a_max_strlen = 0)
	{
		if (!$a_max_strlen)
		{
			return ilUtil::stripSlashes($this->fullname);
		}
		
		if (strlen($this->fullname) <= $a_max_strlen)
		{
			return ilUtil::stripSlashes($this->fullname);
		}
		
		if ((strlen($this->utitle) + strlen($this->lastname) + 4) <= $a_max_strlen)
		{
			return ilUtil::stripSlashes($this->utitle." ".substr($this->firstname,0,1).". ".$this->lastname);
		}
		
		if ((strlen($this->firstname) + strlen($this->lastname) + 1) <= $a_max_strlen)
		{
			return ilUtil::stripSlashes($this->firstname." ".$this->lastname);
		}

		if ((strlen($this->lastname) + 3) <= $a_max_strlen)
		{
			return ilUtil::stripSlashes(substr($this->firstname,0,1).". ".$this->lastname);
		}
		
		return ilUtil::stripSlashes(substr($this->lastname,0,$a_max_strlen));
	}

// ### AA 03.09.01 updated page access logger ###
	/**
	* get read lessons, ordered by timestamp
	* @access	public
	* @return	array	lessons
	*/
	function getLastVisitedLessons()
	{
		//query
		$q = "SELECT * FROM lo_access ".
			"WHERE usr_id='".$this->id."' ".
			"ORDER BY timestamp DESC";
		$rst = $this->ilias->db->query($q);

		// fill array
		$result = array();
		while($record = $rst->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$result[] = array(
			"timestamp"	=>	$record->timestamp,
			"usr_id"		=>	$record->usr_id,
			"lm_id"		=>	$record->lm_id,
			"obj_id"		=>	$record->obj_id,
			"lm_title"	=>	$record->lm_title);
		}
		return $result;
	}

// ### AA 03.09.01 updated page access logger ###
	/**
	* get all lessons, unordered
	* @access	public
	* @return	array	lessons
	*/
	function getLessons()
	{
		//query
		$q = "SELECT * FROM lo_access ".
			"WHERE usr_id='".$this->id."' ";
		$rst = $this->ilias->db->query($q);

		// fill array
		$result = array();
		while($record = $rst->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$result[] = array(
			"timestamp"	=>	$record->timestamp,
			"usr_id"		=>	$record->usr_id,
			"lm_id"		=>	$record->lm_id,
			"obj_id"		=>	$record->obj_id,
			"lm_title"	=>	$record->lm_title);
		}
		return $result;
	}


	/**
	* get courses the user has access to
	* @access	public
	* @return	array	lessons
	* // TODO: query wird nicht abgeschickt!!!
	*/
	function getCourses()
	{
		global $lng;

		//initialize array
		$courses = array();
		//query
		$sql = "SELECT * FROM courses
				WHERE user_fk='".$this->id."'
				AND read=1";
		$courses[] = array(
			"id" => 1,
			"title" => "Course 1",
			"desc" => "description of course one",
			"content" => "This is Course One",
			"datetime" => date("Y-m-d")
			);
		return $courses;
	}

	/**
	* get literature bookmarks
	* @access	public
	* @return	array	lessons
	* // TODO: query wird nicht abgeschickt!!!
	*/
	function getLiterature()
	{
		//initialize array
		$literature = array();
		//query
		$sql = "SELECT * FROM literature";

		$literature[] = array(
			"id" => 1,
			"url" => "http://www.gutenberg.de",
			"desc" => "project gutenberg",
			);

		return $literature;
	}

	/**
	* check wether user has accepted user agreement
	*/
	function hasAcceptedUserAgreement()
	{
		if ($this->accept_date != "0000-00-00 00:00:00" || $this->login == "root")
		{
			return true;
		}
		return false;
	}

	/**
	* set login / username
	* @access	public
	* @param	string	username
	*/
	function setLogin($a_str)
	{
		$this->login = $a_str;
	}

	/**
	* get login / username
	* @access	public
	*/
	function getLogin()
	{
		return $this->login;
	}

	/**
	* set password
	* @access	public
	* @param	string	passwd
	*/
	function setPasswd($a_str, $a_type = IL_PASSWD_PLAIN)
	{
		$this->passwd = $a_str;
		$this->passwd_type = $a_type;
	}

	/**
	* get password
	* @return password. The password is encoded depending on the current 
    *                   password type.
	* @access	public
	* @see getPasswdType
	*/
	function getPasswd()
	{
		return $this->passwd;
	}
	/**
	* get password type
	* @return password type (IL_PASSWD_PLAIN, IL_PASSWD_MD5 or IL_PASSWD_CRYPT).
	* @access	public
	* @see getPasswd
	*/
	function getPasswdType()
	{
		return $this->passwd_type;
	}

	/**
	* set gender
	* @access	public
	* @param	string	gender
	*/
	function setGender($a_str)
	{
		$this->gender = substr($a_str,-1);
	}

	/**
	* get gender
	* @access	public
	*/
	function getGender()
	{
		return $this->gender;
	}

	/**
	* set user title
	* (note: don't mix up this method with setTitle() that is derived from
	* ilObject and sets the user object's title)
	* @access	public
	* @param	string	title
	*/
	function setUTitle($a_str)
	{
		$this->utitle = $a_str;
	}

	/**
	* get user title
	* (note: don't mix up this method with getTitle() that is derived from
	* ilObject and gets the user object's title)
	* @access	public
	*/
	function getUTitle()
	{
		return $this->utitle;
	}

	/**
	* set firstname
	* @access	public
	* @param	string	firstname
	*/
	function setFirstname($a_str)
	{
		$this->firstname = $a_str;
	}

	/**
	* get firstname
	* @access	public
	*/
	function getFirstname()
	{
		return $this->firstname;
	}

	/**
	* set lastame
	* @access	public
	* @param	string	lastname
	*/
	function setLastname($a_str)
	{
		$this->lastname = $a_str;
	}

	/**
	* get lastname
	* @access	public
	*/
	function getLastname()
	{
		return $this->lastname;
	}

	/**
	* set institution
	* @access	public
	* @param	string	institution
	*/
	function setInstitution($a_str)
	{
		$this->institution = $a_str;
	}

	/**
	* get institution
	* @access	public
	*/
	function getInstitution()
	{
		return $this->institution;
	}

	/**
	* set department
	* @access	public
	* @param	string	department
	*/
	function setDepartment($a_str)
	{
		$this->department = $a_str;
	}

	/**
	* get department
	* @access	public
	*/
	function getDepartment()
	{
		return $this->department;
	}

	/**
	* set street
	* @access	public
	* @param	string	street
	*/
	function setStreet($a_str)
	{
		$this->street = $a_str;
	}

	/**
	* get street
	* @access	public
	*/
	function getStreet()
	{
		return $this->street;
	}

	/**
	* set city
	* @access	public
	* @param	string	city
	*/
	function setCity($a_str)
	{
		$this->city = $a_str;
	}

	/**
	* get city
	* @access	public
	*/
	function getCity()
	{
		return $this->city;
	}

	/**
	* set zipcode
	* @access	public
	* @param	string	zipcode
	*/
	function setZipcode($a_str)
	{
		$this->zipcode = $a_str;
	}

	/**
	* get zipcode
	* @access	public
	*/
	function getZipcode()
	{
		return $this->zipcode;
	}

	/**
	* set country
	* @access	public
	* @param	string	country
	*/
	function setCountry($a_str)
	{
		$this->country = $a_str;
	}

	/**
	* get country
	* @access	public
	*/
	function getCountry()
	{
		return $this->country;
	}

	/**
	* set office phone
	* @access	public
	* @param	string	office phone
	*/
	function setPhoneOffice($a_str)
	{
		$this->phone_office = $a_str;
	}

	/**
	* get office phone
	* @access	public
	*/
	function getPhoneOffice()
	{
		return $this->phone_office;
	}

	/**
	* set home phone
	* @access	public
	* @param	string	home phone
	*/
	function setPhoneHome($a_str)
	{
		$this->phone_home = $a_str;
	}

	/**
	* get home phone
	* @access	public
	*/
	function getPhoneHome()
	{
		return $this->phone_home;
	}

	/**
	* set mobile phone
	* @access	public
	* @param	string	mobile phone
	*/
	function setPhoneMobile($a_str)
	{
		$this->phone_mobile = $a_str;
	}

	/**
	* get mobile phone
	* @access	public
	*/
	function getPhoneMobile()
	{
		return $this->phone_mobile;
	}

	/**
	* set fax
	* @access	public
	* @param	string	fax
	*/
	function setFax($a_str)
	{
		$this->fax = $a_str;
	}

	/**
	* get fax
	* @access	public
	*/
	function getFax()
	{
		return $this->fax;
	}

	/**
	* set client ip number
	* @access	public
	* @param	string	client ip
	*/
	function setClientIP($a_str)
	{
		$this->client_ip = $a_str;
	}

	/**
	* get client ip number
	* @access	public
	*/
	function getClientIP()
	{
		return $this->client_ip;
	}

	/**
	* set matriculation number
	* @access	public
	* @param	string	matriculation number
	*/
	function setMatriculation($a_str)
	{
		$this->matriculation = $a_str;
	}

	/**
	* get matriculation number
	* @access	public
	*/
	function getMatriculation()
	{
		return $this->matriculation;
	}

	/**
	* set email
	* @access	public
	* @param	string	email address
	*/
	function setEmail($a_str)
	{
		$this->email = $a_str;
	}

	/**
	* get email address
	* @access	public
	*/
	function getEmail()
	{
		return $this->email;
	}

	/**
	* set hobby
	* @access	public
    * @param    string  hobby
	*/
	function setHobby($a_str)
	{
		$this->hobby = $a_str;
	}

	/**
    * get hobby
	* @access	public
	*/
	function getHobby()
	{
		return $this->hobby;
	}

	/**
	* set user language
	* @access	public
	* @param	string	lang_key (i.e. de,en,fr,...)
	*/
	function setLanguage($a_str)
	{
		$this->setPref("language",$a_str);
		unset($_SESSION['lang']);
	}

	/**
	* returns a 2char-language-string
	* @access	public
	* @return	string	language
	*/
	function getLanguage()
	{
		 return $this->prefs["language"];
	}

	function _lookupLanguage($a_usr_id)
	{
		global $ilDB;

		$q = "SELECT value FROM usr_pref WHERE usr_id='".$a_usr_id."' AND keyword = 'language'";
		$r = $ilDB->query($q);

		while($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			return $row['value'];
		}
		return 'en';
	}
	/**
	 * returns the current language (may differ from user's pref setting!)
	 * 
	 */
	function getCurrentLanguage()
	{
		return $_SESSION['lang'];
	}

	/**
	* set user's last login
	* @access	public
	* @param	string	login date
	*/
	function setLastLogin($a_str)
	{
		$this->last_login = $a_str;
	}

	/**
	* returns last login date
	* @access	public
	* @return	string	date
	*/
	function getLastLogin()
	{
		 return $this->last_login;
	}

	/**
	* set last update of user data set
	* @access	public
	* @param	string	date
	*/
	function setLastUpdate($a_str)
	{
		$this->last_update = $a_str;
	}
	function getLastUpdate()
	{
		return $this->last_update;
	}

    /**
    * set referral comment
    * @access   public
    * @param    string  hobby
    */
    function setComment($a_str)
    {
        $this->referral_comment = $a_str;
    }

    /**
    * get referral comment
    * @access   public
    */
    function getComment()
    {
        return $this->referral_comment;
    }

    /**
    * set date the user account was activated
    * 0000-00-00 00:00:00 indicates that the user has not yet been activated
    * @access   public
    * @return   string      date of last update
    */
    function setApproveDate($a_str)
    {
        $this->approve_date = $a_str;
    }

    /**
    * get date the user account was activated
    * @access   public
    * @return   string      date of last update
    */
    function getApproveDate()
    {
        return $this->approve_date;
    }

    /**
    * set user active state and updates system fields appropriately
    * @access   public
    * @param    string  $a_active the active state of the user account
    * @param    string  $a_owner the id of the person who approved the account, defaults to 6 (root)
    */
    function setActive($a_active, $a_owner = 6)
    {
        if (empty($a_owner))
        {
            $a_owner = 0;
        }

        if ($a_active)
        {
            $this->active = 1;
            $this->setApproveDate(date('Y-m-d H:i:s'));
            $this->setOwner($a_owner);
        }
        else
        {
            $this->active = 0;
            $this->setApproveDate('0000-00-00 00:00:00');
            $this->setOwner(0);
        }
    }

    /**
    * get user active state
    * @access   public
    */
    function getActive()
    {
        return $this->active;
    }

    /**
    * synchronizes current and stored user active values
    * for the owner value to be set correctly, this function should only be called when an admin is approving a user account
    * @access  public
    */
    function syncActive()
    {
        $storedActive   = 0;
        if ($this->getStoredActive($this->id))
        {
            $storedActive   = 1;
        }

        $currentActive  = 0;
        if ($this->active)
        {
            $currentActive  = 1;
        }

        if ((!empty($storedActive) && empty($currentActive)) ||
                (empty($storedActive) && !empty($currentActive)))
        {
            $this->setActive($currentActive, $this->getUserIdByLogin($this->ilias->auth->getUsername()));
        }
    }

    /**
    * get user active state
    * @param   integer $a_id user id
    * @access  public
    * @return  true if active, otherwise false
    */
    function getStoredActive($a_id)
    {
        global $ilias;

        $query = "SELECT active FROM usr_data ".
            "WHERE usr_id = '".$a_id."'";

        $row = $ilias->db->getRow($query,DB_FETCHMODE_OBJECT);

        return $row->active ? true : false;
    }

	/**
	* set user skin (template set)
	* @access	public
	* @param	string	directory name of template set
	*/
	function setSkin($a_str)
	{
		// TODO: exception handling (dir exists)
		$this->skin = $a_str;
	}

    function setTimeLimitOwner($a_owner)
    {
        $this->time_limit_owner = $a_owner;
    }
    function getTimeLimitOwner()
    {
        return $this->time_limit_owner ? $this->time_limit_owner : 7;
    }
    function setTimeLimitFrom($a_from)
    {
        $this->time_limit_from = $a_from;
    }
    function getTimeLimitFrom()
    {
        return $this->time_limit_from ? $this->time_limit_from : time();
    }
    function setTimeLimitUntil($a_until)
    {
        $this->time_limit_until = $a_until;
    }
    function getTimeLimitUntil()
    {
        return $this->time_limit_until ? $this->time_limit_until : time();
    }
    function setTimeLimitUnlimited($a_unlimited)
    {
        $this->time_limit_unlimited = $a_unlimited;
    }
    function getTimeLimitUnlimited()
    {
        return $this->time_limit_unlimited;
    }
	function setTimeLimitMessage($a_time_limit_message)
	{
		return $this->time_limit_message = $a_time_limit_message;
	}
	function getTimeLimitMessage()
	{
		return $this->time_limit_message;
	}
		

	function checkTimeLimit()
	{
		if($this->getTimeLimitUnlimited())
		{
			return true;
		}
		if($this->getTimeLimitFrom() < time() and $this->getTimeLimitUntil() > time())
		{
			return true;
		}
		return false;
	}
    function setProfileIncomplete($a_prof_inc)
    {
        $this->profile_incomplete = (boolean) $a_prof_inc;
    }
    function getProfileIncomplete()
    {
        return $this->profile_incomplete;
    }

	function &getAppliedUsers()
	{
		$this->applied_users = array();
		$this->__readAppliedUsers($this->getId());

		return $this->applied_users ? $this->applied_users : array();
	}

	function isChild($a_usr_id)
	{
		if($a_usr_id == $this->getId())
		{
			return true;
		}

		$this->applied_users = array();
		$this->__readAppliedUsers($this->getId());

		return in_array($a_usr_id,$this->applied_users);
	}

	function __readAppliedUsers($a_parent_id)
	{
		$query = "SELECT usr_id FROM usr_data ".
			"WHERE time_limit_owner = '".$a_parent_id."'";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->applied_users[] = $row->usr_id;
			
			// recursion
			$this->__readAppliedUsers($row->usr_id);
		}
		return true;
	}

	/*
     * check user id with login name
     * @access  public
     */
	function checkUserId()
	{
		$r = $this->ilias->db->query("SELECT usr_id FROM usr_data WHERE login='".$this->ilias->auth->getUsername()."'");
		//query has got a result
		if ($r->numRows() > 0)
		{
			$data = $r->fetchRow();
			$this->id = $data[0];

			return $this->id;
		}

		return false;
	}

    /*
     * check to see if current user has been made active
     * @access  public
     * @return  true if active, otherwise false
     */
    function isCurrentUserActive()
    {
        $r = $this->ilias->db->query("SELECT active FROM usr_data WHERE login='".$this->ilias->auth->getUsername()."'");
        //query has got a result
        if ($r->numRows() > 0)
        {
            $data = $r->fetchRow();
            if (!empty($data[0]))
            {
                return true;
            }
        }

        return false;
    }

    /*
	 * STATIC METHOD
	 * get the user_id of a login name
	 * @param	string login name
	 * @return  integer id of user
	 * @static
	 * @access	public
	 */
	function getUserIdByLogin($a_login)
	{
		global $ilias;

		$query = "SELECT usr_id FROM usr_data ".
			"WHERE login = '".$a_login."'";

		$row = $ilias->db->getRow($query,DB_FETCHMODE_OBJECT);

		return $row->usr_id ? $row->usr_id : 0;
	}

	/**
	 * STATIC METHOD
	 * get all user_ids of an email address
	 * @param	string email of user
	 * @return  integer id of user
	 * @static
	 * @access	public
	 */
	function _getUserIdsByEmail($a_email)
	{
		global $ilias;
		$query = "SELECT login FROM usr_data ".
			"WHERE email = '".$a_email."' and active=1";

 		$res = $ilias->db->query($query);
 		$ids = array ();
        while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
        {
            $ids[] = $row->login;
        }

		return $ids;	
	}



	/**
	 * STATIC METHOD
	 * get the user_id of an email address
	 * @param	string email of user
	 * @return  integer id of user
	 * @static
	 * @access	public
	 */
	function getUserIdByEmail($a_email)
	{
		$query = "SELECT usr_id FROM usr_data ".
			"WHERE email = '".$a_email."'";

		$row = $this->ilias->db->getRow($query,DB_FETCHMODE_OBJECT);
		return $row->usr_id ? $row->usr_id : 0;
	}

    /*
     * STATIC METHOD
     * get the login name of a user_id
     * @param   integer id of user
     * @return  string login name; false if not found
     * @static
     * @access  public
     */
    function getLoginByUserId($a_userid)
    {
        global $ilias;

        $query = "SELECT login FROM usr_data ".
            "WHERE usr_id = '".$a_userid."'";

        $row = $ilias->db->getRow($query,DB_FETCHMODE_OBJECT);

        return $row->login ? $row->login : false;
    }

	/**
	 * STATIC METHOD
	 * get the user_ids which correspond a search string
	 * @param	string search string
	 * @static
	 * @access	public
	 */
	function searchUsers($a_search_str, $active = 1)
	{
		// NO CLASS VARIABLES IN STATIC METHODS
		global $ilias;

        // This is a temporary hack to search users by their role
        // See Mantis #338. This is a hack due to Mantis #337.
        if (strtolower(substr($a_search_str, 0, 5)) == "role:") 
        { 
            $query = "SELECT DISTINCT usr_data.usr_id,usr_data.login,usr_data.firstname,usr_data.lastname,usr_data.email ". 
                   "FROM object_data,rbac_ua,usr_data ". 
             "WHERE object_data.title LIKE '%".substr($a_search_str,5)."%' and object_data.type = 'role' ". 
             "and rbac_ua.rol_id = object_data.obj_id ". 
             "and usr_data.usr_id = rbac_ua.usr_id ". 
             "AND rbac_ua.usr_id != '".ANONYMOUS_USER_ID."'"; 
        } 
        else
        { 
            $query = "SELECT usr_id,login,firstname,lastname,email,active FROM usr_data ".
                "WHERE (login LIKE '%".$a_search_str."%' ".
                "OR firstname LIKE '%".$a_search_str."%' ".
                "OR lastname LIKE '%".$a_search_str."%' ".
                "OR email LIKE '%".$a_search_str."%') ".
                "AND usr_id != '".ANONYMOUS_USER_ID."'";
        }
        
        if (is_numeric($active) && $active > -1)
        	$query .= "AND active = '$active'";

        $res = $ilias->db->query($query);
        while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
        {
            $ids[] = array(
                "usr_id"    => $row->usr_id,
                "login"     => $row->login,
                "firstname" => $row->firstname,
                "lastname"  => $row->lastname,
                "email"     => $row->email,
                "active"    => $row->active);
        }

		return $ids ? $ids : array();
	}

	/**
	 * STATIC METHOD
	 * search for user data. This method is called from class.ilSearch
	 * @param	object object of search class
	 * @static
	 * @access	public
	 */
	function _search(&$a_search_obj, $active=1)
	{
		global $ilBench;

		// NO CLASS VARIABLES IN STATIC METHODS

		// TODO: CHECK IF ITEMS ARE PUBLIC VISIBLE

		$where_condition = $a_search_obj->getWhereCondition("like",array("login","firstname","lastname","title",
																		 "email","institution","street","city",
																		 "zipcode","country","phone_home","fax"));
		$in = $a_search_obj->getInStatement("usr_data.usr_id");

		$query = "SELECT DISTINCT(usr_data.usr_id) FROM usr_data ".
			"LEFT JOIN usr_pref USING (usr_id) ".
			$where_condition." ".
			$in." ".
			"AND usr_data.usr_id != '".ANONYMOUS_USER_ID."' ";
#			"AND usr_pref.keyword = 'public_profile' ";
#			"AND usr_pref.value = 'y'";
		
  		if (is_numeric($active)  && $active > -1)
        	$query .= "AND active = '$active'";

		$ilBench->start("Search", "ilObjUser_search");
		$res = $a_search_obj->ilias->db->query($query);
		$ilBench->stop("Search", "ilObjUser_search");

		$counter = 0;

		while ($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$result_data[$counter++]["id"]				=  $row->usr_id;

			// LINKS AND TARGETS AREN'T SAVED ANYMORE, SEARCHGUI HAS TO CALL ilObjUser::_getSearchLink
			// TO GET THE LINK OF SPECIFIC OBJECT
			#$result_data[$counter]["link"]				=  "profile.php?user=".$row->usr_id;
			#$result_data[$counter++]["target"]			=  "";
		}
		return $result_data ? $result_data : array();
	}

	/**
	 * STATIC METHOD
	 * create a link to the object
	 * This method used by class.ilSearchGUI.php to a link to the results
	 * @param	int uniq id
	 * @return array array('link','target')
	 * @static
	 * @access	public
	 */
	function _getLinkToObject($a_id)
	{
		return array("profile.php?user=".$a_id,"");
	}

	/*
	* get the memberships(group_ids) of groups that are subscribed to the current user object
	* @param	integer optional user_id
	* @access	public
	*/
	function getGroupMemberships($a_user_id = "")
	{
		global $rbacreview, $tree;

		if (strlen($a_user_id) > 0)
		{
			$user_id = $a_user_id;
		}
		else
		{
			$user_id = $this->getId();
		}

		$grp_memberships = array();
		
		// get all roles which the user is assigned to
		$roles = $rbacreview->assignedRoles($user_id);

		foreach ($roles as $role)
		{
			$ass_rolefolders = $rbacreview->getFoldersAssignedToRole($role);	//rolef_refids

			foreach ($ass_rolefolders as $role_folder)
			{
				$node = $tree->getParentNodeData($role_folder);

				if ($node["type"] == "grp")
				{
					$group =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);

					if ($group->isMember($user_id) == true && !in_array($group->getId(), $grp_memberships) )
					{
						array_push($grp_memberships, $group->getId());
					}
				}

				unset($group);
			}
		}

		return $grp_memberships;
	}


	/**
	* STATIC METHOD
	* updates Session roles
	* @param	integer user_id
	* @static
	* @return	boolean	true if user is online and session was updated
	* @access	public
	*/
	function updateActiveRoles($a_user_id)
	{
		global $rbacreview, $ilDB;
		
		if (!count($user_online = ilUtil::getUsersOnline($a_user_id)) == 1)
		{
			return false;
		}
		
		$role_arr = $rbacreview->assignedRoles($a_user_id);

		if ($_SESSION["AccountId"] == $a_user_id)
		{
			$_SESSION["RoleId"] = $role_arr;
		}
		else
		{
			$roles = "RoleId|".serialize($role_arr);
			$modified_data = preg_replace("/RoleId.*?;\}/",$roles,$user_online[$a_user_id]["data"]);

			$q = "UPDATE usr_session SET data='".ilUtil::prepareDBString($modified_data)."' WHERE user_id = '".$a_user_id."'";
			$ilDB->query($q);
		}

		return true;
	}

	/**
	* STATIC METHOD
	* get all user logins
	* @param	ilias object
	* @static
	* @return	array of logins
	* @access	public
	*/
	function _getAllUserLogins(&$ilias)
	{
		$query = "SELECT login FROM usr_data ";

		$res = $ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$logins[] = $row->login;
		}
		return $logins ? $logins : array();
	}
	
	/**
     * STATIC METHOD
     * get all user data
     * @param	array desired columns
     * @static
     * @return	array of user data
     * @access	public
     */
	function _getAllUserData($a_fields = NULL, $active =-1)
	{
        global $ilDB;

        $result_arr = array();

        if ($a_fields !== NULL and is_array($a_fields))
        {
            if (count($a_fields) == 0)
            {
                $select = "*";
            }
            else
            {
                if (($usr_id_field = array_search("usr_id",$a_fields)) !== false)
                    unset($a_fields[$usr_id_field]);

                $select = implode(",",$a_fields).",usr_data.usr_id";
				// online time
				if(in_array('online_time',$a_fields))
				{
					$select .= ",ut_online.online_time ";
				}
            }

	        $q = "SELECT ".$select." FROM usr_data ";

			// Add online_time if desired
			// Need left join here to show users that never logged in
			if(in_array('online_time',$a_fields))
			{
				$q .= "LEFT JOIN ut_online ON usr_data.usr_id = ut_online.usr_id ";
			}

	        
	        if (is_numeric($active) && $active > -1)
	        	$q .= "WHERE active='$active'";
	        	
            $r = $ilDB->query($q);

            while ($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
            {
                $result_arr[] = $row;
            }
        }
        
   		return $result_arr;
	}
	
	/**
	* skins and styles
	*/
	function _getNumberOfUsersForStyle($a_skin, $a_style)
	{
		global $ilDB;
		
		$q = "SELECT count(*) as cnt FROM usr_pref AS up1, usr_pref AS up2 ".
			" WHERE up1.keyword= ".$ilDB->quote("style")." AND up1.value= ".$ilDB->quote($a_style).
			" AND up2.keyword= ".$ilDB->quote("skin")." AND up2.value= ".$ilDB->quote($a_skin).
			" AND up1.usr_id = up2.usr_id ";
			
		$cnt_set = $ilDB->query($q);
		
		$cnt_rec = $cnt_set->fetchRow(DB_FETCHMODE_ASSOC);
		
		return $cnt_rec["cnt"];
	}

	/**
	* skins and styles
	*/
	function _getAllUserAssignedStyles()
	{
		global $ilDB;
		
		$q = "SELECT DISTINCT up1.value as style, up2.value as skin FROM usr_pref AS up1, usr_pref AS up2 ".
			" WHERE up1.keyword= ".$ilDB->quote("style").
			" AND up2.keyword= ".$ilDB->quote("skin").
			" AND up1.usr_id = up2.usr_id ";
		
		
		$sty_set = $ilDB->query($q);
		
		$styles = array();
		while($sty_rec = $sty_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$styles[] = $sty_rec["skin"].":".$sty_rec["style"];
		}
		
		return $styles;
	}

	/**
	* skins and styles
	*/
	function _moveUsersToStyle($a_from_skin, $a_from_style, $a_to_skin, $a_to_style)
	{
		global $ilDB;
		
		$q = "SELECT up1.usr_id as usr_id FROM usr_pref AS up1, usr_pref AS up2 ".
			" WHERE up1.keyword= ".$ilDB->quote("style")." AND up1.value= ".$ilDB->quote($a_from_style).
			" AND up2.keyword= ".$ilDB->quote("skin")." AND up2.value= ".$ilDB->quote($a_from_skin).
			" AND up1.usr_id = up2.usr_id ";

		$usr_set = $ilDB->query($q);

		while ($usr_rec = $usr_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			ilObjUser::_writePref($usr_rec["usr_id"], "skin", $a_to_skin);
			ilObjUser::_writePref($usr_rec["usr_id"], "style", $a_to_style);
		}
	}

	/**
	* add an item to user's personal desktop
	*
	* @param	int		$a_item_id		ref_id for objects, that are in the main tree
	*									(learning modules, forums) obj_id for others
	* @param	string	$a_type			object type
	*/
	function addDesktopItem($a_item_id, $a_type, $a_par = "")
	{
		$q = "SELECT * FROM desktop_item WHERE ".
			"item_id = '$a_item_id' AND type = '$a_type' AND user_id = '".
			$this->getId()."'";
		$item_set = $this->ilias->db->query($q);

		// only insert if item is not already on desktop
		if (!$d = $item_set->fetchRow())
		{
			$q = "INSERT INTO desktop_item (item_id, type, user_id, parameters) VALUES ".
				" ('$a_item_id','$a_type','".$this->getId()."' , '$a_par')";
			$this->ilias->db->query($q);
		}
	}

	/**
	* set parameters of a desktop item entry
	*
	* @param	int		$a_item_id		ref_id for objects, that are in the main tree
	*									(learning modules, forums) obj_id for others
	* @param	string	$a_type			object type
	* @param	string	$a_par			parameters
	*/
	function setDesktopItemParameters($a_item_id, $a_type, $a_par)
	{
		$q = "UPDATE desktop_item SET parameters = '$a_par' ".
			" WHERE item_id = '$a_item_id' AND type = '$a_type' ".
			" AND user_id = '".$this->getId()."' ";
		$this->ilias->db->query($q);
	}

	/**
	* drop an item from user's personal desktop
	*
	* @param	int		$a_item_id		ref_id for objects, that are in the main tree
	*									(learning modules, forums) obj_id for others
	* @param	string	$a_type			object type
	*/
	function dropDesktopItem($a_item_id, $a_type)
	{
		$q = "DELETE FROM desktop_item WHERE ".
			" item_id = '$a_item_id' AND".
			" type = '$a_type' AND".
			" user_id = '".$this->getId()."'";
		$this->ilias->db->query($q);
	}

	/**
	* check wether an item is on the users desktop or not
	*
	* @param	int		$a_item_id		ref_id for objects, that are in the main tree
	*									(learning modules, forums) obj_id for others
	* @param	string	$a_type			object type
	*/
	function isDesktopItem($a_item_id, $a_type)
	{
		$q = "SELECT * FROM desktop_item WHERE ".
			"item_id = '$a_item_id' AND type = '$a_type' AND user_id = '".
			$this->getId()."'";
		$item_set = $this->ilias->db->query($q);

		if ($d = $item_set->fetchRow())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* get all desktop items of user and specified type
	*
	* note: the implementation of this method is not good style (directly
	* reading tables object_data and object_reference), must be revised someday...
	*/
	function getDesktopItems($a_types = "")
	{
		global $ilUser, $rbacsystem, $tree;

		
		if ($a_types == "")
		{
			$q = "SELECT obj.obj_id, obj.description, oref.ref_id, obj.title, obj.type ".
				" FROM desktop_item AS it, object_reference AS oref ".
					", object_data AS obj".
				" WHERE ".
				"it.item_id = oref.ref_id AND ".
				"oref.obj_id = obj.obj_id AND ".
				"it.user_id = '".$this->getId()."'";

			$item_set = $this->ilias->db->query($q);
			while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
			{
				if ($tree->isInTree($item_rec["ref_id"]))
				{
					$parent_ref = $tree->getParentId($item_rec["ref_id"]);
					$par_left = $tree->getLeftValue($parent_ref);
					$par_left = sprintf("%010d", $par_left);
					$items[$par_left.$item_rec["title"].$item_rec["ref_id"]] =
						array("ref_id" => $item_rec["ref_id"], 
							"obj_id" => $item_rec["obj_id"],
							"type" => $item_rec["type"],
							"title" => $item_rec["title"],
							"description" => $item_rec["description"],
							"parent_ref" => $parent_ref);
				}
			}
			ksort($items);
		}
		else
		{
			if (!is_array($a_types))
			{
				$a_types = array($a_types);
			}
			$items = array();
			$foundsurveys = array();
			foreach($a_types as $a_type)
			{
				$q = "SELECT obj.obj_id, obj.description, oref.ref_id, obj.title FROM desktop_item AS it, object_reference AS oref ".
					", object_data AS obj WHERE ".
					"it.item_id = oref.ref_id AND ".
					"oref.obj_id = obj.obj_id AND ".
					"it.type = '$a_type' AND ".
					"it.user_id = '".$this->getId()."' ".
					"ORDER BY title";
	
				$item_set = $this->ilias->db->query($q);
				while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
				{
					$items[$item_rec["title"].$a_type.$item_rec["ref_id"]] =
						array("ref_id" => $item_rec["ref_id"], 
						"obj_id" => $item_rec["obj_id"], "type" => $a_type,
						"title" => $item_rec["title"], "description" => $item_rec["description"]);
				}
				
			}
			ksort($items);
		}
		return $items;
	}

	/**
	* add an item to user's personal clipboard
	*
	* @param	int		$a_item_id		ref_id for objects, that are in the main tree
	*									(learning modules, forums) obj_id for others
	* @param	string	$a_type			object type
	*/
	function addObjectToClipboard($a_item_id, $a_type, $a_title)
	{
		$q = "SELECT * FROM personal_clipboard WHERE ".
			"item_id = '$a_item_id' AND type = '$a_type' AND user_id = '".
			$this->getId()."'";
		$item_set = $this->ilias->db->query($q);

		// only insert if item is not already on desktop
		if (!$d = $item_set->fetchRow())
		{
			$q = "INSERT INTO personal_clipboard (item_id, type, user_id, title) VALUES ".
				" ('$a_item_id','$a_type','".$this->getId()."', '".$a_title."')";
			$this->ilias->db->query($q);
		}
	}

	/**
	* get all clipboard objects of user and specified type
	*/
	function getClipboardObjects($a_type = "")
	{
		$type_str = ($a_type != "")
			? " AND type = '$a_type' "
			: "";
		$q = "SELECT * FROM personal_clipboard WHERE ".
			"user_id = '".$this->getId()."' ".
			$type_str;
		$objs = $this->ilias->db->query($q);
		$objects = array();
		while ($obj = $objs->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if ($obj["type"] == "mob")
			{
				$obj["title"] = ilObject::_lookupTitle($obj["item_id"]);
			}
			$objects[] = array ("id" => $obj["item_id"],
				"type" => $obj["type"], "title" => $obj["title"]);
		}
		return $objects;
	}

	/**
	* get all users, that have a certain object within their clipboard
	*
	* @param	string		$a_type		object type
	* @param	string		$a_type		object type
	*
	* @return	array		array of user IDs
	*/
	function _getUsersForClipboadObject($a_type, $a_id)
	{
		global $ilDB;

		$q = "SELECT DISTINCT user_id FROM personal_clipboard WHERE ".
			"item_id = '$a_id' AND ".
			"type = '$a_type'";
		$user_set = $ilDB->query($q);
		$users = array();
		while ($user_rec = $user_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$users[] = $user_rec["user_id"];
		}

		return $users;
	}

	/**
	* remove object from user's personal clipboard
	*
	* @param	int		$a_item_id		ref_id for objects, that are in the main tree
	*									(learning modules, forums) obj_id for others
	* @param	string	$a_type			object type
	*/
	function removeObjectFromClipboard($a_item_id, $a_type)
	{
		$q = "DELETE FROM personal_clipboard WHERE ".
			"item_id = '$a_item_id' AND type = '$a_type' ".
			" AND user_id = '".$this->getId()."'";
		$this->ilias->db->query($q);
	}

	function _getImportedUserId($i2_id)
	{
		$query = "SELECT obj_id FROM object_data WHERE import_id = '".$i2_id."'";

		$res = $this->ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$id = $row->obj_id;
		}
		return $id ? $id : 0;
	}

/*

	function setiLincData($a_id,$a_login,$a_passwd)
	{
		$this->ilinc_id = $a_id;
		$this->ilinc_login = $a_login;
		$this->ilinc_passwd = $a_passwd;
	}
	
*/
	
/*

	function getiLincData()
	{
		return array ("id" => $this->ilinc_id, "login" => $this->ilinc_login, "passwd" => $this->ilinc_passwd);
	}
*/
	/**
    * set auth mode
	* @access	public
	*/
	function setAuthMode($a_str)
	{
		$this->auth_mode = $a_str;
	}
	
	/**
    * get auth mode
	* @access	public
	*/
	function getAuthMode($a_auth_key = false)
	{
		if (!$a_auth_key)
		{
			return $this->auth_mode;
		}
		
		include_once('classes/class.ilAuthUtils.php');
		return ilAuthUtils::_getAuthMode($this->auth_mode);
	}

	/**
    * set external account
	*
	* note: 3.7.0 uses this field only for cas and soap authentication.
	*
	* @access	public
	*/
	function setExternalAccount($a_str)
	{
		$this->ext_account = $a_str;
	}
	
	/**
    * get external account
	*
	* note: 3.7.0 uses this field only for cas and soap authentication.
	*
	* @access	public
	*/
	function getExternalAccount()
	{
		return $this->ext_account;
	}
	
	/**
	* check whether external account and authentication method
	* matches with a user
	*
	*/
	function _checkExternalAuthAccount($a_auth, $a_account)
	{
		global $ilDB;
		
		$r = $ilDB->query("SELECT * FROM usr_data WHERE ".
			" ext_account = ".$ilDB->quote($a_account)." AND ".
			" auth_mode = ".$ilDB->quote($a_auth));
		if ($usr = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			return $usr["login"];
		}
		else
		{
			return false;
		}
	}
	
	/**
	* Create a personal picture image file from a temporary image file
	*
	* @param	string $tmp_file Complete path to the temporary image file
	* @param	int	$obj_id The object id of the related user account
	* @return returns TRUE on success, otherwise FALSE
	*/
	function _uploadPersonalPicture($tmp_file, $obj_id)
	{
		$webspace_dir = ilUtil::getWebspaceDir();
		$image_dir = $webspace_dir."/usr_images";
		$store_file = "usr_".$obj_id."."."jpg";
		$target_file = $image_dir."/$store_file";
	
		chmod($tmp_file, 0770);
	
		// take quality 100 to avoid jpeg artefacts when uploading jpeg files
		// taking only frame [0] to avoid problems with animated gifs
		$show_file  = "$image_dir/usr_".$obj_id.".jpg"; 
		$thumb_file = "$image_dir/usr_".$obj_id."_small.jpg";
		$xthumb_file = "$image_dir/usr_".$obj_id."_xsmall.jpg"; 
		$xxthumb_file = "$image_dir/usr_".$obj_id."_xxsmall.jpg";
	
		system(ilUtil::getConvertCmd()." $tmp_file" . "[0] -geometry 200x200 -quality 100 JPEG:$show_file");
		system(ilUtil::getConvertCmd()." $tmp_file" . "[0] -geometry 100x100 -quality 100 JPEG:$thumb_file");
		system(ilUtil::getConvertCmd()." $tmp_file" . "[0] -geometry 75x75 -quality 100 JPEG:$xthumb_file");
		system(ilUtil::getConvertCmd()." $tmp_file" . "[0] -geometry 30x30 -quality 100 JPEG:$xxthumb_file");

		// store filename
		ilObjUser::_writePref($obj_id, "profile_image", $store_file);

		return TRUE;
	}
	
	/**
	* get path to personal picture
	*
	* @param	string		size		"small", "xsmall" or "xxsmall"
	*/
	function getPersonalPicturePath($a_size = "small")
	{
		return ilObjUser::_getPersonalPicturePath($this->getId(),$a_size);
	}

	/**
	* get path to personal picture
	*
	* @param	string		size		"small", "xsmall" or "xxsmall"
	* STATIC
	*/
	function _getPersonalPicturePath($a_usr_id,$a_size = "small")
	{
		global $ilDB;

		$query = "SELECT * FROM usr_pref WHERE ".
			"keyword = 'public_upload' ".
			"AND value = 'y' ".
			"AND usr_id = '".$a_usr_id."'";

		$res = $ilDB->query($query);
		$upload = $res->numRows() ? true : false;

		$query = "SELECT * FROM usr_pref WHERE ".
			"keyword = 'public_profile' ".
			"AND value = 'y' ".
			"AND usr_id = '".$a_usr_id."'";

		$res = $ilDB->query($query);
		$profile = $res->numRows() ? true : false;


		if(defined('ILIAS_MODULE'))
		{
			$webspace_dir = ('.'.$webspace_dir);
		}
		$webspace_dir .= ('./'.ilUtil::getWebspaceDir());

		$image_dir = $webspace_dir."/usr_images";
		$thumb_file = $image_dir."/usr_".$a_usr_id."_".$a_size.".jpg";

		if($upload and $profile and @is_file($thumb_file))
		{
			$file = $thumb_file."?t=".rand(1, 99999);
		}
		else
		{
			$file = ilUtil::getImagePath("no_photo_".$a_size.".jpg");
		}

		return $file;
	}

	function setUserDefinedData($a_data)
	{
		if(!is_array($a_data))
		{
			return false;
		}
		foreach($a_data as $field => $data)
		{
			$new_data[$field] = ilUtil::stripSlashes($data);
		}
		$this->user_defined_data = $new_data;

		return true;
	}

	function getUserDefinedData()
	{
		return $this->user_defined_data ? $this->user_defined_data : array();
	}

	function updateUserDefinedFields()
	{
		foreach($this->user_defined_data as $field => $value)
		{
			$query = "UPDATE usr_defined_data ".
				"SET `".$field."` = '".ilUtil::prepareDBString($value)."' ".
				"WHERE usr_id = '".$this->getId()."'";

			$this->db->query($query);
		}
		return true;
	}

	function readUserDefinedFields()
	{
		$query = "SELECT * FROM usr_defined_data ".
			"WHERE usr_id = '".$this->getId()."'";

		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$this->user_defined_data = $row;
		}
		return true;
	}

	function addUserDefinedFieldEntry()
	{
		$query = "INSERT INTO usr_defined_data ".
			"SET usr_id = '".$this->getId()."'";
		$this->db->query($query);

		return true;
	}

	function deleteUserDefinedFieldEntries()
	{
		$query = "DELETE FROM usr_defined_data ".
			"WHERE usr_id = '".$this->getId()."'";
		$this->db->query($query);

		return true;
	}

	/**
	* Get formatted mail body text of user profile data.
	*
	* @param	object	  Language object (choose user language of recipient) or null to use language of current user
	*/
	function getProfileAsString(&$a_language)
	{
		include_once 'classes/class.ilObjRole.php';

		global $lng,$rbacreview;

		$language =& $a_language;
		$language->loadLanguageModule('registration');
		$language->loadLanguageModule('crs');

		$body = '';
        $body .= ($language->txt("login").": ".$this->getLogin()."\n");
		
		if(strlen($this->getUTitle()))
		{
			$body .= ($language->txt("title").": ".$this->getUTitle()."\n");
		}
		if(strlen($this->getGender()))
		{
			$gender = ($this->getGender() == 'm') ? 
				$language->txt('gender_m') :
				$language->txt('gender_f');
			$body .= ($language->txt("gender").": ".$gender."\n");
		}
		if(strlen($this->getFirstname()))
		{
			$body .= ($language->txt("firstname").": ".$this->getFirstname()."\n");
		}
		if(strlen($this->getLastname()))
		{
			$body .= ($language->txt("lastname").": ".$this->getLastname()."\n");
		}
		if(strlen($this->getInstitution()))
		{
			$body .= ($language->txt("institution").": ".$this->getInstitution()."\n");
		}
		if(strlen($this->getDepartment()))
		{
			$body .= ($language->txt("department").": ".$this->getDepartment()."\n");
		}
		if(strlen($this->getStreet()))
		{
			$body .= ($language->txt("street").": ".$this->getStreet()."\n");
		}
		if(strlen($this->getCity()))
		{
			$body .= ($language->txt("city").": ".$this->getCity()."\n");
		}
		if(strlen($this->getZipcode()))
		{
			$body .= ($language->txt("zipcode").": ".$this->getZipcode()."\n");
		}
		if(strlen($this->getCountry()))
		{
			$body .= ($language->txt("country").": ".$this->getCountry()."\n");
		}
		if(strlen($this->getPhoneOffice()))
		{
			$body .= ($language->txt("phone_office").": ".$this->getPhoneOffice()."\n");
		}
		if(strlen($this->getPhoneHome()))
		{
			$body .= ($language->txt("phone_home").": ".$this->getPhoneHome()."\n");
		}
		if(strlen($this->getPhoneMobile()))
		{
			$body .= ($language->txt("phone_mobile").": ".$this->getPhoneMobile()."\n");
		}
		if(strlen($this->getFax()))
		{
			$body .= ($language->txt("fax").": ".$this->getFax()."\n");
		}
		if(strlen($this->getEmail()))
		{
			$body .= ($language->txt("email").": ".$this->getEmail()."\n");
		}
		if(strlen($this->getHobby()))
		{
			$body .= ($language->txt("hobby").": ".$this->getHobby()."\n");
		}
		if(strlen($this->getComment()))
		{
			$body .= ($language->txt("referral_comment").": ".$this->getComment()."\n");
		}
		if(strlen($this->getMatriculation()))
		{
			$body .= ($language->txt("matriculation").": ".$this->getMatriculation()."\n");
		}
		if(strlen($this->getCreateDate()))
		{
			$body .= ($language->txt("create_date").": ".$this->getCreateDate()."\n");
		}

		foreach($rbacreview->getGlobalRoles() as $role)
		{
			if($rbacreview->isAssigned($this->getId(),$role))
			{
				$gr[] = ilObjRole::_lookupTitle($role);
			}
		}
		if(count($gr))
		{
			$body .= ($language->txt('reg_role_info').': '.implode(',',$gr)."\n");
		}

		// Time limit 
		if($this->getTimeLimitUnlimited())
		{
			$body .= ($language->txt('time_limit').": ".$language->txt('crs_unlimited')."\n");
		}
		else
		{
			$body .= ($language->txt('time_limit').": ".$language->txt('crs_from')." ".
					  strftime('%c',$this->getTimeLimitFrom())." ".
					  $language->txt('crs_to')." ".
					  strftime('%c',$this->getTimeLimitUntil())."\n");
		}
		return $body;
	}
} // END class ilObjUser
?>
