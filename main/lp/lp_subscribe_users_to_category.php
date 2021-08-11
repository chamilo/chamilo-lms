<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Repository\CourseRepository;
use Chamilo\CoreBundle\Entity\Repository\ItemPropertyRepository;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpCategoryUser;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

$is_allowed_to_edit = api_is_allowed_to_edit(false, true, false, false);

if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

$categoryId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (empty($categoryId)) {
    api_not_allowed(true);
}

$subscriptionSettings = learnpath::getSubscriptionSettings();
if (false == $subscriptionSettings['allow_add_users_to_lp_category']) {
    api_not_allowed(true);
}

$allowUserGroups = api_get_configuration_value('allow_lp_subscription_to_usergroups');
$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();
$sessionId = api_get_session_id();
$currentUser = api_get_user_entity(api_get_user_id());

$em = Database::getManager();

/** @var CLpCategory $category */
$category = $em->getRepository('ChamiloCourseBundle:CLpCategory')->find($categoryId);

if (!$category) {
    api_not_allowed(true);
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => strip_tags($category->getName())];

$url = api_get_self().'?'.api_get_cidreq().'&action=add_users_to_category&id='.$categoryId;

$message = Display::return_message(get_lang('UserLpCategorySubscriptionDescription'));

// Building the form for Groups
$form = new FormValidator('lp_edit', 'post', $url);
$form->addElement('hidden', 'group_form', 1);
$form->addLabel('', $message);

// Group list
$groupList = \CourseManager::get_group_list_of_course(
    api_get_course_id(),
    $sessionId,
    1
);
$groupChoices = array_column($groupList, 'name', 'id');

$session = api_get_session_entity($sessionId);

/** @var CourseRepository $courseRepo */
$courseRepo = $em->getRepository('ChamiloCoreBundle:Course');
/** @var ItemPropertyRepository $itemRepo */
$itemRepo = $em->getRepository('ChamiloCourseBundle:CItemProperty');

$course = api_get_course_entity($courseId);

// Subscribed groups to a LP
$subscribedGroupsInLp = $itemRepo->getGroupsSubscribedToItem(
    'learnpath_category',
    $categoryId,
    $course,
    $session
);

$selectedGroupChoices = [];
/** @var \Chamilo\CourseBundle\Entity\CItemProperty $itemProperty */
foreach ($subscribedGroupsInLp as $itemProperty) {
    $selectedGroupChoices[] = $itemProperty->getGroup()->getId();
}

$groupMultiSelect = $form->addElement(
    'advmultiselect',
    'groups',
    get_lang('Groups'),
    $groupChoices
);

// submit button
$form->addButtonSave(get_lang('Save'));

// UserGroup
if ($allowUserGroups) {
    $formUserGroup = new FormValidator('lp_edit_usergroup', 'post', $url);
    $formUserGroup->addHidden('usergroup_form', 1);

    $userGroup = new UserGroup();
    $conditions = [];
    $conditions['where'] = [' usergroup.course_id = ? ' => $courseId];
    $groups = $userGroup->getUserGroupInCourse($conditions);
    $allOptions = array_column($groups, 'name', 'id');
    $items = $userGroup->getGroupsByLpCategory($categoryId, $courseId, $sessionId);

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
        $table = Database::get_course_table(TABLE_LP_CATEGORY_REL_USERGROUP);
        if (isset($values['usergroups'])) {
            $userGroups = $values['usergroups'];
            foreach ($selectedUserGroupChoices as $userGroupId) {
                $userGroupId = (int) $userGroupId;
                if (!in_array($userGroupId, $userGroups)) {
                    $sql = "DELETE FROM $table
                            WHERE
                                c_id = $courseId AND
                                lp_category_id = $categoryId AND
                                usergroup_id = $userGroupId
                                $sessionCondition
                                ";
                    Database::query($sql);
                    $userList = $userGroup->get_users_by_usergroup($userGroupId);
                    foreach ($userList as $userId) {
                        $user = api_get_user_entity($userId);
                        $criteria = Criteria::create()->where(
                            Criteria::expr()->eq('user', $user)
                        );
                        $userCategory = $category->getUsers()->matching($criteria)->first();
                        if ($userCategory) {
                            $category->removeUsers($userCategory);
                        }
                    }
                }
            }

            foreach ($userGroups as $userGroupId) {
                $userGroupId = (int) $userGroupId;
                $sql = "SELECT id FROM $table
                        WHERE
                            c_id = $courseId AND
                            lp_category_id = $categoryId AND
                            usergroup_id = $userGroupId
                            $sessionCondition
                        ";
                $result = Database::query($sql);

                if (0 == Database::num_rows($result)) {
                    $params = [
                        'lp_category_id' => $categoryId,
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

            $groups = $userGroup->getGroupsByLpCategory($categoryId, $courseId, $sessionId);
            $userList = [];
            foreach ($groups as $groupId) {
                $userList = $userGroup->get_users_by_usergroup($groupId);
                foreach ($userList as $userId) {
                    $user = api_get_user_entity($userId);
                    if ($user) {
                        $categoryUser = new CLpCategoryUser();
                        $categoryUser->setUser($user);
                        $category->addUser($categoryUser);
                    }
                }
            }

            $em->persist($category);
            $em->flush();
            Display::addFlash(Display::return_message(get_lang('Updated')));
        } else {
            foreach ($category->getUsers() as $userCategory) {
                $category->removeUsers($userCategory);
            }

            // Clean all
            $sql = "DELETE FROM $table
                    WHERE
                        c_id = $courseId AND
                        lp_category_id = $categoryId
                        $sessionCondition
                    ";
            Database::query($sql);
            $em->persist($category);
            $em->flush();
        }

        header("Location: $url");
        exit;
    }
}

$defaults = [];
if (!empty($selectedGroupChoices)) {
    $defaults['groups'] = $selectedGroupChoices;
}
$form->setDefaults($defaults);

// Getting subscribe users to the course.
$choices = [];
if (empty($sessionId)) {
    $subscribedUsers = $courseRepo->getSubscribedStudents($course);
    $subscribedUsers = $subscribedUsers->getQuery();
    $subscribedUsers = $subscribedUsers->execute();

    // Getting all users in a nice format.
    /** @var User $user */
    foreach ($subscribedUsers as $user) {
        $choices[$user->getId()] = $user->getCompleteNameWithClasses();
    }
} else {
    $users = CourseManager::get_user_list_from_course_code($course->getCode(), $sessionId);
    foreach ($users as $user) {
        $choices[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
    }
}

// Getting subscribed users to a category.
$subscribedUsersInCategory = $category->getUsers();

$selectedChoices = [];
foreach ($subscribedUsersInCategory as $item) {
    $selectedChoices[] = $item->getUser()->getId();
}

// Building the form for Users
$formUsers = new FormValidator('lp_edit_users', 'post', $url);
$formUsers->addElement('hidden', 'user_form', 1);
$formUsers->addLabel('', $message);

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

$tpl = new Template();
if ($formUsers->validate()) {
    $values = $formUsers->getSubmitValues();

    // Subscribing users
    $users = isset($values['users']) ? $values['users'] : [];
    $userForm = isset($values['user_form']) ? $values['user_form'] : [];
    if (!empty($userForm)) {
        $deleteUsers = [];
        if ($subscribedUsersInCategory) {
            /** @var CLpCategoryUser $user */
            foreach ($subscribedUsersInCategory as $user) {
                $userId = $user->getUser()->getId();

                if (!in_array($userId, $users)) {
                    $category->removeUsers($user);
                }
            }
        }

        foreach ($users as $userId) {
            $categoryUser = new CLpCategoryUser();
            $user = UserManager::getRepository()->find($userId);
            if ($user) {
                $categoryUser->setUser($user);
                $category->addUser($categoryUser);
            }
        }

        $em->persist($category);
        $em->flush();
        Display::addFlash(Display::return_message(get_lang('Updated')));
    }

    header("Location: $url");
    exit;
}

if ($form->validate()) {
    $values = $form->getSubmitValues();
    // Subscribing groups
    $groups = isset($values['groups']) ? $values['groups'] : [];
    $groupForm = isset($values['group_form']) ? $values['group_form'] : [];

    if (!empty($groupForm)) {
        $itemRepo->subscribeGroupsToItem(
            $currentUser,
            'learnpath_category',
            $course,
            $session,
            $categoryId,
            $groups
        );
        Display::addFlash(Display::return_message(get_lang('Updated')));
    }

    header("Location: $url");
    exit;
}

$headers = [
    get_lang('SubscribeUsersToLpCategory'),
    get_lang('SubscribeGroupsToLpCategory'),
];
$items = [$formUsers->toHtml(), $form->toHtml()];

if ($allowUserGroups) {
    $headers[] = get_lang('SubscribeClassesToLpCategory');
    $items[] = $formUserGroup->toHtml();
}
$tabs = Display::tabs($headers, $items);
$tpl->assign('content', $tabs);
$tpl->display_one_col_template();
