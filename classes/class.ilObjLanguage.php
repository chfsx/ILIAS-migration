<?php
/**
* Class ilObjLanguage
*
* @author Sascha Hofmann <shofmann@databay.de>
* @version $Id$
*
* @extends ilObject
* @package ilias-core
*/
class ilObjLanguage extends ilObject
{
	/**
	* separator of module, identifier & values
	* in language files
	*
	* @var		string
	* @access	private
	*/
	var $separator;
	var $lang_default;
	var $lang_user;
	var $lang_path;

	var $key;
	var $status;


	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjLanguage($a_id = 0, $a_call_by_reference = false)
	{
		global $lng;

		$this->type = "lng";
		$this->ilObject($a_id,$a_call_by_reference);

		$this->type = "lng";
		$this->key = $this->title;
		$this->status = $this->desc;
		$this->lang_default = $lng->lang_default;
		$this->lang_user = $lng->lang_user;
		$this->lang_path = $lng->lang_path;
		$this->separator = $lng->separator;
	}

	/**
	* get language key
	*
	* @return	string		language key
	*/
	function getKey()
	{
		return $this->key;
	}

	/**
	* get language status
	*
	* @return	string		language status
	*/
	function getStatus()
	{
		return $this->status;
	}

	/**
	* check if language is system language
	*/
	function isSystemLanguage()
	{
		if ($this->key == $this->lang_default)
			return true;
		else
			return false;
	}

	/**
	* check if language is system language
	*/
	function isUserLanguage()
	{
		if ($this->key == $this->lang_user)
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	* install current language
	*
	* @return	string	installed language key
	*/
	function install ()
	{
		$lang = getObject($this->id);
		$lang_key = $lang["title"];
		$lang_status = $lang["desc"];

		if ($lang_status != "installed")
		{
			if ($this->check())
			{
				// lang-file is ok. Flush data in db and...
				$this->flush();
				// ...re-insert data from lang-file
				$this->insert();
				// update information in db-table about available/installed languages
				$this->setTitle($lang_key);
				$this->setDescription("installed");
				$this->update();
				$this->optimizeData();
				$lang_installed[] = $lang_key;
				return $lang_key;
			}
		}
		return "";
	}

	/**
	* uninstall a language
	*
	* @return	string	uninstalled language key
	*/
	function uninstall ()
	{
		if (($this->status == "installed") && ($this->key != $this->lang_default) && ($this->key != $this->lang_user))
		{
			$this->flush();
			$this->setTitle($this->key);
			$this->setDescription("not_installed");
			$this->update();
			$this->resetUserLanguage($this->key);

			return $this->key;
		}
		return "";
	}

	/**
	* remove language data from database
	*/
	function flush()
	{
		$query = "DELETE FROM lng_data WHERE lang_key='".$this->key."'";
		$this->ilias->db->query($query);
	}

	/**
	* insert language data from file into database
	*/
	function insert ()
	{
		$tmpPath = getcwd();
		chdir($this->lang_path);

		$lang_file = "ilias_".$this->key.".lang";

		if ($lang_file)
		{
			// remove header first
			if ($content = $this->cut_header(file($lang_file)))
			{
				foreach ($content as $key => $val)
				{
					$separated = explode ($this->separator,trim($val));
					$num = count($separated);

					$query = "INSERT INTO lng_data ".
						 	 "(module,identifier,lang_key,value) ".
						 	 "VALUES ".
						 	 "('".$separated[0]."','".$separated[1]."','".$this->key."','".addslashes($separated[2])."')";
					$this->ilias->db->query($query);
				}
				$query = "UPDATE object_data SET ".
						 "last_update = now() ".
						 "WHERE title = '".$this->key."' ".
						 "AND type = 'lng'";
				$this->ilias->db->query($query);
			}
		}

		chdir($tmpPath);
	}

	/**
	* search ILIAS for users which have selected '$lang_key' as their prefered language and
	* reset them to default language (english). A message is sent to all affected users
	*
	* @param	string		$lang_key	international language key (2 digits)
	*/
	function resetUserLanguage($lang_key)
	{
		$q = "UPDATE usr_pref SET ".
			 "value = '".$this->lang_default."' ".
			 "WHERE keyword = 'language' ".
			 "AND value = '".$lang_key."'";
		$this->ilias->db->query($q);
	}

	/**
	* remove lang-file haeder information from '$content'
	*
	* This function seeks for a special keyword where the language information starts.
	* if found it returns the plain language information, otherwise returns false
	*
	* @param	string	$content	expecting an ILIAS lang-file
	* @return	string	$content	content without header info OR false if no valid header was found
	*/
	function cut_header ($content)
	{
		foreach ($content as $key => $val)
		{
			if (trim($val) == "<!-- language file start -->")
			{
				return array_slice($content,$key +1);
			}
	 	}

	 	return false;
	}

	/**
	* optimizes the db-table langdata
	*
	* @return	boolean	true on success
	*/
	function optimizeData ()
	{
		// optimize
		$q = "OPTIMIZE TABLE lng_data";
		$this->ilias->db->query($q);

		return true;
	}

	/**
	* validate the logical structure the lang file
	*
	* This function checks if a lang-file exists,
	* the file has a header and each lang-entry consist of exact three elements
	* (module,identifier,value)
	*
	* @return	string	system message
	*/
	function check ()
	{
		$tmpPath = getcwd();
		chdir ($this->lang_path);

		// compute lang-file name format
		$lang_file = "ilias_".$this->key.".lang";

		// file check
		if (!is_file($lang_file))
		{
			$this->ilias->raiseError("File not found: ".$lang_file,$this->ilias->error_obj->MESSAGE);
		}

		// header check
		if (!$content = $this->cut_header(file($lang_file)))
		{
			$this->ilias->raiseError("Wrong Header in ".$lang_file,$this->ilias->error_obj->MESSAGE);
		}

		// check (counting) elements of each lang-entry
		foreach ($content as $key => $val)
		{
			$separated = explode ($this->separator,trim($val));
			$num = count($separated);

			if ($num != 3)
			{
				$this->ilias->raiseError("Wrong parameter count in ".$lang_file."! Please check your language file!",$this->ilias->error_obj->MESSAGE);
			}
		}

		chdir($tmpPath);

		// no error occured
		return true;
	}
} // END class.LanguageObject
?>
