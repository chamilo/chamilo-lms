<?php
/* For licensing terms, see /license.txt */

/**
* A drop down list with all languages to use with QuickForm
*/
class SelectAjax extends HTML_QuickForm_select
{
    /**
     * @inheritdoc
     */
    public function __construct($elementName, $elementLabel = '', $options = null, $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $options, $attributes);
    }

    /**
     * The ajax call must contain an array of id and text
     * @return string
     */
    public function toHtml()
    {
        $iso = api_get_language_isocode(api_get_interface_language());

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
            $this->getAttribute('minimumInputLength') : 3
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
        // URL must return ajax json_encode arrady [items => [['id'=>1, 'text'='content']]
        $url = $this->getAttribute('url');

        if (!$url) {
            $url = $this->getAttribute('url_function');
        } else {
            $url = "'$url'";
        }

        $tagsAttr = $this->getAttribute('tags');
        $multipleAttr = $this->getAttribute('multiple');

        $tags = !empty($tagsAttr) ? (bool) $tagsAttr : false;
        $tags = $tags ? 'true' : 'false';

        $multiple = !empty($multipleAttr) ? (bool) $multipleAttr : false;
        $multiple = $multiple ? 'true' : 'false';

        $max = $this->getAttribute('maximumSelectionLength');
        $max = !empty($max) ? "maximumSelectionLength: $max, " : '';

        $html = <<<JS
            <script>
                $(function(){
                    $('#{$this->getAttribute('id')}').select2({
                        language: '$iso',
                        placeholder: '$plHolder',
                        allowClear: true,
                        width: '$width',
                        minimumInputLength: '$minimumInputLength',
                        tags: $tags,
                        ajax: {
                            url: $url,
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
        $this->removeAttribute('maximumSelectionLength');
        $this->removeAttribute('tags');
        $this->removeAttribute('placeholder');
        $this->removeAttribute('class');
        $this->removeAttribute('url');
        $this->removeAttribute('url_function');
        $this->setAttribute('style', 'width: 100%;');

        return parent::toHtml().$html;
    }

    /**
     * We check the options and return only the values that _could_ have been
     * selected. We also return a scalar value if select is not "multiple"
     */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $value = $this->_findValue($submitValues);

        if (!$value) {
            $value = '';
        }

        return $this->_prepareValue($value, $assoc);
    }
}
