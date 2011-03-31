<?php
 /*
   +----------------------------------------------------------------------------+
   | ILIAS open source                                                          |
   +----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne           |
   |                                                                            |
   | This program is free software; you can redistribute it and/or              |
   | modify it under the terms of the GNU General Public License                |
   | as published by the Free Software Foundation; either version 2             |
   | of the License, or (at your option) any later version.                     |
   |                                                                            |
   | This program is distributed in the hope that it will be useful,            |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of             |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              |
   | GNU General Public License for more details.                               |
   |                                                                            |
   | You should have received a copy of the GNU General Public License          |
   | along with this program; if not, write to the Free Software                |
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. |
   +----------------------------------------------------------------------------+
*/

include_once "./Services/Certificate/classes/class.ilCertificateAdapter.php";

/**
* SCORM certificate adapter
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @ingroup ModulesScormAicc
*/
class ilSCORMCertificateAdapter extends ilCertificateAdapter
{
	private $object;
	
	/**
	* ilSCORMCertificateAdapter contructor
	*
	* @param object $object A reference to a test object
	*/
	function __construct(&$object)
	{
		$this->object =& $object;
	}

	/**
	* Returns the certificate path (with a trailing path separator)
	*
	* @return string The certificate path
	*/
	public function getCertificatePath()
	{
		return CLIENT_WEB_DIR . "/certificates/scorm/" . $this->object->getId() . "/";
	}
	
	/**
	* Returns an array containing all variables and values which can be exchanged in the certificate.
	* The values will be taken for the certificate preview.
	*
	* @return array The certificate variables
	*/
	public function getCertificateVariablesForPreview()
	{
		global $lng;
		include_once "./classes/class.ilFormat.php";
		$insert_tags = array(
			"[USER_LOGIN]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_login")),
			"[USER_FULLNAME]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_fullname")),
			"[USER_FIRSTNAME]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_firstname")),
			"[USER_LASTNAME]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_lastname")),
			"[USER_TITLE]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_title")),
			"[USER_SALUTATION]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_salutation")),
			"[USER_BIRTHDAY]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_birthday")),
			"[USER_INSTITUTION]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_institution")),
			"[USER_DEPARTMENT]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_department")),
			"[USER_STREET]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_street")),
			"[USER_CITY]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_city")),
			"[USER_ZIPCODE]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_zipcode")),
			"[USER_COUNTRY]" => ilUtil::prepareFormOutput($lng->txt("certificate_var_user_country")),
			"[USER_LASTACCESS]" => ilFormat::formatDate(ilFormat::unixtimestamp2datetime(time()-(24*60*60*5)), "datetime", TRUE, FALSE),
			"[SCORM_TITLE]" => ilUtil::prepareFormOutput($this->object->getTitle()),
			"[SCORM_POINTS]" => number_format(80.7, 1, $lng->txt("lang_sep_decimal"), $lng->txt("lang_sep_thousand")) . " %",
			"[SCORM_POINTS_MAX]" => number_format(90, 0, $lng->txt("lang_sep_decimal"), $lng->txt("lang_sep_thousand")),
			"[DATE]" => ilFormat::formatDate(ilFormat::unixtimestamp2datetime(time()), "date", FALSE, FALSE),
			"[DATETIME]" => ilFormat::formatDate(ilFormat::unixtimestamp2datetime(time()), "datetime", TRUE, FALSE)
		);
		return $insert_tags;
	}

	/**
	* Returns an array containing all variables and values which can be exchanged in the certificate
	* The values should be calculated from real data. The $params parameter array should contain all
	* necessary information to calculate the values.
	*
	* @param array $params An array of parameters to calculate the certificate parameter values
	* @return array The certificate variables
	*/
	public function getCertificateVariablesForPresentation($params = array())
	{
		global $lng;
		
		$lng->loadLanguageModule('certificate');
		$user_data = $params["user_data"];
		$points = $this->object->getPointsInPercent();
		$txtPoints = "";
		if (is_null($points))
		{
			$txtPoints = $lng->txt("certificate_points_notavailable");
		}
		else
		{
			$txtPoints = number_format($points, 1, $lng->txt("lang_sep_decimal"), $lng->txt("lang_sep_thousand")) . " %";
		}
		$salutation = "";
		if (strlen($user_data["gender"]))
		{
			$salutation = $lng->txt("salutation_" . $user_data["gender"]);
		}
		$y = ""; $m = ""; $d = "";
		if (preg_match("/(\\d{4})-(\\d{2})-(\\d{2})/", $user_data["birthday"], $matches))
		{
			$y = $matches[1];
			$m = $matches[2];
			$d = $matches[3];
		}
		
		$max_points = $this->object->getMaxPoints();
		$txtMaxPoints = '';
		if (is_null($max_points))
		{
			$txtMaxPoints = $lng->txt("certificate_points_notavailable");
		}
		else
		{
			if($max_points != floor($max_points))
			{
				$txtMaxPoints = number_format($max_points, 1, $lng->txt("lang_sep_decimal"), $lng->txt("lang_sep_thousand"));
			}
			else
			{
				$txtMaxPoints = $max_points;
			}
		}
		
		include_once "./classes/class.ilFormat.php";
		$insert_tags = array(
			"[USER_LOGIN]" => ilUtil::prepareFormOutput(trim($user_data["login"])),
			"[USER_FULLNAME]" => ilUtil::prepareFormOutput(trim($user_data["title"] . " " . $user_data["firstname"] . " " . $user_data["lastname"])),
			"[USER_FIRSTNAME]" => ilUtil::prepareFormOutput($user_data["firstname"]),
			"[USER_LASTNAME]" => ilUtil::prepareFormOutput($user_data["lastname"]),
			"[USER_TITLE]" => ilUtil::prepareFormOutput($user_data["title"]),
			"[USER_SALUTATION]" => ilUtil::prepareFormOutput($salutation),
			"[USER_BIRTHDAY]" => ilUtil::prepareFormOutput((strlen($y.$m.$d)) ? str_replace("m", $m, str_replace("d", $d, str_replace("Y", $y, $lng->txt("lang_dateformat")))) : $lng->txt("not_available")),
			"[USER_INSTITUTION]" => ilUtil::prepareFormOutput($user_data["institution"]),
			"[USER_DEPARTMENT]" => ilUtil::prepareFormOutput($user_data["department"]),
			"[USER_STREET]" => ilUtil::prepareFormOutput($user_data["street"]),
			"[USER_CITY]" => ilUtil::prepareFormOutput($user_data["city"]),
			"[USER_ZIPCODE]" => ilUtil::prepareFormOutput($user_data["zipcode"]),
			"[USER_COUNTRY]" => ilUtil::prepareFormOutput($user_data["country"]),
			"[USER_LASTACCESS]" => ilFormat::formatDate(ilFormat::unixtimestamp2datetime($params["last_access"]), "datetime", FALSE, FALSE),
			"[SCORM_TITLE]" => ilUtil::prepareFormOutput($this->object->getTitle()),
			"[SCORM_POINTS]" => $txtPoints,
			"[SCORM_POINTS_MAX]" => $txtMaxPoints,
			"[DATE]" => ilFormat::formatDate(ilFormat::unixtimestamp2datetime(time()), "date", FALSE, FALSE),
			"[DATETIME]" => ilFormat::formatDate(ilFormat::unixtimestamp2datetime(time()), "datetime", TRUE, FALSE)
		);
		return $insert_tags;
	}
	
	/**
	* Returns a description of the available certificate parameters. The description will be shown at
	* the bottom of the certificate editor text area.
	*
	* @return string The certificate parameters description
	*/
	public function getCertificateVariablesDescription()
	{
		global $lng;
		
		$template = new ilTemplate("tpl.certificate_edit.html", TRUE, TRUE, "Modules/ScormAicc");
		$template->setVariable("PH_INTRODUCTION", $lng->txt("certificate_ph_introduction"));
		$template->setVariable("PH_USER_LOGIN", $lng->txt("certificate_ph_login"));
		$template->setVariable("PH_USER_FULLNAME", $lng->txt("certificate_ph_fullname"));
		$template->setVariable("PH_USER_FIRSTNAME", $lng->txt("certificate_ph_firstname"));
		$template->setVariable("PH_USER_LASTNAME", $lng->txt("certificate_ph_lastname"));
		$template->setVariable("PH_RESULT_PASSED", $lng->txt("certificate_ph_passed"));
		$template->setVariable("PH_RESULT_POINTS", $lng->txt("certificate_ph_resultpoints"));
		$template->setVariable("PH_RESULT_PERCENT", $lng->txt("certificate_ph_resultpercent"));
		$template->setVariable("PH_USER_TITLE", $lng->txt("certificate_ph_title"));
		$template->setVariable("PH_USER_BIRTHDAY", $lng->txt("certificate_ph_birthday"));
		$template->setVariable("PH_USER_SALUTATION", $lng->txt("certificate_ph_salutation"));
		$template->setVariable("PH_USER_STREET", $lng->txt("certificate_ph_street"));
		$template->setVariable("PH_USER_INSTITUTION", $lng->txt("certificate_ph_institution"));
		$template->setVariable("PH_USER_DEPARTMENT", $lng->txt("certificate_ph_department"));
		$template->setVariable("PH_USER_CITY", $lng->txt("certificate_ph_city"));
		$template->setVariable("PH_USER_ZIPCODE", $lng->txt("certificate_ph_zipcode"));
		$template->setVariable("PH_USER_COUNTRY", $lng->txt("certificate_ph_country"));
		$template->setVariable("PH_USER_LASTACCESS", $lng->txt("certificate_ph_lastaccess"));
		$template->setVariable("PH_SCORM_TITLE", $lng->txt("certificate_ph_scormtitle"));
		$template->setVariable("PH_SCORM_POINTS", $lng->txt("certificate_ph_scormpoints"));
		$template->setVariable("PH_SCORM_POINTS_MAX", $lng->txt("certificate_ph_scormmaxpoints"));
		$template->setVariable("PH_DATE", $lng->txt("certificate_ph_date"));
		$template->setVariable("PH_DATETIME", $lng->txt("certificate_ph_datetime"));
		return $template->get();
	}

	/**
	* Allows to add additional form fields to the certificate editor form
	* This method will be called when the certificate editor form will built
	* using the ilPropertyFormGUI class. Additional fields will be added at the
	* bottom of the form.
	*
	* @param object $form An ilPropertyFormGUI instance
	* @param array $form_fields An array containing the form values. The array keys are the names of the form fields
	*/
	public function addAdditionalFormElements(&$form, $form_fields)
	{
		global $lng;
		$short_name = new ilTextInputGUI($lng->txt("certificate_short_name"), "short_name");
		$short_name->setRequired(TRUE);
		require_once "./Services/Utilities/classes/class.ilStr.php";
		$short_name->setValue(strlen($form_fields["short_name"]) ? $form_fields["short_name"] : ilStr::subStr($this->object->getTitle(), 0, 30));
		$short_name->setSize(30);
		if (strlen($form_fields["short_name"])) {
			$short_name->setInfo(str_replace("[SHORT_TITLE]", $form_fields["short_name"], $lng->txt("certificate_short_name_description")));
		} else {
			$short_name->setInfo($lng->txt("certificate_short_name_description"));
		}
		if (count($_POST)) $short_name->checkInput();
		$form->addItem($short_name);

		$visibility = new ilCheckboxInputGUI($lng->txt("certificate_enabled_scorm"), "certificate_enabled_scorm");
		$visibility->setInfo($lng->txt("certificate_enabled_scorm_introduction"));
		$visibility->setValue(1);
		if ($form_fields["certificate_enabled_scorm"])
		{
			$visibility->setChecked(TRUE);
		}
		if (count($_POST)) $visibility->checkInput();
		$form->addItem($visibility);
	}
	
	/**
	* Allows to add additional form values to the array of form values evaluating a
	* HTTP POST action.
	* This method will be called when the certificate editor form will be saved using
	* the form save button.
	*
	* @param array $form_fields A reference to the array of form values
	*/
	public function addFormFieldsFromPOST(&$form_fields)
	{
		$form_fields["certificate_enabled_scorm"] = $_POST["certificate_enabled_scorm"];
		$form_fields["short_name"] = $_POST["short_name"];
	}

	/**
	* Allows to add additional form values to the array of form values evaluating the
	* associated adapter class if one exists 
	* This method will be called when the certificate editor form will be shown and the
	* content of the form has to be retrieved from wherever the form values are saved.
	*
	* @param array $form_fields A reference to the array of form values
	*/
	public function addFormFieldsFromObject(&$form_fields)
	{
		global $ilSetting;
		$scormSetting = new ilSetting("scorm");
		$form_fields["certificate_enabled_scorm"] = $scormSetting->get("certificate_" . $this->object->getId());
		$form_fields["short_name"] = $scormSetting->get("certificate_short_name_" . $this->object->getId());
	}
	
	/**
	* Allows to save additional adapter form fields
	* This method will be called when the certificate editor form is complete and the
	* form values will be saved.
	*
	* @param array $form_fields A reference to the array of form values
	*/
	public function saveFormFields(&$form_fields)
	{
		global $ilSetting;
		$scormSetting = new ilSetting("scorm");
		$scormSetting->set("certificate_" . $this->object->getId(), $form_fields["certificate_enabled_scorm"]);
		$scormSetting->set("certificate_short_name_" . $this->object->getId(), $form_fields["short_name"]);
	}

	/**
	* Returns the adapter type
	* This value will be used to generate file names for the certificates
	*
	* @return string A string value to represent the adapter type
	*/
	public function getAdapterType()
	{
		return "scorm";
	}

	/**
	* Returns a certificate ID
	* This value will be used to generate unique file names for the certificates
	*
	* @return mixed A unique ID which represents a certificate
	*/
	public function getCertificateID()
	{
		return $this->object->getId();
	}

	/**
	* Set the name of the certificate file
	* This method will be called when the certificate will be generated
	*
	* @return string The certificate file name
	*/
	public function getCertificateFilename($params = array())
	{
		global $lng;
		
		$user_data = $params["user_data"];
		if (!is_array($user_data))
		{
			global $ilSetting;
			$scormSetting = new ilSetting("scorm");
			$short_title = $scormSetting->get("certificate_short_name_" . $this->object->getId());
			return strftime("%y%m%d", time()) . "_" . $lng->txt("certificate_var_user_lastname") . "_" . $short_title . "_Zertifikat.pdf";
		}
		else
		{
			return strftime("%y%m%d", time()) . "_" . $user_data["lastname"] . "_" . $params["short_title"] . "_Zertifikat.pdf";
		}
	}

	/**
	* Is called when the certificate is deleted
	* Add some adapter specific code if more work has to be done when the
	* certificate file was deleted
	*/
	public function deleteCertificate()
	{
		global $ilSetting;
		$scormSetting = new ilSetting("scorm");
		$scormSetting->delete("certificate_" . $this->object->getId());
	}
}

?>
