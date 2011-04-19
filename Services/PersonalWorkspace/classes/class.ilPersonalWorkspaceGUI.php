<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* GUI class for personal workspace
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id: class.ilPersonalDesktopGUI.php 26976 2010-12-16 13:24:38Z akill $
*
* @ilCtrl_Calls ilPersonalWorkspaceGUI: ilObjWorkspaceRootFolderGUI, ilObjWorkspaceFolderGUI
* @ilCtrl_Calls ilPersonalWorkspaceGUI: ilObjectCopyGUI, ilObjFileGUI, ilObjBlogGUI
* @ilCtrl_Calls ilPersonalWorkspaceGUI: ilObjTestVerificationGUI, ilObjExerciseVerificationGUI
*
* @ingroup ServicesPersonalWorkspace
*/
class ilPersonalWorkspaceGUI
{
	protected $tree; // [ilTree]
	protected $node_id; // [int]
	
	/**
	 * constructor
	 */
	public function __construct()
	{
		global $ilCtrl, $lng;

		$lng->loadLanguageModule("wsp");

		$this->initTree();

		$ilCtrl->saveParameter($this, "wsp_id");

		$this->node_id = $_REQUEST["wsp_id"];
		if(!$this->node_id)
		{
			$this->node_id = $this->tree->getRootId();
		}
	}
	
	/**
	 * execute command
	 */
	public function executeCommand()
	{
		global $ilCtrl, $lng, $objDefinition;

		$ilCtrl->setReturn($this, "render");
		$cmd = $ilCtrl->getCmd();

		// new type
		if($_REQUEST["new_type"])
		{
			$class_name = $objDefinition->getClassName($_REQUEST["new_type"]);
			$ilCtrl->setCmdClass("ilObj".$class_name."GUI");
		}

		// root node
		$next_class = $ilCtrl->getNextClass();		
		if(!$next_class)
		{
			$node = $this->tree->getNodeData($this->node_id);
			$next_class = "ilObj".$objDefinition->getClassName($node["type"])."GUI";
			$ilCtrl->setCmdClass($next_class);
		}

		// current node
		$class_path = $ilCtrl->lookupClassPath($next_class);
		include_once($class_path);
		$class_name = $ilCtrl->getClassForClasspath($class_path);
		if($_REQUEST["new_type"])
		{
			$gui = new $class_name(null, ilObject2GUI::WORKSPACE_NODE_ID, $this->node_id);
			$gui->setCreationMode();
		}
		else
		{
			$gui = new $class_name($this->node_id, ilObject2GUI::WORKSPACE_NODE_ID, false);
		}
		$ilCtrl->forwardCommand($gui);

		$this->renderLocator();
		$this->renderTitle();

		if(($cmd == "" || $cmd == "render" || $cmd == "view") && !$_REQUEST["new_type"])
		{
			$this->renderToolbar();
		}
	}

	/**
	 * Init personal tree
	 */
	protected function initTree()
	{
		global $ilUser;

		$user_id = $ilUser->getId();

		include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";
		$this->tree = new ilWorkspaceTree($user_id);
		if(!$this->tree->readRootId())
		{
			// create (workspace) root folder
			$root = ilObjectFactory::getClassByType("wsrt");
			$root = new $root(null);
			$root->create();

			$root_id = $this->tree->createReference($root->getId());
			$this->tree->addTree($user_id, $root_id);
			$this->tree->setRootId($root_id);
		}
	}

	protected function renderTitle()
	{
		global $tpl, $lng;
		
		$root = $this->tree->getNodeData($this->node_id);
		if($root["type"] == "wsrt")
		{
			$title = $lng->txt("wsp_personal_workspace");
			$icon = ilUtil::getImagePath("icon_wsrt_b.gif");
			$tpl->setDescription($lng->txt("wsp_personal_workspace_description"));
		}
		else
		{
			$title = $root["title"];
			$icon = ilObject::_getIcon($root["obj_id"], "big");
		}
		$tpl->setTitle($title);
		$tpl->setTitleIcon($icon, $title);		
	}
	
	/**
	 * Render workspace toolbar (folder navigation, add subobject)
	 */
	protected function renderToolbar()
	{
		global $lng, $ilCtrl, $objDefinition, $ilToolbar;

		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
		$ilToolbar->setFormAction($ilCtrl->getFormAction($this));

		$root = $this->tree->getNodeData($this->node_id);
		$subtypes = $objDefinition->getCreatableSubObjects($root["type"], ilObjectDefinition::MODE_WORKSPACE);
		if($subtypes)
		{
			// :TODO: permission checks?
			$options = array(""=>"-");
			foreach(array_keys($subtypes) as $type)
			{
				$class = $objDefinition->getClassName($type);
				$options[$type] = $lng->txt("wsp_type_".$type);
			}

			asort($options);
			$types = new ilSelectInputGUI($lng->txt("wsp_navigation_resource"), "new_type");
			$types->setOptions($options);
			$ilToolbar->addInputItem($types, "new_type");

			$ilToolbar->addFormButton($lng->txt("ok"), "create");
		}
	}

	/**
	 * Build locator for current node
	 */
	protected function renderLocator()
	{
		global $lng, $ilCtrl, $ilLocator, $tpl, $objDefinition;

		$ilLocator->clearItems();
		
		$path = $this->tree->getPathFull($this->node_id);
		foreach($path as $node)
		{
			$obj_class = "ilObj".$objDefinition->getClassName($node["type"])."GUI";

			$ilCtrl->setParameter($this, "wsp_id", $node["wsp_id"]);

			switch($node["type"])
			{			
				case "wsrt":
					$ilLocator->addItem($lng->txt("wsp_personal_workspace"), $ilCtrl->getLinkTargetByClass($obj_class, "render"));
					break;

				case "blog":
				case $objDefinition->isContainer($node["type"]):
					$ilLocator->addItem($node["title"], $ilCtrl->getLinkTargetByClass($obj_class, "render"));
					break;

				default:
					$ilLocator->addItem($node["title"], $ilCtrl->getLinkTargetByClass($obj_class, "edit"));
					break;
			}
		}

		$tpl->setLocator();
		$ilCtrl->setParameter($this, "wsp_id", $this->node_id);
	}
}

?>