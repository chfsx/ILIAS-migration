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
 * @package    PHPExcel_Style
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

/** PHPExcel_Style_Color */
require_once PHPEXCEL_ROOT . 'PHPExcel/Style/Color.php';

/** PHPExcel_IComparable */
require_once PHPEXCEL_ROOT . 'PHPExcel/IComparable.php';


/**
 * PHPExcel_Style_Fill
 *
 * @category   PHPExcel
 * @package    PHPExcel_Style
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Style_Fill implements PHPExcel_IComparable
{
	/* Fill types */
	const FILL_NONE							= 'none';
	const FILL_SOLID						= 'solid';
	const FILL_GRADIENT_LINEAR				= 'linear';
	const FILL_GRADIENT_PATH				= 'path';
	const FILL_PATTERN_DARKDOWN				= 'darkDown';
	const FILL_PATTERN_DARKGRAY				= 'darkGray';
	const FILL_PATTERN_DARKGRID				= 'darkGrid';
	const FILL_PATTERN_DARKHORIZONTAL		= 'darkHorizontal';
	const FILL_PATTERN_DARKTRELLIS			= 'darkTrellis';
	const FILL_PATTERN_DARKUP				= 'darkUp';
	const FILL_PATTERN_DARKVERTICAL			= 'darkVertical';
	const FILL_PATTERN_GRAY0625				= 'gray0625';
	const FILL_PATTERN_GRAY125				= 'gray125';
	const FILL_PATTERN_LIGHTDOWN			= 'lightDown';
	const FILL_PATTERN_LIGHTGRAY			= 'lightGray';
	const FILL_PATTERN_LIGHTGRID			= 'lightGrid';
	const FILL_PATTERN_LIGHTHORIZONTAL		= 'lightHorizontal';
	const FILL_PATTERN_LIGHTTRELLIS			= 'lightTrellis';
	const FILL_PATTERN_LIGHTUP				= 'lightUp';
	const FILL_PATTERN_LIGHTVERTICAL		= 'lightVertical';
	const FILL_PATTERN_MEDIUMGRAY			= 'mediumGray';

	/**
	 * Fill type
	 *
	 * @var string
	 */
	private $_fillType;
	
	/**
	 * Rotation
	 *
	 * @var double
	 */
	private $_rotation;
	
	/**
	 * Start color
	 * 
	 * @var PHPExcel_Style_Color
	 */
	private $_startColor;
	
	/**
	 * End color
	 * 
	 * @var PHPExcel_Style_Color
	 */
	private $_endColor;
	
	/**
	 * Parent Borders
	 *
	 * @var _parentPropertyName string
	 */
	private $_parentPropertyName;

	/**
	 * Supervisor?
	 *
	 * @var boolean
	 */
	private $_isSupervisor;

	/**
	 * Parent. Only used for supervisor
	 *
	 * @var PHPExcel_Style
	 */
	private $_parent;

    /**
     * Create a new PHPExcel_Style_Fill
     */
    public function __construct($isSupervisor = false)
    {
    	// Supervisor?
		$this->_isSupervisor = $isSupervisor;

    	// Initialise values
    	$this->_fillType			= PHPExcel_Style_Fill::FILL_NONE;
    	$this->_rotation			= 0;
		$this->_startColor			= new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_WHITE, $isSupervisor);
		$this->_endColor			= new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_BLACK, $isSupervisor);

		// bind parent if we are a supervisor
		if ($isSupervisor) {
			$this->_startColor->bindParent($this, '_startColor');
			$this->_endColor->bindParent($this, '_endColor');
		}
    }

	/**
	 * Bind parent. Only used for supervisor
	 *
	 * @param PHPExcel_Style $parent
	 * @return PHPExcel_Style_Fill
	 */
	public function bindParent($parent)
	{
		$this->_parent = $parent;
		return $this;
	}

	/**
	 * Is this a supervisor or a real style component?
	 *
	 * @return boolean
	 */
	public function getIsSupervisor()
	{
		return $this->_isSupervisor;
	}

	/**
	 * Get the shared style component for the currently active cell in currently active sheet.
	 * Only used for style supervisor
	 *
	 * @return PHPExcel_Style_Fill
	 */
	public function getSharedComponent()
	{
		return $this->_parent->getSharedComponent()->getFill();
	}

	/**
	 * Get the currently active sheet. Only used for supervisor
	 *
	 * @return PHPExcel_Worksheet
	 */
	public function getActiveSheet()
	{
		return $this->_parent->getActiveSheet();
	}

	/**
	 * Get the currently active cell coordinate in currently active sheet.
	 * Only used for supervisor
	 *
	 * @return string E.g. 'A1'
	 */
	public function getXSelectedCells()
	{
		return $this->getActiveSheet()->getXSelectedCells();
	}

	/**
	 * Get the currently active cell coordinate in currently active sheet.
	 * Only used for supervisor
	 *
	 * @return string E.g. 'A1'
	 */
	public function getXActiveCell()
	{
		return $this->getActiveSheet()->getXActiveCell();
	}

	/**
	 * Build style array from subcomponents
	 *
	 * @param array $array
	 * @return array
	 */
	public function getStyleArray($array)
	{
		return array('fill' => $array);
	}

    /**
     * Apply styles from array
     * 
     * <code>
     * $objPHPExcel->getActiveSheet()->getStyle('B2')->getFill()->applyFromArray(
     * 		array(
     * 			'type'       => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
     * 			'rotation'   => 0,
     * 			'startcolor' => array(
     * 				'rgb' => '000000'
     * 			),
     * 			'endcolor'   => array(
     * 				'argb' => 'FFFFFFFF'
     * 			)
     * 		)
     * );
     * </code>
     * 
     * @param	array	$pStyles	Array containing style information
     * @throws	Exception
     * @return PHPExcel_Style_Fill
     */
	public function applyFromArray($pStyles = null) {
		if (is_array($pStyles)) {
			if ($this->_isSupervisor) {
				$this->getActiveSheet()->getStyle($this->getXSelectedCells())->applyFromArray($this->getStyleArray($pStyles));
			} else {
				if (array_key_exists('type', $pStyles)) {
					$this->setFillType($pStyles['type']);
				}
				if (array_key_exists('rotation', $pStyles)) {
					$this->setRotation($pStyles['rotation']);
				}
				if (array_key_exists('startcolor', $pStyles)) {
					$this->getStartColor()->applyFromArray($pStyles['startcolor']);
				}
				if (array_key_exists('endcolor', $pStyles)) {
					$this->getEndColor()->applyFromArray($pStyles['endcolor']);
				}
				if (array_key_exists('color', $pStyles)) {
					$this->getStartColor()->applyFromArray($pStyles['color']);
				}
			}
		} else {
			throw new Exception("Invalid style array passed.");
		}
		return $this;
	}
    
    /**
     * Get Fill Type
     *
     * @return string
     */
    public function getFillType() {
		if ($this->_isSupervisor) {
			return $this->getSharedComponent()->getFillType();
		}
		return $this->_fillType;
    }
    
    /**
     * Set Fill Type
     *
     * @param string $pValue	PHPExcel_Style_Fill fill type
     * @return PHPExcel_Style_Fill
     */
    public function setFillType($pValue = PHPExcel_Style_Fill::FILL_NONE) {
		if ($this->_isSupervisor) {
			$styleArray = $this->getStyleArray(array('type' => $pValue));
			$this->getActiveSheet()->getStyle($this->getXSelectedCells())->applyFromArray($styleArray);
		} else {
			$this->_fillType = $pValue;
		}
		return $this;
    }
    
    /**
     * Get Rotation
     *
     * @return double
     */
    public function getRotation() {
		if ($this->_isSupervisor) {
			return $this->getSharedComponent()->getRotation();
		}
    	return $this->_rotation;
    }
    
    /**
     * Set Rotation
     *
     * @param double $pValue
     * @return PHPExcel_Style_Fill
     */
    public function setRotation($pValue = 0) {
		if ($this->_isSupervisor) {
			$styleArray = $this->getStyleArray(array('rotation' => $pValue));
			$this->getActiveSheet()->getStyle($this->getXSelectedCells())->applyFromArray($styleArray);
		} else {
			$this->_rotation = $pValue;
		}
		return $this;
    }
    
    /**
     * Get Start Color
     *
     * @return PHPExcel_Style_Color
     */
    public function getStartColor() {
    	return $this->_startColor;
    }
    
    /**
     * Set Start Color
     *
     * @param 	PHPExcel_Style_Color $pValue
     * @throws 	Exception
     * @return PHPExcel_Style_Fill
     */
    public function setStartColor(PHPExcel_Style_Color $pValue = null) {
		// make sure parameter is a real color and not a supervisor
		$color = $pValue->getIsSupervisor() ? $pValue->getSharedComponent() : $pValue;
		
		if ($this->_isSupervisor) {
			$styleArray = $this->getStartColor()->getStyleArray(array('argb' => $color->getARGB()));
			$this->getActiveSheet()->getStyle($this->getXSelectedCells())->applyFromArray($styleArray);
		} else {
			$this->_startColor = $color;
		}
		return $this;
    }
    
    /**
     * Get End Color
     *
     * @return PHPExcel_Style_Color
     */
    public function getEndColor() {
    	return $this->_endColor;
    }
    
    /**
     * Set End Color
     *
     * @param 	PHPExcel_Style_Color $pValue
     * @throws 	Exception
     * @return PHPExcel_Style_Fill
     */
    public function setEndColor(PHPExcel_Style_Color $pValue = null) {
		// make sure parameter is a real color and not a supervisor
		$color = $pValue->getIsSupervisor() ? $pValue->getSharedComponent() : $pValue;
		
		if ($this->_isSupervisor) {
			$styleArray = $this->getEndColor()->getStyleArray(array('argb' => $color->getARGB()));
			$this->getActiveSheet()->getStyle($this->getXSelectedCells())->applyFromArray($styleArray);
		} else {
			$this->_endColor = $color;
		}
		return $this;
    }

	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */	
	public function getHashCode() {
		if ($this->_isSupervisor) {
			return $this->getSharedComponent()->getHashCode();
		}
    	return md5(
    		  $this->getFillType()
    		. $this->getRotation()
    		. $this->getStartColor()->getHashCode()
    		. $this->getEndColor()->getHashCode()
    		. __CLASS__
    	);
    }
    
    /**
     * Hash index
     *
     * @var string
     */
    private $_hashIndex;
    
	/**
	 * Get hash index
	 * 
	 * Note that this index may vary during script execution! Only reliable moment is
	 * while doing a write of a workbook and when changes are not allowed.
	 *
	 * @return string	Hash index
	 */
	public function getHashIndex() {
		return $this->_hashIndex;
	}
	
	/**
	 * Set hash index
	 * 
	 * Note that this index may vary during script execution! Only reliable moment is
	 * while doing a write of a workbook and when changes are not allowed.
	 *
	 * @param string	$value	Hash index
	 */
	public function setHashIndex($value) {
		$this->_hashIndex = $value;
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
