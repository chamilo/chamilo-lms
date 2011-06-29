<?php

/**
 * Create images
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
 * Create images
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
class CreateImage extends CreateElement
{
    const NAMESPACEWORD = 'wp';
    const NAMESPACEWORD1 = 'a';
    const NAMESPACEWORD2 = 'pic';
    const CONSTWORD = 360000;
    const TAMBORDER = 12700;

    /**
     * @access private
     * @var CreateImage
     * @static
     */
    private static $_instance = NULL;
    /**
     *
     * @access private
     * @var string
     */
    private $_name;
    /**
     *
     * @access private
     * @var int
     */
    private $_rId;
    /**
     *
     * @access private
     * @var string
     */
    private $_ajusteTexto;
    /**
     *
     * @access private
     * @var int
     */
    private $_sizeX;
    /**
     *
     * @access private
     * @var int
     */
    private $_sizeY;
    /**
     *
     * @access private
     * @var int
     */
    private $_spacingTop;
    /**
     *
     * @access private
     * @var int
     */
    private $_spacingBottom;
    /**
     *
     * @access private
     * @var int
     */
    private $_spacingLeft;
    /**
     *
     * @access private
     * @var int
     */
    private $_spacingRight;
    /**
     *
     * @access private
     * @var int
     */
    private $_jc;
    /**
     *
     * @access private
     * @var string
     */
    private $_border;
    /**
     *
     * @access private
     * @var string
     */
    private $_borderDiscontinuo;
    /**
     *
     * @access private
     * @var int
     */
    private $_scaling;

    /**
     * Construct
     *
     * @access public
     */
    public function __construct()
    {
        $this->_name = '';
        $this->_rId = '';
        $this->_ajusteTexto = '';
        $this->_sizeX = '';
        $this->_sizeY = '';
        $this->_spacingTop = '';
        $this->_spacingBottom = '';
        $this->_spacingLeft = '';
        $this->_spacingRight = '';
        $this->_jc = '';
        $this->_border = '';
        $this->_borderDiscontinuo = '';
        $this->_scaling = '';
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
     * @return CreateImage
     * @access public
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateImage();
        }
        return self::$_instance;
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
     * @return <type>
     */
    public function getName()
    {
        return $this->_name;
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
     * @return <type>
     */
    public function getRId()
    {
        return $this->_rId;
    }

    /**
     * Create image
     *
     * @access public
     * @param array $args[0]
     */
    public function createImage()
    {
        $this->_xml = '';
        $this->_name = '';
        $this->_rId = '';
        $args = func_get_args();
        if (!empty($args[0]['rId']) && (!empty($args[0]['name']))) {
            $arr = array();
            $atributos = getimagesize($args[0]['name']);

            $ajusteTexto = 0;

            if (!empty($args[0]['sizeX'])) {
                $tamPxX = $args[0]['sizeX'];
            } elseif (!empty($args[0]['scaling'])) {
                $tamPxX = $atributos[0] * $args[0]['scaling'] / 100;
            } else {
                $tamPxX = $atributos[0];
            }

            if (!empty($args[0]['scaling'])) {
                $tamPxY = $atributos[1] * $args[0]['scaling'] / 100;
            } elseif (!empty($args[0]['sizeY'])) {
                $tamPxY = $args[0]['sizeY'];
            } else {
                $tamPxY = $atributos[1];
            }

            $this->setName($args[0]['name']);
            $this->setRId($args[0]['rId']);
            $top = '0';
            $bottom = '0';
            $left = '0';
            $right = '0';

            switch ($atributos['mime']) {
                case 'image/png':
                    list($dpiX, $dpiY) =
                        $this->get_dpi_png($args[0]['name']);
                    $tamWordX =
                        round($tamPxX * 100 / $dpiX * CreateImage::CONSTWORD);
                    $tamWordY =
                        round($tamPxY * 100 / $dpiY * CreateImage::CONSTWORD);
                    break;
                case 'image/jpg':
                case 'image/jpeg':
                    list($dpiX, $dpiY) = $this->get_dpi_jpg($args[0]['name']);
                    $tamWordX = round(
                        $tamPxX * 2.54 /
                        $dpiX * CreateImage::CONSTWORD
                    );
                    $tamWordY = round(
                        $tamPxY * 2.54 /
                        $dpiY * CreateImage::CONSTWORD
                    );
                    break;
                case 'image/gif':
                    $tamWordX = round(
                        $tamPxX * 2.54 /
                        96 * CreateImage::CONSTWORD
                    );
                    $tamWordY = round(
                        $tamPxY * 2.54 /
                        96 * CreateImage::CONSTWORD
                    );
                    break;
            }
            $align = '';
            $this->generateP();
            $this->generateR();
            $this->generateRPR();
            $this->generateNOPROOF();
            $this->generateDRAWING();
            $this->generateINLINE();
            $this->generateEXTENT($tamWordX, $tamWordY);
            $this->generateEFFECTEXTENT($left, $top, $right, $bottom);
            $this->generateDOCPR();
            $this->generateCNVGRAPHICFRAMEPR();
            $this->generateGRAPHICPRAMELOCKS(1);
            $this->generateGRAPHIC();
            $this->generateGRAPHICDATA();
            $this->generatePIC();
            $this->generateNVPICPR();
            $this->generateCNVPR();
            $this->generateCNVPICPR();
            $this->generateBLIPFILL();
            $this->generateBLIP();
            $this->generateSTRETCH();
            $this->generateFILLRECT();
            $this->generateSPPR();
            $this->generateXFRM();
            $this->generateOFF();
            $this->generateEXT($tamWordX, $tamWordY);
            $this->generatePRSTGEOM();
            $this->generateAVLST();
            $this->cleanTemplate();
        } else {
            echo 'There was an error adding the image';
        }
    }

    /**
     * Get image jpg dpi
     *
     * @access private
     * @param string $filename
     * @return array
     */
    private function get_dpi_jpg($filename)
    {
        $a = fopen($filename, 'r');
        $string = fread($a, 20);
        fclose($a);
        $data = bin2hex(substr($string, 14, 4));
        $x = substr($data, 0, 4);
        $y = substr($data, 4, 4);
        return array(hexdec($x), hexdec($y));
    }

    /**
     * Get image png dpi
     *
     * @access private
     * @param string $filename
     * @return array
     */
    private function get_dpi_png($filename)
    {
        $a = fopen($filename, 'r');
        $string = fread($a, 1000);
        $aux = strpos($string, 'pHYs');
        $data = bin2hex(substr($string, $aux + strlen('pHYs'), 16));
        fclose($a);
        $x = substr($data, 0, 8);
        $y = substr($data, 8, 8);
        return array(hexdec($x), hexdec($y));
    }
}
