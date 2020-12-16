<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);
api_protect_teacher_script();

$request = HttpRequest::createFromGlobals();

$em = Database::getManager();

$toolLaunch = $em->find(
    ToolLaunch::class,
    $request->query->getInt('edit')
);

if (null === $toolLaunch) {
    header('Location: '.api_get_course_url());
    exit;
}

$course = api_get_course_entity();
$session = api_get_session_entity();

$cidReq = api_get_cidreq();

$plugin = XApiPlugin::create();

$toolIsCmi5 = 'cmi5' === $toolLaunch->getActivityType();
$toolIsTinCan = !$toolIsCmi5;

$langEditActivity = $plugin->get_lang('EditActivity');

$frmActivity = new FormValidator('frm_activity', 'post', api_get_self()."?$cidReq&edit={$toolLaunch->getId()}");
$frmActivity->addText('title', get_lang('Title'));
$frmActivity->addTextarea('description', get_lang('Description'));

if ($toolIsTinCan) {
    $frmActivity->addButtonAdvancedSettings('advanced_params');
    $frmActivity->addHtml('<div id="advanced_params_options" style="display:none">');
    $frmActivity->addCheckBox('allow_multiple_attempts', '', get_lang('AllowMultipleAttempts'));
    $frmActivity->addHtml('</div>');
}

$frmActivity->addButtonAdvancedSettings('lrs_params', $plugin->get_lang('LrsConfiguration'));
$frmActivity->addHtml('<div id="lrs_params_options" style="display:none">');
$frmActivity->addText(
    'lrs_url',
    [
        $plugin->get_lang('lrs_url'),
        $plugin->get_lang('lrs_url_help'),
    ],
    false
);
$frmActivity->addText(
    'lrs_auth_username',
    [
        $plugin->get_lang('lrs_auth_username'),
        $plugin->get_lang('lrs_auth_username_help'),
    ],
    false
);
$frmActivity->addText(
    'lrs_auth_password',
    [
        $plugin->get_lang('lrs_auth_password'),
        $plugin->get_lang('lrs_auth_password_help'),
    ],
    false
);
$frmActivity->addHtml('</div>');
$frmActivity->addButtonUpdate(get_lang('Update'));
$frmActivity->applyFilter('title', 'trim');
$frmActivity->applyFilter('description', 'trim');
$frmActivity->applyFilter('lrs_url', 'trim');
$frmActivity->applyFilter('lrs_auth', 'trim');

if ($frmActivity->validate()) {
    $values = $frmActivity->exportValues();

    $toolLaunch
        ->setTitle($values['title'])
        ->setDescription(empty($values['description']) ? null : $values['description']);

    if ($toolIsTinCan && isset($values['allow_multiple_attempts'])) {
        $toolLaunch->setAllowMultipleAttempts(true);
    }

    if (!empty($values['lrs_url'])
        && !empty($values['lrs_auth_username'])
        && !empty($values['lrs_auth_password'])
    ) {
        $toolLaunch
            ->setLrsUrl($values['lrs_url'])
            ->setLrsAuthUsername($values['lrs_auth_username'])
            ->setLrsAuthPassword($values['lrs_auth_password']);
    }

    $em->persist($toolLaunch);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ActivityUpdated'), 'success')
    );

    header('Location: '.api_get_course_url());
    exit;
}

$frmActivity->setDefaults(
    [
        'title' => $toolLaunch->getTitle(),
        'description' => $toolLaunch->getDescription(),
        'allow_multiple_attempts' => $toolLaunch->isAllowMultipleAttempts(),
        'lrs_url' => $toolLaunch->getLrsUrl(),
        'lrs_auth_username' => $toolLaunch->getLrsAuthUsername(),
        'lrs_auth_password' => $toolLaunch->getLrsAuthPassword(),
    ]
);

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    'start.php?'.api_get_cidreq()
);

$pageContent = $frmActivity->returnForm();

$interbreadcrumb[] = ['url' => 'start.php', 'name' => $plugin->get_lang('ToolTinCan')];

$view = new Template($langEditActivity);
$view->assign('header', $langEditActivity);
$view->assign(
    'actions',
    Display::toolbarAction(
        'xapi_actions',
        [$actions]
    )
);
$view->assign('content', $pageContent);
$view->display_one_col_template();
