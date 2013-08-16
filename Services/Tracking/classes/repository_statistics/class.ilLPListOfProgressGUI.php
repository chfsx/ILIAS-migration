<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilLPListOfProgress
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
*
* @version $Id$
*
* @ilCtrl_Calls ilLPListOfProgressGUI: ilLPProgressTableGUI
*
* @package ilias-tracking
*
*/

include_once './Services/Tracking/classes/class.ilLearningProgressBaseGUI.php';
include_once './Services/Tracking/classes/class.ilLPStatusWrapper.php';

class ilLPListOfProgressGUI extends ilLearningProgressBaseGUI
{
	var $tracked_user = null;
	var $details_id = 0;
	var $details_type = '';
	var $details_mode = 0;

	function ilLPListOfProgressGUI($a_mode,$a_ref_id,$a_user_id = 0)
	{
		parent::ilLearningProgressBaseGUI($a_mode,$a_ref_id,$a_user_id);
		
		$this->__initUser($a_user_id);
		
		// Set item id for details
		$this->__initDetails((int) $_GET['details_id']);
		$this->ctrl->saveParameter($this,'details_id',$_REQUEST['details_id']);
	}
		

	/**
	 * execute command
	 */
	function &executeCommand()
	{
		global $ilUser;
		
		$this->ctrl->setReturn($this, "show");
		$this->ctrl->saveParameter($this,'user_id',$this->getUserId());
		switch($this->ctrl->getNextClass())
		{
			case 'illpprogresstablegui':
				include_once './Services/Tracking/classes/repository_statistics/class.ilLPProgressTableGUI.php';
			    $table_gui = new ilLPProgressTableGUI($this, "", $this->tracked_user);
				$this->ctrl->setReturn($this,'show');
				$this->ctrl->forwardCommand($table_gui);
				break;

			default:
				$cmd = $this->__getDefaultCommand();
				$this->$cmd();

		}
		return true;
	}

	function show()
	{
		global $ilObjDataCache;

		switch($this->getMode())
		{
			// Show only detail of current repository item if called from repository
			case LP_MODE_REPOSITORY:
				$this->__initDetails($this->getRefId());
				return $this->details();

			case LP_MODE_USER_FOLDER:
				// if called from user folder obj_id is id of current user
				$this->__initUser($this->getUserId());
				break;
		}

		// not called from repository
 		$this->__showProgressList();
	}

	function details()
	{
		global $ilObjDataCache,$ilCtrl,$rbacsystem;

		// Show back button to crs if called from crs. Otherwise if called from personal desktop or administration
		// show back to list
		if((int)$_GET['crs_id'])
		{
			$this->ctrl->setParameter($this,'details_id',(int) $_GET['crs_id']);
			$this->__showButton($this->ctrl->getLinkTarget($this,'details'),$this->lng->txt('trac_view_crs'));
		}
		elseif($this->getMode() == LP_MODE_PERSONAL_DESKTOP or
			   $this->getMode() == LP_MODE_ADMINISTRATION or
			   $this->getMode() == LP_MODE_USER_FOLDER)
		{
			$this->__showButton($this->ctrl->getLinkTarget($this,'show'),$this->lng->txt('trac_view_list'));
		}

		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lp_progress_container.html','Services/Tracking');

		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);
		$info->setFormAction($ilCtrl->getFormAction($this));
		$this->__appendUserInfo($info, $this->tracked_user);
		$this->__showObjectDetails($info,$this->details_obj_id);
		$this->__appendLPDetails($info,$this->details_obj_id,$this->tracked_user->getId());
		
		// Finally set template variable
		$this->tpl->setVariable("LM_INFO",$info->getHTML());
		
		include_once './Services/Object/classes/class.ilObjectLP.php';
		$olp = ilObjectLP::getInstance($this->details_obj_id);	
		$collection = $olp->getCollectionInstance();
		if($collection)
		{			
			$obj_ids = array();
			foreach($collection->getItems() as $item_id)
			{
				if($collection instanceof ilLPCollectionOfRepositoryObjects)
				{
					$obj_ids[ilObject::_lookupObjectId($item_id)] = array($item_id);
					
				}
				else
				{
					$obj_ids[] = $item_id;
				}				
			}
		}
		
		$personal_only = !$rbacsystem->checkAccess('edit_learning_progress',$this->getRefId());
	
		include_once("./Services/Tracking/classes/repository_statistics/class.ilLPProgressTableGUI.php");
		$lp_table = new ilLPProgressTableGUI($this, "details", $this->tracked_user, $obj_ids, true, $this->details_mode, $personal_only, $this->details_obj_id);
		$this->tpl->setVariable("LP_OBJECTS", $lp_table->getHTML());
		
		$this->tpl->setVariable("LEGEND",$this->__getLegendHTML());
	}

	function __showProgressList()
	{
		global $ilUser,$ilObjDataCache,$ilCtrl;

		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lp_list_progress.html','Services/Tracking');
		
		// User info
		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);
		$info->setFormAction($ilCtrl->getFormAction($this));
		
		if ($this->__appendUserInfo($info, $this->tracked_user))
		{
			$this->tpl->setCurrentBlock("info_user");
			$this->tpl->setVariable("USER_INFO",$info->getHTML());
			$this->tpl->parseCurrentBlock();
		}

		include_once("./Services/Tracking/classes/repository_statistics/class.ilLPProgressTableGUI.php");
		$lp_table = new ilLPProgressTableGUI($this, "", $this->tracked_user);
		$this->tpl->setVariable("LP_OBJECTS", $lp_table->getHTML());

		$this->tpl->setVariable("LEGEND", $this->__getLegendHTML());
	}

	function __initUser($a_usr_id = 0)
	{
		global $ilUser,$rbacreview,$rbacsystem;

		if($_POST['user_id'])
		{
			$a_usr_id = $_POST['user_id'];
			$this->ctrl->setParameter($this,'user_id',$_POST['user_id']);
		}

		if($a_usr_id)
		{
			$this->tracked_user = ilObjectFactory::getInstanceByObjId($a_usr_id);
		}
		else
		{
			$this->tracked_user = $ilUser;
		}
		
		// #8762: see ilObjUserGUI->getTabs()
		if($this->mode == LP_MODE_USER_FOLDER && $rbacsystem->checkAccess('read',$this->ref_id))
		{
			return true;
		}
		
		// Check access
		if(!$rbacreview->isAssigned($ilUser->getId(),SYSTEM_ROLE_ID))
		{
			$this->tracked_user = $ilUser;
		}
		
		return true;
	}

	function __initDetails($a_details_id)
	{
		global $ilObjDataCache;

		if(!$a_details_id)
		{
			$a_details_id = $this->getRefId();
		}
		if($a_details_id)
		{
			$ref_ids = ilObject::_getAllReferences($a_details_id);
			
			$this->details_id = $a_details_id;
			$this->details_obj_id = $ilObjDataCache->lookupObjId($this->details_id);
			$this->details_type = $ilObjDataCache->lookupType($this->details_obj_id);
						
			include_once 'Services/Object/classes/class.ilObjectLP.php';
			$olp = ilObjectLP::getInstance($this->details_obj_id);													
			$this->details_mode = $olp->getCurrentMode();
		}
	}
}

?>