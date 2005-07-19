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

	/*
	 * get reference of ilFulltext/LikeMetaDataSearch.
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltext/LikeMetaDataSearch
	 */
	function &_getMetaDataSearchInstance(&$query_parser)
	{
		include_once 'Services/Search/classes/class.ilObjSearchSettings.php';

		$search_settings = new ilSearchSettings();

		if($search_settings->enabledIndex())
		{
			// FULLTEXT
			include_once 'Services/Search/classes/Fulltext/class.ilFulltextMetaDataSearch.php';

			return new ilFulltextMetaDataSearch($query_parser);
		}
		else
		{
			// LIKE
			include_once 'Services/Search/classes/Like/class.ilLikeMetaDataSearch.php';

			return new ilLikeMetaDataSearch($query_parser);
		}
	}

	/*
	 * get reference of ilFulltextLMContentSearch
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltextLMContentSearch
	 */
	function &_getLMContentSearchInstance(&$query_parser)
	{
		// In the moment only Fulltext search. Maybe later is lucene search possible
		include_once 'Services/Search/classes/Fulltext/class.ilFulltextLMContentSearch.php';
		
		return new ilFulltextLMContentSearch($query_parser);

	}

	/*
	 * get reference of ilFulltextForumSearch
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltextForumSearch
	 */
	function &_getForumSearchInstance(&$query_parser)
	{
		include_once 'Services/Search/classes/class.ilObjSearchSettings.php';

		$search_settings = new ilSearchSettings();

		if($search_settings->enabledIndex())
		{
			// FULLTEXT
			include_once 'Services/Search/classes/Fulltext/class.ilFulltextForumSearch.php';
			
			return new ilFulltextForumSearch($query_parser);
		}
		else
		{
			// LIKE
			include_once 'Services/Search/classes/Like/class.ilLikeForumSearch.php';

			return new ilLikeForumSearch($query_parser);
		}

	}
		
	/*
	 * get reference of ilFulltextGlossaryDefinitionSearch
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltextGlossaryDefinitionSearch
	 */
	function &_getGlossaryDefinitionSearchInstance(&$query_parser)
	{
		// In the moment only Fulltext search. Maybe later is lucene search possible
		include_once 'Services/Search/classes/Fulltext/class.ilFulltextGlossaryDefinitionSearch.php';
		
		return new ilFulltextGlossaryDefinitionSearch($query_parser);
	}
	/*
	 * get reference of ilFulltextExerciseSearch
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltextExerciseSearch
	 */
	function &_getExerciseSearchInstance(&$query_parser)
	{
		// In the moment only Fulltext search. Maybe later is lucene search possible
		include_once 'Services/Search/classes/Fulltext/class.ilFulltextExerciseSearch.php';
		
		return new ilFulltextExerciseSearch($query_parser);
	}

	/*
	 * get reference of ilFulltextTestSearch
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltextTestSearch
	 */
	function &_getTestSearchInstance(&$query_parser)
	{
		// In the moment only Fulltext search. Maybe later is lucene search possible
		include_once 'Services/Search/classes/Fulltext/class.ilFulltextTestSearch.php';
		
		return new ilFulltextTestSearch($query_parser);
	}

	/*
	 * get reference of ilFulltextMediapoolSearch
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltextMediapoolSearch
	 */
	function &_getMediaPoolSearchInstance(&$query_parser)
	{
		// In the moment only Fulltext search. Maybe later is lucene search possible
		include_once 'Services/Search/classes/Fulltext/class.ilFulltextMediaPoolSearch.php';
		
		return new ilFulltextMediaPoolSearch($query_parser);
	}
	/*
	 * get reference of ilFulltextAdvancedSearch
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltextAdvancedSearch
	 */
	function &_getAdvancedSearchInstance(&$query_parser)
	{
		// In the moment only Fulltext search. Maybe later is lucene search possible
		include_once 'Services/Search/classes/Fulltext/class.ilFulltextAdvancedSearch.php';
		
		return new ilFulltextAdvancedSearch($query_parser);
	}
	/*
	 * get reference of ilLuceneFileSearch
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltextAdvancedSearch
	 */
	function &_getFileSearchInstance(&$query_parser)
	{
		include_once 'Services/Search/classes/Lucene/class.ilLuceneFileSearch.php';
		
		return new ilLuceneFileSearch($query_parser);
	}
	/*
	 * get reference of ilLuceneHTLMSearch
	 * 
	 * @param object query parser object
	 * @return object reference of ilFulltextAdvancedSearch
	 */
	function &_getHTLMSearchInstance(&$query_parser)
	{
		include_once 'Services/Search/classes/Lucene/class.ilLuceneHTLMSearch.php';
		
		return new ilLuceneHTLMSearch($query_parser);
	}
	/*
	 * get reference of ilFulltextWebresourceSearch
	 * 
	 * @param object query parser object
	 * @return object reference of ilWebresourceAdvancedSearch
	 */
	function &_getWebresourceSearchInstance(&$query_parser)
	{
		include_once 'Services/Search/classes/Fulltext/class.ilFulltextWebresourceSearch.php';
		
		return new ilFulltextWebresourceSearch($query_parser);
	}


}
?>