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
* INIFile Parser
*
* Description:
*
* A Simpe Ini File Implementation to keep settings 
* in a simple file instead of in a DB
* Based upon class.INIfile.php by Mircho Mirev <mircho@macropoint.com>
* 
* Usage Examples: 
* $ini = new IniFile("./ini.ini");
* Read entire group in an associative array
* $grp = $ini->read_group("MAIN");
* //prints the variables in the group
* if ($grp)
* for(reset($grp); $key=key($grp); next($grp))
* {
* echo "GROUP ".$key."=".$grp[$key]."<br>";
* }
* //set a variable to a value
* $ini->setVariable("NEW","USER","JOHN");
* //Save the file
* $ini->save_data();
*
* @author Mircho Mirev <mircho@macropoint.com>
* @author Peter Gabriel <peter@gabriel-online.net>
* @version $Id$
*
* @package application
*/
class ilIniFile
{
	/**
	* name of file
	* @var string
	* @access public
	*/
	var $INI_FILE_NAME = "";

	/**
	* error var
	* @var string
	* @access public
	*/
	var $ERROR = "";

	/**
	* sections in ini-file
	* @var array
	* @access public
	*/
	var $GROUPS = array();

	/**
	* actual section
	* @var string
	* @access public
	*/
	var $CURRENT_GROUP = "";

	/**
	* Constructor
	* @access	public
	* @param	string		name of file to be parsed
	*/
	function ilIniFile($a_iniFileName)
	{
		//check if a filename is given
		if (empty($a_iniFileName))
		{
			$this->error("no_file_given");
			return false;
		}

		$this->INI_FILE_NAME = $a_iniFileName;
		return true;
	}

	/**
	* read
	* @access	private
	*/
	function read()
	{
		//check if file exists
		if (!file_exists($this->INI_FILE_NAME))
		{
			$this->error("file_does_not_exist");
			return false;
		}
		else
		{
			//parse the file
			if ($this->parse()==false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	* load and parse an inifile
	* @access	private
	*/
	function parse()
	{
		//use php4 function parse_ini_file
		$this->GROUPS = parse_ini_file($this->INI_FILE_NAME, true);

		//check if groups are filled
		if ($this->GROUPS == false)
		{
			$this->error("file_not_accessible");
			return false;
		}
		//set current group
		$temp = array_keys($this->GROUPS);
		$this->CURRENT_GROUP = $temp[count($temp)-1];
		return true;
	}

	/**
	* parse data
	* @access	private
 	* @param	array
	*/
	function parse_data($a_data)
	{
		if (ereg("\[([[:alnum:]]+)\]",$a_data,$out))
		{
			$this->CURRENT_GROUP= trim($out[1]);
		}
		elseif (!empty($a_data))
		{
			$split_data = split("=", $a_data);
			$this->GROUPS[$this->CURRENT_GROUP][trim($split_data[0])]=trim($split_data[1]);
		}
	}

	/**
	* DESCRIPTION MISSING
	* @access	public
	* @param	string
	*/
	function setContent($a_data)
	{
		$this->GROUPS = $a_data;
		return true;
	}

	/**
	* save ini-file-data to filesystem
	* @access	private
	*/
	function write()
	{
		$fp = @fopen($this->INI_FILE_NAME,"w");
		
		if (empty($fp))
		{
			$this->error("Cannot create file $this->INI_FILE_NAME");
			return false;
		}

		//write php tags (security issue)
		$result = fwrite($fp, "<?php\n");

		$groups = $this->readGroups();
		$group_cnt = count($groups);
		
		for ($i=0; $i<$group_cnt; $i++)
		{
			$group_name = $groups[$i];
			//prevent empty line at beginning of ini-file
			if ($i==0)
			{
				$res = sprintf("[%s]\r\n",$group_name);
			}
			else
			{
				$res = sprintf("\r\n[%s]\r\n",$group_name);
			}
			
			$result = fwrite($fp, $res);
			$group = $this->readGroup($group_name);
			
			for (reset($group); $key=key($group);next($group))
			{
				$res = sprintf("%s = %s\r\n",$key,$group[$key]);
				$result = fwrite($fp,$res);
			}
		}
		
		//write php tags (security issue)
		$result = fwrite($fp, "*/ ?>");
		
		fclose($fp);

		return true;
	}

	/**
	* returns the content of IniFile
	* @access	public
	* @return	string		content
	*/
	function show()
	{
		$groups = $this->readGroups();
		$group_cnt = count($groups);
		
		//clear content
		$content = "";
		
		// go through all groups
		for ($i=0; $i<$group_cnt; $i++)
		{
			$group_name = $groups[$i];
			//prevent empty line at beginning of ini-file
			if ($i==0)
			{
				$content = sprintf("[%s]\n",$group_name);
			}
			else
			{
				$content .= sprintf("\n[%s]\n",$group_name);
			}

			$group = $this->readGroup($group_name);
			
			//go through group an display all variables
			for (reset($group); $key=key($group);next($group))
			{
				$content .= sprintf("%s = %s\n",$key,$group[$key]);
			}
		}

		return $content;
	}
	
	/**
	* returns number of groups
	* @access	public	
	*/
	function getGroupCount()
	{
		return count($this->GROUPS);
	}
	
	/**
	* returns an array with the names of all the groups
	* @access	public
	*/
	function readGroups()
	{
		$groups = array();
		for (reset($this->GROUPS);$key=key($this->GROUPS);next($this->GROUPS))
		{
			$groups[]=$key;
		}

		return $groups;
	}
	
	/**
	* checks if a group exists
	* @access	public
	* @param	string		group name
	*/
	function groupExists($a_group_name)
	{
		$group = $this->GROUPS[$a_group_name];

		if (!is_array($group))
		{
			 return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	* returns an associative array of the variables in one group
	* @access	public	
	* @param	string		group
	*/
	function readGroup($a_group)
	{
		$group_array = $this->GROUPS[$a_group];
		if (!empty($group_array))
		{
			return $group_array;
		}
		else 
		{
			$this->Error("Group '".$a_group."' does not exist");
			return false;
		}
	}
	
	/**
	* adds a new group
	* @access	public
	* @param	string		group name
	*/
	function addGroup($a_group_name)
	{
		$new_group = $this->GROUPS[$a_group_name];

		if (empty($new_group))
		{
			$this->GROUPS[$a_group_name] = array();
		}
		else
		{
			$this->Error("Group '".$a_group_name."' exists");
		}
	}
	
	/**
	* reads a single variable from a group
	* @access	public
	* @param	string		group
	* @param	string		??
	*/
	function readVariable($a_group, $a_var_name)
	{
		$var_value = trim($this->GROUPS[$a_group][$a_var_name]);

		if (!empty($var_value))
		{
			return $var_value;
		}
		else
		{
			$this->Error("'".$a_var_name."' does not exist in '".$a_group."'");
			return false;
		}
	}
	
	/**
	* sets a variable in a group
	* @access	public
	* @param	string
	* @param	string
	* @param	string
	*/
	function setVariable($a_group, $a_var_name, $a_var_value)
	{
		if ($this->groupExists($a_group))
		{
			$this->GROUPS[$a_group][$a_var_name] = $a_var_value;
		}
	}	
	
	/**
	* error handling
	* @access	public
	* @param	string
	*/
	function error($a_errmsg)
	{
		$this->ERROR = $a_errmsg;
		return true;
	}
} //END class.IniFile
?>
