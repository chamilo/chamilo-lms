<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CourseModulesUrlLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseModulesUrlLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $lp = new \learnpath(
            $incomingData['c_code'],
            $incomingData['lp_id'],
            1
        );

        return $lp->add_item(
            0,
            0,
            'link',
            0,
            $incomingData['title'],
            ''
        );
    }
}
