<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Alexey Borzov <avb@php.net>                                  |
// +----------------------------------------------------------------------+
//
// $Id: Compare.php 6184 2005-09-07 10:08:17Z bmol $
/**
 * @package chamilo.library
 */

/**
 * Rule to compare two form fields
 *
 * The most common usage for this is to ensure that the password
 * confirmation field matches the password field
 *
 * @access public
 * @package HTML_QuickForm
 * @version $Revision: 6184 $
 */
class HTML_QuickForm_Rule_CompareDate extends HTML_QuickForm_Rule
{
    function validate($values, $options)
    {
        if (!is_array($values[0]) && !is_array($values[1])) {
            return api_strtotime($values[0]) < api_strtotime($values[1]);
        } else {
            $compareFn = create_function(
                '$a, $b', 'return mktime($a[\'H\'],$a[\'i\'],0,$a[\'M\'],$a[\'d\'],$a[\'Y\']) <=   mktime($b[\'H\'],$b[\'i\'],0,$b[\'M\'],$b[\'d\'],$b[\'Y\'] );'
            );
            return $compareFn($values[0], $values[1]);
        }
    }
}
