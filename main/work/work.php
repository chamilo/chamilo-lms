<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.work
 **/

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

require_once 'work.lib.php';

$courseInfo = api_get_course_info();
$course_id = $courseInfo['real_id'];
$user_id = api_get_user_id();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();

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

$item_id = isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;
$origin = isset($_REQUEST['origin']) ? Security::remove_XSS($_REQUEST['origin']) : '';
$course_dir = api_get_path(SYS_COURSE_PATH).$courseInfo['path'];
$base_work_dir = $course_dir . '/work';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

//Download folder
if ($action === 'downloadfolder') {
    require 'downloadfolder.inc.php';
}

$display_upload_form = false;
if ($action === 'upload_form') {
    $display_upload_form = true;
}

/*	Header */
if (api_is_in_gradebook()) {
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.api_get_cidreq(),
        'name' => get_lang('ToolGradebook'),
    );
}

if (!empty($groupId)) {
    api_protect_course_group(GroupManager::GROUP_TOOL_WORK);
    $group_properties = GroupManager::get_group_properties($groupId);

    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    );
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.$group_properties['name'],
    );
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
        'name' => get_lang('StudentPublications'),
    );
    $url_dir = api_get_path(WEB_CODE_PATH).'work/work.php?&id=' . $work_id.'&'.api_get_cidreq();
    if (!empty($my_folder_data)) {
        $interbreadcrumb[] = array('url' => $url_dir, 'name' =>  $my_folder_data['title']);
    }

    if ($action == 'upload_form') {
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
            'name' => get_lang('UploadADocument'),
        );
    }

    if ($action == 'create_dir') {
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
            'name' => get_lang('CreateAssignment'),
        );
    }
} else {
    if ($origin != 'learnpath') {
        if (isset($_GET['id']) && !empty($_GET['id']) || $display_upload_form || $action == 'settings' || $action == 'create_dir') {
            $interbreadcrumb[] = array(
                'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
                'name' => get_lang('StudentPublications'),
            );
        } else {
            $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('StudentPublications'));
        }

        if (!empty($my_folder_data)) {
            $interbreadcrumb[] = array(
                'url' => api_get_path(WEB_CODE_PATH).'work/work.php?id='.$work_id.'&'.api_get_cidreq(),
                'name' => $my_folder_data['title'],
            );
        }

        if ($action == 'upload_form') {
            $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('UploadADocument'));
        }
        if ($action == 'settings') {
            $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('EditToolOptions'));
        }
        if ($action == 'create_dir') {
            $interbreadcrumb[] = array('url' => '#','name' => get_lang('CreateAssignment'));
        }
    }
}

// Stats
Event::event_access_tool(TOOL_STUDENTPUBLICATION);

$is_allowed_to_edit = api_is_allowed_to_edit();
$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

/*	Display links to upload form and tool options */
if (!in_array($action, array('add', 'create_dir'))) {
    $token = Security::get_token();
}

$currentUrl = api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq();
$content = null;

// For teachers
switch ($action) {
    case 'settings':
        //if posts
        if ($is_allowed_to_edit && !empty($_POST['changeProperties'])) {
            updateSettings(
                $courseInfo,
                $_POST['show_score'],
                $_POST['student_delete_own_publication']
            );
            Display::addFlash(Display::return_message(get_lang('Saved'), 'success'));
            header('Location: '.$currentUrl);
            exit;
        }
        $studentDeleteOwnPublication = api_get_course_setting('student_delete_own_publication') == 1 ? 1 : 0;
        /*	Display of tool options */
        $content = settingsForm(
            array(
                'show_score' => $courseInfo['show_score'],
                'student_delete_own_publication' =>  $studentDeleteOwnPublication
            )
        );
        break;
    case 'add':
    case 'create_dir':
        if (!$is_allowed_to_edit) {
            api_not_allowed();
        }
        $form = new FormValidator(
            'form1',
            'post',
            api_get_path(WEB_CODE_PATH) . 'work/work.php?action=create_dir&' . api_get_cidreq()
        );
        $form->addElement('header', get_lang('CreateAssignment'));
        $form->addElement('hidden', 'action', 'add');
        $defaults = isset($_POST) ? $_POST : array();
        $form = getFormWork($form, $defaults);
        $form->addButtonCreate(get_lang('CreateDirectory'));

        if ($form->validate()) {
            $result = addDir(
                $_POST,
                $user_id,
                $courseInfo,
                $groupId,
                $sessionId
            );

            if ($result) {
                $message = Display::return_message(get_lang('DirectoryCreated'), 'success');
            } else {
                $message = Display::return_message(get_lang('CannotCreateDir'), 'error');
            }

            Display::addFlash($message);
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
                $message = Display::return_message(
                    get_lang('DirDeleted') . ': ' . $work_to_delete['title'],
                    'success'
                );
                Display::addFlash($message);
            }
            header('Location: '.$currentUrl);
            exit;
        }
        break;
    case 'move':
        /*	Move file form request */
        if ($is_allowed_to_edit) {
            if (!empty($item_id)) {
                $content = generateMoveForm(
                    $item_id,
                    $curdirpath,
                    $courseInfo,
                    $groupId,
                    $sessionId
                );
            }
        }
        break;
    case 'move_to':
        /* Move file command */
        if ($is_allowed_to_edit) {
            $move_to_path = get_work_path($_REQUEST['move_to_id']);

            if ($move_to_path == -1) {
                $move_to_path = '/';
            } elseif (substr($move_to_path, -1, 1) != '/') {
                $move_to_path = $move_to_path .'/';
            }

            // Security fix: make sure they can't move files that are not in the document table
            if ($path = get_work_path($item_id)) {
                if (move($course_dir.'/'.$path, $base_work_dir . $move_to_path)) {
                    // Update db
                    updateWorkUrl(
                        $item_id,
                        'work' . $move_to_path,
                        $_REQUEST['move_to_id']
                    );

                    api_item_property_update(
                        $courseInfo,
                        'work',
                        $_REQUEST['move_to_id'],
                        'FolderUpdated',
                        $user_id
                    );

                    $message = Display::return_message(get_lang('DirMv'), 'success');
                } else {
                    $message = Display::return_message(get_lang('Impossible'), 'error');
                }
            } else {
                $message = Display::return_message(get_lang('Impossible'), 'error');
            }
            Display::addFlash($message);
            header('Location: '.$currentUrl);
            exit;
        }
        break;
    case 'visible':
        if (!$is_allowed_to_edit) {
            api_not_allowed();
        }

        api_item_property_update(
            $courseInfo,
            'work',
            $work_id,
            'visible',
            api_get_user_id(),
            null,
            null,
            null,
            null,
            $sessionId
        );

        Display::addFlash(
            Display::return_message(
                get_lang('VisibilityChanged'),
                'confirmation'
            )
        );

        header('Location: '.$currentUrl);
        exit;

        break;
    case 'invisible':
        if (!$is_allowed_to_edit) {
            api_not_allowed();
        }

        api_item_property_update(
            $courseInfo,
            'work',
            $work_id,
            'invisible',
            api_get_user_id(),
            null,
            null,
            null,
            null,
            $sessionId
        );

        Display::addFlash(
            Display::return_message(
                get_lang('VisibilityChanged'),
                'confirmation'
            )
        );

        header('Location: '.$currentUrl);
        exit;

        break;
    case 'list':
        /*	Display list of student publications */
        if (!empty($my_folder_data['description'])) {
            $content = '<div>'.
                get_lang('Description').':'.Security::remove_XSS($my_folder_data['description'], STUDENT).
                '</div>';
        }

        // Work list
        if (api_is_allowed_to_edit() || api_is_coach()) {
            $content .= '<div class="row">';
            $content .= '<div class="col-md-12">';
            $content .= '<div class="table-responsive">';
            $content .= Display::panel(showTeacherWorkGrid());
            $content .= '</div>';
            $content .= '</div>';
            $content .= '<div id="student-list-work" style="display: none" class="table-responsive">';
            $content .= '<div class="toolbar"><a id="closed-view-list" href="#"><em class="fa fa-times-circle"></em> ' .get_lang('Close'). '</a></div>';
            $content .= showStudentList($work_id);
            $content .= '</div>';
        } else {
            $content .= Display::panel(showStudentWorkGrid());
        }
        break;
}

Display :: display_header(null);
Display::display_introduction_section(TOOL_STUDENTPUBLICATION);

if ($origin === 'learnpath') {
    echo '<div style="height:15px">&nbsp;</div>';
}

display_action_links($work_id, $curdirpath, $action);
echo $content;

Display::display_footer();
