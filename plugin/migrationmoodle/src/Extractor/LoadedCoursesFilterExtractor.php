<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Extractor;

use Chamilo\PluginBundle\MigrationMoodle\Task\CoursesTask;

/**
 * Class LoadedCoursesFilterExtractor.
 *
 * Extractor for course already extracted and loaded.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Extractor
 */
class LoadedCoursesFilterExtractor extends FilterExtractor
{
    /**
     * LoadedCoursesFilterExtractor constructor.
     */
    public function __construct(array $configuration)
    {
        parent::__construct($configuration);

        $this->calledClass = CoursesTask::class;
    }

    /**
     * @return bool
     */
    public function filter(array $sourceData)
    {
        $courseId = $sourceData['id'];

        if (isset($sourceData['course'])) {
            $courseId = $sourceData['course'];
        }

        return !$this->existsExtracted($courseId);
    }
}
