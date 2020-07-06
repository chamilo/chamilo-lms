<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Wiki toolbar configuration.
 *
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
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
        if (api_get_setting('more_buttons_maximized_mode') != 'true') {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();
        }

        $config['forcePasteAsPlainText'] = false;

        if (api_get_setting('force_wiki_paste_as_plain_text') == 'true') {
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
            ['Image', 'Video', 'Flash', 'Oembed', 'Youtube', 'VimeoEmbed', 'Audio', 'Asciimath'],
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
                api_get_setting('allow_spellcheck') == 'true' ? 'Scayt' : '',
            ],
            api_get_setting('enabled_wiris') == 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_formulaEditorChemistry'] : [''],
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
            api_get_setting('enabled_wiris') == 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_formulaEditorChemistry'] : [''],
            ['Toolbarswitch'],
        ];
    }
}
