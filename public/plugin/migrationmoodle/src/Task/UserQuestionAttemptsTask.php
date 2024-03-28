<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UserQuestionAttemptLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\DateTimeObject;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedQuestionLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserSessionLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\UserQuestionAnswer;

/**
 * Class UserQuestionAttemptsTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
abstract class UserQuestionAttemptsTask extends BaseTask
{
    protected $questionType;

    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $userFilter = $this->plugin->getUserFilterSetting();

        $userFilterCondition = !empty($userFilter) ? "AND u.username LIKE '$userFilter%'" : '';

        return [
            'class' => LoadedUsersFilterExtractor::class,
            'query' => "SELECT
                    qqa.id,
                    qa.userid,
                    qa.id quiz_attempt,
                    qqa.questionid,
                    qqa.questionsummary,
                    qqa.rightanswer,
                    qqa.responsesummary,
                    qqa.timemodified,
                    qqas.fraction,
                    q.course,
                    qq.defaultmark,
                    qq.qtype
                FROM mdl_question_attempts qqa
                INNER JOIN mdl_quiz_attempts qa ON qqa.questionusageid = qa.uniqueid
                INNER JOIN mdl_question_attempt_steps qqas
                    ON (qqa.id = qqas.questionattemptid AND qa.userid = qqas.userid)
                INNER JOIN mdl_question_attempt_step_data qqasd ON qqas.id = qqasd.attemptstepid
                INNER JOIN mdl_question qq ON (qqa.questionid = qq.id)
                INNER JOIN mdl_quiz q ON (qa.quiz = q.id)
                INNER JOIN mdl_user u ON (qqas.userid = u.id)
                WHERE qqas.state NOT IN ('todo', 'complete')
                    AND (qqasd.name = '-finish' AND qqasd.value = 1)
                    AND qq.qtype = '{$this->questionType}'
                    $userFilterCondition
                ORDER BY qa.userid, qa.quiz, qqa.slot",
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'exe_id' => [
                    'class' => LoadedUserQuizLookup::class,
                    'properties' => ['quiz_attempt'],
                ],
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['userid'],
                ],
                'question_id' => [
                    'class' => LoadedQuestionLookup::class,
                    'properties' => ['questionid'],
                ],
                'answer' => [
                    'class' => UserQuestionAnswer::class,
                    'properties' => [
                        'qtype',
                        'rightanswer',
                        'responsesummary',
                        'fraction',
                        'defaultmark',
                        'questionsummary',
                        'questionid',
                    ],
                ],
                'marks' => 'fraction',
                'c_id' => [
                    'class' => LoadedCourseLookup::class,
                    'properties' => ['course'],
                ],
                'tms' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['timemodified'],
                ],
                'session_id' => [
                    'class' => LoadedUserSessionLookup::class,
                    'properties' => ['userid'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UserQuestionAttemptLoader::class,
        ];
    }
}
