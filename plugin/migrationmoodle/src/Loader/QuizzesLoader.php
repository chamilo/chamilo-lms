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
     * @param array $incomingData
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
        $exercise->active = false;

        $quizId = $exercise->save();

        \Database::getManager()
            ->createQuery('UPDATE ChamiloCourseBundle:CLpItem i SET i.path = :path WHERE i.iid = :id')
            ->setParameters(['path' => $quizId, 'id' => $incomingData['item_id']])
            ->execute();

        return $quizId;
    }
}
