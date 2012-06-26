<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/DataSet/classes/class.ilDataSet.php");

/**
 * Media Pool Data set class
 * 
 * This class implements the following entities:
 * - mob: object data
 * - mob_media_item: data from table media_item
 * - mob_mi_map_area: data from a table map_area
 * - mob_mi_parameter: data from a table mob_parameter
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 * @ingroup ingroup ServicesMediaObjects
 */
class ilMediaObjectDataSet extends ilDataSet
{	
	/**
	 * Get supported versions
	 *
	 * @param
	 * @return
	 */
	public function getSupportedVersions()
	{
		return array("4.3.0", "4.1.0");
	}
	
	/**
	 * Get xml namespace
	 *
	 * @param
	 * @return
	 */
	function getXmlNamespace($a_entity, $a_schema_version)
	{
		return "http://www.ilias.de/xml/Services/MediaObject/".$a_entity;
	}
	
	/**
	 * Get field types for entity
	 *
	 * @param
	 * @return
	 */
	protected function getTypes($a_entity, $a_version)
	{
		// mob
		if ($a_entity == "mob")
		{
			switch ($a_version)
			{
				case "4.1.0":
				case "4.3.0":
					return array(
						"Id" => "integer",
						"Title" => "text",
						"Description" => "text",
						"Dir" => "directory"
						);
			}
		}
		
		// media item
		if ($a_entity == "mob_media_item")
		{
			switch ($a_version)
			{
				case "4.1.0":
					return array(
						"Id" => "integer",
						"MobId" => "integer",
						"Width" => "integer",
						"Height" => "integer",
						"Halign" => "text",
						"Caption" => "text",
						"Nr" => "integer",
						"Purpose" => "text",
						"Location" => "text",
						"LocationType" => "text",
						"Format" => "text",
						"TextRepresentation" => "text"
					);

				case "4.3.0":
					return array(
						"Id" => "integer",
						"MobId" => "integer",
						"Width" => "integer",
						"Height" => "integer",
						"Halign" => "text",
						"Caption" => "text",
						"Nr" => "integer",
						"Purpose" => "text",
						"Location" => "text",
						"LocationType" => "text",
						"Format" => "text",
						"TextRepresentation" => "text",
						"HighlightMode" => "text",
						"HighlightText" => "text"
					);
			}
		}

		// map areas
		if ($a_entity == "mob_mi_map_area")
		{
			switch ($a_version)
			{
				case "4.1.0":
				case "4.3.0":
						return array(
							"MiId" => "integer",
							"Nr" => "integer",
							"Shape" => "text",
							"Coords" => "text",
							"LinkType" => "text",
							"Title" => "text",
							"Href" => "text",
							"Target" => "text",
							"Type" => "text",
							"TargetFrame" => "text"
						);
			}
		}				

		// media item parameter
		if ($a_entity == "mob_mi_parameter")
		{
			switch ($a_version)
			{
				case "4.1.0":
				case "4.3.0":
						return array(
							"MiId" => "integer",
							"Name" => "text",
							"Value" => "text"
						);
			}
		}
	}

	/**
	 * Read data
	 *
	 * @param
	 * @return
	 */
	function readData($a_entity, $a_version, $a_ids, $a_field = "")
	{
		global $ilDB;

		if (!is_array($a_ids))
		{
			$a_ids = array($a_ids);
		}

		// mob
		if ($a_entity == "mob")
		{
			$this->data = array();
			
			foreach ($a_ids as $mob_id)
			{
				if (ilObject::_lookupType($mob_id) == "mob")
				{
					$this->data[] = array ("Id" => $mob_id,
						"Title" => ilObject::_lookupTitle($mob_id),
						"Description" => ilObject::_lookupDescription($mob_id));
				}
			}
		}

		// media item
		if ($a_entity == "mob_media_item")
		{
			switch ($a_version)
			{
				case "4.1.0":
					$this->getDirectDataFromQuery("SELECT id, mob_id, width, height, halign,".
						"caption, nr, purpose, location, location_type, format, text_representation".
						" FROM media_item WHERE ".
						$ilDB->in("mob_id", $a_ids, false, "integer"));
					break;

				case "4.3.0":
					$this->getDirectDataFromQuery("SELECT id, mob_id, width, height, halign,".
						"caption, nr, purpose, location, location_type, format, text_representation,".
						" highlight_mode, highlight_class".
						" FROM media_item WHERE ".
						$ilDB->in("mob_id", $a_ids, false, "integer"));
					break;
			}
		}	

		
		// media item map area
		if ($a_entity == "mob_mi_map_area")
		{
			switch ($a_version)
			{
				case "4.1.0":
				case "4.3.0":
					$this->getDirectDataFromQuery("SELECT item_id mi_id, nr".
						" ,shape, coords, link_type, title, href, target, type, target_frame ".
						" FROM map_area ".
						" WHERE ".
						$ilDB->in("item_id", $a_ids, false, "integer").
						" ORDER BY nr");
					break;
			}
		}			

		// media item parameter
		if ($a_entity == "mob_mi_parameter")
		{
			switch ($a_version)
			{
				case "4.1.0":
				case "4.3.0":
					$this->getDirectDataFromQuery("SELECT med_item_id mi_id, name, value".
						" FROM mob_parameter ".
						" WHERE ".
						$ilDB->in("med_item_id", $a_ids, false, "integer"));
					break;
			}
		}			
		
	}
	
	/**
	 * Determine the dependent sets of data 
	 */
	protected function getDependencies($a_entity, $a_version, $a_rec, $a_ids)
	{
		switch ($a_entity)
		{
			case "mob":
				return array (
					"mob_media_item" => array("ids" => $a_rec["Id"])
				);
				
			case "mob_media_item":
				return array (
					"mob_mi_map_area" => array("ids" => $a_rec["Id"]),
					"mob_mi_parameter" => array("ids" => $a_rec["Id"])
				);
		}
		return false;
	}

	/**
	 * Get xml record
	 *
	 * @param
	 * @return
	 */
	function getXmlRecord($a_entity, $a_version, $a_set)
	{
		if ($a_entity == "mob")
		{
			include_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
			$dir = ilObjMediaObject::_getDirectory($a_set["Id"]);
			$a_set["Dir"] = $dir;
		}

		return $a_set;
	}
	
	/**
	 * Import record
	 *
	 * @param
	 * @return
	 */
	function importRecord($a_entity, $a_types, $a_rec, $a_mapping, $a_schema_version)
	{
//echo $a_entity;
//var_dump($a_rec);

		switch ($a_entity)
		{
			case "mob":

//var_dump($a_rec);

				include_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
				$newObj = new ilObjMediaObject();
				$newObj->setType("mob");
				$newObj->setTitle($a_rec["Title"]);
				$newObj->setDescription($a_rec["Description"]);
				$newObj->create();
				$newObj->createDirectory();
				ilObjMediaObject::_createThumbnailDirectory($newObj->getId());
				$this->current_mob = $newObj;

				$dir = str_replace("..", "", $a_rec["Dir"]);
				if ($dir != "" && $this->getImportDirectory() != "")
				{
					$source_dir = $this->getImportDirectory()."/".$dir;
					$target_dir = $dir = ilObjMediaObject::_getDirectory($newObj->getId());
					ilUtil::rCopy($source_dir, $target_dir);
				}

				$a_mapping->addMapping("Services/MediaObjects", "mob", $a_rec["Id"], $newObj->getId());
//echo "<br>++add++"."0:".$a_rec["Id"].":mob+0:".$newObj->getId().":mob"."+";
				$a_mapping->addMapping("Services/MetaData", "md",
					"0:".$a_rec["Id"].":mob", "0:".$newObj->getId().":mob");
				break;

			case "mob_media_item":

				// determine parent mob
				include_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
				$mob_id = (int) $a_mapping->getMapping("Services/MediaObjects", "mob", $a_rec["MobId"]);
				if (is_object($this->current_mob) && $this->current_mob->getId() == $mob_id)
				{
					$mob = $this->current_mob;
				}
				else
				{
					$mob = new ilObjMediaObject($mob_id);
				}

				include_once("./Services/MediaObjects/classes/class.ilMediaItem.php");
				$newObj = new ilMediaItem();
				$newObj->setMobId($mob_id);
				$newObj->setWidth($a_rec["Width"]);
				$newObj->setHeight($a_rec["Height"]);
				$newObj->setCaption($a_rec["Caption"]);
				$newObj->setNr($a_rec["Nr"]);
				$newObj->setPurpose($a_rec["Purpose"]);
				$newObj->setLocation($a_rec["Location"]);
				$newObj->setLocationType($a_rec["LocationType"]);
				$newObj->setFormat($a_rec["Format"]);
				$newObj->setHighlightMode($a_rec["HighlightMode"]);
				$newObj->setHighlightClass($a_rec["HighlightClass"]);
				$newObj->setTextRepresentation($a_rec["TextRepresentation"]);
				$newObj->create();
				$this->current_media_item = $newObj;

				$a_mapping->addMapping("Services/MediaObjects", "mob_media_item", $a_rec["Id"], $newObj->getId());

				break;

			case "mob_mi_parameter":

				// get media item
				include_once("./Services/MediaObjects/classes/class.ilMediaItem.php");
				$med_id = (int) $a_mapping->getMapping("Services/MediaObjects", "mob_media_item", $a_rec["MiId"]);
				if (is_object($this->current_media_item) && $this->current_media_item->getId() == $med_id)
				{
					$med = $this->current_media_item;
				}
				else
				{
					$med = new ilMediaItem($med_id);
				}
				$med->writeParameter($a_rec["Name"], $a_rec["Value"]);

				break;

			case "mob_mi_map_area":
				// get media item
				include_once("./Services/MediaObjects/classes/class.ilMediaItem.php");
				$med_id = (int) $a_mapping->getMapping("Services/MediaObjects", "mob_media_item", $a_rec["MiId"]);
				if (is_object($this->current_media_item) && $this->current_media_item->getId() == $med_id)
				{
					$med = $this->current_media_item;
				}
				else
				{
					$med = new ilMediaItem($med_id);
				}

				include_once("./Services/MediaObjects/classes/class.ilMapArea.php");
				$map_area = new ilMapArea();
				$map_area->setItemId($med_id);
				$map_area->setNr($a_rec["Nr"]);
				$map_area->setShape($a_rec["Shape"]);
				$map_area->setCoords($a_rec["Coords"]);
				$map_area->setLinkType($a_rec["LinkType"]);
				$map_area->setTitle($a_rec["Title"]);
				$map_area->setHref($a_rec["Href"]);
				$map_area->setTarget($a_rec["Target"]);
				$map_area->setType($a_rec["Type"]);
				$map_area->setTargetFrame($a_rec["TargetFrame"]);
				$map_area->create();
				
				break;
		}
	}
	
}
?>