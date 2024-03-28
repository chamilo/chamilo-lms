<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\CQuizLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\Percentage;

/**
 * Class CQuizTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class CQuizTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => 'SELECT * FROM mdl_quiz',
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
                'exerciseTitle' => 'name',
                'exerciseDescription' => 'intro',
                //'exerciseFeedbackType',
                //'results_disabled',
                //'exerciseType',
                //'question_selection_type',
                //'randomQuestions' => 'shufflequestions',
                'randomAnswers' => 'shuffleanswers',
                //'display_category_name',
                //'hide_question_title',
                'exerciseAttempts' => 'attempts',
                //'activate_start_date_check',
                'start_time' => 'timeopen',
                //'activate_end_date_check',
                'end_time' => 'timeclose',
                //'enabletimercontrol',
                'enabletimercontroltotalminutes' => 'timelimit',
                'pass_percentage' => [
                    'class' => Percentage::class,
                    'properties' => ['sumgrades', 'grade'],
                ],
                //'text_when_finished',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => CQuizLoader::class,
        ];
    }
}
