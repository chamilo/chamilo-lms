<?php
/* For licensing terms, see /license.txt */

/**
 * Learning paths reporting.
 *
 * @package chamilo.reporting
 */
require_once __DIR__.'/../inc/global.inc.php';

$cidReset = true;
$from_myspace = false;
$from_link = '';
if (isset($_GET['from']) && $_GET['from'] == 'myspace') {
    $from_link = '&from=myspace';
    $this_section = SECTION_TRACKING;
} else {
    $this_section = SECTION_COURSES;
}

$session_id = isset($_REQUEST['id_session']) && !empty($_REQUEST['id_session'])
    ? intval($_REQUEST['id_session'])
    : api_get_session_id();
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$user_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : api_get_user_id();
$courseCode = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : api_get_course_id();
$origin = api_get_origin();
$lp_id = (int) $_GET['lp_id'];
$csv_content = [];
$course_info = api_get_course_info($courseCode);

if (empty($course_info) || empty($lp_id)) {
    api_not_allowed(api_get_origin() !== 'learnpath');
}
$userInfo = api_get_user_info($user_id);
$name = $userInfo['complete_name'];
$isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $user_id);

if (!api_is_platform_admin(true) &&
    !CourseManager::is_course_teacher(api_get_user_id(), $courseCode) &&
    !$isBoss &&
    !Tracking::is_allowed_to_coach_student(api_get_user_id(), $user_id) &&
    !api_is_drh() &&
    !api_is_course_tutor()
) {
    api_not_allowed(api_get_origin() !== 'learnpath');
}

if ($origin === 'user_course') {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_COURSE_PATH).$course_info['directory'],
        'name' => $course_info['name'],
    ];
    $interbreadcrumb[] = [
        'url' => "../user/user.php?cidReq=$courseCode",
        'name' => get_lang('Users'),
    ];
} elseif ($origin === 'tracking_course') {
    $interbreadcrumb[] = [
        'url' => "../tracking/courseLog.php?cidReq=$courseCode&id_session=$session_id",
        'name' => get_lang('Tracking'),
    ];
} else {
    $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('MySpace')];
    $interbreadcrumb[] = ['url' => 'student.php', 'name' => get_lang('MyStudents')];
    $interbreadcrumb[] = ['url' => "myStudents.php?student=$user_id", 'name' => get_lang('StudentDetails')];
    $nameTools = get_lang('DetailsStudentInCourse');
}

$interbreadcrumb[] = [
    'url' => "myStudents.php?student=$user_id&course=$courseCode&details=true&origin=$origin",
    'name' => get_lang('DetailsStudentInCourse'),
];
$nameTools = get_lang('LearningPathDetails');
$sql = 'SELECT name	FROM '.Database::get_course_table(TABLE_LP_MAIN).' 
        WHERE c_id = '.$course_info['real_id'].' AND id='.$lp_id;
$rs = Database::query($sql);
$lp_title = Database::result($rs, 0, 0);

$origin = 'tracking';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
switch ($action) {
    case 'export_stats':
        $itemId = isset($_REQUEST['extend_id']) ? $_REQUEST['extend_id'] : 0;
        $itemViewId = isset($_REQUEST['extend_attempt_id']) ? $_REQUEST['extend_attempt_id'] : 0;
        $em = Database::getManager();

        $repo = $em->getRepository('ChamiloCourseBundle:CLpItemView');
        /** @var \Chamilo\CourseBundle\Entity\CLpItemView $itemView */
        $itemView = $repo->find($itemViewId);

        if (!$itemView) {
            api_not_allowed();
        }

        $view = $em->getRepository('ChamiloCourseBundle:CLpView')->find($itemView->getLpViewId());
        $lp = $em->getRepository('ChamiloCourseBundle:CLp')->find($view->getLpId());

        $duration = learnpathItem::getScormTimeFromParameter('js', $itemView->getTotalTime());
        $endTime = $itemView->getStartTime() + $itemView->getTotalTime();

        $list1 = learnpath::get_iv_interactions_array($itemViewId);
        $counter = 0;
        $table = new HTML_Table();

        $total = 0;
        $numberChoices = 0;
        $questionCounter = 0;

        $studentName = '';
        $questions = [];
        foreach ($list1 as $id => $interaction) {
            $counter++;
            if ($counter === 1) {
                continue;
            } elseif ($counter === 2) {
                $studentName = $interaction['student_response_formatted'];
            } else {
                $data = $interaction['student_response_formatted'];

                switch ($interaction['type']) {
                    case 'fill-in':
                        $questionCounter++;
                        $questions[$questionCounter]['question'] = $data;
                        break;
                    case 'choice':
                        $questions[$questionCounter]['options'][] = $interaction;
                        $numberChoices++;
                        break;
                }
            }
        }

        $counter = 1;
        $table = new HTML_Table(['class' => 'table data_table']);
        $row = 0;
        $scoreDisplay = new ScoreDisplay();
        $globalTotal = 0;
        $globalTotalCount = 0;
        foreach ($questions as $data) {
            // Question title
            $table->setCellContents($row, 0, '<b>'.$data['question'].'</b>');
            $table->setCellAttributes($row, 0, ['colspan' => '3', 'style' => 'text-align:center;']);
            $choiceCounter = 1;
            $row++;
            $total = 0;
            // Question options
            foreach ($data['options'] as $option) {
                if ($option['result'] === 'correct') {
                    $total++;
                    $globalTotal++;
                }
                $table->setCellContents($row, 0, $choiceCounter);
                $table->setCellContents($row, 1, $option['student_response_formatted']);
                $table->setCellContents($row, 2, $option['result']);
                $choiceCounter++;
                $row++;
            }
            // Question total
            $table->setCellContents($row, 0, get_lang('Total'));
            $table->setCellContents($row, 1, $data['question']);
            $totalOptions = count($data['options']);
            $score = $scoreDisplay->display_score([0 => $total, 1 => $totalOptions]);
            $table->setCellContents($row, 2, $score);
            $globalTotalCount += $totalOptions;

            $row++;
        }

        $score = $scoreDisplay->display_score([0 => $globalTotal, 1 => $globalTotalCount]);
        $table->setCellContents($row, 0, get_lang('GlobalTotal'));
        $table->setCellContents($row, 1, '');
        $table->setCellContents($row, 2, $score);

        $tableToString = $table->toHtml();

        $duration = learnpathItem::getScormTimeFromParameter('js', $itemView->getTotalTime());

        $table = new HTML_Table(['class' => 'table']);
        $data = [
            get_lang('Name') => $lp->getName(),
            get_lang('Attempt') => $itemView->getViewCount(),
            get_lang('Score') => $score,
            get_lang('Duration') => $duration,
            get_lang('StartTime') => api_get_local_time($itemView->getStartTime()),
            get_lang('EndTime') => api_get_local_time($endTime),
            get_lang('Student') => $studentName,
        ];
        $row = 0;
        foreach ($data as $key => $value) {
            $table->setCellContents($row, 0, $key);
            $table->setCellContents($row, 1, $value);
            $row++;
        }

        $headerTableToString = $table->toHtml();

        $content = $headerTableToString.$tableToString;

        $pdf = new PDF();
        $pdf->content_to_pdf(
            $content,
            null,
            $courseInfo['code'],
            $courseInfo['code'],
            'D',
            false,
            null,
            false,
            true
        );
        break;
}

$output = require_once api_get_path(SYS_CODE_PATH).'lp/lp_stats.php';

$actions = [];
$actions[] = Display::url(
    Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
    'javascript:history.back();'
);
$actions[] = Display::url(
    Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM),
    'javascript:window.print();'
);
$actions[] = Display::url(
    Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM),
    api_get_self().'?export=csv&'.Security::remove_XSS($_SERVER['QUERY_STRING'])
);

Display::display_header($nameTools);
echo Display::toolbarAction(
    'actions',
    [implode(PHP_EOL, $actions)]
);

$table_title = $session_id
    ? Display::return_icon('session.png', get_lang('Session')).PHP_EOL.api_get_session_name($session_id).PHP_EOL
    : PHP_EOL;
$table_title .= Display::return_icon('course.png', get_lang('Course')).PHP_EOL.$course_info['name'].PHP_EOL
    .Display::return_icon('user.png', get_lang('User')).' '.$name;

echo Display::page_header($table_title);
echo Display::page_subheader(
    Display::return_icon('learnpath.png', get_lang('ToolLearnpath')).PHP_EOL.$lp_title
);
echo $output;
Display::display_footer();
