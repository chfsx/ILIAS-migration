<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2008 ILIAS open source, University of Cologne            |
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

include_once("Services/Table/classes/class.ilTable2GUI.php");

/**
* TableGUI class for learning objectives alignments
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ingroup ModulesScormAicc
*/
class ilObjectivesAlignmentTableGUI extends ilTable2GUI
{

	function __construct($a_parent_obj, $a_parent_cmd,
		$a_tree, $a_slm_obj, $a_chap)
	{
		global $ilCtrl, $lng;
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->tree = $a_tree;
		$this->slm_object = $a_slm_obj;
		$this->chap = $a_chap;
		$this->addColumn($lng->txt("sahs_sco_objective"), "", "50%");
		$this->addColumn($lng->txt("sahs_questions"), "", "50%");
		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
		$this->setRowTemplate("tpl.objectives_alignment_table_row.html",
			"Modules/Scorm2004");
		$this->getScos();
		$this->setNoEntriesText($lng->txt("sahs_oa_no_scos"));
		//$this->setTitle($lng->txt("sahs_objectives_alignment"));
		include_once("./Modules/Scorm2004/classes/class.ilSCORM2004Node.php");
		$this->setTitle(ilScorm2004Node::_lookupTitle($this->chap),
			"icon_chap.gif");
	}
	
	/**
	* Get scos for list (we may present this in an other way in the future)
	*/
	function getScos()
	{
		$nodes = $this->tree->getChilds($this->chap);
		$scos = array();

		$nr = 1;
		foreach($nodes as $node)
		{
			if ($node["type"] == "sco")
			{
				$node["nr"] = $nr++;
				$scos[] = $node;
			}
		}

		$this->setDefaultOrderField("nr");
		$this->setDefaultOrderDirection("asc");
		$this->setData($scos);
	}
	
	/**
	* Standard Version of Fill Row. Most likely to
	* be overwritten by derived class.
	*/
	protected function fillRow($a_set)
	{
		global $lng, $ilCtrl;

		$lng->loadLanguageModule("assessment");
		include_once("./Modules/Scorm2004/classes/class.ilSCORM2004NodeFactory.php");
		$node_object = ilSCORM2004NodeFactory::getInstance($this->slm_object,
			$a_set["child"], false);
		$tr_data = $node_object->getObjectives();
		
		// learning objectives
		foreach($tr_data as $data)
		{
			$this->tpl->setCurrentBlock("objective");
			$this->tpl->setVariable("TXT_LEARNING_OBJECTIVE", $data->getObjectiveID());
			$this->tpl->setVariable("IMG_LOBJ", ilUtil::getImagePath("icon_lobj_s.gif"));
			$this->tpl->parseCurrentBlock();
		}
		
		// pages
		include_once("./Modules/Scorm2004/classes/class.ilSCORM2004Page.php");
		include_once("./Modules/TestQuestionPool/classes/class.assQuestion.php");
		$childs = $this->tree->getChilds($a_set["child"]);
		foreach ($childs as $child)
		{
			// get question ids
			$qids = ilSCORM2004Page::_getQuestionIdsForPage("sahs", $child["child"]);

			if (count($qids) > 0)
			{
				// output questions
				foreach ($qids as $qid)
				{
					$this->tpl->setCurrentBlock("question");
					//$qtitle = assQuestion::_getTitle($qid);
					$qtype = assQuestion::_getQuestionType($qid);
					$qtext = assQuestion::_getQuestionText($qid);
					$this->tpl->setVariable("TXT_QUESTION", $qtext);
					$this->tpl->setVariable("TXT_QTYPE", $lng->txt($qtype));
					$this->tpl->setVariable("IMG_QST",
						ilUtil::getImagePath("comment_question.gif"));
					$this->tpl->parseCurrentBlock();
				}

				// output page title
				$page_title = ilSCORM2004Node::_lookupTitle($child["child"]);
				$this->tpl->setCurrentBlock("page");
				$this->tpl->setVariable("TXT_PAGE_TITLE", $page_title);
				$this->tpl->setVariable("IMG_PAGE", ilUtil::getImagePath("icon_pg_s.gif"));
				$ilCtrl->setParameterByClass("ilscorm2004pagenodegui", "obj_id", $child["child"]);
				$this->tpl->setVariable("HREF_EDIT_PAGE",
					$ilCtrl->getLinkTargetByClass("ilscorm2004pagenodegui",
						"edit"));
				$this->tpl->parseCurrentBlock();
			}
		}
		
		// sco title
		$this->tpl->setVariable("TXT_SCO_TITLE", $a_set["title"]);
		$this->tpl->setVariable("IMG_SCO", ilUtil::getImagePath("icon_sco_s.gif"));
		$ilCtrl->setParameterByClass("ilscorm2004scogui", "obj_id", $a_set["child"]);
		$this->tpl->setVariable("HREF_EDIT_SCO",
			$ilCtrl->getLinkTargetByClass("ilscorm2004scogui",
				"showProperties"));
	}

}
?>
