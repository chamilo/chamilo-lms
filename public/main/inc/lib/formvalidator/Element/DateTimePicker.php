<?php

/* For licensing terms, see /license.txt */

/**
 * Form element to select a date and hour.
 */
class DateTimePicker extends HTML_QuickForm_text
{
    /**
     * DateTimePicker constructor.
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
    }

    /**
     * HTML code to display this datepicker.
     *
     * @return string
     */
    public function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }

        $id = $this->getAttribute('id');
        $value = $this->getValue();

        $formattedValue = '';
        if (!empty($value)) {
            $formattedValue = api_format_date($value, DATE_TIME_FORMAT_LONG_24H);
        }

        $label = $this->getLabel();
        if (is_array($label) && isset($label[0])) {
            $label = $label[0];
        }

        //$resetFieldX = sprintf(get_lang('Reset %s'), $label);

        return '
            <div id="div_'.$id.'" class="flex flex-row mt-1">
                <input '.$this->_getAttrString($this->_attributes).'
                    class="form-control" type="text" value="'.$value.'" data-input>
                <div class="ml-1" id="button-addon3">
                    <button class="btn btn-outline-secondary"  type="button" data-toggle>
                        <i class="fas fa-calendar-alt"></i>
                    </button>
                    <button class="btn btn-outline-secondary" type="button" data-clear>
                        <i class="fas fa-times"></i>
                    </button>
              </div>
            </div>
        '.$this->getElementJS();
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $value = substr($value, 0, 16);
        $this->updateAttributes(['value' => $value]);
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
        //timeFormat: 'hh:mm'
        $js .= "<script>
            $(function() {
                var config = {
                    altInput: true,
                    altFormat: '".get_lang('F d, Y')." ".get_lang('at')." H:i',
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i',
                    time_24hr: true,
                    wrap: false,
                    locale: {
                      firstDayOfWeek: 1
                    }
                };
                $('#{$id}').flatpickr(config);

                /*

                var txtDateTime = $('#$id'),
                    inputGroup = txtDateTime.parents('.input-group'),
                    txtDateTimeAlt = $('#{$id}_alt'),
                    txtDateTimeAltText = $('#{$id}_alt_text');
                txtDateTime
                    .hide()
                    .datetimepicker({
                        defaultDate: '".$this->getValue()."',
                        dateFormat: 'yy-mm-dd',
                        timeFormat: 'HH:mm',
                        altField: '#{$id}_alt',
                        altFormat: \"".get_lang('MM dd, yy')."\",
                        altTimeFormat: \"".get_lang('HH:mm')."\",
                        altSeparator: \" ".get_lang(' at')." \",
                        altFieldTimeOnly: false,
                        showOn: 'both',
                        buttonImage: '".Display::return_icon('attendance.png', null, [], ICON_SIZE_TINY, true, true)."',
                        buttonImageOnly: true,
                        buttonText: '".get_lang('Select date')."',
                        changeMonth: true,
                        changeYear: true
                    })
                    .on('change', function (e) {
                        txtDateTimeAltText.text(txtDateTimeAlt.val());
                    });

                txtDateTimeAltText.on('click', function () {
                    txtDateTime.datepicker('show');
                });

                inputGroup
                    .find('button')
                    .on('click', function (e) {
                        e.preventDefault();

                        $('#$id, #{$id}_alt').val('');
                        $('#{$id}_alt_text').html('');
                    });
                */
            });
        </script>";

        return $js;
    }
}
