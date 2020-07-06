<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;

/**
 * Class FilesForCourseIntroductionsTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class FilesForCourseIntroductionsTask extends CourseFilesTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT
                    f.id,
                    f.contenthash,
                    f.filepath,
                    f.filename,
                    f.filesize,
                    f.mimetype,
                    c.id course
                FROM mdl_files f
                INNER JOIN mdl_context ctx ON f.contextid = ctx.id
                INNER JOIN mdl_course c ON ctx.instanceid = c.id
                INNER JOIN mdl_course_sections cs ON (cs.course = c.id AND cs.id = f.itemid)
                WHERE f.component = 'course'
                    AND f.filearea = 'section'
                    AND ctx.contextlevel = 50
                    AND f.filename NOT IN ('.', '..')
                    AND cs.section = 0 AND (cs.summary != '' AND cs.summary IS NOT NULL)",
        ];
    }
}
