<?php

/* For licensing terms, see /license.txt */

/**
 * Class RemedialCoursePlugin.
 */
class RemedialCoursePlugin extends Plugin
{
    public const SETTING_ENABLED = 'enabled';
    public const EXTRAFIELD_REMEDIAL_VARIABLE = 'remedialcourselist';
    public const EXTRAFIELD_ADVACED_VARIABLE = 'advancedcourselist';

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
        $advancedcourselist = $extraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_ADVACED_VARIABLE
        );
        if (false === $advancedcourselist) {
            $extraField->save([
                'field_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'variable' => self::EXTRAFIELD_ADVACED_VARIABLE,
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

    /**
     * When a student completes the number of attempts and fails the exam, she is enrolled in a series of remedial
     * courses BT#18165.
     */
    public function getRemedialCourseList(
        Exercise $objExercise,
        int $userId = 0,
        int $sessionId = 0,
        bool $review = false,
        int $lpId = 0,
        int $lpItemId = 0
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
            $objExercise->iid,
            self::EXTRAFIELD_REMEDIAL_VARIABLE
        );
        $remedialCourseIds = isset($remedialExcerciseField['value'])
            ? explode(';', $remedialExcerciseField['value'])
            : [];

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
            $objExercise->iid,
            $objExercise->course_id,
            $sessionId,
            $lpId,
            $lpItemId
        );
        $bestAttempt = Event::get_best_attempt_exercise_results_per_user(
            $userId,
            $objExercise->iid,
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
                $comments = Event::get_comments($objExercise->iid, $questionId);

                if (empty($comments) || $score == 0) {
                    return null;
                }
            }
        }

        if (empty($bestAttempt)) {
            return null;
        }

        $bestAttempt['exe_result'] = (int) $bestAttempt['exe_result'];

        $isPassedPercentage = ExerciseLib::isPassPercentageAttemptPassed(
            $objExercise,
            $bestAttempt['exe_result'],
            $bestAttempt['exe_weighting']
        );

        $hasAttempts = count($exerciseStatInfo) < $objExercise->selectAttempts();
        $isBlockedByPercentage = $objExercise->isBlockedByPercentage($bestAttempt);

        $doSubscriptionToRemedial = $isBlockedByPercentage || (!$isPassedPercentage && !$hasAttempts);

        if (!$doSubscriptionToRemedial) {
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
                    $courses[] = Display::url(
                        $courseData['title'],
                        api_get_course_url($courseData['code'], $sessionId)
                    );
                }
            } else {
                $isSubscribed = CourseManager::is_user_subscribed_in_course($userId, $courseData['code']);

                if (!$isSubscribed) {
                    CourseManager::subscribeUser($userId, $courseData['code']);
                    $courses[] = Display::url(
                        $courseData['title'],
                        api_get_course_url($courseData['code'])
                    );
                }
            }
        }

        if (empty($courses)) {
            return null;
        }

        return sprintf($this->get_lang('SubscriptionToXRemedialCourses'), implode(' - ', $courses));
    }

    /**
     * When a student takes an exam, and he gets an acceptable grade, he is enrolled in a series of courses that
     * represent the next level BT#18165.
     */
    public function getAdvancedCourseList(
        Exercise $objExercise,
        int $userId = 0,
        int $sessionId = 0,
        int $lpId = 0,
        int $lpItemId = 0
    ): ?string {
        if ('true' !== $this->get(self::SETTING_ENABLED)) {
            return null;
        }

        $field = new ExtraField('exercise');
        $advancedCourseField = $field->get_handler_field_info_by_field_variable(self::EXTRAFIELD_ADVACED_VARIABLE);

        if (false === $advancedCourseField) {
            return null;
        }

        $userId = empty($userId) ? api_get_user_id() : $userId;
        $bestAttempt = Event::get_best_attempt_exercise_results_per_user(
            $userId,
            $objExercise->iid,
            $objExercise->course_id,
            $sessionId
        );

        if (!isset($bestAttempt['exe_result'])) {
            // In the case that the result is 0, get_best_attempt_exercise_results_per_user does not return data,
            // for that this block is used
            $exerciseStatInfo = Event::getExerciseResultsByUser(
                $userId,
                $objExercise->iid,
                $objExercise->course_id,
                $sessionId,
                $lpId,
                $lpItemId
            );
            $bestAttempt['exe_result'] = 0;

            foreach ($exerciseStatInfo as $attempt) {
                if ($attempt['exe_result'] >= $bestAttempt['exe_result']) {
                    $bestAttempt = $attempt;
                }
            }
        }

        if (
            !isset($bestAttempt['exe_result'])
            || !isset($bestAttempt['exe_id'])
            || !isset($bestAttempt['exe_weighting'])
        ) {
            // No try, No exercise id, no defined total
            return null;
        }

        $percentSuccess = $objExercise->selectPassPercentage();
        $pass = ExerciseLib::isPassPercentageAttemptPassed(
            $objExercise,
            $bestAttempt['exe_result'],
            $bestAttempt['exe_weighting']
        );

        if (0 == $percentSuccess && false == $pass) {
            return null;
        }

        $canRemedial = false === $pass;
        // Advance Course
        $extraFieldValue = new ExtraFieldValue('exercise');
        $advanceCourseExcerciseField = $extraFieldValue->get_values_by_handler_and_field_variable(
            $objExercise->iid,
            self::EXTRAFIELD_ADVACED_VARIABLE
        );

        if ($canRemedial || !isset($advanceCourseExcerciseField['value'])) {
            return null;
        }

        $coursesIds = explode(';', $advanceCourseExcerciseField['value']);

        if (empty($advanceCourseExcerciseField['value']) || count($coursesIds) == 0) {
            return null;
        }

        $isInASession = !empty($sessionId);
        $courses = [];

        foreach ($coursesIds as $course) {
            $courseData = api_get_course_info_by_id($course);

            if (empty($courseData) || !isset($courseData['real_id'])) {
                continue;
            }

            // if session is 0, always will be true
            $courseExistsInSession = true;

            if ($isInASession) {
                $courseExistsInSession = SessionManager::sessionHasCourse($sessionId, $courseData['code']);
            }

            if (!$courseExistsInSession) {
                continue;
            }

            $isSubscribed = CourseManager::is_user_subscribed_in_course(
                $userId,
                $courseData['code'],
                $isInASession,
                $sessionId
            );

            if (!$isSubscribed) {
                CourseManager::subscribeUser(
                    $userId,
                    $courseData['code'],
                    STUDENT,
                    $sessionId
                );
            }

            $courses[] = Display::url(
                $courseData['title'],
                api_get_course_url($courseData['code'], $sessionId)
            );
        }

        if (empty($courses)) {
            return null;
        }

        return sprintf(
            $this->get_lang('SubscriptionToXAdvancedCourses'),
            implode(' - ', $courses)
        );
    }
}
