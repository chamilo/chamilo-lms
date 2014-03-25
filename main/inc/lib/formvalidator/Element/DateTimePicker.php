<?php
/* For licensing terms, see /license.txt */
require_once 'HTML/QuickForm/date.php';

/**
 * Form element to select a date and hour (with popup datepicker)
 */
class DateTimePicker extends HTML_QuickForm_text
{
    public $addLibrary = false;
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
		$this->_type = 'datetimepicker';
	}

	/**
	 * HTML code to display this datepicker
	 */
	public function toHtml()
	{
		$js = $this->getElementJS();
		return $js.parent::toHtml();
	}

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

        if ($this->addLibrary == true) {
            $js .= '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/datetimepicker/jquery-ui-timepicker-addon.js" type="text/javascript"></script>';
            $js .='<link  href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/datetimepicker/jquery-ui-timepicker-addon.css" rel="stylesheet" type="text/css" />';

            $isocode = api_get_language_isocode();
            if ($isocode != 'en') {
                $js .= '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/datetimepicker/i18n/jquery-ui-timepicker-'.$isocode.'.js" type="text/javascript"></script>';

                $js .= api_get_js('jquery-ui/jquery-ui-i18n.min.js');
                $js .= '<script>
                $(function(){
                    $.datepicker.setDefaults($.datepicker.regional["'.$isocode.'"]);
                });
                </script>';
            }
        }

        $id = $this->getAttribute('id');
        //timeFormat: 'hh:mm'
        $js .= "<script>
            $(function() {
                $('#$id').datetimepicker({
                    dateFormat: 'yy-mm-dd'
                });
            });
        </script>";
		return $js;
	}

}
