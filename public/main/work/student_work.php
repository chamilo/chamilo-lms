<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CStudentPublication;

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);
api_protect_course_group(GroupManager::GROUP_TOOL_WORK);

$this_section = SECTION_COURSES;
$studentId = isset($_GET['studentId']) ? (int) ($_GET['studentId']) : null;

if (empty($studentId)) {
    api_not_allowed(true);
}

$tool_name = get_lang('Assignments');
$group_id = api_get_group_id();
$user = api_get_user_entity($studentId);
$completeName = UserManager::formatUserFullName($user);
$courseInfo = api_get_course_info();
$course = api_get_course_entity();
$session = api_get_session_entity();

if (null === $user || null === $course) {
    api_not_allowed(true);
}

// Only a teachers page.
if (!empty($group_id)) {
    $group = api_get_group_entity($group_id);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('Group area').' '.$group->getName(),
    ];
} else {
    if (!(api_is_allowed_to_edit() || api_is_coach())) {
        api_not_allowed(true);
    }
}

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'export_to_pdf':
        exportAllWork($user, $course, 'pdf');
        exit;

        break;
    case 'download':
        if (api_is_allowed_to_edit()) {
            downloadAllFilesPerUser($studentId, $courseInfo);
        }

        break;
    case 'delete_all':
        if (api_is_allowed_to_edit()) {
            $deletedItems = deleteAllWorkPerUser($user, $course);
            if (!empty($deletedItems)) {
                $message = get_lang('File deleted').'<br >';
                foreach ($deletedItems as $item) {
                    $message .= $item['title'].'<br />';
                }
                $message = Display::return_message($message, 'info', false);
                Display::addFlash($message);
            }
            header('Location: '.api_get_self().'?studentId='.$studentId.'&'.api_get_cidreq());
            exit;
        }

        break;
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('Assignments'),
];
$interbreadcrumb[] = [
    'url' => '#',
    'name' => $completeName,
];

Display::display_header(null);

$actions = '<a href="'.api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('Back to Assignments list'), '', ICON_SIZE_MEDIUM).
    '</a>';

if (api_is_allowed_to_edit()) {
    $actions .= '<a
        href="'.api_get_path(WEB_CODE_PATH).'work/student_work.php?action=export_to_pdf&studentId='.$studentId.'&'.api_get_cidreq().'">'.
        Display::return_icon('pdf.png', get_lang('Export to PDF'), '', ICON_SIZE_MEDIUM).
        '</a>';

    $actions .= '<a
        href="'.api_get_path(WEB_CODE_PATH).'work/student_work.php?action=download&studentId='.$studentId.'&'.api_get_cidreq().'">'.
        Display::return_icon('save.png', get_lang('Download'), '', ICON_SIZE_MEDIUM).
        '</a>';

    $actions .= '<a
            onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to delete').'\')) return false;"
            href="'.api_get_path(WEB_CODE_PATH).'work/student_work.php?action=delete_all&studentId='.$studentId.'&'.api_get_cidreq().'">'.
        Display::return_icon('delete.png', get_lang('Delete all papers'), '', ICON_SIZE_MEDIUM).
        '</a>';
}
echo Display::toolbarAction('toolbar', [$actions]);

$table = new HTML_Table(['class' => 'data_table']);
$column = 0;
$row = 0;
$headers = [
    get_lang('Title'),
    get_lang('Time of reception'),
    get_lang('Deadline'),
    get_lang('Feedback'),
    get_lang('Detail'),
];
foreach ($headers as $header) {
    $table->setHeaderContents($row, $column, $header);
    $column++;
}
$row++;
$column = 0;
$url = api_get_path(WEB_CODE_PATH).'work/';

$repo = Container::getStudentPublicationRepository();
$works = $repo->getStudentPublicationByUser($user, $course, $session);
//$userWorks = getWorkPerUser($studentId, $courseId, $sessionId);
foreach ($works as $workData) {
    /** @var CStudentPublication $work */
    $work = $workData['work'];
    /** @var CStudentPublication[] $results */
    $results = $workData['results'];

    $scoreWeight = 0 == (int) $work->getQualification() ? null : $work->getQualification();
    $workId = $work->getIid();

    foreach ($results as $userResult) {
        $itemId = $userResult->getIid();
        $table->setCellContents($row, $column, $work->getTitle().' ['.trim(strip_tags($userResult->getTitle())).']');
        $table->setCellAttributes($row, $column, ['width' => '300px']);
        $column++;
        $table->setCellContents($row, $column, api_get_local_time($userResult->getSentDate()));
        $column++;
        $assignment = $work->getAssignment();
        $dateQualification = !empty($assignment->getExpiresOn()) ? api_get_local_time($assignment->getExpiresOn()) : '-';
        $table->setCellContents($row, $column, $dateQualification);
        $column++;
        $score = $userResult->getQualification();
        $table->setCellContents($row, $column, $score);
        $column++;

        // Detail
        $links = null;
        // is a text
        $url = api_get_path(WEB_CODE_PATH).'work/view.php?'.api_get_cidreq().'&id='.$itemId;
        $links .= Display::url(Display::return_icon('default.png', get_lang('View')), $url);
        if ($userResult->getResourceNode()->hasResourceFile()) {
            $url = $repo->getResourceFileDownloadUrl($userResult).'?'.api_get_cidreq();
            $links .= Display::url(Display::return_icon('save.png', get_lang('Download')), $url);
        }

        if (api_is_allowed_to_edit()) {
            $url = api_get_path(WEB_CODE_PATH).
                'work/edit.php?'.api_get_cidreq().'&item_id='.$itemId.'&id='.$workId.'&parent_id='.$workId;
            $links .= Display::url(
                Display::return_icon('edit.png', get_lang('Comment')),
                $url
            );
        }
        $table->setCellContents($row, $column, $links);
        $row++;
        $column = 0;
    }
}
echo Display::page_subheader($completeName);
echo $table->toHtml();

Display::display_footer();
