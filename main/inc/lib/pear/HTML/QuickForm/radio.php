<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * HTML class for a radio type element
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
 * @version     CVS: $Id: radio.php,v 1.20 2009/04/04 21:34:04 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * HTML class for a radio type element
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_radio extends HTML_QuickForm_input
{
    /**
     * Radio display text
     * @var       string
     * @since     1.1
     * @access    private
     */
    public $_text = '';
    public $labelClass;
    public $radioClass;

    /**
     * Class constructor
     *
     * @param     string    Input field name attribute
     * @param     mixed     Label(s) for a field
     * @param     string    Text to display near the radio
     * @param     string    Input field value
     * @param     mixed     Either a typical HTML attribute string or an associative array
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function __construct(
        $elementName = null,
        $elementLabel = null,
        $text = null,
        $value = null,
        $attributes = null
    ) {
        $this->labelClass = isset($attributes['label-class']) ? $attributes['label-class'] : '';
        $this->radioClass = isset($attributes['radio-class']) ? $attributes['radio-class'] : 'radio';

        if (isset($attributes['label-class'])) {
            unset($attributes['label-class']);
        }

        if (isset($attributes['radio-class'])) {
            unset($attributes['radio-class']);
        }

        $columnsSize = isset($attributes['cols-size']) ? $attributes['cols-size'] : null;
        $this->setColumnsSize($columnsSize);

        parent::__construct($elementName, $elementLabel, $attributes);
        if (isset($value)) {
            $this->setValue($value);
        }
        $this->_persistantFreeze = true;
        $this->setType('radio');
        $this->_text = $text;
        $this->_generateId();
    }

    /**
     * Sets whether radio button is checked
     *
     * @param     bool    $checked  Whether the field is checked or not
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setChecked($checked)
    {
        if (!$checked) {
            $this->removeAttribute('checked');
        } else {
            $this->updateAttributes(array('checked'=>'checked'));
        }
    }

    /**
     * Returns whether radio button is checked
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function getChecked()
    {
        return $this->getAttribute('checked');
    }

    /**
     * Returns the radio element in HTML
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function toHtml()
    {
        if (0 == strlen($this->_text)) {
            $label = '';
        } elseif ($this->_flagFrozen) {
            $label = $this->_text;
        } else {
            $labelClass = $this->labelClass;
            $radioClass = $this->radioClass;

            $label = '<div class="'.$radioClass.'">
                <label class="'.$labelClass.'">' .
                HTML_QuickForm_input::toHtml().
                ''.
                $this->_text .
                '</label>&nbsp;&nbsp;
            </div>';

            return  $label;
        }

        return HTML_QuickForm_input::toHtml() . $label;
    }

    /**
     * Returns the value of field without HTML tags
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function getFrozenHtml()
    {
        if ($this->getChecked()) {
            return '<code>(x)</code>' .
                   $this->_getPersistantData();
        } else {
            return '<code>( )</code>';
        }
    }

    /**
     * Sets the radio text
     *
     * @param     string    $text  Text to display near the radio button
     * @since     1.1
     * @access    public
     * @return    void
     */
    public function setText($text)
    {
        $this->_text = $text;
    }

    /**
     * Returns the radio text
     *
     * @since     1.1
     * @access    public
     * @return    string
     */
    public function getText()
    {
        return $this->_text;
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
     */
    public function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'updateValue':
                // constant values override both default and submitted ones
                // default values are overriden by submitted
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value) {
                    $value = $this->_findValue($caller->_submitValues);
                    if (null === $value) {
                        $value = $this->_findValue($caller->_defaultValues);
                    }
                }
                if (!is_null($value) && $value == $this->getValue()) {
                    $this->setChecked(true);
                } else {
                    $this->setChecked(false);
                }
                break;
            case 'setGroupValue':
                if ($arg == $this->getValue()) {
                    $this->setChecked(true);
                } else {
                    $this->setChecked(false);
                }
                break;
            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }

        return true;
    }

   /**
    * Returns the value attribute if the radio is checked, null if it is not
    */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        if (null === $value) {
            $value = $this->getChecked()? $this->getValue(): null;
        } elseif ($value != $this->getValue()) {
            $value = null;
        }

        return $this->_prepareValue($value, $assoc);
    }

      /**
     * @return null
     */
    public function getColumnsSize()
    {
        return $this->columnsSize;
    }
}
