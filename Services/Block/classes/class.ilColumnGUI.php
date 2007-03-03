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

define ("IL_COL_LEFT", "left");
define ("IL_COL_RIGHT", "right");
define ("IL_COL_CENTER", "center");

define ("IL_SCREEN_SIDE", "");
define ("IL_SCREEN_CENTER", "center");
define ("IL_SCREEN_FULL", "full");

/**
* Column user interface class. This class is used on the personal desktop,
* the info screen class and witin container classes.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilColumnGUI:
*/
class ilColumnGUI
{
	protected $side = IL_COL_RIGHT;
	protected $type;
	protected $enableedit = false;
	protected $repositorymode = false;
	protected $repositoryitems = array();
	
	// all blocks that are repository objects
	protected $rep_block_types = array("feed");
	
	//
	// This two arrays may be replaced by some
	// xml or other magic in the future...
	//
	
	static protected $locations = array(
		"ilNewsForContextBlockGUI" => "Services/News/",
		"ilPDNotesBlockGUI" => "Services/Notes/",
		"ilPDMailBlockGUI" => "Services/Mail/",
		"ilUsersOnlineBlockGUI" => "Services/PersonalDesktop/",
		"ilPDSysMessageBlockGUI" => "Services/Mail/",
		"ilPDSelectedItemsBlockGUI" => "Services/PersonalDesktop/",
		"ilBookmarkBlockGUI" => "Services/PersonalDesktop/",
		"ilPDNewsBlockGUI" => "Services/News/",
		"ilExternalFeedBlockGUI" => "Services/Block/",
		"ilPDExternalFeedBlockGUI" => "Services/Feeds/",
		"ilHtmlBlockGUI" => "Services/Block/",
		"ilPDFeedbackBlockGUI" => "Services/Feedback/");
	
	static protected $block_types = array(
			"ilPDMailBlockGUI" => "pdmail",
			"ilPDNotesBlockGUI" => "pdnotes",
			"ilUsersOnlineBlockGUI" => "pdusers",
			"ilPDNewsBlockGUI" => "pdnews",
			"ilBookmarkBlockGUI" => "pdbookm",
			"ilNewsForContextBlockGUI" => "news",
			"ilExternalFeedBlockGUI" => "feed",
			"ilPDExternalFeedBlockGUI" => "pdfeed",
			"ilPDFeedbackBlockGUI" => "pdfeedb",
			"ilPDSysMessageBlockGUI" => "pdsysmess",
			"ilPDSelectedItemsBlockGUI" => "pditems",
			"ilHtmlBlockGUI" => "html"
		);

		
	protected $default_blocks = array(
		"cat" => array("ilNewsForContextBlockGUI" => IL_COL_RIGHT),
		"crs" => array("ilNewsForContextBlockGUI" => IL_COL_RIGHT),
		"grp" => array("ilNewsForContextBlockGUI" => IL_COL_RIGHT),
		"frm" => array("ilNewsForContextBlockGUI" => IL_COL_RIGHT),
		"root" => array(),
		"info" => array(
			"ilNewsForContextBlockGUI" => IL_COL_RIGHT),
		"pd" => array(
			"ilPDSysMessageBlockGUI" => IL_COL_LEFT,
			"ilPDFeedbackBlockGUI" => IL_COL_LEFT,
			"ilPDNewsBlockGUI" => IL_COL_LEFT,
			"ilPDSelectedItemsBlockGUI" => IL_COL_CENTER,
			"ilPDMailBlockGUI" => IL_COL_RIGHT,
			"ilPDNotesBlockGUI" => IL_COL_RIGHT,
			"ilUsersOnlineBlockGUI" => IL_COL_RIGHT,
			"ilBookmarkBlockGUI" => IL_COL_RIGHT)
		);

	// these are only for pd blocks
	protected $custom_blocks = array(
		"cat" => array(),
		"crs" => array(),
		"grp" => array(),
		"frm" => array(),
		"root" => array(),
		"info" => array(),
		"pd" => array("ilPDExternalFeedBlockGUI")
		);

	/**
	* Constructor
	*
	* @param
	*/
	function ilColumnGUI($a_col_type = "", $a_side = "", $use_std_context = false)
	{
		global $ilUser, $tpl, $ilCtrl;

		$this->setColType($a_col_type);
		//if ($a_side == "")
		//{
		//	$a_side = $_GET["col_side"];
		//}

		$this->setSide($a_side);
	}

	/**
	* Get Column Side of Current Command
	*
	* @return	string	Column Side
	*/
	static function getCmdSide()
	{
		return $_GET["col_side"];
	}

	/**
	* Set Column Type.
	*
	* @param	string	$a_coltype	Column Type
	*/
	function setColType($a_coltype)
	{
		$this->coltype = $a_coltype;
	}

	/**
	* Get Column Type.
	*
	* @return	string	Column Type
	*/
	function getColType()
	{
		return $this->coltype;
	}

	/**
	* Set Side IL_COL_LEFT | IL_COL_RIGHT.
	*
	* @param	string	$a_side	Side IL_COL_LEFT | IL_COL_RIGHT
	*/
	function setSide($a_side)
	{
		$this->side = $a_side;
	}

	/**
	* Get Side IL_COL_LEFT | IL_COL_RIGHT.
	*
	* @return	string	Side IL_COL_LEFT | IL_COL_RIGHT
	*/
	function getSide()
	{
		return $this->side;
	}

	/**
	* Set EnableEdit.
	*
	* @param	boolean	$a_enableedit	EnableEdit
	*/
	function setEnableEdit($a_enableedit)
	{
		$this->enableedit = $a_enableedit;
	}

	/**
	* Get EnableEdit.
	*
	* @return	boolean	EnableEdit
	*/
	function getEnableEdit()
	{
		return $this->enableedit;
	}

	/**
	* Set RepositoryMode.
	*
	* @param	boolean	$a_repositorymode	RepositoryMode
	*/
	function setRepositoryMode($a_repositorymode)
	{
		$this->repositorymode = $a_repositorymode;
	}

	/**
	* Get RepositoryMode.
	*
	* @return	boolean	RepositoryMode
	*/
	function getRepositoryMode()
	{
		return $this->repositorymode;
	}

	/**
	* Set Administration Commmands.
	*
	* @param	boolean	$a_admincommands	Administration Commmands
	*/
	function setAdminCommands($a_admincommands)
	{
		$this->admincommands = $a_admincommands;
	}

	/**
	* Get Administration Commmands.
	*
	* @return	boolean	Administration Commmands
	*/
	function getAdminCommands()
	{
		return $this->admincommands;
	}

	/**
	* Get Screen Mode for current command.
	*/
	static function getScreenMode()
	{
		global $ilCtrl;

		if ($ilCtrl->getCmdClass() == "ilcolumngui")
		{
			switch ($ilCtrl->getCmd())
			{
				case "addBlock":
					return IL_SCREEN_CENTER;
			}
		}

		$cur_block_type = ($_GET["block_type"])
			? $_GET["block_type"]
			: $_POST["block_type"];

		if ($class = array_search($cur_block_type, self::$block_types))
		{
			include_once("./".self::$locations[$class]."classes/".
				"class.".$class.".php");
			return call_user_func(array($class, 'getScreenMode'));
		}

		return IL_SCREEN_SIDE;
	}
	
	/**
	* This function is supposed to be used for block type specific
	* properties, that should be passed to ilBlockGUI->setProperty
	*
	* @param	string	$a_property		property name
	* @param	string	$a_value		property value
	*/
	function setBlockProperty($a_block_type, $a_property, $a_value)
	{
		$this->block_property[$a_block_type][$a_property] = $a_value;
	}
	
	function getBlockProperties($a_block_type)
	{
		return $this->block_property[$a_block_type];
	}

	function setAllBlockProperties($a_block_properties)
	{
		$this->block_property = $a_block_properties;
	}

	/**
	* Set Repository Items.
	*
	* @param	array	$a_repositoryitems	Repository Items
	*/
	function setRepositoryItems($a_repositoryitems)
	{
		$this->repositoryitems = $a_repositoryitems;
	}

	/**
	* Get Repository Items.
	*
	* @return	array	Repository Items
	*/
	function getRepositoryItems()
	{
		return $this->repositoryitems;
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilCtrl;
		
		$ilCtrl->setParameter($this, "col_side" ,$this->getSide());
		//$ilCtrl->saveParameter($this, "col_side");

		$next_class = $ilCtrl->getNextClass();
		$cmd = $ilCtrl->getCmd("getHTML");

		$cur_block_type = ($_GET["block_type"])
			? $_GET["block_type"]
			: $_POST["block_type"];

		if ($next_class != "")
		{
			// forward to block
			if ($gui_class = array_search($cur_block_type, self::$block_types))
			{
				include_once("./".self::$locations[$gui_class]."classes/".
					"class.".$gui_class.".php");
				$ilCtrl->setParameter($this, "block_type", $cur_block_type);
				$block_gui = new $gui_class();
				$block_gui->setProperties($this->block_property[$cur_block_type]);
				$block_gui->setRepositoryMode($this->getRepositoryMode());
				$block_gui->setEnableEdit($this->getEnableEdit());
				$block_gui->setAdminCommands($this->getAdminCommands());

				if (in_array($gui_class, $this->custom_blocks[$this->getColType()]) ||
					in_array($cur_block_type, $this->rep_block_types))
				{
					$block_class = substr($gui_class, 0, strlen($gui_class)-3);
					include_once("./".self::$locations[$gui_class]."classes/".
						"class.".$block_class.".php");
					$app_block = new $block_class($_GET["block_id"]);
					$block_gui->setBlock($app_block);
				}
				$html = $ilCtrl->forwardCommand($block_gui);
				$ilCtrl->setParameter($this, "block_type", "");
				
				return $html;
			}
		}
		else
		{
			return $this->$cmd();
		}
	}

	/**
	* Get HTML for column.
	*/
	function getHTML()
	{
		global $ilCtrl;
		
		$ilCtrl->setParameter($this, "col_side" ,$this->getSide());
		
		$this->tpl = new ilTemplate("tpl.column.html", true, true, "Services/Block");
		
		$this->determineBlocks();
		$this->showBlocks();
		
		if ($this->getEnableEdit() || !$this->getRepositoryMode())
		{
			$this->addHiddenBlockSelector();
		}

		return $this->tpl->get();
	}
	
	/**
	* Show blocks.
	*/
	function showBlocks()
	{
		global $ilCtrl;
		
		$blocks = array();
		
		$i = 1;
		$sum_moveable = count($this->blocks[$this->getSide()]);

		foreach($this->blocks[$this->getSide()] as $block)
		{
			$gui_class = $block["class"];
			$block_class = substr($block["class"], 0, strlen($block["class"])-3);
			
			// get block gui class
			include_once("./".self::$locations[$gui_class]."classes/".
				"class.".$gui_class.".php");
			$block_gui = new $gui_class();
			$block_gui->setProperties($this->block_property[$block["type"]]);
			$block_gui->setRepositoryMode($this->getRepositoryMode());
			$block_gui->setEnableEdit($this->getEnableEdit());
			$block_gui->setAdminCommands($this->getAdminCommands());
			if ($this->getSide() == IL_COL_LEFT)
			{
				$block_gui->setAllowMove("right");
			}
			else if ($this->getSide() == IL_COL_RIGHT)
			{
				$block_gui->setAllowMove("left");
			}
			if ($i > 1)
			{
				$block_gui->setAllowMove("up");
			}
			if ($i < $sum_moveable)
			{
				$block_gui->setAllowMove("down");
			}
			
			// get block for custom blocks
			if ($block["custom"])
			{
				include_once("./".self::$locations[$gui_class]."classes/".
					"class.".$block_class.".php");
				$app_block = new $block_class($block["id"]);
				$block_gui->setBlock($app_block);
				$block_gui->setRefId($block["ref_id"]);
			}

			$ilCtrl->setParameter($this, "block_type", $block_gui->getBlockType());
			$this->tpl->setCurrentBlock("col_block");
			$html = $ilCtrl->getHTML($block_gui);

			$this->tpl->setVariable("BLOCK", $html);
			$this->tpl->parseCurrentBlock();
			$ilCtrl->setParameter($this, "block_type", "");
			
			// count (moveable) blocks
			if ($block["type"] != "pdsysmess" && $block["type"] != "pdfeedb")
			{
				$i++;
			}
			else
			{
				$sum_moveable--;
			}
		}
	}

	/**
	* Add hidden block and create block selectors.
	*/
	function addHiddenBlockSelector()
	{
		global $lng, $ilUser, $ilCtrl, $ilSetting;
		
		// show selector for hidden blocks
		include_once("Services/Block/classes/class.ilBlockSetting.php");
		$hidden_blocks = array();
		$blocks = array("pdmail" => $lng->txt("mail"),
			"pdnotes" => $lng->txt("notes"),
			"pdusers" => $lng->txt("users_online"),
			"pdnews" => $lng->txt("news_internal_news"),
			"pdbookm" => $lng->txt("my_bms"),
			"news" => $lng->txt("news_internal_news"),
			"feed" => $lng->txt("feed"),
			"pdfeed" => $lng->txt("feed"),
			"html" => $lng->txt("html_block"),
			);
			

		foreach($this->blocks[$this->getSide()] as $block)
		{
			include_once("./".self::$locations[$block["class"]]."classes/".
				"class.".$block["class"].".php");
				
			if ($block["custom"] == false)
			{
				if ($ilCtrl->getContextObjType() == "user")	// personal desktop
				{
					if (ilBlockSetting::_lookupDetailLevel($block["type"], $ilUser->getId()) == 0)
					{
						$hidden_blocks[$block["type"]] = $blocks[$block["type"]];
					}
				}
				else if ($ilCtrl->getContextObjType() != "")
				{
					if (ilBlockSetting::_lookupDetailLevel($block["type"], $ilUser->getId(),
						$ilCtrl->getContextObjId()) == 0)
					{
						$hidden_blocks[$block["type"]."_".$ilCtrl->getContextObjId()] = $blocks[$block["type"]];
					}
				}
			}
			else
			{
				if (ilBlockSetting::_lookupDetailLevel($block["type"], $ilUser->getId(),
					$block["id"]) == 0)
				{
					include_once("./Services/Block/classes/class.ilCustomBlock.php");
					$cblock = new ilCustomBlock($block["id"]);
					$hidden_blocks[$block["type"]."_".$block["id"]] =
						$cblock->getTitle();
				}
			}
		}
		if (count($hidden_blocks) > 0)
		{
			$this->tpl->setCurrentBlock("hidden_block_selector");
			$this->tpl->setVariable("HB_ACTION", $ilCtrl->getFormAction($this));
			$this->tpl->setVariable("BLOCK_SEL", ilUtil::formSelect("", "block", $hidden_blocks,
				false, true, 0, "ilEditSelect"));
			$this->tpl->setVariable("TXT_ACTIVATE", $lng->txt("show"));
			$this->tpl->parseCurrentBlock();
		}
		
		// create block selection list
		if (!$this->getRepositoryMode() || $this->getEnableEdit())
		{
			$add_blocks = array();
			if ($this->getSide() == IL_COL_RIGHT)
			{
				if (is_array($this->custom_blocks[$this->getColType()]))
				{
					foreach($this->custom_blocks[$this->getColType()] as $block_class)
					{
						include_once("./".self::$locations[$block_class]."classes/".
							"class.".$block_class.".php");
						$block_type = call_user_func(array($block_class, 'getBlockType'));
						
						// check blocks that can be globally (de-)activated
						$activated = true;
						//if ($block_type == "feed")
						//{
						//	if (!$ilSetting->get("block_activated_".$block_type))
						//	{
						//		$activated = false;
						//	}
						//}
						if ($block_type == "pdfeed")		// maximum number
						{
							//if ($ilSetting->get("block_max_nr_".$block_type) >=
							//	)
							//{
						}

						if ($activated)
						{
							$add_blocks[$block_type] = $blocks[$block_type];
						}
					}
				}
			}
			if (count($add_blocks) > 0)
			{
				$this->tpl->setCurrentBlock("add_block_selector");
				$this->tpl->setVariable("AB_ACTION", $ilCtrl->getFormAction($this));
				$this->tpl->setVariable("ADD_BLOCK_SEL", ilUtil::formSelect("", "block_type", $add_blocks,
					false, true, 0, "ilEditSelect"));
				$this->tpl->setVariable("TXT_ADD", $lng->txt("create"));
				$this->tpl->parseCurrentBlock();
			}
		}
		
		//return $tpl->get();

	}

	/**
	* Update Block (asynchronous)
	*/
	function updateBlock()
	{
		global $ilCtrl;
		
		$this->determineBlocks();
		$i = 1;
		$sum_moveable = count($this->blocks[$this->getSide()]);
		
		foreach ($this->blocks[$this->getSide()] as $block)
		{
			include_once("./".self::$locations[$block["class"]]."classes/".
				"class.".$block["class"].".php");
				
			// set block id to context obj id,
			// if block is not a custom block and context is not personal desktop
			if (!$block["custom"] && $ilCtrl->getContextObjType() != "" && $ilCtrl->getContextObjType() != "user")
			{
				$block["id"] = $ilCtrl->getContextObjId();
			}
				
			if (is_int(strpos($_GET["block_id"], "block_".$block["type"]."_".$block["id"])))
			{
				$gui_class = $block["class"];
				$block_class = substr($block["class"], 0, strlen($block["class"])-3);
				
				$block_gui = new $gui_class();
				$block_gui->setProperties($this->block_property[$block["type"]]);
				$block_gui->setRepositoryMode($this->getRepositoryMode());
				$block_gui->setEnableEdit($this->getEnableEdit());
				$block_gui->setAdminCommands($this->getAdminCommands());
				
				if ($this->getSide() == IL_COL_LEFT)
				{
					$block_gui->setAllowMove("right");
				}
				else if ($this->getSide() == IL_COL_RIGHT)
				{
					$block_gui->setAllowMove("left");
				}
				if ($i > 1)
				{
					$block_gui->setAllowMove("up");
				}
				if ($i < $sum_moveable)
				{
					$block_gui->setAllowMove("down");
				}
				
				// get block for custom blocks
				if ($block["custom"])
				{
					include_once("./".self::$locations[$gui_class]."classes/".
						"class.".$block_class.".php");
					$app_block = new $block_class($block["id"]);
					$block_gui->setBlock($app_block);
					$block_gui->setRefId($block["ref_id"]);
				}

				$ilCtrl->setParameter($this, "block_type", $block["type"]);
				echo $ilCtrl->getHTML($block_gui);
				exit;
			}
			
			// count (moveable) blocks
			if ($block["type"] != "pdsysmess" && $block["type"] != "pdfeedb")
			{
				$i++;
			}
			else
			{
				$sum_moveable--;
			}
		}
		echo "Error: ilColumnGUI::updateBlock: Block '".
			$_GET["block_id"]."' unknown.";
		exit;
	}

	/**
	* Activate hidden block
	*/
	function activateBlock()
	{
		global $ilUser, $ilCtrl;

		if ($_POST["block"] != "")
		{
			$block = explode("_", $_POST["block"]);
			include_once("Services/Block/classes/class.ilBlockSetting.php");
			ilBlockSetting::_writeDetailLevel($block[0], 2, $ilUser->getId(), $block[1]);
		}

		$ilCtrl->returnToParent($this);
	}

	/**
	* Add a block
	*/
	function addBlock()
	{
		global $ilCtrl;
		
		$class = array_search($_POST["block_type"], self::$block_types);

		$ilCtrl->setCmdClass($class);
		$ilCtrl->setCmd("create");
		include_once("./".self::$locations[$class]."classes/class.".$class.".php");
		$block_gui = new $class();
		$block_gui->setProperties($this->block_property[$_POST["block_type"]]);
		$block_gui->setRepositoryMode($this->getRepositoryMode());
		$block_gui->setEnableEdit($this->getEnableEdit());
		$block_gui->setAdminCommands($this->getAdminCommands());
		
		$ilCtrl->setParameter($this, "block_type", $_POST["block_type"]);
		$html = $ilCtrl->forwardCommand($block_gui);
		$ilCtrl->setParameter($this, "block_type", "");
		return $html;
	}
	
	function determineBlocks()
	{
		global $ilUser, $ilCtrl, $ilSetting;

		include_once("./Services/Block/classes/class.ilBlockSetting.php");
		$this->blocks[IL_COL_LEFT] = array();
		$this->blocks[IL_COL_RIGHT] = array();
		$this->blocks[IL_COL_CENTER] = array();
		
		$user_id = ($this->getColType() == "pd")
			? $ilUser->getId()
			: 0;

		$def_nr = 1000;
		if (is_array($this->default_blocks[$this->getColType()]))
		{
			foreach($this->default_blocks[$this->getColType()] as $class => $def_side)
			{
				$type = self::$block_types[$class];
				$nr = ilBlockSetting::_lookupNr($type, $user_id);
				if ($nr === false)
				{
					$nr = $def_nr++;
				}
				// extra handling for system messages and feedback block
				if ($type == "pdsysmess")		// always show sys mess first
				{
					$nr = -15;
				}
				if ($type == "pdfeedb")		// always show feedback request second
				{
					$nr = -10;
				}
				$side = ilBlockSetting::_lookupSide($type, $user_id);
				if ($side === false)
				{
					$side = $def_side;
				}
				
				// check blocks that can be globally (de-)activated
				$activated = true;
				if ($type == "news" ||  $type == "pdnews")
				{
					if (!$ilSetting->get("block_activated_".$type))
					{
						$activated = false;
					}
				}
				
				if ($activated)
				{
					$this->blocks[$side][] = array(
						"nr" => $nr,
						"class" => $class,
						"type" => $type,
						"id" => 0,
						"custom" => false);
				}
			}
		}
		
		if (!$this->getRepositoryMode())
		{
			include_once("./Services/Block/classes/class.ilCustomBlock.php");
			$costum_block = new ilCustomBlock();
			$costum_block->setContextObjId($ilCtrl->getContextObjId());
			$costum_block->setContextObjType($ilCtrl->getContextObjType());
			$c_blocks = $costum_block->queryBlocksForContext();
	
			foreach($c_blocks as $c_block)
			{
				$type = $c_block["type"];
				$class = array_search($type, self::$block_types);
				$nr = ilBlockSetting::_lookupNr($type, $user_id, $c_block["id"]);
				if ($nr === false)
				{
					$nr = $def_nr++;
				}
				$side = ilBlockSetting::_lookupSide($type, $user_id, $c_block["id"]);
				if ($side === false)
				{
					$side = IL_COL_RIGHT;
				}
	
				// check blocks that can be globally (de-)activated
				$activated = true;
				//if ($type == "feed")
				//{
				//	if (!$ilSetting->get("block_activated_".$type))
				//	{
				//		$activated = false;
				//	}
				//}
				
				if ($activated)
				{
					$this->blocks[$side][] = array(
						"nr" => $nr,
						"class" => $class,
						"type" => $type,
						"id" => $c_block["id"],
						"custom" => true);
				}
			}
		}
		else	// get all subitems
		{
			include_once("./Services/Block/classes/class.ilCustomBlock.php");
			$rep_items = $this->getRepositoryItems();
			foreach($this->rep_block_types as $block_type)
			{
				if (!is_array($rep_items[$block_type]))
				{
					continue;
				}
				foreach($rep_items[$block_type] as $item)
				{
					$costum_block = new ilCustomBlock();
					$costum_block->setContextObjId($item["obj_id"]);
					$costum_block->setContextObjType($block_type);
					$c_blocks = $costum_block->queryBlocksForContext();
					$c_block = $c_blocks[0];
					
					$type = $block_type;
					$class = array_search($type, self::$block_types);
					$nr = ilBlockSetting::_lookupNr($type, $user_id, $c_block["id"]);
					if ($nr === false)
					{
						$nr = $def_nr++;
					}
					$side = ilBlockSetting::_lookupSide($type, $user_id, $c_block["id"]);
					if ($side === false)
					{
						$side = IL_COL_RIGHT;
					}
		
					// check blocks that can be globally (de-)activated
					$activated = true;
					//if ($type == "feed")
					//{
					//	if (!$ilSetting->get("block_activated_".$type))
					//	{
					//		$activated = false;
					//	}
					//}
					
					if ($activated)
					{
						$this->blocks[$side][] = array(
							"nr" => $nr,
							"class" => $class,
							"type" => $type,
							"id" => $c_block["id"],
							"custom" => true,
							"ref_id" => $item["ref_id"]);
					}
				}
			}
		}
		
		
		$this->blocks[IL_COL_LEFT] =
			ilUtil::sortArray($this->blocks[IL_COL_LEFT], "nr", "asc", true);
		$this->blocks[IL_COL_RIGHT] =
			ilUtil::sortArray($this->blocks[IL_COL_RIGHT], "nr", "asc", true);
		$this->blocks[IL_COL_CENTER] =
			ilUtil::sortArray($this->blocks[IL_COL_CENTER], "nr", "asc", true);

	}

	function moveBlock()
	{
		global $ilUser, $ilCtrl;
		
		$this->determineBlocks();
		
		if ($this->getColType() == "pd")
		{
			$bid = explode("_", $_GET["block_id"]);
			$i = 2;
			foreach($this->blocks[$this->getCmdSide()] as $block)
			{
				// only handle non-hidden blocks
				if (ilBlockSetting::_lookupDetailLevel($block["type"],
					$ilUser->getId(), $block["id"]) != 0)
				{
					ilBlockSetting::_writeNumber($block["type"], $i, $ilUser->getId(), $block["id"]);

					if ($block["type"] == $bid[0] && $block["id"] == $bid[1])
					{
						if ($_GET["move_dir"] == "up")
						{
							ilBlockSetting::_writeNumber($block["type"], $i-3, $ilUser->getId(), $block["id"]);
						}
						if ($_GET["move_dir"] == "down")
						{
							ilBlockSetting::_writeNumber($block["type"], $i+3, $ilUser->getId(), $block["id"]);
						}
						if ($_GET["move_dir"] == "left")
						{
							ilBlockSetting::_writeNumber($block["type"], 200, $ilUser->getId(), $block["id"]);
							ilBlockSetting::_writeSide($block["type"], IL_COL_LEFT, $ilUser->getId(), $block["id"]);
						}
						if ($_GET["move_dir"] == "right")
						{
							ilBlockSetting::_writeNumber($block["type"], 200, $ilUser->getId(), $block["id"]);
							ilBlockSetting::_writeSide($block["type"], IL_COL_RIGHT, $ilUser->getId(), $block["id"]);
						}
					}
					else
					{
						ilBlockSetting::_writeNumber($block["type"], $i, $ilUser->getId(), $block["id"]);
					}
					$i+=2;
				}
			}
		}
		$ilCtrl->returnToParent($this);
	}
}
