<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonQuestionPagesQuestionLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\QuestionType;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\ReplaceFilePaths;

/**
 * Class QuestionsTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class QuestionsTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT qq.id, qq.category, qq.questiontext, qq.qtype, q.course, q.id quiz_id
                FROM mdl_question qq
                INNER JOIN mdl_quiz_slots qs ON qq.id = qs.questionid
                INNER JOIN mdl_quiz q ON qs.quizid = q.id
                INNER JOIN mdl_course_modules cm ON (q.course = cm.course AND cm.instance = q.id)
                INNER JOIN mdl_modules m ON cm.module = m.id
                INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                WHERE m.name = 'quiz'",
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
                    'class' => LoadedQuizLookup::class,
                    'properties' => ['quiz_id'],
                ],
                'question_title' => [
                    'class' => ReplaceFilePaths::class,
                    'properties' => ['questiontext', 'course'],
                ],
                'question_type' => [
                    'class' => QuestionType::class,
                    'properties' => ['qtype', 'id'],
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
