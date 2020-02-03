<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class EfcUserSessionLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class EfcUserSessionLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $datetime = api_get_utc_datetime();
        $coachId = 1;

        return \SessionManager::create_session(
            $incomingData['name'],
            $datetime,
            '',
            $datetime,
            '',
            $datetime,
            '',
            $coachId,
            0
        );
    }
}
