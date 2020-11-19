<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Repository\CourseRepository;
use Chamilo\CoreBundle\Entity\Repository\ItemPropertyRepository;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\UserBundle\Entity\User;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

$subscriptionSettings = learnpath::getSubscriptionSettings();
if ($subscriptionSettings['allow_add_users_to_lp'] == false) {
    api_not_allowed(true);
}

$is_allowed_to_edit = api_is_allowed_to_edit(false, true, false, false);
if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

$lpId = isset($_GET['lp_id']) ? (int) $_GET['lp_id'] : 0;

if (empty($lpId)) {
    api_not_allowed(true);
}

$allowUserGroups = api_get_configuration_value('allow_lp_subscription_to_usergroups');
$currentUser = api_get_user_entity(api_get_user_id());

$oLP = new learnpath(api_get_course_id(), $lpId, api_get_user_id());
$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];

$interbreadcrumb[] = [
    'url' => api_get_self().'?action=build&lp_id='.$oLP->get_id().'&'.api_get_cidreq(),
    'name' => $oLP->getNameNoTags(),
];

$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();
$sessionId = api_get_session_id();

$url = api_get_self().'?'.api_get_cidreq().'&lp_id='.$lpId;
$lp = new learnpath($courseCode, $lpId, api_get_user_id());
$em = Database::getManager();
/** @var CourseRepository $courseRepo */
$courseRepo = $em->getRepository('ChamiloCoreBundle:Course');
/** @var ItemPropertyRepository $itemRepo */
$itemRepo = $em->getRepository('ChamiloCourseBundle:CItemProperty');

/** @var Session $session */
$session = null;
if (!empty($sessionId)) {
    $session = $em->getRepository('ChamiloCoreBundle:Session')->find($sessionId);
}

$course = $courseRepo->find($courseId);
$subscribedUsers = [];

// Getting subscribe users to the course.
if (!$session) {
    $subscribedUsers = $courseRepo->getSubscribedStudents($course)
        ->getQuery()
        ->getResult();
} else {
    $list = $session->getUserCourseSubscriptionsByStatus($course, Session::STUDENT);
    if ($list) {
        /** @var SessionRelCourseRelUser $sessionCourseUser */
        foreach ($list as $sessionCourseUser) {
            $subscribedUsers[$sessionCourseUser->getUser()->getId()] = $sessionCourseUser->getUser();
        }
    }
}

// Getting all users in a nice format.
$choices = [];
/** @var User $user */
foreach ($subscribedUsers as $user) {
    $choices[$user->getId()] = $user->getCompleteNameWithClasses();
}

// Getting subscribed users to a LP.
$subscribedUsersInLp = $itemRepo->getUsersSubscribedToItem(
    'learnpath',
    $lpId,
    $course,
    $session
);

$selectedChoices = [];
if (!empty($subscribedUsersInLp)) {
    foreach ($subscribedUsersInLp as $itemProperty) {
        if (!empty($itemProperty)) {
            $getToUser = $itemProperty->getToUser();
            if (!empty($getToUser)) {
                $selectedChoices[] = $getToUser->getId();
            }
        }
    }
}

// User form.
$formUsers = new FormValidator('lp_edit', 'post', $url);
$formUsers->addElement('hidden', 'user_form', 1);

$userMultiSelect = $formUsers->addElement(
    'advmultiselect',
    'users',
    get_lang('Users'),
    $choices
);
$formUsers->addButtonSave(get_lang('Save'));

$defaults = [];
if (!empty($selectedChoices)) {
    $defaults['users'] = $selectedChoices;
}

$formUsers->setDefaults($defaults);

// Group form.
$form = new FormValidator('lp_edit', 'post', $url);
$form->addElement('hidden', 'group_form', 1);

// Group list
$groupList = \CourseManager::get_group_list_of_course(
    $courseCode,
    $sessionId,
    1
);
$groupChoices = array_column($groupList, 'name', 'id');

// Subscribed groups to a LP
$subscribedGroupsInLp = $itemRepo->getGroupsSubscribedToItem(
    'learnpath',
    $lpId,
    $course,
    $session
);

$selectedGroupChoices = [];
/** @var CItemProperty $itemProperty */
if (!empty($subscribedGroupsInLp)) {
    foreach ($subscribedGroupsInLp as $itemProperty) {
        if (!empty($itemProperty)) {
            $getGroup = $itemProperty->getGroup();
            if (!empty($getGroup)) {
                $selectedGroupChoices[] = $itemProperty->getGroup()->getId();
            }
        }
    }
}

$groupMultiSelect = $form->addElement(
    'advmultiselect',
    'groups',
    get_lang('Groups'),
    $groupChoices
);

$form->addButtonSave(get_lang('Save'));

// UserGroup
if ($allowUserGroups) {
    $formUserGroup = new FormValidator('usergroup_form', 'post', $url);
    $formUserGroup->addHidden('usergroup_form', 1);

    $userGroup = new UserGroup();
    $conditions = [];
    $conditions['where'] = [' usergroup.course_id = ? ' => $courseId];
    $groups = $userGroup->getUserGroupInCourse($conditions);
    $allOptions = array_column($groups, 'name', 'id');
    $items = $userGroup->getGroupsByLp($lpId, $courseId, $sessionId);

    $selectedUserGroupChoices = [];
    if (!empty($items)) {
        foreach ($items as $data) {
            if (isset($allOptions[$data['usergroup_id']])) {
                $selectedUserGroupChoices[] = $data['usergroup_id'];
            }
        }
    }

    $userGroupMultiSelect = $formUserGroup->addElement(
        'advmultiselect',
        'usergroups',
        get_lang('Classes'),
        $allOptions
    );

    $formUserGroup->setDefaults(['usergroups' => $selectedUserGroupChoices]);
    $formUserGroup->addButtonSave(get_lang('Save'));
    $sessionCondition = api_get_session_condition($sessionId, true);
    if ($formUserGroup->validate()) {
        $values = $formUserGroup->getSubmitValues();
        $table = Database::get_course_table(TABLE_LP_REL_USERGROUP);
        if (isset($values['usergroups'])) {
            $userGroups = $values['usergroups'];
            foreach ($selectedUserGroupChoices as $userGroupId) {
                $userGroupId = (int) $userGroupId;
                if (!in_array($userGroupId, $userGroups)) {
                    $sql = "DELETE FROM $table
                            WHERE
                                c_id = $courseId AND
                                lp_id = $lpId AND
                                usergroup_id = $userGroupId
                                $sessionCondition
                            ";
                    Database::query($sql);

                    $userList = $userGroup->get_users_by_usergroup($userGroupId);
                    $itemRepo->unsubcribeUsersToItem(
                        'learnpath',
                        $course,
                        $session,
                        $lpId,
                        $userList
                    );
                }
            }

            foreach ($userGroups as $userGroupId) {
                $userGroupId = (int) $userGroupId;
                $sql = "SELECT id FROM $table
                        WHERE
                            c_id = $courseId AND
                            lp_id = $lpId AND
                            usergroup_id = $userGroupId
                            $sessionCondition
                            ";
                $result = Database::query($sql);

                if (0 == Database::num_rows($result)) {
                    $params = [
                        'lp_id' => $lpId,
                        'c_id' => $courseId,
                        'usergroup_id' => $userGroupId,
                        'created_at' => api_get_utc_datetime(),
                    ];
                    if (!empty($sessionId)) {
                        $params['session_id'] = $sessionId;
                    }
                    Database::insert($table, $params);
                }
            }

            $groups = $userGroup->getGroupsByLp($lpId, $courseId, $sessionId);
            $userList = [];
            foreach ($groups as $groupId) {
                $userList = $userGroup->get_users_by_usergroup($groupId);
                $itemRepo->subscribeUsersToItem(
                    $currentUser,
                    'learnpath',
                    $course,
                    $session,
                    $lpId,
                    $userList,
                    false
                );
            }

            Display::addFlash(Display::return_message(get_lang('Updated')));
        } else {
            foreach ($groups as $group) {
                $userList = $userGroup->get_users_by_usergroup($group['id']);
                $itemRepo->unsubcribeUsersToItem(
                    'learnpath',
                    $course,
                    $session,
                    $lpId,
                    $userList
                );
            }

            // Clean all
            $sql = "DELETE FROM $table
                    WHERE
                        c_id = $courseId AND
                        lp_id = $lpId
                        $sessionCondition
                    ";
            Database::query($sql);
        }
        header("Location: $url");
        exit;
    }
}

$defaults = [];
if (!empty($selectedChoices)) {
    $defaults['users'] = $selectedChoices;
}

$formUsers->setDefaults($defaults);

$defaults = [];
if (!empty($selectedGroupChoices)) {
    $defaults['groups'] = $selectedGroupChoices;
}
$form->setDefaults($defaults);

// Group.
if ($form->validate()) {
    $values = $form->getSubmitValues();

    // Subscribing users
    $users = isset($values['users']) ? $values['users'] : [];
    $userForm = isset($values['user_form']) ? $values['user_form'] : [];

    if (!empty($userForm)) {
        $itemRepo->subscribeUsersToItem(
            $currentUser,
            'learnpath',
            $course,
            $session,
            $lpId,
            $users
        );
        Display::addFlash(Display::return_message(get_lang('Updated')));
    }

    // Subscribing groups
    $groups = isset($values['groups']) ? $values['groups'] : [];
    $groupForm = isset($values['group_form']) ? $values['group_form'] : [];

    if (!empty($groupForm)) {
        $itemRepo->subscribeGroupsToItem(
            $currentUser,
            'learnpath',
            $course,
            $session,
            $lpId,
            $groups
        );
        Display::addFlash(Display::return_message(get_lang('Updated')));
    }

    header("Location: $url");
    exit;
}

$message = Display::return_message(get_lang('UserLpSubscriptionDescription'));
$headers = [
    get_lang('SubscribeUsersToLp'),
    get_lang('SubscribeGroupsToLp'),
];

$items = [$formUsers->toHtml(), $form->toHtml()];

if ($allowUserGroups) {
    $headers[] = get_lang('SubscribeUserGroupsToLp');
    $items[] = $formUserGroup->toHtml();
}

$menu = $oLP->build_action_menu(true, false, true, false);

$tpl = new Template();
$tabs = Display::tabs($headers, $items);
$tpl->assign('content', $menu.$message.$tabs);
$tpl->display_one_col_template();
