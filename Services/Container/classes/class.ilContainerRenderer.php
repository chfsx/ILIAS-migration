<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilContainerRenderer
*
* @author Jörg Lützenkirchen  <luetzenkirchen@leifos.com>
* @version $Id: class.ilContainerGUI.php 52026 2014-08-05 10:22:06Z smeyer $
*/
class ilContainerRenderer
{
	// switches
	protected $enable_manage_select_all; // [bool]
	protected $enable_multi_download; // [bool]	
	protected $active_block_ordering; // [bool]
	
	// properties
	protected $type_blocks = array(); // [array]
	protected $custom_blocks = array(); // [array]
	protected $items = array(); // [array]
	protected $hidden_items = array(); // [array]
	protected $block_items = array(); // [array]	
	protected $details = array(); // [array]
	
	// block (unique) ids
	protected $rendered_blocks = array(); // [array]	
	protected $bl_cnt = 0; // [int]
	protected $cur_row_type; // [string]
	
	// ordering
	protected $block_pos = array(); // [array]
	protected $block_custom_pos = array(); // [array]
	protected $order_cnt = 0; // [int]
		
	/**
	 * Constructor
	 * 
	 * @param bool $a_enable_manage_select_all
	 * @param bool $a_enable_multi_download
	 * @param bool $a_active_block_ordering
	 * @param array $a_block_custom_positions
	 * @return self
	 */
	public function __construct($a_enable_manage_select_all = false, $a_enable_multi_download = false, $a_active_block_ordering = false, array $a_block_custom_positions = null)
	{
		$this->enable_manage_select_all = (bool)$a_enable_manage_select_all;
		$this->enable_multi_download = (bool)$a_enable_multi_download;				
		$this->active_block_ordering = (bool)$a_active_block_ordering;			
		$this->block_custom_pos = $a_block_custom_positions;
	}
	
	
	//
	// blocks
	//
	
	/**
	 * Add type block 
	 * 
	 * @param string $a_type repository object type
	 * @param string $a_prefix html snippet
	 * @param string $a_postfix html snippet
	 * @return boolean
	 */
	public function addTypeBlock($a_type, $a_prefix = null, $a_postfix = null)
	{
		if($a_type != "itgr" &&
			!$this->hasTypeBlock($a_type))
		{
			$this->type_blocks[$a_type] = array(
				"prefix" => $a_prefix
				,"postfix" => $a_postfix
			);
			return true;
		}
		return false;
	}
	
	/**
	 * Type block already exists?
	 * 
	 * @param string $a_type repository object type
	 * @return bool
	 */
	public function hasTypeBlock($a_type)
	{
		return array_key_exists($a_type, $this->type_blocks);
	}
	
	/**
	 * Add custom block
	 * 
	 * @param mixed $a_id
	 * @param string $a_caption
	 * @param string $a_actions html snippet
	 * @return boolean
	 */
	public function addCustomBlock($a_id, $a_caption, $a_actions = null)
	{
		if(!$this->hasCustomBlock($a_id))
		{
			$this->custom_blocks[$a_id] = array(
				"caption" => $a_caption
				,"actions" => $a_actions
			);
			return true;
		}
		return false;
	}
	
	/**
	 * Custom block already exists?
	 * 
	 * @param mixede $a_id
	 * @return bool
	 */
	public function hasCustomBlock($a_id)
	{
		return array_key_exists($a_id, $this->custom_blocks);
	}
	
	/**
	 * Any block with id exists?
	 * 
	 * @param mixed $a_id
	 * @return bool
	 */
	public function isValidBlock($a_id)
	{
		return ($this->hasTypeBlock($a_id) || 
			$this->hasCustomBlock($a_id));
	}
	
	
	//
	// items
	//
	
	/**
	 * Mark item id as used, but do not render
	 * 
	 * @param mixed $a_id
	 */
	public function hideItem($a_id)
	{
		$this->hidden_items[] = $a_id;		
		$this->removeItem($a_id);
	}
	
	/**
	 * Remove item (from any block)
	 * 
	 * @param mixed $a_id
	 */
	public function removeItem($a_id)
	{
		unset($this->items[$a_id]);
		
		foreach($this->block_items as $block_id => $items)
		{
			foreach($items as $idx => $item_id)
			{
				if($item_id == $a_id)
				{
					unset($this->block_items[$block_id][$idx]);
					if(!sizeof($this->block_items[$block_id]))
					{
						unset($this->block_items[$block_id]);
					}
					break;
				}
			}
		}
	}
	
	/**
	 * Item with id existS?
	 * 
	 * @param mixed $a_id
	 * @return bool
	 */
	public function hasItem($a_id)
	{
		return (array_key_exists($a_id, $this->items) || 
			in_array($a_id, $this->hidden_items));
	}
	
	/**
	 * Add item to existing block
	 * 
	 * @param mixed $a_block_id
	 * @param string $a_item_type repository object type
	 * @param mixed $a_item_id
	 * @param string $a_item_html html snippet
	 * @param bool $a_force enable multiple rendering
	 * @return boolean
	 */
	public function addItemToBlock($a_block_id, $a_item_type, $a_item_id, $a_item_html, $a_force = false)
	{
		if($this->isValidBlock($a_block_id) &&
			$a_item_type != "itgr" &&
			(!$this->hasItem($a_item_id) || $a_force) &&
			trim($a_item_html))
		{
			$this->items[$a_item_id] = array(
				"type" => $a_item_type
				,"html" => $a_item_html
			);
			$this->block_items[$a_block_id][] = $a_item_id;
			return true;
		}
		return false;
	}		
	
	/**
	 * Add details level
	 * 
	 * @param int $a_level
	 * @param string $a_url
	 * @param bool $a_active
	 */		
	public function addDetailsLevel($a_level, $a_url, $a_active = false)
	{
		$this->details[$a_level] = array(			
			"url" => $a_url
			,"active" => (bool)$a_active
		);
	}
	
	/**
	 * Reset/remove all detail levels
	 */
	public function resetDetails()
	{
		$this->details = array();
	}		
	
	
	//
	// render
	//
	
	/**
	 * Set block position
	 * 
	 * @param mixed $a_block_id
	 * @param int $a_pos
	 */
	public function setBlockPosition($a_block_id, $a_pos)
	{
		if($this->isValidBlock($a_block_id))
		{
			$this->block_pos[$a_block_id] = $a_pos;
		}
	}
	
	/**
	 * Get rendered html (of all blocks)
	 * 
	 * @return string
	 */
	public function getHTML()
	{
		$valid = false;					
		
		$block_tpl = $this->initBlockTemplate();			
		
		foreach($this->processBlockPositions() as $block_id)
		{
			if(array_key_exists($block_id, $this->custom_blocks))
			{
				if($this->renderHelperCustomBlock($block_tpl, $block_id))
				{
					$this->addSeparatorRow($block_tpl);				
					$valid = true;
				}
			}
			if(array_key_exists($block_id, $this->type_blocks))
			{
				if($this->renderHelperTypeBlock($block_tpl, $block_id))
				{
					$this->addSeparatorRow($block_tpl);				
					$valid = true;
				}
			}
		}
		
		if($valid)
		{		
			$this->renderDetails($block_tpl);
			
			return $block_tpl->get();
		}
	}	
	
	/**
	 * Get rendered html of single type block
	 * 
	 * @param string $a_type repository object type
	 * @return html
	 */
	public function renderSingleTypeBlock($a_type)
	{												
		$block_tpl = $this->initBlockTemplate();

		if($this->renderHelperTypeBlock($block_tpl, $a_type, true))
		{					
			return $block_tpl->get();
		}					
	}
	
	/**
	 * Get rendered html of single custom block
	 * 
	 * @param mixed $a_id 
	 * @return html
	 */
	public function renderSingleCustomBlock($a_id)
	{				
		$block_tpl = $this->initBlockTemplate();

		if($this->renderHelperCustomBlock($block_tpl, $a_id, true))
		{					
			return $block_tpl->get();
		}					
	}
		
	
	//
	// render (helper)
	//	
	
	/**
	 * Process block positions
	 * 
	 * @return array block ids
	 */
	protected function processBlockPositions()
	{
		// manual order
		if(sizeof($this->block_custom_pos))
		{
			$tmp = $this->block_pos;
			$this->block_pos = array();		
			foreach($this->block_custom_pos as $idx => $block_id)
			{				
				if($this->isValidBlock($block_id))
				{
					$this->block_pos[$block_id] = $idx;
				}
			}
			
			// at least some manual are valid
			if(sizeof($this->block_pos))
			{				
				// append missing blocks from default order
				$last = max($this->block_pos);
				foreach(array_keys($tmp) as $block_id)
				{					
					if(!array_key_exists($block_id, $this->block_pos))
					{
						$this->block_pos[$block_id] = ++$last;
					}
				}				
			}
			// all manual invalid, use default
			else
			{
				$this->block_pos = $tmp;
			}
		}
		
		// add missing blocks to order
		$last = sizeof($this->block_pos) 
			? max($this->block_pos)
			: 0;
		foreach(array_keys($this->custom_blocks) as $block_id)
		{
			if(!array_key_exists($block_id, $this->block_pos))
			{
				$this->block_pos[$block_id] = ++$last;
			}
		}
		foreach(array_keys($this->type_blocks) as $block_id)
		{
			if(!array_key_exists($block_id, $this->block_pos))
			{
				$this->block_pos[$block_id] = ++$last;
			}
		}
			
		asort($this->block_pos);
		
		return array_keys($this->block_pos);
	}		
	
	/**
	 * Render custom block
	 * 
	 * @param ilTemplate $a_block_tpl
	 * @param mixed $a_block_id
	 * @param bool $a_is_single
	 * @return boolean
	 */
	protected function renderHelperCustomBlock(ilTemplate $a_block_tpl, $a_block_id, $a_is_single = false)
	{
		if($this->hasCustomBlock($a_block_id))
		{
			return $this->renderHelperGeneric($a_block_tpl, $a_block_id, $this->custom_blocks[$a_block_id], $a_is_single);			
		}						
		return false;
	}
	
	/**
	 * Render type block
	 * 
	 * @param ilTemplate $a_block_tpl
	 * @param string $a_type repository object type
	 * @param bool $a_is_single
	 * @return boolean
	 */
	protected function renderHelperTypeBlock(ilTemplate $a_block_tpl, $a_type, $a_is_single = false)
	{
		if($this->hasTypeBlock($a_type))
		{						
			$block = $this->type_blocks[$a_type];
			$block["type"] = $a_type;
			
			return $this->renderHelperGeneric($a_block_tpl, $a_type, $block, $a_is_single);		
		}		
		return false;
	}
		
	/**
	 * Render block
	 * 
	 * @param ilTemplate $a_block_tpl
	 * @param mixed $a_block_id
	 * @param array $a_block block properties
	 * @param bool $a_is_single
	 * @return boolean
	 */
	protected function renderHelperGeneric(ilTemplate $a_block_tpl, $a_block_id, array $a_block, $a_is_single = false)
	{					
		if(!in_array($a_block_id, $this->rendered_blocks) &&
			is_array($this->block_items[$a_block_id]))
		{	
			$this->rendered_blocks[] = $a_block_id;
		
			$block_types = array();
			foreach($this->block_items[$a_block_id] as $item_id)
			{	
				if(isset($this->items[$item_id]["type"]))
				{
					$block_types[] = $this->items[$item_id]["type"];
				}
			}
			
			$order_id = (!$a_is_single && $this->active_block_ordering) 
				? $a_block_id
				: null;			
			$this->addHeaderRow($a_block_tpl, $a_block["type"], $a_block["caption"], array_unique($block_types), $a_block["actions"], $order_id);

			if($a_block["prefix"])
			{
				$this->addStandardRow($a_block_tpl, $a_block["prefix"]);
			}

			foreach($this->block_items[$a_block_id] as $item_id)
			{							
				$this->addStandardRow($a_block_tpl, $this->items[$item_id]["html"], $item_id);			
			}		

			if($a_block["postfix"])
			{
				$this->addStandardRow($a_block_tpl, $a_block["postfix"]);
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Init template
	 * 
	 * @return ilTemplate
	 */
	protected function initBlockTemplate()
	{
		// :TODO: obsolete?
		$this->cur_row_type = "row_type_1";
		
		return new ilTemplate("tpl.container_list_block.html", true, true,
			"Services/Container");
	}
	
	/**
	 * Render block header
	 * 
	 * @param ilTemplate $a_tpl
	 * @param string $a_type
	 * @param string $a_text
	 * @param array $a_types_in_block
	 * @param string $a_commands_html
	 * @param int $a_order_id
	 */
	protected function addHeaderRow(ilTemplate $a_tpl, $a_type = "", $a_text = "", array $a_types_in_block = null, $a_commands_html = null, $a_order_id = null)
	{
		global $lng, $ilSetting, $objDefinition;
		
		$a_tpl->setVariable("CB_ID", ' id="bl_cntr_'.(++$this->bl_cnt).'"');
			
		if ($this->enable_manage_select_all)
		{
			$this->renderSelectAllBlock($a_tpl);
		}
		else if ($this->enable_multi_download)
		{			
			if ($a_type)
			{
				$a_types_in_block = array($a_type);
			}
			foreach($a_types_in_block as $type)
			{				
				if (in_array($type, $this->getDownloadableTypes()))
				{
					$this->renderSelectAllBlock($a_tpl);
					break;
				}				
			}			
		}
				
		if ($a_text == "" && $a_type != "")
		{
			if (!$objDefinition->isPlugin($a_type))
			{
				$title = $lng->txt("objs_".$a_type);
			}
			else
			{
				include_once("./Services/Component/classes/class.ilPlugin.php");
				$title = ilPlugin::lookupTxt("rep_robj", $a_type, "objs_".$a_type);
			}
		}
		else
		{
			$title = $a_text;
		}
	
		if ($ilSetting->get("icon_position_in_lists") != "item_rows" &&
			$a_type != "")
		{
			$icon = ilUtil::getImagePath("icon_".$a_type.".png");

			$a_tpl->setCurrentBlock("container_header_row_image");
			$a_tpl->setVariable("HEADER_IMG", $icon);
			$a_tpl->setVariable("HEADER_ALT", $title);
		}
		else
		{
			$a_tpl->setCurrentBlock("container_header_row");
		}
	
		if($a_order_id)
		{			
			$a_tpl->setVariable("BLOCK_HEADER_ORDER_NAME", "position[blocks][".$a_order_id."]");
			$a_tpl->setVariable("BLOCK_HEADER_ORDER_NUM", (++$this->order_cnt)*10);
		}
		
		$a_tpl->setVariable("BLOCK_HEADER_CONTENT", $title);
		$a_tpl->setVariable("CHR_COMMANDS", $a_commands_html);			
		$a_tpl->parseCurrentBlock();
		
		$a_tpl->touchBlock("container_row");
		
		$this->resetRowType();
	}
	
	/**
	 * Render item row
	 * 
	 * @param ilTemplate $a_tpl
	 * @param string $a_html
	 * @param int $a_ref_id
	 */
	protected function addStandardRow(ilTemplate $a_tpl, $a_html, $a_ref_id = 0)
	{		
		// :TODO: obsolete?
		$this->cur_row_type = ($this->cur_row_type == "row_type_1")
			? "row_type_2"
			: "row_type_1";

		if ($a_ref_id > 0)
		{
			$a_tpl->setCurrentBlock($this->cur_row_type);
			$a_tpl->setVariable("ROW_ID", 'id="item_row_'.$a_ref_id.'"');
			$a_tpl->parseCurrentBlock();
		}
		else
		{
			$a_tpl->touchBlock($this->cur_row_type);
		}
		
		$a_tpl->setCurrentBlock("container_standard_row");
		$a_tpl->setVariable("BLOCK_ROW_CONTENT", $a_html);
		$a_tpl->parseCurrentBlock();
		
		$a_tpl->touchBlock("container_row");
	}
	
	/**
	 * Render "select all"
	 * 
	 * @global ilTemplate $lng
	 */
	protected function renderSelectAllBlock(ilTemplate $a_tpl)
	{
		global $lng;
		
		$a_tpl->setCurrentBlock("select_all_row");
		$a_tpl->setVariable("CHECKBOXNAME", "bl_cb_".$this->bl_cnt);
		$a_tpl->setVariable("SEL_ALL_PARENT", "bl_cntr_".$this->bl_cnt);
		$a_tpl->setVariable("SEL_ALL_PARENT", "bl_cntr_".$this->bl_cnt);
		$a_tpl->setVariable("TXT_SELECT_ALL", $lng->txt("select_all"));
		$a_tpl->parseCurrentBlock();
	}
	
	/**
	 * Render separator row
	 * 
	 * @param ilTemplate $a_tpl
	 */
	protected function addSeparatorRow(ilTemplate $a_tpl)
	{		
		$a_tpl->setCurrentBlock("container_block");
		$a_tpl->parseCurrentBlock();
	}
	
	/**
	 * Reset internal row type
	 */
	protected function resetRowType()
	{
		// :TODO: obsolete?
		$this->cur_row_type = "";
	}
	
	/**
	 * Get downloadable repository object types
	 * 
	 * @return array
	 */
	protected function getDownloadableTypes()
	{
		return array("fold", "file");
	}
	
	/**
	 * Render detail level 
	 *
	 * @param ilTemplate $a_tpl
	 */
	public function renderDetails(ilTemplate $a_tpl)
	{
		global $lng;
		
		if(sizeof($this->details))
		{
			foreach($this->details as $level => $item)
			{
				$a_tpl->setCurrentBlock('details_img');
				
				$append = $item['active'] ? '' : 'off';				
				$a_tpl->setVariable('DETAILS_SRC',ilUtil::getImagePath('details'.$level.$append.'.png'));
				
				$a_tpl->setVariable('DETAILS_ALT',$lng->txt('details').' '.$level);							
				$a_tpl->setVariable('DETAILS_LINK', $item['url']);
				$a_tpl->parseCurrentBlock();
			}
						
			$a_tpl->setCurrentBlock('container_details_row');
			$a_tpl->setVariable('TXT_DETAILS', $lng->txt('details'));
			$a_tpl->parseCurrentBlock();
		}
	}			
}
