<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Export User Interface Class
*
* @author	Alex Killing <alex.killing@gmx.de>
* @version	$Id$
* @ingroup	ServicesExport
*
* @ilCtrl_Calls ilExportGUI:
*/
class ilExportGUI
{
	protected $formats = array();
	protected $custom_columns = array();
	protected $custom_multi_commands = array();
	
	/**
	 * Constuctor
	 *
	 * @param
	 * @return
	 */
	function __construct($a_parent_gui)
	{
		global $lng;
		
		$this->parent_gui = $a_parent_gui;
		$this->obj = $a_parent_gui->object;
		$lng->loadLanguageModule("exp");
	}
	
	/**
	 * Add formats
	 *
	 * @param	array	formats
	 */
	function addFormat($a_key, $a_txt = "", $a_call_obj = null, $a_call_func = "")
	{
		global $lng;
		
		if ($a_txt == "")
		{
			$a_txt = $lng->txt("exp_".$a_key);
		}
		$this->formats[] = array("key" => $a_key, "txt" => $a_txt,
			"call_obj" => $a_call_obj, "call_func" => $a_call_func);
	}
	
	/**
	 * Get formats
	 *
	 * @return	array	formats
	 */
	function getFormats()
	{
		return $this->formats;
	}
	
	/**
	 * Add custom column
	 *
	 * @param
	 * @return
	 */
	function addCustomColumn($a_txt, $a_obj, $a_func)
	{
		$this->custom_columns[] = array("txt" => $a_txt,
										"obj" => $a_obj,
										"func" => $a_func);
	}
	
	/**
	 * Add custom multi command
	 *
	 * @param
	 * @return
	 */
	function addCustomMultiCommand($a_txt, $a_obj, $a_func)
	{
		$this->custom_multi_commands[] = array("txt" => $a_txt,
										"obj" => $a_obj,
										"func" => $a_func);
	}
	
	/**
	 * Get custom multi commands
	 */
	function getCustomMultiCommands()
	{
		return $this->custom_multi_commands;
	}

	/**
	 * Get custom columns
	 *
	 * @param
	 * @return
	 */
	function getCustomColumns()
	{
		return $this->custom_columns;
	}

	/**
	 * Execute command
	 *
	 * @param
	 * @return
	 */
	function executeCommand()
	{
		global $ilCtrl;
	
		$cmd = $ilCtrl->getCmd("listExportFiles");
		
		switch ($cmd)
		{
			case "listExportFiles":
				$this->$cmd();
				break;
				
			default:
				if (substr($cmd, 0, 7) == "create_")
				{
					$this->createExportFile();
				}
				else if (substr($cmd, 0, 6) == "multi_")	// custom multi command
				{
					$this->handleCustomMultiCommand();
				}
				else
				{
					$this->$cmd();
				}
				break;
		}
	}
	
	/**
	 * List export files
	 *
	 * @param
	 * @return
	 */
	function listExportFiles()
	{
		global $tpl, $ilToolbar, $ilCtrl, $lng;

		// creation buttons
		$ilToolbar->setFormAction($ilCtrl->getFormAction($this));
		if (count($this->getFormats()) > 1)
		{
			// type selection
			foreach ($this->getFormats() as $f)
			{
				$options[$f["key"]] = $f["txt"];
			}
			include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
			$si = new ilSelectInputGUI($lng->txt("type"), "format");
			$si->setOptions($options);
			$ilToolbar->addInputItem($si, true);
			$ilToolbar->addFormButton($lng->txt("exp_create_file"), "createExportFile");
		}
		else
		{
			$format = $this->getFormats();
			$format = $format[0];
			$ilToolbar->addFormButton($lng->txt("exp_create_file")." (".$format["txt"].")", "create_".$format["key"]);
		}
	
		include_once("./Services/Export/classes/class.ilExportTableGUI.php");
		$table = new ilExportTableGUI($this, "listExportFiles", $this->obj);
		$table->setSelectAllCheckbox("file");
		foreach ($this->getCustomColumns() as $c)
		{
			$table->addCustomColumn($c["txt"], $c["obj"], $c["func"]); 
		}
		foreach ($this->getCustomMultiCommands() as $c)
		{
			$table->addCustomMultiCommand($c["txt"], "multi_".$c["func"]); 
		}
		$tpl->setContent($table->getHTML());
		
	}
	
	/**
	 * Create export file
	 *
	 * @param
	 * @return
	 */
	function createExportFile()
	{
		global $ilCtrl;

		if ($ilCtrl->getCmd() == "createExportFile")
		{
			$format = ilUtil::stripSlashes($_POST["format"]);
		}
		else
		{
			$format = substr($ilCtrl->getCmd(), 7);
		}
		foreach ($this->getFormats() as $f)
		{
			if ($f["key"] == $format)
			{
				if (is_object($f["call_obj"]))
				{
					$f["call_obj"]->$f["call_func"]();
				}
				else if ($format == "xml")		// standard procedure
				{
					include_once("./Services/Export/classes/class.ilExport.php");
					ilExport::_exportObject($this->obj->getType(),
						$this->obj->getId(), "4.1.0");
				}
			}
		}
		$ilCtrl->redirect($this, "listExportFiles");
	}
	
	/**
	 * Confirm file deletion
	 */
	function confirmDeletion()
	{
		global $ilCtrl, $tpl, $lng;
			
		if (!is_array($_POST["file"]) || count($_POST["file"]) == 0)
		{
			ilUtil::sendInfo($lng->txt("no_checkbox"), true);
			$ilCtrl->redirect($this, "listExportFiles");
		}
		else
		{
			include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
			$cgui = new ilConfirmationGUI();
			$cgui->setFormAction($ilCtrl->getFormAction($this));
			$cgui->setHeaderText($lng->txt("exp_really_delete"));
			$cgui->setCancel($lng->txt("cancel"), "listExportFiles");
			$cgui->setConfirm($lng->txt("delete"), "delete");
			
			foreach ($_POST["file"] as $i)
			{
				$iarr = explode(":", $i);
				$cgui->addItem("file[]", $i, $iarr[1]);
			}
			
			$tpl->setContent($cgui->getHTML());
		}
	}
	
	/**
	 * Delete files
	 */
	function delete()
	{
		global $ilCtrl;
		
		foreach($_POST["file"] as $file)
		{
			$file = explode(":", $file);
			
			include_once("./Services/Export/classes/class.ilExport.php");
			$export_dir = ilExport::_getExportDirectory($this->obj->getId(),
				str_replace("..", "", $file[0]), $this->obj->getType());

			$exp_file = $export_dir."/".str_replace("..", "", $file[1]);
			$exp_dir = $export_dir."/".substr($file[1], 0, strlen($file[1]) - 4);
			if (@is_file($exp_file))
			{
				unlink($exp_file);
			}
			if (@is_dir($exp_dir))
			{
				ilUtil::delDir($exp_dir);
			}
		}
		$ilCtrl->redirect($this, "listExportFiles");
	}
	
	/**
	 * Download file
	 */
	function download()
	{
		global $ilCtrl, $lng;
		
		if(!isset($_POST["file"]))
		{
			ilUtil::sendFailure($lng->txt("no_checkbox"), true);
			$ilCtrl->redirect($this, "listExportFiles");
		}

		if (count($_POST["file"]) > 1)
		{
			ilUtil::sendFailure($lng->txt("exp_select_max_one_item"), true);
			$ilCtrl->redirect($this, "listExportFiles");
		}

		$file = explode(":", $_POST["file"][0]);
		include_once("./Services/Export/classes/class.ilExport.php");
		$export_dir = ilExport::_getExportDirectory($this->obj->getId(),
			str_replace("..", "", $file[0]), $this->obj->getType());
		ilUtil::deliverFile($export_dir."/".$file[1],
			$file[1]);
	}
	
	/**
	 * Handle custom multi command
	 *
	 * @param
	 * @return
	 */
	function handleCustomMultiCommand()
	{
		global $ilCtrl;

		$cmd = substr($ilCtrl->getCmd(), 6);
		foreach ($this->getCustomMultiCommands() as $c)
		{
			if ($c["func"] == $cmd)
			{
				$c["obj"]->$c["func"]($_POST["file"]);
			}
		}
	}
}
