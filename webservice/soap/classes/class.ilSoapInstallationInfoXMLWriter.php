<?php

include_once "./classes/class.ilXmlWriter.php";

class ilSoapInstallationInfoXMLWriter extends ilXmlWriter
{
	/**
	 * array of ilSetting Objects
	 *
	 * @var array
	 */
	private $settings;

	/**
	* constructor
	* @param	string	xml version
	* @param	string	output encoding
	* @param	string	input encoding
	* @access	public
	*/
	function ilSoapInstallationInfoXMLWriter()
	{
		parent::ilXmlWriter();
	}
	
	/**
	 * write access to property settings
	 *
	 * @param array $settings is an array of ilSetting Objects 
	 */
	public function setSettings($settings) {
		$this->settings = $settings;	
	}

	public function start()
	{
		$this->__buildHeader();
		$this->__buildInstallationInfo();
		$this->xmlStartTag("Clients");
		if (is_array($this->settings)) 
		{
			foreach ($this->settings as $setting)
				$this->__buildSetting ($setting);
		}
		$this->xmlEndTag("Clients");
		$this->__buildFooter();		
		
	}

	public function getXML()
	{
		return $this->xmlDumpMem(FALSE);
	}
	
	private function __buildHeader()
	{
		// we have to build the http path here since this request is client independent!
		if($_SERVER["HTTPS"] == "on")
		{
			$protocol = 'https://';
		}
		else
		{
			$protocol = 'http://';
		}
		$host = $_SERVER['HTTP_HOST'];

		$path = pathinfo($_SERVER['REQUEST_URI']);
		if(!$path['extension'])
		{
			$uri = $_SERVER['REQUEST_URI'];
		}
		else
		{
			$uri = dirname($_SERVER['REQUEST_URI']);
		}
		$httppath = ilUtil::removeTrailingPathSeparators($protocol.$host.$uri);		
		$this->xmlSetDtdDef("<!DOCTYPE Installation PUBLIC \"-//ILIAS//DTD Group//EN\" \"".$httppath."/xml/ilias_client_3_10.dtd\">");  
		$this->xmlSetGenCmt("Export of ILIAS clients.");
		$this->xmlHeader();
		$this->xmlStartTag("Installation",
			array (
				"version" => ILIAS_VERSION,
			));			
		
		return true;
	}

	private function __buildFooter()
	{
		$this->xmlEndTag('Installation');
	}
	
	/**
	 * create client tag
	 *
	 * @param ilSetting $setting
	 */
	private function __buildSetting($setting) {
		$auth_modes = ilAuthUtils::_getActiveAuthModes();
		$auth_mode_default =  strtoupper(ilAuthUtils::_getAuthModeName(array_shift($auth_modes)));
		$auth_mode_names = array();
		foreach ($auth_modes as $mode) {
			$auth_mode_names[] = strtoupper(ilAuthUtils::_getAuthModeName($mode));
		}
		
		
		$this->xmlStartTag("Client", 
			array(
				"inst_id" => $setting->get("inst_id"),
				"id" => $setting->clientid,
				"enabled" => $setting->access == 1 ? "TRUE" : "FALSE",
				"path" => $setting->httpPath,
				"default_lang" => $setting->language,
			    
			));
		$this->xmlElement ("Name", null, $setting->get("inst_name"));
		$this->xmlElement ("Description", null, $setting->description);
		$this->xmlElement ("Institution", null, $setting->get("inst_institution"));
		$this->xmlStartTag("Responsible");
		$this->xmlElement ("Firstname", null, $setting->get("admin_firstname"));
		$this->xmlElement ("Lastname", null, $setting->get("admin_lastname"));
		$this->xmlElement ("Title", null, $setting->get("admin_title"));
		$this->xmlElement ("Institution", null, $setting->get("admin_institution"));
		$this->xmlElement ("Position", null, $setting->get("admin_position"));
		$this->xmlElement ("Email", null, $setting->get("admin_email"));
		$this->xmlElement ("Street ", null, $setting->get("admin_street"));
		$this->xmlElement ("ZipCode ", null, $setting->get("admin_zipcode"));
		$this->xmlElement ("City", null, $setting->get("admin_city"));
		$this->xmlElement ("Country", null, $setting->get("admin_country"));
		$this->xmlElement ("Phone", null, $setting->get("admin_phone"));		
		$this->xmlEndTag("Responsible");
		$this->xmlStartTag("Settings");
		$this->xmlElement("Setting", array("key" => "error_recipient"), $setting->get("error_recipient"));		
		$this->xmlElement("Setting", array("key" => "feedback_recipient"), $setting->get("feedback_recipient"));
		$this->xmlElement("Setting", array("key" => "session_expiration"), $setting->session);
		$this->xmlElement("Setting", array("key" => "soap_enabled"), $setting->get("soap_user_administration"));
		$this->xmlElement("Setting", array("key" => "authentication_methods"), join(",", $auth_mode_names));
		$this->xmlElement("Setting", array("key" => "authentication_default_method"), $auth_mode_default);
		$this->xmlEndTag("Settings");
		
		
		// create advanced meta data record xml
		include_once 'Services/AdvancedMetaData/classes/class.ilAdvancedMDRecord.php';
		include_once 'Services/AdvancedMetaData/classes/class.ilAdvancedMDRecordXMLWriter.php';
		
		$record_ids = array();
		$record_types = ilAdvancedMDRecord::_getAssignableObjectTypes();
		foreach($record_types as $type) {
			$records = ilAdvancedMDRecord::_getActivatedRecordsByObjectType($type);
			foreach ($records as $record){
				$record_ids [] = $record->getRecordId();
			}			
		}
		$record_ids = array_unique($record_ids);
		$this->xmlStartTag('AdvancedMetaDataRecords');
		if (count($record_ids) > 0)
		{
		 	foreach($record_ids as $record_id)
		 	{
	 			$record_obj = ilAdvancedMDRecord::_getInstanceByrecordId($record_id);
	 			$record_obj->toXML($this);
	 		}
		}
	 	$this->xmlEndTag('AdvancedMetaDataRecords');		
		
		// create user defined fields record xml
	    include_once ("./Services/User/classes/class.ilUserDefinedFields.php");
		$udf_data = & ilUserDefinedFields::_getInstance();
		$udf_data->addToXML($this);
				

		$this->xmlEndTag("Client");
	}
	
	private function __buildInstallationInfo() 
	{
		$this->xmlStartTag("Settings");
		$this->xmlElement("Setting", array("key" => "default_client"), $GLOBALS['ilIliasIniFile']->readVariable("clients","default"));
		$this->xmlElement("Setting", array("key" => "post_max_size"), ilSoapAdministration::return_bytes(ini_get("post_max_size")));
		$this->xmlElement("Setting", array("key" => "upload_max_filesize"), ilSoapAdministration::return_bytes(ini_get("upload_max_filesize")));
		$this->xmlEndTag("Settings");			
	}
}

?>