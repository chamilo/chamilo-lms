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

$courseInfo = api_get_course_info();
protectWork($courseInfo, $work['parent_id']);

$action = $_REQUEST['action'] ?? null;
$page = $_REQUEST['page'] ?? null;

$work['title'] = isset($work['title']) ? Security::remove_XSS($work['title']) : '';
$work['description'] = isset($work['description']) ? Security::remove_XSS($work['description']) : '';

$htmlHeadXtra[] = '<script>'.ExerciseLib::getJsCode().'</script>';
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
];
if (api_get_configuration_value('allow_skill_rel_items') == true) {
    $htmlContentExtraClass[] = 'feature-item-user-skill-on';
}

$folderData = get_work_data_by_id($work['parent_id']);
$currentUserId = api_get_user_id();
$isCourseManager = api_is_platform_admin() || api_is_coach() || api_is_allowed_to_edit(false, false, true);

$allowBaseCourseTeacher = api_get_configuration_value('assignment_base_course_teacher_access_to_all_session');
if (false === $isCourseManager && $allowBaseCourseTeacher) {
    // Check if user is base course teacher.
    if (CourseManager::is_course_teacher($currentUserId, $courseInfo['code'])) {
        $isCourseManager = true;
    }
}

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
    $currentUserId,
    $courseInfo
);

$isDrhOfSession = !empty(SessionManager::getSessionFollowedByDrh($currentUserId, $work['session_id']));
if (($isDrhOfCourse || $allowEdition || $isDrhOfSession || user_is_author($id)) ||
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
        $isCourseManager || $isDrhOfCourse || $isDrhOfSession || user_is_author($id)
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
                    $qualification = isset($_POST['qualification']) ? api_float_val($_POST['qualification']) : 0;

                    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
                    $sql = "UPDATE $work_table
                            SET
                                qualificator_id = '".api_get_user_id()."',
                                qualification = '$qualification',
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
                        if (!empty($resultUpload['url'])) {
                            $title = !empty($resultUpload['filename']) ? $resultUpload['filename'] : get_lang('Untitled');
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
            case 'delete_attachment':
                deleteCommentFile(
                    $_REQUEST['comment_id'],
                    api_get_course_info()
                );

                Display::addFlash(Display::return_message(get_lang('DocDeleted')));
                header('Location: '.$url);
                exit;
            case 'delete_correction':
                if ($allowEdition && !empty($work['url_correction'])) {
                    deleteCorrection($courseInfo, $work);
                    Display::addFlash(Display::return_message(get_lang('Deleted')));
                }

                header('Location: '.$url);
                exit;
        }

        $comments = getWorkComments($work);
        $commentForm = getWorkCommentForm($work, $folderData);

        $tpl = new Template();
        $tpl->assign('work', $work);
        $tpl->assign('comments', $comments);
        $actions = '';

        if (!empty($work['contains_file'])) {
            if (!empty($work['download_url'])) {
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
                $file = getFileContents($work['id'], $courseInfo, api_get_session_id());
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

        if (!empty($work['url_correction']) && !empty($work['download_url'])) {
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
