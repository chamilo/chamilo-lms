<?php
/* For licensing terms, see /license.txt */
/**
 *	@package chamilo.work
 **/

/* INIT SECTION */

use ChamiloSession as Session;

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook', 'tracking');

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

require_once 'work.lib.php';

require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';

$course_id      = api_get_course_int_id();
$course_info    = api_get_course_info();
$user_id 	    = api_get_user_id();
$id_session     = api_get_session_id();

// Section (for the tabs)
$this_section = SECTION_COURSES;
$work_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$my_folder_data = get_work_data_by_id($work_id);

$curdirpath = '';
$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = to_javascript_work();

$_course = api_get_course_info();

/*	Constants and variables */

$tool_name = get_lang('StudentPublications');
$course_code = api_get_course_id();
$session_id = api_get_session_id();
$group_id = api_get_group_id();

$item_id 		        = isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;
$parent_id 		        = isset($_REQUEST['parent_id']) ? Database::escape_string($_REQUEST['parent_id']) : '';
$origin 		        = isset($_REQUEST['origin']) ? Security::remove_XSS($_REQUEST['origin']) : '';
$submitGroupWorkUrl     = isset($_REQUEST['submitGroupWorkUrl']) ? Security::remove_XSS($_REQUEST['submitGroupWorkUrl']) : '';
$title 			        = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';
$description 	        = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';
$uploadvisibledisabled  = isset($_REQUEST['uploadvisibledisabled']) ? Database::escape_string($_REQUEST['uploadvisibledisabled']) : $course_info['show_score'];
$course_dir 		= api_get_path(SYS_COURSE_PATH).$_course['path'];
$base_work_dir 		= $course_dir . '/work';
$link_target_parameter = ""; // e.g. "target=\"_blank\"";
$display_list_users_without_publication = isset($_GET['list']) && Security::remove_XSS($_GET['list']) == 'without' ? true : false;

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';
//Download folder
if ($action == 'downloadfolder') {
    require 'downloadfolder.inc.php';
}

$display_upload_form = false;
if ($action == 'upload_form') {
    $display_upload_form = true;
}

/*	Header */
if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
    $_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
    $gradebook =	$_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
    unset($_SESSION['gradebook']);
    $gradebook = '';
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array ('url' => '../gradebook/' . $_SESSION['gradebook_dest'],'name' => get_lang('ToolGradebook'));
}

if (!empty($group_id)) {
    $group_properties  = GroupManager::get_group_properties($group_id);
    $show_work = false;

    if (api_is_allowed_to_edit(false, true)) {
        $show_work = true;
    } else {
        // you are not a teacher
        $show_work = GroupManager::user_has_access($user_id, $group_id, GroupManager::GROUP_TOOL_WORK);
    }

    if (!$show_work) {
        api_not_allowed();
    }

    $interbreadcrumb[] = array ('url' => '../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$group_id, 'name' => get_lang('GroupSpace').' '.$group_properties['name']);
    $interbreadcrumb[] = array ('url' =>'work.php?gidReq='.$group_id,'name' => get_lang('StudentPublications'));
    $url_dir = 'work.php?&id=' . $work_id;
    if (!empty($my_folder_data)) {
        $interbreadcrumb[] = array ('url' => $url_dir, 'name' =>  $my_folder_data['title']);
    }

    if ($action == 'upload_form') {
        $interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('UploadADocument'));
    }

    if ($action == 'create_dir') {
        $interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('CreateAssignment'));
    }
} else {
    if ($origin != 'learnpath') {

        if (isset($_GET['id']) && !empty($_GET['id']) || $display_upload_form || $action == 'settings' || $action == 'create_dir') {
            $interbreadcrumb[] = array ('url' => 'work.php', 'name' => get_lang('StudentPublications'));
        } else {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('StudentPublications'));
        }

        if (!empty($my_folder_data)) {
            $interbreadcrumb[] = array ('url' => 'work.php?id=' . $work_id, 'name' =>  $my_folder_data['title']);
        }

        if ($action == 'upload_form') {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('UploadADocument'));
        }
        if ($action == 'settings') {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('EditToolOptions'));
        }
        if ($action == 'create_dir') {
            $interbreadcrumb[] = array ('url' => '#','name' => get_lang('CreateAssignment'));
        }
    }
}

// Stats
event_access_tool(TOOL_STUDENTPUBLICATION);

$is_allowed_to_edit = api_is_allowed_to_edit();
$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

/*	Display links to upload form and tool options */
if (!in_array($action, array('add', 'create_dir'))) {
    $token = Security::get_token();
}
$courseInfo = api_get_course_info();

$currentUrl = api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq();
$content = null;

// For teachers
switch ($action) {
    case 'settings':
        //if posts
        if ($is_allowed_to_edit && !empty($_POST['changeProperties'])) {
            updateSettings($course, $_POST['show_score'], $_POST['student_delete_own_publication']);
            Session::write('message', Display::return_message(get_lang('Saved'), 'success'));
            header('Location: '.$currentUrl);
            exit;
        }
        $studentDeleteOwnPublication = api_get_course_setting('student_delete_own_publication') == 1 ? 1 : 0;
        /*	Display of tool options */
        $content = settingsForm(
            array(
                'show_score' => $course_info['show_score'],
                'student_delete_own_publication' =>  $studentDeleteOwnPublication
            )
        );
        break;
    case 'add':
    case 'create_dir':
        if (!$is_allowed_to_edit) {
            api_not_allowed();
        }
        $form = new FormValidator('form1', 'post', api_get_path(WEB_CODE_PATH).'work/work.php?action=create_dir&'. api_get_cidreq());
        $form->addElement('header', get_lang('CreateAssignment'));
        $form->addElement('hidden', 'action', 'add');
        $defaults = isset($_POST) ? $_POST : array();
        $form = getFormWork($form, $defaults);
        $form->addElement('style_submit_button', 'submit', get_lang('CreateDirectory'));

        if ($form->validate()) {

            $result = addDir($_POST, $user_id, $_course, $group_id, $id_session);
            if ($result) {
                $message = Display::return_message(get_lang('DirectoryCreated'), 'success');
            } else {
                $message = Display::return_message(get_lang('CannotCreateDir'), 'error');
            }

            Session::write('message', $message);
            header('Location: '.$currentUrl);
            exit;
        } else {
            $content = $form->return_form();
        }
        break;
    case 'delete_dir':
        if ($is_allowed_to_edit) {
            $work_to_delete = get_work_data_by_id($_REQUEST['id']);
            $result = deleteDirWork($_REQUEST['id']);
            if ($result) {
                $message = Display::return_message(get_lang('DirDeleted') . ': '.$work_to_delete['title'], 'success');
                Session::write('message', $message);
                header('Location: '.$currentUrl);
                exit;
            }
        }
        break;
    case 'move':
        /*	Move file form request */
        if ($is_allowed_to_edit) {
            if (!empty($item_id)) {
                $content = generateMoveForm($item_id, $curdirpath, $course_info, $group_id, $session_id);
            }
        }
        break;
    case 'move_to':
        /* Move file command */
        if ($is_allowed_to_edit) {
            $move_to_path = get_work_path($_REQUEST['move_to_id']);

            if ($move_to_path==-1) {
                $move_to_path = '/';
            } elseif (substr($move_to_path, -1, 1) != '/') {
                $move_to_path = $move_to_path .'/';
            }

            // Security fix: make sure they can't move files that are not in the document table
            if ($path = get_work_path($item_id)) {
                if (move($course_dir.'/'.$path, $base_work_dir . $move_to_path)) {
                    // Update db
                    updateWorkUrl($item_id, 'work' . $move_to_path, $_REQUEST['move_to_id']);
                    api_item_property_update($_course, 'work', $_REQUEST['move_to_id'], 'FolderUpdated', $user_id);

                    $message = Display::return_message(get_lang('DirMv'), 'success');
                } else {
                    $message = Display::return_message(get_lang('Impossible'), 'error');
                }
            } else {
                $message = Display::return_message(get_lang('Impossible'), 'error');
            }
            Session::write('message', $message);
            header('Location: '.$currentUrl);
            exit;
        }
        break;
    case 'list':
        /*	Display list of student publications */
        if (!empty($my_folder_data['description'])) {
            $content = '<p><div><strong>'.
                get_lang('Description').':</strong><p>'.Security::remove_XSS($my_folder_data['description'], STUDENT).
                '</p></div></p>';
        }
        if (api_is_allowed_to_edit()) {
            // Work list
            $content .= '<div class="row">';
            $content .= '<div class="span9">';
            $content .= showTeacherWorkGrid();
            $content .= '</div>';
            $content .= '<div class="span3">';
            $content .= showStudentList($work_id);
            $content .= '</div>';
        } else {
            $content .= showStudentWorkGrid();
        }
    break;
}

Display :: display_header(null);
Display::display_introduction_section(TOOL_STUDENTPUBLICATION);

if ($origin == 'learnpath') {
    echo '<div style="height:15px">&nbsp;</div>';
}

display_action_links($work_id, $curdirpath, $action);

$message = Session::read('message');
echo $message;
Session::erase('message');
echo $content;

Display::display_footer();
