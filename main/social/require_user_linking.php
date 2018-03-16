<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$isAllowed = api_get_configuration_value('show_link_request_hrm_user') && api_is_drh();

if (!$isAllowed) {
    api_not_allowed(true);
}

$hrm = api_get_user_entity(api_get_user_id());

$usersRequested = UserManager::getUsersFollowedByUser(
    $hrm->getId(),
    null,
    null,
    false,
    false,
    null,
    null,
    null,
    null,
    null,
    null,
    HRM_REQUEST
);

$requestOptions = [];

foreach ($usersRequested as $userRequested) {
    $userInfo = api_get_user_info($userRequested['user_id']);

    if (!$userInfo) {
        continue;
    }

    $requestOptions[$userInfo['user_id']] = $userInfo['complete_name'];
}

$form = new FormValidator('require_user_linking');
$form->addUserAvatar('hrm', get_lang('DRH'), 'medium');
$form->addSelectAjax(
    'users',
    [get_lang('LinkMeToStudent'), get_lang('LinkMeToStudentComment')],
    $requestOptions,
    [
        'multiple' => 'multiple',
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
    ]
);
$form->addButtonSave(get_lang('RequestLinkToStudent'));
$form->setDefaults([
    'hrm' => $hrm,
    'users' => array_keys($requestOptions),
]);

if ($form->validate()) {
    $values = $form->exportValues();
    //Avoid self-subscribe as request
    $usersId = array_filter($values['users'], function ($userId) use ($hrm) {
        return (int) $userId != $hrm->getId();
    });

    UserManager::clearHrmRequestsForUser($hrm, $usersId);
    UserManager::requestUsersToHRManager($hrm->getId(), $usersId, false);

    Display::addFlash(
        Display::return_message(get_lang('LinkingRequestsAdded'), 'success')
    );

    header('Location: '.api_get_self());
    exit;
}

$usersAssigned = UserManager::get_users_followed_by_drh($hrm->getId());

$content = $form->returnForm();

$content .= Display::page_subheader(get_lang('AssignedUsersListToHumanResourcesManager'));
$content .= '<div class="row">';

foreach ($usersAssigned as $userAssigned) {
    $userAssigned = api_get_user_info($userAssigned['user_id']);
    $userPicture = isset($userAssigned["avatar_medium"]) ? $userAssigned["avatar_medium"] : $userAssigned["avatar"];
    $studentLink = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$userAssigned['user_id'];

    $content .= '
        <div class="col-sm-4 col-md-3">
            <div class="media">
                <div class="media-left">
    ';
    $content .= '<a href="'.$studentLink.'">';
    $content .= Display::img($userPicture, $userAssigned['complete_name'], ['class' => 'media-object'], false);
    $content .= '</a>';
    $content .= '
                </div>
                <div class="media-body">
                    <h4 class="media-heading"><a href="'.$studentLink.'">'.$userAssigned['complete_name'].'</a></h4>
                    '.$userAssigned['username'].'
                </div>
            </div>
        </div>
    ';
}

$content .= '</div>';

$toolName = get_lang('RequestLinkingToUser');

$view = new Template($toolName);
$view->assign('header', $toolName);
$view->assign('content', $content);
$view->display_one_col_template();
