<?php

/**
 * Create a DOCX file. Table example
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

$valuesTable = array(
    array(
        11,
        12
    ),
    array(
        21,
        22
    ),
);

$paramsTable = array(
    'border' => 'single',
    'border_sz' => 20
);


$docx->addTable($valuesTable, $paramsTable);

$docx->createDocx('example_table');
