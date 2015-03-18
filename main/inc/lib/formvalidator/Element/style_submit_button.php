<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * HTML class for a submit type element
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
 * @copyright   2001-2007 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: submit.php 17344 2008-12-17 08:55:29Z Scara84 $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * HTML class for a submit type element
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.10
 * @since       1.0
 */
class HTML_QuickForm_style_submit_button extends HTML_QuickForm_input
{
    /**
     * Class constructor
     *
     * @param     string    Input field name attribute
     * @param     string    Input field value
     * @param     mixed     Either a typical HTML attribute string or an associative array
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function __construct($elementName = null, $value = null, $attributes = null, $img = null)
    {
        if (empty($attributes)) {
            $attributes = array();
        }

        if (!isset($attributes['class'])) {
            if (is_array($attributes)) {
                $attributes['class'] = 'btn';
            }
        }
        parent::__construct($elementName, null, $attributes, $value, $img);
        $this->setValue($value);
        $this->setType('submit');
    }

    /**
     * Freeze the element so that only its value is returned
     *
     * @access    public
     * @return    void
     */
    public function freeze()
    {
        return false;
    }

   /**
    * Only return the value if it is found within $submitValues (i.e. if
    * this particular submit button was clicked)
    */
    public function exportValue(&$submitValues, $assoc = false)
    {
        return $this->_prepareValue($this->_findValue($submitValues), $assoc);
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
            //Adding the btn class
            if (isset($this->_attributes['class'])) {
                $this->_attributes['class'] = 'btn '.$this->_attributes['class'];
            }
            return '<button ' . $this->_getAttrString($this->_attributes) . ' >'.$this->getValue() .'</button>';
        }
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string    $event  Name of event
     * @param     mixed     $arg    event arguments
     * @param     object    &$caller calling object
     * @since     1.0
     * @access    public
     * @return    void
     * @throws
     */
    public function onQuickFormEvent($event, $arg, &$caller)
    {
        // do not use submit values for button-type elements
        $type = $this->getType();
        if (('updateValue' != $event) ||
            ('submit' != $type && 'reset' != $type && 'button' != $type)) {
            parent::onQuickFormEvent($event, $arg, $caller);
        } else {
            $value = $this->_findValue($caller->_constantValues);
            if (null === $value) {
                $value = $this->_findValue($caller->_defaultValues);
            }
            if (null !== $value) {
                $this->setValue($value);
            }
        }

        return true;
    }

    /**
     * Returns an HTML formatted attribute string
    * @param    array   $attributes
    * @return   string
    * @access   private
    */
    public function _getAttrString($attributes)
    {
        $strAttr = '';
        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                if ($key != 'value') $strAttr .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
        }

        return $strAttr;
    }

}
