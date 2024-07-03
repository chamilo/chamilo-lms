<?php
/* For licensing terms, see /license.txt */

/**
 * InternalUrl element (URL without the domain as prefix).
 *
 * Class InternalUrl
 */
class InternalUrl extends HTML_QuickForm_text
{
    /**
     * InternalUrl constructor.
     *
     * @param string $elementName
     * @param string $elementLabel
     * @param array  $attributes
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null)
    {
        if (!isset($attributes['id'])) {
            $attributes['id'] = $elementName;
        }

        $attributes['type'] = 'text';
        $attributes['class'] = 'form-control';

        parent::__construct($elementName, $elementLabel, $attributes);

        $this->setType('text');
    }
}
