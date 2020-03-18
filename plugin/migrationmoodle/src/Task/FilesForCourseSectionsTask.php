<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;

/**
 * Class FilesForCourseSectionsTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class FilesForCourseSectionsTask extends CourseFilesTask
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
                    AND cs.section > 0
                    AND c.id NOT IN (
                        SELECT sco.course
                        FROM mdl_scorm sco
                        INNER JOIN mdl_course_modules cm ON (sco.course = cm.course AND cm.instance = sco.id)
                        INNER JOIN mdl_modules m ON cm.module = m.id
                        INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                        WHERE m.name = 'scorm'
                    )",
        ];
    }
}
