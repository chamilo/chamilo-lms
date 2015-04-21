<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Forum toolbar configuration
 *
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar *
 */
class Forum extends Basic
{

    /**
     * Get the toolbar config
     * @return array
     */
    public function getConfig()
    {
        if (api_get_setting('more_buttons_maximized_mode') != 'true') {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();
        }

        return $config;
    }

    /**
     * Get the default toolbar configuration when the setting more_buttons_maximized_mode is false
     * @return array
     */
    protected function getNormalToolbar()
    {
        return [
            ['Save', 'Maximize', 'PasteFromWord', '-', 'Undo', 'Redo'],
            ['Link', 'Unlink', 'Anchor'],
            ['Image', 'Video', 'Flash', 'Oembed', 'Youtube', 'Audio'],
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
                'Source'
            ],
            '/',
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            [
                'JustifyLeft',
                'JustifyCenter',
                'JustifyRight'
            ]
        ];
    }

    /**
     * Get the toolbar configuration when CKEditor is minimized
     * @return array
     */
    protected function getMinimizedToolbar()
    {
        return [
            ['Save', 'NewPage', 'Templates', '-', 'PasteFromWord'],
            ['Undo', 'Redo'],
            ['Link', 'Image', 'Video', 'Oembed', 'Flash', 'Youtube', 'Audio', 'Table', 'Asciimath', 'Asciisvg'],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyBlock'],
            ['Format', 'Font', 'FontSize', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor', 'Source'],
            ['Toolbarswitch']
        ];
    }

}
