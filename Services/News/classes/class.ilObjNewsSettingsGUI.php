<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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
include_once("./classes/class.ilObjectGUI.php");


/**
* News Settings.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilObjNewsSettingsGUI: ilPermissionGUI
*
* @ingroup ServicesNews
*/
class ilObjNewsSettingsGUI extends ilObjectGUI
{
    private static $ERROR_MESSAGE;
	/**
	 * Contructor
	 *
	 * @access public
	 */
	public function __construct($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
	{
		$this->type = 'nwss';
		parent::ilObjectGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

		$this->lng->loadLanguageModule('news');
		$this->lng->loadLanguageModule('feed');

	}

	/**
	 * Execute command
	 *
	 * @access public
	 *
	 */
	public function executeCommand()
	{
		global $rbacsystem,$ilErr,$ilAccess;

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		$this->prepareOutput();

		if(!$ilAccess->checkAccess('read','',$this->object->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('no_permission'),$ilErr->WARNING);
		}

		switch($next_class)
		{
			case 'ilpermissiongui':
				$this->tabs_gui->setTabActive('perm_settings');
				include_once("./classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			default:
				if(!$cmd || $cmd == 'view')
				{
					$cmd = "editSettings";
				}

				$this->$cmd();
				break;
		}
		return true;
	}

	/**
	 * Get tabs
	 *
	 * @access public
	 *
	 */
	public function getAdminTabs()
	{
		global $rbacsystem, $ilAccess;

		if ($rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->tabs_gui->addTarget("news_edit_news_settings",
				$this->ctrl->getLinkTarget($this, "editSettings"),
				array("editSettings", "view"));
		}

		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$this->tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass('ilpermissiongui',"perm"),
				array(),'ilpermissiongui');
		}
	}

	/**
	* Edit news settings.
	*/
	public function editSettings()
	{
		global $ilCtrl, $lng, $ilSetting;
		
		$news_set = new ilSetting("news");
		$feed_set = new ilSetting("feed");
		
		$enable_internal_news = $ilSetting->get("block_activated_news");
		$enable_internal_rss = $news_set->get("enable_rss_for_internal");
		$disable_repository_feeds = $feed_set->get("disable_rep_feeds");
		$nr_personal_desktop_feeds = $feed_set->get("nr_personal_desktop_feeds");
		
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->setTitle($lng->txt("news_settings"));
		
		// Enable internal news
		$cb_prop = new ilCheckboxInputGUI($lng->txt("news_enable_internal_news"),
			"enable_internal_news");
		$cb_prop->setValue("1");
		$cb_prop->setInfo($lng->txt("news_enable_internal_news_info"));
		$cb_prop->setChecked($enable_internal_news);
		$form->addItem($cb_prop);
		
		// Enable rss for internal news
		$cb_prop = new ilCheckboxInputGUI($lng->txt("news_enable_internal_rss"),
			"enable_internal_rss");
		$cb_prop->setValue("1");
		$cb_prop->setInfo($lng->txt("news_enable_internal_rss_info"));
		$cb_prop->setChecked($enable_internal_rss);
		$form->addItem($cb_prop);

		// Number of news items per object
		$nr_opts = array(50 => 50, 100 => 100, 200 => 200);
		$nr_sel = new ilSelectInputGUI($lng->txt("news_nr_of_items"),
			"news_max_items");
		$nr_sel->setInfo($lng->txt("news_nr_of_items_info"));
		$nr_sel->setOptions($nr_opts);
		$nr_sel->setValue($news_set->get("max_items"));
		$form->addItem($nr_sel);

		// Section Header: External Web Feeds Settings
		$sh = new ilFormSectionHeaderGUI();
		$sh->setTitle($lng->txt("feed_settings"));
		$form->addItem($sh);
		
		// Disable External Web Feeds in catetegories
		$cb_prop = new ilCheckboxInputGUI($lng->txt("feed_disable_rep_feeds"),
			"disable_repository_feeds");
		$cb_prop->setValue("1");
		$cb_prop->setInfo($lng->txt("feed_disable_rep_feeds_info"));
		$cb_prop->setChecked($disable_repository_feeds);
		$form->addItem($cb_prop);

		// Number of External Feeds on personal desktop
		$sel = new ilSelectInputGUI($lng->txt("feed_nr_pd_feeds"), "nr_pd_feeds");
		$sel->setInfo($lng->txt("feed_nr_pd_feeds_info"));
		$sel->setOptions(array(0 => "0",
			1 => "1",
			2 => "2",
			3 => "3",
			4 => "4",
			5 => "5"));
		$sel->setValue($nr_personal_desktop_feeds);
		$form->addItem($sel);
		
		// command buttons
		$form->addCommandButton("saveSettings", $lng->txt("save"));
		$form->addCommandButton("view", $lng->txt("cancel"));

		$this->tpl->setContent($form->getHTML());
	}

	/**
	* Save news and external webfeeds settings
	*/
	public function saveSettings()
	{
		global $ilCtrl, $ilSetting;
		
		$news_set = new ilSetting("news");
		$feed_set = new ilSetting("feed");
		$ilSetting->set("block_activated_news", $_POST["enable_internal_news"]);
		$ilSetting->set("block_activated_pdnews", $_POST["enable_internal_news"]);
		$news_set->set("enable_rss_for_internal", $_POST["enable_internal_rss"]);
		$news_set->set("max_items", $_POST["news_max_items"]);
		$feed_set->set("disable_rep_feeds", $_POST["disable_repository_feeds"]);
		$feed_set->set("nr_personal_desktop_feeds", $_POST["nr_pd_feeds"]);
		if ($_POST["nr_pd_feeds"] > 0)
		{
			$ilSetting->set("block_activated_pdfeed", 1);
		}
		else
		{
			$ilSetting->set("block_activated_pdfeed", 0);
		}
		ilUtil::sendInfo($this->lng->txt("settings_saved"),true);
		
		$ilCtrl->redirect($this, "view");
	}
}
?>