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


/**
* Class ilGlossaryTerm
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilGlossaryTerm
{
	var $ilias;
	var $lng;
	var $tpl;

	var $id;
	var $glossary;
	var $term;
	var $language;
	var $glo_id;
	var $import_id;

	/**
	* Constructor
	* @access	public
	*/
	function ilGlossaryTerm($a_id = 0)
	{
		global $lng, $ilias, $tpl;

		$this->lng =& $lng;
		$this->ilias =& $ilias;
		$this->tpl =& $tpl;

		$this->id = $a_id;
		$this->type = "term";
		if ($a_id != 0)
		{
			$this->read();
		}
	}

	/**
	* read glossary term data
	*/
	function read()
	{
		$q = "SELECT * FROM glossary_term WHERE id = '".$this->id."'";
		$term_set = $this->ilias->db->query($q);
		$term_rec = $term_set->fetchRow(DB_FETCHMODE_ASSOC);

		$this->setTerm($term_rec["term"]);
		$this->setImportId($term_rec["import_id"]);
		$this->setLanguage($term_rec["language"]);
		$this->setGlossaryId($term_rec["glo_id"]);

	}

	/**
	* get current term id for import id (static)
	*
	* @param	int		$a_import_id		import id
	*
	* @return	int		id
	*/
	function _getIdForImportId($a_import_id)
	{
		$q = "SELECT * FROM glossary_term WHERE import_id = '".$a_import_id."'".
			" ORDER BY create_date DESC LIMIT 1";
		$term_set = $this->ilias->db->query($q);
		if ($term_rec = $term_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			return $term_rec["id"];
		}
		else
		{
			return 0;
		}
	}

	/**
	* set glossary term id (= glossary item id)
	*
	* @param	int		$a_id		glossary term id
	*/
	function setId($a_id)
	{
		$this->id = $a_id;
	}


	/**
	* get term id (= glossary item id)
	*
	* @return	int		glossary term id
	*/
	function getId()
	{
		return $this->id;
	}

	/**
	* set glossary object
	*
	* @param	object		$a_glossary		glossary object
	*/
	function setGlossary(&$a_glossary)
	{
		$this->glossary =& $a_glossary;
		$this->setGlossaryId($a_glossary->getId());
	}


	/**
	* set glossary id
	*
	* @param	int		$a_glo_id		glossary id
	*/
	function setGlossaryId($a_glo_id)
	{
		$this->glo_id = $a_glo_id;
	}


	/**
	* get glossary id
	*
	* @return	int		glossary id
	*/
	function getGlossaryId()
	{
		return $this->glo_id;
	}


	/**
	* set term
	*
	* @param	string		$a_term		term
	*/
	function setTerm($a_term)
	{
		$this->term = $a_term;
	}


	/**
	* get term
	*
	* @return	string		term
	*/
	function getTerm()
	{
		return $this->term;
	}


	/**
	* set language
	*
	* @param	string		$a_language		two letter language code
	*/
	function setLanguage($a_language)
	{
		$this->language = $a_language;
	}

	/**
	* get language
	* @return	string		two letter language code
	*/
	function getLanguage()
	{
		return $this->language;
	}


	/**
	* set import id
	*/
	function setImportId($a_import_id)
	{
		$this->import_id = $a_import_id;
	}


	/**
	* get import id
	*/
	function getImportId()
	{
		return $this->import_id;
	}


	/**
	* create new glossary term
	*/
	function create()
	{
		$q = "INSERT INTO glossary_term (glo_id, term, language, import_id, create_date, last_update)".
			" VALUES ('".$this->getGlossaryId()."', '".ilUtil::prepareDBString($this->term).
			"', '".$this->language."','".$this->getImportId()."',now(), now())";
		$this->ilias->db->query($q);
		$this->setId($this->ilias->db->getLastInsertId());
	}


	/**
	* delete glossary term (and all its definition objects)
	*/
	function delete()
	{
		require_once("content/classes/class.ilGlossaryDefinition.php");
		$defs = ilGlossaryDefinition::getDefinitionList($this->getId());
		foreach($defs as $def)
		{
			$def_obj =& new ilGlossaryDefinition($def["id"]);
			$def_obj->delete();
		}
		$q = "DELETE FROM glossary_term ".
			" WHERE id = '".$this->getId()."'";
		$this->ilias->db->query($q);
	}


	/**
	* update glossary term
	*/
	function update()
	{
		$q = "UPDATE glossary_term SET ".
			" glo_id = '".$this->getGlossaryId()."', ".
			" term = '".ilUtil::prepareDBString($this->getTerm())."', ".
			" import_id = '".$this->getImportId()."', ".
			" language = '".$this->getLanguage()."', ".
			" last_update = now() ".
			" WHERE id = '".$this->getId()."'";
		$this->ilias->db->query($q);
	}


	/**
	* static
	*/
	function getTermList($a_glo_id)
	{
		$terms = array();
		$q = "SELECT * FROM glossary_term WHERE glo_id ='".$a_glo_id."' ORDER BY language, term";
		$term_set = $this->ilias->db->query($q);
		while ($term_rec = $term_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$terms[] = array("term" => $term_rec["term"],
				"language" => $term_rec["language"], "id" => $term_rec["id"]);
		}
		return $terms;
	}

	/**
	* export xml
	*/
	function exportXML(&$a_xml_writer, $a_inst)
	{
		//include_once("content/classes/class..php");

		$attrs = array();
		$attrs["Language"] = $this->getLanguage();
		$attrs["Id"] = "il_".IL_INST_ID."_git_".$this->getId();
		$a_xml_writer->xmlStartTag("GlossaryItem", $attrs);

		$attrs = array();
		$a_xml_writer->xmlElement("GlossaryTerm", $attrs, $this->getTerm());

		$defs = ilGlossaryDefinition::getDefinitionList($this->getId());

		foreach($defs as $def)
		{
			$definition = new ilGlossaryDefinition($def["id"]);
			$definition->exportXML($a_xml_writer, $a_inst);
		}

		$a_xml_writer->xmlEndTag("GlossaryItem");
	}

} // END class ilGlossaryTerm

?>
