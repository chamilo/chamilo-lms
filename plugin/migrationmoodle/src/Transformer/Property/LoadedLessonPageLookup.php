<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\LessonPagesTask;

/**
 * Class LoadedLessonPageLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedLessonPageLookup extends LoadedKeyLookup
{
    /**
     * LoadedLessonPageLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = LessonPagesTask::class;
    }
}
