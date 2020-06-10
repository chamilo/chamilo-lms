<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\LessonPagesQuizQuestionTask;

/**
 * Class LoadedLessonPageQuizQuestionLookup.
 *
 * Lookup for the ID from a quiz question migrated.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedLessonPageQuizQuestionLookup extends LoadedKeyLookup
{
    /**
     * LoadedLessonPageQuizQuestionLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = LessonPagesQuizQuestionTask::class;
    }
}
