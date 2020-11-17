<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

require_once 'work.lib.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$work = get_work_data_by_id($id);

if (empty($work)) {
    api_not_allowed(true);
}

protectWork(api_get_course_info(), $work['parent_id']);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : null;

$work['title'] = isset($work['title']) ? Security::remove_XSS($work['title']) : '';
$work['description'] = isset($work['description']) ? Security::remove_XSS($work['description']) : '';

$htmlHeadXtra[] = '<script>'.ExerciseLib::getJsCode().'</script>';
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
];

$folderData = get_work_data_by_id($work['parent_id']);
$courseInfo = api_get_course_info();

$isCourseManager = api_is_platform_admin() || api_is_coach() || api_is_allowed_to_edit(false, false, true);

$allowEdition = false;
if ($isCourseManager) {
    $allowEdition = true;
    if (!empty($work['qualification']) && api_get_configuration_value('block_student_publication_score_edition')) {
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

$isDrhOfSession = !empty(SessionManager::getSessionFollowedByDrh(api_get_user_id(), $work['session_id']));

if ((user_is_author($id) || $isDrhOfCourse || $allowEdition || $isDrhOfSession) ||
    (
        0 == $courseInfo['show_score'] &&
        1 == $work['active'] &&
        1 == $work['accepted']
    )
) {
    if ((api_is_allowed_to_edit() || api_is_coach()) || api_is_drh()) {
        $url_dir = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?id='.$folderData['id'].'&'.api_get_cidreq();
    } else {
        $url_dir = api_get_path(WEB_CODE_PATH).'work/work_list.php?id='.$folderData['id'].'&'.api_get_cidreq();
    }

    $userInfo = api_get_user_info($work['user_id']);
    $interbreadcrumb[] = ['url' => $url_dir, 'name' => $folderData['title']];
    $interbreadcrumb[] = ['url' => '#', 'name' => $userInfo['complete_name']];
    $interbreadcrumb[] = ['url' => '#', 'name' => $work['title']];

    if ((
        0 == $courseInfo['show_score'] &&
        1 == $work['active'] &&
        1 == $work['accepted']
        ) ||
        $isCourseManager || user_is_author($id) || $isDrhOfCourse || $isDrhOfSession
    ) {
        if ($page === 'edit') {
            $url = api_get_path(WEB_CODE_PATH).'work/edit.php?id='.$folderData['id'].'&item_id='.$work['id'].'&'.api_get_cidreq();
        } else {
            $url = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$work['id'].'&'.api_get_cidreq();

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
                    $sql = "UPDATE $work_table
                            SET
                                qualificator_id = '".api_get_user_id()."',
                                qualification = '".api_float_val($_POST['qualification'])."',
                                date_of_qualification = '".api_get_utc_datetime()."'
                            WHERE c_id = ".$courseInfo['real_id']." AND id = $id";
                    Database::query($sql);
                    Display::addFlash(Display::return_message(get_lang('Updated')));

                    $resultUpload = uploadWork(
                        $folderData,
                        $courseInfo,
                        true,
                        $work
                    );
                    if ($resultUpload) {
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
                                Display::return_message(get_lang('FileUploadSucces'))
                            );
                        }
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

                Display::addFlash(Display::return_message(get_lang('DocDeleted')));
                header('Location: '.$url);
                exit;
                break;
            case 'delete_correction':
                if ($allowEdition && isset($work['url_correction']) && !empty($work['url_correction'])) {
                    deleteCorrection($courseInfo, $work);
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

        if (isset($work['contains_file']) && !empty($work['contains_file'])) {
            if (isset($work['download_url']) && !empty($work['download_url'])) {
                $actions = Display::url(
                    Display::return_icon(
                        'back.png',
                        get_lang('BackToWorksList'),
                        null,
                        ICON_SIZE_MEDIUM
                    ),
                    api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq()
                );

                // Check if file can be downloaded
                $file = getFileContents($work['id'], $courseInfo, api_get_session_id(), false);
                if (!empty($file)) {
                    $actions .= Display::url(
                        Display::return_icon(
                            'save.png',
                            get_lang('Download'),
                            null,
                            ICON_SIZE_MEDIUM
                        ),
                        $work['download_url']
                    );
                }
            }
        }

        if (isset($work['url_correction']) && !empty($work['url_correction']) && !empty($work['download_url'])) {
            $actions .= Display::url(
                Display::return_icon(
                    'check-circle.png',
                    get_lang('Correction'),
                    null,
                    ICON_SIZE_MEDIUM
                ),
                $work['download_url'].'&correction=1'
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
        $template = $tpl->get_template('work/view.tpl');
        $content = $tpl->fetch($template);
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
    } else {
        api_not_allowed(true);
    }
} else {
    api_not_allowed(true);
}
