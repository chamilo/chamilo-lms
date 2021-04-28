<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\UserBundle\Entity\User;

require_once __DIR__.'/../inc/global.inc.php';
require_once '../work/work.lib.php';

api_block_anonymous_users(false);

$allowedToTrackUser = api_is_platform_admin(true, true) ||
    api_is_allowed_to_edit(null, true) ||
    api_is_session_admin() ||
    api_is_drh() ||
    api_is_student_boss() ||
    api_is_course_admin() ||
    api_is_teacher();

if (!$allowedToTrackUser) {
    api_not_allowed();
}

$studentId = isset($_GET['student']) ? (int) $_GET['student'] : 0;

$student = api_get_user_entity($studentId);

if (empty($student)) {
    api_not_allowed(true);
}

function getCoursesInSession(int $studentId): array
{
    $coursesInSessions = [];

    $courseRelUser = Database::select(
        'c_id',
        Database::get_main_table(TABLE_MAIN_COURSE_USER),
        [
            'where' => [
                'relation_type <> ? AND user_id = ?' => [COURSE_RELATION_TYPE_RRHH, $studentId],
            ],
        ]
    );
    $sessionRelCourseRelUser = Database::select(
        ['session_id', 'c_id'],
        Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER),
        [
            'where' => [
                'user_id = ?' => $studentId,
            ],
        ]
    );

    foreach ($courseRelUser as $row) {
        $coursesInSessions[0][] = $row['c_id'];
    }

    foreach ($sessionRelCourseRelUser as $row) {
        $coursesInSessions[$row['session_id']][] = $row['c_id'];
    }

    return $coursesInSessions;
}

function generateForm(int $studentId, array $coursesInSessions): FormValidator
{
    $form = new FormValidator(
        'frm_export',
        'post',
        api_get_self()."?student=$studentId",
        '',
        [],
        FormValidator::LAYOUT_BOX
    );

    foreach ($coursesInSessions as $sId => $courses) {
        if (empty($courses)) {
            continue;
        }

        if ($sId) {
            $sessionInfo = api_get_session_info($sId);

            $dateSession = empty($sessionInfo['duration'])
                ? '('.SessionManager::parseSessionDates($sessionInfo, true)['display'].')'
                : '';

            $fieldTitle = $sessionInfo['name'].PHP_EOL.Display::tag('small', $dateSession);
        } else {
            $fieldTitle = get_lang('Courses');
        }

        $options = [];

        foreach ($courses as $courseId) {
            $courseInfoItem = api_get_course_info_by_id($courseId);

            if (empty($sessionInfo)) {
                $isSubscribed = CourseManager::is_user_subscribed_in_course(
                    $studentId,
                    $courseInfoItem['code']
                );
            } else {
                $isSubscribed = CourseManager::is_user_subscribed_in_course(
                    $studentId,
                    $courseInfoItem['code'],
                    true,
                    $sId
                );
            }

            if (!$isSubscribed) {
                continue;
            }

            $options[$sId.'_'.$courseId] = $courseInfoItem['title'];
        }

        $form->addCheckBoxGroup("sc", $fieldTitle, $options, ['checked' => 'checked']);
    }

    $form->addButtonExport(get_lang('ExportToPDF'));

    return $form;
}

function generateHtmlForLearningPaths(User $student, array $courseInfo, int $sessionId): string
{
    $html = Display::page_subheader2(get_lang('ToolLearnpath'));

    $columnHeaders = [];
    $columnHeaders['lp'] = get_lang('LearningPath');
    $columnHeaders['time'] = get_lang('Time');
    $columnHeaders['best_score'] = get_lang('BestScore');
    $columnHeaders['latest_attempt_avg_score'] = get_lang('LatestAttemptAverageScore');
    $columnHeaders['progress'] = get_lang('Progress');
    $columnHeaders['last_connection'] = get_lang('LastConnexion');

    $trackingColumns = api_get_configuration_value('tracking_columns');

    if (isset($trackingColumns['my_students_lp'])) {
        foreach ($columnHeaders as $key => $value) {
            if (!isset($trackingColumns['my_progress_lp'][$key]) || $trackingColumns['my_students_lp'][$key] == false) {
                unset($columnHeaders[$key]);
            }
        }
    }

    if (true === api_get_configuration_value('student_follow_page_add_LP_subscription_info')) {
        $columnHeaders['student_follow_page_add_LP_subscription_info'] = get_lang('Unlock');
    }

    if (true === api_get_configuration_value('student_follow_page_add_LP_acquisition_info')) {
        $columnHeaders['student_follow_page_add_LP_acquisition_info'] = get_lang('Acquisition');
    }

    $columnHeadersKeys = array_keys($columnHeaders);

    $hideInvisibleViews = api_get_configuration_value('student_follow_page_add_LP_invisible_checkbox');

    $timeCourse = Tracking::minimumTimeAvailable($sessionId, $courseInfo['real_id'])
        ? Tracking::getCalculateTime($student->getId(), $courseInfo['real_id'], $sessionId)
        : null;

    $lpCategories = learnpath::getCategories($courseInfo['real_id'], true);

    /** @var CLpCategory $item */
    foreach ($lpCategories as $item) {
        $categoryId = $item->getId();

        if (!learnpath::categoryIsVisibleForStudent($item, $student, $courseInfo['real_id'], $sessionId)) {
            continue;
        }

        $lpList = new LearnpathList(
            $student->getUserId(),
            $courseInfo,
            $sessionId,
            null,
            false,
            $categoryId,
            false,
            false,
            false
        );

        $flatList = $lpList->get_flat_list();

        if (count($lpCategories) > 1) {
            $html .= Display::page_subheader3($item->getName());
        }

        $lpTable = [$columnHeaders];

        foreach ($flatList as $learnpath) {
            $lpId = $learnpath['lp_old_id'];

            if ($hideInvisibleViews
                && !StudentFollowPage::isViewVisible($lpId, $student->getId(), $courseInfo['real_id'], $sessionId)
            ) {
                continue;
            }

            $contentToExport = [];

            if (in_array('lp', $columnHeadersKeys)) {
                $contentToExport[] = api_html_entity_decode(stripslashes($learnpath['lp_name']), ENT_QUOTES);
            }

            if (in_array('time', $columnHeadersKeys)) {
                // Get time in lp
                if (!empty($timeCourse)) {
                    $lpTime = $timeCourse[TOOL_LEARNPATH] ?? 0;
                    $totalTime = isset($lpTime[$lpId]) ? (int) $lpTime[$lpId] : 0;
                } else {
                    $totalTime = Tracking::get_time_spent_in_lp(
                        $student->getId(),
                        $courseInfo['code'],
                        [$lpId],
                        $sessionId
                    );
                }

                $contentToExport[] = api_time_to_hms($totalTime);
            }

            if (in_array('best_score', $columnHeadersKeys)) {
                $bestScore = Tracking::get_avg_student_score(
                    $student->getId(),
                    $courseInfo['code'],
                    [$lpId],
                    $sessionId,
                    false,
                    false,
                    true
                );

                $contentToExport[] = empty($bestScore) ? '' : "$bestScore %";
            }

            if (in_array('latest_attempt_avg_score', $columnHeadersKeys)) {
                $scoreLatest = Tracking::get_avg_student_score(
                    $student->getId(),
                    $courseInfo['code'],
                    [$lpId],
                    $sessionId,
                    false,
                    true
                );

                if (isset($scoreLatest) && is_numeric($scoreLatest)) {
                    $scoreLatest = "$scoreLatest %";
                }

                $contentToExport[] = $scoreLatest;
            }

            if (in_array('progress', $columnHeadersKeys)) {
                $progress = Tracking::get_avg_student_progress(
                    $student->getId(),
                    $courseInfo['code'],
                    [$lpId],
                    $sessionId
                );

                $contentToExport[] = is_numeric($progress) ? "$progress %" : '0 %';
            }

            if (in_array('last_connection', $columnHeadersKeys)) {
                // Get last connection time in lp
                $startTime = Tracking::get_last_connection_time_in_lp(
                    $student->getId(),
                    $courseInfo['code'],
                    $lpId,
                    $sessionId
                );

                $contentToExport[] = empty($startTime)
                    ? '-'
                    : api_convert_and_format_date($startTime, DATE_TIME_FORMAT_LONG);
            }

            if (in_array('student_follow_page_add_LP_subscription_info', $columnHeadersKeys)) {
                $lpSubscription = StudentFollowPage::getLpSubscription(
                    $learnpath,
                    $student->getId(),
                    $courseInfo['real_id'],
                    $sessionId
                );
                $contentToExport[] = strip_tags(str_replace('<br>', "\n", $lpSubscription));
            }

            if (in_array('student_follow_page_add_LP_acquisition_info', $columnHeadersKeys)) {
                $lpAcquisition = StudentFollowPage::getLpAcquisition(
                    $learnpath,
                    $student->getId(),
                    $courseInfo['real_id'],
                    $sessionId
                );
                $contentToExport[] = strip_tags(str_replace('<br>', "\n", $lpAcquisition));
            }

            $lpTable[] = $contentToExport;
        }

        $html .= Export::convert_array_to_html($lpTable);
    }

    return $html;
}

function generateHtmlForQuizzes(int $studentId, array $courseInfo, int $sessionId): string
{
    $html = Display::page_subheader2(get_lang('ToolQuiz'));

    $columnHeaders = [];
    $columnHeaders[] = get_lang('Exercises');
    $columnHeaders[] = get_lang('LearningPath');
    $columnHeaders[] = get_lang('AvgCourseScore');
    $columnHeaders[] = get_lang('Attempts');

    $taskTable = [$columnHeaders];

    $tblQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $sessionCondition = api_get_session_condition($sessionId, true, true, 'quiz.session_id');

    $sql = "SELECT quiz.title, id
        FROM $tblQuiz AS quiz
        WHERE
            quiz.c_id = ".$courseInfo['real_id']."
            AND active IN (0, 1)
            $sessionCondition
        ORDER BY quiz.title ASC";
    $resultExercices = Database::query($sql);

    if (Database::num_rows($resultExercices) > 0) {
        while ($exercices = Database::fetch_array($resultExercices)) {
            $exerciseId = (int) $exercices['id'];
            $countAttempts = Tracking::count_student_exercise_attempts(
                $studentId,
                $courseInfo['real_id'],
                $exerciseId,
                0,
                0,
                $sessionId,
                2
            );
            $scorePercentage = Tracking::get_avg_student_exercise_score(
                $studentId,
                $courseInfo['code'],
                $exerciseId,
                $sessionId,
                1,
                0
            );

            $lpName = '-';

            if (!isset($scorePercentage) && $countAttempts > 0) {
                $lpScores = Tracking::get_avg_student_exercise_score(
                    $studentId,
                    $courseInfo['code'],
                    $exerciseId,
                    $sessionId,
                    2,
                    1
                );
                $scorePercentage = $lpScores[0];
                $lpName = $lpScores[1];
            }
            $lpName = !empty($lpName) ? $lpName : get_lang('NoLearnpath');

            $contentToExport = [];
            $contentToExport[] = Exercise::get_formated_title_variable($exercices['title']);
            $contentToExport[] = empty($lpName) ? '-' : $lpName;
            $contentToExport[] = $countAttempts > 0 ? sprintf(get_lang('XPercent'), $scorePercentage) : '-';
            $contentToExport[] = $countAttempts;

            $taskTable[] = $contentToExport;
        }

        $html .= Export::convert_array_to_html($taskTable);
    }

    return $html;
}

function generateHtmlForTasks(int $studentId, array $courseInfo, int $sessionId): string
{
    $columnHeaders = [];
    $columnHeaders[] = get_lang('Tasks');
    $columnHeaders[] = get_lang('DocumentNumber');
    $columnHeaders[] = get_lang('Note');
    $columnHeaders[] = get_lang('HandedOut');
    $columnHeaders[] = get_lang('HandOutDateLimit');
    $columnHeaders[] = get_lang('ConsideredWorkingTime');

    $workingTime = api_get_configuration_value('considered_working_time');

    $userWorks = getWorkPerUser($studentId, $courseInfo['real_id'], $sessionId);

    $taskTable = [$columnHeaders];

    foreach ($userWorks as $work) {
        $work = $work['work'];

        foreach ($work->user_results as $key => $results) {
            $documentNumber = $key + 1;

            $contentToExport = [];

            $contentToExport[] = $work->title;
            $contentToExport[] = $documentNumber;
            $contentToExport[] = !empty($results['qualification']) ? $results['qualification'] : '-';
            $contentToExport[] = api_convert_and_format_date($results['sent_date_from_db']).PHP_EOL
                .$results['expiry_note'];

            $assignment = get_work_assignment_by_id($work->id, $courseInfo['real_id']);

            if (!empty($assignment['expires_on'])) {
                $contentToExport[] = api_convert_and_format_date($assignment['expires_on']);
            }

            $fieldValue = new ExtraFieldValue('work');
            $resultExtra = $fieldValue->getAllValuesForAnItem($work->iid, true);

            foreach ($resultExtra as $field) {
                $field = $field['value'];

                if ($workingTime == $field->getField()->getVariable()) {
                    $time = $field->getValue();

                    $contentToExport[] = $time;
                }
            }

            $taskTable[] = $contentToExport;
        }
    }

    return Display::page_subheader2(get_lang('ToolStudentPublication')).PHP_EOL
        .Export::convert_array_to_html($taskTable);
}

$coursesInSessions = getCoursesInSession($studentId);

$form = generateForm($studentId, $coursesInSessions);

if ($form->validate()) {
    $values = $form->exportValues();

    $pdfHtml = [];

    if (!empty($values['sc'])) {
        foreach ($values['sc'] as $courseKey) {
            [$sessionId, $courseId] = explode('_', $courseKey);

            if (empty($coursesInSessions[$sessionId]) || !in_array($courseId, $coursesInSessions[$sessionId])) {
                continue;
            }

            $courseInfo = api_get_course_info_by_id($courseId);

            $courseHtml = '';

            if ($sessionId) {
                $sessionInfo = api_get_session_info($sessionId);

                $dateSession = empty($sessionInfo['duration'])
                    ? '('.SessionManager::parseSessionDates($sessionInfo, true)['display'].')'
                    : '';

                $courseHtml .= Display::page_header($sessionInfo['name'].PHP_EOL.Display::tag('small', $dateSession))
                    .Display::page_subheader($courseInfo['title']);
            } else {
                $courseHtml .= Display::page_header($courseInfo['title']);
            }

            $courseHtml .= generateHtmlForLearningPaths($student, $courseInfo, $sessionId);
            $courseHtml .= generateHtmlForQuizzes($studentId, $courseInfo, $sessionId);
            $courseHtml .= generateHtmlForTasks($studentId, $courseInfo, $sessionId);

            $pdfHtml[] = $courseHtml;
        }
    }

    $params = [
        'filename' => get_lang('StudentFollow'),
        'pdf_title' => $student->getCompleteNameWithUsername(),
        'format' => 'A4',
        'orientation' => 'P',
    ];

    $pdf = new PDF($params['format'], $params['orientation'], $params);
    $pdf->html_to_pdf_with_template(implode('<pagebreak>', $pdfHtml));

    exit;
}

echo Display::page_subheader($student->getCompleteNameWithUsername());
$form->display();
