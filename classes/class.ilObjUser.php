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
* @author	Sascha Hofmann <shofmann@databay.de>
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
	var $passwd;	// md5 hash of password
	var $passwd_type;
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
		global $ilias;

		// init variables
		$this->ilias =& $ilias;

		$this->type = "usr";
		$this->ilObject($a_user_id, $a_call_by_reference);
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
			if (strpos($_SERVER["HTTP_USER_AGENT"],"Windows CE") > 0)
			{
				$this->skin = "pda";
			}
			else
			{
			 	$this->skin = $this->ilias->ini->readVariable("layout","skin");
			}

			$this->prefs["skin"] = $this->skin;

			//style (css)
		 	$this->prefs["style"] = $this->ilias->ini->readVariable("layout","style");
		}
	}

	/**
	* loads a record "user" from database
	* @access private
	*/
	function read ()
	{
		// TODO: fetching default role should be done in rbacadmin
		$q = "SELECT * FROM usr_data ".
			 "LEFT JOIN rbac_ua ON usr_data.usr_id=rbac_ua.usr_id ".
			 "WHERE usr_data.usr_id='".$this->id."'";
		$r = $this->ilias->db->query($q);

		if ($r->numRows() > 0)
		{
			$data = $r->fetchRow(DB_FETCHMODE_ASSOC);

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

			//pda support
			if (strpos($_SERVER["HTTP_USER_AGENT"],"Windows CE") > 0)
			{
				$this->skin = "pda";
			}
			else
			{
				$this->skin = $this->prefs["skin"];
			}

			//check style-setting (skins could have more than one stylesheet
			if ($this->prefs["style"] == "" || file_exists($this->ilias->tplPath."/".$this->skin."/".$this->prefs["style"].".css") == false)
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
			 $this->ilias->raiseError("<b>Error: There is no dataset with id ".$this->id."!</b><br />class: ".get_class($this)."<br />Script: ".__FILE__."<br />Line: ".__LINE__, $this->ilias->FATAL);
		}

		parent::read();
	}

	/**
	* loads a record "user" from array
	* @access	public
	* @param	array		userdata
	*/
	function assignData($a_data)
	{
		// basic personal data
		$this->setLogin($a_data["login"]);
		$this->setPasswd($a_data["passwd"]);
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
		$this->setEmail($a_data["email"]);
		$this->setHobby($a_data["hobby"]);

		// system data
		$this->setLastLogin($a_data["last_login"]);
		$this->setLastUpdate($a_data["last_update"]);
		$this->create_date	= $a_data["create_date"];
	}

	/**
	* TODO: drop fields last_update & create_date. redundant data in object_data!
	* saves a new record "user" to database
	* @access	public
	*/
	function saveAsNew ()
	{
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
		}

		$q = "INSERT INTO usr_data ".
			 "(usr_id,login,".$pw_field.",firstname,lastname,title,gender,".
			 "email,hobby,institution,department,street,city,zipcode,country,".
			 "phone_office,phone_home,phone_mobile,fax,last_login,last_update,create_date) ".
			 "VALUES ".
			 "('".$this->id."','".$this->login."','".$pw_value."', ".
			 "'".ilUtil::addSlashes($this->firstname)."','".ilUtil::addSlashes($this->lastname)."', ".
			 "'".ilUtil::addSlashes($this->utitle)."','".$this->gender."', ".
			 "'".$this->email."','".ilUtil::addSlashes($this->hobby)."', ".
			 "'".ilUtil::addSlashes($this->institution)."','".ilUtil::addSlashes($this->department)."','".ilUtil::addSlashes($this->street)."', ".
			 "'".ilUtil::addSlashes($this->city)."','".$this->zipcode."','".ilUtil::addSlashes($this->country)."', ".
			 "'".$this->phone_office."','".$this->phone_home."',".
			 "'".$this->phone_mobile."','".$this->fax."', 0, now(), now())";

		$this->ilias->db->query($q);

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
	function update ()
	{
		//$this->id = $this->data["Id"];

		$q = "UPDATE usr_data SET ".
			 "gender='".$this->gender."', ".
			 "title='".ilUtil::addSlashes($this->utitle)."', ".
			 "firstname='".ilUtil::addSlashes($this->firstname)."', ".
			 "lastname='".ilUtil::addSlashes($this->lastname)."', ".
			 "email='".$this->email."', ".
			 "hobby='".ilUtil::addSlashes($this->hobby)."', ".
			 "institution='".ilUtil::addSlashes($this->institution)."', ".
			 "department='".ilUtil::addSlashes($this->department)."', ".
			 "street='".ilUtil::addSlashes($this->street)."', ".
			 "city='".ilUtil::addSlashes($this->city)."', ".
			 "zipcode='".$this->zipcode."', ".
			 "country='".ilUtil::addSlashes($this->country)."', ".
			 "phone_office='".$this->phone_office."', ".
			 "phone_home='".$this->phone_home."', ".
			 "phone_mobile='".$this->phone_mobile."', ".
			 "fax='".$this->fax."', ".
			 "last_update=now() ".
			 "WHERE usr_id='".$this->id."'";

		$this->ilias->db->query($q);

		$this->writePrefs();

		parent::update();

		$this->read();

		return true;
	}

	/**
	* updates the login data of a "user"
	* // TODO set date with now() should be enough
	* @access	public
	*/
	function refreshLogin ()
	{
		$q = "UPDATE usr_data SET ".
			 "last_login = now() ".
			 "WHERE usr_id = '".$this->id."'";

		$this->ilias->db->query($q);
	}

	/**
	* updates password
	* @param	string	old password
	* @param	string	new password1
	* @param	string	new password2
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
		if (md5($a_old) != $this->passwd)
		{
			return false;
		}

		//update password
		$this->passwd = md5($a_new1);

		$q = "UPDATE usr_data SET ".
			 "passwd='".$this->passwd."' ".
			 "WHERE usr_id='".$this->id."'";
		$this->ilias->db->query($q);

		return true;
	}

	/**
	* reset password
	* @param	string	new password1
	* @param	string	new password2
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
		//DELETE
		$q = "DELETE FROM usr_pref ".
			 "WHERE usr_id='".$this->id."' ".
			 "AND keyword='".$a_keyword."'";
		$this->ilias->db->query($q);

		//INSERT
		if ($a_value != "")
		{
			$q = "INSERT INTO usr_pref ".
				 "(usr_id, keyword, value) ".
				 "VALUES ".
				 "('".$this->id."', '".$a_keyword."', '".$a_value."')";
			$this->ilias->db->query($q);
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
/*
	function selectUserpref()
	{
		$q="SELECT FROM urs_pref ".
			"WHERE usr_id='".$this->id."'";
		this->ilias->db->query($q);
		echo "Hallo World";
	}
*/
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
	function delete ()
	{
		global $rbacadmin;

		// delete user_account
		$this->ilias->db->query("DELETE FROM usr_data WHERE usr_id='".$this->getId()."'");

		// delete user_prefs
		$this->ilias->db->query("DELETE FROM usr_pref WHERE usr_id='".$this->getId()."'");

		// remove user from rbac
		$rbacadmin->removeUser($this->getId());

		// remove mailbox
		include_once ("classes/class.ilMailbox.php");
		$mailbox = new IlMailbox($this->getId());
		$mailbox->delete();

		// remove bookmarks
		// TODO: move this to class.ilBookmarkFolder
		$q = "DELETE FROM bookmark_tree WHERE tree='".$this->getId()."'";
		$this->ilias->db->query($q);

		$q = "DELETE FROM bookmark_data WHERE user_id='".$this->getId()."'";
		$this->ilias->db->query($q);

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
	function setFullname ($a_title = "",$a_firstname = "",$a_lastname = "")
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
	*/
	function getFullname()
	{
		return ilUtil::stripSlashes($this->fullname);
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
	* set password md5 encrypted
	* @access	public
	* @param	string	passwd
	*/
	function setPasswd($a_str, $a_type = IL_PASSWD_PLAIN)
	{
		$this->passwd = $a_str;
		$this->passwd_type = $a_type;
	}

	/**
	* get password (md5 hash)
	* @access	public
	*/
	function getPasswd()
	{
		return $this->passwd;
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
	* set hobbie
	* @access	public
	* @param	string	hobbie
	*/
	function setHobby($a_str)
	{
		$this->hobby = $a_str;
	}

	/**
	* get hobbie
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
		$this->prefs["language"] = $a_str;
	}

	/**
	* returns a 2char-language-string
	* @access	public
	* @return	string	language
	*/
	function getLanguage ()
	{
		 return $this->prefs["language"];
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
	function getLastLogin ()
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

	/*
	* check user id with login name
	* @access	public
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

	/**
	 * STATIC METHOD
	 * get the user_ids which correspond a search string
	 * @param	string search string
	 * @static
	 * @access	public
	 */
	function searchUsers($a_search_str)
	{
		// NO CLASS VARIABLES IN STATIC METHODS
		global $ilias;

		$query = "SELECT usr_id,login,firstname,lastname,email FROM usr_data ".
			"WHERE (login LIKE '%".$a_search_str."%' ".
			"OR firstname LIKE '%".$a_search_str."%' ".
			"OR lastname LIKE '%".$a_search_str."%' ".
			"OR email LIKE '%".$a_search_str."%') ".
			"AND usr_id != '".ANONYMOUS_USER_ID."'";

		$res = $ilias->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ids[] = array(
				"usr_id"     => $row->usr_id,
				"login"      => $row->login,
				"firstname"  => $row->firstname,
				"lastname"   => $row->lastname,
				"email"      => $row->email);
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
	function _search(&$a_search_obj)
	{
		global $ilBench;

		// NO CLASS VARIABLES IN STATIC METHODS

		// TODO CHECK IF ITEMS ARE PUBLIC VISIBLE

		$where_condition = $a_search_obj->getWhereCondition("like",array("login","firstname","lastname","title",
																		 "email","institution","street","city",
																		 "zipcode","country","phone_home","fax"));
		$in = $a_search_obj->getInStatement("usr_data.usr_id");

		$query = "SELECT usr_data.usr_id FROM usr_data ".
			"LEFT JOIN usr_pref USING (usr_id) ".
			$where_condition." ".
			$in." ".
			"AND usr_data.usr_id != '".ANONYMOUS_USER_ID."' ";
#			"AND usr_pref.keyword = 'public_profile' ";
#			"AND usr_pref.value = 'y'";

		$ilBench->start("Search", "ilObjUser_search");
		$res = $a_search_obj->ilias->db->query($query);
		$ilBench->stop("Search", "ilObjUser_search");

		$counter = 0;
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
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
	function getGroupMemberships($a_user_id="")
	{
		global $rbacreview, $rbacadmin, $ilias, $tree;

		if(strlen($a_user_id) > 0)
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

		foreach($roles as $role)
		{
			$ass_rolefolders = $rbacreview->getFoldersAssignedToRole($role);	//rolef_refids

			foreach($ass_rolefolders as $role_folder)
			{
				$node = $tree->getParentNodeData($role_folder);

				if($node["type"] =="grp")
				{
					$group =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);
					if($group->isMember($user_id)==true && !in_array($group->getId(), $grp_memberships) )
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
		global $rbacreview;
		
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

			$q = "UPDATE usr_session SET data='".$modified_data."' WHERE user_id = '".$a_user_id."'";
			$this->ilias->db->query($q);
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
	* (maybe it should be a method in all object classes)
	*/
	function getDesktopItems($a_type)
	{
		$items = array();

		switch ($a_type)
		{
			case "lm":
			case "glo":
			case "tst":
			case "dbk":
			case "slm":
			case "htlm":
				$q = "SELECT oref.ref_id, obj.title, parameters, oref.obj_id FROM desktop_item AS it, object_reference AS oref ".
					", object_data AS obj WHERE ".
					"it.item_id = oref.ref_id AND ".
					"oref.obj_id = obj.obj_id AND ".
					"it.type = '$a_type' AND ".
					"it.user_id = '".$this->getId()."' ".
					"ORDER BY title";
				$item_set = $this->ilias->db->query($q);
				while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
				{
					if($a_type == "glo")
					{
						$link = "content/glossary_presentation.php?ref_id=".$item_rec["ref_id"].
							"&obj_id=".$item_rec["parameters"];
						$edit_link = "content/glossary_edit.php?ref_id=".$item_rec["ref_id"].
							"&obj_id=".$item_rec["parameters"];
						$target = "bottom";
					}
					elseif ($a_type == "slm")
					{
						$link = "content/scorm_presentation.php?ref_id=".$item_rec["ref_id"].
							"&obj_id=".$item_rec["parameters"];
						$target = "bottom";
					}
					elseif ($a_type == "htlm")
					{
						$link = "content/fblm_presentation.php?ref_id=".$item_rec["ref_id"];
						$edit_link = "content/fblm_edit.php?ref_id=".$item_rec["ref_id"];
						$target = "ilContObj".$item_rec["obj_id"];
					}
					elseif ($a_type == "tst")
					{
						$link = "assessment/test.php?ref_id=".$item_rec["ref_id"]."&cmd=run";
						$target = "bottom";
						$whereclause .= sprintf("ref_fi = %s OR ", $this->ilias->db->quote($item_rec["ref_id"]));
					}
					else
					{
						$link = "content/lm_presentation.php?ref_id=".$item_rec["ref_id"].
							"&obj_id=".$item_rec["parameters"];
						$edit_link = "content/lm_edit.php?ref_id=".$item_rec["ref_id"].
							"&obj_id=".$item_rec["parameters"];
						$target = "ilContObj".$item_rec["obj_id"];
					}
					$items[] = array ("type" => $a_type, "id" => $item_rec["ref_id"], "title" => $item_rec["title"],
						"parameters" => $item_rec["parameters"],
						"link" => $link, "target" => $target, "edit_link" => $edit_link);
				}
				break;

			case "frm":
				$q = "SELECT oref.ref_id, obj.title FROM desktop_item AS it, object_reference AS oref ".
					", object_data AS obj WHERE ".
					"it.item_id = oref.ref_id AND ".
					"oref.obj_id = obj.obj_id AND ".
					"it.type = 'frm' AND ".
					"it.user_id = '".$this->getId()."' ".
					"ORDER BY title";
				$item_set = $this->ilias->db->query($q);
				while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
				{
					$items[] = array ("type" => $a_type, "id" => $item_rec["ref_id"], "title" => $item_rec["title"],
						"link" => "forums_threads_liste.php?ref_id=".$item_rec["ref_id"]."&backurl=forums", "target" => "bottom");
				}
				break;

			case "grp":
				$q = "SELECT oref.ref_id, obj.title FROM desktop_item AS it, object_reference AS oref ".
					", object_data AS obj WHERE ".
					"it.item_id = oref.ref_id AND ".
					"oref.obj_id = obj.obj_id AND ".
					"it.type = 'grp' AND ".
					"it.user_id = '".$this->getId()."' ".
					"ORDER BY title";
				$item_set = $this->ilias->db->query($q);
				while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
				{
					$items[] = array ("type" => $a_type, "id" => $item_rec["ref_id"], "title" => $item_rec["title"],
						"link" => "group.php?ref_id=".$item_rec["ref_id"], "target" => "bottom");
				}
				break;

			case "chat":
				$q = "SELECT oref.ref_id, obj.title FROM desktop_item AS it, object_reference AS oref ".
					", object_data AS obj WHERE ".
					"it.item_id = oref.ref_id AND ".
					"oref.obj_id = obj.obj_id AND ".
					"it.type = 'chat' AND ".
					"it.user_id = '".$this->getId()."' ".
					"ORDER BY title";
				$item_set = $this->ilias->db->query($q);
				while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
				{
					$items[] = array ("type" => $a_type, "id" => $item_rec["ref_id"], "title" => $item_rec["title"],
						"link" => "chat/chat_rep.php?ref_id=".$item_rec["ref_id"], "target" => "bottom");
				}
				break;
			default:
				break;
		}

		switch ($a_type)
		{
			case "tst":
				// get the users test status from the database
				global $ilUser;

				$whereclause = preg_replace("/ OR $/", "", $whereclause);
				if ($whereclause) {
					$status_array = array();
					$whereclause = "WHERE ($whereclause) AND ";
					$q = sprintf("SELECT tst_tests.test_type_fi, tst_tests.starting_time, tst_tests.ref_fi as id, tst_tests.nr_of_tries, tst_active.tries FROM tst_tests, tst_active $whereclause tst_tests.test_id = tst_active.test_fi AND tst_active.user_fi = %s",
						$this->ilias->db->quote($ilUser->id)
					);
					$item_set = $this->ilias->db->query($q);
					while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_OBJECT)) {
						$status_array[$item_rec->id] = $item_rec;
					}
					foreach ($items as $key => $value) {
						$items[$key]["nr_of_tries"] = $status_array[$value["id"]]->nr_of_tries;
						$items[$key]["used_tries"] = $status_array[$value["id"]]->tries;
						if ($status_array[$value["id"]]->test_type_fi == 1) {
							// assessment test. check starting time
							if ($status_array[$value["id"]]->starting_time) {
								preg_match("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", $status_array[$value["id"]]->starting_time, $matches);
								$epoch_time = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
								$now = mktime();
								if ($now < $epoch_time) {
									$items[$key]["starting_time_not_reached"] = 1;
								}
							}
						}
					}
				}
				break;
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
			$objects[] = array ("id" => $obj["item_id"],
				"type" => $obj["type"], "title" => $obj["title"]);
		}
		return $objects;
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

		

} // END class ilObjUser
?>
