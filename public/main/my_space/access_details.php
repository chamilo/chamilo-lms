<?php

/* For licensing terms, see /license.txt */

/**
 * This is the tracking library for Chamilo.
 *
 * @param int    $user_id     the user id
 * @param string $course_code the course code
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Jorge Frisancho Jibaja - select between dates
 */
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) ||
    api_is_teacher() || api_is_course_tutor();

if (!$allowToTrack) {
    api_not_allowed(true);
    exit;
}

// the section (for the tabs)
$this_section = SECTION_TRACKING;

$user_id = isset($_REQUEST['student']) ? (int) $_REQUEST['student'] : 0;
$session_id = isset($_REQUEST['id_session']) ? (int) $_REQUEST['id_session'] : 0;
$type = isset($_REQUEST['type']) ? Security::remove_XSS((string) $_REQUEST['type']) : 'day';
$course_code = isset($_REQUEST['course']) ? Security::remove_XSS((string) $_REQUEST['course']) : '';

$courseInfo = api_get_course_info($course_code);
if (empty($courseInfo)) {
    api_not_allowed(true);
    exit;
}

$userInfo = api_get_user_info($user_id);
if (empty($userInfo)) {
    api_not_allowed(true);
    exit;
}

$origin = isset($_REQUEST['origin'])
    ? Security::remove_XSS((string) $_REQUEST['origin'])
    : 'tracking_course';

$cid = isset($_REQUEST['cid'])
    ? (int) $_REQUEST['cid']
    : (int) ($courseInfo['real_id'] ?? 0);

$backUrl = api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?details=true'
    .'&cid='.$cid
    .'&course='.urlencode($course_code)
    .'&origin='.urlencode($origin)
    .'&sid='.$session_id
    .'&student='.$user_id;

$allowedTypes = ['day', 'month'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'day';
}

$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');

$from = isset($_REQUEST['from']) ? Security::remove_XSS((string) $_REQUEST['from']) : $defaultFrom;
$to = isset($_REQUEST['to']) ? Security::remove_XSS((string) $_REQUEST['to']) : $defaultTo;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = $defaultFrom;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = $defaultTo;
}

$form = new FormValidator(
    'myform',
    'get',
    api_get_self(),
    null,
    ['id' => 'myform', 'class' => 'w-full']
);

$renderer = $form->defaultRenderer();
$renderer->setElementTemplate(
    '
    <div class="mb-5">
        <label {label-for} class="mb-2 block text-sm font-medium text-gray-700">
            <!-- BEGIN required --><span class="text-red-600">*</span> <!-- END required -->{label}
        </label>
        <div>
            {element}
            <!-- BEGIN error -->
                <div class="mt-2 text-sm text-red-600">{error}</div>
            <!-- END error -->
            <!-- BEGIN label_2 -->
                <div class="mt-1 text-sm text-gray-500">{label_2}</div>
            <!-- END label_2 -->
        </div>
    </div>'
);

$renderer->setRequiredNoteTemplate(
    '<div class="mt-4 text-sm text-gray-500">{requiredNote}</div>'
);

$form->setRequiredNote(
    '<span class="text-red-600">*</span> '.get_lang('Required field')
);

$inputClass = 'block w-full rounded-xl border border-gray-30 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20';
$selectClass = $inputClass;

$form->addElement(
    'text',
    'from',
    get_lang('From'),
    [
        'id' => 'date_from',
        'class' => $inputClass,
        'autocomplete' => 'off',
        'placeholder' => 'YYYY-MM-DD',
    ]
);

$form->addElement(
    'text',
    'to',
    get_lang('Until'),
    [
        'id' => 'date_to',
        'class' => $inputClass,
        'autocomplete' => 'off',
        'placeholder' => 'YYYY-MM-DD',
    ]
);

$form->addSelect(
    'type',
    get_lang('Type'),
    [
        'day' => get_lang('Day'),
        'month' => get_lang('Month'),
    ],
    [
        'id' => 'type',
        'class' => $selectClass,
    ]
);

$form->addElement('hidden', 'student', $user_id);
$form->applyFilter('student', 'html_filter');

$form->addElement('hidden', 'course', $course_code);
$form->applyFilter('course', 'html_filter');

$form->addElement('hidden', 'id_session', $session_id);
$form->applyFilter('id_session', 'html_filter');

$form->addRule('from', get_lang('Required field'), 'required');
$form->addRule('to', get_lang('Required field'), 'required');

$form->addHtml(
    '
    <div class="mt-6 flex flex-wrap items-center gap-3">
        <button
            id="access-details-search"
            type="submit"
            class="inline-flex items-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
        >
            '.get_lang('Search').'
        </button>
        <span id="loading-state" class="hidden text-sm text-gray-500">'.get_lang('Loading').'...</span>
    </div>'
);

$form->setDefaults([
    'from' => $from,
    'to' => $to,
    'type' => $type,
]);

$course = $course_code;
if ($form->validate()) {
    $values = $form->exportValues();
    $from = $values['from'] ?? $from;
    $to = $values['to'] ?? $to;
    $type = $values['type'] ?? $type;
    $course = $values['course'] ?? $course_code;
}

$url = api_get_path(WEB_AJAX_PATH).'myspace.ajax.php?a=access_detail_by_date'
    .'&course='.urlencode($course)
    .'&student='.$user_id
    .'&session_id='.$session_id;

$flatpickrCss = api_get_path(WEB_PATH).'build/flatpickr/flatpickr.min.css';
$flatpickrJs = api_get_path(WEB_PATH).'build/flatpickr/flatpickr.min.js';

$exportLabel = addslashes(get_lang('Export to XLS'));
$noDataLabel = addslashes(get_lang('No data available'));
$errorLabel = addslashes(get_lang('An error occurred'));
$emptyInstruction = addslashes(get_lang('Use the filters above to load the learner access details.'));
$invalidRangeLabel = addslashes(get_lang('Start date must be before end date'));
$loadingLabel = addslashes(get_lang('Loading'));

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.$flatpickrCss.'" />';
$htmlHeadXtra[] = '<script src="'.$flatpickrJs.'"></script>';
$htmlHeadXtra[] = "
<script>
function setAccessDetailsLoading(isLoading) {
    var button = document.getElementById('access-details-search');
    var loading = document.getElementById('loading-state');

    if (button) {
        button.disabled = isLoading;
    }

    if (loading) {
        if (isLoading) {
            loading.classList.remove('hidden');
        } else {
            loading.classList.add('hidden');
        }
    }
}

function setInfoMessage(targetSelector, text, toneClass) {
    var html = '<div class=\"rounded-xl border px-4 py-3 text-sm ' + toneClass + '\">' + text + '</div>';
    $(targetSelector).html(html);
}

function renderAccessDetailsEmptyState() {
    setInfoMessage('#messages', '".$noDataLabel."', 'border-gray-30 bg-gray-20 text-amber-800');
    setInfoMessage('#cev_cont_stats', '".$noDataLabel."', 'border-gray-30 bg-gray-20 text-amber-800');
    $('#graph').empty();
    $('#export-link-container').empty();
}

function enhanceGraphContainer() {
    $('#graph img').addClass('max-w-full h-auto mx-auto');
    $('#graph table').addClass('w-full');
}

function getRangeInDays(startDate, endDate) {
    var start = new Date(startDate + 'T00:00:00');
    var end = new Date(endDate + 'T00:00:00');

    if (isNaN(start.getTime()) || isNaN(end.getTime())) {
        return 0;
    }

    return Math.floor((end.getTime() - start.getTime()) / 86400000) + 1;
}

function loadGraph() {
    var startDate = $('#date_from').val();
    var endDate = $('#date_to').val();
    var selectedType = $('#type').val();
    var effectiveType = selectedType;

    if (!startDate || !endDate) {
        return false;
    }

    if (startDate > endDate) {
        $('#messages').html(
            '<div class=\"rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800\">Start date must be before end date</div>'
        );
        return false;
    }

    var rangeInDays = getRangeInDays(startDate, endDate);

    // Auto-switch to month mode for large ranges to keep the chart readable.
    if ('day' === selectedType && rangeInDays > 93) {
        effectiveType = 'month';
        $('#type').val('month');
    }

    var requestUrl = '".$url."&startDate=' + encodeURIComponent(startDate) + '&endDate=' + encodeURIComponent(endDate) + '&type=' + encodeURIComponent(effectiveType);

    if (window.history && window.history.replaceState) {
        var newUrl = new URL(window.location.href);
        newUrl.searchParams.set('student', '".(int) $user_id."');
        newUrl.searchParams.set('course', '".addslashes($course_code)."');
        newUrl.searchParams.set('id_session', '".(int) $session_id."');
        newUrl.searchParams.set('from', startDate);
        newUrl.searchParams.set('to', endDate);
        newUrl.searchParams.set('type', effectiveType);
        window.history.replaceState({}, '', newUrl.toString());
    }

    setAccessDetailsLoading(true);

    $.ajax({
        url: requestUrl,
        dataType: 'json',
        success: function(db) {
            if (db && !db.is_empty) {
                $('#messages').html(db.result || '');
                $('#cev_cont_stats').html(db.stats || '');
                $('#graph').html(db.graph_result || '');

                var exportLink = $('<a></a>')
                    .attr('href', requestUrl + '&export=excel')
                    .attr('target', '_blank')
                    .attr('class', 'inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700')
                    .text('Export to XLS');

                $('#export-link-container').html(exportLink);
            } else {
                renderAccessDetailsEmptyState();
            }
        },
        error: function() {
            $('#messages').html(
                '<div class=\"rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800\">An error occurred</div>'
            );
            $('#graph').empty();
            $('#export-link-container').empty();
        },
        complete: function() {
            setAccessDetailsLoading(false);
        }
    });

    return false;
}

$(function() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#date_from', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });

        flatpickr('#date_to', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });
    }

    $('#myform').on('submit', function(e) {
        e.preventDefault();
        loadGraph();
    });

    if (!$('#messages').children().length) {
        setInfoMessage('#messages', '".$emptyInstruction."', 'border-sky-200 bg-sky-50 text-sky-800');
    }

    loadGraph();
});
</script>";

$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Access details')];

Display::display_header('');

echo '<div class="mb-4">';
echo '    <a
            href="'.$backUrl.'"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-30 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-10 hover:text-gray-900"
        >';
echo          Display::getMdiIcon('arrow-left', 'ch-tool-icon', null, 18, get_lang('Back'));
echo '        <span>'.get_lang('Back').'</span>';
echo '    </a>';
echo '</div>';

echo Display::page_header(get_lang('Learner details in course'));
echo Display::page_subheader(
    get_lang('User').': '.$userInfo['complete_name'].' - '.
    get_lang('Course').': '.$courseInfo['title'].' ('.$course_code.')'
);

$data = MySpace::getStats($user_id, $courseInfo, $session_id, $from, $to);

echo '<div class="mt-6 space-y-6">';

echo '<div class="rounded-2xl border border-gray-30 bg-white shadow-sm">';
echo '    <div class="border-b border-gray-30 px-6 py-4">';
echo '        <h2 class="text-lg font-semibold text-gray-800">'.get_lang('Filter').'</h2>';
echo '        <p class="mt-1 text-sm text-gray-500">'.get_lang('Select a date range and grouping type to review the learner access details.').'</p>';
echo '    </div>';
echo '    <div class="px-6 py-6">';
$form->display();
echo '    </div>';
echo '</div>';

echo '<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">';

echo '    <div class="rounded-2xl border border-gray-30 bg-white shadow-sm">';
echo '        <div class="border-b border-gray-30 px-6 py-4">';
echo '            <h3 class="text-lg font-semibold text-gray-800">'.get_lang('Statistics').'</h3>';
echo '        </div>';
echo '        <div id="cev_cont_stats" class="px-6 py-6">';

if (!empty($data)) {
    echo '      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">';
    echo '          <div class="rounded-xl bg-gray-20 p-4">';
    echo '              <dt class="text-sm font-medium text-gray-500">'.get_lang('Total').'</dt>';
    echo '              <dd class="mt-2 text-xl font-semibold text-gray-900">'.($data['total'] ?? '-').'</dd>';
    echo '          </div>';
    echo '          <div class="rounded-xl bg-gray-20 p-4">';
    echo '              <dt class="text-sm font-medium text-gray-500">'.get_lang('Average').'</dt>';
    echo '              <dd class="mt-2 text-xl font-semibold text-gray-900">'.($data['avg'] ?? '-').'</dd>';
    echo '          </div>';
    echo '          <div class="rounded-xl bg-gray-20 p-4">';
    echo '              <dt class="text-sm font-medium text-gray-500">'.get_lang('Quantity').'</dt>';
    echo '              <dd class="mt-2 text-xl font-semibold text-gray-900">'.($data['times'] ?? '0').'</dd>';
    echo '          </div>';
    echo '      </dl>';
} else {
    echo '<div class="rounded-xl border border-gray-30 bg-gray-10 px-4 py-3 text-sm text-amber-800">'.get_lang('No data available').'</div>';
}

echo '        </div>';
echo '    </div>';

echo '    <div class="rounded-2xl border border-gray-30 bg-white shadow-sm">';
echo '        <div class="border-b border-gray-30 px-6 py-4">';
echo '            <div class="flex flex-wrap items-center justify-between gap-3">';
echo '                <h3 class="text-lg font-semibold text-gray-800">'.get_lang('Details').'</h3>';
echo '                <div id="export-link-container"></div>';
echo '            </div>';
echo '        </div>';
echo '        <div class="px-6 py-6">';
echo '            <div id="messages"></div>';
echo '        </div>';
echo '    </div>';

echo '</div>';

echo '<div class="rounded-2xl border border-gray-30 bg-white shadow-sm">';
echo '    <div class="border-b border-gray-30 px-6 py-4">';
echo '        <h3 class="text-lg font-semibold text-gray-800">'.get_lang('Access details').'</h3>';
echo '    </div>';
echo '    <div class="px-6 py-6">';
echo '        <div id="graph" class="overflow-x-auto"></div>';
echo '    </div>';
echo '</div>';

echo '</div>';

Display::display_footer();
