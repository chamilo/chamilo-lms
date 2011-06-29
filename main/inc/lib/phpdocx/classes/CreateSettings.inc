<?php

/**
 * Create default settings
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
 * Create default settings
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
class CreateSettings extends CreateElement
{

    /**
     *
     * @access protected
     */
    protected $_xml;
    /**
     *
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
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateSettings();
        }
        return self::$_instance;
    }

    /**
     * Generate settings
     */
    public function generateSettings()
    {
        $this->_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<w:settings xmlns:o="urn:schemas-microsoft-com:office:offi'
                . 'ce" xmlns:r="http://schemas.openxmlformats.org/officeDocume'
                . 'nt/2006/relationships" xmlns:m="http://schemas.openxmlfor'
                . 'mats.org/officeDocument/2006/math" xmlns:v="urn:schemas-'
                . 'microsoft-com:vml" xmlns:w10="urn:schemas-microsoft-com:'
                . 'office:word" xmlns:w="http://schemas.openxmlformats.org/'
                . 'wordprocessingml/2006/main" xmlns:sl="http://schemas.'
                . 'openxmlformats.org/schemaLibrary/2006/main"><w:zoom w:'
                . 'percent="100"/><w:proofState w:grammar="clean"/>'
                . '<w:defaultTabStop w:val="708"/><w:hyphenationZone w:val='
                . '"425"/><w:characterSpacingControl w:val="doNotCompress"/>'
                . '<w:compat/><w:rsids><w:rsidRoot w:val="00A84D90"/><w:rsid '
                . 'w:val="00A84D90"/><w:rsid w:val="00BD6F4D"/></w:rsids>'
                . '<m:mathPr><m:mathFont m:val="Cambria Math"/><m:brkBin '
                . 'm:val="before"/><m:brkBinSub m:val="--"/><m:smallFrac '
                . 'm:val="off"/><m:dispDef/><m:lMargin m:val="0"/><m:rMargin '
                . 'm:val="0"/><m:defJc m:val="centerGroup"/><m:wrapIndent '
                . 'm:val="1440"/><m:intLim m:val="subSup"/><m:naryLim '
                . 'm:val="undOvr"/></m:mathPr><w:themeFontLang w:val="es-ES"/>'
                . '<w:clrSchemeMapping w:bg1="light1" w:t1="dark1" '
                . 'w:bg2="light2" w:t2="dark2" w:accent1="accent1" w:accent2='
                . '"accent2" w:accent3="accent3" w:accent4="accent4" w:accent5='
                . '"accent5" w:accent6="accent6" w:hyperlink="hyperlink" '
                . 'w:followedHyperlink="followedHyperlink"/><w:shapeDefaults>'
                . '<o:shapedefaults v:ext="edit" spidmax="2050"/>'
                . '<o:shapelayout v:ext="edit"><o:idmap v:ext="edit" data="1"/>'
                . '</o:shapelayout></w:shapeDefaults><w:decimalSymbol'
                . ' w:val=","/><w:listSeparator w:val=";"/></w:settings>';
    }

}