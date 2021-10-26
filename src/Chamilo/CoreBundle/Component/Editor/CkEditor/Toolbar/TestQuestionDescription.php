<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * TestQuestionDescription toolbar configuration.
 */
class TestQuestionDescription extends Basic
{
    /**
     * Get the toolbar config.
     *
     * @return array
     */
    public function getConfig()
    {
        $config['toolbarGroups'] = [
            ['name' => 'document', 'groups' => ['document', 'doctools']],
            ['name' => 'clipboard', 'groups' => ['clipboard', 'undo']],
            ['name' => 'editing', 'groups' => ['clipboard', 'undo']],
            '/',
            ['name' => 'basicstyles', 'groups' => ['basicstyles', 'cleanup']],
            ['name' => 'paragraph', 'groups' => ['list', 'indent', 'blocks', 'align']],
            ['name' => 'links'],
            ['name' => 'insert'],
            '/',
            ['name' => 'styles'],
            ['name' => 'colors'],
            ['name' => 'tools'],
            ['name' => 'others'],
            ['name' => 'mode'],
        ];

        $config['extraPlugins'] = $this->getPluginsToString();
        if (api_get_setting('more_buttons_maximized_mode') !== 'true') {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();
            $config['toolbar_maxToolbar'] = $this->getMaximizedToolbar();
        }

        return $config;
    }

    /**
     * @return array
     */
    public function getConditionalPlugins()
    {
        $plugins = [];
        if (api_get_setting('show_glossary_in_documents') === 'ismanual') {
            $plugins[] = 'glossary';
        }

        return $plugins;
    }

    /**
     * Get the toolbar configuration when CKEditor is maximized.
     *
     * @return array
     */
    protected function getMaximizedToolbar()
    {
        return [
            ['NewPage', 'Templates', '-', 'Preview', 'Print'],
            ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord'],
            ['Undo', 'Redo', '-', 'SelectAll', 'Find', '-', 'RemoveFormat'],
            ['Link', 'Unlink', 'Anchor', 'Glossary'],
            [
                'Image',
                'Mapping',
                'Video',
                'Flash',
                'Youtube',
                'VimeoEmbed',
                'Oembed',
                'Audio',
                'leaflet',
                'Smiley',
                'SpecialChar',
                'Asciimath',
                'Asciisvg',
            ],
            '/',
            ['Table', '-', 'CreateDiv'],
            ['BulletedList', 'NumberedList', 'HorizontalRule', '-', 'Outdent', 'Indent', 'Blockquote'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            [
                'Bold',
                'Italic',
                'Underline',
                'Strike',
                '-',
                'Subscript',
                'Superscript',
                '-',
                'TextColor',
                'BGColor',
                api_get_configuration_value('translate_html') ? 'Language' : '',
            ],
            [api_get_setting('allow_spellcheck') == 'true' ? 'Scayt' : ''],
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['PageBreak', 'ShowBlocks'],
            api_get_setting('enabled_wiris') === 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_formulaEditorChemistry'] : [''],
            ['Toolbarswitch', 'Source'],
        ];
    }

    /**
     * Get the default toolbar configuration when the setting more_buttons_maximized_mode is false.
     *
     * @return array
     */
    protected function getNormalToolbar()
    {
        return [
            ['Maximize', '-', 'PasteFromWord', '-', 'Undo', 'Redo'],
            ['Link', 'Unlink'],
            ['Image', 'Video', 'Flash', 'Oembed', 'Youtube', 'VimeoEmbed', 'Audio'],
            ['Table', 'SpecialChar'],
            [
                'NumberedList',
                'BulletedList',
                '-',
                'Outdent',
                'Indent',
                '-',
                'TextColor',
                'BGColor',
                '-',
                api_get_configuration_value('translate_html') ? 'Language' : '',
            ],
            '/',
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight'],
            api_get_setting('enabled_wiris') === 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_formulaEditorChemistry'] : [''],
            ['Source'],
        ];
    }

    /**
     * Get the toolbar configuration when CKEditor is minimized.
     *
     * @return array
     */
    protected function getMinimizedToolbar()
    {
        return [
            $this->getNewPageBlock(),
            ['Undo', 'Redo'],
            [
                'Link',
                'Unlink',
                'Image',
                'Video',
                'Flash',
                'Oembed',
                'Youtube',
                'VimeoEmbed',
                'Audio',
                'Table',
                'Asciimath',
                'Asciisvg',
            ],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            [
                'Styles',
                'Format',
                'Font',
                'FontSize',
                'Bold',
                'Italic',
                'Underline',
                'TextColor',
                'BGColor',
                api_get_configuration_value('translate_html') ? 'Language' : '',
            ],
            api_get_setting('enabled_wiris') === 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_formulaEditorChemistry'] : [''],
            ['Toolbarswitch', 'Source'],
        ];
    }
}
