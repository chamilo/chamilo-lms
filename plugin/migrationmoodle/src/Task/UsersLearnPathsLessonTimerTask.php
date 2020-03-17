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
     * @inheritDoc
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedUsersFilterExtractor::class,
            'query' => "SELECT * FROM mdl_lesson_timer",
        ];
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UserLearnPathLessonTimerLoader::class,
        ];
    }
}
