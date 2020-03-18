<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class TrackLoginLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class TrackLoginLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $incomingData['login_date'] = $incomingData['login_date']->format('Y-m-d H:i:s');

        if ($incomingData['logout_date']) {
            $incomingData['logout_date'] = $incomingData['logout_date']->format('Y-m-d H:i:s');
        }

        $incomingData['user_ip'] = '';

        return \Database::insert(
            \Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN),
            $incomingData
        );
    }
}
