<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\UserBundle\Entity\User;

require_once '../inc/global.inc.php';

api_protect_course_script();

$is_allowed_to_edit = api_is_allowed_to_edit(false, true, false, false);

if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

$lpId = isset($_GET['lp_id']) ? intval($_GET['lp_id']) : 0;

if (empty($lpId)) {
    api_not_allowed(true);
}


$oLP = new learnpath(api_get_course_id(), $lpId, api_get_user_id());

$interbreadcrumb[] = array(
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths')
);

$interbreadcrumb[] = array(
    'url' => api_get_self()."?action=build&lp_id=".$oLP->get_id().'&'.api_get_cidreq(),
    'name' => $oLP->get_name()
);

$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();

$url = api_get_self().'?'.api_get_cidreq().'&lp_id='.$lpId;
$lp = new \learnpath($courseCode, $lpId, api_get_user_id());
$em = Database::getManager();

$session = null;
if (!empty($sessionId)) {
    $session = $em->getRepository('ChamiloCoreBundle:Session')->find($sessionId);
}

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

// Getting subscribed users to a LP.
$subscribedUsersInLp = $em->getRepository('ChamiloCourseBundle:CItemProperty')->getUsersSubscribedToItem(
    'learnpath',
    $lpId,
    $course,
    $session
);
$selectedChoices = array();
foreach ($subscribedUsersInLp as $itemProperty) {
    $selectedChoices[] = $itemProperty->getToUser()->getId();
}

//Building the form for Users
$formUsers = new \FormValidator('lp_edit', 'post', $url);
$formUsers->addElement('hidden', 'user_form', 1);

$userMultiSelect = $formUsers->addElement('advmultiselect', 'users', get_lang('Users'), $choices);
$formUsers->addButtonSave(get_lang('Save'));

$defaults = array();

if (!empty($selectedChoices)) {
    $defaults['users'] = $selectedChoices;
}

$formUsers->setDefaults($defaults);

//Building the form for Groups

$form = new \FormValidator('lp_edit', 'post', $url);
$form->addElement('hidden', 'group_form', 1);

// Group list
$groupList = \CourseManager::get_group_list_of_course(
    api_get_course_id(),
    api_get_session_id(),
    1
);
$groupChoices = array_column($groupList, 'name', 'id');

// Subscribed groups to a LP
$subscribedGroupsInLp = $em->getRepository('ChamiloCourseBundle:CItemProperty')->getGroupsSubscribedToItem(
    'learnpath',
    $lpId,
    $course,
    $session
);

$selectedGroupChoices = array();
/** @var CItemProperty $itemProperty */
foreach ($subscribedGroupsInLp as $itemProperty) {
    $selectedGroupChoices[] = $itemProperty->getGroup()->getId();
}

$groupMultiSelect = $form->addElement('advmultiselect', 'groups', get_lang('Groups'), $groupChoices);

// submit button
$form->addButtonSave(get_lang('Save'));

$defaults = array();
if (!empty($selectedGroupChoices)) {
    $defaults['groups'] = $selectedGroupChoices;
}
$form->setDefaults($defaults);

$tpl = new Template();

$currentUser = $em->getRepository('ChamiloUserBundle:User')->find(api_get_user_id());

if ($form->validate()) {
    $values = $form->getSubmitValues();

    // Subscribing users
    $users = isset($values['users']) ? $values['users'] : [];
    $userForm = isset($values['user_form']) ? $values['user_form'] : [];

    if (!empty($userForm)) {
        $em->getRepository('ChamiloCourseBundle:CItemProperty')->subscribeUsersToItem(
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
        $em->getRepository('ChamiloCourseBundle:CItemProperty')->subscribeGroupsToItem(
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
} else {
    $headers = [get_lang('SubscribeUsersToLp'), get_lang('SubscribeGroupsToLp')];
    $tabs = Display::tabs($headers, [$formUsers->toHtml(),$form->toHtml()]);
    $tpl->assign('tabs', $tabs);
}

$layout = $tpl->get_template('learnpath/subscribe_users.tpl');
$tpl->display($layout);
