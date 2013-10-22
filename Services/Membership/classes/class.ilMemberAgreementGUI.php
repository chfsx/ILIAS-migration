<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once('Services/PrivacySecurity/classes/class.ilPrivacySettings.php');
include_once('Services/Membership/classes/class.ilMemberAgreement.php');
include_once('Modules/Course/classes/Export/class.ilCourseUserData.php');
include_once('Modules/Course/classes/Export/class.ilCourseDefinedFieldDefinition.php');

/** 
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
* 
* @ilCtrl_Calls ilMemberAgreementGUI: 
* @ingroup ModulesCourse
*/
class ilMemberAgreementGUI
{
	private $ref_id;
	private $obj_id;
	private $type;
	
	private $db;
	private $ctrl;
	private $lng;
	private $tpl; 
	
	private $privacy;
	private $agreement;
	
	private $required_fullfilled = false;
	private $agrement_required = false;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * 
	 */
	public function __construct($a_ref_id)
	{
		global $ilDB,$ilCtrl,$lng,$tpl,$ilUser,$ilObjDataCache;
		
		$this->ref_id = $a_ref_id;
	 	$this->obj_id = $ilObjDataCache->lookupObjId($this->ref_id);
		$this->type = ilObject::_lookupType($this->obj_id);
	 	$this->ctrl = $ilCtrl;
	 	$this->tpl = $tpl;
	 	$this->lng = $lng;
	 	$this->lng->loadLanguageModule('ps');
	 	
	 	$this->privacy = ilPrivacySettings::_getInstance();
	 	$this->agreement = new ilMemberAgreement($ilUser->getId(),$this->obj_id);
	 	$this->init();
	}
	
	/**
	 * Execute Command
	 *
	 * @access public
	 * 
	 */
	public function executeCommand()
	{
	 	$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				if(!$cmd or $cmd == 'view')
				{
					$cmd = 'showAgreement';
				}
				$this->$cmd();
				break;
		}	 	
	}

	/**
	 * Get privycy settings
	 * @return ilPrivacySettings
	 */
	public function getPrivacy()
	{
		return $this->privacy;
	}
	
	/**
	 * @return ilMemberAgreement
	 */
	public function getAgreement()
	{
		return $this->agreement;
	}
	
	/**
	 * Show agreement form
	 * @param ilPropertyFormGUI $form
	 * @return bool
	 */
	protected function showAgreement(ilPropertyFormGUI $form = null)
	{
		$form = $this->initFormAgreement($form);
		
		$this->tpl->setContent($form->getHTML());
		return true;
	}
	
	
	
	protected function initFormAgreement()
	{
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->lng->txt($this->type.'_agreement_header'));
		$form->setFormAction($GLOBALS['ilCtrl']->getFormAction($this));
		$form->addCommandButton('save', $this->lng->txt('save'));
		
		$form = self::addExportFieldInfo($form, $this->obj_id, $this->type);
		$form = self::addCustomFields($form, $this->obj_id, $this->type);
		$form = self::addAgreement($form, $this->obj_id, $this->type);
		
		return $form;
	}
	
	/**
	 * Add export field info to form
	 * @global type $lng
	 * @param type $form
	 * @param type $a_obj_id
	 * @param type $a_type
	 * @return type
	 */
	public static function addExportFieldInfo($form,$a_obj_id,$a_type)
	{
		global $lng;
		
		include_once('Services/PrivacySecurity/classes/class.ilExportFieldsInfo.php');
		$fields_info = ilExportFieldsInfo::_getInstanceByType(ilObject::_lookupType($a_obj_id));

		$fields = new ilCustomInputGUI($lng->txt($a_type.'_user_agreement'),'');
		$tpl = new ilTemplate('tpl.agreement_form.html',true,true,'Services/Membership');
		$tpl->setVariable('TXT_INFO_AGREEMENT',$lng->txt($a_type.'_info_agreement'));
		foreach($fields_info->getExportableFields() as $field)
		{
			$tpl->setCurrentBlock('field_item');
			$tpl->setVariable('FIELD_NAME',$lng->txt($field));
			$tpl->parseCurrentBlock();
		}
		$fields->setHtml($tpl->get());
		$form->addItem($fields);
		
		return $form;
	}
	
	/**
	 * Add agreement to form
	 * @param type $form
	 * @param type $a_obj_id
	 * @param type $a_type
	 */
	public static function addAgreement($form, $a_obj_id, $a_type)
	{
		global $lng;
		
		$agreement = new ilCheckboxInputGUI($lng->txt($a_type.'_agree'),'agreement');
		$agreement->setRequired(true);
		$agreement->setOptionTitle($lng->txt($a_type.'_info_agree'));
		$agreement->setValue(1);
		$form->addItem($agreement);
		
		return $form;
	}
	
	/**
	 * Add custom course fields
	 * @param type $form
	 * @param type $a_obj_id
	 * @param type $a_type
	 */
	public static function addCustomFields($form, $a_obj_id, $a_type)
	{
		global $lng;
		
	 	include_once('Modules/Course/classes/Export/class.ilCourseDefinedFieldDefinition.php');
	 	include_once('Modules/Course/classes/Export/class.ilCourseUserData.php');

		if(!count($cdf_fields = ilCourseDefinedFieldDefinition::_getFields($a_obj_id)))
		{
			return true;
		}
		
		$cdf = new ilNonEditableValueGUI($lng->txt('ps_crs_user_fields'));
		$cdf->setValue($lng->txt($a_type.'_ps_cdf_info'));
		$cdf->setRequired(true);
		
		foreach($cdf_fields as $field_obj)
		{
			switch($field_obj->getType())
			{
				case IL_CDF_TYPE_SELECT:
					
					if($field_obj->getValueOptions())
					{
						// Show as radio group
						$option_radios = new ilRadioGroupInputGUI($field_obj->getName(), 'cdf_'.$field_obj->getId());
						if($field_obj->isRequired())
						{
							$option_radios->setRequired(true);
						}
						
						$open_answer_indexes = (array) $field_obj->getValueOptions();
						foreach($field_obj->getValues() as $key => $val)
						{
							$option_radio = new ilRadioOption($val,$field_obj->getId().'_'.$key);
							
							// open answers
							if(in_array($key, $open_answer_indexes))
							{
								$open_answer = new ilTextInputGUI('Sonstiges', 'cdf_oa_'.$field_obj->getId());
								$open_answer->setRequired(true);
								$option_radio->addSubItem($open_answer);
							}
							
							$option_radios->addOption($option_radio);
						}
						$cdf->addSubItem($option_radios);
					}
					else
					{
						$select = new ilSelectInputGUI($field_obj->getName(),'cdf_'.$field_obj->getId());
						#$select->setValue(ilUtil::stripSlashes($_POST['cdf'][$field_obj->getId()]));
						$select->setOptions($field_obj->prepareSelectBox());
						if($field_obj->isRequired())
						{
							$select->setRequired(true);
						}
						$cdf->addSubItem($select);
					}
					break;				

				case IL_CDF_TYPE_TEXT:
					$text = new ilTextInputGUI($field_obj->getName(),'cdf_'.$field_obj->getId());
					#$text->setValue(ilUtil::stripSlashes($_POST['cdf'][$field_obj->getId()]));
					$text->setSize(32);
					$text->setMaxLength(255);
					if($field_obj->isRequired())
					{
						$text->setRequired(true);
					}
					$cdf->addSubItem($text);
					break;
			}
		}
		$form->addItem($cdf);
		return $form;
		
	}


	
	/**
	 * Save
	 *
	 * @access private
	 * @param
	 * 
	 */
	private function save()
	{
		$form = $this->initFormAgreement();
		
		if($form->checkInput())
		{
			self::saveCourseDefinedFields($form, $this->obj_id);

			$this->getAgreement()->setAccepted(true);
			$this->getAgreement()->setAcceptanceTime(time());
			$this->getAgreement()->save();
			$this->ctrl->returnToParent($this);
		}
		elseif(!$this->checkAgreement())
	 	{
	 		ilUtil::sendFailure($this->lng->txt($this->type.'_agreement_required'));
			$form->setValuesByPost();
	 		$this->showAgreement($form);
	 		return false;
	 	}
		else
		{
			ilUtil::sendFailure($this->lng->txt('fill_out_all_required_fields'));
			$form->setValuesByPost();
			$this->showAgreement($form);
			return false;
		}
	}
	
	
	/**
	 * Save course defined fields
	 * @param ilPropertyFormGUI $form
	 */
	public static function saveCourseDefinedFields(ilPropertyFormGUI $form, $a_obj_id)
	{
		global $ilUser;
		
		foreach(ilCourseDefinedFieldDefinition::_getFields($a_obj_id) as $field_obj)
		{
			switch($field_obj->getType())
			{
				case IL_CDF_TYPE_SELECT:
					
					// Split value id from post
					list($field_id,$option_id) = explode('_', $form->getInput('cdf_'.$field_obj->getId()));
					$open_answer_indexes = (array) $field_obj->getValueOptions();
					if(in_array($option_id, $open_answer_indexes))
					{
						$value = $form->getInput('cdf_oa_'.$field_obj->getId());
					}
					else
					{
						$value = $field_obj->getValueById($option_id);
					}
					break;
					
				case IL_CDF_TYPE_TEXT:
					$value = $form->getInput('cdf_'.$field_obj->getId());
					break;
			}
			
			$course_user_data = new ilCourseUserData($ilUser->getId(),$field_obj->getId());
			$course_user_data->setValue($value);
			$course_user_data->update();
		}
	}
	
	
	/**
	 * Check Agreement
	 *
	 * @access private
	 * 
	 */
	private function checkAgreement()
	{
		global $ilUser;
		
	 	if($_POST['agreement'])
	 	{
	 		return true;
	 	}
		if($this->privacy->confirmationRequired($this->type))
		{
			return false;
		}
	 	return true;
	}
	
	
	
	/**
	 * Read setting
	 *
	 * @access private
	 * @return void
	 */
	private function init()
	{
		global $ilUser;
		
	 	$this->required_fullfilled = ilCourseUserData::_checkRequired($ilUser->getId(),$this->obj_id);
 		$this->agreement_required = $this->getAgreement()->agreementRequired();
	}
	
	/**
	 * Send info message
	 *
	 * @access private
	 */
	private function sendInfoMessage()
	{
		$message = '';
		if($this->agreement_required)
		{
			$message = $this->lng->txt($this->type.'_ps_agreement_req_info');
		}
		if(!$this->required_fullfilled)
		{
			if(strlen($message))
			{
				$message .= '<br />';
			}
			$message .= $this->lng->txt($this->type.'_ps_required_info');
		}
		
		if(strlen($message))
		{
			ilUtil::sendFailure($message);
		}
	}
}


?>