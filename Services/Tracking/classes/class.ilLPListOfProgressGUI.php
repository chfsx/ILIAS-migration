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
   * Class ilLPListOfProgress
   *
   * @author Stefan Meyer <smeyer@databay.de>
   *
   * @version $Id$
   *
   * @ilCtrl_Calls ilLPListOfProgressGUI: ilLPFilterGUI, ilPDFPresentation
   *
   * @package ilias-tracking
   *
   */

include_once './Services/Tracking/classes/class.ilLearningProgressBaseGUI.php';
include_once './Services/Tracking/classes/class.ilLPStatusWrapper.php';

class ilLPListOfProgressGUI extends ilLearningProgressBaseGUI
{
	var $tracked_user = null;
	var $show_user_info = false;
	var $filter_gui = null;

	var $details_id = 0;
	var $details_type = '';
	var $details_mode = 0;

	function ilLPListOfProgressGUI($a_mode,$a_ref_id,$a_user_id = 0)
	{
		parent::ilLearningProgressBaseGUI($a_mode,$a_ref_id,$a_user_id);

		$this->__initFilterGUI();
		$this->__initUser($a_user_id);
		
		// Set item id for details
		$this->__initDetails((int) $_GET['details_id']);
	}
		

	/**
	 * execute command
	 */
	function &executeCommand()
	{
		$this->ctrl->setReturn($this, "show");
		$this->ctrl->saveParameter($this,'user_id',$this->getUserId());
		switch($this->ctrl->getNextClass())
		{
			case 'illpfiltergui':

				$this->ctrl->forwardCommand($this->filter_gui);
				break;

			case 'ilpdfpresentation':
				
				include_once './Services/Tracking/classes/class.ilPDFPresentation.php';

				$pdf_gui = new ilPDFPresentation($this->getMode(),$this->getRefId(),$this->getUserId());
				$pdf_gui->setType(LP_ACTIVE_PROGRESS);
				$this->ctrl->setReturn($this,'show');
				$this->ctrl->forwardCommand($pdf_gui);
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
				$this->__initDetails($ilObjDataCache->lookupObjId($this->getRefId()));
				return $this->details();

			case LP_MODE_USER_FOLDER:
				// if called from user folder obj_id is id of current user
				$this->__initUser($this->getUserId());
				break;
		}

		// not called from repository
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lp_list_progress.html','Services/Tracking');

		$this->__showFilter();
		$this->__showProgress();
	}

	function details()
	{

		// Show back button to crs if called from crs. Otherwise if called from personal desktop or administration
		// show back to list
		if((int) $_GET['crs_id'])
		{
			$this->ctrl->setParameter($this,'details_id',(int) $_GET['crs_id']);
			$this->__showButton($this->ctrl->getLinkTarget($this,'details'),$this->lng->txt('trac_view_crs'));
		}
		elseif($this->getMode() == LP_MODE_PERSONAL_DESKTOP or
			   $this->getMode() == LP_MODE_ADMINISTRATION)
		{
			$this->__showButton($this->ctrl->getLinkTarget($this,'show'),$this->lng->txt('trac_view_list'));
		}


		switch($this->details_type)
		{
			case 'crs':
				$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lp_lm_details.html','Services/Tracking');


				// show course member selection
				$this->__showMemberSelector();

				if($this->details_mode == LP_MODE_COLLECTION)
				{
					$this->__showCourseDetails();
				}
				else
				{
					$this->__showDetails();
				}
				break;

			case 'lm':
				$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lp_lm_details.html','Services/Tracking');
				$this->__showDetails();
				break;

			case 'sahs':
				if($this->details_mode == LP_MODE_SCORM)
				{
					$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lp_sco_details.html','Services/Tracking');
					$this->__showSCORMDetails();
				}
				else
				{
					$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lp_lm_details.html','Services/Tracking');
					$this->__showDetails();
				}
				break;

			case 'tst':
				$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lp_lm_details.html','Services/Tracking');
				$this->__showDetails();
				break;
				
			default:
				echo "Don't know";
		}
		
	}

	function __showDetails()
	{
		global $ilObjDataCache;

		include_once("classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);

		$this->__appendUserInfo($info);
		$this->__showObjectDetails($info);
		$this->__appendLPDetails($info,$this->details_id,$this->tracked_user->getId());
	
		// Finally set template variable
		$this->tpl->setVariable("LM_INFO",$info->getHTML());
	}

	function __showCourseDetails()
	{
		global $ilObjDataCache;

		include_once("classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);

		$this->__appendUserInfo($info);
		$this->__showObjectDetails($info);
		$this->__appendLPDetails($info,$this->details_id,$this->tracked_user->getId());
		
		// Finally set template variable
		$this->tpl->setVariable("LM_INFO",$info->getHTML());

		// Show table header
		$this->tpl->setVariable("HEAD_STATUS",$this->lng->txt('trac_status'));
		$this->tpl->setVariable("HEAD_OPTIONS",$this->lng->txt('actions'));

		// Start list of relevant items
		
		$counter = 0;

		$items = ilLPCollections::_getItems($this->details_id);
		$this->__readItemStatusInfo($items);
		include_once './Services/Tracking/classes/class.ilLPCollections.php';
		foreach($items as $item_id)
		{
			$type = $ilObjDataCache->lookupType($item_id);

			// Object icon
			$this->tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_'.$type.'.gif'));
			$this->tpl->setVariable("TYPE_ALT_IMG",$this->lng->txt('obj_'.$type));

			$obj_tpl = new ilTemplate('tpl.lp_object.html',true,true,'Services/Tracking');

			// Title/description
			$this->tpl->setVariable("TXT_TITLE",$ilObjDataCache->lookupTitle($item_id));

			if(strlen($desc = $ilObjDataCache->lookupDescription($item_id)))
			{
				$this->tpl->setCurrentBlock("item_description");
				$this->tpl->setVariable("TXT_DESC",$desc);
				$this->tpl->parseCurrentBlock();
			}

			// Status info
			if($status_info = $this->__getStatusInfo($item_id,$this->tracked_user->getId()))
			{
				$this->tpl->setCurrentBlock("status_info");
				$this->tpl->setVariable("STATUS_PROP",$status_info[0]);
				$this->tpl->setVariable("STATUS_VAL",$status_info[1]);
				$this->tpl->parseCurrentBlock();
			}

			$status = $this->__readStatus($item_id,$this->tracked_user->getId());
			$this->tpl->setCurrentBlock("item_property");
			$this->tpl->setVariable("TXT_PROP",$this->lng->txt('trac_status'));
			$this->tpl->setVariable("VAL_PROP",$this->lng->txt($status));
			$this->tpl->parseCurrentBlock();
			
			$this->tpl->setCurrentBlock("item_properties");
			$this->tpl->parseCurrentBlock();

			$this->__showImageByStatus($this->tpl,$status);

			// Details link
			$this->tpl->setCurrentBlock("item_command");
			$this->ctrl->setParameter($this,'details_id',$item_id);
			$this->ctrl->setParameter($this,'crs_id',$this->details_id);
			$this->tpl->setVariable("HREF_COMMAND",$this->ctrl->getLinkTarget($this,'details'));
			$this->tpl->setVariable("TXT_COMMAND",$this->lng->txt('details'));
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("container_standard_row");
			$this->tpl->setVariable("TBLROW",ilUtil::switchColor($counter++,'tblrow1','tblrow2'));
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("crs_collection");
		$this->tpl->setVariable("HEADER_IMG",ilUtil::getImagePath('icon_crs.gif'));
		$this->tpl->setVariable("HEADER_ALT",$this->lng->txt('obj_crs'));
		$this->tpl->setVariable("BLOCK_HEADER_CONTENT",$this->lng->txt('trac_crs_releavant_items'));
		$this->tpl->parseCurrentBlock();


	}

	function __showSCORMDetails()
	{
		global $ilObjDataCache;

		include_once("classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);

		$this->__appendUserInfo($info);
		$this->__showObjectDetails($info);
		$this->__appendLPDetails($info,$this->details_id,$this->tracked_user->getId());

		// Finally set template variable
		$this->tpl->setVariable("LM_INFO",$info->getHTML());

		// Start list of relevant items
		
		$counter = 0;
		include_once './Services/Tracking/classes/class.ilLPCollections.php';
		include_once './content/classes/SCORM/class.ilSCORMItem.php';

		foreach(ilLPCollections::_getItems($this->details_id) as $item_id)
		{

			// Show table header
			$this->tpl->setVariable("HEAD_STATUS",$this->lng->txt('trac_status'));
			$this->tpl->setVariable("HEAD_OPTIONS",$this->lng->txt('actions'));

			$obj_tpl = new ilTemplate('tpl.lp_object.html',true,true,'Services/Tracking');
			$this->tpl->setVariable("TXT_TITLE",ilSCORMItem::_lookupTitle($item_id));

			// Tracking activated for object
			// Users status

			$status = $this->__readSCORMStatus($item_id);
			$this->tpl->setCurrentBlock("item_property");
			$this->tpl->setVariable("TXT_PROP",$this->lng->txt('trac_status'));
			$this->tpl->setVariable("VAL_PROP",$this->lng->txt($status));
			$this->tpl->parseCurrentBlock();
			$this->__showImageByStatus($this->tpl,$status);
			
			$this->tpl->setCurrentBlock("item_properties");
			$this->tpl->parseCurrentBlock();
			
			$this->tpl->setVariable("BLOCK_ROW_CONTENT",$obj_tpl->get());
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("container_standard_row");
			$this->tpl->setVariable("TBLROW",ilUtil::switchColor($counter++,'tblrow1','tblrow2'));
			$this->tpl->parseCurrentBlock();

		}
		$this->tpl->setCurrentBlock("crs_collection");
		$this->tpl->setVariable("HEADER_IMG",ilUtil::getImagePath('icon_sahs.gif'));
		$this->tpl->setVariable("HEADER_ALT",$this->lng->txt('obj_sahs'));
		$this->tpl->setVariable("BLOCK_HEADER_CONTENT",$this->lng->txt('trac_sahs_relevant_items'));
		$this->tpl->parseCurrentBlock();
	}		

	function __appendUserInfo(&$info)
	{

		if($this->show_user_info)
		{
			
			$info->addSection($this->lng->txt("trac_user_data"));
			$info->addProperty($this->lng->txt('username'),$this->tracked_user->getLogin());
			$info->addProperty($this->lng->txt('name'),$this->tracked_user->getFullname());
			$info->addProperty($this->lng->txt('last_login'),ilFormat::formatDate($this->tracked_user->getLastLogin()));
			$info->addProperty($this->lng->txt('trac_total_online'),
							   ilFormat::_secondsToString(ilOnlineTracking::_getOnlineTime($this->tracked_user->getId())));
		}

	}

	function __showFilter()
	{
		$this->tpl->setVariable("FILTER",$this->filter_gui->getHTML());
	}

	function __showProgress()
	{
		// User info
		include_once("classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);
		$this->__appendUserInfo($info);
		$this->tpl->setVariable("USER_INFO",$info->getHTML());

		#$this->__showButton($this->ctrl->getLinkTargetByClass('ilpdfpresentation','createList'),$this->lng->txt('pdf_export'));
		$this->__initFilter();

		$tpl = new ilTemplate('tpl.lp_progress.html',true,true,'Services/Tracking');

		$this->filter->setRequiredPermission('read');
		if(!count($objs = $this->filter->getObjects()))
		{
			sendInfo($this->lng->txt('trac_filter_no_access'));
			return true;
		}

		// Output filter limit info
		if($this->filter->limitReached())
		{
			$info = sprintf($this->lng->txt('trac_filter_limit_reached'),$this->filter->getLimit());
			$tpl->setVariable("LIMIT_REACHED",$info);
		}

		$type = $this->filter->getFilterType();
		$tpl->setVariable("HEADER_IMG",ilUtil::getImagePath('icon_'.$type.'.gif'));
		$tpl->setVariable("HEADER_ALT",$this->lng->txt('objs_'.$type));
		$tpl->setVariable("BLOCK_HEADER_CONTENT",$this->lng->txt('objs_'.$type));

		// Show table header
		$tpl->setVariable("HEAD_STATUS",$this->lng->txt('trac_status'));
		$tpl->setVariable("HEAD_OPTIONS",$this->lng->txt('actions'));

		// Sort objects by title
		$sorted_objs = $this->__sort(array_keys($objs),'object_data','title','obj_id');

		// Read status info
		$this->__readItemStatusInfo($sorted_objs);

		$counter = 0;
		foreach($sorted_objs as $obj_id)
		{
			$obj_data =& $objs[$obj_id];

			$tpl->setVariable("TBLROW",ilUtil::switchColor($counter++,'tblrow1','tblrow2'));
			$tpl->setCurrentBlock("container_standard_row");
			$tpl->setVariable("ITEM_ID",$obj_id);

			// Title / Description
			$tpl->setVariable("TXT_TITLE",$obj_data['title']);
			if(strlen($obj_data['description']))
			{
				$tpl->setCurrentBlock("item_description");
				$tpl->setVariable("TXT_DESC",$obj_data['description']);
				$tpl->parseCurrentBlock();
			}

			// Status
			$status = $this->__readStatus($obj_id,$this->tracked_user->getId());

			// Status info
			if($status_info = $this->__getStatusInfo($obj_id,$this->tracked_user->getId()))
			{
				$tpl->setCurrentBlock("status_info");
				$tpl->setVariable("STATUS_PROP",$status_info[0]);
				$tpl->setVariable("STATUS_VAL",$status_info[1]);
				$tpl->parseCurrentBlock();
			}

			$tpl->setCurrentBlock("item_property");
			$tpl->setVariable("TXT_PROP",$this->lng->txt('trac_status'));
			$tpl->setVariable("VAL_PROP",$this->lng->txt($status));
			$tpl->parseCurrentBlock();

			$this->__showImageByStatus($tpl,$status);

			
			// Path info
			$tpl->setVariable("OCCURRENCES",$this->lng->txt('trac_occurrences'));
			foreach($obj_data['ref_ids'] as $ref_id)
			{
				$this->__insertPath($tpl,$ref_id);
			}

			// Details link
			$tpl->setCurrentBlock("item_command");
			$this->ctrl->setParameter($this,'details_id',$obj_id);
			$tpl->setVariable("HREF_COMMAND",$this->ctrl->getLinkTarget($this,'details'));
			$tpl->setVariable("TXT_COMMAND",$this->lng->txt('details'));
			$tpl->parseCurrentBlock();


			// Hide link
			$tpl->setCurrentBlock("item_command");
			$this->ctrl->setParameterByClass('illpfiltergui','hide',$obj_id);
			$tpl->setVariable("HREF_COMMAND",$this->ctrl->getLinkTargetByClass('illpfiltergui','hide'));
			$tpl->setVariable("TXT_COMMAND",$this->lng->txt('trac_hide'));
			$tpl->parseCurrentBlock();


			$tpl->setCurrentBlock("container_standard_row");
			$tpl->parseCurrentBlock();
		}	

		// Hide button
		$tpl->setVariable("DOWNRIGHT",ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->setVariable("BTN_HIDE_SELECTED",$this->lng->txt('trac_hide'));
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormActionByClass('illpfiltergui'));

		$this->tpl->setVariable("LP_OBJECTS",$tpl->get());

		return true;
	}


	function __initUser($a_usr_id = 0)
	{
		global $ilUser;

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
		$this->show_user_info = ($this->tracked_user->getId() != $ilUser->getId());
		return true;
	}


	function __initFilterGUI()
	{
		global $ilUser;

		include_once './Services/Tracking/classes/class.ilLPFilterGUI.php';

		$this->filter_gui = new ilLPFilterGUI($ilUser->getId());
	}

	function __initFilter()
	{
		global $ilUser;

		include_once './Services/Tracking/classes/class.ilLPFilter.php';

		$this->filter = new ilLPFilter($ilUser->getId());
	}

	function __initDetails($a_details_id)
	{
		global $ilObjDataCache;

		if($a_details_id)
		{
			$this->details_id = $a_details_id;
			$this->details_type = $ilObjDataCache->lookupType($this->details_id);
			$this->details_mode = ilLPObjSettings::_lookupMode($this->details_id);
		}
	}

	function __readSCORMStatus($sco_id)
	{
		include_once './content/classes/SCORM/class.ilObjSCORMTracking.php';

		$in_progress = ilObjSCORMTracking::_getInProgress($sco_id);
		$completed = ilObjSCORMTracking::_getCompleted($sco_id);

		if(in_array($this->tracked_user->getId(),$in_progress) and !in_array($this->tracked_user->getId(),$completed))
		{
			return $status = LP_STATUS_IN_PROGRESS;
		}
		elseif(in_array($this->tracked_user->getId(),$completed))
		{
			return $status = LP_STATUS_COMPLETED;
		}
		else
		{
			return $status = LP_STATUS_NOT_ATTEMPTED;
		}
	}

	function __showMemberSelector()
	{
		global $rbacsystem;

		if($this->getMode() != LP_MODE_REPOSITORY)
		{
			return false;
		}
		if(!$rbacsystem->checkAccess('edit_learning_progress',(int) $_GET['ref_id']))
		{
			return false;
		}

		if(!$this->details_mode)

		$this->tpl->setCurrentBlock("member_selector");
		$this->ctrl->setParameter($this,'details_id',$this->details_id);
		$this->tpl->setVariable("MEMBER_ACTION",$this->ctrl->getFormAction($this,"details"));
		$this->tpl->setVariable("CRS_MEMBERS",$this->lng->txt("trac_crs_members"));
		
		// Build selection
		include_once "./course/classes/class.ilCourseMembers.php";
		$members = ilCourseMembers::_getMembers($this->details_id);
		$sorted_members = $this->__sort($members,'usr_data','lastname','usr_id');

		foreach($sorted_members as $member_id)
		{
			$options[$member_id] = ilObjUser::_lookupTitle($member_id);
		}

		$this->tpl->setVariable("MEMBER_SELECTION",ilUtil::formSelect($this->tracked_user->getId(),
																	  "user_id",
																	  $options,
																	  false,
																	  true));
			
		



		$this->tpl->setVariable("SHOW",$this->lng->txt("trac_show"));
		$this->tpl->parseCurrentBlock();

		return true;
	}

}
?>