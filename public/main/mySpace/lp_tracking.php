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
if (isset($_GET['from']) && 'myspace' === $_GET['from']) {
    $from_link = '&from=myspace';
    $this_section = SECTION_TRACKING;
} else {
    $this_section = SECTION_COURSES;
}

$session_id = isset($_REQUEST['id_session']) ? (int) $_REQUEST['id_session'] : api_get_session_id();
$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'];
$user_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : api_get_user_id();
$courseCode = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : api_get_course_id();
$origin = api_get_origin();
$lp_id = (int) $_GET['lp_id'];
$csv_content = [];
$courseInfo = api_get_course_info($courseCode);

if (empty($courseInfo) || empty($lp_id)) {
    api_not_allowed('learnpath' !== api_get_origin());
}
$courseId = $courseInfo['real_id'];
$userInfo = api_get_user_info($user_id);
$name = $userInfo['complete_name'];
$isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $user_id);

if (!$isBoss &&
    !api_is_platform_admin(true) &&
    !api_is_drh() &&
    !api_is_course_tutor() &&
    !CourseManager::isCourseTeacher(api_get_user_id(), $courseInfo['real_id']) &&
    !Tracking::is_allowed_to_coach_student(api_get_user_id(), $user_id)
) {
    api_not_allowed('learnpath' !== api_get_origin());
}

if ('user_course' === $origin) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_COURSE_PATH).$courseInfo['directory'],
        'name' => $courseInfo['name'],
    ];
    $interbreadcrumb[] = [
        'url' => "../user/user.php?cid=$courseId",
        'name' => get_lang('Users'),
    ];
} elseif ('tracking_course' === $origin) {
    $interbreadcrumb[] = [
        'url' => "../tracking/courseLog.php?cid=$courseId&sid=$session_id",
        'name' => get_lang('Reporting'),
    ];
} else {
    $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Reporting')];
    $interbreadcrumb[] = ['url' => 'student.php', 'name' => get_lang('My learners')];
    $interbreadcrumb[] = ['url' => "myStudents.php?student=$user_id", 'name' => get_lang('Learner details')];
    $nameTools = get_lang('Learner details in course');
}

$interbreadcrumb[] = [
    'url' => "myStudents.php?student=$user_id&course=$courseCode&details=true&origin=$origin",
    'name' => get_lang('Learner details in course'),
];
$nameTools = get_lang('Learnpath details');

$lpRepo = \Chamilo\CoreBundle\Framework\Container::getLpRepository();
/** @var \Chamilo\CourseBundle\Entity\CLp $lp */
$lp = $lpRepo->find($lp_id);
$lp_title = $lp->getName();
$origin = 'tracking';
$action = $_REQUEST['action'] ?? '';
switch ($action) {
    case 'export_stats':
        if (!api_is_allowed_to_edit(false, false, true)) {
            api_not_allowed();
        }
        $tpl = new Template(null, false, false);
        $itemId = isset($_REQUEST['extend_id']) ? $_REQUEST['extend_id'] : 0;
        $itemViewId = isset($_REQUEST['extend_attempt_id']) ? $_REQUEST['extend_attempt_id'] : 0;
        $em = Database::getManager();

        $repo = $em->getRepository(CLpItemView::class);
        /** @var CLpItemView $itemView */
        $itemView = $repo->find($itemViewId);

        if (!$itemView) {
            api_not_allowed();
        }

        $view = $em->getRepository(\Chamilo\CourseBundle\Entity\CLpView::class)->find($itemView->getLpViewId());
        $lp = $em->getRepository(\Chamilo\CourseBundle\Entity\CLp::class)->find($view->getLpId());

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
            if (1 === $counter) {
                continue;
            } elseif (2 === $counter) {
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
                if ('correct' === $option['result']) {
                    $total++;
                    $globalTotal++;
                }
                $table->setCellContents($row, 0, 'Q'.$choiceCounter);
                $table->setCellContents($row, 1, $option['student_response_formatted']);
                $result = Display::return_icon('icon_check.png', null, [], ICON_SIZE_SMALL);
                if ('wrong' === $option['result']) {
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

        $addLogo = (isset($_GET['add_logo']) && 1 === (int) $_GET['add_logo']);
        if ($addLogo) {
            $secondLogo = api_get_path(SYS_PATH).'custompages/url-images/'.api_get_current_access_url_id().'_url_image_2.png';
            $logo2 = Display::img($secondLogo, null, ['style' => 'height:70px;']);
            $table->setCellContents(0, 1, $logo2);
        }

        $table->setCellAttributes(0, 1, ['style' => 'display:block;float:right;text-align:right']);
        $pdf->set_custom_header($table->toHtml());

        $background = api_get_path(SYS_PATH).'custompages/url-images/'.api_get_current_access_url_id().'_pdf_background.png';
        $content = '<html><body style="background-image-resize: 5; background-position: top left; background-image: url('.$background.');">'.$content.'</body></html>';

        $pdf->content_to_pdf(
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
    Display::return_icon('export_csv.png', get_lang('CSV export'), '', ICON_SIZE_MEDIUM),
    api_get_self().'?export=csv&'.Security::remove_XSS($_SERVER['QUERY_STRING'])
);

Display::display_header($nameTools);
echo Display::toolbarAction('actions', [implode(PHP_EOL, $actions)]);
$table_title = $session_id
    ? Display::return_icon('session.png', get_lang('Session')).PHP_EOL.api_get_session_name($session_id).PHP_EOL
    : PHP_EOL;
$table_title .= Display::return_icon('course.png', get_lang('Course')).PHP_EOL.$courseInfo['name'].PHP_EOL
    .Display::return_icon('user.png', get_lang('User')).' '.$name;

echo Display::page_header($table_title);
echo Display::page_subheader(
    Display::return_icon('learnpath.png', get_lang('Learning path')).PHP_EOL.$lp_title
);
echo $output;
Display::display_footer();
