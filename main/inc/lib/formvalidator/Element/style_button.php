<?php
/* For licensing terms, see /license.txt */

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
 * @copyright   2001-2007 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: input.php 17344 2008-12-17 08:55:29Z Scara84 $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * Base class for form elements
 */
require_once 'HTML/QuickForm/element.php';

/**
 * Base class for <button></button> form elements
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @abstract
 */
class HTML_QuickForm_stylebutton extends HTML_QuickForm_element
{
    /**
     * Class constructor
     *
     * @param    string     Input field name attribute
     * @param    mixed      Label(s) for the input field
     * @param    mixed      Either a typical HTML attribute string or an associative array
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function HTML_QuickForm_stylebutton($elementName = null, $elementLabel = null, $attributes = null)
    {
        $this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
    }

    /** Returns an HTML formatted attribute string
     * @param    array   $attributes
     * @return   string
     * @access   private
     */
    public function _getAttrString($attributes) {
        $strAttr = '';
        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
            	if ($key != 'value') {
                    $strAttr .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
                }
            }
        }
        return $strAttr;
    }

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
        $this->updateAttributes(array('value'=>$value));
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
        //return $this->getAttribute('value');
        return $this->_attributes['value'];
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
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
            // Adding the btn class
            if (isset($this->_attributes['class'])) {
                $this->_attributes['class'] = 'btn '.$this->_attributes['class'];
            }
            $addIcon = null;

            if (strpos($this->_attributes['class'], 'search')) {
                $this->_attributes['class'] = 'btn btn-primary';
                $addIcon = '<i class="fa fa-search fa-lg"></i> ';
            }

            if (strpos($this->_attributes['class'], 'minus')) {
                $this->_attributes['class'] = 'btn btn-danger';
                $addIcon = '<i class="fa fa-minus-circle fa-lg"></i> ';
            }

            if (strpos($this->_attributes['class'], 'plus')) {
                $this->_attributes['class'] = 'btn btn-success';
                $addIcon = '<i class="fa fa-plus-circle fa-lg"></i> ';
            }

            if (strpos($this->_attributes['class'], 'save')) {
                $this->_attributes['class'] = 'btn btn-primary';
                $addIcon = '<i class="fa fa-check fa-lg"></i> ';
            }
            if (strpos($this->_attributes['class'], 'add')) {
                $this->_attributes['class'] = 'btn btn-primary';
                $addIcon = '<i class="fa fa-plus"></i></i> ';
            }
            return $this->_getTabs().
                    '<button '.$this->_getAttrString($this->_attributes).' >'.$addIcon.$this->getValue() .'</button>';
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
     * We don't need values from button-type elements (except submit) and files
     * @param $submitValues
     * @param bool $assoc
     * @return mixed|null
     */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $type = $this->getType();
        if ('reset' == $type || 'button' == $type) {
            return null;
        } else {
            return parent::exportValue($submitValues, $assoc);
        }
    }
}
