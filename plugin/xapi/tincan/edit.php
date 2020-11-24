<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../../../main/inc/global.inc.php';

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

$langEditActivity = $plugin->get_lang('EditActivity');

$frmActivity = new FormValidator('frm_activity', 'post', api_get_self()."?$cidReq&edit={$toolLaunch->getId()}");
$frmActivity->addText('title', get_lang('Title'));
$frmActivity->addTextarea('description', get_lang('Description'));
$frmActivity->addCheckBox('allow_multiple_attempts', '', get_lang('AllowMultipleAttempts'));
$frmActivity->addButtonAdvancedSettings('advanced_params');
$frmActivity->addHtml('<div id="advanced_params_options" style="display:none">');
$frmActivity->addUrl('launch_url', $plugin->get_lang('ActivityLaunchUrl'), true);
$frmActivity->addUrl('activity_id', $plugin->get_lang('ActivityId'), true);
$frmActivity->addUrl('activity_type', $plugin->get_lang('ActivityType'), true);
$frmActivity->addHtml('</div>');
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
    'lrs_auth',
    [
        $plugin->get_lang('lrs_auth_username'),
        $plugin->get_lang('lrs_auth_username_help'),
    ],
    false
);
$frmActivity->addText(
    'lrs_auth',
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
        ->setDescription(empty($values['description']) ? null : $values['description'])
        ->setLaunchUrl($values['launch_url'])
        ->setActivityId($values['activity_id'])
        ->setActivityType($values['activity_type'])
        ->setAllowMultipleAttempts(
            isset($values['allow_multiple_attempts'])
        );

    if (!empty($values['lrs_url']) && !empty($values['lrs_auth'])) {
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
        'activity_id' => $toolLaunch->getActivityId(),
        'activity_type' => $toolLaunch->getActivityType(),
        'launch_url' => $toolLaunch->getLaunchUrl(),
        'allow_multiple_attempts' => $toolLaunch->isAllowMultipleAttempts(),
        'lrs_url' => $toolLaunch->getLrsUrl(),
        'lrs_auth_username' => $toolLaunch->getLrsAuthUsername(),
        'lrs_auth_password' => $toolLaunch->getLrsAuthPassword(),
    ]
);

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    'index.php?'.api_get_cidreq()
);

$pageContent = $frmActivity->returnForm();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => $plugin->get_lang('ToolTinCan')];

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
