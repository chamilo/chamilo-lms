<?php
/* For licensing terms, see /license.txt */

/**
 * Form element to select a date and hour (with popup datepicker)
 *
 * Class DatePicker
 */
class Number extends HTML_QuickForm_text
{
    /**
     * @param string $elementName
     * @param string $elementLabel
     * @param array  $attributes
     */
    public function Number($elementName = null, $elementLabel = null, $attributes = null)
    {
        if (!isset($attributes['id'])) {
            $attributes['id'] = $elementName;
        }
        
        $attributes['type'] = 'number';

        HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_appendName = true;
        $this->_type = 'number';
    }
}
