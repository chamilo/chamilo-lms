<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * FAQ toolbar configuration
 * 
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class FAQ extends Basic
{

    /**
     * @return mixed
     */
    public function getConfig()
    {
        if (api_get_setting('more_buttons_maximized_mode') != 'true') {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getSmallToolbar();

            $config['toolbar_maxToolbar'] = $this->getMaximizedToolbar();
        }

        return $config;
    }

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
                'Oembed',
                'Youtube',
                'Flash',
                'Audio',
                'leaflet',
                'Smiley',
                'SpecialChar'
            ],
            '/',
            ['Table', '-', 'CreateDiv'],
            ['BulletedList', 'NumberedList', 'HorizontalRule', '-', 'Outdent', 'Indent', 'Blockquote'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript', '-', 'TextColor', 'BGColor'],
            [api_get_setting('allow_spellcheck') == 'true' ? 'Scayt' : ''],
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['PageBreak', 'ShowBlocks', 'Source'],
            ['Toolbarswitch']
        ];
    }

    protected function getNormalToolbar()
    {
        return [
            ['Save', 'Preview', 'Source']
        ];
    }

    protected function getSmallToolbar()
    {
        return [
            ['NewPage', '-', 'PasteFromWord'],
            ['Undo', 'Redo'],
            ['Link', 'Image', 'Video', 'Flash', 'Audio', 'Table', 'Asciimath', 'Asciisvg'],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter'],
            ['Format', 'Font', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor'],
            ['Toolbarswitch']
        ];
    }

}
