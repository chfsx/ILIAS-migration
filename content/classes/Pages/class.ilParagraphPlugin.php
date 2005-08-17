<?php
/**
 * This class describes plugin
 * 
 */
 
 class ilParagraphPlugin {
 	/**
 	 * all plugin properties are stored in a associative array
 	 * to be processed very easy
 	 * Properties: filetype, title, link and image 
 	 */
	var $properties;
	
	/*
	 * the directory we are plugin resides within the plugins directory
	 */
	var $directory;
	
	/**
	 * switch, which activates the plugin, defaults to false
	 */
	var $active;
	
	/**
	 * constructor creates a ParagraphPlugin 
	 */
	function ilParagraphPlugin ($directory, $title, $filetype, $link) {
		$this->directory = $directory;
		$this->properties = array ("filetype" => "", "title" => "", "link" => "");
		$this->setTitle($title);
		$this->setFileType($filetype);
		$this->setLink ($link);
		$this->active = false;		
	}
	
	/**
	 * returns a string representation used to active a plugin in page.xsl
	 * all properties separatad by #
	 */
	function serializeToString (){		
		return implode("#",$this->properties);
	}
	
	
	/** 
	 * set title of plugin used within alt tag of image
	 * replaces |,# sign with _
	 */
	function setTitle ($title) {
		$title = str_replace (array("|","#"), array ("_","_"),$title);			
		$this->properties["title"] = $title;
	}
	
	/** 
	 * set link of plugin relativ to plugin url
	 * replaces |,# sign with _
	 */
	function setLink ($link) {
		$link = str_replace (array("|","#"), array ("_","_"),$link);
		$this->properties["link"] = $this->getPluginURL()."/".$link;
	}
	
	/** 
	 * set image link relative to plugin url
	 * replaces |,# sign with _
	 */
	function setImage ($image) {
		$image = str_replace (array("|","#"), array ("_","_"),$image);
		$this->properties["image"] = $this->getResourceURL()."/".$image;
	}
	
	/** 
	 * set filetype of plugin to determine for which paragraph it will be activated
	 * replaces |,# sign with _
	 */
	function setFileType ($filetype) {
		$filetype = str_replace (array("|","#"), array ("_","_"),$filetype);
		$this->properties["filetype"] = $filetype;
	}	
	
	/**
	 * return title
	 */
	
	function getTitle () {
		return $this->properties["title"];
	}
		
	/**
	 * return plugin directory
	 */
	function getPluginDir () {
		return ILIAS_ABSOLUTE_PATH."/content/plugins"."/".$this->directory;
	}
	
	/**
	 * return template directory
	 */
	function getTemplateDir () {
		return $this->getPluginDir()."/templates";	
	}
	
	/**
	 * return class directory
	 */
	function getClassDir () {
		return $this->getPluginDir()."/classes";	
	}
	
	/**
	 * return resource directory
	 */
	
	function getResourceDir () {
		return $this->getPluginDir()."/resources";	
	}
	
	/**
	 * return resource url
	 */
	
	function getResourceURL () {
		return ILIAS_HTTP_PATH."/content/plugins/".$this->directory."/resources";	
	}
	
	/**
	 * return plugin url
	 */
	function getPluginURL () {
		return ILIAS_HTTP_PATH."/content/plugins/".$this->directory;	
	}
	
	/**
	 * return true if plugin is active
	 */
	function isActive() {
		return $this->active;
	}
	
	/**
	 * sets active to value bool
	 */
	function setActive ($bool) {
		$this->active = ($bool)?true:false;
	}
}
 
?>
