<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = 'session_my_space';

$allow = Tracking::isAllowToTrack(api_get_session_id());

if (!$allow) {
    api_not_allowed(true);
}

$export_to_xls = false;
if (isset($_GET['export'])) {
    $export_to_xls = true;
}

if (api_is_platform_admin()) {
    $global = true;
} else {
    $global = false;
}
$global = true;

// View flag: admin layout is only used when view=admin
$view = $_GET['view'] ?? null;
$viewIsAdmin = 'admin' === $view;

$course_list = $course_select_list = [];
$html_result = '';
$course_select_list[0] = get_lang('none');

$htmlHeadXtra[] = '
<script type="text/javascript">
function load_courses() {
    document.search_simple.submit();
}
</script>';

$session_id = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : null;

if (empty($session_id)) {
    $temp_course_list = CourseManager::get_courses_list();
} else {
    $temp_course_list = SessionManager::get_course_list_by_session_id($session_id);
}

foreach ($temp_course_list as $temp_course_item) {
    $course_item = api_get_course_info($temp_course_item['code']);
    $course_select_list[$temp_course_item['code']] = $course_item['title'];
}

// Get session list
$session_list = SessionManager::get_sessions_list([], ['title']);

$my_session_list = [];
$my_session_list[0] = get_lang('none');
foreach ($session_list as $sesion_item) {
    $my_session_list[$sesion_item['id']] = $sesion_item['title'];
}

// Preserve view parameter in form action so admin layout stays active.
$formAction = api_get_self();
if (!empty($view)) {
    $formAction .= '?view='.urlencode((string) $view);
}

$form = new FormValidator('search_simple', 'POST', $formAction, '', null, false);

if ($viewIsAdmin) {
    // Keep admin view flag across form submissions
    $form->addHidden('view', 'admin');
}
$form->addSelect(
    'session_id',
    get_lang('Course sessions'),
    $my_session_list,
    ['id' => 'session_id', 'onchange' => 'load_courses();']
);
$form->addSelect(
    'course_code',
    get_lang('Courses'),
    $course_select_list
);
$form->addButtonFilter(get_lang('Filter'), 'submit_form');

if (!empty($_REQUEST['course_code'])) {
    $course_code = $_REQUEST['course_code'];
} else {
    $course_code = '';
}

if (empty($course_code)) {
    $course_code = 0;
}

$form->setDefaults(['course_code' => (string) $course_code]);

$course_info = api_get_course_info($course_code);
$main_question_list = [];

if (!empty($course_info)) {
    $list = new LearnpathList('', $course_info);
    $lp_list = $list->get_flat_list();

    foreach ($lp_list as $lp_id => $lp) {
        $exercise_list = Event::get_all_exercises_from_lp($lp_id);

        foreach ($exercise_list as $exercise) {
            $my_exercise = new Exercise($course_info['real_id']);
            $my_exercise->read($exercise['path']);
            $question_list = $my_exercise->selectQuestionList();

            $exercise_stats = Event::get_all_exercise_event_from_lp(
                $exercise['path'],
                $course_info['real_id'],
                $session_id
            );

            foreach ($question_list as $question_id) {
                $question_data = Question::read($question_id);
                $main_question_list[$question_id] = $question_data;
                $quantity_exercises = 0;
                $question_result = 0;

                foreach ($exercise_stats as $stats) {
                    if (!empty($stats['question_list'])) {
                        foreach ($stats['question_list'] as $my_question_stat) {
                            if ($question_id == $my_question_stat['question_id']) {
                                $question_result += $my_question_stat['marks'];
                                $quantity_exercises++;
                            }
                        }
                    }
                }

                if (!empty($quantity_exercises)) {
                    // Score % average
                    $main_question_list[$question_id]->results = ($question_result / ($quantity_exercises));
                } else {
                    $main_question_list[$question_id]->results = 0;
                }

                $main_question_list[$question_id]->quantity = $quantity_exercises;
            }
        }
    }
}

$course_average = [];
$counter = 0;
$noResultsMessage = '';

if (!empty($main_question_list) && is_array($main_question_list)) {
    $html_result .= '<table class="table table-hover table-striped data_table">';
    $html_result .= '<tr><th>'.get_lang('Question').
        Display::getMdiIcon(
            ActionIcon::INFORMATION,
            'ch-tool-icon',
            null,
            ICON_SIZE_SMALL,
            get_lang('These questions have been taken from the learning paths')
        ).'</th>';
    $html_result .= '<th>'.$course_info['visual_code'].' '.get_lang('Average score').
        Display::getMdiIcon(
            ActionIcon::INFORMATION,
            'ch-tool-icon',
            null,
            ICON_SIZE_SMALL,
            get_lang('All learners attempts are considered')
        ).'</th>';
    $html_result .= '<th>'.get_lang('Quantity').'</th>';

    foreach ($main_question_list as $question) {
        $counter++;
        $rowClass = 0 === $counter % 2 ? 'row_odd' : 'row_even';

        $html_result .= "<tr class=\"{$rowClass}\">";
        $html_result .= '<td>';

        $question_title = trim($question->question);
        if (empty($question_title)) {
            $html_result .= get_lang('Untitled').' '.get_lang('Question').' #'.$question->id;
        } else {
            $html_result .= $question->question;
        }

        $html_result .= '</td>';
        $html_result .= '<td>';
        $html_result .= round($question->results, 2).' / '.$question->weighting;
        $html_result .= '</td>';

        $html_result .= '<td>';
        $html_result .= $question->quantity;
        $html_result .= '</td>';
        $html_result .= '</tr>';
    }

    $html_result .= '</table>';
} else {
    if (!empty($course_code)) {
        $noResultsMessage = Display::return_message(get_lang('No results found'), 'warning');
    }
}

/**
 * Rendering section
 */
if (!$export_to_xls) {
    $pageTitle = get_lang('Learning paths exercises results list');

    // ---------------------------------------------------------------------
    // Modern admin layout (only when view=admin and user is platform admin)
    // ---------------------------------------------------------------------
    if ($viewIsAdmin && api_is_platform_admin(true, true)) {
        Display::display_header($pageTitle);

        // Local styles used by admin reporting cards.
        echo '<style>
            .admin-report-card-active {
                border-color: #0284c7 !important;
                background-color: #e0f2fe !important;
            }
            .reporting-admin-card {
                border-color: #e5e7eb !important;
                border-width: 1px !important;
            }
            .reporting-admin-card .panel,
            .reporting-admin-card fieldset {
                border-color: #e5e7eb !important;
            }
        </style>';

        // Toolbar (my space menu + print button)
        $actionsLeft = Display::mySpaceMenu('admin_view');

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

        $toolbar = Display::toolbarAction('toolbar-admin', [$actionsLeft, $actionsRight]);

        echo '<div class="w-full px-4 md:px-8 pb-8 space-y-6">';

        // Toolbar row (icons aligned to the left)
        echo '  <div class="flex flex-wrap gap-2">';
        echo        $toolbar;
        echo '  </div>';

        // Page header
        echo '  <div class="space-y-1">';
        echo        Display::page_subheader($pageTitle);
        echo '  </div>';

        // Admin report cards (shared helper)
        $currentScriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
        echo MySpace::renderAdminReportCardsSection(null, $currentScriptName, true);

        // Filter form card
        echo '  <section class="reporting-admin-card bg-white rounded-xl shadow-sm border border-gray-200 w-full">';
        echo '      <div class="p-4 md:p-5">';
        $form->display();
        echo '      </div>';
        echo '  </section>';

        // Empty course warning
        if (empty($course_code)) {
            echo '  <section class="mt-2">';
            echo        Display::return_message(get_lang('Please select a course'), 'warning');
            echo '  </section>';
        }

        // Results table or "no results" message
        if (!empty($html_result)) {
            echo '  <section class="bg-white rounded-xl shadow-sm border border-gray-200 w-full">';
            echo '      <div class="p-4 md:p-5 overflow-x-auto">';
            echo            $html_result;
            echo '      </div>';
            echo '  </section>';
        } elseif (!empty($noResultsMessage) && !empty($course_code)) {
            echo '  <section class="mt-2">';
            echo        $noResultsMessage;
            echo '  </section>';
        }

        echo '</div>';

        Display::display_footer();

        // Stop here, do not fall back to legacy layout.
        return;
    }

    // ---------------------------------------------------------------------
    // Legacy layout (all other views – unchanged behaviour)
    // ---------------------------------------------------------------------
    Display::display_header(get_lang('Reporting'));

    echo '<div class="actions">';
    if ($global) {
        echo MySpace::getTopMenu();
    } else {
        echo '<div style="float:left; clear:left">
                <a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.
            get_lang('Report on learners').'</a>&nbsp;|
                <a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.
            get_lang('Course report').'</a>&nbsp;';
        echo '</div>';
    }
    echo '</div>';

    if (api_is_platform_admin()) {
        $actions = MySpace::generateAdminActionLinks();

        echo '<ul class="list-disc m-y-2">';
        foreach ($actions as $action) {
            echo '<li><a href="'.$action['url'].'">'.$action['content'].'</a></li>'.PHP_EOL;
        }
        echo '</ul>';
    }

    echo '<br />';
    echo '<h2>'.get_lang('Learning paths exercises results list').'</h2>';

    $form->display();

    if (empty($course_code)) {
        echo Display::return_message(get_lang('Please select a course'), 'warning');
    }

    if (!empty($html_result)) {
        echo $html_result;
    } elseif (!empty($noResultsMessage)) {
        echo $noResultsMessage;
    }

    Display::display_footer();
}
