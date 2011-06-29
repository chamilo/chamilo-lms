<?php

/**
 * Create excel sheet
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) 2009-2011 Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    LGPL
 * @version    1.0
 * @link       http://www.phpdocx.com
 * @since      File available since Release 1.0
 */
include_once dirname(__FILE__) . '/CreateElement.inc';

/**
 * Create excel sheet
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) 2009-2011 Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    http://www.phpdocx.com/wp-content/themes/lightword/pro_license.php
 * @version    1.0
 * @link       http://www.phpdocx.com
 * @since      Class available since Release 1.0
 */
class CreateExcelSheet extends CreateElement
{
    /**
     *
     * @var CreateExcelSheet
     * @access private
     * @static
     */
    private static $_instance = NULL;

    /**
     * Construct
     *
     * @access public
     */
    public function __construct()
    {

    }

    /**
     * Destruct
     *
     * @access public
     */
    public function __destruct()
    {

    }
    
    /**
     *
     * @return string
     * @access public
     */
    public function __toString()
    {
        return $this->_xml;
    }

    /**
     *
     * @return CreateExcelSheet
     * @access public
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateExcelSheet();
        }
        return self::$_instance;
    }

    /**
     * Create excel sheet
     *
     * @access public
     * @param string $args[0]
     * @param array $args[1]
     */
    public function createExcelSheet()
    {
        $this->_xml = '';
        $args = func_get_args();
        $type = $args[1];
        $dats = $args[0];
        $sizeDats = count($dats);
        foreach ($dats as $ind => $data) {
            $sizeCols = count($data);
            break;
        }
        $sizeDats = count($dats);
        if (strpos($type, 'pie') !== false) {
            $sizeCols = 1;
        } else {
            $sizeDats--;
        }
        $this->generateWORKSHEET();
        $this->generateDIMENSION($sizeDats, $sizeCols);
        $this->generateSHEETVIEWS();
        $this->generateSHEETVIEW();
        $this->generateSELECTION($sizeDats + $sizeCols);
        $this->generateSHEETFORMATPR();
        $this->generateCOLS();
        $this->generateCOL();
        $this->generateSHEETDATA();
        $row = 1;
        foreach ($dats as $ind => $val) {
            $col = 1;
            $letra = 'A';
            $this->generateROW($row, $sizeCols);
            $this->generateC($letra . $row, '', 's');
            $this->generateV($sizeDats + $sizeCols);
            $letra++;
            foreach ($val as $valores) {
                $this->generateC($letra . $row, '', 's');
                $this->generateV($col - 1);
                $col++;
                $letra++;
            }
            $this->cleanTemplateROW();
            $row++;
            break;
        }
        foreach ($dats as $ind => $val) {
            if ($ind == '0')
                continue;
            $this->generateROW($row, $sizeCols);
            $col = 1;
            $letra = 'A';
            $this->generateC($letra . $row, 1, 's');
            $this->generateV($sizeCols + $row - 2);
            $letra++;
            foreach ($val as $valores) {
                $s = '';
                if ($col != $sizeCols)
                    $s = 1;
                $this->generateC($letra . $row, $s);
                $this->generateV($valores);
                $col++;
                $letra++;
            }
            $row++;
            $this->cleanTemplateROW();
        }
        $this->generateROW($row + 1, $sizeCols);
        $row++;
        $this->generateC('B' . $row, 2, 's');
        $this->generateV($sizeDats + $sizeCols + 1);
        $this->generatePAGEMARGINS();
        $this->generateTABLEPARTS();
        $this->generateTABLEPART(1);
        $this->cleanTemplate();
    }

    /**
     * Generate worksheet
     *
     * @access protected
     */
    protected function generateWORKSHEET()
    {
        $this->_xml = '<?xml version="1.0" encoding="UTF-8" ' . 
                      'standalone="yes" ?><worksheet ' .
                      'xmlns="http://schemas.openxmlformats.org/' .
                      'spreadsheetml/2006/main" ' . 'xmlns:r="http://schemas.' .
                      'openxmlformats.org/officeDocument/2006/relationships"' .
                      '>__GENERATEWORKSHEET__</worksheet>';
    }

    /**
     * Generate dimension
     *
     * @param int $sizeX
     * @param int $sizeY
     * @access protected
     */
    protected function generateDIMENSION($sizeX, $sizeY)
    {
        $char = 'A';
        for ($i = 0; $i < $sizeY; $i++)
            $char++;
        $sizeX += $sizeY;
        $xml = '<dimension ref="A1:' . $char . $sizeX .
                '"></dimension>__GENERATEWORKSHEET__';
        
        $this->_xml = str_replace('__GENERATEWORKSHEET__', $xml, $this->_xml);
    }

    /**
     * Generate sheetviews
     *
     * @access protected
     */
    protected function generateSHEETVIEWS()
    {
        $xml = '<sheetViews>__GENERATESHEETVIEWS__' .
            '</sheetViews>__GENERATEWORKSHEET__';
        $this->_xml = str_replace('__GENERATEWORKSHEET__', $xml, $this->_xml);
    }

    /**
     * Generate sheetview
     *
     * @param string $tabSelected
     * @param string $workbookViewId
     * @access protected
     */
    protected function generateSHEETVIEW($tabSelected = '1',
                                         $workbookViewId = '0')
    {
        $xml = '<sheetView tabSelected="' . $tabSelected .
                '" workbookViewId="' . $workbookViewId .
                '">__GENERATESHEETVIEW__</sheetView>';
        
        $this->_xml = str_replace('__GENERATESHEETVIEWS__', $xml, $this->_xml);
    }

    /**
     * Generate selection
     *
     * @param string $num
     * @access protected
     */
    protected function generateSELECTION($num)
    {
        $xml = '<selection activeCell="B' . $num . 
                '" sqref="B' . $num . '"></selection>';
        
        $this->_xml = str_replace('__GENERATESHEETVIEW__', $xml, $this->_xml);
    }

    /**
     * Generate sheetformatpr
     *
     * @param string $baseColWidth
     * @param string $defaultRowHeight
     * @access protected
     */
    protected function generateSHEETFORMATPR($baseColWidth = '10',
                                             $defaultRowHeight = '15')
    {
        $xml = '<sheetFormatPr baseColWidth="' . $baseColWidth .
                '" defaultRowHeight="' . $defaultRowHeight .
                '"></sheetFormatPr>__GENERATEWORKSHEET__';

        $this->_xml = str_replace('__GENERATEWORKSHEET__', $xml, $this->_xml);
    }

    /**
     * Generate cols
     *
     * @access protected
     */
    protected function generateCOLS()
    {
        $xml = '<cols>__GENERATECOLS__</cols>__GENERATEWORKSHEET__';
        $this->_xml = str_replace('__GENERATEWORKSHEET__', $xml, $this->_xml);
    }

    /**
     * Generate col
     *
     * @param string $min
     * @param string $max
     * @param string $width
     * @param string $customWidth
     * @access protected
     */
    protected function generateCOL($min = '1', $max = '1',
                                   $width = '11.85546875', $customWidth = '1')
    {
        $xml = '<col min="' . $min . '" max="' . $max . '" width="' . $width . 
                '" customWidth="' . $customWidth . '"></col>';

        $this->_xml = str_replace('__GENERATECOLS__', $xml, $this->_xml);
    }

    /**
     * Generate sheetdata
     *
     * @access protected
     */
    protected function generateSHEETDATA()
    {
        $xml = '<sheetData>__GENERATESHEETDATA__</sheetData>' .
            '__GENERATEWORKSHEET__';
        $this->_xml = str_replace('__GENERATEWORKSHEET__', $xml, $this->_xml);
    }

    /**
     * Generate row
     *
     * @param string $r
     * @param string $spans
     * @access protected
     */
    protected function generateROW($r, $spans)
    {
        $spans = '1:' . ($spans + 1);
        $xml = '<row r="' . $r . '" spans="' . $spans . 
                '">__GENERATEROW__</row>__GENERATESHEETDATA__';

        $this->_xml = str_replace('__GENERATESHEETDATA__', $xml, $this->_xml);
    }

    /**
     * Generate c
     *
     * @param string $r
     * @param string $s
     * @param string $t
     * @access protected
     */
    protected function generateC($r, $s, $t = '')
    {
        $xmlAux = '<c r="' . $r . '"';
        if ($s != '') {
            $xmlAux .= ' s="' . $s . '"';
        }
        if ($t != '') {
            $xmlAux .= ' t="' . $t . '"';
        }
        $xmlAux .= '>__GENERATEC__</c>__GENERATEROW__';
        $this->_xml = str_replace('__GENERATEROW__', $xmlAux, $this->_xml);
    }

    /**
     * Generate v
     *
     * @param string $num
     * @access protected
     */
    protected function generateV($num)
    {
        $this->_xml = str_replace(
            '__GENERATEC__', '<v>' . $num . '</v>', $this->_xml
        );
    }

    /**
     * Generate pagemargins
     *
     * @param string $left
     * @param string $rigth
     * @param string $bottom
     * @param string $top
     * @param string $header
     * @param string $footer
     * @access protected
     */
    protected function generatePAGEMARGINS($left = '0.7', $rigth = '0.7',
                                           $bottom = '0.75', $top = '0.75',
                                           $header = '0.3', $footer = '0.3')
    {
        $xml = '<pageMargins left="' . $left . '" right="' . $rigth . 
                '" top="' . $top . '" bottom="' . $bottom .
                '" header="' . $header . '" footer="' . $footer .
                '"></pageMargins>__GENERATEWORKSHEET__';
        
        $this->_xml = str_replace('__GENERATEWORKSHEET__', $xml, $this->_xml);
    }

    /**
     * Generate pagesetup
     *
     * @param string $paperSize
     * @param string $orientation
     * @param string $id
     * @access protected
     */
    protected function generatePAGESETUP($paperSize = '9',
                                         $orientation = 'portrait', $id = '1')
    {
        $xml = '<pageSetup paperSize="' . $paperSize .
                '" orientation="' . $orientation . '" r:id="rId' . $id .
                '"></pageSetup>__GENERATEWORKSHEET__';
        
        $this->_xml = str_replace('__GENERATEWORKSHEET__', $xml, $this->_xml);
    }

    /**
     * Generate tableparts
     *
     * @param string $count
     * @access protected
     */
    protected function generateTABLEPARTS($count = '1')
    {
        $xml = '<tableParts count="' . $count . 
                '">__GENERATETABLEPARTS__</tableParts>';

        $this->_xml = str_replace('__GENERATEWORKSHEET__', $xml, $this->_xml);
    }

    /**
     * Generate tablepart
     *
     * @param string $id
     * @access protected
     */
    protected function generateTABLEPART($id = '1')
    {
        $xml = '<tablePart r:id="rId' . $id . '"></tablePart>';
        $this->_xml = str_replace('__GENERATETABLEPARTS__', $xml, $this->_xml);
    }

    /**
     * Clean template row tags
     *
     * @access private
     */
    private function cleanTemplateROW()
    {
        $this->_xml = str_replace('__GENERATEROW__', '', $this->_xml);
    }
}
