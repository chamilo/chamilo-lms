<?php
/* For licensing terms, see /license.txt */

/**
 * Float element.
 *
 * Accepts values like 3.1415 and 3,1415 (its processed and converted to 3.1415)
 *
 * Class Float
 */
class FloatNumber extends HTML_QuickForm_text
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

        $attributes['type'] = 'float';
        $attributes['class'] = 'form-control';

        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_appendName = true;
        $this->setType('float');
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $value = api_float_val($value);
        $this->updateAttributes(
            [
                'value' => $value,
            ]
        );
    }

    /**
     * @return float
     */
    public function getValue()
    {
        $value = $this->getAttribute('value');
        $value = api_float_val($value);

        return $value;
    }

    /**
     * @param mixed $value
     * @param array $submitValues
     * @param array $errors
     */
    public function getSubmitValue($value, &$submitValues, &$errors)
    {
        $value = api_float_val($value);
        $elementName = $this->getName();
        $submitValues[$elementName] = $value;

        return $value;
    }

    /**
     * We check the options and return only the values that _could_ have been
     * selected. We also return a scalar value if select is not "multiple".
     */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        $value = api_float_val($value);
        if (!$value) {
            $value = '';
        }

        return $this->_prepareValue($value, $assoc);
    }
}
