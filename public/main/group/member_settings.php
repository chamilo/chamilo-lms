<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

$group_id = api_get_group_id();
$current_group = GroupManager::get_group_properties($group_id);
$groupEntity = api_get_group_entity($group_id);

$nameTools = get_lang('Edit this group');
$interbreadcrumb[] = ['url' => 'group.php', 'name' => get_lang('Groups')];
$interbreadcrumb[] = ['url' => 'group_space.php?'.api_get_cidreq(), 'name' => $groupEntity->getName()];

$is_group_member = GroupManager::isTutorOfGroup(api_get_user_id(), $groupEntity);

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
    if ('true' === $orderListByOfficialCode) {
        $cmp = api_strcmp($user_a['official_code'], $user_b['official_code']);
        if (0 !== $cmp) {
            return $cmp;
        } else {
            $cmp = api_strcmp($user_a['lastname'], $user_b['lastname']);
            if (0 !== $cmp) {
                return $cmp;
            } else {
                return api_strcmp($user_a['username'], $user_b['username']);
            }
        }
    }

    if (api_sort_by_first_name()) {
        $cmp = api_strcmp($user_a['firstname'], $user_b['firstname']);
        if (0 !== $cmp) {
            return $cmp;
        } else {
            $cmp = api_strcmp($user_a['lastname'], $user_b['lastname']);
            if (0 !== $cmp) {
                return $cmp;
            } else {
                return api_strcmp($user_a['username'], $user_b['username']);
            }
        }
    } else {
        $cmp = api_strcmp($user_a['lastname'], $user_b['lastname']);
        if (0 !== $cmp) {
            return $cmp;
        } else {
            $cmp = api_strcmp($user_a['firstname'], $user_b['firstname']);
            if (0 !== $cmp) {
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
    if (GroupManager::MEMBER_PER_GROUP_NO_LIMIT == $value['max_student']) {
        return true;
    }
    if (isset($value['max_student']) &&
        isset($value['group_members']) &&
        $value['max_student'] < count($value['group_members'])
    ) {
        return ['group_members' => get_lang('Number proposed exceeds max. that you allowed (you can modify in the group settings). Group composition has not been modified')];
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
$form->addElement('hidden', 'max_student', $groupEntity->getMaxStudent());

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
        if (INVITEE != $user['status']) {
            $officialCode = !empty($user['official_code']) ? ' - '.$user['official_code'] : null;

            $groups = $userGroup->getUserGroupListByUser($user['user_id']);
            $groupNameListToString = '';
            if (!empty($groups)) {
                $groupNameList = array_column($groups, 'name');
                $groupNameListToString = ' - ['.implode(', ', $groupNameList).']';
            }

            $name = api_get_person_name($user['firstname'], $user['lastname']).
                    ' ('.$user['username'].')'.$officialCode;

            if ('true' === $orderUserListByOfficialCode) {
                $officialCode = !empty($user['official_code']) ? $user['official_code'].' - ' : '? - ';
                $name = $officialCode.' '.api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')';
            }
            $possible_users[$user['user_id']] = $name.$groupNameListToString;
        }
    }
}

// Group members
$group_member_list = GroupManager::get_subscribed_users($groupEntity);

$selected_users = [];
if (!empty($group_member_list)) {
    foreach ($group_member_list as $index => $user) {
        $selected_users[] = $user['user_id'];
    }
}

$group_members_element = $form->addElement(
    'advmultiselect',
    'group_members',
    get_lang('Group members'),
    $possible_users
);
$form->addFormRule('check_group_members');

// submit button
$form->addButtonSave(get_lang('Save settings'));

if ($form->validate()) {
    $values = $form->exportValues();

    // Storing the users (we first remove all users and then add only those who were selected)
    GroupManager::unsubscribeAllUsers($groupEntity->getIid());
    if (isset($_POST['group_members']) && count($_POST['group_members']) > 0) {
        GroupManager::subscribeUsers(
            $values['group_members'],
            $groupEntity
        );
    }

    // Returning to the group area (note: this is inconsistent with the rest of chamilo)
    $cat = GroupManager::get_category_from_group($group_id);
    $categoryId = 0;
    if ($cat) {
        $categoryId = $cat['iid'];
    }
    $max_member = $groupEntity->getMaxStudent();

    if (isset($_POST['group_members']) &&
        count($_POST['group_members']) > $max_member &&
        GroupManager::MEMBER_PER_GROUP_NO_LIMIT != $max_member
    ) {
        Display::addFlash(Display::return_message(get_lang('Number proposed exceeds max. that you allowed (you can modify in the group settings). Group composition has not been modified'), 'warning'));
        header('Location: group.php?'.api_get_cidreq(true, false));
    } else {
        Display::addFlash(Display::return_message(get_lang('Group settings modified'), 'success'));
        header('Location: group.php?'.api_get_cidreq(true, false).'&category='.$categoryId);
    }
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : null;
switch ($action) {
    case 'empty':
        if (api_is_allowed_to_edit(false, true)) {
            GroupManager::unsubscribeAllUsers($group_id);
            echo Display::return_message(get_lang('The group is now empty'), 'confirm');
        }

        break;
}

$defaults = $current_group;
$defaults['group_members'] = $selected_users;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$defaults['action'] = $action;

if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
    $keyword_name = Security::remove_XSS($_GET['keyword']);
    echo '<br/>'.get_lang('Search results for:').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

Display::display_header($nameTools, 'Group');

$form->setDefaults($defaults);
echo GroupManager::getSettingBar('member');
$form->display();

Display::display_footer();
