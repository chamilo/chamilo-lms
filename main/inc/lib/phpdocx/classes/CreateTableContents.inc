<?php

/**
 * Create table of contents
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
 * Create table of contents
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
class CreateTableContents extends CreateElement
{
    /**
     *
     * @var string
     * @access protected
     */
    protected $_xml;

    /**
     *
     * @var CreateTableContents
     * @access protected
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
     * Magic method, returns current XML
     *
     * @access public
     * @return string Return current XML
     */
    public function __toString()
    {
        return $this->_xml;
    }

    /**
     *
     * @return CreateTableContents
     * @access public
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateTableContents();
        }
        return self::$_instance;
    }

    /**
     * Create table of contents
     *
     * @param string $font
     * @access public
     */
    public function createTableContents($font)
    {
        $this->generateP();
        $this->generateFLDSIMPLE();
        $this->generateR();
        $this->generateRPR();
        $this->generateRFONTS($font);
        $this->generateB();
        $this->generateBCS();
        $this->generateNOPROOF();
        $this->generateT('Press F9 to update table of contents.');
        $this->cleanTemplate();
    }

    /**
     * Generate w:fldsimple
     *
     * @param string $instr. Optional, 'TOC \o &quot;1-4&quot; \h \z \u'
     *                       as default
     * @access protected
     */
    protected function generateFLDSIMPLE($instr = 'TOC \o &quot;1-4&quot;
                                                \h \z \u')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
               ':fldSimple ' . CreateElement::NAMESPACEWORD .
               ':instr="' . $instr .
               '">__GENERATEP__</' . CreateElement::NAMESPACEWORD .
               ':fldSimple>';

        $this->_xml = str_replace('__GENERATEP__', $xml, $this->_xml);
    }
}
