<?php

/**
 * Generate excel shared strings
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
 * Generate excel shared strings
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
class CreateExcelSharedStrings extends CreateElement
{
    /**
     *
     * @var CreateExcelSharedStrings
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
     * @return CreateExcelSharedStrings
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateExcelSharedStrings();
        }
        return self::$_instance;
    }

    /**
     * Create excel shared strings
     *
     * @param string $args[0]
     * @param string $args[1]
     * @access public
     */
    public function createExcelSharedStrings()
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
        $this->generateSST($tamDatos + $tamCols + 2);


        for ($i = 0; $i < $tamCols; $i++) {
            if (strpos($type, 'pie') !== false) {
                $this->generateSI();
                $this->generateT('0');
                break;
            }else
                $this->generateSI();
            $this->generateT($dats[0][$i]);
        }

        foreach ($dats as $ind => $val) {
            if ($ind == '0')
                continue;
            $this->generateSI();
            $this->generateT($ind);
        }
        $this->generateSI();
        $this->generateT(' ', 'preserve');

        $msg = 'To change the range size of values,
                drag the bottom right corner';
        $this->generateSI();
        $this->generateT($msg);

        $this->cleanTemplate();
    }

    /**
     * Generate sst
     *
     * @param string $num
     * @access protected
     */
    protected function generateSST($num)
    {
        $this->_xml = '<?xml version="1.0" encoding="UTF-8" '.
                      'standalone="yes" ?><sst xmlns="http://schemas.'.
                      'openxmlformats.org/spreadsheetml/2006/main" '.
                      'count="' . $num . '" uniqueCount="' . $num .
                      '">__GENERATESST__</sst>';
    }

    /**
     * Generate si
     * @access protected
     */
    protected function generateSI()
    {
        $xml = '<si>__GENERATESI__</si>__GENERATESST__';
        $this->_xml = str_replace('__GENERATESST__', $xml, $this->_xml);
    }

    /**
     * Generate t
     *
     * @param string $name
     * @param string $space
     * @access protected
     */
    protected function generateT($name, $space = '')
    {
        $xmlAux = '<t';
        if ($space != '')
            $xmlAux .= ' xml:space="' . $space . '"';
        $xmlAux .= '>' . $name . '</t>';
        $this->_xml = str_replace('__GENERATESI__', $xmlAux, $this->_xml);
    }
}