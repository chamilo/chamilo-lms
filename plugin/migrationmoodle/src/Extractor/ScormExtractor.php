<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Extractor;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesScormTask;
use Chamilo\PluginBundle\MigrationMoodle\Traits\MapTrait\MapTrait;

/**
 * Class ScormExtractor.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Extractor
 */
class ScormExtractor extends BaseExtractor
{
    use MapTrait;

    /**
     * ScormExtractor constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        parent::__construct($configuration);

        $this->calledClass = CourseModulesScormTask::class;
    }

    /**
     * Filter to avoid scorms not yet migrated.
     *
     * @param array $sourceData
     *
     * @return bool
     */
    public function filter(array $sourceData)
    {
        $scormId = $sourceData['scorm'];

        $taskName = $this->getTaskName();

        $result = \Database::select(
            'COUNT(1) AS c',
            'plugin_migrationmoodle_item i INNER JOIN plugin_migrationmoodle_task t ON i.task_id = t.id',
            [
                'where' => [
                    't.name = ? AND i.extracted_id = ?' => [$taskName, $scormId]
                ],
            ],
            'first'
        );

        return $result['c'] == 0;
    }
}
