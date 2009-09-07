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

/** PHPExcel */
require_once PHPEXCEL_ROOT . 'PHPExcel.php';

/** PHPExcel_Writer_Excel2007 */
require_once PHPEXCEL_ROOT . 'PHPExcel/Writer/Excel2007.php';

/** PHPExcel_Writer_Excel2007_WriterPart */
require_once PHPEXCEL_ROOT . 'PHPExcel/Writer/Excel2007/WriterPart.php';

/** PHPExcel_Shared_File */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/File.php';

/** PHPExcel_Shared_XMLWriter */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/XMLWriter.php';


/**
 * PHPExcel_Writer_Excel2007_ContentTypes
 *
 * @category   PHPExcel
 * @package    PHPExcel_Writer_Excel2007
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Writer_Excel2007_ContentTypes extends PHPExcel_Writer_Excel2007_WriterPart
{
	/**
	 * Write content types to XML format
	 *
	 * @param 	PHPExcel $pPHPExcel
	 * @return 	string 						XML Output
	 * @throws 	Exception
	 */
	public function writeContentTypes(PHPExcel $pPHPExcel = null)
	{
		// Create XML writer
		$objWriter = null;
		if ($this->getParentWriter()->getUseDiskCaching()) {
			$objWriter = new PHPExcel_Shared_XMLWriter(PHPExcel_Shared_XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
		} else {
			$objWriter = new PHPExcel_Shared_XMLWriter(PHPExcel_Shared_XMLWriter::STORAGE_MEMORY);
		}

		// XML header
		$objWriter->startDocument('1.0','UTF-8','yes');

		// Types
		$objWriter->startElement('Types');
		$objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/content-types');

			// Theme
			$this->_writeOverrideContentType(
				$objWriter, '/xl/theme/theme1.xml', 'application/vnd.openxmlformats-officedocument.theme+xml'
			);

			// Styles
			$this->_writeOverrideContentType(
				$objWriter, '/xl/styles.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml'
			);

			// Rels
			$this->_writeDefaultContentType(
				$objWriter, 'rels', 'application/vnd.openxmlformats-package.relationships+xml'
			);

			// XML
			$this->_writeDefaultContentType(
				$objWriter, 'xml', 'application/xml'
			);

			// VML
			$this->_writeDefaultContentType(
				$objWriter, 'vml', 'application/vnd.openxmlformats-officedocument.vmlDrawing'
			);

			// Workbook
			$this->_writeOverrideContentType(
				$objWriter, '/xl/workbook.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml'
			);

			// DocProps
			$this->_writeOverrideContentType(
				$objWriter, '/docProps/app.xml', 'application/vnd.openxmlformats-officedocument.extended-properties+xml'
			);

			$this->_writeOverrideContentType(
				$objWriter, '/docProps/core.xml', 'application/vnd.openxmlformats-package.core-properties+xml'
			);

			// Worksheets
			$sheetCount = $pPHPExcel->getSheetCount();
			for ($i = 0; $i < $sheetCount; ++$i) {
				$this->_writeOverrideContentType(
					$objWriter, '/xl/worksheets/sheet' . ($i + 1) . '.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml'
				);
			}

			// Shared strings
			$this->_writeOverrideContentType(
				$objWriter, '/xl/sharedStrings.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml'
			);

			// Add worksheet relationship content types
			for ($i = 0; $i < $sheetCount; ++$i) {
				if ($pPHPExcel->getSheet($i)->getDrawingCollection()->count() > 0) {
					$this->_writeOverrideContentType(
						$objWriter, '/xl/drawings/drawing' . ($i + 1) . '.xml', 'application/vnd.openxmlformats-officedocument.drawing+xml'
					);
				}
			}

			// Comments
			for ($i = 0; $i < $sheetCount; ++$i) {
				if (count($pPHPExcel->getSheet($i)->getComments()) > 0) {
					$this->_writeOverrideContentType(
						$objWriter, '/xl/comments' . ($i + 1) . '.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.comments+xml'
					);
				}
			}

			// Add media content-types
			$aMediaContentTypes = array();
			$mediaCount = $this->getParentWriter()->getDrawingHashTable()->count();
			for ($i = 0; $i < $mediaCount; ++$i) {
				$extension 	= '';
				$mimeType 	= '';
				
				if ($this->getParentWriter()->getDrawingHashTable()->getByIndex($i) instanceof PHPExcel_Worksheet_Drawing) {
					$extension 	= strtolower($this->getParentWriter()->getDrawingHashTable()->getByIndex($i)->getExtension());
					$mimeType 	= $this->_getImageMimeType( $this->getParentWriter()->getDrawingHashTable()->getByIndex($i)->getPath() );
				} else if ($this->getParentWriter()->getDrawingHashTable()->getByIndex($i) instanceof PHPExcel_Worksheet_MemoryDrawing) {
					$extension 	= strtolower($this->getParentWriter()->getDrawingHashTable()->getByIndex($i)->getMimeType());
					$extension 	= explode('/', $extension);
					$extension 	= $extension[1];
					
					$mimeType 	= $this->getParentWriter()->getDrawingHashTable()->getByIndex($i)->getMimeType();
				}
				
				if (!isset( $aMediaContentTypes[$extension]) ) {
						$aMediaContentTypes[$extension] = $mimeType;

						$this->_writeDefaultContentType(
							$objWriter, $extension, $mimeType
						);
				}
			}
			
			$sheetCount = $pPHPExcel->getSheetCount();
			for ($i = 0; $i < $sheetCount; ++$i) {
				if (count($pPHPExcel->getSheet()->getHeaderFooter()->getImages()) > 0) {
					foreach ($pPHPExcel->getSheet()->getHeaderFooter()->getImages() as $image) {
						if (!isset( $aMediaContentTypes[strtolower($image->getExtension())]) ) {
							$aMediaContentTypes[strtolower($image->getExtension())] = $this->_getImageMimeType( $image->getPath() );

							$this->_writeDefaultContentType(
								$objWriter, strtolower($image->getExtension()), $aMediaContentTypes[strtolower($image->getExtension())]
							);
						}
					}
				}
			}

		$objWriter->endElement();

		// Return
		return $objWriter->getData();
	}

	/**
	 * Get image mime type
	 *
	 * @param 	string	$pFile	Filename
	 * @return 	string	Mime Type
	 * @throws 	Exception
	 */
	private function _getImageMimeType($pFile = '')
	{
		if (PHPExcel_Shared_File::file_exists($pFile)) {
			$image = getimagesize($pFile);
			return image_type_to_mime_type($image[2]);
		} else {
			throw new Exception("File $pFile does not exist");
		}
	}

	/**
	 * Write Default content type
	 *
	 * @param 	PHPExcel_Shared_XMLWriter 	$objWriter 		XML Writer
	 * @param 	string 						$pPartname 		Part name
	 * @param 	string 						$pContentType 	Content type
	 * @throws 	Exception
	 */
	private function _writeDefaultContentType(PHPExcel_Shared_XMLWriter $objWriter = null, $pPartname = '', $pContentType = '')
	{
		if ($pPartname != '' && $pContentType != '') {
			// Write content type
			$objWriter->startElement('Default');
			$objWriter->writeAttribute('Extension', 	$pPartname);
			$objWriter->writeAttribute('ContentType', 	$pContentType);
			$objWriter->endElement();
		} else {
			throw new Exception("Invalid parameters passed.");
		}
	}

	/**
	 * Write Override content type
	 *
	 * @param 	PHPExcel_Shared_XMLWriter 	$objWriter 		XML Writer
	 * @param 	string 						$pPartname 		Part name
	 * @param 	string 						$pContentType 	Content type
	 * @throws 	Exception
	 */
	private function _writeOverrideContentType(PHPExcel_Shared_XMLWriter $objWriter = null, $pPartname = '', $pContentType = '')
	{
		if ($pPartname != '' && $pContentType != '') {
			// Write content type
			$objWriter->startElement('Override');
			$objWriter->writeAttribute('PartName', 		$pPartname);
			$objWriter->writeAttribute('ContentType', 	$pContentType);
			$objWriter->endElement();
		} else {
			throw new Exception("Invalid parameters passed.");
		}
	}
}
