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

require_once("content/classes/class.ilLMPageObject.php");
require_once("content/classes/Pages/class.ilPageObject.php");
require_once("content/classes/class.ilStructureObject.php");
require_once("content/classes/class.ilObjLearningModule.php");
require_once("classes/class.ilMetaData.php");
require_once("content/classes/Pages/class.ilPCParagraph.php");
require_once("content/classes/Pages/class.ilPCTable.php");
require_once("content/classes/Media/class.ilObjMediaObject.php");
require_once("content/classes/Media/class.ilMediaItem.php");
require_once("content/classes/Media/class.ilMapArea.php");
require_once("content/classes/class.ilBibItem.php");
require_once("content/classes/class.ilObjGlossary.php");
require_once("content/classes/class.ilGlossaryTerm.php");
require_once("content/classes/class.ilGlossaryDefinition.php");
require_once("content/classes/Pages/class.ilInternalLink.php");
require_once("classes/class.ilObjFile.php");

/**
* Content Object Parser
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @extends ilSaxParser
* @package content
*/
class ilContObjParser extends ilSaxParser
{
	var $lng;
	var $tree;
	var $cnt;				// counts open elements
	var $current_element;	// store current element type
	var $learning_module;	// current learning module
	var $page_object;		// current page object
	var $lm_page_object;
	var $structure_objects;	// array of current structure objects
	var $media_object;
	var $current_object;	// at the time a LearningModule, PageObject or StructureObject
	var $meta_data;			// current meta data object
	var $paragraph;
	var $table;
	var $lm_tree;
	var $pg_into_tree;
	var $st_into_tree;
	var $container;
	var $in_page_object;	// are we currently within a PageObject? true/false
	var $in_meta_data;		// are we currently within MetaData? true/false
	var $in_media_object;
	var $in_file_item;
	var $in_glossary;
	var $in_map_area;
	var $content_object;
	var $glossary_object;
	var $file_item;
	var $keyword_language;
	var $pages_to_parse;
	var $mob_mapping;
	var $file_item_mapping;
	var $subdir;
	var $media_item;		// current media item
	var $loc_type;			// current location type
	var $bib_item;			// current bib item object
	var $map_area;			// current map area
	var $in_bib_item;		// are we currently within BibItem? true/false
	var $link_targets;		// stores all objects by import id

	/**
	* Constructor
	*
	* @param	object		$a_content_object	must be of type ilObjContentObject
	* @param	string		$a_xml_file			xml file
	* @param	string		$a_subdir			subdirectory in import directory
	* @access	public
	*/
	function ilContObjParser(&$a_content_object, $a_xml_file, $a_subdir)
	{
		global $lng, $tree;

		parent::ilSaxParser($a_xml_file);
		$this->cnt = array();
		$this->current_element = array();
		$this->structure_objects = array();
		$this->content_object =& $a_content_object;
		//$this->lm_id = $a_lm_id;
		$this->st_into_tree = array();
		$this->pg_into_tree = array();
		$this->pages_to_parse = array();
		$this->mobs_with_int_links = array();
		$this->mob_mapping = array();
		$this->file_item_mapping = array();
		$this->pg_mapping = array();
		$this->link_targets = array();
		$this->subdir = $a_subdir;
		$this->lng =& $lng;
		$this->tree =& $tree;
		$this->inside_code = false;

		$this->lm_tree = new ilTree($this->content_object->getId());
		$this->lm_tree->setTreeTablePK("lm_id");
		$this->lm_tree->setTableNames('lm_tree','lm_data');
		//$this->lm_tree->addTree($a_lm_id, 1); happens in ilObjLearningModuleGUI

	}

	/**
	* set event handler
	* should be overwritten by inherited class
	* @access	private
	*/
	function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser,$this);
		xml_set_element_handler($a_xml_parser,'handlerBeginTag','handlerEndTag');
		xml_set_character_data_handler($a_xml_parser,'handlerCharacterData');
	}

	function startParsing()
	{
		parent::startParsing();
//echo "<b>storeTree</b><br>";
		$this->storeTree();
//echo "<b>processIntLinks</b><br>";
		$this->processPagesToParse();
//echo "<b>copyMobFiles</b><br>";
		$this->copyMobFiles();
//echo "<b>copyFileItems</b><br>";
		$this->copyFileItems();
	}

	/**
	* insert StructureObjects and PageObjects into tree
	*/
	function storeTree()
	{
//echo "<b>Storing the tree</b><br>";
		foreach($this->st_into_tree as $st)
		{
//echo "insert st id: ".$st["id"].", parent:".$st["parent"]."<br>";
			$this->lm_tree->insertNode($st["id"], $st["parent"]);
//echo "done<br>";
			if (is_array($this->pg_into_tree[$st["id"]]))
			{
				foreach($this->pg_into_tree[$st["id"]] as $pg)
				{
					switch ($pg["type"])
					{
						case "pg_alias":
							if ($this->pg_mapping[$pg["id"]] == "")
							{
								echo "No PageObject for PageAlias ".$pg["id"]." found! Aborting.";
								exit;
							}
//echo "storeTree.pg_alias:".$pg["id"].":".$this->pg_mapping[$pg["id"]].":".$st["id"].":<br>";
							$this->lm_tree->insertNode($this->pg_mapping[$pg["id"]], $st["id"]);
//echo "done<br>";
							break;

						case "pg":
//echo "storeTree.pg:".$pg["id"].":".$st["id"].":<br>";
							$this->lm_tree->insertNode($pg["id"], $st["id"]);
//echo "done<br>";
							break;
					}
				}
			}
		}
//echo "6";
//echo "<b>END: storing the tree</b>";
	}


	/**
	* parse pages that contain files, mobs and/or internal links
	*/
	function processPagesToParse()
	{
		/*
		$pg_mapping = array();
		foreach($this->pg_mapping as $key => $value)
		{
			$pg_mapping[$key] = "il__pg_".$value;
		}*/
//echo "<br><b>processIntLinks</b>";
		// outgoin internal links
		foreach($this->pages_to_parse as $page_id)
		{
			$page_obj =& new ilPageObject($this->content_object->getType(), $page_id);
			$page_obj->buildDom();
			$page_obj->resolveIntLinks();
			$page_obj->update(false);
			unset($page_obj);
		}

		// outgoins map area (mob) internal links
		foreach($this->mobs_with_int_links as $mob_id)
		{
			ilMediaItem::_resolveMapAreaLinks($mob_id);
		}

		// incoming internal links
		$done = array();
		foreach ($this->link_targets as $link_target)
		{
//echo "doin link target:".$link_target.":<br>";
			$link_arr = explode("_", $link_target);
			$target_inst = $link_arr[1];
			$target_type = $link_arr[2];
			$target_id = $link_arr[3];
			$sources = ilInternalLink::_getSourcesOfTarget($target_type, $target_id, $target_inst);
			foreach($sources as $key => $source)
			{
//echo "got source:".$key.":<br>";
				if(in_array($key, $done))
				{
					continue;
				}
				$type_arr = explode(":", $source["type"]);

				// content object pages
				if ($type_arr[1] == "pg")
				{
					$page_object = new ilPageObject($type_arr[0], $source["id"]);
					$page_object->buildDom();
					$page_object->resolveIntLinks();
					$page_object->update();
					unset($page_object);
				}
				$done[$key] = $key;
			}
		}
	}


	/**
	* copy multimedia object files from import zip file to mob directory
	*/
	function copyMobFiles()
	{
		$imp_dir = $this->content_object->getImportDirectory();
		foreach ($this->mob_mapping as $origin_id => $mob_id)
		{
			if(empty($origin_id))
			{
				continue;
			}

			/*
			$origin_arr = explode("_", $origin_id);
			if ($origin_arr[2] == "el") // imagemap
			{
				$obj_dir = "imagemap".$origin_arr[3];
			}
			else // normal media object
			{
				$obj_dir = "mm".$origin_arr[3];
			}*/

			$obj_dir = $origin_id;
			$source_dir = $imp_dir."/".$this->subdir."/objects/".$obj_dir;
			$target_dir = ilUtil::getWebspaceDir()."/mobs/mm_".$mob_id;

//echo "copy from $source_dir to $target_dir <br>";
			if (@is_dir($source_dir))
			{
				// make target directory
				ilUtil::makeDir($target_dir);
				//@mkdir($target_dir);
				//@chmod($target_dir, 0755);

				if (@is_dir($target_dir))
				{
					ilUtil::rCopy($source_dir, $target_dir);
				}
			}
		}
	}

	/**
	* copy files of file items
	*/
	function copyFileItems()
	{
		$imp_dir = $this->content_object->getImportDirectory();
		foreach ($this->file_item_mapping as $origin_id => $file_id)
		{
			if(empty($origin_id))
			{
				continue;
			}
			$obj_dir = $origin_id;
			$source_dir = $imp_dir."/".$this->subdir."/objects/".$obj_dir;
			$target_dir = ilUtil::getDataDir()."/files/file_".$file_id;

//echo "copy from $source_dir to $target_dir <br>";
			if (@is_dir($source_dir))
			{
				// make target directory
				ilUtil::makeDir($target_dir);
				//@mkdir($target_dir);
				//@chmod($target_dir, 0755);

				if (@is_dir($target_dir))
				{
					ilUtil::rCopy($source_dir, $target_dir);
				}
			}
		}
	}


	/*
	* update parsing status for a element begin
	*/
	function beginElement($a_name)
	{
		if(!isset($this->status["$a_name"]))
		{
			$this->cnt[$a_name] == 1;
		}
		else
		{
			$this->cnt[$a_name]++;
		}
		$this->current_element[count($this->current_element)] = $a_name;
	}

	/*
	* update parsing status for an element ending
	*/
	function endElement($a_name)
	{
		$this->cnt[$a_name]--;
		unset ($this->current_element[count($this->current_element) - 1]);
	}

	/*
	* returns current element
	*/
	function getCurrentElement()
	{
		return ($this->current_element[count($this->current_element) - 1]);
	}

	/*
	* returns number of current open elements of type $a_name
	*/
	function getOpenCount($a_name)
	{
		if (isset($this->cnt[$a_name]))
		{
			return $this->cnt[$a_name];
		}
		else
		{
			return 0;
		}

	}

	/**
	* generate a tag with given name and attributes
	*
	* @param	string		"start" | "end" for starting or ending tag
	* @param	string		element/tag name
	* @param	array		array of attributes
	*/
	function buildTag ($type, $name, $attr="")
	{
		$tag = "<";

		if ($type == "end")
			$tag.= "/";

		$tag.= $name;

		if (is_array($attr))
		{
			while (list($k,$v) = each($attr))
				$tag.= " ".$k."=\"$v\"";
		}

		$tag.= ">";

		return $tag;
	}

	/**
	* handler for begin of element
	*/
	function handlerBeginTag($a_xml_parser,$a_name,$a_attribs)
	{
//echo "BEGIN TAG: $a_name <br>";
		switch($a_name)
		{
			case "ContentObject":
				$this->current_object =& $this->content_object;
//echo "<br>Parser:CObjType:".$a_attribs["Type"];
				if ($a_attribs["Type"] == "Glossary")
				{
					$this->glossary_object =& $this->content_object;
				}
				break;

			case "StructureObject":
//echo "<br><br>StructureOB-SET-".count($this->structure_objects)."<br>";
				$this->structure_objects[count($this->structure_objects)]
					=& new ilStructureObject($this->content_object);
				$this->current_object =& $this->structure_objects[count($this->structure_objects) - 1];
				$this->current_object->setLMId($this->content_object->getId());
				break;

			case "PageObject":
				$this->in_page_object = true;
				$this->lm_page_object =& new ilLMPageObject($this->content_object);
				$this->page_object =& new ilPageObject($this->content_object->getType());
				$this->lm_page_object->setLMId($this->content_object->getId());
				$this->lm_page_object->assignPageObject($this->page_object);
				$this->current_object =& $this->lm_page_object;
				break;

			case "PageAlias":
				$this->lm_page_object->setAlias(true);
				$this->lm_page_object->setOriginID($a_attribs["OriginId"]);
				break;

			case "MediaObject":
//echo "<br>---NEW MEDIAOBJECT---<br>";
				$this->in_media_object = true;
				$this->media_object =& new ilObjMediaObject();
				break;

			case "MediaAlias":
//echo "<br>---NEW MEDIAALIAS---<br>";
				$this->media_object->setAlias(true);
				$this->media_object->setOriginID($a_attribs["OriginId"]);
				if (is_object($this->page_object))
				{
					$this->page_object->needsImportParsing(true);
				}
				break;

			case "MediaItem":
			case "MediaAliasItem":
				$this->in_media_item = true;
				$this->media_item =& new ilMediaItem();
				$this->media_item->setPurpose($a_attribs["Purpose"]);
				break;

			case "Layout":
				if (is_object($this->media_object) && $this->in_media_object)
				{
					$this->media_item->setWidth($a_attribs["Width"]);
					$this->media_item->setHeight($a_attribs["Height"]);
					$this->media_item->setHAlign($a_attribs["HorizontalAlign"]);
				}
				break;

			case "Parameter":
				if (is_object($this->media_object) && $this->in_media_object)
				{
					$this->media_item->setParameter($a_attribs["Name"], $a_attribs["Value"]);
				}
				break;

			case "MapArea":
				$this->in_map_area = true;
				$this->map_area =& new ilMapArea();
				$this->map_area->setShape($a_attribs["Shape"]);
				$this->map_area->setCoords($a_attribs["Coords"]);
				break;

			case "Glossary":
				$this->in_glossary = true;
				if ($this->content_object->getType() != "glo")
				{
					$this->glossary_object =& new ilObjGlossary();
					$this->glossary_object->setTitle("");
					$this->glossary_object->setDescription("");
					$this->glossary_object->create();
					$this->glossary_object->createReference();
					$parent =& $this->tree->getParentNodeData($this->content_object->getRefId());
					$this->glossary_object->putInTree($parent["child"]);
					$this->glossary_object->setPermissions($parent["child"]);
					$this->glossary_object->notify("new", $_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$this->glossary_object->getRefId());
				}
				$this->current_object =& $this->glossary_object;
				break;

			case "GlossaryItem":
				$this->glossary_term =& new ilGlossaryTerm();
				$this->glossary_term->setGlossaryId($this->glossary_object->getId());
				$this->glossary_term->setLanguage($a_attribs["Language"]);
				$this->glossary_term->setImportId($a_attribs["Id"]);
				$this->link_targets[$a_attribs["Id"]] = $a_attribs["Id"];
				break;

			case "Definition":
				$this->in_glossary_definition = true;
				$this->glossary_definition =& new ilGlossaryDefinition();
				$this->page_object =& new ilPageObject("gdf");
				$this->glossary_definition->setTermId($this->glossary_term->getId());
				$this->glossary_definition->assignPageObject($this->page_object);
				$this->current_object =& $this->glossary_definition;
				break;

			case "FileItem":
				$this->in_file_item = true;
				$this->file_item =& new ilObjFile();
				$this->file_item->setTitle("dummy");
				if (is_object($this->page_object))
				{
					$this->page_object->needsImportParsing(true);
				}
				break;

			case "Paragraph":
				if ($a_attribs["Characteristic"] == "Code")
				{
					$this->inside_code = true;
				}
				break;


			////////////////////////////////////////////////
			/// Meta Data Section
			////////////////////////////////////////////////
			case "MetaData":
				$this->in_meta_data = true;
#echo "<br>---NEW METADATA---<br>";
				$this->meta_data =& new ilMetaData();
				if(!$this->in_media_object)
				{
//echo "<br><b>assign to current object</b>";
					$this->current_object->assignMetaData($this->meta_data);
					if(get_class($this->current_object) == "ilobjlearningmodule")
					{
						$this->meta_data->setId($this->content_object->getId());
						$this->meta_data->setType("lm");
					}
				}
				else
				{
					$this->media_object->assignMetaData($this->meta_data);
				}
				break;

			// GENERAL: Identifier
			case "Identifier":
				if ($this->in_meta_data)
				{
					$this->meta_data->setImportIdentifierEntryID($a_attribs["Entry"]);
					$this->link_targets[$a_attribs["Entry"]] = $a_attribs["Entry"];
				}
				if ($this->in_file_item)
				{
					if ($this->file_item_mapping[$a_attribs["Entry"]] == "")
					{
						$this->file_item->create();
						$this->file_item->setImportId($a_attribs["Entry"]);
						$this->file_item_mapping[$a_attribs["Entry"]] = $this->file_item->getId();
					}
				}
				break;

			// GENERAL: Keyword
			case "Keyword":
//echo "<b>>>".count($this->meta_data->technicals)."</b><br>";
				$this->keyword_language = $a_attribs["Language"];
				break;

			// Internal Link
			case "IntLink":
				if (is_object($this->page_object))
				{
					$this->page_object->setContainsIntLink(true);
				}
				if ($this->in_map_area)
				{
//echo "intlink:maparea:<br>";
					$this->map_area->setLinkType(IL_INT_LINK);
					$this->map_area->setTarget($a_attribs["Target"]);
					$this->map_area->setType($a_attribs["Type"]);
					$this->map_area->setTargetFrame($a_attribs["TargetFrame"]);
					if (is_object($this->media_object))
					{
//echo ":setContainsLink:<br>";
						$this->media_object->setContainsIntLink(true);
					}
				}
				break;

			// External Link
			case "ExtLink":
				if ($this->in_map_area)
				{
					$this->map_area->setLinkType(IL_EXT_LINK);
					$this->map_area->setHref($a_attribs["Href"]);
					$this->map_area->setExtTitle($a_attribs["Title"]);
				}
				break;

			// TECHNICAL
			case "Technical":
				$this->meta_technical =& new ilMetaTechnical($this->meta_data);
				$this->meta_data->addTechnicalSection($this->meta_technical);
//echo "<b>>>".count($this->meta_data->technicals)."</b><br>";
				break;

			// TECHNICAL: Size
			case "Size":
				$this->meta_technical->setSize($a_attribs["Size"]);
				break;

			case "Location":
				$this->loc_type = $a_attribs["Type"];
				break;

			// TECHNICAL: Requirement
			case "Requirement":
				if (!is_object($this->requirement_set))
				{
					$this->requirement_set =& new ilMetaTechnicalRequirementSet();
				}
				$this->requirement =& new ilMetaTechnicalRequirement();
				break;

			// TECHNICAL: OperatingSystem
			case "OperatingSystem":
				$this->requirement->setType("OperatingSystem");
				$this->requirement->setName($a_attribs["Name"]);
				$this->requirement->setMinVersion($a_attribs["MinimumVersion"]);
				$this->requirement->setMaxVersion($a_attribs["MaximumVersion"]);
				break;

			// TECHNICAL: Browser
			case "Browser":
				$this->requirement->setType("Browser");
				$this->requirement->setName($a_attribs["Name"]);
				$this->requirement->setMinVersion($a_attribs["MinimumVersion"]);
				$this->requirement->setMaxVersion($a_attribs["MaximumVersion"]);
				break;

			// TECHNICAL: OrComposite
			case "OrComposite":
				$this->meta_technical->addRequirementSet($this->requirement_set);
				unset($this->requirement_set);
				break;

			// TECHNICAL: InstallationRemarks
			case "InstallationRemarks":
				$this->meta_technical->setInstallationRemarksLanguage($a_attribs["Language"]);
				break;

			// TECHNICAL: InstallationRemarks
			case "OtherPlatformRequirements":
				$this->meta_technical->setOtherRequirementsLanguage($a_attribs["Language"]);
				break;

			case "Bibliography":
				$this->in_bib_item = true;
#echo "<br>---NEW BIBLIOGRAPHY---<br>";
				$this->bib_item =& new ilBibItem();
				break;

		}
		$this->beginElement($a_name);
//echo "Begin Tag: $a_name<br>";

		// append content to page xml content
		if(($this->in_page_object || $this->in_glossary_definition)
			&& !$this->in_meta_data && !$this->in_media_object)
		{
			if ($a_name == "Definition")
			{
				$app_name = "PageObject";
				$app_attribs = array();
			}
			else
			{
				$app_name = $a_name;
				$app_attribs = $a_attribs;
			}

			// change identifier entry of file items to new local file id
			if ($this->in_file_item && $app_name == "Identifier")
			{
				$app_attribs["Entry"] = "il__file_".$this->file_item_mapping[$a_attribs["Entry"]];
				//$app_attribs["Entry"] = "il__file_".$this->file_item->getId();
			}

			$this->page_object->appendXMLContent($this->buildTag("start", $app_name, $app_attribs));
//echo "&nbsp;&nbsp;after append, xml:".$this->page_object->getXMLContent().":<br>";
		}
		// append content to meta data xml content
		if ($this->in_meta_data )   // && !$this->in_page_object && !$this->in_media_object
		{
			$this->meta_data->appendXMLContent("\n".$this->buildTag("start", $a_name, $a_attribs));
		}
		// append content to bibitem xml content
		if ($this->in_bib_item)   // && !$this->in_page_object && !$this->in_media_object
		{
			$this->bib_item->appendXMLContent("\n".$this->buildTag("start", $a_name, $a_attribs));
		}
	}


	/**
	* handler for end of element
	*/
	function handlerEndTag($a_xml_parser,$a_name)
	{
//echo "END TAG: $a_name <br>";
		// append content to page xml content
		if (($this->in_page_object || $this->in_glossary_definition)
			&& !$this->in_meta_data && !$this->in_media_object)
		{
			$app_name = ($a_name == "Definition")
				? "PageObject"
				: $a_name;
			$this->page_object->appendXMLContent($this->buildTag("end", $app_name));
		}

		if ($this->in_meta_data)	//  && !$this->in_page_object && !$this->in_media_object

		// append content to metadataxml content
		if($a_name == "MetaData")
		{
			$this->meta_data->appendXMLContent("\n".$this->buildTag("end", $a_name));
		}
		else
		{
			$this->meta_data->appendXMLContent($this->buildTag("end", $a_name));
		}

		// append content to bibitemxml content
		if ($this->in_bib_item)	// && !$this->in_page_object && !$this->in_media_object
		{
			if($a_name == "BibItem")
			{
				$this->bib_item->appendXMLContent("\n".$this->buildTag("end", $a_name));
			}
			else
			{
				$this->bib_item->appendXMLContent($this->buildTag("end", $a_name));
			}

		}

		switch($a_name)
		{
			case "StructureObject":
				unset($this->meta_data);
				unset($this->structure_objects[count($this->structure_objects) - 1]);
				break;

			case "PageObject":

				$this->in_page_object = false;
				if (!$this->lm_page_object->isAlias())
				{
//echo "ENDPageObject ".$this->page_object->getImportId().":<br>";
					//$this->page_object->createFromXML();
					$this->page_object->updateFromXML();
					$this->pg_mapping[$this->lm_page_object->getImportId()]
						= $this->lm_page_object->getId();

					// collect pages with internal links
					if ($this->page_object->containsIntLink())
					{
						$this->pages_to_parse[$this->page_object->getId()] = $this->page_object->getId();
					}
					if ($this->page_object->needsImportParsing())
					{
						$this->pages_to_parse[$this->page_object->getId()] = $this->page_object->getId();
					}
				}

				// if we are within a structure object: put page in tree
				$cnt = count($this->structure_objects);
				if ($cnt > 0)
				{
					$parent_id = $this->structure_objects[$cnt - 1]->getId();
					if ($this->lm_page_object->isAlias())
					{
						$this->pg_into_tree[$parent_id][] = array("type" => "pg_alias", "id" => $this->lm_page_object->getOriginId());
					}
					else
					{
						$this->pg_into_tree[$parent_id][] = array("type" => "pg", "id" => $this->lm_page_object->getId());
					}
				}

				// if we are within a structure object: put page in tree
				unset($this->meta_data);	//!?!
				unset($this->page_object);
				unset($this->lm_page_object);
				unset ($this->container[count($this->container) - 1]);
				break;

			case "MediaObject":
				$this->in_media_object = false;
//echo "ENDMediaObject:ImportId:".$this->media_object->getImportId()."<br>";
				// create media object on first occurence of an Id
				if(empty($this->mob_mapping[$this->media_object->getImportId()]))
				{
					if ($this->media_object->isAlias())
					{
						// this data will be overwritten by the "real" mob
						// see else section below
						$dummy_meta =& new ilMetaData();
						$this->media_object->assignMetaData($dummy_meta);
						$this->media_object->setTitle("dummy");
						$this->media_object->setDescription("dummy");
					}
					else
					{
						$this->media_object->setTitle($this->meta_data->getTitle());
					}

					// create media object
//echo "creating media object:title:".$this->media_object->getTitle().":".
//	$this->meta_data->getTitle().":<br>";
					$this->media_object->create();
//echo "creating media object<br>";
					// collect mobs with internal links
					if ($this->media_object->containsIntLink())
					{
//echo "got int link :".$this->media_object->getId().":<br>";
						$this->mobs_with_int_links[] = $this->media_object->getId();
					}

					$this->mob_mapping[$this->media_object->getImportId()]
							= $this->media_object->getId();
//echo "create:import_id:".$this->media_object->getImportId().":ID:".$this->mob_mapping[$this->media_object->getImportId()]."<br>";
				}
				else
				{
					// get the id from mapping
					$this->media_object->setId($this->mob_mapping[$this->media_object->getImportId()]);

					// update "real" (no alias) media object
					// (note: we overwrite any data from the dummy mob
					// created by an MediaAlias, only the data of the real
					// object is stored in db separately; data of the
					// MediaAliases are within the page XML
					if (!$this->media_object->isAlias())
					{
//echo "<b>REAL UPDATING STARTS HERE</b><br>";
//echo "<b>>>".count($this->meta_data->technicals)."</b><br>";
//echo "origin:".$this->media_object->getImportId().":ID:".$this->mob_mapping[$this->media_object->getImportId()]."<br>";

						// update media object

						$this->meta_data->setId($this->media_object->getId());
						$this->meta_data->setType("mob");
						$this->media_object->assignMetaData($this->meta_data);
						$this->media_object->setTitle($this->meta_data->getTitle());
						$this->media_object->setDescription($this->meta_data->getDescription());

						$this->media_object->update();
#echo "update media object :".$this->media_object->getId().":<br>";

						// collect mobs with internal links
						if ($this->media_object->containsIntLink())
						{
//echo "got int link :".$this->media_object->getId().":<br>";
							$this->mobs_with_int_links[] = $this->media_object->getId();
						}
					}
				}

				// append media alias to page, if we are in a page
				if ($this->in_page_object || $this->in_glossary_definition)
				{
					$this->page_object->appendXMLContent($this->media_object->getXML(IL_MODE_ALIAS));
//echo "Appending:".htmlentities($this->media_object->getXML(IL_MODE_ALIAS))."<br>";
				}

				break;

			case "MediaItem":
			case "MediaAliasItem":
				$this->in_media_item = false;
				$this->media_object->addMediaItem($this->media_item);
//echo "adding media item";
				break;

			case "MapArea":
				$this->in_map_area = false;
				$this->media_item->addMapArea($this->map_area);
				break;


			case "MetaData":

				$this->in_meta_data = false;
                if(get_class($this->current_object) == "illmpageobject" && !$this->in_media_object)
				{
					// Metadaten eines PageObjects sichern in NestedSet
					if (is_object($this->lm_page_object))
					{
						$this->lm_page_object->create(true);
						//$this->page_object->createFromXML();

						include_once("./classes/class.ilNestedSetXML.php");
						$nested = new ilNestedSetXML();
						$xml = $this->meta_data->getXMLContent();
//echo "<br><br>".htmlentities($xml);
						$nested->dom = domxml_open_mem($xml);
						$nodes = $nested->getDomContent("//MetaData/General", "Identifier");
						if (is_array($nodes))
						{
							$nodes[0]["Entry"] = "il__" . $this->current_object->getType() . "_" . $this->current_object->getId();
							$nested->updateDomContent("//MetaData/General", "Identifier", 0, $nodes[0]);
						}
						$xml = $nested->dom->dump_mem(0);
//$xml = str_replace("&quot;", "\"", $xml);
//echo "<br><br>".htmlentities($xml);
						$nested->import($xml,$this->lm_page_object->getId(),"pg");
					}
                }
				else if(get_class($this->current_object) == "ilstructureobject")
				{    // save structure object at the end of its meta block
					// determine parent
					$cnt = count($this->structure_objects);
					if ($cnt > 1)
					{
						$parent_id = $this->structure_objects[$cnt - 2]->getId();
					}
					else
					{
						$parent_id = $this->lm_tree->getRootId();
					}

					// create structure object and put it in tree
					$this->current_object->create(true);
					$this->st_into_tree[] = array ("id" => $this->current_object->getId(),
						"parent" => $parent_id);

					// Metadaten eines StructureObjects sichern in NestedSet
					include_once("./classes/class.ilNestedSetXML.php");
					$nested = new ilNestedSetXML();
					$xml = $this->meta_data->getXMLContent();
					$nested->dom = domxml_open_mem($xml);
					$nodes = $nested->getDomContent("//MetaData/General", "Identifier");
					if (is_array($nodes))
					{
						$nodes[0]["Entry"] = "il__" . $this->current_object->getType() . "_" . $this->current_object->getId();
						$nested->updateDomContent("//MetaData/General", "Identifier", 0, $nodes[0]);
					}
					$xml = $nested->dom->dump_mem(0);
					$nested->import($xml,$this->current_object->getId(),"st");
				}
				else if(get_class($this->current_object) == "ilobjdlbook" || get_class($this->current_object) == "ilobjlearningmodule" ||
					get_class($this->current_object) == "ilobjcontentobject" ||
					(get_class($this->current_object) == "ilobjglossary" && $this->in_glossary))
				{
					// Metadaten eines ContentObjects sichern in NestedSet
					include_once("./classes/class.ilNestedSetXML.php");
					$nested = new ilNestedSetXML();
					$xml = $this->meta_data->getXMLContent();
					$nested->dom = domxml_open_mem($xml);
					$nodes = $nested->getDomContent("//MetaData/General", "Identifier");
					if (is_array($nodes))
					{
						$nodes[0]["Entry"] = "il__" . $this->current_object->getType() . "_" . $this->current_object->getId();
						$nested->updateDomContent("//MetaData/General", "Identifier", 0, $nodes[0]);
					}
					$xml = $nested->dom->dump_mem(0);
//echo "<br><br>class:".get_class($this->current_object).":".htmlentities($xml).":<br>";
//echo "<br>ID:".$this->current_object->getId().":Type:".$this->current_object->getType();
//echo $this->in_glossary;
					$nested->import($xml,$this->current_object->getId(),$this->current_object->getType());
				}
				else if(get_class($this->current_object) == "ilglossarydefinition" && !$this->in_media_object)
				{
//echo "<br><br>class:".get_class($this->current_object).":".htmlentities($this->meta_data->getXMLContent()).":<br>";
					$this->glossary_definition->create();
//echo "<br>ID:".$this->current_object->getId().":Type:".$this->current_object->getType();
					$this->page_object->setId($this->glossary_definition->getId());
					$this->page_object->updateFromXML();
//echo "saving page_object, xml:".$this->page_object->getXMLContent().":<br>";
					// save glossary term definition to nested set
					include_once("./classes/class.ilNestedSetXML.php");
					$nested = new ilNestedSetXML();
					$xml = $this->meta_data->getXMLContent();
					$nested->dom = domxml_open_mem($xml);
					$nodes = $nested->getDomContent("//MetaData/General", "Identifier");
					if (is_array($nodes))
					{
						$nodes[0]["Entry"] = "il__" . $this->current_object->getType() . "_" . $this->current_object->getId();
						$nested->updateDomContent("//MetaData/General", "Identifier", 0, $nodes[0]);
					}
					$xml = $nested->dom->dump_mem(0);
//echo "<br><br>class:".get_class($this->current_object).":".htmlentities($xml).":<br>";
//echo "<br>ID:".$this->glossary_definition->getId().":Type:gdf";
					$nested->import($xml,$this->glossary_definition->getId(),"gdf");
                }


				if(get_class($this->current_object) == "ilobjlearningmodule" ||
					get_class($this->current_object) == "ilobjdlbook" ||
					get_class($this->current_object) == "ilobjglossary")
				{
					if (get_class($this->current_object) == "ilobjglossary" &&
						$this->content_object->getType() != "glo")
					{
//echo "<br><b>getting2: ".$this->content_object->getTitle()."</b>";
						$this->current_object->setTitle($this->content_object->getTitle()." - ".
							$this->lng->txt("glossary"));
					}
					$this->current_object->update();
				}

#				echo "Type: " . $this->current_object->getType() . "<br>\n";
#				echo "Obj.-ID: " . $this->current_object->getId() . "<br>\n";
				break;

			case "FileItem":
				$this->in_file_item = false;
				// only update new file items
				if ($this->file_item->getImportId($a_attribs["Entry"] != ""))
				{
					$this->file_item->update();
				}
				break;

			case "Bibliography":

				$this->in_bib_item = false;

				$nested = new ilNestedSetXML();
				$nested->import($this->bib_item->getXMLContent(),$this->content_object->getId(),"bib");
				break;

			case "Table":
				unset ($this->container[count($this->container) - 1]);
				break;

			case "Glossary":
				$this->in_glossary = false;
				break;

			case "GlossaryTerm":
				$this->glossary_term->setTerm($this->chr_data);
				$this->glossary_term->create();
				break;

			case "Paragraph":
				$this->inside_code = false;
				break;

			case "Definition":
				$this->in_glossary_definition = false;
				$this->page_object->updateFromXML();
				$this->page_object->buildDom();
				$this->glossary_definition->setShortText($this->page_object->getFirstParagraphText());
				$this->glossary_definition->update();
				//$this->pg_mapping[$this->lm_page_object->getImportId()]
				//	= $this->lm_page_object->getId();
				if ($this->page_object->containsIntLink())
				{
					$this->pages_to_parse[$this->page_object->getId()] = $this->page_object->getId();
				}
				break;

			case "Format":
				if ($this->in_media_item)
				{
					$this->media_item->setFormat($this->chr_data);
				}
				if ($this->in_meta_data)
				{
					$this->meta_technical->addFormat($this->chr_data);
				}
				if ($this->in_file_item)
				{
					$this->file_item->setFileType($this->chr_data);
				}
				break;

			case "Title":
				$this->current_object->setTitle($this->chr_data);
				$this->meta_data->setTitle($this->chr_data);
				break;

			case "Language":
				if (is_object($this->meta_data))
				{
					$this->meta_data->setLanguage($this->chr_data);
				}
				else if (is_object($this->bib_item))
				{
					$this->bib_item->setLanguage($this->chr_data);
				}
				break;

			case "Description":
				$this->meta_data->setDescription($this->chr_data);
				break;

			case "Caption":
				if ($this->in_media_object)
				{
					$this->media_item->setCaption($this->chr_data);
				}
				break;

			// TECHNICAL: Location
			case "Location":
				// TODO: adapt for files in "real" subdirectories
				if ($this->in_media_item)
				{
					$this->media_item->setLocationType($this->loc_type);
					$this->media_item->setLocation($this->chr_data);
				}
				if ($this->in_meta_data)
				{
					//$this->meta_technical->addLocation($this->loc_type, $a_data);
				}
				if ($this->in_file_item)
				{
					$this->file_item->setFileName($this->chr_data);
					$this->file_item->setTitle($this->chr_data);
				}
				break;

			//////////////////////////////////
			/// MetaData Section
			//////////////////////////////////
			// TECHNICAL: Requirement
			/*
			case "Requirement":
				$this->requirement_set->addRequirement($this->requirement);
				break;*/


		}
		$this->endElement($a_name);
		$this->chr_data = "";
	}

	/**
	* handler for character data
	*/
	function handlerCharacterData($a_xml_parser,$a_data)
	{

		// i don't know why this is necessary, but
		// the parser seems to convert "&gt;" to ">" and "&lt;" to "<"
		// in character data, but we don't want that, because it's the
		// way we mask user html in our content, so we convert back...
		$a_data = str_replace("<","&lt;",$a_data);
		$a_data = str_replace(">","&gt;",$a_data);

		// DELETE WHITESPACES AND NEWLINES OF CHARACTER DATA
		$a_data = preg_replace("/\n/","",$a_data);
		if (!$this->inside_code)
		{
			$a_data = preg_replace("/\t+/","",$a_data);
		}

		$this->chr_data .= $a_data;

		if(!empty($a_data))
		{
			// append all data to page, if we are within PageObject,
			// but not within MetaData or MediaObject
			if (($this->in_page_object || $this->in_glossary_definition)
				&& !$this->in_meta_data && !$this->in_media_object)
			{
				$this->page_object->appendXMLContent($a_data);
			}

			if ($this->in_meta_data  )
			{
				$this->meta_data->appendXMLContent($a_data);
//echo "<br>".$a_data;
			}

			if ($this->in_bib_item  )
			{
				$this->bib_item->appendXMLContent($a_data);
			}

			switch($this->getCurrentElement())
			{

				case "IntLink":
				case "ExtLink":
					if($this->in_map_area)
					{
						$this->map_area->appendTitle($a_data);
					}
					break;

			}
		}

	}

}
?>
