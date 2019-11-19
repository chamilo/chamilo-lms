<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class LpQuizzesLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LpQuizzesLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @param array $incomingData
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $questionType = $this->getQuestionType($incomingData['qtype'], $incomingData['qoption']);

        $exercise = new \Exercise($incomingData['c_id']);
        $exercise->updateTitle(\Exercise::format_title_variable($incomingData['item_title']));
        $exercise->updateDescription('');
        $exercise->updateAttempts(0);
        $exercise->updateFeedbackType(0);
        $exercise->updateType(2);
        $exercise->setRandom(0);
        $exercise->updateRandomAnswers(0);
        $exercise->updateResultsDisabled(0);
        $exercise->updateExpiredTime(0);
        $exercise->updateTextWhenFinished('');
        $exercise->updateDisplayCategoryName(1);
        $exercise->updatePassPercentage(0);
        $exercise->setQuestionSelectionType(1);
        $exercise->setHideQuestionTitle(0);
        $exercise->sessionId = 0;
        $exercise->start_time = null;
        $exercise->end_time = null;

        $quizId = $exercise->save();

        \Database::getManager()
            ->createQuery('UPDATE ChamiloCourseBundle:CLpItem i SET i.path = :path WHERE i.iid = :id')
            ->setParameters(['path' => $quizId, 'id' => $incomingData['item_id']])
            ->execute();

        $question = \Question::getInstance($questionType);
        $question->course = api_get_course_info_by_id($incomingData['c_id']);
        $question->updateTitle($incomingData['question_title']);
        $question->updateLevel(1);
        $question->updateCategory(0);
        $question->save($exercise);

        return $question->id;
    }

    /**
     * @param int $type   Type of question page.
     * @param int $option Extra option for quesiton page.
     *
     * @throws \Exception
     *
     * @return int
     */
    private function getQuestionType($type, $option)
    {
        if (in_array($type, [1, 8])) {
            return FILL_IN_BLANKS;
        }

        if (in_array($type, [2, 3])) {
            if ($type == 3 && $option) {
                return MULTIPLE_ANSWER;
            }

            return UNIQUE_ANSWER;
        }

        if ($type == 5) {
            return MATCHING_DRAGGABLE;
        }

        if ($type == 10) {
            return FREE_ANSWER;
        }

        throw new \Exception("Type $type not found.");
    }
}
