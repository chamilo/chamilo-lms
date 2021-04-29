<?php

/* For licensing terms, see /license.txt */

/**
 * Class RemedialCoursePlugin.
 */
class RemedialCoursePlugin extends Plugin
{
    const SETTING_ENABLED = 'enabled';
    const EXTRAFIELD_REMEDIAL_VARIABLE = 'remedialcourselist';

    /**
     * RemedialCoursePlugin constructor.
     */
    protected function __construct()
    {
        $settings = [
            self::SETTING_ENABLED => 'boolean',
        ];
        parent::__construct(
            '1.0',
            'Carlos Alvarado',
            $settings
        );
    }

    /**
     * Create a new instance of RemedialCoursePlugin.
     */
    public static function create(): RemedialCoursePlugin
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Perform the plugin installation.
     */
    public function install()
    {
        $this->saveRemedialField();
        $this->saveAdvanceRemedialField();
    }

    /**
     * Save the arrangement for remedialcourselist, it is adjusted internally so that the values
     * match the necessary ones.
     */
    public function saveRemedialField()
    {
        $extraField = new ExtraField('exercise');
        $remedialcourselist = $extraField->get_handler_field_info_by_field_variable(self::EXTRAFIELD_REMEDIAL_VARIABLE);
        if (false === $remedialcourselist) {
            $extraField->save([
                'field_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'variable' => self::EXTRAFIELD_REMEDIAL_VARIABLE,
                'display_text' => 'remedialCourseList',
                'default_value' => 1,
                'field_order' => 0,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 0,
            ]);
        }
    }

    /**
     * Save the arrangement for remedialadvancecourselist, it is adjusted internally so that the values
     * match the necessary ones.
     */
    public function saveAdvanceRemedialField()
    {
        $extraField = new ExtraField('exercise');
        $advancedcourselist = $extraField->get_handler_field_info_by_field_variable('advancedcourselist');
        if (false === $advancedcourselist) {
            $extraField->save([
                'field_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'variable' => 'advancedcourselist',
                'display_text' => 'advancedCourseList',
                'default_value' => 1,
                'field_order' => 0,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 0,
            ]);
        }
    }

    /**
     * Set default_value to 0.
     */
    public function uninstall()
    {
    }

    public function get_name(): string
    {
        return 'remedial_course';
    }

    public function getRemedialCourseList(
        Exercise $objExercise,
        int $userId = 0,
        int $sessionId = 0,
        bool $review = false
    ): ?string {
        if ('true' !== $this->get(self::SETTING_ENABLED)) {
            return null;
        }

        $field = new ExtraField('exercise');
        $remedialField = $field->get_handler_field_info_by_field_variable(self::EXTRAFIELD_REMEDIAL_VARIABLE);

        if (empty($remedialField)) {
            return null;
        }

        $extraFieldValue = new ExtraFieldValue('exercise');
        $remedialExcerciseField = $extraFieldValue->get_values_by_handler_and_field_variable(
            $objExercise->iId,
            self::EXTRAFIELD_REMEDIAL_VARIABLE
        );
        $remedialCourseIds = explode(';', $remedialExcerciseField['value']);

        if (empty($remedialExcerciseField['value']) || count($remedialCourseIds) == 0) {
            return null;
        }

        $questionExcluded = [
            FREE_ANSWER,
            ORAL_EXPRESSION,
            ANNOTATION,
        ];

        $userId = empty($userId) ? api_get_user_id() : $userId;

        $exerciseStatInfo = Event::getExerciseResultsByUser(
            $userId,
            $objExercise->iId,
            $objExercise->course_id,
            $sessionId
        );
        $bestAttempt = Event::get_best_attempt_exercise_results_per_user(
            $userId,
            $objExercise->iId,
            $objExercise->course_id,
            $sessionId
        );

        foreach ($exerciseStatInfo as $attempt) {
            if (!isset($bestAttempt['exe_result']) || $attempt['exe_result'] >= $bestAttempt['exe_result']) {
                $bestAttempt = $attempt;
            }

            if (!isset($attempt['question_list'])) {
                continue;
            }

            foreach ($attempt['question_list'] as $questionId => $answer) {
                $question = Question::read($questionId, api_get_course_info_by_id($attempt['c_id']));
                $questionOpen = in_array($question->type, $questionExcluded) && !$review;

                if (!$questionOpen) {
                    continue;
                }

                $score = $attempt['exe_result'];
                $comments = Event::get_comments($objExercise->iId, $questionId);

                if (empty($comments) || $score == 0) {
                    return null;
                }
            }
        }

        if (empty($bestAttempt)) {
            return null;
        }

        $canRemedial = false;

        if (isset($bestAttempt['exe_result'])) {
            $bestAttempt['exe_result'] = (int) $bestAttempt['exe_result'];
            $canRemedial = $objExercise->isBlockedByPercentage($bestAttempt);

            if (false == $canRemedial) {
                $pass = ExerciseLib::isPassPercentageAttemptPassed(
                    $objExercise,
                    $bestAttempt['exe_result'],
                    $bestAttempt['exe_weighting']
                );
                $canRemedial = !$pass;

                if (false == $canRemedial) {
                    return null;
                }
            }
        }

        // Remedial course
        if (!$canRemedial) {
            return null;
        }

        $courses = [];
        $isInASession = !empty($sessionId);

        foreach ($remedialCourseIds as $courseId) {
            $courseData = api_get_course_info_by_id($courseId);

            if (empty($courseData)) {
                continue;
            }

            if ($isInASession) {
                $courseExistsInSession = SessionManager::sessionHasCourse($sessionId, $courseData['code']);

                if ($courseExistsInSession) {
                    SessionManager::subscribe_users_to_session_course([$userId], $sessionId, $courseData['code']);
                    $courses[] = $courseData['title'];
                }
            } else {
                $isSubscribed = CourseManager::is_user_subscribed_in_course($userId, $courseData['code']);

                if (!$isSubscribed) {
                    CourseManager::subscribeUser($userId, $courseData['code']);
                    $courses[] = $courseData['title'];
                }
            }
        }

        if (0 != count($courses)) {
            return sprintf(
                $this->get_lang('SubscriptionToXRemedialCourses'),
                implode(' - ', $courses)
            );
        }

        return null;
    }
}
