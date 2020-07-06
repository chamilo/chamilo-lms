<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UserLearnPathLessonTimerLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseModuleLessonLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserSessionLookup;

/**
 * Class UsersLearnPathsLessonTimerTask.
 *
 * Update lp item (dirs) view.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UsersLearnPathsLessonTimerTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $query = 'SELECT * FROM mdl_lesson_timer
            WHERE completed = 1
            GROUP BY lt.userid, lt.lessonid
            ORDER BY userid, lessonid, starttime';

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $query = "SELECT lt.* FROM mdl_lesson_timer lt
                INNER JOIN mdl_user u ON lt.userid = u.id
                WHERE u.username LIKE '$userFilter%'
                    AND lt.completed = 1
                GROUP BY lt.userid, lt.lessonid
                ORDER BY lt.userid, lt.lessonid, lt.starttime";
        }

        return [
            'class' => LoadedUsersFilterExtractor::class,
            'query' => $query,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'parent_item_id' => [
                    'class' => LoadedCourseModuleLessonLookup::class,
                    'properties' => ['lessonid'],
                ],
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['userid'],
                ],
                'start_time' => 'starttime',
                'session_id' => [
                    'class' => LoadedUserSessionLookup::class,
                    'properties' => ['userid'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UserLearnPathLessonTimerLoader::class,
        ];
    }
}
