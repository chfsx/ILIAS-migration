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
* This class provides processing control methods
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
* @package ilias-core
*/
class ilCtrl
{
	var $target_script;
	var $forward;			// forward array
	var $parent;			// parent array (reverse forward)
	var $save_parameter;	// save parameter array
	var $return;			// return commmands

	/**
	*
	*/
	function ilCtrl()
	{
	}

	function getCallStructure($a_class)
	{
		$this->called_forward[$a_class] = $a_class;

		$methods = get_class_methods($a_class);

		if (!is_array($methods))
		{
			$a_class = strtolower($a_class);
			if ($this->parent[$a_class][0] == "")
			{
				echo "<b>Error in ilCtrl::getCallStructure():</b><br> $a_class is not included!";
			}
			else
			{
				echo "<b>Error in ilCtrl::getCallStructure:</b><br> $a_class is not included within ".
					$this->parent[$a_class][0]."!<br><br>";
				echo "$a_class is returned by ".$this->parent[$a_class][0]."::_forwards()".
					" but $a_class is not included at the top of ".$this->parent[$a_class][0].
					" class file.";
			}
			exit;
		}

		if (in_array(strtolower("_forwards"), $methods))
		{
			$forw = call_user_func(array($a_class, "_forwards"));
			$this->forwards($a_class, $forw);
			if (is_array($forw))
			{
				foreach($forw as $forw_class)
				{
					if (!isset($this->called_forward[$forw_class]))
					{
						$this->getCallStructure($forw_class);
					}
				}
			}
		}
	}

	/**
	* stores which classes forward to which other classes
	*/
	function forwards($a_from_class, $a_to_class)
	{
		$a_from_class = strtolower($a_from_class);

		if (is_array($a_to_class))
		{
			foreach($a_to_class as $to_class)
			{
				$this->forward[$a_from_class][] = strtolower($to_class);
				$this->parent[strtolower($to_class)][] = $a_from_class;
			}
		}
		else
		{
			$this->forward[strtolower(get_class($a_obj))][] = strtolower($a_to_class);
			$this->parent[strtolower($a_to_class)][] = strtolower(get_class($a_obj));
		}
	}

	/**
	* set parameters that must be saved in forms an links
	*/
	function saveParameter(&$a_obj, $a_parameter)
	{
		if (is_array($a_parameter))
		{
			foreach($a_parameter as $parameter)
			{
				$this->save_parameter[get_class($a_obj)][] = $parameter;
			}
		}
		else
		{
			$this->save_parameter[strtolower(get_class($a_obj))][] = $a_parameter;
		}
	}

	/**
	* set a parameter (note: if this is also a saved parameter, the saved
	* value will be overwritten)
	*/
	function setParameter(&$a_obj, $a_parameter, $a_value)
	{
		$this->parameter[strtolower(get_class($a_obj))][$a_parameter] = $a_value;
	}

	/**
	* get next class
	*/
	function getNextClass($a_gui_obj)
	{
		$next = $this->searchNext(get_class($a_gui_obj));
		return $next;
	}

	/**
	* private
	*/
	function searchNext($a_class)
	{
		if (is_array($this->forward[$a_class]))
		{
			foreach($this->forward[$a_class] as $next_class)
			{
				if (strtolower($next_class) == strtolower($this->getCmdClass()))
				{
					return $this->getCmdClass();
				}
				else
				{
					$ret = $this->searchNext($next_class);
					if ($ret != "")
					{
						return $next_class;
					}
				}
			}
		}
		return "";
	}

	/**
	* target script name
	*/
	function setTargetScript($a_target_script)
	{
		$this->target_script = $a_target_script;
	}

	function getTargetScript()
	{
		return $this->target_script;
	}

	/**
	* determines current get/post command
	*/
	function getCmd($a_default_cmd = "")
	{
		$cmd = $_GET["cmd"];
		if($cmd == "post")
		{
			$cmd = key($_POST["cmd"]);
		}
		if($cmd == "")
		{
			$cmd = $a_default_cmd;
		}

		return $cmd;
	}

	/**
	* determines responsible class for current command
	*/
	function getCmdClass()
	{
		return strtolower($_GET["cmdClass"]);
	}

	function getFormAction(&$a_gui_obj)
	{
		$script = $this->getLinkTargetByClass(get_class($a_gui_obj), "post");
		return $script;
	}

	function redirect(&$a_gui_obj, $a_cmd = "")
	{
		$script = $this->getLinkTargetByClass(get_class($a_gui_obj), $a_cmd);
		ilUtil::redirect($script);
	}

	function getLinkTarget(&$a_gui_obj, $a_cmd = "")
	{
		return $this->getLinkTargetByClass(get_class($a_gui_obj), $a_cmd);
	}

	function getLinkTargetByClass($a_class, $a_cmd  = "")
	{
		$a_class = strtolower($a_class);
		$cmd_str = ($a_cmd != "")
			? "&cmd=".$a_cmd
			: "";
		$script = $this->getTargetScript();
		$script = $this->getUrlParameters($a_class, $script, $a_cmd);

		return $script;
	}

	/**
	* set return command
	*/
	function setReturn(&$a_gui_obj, $a_cmd)
	{
		$this->return[get_class($a_gui_obj)] = $a_cmd;
	}

	/**
	* redirects to next parent class that used setReturn
	*/
	function returnToParent($a_gui_obj)
	{
		$a_class = strtolower(get_class($a_gui_obj));

		$ret_class = $this->searchReturnClass($a_class);
		if($ret_class)
		{
			$script = $this->getTargetScript();
			$script = $this->getUrlParameters($ret_class, $script, $this->return[$ret_class]);
			ilUtil::redirect($script);
		}
	}

	function searchReturnClass($a_class)
	{
		// append parameters of parent classes
		if (is_array($this->parent[$a_class]))
		{
			foreach($this->parent[$a_class] as $parent)
			{
				if ($this->return[$parent] != "")
				{
					return $parent;
				}
			}
			foreach($this->parent[$a_class] as $parent)
			{
				$par_ret = $this->searchReturnClass($parent);
				if ($par_ret != "")
				{
					return $par_ret;
				}
			}
		}
		return false;
	}

	function getUrlParameters($a_class, $a_str, $a_cmd = "")
	{
		$a_class = strtolower($a_class);

		$params = $this->getParameterArrayByClass($a_class, $a_cmd);
		foreach ($params as $par => $value)
		{
			$a_str = ilUtil::appendUrlParameterString($a_str, $par."=".$value);
		}

		return $a_str;
	}

	function getParameterArray(&$a_gui_obj, $a_cmd = "")
	{
		return $this->getParameterArrayByClass(get_class($a_gui_obj), $a_cmd);
	}

	function getParameterArrayByClass($a_class, $a_cmd = "")
	{
		$a_class = strtolower($a_class);

		$params = array();

		// append parameters of parent classes
		if (is_array($this->parent[$a_class]))
		{
			foreach($this->parent[$a_class] as $parent)
			{
				$par_params = $this->getParameterArrayByClass($parent);
				foreach($par_params as $key => $value)
				{
					$params[$key] = $value;
				}
			}
		}

		// append parameters of current class
		if (is_array($this->save_parameter[$a_class]))
		{
			foreach($this->save_parameter[$a_class] as $par)
			{
				$params[$par] = $_GET[$par];
			}
		}
		if (is_array($this->parameter[$a_class]))
		{
			foreach($this->parameter[$a_class] as $par => $value)
			{
				$params[$par] = $value;
			}
		}

		if ($a_cmd != "")
		{
			$params["cmd"] = $a_cmd;
		}
		$params["cmdClass"] = $a_class;

		return $params;
	}


} // END class.ilCtrl
?>
