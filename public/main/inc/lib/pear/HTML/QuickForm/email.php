<?php

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
    public function __construct(
        $elementName = null,
        $elementLabel = null,
        $attributes = null
    ) {
        if (is_array($attributes) || empty($attributes)) {
            $attributes['class'] = 'form-control';
        }
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->setType('email');
    }

    /**
     * Sets size of password element
     *
     * @param     string    $size  Size of password field
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setSize($size)
    {
        $this->updateAttributes(array('size'=>$size));
    }

    /**
     * Sets maxlength of password element
     *
     * @param     string    $maxlength  Maximum length of password field
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setMaxlength($maxlength)
    {
        $this->updateAttributes(array('maxlength'=>$maxlength));
    }
}
