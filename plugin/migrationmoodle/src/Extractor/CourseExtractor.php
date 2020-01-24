<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Extractor;

/**
 * Class CourseExtractor.
 *
 * Extractor for course already extracted and loaded.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Extractor
 */
class CourseExtractor extends FilterExtractor
{
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
        $courseId = $sourceData['id'];

        if (isset($sourceData['course'])) {
            $courseId = $sourceData['course'];
        }

        return !$this->existsExtracted($courseId);
    }
}
