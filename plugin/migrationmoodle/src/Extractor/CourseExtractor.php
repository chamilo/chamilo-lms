<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Extractor;

use Chamilo\PluginBundle\MigrationMoodle\Task\EfcCoursesTask;
use Chamilo\PluginBundle\MigrationMoodle\Traits\MapTrait\MapTrait;

/**
 * Class CourseExtractor.
 *
 * Extractor for course already extracted and loaded.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Extractor
 */
class CourseExtractor extends BaseExtractor
{
    use MapTrait;

    /**
     * CourseExtractor constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        parent::__construct($configuration);

        $this->calledClass = EfcCoursesTask::class;
    }

    /**
     * @param array $sourceData
     *
     * @return bool
     */
    public function filter(array $sourceData)
    {
        $taskName = $this->getTaskName();

        $courseId = $sourceData['id'];

        if (isset($sourceData['course'])) {
            $courseId = $sourceData['course'];
        }

        $result = \Database::select(
            'COUNT(1) AS c',
            'plugin_migrationmoodle_item i INNER JOIN plugin_migrationmoodle_task t ON i.task_id = t.id',
            [
                'where' => [
                    't.name = ? AND i.extracted_id = ?' => [$taskName, $courseId]
                ]
            ],
            'first'
        );

        return $result['c'] == 0;
    }
}
