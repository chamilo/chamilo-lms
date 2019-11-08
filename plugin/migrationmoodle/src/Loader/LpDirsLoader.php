<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class LpDirsLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LpDirsLoader implements LoaderInterface
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
        $lp = new \learnpath(
            $incomingData['c_code'],
            $incomingData['lp_id'],
            api_get_user_id()
        );
        $itemId = $lp->add_item(
            0,
            $incomingData['previous'],
            'dir',
            0,
            $incomingData['title'],
            ''
        );

        return $itemId;
    }
}
