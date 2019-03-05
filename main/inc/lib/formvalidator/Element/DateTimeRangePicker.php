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
        '.$this->getElementJS();
    }

    /**
     * @param string $layout
     *
     * @return string
     */
    public function getTemplate($layout)
    {
        $size = $this->getColumnsSize();

        if (empty($size)) {
            $sizeTemp = $this->getInputSize();
            if (empty($size)) {
                $sizeTemp = 8;
            }
            $size = [2, $sizeTemp, 2];
        } else {
            if (is_array($size)) {
                if (count($size) != 3) {
                    $sizeTemp = $this->getInputSize();
                    if (empty($size)) {
                        $sizeTemp = 8;
                    }
                    $size = [2, $sizeTemp, 2];
                }
                // else just keep the $size array as received
            } else {
                $size = [2, intval($size), 2];
            }
        }

        $id = $this->getAttribute('id');

        switch ($layout) {
            case FormValidator::LAYOUT_INLINE:
                return '
                <div class="form-group {error_class}">
                    <label {label-for} >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>
                    {element}
                </div>';
                break;
            case FormValidator::LAYOUT_HORIZONTAL:
                return '
                <span id="'.$id.'_date_time_wrapper">
                <div class="form-group {error_class}">
                    <label {label-for} class="col-sm-'.$size[0].' control-label" >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>
                    <div class="col-sm-'.$size[1].'">
                        {icon}
                        {element}

                        <!-- BEGIN label_2 -->
                        <p class="help-block">{label_2}</p>
                        <!-- END label_2 -->

                        <!-- BEGIN error -->
                        <span class="help-inline help-block">{error}</span>
                        <!-- END error -->
                    </div>
                    <div class="col-sm-'.$size[2].'">
                        <!-- BEGIN label_3 -->
                            {label_3}
                        <!-- END label_3 -->
                    </div>            
                </div>                
                <div class="form-group {error_class}">
                    <label class="col-sm-'.$size[0].' control-label" >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        '.get_lang('Hour').'
                    </label>
                    <div class="col-sm-'.$size[1].'"> 
                        <div class="input-group"> 
                            <p id="'.$id.'_time_range">                    
                                <input type="text" name="'.$id.'_time_range_start" class="time start" autocomplete="off"> 
                                '.get_lang('To').'
                                <input type="text" name="'.$id.'_time_range_end" class="time end " autocomplete="off">
                            </p>
                        </div>                   
                    </div>
                </div> 
                </span>                   
                ';
                break;
            case FormValidator::LAYOUT_BOX_NO_LABEL:
                return '
                        <label {label-for}>{label}</label>
                        <div class="input-group">
                            
                            {icon}
                            {element}
                        </div>';
                break;
        }
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
