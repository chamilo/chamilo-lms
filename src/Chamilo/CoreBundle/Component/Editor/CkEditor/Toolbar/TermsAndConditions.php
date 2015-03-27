<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * TermsAndConditions toolbar configuration
 *
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar *
 */
class TermsAndConditions extends Basic
{

    public function getConfig()
    {
        $config['toolbar_minToolbar'] = [
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight']
        ];

        $config['toolbar_maxToolbar'] = $config['toolbar_minToolbar'];

        return $config;
    }

}
