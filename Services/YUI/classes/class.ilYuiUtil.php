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
		
		$tpl->addJavaScript("./Services/YUI/js/0_12_1/yahoo/yahoo-min.js");
		$tpl->addJavaScript("./Services/YUI/js/0_12_1/connection/connection-min.js");
	}
	
} // END class.ilUtil
?>
