<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$csv_content = [];
$nameTools = get_lang('MySpace');

$allowToTrack = api_is_platform_admin(true, true);
if (!$allowToTrack) {
    api_not_allowed(true);
}

$userInfo = [];
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$content = '';
switch ($action) {
    case 'add_user':
        break;
}

Display::display_header($nameTools);
echo '<div class="actions">';
echo MySpace::getTopMenu();
echo '</div>';
echo MySpace::getAdminActions();

$form = new FormValidator('users', 'get', api_get_self().'?a=users_active');
$form->addDateRangePicker(
    'daterange',
    get_lang('DateRange'),
    true,
    ['format' => 'YYYY-MM-DD', 'timePicker' => 'false', 'validate_format' => 'Y-m-d']
);
$form->addHidden('a', 'users_active');
$form->addButtonFilter(get_lang('Search'));

$weekFormat = 'oW';

if ($form->validate()) {
    $startDate = Database::escape_string($_REQUEST['daterange_start']);
    $endDate = Database::escape_string($_REQUEST['daterange_end']);

    $date = new DateTime($startDate);
    $weekStart = $date->format($weekFormat);

    $date = new DateTime($endDate);
    $weekEnd = $date->format($weekFormat);

    $first = DateTime::createFromFormat('Y-m-d', $startDate);
    $second = DateTime::createFromFormat('Y-m-d', $endDate);
    $numberOfWeeks = floor($first->diff($second)->days / 7);

    $sql = " SELECT id_coach, name, id as session_id, display_start_date, display_end_date
             FROM session
             WHERE display_start_date BETWEEN '$startDate' AND '$endDate'
             ORDER BY id_coach";
    $result = Database::query($sql);

    $coachList = [];
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $coachId = $row['id_coach'];
        if (!isset($coachList[$coachId])) {
            $userInfo = api_get_user_info($coachId);
            $coachList[$coachId]['complete_name'] = $userInfo['complete_name'];
            $coachList[$coachId]['session_count'] = 0;
        }

        $date = new DateTime($row['display_start_date']);
        $week = $date->format($weekFormat);
        $coachList[$coachId]['week'][$week]['sessions'][] = $row;
        $coachList[$coachId]['session_count'] += 1;
        $coachList[$coachId]['data'] = $row;
    }

    $table = new HTML_Table(['class' => 'table table-responsive']);
    $headers = [
        get_lang('Coach'),
        get_lang('Sessions'),
    ];

    $date = new DateTime($startDate);
    for ($i = 0; $i <= $numberOfWeeks; $i++) {
        $headers[] = $date->format('o-W');
        $date->add(new DateInterval('P1W'));
    }

    $row = 0;
    $column = 0;
    foreach ($headers as $header) {
        $table->setHeaderContents($row, $column, $header);
        $column++;
    }
    $row++;
    $url = api_get_path(WEB_CODE_PATH).'session/resume_session.php?';
    foreach ($coachList as $coachData) {
        $column = 0;
        $table->setCellContents($row, $column++, $coachData['complete_name']);
        $table->setCellContents($row, $column++, $coachData['session_count']);

        $date = new DateTime($startDate);
        for ($i = 2; $i <= $numberOfWeeks; $i++) {
            $dateWeekToCheck = $date->format($weekFormat);
            if (isset($coachData['week'][$dateWeekToCheck])) {
                $sessionArray = [];
                foreach ($coachData['week'][$dateWeekToCheck]['sessions'] as $session) {
                    $date2 = new DateTime($session['display_start_date']);
                    $sessionArray[] = Display::url(
                        $session['name'],
                        $url.'id_session='.$session['session_id'],
                        ['class' => 'label label-success', 'target' => '_blank']
                    );
                }
                $table->setCellContents($row, $i, implode('<br /><br />', $sessionArray));
                $table->updateCellAttributes(
                    $row,
                    $i,
                    'style="background:green"'
                );
            }
            $date->add(new DateInterval('P1W'));
        }
        $row++;
    }

    $content = $table->toHtml();
}

echo $form->returnForm();
echo $content;

Display::display_footer();
