<?php
/**
* Base class for all file (directory) operations
* This class is abstract and needs to be extended
*  
* @author	Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* @package	mail
*/

class ilFile
{
	/**
	* Path of directory
	* @var string path
	* @access private
	*/
	var $path;

	/**
	* ilias object
	* @var object Ilias
	* @access public
	*/
	var $ilias;


	/**
	* Constructor
	* get ilias object
	* @access	public
	*/
	function ilFile()
	{
		global $ilias;

		$this->ilias = &$ilias;
	}

	/**
	* delete trailing slash of path variables
	* @param string path
	* @access	public
	* @return string path
	*/
	function deleteTrailingSlash($a_path)
	{
		// DELETE TRAILING '/'
		if(substr($a_path,-1) == '/')
		{
			$a_path = substr($a_path,-1);
		}
		return $a_path;
	}
}
?>