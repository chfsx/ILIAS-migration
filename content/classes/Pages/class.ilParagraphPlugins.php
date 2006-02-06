<?php
/**
 * class which contains all registered plugins
 */


include_once("./classes/class.ilDOMXML.php");
include_once("./content/classes/Pages/class.ilParagraphPlugin.php");

class ilParagraphPlugins {
	/**
	 * array which contains an instance of each plugin
	 * keys are equal to the serialization string of a plugin
	 */
	var $plugins;
	
	/**
	 * contains the plugins directory
	 */
	var $pluginDirectory;
	/**
	 * array which contains all directories which should not be parsed 
	 * within the plugins directory, by default the sub directories
	 * resources, CVS and classes are skipped
	 */
	var $skipDirectories;
	
	/**
	 * constructor initializes skip Directories
	 */
	function ilParagraphPlugins () {
		$this->plugins = array();
		$this->pluginDirectory = ILIAS_ABSOLUTE_PATH."/content/plugins";
		$this->skipDirectories = array ();
		$this->skipDirectories [$this->pluginDirectory."/classes"] = "skip"; 
		$this->skipDirectories [$this->pluginDirectory."/resources"]= "skip";
		$this->skipDirectories [$this->pluginDirectory."/CVS"]= "skip";
	}
	
	/**
	 * getPluginArray
	 */
	function getRegisteredPluginsAsArray () {
		return $this->plugins;		
	}
	
	
	/**
	 * get a specific plugin by its directory name (since directories are unique)
	 *
	 * @param ilParagraphPlugin $pluginDir
	 */
	function getParagraphPlugin ($pluginDir) {
	    foreach ($this->plugins as $plugin) {
	        if (strcasecmp($pluginDir, $plugin->getDirectory()) == 0) 
	           return $plugin;
	    }
	    return null;
	}
	
	/**
	 * register plugin
	 */
	function registerPlugin ($plugin) {
		//echo "registered Plugin ".$plugin->getTitle();
		$this->plugins[$plugin->serializeToString()] = $plugin;
	}
	
	/**
	 * serializes all plugin to one string
	 * format filetype#title#link#image|filetype#title#link#image|...
	 */
	function serializeToString (){
		return implode ("|", array_keys($this->plugins));		
	}
	
	/**
	 * parses plugin subdirectory to determine registered plugins
	 */
	function initialize () {		
		if (file_exists($this->pluginDirectory)) 
		{
		    $pluginDirs = glob($this->pluginDirectory."/*",GLOB_ONLYDIR);
		    if (is_array($pluginDirs))
		    {
    			foreach ($pluginDirs as $pluginDir) {
    			    // if there is no plugin xml file, or we are in a skipping directory then continue loop
    				if (array_key_exists($pluginDir,$this->skipDirectories) || !file_exists($pluginDir."/plugin.xml")) {
    					continue;
    				}
                    
    				
    				// load plugin xml, to retrieve plugin node (see dtd)
    				$pluginDOM = new ilDOMXML();
    				$pluginDOM->loadDocument("plugin.xml", $pluginDir, false);
    				$pluginNodes = $pluginDOM->getElementsByTagname("plugin");
    				
    				if ( count ($pluginNodes)>0 ) 
    				{
    				    // found plugin node
    				    $pluginNode = $pluginNodes[0];
    				    //print_r($pluginNode) ;
        				//this is the subdirectory of the plugin
    				    $pluginSubDir = str_replace($this->pluginDirectory."/","",$pluginDir);
    
        				// class file containing class which inherits from paragraph plugin
    				    $classfile = $pluginNode->get_attribute ("classfile");
    				    // according classname
        				$classname = $pluginNode->get_attribute ("classname");
        				
        				// filter filetype, refers to sourcecode directory, hfile, e.g. java122 affects, that this plugin is for this paragraph type only
        				$filetype  = $pluginNode->get_attribute ("filetype");
        				
        				// enable/disable plugin
        				$active    = strcasecmp($pluginNode->get_attribute ("active"),"true") == 0;
        
        				// title is alt text for image
        				$title       = $this->getTextContent($pluginNode,"title");
        				
        				// link    				
        				$link        = $this->getTextContent($pluginNode,"link");
        				
        				// description, not shown at the momemnt
        				$description = $this->getTextContent($pluginNode,"description");
                        
        				
        				// prepare class file for include, must reside in classes directory
        				$classfile = $pluginDir . "/classes/".$classfile;
        				
       /* 				echo $classfile."<br>";
        				echo $classname."<br>";
        				echo $filetype."<br>";
        				echo $active."<br>";
        				echo $title."<br>";
        				echo $description."<br>";
       */ 				
        				if (file_exists($classfile) && $active == TRUE && !class_exists($classname)) {
        					include ($classfile);
        					$plugin = new $classname($pluginSubDir, $title, $filetype, $link, $description, $active);
        					
        					//print_r($plugin);
        					
        					if (is_a($plugin,"ilParagraphPlugin") && $plugin->isActive()) {
        						$this->registerPlugin($plugin);
        						unset ($plugin);
        					}
        				}
    				    
    				}	
    			}
			}	
		}
	}
	
	
	/**
	 * get Text content from a child node (unique by name), otherwise first will be taken.
	 *
	 * @param DOM_ELEMENT $a_element
	 * @param String $nodename
	 * @return String
	 */
	function getTextContent ($a_element, $nodename) {
	    $elems = $a_element -> get_elements_by_tagname ( $nodename );
	    if (count ($elems) > 0)
	    {
	        return $elems[0]->get_content();
	    }
        return "";
	}
}
?>
