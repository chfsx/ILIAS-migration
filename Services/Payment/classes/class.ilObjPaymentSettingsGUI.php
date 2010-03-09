<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
 
/**
* Class ilObjPaymentSettingsGUI
*
* @author Stefan Meyer <meyer@leifos.com> 
* @author Jens Conze <jc@databay.de> 
* @author Jesper G�dvad <jesper@ilias.dk>
* @version $Id$
* 
* @ilCtrl_Calls ilObjPaymentSettingsGUI: ilPermissionGUI, ilShopTopicsGUI, ilPageObjectGUI
* 
* @extends ilObjectGUI
* @package ilias-core
*
*/

require_once './classes/class.ilObjectGUI.php';
include_once './Services/Payment/classes/class.ilShopVatsList.php';
include_once './Services/Payment/classes/class.ilPaymentPrices.php';
include_once './Services/Payment/classes/class.ilPaymentObject.php';
include_once './Services/Payment/classes/class.ilFileDataShop.php';
include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
include_once './Services/Utilities/classes/class.ilConfirmationGUI.php';
include_once './Services/Payment/classes/class.ilPaymentBookings.php';
include_once './Services/Payment/classes/class.ilGeneralSettings.php';
include_once './Services/Payment/classes/class.ilPaymentCurrency.php';

class ilObjPaymentSettingsGUI extends ilObjectGUI
{
	var $user_obj = null;
	var $pobject = null;

	var $section;
	var $mainSection;
	var $genSetData = null;

	/**
	* Constructor
	* @access public
	*/
	function ilObjPaymentSettingsGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output = true)
	{
		global $ilias;

		$this->user_obj = $ilias->account;

		$this->pobject = new ilPaymentObject($this->user_obj);
		
		$genSet = new ilGeneralSettings();
		$this->genSetData = $genSet->getAll();

		$this->type = 'pays';
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);

		$this->SECTION_GENERAL = 1;
		$this->SECTION_PAYPAL = 2;
		$this->SECTION_EPAY = 12;
		$this->SETTINGS = 3;
		$this->OTHERS = 0;
		$this->STATISTIC = 4;
		$this->VENDORS = 5;
//		$this->CURRENCIES = 14; #TODO: CURRENCY not finished yet
		$this->PAY_METHODS = 6;
		$this->OBJECTS = 7;
		$this->SECTION_BMF = 8;
		$this->TOPICS = 9;
		$this->VATS = 10;
		$this->SECTION_VATS = 11;
		$this->SECTION_ERP = 13;
		
		$this->lng->loadLanguageModule('payment');
	}
	
	function executeCommand()
	{		
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();
		$this->prepareOutput();

		switch($next_class)
		{
			case 'ilpermissiongui':
				include_once('Services/AccessControl/classes/class.ilPermissionGUI.php');
				$perm_gui = new ilPermissionGUI($this);
				$ret = $this->ctrl->forwardCommand($perm_gui);
				break;
				
			case 'ilpageobjectgui':
				$this->prepareOutput();
				$ret = $this->forwardToPageObject();
				if($ret != '')
				{
					$this->tpl->setContent($ret);
				}				
				break;
				
			case 'ilshoptopicsgui':
				include_once './Services/Payment/classes/class.ilShopTopicsGUI.php';
				$topics_gui = new ilShopTopicsGUI($this);
				$ret = $this->ctrl->forwardCommand($topics_gui);
				break;
			
			default:
				if ($cmd == '' || $cmd == 'view')
				{
					$cmd = 'generalSettings';
				}
			
				switch ($cmd)
				{
					case 'vendors' :
					case 'searchUser' :
					case 'search' :
					case 'performSearch' :
					case 'addVendor' :
					case 'exportVendors' :
					case 'performDeleteVendors' :
					case 'cancelDeleteVendors' :
					case 'performEditVendor' :	$this->__setSection($this->OTHERS);
												$this->__setMainSection($this->STATISTIC);
												$this->tabs_gui->setTabActive('vendors');
												break;
					case 'statistic' :
					case 'editStatistic' :
					case 'updateStatistic' :
					case 'deleteStatistic' :
					case 'performDelete' :
					case 'resetFilter' :
					case 'exportVendors' :
					case 'addCustomer' :
					case 'saveCustomer' :
					case 'showObjectSelector' :
					case 'searchUserSP' :
					case 'performSearchSP' :	$this->__setSection($this->OTHERS);
												$this->__setMainSection($this->STATISTIC);
												$this->tabs_gui->setTabActive('bookings');
												break;
					case 'updateObjectDetails' :
					case 'deleteObject' :
					case 'performObjectDelete' :
					case 'objects' :
					case 'editPrices' :
					case 'addPrice' :					
					case 'editDetails' :
					case 'resetObjectFilter' :
												$this->__setSection($this->OTHERS);
												$this->__setMainSection($this->OBJECTS);
												$this->tabs_gui->setTabActive('objects');
												break;
					case 'saveGeneralSettings' :
					case 'generalSettings' :	$this->__setSection($this->SECTION_GENERAL);
												$this->__setMainSection($this->SETTINGS);
												$this->tabs_gui->setTabActive('settings');
												break;
					case 'saveBmfSettings' :
					case 'bmfSettings' :		$this->__setSection($this->SECTION_BMF);
												$this->__setMainSection($this->SETTINGS);
												$this->tabs_gui->setTabActive('settings');
												break;
					case 'savePaypalSettings' :
					case 'paypalSettings' :		$this->__setSection($this->SECTION_PAYPAL);
												$this->__setMainSection($this->SETTINGS);
												$this->tabs_gui->setTabActive('settings');
												break;
					case 'saveEPaySettings' :
					case 'epaySettings' :		$this->__setSection($this->SECTION_EPAY);
												$this->__setMainSection($this->SETTINGS);
												$this->tabs_gui->setTabActive('settings');
												break;
					case 'savePayMethods' :		$this->__setSection($this->OTHERS);
												$this->__setMainSection($this->PAY_METHODS);
												$this->tabs_gui->setTabActive('pay_methods');
												break;
					case 'gateway' :			if ($_POST['action'] == 'editVendorObject' ||
													$_POST['action'] == 'deleteVendorsObject')
												{
													$this->__setSection($this->OTHERS);
													$this->__setMainSection($this->STATISTIC);
													$this->tabs_gui->setTabActive('vendors');
												}
												break;
												
		
					case 'deleteVat' :
					case 'newVat':
					case 'insertVat':

					case 'updateVat':
					case 'performDeleteVat':
					case 'confirmDeleteVat':
					case 'createVat':
					case 'saveVat':
					case 'editVat':			
					case 'vats' :						
									$this->__setSection($this->OTHERS);
									$this->__setMainSection($this->VATS);
									$this->tabs_gui->setTabActive('vats');					
								break;
								
					case 'saveERPsettings' :
					case 'delERPpreview': 
					case 'testERPsettings' :
					case 'erpSettings' : $this->__setSection($this->SECTION_ERP);
				                        $this->__setMainSection($this->SETTINGS);
				                        $this->tabs_gui->setTabActive('settings');
									break;
#TODO: CURRENCY not finished yet
/*					case 'currencies':
					case 'addCurrency':
					case 'editCurrency':
					case 'deleteCurrency':
					case 'performDeleteCurrency':
					case 'updateCurrency':
								$this->__setSection($this->OTHERS);
								$this->__setMainSection($this->CURRENCIES);
								$this->tabs_gui->setTabActive('currencies');						
							break;    
*/						
					default :					$this->__setSection($this->OTHERS);
												$this->__setMainSection($this->OTHERS);
												break;
				}
				$cmd .= 'Object';

				$this->__buildSettingsButtons();

				$this->$cmd();

				break;
		}
		return true;
	}
	
	public function forwardToPageObject()
	{	
		global $ilTabs;
		
		if(!(int)$_GET['pobject_id'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));
			return $this->showObjects();
		}
		$this->ctrl->setParameter($this, 'pobject_id', (int)$_GET['pobject_id']);
		$this->__initPaymentObject((int)$_GET['pobject_id']);		
		
		$this->lng->loadLanguageModule('content');
		
		$ilTabs->clearTargets();
		$ilTabs->clearSubTabs();
		$ilTabs->setBackTarget($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, 'editDetails'));


		include_once 'Services/COPage/classes/class.ilPageObject.php';
		include_once 'Services/COPage/classes/class.ilPageObjectGUI.php';
		include_once('./Services/Style/classes/class.ilObjStyleSheet.php');
		
		$this->tpl->setVariable('LOCATION_CONTENT_STYLESHEET', ilObjStyleSheet::getContentStylePath(0));

		if(!ilPageObject::_exists('shop', $this->pobject->getPobjectId()))
		{
			// doesn't exist -> create new one
			$new_page_object = new ilPageObject('shop');
			$new_page_object->setParentId(0);
			$new_page_object->setId($this->pobject->getPobjectId());
			$new_page_object->createFromXML();
		}
				
		$this->ctrl->setReturnByClass('ilpageobjectgui', 'edit');

		$page_gui = new ilPageObjectGUI('shop', $this->pobject->getPobjectId());
		$this->ctrl->setParameter($page_gui, 'pobject_id', (int)$_GET['pobject_id']);
		$page_gui->setIntLinkHelpDefault('StructureObject', $this->pobject->getPobjectId());
		$page_gui->setTemplateTargetVar('ADM_CONTENT');
		$page_gui->setLinkXML('');
		$page_gui->setFileDownloadLink($this->ctrl->getLinkTargetByClass(array('ilpageobjectgui'), 'downloadFile'));
		$page_gui->setFullscreenLink($this->ctrl->getLinkTargetByClass(array('ilpageobjectgui'), 'displayMediaFullscreen'));
		$page_gui->setSourcecodeDownloadScript($this->ctrl->getLinkTargetByClass(array('ilpageobjectgui'), 'download_paragraph'));
		$page_gui->setPresentationTitle('');
		$page_gui->setTemplateOutput(false);
		$page_gui->setHeader('');
		$page_gui->setEnabledRepositoryObjects(false);
		$page_gui->setEnabledFileLists(true);
		$page_gui->setEnabledMaps(true);
		$page_gui->setEnabledPCTabs(true);

		return $this->ctrl->forwardCommand($page_gui);
	}
	
	function saveBmfSettingsObject()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}
		
		include_once './Services/Payment/classes/class.ilBMFSettings.php';
		
		$this->error = '';
		
		$bmfSetObj = ilBMFSettings::getInstance();			
		
		$bmfSetObj->setClientId(ilUtil::stripSlashes($_POST['mandantNr']));
		$bmfSetObj->setBewirtschafterNr(ilUtil::stripSlashes($_POST['bewirtschafterNr']));
		$bmfSetObj->setHaushaltsstelle(ilUtil::stripSlashes($_POST['haushaltsstelle']));
		$bmfSetObj->setObjectId(ilUtil::stripSlashes($_POST['objektNr']));
		$bmfSetObj->setKennzeichenMahnverfahren(ilUtil::stripSlashes($_POST['kennzeichenMahnverfahren']));
		$bmfSetObj->setWaehrungsKennzeichen(ilUtil::stripSlashes($_POST['waehrungskennzeichen']));
		$bmfSetObj->setEpaymentServer(ilUtil::stripSlashes($_POST['ePaymentServer']));
		$bmfSetObj->setClientCertificate(ilUtil::stripSlashes($_POST['clientCertificate']));
		$bmfSetObj->setCaCertificate(ilUtil::stripSlashes($_POST['caCertificate']));
		$bmfSetObj->setTimeout(ilUtil::stripSlashes($_POST['timeOut']));
		
		if ($_POST['mandantNr'] == '' ||
			$_POST['bewirtschafterNr'] == '' ||
			$_POST['haushaltsstelle'] == '' ||
			$_POST['objektNr'] == '' ||
			$_POST['kennzeichenMahnverfahren'] == '' ||
			$_POST['waehrungskennzeichen'] == '' ||
			$_POST['ePaymentServer'] == '' ||
			$_POST['clientCertificate'] == '' ||
			$_POST['caCertificate'] == '' ||			
			$_POST['timeOut'] == '')
		{
			$this->error = $this->lng->txt('pays_bmf_settings_not_valid');
			ilUtil::sendFailure($this->error);
			$this->bmfSettingsObject();
			return;
		}
		
		$bmfSetObj->save();
				
		$this->bmfSettingsObject();

		ilUtil::sendSuccess($this->lng->txt('pays_updated_bmf_settings'));

		return true;
	}
	
	function bmfSettingsObject()
	{	
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}
		include_once './Services/Payment/classes/class.ilBMFSettings.php';


		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.main_view.html','Services/Payment');
		
		$bmfSetObj = ilBMFSettings::getInstance();		
						
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this, 'saveBmfSettings'));
		$form->setTitle($this->lng->txt('pays_bmf_settings'));
		
		$form->addCommandButton('saveBmfSettings',$this->lng->txt('save'));
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_bmf_client_id'), 'mandantNr');
		$formItem->setValue($bmfSetObj->getClientId());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_bmf_bewirtschafter_nr'), 'bewirtschafterNr');
		$formItem->setValue($bmfSetObj->getBewirtschafterNr());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_bmf_haushaltsstelle'), 'haushaltsstelle');
		$formItem->setValue($bmfSetObj->getHaushaltsstelle());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_bmf_object_id'), 'objektNr');
		$formItem->setValue($bmfSetObj->getObjectId());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_bmf_kennzeichen_mahnverfahren'), 'kennzeichenMahnverfahren');
		$formItem->setValue($bmfSetObj->getKennzeichenMahnverfahren());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_bmf_waehrungskennzeichen'), 'waehrungskennzeichen');
		$formItem->setValue($bmfSetObj->getWaehrungsKennzeichen());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_bmf_epayment_server'), 'ePaymentServer');
		$formItem->setValue($bmfSetObj->getEpaymentServer());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_bmf_client_certificate'), 'clientCertificate');
		$formItem->setValue($bmfSetObj->getClientCertificate());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_bmf_ca_certificate'), 'caCertificate');
		$formItem->setValue($bmfSetObj->getCaCertificate());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_bmf_timeout'), 'timeOut');
		$formItem->setValue($bmfSetObj->getTimeOut());
		$form->addItem($formItem);				
				
		$this->tpl->setVariable('FORM',$form->getHTML());
	}
	

	function updateDetailsObject()
	{ 
		if(!$_GET['pobject_id'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->objectObjects();
			return true;
		}
		
		$this->__initPaymentObject((int) $_GET['pobject_id']);
		$this->ctrl->setParameter($this,'pobject_id',(int) $_GET['pobject_id']);

		// read old settings
		$old_pay_method = $this->pobject->getPayMethod();
		$old_status = $this->pobject->getStatus();
		$old_vat_id = $this->pobject->getVatId();
		
		// check status changed from not_buyable
		if($old_status == $this->pobject->STATUS_NOT_BUYABLE and
		   (int) $_POST['status'] != $old_status)
		{
			// check pay_method edited
			switch((int) $_POST['pay_method'])
			{
				case $this->pobject->PAY_METHOD_NOT_SPECIFIED:
					ilUtil::sendInfo($this->lng->txt('paya_select_pay_method_first'));
					$this->editDetailsObject();

					return false;
				default:
					;
			}
			// check minimum one price
			$prices_obj = new ilPaymentPrices((int) $_GET['pobject_id']);
			if(!count($prices_obj->getPrices()))
			{
				ilUtil::sendInfo($this->lng->txt('paya_edit_prices_first'));
				$this->editDetailsObject();
						
				return false;
			}				
		}
		

		$this->pobject->setStatus((int) $_POST['status']);
		$this->pobject->setVendorId((int) $_POST['vendor']);
		$this->pobject->setPayMethod((int) $_POST['pay_method']);
		$this->pobject->setTopicId((int) $_POST['topic_id']);
		$this->pobject->setVatId((int) $_POST['vat_id']); 
		
		$this->pobject->update();

		ilUtil::sendInfo($this->lng->txt('paya_details_updated'));
		$this->editDetailsObject();

		return true;
	}
	
	function editPricesObject($a_show_delete = false)
	{
		global $ilToolbar;
		
		if($a_show_delete == false) unset($_SESSION['price_ids']);

	
		include_once './Services/Table/classes/class.ilTableGUI.php';



		$_SESSION['price_ids'] = $_SESSION['price_ids'] ? $_SESSION['price_ids'] : array();

		if(!$_GET['pobject_id'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->objectsObject();
			return true;
		}
		$this->ctrl->setParameter($this,'pobject_id',(int) $_GET['pobject_id']);

		$ilToolbar->addButton($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, 'objects'));
		$ilToolbar->addButton($this->lng->txt('paya_edit_details'), $this->ctrl->getLinkTarget($this, 'editDetails'));
		$ilToolbar->addButton($this->lng->txt('paya_edit_prices'), $this->ctrl->getLinkTarget($this, 'editPrices'));

		$this->__initPaymentObject((int) $_GET['pobject_id']);

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');

		$price_obj = new ilPaymentPrices((int) $_GET['pobject_id']);
		$prices = $price_obj->getPrices();

		// No prices created
		if(!count($prices))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_price_available'));
			$ilToolbar->addButton($this->lng->txt('paya_add_price'), $this->ctrl->getLinkTarget($this, 'addPrice'));

			return true;
		}
		// Show confirm delete
		if($a_show_delete)
		{	
			$oConfirmationGUI = new ilConfirmationGUI();
			
			// set confirm/cancel commands
			$oConfirmationGUI->setFormAction($this->ctrl->getFormAction($this,"performDeletePrice"));
			$oConfirmationGUI->setHeaderText($this->lng->txt("paya_sure_delete_selected_prices"));
			$oConfirmationGUI->setCancel($this->lng->txt("cancel"), "editPrices");
			$oConfirmationGUI->setConfirm($this->lng->txt("confirm"), "performDeletePrice");			
			
			foreach($prices as $price)
			{
				$delete_row = '';
				$currency = ilPaymentCurrency::_getCurrency($price['currency']);
				
				if(in_array($price['price_id'],$_SESSION['price_ids']))
				{
					if ($price['unlimited_duration'] == '1') 
					{
						$tmp_price = $this->lng->txt('unlimited_duration');
					}
					else
					{
						$tmp_price = $price['duration'].' '.$this->lng->txt('paya_months');
					}
					$delete_row = ''.$tmp_price.'   '.
									ilFormat::_getLocalMoneyFormat($price['price']).' '.
									$this->genSetData['currency_unit'];
//TODO CURRENCY									$currency['unit'];
									
					$oConfirmationGUI->addItem('',$delete_row, $delete_row);
				}
			}
				
			$this->tpl->setVariable('CONFIRMATION', $oConfirmationGUI->getHTML());		
			
			return true;			
		}			

		// Fill table cells
		$tpl = new ilTemplate('tpl.table.html',true,true);

		// set table header
		$tpl->setCurrentBlock('tbl_form_header');
		
		$tpl->setVariable('FORMACTION',$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$counter = 0;
		foreach($prices as $price)
		{
			$currency = ilPaymentCurrency::_getCurrency($price['currency']);
			if($a_show_delete == true ) 
			{	
				$this->ctrl->setParameter($this, 'show_delete', 'true');
				
				if(in_array($price['price_id'],$_SESSION['price_ids']))
				{
			
					$data[$counter]['price_id'] = '';
					$data[$counter]['duration'] =$price['duration']  ;
					$data[$counter]['month'] = $this->lng->txt('paya_months');
					
					$data[$counter]['unlimited_duration'] = ilUtil::formCheckBox($price['unlimited_duration'] ? 1 : 0,
						'duration_ids[]', (int)$price['price_id']);	
					
					$data[$counter]['price'] = ilFormat::_getLocalMoneyFormat($price['price']);
//TODO: CURRENCY					$data[$counter]['currency_unit'] = $currency['unit'];
					$data[$counter]['currency_unit'] = $this->genSetData['currency_unit'];
				}
			}
			else
			{
				$data[$counter]['price_id'] = ilUtil::formCheckBox(in_array($price['price_id'],$_SESSION['price_ids']) ? 1 : 0,
					'price_ids[]', $price['price_id']);	
				
				$data[$counter]['duration'] = ilUtil::formInput('prices['.$price['price_id'].'][duration]',$price['duration']);
				$data[$counter]['month'] = $this->lng->txt('paya_months');
				
				$data[$counter]['unlimited_duration'] = ilUtil::formCheckBox($price['unlimited_duration'] ? 1 : 0,
					'duration_ids[]', (int)$price['price_id']);	
				
				$data[$counter]['price'] = ilUtil::formInput('prices['.$price['price_id'].'][price]', ilFormat::_getLocalMoneyFormat($price['price']));
			#	$data[$counter]['currency_unit'] = $currency['unit']; #
			$data[$counter]['currency_unit'] = $this->genSetData['currency_unit'];
			}
			++$counter;
		}
		$this->__editPricesTable($data);	
	
		return true;
	}	
		
	function __editPricesTable($a_result_set)
	{
		$tpl = new ilTemplate('tpl.table.html',true,true);
		
		$parmeter = $this->ctrl->getParameterArray($this, 'show_delete');
		
		if(!$parmeter['show_delete'])
		{
			// SET FOOTER
			$tpl->setCurrentBlock("tbl_action_btn");
			$tpl->setVariable("BTN_NAME","deletePrice");
			$tpl->setVariable("BTN_VALUE",$this->lng->txt("paya_delete_price"));
			$tpl->parseCurrentBlock();
	
			$tpl->setCurrentBlock("plain_buttons");
			$tpl->setVariable("PBTN_NAME","addPrice");
			$tpl->setVariable("PBTN_VALUE",$this->lng->txt("paya_add_price"));
			$tpl->parseCurrentBlock();
			
			$tpl->setCurrentBlock("plain_buttons");
			$tpl->setVariable("PBTN_NAME","updatePrice");
			$tpl->setVariable("PBTN_VALUE",$this->lng->txt("paya_update_price"));
			$tpl->parseCurrentBlock();
			
			$tpl->setCurrentBlock("tbl_action_row");
			$tpl->setVariable("TPLPATH",$this->tpl->tplPath);
			$tpl->setVariable("COLUMN_COUNTS",6);
			$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
			$tpl->parseCurrentBlock();
		}
		$tbl = new ilTableGUI();
		$tbl->setTemplate($tpl);

		// SET FORMAACTION
		$tpl->setCurrentBlock("tbl_form_header");
		$this->ctrl->setParameter($this, 'cmd', 'editprices');
		
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tmp_obj = ilObjectFactory::getInstanceByRefId($this->pobject->getRefId());		
		$tbl->setTitle($tmp_obj->getTitle(),
					   'icon_'.$tmp_obj->getType().'_b.gif',
					   $this->lng->txt('objs_'.$tmp_obj->getType()));
					   		
		$tbl->setHeaderNames(array( '',
									$this->lng->txt("duration"),
								  	'',
								 	$this->lng->txt("unlimited_duration"),
								   	$this->lng->txt("price_a"),
								  	''),
								   	'');
		$header_params = $this->ctrl->getParameterArray($this,'');
		$tbl->setHeaderVars(array(	"price_id",
									"duration",
									"month",
									"unlimited_duration",
									"price",
									"currency_unit",
									"options"),$header_params);
		$tbl->setColumnWidth(array('5%', "10%","10%","15%","15%","50%"));

		$offset = $_GET["offset"];
		if($_GET["sort_by"] == NULL) $order = 'duration'; 
		else $order = $_GET["sort_by"]; 
		$direction = $_GET["sort_order"]; 

		$tbl->setOrderColumn($order);
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setMaxCount(count($a_result_set));
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		$tbl->setData($a_result_set);

		$tbl->render();
		
		if(!$parmeter['show_delete'])
		{
			$this->tpl->setVariable("TABLE",$tbl->tpl->get());
			return true;
		}

		return true;
	}	
			
	function addPriceObject()
	{
		global $ilToolbar;
		
		if(!$_GET['pobject_id'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->showObjects();
			return true;
		}

		include_once './Services/Payment/classes/class.ilGeneralSettings.php';

		$genSet = new ilGeneralSettings();

		$this->ctrl->setParameter($this,'pobject_id',(int) $_GET['pobject_id']);

		$this->__initPaymentObject((int) $_GET['pobject_id']);

		$ilToolbar->addButton($this->lng->txt('paya_edit_details'), $this->ctrl->getLinkTarget($this, 'editDetails'));
		$ilToolbar->addButton($this->lng->txt('paya_edit_prices'), $this->ctrl->getLinkTarget($this, 'editPrices'));
	
		$tmp_obj = ilObjectFactory::getInstanceByRefId($this->pobject->getRefId());		
	
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');
		
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->lng->txt('paya_add_price_title'));
		
		// object_title
		$oTitle = new ilNonEditableValueGUI($this->lng->txt('title'));
		$oTitle->setValue($tmp_obj->getTitle());
		$form->addItem($oTitle);
			
		// duration
		$oDuration = new ilTextInputGUI();
		$oDuration->setTitle($this->lng->txt('duration'));
		$oDuration->setValue($_POST['duration']);
		$oDuration->setInfo($this->lng->txt('paya_months'));
		$oDuration->setPostVar('duration');
		$form->addItem($oDuration);
		
		// unlimited duration
		$oUnlimitedDuration = new ilCheckboxInputGUI($this->lng->txt('unlimited_duration'), 'unlimited_duration');
		$oUnlimitedDuration->setChecked($_POST['unlimited_duration'] == 1);
		$form->addItem($oUnlimitedDuration);
		
		// price
		$oPrice = new ilTextInputGUI();
		$oPrice->setTitle($this->lng->txt('price_a'));
		$oPrice->setValue($_POST['price']);
		$oPrice->setPostVar('price');
		$oPrice->setRequired(true);
		$form->addItem($oPrice);
	
		 // currency
		// TODO show curency selector
		 
#TODO: CURRENCY not finished yet
/*		$objCurrency = new ilPaymentCurrency();
		$currencies = $objCurrency->_getAvailableCurrencies();
		
		foreach($currencies as $currency)
		{
			$currency_options[$currency['currency_id']] = $currency['unit'].' / '.$currency['subunit'];
		}

		
		$oCurrency = new ilSelectInputGUI($this->lng->txt('currency'), 'currency_id');
		$oCurrency->setOptions($currency_options);
		
		$oCurrency->setValue($_SESSION['pay_objects']['currency_value']);
		$oCurrency->setPostVar('currency_id');
 */
		$currency_options = $this->genSetData['currency_unit'] .'/'.$this->genSetData['currency_subunit']; 
		$oCurrency = new ilNonEditableValueGUI($this->lng->txt('currency'));
		$oCurrency->setValue($currency_options);
		$form->addItem($oCurrency);
/**/		
		$form->addCommandButton('performAddPrice',$this->lng->txt('paya_add_price'));
		$form->addCommandButton('editPrices', $this->lng->txt('cancel'));		
		$this->tpl->setVariable('FORM',$form->getHTML());

		return true;
	}

	function performAddPriceObject()
	{
		if(!$_GET['pobject_id'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->objectsObject();
			return true;
		}


		$currency = ilPaymentCurrency::_getAvailableCurrencies();

		$prices = new ilPaymentPrices((int) $_GET['pobject_id']);

		$prices->setUnlimitedDuration((int)$_POST['unlimited_duration']);	

		if($_POST['unlimited_duration'] == '1')
		{
			$prices->setUnlimitedDuration(1);
		}

		$prices->setDuration($_POST['duration']);
		$prices->setPrice($_POST['price']);
		$prices->setCurrency($currency['currency_id']); //test

		if(!$prices->validate())
		{
			ilUtil::sendInfo($this->lng->txt('paya_price_not_valid'));
			$this->addPriceObject();

			return true;
		}
		$prices->add();

		ilUtil::sendInfo($this->lng->txt('paya_added_new_price'));
		$this->editPricesObject();

		return true;
	}		

	function performDeletePriceObject()
	{
		if(!$_GET['pobject_id'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->objectsObject();
			return true;
		}

		if(!count($_SESSION['price_ids']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_prices_selected'));
			
			$this->editPricesObject();
			return true;
		}
		
		$prices = new ilPaymentPrices((int) $_GET['pobject_id']);

		foreach($_SESSION['price_ids'] as $price_id)
		{
			if($prices->delete($price_id))
			ilUtil::sendInfo($this->lng->txt('paya_deleted_selected_prices'));
			
		}

		// check if it was last price otherwise set status to 'not_buyable'
		if(!count($prices->getPrices()))
		{
			$this->__initPaymentObject((int) $_GET['pobject_id']);

			$this->pobject->setStatus($this->pobject->STATUS_NOT_BUYABLE);
			$this->pobject->update();
			
			ilUtil::sendInfo($this->lng->txt('paya_deleted_last_price'));
		}
		unset($prices);
		unset($_SESSION['price_ids']);
		
		return $this->editPricesObject();
	}

	function deletePriceObject()
	{
		if(!$_GET['pobject_id'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->objectsObject();
			return true;
		}

		if(!count($_POST['price_ids']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_prices_selected'));
			
			$this->editPricesObject();
			return true;
		}
		$_SESSION['price_ids'] = $_POST['price_ids'];

		$this->editPricesObject(true);
		return true;
	}	

	function updatePriceObject()
	{
		if(!$_GET['pobject_id'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->objectsObject();
			return true;
		}
		$po = new ilPaymentPrices((int) $_GET['pobject_id']);

		$this->ctrl->setParameter($this,'pobject_id',(int) $_GET['pobject_id']);

		// validate
		foreach($_POST['prices'] as $price_id => $price)
		{
			$old_price = $po->getPrice($price_id);

			$po->setDuration($price['duration']);
			$po->setPrice($price['price']);
			$po->setCurrency($old_price['currency']);

			if(!$po->validate())
			{
				$error = true;
			}
		}
		if($error)
		{
			ilUtil::sendInfo($this->lng->txt('paya_insert_only_numbers'));

			$this->editPricesObject();
			return false;
		}
		
		
		foreach($_POST['prices'] as $price_id => $price)
		{
			$old_price = $po->getPrice($price_id);

			if(isset($_POST['duration_ids']))
			{
	 			$search = in_array((string)$price_id, $_POST['duration_ids']);
	
				 if($_POST['duration_ids'] == NULL)
				{
					$po->setUnlimitedDuration(0);		
					$po->setDuration($price['duration']);	
				}

				else if( $search = in_array((string)$price_id, $_POST['duration_ids']))
				{
					$po->setUnlimitedDuration(1);		
					$po->setDuration(0);	
				}
				else 
				{
					$po->setUnlimitedDuration(0);	
				}	
			}

			$po->setDuration($price['duration']);	
			$po->setPrice($price['price']);
			$po->setCurrency($old_price['currency']);

			$po->update($price_id);
		}
		ilUtil::sendSuccess($this->lng->txt('paya_updated_prices'));
		$this->editPricesObject();

		return true;
	}
	
//	function editObjectObject($a_show_confirm = false)
	function editDetailsObject($a_show_confirm = false)
	{
		global $ilToolbar;
		
		if(!(int)$_GET['pobject_id'])
		{	
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));
			return $this->showObjects();
		}
			
		$this->__initPaymentObject((int)$_GET['pobject_id']);

		$this->ctrl->setParameter($this,'pobject_id', (int)$_GET['pobject_id']);

		$ilToolbar->addButton($this->lng->txt('paya_edit_details'), $this->ctrl->getLinkTarget($this, 'editDetails'));
		$ilToolbar->addButton($this->lng->txt('paya_edit_prices'), $this->ctrl->getLinkTarget($this, 'editPrices'));
		$ilToolbar->addButton($this->lng->txt('pay_edit_abstract'), $this->ctrl->getLinkTargetByClass(array('ilpageobjectgui'), 'edit'));
		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');
		if($a_show_confirm)
		{
			$this->tpl->setCurrentBlock('confirm_delete');
			$this->tpl->setVariable('CONFIRM_FORMACTION',$this->ctrl->getFormAction($this));
			$this->tpl->setVariable('TXT_CANCEL',$this->lng->txt('cancel'));
			$this->tpl->setVariable('CONFIRM_CMD','performDelete');
			$this->tpl->setVariable('TXT_CONFIRM',$this->lng->txt('confirm'));
			$this->tpl->parseCurrentBlock();
		}
		
		$tmp_obj = ilObjectFactory::getInstanceByRefId($this->pobject->getRefId());


		$oForm = new ilPropertyFormGUI();
		$oForm->setFormAction($this->ctrl->getFormAction($this, 'updateDetails'));
		$oForm->setTitle($tmp_obj->getTitle());
		$oForm->setTitleIcon(ilUtil::getImagePath('icon_'.$tmp_obj->getType().'_b.gif'));
		
		// repository path
		$oPathGUI = new ilNonEditableValueGUI($this->lng->txt('path'));
		$oPathGUI->setValue($this->__getHTMLPath($this->pobject->getRefId()));
		$oForm->addItem($oPathGUI);
		
		// number of purchasers
		$oPurchasersGUI = new ilNonEditableValueGUI($this->lng->txt('paya_count_purchaser'));
		$oPurchasersGUI->setValue(ilPaymentBookings::_getCountBookingsByObject((int)$_GET['pobject_id']));
		$oForm->addItem($oPurchasersGUI);
		
		// vendors
		$oVendorsGUI = new ilSelectInputGUI($this->lng->txt('paya_vendor'), 'vendor');		
		$oVendorsGUI->setOptions($this->__getVendors());
		$oVendorsGUI->setValue($this->pobject->getVendorId());
		$oForm->addItem($oVendorsGUI);
		
		// status
		$oStatusGUI = new ilSelectInputGUI($this->lng->txt('status'), 'status');
		$oStatusGUI->setOptions($this->__getStatus());
		$oStatusGUI->setValue($this->pobject->getStatus());
		$oForm->addItem($oStatusGUI);

		// pay methods
		$oPayMethodsGUI = new ilSelectInputGUI($this->lng->txt('paya_pay_method'), 'pay_method');
		$oPayMethodsGUI->setOptions(ilPayMethods::getPayMethodsOptions('not_specified'));

		$oPayMethodsGUI->setValue($this->pobject->getPayMethod());
		$oForm->addItem($oPayMethodsGUI);		
		
		// topics
		include_once './Services/Payment/classes/class.ilShopTopics.php';
		ilShopTopics::_getInstance()->read();
		if(is_array($topics = ilShopTopics::_getInstance()->getTopics()) && count($topics))
		{
			$oTopicsGUI = new ilSelectInputGUI($this->lng->txt('topic'), 'topic_id');

			ilShopTopics::_getInstance()->read();
			$topic_options = array();
			$topic_options[''] = $this->lng->txt('please_choose');
			
			foreach($topics as $oTopic)
			{			
				$topic_options[$oTopic->getId()] = $oTopic->getTitle();
			}
			
			$oTopicsGUI->setOptions($topic_options);
			$oTopicsGUI->setValue($this->pobject->getTopicId());
			$oForm->addItem($oTopicsGUI);
		}
		
		// vats
		$oShopVatsList = new ilShopVatsList();
		$oShopVatsList->read();			
		if($oShopVatsList->hasItems())
		{
			$oVatsGUI = new ilSelectInputGUI($this->lng->txt('vat_rate'), 'vat_id');

			$vats_options = array();				
			foreach($oShopVatsList as $oVAT)
			{	
				$vats_options[$oVAT->getId()] = ilShopUtils::_formatVAT($oVAT->getRate()).' -> '.$oVAT->getTitle();
			}
		
			$oVatsGUI->setOptions($vats_options);
			$oVatsGUI->setValue($this->pobject->getVatId());
			$oForm->addItem($oVatsGUI);
		}
		else
		{
			$oVatsGUI = new ilNonEditableValueGUI($this->lng->txt('vat_rate'));		
			$oVatsGUI->setValue($this->lng->txt('paya_no_vats_assigned'));	
			$oForm->addItem($oVatsGUI);	
		}						
		
		$oThumbnail = new ilImageFileInputGUI($this->lng->txt('pay_thumbnail'), 'thumbnail');
		$oFile = new ilFileDataShop($this->pobject->getPobjectId());
		if(($webpath_file = $oFile->getCurrentImageWebPath()) !== false)
		{
			$oThumbnail->setImage($webpath_file);
		}
		$oForm->addItem($oThumbnail);
		
		// buttons
		$oForm->addCommandButton('updateDetails', $this->lng->txt('save'));
		$oForm->addCommandButton('deleteObject', $this->lng->txt('delete'));		

		$this->tpl->setVariable('FORM', $oForm->getHTML());
		
	}
	
	function deleteObjectObject()
	{
		//include_once './Services/Payment/classes/class.ilPaymentBookings.php';

		if(!$_GET['pobject_id'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->editDetailsObject();
			return true;
		}
		if(ilPaymentBookings::_getCountBookingsByObject((int) $_GET['pobject_id']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_bookings_available'));
			$this->editDetailsObject();

			return false;
		}
		else
		{
			ilUtil::sendQuestion($this->lng->txt('paya_sure_delete_object'));
			$this->editDetailsObject(true);

			return true;
		}
	}
	
	function performObjectDeleteObject()
	{
	//	include_once './Services/Payment/classes/class.ilPaymentBillVendor.php';

		if(!$_GET['pobject_id'])
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_object_selected'));

			$this->objectsObject();
			return true;
		}
		$this->__initPaymentObject((int) $_GET['pobject_id']);

		// delete object data
		$this->pobject->delete();
		
		// delete payment prices
		$price_obj = new ilPaymentPrices((int) $_GET['pobject_id']);
		$price_obj->deleteAllPrices();
		unset($price_obj);

/**  doesn't make sense   payment_bill_vendor has no data. never **/		
/*	
		// delete bill vendor data if exists
		$bv = new ilPaymentBillVendor((int) $_GET['pobject_id']);
		$bv->delete();
		unset($bv);
*/

		ilUtil::sendInfo($this->lng->txt('paya_deleted_object'));

		$this->objectsObject();

		return true;
	}
	
	function __getHTMLPath($a_ref_id)
	{
		global $tree;

		$path = $tree->getPathFull($a_ref_id);

		unset($path[0]);

		foreach($path as $data)
		{
			$html .= $data['title'].' > ';
		}
		return substr($html,0,-2);
	}
		
	private function __getVendors()
	{
		include_once 'Services/Payment/classes/class.ilPaymentVendors.php';
		
		$options = array();		
		$vendors = array();
		
		$vendor_obj = new ilPaymentVendors();
		$all_vendors = $vendor_obj->getVendors();
		if (is_array($all_vendors))
		{
			foreach ($all_vendors as $vendor)
			{				
				$vendors[] = $vendor['vendor_id'];
			}
		}		
		
		foreach($vendors as $vendor)
		{
			$tmp_obj = ilObjectFactory::getInstanceByObjId($vendor,false);
			$options[$vendor] = $tmp_obj->getFullname().' ['.$tmp_obj->getLogin().']';
		}
		return $options;
	}

	
	private function __getStatus()
	{
		$option = array();
		$option[$this->pobject->STATUS_NOT_BUYABLE] = $this->lng->txt('paya_not_buyable');
		$option[$this->pobject->STATUS_BUYABLE] = $this->lng->txt('paya_buyable');
		$option[$this->pobject->STATUS_EXPIRES] = $this->lng->txt('paya_expires');
		
		return $option;
	}
	
	function resetObjectFilterObject()
	{
		unset($_SESSION['pay_statistics']);
		unset($_POST['title_type']);
		unset($_POST['title_value']);
		unset($_POST['vendor']);
		unset($_POST['pay_method']);

		ilUtil::sendInfo($this->lng->txt('paya_filter_reseted'));

		return $this->objectsObject();
	}
	
	function objectsObject()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}
		
		if ($_POST['updateView'] == 1)
		{
			$_SESSION['pay_objects']['title_type'] = $_POST['title_type'];
			$_SESSION['pay_objects']['title_value'] = $_POST['title_value'];			
			$_SESSION['pay_objects']['pay_method'] = $_POST['pay_method'];			
			$_SESSION['pay_objects']['vendor'] = $_POST['vendor'];
		}	

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');

		$this->__initPaymentObject();
		$this->lng->loadLanguageModule('search');
		
		$filter_form = new ilPropertyFormGUI();
		$filter_form->setFormAction($this->ctrl->getFormAction($this));
		$filter_form->setTitle($this->lng->txt('pay_filter'));
		$filter_form->setId('filter_form');
		$filter_form->setTableWidth('100 %');
	
		//hide_filter
		$o_hide_check = new ilCheckBoxInputGUI($this->lng->txt('show_filter'),'show_filter');
		$o_hide_check->setValue(1);		
		$o_hide_check->setChecked($_POST['show_filter'] ? 1 : 0);		
	
		$o_hidden = new ilHiddenInputGUI('updateView');
		$o_hidden->setValue(1);
		$o_hidden->setPostVar('updateView');
		$o_hide_check->addSubItem($o_hidden);

		//title
		$radio_group = new ilRadioGroupInputGUI($this->lng->txt('search_in_title'), 'title_type');
		$radio_option = new ilRadioOption($this->lng->txt('search_any_word'), 'or');
		$radio_group->addOption($radio_option);
		$radio_option = new ilRadioOption($this->lng->txt('search_all_words'), 'and');
		$radio_group->addOption($radio_option);

		$radio_group->setRequired(false);
		$radio_group->setValue('or');
		$radio_group->setPostVar('title_type');
		
		$o_title = new ilTextInputGUI();
		$o_title->setValue($_SESSION['pay_objects']['title_value']);
		$o_title->setPostVar('title_value');
		$o_title->setTitle($this->lng->txt('title'));
		
		$o_hide_check->addSubItem($radio_group);
		$o_hide_check->addSubItem($o_title);
		
		//vendor
		$o_vendor = new ilTextInputGUI();
		$o_vendor->setTitle($this->lng->txt('paya_vendor'));
		$o_vendor->setValue($_SESSION['pay_objects']['vendor']);				
		$o_vendor->setPostVar('vendor');
		$o_hide_check->addSubItem($o_vendor);
		
		// paymethod	
		$o_paymethod = new ilSelectInputGUI();
		$o_paymethod->setTitle($this->lng->txt('payment_system'));
		$o_paymethod->setOptions(ilPaymethods::getPayMethodsOptions('all'));
		$o_paymethod->setValue($_SESSION['pay_objects']['pay_method']);
		$o_paymethod->setPostVar('pay_method');
		$o_hide_check->addSubItem($o_paymethod);				
		
		$filter_form->addCommandButton('objects', $this->lng->txt('pay_update_view'));
		$filter_form->addCommandButton('resetObjectFilter', $this->lng->txt('pay_reset_filter'));
		
		$filter_form->addItem($o_hide_check);		
		if(!count($objects = ilPaymentObject::_getAllObjectsData()))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_objects_assigned'));
			
			return true;
		}		
		$this->tpl->setVariable('FORM', $filter_form->getHTML());

		$counter = 0;
		foreach($objects as $data)
		{
			$tmp_obj = ilObjectFactory::getInstanceByRefId($data['ref_id']);
			$f_result[$counter][] = $tmp_obj->getTitle();

			switch($data['status'])
			{
				case $this->pobject->STATUS_BUYABLE:
					$f_result[$counter][] = $this->lng->txt('paya_buyable');
					break;

				case $this->pobject->STATUS_NOT_BUYABLE:
					$f_result[$counter][] = $this->lng->txt('paya_not_buyable');
					break;
					
				case $this->pobject->STATUS_EXPIRES:
					$f_result[$counter][] = $this->lng->txt('paya_expires');
					break;
			}

			include_once './Services/Payment/classes/class.ilPayMethods.php';
			$str_paymethod = ilPayMethods::getStringByPaymethod($data['pay_method']);
			$f_result[$counter][] = $str_paymethod;
			
			if($data['vat_id'] <= 0)
			{
			 	$vat_rate = $this->lng->txt('payment_vat_has_to_be_defined_by_administration_short');
			}
			else 
			{				
				try
				{
					$oVAT = new ilShopVats((int)$data['vat_id']);
					$vat_rate = ilShopUtils::_formatVAT((float)$oVAT->getRate()); 
				}
				catch(ilShopException $e)
				{
					$vat_rate = $this->lng->txt('payment_vat_has_to_be_defined_by_administration_short');		
				}
			}			
		
			$f_result[$counter][] = $vat_rate;
						
			$tmp_user = ilObjectFactory::getInstanceByObjId($data['vendor_id']);
			$f_result[$counter][] = $tmp_user->getFullname().' ['.$tmp_user->getLogin().']';

			// Get number of purchasers
			$f_result[$counter][] = ilPaymentBookings::_getCountBookingsByObject($data['pobject_id']);

			// edit link
			$this->ctrl->setParameter($this,'pobject_id',$data['pobject_id']);
			$link_change = "<div class=\"il_ContainerItemCommands\"><a class=\"il_ContainerItemCommand\" href=\"".$this->ctrl->getLinkTarget($this,"editDetails")."\">".$this->lng->txt("edit")."</a></div>";

			$f_result[$counter][] = $link_change;
			unset($tmp_user);
			unset($tmp_obj);

			++$counter;
		}
		
		$this->__showObjectsTable($f_result);	

		//return true;
	}
	
	function __showObjectsTable($a_result_set)
	{
		$tbl = $this->initTableGUI();
		$tpl = $tbl->getTemplateObject();

		// SET FORMAACTION
		$tpl->setCurrentBlock('tbl_form_header');
		
		$this->ctrl->setParameter($this, 'cmd', 'objects');

		$tpl->setVariable('FORMACTION', $this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();
		
		$tbl->setTitle($this->lng->txt('objects'),'icon_pays.gif',$this->lng->txt('objects'));
		$tbl->setHeaderNames(array($this->lng->txt('title'),
								   $this->lng->txt('status'),
								   $this->lng->txt('paya_pay_method'),
								   $this->lng->txt("vat_rate"),
								   $this->lng->txt('paya_vendor'),
								   $this->lng->txt('paya_count_purchaser'),
								   ''));
		$header_params = $this->ctrl->getParameterArray($this,'');
		

		$tbl->setHeaderVars(array('title',
								  'status',
								  'pay_method',
								   'vat_rate',
								  'vendor',
								  'purchasers',
								  'options'),$header_params);
								  /*
							array("cmd" => "",
								  "cmdClass" => "ilpaymentobjectgui",
								  "cmdNode" => $_GET["cmdNode"]));
								  */
		$tbl->setColumnWidth(array("15%","15%","15%","20%","20%"));

#		$this->setTableGUIBasicData($tbl,$a_result_set);

		$offset = $_GET['offset'];
		$order = $_GET['sort_by'];
		$direction = $_GET['sort_order'] ? $_GET['sort_order'] : 'desc';

		$tbl->setOrderColumn($order,'order_date');
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET['limit']);
		$tbl->setMaxCount(count($a_result_set));
		$tbl->setFooter('tblfooter',$this->lng->txt('previous'),$this->lng->txt('next'));
		$tbl->setData($a_result_set);

		$tbl->render();

		$this->tpl->setVariable('TABLE',$tbl->tpl->get());

		return true;
	}
	
	function __initPaymentObject($a_pobject_id = 0)
	{
		$this->pobject = new ilPaymentObject($this->user_obj,$a_pobject_id);
		return true;
	}

	function gatewayObject()
	{
		switch($_POST['action'])
		{
			case 'deleteVendorsObject':
				$this->deleteVendors();
				break;

			case 'editVendorObject':
				$this->editVendor();
				break;

			case 'performEditVendorObject':
				$this->performEditVendorObject();
				break;

			default:
				$this->vendorsObject();
				break;
		}
		return true;
	}

	function resetFilterObject()
	{
		unset($_SESSION['pay_statistics']);
		unset($_POST['transaction_type']);
		unset($_POST['transaction_value']);
		unset($_POST['from']);
		unset($_POST['til']);
		unset($_POST['payed']);
		unset($_POST['access']);
		unset($_POST['customer']);
		unset($_POST['pay_method']);
		unset($_POST['updateView']);
		ilUtil::sendInfo($this->lng->txt('paya_filter_reseted'));

		return $this->statisticObject();
	}

	function statisticObject()
	{
		global $rbacsystem, $ilToolbar;

		include_once './Services/Payment/classes/class.ilPayMethods.php';
				
		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}

		$ilToolbar->addButton($this->lng->txt('paya_add_customer'), $this->ctrl->getLinkTarget($this, 'showObjectSelector'));

		if ($_POST['updateView'] == 1)
		{
			$_SESSION['pay_statistics']['show_filter']= $_POST['show_filter'];
			$_SESSION['pay_statistics']['updateView'] = true;
			$_SESSION['pay_statistics']['until_check'] = $_POST['until_check'];
			$_SESSION['pay_statistics']['from_check'] = $_POST['from_check'];
			$_SESSION['pay_statistics']['transaction_type'] = isset($_POST['transaction_type']) ? $_POST['transaction_type'] : '' ;
			$_SESSION['pay_statistics']['transaction_value'] = isset($_POST['transaction_value']) ?  $_POST['transaction_value'] : '';
			
			if($_SESSION['pay_statistics']['from_check'] == '1')
			{
				$_SESSION['pay_statistics']['from']['date']['d'] = $_POST['from']['date']['d'];
				$_SESSION['pay_statistics']['from']['date']['m'] = $_POST['from']['date']['m'];
				$_SESSION['pay_statistics']['from']['date']['y'] = $_POST['from']['date']['y'];
			} 
			else 
			{
				$_SESSION['pay_statistics']['from']['date']['d'] = '';
				$_SESSION['pay_statistics']['from']['date']['m'] = '';
				$_SESSION['pay_statistics']['from']['date']['y'] = '';
			}
			
			if($_SESSION['pay_statistics']['until_check']== '1')
			{
				$_SESSION['pay_statistics']['til']['date']['d'] = $_POST['til']['date']['d'];
				$_SESSION['pay_statistics']['til']['date']['m'] = $_POST['til']['date']['m'];
				$_SESSION['pay_statistics']['til']['date']['y'] = $_POST['til']['date']['y'];
			} 
			else 
			{
				$_SESSION['pay_statistics']['til']['date']['d'] = '';
				$_SESSION['pay_statistics']['til']['date']['m'] = '';
				$_SESSION['pay_statistics']['til']['date']['y'] = '';
			}

			$_SESSION['pay_statistics']['payed'] = $_POST['payed'];
			$_SESSION['pay_statistics']['access'] = $_POST['access'];
			$_SESSION['pay_statistics']['pay_method'] = $_POST['pay_method'];
			$_SESSION['pay_statistics']['customer'] = isset ($_POST['customer']) ? $_POST['customer'] : '';
			$_SESSION['pay_statistics']['vendor'] = isset ($_POST['vendor']) ? $_POST['vendor']: '';
		}

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');

		$filter_form = new ilPropertyFormGUI();
		$filter_form->setFormAction($this->ctrl->getFormAction($this));
		$filter_form->setTitle($this->lng->txt('pay_filter'));
		$filter_form->setId('formular');
		$filter_form->setTableWidth('100 %');
		//filter	
		$o_hide_check = new ilCheckBoxInputGUI($this->lng->txt('show_filter'),'show_filter');
		$o_hide_check->setValue(1);		
		$o_hide_check->setChecked($_POST['show_filter'] ? 1 : 0);		

		$o_hidden = new ilHiddenInputGUI('updateView');
		$o_hidden->setValue(1);
		$o_hidden->setPostVar('updateView');
		$o_hide_check->addSubItem($o_hidden);
			
		$o_transaction_type = new ilSelectInputGUI(); 
		$trans_option = array($this->lng->txt('pay_starting'),$this->lng->txt('pay_ending'));
		$trans_value = array('0','1'); 
		$o_transaction_type->setTitle($this->lng->txt('paya_transaction'));
		$o_transaction_type->setOptions($trans_option);
		$o_transaction_type->setValue($_SESSION['pay_statistics']['transaction_type']);		
		$o_transaction_type->setPostVar('transaction_type');
		$o_hide_check->addSubItem($o_transaction_type);
		
		$o_transaction_val = new ilTextInputGUI();
		$o_transaction_val->setValue($_SESSION['pay_statistics']['transaction_value']);		
		$o_transaction_val->setPostVar('transaction_value');
		$o_hide_check->addSubItem($o_transaction_val);

		$o_customer = new ilTextInputGUI();
		$o_customer->setTitle($this->lng->txt('paya_customer'));
		$o_customer->setValue($_SESSION['pay_statistics']['customer']);		
		$o_customer->setPostVar('customer');
		$o_hide_check->addSubItem($o_customer);
		
		$o_vendor = new ilTextInputGUI();
		$o_vendor->setTitle($this->lng->txt('paya_vendor'));
		$o_vendor->setValue($_SESSION['pay_statistics']['vendor']);				
		$o_vendor->setPostVar('vendor');
		$o_hide_check->addSubItem($o_vendor);
		
		$o_from_check = new ilCheckBoxInputGUI($this->lng->txt('pay_order_date_from'),'from_check');
		$o_from_check->setValue(1);		
		$o_from_check->setChecked($_SESSION['pay_statistics']['from_check'] ? 1 : 0);		
		
		$o_date_from = new ilDateTimeInputGUI();
		$o_date_from->setPostVar('from');			
		
		if($_SESSION['pay_statistics']['from_check'] == '1') 
		{
			$o_date_from->setValueByArray($_SESSION['pay_statistics']['from']);	
			$o_date_from->checkInput();	
		}

		$o_from_check->addSubItem($o_date_from);
		$o_hide_check->addSubItem($o_from_check);
		
		$o_until_check = new ilCheckBoxInputGUI($this->lng->txt('pay_order_date_til'), 'until_check');
		$o_until_check->setValue(1);	
		$o_until_check->setChecked($_SESSION['pay_statistics']['until_check'] ? 1 : 0);				

		$o_date_until = new ilDateTimeInputGUI();
		$o_date_until->setPostVar('til');

		if($_SESSION['pay_statistics']['until_check'] == '1') 
		{
			$o_date_until->setValueByArray($_SESSION['pay_statistics']['til']);		
			$o_date_until->checkInput();	
		}
		
		$o_until_check->addSubItem($o_date_until);
		$o_hide_check->addSubItem($o_until_check);	
		
		$o_payed = new ilSelectInputGUI();
		$payed_option = array('all'=>$this->lng->txt('pay_all'),'1'=>$this->lng->txt('yes'),'0'=>$this->lng->txt('no'));

		$o_payed->setTitle($this->lng->txt('paya_payed'));
		$o_payed->setOptions($payed_option);
		$o_payed->setValue($_SESSION['pay_statistics']['payed']);
		$o_payed->setPostVar('payed');		

		$o_hide_check->addSubItem($o_payed);

		$o_access = new ilSelectInputGUI();
		$access_option = array('all'=>$this->lng->txt('pay_all'),'1'=>$this->lng->txt('yes'),'0'=>$this->lng->txt('no'));

		$o_access->setTitle($this->lng->txt('paya_access'));
		$o_access->setOptions($access_option);
		$o_access->setValue($_SESSION['pay_statistics']['access']);
		$o_access->setPostVar('access');
		$o_hide_check->addSubItem($o_access);		
		
		$o_paymethod = new ilSelectInputGUI();
		$o_paymethod->setTitle($this->lng->txt('payment_system'));
		$o_paymethod->setOptions(ilPaymethods::getPayMethodsOptions('all'));
		$o_paymethod->setValue($_SESSION['pay_statistics']['pay_method']);
		$o_paymethod->setPostVar('pay_method');
		$o_hide_check->addSubItem($o_paymethod);				
		
		$filter_form->addCommandButton('statistic', $this->lng->txt('pay_update_view'));
		$filter_form->addCommandButton('resetFilter', $this->lng->txt('pay_reset_filter'));
		
		$filter_form->addItem($o_hide_check);		
		if(!$_SESSION['pay_statistics']['updateView'])
		{
			ilUtil::sendInfo($this->lng->txt('statistics_filter_advice'));
			//return true;
		}	

		$this->tpl->setVariable('FORM', $filter_form->getHTML());
		//else 	$filter_form->checkInput();
		
		// STATISTICS TABLE
		$this->__initBookingObject();

		if(!count($bookings = $this->booking_obj->getBookings()))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_bookings'));

			return true;
		}
#		else
#		{
#			$this->__showButton('exportVendors',$this->lng->txt('excel_export'));
#		}
		$img_change = "<img src=\"".ilUtil::getImagePath("edit.gif")."\" alt=\"".
			$this->lng->txt("edit")."\" title=\"".$this->lng->txt("edit").
			"\" border=\"0\" vspace=\"0\"/>";

		include_once 'Services/User/classes/class.ilObjUser.php';
		$object_title_cache = array();
		$user_title_cache = array();
		
		$counter = 0;
		foreach($bookings as $booking)
		{
			if(array_key_exists($booking['ref_id'], $object_title_cache))
			{
				$tmp_obj = $object_title_cache[$booking['ref_id']];
			}
			else
			{
				$tmp_obj = ilObject::_lookupTitle(ilObject::_lookupObjId($booking['ref_id']));				
				$object_title_cache[$booking['ref_id']] = $tmp_obj;
			}
			

				if(array_key_exists($booking['b_vendor_id'], $user_title_cache))
			{
				$tmp_vendor = $user_title_cache[$booking['b_vendor_id']];
			}
			else
			{
				$tmp_vendor = ilObjUser::_lookupLogin($booking['b_vendor_id']);
				$user_title_cache[$booking['b_vendor_id']] = $tmp_vendor;
			}
			if(array_key_exists($booking['customer_id'], $user_title_cache))
			{
				$tmp_purchaser = $user_title_cache[$booking['customer_id']];
			}
			else
			{
				if(ANONYMOUS_USER_ID == $booking['customer_id'])
				{
					$tmp_purchaser = ilObjUser::_lookupLogin($booking['customer_id']);
					$purchaser_name = $booking['name_extern'];
					$tmp_purchaser_email = $booking['email_extern'];
				}
				else
				{
					$tmp_purchaser = ilObjUser::_lookupLogin($booking['customer_id']);
					$tmp_purchaser_name = ilObjUser::_lookupName($booking['customer_id']);
					$purchaser_name = $tmp_purchaser_name['firstname'].' '.$tmp_purchaser_name['lastname'];
					$tmp_purchaser_email = ilObjUser::_lookupEmail($booking['customer_id']);
				}			
				$user_title_cache[$booking['customer_id']] = $tmp_purchaser;
			}
						
			$transaction = $booking['transaction_extern'];
			$str_paymethod = ilPayMethods::getStringByPaymethod($booking['b_pay_method']);
			$transaction .= $booking['transaction']."<br> (" . $str_paymethod . ")";
			
			$f_result[$counter][] = $transaction;
			$f_result[$counter][] = ($tmp_obj != '' ?  $tmp_obj : $this->lng->txt('object_deleted'));
			$f_result[$counter][] = ($tmp_vendor != '' ?  '['.$tmp_vendor.']' : $this->lng->txt('user_deleted'));
			$f_result[$counter][] = ($tmp_purchaser != '' ? 
									$purchaser_name. ' ['.$tmp_purchaser.']<br>'
									.$tmp_purchaser_email 
									: $this->lng->txt('user_deleted'));
			$f_result[$counter][] =	ilDatePresentation::formatDate(new ilDateTime($booking['order_date'], IL_CAL_UNIX));	 
					
			if($booking['duration'] != 0)
			{
				$f_result[$counter][] = $booking['duration'].' '.$this->lng->txt('paya_months');
			
			}
			else
			{
				$f_result[$counter][] = $this->lng->txt("unlimited_duration");
			}

			$f_result[$counter][] = $booking['price'].' '.$booking['currency_unit'];
			$f_result[$counter][] = ($booking['discount'] != '' ? ($booking['discount'].' '.$booking['currency_unit']) : '&nbsp;');

			$payed_access = $booking['payed'] ? 
				$this->lng->txt('yes') : 
				$this->lng->txt('no');

			$payed_access .= '/';
			$payed_access .= $booking['access_granted'] ?
				$this->lng->txt('yes') : 
				$this->lng->txt('no');

			$f_result[$counter][] = $payed_access;

			$this->ctrl->setParameter($this,"booking_id",$booking['booking_id']);
#			$link_change = "<a href=\"".$this->ctrl->getLinkTarget($this,"editStatistic")."\"> ".
#				$img_change."</a>";
			$link_change = "<div class=\"il_ContainerItemCommands\"><a class=\"il_ContainerItemCommand\" href=\"".$this->ctrl->getLinkTarget($this,"editStatistic")."\">".$this->lng->txt("edit")."</a></div>";

			$f_result[$counter][] = $link_change;

			unset($tmp_obj);
			unset($tmp_vendor);
			unset($tmp_purchaser);

			++$counter;
		}
		return $this->__showStatisticTable($f_result);
	}
	
	function editStatisticObject($a_show_confirm_delete = false)
	{
		global $ilToolbar;

		include_once './Services/Payment/classes/class.ilPayMethods.php';
				
		if(!isset($_GET['booking_id']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_booking_id_given'));
			$this->showStatistics();

			return true;
		}

		$ilToolbar->addButton($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, 'statistic'));

		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');	
		$this->ctrl->setParameter($this,'booking_id',(int) $_GET['booking_id']);

		// confirm delete
		if($a_show_confirm_delete)
		{
			$oConfirmationGUI = new ilConfirmationGUI();
			
			// set confirm/cancel commands
			$oConfirmationGUI->setFormAction($this->ctrl->getFormAction($this,"performDelete"));
			$oConfirmationGUI->setHeaderText($this->lng->txt("paya_sure_delete_stat"));
			$oConfirmationGUI->setCancel($this->lng->txt("cancel"), "statistic");
			$oConfirmationGUI->setConfirm($this->lng->txt("confirm"), "performDelete");			
		
			$this->tpl->setVariable('CONFIRMATION', $oConfirmationGUI->getHTML());
			return true;
		}
			

		$this->__initBookingObject();
		$bookings = $this->booking_obj->getBookings();
		$booking = $bookings[(int) $_GET['booking_id']];

		// get customer_obj
		$tmp_user = ilObjectFactory::getInstanceByObjId($booking['customer_id'], false);

		$oForm = new ilPropertyFormGUI();
		$oForm->setFormAction($this->ctrl->getFormAction($this));
		$oForm->setId('stat_form');
		$oForm->setTableWidth('50 %');		
		$oForm->setTitleIcon(ilUtil::getImagePath('icon_usr.gif'));
		if(is_object($tmp_user))
		{
			$frm_user = $tmp_user->getFullname().' ['.$tmp_user->getLogin().']';
		}
		else
		{
			$frm_user = $this->lng->txt('user_deleted');
		}
		$oForm->setTitle($frm_user);

		$pObj = new ilPaymentObject($this->user_obj, $booking['pobject_id']);
		$tmp_obj = ilObject::_lookupTitle(ilObject::_lookupObjId($pObj->getRefId()));				

		// object_title
		$oTitleGUI = new ilNonEditableValueGUI($this->lng->txt('title'));
		$oTitleGUI->setValue($tmp_obj != '' ?  $tmp_obj : $this->lng->txt('object_deleted'));
		$oForm->addItem($oTitleGUI);
				
		// transaction
		$oTransactionGUI = new ilNonEditableValueGUI($this->lng->txt('paya_transaction'));
		$oTransactionGUI->setValue($booking['transaction']);
		$oForm->addItem($oTransactionGUI);
		
		//vendor
		$oVendorGUI = new ilNonEditableValueGUI($this->lng->txt('paya_vendor'));
		$tmp_vendor = ilObjectFactory::getInstanceByObjId($booking['b_vendor_id'], false);
		if(is_object($tmp_vendor))
		{
			$frm_vendor = $tmp_vendor->getFullname().' ['.$tmp_vendor->getLogin().']';
		}
		else
		{
			$frm_vendor =  $this->lng->txt('user_deleted');
		}		
		$oVendorGUI->setValue($frm_vendor);
		$oForm->addItem($oVendorGUI);

		// paymethod
		$oPaymethodGUI = new ilNonEditableValueGUI($this->lng->txt('paya_pay_method'));
		$oPaymethodGUI->setValue(ilPayMethods::getStringByPaymethod($booking['b_pay_method']));
		$oForm->addItem($oPaymethodGUI);	

		// order_date
		$oOrderdateGUI = new ilNonEditableValueGUI($this->lng->txt('paya_order_date'));
		$oOrderdateGUI->setValue(ilDatePresentation::formatDate(new ilDateTime($booking['order_date'], IL_CAL_UNIX)));
		$oForm->addItem($oOrderdateGUI);	
		
		// duration
		$oDurationGUI = new ilNonEditableValueGUI($this->lng->txt('duration'));
		if($booking['duration'] != 0)
		{
			$frm_duration = $booking['duration'].' '.$this->lng->txt('paya_months');
		}
		else
		{				
			$frm_duration = $this->lng->txt("unlimited_duration");
		}		
		$oDurationGUI->setValue($frm_duration);
		$oForm->addItem($oDurationGUI);		
		
		// price
		$oPriceGUI = new ilNonEditableValueGUI($this->lng->txt('price_a'));
		$oPriceGUI->setValue($booking['price'].' '.$booking['currency_unit']);
		$oForm->addItem($oPriceGUI);

		// payed
		$oPayedGUI = new ilSelectInputGUI();
		$payed_option = array(0 => $this->lng->txt('no'),1 => $this->lng->txt('yes'));

		$oPayedGUI->setTitle($this->lng->txt('paya_payed'));
		$oPayedGUI->setOptions($payed_option);
		$oPayedGUI->setValue($booking['payed']);
		$oPayedGUI->setPostVar('payed');		
		$oForm->addItem($oPayedGUI);
		
		// access
		$oAccessGUI = new ilSelectInputGUI();
		$access_option = array(0 => $this->lng->txt('no'),1 => $this->lng->txt('yes'));

		$oAccessGUI->setTitle($this->lng->txt('paya_access'));
		$oAccessGUI->setOptions($payed_option);
		$oAccessGUI->setValue($booking['access_granted']);
		$oAccessGUI->setPostVar('access');		
		$oForm->addItem($oAccessGUI);
		
		$oForm->addCommandButton('updateStatistic',$this->lng->txt('save'));
		$oForm->addCommandButton('deleteStatistic',$this->lng->txt('delete'));

		// show CUSTOMER_DATA if isset -> setting: save_user_address
		if(ilPayMethods::_PMEnabled($booking['b_pay_method']))
		{
			$oForm2 = new ilPropertyFormGUI();
			$oForm2->setId('cust_form');
			$oForm2->setTableWidth('50 %');		
			$oForm2->setTitle($frm_user);		
			
			// email
			$oEmailGUI = new ilNonEditableValueGUI($this->lng->txt('email'));
			$email = (!isset($tmp_user)) ? $this->lng->txt('user_deleted') : $tmp_user->getEmail();
			$oEmailGUI->setValue($email);
			$oForm2->addItem($oEmailGUI);	

			// street
			$oStreetGUI = new ilNonEditableValueGUI($this->lng->txt('street'));
			$oStreetGUI->setValue($booking['street']);
			$oForm2->addItem($oStreetGUI);
				
			// pobox
			$oPoBoxGUI = new ilNonEditableValueGUI($this->lng->txt('pay_bmf_po_box'));
			$oPoBoxGUI->setValue($booking['po_box']);
			$oForm2->addItem($oPoBoxGUI);	
				
			// zipcode
			$oPoBoxGUI = new ilNonEditableValueGUI($this->lng->txt('zipcode'));
			$oPoBoxGUI->setValue($booking['zipcode']);
			$oForm2->addItem($oPoBoxGUI);
					
			// city
			$oCityGUI = new ilNonEditableValueGUI($this->lng->txt('city'));
			$oCityGUI->setValue($booking['city']);
			$oForm2->addItem($oCityGUI);	
			
			// country
			$oCountryGUI = new ilNonEditableValueGUI($this->lng->txt('country'));
			$oCountryGUI->setValue($booking['country']);
			$oForm2->addItem($oCountryGUI);	
		}
		
		$this->tpl->setVariable('FORM',$oForm->getHTML());
		$this->tpl->setVariable('FORM_2',$oForm2->getHTML());
		return true;
		
	}
	function updateStatisticObject()
	{
		if(!isset($_GET['booking_id']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_booking_id_given'));
			$this->statisticObject();

			return true;
		}
		$this->__initBookingObject();

		$this->booking_obj->setBookingId((int) $_GET['booking_id']);
		$this->booking_obj->setAccess((int) $_POST['access']);
		$this->booking_obj->setPayed((int) $_POST['payed']);
		
		if($this->booking_obj->update())
		{
			ilUtil::sendSuccess($this->lng->txt('paya_updated_booking'));

			$this->statisticObject();
			return true;
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt('paya_error_update_booking'));

			$this->statisticObject();
			
			return true;
		}
	}

	function deleteStatisticObject()
	{
		if(!isset($_GET['booking_id']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_booking_id_given'));
			$this->statisticObject();

			return true;
		}
		ilUtil::sendQuestion($this->lng->txt('paya_sure_delete_stat'));

		$this->editStatisticObject(true);

		return true;
	}
	function performDeleteObject()
	{
		if(!isset($_GET['booking_id']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_booking_id_given'));
			$this->statisticObject();

			return true;
		}

		$this->__initBookingObject();
		$this->booking_obj->setBookingId((int) $_GET['booking_id']);
		if(!$this->booking_obj->delete())
		{
			die('Error deleting booking');
		}
		ilUtil::sendInfo($this->lng->txt('pay_deleted_booking'));

		$this->statisticObject();

		return true;
	}

	function getAdminTabs($tabs_gui)
	{
		$this->getTabs($tabs_gui);
	}

	/**
	* get tabs
	* @access	public
	* @param	object	tabs gui object
	*/
	function getTabs($tabs_gui)
	{
		global $rbacsystem;

		if ($rbacsystem->checkAccess('visible,read',$this->object->getRefId()))
		{
			$tabs_gui->addTarget('settings',
				$this->ctrl->getLinkTarget($this, 'generalSettings'), array('generalSettings','', 'view'), '', '');
				
			$tabs_gui->addTarget('bookings',
				$this->ctrl->getLinkTarget($this, 'statistic'), 'statistic', '', '');
				
			$tabs_gui->addTarget('objects',
				$this->ctrl->getLinkTarget($this, 'objects'), 'objects', '', '');
				
			$tabs_gui->addTarget('vendors',
				$this->ctrl->getLinkTarget($this, 'vendors'), 'vendors', '', '');
#TODO: CURRENCY not finished yet
/*				
			$tabs_gui->addTarget('currencies',
				$this->ctrl->getLinkTarget($this, 'currencies'), 'currencies', '','');
*/				
			$tabs_gui->addTarget('pay_methods',
				$this->ctrl->getLinkTarget($this, 'payMethods'), 'payMethods', '', '');
			
			$tabs_gui->addTarget('topics',
					$this->ctrl->getLinkTargetByClass('ilshoptopicsgui', ''), 'payment_topics', '', '');

			$tabs_gui->addTarget('vats',
					$this->ctrl->getLinkTarget($this, 'vats'), 'vats', '', '');				
		}

		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$tabs_gui->addTarget('perm_settings',
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), 'perm'), array('perm','info','owner'), 'ilpermissiongui');
		}
	}

	function generalSettingsObject($a_show_confirm = false)
	{	
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}
		
		include_once './Services/Payment/classes/class.ilGeneralSettings.php';

		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.main_view.html','Services/Payment');		

		$genSet = new ilGeneralSettings();
		$genSetData = $genSet->getAll();		
						
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this, 'saveGeneralSettings'));
		$form->setTitle($this->lng->txt('pays_general_settings'));
		
		$form->addCommandButton('saveGeneralSettings',$this->lng->txt('save'));
		
		// enable webshop
		$formItem = new ilCheckboxInputGUI($this->lng->txt('pay_enable_shop'), 'shop_enabled');
		$formItem->setChecked((int)$genSetData['shop_enabled']);
		$formItem->setInfo($this->lng->txt('pay_enable_shop_info'));
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_currency_unit'), 'currency_unit');
		$formItem->setSize(5);
		$formItem->setValue($this->error != '' && isset($_POST['currency_unit'])
						? ilUtil::prepareFormOutput($_POST['currency_unit'],true)
						: ilUtil::prepareFormOutput($genSetData['currency_unit'],true));
		$formItem->setRequired(true);
		$form->addItem($formItem);
	/*	
		$formItem = new ilTextInputGUI($this->lng->txt('pays_currency_subunit'), 'currency_subunit');
		$formItem->setSize(5);
		$formItem->setRequired(true);
		$formItem->setValue($this->error != '' && isset($_POST['currency_subunit'])
							? ilUtil::prepareFormOutput($_POST['currency_subunit'],true)
							: ilUtil::prepareFormOutput($genSetData['currency_subunit'],true));
		$form->addItem($formItem);
	*/	
		$formItem = new ilTextAreaInputGUI($this->lng->txt('pays_address'), 'address');
		$formItem->setRows(7);
		$formItem->setCols(35);
		$formItem->setRequired(true);
		$formItem->setValue($this->error != '' && isset($_POST['address'])
							? ilUtil::prepareFormOutput($_POST['address'],true)
							: ilUtil::prepareFormOutput($genSetData['address'],true));
		$form->addItem($formItem);
		
		$formItem = new ilTextAreaInputGUI($this->lng->txt('pays_bank_data'), 'bank_data');
		$formItem->setRows(7);
		$formItem->setCols(35);
		$formItem->setRequired(true);
		$formItem->setValue($this->error != '' && isset($_POST['bank_data'])
							? ilUtil::prepareFormOutput($_POST['bank_data'],true)
							: ilUtil::prepareFormOutput($genSetData['bank_data'],true));
		$form->addItem($formItem);
		
		$formItem = new ilTextAreaInputGUI($this->lng->txt('pays_add_info'), 'add_info');
		$formItem->setRows(7);
		$formItem->setCols(35);
		$formItem->setValue($this->error != '' && isset($_POST['add_info'])
							? ilUtil::prepareFormOutput($_POST['add_info'],true)
							: ilUtil::prepareFormOutput($genSetData['add_info'],true));
		$form->addItem($formItem);
	
		$formItem = new ilTextInputGUI($this->lng->txt('pays_pdf_path'), 'pdf_path');
		$formItem->setValue($this->error != "" && isset($_POST['pdf_path'])
							? ilUtil::prepareFormOutput($_POST['pdf_path'],true)
							: ilUtil::prepareFormOutput($genSetData['pdf_path'],true));
		$formItem->setRequired(true);
		$form->addItem($formItem);

		// default sorting type
		$formItem = new ilSelectInputGUI($this->lng->txt('pay_topics_default_sorting_type'), 'topics_sorting_type');
		$formItem->setValue($genSetData['topics_sorting_type']);
		$options = array(
			1 => $this->lng->txt('pay_topics_sort_by_title'),
			2 => $this->lng->txt('pay_topics_sort_by_date'),
			3 => $this->lng->txt('pay_topics_sort_manually')
		);
		$formItem->setOptions($options);
		$form->addItem($formItem);
		
		// default sorting direction
		$formItem = new ilSelectInputGUI($this->lng->txt('pay_topics_default_sorting_direction'), 'topics_sorting_direction');
		$formItem->setValue($genSetData['topics_sorting_direction']);
		$options = array(
			'asc' => $this->lng->txt('sort_asc'),
			'desc' => $this->lng->txt('sort_desc'),
		);
		$formItem->setOptions($options);
		$form->addItem($formItem);
		
		// topics custom sorting
		$formItem = new ilCheckboxInputGUI($this->lng->txt('pay_topics_allow_custom_sorting'), 'topics_allow_custom_sorting');
		$formItem->setChecked((int)$genSetData['topics_allow_custom_sorting']);
		$formItem->setInfo($this->lng->txt('pay_topics_allow_custom_sorting_info'));
		$form->addItem($formItem);
		
		// objects custom sorting
		$formItem = new ilCheckboxInputGUI($this->lng->txt('pay_hide_filtering'), 'objects_allow_custom_sorting');
		$formItem->setChecked((int)$genSetData['objects_allow_custom_sorting']);
		$formItem->setInfo($this->lng->txt('pay_hide_filtering_info'));
		$form->addItem($formItem);	
		
		// max hits
		$formItem = new ilSelectInputGUI($this->lng->txt('pay_max_hits'), 'max_hits');
		$formItem->setValue($genSetData['max_hits']);
		$options = array();
		for($i = 10; $i <= 100; $i += 10)
		{
			$options[$i] = $i;
		}
		$formItem->setOptions($options);
		$formItem->setInfo($this->lng->txt('pay_max_hits_info'));
		$form->addItem($formItem);

		// hide advanced search
		$formItem = new ilCheckboxInputGUI($this->lng->txt('pay_hide_advanced_search'), 'hide_advanced_search');
		$formItem->setChecked((int)$genSetData['hide_advanced_search']);
		$formItem->setInfo($this->lng->txt('pay_hide_advanced_search_info'));
		$form->addItem($formItem);

		// hide shop news
		$formItem = new ilCheckboxInputGUI($this->lng->txt('pay_hide_news'), 'hide_news');
		$formItem->setChecked((int)$genSetData['hide_news']);
		$formItem->setInfo($this->lng->txt('pay_hide_news_info'));
		$form->addItem($formItem);



		// Hide coupons
		$formItem = new ilCheckboxInputGUI($this->lng->txt('pay_hide_coupons'), 'hide_coupons');
		$formItem->setChecked((int)$genSetData['hide_coupons']);
		$formItem->setInfo($this->lng->txt('pay_hide_coupons'));
		$form->addItem($formItem);
	
		$this->tpl->setVariable('FORM',$form->getHTML());
	}
	
	function saveGeneralSettingsObject()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}
		
		include_once './Services/Payment/classes/class.ilGeneralSettings.php';

		$genSet = new ilGeneralSettings();

		if ($_POST['currency_unit'] == '' ||
			#$_POST['currency_subunit'] == '' ||
			$_POST['address'] == '' ||
			$_POST['bank_data'] == '' ||
			$_POST['pdf_path'] == '')
		{
			$this->error = $this->lng->txt('pays_general_settings_not_valid');
			ilUtil::sendFailure($this->error);
			$this->generalSettingsObject();
			return;
		}
		
		$genSet->clearAll();
				
		$values = array(
			'currency_unit',
			#'currency_subunit',
			'address',
			'bank_data',
			'add_info',
			'pdf_path',
			'topics_allow_custom_sorting',
			'topics_sorting_type',
			'topics_sorting_direction',
			'max_hits',
			'shop_enabled',
			'hide_advanced_search',
			//'hide_filtering',
			'objects_allow_custom_sorting',
			'hide_coupons',
			'hide_news'
		);
		
		foreach ($values as $value) $values[$value] = ilUtil::stripSlashes($_POST[$value]);		
		
		$genSet->setAll($values);
		$this->generalSettingsObject();

		ilUtil::sendSuccess($this->lng->txt('pays_updated_general_settings'));

		return true;
	}

	/**
  * Genereates the EPAY setup form
  */
  function epaySettingsObject($a_show_confirm = false)
  {
    global $rbacsystem;

		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}

		include_once './Services/Payment/classes/class.ilEPaySettings.php';

		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.pays_epay_settings.html','Services/Payment');
		
		$ePayObj = ilEPaySettings::getInstance();
		
		$ep = $ePayObj->getAll();
				
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this, 'saveEPaySettings'));
		$form->setTitle($this->lng->txt('pays_epay_settings'));
		
		$form->addCommandButton('saveEPaySettings',$this->lng->txt('save'));
		
	    $fields = array (
	      array('pays_epay_server_host', 'server_host',  true, null),
	      array('pays_epay_server_path', 'server_path', true, null),
	      array('pays_epay_merchant_number', 'merchant_number', true, null),
	      array('pays_epay_auth_token', 'auth_token', true, 'pays_epay_auth_token_info'),
	      array('pays_epay_auth_email', 'auth_email', true, 'pays_epay_auth_email_info')
	    );
		
		foreach ($fields as $f)
		{
	      $fi = new ilTextInputGUI($this->lng->txt($f[0]), $f[1]);
	      $fi->setValue($ep[$f[1]]);
	      $fi->setRequired($f[2]);
	      if ($f[3] != null ) $fi->setInfo($this->lng->txt($f[3]));
	      if ($f[1] == 'auth_token') $fi->setInputType('password');
	      $form->addItem($fi);		
		}		

		$formItem = new ilCheckboxInputGUI($this->lng->txt('pays_epay_instant_capture'), 'instant_capture');
		$formItem->setChecked($ep['instant_capture'] == 1);
		$formItem->setInfo($this->lng->txt('pays_epay_instant_capture_info'));
		$form->addItem($formItem);
			
		$this->tpl->setVariable('EPAY_INFO', $this->lng->txt('pays_epay_info'));
		$this->tpl->setVariable('EPAY_SETTINGS',$form->getHTML());		
	}

  	function saveEPaySettingsObject()
	{
		include_once './Services/Payment/classes/class.ilEPaySettings.php';
		global $rbacsystem;
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}
		
		$epSet = ilEPaySettings::getInstance();
		
		$arr = ilUtil::stripSlashesArray( array ($_POST['server_host'], $_POST['server_path'], $_POST['merchant_number'],
      	$_POST['auth_token'], $_POST['auth_email']));    
   	 	$arr['instant_capture'] = isset($_POST['instant_capture']) ? 1 : 0;
		
		$epSet->setAll($arr);
		
		if (!$epSet->valid()) 
		{
			$this->error = $this->lng->txt('pays_epay_settings_not_valid');
			ilUtil::sendFailure($this->error);
			$this->epaySettingsObject();
			return;
		}
		
		$epSet->save();
		ilUtil::sendSuccess($this->lng->txt('pays_updated_epay_settings'));
				
		$this->epaySettingsObject();
		return true;
	}

	/**
	* Generate the ERP setup form for display
	*
	*/	
	private function getERPform_eco(&$op, $erps_id = 0)
	{
	    $erp = new ilERP_eco();
	    $erp->loadSettings($erps_id);
	    $set = $erp->getSettings(0);
	    
	    $fields = array(
	      array("pays_eco_agreement", "agreement", 10),
	      array("username", "username", 16),
	      array("password", "password", 16),
	      array("pays_eco_product_number", "product", 6),
	      array("pays_eco_payment_terms", "terms", 6),
	      array("pays_eco_layout", "layout", 6), 
	      array("pays_eco_cur_handle_code", "code", 3)
	    );
	    
	    foreach ($fields as $f)
	    {
	      $txt = new ilTextInputGUI($this->lng->txt($f[0]), $f[1]);
	      $txt->setMaxLength($f[2]);
	      $txt->setValue($set[$f[1]]);
	      if ($f[0] == 'password') $txt->setInputType('password');
	      $op->addSubItem($txt);
	    }
	}
	
	private function getERPform_none(&$op, $erps_id = 0)
	{
	
	}
	

	/**
	* Generates the settings form for ERP
	*
	*/	
	private function getERPform()
	{

	    require_once './Services/Payment/classes/class.ilERP_eco.php';
	    
	    global $ilias;
	      
	    $systems = ilERP::getAllERPs();
	    $active = ilERP::getActive();    
	    global $ilias;
	
	    $frm = new ilPropertyFormGUI();
	    
		$frm->setFormAction($this->ctrl->getFormAction($this, 'saveEEPsettings'));		
		$frm->setTitle($this->lng->txt('pays_erp_settings'));		
	
		if (ilERP::preview_exists()) 
		{
			$preview_link = "<br/><a href='". ilERP::getPreviewUrl() ."' target='_blank'>" . $this->lng->txt('pays_erp_invoice_preview') . "</a>";
			$frm->addCommandButton('delERPpreview', $this->lng->txt('pays_erp_invoice_delpreview') );
	    }		
		
		$frm->addCommandButton('saveERPsettings',$this->lng->txt('save'));		
		$frm->addCommandButton('testERPsettings',$this->lng->txt('test'));		
		
		$savepdf = new ilCheckboxInputGUI($this->lng->txt('pays_erp_invoice_copies'), 'save_copy');		
		$chk =     new ilCheckboxInputGUI($this->lng->txt('enable_ean'),    'use_ean');
		
		$savepdf->setDisabled( $active['erp_id']  == ERP_NONE);        
    	$chk->setDisabled( $active['erp_id'] == ERP_NONE);

   	 	$rdo = new ilRadioGroupInputGUI($this->lng->txt("pays_erp_system"), "erp_id");
		$rdo->setInfo("The ERP is currently in development");   
		
		$rdo->setValue($active['erp_id']);

    	foreach ($systems as $system)
		{
			$desc = $system['description'];
			$desc .= empty($system['url']) ? '' : ' <a href="'.$system['url'].'" target="_blank">' . $this->lng->txt("additional_info") ."</a>";
      
			$op = new ilRadioOption($system['name'], $system['erp_id'], $desc);
      
			$function = "getERPform_" . $system['erp_short'];      
			$this->$function(&$op, $active['erps_id']);

			$rdo->addOption($op);
    	}      
		$frm->addItem($rdo);
		
		$savepdf->setChecked( $active['save_copy'] );       
	    $chk->setChecked( $active['use_ean']);                
	    
	    $save_msg = $this->lng->txt('pays_erp_invoice_copies_info') .' ' .ilERP::getSaveDirectory();
    
	    if (!is_writable( ilERP::getSaveDirectory() )) $save_msg .= "<br/><b>" . $this->lng->txt('pays_erp_invoice_nowrite') . "</b>";
	    $save_msg .= $preview_link;
	    $savepdf->setInfo($save_msg);
	    $frm->addItem($savepdf);
		
		$chk->setInfo($this->lng->txt('enable_ean_info'));
		$frm->addItem($chk);
		
		return $frm;
	}
	
	/**
	* ERP Object factory. Should be moved
	* @access private
	* @return mixed ERP instance
	*/
	private function getERPObject($system)
	{		
	    require_once './Services/Payment/classes/class.ilERP.php';
	    
		switch ($system)
		{		
	      case ERP_NONE:        
	        require_once './Services/Payment/classes/class.ilERP_none.php';		
	        $instance = new ilERP_none();
	        break;
	      case ERP_ECONOMIC:
	        require_once './Services/Payment/classes/class.ilERP_eco.php';		
	        $instance = new ilERP_eco();                
	        break;
	      default:        
	        throw new ilERPException("System " . $system . " is invalid.");        
	        break;
	    }
    	return $instance;	
	}
	
	private function getERParray()
	{
	    $a = array();    
	    foreach ($_POST as $a_post => $a_value) if ($a_post != 'cmd') $a[$a_post] = ilUtil::stripSlashes($a_value);    
	    $a['use_ean'] =   (isset($_POST['use_ean'])) ? 1 : 0;
	    $a['save_copy'] = (isset($_POST['save_copy'])) ? 1 : 0;
	    return $a;
	}
  

	private function delERPpreviewObject()
	{
		require_once './Services/Payment/classes/class.ilERP.php';
		if (ilERP::preview_exists()) ilERP::preview_delete();
		ilUtil::sendInfo($this->lng->txt('pays_erp_invoice_deleted'));
		$this->erpSettingsObject();
  	}
  
  
  	private function testERPSettingsObject()
  	{
  		global $rbacsystem;
  		global $ilUser;
  		
  		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
  		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_write'),$this->ilias->error_obj->MESSAGE);
		}
			
		try
	    {        	
	      $this->saveERPsettingsObject();		
	      $active = ilERP::getActive();
	      assert ($active['erp_id'] == (int) $_POST['erp_id']);
	        
	      $cls = "ilERPDebtor_" . $active['erp_short'] ;
	      require_once './Services/Payment/classes/class.' . $cls . ".php";
	            
	      if ($active['erp_id']== ERP_NONE) ilUtil::sendInfo($this->lng->txt('saved_successfully'));
	      else
	      { 
	        $deb = new $cls();        
	        $nr = rand(1030,1040);        
	        if ($deb->getDebtorByNumber($nr))
	        {          
	          $good .= $this->lng->txt('pays_erp_tst_existing');        
	        }
	        else
	        {
	          $deb->setTestValues();
	          $deb->createDebtor($nr);
	          
	          $good = $this->lng->txt('pays_erp_tst_new');     
	          
	        } 
	        $good .= " " . $nr . ", " . $deb->getName() . " ";
	        
	        $amount = rand(10,1000);      
	        $pcs = rand(1,10);
	        $good .= $this->lng->txt('pays_erp_tst_billed') . " " . $pcs . " x " . $amount .
	          "<br/>" . $this->lng->txt('total') . " " . number_format( $pcs*$amount, 2, ',','.');
	        
	        $deb->createInvoice();
	        $deb->createInvoiceLine( 0, $this->lng->txt('pays_erp_tst_product'), $pcs, $amount);
	        $deb->createInvoiceLine( 0, "www.ilias.dk", 1, 1);       
	        $v = $deb->bookInvoice();
	        $good .= ", # " . $deb->getInvoiceNumber(); 
	        
	        $attach = $deb->getInvoicePDF($v); 
	        
	        $deb->saveInvoice($attach, true);                 
	        $deb->sendInvoice($this->lng->txt('pay_order_paid_subject'), $deb->getName() . ",\n" . $this->lng->txt('pays_erp_invoice_attached'), $ilUser->getEmail(), $attach, "faktura");
	        
	        $good .= "<br/>" . $ilUser->getEmail() . " => " . $this->lng->txt('mail_sent');      
	        ilUtil::sendInfo($good);       
	      }
	    }
	    catch (ilERPException $e)
	    {
	      ilUtil::sendInfo($good);
	      ilUtil::sendFailure($e->getMessage());
	    }    
		$this->erpSettingsObject();

	}

	
  /**
  * When updating ERP settings test connection and report error.
  */
  private function checkForERPerror(&$instance)
  {
    $message ="";
    
    if (!$instance->looksValid()) $message = str_replace('%s', $instance->getName, $this->lng->txt('pays_erp_bad_settings'));
    else 
    {    
      try
      {
        $instance->connect();
        ilUtil::sendInfo(str_replace('%s', $instance->getName(), $this->lng->txt("pays_erp_connection_established")));
      }
      catch (ilERPException $e)
      {
        ilUtil::sendFailure($e->getMessage());
      }
    }    
  }
   
	/**
	* Save settings for ERP
	*/	
	private function saveERPsettingsObject()
	{
    	global $rbacsystem;
    	if(!$rbacsystem->checkAccess('write', $this->object->getRefId()))
    	{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_write'),$this->ilias->error_obj->MESSAGE);
		}		
		
		$settings = $this->getERParray();    		
		$system = (int) $_POST['erp_id'];		
		$instance = $this->getERPObject($system);				
    	$instance->setSettings($settings);
    
	    switch ($system)
	    {
	      case ERP_NONE:
	        break;
	      case ERP_ECONOMIC:
	        $this->checkForERPerror($instance);
	        break;      
	    }			
		
		$instance->setActive($system);      
	    $instance->saveSettings($settings);
	    ilUtil::sendSuccess(str_replace('%s', $instance->getName(), $this->lng->txt('pays_erp_updated_settings')));
	    $this->erpSettingsObject();    

	    return true;	
	}
	
	/**
	* ERP
	*
	*/	
	function erpSettingsObject($a_show_confirm = false)
	{
		global $rbacsystem;
    	if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}
		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.pays_erp_settings.html','Services/Payment');
		
		$form = $this->getERPform();
		
		$this->tpl->setVariable('ERP_INFO', $this->lng->txt('pays_erp_info'));
		$this->tpl->setVariable('ERP_SETTINGS',$form->getHTML());		    
  }
	
	function paypalSettingsObject($a_show_confirm = false)
	{	
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}

		include_once './Services/Payment/classes/class.ilPaypalSettings.php';		

		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.main_view.html','Services/Payment');
		
		$ppSet = ilPaypalSettings::getInstance();
				
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this, 'savePaypalSettings'));
		$form->setTitle($this->lng->txt('pays_paypal_settings'));
		
		$form->addCommandButton('savePaypalSettings',$this->lng->txt('save'));
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_server_host'), 'server_host');
		$formItem->setValue($ppSet->getServerHost());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_server_path'), 'server_path');
		$formItem->setValue($ppSet->getServerPath());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_email_vendor'), 'vendor');
		$formItem->setValue($ppSet->getVendor());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_auth_token'), 'auth_token');
		$formItem->setValue($ppSet->getAuthToken());
		$form->addItem($formItem);
		
		$formItem = new ilTextInputGUI($this->lng->txt('pays_page_style'), 'page_style');
		$formItem->setValue($ppSet->getPageStyle());
		$form->addItem($formItem);
				
		$this->tpl->setVariable('FORM',$form->getHTML());		
	}
	
	function savePaypalSettingsObject()
	{
		include_once './Services/Payment/classes/class.ilPaypalSettings.php';

		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}
		
		$ppSet = ilPaypalSettings::getInstance();
		
		$ppSet->setServerHost(ilUtil::stripSlashes($_POST['server_host']));
		$ppSet->setServerPath(ilUtil::stripSlashes($_POST['server_path']));
		$ppSet->setVendor(ilUtil::stripSlashes($_POST['vendor']));
		$ppSet->setAuthToken(ilUtil::stripSlashes($_POST['auth_token']));
		$ppSet->setPageStyle(ilUtil::stripSlashes($_POST['page_style']));
		$ppSet->setSsl(ilUtil::stripSlashes($_POST['ssl']));		

		if ($_POST['server_host'] == '' ||
			$_POST['server_path'] == '' ||
			$_POST['vendor'] == '' ||
			$_POST['auth_token'] == '')
		{
			$this->error = $this->lng->txt('pays_paypal_settings_not_valid');
			ilUtil::sendFailure($this->error);
			$this->paypalSettingsObject();
			return;
		}
		
		$ppSet->save();
				
		$this->paypalSettingsObject();

		ilUtil::sendSuccess($this->lng->txt('pays_updated_paypal_settings'));

		return true;
	}

	//function vendorsObject($a_show_confirm = false)
	function vendorsObject($a_show_confirm = false, $confirmation_gui = '')
	{
	//	include_once './Services/Payment/classes/class.ilPaymentBookings.php';
		include_once './Services/Table/classes/class.ilTable2GUI.php';	
	
		global $rbacsystem, $ilToolbar;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}

		$_SESSION['pays_vendor'] = is_array($_SESSION['pays_vendor']) ?  $_SESSION['pays_vendor'] : array();
	
		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.main_view.html','Services/Payment');
		
		$ilToolbar->addButton($this->lng->txt('search_user'), $this->ctrl->getLinkTarget($this, 'searchUser'));

		$this->object->initPaymentVendorsObject();
		if(!count($vendors = $this->object->payment_vendors_obj->getVendors()))
		{
			ilUtil::sendInfo($this->lng->txt('pay_no_vendors_created'));
		}

		if($a_show_confirm)
		{
			$oConfirmationGUI = new ilConfirmationGUI();
			
			// set confirm/cancel commands
			$oConfirmationGUI->setFormAction($this->ctrl->getFormAction($this,"performDeleteVendors"));
			$oConfirmationGUI->setHeaderText($this->lng->txt("pays_sure_delete_selected_vendors"));
			$oConfirmationGUI->setCancel($this->lng->txt("cancel"), "vendors");
			$oConfirmationGUI->setConfirm($this->lng->txt("confirm"), "performDeleteVendors");			
		
			foreach($vendors as $vendor)
			{
				if(in_array($vendor['vendor_id'],$_SESSION['pays_vendor']))
				{
					// GET USER OBJ
					if($tmp_obj = ilObjectFactory::getInstanceByObjId($vendor['vendor_id'],false))
					{
						$delete_row = '';
						$delete_row = $tmp_obj->getLogin().' '.
										$vendor['cost_center'].' '.
										ilPaymentBookings::_getCountBookingsByVendor($vendor['vendor_id']);
						$oConfirmationGUI->addItem('',$delete_row, $delete_row);		
						unset($tmp_obj);
					}
				}
			} // END VENDORS TABLE
			$this->tpl->setVariable('CONFIRMATION', $oConfirmationGUI->getHTML());	#
			return true;		
		}

		$counter = 0;
		$f_result = array();
		foreach($vendors as $vendor)
		{
			// GET USER OBJ
			if($tmp_obj = ilObjectFactory::getInstanceByObjId($vendor['vendor_id'],false))
			{
				$f_result[$counter][]	= ilUtil::formCheckbox(in_array($vendor['vendor_id'],$_SESSION['pays_vendor']) ? 1 : 0,
											"vendor[]", $vendor['vendor_id']);
				$f_result[$counter][]	= $tmp_obj->getLogin();
				$f_result[$counter][]	= $vendor['cost_center'];
				$f_result[$counter][]	= ilPaymentBookings::_getCountBookingsByVendor($vendor['vendor_id']);
				
				unset($tmp_obj);
				++$counter;
			}
		} // END VENDORS TABLE
		$this->__showVendorsTable($f_result);

		return true;
	}

	function exportVendorsObject()
	{
		include_once './Services/Payment/classes/class.ilPaymentExcelWriterAdapter.php';

		$pewa = new ilPaymentExcelWriterAdapter('payment_vendors.xls');

		// add/fill worksheet
		$this->addVendorWorksheet($pewa);
		$this->addStatisticWorksheet($pewa);

		// HEADER SENT
		
		$workbook = $pewa->getWorkbook();
		@$workbook->close();
	}

	function addStatisticWorksheet(&$pewa)
	{
		include_once './Services/Excel/classes/class.ilExcelUtils.php';
		include_once './Services/Payment/classes/class.ilPaymentVendors.php';

		$this->__initBookingObject();

		$workbook = $pewa->getWorkbook();
		$worksheet = $workbook->addWorksheet(utf8_decode($this->lng->txt('bookings')));	
		
		$worksheet->mergeCells(0,0,0,3);
		$worksheet->setColumn(0,0,16);
		$worksheet->setColumn(0,1,32);
		$worksheet->setColumn(0,2,32);
		$worksheet->setColumn(0,3,16);
		$worksheet->setColumn(0,4,16);
		$worksheet->setColumn(0,5,16);
		$worksheet->setColumn(0,6,24);
		$worksheet->setColumn(0,7,8);
		$worksheet->setColumn(0,8,12);
		$worksheet->setColumn(0,9,16);

		$title = $this->lng->txt('bookings');
		$title .= ' '.$this->lng->txt('as_of').' ';
		$title .= strftime('%Y-%m-%d %R',time());

		$worksheet->writeString(0,0,$title,$pewa->getFormatTitle());

		$worksheet->writeString(1,0,ilExcelUtils::_convert_text($this->lng->txt('payment_system')),$pewa->getFormatHeader());
		$worksheet->writeString(1,1,ilExcelUtils::_convert_text($this->lng->txt('paya_transaction')),$pewa->getFormatHeader());
		$worksheet->writeString(1,2,ilExcelUtils::_convert_text($this->lng->txt('title')),$pewa->getFormatHeader());
		$worksheet->writeString(1,3,ilExcelUtils::_convert_text($this->lng->txt('paya_vendor')),$pewa->getFormatHeader());
		$worksheet->writeString(1,4,ilExcelUtils::_convert_text($this->lng->txt('pays_cost_center')),$pewa->getFormatHeader());
		$worksheet->writeString(1,5,ilExcelUtils::_convert_text($this->lng->txt('paya_customer')),$pewa->getFormatHeader());
		$worksheet->writeString(1,6,ilExcelUtils::_convert_text($this->lng->txt('email')),$pewa->getFormatHeader());
		$worksheet->writeString(1,7,ilExcelUtils::_convert_text($this->lng->txt('paya_order_date')),$pewa->getFormatHeader());
		$worksheet->writeString(1,8,ilExcelUtils::_convert_text($this->lng->txt('duration')),$pewa->getFormatHeader());
		$worksheet->writeString(1,9,ilExcelUtils::_convert_text($this->lng->txt('price_a')),$pewa->getFormatHeader());
		$worksheet->writeString(1,10,ilExcelUtils::_convert_text($this->lng->txt('paya_payed_access')),$pewa->getFormatHeader());
		
		$worksheet->writeString(1,11,ilExcelUtils::_convert_text($this->lng->txt('street')),$pewa->getFormatHeader());
		$worksheet->writeString(1,12,ilExcelUtils::_convert_text($this->lng->txt('pay_bmf_po_box')),$pewa->getFormatHeader());
		$worksheet->writeString(1,13,ilExcelUtils::_convert_text($this->lng->txt('zipcode')),$pewa->getFormatHeader());
		$worksheet->writeString(1,14,ilExcelUtils::_convert_text($this->lng->txt('city')),$pewa->getFormatHeader());
		$worksheet->writeString(1,15,ilExcelUtils::_convert_text($this->lng->txt('country')),$pewa->getFormatHeader());
		
		if(!count($bookings = $this->booking_obj->getBookings()))
		{
			return false;
		}

		include_once 'Services/User/classes/class.ilObjUser.php';
		$object_title_cache = array();
		$user_title_cache = array();

		$counter = 2;
		foreach($bookings as $booking)
		{
			if(array_key_exists($booking['ref_id'], $object_title_cache))
			{
				$tmp_obj = $object_title_cache[$booking['ref_id']];
			}
			else
			{
				$tmp_obj = ilObject::_lookupTitle(ilObject::_lookupObjId($booking['ref_id']));				
				$object_title_cache[$booking['ref_id']] = $tmp_obj;
			}
			if(array_key_exists($booking['b_vendor_id'], $user_title_cache))
			{
				$tmp_vendor = $user_title_cache[$booking['b_vendor_id']];
			}
			else
			{
				$tmp_vendor = ilObjUser::_lookupLogin($booking['b_vendor_id']);
				$user_title_cache[$booking['b_vendor_id']] = $tmp_vendor;
			}
			if(array_key_exists($booking['customer_id'], $user_title_cache))
			{
				$tmp_purchaser = $user_title_cache[$booking['customer_id']];
			}
			else
			{
				$tmp_purchaser_name = ilObjUser::_lookupName($booking['customer_id']);
				$tmp_purchaser_login = ilObjUser::_lookupLogin($booking['customer_id']);
				$tmp_purchaser_email = ilObjUser::_lookupEmail($booking['customer_id']);
				$tmp_purchaser = ''.$tmp_purchaser_name['firstname'].' '.$tmp_purchaser_name['lastname'].' ['.$tmp_purchaser_login.']';
				$user_title_cache[$booking['customer_id']] = $tmp_purchaser;
			}
			
			include_once './Services/Payment/classes/class.ilPayMethods.php';
			$str_paymethod = ilPayMethods::getStringByPaymethod($booking['b_pay_method']);	
		
			$worksheet->writeString($counter,0,ilExcelUtils::_convert_text($str_paymethod));
			$worksheet->writeString($counter,1,ilExcelUtils::_convert_text($booking['transaction']));
			$worksheet->writeString($counter,2,ilExcelUtils::_convert_text(($tmp_obj != '' ? $tmp_obj : $this->lng->txt('object_deleted'))));
			$worksheet->writeString($counter,3,ilExcelUtils::_convert_text(($tmp_vendor != '' ? $tmp_vendor : $this->lng->txt('user_deleted'))));
			$worksheet->writeString($counter,4,ilExcelUtils::_convert_text(ilPaymentVendors::_getCostCenter($booking['b_vendor_id'])));
			$worksheet->writeString($counter,5,ilExcelUtils::_convert_text(($tmp_purchaser != '' ? $tmp_purchaser : $this->lng->txt('user_deleted'))));
			$worksheet->writeString($counter,6,ilExcelUtils::_convert_text($tmp_purchaser_email));
			$worksheet->writeString($counter,7,strftime('%Y-%m-%d %R',$booking['order_date']));
			$worksheet->writeString($counter,8,$booking['duration']);
			$worksheet->writeString($counter,9,ilExcelUtils::_convert_text($booking['price']));
			
			$payed_access = $booking['payed'] ? 
				$this->lng->txt('yes') : 
				$this->lng->txt('no');

			$payed_access .= '/';
			$payed_access .= $booking['access_granted'] ?
				$this->lng->txt('yes') : 
				$this->lng->txt('no');

			$worksheet->writeString($counter,10,$payed_access);

			$worksheet->writeString($counter,11,ilExcelUtils::_convert_text($booking['street']));
			$worksheet->writeString($counter,12,ilExcelUtils::_convert_text($booking['po_box']));
			$worksheet->writeString($counter,13,ilExcelUtils::_convert_text($booking['zipcode']));
			$worksheet->writeString($counter,14,ilExcelUtils::_convert_text($booking['city']));
			$worksheet->writeString($counter,15,ilExcelUtils::_convert_text($booking['country']));
			
			unset($tmp_obj);
			unset($tmp_vendor);
			unset($tmp_purchaser);

			++$counter;
		}
	}		

	function addVendorWorksheet($pewa)
	{
		include_once './Services/Excel/classes/class.ilExcelUtils.php';

		$this->object->initPaymentVendorsObject();

		$workbook = $pewa->getWorkbook();
		$worksheet = $workbook->addWorksheet(ilExcelUtils::_convert_text($this->lng->txt('pays_vendor')));

		// SHOW HEADER
		$worksheet->mergeCells(0,0,0,2);
		$worksheet->setColumn(1,0,32);
		$worksheet->setColumn(1,1,32);
		$worksheet->setColumn(1,2,32);

		$title = $this->lng->txt('bookings');
		$title .= ' '.$this->lng->txt('as_of').' ';
		$title .= strftime('%Y-%m-%d %R',time());

		$worksheet->writeString(0,0,$title,$pewa->getFormatTitle());

		$worksheet->writeString(1,0,ilExcelUtils::_convert_text($this->lng->txt('login')),$pewa->getFormatHeader());
		$worksheet->writeString(1,1,ilExcelUtils::_convert_text($this->lng->txt('fullname')),$pewa->getFormatHeader());
		$worksheet->writeString(1,2,ilExcelUtils::_convert_text($this->lng->txt('pays_cost_center')),$pewa->getFormatHeader());

		if(!count($vendors = $this->object->payment_vendors_obj->getVendors()))
		{
			return false;
		}

		$counter = 2;
		foreach($vendors as $vendor)
		{
			// GET USER OBJ
			if($tmp_obj = ilObjectFactory::getInstanceByObjId($vendor['vendor_id'],false))
			{
				$worksheet->writeString($counter,0,ilExcelUtils::_convert_text($tmp_obj->getLogin()));
				$worksheet->writeString($counter,1,ilExcelUtils::_convert_text($tmp_obj->getFullname()));
				$worksheet->writeString($counter,2,ilExcelUtils::_convert_text($vendor['cost_center']));
			}
			unset($tmp_obj);
			++$counter;
		}
	}		
	
	function payMethodsObject($askForDeletingAddresses = array(),$oConfirmationGUI = '')
	{
		include_once './Services/Payment/classes/class.ilPayMethods.php';
		include_once './Services/Table/classes/class.ilTable2GUI.php';

		global $rbacsystem, $ilCtrl;
		
		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.main_view.html','Services/Payment');
		
		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}
	
		if(count($askForDeletingAddresses))
		{
			$oConfirmationGUI = new ilConfirmationGUI();
			
			// set confirm/cancel commands
			$oConfirmationGUI->setFormAction($ilCtrl->getFormAction($this, "deleteAddressesForPaymethods"));
			$oConfirmationGUI->setHeaderText($this->lng->txt("info_delete_sure"));
			$oConfirmationGUI->setCancel($this->lng->txt("cancel"), "paymethods");
			$oConfirmationGUI->setConfirm($this->lng->txt("confirm"), "deleteAddressesForPaymethods");
			
			foreach($askForDeletingAddresses as $pm_id)
			{
				$pm_obj = new ilPayMethods($pm_id);
				
				$oConfirmationGUI->addHiddenItem('pm_id[]',$pm_id);
				$oConfirmationGUI->additem('paymethod',$pm_obj->getPmId(), $this->lng->txt('delete_addresses_bill').' -> '.ilPayMethods::getStringByPaymethod($pm_obj->getPmTitle()));
			}
			
			$this->tpl->setVariable('CONFIRMATION', $oConfirmationGUI->getHtml());
			return true;	
		}
		
		$obj_paymethods = new ilPayMethods();
		$paymethods = $obj_paymethods->readAll();

		
		$tbl = new ilTable2GUI($this, 'paymethods');
		$tbl->setId('pay_methods_tbl');
		$tbl->setFormAction($this->ctrl->getFormAction($this), 'savePayMethods');
		
		$result = array();		$counter = 0;
		foreach($paymethods as $paymethod)
		{
			//$result[$counter][] = $this->lng->txt('pays_online');
			$result[$counter][] = ilPayMethods::getStringByPaymethod($paymethod['pm_title']);
			$result[$counter][] = ilUtil::formCheckbox($paymethod['pm_enabled'] ? 1 : 0,'pm_enabled['.$paymethod['pm_id'].']',1);								
			$result[$counter][] = ilUtil::formCheckbox($paymethod['save_usr_adr'] ? 1 : 0,'save_usr_adr['.$paymethod['pm_id'].']',1);
			$this->ctrl->clearParameters($this);
			$counter++;
		}
		
		

		$counter = 0;
		$tbl = $this->__initTableGUI();
		$tpl = $tbl->getTemplateObject();

		// SET FORMACTION
		$tpl->setCurrentBlock('tbl_form_header');

		$tpl->setVariable('FORMACTION',$this->ctrl->getFormAction($this), 'savePayMethods');
		
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt('pays_pay_methods'),'icon_pays.gif',$this->lng->txt('bookings'));

		$tbl->setHeaderNames(array(
								   $this->lng->txt('paya_pay_method'),
								   $this->lng->txt('enabled'),
								   $this->lng->txt('save_customer_address')));

		$tbl->setHeaderVars(array(
								  'pm_title',
								  'pm_enabled',
								  'save_usr_adr'),
							$this->ctrl->getParameterArray($this,'paymethods',false));
		
		$offset = $_GET['offset'];

		$tbl->disable('sort');
		$tbl->disable('linkbar');
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET['limit']);
		$tbl->setMaxCount(count($result));
		$tbl->setFooter('tblfooter',$this->lng->txt('previous'),$this->lng->txt('next'));
		$tbl->setData($result);

		$tpl->setVariable('COLUMN_COUNTS',3);
		$tpl->setCurrentBlock('plain_buttons');
		$tpl->setVariable('PBTN_NAME','savePayMethods');
		$tpl->setVariable('PBTN_VALUE',$this->lng->txt('save'));
		$tpl->parseCurrentBlock();
		$tbl->render();

		$this->tpl->setVariable('TABLE',$tbl->tpl->get());	
		return true;		
	}

	function savePayMethodsObject()
	{
		include_once './Services/Payment/classes/class.ilPayMethods.php';

		global $rbacsystem;
		
		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}

		$count_pm = ilPayMethods::countPM();

		$askForDeletingAddresses = array();
		
		for($i = 1; $i <= $count_pm; $i++)
		{
			if(!array_key_exists($i,$_POST['pm_enabled']) && ilPayMethods::_PmEnabled($i) == 1)
			{
				if(ilPaymentObject::_getCountObjectsByPayMethod($i))
				{
					ilUtil::sendInfo($this->lng->txt('pays_objects_bill_exist'));
					$this->payMethodsObject();
	
					return false;
				}
				else ilPayMethods::_PMdisable($i);
			}
			else 
			if(!array_key_exists($i,$_POST['pm_enabled']) && ilPayMethods::_PmEnabled($i) == 0)
			{
				continue;
			}
			else
			{
				ilPayMethods::_PMenable($i);
			}

			if(!array_key_exists($i,$_POST['save_usr_adr']) && ilPayMethods::_EnabledSaveUserAddress($i) == 1)
			{
				$askForDeletingAddresses[] = $i;
			}
			else 
			if(!array_key_exists($i,$_POST['save_usr_adr']) && ilPayMethods::_EnabledSaveUserAddress($i) == 0)
			{
				continue;
			}
			else
			{ 
				ilPayMethods::_enableSaveUserAddress($i);	
			}

		}
		$tmp = $this->payMethodsObject($askForDeletingAddresses,$oConfirmationGUI);
		if(!$askForDeletingAddresses)
			ilUtil::sendSuccess($this->lng->txt('pays_updated_pay_method'));
		

		return true;
	}	

	function cancelDeleteVendorsObject()
	{
		unset($_SESSION['pays_vendor']);
		$this->vendorsObject();

		return true;
	}

	function deleteVendors()
	{
		//include_once './Services/Payment/classes/class.ilPaymentBookings.php';

		if(!count($_POST['vendor']))
		{
			ilUtil::sendFailure($this->lng->txt('pays_no_vendor_selected'));
			$this->vendorsObject();

			return true;
		}
		// CHECK BOOKINGS
		foreach($_POST['vendor'] as $vendor)
		{
			if(ilPaymentBookings::_getCountBookingsByVendor($vendor))
			{
				ilUtil::sendInfo($this->lng->txt('pays_active_bookings'));
				$this->vendorsObject();

				return true;
			}
		}
		
		$_SESSION['pays_vendor'] = $_POST['vendor'];
		ilUtil::sendQuestion($this->lng->txt('pays_sure_delete_selected_vendors'));
		$this->vendorsObject(true);

		return true;
	}
	
	function performDeleteVendorsObject()
	{
		include_once './Services/Payment/classes/class.ilPaymentTrustees.php';
		
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('write', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_write'),$this->ilias->error_obj->MESSAGE);
		}

		$this->object->initPaymentVendorsObject();

		foreach($_SESSION['pays_vendor'] as $vendor)
		{
			$this->object->payment_vendors_obj->delete($vendor);
			ilPaymentTrustees::_deleteTrusteesOfVendor($vendor);
		}

		ilUtil::sendInfo($this->lng->txt('pays_deleted_number_vendors').' '.count($_SESSION['pays_vendor']));
		unset($_SESSION['pays_vendor']);
		
		$this->vendorsObject();

		return true;
	}

	function editVendor()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess("write", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}

		if(!count($_POST['vendor']))
		{
			ilUtil::sendInfo($this->lng->txt('pays_no_vendor_selected'));
			$this->vendorsObject();

			return true;
		}
		if(count($_POST['vendor']) > 1)
		{
			ilUtil::sendInfo($this->lng->txt('pays_too_many_vendors_selected'));
			$this->vendorsObject();

			return true;
		}

		$_SESSION['pays_vendor'] = $_POST['vendor'][0];

		$this->object->initPaymentVendorsObject();

		if (!is_array($this->object->payment_vendors_obj->vendors[$_SESSION['pays_vendor']]))
		{
			$this->vendorsObject();

			return true;
		}
	
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');	
		
		$form_gui = new ilPropertyFormGUI();
		$form_gui->setFormAction($this->ctrl->getFormAction($this, 'performEditVendor'));
		$form_gui->setTitle($this->lng->txt('pays_vendor'));

		$oVendorGUI = new ilNonEditableValueGUI($this->lng->txt('pays_vendor'));		
		$oVendorGUI->setValue(ilObjUser::getLoginByUserId($this->object->payment_vendors_obj->vendors[$_SESSION['pays_vendor']]['vendor_id']), true);	
		$form_gui->addItem($oVendorGUI);	
		
		$oCostcenterGUI = new ilTextInputGUI($this->lng->txt('pays_cost_center'),'cost_center');
		$oCostcenterGUI->setValue($this->error != '' && isset($_POST['cost_center'])
								? ilUtil::prepareFormOutput($_POST['cost_center'],true)
								: ilUtil::prepareFormOutput($this->object->payment_vendors_obj->vendors[$_SESSION['pays_vendor']]['cost_center'],true));
		$form_gui->addItem($oCostcenterGUI);	

		$form_gui->addCommandButton('performEditVendor',$this->lng->txt('save'));
		$this->tpl->setVariable('FORM', $form_gui->getHTML());
	}
	
	function performEditVendorObject()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('write', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_write'),$this->ilias->error_obj->MESSAGE);
		}

		if(!count($_SESSION['pays_vendor']))
		{
			ilUtil::sendInfo($this->lng->txt('pays_no_vendor_selected'));
			$this->vendorsObject();

			return true;
		}
		if(count($_SESSION['pays_vendor']) > 1)
		{
			ilUtil::sendInfo($this->lng->txt('pays_too_many_vendors_selected'));
			$this->vendorsObject();

			return true;
		}

		$this->object->initPaymentVendorsObject();

		if (!is_array($this->object->payment_vendors_obj->vendors[$_SESSION['pays_vendor']]))
		{
			$this->vendorsObject();
			return true;
		}

		if ($_POST['cost_center'] == '')
		{
			$this->error = $this->lng->txt('pays_cost_center_not_valid');
			ilUtil::sendFailure($this->error);
			$_POST['vendor'] = array($_SESSION['pays_vendor']);
			$this->editVendor();
			return;
		}

		$this->object->initPaymentVendorsObject();
		$this->object->payment_vendors_obj->update($_SESSION['pays_vendor'], $_POST['cost_center']);

		unset($_SESSION['pays_vendor']);

		$this->vendorsObject();

		return true;
	}

	function showObjectSelectorObject()
	{
		global $rbacsystem, $tree, $ilToolbar;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}

		include_once './Services/Payment/classes/class.ilPaymentObjectSelector.php';

		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.paya_object_selector.html','Services/Payment');
		
		$ilToolbar->addButton($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, 'statistic'));

		ilUtil::sendInfo($this->lng->txt('paya_select_object_to_sell'));

		$exp = new ilPaymentObjectSelector($this->ctrl->getLinkTarget($this,'showObjectSelector'), strtolower(get_class($this)));
		$exp->setExpand($_GET['paya_link_expand'] ? $_GET['paya_link_expand'] : $tree->readRootId());
		$exp->setExpandTarget($this->ctrl->getLinkTarget($this,'showObjectSelector'));
		
		$exp->setOutput(0);

		$this->tpl->setVariable('EXPLORER',$exp->getOutput());

		return true;
	}

	function searchUserObject()
	{
		global $rbacsystem, $ilToolbar;

		// MINIMUM ACCESS LEVEL = 'read'
		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}

		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');
		
		$ilToolbar->addButton($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, 'vendors'));

		$this->lng->loadLanguageModule('search');

		$form_gui = new ilPropertyFormGUI();
		$form_gui->setFormAction($this->ctrl->getFormAction($this));
		$form_gui->setTitle($this->lng->txt('crs_search_members'));
		$form_gui->setId('search_form');
	
		$oTitle = new ilTextInputGUI($this->lng->txt('search_search_term'), 'search_str');
		$oTitle->setMaxLength(255);
		$oTitle->setSize(40);
		$oTitle->setValue($_POST['search_str']); // $_SESSION['pays_search_str']
		$form_gui->addItem($oTitle);		
		
		// buttons
		$form_gui->addCommandButton('search', $this->lng->txt('search'));
		$form_gui->addCommandButton('vendors', $this->lng->txt('cancel'));	//??vendors	
		
		$this->tpl->setVariable('FORM',$form_gui->getHTML());	
		return true;
	}

	function searchObject()
	{
		global $rbacsystem,$tree, $ilToolbar;

		if(!$rbacsystem->checkAccess('read', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'),$this->ilias->error_obj->MESSAGE);
		}

		$_SESSION['pays_search_str'] = $_POST['search_str'] = $_POST['search_str'] ? $_POST['search_str'] : $_SESSION['pays_search_str'];

		if(!isset($_POST['search_str']))
		{
			ilUtil::sendInfo($this->lng->txt('crs_search_enter_search_string'));
			$this->searchUserObject();
			
			return false;
		}
		if(!count($result = $this->__search(ilUtil::stripSlashes($_POST['search_str']))))
		{
			ilUtil::sendInfo($this->lng->txt('crs_no_results_found'));
			$this->searchUserObject();

			return false;
		}

		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.main_view.html','Services/Payment');
		$ilToolbar->addButton($this->lng->txt('crs_new_search'), $this->ctrl->getLinkTarget($this, 'searchUser'));
		
		$counter = 0;
		$f_result = array();
		foreach($result as $user)
		{
			if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($user['id'],false))
			{
				continue;
			}
			$f_result[$counter][] = ilUtil::formCheckbox(0,'user[]',$user['id']);
			$f_result[$counter][] = $tmp_obj->getLogin();
			$f_result[$counter][] = $tmp_obj->getLastname();
			$f_result[$counter][] = $tmp_obj->getFirstname();

			unset($tmp_obj);
			++$counter;
		}
		$this->__showSearchUserTable($f_result);

		return true;
	}
	
	function addVendorObject()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'administrate'
		if(!$rbacsystem->checkAccess('write', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_write'),$this->ilias->error_obj->MESSAGE);
		}
		if(!$_POST['vendor_login'])
		{
			ilUtil::sendFailure($this->lng->txt('pays_no_username_given'));
			$this->vendorsObject();

			return true;
		}
		if(!($usr_id = ilObjUser::getUserIdByLogin(ilUtil::stripSlashes($_POST['vendor_login']))))
		{
			ilUtil::sendFailure($this->lng->txt('pays_no_valid_username_given'));
			$this->vendorsObject();

			return true;
		}
		
		$this->object->initPaymentVendorsObject();

		if($this->object->payment_vendors_obj->isAssigned($usr_id))
		{
			ilUtil::sendFailure($this->lng->txt('pays_user_already_assigned'));
			$this->vendorsObject();

			return true;
		}
		$this->object->payment_vendors_obj->add($usr_id);

		ilUtil::sendSuccess($this->lng->txt('pays_added_vendor'));
		$this->vendorsObject();
		
		return true;
	}
		
	function addUserObject()
	{
		global $rbacsystem;

		// MINIMUM ACCESS LEVEL = 'administrate'
		if(!$rbacsystem->checkAccess('write', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_write'),$this->ilias->error_obj->MESSAGE);
		}

		$this->lng->loadLanguageModule('crs');
		if(!is_array($_POST['user']))
		{
			ilUtil::sendFailure($this->lng->txt('crs_no_users_selected'));
			$this->searchObject();

			return false;
		}
		
		$this->object->initPaymentVendorsObject();

		$already_assigned = $assigned = 0;
		foreach($_POST['user'] as $usr_id)
		{
			if($this->object->payment_vendors_obj->isAssigned($usr_id))
			{
				++$already_assigned;
				continue;
			}
			$this->object->payment_vendors_obj->add($usr_id);
			++$assigned;
			
			// TODO: SEND NOTIFICATION
		}
		$message = '';
		if($assigned)
		{
			$message .= $this->lng->txt('pays_assigned_vendors').' '.$assigned;
		}
		if($already_assigned)
		{
			$message .= '<br />'.$this->lng->txt('pays_already_assigned_vendors').' '.$already_assigned;
		}

		ilUtil::sendInfo($message);
		$this->vendorsObject();

		return true;
	}		

	function searchUserSPObject()
	{
		global $ilToolbar;
		
		if(!isset($_GET['sell_id']))
		{
			ilUtil::sendFailiure($this->lng->txt('paya_no_booking_id_given'));
			$this->showObjectSelectorObject();

			return false;
		}

		$ilToolbar->addButton($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, 'showObjectSelector'));
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');
		
		$this->lng->loadLanguageModule('search');
		$this->ctrl->setParameter($this, 'sell_id', $_GET['sell_id']);

		$form_gui = new ilPropertyFormGUI();
		$form_gui->setFormAction($this->ctrl->getFormAction($this));
		$form_gui->setTitle($this->lng->txt('search_user'));
		$form_gui->setId('search_form');
	
		$oTitle = new ilTextInputGUI($this->lng->txt('search_search_term'), 'search_str');
		$oTitle->setMaxLength(255);
		$oTitle->setSize(40);
		$oTitle->setValue($_POST['search_str']);
		$form_gui->addItem($oTitle);		
		
		// buttons
		$form_gui->addCommandButton('performSearchSP', $this->lng->txt('search'));
		$form_gui->addCommandButton('bookings', $this->lng->txt('cancel'));		
		
		$this->tpl->setVariable('FORM',$form_gui->getHTML());	
		return true;
	}

	function performSearchSPObject()
	{
		global $ilToolbar;
		// SAVE it to allow sort in tables
		$_SESSION['paya_search_str_user_sp'] = $_POST['search_str'] = $_POST['search_str'] ? $_POST['search_str'] : $_SESSION['paya_search_str_user_sp'];

		if(!trim($_POST['search_str']))
		{
			ilUtil::sendFailure($this->lng->txt('search_no_search_term'));
			$this->statistics();

			return false;
		}
		if(!count($result = $this->__search(ilUtil::stripSlashes($_POST['search_str']))))
		{
			ilUtil::sendInfo($this->lng->txt('search_no_match'));
			$this->searchUserSPObject();

			return false;
		}

		if(!isset($_GET['sell_id']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_booking_id_given'));
			$this->showObjectSelectorObject();

			return false;
		}

	
		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.main_view.html','Services/Payment');
		$this->ctrl->setParameter($this, 'sell_id', $_GET['sell_id']);

		$ilToolbar->addButton($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, 'searchUserSP'));
		
		$counter = 0;
		$f_result = array();
		foreach($result as $user)
		{
			if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($user['id'],false))
			{
				continue;
			}
			$f_result[$counter][] = ilUtil::formRadiobutton(0,'user_id',$user['id']);
			$f_result[$counter][] = $tmp_obj->getLogin();
			$f_result[$counter][] = $tmp_obj->getFirstname();
			$f_result[$counter][] = $tmp_obj->getLastname();
			
			unset($tmp_obj);
			++$counter;
		}
		$this->__showSearchUserSPTable($f_result);
	}

	function addCustomerObject()
	{
		global $ilToolbar;
		if ($_POST['sell_id'] != '') $_GET['sell_id'] = $_POST['sell_id'];
		if ($_GET['user_id'] != '') $_POST['user_id'] = $_GET['user_id'];

		if(!isset($_GET['sell_id']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_booking_id_given'));
			$this->showObjectSelectorObject();

			return true;
		}

		if(!isset($_POST['user_id']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_no_user_id_given'));
			$this->searchUserSPObject();

			return true;
		}
		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.main_view.html','Services/Payment');
		$this->ctrl->setParameter($this, 'sell_id', $_GET['sell_id']);

		$ilToolbar->addButton($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, 'searchUserSP'));

		$this->ctrl->setParameter($this, 'user_id', $_POST['user_id']);

		$pObjectId = ilPaymentObject::_lookupPobjectId($_GET['sell_id']);
		$obj = new ilPaymentObject($this->user_obj, $pObjectId);

		// get obj
		$tmp_obj = ilObjectFactory::getInstanceByRefId($_GET['sell_id']);
		// get customer_obj
		$tmp_user = ilObjectFactory::getInstanceByObjId($_POST['user_id']);
		// get vendor_obj
		$tmp_vendor = ilObjectFactory::getInstanceByObjId($obj->getVendorId());
/**/

		$oForm = new ilPropertyFormGUI();
		$oForm->setFormAction($this->ctrl->getFormAction($this, 'saveCustomer'));
		$oForm->setTitle($this->lng->txt($tmp_user->getFullname().' ['.$tmp_user->getLogin().']'));
		
		//transaction
		$oTransaction = new ilTextInputGUI();
		$oTransaction->setTitle($this->lng->txt('paya_transaction'));
		//$oTransaction->setValue(ilUtil::prepareFormOutut($_POST['transaction'], true));
		$oTransaction->setValue($_POST['transaction']);
		$oTransaction->setPostVar('transaction');
		$oForm->addItem($oTransaction);
		
		//object
		$oObject = new ilNonEditableValueGUI($this->lng->txt('title'));
		$oObject->setValue($tmp_obj->getTitle());
		$oForm->addItem($oObject);
		
		//vendor
		$oVendor = new ilNonEditableValueGUI($this->lng->txt('paya_vendor'));
		$oVendor->setValue($tmp_vendor->getFullname().' ['.$tmp_vendor->getLogin().']');
		$oForm->addItem($oVendor);
		
		// pay methods
		$oPayMethods = new ilSelectInputGUI($this->lng->txt('paya_pay_method'), 'pay_method');
		$payOptions = ilPaymethods::getPayMethodsOptions(false);
		$oPayMethods->setOptions($payOptions);
		$oPayMethods->setValue($_POST['pay_method']);
		$oPayMethods->setPostVar('pay_method');
		$oForm->addItem($oPayMethods);	
		
		
		//duration
		$duration_opions = array();	
		$prices_obj = new ilPaymentPrices($pObjectId);
		$prices = $prices_obj->getPrices();

		
		if (is_array($prices = $prices_obj->getPrices()))
		{
			foreach($prices as $price)
			{
				$duration_options[$price['price_id']] = 
					#$price['duration'].' '.$this->lng->txt('paya_months').', '.ilPaymentPrices::_getPriceString($price['price_id']);
					$price['duration'].' '.$this->lng->txt('paya_months').', '. ilPaymentPrices::_getPrice($price['price_id']).' '.ilPaymentCurrency::_getUnit($price['currency']);
			}
		}

		$oDuration = new ilSelectInputGUI($this->lng->txt('duration'), 'duration');
		$oDuration->setOptions($duration_options);
		$oDuration->setValue($_POST['duration']);
		$oForm->addItem($oDuration);	

		//payed		
		$o_payed = new ilSelectInputGUI();
		$payed_option = array('1'=>$this->lng->txt('yes'),'0'=>$this->lng->txt('no'));

		$o_payed->setTitle($this->lng->txt('paya_payed'));
		$o_payed->setOptions($payed_option);
		$o_payed->setValue($_POST['payed']);
		$o_payed->setPostVar('payed');		
		$oForm->addItem($o_payed);	


		$o_access = new ilSelectInputGUI();
		$access_option = array('1'=>$this->lng->txt('yes'),'0'=>$this->lng->txt('no'));

		$o_access->setTitle($this->lng->txt('paya_access'));
		$o_access->setOptions($access_option);
		$o_access->setValue($_POST['access']);
		$o_access->setPostVar('access');
		
		$oForm->addItem($o_access);	

		$oForm->addCommandButton('saveCustomer',$this->lng->txt('save'));
		$oForm->addCommandButton('bookings', $this->lng->txt('cancel'));	
		
		$this->tpl->setVariable('FORM',$oForm->getHTML());
		return true;
	}

	function saveCustomerObject()
	{
		global $ilias, $ilUser,$ilObjDataCache;

		if(!isset($_GET['sell_id']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_error_no_object_id_given'));
			$this->showObjectSelectorObject();

			return true;
		}

		if(!isset($_GET['user_id']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_error_no_user_id_given'));
			$this->searchUserSPObject();

			return true;
		}

		if ($_POST['pay_method'] == '' ||
			$_POST['duration'] == '')
		{
			ilUtil::sendInfo($this->lng->txt('paya_error_mandatory_fields'));
			$this->addCustomerObject();

			return true;
		}

		$pObjectId = ilPaymentObject::_lookupPobjectId($_GET['sell_id']);
		$obj = new ilPaymentObject($this->user_obj, $pObjectId);

		$this->__initBookingObject();

		$inst_id_time = $ilias->getSetting('inst_id').'_'.$this->user_obj->getId().'_'.substr((string) time(),-3);
		$transaction = $inst_id_time.substr(md5(uniqid(rand(), true)), 0, 4);
		$this->booking_obj->setTransaction($transaction);
		$this->booking_obj->setTransactionExtern($_POST['transaction']);
		$this->booking_obj->setPobjectId($pObjectId);
		$this->booking_obj->setCustomerId($_GET['user_id']);
		$this->booking_obj->setVendorId($obj->getVendorId());

		$this->booking_obj->setPayMethod($_POST['pay_method']); 
		$this->booking_obj->setOrderDate(time());

		$price = ilPaymentPrices::_getPrice($_POST['duration']);
		$currency = ilPaymentCurrency::_getUnit($price['currency']); 
		$this->booking_obj->setDuration($price['duration']);
		$this->booking_obj->setPrice($price['price']);
		$this->booking_obj->setAccess((int) $_POST['access']);
		$this->booking_obj->setPayed((int) $_POST['payed']);
		$this->booking_obj->setVoucher('');
		
		$obj_id = $ilObjDataCache->lookupObjId($obj->getRefId());
		$obj_type = $ilObjDataCache->lookupType($obj_id);
		$obj_title = $ilObjDataCache->lookupTitle($obj_id);

	//	include_once 'Services/Payment/classes/class.ilShopVatsList.php';
		$oVAT = new ilShopVats((int)$obj->getVatId());
		$obj_vat_rate = $oVAT->getRate();
		$obj_vat_unit = $obj->getVat($this->booking_obj->getPrice());
	
		$this->booking_obj->setObjectTitle($obj_title);
		$this->booking_obj->setVatRate($obj_vat_rate);
		$this->booking_obj->setVatUnit($obj_vat_unit);

		$currency_id = $price['currency'];
		$currency_unit = ilPaymentCurrency::_getUnit($currency_id);
		$this->booking_obj->setCurrencyUnit($currency_unit);	
		include_once './Services/Payment/classes/class.ilPayMethods.php';

		$save_user_address_enabled = ilPayMethods::_EnabledSaveUserAddress($this->booking_obj->getPayMethod());
		if($save_user_address_enabled == 1)
		{
			global $ilObjUser;
			$user_id[] = $_GET["user_id"];
		
			$cust_obj = ilObjUser::_readUsersProfileData($user_id);
		
			$this->booking_obj->setStreet($cust_obj[$_GET["user_id"]]['street'],'');
			
			$this->booking_obj->setZipcode($cust_obj[$_GET["user_id"]]['zipcode']);
			$this->booking_obj->setCity($cust_obj[$_GET["user_id"]]['city']);
			$this->booking_obj->setCountry($cust_obj[$_GET["user_id"]]['country']);
		}			
		
		if($this->booking_obj->add())
		{
			ilUtil::sendInfo($this->lng->txt('paya_customer_added_successfully'));
			$this->statisticObject();
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt('paya_error_adding_customer'));
			$this->addCustomerObject();
		}
		return true;
	}

	// PRIVATE
	function __setSection($a_section)
	{
		$this->section = $a_section;
	}
	function __getSection()
	{
		return $this->section;
	}
	function __setMainSection($a_section)
	{
		$this->mainSection = $a_section;
	}
	function __getMainSection()
	{
		return $this->mainSection;
	}

	private function __buildSettingsButtons()
	{
		if($this->__getMainSection() == $this->SETTINGS)
		{
			$this->tabs_gui->addSubTabTarget('pays_general',
											 $this->ctrl->getLinkTargetByClass('ilobjpaymentsettingsgui', 'generalSettings'),
											 '',
											 '',
											 '',
											 $this->__getSection() == $this->SECTION_GENERAL ? true : false);
			$this->tabs_gui->addSubTabTarget('pays_bmf',
											 $this->ctrl->getLinkTargetByClass('ilobjpaymentsettingsgui', 'bmfSettings'),
											 '',
											 '',
											 '',
											 $this->__getSection() == $this->SECTION_BMF ? true : false);
			$this->tabs_gui->addSubTabTarget('pays_paypal',
											 $this->ctrl->getLinkTargetByClass('ilobjpaymentsettingsgui', 'paypalSettings'),
											 '',
											 '',
											 '',
											 $this->__getSection() == $this->SECTION_PAYPAL ? true : false);

			$this->tabs_gui->addSubTabTarget('pays_epay',
											 $this->ctrl->getLinkTargetByClass('ilobjpaymentsettingsgui', 'epaySettings'),
											 '',
											 '',
											 '',
											 $this->__getSection() == $this->SECTION_EPAY ? true : false);
			$this->tabs_gui->addSubTabTarget('pays_erp',
			 								 $this->ctrl->getLinkTargetByClass('ilobjpaymentsettingsgui', 'erpSettings'),
			 								 '',
			 								 '',
			 								 '',
			 								 $this->__getSection() == $this->SECTION_ERP ? true : false);
		}
	}
	
	function __showStatisticTable($a_result_set)
	{
		$tbl = $this->__initTableGUI();
		$tpl = $tbl->getTemplateObject();

		// SET FORMACTION
		$tpl->setCurrentBlock('tbl_form_header');

		$tpl->setVariable('FORMACTION',$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();


		$tbl->setTitle($this->lng->txt('bookings'),'icon_pays.gif',$this->lng->txt('bookings'));
		$tbl->setHeaderNames(array($this->lng->txt('paya_transaction'),
								   $this->lng->txt('title'),
								   $this->lng->txt('paya_vendor'),
								   $this->lng->txt('paya_customer'),
								   $this->lng->txt('paya_order_date'),
								   $this->lng->txt('duration'),
								   $this->lng->txt('price_a'),
								   $this->lng->txt('paya_coupons_coupons'),
								   $this->lng->txt('paya_payed_access'),
								   ''));

		$tbl->setHeaderVars(array('transaction',
								  'title',
								  'vendor',
								  'customer',
								  'order_date',
								  'duration',
								  'price',
								  'discount',
								  'payed_access',
								  'options'),
							$this->ctrl->getParameterArray($this,'statistic',false));
					

		$offset = $_GET['offset'];
		$order = $_GET['sort_by'];
		$direction = $_GET['sort_order'] ? $_GET['sort_order'] : 'desc';

		$tbl->setOrderColumn($order,'order_date');
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET['limit']);
		$tbl->setMaxCount(count($a_result_set));
		$tbl->setFooter('tblfooter',$this->lng->txt('previous'),$this->lng->txt('next'));
		$tbl->setData($a_result_set);

		$tpl->setVariable('COLUMN_COUNTS',10);
		$tpl->setCurrentBlock('plain_buttons');
		$tpl->setVariable('PBTN_NAME','exportVendors');
		$tpl->setVariable('PBTN_VALUE',$this->lng->txt('excel_export'));
		$tpl->parseCurrentBlock();
		$tbl->render();

		$this->tpl->setVariable('TABLE',$tbl->tpl->get());

		return true;
	}

	function __initBookingObject()
	{
		include_once './Services/Payment/classes/class.ilPaymentBookings.php';

		$this->booking_obj = new ilPaymentBookings($this->user_obj->getId(),true);
	}

	function __showVendorsTable($a_result_set)
	{
		$actions = array(
			'editVendorObject'	=> $this->lng->txt('pays_edit_vendor'),
			'deleteVendorsObject'	=> $this->lng->txt('pays_delete_vendor')
		);

		$tbl = $this->__initTableGUI();
		$tpl = $tbl->getTemplateObject();

		// SET FORMACTION
		$tpl->setCurrentBlock('tbl_form_header');

		$tpl->setVariable('FORMACTION',$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock('tbl_action_row');

		$tpl->setCurrentBlock('input_text');
		$tpl->setVariable('PB_TXT_NAME','vendor_login');
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock('plain_button');
		$tpl->setVariable('PBTN_NAME','addVendor');
		$tpl->setVariable('PBTN_VALUE',$this->lng->txt('pays_add_vendor'));
		$tpl->parseCurrentBlock();
		$tpl->setCurrentBlock('plain_buttons');
		$tpl->parseCurrentBlock();

		$tpl->setVariable('COLUMN_COUNTS',4);

		$tpl->setVariable('IMG_ARROW', ilUtil::getImagePath('arrow_downright.gif'));

		$tpl->setCurrentBlock('tbl_action_select');
		$tpl->setVariable('SELECT_ACTION',ilUtil::formSelect(1,'action',$actions,false,true));
		$tpl->setVariable('BTN_NAME','gateway');
		$tpl->setVariable('BTN_VALUE',$this->lng->txt('execute'));
		$tpl->parseCurrentBlock();
		$tpl->setCurrentBlock('tbl_action_row');
		$tpl->setVariable('TPLPATH',$this->tpl->tplPath);
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt('vendors'),'icon_usr.gif',$this->lng->txt('vendors'));
		$tbl->setHeaderNames(array('',
								   $this->lng->txt('pays_vendor'),
								   $this->lng->txt('pays_cost_center'),
								   $this->lng->txt('pays_number_bookings')));
		$tbl->setHeaderVars(array('',
								  'login',
								  'cost_center',
								  'bookings'),
							array('ref_id' => $this->object->getRefId(),
								  'cmd' => 'vendors',
								  'update_members' => 1,
								  'baseClass' => 'ilAdministrationGUI',
								  'cmdClass' => 'ilobjpaymentsettingsgui',
								  'cmdNode' => $_GET['cmdNode']));
#		$tbl->setColumnWidth(array('4%','48%','25%','24%'));

		$tpl->setVariable('COLUMN_COUNTS',9);
		$tpl->setCurrentBlock('plain_buttons');
		$tpl->setVariable('PBTN_NAME','exportVendors');
		$tpl->setVariable('PBTN_VALUE',$this->lng->txt('excel_export'));
		$tpl->parseCurrentBlock();

		$this->__setTableGUIBasicData($tbl,$a_result_set);
		$tbl->render();

		$this->tpl->setVariable('TABLE',$tbl->tpl->get());

		return true;
	}


	function __showSearchUserTable($a_result_set,$a_cmd = 'search')
	{
		$tbl = $this->__initTableGUI();
		$tpl = $tbl->getTemplateObject();

		// SET FORMACTION
		$tpl->setCurrentBlock('tbl_form_header');
		$tpl->setVariable('FORMACTION',$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock('tbl_action_btn');
		$tpl->setVariable('BTN_NAME','addUser');
		$tpl->setVariable('BTN_VALUE',$this->lng->txt('add'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock('tbl_action_btn');
		$tpl->setVariable('BTN_NAME','vendors');
		$tpl->setVariable('BTN_VALUE',$this->lng->txt('cancel'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock('tbl_action_row');
		$tpl->setVariable('COLUMN_COUNTS',5);
		$tpl->setVariable('IMG_ARROW',ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt('pays_header_select_vendor'),'icon_usr.gif',$this->lng->txt('pays_header_select_vendor'));
		$tbl->setHeaderNames(array('',
								   $this->lng->txt('login'),
								   $this->lng->txt('firstname'),
								   $this->lng->txt('lastname')));
		$tbl->setHeaderVars(array('',
								  'login',
								  'firstname',
								  'lastname'),
							array('ref_id' => $this->object->getRefId(),
								  'cmd' => $a_cmd,
								  'cmdClass' => 'ilobjpaymentsettingsgui',
								  'cmdNode' => $_GET['cmdNode']));

		$tbl->setColumnWidth(array('3%','32%','32%','32%'));

		$this->__setTableGUIBasicData($tbl,$a_result_set);
		$tbl->render();
		
		$this->tpl->setVariable('TABLE',$tbl->tpl->get());

		return true;
	}

	function __initTableGUI()
	{
		include_once './Services/Table/classes/class.ilTableGUI.php';
		return new ilTableGUI(0,false);
	}

	function __setTableGUIBasicData($tbl, $result_set, $from = '')
	{
		$offset = $_GET['offset'];
		$order = $_GET['sort_by'];
		$direction = $_GET['sort_order'];

		$tbl->setOrderColumn($order);
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET['limit']);
		$tbl->setMaxCount(count($result_set));
		$tbl->setFooter('tblfooter',$this->lng->txt('previous'),$this->lng->txt('next'));
		$tbl->setData($result_set);
	}

	function __search($a_search_string)
	{
		include_once('./classes/class.ilSearch.php');

		$this->lng->loadLanguageModule('content');

		$search = new ilSearch($_SESSION['AccountId']);
		$search->setPerformUpdate(false);
		$search->setSearchString(ilUtil::stripSlashes($a_search_string));
		$search->setCombination('and');
		$search->setSearchFor(array(0 => 'usr'));
		$search->setSearchType('new');

		if($search->validate($message))
		{
			$search->performSearch();
		}
		else
		{
			ilUtil::sendInfo($message,true);
			$this->ctrl->redirect($this,'searchUser');
		}
		return $search->getResultByType('usr');
	}		
	
	function __searchSP($a_search_string)
	{
		include_once('./classes/class.ilSearch.php');

		$this->lng->loadLanguageModule('content');

		$search = new ilSearch($this->user_obj->getId());
		$search->setPerformUpdate(false);
		$search->setSearchString(ilUtil::stripSlashes($a_search_string));
		$search->setCombination('and');
		$search->setSearchFor(array(0 => 'usr'));
		$search->setSearchType('new');

		if($search->validate($message))
		{
			$search->performSearchSPObject();
		}
		else
		{
			ilUtil::sendInfo($message,true);
			$this->ctrl->redirect($this,'searchUserSP');
		}
		return $search->getResultByType('usr');
	}
	function __showSearchUserSPTable($a_result_set)
	{
		$tbl = $this->initTableGUI();
		$tpl = $tbl->getTemplateObject();


		// SET FORMACTION
		$tpl->setCurrentBlock('tbl_form_header');
		$this->ctrl->setParameter($this, 'sell_id', $_GET['sell_id']);
		$tpl->setVariable('FORMACTION',$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock('tbl_action_btn');
		$tpl->setVariable('BTN_NAME','addCustomer');
		$tpl->setVariable('BTN_VALUE',$this->lng->txt('add'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock('tbl_action_btn');
		$tpl->setVariable('BTN_NAME','statistic');
		$tpl->setVariable('BTN_VALUE',$this->lng->txt('cancel'));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock('tbl_action_row');
		$tpl->setVariable('COLUMN_COUNTS',5);
		$tpl->setVariable('IMG_ARROW',ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt('users'),'icon_usr.gif',$this->lng->txt('crs_header_edit_members'));
		$tbl->setHeaderNames(array('',
								   $this->lng->txt('login'),
								   $this->lng->txt('firstname'),
								   $this->lng->txt('lastname')));
		$this->ctrl->setParameter($this, 'cmd', 'addCustomer');
		$header_params = $this->ctrl->getParameterArray($this,'');
		$tbl->setHeaderVars(array('',
								  'login',
								  'firstname',
								  'lastname'), $header_params);
								  /*
							array('cmd' => 'performSearch',
								  'cmdClass' => 'ilpaymentstatisticgui',
								  'cmdNode' => $_GET['cmdNode']));
								  */

		$tbl->setColumnWidth(array('3%','32%','32%','32%'));

		$this->setTableGUIBasicData($tbl,$a_result_set);
		$tbl->render();
		
		$this->tpl->setVariable('TABLE',$tbl->tpl->get());

		return true;
	}

	function initTableGUI()
	{
		include_once './Services/Table/classes/class.ilTableGUI.php';

		return new ilTableGUI(0,false);
	}
	function setTableGUIBasicData($tbl, $result_set, $a_default_order_column = '')
	{
		
		$offset = $_GET['offset'];
		$order = $_GET['sort_by'];
		$direction = $_GET['sort_order'];

		$tbl->setOrderColumn($order,$a_default_order_column);
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET['limit']);
		$tbl->setMaxCount(count($result_set));
		$tbl->setFooter('tblfooter',$this->lng->txt('previous'),$this->lng->txt('next'));
		$tbl->setData($result_set);
	}

	public function vatsObject()
	{
		global $ilAccess;

		if(!$ilAccess->checkAccess('read', '', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'), $this->ilias->error_obj->MESSAGE);
		}
		
		include_once 'Services/Table/classes/class.ilTable2GUI.php';
		$tbl = new ilTable2GUI($this, 'vats');
		$tbl->setId('pay_vats_tbl');
		$tbl->setFormAction($this->ctrl->getFormAction($this), 'createVat');
		$tbl->setTitle($this->lng->txt('payment_tax_rates'));
		$tbl->setRowTemplate('tpl.shop_vats_list_row.html', 'Services/Payment');				

	 	$tbl->setDefaultOrderField('title');	
		
		$result = array();		
		
		$tbl->addColumn('', 'check', '1%');
	 	$tbl->addColumn($this->lng->txt('vat_title'), 'vat_title', '33%');
	 	$tbl->addColumn($this->lng->txt('vat_rate'), 'vat_rate', '33%');
		$tbl->addColumn('', 'commands', '33%');		
		
		$oShopVatsList = new ilShopVatsList();
		$oShopVatsList->read();		
		
		$result = array();
		
		if($oShopVatsList->hasItems())
		{
			$tbl->enable('select_all');				
			$tbl->setSelectAllCheckbox('vat_id');
			
			$counter = 0;
			foreach($oShopVatsList as $oVAT)
			{
				$result[$counter]['check'] = ilUtil::formCheckbox(0, 'vat_id[]', $oVAT->getId());
				$result[$counter]['vat_title'] = $oVAT->getTitle();
				$result[$counter]['vat_rate'] = ilShopUtils::_formatVAT((float)$oVAT->getRate());								
				$this->ctrl->setParameter($this, 'vat_id',  $oVAT->getId());
				$result[$counter]['edit_text'] = $this->lng->txt('edit');
				$result[$counter]['edit_url'] = $this->ctrl->getLinkTarget($this, 'editVat');
				$result[$counter]['delete_text'] = $this->lng->txt('delete');
				$result[$counter]['delete_url'] = $this->ctrl->getLinkTarget($this, 'confirmDeleteVat');
				$this->ctrl->clearParameters($this);
				++$counter;
			}
			
			$tbl->addMultiCommand('confirmDeleteVat', $this->lng->txt('delete'));	
		}
		else
		{
			$tbl->disable('header');
			$tbl->disable('footer');

			$tbl->setNoEntriesText($this->lng->txt('paya_no_vats_assigned'));
		}
		
		$tbl->setData($result);
		
		$tbl->addCommandButton('createVat', $this->lng->txt('paya_insert_vats'));
		
		$this->tpl->setContent($tbl->getHTML());
		
		return true;
	}	

	public function confirmDeleteVatObject()
	{  
		if((int)$_GET['vat_id'] && !isset($_POST['vat_id']))
		{
			$_POST['vat_id'][] = $_GET['vat_id']; 	
		}		

		$c_gui = new ilConfirmationGUI();
		$c_gui->setFormAction($this->ctrl->getFormAction($this, 'performDeleteVat'));
		$c_gui->setHeaderText($this->lng->txt('paya_sure_delete_vats'));
		$c_gui->setCancel($this->lng->txt('cancel'), 'vats');
		$c_gui->setConfirm($this->lng->txt('confirm'), 'performDeleteVat');
		
		$counter = 0;
		foreach((array)$_POST['vat_id'] as $vat_id)
		{
			try
			{
				$oVAT = new ilShopVats((int)$vat_id);
				$c_gui->addItem('vat_id[]', $oVAT->getId(), $oVAT->getTitle());
				++$counter;
			}
			catch(ilShopException $e)
			{
				ilUtil::sendInfo($e->getMessage());
				return $this->vatsObject();				
			}
		}	

		if($counter)
		{
			return $this->tpl->setContent($c_gui->getHTML());	
		}
		else
		{
			return $this->vatsObject();
		}
	}
	
	public function performDeleteVatObject()
	{
		if(!is_array($_POST['vat_id']))
		{
			return $this->vatsObject();
		}		
		
		foreach($_POST['vat_id'] as $vat_id)
		{
			try
			{
				$oVAT = new ilShopVats((int)$vat_id);
				$oVAT->delete();
				
			}
			catch(ilShopException $e)
			{
				ilUtil::sendInfo($e->getMessage());
				return $this->vatsObject();				
			}
		}
		
		ilUtil::sendSuccess($this->lng->txt('payment_vat_deleted_successfully'));		
		return $this->vatsObject();
	}
	public function createVatObject()
	{
		$this->initVatForm('create');
		$this->tpl->setContent($this->form->getHtml());
	}
	
	public function editVatObject()
	{
		$this->initVatForm('edit');
		$this->fillVATDataIntoVATForm();
		$this->tpl->setContent($this->form->getHtml());
	}
	
	private function initVatForm($a_type = 'create')
	{
		$this->form = new ilPropertyFormGUI();		
		if($a_type == 'edit')
		{
			$this->ctrl->setParameter($this, 'vat_id', $_GET['vat_id']);
			$this->form->setFormAction($this->ctrl->getFormAction($this, 'updateVat'));
			$this->form->setTitle($this->lng->txt('payment_edit_vat'));	
		}
		else
		{
			$this->form->setFormAction($this->ctrl->getFormAction($this, 'saveVat'));
			$this->form->setTitle($this->lng->txt('payment_add_vat'));
		}
				
		$oTitle = new ilTextInputGUI($this->lng->txt('title'), 'vat_title');
		$oTitle->setMaxLength(255);
		$oTitle->setSize(40);
		$oTitle->setRequired(true);
		$oTitle->setInfo($this->lng->txt('payment_vat_title_info'));
		$this->form->addItem($oTitle);
		
		$oRate = new ilTextInputGUI($this->lng->txt('vat_rate'), 'vat_rate');
		$oRate->setMaxLength(5);
		$oRate->setSize(5);
		$oRate->setRequired(true);
		$oRate->setInfo($this->lng->txt('payment_vat_rate_info'));
		$this->form->addItem($oRate);
		
		if($a_type == 'edit')
		{			
			$this->form->addCommandButton('updateVat', $this->lng->txt('save'));
		}
		else
		{
			$this->form->addCommandButton('saveVat', $this->lng->txt('save'));	
		}
		
		$this->form->addCommandButton('vats', $this->lng->txt('cancel'));
	}

	private function fillVATDataIntoVATForm()
	{	
		$oVAT = new ilShopVats((int)$_GET['vat_id']);						
		$this->form->setValuesByArray(array(
			'vat_title' => $oVAT->getTitle(),
			'vat_rate' => $oVAT->getRate()
		));
	}		
	
	public function updateVatObject()
	{
		$this->initVatForm('edit');
		if(!$this->form->checkInput())
		{
			$this->form->setValuesByPost();
			return $this->tpl->setContent($this->form->getHtml());
		}
		
		if(!ilShopUtils::_checkVATRate($this->form->getInput('vat_rate')))
		{
			$this->form->getItemByPostVar('vat_rate')->setAlert($this->lng->txt('payment_vat_input_invalid'));
			$this->form->setValuesByPost();
			return $this->tpl->setContent($this->form->getHtml());
		}
		
		try
		{
			$oVAT = new ilShopVats((int)$_GET['vat_id']);
			$oVAT->setTitle($this->form->getInput('vat_title'));
			$oVAT->setRate((float)str_replace(',', '.', $this->form->getInput('vat_rate')));		
			$oVAT->update();
		}
		catch(ilShopException $e)
		{
			ilUtil::sendInfo($e->getMessage());
			$this->form->setValuesByPost();
			return $this->tpl->setContent($this->form->getHtml());			
		}
		
		ilUtil::sendInfo($this->lng->txt('saved_successfully'));
		return $this->vatsObject();
	}
	
	public function saveVatObject()
	{		
		$this->initVatForm('create');
		if(!$this->form->checkInput())
		{
			$this->form->setValuesByPost();
			return $this->tpl->setContent($this->form->getHtml());
		}
		
		if(!ilShopUtils::_checkVATRate($this->form->getInput('vat_rate')))
		{
			$this->form->getItemByPostVar('vat_rate')->setAlert($this->lng->txt('payment_vat_input_invalid'));
			$this->form->setValuesByPost();
			return $this->tpl->setContent($this->form->getHtml());
		}
		
		try
		{
			$oVAT = new ilShopVats();
			$oVAT->setTitle($this->form->getInput('vat_title'));
			$oVAT->setRate((float)str_replace(',', '.', $this->form->getInput('vat_rate')));		
			$oVAT->save();
		}
		catch(ilShopException $e)
		{
			ilUtil::sendInfo($e->getMessage());
			$this->form->setValuesByPost();
			return $this->tpl->setContent($this->form->getHtml());
			
		}
		
		ilUtil::sendInfo($this->lng->txt('saved'));
		return $this->vatsObject();
				
	}	

	function deleteAddressesForPaymethodsObject()
	{
		// delete addresses here
		include_once './Services/Payment/classes/class.ilPayMethods.php';
		
		$this->__initBookingObject();	
		
		foreach($_POST['pm_id'] as $pay_method)
		{
			ilPayMethods::_disableSaveUserAddress($pay_method);
			$del_bookings = $this->booking_obj->deleteAddressesByPaymethod((int)$pay_method);
		}	
		ilUtil::sendSuccess($this->lng->txt('pays_updated_pay_method'));
		return $this->payMethodsObject();
	}	
	
	
	// show currencies
	
	public function currenciesObject()
	{
		global $ilToolbar;

		$ilToolbar->addButton($this->lng->txt('paya_add_currency'), $this->ctrl->getLinkTarget($this, 'addCurrency'));
				
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');
		$currency_res = ilPaymentCurrency::_getAvailableCurrencies();
		
		//default currency
		$oForm = new ilPropertyFormGUI();
		$oForm->setFormAction($this->ctrl->getFormAction($this));
		$oForm->setTitle($this->lng->txt('paya_set_default_currency'));
		$oForm->setId('frm_default_currency');
		
		$oCurrency = new ilSelectInputGUI();

		$currency_options = array();
		foreach($currency_res as $cur)
		{
			$currency_options[$cur['currency_id']] = $cur['unit'];
			
			if($cur['is_default'])
			{
				$old_default_currency = $cur['currency_id'];
			}
		}
		$ohidden = new ilHiddenInputGUI('old_default_currency');
		
		$ohidden->setValue($old_default_currency);
		$ohidden->setPostVar('old_default_currency');
		$oForm->addItem($ohidden);		
		
		$oCurrency->setTitle($this->lng->txt('currencies'));
		$oCurrency->setOptions($currency_options);
		$default_cur = ilPaymentCurrency::_getDefaultCurrency();
		
		$oCurrency->setValue($default_cur['currency_id']);
		$oCurrency->setPostVar('set_default_currency');		
		$oForm->addItem($oCurrency);

		$oForm->addCommandButton('updateDefaultCurrency', $this->lng->txt('save'));
		$this->tpl->setVariable('FORM', $oForm->getHTML());
		
		// currency table
		 $counter = 0;
		 foreach($currency_res as $cur)
		 {

			$f_result[$counter]['unit'] = $cur['unit'];
			$f_result[$counter]['iso_code'] = $cur['iso_code'];
			$f_result[$counter]['symbol'] = $cur['symbol'];
			$f_result[$counter]['conversion_rate'] = $cur['conversion_rate'];
			
			$this->ctrl->setParameter($this,"currency_id",$cur['currency_id']);
			$link_delete = "<div class=\"il_ContainerItemCommands\"><a class=\"il_ContainerItemCommand\" href=\"".$this->ctrl->getLinkTarget($this,"deleteCurrency")."\">".$this->lng->txt("delete")."</a></div>";
			$f_result[$counter]['linkdelete'] = $link_delete;
			
			$this->ctrl->setParameter($this,"currency_id",$cur['currency_id']);
			$link_change = "<div class=\"il_ContainerItemCommands\"><a class=\"il_ContainerItemCommand\" href=\"".$this->ctrl->getLinkTarget($this,"editCurrency")."\">".$this->lng->txt("edit")."</a></div>";
			$f_result[$counter]['linkchange'] = $link_change;
			$counter++;
		 }

		$tbl = $this->initTableGUI();
		$tpl = $tbl->getTemplateObject();

		// SET FORMACTION
		$tpl->setCurrentBlock('tbl_form_header');
		
		$this->ctrl->setParameter($this, 'cmd', 'currencies');

		$tpl->setVariable('FORMACTION', $this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();
		
		$tbl->setTitle($this->lng->txt('currencies'));
		$tbl->setHeaderNames(array($this->lng->txt('currency_unit'),
								   $this->lng->txt('iso_code'),
								   $this->lng->txt('currency_symbol'),
								   $this->lng->txt('conversion_rate'),
									'', ''));
		$header_params = $this->ctrl->getParameterArray($this,'');
		

		$tbl->setHeaderVars(array('unit','iso_code','symbol','conversion_rate', 'linkdelete','linkchange'),$header_params);
		$tbl->setColumnWidth(array("20%","15%","15%",'20%','15%','15%'));


		$offset = $_GET['offset'];
		//$order = $_GET['sort_by'];
		$direction = $_GET['sort_order'] ? $_GET['sort_order'] : 'desc';

		//$tbl->setOrderColumn($order,'order_date');
		//$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET['limit']);
		$tbl->setMaxCount(count($f_result));
		$tbl->setFooter('tblfooter',$this->lng->txt('previous'),$this->lng->txt('next'));
		$tbl->setData($f_result);

		$tbl->render();

		$this->tpl->setVariable('TABLE',$tbl->tpl->get());

		return true;
	}
	public function updateDefaultCurrencyObject()
	{
		if($_POST['old_default_currency'] != $_POST['set_default_currency'])
		{
			ilPaymentCurrency::_updateIsDefault($_POST['old_default_currency'], 0);
			ilPaymentCurrency::_updateIsDefault($_POST['set_default_currency'], 1);
		}
		$this->currenciesObject();
		

	}
	
	public function addCurrencyObject()
	{
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');	
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->lng->txt('paya_add_currency'));
		
		$o_Unit = new ilTextInputGUI($this->lng->txt('paya_currency_unit'),'currency_unit');
		$o_Unit->setValue($_POST['currency_unit']);
		$o_Unit->setPostVar('currency_unit');
		$o_Unit->setRequired(true);

		$o_Isocode = new ilTextInputGUI($this->lng->txt('iso_code'),'iso_code');
		$o_Isocode->setValue($_POST['iso_code']);
		$o_Isocode->setPostVar('iso_code');
		$o_Isocode->setRequired(true);
		
		$o_Symbol = new ilTextInputGUI($this->lng->txt('symbol'), 'symbol');
		$o_Symbol->setValue($_POST['symbol']);
		$o_Symbol->setPostVar('symbol');
		$o_Symbol->setRequired(true);
		
		$o_Conversionrate = new IlTextInputGUI($this->lng->txt('conversion_rate'), 'conversion_rate');
		$o_Conversionrate->setValue($_POST['conversion_rate']);
		$o_Conversionrate->setPostVar('conversion_rate');
		$o_Conversionrate->setRequired(true);
		
		$form->addItem($o_Unit);
		$form->addItem($o_Isocode);
		$form->addItem($o_Symbol);
		$form->addItem($o_Conversionrate);

		$form->addCommandButton('saveCurrency', $this->lng->txt('save'));	
		$form->addCommandButton('currencies', $this->lng->txt('cancel'));	
		
		$this->tpl->setVariable('FORM', $form->getHTML());

	}
	public function saveCurrencyObject()
	{
		
		$obj_currency = new ilPaymentCurrency();
		$obj_currency->setUnit($_POST['currency_unit']);
		$obj_currency->setIsoCode($_POST['iso_code']);
		$obj_currency->setSymbol($_POST['symbol']);
		$obj_currency->setConversionRate($_POST['conversion_rate']);
		$obj_currency->addCurrency();
		$this->currenciesObject();
		
	}
	
	public function editCurrencyObject()
	{
		$currency_id = $_GET['currency_id'];
		$obj_currency = ilPaymentCurrency::_getCurrency($currency_id);
	
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');	
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->lng->txt('paya_add_currency'));
		
		$o_Unit = new ilTextInputGUI($this->lng->txt('paya_currency_unit'),'currency_unit');
		$o_Unit->setValue($obj_currency[$currency_id]['unit']);
		$o_Unit->setPostVar('currency_unit');
		$o_Unit->setRequired(true);
		
		$o_Isocode = new ilTextInputGUI($this->lng->txt('iso_code'),'iso_code');
		$o_Isocode->setValue($obj_currency[$currency_id]['iso_code']);
		$o_Isocode->setPostVar('iso_code');
		$o_Isocode->setRequired(true);
		
		$o_Symbol = new ilTextInputGUI($this->lng->txt('symbol'), 'symbol');
		$o_Symbol->setValue($obj_currency[$currency_id]['symbol']);
		$o_Symbol->setPostVar('symbol');
		$o_Symbol->setRequired(true);
		
		$o_Conversionrate = new IlTextInputGUI($this->lng->txt('conversion_rate'), 'conversion_rate');
		$o_Conversionrate->setValue($obj_currency[$currency_id]['conversion_rate']);
		$o_Conversionrate->setPostVar('conversion_rate');
		$o_Conversionrate->setRequired(true);
		

		
		$o_hidden = new ilHiddenInputGUI('currency_id');
		$o_hidden->setValue($obj_currency[$currency_id]['currency_id']);
		$o_hidden->setPostVar('currency_id');
		$form->addItem($o_hidden);
		
		
		$form->addItem($o_Unit);
		$form->addItem($o_Isocode);
		$form->addItem($o_Symbol);
		$form->addItem($o_Conversionrate);
		
	//$this->ctrl->setParameter($this,'currency_id',(int) $_GET['currency_id']);
		$form->addCommandButton('updateCurrency', $this->lng->txt('save'));	
		$form->addCommandButton('currencies', $this->lng->txt('cancel'));	
		
		$this->tpl->setVariable('FORM', $form->getHTML());
			
		
	}
	public function deleteCurrencyObject()
	{
		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.main_view.html','Services/Payment');	
		$_SESSION['currency_id'] = $_GET['currency_id'];
		$oConfirmationGUI = new ilConfirmationGUI();
		$this->ctrl->setParameter($this,'currency_id',(int) $_GET['currency_id']);
		// set confirm/cancel commands
		$oConfirmationGUI->setFormAction($this->ctrl->getFormAction($this,"performDeleteCurrency"));
				
		$oConfirmationGUI->setHeaderText($this->lng->txt("paya_sure_delete_selected_currency"));
		$oConfirmationGUI->setCancel($this->lng->txt("cancel"), "currencies");
		$oConfirmationGUI->setConfirm($this->lng->txt("confirm"), "performDeleteCurrency");			
	
		$oConfirmationGUI->addItem('currency_id','', ilPaymentCurrency::_getUnit($_GET['currency_id']),'' );
		
		$this->tpl->setVariable('CONFIRMATION', $oConfirmationGUI->getHtml());
		
	}
	public function performDeleteCurrencyObject()
	{
	
		if(!$_SESSION['currency_id']) return false;
		
		$obj_currency = new ilPaymentCurrency((int)$_SESSION['currency_id']);
		$obj_currency->deleteCurrency();
		
		$this->currenciesObject();
		

	}
	public function updateCurrencyObject()
	{
		if(!$_POST['currency_id']) return false;
		
		$obj_currency = new ilPaymentCurrency($_POST['currency_id']);
		$obj_currency->setUnit($_POST['currency_unit']);
		$obj_currency->setIsoCode($_POST['iso_code']);
		$obj_currency->setSymbol($_POST['symbol']);
		$obj_currency->setConversionRate($_POST['conversion_rate']);
		
		$obj_currency->updateCurrency();
			
		$this->currenciesObject();
		
	}
} // END class.ilObjPaymentSettingsGUI
?>
