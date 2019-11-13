<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LpDocumentsLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseCodeLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpFromLessonLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpItemLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\ReplaceFilePaths;

/**
 * Class LpDocumentsTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class LpDocumentsTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => 'SELECT lp.id, l.id lessonid, l.course, lp.title, lp.contents
                FROM mdl_lesson_pages lp
                INNER JOIN mdl_lesson l ON lp.lessonid = l.id
                WHERE lp.qtype = 20',
        ];
    }

    /**
     * @return array
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'c_code' => [
                    'class' => LoadedCourseCodeLookup::class,
                    'properties' => ['course'],
                ],
                'lp_id' => [
                    'class' => LoadedLpFromLessonLookup::class,
                    'properties' => ['lessonid'],
                ],
                'item_id' => [
                    'class' => LoadedLpItemLookup::class,
                    'properties' => ['id'],
                ],
                'item_title' => 'title',
                'item_content' => [
                    'class' => ReplaceFilePaths::class,
                    'properties' => ['contents', 'course'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => LpDocumentsLoader::class,
        ];
    }
}
