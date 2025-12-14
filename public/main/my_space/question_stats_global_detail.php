<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuiz;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_TRACKING;

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Reporting')];

$courseIdList = isset($_REQUEST['courses']) ? $_REQUEST['courses'] : [];
$exercises = isset($_REQUEST['exercises']) ? $_REQUEST['exercises'] : [];

$courseOptions = [];
$exerciseList = [];
$selectedExercises = [];

if (!empty($courseIdList)) {
    foreach ($courseIdList as $courseId) {
        $course = api_get_course_entity($courseId);

        $qb = Container::getQuizRepository()->findAllByCourse($course, null, null, 2, false);
        /** @var CQuiz[] $courseExerciseList */
        $courseExerciseList = $qb->getQuery()->getResult();

        if (!empty($courseExerciseList)) {
            foreach ($courseExerciseList as $exercise) {
                $exerciseId = $exercise->getIid();
                if (in_array($exerciseId, $exercises)) {
                    $selectedExercises[$courseId][] = $exerciseId;
                }
            }

            $exerciseList += array_column($courseExerciseList, 'title', 'iid');
        }

        $courseOptions[$courseId] = $course->getTitle();
    }
}

$exerciseList = array_unique($exerciseList);
if (!empty($exerciseList)) {
    array_walk($exerciseList, function (&$title) {
        $title = Exercise::get_formated_title_variable($title);
    });
}

// -----------------------------------------------------------------------------
// Search form
// -----------------------------------------------------------------------------
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
        get_lang('Test'),
        $exerciseList,
        [
            'multiple' => true,
        ]
    );
}

$form->setDefaults(['course_id_list' => array_keys($courseOptions)]);
$form->addButtonSearch(get_lang('Search'));

$tableContent = '';

/**
 * Build a row with question statistics for a given course, exercise and session.
 */
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
        get_lang('Correct attempts'),
        get_lang('Incorrect attempts'),
        get_lang('Students with correct answers'),
        get_lang('Students with incorrect answers'),
    ];

    $scoreDisplay = new ScoreDisplay();
    $exercises = $form->getSubmitValue('exercises');

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
                $correctCount = 0;
                $wrongCount = 0;
                $correctCountStudent = 0;
                $wrongCountStudent = 0;

                // Per-session rows
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

                // Base course row (no session)
                $row = getCourseSessionRow($courseId, $exerciseObj, 0, get_lang('Base course'));

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

                // Total row
                $orderedData[] = [
                    get_lang('Total'),
                    $correctCount,
                    $wrongCount,
                    $correctCountStudent,
                    $wrongCountStudent,
                ];

                // Table rendering
                $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
                $table->setHeaders($headers);
                $table->setData($orderedData);
                $tableContent .= $table->toHtml();
            }
        }
    }
}

$nameTools = get_lang('Tests management');

$htmlHeadXtra[] = '<script>
$(function() {
    $("#search_form").submit();
    $("#search_form_courses").on("change", function () {
        $("#search_form_exercises").parent().parent().parent().hide();
        $("#search_form_exercises").each(function() {
            $(this).remove();
        });
    });
});
</script>';

// -----------------------------------------------------------------------------
// Toolbar + navigation (aligned with admin_view / tc_report / ti_report)
// -----------------------------------------------------------------------------
$webCodePath = api_get_path(WEB_CODE_PATH);

// Tabs to switch to other question stats reports.
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isGlobal = 'question_stats_global.php' === $currentScript;
$isDetail = 'question_stats_global_detail.php' === $currentScript;

$questionTabs =
    '<div class="inline-flex items-center ml-4">'.
    '<div class="inline-flex rounded-full bg-gray-10 border border-gray-25 px-1 py-1 text-body-2">'.
    '<a href="'.$webCodePath.'my_space/question_stats_global.php"'
    .' class="px-3 py-1 rounded-full transition '
    .($isGlobal
        ? 'bg-white text-gray-90 shadow-sm'
        : 'text-gray-50 hover:bg-gray-15 hover:text-gray-90').'"'
    .'>'.get_lang('Question stats').'</a>'.

    '<a href="'.$webCodePath.'my_space/question_stats_global_detail.php"'
    .' class="ml-1 px-3 py-1 rounded-full transition '
    .($isDetail
        ? 'bg-white text-gray-90 shadow-sm'
        : 'text-gray-50 hover:bg-gray-15 hover:text-gray-90').'"'
    .'>'.get_lang('Detailed questions stats').'</a>'.
    '</div>'.
    '</div>';

// Left side: shared MySpace reporting menu + question tabs.
$actionsLeft = Display::mySpaceMenu('admin_view');
$actionsLeft .= $questionTabs;

// Right side: print action.
$actionsRight = Display::url(
    Display::getMdiIcon(
        ActionIcon::PRINT,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Print')
    ),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print();']
);

$toolbar = Display::toolbarAction('toolbar-question-stats', [$actionsLeft, $actionsRight]);

// -----------------------------------------------------------------------------
// Layout & rendering
// -----------------------------------------------------------------------------
Display::display_header($nameTools, get_lang('Test'));

// Shared styles for reporting cards and active admin cards.
echo '<style>
    .admin-report-card-active {
        border-color: #0284c7 !important;
        background-color: #e0f2fe !important;
    }

    .reporting-question-card {
        border-color: #e5e7eb !important;
        border-width: 1px !important;
    }

    .reporting-question-card .panel,
    .reporting-question-card fieldset {
        border-color: #e5e7eb !important;
    }
</style>';

// Main container (full width with padding)
echo '<div class="w-full px-4 md:px-8 pb-8 space-y-6">';

// Header row with title + toolbar
// Toolbar row (icons aligned to the left, same as other reporting pages)
echo '  <div class="flex flex-wrap gap-2">';
echo        $toolbar;
echo '  </div>';

// Page header
echo '  <div class="space-y-1">';
echo        Display::page_subheader($nameTools);
echo '  </div>';

// Admin reports cards (Available reports) with active state based on this script.
$currentScriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
echo MySpace::renderAdminReportCardsSection(null, $currentScriptName, true);

// Search form card
echo '  <section class="reporting-question-card bg-white rounded-xl shadow-sm w-full">';
echo '      <div class="p-4 md:p-5">';
$form->display();
echo '      </div>';
echo '  </section>';

// Tables card (only if we have results)
if (!empty($tableContent)) {
    echo '  <section class="reporting-question-card bg-white rounded-xl shadow-sm w-full">';
    echo '      <div class="p-4 md:p-5 overflow-x-auto">';
    echo            $tableContent;
    echo '      </div>';
    echo '  </section>';
}

echo '</div>';

Display::display_footer();
