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
* Class ilFulltextObjectSearch
*
* Performs Mysql fulltext search in object_data title and description
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* @package ilias-search
*
*/
include_once 'Services/Search/classes/class.ilObjectSearch.php';

class ilFulltextObjectSearch extends ilObjectSearch
{
	/**
	* Constructor
	* @access public
	*/
	function ilFulltextObjectSearch(&$qp_obj)
	{
		parent::ilObjectSearch($qp_obj);
	}

	function __createWhereCondition()
	{
		
		if($this->db->isMysql4_0OrHigher())
		{
			$where = " WHERE MATCH (title,description) AGAINST(' ";
			
			$prefix = $this->qp_obj->getCombination() == 'and' ? '+' : '';
			foreach($this->qp_obj->getWords() as $word)
			{
				$where .= $prefix;
				$where .= $word;
				$where .= '* ';
			}
			$where .= "' IN BOOLEAN MODE) ";
			
			return $where;
		}
		else
		{
			if($this->qp_obj->getCombination() == 'or')
			{
				// i do not see any reason, but MATCH AGAINST(...) OR MATCH AGAINST(...) does not use an index
				$where = " WHERE MATCH (title,description) AGAINST(' ";
			
				foreach($this->qp_obj->getWords() as $word)
				{
					$where .= $word;
				}
				$where .= "')";
			
				return $where;
			}
			else
			{
				$where = "WHERE ";
				$counter = 0;
				foreach($this->qp_obj->getWords() as $word)
				{
					if($counter++)
					{
						$where .= strtoupper($this->qp_obj->getCombination());
					}
					$where .= " MATCH (title,description) AGAINST('";
					$where .= $word;
					$where .= "')";
				}
				return $where;
			}
		}
	}
}
?>
