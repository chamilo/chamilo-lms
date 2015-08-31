<?php
/* For licensing terms, see /license.txt */

/**
* A drop down list with all languages to use with QuickForm
*/
class SelectAjax extends HTML_QuickForm_select
{
    /**
     * Class constructor
     */
    function SelectAjax($elementName = null, $elementLabel = null, $options = null, $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $options, $attributes);
    }

    /**
     * The ajax call must contain an array of id and text
     * @return string
     */
    function toHtml()
    {
        $html = api_get_asset('select2/dist/js/select2.min.js');

        $iso = api_get_language_isocode(api_get_interface_language());
        $languageCondition = '';

        if (file_exists(api_get_path(SYS_PATH) . "web/assets/select2/dist/js/i18n/$iso.js")) {
            $html .= api_get_asset("select2/dist/js/i18n/$iso.js");
            $languageCondition = "language: '$iso',";
        }

        $html .= api_get_css(api_get_path(WEB_PATH).'web/assets/select2/dist/css/select2.min.css');

        $formatResult = $this->getAttribute('formatResult');

        $formatCondition = null;

        if (!empty($formatResult)) {
            $formatCondition = ',
                templateResult : '.$formatResult.',
                templateSelection : '.$formatResult;
        }

        $defaultValues = $this->getAttribute('defaults');
        $defaultValues = empty($defaultValues) ? [] : $defaultValues;

        $width = 'element';
        $givenWidth = '300';
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

        $html .= <<<JS
            <script>
                $(function(){
                    $('#{$this->getAttribute('name')}').select2({
                        $languageCondition
                        placeholder_: '$plHolder',
                        allowClear: true,
                        width: '$width',
                        minimumInputLength: '$minimumInputLength',
                        // instead of writing the function to execute the request we use Select2s convenient helper
                        ajax: {
                            url: '{$this->getAttribute('url')}',
                            dataType: 'json',
                            data: function(params) {
                                return {
                                    q: params.term, // search term
                                    page_limit: 10,
                                };
                            },
                            processResults: function (data, page) {
                                //parse the results into the format expected by Select2
                                return {
                                    results: data.items
                                };
                            }
                            $formatCondition
                        }
                    });
                });
            </script>
JS;

        $html .= Display::select(
            $this->getAttribute('name'),
            $defaultValues,
            array_keys($defaultValues),
            [
                'id' =>  $this->getAttribute('name'),
                'style' => 'width: 100%;'
            ],
            false
        );
        return $html;
    }
}
