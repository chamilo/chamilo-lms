<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Profile toolbar configuration
 * 
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class Profile extends Basic
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
            $config['toolbar_maxToolbar'] = $this->getMaximizedToolbar();
        }

        return $config;
    }

    /**
     * Get the toolbar configuration when CKEditor is maximized
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

    /**
     * Get the default toolbar configuration when the setting more_buttons_maximized_mode is false
     * @return array
     */
    protected function getNormalToolbar()
    {
        return [
            ['Maximize', '-', 'PasteFromWord', '-', 'Undo', 'Redo'],
            ['Link', 'Unlink', 'Anchor'],
            ['Image', 'Video', 'Flash', 'Oembed', 'Youtube', 'Audio'],
            ['Table', 'Smiley'],
            '/',
            ['Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['JustifyLeft', 'JustifyCenter', '-', 'NumberedList', 'BulletedList', '-', 'TextColor', 'BGColor'],
            ['Source']
        ];
    }

    /**
     * Get the toolbar configuration when CKEditor is minimized
     * @return array
     */
    protected function getMinimizedToolbar()
    {
        return [
            ['NewPage', 'Templates', '-', 'PasteFromWord'],
            ['Undo', 'Redo'],
            ['Link', 'Image', 'Video', 'Flash', 'Audio', 'Table', 'Asciimath'],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyBlock'],
            ['Format', 'Font', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor'],
            ['Toolbarswitch']
        ];
    }

}
