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

/*$form = new FormValidator('survey');
$form->addSelectAjax(
    'user_id',
    get_lang('User'),
    [],
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
    ]
);
$form->addButtonSearch();*/

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


if ($form->validate()) {
    $startDate = $_REQUEST['daterange_start'];
    $endDate = $_REQUEST['daterange_end'];

    $date = new DateTime($startDate);
    $weekStart = $date->format('YW');

    $date = new DateTime($endDate);
    $weekEnd = $date->format('YW');

    $first = DateTime::createFromFormat('Y-m-d', $startDate);
    $second = DateTime::createFromFormat('Y-m-d', $endDate);
    $numberOfWeeks = floor($first->diff($second)->days/7);

    echo $content;
    //SessionManager::get_sessions_by_general_coach()
    $sql = " SELECT id_coach, id as session_id, display_start_date, display_end_date
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
        $week = $date->format('YW');
        $coachList[$coachId]['week'][$week] = $week;
        $coachList[$coachId]['session_count'] += 1;
    }

    $table = new HTML_Table(['class' => 'table table-responsive']);
    $headers = [
        get_lang('Coach'),
        get_lang('Sessions'),
    ];

    $i = $weekStart;
    for ($i; $i <= $weekEnd; $i++) {
        $headers[] = substr($weekStart,0,4).' - '.substr($i,4,6);
    }

    $row = 0;
    $column = 0;
    foreach ($headers as $header) {
        $table->setHeaderContents($row, $column, $header);
        $column++;
    }
    $row++;

    foreach ($coachList as $coachData) {
        $column = 0;
        $table->setCellContents($row, $column++, $coachData['complete_name']);
        $table->setCellContents($row, $column++, $coachData['session_count']);

        $i = $weekStart;
        for ($i; $i <= $weekEnd; $i++) {
            if (isset($coachData['week'][$i])) {
                $table->setCellContents($row, $column++, '');
                $table->updateCellAttributes(
                    $row,
                    $column,
                    'style="background:green"'
                );
            }
        }
        $row++;
    }

    $content = $table->toHtml();
}


echo $form->returnForm();
echo $content;

Display::display_footer();
