<?php
/* For licensing terms, see /license.txt */
/**
 *	@package chamilo.work
 **/

/* INIT SECTION */

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook');

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
$htmlHeadXtra[] = '<script>
function setFocus() {
    $("#work_title").focus();
}
$(document).ready(function () {
    setFocus();
});
</script>';

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
    $interbreadcrumb[] = array ('url' => $url_dir, 'name' =>  $my_folder_data['title']);

    if ($action == 'upload_form') {
        $interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('UploadADocument'));
    }

    if ($action == 'create_dir') {
        $interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('CreateAssignment'));
    }
    Display::display_header(null);
} else {
    if (isset($origin) && $origin != 'learnpath') {

        if (isset($_GET['id']) && !empty($_GET['id']) || $display_upload_form || $action == 'settings' || $action == 'create_dir') {
            $interbreadcrumb[] = array ('url' => 'work.php', 'name' => get_lang('StudentPublications'));
        } else {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('StudentPublications'));
        }
        $url_dir = 'work.php?id=' . $work_id;
        $interbreadcrumb[] = array ('url' => $url_dir,'name' =>  $my_folder_data['title']);

        if ($action == 'upload_form') {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('UploadADocument'));
        }
        if ($action == 'settings') {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('EditToolOptions'));
        }
        if ($action == 'create_dir') {
            $interbreadcrumb[] = array ('url' => '#','name' => get_lang('CreateAssignment'));
        }
        Display :: display_header(null);
    } else {
        //we are in the learnpath tool
        Display::display_reduced_header();
    }
}

// Stats
event_access_tool(TOOL_STUDENTPUBLICATION);

$is_allowed_to_edit = api_is_allowed_to_edit();
$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

Display::display_introduction_section(TOOL_STUDENTPUBLICATION);

if ($origin == 'learnpath') {
    echo '<div style="height:15px">&nbsp;</div>';
}

/*	Display links to upload form and tool options */
if (!in_array($action, array('add', 'create_dir'))) {
    $token = Security::get_token();
}
$courseInfo = api_get_course_info();
display_action_links($work_id, $curdirpath, $action);
// For teachers
switch ($action) {
    case 'settings':
        //if posts
        if ($is_allowed_to_edit && !empty($_POST['changeProperties'])) {
            updateSettings($course, $_POST['show_score'], $_POST['student_delete_own_publication']);
            Display::display_confirmation_message(get_lang('Saved'));
        }
        $studentDeleteOwnPublication = api_get_course_setting('student_delete_own_publication') == 1 ? 1 : 0;
        /*	Display of tool options */
        settingsForm(
            array(
                'show_score' => $course_info['show_score'],
                'student_delete_own_publication' =>  $studentDeleteOwnPublication
            )
        );
        break;
    case 'mark_work':
        if (!api_is_allowed_to_edit()) {
            echo Display::return_message(get_lang('ActionNotAllowed'), 'error');
            Display::display_footer();
        }
        break;
    case 'create_dir':
    case 'add':
        // Show them the form for the directory name
        if ($is_allowed_to_edit && in_array($action, array('create_dir', 'add'))) {

            $form = new FormValidator('form1', 'post', api_get_path(WEB_CODE_PATH).'work/work.php?action=create_dir&'. api_get_cidreq());
            $form->addElement('header', get_lang('CreateAssignment'));
            $form->addElement('hidden', 'action', 'add');
            $form = getFormWork($form, array());
            $form->addElement('style_submit_button', 'submit', get_lang('CreateDirectory'));

            if ($form->validate()) {
                $result = addDir($_POST, $user_id, $_course, $group_id, $id_session);
                if ($result) {
                    Display::display_confirmation_message(get_lang('DirectoryCreated'), false);
                } else {
                    Display::display_error_message(get_lang('CannotCreateDir'));
                }
            } else {
                $form->display();
            }
        }
    case 'delete_dir':
    case 'move':
    case 'move_to':
    case 'list':
        /* Move file command */
        if ($is_allowed_to_edit && $action == 'move_to') {
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

                    Display::display_confirmation_message(get_lang('DirMv'));
                } else {
                    Display::display_error_message(get_lang('Impossible'));
                }
            } else {
                Display :: display_error_message(get_lang('Impossible'));
            }
        }

        /*	Move file form request */
        if ($is_allowed_to_edit && $action == 'move') {
            if (!empty($item_id)) {
                echo generateMoveForm($item_id, $curdirpath, $course_info, $group_id, $session_id);
            }
        }

        /*	Delete dir */

        if ($is_allowed_to_edit && $action == 'delete_dir') {
            $work_to_delete = get_work_data_by_id($_REQUEST['id']);
            $result = deleteDirWork($_REQUEST['id']);

            if ($result) {
                Display::display_confirmation_message(get_lang('DirDeleted') . ': '.$work_to_delete['title']);
            }
        }

        /*	Display list of student publications */
        if (!empty($my_folder_data['description'])) {
            echo '<p><div><strong>'.
                get_lang('Description').':</strong><p>'.Security::remove_XSS($my_folder_data['description'], STUDENT).
                '</p></div></p>';
        }

        $my_folder_data = get_work_data_by_id($work_id);

        $work_parents = array();
        if (empty($my_folder_data)) {
            $work_parents = getWorkList($work_id, $my_folder_data, $add_query);
        }

        if (api_is_allowed_to_edit()) {
            $userList = getWorkUserList($course_code, $session_id);

            // Work list
            echo '<div class="row">';
            echo '<div class="span9">';
            $grid = showTeacherWorkGrid();
            echo $grid ;
            echo '</div>';
            echo '<div class="span3">';
            echo showStudentList($userList, $work_parents, $group_id, $course_id, $session_id);
            echo '</div>';
        } else {
            echo showStudentWorkGrid();
        }
    break;
}
if ($origin != 'learnpath') {
    //we are not in the learning path tool
    Display :: display_footer();
}
