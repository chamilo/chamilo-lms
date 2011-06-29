<?php

/**
 * Create text
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
 * Create text
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
class CreateText extends CreateElement
{
    const IDTITLE = 229998237;
    /**
     *
     * @access private
     * @var string
     */
    private static $_instance = NULL;
    /**
     *
     * @access private
     * @var int
     */
    private static $_idTitle = 0;

    /**
     * Destruct
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
     * @return CreateText
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateText();
        }
        return self::$_instance;
    }

    /**
     * Create text
     *
     * @access public
     * @param mixed $args[0]
     * @param array $args[1]
     */
    public function createText()
    {
        $this->_xml = '';
        $args = func_get_args();

        $this->generateP();
        if (!is_array($args[0])) {
            if (
                !empty($args[1]['jc']) ||
                !empty($args[1]['pageBreakBefore']) ||
                !empty($args[1]['widowControl']) ||
                !empty($args[1]['wordWrap'])
            ) {
                $this->generatePPR();
                if (!empty($args[1]['jc']))
                    $this->generateJC($args[1]['jc']);
                if (!empty($args[1]['pageBreakBefore']))
                    $this->generatePAGEBREAKBEFORE($args[1]['pageBreakBefore']);
                if (!empty($args[1]['widowControl']))
                    $this->generateWIDOWCONTROL($args[1]['widowControl']);
                if (!empty($args[1]['wordWrap']))
                    $this->generateWORDWRAP($args[1]['wordWrap']);
            }
            $this->generateR();
            if (
                $args[1]['b'] != '' ||
                $args[1]['i'] != '' ||
                $args[1]['u'] != '' ||
                $args[1]['sz'] != '' ||
                $args[1]['color'] != '' ||
                $args[1]['font'] != '' ||
                $args[1]['tabs'] != ''
            ) {
                $this->generateRPR();
                if (isset($args[1]['font']) && $args[1]['font'] != '') {
                    $this->generateRFONTS($args[1]['font']);
                }
                if (isset($args[1]['b']) && $args[1]['b'] != '') {
                    $this->generateB($args[1]['b']);
                }
                if (isset($args[1]['i']) && $args[1]['i'] != '') {
                    $this->generateI($args[1]['i']);
                }
                if (isset($args[1]['u']) && $args[1]['u'] != '') {
                    $this->generateU($args[1]['u']);
                }
                if (isset($args[1]['sz']) && $args[1]['sz'] != '') {
                    $this->generateSZ($args[1]['sz']);
                }
                if (isset($args[1]['color']) && $args[1]['color'] != '') {
                    $this->generateCOLOR($args[1]['color']);
                }
            }
            $this->generateT($args[0], $args[1]['spaces']);
        }
    }

    /**
     * Create title
     *
     * @access protected
     * @param string $arrArgs[0]
     * @param array $arrArgs[1]
     */
    public function createTitle()
    {
        $this->_xml = '';
        $args = func_get_args();
        if (!empty($args[1]['val']) && !empty($args[0])) {
            $this->generateP();
            $this->generatePPR();
            if ($args[1]['type'] == 'subtitle')
                $this->generatePSTYLE('Subttulo' . $args[1]['val']);
            else
                $this->generatePSTYLE('Ttulo' . $args[1]['val']);
            if (!empty($args[1]['pageBreakBefore']))
                $this->generatePAGEBREAKBEFORE($args[1]['pageBreakBefore']);
            if (!empty($args[1]['widowControl']))
                $this->generateWIDOWCONTROL($args[1]['widowControl']);
            if (!empty($args[1]['wordWrap']))
                $this->generateWORDWRAP($args[1]['wordWrap']);
            self::$_idTitle++;
            $this->generateBOOKMARKSTART(
                self::$_idTitle, '_Toc' . (self::$_idTitle + self::IDTITLE)
            );
            $this->generateR();
            if (
                $args[1]['b'] != '' ||
                $args[1]['i'] != '' ||
                $args[1]['u'] != '' ||
                $args[1]['sz'] != '' ||
                $args[1]['color'] != '' ||
                $args[1]['font'] != ''
            ) {
                $this->generateRPR();
                if ($args[1]['font'] != '') {
                    $this->generateRFONTS($args[1]['font']);
                }
                if ($args[1]['b'] != '') {
                    $this->generateB($args[1]['b']);
                }
                if ($args[1]['i'] != '') {
                    $this->generateI($args[1]['i']);
                }
                if ($args[1]['u'] != '') {
                    $this->generateU($args[1]['u']);
                }
                if ($args[1]['sz'] != '') {
                    $this->generateSZ($args[1]['sz']);
                }
                if ($args[1]['color'] != '') {
                    $this->generateCOLOR($args[1]['color']);
                }
            }
            $this->generateT($args[0]);
            $this->generateBOOKMARKEND(self::$_idTitle);
            $this->cleanTemplate();
        }
    }

    /**
     * Generate w:b
     *
     * @access protected
     * @param string $val
     */
    protected function generateB($strVal = 'single')
    {
        $element = '<' . CreateElement::NAMESPACEWORD . ':b ' .
                CreateElement::NAMESPACEWORD . ':val="' . $strVal . '"></' .
                CreateElement::NAMESPACEWORD . ':b>__GENERATERPR__';
        $this->_xml = str_replace('__GENERATERPR__', $element, $this->_xml);
    }

    /**
     * Generate w:color
     *
     * @access protected
     * @param string $val
     */
    protected function generateCOLOR($val = '000000')
    {
        $this->_xml = str_replace(
            '__GENERATERPR__', '<' . CreateElement::NAMESPACEWORD . ':color ' .
            CreateElement::NAMESPACEWORD . ':val="' . $val . '"></' .
            CreateElement::NAMESPACEWORD . ':color>__GENERATERPR__', $this->_xml
        );
    }

    /**
     * Generate w:jc
     *
     * @access protected
     * @param string $val
     */
    protected function generateJC($val = '')
    {
        $this->_xml = str_replace(
            '__GENERATEPPR__', '<' . CreateElement::NAMESPACEWORD . ':jc ' .
            CreateElement::NAMESPACEWORD . ':val="' . $val . '"></' .
            CreateElement::NAMESPACEWORD . ':jc>__GENERATESZ__', $this->_xml
        );
    }

    /**
     * Generate w:i
     *
     * @access protected
     * @param string $val
     */
    protected function generateI($val = 'single')
    {
        $this->_xml = str_replace(
            '__GENERATERPR__', '<' . CreateElement::NAMESPACEWORD . ':i ' .
            CreateElement::NAMESPACEWORD . ':val="' . $val . '"></' .
            CreateElement::NAMESPACEWORD . ':i>__GENERATERPR__', $this->_xml
        );
    }

    /**
     * Generate w:sz
     *
     * @access protected
     * @param string $val
     */
    protected function generateSZ($val = '11')
    {
        $val *= 2;
        $this->_xml = str_replace(
            '__GENERATERPR__', '<' . CreateElement::NAMESPACEWORD . ':sz ' .
            CreateElement::NAMESPACEWORD . ':val="' . $val . '"></' .
            CreateElement::NAMESPACEWORD . ':sz>__GENERATERPR__', $this->_xml
        );
    }

    /**
     * Generate w:u
     *
     * @access protected
     * @param string $val
     */
    protected function generateU($val = 'single')
    {
        $this->_xml = str_replace(
            '__GENERATERPR__', '<' . CreateElement::NAMESPACEWORD . ':u ' .
            CreateElement::NAMESPACEWORD . ':val="' . $val . '"></' .
            CreateElement::NAMESPACEWORD . ':u>__GENERATERPR__', $this->_xml
        );
    }

    /**
     * Generate w:pstyle
     *
     * @access protected
     * @param string $val
     */
    protected function generatePSTYLE($val = 'Ttulo')
    {
        $this->_xml = str_replace(
            '__GENERATEPPR__', '<' . CreateElement::NAMESPACEWORD . ':pStyle ' .
            CreateElement::NAMESPACEWORD . ':val="' . $val . '"></' .
            CreateElement::NAMESPACEWORD . ':pStyle>__GENERATEPPR__',
            $this->_xml
        );
    }

    /**
     * Generate w:pagebreakbefore
     *
     * @access protected
     * @param string $val
     */
    protected function generatePAGEBREAKBEFORE($val = 'on')
    {
        $this->_xml = str_replace(
            '__GENERATEPPR__', '<' . CreateElement::NAMESPACEWORD .
            ':pageBreakBefore val="' . $val . '"></' .
            CreateElement::NAMESPACEWORD . ':pageBreakBefore>__GENERATEPPR__',
            $this->_xml
        );
    }

    /**
     * Generate w:widowcontrol
     *
     * @access protected
     * @param string $val
     */
    protected function generateWIDOWCONTROL($val = 'on')
    {
        $this->_xml = str_replace(
            '__GENERATEPPR__', '<' . CreateElement::NAMESPACEWORD .
            ':widowControl val="' . $val . '"></' .
            CreateElement::NAMESPACEWORD . ':widowControl>__GENERATEPPR__',
            $this->_xml
        );
    }

    /**
     * Generate w:wordwrap
     *
     * @access protected
     * @param string $val
     */
    protected function generateWORDWRAP($val = 'on')
    {
        $this->_xml = str_replace(
            '__GENERATEPPR__', '<' . CreateElement::NAMESPACEWORD .
            ':wordWrap val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':wordWrap>__GENERATEPPR__', $this->_xml
        );
    }

    /**
     * Generate w:bookmarkstart
     *
     * @access protected
     * @param int $id
     * @param string $name
     */
    protected function generateBOOKMARKSTART($id, $name)
    {
        $this->_xml = str_replace(
            '__GENERATER__', '<' . CreateElement::NAMESPACEWORD .
            ':bookmarkStart ' . CreateElement::NAMESPACEWORD . ':id="' . $id .
            '" ' . CreateElement::NAMESPACEWORD . ':name="' . $name . '"></' .
            CreateElement::NAMESPACEWORD .
            ':bookmarkStart>__GENERATER____GENERATEBOOKMARKEND__', $this->_xml
        );
    }

    /**
     * Generate w:bookmarkend
     *
     * @access protected
     * @param int $id
     */
    protected function generateBOOKMARKEND($id)
    {
        $this->_xml = str_replace(
            '__GENERATEBOOKMARKEND__', '<' . CreateElement::NAMESPACEWORD .
            ':bookmarkEnd ' . CreateElement::NAMESPACEWORD . ':id="' . $id .
            '"></' . CreateElement::NAMESPACEWORD . ':bookmarkEnd>', $this->_xml
        );
    }

}
