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

require_once "./survey/classes/class.SurveyQuestion.php";

define("ORDINAL_QUESTION_IDENTIFIER", "Ordinal Question");

/**
* Ordinal survey question
*
* The SurveyOrdinalQuestion class defines and encapsulates basic methods and attributes
* for ordinal survey question types.
*
* @author		Helmut Schottmüller <hschottm@tzi.de>
* @version	$Id$
* @module   class.SurveyOrdinalQuestion.php
* @modulegroup   Survey
*/
class SurveyOrdinalQuestion extends SurveyQuestion {
/**
* Categories contained in this question
*
* Categories contained in this question
*
* @var array
*/
  var $categories;

/**
* SurveyOrdinalQuestion constructor
*
* The constructor takes possible arguments an creates an instance of the SurveyOrdinalQuestion object.
*
* @param string $title A title string to describe the question
* @param string $description A description string to describe the question
* @param string $author A string containing the name of the questions author
* @param integer $owner A numerical ID to identify the owner/creator
* @access public
*/
  function SurveyOrdinalQuestion(
    $title = "",
    $description = "",
    $author = "",
		$questiontext = "",
    $owner = -1
  )

  {
		$this->SurveyQuestion($title, $description, $author, $questiontext, $owner);
		$this->categories = array();
	}
	
/**
* Returns the number of categories contained in that question
*
* Returns the number of categories contained in that question
*
* @return integer The number of contained categories
* @access public
* @see $categories
*/
	function getCategoryCount() 
	{
		return count($this->categories);
	}
	
/**
* Adds a category to the question at a given index
*
* Adds a category to the question at a given index
*
* @param string $categoryname The name of the category
* @param integer $index The index of the category
* @access public
* @see $categories
*/
	function addCategoryWithIndex($categoryname, $index) 
	{
		$this->categories[$index] = $categoryname;
	}

/**
* Adds a category to the question
*
* Adds a category to the question
*
* @param integer $categoryname The name of the category
* @access public
* @see $categories
*/
	function addCategory($categoryname) 
	{
		array_push($this->categories, $categoryname);
	}
	
/**
* Adds a category array to the question
*
* Adds a category array to the question
*
* @param array $categories An array with categories
* @access public
* @see $categories
*/
	function addCategoryArray($categories) 
	{
		$this->categories = array_merge($this->categories, $categories);
	}
	
/**
* Removes a category from the list of categories
*
* Removes a category from the list of categories
*
* @param integer $index The index of the category to be removed
* @access public
* @see $categories
*/
	function removeCategory($index)
	{
		array_splice($this->categories, $index, 1);
	}

/**
* Removes many categories from the list of categories
*
* Removes many categories from the list of categories
*
* @param array $array An array containing the index positions of the categories to be removed
* @access public
* @see $categories
*/
	function removeCategories($array)
	{
		foreach ($array as $index)
		{
			unset($this->categories[$index]);
		}
		$this->categories = array_values($this->categories);
	}

/**
* Removes a category from the list of categories
*
* Removes a category from the list of categories
*
* @param string $name The name of the category to be removed
* @access public
* @see $categories
*/
	function removeCategoryWithName($name)
	{
		$index = array_search($name, $this->categories);
		$this->removeCategory($index);
	}
	
/**
* Returns the name of a category for a given index
*
* Returns the name of a category for a given index
*
* @param integer $index The index of the category
* @result string Category name
* @access public
* @see $categories
*/
	function getCategory($index)
	{
		return $this->categories[$index];
	}
	
/**
* Empties the categories list
*
* Empties the categories list
*
* @access public
* @see $categories
*/
	function flushCategories() 
	{
		$this->categories = array();
	}
	
/**
* Gets the available phrases from the database
*
* Gets the available phrases from the database
*
* @param boolean $useronly Returns only the user defined phrases if set to true. The default is false.
* @result array All available phrases as key/value pairs
* @access public
*/
	function &getAvailablePhrases($useronly = 0)
	{
		global $ilUser;
		
		$phrases = array();
    $query = sprintf("SELECT * FROM survey_phrase WHERE defaultvalue = '1' OR owner_fi = %s ORDER BY title",
      $this->ilias->db->quote($ilUser->id)
    );
    $result = $this->ilias->db->query($query);
		while ($row = $result->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if (($row->defaultvalue == 1) and ($row->owner_fi == 0))
			{
				if (!$useronly)
				{
					$phrases[$row->phrase_id] = array(
						"title" => $this->lng->txt($row->title),
						"owner" => $row->owner_fi
					);
				}
			}
			else
			{
				$phrases[$row->phrase_id] = array(
					"title" => $row->title,
					"owner" => $row->owner_fi
				);
			}
		}
		return $phrases;
	}
	
/**
* Gets the available categories for a given phrase
*
* Gets the available categories for a given phrase
*
* @param integer $phrase_id The database id of the given phrase
* @result array All available categories
* @access public
*/
	function &getCategoriesForPhrase($phrase_id)
	{
		$categories = array();
    $query = sprintf("SELECT survey_category.* FROM survey_category, survey_phrase_category WHERE survey_phrase_category.category_fi = survey_category.category_id AND survey_phrase_category.phrase_fi = %s ORDER BY survey_phrase_category.sequence",
      $this->ilias->db->quote($phrase_id)
    );
    $result = $this->ilias->db->query($query);
		while ($row = $result->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if (($row->defaultvalue == 1) and ($row->owner_fi == 0))
			{
				$categories[$row->category_id] = $this->lng->txt($row->title);
			}
			else
			{
				$categories[$row->category_id] = $row->title;
			}
		}
		return $categories;
	}
	
/**
* Adds a phrase to the question
*
* Adds a phrase to the question
*
* @param integer $phrase_id The database id of the given phrase
* @access public
*/
	function addPhrase($phrase_id)
	{
		global $ilUser;
		
    $query = sprintf("SELECT survey_category.* FROM survey_category, survey_phrase_category WHERE survey_phrase_category.category_fi = survey_category.category_id AND survey_phrase_category.phrase_fi = %s AND (survey_category.owner_fi = 0 OR survey_category.owner_fi = %s) ORDER BY survey_phrase_category.sequence",
      $this->ilias->db->quote($phrase_id),
			$this->ilias->db->quote($ilUser->id)
    );
    $result = $this->ilias->db->query($query);
		while ($row = $result->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if (($row->defaultvalue == 1) and ($row->owner_fi == 0))
			{
				array_push($this->categories, $this->lng->txt($row->title));
			}
			else
			{
				array_push($this->categories, $row->title);;
			}
		}
	}
	
/**
* Loads a SurveyOrdinalQuestion object from the database
*
* Loads a SurveyOrdinalQuestion object from the database
*
* @param integer $id The database id of the ordinal survey question
* @access public
*/
  function loadFromDb($id) {
    $query = sprintf("SELECT * FROM survey_question WHERE question_id = %s",
      $this->ilias->db->quote($id)
    );
    $result = $this->ilias->db->query($query);
    if (strcmp(strtolower(get_class($result)), db_result) == 0) {
      if ($result->numRows() == 1) {
        $data = $result->fetchRow(DB_FETCHMODE_OBJECT);
        $this->id = $data->question_id;
        $this->title = $data->title;
        $this->description = $data->description;
        $this->obj_id = $data->obj_fi;
				$this->orientation = $data->orientation;
        $this->author = $data->author;
        $this->owner = $data->owner_fi;
        $this->questiontext = $data->questiontext;
				$this->obligatory = $data->obligatory;
        $this->complete = $data->complete;
				$this->original_id = $data->original_id;
      }
      // loads materials uris from database
      $this->loadMaterialFromDb($id);

			$this->flushCategories();
      $query = sprintf("SELECT survey_variable.*, survey_category.title FROM survey_variable, survey_category WHERE survey_variable.question_fi = %s AND survey_variable.category_fi = survey_category.category_id ORDER BY sequence ASC",
        $this->ilias->db->quote($id)
      );
      $result = $this->ilias->db->query($query);
      if (strcmp(strtolower(get_class($result)), db_result) == 0) {
        while ($data = $result->fetchRow(DB_FETCHMODE_OBJECT)) {
          array_push($this->categories, $data->title);
        }
      }
    }
		parent::loadFromDb($id);
  }

/**
* Returns true if the question is complete for use
*
* Returns true if the question is complete for use
*
* @result boolean True if the question is complete for use, otherwise false
* @access public
*/
	function isComplete()
	{
		if ($this->title and $this->author and $this->questiontext and count($this->categories))
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}
	
/**
* Saves a SurveyOrdinalQuestion object to a database
*
* Saves a SurveyOrdinalQuestion object to a database
*
* @access public
*/
  function saveToDb($original_id = "")
  {
		$complete = 0;
		if ($this->isComplete()) {
			$complete = 1;
		}
		if ($original_id)
		{
			$original_id = $this->ilias->db->quote($original_id);
		}
		else
		{
			$original_id = "NULL";
		}
    if ($this->id == -1) {
      // Write new dataset
      $now = getdate();
      $created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
      $query = sprintf("INSERT INTO survey_question (question_id, subtype, questiontype_fi, obj_fi, owner_fi, title, description, author, questiontext, obligatory, orientation, complete, created, original_id, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NULL)",
				$this->ilias->db->quote("0"),
        $this->ilias->db->quote("2"),
        $this->ilias->db->quote($this->obj_id),
        $this->ilias->db->quote($this->owner),
        $this->ilias->db->quote($this->title),
        $this->ilias->db->quote($this->description),
        $this->ilias->db->quote($this->author),
        $this->ilias->db->quote($this->questiontext),
				$this->ilias->db->quote(sprintf("%d", $this->obligatory)),
				$this->ilias->db->quote(sprintf("%d", $this->orientation)),
				$this->ilias->db->quote("$complete"),
        $this->ilias->db->quote($created),
				$original_id
      );
      $result = $this->ilias->db->query($query);
      if ($result == DB_OK) {
        $this->id = $this->ilias->db->getLastInsertId();
      }
    } else {
      // update existing dataset
      $query = sprintf("UPDATE survey_question SET title = %s, subtype = %s, description = %s, author = %s, questiontext = %s, obligatory = %s, orientation = %s, complete = %s WHERE question_id = %s",
        $this->ilias->db->quote($this->title),
				$this->ilias->db->quote("0"),
        $this->ilias->db->quote($this->description),
        $this->ilias->db->quote($this->author),
        $this->ilias->db->quote($this->questiontext),
				$this->ilias->db->quote(sprintf("%d", $this->obligatory)),
				$this->ilias->db->quote(sprintf("%d", $this->orientation)),
				$this->ilias->db->quote("$complete"),
        $this->ilias->db->quote($this->id)
      );
      $result = $this->ilias->db->query($query);
    }
    if ($result == DB_OK) {
      // saving material uris in the database
      $this->saveMaterialsToDb();

      // save categories
			
			// delete existing category relations
      $query = sprintf("DELETE FROM survey_variable WHERE question_fi = %s",
        $this->ilias->db->quote($this->id)
      );
      $result = $this->ilias->db->query($query);
      // create new category relations
      foreach ($this->categories as $key => $value) {
				$category_id = $this->saveCategoryToDb($value);
        $query = sprintf("INSERT INTO survey_variable (variable_id, category_fi, question_fi, value1, sequence, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, NULL)",
					$this->ilias->db->quote($category_id),
          $this->ilias->db->quote($this->id),
          $this->ilias->db->quote(($key + 1)),
          $this->ilias->db->quote($key)
        );
        $answer_result = $this->ilias->db->query($query);
      }
    }
		parent::saveToDb($original_id);
  }

	/**
	* Imports a question from XML
	*
	* Sets the attributes of the question from the XML text passed
	* as argument
	*
	* @return boolean True, if the import succeeds, false otherwise
	* @access public
	*/
	function from_xml($xml_text)
	{
		$result = false;
		if (!empty($this->domxml))
		{
			$this->domxml->free();
		}
		$xml_text = preg_replace("/>\s*?</", "><", $xml_text);
		$this->domxml = domxml_open_mem($xml_text);
		if (!empty($this->domxml))
		{
			$root = $this->domxml->document_element();
			$item = $root->first_child();
			$this->setTitle($item->get_attribute("title"));
			$this->gaps = array();
			$itemnodes = $item->child_nodes();
			foreach ($itemnodes as $index => $node)
			{
				switch ($node->node_name())
				{
					case "qticomment":
						$comment = $node->get_content();
						if (strpos($comment, "ILIAS Version=") !== false)
						{
						}
						elseif (strpos($comment, "Questiontype=") !== false)
						{
						}
						elseif (strpos($comment, "Author=") !== false)
						{
							$comment = str_replace("Author=", "", $comment);
							$this->setAuthor($comment);
						}
						else
						{
							$this->setDescription($comment);
						}
						break;
					case "itemmetadata":
						$qtimetadata = $node->first_child();
						$metadata_fields = $qtimetadata->child_nodes();
						foreach ($metadata_fields as $index => $metadata_field)
						{
							$fieldlabel = $metadata_field->first_child();
							$fieldentry = $fieldlabel->next_sibling();
							switch ($fieldlabel->get_content())
							{
								case "obligatory":
									$this->setObligatory($fieldentry->get_content());
									break;
							}
						}
						break;
					case "presentation":
						$flow = $node->first_child();
						$flownodes = $flow->child_nodes();
						foreach ($flownodes as $idx => $flownode)
						{
							if (strcmp($flownode->node_name(), "material") == 0)
							{
								$mattext = $flownode->first_child();
								$this->setQuestiontext($mattext->get_content());
							}
							elseif (strcmp($flownode->node_name(), "response_lid") == 0)
							{
								$ident = $flownode->get_attribute("ident");
								$shuffle = "";
								$render_choice = $flownode->first_child();
								if (strcmp($render_choice->node_name(), "render_choice") == 0)
								{
									$labels = $render_choice->child_nodes();
									foreach ($labels as $lidx => $response_label)
									{
										$material = $response_label->first_child();
										$mattext = $material->first_child();
										$shuf = 0;
										$this->addCategoryWithIndex($mattext->get_content(), $response_label->get_attribute("ident"));
									}
								}
							}
						}
						break;
				}
			}
			$result = true;
		}
		return $result;
	}

	/**
	* Returns a QTI xml representation of the question
	*
	* Returns a QTI xml representation of the question and sets the internal
	* domxml variable with the DOM XML representation of the QTI xml representation
	*
	* @return string The QTI xml representation of the question
	* @access public
	*/
	function to_xml($a_include_header = true, $obligatory_state = "")
	{
		if (!empty($this->domxml))
		{
			$this->domxml->free();
		}
		$xml_header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		$xml_header .= "<questestinterop></questestinterop>\n";
		$this->domxml = domxml_open_mem($xml_header);
		$root = $this->domxml->document_element();
		// qti ident
		$qtiIdent = $this->domxml->create_element("item");
		$qtiIdent->set_attribute("ident", $this->getId());
		$qtiIdent->set_attribute("title", $this->getTitle());
		$root->append_child($qtiIdent);
		// add qti comment
		$qtiComment = $this->domxml->create_element("qticomment");
		$qtiCommentText = $this->domxml->create_text_node($this->getDescription());
		$qtiComment->append_child($qtiCommentText);
		$qtiIdent->append_child($qtiComment);
		$qtiComment = $this->domxml->create_element("qticomment");
		$qtiCommentText = $this->domxml->create_text_node("ILIAS Version=".$this->ilias->getSetting("ilias_version"));
		$qtiComment->append_child($qtiCommentText);
		$qtiIdent->append_child($qtiComment);
		$qtiComment = $this->domxml->create_element("qticomment");
		$qtiCommentText = $this->domxml->create_text_node("Questiontype=".ORDINAL_QUESTION_IDENTIFIER);
		$qtiComment->append_child($qtiCommentText);
		$qtiIdent->append_child($qtiComment);
		$qtiComment = $this->domxml->create_element("qticomment");
		$qtiCommentText = $this->domxml->create_text_node("Author=".$this->getAuthor());
		$qtiComment->append_child($qtiCommentText);
		$qtiIdent->append_child($qtiComment);

		$qtiItemMetadata = $this->domxml->create_element("itemmetadata");
		$qtiMetadata = $this->domxml->create_element("qtimetadata");
		// obligatory state
		$qtiMetadatafield = $this->domxml->create_element("qtimetadatafield");
		$qtiFieldLabel = $this->domxml->create_element("fieldlabel");
		$qtiFieldLabelText = $this->domxml->create_text_node("obligatory");
		$qtiFieldLabel->append_child($qtiFieldLabelText);
		$qtiFieldEntry = $this->domxml->create_element("fieldentry");
		if (strcmp($obligatory_state, "") != 0)
		{
			$this->setObligatory($obligatory_state);
		}
		$qtiFieldEntryText = $this->domxml->create_text_node(sprintf("%d", $this->getObligatory()));
		$qtiFieldEntry->append_child($qtiFieldEntryText);
		$qtiMetadatafield->append_child($qtiFieldLabel);
		$qtiMetadatafield->append_child($qtiFieldEntry);
		$qtiMetadata->append_child($qtiMetadatafield);
		$qtiItemMetadata->append_child($qtiMetadata);
		$qtiIdent->append_child($qtiItemMetadata);

		// PART I: qti presentation
		$qtiPresentation = $this->domxml->create_element("presentation");
		$qtiPresentation->set_attribute("label", $this->getTitle());
		// add flow to presentation
		$qtiFlow = $this->domxml->create_element("flow");
		// add material with question text to presentation
		$qtiMaterial = $this->domxml->create_element("material");
		$qtiMatText = $this->domxml->create_element("mattext");
		$qtiMatTextText = $this->domxml->create_text_node($this->getQuestiontext());
		$qtiMatText->append_child($qtiMatTextText);
		$qtiMaterial->append_child($qtiMatText);
		$qtiFlow->append_child($qtiMaterial);
		// add answers to presentation
		$qtiResponseLid = $this->domxml->create_element("response_lid");
		$qtiResponseLid->set_attribute("ident", "MCSR");
		$qtiResponseLid->set_attribute("rcardinality", "Single");
		$qtiRenderChoice = $this->domxml->create_element("render_choice");
		$qtiRenderChoice->set_attribute("shuffle", "no");

		// add categories
		for ($index = 0; $index < $this->getCategoryCount(); $index++)
		{
			$category = $this->getCategory($index);
			$qtiResponseLabel = $this->domxml->create_element("response_label");
			$qtiResponseLabel->set_attribute("ident", $index);
			$qtiMaterial = $this->domxml->create_element("material");
			$qtiMatText = $this->domxml->create_element("mattext");
			$qtiMatTextText = $this->domxml->create_text_node($category);
			$qtiMatText->append_child($qtiMatTextText);
			$qtiMaterial->append_child($qtiMatText);
			$qtiResponseLabel->append_child($qtiMaterial);
			$qtiRenderChoice->append_child($qtiResponseLabel);
		}
		$qtiResponseLid->append_child($qtiRenderChoice);
		$qtiFlow->append_child($qtiResponseLid);
		$qtiPresentation->append_child($qtiFlow);
		$qtiIdent->append_child($qtiPresentation);
		$xml = $this->domxml->dump_mem(true);
		if (!$a_include_header)
		{
			$pos = strpos($xml, "?>");
			$xml = substr($xml, $pos + 2);
		}
//echo htmlentities($xml);
		return $xml;
	}

	function syncWithOriginal()
	{
		if ($this->original_id)
		{
			$complete = 0;
			if ($this->isComplete()) {
				$complete = 1;
			}
			$query = sprintf("UPDATE survey_question SET title = %s, subtype = %s, description = %s, author = %s, questiontext = %s, obligatory = %s, complete = %s WHERE question_id = %s",
				$this->ilias->db->quote($this->title . ""),
				$this->ilias->db->quote("0"),
				$this->ilias->db->quote($this->description . ""),
				$this->ilias->db->quote($this->author . ""),
				$this->ilias->db->quote($this->questiontext . ""),
				$this->ilias->db->quote(sprintf("%d", $this->obligatory) . ""),
				$this->ilias->db->quote($complete . ""),
				$this->ilias->db->quote($this->original_id . "")
			);
			$result = $this->ilias->db->query($query);
			if ($result == DB_OK) {
				// save categories
				
				// delete existing category relations
				$query = sprintf("DELETE FROM survey_variable WHERE question_fi = %s",
					$this->ilias->db->quote($this->original_id . "")
				);
				$result = $this->ilias->db->query($query);
				// create new category relations
				foreach ($this->categories as $key => $value) {
					$category_id = $this->saveCategoryToDb($value);
					$query = sprintf("INSERT INTO survey_variable (variable_id, category_fi, question_fi, value1, sequence, TIMESTAMP) VALUES (NULL, %s, %s, %s, %s, NULL)",
						$this->ilias->db->quote($category_id . ""),
						$this->ilias->db->quote($this->original_id . ""),
						$this->ilias->db->quote(($key + 1) . ""),
						$this->ilias->db->quote($key . "")
					);
					$answer_result = $this->ilias->db->query($query);
				}
			}
		}
		parent::syncWithOriginal();
	}

}
?>
