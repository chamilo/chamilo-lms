<?php
/* For licensing terms, see /license.txt */
require_once 'HTML/QuickForm/date.php';

/**
 * Form element to select a date and hour (with popup datepicker)
 */
class DateTimePicker extends HTML_QuickForm_text
{
	/**
	 * Constructor
	 */
	public function DateTimePicker($elementName = null, $elementLabel = null, $attributes = null)
	{
        if (!isset($attributes['id'])) {
            $attributes['id'] = $elementName;
        }

		HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
		$this->_appendName = true;
		$this->_type = 'date_time_picker';
	}

	/**
	 * HTML code to display this datepicker
	 */
	public function toHtml()
	{
		$js = $this->getElementJS();
		return $js.parent::toHtml();
	}

    /**
     * @param string $value
     */
    function setValue($value)
    {
        $value = substr($value, 0, 16);
        $this->updateAttributes(
            array(
                'value'=>$value
            )
        );
    }

	/**
	 * Get the necessary javascript for this datepicker
	 */
	private function getElementJS()
	{
        $js = null;
        $id = $this->getAttribute('id');
        //timeFormat: 'hh:mm'
        $js .= "<script>
            $(function() {
                $('#$id').datetimepicker({
                    dateFormat: 'yy-mm-dd',
                    timeFormat: 'HH:mm'
                });
            });
        </script>";

		return $js;
	}
}
