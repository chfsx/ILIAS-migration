<?php
// include pear
include_once("DB.php");

/**
* Language class
*
* @author Peter Gabriel <pgabriel@databay.de>
* @version $Id$
* @package ilias-core
*/
class Language
{
	var $LANGUAGESDIR = "./lang";
	
	/**
	 * constructor
	 * @param string lng languagecode (two characters), e.g. "de", "en", "in"
	 */
	function Language($lng)
	{
        $txt = @file($this->LANGUAGESDIR."/".$lng.".lang");
        $this->text = array();
        if (is_array($txt)==true)
        {
			foreach($txt as $row)
			{
				if ($row[0]!="#")
				{
					$a = explode("#:#",trim($row));
					$this->text[$lng][trim($a[0])] = trim($a[1]);
				}
			}
			$this->lng = $lng;
			return true;
        }
        else
        {
			return false;
        }
	}
	
	/**
	 * gets the text for a given topic
	 *
	 * @param string topic
	 * @return string text clear-text
	 * @access public
	 */
	function txt($topic)
	{
        return $this->text[$this->lng][$topic];
	}

	/**
	 * get all languages in the system
	 *
	 * @param void
	 * @return array langs
	 * @access public
	 */

	function getAllLanguages()
	{
		//initialization
		$langs = array();
		//search for all languages files
		if ($dir = @opendir($this->LANGUAGESDIR))
		{
			while ($file = readdir($dir))
			{
				if (strpos($file,".lang") > 0)
				{
					$id = substr($file,0,2);
					//read the first line from each lang-file, first line is the name of language
					$fp = fopen ($this->LANGUAGESDIR."/".$file, "r");
					$name = fgets($fp,1000);
					fclose($fp);
					$langs[] = array( "id" => $id,
									  "name" => $name
						);
				} //if
			}  //while
			closedir($dir);
		} //if
		return $langs;
	} //function

	/** 
	 * formatting function for dates
	 * @access public
	 * @param string date date, given in sql-format YYYY-MM-DD
	 */
    function formatDate($str)
	{
		$d = substr($str,8,2);
		$m = substr($str,5,2);
		$y = substr($str,0,4);
		switch ($this->lng)
		{
			case "en":
				return $y."/".$m."/".$d;
			case "de":
				return $d.".".$m.".".$y;
		}
	}

	/**
	 * user agreement
	 */
	function getUserAgreement()
	{
		return "here is da user agreement";
	}
} //class
?>