<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\LessonPagesQuizTask;

/**
 * Class LoadedLessonPageQuizLookup.
 *
 * Lookup for a quiz ID migrated in LpQuizzesTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedLessonPageQuizLookup extends LoadedKeyLookup
{
    /**
     * LoadedLessonPageQuizLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = LessonPagesQuizTask::class;
    }
}
