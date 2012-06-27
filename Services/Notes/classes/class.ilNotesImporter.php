<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Export/classes/class.ilXmlImporter.php");

/**
 * Importer class for notes data
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 * @ingroup ServicesNotes
 */
class ilNotesImporter extends ilXmlImporter
{

	/**
	 * Initialisation
	 */
	function init()
	{
		include_once("./Services/Notes/classes/class.ilNotesDataSet.php");
		$this->ds = new ilNotesDataSet();
		$this->ds->setDSPrefix("ds");
	}


	/**
	 * Import XML
	 *
	 * @param
	 * @return
	 */
	function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping)
	{
		include_once("./Services/DataSet/classes/class.ilDataSetImportParser.php");
		$parser = new ilDataSetImportParser($a_entity, $this->getSchemaVersion(),
			$a_xml, $this->ds, $a_mapping);
	}

	
}

?>