<?php

/**
 * Create links using text strings
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
 * Create links using text strings
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
class CreateLink extends CreateElement
{

    /**
     *
     * @access private
     * @static
     */
    private static $_instance = null;
    /**
     *
     * @access private
     * @var string
     */
    private $_link;
    /**
     *
     * @access private
     * @var string
     */
    private $_title;
    /**
     *
     * @access private
     * @var string
     */
    private $_font;

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
     * Singleton, return instance of class
     *
     * @access public
     * @return CreateLink
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateLink();
        }
        return self::$_instance;
    }

    /**
     * Getter. Access to link var
     *
     * @access public
     * @return string
     */
    public function getLink()
    {
        return $this->_link;
    }

    /**
     * Getter. Access to title var
     *
     * @access public
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Getter. Access to font var
     *
     * @access public
     * @return string
     */
    public function getFont()
    {
        return $this->_font;
    }

    /**
     * Create a link
     *
     * @access public
     * @param string $arrArgs[0] Text to add
     * @param string $arrArgs[1] URL to add
     * @param string $arrArgs[2] RFONTS
     */
    public function createLink()
    {
        $this->_xml = '';
        $args = func_get_args();

        $this->generateP();
        $this->generateR();
        $this->generateFLDCHAR('begin');
        $this->generateR();
        $this->generateINSTRTEXT($args[1]);
        $this->generateR();
        $this->generateFLDCHAR('separate');
        $this->generatePROOFERR('gramStart');
        $this->generateR();
        $this->generateRPR();
        $this->generateRFONTS($args[2]);
        $this->generateRSTYLE('Hipervnculo');
        $this->generateT($args[0]);
        $this->generatePROOFERR('gramEnd');
        $this->generateR();
        $this->generateFLDCHAR('end');
    }

    /**
     * Generate w:fldChar
     * 
     * @access protected
     * @param string $fldCharType
     */
    protected function generateFLDCHAR($fldCharType)
    {
        $strXML = '<' . CreateElement::NAMESPACEWORD . ':fldChar '
                . CreateElement::NAMESPACEWORD . ':fldCharType="'
                . $fldCharType . '"></'
                . CreateElement::NAMESPACEWORD . ':fldChar>';
        $this->_xml = str_replace('__GENERATER__', $strXML, $this->_xml);
    }

    /**
     * Generate w:hyperlink
     * 
     * @access protected
     * @param string $type
     */
    protected function generateHYPERLINK($type)
    {
        $this->_xml = '<' . createElement::NAMESPACEWORD
                . ':hyperlink r:id="rId5" w:history="1">__GENERATEP__</'
                . createElement::NAMESPACEWORD . ':hyperlink>';
    }

    /**
     * Generate w:instrText
     * 
     * @access protected
     * @param string $link. Optional, use http://localhost as default
     */
    protected function generateINSTRTEXT($link = 'http://localhost')
    {
        $xml = '<' . createElement::NAMESPACEWORD
                . ':instrText xml:space="preserve"> HYPERLINK "'
                . $link . '"</' . createElement::NAMESPACEWORD
                . ':instrText>';
        $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
    }

    /**
     * Generate w:proofErr
     * 
     * @access protected
     * @param string $type
     */
    protected function generatePROOFERR($type)
    {
        $xml = '<' . createElement::NAMESPACEWORD . ':proofErr '
                . createElement::NAMESPACEWORD . ':type="' . $type
                . '"></' . createElement::NAMESPACEWORD
                . ':proofErr>__GENERATERSUB__';
        $this->_xml = str_replace('__GENERATERSUB__', $xml, $this->_xml);
    }

    /**
     * Generate w:r
     * 
     * @access protected
     */
    protected function generateR()
    {
        if (preg_match("/__GENERATEP__/", $this->_xml)) {
            $xml = '<' . createElement::NAMESPACEWORD
                    . ':r>__GENERATER__</' . createElement::NAMESPACEWORD
                    . ':r>__GENERATERSUB__';
            $this->_xml = str_replace('__GENERATEP__', $xml, $this->_xml);
        } elseif (preg_match("/__GENERATERSUB__/", $this->_xml)) {
            $xml = '<' . createElement::NAMESPACEWORD . ':r>'
                    . '__GENERATER__</' . createElement::NAMESPACEWORD
                    . ':r>__GENERATERSUB__';
            $this->_xml = str_replace('__GENERATERSUB__', $xml, $this->_xml);
        } else {
            $this->_xml = '<' . createElement::NAMESPACEWORD
                    . ':r>__GENERATER__</' . createElement::NAMESPACEWORD
                    . ':r>__GENERATERSUB__';
        }
    }

    /**
     * Generate w:rStyle
     * 
     * @access protected
     * @param string $val
     */
    protected function generateRSTYLE($val)
    {
        $xml = '<' . createElement::NAMESPACEWORD . ':rStyle '
                . createElement::NAMESPACEWORD . ':val="' . $val . '"></'
                . createElement::NAMESPACEWORD . ':rStyle>';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

}
