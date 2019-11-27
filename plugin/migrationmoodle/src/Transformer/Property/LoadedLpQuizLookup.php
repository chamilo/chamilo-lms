<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\LessonPagesQuizTask;

/**
 * Class LoadedLpQuizLookup.
 *
 * Lookup for a quiz ID migrated in LpQuizzesTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedLpQuizLookup extends LoadedKeyLookup
{
    /**
     * LoadedLpQuizLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = LessonPagesQuizTask::class;
    }
}
