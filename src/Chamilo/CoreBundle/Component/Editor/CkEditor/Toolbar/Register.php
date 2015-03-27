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
        $config['toolbar_minToolbar'] = [
            ['Toolbarswitch', 'PasteFromWord', '-', 'Undo', 'Redo'],
            ['Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['JustifyLeft', 'JustifyCenter', '-', 'NumberedList', 'BulletedList', '-', 'TextColor', 'BGColor']
        ];

        $config['toolbar_maxToolbar'] = $config['toolbar_minToolbar'];

        return $config;
    }

}
