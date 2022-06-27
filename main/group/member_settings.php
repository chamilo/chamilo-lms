<?php

/* For licensing terms, see /license.txt */

/**
 * This script displays an area where teachers can edit the group properties and member list.
 * Groups are also often called "teams" in the Dokeos code.
 *
 * @author various contributors
 * @author Roan Embrechts (VUB), partial code cleanup, initial virtual course support
 *
 * @todo course admin functionality to create groups based on who is in which course (or class).
 */
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

$group_id = api_get_group_id();
$current_group = GroupManager::get_group_properties($group_id);

$nameTools = get_lang('EditGroup');
$interbreadcrumb[] = ['url' => 'group.php', 'name' => get_lang('Groups')];
$interbreadcrumb[] = ['url' => 'group_space.php?'.api_get_cidreq(), 'name' => $current_group['name']];

$is_group_member = GroupManager::is_tutor_of_group(api_get_user_id(), $current_group);

if (!api_is_allowed_to_edit(false, true) && !$is_group_member) {
    api_not_allowed(true);
}

/**
 * Function to sort users after getting the list in the DB.
 * Necessary because there are 2 or 3 queries. Called by usort().
 */
function sort_users($user_a, $user_b)
{
    $orderListByOfficialCode = api_get_setting('order_user_list_by_official_code');
    if ($orderListByOfficialCode === 'true') {
        $cmp = api_strcmp($user_a['official_code'], $user_b['official_code']);
        if ($cmp !== 0) {
            return $cmp;
        } else {
            $cmp = api_strcmp($user_a['lastname'], $user_b['lastname']);
            if ($cmp !== 0) {
                return $cmp;
            } else {
                return api_strcmp($user_a['username'], $user_b['username']);
            }
        }
    }

    if (api_sort_by_first_name()) {
        $cmp = api_strcmp($user_a['firstname'], $user_b['firstname']);
        if ($cmp !== 0) {
            return $cmp;
        } else {
            $cmp = api_strcmp($user_a['lastname'], $user_b['lastname']);
            if ($cmp !== 0) {
                return $cmp;
            } else {
                return api_strcmp($user_a['username'], $user_b['username']);
            }
        }
    } else {
        $cmp = api_strcmp($user_a['lastname'], $user_b['lastname']);
        if ($cmp !== 0) {
            return $cmp;
        } else {
            $cmp = api_strcmp($user_a['firstname'], $user_b['firstname']);
            if ($cmp !== 0) {
                return $cmp;
            } else {
                return api_strcmp($user_a['username'], $user_b['username']);
            }
        }
    }
}

/**
 * Function to check if the number of selected group members is valid.
 */
function check_group_members($value)
{
    if ($value['max_student'] == GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
        return true;
    }
    if (isset($value['max_student']) &&
        isset($value['group_members']) &&
        $value['max_student'] < count($value['group_members'])
    ) {
        return ['group_members' => get_lang('GroupTooMuchMembers')];
    }

    return true;
}

$htmlHeadXtra[] = '<script>
$(function() {
    $("#max_member").on("focus", function() {
        $("#max_member_selected").attr("checked", true);
    });
});
 </script>';

// Build form
$form = new FormValidator(
    'group_edit',
    'post',
    api_get_self().'?'.api_get_cidreq()
);
$form->addElement('hidden', 'action');
$form->addElement('hidden', 'max_student', $current_group['max_student']);

$complete_user_list = CourseManager::get_user_list_from_course_code(
    api_get_course_id(),
    api_get_session_id()
);

$subscribedTutors = GroupManager::getTutors($current_group);
if ($subscribedTutors) {
    $subscribedTutors = array_column($subscribedTutors, 'user_id');
}

$orderUserListByOfficialCode = api_get_setting('order_user_list_by_official_code');
$possible_users = [];
$userGroup = new UserGroup();

if (!empty($complete_user_list)) {
    usort($complete_user_list, 'sort_users');
    foreach ($complete_user_list as $index => $user) {
        if (in_array($user['user_id'], $subscribedTutors)) {
            continue;
        }
        //prevent invitee users add to groups or tutors - see #8091
        if ($user['status'] != INVITEE) {
            $officialCode = !empty($user['official_code']) ? ' - '.$user['official_code'] : null;

            $groups = $userGroup->getUserGroupListByUser($user['user_id']);
            $groupNameListToString = '';
            if (!empty($groups)) {
                $groupNameList = array_column($groups, 'name');
                $groupNameListToString = ' - ['.implode(', ', $groupNameList).']';
            }

            $name = api_get_person_name($user['firstname'], $user['lastname']).
                    ' ('.$user['username'].')'.$officialCode;

            if ($orderUserListByOfficialCode === 'true') {
                $officialCode = !empty($user['official_code']) ? $user['official_code']." - " : '? - ';
                $name = $officialCode.' '.api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')';
            }
            $possible_users[$user['user_id']] = $name.$groupNameListToString;
        }
    }
}

// Group members
$group_member_list = GroupManager::get_subscribed_users($current_group);

$selected_users = [];
if (!empty($group_member_list)) {
    foreach ($group_member_list as $index => $user) {
        $selected_users[] = $user['user_id'];
    }
}

$group_members_element = $form->addElement(
    'advmultiselect',
    'group_members',
    get_lang('GroupMembers'),
    $possible_users
);
$form->addFormRule('check_group_members');

// submit button
$form->addButtonSave(get_lang('SaveSettings'));

if ($form->validate()) {
    $values = $form->exportValues();

    // Storing the users (we first remove all users and then add only those who were selected)
    GroupManager::unsubscribe_all_users($current_group);
    if (isset($_POST['group_members']) && count($_POST['group_members']) > 0) {
        GroupManager::subscribe_users(
            $values['group_members'],
            $current_group
        );
    }

    // Returning to the group area (note: this is inconsistent with the rest of chamilo)
    $cat = GroupManager::get_category_from_group($current_group['iid']);
    $max_member = $current_group['max_student'];

    if (isset($_POST['group_members']) &&
        count($_POST['group_members']) > $max_member &&
        $max_member != GroupManager::MEMBER_PER_GROUP_NO_LIMIT
    ) {
        Display::addFlash(Display::return_message(get_lang('GroupTooMuchMembers'), 'warning'));
        header('Location: group.php?'.api_get_cidreq(true, false));
    } else {
        Display::addFlash(Display::return_message(get_lang('GroupSettingsModified'), 'success'));
        header('Location: group.php?'.api_get_cidreq(true, false).'&category='.$cat['id']);
    }
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : null;
switch ($action) {
    case 'empty':
        if (api_is_allowed_to_edit(false, true)) {
            GroupManager::unsubscribe_all_users($current_group);
            echo Display::return_message(get_lang('GroupEmptied'), 'confirm');
        }
        break;
}

$defaults = $current_group;
$defaults['group_members'] = $selected_users;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$defaults['action'] = $action;

if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
    $keyword_name = Security::remove_XSS($_GET['keyword']);
    echo '<br/>'.get_lang('SearchResultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

Display::display_header($nameTools, 'Group');

$form->setDefaults($defaults);
echo GroupManager::getSettingBar('member');
$form->display();

Display::display_footer();
