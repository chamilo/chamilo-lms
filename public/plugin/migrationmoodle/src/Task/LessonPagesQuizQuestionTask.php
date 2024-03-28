<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonQuestionPagesQuestionLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLessonPageQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\QuizQuestionTypeFromLessonPage;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\ReplaceFilePaths;

/**
 * Class LessonPagesQuizQuestionTask.
 *
 * Task to convert the question pages from a moodle lesson in one chamilo question to be added in quiz already creadted.
 *
 * The types question pages are:
 * 1, short answer; 2, true-false; 3, multiple choice or multiple answer; 5, matching; 8, numerical; 10, essay.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class LessonPagesQuizQuestionTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => 'SELECT lp.id, l.course, lp.contents, lp.qoption, lp.qtype
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
                'quiz_id' => [
                    'class' => LoadedLessonPageQuizLookup::class,
                    'properties' => ['id'],
                ],
                'question_title' => [
                    'class' => ReplaceFilePaths::class,
                    'properties' => ['contents', 'course'],
                ],
                'question_type' => [
                    'class' => QuizQuestionTypeFromLessonPage::class,
                    'properties' => ['qtype', 'qoption'],
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
            'class' => LessonQuestionPagesQuestionLoader::class,
        ];
    }
}
