<?php

/**
 * Create a DOCX file. Title example
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

$paramsTitle = array(
    'val' => 1,
    'u' => 'single',
    'font' => 'Blackadder ITC',
    'sz' => 22
);

$docx->addTitle('Lorem ipsum dolor sit amet.', $paramsTitle);

$docx->createDocx('example_title');