<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';
$this_section = SECTION_COURSES;

$workId = isset($_GET['id']) ? (int) ($_GET['id']) : null;
$group_id = api_get_group_id();
$user_id = api_get_user_id();

if (empty($workId)) {
    api_not_allowed(true);
}

$my_folder_data = get_work_data_by_id($workId);
if (empty($my_folder_data)) {
    api_not_allowed(true);
}

if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed(true);
}

// User with no works
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

switch ($action) {
    case 'send_mail':
        $check = Security::check_token('get');
        if ($check) {
            $mails_sent_to = send_reminder_users_without_publication(
                $my_folder_data
            );

            if (empty($mails_sent_to)) {
                Display::addFlash(Display::return_message(get_lang('NoResults'), 'warning'));
            } else {
                Display::addFlash(Display::return_message(
                    get_lang('MessageHasBeenSent').' '.implode(', ', $mails_sent_to),
                    'success'
                ));
            }
            Security::clear_token();
        }
        break;
}

$token = Security::get_token();

if (!empty($group_id)) {
    $group_properties = GroupManager::get_group_properties($group_id);
    $show_work = false;

    if (api_is_allowed_to_edit(false, true)) {
        $show_work = true;
    } else {
        // you are not a teacher
        $show_work = GroupManager::user_has_access(
            $user_id,
            $group_properties['iid'],
            GroupManager::GROUP_TOOL_WORK
        );
    }

    if (!$show_work) {
        api_not_allowed();
    }

    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?gidReq='.$group_id,
        'name' => get_lang('GroupSpace').' '.$group_properties['name'],
    ];
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId,
    'name' => $my_folder_data['title'],
];

if (isset($_GET['list']) && $_GET['list'] == 'with') {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('UsersWithTask')];
} else {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('UsersWithoutTask')];
}

Display::display_header(null);

echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'work/work_list_all.php?id='.$workId.'&'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('BackToWorksList'), '', ICON_SIZE_MEDIUM).'</a>';
$output = '';
if (!empty($workId)) {
    if (empty($_GET['list']) or Security::remove_XSS($_GET['list']) == 'with') {
        $output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&id='.$workId.'&list=without">'.
            Display::return_icon('exercice_uncheck.png', get_lang('ViewUsersWithoutTask'), '', ICON_SIZE_MEDIUM).
            "</a>";
    } else {
        if (!isset($_GET['action']) || (isset($_GET['action']) && $_GET['action'] != 'send_mail')) {
            $output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&id='.$workId.'&list=without&action=send_mail&sec_token='.$token.'">'.
                Display::return_icon('mail_send.png', get_lang('ReminderMessage'), '', ICON_SIZE_MEDIUM).
                "</a>";
        } else {
            $output .= Display::return_icon('mail_send_na.png', get_lang('ReminderMessage'), '', ICON_SIZE_MEDIUM);
        }
    }
}

echo $output;
echo '</div>';

display_list_users_without_publication($workId);
