<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\LessonQuestionPagesQuestionTask;

/**
 * Class LoadedLpQuizQuestionLookup.
 *
 * Lookup for the ID from a quiz question migrated.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedLpQuizQuestionLookup extends LoadedKeyLookup
{
    /**
     * LoadedLpQuizQuestionLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = LessonQuestionPagesQuestionTask::class;
    }
}
