<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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
	// {{{ properties
	/* Path to image */

	
    // {{{ constructor
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
    function HTML_QuickForm_stylebutton($elementName=null, $elementLabel=null, $attributes=null)
    {
        $this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        
    } //end constructor

    // }}}
    // {{{ setType()

    /**
     * Sets the element type
     *
     * @param     string    $type   Element type
     * @since     1.0
     * @access    public
     * @return    void
     */
  
  
    /* Returns an HTML formatted attribute string
     * @param    array   $attributes
     * @return   string
     * @access   private
     */ 
    function _getAttrString($attributes)
    {
        $strAttr = '';

        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
            	if ($key != 'value') $strAttr .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
        }
        return $strAttr;
    } // end func _getAttrString
  
    
    function setType($type)
    {
        $this->_type = $type;
        $this->updateAttributes(array('type'=>$type));
    } // end func setType
    
    // }}}
    // {{{ setName()

    /**
     * Sets the input field name
     * 
     * @param     string    $name   Input field name attribute
     * @since     1.0
     * @access    public
     * @return    void
     */
    function setName($name)
    {
        $this->updateAttributes(array('name'=>$name));
    } //end func setName
    
    // }}}
    // {{{ getName()

    /**
     * Returns the element name
     * 
     * @since     1.0
     * @access    public
     * @return    string
     */
    function getName()
    {
        return $this->getAttribute('name');
    } //end func getName
    
    // }}}
    // {{{ setValue()

    /**
     * Sets the value of the form element
     *
     * @param     string    $value      Default value of the form element
     * @since     1.0
     * @access    public
     * @return    void
     */
    function setValue($value)
    {
        $this->updateAttributes(array('value'=>$value));
    } // end func setValue

    // }}}
    // {{{ getValue()

    /**
     * Returns the value of the form element
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    function getValue()
    {
        //return $this->getAttribute('value');
	    return $this->_attributes['value'];
    } // end func getValue
    
    // }}}
    // {{{ toHtml()

    /**
     * Returns the input field in HTML
     * 
     * @since     1.0
     * @access    public
     * @return    string
     */
    function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
            return $this->_getTabs() . '<button' . $this->_getAttrString($this->_attributes) . ' >'.$this->getValue() .'</button>';
        }
    } //end func toHtml

    // }}}
    // {{{ onQuickFormEvent()

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
    function onQuickFormEvent($event, $arg, &$caller)
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
    } // end func onQuickFormEvent

    // }}}
    // {{{ exportValue()

   /**
    * We don't need values from button-type elements (except submit) and files
    */
    function exportValue(&$submitValues, $assoc = false)
    {
        $type = $this->getType();
        if ('reset' == $type || 'button' == $type) {
            return null;
        } else {
            return parent::exportValue($submitValues, $assoc);
        }
    }
    
    // }}}
} // end class HTML_QuickForm_element
?>
