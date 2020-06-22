<?php

/**
 * Base class for <input /> form elements
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
 * @version     CVS: $Id: input.php,v 1.10 2009/04/04 21:34:03 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * Base class for <input /> form elements
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 * @since       1.0
 * @abstract
 */
class HTML_QuickForm_input extends HTML_QuickForm_element
{
    /**
     * Sets the element type
     *
     * @param     string    $type   Element type
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setType($type)
    {
        $this->_type = $type;
        $this->updateAttributes(array('type'=>$type));
    }

    /**
     * Sets the input field name
     *
     * @param     string    $name   Input field name attribute
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setName($name)
    {
        $this->updateAttributes(array('name'=>$name));
    }

    /**
     * Returns the element name
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Sets the value of the form element
     *
     * @param     string    $value      Default value of the form element
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setValue($value)
    {
        $this->updateAttributes(array('value' => $value));
    }

    /**
     * Returns the value of the form element
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function getValue()
    {
        return $this->getAttribute('value');
    }

    /**
     * Returns the input field in HTML
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function toHtml()
    {
        if ($this->isFrozen()) {
            return $this->getFrozenHtml();
        }

        return $this->_getTabs().'<input'.$this->_getAttrString($this->_attributes).' />';
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
            ('submit' != $type && 'reset' != $type && 'image' != $type && 'button' != $type)) {
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
    * We don't need values from button-type elements (except submit) and files
    */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $type = $this->getType();
        if ('reset' === $type || 'image' === $type || 'button' === $type || 'file' === $type) {
            return null;
        }

        return parent::exportValue($submitValues, $assoc);
    }
}
