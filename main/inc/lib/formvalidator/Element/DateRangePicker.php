<?php
/* For licensing terms, see /license.txt */

require_once 'HTML/QuickForm/date.php';

/**
 * Form element to select a date and hour (with popup datepicker)
 */
class DateRangePicker extends HTML_QuickForm_text
{
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

        //timeFormat: 'hh:mm'
        $js .= "<script>
            $(function() {
                $('#$id').daterangepicker({
                    format: 'YYYY-MM-DD HH:mm',
                    timePicker: true,
                    timePickerIncrement: 30,
                    timePicker12Hour: false,
                    $defaultDates
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

        return array(
            'start' => $dates[0],
            'end' => $dates[1]
        );
    }

    /**
    * @param array $dates result of parseDateRange()
    *
    * @return bool
    */
    public function validateDates($dates)
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
