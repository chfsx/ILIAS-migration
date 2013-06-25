<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("class.ilCloudPluginGUI.php");

/**
 * Class ilCloudPluginListGUI
 *
 * Abstract class working as base for ilCloudPluginItemCreationListGUI and ilCloudPluginActionListGUI
 *
 * @author Timon Amstutz timon.amstutz@ilub.unibe.ch
 * @version $Id$
 * @ingroup ModulesCloud
 */
abstract class ilCloudPluginListGUI extends ilCloudPluginGUI
{
    /**
     * @var ilAdvancedSelectionListGUI
     */
    protected $selection_list = null;

    /**
     * @var ilcloudFileNode
     */
    protected $node = null;

    abstract protected function addSelectionListItemsBefore();
    abstract protected function addSelectionListItemsAfter();
}
?>
