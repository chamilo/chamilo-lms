<?php

/**
 * Create a DOCX file. Transform DOCX to HTML
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
require_once '../../classes/TransformDoc.inc';

$document = new TransformDoc();
$document->setStrFile('../files/Text.docx');
$document->generateXHTML();
$document->validatorXHTML();
echo $document->getStrXHTML();
