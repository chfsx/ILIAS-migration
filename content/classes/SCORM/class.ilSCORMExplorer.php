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

/*
* Explorer View for SCORM Learning Modules
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/

require_once("classes/class.ilExplorer.php");
require_once("content/classes/SCORM/class.ilSCORMTree.php");

class ilSCORMExplorer extends ilExplorer
{

	/**
	 * id of root folder
	 * @var int root folder id
	 * @access private
	 */
	var $slm_obj;
	var $api;

	/**
	* Constructor
	* @access	public
	* @param	string	scriptname
	* @param    int user_id
	*/
	function ilSCORMExplorer($a_target, &$a_slm_obj)
	{
		parent::ilExplorer($a_target);
		$this->slm_obj =& $a_slm_obj;
		$this->tree = new ilSCORMTree($a_slm_obj->getId());
		$this->root_id = $this->tree->readRootId();
		$this->checkPermissions(false);
		$this->outputIcons(true);
		$this->setOrderColumn("");
		$this->api = 1;
	}

	function setAPI($a_api_nr)
	{
		$this->api = $a_api_nr;
	}

	/**
	* overwritten method from base class
	* @access	public
	* @param	integer obj_id
	* @param	integer array options
	*/
	function formatHeader($a_obj_id,$a_option)
	{
		global $lng, $ilias;

		$tpl = new ilTemplate("tpl.tree.html", true, true);

		$tpl->setCurrentBlock("row");
		//$tpl->setVariable("TYPE", $a_option["type"]);
		//$tpl->setVariable("ICON_IMAGE" ,ilUtil::getImagePath("icon_".$a_option["type"].".gif"));
		$tpl->setVariable("TITLE", $lng->txt("cont_manifest"));
		$tpl->setVariable("LINK_TARGET", $this->target."&".$this->target_get."=".$a_obj_id);
		$tpl->setVariable("TARGET", " target=\"".$this->frame_target."\"");
		$tpl->parseCurrentBlock();

		$this->output[] = $tpl->get();
	}


	/**
	* Creates Get Parameter
	* @access	private
	* @param	string
	* @param	integer
	* @return	string
	*/
	function createTarget($a_type,$a_child)
	{
		// SET expand parameter:
		//     positive if object is expanded
		//     negative if object is compressed
		$a_child = ($a_type == '+')
			? $a_child
			: -(int) $a_child;

		$s=($this->api == 2) ? "2" : "";
		return $_SERVER["PATH_INFO"]."?cmd=explorer$s&ref_id=".$this->slm_obj->getRefId()."&scexpand=".$a_child;
	}

	function setOutput($a_parent_id, $a_depth = 0)
	{
		global $rbacadmin, $rbacsystem;
		static $counter = 0;

		if (!isset($a_parent_id))
		{
			$this->ilias->raiseError(get_class($this)."::setOutput(): No node_id given!",$this->ilias->error_obj->WARNING);
		}

		if ($this->showChilds($a_parent_id))
		{			
			$objects = $this->tree->getChilds($a_parent_id, $this->order_column);
		}
		else
		{
			$objects = array();
		}				

		if (count($objects) > 0)
		{
			$tab = ++$a_depth - 2;
			// Maybe call a lexical sort function for the child objects
			
			//666if ($this->post_sort)
			//{
				//$objects = $this->sortNodes($objects);
			//}

			foreach ($objects as $key => $object)
			{				
				//ask for FILTER																
				if ($this->filtered == false or $this->checkFilter($object["type"]) == false)
				{
					if ($rbacsystem->checkAccess("visible",$object["child"]) || (!$this->rbac_check))
					{
						if ($object["child"] != $this->tree->getRootId())
						{
							$parent_index = $this->getIndex($object);
						}
						$this->format_options["$counter"]["parent"]		= $object["parent"];
						$this->format_options["$counter"]["child"]		= $object["child"];
						$this->format_options["$counter"]["title"]		= $object["title"];
						$this->format_options["$counter"]["type"]		= $object["type"];
						$this->format_options["$counter"]["obj_id"]		= $object["obj_id"];
						$this->format_options["$counter"]["desc"] 		= "obj_".$object["type"];
						$this->format_options["$counter"]["depth"]		= $tab;
						$this->format_options["$counter"]["container"]	= false;
						$this->format_options["$counter"]["visible"]	= true;
						
						// Create prefix array
						for ($i = 0; $i < $tab; ++$i)
						{							
							 $this->format_options["$counter"]["tab"][] = 'blank';
						}												
														
						if ($object["type"]=="sos")
							$this->setExpand($object["obj_id"]);
						
						if ($object["child"] != $this->tree->getRootId() and (!in_array($object["parent"],$this->expanded)
						   or !$this->format_options["$parent_index"]["visible"]))
						{
							$this->format_options["$counter"]["visible"] = false;
						}

						// if object exists parent is container
						if ($object["child"] != $this->tree->getRootId())
						{
							$this->format_options["$parent_index"]["container"] = true;

							if (in_array($object["parent"],$this->expanded))
							{
								$this->format_options["$parent_index"]["tab"][($tab-2)] = 'minus';
							}
							else
							{
								$this->format_options["$parent_index"]["tab"][($tab-2)] = 'plus';
							}
						}

						++$counter;

						// stop recursion if 2. level beyond expanded nodes is reached
						if (in_array($object["parent"],$this->expanded) or ($object["parent"] == 0))
						{
							// recursive
							$this->setOutput($object["child"],$a_depth);
						}
					} //if
				} //if FILTER
			} //foreach
		} //if
	} //function


/**
	* Creates output
	* recursive method
	* @access	public
	* @return	string
	*/
	function getOutput()
	{
		$this->format_options[0]["tab"] = array();

		$depth = $this->tree->getMaximumDepth();

		for ($i=0;$i<$depth;++$i)
		{
			$this->createLines($i);
		}

		foreach ($this->format_options as $key => $options)
		{
			if ($options["visible"] and $key != 0)
			{
				$this->formatObject($options["child"],$options);
			}
		}

		return implode('',$this->output);
	}

	function isClickable($a_type, $a_id = 0)
	{
		if ($a_type != "sit")
		{
			return false;
		}
		else
		{
			$sc_object =& new ilSCORMItem($a_id);
			if ($sc_object->getIdentifierRef() != "")
			{
				return true;
			}
		}
		return false;
	}

	function formatItemTable(&$tpl, $a_id, $a_type)
	{
		global $lng;

		/*
		if ($a_type != "sit")
		{
			return;
		}
		else
		{
			$sc_object =& new ilSCORMItem($a_id);
			if ($sc_object->getIdentifierRef() != "")
			{
				$trdata = $sc_object->getTrackingDataOfUser();

				// credits
				if ($trdata["mastery_score"] != "")
				{
					$tpl->setCurrentBlock("item_row");
					$tpl->setVariable("TXT_KEY", $lng->txt("cont_credits"));
					$tpl->setVariable("TXT_VALUE", $trdata["mastery_score"]);
					$tpl->parseCurrentBlock();
				}

				// total time
				if ($trdata["total_time"] != "")
				{
					$tpl->setCurrentBlock("item_row");
					$tpl->setVariable("TXT_KEY", $lng->txt("cont_total_time"));
					$tpl->setVariable("TXT_VALUE", $trdata["total_time"]);
					$tpl->parseCurrentBlock();
				}

				$tpl->setCurrentBlock("item_table");
				$tpl->parseCurrentBlock();
			}
		}*/
	}



/**
	* Creates output
	* recursive method
	* @access	private
	* @param	integer
	* @param	array
	* @return	string
	*/
	function formatObject($a_node_id,$a_option)
	{
		global $lng;

		if (!isset($a_node_id) or !is_array($a_option))
		{
			$this->ilias->raiseError(get_class($this)."::formatObject(): Missing parameter or wrong datatype! ".
									"node_id: ".$a_node_id." options:".var_dump($a_option),$this->ilias->error_obj->WARNING);
		}

		$tpl = new ilTemplate("tpl.scorm_tree.html", true, true, true);

	 	if ($a_option["type"]=="sos")
			return;

		if ($a_option["type"]=="srs")
			return;

		foreach ($a_option["tab"] as $picture)
		{
			if ($picture == 'plus')
			{
				$target = $this->createTarget('+',$a_node_id);
				$tpl->setCurrentBlock("expander");
				$tpl->setVariable("LINK_TARGET_EXPANDER", $target);
				$tpl->setVariable("IMGPATH", ilUtil::getImagePath("browser/plus.gif"));
				$tpl->parseCurrentBlock();
			}

			if ($picture == 'minus')
			{
				$target = $this->createTarget('-',$a_node_id);
				$tpl->setCurrentBlock("expander");
				$tpl->setVariable("LINK_TARGET_EXPANDER", $target);
				$tpl->setVariable("IMGPATH", ilUtil::getImagePath("browser/minus.gif"));
				$tpl->parseCurrentBlock();
			}

			if ($picture == 'blank' or $picture == 'winkel'
			   or $picture == 'hoch' or $picture == 'quer' or $picture == 'ecke')
			{
				$picture = 'blank';
				$tpl->setCurrentBlock("lines");
				$tpl->setVariable("IMGPATH_LINES", ilUtil::getImagePath("browser/".$picture.".gif"));
				$tpl->parseCurrentBlock();
			}
		}

		if ($this->output_icons)
		{
			if ($this->isClickable($a_option["type"], $a_node_id))
			{
				$sc_object =& new ilSCORMItem($a_node_id);

				$trdata = $sc_object->getTrackingDataOfUser();
				$tpl->setCurrentBlock("icon");
				// status
				$status = ($trdata["cmi.core.lesson_status"] == "")
					? "not attempted"
					: $trdata["cmi.core.lesson_status"];
				$alt = $lng->txt("cont_status").": ".
					$lng->txt("cont_sc_stat_".str_replace(" ", "_", $status));

				// score
				if ($trdata["cmi.core.score.raw"] != "")
				{
					$alt.= ", ".$lng->txt("cont_credits").
					": ".$trdata["cmi.core.score.raw"];
				}

				// total time
				if ($trdata["cmi.core.total_time"] != "" &&
					$trdata["cmi.core.total_time"] != "0000:00:00.00")
				{
					$alt.= ", ".$lng->txt("cont_total_time").
					": ".$trdata["cmi.core.total_time"];
				}

				$tpl->setVariable("ICON_IMAGE",
					ilUtil::getImagePath("scorm/".str_replace(" ", "_", $status).".gif"));
				$tpl->setVariable("TXT_ALT_IMG", $alt);
				$tpl->parseCurrentBlock();
			}
		}

		if ($this->isClickable($a_option["type"], $a_node_id))	// output link
		{
			$tpl->setCurrentBlock("link");
			//$target = (strpos($this->target, "?") === false) ?
			//	$this->target."?" : $this->target."&";
			//$tpl->setVariable("LINK_TARGET", $target.$this->target_get."=".$a_node_id.$this->params_get);
			//$tpl->setVariable("TITLE", ilUtil::shortenText($a_option["title"], $this->textwidth, true));
			$frame_target = $this->buildFrameTarget($a_option["type"], $a_node_id, $a_option["obj_id"]);
			if ($frame_target != "")
			{
				$tpl->setVariable("TITLE", ilUtil::shortenText($a_option["title"]." ($a_node_id)", $this->textwidth, true));
				$tpl->setVariable("LINK_TARGET", "javascript:void(0);");
				$tpl->setVariable("ONCLICK", " onclick=\"parent.APIFRAME.setupApi();parent.APIFRAME.API.IliasLaunchSco('".$a_node_id."');return false;\"");
			}
			$tpl->parseCurrentBlock();
		}
		else			// output text only
		{
			$tpl->setCurrentBlock("text");
			$tpl->setVariable("OBJ_TITLE", ilUtil::shortenText($a_option["title"], $this->textwidth, true));
			$tpl->parseCurrentBlock();
		}
		$this->formatItemTable($tpl, $a_node_id, $a_option["type"]);

		$tpl->setCurrentBlock("row");
		$tpl->parseCurrentBlock();

		$this->output[] = $tpl->get();
	}
}
?>
