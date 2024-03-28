<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserQuestionAttemptLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserQuestionAttemptLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $incomingData['marks'] = (float) $incomingData['marks'];
        $incomingData['teacher_comment'] = '';
        $incomingData['tms'] = $incomingData['tms']->format('Y-m-d H:i:s');
        $incomingData['position'] = 0;

        return \Database::insert(
            \Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT),
            $incomingData
        );
    }
}
