<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CStudentPublication;

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

$courseInfo = api_get_course_info();
$user_id = api_get_user_id();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();

// Section (for the tabs)
$this_section = SECTION_COURSES;
$work_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$my_folder_data = get_work_data_by_id($work_id);
$repo = Container::getStudentPublicationRepository();
$studentPublication = null;
if (!empty($work_id)) {
    $studentPublication = $repo->find($work_id);
}

$curdirpath = '';
$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = to_javascript_work();
$tool_name = get_lang('Assignments');

$item_id = isset($_REQUEST['item_id']) ? (int) $_REQUEST['item_id'] : null;
$origin = api_get_origin();
$action = $_REQUEST['action'] ?? 'list';

$display_upload_form = false;
if ('upload_form' === $action) {
    $display_upload_form = true;
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.api_get_cidreq(),
        'name' => get_lang('Assessments'),
    ];
}

if (!empty($groupId)) {
    api_protect_course_group(GroupManager::GROUP_TOOL_WORK);
    $group_properties = GroupManager::get_group_properties($groupId);

    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('Group area').' '.$group_properties['name'],
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
        'name' => get_lang('Assignments'),
    ];
    $url_dir = api_get_path(WEB_CODE_PATH).'work/work.php?&id='.$work_id.'&'.api_get_cidreq();
    if (!empty($my_folder_data)) {
        $interbreadcrumb[] = ['url' => $url_dir, 'name' => $my_folder_data['title']];
    }

    if ('upload_form' == $action) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
            'name' => get_lang('Upload a document'),
        ];
    }

    if ('create_dir' == $action) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
            'name' => get_lang('Create assignment'),
        ];
    }
} else {
    if ('learnpath' != $origin) {
        if (isset($_GET['id']) &&
            !empty($_GET['id']) || $display_upload_form || 'create_dir' === $action
        ) {
            $interbreadcrumb[] = [
                'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
                'name' => get_lang('Assignments'),
            ];
        } else {
            $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Assignments')];
        }

        if (!empty($my_folder_data)) {
            $interbreadcrumb[] = [
                'url' => api_get_path(WEB_CODE_PATH).'work/work.php?id='.$work_id.'&'.api_get_cidreq(),
                'name' => $my_folder_data['title'],
            ];
        }

        if ('upload_form' === $action) {
            $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Upload a document')];
        }

        if ('create_dir' === $action) {
            $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Create assignment')];
        }
    }
}

Event::event_access_tool(TOOL_STUDENTPUBLICATION);

$logInfo = [
    'tool' => TOOL_STUDENTPUBLICATION,
    'action' => $action,
];
Event::registerLog($logInfo);

$groupId = api_get_group_id();
$isTutor = false;
if (!empty($groupId)) {
    $isTutor = GroupManager::isTutorOfGroup(
        api_get_user_id(),
        api_get_group_entity()
    );
}

$is_allowed_to_edit = api_is_allowed_to_edit();
$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

/*	Display links to upload form and tool options */
if (!in_array($action, ['add', 'create_dir'])) {
    $token = Security::get_token();
}

$currentUrl = api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq();
$content = null;

// For teachers
switch ($action) {
    case 'add':
    case 'create_dir':
        if (!($is_allowed_to_edit || $isTutor)) {
            api_not_allowed(true);
        }
        $addUrl = api_get_path(WEB_CODE_PATH).'work/work.php?action=create_dir&'.api_get_cidreq();
        $form = new FormValidator(
            'form1',
            'post',
            $addUrl
        );
        $form->addHeader(get_lang('Create assignment'));
        $form->addElement('hidden', 'action', 'add');
        // Set default values
        $defaults = !empty($_POST) ? $_POST : ['allow_text_assignment' => 2];

        $form = getFormWork($form, $defaults);
        $form->addButtonCreate(get_lang('Validate'));

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $result = addDir(
                $values,
                $user_id,
                $courseInfo,
                $groupId,
                $sessionId
            );

            if ($result) {
                SkillModel::saveSkills($form, ITEM_TYPE_STUDENT_PUBLICATION, $result);

                $message = Display::return_message(get_lang('Directory created'), 'success');
            } else {
                $currentUrl = $addUrl;
                $message = Display::return_message(get_lang('Unable to create the folder.'), 'error');
            }

            Display::addFlash($message);
            header('Location: '.$currentUrl);
            exit;
        } else {
            $content = $form->returnForm();
        }

        break;
    case 'delete_dir':
        if ($is_allowed_to_edit) {
            $work_to_delete = get_work_data_by_id($_REQUEST['id']);
            $result = deleteDirWork($_REQUEST['id']);
            if ($result) {
                $message = Display::return_message(
                    get_lang('Folder deleted').': '.$work_to_delete['title'],
                    'success'
                );
                Display::addFlash($message);
            }
            header('Location: '.$currentUrl);
            exit;
        }

        break;
    case 'move':
        // Move file form request
        if ($is_allowed_to_edit && !empty($item_id)) {
            $content = generateMoveForm(
                $item_id,
                $curdirpath,
                $courseInfo,
                $groupId,
                $sessionId
            );
        }

        break;
    case 'move_to':
        /* Move file command */
        if ($is_allowed_to_edit && isset($_REQUEST['move_to_id'])) {
            $moveToParentId = $_REQUEST['move_to_id'];

            /** @var CStudentPublication $newParent */
            $newParent = $repo->find($_REQUEST['move_to_id']);

            /** @var CStudentPublication $studentPublication */
            $studentPublication = $repo->find($_REQUEST['item_id']);
            if ($_REQUEST['move_to_id']) {
                $parent = $repo->find($_REQUEST['move_to_id']);
                $studentPublication->setParent($parent);
            }
            $studentPublication->getResourceNode()->setParent($newParent->getResourceNode());
            $repo->update($studentPublication);
            /*api_item_property_update(
                $courseInfo,
                'work',
                $_REQUEST['move_to_id'],
                'FolderUpdated',
                $user_id
            );*/

            $message = Display::return_message(get_lang('Element moved'), 'success');

            Display::addFlash($message);
            header('Location: '.$currentUrl);
            exit;
        }

        break;
    case 'visible':
        if (!$is_allowed_to_edit) {
            api_not_allowed();
        }

        $repo->setVisibilityPublished($studentPublication);

        /*api_item_property_update(
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
        );*/

        Display::addFlash(
            Display::return_message(
                get_lang('The visibility has been changed.'),
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

        $repo->setVisibilityDraft($studentPublication);
        /*
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
        );*/

        Display::addFlash(
            Display::return_message(
                get_lang('The visibility has been changed.'),
                'confirmation'
            )
        );

        header('Location: '.$currentUrl);
        exit;

        break;
    case 'list':
        /* Display list of student publications */
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
            $content .= '<div class="toolbar"><a id="closed-view-list" href="#">
                         <em class="fa fa-times-circle"></em> '.get_lang('Close').'</a></div>';
            $content .= showStudentList($work_id);
            $content .= '</div>';
        } else {
            $content .= Display::panel(showStudentWorkGrid());
        }

        break;
}

Display::display_header(null);
Display::display_introduction_section(TOOL_STUDENTPUBLICATION);

if ('learnpath' === $origin) {
    echo '<div style="height:15px">&nbsp;</div>';
}

displayWorkActionLinks($work_id, $action, $isTutor);
echo $content;

Display::display_footer();
