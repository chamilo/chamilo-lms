<?php

/**
 * Create style table
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
 * Create style table
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
class CreateStyleTable extends CreateStyle
{

    /**
     * @access protected
     * @var string
     */
    protected $_xml;
    /**
     * @access private
     * @var CreateStyleTable
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
     * @return CreateStyleTable
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateStyleTable();
        }
        return self::$_instance;
    }

    /**
     * Add table style
     *
     * @access public
     * @param array $args[0]
     */
    public function addStyleTable()
    {
        $this->_xml = '';
        $args = func_get_args();
        if (!empty($args[0]['type'])) {
            $this->generateTBLSTYLEPR($args[0]['type']);
        }
        if (!empty($args[0]['name'])) {
            $this->generateNAME($args[0]['name']);
        }
        if (!empty($args[0]['basedOn'])) {
            $this->generateBASEDON($args[0]['basedOn']);
        }
        if (!empty($args[0]['next'])) {
            $this->generateNEXT($args[0]['next']);
        }
        if (!empty($args[0]['link'])) {
            $this->generateLINK($args[0]['link']);
        }
        if ($args[0]['autoRedefine'] == 1) {
            $this->generateAUTOREDEFINE();
        }
        if (!empty($args[0]['uiPriority'])) {
            $this->generateUIPRIORITY($args[0]['uiPriority']);
        }
        if ($args[0]['semiHidden'] == 1) {
            $this->generateSEMIHIDDEN();
        }
        if ($args[0]['unhideWhenUsed'] == 1) {
            $this->generateUNHIDEWHENUSED();
        }
        if ($args[0]['qFormat'] == 1) {
            $this->generateQFORMAT();
        }
        if (!empty($args[0]['rsid'])) {
            $this->generateRSID($args[0]['rsid']);
        }
        if ($args[0]['keepNext'] == 1 ||
            !empty($args[0]['keepLines']) ||
            (isset($args[0]['spacing_before']) ||
            isset($args[0]['spacing_after']) ||
            isset($args[0]['spacing_line']) ||
            isset($args[0]['spacing_lineRule'])) ||
            !empty($args[0]['outlineLvl']) ||
            $args[0]['contextualSpacing'] == 1 ||
            isset($args[0]['ilvl']) ||
            isset($args[0]['ind_left'])
        ) {
            $this->generatePPR();
            if ($args[0]['keepNext'] == 1) {
                $this->generateKEEPNEXT();
            }
            if (!empty($args[0]['keepLines'])) {
                $this->generateKEEPLINES($args[0]['keepLines']);
            }
            if (isset($args[0]['pBdr_bottom_val']) &&
                isset($args[0]['pBdr_bottom_sz']) &&
                isset($args[0]['pBdr_bottom_space']) &&
                isset($args[0]['pBdr_bottom_color']) &&
                isset($args[0]['pBdr_bottom_themeColor'])
            ) {
                $this->generatePBDR();
                $this->generatePBDR_BOTTOM(
                    $args[0]['pBdr_bottom_val'],
                    $args[0]['pBdr_bottom_sz'],
                    $args[0]['pBdr_bottom_space'],
                    $args[0]['pBdr_bottom_color'],
                    $args[0]['pBdr_bottom_themeColor']
                );
            }
            if (!empty($args[0]['tab_center']) || $args[0]['tab_right']) {
                $this->generateTABS();
                if (!empty($args[0]['tab_center'])) {
                    $this->generateTABCENTER($args[0]['tab_center']);
                }
                if (!empty($args[0]['tab_right'])) {
                    $this->generateTABRIGHT($args[0]['tab_right']);
                }
            }
            if (isset($args[0]['spacing_before']) ||
                isset($args[0]['spacing_after']) ||
                isset($args[0]['spacing_line']) ||
                isset($args[0]['spacing_lineRule'])
            ) {
                $this->generateSPACING(
                    $args[0]['spacing_before'],
                    $args[0]['spacing_after'],
                    $args[0]['spacing_line'],
                    $args[0]['spacing_lineRule']
                );
            }
            if (isset($args[0]['ind_left'])) {
                $this->generateIND($args[0]['ind_left']);
            }
            if ($args[0]['contextualSpacing'] == 1) {
                $this->generateCONTEXTUALSPACING();
            }
            if (isset($args[0]['outlineLvl'])) {
                $this->generateOUTLINELVL($args[0]['outlineLvl']);
            }
            if (isset($args[0]['ilvl'])) {
                $this->generateNUMPR();
                $this->generateILVL($args[0]['ilvl']);
            }
        }
        if ((!empty($args[0]['rFonts_asciiTheme']) &&
            !empty($args[0]['rFonts_eastAsiaTheme']) &&
            !empty($args[0]['rFonts_hAnsiTheme']) &&
            !empty($args[0]['rFonts_cstheme'])) ||
            $args[0]['b'] == 1 ||
            $args[0]['bCs'] == 1 ||
            $args[0]['i'] == 1 ||
            $args[0]['iCs'] == 1 ||
            $args[0]['u'] == 1 ||
            !empty($args[0]['color_val']) ||
            !empty($args[0]['sz']) ||
            !empty($args[0]['szCs']) ||
            !empty($args[0]['kern']) ||
            !empty($args[0]['rPr_spacing']) ||
            !empty($args[0]['u'])
        ) {

            $this->generateRPR();
            if (!empty($args[0]['rFonts_asciiTheme']) &&
                !empty($args[0]['rFonts_eastAsiaTheme']) &&
                !empty($args[0]['rFonts_hAnsiTheme']) &&
                !empty($args[0]['rFonts_cstheme'])
            ) {
                $this->generateRFONTS(
                    $args[0]['rFonts_asciiTheme'],
                    $args[0]['rFonts_eastAsiaTheme'],
                    $args[0]['rFonts_hAnsiTheme'],
                    $args[0]['rFonts_cstheme']
                );
            }
            if (!empty($args[0]['rFonts_ascii']) &&
                !empty($args[0]['rFonts_hAnsi']) &&
                !empty($args[0]['rFonts_cs'])
            ) {
                $this->generateRFONTS2(
                    $args[0]['rFonts_ascii'],
                    $args[0]['rFonts_hAnsi'],
                    $args[0]['rFonts_cs']
                );
            }
            if ($args[0]['b'] == 1) {
                $this->generateB();
            }
            if ($args[0]['bCs'] == 1) {
                $this->generateBCS();
            }
            if (isset($args[0]['i'])) {
                $this->generateI($args[0]['i']);
            }
            if (isset($args[0]['iCs'])) {
                $this->generateICS($args[0]['iCs']);
            }
            if ($args[0]['u'] == 1) {
                $this->generateU();
            }
            if (!empty($args[0]['color_val'])) {
                $this->generateCOLOR(
                    $args[0]['color_val'],
                    $args[0]['color_themeColor'],
                    $args[0]['color_themeShade']
                );
            }
            if (!empty($args[0]['u'])) {
                $this->generateU($args[0]['u']);
            }
            if (!empty($args[0]['rPr_spacing'])) {
                $this->generateRPR_SPACING($args[0]['rPr_spacing']);
            }
            if (!empty($args[0]['kern'])) {
                $this->generateKERN($args[0]['kern']);
            }
            if (!empty($args[0]['sz'])) {
                $this->generateSZ($args[0]['sz']);
            }
            if (!empty($args[0]['szCs'])) {
                $this->generateSZCS($args[0]['szCs']);
            }
        }
        if (isset($args[0]['tblPr'])) {
            $this->generateTBLPR();
        }
        if ((isset($args[0]['top_w']) &&
            isset($args[0]['top_type'])) ||
            (isset($args[0]['left_w']) &&
            isset($args[0]['left_type'])) ||
            (isset($args[0]['bottom_w']) &&
            isset($args[0]['bottom_type'])) ||
            (isset($args[0]['right_w']) &&
            isset($args[0]['right_type'])) ||
            (isset($args[0]['tblInd_w']) &&
            isset($args[0]['tblInd_type'])) &&
            isset($args[0]['tblborder_top_val']) ||
            isset($args[0]['tblborder_left_val']) ||
            isset($args[0]['tblborder_bottom_val']) ||
            isset($args[0]['tblborder_right_val']) ||
            isset($args[0]['tblborder_insideH_val']) ||
            isset($args[0]['tblborder_insideV_val']) ||
            (isset($args[0]['shd_val']) &&
            isset($args[0]['shd_color']) &&
            isset($args[0]['shd_fill']) &&
            isset($args[0]['shd_themeFill']))
        ) {
            $this->generateTCPR();
            if (isset($args[0]['tbl_style_row'])) {
                $this->generateTBLSTYLEROWBANDSIZE($args[0]['tbl_style_row']);
            }
            if (isset($args[0]['tbl_style_col'])) {
                $this->generateTBLSTYLECOLBANDSIZE($args[0]['tbl_style_col']);
            }

            if (isset($args[0]['tblInd_w']) &&
                isset($args[0]['tblInd_type'])
            ) {

                $this->generateTBLIND(
                    $args[0]['tblInd_w'],
                    $args[0]['tblInd_type']
                );
            }

            if (isset($args[0]['tblborder_top_val']) ||
                isset($args[0]['tblborder_left_val']) ||
                isset($args[0]['tblborder_bottom_val']) ||
                isset($args[0]['tblborder_right_val']) ||
                isset($args[0]['tblborder_insideH_val']) ||
                isset($args[0]['tblborder_insideV_val'])
            ) {
                $this->generateTCBORDERS();
                if (isset($args[0]['tblborder_top_val'])) {
                    $this->generateTBLBORDERS_TOP(
                        $args[0]['tblborder_top_val'],
                        $args[0]['tblborder_top_sz'],
                        $args[0]['tblborder_top_space'],
                        $args[0]['tblborder_top_color'],
                        $args[0]['tblborder_top_themeColor'],
                        $args[0]['tblborder_insideV_themeTint']
                    );
                }
                if (isset($args[0]['tblborder_left_val'])) {
                    $this->generateTBLBORDERS_LEFT(
                        $args[0]['tblborder_left_val'],
                        $args[0]['tblborder_left_sz'],
                        $args[0]['tblborder_left_space'],
                        $args[0]['tblborder_left_color'],
                        $args[0]['tblborder_left_themeColor'],
                        $args[0]['tblborder_insideV_themeTint']
                    );
                }
                if (isset($args[0]['tblborder_bottom_val'])) {
                    $this->generateTBLBORDERS_BOTTOM(
                        $args[0]['tblborder_bottom_val'],
                        $args[0]['tblborder_bottom_sz'],
                        $args[0]['tblborder_bottom_space'],
                        $args[0]['tblborder_bottom_color'],
                        $args[0]['tblborder_bottom_themeColor'],
                        $args[0]['tblborder_insideV_themeTint']
                    );
                }
                if (isset($args[0]['tblborder_right_val'])) {
                    $this->generateTBLBORDERS_RIGHT(
                        $args[0]['tblborder_right_val'],
                        $args[0]['tblborder_right_sz'],
                        $args[0]['tblborder_right_space'],
                        $args[0]['tblborder_right_color'],
                        $args[0]['tblborder_right_themeColor'],
                        $args[0]['tblborder_insideV_themeTint']
                    );
                }
                if (isset($args[0]['tblborder_insideH_val'])) {
                    $this->generateTBLBORDERS_INSIDEH(
                        $args[0]['tblborder_insideH_val'],
                        $args[0]['tblborder_insideH_sz'],
                        $args[0]['tblborder_insideH_space'],
                        $args[0]['tblborder_insideH_color'],
                        $args[0]['tblborder_insideH_themeColor'],
                        $args[0]['tblborder_insideV_themeTint']
                    );
                }
                if (isset($args[0]['tblborder_insideV_val'])) {
                    $this->generateTBLBORDERS_INSIDEV(
                        $args[0]['tblborder_insideV_val'],
                        $args[0]['tblborder_insideV_sz'],
                        $args[0]['tblborder_insideV_space'],
                        $args[0]['tblborder_insideV_color'],
                        $args[0]['tblborder_insideV_themeColor'],
                        $args[0]['tblborder_insideV_themeTint']
                    );
                }
            }
            if (isset($args[0]['shd_val']) &&
                isset($args[0]['shd_color']) &&
                isset($args[0]['shd_fill']) &&
                isset($args[0]['shd_themeFill'])
            ) {
                $this->generateSHD(
                    $args[0]['shd_val'],
                    $args[0]['shd_color'],
                    $args[0]['shd_fill'],
                    $args[0]['shd_themeFill'],
                    $args[0]['shd_themeFillTint']
                );
            }
            if ((isset($args[0]['top_w']) &&
                isset($args[0]['top_type'])) ||
                (isset($args[0]['left_w']) &&
                isset($args[0]['left_type'])) ||
                (isset($args[0]['bottom_w']) &&
                isset($args[0]['bottom_type'])) ||
                (isset($args[0]['right_w']) &&
                isset($args[0]['right_type']))
            ) {
                $this->generateTBLCELLMAR();

                if (isset($args[0]['top_w']) &&
                    isset($args[0]['top_type'])) {
                    $this->generateTOP(
                        $args[0]['top_w'],
                        $args[0]['top_type']
                    );
                }
                if (isset($args[0]['left_w']) &&
                    isset($args[0]['left_type'])) {
                    $this->generateLEFT(
                        $args[0]['left_w'],
                        $args[0]['left_type']
                    );
                }
                if (isset($args[0]['bottom_w']) &&
                    isset($args[0]['bottom_type'])) {
                    $this->generateBOTTOM(
                        $args[0]['bottom_w'],
                        $args[0]['bottom_type']
                    );
                }
                if (isset($args[0]['right_w']) &&
                    isset($args[0]['right_type'])) {
                    $this->generateRIGHT(
                        $args[0]['right_w'],
                        $args[0]['right_type']
                    );
                }
            }
        }
        $this->cleanTemplate();
        $this->_xml .= '__GENERATESTYLE__';
    }

    /**
     * Generate w:tblStylePr
     *
     * @access protected
     * @param string $type
     */
    protected function generateTBLSTYLEPR($type)
    {
        $this->_xml .= '<' . CreateElement::NAMESPACEWORD .
            ':tblStylePr ' . CreateElement::NAMESPACEWORD .
            ':type="' . $type . '">__GENERATESTYLE__</' .
            CreateElement::NAMESPACEWORD . ':tblStylePr>';
    }

    /**
     * Generate w:tblPr
     *
     * @access protected
     */
    protected function generateTBLPR()
    {
        $this->_xml = str_replace(
            '__GENERATESTYLE__', '<' .
            CreateElement::NAMESPACEWORD .
            ':tblPr></' . CreateElement::NAMESPACEWORD .
            ':tblPr>__GENERATESTYLE__',
            $this->_xml
        );
    }

    /**
     * Generate w:tcPr
     *
     * @access protected
     */
    protected function generateTCPR()
    {
        $this->_xml = str_replace(
            '__GENERATESTYLE__', '<' .
            CreateElement::NAMESPACEWORD .
            ':tcPr>__GENERATETBLPR__</' . CreateElement::NAMESPACEWORD .
            ':tcPr>__GENERATESTYLE__',
            $this->_xml
        );
    }

    /**
     * Generate w:tcBorders
     *
     * @access protected
     */
    protected function generateTCBORDERS()
    {
        $this->_xml = str_replace(
            '__GENERATETBLPR__', '<' .
            CreateElement::NAMESPACEWORD .
            ':tcBorders>__GENERATETBLBORDERS__</' .
            CreateElement::NAMESPACEWORD . ':tcBorders>__GENERATETBLPR__',
            $this->_xml
        );
    }

    /**
     * Generate w:shd
     *
     * @access protected
     * @param string $val
     * @param string $color
     * @param string $fill
     * @param string $themeFill
     * @param string $themeFillTint
     */
    protected function generateSHD($val, $color = '', $fill = '',
                                   $themeFill = '', $themeFillTint = '')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD . ':shd ' .
            CreateElement::NAMESPACEWORD . ':val="' . $val . '"';
        if ($color != '')
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':color="' . $color . '"';
        if ($fill != '')
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':fill="' . $fill . '"';
        if ($themeFill != '')
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeFill="' . $themeFill . '"';
        if ($themeFillTint != '')
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeFillTint="' . $themeFillTint . '"';
        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':shd>__GENERATETBLPR__';
        $this->_xml = str_replace('__GENERATETBLPR__', $xmlAux, $this->_xml);
    }

}