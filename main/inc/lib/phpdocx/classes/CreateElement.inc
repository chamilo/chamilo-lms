<?php

/**
 * Create tag elements
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

/**
 * Main class, create tag elements
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
class CreateElement
{
    const MATHNAMESPACEWORD = 'm';
    const NAMESPACEWORD = 'w';

    /**
     *
     * @var string
     * @access protected
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

    }

    /**
     * Generate w:p
     *
     * @access protected
     */
    protected function generateP()
    {
        $this->_xml = '<' . CreateElement::NAMESPACEWORD .
                ':p>__GENERATEP__</' . CreateElement::NAMESPACEWORD .
                ':p>';
    }

    /**
     * Generate w:ppr
     *
     * @access protected
     */
    protected function generatePPR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD . ':pPr>__GENERATEPPR__</' .
                CreateElement::NAMESPACEWORD . ':pPr>__GENERATER__';

        $this->_xml = str_replace('__GENERATEP__', $xml, $this->_xml);
    }

    /**
     * Generate w:t
     *
     * @param string $dat
     * @access protected
     */
    protected function generateT($dat)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD . ':t xml:space="preserve">' .
                $dat . '</' . CreateElement::NAMESPACEWORD . ':t>';

        $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
    }

    /**
     * Generate w:rpr
     *
     * @access protected
     */
    protected function generateRPR()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':rPr>__GENERATERPR__</' . CreateElement::NAMESPACEWORD .
                ':rPr>__GENERATER__';

        $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
    }

    /**
     * Generate w:jc
     *
     * @param string $val
     * @access protected
     */
    protected function generateJC($val = '')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':jc ' . CreateElement::NAMESPACEWORD . ':val="' . $val .
                '"></' . CreateElement::NAMESPACEWORD . ':jc>';

        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
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
     * Generate w:noproof
     *
     * @access protected
     */
    protected function generateNOPROOF()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':noProof></' . CreateElement::NAMESPACEWORD .
                ':noProof>__GENERATEPPR__';

        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
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
                        ':r>';
                $this->_xml = str_replace('__GENERATEP__', $xml, $this->_xml);
            } elseif (preg_match("/__GENERATER__/", $this->_xml)) {
                $xml = '<' . CreateElement::NAMESPACEWORD .
                        ':r>__GENERATER__</' . CreateElement::NAMESPACEWORD .
                        ':r>';
                $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
            }
        } else {
            $this->_xml = '<' . CreateElement::NAMESPACEWORD .
                    ':r>__GENERATER__</' . CreateElement::NAMESPACEWORD .
                    ':r>__GENERATER__';
        }
    }

    /**
     * Generate w:drawing
     *
     * @access protected
     */
    protected function generateDRAWING()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD .
                ':drawing>__GENERATEDRAWING__</' .
                CreateElement::NAMESPACEWORD .
                ':drawing>';

        $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
    }

    /**
     * Generate w:inline
     *
     * @param string $distT
     * @param string $distB
     * @param string $distL
     * @param string $distR
     * @access protected
     */
    protected function generateINLINE($distT = '0', $distB ='0',
                                      $distL = '0', $distR ='0')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD .
                ':inline distT="' . $distT . '" distB="' . $distB .
                '" distL="' . $distL . '" distR="' . $distR .
                '">__GENERATEINLINE__</' . CreateImage::NAMESPACEWORD .
                ':inline>';

        $this->_xml = str_replace('__GENERATEDRAWING__', $xml, $this->_xml);
    }

    /**
     * Generate w:anchor
     *
     * @param string $behindDoc
     * @param string $distT
     * @param string $distB
     * @param string $distL
     * @param string $distR
     * @param int $simplePos
     * @param string $relativeHeight
     * @param string $locked
     * @param string $layoutInCell
     * @param string $allowOverlap
     * @access protected
     */
    protected function generateANCHOR($behindDoc = 0, $distT = '0',
                                      $distB = '0', $distL = '114300',
                                      $distR = '114300', $simplePos = 0,
                                      $relativeHeight = '251658240',
                                      $locked = 0, $layoutInCell = 1,
                                      $allowOverlap = 1)
    {
        $xml = '<' . CreateImage::NAMESPACEWORD . ':anchor distT="' . $distT .
                '" distB="' . $distB . '" distL="' . $distL .
                '" distR="' . $distR . '" simplePos="' . $simplePos .
                '" relativeHeight="' . $relativeHeight .
                '" behindDoc="' . $behindDoc .
                '" locked="' . $locked .
                '" layoutInCell="' . $layoutInCell .
                '" allowOverlap="' . $allowOverlap .
                '">__GENERATEINLINE__</' . CreateImage::NAMESPACEWORD .
                ':anchor>';

        $this->_xml = str_replace('__GENERATEDRAWING__', $xml, $this->_xml);
    }

    /**
     * Generate w:simplepos
     *
     * @param string $x
     * @param string $y
     * @access protected
     */
    protected function generateSIMPLEPOS($x = '0', $y = '0')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD .
                ':simplePos x="' . $x . '" y="' . $y .
                '"></' . CreateImage::NAMESPACEWORD .
                ':simplePos>__GENERATEINLINE__';

        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:positionh
     *
     * @param string $relativeFrom
     * @access protected
     */
    protected function generatePOSITIONH($relativeFrom = 'column')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD .
                ':positionH relativeFrom="' . $relativeFrom .
                '">__GENERATEPOSITION__</' . CreateImage::NAMESPACEWORD .
                ':positionH>__GENERATEINLINE__';

        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:positionv
     *
     * @param string $relativeFrom
     * @access protected
     */
    protected function generatePOSITIONV($relativeFrom = 'paragraph')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD .
                ':positionV relativeFrom="' . $relativeFrom .
                '">__GENERATEPOSITION__</' . CreateImage::NAMESPACEWORD .
                ':positionV>__GENERATEINLINE__';

        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:posoffset
     *
     * @param int $num
     * @access protected
     */
    protected function generatePOSOFFSET($num)
    {
        $xml = '<' . CreateImage::NAMESPACEWORD . ':posOffset>' . $num . 
                '</' . CreateImage::NAMESPACEWORD . ':posOffset>';
        
        $this->_xml = str_replace('__GENERATEPOSITION__', $xml, $this->_xml);
    }

    /**
     * Generate w:extent
     *
     * @param string $cx
     * @param string $cy
     * @access protected
     */
    protected function generateEXTENT($cx = '2986543', $cy = '2239906')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD . ':extent cx="' . $cx .
                '" cy="' . $cy . '"></' . CreateImage::NAMESPACEWORD .
                ':extent>__GENERATEINLINE__';

        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:effectextent
     *
     * @param string $l
     * @param string $t
     * @param string $r
     * @param string $b
     * @access protected
     */
    protected function generateEFFECTEXTENT($l = "19050", $t = "0",
                                            $r = "4307", $b = "0")
    {
        $xml = '<' . CreateImage::NAMESPACEWORD . ':effectExtent l="' . $l . 
                '" t="' . $t . '" r="' . $r . '" b="' . $b .
                '"></' . CreateImage::NAMESPACEWORD .
                ':effectExtent>__GENERATEINLINE__';
        
        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:wrapsquare
     *
     * @param string $wrapText
     * @access protected
     */
    protected function generateWRAPSQUARE($wrapText = "bothSides")
    {
        $xml = '<' . CreateImage::NAMESPACEWORD . 
                ':wrapSquare wrapText="' . $wrapText .
                '"></' . CreateImage::NAMESPACEWORD .
                ':wrapSquare>__GENERATEINLINE__';
        
        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:wrapnone
     *
     * @access protected
     */
    protected function generateWRAPNONE()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD .
                ':wrapNone></' . CreateImage::NAMESPACEWORD .
                ':wrapNone>__GENERATEINLINE__';
        
        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:wraptopandbottom
     *
     * @access protected
     */
    protected function generateWRAPTOPANDBOTTOM()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD .
                ':wrapTopAndBottom></' . CreateImage::NAMESPACEWORD .
                ':wrapTopAndBottom>__GENERATEINLINE__';
        
        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:wrapthrough
     *
     * @param string $wrapText
     * @access protected
     */
    protected function generateWRAPTHROUGH($wrapText = 'bothSides')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD . 
                ':wrapThrough wrapText="' . $wrapText .
                '">__GENERATEWRAPTHROUGH__</' . CreateImage::NAMESPACEWORD .
                ':wrapThrough>__GENERATEINLINE__';

        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:wrappolygon
     *
     * @param string $edited
     * @access protected
     */
    protected function generateWRAPPOLYGON($edited = '0')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD . 
                ':wrapPolygon edited="' . $edited .
                '">__GENERATEWRAPPOLYGON__</' . CreateImage::NAMESPACEWORD .
                ':wrapPolygon>';
        
        $this->_xml = str_replace('__GENERATEWRAPTHROUGH__', $xml, $this->_xml);
    }

    /**
     * Generate w:start
     *
     * @param string $x
     * @param string $y
     * @access protected
     */
    protected function generateSTART($x = '-198', $y = '0')
    {

        $xml = '<' . CreateImage::NAMESPACEWORD . 
                ':start x="' . $x . '" y="' . $y .
                '"></' . CreateImage::NAMESPACEWORD .
                ':start>__GENERATEWRAPPOLYGON__';
        
        $this->_xml = str_replace('__GENERATEWRAPPOLYGON__', $xml, $this->_xml);
    }

    /**
     * Generate w:lineto
     *
     * @param string $x
     * @param string $y
     * @access protected
     */
    protected function generateLINETO($x = '-198', $y = '21342')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD . 
                ':lineTo x="' . $x . '" y="' . $y .
                '"></' . CreateImage::NAMESPACEWORD .
                ':lineTo>__GENERATEWRAPPOLYGON__';
        
        $this->_xml = str_replace('__GENERATEWRAPPOLYGON__', $xml, $this->_xml);
    }

    /**
     * Generate w:docpr
     *
     * @param string $id
     * @param string $name
     * @access protected
     */
    protected function generateDOCPR($id = "1", $name = "0 Imagen")
    {
        $xml = '<' . CreateImage::NAMESPACEWORD . ':docPr id="' . $id . 
                '" name="' . $name . '" descr="' . $this->getName() .
                '"></' . CreateImage::NAMESPACEWORD .
                ':docPr>__GENERATEINLINE__';
        
        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:cnvgraphicframepr
     *
     * @access protected
     */
    protected function generateCNVGRAPHICFRAMEPR()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD . 
                ':cNvGraphicFramePr>__GENERATECNVGRAPHICFRAMEPR__</' .
                CreateImage::NAMESPACEWORD .
                ':cNvGraphicFramePr>__GENERATEINLINE__';
        
        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:graphicpframelocks
     *
     * @param string $noChangeAspect
     * @access protected
     */
    protected function generateGRAPHICPRAMELOCKS($noChangeAspect = '')
    {
        $xmlAux = '<' . CreateImage::NAMESPACEWORD1 . 
                    ':graphicFrameLocks xmlns:a="' .
                    'http://schemas.openxmlformats.org/drawingml/2006/main"';

        if ($noChangeAspect != '')
            $xmlAux .= ' noChangeAspect="' . $noChangeAspect . '"';
        $xmlAux .= '></' . CreateImage::NAMESPACEWORD1 . ':graphicFrameLocks>';

        $this->_xml = str_replace(
            '__GENERATECNVGRAPHICFRAMEPR__', $xmlAux, $this->_xml
        );
    }

    /**
     * Generate w:graphic
     *
     * @param string $xmlns
     * @access protected
     */
    protected function generateGRAPHIC(
        $xmlns = 'http://schemas.openxmlformats.org/drawingml/2006/main')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
                ':graphic xmlns:a="' . $xmlns .
                '">__GENERATEGRAPHIC__</' . CreateImage::NAMESPACEWORD1 .
                ':graphic>';
        
        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:graphicdata
     *
     * @param string $uri
     * @access protected
     */
    protected function generateGRAPHICDATA(
        $uri = 'http://schemas.openxmlformats.org/drawingml/2006/picture')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . 
                ':graphicData uri="' . $uri .
                '">__GENERATEGRAPHICDATA__</' . CreateImage::NAMESPACEWORD1 .
                ':graphicData>';

        $this->_xml = str_replace('__GENERATEGRAPHIC__', $xml, $this->_xml);
    }

    /**
     * Generate w:pic
     *
     * @param string $pic
     * @access protected
     */
    protected function generatePIC(
        $pic = 'http://schemas.openxmlformats.org/drawingml/2006/picture')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD2 . 
                ':pic xmlns:pic="' . $pic .
                '">__GENERATEPIC__</' . CreateImage::NAMESPACEWORD2 .
                ':pic>';

        $this->_xml = str_replace('__GENERATEGRAPHICDATA__', $xml, $this->_xml);
    }

    /**
     * Generate w:nvpicpr
     *
     * @access protected
     */
    protected function generateNVPICPR()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD2 .
                ':nvPicPr>__GENERATENVPICPR__</' . CreateImage::NAMESPACEWORD2 .
                ':nvPicPr>__GENERATEPIC__';

        $this->_xml = str_replace('__GENERATEPIC__', $xml, $this->_xml);
    }

    /**
     * Generate w:cnvpr
     *
     * @param string $id
     * @access protected
     */
    protected function generateCNVPR($id = '0')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD2 .
                ':cNvPr id="' . $id . '" name="' . $this->getName() .
                '"></' . CreateImage::NAMESPACEWORD2 .
                ':cNvPr>__GENERATENVPICPR__';

        $this->_xml = str_replace('__GENERATENVPICPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:cnvpicpr
     *
     * @access protected
     */
    protected function generateCNVPICPR()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD2 .
                ':cNvPicPr></' . CreateImage::NAMESPACEWORD2 .
                ':cNvPicPr>__GENERATENVPICPR__';

        $this->_xml = str_replace('__GENERATENVPICPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:blipfill
     *
     * @access protected
     */
    protected function generateBLIPFILL()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD2 .
                ':blipFill>__GENERATEBLIPFILL__</' .
                CreateImage::NAMESPACEWORD2 . ':blipFill>__GENERATEPIC__';

        $this->_xml = str_replace('__GENERATEPIC__', $xml, $this->_xml);
    }

    /**
     * Generate w:blip
     *
     * @param string $cstate
     * @access protected
     */
    protected function generateBLIP($cstate = 'print')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
                ':blip r:embed="rId' . $this->getRId() .
                '" cstate="' . $cstate .
                '"></' . CreateImage::NAMESPACEWORD1 .
                ':blip>__GENERATEBLIPFILL__';
        
        $this->_xml = str_replace('__GENERATEBLIPFILL__', $xml, $this->_xml);
    }

    /**
     * Generate w:stretch
     *
     * @access protected
     */
    protected function generateSTRETCH()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
                ':stretch>__GENERATESTRETCH__</' . CreateImage::NAMESPACEWORD1 .
                ':stretch>__GENERATEBLIPFILL__';

        $this->_xml = str_replace('__GENERATEBLIPFILL__', $xml, $this->_xml);
    }

    /**
     * Generate w:fillrect
     *
     * @access protected
     */
    protected function generateFILLRECT()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
                ':fillRect></' . CreateImage::NAMESPACEWORD1 .
                ':fillRect>';

        $this->_xml = str_replace('__GENERATESTRETCH__', $xml, $this->_xml);
    }

    /**
     * Generate w:sppr
     *
     * @access protected
     */
    protected function generateSPPR()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD2 .
                ':spPr>__GENERATESPPR__</' . CreateImage::NAMESPACEWORD2 .
                ':spPr>';
        
        $this->_xml = str_replace('__GENERATEPIC__', $xml, $this->_xml);
    }

    /**
     * Generate w:xfrm
     *
     * @access protected
     */
    protected function generateXFRM()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
                ':xfrm>__GENERATEXFRM__</' . CreateImage::NAMESPACEWORD1 .
                ':xfrm>__GENERATESPPR__';

        $this->_xml = str_replace('__GENERATESPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:off
     *
     * @param string $x
     * @param string $y
     * @access protected
     */
    protected function generateOFF($x = '0', $y = '0')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . 
                ':off x="' . $x . '" y="' . $y .
                '"></' . CreateImage::NAMESPACEWORD1 .
                ':off>__GENERATEXFRM__';

        $this->_xml = str_replace('__GENERATEXFRM__', $xml, $this->_xml);
    }

    /**
     * Generate w:ext
     *
     * @param string $cx
     * @param string $cy
     * @access protected
     */
    protected function generateEXT($cx = '2997226', $cy = '2247918')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . 
                ':ext cx="' . $cx . '" cy="' . $cy .
                '"></' . CreateImage::NAMESPACEWORD1 .
                ':ext>__GENERATEXFRM__';
        
        $this->_xml = str_replace('__GENERATEXFRM__', $xml, $this->_xml);
    }

    /**
     * Generate w:prstgeom
     *
     * @param string $prst
     * @access protected
     */
    protected function generatePRSTGEOM($prst = 'rect')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . 
                ':prstGeom prst="' . $prst .
                '">__GENERATEPRSTGEOM__</' . CreateImage::NAMESPACEWORD1 .
                ':prstGeom>__GENERATESPPR__';

        $this->_xml = str_replace('__GENERATESPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:avlst
     *
     * @access protected
     */
    protected function generateAVLST()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
                ':avLst></' . CreateImage::NAMESPACEWORD1 .
                ':avLst>__GENERATEPRSTGEOM__';

        $this->_xml = str_replace('__GENERATEPRSTGEOM__', $xml, $this->_xml);
    }

    /**
     * Generate w:ln
     *
     * @param string $w
     * @access protected
     */
    protected function generateLN($w = '12700')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
                ':ln w="' . $w . '">__GENERATELN__</' .
                CreateImage::NAMESPACEWORD1 .
                ':ln>__GENERATESPPR__';

        $this->_xml = str_replace('__GENERATESPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:solidfill
     *
     * @access protected
     */
    protected function generateSOLIDFILL()
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
                ':solidFill>__GENERATESOLIDFILL__</' .
                CreateImage::NAMESPACEWORD1 .
                ':solidFill>__GENERATELN__';
        $this->_xml = str_replace('__GENERATELN__', $xml, $this->_xml);
    }

    /**
     * Generate w:schemeclr
     *
     * @param string $val
     * @access protected
     */
    protected function generateSCHEMECLR($val = 'tx1')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . ':schemeClr val="' . $val .
                '"></' . CreateImage::NAMESPACEWORD1 . ':schemeClr>';
        
        $this->_xml = str_replace('__GENERATESOLIDFILL__', $xml, $this->_xml);
    }

    /**
     * Generate w:prstdash
     *
     * @param string $val
     * @access protected
     */
    protected function generatePRSTDASH($val = 'sysDash')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 . ':prstDash val="' . $val .
                '"></' . CreateImage::NAMESPACEWORD1 .
                ':prstDash>__GENERATELN__';
        
        $this->_xml = str_replace('__GENERATELN__', $xml, $this->_xml);
    }

    /**
     * Delete pending tags
     *
     * @access protected
     */
    protected function cleanTemplate()
    {
        $this->_xml = preg_replace('/__[A-Z]+__/', '', $this->_xml);
    }

    /**
     * Delete first w:rpr
     *
     * @access protected
     */
    protected function cleanTemplateFirstRPR()
    {
        $this->_xml = preg_replace('/__GENERATERPR__/', '', $this->_xml, 1);
    }

    /**
     * Generate w:r
     *
     * @access protected
     */
    protected function generateQUITAR()
    {
        $this->_xml = '<' . CreateElement::NAMESPACEWORD .
                      ':r>__GENERATER__</' . CreateElement::NAMESPACEWORD .
                      ':r>';
    }

    /**
     * Generate w:pstyle
     *
     * @param string $val
     * @access protected
     */
    protected function generatePSTYLE($val = 'Textonotaalfinal')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD . 
                ':pStyle ' . CreateElement::NAMESPACEWORD .
                ':val="' . $val . '"></' . CreateElement::NAMESPACEWORD .
                ':pStyle>';

        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:rstyle
     *
     * @param string $val
     * @access protected
     */
    protected function generateRSTYLE($val = 'Refdenotaalfinal')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD . 
                ':rStyle ' . CreateElement::NAMESPACEWORD .
                ':val="' . $val .
                '"></' . CreateElement::NAMESPACEWORD .
                ':rStyle>';
        
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:rfonts
     *
     * @param string $font
     * @access protected
     */
    protected function generateRFONTS($font)
    {
        $xml = '<' . CreateElement::NAMESPACEWORD . 
                ':rFonts ' . CreateElement::NAMESPACEWORD .
                ':ascii="' . $font . '" ' . CreateElement::NAMESPACEWORD .
                ':hAnsi="' . $font . '" ' . CreateElement::NAMESPACEWORD .
                ':cs="' . $font . '"></' . CreateElement::NAMESPACEWORD .
                ':rFonts>__GENERATERPR__';
        
        $this->_xml = str_replace('__GENERATERPR__', $xml, $this->_xml);
    }

}
