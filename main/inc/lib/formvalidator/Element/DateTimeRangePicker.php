<?php
/* For licensing terms, see /license.txt */

/**
 * Form element to select a date.
 *
 * Class DatePicker
 */
class DateTimeRangePicker extends DateRangePicker
{
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
        $label = $this->getLabel();

        if (!empty($value)) {
            $value = api_format_date($value, DATE_FORMAT_LONG_NO_DAY);
        }

        return '
            <div class="input-group">
                <span class="input-group-addon cursor-pointer">
                    <input '.$this->_getAttrString($this->_attributes).'>
                </span>
                <p class="form-control disabled" id="'.$id.'_alt_text">'.$value.'</p>
                <input class="form-control" type="hidden" id="'.$id.'_alt" value="'.$value.'">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button"
                            title="'.sprintf(get_lang('ResetFieldX'), $this->_label).'">
                        <span class="fa fa-trash text-danger" aria-hidden="true"></span>
                        <span class="sr-only">'.sprintf(get_lang('ResetFieldX'), $this->_label).'</span>
                    </button>
                </span>                
            </div>
            <div class="input-group">
                <br />
                <p id="'.$id.'_time_range">
                    <input type="text" name="'.$id.'_time_range_start" class="time start" autocomplete="off"> 
                    '.get_lang('To').'
                    <input type="text" name="'.$id.'_time_range_end" class="time end " autocomplete="off">
                </p>
            </div>
        '.$this->getElementJS();
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

        $js .= "<script>                    
            $(function() {
                var txtDate = $('#$id'),
                    inputGroup = txtDate.parents('.input-group'),
                    txtDateAlt = $('#{$id}_alt'),
                    txtDateAltText = $('#{$id}_alt_text');
                    
                txtDate
                    .hide()
                    .datepicker({
                        defaultDate: '".$this->getValue()."',
                        dateFormat: 'yy-mm-dd',
                        altField: '#{$id}_alt',
                        altFormat: \"".get_lang('DateFormatLongNoDayJS')."\",
                        showOn: 'both',
                        buttonImage: '".Display::return_icon('attendance.png', null, [], ICON_SIZE_TINY, true, true)."',
                        buttonImageOnly: true,
                        buttonText: '".get_lang('SelectDate')."',
                        changeMonth: true,
                        changeYear: true,
                        yearRange: 'c-60y:c+5y'
                    })
                    .on('change', function (e) {
                        txtDateAltText.text(txtDateAlt.val());
                    });
                    
                txtDateAltText.on('click', function () {
                    txtDate.datepicker('show');
                });

                inputGroup
                    .find('button')
                    .on('click', function (e) {
                        e.preventDefault();

                        $('#$id, #{$id}_alt').val('');
                        $('#{$id}_alt_text').html('');
                    });
                
                $('#".$id."_time_range .time').timepicker({
                    'showDuration': true,
                    'timeFormat': 'H:i:s',
                    'scrollDefault': 'now',
                    
                });
                var timeOnlyExampleEl = document.getElementById('".$id."_time_range');
                var timeOnlyDatepair = new Datepair(timeOnlyExampleEl);
                
            });
        </script>";

        return $js;
    }
}
