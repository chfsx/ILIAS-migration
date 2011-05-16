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

/** 
* 
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
* 
* 
* @ilCtrl_Calls ilECSSettingsGUI:
* @ingroup ServicesWebServicesECS
*/
class ilECSSettingsGUI
{
	protected $tpl;
	protected $lng;
	protected $ctrl;
	protected $tabs_gui;
	

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		global $lng,$tpl,$ilCtrl,$ilTabs;
		
		$this->tpl = $tpl;
		$this->lng = $lng;
		$this->lng->loadLanguageModule('ecs');
		$this->ctrl = $ilCtrl;
		$this->tabs_gui = $ilTabs; 
		
		$this->initSettings();
	}
	
	/**
	 * Execute command
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		$this->setSubTabs();
		switch($next_class)
		{
			default:
				if(!$cmd)
				{
					$cmd = "settings";
				}
				$this->$cmd();
				break;
		}
		return true;
	}
	
	
	/**
	 * Read all importable econtent
	 *
	 * @access protected
	 */
	protected function readAll()
	{
		include_once('Services/WebServices/ECS/classes/class.ilECSConnector.php');
		include_once('Services/WebServices/ECS/classes/class.ilECSConnectorException.php');
		include_once('./Services/WebServices/ECS/classes/class.ilECSEventQueueReader.php');

		try
		{
			ilECSEventQueueReader::handleImportReset();
			ilECSEventQueueReader::handleExportReset();
			
			include_once('./Services/WebServices/ECS/classes/class.ilECSTaskScheduler.php');
			ilECSTaskScheduler::_getInstance()->startTaskExecution();

			ilUtil::sendInfo($this->lng->txt('ecs_remote_imported'));
			$this->imported();
			return true;
		}
		catch(ilECSConnectorException $e1)
		{
			ilUtil::sendInfo('Cannot connect to ECS server: '.$e1->getMessage());
			$this->imported();
			return false;
		}
		catch(ilException $e2)
		{
			ilUtil::sendInfo('Update failed: '.$e1->getMessage());
			$this->imported();
			return false;
		}
		
		
	}
	
	/**
	 * show settings 
	 *
	 * @access protected
	 */
	protected function settings()
	{
		$this->initSettingsForm();
	 	$this->tabs_gui->setSubTabActive('ecs_settings');
		
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.ecs_settings.html','Services/WebServices/ECS');
		$this->tpl->setVariable('SETTINGS_TABLE',$this->form->getHTML());
	}
	
	/**
	 * init settings form
	 *
	 * @access protected
	 */
	protected function initSettingsForm()
	{
		if(is_object($this->form))
		{
			return true;
		}
		include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
		
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this,'settings'));
		$this->form->setTitle($this->lng->txt('ecs_connection_settings'));
		
		$ena = new ilCheckboxInputGUI($this->lng->txt('ecs_active'),'active');
		$ena->setChecked($this->settings->isEnabled());
		$ena->setValue(1);
		$this->form->addItem($ena);
		
		$ser = new ilTextInputGUI($this->lng->txt('ecs_server_url'),'server');
		$ser->setValue($this->settings->getServer());
		$ser->setRequired(true);
		$this->form->addItem($ser);
		
		$pro = new ilSelectInputGUI($this->lng->txt('ecs_protocol'),'protocol');
		// fixed to https
		#$pro->setOptions(array(ilECSSetting::PROTOCOL_HTTP => $this->lng->txt('http'),
		#		ilECSSetting::PROTOCOL_HTTPS => $this->lng->txt('https')));
		$pro->setOptions(array(ilECSSetting::PROTOCOL_HTTPS => 'HTTPS'));
		$pro->setValue($this->settings->getProtocol());
		$pro->setRequired(true);
		$this->form->addItem($pro);
		
		$por = new ilTextInputGUI($this->lng->txt('ecs_port'),'port');
		$por->setSize(5);
		$por->setMaxLength(5);
		$por->setValue($this->settings->getPort());
		$por->setRequired(true);
		$this->form->addItem($por);
		
		$cer = new ilTextInputGUI($this->lng->txt('ecs_client_cert'),'client_cert');
		$cer->setSize(60);
		$cer->setValue($this->settings->getClientCertPath());
		$cer->setRequired(true);
		$this->form->addItem($cer);
		
		$cer = new ilTextInputGUI($this->lng->txt('ecs_cert_key'),'key_path');
		$cer->setSize(60);
		$cer->setValue($this->settings->getKeyPath());
		$cer->setRequired(true);
		$this->form->addItem($cer);
		
		$cer = new ilTextInputGUI($this->lng->txt('ecs_key_password'),'key_password');
		$cer->setSize(12);
		$cer->setValue($this->settings->getKeyPassword());
		$cer->setInputType('password');
		$cer->setRequired(true);
		$this->form->addItem($cer);

		$cer = new ilTextInputGUI($this->lng->txt('ecs_ca_cert'),'ca_cert');
		$cer->setSize(60);
		$cer->setValue($this->settings->getCACertPath());
		$cer->setRequired(true);
		$this->form->addItem($cer);

		$ser = new ilNonEditableValueGUI($this->lng->txt('cert_serial'));
		$ser->setValue($this->settings->getCertSerialNumber() ? $this->settings->getCertSerialNumber() : $this->lng->txt('ecs_no_value'));
		$this->form->addItem($ser);

		$loc = new ilFormSectionHeaderGUI();
		$loc->setTitle($this->lng->txt('ecs_local_settings'));
		$this->form->addItem($loc);
		
		$pol = new ilDurationInputGUI($this->lng->txt('ecs_polling'),'polling');
		$pol->setShowDays(false);
		$pol->setShowHours(false);
		$pol->setShowMinutes(true);
		$pol->setShowSeconds(true);
		$pol->setSeconds($this->settings->getPollingTimeSeconds());
		$pol->setMinutes($this->settings->getPollingTimeMinutes());
		$pol->setRequired(true);
		$pol->setInfo($this->lng->txt('ecs_polling_info'));
		$this->form->addItem($pol);
		
		$imp = new ilCustomInputGUI($this->lng->txt('ecs_import_id'));
		$imp->setRequired(true);
		
		$tpl = new ilTemplate('tpl.ecs_import_id_form.html',true,true,'Services/WebServices/ECS');
		$tpl->setVariable('SIZE',5);
		$tpl->setVariable('MAXLENGTH',11);
		$tpl->setVariable('POST_VAR','import_id');
		$tpl->setVariable('PROPERTY_VALUE',$this->settings->getImportId());
		
		if($this->settings->getImportId())
		{
			$tpl->setVariable('COMPLETE_PATH',$this->buildPath($this->settings->getImportId()));
		}		
		
		$imp->setHTML($tpl->get());
		$imp->setInfo($this->lng->txt('ecs_import_id_info'));
		$this->form->addItem($imp);
		
		$loc = new ilFormSectionHeaderGUI();
		$loc->setTitle($this->lng->txt('ecs_remote_user_settings'));
		$this->form->addItem($loc);
		
		$role = new ilSelectInputGUI($this->lng->txt('ecs_role'),'global_role');
		$role->setOptions($this->prepareRoleSelect());
		$role->setValue($this->settings->getGlobalRole());
		$role->setInfo($this->lng->txt('ecs_global_role_info'));
		$role->setRequired(true);
		$this->form->addItem($role);
		
		$duration = new ilDurationInputGUI($this->lng->txt('ecs_account_duration'),'duration');
		$duration->setInfo($this->lng->txt('ecs_account_duration_info'));
		$duration->setMonths($this->settings->getDuration());
		$duration->setShowSeconds(false);
		$duration->setShowMinutes(false);
		$duration->setShowHours(false);
		$duration->setShowDays(false);
		$duration->setShowMonths(true);
		$duration->setRequired(true);
		$this->form->addItem($duration);
		
		// Email recipients
		$loc = new ilFormSectionHeaderGUI();
		$loc->setTitle($this->lng->txt('ecs_notifications'));
		$this->form->addItem($loc);
		
		$rcp_user = new ilTextInputGUI($this->lng->txt('ecs_user_rcp'),'user_recipients');
		$rcp_user->setValue($this->settings->getUserRecipientsAsString());
		$rcp_user->setInfo($this->lng->txt('ecs_user_rcp_info'));
		$this->form->addItem($rcp_user);

		$rcp_econ = new ilTextInputGUI($this->lng->txt('ecs_econ_rcp'),'econtent_recipients');
		$rcp_econ->setValue($this->settings->getEContentRecipientsAsString());
		$rcp_econ->setInfo($this->lng->txt('ecs_econ_rcp_info'));
		$this->form->addItem($rcp_econ);

		$rcp_app = new ilTextInputGUI($this->lng->txt('ecs_approval_rcp'),'approval_recipients');
		$rcp_app->setValue($this->settings->getApprovalRecipientsAsString());
		$rcp_app->setInfo($this->lng->txt('ecs_approval_rcp_info'));
		$this->form->addItem($rcp_app);

		$this->form->addCommandButton('saveSettings',$this->lng->txt('save'));
		$this->form->addCommandButton('settings',$this->lng->txt('cancel'));
	}
	
	/**
	 * save settings
	 *
	 * @access protected
	 */
	protected function saveSettings()
	{
		$this->settings->setEnabledStatus((int) $_POST['active']);
		$this->settings->setServer(ilUtil::stripSlashes($_POST['server']));
		$this->settings->setPort(ilUtil::stripSlashes($_POST['port']));
		$this->settings->setProtocol(ilUtil::stripSlashes($_POST['protocol']));
		$this->settings->setClientCertPath(ilUtil::stripSlashes($_POST['client_cert']));
		$this->settings->setCACertPath(ilUtil::stripSlashes($_POST['ca_cert']));
		$this->settings->setKeyPath(ilUtil::stripSlashes($_POST['key_path']));
		$this->settings->setKeyPassword(ilUtil::stripSlashes($_POST['key_password']));
		$this->settings->setImportId(ilUtil::stripSlashes($_POST['import_id']));
		$this->settings->setPollingTimeMS((int) $_POST['polling']['mm'],(int) $_POST['polling']['ss']);
		$this->settings->setServer(ilUtil::stripSlashes($_POST['server']));
		$this->settings->setGlobalRole((int) $_POST['global_role']);
		$this->settings->setDuration((int) $_POST['duration']['MM']);
		
		$this->settings->setUserRecipients(ilUtil::stripSlashes($_POST['user_recipients']));
		$this->settings->setEContentRecipients(ilUtil::stripSlashes($_POST['econtent_recipients']));
		$this->settings->setApprovalRecipients(ilUtil::stripSlashes($_POST['approval_recipients']));
		
		if(!$error = $this->settings->validate())
		{
			$this->settings->save();
			ilUtil::sendInfo($this->lng->txt('settings_saved'));
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt($error));
		}
		
		$this->setSubTabs('ecs_settings');
		$this->settings();
		return true;
	}
	
	/**
	 * show communities
	 *
	 * @access public
	 * 
	 */
	public function communities()
	{
	 	$this->tabs_gui->setSubTabActive('ecs_communities');

	 	$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.ecs_communities.html','Services/WebServices/ECS');
	 	
	 	$this->tpl->setVariable('FORMACTION',$this->ctrl->getFormAction($this,'updateCommunities'));
	 	$this->tpl->setVariable('IMAGE_DOWNRIGHT',ilUtil::getImagePath('arrow_downright.gif'));
	 	$this->tpl->setVariable('TXT_SAVE',$this->lng->txt('ecs_enable_participant'));
	 	
	 	include_once('Services/WebServices/ECS/classes/class.ilECSCommunityReader.php');
	 	include_once('Services/WebServices/ECS/classes/class.ilECSCommunityTableGUI.php');

		try
		{
			$reader = ilECSCommunityReader::_getInstance();

		 	foreach($reader->getCommunities() as $community)
		 	{
		 		$this->tpl->setCurrentBlock('table_community');
		 		$table_gui = new ilECSCommunityTableGUI($this,'communities',$community->getId());
				
		 		$table_gui->setTitle($community->getTitle().' ('.$community->getDescription().')');
		 		#$table_gui->parse($community->getParticipants());
		 		$table_gui->parse($community->getParticipants());
			
				#$table_gui->setSelectAllCheckbox("mid");
				$this->tpl->setVariable('TABLE_COMM',$table_gui->getHTML());
		 		$this->tpl->parseCurrentBlock();
		 	}
		}
		catch(ilECSConnectorException $exc)
		{
			ilUtil::sendInfo('Cannot connect to ECS server');
			return true;
		}
	}
	
	/**
	 * update whitelist
	 *
	 * @access protected
	 * 
	 */
	protected function updateCommunities()
	{
		global $ilLog;

		
		$mids = $_POST['mid'] ? $_POST['mid'] : array();
		
		include_once('./Services/WebServices/ECS/classes/class.ilECSParticipantSettings.php');
		$part = ilECSParticipantSettings::_getInstance();

		foreach($part->getEnabledParticipants() as $mid)
		{
			if(!in_array($mid,$mids))
			{
				// Delete all remote courses
				include_once('./Modules/RemoteCourse/classes/class.ilObjRemoteCourse.php');
				foreach(ilObjRemoteCourse::_lookupObjIdsByMID($mid) as $obj_id)
				{
					foreach(ilObject::_getAllReferences($obj_id) as $ref_id)
					{
						$to_delete = ilObjectFactory::getInstanceByRefId($ref_id,false);
						$to_delete->delete();
					}
				}
			}
		}
		

		/*
		try
		{
			// Update all exported econtent
			include_once('./Services/WebServices/ECS/classes/class.ilECSEContentReader.php');
			include_once('./Services/WebServices/ECS/classes/class.ilECSConnector.php');
			include_once('./Services/WebServices/ECS/classes/class.ilECSExport.php');
			$reader = new ilECSEContentReader();
			$reader->read();
			$all_content = $reader->getEContent();
			
			// read update events
			foreach($all_content as $content)
			{
				if(ilECSExport::_isRemote($content->getEContentId()))
				{
					$ilLog->write(__METHOD__.': Ignoring remote EContent: '.$content->getTitle());
					// Do not handle remote courses.
					continue;
				}
				$members = array_intersect($mids,$content->getEligibleMembers());
				if(!$members)
				{
					$ilLog->write(__METHOD__.': Deleting EContent: '.$content->getTitle());
					$connector = new ilECSConnector();
					$connector->deleteResource($content->getEContentId());
					
					ilECSExport::_deleteEContentIds(array($content->getEContentId()));
				}
				elseif(count($members) != count($content->getEligibleMembers()))
				{
					$ilLog->write(__METHOD__.': Update eligible members for EContent: '.$content->getTitle());
					$content->setEligibleMembers($members);
					$connector = new ilECSConnector();
					$connector->updateResource($content->getEContentId(),json_encode($content));
				}
			}
		}
		catch(ilECSConnectorException $e)
		{
			ilUtil::sendInfo('Cannot connect to ECS server: '.$e->getMessage());
			$this->communities();
			return false;
		}
		catch(ilException $e)
		{
			ilUtil::sendInfo('Update failed: '.$e->getMessage());
			$this->communities();
			return false;
		}

		*/
		$part->setEnabledParticipants($_POST['mid'] ? $_POST['mid'] : array());
		$part->save();
		
		ilUtil::sendInfo($this->lng->txt('settings_saved'));
		$this->communities();
		return true;
	}
	
	/**
	 * Show mapping settings (EContent-Data <-> (Remote)Course
	 *
	 * @access public
	 */
	public function mappings()
	{
	 	include_once('./Services/AdvancedMetaData/classes/class.ilAdvancedMDFieldDefinition.php');
	 	
	 	$this->tabs_gui->setSubTabActive('ecs_mappings');
	 	
	 	$fields = ilAdvancedMDFieldDefinition::_getActiveDefinitionsByObjType('crs');
	 	if(!count($fields))
	 	{
	 		ilUtil::sendInfo($this->lng->txt('ecs_no_adv_md'));
	 		return true;
	 	}
	 	
	 	$this->initMappingsForm();
		$this->tpl->setContent($this->form->getHTML());
	 	return true;
	}
	
	/**
	 * Save mappings
	 *
	 * @access protected
	 * 
	 */
	protected function saveMappings()
	{
		include_once('./Services/WebServices/ECS/classes/class.ilECSDataMappingSettings.php');
		$mapping_settings = ilECSDataMappingSettings::_getInstance();
		$mapping_settings->setMappings($_POST['mapping']);
		$mapping_settings->save();
		
		ilUtil::sendInfo($this->lng->txt('settings_saved'));
		$this->mappings();
		return true;
	}

	/**
	 * init mapping form
	 *
	 * @access protected
	 */
	protected function initMappingsForm()
	{
		include_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
		
		if(is_object($this->form))
		{
			return true;
		}

		include_once('./Services/WebServices/ECS/classes/class.ilECSDataMappingSettings.php');
		$mapping_settings = ilECSDataMappingSettings::_getInstance();
			
	 	include_once('./Services/AdvancedMetaData/classes/class.ilAdvancedMDFieldDefinition.php');
	 	$fields = ilAdvancedMDFieldDefinition::_getActiveDefinitionsByObjType('crs');
		$fields = array_unique(array_merge(ilAdvancedMDFieldDefinition::_getActiveDefinitionsByObjType('crs'),
								ilAdvancedMDFieldDefinition::_getActiveDefinitionsByObjType('rcrs')));
		
		 	
		$options = $this->prepareFieldSelection($fields);	
		$this->form = new ilPropertyFormGUI();
		$this->form->setTitle($this->lng->txt('ecs_mapping_tbl'));
		$this->form->setFormAction($this->ctrl->getFormAction($this,'saveMappings'));
		$this->form->addCommandButton('saveMappings',$this->lng->txt('save'));
		$this->form->addCommandButton('mappings',$this->lng->txt('cancel'));
				
		// get all optional fields
		include_once('./Services/WebServices/ECS/classes/class.ilECSUtils.php');
		$optional = ilECSUtils::_getOptionalEContentFields();
		
		foreach($optional as $field_name)
		{
			$select = new ilSelectInputGUI($this->lng->txt('ecs_field_'.$field_name),'mapping['.$field_name.']');
			$select->setValue($mapping_settings->getMappingByECSName($field_name));
			$select->setOptions($options);
			$this->form->addItem($select);
		}
	}
	
	/**
	 * Category mappings 
	 * @return
	 */
	protected function categoryMapping()
	{
		$this->tabs_gui->setSubTabActive('ecs_category_mapping');
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.category_mapping.html','Services/WebServices/ECS');
		
		$this->initRule();
		$this->initCategoryMappingForm();
		
		
		$this->tpl->setVariable('NEW_RULE_TABLE',$this->form->getHTML());
		if($html = $this->showRulesTable())
		{
			$this->tpl->setVariable('RULES_TABLE',$html);			
		}
	}
	
	/**
	 * save category mapping 
	 * @return
	 */
	protected function addCategoryMapping()
	{
		$this->initRule();
		
		$this->initCategoryMappingForm('add');
		if($this->form->checkInput())
		{
			$this->rule->setContainerId($this->form->getInput('import_id'));
			$this->rule->setMappingType($this->form->getInput('type'));
			$this->rule->setMappingValue($this->form->getInput('mapping_value'));
			$this->rule->setFieldName($this->form->getInput('field'));
			$this->rule->setDateRangeStart($this->form->getItemByPostVar('dur_begin')->getDate());
			$this->rule->setDateRangeEnd($this->form->getItemByPostVar('dur_end')->getDate());
			
			if($err = $this->rule->validate())
			{
				ilUtil::sendInfo($this->lng->txt($err));
				$this->form->setValuesByPost();
				$this->categoryMapping();
				return false;
			}
			$this->rule->save();
			ilUtil::sendInfo($this->lng->txt('settings_saved'));
			unset($this->rule);
			$this->categoryMapping();
			return true;
		}
		ilUtil::sendInfo($this->lng->txt('err_check_input'));
		$this->form->setValuesByPost();
		$this->categoryMapping();
		return false;
	}
	
	/**
	 * Edit category mapping
	 * @return
	 */
	protected function editCategoryMapping()
	{
		if(!$_REQUEST['rule_id'])
		{
			ilUtil::sendInfo($this->lng->txt('select_one'));
			$this->categoryMapping();
			return false;
		}

		$this->tabs_gui->setSubTabActive('ecs_category_mapping');
		$this->ctrl->saveParameter($this,'rule_id');
		$this->initRule((int) $_REQUEST['rule_id']);
		
		$this->initCategoryMappingForm('edit');
		$this->tpl->setContent($this->form->getHTML());
		return true;
	}
	
	/**
	 * update category mapping 
	 * @return
	 */
	protected function updateCategoryMapping()
	{
		if(!$_REQUEST['rule_id'])
		{
			ilUtil::sendInfo($this->lng->txt('select_one'));
			$this->categoryMapping();
			return false;
		}
		$this->ctrl->saveParameter($this,'rule_id');
		$this->initRule((int) $_REQUEST['rule_id']);
		$this->initCategoryMappingForm('edit');
		if($this->form->checkInput())
		{
			$this->rule->setContainerId($this->form->getInput('import_id'));
			$this->rule->setMappingType($this->form->getInput('type'));
			$this->rule->setMappingValue($this->form->getInput('mapping_value'));
			$this->rule->setFieldName($this->form->getInput('field'));
			$this->rule->setDateRangeStart($this->form->getItemByPostVar('dur_begin')->getDate());
			$this->rule->setDateRangeEnd($this->form->getItemByPostVar('dur_end')->getDate());
			
			if($err = $this->rule->validate())
			{
				ilUtil::sendInfo($this->lng->txt($err));
				$this->form->setValuesByPost();
				$this->editCategoryMapping();
				return false;
			}
			$this->rule->update();
			ilUtil::sendInfo($this->lng->txt('settings_saved'),true);
			$this->ctrl->redirect($this,'categoryMapping');
			return true;
		}
		ilUtil::sendInfo($this->lng->txt('err_check_input'));
		$this->form->setValuesByPost();
		$this->editCategoryMapping();
		return false;
		
	}
	
	/**
	 * Delete selected category mappings 
	 */
	protected function deleteCategoryMappings()
	{
		if(!is_array($_POST['rules']) or !$_POST['rules'])
		{
			ilUtil::sendInfo($this->lng->txt('no_checkbox'));
			$this->categoryMapping();
			return false;
		}
		foreach($_POST['rules'] as $rule_id)
		{
			include_once './Services/WebServices/ECS/classes/class.ilECSCategoryMappingRule.php';
			$rule = new ilECSCategoryMappingRule($rule_id);
			$rule->delete();			
		}
		ilUtil::sendInfo($this->lng->txt('settings_saved'));
		$this->categoryMapping();
		return true;
	}
	
	/**
	 * Show rules table 
	 * @return
	 */
	protected function showRulesTable()
	{
		include_once './Services/WebServices/ECS/classes/class.ilECSCategoryMapping.php';
		
		if(!$rules = ilECSCategoryMapping::getActiveRules())
		{
			return false;
		}
		include_once './Services/WebServices/ECS/classes/class.ilECSCategoryMappingTableGUI.php';
		$rule_table = new ilECSCategoryMappingTableGUI($this,'categoryMapping');
		$rule_table->parse($rules);
		return $rule_table->getHTML();
	}
	
	/**
	 * Init rule 
	 * @param int	$rule_id	rule id
	 * @return
	 */
	protected function initRule($a_rule_id = 0)
	{
		if(is_object($this->rule))
		{
			return $this->rule;
		}
		
		include_once './Services/WebServices/ECS/classes/class.ilECSCategoryMappingRule.php';
		$this->rule = new ilECSCategoryMappingRule($a_rule_id);
	}
	
	/**
	 * Init category mapping form 
	 * @return
	 */
	protected function initCategoryMappingForm($a_mode = 'add')
	{
		if(is_object($this->form))
		{
			return true;
		}
		
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		include_once './Services/WebServices/ECS/classes/class.ilECSCategoryMappingRule.php';
		
		$this->form = new ilPropertyFormGUI();
		
		if($a_mode == 'add') 
		{
			$this->form->setTitle($this->lng->txt('ecs_new_category_mapping'));
			$this->form->setFormAction($this->ctrl->getFormAction($this,'categoryMapping'));
			$this->form->addCommandButton('addCategoryMapping',$this->lng->txt('save'));
			$this->form->addCommandButton('categoryMapping',$this->lng->txt('cancel'));
		}
		else
		{
			$this->form->setTitle($this->lng->txt('ecs_edit_category_mapping'));
			$this->form->setFormAction($this->ctrl->getFormAction($this,'editCategoryMapping'));
			$this->form->addCommandButton('updateCategoryMapping',$this->lng->txt('save'));
			$this->form->addCommandButton('categoryMapping',$this->lng->txt('cancel'));
		}
		
		$imp = new ilCustomInputGUI($this->lng->txt('ecs_import_id'),'import_id');
		$imp->setRequired(true);
		
		$tpl = new ilTemplate('tpl.ecs_import_id_form.html',true,true,'Services/WebServices/ECS');
		$tpl->setVariable('SIZE',5);
		$tpl->setVariable('MAXLENGTH',11);
		$tpl->setVariable('POST_VAR','import_id');
		$tpl->setVariable('PROPERTY_VALUE',$this->rule->getContainerId());
		
		if($this->settings->getImportId())
		{
			$tpl->setVariable('COMPLETE_PATH',$this->buildPath($this->rule->getContainerId()));
		}		
		
		$imp->setHTML($tpl->get());
		$imp->setInfo($this->lng->txt('ecs_import_id_info'));
		$this->form->addItem($imp);
		
		include_once('./Services/WebServices/ECS/classes/class.ilECSCategoryMapping.php');
		$fields = ilECSCategoryMapping::getPossibleFields();
		foreach($fields as $field)
		{
			$options[$field] = $this->lng->txt('ecs_field_'.$field);
		}
		$select = new ilSelectInputGUI($this->lng->txt('ecs_attribute_name'),'field');
		$select->setValue($this->rule->getFieldName());
		$select->setRequired(true);
		$select->setOptions($options);
		$this->form->addItem($select);

		//	Value
		$value = new ilRadioGroupInputGUI($this->lng->txt('ecs_cat_mapping_type'),'type');
		$value->setValue($this->rule->getMappingType());
		$value->setRequired(true);
		
		$fixed = new ilRadioOption($this->lng->txt('ecs_cat_mapping_fixed'),ilECSCategoryMappingRule::TYPE_FIXED);
		$fixed->setInfo($this->lng->txt('ecs_cat_mapping_fixed_info'));
		
			$fixed_val = new ilTextInputGUI($this->lng->txt('ecs_cat_mapping_values'),'mapping_value');
			$fixed_val->setValue($this->rule->getMappingValue());
			$fixed_val->setMaxLength(255);
			$fixed_val->setSize(40);
			$fixed->addSubItem($fixed_val);
		
		$value->addOption($fixed);

		$duration = new ilRadioOption($this->lng->txt('ecs_cat_mapping_duration'),ilECSCategoryMappingRule::TYPE_DURATION);
		$duration->setInfo($this->lng->txt('ecs_cat_mapping_duration_info'));
		
			$dur_start = new ilDateTimeInputGUI($this->lng->txt('from'),'dur_begin');
			$dur_start->setDate($this->rule->getDateRangeStart());
			$duration->addSubItem($dur_start);
			
			$dur_end = new ilDateTimeInputGUI($this->lng->txt('to'),'dur_end');
			$dur_end->setDate($this->rule->getDateRangeEnd());
			$duration->addSubItem($dur_end);
		
		$value->addOption($duration);
		
		$this->form->addItem($value);
		
	}
	
	
	/**
	 * Show imported materials
	 *
	 * @access protected
	 */
	protected function imported()
	{
		global $ilUser;

		$this->tabs_gui->setSubTabActive('ecs_import');
		
		$rcourses = ilUtil::_getObjectsByOperations('rcrs','visible',$ilUser->getId(),-1);
		
	 	$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.ecs_imported.html','Services/WebServices/ECS');

		include_once './Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php';
		$tb = new ilToolbarGUI();
		
		if(count($rcourses))
		{
			$tb->addButton(
				$this->lng->txt('csv_export'),
				$this->ctrl->getLinkTarget($this,'exportImported')
			);
		}
		if($this->settings->isEnabled())
		{
			$tb->addButton(
				$this->lng->txt('ecs_read_remote_links'),
				$this->ctrl->getLinkTarget($this,'readAll')
			);		
		}
		$this->tpl->setVariable('ACTION_BUTTONS',$tb->getHTML());
		
	 	include_once('Services/WebServices/ECS/classes/class.ilECSImportedContentTableGUI.php');

 		$table_gui = new ilECSImportedContentTableGUI($this,'imported');
				
 		$table_gui->setTitle($this->lng->txt('ecs_imported_content'));
 		$table_gui->parse($rcourses);
		$this->tpl->setVariable('TBL_IMPORTED',$table_gui->getHTML());

		return true;
	}
	
	/**
	 * csv export of imported remote courses
	 *
	 * @access protected
	 * @return
	 */
	protected function exportImported()
	{
		global $ilObjDataCache,$ilUser;
		
		$rcourses = ilUtil::_getObjectsByOperations('rcrs','visible',$ilUser->getId(),-1);
		
		// Read participants
		include_once('./Modules/RemoteCourse/classes/class.ilObjRemoteCourse.php');
		include_once('./Services/WebServices/ECS/classes/class.ilECSCommunityReader.php');
		try
		{
			$reader = ilECSCommunityReader::_getInstance();
		}
		catch(ilECSConnectorException $e)
		{
			$reader = null;
		}
		
		// read obj_ids
		$ilObjDataCache->preloadReferenceCache($rcourses);
		$obj_ids = array();
		foreach($rcourses as $rcrs_ref_id)
		{
			$obj_id = $ilObjDataCache->lookupObjId($rcrs_ref_id);
			$obj_ids[$obj_id] = $obj_id; 
		}

		include_once('Services/Utilities/classes/class.ilCSVWriter.php');
		$writer = new ilCSVWriter();
		
		$writer->addColumn($this->lng->txt('title'));
		$writer->addColumn($this->lng->txt('description'));
		$writer->addColumn($this->lng->txt('ecs_imported_from'));			
		$writer->addColumn($this->lng->txt('ecs_field_courseID'));			
		$writer->addColumn($this->lng->txt('ecs_field_term'));			
		$writer->addColumn($this->lng->txt('ecs_field_lecturer'));			
		$writer->addColumn($this->lng->txt('ecs_field_courseType'));			
		$writer->addColumn($this->lng->txt('ecs_field_semester_hours'));			
		$writer->addColumn($this->lng->txt('ecs_field_credits'));			
		$writer->addColumn($this->lng->txt('ecs_field_room'));			
		$writer->addColumn($this->lng->txt('ecs_field_cycle'));			
		$writer->addColumn($this->lng->txt('ecs_field_begin'));			
		$writer->addColumn($this->lng->txt('ecs_field_end'));			
		$writer->addColumn($this->lng->txt('last_update'));	
		
		include_once('./Services/WebServices/ECS/classes/class.ilECSDataMappingSettings.php');
		$settings = ilECSDataMappingSettings::_getInstance();
		
		foreach($obj_ids as $obj_id)
		{
			include_once('./Services/AdvancedMetaData/classes/class.ilAdvancedMDValues.php');
			$values = ilAdvancedMDValues::_getValuesByObjId($obj_id);
			
			$writer->addRow();
			$writer->addColumn(ilObject::_lookupTitle($obj_id));
			$writer->addColumn(ilObject::_lookupDescription($obj_id));
			
			$mid = ilObjRemoteCourse::_lookupMID($obj_id);	
			if($reader and ($participant = $reader->getParticipantByMID($mid)))
			{
				$writer->addColumn($participant->getParticipantName());
			}
			$field = $settings->getMappingByECSName('courseID');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('term');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('lecturer');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('courseType');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('semester_hours');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('credits');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('room');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('cycle');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('begin');
			$writer->addColumn(isset($values[$field]) ?  ilFormat::formatUnixTime($values[$field],true) : '');
			
			$field = $settings->getMappingByECSName('end');
			$writer->addColumn(isset($values[$field]) ?  ilFormat::formatUnixTime($values[$field],true) : '');
			
			$writer->addColumn($ilObjDataCache->lookupLastUpdate($obj_id));
		}
		ilUtil::deliverData($writer->getCSVString(), date("Y_m_d")."_ecs_import.csv", "text/csv");	
	}
	
	/**
	 * Show released materials 
	 *
	 * @access protected
	 * @return
	 */
	protected function released()
	{
		global $ilUser;
		
		$this->tabs_gui->setSubTabActive('ecs_released');
		
		include_once('./Services/WebServices/ECS/classes/class.ilECSExport.php');
		$exported = ilECSExport::_getExportedIDs();

	 	$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.ecs_released.html','Services/WebServices/ECS');

		include_once './Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php';
		$tb = new ilToolbarGUI();
		
		if(count($exported))
		{
			$tb->addButton(
				$this->lng->txt('csv_export'),
				$this->ctrl->getLinkTarget($this,'exportReleased')
			);
		}
		if($this->settings->isEnabled())
		{
			$tb->addButton(
				$this->lng->txt('ecs_read_remote_links'),
				$this->ctrl->getLinkTarget($this,'readAll')
			);		
		}
		$this->tpl->setVariable('ACTION_BUTTONS',$tb->getHTML());
		
	 	include_once('Services/WebServices/ECS/classes/class.ilECSReleasedContentTableGUI.php');
 		$table_gui = new ilECSReleasedContentTableGUI($this,'released');
				
 		$table_gui->setTitle($this->lng->txt('ecs_released_content'));
 		$table_gui->parse($exported);
		$this->tpl->setVariable('TABLE_REL',$table_gui->getHTML());

		return true;
	
	}
	
	/**
	 * export released
	 *
	 * @access protected
	 * @return
	 */
	protected function exportReleased()
	{
		global $ilObjDataCache;
		
		include_once('./Services/WebServices/ECS/classes/class.ilECSExport.php');
		$exported = ilECSExport::_getExportedIDs();
		$ilObjDataCache->preloadObjectCache($exported);
		
		include_once('Services/Utilities/classes/class.ilCSVWriter.php');
		$writer = new ilCSVWriter();
		
		$writer->addColumn($this->lng->txt('title'));
		$writer->addColumn($this->lng->txt('description'));			
		$writer->addColumn($this->lng->txt('ecs_field_courseID'));			
		$writer->addColumn($this->lng->txt('ecs_field_term'));			
		$writer->addColumn($this->lng->txt('ecs_field_lecturer'));			
		$writer->addColumn($this->lng->txt('ecs_field_courseType'));			
		$writer->addColumn($this->lng->txt('ecs_field_semester_hours'));			
		$writer->addColumn($this->lng->txt('ecs_field_credits'));			
		$writer->addColumn($this->lng->txt('ecs_field_room'));			
		$writer->addColumn($this->lng->txt('ecs_field_cycle'));			
		$writer->addColumn($this->lng->txt('ecs_field_begin'));			
		$writer->addColumn($this->lng->txt('ecs_field_end'));			
		$writer->addColumn($this->lng->txt('last_update'));	
		
		include_once('./Services/WebServices/ECS/classes/class.ilECSDataMappingSettings.php');
		$settings = ilECSDataMappingSettings::_getInstance();

		foreach($exported as $obj_id)
		{
			include_once('./Services/AdvancedMetaData/classes/class.ilAdvancedMDValues.php');
			$values = ilAdvancedMDValues::_getValuesByObjId($obj_id);
			
			$writer->addRow();
			$writer->addColumn(ilObject::_lookupTitle($obj_id));
			$writer->addColumn(ilObject::_lookupDescription($obj_id));
			
			$field = $settings->getMappingByECSName('courseID');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('term');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('lecturer');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('courseType');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('semester_hours');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('credits');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('room');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('cycle');
			$writer->addColumn(isset($values[$field]) ? $values[$field] : '');
			
			$field = $settings->getMappingByECSName('begin');
			$writer->addColumn(isset($values[$field]) ?  ilFormat::formatUnixTime($values[$field],true) : '');
			
			$field = $settings->getMappingByECSName('end');
			$writer->addColumn(isset($values[$field]) ?  ilFormat::formatUnixTime($values[$field],true) : '');
			
			$writer->addColumn($ilObjDataCache->lookupLastUpdate($obj_id));
		}

		ilUtil::deliverData($writer->getCSVString(), date("Y_m_d")."_ecs_export.csv", "text/csv");	
	}
	
	
	/**
	 * get options for field selection
	 * @param array array of field objects
	 * @access protected
	 */
	protected function prepareFieldSelection($fields)
	{
		include_once('./Services/AdvancedMetaData/classes/class.ilAdvancedMDRecord.php');
		
		$options[0] = $this->lng->txt('ecs_ignore_field');
		foreach($fields as $field)
		{
			$field = ilAdvancedMDFieldDefinition::_getInstanceByFieldId($field);
			$title = ilAdvancedMDRecord::_lookupTitle($field->getRecordId());
			$options[$field->getFieldId()] = $title.': '.$field->getTitle();		
		}
		return $options;
	}
	


	/**
	 * Init settings
	 *
	 * @access protected
	 */
	protected function initSettings()
	{	
		include_once('Services/WebServices/ECS/classes/class.ilECSSetting.php');
		$this->settings = ilECSSetting::_getInstance();
	}
	
	/**
	 * set sub tabs
	 *
	 * @access protected
	 */
	protected function setSubTabs()
	{
		$this->tabs_gui->clearSubTabs();
		
		$this->tabs_gui->addSubTabTarget("ecs_settings",
			$this->ctrl->getLinkTarget($this,'settings'),
			"settings",get_class($this));
		
		// Disable all other tabs, if server hasn't been configured. 
		if(!$this->settings->isEnabled())
		{
			return true;
		}

		$this->tabs_gui->addSubTabTarget("ecs_communities",
			$this->ctrl->getLinkTarget($this,'communities'),
			"communities",get_class($this));
			
		$this->tabs_gui->addSubTabTarget('ecs_mappings',
			$this->ctrl->getLinkTarget($this,'mappings'),
			'mappings',get_class($this));
			
		$this->tabs_gui->addSubTabTarget('ecs_category_mapping',
			$this->ctrl->getLinkTarget($this,'categoryMapping'));
			
		$this->tabs_gui->addSubTabTarget('ecs_import',
			$this->ctrl->getLinkTarget($this,'imported'));

		$this->tabs_gui->addSubTabTarget('ecs_released',
			$this->ctrl->getLinkTarget($this,'released'));

	}
	
	/**
	 * get global role array
	 *
	 * @access protected
	 */
	private function prepareRoleSelect()
	{
		global $rbacreview,$ilObjDataCache;
		
		$global_roles = ilUtil::_sortIds($rbacreview->getGlobalRoles(),
			'object_data',
			'title',
			'obj_id');
		
		$select[0] = $this->lng->txt('links_select_one');
		foreach($global_roles as $role_id)
		{
			$select[$role_id] = ilObject::_lookupTitle($role_id);
		}
		return $select;
	}
	
	private function buildPath($a_ref_id)
	{
		$loc = new ilLocatorGUI();
		$loc->setTextOnly(false);
		$loc->addContextItems($a_ref_id);
		
		return $loc->getHTML();
	}
	
}

?>