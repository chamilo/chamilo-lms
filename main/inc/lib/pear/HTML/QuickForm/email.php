<?php
/**
 * Base class for <input /> form elements
 */
require_once 'HTML/QuickForm/input.php';

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
class HTML_QuickForm_email extends HTML_QuickForm_input
{
    // {{{ constructor

    /**
     * Class constructor
     *
     * @param     string    $elementName    (optional)Input field name attribute
     * @param     string    $elementLabel   (optional)Input field label
     * @param     mixed     $attributes     (optional)Either a typical HTML attribute string
     *                                      or an associative array
     * @since     1.0
     * @access    public
     * @return    void
     * @throws
     */
    function HTML_QuickForm_email($elementName=null, $elementLabel=null, $attributes=null)
    {
        HTML_QuickForm_input::HTML_QuickForm_input($elementName, $elementLabel, $attributes);
        $this->setType('email');
    } //end constructor

    // }}}
    // {{{ setSize()

    /**
     * Sets size of password element
     *
     * @param     string    $size  Size of password field
     * @since     1.0
     * @access    public
     * @return    void
     */
    function setSize($size)
    {
        $this->updateAttributes(array('size'=>$size));
    } //end func setSize

    // }}}
    // {{{ setMaxlength()

    /**
     * Sets maxlength of password element
     *
     * @param     string    $maxlength  Maximum length of password field
     * @since     1.0
     * @access    public
     * @return    void
     */
    function setMaxlength($maxlength)
    {
        $this->updateAttributes(array('maxlength'=>$maxlength));
    } //end func setMaxlength

    // }}}
    // {{{ getFrozenHtml()

    // }}}

} //end class HTML_QuickForm_password