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
$interbreadcrumb[] = ['url' => 'group.php?'.api_get_cidreq(), 'name' => get_lang('Groups')];
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

$htmlHeadXtra[] = '<script>
$(function() {
    $("#max_member").on("focus", function() {
        $("#max_member_selected").attr("checked", true);
    });
});
 </script>';

// Build form
$form = new FormValidator('group_edit', 'post', api_get_self().'?'.api_get_cidreq());
$form->addElement('hidden', 'action');

// Group tutors
$group_tutor_list = GroupManager::get_subscribed_tutors($current_group);

$selected_tutors = [];
foreach ($group_tutor_list as $index => $user) {
    $selected_tutors[] = $user['user_id'];
}

$complete_user_list = CourseManager::get_user_list_from_course_code(
    api_get_course_id(),
    api_get_session_id()
);

$possible_users = [];
$userGroup = new UserGroup();

$subscribedUsers = GroupManager::get_subscribed_users($current_group);
if ($subscribedUsers) {
    $subscribedUsers = array_column($subscribedUsers, 'user_id');
}

$orderUserListByOfficialCode = api_get_setting('order_user_list_by_official_code');
if (!empty($complete_user_list)) {
    usort($complete_user_list, 'sort_users');
    foreach ($complete_user_list as $index => $user) {
        if (in_array($user['user_id'], $subscribedUsers)) {
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

            $name = api_get_person_name(
                $user['firstname'],
                $user['lastname']
            ).' ('.$user['username'].')'.$officialCode;

            if ($orderUserListByOfficialCode === 'true') {
                $officialCode = !empty($user['official_code']) ? $user['official_code']." - " : '? - ';
                $name = $officialCode.' '.api_get_person_name(
                    $user['firstname'],
                    $user['lastname']
                ).' ('.$user['username'].')';
            }

            $possible_users[$user['user_id']] = $name.$groupNameListToString;
        }
    }
}

$group_tutors_element = $form->addElement(
    'advmultiselect',
    'group_tutors',
    get_lang('GroupTutors'),
    $possible_users,
    'style="width: 280px;"'
);

// submit button
$form->addButtonSave(get_lang('SaveSettings'));

if ($form->validate()) {
    $values = $form->exportValues();

    // Storing the tutors (we first remove all the tutors and then add only those who were selected)
    GroupManager::unsubscribe_all_tutors($current_group['iid']);
    if (isset($_POST['group_tutors']) && count($_POST['group_tutors']) > 0) {
        GroupManager::subscribe_tutors($values['group_tutors'], $current_group);
    }

    // Returning to the group area (note: this is inconsistent with the rest of chamilo)
    $cat = GroupManager::get_category_from_group($current_group['iid']);

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

$defaults = $current_group;
$defaults['group_tutors'] = $selected_tutors;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$defaults['action'] = $action;

if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
    $keyword_name = Security::remove_XSS($_GET['keyword']);
    echo '<br/>'.get_lang('SearchResultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

Display::display_header($nameTools, 'Group');
$form->setDefaults($defaults);
echo GroupManager::getSettingBar('tutor');
$form->display();

Display::display_footer();
