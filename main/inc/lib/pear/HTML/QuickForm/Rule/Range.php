<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Checks that the length of value is within range
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: Range.php,v 1.8 2009/04/04 21:34:04 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * Checks that the length of value is within range
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 * @since       3.2
 */
class HTML_QuickForm_Rule_Range extends HTML_QuickForm_Rule
{
    /**
     * Validates a value using a range comparison
     *
     * @param     string    $value      Value to be checked
     * @param     mixed     $options    Int for length, array for range
     * @access    public
     * @return    boolean   true if value is valid
     */
    public function validate($value, $options)
    {
        $length = api_strlen($value);

        switch ($this->name) {
            case 'min_numeric_length':
                return (float) $value >= $options;
            case 'max_numeric_length':
                return (float) $value <= $options;
            case 'minlength':
                return $length >= $options;
            case 'maxlength':
                return $length <= $options;
            default:
                return $length >= $options[0] && $length <= $options[1];
        }
    }

    public function getValidationScript($options = null)
    {
        switch ($this->name) {
            case 'minlength':
                $test = '{jsVar}.length < '.$options;
                break;
            case 'maxlength':
                $test = '{jsVar}.length > '.$options;
                break;
            default:
                $test = '({jsVar}.length < '.$options[0].' || {jsVar}.length > '.$options[1].')';
        }
        return array('', "{jsVar} != '' && {$test}");
    }
}