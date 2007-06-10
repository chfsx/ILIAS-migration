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

define("MAGPIE_DIR", "./Services/Feeds/magpierss/");
define("MAGPIE_CACHE_ON", true);
define("MAGPIE_CACHE_DIR", "./".ILIAS_WEB_DIR."/".CLIENT_ID."/magpie_cache");
define('MAGPIE_OUTPUT_ENCODING', "UTF-8");
define('MAGPIE_CACHE_AGE', 900);			// 900 seconds = 15 minutes
include_once(MAGPIE_DIR."/rss_fetch.inc");

include_once("./Services/Feeds/classes/class.ilExternalFeedItem.php");

/**
* Handles external Feeds via Magpie libaray.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
* @ingroup ServicesFeeds
*/
class ilExternalFeed
{
	protected $items = array();
	
	/**
	* Constructor
	*/
	function ilExternalFeed()
	{
		$this->createCacheDirectory();
	}

	/**
	* Set Url.
	*
	* @param	string	$a_url	Url
	*/
	function setUrl($a_url)
	{
		$this->url = $a_url;
	}

	/**
	* Get Url.
	*
	* @return	string	Url
	*/
	function getUrl()
	{
		return $this->url;
	}

	/**
	* Set Error.
	*
	* @param	string	$a_error	Error
	*/
	function setError($a_error)
	{
		$this->error = $a_error;
	}

	/**
	* Get Error.
	*
	* @return	string	Error
	*/
	function getError()
	{
		return $this->error;
	}

	/**
	* Create magpie cache directorry (if not existing)
	*/
	function createCacheDirectory()
	{
		if (!is_dir(ilUtil::getWebspaceDir()."/magpie_cache"))
		{
			ilUtil::makeDir(ilUtil::getWebspaceDir()."/magpie_cache");
		}
		
//echo "<br/>./".ILIAS_WEB_DIR."/".CLIENT_ID."/magpie_cache";
//echo "<br>".ilUtil::getWebspaceDir()."/magpie_cache";

	}
	
	/**
	* Check Url
	*
	* @param	string		URL
	* @return	mixed		true, if everything is fine, error string otherwise
	*/
	static function _checkUrl($a_url)
	{
		$feed = @fetch_rss($a_url);
		if (!$feed)
		{
			$error = magpie_error();
			
			if ($error != "")
			{
				return $error;
			}
			else
			{
				return "Unknown Error.";
			}
		}
		
		return true;
	}
	
	/**
	* Fetch the feed
	*/
	function fetch()
	{
		if ($this->getUrl() != "")
		{
			$this->feed = @fetch_rss($this->getUrl());
		}
		
		if(!$this->feed)
		{
			$error = magpie_error();
			if ($error == "")
			{
				$this->setError("Unknown Error.");
			}
			else
			{
				$this->setError(magpie_error());
			}
			return false;
		}
		
		if (is_array($this->feed->items))
		{
			foreach($this->feed->items as $item)
			{
				$item_obj = new ilExternalFeedItem();
				$item_obj->setMagpieItem($item);
				$this->items[] = $item_obj;
			}
		}
	}
	
	/**
	* Check cache hit
	*/
	function checkCacheHit()
	{
		$cache = new RSSCache( MAGPIE_CACHE_DIR, MAGPIE_CACHE_AGE );
        
        $cache_status    = 0;       // response of check_cache
        $request_headers = array(); // HTTP headers to send with fetch
        $rss             = 0;       // parsed RSS object
        $errormsg        = 0;       // errors, if any
        
        $cache_key       = $this->getUrl().MAGPIE_OUTPUT_ENCODING;
        
        if (!$cache->ERROR) {
            // return cache HIT, MISS, or STALE
            $cache_status = $cache->check_cache( $cache_key);
        }
                
        // if object cached, and cache is fresh, return cached obj
        if ($cache_status == 'HIT')
		{
			return true;
		}
		
		return false;
	}
	
	/**
	* Get Channel
	*/
	function getChannelTitle()
	{
		return $this->feed->channel["title"];
	}

	/**
	* Get Description
	*/
	function getChannelDescription()
	{
		return $this->feed->channel["description"];
	}

	/**
	* Get Items
	*/
	function getItems()
	{
		return $this->items;
	}
	
	/**
	* Determine Feed Url
	*
	* @param	$a_url	URL that 
	*/
	static function _determineFeedUrl($a_url)
	{
		$res = @fopen($a_url, "r");
		
		if (!$res)
		{
			return "";
		}
		
		$contents = '';
		while (!feof($res))
		{
			$contents.= fread($res, 8192);
		}
		fclose($res);
		
		return ilExternalFeed::_getRSSLocation($contents, $a_url);
	}
	
	/**
	* This one is by Keith Devens
	*, see http://keithdevens.com/weblog/archive/2002/Jun/03/RSSAuto-DiscoveryPHP
	*/
	function _getRSSLocation($html, $location)
	{
		if(!$html or !$location){
			return false;
		}else{
			#search through the HTML, save all <link> tags
			# and store each link's attributes in an associative array
			preg_match_all('/<link\s+(.*?)\s*\/?>/si', $html, $matches);
			$links = $matches[1];
			$final_links = array();
			$link_count = count($links);
			for($n=0; $n<$link_count; $n++){
				$attributes = preg_split('/\s+/s', $links[$n]);
				foreach($attributes as $attribute){
					$att = preg_split('/\s*=\s*/s', $attribute, 2);
					if(isset($att[1])){
						$att[1] = preg_replace('/([\'"]?)(.*)\1/', '$2', $att[1]);
						$final_link[strtolower($att[0])] = $att[1];
					}
				}
				$final_links[$n] = $final_link;
			}
			#now figure out which one points to the RSS file
			for($n=0; $n<$link_count; $n++){
				if(strtolower($final_links[$n]['rel']) == 'alternate'){
					if(strtolower($final_links[$n]['type']) == 'application/rss+xml'){
						$href = $final_links[$n]['href'];
					}
					if(!$href and strtolower($final_links[$n]['type']) == 'text/xml'){
						#kludge to make the first version of this still work
						$href = $final_links[$n]['href'];
					}
					if($href){
						if(strstr($href, "http://") !== false){ #if it's absolute
							$full_url = $href;
						}else{ #otherwise, 'absolutize' it
							$url_parts = parse_url($location);
							#only made it work for http:// links. Any problem with this?
							$full_url = "http://$url_parts[host]";
							if(isset($url_parts['port'])){
								$full_url .= ":$url_parts[port]";
							}
							if($href{0} != '/'){ #it's a relative link on the domain
								$full_url .= dirname($url_parts['path']);
								if(substr($full_url, -1) != '/'){
									#if the last character isn't a '/', add it
									$full_url .= '/';
								}
							}
							$full_url .= $href;
						}
						return $full_url;
					}
				}
			}
			return false;
		}
	}
}
?>
