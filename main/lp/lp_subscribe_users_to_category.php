<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Repository\CourseRepository;
use Chamilo\CoreBundle\Entity\Repository\ItemPropertyRepository;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpCategoryUser;
use Chamilo\UserBundle\Entity\User;

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
if ($subscriptionSettings['allow_add_users_to_lp_category'] == false) {
    api_not_allowed(true);
}

$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();
$sessionId = api_get_session_id();

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
/** @var CItemProperty $itemProperty */
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
$formUsers = new FormValidator('lp_edit', 'post', $url);
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

// Building the form for Groups
$tpl = new Template();

$currentUser = api_get_user_entity(api_get_user_id());

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

        $em->merge($category);
        $em->flush();
        Display::addFlash(Display::return_message(get_lang('Updated')));
    }

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
} else {
    $headers = [
        get_lang('SubscribeUsersToLpCategory'),
        get_lang('SubscribeGroupsToLpCategory'),
    ];
    $tabs = Display::tabs($headers, [$formUsers->toHtml(), $form->toHtml()]);
    $tpl->assign('content', $tabs);
    $tpl->display_one_col_template();
}
