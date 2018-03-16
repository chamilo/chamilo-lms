<?php
/* For licensing terms, see /license.txt */

/**
 * Url element.
 *
 * Class Url
 */
class Url extends HTML_QuickForm_text
{
    /**
     * Url constructor.
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

        $attributes['type'] = 'url';
        $attributes['class'] = 'form-control';

        parent::__construct($elementName, $elementLabel, $attributes);

        $this->setType('url');
    }
}
