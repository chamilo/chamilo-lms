<?php

/**
 * Create a DOCX file. Footer example
 *
 * @category   Phpdocx
 * @package    examples
 * @subpackage easy
 * @copyright  Copyright (c) 2009-2011 Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    LGPL
 * @version    2.0
 * @link       http://www.phpdocx.com
 * @since      File available since Release 2.0
 */
require_once '../../classes/CreateDocx.inc';

$docx = new CreateDocx();

$paramsFooter = array(
    'font' => 'Times New Roman'
);

$docx->addFooter('Footer. Times New Roman font', $paramsFooter);

$docx->createDocx('example_footer');