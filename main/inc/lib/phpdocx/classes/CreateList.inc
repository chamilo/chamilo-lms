<?php

/**
 * Create lists
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
 * Create lists
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
class CreateList extends CreateElement
{
    /**
     *
     * @var mixed
     * @access public
     */
    public $list;
    /**
     *
     * @var array
     * @access public
     */
    public $val;
    /**
     *
     * @var string
     * @access public
     */
    public $font;
    /**
     *
     * @var array
     * @access public
     */
    public $data;
    /**
     * @access private
     * @var CreateList
     * @static
     */
    private static $_instance = NULL;
    /**
     *
     * @access private
     * @var int
     * @static
     */
    private static $_numericList = -1;

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
     * @access public
     * @return string
     */
    public function __toString()
    {
        $this->cleanTemplate();
        return $this->_xml;
    }

    /**
     *
     * @access public
     * @return CreateList
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateList();
        }
        return self::$_instance;
    }

    /**
     * Create list
     * 
     * @access public
     * @param array args[0]
     * @param array args[1]
     */
    public function createList()
    {
        $this->_xml = '';
        $args = func_get_args();
        $this->list = '';

        if ($args[1]['val'] == 2) {
            self::$_numericList++;
            $this->runArray(
                $args[0],
                $args[1]['val'] + self::$_numericList,
                1,
                $args[1]['font']
            );
        } else {
            $this->runArray($args[0], $args[1]['val'], 1, $args[1]['font']);
        }

        $this->_xml = $this->list;
    }

    /**
     * Add list
     *
     * @param string $list
     * @access protected
     */
    protected function addList($list)
    {
        $this->_xml = str_replace('__GENERATER__', $list, $this->_xml);
    }

    /**
     * Generate w:ilfo
     *
     * @param int $val
     * @access protected
     */
    protected function generateILFO($val = 0)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':ilfo ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val .
            '"></' . CreateElement::NAMESPACEWORD .
            ':ilfo>';
        $this->_xml = str_replace('__GENERATEILFO__', $xml, $this->_xml);
    }

    /**
     * Generate w:ilvl
     *
     * @param string $val
     * @access protected
     */
    protected function generateILVL($val = '')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':ilvl ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':ilvl>__GENERATEPSTYLE__';
        $this->_xml = str_replace('__GENERATEPSTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:listpr
     *
     * @access protected
     */
    protected function generateLISTPR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':listPr>__GENERATEILVL____GENERATEILFO__</' .
            CreateElement::NAMESPACEWORD . ':listPr>__GENERATER__';
        $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
    }

    /**
     * Generate w:numid
     *
     * @param int $val
     * @access protected
     */
    protected function generateNUMID($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':numId ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':numId>';
        $this->_xml = str_replace('__GENERATEPSTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:numpr
     *
     * @access protected
     */
    protected function generateNUMPR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':numPr>__GENERATEPSTYLE__</' . CreateElement::NAMESPACEWORD .
            ':numPr>';
        $this->_xml = str_replace('__GENERATEPSTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:pstyle
     *
     * @param string $val
     * @access protected
     */
    protected function generatePSTYLE($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':pStyle ' . CreateElement::NAMESPACEWORD . ':val="' . $val .
            '">__GENERATEPSTYLE__</' . CreateElement::NAMESPACEWORD .
            ':pStyle>';
        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Recursive generation of lists
     *
     * @param array $dat
     * @param string $val
     * @param int $depth
     * @param string $font
     * @access protected
     */
    protected function runArray($dat, $val, $depth, $font = '')
    {
        foreach ($dat as $cont) {
                $this->generateP();
                $this->generatePPR();
                $this->generatePSTYLE('Prrafodelista');
                $this->generateNUMPR();
                $this->generateILVL($depth);
                $this->generateNUMID($val);
                $this->generateR();
                if ($font != '') {
                    $this->generateRPR();
                    $this->generateRFONTS($font);
                }
                $this->generateT($cont);
                $this->list .= $this->_xml;
        }
    }
}
