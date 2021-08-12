<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Documents toolbar configuration.
 *
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class IntroductionSection extends Basic
{
    public $plugins = [];

    /**
     * Get the toolbar config.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [];

        if (api_get_setting('more_buttons_maximized_mode') !== 'true') {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();
            $config['toolbar_maxToolbar'] = $this->getMaximizedToolbar();
        }

        $config['extraPlugins'] = $this->getPluginsToString();
        $config['fullPage'] = false;

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
     * Get the default toolbar configuration when the setting more_buttons_maximized_mode is false.
     *
     * @return array
     */
    protected function getNormalToolbar()
    {
        return [
            ['Maximize', 'PasteFromWord', '-', 'Undo', 'Redo'],
            ['Link', 'Unlink', 'Anchor', 'inserthtml', 'Glossary'],
            [
                'Image',
                'Video',
                'Flash',
                'Oembed',
                'Youtube',
                'VimeoEmbed',
                'Audio',
                'Asciimath',
                'Asciisvg',
            ],
            ['Table', 'SpecialChar'],
            [
                'Outdent',
                'Indent',
                '-',
                'TextColor',
                'BGColor',
                '-',
                'NumberedList',
                'BulletedList',
                '-',
                api_get_configuration_value('translate_html') ? 'Language' : '',
                api_get_setting('allow_spellcheck') === 'true' ? 'Scayt' : '',
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
                'Image',
                'Video',
                'Flash',
                'Youtube',
                'VimeoEmbed',
                'Audio',
                'Table',
                'Asciimath',
                'Asciisvg',
            ],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyBlock'],
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
            [
                'ShowBlocks',
            ],
            api_get_setting('enabled_wiris') === 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_formulaEditorChemistry'] : [''],
            ['Toolbarswitch', 'Source'],
        ];
    }

    /**
     * @return array
     */
    protected function getMaximizedToolbar()
    {
        return [
            $this->getNewPageBlock(),
            ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', 'inserthtml'],
            ['Undo', 'Redo', '-', 'SelectAll', 'Find', '-', 'RemoveFormat'],
            ['Link', 'Unlink', 'Anchor', 'Glossary'],
            [
                'Image',
                'Mapping',
                'Video',
                'Oembed',
                'Flash',
                'Youtube',
                'VimeoEmbed',
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
            api_get_setting('enabled_wiris') == 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_formulaEditorChemistry'] : [''],
            ['Toolbarswitch', 'Source'],
        ];
    }
}
