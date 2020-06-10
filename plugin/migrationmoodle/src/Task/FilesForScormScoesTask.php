<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\FilesForScormScoLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;

/**
 * Class FilesForScormScoesTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class FilesForScormScoesTask extends BaseTask
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
                  f.mimetype,
                  s.name scorm_name,
                  cm.course
                FROM mdl_files f
                INNER JOIN mdl_context ctx ON f.contextid = ctx.id
                INNER JOIN mdl_course_modules cm ON ctx.instanceid = cm.id
                INNER JOIN mdl_modules m ON cm.module = m.id
                INNER JOIN mdl_scorm s ON (cm.course = s.course AND cm.instance = s.id)
                WHERE
                  m.name = 'scorm'
                  AND ctx.contextlevel = 70
                  AND f.filename NOT IN ('.', '..')
                  AND s.reference != f.filename
                  AND f.filearea = 'content'
                  AND f.component = 'mod_scorm'
                ORDER BY s.course, s.id",
        ];
    }

    /**
     * {@inheritdoc}
     */

    /**
     * @return array
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'contenthash' => 'contenthash',
                'filepath' => 'filepath',
                'filename' => 'filename',
                'mimetype' => 'mimetype',
                'c_id' => [
                    'class' => LoadedCourseLookup::class,
                    'properties' => ['course'],
                ],
                'lp_name' => 'scorm_name',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => FilesForScormScoLoader::class,
        ];
    }
}
