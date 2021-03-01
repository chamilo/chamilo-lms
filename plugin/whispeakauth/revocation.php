<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\WhispeakAuth\Request\ApiRequest;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = WhispeakAuthPlugin::create();

api_protect_admin_script(true);

$plugin->protectTool();

$pageContent = '';

$form = new FormValidator('frm_revocation', 'GET');
$form->setAttribute('onsubmit', "return confirm('".addslashes(get_lang('AreYouSureToDelete'))."');");
$slctUsers = $form->addSelectAjax(
    'users',
    get_lang('Users'),
    [],
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
        'id' => 'user_id',
        'multiple' => true,
    ]
);
$form->addButton('asubmit', $plugin->get_lang('DeleteEnrollments'), 'times', 'danger');
$form->addRule('users', get_lang('ThisFieldIsRequired'), 'required');

$userIds = [];

if ($form->validate()) {
    $formValues = $form->exportValues();
    $userIds = $formValues['users'] ?: [];

    /** @var int $userId */
    foreach ($userIds as $userId) {
        $user = api_get_user_entity($userId);

        if (null === $user) {
            continue;
        }

        $slctUsers->addOption($user->getCompleteNameWithUsername(), $user->getId());

        $request = new ApiRequest();

        $pageContent .= Display::page_subheader($user->getCompleteNameWithUsername(), null, 'h4');

        try {
            $request->deleteEnrollment($user);

            $response = WhispeakAuthPlugin::deleteEnrollment($user->getId());

            $pageContent .= Display::return_message(
                $plugin->get_lang('EnrollmentDeleted'),
                'success'
            );
        } catch (Exception $e) {
            $pageContent .= Display::return_message(
                $e->getMessage(),
                'error'
            );
        }
    }
}

$interbreadcrumb[] = [
    'name' => get_lang('Administration'),
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
];
$interbreadcrumb[] = [
    'name' => $plugin->get_title(),
    'url' => 'admin.php',
];

$actionsLeft = Display::url(
    Display::return_icon('back.png', $plugin->get_lang('Back'), [], ICON_SIZE_MEDIUM),
    'admin.php'
);

$pageTitle = $plugin->get_lang('Revocation');

$template = new Template($pageTitle);
$template->assign('actions', Display::toolbarAction('whispeak_admin', [$actionsLeft]));
$template->assign('header', $pageTitle);
$template->assign(
    'content',
    $form->returnForm().PHP_EOL.$pageContent
);
$template->display_one_col_template();
