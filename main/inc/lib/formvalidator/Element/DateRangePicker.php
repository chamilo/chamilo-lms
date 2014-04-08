<?php
/* For licensing terms, see /license.txt */
require_once 'HTML/QuickForm/date.php';

/**
 * Form element to select a date and hour (with popup datepicker)
 */
class DateRangePicker extends HTML_QuickForm_text
{
    public $addLibrary = false;
	/**
	 * Constructor
	 */
	public function DateRangePicker($elementName = null, $elementLabel = null, $attributes = null)
	{
        if (!isset($attributes['id'])) {
            $attributes['id'] = $elementName;
        }
        $attributes['class'] = 'span3';
		HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
		$this->_appendName = true;
		$this->_type = 'date_range_picker';
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
        $this->updateAttributes(
            array(
                'value' => $value
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
            $js .= '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/daterange/moment.min.js" type="text/javascript"></script>';
            $js .= '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/daterange/daterangepicker.js" type="text/javascript"></script>';
            $js .='<link  href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/daterange/daterangepicker-bs2.css" rel="stylesheet" type="text/css" />';

            $isocode = api_get_language_isocode();
            if ($isocode != 'en') {
                $js .= api_get_js('jquery-ui/jquery-ui-i18n.min.js');
                $js .= '<script>
                $(function(){
                    moment.lang("'.$isocode.'");
                });
                </script>';
            }
        }

        $id = $this->getAttribute('id');
        //timeFormat: 'hh:mm'
        $js .= "<script>
            $(function() {
                $('#$id').daterangepicker({
                    format: 'YYYY-MM-DD HH:mm',
                    timePicker: true,
                    timePickerIncrement: 30,
                    timePicker12Hour: false,
                    ranges: {
                         '".get_lang('Today')."': [moment(), moment()],
                         '".get_lang('ThisWeek')."': [moment().weekday(1), moment().weekday(5)],
                         '".get_lang('NextWeek')."': [moment().weekday(8), moment().weekday(12)]
                    },
                    //showDropdowns : true,
                    separator: ' / ',
                    locale: {
                        applyLabel: '".get_lang('Apply')."',
                        cancelLabel: '".get_lang('Cancel')."',
                        fromLabel: '".get_lang('From')."',
                        toLabel: '".get_lang('To')."',
                        customRangeLabel: '".get_lang('CustomRange')."',
                    }
                });
            });
        </script>";

		return $js;
	}

    /**
     * @param array $dateRange

     * @return array
     */
    function parseDateRange($dateRange)
    {
        $dates = explode('/', $dateRange);
        $dates = array_map('trim', $dates);

        return array(
            'start' => $dates[0],
            'end' => $dates[1]
        );
    }

    /**
     * @param array $dates result of parseDateRange()
     * @return bool
     */
    function validateDates($dates)
    {

        if (empty($dates['start']) || empty($dates['end'])) {
            return false;
        }
        $format = 'Y-m-d H:i';
        $d = DateTime::createFromFormat($format, $dates['start']);
        $resultStart = $d && $d->format($format) == $dates['start'];

        $d = DateTime::createFromFormat($format, $dates['end']);
        $resultEnd = $d && $d->format($format) == $dates['end'];

        if (!($resultStart) || !$resultEnd) {
            return false;
        }

        return true;
    }



}
