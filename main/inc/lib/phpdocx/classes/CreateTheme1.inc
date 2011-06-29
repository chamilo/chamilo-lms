<?php

/**
 *
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
 *
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
class CreateTheme1 extends CreateElement
{
    const NAMESPACEWORD = 'a';

    /**
     * @access private
     * @var CreateFooter
     * @static
     */
    private static $_instance = NULL;

    /**
     *
     * @access protected
     * @var string
     */
    protected $_xml;

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
     * @return CreateTheme1
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateTheme1();
        }
        return self::$_instance;
    }

    /**
     * Create theme
     *
     * @access public
     * @param string $font
     */
    public function createTheme($font)
    {

        $this->_xml = '';
        $this->generateTHEMEELEMENTS();
        $this->generateCLRSCHEME('Office');
        $this->generateDK1();
        $this->generateSYSCLR("windowText", "000000");
        $this->generateLT1();
        $this->generateSYSCLR("window", "FFFFFF");
        $this->generateDK2();
        $this->generateSRGBCLR('1F497D');
        $this->generateLT2();
        $this->generateSRGBCLR('EEECE1');
        $this->generateACCENT1();
        $this->generateSRGBCLR('4F81BD');
        $this->generateACCENT2();
        $this->generateSRGBCLR('C0504D');
        $this->generateACCENT3();
        $this->generateSRGBCLR('9BBB59');
        $this->generateACCENT4();
        $this->generateSRGBCLR('8064A2');
        $this->generateACCENT5();
        $this->generateSRGBCLR('4BACC6');
        $this->generateACCENT6();
        $this->generateSRGBCLR('F79646');
        $this->generateHLINK();
        $this->generateSRGBCLR('0000FF');
        $this->generateFOLHLINK();
        $this->generateSRGBCLR('800080');
        if ($font != '') {
            $this->generateFONTSCHEMA('Office');
            $this->generateMAYORFONT();
            $this->generateLATIN($font);
            $this->generateEA('');
            $this->generateCS('');
            $this->generateMINORFONT();
            $this->generateLATIN($font);
            $this->generateEA('');
            $this->generateCS('');
        } else {
            $this->generateFONTSCHEMA('Office');
            $this->generateMAYORFONT();
            $this->generateLATIN('Cambria');
            $this->generateEA('');
            $this->generateCS('');
            $this->generateFONT('Jpan', 'ＭＳ 明朝');
            $this->generateFONT2('Hang', '맑은 고딕');
            $this->generateFONT2('Hans', '宋体');
            $this->generateFONT2('Hant', '新細明體');
            $this->generateFONT2('Arab', 'Times New Roman');
            $this->generateFONT2('Hebr', 'Times New Roman');
            $this->generateFONT2('Thai', 'Angsana New');
            $this->generateFONT2('Ethi', 'Nyala');
            $this->generateFONT2('Beng', 'Vrinda');
            $this->generateFONT2('Gujr', 'Shruti');
            $this->generateFONT2('Khmr', 'MoolBoran');
            $this->generateFONT2('Knda', 'Tunga');
            $this->generateFONT2('Guru', 'Raavi');
            $this->generateFONT2('Cans', 'Euphemia');
            $this->generateFONT2('Cher', 'Plantagenet Cherokee');
            $this->generateFONT2('Yiii', 'Microsoft Yi Baiti');
            $this->generateFONT2('Tibt', 'Microsoft Himalaya');
            $this->generateFONT2('Thaa', 'MV Boli');
            $this->generateFONT2('Deva', 'Mangal');
            $this->generateFONT2('Telu', 'Gautami');
            $this->generateFONT2('Taml', 'Latha');
            $this->generateFONT2('Syrc', 'Estrangelo Edessa');
            $this->generateFONT2('Orya', 'Kalinga');
            $this->generateFONT2('Mlym', 'Kartika');
            $this->generateFONT2('Laoo', 'DokChampa');
            $this->generateFONT2('Sinh', 'Iskoola Pota');
            $this->generateFONT2('Mong', 'Mongolian Baiti');
            $this->generateFONT2('Viet', 'Times New Roman');
            $this->generateFONT3('Uigh', 'Microsoft Uighur');
            $this->generateMINORFONT();
            $this->generateLATIN('Calibri');
            $this->generateEA('');
            $this->generateCS('');
            $this->generateFONT('Jpan', 'ＭＳ 明朝');
            $this->generateFONT2('Hang', '맑은 고딕');
            $this->generateFONT2('Hans', '宋体');
            $this->generateFONT2('Hant', '新細明體');
            $this->generateFONT2('Arab', 'Arial');
            $this->generateFONT2('Hebr', 'Arial');
            $this->generateFONT2('Thai', 'Cordia New');
            $this->generateFONT2('Ethi', 'Nyala');
            $this->generateFONT2('Beng', 'Vrinda');
            $this->generateFONT2('Gujr', 'Shruti');
            $this->generateFONT2('Khmr', 'DaunPenh');
            $this->generateFONT2('Knda', 'Tunga');
            $this->generateFONT2('Guru', 'Raavi');
            $this->generateFONT2('Cans', 'Euphemia');
            $this->generateFONT2('Cher', 'Plantagenet Cherokee');
            $this->generateFONT2('Yiii', 'Microsoft Yi Baiti');
            $this->generateFONT2('Tibt', 'Microsoft Himalaya');
            $this->generateFONT2('Thaa', 'MV Boli');
            $this->generateFONT2('Deva', 'Mangal');
            $this->generateFONT2('Telu', 'Gautami');
            $this->generateFONT2('Taml', 'Latha');
            $this->generateFONT2('Syrc', 'Estrangelo Edessa');
            $this->generateFONT2('Orya', 'Kalinga');
            $this->generateFONT2('Mlym', 'Kartika');
            $this->generateFONT2('Laoo', 'DokChampa');
            $this->generateFONT2('Sinh', 'Iskoola Pota');
            $this->generateFONT2('Mong', 'Mongolian Baiti');
            $this->generateFONT2('Viet', 'Arial');
            $this->generateFONT3('Uigh', 'Microsoft Uighur');
        }
        $this->generateFMTSCHEME('Office');
        $this->generateFILLSTYLELST();
        $this->generateSOLIDFILL();
        $this->generateSCHEMECLR('phClr');
        $this->generateGRADFILL(1);
        $this->generateGSLST();
        $this->generateGS(0);
        $this->generateSCHEMECLR2('phClr');
        $this->generateTINT('50000');
        $this->generateSATMOD('300000');
        $this->generateGS(35000);
        $this->generateSCHEMECLR2('phClr');
        $this->generateTINT(37000);
        $this->generateSATMOD(300000);
        $this->generateGS2(100000);
        $this->generateSCHEMECLR2('phClr');
        $this->generateTINT(15000);
        $this->generateSATMOD(350000);
        $this->generateLIN(16200000, 1);

        $this->generateGRADFILL2(1);
        $this->generateGSLST();
        $this->generateGS(0);
        $this->generateSCHEMECLR2('phClr');
        $this->generateSHADE('51000');
        $this->generateSATMOD('130000');
        $this->generateGS(80000);
        $this->generateSCHEMECLR2('phClr');
        $this->generateSHADE(93000);
        $this->generateSATMOD(130000);
        $this->generateGS2(100000);
        $this->generateSCHEMECLR2('phClr');
        $this->generateSHADE(94000);
        $this->generateSATMOD(135000);
        $this->generateLIN(16200000, 0);

        $this->generateLNSTYLELST();
        $this->generateLN(9525, "flat", "sng", "ctr");
        $this->generateSOLIDFILL();
        $this->generateSCHEMECLR3('phClr');
        $this->generateSHADE('95000');
        $this->generateSATMOD('105000');
        $this->generatePRSTDASH('solid');
        $this->generateLN(25400, "flat", "sng", "ctr");
        $this->generateSOLIDFILL();
        $this->generateSCHEMECLR('phClr');
        $this->generatePRSTDASH('solid');
        $this->generateLN2(38100, 'flat', 'sng', 'ctr');
        $this->generateSOLIDFILL();
        $this->generateSCHEMECLR('phClr');
        $this->generatePRSTDASH('solid');

        $this->generateEFFECTSTYLELST();
        $this->generateEFFECTSTYLE();
        $this->generateEFFECTLST();
        $this->generateOUTERSHDW(40000, 20000, 5400000, 0);
        $this->generateSRGBCLR2('000000');
        $this->generateALPHA(38000);
        $this->generateEFFECTSTYLE();
        $this->generateEFFECTLST();
        $this->generateOUTERSHDW(40000, 23000, 5400000, 0);
        $this->generateSRGBCLR2('000000');
        $this->generateALPHA(35000);
        $this->generateEFFECTSTYLE2();
        $this->generateEFFECTLST2();
        $this->generateOUTERSHDW(40000, 23000, 5400000, 0);
        $this->generateSRGBCLR2('000000');
        $this->generateALPHA(35000);

        $this->generateSCENE3D();
        $this->generateCAMERA('orthographicFront');
        $this->generateROT(0, 0, 0);
        $this->generateLIGHTRIG('threePt', 't');
        $this->generateROT(0, 0, 1200000);
        $this->generateSP3D();
        $this->generateBEVELT(63500, 25400);
        $this->generateBGFILLSTYLELST();
        $this->generateSOLIDFILL();
        $this->generateSCHEMECLR('phClr');
        $this->generateGRADFILL(1);
        $this->generateGSLST();
        $this->generateGS(0);
        $this->generateSCHEMECLR2('phClr');
        $this->generateTINT('40000');
        $this->generateSATMOD('350000');
        $this->generateGS(40000);
        $this->generateSCHEMECLR2('phClr');
        $this->generateTINT2(45000);
        $this->generateSHADE(99000);
        $this->generateSATMOD(350000);
        $this->generateGS2(100000);
        $this->generateSCHEMECLR2('phClr');
        $this->generateSHADE(20000);
        $this->generateSATMOD(255000);
        $this->generatePATH('circle');
        $this->generateFILLTORECT(50000, '-80000', 50000, 180000);

        $this->generateGRADFILL2(1);
        $this->generateGSLST();
        $this->generateGS(0);
        $this->generateSCHEMECLR2('phClr');
        $this->generateTINT('80000');
        $this->generateSATMOD('300000');
        $this->generateGS2(100000);
        $this->generateSCHEMECLR2('phClr');
        $this->generateSHADE(30000);
        $this->generateSATMOD(200000);
        $this->generatePATH('circle');
        $this->generateFILLTORECT(50000, 50000, 50000, 50000);
        $this->generateOBJECTDEFAULTS();
        $this->generateEXTRACLRSCHEMELST();
        $this->cleanTemplate();
    }

    /**
     * Generate a:themeelements
     *
     * @access protected
     */
    protected function generateTHEMEELEMENTS()
    {
        $this->_xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':themeElements>__GENERATETHEMEELEMENTS1____' .
            'GENERATETHEMEELEMENTS2____GENERATETHEMEELEMENTS3__</' .
            CreateTheme1::NAMESPACEWORD .
            ':themeElements>__GENERATETHEMEELEMENTS4__';
    }

    /**
     * Generate a:clrscheme
     *
     * @access protected
     * @param string $name
     */
    protected function generateCLRSCHEME($name = 'Office')
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':clrScheme name="' . $name .
            '">__GENERATECLRSCHEME__</' . CreateTheme1::NAMESPACEWORD .
            ':clrScheme>';

        $this->_xml = str_replace(
            '__GENERATETHEMEELEMENTS1__',
            $xml,
            $this->_xml
        );
    }

    /**
     * Generate a:dk1
     *
     * @access protected
     */
    protected function generateDK1()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':dk1>__GENERATESYSCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':dk1>__GENERATEDK1__';

        $this->_xml = str_replace('__GENERATECLRSCHEME__', $xml, $this->_xml);
    }

    /**
     * Generate a:sysclr
     * 
     * @access protected
     * @param string $val
     * @param string $lastClr
     */
    protected function generateSYSCLR($val = 'windowText', $lastClr = '000000')
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':sysClr val="' . $val . '" lastClr="' . $lastClr .
            '"></' . CreateTheme1::NAMESPACEWORD . ':sysClr>';
        $this->_xml = str_replace('__GENERATESYSCLR__', $xml, $this->_xml);
    }

    /**
     * Generate a:lt1
     *
     * @access protected
     */
    protected function generateLT1()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':lt1>__GENERATESYSCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':lt1>__GENERATELT1__';
        $this->_xml = str_replace('__GENERATEDK1__', $xml, $this->_xml);
    }

    /**
     * Generate a:dk2
     *
     * @access protected
     */
    protected function generateDK2()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':dk2>__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':dk2>__GENERATEDK2__';
        $this->_xml = str_replace('__GENERATELT1__', $xml, $this->_xml);
    }

    /**
     * Generate a:srgbclr
     *
     * @access protected
     * @param string $val
     */
    protected function generateSRGBCLR($val = 'windowText')
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':srgbClr val="' . $val .
            '"></' . CreateTheme1::NAMESPACEWORD . ':srgbClr>';
        $this->_xml = str_replace('__GENERATESRGBCLR__', $xml, $this->_xml);
    }

    /**
     * Generate a:lt2
     *
     * @access protected
     */
    protected function generateLT2()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':lt2>__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':lt2>__GENERATELT2__';
        $this->_xml = str_replace('__GENERATEDK2__', $xml, $this->_xml);
    }

    /**
     * Generate a:accent1
     *
     * @access protected
     */
    protected function generateACCENT1()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':accent1>__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':accent1>__GENERATEACCENT1__';
        $this->_xml = str_replace('__GENERATELT2__', $xml, $this->_xml);
    }

    /**
     * Generate a:accent2
     *
     * @access protected
     */
    protected function generateACCENT2()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':accent2>__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':accent2>__GENERATEACCENT2__';
        $this->_xml = str_replace('__GENERATEACCENT1__', $xml, $this->_xml);
    }

    /**
     * Generate a:accent3
     *
     * @access protected
     */
    protected function generateACCENT3()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':accent3>__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':accent3>__GENERATEACCENT3__';
        $this->_xml = str_replace('__GENERATEACCENT2__', $xml, $this->_xml);
    }

    /**
     * Generate a:accent4
     *
     * @access protected
     */
    protected function generateACCENT4()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':accent4>__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':accent4>__GENERATEACCENT4__';
        $this->_xml = str_replace('__GENERATEACCENT3__', $xml, $this->_xml);
    }

    /**
     * Generate a:accent5
     *
     * @access protected
     */
    protected function generateACCENT5()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':accent5>__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':accent5>__GENERATEACCENT5__';
        $this->_xml = str_replace('__GENERATEACCENT4__', $xml, $this->_xml);
    }

    /**
     * Generate a:accent6
     *
     * @access protected
     */
    protected function generateACCENT6()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':accent6>__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':accent6>__GENERATEACCENT6__';
        $this->_xml = str_replace('__GENERATEACCENT5__', $xml, $this->_xml);
    }

    /**
     * Generate a:hlink
     *
     * @access protected
     */
    protected function generateHLINK()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':hlink>__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':hlink>__GENERATEHLINK__';
        $this->_xml = str_replace('__GENERATEACCENT6__', $xml, $this->_xml);
    }

    /**
     * Generate a:folhlink
     *
     * @access protected
     */
    protected function generateFOLHLINK()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':folHlink>__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':folHlink>';
        $this->_xml = str_replace('__GENERATEHLINK__', $xml, $this->_xml);
    }

    /**
     * Generate a:fontschema
     *
     * @access protected
     * @param string $name
     */
    protected function generateFONTSCHEMA($name = 'Office')
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':fontScheme name="' . $name .
            '">__GENERATEFONTSCHEMA1____GENERATEFONTSCHEMA2__</' .
            CreateTheme1::NAMESPACEWORD . ':fontScheme>';

        $this->_xml = str_replace(
            '__GENERATETHEMEELEMENTS2__', $xml, $this->_xml
        );
    }

    /**
     * Generate a:mayorfont
     *
     * @access protected
     */
    protected function generateMAYORFONT()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':majorFont>__GENERATEMAYORFONT__</' .
            CreateTheme1::NAMESPACEWORD .
            ':majorFont>';

        $this->_xml = str_replace('__GENERATEFONTSCHEMA1__', $xml, $this->_xml);
    }

    /**
     * Generate a:latin
     *
     * @access protected
     * @param string $typeface
     */
    protected function generateLATIN($typeface = 'cambia')
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':latin typeface="' . $typeface .
            '"></' . CreateTheme1::NAMESPACEWORD .
            ':latin>__GENERATELATIN__';

        $this->_xml = str_replace('__GENERATEMAYORFONT__', $xml, $this->_xml);
    }

    /**
     * Generate a:ea
     *
     * @access protected
     * @param string $typeface
     */
    protected function generateEA($typeface = '')
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':ea typeface="' . $typeface .
            '"></' . CreateTheme1::NAMESPACEWORD .
            ':ea>__GENERATEEA__';
        $this->_xml = str_replace('__GENERATELATIN__', $xml, $this->_xml);
    }

    /**
     * Generate a:cs
     *
     * @access protected
     * @param string $typeface
     */
    protected function generateCS($typeface = '')
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':cs typeface="' . $typeface .
            '"></' . CreateTheme1::NAMESPACEWORD .
            ':cs>__GENERATECS__';

        $this->_xml = str_replace('__GENERATEEA__', $xml, $this->_xml);
    }

    /**
     * Generate a:font
     *
     * @access protected
     * @param string $script
     * @param string $typeface
     */
    protected function generateFONT($script = '', $typeface = '')
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':font script="' . $script .
            '" typeface="' . $typeface .
            '"></' . CreateTheme1::NAMESPACEWORD .
            ':font>__GENERATEFONT__';
        $this->_xml = str_replace('__GENERATECS__', $xml, $this->_xml);
    }

    /**
     * Generate a:font
     *
     * @access protected
     * @param string script
     * @param string $typeface
     */
    protected function generateFONT2($script = '', $typeface = '')
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':font script="' . $script .
            '" typeface="' . $typeface .
            '"></' . CreateTheme1::NAMESPACEWORD .
            ':font>__GENERATEFONT__';

        $this->_xml = str_replace('__GENERATEFONT__', $xml, $this->_xml);
    }

    /**
     * Generate a:font
     *
     * @access protected
     * @param string $script
     * @param string $typeface
     */
    protected function generateFONT3($script = '', $typeface = '')
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':font script="' . $script .
            '" typeface="' . $typeface .
            '"></' . CreateTheme1::NAMESPACEWORD .
            ':font>';
        $this->_xml = str_replace('__GENERATEFONT__', $xml, $this->_xml);
    }

    /**
     * Generate a:minorfont
     *
     * @access protected
     */
    protected function generateMINORFONT()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':minorFont>__GENERATEMAYORFONT__</' .
            CreateTheme1::NAMESPACEWORD . ':minorFont>';
        $this->_xml = str_replace('__GENERATEFONTSCHEMA2__', $xml, $this->_xml);
    }

    /**
     * Generate a:fmtscheme
     *
     * @access protected
     * @param string $name
     */
    protected function generateFMTSCHEME($name)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':fmtScheme name="' . $name .
            '">__GENERATEFMTSCHEME__</' . CreateTheme1::NAMESPACEWORD .
            ':fmtScheme>';
        $this->_xml = str_replace(
            '__GENERATETHEMEELEMENTS3__', $xml, $this->_xml
        );
    }

    /**
     * Generate a:fillstylelst
     *
     * @access protected
     */
    protected function generateFILLSTYLELST()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':fillStyleLst>__GENERATEFILLSTYLELST__</' .
            CreateTheme1::NAMESPACEWORD .
            ':fillStyleLst>__GENERATEFILLSTYLELST2__';
        $this->_xml = str_replace('__GENERATEFMTSCHEME__', $xml, $this->_xml);
    }

    /**
     * Generate a:solidfill
     *
     * @access protected
     */
    protected function generateSOLIDFILL()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':solidFill>__GENERATESOLIDFILL__</' .
            CreateTheme1::NAMESPACEWORD .
            ':solidFill>__GENERATESOLIDFILL2__';

        $this->_xml = str_replace(
            '__GENERATEFILLSTYLELST__', $xml, $this->_xml
        );
    }

    /**
     * Generate a:schemeclr
     *
     * @access protected
     * @param string $val
     */
    protected function generateSCHEMECLR($val)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':schemeClr val="' . $val .
            '"></' . CreateTheme1::NAMESPACEWORD . ':schemeClr>';

        $this->_xml = str_replace('__GENERATESOLIDFILL__', $xml, $this->_xml);
    }

    /**
     * Generate a:gradfill
     *
     * @access protected
     * @param string $rotWithShape
     */
    protected function generateGRADFILL($rotWithShape)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':gradFill rotWithShape="' .
            $rotWithShape . '">__GENERATEGRADFILL__</' .
            CreateTheme1::NAMESPACEWORD .
            ':gradFill>__GENERATESOLIDFILL2__';

        $this->_xml = str_replace('__GENERATESOLIDFILL2__', $xml, $this->_xml);
    }

    /**
     * Generate a:gradfill
     *
     * @access protected
     * @param string $rotWithShape
     */
    protected function generateGRADFILL2($rotWithShape)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':gradFill rotWithShape="' .
            $rotWithShape . '">__GENERATEGRADFILL__</' .
            CreateTheme1::NAMESPACEWORD . ':gradFill>';
        $this->_xml = str_replace('__GENERATESOLIDFILL2__', $xml, $this->_xml);
    }

    /**
     * Generate a:gslst
     *
     * @access protected
     */
    protected function generateGSLST()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':gsLst>__GENERATEGSLST__</'.
            CreateTheme1::NAMESPACEWORD . ':gsLst>__GENERATEGSLST2__';
        $this->_xml = str_replace('__GENERATEGRADFILL__', $xml, $this->_xml);
    }

    /**
     * Generate a:gs
     *
     * @access protected
     * @param string $pos
     */
    protected function generateGS($pos)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':gs pos="' . $pos .
            '">__GENERATEPOS__</' . CreateTheme1::NAMESPACEWORD .
            ':gs>__GENERATEGSLST__';
        $this->_xml = str_replace('__GENERATEGSLST__', $xml, $this->_xml);
    }

    /**
     * Generate a:gs
     *
     * @access protected
     * @param string $pos
     */
    protected function generateGS2($pos)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':gs pos="' .
            $pos . '">__GENERATEPOS__</' . CreateTheme1::NAMESPACEWORD . ':gs>';
        $this->_xml = str_replace('__GENERATEGSLST__', $xml, $this->_xml);
    }

    /**
     * Generate a:schemeclr
     *
     * @access protected
     * @param string $val
     */
    protected function generateSCHEMECLR2($val)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':schemeClr val="' . $val .
            '">__GENERATESCHEMECLR2__</' . CreateTheme1::NAMESPACEWORD .
            ':schemeClr>';
        $this->_xml = str_replace('__GENERATEPOS__', $xml, $this->_xml);
    }

    /**
     * Generate a:tint
     *
     * @access protected
     * @param string $val
     */
    protected function generateTINT($val)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':tint val="' . $val .
            '"></' . CreateTheme1::NAMESPACEWORD .
            ':tint>__GENERATETINT__';
        $this->_xml = str_replace('__GENERATESCHEMECLR2__', $xml, $this->_xml);
    }

    /**
     * Generate a:shade
     *
     * @access protected
     * @param string $val
     */
    protected function generateSHADE($val)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':shade val="' . $val .
            '"></' . CreateTheme1::NAMESPACEWORD . ':shade>__GENERATETINT__';
        $this->_xml = str_replace('__GENERATESCHEMECLR2__', $xml, $this->_xml);
    }

    /**
     * Generate a:satmod
     *
     * @access protected
     * @param string $val
     */
    protected function generateSATMOD($val)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':satMod val="' . $val .
            '"></' . CreateTheme1::NAMESPACEWORD . ':satMod>';
        $this->_xml = str_replace('__GENERATETINT__', $xml, $this->_xml);
    }

    /**
     * Generate a:lin
     *
     * @access protected
     * @param string $val
     * @param string $scaled
     */
    protected function generateLIN($ang, $scaled)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':lin ang="' . $ang .
            '" scaled="' . $scaled .
            '"></' . CreateTheme1::NAMESPACEWORD . ':lin>';
        $this->_xml = str_replace('__GENERATEGSLST2__', $xml, $this->_xml);
    }

    /**
     * Generate a:lnstylelst
     *
     * @access protected
     */
    protected function generateLNSTYLELST()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':lnStyleLst>__GENERATELNSTYLELST__</' .
            CreateTheme1::NAMESPACEWORD . ':lnStyleLst>__GENERATELNSTYLELST2__';
        $this->_xml = str_replace(
            '__GENERATEFILLSTYLELST2__', $xml, $this->_xml
        );
    }

    /**
     * Generate a:ln
     *
     * @access protected
     * @param string $w
     * @param string $cap
     * @param string $cmpd
     * @param string $algn
     */
    protected function generateLN($w, $cap, $cmpd, $algn)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':ln w="' . $w .
            '" cap="' . $cap . '" cmpd="' . $cmpd . '" algn="' . $algn .
            '">__GENERATEFILLSTYLELST__</' . CreateTheme1::NAMESPACEWORD .
            ':ln>__GENERATELNSTYLELST__';
        $this->_xml = str_replace('__GENERATELNSTYLELST__', $xml, $this->_xml);
    }

    /**
     * Generate a:ln
     *
     * @access protected
     * @param string $w
     * @param string $cap
     * @param string $cmpd
     * @param string $algn
     */
    protected function generateLN2($w, $cap, $cmpd, $algn)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':ln w="' . $w .
            '" cap="' . $cap . '" cmpd="' . $cmpd . '" algn="' . $algn .
            '">__GENERATEFILLSTYLELST__</' . CreateTheme1::NAMESPACEWORD
            . ':ln>';
        $this->_xml = str_replace('__GENERATELNSTYLELST__', $xml, $this->_xml);
    }

    /**
     * Generate a:schemeclr3
     *
     * @access protected
     * @param string $val
     */
    protected function generateSCHEMECLR3($val)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':schemeClr val="' .
            $val . '">__GENERATESCHEMECLR2__</' . CreateTheme1::NAMESPACEWORD
            . ':schemeClr>';
        $this->_xml = str_replace('__GENERATESOLIDFILL__', $xml, $this->_xml);
    }

    /**
     * Generate a:prstdash
     *
     * @access protected
     * @param string $val
     */
    protected function generatePRSTDASH($val)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':prstDash val="' . $val . '"></' .
            CreateTheme1::NAMESPACEWORD . ':prstDash>';
        $this->_xml = str_replace('__GENERATESOLIDFILL2__', $xml, $this->_xml);
    }

    /**
     * Generate a:effectstylest
     *
     * @access protected
     */
    protected function generateEFFECTSTYLELST()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':effectStyleLst>__GENERATEEFFECTSTYLELST__</' .
            CreateTheme1::NAMESPACEWORD .
            ':effectStyleLst>__GENERATEEFFECTSTYLELST2__';
        $this->_xml = str_replace('__GENERATELNSTYLELST2__', $xml, $this->_xml);
    }

    /**
     * Generate a:effectstyle
     *
     * @access protected
     */
    protected function generateEFFECTSTYLE()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':effectStyle>__GENERATEEFFECTSTYLE__</' .
            CreateTheme1::NAMESPACEWORD .
            ':effectStyle>__GENERATEEFFECTSTYLELST__';
        $this->_xml = str_replace(
            '__GENERATEEFFECTSTYLELST__',
            $xml,
            $this->_xml
        );
    }

    /**
     * Generate a:effectstyle2
     *
     * @access protected
     */
    protected function generateEFFECTSTYLE2()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':effectStyle>__GENERATEEFFECTSTYLE__</' .
            CreateTheme1::NAMESPACEWORD . ':effectStyle>';
        $this->_xml = str_replace(
            '__GENERATEEFFECTSTYLELST__', $xml, $this->_xml
        );
    }

    /**
     * Generate a:effectlst
     *
     * @access protected
     */
    protected function generateEFFECTLST()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':effectLst>__GENERATEEFFECTLST__</' .
            CreateTheme1::NAMESPACEWORD . ':effectLst>';
        $this->_xml = str_replace('__GENERATEEFFECTSTYLE__', $xml, $this->_xml);
    }

    /**
     * Generate a:outershdw
     *
     * @access protected
     * @param string $blurRad
     * @param string $dist
     * @param string $dir
     * @param string $rotWithShape
     */
    protected function generateOUTERSHDW($blurRad, $dist, $dir, $rotWithShape)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':outerShdw blurRad="' . $blurRad . '" dist="' .
            $dist . '" dir="' . $dir . '" rotWithShape="' .
            $rotWithShape . '">__GENERATEOUTERSHDW__</' .
            CreateTheme1::NAMESPACEWORD . ':outerShdw>';
        $this->_xml = str_replace('__GENERATEEFFECTLST__', $xml, $this->_xml);
    }

    /**
     * Generate a:srgbclr2
     *
     * @access protected
     * @param string $val
     */
    protected function generateSRGBCLR2($val)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':srgbClr val="' .
            $val . '">__GENERATESRGBCLR__</' . CreateTheme1::NAMESPACEWORD .
            ':srgbClr>';
        $this->_xml = str_replace('__GENERATEOUTERSHDW__', $xml, $this->_xml);
    }

    /**
     * Generate a:alpha
     *
     * @access protected
     * @param string $val
     */
    protected function generateALPHA($val)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':alpha val="' . $val .
            '"></' . CreateTheme1::NAMESPACEWORD . ':alpha>';
        $this->_xml = str_replace('__GENERATESRGBCLR__', $xml, $this->_xml);
    }

    /**
     * Generate a:effectlst2
     *
     * @access protected
     */
    protected function generateEFFECTLST2()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':effectLst>__GENERATEEFFECTLST__</' . CreateTheme1::NAMESPACEWORD .
            ':effectLst>__GENERATEEFFECTLST2__';
        $this->_xml = str_replace('__GENERATEEFFECTSTYLE__', $xml, $this->_xml);
    }

    /**
     *
     * @access protected
     */
    protected function generateSCENE3D()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':scene3d>__GENERATESCENE3D__</' . CreateTheme1::NAMESPACEWORD .
            ':scene3d>__GENERATESCENE3D2__';
        $this->_xml = str_replace('__GENERATEEFFECTLST2__', $xml, $this->_xml);
    }

    /**
     * Generate a:camera
     *
     * @access protected
     * @param string $prst
     */
    protected function generateCAMERA($prst)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':camera prst="' . $prst .
            '">__GENERATECAMERA__</' . CreateTheme1::NAMESPACEWORD .
            ':camera>__GENERATECAMERA2__';
        $this->_xml = str_replace('__GENERATESCENE3D__', $xml, $this->_xml);
    }

    /**
     * Generate a:rot
     *
     * @access protected
     * @param string $lat
     * @param string $lon
     * @param string @rev
     */
    protected function generateROT($lat, $lon, $rev)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':rot lat="' . $lat .
            '" lon="' . $lon . '" rev="' . $rev .
            '"></' . CreateTheme1::NAMESPACEWORD . ':rot>';
        $this->_xml = str_replace('__GENERATECAMERA__', $xml, $this->_xml);
    }

    /**
     * Generate a:lightrig
     *
     * @access protected
     * @param string $rig
     * @param string $dir
     */
    protected function generateLIGHTRIG($rig, $dir)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':lightRig rig="' . $rig . '" dir="' . $dir .
            '">__GENERATECAMERA__</' . CreateTheme1::NAMESPACEWORD .
            ':lightRig>';
        $this->_xml = str_replace('__GENERATECAMERA2__', $xml, $this->_xml);
    }

    /**
     * Generate a:sp3d
     *
     * @access protected
     */
    protected function generateSP3D()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':sp3d>__GENERATESP3D__</' . CreateTheme1::NAMESPACEWORD .
            ':sp3d>';
        $this->_xml = str_replace('__GENERATESCENE3D2__', $xml, $this->_xml);
    }

    /**
     * Generate a:bevelt
     *
     * @access protected
     * @param string $w
     * @param string $h
     */
    protected function generateBEVELT($w, $h)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':bevelT w="' . $w . '" h="' . $h .
            '"></' . CreateTheme1::NAMESPACEWORD . ':bevelT>';
        $this->_xml = str_replace('__GENERATESP3D__', $xml, $this->_xml);
    }

    /**
     * Generate a:bgfillstylelst
     *
     * @access protected
     */
    protected function generateBGFILLSTYLELST()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':bgFillStyleLst>__GENERATEFILLSTYLELST__</' .
            CreateTheme1::NAMESPACEWORD . ':bgFillStyleLst>';

        $this->_xml = str_replace(
            '__GENERATEEFFECTSTYLELST2__',
            $xml,
            $this->_xml
        );
    }

    /**
     * Generate a:tint2
     *
     * @access protected
     * @param string $val
     */
    protected function generateTINT2($val)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD . ':tint val="' . $val .
            '"></' . CreateTheme1::NAMESPACEWORD .
            ':tint>__GENERATESCHEMECLR2__';
        $this->_xml = str_replace('__GENERATESCHEMECLR2__', $xml, $this->_xml);
    }

    /**
     * Generate a:path
     *
     * @access protected
     * @param string @path
     */
    protected function generatePATH($path)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':path path="' . $path .
            '">__GENERATEPATH__</' . CreateTheme1::NAMESPACEWORD .
            ':path>';
        $this->_xml = str_replace('__GENERATEGSLST2__', $xml, $this->_xml);
    }

    /**
     * Generate a:filltorect
     *
     * @access protected
     * @param string $l
     * @param string $t
     * @param string $r
     * @param string $b
     */
    protected function generateFILLTORECT($l, $t, $r, $b)
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':fillToRect l="' . $l . '" t="' . $t . '" r="' . $r .
            '" b="' . $b . '"></' . CreateTheme1::NAMESPACEWORD .
            ':fillToRect>';
        $this->_xml = str_replace('__GENERATEPATH__', $xml, $this->_xml);
    }

    /**
     * Generate a:objectdefaults
     *
     * @access protected
     */
    protected function generateOBJECTDEFAULTS()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':objectDefaults></' . CreateTheme1::NAMESPACEWORD .
            ':objectDefaults>__GENERATETHEMEELEMENTS4__';

        $this->_xml = str_replace(
            '__GENERATETHEMEELEMENTS4__',
            $xml,
            $this->_xml
        );
    }

    /**
     * Generate a:extraclrschemelst
     *
     * @access protected
     */
    protected function generateEXTRACLRSCHEMELST()
    {
        $xml = '<' . CreateTheme1::NAMESPACEWORD .
            ':extraClrSchemeLst></' . CreateTheme1::NAMESPACEWORD .
            ':extraClrSchemeLst>';

        $this->_xml = str_replace(
            '__GENERATETHEMEELEMENTS4__',
            $xml,
            $this->_xml
        );
    }

}