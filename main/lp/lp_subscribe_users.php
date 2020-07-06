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

$is_allowed_to_edit = api_is_allowed_to_edit(false, true, false, false);

if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

$lpId = isset($_GET['lp_id']) ? (int) $_GET['lp_id'] : 0;

if (empty($lpId)) {
    api_not_allowed(true);
}

$subscriptionSettings = learnpath::getSubscriptionSettings();
if ($subscriptionSettings['allow_add_users_to_lp'] == false) {
    api_not_allowed(true);
}

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
foreach ($subscribedUsersInLp as $itemProperty) {
    $selectedChoices[] = $itemProperty->getToUser()->getId();
}

//Building the form for Users
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

// Building the form for Groups
$form = new FormValidator('lp_edit', 'post', $url);
$form->addElement('hidden', 'group_form', 1);

// Group list
$groupList = \CourseManager::get_group_list_of_course(
    api_get_course_id(),
    api_get_session_id(),
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
foreach ($subscribedGroupsInLp as $itemProperty) {
    $selectedGroupChoices[] = $itemProperty->getGroup()->getId();
}

$groupMultiSelect = $form->addElement(
    'advmultiselect',
    'groups',
    get_lang('Groups'),
    $groupChoices
);

$form->addButtonSave(get_lang('Save'));

$defaults = [];
if (!empty($selectedGroupChoices)) {
    $defaults['groups'] = $selectedGroupChoices;
}
$form->setDefaults($defaults);

$currentUser = api_get_user_entity(api_get_user_id());

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

$menu = $oLP->build_action_menu(true, false, true, false);

$tpl = new Template();
$tabs = Display::tabs($headers, [$formUsers->toHtml(), $form->toHtml()]);
$tpl->assign('content', $menu.$message.$tabs);
$tpl->display_one_col_template();
