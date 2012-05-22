<?php

/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
include_once("./Services/Taxonomy/exceptions/class.ilTaxonomyException.php");

/**
 * Input GUI class for taxonomy assignments
 *
 * @author Alex Killing <alex.killing@gmx.de> 
 * @version $Id$
 *
 * @ingroup	ServicesTaxonomy
 */
class ilTaxAssignInputGUI extends ilSelectInputGUI
{
	/**
	 * Constructor
	 *
	 * @param	string	$a_title	Title
	 * @param	string	$a_postvar	Post Variable
	 */
	function __construct($a_taxonomy_id, $a_title = "", $a_postvar = "")
	{
		parent::__construct($a_title, $a_postvar);
		$this->setType("tax_assign");
		
		if ((int) $a_taxonomy_id == 0)
		{
			throw new ilTaxonomyExceptions("No taxonomy ID passed to ilTaxAssignInputGUI.");
		}
		
		$this->setTaxonomyId((int) $a_taxonomy_id);
	}
	
	/**
	 * Set taxonomy id
	 *
	 * @param int $a_val taxonomy id	
	 */
	function setTaxonomyId($a_val)
	{
		$this->taxononmy_id = $a_val;
	}
	
	/**
	 * Get taxonomy id
	 *
	 * @return int taxonomy id
	 */
	function getTaxonomyId()
	{
		return $this->taxononmy_id;
	}

	/**
	 * Set Options.
	 *
	 * @param	array	$a_options	Options. Array ("value" => "option_text")
	 */
	function setOptions($a_options)
	{
		throw new ilTaxonomyExceptions("setOptions: Not supported for ilTaxAssignInputGUI.");
	}

	/**
	 * Get Options.
	 *
	 * @return	array	Options. Array ("value" => "option_text")
	 */
	function getOptions()
	{
		global $lng;
		
		$options = array("" => $lng->txt("please_select"));
		
		include_once("./Services/Taxonomy/classes/class.ilTaxonomyTree.php");
		$tax_tree = new ilTaxonomyTree($this->getTaxonomyId());
		
		$nodes = $tax_tree->getSubtree($tax_tree->getNodeData($tax_tree->readRootId()));
		foreach ($nodes as $n)
		{
			if ($n["type"] == "taxn")
			{
				$options[$n["child"]] = str_repeat("&nbsp;", ($n["depth"] - 2) * 2).$n["title"];
			}
		}
		
		return $options;
	}
}
