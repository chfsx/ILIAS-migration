<?php
/**
* Class LearningModuleObjectOut
*
* @author Stefan Meyer <smeyer@databay.de>
* @author Sascha Hofmann <shofmann@databay.de> 
* $Id$Id: class.LearningModuleObjectOut.php,v 1.7 2003/01/30 14:57:01 smeyer Exp $
* 
* @extends ObjectOut
* @package ilias-core
*/

class LearningModuleObjectOut extends ObjectOut
{
	/**
	* Constructor
	* 
	* @access public
	*/
	function LearningModuleObjectOut($a_data)
	{
		$this->ObjectOut($a_data);
	}

	/**
	* Overwritten method from class.Object.php
	* It handles all button commands from Learning Modules
	* 
	* @access public
	*/
	function gatewayObject()
	{
		global $lng;

		switch(key($_POST["cmd"]))
		{
			case "import":
				return $this->importObject();
				break;
				
			case "export":
				return;
				break;

			case "upload":
				return $this->uploadObject();
				break;
		}
		parent::gatewayObject();
	}

	/**
	* display dialogue for importing XML-LeaningObjects
	*
	*  @access	public
	*/
	function importObject()
	{
		$this->getTemplateFile("import");
		$this->tpl->setVariable("FORMACTION", "adm_object.php?cmd=gateway&type=".$_GET["type"].
						  "&obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&parent_parent=".$_GET["parent_parent"]);
		$this->tpl->setVariable("BTN_NAME", "upload");
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("upload"));
	}
	
	/**
	* display status information or report errors messages
	* in case of error
	* 
	* @access	public
	*/
	function uploadObject()
	{
		header("Location: adm_object.php?cmd=view&obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"].
			   "&message=".urlencode($this->data["msg"]));
		exit();
		
		//nada para mirar ahora :-)
	}
} // END class.LeraningObject
?>