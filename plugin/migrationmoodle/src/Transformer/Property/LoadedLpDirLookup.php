<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesLessonTask;

/**
 * Class LoadedLpDirLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedLpDirLookup extends LoadedLpLookup
{
    /**
     * LoadedLpDirLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseModulesLessonTask::class;
    }

    /**
     * @param array $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function transform(array $data)
    {
        list($cmId, $sequenceStr) = array_values($data);

        $sequence = explode(',', $sequenceStr);
        $index = array_search($cmId, $sequence);

        if (empty($index)) {
            return 0;
        }

        $previous = $sequence[$index - 1];

        return parent::transform([$previous]);
    }
}
