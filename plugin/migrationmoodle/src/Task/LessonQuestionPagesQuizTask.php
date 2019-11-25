<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonQuestionPagesQuizLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpItemLookup;

/**
 * Class LessonQuestionPagesQuizTask.
 *
 * Task to convert the question pages from a moodle lesson in one chamilo quiz with one question.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class LessonQuestionPagesQuizTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => 'SELECT lp.id, l.course, lp.title
                FROM mdl_lesson_pages lp
                INNER JOIN mdl_lesson l ON lp.lessonid = l.id
                WHERE lp.qtype IN (1, 2, 3, 5, 8, 10)',
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
                'item_id' => [
                    'class' => LoadedLpItemLookup::class,
                    'properties' => ['id'],
                ],
                'item_title' => 'title',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => LessonQuestionPagesQuizLoader::class,
        ];
    }
}
