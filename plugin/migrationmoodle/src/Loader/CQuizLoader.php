<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CQuizLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CQuizLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @throws \Doctrine\DBAL\DBALException
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
        $exercise->updateType(2);
        $exercise->setRandom(0);
        $exercise->updateRandomAnswers($incomingData['randomAnswers']);
        $exercise->updateResultsDisabled(0);
        $exercise->updateExpiredTime($incomingData['enabletimercontroltotalminutes']);
        //$exercise->updatePropagateNegative($incomingData['propagate_neg']);
        //$exercise->updateSaveCorrectAnswers($incomingData['save_correct_answers']);
        //$exercise->updateRandomByCat($incomingData['randomByCat']);
        $exercise->updateTextWhenFinished('');
        $exercise->updateDisplayCategoryName(1);
        //$exercise->updateReviewAnswers($incomingData['review_answers']);
        $exercise->updatePassPercentage($incomingData['pass_percentage']);
        //$exercise->updateCategories($incomingData['category']);
        //$exercise->updateEndButton($incomingData['end_button']);
        //$exercise->setOnSuccessMessage($incomingData['on_success_message']);
        //$exercise->setOnFailedMessage($incomingData['on_failed_message']);
        //$exercise->updateEmailNotificationTemplate($incomingData['email_notification_template']);
        //$exercise->setEmailNotificationTemplateToUser($incomingData['email_notification_template_to_user']);
        //$exercise->setNotifyUserByEmail($incomingData['notify_user_by_email']);
        //$exercise->setModelType($incomingData['model_type']);
        $exercise->setQuestionSelectionType(1);
        $exercise->setHideQuestionTitle(0);
        $exercise->sessionId = 0;
        //$exercise->setScoreTypeModel($incomingData['score_type_model']);
        //$exercise->setGlobalCategoryId($incomingData['global_category_id']);
        //$exercise->setShowPreviousButton($incomingData['show_previous_button']);
        //$exercise->setNotifications($incomingData['notifications']);
        //$exercise->setExerciseCategoryId($incomingData['exercise_category_id']);
        $exercise->setPageResultConfiguration($incomingData);
        $exercise->start_time = api_get_utc_datetime($incomingData['start_time'], true);
        $exercise->end_time = api_get_utc_datetime($incomingData['end_time'], true);
        $exercise->expired_time = $incomingData['enabletimercontroltotalminutes'];
        $exercise->random_answers = $incomingData['randomAnswers'] == 1 ? 1 : 0;

        $iId = $exercise->save();

        return $iId;
    }
}
