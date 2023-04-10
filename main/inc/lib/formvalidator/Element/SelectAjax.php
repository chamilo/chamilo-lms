<?php

/* For licensing terms, see /license.txt */

/**
 * A drop down list with all languages to use with QuickForm.
 */
class SelectAjax extends HTML_QuickForm_select
{
    /**
     * {@inheritdoc}
     */
    public function __construct($elementName, $elementLabel = '', $options = null, $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $options, $attributes);
    }

    /**
     * The ajax call must contain an array of id and text.
     *
     * @return string
     */
    public function toHtml()
    {
        $iso = api_get_language_isocode(api_get_interface_language());
        $dropdownParent = $this->getAttribute('dropdownParent');
        $dropdownParentCondition = $dropdownParent ? "dropdownParent: '$dropdownParent'," : '';
        $formatResult = $this->getAttribute('formatResult');
        $formatSelection = $this->getAttribute('formatSelection');
        $formatCondition = '';

        if (!empty($formatResult)) {
            $formatCondition .= ',
                templateResult : '.$formatResult;
        }

        if (!empty($formatSelection)) {
            $formatCondition .= ',
                templateSelection : '.$formatSelection;
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
            $plHolder = preg_replace("/'/", "\\'", get_lang('SelectAnOption'));
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

        // wait XX milliseconds before triggering the request
        $delay = ((int) $this->getAttribute('delay')) ?: 1000;

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
                        $dropdownParentCondition
                        ajax: {
                            url: $url,
                            delay: $delay,
                            dataType: 'json',
                            data: function(params) {
                                return {
                                    q: params.term, // search term
                                    page_limit: 10,
                                };
                            },
                            processResults: function (data, page) {
                                // Parse the results into the format expected by Select2
                                if (data.items) {
                                    return {
                                        results: data.items
                                    };
                                }
                                return {
                                    results: ''
                                };
                            }
                        }
                        $formatCondition
                    });
                });
            </script>
JS;

        $this->removeAttribute('formatResult');
        $this->removeAttribute('formatSelection');
        $this->removeAttribute('minimumInputLength');
        $this->removeAttribute('maximumSelectionLength');
        $this->removeAttribute('tags');
        $this->removeAttribute('placeholder');
        $this->removeAttribute('class');
        $this->removeAttribute('url');
        $this->removeAttribute('url_function');
        $this->removeAttribute('dropdownParent');
        $this->setAttribute('style', 'width: 100%;');

        return parent::toHtml().$html;
    }

    /**
     * We check the options and return only the values that _could_ have been
     * selected. We also return a scalar value if select is not "multiple".
     */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $value = $this->_findValue($submitValues);

        if (!$value) {
            $value = '';
        }

        return $this->_prepareValue($value, $assoc);
    }

    public static function templateResultForUsersInCourse(): string
    {
        return "function (state) {
                if (state.loading) {
                    return state.text;
                }

                var \$container = \$(
                    '<div class=\"select2-result-user clearfix\">' +
                        '<div class=\"select2-result-user__avatar pull-left\">' +
                            '<img>' +
                        '</div>' +
                        '<div class=\"select2-result-user__info pull-left\">' +
                            '<div class=\"select2-result-user__name\"></div>' +
                            '<div class=\"select2-result-user__username small\"></div>' +
                        '</div>' +
                    '</div>'
                );

                \$container.find('.select2-result-user__avatar img')
                    .prop({ 'src': state.avatarUrl, 'alt': state.username })
                    .css({ 'width': '40px', 'height': '40px' });
                \$container.find('.select2-result-user__info').css({ 'paddingLeft': '6px' });
                \$container.find('.select2-result-user__name').text(state.completeName);
                \$container.find('.select2-result-user__username').text(state.username);

                return \$container;
            }";
    }

    public static function templateSelectionForUsersInCourse(): string
    {
        return "function (state) {
                if (!state.id) {
                    return state.text;
                }
    
                if (!state.avatarUrl) {
                    var avatarUrl = $(state.element).data('avatarurl');
                    var username = $(state.element).data('username');
                    
                    state.avatarUrl = avatarUrl;
                    state.username = username;
                    state.completeName = state.text;
                }
    
                var \$container = \$('<span><img> ' + state.completeName + '</span>');
    
                \$container.find('img')
                    .prop({ 'src': state.avatarUrl, 'alt': state.username })
                    .css({ 'width': '20px', 'height': '20px' });
    
                return \$container;
            }";
    }
}
