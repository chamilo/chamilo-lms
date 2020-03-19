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
        $query = 'SELECT * FROM mdl_lesson_branch';

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $query = "SELECT lb.*
                FROM mdl_lesson_branch lb
                INNER JOIN mdl_user u ON lb.userid = u.id
                WHERE u.username LIKE '$userFilter%'";
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
