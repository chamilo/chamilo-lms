<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UserLearnPathLessonBranchLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseModuleLessonLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLessonPageLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserSessionLookup;

/**
 * Class UsersLearnPathsLessonBranchTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UsersLearnPathsLessonBranchTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $query = 'SELECT lb.* FROM mdl_lesson_branch lb
            INNER JOIN (
                SELECT lt.lessonid, lt.userid, lt.starttime, lt.lessontime
                FROM mdl_lesson_timer lt
                WHERE lt.completed = 1
                GROUP BY lt.userid, lt.lessonid
                ORDER BY lt.userid, lt.lessonid, lt.starttime
            ) AS lesson_timer ON (lb.lessonid = lesson_timer.lessonid AND lb.userid = lesson_timer.userid)
            WHERE lb.timeseen >= lesson_timer.starttime AND lb.timeseen <= lesson_timer.lessontime
            ORDER BY lb.userid, lb.lessonid, lb.timeseen';

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $query = "SELECT lb.* FROM mdl_lesson_branch lb
                INNER JOIN (
                    SELECT lt.lessonid, lt.userid, lt.starttime, lt.lessontime
                    FROM mdl_lesson_timer lt
                    INNER JOIN mdl_user u ON (lt.userid = u.id)
                    WHERE lt.completed = 1
                        AND u.username LIKE '$userFilter%'
                    GROUP BY lt.userid, lt.lessonid
                    ORDER BY lt.userid, lt.lessonid, lt.starttime
                ) AS lesson_timer ON (lb.lessonid = lesson_timer.lessonid AND lb.userid = lesson_timer.userid)
                INNER JOIN mdl_user u ON (lb.userid = u.id)
                WHERE lb.timeseen >= lesson_timer.starttime AND lb.timeseen <= lesson_timer.lessontime
                    AND u.username LIKE '$userFilter%'
                ORDER BY lb.userid, lb.lessonid, lb.timeseen";
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
                'item_id' => [
                    'class' => LoadedLessonPageLookup::class,
                    'properties' => ['pageid'],
                ],
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['userid'],
                ],
                'end_time' => 'timeseen',
                'session_id' => [
                    'class' => LoadedUserSessionLookup::class,
                    'properties' => ['userid'],
                ],
                'view_count' => 'retry',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UserLearnPathLessonBranchLoader::class,
        ];
    }
}
