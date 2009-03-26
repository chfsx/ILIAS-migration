<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
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
* Yahoo YUI Library Utility functions
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*/
class ilYuiUtil
{
	/**
	* Init YUI Connection module
	*/
	static function initConnection()
	{
		global $tpl;
		
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo/yahoo-min.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/event/event-min.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/connection/connection-min.js");
	}
	
	/**
	* Init YUI Drag and Drop
	*/
	static function initDragDrop()
	{
		global $tpl;

		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo-dom-event/yahoo-dom-event.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/dragdrop/dragdrop-min.js");
	}
	
	/**
	* Init YUI DomEvent
	*/
	static function initDomEvent()
	{
		global $tpl;

		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo/yahoo-min.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo-dom-event/yahoo-dom-event.js");
	}
	
	/**
	 * Init yui panel
	 *
	 * @access public
	 * @param void
	 * @return void
	 */
	public function initPanel()
	{
		global $tpl;
		
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/yahoo-dom-event/yahoo-dom-event.js');
		// optional
		//$tpl->addJavaScript('./Services/YUI/js/2_5_0/animation/animation-min.js');
		// optional
		//$tpl->addJavaScript('./Services/YUI/js/2_5_0/dragdrop/dragdrop-min.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/container/container-min.js');
		$tpl->addCss("./Services/YUI/js/2_5_0/container/assets/skins/sam/container.css");
		#$tpl->addCss("./Services/YUI/js/2_5_0/container/assets/container-core.css");
		$tpl->addCss('./Services/Calendar/css/panel_min.css');
		
	}


	/**
	* Init YUI Connection module
	*/
	static function initConnectionWithAnimation()
	{
		global $tpl;
		
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo/yahoo-min.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/animation/animation-min.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo-dom-event/yahoo-dom-event.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/connection/connection-min.js");
	}

	/**
	* Init YUI Menu module
	*/
	static function initMenu()
	{
		global $tpl;
		
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo/yahoo-min.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/event/event.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/dom/dom.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/container/container_core.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/menu/menu.js");
		$tpl->addCss("./Services/YUI/js/2_5_0/menu/assets/menu.css");
	}

	/**
	* Init YUI Overlay module
	*/
	static function initOverlay()
	{
		global $tpl;
		
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo/yahoo-min.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/event/event.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/dom/dom.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/container/container_core.js");
	}
	
	/**
	* Init YUI Simple Dialog
	*/
	static function initSimpleDialog()
	{
		global $tpl;

		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo/yahoo-min.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/event/event.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/dom/dom.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/container/container.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/dragdrop/dragdrop.js");
		$tpl->addCss("./Services/YUI/js/2_5_0/container/assets/container.css");
		$tpl->addCss("./Services/YUI/templates/default/tpl.simpledialog.css");
	}
	
	/**
	* init drag & drop list
	*/
	static function initDragDropList()
	{
		global $tpl;

		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo-dom-event/yahoo-dom-event.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/animation/animation-min.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/dragdrop/dragdrop-min.js");
		$tpl->addCss("./Services/YUI/templates/default/DragDropList.css");
	}
	
	/**
	* init drag & drop and animation
	*/
	static function initDragDropAnimation()
	{
		global $tpl;

		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo-dom-event/yahoo-dom-event.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/animation/animation-min.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/dragdrop/dragdrop-min.js");
	}
	
	/**
	* get a drag & drop list
	*/
	static function getDragDropList($id_source, $title_source, $source, $id_dest, $title_dest, $dest)
	{
		ilYuiUtil::initDragDropList();
		
		$template = new ilTemplate("tpl.dragdroplist.html", TRUE, TRUE, "Services/YUI");
		foreach ($source as $id => $name)
		{
			$template->setCurrentBlock("source_element");
			$template->setVariable("ELEMENT_ID", $id);
			$template->setVariable("ELEMENT_NAME", $name);
			$template->parseCurrentBlock();
			$template->setCurrentBlock("element");
			$template->setVariable("ELEMENT_ID", $id);
			$template->parseCurrentBlock();
		}
		foreach ($dest as $id => $name)
		{
			$template->setCurrentBlock("dest_element");
			$template->setVariable("ELEMENT_ID", $id);
			$template->setVariable("ELEMENT_NAME", $name);
			$template->parseCurrentBlock();
			$template->setCurrentBlock("element");
			$template->setVariable("ELEMENT_ID", $id);
			$template->parseCurrentBlock();
		}
		$template->setVariable("TITLE_LIST_1", $title_source);
		$template->setVariable("TITLE_LIST_2", $title_dest);
		$template->setVariable("LIST_1", $id_source);
		$template->setVariable("LIST_2", $id_dest);
		return $template->get();
	}
	
	static function addYesNoDialog($dialogname, $headertext, $message, $yesaction, $noaction, $defaultyes, $icon = "help")
	{
		global $tpl, $lng;
		
		ilYuiUtil::initSimpleDialog();
		
		$template = new ilTemplate("tpl.yes_no_dialog.js", TRUE, TRUE, "Services/YUI");
		$template->setVariable("DIALOGNAME", $dialogname);
		$template->setVariable("YES_ACTION", $yesaction);
		$template->setVariable("NO_ACTION", $noaction);
		$template->setVariable("DIALOG_HEADER", $headertext);
		$template->setVariable("DIALOG_MESSAGE", $message);
		$template->setVariable("TEXT_YES", $lng->txt("yes"));
		$template->setVariable("TEXT_NO", $lng->txt("no"));
		switch ($icon)
		{
			case "warn":
				$template->setVariable("ICON", "YAHOO.widget.SimpleDialog.ICON_WARN");
				break;
			case "tip":
				$template->setVariable("ICON", "YAHOO.widget.SimpleDialog.ICON_TIP");
				break;
			case "info":
				$template->setVariable("ICON", "YAHOO.widget.SimpleDialog.ICON_INFO");
				break;
			case "block":
				$template->setVariable("ICON", "YAHOO.widget.SimpleDialog.ICON_BLOCK");
				break;
			case "alarm":
				$template->setVariable("ICON", "YAHOO.widget.SimpleDialog.ICON_ALARM");
				break;
			case "help":
			default:
				$template->setVariable("ICON", "YAHOO.widget.SimpleDialog.ICON_HELP");
				break;
		}
		if ($defaultyes)
		{
			$template->touchBlock("isDefaultYes");
		}
		else
		{
			$template->touchBlock("isDefaultNo");
		}
		$tpl->setCurrentBlock("HeadContent");
		$tpl->setVariable("CONTENT_BLOCK", $template->get());
		$tpl->parseCurrentBlock();
	}
	
	/**
	 * init calendar
	 *
	 * @access public
	 * @return
	 * @static
	 */
	public static function initCalendar()
	{
		global $tpl;
		
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/yahoo/yahoo.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/event/event.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/dom/dom.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/calendar/calendar.js');
			
		$tpl->addCss('./Services/YUI/js/2_5_0/calendar/assets/skins/sam/calendar.css');
		$tpl->addCss('./Services/Calendar/css/calendar.css');
	}
	
	/**
	 * init button control
	 * In the moment used for calendar color picker button
	 *
	 * @access public
	 * @return void
	 * @static
	 */
	public static function initButtonControl()
	{
		global $tpl;
		
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/yahoo-dom-event/yahoo-dom-event.js");
		$tpl->addJavaScript("./Services/YUI/js/2_5_0/element/element-beta-min.js");
		
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/container/container_core-min.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/menu/menu-min.js');
		
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/button/button-min.js');

		$tpl->addCss("./Services/YUI/js/2_5_0/button/assets/skins/sam/button.css");
		$tpl->addCss("./Services/YUI/js/2_5_0/menu/assets/skins/sam/menu.css");
	}
	
	/**
	 * init color picker button
	 *
	 * @access public
	 * @return void
	 * @static
	 */
	public static function initColorPicker()
	{
		global $tpl;

		self::initButtonControl();
		
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/dragdrop/dragdrop-min.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/slider/slider-min.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/colorpicker/colorpicker-min.js');

		$tpl->addCss('./Services/Form/css/color_picker.css');
		$tpl->addCss("./Services/YUI/js/2_5_0/colorpicker/assets/skins/sam/colorpicker.css");
	}
	
	/**
	* Init YUI TabView component
	*/
	public static function initTabView()
	{
		global $tpl;
		
		$tpl->addCss("./Services/YUI/js/2_5_0/tabview/assets/skins/sam/tabview.css");
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/yahoo-dom-event/yahoo-dom-event.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/element/element-beta-min.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/tabview/tabview-min.js');

		//<script type="text/javascript" src="http://yui.yahooapis.com/2.5.1/build/tabview/tabview-min.js"></script>
	}
	
	/**
	 * Init YUI AutoComplete component
	 * @author jposselt@databay.de
	 */
	 public static function initAutoComplete()
	 {
	 	global $tpl;

		self::initJson();

	 	$tpl->addCss("./Services/YUI/js/2_5_0/autocomplete/assets/skins/sam/autocomplete.css");

		$tpl->addJavaScript('./Services/YUI/js/2_5_0/yahoo-dom-event/yahoo-dom-event.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/datasource/datasource-beta-min.js');
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/connection/connection-min.js');		
	 	$tpl->addJavaScript('./Services/YUI/js/2_5_0/autocomplete/autocomplete-min.js');
	 }
	 
	 /**
	  * Init YUI JSON component
	  * @author jposselt@databay.de
	  */
	 public static function initJson()
	 {
		global $tpl;
		$tpl->addJavaScript('./Services/YUI/js/2_5_0/yahoo/yahoo-min.js');		
	 	$tpl->addJavaScript('./Services/YUI/js/2_5_0/json/json-min.js');
	 }
	 
} // END class.ilUtil
?>
