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
* client management
*
* @author Sascha Hofmann <shofmann@databay.de> 
* @version $Id$
*
* @package ilias-setup
*/

class ilClientList
{
	var $ini;			// ini file object
	var $path;			// clients base dir
	var $error = "";	// error text
	
	function ilClientList()
	{		
		$this->path = ILIAS_ABSOLUTE_PATH."/".ILIAS_WEB_DIR;
		$this->init();
	}

	function init()
	{
		// set path to directory where clients reside
		$d = dir($this->path);
		$tmpPath = getcwd();
		chdir ($this->path);

		// get available lang-files
		while ($entry = $d->read())
		{
			if (strlen($entry) == 32)
			{
				if (is_file($this->path."/".$entry."/client.ini.php"))
				{
					$client = new ilClient($entry);
					$client->init();
					
					$this->clients[$entry] = $client;
					
					unset($client);
				}
			}
		}
		
		chdir($tmpPath);
	}
	
	function getClients()
	{
		return ($this->clients) ? $this->clients : array();
	}
}
?>