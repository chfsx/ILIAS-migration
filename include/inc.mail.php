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


function setLocator($a_obj_id,$a_user_id,$a_txt_prefix)
{
		global $lng,$tpl;

		// IF THERE IS NO OBJ_ID GIVEN GET THE ID OF MAIL ROOT NODE
		if(!$a_obj_id)
		{
			include_once "classes/class.ilMailbox.php";
			$mbox = new ilMailBox($_SESSION["AccountId"]);
			$a_obj_id = $mbox->getInboxFolder();
		}		

		$tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
		$tpl->setVariable("TXT_LOCATOR",$lng->txt("locator"));
		$mtree = new ilTree($a_user_id);
		$mtree->setTableNames('mail_tree','mail_obj_data');
		$path_full = $mtree->getPathFull($a_obj_id,$mtree->readRootId());
		
		// FIRST ITEM IS INBOX
		$tpl->touchBlock("locator_separator");
		$tpl->setCurrentBlock("locator_item");
		$tpl->setVariable("ITEM", $lng->txt("mail_mails_of"));
		$tpl->setVariable("LINK_ITEM", "mail.php");
		$tpl->parseCurrentBlock();
#		var_dump("<pre>",$_SERVER,"</pre");
		switch(basename($_SERVER["SCRIPT_NAME"]))
		{
			case "mail_addressbook.php":
				$tpl->setCurrentBlock("locator_item");
				$tpl->setVariable("ITEM", $lng->txt("mail_addressbook"));
				$tpl->setVariable("LINK_ITEM", "mail_addressbook.php?mobj_id=$a_obj_id");
				$tpl->parseCurrentBlock();
				return true;

			case "mail_new.php":
			case "mail_attachment.php":
			case "mail_search.php":
				$tpl->setCurrentBlock("locator_item");
				$tpl->setVariable("ITEM", $lng->txt("mail_compose"));
				$tpl->setVariable("LINK_ITEM", "mail_new.php?mobj_id=$a_obj_id");
				$tpl->parseCurrentBlock();
				return true;
				
			case "mail_options.php":
				$tpl->setCurrentBlock("locator_item");
				$tpl->setVariable("ITEM", $lng->txt("mail_options_of"));
				$tpl->setVariable("LINK_ITEM", "mail_options.php?mobj_id=$a_obj_id");
				$tpl->parseCurrentBlock();
				return true;
		}
		unset($path_full[0]);
		foreach ($path_full as $key => $row)
		{
			if($row["type"] != 'user_folder')
			{
				$row["title"] = $lng->txt("mail_".$row["title"]);
			}
			if ($key < count($path_full))
			{
				$tpl->touchBlock("locator_separator");
			}
			$tpl->setCurrentBlock("locator_item");
			$tpl->setVariable("ITEM", $row["title"]);
			// TODO: SCRIPT NAME HAS TO BE VARIABLE!!!
			$tpl->setVariable("LINK_ITEM", "mail.php?mobj_id=".$row["child"]);
			$tpl->parseCurrentBlock();
		}

		$tpl->setCurrentBlock("locator");
		
		
		$tpl->setVariable("TXT_PATH",$a_txt_prefix);
		$tpl->parseCurrentBlock();
}
/**
 * Builds a select form field with options and shows the selected option first
 * @param	string	value to be selected
 * @param	string	variable name in formular
 * @param	array	array with $options (key = lang_key, value = long name)
 * @param	boolean
 */
function formSelect ($selected,$varname,$options,$multiple = false)
{
	global $lng;
		
	$multiple ? $multiple = " multiple=\"multiple\"" : "";
	$str = "<select name=\"".$varname ."\"".$multiple.">\n";

	foreach ($options as $key => $val)
	{
		
		$str .= " <option value=\"".$val."\"";
			
		if ($selected == $key)
		{
			$str .= " selected=\"selected\"";
		}
			
		$str .= ">".$val."</option>\n";
	}

	$str .= "</select>\n";
		
	return $str;
}

function assignMailToPost($a_mail_data)
{
	foreach($a_mail_data as $key => $data)
	{
		$_POST[$key] = $data;
	}
}

?>
