<?php

/**
 * Create font table
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
 * Create font table
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
class CreateFontTable extends CreateElement
{
    /**
     *
     * @var string
     * @access protected
     */
    protected $_xml;

    /**
     *
     * @var CreateFontTable
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
     * @return CreateFontTable
     * @access public
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateFontTable();
        }
        return self::$_instance;
    }

    /**
     * Create font
     *
     * @access public
     * @param array args[0]
     */
    public function createFont()
    {
        $this->_xml = '';
        $args = func_get_args();
        if (!empty($args[0]['name']) && !empty($args[0]['pitch']) &&
            !empty($args[0]['usb0']) && !empty($args[0]['usb1']) &&
            !empty($args[0]['usb2']) && !empty($args[0]['usb3']) &&
            !empty($args[0]['csb0']) && !empty($args[0]['csb1']) &&
            !empty($args[0]['family']) && !empty($args[0]['charset']) &&
            !empty($args[0]['panose1'])
        ) {
            $this->generateFONT($args[0]['name']);
            $this->generatePANOSE1($args[0]['panose1']);
            $this->generateCHARSET($args[0]['charset']);
            $this->generateFAMILY($args[0]['family']);
            $this->generatePITCH($args[0]['pitch']);
            $this->generateSIG(
                $args[0]['usb0'], $args[0]['usb1'], $args[0]['usb2'],
                $args[0]['usb3'], $args[0]['csb0'], $args[0]['csb1']
            );
        } else {
            exit('You`re adding an empty font');
        }
    }

    /**
     * Generate w:font
     *
     * @param string $name
     * @access protected
     */
    protected function generateFONT($name = 'Calibri')
    {
        $this->_xml = '<' . CreateElement::NAMESPACEWORD . 
                       ':font ' . CreateDocx::NAMESPACEWORD .
                       ':name="' . $name .
                       '">__GENERATEFONT__</' . CreateElement::NAMESPACEWORD .
                       ':font>';
    }

    /**
     * Generate w:panose1
     *
     * @param string $val
     * @access protected
     */
    protected function generatePANOSE1($val = '020F0502020204030204')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
               ':panose1 ' . CreateElement::NAMESPACEWORD .
               ':val="' . $val .
               '"></' . CreateElement::NAMESPACEWORD .
               ':panose1>__GENERATEPANOSE1__';

        $this->_xml = str_replace('__GENERATEFONT__', $xml, $this->_xml);
    }

    /**
     * Generate w:charset
     *
     * @param string $val
     * @access protected
     */
    protected function generateCHARSET($val = '00')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
               ':charset ' . CreateElement::NAMESPACEWORD .
               ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
               ':charset>__GENERATECHARSET__';

        $this->_xml = str_replace('__GENERATEPANOSE1__', $xml, $this->_xml);
    }
    
    /**
     * Generate w:family
     *
     * @param string $val
     * @access protected
     */
    protected function generateFAMILY($val = 'swiss')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':family ' . CreateElement::NAMESPACEWORD .
                ':val="' . $val .
                '"></' . CreateElement::NAMESPACEWORD .
                ':family>__GENERATEFAMILY__';
        
        $this->_xml = str_replace('__GENERATECHARSET__', $xml, $this->_xml);
    }

    /**
     * Generate w:pitch
     *
     * @param string $val
     * @access protected
     */
    protected function generatePITCH($val = '00')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':pitch ' . CreateElement::NAMESPACEWORD .
                ':val="' . $val .
                '"></' . CreateElement::NAMESPACEWORD .
                ':pitch>__GENERATEPITCH__';
        
        $this->_xml = str_replace('__GENERATEFAMILY__', $xml, $this->_xml);
    }

    /**
     * Generate w:sig
     *
     * @param string $usbA
     * @param string $usbB
     * @param string $usbC
     * @param string $usbD
     * @param string $csbA
     * @param string $csbB
     * @access protected
     */
    protected function generateSIG($usbA = 'A00002EF', $usbB = '4000207B',
                                   $usbC = '00000000', $usbD = '00000000',
                                   $csbA = '0000009F', $csbB = '00000000')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
               ':sig ' . CreateElement::NAMESPACEWORD .
               ':usb0="' . $usbA . '" ' . CreateElement::NAMESPACEWORD .
               ':usb1="' . $usbB . '" ' . CreateElement::NAMESPACEWORD .
               ':usb2="' . $usbC . '" ' . CreateElement::NAMESPACEWORD .
               ':usb3="' . $usbD . '" ' . CreateElement::NAMESPACEWORD .
               ':csb0="' . $csbA . '" ' . CreateElement::NAMESPACEWORD .
               ':csb1="' . $csbB . '"></' . CreateElement::NAMESPACEWORD .
               ':sig>';

        $this->_xml = str_replace('__GENERATEPITCH__', $xml, $this->_xml);
    }
}
