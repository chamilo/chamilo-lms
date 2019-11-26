<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Element for HTML_QuickForm to display a CAPTCHA equation
 *
 * The HTML_QuickForm_CAPTCHA package adds an element to the
 * HTML_QuickForm package to display a CAPTCHA equation.
 *
 * This package requires the use of a PHP session.
 *
 * PHP versions 4 and 5
 *
 * @category  HTML
 * @package   HTML_QuickForm_CAPTCHA
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright 2006-2008 by Philippe Jausions / 11abacus
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD
 * @version   CVS: $Id: Equation.php,v 1.1 2008/04/26 23:27:30 jausions Exp $
 * @link      http://pear.php.net/package/HTML_QuickForm_CAPTCHA
 */

/**
 * Element for HTML_QuickForm to display a CAPTCHA equation question
 *
 * The HTML_QuickForm_CAPTCHA package adds an element to the
 * HTML_QuickForm package to display a CAPTCHA equation question.
 *
 * Options for the element
 * <ul>
 *  <li>'min'           (integer)  Minimal number to use in an equation.</li>
 *  <li>'max'           (integer)  Maximal number to use in an equation.</li>
 *  <li>'severity'      (integer)  Complexity of the equation to resolve
 *                                 (1 = easy, 2 = harder)</li>
 *  <li>'numbersToText' (boolean)  Whether to use the Numbers_Words
 *                                 package to convert numbers to text,</li>
 *  <li>'sessionVar'    (string)   name of session variable containing
 *                                 the Text_CAPTCHA instance (defaults to
 *                                 _HTML_QuickForm_CAPTCHA.)</li>
 * </ul>
 *
 * This package requires the use of a PHP session.
 *
 * @category  HTML
 * @package   HTML_QuickForm_CAPTCHA
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright 2006-2008 by Philippe Jausions / 11abacus
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD
 * @version   Release: 0.3.0
 * @link      http://pear.php.net/package/HTML_QuickForm_CAPTCHA
 * @see       Text_CAPTCHA_Driver_Equation
 */
class HTML_QuickForm_CAPTCHA_Equation extends HTML_QuickForm_CAPTCHA
{
    /**
     * Default options
     *
     * @var    array
     * @access protected
     */
    var $_options = array(
            'sessionVar'    => '_HTML_QuickForm_CAPTCHA',
            'severity'      => 1,
            'numbersToText' => false,
            'phrase'        => null,
            );

    /**
     * CAPTCHA driver
     *
     * @var    string
     * @access protected
     */
    var $_CAPTCHA_driver = 'Equation';
}
