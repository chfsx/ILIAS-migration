<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Html/classes/class.ilHtmlPurifierAbstractLibWrapper.php';

/** 
* Concrete class for sanitizing html of forum posts
* 
* @author	Michael Jansen <mjansen@databay.de>
* @version	$Id$
* 
*/
class ilHtmlForumPostPurifier extends ilHtmlPurifierAbstractLibWrapper
{	
	/** 
	* Type of purifier
	* 
	* @var		string
	* @type		string 
	* @access	public
	* @static
	* 
	*/
	public static $_type = 'frm_post';
	
	/** 
	* Constructor
	* 
	* @access	public
	* 
	*/
	public function __construct()
	{
		parent::__construct();
	}
	
	/** 
	* Concrete function which builds a html purifier config instance
	* 
	* @access	protected
	* @return	HTMLPurifier_Config Instance of HTMLPurifier_Config
	* 
	*/
	protected function getPurifierConfigInstance()
	{
		include_once 'Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php';
		
		$config = HTMLPurifier_Config::createDefault();
		$config->set('HTML.DefinitionID', 'ilias forum post');
		$config->set('HTML.DefinitionRev', 1);
		$config->set('Cache.SerializerPath', ilHtmlPurifierAbstractLibWrapper::_getCacheDirectory());
		$config->set('HTML.Doctype', 'XHTML 1.0 Strict');		
		
		// Bugfix #5945: Necessary because TinyMCE does not use the "u" 
		// html element but <span style="text-decoration: underline">E</span>
		$tags = ilObjAdvancedEditing::_getUsedHTMLTags(self::$_type);		
		if(in_array('u', $tags) && !in_array('span', $tags)) $tags[] = 'span';
		$config->set('HTML.AllowedElements', $this->removeUnsupportedElements($tags));		
		
		$def = $config->getHTMLDefinition(true);
		$def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');		

		return $config;
	}	
}
?>