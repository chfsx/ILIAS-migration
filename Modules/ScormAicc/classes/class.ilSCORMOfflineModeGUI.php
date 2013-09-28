<?php/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE *//*** GUI class ilSCORMOfflineModeGUI** GUI class for scorm offline player connection** @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>* @version $Id: class.ilSCORMOfflineModeGUI.php  $***/class ilSCORMOfflineModeGUI{	var $lmId;	var $clientIdSop;	function ilSCORMOfflineModeGUI($type) {		global $ilias, $tpl, $lng, $ilCtrl;		$this->ilias =& $ilias;		$this->tpl =& $tpl;		$this->lng =& $lng;		$this->ctrl =& $ilCtrl;				$this->ctrl->saveParameter($this, "ref_id");			}	function executeCommand()	{		global $tpl, $ilCtrl;		$this->lmId = ilObject::_lookupObjectId($_GET["ref_id"]);		$iliasDomain = substr(ILIAS_HTTP_PATH,7);		if (substr($iliasDomain,0,1) == "\/") $iliasDomain = substr($iliasDomain,1);		if (substr($iliasDomain,0,4) == "www.") $iliasDomain = substr($iliasDomain,4);		$this->clientIdSop = $iliasDomain.';'.CLIENT_ID;		include_once "./Modules/ScormAicc/classes/class.ilSCORMOfflineMode.php";		$offlineMode = new ilSCORMOfflineMode();		$cmd = $ilCtrl->getCmd();		$this->setOfflineModeTabs($cmd);		switch($cmd){			case 'offlineMode_il2sopContent':				ilUtil::deliverFile(ilUtil::getDataDir()."/lm_data/lm_".$this->lmId.".zip","lm_".$this->lmId.".zip");				break;			case 'offlineMode_il2sop':				$offlineMode->il2sop();				break;			case 'offlineMode_il2sopStop':				$offlineMode->setOfflineMode("online");				$this->view($offlineMode->getOfflineMode(),"msg_import_failure");				break;			case 'offlineMode_il2sopOk':				$offlineMode->setOfflineMode("offline");				$this->view($offlineMode->getOfflineMode(),"msg_import_ok");				break;			default:				if ($offlineMode->getOfflineMode() == "il2sop") $offlineMode->setOfflineMode("online");				$this->view($offlineMode->getOfflineMode());				break;		}	}	function view($offlineMode,$message="") {		global $tpl;		// Fill meta header tags		$tpl->setCurrentBlock('mh_meta_item');		$tpl->setVariable('MH_META_NAME','require-sop-version');		$tpl->setVariable('MH_META_CONTENT',"0.1");//		$tpl->addJavascript('./Modules/ScormAicc/scripts/sopConnector.js');		$tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.scorm_offline_mode.html", "Modules/ScormAicc");		$tpl->setVariable("CHECKING_SOPCONNECTOR","checking_sopConnector");		$tpl->setVariable("CLIENT_ID",CLIENT_ID);		$tpl->setVariable("CLIENT_ID_SOP",$this->clientIdSop);		$tpl->setVariable("REF_ID",$_GET['ref_id']);		$tpl->setVariable("LM_ID",$this->lmId);		$tpl->setVariable("OFFLINE_MODE",$offlineMode);		$tpl->setVariable("MESSAGE_RESULT",$message);		$tpl->parseCurrentBlock();		$tpl->show();	}	function importStop() {	}		function setOfflineModeTabs($a_active)	{		global $ilTabs, $ilLocator,$tpl;//		$thisurl = "ilias.php?baseClass=ilSAHSPresentationGUI&amp;ref_id=".$_GET["ref_id"]."&amp;cmd=".$a_active;//		$thisurl = $this->ctrl->getLinkTargetByClass(array('ilsahspresentationgui', 'ilscormofflinemodegui'),$a_active);		$thisurl =$this->ctrl->getLinkTarget($this, $a_active);		$ilTabs->addTab("info_short", $this->lng->txt("info_short"), $this->ctrl->getLinkTargetByClass("ilinfoscreengui", "showSummary"));		$ilTabs->addTab($a_active, $this->lng->txt($a_active), $thisurl);					$ilTabs->activateTab($a_active);		$tpl->getStandardTemplate();		$tpl->setTitle(ilObject::_lookupTitle($this->lmId));		$tpl->setTitleIcon(ilUtil::getImagePath("icon_slm_b.png"));		$ilLocator->addRepositoryItems();		$ilLocator->addItem(ilObject::_lookupTitle($this->lmId),$thisurl);		$tpl->setLocator();	}    }?>