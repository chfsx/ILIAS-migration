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
 * @package    PHPExcel_Writer_Excel2007
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.0, 2009-08-10
 */


/** PHPExcel root directory */
if (!defined('PHPEXCEL_ROOT')) {
	/**
	 * @ignore
	 */
	define('PHPEXCEL_ROOT', dirname(__FILE__) . '/../../../');
}

/** PHPExcel_IWriter */
require_once PHPEXCEL_ROOT . 'PHPExcel/Writer/IWriter.php';


/**
 * PHPExcel_Writer_Excel2007_WriterPart
 *
 * @category   PHPExcel
 * @package    PHPExcel_Writer_Excel2007
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
abstract class PHPExcel_Writer_Excel2007_WriterPart
{
	/**
	 * Parent IWriter object
	 *
	 * @var PHPExcel_Writer_IWriter
	 */
	private $_parentWriter;
	
	/**
	 * Set parent IWriter object
	 *
	 * @param PHPExcel_Writer_IWriter	$pWriter
	 * @throws Exception
	 */
	public function setParentWriter(PHPExcel_Writer_IWriter $pWriter = null) {
		$this->_parentWriter = $pWriter;
	}
	
	/**
	 * Get parent IWriter object
	 *
	 * @return PHPExcel_Writer_IWriter
	 * @throws Exception
	 */
	public function getParentWriter() {
		if (!is_null($this->_parentWriter)) {
			return $this->_parentWriter;
		} else {
			throw new Exception("No parent PHPExcel_Writer_IWriter assigned.");
		}
	}
}
