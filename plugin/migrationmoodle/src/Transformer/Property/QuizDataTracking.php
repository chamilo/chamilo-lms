<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Doctrine\DBAL\DBALException;

/**
 * Class QuizDataTracking.
 *
 * Transform mdl_quiz_attempt.layout to track_e_exercises.data_tracking.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class QuizDataTracking extends LoadedQuestionLookup
{
    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function transform(array $data)
    {
        list($mQuizId, $mAttemptLayout) = array_values($data);

        $mAttemptLayout = explode(',', $mAttemptLayout);
        $mAttemptLayout = array_filter($mAttemptLayout);

        $tracking = [];

        foreach ($mAttemptLayout as $mQuestionSlot) {
            $mQuestionId = $this->findQuestionBySlotInQuiz($mQuizId, $mQuestionSlot);

            $tracking[] = parent::transform([$mQuestionId]);
        }

        return implode(',', $tracking);
    }

    /**
     * @param int $quizId
     * @param int $slot
     *
     * @throws \Exception
     *
     * @return mixed
     */
    private function findQuestionBySlotInQuiz($quizId = 0, $slot = 0)
    {
        try {
            $connection = \MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $exception) {
            throw new \Exception('Unable to start connection.', 0, $exception);
        }

        try {
            $sql = "SELECT questionid FROM mdl_quiz_slots WHERE slot = ? AND quizid = ?";

            $result = $connection->fetchAssoc($sql, [$slot, $quizId]);
        } catch (DBALException $exception) {
            throw new \Exception("Unable to execute query '{$this->query}'.", 0, $exception);
        }

        $connection->close();

        return $result['questionid'];
    }
}
