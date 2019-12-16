<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * TermsAndConditions toolbar configuration.
 */
class TermsAndConditions extends Basic
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
            $config['toolbar_minToolbar'] = $this->getNormalToolbar();
            $config['toolbar_maxToolbar'] = $this->getNormalToolbar();
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
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['Bold', 'Italic', 'Underline'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight'],
        ];
    }
}
