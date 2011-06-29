<?php

/**
 * Create XLSX
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
 * Create XLSX
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
class CreateXlsx
{

    /**
     * @access private
     * @var CreateFooter
     * @static
     */
    private static $_instance = NULL;
    /**
     *
     * @access private
     * @var <type>
     */
    private $_zipXlsx;
    /**
     *
     * @access private
     * @var <type>
     */
    private $_xmlXlTablesContent;
    /**
     *
     * @access private
     * @var <type>
     */
    private $_xmlXlSharedStringsContent;
    /**
     *
     * @access private
     * @var <type>
     */
    private $_xmlXlSheetContent;

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
        return $this->_zipXlsx;
    }

    /**
     *
     * @access public
     * @return CreateXlsx
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateXlsx();
        }
        return self::$_instance;
    }

    /**
     * Create XLSX
     * 
     * @access public
     * @return bool
     */
    public function createXlsx()
    {
        $args = func_get_args();
        $this->_zipXlsx = new ZipArchive();
        $this->_zipXlsx->open($args[0], ZipArchive::OVERWRITE);
        $this->_xmlXlTablesContent = '';
        $this->_xmlXlSharedStringsContent = '';
        $this->_xmlXlSheetContent = '';
        try {
            $dirname = substr(__FILE__, 0, strpos(__FILE__, '/classes')) .
                '/excel/';

            $this->_zipXlsx->addFile(
                $dirname . '[Content_Types].xml',
                '[Content_Types].xml'
            );
            $this->_zipXlsx->addFile(
                $dirname . 'docProps/core.xml',
                'docProps/core.xml'
            );
            $this->_zipXlsx->addFile(
                $dirname . 'docProps/app.xml',
                'docProps/app.xml'
            );
            $this->_zipXlsx->addFile(
                $dirname . '_rels/.rels',
                '_rels/.rels'
            );
            $this->_zipXlsx->addFile(
                $dirname . 'xl/_rels/workbook.xml.rels',
                'xl/_rels/workbook.xml.rels'
            );
            $this->_zipXlsx->addFile(
                $dirname . 'xl/theme/theme1.xml',
                'xl/theme/theme1.xml'
            );
            $this->_zipXlsx->addFile(
                $dirname . 'xl/worksheets/_rels/sheet1.xml.rels',
                'xl/worksheets/_rels/sheet1.xml.rels'
            );
            $this->_zipXlsx->addFile(
                $dirname . 'xl/styles.xml',
                'xl/styles.xml'
            );
            $this->_zipXlsx->addFile(
                $dirname . 'xl/workbook.xml',
                'xl/workbook.xml'
            );
            $this->addTable($args[1], $args[2]);
            $this->_zipXlsx->addFromString(
                'xl/tables/table1.xml',
                $this->_xmlXlTablesContent
            );

            $this->addSharedStrings($args[1], $args[2]);
            $this->_zipXlsx->addFromString(
                'xl/sharedStrings.xml',
                $this->_xmlXlSharedStringsContent
            );

            $this->addSheet($args[1], $args[2]);
            $this->_zipXlsx->addFromString(
                'xl/worksheets/sheet1.xml',
                $this->_xmlXlSheetContent
            );
            $this->_zipXlsx->close();

            return true;
        } catch (Exception $e) {
            echo 'There was an error related to XLSX file: ',
            $e->getMessage(),
            "\n";
            return false;
        }
    }

    /**
     * Add table
     *
     * @access protected
     * @param array $args[0]
     * @param array $args[1]
     */
    protected function addTable()
    {
        $args = func_get_args();
        $excelTable = CreateExcelTable::getInstance();
        $excelTable->createExcelTable($args[0], $args[1]);
        $this->_xmlXlTablesContent .= (string) $excelTable;
    }

    /**
     * Add shared strings
     *
     * @access protected
     * * @param array $args[0]
     * @param array $args[1]
     */
    protected function addSharedStrings()
    {
        $args = func_get_args();
        $excelSS = CreateExcelSharedStrings::getInstance();
        $excelSS->createExcelSharedStrings($args[0], $args[1]);
        $this->_xmlXlSharedStringsContent .= (string) $excelSS;
    }

    /**
     * Add sheet
     *
     * @access protected
     * * @param array $args[0]
     * @param array $args[1]
     */
    protected function addSheet()
    {
        $args = func_get_args();
        $excelSheet = CreateExcelSheet::getInstance();
        $excelSheet->createExcelSheet($args[0], $args[1]);
        $this->_xmlXlSheetContent .= (string) $excelSheet;
    }

    /**
     *
     * @access private
     * @deprecated
     */
    private function cleanTemplate()
    {
        $this->_xmlWordDocumentTemplate = preg_replace(
            '/__[A-Z]+__/',
            '',
            $this->_xmlWordDocumentTemplate
        );
    }

}