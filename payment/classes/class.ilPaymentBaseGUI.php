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
include_once "./payment/classes/class.ilPaymentVendors.php";

class ilPaymentBaseGUI
{
	var $ilias;
	var $lng;
	var $db;
	var $tpl;
	var $rbacsystem;

	var $user_obj;

	var $section;
	var $main_section;

	function ilPaymentBaseGUI()
	{
		global $ilias,$ilDB,$lng,$tpl,$rbacsystem;

		define('ILIAS_MODULE','payment');

		$this->ilias =& $ilias;
		$this->db =& $ilDB;
		$this->lng =& $lng;
		$this->tpl =& $tpl;


		$this->SECTION_STATISTIC = 1;
		$this->SECTION_OBJECT = 2;
		$this->SECTION_TRUSTEE = 3;

		$this->ADMIN = 4;
	}

	function setSection($a_section)
	{
		$this->section = $a_section;
	}
	function getSection()
	{
		return $this->section;
	}
	function setMainSection($a_main_section)
	{
		$this->main_section = $a_main_section;
	}
	function getMainSection()
	{
		return $this->main_section;
	}
	
	function buildHeader()
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.payb_content.html");
		
		switch($this->getMainSection())
		{
			case $this->ADMIN:
				$this->tpl->setVariable("HEADER",$this->lng->txt('paya_header'));
				break;
		}
		$this->__buildStylesheet();
		$this->__buildStatusline();
		$this->__buildButtons();
	}

	function showButton($a_cmd,$a_text,$a_target = '')
	{
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");
		
		// display button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK",$this->ctrl->getLinkTarget($this,$a_cmd));
		$this->tpl->setVariable("BTN_TXT",$a_text);
		if($a_target)
		{
			$this->tpl->setVariable("BTN_TARGET",$a_target);
		}

		$this->tpl->parseCurrentBlock();
	}
	function &initTableGUI()
	{
		include_once "./classes/class.ilTableGUI.php";

		return new ilTableGUI(0,false);
	}
	function setTableGUIBasicData(&$tbl,&$result_set,$from = "")
	{
		switch($from)
		{
			default:
				$offset = $_GET["offset"];
				$order = $_GET["sort_by"];
				$direction = $_GET["sort_order"];
				break;
		}

		$tbl->setOrderColumn($order);
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setMaxCount(count($result_set));
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		$tbl->setData($result_set);
	}

		

	// PIRVATE
	function  __buildStatusline()
	{
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		$this->__buildLocator();
	}	
	function __buildLocator()
	{
		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
		$this->tpl->setVariable("TXT_LOCATOR",$this->lng->txt("locator"));

		$this->tpl->setCurrentBlock("locator_item");
		$this->tpl->setVariable("ITEM", $this->lng->txt("personal_desktop"));
		$this->tpl->setVariable("LINK_ITEM", "../usr_personaldesktop.php");
		$this->tpl->parseCurrentBlock();

		switch($this->getMainSection())
		{
			case $this->ADMIN:
				$this->tpl->setCurrentBlock("locator_item");
				$this->tpl->setVariable("PREFIX",'>&nbsp;');
				$this->tpl->setVariable("ITEM", $this->lng->txt("paya_locator"));
				$this->tpl->setVariable("LINK_ITEM", "./payment_admin.php");
				$this->tpl->parseCurrentBlock();

				break;
		}

		// CHECK for new mail and info
		sendInfo();

		return true;
	}
	function __buildStylesheet()
	{
		$this->tpl->setVariable("LOCATION_STYLESHEET",ilUtil::getStyleSheetLocation());
		$this->tpl->setVariable("LOCATION_JAVASCRIPT",ilUtil::getJSPath('functions.js'));
	}

	function __buildButtons()
	{
		$this->tpl->addBlockFile("TABS", "tabs", "tpl.tabs.html");

		if(ilPaymentVendors::_isVendor($this->user_obj->getId()) or 
		   ilPaymentTrustees::_hasStatisticPermission($this->user_obj->getId()))
		{
			$this->tpl->setCurrentBlock("tab");
			$this->tpl->setVariable('TAB_TYPE',$this->getSection() == $this->SECTION_STATISTIC ? 'tabactive' : 'tabinactive');
			$this->tpl->setVariable("TAB_LINK",$this->ctrl->getLinkTargetByClass('ilpaymentstatisticgui'));
			$this->tpl->setVariable("TAB_TEXT",$this->lng->txt('paya_statistik'));
			$this->tpl->parseCurrentBlock();
		}
		if(ilPaymentVendors::_isVendor($this->user_obj->getId()) or 
		   ilPaymentTrustees::_hasObjectPermission($this->user_obj->getId()))
		{
			$this->tpl->setCurrentBlock("tab");
			$this->tpl->setVariable('TAB_TYPE',$this->getSection() == $this->SECTION_OBJECT ? 'tabactive' : 'tabinactive');
			$this->tpl->setVariable("TAB_LINK",$this->ctrl->getLinkTargetByClass('ilpaymentobjectgui'));
			$this->tpl->setVariable("TAB_TEXT",$this->lng->txt('paya_object'));
			$this->tpl->parseCurrentBlock();
		}
		if(ilPaymentVendors::_isVendor($this->user_obj->getId()))
		{
			$this->tpl->setCurrentBlock("tab");
			$this->tpl->setVariable('TAB_TYPE',$this->getSection() == $this->SECTION_TRUSTEE ? 'tabactive' : 'tabinactive');
			$this->tpl->setVariable("TAB_LINK",$this->ctrl->getLinkTargetByClass('ilpaymenttrusteegui'));
			$this->tpl->setVariable("TAB_TEXT",$this->lng->txt('paya_trustees'));
			$this->tpl->parseCurrentBlock();
		}
	}




}
?>