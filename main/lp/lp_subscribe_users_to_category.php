<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpCategoryUser;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

$is_allowed_to_edit = api_is_allowed_to_edit(false, true, false, false);

if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

$categoryId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($categoryId)) {
    api_not_allowed(true);
}

$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();

$em = Database::getManager();

/** @var \Chamilo\CourseBundle\Entity\CLpCategory $category */
$category = $em->getRepository('ChamiloCourseBundle:CLpCategory')->find($categoryId);

if (!$category) {
    api_not_allowed(true);
}

$interbreadcrumb[] = array(
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths')
);
$interbreadcrumb[] = array('url' => '#', 'name' => strip_tags($category->getName()));

// Find course.
$course = $em->getRepository('ChamiloCoreBundle:Course')->find($courseId);

// Getting subscribe users to the course.
$subscribedUsers = $em->getRepository('ChamiloCoreBundle:Course')->getSubscribedStudents($course);
$subscribedUsers = $subscribedUsers->getQuery();
$subscribedUsers = $subscribedUsers->execute();

// Getting all users in a nice format.
$choices = array();
/** @var User $user */
foreach ($subscribedUsers as $user) {
    $choices[$user->getUserId()] = $user->getCompleteNameWithClasses();
}

// Getting subscribed users to a category.
$subscribedUsersInCategory = $category->getUsers();

$selectedChoices = array();
foreach ($subscribedUsersInCategory as $item) {
    $selectedChoices[] = $item->getUser()->getId();
}

$url = api_get_self().'?'.api_get_cidreq().'&action=add_users_to_category&id='.$categoryId;

//Building the form for Users
$formUsers = new \FormValidator('lp_edit', 'post', $url);
$formUsers->addElement('hidden', 'user_form', 1);

$userMultiSelect = $formUsers->addElement(
    'advmultiselect',
    'users',
    get_lang('Users'),
    $choices
);
$formUsers->addButtonSave(get_lang('Save'));

$defaults = array();

if (!empty($selectedChoices)) {
    $defaults['users'] = $selectedChoices;
}

$formUsers->setDefaults($defaults);

// Building the form for Groups
$tpl = new Template();

$currentUser = $em->getRepository('ChamiloUserBundle:User')->find(api_get_user_id());

if ($formUsers->validate()) {
    $values = $formUsers->getSubmitValues();

    // Subscribing users
    $users = isset($values['users']) ? $values['users'] : [];

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
        $categoryUser->setUser($user);
        $category->addUser($categoryUser);
    }

    $em->merge($category);
    $em->flush();
    Display::addFlash(Display::return_message(get_lang('Updated')));

    header("Location: $url");
    exit;
} else {
    $headers = [get_lang('SubscribeUsersToLp'), get_lang('SubscribeGroupsToLp')];
    $tabs = $formUsers->toHtml();
    $tpl->assign('tabs', $tabs);
}

$layout = $tpl->get_template('learnpath/subscribe_users.tpl');
$tpl->display($layout);
