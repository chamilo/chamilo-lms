<?php
/* For licensing terms, see /license.txt */

/**
 * Number element.
 *
 * Class Number
 */
class Number extends HTML_QuickForm_text
{
    /**
     * @param string $elementName
     * @param string $elementLabel
     * @param array  $attributes
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null)
    {
        if (!isset($attributes['id'])) {
            $attributes['id'] = $elementName;
        }

        $attributes['type'] = 'number';

        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_appendName = true;
        $this->setType('number');
    }
}
