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
   * class reading a glossary to transform it into a structure object
   *
   * @author Roland Kuestermann (rku@aifb.uni-karlsruhe.de)
   * @version $Id: class.ilSoapStructureReader.php,v 1.5 2006/05/23 23:09:06 hschottm Exp $
   *
   * @package ilias
   */

include_once "./webservice/soap/classes/class.ilSoapStructureReader.php";
include_once "./webservice/soap/classes/class.ilSoapStructureObjectFactory.php";
include_once "./content/classes/class.ilGlossaryDefinition.php";

class ilSoapGLOStructureReader extends ilSoapStructureReader
{

	function ilSoapGLOStructureReader ($object)
	{
		parent::ilSoapStructureReader($object);
	}

	function _parseStructure () {
	    /* @var $object ilObjGlossary */

	    $terms = $this->object->getTermlist();

	    foreach ($terms as $term)
		{

		    /* @var $termStructureObject ilSoapGLOTermStructureObject*/
		    $termStructureObject = ilSoapStructureObjectFactory::getInstance (
		      $term["id"], "git", $term["term"]);

		    $termStructureObject->setGlossaryRefId($this->object->getRefId());

		    $this->structureObject->addStructureObject($termStructureObject);

		    $defs = ilGlossaryDefinition::getDefinitionList($term["id"]);

		    foreach ($defs as $def) {
		        $defStructureObject = ilSoapStructureObjectFactory::getInstance(
		          $def["id"], "gdf",$def["short_text"]);

                $termStructureObject->addStructureObject($defStructureObject);
		    }

		   // print_r($defs);

		}


	}
}

?>