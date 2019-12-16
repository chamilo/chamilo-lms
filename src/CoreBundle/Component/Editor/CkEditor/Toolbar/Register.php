<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Register toolbar configuration.
 */
class Register extends Basic
{
    public $plugins = [];

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
            $config['toolbar_maxToolbar'] = $this->getMinimizedToolbar();
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
            ['Maximize', '-', 'PasteFromWord', '-', 'Undo', 'Redo'],
            ['Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', '-', 'NumberedList', 'BulletedList', '-', 'TextColor', 'BGColor'],
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
            ['Toolbarswitch', 'PasteFromWord', '-', 'Undo', 'Redo'],
            ['Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', '-', 'NumberedList', 'BulletedList', '-', 'TextColor', 'BGColor'],
        ];
    }
}
