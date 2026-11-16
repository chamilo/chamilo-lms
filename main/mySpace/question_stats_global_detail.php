<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_TRACKING;

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$interbreadcrumb[] = ["url" => "index.php", "name" => get_lang('MySpace')];

$courseIdList = isset($_REQUEST['courses']) ? $_REQUEST['courses'] : [];
$exercises = isset($_REQUEST['exercises']) ? $_REQUEST['exercises'] : [];

$courseOptions = [];
$exerciseList = [];
$selectedExercises = [];

/**
 * Whether the current user teaches, tutors or coaches (in any session) the given course.
 *
 * @param int $courseId
 *
 * @return bool
 */
function isUserLinkedToCourse($courseId)
{
    $userId = api_get_user_id();
    $courseId = (int) $courseId;

    if (1 == CourseManager::get_tutor_in_course_status($userId, $courseId)) {
        return true;
    }

    $courseInfo = api_get_course_info_by_id($courseId);
    if (!empty($courseInfo) && CourseManager::is_course_teacher($userId, $courseInfo['code'])) {
        return true;
    }

    $result = Database::query(
        "SELECT 1
        FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
        WHERE user_id = $userId AND c_id = $courseId AND status = 2
        UNION
        SELECT 1
        FROM ".Database::get_main_table(TABLE_MAIN_SESSION)." s
        INNER JOIN ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE)." sc ON sc.session_id = s.id
        WHERE s.id_coach = $userId AND sc.c_id = $courseId"
    );

    return Database::num_rows($result) > 0;
}

if (!empty($courseIdList)) {
    $isUnrestricted = api_is_platform_admin(true, true);
    foreach ($courseIdList as $courseId) {
        // Only the courses the user teaches, tutors or coaches may be reported on.
        if (!$isUnrestricted && !isUserLinkedToCourse($courseId)) {
            continue;
        }

        $courseInfo = api_get_course_info_by_id($courseId);
        $courseExerciseList = ExerciseLib::get_all_exercises(
            $courseInfo,
            0,
            false,
            null,
            false,
            3
        );

        if (!empty($courseExerciseList)) {
            foreach ($courseExerciseList as $exercise) {
                $exerciseId = $exercise['iid'];
                if (in_array($exerciseId, $exercises)) {
                    $selectedExercises[$courseId][] = $exerciseId;
                }
            }
            $exerciseList += array_column($courseExerciseList, 'title', 'iid');
        }
        $courseOptions[$courseId] = $courseInfo['name'];
    }
}

$exerciseList = array_unique($exerciseList);
if (!empty($exerciseList)) {
    array_walk($exerciseList, function (&$title) {
        $title = Exercise::get_formated_title_variable($title);
    });
}

$form = new FormValidator('search_form', 'GET', api_get_self());
$form->addSelectAjax(
    'courses',
    get_lang('Course'),
    $courseOptions,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
        'multiple' => true,
    ]
);

if (!empty($courseIdList)) {
    $form->addSelect(
        'exercises',
        get_lang('Exercise'),
        $exerciseList,
        [
            'multiple' => true,
        ]
    );
}

$form->setDefaults(['course_id_list' => array_keys($courseOptions)]);
$form->addButtonSearch(get_lang('Search'));

$tableContent = '';

function getCourseSessionRow($courseId, Exercise $exercise, $sessionId, $title)
{
    $correctCount = ExerciseLib::getExerciseResultsCount('correct', $courseId, $exercise, $sessionId);
    $wrongCount = ExerciseLib::getExerciseResultsCount('wrong', $courseId, $exercise, $sessionId);
    $correctCountStudent = ExerciseLib::getExerciseResultsCount(
        'correct_student',
        $courseId,
        $exercise,
        $sessionId
    );
    $wrongCountStudent = ExerciseLib::getExerciseResultsCount(
        'wrong_student',
        $courseId,
        $exercise,
        $sessionId
    );

    //$questions = ExerciseLib::getWrongQuestionResults($courseId, $exerciseId, $sessionId);
    return [
        'title' => $title,
        'correct_count' => $correctCount,
        'wrong_count' => $wrongCount,
        'correct_count_student' => $correctCountStudent,
        'wrong_count_student' => $wrongCountStudent,
    ];
}

if ($form->validate()) {
    $headers = [
        get_lang('Session'),
        get_lang('SuccessfulAttempt'),
        get_lang('FailedAttempt'),
        get_lang('StudentWithSuccessfulAttempt'),
        get_lang('StudentWithFailedAttempt'),
    ];
    $scoreDisplay = new ScoreDisplay();
    $exercises = $form->getSubmitValue('exercises');
    $exerciseTable = Database::get_course_table(TABLE_QUIZ_TEST);
    if ($exercises) {
        foreach ($selectedExercises as $courseId => $courseExerciseList) {
            $sessions = SessionManager::get_session_by_course($courseId);
            $courseTitle = $courseOptions[$courseId];

            foreach ($courseExerciseList as $exerciseId) {
                $exerciseObj = new Exercise($courseId);
                $result = $exerciseObj->read($exerciseId, false);
                if (false === $result) {
                    continue;
                }

                $exerciseTitle = $exerciseList[$exerciseId];
                $tableContent .= Display::page_subheader2($courseTitle.' - '.$exerciseTitle);
                $orderedData = [];
                $total = [];
                $correctCount = 0;
                $wrongCount = 0;
                $correctCountStudent = 0;
                $wrongCountStudent = 0;

                foreach ($sessions as $session) {
                    $sessionId = $session['id'];
                    $row = getCourseSessionRow($courseId, $exerciseObj, $sessionId, $session['name']);
                    $correctCount += $row['correct_count'];
                    $wrongCount += $row['wrong_count'];
                    $correctCountStudent += $row['correct_count_student'];
                    $wrongCountStudent += $row['wrong_count_student'];

                    $orderedData[] = [
                        $row['title'],
                        $row['correct_count'],
                        $row['wrong_count'],
                        $row['correct_count_student'],
                        $row['wrong_count_student'],
                    ];
                }

                // Course base
                $row = getCourseSessionRow($courseId, $exerciseObj, 0, get_lang('BaseCourse'));
                $orderedData[] = [
                    $row['title'],
                    $row['correct_count'],
                    $row['wrong_count'],
                    $row['correct_count_student'],
                    $row['wrong_count_student'],
                ];

                $correctCount += $row['correct_count'];
                $wrongCount += $row['wrong_count'];
                $correctCountStudent += $row['correct_count_student'];
                $wrongCountStudent += $row['wrong_count_student'];
                $orderedData[] = [
                    get_lang('Total'),
                    $correctCount,
                    $wrongCount,
                    $correctCountStudent,
                    $wrongCountStudent,
                ];

                /*$table = new SortableTableFromArray(
                    $orderedData,
                    10,
                    1000,
                    uniqid('question_tracking_')
                );*/
                $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
                $table->setHeaders($headers);
                $table->setData($orderedData);
                $tableContent .= $table->toHtml();
            }
        }
    }
}

$nameTools = get_lang('ExerciseManagement');
$htmlHeadXtra[] = '<script>
$(function() {
 $("#search_form").submit();
    $("#search_form_courses").on("change", function (e) {
       $("#search_form_exercises").parent().parent().parent().hide();
       $("#search_form_exercises").each(function() {
            $(this).remove();
        });
    });
});
</script>';

Display::display_header($nameTools, get_lang('Exercise'));
$form->display();
echo $tableContent;

Display::display_footer();
