<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Rule for HTML_QuickForm to display a CAPTCHA image
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
 * @version   CVS: $Id: CAPTCHA.php,v 1.1 2008/04/26 23:27:30 jausions Exp $
 * @link      http://pear.php.net/package/HTML_QuickForm_CAPTCHA
 */

/**
 * Rule to compare a field with a CAPTCHA image
 *
 * @category  HTML
 * @package   HTML_QuickForm_CAPTCHA
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright 2006-2008 by Philippe Jausions / 11abacus
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD
 * @version   Release: 0.3.0
 * @link      http://pear.php.net/package/HTML_QuickForm_CAPTCHA
 */
class HTML_QuickForm_Rule_CAPTCHA extends HTML_QuickForm_Rule
{
    /**
     * Validates the data entered matches the CAPTCHA image that was
     * displayed
     *
     * @param string                        $value   data to validate
     * @param HTML_QuickForm_CAPTCHA_Common $captcha element to check against
     *
     * @return boolean TRUE if valid, FALSE otherwise
     * @access public
     * @static
     */
    function validate($value, $captcha)
    {
        return ($value == $captcha->getValue());
    }
}
