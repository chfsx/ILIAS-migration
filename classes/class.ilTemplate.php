<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
* special template class to simplify handling of ITX/PEAR
* @author	Stefan Kesseler <skesseler@databay.de>
* @author	Sascha Hofmann <shofmann@databay.de>
* @version	$Id$
*/
class ilTemplate extends ilTemplateX
{
	/**
	* Content-type for template output
	* @var	string
	*/
	var $contenttype;
	/**
	* variablen die immer in jedem block ersetzt werden sollen
	* @var	array
	*/
	var $vars;

	/**
	* Aktueller Block
	* Der wird gemerkt bei der berladenen Funktion setCurrentBlock, damit beim ParseBlock
	* vorher ein replace auf alle Variablen gemacht werden kann, die mit dem BLockname anfangen.
	* @var	string
	*/
	var $activeBlock;
	
	var $js_files = array();		// list of JS files that should be included
	var $js_files_vp = array();	// version parameter flag
	var $js_files_batch = array();	// version parameter flag
	var $css_files = array();		// list of css files that should be included
	var $inline_css = array();
	var $admin_panel_commands = array();
	
	private $addFooter; // creates an output of the ILIAS footer
	
	protected static $il_cache = array();
	protected $message = "";
	
	protected $title_desc = "";
	protected $upper_icon = "";
	protected $tree_flat_link = "";
	protected $mount_webfolder = "";
	protected $stop_floating = "";
	protected $page_form_action = "";
	protected $page_actions = array();
	protected $creation_selector = false;
	protected $permanent_link = false;
	protected $adv_layout = false;
	protected $content_style_sheet = "";

	/**
	* constructor
	* @param	string	$file 		templatefile (mit oder ohne pfad)
	* @param	boolean	$flag1 		remove unknown variables
	* @param	boolean	$flag2 		remove empty blocks
	* @param	boolean	$in_module	should be set to true, if template file is in module subdirectory
	* @param	array	$vars 		variables to replace
	* @access	public
	*/
	function ilTemplate($file,$flag1,$flag2,$in_module = false, $vars = "DEFAULT",
		$plugin = false, $a_use_cache = false)
	{
		global $ilias;
//echo "<br>-".$file."-";

		$this->activeBlock = "__global__";
		$this->vars = array();
		$this->addFooter = TRUE;
		
		$this->il_use_cache = $a_use_cache;
		$this->il_cur_key = $file."/".$in_module;

		$fname = $this->getTemplatePath($file, $in_module, $plugin);

		$this->tplName = basename($fname);
		$this->tplPath = dirname($fname);
		// set default content-type to text/html
		$this->contenttype = "text/html";
		if (!file_exists($fname))
		{
			$ilias->raiseError("template ".$fname." was not found.", $ilias->error_obj->FATAL);
			return false;
		}

		//$this->IntegratedTemplateExtension(dirname($fname));
		$this->callConstructor();
		//$this->loadTemplatefile(basename($fname), $flag1, $flag2);
		$this->loadTemplatefile($fname, $flag1, $flag2);
		//add tplPath to replacevars
		$this->vars["TPLPATH"] = $this->tplPath;
		
		// set Options
		if (method_exists($this, "setOption"))
		{
			$this->setOption('use_preg', false);
		}
		$this->setBodyClass("std");
		
		return true;
	}
	
	// overwrite their init function
    function init()
    {
        $this->free();
        $this->buildFunctionlist();
        
        $cache_hit = false;
        if ($this->il_use_cache)
        {
        	// cache hit
        	if (isset(self::$il_cache[$this->il_cur_key]) && is_array(self::$il_cache[$this->il_cur_key]))
        	{
        		$cache_hit = true;
//echo "cache hit";
        		$this->err = self::$il_cache[$this->il_cur_key]["err"];
        		$this->flagBlocktrouble = self::$il_cache[$this->il_cur_key]["flagBlocktrouble"];
        		$this->blocklist = self::$il_cache[$this->il_cur_key]["blocklist"];
        		$this->blockdata = self::$il_cache[$this->il_cur_key]["blockdata"];
        		$this->blockinner = self::$il_cache[$this->il_cur_key]["blockinner"];
        		$this->blockparents = self::$il_cache[$this->il_cur_key]["blockparents"];
        		$this->blockvariables = self::$il_cache[$this->il_cur_key]["blockvariables"];
        	}
        }
        
		if (!$cache_hit)
		{
			$this->findBlocks($this->template);
			$this->template = '';
			$this->buildBlockvariablelist();
	        if ($this->il_use_cache)
	        {
        		self::$il_cache[$this->il_cur_key]["err"] = $this->err;
        		self::$il_cache[$this->il_cur_key]["flagBlocktrouble"] = $this->flagBlocktrouble;
        		self::$il_cache[$this->il_cur_key]["blocklist"] = $this->blocklist;
        		self::$il_cache[$this->il_cur_key]["blockdata"] = $this->blockdata;
        		self::$il_cache[$this->il_cur_key]["blockinner"] = $this->blockinner;
        		self::$il_cache[$this->il_cur_key]["blockparents"] = $this->blockparents;
        		self::$il_cache[$this->il_cur_key]["blockvariables"] = $this->blockvariables;
	        }
		}
		
        // we don't need it any more
        $this->template = '';

    } // end func init
	
	/*
	* Sets wheather the ILIAS footer should be shown or not
	*
	* @param boolean $value TRUE to show the ILIAS footer, FALSE to hide it
	*/
	function setAddFooter($value)
	{
		$this->addFooter = $value;
	}
	
	/*
	* Returns wheather the ILIAS footer should be shown or not
	*
	* @return boolean TRUE if the ILIAS footer will be shown, FALSE otherwise
	*/
	function getAddFooter()
	{
		return $this->addFooter;
	}

	
	/**
	* Use this for final get before sending asynchronous output (ajax)
	* per echo to output.
	*/
	function getAsynch()
	{
		header("Content-type: " . $this->getContentType() . "; charset=UTF-8");
		return $this->get();
	}
	
	/**
	* ???
	* @access	public
	* @param	string
	* @return	string
	*/
	function get($part = "DEFAULT", $add_error_mess = false,
		$handle_referer = false, $add_ilias_footer = false,
		$add_standard_elements = false, $a_main_menu = true, $a_tabs = true)
	{
		if ($add_error_mess)
		{
			$this->fillMessage();
		}

		if ($add_ilias_footer)
		{
			$this->addILIASFooter();
		}

		// set standard parts (tabs and title icon)
		if($add_standard_elements)
		{
			// to get also the js files for the main menu
			$this->getMainMenu();
			
			// these fill blocks in tpl.main.html
			$this->fillCssFiles();
			$this->fillInlineCss();
			$this->fillContentStyle();
			$this->fillBodyClass();
			$this->fillOnLoadCode();

			// these fill just plain placeholder variables in tpl.main.html
			$this->setCurrentBlock("DEFAULT");
			$this->fillNewContentStyle();
			$this->fillContentLanguage();
			$this->fillWindowTitle();

			// these fill blocks in tpl.adm_content.html
			$this->fillHeader();
			$this->fillSideIcons();
			$this->fillScreenReaderFocus();
			$this->fillStopFloating();
			$this->fillPageFormAction();
			$this->fillLeftContent();
			$this->fillRightContent();
			$this->fillAdminPanel();
			$this->fillPermanentLink();
			$this->fillToolbar();

			// late loading of javascipr files, since operations above may add files
			$this->fillJavaScriptFiles();

			// these fill just plain placeholder variables in tpl.adm_content.html
			if ($this->blockExists("content"))
			{
				$this->setCurrentBlock("content");
				if ($a_tabs)
				{
					$this->fillTabs();
				}
				$this->fillMainContent();
				if ($a_main_menu)
				{
					$this->fillMainMenu();
				}
				$this->parseCurrentBlock();
			}
		}

		if ($handle_referer)
		{
			$this->handleReferer();
		}

		if ($part == "DEFAULT")
		{
			return parent::get();
		}
		else
		{
			return parent::get($part);
		}

	}

	/**
	* Set message. Please use ilUtil::sendInfo(), ilUtil::sendSuccess()
	* and ilUtil::sendFailure()
	*/
	function setMessage($a_type, $a_txt, $a_keep = false)
	{
		if (!in_array($a_type, array("info", "success", "failure", "question")) || $a_txt == "")
		{
			return;
		}
		if ($a_type == "question")
		{
			$a_type = "mess_question";
		}
		if (!$a_keep)
		{
			$this->message[$a_type] = $a_txt;
		}
		else
		{
			$_SESSION[$a_type] = $a_txt;
		}
	}
	
	function fillMessage()
	{
		global $lng;

		$ms = array("info", "success", "failure", "question");
		$out = "";
		
		foreach ($ms as $m)
		{
			$txt = "";
			if ($m == "question")
			{
				$m = "mess_question";
			}

			if (isset($_SESSION[$m]) && $_SESSION[$m] != "")
			{
				$txt = $_SESSION[$m];
			}
			else if (isset($this->message[$m]))
			{
				$txt = $this->message[$m];
			}

			if ($m == "mess_question")
			{
				$m = "question";
			}

			if ($txt != "")
			{
				$out.= $this->getMessageHTML($txt, $m);
			}
		
			if ($m == "question")
			{
				$m = "mess_question";
			}

			if (isset($_SESSION[$m]) && $_SESSION[$m])
			{
				session_unregister($m);
			}
		}
		
		if ($out != "")
		{
			$this->setVariable("MESSAGE", $out);
		}
	}

	/**
	* Get HTML for a system message
	*/
	public function getMessageHTML($a_txt, $a_type = "info")
	{
		global $lng;
		
		$mtpl = new ilTemplate("tpl.message.html", true, true, "Services/Utilities");
		$mtpl->setCurrentBlock($a_type."_message");
		$mtpl->setVariable("TEXT", $a_txt);
		$mtpl->setVariable("MESSAGE_HEADING", $lng->txt($a_type."_message"));
		$mtpl->setVariable("ALT_IMAGE", $lng->txt("icon")." ".$lng->txt($a_type."_message"));
		$mtpl->setVariable("SRC_IMAGE", ilUtil::getImagePath("mess_".$a_type.".gif"));
		$mtpl->parseCurrentBlock();
		
		return $mtpl->get();
	}
	
	/**
	* Get the content type for the template output
	*
	* @return string Content type
	* @access	public
	*/
	function getContentType()
	{
		return $this->contenttype;
	}
	
	/**
	* Set the content type for the template output
	*
	* Set the content type for the template output
	* Usually this is text/html. For MathML output the
	* content type should be set to text/xml
	*
	* @param string $a_content_type Content type
	* @access	public
	*/
	function setContentType($a_content_type = "text/html")
	{
		$this->contenttype = $a_content_type;
	}
	
	/**
	* @access	public
	* @param	string
	* @param bool fill template variable {TABS} with content of ilTabs
	*/
	function show($part = "DEFAULT", $a_fill_tabs = true, $a_skip_main_menu = false)
	{
		global $ilias;

		// include yahoo dom per default
		include_once("./Services/YUI/classes/class.ilYuiUtil.php");
		ilYuiUtil::initDom();
		
//echo "-".ilUtil::getP3PLocation()."-";
		//header('P3P: policyref="'.ilUtil::getP3PLocation().
		//	'", CP="CURa ADMa DEVa TAIa PSAa PSDa IVAa IVDa OUR BUS IND UNI COM NAV INT CNT STA PRE"');
		header('P3P: CP="CURa ADMa DEVa TAIa PSAa PSDa IVAa IVDa OUR BUS IND UNI COM NAV INT CNT STA PRE"');
		header("Content-type: " . $this->getContentType() . "; charset=UTF-8");

		$this->fillMessage();
		
		// display ILIAS footer
		if ($part !== false)
		{
			$this->addILIASFooter();
		}

		// set standard parts (tabs and title icon)
		$this->fillBodyClass();
		if ($a_fill_tabs)
		{
			// to get also the js files for the main menu
			if (!$a_skip_main_menu)
			{
				$this->getMainMenu();
			}

			// these fill blocks in tpl.main.html
			$this->fillCssFiles();
			$this->fillInlineCss();
			//$this->fillJavaScriptFiles();
			$this->fillContentStyle();

			// these fill just plain placeholder variables in tpl.main.html
			$this->setCurrentBlock("DEFAULT");
			$this->fillNewContentStyle();
			$this->fillContentLanguage();
			$this->fillWindowTitle();
			$this->fillOnLoadCode();

			// these fill blocks in tpl.adm_content.html
			$this->fillHeader();
			$this->fillSideIcons();
			$this->fillScreenReaderFocus();
			$this->fillStopFloating();
			$this->fillPageFormAction();
			$this->fillLeftContent();
			$this->fillRightContent();
			$this->fillAdminPanel();
			$this->fillPermanentLink();
			$this->fillToolbar();

			// late loading of javascipr files, since operations above may add files
			$this->fillJavaScriptFiles();

			// these fill just plain placeholder variables in tpl.adm_content.html
			// these fill just plain placeholder variables in tpl.adm_content.html
			if ($this->blockExists("content"))
			{
				$this->setCurrentBlock("content");
				$this->fillTabs();
				$this->fillMainContent();
				$this->fillMainMenu();
				$this->parseCurrentBlock();
			}
		}
		
		if ($part == "DEFAULT" or is_bool($part))
		{
			parent::show();
		}
		else
		{
			parent::show($part);
		}
		
		$this->handleReferer();
	}
	
	
	/**
	 * Add current user language to meta tags
	 *
	 * @access public
	 * 
	 */
	public function fillContentLanguage()
	{
	 	global $ilUser,$lng;
	 	
	 	if(is_object($ilUser))
	 	{
	 		if($ilUser->getLanguage())
	 		{
		 		$this->setVariable('META_CONTENT_LANGUAGE',$ilUser->getLanguage());
		 		return true;
	 		}
	 		if(is_object($lng))
	 		{
		 		$this->setVariable('META_CONTENT_LANGUAGE',$lng->getDefaultLanguage());
		 		return true;
	 		}
	 	}
 		$this->setVariable('META_CONTENT_LANGUAGE','en');
		return true;	 	
	}

	function fillWindowTitle()
	{
		global $ilSetting;
		
		if ($this->header_page_title != "")
		{
			$a_title = ilUtil::stripScriptHTML($this->header_page_title);	
			$this->setVariable("PAGETITLE", "- ".$a_title);
		}
		
		if ($ilSetting->get('short_inst_name') != "")
		{
			$this->setVariable("WINDOW_TITLE",
				$ilSetting->get('short_inst_name'));
		}
		else
		{
			$this->setVariable("WINDOW_TITLE",
				"ILIAS");
		}
	}
	
	function fillTabs()
	{
		global $ilias,$ilTabs;

		if ($this->blockExists("tabs_outer_start"))
		{
			$sthtml = $ilTabs->getSubTabHTML();
			$thtml = $ilTabs->getHTML((trim($sthtml) == ""));

			$this->touchBlock("tabs_outer_start");
			$this->touchBlock("tabs_outer_end");
			$this->touchBlock("tabs_inner_start");
			$this->touchBlock("tabs_inner_end");

			if ($thtml != "")
			{
				$this->setVariable("TABS",$thtml);
			}
			$this->setVariable("SUB_TABS", $sthtml);
		}
	}
	
	/**
	* Advanced column layout (alpha! do not use yet)
	*/
	function enableAdvancedColumnLayout($a_left, $a_right)
	{
		$this->adv_layout = true;
		include_once("./Services/YUI/classes/class.ilYuiUtil.php");
		ilYuiUtil::initLayout();
	}
	
	function fillToolbar()
	{
		global $ilToolbar;
		
		$this->setVariable("BUTTONS", $ilToolbar->getHTML());
	}

	function fillPageFormAction()
	{
		if ($this->page_form_action != "")
		{
			$this->setCurrentBlock("page_form_start");
			$this->setVariable("PAGE_FORM_ACTION", $this->page_form_action);
			$this->parseCurrentBlock();
			$this->touchBlock("page_form_end");
		}
	}
	
	function fillJavaScriptFiles($a_force = false)
	{
		global $ilias, $ilTabs, $ilSetting, $ilUser;
		
		if (is_object($ilSetting))		// maybe this one can be removed
		{
			$vers = "vers=".str_replace(array(".", " "), "-", $ilSetting->get("ilias_version"));
		}
		if ($this->blockExists("js_file"))
		{
			// three batches
			for ($i=1; $i<=3; $i++)
			{
				reset($this->js_files);
				foreach($this->js_files as $file)
				{
					if (is_file($file) || substr($file, 0, 4) == "http" || $a_force)
					{
						if ($this->js_files_batch[$file] == $i)
						{
							$this->setCurrentBlock("js_file");
		
							if ($this->js_files_vp[$file])
							{
								$this->setVariable("JS_FILE", ilUtil::appendUrlParameterString($file,$vers));
							}
							else
							{
								$this->setVariable("JS_FILE", $file);
							}
							
							$this->parseCurrentBlock();
						}
					}
				}
			}
		}
	}

	/**
	 * Fill in the css file tags
	 * 
	 * @param boolean $a_force
	 */
	function fillCssFiles($a_force = false)
	{
		if (!$this->blockExists("css_file"))
		{
			return;
		}
		foreach($this->css_files as $css)
		{
			$filename = $css["file"];
			if (strpos($filename, "?") > 0) $filename = substr($filename, 0, strpos($filename, "?"));
			if (is_file($filename) || $a_force)
			{
				$this->setCurrentBlock("css_file");
				$this->setVariable("CSS_FILE", $css["file"]);
				$this->setVariable("CSS_MEDIA", $css["media"]);
				$this->parseCurrentBlock();
			}
		}
	}

	/**
	 * Fill in the inline css
	 *
	 * @param boolean $a_force
	 */
	function fillInlineCss()
	{
		if (!$this->blockExists("css_inline"))
		{
			return;
		}
		foreach($this->inline_css as $css)
		{
			$this->setCurrentBlock("css_file");
			$this->setVariable("CSS_INLINE", $css["css"]);
			//$this->setVariable("CSS_MEDIA", $css["media"]);
			$this->parseCurrentBlock();
		}
	}

	/**
	* Set content style (used for page content editor)
	*/
	function setContentStyleSheet($a_style)
	{
		$this->content_style_sheet = $a_style;
	}
	
	/**
	* Fill Content Style
	*/
	function fillContentStyle()
	{
		if ($this->content_style_sheet != "")
		{
			$this->setCurrentBlock("ContentStyle");
			$this->setVariable("LOCATION_CONTENT_STYLESHEET",
				$this->content_style_sheet);
			$this->parseCurrentBlock();
		}
	}
	
	/**
	* Fill Content Style
	*/
	private function fillNewContentStyle()
	{
		$this->setVariable("LOCATION_NEWCONTENT_STYLESHEET_TAG",
			'<link rel="stylesheet" type="text/css" href="'.
			ilUtil::getNewContentStyleSheetLocation()
			.'" />');
	}
	
	function getMainMenu()
	{
		global $ilMainMenu;

		$ilMainMenu->setLoginTargetPar($this->getLoginTargetPar());
		$this->main_menu = $ilMainMenu->getHTML();
	}
	
	function fillMainMenu()
	{
		global $tpl, $ilMainMenu, $ilCtrl, $ilSetting, $ilUser; 
		$tpl->setVariable("MAINMENU", $this->main_menu);
		if($this->variableExists('MAINMENU'))
		{
			global $ilAuth, $lng, $tpl, $ilClientIniFile, $ilUser;
			
			if((int)$ilSetting->get('session_handling_type') == 0 &&
			   (int)$ilSetting->get('session_reminder_enabled') &&
			   $ilUser->getId() != ANONYMOUS_USER_ID &&
			   (int)$ilUser->getPref('session_reminder_enabled'))
			{
				$leadTime = (float)$ilUser->getPref('session_reminder_lead_time') * 60;
				$expiresTime = $ilAuth->sessionValidThru();
				$currentTime = time();
				$expiresInXSeconds = $expiresTime - $currentTime;				
				
				if($leadTime > $expiresInXSeconds) return;				
    							
				$tplSR = new ilTemplate('tpl.SessionReminder.js', true, true);
						
				$tplSR->setVariable('ILIAS_SESSION_COUNTDOWN', ($expiresInXSeconds - $leadTime) * 1000);
				$tplSR->setVariable('ILIAS_SESSION_EXTENDER_URL', './ilias.php?baseClass=ilPersonalDesktopGUI');				
				$tplSR->setVariable('ILIAS_SESSION_CHECKER_URL',
					'./sessioncheck.php'.
					'?lang='.$lng->getLangKey().
					'&client_id='.CLIENT_ID.
					'&session_id='.session_id().
					'&lead_time='.$leadTime.
					'&timezone='.urlencode($ilUser->getTimeZone()).
					'&countDownTime='.($expiresInXSeconds - $leadTime));
				$tplSR->setVariable('CONFIRM_TXT', $lng->txt('session_reminder_alert'));

				$tpl->setVariable('SESSION_REMINDER', $tplSR->get());		
			}
		}
	}

	/**
	* add ILIAS footer
	*/
	function addILIASFooter()
	{
		global $ilAuth;
		
		if (!$this->getAddFooter()) return;
		global $ilias, $ilClientIniFile, $ilCtrl, $ilDB, $ilSetting;
		
		$ftpl = new ilTemplate("tpl.footer.html", true, true);
		
		$ftpl->setVariable("ILIAS_VERSION", $ilias->getSetting("ilias_version"));

		// output translation link
		if ($ilSetting->get("lang_ext_maintenance") == "1")
		{
			include_once("Services/Language/classes/class.ilObjLanguageAccess.php");
			if (ilObjLanguageAccess::_checkTranslate())
			{
				include_once("Services/Language/classes/class.ilObjLanguageExtGUI.php");
				$ftpl->setVariable("TRANSLATION_LINK",
					ilObjLanguageExtGUI::_getTranslationLink());
			}
		}

		if (DEVMODE)
		{
			// execution time
			$t1 = explode(" ", $GLOBALS['ilGlobalStartTime']);
			$t2 = explode(" ", microtime());
			$diff = $t2[0] - $t1[0] + $t2[1] - $t1[1];

			$mem_usage = "";
			if(function_exists("memory_get_usage"))
			{
				$mem_usage.=
					"<br /> Memory Usage: ".memory_get_usage()." Bytes";
			}
			if(function_exists("xdebug_peak_memory_usage"))
			{
				$mem_usage.=
					"<br /> XDebug Peak Memory Usage: ".xdebug_peak_memory_usage()." Bytes";
			}
			$mem_usage.= "<br>".round($diff, 4)." Seconds";
			
			if ($mem_usage != "")
			{
				$ftpl->setVariable("MEMORY_USAGE", $mem_usage);
			}
			
			if (is_object($ilAuth) && isset($_SESSION[$ilAuth->_sessionName]) &&
				isset($_SESSION[$ilAuth->_sessionName]["timestamp"]))
			{
				$ftpl->setVariable("SESS_INFO", "<br />maxlifetime: ".
					ini_get("session.gc_maxlifetime")." (".
					(ini_get("session.gc_maxlifetime")/60)."), id: ".session_id()."<br />".
					"timestamp: ".date("Y-m-d H:i:s", $_SESSION[$ilAuth->_sessionName]["timestamp"]).
					", idle: ".date("Y-m-d H:i:s", $_SESSION[$ilAuth->_sessionName]["idle"]).
					"<br />expire: ".($exp = $ilClientIniFile->readVariable("session","expire")).
					" (".($exp/60)."), session ends at: ".
					date("Y-m-d H:i:s", $_SESSION[$ilAuth->_sessionName]["idle"] + $exp));
			}
			
			if (version_compare(PHP_VERSION,'5','>='))
			{
				$ftpl->setVariable("VALIDATION_LINKS",
					'<br /><a href="'.
					ilUtil::appendUrlParameterString($_SERVER["REQUEST_URI"], "do_dev_validate=xhtml").
					'">Validate</a> | <a href="'.
					ilUtil::appendUrlParameterString($_SERVER["REQUEST_URI"], "do_dev_validate=accessibility").
					'">Accessibility</a>');
			}
			if (!empty($_GET["do_dev_validate"]) && $ftpl->blockExists("xhtml_validation"))
			{
				require_once("Services/XHTMLValidator/classes/class.ilValidatorAdapter.php");
				$template2 = ilPHP::cloneObject($this);
//echo "-".ilValidatorAdapter::validate($template2->get(), $_GET["do_dev_validate"])."-";
				$ftpl->setCurrentBlock("xhtml_validation");
				$ftpl->setVariable("VALIDATION",
					ilValidatorAdapter::validate($template2->get("DEFAULT",
					false, false, false, true), $_GET["do_dev_validate"]));
				$ftpl->parseCurrentBlock();
			}
			
			// controller history
			if (is_object($ilCtrl) && $ftpl->blockExists("c_entry") &&
				$ftpl->blockExists("call_history"))
			{
				$hist = $ilCtrl->getCallHistory();
				foreach($hist as $entry)
				{
					$ftpl->setCurrentBlock("c_entry");
					$ftpl->setVariable("C_ENTRY", $entry["class"]);
					if (is_object($ilDB))
					{
						$file = $ilCtrl->lookupClassPath($entry["class"]);
						$add = $entry["mode"]." - ".$entry["cmd"];
						if ($file != "")
						{
							$add.= " - ".$file;
						}
						$ftpl->setVariable("C_FILE", $add);
					}
					$ftpl->parseCurrentBlock();
				}
				$ftpl->setCurrentBlock("call_history");
				$ftpl->parseCurrentBlock();
				
				// debug hack
				$debug = $ilCtrl->getDebug();
				foreach($debug as $d)
				{
					$ftpl->setCurrentBlock("c_entry");
					$ftpl->setVariable("C_ENTRY", $d);
					$ftpl->parseCurrentBlock();
				}
				$ftpl->setCurrentBlock("call_history");
				$ftpl->parseCurrentBlock();
			}
			
			// included files
			if (is_object($ilCtrl) && $ftpl->blockExists("i_entry") &&
				$ftpl->blockExists("included_files"))
			{
				$fs = get_included_files();
				$ifiles = array();
				$total = 0;
				foreach($fs as $f)
				{
					$ifiles[] = array("file" => $f, "size" => filesize($f));
					$total += filesize($f);
				}
				$ifiles = ilUtil::sortArray($ifiles, "size", "desc", true);
				foreach($ifiles as $f)
				{
					$ftpl->setCurrentBlock("i_entry");
					$ftpl->setVariable("I_ENTRY", $f["file"]." (".$f["size"]." Bytes, ".round(100 / $total * $f["size"], 2)."%)");
					$ftpl->parseCurrentBlock();
				}
				$ftpl->setCurrentBlock("i_entry");
				$ftpl->setVariable("I_ENTRY", "Total (".$total." Bytes, 100%)");
				$ftpl->parseCurrentBlock();
				$ftpl->setCurrentBlock("included_files");
				$ftpl->parseCurrentBlock();				
			}

		}

		// BEGIN Usability: Non-Delos Skins can display the elapsed time in the footer
		// The corresponding $ilBench->start invocation is in inc.header.php
		global $ilBench;
		$ilBench->stop("Core", "ElapsedTimeUntilFooter");
		$ftpl->setVariable("ELAPSED_TIME",
			", ".number_format($ilBench->getMeasuredTime("Core", "ElapsedTimeUntilFooter"),1).' seconds');
		// END Usability: Non-Delos Skins can display the elapsed time in the footer
		
		$this->setVariable("FOOTER", $ftpl->get());
	}


	/**
	* TODO: this is nice, but shouldn't be done here
	* (-> maybe at the end of ilias.php!?, alex)
	*/
	function handleReferer()
	{
		if (((substr(strrchr($_SERVER["PHP_SELF"],"/"),1) != "error.php")
			&& (substr(strrchr($_SERVER["PHP_SELF"],"/"),1) != "adm_menu.php")
			&& (substr(strrchr($_SERVER["PHP_SELF"],"/"),1) != "chat.php")))
		{
			$_SESSION["post_vars"] = $_POST;

			// referer is modified if query string contains cmd=gateway and $_POST is not empty.
			// this is a workaround to display formular again in case of error and if the referer points to another page
			$url_parts = @parse_url($_SERVER["REQUEST_URI"]);
			if(!$url_parts)
			{
				$protocol = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://';
				$host = $_SERVER['HTTP_HOST'];
				$path = $_SERVER['REQUEST_URI'];
				$url_parts = @parse_url($protocol.$host.$path);
			}

			if (isset($url_parts["query"]) && preg_match("/cmd=gateway/",$url_parts["query"]) && (isset($_POST["cmd"]["create"])))
			{
				foreach ($_POST as $key => $val)
				{
					if (is_array($val))
					{
						$val = key($val);
					}

					$str .= "&".$key."=".$val;
				}

				$_SESSION["referer"] = preg_replace("/cmd=gateway/",substr($str,1),$_SERVER["REQUEST_URI"]);
				$_SESSION['referer_ref_id'] = (int) $_GET['ref_id'];
				
			}
			else if (isset($url_parts["query"]) && preg_match("/cmd=post/",$url_parts["query"]) && (isset($_POST["cmd"]["create"])))
			{
				foreach ($_POST as $key => $val)
				{
					if (is_array($val))
					{
						$val = key($val);
					}

					$str .= "&".$key."=".$val;
				}

				$_SESSION["referer"] = preg_replace("/cmd=post/",substr($str,1),$_SERVER["REQUEST_URI"]);
				if (isset($_GET['ref_id']))
				{
					$_SESSION['referer_ref_id'] = (int) $_GET['ref_id'];
				}
				else
				{
					$_SESSION['referer_ref_id'] = 0;
				}
							}
			else
			{
				$_SESSION["referer"] = $_SERVER["REQUEST_URI"];
				if (isset($_GET['ref_id']))
				{
					$_SESSION['referer_ref_id'] = (int) $_GET['ref_id'];
				}
				else
				{
					$_SESSION['referer_ref_id'] = 0;
				}
			}

			unset($_SESSION["error_post_vars"]);
		}
	}

	/**
	* check if block exists in actual template
	* @access	private
	* @param string blockname
	* @return	boolean
	*/
	function blockExists($a_blockname)
	{
		// added second evaluation to the return statement because the first one only works for the content block (Helmut Schottmüller, 2007-09-14)		
		return (isset($this->blockvariables["content"][$a_blockname]) ? true : false) | (isset($this->blockvariables[$a_blockname]) ? true : false);
	}
	
	private function variableExists($a_variablename)
	{
		return (isset($this->blockvariables["content"][$a_variablename]) ? true : false);
	}

	/**
	* all template vars defined in $vars will be replaced automatically
	* without setting and parsing them with setVariable & parseCurrentBlock
	* @access	private
	* @return	integer
	*/
	function fillVars()
	{
		$count = 0;
		reset($this->vars);

		while(list($key, $val) = each($this->vars))
		{
			if (is_array($this->blockvariables[$this->activeBlock]))
			{
				if  (array_key_exists($key, $this->blockvariables[$this->activeBlock]))
				{
					$count++;

					$this->setVariable($key, $val);
				}
			}
		}
		
		return $count;
	}
	
	/**
	* Überladene Funktion, die sich hier lokal noch den aktuellen Block merkt.
	* @access	public
	* @param	string
	* @return	???
	*/
	function setCurrentBlock ($part = "DEFAULT")
	{
		$this->activeBlock = $part;

		if ($part == "DEFAULT")
		{
			return parent::setCurrentBlock();
		}
		else
		{
			return parent::setCurrentBlock($part);
		}
	}

	/**
	* overwrites ITX::touchBlock.
	* @access	public
	* @param	string
	* @return	???
	*/
	function touchBlock($block)
	{
		$this->setCurrentBlock($block);
		$count = $this->fillVars();
		$this->parseCurrentBlock();

		if ($count == 0)
		{
			parent::touchBlock($block);
		}
	}

	/**
	* Überladene Funktion, die auf den aktuelle Block vorher noch ein replace ausführt
	* @access	public
	* @param	string
	* @return	string
	*/
	function parseCurrentBlock($part = "DEFAULT")
	{
		// Hier erst noch ein replace aufrufen
		if ($part != "DEFAULT")
		{
			$tmp = $this->activeBlock;
			$this->activeBlock = $part;
		}

		if ($part != "DEFAULT")
		{
			$this->activeBlock = $tmp;
		}

		$this->fillVars();

		$this->activeBlock = "__global__";

		if ($part == "DEFAULT")
		{
			return parent::parseCurrentBlock();
		}
		else
		{
			return parent::parseCurrentBlock($part);
		}
	}

	/**
	* ???
	* TODO: Adjust var names to ilias. This method wasn't used so far
	* and isn't translated yet
	* @access	public
	* @param	string
	* @param	string
	* @param	string
	* @param	string
	*/
	function replaceFromDatabase(&$DB,$block,$conv,$select="default")
	{
		$res = $DB->selectDbAll();

		while ($DB->getDbNextElement($res))
		{
			$this->setCurrentBlock($block);
			$result = array();
			reset($conv);

			while (list ($key,$val) = each ($conv))
			{
				$result[$val]=$DB->element->data[$key];
			}

			if (($select != "default")
				&& ($DB->element->data[$select["id"]]==$select["value"]
				|| (strtolower($select["text"]) == "checked"
				&& strpos( ",,".$select["value"].",," , ",".$DB->element->data[$select["id"]]."," )!=false)))
			{
				$result[$select["field"]] = $select["text"];
			}

			$this->replace($result);
			$this->parseCurrentBlock($block);
		}
	}

	/**
	* Wird angewendet, wenn die Daten in ein Formular replaced werden sollen,
	* Dann wird erst noch ein htmlspecialchars drumherum gemacht.
	* @access	public
	* @param	string
	*/
	function prepareForFormular($vars)
	{
		if (!is_array($vars))
		{
			return;
		}

		reset($vars);

		while (list($i) = each($vars))
		{
			$vars[$i] = stripslashes($vars[$i]);
			$vars[$i] = htmlspecialchars($vars[$i]);
		}

		return($vars);
	}

	/**
	* ???
	* @access	public
	*/
	function replace()
	{
		reset($this->vars);

		while(list($key, $val) = each($this->vars))
		{
			$this->setVariable($key, $val);
		}
	}

	/**
	* ???
	* @access	public
	*/
	function replaceDefault()
	{
		$this->replace($this->vars);
	}

	/**
	* checks for a topic in the template
	* @access	private
 	* @param	string
	* @param	string
	* @return	boolean
	*/
	function checkTopic($a_block, $a_topic)
	{
		return array_key_exists($a_topic, $this->blockvariables[$a_block]);
	}

	/**
	* check if there is a NAVIGATION-topic
	* @access	public
	* @return	boolean
	*/
	function includeNavigation()
	{
		return $this->checkTopic("__global__", "NAVIGATION");
	}

	/**
	* check if there is a TREE-topic
	* @access	public
	* @return	boolean
	*/
	function includeTree()
	{
		return $this->checkTopic("__global__", "TREE");
	}

	/**
	* check if a file exists
	* @access	public
	* @return	boolean
	*/
	function fileExists($filename)
	{
		return file_exists($this->tplPath."/".$filename);
	}


	/**
	* overwrites ITX::addBlockFile
	* @access	public
	* @param	string
	* @param	string
	* @param	string		$tplname		template name
	* @param	boolean		$in_module		should be set to true, if template file is in module subdirectory
	* @return	boolean/string
	*/
	function addBlockFile($var, $block, $tplname, $in_module = false)
	{
		if (DEBUG)
		{
			echo "<br/>Template '".$this->tplPath."/".$tplname."'";
		}

		$tplfile = $this->getTemplatePath($tplname, $in_module);
		if (file_exists($tplfile) == false)
		{
			echo "<br/>Template '".$tplfile."' doesn't exist! aborting...";
			return false;
		}

		return parent::addBlockFile($var, $block, $tplfile);
	}

	/**
	* builds a full template path with template and module name
	*
	* @param	string		$a_tplname		template name
	* @param	boolean		$in_module		should be set to true, if template file is in module subdirectory
	*
	* @return	string		full template path
	*/
	function getTemplatePath($a_tplname, $a_in_module = false, $a_plugin = false)
	{
		global $ilias, $ilCtrl;
		
		$fname = "";
		
		// if baseClass functionality is used (ilias.php):
		// get template directory from ilCtrl
		if (!empty($_GET["baseClass"]) && $a_in_module === true)
		{
			$a_in_module = $ilCtrl->getModuleDir();
		}

		if (strpos($a_tplname,"/") === false)
		{
			$module_path = "";
			
			//$fname = $ilias->tplPath;
			if ($a_in_module)
			{
				if ($a_in_module === true)
				{
					$module_path = ILIAS_MODULE."/";
				}
				else
				{
					$module_path = $a_in_module."/";
				}
			}

			if ($ilias->account->skin != "default")
			{
				$fname = "./Customizing/global/skin/".
					$ilias->account->skin."/".$module_path.basename($a_tplname);
			}
			if($fname == "" || !file_exists($fname))
			{
				$fname = "./".$module_path."templates/default/".basename($a_tplname);
			}
		}
		else
		{
			$fname = $a_tplname;
		}
		
		return $fname;
	}
	
	function setHeaderPageTitle($a_title)
	{
		$a_title = ilUtil::stripScriptHTML($a_title);	
		$this->header_page_title = $a_title;
	}
	
	function setStyleSheetLocation($a_stylesheet)
	{
		$this->setVariable("LOCATION_STYLESHEET", $a_stylesheet);
	}

	function setNewContentStyleSheetLocation($a_stylesheet)
	{
		$this->setVariable("LOCATION_NEWCONTENT_STYLESHEET", $a_stylesheet);
	}

	function getStandardTemplate()
	{
		// always load jQuery
		include_once("./Services/jQuery/classes/class.iljQueryUtil.php");
		iljQueryUtil::initjQuery();

		// always load Basic js
		$this->addJavaScript("./Services/JavaScript/js/Basic.js",
			true, 1);

		$this->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
	}
	
	/**
	* sets title in standard template
	*/
	function setTitle($a_title)
	{
		$this->title = ilUtil::stripScriptHTML($a_title);
		$this->header_page_title = $a_title;
	}
	
	/**
	* Fill header
	*/
	private function fillHeader()
	{
		global $lng;
		
		if ($this->icon_path != "")
		{
			if ($this->icon_desc != "")
			{
				$this->setCurrentBlock("header_image_desc");
				$this->setVariable("IMAGE_DESC", $lng->txt("icon")." ".$this->icon_desc);
				$this->parseCurrentBlock();
			}
			$this->setCurrentBlock("header_image");
			if ($this->icon_desc != "")
			{
				$this->setVariable("IMAGE_ALT", $lng->txt("icon")." ".$this->icon_desc);
			}
			else
			{
				// empty alt tag for images that, e.g. are directly attached in heading
				// and would only repeat the heading text
				$this->setVariable("IMAGE_ALT", "");
			}
			$this->setVariable("IMG_HEADER", $this->icon_path);
			$this->parseCurrentBlock();
			$header = true;
		}

		if ($this->title != "")
		{
			$this->title = ilUtil::stripScriptHTML($this->title);			
			$this->setVariable("HEADER", $this->title);
			$header = true;
		}
		
		if ($header)
		{
			$this->setCurrentBlock("header_image");
			$this->parseCurrentBlock();
		}
		
		if ($this->title_desc != "")
		{
			$this->setCurrentBlock("header_desc");
			$this->setVariable("H_DESCRIPTION", $this->title_desc);
			$this->parseCurrentBlock();
		}
	}
	
	/**
	* set title icon
	*/
	function setTitleIcon($a_icon_path, $a_icon_desc = "")
	{
		$this->icon_desc = $a_icon_desc;
		$this->icon_path = $a_icon_path;
	}

	function setBodyClass($a_class = "")
	{
		$this->body_class = $a_class;
	}
	
	function fillBodyClass()
	{
		if ($this->body_class != "" && $this->blockExists("body_class"))
		{
			$this->setCurrentBlock("body_class");
			$this->setVariable("BODY_CLASS", $this->body_class);
			$this->parseCurrentBlock();
		}
	}
	
	function setPageFormAction($a_action)
	{
		$this->page_form_action = $a_action;
	}
	
	/**
	* sets title in standard template
	*/
	function setDescription($a_descr)
	{
		$this->title_desc = $a_descr;
//		$this->setVariable("H_DESCRIPTION", $a_descr);
	}
	
	/**
	* set stop floating (if no tabs are used)
	*/
	function stopTitleFloating()
	{
		$this->stop_floating = true;
	}
	
	/**
	* stop floating
	*/
	private function fillStopFloating()
	{
		if ($this->stop_floating)
		{
			$this->touchBlock("stop_floating");
		}
	}
	
	/**
	* sets content for standard template
	*/
	function setContent($a_html)
	{
		if ($a_html != "")
		{
			$this->main_content = $a_html;
		}
	}
	
	/**
	* Fill main content
	*/
	public function fillMainContent()
	{
		if (trim($this->main_content) != "")
		{
			$this->setVariable("ADM_CONTENT", $this->main_content);
		}
		if($this->adv_layout)
		{
			$this->touchBlock("adv_layout_init");
			$this->setVariable("COL_LAY_SKIN", " yui-skin-sam");
		}
	}

	/**
	* sets content of right column
	*/
	function setRightContent($a_html)
	{
		$this->right_content = $a_html;
	}
	
	/**
	* Fill right content
	*/
	private function fillRightContent()
	{
		if (trim($this->right_content) != "")
		{
			$this->setCurrentBlock("right_column");
			$this->setVariable("RIGHT_CONTENT", $this->right_content);
			$this->parseCurrentBlock();
			$this->touchBlock("right_columnt");
		}
	}
	
	/**
	* sets content of left column
	*/
	function setLeftContent($a_html)
	{
		$this->left_content = $a_html;
	}
		
	/**
	* Fill left content
	*/
	private function fillLeftContent()
	{
		if (trim($this->left_content) != "")
		{
			$this->setCurrentBlock("left_column");
			$this->setVariable("LEFT_CONTENT", $this->left_content);
			$this->parseCurrentBlock();
			$this->touchBlock("left_columnt");
		}
	}

	/**
	* Insert locator.
	*/
	function setLocator()
	{
		global $ilLocator, $lng, $ilPluginAdmin;

		$html = $ilLocator->getHTML();

		if (is_object($ilPluginAdmin))
		{
			$pl_names = $ilPluginAdmin->getActivePluginsForSlot(IL_COMP_SERVICE, "UIComponent", "uihk");
						foreach ($pl_names as $pl)
			{
				$ui_plugin = ilPluginAdmin::getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", $pl);
				$gui_class = $ui_plugin->getUIClassInstance();
				$resp = $gui_class->getHTML("Services/Locator", "main_locator",
					array("locator_gui" => $ilLocator));
				if ($resp["mode"] != ilUIHookPluginGUI::KEEP)
				{
					$plugin_html = true;
					break;		// first one wins
				}

			}
			// combine plugin and default html
			if ($plugin_html)
			{
				$html = $gui_class->modifyHTML($html, $resp);
			}
		}

		$this->setVariable("LOCATOR", $html);
	}
	
	/**
	* sets tabs in standard template
	*/
	function setTabs($a_tabs_html)
	{
		if ($a_tabs_html != "" && $this->blockExists("tabs_outer_start"))
		{
			$this->touchBlock("tabs_outer_start");
			$this->touchBlock("tabs_outer_end");
			$this->touchBlock("tabs_inner_start");
			$this->touchBlock("tabs_inner_end");
			$this->setVariable("TABS", $a_tabs_html);
		}
	}

	/**
	* sets subtabs in standard template
	*/
	function setSubTabs($a_tabs_html)
	{
		$this->setVariable("SUB_TABS", $a_tabs_html);
	}
	
	/**
	* sets icon to upper level
	*/
	function setUpperIcon($a_link, $a_frame = "")
	{
		global $lng;
		
		$this->upper_icon = $a_link;
		$this->upper_icon_frame = $a_frame;
	}

	/**
	 * Set target parameter for login (public sector).
	 * This is used by the main menu
	 */
	public function setLoginTargetPar($a_val)
	{
		$this->login_target_par = $a_val;
	}

	/**
	 * Get target parameter for login
	 */
	public function getLoginTargetPar()
	{
		return $this->login_target_par;
	}

	/**
	* Accessibility focus for screen readers
	*/
	function fillScreenReaderFocus()
	{
		global $ilUser;

		if (is_object($ilUser) && $ilUser->getPref("screen_reader_optimization"))
		{
			$this->touchBlock("sr_focus");
		}
	}
	
	/**
	* Fill side icons (upper icon, tree icon, webfolder icon)
	*/
	function fillSideIcons()
	{
		global $lng, $ilSetting;
		
		if ($this->upper_icon == "" && $this->tree_flat_link == ""
			&& $this->mount_webfolder == "")
		{
			return;
		}
		
		// upper icon
		if ($this->upper_icon != "")
		{
			if ($this->upper_icon_frame != "")
			{
				$this->setCurrentBlock("target_top");
				$this->setVariable("TARGET_TOP", $this->upper_icon_frame);
				$this->parseCurrentBlock();
			}
	
			$this->setCurrentBlock("alt_top");
			$this->setVariable("ALT_TOP", $lng->txt("up"));
			$this->parseCurrentBlock();
	
			$this->setCurrentBlock("top");
			$this->setVariable("LINK_TOP", $this->upper_icon);
			$this->setVariable("IMG_TOP",ilUtil::getImagePath("ic_top.gif"));
			$this->parseCurrentBlock();
		}
		
		// tree/flat icon
		if ($this->tree_flat_link != "")
		{
			$this->setCurrentBlock("tree_mode");
			$this->setVariable("LINK_MODE", $this->tree_flat_link);
			if ($ilSetting->get("tree_frame") == "right")
			{
				if ($this->tree_flat_mode == "tree")
				{
					$this->setVariable("IMG_TREE",ilUtil::getImagePath("ic_sidebar_left.gif"));
					$this->setVariable("RIGHT", "Right");
				}
				else
				{
					$this->setVariable("IMG_TREE",ilUtil::getImagePath("ic_sidebar_right.gif"));
					$this->setVariable("RIGHT", "Right");
				}
			}
			else
			{
				if ($this->tree_flat_mode == "tree")
				{
					$this->setVariable("IMG_TREE",ilUtil::getImagePath("ic_sidebar_right.gif"));
				}
				else
				{
					$this->setVariable("IMG_TREE",ilUtil::getImagePath("ic_sidebar_left.gif"));
				}
			}
			$this->setVariable("ALT_TREE",$lng->txt($this->tree_flat_mode."view"));
			$this->setVariable("TARGET_TREE", ilFrameTargetInfo::_getFrame("MainContent"));
			include_once("./Services/Accessibility/classes/class.ilAccessKeyGUI.php");
			$this->setVariable("TREE_ACC_KEY",
				ilAccessKeyGUI::getAttribute(($this->tree_flat_mode == "tree")
					? ilAccessKey::TREE_ON
					: ilAccessKey::TREE_OFF));
			$this->parseCurrentBlock();
		}
		
		// mount webfolder
		if ($this->mount_webfolder != "")
		{
			require_once('Services/WebDAV/classes/class.ilDAVServer.php');
			$davServer = new ilDAVServer();
			$a_ref_id = $this->mount_webfolder;
			$a_link =  $davServer->getMountURI($a_ref_id);
			$a_folder = $davServer->getFolderURI($a_ref_id);
			
			$this->setCurrentBlock("mount_webfolder");
			$this->setVariable("LINK_MOUNT_WEBFOLDER", $a_link);
			$this->setVariable("FOLDER_MOUNT_WEBFOLDER", $a_folder);
			$this->setVariable("IMG_MOUNT_WEBFOLDER",ilUtil::getImagePath("ic_mount_webfolder.gif"));
			$this->setVariable("ALT_MOUNT_WEBFOLDER",$lng->txt("mount_webfolder"));
			$this->setVariable("TARGET_MOUNT_WEBFOLDER", '_blank');
			$this->parseCurrentBlock();
		}
		
		$this->setCurrentBlock("tree_icons");
		$this->parseCurrentBlock();
	}
	
	// BEGIN WebDAV: Mount webfolder icon.
	/**
	* shows icon for mounting a webfolder
	*/
	function setMountWebfolderIcon($a_ref_id)
	{
		global $lng;
		
		$this->mount_webfolder = $a_ref_id;
	}
	// END WebDAV: Mount webfolder icon.

	/**
	* set tree/flat icon
	* @param	string		link target
	* @param	strong		mode ("tree" | "flat")
	*/
	function setTreeFlatIcon($a_link, $a_mode)
	{
		global $lng;

		$this->tree_flat_link = $a_link;
		$this->tree_flat_mode = $a_mode;
	}

	/**
	* Add a javascript file that should be included in the header.
	*/
	function addJavaScript($a_js_file, $a_add_version_parameter = true, $a_batch = 2)
	{
		// three batches currently
		if ($a_batch < 1 || $a_batch > 3)
		{
			$a_batch = 2;
		}
		if (!in_array($a_js_file, $this->js_files))
		{
			$this->js_files[] = $a_js_file;
			$this->js_files_vp[$a_js_file] = $a_add_version_parameter;
			$this->js_files_batch[$a_js_file] = $a_batch;
		}
	}

	/**
	* Add on load code
	*/
	function addOnLoadCode($a_code, $a_batch = 2)
	{
		// three batches currently
		if ($a_batch < 1 || $a_batch > 3)
		{
			$a_batch = 2;
		}
		$this->on_load_code[$a_batch][] = $a_code;
	}
	
	/**
	 * Add a css file that should be included in the header.
	 */
	function addCss($a_css_file, $media = "screen")
	{
		if (!array_key_exists($a_css_file . $media, $this->css_files))
		{
			$this->css_files[$a_css_file . $media] = array("file" => $a_css_file, "media" => $media);
		}
	}

	/**
	 * Add a css file that should be included in the header.
	 */
	function addInlineCss($a_css, $media = "screen")
	{
		$this->inline_css[] = array("css" => $a_css, "media" => $media);
	}
	
	/**
	* Set selection and create button for adding new objects
	*/
	function setCreationSelector($a_form_action, $a_options,
		$a_command, $a_txt)
	{
		$this->setPageFormAction($a_form_action);

		$this->creation_selector =
			array("form_action" => $a_form_action,
				"options" => $a_options,
				"command" => $a_command,
				"txt" => $a_txt);
	}
	
	/**
	* Show admin view button
	*/
	function setPageActions($a_page_actions_html)
	{
		$this->page_actions = $a_page_actions_html;
	}
	
	/**
	* Show admin view button
	*/
	function setEditPageButton($a_link, $a_txt, $a_frame)
	{
		$this->edit_page_button =
			array("link" => $a_link, "txt" => $a_txt, "frame" => $a_frame);
	}
	
	/**
	* Add a command to the admin panel
	*/
	function addAdminPanelCommand($a_cmd, $a_txt, $a_arrow = false)
	{
		$this->admin_panel_commands[] =
			array("cmd" => $a_cmd, "txt" => $a_txt);
		if ($a_arrow)
		{
			$this->admin_panel_arrow = true;
		}
	}
	
	/**
	* Put admin panel into template:
	* - creation selector
	* - admin view on/off button
	*/
	function fillAdminPanel()
	{
		global $lng;
		
		$adm_view_cmp = $adm_cmds = $creation_selector = $adm_view = false;
		
		$toolb = new ilToolbarGUI();
		
		// admin panel commands
		if ((count($this->admin_panel_commands) > 0))
		{
			foreach($this->admin_panel_commands as $cmd)
			{
				$toolb->addFormButton($cmd["txt"], $cmd["cmd"]);
			}

			$adm_cmds = true;
		}
		if ($adm_cmds)
		{
			$this->setCurrentBlock("adm_view_components");
			if ($this->admin_panel_arrow)
			{
				$toolb->setLeadingImage(ilUtil::getImagePath("arrow_upright.gif"), $lng->txt("actions"));
			}
			$this->setVariable("ADM_PANEL1", $toolb->getHTML());
			$this->parseCurrentBlock();
			$adm_view_cmp = true;
		}
		
		// admin view button
		if ($this->page_actions != "")
		{
			$this->setVariable("PAGE_ACTIONS", $this->page_actions);
			$adm_view = true;
		}

		// creation selector
		if (is_array($this->creation_selector))
		{
			$this->setCurrentBlock("add_commands");
			if ($adm_cmds)
			{
				$this->setVariable("ADD_COM_WIDTH", 'width="1"');
			}
			
			include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
			$selection = new ilAdvancedSelectionListGUI();
			$selection->setFormSelectMode("new_type", "", false,
				"", "", "",
				"", $this->creation_selector["txt"], "", $this->creation_selector["command"]);
			$selection->setListTitle($lng->txt("cntr_add_new_item"));
			$selection->setId("item_creation");
			//$selection->setSelectionHeaderClass("MMInactive");
			$selection->setHeaderIcon(ilUtil::getImagePath("cmd_add_s.gif"));
			$selection->setItemLinkClass("xsmall");
			//$selection->setSelectionHeaderClass("submit");
			$selection->setUseImages(true);
			$selection->setOnClickMode(ilAdvancedSelectionListGUI::ON_ITEM_CLICK_FORM_SUBMIT,
				"main_page_form");
			foreach ($this->creation_selector["options"] as $item)
			{
				$selection->addItem($item["title"], $item["value"], "",
					$item["img"], $item["title"], "");
			}
			$this->setVariable("SELECT_OBJTYPE_REPOS",
				$selection->getHTML());
			
			$this->parseCurrentBlock();
			$creation_selector = true;
		}
		
		if ($adm_cmds)
		{
			$this->setCurrentBlock("adm_view_components2");
			if ($this->admin_panel_arrow)
			{
				$toolb->setLeadingImage(ilUtil::getImagePath("arrow_downright.gif"), $lng->txt("actions"));
			}
			$this->setVariable("ADM_PANEL2", $toolb->getHTML());
			$this->parseCurrentBlock();
		}

	}
	
	function setPermanentLink($a_type, $a_id, $a_append = "", $a_target = "")
	{
		$this->permanent_link = array(
			"type" => $a_type,
			"id" => $a_id,
			"append" => $a_append,
			"target" => $a_target);
	}
	
	/**
	* Fill in permanent link
	*/
	function fillPermanentLink()
	{
		if (is_array($this->permanent_link))
		{
			include_once("./Services/PermanentLink/classes/class.ilPermanentLinkGUI.php");
			$plinkgui = new ilPermanentLinkGUI(
				$this->permanent_link["type"],
				$this->permanent_link["id"],
				$this->permanent_link["append"],
				$this->permanent_link["target"]);
			$this->setVariable("PRMLINK", $plinkgui->getHTML());
		}
	}

	/**
	* Fill add on load code
	*/
	function fillOnLoadCode()
	{
		for ($i = 1; $i <= 3; $i++)
		{
			if (is_array($this->on_load_code[$i]))
			{
				$this->setCurrentBlock("on_load_code");
				foreach ($this->on_load_code[$i] as $code)
				{
					$this->setCurrentBlock("on_load_code_inner");
					$this->setVariable("OLCODE", $code);
					$this->parseCurrentBlock();
				}
				$this->setCurrentBlock("on_load_code");
				$this->parseCurrentBlock();
			}
		}
	}

}
?>
