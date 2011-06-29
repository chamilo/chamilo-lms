<?php

/**
 * Create a DOCX file. Image example
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

$paramsImg = array(
    'name' => '../files/img/image.png',
    'scaling' => 50,
    'spacingTop' => 100,
    'spacingBottom' => 0,
    'spacingLeft' => 100,
    'spacingRight' => 0,
    'textWrap' => 1,
    'border' => 1,
    'borderDiscontinuous' => 1
);

$docx->addImage($paramsImg);

$docx->createDocx('example_image');