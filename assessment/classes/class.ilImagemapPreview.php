<?php
 /*
   +----------------------------------------------------------------------------+
   | ILIAS open source                                                          |
   +----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne           |
   |                                                                            |
   | This program is free software; you can redistribute it and/or              |
   | modify it under the terms of the GNU General Public License                |
   | as published by the Free Software Foundation; either version 2             |
   | of the License, or (at your option) any later version.                     |
   |                                                                            |
   | This program is distributed in the hope that it will be useful,            |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of             |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              |
   | GNU General Public License for more details.                               |
   |                                                                            |
   | You should have received a copy of the GNU General Public License          |
   | along with this program; if not, write to the Free Software                |
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. |
   +----------------------------------------------------------------------------+
*/

require_once "classes/class.ilUtil.php";

/**
* Image map image preview creator
*
* Takes an image and imagemap areas and creates a preview image containing
* the imagemap areas.
*
* @author		Helmut Schottm�ller <hschottm@tzi.de>
* @version	$Id$
* @module   class.ilImagemapPreview.php
* @modulegroup   Assessment
*/
class ilImagemapPreview {
	var $imagemap_filename;
	var $preview_filename;
	var $areas;
	
/**
* ilImagemapPreview constructor
*
* Creates an instance of the ilImagemapPreview class
*
* @param integer $id The database id of a image map question object
* @access public
*/
  function ilImagemapPreview(
		$imagemap_filename = "",
		$preview_filename = ""
  )

  {
		$this->imagemap_filename = $imagemap_filename;
		$this->preview_filename = $preview_filename;
		if (!is_file($this->preview_filename))
		{
			$this->preview_filename = tempnam("", "preview-") . ".jpg";
		}
		$this->areas = array();
	}

	function addArea(
		$shape,
		$coords,
		$title = "",
		$href = "",
		$target = "",
		$visible = true,
		$linecolor = "red",
		$bordercolor = "white"
	)
	{
		array_push($this->areas, array(
			"shape" => "$shape",
			"coords" => "$coords",
			"title" => "$title",
			"href" => "$href",
			"target" => "$target",
			"linecolor" => "$linecolor",
			"bordercolor" => "$bordercolor",
			"visible" => (int)$visible
		));
	}
	
	function deleteArea($key, $value)
	{
		foreach ($this->areas as $areakey => $areavalue)
		{
			if (strcmp($value, $areavalue[$key]) == 0)
			{
				unset($this->areas[$areakey]);				
			}
		}
		$this->areas = array_values($this->areas);
	}
	
	function createPreview()
	{
		if (!count($this->areas)) return;
		$convert_prefix = ilUtil::getConvertCmd() . " -quality 100 ";
		foreach ($this->areas as $area)
		{
			if ($area["visible"] and strcmp(strtolower($area["shape"]), "rect") == 0) 
			{
				preg_match("/(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/", $area["coords"], $matches);
				$x0 = $matches[1];
				$y0 = $matches[2];
				$x1 = $matches[3];
				$y1 = $matches[4];
				// draw a rect around the selection
				$convert_cmd .=	"-stroke " . $area["bordercolor"] . " -fill none -linewidth 5 -draw \"rectangle " .
				$x0 . "," . $y0 .	" " . ($x1) . "," . $y1 . "\" " .
				"-stroke " . $area["linecolor"] . " -fill none -linewidth 3 -draw \"rectangle " .
				$x0 . "," . $y0 .	" " . ($x1) . "," . $y1 . "\" ";
			} 
			else if ($area["visible"] and strcmp(strtolower($area["shape"]), "circle") == 0) 
			{
				preg_match("/(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/", $area["coords"], $matches);
				$x = $matches[1];
				$y = $matches[2];
				$r = $matches[3];
				// draw a circle around the selection
				$convert_cmd .= "-stroke " . $area["bordercolor"] . " -fill none -linewidth 5 -draw \"circle " .
				$x . "," . $y .	" " . ($x+$r) . "," . $y . "\" " .
				"-stroke " . $area["linecolor"] . " -fill none -linewidth 3 -draw \"circle " .
				$x . "," . $y .	" " . ($x+$r) . "," . $y . "\" ";
			} 
			else if ($area["visible"] and strcmp(strtolower($area["shape"]), "poly") == 0) 
			{
				// draw a polygon around the selection
				$convert_cmd .= "-stroke " . $area["bordercolor"] . " -fill none -linewidth 5 -draw \"polygon ";
				preg_match_all("/(\d+)\s*,\s*(\d+)/", $area["coords"], $matches, PREG_PATTERN_ORDER);
				for ($i = 0; $i < count($matches[0]); $i++) 
				{
					$convert_cmd .= $matches[1][$i] . "," . $matches[2][$i] .	" ";
				}
				$convert_cmd .= "\" ";
				$convert_cmd .= "-stroke " . $area["linecolor"] . " -fill none -linewidth 3 -draw \"polygon ";
				preg_match_all("/(\d+)\s*,\s*(\d+)/", $area["coords"], $matches, PREG_PATTERN_ORDER);
				for ($i = 0; $i < count($matches[0]); $i++) 
				{
					$convert_cmd .= $matches[1][$i] . "," . $matches[2][$i] .	" ";
				}
				$convert_cmd .= "\" ";
			}
		}
		$convert_cmd = $convert_prefix . $convert_cmd .  "$this->imagemap_filename $this->preview_filename";
		system($convert_cmd);
	}
	
	function getPreviewFilename()
	{
		return $this->preview_filename;
	}
	
	function getImagemap($title)
	{
		$map = "<map name=\"$title\"> ";
		foreach ($this->areas as $area)
		{
			$map .= "<area alt=\"" . $area["title"] . "\"  title=\"" . $area["title"] . "\" ";
			$map .= "shape=\"" . $area["shape"] . "\" ";
			$map .= "coords=\"" .  $area["coords"] . "\" ";
			if ($area["href"])
			{
				$map .= "href=\"" . $area["href"] . "\" ";
				if ($area["target"])
				{
					$map .= "target=\"" . $area["target"] . "\" ";
				}
				$map .= "/>\n";
			}
			else
			{
				$map .= "nohref />\n";
			}
		}
		$map .= "</map>";
		return $map;
	}

}
?>
