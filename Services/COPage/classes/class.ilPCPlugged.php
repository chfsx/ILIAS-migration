<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once("./Services/COPage/classes/class.ilPageContent.php");

/**
* Class ilPCPlugged
* Plugged content object (see ILIAS DTD)
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ingroup ServicesCOPage
*/
class ilPCPlugged extends ilPageContent
{
	var $dom;
	var $plug_node;

	/**
	* Init page content component.
	*/
	function init()
	{
		$this->setType("plug");
	}

	/**
	* Set node
	*/
	function setNode(&$a_node)
	{
		parent::setNode($a_node);		// this is the PageContent node
		$this->plug_node =& $a_node->first_child();		// this is the Plugged node
	}

	/**
	* Create plugged node in xml.
	*
	* @param	object	$a_pg_obj		Page Object
	* @param	string	$a_hier_id		Hierarchical ID
	*/
	function create($a_pg_obj, $a_hier_id, $a_pc_id, $a_plugin_name,
		$a_plugin_version)
	{
		$this->node = $this->createPageContentNode();
		$a_pg_obj->insertContent($this, $a_hier_id, IL_INSERT_AFTER, $a_pc_id);
		$this->plug_node =& $this->dom->create_element("Plugged");
		$this->plug_node =& $this->node->append_child($this->plug_node);
		$this->plug_node->set_attribute("PluginName", $a_plugin_name);
		$this->plug_node->set_attribute("PluginVersion", $a_plugin_version);
	}

	/**
	* Set properties of plugged component.
	*
	* @param	array	$a_properties		component properties
	*/
	function setProperties($a_properties)
	{
		if (!is_object($this->plug_node))
		{
			return;
		}
		
		// delete properties
		$children = $this->plug_node->child_nodes();
		for($i=0; $i<count($children); $i++)
		{
			$this->plug_node->remove_child($children[$i]);
		}
		// set properties
		foreach($a_properties as $key => $value)
		{
			$prop_node = $this->dom->create_element("PluggedProperty");
			$prop_node = $this->plug_node->append_child($prop_node);
			$prop_node->set_attribute("Name", $key);
			$prop_node->set_content($value);
		}
	}

	/**
	* Get properties of plugged component
	*
	* @return	string		characteristic
	*/
	function getProperties()
	{
		$properties = array();
		
		if (is_object($this->plug_node))
		{
			// delete properties
			$children = $this->plug_node->child_nodes();
			for($i=0; $i<count($children); $i++)
			{
				if ($children[$i]->node_name() == "PluggedProperty")
				{
					$properties[$children[$i]->get_attribute("Name")] =
						$children[$i]->get_content();
				}
			}
		}
		
		return $properties;
	}
	
	/**
	* Set version of plugged component
	*
	* @param	string	$a_version		version
	*/
	function setPluginVersion($a_version)
	{
		if (!empty($a_version))
		{
			$this->plug_node->set_attribute("PluginVersion", $a_version);
		}
		else
		{
			if ($this->plug_node->has_attribute("PluginVersion"))
			{
				$this->plug_node->remove_attribute("PluginVersion");
			}
		}
	}

	/**
	* Get version of plugged component
	*
	* @return	string		version
	*/
	function getPluginVersion()
	{
		if (is_object($this->plug_node))
		{
			return $this->plug_node->get_attribute("PluginVersion");
		}
	}

	/**
	* Set name of plugged component
	*
	* @param	string	$a_name		name
	*/
	function setPluginName($a_name)
	{
		if (!empty($a_name))
		{
			$this->plug_node->set_attribute("PluginName", $a_name);
		}
		else
		{
			if ($this->plug_node->has_attribute("PluginName"))
			{
				$this->plug_node->remove_attribute("PluginName");
			}
		}
	}

	/**
	* Get name of plugged component
	*
	* @return	string		name
	*/
	function getPluginName()
	{
		if (is_object($this->plug_node))
		{
			return $this->plug_node->get_attribute("PluginName");
		}
	}

	/**
	 * Modify page content after xsl
	 *
	 * @param string $a_output
	 * @return string
	 */
	function modifyPageContentPostXsl($a_html, $a_mode)
	{
		global $lng, $ilPluginAdmin;
		
		$c_pos = 0;
		$start = strpos($a_html, "{{{{{Plugged;");
		if (is_int($start))
		{
			$end = strpos($a_html, "}}}}}", $start);
		}
		$i = 1;
		while ($end > 0)
		{
			$param = substr($a_html, $start + 13, $end - $start - 13);
			
			$param = explode(";", $param);
			
			$plugin_name = $param[0];
			$plugin_version = $param[1];
			$properties = array();
			for ($i == 2; $i < count($param); $i+=2)
			{
				$properties[$param[$i]] = $param[$i+1];
			}
			
			// get html from plugin
			if ($a_mode == "edit")
			{
				$plugin_html = '<div class="il_Block ilBlockContent">'.$lng->txt("content_plugin_not_activated")." (".$plugin_name.")</div>";
			}
	        if ($ilPluginAdmin->isActive(IL_COMP_SERVICE, "COPage", "pgcp", $plugin_name))
	        {
				$plugin_obj = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "COPage",
					"pgcp", $plugin_name);
				$gui_obj = $plugin_obj->getUIClassInstance();
				$plugin_html = $gui_obj->getElementHTML($a_mode, $properties, $plugin_version);
			}
			
			$a_html = substr($a_html, 0, $start).
				$plugin_html.
				substr($a_html, $end + 5);

			$start = strpos($a_html, "{{{{{Plugged;", $start + 5);
			$end = 0;
			if (is_int($start))
			{
				$end = strpos($a_html, "}}}}}", $start);
			}
		}
				
		return $a_html;
	}
}

?>
