<?php

/**
 * Create a DOCX file. Header
 *
 * @category   Phpdocx
 * @package    examples
 * @subpackage intermediate
 * @copyright  Copyright (c) 2009-2011 Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    LGPL
 * @version    2.0
 * @link       http://www.phpdocx.com
 * @since      File available since Release 2.0
 */
require_once '../../classes/CreateDocx.inc';

$docx = new CreateDocx();

$paramsHeader = array(
    'font' => 'Times New Roman',
    'jc' => 'right',
    'textWrap' => 5,
);

$docx->addHeader('Header text', $paramsHeader);

$docx->createDocx('example_header');
