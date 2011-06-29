<?php

/**
 * Create styles
 *
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) 2009-2011 Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    LGPL
 * @version    2.1
 * @link       http://www.phpdocx.com
 * @since      File available since Release 1.0
 */
include_once dirname(__FILE__) . '/CreateElement.inc';

/**
 * Create styles
 *
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) 2009-2011 Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    http://www.phpdocx.com/wp-content/themes/lightword/pro_license.php
 * @version    2.1
 * @link       http://www.phpdocx.com
 * @since      Class available since Release 1.0
 */
class CreateStyle extends CreateElement
{

    /**
     * @access protected
     * @var string
     */
    protected $_xml;
    /**
     * @access private
     * @var CreateStyle
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
     * @return CreateStyle
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateStyle();
        }
        return self::$_instance;
    }

    /**
     * Add style
     *
     * @access public
     * @param array $args[0]
     */
    public function addStyle()
    {
        $args = func_get_args();
        if (!empty($args[0][0]['type']) && !empty($args[0][0]['styleId'])) {
            $this->generateSTYLE(
                $args[0][0]['type'], $args[0][0]['styleId'],
                $args[0][0]['default'], $args[0][0]['customStyle']
            );
        }
        if (!empty($args[0][0]['name'])) {
            $this->generateNAME($args[0][0]['name']);
        }
        if (!empty($args[0][0]['basedOn'])) {
            $this->generateBASEDON($args[0][0]['basedOn']);
        }
        if (!empty($args[0][0]['next'])) {
            $this->generateNEXT($args[0][0]['next']);
        }
        if (!empty($args[0][0]['link'])) {
            $this->generateLINK($args[0][0]['link']);
        }
        if ($args[0][0]['autoRedefine'] == 1) {
            $this->generateAUTOREDEFINE();
        }
        if (!empty($args[0][0]['uiPriority'])) {
            $this->generateUIPRIORITY($args[0][0]['uiPriority']);
        }
        if ($args[0][0]['semiHidden'] == 1) {
            $this->generateSEMIHIDDEN();
        }
        if ($args[0][0]['unhideWhenUsed'] == 1) {
            $this->generateUNHIDEWHENUSED();
        }
        if ($args[0][0]['qFormat'] == 1) {
            $this->generateQFORMAT();
        }
        if (!empty($args[0][0]['rsid'])) {
            $this->generateRSID($args[0][0]['rsid']);
        }
        if ($args[0][0]['keepNext'] == 1 ||
            !empty($args[0][0]['keepLines']) ||
            (isset($args[0][0]['spacing_before']) ||
            isset($args[0][0]['spacing_after']) ||
            isset($args[0][0]['spacing_line']) ||
            isset($args[0][0]['spacing_lineRule'])) ||
            !empty($args[0][0]['outlineLvl']) ||
            $args[0][0]['contextualSpacing'] == 1 ||
            isset($args[0][0]['ilvl']) ||
            isset($args[0][0]['ind_left'])) {
            $this->generatePPR();
            if ($args[0][0]['keepNext'] == 1)
                $this->generateKEEPNEXT();

            if (!empty($args[0][0]['keepLines'])) {
                $this->generateKEEPLINES($args[0][0]['keepLines']);
            }

            if (isset($args[0][0]['pBdr_bottom_val']) &&
                isset($args[0][0]['pBdr_bottom_sz']) &&
                isset($args[0][0]['pBdr_bottom_space']) &&
                isset($args[0][0]['pBdr_bottom_color']) &&
                isset($args[0][0]['pBdr_bottom_themeColor'])) {
                $this->generatePBDR();
                $this->generatePBDR_BOTTOM(
                    $args[0][0]['pBdr_bottom_val'],
                    $args[0][0]['pBdr_bottom_sz'],
                    $args[0][0]['pBdr_bottom_space'],
                    $args[0][0]['pBdr_bottom_color'],
                    $args[0][0]['pBdr_bottom_themeColor']
                );
            }

            if (!empty($args[0][0]['tab_center']) || $args[0][0]['tab_right']) {
                $this->generateTABS();
                if (!empty($args[0][0]['tab_center'])) {
                    $this->generateTABCENTER($args[0][0]['tab_center']);
                }
                if (!empty($args[0][0]['tab_right'])) {
                    $this->generateTABRIGHT($args[0][0]['tab_right']);
                }
            }

            if (isset($args[0][0]['spacing_before']) ||
                isset($args[0][0]['spacing_after']) ||
                isset($args[0][0]['spacing_line']) ||
                isset($args[0][0]['spacing_lineRule'])) {
                $this->generateSPACING(
                    $args[0][0]['spacing_before'],
                    $args[0][0]['spacing_after'],
                    $args[0][0]['spacing_line'],
                    $args[0][0]['spacing_lineRule']
                );
            }
            if (isset($args[0][0]['ind_left'])) {
                $this->generateIND($args[0][0]['ind_left']);
            }
            if ($args[0][0]['contextualSpacing'] == 1) {
                $this->generateCONTEXTUALSPACING();
            }
            if (isset($args[0][0]['outlineLvl'])) {
                $this->generateOUTLINELVL($args[0][0]['outlineLvl']);
            }
            if (isset($args[0][0]['ilvl'])) {
                $this->generateNUMPR();
                $this->generateILVL($args[0][0]['ilvl']);
            }
        }
        if ((!empty($args[0][0]['rFonts_asciiTheme']) &&
            !empty($args[0][0]['rFonts_eastAsiaTheme']) &&
            !empty($args[0][0]['rFonts_hAnsiTheme']) &&
            !empty($args[0][0]['rFonts_cstheme'])) ||
            $args[0][0]['b'] == 1 ||
            $args[0][0]['bCs'] == 1 ||
            $args[0][0]['i'] == 1 ||
            $args[0][0]['iCs'] == 1 ||
            $args[0][0]['u'] == 1 ||
            !empty($args[0][0]['color_val']) ||
            !empty($args[0][0]['sz']) ||
            !empty($args[0][0]['szCs']) ||
            !empty($args[0][0]['kern']) ||
            !empty($args[0][0]['rPr_spacing']) ||
            !empty($args[0][0]['u']) ||
            !empty($args[0][0]['vertAlign'])) {
            $this->generateRPR();
            if (!empty($args[0][0]['rFonts_asciiTheme']) &&
                !empty($args[0][0]['rFonts_eastAsiaTheme']) &&
                !empty($args[0][0]['rFonts_hAnsiTheme']) &&
                !empty($args[0][0]['rFonts_cstheme']))
                $this->generateRFONTS(
                    $args[0][0]['rFonts_asciiTheme'],
                    $args[0][0]['rFonts_eastAsiaTheme'],
                    $args[0][0]['rFonts_hAnsiTheme'],
                    $args[0][0]['rFonts_cstheme']
                );

            if (!empty($args[0][0]['rFonts_ascii']) &&
                !empty($args[0][0]['rFonts_hAnsi']) &&
                !empty($args[0][0]['rFonts_cs'])) {
                $this->generateRFONTS2(
                    $args[0][0]['rFonts_ascii'],
                    $args[0][0]['rFonts_hAnsi'],
                    $args[0][0]['rFonts_cs']
                );
            }
            if (isset($args[0][0]['b']) && $args[0][0]['b'] == 1) {
                $this->generateB();
            }
            if (isset($args[0][0]['bCs']) && $args[0][0]['bCs'] == 1) {
                $this->generateBCS();
            }
            if (isset($args[0][0]['i'])) {
                $this->generateI($args[0][0]['i']);
            }
            if (isset($args[0][0]['iCs'])) {
                $this->generateICS($args[0][0]['iCs']);
            }
            if (isset($args[0][0]['u']) && $args[0][0]['u'] == 1) {
                $this->generateU();
            }
            if (!empty($args[0][0]['color_val'])) {
                $this->generateCOLOR(
                    $args[0][0]['color_val'],
                    $args[0][0]['color_themeColor'],
                    $args[0][0]['color_themeShade']
                );
            }
            if (!empty($args[0][0]['u'])) {
                $this->generateU($args[0][0]['u']);
            }
            if (!empty($args[0][0]['rPr_spacing'])) {
                $this->generateRPR_SPACING($args[0][0]['rPr_spacing']);
            }
            if (!empty($args[0][0]['kern'])) {
                $this->generateKERN($args[0][0]['kern']);
            }
            if (!empty($args[0][0]['sz'])) {
                $this->generateSZ($args[0][0]['sz']);
            }
            if (!empty($args[0][0]['szCs'])) {
                $this->generateSZCS($args[0][0]['szCs']);
            }
            if (!empty($args[0][0]['vertAlign'])) {
                $this->generateVERTALIGN($args[0][0]['vertAlign']);
            }
        }
        if ((isset($args[0][0]['top_w']) &&
            isset($args[0][0]['top_type'])) ||
            (isset($args[0][0]['left_w']) &&
            isset($args[0][0]['left_type'])) ||
            (isset($args[0][0]['bottom_w']) &&
            isset($args[0][0]['bottom_type'])) ||
            (isset($args[0][0]['right_w']) &&
            isset($args[0][0]['right_type'])) ||
            (isset($args[0][0]['tblInd_w']) &&
            isset($args[0][0]['tblInd_type'])) &&
            (isset($args[0][0]['tblborder_top_val'])) ||
            (isset($args[0][0]['tblborder_left_val'])) ||
            (isset($args[0][0]['tblborder_bottom_val'])) ||
            (isset($args[0][0]['tblborder_right_val'])) ||
            (isset($args[0][0]['tblborder_insideH_val'])) ||
            (isset($args[0][0]['tblborder_insideV_val']))) {
            $this->generateTBLPR();
            if (isset($args[0][0]['tbl_style_row'])) {
                $this->generateTBLSTYLEROWBANDSIZE(
                    $args[0][0]['tbl_style_row']
                );
            }
            if (isset($args[0][0]['tbl_style_col'])) {
                $this->generateTBLSTYLECOLBANDSIZE(
                    $args[0][0]['tbl_style_col']
                );
            }
            if (isset($args[0][0]['tblInd_w']) &&
                isset($args[0][0]['tblInd_type'])) {
                $this->generateTBLIND(
                    $args[0][0]['tblInd_w'],
                    $args[0][0]['tblInd_type']
                );
            }
            if ((isset($args[0][0]['tblborder_top_val'])) ||
                (isset($args[0][0]['tblborder_left_val'])) ||
                (isset($args[0][0]['tblborder_bottom_val'])) ||
                (isset($args[0][0]['tblborder_right_val'])) ||
                (isset($args[0][0]['tblborder_insideH_val'])) ||
                (isset($args[0][0]['tblborder_insideV_val']))) {
                $this->generateTBLBORDERS();

                if (isset($args[0][0]['tblborder_top_val'])) {
                    $this->generateTBLBORDERS_TOP(
                        $args[0][0]['tblborder_top_val'],
                        $args[0][0]['tblborder_top_sz'],
                        $args[0][0]['tblborder_top_space'],
                        $args[0][0]['tblborder_top_color'],
                        $args[0][0]['tblborder_top_themeColor'],
                        $args[0][0]['tblborder_insideV_themeTint']
                    );
                }
                if (isset($args[0][0]['tblborder_left_val'])) {
                    $this->generateTBLBORDERS_LEFT(
                        $args[0][0]['tblborder_left_val'],
                        $args[0][0]['tblborder_left_sz'],
                        $args[0][0]['tblborder_left_space'],
                        $args[0][0]['tblborder_left_color'],
                        $args[0][0]['tblborder_left_themeColor'],
                        $args[0][0]['tblborder_insideV_themeTint']
                    );
                }
                if (isset($args[0][0]['tblborder_bottom_val'])) {
                    $this->generateTBLBORDERS_BOTTOM(
                        $args[0][0]['tblborder_bottom_val'],
                        $args[0][0]['tblborder_bottom_sz'],
                        $args[0][0]['tblborder_bottom_space'],
                        $args[0][0]['tblborder_bottom_color'],
                        $args[0][0]['tblborder_bottom_themeColor'],
                        $args[0][0]['tblborder_insideV_themeTint']
                    );
                }
                if (isset($args[0][0]['tblborder_right_val'])) {
                    $this->generateTBLBORDERS_RIGHT(
                        $args[0][0]['tblborder_right_val'],
                        $args[0][0]['tblborder_right_sz'],
                        $args[0][0]['tblborder_right_space'],
                        $args[0][0]['tblborder_right_color'],
                        $args[0][0]['tblborder_right_themeColor'],
                        $args[0][0]['tblborder_insideV_themeTint']
                    );
                }
                if (isset($args[0][0]['tblborder_insideH_val'])) {
                    $this->generateTBLBORDERS_INSIDEH(
                        $args[0][0]['tblborder_insideH_val'],
                        $args[0][0]['tblborder_insideH_sz'],
                        $args[0][0]['tblborder_insideH_space'],
                        $args[0][0]['tblborder_insideH_color'],
                        $args[0][0]['tblborder_insideH_themeColor'],
                        $args[0][0]['tblborder_insideV_themeTint']
                    );
                }
                if (isset($args[0][0]['tblborder_insideV_val'])) {
                    $this->generateTBLBORDERS_INSIDEV(
                        $args[0][0]['tblborder_insideV_val'],
                        $args[0][0]['tblborder_insideV_sz'],
                        $args[0][0]['tblborder_insideV_space'],
                        $args[0][0]['tblborder_insideV_color'],
                        $args[0][0]['tblborder_insideV_themeColor'],
                        $args[0][0]['tblborder_insideV_themeTint']
                    );
                }
            }

            if ((isset($args[0][0]['top_w']) &&
                isset($args[0][0]['top_type'])) ||
                (isset($args[0][0]['left_w']) &&
                isset($args[0][0]['left_type'])) ||
                (isset($args[0][0]['bottom_w']) &&
                isset($args[0][0]['bottom_type'])) ||
                (isset($args[0][0]['right_w']) &&
                isset($args[0][0]['right_type']))) {

                $this->generateTBLCELLMAR();
                if (isset($args[0][0]['top_w']) &&
                    isset($args[0][0]['top_type'])) {
                    $this->generateTOP(
                        $args[0][0]['top_w'],
                        $args[0][0]['top_type']
                    );
                }
                if (isset($args[0][0]['left_w']) &&
                    isset($args[0][0]['left_type'])) {
                    $this->generateLEFT(
                        $args[0][0]['left_w'],
                        $args[0][0]['left_type']
                    );
                }
                if (isset($args[0][0]['bottom_w']) &&
                    isset($args[0][0]['bottom_type'])) {
                    $this->generateBOTTOM(
                        $args[0][0]['bottom_w'],
                        $args[0][0]['bottom_type']
                    );
                }
                if (isset($args[0][0]['right_w']) &&
                    isset($args[0][0]['right_type'])) {
                    $this->generateRIGHT(
                        $args[0][0]['right_w'],
                        $args[0][0]['right_type']
                    );
                }
            }
        }
        if (isset($args[0][0]['shd_val']) &&
            isset($args[0][0]['shd_color']) &&
            isset($args[0][0]['shd_fill']) &&
            isset($args[0][0]['shd_themeFill'])) {
            $this->generateTCPR();
            $this->generateSHD(
                $args[0][0]['shd_val'],
                $args[0][0]['shd_color'],
                $args[0][0]['shd_fill'],
                $args[0][0]['shd_themeFill'],
                $args[0][0]['shd_themeFillTint']
            );
        }
        if (count($args[0]) > 1) {
            for ($i = 1; $i < count($args[0]); $i++) {
                $this->addStyleTabla($args[0][$i]);
            }
        }
        $this->cleanTemplate();
    }

    /**
     * Create style
     *
     * @access public
     * @param string $lang
     */
    public function createStyle($lang = 'en-US')
    {
        $this->_xml .= '<w:docDefaults><w:rPrDefault><w:rPr>' .
            '<w:rFonts w:asciiTheme="minorHAnsi" w:eastAsiaTh' .
            'eme="minorHAnsi" w:hAnsiTheme="minorHAnsi" w:cst' .
            'heme="minorBidi"/><w:sz w:val="22"/><w:szCs w:val="22"/>' .
            '<w:lang w:val="' . $lang . '" w:eastAsia="' . $lang .
            '" w:bidi="ar-SA"/></w:rPr></w:rPrDefault><w:pPrDefault>' .
            '<w:pPr><w:spacing w:after="200" w:line="276" w:lineRule' .
            '="auto"/></w:pPr></w:pPrDefault></w:docDefaults>' .
            '<w:latentStyles w:defLockedState="0" w:defUIPriority="99" ' .
            'w:defSemiHidden="1" w:defUnhideWhenUsed="1" w:defQFormat' .
            '="0" w:count="267">' .
            '<w:lsdException w:name="Normal" w:semiHidden="0" w:uiPriorit' .
            'y="0" w:unhideWhenUsed="0" w:qFormat="1"/><w:lsdException w:n' .
            'ame="heading 1" w:semiHidden="0" w:uiPriority="9" w:unhideWhen' .
            'Used="0" w:qFormat="1"/><w:lsdException w:name="heading 2" w:u' .
            'iPriority="9" w:qFormat="1"/><w:lsdException w:name="heading ' .
            '3" w:uiPriority="9" w:qFormat="1"/><w:lsdException w:name="h' .
            'eading 4" w:uiPriority="9" w:qFormat="1"/><w:lsdException ' .
            'w:name="heading 5" w:uiPriority="9" w:qFormat="1"/><w:lsd' .
            'Exception w:name="heading 6" w:uiPriority="9" w:qFormat=' .
            '"1"/><w:lsdException w:name="heading 7" w:uiPriority="9" ' .
            'w:qFormat="1"/><w:lsdException w:name="heading 8" w:uiPriority' .
            '="9" w:qFormat="1"/><w:lsdException w:name="heading 9" w:uiPr' .
            'iority="9" w:qFormat="1"/><w:lsdException w:name="toc 1" ' .
            'w:uiPriority="39"/><w:lsdException w:name="toc 2" w:ui' .
            'Priority="39"/><w:lsdException w:name="toc 3" w:ui' .
            'Priority="39"/><w:lsdException w:name="toc 4" w:uiPr' .
            'iority="39"/><w:lsdException w:name="toc 5" w:uiPrior' .
            'ity="39"/><w:lsdException w:name="toc 6" w:uiPriority="39"/>' .
            '<w:lsdException w:name="toc 7" w:uiPriority="39"/>' .
            '<w:lsdException w:name="toc 8" w:uiPriority="39"/>' .
            '<w:lsdException w:name="toc 9" w:uiPriority="39"/>' .
            '<w:lsdException w:name="caption" w:uiPriority="35" ' .
            'w:qFormat="1"/><w:lsdException w:name="Title" w:sem' .
            'iHidden="0" w:uiPriority="10" w:unhideWhenUsed="0" w:qFo' .
            'rmat="1"/><w:lsdException w:name="Default Paragraph Font" w:ui' .
            'Priority="1"/><w:lsdException w:name="Subtitle" w:semiHidd' .
            'en="0" w:uiPriority="11" w:unhideWhenUsed="0" w:qForm' .
            'at="1"/><w:lsdException w:name="Strong" w:semiHidden="0" w:uiP' .
            'riority="22" w:unhideWhenUsed="0" w:qFormat="1"/><w:lsdExc' .
            'eption w:name="Emphasis" w:semiHidden="0" w:uiPriority="20" w:u' .
            'nhideWhenUsed="0" w:qFormat="1"/><w:lsdException w:name="Ta' .
            'ble Grid" w:semiHidden="0" w:uiPriority="59" w:unhideWhenUs' .
            'ed="0"/><w:lsdException w:name="Placeholder Text" w:unhide' .
            'WhenUsed="0"/><w:lsdException w:name="No Spacing" w:semiHi' .
            'dden="0" w:uiPriority="1" w:unhideWhenUsed="0" w:qForm' .
            'at="1"/><w:lsdException w:name="Light Shading" w:semiHi' .
            'dden="0" w:uiPriority="60" w:unhideWhenUsed="0"/><w:lsd' .
            'Exception w:name="Light List" w:semiHidden="0" w:uiPrior' .
            'ity="61" w:unhideWhenUsed="0"/><w:lsdException w:name' .
            '="Light Grid" w:semiHidden="0" w:uiPriority="62" w:unhid' .
            'eWhenUsed="0"/><w:lsdException w:name="Medium Shading 1" w:semi' .
            'Hidden="0" w:uiPriority="63" w:unhideWhenUsed="0"/><w:lsdEx' .
            'ception w:name="Medium Shading 2" w:semiHidden="0" w:uiPri' .
            'ority="64" w:unhideWhenUsed="0"/><w:lsdException w:name="Me' .
            'dium List 1" w:semiHidden="0" w:uiPriority="65" w:unhideWhe' .
            'nUsed="0"/><w:lsdException w:name="Medium List 2" w:semiHid' .
            'den="0" w:uiPriority="66" w:unhideWhenUsed="0"/><w:lsdExcep' .
            'tion w:name="Medium Grid 1" w:semiHidden="0" w:uiPriorit' .
            'y="67" w:unhideWhenUsed="0"/><w:lsdException w:name="Mediu' .
            'm Grid 2" w:semiHidden="0" w:uiPriority="68" w:unhideWhe' .
            'nUsed="0"/><w:lsdException w:name="Medium Grid 3" w:semi' .
            'Hidden="0" w:uiPriority="69" w:unhideWhenUsed="0"/><w:lsd' .
            'Exception w:name="Dark List" w:semiHidden="0" w:uiPrio' .
            'rity="70" w:unhideWhenUsed="0"/><w:lsdException w:name="Colo' .
            'rful Shading" w:semiHidden="0" w:uiPriority="71" w:unh' .
            'ideWhenUsed="0"/><w:lsdException w:name="Colorful List" w:sem' .
            'iHidden="0" w:uiPriority="72" w:unhideWhenUsed="0"/><w:lsdEx' .
            'ception w:name="Colorful Grid" w:semiHidden="0" w:uiPrio' .
            'rity="73" w:unhideWhenUsed="0"/><w:lsdException w:name="Lig' .
            'ht Shading Accent 1" w:semiHidden="0" w:uiPriority="60" w:un' .
            'hideWhenUsed="0"/><w:lsdException w:name="Light List Acce' .
            'nt 1" w:semiHidden="0" w:uiPriority="61" w:unhideWhenUs' .
            'ed="0"/><w:lsdException w:name="Light Grid Accent 1" w:sem' .
            'iHidden="0" w:uiPriority="62" w:unhideWhenUsed="0"/>' .
            '<w:lsdException w:name="Medium Shading 1 Accent 1" w' .
            ':semiHidden="0" w:uiPriority="63" w:unhideWhenUsed="' .
            '0"/><w:lsdException w:name="Medium Shading 2 Accent' .
            ' 1" w:semiHidden="0" w:uiPriority="64" w:unhideWhen' .
            'Used="0"/><w:lsdException w:name="Medium List 1 Acc' .
            'ent 1" w:semiHidden="0" w:uiPriority="65" w:unhideW' .
            'henUsed="0"/><w:lsdException w:name="Revision" w:un' .
            'hideWhenUsed="0"/><w:lsdException w:name="List Para' .
            'graph" w:semiHidden="0" w:uiPriority="34" w:unhideW' .
            'henUsed="0" w:qFormat="1"/><w:lsdException w:name=' .
            '"Quote" w:semiHidden="0" w:uiPriority="29" w:unhi' .
            'deWhenUsed="0" w:qFormat="1"/><w:lsdException w:n' .
            'ame="Intense Quote" w:semiHidden="0" w:uiPriority' .
            '="30" w:unhideWhenUsed="0" w:qFormat="1"/><w:lsdE' .
            'xception w:name="Medium List 2 Accent 1" w:semiHi' .
            'dden="0" w:uiPriority="66" w:unhideWhenUsed="0"/' .
            '><w:lsdException w:name="Medium Grid 1 Accent 1"' .
            ' w:semiHidden="0" w:uiPriority="67" w:unhideWhenU' .
            'sed="0"/><w:lsdException w:name="Medium Grid 2 A' .
            'ccent 1" w:semiHidden="0" w:uiPriority="68" w:un' .
            'hideWhenUsed="0"/><w:lsdException w:name="Medium' .
            ' Grid 3 Accent 1" w:semiHidden="0" w:uiPriority=' .
            '"69" w:unhideWhenUsed="0"/><w:lsdException w:nam' .
            'e="Dark List Accent 1" w:semiHidden="0" w:uiPri' .
            'ority="70" w:unhideWhenUsed="0"/><w:lsdExceptio' .
            'n w:name="Colorful Shading Accent 1" w:semiHidd' .
            'en="0" w:uiPriority="71" w:unhideWhenUsed="0"/' .
            '><w:lsdException w:name="Colorful List Accent 1" w:' .
            'semiHidden="0" w:uiPriority="72" w:unhideWhenUsed="0"/><w:l' .
            'sdException w:name="Colorful Grid Accent 1" w:semiHidden="' .
            '0" w:uiPriority="73" w:unhideWhenUsed="0"/><w:lsdException' .
            ' w:name="Light Shading Accent 2" w:semiHidden="0" w:uiPrio' .
            'rity="60" w:unhideWhenUsed="0"/><w:lsdException w:name="Lig' .
            'ht List Accent 2" w:semiHidden="0" w:uiPriority="61" w:unhi' .
            'deWhenUsed="0"/><w:lsdException w:name="Light Grid Accen' .
            't 2" w:semiHidden="0" w:uiPriority="62" w:unhideWhenUsed="0"' .
            '/><w:lsdException w:name="Medium Shading 1 Accent 2" w:semiH' .
            'idden="0" w:uiPriority="63" w:unhideWhenUsed="0"/><w:lsdExce' .
            'ption w:name="Medium Shading 2 Accent 2" w:semiHidden="0" w:' .
            'uiPriority="64" w:unhideWhenUsed="0"/><w:lsdException w:name=' .
            '"Medium List 1 Accent 2" w:semiHidden="0" w:uiPriority="65"' .
            ' w:unhideWhenUsed="0"/><w:lsdException w:name="Medium List 2 ' .
            'Accent 2" w:semiHidden="0" w:uiPriority="66" w:unhideWhenUsed=' .
            '"0"/><w:lsdException w:name="Medium Grid 1 Accent 2" w:semiH' .
            'idden="0" w:uiPriority="67" w:unhideWhenUsed="0"/><w:lsdExcept' .
            'ion w:name="Medium Grid 2 Accent 2" w:semiHidden="0" w:uiPriori' .
            'ty="68" w:unhideWhenUsed="0"/><w:lsdException w:name="Medi' .
            'um Grid 3 Accent 2" w:semiHidden="0" w:uiPriority="69" w:unh' .
            'ideWhenUsed="0"/><w:lsdException w:name="Dark List Accent 2' .
            '" w:semiHidden="0" w:uiPriority="70" w:unhideWhenUsed="0"/' .
            '><w:lsdException w:name="Colorful Shading Accent 2" w:semi' .
            'Hidden="0" w:uiPriority="71" w:unhideWhenUsed="0"/><w:lsdE' .
            'xception w:name="Colorful List Accent 2" w:semiHidden="0" ' .
            'w:uiPriority="72" w:unhideWhenUsed="0"/><w:lsdException w:' .
            'name="Colorful Grid Accent 2" w:semiHidden="0" w:uiPriorit' .
            'y="73" w:unhideWhenUsed="0"/><w:lsdException w:name="Ligh' .
            't Shading Accent 3" w:semiHidden="0" w:uiPriority="60" w:' .
            'unhideWhenUsed="0"/><w:lsdException w:name="Light List Acc' .
            'ent 3" w:semiHidden="0" w:uiPriority="61" w:unhideWhenUsed' .
            '="0"/><w:lsdException w:name="Light Grid Accent 3" w:semiH' .
            'idden="0" w:uiPriority="62" w:unhideWhenUsed="0"/><w:lsdEx' .
            'ception w:name="Medium Shading 1 Accent 3" w:semiHidden="0' .
            '" w:uiPriority="63" w:unhideWhenUsed="0"/><w:lsdException ' .
            'w:name="Medium Shading 2 Accent 3" w:semiHidden="0" w:uiP' .
            'riority="64" w:unhideWhenUsed="0"/><w:lsdException w:name' .
            '="Medium List 1 Accent 3" w:semiHidden="0" w:uiPriority' .
            '="65" w:unhideWhenUsed="0"/><w:lsdException w:nam' .
            'e="Medium List 2 Accent 3" w:semiHidden="0" w:uiPr' .
            'iority="66" w:unhideWhenUsed="0"/><w:lsdException ' .
            'w:name="Medium Grid 1 Accent 3" w:semiHidden="0" w' .
            ':uiPriority="67" w:unhideWhenUsed="0"/><w:lsdExcep' .
            'tion w:name="Medium Grid 2 Accent 3" w:semiHidden="0" w:uiPr' .
            'iority="68" w:unhideWhenUsed="0"/><w:lsdException w:name=' .
            '"Medium Grid 3 Accent 3" w:semiHidden="0" w:uiPriority="' .
            '69" w:unhideWhenUsed="0"/><w:lsdException w:name="Dark L' .
            'ist Accent 3" w:semiHidden="0" w:uiPriority="70" w:unhid' .
            'eWhenUsed="0"/><w:lsdException w:name="Colorful Shading ' .
            'Accent 3" w:semiHidden="0" w:uiPriority="71" w:unhideWhenUse' .
            'd="0"/><w:lsdException w:name="Colorful List Accent 3" w:sem' .
            'iHidden="0" w:uiPriority="72" w:unhideWhenUsed="0"/><w:lsdEx' .
            'ception w:name="Colorful Grid Accent 3" w:semiHidden="0" w:ui' .
            'Priority="73" w:unhideWhenUsed="0"/><w:lsdException w:name="' .
            'Light Shading Accent 4" w:semiHidden="0" w:uiPriority="60" w:' .
            'unhideWhenUsed="0"/><w:lsdException w:name="Light List Accen' .
            't 4" w:semiHidden="0" w:uiPriority="61" w:unhideWhenUsed="0"' .
            '/><w:lsdException w:name="Light Grid Accent 4" w:semiHidden=' .
            '"0" w:uiPriority="62" w:unhideWhenUsed="0"/><w:lsdException ' .
            'w:name="Medium Shading 1 Accent 4" w:semiHidden="0" w:uiPrio' .
            'rity="63" w:unhideWhenUsed="0"/><w:lsdException w:name="Mediu' .
            'm Shading 2 Accent 4" w:semiHidden="0" w:uiPriority="64" w:' .
            'unhideWhenUsed="0"/><w:lsdException w:name="Medium List 1 A' .
            'ccent 4" w:semiHidden="0" w:uiPriority="65" w:unhideWhenUse' .
            'd="0"/><w:lsdException w:name="Medium List 2 Accent 4" w:se' .
            'miHidden="0" w:uiPriority="66" w:unhideWhenUsed="0"/><w:ls' .
            'dException w:name="Medium Grid 1 Accent 4" w:semiHidden="0" ' .
            'w:uiPriority="67" w:unhideWhenUsed="0"/><w:lsdException w:nam' .
            'e="Medium Grid 2 Accent 4" w:semiHidden="0" w:uiPriority="68"' .
            ' w:unhideWhenUsed="0"/><w:lsdException w:name="Medium Grid 3 A' .
            'ccent 4" w:semiHidden="0" w:uiPriority="69" w:unhideWhenUsed="' .
            '0"/><w:lsdException w:name="Dark List Accent 4" w:semiHidden="' .
            '0" w:uiPriority="70" w:unhideWhenUsed="0"/><w:lsdException w:n' .
            'ame="Colorful Shading Accent 4" w:semiHidden="0" w:uiPriority=' .
            '"71" w:unhideWhenUsed="0"/><w:lsdException w:name="Colorful ' .
            'List Accent 4" w:semiHidden="0" w:uiPriority="72" w:unhideWhe' .
            'nUsed="0"/><w:lsdException w:name="Colorful Grid Accent 4" w' .
            ':semiHidden="0" w:uiPriority="73" w:unhideWhenUsed="0"/><w:ls' .
            'dException w:name="Light Shading Accent 5" w:semiHidden="0" w:' .
            'uiPriority="60" w:unhideWhenUsed="0"/><w:lsdException w:name="' .
            'Light List Accent 5" w:semiHidden="0" w:uiPriority="61" w:unh' .
            'ideWhenUsed="0"/><w:lsdException w:name="Light Grid Accent 5"' .
            ' w:semiHidden="0" w:uiPriority="62" w:unhideWhenUsed="0"/><w' .
            ':lsdException w:name="Medium Shading 1 Accent 5" w:semiHidden' .
            '="0" w:uiPriority="63" w:unhideWhenUsed="0"/><w:lsdException' .
            ' w:name="Medium Shading 2 Accent 5" w:semiHidden="0" w:uiPrio' .
            'rity="64" w:unhideWhenUsed="0"/><w:lsdException w:name="Medium' .
            ' List 1 Accent 5" w:semiHidden="0" w:uiPriority="65" w:unhideWh' .
            'enUsed="0"/><w:lsdException w:name="Medium List 2 Accent 5" w:' .
            'semiHidden="0" w:uiPriority="66" w:unhideWhenUsed="0"/><w:lsdE' .
            'xception w:name="Medium Grid 1 Accent 5" w:semiHidden="0" w:ui' .
            'Priority="67" w:unhideWhenUsed="0"/><w:lsdException w:name="M' .
            'edium Grid 2 Accent 5" w:semiHidden="0" w:uiPriority="68" w:u' .
            'nhideWhenUsed="0"/><w:lsdException w:name="Medium Grid 3 Acce' .
            'nt 5" w:semiHidden="0" w:uiPriority="69" w:unhideWhenUsed="0"' .
            '/><w:lsdException w:name="Dark List Accent 5" w:semiHidden="' .
            '0" w:uiPriority="70" w:unhideWhenUsed="0"/><w:lsdException w' .
            ':name="Colorful Shading Accent 5" w:semiHidden="0" w:uiPriori' .
            'ty="71" w:unhideWhenUsed="0"/><w:lsdException w:name="Colorf' .
            'ul List Accent 5" w:semiHidden="0" w:uiPriority="72" w:unhid' .
            'eWhenUsed="0"/><w:lsdException w:name="Colorful Grid Accent 5' .
            '" w:semiHidden="0" w:uiPriority="73" w:unhideWhenUsed="0"/><w' .
            ':lsdException w:name="Light Shading Accent 6" w:semiHidden="0' .
            '" w:uiPriority="60" w:unhideWhenUsed="0"/><w:lsdException w:' .
            'name="Light List Accent 6" w:semiHidden="0" w:uiPriority="61' .
            '" w:unhideWhenUsed="0"/><w:lsdException w:name="Light Grid A' .
            'ccent 6" w:semiHidden="0" w:uiPriority="62" w:unhideWhenUsed' .
            '="0"/><w:lsdException w:name="Medium Shading 1 Accent 6" w:s' .
            'emiHidden="0" w:uiPriority="63" w:unhideWhenUsed="0"/><w:lsd' .
            'Exception w:name="Medium Shading 2 Accent 6" w:semiHidden="' .
            '0" w:uiPriority="64" w:unhideWhenUsed="0"/><w:lsdException ' .
            'w:name="Medium List 1 Accent 6" w:semiHidden="0" w:uiPriority="' .
            '65" w:unhideWhenUsed="0"/><w:lsdException w:name="Medium List ' .
            '2 Accent 6" w:semiHidden="0" w:uiPriority="66" w:unhideWhenUs' .
            'ed="0"/><w:lsdException w:name="Medium Grid 1 Accent 6" w:sem' .
            'iHidden="0" w:uiPriority="67" w:unhideWhenUsed="0"/><w:lsdExc' .
            'eption w:name="Medium Grid 2 Accent 6" w:semiHidden="0" w:uiP' .
            'riority="68" w:unhideWhenUsed="0"/><w:lsdException w:name="M' .
            'edium Grid 3 Accent 6" w:semiHidden="0" w:uiPriority="69" w:' .
            'unhideWhenUsed="0"/><w:lsdException w:name="Dark List Accent' .
            ' 6" w:semiHidden="0" w:uiPriority="70" w:unhideWhenUsed="0"' .
            '/><w:lsdException w:name="Colorful Shading Accent 6" w:semi' .
            'Hidden="0" w:uiPriority="71" w:unhideWhenUsed="0"/><w:lsdEx' .
            'ception w:name="Colorful List Accent 6" w:semiHidden="0" w:ui' .
            'Priority="72" w:unhideWhenUsed="0"/><w:lsdException w:name="C' .
            'olorful Grid Accent 6" w:semiHidden="0" w:uiPriority="73" w:un' .
            'hideWhenUsed="0"/><w:lsdException w:name="Subtle Emphasis" w:' .
            'semiHidden="0" w:uiPriority="19" w:unhideWhenUsed="0" w:qFormat' .
            '="1"/><w:lsdException w:name="Intense Emphasis" w:semiHidden="' .
            '0" w:uiPriority="21" w:unhideWhenUsed="0" w:qFormat="1"/><w:l' .
            'sdException w:name="Subtle Reference" w:semiHidden="0" w:uiPr' .
            'iority="31" w:unhideWhenUsed="0" w:qFormat="1"/><w:lsdExcept' .
            'ion w:name="Intense Reference" w:semiHidden="0" w:uiPrior' .
            'ity="32" w:unhideWhenUsed="0" w:qFormat="1"/><w:lsdException' .
            ' w:name="Book Title" w:semiHidden="0" w:uiPriority="33" w:un' .
            'hideWhenUsed="0" w:qFormat="1"/><w:lsdException w:name="Bib' .
            'liography" w:uiPriority="37"/><w:lsdException w:name="TOC Hea' .
            'ding" w:uiPriority="39" w:qFormat="1"/></w:latentStyles>';

        $parameters = array('type' => 'paragraph',
            'default' => '1', 'styleId' => 'Normal',
            'name' => 'Normal',
            'qFormat' => 1, 'rsid' => '000415ED');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Ttulo1',
            'name' => 'heading 1',
            'basedOn' => 'Normal',
            'next' => 'Normal',
            'link' => 'Ttulo1Car',
            'uiPriority' => '9', 'qFormat' => 1,
            'rsid' => '00CF3BFC',
            'keepNext' => 1, 'keepLines' => 1, 'spacing_before' => '480',
            'spacing_after' => '0', 'outlineLvl' => '0',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'b' => 1, 'bCs' => 1, 'color_val' => '365F91',
            'color_themeColor' => 'accent1',
            'color_themeShade' => 'BF',
            'sz' => 28, 'szCs' => 28);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Ttulo2',
            'name' => 'heading 2',
            'basedOn' => 'Normal',
            'next' => 'Normal',
            'link' => 'Ttulo2Car',
            'uiPriority' => '9', 'unhideWhenUsed' => 1, 'qFormat' => 1,
            'rsid' => '005235DC',
            'keepNext' => 1, 'keepLines' => 1, 'spacing_before' => '200',
            'spacing_after' => '0', 'outlineLvl' => '1',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'b' => 1, 'bCs' => 1, 'color_val' => '200290',
            'sz' => 32, 'szCs' => 26);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Ttulo3',
            'name' => 'heading 3',
            'basedOn' => 'Normal',
            'next' => 'Normal',
            'link' => 'Ttulo3Car',
            'uiPriority' => '9', 'unhideWhenUsed' => 1, 'qFormat' => 1,
            'rsid' => '005235DC',
            'keepNext' => 1, 'keepLines' => 1, 'spacing_before' => '200',
            'spacing_after' => '0', 'outlineLvl' => '2',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'b' => 1, 'bCs' => 1, 'color_val' => '000000',
            'color_themeColor' => 'text1',
            'sz' => 28);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Ttulo4',
            'name' => 'heading 4',
            'basedOn' => 'Normal',
            'next' => 'Normal',
            'link' => 'Ttulo4Car',
            'uiPriority' => '9', 'unhideWhenUsed' => 1, 'qFormat' => 1,
            'rsid' => '007D6ADC',
            'keepNext' => 1, 'keepLines' => 1, 'spacing_before' => '200',
            'spacing_after' => '0', 'outlineLvl' => '3',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'b' => 1, 'bCs' => 1, 'i' => 1, 'iCs' => 1, 'color_val' => '4F81BD',
            'color_themeColor' => 'accent1');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'character',
            'default' => '1', 'styleId' => 'Fuentedeprrafopredeter',
            'name' => 'Default Paragraph Font',
            'uiPriority' => '1', 'unhideWhenUsed' => 1,
            'semiHidden' => 1);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'table',
            'default' => '1', 'styleId' => 'Tablanormal',
            'name' => 'Normal Table',
            'uiPriority' => '99',
            'semiHidden' => 1, 'unhideWhenUsed' => 1, 'qFormat' => 1,
            'tblInd_w' => '0', 'tblInd_type' => 'dxa',
            'top_w' => '0', 'top_type' => 'dxa',
            'left_w' => '108',
            'left_type' => 'dxa',
            'bottom_w' => '0', 'bottom_type' => 'dxa',
            'right_w' => '108',
            'right_type' => 'dxa');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'numbering',
            'default' => '1', 'styleId' => 'Sinlista',
            'name' => 'No List',
            'uiPriority' => '99',
            'semiHidden' => 1, 'unhideWhenUsed' => 1);
        $this->addStyle(array($parameters));
        // falla x el acento!!!
        $parameters = array('type' => 'character',
            'customStyle' => '1', 'styleId' => 'Ttulo1Car',
            'name' => 'Titulo 1 Car',
            'basedOn' => 'Fuentedeprrafopredeter',
            'link' => 'Ttulo1',
            'uiPriority' => '9',
            'rsid' => '005235DC',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'b' => 1, 'bCs' => 1,
            'color_val' => '365F91',
            'color_themeColor' => 'accent1',
            'color_themeShade' => 'BF',
            'sz' => '28',
            'szCs' => '28');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'character',
            'customStyle' => '1', 'styleId' => 'Ttulo2Car',
            'name' => 'Titulo 2 Car',
            'basedOn' => 'Fuentedeprrafopredeter',
            'link' => 'Ttulo2',
            'uiPriority' => '9',
            'rsid' => '00CF3BFC',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'b' => 1, 'bCs' => 1,
            'color_val' => '200290',
            'sz' => 32, 'szCs' => 26);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'character',
            'customStyle' => '1', 'styleId' => 'Ttulo3Car',
            'name' => 'Titulo 3 Car',
            'basedOn' => 'Fuentedeprrafopredeter',
            'link' => 'Ttulo3',
            'uiPriority' => '9',
            'rsid' => '005235DC',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'b' => 1, 'bCs' => 1,
            'color_val' => '000000',
            'color_themeColor' => 'text1',
            'sz' => 28);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Ttulo',
            'name' => 'Title',
            'basedOn' => 'Normal',
            'next' => 'Normal',
            'link' => 'TtuloCar',
            'uiPriority' => '10',
            'qFormat' => 1,
            'rsid' => '00CF3BFC',
            'pBdr_bottom_val' => 'single',
            'pBdr_bottom_sz' => 8, 'pBdr_bottom_space' => 4,
            'pBdr_bottom_color' => '4F81BD',
            'pBdr_bottom_themeColor' => 'accent1',
            'spacing_after' => '300',
            'spacing_line' => '240',
            'spacing_lineRule' => 'auto',
            'contextualSpacing' => 1, 'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'color_val' => '17365D',
            'color_themeColor' => 'text2',
            'color_themeShade' => 'BF',
            'rPr_spacing' => '5', 'kern' => '28',
            'sz' => 52, 'szCs' => 52);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'character',
            'customStyle' => '1', 'styleId' => 'TtuloCar',
            'name' => 'Titulo Car',
            'basedOn' => 'Fuentedeprrafopredeter',
            'link' => 'Ttulo',
            'uiPriority' => '10',
            'rsid' => '00CF3BFC',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'color_val' => '17365D',
            'color_themeColor' => 'text2',
            'color_themeShade' => 'BF',
            'rPr_spacing' => '5', 'kern' => '28',
            'sz' => 52, 'szCs' => 52);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Subttulo',
            'name' => 'Subtitle',
            'basedOn' => 'Normal',
            'next' => 'Normal',
            'link' => 'SubttuloCar',
            'uiPriority' => '11',
            'qFormat' => 1,
            'rsid' => '00CF3BFC',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'ilvl' => '1', 'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'i' => 1, 'iCs' => 1, 'color_val' => '4F81BD',
            'color_themeColor' => 'accent1',
            'rPr_spacing' => '15',
            'sz' => 24, 'szCs' => 24);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Sinespaciado',
            'name' => 'No Spacing',
            'uiPriority' => '1', 'qFormat' => 1, 'rsid' => '007D6ADC',
            'spacing_after' => '0',
            'spacing_line' => '240',
            'spacing_lineRule' => 'auto');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'character',
            'customStyle' => '1', 'styleId' => 'Ttulo4Car',
            'name' => 'Titulo 4 Car',
            'basedOn' => 'Fuentedeprrafopredeter',
            'link' => 'Ttulo4',
            'uiPriority' => '9',
            'rsid' => '007D6ADC',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'b' => 1, 'bCs' => 1,
            'i' => 1, 'iCs' => 1, 'color_val' => '4F81BD',
            'color_themeColor' => 'accent1');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'character',
            'styleId' => 'Hipervnculo',
            'name' => 'Hyperlink',
            'basedOn' => 'Fuentedeprrafopredeter',
            'uiPriority' => '99',
            'unhideWhenUsed' => 1,
            'rsid' => '00EF1859',
            'color_val' => '0000FF',
            'color_themeColor' => 'hyperlink',
            'u' => 'single');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Prrafodelista',
            'name' => 'List Paragraph',
            'basedOn' => 'Normal',
            'uiPriority' => '34',
            'qFormat' => 1, 'rsid' => '007D6ADC',
            'ind_left' => 720, 'contextualSpacing' => 1);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'table',
            'styleId' => 'Tablaconcuadrcula',
            'name' => 'Table Grid',
            'basedOn' => 'Tablanormal',
            'uiPriority' => '59',
            'rsid' => '007D6ADC',
            'spacing_after' => '0',
            'spacing_line' => '240',
            'spacing_lineRule' => 'auto',
            'tblInd_w' => '0', 'tblInd_type' => 'dxa',
            'top_w' => '0', 'top_type' => 'dxa',
            'left_w' => '108',
            'left_type' => 'dxa',
            'bottom_w' => '0', 'bottom_type' => 'dxa',
            'right_w' => '108',
            'tblborder_top_val' => 'single',
            'tblborder_top_sz' => 4,
            'tblborder_top_space' => '0',
            'tblborder_top_color' => '000000',
            'tblborder_top_themeColor' => 'text1',
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 4, 'tblborder_left_space' => '0',
            'tblborder_left_color' => '000000',
            'tblborder_left_themeColor' => 'text1',
            'tblborder_bottom_val' => 'single',
            'tblborder_bottom_sz' => 4, 'tblborder_bottom_space' => '0',
            'tblborder_bottom_color' => '000000',
            'tblborder_bottom_themeColor' => 'text1',
            'tblborder_right_val' => 'single',
            'tblborder_right_sz' => 4, 'tblborder_right_space' => '0',
            'tblborder_right_color' => '000000',
            'tblborder_right_themeColor' => 'text1',
            'tblborder_insideH_val' => 'single',
            'tblborder_insideH_sz' => 4, 'tblborder_insideH_space' => '0',
            'tblborder_insideH_color' => '000000',
            'tblborder_insideH_themeColor' => 'text1',
            'tblborder_insideV_val' => 'single',
            'tblborder_insideV_sz' => 4, 'tblborder_insideV_space' => '0',
            'tblborder_insideV_color' => '000000',
            'tblborder_insideV_themeColor' => 'text1');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Textodeglobo',
            'name' => 'Balloon Text',
            'basedOn' => 'Normal',
            'link' => 'TextodegloboCar',
            'uiPriority' => '99',
            'semiHidden' => 1,
            'unhideWhenUsed' => 1, 'rsid' => '007D6ADC',
            'spacing_after' => '0', 'spacing_line' => '240',
            'spacing_lineRule' => 'auto',
            'sz' => 16, 'szCs' => 16, 'rFonts_ascii' => 'Tahoma',
            'rFonts_hAnsi' => 'Tahoma',
            'rFonts_cs' => 'Tahoma');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'character',
            'customStyle' => '1', 'styleId' => 'TextodegloboCar',
            'name' => 'Texto de globo Car',
            'basedOn' => 'Fuentedeprrafopredeter',
            'link' => 'Textodeglobo',
            'uiPriority' => '99',
            'semiHidden' => 1, 'rsid' => '007D6ADC',
            'sz' => 16, 'szCs' => 16, 'rFonts_ascii' => 'Tahoma',
            'rFonts_hAnsi' => 'Tahoma',
            'rFonts_cs' => 'Tahoma');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Encabezado',
            'name' => 'header',
            'basedOn' => 'Normal',
            'link' => 'EncabezadoCar',
            'uiPriority' => '99',
            'unhideWhenUsed' => 1,
            'rsid' => '007D6ADC',
            'spacing_after' => '0', 'spacing_line' => '240',
            'spacing_lineRule' => 'auto',
            'tab_center' => 4252, 'tab_right' => 8504);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'character',
            'customStyle' => '1', 'styleId' => 'EncabezadoCar',
            'name' => 'Encabezado Car',
            'basedOn' => 'Fuentedeprrafopredeter',
            'link' => 'Encabezado',
            'uiPriority' => '99',
            'rsid' => '007D6ADC');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Piedepgina',
            'name' => 'footer',
            'basedOn' => 'Normal',
            'link' => 'PiedepginaCar',
            'uiPriority' => '99',
            'unhideWhenUsed' => 1,
            'rsid' => '007D6ADC',
            'spacing_after' => '0', 'spacing_line' => '240',
            'spacing_lineRule' => 'auto',
            'tab_center' => 4252, 'tab_right' => 8504);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'character',
            'customStyle' => '1', 'styleId' => 'PiedepginaCar',
            'name' => 'Pie de pagina Car',
            'basedOn' => 'Fuentedeprrafopredeter',
            'link' => 'Piedepgina',
            'uiPriority' => '99',
            'rsid' => '007D6ADC');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'TDC1',
            'name' => 'toc 1',
            'basedOn' => 'Normal',
            'next' => 'Normal',
            'autoRedefine' => 1, 'uiPriority' => '39',
            'unhideWhenUsed' => 1,
            'rsid' => '00EF1859',
            'spacing_after' => '100');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'numbering',
            'customStyle' => '1', 'styleId' => 'Sinlista',
            'name' => 'No List',
            'uiPriority' => '99',
            'semiHidden' => 1, 'unhideWhenUsed' => 1);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'TDC2',
            'name' => 'toc 2',
            'basedOn' => 'Normal',
            'next' => 'Normal',
            'autoRedefine' => 1, 'uiPriority' => '39',
            'semiHidden' => 1,
            'unhideWhenUsed' => 1, 'rsid' => '005F706C',
            'spacing_after' => '100',
            'ind_left' => 220, 'b' => 1, 'sz' => 24);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'TDC3',
            'name' => 'toc 3',
            'basedOn' => 'Normal',
            'next' => 'Normal',
            'autoRedefine' => 1, 'uiPriority' => '39',
            'unhideWhenUsed' => 1, 'rsid' => '00EF1859',
            'spacing_after' => '100',
            'ind_left' => 440);
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'table',
            'styleId' => 'Cuadrculamedia3-nfasis1',
            'name' => 'Medium Grid 3 Accent 1',
            'basedOn' => 'Tablanormal',
            'uiPriority' => '69',
            'rsid' => '00387AE9',
            'spacing_after' => '0', 'spacing_line' => '240',
            'spacing_lineRule' => 'auto',
            'tblInd_w' => '0', 'tblInd_type' => 'dxa',
            'top_w' => '0', 'top_type' => 'dxa',
            'left_w' => '108',
            'left_type' => 'dxa',
            'bottom_w' => '0', 'bottom_type' => 'dxa',
            'right_w' => '108',
            'right_type' => 'dxa',
            'tbl_style_row' => 1, 'tbl_style_col' => 1,
            'tblborder_top_val' => 'single',
            'tblborder_top_sz' => 8, 'tblborder_top_space' => '0',
            'tblborder_top_color' => 'FFFFFF',
            'tblborder_top_themeColor' => 'background1',
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 8, 'tblborder_left_space' => '0',
            'tblborder_left_color' => 'FFFFFF',
            'tblborder_left_themeColor' => 'background1',
            'tblborder_bottom_val' => 'single',
            'tblborder_bottom_sz' => 8, 'tblborder_bottom_space' => '0',
            'tblborder_bottom_color' => 'FFFFFF',
            'tblborder_bottom_themeColor' => 'background1',
            'tblborder_right_val' => 'single',
            'tblborder_right_sz' => 8, 'tblborder_right_space' => '0',
            'tblborder_right_color' => 'FFFFFF',
            'tblborder_right_themeColor' => 'background1',
            'tblborder_insideH_val' => 'single',
            'tblborder_insideH_sz' => 6, 'tblborder_insideH_space' => '0',
            'tblborder_insideH_color' => 'FFFFFF',
            'tblborder_insideH_themeColor' => 'background1',
            'tblborder_insideV_val' => 'single',
            'tblborder_insideV_sz' => 6, 'tblborder_insideV_space' => '0',
            'tblborder_insideV_color' => 'FFFFFF',
            'tblborder_insideV_themeColor' => 'background1',
            'shd_val' => 'clear',
            'shd_color' => 'auto',
            'shd_fill' => 'D3DFEE',
            'shd_themeFill' => 'accent1',
            'shd_themeFillTint' => '3F');

        $styleTableA = array('type' => 'firstRow',
            'b' => 1, 'b' => 1, 'bCs' => 1, 'i' => '0', 'iCs' => '0',
            'color_val' => 'FFFFFF',
            'color_themeColor' => 'background1',
            'tblPr' => 1,
            'tblborder_top_val' => 'single',
            'tblborder_top_sz' => 8, 'tblborder_top_space' => '0',
            'tblborder_top_color' => 'FFFFFF',
            'tblborder_top_themeColor' => 'background1',
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 8, 'tblborder_left_space' => '0',
            'tblborder_left_color' => 'FFFFFF',
            'tblborder_left_themeColor' => 'background1',
            'tblborder_bottom_val' => 'single',
            'tblborder_bottom_sz' => 24, 'tblborder_bottom_space' => '0',
            'tblborder_bottom_color' => 'FFFFFF',
            'tblborder_bottom_themeColor' => 'background1',
            'tblborder_right_val' => 'single',
            'tblborder_right_sz' => 8, 'tblborder_right_space' => '0',
            'tblborder_right_color' => 'FFFFFF',
            'tblborder_right_themeColor' => 'background1',
            'tblborder_insideH_val' => 'nil',
            'tblborder_insideV_val' => 'single',
            'tblborder_insideV_sz' => 8, 'tblborder_insideV_space' => '0',
            'tblborder_insideV_color' => 'FFFFFF',
            'tblborder_insideV_themeColor' => 'background1',
            'shd_val' => 'clear',
            'shd_color' => 'auto',
            'shd_fill' => '4F81BD',
            'shd_themeFill' => 'accent1');

        $styleTableB = array('type' => 'lastRow',
            'b' => 1, 'bCs' => 1, 'i' => '0', 'iCs' => '0',
            'color_val' => 'FFFFFF',
            'color_themeColor' => 'background1',
            'tblPr' => 1,
            'tblborder_top_val' => 'single',
            'tblborder_top_sz' => 24, 'tblborder_top_space' => '0',
            'tblborder_top_color' => 'FFFFFF',
            'tblborder_top_themeColor' => 'background1',
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 8, 'tblborder_left_space' => '0',
            'tblborder_left_color' => 'FFFFFF',
            'tblborder_left_themeColor' => 'background1',
            'tblborder_bottom_val' => 'single',
            'tblborder_bottom_sz' => 8, 'tblborder_bottom_space' => '0',
            'tblborder_bottom_color' => 'FFFFFF',
            'tblborder_bottom_themeColor' => 'background1',
            'tblborder_right_val' => 'single',
            'tblborder_right_sz' => 8, 'tblborder_right_space' => '0',
            'tblborder_right_color' => 'FFFFFF',
            'tblborder_right_themeColor' => 'background1',
            'tblborder_insideH_val' => 'nil',
            'tblborder_insideV_val' => 'single',
            'tblborder_insideV_sz' => 8, 'tblborder_insideV_space' => '0',
            'tblborder_insideV_color' => 'FFFFFF',
            'tblborder_insideV_themeColor' => 'background1',
            'shd_val' => 'clear',
            'shd_color' => 'auto',
            'shd_fill' => '4F81BD',
            'shd_themeFill' => 'accent1');

        $styleTableC = array('type' => 'firstCol',
            'b' => 1, 'bCs' => 1, 'i' => '0', 'iCs' => '0',
            'color_val' => 'FFFFFF',
            'color_themeColor' => 'background1',
            'tblPr' => 1,
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 8, 'tblborder_left_space' => '0',
            'tblborder_left_color' => 'FFFFFF',
            'tblborder_left_themeColor' => 'background1',
            'tblborder_right_val' => 'single',
            'tblborder_right_sz' => 24, 'tblborder_right_space' => '0',
            'tblborder_right_color' => 'FFFFFF',
            'tblborder_right_themeColor' => 'background1',
            'tblborder_insideH_val' => 'nil',
            'tblborder_insideV_val' => 'nil',
            'shd_val' => 'clear',
            'shd_color' => 'auto',
            'shd_fill' => '4F81BD',
            'shd_themeFill' => 'accent1');

        $styleTableD = array('type' => 'lastCol',
            'b' => 1, 'bCs' => 1, 'i' => '0', 'iCs' => '0',
            'color_val' => 'FFFFFF',
            'color_themeColor' => 'background1',
            'tblPr' => 1,
            'tblborder_top_val' => 'nil',
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 24, 'tblborder_left_space' => '0',
            'tblborder_left_color' => 'FFFFFF',
            'tblborder_left_themeColor' => 'background1',
            'tblborder_bottom_val' => 'nil',
            'tblborder_right_val' => 'nil',
            'tblborder_insideH_val' => 'nil',
            'tblborder_insideV_val' => 'nil',
            'shd_val' => 'clear',
            'shd_color' => 'auto',
            'shd_fill' => '4F81BD',
            'shd_themeFill' => 'accent1');

        $styleTableE = array('type' => 'band1Vert',
            'tblPr' => 1,
            'tblborder_top_val' => 'single',
            'tblborder_top_sz' => 8, 'tblborder_top_space' => '0',
            'tblborder_top_color' => 'FFFFFF',
            'tblborder_top_themeColor' => 'background1',
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 8, 'tblborder_left_space' => '0',
            'tblborder_left_color' => 'FFFFFF',
            'tblborder_left_themeColor' => 'background1',
            'tblborder_bottom_val' => 'single',
            'tblborder_bottom_sz' => 8, 'tblborder_bottom_space' => '0',
            'tblborder_bottom_color' => 'FFFFFF',
            'tblborder_bottom_themeColor' => 'background1',
            'tblborder_right_val' => 'single',
            'tblborder_right_sz' => 8, 'tblborder_right_space' => '0',
            'tblborder_right_color' => 'FFFFFF',
            'tblborder_right_themeColor' => 'background1',
            'tblborder_insideH_val' => 'nil',
            'tblborder_insideV_val' => 'nil',
            'shd_val' => 'clear',
            'shd_color' => 'auto',
            'shd_fill' => 'A7BFDE',
            'shd_themeFill' => 'accent1',
            'shd_themeFillTint' => '7F');

        $styleTableF = array('type' => 'band1Horz',
            'tblPr' => 1,
            'tblborder_top_val' => 'single',
            'tblborder_top_sz' => 8, 'tblborder_top_space' => '0',
            'tblborder_top_color' => 'FFFFFF',
            'tblborder_top_themeColor' => 'background1',
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 8, 'tblborder_left_space' => '0',
            'tblborder_left_color' => 'FFFFFF',
            'tblborder_left_themeColor' => 'background1',
            'tblborder_bottom_val' => 'single',
            'tblborder_bottom_sz' => 8, 'tblborder_bottom_space' => '0',
            'tblborder_bottom_color' => 'FFFFFF',
            'tblborder_bottom_themeColor' => 'background1',
            'tblborder_right_val' => 'single',
            'tblborder_right_sz' => 8, 'tblborder_right_space' => '0',
            'tblborder_right_color' => 'FFFFFF',
            'tblborder_right_themeColor' => 'background1',
            'tblborder_insideH_val' => 'single',
            'tblborder_insideH_sz' => 8, 'tblborder_insideH_space' => '0',
            'tblborder_insideH_color' => 'FFFFFF',
            'tblborder_insideH_themeColor' => 'background1',
            'tblborder_insideV_val' => 'single',
            'tblborder_insideV_sz' => 8, 'tblborder_insideV_space' => '0',
            'tblborder_insideV_color' => 'FFFFFF',
            'tblborder_insideV_themeColor' => 'background1',
            'shd_val' => 'clear',
            'shd_color' => 'auto',
            'shd_fill' => 'A7BFDE',
            'shd_themeFill' => 'accent1',
            'shd_themeFillTint' => '7F');

        $this->addStyle(
            array($parameters,
                $styleTableA, $styleTableB,
                $styleTableC, $styleTableD,
                $styleTableE, $styleTableF)
        );
        $parameters = array('type' => 'character',
            'styleId' => 'nfasis',
            'name' => 'Emphasis',
            'basedOn' => 'Fuentedeprrafopredeter',
            'uiPriority' => '20',
            'qFormat' => 1, 'rsid' => '005235DC',
            'i' => 1, 'iCs' => 1);
        $this->addStyle(array($parameters));

        $parameters = array('type' => 'table',
            'styleId' => 'Sombreadomedio1',
            'name' => 'Medium Shading 1',
            'basedOn' => 'Tablanormal',
            'uiPriority' => '63',
            'rsid' => '00E559CE',
            'spacing_after' => '0', 'spacing_line' => '240',
            'spacing_lineRule' => 'auto',
            'tblInd_w' => '0', 'tblInd_type' => 'dxa',
            'top_w' => '0', 'top_type' => 'dxa',
            'left_w' => '108',
            'left_type' => 'dxa',
            'bottom_w' => '0', 'bottom_type' => 'dxa',
            'right_w' => '108',
            'right_type' => 'dxa',
            'tbl_style_row' => 1, 'tbl_style_col' => 1,
            'tblborder_top_val' => 'single',
            'tblborder_top_sz' => 8, 'tblborder_top_space' => '0',
            'tblborder_top_color' => '404040',
            'tblborder_top_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 8, 'tblborder_left_space' => '0',
            'tblborder_left_color' => '404040',
            'tblborder_left_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_bottom_val' => 'single',
            'tblborder_bottom_sz' => 8, 'tblborder_bottom_space' => '0',
            'tblborder_bottom_color' => '404040',
            'tblborder_bottom_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_right_val' => 'single',
            'tblborder_right_sz' => 8, 'tblborder_right_space' => '0',
            'tblborder_right_color' => '404040',
            'tblborder_right_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_insideH_val' => 'single',
            'tblborder_insideH_sz' => 8, 'tblborder_insideH_space' => '0',
            'tblborder_insideH_color' => '404040',
            'tblborder_insideH_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF');

        $styleTableA = array('type' => 'firstRow',
            'spacing_before' => '0', 'spacing_after' => '0',
            'spacing_line' => '240',
            'spacing_lineRule' => 'auto',
            'b' => 1, 'b' => 1, 'bCs' => 1, 'color_val' => 'FFFFFF',
            'color_themeColor' => 'background1',
            'tblPr' => 1,
            'tblborder_top_val' => 'single',
            'tblborder_top_sz' => 8, 'tblborder_top_space' => '0',
            'tblborder_top_color' => '404040',
            'tblborder_top_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 8, 'tblborder_left_space' => '0',
            'tblborder_left_color' => '404040',
            'tblborder_left_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_bottom_val' => 'single',
            'tblborder_bottom_sz' => 8, 'tblborder_bottom_space' => '0',
            'tblborder_bottom_color' => '404040',
            'tblborder_bottom_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_right_val' => 'single',
            'tblborder_right_sz' => 8, 'tblborder_right_space' => '0',
            'tblborder_right_color' => '404040',
            'tblborder_right_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_insideH_val' => 'nil',
            'tblborder_insideV_val' => 'nil',
            'shd_val' => 'clear',
            'shd_color' => 'auto',
            'shd_fill' => '000000',
            'shd_themeFill' => 'text1');

        $styleTableB = array('type' => 'firstRow',
            'spacing_before' => '0', 'spacing_after' => '0',
            'spacing_line' => '240',
            'spacing_lineRule' => 'auto',
            'b' => 1, 'b' => 1, 'bCs' => 1, 'tblPr' => 1,
            'tblborder_top_val' => 'double',
            'tblborder_top_sz' => 6, 'tblborder_top_space' => '0',
            'tblborder_top_color' => '404040',
            'tblborder_top_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_left_val' => 'single',
            'tblborder_left_sz' => 8, 'tblborder_left_space' => '0',
            'tblborder_left_color' => '404040',
            'tblborder_left_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_bottom_val' => 'single',
            'tblborder_bottom_sz' => 8, 'tblborder_bottom_space' => '0',
            'tblborder_bottom_color' => '404040',
            'tblborder_bottom_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_right_val' => 'single',
            'tblborder_right_sz' => 8, 'tblborder_right_space' => '0',
            'tblborder_right_color' => '404040',
            'tblborder_right_themeColor' => 'text1',
            'tblborder_insideV_themeTint' => 'BF',
            'tblborder_insideH_val' => 'nil',
            'tblborder_insideV_val' => 'nil');

        $styleTableC = array('type' => 'firstCol',
            'b' => 1, 'bCs' => 1);
        $styleTableD = array('type' => 'lastCol',
            'b' => 1, 'bCs' => 1);
        $styleTableE = array('type' => 'band1Vert',
            'tblPr' => 1, 'shd_val' => 'clear',
            'shd_color' => 'auto',
            'shd_fill' => 'C0C0C0',
            'shd_themeFill' => 'text1',
            'shd_themeFillTint' => '3F');
        $styleTableF = array('type' => 'band1Horz',
            'tblPr' => 1, 'tblborder_insideH_val' => 'nil',
            'tblborder_insideV_val' => 'nil',
            'shd_val' => 'clear',
            'shd_color' => 'auto',
            'shd_fill' => 'C0C0C0',
            'shd_themeFill' => 'text1',
            'shd_themeFillTint' => '3F');
        $styleTableG = array('type' => 'band1Horz',
            'tblPr' => 1, 'tblborder_insideH_val' => 'nil',
            'tblborder_insideV_val' => 'nil');

        $this->addStyle(
            array($parameters,
                $styleTableA, $styleTableB,
                $styleTableC, $styleTableD,
                $styleTableE, $styleTableF,
                $styleTableG)
        );

        $parameters = array('type' => 'paragraph',
            'styleId' => 'Ttulo4',
            'name' => 'heading 4',
            'basedOn' => 'Normal',
            'next' => 'Normal',
            'link' => 'Ttulo4Car',
            'uiPriority' => '9',
            'unhideWhenUsed' => 1, 'qFormat' => 1, 'rsid' => '00C967D7',
            'keepNext' => 1, 'keepLines' => 1, 'spacing_after' => '0',
            'spacing_before' => '200',
            'outlineLvl' => 3, 'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'b' => 1, 'bCs' => 1, 'i' => 1, 'iCs' => 1,
            'color_val' => '244061',
            'color_themeColor' => 'accent1',
            'color_themeShade' => '80');
        $this->addStyle(array($parameters));

        $parameters = array('type' => 'character',
            'customStyle' => '1', 'styleId' => 'Ttulo4Car',
            'name' => 'Titulo 4 Car',
            'basedOn' => 'Fuentedeprrafopredeter',
            'link' => 'Ttulo4',
            'uiPriority' => '9', 'rsid' => '00C967D7',
            'rFonts_asciiTheme' => 'majorHAnsi',
            'rFonts_eastAsiaTheme' => 'majorEastAsia',
            'rFonts_hAnsiTheme' => 'majorHAnsi',
            'rFonts_cstheme' => 'majorBidi',
            'b' => 1, 'bCs' => 1, 'i' => 1, 'iCs' => 1,
            'color_val' => '244061',
            'color_themeColor' => 'accent1',
            'color_themeShade' => '80',
            'sz' => '24');
        $this->addStyle(array($parameters));
        $parameters = array('type' => 'paragraph',
            'styleId' => 'Textonotaalfinal',
            'name' => 'endnote text',
            'basedOn' => 'Normal',
            'link' => 'TextonotaalfinalCar',
            'semiHidden' => 1,
            'uiPriority' => '99',
            'unhideWhenUsed' => 1, 'rsid' => '00684540',
            'spacing_after' => '0', 'spacing_line' => '240',
            'spacing_lineRule' => 'auto',
            'sz' => '20',
            'szCs' => '20');
        $this->addStyle(array($parameters));

        $parameters = array('type' => 'character',
            'customStyle' => '1', 'styleId' => 'TextonotaalfinalCar',
            'name' => 'Texto nota al final Car',
            'basedOn' => 'Fuentedeprrafopredeter',
            'link' => 'Textonotaalfinal',
            'semiHidden' => 1, 'uiPriority' => '99',
            'rsid' => '00684540',
            'sz' => '20',
            'szCs' => '20');
        $this->addStyle(array($parameters));

        $parameters = array('type' => 'character',
            'styleId' => 'Refdenotaalfinal',
            'name' => 'endnote reference',
            'basedOn' => 'Fuentedeprrafopredeter',
            'semiHidden' => 1,
            'uiPriority' => '99',
            'unhideWhenUsed' => 1, 'rsid' => '00684540',
            'vertAlign' => 'superscript');
        $this->addStyle(array($parameters));
    }

    /**
     * Add table style
     *
     * @access public
     * @param array $param
     */
    public function addStyleTabla($param)
    {
        $objStyle = CreateStyleTable::getInstance();
        $objStyle->addStyleTable($param);
        $this->_xml = str_replace(
            '__GENERATESTYLE__',
            (string) $objStyle,
            $this->_xml
        );
    }

    /**
     * Generate w:style
     *
     * @param string $type
     * @param string $styleId
     * @param string $default
     * @param string $customStyle
     */
    protected function generateSTYLE($type, $styleId, $default = '',
                                     $customStyle = '')
    {
        $this->_xml .= '<' . CreateElement::NAMESPACEWORD .
            ':style ' . CreateElement::NAMESPACEWORD .
            ':type="' . $type . '"';
        if ($default != '')
            $this->_xml .= ' ' . CreateElement::NAMESPACEWORD .
                ':default="' . $default . '"';
        $this->_xml .= ' ' . CreateElement::NAMESPACEWORD .
            ':styleId="' . $styleId . '"';
        if ($customStyle != '')
            $this->_xml .= ' ' . CreateElement::NAMESPACEWORD .
                ':customStyle="' . $customStyle . '"';
        $this->_xml .= '>__GENERATESTYLE__</' .
            CreateElement::NAMESPACEWORD . ':style>';
    }

    /**
     * Generate w:name
     *
     * @access protected
     * @param string $val
     */
    protected function generateNAME($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':name ' . CreateElement::NAMESPACEWORD . ':val="' . $val .
            '"></' . CreateElement::NAMESPACEWORD . ':name>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:basedon
     *
     * @access protected
     * @param string $val
     */
    protected function generateBASEDON($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':basedOn ' . CreateElement::NAMESPACEWORD . ':val="' . $val .
            '"></' . CreateElement::NAMESPACEWORD .
            ':basedOn>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:next
     *
     * @access protected
     * @param string $val
     */
    protected function generateNEXT($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':next ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':next>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:link
     *
     * @access protected
     * @param string $val
     */
    protected function generateLINK($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':link ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':link>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:uipriority
     *
     * @access protected
     * @param string $val
     */
    protected function generateUIPRIORITY($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':uiPriority ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':uiPriority>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:unhidewhenused
     *
     * @access protected
     */
    protected function generateUNHIDEWHENUSED()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':unhideWhenUsed></' . CreateElement::NAMESPACEWORD .
            ':unhideWhenUsed>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:autoredefine
     *
     * @access protected
     */
    protected function generateAUTOREDEFINE()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':autoRedefine></' . CreateElement::NAMESPACEWORD .
            ':autoRedefine>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:qformat
     *
     * @access protected
     */
    protected function generateQFORMAT()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':qFormat></' . CreateElement::NAMESPACEWORD .
            ':qFormat>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:rsid
     *
     * @access protected
     * @param string $val
     */
    protected function generateRSID($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':rsid ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':rsid>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:semihidden
     *
     * @access protected
     */
    protected function generateSEMIHIDDEN()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':semiHidden></' . CreateElement::NAMESPACEWORD .
            ':semiHidden>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
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
            ':pPr>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:keepnext
     *
     * @access protected
     */
    protected function generateKEEPNEXT()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':keepNext></' . CreateElement::NAMESPACEWORD .
            ':keepNext>__GENERATEPPR__';
        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:tabs
     *
     * @access protected
     */
    protected function generateTABS()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':tabs>__GENERATETABS__</' . CreateElement::NAMESPACEWORD .
            ':tabs>__GENERATEPPR__';
        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:tabcenter
     *
     * @access protected
     * @param string $pos
     */
    protected function generateTABCENTER($pos)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':tab ' . CreateElement::NAMESPACEWORD .
            ':val="center" ' . CreateElement::NAMESPACEWORD .
            ':pos="' . $pos . '"></' . CreateElement::NAMESPACEWORD .
            ':tab>__GENERATETABS__';
        $this->_xml = str_replace('__GENERATETABS__', $xml, $this->_xml);
    }

    /**
     * Generate w:tabright
     *
     * @access protected
     * @param string $pos
     */
    protected function generateTABRIGHT($pos)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':tab ' . CreateElement::NAMESPACEWORD .
            ':val="right" ' . CreateElement::NAMESPACEWORD .
            ':pos="' . $pos . '"></' . CreateElement::NAMESPACEWORD .
            ':tab>__GENERATETABS__';
        $this->_xml = str_replace('__GENERATETABS__', $xml, $this->_xml);
    }

    /**
     * Generate w:numpr
     *
     * @access protected
     */
    protected function generateNUMPR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':numPr>__GENERATENUMPR__</' . CreateElement::NAMESPACEWORD .
            ':numPr>__GENERATEPPR__';
        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:ilvl
     *
     * @access protected
     * @param string $val
     */
    protected function generateILVL($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':ilvl ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':ilvl>__GENERATENUMPR__';
        $this->_xml = str_replace('__GENERATENUMPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:keeplines
     *
     * @access protected
     */
    protected function generateKEEPLINES()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':keepLines></' . CreateElement::NAMESPACEWORD .
            ':keepLines>__GENERATEPPR__';
        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:pbdr
     *
     * @access protected
     */
    protected function generatePBDR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':pBdr>__GENERATEPBDR__</' . CreateElement::NAMESPACEWORD .
            ':pBdr>__GENERATEPPR__';
        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:pbdr
     *
     * @param string $val
     * @param string $sz
     * @param string $space
     * @param string $color
     * @param string $themeColor
     */
    protected function generatePBDR_BOTTOM($val, $sz, $space, $color,
                                           $themeColor)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':pBdr ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '" ' . CreateElement::NAMESPACEWORD .
            ':sz="' . $sz . '" ' . CreateElement::NAMESPACEWORD .
            ':space="' . $space . '" ' . CreateElement::NAMESPACEWORD .
            ':color="' . $color . '" ' . CreateElement::NAMESPACEWORD .
            ':themeColor="' . $themeColor .
            '"></' . CreateElement::NAMESPACEWORD . ':pBdr>__GENERATEPBDR__';
        $this->_xml = str_replace('__GENERATEPBDR__', $xml, $this->_xml);
    }

    /**
     * Generate w:spacing
     *
     * @param string $before
     * @param string $after
     * @param string $line
     * @param string $lineRule
     */
    protected function generateSPACING($before, $after, $line, $lineRule)
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD . ':spacing';
        if ($before != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':before="' . $before . '"';
        }
        if ($after != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':after="' . $after . '"';
        }
        if ($line != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':line="' . $line . '"';
        }
        if ($lineRule != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':lineRule="' . $lineRule . '"';
        }

        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':spacing>__GENERATEPPR__';

        $this->_xml = str_replace('__GENERATEPPR__', $xmlAux, $this->_xml);
    }

    /**
     * Generate w:spacing
     *
     * @access protected
     * @param string $val
     */
    protected function generateRPR_SPACING($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':spacing ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':spacing>__GENERATERPR__';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:kern
     *
     * @access protected
     * @param string $val
     */
    protected function generateKERN($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':kern ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':kern>__GENERATERPR__';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:contextualspacing
     *
     * @access protected
     */
    protected function generateCONTEXTUALSPACING()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':contextualSpacing></' . CreateElement::NAMESPACEWORD .
            ':contextualSpacing>__GENERATEPPR__';
        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:outlinelvl
     *
     * @access protected
     * @param string $val
     */
    protected function generateOUTLINELVL($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':outlineLvl ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':outlineLvl>__GENERATEPPR__';
        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:ind
     *
     * @access protected
     * @param string $left
     */
    protected function generateIND($left)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':ind ' . CreateElement::NAMESPACEWORD .
            ':left="' . $left . '"></' . CreateElement::NAMESPACEWORD .
            ':ind>__GENERATEPPR__';
        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:tcpr
     *
     * @access protected
     */
    protected function generateTCPR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':tcPr>__GENERATETCPR__</' . CreateElement::NAMESPACEWORD .
            ':tcPr>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
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
    protected function generateSHD($val, $color='', $fill='', $themeFill='',
                                   $themeFillTint='')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD .
            ':shd ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"';
        if ($color != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':color="' . $color . '"';
        }
        if ($fill != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':fill="' . $fill . '"';
        }
        if ($themeFill != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeFill="' . $themeFill . '"';
        }
        if ($themeFillTint != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeFillTint="' . $themeFillTint . '"';
        }

        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':shd>__GENERATETCPR__';
        $this->_xml = str_replace('__GENERATETCPR__', $xmlAux, $this->_xml);
    }

    /**
     * Generate w:tblpr
     *
     * @access protected
     */
    protected function generateTBLPR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':tblPr>__GENERATETBLPR__</' . CreateElement::NAMESPACEWORD .
            ':tblPr>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:tblind
     *
     * @param string $w
     * @param string $type
     */
    protected function generateTBLIND($w, $type)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':tblInd ' . CreateElement::NAMESPACEWORD . ':w="' . $w .
            '" ' . CreateElement::NAMESPACEWORD . ':type="' . $type .
            '"></' . CreateElement::NAMESPACEWORD .
            ':tblInd>__GENERATETBLPR__';
        $this->_xml = str_replace('__GENERATETBLPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:tblstylerowbandsize
     *
     * @param string $val
     */
    protected function generateTBLSTYLEROWBANDSIZE($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':tblStyleRowBandSize ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':tblStyleRowBandSize>__GENERATETBLPR__';
        $this->_xml = str_replace('__GENERATETBLPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:tblstylecolbandsize
     *
     * @param string $val
     */
    protected function generateTBLSTYLECOLBANDSIZE($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':tblStyleColBandSize ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':tblStyleColBandSize>__GENERATETBLPR__';
        $this->_xml = str_replace('__GENERATETBLPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:tblborders
     */
    protected function generateTBLBORDERS()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':tblBorders>__GENERATETBLBORDERS__</' .
            CreateElement::NAMESPACEWORD .
            ':tblBorders>__GENERATETBLPR__';
        $this->_xml = str_replace('__GENERATETBLPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:top
     *
     * @param string $val
     * @param string $sz
     * @param string $space
     * @param string $color
     * @param string $themeColor
     * @param string $themeTint
     */
    protected function generateTBLBORDERS_TOP($val, $sz = '', $space = '',
                                              $color = '', $themeColor = '',
                                              $themeTint ='')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD .
            ':top ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"';
        if ($sz != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':sz="' . $sz . '"';
        }
        if ($space != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':space="' . $space . '"';
        }
        if ($color != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':color="' . $color . '"';
        }
        if ($themeColor != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeColor="' . $themeColor . '"';
        }
        if ($themeTint != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeTint="' . $themeTint . '"';
        }
        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':top>__GENERATETBLBORDERS__';
        $this->_xml = str_replace(
            '__GENERATETBLBORDERS__',
            $xmlAux,
            $this->_xml
        );
    }

    /**
     * Generate w:left
     *
     * @param string $val
     * @param string $sz
     * @param string $space
     * @param string $color
     * @param string $themeColor
     * @param string $themeTint
     */
    protected function generateTBLBORDERS_LEFT($val, $sz = '', $space = '',
                                               $color = '', $themeColor = '',
                                               $themeTint = '')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD . ':left ' .
            CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"';
        if ($sz != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':sz="' . $sz . '"';
        }
        if ($space != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':space="' . $space . '"';
        }
        if ($color != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':color="' . $color . '"';
        }
        if ($themeColor != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeColor="' . $themeColor . '"';
        }
        if ($themeTint != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeTint="' . $themeTint . '"';
        }
        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':left>__GENERATETBLBORDERS__';
        $this->_xml = str_replace(
            '__GENERATETBLBORDERS__',
            $xmlAux,
            $this->_xml
        );
    }

    /**
     * Generate w:bottom
     *
     * @param string $val
     * @param string $sz
     * @param string $space
     * @param string $color
     * @param string $themeColor
     * @param string $themeTint
     */
    protected function generateTBLBORDERS_BOTTOM($val, $sz = '', $space = '',
                                                 $color = '', $themeColor = '',
                                                 $themeTint = '')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD .
            ':bottom ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"';
        if ($sz != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':sz="' . $sz . '"';
        }
        if ($space != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':space="' . $space . '"';
        }
        if ($color != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':color="' . $color . '"';
        }
        if ($themeColor != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeColor="' . $themeColor . '"';
        }
        if ($themeTint != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeTint="' . $themeTint . '"';
        }

        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':bottom>__GENERATETBLBORDERS__';

        $this->_xml = str_replace(
            '__GENERATETBLBORDERS__',
            $xmlAux,
            $this->_xml
        );
    }

    /**
     * Generate w:right
     *
     * @param string $val
     * @param string $sz
     * @param string $space
     * @param string $color
     * @param string $themeColor
     * @param string $themeTint
     */
    protected function generateTBLBORDERS_RIGHT($val, $sz='', $space='',
                                                $color='', $themeColor='',
                                                $themeTint='')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD .
            ':right ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"';
        if ($sz != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':sz="' . $sz . '"';
        }
        if ($space != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':space="' . $space . '"';
        }
        if ($color != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':color="' . $color . '"';
        }
        if ($themeColor != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeColor="' . $themeColor . '"';
        }
        if ($themeTint != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeTint="' . $themeTint . '"';
        }
        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':right>__GENERATETBLBORDERS__';
        $this->_xml = str_replace(
            '__GENERATETBLBORDERS__', $xmlAux, $this->_xml
        );
    }

    /**
     * Generate w:insideh
     *
     * @param string $val
     * @param string $sz
     * @param string $space
     * @param string $color
     * @param string $themeColor
     * @param string $themeTint
     */
    protected function generateTBLBORDERS_INSIDEH($val, $sz='', $space='',
                                                  $color='', $themeColor='',
                                                  $themeTint='')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD .
            ':insideH ' . CreateElement::NAMESPACEWORD . ':val="' . $val . '"';
        if ($sz != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':sz="' . $sz . '"';
        }
        if ($space != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':space="' . $space . '"';
        }
        if ($color != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':color="' . $color . '"';
        }
        if ($themeColor != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeColor="' . $themeColor . '"';
        }
        if ($themeTint != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeTint="' . $themeTint . '"';
        }
        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':insideH>__GENERATETBLBORDERS__';
        $this->_xml = str_replace(
            '__GENERATETBLBORDERS__',
            $xmlAux,
            $this->_xml
        );
    }

    /**
     * Generate w:insidev
     *
     * @param string $val
     * @param string $sz
     * @param string $space
     * @param string $color
     * @param string $themeColor
     * @param string $themeTint
     */
    protected function generateTBLBORDERS_INSIDEV($val, $sz='', $space='',
                                                  $color='', $themeColor='',
                                                  $themeTint='')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD .
            ':insideV ' . CreateElement::NAMESPACEWORD . ':val="' . $val . '"';
        if ($sz != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':sz="' . $sz . '"';
        }
        if ($space != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':space="' . $space . '"';
        }
        if ($color != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':color="' . $color . '"';
        }
        if ($themeColor != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeColor="' . $themeColor . '"';
        }
        if ($themeTint != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeTint="' . $themeTint . '"';
        }
        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':insideV>__GENERATETBLBORDERS__';

        $this->_xml = str_replace(
            '__GENERATETBLBORDERS__',
            $xmlAux,
            $this->_xml
        );
    }

    /**
     * Generate w:tblcellmar
     */
    protected function generateTBLCELLMAR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':tblCellMar>__GENERATETBLCELLMAR__</' .
            CreateElement::NAMESPACEWORD .
            ':tblCellMar>__GENERATETBLPR__';
        $this->_xml = str_replace('__GENERATETBLPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:top
     *
     * @access protected
     * @param string $w
     * @param string $type
     */
    protected function generateTOP($w, $type)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':top ' . CreateElement::NAMESPACEWORD . ':w="' . $w .
            '" ' . CreateElement::NAMESPACEWORD . ':type="' . $type .
            '"></' . CreateElement::NAMESPACEWORD .
            ':top>__GENERATETBLCELLMAR__';
        $this->_xml = str_replace('__GENERATETBLCELLMAR__', $xml, $this->_xml);
    }

    /**
     * Generate w:left
     *
     * @access protected
     * @param string $w
     * @param string $type
     */
    protected function generateLEFT($w, $type)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':left ' . CreateElement::NAMESPACEWORD .
            ':w="' . $w . '" ' . CreateElement::NAMESPACEWORD .
            ':type="' . $type . '"></' . CreateElement::NAMESPACEWORD .
            ':left>__GENERATETBLCELLMAR__';
        $this->_xml = str_replace('__GENERATETBLCELLMAR__', $xml, $this->_xml);
    }

    /**
     * Generate w:bottom
     *
     * @access protected
     * @param string $w
     * @param string $type
     */
    protected function generateBOTTOM($w, $type)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':bottom ' . CreateElement::NAMESPACEWORD .
            ':w="' . $w . '" ' . CreateElement::NAMESPACEWORD .
            ':type="' . $type . '"></' . CreateElement::NAMESPACEWORD .
            ':bottom>__GENERATETBLCELLMAR__';
        $this->_xml = str_replace('__GENERATETBLCELLMAR__', $xml, $this->_xml);
    }

    /**
     * Generate w:right
     *
     * @access protected
     * @param string $w
     * @param string $type
     */
    protected function generateRIGHT($w, $type)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':right ' . CreateElement::NAMESPACEWORD .
            ':w="' . $w . '" ' . CreateElement::NAMESPACEWORD .
            ':type="' . $type . '"></' . CreateElement::NAMESPACEWORD .
            ':right>__GENERATETBLCELLMAR__';
        $this->_xml = str_replace('__GENERATETBLCELLMAR__', $xml, $this->_xml);
    }

    /**
     * Generate w:rpr
     */
    protected function generateRPR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':rPr>__GENERATERPR__</' . CreateElement::NAMESPACEWORD .
            ':rPr>__GENERATESTYLE__';
        $this->_xml = str_replace('__GENERATESTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate w:rfonts
     *
     * @access protected
     * @param string $asciiTheme
     * @param string $eastAsiaTheme
     * @param string $hAnsiTheme
     * @param string $cstheme
     */
    protected function generateRFONTS($asciiTheme, $eastAsiaTheme, $hAnsiTheme,
                                      $cstheme)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':rFonts ' . CreateElement::NAMESPACEWORD .
            ':asciiTheme="' . $asciiTheme . '" ' .
            CreateElement::NAMESPACEWORD .
            ':eastAsiaTheme="' . $eastAsiaTheme .
            '" ' . CreateElement::NAMESPACEWORD .
            ':hAnsiTheme="' . $hAnsiTheme .
            '" ' . CreateElement::NAMESPACEWORD .
            ':cstheme="' . $cstheme .
            '"></' . CreateElement::NAMESPACEWORD .
            ':rFonts>__GENERATERPR__';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:rfonts
     *
     * @access protected
     * @param string $ascii
     * @param string $hAnsi
     * @param string $cs
     */
    protected function generateRFONTS2($ascii, $hAnsi, $cs)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':rFonts ' . CreateElement::NAMESPACEWORD .
            ':ascii="' . $ascii . '" ' . CreateElement::NAMESPACEWORD .
            ':hAnsi="' . $hAnsi . '" ' . CreateElement::NAMESPACEWORD .
            ':cs="' . $cs . '"></' . CreateElement::NAMESPACEWORD .
            ':rFonts>__GENERATERPR__';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:b
     *
     * @access protected
     */
    protected function generateB()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':b></' . CreateElement::NAMESPACEWORD .
            ':b>__GENERATERPR__';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:bcs
     *
     * @access protected
     */
    protected function generateBCS()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':bCs></' . CreateElement::NAMESPACEWORD .
            ':bCs>__GENERATERPR__';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:i
     *
     * @access protected
     * @param string $val
     */
    protected function generateI($val='')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD . ':i';
        if ($val != 1 || $val != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':val="' . $val . '"';
        }
        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':i>__GENERATERPR__';

        $this->_xml = str_replace('__GENERATERPR__', $xmlAux, $this->_xml);
    }

    /**
     * Generate w:ics
     *
     * @access protected
     * @param string $val
     */
    protected function generateICS($val = '')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD . ':iCs';
        if ($val != 1 || $val != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':val="' . $val . '"';
        }
        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':iCs>__GENERATERPR__';

        $this->_xml = str_replace('__GENERATERPR__', $xmlAux, $this->_xml);
    }

    /**
     * Generate w:u
     *
     * @access protected
     * @param string $val
     */
    protected function generateU($val = '')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD . ':u';
        if ($val != '' && $val != 1) {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':val="' . $val . '"';
        }
        $xmlAux .= '></' . CreateElement::NAMESPACEWORD . ':u>__GENERATERPR__';

        $this->_xml = str_replace('__GENERATERPR__', $xmlAux, $this->_xml);
    }

    /**
     * Generate w:color
     *
     * @access protected
     * @param string $val
     * @param string $themeColor
     * @param string $themeShade
     */
    protected function generateCOLOR($val, $themeColor='', $themeShade = '')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD .
            ':color ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"';
        if ($themeColor != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeColor="' . $themeColor . '"';
        }
        if ($themeShade != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD .
                ':themeShade="' . $themeShade . '"';
        }
        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':color>__GENERATERPR__';

        $this->_xml = str_replace('__GENERATERPR__', $xmlAux, $this->_xml);
    }

    /**
     * Generate w:sz
     *
     * @access protected
     * @param string $val
     */
    protected function generateSZ($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':sz ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':sz>__GENERATERPR__';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:szcs
     *
     * @access protected
     * @param string $val
     */
    protected function generateSZCS($val)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':szCs ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':szCs>__GENERATERPR__';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:vertAlign
     *
     * @access protected
     * @param string $val
     */
    protected function generateVERTALIGN($val = 'superscript')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
            ':vertAlign ' . CreateElement::NAMESPACEWORD .
            ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
            ':vertAlign>__GENERATERPR__';
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

}
