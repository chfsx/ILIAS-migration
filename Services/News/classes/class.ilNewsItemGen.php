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

define("NEWS_TEXT", "text");
define("NEWS_HTML", "html");
define("NEWS_AUDIO", "audio");
define("NEWS_USERS", "users");
define("NEWS_PUBLIC", "public");

/**
* A news item can be created by different sources. E.g. when
* a new forum posting is created, or when a change in a
* learning module is announced.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*/
class ilNewsItemGen 
{

	protected $id;
	protected $title;
	protected $content;
	protected $context_obj_id;
	protected $context_obj_type;
	protected $context_sub_obj_id;
	protected $context_sub_obj_type;
	protected $content_type = "text";
	protected $creation_date;
	protected $update_date;
	protected $user_id;
	protected $visibility = "users";
	protected $content_long;
	protected $priority = 1;
	protected $content_is_lang_var = 0;
	protected $mob_id;
	protected $playtime;

	/**
	* Constructor.
	*
	* @param	int	$a_id	
	*/
	public function __construct($a_id = 0)
	{
		if ($a_id > 0)
		{
			$this->setId($a_id);
			$this->read();
		}

	}

	/**
	* Set Id.
	*
	* @param	int	$a_id	
	*/
	public function setId($a_id)
	{
		$this->id = $a_id;
	}

	/**
	* Get Id.
	*
	* @return	int	
	*/
	public function getId()
	{
		return $this->id;
	}

	/**
	* Set Title.
	*
	* @param	string	$a_title	Title of news item.
	*/
	public function setTitle($a_title)
	{
		$this->title = $a_title;
	}

	/**
	* Get Title.
	*
	* @return	string	Title of news item.
	*/
	public function getTitle()
	{
		return $this->title;
	}

	/**
	* Set Content.
	*
	* @param	string	$a_content	Content of news.
	*/
	public function setContent($a_content)
	{
		$this->content = $a_content;
	}

	/**
	* Get Content.
	*
	* @return	string	Content of news.
	*/
	public function getContent()
	{
		return $this->content;
	}

	/**
	* Set ContextObjId.
	*
	* @param	int	$a_context_obj_id	
	*/
	public function setContextObjId($a_context_obj_id)
	{
		$this->context_obj_id = $a_context_obj_id;
	}

	/**
	* Get ContextObjId.
	*
	* @return	int	
	*/
	public function getContextObjId()
	{
		return $this->context_obj_id;
	}

	/**
	* Set ContextObjType.
	*
	* @param	int	$a_context_obj_type	
	*/
	public function setContextObjType($a_context_obj_type)
	{
		$this->context_obj_type = $a_context_obj_type;
	}

	/**
	* Get ContextObjType.
	*
	* @return	int	
	*/
	public function getContextObjType()
	{
		return $this->context_obj_type;
	}

	/**
	* Set ContextSubObjId.
	*
	* @param	int	$a_context_sub_obj_id	
	*/
	public function setContextSubObjId($a_context_sub_obj_id)
	{
		$this->context_sub_obj_id = $a_context_sub_obj_id;
	}

	/**
	* Get ContextSubObjId.
	*
	* @return	int	
	*/
	public function getContextSubObjId()
	{
		return $this->context_sub_obj_id;
	}

	/**
	* Set ContextSubObjType.
	*
	* @param	int	$a_context_sub_obj_type	
	*/
	public function setContextSubObjType($a_context_sub_obj_type)
	{
		$this->context_sub_obj_type = $a_context_sub_obj_type;
	}

	/**
	* Get ContextSubObjType.
	*
	* @return	int	
	*/
	public function getContextSubObjType()
	{
		return $this->context_sub_obj_type;
	}

	/**
	* Set ContentType.
	*
	* @param	string	$a_content_type	Content type.
	*/
	public function setContentType($a_content_type = "text")
	{
		$this->content_type = $a_content_type;
	}

	/**
	* Get ContentType.
	*
	* @return	string	Content type.
	*/
	public function getContentType()
	{
		return $this->content_type;
	}

	/**
	* Set CreationDate.
	*
	* @param	string	$a_creation_date	Date of creation.
	*/
	public function setCreationDate($a_creation_date)
	{
		$this->creation_date = $a_creation_date;
	}

	/**
	* Get CreationDate.
	*
	* @return	string	Date of creation.
	*/
	public function getCreationDate()
	{
		return $this->creation_date;
	}

	/**
	* Set UpdateDate.
	*
	* @param	string	$a_update_date	Date of last update.
	*/
	public function setUpdateDate($a_update_date)
	{
		$this->update_date = $a_update_date;
	}

	/**
	* Get UpdateDate.
	*
	* @return	string	Date of last update.
	*/
	public function getUpdateDate()
	{
		return $this->update_date;
	}

	/**
	* Set UserId.
	*
	* @param	int	$a_user_id	User Id of last update.
	*/
	public function setUserId($a_user_id)
	{
		$this->user_id = $a_user_id;
	}

	/**
	* Get UserId.
	*
	* @return	int	User Id of last update.
	*/
	public function getUserId()
	{
		return $this->user_id;
	}

	/**
	* Set Visibility.
	*
	* @param	string	$a_visibility	Access level of news.
	*/
	public function setVisibility($a_visibility = "users")
	{
		$this->visibility = $a_visibility;
	}

	/**
	* Get Visibility.
	*
	* @return	string	Access level of news.
	*/
	public function getVisibility()
	{
		return $this->visibility;
	}

	/**
	* Set ContentLong.
	*
	* @param	string	$a_content_long	Long content of news
	*/
	public function setContentLong($a_content_long)
	{
		$this->content_long = $a_content_long;
	}

	/**
	* Get ContentLong.
	*
	* @return	string	Long content of news
	*/
	public function getContentLong()
	{
		return $this->content_long;
	}

	/**
	* Set Priority.
	*
	* @param	int	$a_priority	News Priority
	*/
	public function setPriority($a_priority = 1)
	{
		$this->priority = $a_priority;
	}

	/**
	* Get Priority.
	*
	* @return	int	News Priority
	*/
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	* Set ContentIsLangVar.
	*
	* @param	boolean	$a_content_is_lang_var	
	*/
	public function setContentIsLangVar($a_content_is_lang_var = 0)
	{
		$this->content_is_lang_var = $a_content_is_lang_var;
	}

	/**
	* Get ContentIsLangVar.
	*
	* @return	boolean	
	*/
	public function getContentIsLangVar()
	{
		return $this->content_is_lang_var;
	}

	/**
	* Set MobId.
	*
	* @param	int	$a_mob_id	Media Object ID (if news includes attachement)
	*/
	public function setMobId($a_mob_id)
	{
		$this->mob_id = $a_mob_id;
	}

	/**
	* Get MobId.
	*
	* @return	int	Media Object ID (if news includes attachement)
	*/
	public function getMobId()
	{
		return $this->mob_id;
	}

	/**
	* Set Playtime.
	*
	* @param	string	$a_playtime	Play Time, hh:mm:ss (of attached media file)
	*/
	public function setPlaytime($a_playtime)
	{
		$this->playtime = $a_playtime;
	}

	/**
	* Get Playtime.
	*
	* @return	string	Play Time, hh:mm:ss (of attached media file)
	*/
	public function getPlaytime()
	{
		return $this->playtime;
	}

	/**
	* Create new item.
	*
	*/
	public function create()
	{
		global $ilDB;
		
		$this->setId($ilDB->nextId("il_news_item"));
		$ilDB->insert("il_news_item", array(
			"id" => array("integer", $this->getId()),
			"title" => array("text", $this->getTitle()),
			"content" => array("clob", $this->getContent()),
			"context_obj_id" => array("integer", (int) $this->getContextObjId()),
			"context_obj_type" => array("text", $this->getContextObjType()),
			"context_sub_obj_id" => array("integer", (int) $this->getContextSubObjId()),
			"context_sub_obj_type" => array("text", $this->getContextSubObjType()),
			"content_type" => array("text", $this->getContentType()),
			"creation_date" => array("timestamp", ilUtil::now()),
			"update_date" => array("timestamp", ilUtil::now()),
			"user_id" => array("integer", $this->getUserId()),
			"visibility" => array("text", $this->getVisibility()),
			"content_long" => array("clob", $this->getContentLong()),
			"priority" => array("integer", $this->getPriority()),
			"content_is_lang_var" => array("integer", $this->getContentIsLangVar()),
			"mob_id" => array("integer", $this->getMobId()),
			"playtime" => array("text", $this->getPlaytime())
		));
	}

	/**
	* Read item from database.
	*
	*/
	public function read()
	{
		global $ilDB;
		
		$query = "SELECT * FROM il_news_item WHERE id = ".
			$ilDB->quote($this->getId(), "integer");
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);

		$this->setTitle($rec["title"]);
		$this->setContent($rec["content"]);
		$this->setContextObjId((int) $rec["context_obj_id"]);
		$this->setContextObjType($rec["context_obj_type"]);
		$this->setContextSubObjId((int) $rec["context_sub_obj_id"]);
		$this->setContextSubObjType($rec["context_sub_obj_type"]);
		$this->setContentType($rec["content_type"]);
		$this->setCreationDate($rec["creation_date"]);
		$this->setUpdateDate($rec["update_date"]);
		$this->setUserId($rec["user_id"]);
		$this->setVisibility($rec["visibility"]);
		$this->setContentLong($rec["content_long"]);
		$this->setPriority($rec["priority"]);
		$this->setContentIsLangVar($rec["content_is_lang_var"]);
		$this->setMobId($rec["mob_id"]);
		$this->setPlaytime($rec["playtime"]);

	}

	/**
	* Update item in database.
	*
	*/
	public function update()
	{
		global $ilDB;
		
		$ilDB->update("il_news_item", array(
			"title" => array("text", $this->getTitle()),
			"content" => array("clob", $this->getContent()),
			"context_obj_id" => array("integer", $this->getContextObjId()),
			"context_obj_type" => array("text", $this->getContextObjType()),
			"context_sub_obj_id" => array("integer", $this->getContextSubObjId()),
			"context_sub_obj_type" => array("text", $this->getContextSubObjType()),
			"content_type" => array("text", $this->getContentType()),
			"update_date" => array("timestamp", ilUtil::now()),
			"user_id" => array("integer", $this->getUserId()),
			"visibility" => array("text", $this->getVisibility()),
			"content_long" => array("clob", $this->getContentLong()),
			"priority" => array("integer", $this->getPriority()),
			"content_is_lang_var" => array("integer", $this->getContentIsLangVar()),
			"mob_id" => array("integer", $this->getMobId()),
			"playtime" => array("text", $this->getPlaytime())
			), array(
			"id" => array("integer", $this->getId())
		));

	}

	/**
	* Delete item from database.
	*
	*/
	public function delete()
	{
		global $ilDB;
		
		$query = "DELETE FROM il_news_item".
			" WHERE id = ".$ilDB->quote($this->getId(), "integer");
		
		$ilDB->manipulate($query);

	}

	/**
	* Query NewsForContext
	*
	*/
	public function queryNewsForContext()
	{
		global $ilDB;
		
		$query = "SELECT id, title, content, context_obj_id, context_obj_type, context_sub_obj_id, context_sub_obj_type, content_type, creation_date, update_date, user_id, visibility, content_long, priority, content_is_lang_var, mob_id, playtime ".
			"FROM il_news_item ".
			"WHERE ".
				"context_obj_id = ".$ilDB->quote($this->getContextObjId(), "integer").
				" AND context_obj_type = ".$ilDB->quote($this->getContextObjType(), "text").
				" AND context_sub_obj_id = ".$ilDB->quote($this->getContextSubObjId(), "integer").
				" AND ".$ilDB->equals("context_sub_obj_type", $this->getContextSubObjType(), "text", true).
				" ORDER BY creation_date DESC ".
				"";
		$set = $ilDB->query($query);
		$result = array();
		while($rec = $ilDB->fetchAssoc($set))
		{
			$result[] = $rec;
		}
		
		return $result;

	}

	/**
	* Query NewsForVisibility
	*
	*/
	public function queryNewsForVisibility()
	{
		global $ilDB;
		
		$query = "SELECT id, title, content, context_obj_id, context_obj_type, context_sub_obj_id, context_sub_obj_type, content_type, creation_date, update_date, user_id, visibility, content_long, priority, content_is_lang_var, mob_id, playtime ".
			"FROM il_news_item ".
			"WHERE ".
				"context_obj_id = ".$ilDB->quote($this->getContextObjId(), "integer").
				" AND context_obj_type = ".$ilDB->quote($this->getContextObjType(), "text").
				" AND context_sub_obj_id = ".$ilDB->quote($this->getContextSubObjId(), "integer").
				" AND ".$ilDB->equals("context_sub_obj_type", $this->getContextSubObjType(), "text", true).
				" AND visibility = ".$ilDB->quote($this->getVisibility(), "text").
				" ORDER BY creation_date DESC ".
				"";
				
		$set = $ilDB->query($query);
		$result = array();
		while($rec = $ilDB->fetchAssoc($set))
		{
			$result[] = $rec;
		}
		
		return $result;

	}


}
?>
