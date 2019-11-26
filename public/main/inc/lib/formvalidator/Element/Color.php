<?php
/* For licensing terms, see /license.txt */

/**
 * Input Color element.
 *
 * Class Color
 */
class Color extends HTML_QuickForm_text
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

        $attributes['type'] = 'color';
        $attributes['class'] = 'form-control';
        $attributes['cols-size'] = isset($attributes['cols-size']) ? $attributes['cols-size'] : [2, 1, 9];

        parent::__construct($elementName, $elementLabel, $attributes);

        $this->_appendName = true;
        $this->setType('color');
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        return parent::toHtml().<<<JS
            <script>
                $(function() {
                    var txtColor = $('#{$this->getAttribute('id')}'),
                        lblColor = txtColor.parent().next();

                    lblColor.text(txtColor.val());

                    txtColor.on('change', function () {
                        lblColor.text(txtColor.val());
                    })
                });
            </script>
JS;
    }
}
