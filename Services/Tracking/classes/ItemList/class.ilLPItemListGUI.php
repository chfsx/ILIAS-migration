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
* Class ilLPItemListGUI
*
* @author Stefan Meyer <smeyer@databay.de>
*
* @version $Id$
*
* @extends ilObjectGUI
* @package ilias-core
*
*/

include_once 'Services/Tracking/classes/class.ilLPStatus.php';
include_once 'Services/Tracking/classes/class.ilLPObjSettings.php';
include_once 'Services/Tracking/classes/class.ilLPStatusWrapper.php';

class ilLPItemListGUI
{
	var $db = null;
	var $anonymized = false;

	function ilLPItemListGUI($a_id,$a_type)
	{
		global $ilDB,$lng,$ilErr,$tree,$ilObjDataCache,$ilCtrl;

		$this->db =& $ilDB;
		$this->lng =& $lng;
		$this->err =& $ilErr;
		$this->tree =& $tree;
		$this->obj_cache = $ilObjDataCache;
		$this->ctrl =& $ilCtrl;

		$this->id = $a_id;
		$this->type = $a_type;
	}

	function isAnonymized()
	{
		return $this->anonymized;
	}

	function setCmdClass($a_class)
	{
		$this->cmd_class = $a_class;
	}
	function getCmdClass()
	{
		return $this->cmd_class;
	}

	function setRefId($a_ref_id)
	{
		$this->ref_id = $a_ref_id;
	}
	function getRefId()
	{
		return $this->ref_id;
	}
	function getId()
	{
		return $this->id;
	}
	function getType()
	{
		return $this->type;
	}

	function setCurrentUser($a_user)
	{
		$this->user = $a_user;
	}
	function getCurrentUser()
	{
		return $this->user;
	}

	function getMode()
	{
		return $this->mode;
	}

	function getTitle()
	{
		return $this->title;
	}
	function getDescription()
	{
		return $this->description;
	}

	function getMark()
	{
		return $this->mark;
	}
	function getComment()
	{
		return $this->comment;
	}
	function getTypicalLearningTime()
	{
		return $this->tlt ? $this->tlt : 0;
	}
	function hasDetails()
	{
		return true;
	}
	function enabled($a_key)
	{
		return $this->enabled[$key];
	}

	function enable($a_key)
	{
		$this->enabled[$key] = true;
	}
	function disable($a_key)
	{
		$this->enabled[$key] = false;
	}

	function setIndentLevel($a_level)
	{
		$this->level = $a_level;
	}

	function getUserStatus()
	{
		return $this->status;
	}

	function getEditingTime()
	{
		return $this->editing_time;
	}

	function showTimingWarning()
	{
		if(!$this->editing_time)
		{
			return false;
		}
		if($this->status == LP_STATUS_COMPLETED)
		{
			return false;
		}
		#print_r('<br>');
		#print_r(date('Y-m-d H:i',$this->editing_time));
		#print_r('<br>');
		#print_r(date('Y-m-d H:i',time()));
		return $this->editing_time < time();
	}

	function addCheckBox($a_check)
	{
		$this->checkbox = $a_check;
	}

	function __readMode()
	{
		include_once 'Services/Tracking/classes/class.ilLPObjSettings.php';
		$this->mode = ilLPObjSettings::_lookupMode($this->getId());
	}

	function __readStatusInfo()
	{
	}
	function __readUserStatus()
	{
	}
	function __readTypicalLearningTime()
	{
	}
	function __readTimings()
	{
		$this->timings = array();
	}
	
	function getHTML()
	{
		return $this->html;
	}

	/**
	* Read all necassary data for output
	*
	* @access public
	*/
	function read()
	{
		$this->__readMode();
		$this->__readStatusInfo();
		$this->__readTypicalLearningTime();
		$this->__readTitle();
		$this->__readDescription();
	}

	function readUserInfo()
	{
		if($this->getCurrentUser())
		{
			$this->__readMark();
			$this->__readComment();
			$this->__readUserStatus();
			$this->__readUserStatusInfo();
			$this->__readEditingTime();
		}
	}

	function readTimings()
	{
		if($this->getRefId())
		{
			include_once 'course/classes/Timings/class.ilTimingCache.php';
			$this->timings =& ilTimingCache::_getTimings($this->getRefId());
		}
	}
	function renderTypeImage()
	{
		$this->tpl->setCurrentBlock("row_type_image");
		$this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_'.$this->getType().'.gif'));
		$this->tpl->setVariable("TYPE_ALT_IMG",$this->lng->txt('obj_'.$this->getType()));
		$this->tpl->parseCurrentBlock();
	}


	function renderContainerProgress()
	{
		$this->tpl = new ilTemplate('tpl.lp_item_list_row.html',true,true,'Services/Tracking');

		$this->renderTypeImage();

		$this->tpl->setVariable("TXT_TITLE",$this->getTitle());
		if(strlen($this->getDescription()))
		{
			$this->tpl->setVariable("TXT_DESC",$this->getDescription());
		}
		// Status info
		if($this->user_status_info)
		{
			$this->tpl->setCurrentBlock("status_info");
			$this->tpl->setVariable("STATUS_PROP",$this->user_status_info[0]);
			$this->tpl->setVariable("STATUS_VAL",$this->user_status_info[1]);
			$this->tpl->parseCurrentBlock();
		}

		// Status
		$this->tpl->setCurrentBlock("item_property");
		$this->tpl->setVariable("TXT_PROP",$this->lng->txt('trac_status'));
		$this->tpl->setVariable("VAL_PROP",$this->lng->txt($this->getUserStatus()));
		$this->tpl->parseCurrentBlock();

		for($i = 0;$i < $this->level;$i++)
		{
			$this->tpl->touchBlock('start_indent');
			$this->tpl->touchBlock('end_indent');
		}

		$this->html = $this->tpl->get();
	}

	function renderSimpleProgress()
	{
		$this->tpl = new ilTemplate('tpl.lp_item_list_row.html',true,true,'Services/Tracking');

		if(is_array($this->checkbox))
		{
			$this->tpl->setVariable("CHECK_NAME",$this->checkbox[0]);
			$this->tpl->setVariable("CHECK_VALUE",$this->checkbox[1]);
			if($this->checkbox[2])
			{
				$this->tpl->setVariable("CHECK_CHECKED",'checked="checked"');
			}
		}
		$this->tpl->setVariable("TXT_TITLE",$this->getTitle());
		if(strlen($this->getDescription()))
		{
			$this->tpl->setVariable("TXT_DESC",$this->getDescription());
		}

		// Status info
		if($this->user_status_info)
		{
			$this->tpl->setCurrentBlock("status_info");
			$this->tpl->setVariable("STATUS_PROP",$this->user_status_info[0]);
			$this->tpl->setVariable("STATUS_VAL",$this->user_status_info[1]);
			$this->tpl->parseCurrentBlock();
		}

		// Status
		$this->tpl->setCurrentBlock("item_property");
		$this->tpl->setVariable("TXT_PROP",$this->lng->txt('trac_status'));
		$this->tpl->setVariable("VAL_PROP",$this->lng->txt($this->getUserStatus()));
		$this->tpl->parseCurrentBlock();

		// Path
		if($this->enabled('path'))
		{
			$this->renderPath(true);
		}

		
		$this->html = $this->tpl->get();
	}

	function renderSimpleProgressFO()
	{
		global $tpl;
		$this->tpl =& $tpl;

		if(strlen($this->getDescription()))
		{
			$this->tpl->setCurrentBlock("description");
			$this->tpl->setVariable("OBJ_DESC",ilXmlWriter::_xmlEscapeData($this->getDescription()));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("status_row");
		$this->tpl->setVariable("STATUS_PROP",ilXmlWriter::_xmlEscapeData($this->lng->txt('trac_status')));
		$this->tpl->setVariable("STATUS_VAL",ilXmlWriter::_xmlEscapeData($this->lng->txt($this->getUserStatus())));
		$this->tpl->parseCurrentBlock();

		if($this->user_status_info)
		{
			$this->tpl->setCurrentBlock("status_row");
			$this->tpl->setVariable("STATUS_PROP",ilXmlWriter::_xmlEscapeData($this->user_status_info[0]));
			$this->tpl->setVariable("STATUS_VAL",ilXmlWriter::_xmlEscapeData($this->user_status_info[1]));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("item");
		$this->tpl->setVariable("OBJ_TITLE",ilXmlWriter::_xmlEscapeData($this->getTitle()));
		$this->tpl->parseCurrentBlock();

	}
		

	function renderObjectList()
	{
		$this->tpl = new ilTemplate('tpl.lp_item_list_row.html',true,true,'Services/Tracking');

		if(is_array($this->checkbox))
		{
			$this->tpl->setVariable("CHECK_NAME",$this->checkbox[0]);
			$this->tpl->setVariable("CHECK_VALUE",$this->checkbox[1]);
			if($this->checkbox[2])
			{
				$this->tpl->setVariable("CHECK_CHECKED",'checked="checked"');
			}
		}
		$this->tpl->setVariable("TXT_TITLE",$this->getTitle());
		if(strlen($this->getDescription()))
		{
			$this->tpl->setVariable("TXT_DESC",$this->getDescription());
		}

		// Status info
		include_once 'Services/Tracking/classes/class.ilLPStatusWrapper.php';
		if($num_na = ilLPStatusWrapper::_getCountNotAttempted($this->getId()))
		{
			$this->tpl->setCurrentBlock("item_property");
			$this->tpl->setVariable("TXT_PROP",$this->lng->txt('trac_not_attempted'));
			$this->tpl->setVariable("VAL_PROP",$num_na);
			$this->tpl->parseCurrentBlock();
			$this->tpl->touchBlock('newline_prop');
		}
		
		$this->tpl->setCurrentBlock("item_property");
		$this->tpl->setVariable("TXT_PROP",$this->lng->txt('trac_in_progress'));
		$this->tpl->setVariable("VAL_PROP",ilLPStatusWrapper::_getCountInProgress($this->getId()));
		$this->tpl->parseCurrentBlock();
		$this->tpl->touchBlock('newline_prop');

		$this->tpl->setCurrentBlock("item_property");
		$this->tpl->setVariable("TXT_PROP",$this->lng->txt('trac_completed'));
		$this->tpl->setVariable("VAL_PROP",ilLPStatusWrapper::_getCountCompleted($this->getId()));
		$this->tpl->parseCurrentBlock();

		if($num_failed = ilLPStatusWrapper::_getCountFailed($this->getId()))
		{
			$this->tpl->touchBlock('newline_prop');
			$this->tpl->setCurrentBlock("item_property");
			$this->tpl->setVariable("TXT_PROP",$this->lng->txt('trac_failed'));
			$this->tpl->setVariable("VAL_PROP",ilLPStatusWrapper::_getCountFailed($this->getId()));
			$this->tpl->parseCurrentBlock();
		}			

		// Path
		if($this->enabled('path'))
		{
			$this->renderPath(false);
		}

		
		$this->html = $this->tpl->get();
	}

	function renderObjectListFO()
	{
		global $tpl;
		$this->tpl =& $tpl;

		if(strlen($this->getDescription()))
		{
			$this->tpl->setCurrentBlock("description");
			$this->tpl->setVariable("OBJ_DESC",ilXmlWriter::_xmlEscapeData($this->getDescription()));
			$this->tpl->parseCurrentBlock();
		}
		include_once 'Services/Tracking/classes/class.ilLPStatusWrapper.php';
		if($num_na = ilLPStatusWrapper::_getCountNotAttempted($this->getId()))
		{
			$this->tpl->setCurrentBlock("status_row");
			$this->tpl->setVariable("STATUS_PROP",ilXmlWriter::_xmlEscapeData($this->lng->txt('trac_not_attempted')));
			$this->tpl->setVariable("STATUS_VAL",ilLPStatusWrapper::_getCountNotAttempted($this->getId()));
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setCurrentBlock("status_row");
		$this->tpl->setVariable("STATUS_PROP",ilXmlWriter::_xmlEscapeData($this->lng->txt('trac_in_progress')));
		$this->tpl->setVariable("STATUS_VAL",ilLPStatusWrapper::_getCountInProgress($this->getId()));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("status_row");
		$this->tpl->setVariable("STATUS_PROP",ilXmlWriter::_xmlEscapeData($this->lng->txt('trac_completed')));
		$this->tpl->setVariable("STATUS_VAL",ilLPStatusWrapper::_getCountCompleted($this->getId()));
		$this->tpl->parseCurrentBlock();

		if($num_failed = ilLPStatusWrapper::_getCountFailed($this->getId()))
		{
			$this->tpl->setCurrentBlock("status_row");
			$this->tpl->setVariable("STATUS_PROP",$this->lng->txt('trac_failed'));
			$this->tpl->setVariable("STATUS_VAL",ilLPStatusWrapper::_getCountFailed($this->getId()));
			$this->tpl->parseCurrentBlock();
		}			

		$this->tpl->setCurrentBlock("item");
		$this->tpl->setVariable("OBJ_TITLE",ilXmlWriter::_xmlEscapeData($this->getTitle()));
		$this->tpl->parseCurrentBlock();
	}

	function renderObjectDetails()
	{
		$this->tpl = new ilTemplate('tpl.lp_item_list_row.html',true,true,'Services/Tracking');

		$this->renderTypeImage();

		if(is_array($this->checkbox))
		{
			$this->tpl->setVariable("CHECK_NAME",$this->checkbox[0]);
			$this->tpl->setVariable("CHECK_VALUE",$this->checkbox[1]);
			if($this->checkbox[2])
			{
				$this->tpl->setVariable("CHECK_CHECKED",'checked="checked"');
			}
		}

		$this->tpl->setVariable("TXT_TITLE",$this->getTitle());
		if(strlen($this->getDescription()))
		{
			$this->tpl->setVariable("TXT_DESC",$this->getDescription());
		}

		// Status info
		if($this->user_status_info)
		{
			$this->tpl->setCurrentBlock("status_info");
			$this->tpl->setVariable("STATUS_PROP",$this->user_status_info[0]);
			$this->tpl->setVariable("STATUS_VAL",$this->user_status_info[1]);
			$this->tpl->parseCurrentBlock();
		}

		// Status
		$this->tpl->setCurrentBlock("item_property");
		$this->tpl->setVariable("TXT_PROP",$this->lng->txt('trac_status'));
		$this->tpl->setVariable("VAL_PROP",$this->lng->txt($this->getUserStatus()));
		$this->tpl->parseCurrentBlock();

		// Comment
		if(strlen($this->getComment()))
		{
			$this->tpl->setCurrentBlock("info_property");
			$this->tpl->setVariable("INFO_TXT_PROP",$this->lng->txt('trac_comment'));
			$this->tpl->setVariable("INFO_VAL_PROP",$this->getComment());
			$this->tpl->parseCurrentBlock();
		}

		for($i = 0;$i < $this->level;$i++)
		{
			$this->tpl->touchBlock('start_indent');
			$this->tpl->touchBlock('end_indent');
		}

		$this->html = $this->tpl->get();
	}

	function __readEditingTime()
	{
		if(!$this->enabled('timings'))
		{
			return false;
		}
		if($this->timings['item']['changeable'] and $this->timings['user'][$this->getCurrentUser()]['end'])
		{
			$end = $this->timings['user'][$this->getCurrentUser()]['end'];
		}
		else
		{
			$end = $this->timings['item']['suggestion_end'];
		}
		$this->editing_time = $end;
	}
			

	// Private
	function __getPercent($max,$reached)
	{
		if(!$max)
		{
			return "0%";
		}

		return sprintf("%.2f%%",$reached / $max * 100);
	}

}
?>