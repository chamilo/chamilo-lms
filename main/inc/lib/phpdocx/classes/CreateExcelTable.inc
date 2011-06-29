<?php

/**
 * Create excel table
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
 * Create excel table
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) 2009-2011 Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    http://www.phpdocx.com/wp-content/themes/lightword/pro_license.php
 * @version    1.0
 * @link       http://www.phpdocx.com
 * @since      File available since Release 1.0
 */
class CreateExcelTable extends CreateElement
{
    /**
     *
     * @var CreateExcelTable
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
     * @return CreateExcelTable
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateExcelTable();
        }
        return self::$_instance;
    }

    /**
     * Create excel table
     * 
     * @access public
     * @param string args[0]
     * @param string args[1]
     */
    public function createExcelTable()
    {
        $this->_xml = '';
        $args = func_get_args();
        $type = $args[1];
        $dats = $args[0];
        $tamDatos = count($dats);
        foreach ($dats as $ind => $data) {
            $tamCols = count($data);
            break;
        }
        $tamDatos = count($dats);
        if (strpos($type, 'pie') !== false) {
            $tamCols = 1;
        } else {
            $tamDatos--;
        }
        $this->generateTABLE($tamDatos, $tamCols);
        $this->generateTABLECOLUMNS($tamCols + 1);
        $this->generateTABLECOLUMN(1, ' ');
        for ($i = 0; $i < $tamCols; $i++) {
            if (strpos($type, 'pie') !== false) {
                $this->generateTABLECOLUMN($i + 2, '0');
                break;
            }else
                $this->generateTABLECOLUMN($i + 2, $dats[0][$i]);
        }
        $this->generateTABLESTYLEINFO();
        $this->cleanTemplate();
    }


    /**
     * Generate table
     *
     * @param int $rows
     * @param int $cols
     * @access protected
     */
    protected function generateTABLE($rows, $cols)
    {
        $chart = 'A';
        for ($i = 0; $i < $cols; $i++)
            $chart++;
        $rows++;
        $this->_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
                      '<table xmlns="http://schemas.openxmlformats.org/spreads'.
                      'heetml/2006/main" id="1" name="Tabla1" displayName='.
                      '"Tabla1" ref="A1:' . $chart . $rows .
                      '" totalsRowShown="0" tableBorderDxfId="0">'.
                      '__GENERATETABLE__</table>';
    }

    /**
     * Generate tablecolumns
     *
     * @param string $count
     * @access protected
     */
    protected function generateTABLECOLUMNS($count = '2')
    {
        $xml = '<tableColumns count="' . $count .
               '">__GENERATETABLECOLUMNS__</tableColumns>__GENERATETABLE__';
        
        $this->_xml = str_replace('__GENERATETABLE__', $xml, $this->_xml);
    }

    /**
     * Generate tablecolumn
     *
     * @param string $id
     * @param string $name
     * @access protected
     */
    protected function generateTABLECOLUMN($id = '2', $name = '')
    {
        $xml =  '<tableColumn id="' . $id . '" name="' . $name .
                '"></tableColumn >__GENERATETABLECOLUMNS__';
        
        $this->_xml = str_replace(
            '__GENERATETABLECOLUMNS__', $xml, $this->_xml
        );
    }

    /**
     * Generate tablestyleinfo
     *
     * @param string $showFirstColumn
     * @param string $showLastColumn
     * @param string $showRowStripes
     * @param string $showColumnStripes
     * @access protected
     */
    protected function generateTABLESTYLEINFO($showFirstColumn = '0',
                                               $showLastColumn  = "0",
                                               $showRowStripes  = "1",
                                               $showColumnStripes = "0")
    {
        $xml = '<tableStyleInfo   showFirstColumn="' . $showFirstColumn .
               '" showLastColumn="' . $showLastColumn .
               '" showRowStripes="' . $showRowStripes .
               '" showColumnStripes="' . $showColumnStripes .
               '"></tableStyleInfo >';
        
        $this->_xml = str_replace('__GENERATETABLE__', $xml, $this->_xml);
    }

    

}