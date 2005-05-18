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

require_once ("content/classes/Pages/class.ilPageContentGUI.php");
//require_once ("content/classes/Pages/class.ilMediaObject.php");
require_once ("content/classes/Pages/class.ilMediaAliasItem.php");
require_once ("content/classes/Media/class.ilObjMediaObjectGUI.php");

/**
* Class ilPCMediaObjectGUI
*
* Editing User Interface for MediaObjects within LMs (see ILIAS DTD)
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
// Todo: extend ilObjMediaObjectGUI !?
class ilPCMediaObjectGUI extends ilPageContentGUI
{
	var $header;
	var $ctrl;

	function ilPCMediaObjectGUI(&$a_pg_obj, &$a_content_obj, $a_hier_id = 0)
	{
		global $ilCtrl;

		$this->ctrl =& $ilCtrl;

//echo "constructor target:".$_SESSION["il_map_il_target"].":<br>";
		parent::ilPageContentGUI($a_pg_obj, $a_content_obj, $a_hier_id);
	}

	function _forwards()
	{
		return array();
	}

	function setHeader($a_title = "")
	{
		$this->header = $a_title;
	}

	function getHeader()
	{
		return $this->header;
	}


	/**
	* insert new media object form
	*/
	function insert($a_post_cmd = "edpost", $a_submit_cmd = "create_mob")
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.mob_new.html", "content");
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_insert_mob"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$this->displayValidationError();

		// select fields for number of columns
		$this->tpl->setVariable("TXT_STANDARD_VIEW", $this->lng->txt("cont_std_view"));
		$this->tpl->setVariable("TXT_FILE", $this->lng->txt("cont_file"));
		$this->tpl->setVariable("TXT_REFERENCE", $this->lng->txt("cont_reference"));
		$this->tpl->setVariable("TXT_REF_HELPTEXT", $this->lng->txt("cont_ref_helptext"));
		$this->tpl->setVariable("TXT_WIDTH", $this->lng->txt("cont_width"));
		$this->tpl->setVariable("TXT_HEIGHT", $this->lng->txt("cont_height"));
		$this->tpl->setVariable("TXT_ORIGINAL_SIZE", $this->lng->txt("cont_orig_size"));
		$this->tpl->setVariable("TXT_CAPTION", $this->lng->txt("cont_caption"));
		$this->tpl->setVariable("TXT_FULLSCREEN_VIEW", $this->lng->txt("cont_fullscreen"));
		$this->tpl->setVariable("TXT_PARAMETER", $this->lng->txt("cont_parameter"));
		$this->tpl->setVariable("TXT_RESIZE", $this->lng->txt("cont_resize_image"));
		$this->tpl->setVariable("TXT_RESIZE_EXPLANATION", $this->lng->txt("cont_resize_explanation"));
		$this->tpl->parseCurrentBlock();

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", $a_submit_cmd);
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();

	}

	/**
	* create new media object in dom and update page in db
	*/
	function &create($a_create_alias = true)
	{
		// determinte title and format
		if ($_POST["standard_type"] == "File")
		{
			$title = $_FILES['standard_file']['name'];
		}
		else
		{
			$title = $_POST["standard_reference"];
		}

		// create dummy object in db (we need an id)
		$this->content_obj = new ilObjMediaObject();
//		$dummy_meta =& new ilMetaData();
//		$dummy_meta->setObject($this->content_obj);
//		$this->content_obj->assignMetaData($dummy_meta);
		$this->content_obj->setTitle($title);
		$this->content_obj->setDescription("");
		$this->content_obj->create();

		// determine and create mob directory, move uploaded file to directory
		//$mob_dir = ilUtil::getWebspaceDir()."/mobs/mm_".$this->content_obj->getId();
		$this->content_obj->createDirectory();
		$mob_dir = ilObjMediaObject::_getDirectory($this->content_obj->getId());

		$media_item =& new ilMediaItem();
		$this->content_obj->addMediaItem($media_item);
		$media_item->setPurpose("Standard");
//		$meta =& $this->content_obj->getMetaData();
//		$meta_technical =& new ilMetaTechnical($meta);

		if ($_POST["standard_type"] == "File")
		{
			$file = $mob_dir."/".$_FILES['standard_file']['name'];
			//move_uploaded_file($_FILES['standard_file']['tmp_name'], $file);
			if (!ilUtil::moveUploadedFile($_FILES['standard_file']['tmp_name'],
				$_FILES['standard_file']['name'], $file, false))
			{
				$this->content_obj->delete();
				$this->ctrl->returnToParent($this, "jump".$this->hier_id);
				return;
			}

			// get mime type
			$format = ilObjMediaObject::getMimeType($file);
			$location = $_FILES['standard_file']['name'];

			// resize standard images
			if ($_POST["standard_size"] != "original" &&
				$_POST["standard_resize"] == "y" &&
				is_int(strpos($format, "image")))
			{
				$location = ilObjMediaObject::_resizeImage($file, $_POST["standard_width"],
					$_POST["standard_height"]);
			}

			// set real meta and object data
			$media_item->setFormat($format);
			$media_item->setLocation($location);
			$media_item->setLocationType("LocalFile");
//			$meta_technical->addFormat($format);
//			$meta_technical->setSize($_FILES['standard_file']['size']);
//			$meta_technical->addLocation("LocalFile", $location);
			$this->content_obj->setTitle($_FILES['standard_file']['name']);
		}
		else	// standard type: reference
		{
			$format = ilObjMediaObject::getMimeType($_POST["standard_reference"]);
			$media_item->setFormat($format);
			$media_item->setLocation($_POST["standard_reference"]);
			$media_item->setLocationType("Reference");
//			$meta_technical->addFormat($format);
//			$meta_technical->setSize(0);
//			$meta_technical->addLocation("Reference", $_POST["standard_reference"]);
			$this->content_obj->setTitle($_POST["standard_reference"]);
		}
//		$meta->addTechnicalSection($meta_technical);
		$this->content_obj->setDescription($format);

		// determine width and height of known image types
		if ($_POST["standard_size"] == "original")
		{
			if (ilUtil::deducibleSize($format))
			{
				$size = getimagesize($file);
				$media_item->setWidth($size[0]);
				$media_item->setHeight($size[1]);
			}
		}
		else
		{
			$media_item->setWidth($_POST["standard_width"]);
			$media_item->setHeight($_POST["standard_height"]);
		}

		if ($_POST["standard_caption"] != "")
		{
			$media_item->setCaption($_POST["standard_caption"]);
		}

		if ($_POST["standard_param"] != "")
		{
			$media_item->setParameters(ilUtil::stripSlashes(utf8_decode($_POST["standard_param"])));
		}

		$media_item->setHAlign("Left");

		// fullscreen view
		if ($_POST["fullscreen"] == "y")
		{
			$media_item =& new ilMediaItem();
			$this->content_obj->addMediaItem($media_item);
			$media_item->setPurpose("Fullscreen");

			// file
			if ($_POST["full_type"] == "File")
			{
				if ($_FILES['full_file']['name'] != "")
				{
					$file = $mob_dir."/".$_FILES['full_file']['name'];
					//move_uploaded_file($_FILES['full_file']['tmp_name'], $file);
					if (!ilUtil::moveUploadedFile($_FILES['full_file']['tmp_name'],
						$_FILES['full_file']['name'], $file, false))
					{
						$this->content_obj->delete();
						$this->ctrl->returnToParent($this, "jump".$this->hier_id);
						return;
					}
				}

				if ($_FILES['full_file']['name'] != "" ||
						($_POST["full_size"] != "original" &&
						$_POST["full_resize"] == "y" &&
						is_int(strpos($format, "image")))
					)
				{
					// set real meta and object data
					$format = ilObjMediaObject::getMimeType($file);
					$location = $_FILES['full_file']['name'];

					// resize fullscreen images
					if ($_POST["full_size"] != "original" &&
						$_POST["full_resize"] == "y" &&
						is_int(strpos($format, "image")))
					{
						$location = ilObjMediaObject::_resizeImage($file, $_POST["full_width"],
							$_POST["full_height"]);
					}
				}

				$media_item->setFormat($format);
				$media_item->setLocation($location);
				$media_item->setLocationType("LocalFile");
/*
				$meta_technical->addFormat($format);
				$meta_technical->setSize($meta_technical->getSize()
				+ $_FILES['full_file']['size']);
				$meta_technical->addLocation("LocalFile", $location);
*/
			}
			else	// reference
			{
				if ($_POST["full_reference"] != "")
				{
					$format = ilObjMediaObject::getMimeType($_POST["full_reference"]);
					$media_item->setFormat($format);
					$media_item->setLocation($_POST["full_reference"]);
					$media_item->setLocationType("Reference");
/*
					$meta_technical->addFormat($format);
					$meta_technical->addLocation("Reference", $_POST["full_reference"]);
*/
				}
			}

			// determine width and height of known image types
			if ($_POST["full_size"] == "original")
			{
				if (ilUtil::deducibleSize($format))
				{
					$size = getimagesize($file);
					$media_item->setWidth($size[0]);
					$media_item->setHeight($size[1]);
				}
			}
			else
			{
				$media_item->setWidth($_POST["full_width"]);
				$media_item->setHeight($_POST["full_height"]);
			}

			if ($_POST["full_caption"] != "")
			{
				$media_item->setCaption($_POST["full_caption"]);
			}

			if ($_POST["full_param"] != "")
			{
				$media_item->setParameters(ilUtil::stripSlashes(utf8_decode($_POST["full_param"])));
			}

		}
		ilUtil::renameExecutables($mob_dir);
		$this->content_obj->update();

		if ($a_create_alias)
		{
			$this->content_obj->setDom($this->dom);
			$this->content_obj->createAlias($this->pg_obj, $this->hier_id);
			$this->updated = $this->pg_obj->update();
			if ($this->updated === true)
			{
				$this->ctrl->returnToParent($this, "jump".$this->hier_id);
			}
			else
			{
				$this->insert();
			}
		}
		else
		{
			return $this->content_obj;
		}
	}



	/**
	* edit properties form
	*/
	function editAlias()
	{
		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		//$item_nr = $this->content_obj->getMediaItemNr("Standard");
		$std_alias_item =& new ilMediaAliasItem($this->dom, $this->getHierId(), "Standard");
		$std_item =& $this->content_obj->getMediaItem("Standard");
//echo htmlentities($this->dom->dump_node($std_alias_item->item_node));
		// edit media alias template
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.mob_alias_properties.html", "content");
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_edit_mob_alias_prop"));
		$this->tpl->setVariable("TXT_STANDARD_VIEW", $this->lng->txt("cont_std_view"));
		$this->tpl->setVariable("TXT_DERIVE", $this->lng->txt("cont_derive_from_obj"));
		$this->tpl->setVariable("TXT_TYPE", $this->lng->txt("cont_".$std_item->getLocationType()));
		$this->tpl->setVariable("TXT_LOCATION", $std_item->getLocation());
		$this->tpl->setVariable("TXT_FORMAT", $this->lng->txt("cont_format"));
		$this->tpl->setVariable("VAL_FORMAT", $std_item->getFormat());
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$this->displayValidationError();

		// width
		$this->tpl->setVariable("TXT_MOB_WIDTH", $this->lng->txt("cont_width"));
		$this->tpl->setVariable("INPUT_MOB_WIDTH", "mob_width");
		$this->tpl->setVariable("VAL_MOB_WIDTH", $std_alias_item->getWidth());

		// height
		$this->tpl->setVariable("TXT_MOB_HEIGHT", $this->lng->txt("cont_height"));
		$this->tpl->setVariable("INPUT_MOB_HEIGHT", "mob_height");
		$this->tpl->setVariable("VAL_MOB_HEIGHT", $std_alias_item->getHeight());

		// caption
		$this->tpl->setVariable("TXT_CAPTION", $this->lng->txt("cont_caption"));
		$this->tpl->setVariable("INPUT_CAPTION", "mob_caption");
		$this->tpl->setVariable("VAL_CAPTION", $std_alias_item->getCaption());
		$this->tpl->parseCurrentBlock();

		// parameters
		$this->tpl->setVariable("TXT_PARAMETER", $this->lng->txt("cont_parameter"));
		$this->tpl->setVariable("INPUT_PARAMETERS", "mob_parameters");
		$this->tpl->setVariable("VAL_PARAMETERS", $std_alias_item->getParameterString());
		$this->tpl->parseCurrentBlock();

		// object default values
		$this->tpl->setVariable("VAL_OBJ_ST_SIZE", $std_item->getWidth()." / ".$std_item->getHeight());
		$this->tpl->setVariable("VAL_OBJ_ST_CAPTION", $std_item->getCaption());
		$this->tpl->setVariable("VAL_OBJ_ST_PARAMETERS", $std_item->getParameterString());
		if ($std_alias_item->definesSize())
		{
			$this->tpl->setVariable("DERIVE_ST_SIZE_N", "checked=\"1\"");
		}
		else
		{
			$this->tpl->setVariable("DERIVE_ST_SIZE_Y", "checked=\"1\"");
		}
		if ($std_alias_item->definesCaption())
		{
			$this->tpl->setVariable("DERIVE_ST_CAPTION_N", "checked=\"1\"");
		}
		else
		{
			$this->tpl->setVariable("DERIVE_ST_CAPTION_Y", "checked=\"1\"");
		}
		if ($std_alias_item->definesParameters())
		{
			$this->tpl->setVariable("DERIVE_ST_PARAMETER_N", "checked=\"1\"");
		}
		else
		{
			$this->tpl->setVariable("DERIVE_ST_PARAMETER_Y", "checked=\"1\"");
		}

		// fullscreen view
		if($this->content_obj->hasFullScreenItem())
		{
			$this->tpl->setCurrentBlock("fullscreen");
			$full_alias_item =& new ilMediaAliasItem($this->dom, $this->getHierId(), "Fullscreen");
			$full_item =& $this->content_obj->getMediaItem("Fullscreen");

			$this->tpl->setVariable("TXT_FULLSCREEN_VIEW", $this->lng->txt("cont_fullscreen"));
			$this->tpl->setVariable("TXT_FULL_TYPE", $this->lng->txt("cont_".$full_item->getLocationType()));
			$this->tpl->setVariable("TXT_FULL_LOCATION", $full_item->getLocation());

			$this->tpl->setVariable("TXT_FULL_FORMAT", $this->lng->txt("cont_format"));
			$this->tpl->setVariable("VAL_FULL_FORMAT", $full_item->getFormat());

			// width text
			$this->tpl->setVariable("TXT_FULL_WIDTH", $this->lng->txt("cont_width"));
			$this->tpl->setVariable("INPUT_FULL_WIDTH", "full_width");

			// height text
			$this->tpl->setVariable("TXT_FULL_HEIGHT", $this->lng->txt("cont_height"));
			$this->tpl->setVariable("INPUT_FULL_HEIGHT", "full_height");

			// caption text
			$this->tpl->setVariable("TXT_FULL_CAPTION", $this->lng->txt("cont_caption"));
			$this->tpl->setVariable("INPUT_FULL_CAPTION", "full_caption");

			// parameters text
			$this->tpl->setVariable("TXT_FULL_PARAMETER", $this->lng->txt("cont_parameter"));
			$this->tpl->setVariable("INPUT_FULL_PARAMETERS", "full_parameters");

			// object default values
			$this->tpl->setVariable("VAL_OBJ_FULL_SIZE", $full_item->getWidth()." / ".$full_item->getHeight());
			$this->tpl->setVariable("VAL_OBJ_FULL_CAPTION", $full_item->getCaption());
			$this->tpl->setVariable("VAL_OBJ_FULL_PARAMETERS", $full_item->getParameterString());
			if ($full_alias_item->definesSize())
			{
				$this->tpl->setVariable("DERIVE_FULL_SIZE_N", "checked=\"1\"");
			}
			else
			{
				$this->tpl->setVariable("DERIVE_FULL_SIZE_Y", "checked=\"1\"");
			}
			if ($full_alias_item->definesCaption())
			{
				$this->tpl->setVariable("DERIVE_FULL_CAPTION_N", "checked=\"1\"");
			}
			else
			{
				$this->tpl->setVariable("DERIVE_FULL_CAPTION_Y", "checked=\"1\"");
			}
			if ($full_alias_item->definesParameters())
			{
				$this->tpl->setVariable("DERIVE_FULL_PARAMETER_N", "checked=\"1\"");
			}
			else
			{
				$this->tpl->setVariable("DERIVE_FULL_PARAMETER_Y", "checked=\"1\"");
			}

			if ($full_alias_item->exists())
			{
				$this->tpl->setVariable("FULLSCREEN_CHECKED", "checked=\"1\"");

				// width
				$this->tpl->setVariable("VAL_FULL_WIDTH", $full_alias_item->getWidth());

				// height
				$this->tpl->setVariable("VAL_FULL_HEIGHT", $full_alias_item->getHeight());

				// caption
				$this->tpl->setVariable("VAL_FULL_CAPTION", $full_alias_item->getCaption());

				// parameters
				$this->tpl->setVariable("VAL_FULL_PARAMETERS", $full_alias_item->getParameterString());
			}

			$this->tpl->parseCurrentBlock();
		}

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "saveAliasProperties");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->parseCurrentBlock();

	}


	/**
	* save table properties in db and return to page edit screen
	*/
	function saveAliasProperties()
	{
		$std_item =& new ilMediaAliasItem($this->dom, $this->getHierId(), "Standard");
		$full_item =& new ilMediaAliasItem($this->dom, $this->getHierId(), "Fullscreen");

		// standard size
		if($_POST["derive_st_size"] == "y")
		{
			$std_item->deriveSize();
		}
		else
		{
			$std_item->setWidth($_POST["mob_width"]);
			$std_item->setHeight($_POST["mob_height"]);
		}

		// standard caption
		if($_POST["derive_st_caption"] == "y")
		{
			$std_item->deriveCaption();
		}
		else
		{
			$std_item->setCaption($_POST["mob_caption"]);
		}

		// standard parameters
		if($_POST["derive_st_parameter"] == "y")
		{
			$std_item->deriveParameters();
		}
		else
		{
			$std_item->setParameters(ilUtil::extractParameterString(ilUtil::stripSlashes(utf8_decode($_POST["mob_parameters"]))));
		}

		if($this->content_obj->hasFullscreenItem())
		{
			if ($_POST["fullscreen"] ==  "y")
			{
				if (!$full_item->exists())
				{
					$full_item->insert();
				}

				// fullscreen size
				if($_POST["derive_full_size"] == "y")
				{
					$full_item->deriveSize();
				}
				else
				{
					$full_item->setWidth($_POST["full_width"]);
					$full_item->setHeight($_POST["full_height"]);
				}

				// fullscreen caption
				if($_POST["derive_full_caption"] == "y")
				{
					$full_item->deriveCaption();
				}
				else
				{
					$full_item->setCaption($_POST["full_caption"]);
				}

				// fullscreen parameters
				if($_POST["derive_full_parameter"] == "y")
				{
					$full_item->deriveParameters();
				}
				else
				{
					$full_item->setParameters(ilUtil::extractParameterString(ilUtil::stripSlashes(utf8_decode($_POST["full_parameters"]))));
				}
			}
			else
			{
				if ($full_item->exists())
				{
					$full_item->delete();
				}
			}
		}

		$this->updated = $this->pg_obj->update();
		if ($this->updated === true)
		{
			$this->ctrl->returnToParent($this, "jump".$this->hier_id);
		}
		else
		{
			$this->pg_obj->addHierIDs();
			$this->edit();
		}
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		// get next class that processes or forwards current command
		$next_class = $this->ctrl->getNextClass($this);

		// get current command
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				$ret =& $this->$cmd();
				break;
		}

		return $ret;
	}


	/**
	* copy media object to clipboard
	*/
	function copyToClipboard()
	{
		$this->ilias->account->addObjectToClipboard($this->content_obj->getId(), $this->content_obj->getType()
			, $this->content_obj->getTitle());
		sendInfo($this->lng->txt("copied_to_clipboard"), true);
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* align media object to center
	*/
	function centerAlign()
	{
		$std_alias_item =& new ilMediaAliasItem($this->dom, $this->getHierId(), "Standard");
		$std_alias_item->setHorizontalAlign("Center");
		$_SESSION["il_pg_error"] = $this->pg_obj->update();
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* align media object to left
	*/
	function leftAlign()
	{
		$std_alias_item =& new ilMediaAliasItem($this->dom, $this->getHierId(), "Standard");
		$std_alias_item->setHorizontalAlign("Left");
		$_SESSION["il_pg_error"] = $this->pg_obj->update();
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* align media object to right
	*/
	function rightAlign()
	{
		$std_alias_item =& new ilMediaAliasItem($this->dom, $this->getHierId(), "Standard");
		$std_alias_item->setHorizontalAlign("Right");
		$_SESSION["il_pg_error"] = $this->pg_obj->update();
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* align media object to left, floating text
	*/
	function leftFloatAlign()
	{
		$std_alias_item =& new ilMediaAliasItem($this->dom, $this->getHierId(), "Standard");
		$std_alias_item->setHorizontalAlign("LeftFloat");
		$_SESSION["il_pg_error"] = $this->pg_obj->update();
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* align media object to right, floating text
	*/
	function rightFloatAlign()
	{
		$std_alias_item =& new ilMediaAliasItem($this->dom, $this->getHierId(), "Standard");
		$std_alias_item->setHorizontalAlign("RightFloat");
		$_SESSION["il_pg_error"] = $this->pg_obj->update();
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* add tabs to ilTabsGUI object
	*
	* @param	object		$tab_gui		ilTabsGUI object
	* @param	boolean		$a_create		new creation true/false
	*/
	function getTabs(&$tab_gui, $a_create = false)
	{
		global $ilCtrl;

		if ($a_create)
		{
			$tab_gui->addTarget("cont_back",
				$ilCtrl->getParentReturn($this), "",
				"");
		}
		else
		{
			$tab_gui->addTarget("cont_mob_inst_prop",
				$ilCtrl->getLinkTarget($this, "editAlias"), "editAlias",
				get_class($this));
		}
	}

}
?>
