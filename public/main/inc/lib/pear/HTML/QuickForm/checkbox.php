<?php

/**
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 *
 * @version     CVS: $Id: checkbox.php,v 1.23 2009/04/04 21:34:02 avb Exp $
 * @version     Release: 3.2.11
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
     * @param string $elementName  Input field name attribute
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
        ?array $attributes = []
    ) {
        $this->labelClass = $attributes['label-class'] ?? '';
        $this->checkboxClass = $attributes['checkbox-class'] ?? 'field-checkbox';

        if (isset($attributes['label-class'])) {
            unset($attributes['label-class']);
        }

        if (isset($attributes['checkbox-class'])) {
            unset($attributes['checkbox-class']);
        }

        $attributes['class'] = '  ';

        if (empty($text) && $elementLabel) {
            $text = $elementLabel;
            $elementLabel = null;
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
        $extraClass = "p-checkbox-input";

        if (isset($this->_attributes['class'])) {
            $this->_attributes['class'] .= $extraClass;
        } else {
            $this->_attributes['class'] = $extraClass;
        }

        if (0 == strlen($this->_text)) {
            $label = '';
        }

        if ($this->_flagFrozen) {
            $label = $this->_text;
        } else {
            $labelClass = $this->labelClass;
            $checkClass = $this->checkboxClass;
            $name = $this->_attributes['name'];
            $id = $this->getAttribute('id');

            // Ensure checkbox input has the expected class for styling.
            /*$existingClass = (string) $this->getAttribute('class');
            if (!str_contains($existingClass, 'p-checkbox-input')) {
                $this->setAttribute('class', trim($existingClass.' p-checkbox-input'));
            } */

            return '<div id="'.$name.'" class="field '.$checkClass.'">
                <div class="p-checkbox p-component '.($this->getChecked() ? 'p-checkbox-checked' : '').'">
                    '.parent::toHtml().'
                    <div class="p-checkbox-box">
                        <svg class="p-icon p-checkbox-icon" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                          <path d="M4.86199 11.5948C4.78717 11.5923 4.71366 11.5745 4.64596 11.5426C4.57826 11.5107 4.51779 11.4652 4.46827 11.4091L0.753985 7.69483C0.683167 7.64891 0.623706 7.58751 0.580092 7.51525C0.536478 7.44299 0.509851 7.36177 0.502221 7.27771C0.49459 7.19366 0.506156 7.10897 0.536046 7.03004C0.565935 6.95111 0.613367 6.88 0.674759 6.82208C0.736151 6.76416 0.8099 6.72095 0.890436 6.69571C0.970973 6.67046 1.05619 6.66385 1.13966 6.67635C1.22313 6.68886 1.30266 6.72017 1.37226 6.76792C1.44186 6.81567 1.4997 6.8786 1.54141 6.95197L4.86199 10.2503L12.6397 2.49483C12.7444 2.42694 12.8689 2.39617 12.9932 2.40745C13.1174 2.41873 13.2343 2.47141 13.3251 2.55705C13.4159 2.64268 13.4753 2.75632 13.4938 2.87973C13.5123 3.00315 13.4888 3.1292 13.4271 3.23768L5.2557 11.4091C5.20618 11.4652 5.14571 11.5107 5.07801 11.5426C5.01031 11.5745 4.9368 11.5923 4.86199 11.5948Z" fill="currentColor"></path>
                        </svg>
                    </div>
                </div>
                <label for="'.$id.'" class="'.$labelClass.'">
                    '.$this->_text.
                '</label>
            </div>';
        }

        return HTML_QuickForm_input::toHtml().$label;
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
