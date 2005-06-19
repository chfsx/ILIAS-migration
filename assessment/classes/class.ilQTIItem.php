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

include_once "./assessment/classes/class.ilQTIResponse.php";

	define ("QT_UNKNOWN", 0);
	define ("QT_MULTIPLE_CHOICE_SR", 1);
	define ("QT_MULTIPLE_CHOICE_MR", 2);
	define ("QT_CLOZE", 3);
	define ("QT_MATCHING", 4);
	define ("QT_ORDERING", 5);
	define ("QT_IMAGEMAP", 6);
	define ("QT_JAVAAPPLET", 7);
	define ("QT_TEXT", 8);

/**
* QTI item class
*
* @author Helmut Schottmüller <hschottm@gmx.de>
* @version $Id$
*
* @package assessment
*/
class ilQTIItem
{
	var $ident;
	var $title;
	var $maxattempts;
	var $label;
	var $xmllang;
	
	var $comment;
	var $ilias_version;
	var $author;
	var $questiontype;
	var $duration;
	var $questiontext;
	var $resprocessing;
	var $itemfeedback;
	var $presentation;
	var $presentationitem;
	var $suggested_solutions;
	
	function ilQTIItem()
	{
		$this->response = array();
		$this->resprocessing = array();
		$this->itemfeedback = array();
		$this->presentation = NULL;
		$this->presentationitem = array();
		$this->suggested_solutions = array();
	}
	
	function setIdent($a_ident)
	{
		$this->ident = $a_ident;
	}
	
	function getIdent()
	{
		return $this->ident;
	}
	
	function setTitle($a_title)
	{
		$this->title = $a_title;
	}
	
	function getTitle()
	{
		return $this->title;
	}
	
	function setComment($a_comment)
	{
		if (preg_match("/(.*?)\=(.*)/", $a_comment, $matches))
		{
			// special comments written by ILIAS
			switch ($matches[1])
			{
				case "ILIAS Version":
					$this->ilias_version = $matches[2];
					return;
					break;
				case "Questiontype":
					$this->questiontype = $matches[2];
					return;
					break;
				case "Author":
					$this->author = $matches[2];
					return;
					break;
			}
		}
		$this->comment = $a_comment;
	}
	
	function getComment()
	{
		return $this->comment;
	}
	
	function setDuration($a_duration)
	{
		if (preg_match("/P(\d+)Y(\d+)M(\d+)DT(\d+)H(\d+)M(\d+)S/", $a_duration, $matches))
		{
			$this->duration = array(
				"h" => $matches[4], 
				"m" => $matches[5], 
				"s" => $matches[6]
			);
		}
	}
	
	function getDuration()
	{
		return $this->duration;
	}
	
	function setQuestiontext($a_questiontext)
	{
		$this->questiontext = $a_questiontext;
	}
	
	function getQuestiontext()
	{
		return $this->questiontext;
	}
	
	function addResprocessing($a_resprocessing)
	{
		array_push($this->resprocessing, $a_resprocessing);
	}
	
	function addItemfeedback($a_itemfeedback)
	{
		array_push($this->itemfeedback, $a_itemfeedback);
	}
	
	function setMaxattempts($a_maxattempts)
	{
		$this->maxattempts = $a_maxattempts;
	}
	
	function getMaxattempts()
	{
		return $this->maxattempts;
	}
	
	function setLabel($a_label)
	{
		$this->label = $a_label;
	}
	
	function getLabel()
	{
		return $this->label;
	}
	
	function setXmllang($a_xmllang)
	{
		$this->xmllang = $a_xmllang;
	}
	
	function getXmllang()
	{
		return $this->xmllang;
	}
	
	function setPresentation($a_presentation)
	{
		$this->presentation = $a_presentation;
	}
	
	function getPresentation()
	{
		return $this->presentation;
	}
	
	function collectResponses()
	{
		$result = array();
		if ($this->presentation != NULL)
		{
		}
	}
	
	function getQuestiontype()
	{
	}
	
	function addPresentationitem($a_presentationitem)
	{
		array_push($this->presentationitem, $a_presentationitem);
	}

	function determineQuestionType()
	{
		if (!$this->presentation) return QT_UNKNOWN;
		foreach ($this->presentation->order as $entry)
		{
			switch ($entry["type"])
			{
				case "response":
					$response = $this->presentation->response[$entry["index"]];
					switch ($response->getResponsetype())
					{
						case RT_RESPONSE_LID:
							switch ($response->getRCardinality())
							{
								case R_CARDINALITY_ORDERED:
									return QT_ORDERING;
									break;
								case R_CARDINALITY_SINGLE:
									return QT_MULTIPLE_CHOICE_SR;
									break;
								case R_CARDINALITY_MULTIPLE:
									return QT_MULTIPLE_CHOICE_MR;
									break;
							}
							break;
						case RT_RESPONSE_XY:
							return QT_IMAGEMAP;
							break;
						case RT_RESPONSE_STR:
							return QT_CLOZE;
							break;
						case RT_RESPONSE_GRP:
							return QT_MATCHING;
							break;
						default:
							break;
					}
			}
		}
		return QT_UNKNOWN;
	}
	
	function setAuthor($a_author)
	{
		$this->author = $a_author;
	}
	
	function getAuthor()
	{
		return $this->author;
	}
	
	function addSuggestedSolution($a_solution)
	{
		array_push($this->suggested_solutions, $a_solution);
	}
}
?>
