<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationCorrection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_STUDENTPUBLICATION;

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$repo = Container::getStudentPublicationRepository();

/** @var CStudentPublication|null $work */
$work = $repo->find($id);

if (null === $work) {
    api_not_allowed(true);
}

$parent = $work->getPublicationParent();

if (null === $parent) {
    api_not_allowed(true);
}

$parentId = $parent->getIid();

protectWork(api_get_course_info(), $parentId);

$action = $_REQUEST['action'] ?? null;
$page = $_REQUEST['page'] ?? null;

$htmlHeadXtra[] = '<script>'.ExerciseLib::getJsCode().'</script>';

$htmlHeadXtra[] = <<<HTML
<style>
    .work-view-page .work-review-form input[type="number"],
    .work-view-page .work-review-form input[name="qualification"] {
        max-width: 10rem;
    }

    .work-view-page .work-review-form input[type="file"] {
        max-width: 100%;
    }

    .work-view-page .work-review-form .form-group {
        margin-bottom: 1rem;
    }

    .work-view-page .work-review-form .tox-tinymce {
        border-radius: 0.75rem;
        overflow: hidden;
    }

    .work-view-page .work-review-form button,
    .work-view-page .work-review-form input[type="submit"] {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        min-height: 2.75rem;
        border-radius: 0.5rem;
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .work-view-page .work-review-form label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #4b5563;
    }

    .work-view-page .work-review-form input[type="text"],
    .work-view-page .work-review-form input[type="number"],
    .work-view-page .work-review-form input[type="file"],
    .work-view-page .work-review-form textarea,
    .work-view-page .work-review-form select {
        border-radius: 0.5rem;
    }
</style>
HTML;

if (!function_exists('work_view_get_origin_query')) {
    function work_view_get_origin_query(): string
    {
        $origin = (string) ($_REQUEST['origin'] ?? '');

        if ('pending' !== $origin) {
            return '';
        }

        $params = [
            'origin' => 'pending',
        ];

        $pendingCourseId = (int) ($_REQUEST['pending_course'] ?? 0);
        if ($pendingCourseId > 0) {
            $params['pending_course'] = $pendingCourseId;
        }

        $pendingStatus = (int) ($_REQUEST['pending_status'] ?? 0);
        if ($pendingStatus > 0) {
            $params['pending_status'] = $pendingStatus;
        }

        return '&'.http_build_query($params);
    }
}

if (!function_exists('work_view_get_back_url')) {
    function work_view_get_back_url(string $defaultUrl): string
    {
        $origin = (string) ($_REQUEST['origin'] ?? '');

        if ('pending' !== $origin) {
            return $defaultUrl;
        }

        $params = [];

        $pendingCourseId = (int) ($_REQUEST['pending_course'] ?? 0);
        if ($pendingCourseId > 0) {
            $params['course'] = $pendingCourseId;
        }

        $pendingStatus = (int) ($_REQUEST['pending_status'] ?? 0);
        if ($pendingStatus > 0) {
            $params['status'] = $pendingStatus;
        }

        $url = api_get_path(WEB_CODE_PATH).'work/pending.php';

        if (!empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $url;
    }
}
$folderData = get_work_data_by_id($parentId);
$courseInfo = api_get_course_info();
$isCourseManager = api_is_platform_admin() || api_is_coach() || api_is_allowed_to_edit(false, false, true);

$allowEdition = false;

if ($isCourseManager) {
    $allowEdition = true;

    if (
        !empty($work->getQualification()) &&
        'true' === api_get_setting('work.block_student_publication_score_edition')
    ) {
        $allowEdition = false;
    }
}

if (api_is_platform_admin()) {
    $allowEdition = true;
}

$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    api_get_user_id(),
    $courseInfo
);

$isDrhOfSession = false;

$canViewBecauseScoreIsVisible = (
    0 == $courseInfo['show_score'] &&
    1 == $work->getActive() &&
    1 == $work->getAccepted()
);

$canViewWork = $isDrhOfCourse || $allowEdition || $isDrhOfSession || user_is_author($id) || $canViewBecauseScoreIsVisible;

if (!$canViewWork) {
    api_not_allowed(true);
}

if ((api_is_allowed_to_edit() || api_is_coach()) || api_is_drh()) {
    $urlDir = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?id='.$folderData['iid'].'&'.api_get_cidreq();
} else {
    $urlDir = api_get_path(WEB_CODE_PATH).'work/work_list.php?id='.$folderData['iid'].'&'.api_get_cidreq();
}

$user = $work->getUser();
$userInfo = null !== $user ? api_get_user_info($user->getId()) : [];

$interbreadcrumb[] = [
    'url' => '#',
    'name' => $userInfo['complete_name'] ?? '',
];
$interbreadcrumb[] = [
    'url' => '#',
    'name' => $work->getTitle(),
];

$workId = $work->getIid();

$originQuery = work_view_get_origin_query();

if ('edit' === $page) {
    $redirectUrl = api_get_path(WEB_CODE_PATH).
        'work/edit.php?id='.$folderData['iid'].'&item_id='.$workId.'&'.api_get_cidreq().$originQuery;
} else {
    $redirectUrl = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$workId.'&'.api_get_cidreq().$originQuery;

    if ('true' === api_get_setting('work.allow_redirect_to_main_page_after_work_upload') && '' === $originQuery) {
        $redirectUrl = api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq();
    }
}

switch ($action) {
    case 'send_comment':
        if (isset($_FILES['attachment'])) {
            $_POST['attachment'] = $_FILES['attachment'];
        }

        addWorkComment(
            api_get_course_info(),
            api_get_user_id(),
            $folderData,
            $work,
            $_POST
        );

        if ($allowEdition) {
            if (isset($_POST['qualification'])) {
                $work->setQualificatorId(api_get_user_id());
                $work->setQualification(api_float_val($_POST['qualification']));
                $work->setDateOfQualification(api_get_utc_datetime(time(), false, true));
                $repo->update($work);
            }

            Display::addFlash(Display::return_message(get_lang('Updated')));

            $request = Container::getRequest();
            $file = $request->files->get('file');

            if (is_array($file)) {
                /** @var UploadedFile $file */
                $file = $file[0];
            }

            if (null !== $file) {
                $correction = (new CStudentPublicationCorrection())
                    ->setParent($work)
                    ->setTitle($file->getClientOriginalName())
                ;

                $correctionRepo = Container::getStudentPublicationCorrectionRepository();
                $correctionRepo->create($correction);
                $correctionRepo->addFile($correction, $file);
                $correctionRepo->update($correction);
            }
        }

        header('Location: '.$redirectUrl);
        exit;

    case 'delete_attachment':
        deleteCommentFile(
            (int) ($_REQUEST['comment_id'] ?? 0),
            api_get_course_info()
        );

        Display::addFlash(Display::return_message(get_lang('Document deleted')));
        header('Location: '.$redirectUrl);
        exit;

    case 'delete_correction':
        if ($allowEdition) {
            deleteCorrection($work);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }

        header('Location: '.$redirectUrl);
        exit;
}

$comments = getWorkComments($work);
$commentForm = getWorkCommentForm($work, $folderData);

$backUrl = work_view_get_back_url($urlDir);
$downloadUrl = '';
$correctionUrl = '';
$deleteCorrectionUrl = '';

if ($work->getContainsFile() && $work->getResourceNode()->hasResourceFile()) {
    $downloadUrl = $repo->getResourceFileDownloadUrl($work).'?'.api_get_cidreq();
}

$correctionNode = $work->getCorrection();

if (null !== $correctionNode) {
    $router = Container::getRouter();

    $correctionUrl = $router->generate(
            'chamilo_core_resource_download',
            [
                'id' => $correctionNode->getId(),
                'tool' => 'student_publication',
                'type' => 'student_publications_corrections',
            ]
        ).'?'.api_get_cidreq();

    if ($allowEdition) {
        $deleteCorrectionUrl = api_get_self().'?action=delete_correction&id='.$id.'&'.api_get_cidreq().work_view_get_origin_query();
    }
}

$sentDate = $work->getSentDate();
$sentDateLabel = '';

if ($sentDate instanceof DateTimeInterface) {
    $sentDateLabel = api_convert_and_format_date($sentDate->format('Y-m-d H:i:s'));
} elseif (!empty($sentDate)) {
    $sentDateLabel = api_convert_and_format_date((string) $sentDate);
}

$score = $work->getQualification();
$scoreLabel = null !== $score && '' !== (string) $score ? (string) $score : '-';
$maximumScore = $folderData['qualification'] ?? null;

$isReviewed = !empty($work->getQualificatorId());

$tpl = new Template();
$tpl->assign('work', $work);
$tpl->assign('comments', $comments);
$tpl->assign('form', ($allowEdition || api_is_allowed_to_session_edit()) ? $commentForm : '');
$tpl->assign('folder_data', $folderData);
$tpl->assign('student_name', $userInfo['complete_name'] ?? '');
$tpl->assign('back_url', $backUrl);
$tpl->assign('download_url', $downloadUrl);
$tpl->assign('correction_url', $correctionUrl);
$tpl->assign('delete_correction_url', $deleteCorrectionUrl);
$tpl->assign('allow_edition', $allowEdition);
$tpl->assign('is_allowed_to_edit', api_is_allowed_to_edit());
$tpl->assign('sent_date_label', $sentDateLabel);
$tpl->assign('score_label', $scoreLabel);
$tpl->assign('maximum_score', $maximumScore);
$tpl->assign('is_reviewed', $isReviewed);

$content = $tpl->fetch('@ChamiloCore/Work/view.html.twig');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
