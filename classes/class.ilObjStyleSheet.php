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


require_once "classes/class.ilObject.php";

/**
* Class ilObjStyleSheet
*
* @author Alex Killing <alex.killing@gmx.de>
* $Id$
*
* @extends ilObject
* @package ilias-core
*/
class ilObjStyleSheet extends ilObject
{
	var $style;


	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjStyleSheet($a_id = 0, $a_call_by_reference = false)
	{
		$this->type = "sty";
		$this->style = array();
		if($a_call_by_reference)
		{
			$this->ilias->raiseError("Can't instantiate media object via reference id.",$this->ilias->error_obj->FATAL);
		}

		parent::ilObject($a_id, false);
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

	/**
	* assign meta data object
	*/
	function assignMetaData(&$a_meta_data)
	{
		$this->meta_data =& $a_meta_data;
	}

	/**
	* get meta data object
	*/
	function &getMetaData()
	{
		return $this->meta_data;
	}

	function create()
	{
		parent::create();

		$def = array(
			array("tag" => "div", "class" => "PageTitle", "parameter" => "margin-top" ,"value" => "5px"),
			array("tag" => "div", "class" => "PageTitle", "parameter" => "margin-bottom" ,"value" => "20px"),
			array("tag" => "div", "class" => "PageTitle", "parameter" => "font-size" ,"value" => "23px"),
			array("tag" => "div", "class" => "PageTitle", "parameter" => "font-weight" ,"value" => "bold"),
			array("tag" => "div", "class" => "PageTitle", "parameter" => "padding-bottom" ,"value" => "3px"),
			array("tag" => "div", "class" => "PageTitle", "parameter" => "border-bottom-width" ,"value" => "1px"),
			array("tag" => "div", "class" => "PageTitle", "parameter" => "border-bottom-style" ,"value" => "solid"),
			array("tag" => "div", "class" => "PageTitle", "parameter" => "border-color" ,"value" => "#000000"),
			array("tag" => "span", "class" => "Strong", "parameter" => "font-weight" ,"value" => "bold"),
			array("tag" => "span", "class" => "Emph", "parameter" => "font-style" ,"value" => "italic"),
			array("tag" => "span", "class" => "Comment", "parameter" => "color" ,"value" => "green"),
			array("tag" => "span", "class" => "Quotation", "parameter" => "color" ,"value" => "brown"),
			array("tag" => "span", "class" => "Quotation", "parameter" => "font-style" ,"value" => "italic"),
			array("tag" => "a", "class" => "FootnoteLink", "parameter" => "color" ,"value" => "blue"),
			array("tag" => "a", "class" => "FootnoteLink", "parameter" => "font-weight" ,"value" => "normal"),
			array("tag" => "a", "class" => "FootnoteLink:hover", "parameter" => "color" ,"value" => "#000000"),
			array("tag" => "div", "class" => "Footnote", "parameter" => "margin-top" ,"value" => "5px"),
			array("tag" => "div", "class" => "Footnote", "parameter" => "margin-bottom" ,"value" => "5px"),
			array("tag" => "div", "class" => "Footnote", "parameter" => "font-style" ,"value" => "italic"),
			array("tag" => "a", "class" => "IntLink", "parameter" => "color" ,"value" => "blue"),
			array("tag" => "a", "class" => "IntLink", "parameter" => "font-weight" ,"value" => "normal"),
			array("tag" => "a", "class" => "IntLink", "parameter" => "text-decoration" ,"value" => "underline"),
			array("tag" => "a", "class" => "IntLink:hover", "parameter" => "color" ,"value" => "#000000"),
			array("tag" => "div", "class" => "LMNavigation", "parameter" => "background-color" ,"value" => "#EEEEEE"),
			array("tag" => "div", "class" => "LMNavigation", "parameter" => "padding" ,"value" => "5px"),
			array("tag" => "div", "class" => "LMNavigation", "parameter" => "border-spacing" ,"value" => "1px"),
			array("tag" => "div", "class" => "LMNavigation", "parameter" => "border-style" ,"value" => "outset"),
			array("tag" => "div", "class" => "LMNavigation", "parameter" => "border-color" ,"value" => "#EEEEEE"),
			array("tag" => "div", "class" => "LMNavigation", "parameter" => "border-width" ,"value" => "1px"),
			array("tag" => "div", "class" => "Page", "parameter" => "background-color" ,"value" => "#EEEEEE"),
			array("tag" => "div", "class" => "Page", "parameter" => "padding" ,"value" => "20px"),
			array("tag" => "div", "class" => "Page", "parameter" => "border-spacing" ,"value" => "1px"),
			array("tag" => "div", "class" => "Page", "parameter" => "border-style" ,"value" => "outset"),
			array("tag" => "div", "class" => "Page", "parameter" => "border-color" ,"value" => "#EEEEEE"),
			array("tag" => "div", "class" => "Page", "parameter" => "border-width" ,"value" => "1px"),
			array("tag" => "td", "class" => "Cell1", "parameter" => "background-color" ,"value" => "#FFCCCC"),
			array("tag" => "td", "class" => "Cell2", "parameter" => "background-color" ,"value" => "#CCCCFF"),
			array("tag" => "td", "class" => "Cell3", "parameter" => "background-color" ,"value" => "#CCFFCC"),
			array("tag" => "td", "class" => "Cell4", "parameter" => "background-color" ,"value" => "#FFFFCC"),

			array("tag" => "p", "class" => "Standard", "parameter" => "margin-top" ,"value" => "10px"),
			array("tag" => "p", "class" => "Standard", "parameter" => "margin-bottom" ,"value" => "10px"),

			array("tag" => "p", "class" => "List", "parameter" => "margin-top" ,"value" => "3px"),
			array("tag" => "p", "class" => "List", "parameter" => "margin-bottom" ,"value" => "3px"),

			array("tag" => "p", "class" => "Headline", "parameter" => "margin-top" ,"value" => "5px"),
			array("tag" => "p", "class" => "Headline", "parameter" => "margin-bottom" ,"value" => "10px"),
			array("tag" => "p", "class" => "Headline", "parameter" => "font-size" ,"value" => "20px"),
			array("tag" => "p", "class" => "Headline", "parameter" => "font-weight" ,"value" => "bold"),

			array("tag" => "p", "class" => "Example", "parameter" => "padding-left" ,"value" => "20px"),
			array("tag" => "p", "class" => "Example", "parameter" => "border-left" ,"value" => "3px"),
			array("tag" => "p", "class" => "Example", "parameter" => "border-left-style" ,"value" => "solid"),
			array("tag" => "p", "class" => "Example", "parameter" => "border-left-color" ,"value" => "blue"),

			array("tag" => "p", "class" => "Citation", "parameter" => "color" ,"value" => "brown"),
			array("tag" => "p", "class" => "Citation", "parameter" => "font-style" ,"value" => "italic"),

			array("tag" => "p", "class" => "Mnemonic", "parameter" => "margin-left" ,"value" => "20px"),
			array("tag" => "p", "class" => "Mnemonic", "parameter" => "margin-right" ,"value" => "20px"),
			array("tag" => "p", "class" => "Mnemonic", "parameter" => "color" ,"value" => "red"),
			array("tag" => "p", "class" => "Mnemonic", "parameter" => "padding" ,"value" => "10px"),
			array("tag" => "p", "class" => "Mnemonic", "parameter" => "border" ,"value" => "1px"),
			array("tag" => "p", "class" => "Mnemonic", "parameter" => "border-style" ,"value" => "solid"),
			array("tag" => "p", "class" => "Mnemonic", "parameter" => "border-color" ,"value" => "red"),

			array("tag" => "p", "class" => "Additional", "parameter" => "padding" ,"value" => "10px"),
			array("tag" => "p", "class" => "Additional", "parameter" => "border" ,"value" => "1px"),
			array("tag" => "p", "class" => "Additional", "parameter" => "border-style" ,"value" => "solid"),
			array("tag" => "p", "class" => "Additional", "parameter" => "border-color" ,"value" => "blue")

		);



		// default style settings
		foreach ($def as $sty)
		{
			$q = "INSERT INTO style_parameter (style_id, tag, class, parameter, value) VALUES ".
				"('".$this->getId()."','".$sty["tag"]."','".$sty["class"].
				"','".$sty["parameter"]."','".$sty["value"]."')";
			$this->ilias->db->query($q);
		}

		$this->read();
		$this->writeCSSFile();
	}

	function addParameter($a_tag, $a_par)
	{
		$avail_params = $this->getAvailableParameters();
		$tag = explode(".", $a_tag);
		$value = $avail_params[$a_par][0];
		$q = "INSERT INTO style_parameter (style_id, tag, class, parameter, value) VALUES ".
			"('".$this->getId()."','".$tag[0]."','".$tag[1].
			"','".$a_par."','".$value."')";
		$this->ilias->db->query($q);
		$this->read();
		$this->writeCSSFile();
	}

	function deleteParameter($a_id)
	{
		$q = "DELETE FROM style_parameter WHERE id = '".$a_id."'";
		$this->ilias->db->query($q);
	}

	function read()
	{
		parent::read();

		$q = "SELECT * FROM style_parameter WHERE style_id = '".$this->getId()."' ORDER BY tag, class ";
		$style_set = $this->ilias->db->query($q);
		$ctag = "";
		$cclass = "";
		$this->style = array();
		while($style_rec = $style_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if ($style_rec["tag"] != $ctag || $style_rec["class"] != $cclass)
			{
				// add current tag array to style array
				if(is_array($tag))
				{
					$this->style[] = $tag;
				}
				$tag = array();
			}
			$ctag = $style_rec["tag"];
			$cclass = $style_rec["class"];
			$tag[] = $style_rec;
		}
		if(is_array($tag))
		{
			$this->style[] = $tag;
		}
	}

	/**
	* write css file to webspace directory
	*/
	function writeCSSFile()
	{
		$style = $this->getStyle();

		$css_file_name = ilUtil::getWebspaceDir()."/css/style_".$this->getId().".css";
		$css_file = fopen($css_file_name, "w");

		foreach ($style as $tag)
		{
			fwrite ($css_file, $tag[0]["tag"].".ilc_".$tag[0]["class"]."\n");
			fwrite ($css_file, "{\n");

			foreach($tag as $par)
			{
				fwrite ($css_file, "\t".$par["parameter"].": ".$par["value"].";\n");
			}
			fwrite ($css_file, "}\n");
			fwrite ($css_file, "\n");
		}
		fclose($css_file);
	}

	function update()
	{
		parent::update();
		$this->read();				// this could be done better
		$this->writeCSSFile();
	}

	function updateStyleParameter($a_id, $a_value)
	{
		$q = "UPDATE style_parameter SET VALUE='".$a_value."' WHERE id = '".$a_id."'";
		$style_set = $this->ilias->db->query($q);
	}

	/**
	* todo: bad style! should return array of objects, not multi-dim-arrays
	*/
	function getStyle()
	{
		return $this->style;
	}

	function getAvailableTags()
	{
		$tags = array("a.FootnoteLink", "a.FootnoteLink:hover", "a.IntLink", "a.IntLink:hover",
			"div.Footnote", "div.LMNavigation", "div.Page", "div.PageTitle", "span.Comment",
			"span.Emph", "span.Quotation", "span.Strong",
			"td.Cell1", "td.Cell2", "td.Cell3", "td.Cell4",
			"p.Standard", "p.List", "p.Headline", "p.Example", "p.Citation", "p.Mnemonic",
			"p.Additional");

		return $tags;
	}

	function getAvailableParameters()
	{
		$pars = array(
			"font-family" => array(),
			"font-style" => array("italic", "oblique", "normal"),
			"font-variant" => array("small-caps", "normal"),
			"font-weight" => array("bold", "normal", "bolder", "lighter"),
			"font-stretch" => array("wider", "narrower", "condensed", "semi-condensed",
					"extra-condensed", "ultra-condensed", "expanded", "semi-expanded",
					"extra-expanded", "ultra-expanded", "normal"),
			"word-spacing" => array(),
			"letter-spacing" => array(),
			"text-decoration" => array("underline", "overline", "line-through", "blink", "none"),
			"text-transform" => array("capitalize", "uppercase", "lowercase", "none"),
			"color" => array(),

			"text-indent" => array(),
			"line-height" => array(),
			"vertival-align" => array("top", "middle", "bottom", "baseline", "sub", "super",
				"text-top", "text-bottom"),
			"text-align" => array("left", "center", "right", "justify"),
			"white-space" => array("normal", "pre", "nowrap"),

			"margin" => array(),
			"margin-top" => array(),
			"margin-bottom" => array(),
			"margin-left" => array(),
			"margin-right" => array(),

			"padding" => array(),
			"padding-top" => array(),
			"padding-bottom" => array(),
			"padding-left" => array(),
			"padding-right" => array(),

			"border-width" => array(),
			"border-width-top" => array(),
			"border-width-bottom" => array(),
			"border-width-left" => array(),
			"border-width-right" => array(),

			"border-color" => array(),
			"border-top-color" => array(),
			"border-bottom-color" => array(),
			"border-left-color" => array(),
			"border-right-color" => array(),

			"border-style" => array("none", "hidden", "dotted", "dashed", "solid", "double",
				"groove", "ridge", "inset", "outset"),
			"border-top-style" => array("none", "hidden", "dotted", "dashed", "solid", "double",
				"groove", "ridge", "inset", "outset"),
			"border-bottom-style" => array("none", "hidden", "dotted", "dashed", "solid", "double",
				"groove", "ridge", "inset", "outset"),
			"border-left-style" => array("none", "hidden", "dotted", "dashed", "solid", "double",
				"groove", "ridge", "inset", "outset"),
			"border-right-style" => array("none", "hidden", "dotted", "dashed", "solid", "double",
				"groove", "ridge", "inset", "outset"),

			"background-color" => array(),
			"background-image" => array(),
			"background-repeat" => array("repeat", "repeat-x", "repeat-y", "no-repeat"),
			"background-attachment" => array("fixed", "scroll"),
			"background-position" => array("top", "center", "middle", "bottom", "left", "right"),

			"cursor" => array("auto", "default", "crosshair", "pointer", "move",
				"n-resize", "ne-resize", "e-resize", "se-resize", "s-resize", "sw-resize",
				"w-resize", "nw-resize", "text", "wait", "help"),
		);

		return $pars;
	}

} // END class.ilObjStyleSheet
?>
