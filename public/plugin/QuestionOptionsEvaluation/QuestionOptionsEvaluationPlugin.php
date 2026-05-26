<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Class QuestionOptionsEvaluationPlugin.
 */
class QuestionOptionsEvaluationPlugin extends Plugin
{
    /**
     * Backward-compatible alias used by legacy exercise code.
     * The Plugin base class maps tool_enable to the real plugin active state.
     */
    public const SETTING_ENABLE = 'tool_enable';
    public const SETTING_MAX_SCORE = 'exercise_max_score';
    public const EXTRAFIELD_FORMULA = 'quiz_evaluation_formula';
    public const TEACHER_MODIFY_ACTION_CALLBACK = 'QuestionOptionsEvaluationPlugin::filterModify';

    public const FORMULA_NONE = -1;
    public const FORMULA_RECALCULATE = 0;
    public const FORMULA_SUCCESS_MINUS_FAILURES = 1;
    public const FORMULA_SUCCESS_MINUS_HALF_FAILURES = 2;
    public const FORMULA_SUCCESS_MINUS_THIRD_FAILURES = 3;

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
        $exerciseId = (int) $exerciseId;
        if ($exerciseId <= 0) {
            return '';
        }

        $plugin = self::create();
        if (!$plugin->isEnabled()) {
            return '';
        }

        $directory = basename(__DIR__);
        $title = get_plugin_lang('plugin_title', self::class);
        $url = api_get_path(WEB_PLUGIN_PATH).$directory.'/evaluation.php?exercise='.$exerciseId;
        $courseRequest = api_get_cidreq();

        if (!empty($courseRequest)) {
            $url .= '&'.$courseRequest;
        }

        return Display::url(
            Display::getMdiIcon(ActionIcon::GRADE, 'ch-tool-icon', null, $iconSize, $title),
            $url,
            [
                'class' => 'ajax',
                'data-size' => 'md',
                'data-title' => $title,
            ]
        );
    }

    public function install()
    {
        $this->createExtraField();
        $this->registerTeacherModifyAction();
    }

    public function uninstall()
    {
        $this->unregisterTeacherModifyAction();
        $this->removeExtraField();
    }

    /**
     * @return Plugin
     */
    public function performActionsAfterConfigure()
    {
        $this->registerTeacherModifyAction();

        return $this;
    }

    public function isEnabled(): bool
    {
        return parent::isEnabled();
    }

    public function shouldApplyFormula(int $formula): bool
    {
        return \in_array(
            $formula,
            [
                self::FORMULA_SUCCESS_MINUS_FAILURES,
                self::FORMULA_SUCCESS_MINUS_HALF_FAILURES,
                self::FORMULA_SUCCESS_MINUS_THIRD_FAILURES,
            ],
            true
        );
    }

    private function normalizeFormula($formula): int
    {
        if (null === $formula) {
            return self::FORMULA_NONE;
        }

        if (is_string($formula) && '' === trim($formula)) {
            return self::FORMULA_NONE;
        }

        $formula = (int) $formula;
        $allowed = [
            self::FORMULA_NONE,
            self::FORMULA_RECALCULATE,
            self::FORMULA_SUCCESS_MINUS_FAILURES,
            self::FORMULA_SUCCESS_MINUS_HALF_FAILURES,
            self::FORMULA_SUCCESS_MINUS_THIRD_FAILURES,
        ];

        return \in_array($formula, $allowed, true) ? $formula : self::FORMULA_NONE;
    }

    /**
     * Persist the selected formula for a given Exercise.
     *
     * @param int      $formula
     * @param Exercise $exercise
     */
    public function saveFormulaForExercise($formula, Exercise $exercise)
    {
        $formula = $this->normalizeFormula($formula);
        $exerciseId = $this->getExerciseId($exercise);

        if ($exerciseId <= 0) {
            return;
        }

        // Do not rewrite question or answer ponderations here.
        // The plugin stores only the selected formula and applies score overrides at result time.
        // This keeps the original Chamilo scoring available when the plugin is disabled.

        $this->createExtraField();

        // Use the current Chamilo extra-field API first. In Chamilo 2 the
        // submitted field key must be prefixed with "extra_".
        $extraFieldValue = new ExtraFieldValue(self::EF_HANDLER);
        $extraFieldValue->saveFieldValues(
            [
                'item_id' => $exerciseId,
                'extra_'.self::EXTRAFIELD_FORMULA => (string) $formula,
            ]
        );

        // Keep a direct write fallback because ExtraFieldValue::save() can create
        // the row without filling field_value when called with a legacy payload.
        $this->saveFormulaValueDirectly($exerciseId, (string) $formula);
    }

    /**
     * Read formula for an Exercise
     *
     * @param int $exerciseId
     *
     * @return int
     */
    public function getStoredFormulaForExercise($exerciseId): int
    {
        $exerciseId = (int) $exerciseId;
        if ($exerciseId <= 0) {
            return self::FORMULA_NONE;
        }

        $value = $this->getStoredFormulaValueDirectly($exerciseId);
        if (null !== $value) {
            return $this->normalizeFormula($value);
        }

        $efv = new ExtraFieldValue(self::EF_HANDLER);
        $value = $efv->get_values_by_handler_and_field_variable($exerciseId, self::EXTRAFIELD_FORMULA);

        if (empty($value) || !is_array($value)) {
            return self::FORMULA_NONE;
        }

        if (array_key_exists('field_value', $value)) {
            return $this->normalizeFormula($value['field_value']);
        }

        if (array_key_exists('value', $value)) {
            return $this->normalizeFormula($value['value']);
        }

        return self::FORMULA_NONE;
    }

    /**
     * Return only formulas that must alter the final exercise score.
     *
     * Legacy exercise.lib.php checks this value with !empty(). Returning null
     * for "No formula" and "Recalculate question scores" prevents those
     * options from being treated as final-score formulas.
     */
    public function getFormulaForExercise($exerciseId): ?int
    {
        $formula = $this->getStoredFormulaForExercise($exerciseId);

        return $this->shouldApplyFormula($formula) ? $formula : null;
    }

    /**
     * @return int
     */
    public function getMaxScore()
    {
        $max = (int) $this->get(self::SETTING_MAX_SCORE);

        return $max > 0 ? $max : 10;
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
        $formula = $this->normalizeFormula($formula);
        if (!$this->shouldApplyFormula($formula)) {
            return 0;
        }

        $em = Database::getManager();

        /** @var TrackEExercise|null $eTrack */
        $eTrack = $em->find(TrackEExercise::class, (int) $trackId);
        if (!$eTrack) {
            return 0;
        }

        $questionIds = array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(',', $eTrack->getDataTracking())
                ),
                'strlen'
            )
        );

        $totalQuestions = count($questionIds);
        if (0 === $totalQuestions) {
            $totalQuestions = $eTrack->getAttempts()->count();
        }

        if (0 === $totalQuestions) {
            return 0;
        }

        $counts = ['correct' => 0, 'incorrect' => 0];

        /** @var TrackEAttempt $qTrack */
        foreach ($eTrack->getAttempts() as $qTrack) {
            if ($qTrack->getMarks() > 0) {
                $counts['correct']++;

                continue;
            }

            if ('' === trim($qTrack->getAnswer())) {
                continue;
            }

            // In Chamilo's default scoring, a wrong single-choice answer is usually stored as 0.
            // Count non-positive answered attempts as failures without changing the original answer weights.
            $counts['incorrect']++;
        }

        $result = $counts['correct'];

        switch ($formula) {
            case self::FORMULA_SUCCESS_MINUS_FAILURES:
                $result = $counts['correct'] - $counts['incorrect'];
                break;
            case self::FORMULA_SUCCESS_MINUS_HALF_FAILURES:
                $result = $counts['correct'] - $counts['incorrect'] / 2;
                break;
            case self::FORMULA_SUCCESS_MINUS_THIRD_FAILURES:
                $result = $counts['correct'] - $counts['incorrect'] / 3;
                break;
            default:
                // Keep default = only positives counted.
                break;
        }

        $maxScore = $eTrack->getMaxScore();
        if ($maxScore <= 0) {
            $maxScore = $this->getMaxScore();
        }

        $score = ($result / $totalQuestions) * $maxScore;

        return $score >= 0 ? $score : 0;
    }

    private function saveFormulaValueDirectly(int $exerciseId, string $formula): void
    {
        $fieldId = $this->getFormulaExtraFieldId();
        if ($fieldId <= 0) {
            return;
        }

        $table = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $formula = Database::escape_string($formula);
        $now = Database::escape_string(api_get_utc_datetime());

        $sql = "SELECT id FROM $table WHERE field_id = $fieldId AND item_id = $exerciseId ORDER BY id DESC LIMIT 1";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        if (!empty($row['id'])) {
            $id = (int) $row['id'];
            Database::query(
                "UPDATE $table SET field_value = '$formula', updated_at = '$now' WHERE id = $id"
            );

            return;
        }

        Database::query(
            "INSERT INTO $table (field_id, item_id, field_value, created_at, updated_at)
             VALUES ($fieldId, $exerciseId, '$formula', '$now', '$now')"
        );
    }

    private function getStoredFormulaValueDirectly(int $exerciseId): ?string
    {
        $fieldId = $this->getFormulaExtraFieldId();
        if ($fieldId <= 0) {
            return null;
        }

        $table = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $sql = "SELECT field_value FROM $table WHERE field_id = $fieldId AND item_id = $exerciseId ORDER BY id DESC LIMIT 1";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        if (empty($row) || !array_key_exists('field_value', $row)) {
            return null;
        }

        if ('' === (string) $row['field_value']) {
            return null;
        }

        return (string) $row['field_value'];
    }

    private function getFormulaExtraFieldId(): int
    {
        $extraField = new ExtraField(self::EF_HANDLER);
        $field = $extraField->get_handler_field_info_by_field_variable(self::EXTRAFIELD_FORMULA);

        if (empty($field['id'])) {
            return 0;
        }

        return (int) $field['id'];
    }

    private function getExerciseId(Exercise $exercise): int
    {
        if (method_exists($exercise, 'getId')) {
            return (int) $exercise->getId();
        }

        if (property_exists($exercise, 'iId')) {
            return (int) $exercise->iId;
        }

        return 0;
    }

    /**
     * Legacy helper kept only for backward compatibility.
     *
     * It is intentionally not called from saveFormulaForExercise(), because changing
     * c_quiz_question/c_quiz_answer ponderations would alter the original Chamilo
     * behavior even after disabling the plugin.
     *
     * @param int      $formula
     * @param Exercise $exercise
     */
    private function recalculateQuestionScore($formula, Exercise $exercise)
    {
        $formula = $this->normalizeFormula($formula);
        if (self::FORMULA_NONE === $formula) {
            return;
        }

        $tblQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tblAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);

        foreach ($exercise->questionList as $questionId) {
            $question = Question::read($questionId, $exercise->course, false);
            if (!$question || !\in_array($question->selectType(), [UNIQUE_ANSWER, MULTIPLE_ANSWER], true)) {
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

                if ($question->selectType() === MULTIPLE_ANSWER || self::FORMULA_RECALCULATE === $formula) {
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

    private function registerTeacherModifyAction()
    {
        $actions = $this->getAdditionalTeacherModifyActions();

        if ($this->hasTeacherModifyAction($actions)) {
            return;
        }

        $actions[] = self::TEACHER_MODIFY_ACTION_CALLBACK;
        $this->saveAdditionalTeacherModifyActions($actions);
    }

    private function unregisterTeacherModifyAction()
    {
        $actions = $this->getAdditionalTeacherModifyActions();

        if (!$this->hasTeacherModifyAction($actions)) {
            return;
        }

        $actions = array_values(
            array_filter(
                $actions,
                function ($action) {
                    return !$this->isTeacherModifyAction($action);
                }
            )
        );

        $this->saveAdditionalTeacherModifyActions($actions);
    }

    private function getAdditionalTeacherModifyActions(): array
    {
        $actions = api_get_setting('exercise.exercise_additional_teacher_modify_actions', true);

        return $this->normalizeTeacherModifyActions($actions);
    }

    private function normalizeTeacherModifyActions($actions): array
    {
        if (empty($actions)) {
            return [];
        }

        if (is_array($actions)) {
            return array_values($actions);
        }

        if (!is_string($actions)) {
            return [];
        }

        $decoded = json_decode($actions, true);
        if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
            return array_values($decoded);
        }

        $unserialized = @unserialize($actions, ['allowed_classes' => false]);
        if (is_array($unserialized)) {
            return array_values($unserialized);
        }

        return array_values(
            array_filter(
                array_map(
                    'trim',
                    preg_split('/\s*,\s*/', $actions) ?: []
                )
            )
        );
    }

    private function hasTeacherModifyAction(array $actions): bool
    {
        foreach ($actions as $action) {
            if ($this->isTeacherModifyAction($action)) {
                return true;
            }
        }

        return false;
    }

    private function isTeacherModifyAction($action): bool
    {
        if (self::TEACHER_MODIFY_ACTION_CALLBACK === $action) {
            return true;
        }

        return is_array($action)
            && isset($action[0], $action[1])
            && self::class === $action[0]
            && 'filterModify' === $action[1];
    }

    private function saveAdditionalTeacherModifyActions(array $actions)
    {
        try {
            Container::getSettingsManager()->updateSetting(
                'exercise.exercise_additional_teacher_modify_actions',
                implode(',', array_values($actions))
            );
        } catch (Throwable $exception) {
            error_log(
                '[QuestionOptionsEvaluation] Failed to update exercise_additional_teacher_modify_actions: '.
                $exception->getMessage()
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
            'default_value'   => (string) self::FORMULA_NONE,
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
