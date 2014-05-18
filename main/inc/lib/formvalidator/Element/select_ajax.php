<?php
/* For licensing terms, see /license.txt */

require_once 'HTML/QuickForm/select.php';
require_once 'HTML/QuickForm/html.php';

/**
* A drop down list with all languages to use with QuickForm
*/
class HTML_QuickForm_Select_Ajax extends HTML_QuickForm_select
{
    /**
     * Class constructor
     */
    function HTML_QuickForm_Select_Ajax($elementName = null, $elementLabel = null, $options = null, $attributes = null)
    {
        parent::HTML_QuickForm_Select($elementName, $elementLabel, $options, $attributes);
    }

    /**
     * The ajax call must contain an array of id and text
     * @return string
     */
    function toHtml()
    {
        $html = api_get_js('select2/select2.js');

        $iso = api_get_language_isocode(api_get_interface_language());
        $localeFile = 'select2_locale_'.$iso.'.js';
        if (file_exists(api_get_path(LIBRARY_PATH).'javascript/select2/'.$localeFile)) {
            $html .= api_get_js('select2/'.$localeFile);
        }
        $html .= api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/select2/select2.css');

        $formatResult = $this->getAttribute('formatResult');

        $formatCondition = null;

        if (!empty($formatResult)) {
            $formatCondition = ',
                formatResult : '.$formatResult.',
                formatSelection : '.$formatResult.',';
        }

        $defaultValues = $this->getAttribute('defaults');

        $dataCondition = null;
        $tags = null;
        if (!empty($defaultValues)) {
            $result = json_encode($defaultValues);
            $result = str_replace('"id"', 'id', $result);
            $result = str_replace('"text"', 'text', $result);
            $dataCondition = '$("#'.$this->getAttribute('name').'").select2("data", '.$result.')';
            $tags = ', tags : function() { return '.$result.'} ';
        }
        $width = 'element';
        $givenWidth = $this->getAttribute('width');
        if (!empty($givenWidth)) {
            $width = $givenWidth;
        }

        //Get the minimumInputLength for select2
        $minimumInputLength = $this->getAttribute('minimumInputLength') > 3 ?
            $this->getAttribute('minimumInputLength') :
            3
        ;

        $plHolder = $this->getAttribute('placeholder');
        if (empty($plHolder)) {
            $plHolder = get_lang('SelectAnOption');
        }

        $html .= '<script>
                $(function() {
                    $("#'.$this->getAttribute('name').'").select2({
                        placeholder: "' . $plHolder . '",
                        allowClear: true,
                        width: "'.$width.'",
                        minimumInputLength: ' . $minimumInputLength . ',
                        // instead of writing the function to execute the request we use Select2s convenient helper
                        ajax: {
                            url: "'.$this->getAttribute('url').'",
                            dataType: "json",
                            data: function (term, page) {
                                return {
                                    q: term, // search term
                                    page_limit: 10,
                                };
                            },
                            results: function (data, page) { // parse the results into the format expected by Select2.
                                // since we are using custom formatting functions we do not need to alter remote JSON data
                                return {
                                    results: data
                                };
                            }
                        }
                        '.$tags.'
                        '.$formatCondition.'
                    });
                    '.$dataCondition.'

                });

        </script>';
        $html .= '<input id="'.$this->getAttribute('name').'" name="'.$this->getAttribute('name').'" />';
        return $html;
    }
}
