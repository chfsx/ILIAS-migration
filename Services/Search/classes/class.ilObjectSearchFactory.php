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

/**
* Class ilSearchObjectListFactory
*
* Factory for Fulltext/LikeObjectSearch classes
* It depends on the search administration setting which class is instantiated
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* @package ilias-search
*/

class ilObjectSearchFactory
{
	
	/*
	 * get reference of ilFulltext/LikeObjectSearch.
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltext/LikeObjectSearch
	 */
	function &_getObjectSearchInstance(&$query_parser)
	{
		include_once 'Services/Search/classes/class.ilObjSearchSettings.php';

		$search_settings = new ilSearchSettings();

		if($search_settings->enabledIndex())
		{
			// FULLTEXT
			include_once 'Services/Search/classes/Fulltext/class.ilFulltextObjectSearch.php';

			return new ilFulltextObjectSearch($query_parser);
		}
		else
		{
			// LIKE
			include_once 'Services/Search/classes/Like/class.ilLikeObjectSearch.php';

			return new ilLikeObjectSearch($query_parser);
		}
			
	}
}
?>