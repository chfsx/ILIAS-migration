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

define ("IL_MODE_ALIAS", 1);
define ("IL_MODE_OUTPUT", 2);
define ("IL_MODE_FULL", 3);

require_once("content/classes/Media/class.ilMediaItem.php");

/**
* Class ilObjMediaObject
*
* Todo: this class must be integrated with group/folder handling
*
* ILIAS Media Object
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilObjMediaObject extends ilObject
{
	var $is_alias;
	var $origin_id;
	var $id;

	var $dom;
	var $hier_id;
	var $node;
	var $mob_node;
	var $media_items;
	var $contains_int_link;

	/**
	* Constructor
	* @access	public
	*/
	function ilObjMediaObject($a_id = 0)
	{
		$this->is_alias = false;
		$this->media_items = array();
		$this->contains_int_link = false;
		$this->type = "mob";
//echo "<br>ilObjMediaObject:Constructor:$a_id:";
		parent::ilObject($a_id, false);

		/*
		if($a_id != 0)
		{
			$this->read();
		}*/


	}

	function setRefId()
	{
		$this->ilias->raiseError("Operation ilObjMedia::setRefId() not allowed.",$this->ilias->error_obj->FATAL);
	}

	function getRefId()
	{
		$this->ilias->raiseError("Operation ilObjMedia::getRefId() not allowed.",$this->ilias->error_obj->FATAL);
	}

	function putInTree()
	{
		$this->ilias->raiseError("Operation ilObjMedia::putInTree() not allowed.",$this->ilias->error_obj->FATAL);
	}

	function createReference()
	{
		$this->ilias->raiseError("Operation ilObjMedia::createReference() not allowed.",$this->ilias->error_obj->FATAL);
	}

	function setTitle($a_title)
	{
		parent::setTitle($a_title);
		$this->meta_data->setTitle($a_title);
	}

	function getTitle()
	{
		return parent::getTitle();
		if (is_object($this->meta_data))
		{
			return $this->meta_data->getTitle();
		}
	}


	/**
	* delete media object
	*/
	function delete()
	{
		$usages = $this->getUsages();

		if (count($usages) == 0)
		{
			// remove directory
			ilUtil::delDir(ilObjMediaObject::_getDirectory($this->getId()));

			// delete meta data of mob
			$nested = new ilNestedSetXML();
			$nested->init($this->getId(), $this->getType());
			$nested->deleteAllDBData();

			// delete media items
			ilMediaItem::deleteAllItemsOfMob($this->getId());

			// delete object
			parent::delete();
		}
	}

	/**
	* get description of media object
	*
	* @return	string		description
	*/
	function getDescription()
	{
		return parent::getDescription();
		return $this->meta_data->getDescription();
	}

	/**
	* set description of media object
	*/
	function setDescription($a_description)
	{
		parent::setDescription($a_description);
		$this->meta_data->setDescription($a_description);
	}


	/**
	* assign meta data object
	*/
	function assignMetaData(&$a_meta_data)
	{
		$this->meta_data =& $a_meta_data;
		$a_meta_data->setObject($this);
	}

	/**
	* get meta data object
	*/
	function &getMetaData()
	{
		return $this->meta_data;
	}


	/**
	* add media item to media object
	*
	* @param	object		$a_item		media item object
	*/
	function addMediaItem(&$a_item)
	{
		$this->media_items[] =& $a_item;
	}


	/**
	* get all media items
	*
	* @return	array		array of media item objects
	*/
	function &getMediaItems()
	{
		return $this->media_items;
	}

	function &getMediaItem($a_purpose)
	{
		for($i=0; $i<count($this->media_items); $i++)
		{
			if($this->media_items[$i]->getPurpose() == $a_purpose)
			{
				return $this->media_items[$i];
			}
		}
		return false;
	}


	/**
	*
	*/
	function removeMediaItem($a_purpose)
	{
		for($i=0; $i<count($this->media_items); $i++)
		{
			if($this->media_items[$i]->getPurpose() == $a_purpose)
			{
				unset($this->media_items[$i]);
			}
		}
	}


	function getMediaItemNr($a_purpose)
	{
		for($i=0; $i<count($this->media_items); $i++)
		{
			if($this->media_items[$i]->getPurpose() == $a_purpose)
			{
				return $i + 1;
			}
		}
		return false;
	}

	function hasFullscreenItem()
	{
		if(is_object($this->getMediaItem("Fullscreen")))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* read media object data from db
	*/
	function read()
	{
//echo "<br>ilObjMediaObject:read";
		parent::read();

		// get media items
		ilMediaItem::_getMediaItemsOfMOb($this);

		// get meta data
		$this->meta_data =& new ilMetaData($this->getType(), $this->getId());
	}

	/**
	* set id
	*/
	function setId($a_id)
	{
		$this->id = $a_id;
	}

	function getId()
	{
		return $this->id;
	}

	/**
	* set wether page object is an alias
	*/
	function setAlias($a_is_alias)
	{
		$this->is_alias = $a_is_alias;
	}

	function isAlias()
	{
		return $this->is_alias;
	}

	function setOriginID($a_id)
	{
		return $this->origin_id = $a_id;
	}

	function getOriginID()
	{
		return $this->origin_id;
	}

	/*
	function getimportId()
	{
		return $this->meta_data->getImportIdentifierEntryID();
	}*/


	/**
	* get import id
	*/
	function getImportId()
	{
		if($this->isAlias())
		{
//echo "getting import id for mob alias:".$this->getOriginId().":<br>";
			return $this->getOriginId();
		}
		else
		{
//echo "getting import id for mob:".$this->meta_data->getImportIdentifierEntryID().":<br>";
			return $this->meta_data->getImportIdentifierEntryID();
		}
	}

	function setImportId($a_id)
	{
		if($this->isAlias())
		{
			$this->meta_data->setOriginID($a_id);
		}
		else
		{
			$this->meta_data->setImportIdentifierEntryID($a_id);
		}
	}

	/**
	* create media object in db
	*/
	function create()
	{
		parent::create();

		// create meta data
		$this->meta_data->setId($this->getId());
		$this->meta_data->setType($this->getType());
		$this->meta_data->create();
		$this->meta_data->getDom();
		$media_items =& $this->getMediaItems();
		for($i=0; $i<count($media_items); $i++)
		{
			$item =& $media_items[$i];
			$item->setMobId($this->getId());
			$item->setNr($i+1);
			$item->create();
		}

	}


	/**
	* update meta data only
	*/
	function updateMetaData()
	{
		$this->meta_data->update();
		$this->setTitle($this->meta_data->getTitle());
		$this->setDescription($this->meta_data->getDescription());

		parent::update();

		return true;
	}


	/**
	* update media object in db
	*/
	function update()
	{
		parent::update();

		$this->updateMetaData();

		ilMediaItem::deleteAllItemsOfMob($this->getId());

		// iterate all items
		$media_items =& $this->getMediaItems();
		$j = 1;
		foreach($media_items as $key => $val)
		{
			$item =& $media_items[$key];
			if (is_object($item))
			{
				$item->setMobId($this->getId());
				$item->setNr($j);
				$item->create();
				$j++;
			}
		}
	}

	/**
	* get directory for files of media object (static)
	*
	* @param	int		$a_mob_id		media object id
	*/
	function _getDirectory($a_mob_id)
	{
		return ilUtil::getWebspaceDir()."/mobs/mm_".$a_mob_id;
	}


	/**
	* create file directory of media object
	*/
	function createDirectory()
	{
		ilUtil::createDirectory(ilObjMediaObject::_getDirectory($this->getId()));
	}

	/**
	* get MediaObject XLM Tag
	*  @param	int		$a_mode		IL_MODE_ALIAS | IL_MODE_OUTPUT | IL_MODE_FULL
	*/
	function getXML($a_mode = IL_MODE_FULL, $a_inst = 0)
	{
		// TODO: full implementation of all parameters

		switch ($a_mode)
		{
			case IL_MODE_ALIAS:
				$xml = "<MediaObject>";
				$xml .= "<MediaAlias OriginId=\"il__mob_".$this->getId()."\"/>";
				$media_items =& $this->getMediaItems();
//echo "MediaItems:".count($media_items).":<br>";
				for($i=0; $i<count($media_items); $i++)
				{
					$item =& $media_items[$i];
					$xml .= "<MediaAliasItem Purpose=\"".$item->getPurpose()."\">";

					// Layout
					$width = ($item->getWidth() != "")
						? "Width=\"".$item->getWidth()."\""
						: "";
					$height = ($item->getHeight() != "")
						? "Height=\"".$item->getHeight()."\""
						: "";
					$halign = ($item->getHAlign() != "")
						? "HorizontalAlign=\"".$item->getHAlign()."\""
						: "";
					$xml .= "<Layout $width $height $halign />";

					// Caption
					if ($item->getCaption() != "")
					{
						$xml .= "<Caption Align=\"bottom\">".
							$item->getCaption()."</Caption>";
					}

					// Parameter
					$parameters = $item->getParameters();
					foreach ($parameters as $name => $value)
					{
						$xml .= "<Parameter Name=\"$name\" Value=\"$value\"/>";
					}
					$xml .= "</MediaAliasItem>";
				}
				break;

			// for output we need technical sections of meta data
			case IL_MODE_OUTPUT:

				// get first technical section
				$meta =& $this->getMetaData();
				$xml = "<MediaObject Id=\"il__mob_".$this->getId()."\">";

				$media_items =& $this->getMediaItems();
				for($i=0; $i<count($media_items); $i++)
				{
					$item =& $media_items[$i];
					$xml .= "<MediaItem Purpose=\"".$item->getPurpose()."\">";

					// Location
					$xml.= "<Location Type=\"".$item->getLocationType()."\">".
						$item->getLocation()."</Location>";

					// Format
					$xml.= "<Format>".$item->getFormat()."</Format>";

					// Layout
					$width = ($item->getWidth() != "")
						? "Width=\"".$item->getWidth()."\""
						: "";
					$height = ($item->getHeight() != "")
						? "Height=\"".$item->getHeight()."\""
						: "";
					$halign = ($item->getHAlign() != "")
						? "HorizontalAlign=\"".$item->getHAlign()."\""
						: "";
					$xml .= "<Layout $width $height $halign />";

					// Caption
					if ($item->getCaption() != "")
					{
						$xml .= "<Caption Align=\"bottom\">".
							$item->getCaption()."</Caption>";
					}

					// Parameter
					$parameters = $item->getParameters();
					foreach ($parameters as $name => $value)
					{
						$xml .= "<Parameter Name=\"$name\" Value=\"$value\"/>";
					}
					$xml .= $item->getMapAreasXML();
					$xml .= "</MediaItem>";
				}
				break;

			// full xml for export
			case IL_MODE_FULL:

				$meta =& $this->getMetaData();
				$xml = "<MediaObject>";

				// meta data
				$nested = new ilNestedSetXML();
				$nested->setParameterModifier($this, "modifyExportIdentifier");
				$xml.= $nested->export($this->getId(), $this->getType());

				$media_items =& $this->getMediaItems();
				for($i=0; $i<count($media_items); $i++)
				{
					$item =& $media_items[$i];
					$xml .= "<MediaItem Purpose=\"".$item->getPurpose()."\">";

					// Location
					$xml.= "<Location Type=\"".$item->getLocationType()."\">".
						$item->getLocation()."</Location>";

					// Format
					$xml.= "<Format>".$item->getFormat()."</Format>";

					// Layout
					$width = ($item->getWidth() != "")
						? "Width=\"".$item->getWidth()."\""
						: "";
					$height = ($item->getHeight() != "")
						? "Height=\"".$item->getHeight()."\""
						: "";
					$halign = ($item->getHAlign() != "")
						? "HorizontalAlign=\"".$item->getHAlign()."\""
						: "";
					$xml .= "<Layout $width $height $halign />";

					// Caption
					if ($item->getCaption() != "")
					{
						$xml .= "<Caption Align=\"bottom\">".
							$item->getCaption()."</Caption>";
					}

					// Parameter
					$parameters = $item->getParameters();
					foreach ($parameters as $name => $value)
					{
						$xml .= "<Parameter Name=\"$name\" Value=\"$value\"/>";
					}
					$xml .= $item->getMapAreasXML(true, $a_inst);
					$xml .= "</MediaItem>";
				}
				break;
		}
		$xml .= "</MediaObject>";
		return $xml;
	}


	/**
	* export XML
	*/
	function exportXML(&$a_xml_writer, $a_inst = 0)
	{
		$a_xml_writer->appendXML($this->getXML(IL_MODE_FULL, $a_inst));
	}


	/**
	* export all media files of object to target directory
	* note: target directory must be the export target directory,
	* "/objects/il_<inst>_mob_<mob_id>/..." will be appended to this directory
	*
	* @param	string		$a_target_dir		target directory
	*/
	function exportFiles($a_target_dir)
	{
		$subdir = "il_".IL_INST_ID."_mob_".$this->getId();
		ilUtil::makeDir($a_target_dir."/objects/".$subdir);

		$mobdir = ilUtil::getWebspaceDir()."/mobs/mm_".$this->getId();
		ilUtil::rCopy($mobdir, $a_target_dir."/objects/".$subdir);
//echo "from:$mobdir:to:".$a_target_dir."/objects/".$subdir.":<br>";
	}


	function modifyExportIdentifier($a_tag, $a_param, $a_value)
	{
		if ($a_tag == "Identifier" && $a_param == "Entry")
		{
			$a_value = ilUtil::insertInstIntoID($a_value);
		}

		return $a_value;
	}


	//////
	// EDIT METHODS: these methods act on the media alias in the dom
	//////

	/**
	* set dom object
	*/
	function setDom(&$a_dom)
	{
		$this->dom =& $a_dom;
	}

	/**
	* set PageContent node
	*/
	function setNode($a_node)
	{
		$this->node =& $a_node;							// page content node
		$this->mob_node =& $a_node->first_child();			// MediaObject node
	}

	/**
	* get PageContent node
	*/
	function &getNode()
	{
		return $this->node;
	}

	/**
	* set hierarchical edit id
	*/
	function setHierId($a_hier_id)
	{
		$this->hier_id = $a_hier_id;
	}

	/**
	* content parser set this flag to true, if the media object contains internal links
	* (this method should only be called by the import parser)
	*
	* @param	boolean		$a_contains_link		true, if page contains intern link tag(s)
	*/
	function setContainsIntLink($a_contains_link)
	{
		$this->contains_int_link = $a_contains_link;
	}

	/**
	* returns true, if mob was marked as containing an intern link (via setContainsIntLink)
	* (this method should only be called by the import parser)
	*/
	function containsIntLink()
	{
		return $this->contains_int_link;
	}


	function createAlias(&$a_pg_obj, $a_hier_id)
	{
		$this->node =& $this->dom->create_element("PageContent");
		$a_pg_obj->insertContent($this, $a_hier_id, IL_INSERT_AFTER);
		$this->mob_node =& $this->dom->create_element("MediaObject");
		$this->mob_node =& $this->node->append_child($this->mob_node);
		$this->mal_node =& $this->dom->create_element("MediaAlias");
		$this->mal_node =& $this->mob_node->append_child($this->mal_node);
		$this->mal_node->set_attribute("OriginId", "il__mob_".$this->getId());

		// standard view
		$item_node =& $this->dom->create_element("MediaAliasItem");
		$item_node =& $this->mob_node->append_child($item_node);
		$item_node->set_attribute("Purpose", "Standard");
		$media_item =& $this->getMediaItem("Standard");

		$layout_node =& $this->dom->create_element("Layout");
		$layout_node =& $item_node->append_child($layout_node);
		if ($media_item->getWidth() > 0)
		{
			$layout_node->set_attribute("Width", $media_item->getWidth());
		}
		if ($media_item->getHeight() > 0)
		{
			$layout_node->set_attribute("Height", $media_item->getHeight());
		}
		$layout_node->set_attribute("HorizontalAlign", "Left");

		// caption
		if ($media_item->getCaption() != "")
		{
			$cap_node =& $this->dom->create_element("Caption");
			$cap_node =& $item_node->append_child($cap_node);
			$cap_node->set_attribute("Align", "bottom");
			$cap_node->set_content($media_item->getCaption());
		}

		$pars = $media_item->getParameters();
		foreach($pars as $par => $val)
		{
			$par_node =& $this->dom->create_element("Parameter");
			$par_node =& $item_node->append_child($par_node);
			$par_node->set_attribute("Name", $par);
			$par_node->set_attribute("Value", $val);
		}

		// fullscreen view
		$fullscreen_item =& $this->getMediaItem("Fullscreen");
		if (is_object($fullscreen_item))
		{
			$item_node =& $this->dom->create_element("MediaAliasItem");
			$item_node =& $this->mob_node->append_child($item_node);
			$item_node->set_attribute("Purpose", "Fullscreen");

			// width and height
			$layout_node =& $this->dom->create_element("Layout");
			$layout_node =& $item_node->append_child($layout_node);
			if ($fullscreen_item->getWidth() > 0)
			{
				$layout_node->set_attribute("Width", $fullscreen_item->getWidth());
			}
			if ($fullscreen_item->getHeight() > 0)
			{
				$layout_node->set_attribute("Height", $fullscreen_item->getHeight());
			}

			// caption
			if ($fullscreen_item->getCaption() != "")
			{
				$cap_node =& $this->dom->create_element("Caption");
				$cap_node =& $item_node->append_child($cap_node);
				$cap_node->set_attribute("Align", "bottom");
				$cap_node->set_content($fullscreen_item->getCaption());
			}

			$pars = $fullscreen_item->getParameters();
			foreach($pars as $par => $val)
			{
				$par_node =& $this->dom->create_element("Parameter");
				$par_node =& $item_node->append_child($par_node);
				$par_node->set_attribute("Name", $par);
				$par_node->set_attribute("Value", $val);
			}
		}
	}

	/**
	* static
	*/
	function _deleteAllUsages($a_type, $a_id)
	{
		$q = "DELETE FROM mob_usage WHERE usage_type='$a_type' AND usage_id='$a_id'";
		$this->ilias->db->query($q);
	}

	function _saveUsage($a_mob_id, $a_type, $a_id)
	{
		$q = "REPLACE INTO mob_usage (id, usage_type, usage_id) VALUES".
			" ('$a_mob_id', '$a_type', '$a_id')";
		$this->ilias->db->query($q);
	}

	/**
	* get all usages of current media object
	*/
	function getUsages()
	{
		global $ilDB;

		// get usages in learning modules
		$q = "SELECT * FROM mob_usage WHERE id = '".$this->getId()."'";
		$us_set = $ilDB->query($q);
		$ret = array();
		while($us_rec = $us_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$ret[] = array("type" => $us_rec["usage_type"],
				"id" => $us_rec["usage_id"]);
		}

		// get usages in media pools
		$q = "SELECT DISTINCT mep_id FROM mep_tree WHERE child = '".$this->getId()."'";
		$us_set = $ilDB->query($q);
		while($us_rec = $us_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$ret[] = array("type" => "mep",
				"id" => $us_rec["mep_id"]);
		}

		// get usages in map areas
		$q = "SELECT DISTINCT mob_id FROM media_item as it, map_area as area ".
			" WHERE area.item_id = it.id ".
			" AND area.link_type='int' ".
			" AND area.target = 'il__mob_".$this->getId()."'";
		$us_set = $ilDB->query($q);
		while($us_rec = $us_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$ret[] = array("type" => "map",
				"id" => $us_rec["mob_id"]);
		}

		// get usages in personal clipboards
		$users = ilObjUser::_getUsersForClipboadObject("mob", $this->getId());
		foreach ($users as $user)
		{
			$ret[] = array("type" => "clip",
				"id" => $user);
		}

		return $ret;
	}

	/**
	* get mime type for file
	*
	* @param	string		$a_file		file name
	* @return	string					mime type
	* static
	*/
	function getMimeType ($a_file)
	{
		// check if mimetype detection enabled in php.ini
		$set = ini_get("mime_magic.magicfile");

		// get mimetype
		if ($set <> "")
		{
			$mime = @mime_content_type($a_file);
		}

		if (empty($mime))
		{
			$path = pathinfo($a_file);
			$ext = ".".strtolower($path["extension"]);

			/**
			* map of mimetypes.py from python.org (there was no author mentioned in the file)
			*/
			$types_map = ilObjMediaObject::getExt2MimeMap();
			$mime = $types_map[$ext];
		}

		// set default if mimetype detection failed or not possible (e.g. remote file)
		if (empty($mime))
		{
			$mime = "application/octet-stream";
		}

		return $mime;
	}


	/**
	* get file extension to mime type map
	*/
	function getExt2MimeMap()
	{
		$types_map = array (
			'.a'      => 'application/octet-stream',
			'.ai'     => 'application/postscript',
			'.aif'    => 'audio/x-aiff',
			'.aifc'   => 'audio/x-aiff',
			'.aiff'   => 'audio/x-aiff',
			'.asd'    => 'application/astound',
			'.asn'    => 'application/astound',
			'.au'     => 'audio/basic',
			'.avi'    => 'video/x-msvideo',
			'.bat'    => 'text/plain',
			'.bcpio'  => 'application/x-bcpio',
			'.bin'    => 'application/octet-stream',
			'.bmp'    => 'image/x-ms-bmp',
			'.c'      => 'text/plain',
			'.cdf'    => 'application/x-cdf',
			'.class'  => 'application/x-java-applet',
			'.com'    => 'application/octet-stream',
			'.cpio'   => 'application/x-cpio',
			'.csh'    => 'application/x-csh',
			'.css'    => 'text/css',
			'.csv'    => 'text/comma-separated-values',
			'.dcr'    => 'application/x-director',
			'.dir'    => 'application/x-director',
			'.dll'    => 'application/octet-stream',
			'.doc'    => 'application/msword',
			'.dot'    => 'application/msword',
			'.dvi'    => 'application/x-dvi',
			'.dwg'    => 'application/acad',
			'.dxf'    => 'application/dxf',
			'.dxr'    => 'application/x-director',
			'.eml'    => 'message/rfc822',
			'.eps'    => 'application/postscript',
			'.etx'    => 'text/x-setext',
			'.exe'    => 'application/octet-stream',
			'.gif'    => 'image/gif',
			'.gtar'   => 'application/x-gtar',
			'.gz'     => 'application/gzip',
			'.h'      => 'text/plain',
			'.hdf'    => 'application/x-hdf',
			'.htm'    => 'text/html',
			'.html'   => 'text/html',
			'.ief'    => 'image/ief',
			'.iff'    => 'image/iff',
			'.jar'    => 'application/x-java-applet',
			'.jpe'    => 'image/jpeg',
			'.jpeg'   => 'image/jpeg',
			'.jpg'    => 'image/jpeg',
			'.js'     => 'application/x-javascript',
			'.ksh'    => 'text/plain',
			'.latex'  => 'application/x-latex',
			'.m1v'    => 'video/mpeg',
			'.man'    => 'application/x-troff-man',
			'.me'     => 'application/x-troff-me',
			'.mht'    => 'message/rfc822',
			'.mhtml'  => 'message/rfc822',
			'.mid'    => 'audio/x-midi',
			'.midi'   => 'audio/x-midi',
			'.mif'    => 'application/x-mif',
			'.mov'    => 'video/quicktime',
			'.movie'  => 'video/x-sgi-movie',
			'.mp2'    => 'audio/mpeg',
			'.mp3'    => 'audio/mpeg',
			'.mpa'    => 'video/mpeg',
			'.mpe'    => 'video/mpeg',
			'.mpeg'   => 'video/mpeg',
			'.mpg'    => 'video/mpeg',
			'.ms'     => 'application/x-troff-ms',
			'.nc'     => 'application/x-netcdf',
			'.nws'    => 'message/rfc822',
			'.o'      => 'application/octet-stream',
			'.obj'    => 'application/octet-stream',
			'.oda'    => 'application/oda',
			'.p12'    => 'application/x-pkcs12',
			'.p7c'    => 'application/pkcs7-mime',
			'.pbm'    => 'image/x-portable-bitmap',
			'.pdf'    => 'application/pdf',
			'.pfx'    => 'application/x-pkcs12',
			'.pgm'    => 'image/x-portable-graymap',
			'.php'    => 'application/x-httpd-php',
			'.phtml'  => 'application/x-httpd-php',
			'.pl'     => 'text/plain',
			'.png'    => 'image/png',
			'.pnm'    => 'image/x-portable-anymap',
			'.pot'    => 'application/vnd.ms-powerpoint',
			'.ppa'    => 'application/vnd.ms-powerpoint',
			'.ppm'    => 'image/x-portable-pixmap',
			'.pps'    => 'application/vnd.ms-powerpoint',
			'.ppt'    => 'application/vnd.ms-powerpoint',
			'.ps'     => 'application/postscript',
			'.psd'    => 'image/psd',
			'.pwz'    => 'application/vnd.ms-powerpoint',
			'.py'     => 'text/x-python',
			'.pyc'    => 'application/x-python-code',
			'.pyo'    => 'application/x-python-code',
			'.qt'     => 'video/quicktime',
			'.ra'     => 'audio/x-pn-realaudio',
			'.ram'    => 'application/x-pn-realaudio',
			'.ras'    => 'image/x-cmu-raster',
			'.rdf'    => 'application/xml',
			'.rgb'    => 'image/x-rgb',
			'.roff'   => 'application/x-troff',
			'.rpm'    => 'audio/x-pn-realaudio-plugin',
			'.rtf'    => 'application/rtf',
			'.rtx'    => 'text/richtext',
			'.sgm'    => 'text/x-sgml',
			'.sgml'   => 'text/x-sgml',
			'.sh'     => 'application/x-sh',
			'.shar'   => 'application/x-shar',
			'.sit'    => 'application/x-stuffit',
			'.snd'    => 'audio/basic',
			'.so'     => 'application/octet-stream',
			'.spc'    => 'text/x-speech',
			'.src'    => 'application/x-wais-source',
			'.sv4cpio'=> 'application/x-sv4cpio',
			'.sv4crc' => 'application/x-sv4crc',
			'.svg'    => 'image/svg+xml',
			'.swf'    => 'application/x-shockwave-flash',
			'.t'      => 'application/x-troff',
			'.tar'    => 'application/x-tar',
			'.talk'   => 'text/x-speech',
			'.tbk'    => 'application/toolbook',
			'.tcl'    => 'application/x-tcl',
			'.tex'    => 'application/x-tex',
			'.texi'   => 'application/x-texinfo',
			'.texinfo'=> 'application/x-texinfo',
			'.tif'    => 'image/tiff',
			'.tiff'   => 'image/tiff',
			'.tr'     => 'application/x-troff',
			'.tsv'    => 'text/tab-separated-values',
			'.tsp'    => 'application/dsptype',
			'.txt'    => 'text/plain',
			'.ustar'  => 'application',
			'.vcf'    => 'text/x-vcard',
			'.vox'    => 'audio/voxware',
			'.wav'    => 'audio/x-wav',
			'.wiz'    => 'application/msword',
			'.wml'    => 'text/vnd.wap.wml',
			'.wmlc'   => 'application/vnd.wap.wmlc',
			'.wmls'   => 'text/vnd.wap.wmlscript',
			'.wmlsc'  => 'application/vnd.wap.wmlscriptc',
			'.wrl'    => 'x-world/x-vrml',
			'.xbm'    => 'image/x-xbitmap',
			'.xla'    => 'application/msexcel',
			'.xlb'    => 'application/vnd.ms-excel',
			'.xls'    => 'application/msexcel',
			'.xml'    => 'text/xml',
			'.xpm'    => 'image/x-xpixmap',
			'.xsl'    => 'application/xml',
			'.xwd'    => 'image/x-xwindowdump',
			'.zip'    => 'application/zip');

		return $types_map;
	}

	function getDataDirectory()
	{
		return ilUtil::getWebspaceDir()."/mobs/mm_".$this->object->getId();
	}
}
?>
