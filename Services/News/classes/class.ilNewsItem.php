<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

define("NEWS_NOTICE", 0);
define("NEWS_MESSAGE", 1);
define("NEWS_WARNING", 2);

include_once("./Services/News/classes/class.ilNewsItemGen.php");

/**
* @defgroup ServicesNews Services/News
*
* A news item can be created by different sources. E.g. when
* a new forum posting is created, or when a change in a
* learning module is announced.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ingroup ServicesNews
*/
class ilNewsItem extends ilNewsItemGen
{

	private static $privFeedId = false;
	private $limitation;
	private $ignorePeriod = false;
	
	/**
	* Constructor.
	*
	* @param	int	$a_id	
	*/
	public function __construct($a_id = 0)
	{
		parent::__construct($a_id);
		$this->limitation = true;
	}

	public function ignorePeriod($a_status = null)
	{
		if(null === $a_status)
		{
			return $this->ignorePeriod;
		}

		$this->ignorePeriod = $a_status;
		return $this;
	}
	
	/**
	* Set Limitation for number of items.
	*
	* @param	boolean	$a_limitation	Limitation for number of items
	*/
	function setLimitation($a_limitation)
	{
		$this->limitation = $a_limitation;
	}

	/**
	* Get Limitation for number of items.
	*
	* @return	boolean	Limitation for number of items
	*/
	function getLimitation()
	{
		return $this->limitation;
	}

	/**
	* Create
	*/
	function create()
	{
		global $ilDB;
		
		parent::create();
		
		$news_set = new ilSetting("news");
		$max_items = $news_set->get("max_items");
		if ($max_items <= 0)
		{
			$max_items = 50;
		}
		
		// limit number of news
		if ($this->getLimitation())
		{
			// Determine how many rows should be deleted
			$query = "SELECT count(*) cnt ".
				"FROM il_news_item ".
				"WHERE ".
					"context_obj_id = ".$ilDB->quote($this->getContextObjId(), "integer").
					" AND context_obj_type = ".$ilDB->quote($this->getContextObjType(), "text").
					" AND context_sub_obj_id = ".$ilDB->quote($this->getContextSubObjId(), "integer").
					" AND ".$ilDB->equals("context_sub_obj_type", $this->getContextSubObjType(), "text", true)." ";
	
			$set = $ilDB->query($query);
			$rec = $ilDB->fetchAssoc($set);
					
			// if we have more records than allowed, delete them
			if (($rec["cnt"] > $max_items) && $this->getContextObjId() > 0)
			{
				$query = "SELECT * ".
					"FROM il_news_item ".
					"WHERE ".
						"context_obj_id = ".$ilDB->quote($this->getContextObjId(), "integer").
						" AND context_obj_type = ".$ilDB->quote($this->getContextObjType(), "text").
						" AND context_sub_obj_id = ".$ilDB->quote($this->getContextSubObjId(), "integer").
						" AND ".$ilDB->equals("context_sub_obj_type", $this->getContextSubObjType(), "text", true).
#						" ORDER BY creation_date ASC";
						" ORDER BY start_date ASC";
	
				$ilDB->setLimit($rec["cnt"] - $max_items);
				$del_set = $ilDB->query($query);
				while ($del_item = $ilDB->fetchAssoc($del_set))
				{
					$del_news = new ilNewsItem($del_item["id"]);
					$del_news->delete();
				}
			}
		}
	}

	/**
	* Get all news items for a user.
	*/
	static function _getNewsItemsOfUser($a_user_id, $a_only_public = false,
		$a_prevent_aggregation = false, $a_per = 0, &$a_cnt = NULL)
	{
		global $ilAccess, $ilUser;
				
		$news_item = new ilNewsItem();
		$news_set = new ilSetting("news");
		
		$per = $a_per;

		include_once("./Services/News/classes/class.ilNewsSubscription.php");
		include_once("./Services/Block/classes/class.ilBlockSetting.php");
		
		// this is currently not used
		$ref_ids = ilNewsSubscription::_getSubscriptionsOfUser($a_user_id);
		
		if (ilObjUser::_lookupPref($a_user_id, "pd_items_news") != "n")
		{
			// this should be the case for all users
			$pd_items = ilObjUser::_lookupDesktopItems($a_user_id);
			foreach($pd_items as $item)
			{
				if (!in_array($item["ref_id"], $ref_ids))
				{
					$ref_ids[] = $item["ref_id"];
				}
			}
		}
		
		$data = array();

		foreach($ref_ids as $ref_id)
		{
			if (!$a_only_public)
			{
				// this loop should not cost too much performance
				$acc = $ilAccess->checkAccess("read", "", $ref_id);
				
				if (!$acc)
				{
					continue;
				}
			}
			if (ilNewsItem::getPrivateFeedId() != false) {
				global $rbacsystem;
				$acc = $rbacsystem->checkAccessOfUser(ilNewsItem::getPrivateFeedId(),"read", $ref_id);
			
				if (!$acc)
				{
					continue;
				}
			}

			$obj_id = ilObject::_lookupObjId($ref_id);
			$obj_type = ilObject::_lookupType($obj_id);
			$news = $news_item->getNewsForRefId($ref_id, $a_only_public, false,
				$per, $a_prevent_aggregation);
			
			// counter
			if (!is_null($a_cnt))
			{
				$a_cnt[$ref_id] = count($news);
			}

			$data = ilNewsItem::mergeNews($data, $news);
		}

		$data = ilUtil::sortArray($data, "start_date", "desc", false, true);

		return $data;
	}
	
	/**
	* Get News For Ref Id.
	*/
	function getNewsForRefId($a_ref_id, $a_only_public = false, $a_stopnesting = false,
		$a_time_period = 0, $a_prevent_aggregation = true, $a_forum_group_sequences = false,
		$a_no_auto_generated = false, $a_ignore_date_filter = false)
	{
		$obj_id = ilObject::_lookupObjId($a_ref_id);
		$obj_type = ilObject::_lookupType($obj_id);
		
		// get starting date
		$starting_date = '';
		$ending_date = '';
		if ($obj_type == "grp" || $obj_type == "crs" || $obj_type == "cat")
		{
			include_once("./Services/Block/classes/class.ilBlockSetting.php");
			$hide_news_per_date = ilBlockSetting::_lookup("news", "hide_news_per_date",
				0, $obj_id);

			$hide_news_until_date = ilBlockSetting::_lookup("news", "hide_news_until_date",
				0, $obj_id);

			if ($hide_news_per_date && !$a_ignore_date_filter)
			{
				$starting_date = ilBlockSetting::_lookup("news", "hide_news_date",
					0, $obj_id);
			}
			if ($hide_news_until_date)
			{
				$ending_date = ilBlockSetting::_lookup("news", "hide_news_date_end",
					0, $obj_id);
			}
		}
		if(!$a_time_period) $this->ignorePeriod(true);

		if ($obj_type == "cat" && !$a_stopnesting)
		{
			$news = $this->getAggregatedChildNewsData($a_ref_id, $a_only_public, $a_time_period,
				$a_prevent_aggregation, $starting_date,$a_no_auto_generated, $ending_date);
		}
		else if (($obj_type == "grp" || $obj_type == "crs") &&
			!$a_stopnesting)
		{
			$news = $this->getAggregatedNewsData($a_ref_id, $a_only_public, $a_time_period,
				$a_prevent_aggregation, $starting_date, $a_no_auto_generated,$ending_date);
		}
		else
		{
			$news_item = new ilNewsItem();
			$news_item->setContextObjId($obj_id);
			$news_item->setContextObjType($obj_type);
			if(!$a_time_period) $news_item->ignorePeriod(true);
			$news = $news_item->queryNewsForContext($a_only_public, $a_time_period,
				$starting_date, $a_no_auto_generated, $ending_date, $news_item->ignorePeriod());
			$unset = array();
			foreach ($news as $k => $v)
			{
				if (!$a_only_public || $v["visibility"] == NEWS_PUBLIC ||
					($v["priority"] == 0 && !$acc &&
						ilBlockSetting::_lookup("news", "public_notifications",
						0, $obj_id)))
				{
					$news[$k]["ref_id"] = $a_ref_id;
				}
				else
				{
					$unset[] = $k;
				}
			}
			foreach ($unset as $un)
			{
				unset($news[$un]);
			}
		}
		
		if (!$a_prevent_aggregation)
		{
			$news = $this->aggregateForums($news);
		}
		else if ($a_forum_group_sequences)
		{
			$news = $this->aggregateForums($news, true);
		}
		
		return $news;
	}
	
	/**
	* Get news aggregation (e.g. for courses, groups)
	*/
	function getAggregatedNewsData($a_ref_id, $a_only_public = false, $a_time_period = 0,
		$a_prevent_aggregation = false, $a_starting_date = "", $a_no_auto_generated = false, $a_ending_date = '')
	{
		global $tree, $ilAccess, $ilObjDataCache;
		
		// get news of parent object
		
		$data = array();
		
		// get subtree
		$cur_node = $tree->getNodeData($a_ref_id);

		if ($cur_node["lft"] != "")		// should never be empty
		{
			$nodes = $tree->getSubTree($cur_node, true);
		}
		else
		{
			$nodes = array();
		}
		
		// preload object data cache
		$ref_ids = array();
		$obj_ids = array();
		foreach($nodes as $node)
		{
			$ref_ids[] = $node["child"];
			$obj_ids[] = $node["obj_id"];
		}

		$ilObjDataCache->preloadReferenceCache($ref_ids);
		if (!$a_only_public)
		{
			$ilAccess->preloadActivationTimes($ref_ids);
		}
		
		// no check, for which of the objects any news are available
		$news_obj_ids = ilNewsItem::filterObjIdsPerNews($obj_ids, $a_time_period, $a_starting_date, $a_ending_date, $this->ignorePeriod());
		//$news_obj_ids = $obj_ids;
		
		// get news for all subtree nodes
		$contexts = array();
		foreach($nodes as $node)
		{
			// only go on, if news are available
			if (!in_array($node["obj_id"], $news_obj_ids))
			{
				continue;
			}
			
			if (!$a_only_public)
			{
				$acc = $ilAccess->checkAccess("read", "", $node["child"]);
				
				if (!$acc)
				{
					continue;
				}
			}
			
			$ref_id[$node["obj_id"]] = $node["child"];
			$contexts[] = array("obj_id" => $node["obj_id"],
				"obj_type" => $node["type"]);
		}
		
		// sort and return
		$news = $this->queryNewsForMultipleContexts($contexts, $a_only_public, $a_time_period,
			$a_starting_date, $a_no_auto_generated, $a_ending_date, $this->ignorePeriod());
				
		$to_del = array();
		foreach ($news as $k => $v)
		{
			$news[$k]["ref_id"] = $ref_id[$v["context_obj_id"]];
			$now = ilUtil::now();
			if(
				($news[$k]['start_date'] == NULL && $news[$k]['end_date'] == NULL)
				|| ($news[$k]['start_date'] != NULL && $news[$k]['start_date'] <= $now && $news[$k]['end_date'] == NULL )
				|| ($news[$k]['start_date'] != NULL && $news[$k]['start_date'] <= $now && $news[$k]['end_date'] >= $now)
				|| ($news[$k]['start_date'] == NULL && $news[$k]['end_date'] >= $now )
			)
			{
				$news[$k]["ref_id"] = $ref_id[$v["context_obj_id"]];
			}
		}
		
		$data = ilNewsItem::mergeNews($data, $news);
		$data = ilUtil::sortArray($data, "start_date", "desc", false, true);
		
		if (!$a_prevent_aggregation)
		{
			$data = $this->aggregateFiles($data, $a_ref_id);
		}
				
		return $data;
	}
	
	function aggregateForums($news, $a_group_posting_sequence = false)
	{
		$to_del = array();
		$forums = array();
		
		// aggregate
		foreach ($news as $k => $v)
		{
			if ($a_group_posting_sequence && $last_aggregation_forum > 0 &&
				$last_aggregation_forum != $news[$k]["context_obj_id"])
			{
				$forums[$last_aggregation_forum] = "";
			}

			if ($news[$k]["context_obj_type"] == "frm")
			{
				if ($forums[$news[$k]["context_obj_id"]] == "")
				{
					// $forums[forum_id] = news_id;
					$forums[$news[$k]["context_obj_id"]] = $k;
					$last_aggregation_forum = $news[$k]["context_obj_id"];
				}
				else
				{
					$to_del[] = $k;
				}
				
				$news[$k]["no_context_title"] = true;
				
				// aggregate every forum into it's "k" news
				$news[$forums[$news[$k]["context_obj_id"]]]["aggregation"][$k]
					= $news[$k];
				$news[$k]["agg_ref_id"]
					= $news[$k]["ref_id"];
				$news[$k]["content"] = "";
				$news[$k]["content_long"] = "";
			}
		}
		
		// delete double entries
		foreach($to_del as $k)
		{
			unset($news[$k]);
		}
//var_dump($news[14]["aggregation"]);

		
		return $news;
	}
	
	function aggregateFiles($news, $a_ref_id)
	{
		$first_file = "";
		$to_del = array();
		foreach ($news as $k => $v)
		{
			// aggregate file related news
			if ($news[$k]["context_obj_type"] == "file")
			{
				if ($first_file == "")
				{
					$first_file = $k;
				}
				else
				{
					$to_del[] = $k;
				}
				$news[$first_file]["aggregation"][$k] = $news[$k];
				$news[$first_file]["agg_ref_id"] = $a_ref_id;
				$news[$first_file]["ref_id"] = $a_ref_id;
			}
		}
		
		foreach($to_del as $v)
		{
			unset($news[$v]);
		}
		
		return $news;
	}

	
	/**
	* Get news aggregation for child objects (e.g. for categories)
	*/
	function getAggregatedChildNewsData($a_ref_id, $a_only_public = false,
		$a_time_period = 0, $a_prevent_aggregation = false, $a_starting_date = "",
		$a_no_auto_generated = false, $a_ending_date = '')
	{
		global $tree, $ilAccess;
		
		// get news of parent object
		$data = $this->getNewsForRefId($a_ref_id, $a_only_public, true, $a_time_period,
			true, false, false, $a_no_auto_generated);
		foreach ($data as $k => $v)
		{
			$data[$k]["ref_id"] = $a_ref_id;
		}

		// get childs
		$nodes = $tree->getChilds($a_ref_id);
		
		// no check, for which of the objects any news are available
		$obj_ids = array();
		foreach($nodes as $node)
		{
			$obj_ids[] = $node["obj_id"];
		}
		$news_obj_ids = ilNewsItem::filterObjIdsPerNews($obj_ids, $a_time_period, $a_starting_date, $a_ending_date, $this->ignorePeriod());
		//$news_obj_ids = $obj_ids;

		// get news for all subtree nodes
		$contexts = array();
		foreach($nodes as $node)
		{
			// only go on, if news are available
			if (!in_array($node["obj_id"], $news_obj_ids))
			{
				continue;
			}

			if (!$a_only_public && !$ilAccess->checkAccess("read", "", $node["child"]))
			{
				continue;
			}
			$ref_id[$node["obj_id"]] = $node["child"];
			$contexts[] = array("obj_id" => $node["obj_id"],
				"obj_type" => $node["type"]);
		}
		
		$news = $this->queryNewsForMultipleContexts($contexts, $a_only_public, $a_time_period,
			$a_starting_date, $a_no_auto_generated, $a_ending_date);
		foreach ($news as $k => $v)
		{
			$news[$k]["ref_id"] = $ref_id[$v["context_obj_id"]];
		}
		$data = ilNewsItem::mergeNews($data, $news);
		
		// sort and return
		$data = ilUtil::sortArray($data, "start_date", "desc", false, true);
		
		if (!$a_prevent_aggregation)
		{
			$data = $this->aggregateFiles($data, $a_ref_id);
		}
		
		return $data;
	}

	/**
	* Convenient function to set the whole context information.
	*/
	function setContext($a_obj_id, $a_obj_type, $a_sub_obj_id = 0, $a_sub_obj_type = "")
	{
		$this->setContextObjId($a_obj_id);
		$this->setContextObjType($a_obj_type);
		$this->setContextSubObjId($a_sub_obj_id);
		$this->setContextSubObjType($a_sub_obj_type);
	}

	/**
	 * Query news for a context
	 *
	 * @param	boolean		query for outgoing rss feed
	 * @param	int			time period in seconds
	 * @param	string		startind date
	 * @param	boolean		do not include auto generated news items
	 * @param	string		ending date
	 * @param	boolean		ignore_time period
	 */
	public function queryNewsForContext($a_for_rss_use = false, $a_time_period = 0,
		$a_starting_date = "", $a_no_auto_generated = false, $a_oldest_first = false, $a_ending_date = "", $ignore_period = false)
	{
		global $ilDB, $ilUser, $lng;

		$and = "";

		if(!$ignore_period)
		{
			if ($a_time_period > 0)
			{
				$limit_ts = date('Y-m-d H:i:s', time() - ($a_time_period * 24 * 60 * 60));
				#$and = " AND creation_date >= ".$ilDB->quote($limit_ts, "timestamp")." ";
				$now = ilUtil::now();
				$and .= " AND (( start_date >= ".$ilDB->quote($limit_ts, "timestamp").")
					OR start_date IS NULL )";
			}
			if ($a_starting_date != "")
			{
				$and.= " AND ((start_date <= ".$ilDB->quote($now, "timestamp")."
					AND start_date >= ".$ilDB->quote($a_starting_date, "timestamp").")
					OR start_date IS NULL)";
			}
		}
		else
		{
			if($a_starting_date != '')
			{
				$and .= " AND ((start_date <=  ".$ilDB->quote($now, "timestamp")."
						AND start_date >= ".$ilDB->quote($a_starting_date, "timestamp").")
						OR start_date IS NULL) ";
			}
			if($a_ending_date != '')
			{
				$and .= " AND ((end_date >= ".$ilDB->quote($now, "timestamp")." 
					AND end_date <= ".$ilDB->quote($a_ending_date,"timestamp").")
					OR end_date IS NULL) ";
			}
		}

/*		//org
		if ($a_time_period > 0)
		{
			$limit_ts = date('Y-m-d H:i:s', time() - ($a_time_period * 24 * 60 * 60));
			$and = " AND creation_date >= ".$ilDB->quote($limit_ts, "timestamp")." ";
		}
		
		if ($a_starting_date != "")
		{
			$and.= " AND creation_date > ".$ilDB->quote($a_starting_date, "timestamp")." ";
		}
/**/
		if ($a_no_auto_generated)
		{
			$and.= " AND priority = 1 AND content_type = ".$ilDB->quote("text", "text")." ";
		}

		// this is changed with 4.1 (news table for lm pages)
		if ($this->getContextSubObjId() > 0)
		{
			$and.= " AND context_sub_obj_id = ".$ilDB->quote($this->getContextSubObjId(), "integer").
				" AND context_sub_obj_type = ".$ilDB->quote($this->getContextSubObjType(), "text");
		}

		$ordering = ($a_oldest_first)
			? " start_date ASC, id ASC "
			: " start_date DESC, id DESC ";

		if ($a_for_rss_use && ilNewsItem::getPrivateFeedId() == false)
		{
			$query = "SELECT * ".
				"FROM il_news_item ".
				" WHERE ".
					"context_obj_id = ".$ilDB->quote($this->getContextObjId(), "integer").
					" AND context_obj_type = ".$ilDB->quote($this->getContextObjType(), "text").
					$and.
					" ORDER BY ".$ordering;
		}
		elseif (ilNewsItem::getPrivateFeedId() != false) 
		{
			$query = "SELECT il_news_item.* ".
				", il_news_read.user_id user_read ".
				"FROM il_news_item LEFT JOIN il_news_read ".
				"ON il_news_item.id = il_news_read.news_id AND ".
				" il_news_read.user_id = ".$ilDB->quote(ilNewsItem::getPrivateFeedId(), "integer").
				" WHERE ".
					"context_obj_id = ".$ilDB->quote($this->getContextObjId(), "integer").
					" AND context_obj_type = ".$ilDB->quote($this->getContextObjType(), "text").
					$and.
					" ORDER BY ".$ordering;
		}
		else
		{
			$query = "SELECT il_news_item.* ".
				", il_news_read.user_id as user_read ".
				"FROM il_news_item LEFT JOIN il_news_read ".
				"ON il_news_item.id = il_news_read.news_id AND ".
				" il_news_read.user_id = ".$ilDB->quote($ilUser->getId(), "integer").
				" WHERE ".
					"context_obj_id = ".$ilDB->quote($this->getContextObjId(), "integer").
					" AND context_obj_type = ".$ilDB->quote($this->getContextObjType(), "text").
					$and.
					" ORDER BY ".$ordering;
		}
//echo $query;
		$set = $ilDB->query($query);
		$result = array();
		while($rec = $ilDB->fetchAssoc($set))
		{
			if (!$a_for_rss_use || 	(ilNewsItem::getPrivateFeedId() != false) || ($rec["visibility"] == NEWS_PUBLIC ||
				($rec["priority"] == 0 &&
				ilBlockSetting::_lookup("news", "public_notifications",
				0, $rec["context_obj_id"]))))
			{
				$result[$rec["id"]] = $rec;
			}
		}

		return $result;

	}
	
	/**
	* Query News for multiple Contexts
	*
	* @param	array	$a_contexts		array of array("obj_id", "obj_type")
	*/
	public function queryNewsForMultipleContexts($a_contexts, $a_for_rss_use = false,
		$a_time_period = 0, $a_starting_date = "", $a_no_auto_generated = false, $a_ending_date = '', $ignore_period = false)
	{
		global $ilDB, $ilUser, $lng, $ilCtrl;

		$and = "";

		if(!$ignore_period)
		{
			if ($a_time_period > 0)
			{
				$limit_ts = date('Y-m-d H:i:s', time() - ($a_time_period * 24 * 60 * 60));
				#$and = " AND creation_date >= ".$ilDB->quote($limit_ts, "timestamp")." ";
				$now = ilUtil::now();
				$and .= " AND (( start_date >= ".$ilDB->quote($limit_ts, "timestamp").")
					OR start_date IS NULL )";
			}
			if ($a_starting_date != "")
			{
				$and.= " AND ((start_date <= ".$ilDB->quote($now, "timestamp")."
					AND start_date >= ".$ilDB->quote($a_starting_date, "timestamp").")
					OR start_date IS NULL)";
			}
		}
		else
		{
			if($a_starting_date != '')
			{
				$and .= " AND ((start_date <=  ".$ilDB->quote($now, "timestamp")."
						AND start_date >= ".$ilDB->quote($a_starting_date, "timestamp").")
						OR start_date IS NULL) ";
			}
			if($a_ending_date != '')
			{
				$and .= " AND ((end_date >= ".$ilDB->quote($now, "timestamp")."
					AND end_date <= ".$ilDB->quote($a_ending_date,"timestamp").")
					OR end_date IS NULL) ";
			}
		}


/* //org
		$and = "";
		if ($a_time_period > 0)
		{
			$limit_ts = date('Y-m-d H:i:s', time() - ($a_time_period * 24 * 60 * 60));
			$and = " AND creation_date >= ".$ilDB->quote($limit_ts, "timestamp")." ";
		}
			
		if ($a_starting_date != "")
		{
			$and.= " AND creation_date > ".$ilDB->quote($a_starting_date, "timestamp")." ";
		}
*/
		if ($a_no_auto_generated)
		{
			$and.= " AND priority = 1 AND content_type = ".$ilDB->quote("text", "text")." ";
		}
		
		$ids = array();
		$type = array();
		foreach($a_contexts as $cont)
		{
			$ids[] = $cont["obj_id"];
			$type[$cont["obj_id"]] = $cont["obj_type"];
		}
		
		if ($a_for_rss_use && ilNewsItem::getPrivateFeedId() == false)
		{
			$query = "SELECT * ".
				"FROM il_news_item ".
				" WHERE ".
					$ilDB->in("context_obj_id", $ids, false, "integer")." ".
					$and.
					" ORDER BY start_date DESC ";
		}
		elseif (ilNewsItem::getPrivateFeedId() != false) 
		{
			$query = "SELECT il_news_item.* ".
				", il_news_read.user_id as user_read ".
				"FROM il_news_item LEFT JOIN il_news_read ".
				"ON il_news_item.id = il_news_read.news_id AND ".
				" il_news_read.user_id = ".$ilDB->quote(ilNewsItem::getPrivateFeedId(), "integer").
				" WHERE ".
					$ilDB->in("context_obj_id", $ids, false, "integer")." ".
					$and.
					" ORDER BY start_date DESC ";
		}		
		else
		{
			$query = "SELECT il_news_item.* ".
				", il_news_read.user_id as user_read ".
				"FROM il_news_item LEFT JOIN il_news_read ".
				"ON il_news_item.id = il_news_read.news_id AND ".
				" il_news_read.user_id = ".$ilDB->quote($ilUser->getId(), "integer").
				" WHERE ".
					$ilDB->in("context_obj_id", $ids, false, "integer")." ".
					$and.
					" ORDER BY start_date DESC ";
		}

		$set = $ilDB->query($query);
		$result = array();
		while($rec = $ilDB->fetchAssoc($set))
		{
			if ($type[$rec["context_obj_id"]] == $rec["context_obj_type"])
			{
				if (!$a_for_rss_use || ilNewsItem::getPrivateFeedId() != false || ($rec["visibility"] == NEWS_PUBLIC ||
					($rec["priority"] == 0 &&
					ilBlockSetting::_lookup("news", "public_notifications",
					0, $rec["context_obj_id"]))))
				{
					$result[$rec["id"]] = $rec;
				}
			}
		}

		return $result;

	}


	/**
	* Set item read.
	*/
	function _setRead($a_user_id, $a_news_id)
	{
		global $ilDB, $ilAppEventHandler;
		
		$ilDB->manipulate("DELETE FROM il_news_read WHERE ".
			"user_id = ".$ilDB->quote($a_user_id, "integer").
			" AND news_id = ".$ilDB->quote($a_news_id, "integer"));
		$ilDB->manipulate("INSERT INTO il_news_read (user_id, news_id) VALUES (".
			$ilDB->quote($a_user_id, "integer").",".
			$ilDB->quote($a_news_id, "integer").")");

		$ilAppEventHandler->raise("Services/News", "readNews",
			array("user_id" => $a_user_id, "news_ids" => array($a_news_id)));
	}
	
	/**
	* Set item unread.
	*/
	function _setUnread($a_user_id, $a_news_id)
	{
		global $ilDB, $ilAppEventHandler;
		
		$ilDB->manipulate("DELETE FROM il_news_read (user_id, news_id) VALUES (".
			" WHERE user_id = ".$ilDB->quote($a_user_id, "integer").
			" AND news_id = ".$ilDB->quote($a_news_id, "integer"));

		$ilAppEventHandler->raise("Services/News", "unreadNews",
			array("user_id" => $a_user_id, "news_ids" => array($a_news_id)));
	}
	
	/**
	* Merges two sets of news
	*
	* @param	array	$n1		Array of news
	* @param	array	$n2		Array of news
	*
	* @return	array			Array of news
	*/
	function mergeNews($n1, $n2)
	{
		foreach($n2 as $id => $news)
		{
			$n1[$id] = $news;
		}
		
		return $n1;
	}
	
	/**
	* Get default visibility for reference id
	*
	* @param	$a_ref_id		reference id
	*/
	static function _getDefaultVisibilityForRefId($a_ref_id)
	{
		global $tree, $ilSetting;

		include_once("./Services/Block/classes/class.ilBlockSetting.php");

		$news_set = new ilSetting("news");
		$default_visibility = ($news_set->get("default_visibility") != "")
				? $news_set->get("default_visibility")
				: "users";

		if ($tree->isInTree($a_ref_id))
		{
			$path = $tree->getPathFull($a_ref_id);
			
			foreach ($path as $key => $row)
			{
				if (!in_array($row["type"], array("root", "cat","crs", "fold", "grp", "icrs")))
				{
					continue;
				}

				$visibility = ilBlockSetting::_lookup("news", "default_visibility",
					0, $row["obj_id"]);
					
				if ($visibility != "")
				{
					$default_visibility = $visibility;
				}
			}
		}
		
		return $default_visibility;
	}
	
	
	/**
	* Delete news item
	*
	*/
	public function delete()
	{
		global $ilDB;
		
		// delete il_news_read entries
		$ilDB->manipulate("DELETE FROM il_news_read ".
			" WHERE news_id = ".$ilDB->quote($this->getId(), "integer"));
		
		// delete multimedia object
		$mob = $this->getMobId();
		
		// delete 
		parent::delete();
		
		// delete mob after news, to have a "mob usage" of 0
		if ($mob > 0 and ilObject::_exists($mob))
		{
			include_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
			$mob = new ilObjMediaObject($mob);
			$mob->delete();
		}
	}
	
	/**
	* Delete all news of a context
	*
	*/
	static public function deleteNewsOfContext($a_context_obj_id,
		$a_context_obj_type, $a_context_sub_obj_id = 0, $a_context_sub_obj_type = "")
	{
		global $ilDB;
		
		if ($a_context_obj_id == 0 || $a_context_obj_type == "")
		{
			return;
		}

		if ($a_context_sub_obj_id > 0)
		{
			$and = " AND context_sub_obj_id = ".$ilDB->quote($a_context_sub_obj_id, "integer").
				" AND context_sub_obj_type = ".$ilDB->quote($a_context_sub_obj_type, "text");
		}
		
		// get news records
		$query = "SELECT * FROM il_news_item".
			" WHERE context_obj_id = ".$ilDB->quote($a_context_obj_id, "integer").
			" AND context_obj_type = ".$ilDB->quote($a_context_obj_type, "text").
			$and;

		$news_set = $ilDB->query($query);
		
		while ($news = $ilDB->fetchAssoc($news_set))
		{
			$news_obj = new ilNewsItem($news["id"]);
			$news_obj->delete();
		}
	}

	/**
	* Lookup News Title
	*/
	static function _lookupTitle($a_news_id)
	{
		global $ilDB;
		
		$query = "SELECT title FROM il_news_item WHERE id = ".
			$ilDB->quote($a_news_id, "integer");
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);
		return $rec["title"];
	}

	/**
	* Lookup News Visibility
	*/
	static function _lookupVisibility($a_news_id)
	{
		global $ilDB;
		
		$query = "SELECT visibility FROM il_news_item WHERE id = ".
			$ilDB->quote($a_news_id, "integer");
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);

		return $rec["visibility"];
	}

	/**
	* Lookup mob id
	*/
	static function _lookupMobId($a_news_id)
	{
		global $ilDB;

		$query = "SELECT mob_id FROM il_news_item WHERE id = ".
			$ilDB->quote($a_news_id, "integer");
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);
		return $rec["mob_id"];
	}

	/**
	* Checks whether news are available for
	*/
	static function filterObjIdsPerNews($a_obj_ids, $a_time_period = 0, $a_starting_date = "",$a_ending_date = '', $ignore_period = false)
	{
		global $ilDB;

		$and = "";

		if(!$ignore_period)
		{
			if ($a_time_period > 0)
			{
				$limit_ts = date('Y-m-d H:i:s', time() - ($a_time_period * 24 * 60 * 60));
				#$and = " AND creation_date >= ".$ilDB->quote($limit_ts, "timestamp")." ";
				$now = ilUtil::now();
				$and .= " AND (( start_date >= ".$ilDB->quote($limit_ts, "timestamp").")
					OR start_date IS NULL )";
			}
			if ($a_starting_date != "")
			{
				$and.= " AND ((start_date <= ".$ilDB->quote($now, "timestamp")."
					AND start_date >= ".$ilDB->quote($a_starting_date, "timestamp").")
					OR start_date IS NULL)";
			}
		}
		else
		{
			if($a_starting_date != '')
			{
				$and .= " AND ((start_date <=  ".$ilDB->quote($now, "timestamp")."
						AND start_date >= ".$ilDB->quote($a_starting_date, "timestamp").")
						OR start_date IS NULL) ";
			}
			if($a_ending_date != '')
			{
				$and .= " AND ((end_date >= ".$ilDB->quote($now, "timestamp")."
					AND end_date <= ".$ilDB->quote($a_ending_date,"timestamp").")
					OR end_date IS NULL) ";
			}
		}

/* // org
		$and = "";
		if ($a_time_period > 0)
		{
			$limit_ts = date('Y-m-d H:i:s', time() - ($a_time_period * 24 * 60 * 60));
			$and = " AND creation_date >= ".$ilDB->quote($limit_ts, "timestamp")." ";
		}
		if ($a_starting_date != "")
		{
			$and.= " AND creation_date >= ".$ilDB->quote($a_starting_date, "timestamp");
		}
*/
		$query = "SELECT DISTINCT(context_obj_id) AS obj_id FROM il_news_item".
			" WHERE ".$ilDB->in("context_obj_id", $a_obj_ids, false, "integer")." ".$and;
			//" WHERE context_obj_id IN (".implode(ilUtil::quoteArray($a_obj_ids),",").")".$and;
// +	" WHERE context_obj_id IN (".implode(",",ilUtil::quoteArray($a_obj_ids)).")".$and;
		$set = $ilDB->query($query);
		$objs = array();
		while($rec = $ilDB->fetchAssoc($set))
		{
			$objs[] = $rec["obj_id"];
		}

		return $objs;
	}
	
	/**
	* Determine title for news item entry
	*/
	static function determineNewsTitle($a_context_obj_type, $a_title, $a_content_is_lang_var,
		$a_agg_ref_id = 0, $a_aggregation = "")
	{
		global $lng;

		if ($a_agg_ref_id > 0)
		{
			$cnt = count($a_aggregation);
			
			// forums
			if ($a_context_obj_type == "frm")
			{
				if ($cnt > 1)
				{
					return sprintf($lng->txt("news_x_postings"), $cnt);
				}
				else
				{
					return $lng->txt("news_1_postings");
				}
			}
			else	// files
			{
				$up_cnt = $cr_cnt = 0;
				foreach($a_aggregation as $item)
				{
					if ($item["title"] == "file_updated")
					{
						$up_cnt++;
					}
					else
					{
						$cr_cnt++;
					}
				}
				$sep = "";
				if ($cr_cnt == 1)
				{
					$tit = $lng->txt("news_1_file_created");
					$sep = "<br />";
				}
				else if ($cr_cnt > 1)
				{
					$tit = sprintf($lng->txt("news_x_files_created"), $cr_cnt);
					$sep = "<br />";
				}
				if ($up_cnt == 1)
				{
					$tit .= $sep.$lng->txt("news_1_file_updated");
				}
				else if ($up_cnt > 1)
				{
					$tit .= $sep.sprintf($lng->txt("news_x_files_updated"), $up_cnt);
				}
				return $tit;
			}
		}
		else
		{
			if ($a_content_is_lang_var)
			{
				return $lng->txt($a_title);
			}
			else
			{
				return $a_title;
			}
		}
		
		return "";
	}
	
	
	/**
	* Get first new id of news set related to a certain context
	*/
	static function getFirstNewsIdForContext($a_context_obj_id,
		$a_context_obj_type, $a_context_sub_obj_id = "", $a_context_sub_obj_type = "")
	{
		global $ilDB;
		
		// Determine how many rows should be deleted
		$query = "SELECT * ".
			"FROM il_news_item ".
			"WHERE ".
				"context_obj_id = ".$ilDB->quote($a_context_obj_id, "integer").
				" AND context_obj_type = ".$ilDB->quote($a_context_obj_type, "text").
				" AND context_sub_obj_id = ".$ilDB->quote($a_context_sub_obj_id, "integer").
				" AND ".$ilDB->equals("context_sub_obj_type", $a_context_sub_obj_type, "text", true);
				
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);
		
		return $rec["id"];
	}
	
	/**
	* Lookup media object usage(s)
	*/
	static function _lookupMediaObjectUsages($a_mob_id)
	{
		global $ilDB;
		
		$query = "SELECT * ".
			"FROM il_news_item ".
			"WHERE ".
				" mob_id = ".$ilDB->quote($a_mob_id, "integer");
				
		$usages = array();
		$set = $ilDB->query($query);
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$usages[$rec["id"]] = array("type" => "news", "id" => $rec["id"]);
		}
		
		return $usages;
	}

	/**
	* Context Object ID
	*/
	static function _lookupContextObjId($a_news_id)
	{
		global $ilDB;
		
		$query = "SELECT * ".
			"FROM il_news_item ".
			"WHERE ".
				" id = ".$ilDB->quote($a_news_id, "integer");
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);
		
		return $rec["context_obj_id"];
	}

	function _lookupDefaultPDPeriod()
	{
		$news_set = new ilSetting("news");
		$per = $news_set->get("pd_period");
		if ($per == 0)
		{
			$per = 30;
		}
		
		return $per;
	}
	
	function _lookupUserPDPeriod($a_user_id)
	{
		global $ilSetting;
		
		$news_set = new ilSetting("news");
		$allow_shorter_periods = $news_set->get("allow_shorter_periods");
		$allow_longer_periods = $news_set->get("allow_longer_periods");
		$default_per = ilNewsItem::_lookupDefaultPDPeriod();
		
		include_once("./Services/Block/classes/class.ilBlockSetting.php");
		$per = ilBlockSetting::_lookup("pdnews", "news_pd_period",
			$a_user_id, 0);

		// news period information
		if ($per <= 0 ||
			(!$allow_shorter_periods && ($per < $default_per)) ||
			(!$allow_longer_periods && ($per > $default_per))
			)
		{
			$per = $default_per;
		}
		
		return $per;
	}
	
	function _lookupRSSPeriod()
	{
		$news_set = new ilSetting("news");
		$rss_period = $news_set->get("rss_period");
		if ($rss_period == 0)		// default to two weeks
		{
			$rss_period = 14;
		}
		return $rss_period;
	}
	function setPrivateFeedId ($a_userId) 
	{
		ilNewsItem::$privFeedId = $a_userId;
	}

	function getPrivateFeedId () {

		return ilNewsItem::$privFeedId;
	}
}
?>
