<?php
/**
* Class LearningObject
*
* @author Stefan Meyer <smeyer@databay.de>
* @author Sascha Hofmann <shofmann@databay.de> 
* $Id$
* 
* @extends Object
* @package ilias-core
*/

class LearningObject extends Object
{
	/**
	* domxml object
	* 
	* @var		object	domxml object
	* @access	public 
	*/
	var $domxml;	
	
	/**
	* Constructor
	* @access public
	*/
	function LearningObject($a_version = "1.0")
	{
		$this->Object();
		$this->domxml = new domxml($a_version);
	}
	
	/**
	* fetch Title & Description from MetaData-Section of domDocument
	* @return	array	Titel & Description
	* @access	public
	*/ 
	function getInfo ()
	{
		$node = $this->domxml->getElementsByTagname("MetaData");
		$childs = $node[0]->child_nodes();
		
		foreach ($childs as $child)
		{
				if (($child->node_type() == XML_ELEMENT_NODE) && ($child->tagname == "General"))
				{
					$childs2 = $child->child_nodes();

					foreach ($childs2 as $child2)
					{
						if (($child2->node_type() == XML_ELEMENT_NODE) && ($child2->tagname == "Title" || $child2->tagname == "Description"))
						{
							$arr[$child2->tagname] = $child2->get_content();
						}
					}
					
					// General-tag was found. Stop foreach-loop
					break;
				}
		}
		
		// for compatibility reasons:
		$arr["title"] = $arr["Title"];
		$arr["desc"] = $arr["Description"];
		
		return $arr;
	}
	
} // END class.LearningObject
?>