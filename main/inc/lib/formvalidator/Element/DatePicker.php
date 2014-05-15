<?php
/* For licensing terms, see /license.txt */

require_once 'HTML/QuickForm/date.php';

/**
 * Form element to select a date and hour (with popup datepicker)
 *
 * Class DatePicker
 */
class DatePicker extends HTML_QuickForm_text
{
    /**
     * @param string $elementName
     * @param string $elementLabel
     * @param array  $attributes
     */
    public function DatePicker($elementName = null, $elementLabel = null, $attributes = null)
    {
        if (!isset($attributes['id'])) {
            $attributes['id'] = $elementName;
        }

        HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_appendName = true;
        $this->_type = 'date_picker';
    }

    /**
    * HTML code to display this datepicker
     *
     * @return string
    */
    public function toHtml()
    {
        $js = $this->getElementJS();

        return $js.parent::toHtml();
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $value = substr($value, 0, 16);
        $this->updateAttributes(
            array(
                'value' => $value
            )
        );
    }

    /**
     * Get the necessary javascript for this datepicker
     * @return string
     */
    private function getElementJS()
    {
        $js = null;
        $id = $this->getAttribute('id');

        $js .= "<script>
            $(function() {
                $('#$id').datepicker({
                    dateFormat: 'yy-mm-dd'
                });
            });
        </script>";

        return $js;
    }

}
