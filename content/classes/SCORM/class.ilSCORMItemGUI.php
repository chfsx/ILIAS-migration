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

require_once ("content/classes/SCORM/class.ilSCORMObjectGUI.php");
require_once ("content/classes/SCORM/class.ilSCORMItem.php");
require_once ("content/classes/SCORM/class.ilSCORMResource.php");
require_once ("classes/class.ilObjSCORMLearningModule.php");

/**
* GUI class for SCORM Items
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @extends ilSCORMItemGUI
* @package content
*/
class ilSCORMItemGUI extends ilSCORMObjectGUI
{
	function ilSCORMItemGUI($a_id)
	{
		parent::ilSCORMObjectGUI();
		$this->sc_object =& new ilSCORMItem($a_id);
	}

	function view()
	{
		global $ilias;

		// get ressource identifier
		$id_ref = $this->sc_object->getIdentifierRef();
		if ($id_ref != "")
		{
			$resource =& new ilSCORMResource();
			$resource->readByIdRef($id_ref, $this->sc_object->getSLMId());

			$slm_obj =& new ilObjSCORMLearningModule($_GET["ref_id"]);

			if ($resource->getHref() != "")
			{
				$param_str = ($this->sc_object->getParameters() != "")
					? "?".$this->sc_object->getParameters()
					: "";

				//header("Location: ../".$slm_obj->getDataDirectory()."/".$resource->getHref());
				$this->tpl =& new ilTemplate("tpl.scorm_content_frameset.html", true, true, true);
				$this->tpl->setVariable("ITEM_LOCATION",$slm_obj->getDataDirectory()."/".$resource->getHref().$param_str);
				$this->tpl->setVariable("ITEM_ID", $_GET["obj_id"]);
				$this->tpl->setVariable("REF_ID", $_GET["ref_id"]);
				$this->tpl->setVariable("USER_ID",$ilias->account->getId());
//				echo "itemid: ".$_GET["obj_id"]."userid: ".$ilias->account->getId();
				$this->tpl->show();
				exit;
			}
		}

		// this point is only reached if now resource could be displayed above!
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.scorm_obj.html", true);
		$this->tpl->setCurrentBlock("par_table");
		$this->tpl->setVariable("TXT_OBJECT_TYPE", $this->lng->txt("cont_item"));
		$this->displayParameter($this->lng->txt("cont_import_id"),
			$this->sc_object->getImportId());
		$this->displayParameter($this->lng->txt("cont_id_ref"),
			$this->sc_object->getIdentifierRef());
		$str_visible = ($this->sc_object->getVisible())
			? "true"
			: "false";
		$this->displayParameter($this->lng->txt("cont_is_visible"),
			$str_visible);
		$this->displayParameter($this->lng->txt("cont_parameters"),
			$this->sc_object->getParameters());
		$this->displayParameter($this->lng->txt("cont_sc_title"),
			$this->sc_object->getTitle());
		$this->displayParameter($this->lng->txt("cont_prereq_type"),
			$this->sc_object->getPrereqType());
		$this->displayParameter($this->lng->txt("cont_prerequisites"),
			$this->sc_object->getPrerequisites());
		$this->displayParameter($this->lng->txt("cont_max_time_allowed"),
			$this->sc_object->getMaxTimeAllowed());
		$this->displayParameter($this->lng->txt("cont_time_limit_action"),
			$this->sc_object->getTimeLimitAction());
		$this->displayParameter($this->lng->txt("cont_data_from_lms"),
			$this->sc_object->getDataFromLms());
		$this->displayParameter($this->lng->txt("cont_mastery_score"),
			$this->sc_object->getMasteryScore());
		$this->tpl->parseCurrentBlock();

	}

	function api()
	{
		global $ilias;

		$this->tpl =& new ilTemplate("tpl.scorm_api.html", true, true, true);
		$this->tpl->setVariable("ITEM_ID", $_GET["obj_id"]);
		$this->tpl->setVariable("USER_ID",$ilias->account->getId());
		$this->tpl->setVariable("SESSION_ID",session_id());
		$this->tpl->show();
		exit;
	}
}
?>
