<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CourseSectionsLoader.
 *
 * Loader for create a Chamilo learning path coming from a Moodle course section.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseSectionsLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @param array $incomingData
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $lpId = \learnpath::add_lp(
            $incomingData['c_id'],
            $incomingData['name'],
            $incomingData['description'],
            'chamilo',
            'manual',
            '',
            '',
            '',
            0
        );

        return $lpId;
    }
}
