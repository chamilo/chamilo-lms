<?php

/**
 * Create footer
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
 * Create footer
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
class CreateFooter extends CreateElement
{
    /**
     *
     * @var CreateFooter
     * @access private
     * @static
     */
    private static $_instance = NULL;

    /**
     *
     * @var int
     * @access private
     * @static
     */
    private static $_numberPage = 0;

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
     * @return CreateFooter
     * @access public
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateFooter();
        }
        return self::$_instance;
    }

    /**
     * Create footer
     *
     * @access public
     * @param string args[0]
     * @param array args[1]
     */
    public function createFooter()
    {
        $this->_xml = '';
        $args = func_get_args();
        $this->generateFTR();
        if ($args[1]['pager'] == 'true') {
            $this->generateP();
            $this->generateR();
            $this->generatePTAB('margin', $args[1]['pagerAlignment']);
            $this->generateFLDSIMPLE();
        }
        $text = CreateText::getInstance();
        $text->createText($args[0], $args[1]);
        $obj = preg_replace('/__[A-Z]+__/', '', (string) $text);
        $xml = $obj . '__GENERATEFTR__';
        $this->_xml = str_replace('__GENERATEFTR__', $xml, $this->_xml);
    }

    /**
     * Generate ftr token
     *
     * @access protected
     */
    protected function generateFTR()
    {
        $this->_xml = '__GENERATEFTR____GENERATEIMGFTR__';
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
                ':p>__GENERATEFTR__';
        $this->_xml = str_replace('__GENERATEFTR__', $xml, $this->_xml);
    }

    /**
     * Generate w:r
     *
     * @access protected
     */
    protected function generateR()
    {
        if (!empty($this->_xml)) {
            if (preg_match("/__GENERATEP__/", $this->_xml)) {
                $xml = '<' . CreateElement::NAMESPACEWORD .
                        ':r>__GENERATER__</' . CreateElement::NAMESPACEWORD .
                        ':r>__GENERATERSUB__';
                
                $this->_xml = str_replace('__GENERATEP__', $xml, $this->_xml);
            } elseif (preg_match("/__GENERATER__/", $this->_xml)) {
                $xml = '<' . CreateElement::NAMESPACEWORD .
                        ':r>__GENERATER__</' . CreateElement::NAMESPACEWORD .
                        ':r>__GENERATERSUB__';

                $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
            } elseif (preg_match("/__GENERATERSUB__/", $this->_xml)) {
                $xml = '<' . CreateElement::NAMESPACEWORD .
                        ':r>__GENERATER__</' . CreateElement::NAMESPACEWORD .
                        ':r>__GENERATERSUB__';

                $this->_xml = str_replace(
                    '__GENERATERSUB__', $xml, $this->_xml
                );
            }
        } else {
            $this->_xml = '<' . CreateElement::NAMESPACEWORD .
                          ':r>__GENERATER__</' . CreateElement::NAMESPACEWORD .
                          ':r>__GENERATERSUB__';
        }
    }

    /**
     * Generate w:ptab
     *
     * @param string $relativeTo
     * @param string $alignment
     * @param string $leader
     * @access protected
     */
    protected function generatePTAB($relativeTo = 'margin',
                                     $alignment = 'right',
                                     $leader = 'none')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':ptab ' . CreateElement::NAMESPACEWORD .
                ':relativeTo="' . $relativeTo .
                '" ' . CreateElement::NAMESPACEWORD .
                ':alignment="' . $alignment .
                '" ' . CreateElement::NAMESPACEWORD .
                ':leader="' . $leader .
                '"></' . CreateElement::NAMESPACEWORD . ':ptab>';

        $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
    }

    /**
     * Generate w:fldsimple
     *
     * @access protected
     */
    protected function generateFLDSIMPLE()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':fldSimple ' . CreateElement::NAMESPACEWORD .
                ':instr=" PAGE   \* MERGEFORMAT "></'
                . CreateElement::NAMESPACEWORD . ':fldSimple>';

        $this->_xml = str_replace('__GENERATERSUB__', $xml, $this->_xml);
    }
}