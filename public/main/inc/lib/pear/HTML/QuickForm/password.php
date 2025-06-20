<?php

/**
 * HTML class for a password type field
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
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: password.php,v 1.8 2009/04/04 21:34:04 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;

/**
 * HTML class for a password type field
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_password extends HTML_QuickForm_text
{
    /**
     * Class constructor
     *
     * @param string $elementName           (optional)Input field name attribute
     * @param string $elementLabel          (optional)Input field label
     * @param mixed  $attributes            (optional)Either a typical HTML attribute string
     *                                      or an associative array
     *
     * @throws
     * @since     1.0
     * @access    public
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null)
    {
        $attributes['class'] = $attributes['class'] ?? '';
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->setType('password');
    }

    /**
     * Sets size of password element
     *
     * @param string $size Size of password field
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setSize($size)
    {
        $this->updateAttributes(array('size' => $size));
    }

    /**
     * Sets maxlength of password element
     *
     * @param string $maxlength Maximum length of password field
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setMaxlength($maxlength)
    {
        $this->updateAttributes(array('maxlength' => $maxlength));
    }

    /**
     * Returns the value of field without HTML tags (in this case, value is changed to a mask)
     *
     * @return    string
     * @throws
     * @since     1.0
     * @access    public
     */
    public function getFrozenHtml()
    {
        $value = $this->getValue();

        return ('' != $value ? '**********' : '&nbsp;').
            $this->_getPersistantData();
    }

    public function toHtml(): string
    {
        if (parent::isFrozen()) {
            return parent::getFrozenHtml();
        }

        $attributes = $this->getAttributes();

        $this->removeAttribute('show_hide');
        $this->_attributes['class'] = ($attributes['class'] ?? '').' p-password-input ';

        $input = parent::toHtml();

        if (empty($attributes['show_hide'])) {
            return $input;
        }

        $id = $attributes['id'] ?? '';

        return '<div class="p-password p-component p-inputwrapper">
                '.$input.'
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" class="p-icon p-password-toggle-mask-icon p-password-unmask-icon" aria-hidden="true" data-pc-section="unmaskicon">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M0.0535499 7.25213C0.208567 7.59162 2.40413 12.4 7 12.4C11.5959 12.4 13.7914 7.59162 13.9465 7.25213C13.9487 7.2471 13.9506 7.24304 13.952 7.24001C13.9837 7.16396 14 7.08239 14 7.00001C14 6.91762 13.9837 6.83605 13.952 6.76001C13.9506 6.75697 13.9487 6.75292 13.9465 6.74788C13.7914 6.4084 11.5959 1.60001 7 1.60001C2.40413 1.60001 0.208567 6.40839 0.0535499 6.74788C0.0512519 6.75292 0.0494023 6.75697 0.048 6.76001C0.0163137 6.83605 0 6.91762 0 7.00001C0 7.08239 0.0163137 7.16396 0.048 7.24001C0.0494023 7.24304 0.0512519 7.2471 0.0535499 7.25213ZM7 11.2C3.664 11.2 1.736 7.92001 1.264 7.00001C1.736 6.08001 3.664 2.80001 7 2.80001C10.336 2.80001 12.264 6.08001 12.736 7.00001C12.264 7.92001 10.336 11.2 7 11.2ZM5.55551 9.16182C5.98308 9.44751 6.48576 9.6 7 9.6C7.68891 9.59789 8.349 9.32328 8.83614 8.83614C9.32328 8.349 9.59789 7.68891 9.59999 7C9.59999 6.48576 9.44751 5.98308 9.16182 5.55551C8.87612 5.12794 8.47006 4.7947 7.99497 4.59791C7.51988 4.40112 6.99711 4.34963 6.49276 4.44995C5.98841 4.55027 5.52513 4.7979 5.16152 5.16152C4.7979 5.52513 4.55027 5.98841 4.44995 6.49276C4.34963 6.99711 4.40112 7.51988 4.59791 7.99497C4.7947 8.47006 5.12794 8.87612 5.55551 9.16182ZM6.2222 5.83594C6.45243 5.6821 6.7231 5.6 7 5.6C7.37065 5.6021 7.72553 5.75027 7.98762 6.01237C8.24972 6.27446 8.39789 6.62934 8.4 7C8.4 7.27689 8.31789 7.54756 8.16405 7.77779C8.01022 8.00802 7.79157 8.18746 7.53575 8.29343C7.27994 8.39939 6.99844 8.42711 6.72687 8.37309C6.4553 8.31908 6.20584 8.18574 6.01005 7.98994C5.81425 7.79415 5.68091 7.54469 5.6269 7.27312C5.57288 7.00155 5.6006 6.72006 5.70656 6.46424C5.81253 6.20842 5.99197 5.98977 6.2222 5.83594Z" fill="currentColor"></path>
                </svg>
            </div>'
            ."<script>$('input#$id + .p-password-toggle-mask-icon').click(() => {
              var txtPasswd = $('input#$id')
              txtPasswd.attr('type', txtPasswd.attr('type') === 'password' ? 'text' : 'password');
            })</script>";
    }
}
