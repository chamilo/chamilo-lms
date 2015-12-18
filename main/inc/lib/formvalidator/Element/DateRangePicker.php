<?php
/* For licensing terms, see /license.txt */

/**
 * Form element to select a range of dates (with popup datepicker)
 */
class DateRangePicker extends HTML_QuickForm_text
{
    /**
    * Constructor
    */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null)
    {
        if (!isset($attributes['id'])) {
            $attributes['id'] = $elementName;
        }
        $attributes['class'] = 'form-control';
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_appendName = true;
        $this->_type = 'date_range_picker';
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        $js = $this->getElementJS();

        $this->removeAttribute('format');
        $this->removeAttribute('timepicker');
        $this->removeAttribute('validate_format');

        return $js.parent::toHtml();
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
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

        $dateRange = $this->getAttribute('value');

        $defaultDates = null;
        if (!empty($dateRange)) {
            $dates = $this->parseDateRange($dateRange);
            $defaultDates = "
                    startDate: '".$dates['start']."',
                    endDate: '".$dates['end']."', ";
        }

        $minDate = null;
        $minDateValue = $this->getAttribute('minDate');
        if (!empty($minDateValue)) {
            $minDate = "
                minDate: '{$minDateValue}',
            ";
        }

        $maxDate = null;
        $maxDateValue = $this->getAttribute('maxDate');
        if (!empty($maxDateValue)) {
            $maxDate = "
                maxDate: '{$maxDateValue}',
            ";
        }

        $format = 'YYYY-MM-DD HH:mm';
        $formatValue = $this->getAttribute('format');
        if (!empty($formatValue)) {
            $format = $formatValue;
        }

        $timePicker = 'true';
        $timePickerValue =  $this->getAttribute('timePicker');
        if (!empty($timePickerValue)) {
            $timePicker = $timePickerValue;
        }

        // timeFormat: 'hh:mm'
        $js .= "<script>
            $(function() {
                $('#$id').daterangepicker({
                    format: '$format',
                    timePicker: $timePicker,
                    timePickerIncrement: 30,
                    timePicker12Hour: false,
                    $defaultDates
                    $maxDate
                    $minDate
                    ranges: {
                         '".addslashes(get_lang('Today'))."': [moment(), moment()],
                         '".addslashes(get_lang('ThisWeek'))."': [moment().weekday(1), moment().weekday(5)],
                         '".addslashes(get_lang('NextWeek'))."': [moment().weekday(8), moment().weekday(12)]
                    },
                    //showDropdowns : true,
                    separator: ' / ',
                    locale: {
                        applyLabel: '".addslashes(get_lang('Ok'))."',
                        cancelLabel: '".addslashes(get_lang('Cancel'))."',
                        fromLabel: '".addslashes(get_lang('From'))."',
                        toLabel: '".addslashes(get_lang('Until'))."',
                        customRangeLabel: '".addslashes(get_lang('CustomRange'))."',
                    }
                });
            });
        </script>";

        return $js;
    }

    /**
     * @param array $dateRange
     *
     * @return array
     */
    public function parseDateRange($dateRange)
    {
        $dates = explode('/', $dateRange);
        $dates = array_map('trim', $dates);
        $start = isset($dates[0]) ? $dates[0] : '';
        $end = isset($dates[1]) ? $dates[1] : '';

        return array(
            'start' => $start,
            'end' => $end
        );
    }

    /**
    * @param array $dates result of parseDateRange()
    *
    * @return bool
    */
    public function validateDates($dates, $format = null)
    {
        if (empty($dates['start']) || empty($dates['end'])) {
            return false;
        }

        $format = $format ? $format : 'Y-m-d H:i';
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
