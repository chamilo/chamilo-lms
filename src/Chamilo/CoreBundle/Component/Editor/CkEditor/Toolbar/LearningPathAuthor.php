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
        $config['toolbar_minToolbar'] = [
            ['Link', 'Unlink', 'Bold', 'Italic', 'TextColor', 'BGColor', 'Source'],
            ['Toolbarswitch']
        ];
        $config['toolbar_maxToolbar'] = [
            ['PageBreak', 'ShowBlocks', 'Source'],
            ['Toolbarswitch']
        ];

        $config['fullPage'] = true;

        return $config;
    }

}
