<?php
/**
 * PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel_RichText
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.0, 2009-08-10
 */


/** PHPExcel root directory */
if (!defined('PHPEXCEL_ROOT')) {
	/**
	 * @ignore
	 */
	define('PHPEXCEL_ROOT', dirname(__FILE__) . '/../../');
}

/** PHPExcel_RichText_ITextElement */
require_once PHPEXCEL_ROOT . 'PHPExcel/RichText/ITextElement.php';

/** PHPExcel_RichText_TextElement */
require_once PHPEXCEL_ROOT . 'PHPExcel/RichText/TextElement.php';

/** PHPExcel_Style_Font */
require_once PHPEXCEL_ROOT . 'PHPExcel/Style/Font.php';


/**
 * PHPExcel_RichText_Run
 *
 * @category   PHPExcel
 * @package    PHPExcel_RichText
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_RichText_Run extends PHPExcel_RichText_TextElement implements PHPExcel_RichText_ITextElement
{	
	/**
	 * Font
	 *
	 * @var PHPExcel_Style_Font
	 */
	private $_font;
	
    /**
     * Create a new PHPExcel_RichText_Run instance
     *
     * @param 	string		$pText		Text
     */
    public function __construct($pText = '')
    {
    	// Initialise variables
    	$this->setText($pText);
    	$this->_font = new PHPExcel_Style_Font();
    }
	
	/**
	 * Get font
	 *
	 * @return PHPExcel_Style_Font
	 */	
	public function getFont() {
		return $this->_font;
	}
	
	/**
	 * Set font
	 *
	 * @param	PHPExcel_Style_Font		$pFont		Font
	 * @throws 	Exception
	 * @return PHPExcel_RichText_ITextElement
	 */	
	public function setFont(PHPExcel_Style_Font $pFont = null) {
		$this->_font = $pFont;
		return $this;
	}
	
	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */	
	public function getHashCode() {
    	return md5(
    		  $this->getText()
    		. $this->_font->getHashCode()
    		. __CLASS__
    	);
    }
	    
	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone() {
		$vars = get_object_vars($this);
		foreach ($vars as $key => $value) {
			if (is_object($value)) {
				$this->$key = clone $value;
			} else {
				$this->$key = $value;
			}
		}
	}
}
