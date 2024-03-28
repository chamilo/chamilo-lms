<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CourseModulesQuizLoader.
 *
 * Loader for create a Chamilo learning path item (quiz type) coming from transformed data of Moodle course quiz module.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseModulesQuizLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @return int
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
            'quiz',
            0,
            $incomingData['title'],
            ''
        );
    }
}
