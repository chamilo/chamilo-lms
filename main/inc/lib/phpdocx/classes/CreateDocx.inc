<?php

/**
 * Generate a DOCX file
 *
 * @category   Phpdocx
 * @package    create
 * @copyright  Copyright (c) 2009-2011 Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    LGPL
 * @version    1.0
 * @link       http://www.phpdocx.com
 * @since      File available since Release 1.0
 */

error_reporting(E_ALL & ~E_NOTICE);

require_once dirname(__FILE__) . '/AutoLoader.inc';
AutoLoader::load();

/**
 * Main class. Methods and vars to generate a DOCX file
 *
 * @category   Phpdocx
 * @package    create
 * @copyright  Copyright (c) 2009-2011 Narcea ProduCiones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    http://www.phpdocx.com/wp-content/themes/lightword/pro_license.php
 * @version    1.0
 * @link       http://www.phpdocx.com
 * @since      Class available since Release 1.0
 */
class CreateDocx
{
    const NAMESPACEWORD = 'w';
    const SCHEMA_IMAGEDOCUMENT =
    'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';
    const SCHEMA_OFFICEDOCUMENT =
    'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument';

    /**
     *
     * @access public
     * @static
     * @var int
     */
    public static $intIdWord;
    /**
     *
     * @access public
     * @var string
     */
    public $graphicTemplate;
    /**
     *
     * @access public
     * @var array
     */
    public $fileGraphicTemplate;
    /**
     *
     * @access public
     * @static
     * @var Logger
     */
    public static $log;
    /**
     *
     * @access private
     * @var string
     */
    private $_contentTypeC;
    /**
     *
     * @access private
     * @var string
     */
    private $_defaultFont;
    /**
     *
     * @access private
     * @var string
     */
    private $_docPropsAppC;
    /**
     *
     * @access private
     * @var string
     */
    private $_docPropsAppT;
    /**
     *
     * @access private
     * @var string
     */
    private $_docPropsCoreC;
    /**
     *
     * @access private
     * @var string
     */
    private $_docPropsCoreT;
    /**
     *
     * @access private
     * @var string
     */
    private $_docPropsCustomC;
    /**
     *
     * @access private
     * @var string
     */
    private $_docPropsCustomT;
    /**
     *
     * @access private
     * @var string
     */
    private static $_encodeUTF;
    /**
     *
     * @access private
     * @var string
     */
    private $_extension;
    /**
     *
     * @access private
     * @var int
     */
    private $_idImgHeader;
    /**
     *
     * @access private
     * @var int
     */
    private $_idRels;
    /**
     *
     * @access private
     * @var array
     */
    private $_idWords;
    /**
     *
     * @access private
     * @var string
     */
    private $_language;
    /**
     *
     * @access private
     * @var boolean
     */
    private $_macro;
    /**
     *
     * @access private
     * @var string
     */
    private $_relsRelsC;
    /**
     *
     * @access private
     * @var string
     */
    private $_relsRelsT;
    /**
     * Path of temp file to use as DOCX file
     *
     * @access private
     * @var string
     */
    private $_tempFile;
    /**
     * Paths of temps files to use as DOCX file
     *
     * @access private
     * @var array
     */
    private $_tempFileXLSX;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordDocumentC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordDocumentT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordEndnotesC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordEndnotesT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordFontTableC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordFontTableT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordFooterC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordFooterT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordFootnotesC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordFootnotesT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordHeaderC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordHeaderT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordNumberingC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordNumberingT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordRelsDocumentRelsC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordRelsDocumentRelsT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordRelsFooterRelsC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordRelsFooterRelsT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordRelsHeaderRelsC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordRelsHeaderRelsT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordSettingsC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordSettingsT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordStylesC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordStylesT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordThemeThemeT;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordThemeThemeC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordWebSettingsC;
    /**
     *
     * @access private
     * @var string
     */
    private $_wordWebSettingsT;
    /**
     *
     * @access private
     * @var ZipArchive
     */
    private $_zipDocx;

    /**
     * Construct
     *
     * @access public
     * @param string $extension File extension. Optional, docx as default
     */
    public function __construct($extension = 'docx')
    {
        $this->_idRels = array();
        $this->_idWords = array();
        Logger::configure(dirname(__FILE__) . '/conf/log4php.properties');
        self::$log = Logger::getLogger('phpdocx_error');
        $this->_idImgHeader = 1;
        $this->_idRels = 1;
        self::$intIdWord = 0;
        self::$_encodeUTF = 0;
        $this->_language = 'en-US';
        $this->graphicTemplate = array();
        $this->fileGraphicTemplate = array();
        $this->_zipDocx = new ZipArchive();
        $this->_tempFile = tempnam(sys_get_temp_dir(), 'document');
        $this->_zipDocx->open($this->_tempFile, ZipArchive::OVERWRITE);
        $this->_extension = $extension;
        $this->_relsRelsC = '';
        $this->_relsRelsT = '';
        $this->_contentTypeC = '';
        $this->_docPropsAppC = '';
        $this->_docPropsAppT = '';
        $this->_docPropsCoreC = '';
        $this->_docPropsCoreT = '';
        $this->_docPropsCustomC = '';
        $this->_docPropsCustomT = '';
        $this->_tempFileXLSX = array();
        $this->_wordDocumentT = '';
        $this->_wordDocumentC = '';
        $this->_wordEndnotesC = '';
        $this->_wordEndnotesT = '';
        $this->_wordFontTableT = '';
        $this->_wordFontTableC = '';
        $this->_wordFooterC = '';
        $this->_wordFooterT = '';
        $this->_wordFootnotesC = '';
        $this->_wordFootnotesT = '';
        $this->_wordHeaderC = '';
        $this->_wordHeaderT = '';
        $this->_wordNumberingC;
        $this->_wordNumberingT;
        $this->_wordRelsDocumentRelsC = '';
        $this->_wordRelsDocumentRelsT = '';
        $this->_wordRelsHeaderRelsC = '';
        $this->_wordRelsHeaderRelsT = '';
        $this->_wordRelsFooterRelsC = '';
        $this->_wordRelsFooterRelsT = '';
        $this->_xmlWordSettings = '';
        $this->_wordSettingsT = '';
        $this->_wordSettingsC = '';
        $this->_xmlWordStyles = '';
        $this->_wordStylesT = '';
        $this->_wordStylesC = '';
        $this->_wordThemeThemeT = '';
        $this->_wordThemeThemeC = '';
        $this->_macro = 0;
        $this->_xmlWordWebSettings = '';
        $this->generateContentType();
        $this->_defaultFont = '';
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
     * Magic method, returns current word XML
     *
     * @access public
     * @return string Return current word
     */
    public function __toString()
    {
        $this->generateTemplateWordDocument();
        return $this->_wordDocumentT;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlContentTypes($xmlContentTypes)
    {
        $this->_contentTypeC = $xmlContentTypes;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlContentTypes()
    {
        return $this->_contentTypeC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlRelsRels($xmlRelsRels)
    {
        $this->_relsRelsC = $xmlRelsRels;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlRels_Rels()
    {
        return $this->_relsRelsC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlDocPropsApp($xmlDocPropsApp)
    {
        $this->_docPropsAppC = $xmlDocPropsApp;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlDocPropsApp()
    {
        return $this->_docPropsAppC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlDocPropsCore($xmlDocPropsCore)
    {
        $this->_docPropsCoreC = $xmlDocPropsCore;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlDocPropsCore()
    {
        return $this->_docPropsCoreC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlWordDocument($xmlWordDocument)
    {
        $this->_wordDocumentC = $xmlWordDocument;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlWordDocumentContent()
    {
        return $this->_wordDocumentC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlWordEndnotes($xmlWordEndnotes)
    {
        $this->_wordEndnotesC = $xmlWordEndnotes;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlWordEndnotes()
    {
        return $this->_wordEndnotesC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlWordFontTable($xmlWordFontTable)
    {
        $this->_wordFontTableC = $xmlWordFontTable;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlWordFontTable()
    {
        return $this->_wordFontTableC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlWordFooter1($xmlWordFooter)
    {
        $this->_wordFooterC = $xmlWordFooter;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlWordFooter1()
    {
        return $this->_wordFooterC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlWordHeader1($xmlWordHeader)
    {
        $this->_wordHeaderC = $xmlWordHeader;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlWordHeader1()
    {
        return $this->_wordHeaderC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlWordRelsDocumentRels($xmlWordRelsDocumentRels)
    {
        $this->_wordRelsDocumentRelsC = $xmlWordRelsDocumentRels;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlWordRelsDocumentRels()
    {
        return $this->_wordRelsDocumentRelsC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlWordSettings($xmlWordSettings)
    {
        $this->_wordSettingsC = $xmlWordSettings;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlWordSettings()
    {
        return $this->_wordSettingsC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlWordStyles($xmlWordStyles)
    {
        $this->_wordStylesC = $xmlWordStyles;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlWordStyles()
    {
        return $this->_wordStylesC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlWordThemeTheme1($xmlWordThemeTheme)
    {
        $this->_wordThemeThemeC = $xmlWordThemeTheme;
    }

    /**
     * Getter
     *
     * @access public
     */
    public function getXmlWordThemeTheme1()
    {
        return $this->_wordThemeThemeC;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function setXmlWordWebSettings($xmlWordWebSettings)
    {
        $this->_wordWebSettingsC = $xmlWordWebSettings;
    }

    /**
     * Setter
     *
     * @access public
     */
    public function getXmlWordWebSettings()
    {
        return $this->_wordWebSettingsC;
    }

    /**
     * Add a break
     *
     * @access public
     * @param string $type Break type
     *  Values: 'line', 'page'
     */
    public function addBreak($type = '')
    {
        $page = CreatePage::getInstance();
        $page->generatePageBreak($type);
        $this->_wordDocumentC .= (string) $page;
    }

    /**
     * Add a new font
     *
     * @access public
     * @param array $fonts Fonts to add
     */
    public function addFont($fonts)
    {
        $font = CreateFontTable::getInstance();
        $font->createFont($fonts);
        $this->_wordFontTableC .= (string) $font;
    }

    /**
     * Add a footer
     *
     * @access public
     * @param string $dat Text to add
     * @param array $paramsFooter Parameters of footer
     *  Values: 'pager' (true, false), 'pagerAlignment' (left, right, false)
     */
    public function addFooter($dat = '', $paramsFooter = '')
    {
        $footer = CreateFooter::getInstance();
        $dat .= ' This document was created with free version of PHPdocx.
                Pro version available.';
        $footer->createFooter($dat, $paramsFooter);
        $this->_wordFooterC .= (string) $footer;
        $this->generateOVERRIDE(
            '/word/footer.xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' .
            '.footer+xml'
        );
    }

    /**
     * Add a graphic
     *
     * @access public
     * @param array $dats Parameters of graphic
     *  Values: 'color' (1, 2, 3...), 'cornerP' (20, 30...),
     *  'cornerX' (20, 30...), 'cornerY' (20, 30...), 'data' (array of values),
     *  'font' (Arial, Times New Roman...), 'groupBar' (clustered, stacked),
     *  'jc' (center, left, right), 'showPercent' (0, 1), 'sizeX' (10, 11,
     *   12...), 'sizeY' (10, 11, 12...), 'textWrap' (0 (inline), 1 (square),
     *  2 (front), 3 (back), 4 (up and bottom), 5 (clear)), 'title', 'type'
     *  (pieChart, barChart, colChart)
     */
    public function addGraphic($dats)
    {
        try {
            if (isset($dats['data']) && isset($dats['type'])) {
                self::$intIdWord++;
                $graphic = CreateGraphic::getInstance();
                if ($graphic->createGraphic(self::$intIdWord, $dats) != false) {
                    $this->_zipDocx->addFromString(
                        'word/charts/chart' . self::$intIdWord . '.xml',
                        $graphic->getXmlChart()
                    );
                    $this->_wordRelsDocumentRelsC .=
                        $this->generateRELATIONSHIP(
                            'rId' . self::$intIdWord, 'chart',
                            'charts/chart' . self::$intIdWord . '.xml'
                        );
                    $this->_wordDocumentC .= (string) $graphic;
                    $this->generateDEFAULT('xlsx', 'application/octet-stream');
                    $this->generateOVERRIDE(
                        '/word/charts/chart' . self::$intIdWord . '.xml',
                        'application/vnd.openxmlformats-officedocument.' .
                        'drawingml.chart+xml'
                    );
                } else {
                    throw new Exception(
                        'There was an error related to the chart.'
                    );
                }
                $excel = CreateXlsx::getInstance();
                $this->_tempFileXLSX[self::$intIdWord] = tempnam(sys_get_temp_dir(), 'documentxlsx');
                if (
                    $excel->createXlsx(
                        $this->_tempFileXLSX[self::$intIdWord],
                        $dats['data'], $dats['type']
                    ) != false
                ) {
                    $this->_zipDocx->addFile(
                        $this->_tempFileXLSX[self::$intIdWord],
                        'word/embeddings/datos' . self::$intIdWord . '.xlsx'
                    );

                    $chartRels = CreateChartRels::getInstance();
                    $chartRels->createRelationship(self::$intIdWord);
                    $this->_zipDocx->addFromString(
                        'word/charts/_rels/chart' . self::$intIdWord .
                        '.xml.rels',
                        (string) $chartRels
                    );
                }
            } else {
                throw new Exception(
                    'Images must have "data" and "type" values.'
                );
            }
        }
        catch (Exception $e) {
            self::$log->fatal($e->getMessage());
            exit();
        }
    }

    /**
     * Add a header.
     *
     * @access public
     * @param string $text Text to add
     * @param array $paramsHeader Parameters of header
     */
    public function addHeader($text = 'Header', $paramsHeader = '')
    {
        $header = CreateHeader::getInstance();
        $header->createHeader($text, $paramsHeader);
        $this->_wordHeaderC .= (string) $header;
        $this->generateOVERRIDE(
            '/word/header.xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.' .
            'header+xml'
        );
    }

    /**
     * Add an image
     *
     * @access public
     * @param array $dats Image to add and paramaters to use
     *  Values: 'border'(1, 2, 3...), 'borderDiscontinuous' (0, 1),
     *  'font' (Arial, Times New Roman...), 'jc' (center, left, right),
     *  'name', 'scaling' (50, 100), 'sizeX' (10, 11, 12...), 'sizeY'
     *  (10, 11, 12...), spacingTop (10, 11...), spacingBottom (10, 11...),
     *  spacingLeft (10, 11...), spacingRight (10, 11...), 'textWrap'
     *  (0 (inline), 1 (square), 2 (front), 3 (back), 4 (up and bottom),
     *  5 (clear))
     */
    public function addImage($dats = '')
    {
        try {
            if (isset($dats['name']) && file_exists($dats['name']) == 'true') {
                $attrImage = getimagesize($dats['name']);
                try {
                    if ($attrImage['mime'] == 'image/jpg' ||
                        $attrImage['mime'] == 'image/jpeg' ||
                        $attrImage['mime'] == 'image/png' ||
                        $attrImage['mime'] == 'image/gif'
                    ) {
                        self::$intIdWord++;
                        $image = CreateImage::getInstance();
                        $dats['rId'] = self::$intIdWord;
                        $image->createImage($dats);
                        $this->_wordDocumentC .= (string) $image;
                        $dir = $this->parsePath($dats['name']);
                        $this->_zipDocx->addFile(
                            $dats['name'], 'word/media/image' .
                            self::$intIdWord . '.' .
                            $dir['extension']
                        );
                        $this->generateDEFAULT(
                            $dir['extension'], $attrImage['mime']
                        );
                        if ((string) $image != '')
                            $this->_wordRelsDocumentRelsC .=
                                $this->generateRELATIONSHIP(
                                    'rId' . self::$intIdWord, 'image',
                                    'media/image' . self::$intIdWord . '.'
                                    . $dir['extension']
                                );
                    } else {
                        throw new Exception('Image format is not supported.');
                    }
                }
                catch (Exception $e) {
                    self::$log->fatal($e->getMessage());
                    exit();
                }
            } else {
                throw new Exception('Image does not exist.');
            }
        }
        catch (Exception $e) {
            self::$log->fatal($e->getMessage());
            exit();
        }
    }

    /**
     * Add a link
     *
     * @access public
     * @param string $text Text to use as link
     * @param string $link URL link
     * @param string $font Type of font
     *  Values: 'Arial', 'Times New Roman'...
     */
    public function addLink($text = '', $textLink = '', $font = '')
    {
        $link = CreateLink::getInstance();
        $link->createLink($text, $textLink, $font);
        $this->_wordDocumentC .= (string) $link;
    }

    /**
     * Add a list
     *
     * @access public
     * @param array $dats Values of the list
     * @param array $paramsList Parameters to use
     *  Values: 'font' (Arial, Times New Roman...),
     *  'val' (0 (clear, 1 (inordinate), 2(numerical))
     */
    public function addList($dats, $paramsList = '')
    {
        $list = CreateList::getInstance();
        $list->createList($dats, $paramsList);
        $this->_wordDocumentC .= (string) $list;
    }

    /**
     * Convert a math eq to DOCX
     *
     * @access public
     * @param string $path Path to a file with math eq
     */
    public function addMathDocx($path)
    {
        $package = new ZipArchive();
        $package->open($path);
        $document = $package->getFromName('word/document.xml');
        $eqs = preg_split('/<[\/]*m:oMathPara>/', $document);
        $this->addMathEq('<m:oMathPara>' . $eqs[1] . '</m:oMathPara>');
        $package->close();
    }

    /**
     * Add an existing math eq to DOCX
     *
     * @access public
     * @param string $eq Math eq
     */
    public function addMathEq($eq)
    {
        $this->_wordDocumentC .= '<' . CreateDocx::NAMESPACEWORD . ':p>' .
            (string) $eq . '</' . CreateDocx::NAMESPACEWORD . ':p>';
    }

    /**
     * Add a paragraph
     *
     * @access public
     * @param string $text Text to add
     * @param string $style Style of the paragraph
     * @param string $align Align of the paragraph
     */
    public function addParagraph($text, $style = '', $align = '')
    {
        $paragraph = CreateText::getInstance();
        $paragraph->createParagraph($text, $style, $align);
        $this->_wordDocumentC .= (string) $paragraph;
    }

    /**
     * Add a table.
     *
     * @access public
     * @param array $dats Values to add
     * @param array $parameters Parameters to use
     *  Values: 'border' (none, single, double),
     *  'border_color' (ffffff, ff0000), 'border_spacing' (0, 1, 2...),
     *  'border_sz' (10, 11...), 'font' (Arial, Times New Roman...),
     *  'jc' (center, left, right), 'size_col' (1200, 1300...),
     *  'TBLSTYLEval' (Cuadrculamedia3-nfasis1, Sombreadomedio1,
     *  Tablaconcuadrcula, TableGrid)
     */
    public function addTable($dats, $parameters = '')
    {
        $table = CreateTable::getInstance();
        $table->createTable($dats, $parameters);
        $this->_wordDocumentC .= (string) $table;
    }

    /**
     * Add a table of contents (TOC)
     *
     * @access public
     * @param string $font Set font type
     *  Values: 'Arial', 'Times New Roman'...
     */
    public function addTableContents($font = '')
    {
        $tableContents = CreateTableContents::getInstance();
        $tableContents->createTableContents($font);
        $this->_wordDocumentC .= (string) $tableContents;
    }

    /**
     * Add a text
     *
     * @access public
     * @param mixed $value Text or array of texts to add
     * @param array $style Style of text
     *  Values: 'b' (single), 'color' (ffffff, ff0000...),
     *  'font' (Arial, Times New Roman...), 'i' (single),
     *  'jc' (both, center, distribute, left, right),
     *  'pageBreakBefore' (on, off), 'sz' (1, 2, 3...),
     *  'u' (dash, dotted, double, single, wave, words),
     *  'widowControl' (on, off), 'wordWrap' (on, off)
     */
    public function addText($value, $style = '')
    {
        $text = CreateText::getInstance();
        $text->createText($value, $style);
        $this->_wordDocumentC .= (string) $text;
    }

    /**
     * Add a title
     *
     * @access public
     * @param string $text Text to add
     * @param array $style Style of title
     *  Values: 'b' (single), 'color' (ffffff, ff0000...),
     *  'font' (Arial, Times New Roman...), 'i' (single),
     *  'jc' (both, center, distribute, left, right),
     *  'pageBreakBefore' (on, off), 'sz' (1, 2, 3...),
     *  'u' (dash, dotted, double, single, wave, words),
     *  'widowControl' (on, off), 'wordWrap' (on, off)
     */
    public function addTitle($text, $style = '')
    {
        $title = CreateText::getInstance();
        $title->createTitle($text, $style);
        $this->_wordDocumentC .= (string) $title;
    }

    /**
     * Generate a new DOCX file
     *
     * @access public
     * @param string $args[0] File name
     * @param string $args[1] Page style
     *  Values: 'bottom' (4000, 4001...), 'left' (4000, 4001...),
     *  'orient' (landscape), 'right' (4000, 4001), 'titlePage' (1),
     *  'top' (4000, 4001)
     */
    public function createDocx()
    {
        $args = func_get_args();
        if (!empty($args[0])) {
            $fileName = $args[0];
        } else {
            $fileName = 'document';
        }
        $this->generateTemplateRelsRels();
        $this->_zipDocx->addFromString('_rels/.rels', $this->_relsRelsT);
        $this->generateTemplateDocPropsApp();
        $this->_zipDocx->addFromString(
            'docProps/app.xml', $this->_docPropsAppT
        );
        $this->generateTemplateDocPropsCore();
        $this->_zipDocx->addFromString(
            'docProps/core.xml', $this->_docPropsCoreT
        );

        $this->addStyle($this->_language);
        $this->generateTemplateWordStyles();
        $this->_zipDocx->addFromString(
            'word/styles.xml', $this->_wordStylesT
        );

        $this->addSettings();
        $this->generateTemplateWordSettings();
        $this->_zipDocx->addFromString(
            'word/settings.xml', $this->_wordSettingsT
        );

        $this->addWebSettings();
        $this->generateTemplateWordWebSettings();
        $this->_zipDocx->addFromString(
            'word/webSettings.xml', $this->_wordWebSettingsT
        );
        if (empty($this->_wordFooterC)) {
            $paramsFooter = array(
                    'pagerAlignment' => 'right',
                    'font' => 'Times New Roman'
                );
            $this->addFooter('', $paramsFooter);
        }
        $this->generateTemplateWordFooter();
        if (self::$_encodeUTF) {
            $this->_zipDocx->addFromString(
                'word/footer.xml', utf8_encode($this->_wordFooterT)
            );
        } else {
            $this->_zipDocx->addFromString(
                'word/footer.xml', $this->_wordFooterT
            );
        }

        if (!empty($this->_wordHeaderC)) {
            $this->generateTemplateWordHeader();
            if (self::$_encodeUTF) {
                $this->_zipDocx->addFromString(
                    'word/header.xml', utf8_encode($this->_wordHeaderT)
                );
            } else {
                $this->_zipDocx->addFromString(
                    'word/header.xml', $this->_wordHeaderT
                );
            }
        }
        if (!empty($this->_wordRelsHeaderRelsC)) {
            $this->generateTemplateWordRelsHeaderRels();
            $this->_zipDocx->addFromString(
                'word/_rels/header.xml.rels', $this->_wordRelsHeaderRelsT
            );
        }

        $this->generateOVERRIDE(
            '/word/document.xml',
            'application/vnd.openxmlformats-officedocument.' .
            'wordprocessingml.document.main+xml'
        );

        $this->generateTemplateContentType();
        $this->_zipDocx->addFromString(
            '[Content_Types].xml',
            $this->_wordContentTypeT
        );

        $this->generateTemplateWordNumbering();
        $this->_zipDocx->addFromString(
            'word/numbering.xml', $this->_wordNumberingT
        );

        $this->generateDefaultWordRels();
        if (!empty($this->_wordRelsDocumentRelsC)) {
            $this->generateTemplateWordRelsDocumentRels();
            $this->_zipDocx->addFromString(
                'word/_rels/document.xml.rels',
                $this->_wordRelsDocumentRelsT
            );
        }
        $arrArgsPage = array();
        if (isset($args[1])) {
            $arrArgsPage = $args[1];
        }
        $this->generateTemplateWordDocument($arrArgsPage);

        if (self::$_encodeUTF) {
            $this->_zipDocx->addFromString(
                'word/document.xml', utf8_encode($this->_wordDocumentT)
            );
        } else {
            $this->_zipDocx->addFromString(
                'word/document.xml', $this->_wordDocumentT
            );
        }

        $this->generateDefaultFonts();
        $this->generateTemplateWordFontTable();
        $this->_zipDocx->addFromString(
            'word/fontTable.xml', $this->_wordFontTableT
        );

        $this->generateTemplateWordThemeTheme1();
        $this->_zipDocx->addFromString(
            'word/theme/theme1.xml', $this->_wordThemeThemeT
        );

        $this->_zipDocx->close();

        $arrpathFile = pathinfo($fileName);
        copy(
            $this->_tempFile,
            $fileName . '.' . $this->_extension
        );
    }

    /**
     * Change the default font
     *
     * @access public
     * @param string $font The new font
     *  Values: 'Arial', 'Times New Roman'...
     */
    public function setDefaultFont($font)
    {
        $this->_defaultFont = $font;
    }

    /**
     * Transform to UTF-8 charset
     *
     * @access public
     */
    public function setEncodeUTF8()
    {
        self::$_encodeUTF = 1;
    }

    /**
     * Change default language.
     * @param $lang Locale: en-US, es-ES...
     * @access public
     */
    public function setLanguage($lang = 'en-US')
    {
        $this->_language = $lang;
    }

    /*** Old API. It will be remove in next version ***/

    /**
     * Add a break
     *
     * @access public
     * @param string $type Break type
     * @deprecated
     */
    public function fAddBreak($type = '')
    {
        $page = CreatePage::getInstance();
        $page->generatePageBreak($type);
        $this->_wordDocumentC .= (string) $page;
    }

    /**
     * Add a new font
     *
     * @access public
     * @param array $fonts Fonts to add
     * @deprecated
     */
    public function fAddFont($fonts)
    {
        $font = CreateFontTable::getInstance();
        $font->createFont($fonts);
        $this->_wordFontTableC .= (string) $font;
    }

    /**
     * Add a footer
     *
     * @access public
     * @param string $dat Text to add
     * @param array $paramsFooter Parameters of footer
     * @deprecated
     */
    public function fAddFooter($dat = '', $paramsFooter = '')
    {
        $footer = CreateFooter::getInstance();
        $dat .= ' This document was created with free version of PHPdocx.
                Pro version available.';
        $footer->createFooter($dat, $paramsFooter);
        $this->_wordFooterC .= (string) $footer;
        $this->generateOVERRIDE(
            '/word/footer.xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' .
            '.footer+xml'
        );
    }

    /**
     * Add a graphic
     *
     * @access public
     * @param array $dats Parameters of graphic
     * @deprecated
     */
    public function fAddGraphic($dats)
    {
        try {
            if (isset($dats['data']) && isset($dats['type'])) {
                self::$intIdWord++;
                $graphic = CreateGraphic::getInstance();
                if ($graphic->createGraphic(self::$intIdWord, $dats) != false) {
                    $this->_zipDocx->addFromString(
                        'word/charts/chart' . self::$intIdWord . '.xml',
                        $graphic->getXmlChart()
                    );
                    $this->_wordRelsDocumentRelsC .=
                        $this->generateRELATIONSHIP(
                            'rId' . self::$intIdWord, 'chart',
                            'charts/chart' . self::$intIdWord . '.xml'
                        );
                    $this->_wordDocumentC .= (string) $graphic;
                    $this->generateDEFAULT('xlsx', 'application/octet-stream');
                    $this->generateOVERRIDE(
                        '/word/charts/chart' . self::$intIdWord . '.xml',
                        'application/vnd.openxmlformats-officedocument.' .
                        'drawingml.chart+xml'
                    );
                } else {
                    throw new Exception(
                        'There was an error related to the chart.'
                    );
                }
                $excel = CreateXlsx::getInstance();
                $this->_tempFileXLSX[self::$intIdWord] = tempnam(sys_get_temp_dir(), 'documentxlsx');
                if (
                    $excel->createXlsx(
                        $this->_tempFileXLSX[self::$intIdWord],
                        $dats['data'], $dats['type']
                    ) != false
                ) {
                    $this->_zipDocx->addFile(
                        $this->_tempFileXLSX[self::$intIdWord],
                        'word/embeddings/datos' . self::$intIdWord . '.xlsx'
                    );

                    $chartRels = CreateChartRels::getInstance();
                    $chartRels->createRelationship(self::$intIdWord);
                    $this->_zipDocx->addFromString(
                        'word/charts/_rels/chart' . self::$intIdWord .
                        '.xml.rels',
                        (string) $chartRels
                    );
                }
            } else {
                throw new Exception(
                    'Images must have "data" and "type" values.'
                );
            }
        }
        catch (Exception $e) {
            self::$log->fatal($e->getMessage());
            exit();
        }
    }

    /**
     * Add a header.
     *
     * @access public
     * @param string $text Text to add
     * @param array $paramsHeader Parameters of header
     * @deprecated
     */
    public function fAddHeader($text = 'Header', $paramsHeader = '')
    {
        $header = CreateHeader::getInstance();
        $header->createHeader($text, $paramsHeader);
        $this->_wordHeaderC .= (string) $header;
        $this->generateOVERRIDE(
            '/word/header.xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.' .
            'header+xml'
        );
    }

    /**
     * Add an image
     *
     * @access public
     * @param array $dats Image to add and paramaters to use
     * @deprecated
     */
    public function fAddImage($dats = '')
    {
        try {
            if (isset($dats['name']) && file_exists($dats['name']) == 'true') {
                $attrImage = getimagesize($dats['name']);
                try {
                    if ($attrImage['mime'] == 'image/jpg' ||
                        $attrImage['mime'] == 'image/jpeg' ||
                        $attrImage['mime'] == 'image/png' ||
                        $attrImage['mime'] == 'image/gif'
                    ) {
                        self::$intIdWord++;
                        $image = CreateImage::getInstance();
                        $dats['rId'] = self::$intIdWord;
                        $image->createImage($dats);
                        $this->_wordDocumentC .= (string) $image;
                        $dir = $this->parsePath($dats['name']);
                        $this->_zipDocx->addFile(
                            $dats['name'], 'word/media/image' .
                            self::$intIdWord . '.' .
                            $dir['extension']
                        );
                        $this->generateDEFAULT(
                            $dir['extension'], $attrImage['mime']
                        );
                        if ((string) $image != '')
                            $this->_wordRelsDocumentRelsC .=
                                $this->generateRELATIONSHIP(
                                    'rId' . self::$intIdWord, 'image',
                                    'media/image' . self::$intIdWord . '.'
                                    . $dir['extension']
                                );
                    } else {
                        throw new Exception('Image format is not supported.');
                    }
                }
                catch (Exception $e) {
                    self::$log->fatal($e->getMessage());
                    exit();
                }
            } else {
                throw new Exception('Image does not exist.');
            }
        }
        catch (Exception $e) {
            self::$log->fatal($e->getMessage());
            exit();
        }
    }

    /**
     * Add a link
     *
     * @access protected
     * @param string $text Text to use as link
     * @param string $link URL link
     * @param string $font Type of font
     * @deprecated
     */
    protected function fAddLink($text = '', $textLink = '', $font = '')
    {
        $link = CreateLink::getInstance();
        $link->createLink($text, $textLink, $font);
        $this->_wordDocumentC .= (string) $link;
    }

    /**
     * Add a list
     *
     * @access public
     * @param array $dats Values of the list
     * @param array $paramsList Parameters to use
     * @deprecated
     */
    public function fAddList($dats, $paramsList = '')
    {
        $list = CreateList::getInstance();
        $list->createList($dats, $paramsList);
        $this->_wordDocumentC .= (string) $list;
    }

    /**
     * Convert a math eq to DOCX
     *
     * @access public
     * @param string $path Path to a file with math eq
     * @deprecated
     */
    public function fAddMathDocx($path)
    {
        $package = new ZipArchive();
        $package->open($path);
        $document = $package->getFromName('word/document.xml');
        $eqs = preg_split('/<[\/]*m:oMathPara>/', $document);
        $this->addMathEq('<m:oMathPara>' . $eqs[1] . '</m:oMathPara>');
        $package->close();
    }

    /**
     * Add an existing math eq to DOCX
     *
     * @access public
     * @param string $eq Math eq
     * @deprecated
     */
    public function fAddMathEq($eq)
    {
        $this->_wordDocumentC .= '<' . CreateDocx::NAMESPACEWORD . ':p>' .
            (string) $eq . '</' . CreateDocx::NAMESPACEWORD . ':p>';
    }

    /**
     * Add a paragraph
     *
     * @access public
     * @param string $text Text to add
     * @param string $style Style of the paragraph
     * @param string $align Align of the paragraph
     * @deprecated
     */
    public function fAddParagraph($text, $style = '', $align = '')
    {
        $paragraph = CreateText::getInstance();
        $paragraph->createParagraph($text, $style, $align);
        $this->_wordDocumentC .= (string) $paragraph;
    }

    /**
     * Add a table.
     *
     * @access public
     * @param array $dats Values to add
     * @param array $parameters Parameters to use
     * @deprecated
     */
    public function fAddTable($dats, $parameters = '')
    {
        $table = CreateTable::getInstance();
        $table->createTable($dats, $parameters);
        $this->_wordDocumentC .= (string) $table;
    }

    /**
     * Add a table of contents (TOC)
     *
     * @access public
     * @param string $font Set font type
     * @deprecated
     */
    public function fAddTableContents($font = '')
    {
        $tableContents = CreateTableContents::getInstance();
        $tableContents->createTableContents($font);
        $this->_wordDocumentC .= (string) $tableContents;
    }

    /**
     * Add a text
     *
     * @access public
     * @param mixed $value Text or array of texts to add
     * @param array $style Style of text
     * @deprecated
     */
    public function fAddText($value, $style = '')
    {
        $text = CreateText::getInstance();
        $text->createText($value, $style);
        $this->_wordDocumentC .= (string) $text;
    }

    /**
     * Add a title
     *
     * @access public
     * @param string $text Text to add
     * @param array $style Style of title
     * @deprecated
     */
    public function fAddTitle($text, $style = '')
    {
        $title = CreateText::getInstance();
        $title->createTitle($text, $style);
        $this->_wordDocumentC .= (string) $title;
    }

    /**
     * Generate a new DOCX file
     *
     * @access public
     * @param string $args[0] File name
     * @deprecated
     */
    public function fCreateDocx()
    {
        $args = func_get_args();
        if (!empty($args[0])) {
            $fileName = $args[0];
        } else {
            $fileName = 'document';
        }
        $this->generateTemplateRelsRels();
        $this->_zipDocx->addFromString('_rels/.rels', $this->_relsRelsT);
        $this->generateTemplateDocPropsApp();
        $this->_zipDocx->addFromString(
            'docProps/app.xml', $this->_docPropsAppT
        );
        $this->generateTemplateDocPropsCore();
        $this->_zipDocx->addFromString(
            'docProps/core.xml', $this->_docPropsCoreT
        );

        $this->addStyle($this->_language);
        $this->generateTemplateWordStyles();
        $this->_zipDocx->addFromString(
            'word/styles.xml', $this->_wordStylesT
        );

        $this->addSettings();
        $this->generateTemplateWordSettings();
        $this->_zipDocx->addFromString(
            'word/settings.xml', $this->_wordSettingsT
        );

        $this->addWebSettings();
        $this->generateTemplateWordWebSettings();
        $this->_zipDocx->addFromString(
            'word/webSettings.xml', $this->_wordWebSettingsT
        );

        if (empty($this->_wordFooterC)) {
            $paramsFooter = array(
                    'pagerAlignment' => 'right',
                    'font' => 'Times New Roman'
                );
            $this->addFooter('', $paramsFooter);
        }
        $this->generateTemplateWordFooter();
        if (self::$_encodeUTF) {
            $this->_zipDocx->addFromString(
                'word/footer.xml', utf8_encode($this->_wordFooterT)
            );
        } else {
            $this->_zipDocx->addFromString(
                'word/footer.xml', $this->_wordFooterT
            );
        }

        if (!empty($this->_wordHeaderC)) {
            $this->generateTemplateWordHeader();
            if (self::$_encodeUTF) {
                $this->_zipDocx->addFromString(
                    'word/header.xml', utf8_encode($this->_wordHeaderT)
                );
            } else {
                $this->_zipDocx->addFromString(
                    'word/header.xml', $this->_wordHeaderT
                );
            }
        }
        if (!empty($this->_wordRelsHeaderRelsC)) {
            $this->generateTemplateWordRelsHeaderRels();
            $this->_zipDocx->addFromString(
                'word/_rels/header.xml.rels', $this->_wordRelsHeaderRelsT
            );
        }

        $this->generateOVERRIDE(
            '/word/document.xml',
            'application/vnd.openxmlformats-officedocument.' .
            'wordprocessingml.document.main+xml'
        );

        $this->generateTemplateContentType();
        $this->_zipDocx->addFromString(
            '[Content_Types].xml',
            $this->_wordContentTypeT
        );

        $this->generateTemplateWordNumbering();
        $this->_zipDocx->addFromString(
            'word/numbering.xml', $this->_wordNumberingT
        );

        $this->generateDefaultWordRels();
        if (!empty($this->_wordRelsDocumentRelsC)) {
            $this->generateTemplateWordRelsDocumentRels();
            $this->_zipDocx->addFromString(
                'word/_rels/document.xml.rels',
                $this->_wordRelsDocumentRelsT
            );
        }
        $arrArgsPage = array();
        if (isset($args[1])) {
            $arrArgsPage = $args[1];
        }
        $this->generateTemplateWordDocument($arrArgsPage);

        if (self::$_encodeUTF) {
            $this->_zipDocx->addFromString(
                'word/document.xml', utf8_encode($this->_wordDocumentT)
            );
        } else {
            $this->_zipDocx->addFromString(
                'word/document.xml', $this->_wordDocumentT
            );
        }

        $this->generateDefaultFonts();
        $this->generateTemplateWordFontTable();
        $this->_zipDocx->addFromString(
            'word/fontTable.xml', $this->_wordFontTableT
        );

        $this->generateTemplateWordThemeTheme1();
        $this->_zipDocx->addFromString(
            'word/theme/theme1.xml', $this->_wordThemeThemeT
        );

        $this->_zipDocx->close();

        $arrpathFile = pathinfo($fileName);
        copy(
            $this->_tempFile,
            $fileName . '.' . $this->_extension
        );
    }

    /**
     * Change the default font
     *
     * @access public
     * @param string $font The new font
     * @deprecated
     */
    public function fSetDefaultFont($font)
    {
        $this->_defaultFont = $font;
    }

    /*** End old API ***/

    /**
     *
     *
     * @access private
     */
    private function generateContentType()
    {
        $this->generateDEFAULT(
            'rels', 'application/vnd.openxmlformats-package.relationships+xml'
        );
        $this->generateDEFAULT('xml', 'application/xml');
        $this->generateOVERRIDE(
            '/word/numbering.xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.' .
            'numbering+xml'
        );
        $this->generateOVERRIDE(
            '/word/styles.xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' .
            '.styles+xml'
        );
        $this->generateOVERRIDE(
            '/docProps/app.xml',
            'application/vnd.openxmlformats-officedocument.extended-' .
            'properties+xml'
        );
        $this->generateOVERRIDE(
            '/word/settings.xml', 'application/' .
            'vnd.openxmlformats-officedocument.wordprocessingml.settings+xml'
        );
        $this->generateOVERRIDE(
            '/word/theme/theme1.xml',
            'application/vnd.openxmlformats-officedocument.theme+xml'
        );
        $this->generateOVERRIDE(
            '/word/fontTable.xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.' .
            'fontTable+xml'
        );
        $this->generateOVERRIDE(
            '/word/webSettings.xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' .
            '.webSettings+xml'
        );
        if ($this->_wordFooterC != '' || $this->_wordHeaderC != '') {
            $this->generateOVERRIDE(
                '/word/header.xml',
                'application/vnd.openxmlformats-officedocument.' .
                'wordprocessingml.header+xml'
            );
            $this->generateOVERRIDE(
                '/word/footer.xml',
                'application/vnd.openxmlformats-officedocument.' .
                'wordprocessingml.footer+xml'
            );
            $this->generateOVERRIDE(
                '/word/footnotes.xml',
                'application/vnd.openxmlformats-officedocument.' .
                'wordprocessingml.footnotes+xml'
            );
            $this->generateOVERRIDE(
                '/word/endnotes.xml',
                'application/vnd.openxmlformats-officedocument.' .
                'wordprocessingml.endnotes+xml'
            );
        }
        $this->generateOVERRIDE(
            '/docProps/core.xml',
            'application/vnd.openxmlformats-package.core-properties+xml'
        );
    }

    /**
     * Generate SECTPR
     *
     * @access private
     * @param array $args Section style
     */
    private function generateSECTPR($args = '')
    {
        $page = CreatePage::getInstance();
        $page->createSECTPR($args);
        $this->_wordDocumentC .= (string) $page;
    }

    /**
     * Generate ContentType XML template
     *
     * @access private
     */
    private function generateTemplateContentType()
    {
        $this->_wordContentTypeT =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/' .
            'content-types">' . $this->_contentTypeC . '</Types>';
    }

    /**
     * Generate DocPropsApp XML template
     *
     * @access private
     */
    private function generateTemplateDocPropsApp()
    {
        $this->_docPropsAppT = 
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Properties xmlns="http://schemas.openxmlformats.org/' .
            'officeDocument/2006/extended-properties" xmlns:vt="' .
            'http://schemas.openxmlformats.org/officeDocument/2006/' .
            'docPropsVTypes"><Template>Normal.dotm</Template><TotalTime>' .
            '0</TotalTime><Pages>1</Pages><Words>1</Words><Characters>1'
            . '</Characters><Application>Microsoft Office Word</Application>' .
            '<DocSecurity>4</DocSecurity><Lines>1</Lines><Paragraphs>1' .
            '</Paragraphs><ScaleCrop>false</ScaleCrop><Company>Company' .
            '</Company><LinksUpToDate>false</LinksUpToDate>' .
            '<CharactersWithSpaces>1</CharactersWithSpaces><SharedDoc>' .
            'false</SharedDoc><HyperlinksChanged>false</HyperlinksChanged>' .
            '<AppVersion>12.0000</AppVersion></Properties>';
    }

    /**
     * Generate DocPropsCore XML template
     *
     * @access private
     */
    private function generateTemplateDocPropsCore()
    {
        date_default_timezone_set('UTC');
        $this->_docPropsCoreT = 
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?> ' .
            '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats' .
            '.org/package/2006/metadata/core-properties" ' .
            'xmlns:dc="http://purl.org/dc/elements/1.1/" ' .
            'xmlns:dcterms="http://purl.org/dc/terms/" ' .
            'xmlns:dcmitype="http://purl.org/dc/dcmitype/" ' .
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
            '<dc:title>Title</dc:title><dc:subject>Subject</dc:subject>' .
            '<dc:creator>2mdc</dc:creator><dc:description>Description' .
            '</dc:description><cp:lastModifiedBy>user' .
            '</cp:lastModifiedBy><cp:revision>1</cp:revision>' .
            '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('c') .
            '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF' .
            '">' . date('c') . '</dcterms:modified></cp:coreProperties>';
    }

    /**
     * Generate RelsRels XML template
     *
     * @access private
     */
    private function generateTemplateRelsRels()
    {
        $this->_relsRelsT =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/' .
            '2006/relationships">' .
            $this->generateRELATIONSHIP(
                'rId3', 'extended-properties', 'docProps/app.xml'
            ) .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats' .
            '.org/package/2006/relationships/metadata/core-properties"' .
            ' Target="docProps/core.xml"/>' .
            $this->generateRELATIONSHIP(
                'rId1', 'officeDocument', 'word/document.xml'
            );
        $this->_relsRelsT .= '</Relationships>';
    }

    /**
     * Generate WordDocument XML template
     *
     * @access private
     */
    private function generateTemplateWordDocument()
    {
        $arrArgs = func_get_args();
        $this->generateSECTPR($arrArgs[0]);
        if (!empty($this->_wordHeaderC)) {
            $this->_wordDocumentC = str_replace(
                '__GENERATEHEADERREFERENCE__',
                '<' . CreateDocx::NAMESPACEWORD . ':headerReference ' .
                CreateDocx::NAMESPACEWORD . ':type="default" r:id="rId' .
                $this->_idWords['header'] . '"></' .
                CreateDocx::NAMESPACEWORD . ':headerReference>',
                $this->_wordDocumentC
            );
        }
        if (!empty($this->_wordFooterC)) {
            $this->_wordDocumentC = str_replace(
                '__GENERATEFOOTERREFERENCE__',
                '<' . CreateDocx::NAMESPACEWORD . ':footerReference ' .
                CreateDocx::NAMESPACEWORD . ':type="default" r:id="rId' .
                $this->_idWords['footer'] . '"></' .
                CreateDocx::NAMESPACEWORD . ':footerReference>',
                $this->_wordDocumentC
            );
        }
        $this->_wordDocumentT =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<' . CreateDocx::NAMESPACEWORD . ':document xmlns:ve=' .
            '"http://schemas.openxmlformats.org/markup-compatibility/2006" ' .
            'xmlns:o="urn:schemas-microsoft-com:office:office"' .
            ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006' .
            '/relationships" xmlns:m="http://schemas.openxmlformats.org/' .
            'officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml"'.
            ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/' .
            'wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:' .
            'office:word" xmlns:w="http://schemas.openxmlformats.org/' .
            'wordprocessingml/2006/main" xmlns:wne="http://schemas' .
            '.microsoft.com/office/word/2006/wordml">' .
            '<' . CreateDocx::NAMESPACEWORD . ':body>' .
            $this->_wordDocumentC .
            '</' . CreateDocx::NAMESPACEWORD . ':body>' .
            '</' . CreateDocx::NAMESPACEWORD . ':document>';
        $this->cleanTemplate();
    }

    /**
     * Generate WordEndnotes XML template
     *
     * @access private
     */
    private function generateTemplateWordEndnotes()
    {
        $this->_wordEndnotesT = 
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<' . CreateDocx::NAMESPACEWORD . ':endnotes xmlns:ve' .
            '="http://schemas.openxmlformats.org/markup-compatibility/2006" ' .
            'xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="' .
            'http://schemas.openxmlformats.org/officeDocument/2006/' .
            'relationships" xmlns:m="http://schemas.openxmlformats.org/' .
            'officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:' .
            'vml" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006' .
            '/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:' .
            'office:word" xmlns:w="http://schemas.openxmlformats.org/' .
            'wordprocessingml/2006/main" xmlns:wne="http://schemas' .
            '.microsoft.com/office/word/2006/wordml">' .
            $this->_wordEndnotesC .
            '</' . CreateDocx::NAMESPACEWORD . ':endnotes>';
        self::$intIdWord++;
        $this->_wordRelsDocumentRelsC .= $this->generateRELATIONSHIP(
            'rId' . self::$intIdWord, 'endnotes', 'endnotes.xml'
        );
        $this->generateOVERRIDE(
            '/word/endnotes.xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' .
            '.endnotes+xml'
        );
    }

    /**
     * Generate WordFontTable XML template
     *
     * @access private
     */
    private function generateTemplateWordFontTable()
    {
        $this->_wordFontTableT =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>' .
            '<' . CreateDocx::NAMESPACEWORD . ':fonts xmlns:r="http://' .
            'schemas.openxmlformats.org/officeDocument/2006/' .
            'relationships" xmlns:w="http://schemas.openxmlformats.org/' .
            'wordprocessingml/2006/main">' . $this->_wordFontTableC .
            '</' . CreateDocx::NAMESPACEWORD . ':fonts>';
    }

    /**
     * Generate WordFooter XML template
     *
     * @access private
     */
    private function generateTemplateWordFooter()
    {
        self::$intIdWord++;
        $this->_idWords['footer'] = self::$intIdWord;
        $this->_wordFooterT =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <' . CreateDocx::NAMESPACEWORD . ':ftr xmlns:ve' .
            '="http://schemas.openxmlformats.org/markup-compatibility/' .
            '2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns' .
            ':r="http://schemas.openxmlformats.org/officeDocument/2006/' .
            'relationships" xmlns:m="http://schemas.openxmlformats.org/' .
            'officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml' .
            '" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/' .
            'wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:' .
            'office:word" xmlns:w="http://schemas.openxmlformats.org/' .
            'wordprocessingml/2006/main" xmlns:wne="http://schemas' .
            '.microsoft.com/office/word/2006/wordml">' . $this->_wordFooterC .
            '</' . CreateDocx::NAMESPACEWORD . ':ftr>';
        $this->_wordRelsDocumentRelsC .= $this->generateRELATIONSHIP(
            'rId' . self::$intIdWord, 'footer', 'footer.xml'
        );
    }

    /**
     * Generate WordFootnotes XML template
     *
     * @access private
     */
    private function generateTemplateWordFootnotes()
    {
        $this->_wordFootnotesT = 
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<' . CreateDocx::NAMESPACEWORD . ':footnotes xmlns:ve="' .
            'http://schemas.openxmlformats.org/markup-compatibility/2006" ' .
            'xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="' .
            'http://schemas.openxmlformats.org/officeDocument/2006/' .
            'relationships" xmlns:m="http://schemas.openxmlformats.org/' .
            'officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:' .
            'vml" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006' .
            '/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:' .
            'office:word" xmlns:w="http://schemas.openxmlformats.org/' .
            'wordprocessingml/2006/main" xmlns:wne="http://schemas.microsoft' .
            '.com/office/word/2006/wordml">' . $this->_wordFootnotesC .
            '</' . CreateDocx::NAMESPACEWORD . ':footnotes>';
        self::$intIdWord++;
        $this->_wordRelsDocumentRelsC .= $this->generateRELATIONSHIP(
            'rId' . self::$intIdWord, 'footnotes', 'footnotes.xml'
        );
        $this->generateOVERRIDE(
            '/word/footnotes.xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' .
            '.footnotes+xml'
        );
    }

    /**
     * Generate WordHeader XML template
     *
     * @access private
     */
    private function generateTemplateWordHeader()
    {
        self::$intIdWord++;
        $this->_idWords['header'] = self::$intIdWord;
        $this->_wordHeaderT = '<?xml version="1.0" encoding="UTF-8" ' .
            'standalone="yes"?>' .
            '<' . CreateDocx::NAMESPACEWORD .
            ':hdr xmlns:ve="http://schemas.openxmlformats.org/markup' .
            '-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:' .
            'office:office" xmlns:r="http://schemas.openxmlformats.org/' .
            'officeDocument/2006/relationships" xmlns:m="http://schemas' .
            '.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:' .
            'schemas-microsoft-com:vml" xmlns:wp="http://schemas' .
            '.openxmlformats.org/drawingml/2006/wordprocessingDrawing" ' .
            'xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="' .
            'http://schemas.openxmlformats.org/wordprocessingml/2006/' .
            'main" xmlns:wne="http://schemas.microsoft.com/office/word/' .
            '2006/wordml"> ' . $this->_wordHeaderC .
            '</' . CreateDocx::NAMESPACEWORD . ':hdr>';
        $this->_wordRelsDocumentRelsC .= $this->generateRELATIONSHIP(
            'rId' . self::$intIdWord, 'header', 'header.xml'
        );
    }

    /**
     * Generate WordNumbering XML template
     *
     * @access private
     */
    private function generateTemplateWordNumbering()
    {
        $this->_wordNumberingT = 
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:numbering xmlns:ve="http://schemas.openxmlformats' .
            '.org/markup-compatibility/2006" xmlns:o="urn:schemas-' .
            'microsoft-com:office:office" xmlns:r="http://schemas' .
            '.openxmlformats.org/officeDocument/2006/relationships" ' .
            'xmlns:m="http://schemas.openxmlformats.org/officeDocument/' .
            '2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:' .
            'wp="http://schemas.openxmlformats.org/drawingml/2006/' .
            'wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com' .
            ':office:word" xmlns:w="http://schemas.openxmlformats.org/' .
            'wordprocessingml/2006/main" xmlns:wne="http://schemas.' .
            'microsoft.com/office/word/2006/wordml"><w:abstractNum w:'
            . 'abstractNumId="0"><w:nsid w:val="713727AE"/><w:multiLevelType' .
            ' w:val="hybridMultilevel"/><w:tmpl w:val="F0B4B6B8"/>' .
            '<w:lvl w:ilvl="0" w:tplc="0C0A0001"><w:start w:val="1"/>' .
            '<w:numFmt w:val="bullet"/><w:lvlText w:val=""/><w:lvlJc ' .
            'w:val="left"/><w:pPr><w:ind w:left="720" w:hanging="360"/>' .
            '</w:pPr><w:rPr><w:rFonts w:ascii="Symbol" w:hAnsi="Symbol" ' .
            'w:hint="default"/></w:rPr></w:lvl><w:lvl w:ilvl="1" ' .
            'w:tplc="0C0A0003" w:tentative="1"><w:start w:val="1"/>' .
            '<w:numFmt w:val="bullet"/><w:lvlText w:val="o"/><w:lvlJc ' .
            'w:val="left"/><w:pPr><w:ind w:left="1440" w:hanging="360"/>' . '
                </w:pPr><w:rPr><w:rFonts w:ascii="Courier New" w:hAnsi=' .
            '"Courier New" w:cs="Courier New" w:hint="default"/></w:rPr>' .
            '</w:lvl><w:lvl w:ilvl="2" w:tplc="0C0A0005" w:tentative="1">' .
            '<w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText ' .
            'w:val=""/><w:lvlJc w:val="left"/><w:pPr><w:ind w:left="2160" ' .
            'w:hanging="360"/></w:pPr><w:rPr><w:rFonts w:ascii="Wingdings" ' .
            'w:hAnsi="Wingdings" w:hint="default"/></w:rPr></w:lvl><w:lvl ' .
            'w:ilvl="3" w:tplc="0C0A0001" w:tentative="1"><w:start ' .
            'w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val=""/>' .
            '<w:lvlJc w:val="left"/><w:pPr><w:ind w:left="2880" w:hanging=' .
            '"360"/></w:pPr><w:rPr><w:rFonts w:ascii="Symbol" w:hAnsi=' .
            '"Symbol" w:hint="default"/></w:rPr></w:lvl><w:lvl w:ilvl="4" ' .
            'w:tplc="0C0A0003" w:tentative="1"><w:start w:val="1"/>' .
            '<w:numFmt w:val="bullet"/><w:lvlText w:val="o"/><w:lvlJc ' .
            'w:val="left"/><w:pPr><w:ind w:left="3600" w:hanging="360"/>' .
            '</w:pPr><w:rPr><w:rFonts w:ascii="Courier New" w:hAnsi=' .
            '"Courier New" w:cs="Courier New" w:hint="default"/></w:rPr>' .
            '</w:lvl><w:lvl w:ilvl="5" w:tplc="0C0A0005" w:tentative="1">' .
            '<w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText ' .
            'w:val=""/><w:lvlJc w:val="left"/><w:pPr><w:ind w:left="4320" ' .
            'w:hanging="360"/></w:pPr><w:rPr><w:rFonts w:ascii="Wingdings" ' .
            'w:hAnsi="Wingdings" w:hint="default"/></w:rPr></w:lvl><w:lvl ' .
            'w:ilvl="6" w:tplc="0C0A0001" w:tentative="1"><w:start ' .
            'w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val=""/>' .
            '<w:lvlJc w:val="left"/><w:pPr><w:ind w:left="5040" ' .
            'w:hanging="360"/></w:pPr><w:rPr><w:rFonts w:ascii="Symbol" ' .
            'w:hAnsi="Symbol" w:hint="default"/></w:rPr></w:lvl><w:lvl ' .
            'w:ilvl="7" w:tplc="0C0A0003" w:tentative="1"><w:start ' .
            'w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="o"/>' .
            '<w:lvlJc w:val="left"/><w:pPr><w:ind w:left="5760" ' .
            'w:hanging="360"/></w:pPr><w:rPr><w:rFonts w:ascii="Courier New" ' .
            'w:hAnsi="Courier New" w:cs="Courier New" w:hint="default"/>' .
            '</w:rPr></w:lvl><w:lvl w:ilvl="8" w:tplc="0C0A0005" ' .
            'w:tentative="1"><w:start w:val="1"/><w:numFmt w:val="bullet"' .
            '/><w:lvlText w:val=""/><w:lvlJc w:val="left"/><w:pPr><w:ind ' .
            'w:left="6480" w:hanging="360"/></w:pPr><w:rPr><w:rFonts ' .
            'w:ascii="Wingdings" w:hAnsi="Wingdings" w:hint="default"/>' .
            '</w:rPr></w:lvl></w:abstractNum><w:num w:numId="1">' .
            '<w:abstractNumId w:val="0"/></w:num></w:numbering>';
    }

    /**
     * Generate WordRelsDocumentRels XML template
     *
     * @access private
     */
    private function generateTemplateWordRelsDocumentRels()
    {
        $this->_wordRelsDocumentRelsT = 
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/' .
            'package/2006/relationships">' . $this->_wordRelsDocumentRelsC .
            '</Relationships>';
    }

    /**
     * Generate WordRelsFooterRels XML template
     *
     * @access private
     */
    private function generateTemplateWordRelsFooterRels()
    {
        $this->_wordRelsFooterRelsT = 
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/' .
            'package/2006/relationships">' . $this->_wordRelsFooterRelsC .
            '</Relationships>';
    }

    /**
     * Generate WordRelsHeaderRels XML template
     *
     * @access private
     */
    private function generateTemplateWordRelsHeaderRels()
    {
        $this->_wordRelsHeaderRelsT = 
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/' .
            'package/2006/relationships">' . $this->_wordRelsHeaderRelsC .
            '</Relationships>';
    }

    /**
     * Generate WordSettings XML template
     *
     * @access private
     */
    private function generateTemplateWordSettings()
    {
        $this->_wordSettingsT = $this->_wordSettingsC;
    }

    /**
     * Generate WordStyles XML template
     *
     * @access private
     */
    private function generateTemplateWordStyles()
    {
        $this->_wordStylesT =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><' .
            CreateDocx::NAMESPACEWORD . ':styles xmlns:r="http://' .
            'schemas.openxmlformats.org/officeDocument/2006/relationships' .
            '" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/' .
            '2006/main">' . $this->_wordStylesC .
            '</' . CreateDocx::NAMESPACEWORD . ':styles>';
    }

    /**
     * Generate WordThemeTheme1 XML template
     *
     * @access private
     */
    private function generateTemplateWordThemeTheme1()
    {
        $this->addTheme($this->_defaultFont);
        $this->_wordThemeThemeT =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?><' .
            CreateTheme1::NAMESPACEWORD . ':theme xmlns:a="http://' .
            'schemas.openxmlformats.org/drawingml/2006/main" name="' .
            'Tema de Office">' . $this->_wordThemeThemeC .
            '</' . CreateTheme1::NAMESPACEWORD . ':theme>';
    }

    /**
     * Generate WordWebSettings XML template
     *
     * @access private
     */
    private function generateTemplateWordWebSettings()
    {
        $this->_wordWebSettingsT = $this->_wordWebSettingsC;
    }

    /**
     * Add settings
     *
     * @access private
     */
    private function addSettings()
    {
        $settings = CreateSettings::getInstance();
        $settings->generateSettings();
        $this->_wordSettingsC .= (string) $settings;
    }

    /**
     * Add style
     *
     * @param string lang Language
     * @access private
     */
    private function addStyle($lang = 'en-US')
    {
        $style = CreateStyle::getInstance();
        $style->createStyle($lang);
        $this->_wordStylesC .= (string) $style;
    }

    /**
     * Add theme
     *
     * @access private
     */
    private function addTheme($strFont)
    {
        $theme = CreateTheme1::getInstance();
        $theme->createTheme($strFont);
        $this->_wordThemeThemeC .= (string) $theme;
    }

    /**
     * Add websettings
     *
     * @access private
     */
    private function addWebSettings()
    {
        $webSettings = CreateWebSettings::getInstance();
        $webSettings->generateWebSettings();
        $this->_wordWebSettingsC .= (string) $webSettings;
    }

    /**
     * Clean template
     *
     * @access private
     */
    private function cleanTemplate()
    {
        $this->_wordDocumentT = preg_replace(
            '/__[A-Z]+__/',
            '',
            $this->_wordDocumentT
        );
    }

    /**
     * Generate DEFAULT
     *
     * @access private
     */
    private function generateDEFAULT($extension, $contentType)
    {
        if (
            strpos($this->_contentTypeC, 'Extension="' . $extension)
            === false
        ) {
            $this->_contentTypeC .= '<Default Extension="' .
                $extension . '" ContentType="' . $contentType . '"> </Default>';
        }
    }

    /**
     *
     *
     * @access private
     */
    private function generateDefaultFonts()
    {
        $font = array(
            'name' => 'Calibri', 'pitch' => 'variable', 'usb0' => 'A00002EF',
            'usb1' => '4000207B', 'usb2' => '00000000', 'usb3' => '00000000',
            'csb0' => '0000009F', 'csb1' => '00000000', 'family' => 'swiss',
            'charset' => '00', 'panose1' => '020F0502020204030204'
        );
        $this->addFont($font);
        $font = array(
            'name' => 'Times New Roman', 'pitch' => 'variable',
            'usb0' => 'E0002AEF', 'usb1' => 'C0007841', 'usb2' => '00000009',
            'usb3' => '00000000', 'csb0' => '000001FF', 'csb1' => '00000000',
            'family' => 'roman', 'charset' => '00',
            'panose1' => '02020603050405020304'
        );
        $this->addFont($font);
        $font = array(
            'name' => 'Cambria', 'pitch' => 'variable', 'usb0' => 'A00002EF',
            'usb1' => '4000004B', 'usb2' => '00000000', 'usb3' => '00000000',
            'csb0' => '0000009F', 'csb1' => '00000000', 'family' => 'roman',
            'charset' => '00', 'panose1' => '02040503050406030204'
        );
        $this->addFont($font);
    }

    /**
     * Generate DefaultWordRels
     *
     * @access private
     */
    private function generateDefaultWordRels()
    {
        self::$intIdWord++;
        $this->_wordRelsDocumentRelsC .= $this->generateRELATIONSHIP(
            'rId' . self::$intIdWord, 'numbering', 'numbering.xml'
        );
        self::$intIdWord++;
        $this->_wordRelsDocumentRelsC .= $this->generateRELATIONSHIP(
            'rId' . self::$intIdWord, 'theme', 'theme/theme1.xml'
        );
        self::$intIdWord++;
        $this->_wordRelsDocumentRelsC .= $this->generateRELATIONSHIP(
            'rId' . self::$intIdWord, 'webSettings', 'webSettings.xml'
        );
        self::$intIdWord++;
        $this->_wordRelsDocumentRelsC .= $this->generateRELATIONSHIP(
            'rId' . self::$intIdWord, 'fontTable', 'fontTable.xml'
        );
        self::$intIdWord++;
        $this->_wordRelsDocumentRelsC .= $this->generateRELATIONSHIP(
            'rId' . self::$intIdWord, 'settings', 'settings.xml'
        );
        self::$intIdWord++;
        $this->_wordRelsDocumentRelsC .= $this->generateRELATIONSHIP(
            'rId' . self::$intIdWord, 'styles', 'styles.xml'
        );
    }

    /**
     * Generate OVERRIDE
     *
     * @access private
     * @param string $partName
     * @param string $contentType
     */
    private function generateOVERRIDE($partName, $contentType)
    {
        if (
            strpos($this->_contentTypeC, 'PartName="' . $partName . '"')
            === false
        ) {
            $this->_contentTypeC .= '<Override PartName="' .
                $partName . '" ContentType="' . $contentType . '"> </Override>';
        }
    }

    /**
     * Gnerate RELATIONSHIP
     *
     * @access private
     */
    private function generateRELATIONSHIP()
    {
        $arrArgs = func_get_args();
        if ($arrArgs[1] == 'vbaProject')
            $strType =
            'http://schemas.microsoft.com/office/2006/relationships/vbaProject';
        else
            $strType = 
            'http://schemas.openxmlformats.org/officeDocument/2006/' .
            'relationships/' . $arrArgs[1];

        return '<Relationship Id="' . $arrArgs[0] . '" Type="' . $strType .
               '" Target="' . $arrArgs[2] . '"></Relationship>';
    }

    /**
     * Parse path dir
     *
     * @access private
     * @param string $dir Directory path
     */
    private function parsePath($dir)
    {
        $barra = 0;
        $path = '';
        if (($barra = strrpos($dir, '/')) !== false) {
            $barra += 1;
            $path = substr($dir, 0, $barra);
        }
        $punto = strpos(substr($dir, $barra), '.');

        $nombre = substr($dir, $barra, $punto);
        $extension = substr($dir, $punto + $barra + 1);
        return array(
            'path' => $path, 'nombre' => $nombre, 'extension' => $extension
        );
    }

}