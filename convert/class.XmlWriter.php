<?php

/**
* XML writer class
* 
* Simple class to simplify manual writing of xml documents.
* It only supports writing xml sequentially, because the xml document
* is saved in a string with no additional structure information.
* The author is responsible for wellformdness and validity
* of the xml document.
* 
* @author Matthias Rulinski <matthias.rulinski@mi.uni-koeln.de>
* @version $Id$
*/
class XmlWriter
{
	//-----------
	// properties
	//-----------
	//private
	var $xmlStr;
	// public
	var $version;
	var $outEnc;
	var $inEnc;
	var $dtdDef = "";
	var $stSheet = "";
	var $genCmt = "Generated by: ILIAS 3 XmlWriter";
	
	//-------
	//methods
	//-------
	// constructor ***
	function XmlWriter ($version = "1.0", $outEnc = "utf-8", $inEnc = "iso-8859-1")
	{
		// initialize xml string
		$this->xmlStr = "";
		
		// set properties ***
		$this->version = $version;
		$this->outEnc = $outEnc;
		$this->inEnc = $inEnc;
	}
	
	// destructor ***
	function _XmlWriter ()
	{
		// terminate xml string
		unset($this->xmlStr);
	}
	
	function xmlSetDtdDef ($dtdDef)
	{
		$this->dtdDef = $dtdDef;
	}
	
	function xmlSetStSheet ($stSheet)
	{
		$this->stSheet = $stSheet;
	}
	
	function xmlSetGenCmt ($genCmt)
	{
		$this->genCmt = $genCmt;
	}
	
	// private
	function xmlEscapeData($data)
	{
		$position = 0;
		$length = strlen($data);
		$escapedData = "";
		
		for(; $position < $length;)
		{
			$character = substr($data, $position, 1);
			$code = Ord($character);
			
			switch($code)
			{
				case 34:
					$character = "&quot;";
					break;
				
				case 38:
					$character = "&amp;";
					break;
				
				case 39:
					$character = "&apos;";
					break;
				
				case 60:
					$character = "&lt;";
					break;
				
				case 62:
					$character = "&gt;";
					break;
				
				default:
					if ($code < 32)
					{
						$character = ("&#".strval($code).";");
					}
					break;
			}
			
			$escapedData .= $character;
			$position ++;
		}
		return $escapedData;
	}
	
	// private
	function xmlEncodeData($data)
	{
		if ($this->inEnc == $this->outEnc)
		{
			$encodedData = $data;
		}
		else
		{
			switch(strtolower($this->outEnc))
			{
				case "utf-8":
					if(strtolower($this->inEnc) == "iso-8859-1")
					{
						$encodedData = utf8_encode($data);
					}
					else
					{
						die ("ERROR: Can not encode iso-8859-1 data in ".$this->outEnc."."); // ***
					}
					break;
				
				case "iso-8859-1":
					if(strtolower($this->inEnc) == "utf-8")
					{
						$encodedData = utf8_decode($data);
					}
					else
					{
						die ("ERROR: Can not encode utf-8 data in ".$this->outEnc."."); // ***
					}
					break;
					
				default:
					die ("ERROR: Can not encode ".$this->inEnc." data in ".$this->outEnc."."); // ***
			}
		}
		return $encodedData;
	}
	
	// private
	function xmlFormatData($data)
	{
		// regular expression for tags
		$formatedXml = preg_replace_callback("|<[^>]*>[^<]*|", array($this, "xmlFormatElement"), $data);
		
		return $formatedXml;
	}
	
	// private
	// callback function for xml_format. Do not invoke directly
	function xmlFormatElement($array)
	{
		// ***
		$found = trim($array[0]);
		
		// ***
		static $indent;
		
		// linebreak (default)
		$nl = "\n";
		
		// ***
		$tab = str_repeat(" ", $indent * 2);
		
		// closing tag
		if (substr($found, 0, 2) == "</")
		{
			$indent --;
			$tab = str_repeat(" ", $indent * 2);
		}
		elseif (substr($found, -2, 1) == "/" or // opening and closing, comment, ...
				strpos($found, "/>") or
				substr($found, 0, 2) == "<!") 
		{
			// do not change indent
		}
		elseif (substr($found, 0, 2) == "<?") 
		{
			// do not change indent
			// no linebreak
			$nl = "";
		}
		else // opening tag
		{
			$indent ++;
		}
		
		// content
		if (substr($found, -1) != ">")
		{
			$found = str_replace(">", ">\n".str_repeat(" ", ($indent + 0) * 2), $found);
		}
		
		return $nl.$tab.$found;
	}
	
	// write xml header
	function xmlHeader()
	{
		// version and encoding
		$this->xmlStr .= "<?xml version=\"".$this->version."\" encoding=\"".$this->outEnc."\"?>";
		
		// dtd definition
		if ($this->dtdDef <> "")
		{
			$this->xmlStr .= $this->dtdDef; // ***
		}
		
		// stSheet
		if ($this->stSheet <> "")
		{
			$this->xmlStr .= $this->stSheet;  // ***
		}
		
		// generated comment
		if ($this->genCmt <> "")
		{
			$this->xmlComment($this->genCmt);
		}
		
		return $xmlStr;
	}
	
	// write starttag
	// takes an array of attributes (name => value)
	function xmlStartTag ($tag, $attrs = NULL, $empty = FALSE, $encode = TRUE, $escape = TRUE)
	{
		// write first part of the starttag
		$this->xmlStr .= "<".$tag;
		
		// check for existing attributes
		if (is_array($attrs))
		{
			// write attributes
			foreach ($attrs as $name => $value)
			{
				// encode
				if ($encode)
				{
		    		$value = $this->xmlEncodeData($value);
				}
				
				// escape
				if ($escape)
				{
	    			 $value = $this->xmlEscapeData($value);
	    		}
				
				$this->xmlStr .= " ".$name."=\"".$value."\"";
			}
		}
		
		// write last part of the starttag
		if ($empty)
		{
			$this->xmlStr .= "/>";
		}
		else
		{
			$this->xmlStr .= ">";
		}
	}
	
	// write endtag
	function xmlEndTag ($tag)
	{
		$this->xmlStr .= "</".$tag.">";
	}
	
	// write comment
	function xmlComment ($comment)
	{
		$this->xmlStr .= "<!--".$comment."-->";
	}
	
	// write data (element's content)
	function xmlData ($data, $encode = TRUE, $escape = TRUE)
	{
		// encode
		if ($encode)
		{
		    $data = $this->xmlEncodeData($data);
		}
		
		// escape
		if ($escape)
		{
	    	 $data = $this->xmlEscapeData($data);
	    }
		
		$this->xmlStr .= $data;
	}
	
	// write basic element (not including any other elements, just textual content!!!)
	// takes an array of attributes (name => value)
	function xmlElement ($tag, $attrs = NULL, $data = Null, $encode = TRUE, $escape = TRUE)
	{
		// check for existing data (element's content)
		if (is_string($data) or
			is_integer($data))
		{
			// write starttag
			$this->xmlStartTag($tag, $attrs);
			
			// write text
			$this->xmlData($data, $escape, $encode);
			
			// write endtag
			$this->xmlEndTag($tag);
		}
		else // no data
		{
			// write starttag (= empty tag)
			$this->xmlStartTag($tag, $attrs, TRUE);
		}
	}
	
	// $file = full path
	function xmlDumpFile($file, $format = TRUE)
	{
		// open file
		if (!($fp = @fopen($file,"w+")))
		{
			die ("ERROR: Could not open \"".$file."\" for writing."); // ***
		}
		
		// set file permissions
		chmod($file, 0770);
		
		// format xml data
		if ($format)
		{
			$xmlStr = $this->xmlFormatData($this->xmlStr);
		}
		else
		{
			$xmlStr = $this->xmlStr;
		}
		
		// write xml data into the file
		fwrite($fp, $xmlStr);
		
		// close file
		fclose($fp);
	}
	
	// $file = full path
	function xmlDumpMem($format = TRUE)
	{
		// format xml data
		if ($format)
		{
			$xmlStr = $this->xmlFormatData($this->xmlStr);
		}
		else
		{
			$xmlStr = $this->xmlStr;
		}
		
		return $xmlStr;
	}
}

?>