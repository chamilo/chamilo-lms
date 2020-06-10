<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UserQuizAttemptLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\DateTimeObject;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserSessionLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\QuizDataTracking;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\Subtract;

/**
 * Class UsersQuizzesAttemptsTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UsersQuizzesAttemptsTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $userFilter = $this->plugin->getUserFilterSetting();
        $userCondition = '';

        if (!empty($userFilter)) {
            $userCondition = "INNER JOIN mdl_user u ON qa.userid = u.id WHERE u.username LIKE '$userFilter%'";
        }

        return [
            'class' => LoadedUsersFilterExtractor::class,
            'query' => "SELECT
                    qa.id,
                    qa.quiz,
                    qa.userid,
                    qa.layout,
                    qa.state,
                    qa.timestart,
                    qa.timefinish,
                    qa.sumgrades real_result,
                    q.sumgrades weighting
                FROM mdl_quiz_attempts qa
                INNER JOIN mdl_quiz q ON qa.quiz = q.id
                $userCondition
                ORDER BY qa.userid, q.id, qa.attempt",
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
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['userid'],
                ],
                'date' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['timestart'],
                ],
                'exo_id' => [
                    'class' => LoadedQuizLookup::class,
                    'properties' => ['quiz'],
                ],
                'result' => 'real_result',
                'weighting' => 'weighting',
                'data_tracking' => [
                    'class' => QuizDataTracking::class,
                    'properties' => ['quiz', 'layout'],
                ],
                'session_id' => [
                    'class' => LoadedUserSessionLookup::class,
                    'properties' => ['userid'],
                ],
                'duration' => [
                    'class' => Subtract::class,
                    'properties' => ['timefinish', 'timestart'],
                ],
                'status' => 'state',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UserQuizAttemptLoader::class,
        ];
    }
}
