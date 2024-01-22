<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ToolIcon;

/**
 * Form element to select a date.
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

        $label = $this->getLabel();
        $requiredFields = api_get_setting('registration.required_extra_fields_in_inscription', true);
        if (!empty($requiredFields) && $requiredFields['options']) {
            $requiredFields = $requiredFields['options'];
        }
        $variable = str_replace('extra_', '',$id);
        $requiredSymbol = '';
        if (!empty($requiredFields) && in_array($variable, $requiredFields)) {
            $requiredSymbol = '<span class="form_required">*</span>';
        }
        return '
            <div>'.$requiredSymbol.$label.'</div>
            <div id="'.$id.'" class="flex flex-row mt-1">
                <input '.$this->_getAttrString($this->_attributes).'
                    class="form-control border" type="text" value="'.$value.'" placeholder="'.get_lang('Select date ..').'" data-input>
                <div class="ml-1" id="button-addon3">
                    <button class="btn btn--secondary-outline"  type="button" data-toggle>
                        <i class="pi pi-calendar pi-lg"></i>
                    </button>
                    <button class="btn btn--secondary-outline" type="button" data-clear>
                        <i class="pi pi-times pi-lg"></i>
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
        if (empty($value)) {
            return;
        }

        $value = substr($value, 0, 16);
        $this->updateAttributes(
            [
                'value' => $value,
            ]
        );
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
                    altFormat: '".get_lang('F d, Y')."',
                    enableTime: false,
                    dateFormat: 'Y-m-d',
                    wrap: true,
                    locale: {
                      firstDayOfWeek: 1
                    }
                };
                $('#{$id}').flatpickr(config);
                if ($('label[for=\"".$id."\"]').length > 0) {
                    $('label[for=\"".$id."\"]').hide();
                }

                document.querySelector('label[for=\"' + '{$id}' + '\"]').classList.add('datepicker-label');
             });
        </script>";

        return $js;
    }
}
