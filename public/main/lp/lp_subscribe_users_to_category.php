<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpCategoryUser;
use Chamilo\CoreBundle\Entity\User;

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

$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();

$em = Database::getManager();

/** @var CLpCategory $category */
$repo = Container::getLpCategoryRepository();
$category = $repo->find($categoryId);

if (!$category) {
    api_not_allowed(true);
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => strip_tags($category->getName())];

$url = api_get_self().'?'.api_get_cidreq().'&action=add_users_to_category&id='.$categoryId;

$message = Display::return_message(get_lang('Note that the inscription of users in a category will override the inscription of users in the Learning Path'));

// Building the form for Groups
$form = new FormValidator('lp_edit', 'post', $url);
$form->addElement('hidden', 'group_form', 1);
$form->addLabel('', $message);

// Group list
$groupList = \CourseManager::get_group_list_of_course(
    api_get_course_id(),
    api_get_session_id(),
    1
);
$groupChoices = array_column($groupList, 'name', 'id');

$session = null;
if (!empty($sessionId)) {
    $session = api_get_session_entity($sessionId);
}
$courseRepo = Container::getCourseRepository();
$course = api_get_course_entity($courseId);

// Subscribed groups to a LP
$links = $category->getResourceNode()->getResourceLinks();

$selectedGroupChoices = [];
foreach ($links as $link) {
    if (null !== $link->getGroup()) {
        $selectedGroupChoices[] = $link->getGroup()->getId();
    }
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
$subscribedUsers = $courseRepo->getSubscribedStudents($course);
$subscribedUsers = $subscribedUsers->getQuery();
$subscribedUsers = $subscribedUsers->execute();

// Getting all users in a nice format.
$choices = [];
/** @var User $user */
foreach ($subscribedUsers as $user) {
    $choices[$user->getUserId()] = $user->getCompleteNameWithClasses();
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
    $users = $values['users'] ?? [];
    $userForm = $values['user_form'] ?? [];

    if (!empty($userForm)) {
        $deleteUsers = [];
        if ($subscribedUsersInCategory) {
            /** @var CLpCategoryUser $user */
            foreach ($subscribedUsersInCategory as $user) {
                $userId = $user->getUser()->getId();

                if (!in_array($userId, $users, true)) {
                    $category->removeUsers($user);
                }
            }
        }

        foreach ($users as $userId) {
            $categoryUser = new CLpCategoryUser();
            $user = UserManager::getRepository()->find($userId);
            $categoryUser->setUser($user);
            $category->addUser($categoryUser);
        }

        $em->persist($category);
        $em->flush();
        Display::addFlash(Display::return_message(get_lang('Update successful')));
    }

    // Subscribing groups
    $groups = $values['groups'] ?? [];
    $groupForm = $values['group_form'] ?? [];

    if (!empty($groupForm)) {
        if (!empty($selectedGroupChoices)) {
            $diff = array_diff($selectedGroupChoices, $groups);
            if (!empty($diff)) {
                foreach ($diff as $groupIdToDelete) {
                    foreach ($links as $link) {
                        if ($link->getGroup() && $link->getGroup()->getIid()) {
                            $repo->getEntityManager()->remove($link);
                        }
                    }
                }
                $repo->getEntityManager()->flush();
            }
        }

        foreach ($groups as $groupId) {
            $group = api_get_group_entity($groupId);
            $repo->addResourceToCourseGroup($category->getResourceNode(), $group);
        }
        $repo->getEntityManager()->flush();

        Display::addFlash(Display::return_message(get_lang('Update successful')));
    }

    header("Location: $url");
    exit;
} else {
    $headers = [
        get_lang('Subscribe users to category'),
        get_lang('Subscribe groups to category'),
    ];
    $tabs = Display::tabs($headers, [$formUsers->toHtml(), $form->toHtml()]);
    $tpl->assign('content', $tabs);
    $tpl->display_one_col_template();
}
