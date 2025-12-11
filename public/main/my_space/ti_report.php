<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$tblSession        = Database::get_main_table(TABLE_MAIN_SESSION);
$tblSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);

$nameTools = get_lang('Reporting');

$allowToTrack = api_is_platform_admin(true, true);
if (!$allowToTrack) {
    api_not_allowed(true);
}

$action  = $_REQUEST['a'] ?? null;
$content = '';

// -----------------------------------------------------------------------------
// Date range filter form
// -----------------------------------------------------------------------------
$form = new FormValidator('users', 'get', api_get_self());
$form->addHidden('a', 'users_active');

$form->addDateRangePicker(
    'daterange',
    get_lang('Date range'),
    true,
    [
        'format'          => 'YYYY-MM-DD',
        'timePicker'      => 'false',
        'validate_format' => 'Y-m-d',
    ]
);

$form->addButtonFilter(get_lang('Search'));

$weekFormat = 'oW';

if ($form->validate()) {
    $values    = $form->getSubmitValues();
    $startDate = Database::escape_string($values['daterange_start']);
    $endDate   = Database::escape_string($values['daterange_end']);

    $first  = DateTime::createFromFormat('Y-m-d', $startDate);
    $second = DateTime::createFromFormat('Y-m-d', $endDate);
    $numberOfWeeks = floor($first->diff($second)->days / 7);

    $sql = "SELECT
                sru.user_id,
                s.title,
                s.id AS session_id,
                s.display_start_date,
                s.display_end_date
            FROM $tblSession s
            LEFT JOIN $tblSessionRelUser sru
                ON (sru.session_id = s.id AND sru.relation_type = ".Session::GENERAL_COACH.")
            WHERE s.display_start_date BETWEEN '$startDate' AND '$endDate'
            ORDER BY sru.user_id";

    $result = Database::query($sql);

    $coachList = [];

    while ($row = Database::fetch_assoc($result)) {
        $coachId = (int) $row['user_id'];

        if (!isset($coachList[$coachId])) {
            $userInfo = api_get_user_info($coachId);

            $coachList[$coachId] = [
                'complete_name' => $userInfo['complete_name'],
                'session_count' => 0,
                'week'          => [],
            ];
        }

        // First week of the session
        $startDateObj = new DateTime($row['display_start_date']);
        $week         = $startDateObj->format($weekFormat);
        $coachList[$coachId]['week'][$week]['sessions'][] = $row;

        // Additional weeks covered by the session
        $endDateObj   = new DateTime($row['display_end_date']);
        $weeksBetween = floor($startDateObj->diff($endDateObj)->days / 7);

        for ($i = 0; $i < $weeksBetween; $i++) {
            $startDateObj->add(new DateInterval('P1W'));
            $week = $startDateObj->format($weekFormat);
            $coachList[$coachId]['week'][$week]['sessions'][] = $row;
        }

        $coachList[$coachId]['session_count']++;
    }

    // -------------------------------------------------------------------------
    // Result table
    // -------------------------------------------------------------------------
    $table = new HTML_Table([
        'class' => 'min-w-full text-sm border border-gray-200 rounded-xl overflow-hidden',
    ]);

    $headers = [
        get_lang('Coach'),
        get_lang('Sessions'),
    ];

    $date = new DateTime($startDate);
    for ($i = 0; $i <= $numberOfWeeks; $i++) {
        $headers[] = $date->format('o-W');
        $date->add(new DateInterval('P1W'));
    }

    $row    = 0;
    $column = 0;

    foreach ($headers as $header) {
        $table->setHeaderContents($row, $column, $header);
        $table->updateCellAttributes(
            $row,
            $column,
            'class="bg-slate-50 text-xs font-semibold text-gray-700 text-center px-2 py-1 whitespace-nowrap"'
        );
        $column++;
    }

    $row++;
    $url = api_get_path(WEB_CODE_PATH).'session/resume_session.php?';

    foreach ($coachList as $coachData) {
        $column = 0;

        // Coach name
        $table->setCellContents($row, $column, $coachData['complete_name']);
        $table->updateCellAttributes(
            $row,
            $column,
            'class="align-top px-2 py-1 text-sm text-gray-900 whitespace-nowrap"'
        );
        $column++;

        // Number of sessions
        $table->setCellContents($row, $column, $coachData['session_count']);
        $table->updateCellAttributes(
            $row,
            $column,
            'class="align-top px-2 py-1 text-center text-sm text-gray-900 whitespace-nowrap"'
        );
        $column++;

        // Weekly distribution
        $date         = new DateTime($startDate);
        $sessionAdded = [];

        for ($i = 2; $i <= $numberOfWeeks; $i++) {
            $dateWeekToCheck = $date->format($weekFormat);

            if (isset($coachData['week'][$dateWeekToCheck])) {
                $sessionArray = [];

                foreach ($coachData['week'][$dateWeekToCheck]['sessions'] as $session) {
                    $name = $session['title'];

                    $showName = !in_array($session['session_id'], $sessionAdded, true);
                    if ($showName) {
                        $sessionAdded[] = $session['session_id'];
                    } else {
                        $name = '';
                    }

                    $sessionArray[] = Display::url(
                        $name,
                        $url.'id_session='.$session['session_id'],
                        [
                            'class'  => 'inline-flex items-center justify-center rounded-full bg-emerald-500 px-2 py-1 text-xs text-white',
                            'target' => '_blank',
                            'title'  => addslashes($session['title']),
                        ]
                    );
                }

                $value = implode('<br /><br />', $sessionArray);

                $value = '<div class="inline-block w-32 overflow-hidden text-ellipsis text-xs text-white">'.
                    $value.
                    '</div>';

                $table->setCellContents($row, $i, $value);
                $table->updateCellAttributes(
                    $row,
                    $i,
                    'class="bg-emerald-50 align-top px-2 py-1 text-center"'
                );
            } else {
                $table->setCellContents(
                    $row,
                    $i,
                    '<div class="inline-block w-32 h-4"></div>'
                );
                $table->updateCellAttributes(
                    $row,
                    $i,
                    'class="align-top px-2 py-1 text-center"'
                );
            }

            $date->add(new DateInterval('P1W'));
        }

        $row++;
    }

    $content = $table->toHtml();
}

// -----------------------------------------------------------------------------
// Page rendering
// -----------------------------------------------------------------------------
Display::display_header($nameTools);

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

// Toolbar
$actionsLeft = Display::mySpaceMenu('tc_report');

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
echo        Display::page_subheader(get_lang('Head manager report'));
echo '  </div>';

// Navigation cards
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
echo MySpace::renderAdminReportCardsSection(null, $currentScript, false);

// Content card (filter + results)
echo '  <section class="reporting-admin-card bg-white rounded-xl shadow-sm w-full">';
echo '      <div class="p-4 md:p-5 space-y-4">';
echo '          <h2 class="text-base md:text-lg font-semibold text-gray-800">'.
    get_lang('Head manager report').
    '</h2>';
echo '          <p class="text-sm text-gray-600">'.
    get_lang('This report shows the weekly distribution of sessions per general coach in the selected date range.').
    '</p>';

echo '          <div class="mb-4">';
echo                $form->returnForm();
echo '          </div>';

if (!empty($content)) {
    echo '      <div class="overflow-x-auto">';
    echo            $content;
    echo '      </div>';
} elseif (isset($_GET['a']) && 'users_active' === $_GET['a']) {
    echo '      <p class="text-sm text-gray-500">'.
        get_lang('No sessions found for the selected period.').
        '</p>';
}

echo '      </div>';
echo '  </section>';

echo '</div>';

Display::display_footer();
