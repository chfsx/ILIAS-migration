<?php
/**
* user class for ilias
* 
* @author Sascha Hofmann <shofmann@databay.de>
* @author Stefan Meyer <smeyer@databay.de>
* @author Peter Gabriel <pgabriel@databay.de>
* @version $Id$
* 
* @extends PEAR
* @package ilias-core
*/
class User extends PEAR
{
	/**
	* User Id
	*
	* @var		integer
	* @access	public
	*/
	var $Id;					

	/**
	* Contains fixed Userdata
	*
	* @var		array
	* @access	public
	*/
	var $data;

	/**
	* Contains variable Userdata (Prefs, Settings)
	*
	* @var		array
	* @access	public
	*/
	var $prefs;
	
	/**
	* database handler
	*
	* @var		object	DBx
	* @access	private
	*/	
	var $db;
	
	/**
	* error handling
	* @var	object	error
	* @acess private
	*/
	var $error_class;

	/**
	* Constructor
	*
	* setup an user object
	*
	* @param object database handler
	* @param string UserID
	*/
	function User(&$dbhandle, $AUsrId = "")
	{
		$this->PEAR();
		$this->error_class = new ErrorHandling();
		$this->setErrorHandling(PEAR_ERROR_CALLBACK,array($this->error_class,'errorHandler'));

		// Initiate variables
		$this->db =& $dbhandle;
		$this->data = array();

		if (!empty($AUsrId) and ($AUsrId > 0))
		{
		    $this->Id = $AUsrId;
		    $this->getUserdata();
		}
	}

	/**
	* loads a record "user" from database
	*
	* @access private
	*/
	function getUserdata ()
	{
		global $ilias;
		
		$query = "SELECT * FROM user_data
				 LEFT JOIN rbac_ua
				 ON user_data.usr_id=rbac_ua.usr_id
				 WHERE user_data.usr_id='".$this->Id."'";	
		
		$res = $this->db->query($query);
		
		if ($res->numRows() > 0)
		{
			$data = $res->FetchRow(DB_FETCHMODE_ASSOC);

			$this->data = array(
				"Id"		 => $this->Id,
				"login"      => $data["login"],
				"passwd"     => $data["passwd"],
				"Gender"	 => $data["gender"],
				"Title"      => $data["title"],
				"FirstName"  => $data["firstname"],
				"SurName"    => $data["surname"],
				"Email"      => $data["email"],
				"Role"       => $data["rol_id"],
				"LastLogin"  => $data["last_login"],
			);

			//get userpreferences from user_pref table
			$this->getPrefs();
			//set language to default if not set
			if ($this->prefs["language"] == "")
				$this->prefs["language"] = $ilias->ini->readVariable("language","default");
			//set template to default if not set
//			if ($this->prefs["skin"] == "")
//			{
//			 	$this->prefs["skin"] = $ilias->ini->readVariable("")
//			}
		}
		else
		{
			 $this->raiseError("<b>Error: There is no dataset with id ".$this->Id."!</b><br>class: ".get_class($this)."<br>Script: ".__FILE__."<br>Line: ".__LINE__, $this->FATAL);
		}
	}
	
	/**
	* loads a record "user" from array
	*
	* @access private
	* @param array		userdata
	*/
	function setUserdata ($AUserdata)
	{
		$this->data = $AUserdata;
	}
	
	/**
	* returns a 2char-language-string
	*
	* @access public
	* @return string language
	*/
	function getLanguage ()
	{
		 return $this->data["language"];
	}

	/**
	* saves a new record "user" to database
	*
	* public method
	*
	*/
	function saveAsNew ()
	{
		// fill user_data
		$query = "INSERT INTO user_data
				 (usr_id,login,passwd,
				 firstname,surname,
				 title,gender,
				 email,
				 last_login,last_update,create_date)
				 VALUES
				 ('".$this->data["Id"]."','".$this->data["Login"]."','".md5($this->data["Passwd"])."',
				  '".$this->data["FirstName"]."','".$this->data["SurName"]."',
				  '".$this->data["Title"]."','".$this->data["Gender"]."',
				  '".$this->data["Email"]."',
				  ',0,now(),now())";

		$res = $this->db->query($query);

		$this->Id = $this->data["Id"];
	}

	/**
	* updates a record "user" and write it into database
	*
	* public method
	*
	*/
	function update ()
	{
		$this->Id = $this->data["Id"];

		$query = "UPDATE user_data SET
				 gender='".$this->data[Gender]."',
				 title='".$this->data[Title]."',
				 firstname='".$this->data[FirstName]."',
				 surname='".$this->data[SurName]."',
				 email='".$this->data[Email]."'
				 WHERE usr_id='".$this->Id."'";
		$this->db->query($query);
		
		$this->writePrefs();
		
		$this->getUserData();

		return true;
	}
	
	/**
	* write userpref to user table
	* @param string keyword
	* @param string value
	* @author Peter Gabriel <pgabriel@databay.de>
	*/
	function writePref($keyword, $value)
	{
		//DELETE
		$sql = "DELETE FROM user_pref 
				WHERE usr_id='".$this->Id."'
				AND keyword='".$keyword."'";
		$r = $this->db->query($sql);
		//INSERT
		$sql = "INSERT INTO user_pref 
				(usr_id, keyword, value_str)
				VALUES
				('".$this->Id."', '".$keyword."', '".$value."')";
		$r = $this->db->query($sql);
	}

	/**
	* write all userprefs
	* @access	public
 	* @author 	Peter Gabriel <pgabriel@databay.de>
	*/
	function writePrefs()
	{
		//DELETE
		$sql = "DELETE FROM user_pref 
			WHERE usr_id='".$this->Id."'";
		$r = $this->db->query($sql);

		foreach ($this->prefs as $keyword => $value)
		{
			//INSERT
			$sql = "INSERT INTO user_pref 
				(usr_id, keyword, value_str)
				VALUES
				('".$this->Id."', '".$keyword."', '".$value."')";
			$r = $this->db->query($sql);
		}
	}


	/**
	* get all user preferences
	* @access	public
	* @author	Peter Gabriel <pgabriel@databay.de>
	* @return	interger	number of preferences
	*/
	function getPrefs()
	{
		$this->prefs = array();
		
		$query = "SELECT * FROM user_pref
			 WHERE usr_id='".$this->Id."'";	
		$r = $this->db->query($query);
		while($row = $r->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$this->prefs[$row["keyword"]] = $row["value_str"];
		} // while	 

		return $r->numRows();
	}
	
	/**
	* deletes a user
	* @access	public
	* @param	integer		user_id
	*/
	function delete ($AUsrId = 0)
	{
		if (empty($AUsrId))
		{
			 $id = $this->Id;
		}
		else
		{
			 $id = $AUsrId;
		}
		
		// delete user_account
		$this->db->query("DELETE FROM user_data WHERE usr_id='$id'");
		
		// delete user-role relation
		$this->db->query("DELETE FROM rbac_ua WHERE usr_id='$id' AND rol_id='$rol_id'");
		
		// delete obj_data entry
		$this->db->query("DELETE FROM object_data WHERE obj_id='$id'");
	}
	
	/**
	* builds a string with Title + Firstname + Surname
	* 
	* @access	public
	* @param	string	title
	* @param	string	firstname
	* @param	string	surname
	* @return	string	fullname
	*/
	function buildFullName ($ATitle="",$AFirstName="",$ASurName="")
	{
		$num_args = func_num_args();
		
		switch ($num_args)
		{
			case 0:
				if ($this->data["Title"])
				{
					$FullName = $this->data["Title"]." ";
				}
				if ($this->data["FirstName"])
				{
					$FullName .= $this->data["FirstName"]." ";
				}
				
				$FullName .= $this->data["SurName"];				
				break;
				
			case 3:
				if ($ATitle)
				{
					$FullName = $ATitle." ";
				}
				
				if ($AFirstName)
				{
					$FullName .= $AFirstName." ";
				}
				
				$FullName .= $ASurName;			
				break;
				
			default:
				// Falsche Parameterzahl
				break;		
		}
		
		return $FullName;
	}

	/**
	* get last read lessons
	* @access	public
	* @return	array	lessons
	*/
	function getLastVisitedLessons()
	{
		global $lng;

		//initialize array
		$lessons = array();
		//query
		$sql = "SELECT * FROM lessons
				WHERE user_fk='".$this->id."'
				AND read=1";
		$lessons[] = array(
			"id" => 1,
			"title" => "Lesson 1",
			"content" => "This is Lesson One",
			"page" => "Contents",
			"pageid" => "1",
			"datetime" => $lng->fmtDate(date("Y-m-d"))
			);
		return $lessons;
	}

	/**
	* get all lessons
	* @access	public
	* @return	array	lessons
	*/
	function getLessons()
	{
		//initialize array
		$lessons = array();

		//query
		$sql = "SELECT * FROM lessons
				WHERE user_fk='".$this->id."'
				AND read=1";

/*		$lessons[] = array(
			"id" => 1,
			"title" => "Lesson 1",
			"content" => "This is Lesson One",
			"page" => "Contents",
			"pageid" => "1",
			"datetime" => $lng->fmtDate(date("Y-m-d"))
			);
*/
		return $lessons;
	}

	/**
	* get courses the user has access to
	* @access	public
	* @return	array	lessons
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
			"datetime" => $lng->fmtDate(date("Y-m-d"))
			);
		return $courses;
	}

	/**
	* get own bookmarks
	* @access	public
	* @return	array	bookmarks
	*/
	function getBookmarks()
	{
		//initialize array
		$bookmarks = array();
		//query
		$sql = "SELECT * FROM bookmarks";

		$bookmarks[] = array(
			"id" => 1,
			"url" => "http://www.gutenberg.de",
			"desc" => "project gutenberg",
			);

		return $bookmarks;
	}

	/**
	* get own bookmarks
	* @access	public
	* @return	array	bookmarks
	*/
	function getBookmarkFolder()
	{
		//initialize array
		$bookmarks = array();
		//query
		$sql = "SELECT * FROM bookmarks";

		$bookmarks[] = array(
			"id" => 1,
			"name" => "sonstiges",
			);

		return $bookmarks;
	}

	/**
	* get literature bookmarks
	* @access	public
	* @return	array	lessons
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
	* set first name
	* @access	public
	* @param	string	str
	*/
	function setFirstName($str)
	{
		$this->data["FirstName"] = $str;
	}

	/**
	* set last name
	* @access	public
	* @param	string	str
	*/
	function setLastName($str)
	{
		$this->data["LastName"] = $str;
	}

	/**
	* set gender
	* @access	public
	* @param	string	str
	*/
	function setGender($str)
	{
		$this->data["Gender"] = $str;
	 }

	/**
	* set title
	* @access	public
	* @param	string	str
	*/
	function setTitle($str)
	{
		$this->data["Title"] = $str;
	}

	/**
	* set email
	* @access	public
	* @param	string	str
	*/
	function setEmail($str)
	{
		$this->data["Email"] = $str;
	}

	/**
	* set language
	* @access	public
	* @param	string	str
	*/
	function setLanguage($str)
	{
		$this->prefs["language"] = $str;
	}
} // END class.User
?>
