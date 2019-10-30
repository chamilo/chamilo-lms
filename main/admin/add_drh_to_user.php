<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\UserBundle\Entity\User as UserEntity;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

if (!isset($_REQUEST['u'])) {
    api_not_allowed(true);
}

$em = Database::getManager();
$userRepository = UserManager::getRepository();
/** @var UserEntity $user */
$user = UserManager::getManager()->find($_REQUEST['u']);

if ($user === null) {
    api_not_allowed(true);
}

$subscribedUsers = $userRepository->getAssignedHrmUserList(
    $user->getId(),
    api_get_current_access_url_id()
);

$hrmOptions = [];
/** @var UserRelUser $subscribedUser */
foreach ($subscribedUsers as $subscribedUser) {
    /** @var UserEntity $hrm */
    $hrm = UserManager::getManager()->find($subscribedUser->getFriendUserId());

    if ($hrm === null) {
        continue;
    }

    $hrmOptions[$hrm->getId()] = UserManager::formatUserFullName($hrm, true);
}

$form = new FormValidator('assign_hrm');
$form->addUserAvatar('u', get_lang('User'), 'medium');
$form->addSelectAjax(
    'hrm',
    get_lang('Human Resource Managers list'),
    $hrmOptions,
    ['multiple' => 'multiple', 'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=user_by_role']
);
$form->addButtonSave(get_lang('Send message'));
$form->setDefaults([
    'u' => $user,
    'hrm' => array_keys($hrmOptions),
]);

if ($form->validate()) {
    /** @var UserRelUser $subscribedUser */
    foreach ($subscribedUsers as $subscribedUser) {
        $em->remove($subscribedUser);
    }
    $em->flush();

    $values = $form->exportValues();

    foreach ($values['hrm'] as $hrmId) {
        /** @var UserEntity $hrm */
        $hrm = UserManager::getManager()->find($hrmId);

        if ($hrm === null) {
            continue;
        }

        if ($hrm->getStatus() !== DRH) {
            continue;
        }

        UserManager::subscribeUsersToHRManager($hrm->getId(), [$user->getId()], false);
    }

    Display::addFlash(
        Display::return_message(get_lang('The assigned users have been updated'), 'success')
    );

    header('Location: '.api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$user->getId());
    exit;
}

$interbreadcrumb[] = ['name' => get_lang('Administration'), 'url' => 'index.php'];
$interbreadcrumb[] = ['name' => get_lang('User list'), 'url' => 'user_list.php'];
$interbreadcrumb[] = [
    'name' => UserManager::formatUserFullName($user),
    'url' => 'user_information.php?user_id='.$user->getId(),
];

$toolName = get_lang('Assign Human Resources Manager to user');

$view = new Template($toolName);
$view->assign('header', $toolName);
$view->assign('content', $form->returnForm());
$view->display_one_col_template();
