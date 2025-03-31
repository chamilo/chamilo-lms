<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEAttempt;

/**
 * Class QuestionOptionsEvaluationPlugin.
 */
class QuestionOptionsEvaluationPlugin extends Plugin
{
    public const SETTING_ENABLE = 'enable';
    public const SETTING_MAX_SCORE = 'exercise_max_score';
    public const EXTRAFIELD_FORMULA = 'quiz_evaluation_formula';

    /**
     * QuestionValuationPlugin constructor.
     */
    protected function __construct()
    {
        $version = '1.0';
        $author = 'Angel Fernando Quiroz Campos';

        parent::__construct(
            $version,
            $author,
            [
                self::SETTING_ENABLE => 'boolean',
                self::SETTING_MAX_SCORE => 'text',
            ]
        );
    }

    /**
     * @return QuestionOptionsEvaluationPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @param int $exerciseId
     * @param int $iconSize
     *
     * @return string
     */
    public static function filterModify($exerciseId, $iconSize = ICON_SIZE_SMALL)
    {
        $directory = basename(__DIR__);
        $title = get_plugin_lang('plugin_title', self::class);
        $enabled = api_get_plugin_setting('questionoptionsevaluation', 'enable');

        if ('true' !== $enabled) {
            return '';
        }

        return Display::url(
            Display::return_icon('options_evaluation.png', $title, [], $iconSize),
            api_get_path(WEB_PATH)."plugin/$directory/evaluation.php?exercise=$exerciseId",
            [
                'class' => 'ajax',
                'data-size' => 'md',
                'data-title' => get_plugin_lang('plugin_title', self::class),
            ]
        );
    }

    public function install()
    {
        $this->createExtraField();
    }

    public function uninstall()
    {
        $this->removeExtraField();
    }

    /**
     * @return Plugin
     */
    public function performActionsAfterConfigure()
    {
        return $this;
    }

    /**
     * @param int $formula
     */
    public function saveFormulaForExercise($formula, Exercise $exercise)
    {
        $this->recalculateQuestionScore($formula, $exercise);

        $extraFieldValue = new ExtraFieldValue('quiz');
        $extraFieldValue->save(
            [
                'item_id' => $exercise->iid,
                'variable' => self::EXTRAFIELD_FORMULA,
                'value' => $formula,
            ]
        );
    }

    /**
     * @param int $exerciseId
     *
     * @return int
     */
    public function getFormulaForExercise($exerciseId)
    {
        $extraFieldValue = new ExtraFieldValue('quiz');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable(
            $exerciseId,
            self::EXTRAFIELD_FORMULA
        );

        if (empty($value)) {
            return 0;
        }

        return (int) $value['value'];
    }

    /**
     * @return int
     */
    public function getMaxScore()
    {
        $max = $this->get(self::SETTING_MAX_SCORE);

        if (!empty($max)) {
            return (int) $max;
        }

        return 10;
    }

    /**
     * @param int $trackId
     * @param int $formula
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return float|int
     */
    public function getResultWithFormula($trackId, $formula)
    {
        $em = Database::getManager();

        $eTrack = $em->find('ChamiloCoreBundle:TrackEExercises', $trackId);

        $qTracks = $em
            ->createQuery(
                'SELECT a FROM ChamiloCoreBundle:TrackEAttempt a
                WHERE a.exeId = :id AND a.userId = :user AND a.cId = :course AND a.sessionId = :session'
            )
            ->setParameters(
                [
                    'id' => $eTrack->getExeId(),
                    'course' => $eTrack->getCId(),
                    'session' => $eTrack->getSessionId(),
                    'user' => $eTrack->getExeUserId(),
                ]
            )
            ->getResult();

        $counts = ['correct' => 0, 'incorrect' => 0];

        /** @var TrackEAttempt $qTrack */
        foreach ($qTracks as $qTrack) {
            if ($qTrack->getMarks() > 0) {
                $counts['correct']++;
            } elseif ($qTrack->getMarks() < 0) {
                $counts['incorrect']++;
            }
        }

        switch ($formula) {
            case 1:
                $result = $counts['correct'] - $counts['incorrect'];
                break;
            case 2:
                $result = $counts['correct'] - $counts['incorrect'] / 2;
                break;
            case 3:
                $result = $counts['correct'] - $counts['incorrect'] / 3;
                break;
        }

        $score = ($result / count($qTracks)) * $this->getMaxScore();

        return $score >= 0 ? $score : 0;
    }

    /**
     * @param int $formula
     */
    private function recalculateQuestionScore($formula, Exercise $exercise)
    {
        $tblQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tblAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);

        foreach ($exercise->questionList as $questionId) {
            $question = Question::read($questionId, $exercise->course, false);
            if (!in_array($question->selectType(), [UNIQUE_ANSWER, MULTIPLE_ANSWER])) {
                continue;
            }

            $questionAnswers = new Answer($questionId, $exercise->course_id, $exercise);
            $counts = array_count_values($questionAnswers->correct);

            $questionPonderation = 0;

            foreach ($questionAnswers->correct as $i => $isCorrect) {
                if (!isset($questionAnswers->iid[$i])) {
                    continue;
                }

                $iid = $questionAnswers->iid[$i];

                if ($question->selectType() == MULTIPLE_ANSWER || 0 === $formula) {
                    $ponderation = 1 == $isCorrect ? 1 / $counts[1] : -1 / $counts[0];
                } else {
                    $ponderation = 1 == $isCorrect ? 1 : -1 / $formula;
                }

                if ($ponderation > 0) {
                    $questionPonderation += $ponderation;
                }

                Database::query("UPDATE $tblAnswer SET ponderation = $ponderation WHERE iid = $iid");
            }

            Database::query("UPDATE $tblQuestion SET ponderation = $questionPonderation WHERE iid = {$question->iid}");
        }
    }

    /**
     * Creates an extrafield.
     */
    private function createExtraField()
    {
        $extraField = new ExtraField('quiz');

        if (false === $extraField->get_handler_field_info_by_field_variable(self::EXTRAFIELD_FORMULA)) {
            $extraField
                ->save(
                    [
                        'variable' => self::EXTRAFIELD_FORMULA,
                        'field_type' => ExtraField::FIELD_TYPE_TEXT,
                        'display_text' => $this->get_lang('EvaluationFormula'),
                        'visible_to_self' => false,
                        'changeable' => false,
                    ]
                );
        }
    }

    /**
     * Removes the extrafield .
     */
    private function removeExtraField()
    {
        $extraField = new ExtraField('quiz');
        $value = $extraField->get_handler_field_info_by_field_variable(self::EXTRAFIELD_FORMULA);

        if (false !== $value) {
            $extraField->delete($value['id']);
        }
    }
}
