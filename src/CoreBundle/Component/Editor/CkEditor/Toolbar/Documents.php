<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Documents toolbar configuration.
 */
class Documents extends Basic
{
    public array $plugins = [];

    /**
     * Get the toolbar config.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [];

        if ('true' !== api_get_setting('more_buttons_maximized_mode')) {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();
            $config['toolbar_maxToolbar'] = $this->getMaximizedToolbar();
        }

        $config['extraPlugins'] = $this->getPluginsToString();
        $config['fullPage'] = true;

        return $config;
    }

    /**
     * @return array
     */
    public function getConditionalPlugins()
    {
        $plugins = [];

        if ('ismanual' === api_get_setting('show_glossary_in_documents')) {
            $plugins[] = 'glossary';
        }

        return $plugins;
    }

    /**
     * Get the default toolbar configuration when the setting more_buttons_maximized_mode is false.
     */
    protected function getNormalToolbar(): array
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
                'true' === api_get_setting('editor.translate_html') ? 'translatehtml' : '',
                'true' === api_get_setting('allow_spellcheck') ? 'Scayt' : '',
            ],
            '/',
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            'true' === api_get_setting('enabled_wiris') ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
            ['Source'],
        ];
    }

    /**
     * @return array
     */
    protected function getMaximizedToolbar()
    {
        return [
            array_merge(['Save'], $this->getNewPageBlock()),
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
                'true' === api_get_setting('editor.translate_html') ? 'translatehtml' : '',
            ],
            ['true' === api_get_setting('allow_spellcheck') ? 'Scayt' : ''],
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['PageBreak', 'ShowBlocks'],
            'true' === api_get_setting('enabled_wiris') ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
            ['Toolbarswitch', 'Source'],
        ];
    }

    /**
     * Get the toolbar configuration when CKEditor is minimized.
     */
    protected function getMinimizedToolbar(): array
    {
        return [
            $this->getNewPageBlock(),
            ['Undo', 'Redo'],
            [
                'Link',
                'Image',
                'Video',
                'Oembed',
                'Flash',
                'Youtube',
                'VimeoEmbed',
                'Audio',
                'Table',
                'Asciimath',
                'Asciisvg',
            ],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight'],
            ['Styles',
                'Format',
                'Font',
                'FontSize',
                'Bold',
                'Italic',
                'Underline',
                'TextColor',
                'BGColor',
            ],
            [
                'true' === api_get_setting('editor.translate_html') ? 'translatehtml' : '',
                'ShowBlocks',
            ],
            'true' === api_get_setting('enabled_wiris') ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
            ['Toolbarswitch', 'Source'],
        ];
    }
}
