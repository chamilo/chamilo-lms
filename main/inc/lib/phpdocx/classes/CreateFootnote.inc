<?php

/**
 * Create footnote
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
 * Create footnote
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
class CreateFootnote extends CreateElement
{
    /**
     *
     * @var bool
     * @access public
     * @static
     */
    public static $init = 0;

    /**
     *
     * @var CreateFootnote
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
    private static $_id;
    

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
     * @return CreateFootnote
     * @access public
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateFootnote();
        }
        return self::$_instance;
    }

    /**
     * Create footnote
     *
     * @access public
     * @param array $args[0]
     *
     */
    public function createFootnote()
    {
        $this->_xml = '';
        $args = func_get_args();
        $this->generateFOOTNOTE('');
        $this->generateP();
        $this->generatePPR();
        $this->generatePSTYLE();
        $this->generateR();
        $this->generateRPR();
        $this->generateRSTYLE();
        $this->generateFOOTNOTEREF();
        $this->generateR();
        if ($args[0]['font'] != '') {
            $this->generateRPR();
            $this->generateRFONTS($args[0]['font']);
        }
        $this->generateT($args[0]['textEndNote']);
        $this->cleanTemplate();
    }

    /**
     * Create document footnote
     *
     * @access public
     * @param array $args[0]
     *
     */
    public function createDocumentFootnote()
    {
        $this->_xml = '';
        $args = func_get_args();
        parent::generateP();
        $this->generateR();
        if ($args[0]['font'] != '') {
            $this->generateRPR();
            $this->generateRFONTS($args[0]['font']);
        }
        $xml = '';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
        $this->generateT($args[0]['textDocument']);
        $this->generateR();
        $this->generateRPR();
        $this->generateRSTYLE();
        $this->generateFOOTNOTEREFERENCE(self::$_id - 2);
        $this->cleanTemplate();
    }

    /**
     * Create init footnote
     *
     * @access public
     * @param array $args[0]
     *
     */
    public function createInitFootnote()
    {
        $this->_xml = '';
        $args = func_get_args();
        $this->generateFOOTNOTE($args[0]['type']);
        $this->generateP();
        $this->generatePPR();
        $this->generateSPACING();
        $this->generateR();
        $this->generateSEPARATOR($args[0]['type']);
        $this->cleanTemplate();
    }

    /**
     * Generate w:footnote
     *
     * @param string $type
     * @access protected
     */
    protected function generateFOOTNOTE($type)
    {
        self::$init = 1;
        if (empty(self::$_id)) {
            self::$_id = 1;
        } else {
            self::$_id++;
        }

        $xmlAux = '<' . CreateElement::NAMESPACEWORD . ':footnote';

        if ($type != '')
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD . 
                       ':type="' . $type . '"';

        $this->_xml = $xmlAux . ' ' . CreateElement::NAMESPACEWORD .
                    ':id="' . (self::$_id - 2) .
                    '">__GENERATEFOOTNOTE__</' . CreateElement::NAMESPACEWORD .
                    ':footnote>';
    }

    /**
     * Generate w:p
     *
     * @param string $rsidR
     * @param string $rsidRDefault
     * @param string $rsidP
     * @access protected
     */
    protected function generateP($rsidR = '005F02E5', 
                                 $rsidRDefault = '005F02E5',
                                 $rsidP = '005F02E5')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
               ':p>__GENERATEP__</' . CreateElement::NAMESPACEWORD . ':p>';

        $this->_xml = str_replace('__GENERATEFOOTNOTE__', $xml, $this->_xml);
    }

    /**
     * Generate w:separator
     *
     * @param string $type
     * @access protected
     */
    protected function generateSEPARATOR($type = 'separator')
    {
        $xml = '<w:' . $type . '></w:' . $type . '>';
        $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
    }

    /**
     * Generate w:ppr
     *
     * @access protected
     */
    protected function generatePPR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':pPr>__GENERATEPPR__</' . CreateElement::NAMESPACEWORD .
                ':pPr>__GENERATEP__';

        $this->_xml = str_replace('__GENERATEP__', $xml, $this->_xml);
    }

    /**
     * Generate w:spacing
     *
     * @param string $after
     * @param string $line
     * @param string $lineRule
     * @access protected
     */
    protected function generateSPACING($after = '0',
                                       $line = '240', $lineRule = 'auto')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':spacing w:after="' . $after .
                '" ' . CreateElement::NAMESPACEWORD .
                ':line="' . $line .
                '" ' . CreateElement::NAMESPACEWORD .
                ':lineRule="' . $lineRule .
                '"></' . CreateElement::NAMESPACEWORD .
                ':spacing>';

        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:r
     *
     * @access protected
     */
    protected function generateR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':r>__GENERATER__</' . CreateElement::NAMESPACEWORD .
                ':r>__GENERATEP__';

        $this->_xml = str_replace('__GENERATEP__', $xml, $this->_xml);
    }

    /**
     * Generate w:footnoteref
     *
     * @access protected
     */
    protected function generateFOOTNOTEREF()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':footnoteRef></' . CreateElement::NAMESPACEWORD .
                ':footnoteRef>';

        $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
    }

    /**
     * Generate w:footnotereference
     *
     * @param string $id
     * @access protected
     */
    protected function generateFOOTNOTEREFERENCE($id = '')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':footnoteReference ' . CreateElement::NAMESPACEWORD .
                ':id="' . $id . '"></' . CreateElement::NAMESPACEWORD .
                ':footnoteReference>';
        
        $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
    }
}
