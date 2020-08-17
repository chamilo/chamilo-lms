<?php
/**
 * HTML class for a checkbox type field.
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
 *
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 *
 * @version     CVS: $Id: checkbox.php,v 1.23 2009/04/04 21:34:02 avb Exp $
 *
 * @see        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * HTML class for a checkbox type field.
 *
 * @category    HTML
 *
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @author      Alexey Borzov <avb@php.net>
 *
 * @version     Release: 3.2.11
 *
 * @since       1.0
 */
class HTML_QuickForm_checkbox extends HTML_QuickForm_input
{
    /**
     * Checkbox display text.
     *
     * @var string
     *
     * @since     1.1
     */
    public $_text = '';
    public $labelClass;
    public $checkboxClass;

    /**
     * Class constructor.
     *
     * @param string $elementName  (optional)Input field name attribute
     * @param string $elementLabel (optional)Input field value
     * @param string $text         (optional)Checkbox display text
     * @param mixed  $attributes   (optional)Either a typical HTML attribute string
     *                             or an associative array
     *
     * @since     1.0
     *
     * @return void
     */
    public function __construct(
        $elementName = null,
        $elementLabel = null,
        $text = '',
        $attributes = null
    ) {
        $this->labelClass = isset($attributes['label-class']) ? $attributes['label-class'] : '';
        $this->checkboxClass = isset($attributes['checkbox-class']) ? $attributes['checkbox-class'] : 'checkbox';

        if (isset($attributes['label-class'])) {
            unset($attributes['label-class']);
        }

        if (isset($attributes['checkbox-class'])) {
            unset($attributes['checkbox-class']);
        }

        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_text = $text;
        $this->setType('checkbox');

        if (!isset($attributes['value'])) {
            $this->updateAttributes(['value' => 1]);
        } else {
            $this->updateAttributes(['value' => $attributes['value']]);
        }

        $this->_generateId();
    }

    /**
     * Sets whether a checkbox is checked.
     *
     * @param bool $checked Whether the field is checked or not
     *
     * @since     1.0
     *
     * @return void
     */
    public function setChecked($checked)
    {
        if (!$checked) {
            $this->removeAttribute('checked');
        } else {
            $this->updateAttributes(['checked' => 'checked']);
        }
    }

    /**
     * Returns whether a checkbox is checked.
     *
     * @since     1.0
     *
     * @return bool
     */
    public function getChecked()
    {
        return (bool) $this->getAttribute('checked');
    }

    /**
     * Returns the checkbox element in HTML.
     *
     * @since     1.0
     *
     * @return string
     */
    public function toHtml()
    {
        if (0 == strlen($this->_text)) {
            $label = '';
        } elseif ($this->_flagFrozen) {
            $label = $this->_text;
        } else {
            $labelClass = $this->labelClass;
            $checkClass = $this->checkboxClass;
            $name = $this->_attributes['name'];
            $label = '<div id="'.$name.'" class="'.$checkClass.'">
                <label class="'.$labelClass.'">'.
                    HTML_QuickForm_input::toHtml().' '.$this->_text.
                '</label>
                </div>
            ';

            return $label;
        }

        return HTML_QuickForm_input::toHtml().$label;
    }

    /**
     * @param string $layout
     *
     * @return string
     */
    public function getTemplate($layout)
    {
        $size = $this->calculateSize();
        switch ($layout) {
            case FormValidator::LAYOUT_INLINE:
                return '
                <div class="input-group">
                    <label {label-for} >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>
                </div>
                <div class="input-group {error_class}">
                    {element}
                </div>
                ';
                break;
            case FormValidator::LAYOUT_HORIZONTAL:
                return '
                <div class="form-group {error_class}">
                    <label {label-for}  class="col-sm-'.$size[0].' control-label  {extra_label_class}" >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>
                    <div class="col-sm-'.$size[1].'">
                        {icon}
                        {element}

                        <!-- BEGIN label_2 -->
                            <p class="help-block">{label_2}</p>
                        <!-- END label_2 -->

                        <!-- BEGIN error -->
                            <span class="help-inline help-block">{error}</span>
                        <!-- END error -->
                    </div>
                    <div class="col-sm-'.$size[2].'">
                        <!-- BEGIN label_3 -->
                            {label_3}
                        <!-- END label_3 -->
                    </div>
                </div>';
                break;
            case FormValidator::LAYOUT_BOX_NO_LABEL:
                return '
                        <div class="input-group">
                            {icon}
                            {element}
                        </div>';
                break;
            case FormValidator::LAYOUT_GRID:
            case FormValidator::LAYOUT_BOX:
                return '
                        <div class="input-group">
                            <label>{label}</label>
                            {icon}
                            {element}
                        </div>';
                break;
        }
    }

    /**
     * Returns the value of field without HTML tags.
     *
     * @since     1.0
     *
     * @return string
     */
    public function getFrozenHtml()
    {
        if ($this->getChecked()) {
            return '<code>[x]</code>'.
                $this->_getPersistantData();
        }

        return '<code>[ ]</code>';
    }

    /**
     * Sets the checkbox text.
     *
     * @param string $text
     *
     * @since     1.1
     *
     * @return void
     */
    public function setText($text)
    {
        $this->_text = $text;
    }

    /**
     * Returns the checkbox text.
     *
     * @since     1.1
     *
     * @return string
     */
    public function getText()
    {
        return $this->_text;
    }

    /**
     * Sets the value of the form element.
     *
     * @param string $value Default value of the form element
     *
     * @since     1.0
     *
     * @return void
     */
    public function setValue($value)
    {
        return $this->setChecked($value);
    }

    /**
     * Returns the value of the form element.
     *
     * @since     1.0
     *
     * @return bool
     */
    public function getValue()
    {
        return $this->getChecked();
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element.
     *
     * @param string $event   Name of event
     * @param mixed  $arg     event arguments
     * @param object &$caller calling object
     *
     * @since     1.0
     *
     * @return void
     */
    public function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'updateValue':
                // constant values override both default and submitted ones
                // default values are overriden by submitted
                $value = $this->_findValue($caller->_constantValues);

                if (null === $value) {
                    // if no boxes were checked, then there is no value in the array
                    // yet we don't want to display default value in this case
                    if ($caller->isSubmitted()) {
                        $value = $this->_findValue($caller->_submitValues);
                    } else {
                        $value = $this->_findValue($caller->_defaultValues);
                    }
                }

                if (null !== $value || $caller->isSubmitted()) {
                    $this->setChecked($value);
                }
                break;
            case 'setGroupValue':
                $this->setChecked($arg);
                break;
            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }

        return true;
    }

    /**
     * Return true if the checkbox is checked, null if it is not checked (getValue() returns false).
     */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        if (null === $value) {
            $value = $this->getChecked() ? true : null;
        }

        return $this->_prepareValue($value, $assoc);
    }
}
