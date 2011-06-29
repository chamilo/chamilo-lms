<?php

/**
 * Create header
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
 * Create header
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
class CreateHeader extends CreateElement
{
    /**
     *
     * @var CreateFooter
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
     * @return CreateHeader
     * @access public
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateHeader();
        }
        return self::$_instance;
    }

    /**
     * Generate w:p
     *
     * @access protected
     */
    protected function generateP()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':p>__GENERATEP__</' . CreateElement::NAMESPACEWORD .
                ':p>__GENERATEHDR__';

        $this->_xml = str_replace('__GENERATEHDR__', $xml, $this->_xml);
    }

    /**
     * Generate hdr token
     *
     * @access protected
     */
    protected function generateHDR()
    {
        //$this->_xml = '__GENERATEHDR____GENERATEHDRIMG__';
        $this->_xml = '__GENERATEHDR__';
    }

    /**
     * Create header
     *
     * @access public
     * @param string args[0]
     * @param array args[1]
     */
    public function createHeader()
    {
        $this->_xml = '';
        $args = func_get_args();

        $text = CreateText::getInstance();
        $text->createText($args[0], $args[1]);
        $this->generateHDR();
        $this->_xml = str_replace(
//            '__GENERATEHDR__', (string) $text . '__GENERATEHDR__',
            '__GENERATEHDR__', (string) $text,
            $this->_xml
        );
    }

}