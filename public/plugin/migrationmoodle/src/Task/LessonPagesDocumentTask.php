<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonPagesDocumentLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseSectionFromLessonLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLessonPageLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\WrapHtmlReplacingFilePaths;

/**
 * Class LessonPagesDocumentTask.
 *
 * Task for convert the Moodle lesson pages in Chamilo course documents.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class LessonPagesDocumentTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
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
                'c_id' => [
                    'class' => LoadedCourseLookup::class,
                    'properties' => ['course'],
                ],
                'lp_id' => [
                    'class' => LoadedCourseSectionFromLessonLookup::class,
                    'properties' => ['lessonid'],
                ],
                'item_id' => [
                    'class' => LoadedLessonPageLookup::class,
                    'properties' => ['id'],
                ],
                'item_title' => 'title',
                'item_content' => [
                    'class' => WrapHtmlReplacingFilePaths::class,
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
            'class' => LessonPagesDocumentLoader::class,
        ];
    }
}
