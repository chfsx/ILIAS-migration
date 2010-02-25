<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Export/interfaces/interface.ilExport2Int.php");

/**
 * Export2 class for media pools
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id: $
 * @ingroup ModulesMediaPool
 */
class ilMediaPoolExport2 implements ilExport2Int
{
	/**
	 * Get export sequence
	 *
	 * @param
	 * @return
	 */
	function getXmlExportSequence($a_target_release, $a_id)
	{
		include_once("./Modules/MediaPool/classes/class.ilObjMediaPool.php");
		$mob_ids = ilObjMediaPool::getAllMobIds($a_id);

		return array (
			array(
				"component" => "Services/MediaObjects",
				"ds_class" => "MediaObjectDataSet",
				"entity" => "mob",
				"ids" => $mob_ids),
			array(
				"component" => "Modules/MediaPool",
				"ds_class" => "MediaPoolDataSet",
				"entity" => "mep",
				"ids" => $a_id)
			);
	}
}

?>