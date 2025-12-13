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

$courseIdList = $_REQUEST['courses'] ?? [];
$exercises = $_REQUEST['exercises'] ?? [];
$groups = $_REQUEST['groups'] ?? [];
$users = $_REQUEST['users'] ?? [];

$courseOptions = [];
$exerciseList = [];
$selectedExercises = [];
$groupList = [];
$allGroups = [];
$allUsers = [];

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

if (!empty($allGroups)) {
    $form->addSelect(
        'groups',
        get_lang('Groups'),
        $allGroups,
        [
            'multiple' => true,
        ]
    );
}

if (!empty($allUsers)) {
    $form->addSelect(
        'users',
        get_lang('Users'),
        $allUsers,
        [
            'multiple' => true,
        ]
    );
}

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

// -----------------------------------------------------------------------------
// Table content
// -----------------------------------------------------------------------------
$tableContent = '';
if ($form->validate()) {
    $headers = [
        get_lang('Course'),
        get_lang('Test'),
        get_lang('Question'),
        get_lang('Wrong answer').' / '.get_lang('Total'),
        '%',
    ];

    $scoreDisplay = new ScoreDisplay();
    $exercises = $form->getSubmitValue('exercises');

    if ($exercises) {
        $orderedData = [];

        foreach ($selectedExercises as $courseId => $selectedExerciseList) {
            foreach ($selectedExerciseList as $exerciseId) {
                $questions = ExerciseLib::getWrongQuestionResults(
                    $courseId,
                    $exerciseId,
                    null,
                    $groups,
                    $users
                );

                foreach ($questions as $data) {
                    $questionId = (int) $data['question_id'];
                    $total = ExerciseLib::getTotalQuestionAnswered(
                        $courseId,
                        $exerciseId,
                        $questionId,
                        null,
                        $groups,
                        $users
                    );

                    $orderedData[] = [
                        $courseOptions[$courseId],
                        $exerciseList[$exerciseId],
                        $data['question'],
                        $data['count'].' / '.$total,
                        $scoreDisplay->display_score([$data['count'], $total], SCORE_AVERAGE),
                    ];
                }
            }
        }

        $table = new SortableTableFromArray(
            $orderedData,
            0,
            100,
            'question_tracking'
        );
        $table->column = 4;
        $column = 0;

        foreach ($headers as $header) {
            $table->set_header($column, $header, false);
            $column++;
        }

        $tableContent = $table->return_table();
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
// Toolbar: MySpace main icons + question stats tabs
// -----------------------------------------------------------------------------
$webCodePath = api_get_path(WEB_CODE_PATH);

// Tabs for "Question stats" and "Detailed questions stats"
$currentScript = basename($_SERVER['SCRIPT_NAME']);
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

// Left side: main MySpace icons + tabs
// The parameter is only used to decide which icon is active.
$actionsLeft = Display::mySpaceMenu('admin_view').$questionTabs;

// Right side: print icon
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
// Page rendering
// -----------------------------------------------------------------------------
Display::display_header($nameTools, get_lang('Test'));

// Small scoped styles for card borders (reused in several tracking pages)
echo '<style>
    .reporting-question-card {
        border-color: #e5e7eb !important;
        border-width: 1px !important;
    }
    .reporting-question-card .panel,
    .reporting-question-card fieldset {
        border-color: #e5e7eb !important;
    }
</style>';

// Main layout container
echo '<div class="w-full px-4 md:px-8 pb-8 space-y-6">';

// Toolbar row (icons + tabs + print)
echo '  <div class="flex flex-wrap gap-2">';
echo        $toolbar;
echo '  </div>';

// Page title
echo '  <div class="space-y-1">';
echo        Display::page_subheader($nameTools);
echo '  </div>';

$currentScriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
echo MySpace::renderAdminReportCardsSection(null, $currentScriptName, true);

// Search form card
echo '  <section class="reporting-question-card bg-white rounded-xl shadow-sm w-full">';
echo '      <div class="p-4 md:p-5">';
$form->display();
echo '      </div>';
echo '  </section>';

// Results table card
if (!empty($tableContent)) {
    echo '  <section class="reporting-question-card bg-white rounded-xl shadow-sm w-full">';
    echo '      <div class="overflow-x-auto">';
    echo            $tableContent;
    echo '      </div>';
    echo '  </section>';
}

echo '</div>';

Display::display_footer();
