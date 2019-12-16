<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * AssessmentsIntroduction toolbar configuration.
 */
class AssessmentsIntroduction extends Basic
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
            ['Save', 'Maximize', '-', 'PasteFromWord', '-', 'Undo', 'Redo'],
            ['Link', 'Unlink', 'Anchor'],
            ['Image', 'Video', 'Flash', 'Oembed', 'Youtube', 'Audio'],
            ['Table', 'SpecialChar'],
            ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'TextColor', 'BGColor'],
            '/',
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['Subscript', 'Superscript', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
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
            ['Link', 'Image', 'Video', 'Flash', 'Audio', 'Table', 'Asciimath', 'Asciisvg'],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Format', 'Font', 'FontSize', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor'],
            ['Toolbarswitch'],
        ];
    }
}
