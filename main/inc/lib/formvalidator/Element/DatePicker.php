<?php
/* For licensing terms, see /license.txt */

/**
 * Form element to select a date.
 *
 * Class DatePicker
 */
class DatePicker extends HTML_QuickForm_text
{
    /**
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
     * @param string $value
     */
    public function setValue($value)
    {
        $value = substr($value, 0, 16);
        $this->updateAttributes(
            [
                'value' => $value,
            ]
        );
    }

    /**
     * @param string $layout
     *
     * @return string
     */
    public function getTemplate($layout)
    {
        $size = $this->calculateSize();

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
            case FormValidator::LAYOUT_HORIZONTAL:
                return '
                <div class="form-group {error_class}">
                    <label {label-for} class="col-sm-'.$size[0].' control-label {extra_label_class}" >
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
                </div>';
            case FormValidator::LAYOUT_BOX_NO_LABEL:
                return '{element}';
        }

        return '<div class="form-group">
                <label {label-for}>{label}</label>
                {element}
            </div>'
        ;
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
            });
        </script>";

        return $js;
    }
}
