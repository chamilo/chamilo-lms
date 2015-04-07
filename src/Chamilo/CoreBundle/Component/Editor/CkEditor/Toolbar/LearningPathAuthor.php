<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * LearningPathAuthor toolbar configuration
 * 
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class LearningPathAuthor extends Basic
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

        $config['fullPage'] = true;

        return $config;
    }

    protected function getMaximizedToolbar()
    {
        return [
            ['PageBreak', 'ShowBlocks', 'Source'],
            ['Toolbarswitch']
        ];
    }

    protected function getNormalToolbar()
    {
        return [
            ['Link', 'Unlink', 'Bold', 'Italic', 'TextColor', 'BGColor', 'Source']
        ];
    }

    protected function getSmallToolbar()
    {
        return [
            ['Link', 'Unlink', 'Bold', 'Italic', 'TextColor', 'BGColor', 'Source'],
            ['Toolbarswitch']
        ];
    }

}
