<?php

/**
 * Create graphics (charts)
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
 * Create graphics (charts)
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
class CreateGraphic extends CreateElement
{
    const NAMESPACEWORD = 'c';

    /**
     *
     * @access protected
     * @var <type>
     */
    protected $_xmlChart;
    
    /**
     * @access private
     * @var CreateGraphic
     * @static
     */
    private static $_instance = null;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_rId;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_textalign;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_jc;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_sizeX;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_sizeY;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_type;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_showPercent;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_data;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_cornerX;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_cornerY;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_cornerP;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_color;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_groupBar;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_title;

    /**
     *
     * @access private
     * @var <type>
     */
    private $_name;

    /**
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
        $this->_rId = '';
        $this->_textalign = '';
        $this->_jc = '';
        $this->_sizeX = '';
        $this->_sizeY = '';
        $this->_type = '';
        $this->_showPercent = '';
        $this->_data = '';
        $this->_cornerX = '';
        $this->_cornerY = '';
        $this->_cornerP = '';
        $this->_color = '';
        $this->_groupBar = '';
        $this->_title = '';
        $this->_font = '';
        $this->_xml = '';
        $this->_name = '';
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
        return $this->_xml;
    }

    /**
     *
     * @access public
     * @return CreateGraphic
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateGraphic();
        }
        return self::$_instance;
    }

    /**
     * Setter. Rid
     *
     * @access public
     * @param string $rId
     */
    public function setRId($rId)
    {
        $this->_rId = $rId;
    }

    /**
     * Getter. Rid
     *
     * @access public
     * @return string
     */
    public function getRId()
    {
        return $this->_rId;
    }

    /**
     * Setter. Name
     *
     * @access public
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Getter. Name
     *
     * @access public
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Setter. Xml chart
     *
     * @access public
     * @param string $xmlChart
     */
    public function setXmlChart($xmlChart)
    {
        $this->_xmlChart = $xmlChart;
    }

    /**
     * Getter. Xml chart
     *
     * @access public
     * @return string
     */
    public function getXmlChart()
    {
        return $this->_xmlChart;
    }

    /**
     * Create graphic
     *
     * @access public
     * @param array $args[0]
     * @param array $args[1]
     * @return boolean
     */
    public function createGraphic()
    {
        $this->_xmlChart = '';
        $args = func_get_args();
        if (!empty($args[1]['type']) && !empty($args[0])) {
            if ($this->createCHARTXML($args) == false) {
                echo 'You haven`t added legends';
                return false;
            }
            $this->createDOCUEMNTXML($args);
            return true;
        } else {
            echo 'You haven`t added a chart type';
            return false;
        }
    }

    /**
     * Generate chartspace XML
     *
     * @access protected
     */
    protected function generateCHARTSPACE()
    {
        $this->_xmlChart = '<?xml version="1.0" encoding="UTF-8" ' .
            'standalone="yes" ?><' . CreateGraphic::NAMESPACEWORD .
            ':chartSpace xmlns:c="http://schemas.openxmlformats.o' .
            'rg/drawingml/2006/chart" xmlns:a="http://schemas.open' .
            'xmlformats.org/drawingml/2006/main" xmlns:r="http://s' .
            'chemas.openxmlformats.org/officeDocument/2006/relatio' .
            'nships">__GENERATECHARTSPACE__</' .
            CreateGraphic::NAMESPACEWORD . ':chartSpace>';
    }

    /**
     * Generate w:date1904
     *
     * @access protected
     * @param string $val
     */
    protected function generateDATE1904($val = '1')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':date1904 val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':date1904>__GENERATECHARTSPACE__';
        $this->_xmlChart = str_replace(
            '__GENERATECHARTSPACE__',
            $xml,
            $this->_xmlChart
        );
    }

    /**
     * Generate w:lang
     *
     * @access protected
     * @param string $val
     */
    protected function generateLANG($val = 'es-ES')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':lang val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':lang>__GENERATECHARTSPACE__';
        $this->_xmlChart = str_replace(
            '__GENERATECHARTSPACE__',
            $xml,
            $this->_xmlChart
        );
    }

    /**
     * Generate w:style
     *
     * @access protected
     * @param string $val
     */
    protected function generateSTYLE($val = '2')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':style val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':style>__GENERATECHARTSPACE__';
        $this->_xmlChart = str_replace(
            '__GENERATECHARTSPACE__',
            $xml,
            $this->_xmlChart
        );
    }

    /**
     * Generate w:title
     *
     * @access protected
     */
    protected function generateTITLE()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':title>__GENERATETITLE__</' . CreateGraphic::NAMESPACEWORD .
            ':title>__GENERATECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:titletx
     *
     * @access protected
     */
    protected function generateTITLETX()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':tx>__GENERATETITLETX__</' . CreateGraphic::NAMESPACEWORD .
            ':tx>__GENERATETITLE__';
        $this->_xmlChart = str_replace(
            '__GENERATETITLE__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:rich
     *
     * @access protected
     */
    protected function generateRICH()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':rich>__GENERATERICH__</' . CreateGraphic::NAMESPACEWORD .
            ':rich>__GENERATETITLETX__';
        $this->_xmlChart = str_replace(
            '__GENERATETITLETX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:bodypr
     *
     * @access protected
     */
    protected function generateBODYPR()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . ':bodyPr></' .
            CreateImage::NAMESPACEWORD1 . ':bodyPr>__GENERATERICH__';
        $this->_xmlChart = str_replace(
            '__GENERATERICH__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:lststyle
     *
     * @access protected
     */
    protected function generateLSTSTYLE()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
            ':lstStyle></' . CreateImage::NAMESPACEWORD1 .
            ':lstStyle>__GENERATERICH__';
        $this->_xmlChart = str_replace(
            '__GENERATERICH__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:titlep
     *
     * @access protected
     */
    protected function generateTITLEP()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
            ':p>__GENERATETITLEP__</' . CreateImage::NAMESPACEWORD1 .
            ':p>__GENERATERICH__';
        $this->_xmlChart = str_replace(
            '__GENERATERICH__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:titleppr
     *
     * @access protected
     */
    protected function generateTITLEPPR()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
            ':pPr>__GENERATETITLEPPR__</' . CreateImage::NAMESPACEWORD1 .
            ':pPr>__GENERATETITLEP__';
        $this->_xmlChart = str_replace(
            '__GENERATETITLEP__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:titlerfonts
     *
     * @access protected
     * @param string $font
     */
    protected function generateTITLERFONTS($font = '')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . ':latin typeface="' .
            $font . '" pitchFamily="34" charset="0"></' .
            CreateImage::NAMESPACEWORD1 . ':latin ><' .
            CreateImage::NAMESPACEWORD1 .
            ':cs typeface="' . $font . '" pitchFamily="34" charset="0"></' .
            CreateImage::NAMESPACEWORD1 . ':cs>';
        $this->_xmlChart = str_replace(
            '__GENERATETITLERPR__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:defrpr
     *
     * @access protected
     */
    protected function generateDEFRPR()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
            ':defRPr>__GENERATEDEFRPR__</' . CreateImage::NAMESPACEWORD1 .
            ':defRPr>__GENERATETITLEPPR__';
        $this->_xmlChart = str_replace(
            '__GENERATETITLEPPR__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:titler
     *
     * @access protected
     */
    protected function generateTITLER()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
            ':r>__GENERATETITLER__</' . CreateImage::NAMESPACEWORD1 .
            ':r>__GENERATETITLEP__';
        $this->_xmlChart = str_replace(
            '__GENERATETITLEP__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:titlerpr
     *
     * @access protected
     */
    protected function generateTITLERPR($lang = 'es-ES')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . ':rPr lang="' .
            $lang . '">__GENERATETITLERPR__</' . CreateImage::NAMESPACEWORD1 .
            ':rPr>__GENERATETITLER__';
        $this->_xmlChart = str_replace(
            '__GENERATETITLER__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:titlet
     *
     * @access protected
     * @param string $nombre
     */
    protected function generateTITLET($nombre = '')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . ':t>' .
            $nombre . '</' . CreateImage::NAMESPACEWORD1 .
            ':t>__GENERATETITLER__';
        $this->_xmlChart = str_replace(
            '__GENERATETITLER__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:titlelayout
     *
     * @access protected
     * @param string $nombre
     */
    protected function generateTITLELAYOUT($nombre = '')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
            ':layout></' . CreateImage::NAMESPACEWORD1 .
            ':layout>';
        $this->_xmlChart = str_replace(
            '__GENERATETITLE__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:autotitledeleted
     *
     * @access protected
     * @param string $val
     */
    protected function generateAUTOTITLEDELETED($val = '1')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':autoTitleDeleted val="' . $val .
            '"></' . CreateGraphic::NAMESPACEWORD .
            ':autoTitleDeleted>__GENERATECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:chart
     *
     * @access protected
     */
    protected function generateCHART()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':chart>__GENERATECHART__</' . CreateGraphic::NAMESPACEWORD .
            ':chart>__GENERATECHARTSPACE__';
        $this->_xmlChart = str_replace(
            '__GENERATECHARTSPACE__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:plotarea
     *
     * @access protected
     */
    protected function generatePLOTAREA()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':plotArea>__GENERATEPLOTAREA__</' . CreateGraphic::NAMESPACEWORD .
            ':plotArea>__GENERATECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:layout
     *
     * @access protected
     */
    protected function generateLAYOUT()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':layout></' . CreateGraphic::NAMESPACEWORD .
            ':layout>__GENERATEPLOTAREA__';
        $this->_xmlChart = str_replace(
            '__GENERATEPLOTAREA__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:piechart
     *
     * @access protected
     */
    protected function generatePIECHART()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':pieChart>__GENERATETYPECHART__</' .
            CreateGraphic::NAMESPACEWORD .
            ':pieChart>__GENERATEPLOTAREA__';
        $this->_xmlChart = str_replace(
            '__GENERATEPLOTAREA__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:barChart
     *
     * @access protected
     */
    protected function generateBARCHART()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':barChart>__GENERATETYPECHART__</' .
            CreateGraphic::NAMESPACEWORD .
            ':barChart>__GENERATEPLOTAREA__';
        $this->_xmlChart = str_replace(
            '__GENERATEPLOTAREA__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:lineChart
     *
     * @access protected
     */
    protected function generateLINECHART()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':lineChart>__GENERATETYPECHART__</' .
            CreateGraphic::NAMESPACEWORD .
            ':lineChart>__GENERATEPLOTAREA__';
        $this->_xmlChart = str_replace(
            '__GENERATEPLOTAREA__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:varyColors
     *
     * @access protected
     * @param string $val
     */
    protected function generateVARYCOLORS($val = '1')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':varyColors val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':varyColors>__GENERATETYPECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATETYPECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:barDir
     *
     * @access protected
     * @param string $val
     */
    protected function generateBARDIR($val = 'bar')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':barDir val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':barDir>__GENERATETYPECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATETYPECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:grouping
     *
     * @access protected
     * @param string $val
     */
    protected function generateGROUPING($val = 'stacked')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':grouping val="' . $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':grouping>__GENERATETYPECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATETYPECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:ser
     *
     * @access protected
     */
    protected function generateSER()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':ser>__GENERATESER__</' . CreateGraphic::NAMESPACEWORD .
            ':ser>__GENERATETYPECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATETYPECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:idx
     *
     * @access protected
     * @param string $val
     */
    protected function generateIDX($val = '0')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':idx val="' . $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':idx>__GENERATESER__';
        $this->_xmlChart = str_replace(
            '__GENERATESER__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:order
     *
     * @access protected
     * @param string $val
     */
    protected function generateORDER($val = '0')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':order val="' . $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':order>__GENERATESER__';
        $this->_xml = str_replace(
            '__GENERATESER__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:tx
     *
     * @access protected
     */
    protected function generateTX()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':tx>__GENERATETX__</' . CreateGraphic::NAMESPACEWORD .
            ':tx>__GENERATESER__';
        $this->_xmlChart = str_replace(
            '__GENERATESER__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:strref
     *
     * @access protected
     */
    protected function generateSTRREF()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':strRef>__GENERATESTRREF__</' . CreateGraphic::NAMESPACEWORD .
            ':strRef>__GENERATETX__';
        $this->_xmlChart = str_replace(
            '__GENERATETX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:f
     *
     * @access protected
     * @param string $val
     */
    protected function generateF($val = 'Hoja1!$B$1')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':f>' .
            $val . '</' . CreateGraphic::NAMESPACEWORD .
            ':f>__GENERATESTRREF__';
        $this->_xmlChart = str_replace(
            '__GENERATESTRREF__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:strcache
     *
     * @access protected
     */
    protected function generateSTRCACHE()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':strCache>__GENERATESTRCACHE__</' . CreateGraphic::NAMESPACEWORD .
            ':strCache>__GENERATESTRREF__';
        $this->_xmlChart = str_replace(
            '__GENERATESTRREF__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:ptcount
     *
     * @access protected
     * @param string $val
     */
    protected function generatePTCOUNT($val = '1')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':ptCount val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':ptCount>__GENERATESTRCACHE__';
        $this->_xmlChart = str_replace(
            '__GENERATESTRCACHE__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:pt
     *
     * @access protected
     * @param string $idx
     */
    protected function generatePT($idx = '0')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':pt idx="' .
            $idx . '">__GENERATEPT__</' . CreateGraphic::NAMESPACEWORD .
            ':pt>__GENERATESTRCACHE__';
        $this->_xmlChart = str_replace(
            '__GENERATESTRCACHE__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:v
     *
     * @access protected
     * @param string $idx
     */
    protected function generateV($idx = 'Ventas')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':v>' .
            $idx . '</' . CreateGraphic::NAMESPACEWORD . ':v>';
        $this->_xmlChart = str_replace(
            '__GENERATEPT__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:cat
     *
     * @access protected
     */
    protected function generateCAT()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':cat>__GENERATETX__</' . CreateGraphic::NAMESPACEWORD .
            ':cat>__GENERATESER__';
        $this->_xmlChart = str_replace(
            '__GENERATESER__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:val
     *
     * @access protected
     */
    protected function generateVAL()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':val>__GENERATETX__</' . CreateGraphic::NAMESPACEWORD .
            ':val>__GENERATESER__';
        $this->_xmlChart = str_replace(
            '__GENERATESER__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:numcache
     *
     * @access protected
     */
    protected function generateNUMCACHE()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':numCache>__GENERATESTRCACHE__</' .
            CreateGraphic::NAMESPACEWORD . ':numCache>__GENERATESTRREF__';
        $this->_xmlChart = str_replace(
            '__GENERATESTRREF__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:numRef
     *
     * @access protected
     */
    protected function generateNUMREF()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':numRef>__GENERATESTRREF__</' . CreateGraphic::NAMESPACEWORD .
            ':numRef>__GENERATETX__';
        $this->_xmlChart = str_replace(
            '__GENERATETX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:formatCode
     *
     * @access protected
     * @param string $val
     */
    protected function generateFORMATCODE($val = 'General')
    {
        $this->_xmlChart = str_replace(
            '__GENERATESTRCACHE__',
            '<' . CreateGraphic::NAMESPACEWORD . ':formatCode>' . $val .
            '</' . CreateGraphic::NAMESPACEWORD .
            ':formatCode>__GENERATESTRCACHE__',
            $this->_xmlChart
        );
    }

    /**
     * Generate w:legend
     *
     * @access protected
     */
    protected function generateLEGEND()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':legend>__GENERATELEGEND__</' .
            CreateGraphic::NAMESPACEWORD . ':legend>__GENERATECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATECHART__',
            $xml,
            $this->_xmlChart
        );
    }

    /**
     * Generate w:legendPos
     *
     * @access protected
     * @param string $val
     */
    protected function generateLEGENDPOS($val = 'r')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':legendPos val="' . 
             $val . '"></' . CreateGraphic::NAMESPACEWORD .
             ':legendPos>__GENERATELEGEND__';
        $this->_xmlChart = str_replace(
            '__GENERATELEGEND__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate c:layout
     *
     * @access protected
     * @param string $font
     */
    protected function generateLEGENDFONT($font = '')
    {
        $xml = '<c:layout /><c:txPr><a:bodyPr /><a:lstStyle />' .
            '<a:p><a:pPr><a:defRPr><a:latin typeface="' . $font .
            '" pitchFamily="34" charset="0" /><a:cs typeface="' .
            $font . '" pitchFamily="34" charset="0" /></a:defRPr>' .
            '</a:pPr><a:endParaRPr lang="es-ES" /></a:p></c:txPr>';
        $this->_xmlChart = str_replace(
            '__GENERATELEGEND__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:poltVisOnly
     *
     * @access protected
     * @param string $val
     */
    protected function generatePLOTVISONLY($val = '1')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':plotVisOnly val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':plotVisOnly>__GENERATECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:externalData
     *
     * @access protected
     * @param string $val
     */
    protected function generateEXTERNALDATA($val = 'rId1')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':externalData r:id="' . $val . '"></' .
            CreateGraphic::NAMESPACEWORD . ':externalData>';
        $this->_xmlChart = str_replace(
            '__GENERATECHARTSPACE__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:spPr
     *
     * @access protected
     */
    protected function generateSPPR()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':spPr>__GENERATESPPR__</' . CreateGraphic::NAMESPACEWORD .
            ':spPr>__GENERATECHARTSPACE__';
        $this->_xmlChart = str_replace(
            '__GENERATECHARTSPACE__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:ln
     *
     * @access protected
     */
    protected function generateLN()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . ':ln>__GENERATELN__</' .
            CreateImage::NAMESPACEWORD1 . ':ln>';
        $this->_xmlChart = str_replace(
            '__GENERATESPPR__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:noFill
     *
     * @access protected
     */
    protected function generateNOFILL()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . ':noFill></' .
            CreateImage::NAMESPACEWORD1 . ':noFill>';
        $this->_xmlChart = str_replace(
            '__GENERATELN__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:overlap
     *
     * @access protected
     * @param string $val
     */
    protected function generateOVERLAP($val = '100')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':overlap val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':overlap>__GENERATETYPECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATETYPECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:shape
     *
     * @access protected
     * @param string $val
     */
    protected function generateSHAPE($val = 'box')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':shape val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':shape>__GENERATETYPECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATETYPECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:axid
     *
     * @access protected
     * @param string $val
     */
    protected function generateAXID($val = '59034624')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':axId val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':axId>__GENERATETYPECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATETYPECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:firstSliceAng
     *
     * @access protected
     * @param string $val
     */
    protected function generateFIRSTSLICEANG($val = '0')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':firstSliceAng val="' . $val . '"></' .
            CreateGraphic::NAMESPACEWORD . ':firstSliceAng>';
        $this->_xmlChart = str_replace(
            '__GENERATETYPECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:dLbls
     *
     * @access protected
     */
    protected function generateDLBLS()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':dLbls>__GENERATEDLBLS__</' . CreateGraphic::NAMESPACEWORD .
            ':dLbls>__GENERATETYPECHART__';
        $this->_xmlChart = str_replace(
            '__GENERATETYPECHART__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:showPercent
     *
     * @access protected
     * @param string $val
     */
    protected function generateSHOWPERCENT($val = '0')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':showPercent val="' . $val . '"></' .
            CreateGraphic::NAMESPACEWORD . ':showPercent>';
        $this->_xmlChart = str_replace(
            '__GENERATEDLBLS__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:chart
     *
     * @access protected
     */
    protected function generateDOCUMENTCHART()
    {
        $this->_xml = str_replace(
            '__GENERATEGRAPHICDATA__',
            '<' . CreateGraphic::NAMESPACEWORD .
            ':chart xmlns:c="http://schemas.openxmlformats.org/drawingml/' .
            '2006/chart" xmlns:r="http://schemas.openxmlformats.org/offic' .
            'eDocument/2006/relationships" r:id="rId' . $this->getRId() .
            '"></' . CreateGraphic::NAMESPACEWORD .
            ':chart>',
            $this->_xml
        );
    }

    /**
     * Generate w:catAx
     *
     * @access protected
     */
    protected function generateCATAX()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':catAx>__GENERATEAX__</' . CreateGraphic::NAMESPACEWORD .
            ':catAx>__GENERATEPLOTAREA__';
        $this->_xmlChart = str_replace(
            '__GENERATEPLOTAREA__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:valAx
     *
     * @access protected
     */
    protected function generateVALAX()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':valAx>__GENERATEAX__</' .
            CreateGraphic::NAMESPACEWORD .
            ':valAx>';
        $this->_xmlChart = str_replace(
            '__GENERATEPLOTAREA__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:axId
     *
     * @access protected
     * @param <type> $val
     */
    protected function generateAXAXID($val = '59034624')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':axId val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':axId>__GENERATEAX__';
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:scaling
     *
     * @access protected
     */
    protected function generateSCALING()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD .
            ':scaling>__GENERATESCALING__</' . CreateGraphic::NAMESPACEWORD .
            ':scaling>__GENERATEAX__';
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:orientation
     *
     * @access protected
     * @param string $val
     */
    protected function generateORIENTATION($val = 'minMax')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':orientation val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD . ':orientation>';
        $this->_xmlChart = str_replace(
            '__GENERATESCALING__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:axPos
     *
     * @access protected
     * @param string $val
     */
    protected function generateAXPOS($val = 'b')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':axPos val="' . $val .
            '"></' . CreateGraphic::NAMESPACEWORD . ':axPos>__GENERATEAX__';
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:tickLblPos
     *
     * @access protected
     * @param string $val
     */
    protected function generateTICKLBLPOS($val = 'nextTo')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':tickLblPos val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':tickLblPos>__GENERATEAX__';
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:crossAx
     *
     * @access protected
     * @param string $val
     */
    protected function generateCROSSAX($val = '59040512')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':crossAx  val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':crossAx >__GENERATEAX__';
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:crosses
     *
     * @access protected
     * @param string $val
     */
    protected function generateCROSSES($val = 'autoZero')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':crosses val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':crosses>__GENERATEAX__';
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:auto
     *
     * @access protected
     * @param string $val
     */
    protected function generateAUTO($val = '1')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':auto val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':auto>__GENERATEAX__';
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:lblAlgn
     *
     * @access protected
     * @param string $val
     */
    protected function generateLBLALGN($val = 'ctr')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':lblAlgn val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':lblAlgn>__GENERATEAX__';
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:lblOffset
     *
     * @access protected
     * @param string $val
     */
    protected function generateLBLOFFSET($val = '100')
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':lblOffset val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD . ':lblOffset>';
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:majorGridlines
     *
     * @access protected
     */
    protected function generateMAJORGRIDLINES()
    {
        $xml = '<' . CreateGraphic::NAMESPACEWORD . ':majorGridlines></' .
            CreateGraphic::NAMESPACEWORD . ':majorGridlines>__GENERATEAX__';
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', $xml, $this->_xmlChart
        );
    }

    /**
     * Generate w:numFmt
     *
     * @access protected
     * @param string $formatCode
     * @param string $sourceLinked
     */
    protected function generateNUMFMT($formatCode='General',
                                      $sourceLinked = '1')
    {
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', '<' .
            CreateGraphic::NAMESPACEWORD .
            ':numFmt formatCode="' . $formatCode .
            '" sourceLinked="' . $sourceLinked . '"></' .
            CreateGraphic::NAMESPACEWORD . ':numFmt>__GENERATEAX__',
            $this->_xmlChart
        );
    }

    /**
     * Generate w:crossBetween
     *
     * @access protected
     * @param string $val
     */
    protected function generateCROSSBETWEEN($val = 'between')
    {
        $this->_xmlChart = str_replace(
            '__GENERATEAX__', '<' .
            CreateGraphic::NAMESPACEWORD . ':crossBetween val="' .
            $val . '"></' . CreateGraphic::NAMESPACEWORD .
            ':crossBetween>',
            $this->_xmlChart
        );
    }

    /**
     * Create chart xml
     *
     * @access protected
     * @param array $args[0]
     */
    protected function createCHARTXML()
    {
        $this->_xmlChart = '';
        $args = func_get_args();
        $type = $args[0][1]['type'];
        $this->setRId($args[0][0]);
        $this->generateCHARTSPACE();
        $this->generateDATE1904(1);
        $this->generateLANG();
        $this->generateSTYLE();
        $this->generateCHART();
        if (!empty($args[0][1]['title'])) {
            $this->generateTITLE();
            $title = $args[0][1]['title'];
            $this->generateTITLETX();
            $this->generateRICH();
            $this->generateBODYPR();
            $this->generateLSTSTYLE();
            $this->generateTITLEP();
            $this->generateTITLEPPR();
            $this->generateDEFRPR();
            if (!empty($args[0][1]['font'])) {
                $this->generateRFONTS($args[0][1]['font']);
            }
            $this->generateTITLER();
            $this->generateTITLERPR();
            if (!empty($args[0][1]['font'])) {
                $this->generateTITLERFONTS($args[0][1]['font']);
            }
            $this->generateTITLET($title);
            $this->generateTITLELAYOUT();
        } else {
            $this->generateAUTOTITLEDELETED();
            $title = '';
        }
        if (empty($args[0][1]['data'])) {
            exit('You haven`t added data');
        }
        $dats = $args[0][1]['data'];
        $this->generatePLOTAREA();
        $this->generateLAYOUT();
        if (strpos($type, 'pie') !== false) {
            $this->generatePIECHART();
            $this->generateVARYCOLORS();
            if (isset($dats[0])) { 
                $legends = $dats[0];
            } else {
                $legends = array($title);
            }
            $sizeDats = count($dats);
        } elseif (
            (strpos($type, 'bar') !== false) ||
            (strpos($type, 'col') !== false)
            ) {
            $this->generateBARCHART();
            $typeBar = 'bar';
            if (strpos($type, 'col') !== false) {
                $typeBar = 'col';
            }
            $this->generateBARDIR($typeBar);
            $groupBar = 'clustered';
            $this->generateGROUPING($groupBar);
            if (isset($dats[0])) {
                $legends = $dats[0];
            } else {
                echo('You must write a legend');
                return false;
            }
            $sizeDats = count($dats) - 1;
        } elseif (strpos($type, 'line') !== false) {
            $this->generateLINECHART();
            $this->generateGROUPING('standard');
            if (isset($dats[0])) $legends = $dats[0];
            else {
                echo('You must write a legend');
                return false;
            }
            $sizeDats = count($dats) - 1;
        } else {
            echo('The graphic is not available');
            return false;
        }


        $chart = 'A';
        for ($i = 0; $i < count($legends); $i++) {
            $this->generateSER();
            $this->generateIDX($i);
            $this->generateORDER($i);
            $chart++;

            $this->generateTX();
            $this->generateSTRREF();
            $this->generateF(' Hoja1!$' . $chart . '$1');
            $this->generateSTRCACHE();
            $this->generatePTCOUNT();
            $this->generatePT();
            if (strpos($args[0][1]['type'], 'pie') !== false) {
                $this->generateV($title);
            } else {
                $this->generateV($legends[$i]);
            }

            $this->cleanTemplate2();
            $this->generateCAT();
            $this->generateSTRREF();
            $this->generateF('Hoja1!$A$2:$A$' . ($sizeDats + 1));
            $this->generateSTRCACHE();
            $this->generatePTCOUNT($sizeDats);

            $num = 0;
            foreach ($dats as $legend => $dat) {
                if ($legend == '0') continue;
                $this->generatePT($num);
                $this->generateV($legend);
                $num++;
            }
            $this->cleanTemplate2();
            $this->generateVAL();
            $this->generateNUMREF();
            $this->generateF('Hoja1!$' . $chart . '$2:$B$' . ($sizeDats + 1));
            $this->generateNUMCACHE();
            $this->generateFORMATCODE();
            $this->generatePTCOUNT($sizeDats);
            $num = 0;
            foreach ($dats as $legend => $dat) {
                if ($legend == '0') continue;
                $this->generatePT($num);
                $this->generateV($dat[$i]);
                $num++;
            }
            $this->cleanTemplate3();
        }
        if (strpos($args[0][1]['type'], 'pie') !== false) {
            $this->generateDLBLS();
            $this->generateSHOWPERCENT();
            $this->generateFIRSTSLICEANG();
        } else {
            $this->generateSHAPE();
            $this->generateAXID();
            $this->generateAXID(59040512);
            $this->generateCATAX();
            $this->generateAXAXID(59034624);
            $this->generateSCALING();
            $this->generateORIENTATION();
            $this->generateAXPOS();
            $this->generateTICKLBLPOS();
            $this->generateCROSSAX();
            $this->generateCROSSES();
            $this->generateAUTO();
            $this->generateLBLALGN();
            $this->generateLBLOFFSET();
            $this->generateVALAX();
            $this->generateAXAXID(59040512);
            $this->generateSCALING();
            $this->generateORIENTATION();
            $this->generateAXPOS('l');
            $this->generateMAJORGRIDLINES();
            $this->generateNUMFMT();
            $this->generateTICKLBLPOS();
            if (!empty($args[0][1]['font'])) {
                $this->generateRFONTS2($args[0][1]['font']);
            }
            $this->generateCROSSAX(59034624);
            $this->generateCROSSES();
            $this->generateCROSSBETWEEN();
        }

        $this->generateLEGEND();
        $this->generateLEGENDPOS();
        if (!empty($args[0][1]['font'])) {
            $this->generateLEGENDFONT($args[0][1]['font']);
        }

        $this->generatePLOTVISONLY();
        if (strpos($args[0][1]['type'], 'pieChart') === false) {
            $this->generateSPPR();
            $this->generateLN();
            $this->generateNOFILL();
        }

        $this->generateEXTERNALDATA();
        $this->cleanTemplateDocument();
        return true;
    }

    /**
     * Create document xml
     *
     * @access protected
     * @param array $args[0]
     */
    protected function createDOCUEMNTXML()
    {
        $args = func_get_args();
        $ajusteTexto = 0;
        if (isset($args[0][1]['sizeX']))
            $sizeX = $args[0][1]['sizeX'] * CreateImage::CONSTWORD;
        else {
            $sizeX = 2993296;
        }
        if (isset($args[0][1]['sizeY']))
            $sizeY = $args[0][1]['sizeY'] * CreateImage::CONSTWORD;
        else {
            $sizeY = 2238233;
        }


        $this->_xml = '';
        $this->generateP();
        $this->generateR();
        $this->generateRPR();
        $this->generateNOPROOF();
        $this->generateDRAWING();
        $this->generateINLINE();
        $this->generateEXTENT($sizeX, $sizeY);
        $this->generateEFFECTEXTENT();
        $this->generateDOCPR();
        $this->generateCNVGRAPHICFRAMEPR();
        $this->generateGRAPHIC();
        $this->generateGRAPHICDATA(
            'http://schemas.openxmlformats.org/'.
            'drawingml/2006/chart'
        );
        $this->generateDOCUMENTCHART();
        $this->cleanTemplate();
        return true;
    }

    /**
     * Clean tags in template document
     *
     * @access protected
     */
    private function cleanTemplateDocument()
    {
        $this->_xmlChart = preg_replace('/__[A-Z]+__/', '', $this->_xmlChart);
    }

    /**
     * Clean tags in template document
     *
     * @access protected
     */
    private function cleanTemplate2()
    {
        $this->_xmlChart = preg_replace(
            array(
                '/__GENERATE[A-B,D-O,Q-R,U-Z][A-Z]+__/',
                '/__GENERATES[A-D,F-Z][A-Z]+__/', '/__GENERATETX__/'),
            '',
            $this->_xmlChart
        );
    }

    /**
     * Clean tags in template document
     *
     * @access protected
     */
    private function cleanTemplate3()
    {
        $this->_xmlChart = preg_replace(
            array(
                '/__GENERATE[A-B,D-O,Q-S,U-Z][A-Z]+__/',
                '/__GENERATES[A-D,F-Z][A-Z]+__/',
                '/__GENERATETX__/'
                ),
            '',
            $this->_xmlChart
        );
    }

    /**
     * Generate c:txPr
     *
     * @access protected
     * @param string $font
     */
    private function generateRFONTS2($font)
    {
        $this->_xmlChart = str_replace(
            '__GENERATEAX__',
            '<c:txPr><a:bodyPr /><a:lstStyle /><a:p>' .
            '<a:pPr><a:defRPr><a:latin typeface="' .
            $font . '" pitchFamily="34" charset="0" /><a:cs typeface="' .
            $font . '" pitchFamily="34" charset="0" /></a:defRPr>' .
            '</a:pPr><a:endParaRPr lang="es-ES" /></a:p></c:txPr>' .
            '__GENERATEAX__', $this->_xmlChart
        );
    }

}
