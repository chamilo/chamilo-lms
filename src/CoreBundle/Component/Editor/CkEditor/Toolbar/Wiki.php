<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Wiki toolbar configuration.
 */
class Wiki extends Basic
{
    /**
     * Get the toolbar config.
     *
     * @return array
     */
    public function getConfig()
    {
        if ('true' != api_get_setting('more_buttons_maximized_mode')) {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();
        }

        $config['forcePasteAsPlainText'] = false;

        if ('true' == api_get_setting('force_wiki_paste_as_plain_text')) {
            $config['forcePasteAsPlainText'] = true;
        }

        return $config;
    }

    /**
     * Get the default toolbar configuration when the setting more_buttons_maximized_mode is false.
     *
     * @return array
     */
    protected function getNormalToolbar()
    {
        return [
            [
                'Maximize',
                'Save',
                'NewPage',
                'Templates',
                'PageBreak',
                'Preview',
                '-',
                'PasteText',
                '-',
                'Undo',
                'Redo',
                '-',
                'SelectAll',
                '-',
                'Find',
            ],
            ['Wikilink', 'Link', 'Unlink', 'Anchor'],
            ['Image', 'Video', 'Flash', 'Oembed', 'Youtube', 'Audio', 'Asciimath'],
            ['Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'leaflet'],
            ['Format', 'Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            [
                'Subscript',
                'Superscript',
                '-',
                'JustifyLeft',
                'JustifyCenter',
                'JustifyRight',
                'JustifyFull',
                '-',
                'NumberedList',
                'BulletedList',
                '-',
                'Outdent',
                'Indent',
                '-',
                'TextColor',
                'BGColor',
                'true' == api_get_setting('allow_spellcheck') ? 'Scayt' : '',
            ],
            'true' == api_get_setting('enabled_wiris') ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
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
            ['Wikilink', 'Link', 'Image', 'Video', 'Flash', 'Audio', 'Table', 'Asciimath', 'Asciisvg'],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Styles', 'Format', 'Font', 'FontSize', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor', 'Source'],
            'true' == api_get_setting('enabled_wiris') ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
            ['Toolbarswitch'],
        ];
    }
}
