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

require_once("content/classes/Media/class.ilMapArea.php");

/**
* Class ilMediaItem
*
* Media Item, component of a media object (file or reference)
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilMediaItem
{
	var $ilias;
	var $id;
	var $purpose;
	var $location;
	var $location_type;
	var $format;
	var $width;
	var $height;
	var $caption;
	var $halign;
	var $parameters;
	var $mob_id;
	var $nr;
	var $mapareas;
	var $map_cnt;
	var $map_image;			// image map work copy image
	var $color1;			// map area line color 1
	var $color2;			// map area line color 2

	function ilMediaItem($a_id = 0)
	{
		global $ilias;

		$this->ilias =& $ilias;
		$this->parameters = array();
		$this->mapareas = array();
		$this->map_cnt = 0;

		if ($a_id != 0)
		{
			$this->setId($a_id);
			$this->read();
		}
	}

	/**
	* set media item id
	*
	* @param	int		$a_id		media item id
	*/
	function setId($a_id)
	{
		$this->id = $a_id;
	}

	/**
	* get media item id
	*
	* @return	int		media item id
	*/
	function getId()
	{
		return $this->id;
	}

	/**
	* set id of parent media object
	*
	* @param	int		$a_mob_id		media object id
	*/
	function setMobId($a_mob_id)
	{
		$this->mob_id = $a_mob_id;
	}

	/**
	* get id of parent media object
	*
	* @return	int		media object id
	*/
	function getMobId()
	{
		return $this->mob_id;
	}

	/**
	* set number of media item within media object
	*/
	function setNr($a_nr)
	{
		$this->nr = $a_nr;
	}

	function getNr()
	{
		return $this->nr;
	}

	/**
	* create persistent media item
	*/
	function create()
	{
		global $ilDB;

		$query = "INSERT INTO media_item (mob_id, purpose, location, ".
			"location_type, format, width, ".
			"height, halign, caption, nr) VALUES ".
			"(".$ilDB->quote($this->getMobId()).",".
			$ilDB->quote($this->getPurpose()).",".
			$ilDB->quote($this->getLocation()).",".
			$ilDB->quote($this->getLocationType()).",".
			$ilDB->quote($this->getFormat()).",".
			$ilDB->quote($this->getWidth()).",".
			$ilDB->quote($this->getHeight()).",".
			$ilDB->quote($this->getHAlign()).",".
			$ilDB->quote($this->getCaption()).",".
			$ilDB->quote($this->getNr()).")";
		$this->ilias->db->query($query);
//echo "create_mob:$query:<br>";
		$item_id = $this->ilias->db->getLastInsertId();
		$this->setId($item_id);

		// create mob parameters
		$params = $this->getParameters();
		foreach($params as $name => $value)
		{
			$query = "INSERT INTO mob_parameter (med_item_id, name, value) VALUES ".
				"(".$ilDB->quote($item_id).",".
				$ilDB->quote($name).",".
				$ilDB->quote($value).")";
			$this->ilias->db->query($query);
		}

		// create map areas
		for ($i=0; $i < count($this->mapareas); $i++)
		{
			$this->mapareas[$i]->setItemId($this->getId());
			$this->mapareas[$i]->setNr($i + 1);
			$this->mapareas[$i]->create();
		}
	}

	/**
	* read media item data (item id or (mob_id and nr) must be set)
	*/
	function read()
	{
		$item_id = $this->getId();
		$mob_id = $this->getMobId();
		$nr = $this->getNr();
		$query = "";
		if($item_id > 0)
		{
			$query = "SELECT * FROM media_item WHERE id = '".$this->getId()."'";
		}
		else if ($mob_id > 0 && $nr > 0)
		{
			$query = "SELECT * FROM media_item WHERE mob_id = '".$this->getMobId()."' ".
				"AND nr='".$this->getNr()."'";
		}
		if ($query != "")
		{
			$item_set = $this->ilias->db->query($query);
			$item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC);

			$this->setLocation($item_rec["location"]);
			$this->setLocationType($item_rec["location_type"]);
			$this->setFormat($item_rec["format"]);
			$this->setWidth($item_rec["width"]);
			$this->setHeight($item_rec["height"]);
			$this->setHAlign($item_rec["halign"]);
			$this->setCaption($item_rec["caption"]);
			$this->setPurpose($item_rec["purpose"]);
			$this->setNr($item_rec["nr"]);
			$this->setMobId($item_rec["mob_id"]);
			$this->setId($item_rec["id"]);

			// get item parameter
			$query = "SELECT * FROM mob_parameter WHERE med_item_id = '".
				$this->getId()."'";
			$par_set = $this->ilias->db->query($query);
			while ($par_rec = $par_set->fetchRow(DB_FETCHMODE_ASSOC))
			{
				$this->setParameter($par_rec["name"], $par_rec["value"]);
			}

			// get item map areas
			$max = ilMapArea::_getMaxNr($this->getId());
			for ($i = 1; $i <= $max; $i++)
			{
				$area =& new ilMapArea($this->getId(), $i);
				$this->addMapArea($area);
			}
		}

	}

	/**
	* read media items into media objects (static)
	*
	* @param	object		$a_mob	 	media object
	*/
	function _getMediaItemsOfMOb(&$a_mob)
	{
		// read media_object record
		$query = "SELECT * FROM media_item WHERE mob_id = '".$a_mob->getId()."' ".
			"ORDER BY nr";
		$item_set = $this->ilias->db->query($query);
		while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$media_item =& new ilMediaItem();
			$media_item->setNr($item_rec["nr"]);
			$media_item->setId($item_rec["id"]);
			$media_item->setLocation($item_rec["location"]);
			$media_item->setLocationType($item_rec["location_type"]);
			$media_item->setFormat($item_rec["format"]);
			$media_item->setWidth($item_rec["width"]);
			$media_item->setHeight($item_rec["height"]);
			$media_item->setHAlign($item_rec["halign"]);
			$media_item->setCaption($item_rec["caption"]);
			$media_item->setPurpose($item_rec["purpose"]);
			$media_item->setMobId($item_rec["mob_id"]);

			// get item parameter
			$query = "SELECT * FROM mob_parameter WHERE med_item_id = '".
				$item_rec["id"]."'";
			$par_set = $this->ilias->db->query($query);
			while ($par_rec = $par_set->fetchRow(DB_FETCHMODE_ASSOC))
			{
				$media_item->setParameter($par_rec["name"], $par_rec["value"]);
			}

			// get item map areas
			$max = ilMapArea::_getMaxNr($media_item->getId());
			for ($i = 1; $i <= $max; $i++)
			{
				$area =& new ilMapArea($media_item->getId(), $i);
				$media_item->addMapArea($area);
			}

			// add media item to media object
			$a_mob->addMediaItem($media_item);
		}
	}

	/**
	* static
	*/
	function deleteAllItemsOfMob($a_mob_id)
	{
		// iterate all media items ob mob
		$query = "SELECT * FROM media_item WHERE mob_id = '".$a_mob_id."'";
		$item_set = $this->ilias->db->query($query);
		while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			// delete all parameters of media item
			$query = "DELETE FROM mob_parameter WHERE med_item_id = '".$item_rec["id"]."'";
			$this->ilias->db->query($query);

			// delete all map areas of media item
			$query = "DELETE FROM map_area WHERE item_id = '".$item_rec["id"]."'";
			$this->ilias->db->query($query);
		}

		// delete media items
		$query = "DELETE FROM media_item WHERE mob_id = '".$a_mob_id."'";
		$this->ilias->db->query($query);
	}

	function setPurpose($a_purpose)
	{
		$this->purpose = $a_purpose;
	}

	function getPurpose()
	{
		return $this->purpose;
	}

	function setLocation($a_location)
	{
		$this->location = $a_location;
	}

	function getLocation()
	{
		return $this->location;
	}

	function setLocationType($a_type)
	{
		$this->location_type = $a_type;
	}

	function getLocationType()
	{
		return $this->location_type;
	}

	function setFormat($a_format)
	{
		$this->format = $a_format;
	}

	function getFormat()
	{
		return $this->format;
	}

	function addMapArea(&$a_map_area)
	{
		$this->mapareas[$this->map_cnt] =& $a_map_area;
		$this->map_cnt++;
	}

	/**
	* delete map area
	*/
	function deleteMapArea($nr)
	{
		for ($i=1; $i<=$this->map_cnt; $i++)
		{
			if($i > $nr)
			{
				$this->mapareas[$i-2] =& $this->mapareas[$i-1];
				$this->mapareas[$i-2]->setNr($i-1);
			}
		}
		if($nr <= $this->map_cnt)
		{
			unset($this->mapareas[$this->map_cnt - 1]);
			$this->map_cnt--;
		}
	}

	/**
	* get map area
	*/
	function &getMapArea($nr)
	{
		return $this->mapareas[$nr-1];
	}

	/**
	* get width
	*/
	function getWidth()
	{
		return $this->width;
	}

	/**
	* set width
	*/
	function setWidth($a_width)
	{
		$this->width = $a_width;
	}

	/**
	* get height
	*/
	function getHeight()
	{
		return $this->height;
	}

	/**
	* set height
	*/
	function setHeight($a_height)
	{
		$this->height = $a_height;
	}

	/**
	* set caption
	*/
	function setCaption($a_caption)
	{
		$this->caption = $a_caption;
	}

	/**
	* get caption
	*/
	function getCaption()
	{
		return $this->caption;
	}

	/**
	* set horizontal align
	*/
	function setHAlign($a_halign)
	{
		$this->halign = $a_halign;
	}

	/**
	* get horizontal align
	*/
	function getHAlign()
	{
		return $this->halign;
	}


	/**
	* set parameter
	*
	* @param	string	$a_name		parameter name
	* @param	string	$a_value	parameter value
	*/
	function setParameter($a_name, $a_value)
	{
		$this->parameters[$a_name] = $a_value;
	}

	/**
	* reset parameters
	*/
	function resetParameters()
	{
		$this->parameters = array();
	}

	/**
	* set alle parameters via parameter string (format: par1="value1", par2="value2", ...)
	*
	* @param	string		$a_par		parameter string
	*/
	function setParameters($a_par)
	{
		$this->resetParameters();
		$par_arr = ilUtil::extractParameterString($a_par);
		if(is_array($par_arr))
		{
			foreach($par_arr as $par => $val)
			{
				$this->setParameter($par, $val);
			}
		}
	}


	/**
	* get all parameters (in array)
	*/
	function getParameters()
	{
		return $this->parameters;
	}


	/**
	* get all parameters (as string)
	*/
	function getParameterString()
	{
		return ilUtil::assembleParameterString($this->parameters);
	}


	/**
	* get a single parameter
	*/
	function getParameter($a_name)
	{
		return $this->parameters[$a_name];
	}

	/**
	* get work directory for image map editing
	*/
	function getWorkDirectory()
	{
		return ilUtil::getDataDir()."/map_workfiles/item_".$this->getId();
	}

	/**
	* create work directory for image map editing
	*/
	function createWorkDirectory()
	{
		if(!@is_dir(ilUtil::getDataDir()."/map_workfiles"))
		{
			ilUtil::createDirectory(ilUtil::getDataDir()."/map_workfiles");
		}
		$work_dir = $this->getWorkDirectory();
		if(!@is_dir($work_dir))
		{
			ilUtil::createDirectory($work_dir);
		}
	}

	/**
	* get location suffix
	*/
	function getSuffix()
	{
		$loc_arr = explode(".", $this->getLocation());

		return $loc_arr[count($loc_arr) - 1];
	}

	/**
	* get image type of image map work copy
	*/
	function getMapWorkCopyType()
	{
		return ilUtil::getGDSupportedImageType($this->getSuffix());
	}

	/**
	*
	*/
	function getMapWorkCopyName()
	{
		$file_arr = explode("/", $this->getLocation());
		$file = $file_arr[count($file_arr) - 1];
		$file_arr = explode(".", $file);
		unset($file_arr[count($file_arr) - 1]);
		$file = implode($file_arr, ".");

		return $this->getWorkDirectory()."/".$file.".".$this->getMapWorkCopyType();
	}

	/**
	* get media file directory
	*/
	function getDirectory()
	{
		return ilObjMediaObject::_getDirectory($this->getMobId());
	}


	/**
	* make map work copy of image
	*
	* @param	int			$a_area_nr		draw area $a_area_nr only
	* @param	boolean		$a_exclude		true: draw all areas but area $a_area_nr
	*/
	function makeMapWorkCopy($a_area_nr = 0, $a_exclude = false)
	{
		$this->createWorkDirectory();
		ilUtil::convertImage($this->getDirectory()."/".$this->getLocation(),
			$this->getMapWorkCopyName(),
			$this->getMapWorkCopyType());

		$this->buildMapWorkImage();

		// draw map areas
		for ($i=0; $i < count($this->mapareas); $i++)
		{
			if (	((($i+1) == $a_area_nr) && !$a_exclude) ||
					((($i+1) != $a_area_nr) && $a_exclude) ||
					($a_area_nr == 0)
				)
			{
				$area =& $this->mapareas[$i];
				$area->draw($this->getMapWorkImage(), $this->color1, $this->color2);
			}
		}

		$this->saveMapWorkImage();
	}


	/**
	* draw a new area in work image
	*
	* @param	string		$a_shape		shape
	* @param	string		$a_coords		coordinates string
	*/
	function addAreaToMapWorkCopy($a_shape, $a_coords)
	{
		$this->buildMapWorkImage();

		// add new area to work image
		$area = new ilMapArea();
		$area->setShape($a_shape);
//echo "addAreaToMap:".$a_shape.":<br>";
		$area->setCoords($a_coords);
		$area->draw($this->getMapWorkImage(), $this->color1, $this->color2, false);

		$this->saveMapWorkImage();
	}

	/**
	* output raw map work copy file
	*/
	function outputMapWorkCopy()
	{
		if ($this->getMapWorkCopyType() != "")
		{
			header("Pragma: no-cache");
			header("Expires: 0");
			header("Content-type: image/".strtolower($this->getMapWorkCopyType()));
			readfile($this->getMapWorkCopyName());
		}
		exit;
	}

	/**
	* build image map work image
	*/
	function buildMapWorkImage()
	{
		$im_type = strtolower($this->getMapWorkCopyType());

		switch ($im_type)
		{
			case "gif":
				$this->map_image = ImageCreateFromGIF($this->getMapWorkCopyName());
				break;

			case "jpg":
				$this->map_image = ImageCreateFromJPEG($this->getMapWorkCopyName());
				break;

			case "png":
				$this->map_image = ImageCreateFromPNG($this->getMapWorkCopyName());
				break;
		}

		// try to allocate black and white as color. if this is not possible, get the closest colors
		if (imagecolorstotal($this->map_image) > 250)
		{
			$this->color1 = imagecolorclosest($this->map_image, 0, 0, 0);
			$this->color2 = imagecolorclosest($this->map_image, 255, 255, 255);
		}
		else
		{
			$this->color1 = imagecolorallocate($this->map_image, 0, 0, 0);
			$this->color2 = imagecolorallocate($this->map_image, 255, 255, 255);
		}
	}

	/**
	* save image map work image
	*/
	function saveMapWorkImage()
	{
		$im_type = strtolower($this->getMapWorkCopyType());

		// save image work-copy and free memory
		switch ($im_type)
		{
			case "gif":
				ImageGIF($this->map_image, $this->getMapWorkCopyName());
				break;

			case "jpg":
				ImageJPEG($this->map_image, $this->getMapWorkCopyName());
				break;

			case "png":
				ImagePNG($this->map_image, $this->getMapWorkCopyName());
				break;
		}

		ImageDestroy($this->map_image);
	}

	/**
	* get image map work image
	*/
	function &getMapWorkImage()
	{
		return $this->map_image;
	}


	/**
	* get xml code of media items' areas
	*/
	function getMapAreasXML($a_insert_inst = false, $a_inst = 0)
	{
		$xml = "";

		// build xml of map areas
		for ($i=0; $i < count($this->mapareas); $i++)
		{
			$area =& $this->mapareas[$i];
			$xml .= "<MapArea Shape=\"".$area->getShape()."\" Coords=\"".$area->getCoords()."\">";
			if ($area->getLinkType() == IL_INT_LINK)
			{
				$target_frame = $area->getTargetFrame();

				if ($area->getType() == "GlossaryItem" && $target_frame == "")
				{
					$target_frame = "Glossary";
				}

				$xml .= "<IntLink Target=\"".$area->getTarget($a_insert_inst, $a_inst)."\" Type=\"".
					$area->getType()."\" TargetFrame=\"".$target_frame."\">";
				$xml .= $area->getTitle();
				$xml .="</IntLink>";
			}
			else
			{
				$xml .= "<ExtLink Href=\"".str_replace("&", "&amp;",$area->getHref())."\" Title=\"".
					$area->getExtTitle()."\">";
				$xml .= $area->getTitle();
				$xml .="</ExtLink>";
			}
			$xml .= "</MapArea>";
		}

		return $xml;
	}


	/**
	* resolve internal links of all media items of a media object
	*
	* @param	int		$a_mob_id		media object id
	*/
	function _resolveMapAreaLinks($a_mob_id)
	{
//echo "mediaItems::resolve<br>";
		// read media_object record
		$query = "SELECT * FROM media_item WHERE mob_id = '".$a_mob_id."' ".
			"ORDER BY nr";
		$item_set = $this->ilias->db->query($query);
		while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			ilMapArea::_resolveIntLinks($item_rec["id"]);
		}
	}

	/**
	* get all internal links of map areas of a mob
	*
	* @param	int		$a_mob_id		media object id
	*/
	function _getMapAreasIntLinks($a_mob_id)
	{
		// read media_items records
		$query = "SELECT * FROM media_item WHERE mob_id = '".$a_mob_id."' ".
			"ORDER BY nr";
		$item_set = $this->ilias->db->query($query);
		$links = array();
		while ($item_rec = $item_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$map_links = ilMapArea::_getIntLinks($item_rec["id"]);
			foreach($map_links as $key => $map_link)
			{
				$links[$key] = $map_link;
			}
		}
		return $links;
	}

}
?>
