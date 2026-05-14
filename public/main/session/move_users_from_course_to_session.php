<?php

/* For licensing terms, see /license.txt */

/**
 * Move user tracking data from a base course/session to a destination session.
 */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$workLib = api_get_path(SYS_CODE_PATH).'work/work.lib.php';
if (file_exists($workLib)) {
    require_once $workLib;
}

$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_admin_script();
api_protect_limit_for_session_admin();
api_set_more_memory_and_time_limits();

function renderTailwindMessage(string $message, string $type = 'info'): string
{
    $styles = [
        'success' => 'border-success/30 bg-success/10 text-success',
        'warning' => 'border-warning/30 bg-warning/10 text-warning',
        'danger' => 'border-danger/30 bg-danger/10 text-danger',
        'info' => 'border-info/30 bg-info/10 text-info',
    ];

    $class = $styles[$type] ?? $styles['info'];

    return '<div class="rounded-xl border '.$class.' px-4 py-3 text-sm font-medium">'.
        api_htmlentities($message).
        '</div>';
}

function renderTailwindFieldLabel(string $for, string $label, string $help = ''): string
{
    $html = '<label for="'.api_htmlentities($for).'" class="mb-2 block text-sm font-semibold text-gray-90">'.
        api_htmlentities($label).
        '</label>';

    if (!empty($help)) {
        $html .= '<p class="mb-2 text-xs text-gray-60">'.api_htmlentities($help).'</p>';
    }

    return $html;
}

function renderTailwindSessionOptions(array $sessions, int $selectedSessionId): string
{
    $html = '';

    foreach ($sessions as $sessionId => $sessionTitle) {
        $sessionId = (int) $sessionId;
        $selected = $sessionId === $selectedSessionId ? ' selected="selected"' : '';
        $html .= '<option value="'.$sessionId.'"'.$selected.'>'.api_htmlentities($sessionTitle).'</option>';
    }

    return $html;
}

function renderTailwindButton(string $name, string $value, string $label, string $variant = 'primary'): string
{
    $styles = [
        'primary' => 'bg-primary text-white hover:bg-primary/90 focus:ring-primary/30',
        'secondary' => 'bg-secondary text-secondary-button-text hover:bg-secondary/80 focus:ring-secondary/30',
        'success' => 'bg-success text-success-button-text hover:bg-success/90 focus:ring-success/30',
    ];

    $class = $styles[$variant] ?? $styles['primary'];

    return '<button type="submit" name="'.api_htmlentities($name).'" value="'.api_htmlentities($value).'" '.
        'class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold shadow-sm '.
        'transition focus:outline-none focus:ring-2 focus:ring-offset-2 '.$class.'">'.
        api_htmlentities($label).
        '</button>';
}

$isPost = 'POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($isPost && !Security::check_token('post')) {
    Display::addFlash(renderTailwindMessage(get_lang('Invalid security token'), 'danger'));
    api_location(api_get_self());
}

$courseId = 0;

if (isset($_REQUEST['course_id']) && '' !== (string) $_REQUEST['course_id']) {
    $courseId = (int) $_REQUEST['course_id'];
}

if (isset($_REQUEST['course_id_manual']) && !empty($_REQUEST['course_id_manual'])) {
    $courseId = (int) $_REQUEST['course_id_manual'];
}

$sourceSessionId = isset($_REQUEST['source_session_id']) ? (int) $_REQUEST['source_session_id'] : 0;
$destinationSessionId = isset($_REQUEST['destination_session_id']) ? (int) $_REQUEST['destination_session_id'] : 0;
$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

$courseInfo = [];
$courseOptions = [];
$sessions = [];
$sessionsWithBase = [0 => get_lang('Base course')];
$messages = [];
$resultBlocks = [];

if (!empty($courseId)) {
    $courseInfo = api_get_course_info_by_id($courseId);

    if (!empty($courseInfo)) {
        $courseOptions[$courseId] = $courseInfo['title'];
        $sessionList = SessionManager::get_session_by_course($courseId);

        foreach ($sessionList as $sessionData) {
            $sessionId = (int) $sessionData['id'];
            $sessionTitle = $sessionData['title'] ?? $sessionData['name'] ?? '#'.$sessionId;

            $sessions[$sessionId] = $sessionTitle;
            $sessionsWithBase[$sessionId] = $sessionTitle;
        }
    } else {
        $messages[] = [
            'type' => 'warning',
            'message' => get_lang('Course not found'),
        ];
    }
}

if ($isPost && in_array($action, ['compare', 'move'], true)) {
    $isMove = 'move' === $action;

    if (empty($courseInfo)) {
        $messages[] = [
            'type' => 'warning',
            'message' => get_lang('Course not found'),
        ];
    } elseif (empty($destinationSessionId)) {
        $messages[] = [
            'type' => 'warning',
            'message' => get_lang('Please select a destination session'),
        ];
    } elseif (!empty($sourceSessionId) && $sourceSessionId === $destinationSessionId) {
        $messages[] = [
            'type' => 'warning',
            'message' => get_lang('Cannot move this to the same session'),
        ];
    } else {
        $availableSessionIds = array_map('intval', array_keys($sessions));

        if (!in_array($destinationSessionId, $availableSessionIds, true)) {
            $messages[] = [
                'type' => 'warning',
                'message' => get_lang('The destination session must include the selected course'),
            ];
        } elseif (!empty($sourceSessionId) && !in_array($sourceSessionId, $availableSessionIds, true)) {
            $messages[] = [
                'type' => 'warning',
                'message' => get_lang('The source session must include the selected course'),
            ];
        } else {
            if (empty($sourceSessionId)) {
                $students = CourseManager::get_user_list_from_course_code(
                    $courseInfo['code'],
                    0,
                    null,
                    null,
                    STUDENT
                );
            } else {
                $students = CourseManager::get_user_list_from_course_code(
                    $courseInfo['code'],
                    $sourceSessionId,
                    null,
                    null,
                    0
                );
            }

            if (empty($students)) {
                $messages[] = [
                    'type' => 'warning',
                    'message' => get_lang('No learners found'),
                ];
            }

            foreach ($students as $student) {
                $studentId = (int) $student['user_id'];
                $name = api_get_person_name($student['firstname'], $student['lastname']);

                $isSubscribed = SessionManager::isUserSubscribedAsStudent($destinationSessionId, $studentId);
                $subscriptionStatus = $isSubscribed ? get_lang('Already subscribed') : get_lang('Will be subscribed');

                if (!$isSubscribed && $isMove) {
                    SessionManager::subscribeUsersToSession(
                        $destinationSessionId,
                        [$studentId],
                        false,
                        false
                    );

                    $subscriptionStatus = get_lang('Subscribed');
                }

                ob_start();

                Tracking::processUserDataMove(
                    $studentId,
                    $courseInfo,
                    $sourceSessionId,
                    $destinationSessionId,
                    $isMove
                );

                $resultBlocks[] = [
                    'student_id' => $studentId,
                    'name' => $name,
                    'subscription_status' => $subscriptionStatus,
                    'content' => ob_get_clean(),
                ];
            }

            if (!empty($resultBlocks)) {
                $messages[] = [
                    'type' => $isMove ? 'success' : 'info',
                    'message' => $isMove
                        ? get_lang('The move process has finished')
                        : get_lang('Comparison finished. No data has been moved.'),
                ];
            }
        }
    }
}

$secToken = Security::get_existing_token();
$title = get_lang('Move users results from base course to a session');
$courseAjaxUrl = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course';

Display::display_header($title);

echo '<div class="space-y-6">';

echo '<div class="flex flex-col gap-4 rounded-2xl border border-primary/30 bg-primary/10 p-6 shadow-sm md:flex-row md:items-center md:justify-between">';
echo '<div class="flex items-start gap-4">';
echo '<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-white shadow-sm">';
echo '<span class="mdi mdi-swap-horizontal-bold text-2xl" aria-hidden="true"></span>';
echo '</div>';
echo '<div>';
echo '<h1 class="text-2xl font-bold text-gray-90">'.api_htmlentities($title).'</h1>';
echo '<p class="mt-2 max-w-3xl text-sm leading-6 text-gray-70">'.
    api_htmlentities(get_lang('Move tracking data from the base course or a source session to another session. Use comparison first to review the affected learner data.')).
    '</p>';
echo '</div>';
echo '</div>';
echo '<a href="../admin/index.php" class="inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-gray-90 shadow-sm transition hover:bg-gray-15 focus:outline-none focus:ring-2 focus:ring-primary/30">';
echo Display::getMdiIcon(
    ActionIcon::BACK,
    'ch-tool-icon',
    null,
    ICON_SIZE_SMALL,
    get_lang('Back to').' '.get_lang('Administration')
);
echo '<span>'.api_htmlentities(get_lang('Back to').' '.get_lang('Administration')).'</span>';
echo '</a>';
echo '</div>';

foreach ($messages as $message) {
    echo renderTailwindMessage($message['message'], $message['type']);
}

echo '<form method="post" action="'.api_htmlentities(api_get_self()).'" class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">';
echo '<input type="hidden" name="sec_token" value="'.api_htmlentities($secToken).'">';

echo '<div class="grid gap-6 lg:grid-cols-3">';
echo '<div class="lg:col-span-2">';
echo renderTailwindFieldLabel(
    'course_id',
    get_lang('Course'),
    get_lang('Search for a course or use the manual course ID field below.')
);
echo '<select id="course_id" name="course_id" data-ajax-url="'.api_htmlentities($courseAjaxUrl).'" class="block w-full rounded-xl border border-gray-25 bg-white px-3 py-2 text-sm text-gray-90 shadow-sm transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">';
if (!empty($courseOptions)) {
    foreach ($courseOptions as $selectedCourseId => $selectedCourseTitle) {
        echo '<option value="'.(int) $selectedCourseId.'" selected="selected">'.
            api_htmlentities($selectedCourseTitle).
            '</option>';
    }
}
echo '</select>';
echo '</div>';

echo '<div>';
echo renderTailwindFieldLabel(
    'course_id_manual',
    get_lang('Course ID'),
    get_lang('Fallback when the course search is not available.')
);
echo '<input id="course_id_manual" name="course_id_manual" type="number" min="1" value="" placeholder="'.api_htmlentities((string) $courseId).'" class="block w-full rounded-xl border border-gray-25 bg-white px-3 py-2 text-sm text-gray-90 shadow-sm transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">';
echo '</div>';
echo '</div>';

echo '<div class="mt-6 flex justify-end">';
echo renderTailwindButton('action', 'load', get_lang('Load sessions'), 'primary');
echo '</div>';

if (!empty($courseInfo)) {
    echo '<div class="mt-6 rounded-2xl border border-info/30 bg-info/10 p-4">';
    echo '<div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">';
    echo '<div>';
    echo '<p class="text-xs font-semibold uppercase tracking-wide text-info">'.api_htmlentities(get_lang('Selected course')).'</p>';
    echo '<p class="mt-1 text-sm font-semibold text-gray-90">'.api_htmlentities($courseInfo['title']).'</p>';
    echo '<p class="mt-1 text-xs text-gray-70">'.api_htmlentities($courseInfo['code']).' · #'.(int) $courseId.'</p>';
    echo '</div>';
    echo '<span class="inline-flex w-fit items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-info shadow-sm">'.
        count($sessions).' '.api_htmlentities(get_lang('Sessions')).
        '</span>';
    echo '</div>';
    echo '</div>';
}

if (!empty($courseInfo) && !empty($sessions)) {
    echo '<div class="mt-6 grid gap-6 md:grid-cols-2">';
    echo '<div>';
    echo renderTailwindFieldLabel(
        'source_session_id',
        get_lang('Source'),
        get_lang('Choose the base course or the session where the tracking data currently exists.')
    );
    echo '<select id="source_session_id" name="source_session_id" class="block w-full rounded-xl border border-gray-25 bg-white px-3 py-2 text-sm text-gray-90 shadow-sm transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">';
    echo renderTailwindSessionOptions($sessionsWithBase, $sourceSessionId);
    echo '</select>';
    echo '</div>';

    echo '<div>';
    echo renderTailwindFieldLabel(
        'destination_session_id',
        get_lang('Destination'),
        get_lang('Choose the session that will receive the tracking data.')
    );
    echo '<select id="destination_session_id" name="destination_session_id" class="block w-full rounded-xl border border-gray-25 bg-white px-3 py-2 text-sm text-gray-90 shadow-sm transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">';
    echo '<option value="0">'.api_htmlentities(get_lang('Select')).'</option>';
    echo renderTailwindSessionOptions($sessions, $destinationSessionId);
    echo '</select>';
    echo '</div>';
    echo '</div>';

    echo '<div class="mt-6 rounded-2xl border border-warning/30 bg-warning/10 p-4 text-sm leading-6 text-warning">';
    echo '<strong>'.api_htmlentities(get_lang('Important')).':</strong> '.
        api_htmlentities(get_lang('The move action updates existing tracking data. Run compare first and test this tool with a non-production course before moving real learner data.'));
    echo '</div>';

    echo '<div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">';
    echo renderTailwindButton('action', 'compare', get_lang('Compare stats'), 'secondary');
    echo renderTailwindButton('action', 'move', get_lang('Move'), 'success');
    echo '</div>';
} elseif (!empty($courseInfo)) {
    echo '<div class="mt-6">';
    echo renderTailwindMessage(get_lang('No sessions found for this course'), 'warning');
    echo '</div>';
}

echo '</form>';

if (!empty($resultBlocks)) {
    echo '<div class="space-y-4">';
    echo '<h2 class="text-xl font-bold text-gray-90">'.api_htmlentities(get_lang('Results')).'</h2>';

    foreach ($resultBlocks as $resultBlock) {
        echo '<article class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">';
        echo '<header class="flex flex-col gap-2 border-b border-gray-25 bg-gray-10 px-5 py-4 md:flex-row md:items-center md:justify-between">';
        echo '<div>';
        echo '<h3 class="text-base font-semibold text-gray-90">'.
            api_htmlentities($resultBlock['name']).' #'.(int) $resultBlock['student_id'].
            '</h3>';
        echo '</div>';
        echo '<span class="inline-flex w-fit items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">'.
            api_htmlentities($resultBlock['subscription_status']).
            '</span>';
        echo '</header>';
        echo '<div class="p-5 text-sm text-gray-80">';
        echo $resultBlock['content'];
        echo '</div>';
        echo '</article>';
    }

    echo '</div>';
}

echo '</div>';

echo '<script>
document.addEventListener("DOMContentLoaded", function () {
    const courseSelect = document.getElementById("course_id")

    if (!courseSelect || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
        return
    }

    const ajaxUrl = courseSelect.dataset.ajaxUrl

    window.jQuery(courseSelect).select2({
        ajax: {
            url: ajaxUrl,
            dataType: "json",
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || "",
                    term: params.term || ""
                }
            },
            processResults: function (data) {
                if (data && Array.isArray(data.results)) {
                    return data
                }

                if (data && Array.isArray(data.items)) {
                    return {
                        results: data.items.map(function (item) {
                            return {
                                id: item.id || item.value,
                                text: item.text || item.title || item.label
                            }
                        })
                    }
                }

                if (Array.isArray(data)) {
                    return {
                        results: data.map(function (item) {
                            return {
                                id: item.id || item.value,
                                text: item.text || item.title || item.label
                            }
                        })
                    }
                }

                return {
                    results: []
                }
            }
        },
        minimumInputLength: 2,
        width: "100%"
    })
})
</script>';

Display::display_footer();
