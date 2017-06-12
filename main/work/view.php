<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

require_once 'work.lib.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$work = get_work_data_by_id($id);

if (empty($id) || empty($work)) {
    api_not_allowed(true);
}

if ($work['active'] != 1) {
    api_not_allowed(true);
}


$work['title'] = isset($work['title']) ? Security::remove_XSS($work['title']) : '';
$work['description'] = isset($work['description']) ? Security::remove_XSS($work['description']) : '';

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
);

$my_folder_data = get_work_data_by_id($work['parent_id']);
$courseInfo = api_get_course_info();

protectWork(api_get_course_info(), $work['parent_id']);

$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    api_get_user_id(),
    $courseInfo
);

if ((user_is_author($id) || $isDrhOfCourse || (api_is_allowed_to_edit() || api_is_coach())) ||
    (
        $courseInfo['show_score'] == 0 &&
        $work['active'] == 1 &&
        $work['accepted'] == 1
    )
) {
    if ((api_is_allowed_to_edit() || api_is_coach()) || api_is_drh()) {
        $url_dir = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?id='.$my_folder_data['id'].'&'.api_get_cidreq();
    } else {
        $url_dir = api_get_path(WEB_CODE_PATH).'work/work_list.php?id='.$my_folder_data['id'].'&'.api_get_cidreq();
    }

    $userInfo = api_get_user_info($work['user_id']);
    $interbreadcrumb[] = array('url' => $url_dir, 'name' => $my_folder_data['title']);
    $interbreadcrumb[] = array('url' => '#', 'name' => $userInfo['complete_name']);
    $interbreadcrumb[] = array('url' => '#', 'name' => $work['title']);

    if (($courseInfo['show_score'] == 0 &&
        $work['active'] == 1 &&
        $work['accepted'] == 1
        ) ||
        (api_is_allowed_to_edit() || api_is_coach()) ||
        user_is_author($id) ||
        $isDrhOfCourse
    ) {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : null;

        if ($page == 'edit') {
            $url = api_get_path(WEB_CODE_PATH).'work/edit.php?id='.$my_folder_data['id'].'&item_id='.$work['id'].'&'.api_get_cidreq();
        } else {
            $url = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$work['id'].'&'.api_get_cidreq();
        }

        switch ($action) {
            case 'send_comment':
                if (isset($_FILES['attachment'])) {
                    $_POST['attachment'] = $_FILES['attachment'];
                }

                addWorkComment(
                    api_get_course_info(),
                    api_get_user_id(),
                    $my_folder_data,
                    $work,
                    $_POST
                );

                if (api_is_allowed_to_edit()) {
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
                        $my_folder_data,
                        $courseInfo,
                        true,
                        $work
                    );
                    if ($resultUpload) {
                        $work_table = Database::get_course_table(
                            TABLE_STUDENT_PUBLICATION
                        );

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
                if (isset($work['url_correction']) && !empty($work['url_correction'])) {
                    if (api_is_allowed_to_edit()) {
                        deleteCorrection($courseInfo, $work);
                        Display::addFlash(
                            Display::return_message(get_lang('Deleted'))
                        );
                    }
                }

                header('Location: '.$url);
                exit;
                break;
        }

        $comments = getWorkComments($work);
        $commentForm = getWorkCommentForm($work, $my_folder_data);

        $tpl = new Template();

        $tpl->assign('work', $work);
        $tpl->assign('comments', $comments);

        if (isset($work['contains_file'])) {
            if (isset($work['download_url'])) {
                $actions = Display::url(
                    Display::return_icon(
                        'save.png',
                        get_lang('Download'),
                        null,
                        ICON_SIZE_MEDIUM
                    ),
                    $work['download_url']
                );

                if (!empty($work['url_correction'])) {
                    $actions .= Display::url(
                        Display::return_icon(
                            'check-circle.png',
                            get_lang('Correction'),
                            null,
                            ICON_SIZE_MEDIUM
                        ),
                        $work['download_url'].'&correction=1'
                    );
                    if (api_is_allowed_to_edit()) {
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

                $tpl->assign(
                    'actions',
                    Display::toolbarAction('toolbar', [$actions])
                );
            }
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
