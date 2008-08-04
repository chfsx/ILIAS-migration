<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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

// note: the values are derived from ilObjCourse constants
// to enable easy migration from course view setting to container view setting

define('IL_CNTR_SORT_MANUAL',1);
define('IL_CNTR_SORT_TITLE',2);
define('IL_CNTR_SORT_ACTIVATION',3);


require_once "./classes/class.ilObject.php";

/**
* Class ilContainer
*
* Base class for all container objects (categories, courses, groups)
* 
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
* @extends ilObject
*/
class ilContainer extends ilObject
{
	// container view constants
	const VIEW_SESSIONS = 0;
	const VIEW_OBJECTIVE = 1;
	const VIEW_TIMING = 2;
	const VIEW_ARCHIVE = 3;
	const VIEW_SIMPLE = 4;
	const VIEW_BY_TYPE = 5;
	const VIEW_INHERIT = 6;

	
	const SORT_MANUAL = 1;
	const SORT_TITLE = 2;
	const SORT_ACTIVATION = 3;
	
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilContainer($a_id = 0, $a_call_by_reference = true)
	{
		parent::__construct($a_id, $a_call_by_reference);
	}
	
	
	
	/**
	* Create directory for the container.
	* It is <webspace_dir>/container_data.
	*/
	function createContainerDirectory()
	{
		$webspace_dir = ilUtil::getWebspaceDir();
		$cont_dir = $webspace_dir."/container_data";
		if (!is_dir($cont_dir))
		{
			ilUtil::makeDir($cont_dir);
		}
		$obj_dir = $cont_dir."/obj_".$this->getId();
		if (!is_dir($obj_dir))
		{
			ilUtil::makeDir($obj_dir);
		}
	}
	
	/**
	* Get the container directory.
	*
	* @return	string	container directory
	*/
	function getContainerDirectory()
	{
		return $this->_getContainerDirectory($this->getId());
	}
	
	/**
	* Get the container directory.
	*
	* @return	string	container directory
	*/
	function _getContainerDirectory($a_id)
	{
		return ilUtil::getWebspaceDir()."/container_data/obj_".$a_id;
	}
	
	/**
	* Get path for big icon.
	*
	* @return	string	icon path
	*/
	function getBigIconPath()
	{
		return ilContainer::_lookupIconPath($this->getId(), "big");
	}

	/**
	* Get path for small icon
	*
	* @return	string	icon path
	*/
	function getSmallIconPath()
	{
		return ilContainer::_lookupIconPath($this->getId(), "small");
	}

	/**
	* Get path for tiny icon
	*
	* @return	string	icon path
	*/
	function getTinyIconPath()
	{
		return ilContainer::_lookupIconPath($this->getId(), "tiny");
	}
	
	/**
	* Lookup a container setting.
	*
	* @param	int			container id
	* @param	string		setting keyword 
	*
	* @return	string		setting value
	*/
	function _lookupContainerSetting($a_id, $a_keyword)
	{
		global $ilDB;
		
		$q = "SELECT * FROM container_settings WHERE ".
				" id = ".$ilDB->quote($a_id)." AND ".
				" keyword = ".$ilDB->quote($a_keyword);
		$set = $ilDB->query($q);
		$rec = $set->fetchRow(DB_FETCHMODE_ASSOC);
		
		return $rec["value"];
	}

	function _writeContainerSetting($a_id, $a_keyword, $a_value)
	{
		global $ilDB;
		
		$q = "REPLACE INTO container_settings (id, keyword, value) VALUES".
			" (".$ilDB->quote($a_id).", ".
			$ilDB->quote($a_keyword).", ".
			$ilDB->quote($a_value).")";

		$ilDB->query($q);
	}
	
	/**
	* lookup icon path
	*
	* @param	int		$a_id		container object id
	* @param	string	$a_size		"big" | "small"
	*/
	function _lookupIconPath($a_id, $a_size = "big")
	{
		if ($a_size == "")
		{
			$a_size = "big";
		}
		
		$size = $a_size;
		
		if (ilContainer::_lookupContainerSetting($a_id, "icon_".$size))
		{
			$cont_dir = ilContainer::_getContainerDirectory($a_id);
			$file_name = $cont_dir."/icon_".$a_size.".gif";

			if (is_file($file_name))
			{
				return $file_name;
			}
		}
		
		return "";
	}

	/**
	* save container icons
	*/
	function saveIcons($a_big_icon, $a_small_icon, $a_tiny_icon)
	{
		global $ilDB;
		
		$this->createContainerDirectory();
		$cont_dir = $this->getContainerDirectory();
		
		// save big icon
		$big_geom = $this->ilias->getSetting("custom_icon_big_width")."x".
			$this->ilias->getSetting("custom_icon_big_height");
		$big_file_name = $cont_dir."/icon_big.gif";

		if (is_file($a_big_icon["tmp_name"]))
		{
			$a_big_icon["tmp_name"] = ilUtil::escapeShellArg($a_big_icon["tmp_name"]);
			$big_file_name = ilUtil::escapeShellArg($big_file_name);
			$cmd = ilUtil::getConvertCmd()." ".$a_big_icon["tmp_name"]."[0] -geometry $big_geom GIF:$big_file_name";
			system($cmd);
		}

		if (is_file($cont_dir."/icon_big.gif"))
		{
			ilContainer::_writeContainerSetting($this->getId(), "icon_big", 1);
		}
		else
		{
			ilContainer::_writeContainerSetting($this->getId(), "icon_big", 0);
		}
	
		// save small icon
		$small_geom = $this->ilias->getSetting("custom_icon_small_width")."x".
			$this->ilias->getSetting("custom_icon_small_height");
		$small_file_name = $cont_dir."/icon_small.gif";

		if (is_file($a_small_icon["tmp_name"]))
		{
			$a_small_icon["tmp_name"] = ilUtil::escapeShellArg($a_small_icon["tmp_name"]);
			$small_file_name = ilUtil::escapeShellArg($small_file_name);
			$cmd = ilUtil::getConvertCmd()." ".$a_small_icon["tmp_name"]."[0] -geometry $small_geom GIF:$small_file_name";
			system($cmd);
		}
		if (is_file($cont_dir."/icon_small.gif"))
		{
			ilContainer::_writeContainerSetting($this->getId(), "icon_small", 1);
		}
		else
		{
			ilContainer::_writeContainerSetting($this->getId(), "icon_small", 0);
		}

		// save tiny icon
		$tiny_geom = $this->ilias->getSetting("custom_icon_tiny_width")."x".
			$this->ilias->getSetting("custom_icon_tiny_height");
		$tiny_file_name = $cont_dir."/icon_tiny.gif";

		if (is_file($a_tiny_icon["tmp_name"]))
		{
			$a_tiny_icon["tmp_name"] = ilUtil::escapeShellArg($a_tiny_icon["tmp_name"]);
			$tiny_file_name = ilUtil::escapeShellArg($tiny_file_name);
			$cmd = ilUtil::getConvertCmd()." ".$a_tiny_icon["tmp_name"]."[0] -geometry $tiny_geom GIF:$tiny_file_name";
			system($cmd);
		}
		if (is_file($cont_dir."/icon_tiny.gif"))
		{
			ilContainer::_writeContainerSetting($this->getId(), "icon_tiny", 1);
		}
		else
		{
			ilContainer::_writeContainerSetting($this->getId(), "icon_tiny", 0);
		}

	}

	/**
	* remove big icon
	*/ 
	function removeBigIcon()
	{
		$cont_dir = $this->getContainerDirectory();
		$big_file_name = $cont_dir."/icon_big.gif";
		@unlink($big_file_name);
		ilContainer::_writeContainerSetting($this->getId(), "icon_big", 0);
	}
	
	/**
	* remove small icon
	*/ 
	function removeSmallIcon()
	{
		$cont_dir = $this->getContainerDirectory();
		$small_file_name = $cont_dir."/icon_small.gif";
		@unlink($small_file_name);
		ilContainer::_writeContainerSetting($this->getId(), "icon_small", 0);
	}
	
	/**
	* remove tiny icon
	*/ 
	function removeTinyIcon()
	{
		$cont_dir = $this->getContainerDirectory();
		$tiny_file_name = $cont_dir."/icon_tiny.gif";
		@unlink($tiny_file_name);
		ilContainer::_writeContainerSetting($this->getId(), "icon_tiny", 0);
	}
	
	/**
	* Get right column
	*
	* @return	object		column object
	*/ 
	function getFirstColumn()
	{
		$col_id = ilContainer::_lookupContainerSetting($this->getId(), "first_column");
		if ($col_id > 0)
		{
			include_once("./Services/Blocks/class.ilBlockColumn.php");
			$block_column = new ilBlockColumn($col_id);
			return $block_column;
		}
		return false;
	}
	

	/**
	 * clone all objects according to this container
	 *
	 * @param string $session_id
	 * @param string $client_id
	 * @param string $new_type
	 * @param int $ref_id
	 * @param int $clone_source
	 * @param array $options
	 * @return new refid if clone has finished or parameter ref id if cloning is still in progress
	 */
	public function cloneAllObject($session_id, $client_id, $new_type, $ref_id, $clone_source, $options, $soap_call = false)
	{
		global $ilLog;
		
		include_once('classes/class.ilLink.php');
		include_once('Services/CopyWizard/classes/class.ilCopyWizardOptions.php');
		
		global $ilAccess,$ilErr,$rbacsystem,$tree,$ilUser;
			
		// Save wizard options
		$copy_id = ilCopyWizardOptions::_allocateCopyId();
		$wizard_options = ilCopyWizardOptions::_getInstance($copy_id);
		$wizard_options->saveOwner($ilUser->getId());
		$wizard_options->saveRoot($clone_source);
			
		// add entry for source container
		$wizard_options->initContainer($clone_source, $ref_id);
		
		foreach($options as $source_id => $option)
		{
			$wizard_options->addEntry($source_id,$option);
		}
		$wizard_options->read();
		$wizard_options->storeTree($clone_source);
		#print_r($options);
		// Duplicate session to avoid logout problems with backgrounded SOAP calls
		$new_session_id = duplicate_session($session_id);
		// Start cloning process using soap call
		include_once 'Services/WebServices/SOAP/classes/class.ilSoapClient.php';

		$soap_client = new ilSoapClient();
		$soap_client->setTimeout(30);
		$soap_client->setResponseTimeout(30);
		$soap_client->enableWSDL(true);

		$ilLog->write(__METHOD__.': Trying to call Soap client...');
		if($soap_client->init())
		{
			$ilLog->write(__METHOD__.': Calling soap clone method...');
			$res = $soap_client->call('ilClone',array($new_session_id.'::'.$client_id, $copy_id));
		}
		else
		{
			$ilLog->write(__METHOD__.': SOAP call failed. Calling clone method manually. ');
			$wizard_options->disableSOAP();
			$wizard_options->read();			
			include_once('./webservice/soap/include/inc.soap_functions.php');
			$res = ilSoapFunctions::ilClone($new_session_id.'::'.$client_id, $copy_id);
		}
		// Check if copy is in progress or if this has been called by soap (don't wait for finishing)
		if($soap_call || ilCopyWizardOptions::_isFinished($copy_id))
		{
			return $res;
		}
		else
		{
			return $ref_id;
		}	
	}
	
	/**
	* Get container view mode
	*/
	function getViewMode()
	{
		return ilContainer::VIEW_BY_TYPE;
	}

	/**
	* Get order type default implementation
	*/
	function getOrderType()
	{
		return IL_CNTR_SORT_TITLE;
		
		// @todo
		//include_once("./Services/Container/classes/class.ilContainerSortingSettings.php");
		//ilContainerSortingSettings::_lookupSortMode($this->getId());
	}
	
	/**
	* Get subitems of container
	*
	* @return	array
	*/
	function getSubItems($a_include_hidden_files = false, $a_include_side_block = false)
	{
		global $objDefinition, $ilBench, $tree;

		if (is_array($this->items[(int) $a_include_hidden_files][(int) $a_include_side_block]))
		{
			return $this->items[(int) $a_include_hidden_files][(int) $a_include_side_block];
		}

		$type_grps = $this->getGroupedObjTypes();

		$objects = $tree->getChilds($this->getRefId(), "title");

		$found = false;

		include_once('Services/Container/classes/class.ilContainerSorting.php');
		$sort = new ilContainerSorting($this->getId());

		// get items attached to a session
		include_once './Modules/Session/classes/class.ilEventItems.php';
		$event_items = ilEventItems::_getItemsOfContainer($this->getRefId());
		
		foreach ($objects as $key => $object)
		{
			// hide object types in devmode
			if ($objDefinition->getDevMode($object["type"]) || $object["type"] == "adm"
				|| $object["type"] == "rolf")
			{
				continue;
			}

			// Do not display hidden files
			require_once 'Modules/File/classes/class.ilObjFileAccess.php';
			if (!$a_include_hidden_files && ilObjFileAccess::_isFileHidden($object['title']))
			{
				continue;
			}

			// filter out items that are attached to an event
			if (in_array($object['ref_id'],$event_items))
			{
				continue;
			}
			
			// filter side block items
			if (!$a_include_side_block && $objDefinition->isSideBlock($object['type']))
			{
				continue;
			}
			
			// group object type groups together (e.g. learning resources)
			$type = $objDefinition->getGroupOfObj($object["type"]);
			if ($type == "")
			{
				$type = $object["type"];
			}
			
			$this->addAdditionalSubItemInformation($object);
			
			$this->items[$type][$key] = $object;
			$this->items["_all"][$key] = $object;
			if ($object["type"] != "sess")
			{
				$this->items["_non_sess"][$key] = $object;
			}
		}

		$this->items[(int) $a_include_hidden_files][(int) $a_include_side_block]
			= $sort->sortTreeDataByType($this->items);
		return $this->items;
	}
	
	/**
	* Add additional information to sub item, e.g. used in
	* courses for timings information etc.
	*/
	function addAdditionalSubItemInformation(&$object)
	{
	}
	
	/**
	* Get grouped repository object types.
	*
	* @return	array	array of object types
	*/
	function getGroupedObjTypes()
	{
		global $objDefinition;
		
		if (empty($this->type_grps))
		{
			$this->type_grps = $objDefinition->getGroupedRepositoryObjectTypes($this->getType());
		}
		return $this->type_grps;
	}
	
	/**
	* Check whether page editing is allowed for container
	*/
	function enablePageEditing()
	{
		global $ilSetting;
		
		// @todo: this will need a more general approach
		if ($ilSetting->get("enable_cat_page_edit"))
		{
			return true;
		}
	}
	
} // END class ilContainer
?>
