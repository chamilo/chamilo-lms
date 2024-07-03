<?php

/* For licensing terms, see /license.txt */

/**
 * Form element to select a range of dates (with popup datepicker).
 */
class DateRangePicker extends HTML_QuickForm_text
{
    /**
     * DateRangePicker constructor.
     *
     * @param string       $elementName
     * @param string|array $elementLabel
     * @param array        $attributes
     */
    public function __construct($elementName, $elementLabel = null, $attributes = null)
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
            [
                'value' => $value,
            ]
        );
    }

    /**
     * @param array $dateRange
     *
     * @return array
     */
    public function parseDateRange($dateRange)
    {
        $dateRange = Security::remove_XSS($dateRange);
        $dates = explode('/', $dateRange);
        $dates = array_map('trim', $dates);
        $start = isset($dates[0]) ? $dates[0] : '';
        $end = isset($dates[1]) ? $dates[1] : '';

        $pattern = 'yyyy-MM-dd HH:mm';
        if ('false' === $this->getAttribute('timePicker') &&
            false === strpos($this->getAttribute('format'), 'HH:mm')) {
            $pattern = 'yyyy-MM-dd';
        }

        $formatter = new IntlDateFormatter(
            'en',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'UTC',
            IntlDateFormatter::GREGORIAN,
            $pattern
        );
        $resultStart = $formatter->format($formatter->parse($start));
        $resultEnd = $formatter->format($formatter->parse($end));

        return [
            'start' => $resultStart,
            'end' => $resultEnd,
        ];
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

        if (!$resultStart || !$resultEnd) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $value
     * @param array $submitValues
     * @param array $errors
     *
     * @return string
     */
    public function getSubmitValue($value, &$submitValues, &$errors)
    {
        /** @var DateRangePicker $element */
        $elementName = $this->getName();
        $parsedDates = $this->parseDateRange($value);
        $validateFormat = $this->getAttribute('validate_format');

        if (!$this->validateDates($parsedDates, $validateFormat)) {
            $errors[$elementName] = get_lang('CheckDates');
        }
        $submitValues[$elementName.'_start'] = $parsedDates['start'];
        $submitValues[$elementName.'_end'] = $parsedDates['end'];

        return $value;
    }

    /**
     * Get the necessary javascript for this datepicker.
     *
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
        $minDateValue = Security::remove_XSS($this->getAttribute('minDate'));
        if (!empty($minDateValue)) {
            $minDate = "
                minDate: '{$minDateValue}',
            ";
        }

        $maxDate = null;
        $maxDateValue = Security::remove_XSS($this->getAttribute('maxDate'));
        if (!empty($maxDateValue)) {
            $maxDate = "
                maxDate: '{$maxDateValue}',
            ";
        }

        $format = 'YYYY-MM-DD HH:mm';
        $formatValue = Security::remove_XSS($this->getAttribute('format'));
        if (!empty($formatValue)) {
            $format = $formatValue;
        }

        $timePicker = 'true';
        $timePickerValue = Security::remove_XSS($this->getAttribute('timePicker'));
        if (!empty($timePickerValue)) {
            $timePicker = 'false';
        }

        $timeIncrement = FormValidator::getTimepickerIncrement();

        // timeFormat: 'hh:mm'
        $js .= "<script>
            $(function() {
                $('#$id').daterangepicker({
                    timePicker: $timePicker,
                    timePickerIncrement: $timeIncrement,
                    timePicker24Hour: true,
                    $defaultDates
                    $maxDate
                    $minDate
                    ranges: {
                         '".addslashes(get_lang('Today'))."': [moment(), moment()],
                         '".addslashes(get_lang('Yesterday'))."': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                         '".addslashes(get_lang('ThisMonth'))."': [moment().startOf('month'), moment().endOf('month')],
                         '".addslashes(get_lang('LastMonth'))."': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                         '".addslashes(get_lang('ThisWeek'))."': [moment().weekday(1), moment().weekday(5)],
                         '".addslashes(get_lang('NextWeek'))."': [moment().weekday(8), moment().weekday(12)]
                    },
                    //showDropdowns : true,

                    locale: {
                        separator: ' / ',
                        format: '$format',
                        applyLabel: '".addslashes(get_lang('Ok'))."',
                        cancelLabel: '".addslashes(get_lang('Cancel'))."',
                        fromLabel: '".addslashes(get_lang('From'))."',
                        toLabel: '".addslashes(get_lang('Until'))."',
                        customRangeLabel: '".addslashes(get_lang('CustomRange'))."',
                    }
                });

                $('#$id').on('change', function() {
                    var myPickedDates = $('#$id').val().split('/');
                    var {$id}_start = myPickedDates[0].trim();
                    var {$id}_end = myPickedDates[1].trim();

                    $('input[name={$id}_start]').val({$id}_start);
                    $('input[name={$id}_end]').val({$id}_end);
                });
            });
        </script>";

        return $js;
    }
}
