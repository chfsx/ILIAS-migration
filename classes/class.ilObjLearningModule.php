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
* Class ilObjLearningModule
*
* @author Sascha Hofmann <shofmann@databay.de>
* $Id$
*
* @extends ilObject
* @package ilias-core
*/

require_once "classes/class.ilObject.php";
require_once "classes/class.ilMetaData.php";

class ilObjLearningModule extends ilObject
{
	var $lm_tree;
	var $meta_data;

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjLearningModule($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "lm";
		$this->ilObject($a_id,$a_call_by_reference);
	}

	function read()
	{
		parent::read();
		$this->lm_tree = new ilTree($this->getId());
		$this->lm_tree->setTableNames('lm_tree','lm_data');
		$this->lm_tree->setTreeTablePK("lm_id");

		$this->meta_data =& new ilMetaData("lm", $this->getId());
		//parent::read();
	}

	function getTitle()
	{
		return $this->meta_data->getTitle();
	}

	function assignMetaData(&$a_meta_data)
	{
		$this->meta_data =& $a_meta_data;
	}

	function &getMetaData()
	{
		return $this->meta_data;
	}


	function update()
	{
		$this->setTitle($this->meta_data->getTitle());
		$this->setDescription($this->meta_data->getDescription());
		$this->meta_data->update();
		parent::update();
	}


	/**
	* if implemented, this function should be called from an Out/GUI-Object
	*/
	function import()
	{
		// nothing to do. just display the dialogue in Out
		return;
	}


	/**
	* create new learning module object
	*
	*/
	function putInTree($a_parent)
	{
		global $tree;

		// put this object in tree under $a_parent
		parent::putInTree($a_parent);

		// make new tree for this object
		$tree->addTree($this->getId());
	}

	function getLayout()
	{
		// todo: make it real
		return "toc2win";
	}

	function &getLMTree()
	{
		return $this->lm_tree;
	}



	/**
	* uploads a complete LearningModule from a LO-XML file
	*
	* @access	public
	*/
	function upload($a_parse_mode, $a_file, $a_name)
	{
		require_once "classes/class.ilXML2SQL.php";
		require_once "classes/class.ilDOMXML.php";

		$source = $a_file;

		// create domxml-handler
		$domxml = new ilDOMXML();
echo "create domxml handler<br>";

		//get XML-file, parse and/or validate the document
		$file = $a_name;
		$root = $domxml->loadDocument(basename($source),dirname($source),$a_parse_mode);
echo "load Document<br>";

		// remove empty text nodes
		$domxml->trimDocument();
echo "trim Document<br>";

		$n = 0;
		$mapping = array();

		// Identify Leaf-LOS (LOs not containing other LOs)
		while (count($elements = $domxml->getElementsByTagname("LearningObject")) > 1)
		{
			// delete first element since this is always the root LearningObject
			array_shift($elements);
echo "Lead LOs identified<br>";
			foreach ($elements as $element)
			{
				if ($domxml->isLeafElement($element,"LearningObject",1))
				{
					$n++;

					$leaf_elements[] = $element;

echo "Copy LO to subtree<br>";
					// copy whole LearningObject to $subtree
					$subtree = $element->clone_node(true);
echo "Get previous and parent<br>";
					$prev_sibling = $element->previous_sibling();
					$parent = $element->parent_node();
echo "Remove LO from main file<br>";
					// remove the LearningObject from main file
					$element->unlink_node();

					// create a new domDocument containing the isolated LearningObject in $subtree
					$lo = new ilDOMXML();
					$node  = $lo->appendChild($subtree);

					// get LO informationen (title & description)
					$obj_data = $lo->getInfo();
					// get unique obj_id of LO
					require_once "classes/class.ilObjLearningObject.php";
					$loObj = new ilObjLearningObject();
					$loObj->setTitle($obj_data["title"]);
echo "LO Title:".$obj_data["title"].".<br>";
					$loObj->setDescription($obj_data["desc"]);
					$loObj->create();
					$lo_id = $loObj->getId();
					unset($loObj);
echo "LO created.<br>";
					// prepare LO for database insertion
					$lotree = $lo->buildTree();

					// create a reference in main file with global obj_id of inserted LO
					// DIRTY: save lm_id too for xsl linking
					$domxml->appendReferenceNodeForLO ($parent,$lo_id,$this->id,$prev_sibling);

					// write to file
//					$lo->domxml->doc->dump_file("c:/htdocs/ilias3/test2/".$lo_id.".xml");
					//echo "<b>LearningObject ".$n."</b><br/>";
					//echo "<pre>".htmlentities($lo->domxml->dumpDocument())."</pre>";

					// insert LO into lo_database
					$xml2sql = new ilXML2SQL($lotree,$lo_id);
					$xml2sql->insertDocument();

					//fetch internal element id, parent_id and save them to reconstruct tree later on
					$mapping[] = array ($lo_id => $lo->getReferences());
				}
			}
		} // END: while. Continue until only the root LO is left in main file
echo "After While Loop<br>";

		$n++;

		// write root LO to file (TESTING)
//		$domxml->doc->dump_file("c:/htdocs/ilias3/test2/root.xml");
		//echo "<b>LearningObject ".$n."</b><br/>";
		//echo "<pre>".htmlentities($domxml->dumpDocument())."</pre>";

		// insert the remaining root-LO into DB
		$lo = new ilDOMXML($domxml->doc);
		$obj_data = $lo->getInfo();
echo "Insert remaining root-LO into DB<br>";
var_dump($obj_data);
echo "<br>";

		require_once "classes/class.ilObjLearningObject.php";
		$loObj = new ilObjLearningObject();
		$loObj->setTitle($obj_data["title"]);
		$loObj->setDescription($obj_data["desc"]);
		$loObj->create();
		$lo_id = $loObj->getId();
		unset($loObj);

echo "Build Tree<br>";
		$lotree = $lo->buildTree();
		$xml2sql = new ilXML2SQL($lotree,$lo_id);
		$xml2sql->insertDocument();

		// copying file to server if document is valid (soon...)
		//move_uploaded_file($a_source,$path()."/".$a_obj_id."_".$a_name);
		$last[$lo_id] = $lo->getReferences();
		array_push($mapping,$last);

		// MOVE TO xml2sql class
		$xml2sql->insertStructureIntoTree(array_reverse($mapping),$this->id);

		// for output
		return $data;
	}


} // END class.LearningModuleObject
?>
