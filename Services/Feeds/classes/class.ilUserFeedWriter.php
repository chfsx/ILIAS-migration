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

include_once("./Services/News/classes/class.ilNewsItem.php");
include_once("./Services/Feeds/classes/class.ilFeedItem.php");
include_once("./Services/Feeds/classes/class.ilFeedWriter.php");

/** @defgroup ServicesFeeds Services/Feeds
 */

/**
* Feed writer for personal user feeds.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
* @ingroup ServicesFeeds
*/
class ilUserFeedWriter extends ilFeedWriter
{
	function ilUserFeedWriter($a_user_id, $a_hash)
	{
		parent::ilFeedWriter();
		
		if ($a_user_id == "" || $a_hash == "")
		{
			return;
		}
		
		$news_set = new ilSetting("news");
		if (!$news_set->get("enable_rss_for_internal"))
		{
			return;
		}

		$hash = ilObjUser::_lookupFeedHash($a_user_id);

		if ($a_hash == $hash)
		{
			$items = ilNewsItem::_getNewsItemsOfUser($a_user_id, true);
			$this->setChannelTitle("ILIAS Channel Title");
			$this->setChannelAbout(ILIAS_HTTP_PATH);
			$this->setChannelLink(ILIAS_HTTP_PATH);
			$this->setChannelDescription("ILIAS Channel Description");
			$i = 0;
			foreach($items as $item)
			{
				$i++;
				$feed_item = new ilFeedItem();
				$feed_item->setTitle($this->prepareStr($item["title"]));
				$feed_item->setDescription($this->prepareStr($item["content"]));
				$feed_item->setLink(ILIAS_HTTP_PATH."/goto.php?client_id=".CLIENT_ID.
					"&amp;target=".$item["context_obj_type"]."_".$item["ref_id"]);
				$feed_item->setAbout(ILIAS_HTTP_PATH."/feed".$item["id"]);
				$this->addItem($feed_item);
			}
		}
	}
}
?>
