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
* Class ilForumExplorer 
* class for explorer view of forum posts
* 
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* @package ilias-forum
*/
require_once("./classes/class.ilExplorer.php");
require_once("./classes/class.ilForum.php");

class ilForumExplorer extends ilExplorer
{
	/**
	* id of thread
	* @var int thread_pk
	* @access private
	*/
	var $thread_id;
	var $thread_subject;
	

	/**
	* id of root node
	* @var int root_id
	* @access private
	*/
	var $root_id;

	/**
	* forum object, used for owerwritten tree methods
	* @var object forum object
	* @access private
	*/
	var $forum;

	/**
	* Constructor
	* @access	public
	* @param	string	scriptname
	*/
	function ilForumExplorer($a_target,$a_thread_id)
	{
		global $lng;

		$lng->loadLanguageModule("forum");

		parent::ilExplorer($a_target);
		$this->thread_id = $a_thread_id;
		$this->forum = new ilForum();
		$tmp_array = $this->forum->getFirstPostNode($this->thread_id);
		$this->root_id = $tmp_array["child"];

		$this->__readThreadSubject();

		// max length of user fullname which is shown in explorer view
		define(FULLNAME_MAXLENGTH,16);
	}

	/**
	* Creates output for explorer view in admin menue
	* recursive method
	* @access	public
	* @param	integer		parent_node_id where to start from (default=0, 'root')
	* @param	integer		depth level where to start (default=1)
	* @return	string
	*/
	function setOutput($a_parent, $a_depth = 1)
	{
		global $lng;
		static $counter = 0;

		if ($objects =  $this->forum->getPostChilds($a_parent,$this->thread_id))
		{
			$tab = ++$a_depth - 2;
			
			foreach ($objects as $key => $object)
			{
				if ($object["child"] != $this->root_id)
				{
					$parent_index = $this->getIndex($object);
				}
				$this->format_options["$counter"]["parent"] = $object["parent"];
				$this->format_options["$counter"]["child"] = $object["child"];
				#$this->format_options["$counter"]["title"] = $object["subject"] ? $object["subject"] : $this->thread_subject;
				$this->format_options["$counter"]["title"] = $object["title"]." <small class=\"small\">".$object["date"]."</small>".
					"<small><br />".$object["subject"]."</small>";
				$this->format_options["$counter"]["type"] = $object["type"];
				$this->format_options["$counter"]["desc"] = "forums_the_".$object["type"];
				$this->format_options["$counter"]["depth"] = $tab;
				$this->format_options["$counter"]["container"] = false;
				$this->format_options["$counter"]["visible"]	  = true;

				// Create prefix array
				for ($i = 0; $i < $tab; ++$i)
				{
					$this->format_options["$counter"]["tab"][] = 'blank';
				}
				// only if parent is expanded and visible, object is visible
				if ($object["child"] != $this->root_id  and (!in_array($object["parent"],$this->expanded) 
														  or !$this->format_options["$parent_index"]["visible"]))
				{
					$this->format_options["$counter"]["visible"] = true;
				}
				// if object exists parent is container
				if ($object["child"] != $this->root_id)
				{
					$this->format_options["$parent_index"]["container"] = true;

					if (in_array($object["parent"],$this->expanded))
					{
						$this->format_options["$parent_index"]["tab"][($tab-2)] = 'minus';
					}
					else
					{
						$this->format_options["$parent_index"]["tab"][($tab-2)] = 'minus';
					}
				}

				++$counter;

				// Recursive
				$this->setOutput($object["child"],$a_depth);
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
		$depth = $this->forum->getPostMaximumDepth($this->thread_id);
		for ($i=0;$i<$depth;++$i)
		{
			$this->createLines($i);
		}

		foreach ($this->format_options as $key => $options)
		{
			if($key == 0)
			{
				$this->formatHeader();
			}
			if ($options["visible"])
			{
				$this->formatObject($options["child"],$options);
			}
		}

		return implode('',$this->output);
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

		$tpl = new ilTemplate("tpl.tree.html", true, true);

		foreach ($a_option["tab"] as $picture)
		{
			if ($picture == 'plus')
			{
				$target = $this->createTarget('+',$a_node_id);
				$tpl->setCurrentBlock("expander");
				$tpl->setVariable("LINK_TARGET", $target);
				$tpl->setVariable("IMGPATH", ilUtil::getImagePath("browser/plus.gif"));
				$tpl->parseCurrentBlock();
			}

			if ($picture == 'minus')
			{
				$target = $this->createTarget('-',$a_node_id);
				$tpl->setCurrentBlock("expander");
				$tpl->setVariable("LINK_TARGET", $target);
				$tpl->setVariable("IMGPATH", ilUtil::getImagePath("browser/minus.gif"));
				$tpl->parseCurrentBlock();
			}
			
			if ($picture == 'blank' or $picture == 'winkel'
			   or $picture == 'hoch' or $picture == 'quer' or $picture == 'ecke')
			{
				$tpl->setCurrentBlock("expander");
				$tpl->setVariable("IMGPATH", ilUtil::getImagePath("browser/".$picture.".gif"));
				$tpl->setVariable("TXT_ALT_IMG", $lng->txt($a_option["desc"]));
				$tpl->parseCurrentBlock();
			}
			
		}

		$tpl->setCurrentBlock("icon");
		//$tpl->setVariable("TYPE", $a_option["type"]);
		$tpl->setVariable("ICON_IMAGE" ,ilUtil::getImagePath("icon_".$a_option["type"].".gif"));
		$tpl->setVariable("TXT_ALT_IMG", $lng->txt($a_option["desc"]));
		$target = (strpos($this->target, "?") === false) ?
			$this->target."?" : $this->target."&";
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("link");
		$tpl->setVariable("LINK_TARGET", $target.$this->target_get."=".$a_node_id."#".$a_node_id);
		#$a_option["title"] = strlen($a_option["title"]) <= FULLNAME_MAXLENGTH
		#	? $a_option["title"]
		#	: substr($a_option["title"],0,FULLNAME_MAXLENGTH)."...";
		$tpl->setVariable("TITLE", $a_option["title"]);

		if ($this->frame_target != "")
		{
			$tpl->setVariable("TARGET", " target=\"".$this->frame_target."\"");
		}

		$tpl->parseCurrentBlock();

		$this->output[] = $tpl->get();
	}

	/**
	* method to create a forum system specific header
	* @access	public
	* @param	integer obj_id
	* @param	integer array options
	* @return	string
	*/
	function formatHeader()
	{
		global $lng, $ilias;

		$tpl = new ilTemplate("tpl.tree.html", true, true);

		$frm = new ilForum();
		$frm->setWhereCondition("thr_pk = ".$this->thread_id);
		$threadData = $frm->getOneThread();

		$tpl->setCurrentBlock("icon");
		$tpl->setVariable("ICON_IMAGE", ilUtil::getImagePath("icon_frm.gif"));
		$tpl->setVariable("TXT_ALT_IMG", $lng->txt("obj_frm"));
		$tpl->parseCurrentBlock();
		$tpl->setCurrentBlock("link");
		$tpl->setVariable("TITLE", $a_option["title"]." ".$lng->txt("forums_thread").": ".$threadData["thr_subject"]);
		$tpl->setVariable("TARGET","target=content");
		$tpl->setVariable("LINK_TARGET",$this->target);
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
	function createTarget($a_type,$a_node_id)
	{
		if (!isset($a_type) or !is_string($a_type) or !isset($a_node_id))
		{
			$this->ilias->raiseError(get_class($this)."::createTarget(): Missing parameter or wrong datatype! ".
									"type: ".$a_type." node_id:".$a_node_id,$this->ilias->error_obj->WARNING);
		}
		list($tmp,$get) = explode("?",$this->target);
		// SET expand parameter:
		//     positive if object is expanded
		//     negative if object is compressed
		$a_node_id = $a_type == '+' ? $a_node_id : -(int) $a_node_id;

		return $_SERVER["PATH_INFO"]."?".$get."&fexpand=".$a_node_id;
	}

	
	/**
	* set the expand option
	* this value is stored in a SESSION variable to save it different view (lo view, frm view,...)
	* @access	private
	* @param	string		pipe-separated integer
	*/
	function setExpand($a_node_id)
	{
		$first_node = $this->forum->getFirstPostNode($this->thread_id);
		$first_node_id = $first_node["id"];
		$_SESSION["fexpand"] = $_SESSION["fexpand"] ? $_SESSION["fexpand"] : array();

		// IF ISN'T SET CREATE SESSION VARIABLE
		if(empty($_SESSION["fexpand"]) or !in_array($first_node_id,$_SESSION["fexpand"]))
		{
			$all_nodes = $this->forum->getPostTree($first_node);
			foreach($all_nodes as $node)
			{
				$tmp_array[] = $node["id"];
			}
			$_SESSION["fexpand"] = array_merge($tmp_array,$_SESSION["fexpand"]);
		}
		// IF $_GET["expand"] is positive => expand this node
		if($a_node_id > 0 && !in_array($a_node_id,$_SESSION["fexpand"]))
		{
			array_push($_SESSION["fexpand"],$a_node_id);
		}
		// IF $_GET["expand"] is negative => compress this node
		if($a_node_id < 0)
		{
			$key = array_keys($_SESSION["fexpand"],-(int) $a_node_id);
			unset($_SESSION["fexpand"][$key[0]]);
		}
		$this->expanded = $_SESSION["fexpand"];
	}

	function __readThreadSubject()
	{
		$this->forum->setWhereCondition("thr_pk = ".$this->thread_id);
		$threadData = $this->forum->getOneThread();

		$this->thread_subject = $threadData["thr_subject"];
	}
		

} // END class.ilExplorer
?>
