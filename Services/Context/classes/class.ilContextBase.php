<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/** 
 * Service context base class
 * 
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 * 
 * @ingroup ServicesContext
 */
abstract class ilContextBase
{			
	/**
	 * Are redirects supported?
	 * 
	 * @return bool 
	 */
	abstract public static function supportsRedirects();
	
	/**
	 * Based on user authentication?
	 *  
	 * @return bool
	 */
	abstract public static function hasUser();
}

?>