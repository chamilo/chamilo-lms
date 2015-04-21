<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Register toolbar configuration
 *
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar *
 */
class Register extends Basic
{

    public $plugins = array(
    );

    public function getConfig()
    {
        if (api_get_setting('more_buttons_maximized_mode') != 'true') {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();
            $config['toolbar_maxToolbar'] = $this->getMinimizedToolbar();
        }

        return $config;
    }

    protected function getNormalToolbar()
    {
        return [
            ['Maximize', '-', 'PasteFromWord', '-', 'Undo', 'Redo'],
            ['Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['JustifyLeft', 'JustifyCenter', '-', 'NumberedList', 'BulletedList', '-', 'TextColor', 'BGColor']
        ];
    }

    protected function getMinimizedToolbar()
    {
        return [
            ['Toolbarswitch', 'PasteFromWord', '-', 'Undo', 'Redo'],
            ['Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['JustifyLeft', 'JustifyCenter', '-', 'NumberedList', 'BulletedList', '-', 'TextColor', 'BGColor']
        ];
    }

}
