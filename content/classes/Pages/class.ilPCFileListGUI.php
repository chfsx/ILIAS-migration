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

require_once("./content/classes/Pages/class.ilPCFileList.php");
require_once("./content/classes/Pages/class.ilPageContentGUI.php");

/**
* Class ilPCListGUI
*
* User Interface for LM List Editing
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilPCFileListGUI extends ilPageContentGUI
{

	/**
	* Constructor
	* @access	public
	*/
	function ilPCFileListGUI(&$a_pg_obj, &$a_content_obj, $a_hier_id)
	{
		parent::ilPageContentGUI($a_pg_obj, $a_content_obj, $a_hier_id);
	}


	/**
	* insert new list form
	*/
	function insert()
	{
		// new list form (list item number)
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.file_list_new.html", true);
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_insert_file_list"));
		$this->tpl->setVariable("FORMACTION",
			ilUtil::appendUrlParameterString($this->getTargetScript(),
			"hier_id=".$this->hier_id."&cmd=edpost"));

		$this->displayValidationError();

		// select fields for number of columns
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("INPUT_TITLE", "flst_title");
		$this->tpl->setVariable("TXT_FILE", $this->lng->txt("file"));

		// language
		$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		$lang = ilMetaData::getLanguages();
		$select_lang = ilUtil::formSelect ("","flst_language",$lang,false,true);
		$this->tpl->setVariable("SELECT_LANGUAGE", $select_lang);


		$this->tpl->parseCurrentBlock();

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "create_flst");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();

	}


	/**
	* create new table in dom and update page in db
	*/
	function create()
	{
		include_once("classes/class.ilObjFile.php");
		$fileObj = new ilObjFile();
		$fileObj->setType("file");
		$fileObj->setTitle($_FILES["Fobject"]["name"]["file"]);
		$fileObj->setDescription("");
		$fileObj->setFileName($_FILES["Fobject"]["name"]["file"]);
		$fileObj->setFileType($_FILES["Fobject"]["type"]["file"]);
		$fileObj->create();
		// upload file to filesystem
		$fileObj->createDirectory();
		$fileObj->getUploadFile($_FILES["Fobject"]["tmp_name"]["file"],
			$_FILES["Fobject"]["name"]["file"]);

//echo "::".is_object($this->dom).":";
		$this->content_obj = new ilPCFileList($this->dom);
		$this->content_obj->create($this->pg_obj, $this->hier_id);
		$this->content_obj->setListTitle($_POST["flst_title"], $_POST["flst_language"]);
		$this->content_obj->appendItem($fileObj->getId(), $fileObj->getFileName(),
			$fileObj->getFileType());
		$this->updated = $this->pg_obj->update();
		if ($this->updated === true)
		{
			header("Location: ".$this->getReturnLocation());
			exit;
		}
		else
		{
			$this->insert();
		}
	}

	/**
	* edit properties form
	*/
	function edit()
	{
		// add paragraph edit template
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.file_list_new.html", true);
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_edit_file_list_properties"));
		$this->tpl->setVariable("FORMACTION",
			ilUtil::appendUrlParameterString($this->getTargetScript(),
			"hier_id=".$this->hier_id."&cmd=edpost"));

		$this->displayValidationError();

		// select fields for number of columns
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("INPUT_TITLE", "flst_title");
		$this->tpl->setVariable("VALUE_TITLE", $this->content_obj->getListTitle());

		// language
		$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		$lang = ilMetaData::getLanguages();
		$select_lang = ilUtil::formSelect ($this->content_obj->getLanguage(),"flst_language",$lang,false,true);
		$this->tpl->setVariable("SELECT_LANGUAGE", $select_lang);


		$this->tpl->parseCurrentBlock();

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "saveProperties");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();

	}


	/**
	* save table properties in db and return to page edit screen
	*/
	function saveProperties()
	{
		$this->content_obj->setListTitle($_POST["flst_title"], $_POST["flst_language"]);
		$this->updated = $this->pg_obj->update();
		if ($this->updated === true)
		{
			header("Location: ".$this->getReturnLocation());
			exit;
		}
		else
		{
			$this->pg_obj->addHierIDs();
			$this->edit();
		}
	}
}
?>
