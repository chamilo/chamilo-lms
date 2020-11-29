<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CourseBundle\Entity\CLpItemView;

/**
 * Learning paths reporting.
 */
require_once __DIR__.'/../inc/global.inc.php';

$cidReset = true;
$from_myspace = false;
$from_link = '';
if (isset($_GET['from']) && 'myspace' == $_GET['from']) {
    $from_link = '&from=myspace';
    $this_section = SECTION_TRACKING;
} else {
    $this_section = SECTION_COURSES;
}

$session_id = isset($_REQUEST['id_session']) ? (int) $_REQUEST['id_session'] : api_get_session_id();
$export_csv = isset($_GET['export']) && 'csv' == $_GET['export'];
$user_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : api_get_user_id();
$courseCode = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : api_get_course_id();
$origin = api_get_origin();
$lp_id = (int) $_GET['lp_id'];
$csv_content = [];
$courseInfo = api_get_course_info($courseCode);

if (empty($courseInfo) || empty($lp_id)) {
    api_not_allowed('learnpath' !== api_get_origin());
}
$userInfo = api_get_user_info($user_id);
$name = $userInfo['complete_name'];
$isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $user_id);

if (!$isBoss &&
    !api_is_platform_admin(true) &&
    !api_is_drh() &&
    !api_is_course_tutor() &&
    !CourseManager::is_course_teacher(api_get_user_id(), $courseCode) &&
    !Tracking::is_allowed_to_coach_student(api_get_user_id(), $user_id)
) {
    api_not_allowed(api_get_origin() !== 'learnpath');
}

if ('user_course' === $origin) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_COURSE_PATH).$courseInfo['directory'],
        'name' => $courseInfo['name'],
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
        WHERE c_id = '.$courseInfo['real_id'].' AND id='.$lp_id;
$rs = Database::query($sql);
$lp_title = Database::result($rs, 0, 0);

$origin = 'tracking';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
switch ($action) {
    case 'export_stats':
        if (!api_is_allowed_to_edit(false, false, true)) {
            api_not_allowed();
        }
        $tpl = new Template(null, false, false);
        $itemId = isset($_REQUEST['extend_id']) ? $_REQUEST['extend_id'] : 0;
        $itemViewId = isset($_REQUEST['extend_attempt_id']) ? $_REQUEST['extend_attempt_id'] : 0;
        $em = Database::getManager();

        $repo = $em->getRepository('ChamiloCourseBundle:CLpItemView');
        /** @var CLpItemView $itemView */
        $itemView = $repo->find($itemViewId);

        if (!$itemView) {
            api_not_allowed();
        }

        $view = $em->getRepository('ChamiloCourseBundle:CLpView')->find($itemView->getLpViewId());
        $lp = $em->getRepository('ChamiloCourseBundle:CLp')->find($view->getLpId());

        $duration = learnpathItem::getScormTimeFromParameter('js', $itemView->getTotalTime());
        $endTime = $itemView->getStartTime() + $itemView->getTotalTime();

        $list1 = learnpath::get_iv_interactions_array($itemViewId, $courseInfo['real_id']);
        $counter = 0;
        $table = new HTML_Table();

        $total = 0;
        $numberChoices = 0;
        $questionCounter = 0;

        $studentName = '';
        $questions = [];
        $categories = [];
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
                    case 'matching':
                        $list = explode(',', $data);
                        if (!empty($list)) {
                            foreach ($list as &$item) {
                                $item = cut($item, 30);
                            }
                            $interaction['student_response_formatted'] = implode('<br />', $list);
                        }
                        $questions[$questionCounter]['options'][] = $interaction;
                        $numberChoices++;
                        break;
                }
            }
        }

        $counter = 1;
        $table = new HTML_Table(['class' => 'table table-hover table-striped  data_table']);
        $row = 0;
        $scoreDisplay = new ScoreDisplay();
        $globalTotal = 0;
        $globalTotalCount = 0;
        foreach ($questions as $data) {
            // Question title
            $table->setCellContents($row, 0, $data['question']);
            $table->setCellAttributes($row, 0, ['colspan' => '3', 'style' => 'text-align:center; font-weight:bold']);
            $choiceCounter = 1;
            $row++;
            $total = 0;
            // Question options
            foreach ($data['options'] as $option) {
                if ($option['result'] === 'correct') {
                    $total++;
                    $globalTotal++;
                }
                $table->setCellContents($row, 0, 'Q'.$choiceCounter);
                $table->setCellContents($row, 1, $option['student_response_formatted']);
                $result = Display::return_icon('icon_check.png', null, [], ICON_SIZE_SMALL);
                if ($option['result'] === 'wrong') {
                    $result = Display::return_icon('icon_error.png', null, [], ICON_SIZE_SMALL);
                }

                $table->setCellContents($row, 2, $result);
                $choiceCounter++;
                $row++;
            }

            // Question total
            $table->setCellContents($row, 0, get_lang('Total'));
            $table->setCellContents($row, 1, $data['question']);

            $totalOptions = count($data['options']);
            $arrayScore = [0 => $total, 1 => $totalOptions];
            $scoreToString = $scoreDisplay->display_score($arrayScore);
            $table->setCellContents($row, 2, $scoreToString);

            $table->setCellAttributes($row, 0, ['style' => 'font-weight:bold']);
            $table->setCellAttributes($row, 1, ['style' => 'font-weight:bold']);
            $table->setCellAttributes($row, 2, ['style' => 'font-weight:bold']);

            $categories[] = [
                'name' => $data['question'],
                'score' => $scoreDisplay->display_score($arrayScore, SCORE_DIV),
                'score_numeric' => $scoreDisplay->display_score($arrayScore, SCORE_NUMERIC),
                'score_percentage' => $scoreDisplay->display_score($arrayScore, SCORE_PERCENT),
            ];
            $tpl->assign('categories', $categories);

            $globalTotalCount += $totalOptions;
            $row++;
        }

        $globalScoreTotal = [0 => $globalTotal, 1 => $globalTotalCount];
        $score = $scoreDisplay->display_score($globalScoreTotal);
        $generalScore[] = [
            'score' => $scoreDisplay->display_score($globalScoreTotal, SCORE_DIV),
            'score_numeric' => $scoreDisplay->display_score($globalScoreTotal, SCORE_NUMERIC),
            'score_percentage' => $scoreDisplay->display_score($globalScoreTotal, SCORE_PERCENT),
        ];
        $tpl->assign('general_score', $generalScore);
        $tpl->assign('global_total', $score);

        $tableToString = $table->toHtml();

        $duration = learnpathItem::getScormTimeFromParameter('js', $itemView->getTotalTime());

        $dataLpInfo = [
            'name' => $lp->getName(),
            'attempt' => $itemView->getViewCount(),
            'score' => $score,
            'duration' => $duration,
            'start_time' => api_get_local_time($itemView->getStartTime()),
            'start_date' => api_get_local_time($itemView->getStartTime(), null, null, null, false),
            'end_time' => api_get_local_time($endTime),
            'candidate' => $studentName,
        ];

        $tpl->assign('data', $dataLpInfo);
        $contentText = $tpl->fetch($tpl->get_template('my_space/pdf_tracking_lp.tpl'));

        $content = $contentText.'<pagebreak>'.$tableToString;

        $pdf = new PDF('A4', 'P', ['margin_footer' => 4, 'top' => 40, 'bottom' => 25]);

        $table = new HTML_Table(['class' => 'table', 'style' => 'display: block; margin-bottom: 50px;']);
        $logo = ChamiloApi::getPlatformLogo(
            api_get_visual_theme(),
            [
                'title' => '',
                'style' => 'max-width:180px, margin-bottom: 100px;',
                'id' => 'header-logo',
            ]
        );
        $table->setCellContents(0, 0, $logo);

        $addLogo = (isset($_GET['add_logo']) && (int) $_GET['add_logo'] === 1);
        if ($addLogo) {
            $secondLogo = api_get_path(SYS_PATH).'custompages/url-images/'.api_get_current_access_url_id().'_url_image_2.png';
            $logo2 = Display::img($secondLogo, null, ['style' => 'height:70px;']);
            $table->setCellContents(0, 1, $logo2);
        }

        $table->setCellAttributes(0, 1, ['style' => 'display:block;float:right;text-align:right']);
        $pdf->set_custom_header($table->toHtml());

        $background = api_get_path(SYS_PATH).'custompages/url-images/'.api_get_current_access_url_id().'_pdf_background.png';
        $content = '<html><body style="background-image-resize: 5; background-position: top left; background-image: url('.$background.');">'.$content.'</body></html>';

        @$pdf->content_to_pdf(
            $content,
            null,
            $courseInfo['code'].'_'.$lp->getName().'_'.api_get_local_time(),
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
$table_title .= Display::return_icon('course.png', get_lang('Course')).PHP_EOL.$courseInfo['name'].PHP_EOL
    .Display::return_icon('user.png', get_lang('User')).' '.$name;

echo Display::page_header($table_title);
echo Display::page_subheader(
    Display::return_icon('learnpath.png', get_lang('ToolLearnpath')).PHP_EOL.$lp_title
);
echo $output;
Display::display_footer();
