<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../main/inc/global.inc.php';

/**
 * Check whether the launch belongs to the current course/session context.
 */
function xapi_edit_matches_current_context(XApiToolLaunch $toolLaunch): bool
{
    $currentCourse = api_get_course_entity();
    $currentSession = api_get_session_entity();

    if (null === $currentCourse || null === $toolLaunch->getCourse()) {
        return false;
    }

    if ($toolLaunch->getCourse()->getId() !== $currentCourse->getId()) {
        return false;
    }

    $toolSession = $toolLaunch->getSession();

    if (null === $currentSession && null === $toolSession) {
        return true;
    }

    if (null === $currentSession || null === $toolSession) {
        return false;
    }

    return $currentSession->getId() === $toolSession->getId();
}

api_protect_course_script(true);
api_protect_teacher_script();

$request = Container::getRequest();
$em = Database::getManager();

$toolLaunch = $em->find(
    XApiToolLaunch::class,
    $request->query->getInt('edit')
);

if (null === $toolLaunch || !xapi_edit_matches_current_context($toolLaunch)) {
    api_not_allowed(true);
}

$cidReq = api_get_cidreq();
$plugin = XApiPlugin::create();

$toolIsCmi5 = 'cmi5' === strtolower(trim((string) $toolLaunch->getActivityType()));
$toolIsTinCan = !$toolIsCmi5;

$langEditActivity = $plugin->get_lang('EditActivity');

$frmActivity = new FormValidator(
    'frm_activity',
    'post',
    api_get_self()."?$cidReq&edit={$toolLaunch->getId()}"
);

$frmActivity->addText('title', get_lang('Title'));
$frmActivity->addTextarea('description', get_lang('Description'));

if ($toolIsTinCan) {
    $frmActivity->addButtonAdvancedSettings('advanced_params');
    $frmActivity->addHtml('<div id="advanced_params_options" style="display:none">');
    $frmActivity->addCheckBox(
        'allow_multiple_attempts',
        '',
        get_lang('Allow multiple attempts')
    );
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
$frmActivity->applyFilter('lrs_auth_username', 'trim');
$frmActivity->applyFilter('lrs_auth_password', 'trim');

if ($frmActivity->validate()) {
    $values = $frmActivity->exportValues();

    $toolLaunch
        ->setTitle(trim((string) $values['title']))
        ->setDescription(
            '' === trim((string) ($values['description'] ?? ''))
                ? null
                : trim((string) $values['description'])
        )
    ;

    if ($toolIsTinCan) {
        $toolLaunch->setAllowMultipleAttempts(!empty($values['allow_multiple_attempts']));
    }

    $lrsUrl = trim((string) ($values['lrs_url'] ?? ''));
    $lrsAuthUsername = trim((string) ($values['lrs_auth_username'] ?? ''));
    $lrsAuthPassword = trim((string) ($values['lrs_auth_password'] ?? ''));

    if ('' !== $lrsUrl || '' !== $lrsAuthUsername || '' !== $lrsAuthPassword) {
        $toolLaunch
            ->setLrsUrl('' !== $lrsUrl ? $lrsUrl : null)
            ->setLrsAuthUsername('' !== $lrsAuthUsername ? $lrsAuthUsername : null)
            ->setLrsAuthPassword('' !== $lrsAuthPassword ? $lrsAuthPassword : null)
        ;
    }

    $em->persist($toolLaunch);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ActivityUpdated'), 'success')
    );

    header('Location: start.php?'.$cidReq);
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
    'start.php?'.$cidReq
);

$pageContent = $frmActivity->returnForm();

$interbreadcrumb[] = [
    'url' => 'start.php?'.$cidReq,
    'name' => $plugin->get_lang('ToolTinCan'),
];

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
