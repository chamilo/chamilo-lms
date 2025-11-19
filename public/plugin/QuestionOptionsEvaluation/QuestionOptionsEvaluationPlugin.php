<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;

/**
 * Class QuestionOptionsEvaluationPlugin.
 */
class QuestionOptionsEvaluationPlugin extends Plugin
{
    public const SETTING_ENABLE     = 'enable';
    public const SETTING_MAX_SCORE  = 'exercise_max_score';
    public const EXTRAFIELD_FORMULA = 'quiz_evaluation_formula';

    /** Use C2 handler only. Do NOT use "quiz" to avoid warnings. */
    private const EF_HANDLER = 'exercise';

    /**
     * QuestionValuationPlugin constructor.
     */
    protected function __construct()
    {
        $version = '1.0';
        $author  = 'Angel Fernando Quiroz Campos';

        parent::__construct(
            $version,
            $author,
            [
                self::SETTING_ENABLE    => 'boolean',
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
        $title     = get_plugin_lang('plugin_title', self::class);
        $enabled   = api_get_plugin_setting('questionoptionsevaluation', 'enable');

        if ('true' !== $enabled) {
            return '';
        }

        return Display::url(
            Display::return_icon('options_evaluation.png', $title, [], $iconSize),
            api_get_path(WEB_PATH)."plugin/$directory/evaluation.php?exercise=$exerciseId",
            [
                'class'      => 'ajax',
                'data-size'  => 'md',
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
     * Persist the selected formula for a given Exercise.
     *
     * @param int      $formula
     * @param Exercise $exercise
     */
    public function saveFormulaForExercise($formula, Exercise $exercise)
    {
        $formula = (int) $formula;

        $this->recalculateQuestionScore($formula, $exercise);

        // Write using the C2 handler to avoid "Undefined array key 'quiz'" warnings
        $extraFieldValue = new ExtraFieldValue(self::EF_HANDLER);
        $extraFieldValue->save(
            [
                'item_id'  => (int) $exercise->iid,
                'variable' => self::EXTRAFIELD_FORMULA,
                'value'    => $formula,
            ]
        );
    }

    /**
     * Read formula for an Exercise
     *
     * @param int $exerciseId
     *
     * @return int
     */
    public function getFormulaForExercise($exerciseId)
    {
        $efv   = new ExtraFieldValue(self::EF_HANDLER);
        $value = $efv->get_values_by_handler_and_field_variable((int) $exerciseId, self::EXTRAFIELD_FORMULA);

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
     * Compute result using negative marking formulas.
     *
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

        /** @var TrackEExercise|null $eTrack */
        $eTrack = $em->find(TrackEExercise::class, (int) $trackId);
        if (!$eTrack) {
            return 0;
        }

        $qTracks = $em
            ->createQuery(
                'SELECT a FROM ChamiloCoreBundle:TrackEAttempt a
                 WHERE a.exeId = :id AND a.userId = :user AND a.cId = :course AND a.sessionId = :session'
            )
            ->setParameters(
                [
                    'id'      => $eTrack->getExeId(),
                    'course'  => $eTrack->getCourse(),
                    'session' => $eTrack->getSession(),
                    'user'    => $eTrack->getUser(),
                ]
            )
            ->getResult();

        // Guard: avoid division by zero if there are no attempts
        if (!$qTracks) {
            return 0;
        }

        $counts = ['correct' => 0, 'incorrect' => 0];

        /** @var TrackEAttempt $qTrack */
        foreach ($qTracks as $qTrack) {
            if ($qTrack->getMarks() > 0) {
                $counts['correct']++;
            } elseif ($qTrack->getMarks() < 0) {
                $counts['incorrect']++;
            }
        }

        // Safe default then apply formula
        $formula = (int) $formula;
        $result  = $counts['correct'];

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
            default:
                // Keep default = only positives counted
                break;
        }

        $score = ($result / count($qTracks)) * $this->getMaxScore();

        return $score >= 0 ? $score : 0;
    }

    /**
     * Recalculate question scores according to the selected formula.
     *
     * @param int      $formula
     * @param Exercise $exercise
     */
    private function recalculateQuestionScore($formula, Exercise $exercise)
    {
        $tblQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tblAnswer   = Database::get_course_table(TABLE_QUIZ_ANSWER);

        foreach ($exercise->questionList as $questionId) {
            $question = Question::read($questionId, $exercise->course, false);
            if (!in_array($question->selectType(), [UNIQUE_ANSWER, MULTIPLE_ANSWER], true)) {
                continue;
            }

            $questionAnswers = new Answer($questionId, $exercise->course_id, $exercise);
            $counts          = array_count_values($questionAnswers->correct);

            // Ensure keys exist to avoid "Undefined index" and division by zero
            $totalCorrect   = (int) ($counts[1] ?? 0);
            $totalIncorrect = (int) ($counts[0] ?? 0);

            $questionPonderation = 0.0;

            foreach ($questionAnswers->correct as $i => $isCorrect) {
                if (!isset($questionAnswers->iid[$i])) {
                    continue;
                }

                $iid = (int) $questionAnswers->iid[$i];

                if ($question->selectType() === MULTIPLE_ANSWER || 0 === (int) $formula) {
                    // Multiple-answer or formula 0 distributes weights across options.
                    // Use max(1, totalX) to avoid division by zero.
                    $ponderation = (1 == $isCorrect)
                        ? 1 / max(1, $totalCorrect)
                        : -1 / max(1, $totalIncorrect);
                } else {
                    // Single-answer: correct=1; wrong=-1/formula
                    $ponderation = (1 == $isCorrect) ? 1.0 : -1.0 / (int) $formula;
                }

                if ($ponderation > 0) {
                    $questionPonderation += $ponderation;
                }

                Database::query("UPDATE $tblAnswer SET ponderation = ".(float)$ponderation." WHERE iid = $iid");
            }

            Database::query(
                "UPDATE $tblQuestion SET ponderation = ".(float)$questionPonderation." WHERE iid = ".(int)$question->iid
            );
        }
    }

    /**
     * Creates the ExtraField for storing the evaluation formula.
     * We force integer 0/1 for boolean flags to satisfy strict MySQL.
     */
    private function createExtraField()
    {
        // Use ONLY the C2 handler; "quiz" is not a valid handler in C2 and triggers warnings.
        $extraField = new ExtraField(self::EF_HANDLER);

        // If the field already exists, do nothing (idempotent install)
        if (false !== $extraField->get_handler_field_info_by_field_variable(self::EXTRAFIELD_FORMULA)) {
            return;
        }

        // Determine a safe value_type to satisfy NOT NULL columns in strict MySQL
        $valueType = 0;
        if (defined('ExtraField::VALUE_TYPE_TEXT')) {
            $valueType = (int) constant('ExtraField::VALUE_TYPE_TEXT');
        } elseif (defined('ExtraField::VALUE_TEXT')) {
            $valueType = (int) constant('ExtraField::VALUE_TEXT');
        } elseif (defined('ExtraField::TYPE_TEXT')) {
            $valueType = (int) constant('ExtraField::TYPE_TEXT');
        }

        // Build payload including explicit ints for boolean-like fields
        $payload = [
            'variable'        => self::EXTRAFIELD_FORMULA,
            'field_type'      => ExtraField::FIELD_TYPE_TEXT,
            'display_text'    => $this->get_lang('EvaluationFormula'),
            'visible'         => 1,
            'visible_to_self' => 0,
            'changeable'      => 0,
            'value_type'      => $valueType,
            'default_value'   => '0',
        ];

        $extraField->save($payload);
    }

    /**
     * Removes the ExtraField (C2 handler only).
     */
    private function removeExtraField()
    {
        $extraField = new ExtraField(self::EF_HANDLER);
        $value      = $extraField->get_handler_field_info_by_field_variable(self::EXTRAFIELD_FORMULA);

        if (false !== $value) {
            $extraField->delete($value['id']);
        }
    }
}
