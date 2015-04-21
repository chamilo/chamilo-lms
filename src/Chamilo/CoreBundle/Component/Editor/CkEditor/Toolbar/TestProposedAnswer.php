<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * TestProposedAnswer toolbar configuration
 * 
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class TestProposedAnswer extends Basic
{
    /**
     * @return mixed
     */
    public function getConfig()
    {
        if (api_get_setting('more_buttons_maximized_mode') != 'true') {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();

            $config['toolbar_maxToolbar'] = $this->getMaximizedToolbar();
        }

        $config['toolbarCanCollapse'] = true;
        $config['toolbarStartupExpanded'] = false;

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
                'Flash',
                'Youtube',
                'Oembed',
                'Audio',
                'leaflet',
                'Smiley',
                'SpecialChar',
                'Asciimath',
                'Asciisvg'
            ],
            '/',
            ['Table', '-', 'CreateDiv'],
            ['BulletedList', 'NumberedList', 'HorizontalRule', '-', 'Outdent', 'Indent', 'Blockquote'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript', '-', 'TextColor', 'BGColor'],
            [api_get_setting('allow_spellcheck') == 'true' ? 'Scayt' : ''],
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['PageBreak', 'ShowBlocks', 'Source'],
            ['Toolbarswitch'],
        ];
    }

    protected function getNormalToolbar()
    {
        return [
            [
                'Maximize',
                'Bold',
                'Image',
                'Link',
                'PasteFromWord',
                'Audio',
                'Table',
                'Subscript',
                'Superscript',
                'Source'
            ]
        ];
    }

    protected function getMinimizedToolbar()
    {
        return [
            ['Templates'],
            ['PasteFromWord'],
            ['Link'],
            ['Image', 'Video', 'Flash', 'Audio', 'Asciimath', 'Asciisvg'],
            ['Table'],
            ['Bold'],
            ['Source', 'Toolbarswitch']
        ];
    }

}
