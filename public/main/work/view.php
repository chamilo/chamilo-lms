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
$repoCorrection = Container::getStudentPublicationCorrectionRepository();
/** @var CStudentPublication|null $work */
$work = $repo->find($id);

if (null === $work) {
    api_not_allowed(true);
}

$parentId = $work->getPublicationParent()->getIid();
protectWork(api_get_course_info(), $parentId);

$action = $_REQUEST['action'] ?? null;
$page = $_REQUEST['page'] ?? null;

/*$work['title'] = isset($work['title']) ? Security::remove_XSS($work['title']) : '';
$work['description'] = isset($work['description']) ? Security::remove_XSS($work['description']) : '';*/

$htmlHeadXtra[] = '<script>'.ExerciseLib::getJsCode().'</script>';
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('Assignments'),
];

$folderData = get_work_data_by_id($parentId);
$courseInfo = api_get_course_info();
$courseEntity = api_get_course_entity();
$isCourseManager = api_is_platform_admin() || api_is_coach() || api_is_allowed_to_edit(false, false, true);

$allowEdition = false;
if ($isCourseManager) {
    $allowEdition = true;
    if (!empty($work->getQualification()) &&
        api_get_configuration_value('block_student_publication_score_edition')
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
// @todo fix $isDrhOfSession check
//$isDrhOfSession = !empty(SessionManager::getSessionFollowedByDrh(api_get_user_id(), $work['session_id']));

if (($isDrhOfCourse || $allowEdition || $isDrhOfSession || user_is_author($id)) ||
    (
        0 == $courseInfo['show_score'] &&
        1 == $work->getActive() &&
        1 == $work->getAccepted()
    )
) {
    if ((api_is_allowed_to_edit() || api_is_coach()) || api_is_drh()) {
        $url_dir = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?id='.$folderData['iid'].'&'.api_get_cidreq();
    } else {
        $url_dir = api_get_path(WEB_CODE_PATH).'work/work_list.php?id='.$folderData['iid'].'&'.api_get_cidreq();
    }

    $userInfo = api_get_user_info($work->getUser()->getId());
    $interbreadcrumb[] = ['url' => $url_dir, 'name' => $folderData['title']];
    $interbreadcrumb[] = ['url' => '#', 'name' => $userInfo['complete_name']];
    $interbreadcrumb[] = ['url' => '#', 'name' => $work->getTitle()];
    $workId = $work->getIid();
    if ((
        0 == $courseInfo['show_score'] &&
        1 == $work->getActive() &&
        1 == $work->getAccepted()
        ) ||
        $isCourseManager || $isDrhOfCourse || $isDrhOfSession || user_is_author($id)
    ) {
        if ('edit' === $page) {
            $url = api_get_path(WEB_CODE_PATH).
                'work/edit.php?id='.$folderData['iid'].'&item_id='.$workId.'&'.api_get_cidreq();
        } else {
            $url = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$workId.'&'.api_get_cidreq();

            $allowRedirect = api_get_configuration_value('allow_redirect_to_main_page_after_work_upload');
            $urlToRedirect = '';
            if ($allowRedirect) {
                $url = api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq();
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
                    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

                    if (isset($_POST['qualification'])) {
                        $work->setQualificatorId(api_get_user_id());
                        $work->setQualification(api_float_val($_POST['qualification']));
                        $work->setDateOfQualification(api_get_utc_datetime(time(), false, true));
                        $repo->update($work);
                    }

                    Display::addFlash(Display::return_message(get_lang('Updated')));

                    /*$resultUpload = uploadWork(
                        $folderData,
                        $courseEntity,
                        true,
                        $work
                    );*/

                    /*if ($resultUpload) {
                        $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
                        if (isset($resultUpload['url']) && !empty($resultUpload['url'])) {
                            $title = isset($resultUpload['filename']) && !empty($resultUpload['filename']) ? $resultUpload['filename'] : get_lang('Untitled');
                            $urlToSave = Database::escape_string($resultUpload['url']);
                            $title = Database::escape_string($title);
                            $sql = "UPDATE $work_table SET
                                        url_correction = '".$urlToSave."',
                                        title_correction = '".$title."'
                                    WHERE iid = ".$work['iid'];
                            Database::query($sql);
                            Display::addFlash(
                                Display::return_message(get_lang('The file has successfully been uploaded.'))
                            );
                        }
                    }*/

                    $request = Container::getRequest();
                    $file = $request->files->get('file');
                    if (is_array($file)) {
                        /** @var UploadedFile $file */
                        $file = $file[0];
                    }

                    if (null !== $file) {
                        $em = Database::getManager();
                        $correction = new CStudentPublicationCorrection();
                        $correction
                            ->setParent($work)
                            ->setTitle($file->getClientOriginalName());
                        // @todo improve file upload.
                        $correctionRepo = Container::getStudentPublicationCorrectionRepository();
                        $correctionRepo->create($correction);
                        $correctionRepo->addFile($correction, $file);
                        $correctionRepo->update($correction);
                    }
                }

                header('Location: '.$url);
                exit;

                break;
            case 'delete_attachment':
                deleteCommentFile(
                    $_REQUEST['comment_id'],
                    api_get_course_info()
                );

                Display::addFlash(Display::return_message(get_lang('Document deleted')));
                header('Location: '.$url);
                exit;

                break;
            case 'delete_correction':
                if ($allowEdition) {
                    deleteCorrection($work);
                    Display::addFlash(Display::return_message(get_lang('Deleted')));
                }

                header('Location: '.$url);
                exit;
                break;
        }

        $comments = getWorkComments($work);
        $commentForm = getWorkCommentForm($work, $folderData);

        $tpl = new Template();
        $tpl->assign('work', $work);
        $tpl->assign('comments', $comments);

        $actions = '';
        if ($work->getContainsFile()) {
            if ($work->getResourceNode()->hasResourceFile()) {
                $actions = Display::url(
                    Display::return_icon(
                        'back.png',
                        get_lang('Back to Assignments list'),
                        null,
                        ICON_SIZE_MEDIUM
                    ),
                    api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq()
                );
                $url = $repo->getResourceFileDownloadUrl($work).'?'.api_get_cidreq();

                $actions .= Display::url(
                    Display::return_icon(
                        'save.png',
                        get_lang('Download'),
                        null,
                        ICON_SIZE_MEDIUM
                    ),
                    $url
                );
            }
        }

        $correctionNode = $work->getCorrection();

        if (null !== $correctionNode) {
            $router = Container::getRouter();
            $url = $router->generate(
                'chamilo_core_resource_download',
                [
                    'id' => $correctionNode->getId(),
                    'tool' => 'student_publication',
                    'type' => 'student_publications_corrections',
                ]
            ).'?'.api_get_cidreq();

            $actions .= Display::url(
                Display::return_icon(
                    'check-circle.png',
                    get_lang('Correction'),
                    null,
                    ICON_SIZE_MEDIUM
                ),
                $url
            );

            if ($allowEdition) {
                $actions .= Display::url(
                    Display::return_icon(
                        'delete.png',
                        get_lang('Delete').': '.get_lang('Correction'),
                        null,
                        ICON_SIZE_MEDIUM
                    ),
                    api_get_self().'?action=delete_correction&id='.$id.'&'.api_get_cidreq()
                );
            }
        }

        if (!empty($actions)) {
            $tpl->assign(
                'actions',
                Display::toolbarAction('toolbar', [$actions])
            );
        }

        if (api_is_allowed_to_session_edit()) {
            $tpl->assign('form', $commentForm);
        }
        $tpl->assign('is_allowed_to_edit', api_is_allowed_to_edit());
        $content = $tpl->fetch('@ChamiloCore/Work/view.html.twig');
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
    } else {
        api_not_allowed(true);
    }
} else {
    api_not_allowed(true);
}
