<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Export/interfaces/interface.ilXmlExporter.php");

/**
 * Export2 class for media pools
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id: $
 * @ingroup ModulesMediaPool
 */
class ilMediaPoolExport2 implements ilXmlExporter
{
	private $ds;

	/**
	 * Constructor
	 */
	function __construct()
	{
		include_once("./Modules/MediaPool/classes/class.ilMediaPoolDataSet.php");
		$this->ds = new ilMediaPoolDataSet();
	}

	/**
	 * Get export sequence
	 *
	 * @param
	 * @return
	 */
	function getXmlExportHeadDependencies($a_target_release, $a_id)
	{
		include_once("./Modules/MediaPool/classes/class.ilObjMediaPool.php");
		$mob_ids = ilObjMediaPool::getAllMobIds($a_id);

		return array (
			array(
				"component" => "Services/MediaObjects",
				"exp_class" => "ilMediaObjectExporter",
				"entity" => "mob",
				"ids" => $mob_ids)
//			,
//			array(
//				"component" => "Modules/MediaPool",
//				"exp_class" => "MediaPoolDataSet",
//				"entity" => "mep",
//				"ids" => $a_id)
			);
	}

	public function getXmlExportTailDependencies($a_target_release, $a_id)
	{
		return array();
	}

	public function setExportDirectories($a_dir_relative, $a_dir_absolute)
	{
		$this->ds->setExportDirectories($a_dir_relative, $a_dir_absolute);
	}

	public function getXmlRepresentation($a_entity, $a_target_release, $a_ids)
	{
		return $this->ds->getXmlRepresentation($a_entity, $a_target_release, $a_ids);
	}
}

?>