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

include_once './Services/Object/classes/class.ilSubItemListGUI.php';
include_once './Modules/Wiki/classes/class.ilWikiPage.php';
include_once './Modules/Wiki/classes/class.ilObjWikiGUI.php';
include_once './classes/class.ilLink.php';

/** 
* Show wiki pages
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
*
* @ingroup ModulesWiki
*/
class ilObjWikiSubItemListGUI extends ilSubItemListGUI
{
	/**
	 * get html 
	 * @return
	 */
	public function getHTML()
	{
		global $lng;
		
		$lng->loadLanguageModule('content');
		foreach($this->getSubItemIds(true) as $sub_item)
		{
			if(is_object($this->getHighlighter()) and strlen($this->getHighlighter()->getContent($this->getObjId(),$sub_item)))
			{
				$this->tpl->setCurrentBlock('sea_fragment');
				$this->tpl->setVariable('TXT_FRAGMENT',$this->getHighlighter()->getContent($this->getObjId(),$sub_item));
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock('subitem');
			$this->tpl->setVariable('SUBITEM_TYPE',$lng->txt('obj_pg'));
			$this->tpl->setVariable('SEPERATOR',':');
			
			$title = ilWikiPage::lookupTitle($sub_item);
			
			$this->tpl->setVariable('LINK',ilObjWikiGUI::getGotoLink($this->getRefId(),$title));
			$this->tpl->setVariable('TARGET',$this->getItemListGUI()->getCommandFrame(''));
			$this->tpl->setVariable('TITLE',$title);			
			$this->tpl->parseCurrentBlock();
		}
		
		$this->showDetailsLink();
		
		return $this->tpl->get();	 
	}
}
?>