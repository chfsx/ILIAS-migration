<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2009 PHPExcel
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
 * @package    PHPExcel_Worksheet
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.0, 2009-08-10
 */


/**
 * PHPExcel_Worksheet_ColumnDimension
 *
 * @category   PHPExcel
 * @package    PHPExcel_Worksheet
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Worksheet_ColumnDimension
{			
	/**
	 * Column index
	 *
	 * @var int
	 */
	private $_columnIndex;
	
	/**
	 * Column width
	 *
	 * When this is set to a negative value, the column width should be ignored by IWriter
	 *
	 * @var double
	 */
	private $_width;
	
	/**
	 * Auto size?
	 *
	 * @var bool
	 */
	private $_autoSize;
	
	/**
	 * Visible?
	 *
	 * @var bool
	 */
	private $_visible;
	
	/**
	 * Outline level
	 *
	 * @var int
	 */
	private $_outlineLevel = 0;
	
	/**
	 * Collapsed
	 *
	 * @var bool
	 */
	private $_collapsed;
		
    /**
     * Create a new PHPExcel_Worksheet_RowDimension
     *
     * @param string $pIndex Character column index
     */
    public function __construct($pIndex = 'A')
    {
    	// Initialise values
    	$this->_columnIndex		= $pIndex;
    	$this->_width			= -1;
    	$this->_autoSize		= false;
    	$this->_visible			= true;
    	$this->_outlineLevel	= 0;
    	$this->_collapsed		= false;
    }
    
    /**
     * Get ColumnIndex
     *
     * @return string
     */
    public function getColumnIndex() {
    	return $this->_columnIndex;
    }
    
    /**
     * Set ColumnIndex
     *
     * @param string $pValue
     * @return PHPExcel_Worksheet_ColumnDimension
     */
    public function setColumnIndex($pValue) {
    	$this->_columnIndex = $pValue;
    	return $this;
    }
    
    /**
     * Get Width
     *
     * @return double
     */
    public function getWidth() {
    	return $this->_width;
    }
    
    /**
     * Set Width
     *
     * @param double $pValue
     * @return PHPExcel_Worksheet_ColumnDimension
     */
    public function setWidth($pValue = -1) {
    	$this->_width = $pValue;
    	return $this;
    }
    
    /**
     * Get Auto Size
     *
     * @return bool
     */
    public function getAutoSize() {
    	return $this->_autoSize;
    }
    
    /**
     * Set Auto Size
     *
     * @param bool $pValue
     * @return PHPExcel_Worksheet_ColumnDimension
     */
    public function setAutoSize($pValue = false) {
    	$this->_autoSize = $pValue;
    	return $this;
    }
    
    /**
     * Get Visible
     *
     * @return bool
     */
    public function getVisible() {
    	return $this->_visible;
    }
    
    /**
     * Set Visible
     *
     * @param bool $pValue
     * @return PHPExcel_Worksheet_ColumnDimension
     */
    public function setVisible($pValue = true) {
    	$this->_visible = $pValue;
    	return $this;
    }
    
    /**
     * Get Outline Level
     *
     * @return int
     */
    public function getOutlineLevel() {
    	return $this->_outlineLevel;
    }
    
    /**
     * Set Outline Level
     *
     * Value must be between 0 and 7
     *
     * @param int $pValue
     * @throws Exception
     * @return PHPExcel_Worksheet_ColumnDimension
     */
    public function setOutlineLevel($pValue) {
    	if ($pValue < 0 || $pValue > 7) {
    		throw new Exception("Outline level must range between 0 and 7.");
    	}
    	
    	$this->_outlineLevel = $pValue;
    	return $this;
    }
    
    /**
     * Get Collapsed
     *
     * @return bool
     */
    public function getCollapsed() {
    	return $this->_collapsed;
    }
    
    /**
     * Set Collapsed
     *
     * @param bool $pValue
     * @return PHPExcel_Worksheet_ColumnDimension
     */
    public function setCollapsed($pValue = true) {
    	$this->_collapsed = $pValue;
    	return $this;
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
