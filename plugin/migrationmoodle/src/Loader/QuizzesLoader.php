<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class QuizzesLoader.
 *
 * Loader for create a Chamilo quiz inside a learning path item coming from a Moodle quiz.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class QuizzesLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $exercise = new \Exercise($incomingData['c_id']);
        $exercise->updateTitle(\Exercise::format_title_variable($incomingData['exerciseTitle']));
        $exercise->updateDescription($incomingData['exerciseDescription']);
        $exercise->updateAttempts($incomingData['exerciseAttempts']);
        $exercise->updateFeedbackType(0);
        $exercise->updateType(ALL_ON_ONE_PAGE);
        $exercise->setRandom(0);
        $exercise->updateRandomAnswers($incomingData['randomAnswers']);
        $exercise->updateResultsDisabled(0);
        $exercise->updateExpiredTime($incomingData['enabletimercontroltotalminutes']);
        $exercise->updateTextWhenFinished('');
        $exercise->updateDisplayCategoryName(1);
        $exercise->updatePassPercentage($incomingData['pass_percentage']);
        $exercise->setQuestionSelectionType(1);
        $exercise->setHideQuestionTitle(0);
        $exercise->sessionId = 0;
        $exercise->start_time = api_get_utc_datetime($incomingData['start_time'], true);
        $exercise->end_time = api_get_utc_datetime($incomingData['end_time'], true);

        $quizId = $exercise->save();

        \Database::query("UPDATE c_quiz SET active = 0 WHERE iid = $quizId");
        \Database::query("UPDATE c_lp_item SET path = '$quizId' WHERE iid = {$incomingData['item_id']}");

        return $quizId;
    }
}
