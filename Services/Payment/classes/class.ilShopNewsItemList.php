<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2008 ILIAS open source, University of Cologne            |
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
*
* @author Nadia Krzywon <nkrzywon@databay.de>
* @version $Id$
*/
class ilShopNewsItemList implements Iterator
{
	const TYPE_NEWS = 1;
	const TYPE_ARCHIVE = 2;
	
	private static $instance = null;
	
	private $db = null;
	private $news = array();
	private $mode = self::TYPE_NEWS;
	private $archive_date = null;
	private $public_section = false;	
	
	private function __construct()
	{
		global $ilDB;
		
		$this->db = $ilDB;
	}
	
	private function __clone()
	{
	}
	
	public function hasItems()
	{
		return (bool)count($this->news);
	}
	
	public function rewind()
	{
		return reset($this->news);
	}
	
	public function valid()
	{
		return (bool)current($this->news);
	}

	public function current()
	{
		return current($this->news);
	}

	public function key()
	{
		return key($this->news);
	}

	public function next()
	{
		return next($this->news);
	}
	
	public function _getInstance()
	{
		if(!isset(self::$instance))
		{
			self::$instance = new ilShopNewsItemList();
		}
		
		return self::$instance;
	}
	
	public function read()
	{
		$this->news = array();
		
		$types = array();
		$data = array();

		$query = 'SELECT * FROM payment_news WHERE 1 = 1 ';		
		if($this->isPublicSection())
		{
			$query .= "AND visibility = %s ";
			$types[] = 'text';
			$data[] = 'public';
		}		
		if($this->getArchiveDate() !== null && $this->getArchiveDate() > 0)
		{
			switch($this->getMode())
			{
				case self::TYPE_NEWS:
					$query .= "AND UNIX_TIMESTAMP(creation_date) >= %s ";
					$types[] = 'integer';
					$data[] = $this->getArchiveDate();					
					break;
					
				case self::TYPE_ARCHIVE:
					$query .= "AND UNIX_TIMESTAMP(creation_date) < %s ";
					$types[] = 'integer';
					$data[] = $this->getArchiveDate();
					break;
			}			
		}		
		$query .= 'ORDER BY update_date DESC ';		

		$result = $this->db->queryf($query, $types, $data);
		
		while($record = $this->db->fetchAssoc($result))
		{
		   $oNewsItem = new ilShopNewsItem();
		   $oNewsItem->setId($record['news_id']);
		   $oNewsItem->setCreationDate($record['creation_date']);
		   $oNewsItem->setUpdateDate($record['update_date']);
		   $oNewsItem->setTitle($record['news_title']);
		   $oNewsItem->setContent($record['news_content']);
		   $oNewsItem->setVisibility($record['visibility']);
		   $oNewsItem->setUserId($record['user_id']);
		   
		   $this->news[] = $oNewsItem;		  	   
		}
		
		return $this;
	}
	
	public function reload()
	{
		$this->read();
		
		return $this;
	}
		
	public function setArchiveDate($a_archive_date)
	{
		$this->archive_date = $a_archive_date;
		
		return $this;
	}	
	public function getArchiveDate()
	{
		return $this->archive_date;
	}
	public function setPublicSection($a_public_section)
	{
		$this->public_section = $a_public_section;
		
		return $this;
	}	
	public function isPublicSection()
	{
		return $this->public_section;
	}
	public function setMode($a_mode)
	{
		$this->mode = $a_mode;
		
		return $this;
	}	
	public function getMode()
	{
		return $this->mode;
	}
}
?>