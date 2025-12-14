<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$session_id = isset($_GET['session_id']) ? (int) $_GET['session_id'] : null;
$this_section = 'session_my_space';
$is_allowedToTrack = Tracking::isAllowToTrack($session_id);

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

$export_to_xls = false;
if (isset($_GET['export'])) {
    $export_to_xls = true;
}

// Keep previous behaviour: platform admin = global view
$global = api_is_platform_admin();

// Admin layout flag: used when coming from MySpace "Admin view" cards
$view = $_GET['view'] ?? null;
$viewIsAdmin = 'admin' === $view;

// Score filter (kept for backward compatibility)
$filter_score = !empty($_REQUEST['score']) ? (int) $_REQUEST['score'] : 70;

// -----------------------------------------------------------------------------
// Form preparation
// -----------------------------------------------------------------------------
$formAction = api_get_self();
if ($viewIsAdmin) {
    $formAction .= '?view=admin';
}

// We keep POST to avoid breaking existing links, but preserve the "view" query.
$form = new FormValidator('search_simple', 'POST', $formAction, '', null, false);

// Get session list
$session_list = SessionManager::get_sessions_list([], ['title']);
$my_session_list = [];
foreach ($session_list as $sesion_item) {
    $my_session_list[$sesion_item['id']] = $sesion_item['title'];
}
if (0 === count($session_list)) {
    $my_session_list[0] = get_lang('none');
}

$form->addSelect('session_id', get_lang('Course sessions'), $my_session_list);
$form->addButtonFilter(get_lang('Filter'));

if (!empty($_REQUEST['session_id'])) {
    $session_id = (int) $_REQUEST['session_id'];
} else {
    $session_id = 0;
}

if (empty($session_id) && !empty($my_session_list)) {
    // Default to the first session if none is selected
    $session_id = (int) key($my_session_list);
}

$form->setDefaults(['session_id' => $session_id]);

$course_list = SessionManager::get_course_list_by_session_id($session_id);
$users = SessionManager::get_users_by_session($session_id);

$course_average = $course_average_counter = [];
$counter = 0;
$main_result = [];

// -----------------------------------------------------------------------------
// Compute per-course / per-user results
// -----------------------------------------------------------------------------
foreach ($course_list as $current_course) {
    $course_info = api_get_course_info($current_course['code']);
    $_course = $course_info;
    $attempt_result = [];

    // Learning paths in the course for this session
    $list = new LearnpathList('', $course_info, $session_id);
    $lp_list = $list->get_flat_list();

    foreach ($lp_list as $lp_id => $lp) {
        $exercise_list = Event::get_all_exercises_from_lp($lp_id);

        // Loop Chamilo exercises inside the learning path
        foreach ($exercise_list as $exercise) {
            $exercise_stats = Event::get_all_exercise_event_from_lp(
                $exercise['path'],
                $course_info['real_id'],
                $session_id
            );

            // Loop exercise attempts
            foreach ($exercise_stats as $stats) {
                // Skip invalid attempts to avoid division by zero
                if (empty($stats['max_score'])) {
                    continue;
                }

                $exeUserId = (int) $stats['exe_user_id'];

                if (!isset($attempt_result[$exeUserId])) {
                    // Initialize accumulator for this user
                    $attempt_result[$exeUserId] = [
                        'result'   => 0.0,
                        'attempts' => 0,
                    ];
                }

                $attempt_result[$exeUserId]['result'] += $stats['score'] / $stats['max_score'];
                $attempt_result[$exeUserId]['attempts']++;
            }
        }
    }

    $main_result[$current_course['code']] = $attempt_result;
}

// -----------------------------------------------------------------------------
// Build HTML table (or "no results" message)
// -----------------------------------------------------------------------------
$total_average_score = 0;
$total_average_score_count = 0;
$html_result = '';
$noResultsMessage = '';

if (!empty($users) && is_array($users)) {
    $html_result .= '<table class="table table-hover table-striped data_table">';
    $html_result .= '<tr><th>'.get_lang('User').'</th>';
    foreach ($course_list as $item) {
        $html_result .= '<th>'.$item['title'].'<br /> '.get_lang('Average score').' %</th>';
    }
    $html_result .= '<th>'.get_lang('Average score').' %</th>';
    $html_result .= '<th>'.get_lang('Last connexion date').'</th></tr>';

    foreach ($users as $user) {
        $total_student = 0;
        $counter++;
        $rowClass = 0 === $counter % 2 ? 'row_odd' : 'row_even';

        $html_result .= '<tr class="'.$rowClass.'"><td>';
        $html_result .= Security::remove_XSS($user['firstname'].' '.$user['lastname']);
        $html_result .= '</td>';

        // Reset counters per user
        $courseCounterForUser = 0;
        $total_result_by_user = 0;

        foreach ($course_list as $current_course) {
            $total_course = 0;
            $html_result .= '<td>';
            $result = '-';

            if (isset($main_result[$current_course['code']][$user['user_id']])) {
                $user_info_stat = $main_result[$current_course['code']][$user['user_id']];

                if (!empty($user_info_stat['result']) && !empty($user_info_stat['attempts'])) {
                    $resultValue = $user_info_stat['result'] / $user_info_stat['attempts'] * 100;
                    $resultValue = round($resultValue, 2);

                    $total_course += $resultValue;
                    $total_result_by_user += $resultValue;

                    if (!isset($course_average[$current_course['code']])) {
                        $course_average[$current_course['code']] = 0;
                    }
                    $course_average[$current_course['code']] += $total_course;

                    if (!isset($course_average_counter[$current_course['code']])) {
                        $course_average_counter[$current_course['code']] = 0;
                    }
                    $course_average_counter[$current_course['code']]++;

                    $result = $resultValue.' ('.$user_info_stat['attempts'].' '.get_lang('Attempts').')';
                    $courseCounterForUser++;
                }
            }

            $html_result .= $result;
            $html_result .= '</td>';
        }

        if (0 === $courseCounterForUser) {
            $total_student = '-';
        } else {
            $total_student = $total_result_by_user / $courseCounterForUser;
            $total_average_score += $total_student;
            $total_average_score_count++;
        }

        $string_date = Tracking::get_last_connection_date($user['user_id'], true);
        $html_result .= '<td>'.$total_student.'</td><td>'.$string_date.'</td></tr>';
    }

    $html_result .= '<tr><th>'.get_lang('Average score').'</th>';
    $total_average = 0;
    $counter = 0;

    foreach ($course_list as $course_item) {
        if (!empty($course_average_counter[$course_item['code']])) {
            $average_per_course = round(
                $course_average[$course_item['code']] / ($course_average_counter[$course_item['code']] * 100) * 100,
                2
            );
        } else {
            $average_per_course = 0;
        }

        if (!empty($average_per_course)) {
            $counter++;
        }
        $total_average += $average_per_course;
        $html_result .= '<td>'.$average_per_course.'</td>';
    }

    if (!empty($total_average_score_count)) {
        $total_average = round(
            $total_average_score / ($total_average_score_count * 100) * 100,
            2
        );
    } else {
        $total_average = '-';
    }

    $html_result .= '<td>'.$total_average.'</td>';
    $html_result .= '<td>-</td>';
    $html_result .= '</tr>';
    $html_result .= '</table>';
} else {
    $noResultsMessage = Display::return_message(get_lang('No results found'), 'warning');
}

// -----------------------------------------------------------------------------
// Rendering
// -----------------------------------------------------------------------------
if ($export_to_xls) {
    // Export mode: plain table or message, no additional layout changes.
    if (!empty($html_result)) {
        echo $html_result;
    } elseif (!empty($noResultsMessage)) {
        echo $noResultsMessage;
    }

    Display::display_footer();

    return;
}

$pageTitle = get_lang('Results of learning paths exercises by session');

// -----------------------------------------------------------------------------
// Admin layout (toolbar + cards) when view=admin
// -----------------------------------------------------------------------------
if ($viewIsAdmin && api_is_platform_admin(true, true)) {
    Display::display_header($pageTitle);

    echo '<style>
        .reporting-admin-card {
            border-color: #e5e7eb !important;
            border-width: 1px !important;
        }
        .reporting-admin-card .panel,
        .reporting-admin-card fieldset {
            border-color: #e5e7eb !important;
        }
    </style>';

    // Toolbar: MySpace admin menu + print button
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

    // Toolbar row
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
    echo        Display::return_message(
        get_lang('Learner score average is calculated bases on all learning paths and all attempts'),
        'normal'
    );
    echo '      </div>';
    echo '  </section>';

    // Results table or "no results" message
    if (!empty($html_result)) {
        echo '  <section class="reporting-admin-card bg-white rounded-xl shadow-sm border border-gray-200 w-full">';
        echo '      <div class="p-4 md:p-5 overflow-x-auto">';
        echo            $html_result;
        echo '      </div>';
        echo '  </section>';
    } elseif (!empty($noResultsMessage)) {
        echo '  <section class="mt-2">';
        echo        $noResultsMessage;
        echo '  </section>';
    }

    echo '</div>';

    Display::display_footer();

    return;
}

// -----------------------------------------------------------------------------
// Legacy layout (for non-admin views, kept for backward compatibility)
// -----------------------------------------------------------------------------
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

echo '<h2>'.get_lang('Results of learning paths exercises by session').'</h2>';
$form->display();
echo Display::return_message(
    get_lang('Learner score average is calculated bases on all learning paths and all attempts')
);

if (!empty($html_result)) {
    echo $html_result;
} elseif (!empty($noResultsMessage)) {
    echo $noResultsMessage;
}

Display::display_footer();
