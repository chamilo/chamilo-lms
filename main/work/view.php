<?php
/* For licensing terms, see /license.txt */

$language_file = array('exercice', 'work', 'document', 'admin');

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

require_once 'work.lib.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$work = get_work_data_by_id($id);

if (empty($id) || empty($work)) {
    api_not_allowed();
}

$interbreadcrumb[] = array ('url' => 'work.php', 'name' => get_lang('StudentPublications'));

$my_folder_data = get_work_data_by_id($work['parent_id']);
$course_info = api_get_course_info();

allowOnlySubscribedUser(api_get_user_id(), $work['parent_id'], $course_info['real_id']);

if (user_is_author($id) || $course_info['show_score'] == 0 && $work['active'] == 1 && $work['accepted'] == 1) {
    if (api_is_allowed_to_edit(null, true)) {
        $url_dir = 'work_list_all.php?id='.$my_folder_data['id'];
    } else {
        $url_dir = 'work_list.php?id='.$my_folder_data['id'];
    }
    $interbreadcrumb[] = array('url' => $url_dir, 'name' => $my_folder_data['title']);
    $interbreadcrumb[] = array('url' => '#','name' => $work['title']);

    if (($course_info['show_score'] == 0 && $work['active'] == 1 && $work['accepted'] == 1) ||
        api_is_allowed_to_edit() || user_is_author($id)
    ) {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
        switch($action) {
            case 'send_comment':
                if (isset($_FILES["file"])) {
                    $_POST['file'] = $_FILES["file"];
                }
                addWorkComment(api_get_course_info(), api_get_user_id(), $work, $_POST);
                $url = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$work['id'].'&'.api_get_cidreq();
                header('Location: '.$url);
                exit;
                break;
            case 'delete_attachment':
                deleteCommentFile($_REQUEST['comment_id'], api_get_course_info());
                $url = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$work['id'].'&'.api_get_cidreq();
                header('Location: '.$url);
                exit;
                break;
        }

        $comments = getWorkComments($work);
        $commentForm = getWorkCommentForm($work);

        $tpl = new Template();
        $tpl->assign('work', $work);
        $tpl->assign('work_comment_enabled', ALLOW_USER_COMMENTS);
        $tpl->assign('comments', $comments);
        $tpl->assign('form', $commentForm);
        $tpl->assign('is_allowed_to_edit', api_is_allowed_to_edit());

        $template = $tpl->get_template('work/view.tpl');
        $content  = $tpl->fetch($template);
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
    } else {
        api_not_allowed(true);
    }
} else {
    api_not_allowed(true);
}
