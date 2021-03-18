<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CGroup;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

$group_id = api_get_group_id();
$groupRepo = Container::getGroupRepository();
$groupEntity = api_get_group_entity($group_id);
$current_group = GroupManager::get_group_properties($group_id);

$nameTools = get_lang('Edit this group');
$interbreadcrumb[] = ['url' => 'group.php?'.api_get_cidreq(), 'name' => get_lang('Groups')];
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
$group_tutor_list = $groupEntity->getTutors();

$selected_tutors = [];
foreach ($group_tutor_list as $user) {
    $selected_tutors[] = $user->getUser()->getId();
}

$complete_user_list = CourseManager::get_user_list_from_course_code(
    api_get_course_id(),
    api_get_session_id()
);

$possible_users = [];
$userGroup = new UserGroup();

$subscribedUsers = GroupManager::get_subscribed_users($groupEntity);
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
        if (INVITEE != $user['status']) {
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

            if ('true' === $orderUserListByOfficialCode) {
                $officialCode = !empty($user['official_code']) ? $user['official_code'].' - ' : '? - ';
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
    get_lang('Coaches'),
    $possible_users,
);

// submit button
$form->addButtonSave(get_lang('Save settings'));

if ($form->validate()) {
    $values = $form->exportValues();

    // Storing the tutors (we first remove all the tutors and then add only those who were selected)
    GroupManager::unsubscribe_all_tutors($group_id);
    if (isset($_POST['group_tutors']) && count($_POST['group_tutors']) > 0) {
        GroupManager::subscribeTutors($values['group_tutors'], $groupEntity);
    }

    // Returning to the group area (note: this is inconsistent with the rest of chamilo)
    $cat = GroupManager::get_category_from_group($group_id);
    $categoryId = null;
    $max_member = null;
    if (!empty($cat)) {
        $categoryId = $cat['iid'];
        $max_member = $cat['max_student'];
    }

    if (isset($_POST['group_members']) &&
        count($_POST['group_members']) > $max_member &&
        GroupManager::MEMBER_PER_GROUP_NO_LIMIT != $max_member
    ) {
        Display::addFlash(
            Display::return_message(
                get_lang(
                    'Number proposed exceeds max. that you allowed (you can modify in the group settings). Group composition has not been modified'
                ),
                'warning'
            )
        );
        header('Location: group.php?'.api_get_cidreq(true, false));
    } else {
        Display::addFlash(Display::return_message(get_lang('Group settings modified'), 'success'));
        header('Location: group.php?'.api_get_cidreq(true, false).'&category='.$categoryId);
    }
    exit;
}

$defaults = $current_group;
$defaults['group_tutors'] = $selected_tutors;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$defaults['action'] = $action;

if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
    $keyword_name = Security::remove_XSS($_GET['keyword']);
    echo '<br/>'.get_lang('Search results for:').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

Display :: display_header($nameTools, 'Group');
$form->setDefaults($defaults);
echo GroupManager::getSettingBar('tutor');
$form->display();

Display :: display_footer();
