<?php

/**
 * Create a DOCX file. List example
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

$docx->addMathEq(
    '<m:oMathPara>
        <m:oMath><m:r><m:t>∪±∞=~×</m:t></m:r></m:oMath>
    </m:oMathPara>'
);

$docx->addMathDocx('../files/math.docx');

$docx->createDocx('example_math');