<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Common class for HTML_QuickForm elements to display a CAPTCHA
 *
 * The HTML_QuickForm_CAPTCHA package adds an element to the
 * HTML_QuickForm package to display a CAPTCHA question (image, riddle, etc...)
 *
 * This package requires the use of a PHP session ($_SESSION).
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2006-2008, Philippe Jausions / 11abacus
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *   - Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *   - Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in the
 *     documentation and/or other materials provided with the distribution.
 *   - Neither the name of 11abacus nor the names of its contributors may
 *     be used to endorse or promote products derived from this software
 *     without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  HTML
 * @package   HTML_QuickForm_CAPTCHA
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright 2006-2008 by Philippe Jausions / 11abacus
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD
 * @version   CVS: $Id: CAPTCHA.php,v 1.1 2008/04/26 23:27:28 jausions Exp $
 * @link      http://pear.php.net/package/HTML_QuickForm_CAPTCHA
 */

/**
 * Common class for HTML_QuickForm elements to display a CAPTCHA
 *
 * The HTML_QuickForm_CAPTCHA package adds an element to the
 * HTML_QuickForm package to display a CAPTCHA question (image, riddle, etc...)
 *
 * This package requires the use of a PHP session ($_SESSION).
 *
 * Because the CAPTCHA element is serialized in the PHP session,
 * you need to include the class declaration BEFORE the session starts.
 * So BEWARE if you have php.ini session.auto_start enabled, you won't be
 * able to use this element, unless you're also using PHP 5's __autoload()
 * or php.ini's unserialize_callback_func setting
 *
 * @category  HTML
 * @package   HTML_QuickForm_CAPTCHA
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright 2006-2008 by Philippe Jausions / 11abacus
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD
 * @version   Release: 0.3.0
 * @link      http://pear.php.net/package/HTML_QuickForm_CAPTCHA
 * @abstract
 */
class HTML_QuickForm_CAPTCHA extends HTML_QuickForm_input
{
    /**
     * Default options
     *
     * @var array
     * @access protected
     */
    var $_options = array(
        'sessionVar' => '_HTML_QuickForm_CAPTCHA',
        'phrase' => null,
    );

    /**
     * CAPTCHA driver
     *
     * @var string
     * @access protected
     */
    var $_CAPTCHA_driver;

    /**
     * Class constructor
     *
     * @param string $elementName  Name
     * @param mixed  $elementLabel Label for the CAPTCHA
     * @param array  $options      Options for the Text_CAPTCHA package
     * <ul>
     *  <li>'sessionVar' (string)  name of session variable containing
     *                             the Text_CAPTCHA instance (defaults to
     *                             _HTML_QuickForm_CAPTCHA.)</li>
     *  <li>Other options depend on the driver used</li>
     * </ul>
     * @param mixed  $attributes   HTML Attributes for the <a> tag surrounding
     *                             the image. Can be a string or array.
     *
     * @access public
     */
    public function __construct(
        $elementName = null,
        $elementLabel = null,
        $options = null,
        $attributes = null
    ) {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->setType('CAPTCHA_'.$this->_CAPTCHA_driver);

        if (is_array($options)) {
            $this->_options = array_merge($this->_options, $options);
        }
    }

    /**
     * Initializes the CAPTCHA instance (if needed)
     *
     * @return boolean TRUE or PEAR_Error on error
     * @access protected
     */
    function _initCAPTCHA()
    {
        $sessionVar = $this->_options['sessionVar'];

        if (empty($_SESSION[$sessionVar])) {

            $_SESSION[$sessionVar] = Text_CAPTCHA::factory($this->_CAPTCHA_driver);

            if (PEAR::isError($_SESSION[$sessionVar])) {
                return $_SESSION[$sessionVar];
            }
            $result = $_SESSION[$sessionVar]->init($this->_options);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Returns the answer/phrase of the CAPTCHA
     *
     * @param mixed &$values Ignored by this element
     *
     * @return string
     * @access private
     */
    function _findValue(&$values)
    {
        return $this->getValue();
    }

    /**
     * Returns the answer/phrase of the CAPTCHA
     *
     * @return string
     * @access public
     */
    function getValue()
    {
        $sessionVar = $this->_options['sessionVar'];

        return (!empty($_SESSION[$sessionVar]))
                 ? $_SESSION[$sessionVar]->getPhrase()
                 : null;
    }

    /**
     * Returns the answer/phrase of the CAPTCHA
     *
     * @param mixed   &$submitValues Ignored by this element
     * @param boolean $assoc         Whether to return an array
     *
     * @return string
     * @access public
     */
    function exportValue(&$submitValues, $assoc = false)
    {
        return ($assoc)
               ? array($this->getName() => $this->getValue())
               : $this->getValue();
    }

    /**
     * Sets the CAPTCHA question/phrase
     *
     * Pass NULL or no argument for a random question/phrase to be generated
     *
     * @param string $phrase Value of the CAPTCHA to set
     *
     * @return void
     * @access public
     */
    function setPhrase($phrase = null)
    {
        $this->_options['phrase'] = $phrase;

        if (!empty($_SESSION[$this->_options['sessionVar']])) {
            $_SESSION[$this->_options['sessionVar']]->setPhrase($phrase);
        }
    }

    /**
     * Destroys the CAPTCHA instance to prevent reuse
     *
     * @return void
     * @access public
     */
    function destroy()
    {
        unset($_SESSION[$this->_options['sessionVar']]);
    }

    /**
     * Returns the HTML for the CAPTCHA
     *
     * This can be overwritten by sub-classes for specific output behavior
     * (for instance the Image CAPTCHA displays an image)
     *
     * @return string
     * @access public
     */
    function toHtml()
    {
        $result = $this->_initCAPTCHA();
        if (PEAR::isError($result)) {
            return $result;
        }

        $captcha = $_SESSION[$this->_options['sessionVar']]->getCAPTCHA();

        $attr = $this->_attributes;
        unset($attr['type']);
        unset($attr['value']);
        unset($attr['name']);

        $html = $this->_getTabs()
                . '<span' . $this->_getAttrString($attr) . '>'
                . htmlspecialchars($captcha)
                . '</span>';
        return $html;
    }
}
