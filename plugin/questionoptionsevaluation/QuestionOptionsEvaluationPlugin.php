<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEAttempt;

/**
 * Class QuestionOptionsEvaluationPlugin.
 */
class QuestionOptionsEvaluationPlugin extends Plugin
{
    const SETTING_ENABLE = 'enable';
    const SETTING_MAX_SCORE = 'exercise_max_score';

    const EXTRAFIELD_FORMULA = 'quiz_evaluation_formula';

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
     * @param Exercise $exercise
     */
    public function recalculateQuestionScore(Exercise $exercise)
    {
        foreach ($exercise->questionList as $questionId) {
            $question = Question::read($questionId);

            if (!in_array($question->selectType(), [UNIQUE_ANSWER, MULTIPLE_ANSWER])) {
                continue;
            }

            $questionAnswers = new Answer($questionId, 0, $exercise);
            $counts = array_count_values($questionAnswers->correct);
            $weighting = [];

            foreach ($questionAnswers->correct as $i => $correct) {
                $weighting[$i] = 1 == $correct ? 1 / $counts[1] : -1 / $counts[0];
            }

            $questionAnswers->new_nbrAnswers = $questionAnswers->nbrAnswers;
            $questionAnswers->new_answer = $questionAnswers->answer;
            $questionAnswers->new_comment = $questionAnswers->comment;
            $questionAnswers->new_correct = $questionAnswers->correct;
            $questionAnswers->new_weighting = $weighting;
            $questionAnswers->new_position = $questionAnswers->position;
            $questionAnswers->new_destination = $questionAnswers->destination;
            $questionAnswers->new_hotspot_coordinates = $questionAnswers->hotspot_coordinates;
            $questionAnswers->new_hotspot_type = $questionAnswers->hotspot_type;

            $allowedWeights = array_filter(
                $weighting,
                function ($weight) {
                    return $weight > 0;
                }
            );

            $questionAnswers->save();
            $question->updateWeighting(array_sum($allowedWeights));
            $question->save($exercise);
        }
    }

    /**
     * @param int      $formula
     * @param Exercise $exercise
     */
    public function saveFormulaForExercise($formula, Exercise $exercise)
    {
        $extraFieldValue = new ExtraFieldValue('quiz');
        $extraFieldValue->save(
            [
                'item_id' => $exercise->iId,
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
            } else {
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

        return ($result / count($qTracks)) * 10;
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
