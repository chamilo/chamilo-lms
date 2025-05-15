<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class LpDocumentsLoader.
 *
 * Loader to create the items for Chamilo learning paths coming from the list of Moodle lesson pages in a course.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LessonPagesLoader implements LoaderInterface
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
        $itemId = $lp->add_item(
            $incomingData['parent'],
            $incomingData['previous'],
            $incomingData['item_type'],
            0,
            $incomingData['title'],
            ''
        );

        return $itemId;
    }
}
