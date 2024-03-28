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
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $incomingData['login_date'] = $incomingData['login_date']->format('Y-m-d H:i:s');

        if ($incomingData['logout_date']) {
            $incomingData['logout_date'] = $incomingData['logout_date']->format('Y-m-d H:i:s');
        }

        $tblTrackELogin = \Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        $firstId = \Database::insert(
            $tblTrackELogin,
            [
                'login_user_id' => $incomingData['login_user_id'],
                'login_date' => $incomingData['login_date'],
                'user_ip' => '',
            ]
        );

        \Database::update(
            $tblTrackELogin,
            ['logout_date' => $incomingData['logout_date']],
            ['login_id = ?' => [$firstId]]
        );

        $incomingData['user_ip'] = '';

        \Database::insert(
            $tblTrackELogin,
            [
                'login_user_id' => $incomingData['login_user_id'],
                'login_date' => $incomingData['logout_date'],
                'logout_date' => $incomingData['logout_date'],
                'user_ip' => '',
            ]
        );

        return $incomingData['login_user_id'];
    }
}
