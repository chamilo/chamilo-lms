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

        $width = 'element';
        $givenWidth = '100%';
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

        $id = $this->getAttribute('id');

        if (empty($id)) {
            $id = $this->getAttribute('name');
            $this->setAttribute('id', $id);
        }

        $html .= <<<JS
            <script>
                $(function(){
                    $('#{$this->getAttribute('id')}').select2({
                        $languageCondition
                        placeholder: '$plHolder',
                        allowClear: true,
                        width: '$width',
                        minimumInputLength: '$minimumInputLength',
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

        $this->removeAttribute('formatResult');
        $this->removeAttribute('minimumInputLength');
        $this->removeAttribute('placeholder');
        $this->removeAttribute('class');
        $this->removeAttribute('url');
        $this->setAttribute('style', 'width: 100%;');

        return parent::toHtml() . $html;
    }
}
