<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class SortSectionModuleLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class SortSectionModuleLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        \learnpath::sortItemByOrderList(
            $incomingData['order_list'],
            $incomingData['c_id']
        );

        return $incomingData['lp_id'];
    }
}
