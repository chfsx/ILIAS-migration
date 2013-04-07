<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Explorer base GUI class.
 *
 * The class is supposed to work on a hierarchie of nodes that are identified
 * by IDs. Whether nodes are represented by associative arrays or objects
 * is not defined by this abstract class.
 *
 * @author	Alex Killing <alex.killing@gmx.de>
 * @version	$Id$
 *
 * @ingroup ServicesUIComponent
 */
abstract class ilExplorerBaseGUI
{
	private static $js_tree_path = "Services/UIComponent/Explorer2/lib/jstree-v.pre1.0/jquery.jstree.js";
	private $skip_root_node = false;
	private $ajax = false;
	
	/**
	 * Constructor
	 */
	public function __construct($a_expl_id, $a_parent_obj, $a_parent_cmd)
	{
		$this->id = $a_expl_id;
		$this->parent_obj = $a_parent_obj;
		$this->parent_cmd = $a_parent_cmd;

		// get open nodes
		include_once("./Services/Authentication/classes/class.ilSessionIStorage.php");
		$this->store = new ilSessionIStorage("expl2");
		$open_nodes = $this->store->get("on_".$this->id);
		$this->open_nodes = unserialize($open_nodes);
		if (!is_array($this->open_nodes))
		{
			$this->open_nodes = array();
		}
		
	}

	//
	// Abstract function that need to be overwritten in derived classes
	//
	
	/**
	 * Get root node.
	 *
	 * Please note that the class does not make any requirements how
	 * nodes are represented (array or object)
	 *
	 * @return mixed root node object/array
	 */
	abstract function getRootNode();
	
	/**
	 * Get childs of node
	 *
	 * @param string $a_parent_id parent node id
	 * @return array childs
	 */
	abstract function getChildsOfNode($a_parent_node_id);
	
	/**
	 * Get content of a node
	 *
	 * @param mixed $a_node node array or object
	 * @return string content of the node
	 */
	abstract function getNodeContent($a_node);

	/**
	 * Get id of a node
	 *
	 * @param mixed $a_node node array or object
	 * @return string id of node
	 */
	abstract function getNodeId($a_node);

	/**
	 * Get id of explorer element
	 *
	 * @return integer id
	 */
	function getId()
	{
		return $this->id;
	}
	
	/**
	 * Set skip root node
	 *
	 * If set to false, the top node will not be displayed.
	 *
	 * @param boolean $a_val skip root node	
	 */
	function setSkipRootNode($a_val)
	{
		$this->skip_root_node = $a_val;
	}
	
	/**
	 * Get skip root node
	 *
	 * @return boolean skip root node
	 */
	function getSkipRootNode()
	{
		return $this->skip_root_node;
	}
	
	/**
	 * Set ajax
	 *
	 * @param boolean $a_val ajax	
	 */
	function setAjax($a_val)
	{
		$this->ajax = $a_val;
	}
	
	/**
	 * Get ajax
	 *
	 * @return boolean ajax
	 */
	function getAjax()
	{
		return $this->ajax;
	}
	

	/**
	 * Get href for node
	 *
	 * @param mixed $a_node node object/array
	 * @return string href attribute
	 */
	function getNodeHref($a_node)
	{
		return "#";
	}

	/**
	 * Node has childs?
	 *
	 * Please note that this standard method may not
	 * be optimal depending on what a derived class does in isNodeVisible.
	 *
	 * @param
	 * @return
	 */
	function nodeHasVisibleChilds($a_node)
	{
		$childs = $this->getChildsOfNode($this->getNodeId($a_node));
		foreach ($childs as $child)
		{
			if ($this->isNodeVisible($child))
			{
				return true;
			}
		}
		return false;
	}
	

	/**
	 * Get node icon path
	 *
	 * @param mixed $a_node node object/array
	 * @return string image file path
	 */
	function getNodeIcon($a_node)
	{
		return "";
	}

	/**
	 * Get node icon alt attribute
	 *
	 * @param mixed $a_node node object/array
	 * @return string image alt attribute
	 */
	function getNodeIconAlt($a_node)
	{
		return "";
	}

	/**
	 * Get node target (frame) attribute
	 *
	 * @param mixed $a_node node object/array
	 * @return string target
	 */
	function getNodeTarget($a_node)
	{
		return "";
	}

	/**
	 * Get node onclick attribute
	 *
	 * @param mixed $a_node node object/array
	 * @return string onclick value
	 */
	function getNodeOnClick($a_node)
	{
		return "";
	}

	/**
	 * Get onclick attribute for node toggling
	 *
	 * @param
	 * @return
	 */
	final protected function getNodeToggleOnClick($a_node)
	{
		return "$('#".$this->getContainerId()."').jstree('toggle_node' , '#".
			$this->getDomNodeIdForNodeId($this->getNodeId($a_node))."'); return false;";
	}
	
	
	/**
	 * Is node visible?
	 *
	 * This method should be used for filtering of any kind.
	 *
	 * @param mixed $a_node node object/array
	 * @return boolean node visible true/false
	 */
	function isNodeVisible($a_node)
	{
		return true;
	}

	/**
	 * Is node highlighted?
	 *
	 * @param mixed $a_node node object/array
	 * @return boolean node visible true/false
	 */
	function isNodeHighlighted($a_node)
	{
		return false;
	}	
	
	/**
	 * Handle explorer internal command.
	 *
	 * @return boolean true, if an internal command has been performed.
	 */
	function handleCommand()
	{
		if ($_GET["exp_cmd"] != "" &&
			$_GET["exp_cont"] == $this->getContainerId())
		{
			$cmd = $_GET["exp_cmd"];
			if (in_array($cmd, array("openNode", "closeNode", "getNodeAsync")))
			{
				$this->$cmd();
			}
			
			return true;
		}
		return false;
	}
	
	/**
	 * Get container id
	 *
	 * @param
	 * @return
	 */
	function getContainerId()
	{
		return "il_expl2_jstree_cont_".$this->getId();
	}
	
	/**
	 * Open node
	 */
	function openNode()
	{
		global $ilLog;
		
		$id = $this->getNodeIdForDomNodeId($_GET["node_id"]);
		if (!in_array($id, $this->open_nodes))
		{
			$this->open_nodes[] = $id;
		}
		$this->store->set("on_".$this->id, serialize($this->open_nodes));
		exit;
	}
	
	/**
	 * Close node
	 */
	function closeNode()
	{
		global $ilLog;
		
		$id = $this->getNodeIdForDomNodeId($_GET["node_id"]);
		if (in_array($id, $this->open_nodes))
		{
			$k = array_search($id, $this->open_nodes);
			unset($this->open_nodes[$k]);
		}
		$this->store->set("on_".$this->id, serialize($this->open_nodes));
		exit;
	}
	
	/**
	 * Get node asynchronously
	 */
	function getNodeAsync()
	{
		if ($_GET["node_id"] != "")
		{
			$id = $this->getNodeIdForDomNodeId($_GET["node_id"]);
		}
		else
		{
			$id = $this->getNodeId($this->getRootNode());
		}
		
		$etpl = new ilTemplate("tpl.explorer2.html", true, true, "Services/UIComponent/Explorer2");
		$this->renderChilds($id, $etpl);
		echo $etpl->get("tag");
		exit;
	}
	
	
	/**
	 * Get HTML
	 *
	 * @param
	 * @return
	 */
	function getHTML()
	{
		global $tpl, $ilCtrl;
		
		$tpl->addJavascript("./Services/UIComponent/Explorer2/js/Explorer2.js");
		$tpl->addJavascript(self::$js_tree_path);
		
		$container_id = $this->getContainerId();
		$container_outer_id = "il_expl2_jstree_cont_out_".$this->getId();
		
		// collect open nodes
		$open_nodes = array($this->getDomNodeIdForNodeId($this->getNodeId($this->getRootNode())));
		foreach ($this->open_nodes as $nid)
		{
			$open_nodes[] = $this->getDomNodeIdForNodeId($nid);
		}
		
		// ilias config options
		$url = $ilCtrl->getLinkTarget($this->parent_obj, $this->parent_cmd, "", true);
		$config = array(
			"container_id" => $container_id,
			"container_outer_id" => $container_outer_id,
			"url" => $url,
			"ajax" => $this->getAjax(),
			);
		
		// jstree config options
		$js_tree_config = array(
			"core" => array(
				"animation" => 300,
				"initially_open" => $open_nodes,
				"open_parents" => false,
				"strings" => array("loading" => "Loading ...", new_node => "New node")
				),
			"plugins" => array("html_data", "themes"),
			"themes" => array("dots" => false, "icons" => false, "theme" => ""),
			"html_data" => array()
			);
		
		$tpl->addOnLoadCode('il.Explorer2.init('.json_encode($config).', '.json_encode($js_tree_config).');');
		
		$etpl = new ilTemplate("tpl.explorer2.html", true, true, "Services/UIComponent/Explorer2");

		// render childs
		$root_node = $this->getRootNode();
		
		if (!$this->getSkipRootNode() &&
			$this->isNodeVisible($this->getRootNode()))
		{
			$this->listStart($etpl);
			$this->renderNode($this->getRootNode(), $etpl);
			$this->listEnd($etpl);
		}
		else
		{		
			$childs = $this->getChildsOfNode($this->getNodeId($root_node));
			$any = false;
			foreach ($childs as $child_node)
			{
				if ($this->isNodeVisible($child_node))
				{
					if (!$any)
					{
						$this->listStart($etpl);
						$any = true;
					}
					$this->renderNode($child_node, $etpl);
				}
			}
			if ($any)
			{
				$this->listEnd($etpl);
			}
		}
		
		$etpl->setVariable("CONTAINER_ID", $container_id);
		$etpl->setVariable("CONTAINER_OUTER_ID", $container_outer_id);
		
		return $etpl->get();
	}
	
	/**
	 * Render node
	 *
	 * @param
	 * @return
	 */
	function renderNode($a_node, $tpl)
	{
		$this->listItemStart($tpl, $a_node);
		if ($this->isNodeHighlighted($a_node))
		{
			$tpl->touchBlock("hl");
		}
		$tpl->setCurrentBlock("content");
		if ($this->getNodeIcon($a_node) != "")
		{
			$tpl->setVariable("ICON", ilUtil::img($this->getNodeIcon($a_node), $this->getNodeIconAlt($a_node))." ");
		}
		$tpl->setVariable("CONTENT", $this->getNodeContent($a_node));
		$tpl->setVariable("HREF", $this->getNodeHref($a_node));
		$target = $this->getNodeTarget($a_node);
		if ($target != "")
		{
			$tpl->setVariable("TARGET", 'target="'.$target.'"');
		}
		$onclick = $this->getNodeOnClick($a_node);
		if ($onclick != "")
		{
			$tpl->setVariable("ONCLICK", 'onclick="'.$onclick.'"');
		}
		$tpl->parseCurrentBlock();
		
		$tpl->touchBlock("tag");
		
		if (!$this->getAjax() || in_array($this->getNodeId($a_node), $this->open_nodes))
		{
			$this->renderChilds($this->getNodeId($a_node), $tpl);
		}
		
		$this->listItemEnd($tpl);
	}
	
	/**
	 * Render childs
	 *
	 * @param
	 * @return
	 */
	function renderChilds($a_node_id, $tpl)
	{
		$childs = $this->getChildsOfNode($a_node_id);
		if (count($childs) > 0)
		{
			$any = false;
			foreach ($childs as $child)
			{
				if ($this->isNodeVisible($child))
				{
					if (!$any)
					{
						$this->listStart($tpl);
						$any = true;
					}
					$this->renderNode($child, $tpl);
				}
			}
			if ($any)
			{
				$this->listEnd($tpl);
			}
		}
	}

	/**
	 * Get DOM node id for node id
	 *
	 * @param
	 * @return
	 */
	function getDomNodeIdForNodeId($a_node_id)
	{
		return "exp_node_".$this->getId()."_".$a_node_id;
	}
	
	/**
	 * Get node id for dom node id
	 *
	 * @param
	 * @return
	 */
	function getNodeIdForDomNodeId($a_dom_node_id)
	{
		$i = strlen("exp_node_".$this->getId()."_");
		return substr($a_dom_node_id, $i);
	}
		
	/**
	 * List item start
	 *
	 * @param
	 * @return
	 */
	function listItemStart($tpl, $a_node)
	{
		$tpl->setCurrentBlock("list_item_start");
		if ($this->getAjax() && $this->nodeHasVisibleChilds($a_node))
		{
			$tpl->touchBlock("li_closed");
		}
		$tpl->setVariable("DOM_NODE_ID",
			$this->getDomNodeIdForNodeId($this->getNodeId($a_node)));
		$tpl->parseCurrentBlock();
		$tpl->touchBlock("tag");
	}

	/**
	 * List item end
	 *
	 * @param
	 * @return
	 */
	function listItemEnd($tpl)
	{
		$tpl->touchBlock("list_item_end");
		$tpl->touchBlock("tag");
	}

	/**
	 * List start
	 *
	 * @param
	 * @return
	 */
	function listStart($tpl)
	{
		$tpl->touchBlock("list_start");
		$tpl->touchBlock("tag");
	}

	/**
	 * List end
	 *
	 * @param
	 * @return
	 */
	function listEnd($tpl)
	{
		$tpl->touchBlock("list_end");
		$tpl->touchBlock("tag");
	}	
}

?>
